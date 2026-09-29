<?php

declare(strict_types=1);

final class JobSwitchException extends RuntimeException
{
    private $httpStatus;
    private $apiCode;
    private $currentJobId;

    public function __construct(int $httpStatus, int $apiCode, string $message, int $currentJobId = 0)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->apiCode = $apiCode;
        $this->currentJobId = $currentJobId;
    }

    public function getHttpStatus(): int { return $this->httpStatus; }
    public function getApiCode(): int { return $this->apiCode; }
    public function getCurrentJobId(): int { return $this->currentJobId; }
}

final class JobSwitchReadbackException extends RuntimeException
{
    private $currentJobId;

    public function __construct(string $message, int $currentJobId)
    {
        parent::__construct($message);
        $this->currentJobId = $currentJobId;
    }

    public function getCurrentJobId(): int { return $this->currentJobId; }
}

/**
 * Bridge that deliberately uses the same inherited Controller helpers as
 * app/controllers/Remotes.php::Change_Job__ntcs().
 *
 * Important: this class does NOT update JOB_lst.act.
 */
final class JobSwitchControllerBridge extends Controller
{
    private function currentJobSeq(int $deviceId): array
    {
        $values = $this->protocol_read_registers($deviceId, 4305, 2);
        if (count($values) < 2) {
            throw new RuntimeException('Controller JOB/SEQ read-back incomplete');
        }
        return [(int)$values[0], (int)$values[1]];
    }

    private function waitForJobSeq(int $deviceId, int $jobId, ?int $seqId): array
    {
        $actual = null;
        $readError = '';
        for ($attempt = 0; $attempt < 20; $attempt++) {
            if ($attempt > 0) usleep(200000);
            try {
                $actual = $this->currentJobSeq($deviceId);
                if ($actual[0] === $jobId && ($seqId === null || $actual[1] === $seqId)) {
                    return $actual;
                }
            } catch (Throwable $e) {
                $readError = $e->getMessage();
            }
        }
        $observed = $actual === null ? 'read failed: ' . $readError : $actual[0] . '/' . $actual[1];
        throw new JobSwitchReadbackException('Controller JOB/SEQ remained ' . $observed .
            ' (expected ' . $jobId . '/' . ($seqId === null ? '*' : $seqId) . ')', $actual[0] ?? 0);
    }

    public function switchControllerJob(int $jobId, int $seqId = 1): array
    {
        if ($jobId <= 0) {
            throw new RuntimeException('job_id invalid');
        }
        if ($seqId < 0) {
            throw new RuntimeException('seq_id invalid');
        }
        if (PHP_OS_FAMILY !== 'Linux') {
            throw new RuntimeException('Controller switch is supported on Linux only');
        }

        // Exactly the same source used by Remotes.php.
        $deviceId = (int)$this->lazyDeviceId(1);
        if ($deviceId < 1 || $deviceId > 255) {
            throw new RuntimeException('device_id invalid');
        }

        $isOp = $this->is_op_protocol_enabled();

        if ($isOp) {
            // Same OP write sequence as Remotes: 463 -> wait -> 464.
            $jobOk = $this->op_write(463, $jobId);
            if (!$jobOk) {
                throw new RuntimeException('OP write job_id failed');
            }

            // Give the controller 500 ms to apply the JOB before sending SEQ.
            usleep(500000);

            $seqOk = $this->op_write(464, $seqId);
            if (!$seqOk) {
                throw new RuntimeException('OP write seq_id failed');
            }
            $actual = $this->waitForJobSeq($deviceId, $jobId, $seqId);

            return [
                'protocol' => 'OP',
                'device_id' => $deviceId,
                'job_id' => $jobId,
                'seq_id' => $seqId,
                'current_job_id' => $actual[0],
                'op_commands' => [
                    'IDAS_WRITE_463_' . $jobId,
                    'IDAS_WRITE_464_' . $seqId,
                ],
            ];
        }

        // Match the confirmed ICDT request: one FC16, address 463, two words.
        $ok = $this->protocol_write_registers($deviceId, 463, [$jobId, $seqId]);
        if (!$ok) throw new RuntimeException('MODBUS write JOB/SEQ pair failed');
        $actual = $this->waitForJobSeq($deviceId, $jobId, $seqId);

        return [
            'protocol' => 'MODBUS',
            'device_id' => $deviceId,
            'job_id' => $jobId,
            'seq_id' => $seqId,
            'current_job_id' => $actual[0],
            'command' => 'FC16_463_' . $jobId . '_' . $seqId,
        ];
    }
}

