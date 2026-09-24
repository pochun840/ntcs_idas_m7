<?php

declare(strict_types=1);

// Local test endpoint for the MES barcode interface. No controller writes.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function mockBarcodeReply(int $status, array $response): void
{
    http_response_code($status);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    mockBarcodeReply(405, ['code' => 405, 'msg' => 'POST required']);
}

if (stripos((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') === false) {
    mockBarcodeReply(415, ['code' => 415, 'msg' => 'Content-Type must be application/json']);
}

$raw = file_get_contents('php://input');
$request = $raw === false ? null : json_decode($raw, true);
if (!is_array($request) || json_last_error() !== JSON_ERROR_NONE) {
    mockBarcodeReply(400, ['code' => 400, 'msg' => 'Invalid JSON']);
}

$barcode = $request['barcode'] ?? null;
if (!is_string($barcode) || trim($barcode) === '') {
    mockBarcodeReply(400, ['code' => 400, 'msg' => 'barcode is required']);
}

$fixture = dirname(__DIR__) . '/app/config/huarong_mes_barcode_mock.json';
$contents = @file_get_contents($fixture);
$response = $contents === false ? null : json_decode($contents, true);
if (!is_array($response) || !isset($response['data']['work_order']) || !is_array($response['data']['work_order'])) {
    mockBarcodeReply(500, ['code' => 500, 'msg' => 'Test MES response is unavailable']);
}

$response['data']['work_order']['SN'] = trim($barcode);
mockBarcodeReply(200, $response);
