<?php

declare(strict_types=1);

function device_api_response(
    int $httpStatus,
    int $code,
    string $msg,
    int $deviceStatus,
    string $message
): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    http_response_code($httpStatus);

    echo json_encode([
        'code' => $code,
        'msg' => $msg,
        'data' => [
            'device_status' => $deviceStatus,
            'execute_time' => date('Y-m-d H:i:s'),
            'message' => $message,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function device_api_json_body(string $flagName, int $invalidCode): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        device_api_response(
            405, $invalidCode, '參數無效', 0, '僅支援 POST'
        );
    }

    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if ($contentType !== '' &&
        strpos($contentType, 'application/json') === false) {
        device_api_response(
            415, $invalidCode, '參數無效', 0,
            'Content-Type 必須為 application/json'
        );
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        device_api_response(
            400, $invalidCode, '參數無效', 0, 'JSON 內容不可為空'
        );
    }

    $body = json_decode($raw, true);
    if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
        device_api_response(
            400, $invalidCode, '參數無效', 0, 'JSON 格式錯誤'
        );
    }

    if (!array_key_exists($flagName, $body)) {
        device_api_response(
            400, $invalidCode, '參數無效', 0,
            '缺少 ' . $flagName
        );
    }

    $value = $body[$flagName];

    if (
        is_bool($value) ||
        is_array($value) ||
        is_object($value) ||
        filter_var($value, FILTER_VALIDATE_INT) === false ||
        (int)$value !== 1
    ) {
        device_api_response(
            400, $invalidCode, '參數無效', 0,
            $flagName . ' 必須固定為 1'
        );
    }

    return $body;
}
