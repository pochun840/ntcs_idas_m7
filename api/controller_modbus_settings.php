<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/service/JobConfigNetworkService.php';
require_once dirname(__DIR__) . '/service/JobConfigControllerSettingsService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['success' => false, 'error' => 'GET required']);
    exit;
}
if (!(new JobConfigNetworkService())->isRemoteAddressOnLocalSubnet((string)($_SERVER['REMOTE_ADDR'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Same subnet required']);
    exit;
}
try {
    echo json_encode(['success' => true, 'data' => JobConfigControllerSettingsService::read()]);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
