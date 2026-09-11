<?php

declare(strict_types=1);

function remote_probe_sanitize_public_data($value)
{
    if (!is_array($value)) return $value;
    foreach (['controller_database', 'controller_root', 'backup'] as $key) unset($value[$key]);
    foreach ($value as $key => $item) {
        if (is_array($item)) $value[$key] = remote_probe_sanitize_public_data($item);
    }
    return $value;
}

function remote_probe_response(int $status, array $body): void
{
    $body = remote_probe_sanitize_public_data($body);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    remote_probe_response(405, ['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'GET is required']]);
}

try {
    require_once dirname(__DIR__) . '/app/config/config.php';
    require_once dirname(__DIR__) . '/app/libraries/Controller.php';
    require_once dirname(__DIR__) . '/service/JobConfigReplaceService.php';
    require_once dirname(__DIR__) . '/service/RemoteJobConfigService.php';

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/remote_controller_probe.php'));
    $idasBaseUrl = preg_replace('#/api/[^/]+$#', '', $scriptName);
    if (!is_string($idasBaseUrl) || $idasBaseUrl === '' || $idasBaseUrl === $scriptName) $idasBaseUrl = '/idas';

    $networkService = new RemoteJobConfigService(
        $idasBaseUrl,
        static function () { return []; },
        static function () { return []; }
    );
    $remoteAddress = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (!$networkService->isRemoteAddressOnLocalSubnet($remoteAddress)) {
        remote_probe_response(403, ['success' => false, 'error' => ['code' => 'REMOTE_NOT_SAME_SUBNET', 'message' => 'Probe requests are only accepted from the same subnet']]);
    }

    $controllerRoot = IDAS_PATH_CONTROLLER_ROOT;
    $controllerDb = $controllerRoot . '/KLS_NTCS.Lin';
    $data = [
        'controller_root_exists' => is_dir($controllerRoot) && is_readable($controllerRoot),
        'controller_database_exists' => is_file($controllerDb),
        'controller_database_readable' => is_readable($controllerDb),
        'controller_database_writable' => is_writable($controllerDb),
        'controller_logged_out' => null,
        'ready' => false,
        'code' => null,
        'message' => null,
    ];

    if (!$data['controller_root_exists']) {
        $data['code'] = 'CONTROLLER_ROOT_NOT_FOUND';
        $data['message'] = 'Controller root was not found or is not readable';
    } elseif (!$data['controller_database_exists'] || !$data['controller_database_readable'] || !$data['controller_database_writable']) {
        $data['code'] = 'CONTROLLER_DATABASE_NOT_WRITABLE';
        $data['message'] = 'KLS_NTCS.Lin was not found or is not writable';
    } else {
        try {
            $service = new JobConfigReplaceService();
            $preflight = $service->preflight();
            $data = array_merge($data, $preflight);
            $data['ready'] = true;
            $data['code'] = 'READY';
            $data['message'] = 'Ready';
        } catch (JobConfigApiException $e) {
            $data['controller_logged_out'] = $e->getErrorCode() === 'CONTROLLER_IN_USE' ? false : null;
            $data['code'] = $e->getErrorCode();
            $data['message'] = $e->getMessage();
        }
    }

    remote_probe_response(200, ['success' => !empty($data['ready']), 'data' => $data]);
} catch (Throwable $e) {
    error_log('[remote_controller_probe] ' . $e->getMessage());
    remote_probe_response(500, ['success' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Probe failed']]);
}
