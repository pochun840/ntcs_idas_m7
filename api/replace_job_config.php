<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/service/JobConfigErrorCodes.php';

// Kept only for backward compatibility with integrations created before the
// public api_version field was changed to report the installed iDAS version.
const JOB_CONFIG_LEGACY_API_VERSION = '1';
const JOB_CONFIG_REQUEST_DEDUP_TTL = 120;
const JOB_CONFIG_AUTO_DEDUP_TTL = 5;

$GLOBALS['JOB_CONFIG_REQUEST_STARTED_AT'] = microtime(true);


function job_config_idas_version(): string
{
    static $version = null;
    if (is_string($version)) return $version;

    $version = 'unknown';
    $infoFile = dirname(__DIR__) . '/info.json';
    $raw = @file_get_contents($infoFile);
    if (!is_string($raw) || trim($raw) === '') return $version;

    $info = json_decode($raw, true);
    if (!is_array($info)) return $version;
    $candidate = trim((string)($info['idas_version'] ?? ''));
    if ($candidate !== '') $version = $candidate;
    return $version;
}


function job_config_max_request_bytes(): int
{
    return JobConfigReplaceService::maxBodyBytes() + 65536;
}

function job_config_is_list_array(array $value): bool
{
    $i = 0;
    foreach ($value as $key => $_) {
        if ($key !== $i++) return false;
    }
    return true;
}

function job_config_canonicalize($value)
{
    if (!is_array($value)) return $value;
    if (job_config_is_list_array($value)) {
        $out = [];
        foreach ($value as $item) $out[] = job_config_canonicalize($item);
        return $out;
    }
    ksort($value, SORT_STRING);
    foreach ($value as $key => $item) $value[$key] = job_config_canonicalize($item);
    return $value;
}

