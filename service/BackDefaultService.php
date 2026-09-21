<?php

declare(strict_types=1);

final class BackDefaultException extends RuntimeException
{
    private $httpStatus;
    private $errorCode;

    public function __construct(int $httpStatus, int $errorCode, string $message)
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->errorCode = $errorCode;
    }

    public function getHttpStatus(): int { return $this->httpStatus; }
    public function getErrorCode(): int { return $this->errorCode; }
}

final class BackDefaultService
{
    private const TABLES = ['JOB_lst', 'SEQ_lst', 'STEP_lst', 'JOBInput_lst', 'JOBOutput_lst'];
    private const DELETE_ORDER = ['STEP_lst', 'SEQ_lst', 'JOBInput_lst', 'JOBOutput_lst', 'JOB_lst'];
    private const PRESERVED_JOB_IDS = [0, 221];

    private $controllerDatabasePath;
    private $idasMirrorPath;
    private $deviceDatabasePath;
    private $controllerFactory;

    public function __construct(array $options = [])
    {
        $databaseRoot = $options['database_root'] ?? IDAS_PATH_DATABASE_ROOT;
        $controllerRoot = $options['controller_root'] ?? IDAS_PATH_CONTROLLER_ROOT;

        $this->controllerDatabasePath =
            $options['controller_database_path'] ?? $controllerRoot . '/KLS_NTCS.Lin';
        $this->idasMirrorPath =
            $options['idas_mirror_path'] ?? $databaseRoot . '/KLS_NTCS_IDAS.Lin';
        $this->deviceDatabasePath =
            $options['device_database_path'] ?? $databaseRoot . '/ntcs_device_IDAS.db';
        $this->controllerFactory =
            $options['controller_factory'] ?? static function () { return new Controller(); };
    }

    public function reset(): array
    {
        $startedAt = microtime(true);
        $this->log('START');

        $this->preflight();

        // Destructive operation: check the login state again immediately before DB write.
        $this->assertControllerLoggedOut();

        $pdo = null;
        $before = [];
        $after = [];

        try {
            $this->log('DB_CONNECT');
            $pdo = idas_sqlite_connect(
                $this->controllerDatabasePath,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            $this->assertRequiredTables($pdo);
            $before = $this->readCounts($pdo);
            $this->log('COUNTS_BEFORE ' . json_encode($before, JSON_UNESCAPED_UNICODE));

            $this->log('TRANSACTION_BEGIN');
            if (!$pdo->beginTransaction()) {
                throw new RuntimeException('無法開始資料庫交易');
            }

            foreach (self::DELETE_ORDER as $table) {
                $this->log('DELETE ' . $table);
                try {
                    $pdo->exec(
                        'DELETE FROM "' . $table . '" WHERE JOBID NOT IN (' .
                        implode(',', self::PRESERVED_JOB_IDS) . ')'
                    );
                } catch (Throwable $e) {
                    throw new RuntimeException(
                        '清除 ' . $table . ' 失敗：' . $this->safeDatabaseMessage($e->getMessage()),
                        0,
                        $e
                    );
                }
            }

            $after = $this->readCounts($pdo);
            $this->log('COUNTS_AFTER ' . json_encode($after, JSON_UNESCAPED_UNICODE));

            $remaining = $this->readNonPreservedCounts($pdo);
            $this->log('NON_PRESERVED_AFTER ' . json_encode($remaining, JSON_UNESCAPED_UNICODE));

            $notEmpty = [];
            foreach ($remaining as $table => $count) {
                if ((int)$count !== 0) {
                    $notEmpty[] = $table . '=' . (int)$count;
                }
            }
            if ($notEmpty !== []) {
                throw new RuntimeException(
                    '清除後驗證失敗，仍有非保留 JOB 資料：' . implode(', ', $notEmpty)
                );
            }

            // System JOB 0 and 221, plus all of their related rows, are intentionally preserved.
            $preserved = $this->readPreservedCounts($pdo);
            $this->log('PRESERVED_AFTER ' . json_encode($preserved, JSON_UNESCAPED_UNICODE));

            // One final login check before commit. If login state changed, rollback.
            $this->assertControllerLoggedOut();

            $this->log('TRANSACTION_COMMIT');
            if (!$pdo->commit()) {
                throw new RuntimeException('資料庫交易提交失敗');
            }
            $pdo = null;
        } catch (BackDefaultException $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
                $this->log('ROLLBACK controller state/preflight failure');
            }
            throw $e;
        } catch (Throwable $e) {
            if ($pdo instanceof PDO && $pdo->inTransaction()) {
                try {
                    $pdo->rollBack();
                    $this->log('ROLLBACK database failure');
                } catch (Throwable $rollbackError) {
                    $this->log('ROLLBACK_FAILED ' . $rollbackError->getMessage());
                }
            }

            $this->log('DATABASE_ERROR ' . $e->getMessage());
            throw new BackDefaultException(
                500,
                2001,
                $this->safeResetFailureMessage($e->getMessage())
            );
        }

        /*
         * Controller DB reset is the authoritative operation.
         * Mirror sync happens only after a verified commit. Mirror failure must not
         * turn an already-completed controller reset into a false "reset failed".
         */
        $mirrorSynced = $this->syncIdasMirror();
        if (!$mirrorSynced) {
            $this->log('MIRROR_SYNC_FAILED controller reset already committed');
        } else {
            $this->log('MIRROR_SYNC_OK');
        }

        $elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);
        $this->log('SUCCESS elapsed_ms=' . $elapsedMs);

