<?php

declare(strict_types=1);

final class JobSwitchException extends RuntimeException
{
    private $httpStatus;
    private $apiCode;

    public function __construct(int $httpStatus, int $apiCode, string $message)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->apiCode = $apiCode;
    }

    public function getHttpStatus(): int { return $this->httpStatus; }
    public function getApiCode(): int { return $this->apiCode; }
    public function getCurrentJobId(): int { return 0; }
}

/**
 * Bridge that deliberately uses the same inherited Controller helpers as
 * app/controllers/Remotes.php::Change_Job__ntcs().
 *
 * Important: this class does NOT update JOB_lst.act.
 */
final class JobSwitchControllerBridge extends Controller
{
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

            usleep(120000);

            $seqOk = $this->op_write(464, $seqId);
            if (!$seqOk) {
                throw new RuntimeException('OP write seq_id failed');
            }

            return [
                'protocol' => 'OP',
                'device_id' => $deviceId,
                'job_id' => $jobId,
                'seq_id' => $seqId,
                'op_commands' => [
                    'IDAS_WRITE_463_' . $jobId,
                    'IDAS_WRITE_464_' . $seqId,
                ],
            ];
        }

        // NTCS/Modbus: send the two controller commands separately.
        // 463 = Change Job ID, 464 = Change Seq ID.
        // Keep a short interval so the Controller can consume the JOB command
        // before the fixed SEQ=1 command arrives.
        $jobOk = $this->protocol_write_register($deviceId, 463, $jobId);
        if (!$jobOk) {
            throw new RuntimeException('MODBUS write job_id failed');
        }

        usleep(120000);

        $seqOk = $this->protocol_write_register($deviceId, 464, $seqId);
        if (!$seqOk) {
            throw new RuntimeException('MODBUS write seq_id failed');
        }

        return [
            'protocol' => 'MODBUS',
            'device_id' => $deviceId,
            'job_id' => $jobId,
            'seq_id' => $seqId,
            'commands' => [
                'WRITE_463_' . $jobId,
                'WRITE_464_' . $seqId,
            ],
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

    public function switchJob(int $targetJobId): array
    {
        $startedAt = microtime(true);
        $seqId = 1;

        // Controller register 463 supports the job numbers defined by NTCS.
        if ($targetJobId <= 0) {
            throw new JobSwitchException(400, 3003, 'job_id 無效');
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
                'Controller JOB/SEQ 切換失敗：' . $e->getMessage()
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
