<?php

declare(strict_types=1);

function remote_job_sanitize_public_data($value)
{
    if (!is_array($value)) return $value;
    foreach (['controller_database', 'controller_root', 'backup'] as $key) unset($value[$key]);
    foreach ($value as $key => $item) {
        if (is_array($item)) $value[$key] = remote_job_sanitize_public_data($item);
    }
    return $value;
}

function remote_job_response(int $status, array $body): void
{
    $body = remote_job_sanitize_public_data($body);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    require_once dirname(__DIR__) . '/app/config/config.php';
    require_once dirname(__DIR__) . '/app/libraries/Controller.php';
    require_once dirname(__DIR__) . '/service/JobConfigReplaceService.php';
    require_once dirname(__DIR__) . '/service/RemoteJobConfigService.php';

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/remote_job_config.php'));
    $idasBaseUrl = preg_replace('#/api/[^/]+$#', '', $scriptName);
    if (!is_string($idasBaseUrl) || $idasBaseUrl === '' || $idasBaseUrl === $scriptName) $idasBaseUrl = '/idas';

    $localService = new JobConfigReplaceService();
    $service = new RemoteJobConfigService(
        $idasBaseUrl,
        static function () use ($localService) { return $localService->preflight(); },
        static function (array $payload) use ($localService) { return $localService->replace($payload); }
    );

    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if ($method === 'GET') {
        $action = strtolower(trim((string)($_GET['action'] ?? 'network')));
        if ($action !== 'network') remote_job_response(400, ['success' => false, 'error' => ['code' => 'INVALID_ACTION', 'message' => 'Unknown GET action']]);
        remote_job_response(200, ['success' => true, 'data' => $service->networkInfo()]);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');
        remote_job_response(405, ['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'GET or POST is required']]);
    }

    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($contentType !== 'application/json') remote_job_response(415, ['success' => false, 'error' => ['code' => 'UNSUPPORTED_MEDIA_TYPE', 'message' => 'Content-Type must be application/json']]);

    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') remote_job_response(400, ['success' => false, 'error' => ['code' => 'EMPTY_BODY', 'message' => 'JSON body is required']]);
    if (strlen($raw) > JobConfigReplaceService::maxBodyBytes() + 65536) remote_job_response(413, ['success' => false, 'error' => ['code' => 'PAYLOAD_TOO_LARGE', 'message' => 'Request is too large']]);
    $request = json_decode($raw, true);
    if (!is_array($request) || json_last_error() !== JSON_ERROR_NONE) remote_job_response(400, ['success' => false, 'error' => ['code' => 'INVALID_JSON', 'message' => json_last_error_msg()]]);

    $action = strtolower(trim((string)($request['action'] ?? 'check')));
    $targets = $service->normalizeTargets($request['targets'] ?? []);

    if ($action === 'check') {
        $checks = $service->checkTargets($targets);
        $allReady = true;
        foreach ($checks as $check) if (empty($check['ready'])) { $allReady = false; break; }
        remote_job_response(200, ['success' => $allReady, 'data' => ['all_ready' => $allReady, 'checks' => $checks]]);
    }

    if ($action === 'write') {
        $payload = $request['payload'] ?? null;
        if (!is_array($payload)) remote_job_response(400, ['success' => false, 'error' => ['code' => 'PAYLOAD_REQUIRED', 'message' => 'payload must contain the JOB / SEQ / STEP JSON object']]);
        $result = $service->writeTargets($targets, $payload);
        if (!empty($result['no_ready_targets'])) {
            remote_job_response(409, ['success' => false, 'error' => ['code' => 'NO_READY_TARGETS', 'message' => 'No target controller passed the preflight check. Nothing was written.'], 'data' => $result]);
        }
        // 200 = every target written, 207 = per-target partial result.  A target
        // that fails preflight is skipped and does not block ready controllers.
        remote_job_response(!empty($result['success']) ? 200 : 207, ['success' => !empty($result['success']), 'data' => $result]);
    }

    remote_job_response(400, ['success' => false, 'error' => ['code' => 'INVALID_ACTION', 'message' => 'action must be check or write']]);
} catch (InvalidArgumentException $e) {
    remote_job_response(400, ['success' => false, 'error' => ['code' => 'INVALID_TARGETS', 'message' => $e->getMessage()]]);
} catch (JobConfigApiException $e) {
    remote_job_response($e->getHttpStatus(), ['success' => false, 'error' => array_merge(['code' => $e->getErrorCode(), 'message' => $e->getMessage()], $e->getDetails())]);
} catch (Throwable $e) {
    error_log('[remote_job_config] ' . $e->getMessage());
    remote_job_response(500, ['success' => false, 'error' => ['code' => 'INTERNAL_ERROR', 'message' => 'Unexpected server error']]);
}
