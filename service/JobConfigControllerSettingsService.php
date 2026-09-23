<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

/** Read Modbus parameters from this controller's ntcs_device.db. */
final class JobConfigControllerSettingsService
{
    public static function read(): array
    {
        $path = idas_path('controller_root', 'ntcs_device.db');
        if (!is_file($path) || !is_readable($path)) throw new RuntimeException('Controller settings database is unavailable');
        $db = idas_sqlite_connect($path);
        $columns = array_column($db->query('PRAGMA table_info(ntcs_device_test)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('wifi', $columns, true)) throw new RuntimeException('Controller Modbus port setting is missing');
        $fields = array_values(array_intersect(['wifi', 'device_id', 'control_id', 'modbus_id', 'modbus_type'], $columns));
        $row = $db->query('SELECT ' . implode(', ', array_map(static function ($field) { return '"' . $field . '"'; }, $fields)) . ' FROM ntcs_device_test LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) throw new RuntimeException('Controller settings row is unavailable');
        $parts = explode('_', (string)($row['wifi'] ?? ''));
        if (count($parts) < 3 || !ctype_digit(trim($parts[2])) || (int)$parts[2] < 1 || (int)$parts[2] > 65535) {
            throw new RuntimeException('Controller Modbus TCP port is invalid');
        }
        $unitId = 1;
        foreach (['device_id', 'control_id', 'modbus_id'] as $column) {
            if (isset($row[$column]) && (int)$row[$column] >= 1 && (int)$row[$column] <= 255) {
                $unitId = (int)$row[$column];
                break;
            }
        }
        return ['unit_id' => $unitId, 'port' => (int)$parts[2], 'modbus_type' => (int)($row['modbus_type'] ?? 0)];
    }
}
