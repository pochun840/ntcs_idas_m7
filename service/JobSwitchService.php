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

final class JobSwitchService
{
    private $controllerDatabasePath;
    private $deviceDatabasePath;
    private $controllerFactory;

    public function __construct(array $options = [])
    {
        $controllerRoot = $options['controller_root'] ?? IDAS_PATH_CONTROLLER_ROOT;
        $databaseRoot = $options['database_root'] ?? IDAS_PATH_DATABASE_ROOT;

        $this->controllerDatabasePath =
            $options['controller_database_path'] ?? $controllerRoot . '/KLS_NTCS.Lin';
        $this->deviceDatabasePath =
            $options['device_database_path'] ?? $databaseRoot . '/ntcs_device_IDAS.db';
        $this->controllerFactory =
            $options['controller_factory'] ?? static function () { return new Controller(); };
    }

    public function switchJob(int $targetJobId): array
    {
        $startedAt = microtime(true);

        if ($targetJobId < 0 || $targetJobId > 9999) {
            throw new JobSwitchException(400, 3003, 'job_id 超出有效範圍');
        }

        if (!is_file($this->controllerDatabasePath) ||
            !is_readable($this->controllerDatabasePath) ||
            !is_writable($this->controllerDatabasePath)) {
            throw new JobSwitchException(503, 3001, '控制器資料庫無法讀寫');
        }

        $pdo = idas_sqlite_connect(
            $this->controllerDatabasePath,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $currentJobId = $this->currentActiveJobId($pdo);

        if (!$this->jobExists($pdo, $targetJobId)) {
            throw new JobSwitchException(404, 3001, '目標 Job 不存在', $currentJobId);
        }

        // DB configuration changes are allowed only when Controller is logged out.
        $controller = call_user_func($this->controllerFactory);
        $this->assertControllerLoggedOut(
            $controller,
            $this->activeUnitId(),
            $currentJobId
        );

        try {
            $pdo->exec('BEGIN IMMEDIATE');

            // Exactly one JOB must be active after a successful switch.
            $pdo->exec('UPDATE JOB_lst SET act = 0');

            $stmt = $pdo->prepare(
                'UPDATE JOB_lst SET act = 1 WHERE JOBID = :job_id'
            );
            $stmt->execute([':job_id' => $targetJobId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException('目標 Job 更新失敗');
            }

            $verification = $this->verifyActiveJob($pdo, $targetJobId);
            if (!$verification['ok']) {
                throw new RuntimeException(
                    'Job 切換驗證失敗：active_count=' .
                    $verification['active_count'] .
                    ', target_act=' . $verification['target_act']
                );
            }

            // Recheck login state immediately before commit.
            $this->assertControllerLoggedOut(
                $controller,
                $this->activeUnitId(),
                $currentJobId
            );

            $pdo->exec('COMMIT');
        } catch (JobSwitchException $e) {
            $this->rollbackQuietly($pdo);
            throw $e;
        } catch (Throwable $e) {
            $this->rollbackQuietly($pdo);
            $this->log('DB_SWITCH_FAILED ' . $e->getMessage());

            throw new JobSwitchException(
                500,
                3001,
                '切換 Job 資料失敗',
                $currentJobId
            );
        }

        // Verify again after commit from the persisted database state.
        $finalCurrentJobId = $this->currentActiveJobId($pdo);
        $finalVerification = $this->verifyActiveJob($pdo, $targetJobId);

        if (!$finalVerification['ok'] || $finalCurrentJobId !== $targetJobId) {
            throw new JobSwitchException(
                500,
                3001,
                'Job 切換後驗證失敗',
                $finalCurrentJobId
            );
        }

        $elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);

        $this->log(
            'SUCCESS target_job_id=' . $targetJobId .
            ' previous_job_id=' . $currentJobId .
            ' current_job_id=' . $finalCurrentJobId .
            ' elapsed_ms=' . $elapsedMs
        );

        return [
            'target_job_id' => $targetJobId,
            'current_job_id' => $finalCurrentJobId,
            'previous_job_id' => $currentJobId,
            'elapsed_ms' => $elapsedMs,
        ];
    }

    private function jobExists(PDO $pdo, int $jobId): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM JOB_lst WHERE JOBID = :job_id LIMIT 1'
        );
        $stmt->execute([':job_id' => $jobId]);

        return (bool)$stmt->fetchColumn();
    }

    private function currentActiveJobId(PDO $pdo): int
    {
        $stmt = $pdo->query(
            'SELECT JOBID FROM JOB_lst WHERE act = 1 ORDER BY JOBID ASC LIMIT 1'
        );
        $value = $stmt->fetchColumn();

        return $value === false ? 0 : (int)$value;
    }

    private function verifyActiveJob(PDO $pdo, int $targetJobId): array
    {
        $activeCount = (int)$pdo
            ->query('SELECT COUNT(*) FROM JOB_lst WHERE act = 1')
            ->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT act FROM JOB_lst WHERE JOBID = :job_id LIMIT 1'
        );
        $stmt->execute([':job_id' => $targetJobId]);
        $targetAct = $stmt->fetchColumn();

        return [
            'ok' => $activeCount === 1 && (int)$targetAct === 1,
            'active_count' => $activeCount,
            'target_act' => $targetAct === false ? -1 : (int)$targetAct,
        ];
    }

    private function assertControllerLoggedOut(
        $controller,
        int $unitId,
        int $currentJobId
    ): void {
        try {
            $status = $controller->idas_check($unitId);
        } catch (Throwable $e) {
            $this->log('LOGIN_CHECK_EXCEPTION ' . $e->getMessage());

            throw new JobSwitchException(
                502,
                3001,
                '控制器登入狀態檢查失敗',
                $currentJobId
            );
        }

        if (!is_array($status)) {
            throw new JobSwitchException(
                502,
                3001,
                '控制器登入狀態檢查失敗',
                $currentJobId
            );
        }

        $error = trim((string)($status['error'] ?? ''));
        $result = $status['result'] ?? null;

        if ($error !== '' || $result === null) {
            throw new JobSwitchException(
                502,
                3001,
                '控制器通訊異常，無法確認登入狀態',
                $currentJobId
            );
        }

        if ((int)$result !== 0) {
            throw new JobSwitchException(
                409,
                3001,
                '控制器尚未登出，無法切換 Job',
                $currentJobId
            );
        }
    }

    private function activeUnitId(): int
    {
        if (!is_file($this->deviceDatabasePath)) {
            return 1;
        }

        try {
            $pdo = idas_sqlite_connect(
                $this->deviceDatabasePath,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $columns = array_column(
                $pdo->query(
                    'PRAGMA table_info(ntcs_device_test)'
                )->fetchAll(PDO::FETCH_ASSOC),
                'name'
            );

            foreach (['device_id', 'control_id', 'modbus_id'] as $column) {
                if (!in_array($column, $columns, true)) {
                    continue;
                }

                $value = (int)$pdo
                    ->query(
                        'SELECT "' . $column .
                        '" FROM ntcs_device_test LIMIT 1'
                    )
                    ->fetchColumn();

                if ($value >= 1 && $value <= 255) {
                    return $value;
                }
            }
        } catch (Throwable $e) {
            $this->log('DEVICE_ID_READ_FAILED ' . $e->getMessage());
        }

        return 1;
    }

    private function rollbackQuietly(PDO $pdo): void
    {
        try {
            $pdo->exec('ROLLBACK');
        } catch (Throwable $ignored) {
        }
    }

    private function log(string $message): void
    {
        error_log('[job-switch] ' . $message);
    }
}
