<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';
require_once dirname(__DIR__) . '/app/libraries/Controller.php';
require_once dirname(__DIR__) . '/service/DeviceControlService.php';
require_once __DIR__ . '/common.php';

device_api_json_body('enable_flag', 3202);

try {
    $service = new DeviceControlService();
    $result = $service->enable();

    device_api_response(
        200,
        0,
        'success',
        (int)$result['device_status'],
        '電批已啟用，可以執行鎖附'
    );
} catch (DeviceControlException $e) {
    error_log(
        '[device-enable] API_ERROR code=' . $e->getApiCode() .
        ' message=' . $e->getMessage()
    );

    device_api_response(
        $e->getHttpStatus(),
        $e->getApiCode(),
        '啟用電批失敗',
        $e->getDeviceStatus(),
        $e->getMessage()
    );
} catch (Throwable $e) {
    error_log('[device-enable] UNEXPECTED ' . $e->getMessage());

    device_api_response(
        500, 3201, '啟用電批失敗', 0, '控制器通訊逾時'
    );
}
