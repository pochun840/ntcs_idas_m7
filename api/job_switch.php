<?php

declare(strict_types=1);

function job_switch_response(
    int $httpStatus,
    int $code,
    string $msg,
    int $switchStatus,
    int $targetJobId,
    int $currentJobId,
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
            'switch_status' => $switchStatus,
            'target_job_id' => $targetJobId,
            'current_job_id' => $currentJobId,
            'execute_time' => date('Y-m-d H:i:s'),
            'message' => $message,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once dirname(__DIR__) . '/app/config/config.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    job_switch_response(
        405, 3003, '參數無效', 0, 0, 0, '僅支援 POST'
    );
}

try {
    require_once dirname(__DIR__) . '/app/libraries/Controller.php';
    require_once dirname(__DIR__) . '/service/JobSwitchService.php';

    $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
    if ($contentType !== '' &&
        strpos($contentType, 'application/json') === false) {
        job_switch_response(
            415, 3003, '參數無效', 0, 0, 0,
            'Content-Type 必須為 application/json'
        );
    }

    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        job_switch_response(
            400, 3003, '參數無效', 0, 0, 0,
            'JSON 內容不可為空'
        );
    }

    $body = json_decode($raw, true);
    if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
        job_switch_response(
            400, 3003, '參數無效', 0, 0, 0,
            'JSON 格式錯誤'
        );
    }

    if (!array_key_exists('job_id', $body)) {
        job_switch_response(
            400, 3003, '參數無效', 0, 0, 0,
            '缺少 job_id'
        );
    }

    $rawJobId = $body['job_id'];

    if (
        is_bool($rawJobId) ||
        is_array($rawJobId) ||
        is_object($rawJobId) ||
        filter_var($rawJobId, FILTER_VALIDATE_INT) === false
    ) {
        job_switch_response(
            400, 3003, '參數無效', 0, 0, 0,
            'job_id 必須為整數'
        );
    }

    $jobId = (int)$rawJobId;

    if ($jobId < 0 || $jobId > 9999) {
        job_switch_response(
            400, 3003, '參數無效', 0, $jobId, 0,
            'job_id 超出有效範圍'
        );
    }

    $service = new JobSwitchService();
    $result = $service->switchJob($jobId);

    job_switch_response(
        200,
        0,
        'success',
        1,
        $jobId,
        (int)$result['current_job_id'],
        '工作切換完成'
    );

} catch (JobSwitchException $e) {
    error_log(
        '[job-switch] API_ERROR code=' . $e->getApiCode() .
        ' http=' . $e->getHttpStatus() .
        ' message=' . $e->getMessage()
    );

    $msg = $e->getApiCode() === 3002
        ? '設備忙碌'
        : ($e->getApiCode() === 3003
            ? '參數無效'
            : '切換工作失敗');

    job_switch_response(
        $e->getHttpStatus(),
        $e->getApiCode(),
        $msg,
        0,
        isset($jobId) ? $jobId : 0,
        $e->getCurrentJobId(),
        $e->getMessage()
    );

} catch (Throwable $e) {
    error_log(
        '[job-switch] UNEXPECTED ' .
        get_class($e) . ': ' . $e->getMessage()
    );

    job_switch_response(
        500,
        3001,
        '切換工作失敗',
        0,
        isset($jobId) ? $jobId : 0,
        0,
        '系統執行 Job 切換時發生未預期錯誤'
    );
}
