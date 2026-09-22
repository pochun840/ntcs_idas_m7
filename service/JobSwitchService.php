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
        $this->controllerDatabasePath =
            $options['controller_database_path'] ?? $controllerRoot . '/KLS_NTCS.Lin';
        // Modbus Unit ID 與華榮 station_code 使用同一個 Controller device_id 來源。
        // Controller 真實設備 DB：/home/kls/NTCS7/ntcs_device.db
        $this->deviceDatabasePath =
            $options['device_database_path'] ?? $controllerRoot . '/ntcs_device.db';
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

        // DB 切換完成後，通知 Controller 套用實際 JOB / SEQ。
        // 463 (0x01CF) = 切換工作編號：寫入 MES 傳入的 job_id。
        // 464 (0x01D0) = 切換工序編號：固定從 SEQ 1 開始。
        // 4305 / 4306 為 Controller 實際 JOB / SEQ 狀態，用於寫後驗證。
        $unitId = $this->activeUnitId();
        $this->applyControllerJobSequence($controller, $unitId, $targetJobId, 1, $finalCurrentJobId);

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


    private function applyControllerJobSequence(
        $controller,
        int $unitId,
        int $jobId,
        int $seqId,
        int $currentJobId
    ): void {
        try {
            // 分開寫 463 / 464，OP 與 Modbus 都使用同一組 register 定義。
            if (!$controller->protocol_write_register($unitId, 463, $jobId)) {
                throw new RuntimeException('Register 463 (Job ID) 寫入失敗');
            }

            // Controller 一次處理一筆命令，保留短暫處理時間後再切 SEQ。
            usleep(120000);

            if (!$controller->protocol_write_register($unitId, 464, $seqId)) {
                throw new RuntimeException('Register 464 (Seq ID) 寫入失敗');
            }

            // 命令 register 不以 463/464 讀回驗證；改讀實際狀態 4305/4306。
            if (!$controller->protocol_wait_for_register_values(
                $unitId,
                4305,
                [$jobId, $seqId],
                5,
                200000
            )) {
                throw new RuntimeException('Controller JOB/SEQ 寫後驗證失敗');
            }

            $this->log(
                'MODBUS_SWITCH_OK unit_id=' . $unitId .
                ' reg463_job_id=' . $jobId .
                ' reg464_seq_id=' . $seqId .
                ' verify_4305_4306=OK'
            );
        } catch (Throwable $e) {
            $this->log(
                'MODBUS_SWITCH_FAILED unit_id=' . $unitId .
                ' target_job_id=' . $jobId .
                ' target_seq_id=' . $seqId .
                ' error=' . $e->getMessage()
            );

            throw new JobSwitchException(
                502,
                3001,
                'Job DB 已切換，但 Controller JOB/SEQ 指令執行失敗：' . $e->getMessage(),
                $currentJobId
            );
        }
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
        // 不再從 iDAS mirror DB (ntcs_device_IDAS.db) 或 control_id/modbus_id 猜值。
        // Modbus Unit ID 固定使用 Controller /home/kls/NTCS7/ntcs_device.db
        // 的 ntcs_device_test.device_id，與接口 1 station_code 完全一致。
        if (!is_file($this->deviceDatabasePath) || !is_readable($this->deviceDatabasePath)) {
            $this->log('DEVICE_DB_NOT_READABLE path=' . $this->deviceDatabasePath);
            throw new JobSwitchException(503, 3001, '無法讀取 Controller ntcs_device.db');
        }

        try {
            $pdo = idas_sqlite_connect(
                $this->deviceDatabasePath,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $stmt = $pdo->query(
                'SELECT device_id FROM ntcs_device_test ' .
                'WHERE device_id IS NOT NULL ' .
                'AND TRIM(CAST(device_id AS TEXT)) <> "" LIMIT 1'
            );
            $rawUnitId = $stmt->fetchColumn();

            if ($rawUnitId === false || !is_numeric($rawUnitId)) {
                throw new RuntimeException('ntcs_device_test 找不到有效的 device_id');
            }

            $unitId = (int)$rawUnitId;
            if ($unitId < 1 || $unitId > 255) {
                throw new RuntimeException('device_id 超出 Modbus Unit ID 範圍 1~255');
            }

            return $unitId;
        } catch (JobSwitchException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->log('DEVICE_ID_READ_FAILED path=' . $this->deviceDatabasePath . ' error=' . $e->getMessage());
            throw new JobSwitchException(503, 3001, '讀取 Controller device_id 失敗：' . $e->getMessage());
        }
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
