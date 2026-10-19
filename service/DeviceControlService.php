<?php

declare(strict_types=1);

final class DeviceControlException extends RuntimeException
{
    private $httpStatus;
    private $apiCode;
    private $deviceStatus;

    public function __construct(
        int $httpStatus,
        int $apiCode,
        string $message,
        int $deviceStatus
    ) {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->apiCode = $apiCode;
        $this->deviceStatus = $deviceStatus;
    }

    public function getHttpStatus(): int { return $this->httpStatus; }
    public function getApiCode(): int { return $this->apiCode; }
    public function getDeviceStatus(): int { return $this->deviceStatus; }
}

final class DeviceControlService
{
    private const ENABLE_REGISTER = 461;

    private $deviceDatabasePath;
    private $controllerFactory;

    public function __construct(array $options = [])
    {
        $databaseRoot = $options['database_root'] ?? IDAS_PATH_DATABASE_ROOT;
        $this->deviceDatabasePath =
            $options['device_database_path'] ?? $databaseRoot . '/ntcs_device_IDAS.db';
        $this->controllerFactory =
            $options['controller_factory'] ?? static function () { return new Controller(); };
    }

    public function disable(): array
    {
        return $this->writeDeviceStatus(0, 3101);
    }

    public function enable(): array
    {
        return $this->writeDeviceStatus(1, 3201);
    }

    private function writeDeviceStatus(int $status, int $communicationErrorCode): array
    {
        $startedAt = microtime(true);
        $unitId = $this->activeUnitId();
        $controller = call_user_func($this->controllerFactory);

        $this->log(
            'WRITE register=' . self::ENABLE_REGISTER .
            ' value=' . $status .
            ' unit_id=' . $unitId
        );

        try {
            $ok = $controller->protocol_write_registers(
                $unitId,
                self::ENABLE_REGISTER,
                [$status]
            );
        } catch (Throwable $e) {
            $this->log('WRITE_EXCEPTION ' . $e->getMessage());
            throw new DeviceControlException(
                502,
                $communicationErrorCode,
                $status === 1 ? '控制器通訊逾時或寫入失敗' : '電批通訊異常',
                $status === 1 ? 0 : 1
            );
        }

        if (!$ok) {
            throw new DeviceControlException(
                502,
                $communicationErrorCode,
                $status === 1 ? '控制器通訊逾時或寫入失敗' : '電批通訊異常',
                $status === 1 ? 0 : 1
            );
        }

        $elapsedMs = (int)round((microtime(true) - $startedAt) * 1000);

        $this->log(
            'SUCCESS device_status=' . $status .
            ' elapsed_ms=' . $elapsedMs
        );

        return [
            'device_status' => $status,
            'elapsed_ms' => $elapsedMs,
        ];
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

    private function log(string $message): void
    {
        error_log('[device-control] ' . $message);
    }
}