        return [
            'before' => $before,
            'after' => $after,
            'mirror_synced' => $mirrorSynced,
            'elapsed_ms' => $elapsedMs,
        ];
    }

    private function preflight(): void
    {
        $root = dirname($this->controllerDatabasePath);

        if (!is_dir($root)) {
            throw new BackDefaultException(503, 2001, '控制器資料庫目錄不存在');
        }
        if (!is_readable($root)) {
            throw new BackDefaultException(503, 2001, '控制器資料庫目錄無法讀取');
        }
        if (!is_file($this->controllerDatabasePath)) {
            throw new BackDefaultException(503, 2001, '控制器資料庫不存在');
        }
        if (!is_readable($this->controllerDatabasePath)) {
            throw new BackDefaultException(503, 2001, '控制器資料庫無法讀取');
        }
        if (!is_writable($this->controllerDatabasePath)) {
            throw new BackDefaultException(503, 2001, '控制器資料庫無法寫入');
        }

        $this->assertControllerLoggedOut();
    }

    private function assertControllerLoggedOut(): void
    {
        $unitId = $this->activeUnitId();

        try {
            $controller = call_user_func($this->controllerFactory);
            $status = $controller->idas_check($unitId);
        } catch (Throwable $e) {
            $this->log('LOGIN_CHECK_EXCEPTION unitId=' . $unitId . ' error=' . $e->getMessage());
            throw new BackDefaultException(502, 2001, '控制器登入狀態檢查失敗');
        }

        if (!is_array($status)) {
            $this->log('LOGIN_CHECK_INVALID_RESPONSE unitId=' . $unitId);
            throw new BackDefaultException(502, 2001, '控制器登入狀態檢查失敗');
        }

        $error = trim((string)($status['error'] ?? ''));
        $result = $status['result'] ?? null;

        if ($error !== '' || $result === null) {
            $this->log(
                'LOGIN_CHECK_UNAVAILABLE unitId=' . $unitId .
                ' result=' . var_export($result, true) .
                ' error=' . $error
            );
            throw new BackDefaultException(502, 2001, '控制器通訊異常，無法確認登入狀態');
        }

        $this->log('LOGIN_CHECK unitId=' . $unitId . ' result=' . (string)$result);

        if ((int)$result !== 0) {
            // Customer contract: Controller not logged out is reported as 2001.
            throw new BackDefaultException(409, 2001, '控制器尚未登出，無法執行重置');
        }
    }

    private function activeUnitId(): int
    {
        if (!is_file($this->deviceDatabasePath)) {
            $this->log('DEVICE_DB_MISSING fallback_unitId=1');
            return 1;
        }

        try {
            $pdo = idas_sqlite_connect(
                $this->deviceDatabasePath,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $columns = array_column(
                $pdo->query('PRAGMA table_info(ntcs_device_test)')->fetchAll(PDO::FETCH_ASSOC),
                'name'
            );

            foreach (['device_id', 'control_id', 'modbus_id'] as $column) {
                if (!in_array($column, $columns, true)) {
                    continue;
                }

                $value = (int)$pdo
                    ->query('SELECT "' . $column . '" FROM ntcs_device_test LIMIT 1')
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

    private function assertRequiredTables(PDO $pdo): void
    {
        $stmt = $pdo->prepare(
            "SELECT 1 FROM sqlite_master WHERE type='table' AND name=:name LIMIT 1"
        );

        foreach (self::TABLES as $table) {
            $stmt->execute([':name' => $table]);
            if (!$stmt->fetchColumn()) {
                throw new RuntimeException('必要資料表不存在：' . $table);
            }
        }
    }

    private function readCounts(PDO $pdo): array
    {
        $counts = [];
        foreach (self::TABLES as $table) {
            $counts[$table] = (int)$pdo
                ->query('SELECT COUNT(*) FROM "' . $table . '"')
                ->fetchColumn();
        }
        return $counts;
    }

    private function readNonPreservedCounts(PDO $pdo): array
    {
        $counts = [];
        $ids = implode(',', self::PRESERVED_JOB_IDS);

        foreach (self::TABLES as $table) {
            $counts[$table] = (int)$pdo
                ->query(
                    'SELECT COUNT(*) FROM "' . $table .
                    '" WHERE JOBID NOT IN (' . $ids . ')'
                )
                ->fetchColumn();
        }

        return $counts;
    }

    private function readPreservedCounts(PDO $pdo): array
    {
        $counts = [];
        $ids = implode(',', self::PRESERVED_JOB_IDS);

        foreach (self::TABLES as $table) {
            $counts[$table] = (int)$pdo
                ->query(
                    'SELECT COUNT(*) FROM "' . $table .
                    '" WHERE JOBID IN (' . $ids . ')'
                )
                ->fetchColumn();
        }

        return $counts;
    }

    private function syncIdasMirror(): bool
    {
        $directory = dirname($this->idasMirrorPath);

        if (!is_dir($directory) || !is_writable($directory)) {
            $this->log('MIRROR_DIRECTORY_NOT_WRITABLE');
            return false;
        }

        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (Throwable $e) {
            $suffix = uniqid('', true);
        }

        $temporary =
            $this->idasMirrorPath . '.back-default-stage.' . getmypid() . '.' . $suffix;

        @unlink($temporary);

        try {
            $pdo = idas_sqlite_connect(
                $this->controllerDatabasePath,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $pdo->exec('VACUUM INTO ' . $pdo->quote($temporary));
            $pdo = null;

            if (!is_file($temporary) || filesize($temporary) <= 0) {
                $this->log('MIRROR_STAGE_NOT_CREATED');
                return false;
            }

            $mode = is_file($this->idasMirrorPath)
                ? @fileperms($this->idasMirrorPath)
                : false;

            if (!@rename($temporary, $this->idasMirrorPath)) {
                $this->log('MIRROR_RENAME_FAILED');
                return false;
            }

            if ($mode !== false) {
                @chmod($this->idasMirrorPath, $mode & 0777);
            }

            return true;
        } catch (Throwable $e) {
            $this->log('MIRROR_SYNC_EXCEPTION ' . $e->getMessage());
            return false;
        } finally {
            @unlink($temporary);
        }
    }

    private function safeResetFailureMessage(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return '控制器資料重置失敗';
        }

        // Do not expose filesystem paths to MES clients.
        $message = str_replace(
            [$this->controllerDatabasePath, $this->idasMirrorPath, $this->deviceDatabasePath],
            ['控制器資料庫', 'iDAS 鏡像資料庫', '裝置資料庫'],
            $message
        );

        return $message;
    }

    private function safeDatabaseMessage(string $message): string
    {
        $message = $this->safeResetFailureMessage($message);
        // Keep useful SQLite reason but avoid an unbounded internal error response.
        if (function_exists('mb_substr')) {
            return mb_substr($message, 0, 240, 'UTF-8');
        }
        return substr($message, 0, 240);
    }

    private function log(string $message): void
    {
        error_log('[back-default] ' . $message);
    }
}
