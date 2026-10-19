<?php

declare(strict_types=1);

// Receive barcode and retrieve its recipe from the configured MES endpoint.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function barcodeReceiveResponse(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    barcodeReceiveResponse(405, ['success' => false, 'message' => '只允許 POST']);
}

$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
if (strpos($contentType, 'application/json') === false) {
    barcodeReceiveResponse(415, ['success' => false, 'message' => 'Content-Type 必須為 application/json']);
}

$raw = file_get_contents('php://input');
$body = $raw === false ? null : json_decode($raw, true);
if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
    barcodeReceiveResponse(400, ['success' => false, 'message' => 'JSON 格式錯誤']);
}

$value = $body['barcode'] ?? null;
if (!is_string($value) || trim($value) === '') {
    barcodeReceiveResponse(400, ['success' => false, 'message' => 'barcode 必須為非空字串']);
}
$barcode = trim($value);

// json_encode escapes control characters before they reach the Apache log.
$loggedBarcode = json_encode($barcode, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($loggedBarcode === false) {
    barcodeReceiveResponse(400, ['success' => false, 'message' => 'barcode 必須為 UTF-8 字串']);
}
try {
    require_once dirname(__DIR__) . '/service/BarcodeRecipeFlowService.php';
    [$http,$result]=(new BarcodeRecipeFlowService())->receive($barcode,'POST');
    barcodeReceiveResponse($http,$result);
} catch(Throwable $e) {error_log('[idas_barcode_state] '.$e->getMessage());barcodeReceiveResponse(503,['success'=>false,'message'=>'無法處理接收條碼']);}
