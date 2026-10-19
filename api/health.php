<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/service/JobConfigErrorCodes.php';
require_once dirname(__DIR__) . '/service/JobConfigApiConfig.php';

function idas_health_api_version(): string
{
    return JobConfigApiConfig::VERSION;
}

function idas_health_response(int $status, array $body): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-iDAS-API-Version: ' . idas_health_api_version());
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    idas_health_response(405, [
        'success' => false,
        'api_version' => idas_health_api_version(),
        'error' => ['code' => JobConfigErrorCodes::METHOD_NOT_ALLOWED, 'message' => 'GET is required'],
    ]);
}

idas_health_response(200, [
    'success' => true,
    'status' => 'ok',
    'product' => 'NTCS7',
    'service' => 'iDAS JOB/SEQ/STEP API',
    'api_version' => idas_health_api_version(),
    'time' => date('Y-m-d H:i:s'),
]);
