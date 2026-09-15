<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/service/JobConfigErrorCodes.php';
require_once dirname(__DIR__) . '/service/JobConfigApiConfig.php';

$GLOBALS['JOB_CONFIG_REQUEST_STARTED_AT'] = microtime(true);


/**
 * Return the controller IP configured under Settings -> Connection.
 * API callers may omit targets; the configured agent_server_ip then becomes
 * the single deployment target. An explicitly supplied targets list always
 * takes precedence.
 */
function job_config_default_target_ip(): ?string
{
    if (!defined('IDAS_PATH_DATABASE_ROOT') || !function_exists('idas_sqlite_connect')) return null;

    $database = rtrim((string)IDAS_PATH_DATABASE_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'das.db';
    if (!is_file($database) || !is_readable($database)) return null;

    try {
        $pdo = idas_sqlite_connect($database);
        $statement = $pdo->prepare("SELECT config_value FROM config WHERE config_name = 'agent_server_ip' LIMIT 1");
        if (!$statement->execute()) return null;
        $value = trim((string)$statement->fetchColumn());
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false ? $value : null;
    } catch (Throwable $e) {
        error_log('[replace_job_config] Unable to read default controller IP: ' . $e->getMessage());
        return null;
    }
}


function job_config_max_request_bytes(): int
{
    return JobConfigReplaceService::maxBodyBytes() + 65536;
}

function job_config_sanitize_public_data($value)
{
    if (!is_array($value)) return $value;
    foreach (['controller_database', 'controller_root', 'backup'] as $key) unset($value[$key]);
    foreach ($value as $key => $item) if (is_array($item)) $value[$key] = job_config_sanitize_public_data($item);
    return $value;
}

function job_config_public_checks(array $checks): array
{
    $allowed = ['ip', 'same_subnet', 'network', 'reachable', 'controller_root_exists', 'controller_logged_out', 'ready', 'code', 'message'];
    $out = [];
    foreach ($checks as $check) {
        if (!is_array($check)) continue;
        $item = [];
        foreach ($allowed as $key) if (array_key_exists($key, $check)) $item[$key] = $check[$key];
        $out[] = $item;
    }
    return $out;
}

function job_config_public_results(array $results): array
{
    $allowed = ['ip', 'success', 'skipped', 'code', 'message', 'preflight_code', 'http_status', 'data'];
    $out = [];
    foreach ($results as $result) {
        if (!is_array($result)) continue;
        $item = [];
        foreach ($allowed as $key) if (array_key_exists($key, $result)) $item[$key] = $result[$key];
        if (isset($item['data']) && is_array($item['data'])) {
            $item['data'] = job_config_sanitize_public_data($item['data']);
            // A successful write already exposes inserted/updated under counts.
            // Do not repeat the same values under data.preview.counts.
            if (isset($item['data']['counts'])) unset($item['data']['preview']);
            if (isset($item['data']['warnings']) && $item['data']['warnings'] === []) unset($item['data']['warnings']);
        }
        $out[] = $item;
    }
    return $out;
}

function job_config_response(int $status, array $body): void
{
    if (!array_key_exists('api_version', $body)) $body['api_version'] = JobConfigApiConfig::VERSION;
    if (!array_key_exists('elapsed_ms', $body)) {
        $startedAt = isset($GLOBALS['JOB_CONFIG_REQUEST_STARTED_AT']) ? (float)$GLOBALS['JOB_CONFIG_REQUEST_STARTED_AT'] : microtime(true);
        $body['elapsed_ms'] = max(0, (int)round((microtime(true) - $startedAt) * 1000));
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-iDAS-API-Version: ' . JobConfigApiConfig::VERSION);
    if (class_exists('JobConfigReplaceService')) header('X-iDAS-Max-Request-Bytes: ' . job_config_max_request_bytes());
    $publicBody = job_config_sanitize_public_data($body);
    http_response_code($status);
    echo json_encode($publicBody, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function job_config_operation_response(int $status, string $action, bool $success, array $data): void
{
    $body = [
        'success' => $success,
        'action' => $action,
        'summary' => isset($data['summary']) && is_array($data['summary']) ? $data['summary'] : [],
        'checks' => isset($data['checks']) && is_array($data['checks']) ? job_config_public_checks($data['checks']) : [],
    ];
    if ($action !== 'check') {
        $body['results'] = isset($data['results']) && is_array($data['results']) ? job_config_public_results($data['results']) : [];
    }
    if (isset($data['elapsed_ms'])) $body['elapsed_ms'] = max(0, (int)$data['elapsed_ms']);
    job_config_response($status, $body);
}

try {
    require_once dirname(__DIR__) . '/app/config/config.php';
    require_once dirname(__DIR__) . '/app/libraries/Controller.php';
    require_once dirname(__DIR__) . '/service/JobConfigReplaceService.php';
    require_once dirname(__DIR__) . '/service/RemoteJobConfigService.php';

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/replace_job_config.php'));
    $idasBaseUrl = preg_replace('#/api/[^/]+$#', '', $scriptName);
    if (!is_string($idasBaseUrl) || $idasBaseUrl === '' || $idasBaseUrl === $scriptName) $idasBaseUrl = '/idas';

    $localService = new JobConfigReplaceService();
    $remoteService = new RemoteJobConfigService(
        $idasBaseUrl,
        static function () use ($localService) { return $localService->preflight(); },
        static function (array $payload) use ($localService) { return $localService->replace($payload); },
        null,
        static function (array $payload) use ($localService) { return $localService->preview($payload); }
    );

    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if ($method === 'GET') {
        $action = strtolower(trim((string)($_GET['action'] ?? '')));
        if ($action === 'network') job_config_response(200, ['success' => true, 'data' => $remoteService->networkInfo()]);
        require __DIR__ . '/replace_job_config_web.php';
        exit;
    }
    if ($method !== 'POST') {
        header('Allow: GET, POST');
        job_config_response(405, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::METHOD_NOT_ALLOWED,'message'=>'GET or POST is required']]);
    }

    $override = $GLOBALS['JOB_CONFIG_REQUEST_OVERRIDE'] ?? null;
    if (is_array($override)) {
        $request = $override;
    } else {
        $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
        if ($contentType !== 'application/json') job_config_response(415, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::UNSUPPORTED_MEDIA_TYPE,'message'=>'Content-Type must be application/json']]);

        $maxRequestBytes = job_config_max_request_bytes();
        $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
        if ($contentLength > $maxRequestBytes) job_config_response(413, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::PAYLOAD_TOO_LARGE,'message'=>'Request is too large','max_bytes'=>$maxRequestBytes]]);
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') job_config_response(400, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::EMPTY_BODY,'message'=>'JSON body is required']]);
        if (strlen($raw) > $maxRequestBytes) job_config_response(413, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::PAYLOAD_TOO_LARGE,'message'=>'Request is too large','max_bytes'=>$maxRequestBytes]]);
        $request = json_decode($raw, true);
        if (!is_array($request) || json_last_error() !== JSON_ERROR_NONE) job_config_response(400, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::INVALID_JSON,'message'=>json_last_error_msg()]]);
    }

    if (isset($request['api_version'])) {
        $requestedVersion = trim((string)$request['api_version']);
        if ($requestedVersion !== JobConfigApiConfig::VERSION) {
            job_config_response(400, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::UNSUPPORTED_API_VERSION,'message'=>'api_version must be ' . JobConfigApiConfig::VERSION]]);
        }
    }

    /*
     * Unified public API (canonical schema for external clients and the iDAS UI):
     * {
     *   "api_version": "<current API version>",
     *   "action": "check|preview|write",   // optional, defaults to write
     *   "targets": ["192.168.100.150"], // optional; defaults to Settings -> Connection IP
     *   "JOB_lst": [...],
     *   "SEQ_lst": [...],
     *   "STEP_lst": [...]
     * }
     *
     * Omit targets for the controller configured in iDAS. Send targets only
     * when a client needs to explicitly select one or more controllers.
     */
    $action = strtolower(trim((string)($request['action'] ?? 'write')));
    $hasTargets = array_key_exists('targets', $request);
    $hasConfig = array_key_exists('JOB_lst', $request) || array_key_exists('SEQ_lst', $request) || array_key_exists('STEP_lst', $request);

    if (!$hasTargets) {
        $defaultTarget = job_config_default_target_ip();
        if ($defaultTarget === null) {
            job_config_response(409, [
                'success'=>false,
                'action'=>$action,
                'error'=>[
                    'code'=>JobConfigErrorCodes::DEFAULT_TARGET_UNAVAILABLE,
                    'message'=>'targets was omitted and no valid controller IP is configured in iDAS Settings -> Connection.',
                ],
            ]);
        }
        $request['targets'] = [$defaultTarget];
    }
    $targets = $remoteService->normalizeTargets($request['targets']);

    if ($action === 'check') {
        $checks = $remoteService->checkTargets($targets);
        $readyCount = 0;
        foreach ($checks as $check) if (!empty($check['ready'])) $readyCount++;
        $allReady = $readyCount === count($targets);
        $data = [
            'all_ready' => $allReady,
            'summary' => [
                'total' => count($targets),
                'ready' => $readyCount,
                'success' => $readyCount,
                'failed' => 0,
                'skipped' => count($targets) - $readyCount,
            ],
            'checks' => $checks,
            'results' => $checks,
        ];
        job_config_operation_response(200, 'check', $allReady, $data);
    }

    if ($action !== 'preview' && $action !== 'write') {
        job_config_response(400, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::INVALID_ACTION,'message'=>'action must be check, preview, or write']]);
    }

    // Canonical payload is flat: targets + JOB_lst + SEQ_lst + STEP_lst.
    // Accept the v13 nested payload shape only as a compatibility bridge.
    if (isset($request['payload']) && is_array($request['payload']) && !$hasConfig) {
        $payload = $request['payload'];
    } else {
        $payload = [
            'JOB_lst' => $request['JOB_lst'] ?? [],
            'SEQ_lst' => $request['SEQ_lst'] ?? [],
            'STEP_lst' => $request['STEP_lst'] ?? [],
        ];
    }

    if (!$hasConfig && !isset($request['payload'])) {
        job_config_response(400, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::CONFIG_REQUIRED,'message'=>'JOB_lst, SEQ_lst and STEP_lst are required for preview/write']]);
    }

    if ($action === 'preview') {
        $result = $remoteService->previewTargets($targets, $payload);
        job_config_operation_response(!empty($result['success']) ? 200 : 207, 'preview', !empty($result['success']), $result);
    }

    $result = $remoteService->writeTargets($targets, $payload);
    if (!empty($result['no_ready_targets'])) {
        job_config_response(409, [
            'success'=>false,
            'action'=>'write',
            'summary'=>$result['summary'] ?? null,
            'checks'=>job_config_public_checks(isset($result['checks']) && is_array($result['checks']) ? $result['checks'] : []),
            'results'=>job_config_public_results(isset($result['results']) && is_array($result['results']) ? $result['results'] : []),
            'error'=>['code'=>JobConfigErrorCodes::NO_READY_TARGETS,'message'=>'No target controller passed the preflight check. Nothing was written.'],
            'elapsed_ms'=>max(0, (int)($result['elapsed_ms'] ?? 0)),
        ]);
    }
    job_config_operation_response(!empty($result['success']) ? 200 : 207, 'write', !empty($result['success']), $result);

} catch (InvalidArgumentException $e) {
    job_config_response(400, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::INVALID_TARGETS,'message'=>$e->getMessage()]]);
} catch (JobConfigApiException $e) {
    job_config_response($e->getHttpStatus(), ['success'=>false,'error'=>array_merge(['code'=>$e->getErrorCode(),'message'=>$e->getMessage()], $e->getDetails())]);
} catch (Throwable $e) {
    error_log('[replace_job_config] ' . $e->getMessage());
    job_config_response(500, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::INTERNAL_ERROR,'message'=>'Unexpected server error']]);
}