final class JobSwitchService
{
    private $controllerDatabasePath;
    private $controllerFactory;

    public function __construct(array $options = [])
    {
        $controllerRoot = $options['controller_root'] ?? IDAS_PATH_CONTROLLER_ROOT;
        $this->controllerDatabasePath =
            $options['controller_database_path'] ?? $controllerRoot . '/KLS_NTCS.Lin';

        $this->controllerFactory = $options['controller_factory'] ?? static function () {
            return new JobSwitchControllerBridge();
        };
    }

    public function switchJob(int $targetJobId, int $seqId = 1): array
    {
        $startedAt = microtime(true);

        // Controller register 463 supports the job numbers defined by NTCS.
        if ($targetJobId <= 0) {
            throw new JobSwitchException(400, 3003, 'job_id 無效');
        }
        if ($seqId < 0 || $seqId > 50) {
            throw new JobSwitchException(400, 3003, 'seq_id 無效');
        }

        // Only READ the JOB table to prevent switching to a recipe that does not exist.
        // Never modify JOB_lst.act here.
        if (!is_file($this->controllerDatabasePath) || !is_readable($this->controllerDatabasePath)) {
            throw new JobSwitchException(503, 3001, '控制器資料庫無法讀取');
        }

        try {
            $pdo = idas_sqlite_connect(
                $this->controllerDatabasePath,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->prepare('SELECT 1 FROM JOB_lst WHERE JOBID = :job_id LIMIT 1');
            $stmt->execute([':job_id' => $targetJobId]);
            if (!$stmt->fetchColumn()) {
                throw new JobSwitchException(404, 3001, '目標 Job 不存在');
            }
        } catch (JobSwitchException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->log('JOB_CHECK_FAILED ' . $e->getMessage());
            throw new JobSwitchException(500, 3001, '確認目標 Job 失敗：' . $e->getMessage());
        }

        try {
            $controller = call_user_func($this->controllerFactory);
            if (!is_object($controller) || !method_exists($controller, 'switchControllerJob')) {
                throw new RuntimeException('Controller switch bridge unavailable');
            }

            // No JOB_lst.act update, no act shortcut, no login-state DB switch.
            // Every request really sends the Controller switch command.
            $switch = $controller->switchControllerJob($targetJobId, $seqId);
        } catch (Throwable $e) {
            $this->log(
                'CONTROLLER_SWITCH_FAILED target_job_id=' . $targetJobId .
                ' seq_id=' . $seqId .
                ' error=' . $e->getMessage()
            );
            throw new JobSwitchException(
                502,
                3001,
                'Controller JOB/SEQ 切換失敗：' . $e->getMessage(),
                $e instanceof JobSwitchReadbackException ? $e->getCurrentJobId() : 0
            );
        }

        $elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);

        $this->log(
            'SUCCESS target_job_id=' . $targetJobId .
            ' seq_id=' . $seqId .
            ' protocol=' . ($switch['protocol'] ?? 'UNKNOWN') .
            ' device_id=' . ($switch['device_id'] ?? 0) .
            ' elapsed_ms=' . $elapsedMs
        );

        return [
            'switch_status' => 1,
            'job_id' => $targetJobId,
            'seq_id' => $seqId,
            'current_job_id' => $switch['current_job_id'],
            'protocol' => $switch['protocol'] ?? null,
            'device_id' => $switch['device_id'] ?? null,
            'elapsed_ms' => $elapsedMs,
        ];
    }

    private function log(string $message): void
    {
        error_log('[job-switch] ' . $message);
    }
}
