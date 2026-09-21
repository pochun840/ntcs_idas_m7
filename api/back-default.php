<?php

declare(strict_types=1);

function back_default_response(
    int $httpStatus,
    int $code,
    string $msg,
    int $resetStatus,
    string $message,
    array $extraData = []
): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    http_response_code($httpStatus);

    $data = array_merge([
        'reset_status' => $resetStatus,
        'reset_time' => date('Y-m-d H:i:s'),
        'device_no' => back_default_device_no(),
        'message' => $message,
    ], $extraData);

    echo json_encode([
        'code' => $code,
        'msg' => $msg,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function back_default_device_no(): string
{
    try {
        $path = IDAS_PATH_DATABASE_ROOT . '/ntcs_device_IDAS.db';
        if (!is_file($path)) {
            return 'SCREWDRIVER_01';
        }

        $pdo = idas_sqlite_connect(
            $path,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );

        $cols = array_column(
            $pdo->query('PRAGMA table_info(ntcs_device_test)')->fetchAll(PDO::FETCH_ASSOC),
            'name'
        );

        foreach (['device_sn', 'serial_number', 'sn'] as $col) {
            if (!in_array($col, $cols, true)) {
                continue;
            }

            $v = trim((string)$pdo
                ->query('SELECT "' . $col . '" FROM ntcs_device_test LIMIT 1')
                ->fetchColumn());

            if ($v !== '') {
                return $v;
            }
        }
    } catch (Throwable $e) {
        error_log('[back-default] device_no lookup failed: ' . $e->getMessage());
    }

    return 'SCREWDRIVER_01';
}

require_once dirname(__DIR__) . '/app/config/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    back_default_response(405, 2003, '參數無效', 0, '僅支援 POST');
}

try {
    require_once dirname(__DIR__) . '/app/libraries/Controller.php';
    require_once dirname(__DIR__) . '/service/BackDefaultService.php';

    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if ($contentType !== '' && strpos($contentType, 'application/json') === false) {
        back_default_response(
            415,
            2003,
            '參數無效',
            0,
            'Content-Type 必須為 application/json'
        );
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        back_default_response(400, 2003, '參數無效', 0, 'JSON 內容不可為空');
    }

    $body = json_decode($raw, true);
    if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
        back_default_response(400, 2003, '參數無效', 0, 'JSON 格式錯誤');
    }

    if (
        !array_key_exists('back_default_value', $body) ||
        filter_var(
            $body['back_default_value'],
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 1]]
        ) === false
    ) {
        back_default_response(
            400,
            2003,
            '參數無效',
            0,
            'back_default_value 必須為 1'
        );
    }

    $service = new BackDefaultService();
    $result = $service->reset();

    back_default_response(
        200,
        0,
        'success',
        1,
        '重置成功',
        [
            'mirror_synced' => (bool)($result['mirror_synced'] ?? false),
            'elapsed_ms' => (int)($result['elapsed_ms'] ?? 0),
        ]
    );
} catch (BackDefaultException $e) {
    error_log(
        '[back-default] API_ERROR code=' . $e->getErrorCode() .
        ' http=' . $e->getHttpStatus() .
        ' message=' . $e->getMessage()
    );

    back_default_response(
        $e->getHttpStatus(),
        $e->getErrorCode(),
        '重置失敗',
        0,
        $e->getMessage()
    );
} catch (Throwable $e) {
    error_log(
        '[back-default] UNEXPECTED ' . get_class($e) . ': ' . $e->getMessage()
    );

    back_default_response(
        500,
        2001,
        '重置失敗',
        0,
        '系統執行重置時發生未預期錯誤'
    );
}
