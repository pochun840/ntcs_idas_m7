<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') { header('Allow: GET'); http_response_code(405); echo json_encode(['success'=>false]); exit; }
try {
    require_once dirname(__DIR__) . '/service/BarcodeReceiveStateService.php';
    echo json_encode(['success'=>true,'data'=>(new BarcodeReceiveStateService())->latest()],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    error_log('[idas_barcode_state] '.$e->getMessage()); http_response_code(503); echo json_encode(['success'=>false]);
}