function job_config_request_hash(array $request): string
{
    unset($request['request_key']);
    return hash('sha256', json_encode(job_config_canonicalize($request), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
}

function job_config_dedupe_dir(): string
{
    $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'idas_job_config_dedupe';
    if (!is_dir($dir)) @mkdir($dir, 0700, true);
    return $dir;
}

function job_config_dedupe_cleanup(string $dir): void
{
    $cutoff = time() - 86400;
    foreach ((array)glob($dir . DIRECTORY_SEPARATOR . '*.json') as $file) {
        $mtime = @filemtime($file);
        if (is_int($mtime) && $mtime < $cutoff) @unlink($file);
    }
}

function job_config_dedupe_path(string $scopeKey): string
{
    $dir = job_config_dedupe_dir();
    job_config_dedupe_cleanup($dir);
    return $dir . DIRECTORY_SEPARATOR . hash('sha256', $scopeKey) . '.json';
}

function job_config_dedupe_read(string $path): ?array
{
    $raw = @file_get_contents($path);
    if (!is_string($raw) || $raw === '') return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function job_config_dedupe_begin(array $request): array
{
    $requestKey = trim((string)($request['request_key'] ?? ''));
    if ($requestKey !== '' && !preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $requestKey)) {
        return ['error' => ['status' => 400, 'code' => JobConfigErrorCodes::INVALID_REQUEST_KEY, 'message' => 'request_key may contain only letters, numbers, dot, underscore, colon, and hyphen (max 128 characters).']];
    }

    $hash = job_config_request_hash($request);
    $automatic = ($requestKey === '');
    $scopeKey = $automatic ? ('auto:' . $hash) : ('key:' . $requestKey);
    $ttl = $automatic ? JOB_CONFIG_AUTO_DEDUP_TTL : JOB_CONFIG_REQUEST_DEDUP_TTL;
    $path = job_config_dedupe_path($scopeKey);
    $now = time();

    $existing = job_config_dedupe_read($path);
    if ($existing && ($now - (int)($existing['created_at'] ?? 0)) <= $ttl) {
        if (($existing['request_hash'] ?? '') !== $hash) {
            return ['error' => ['status' => 409, 'code' => JobConfigErrorCodes::REQUEST_KEY_CONFLICT, 'message' => 'The same request_key was already used with different request content.']];
        }
        if (($existing['state'] ?? '') === 'complete' && isset($existing['response_body']) && is_array($existing['response_body'])) {
            return ['replay' => [
                'status' => (int)($existing['response_status'] ?? 200),
                'body' => array_merge($existing['response_body'], ['duplicate_replay' => true]),
            ]];
        }
        return ['error' => ['status' => 409, 'code' => JobConfigErrorCodes::REQUEST_IN_PROGRESS, 'message' => 'An identical write request is already being processed.']];
    }
    if (is_file($path)) @unlink($path);

    $pending = [
        'created_at' => $now,
        'state' => 'pending',
        'request_hash' => $hash,
        'request_key' => $requestKey,
        'automatic' => $automatic,
    ];

    // Atomic create prevents two simultaneous retries from both entering the write path.
    $handle = @fopen($path, 'x');
    if ($handle === false) {
        $race = job_config_dedupe_read($path);
        if ($race && ($race['request_hash'] ?? '') === $hash && ($race['state'] ?? '') === 'complete' && isset($race['response_body']) && is_array($race['response_body'])) {
            return ['replay' => [
                'status' => (int)($race['response_status'] ?? 200),
                'body' => array_merge($race['response_body'], ['duplicate_replay' => true]),
            ]];
        }
        return ['error' => ['status' => 409, 'code' => JobConfigErrorCodes::REQUEST_IN_PROGRESS, 'message' => 'An identical write request is already being processed.']];
    }
    @flock($handle, LOCK_EX);
    fwrite($handle, json_encode($pending, JSON_UNESCAPED_SLASHES));
    fflush($handle);
    @flock($handle, LOCK_UN);
    fclose($handle);
    return ['context' => ['path' => $path, 'pending' => $pending]];
}

function job_config_dedupe_complete(int $status, array $body): void
{
    $context = $GLOBALS['JOB_CONFIG_DEDUPE_CONTEXT'] ?? null;
    if (!is_array($context) || empty($context['path']) || !isset($context['pending']) || !is_array($context['pending'])) return;
    $record = $context['pending'];
    $record['state'] = 'complete';
    $record['response_status'] = $status;
    $record['response_body'] = $body;
    $record['completed_at'] = time();
    @file_put_contents((string)$context['path'], json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    unset($GLOBALS['JOB_CONFIG_DEDUPE_CONTEXT']);
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
    if (!array_key_exists('api_version', $body)) $body['api_version'] = job_config_idas_version();
    if (!array_key_exists('elapsed_ms', $body)) {
        $startedAt = isset($GLOBALS['JOB_CONFIG_REQUEST_STARTED_AT']) ? (float)$GLOBALS['JOB_CONFIG_REQUEST_STARTED_AT'] : microtime(true);
        $body['elapsed_ms'] = max(0, (int)round((microtime(true) - $startedAt) * 1000));
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-iDAS-API-Version: ' . job_config_idas_version());
    if (class_exists('JobConfigReplaceService')) header('X-iDAS-Max-Request-Bytes: ' . job_config_max_request_bytes());
    $publicBody = job_config_sanitize_public_data($body);
    job_config_dedupe_complete($status, $publicBody);
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
        $acceptedVersions = [job_config_idas_version(), JOB_CONFIG_LEGACY_API_VERSION];
        if (!in_array($requestedVersion, $acceptedVersions, true)) {
            job_config_response(400, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::UNSUPPORTED_API_VERSION,'message'=>'api_version must match the installed iDAS version (' . job_config_idas_version() . ')']]);
        }
    }

    /*
     * Unified public API (canonical schema for MES / iDAS UI / CMD):
     * {
     *   "action": "check|preview|write",   // optional, defaults to write
     *   "request_key": "MES-20260914-0001", // recommended for write idempotency
     *   "targets": ["192.168.100.150"],
     *   "JOB_lst": [...],
     *   "SEQ_lst": [...],
     *   "STEP_lst": [...]
     * }
     *
     * Single and multi-controller deployment use the exact same schema; only
     * the number of targets changes.  A legacy raw local JOB/SEQ/STEP body is
     * still accepted for backward compatibility, but new integrations should
     * always send targets.
     */
    $action = strtolower(trim((string)($request['action'] ?? 'write')));
    $hasTargets = array_key_exists('targets', $request);
    $hasConfig = array_key_exists('JOB_lst', $request) || array_key_exists('SEQ_lst', $request) || array_key_exists('STEP_lst', $request);

    // Backward compatibility for older local callers. Not part of the new public contract.
    if (!$hasTargets && $hasConfig && !isset($request['payload'])) {
        if ($action === 'preview') {
            $result = $localService->preview($request);
            job_config_response(200, ['success'=>true, 'data'=>$result]);
        }
        if ($action !== 'write') job_config_response(400, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::INVALID_ACTION,'message'=>'action must be preview or write for legacy local JSON']]);
        $dedupe = job_config_dedupe_begin($request);
        if (!empty($dedupe['error'])) job_config_response((int)$dedupe['error']['status'], ['success'=>false,'error'=>['code'=>$dedupe['error']['code'],'message'=>$dedupe['error']['message']]]);
        if (!empty($dedupe['replay'])) job_config_response((int)$dedupe['replay']['status'], $dedupe['replay']['body']);
        if (!empty($dedupe['context'])) $GLOBALS['JOB_CONFIG_DEDUPE_CONTEXT'] = $dedupe['context'];
        $result = $localService->replace($request);
        job_config_response(200, ['success'=>true, 'data'=>$result]);
    }

    if (!$hasTargets) job_config_response(400, ['success'=>false,'error'=>['code'=>JobConfigErrorCodes::TARGETS_REQUIRED,'message'=>'targets is required']]);
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

    $dedupe = job_config_dedupe_begin($request);
    if (!empty($dedupe['error'])) job_config_response((int)$dedupe['error']['status'], ['success'=>false,'action'=>'write','error'=>['code'=>$dedupe['error']['code'],'message'=>$dedupe['error']['message']]]);
    if (!empty($dedupe['replay'])) job_config_response((int)$dedupe['replay']['status'], $dedupe['replay']['body']);
    if (!empty($dedupe['context'])) $GLOBALS['JOB_CONFIG_DEDUPE_CONTEXT'] = $dedupe['context'];

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
