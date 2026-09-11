<?php

declare(strict_types=1);

function job_config_sanitize_public_data($value)
{
    if (!is_array($value)) return $value;
    foreach (['controller_database', 'controller_root', 'backup'] as $key) unset($value[$key]);
    foreach ($value as $key => $item) {
        if (is_array($item)) $value[$key] = job_config_sanitize_public_data($item);
    }
    return $value;
}

function job_config_response(int $status, array $body): void
{
    $body = job_config_sanitize_public_data($body);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
if ($requestMethod === 'GET') {
    require __DIR__ . '/replace_job_config_web.php';
    exit;
}
if ($requestMethod !== 'POST') {
    header('Allow: POST');
    job_config_response(405, ['success'=>false,'error'=>['code'=>'METHOD_NOT_ALLOWED','message'=>'POST is required']]);
}

$contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
if ($contentType !== 'application/json') {
    job_config_response(415, ['success'=>false,'error'=>['code'=>'UNSUPPORTED_MEDIA_TYPE','message'=>'Content-Type must be application/json']]);
}

$declaredLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($declaredLength > 67108864) {
    job_config_response(413, ['success'=>false,'error'=>['code'=>'PAYLOAD_TOO_LARGE','message'=>'JSON body exceeds 64 MiB']]);
}
$raw = file_get_contents('php://input');
if ($raw === false || $raw === '') {
    job_config_response(400, ['success'=>false,'error'=>['code'=>'EMPTY_BODY','message'=>'JSON body is required']]);
}
if (strlen($raw) > 67108864) {
    job_config_response(413, ['success'=>false,'error'=>['code'=>'PAYLOAD_TOO_LARGE','message'=>'JSON body exceeds 64 MiB']]);
}
$payload = json_decode($raw, true);
if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
    job_config_response(400, ['success'=>false,'error'=>['code'=>'INVALID_JSON','message'=>json_last_error_msg()]]);
}

try {
    require_once dirname(__DIR__) . '/app/config/config.php';
    require_once dirname(__DIR__) . '/app/libraries/Controller.php';
    require_once dirname(__DIR__) . '/service/JobConfigReplaceService.php';
    $service = new JobConfigReplaceService();
    $result = $service->replace($payload);
    job_config_response(200, ['success'=>true,'data'=>$result]);
} catch (JobConfigApiException $e) {
    job_config_response($e->getHttpStatus(), ['success'=>false,'error'=>array_merge(['code'=>$e->getErrorCode(),'message'=>$e->getMessage()], $e->getDetails())]);
} catch (Throwable $e) {
    error_log('[replace_job_config] ' . $e->getMessage());
    job_config_response(500, ['success'=>false,'error'=>['code'=>'INTERNAL_ERROR','message'=>'Unexpected server error']]);
}
