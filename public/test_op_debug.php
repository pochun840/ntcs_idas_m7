<?php
// 放到 /var/www/html/idas/public/test_op_debug.php
// 一般測試： http://<iDAS_IP>/idas/public/test_op_debug.php
// 效能測試： http://<iDAS_IP>/idas/public/test_op_debug.php?bench=1&qty=64
require_once '../app/libraries/Controller.php';

$c = new Controller();

header('Content-Type: application/json; charset=utf-8');

function ms(callable $fn) {
    $t0 = microtime(true);
    $ret = $fn();
    return [
        'ms' => round((microtime(true) - $t0) * 1000, 2),
        'result' => $ret,
    ];
}

$readRawTimed = ms(function() use ($c) {
    return $c->op_send('IDAS_READ_4097');
});

$readValueTimed = ms(function() use ($c) {
    return $c->op_read(4097);
});

$writeRawTimed = ms(function() use ($c) {
    return $c->op_send('IDAS_WRITE_463_2');
});

$writeOkTimed = ms(function() use ($c) {
    return $c->op_write(463, 2);
});

$result = [
    'modbus_type' => $c->get_modbus_type_from_controller(),
    'is_op' => $c->is_op_protocol_enabled(),
    'read_4097_raw_ms' => $readRawTimed['ms'],
    'read_4097_raw' => $readRawTimed['result'],
    'read_4097_value_ms' => $readValueTimed['ms'],
    'read_4097_value' => $readValueTimed['result'],
    'write_463_2_raw_ms' => $writeRawTimed['ms'],
    'write_463_2_raw' => $writeRawTimed['result'],
    'write_463_2_ok_ms' => $writeOkTimed['ms'],
    'write_463_2_ok' => $writeOkTimed['result'],
];

if (isset($_GET['bench'])) {
    $qty = isset($_GET['qty']) ? (int)$_GET['qty'] : 16;
    if ($qty < 1) $qty = 1;
    if ($qty > 128) $qty = 128;

    $benchRead = ms(function() use ($c, $qty) {
        return $c->protocol_read_registers(1, 4097, $qty);
    });

    $values = is_array($benchRead['result']) ? $benchRead['result'] : [];
    $result['bench'] = [
        'read_start' => 4097,
        'read_qty' => $qty,
        'read_ms' => $benchRead['ms'],
        'avg_ms_per_register' => $qty > 0 ? round($benchRead['ms'] / $qty, 2) : null,
        'first_values' => array_slice($values, 0, min(10, count($values))),
        'note' => 'OP still sends one command at a time; this benchmark keeps one TCP connection and reads sequentially.',
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
