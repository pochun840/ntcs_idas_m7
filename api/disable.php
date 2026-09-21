<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/libraries/Controller.php';
require_once dirname(__DIR__) . '/service/DeviceControlService.php';
require_once __DIR__ . '/common.php';

device_api_json_body('disable_flag', 3102);

try {
    $service = new DeviceControlService();
    $result = $service->disable();

    device_api_response(
        200,
        0,
        'success',
        (int)$result['device_status'],
        '電批已禁用，無法啟動鎖附'
    );
} catch (DeviceControlException $e) {
    error_log(
        '[device-disable] API_ERROR code=' . $e->getApiCode() .
        ' message=' . $e->getMessage()
    );

    device_api_response(
        $e->getHttpStatus(),
        $e->getApiCode(),
        '禁用電批失敗',
        $e->getDeviceStatus(),
        $e->getMessage()
    );
} catch (Throwable $e) {
    error_log('[device-disable] UNEXPECTED ' . $e->getMessage());

    device_api_response(
        500, 3101, '禁用電批失敗', 1, '電批通訊異常'
    );
}
