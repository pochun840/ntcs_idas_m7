<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/service/HuarongBarcodeService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function huarong_barcode_response(int $status, array $body): void
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
    huarong_barcode_response(405, ['success' => false, 'message' => '只允許 POST']);
}

$contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
if ($contentType !== '' && strpos($contentType, 'application/json') === false) {
    huarong_barcode_response(415, ['success' => false, 'message' => 'Content-Type 必須為 application/json']);
}

$raw = file_get_contents('php://input');
$body = json_decode((string)$raw, true);
if (!is_array($body) || json_last_error() !== JSON_ERROR_NONE) {
    huarong_barcode_response(400, ['success' => false, 'message' => 'JSON 格式錯誤']);
}

$barcode = trim((string)($body['barcode'] ?? $body['Barcode'] ?? ''));
if ($barcode === '') {
    huarong_barcode_response(400, ['success' => false, 'message' => 'barcode 不能為空']);
}

try {
    $config = require dirname(__DIR__) . '/app/config/huarong_mes.php';
    $service = new HuarongBarcodeService($config);

    // 1) Barcode + device_id(station_code) -> 華榮 MES。
    $mes = $service->send($barcode);
    $data = $service->assertSuccessfulResponse($mes['response']);

    // 2) 先將華榮配方轉成既有單台寫入格式。
    $jobConfig = $service->mapJobConfig($data);

    // 3) 沿用既有 JobConfigReplaceService：登入狀態檢查、upsert、Controller reload、
    //    Mirror sync、寫後驗證與 rollback 全部使用同一套核心邏輯。
    require_once dirname(__DIR__) . '/app/config/config.php';
    require_once dirname(__DIR__) . '/app/libraries/Controller.php';
    require_once dirname(__DIR__) . '/app/libraries/Database.php';
    require_once dirname(__DIR__) . '/service/JobConfigReplaceService.php';
    require_once dirname(__DIR__) . '/service/JobSwitchService.php';

    $writer = new JobConfigReplaceService();
    $writeResult = $writer->replace($jobConfig);

    // 4) JOB/SEQ/STEP 寫入與驗證成功後，自動切換到 MES 回傳的 job_id。
    //    直接共用接口 4 的 JobSwitchService，不透過 HTTP 呼叫自己。
    $targetJobId = (int)$data['job']['job_id'];
    $switcher = new JobSwitchService();
    $switchResult = $switcher->switchJob($targetJobId);

    // 5) 寫入 + Job Switch 都成功後才建立 READY Context。
    //    同時記錄目前 ntcs_data 最新 id，接口 2 只會上報之後新產生的鎖附結果，
    //    避免把上一個 Barcode / SN 的舊資料送給華榮。
    $baselineResultId = 0;
    $dataDb = (new Database())->getDb_data();
    $stmt = $dataDb->query('SELECT COALESCE(MAX(id), 0) FROM ntcs_data');
    if ($stmt !== false) {
        $baselineResultId = (int)$stmt->fetchColumn();
    }
    $context = $service->saveContext($data, $mes['payload'], $baselineResultId);

    huarong_barcode_response(200, [
        'success' => true,
        'message' => 'Barcode 校驗成功，JOB/SEQ/STEP 已寫入控制器',
        'station_code' => $mes['payload']['station_code'],
        'http_code' => $mes['http_code'],
        'mes' => [
            'code' => (int)$mes['response']['code'],
            'msg' => (string)($mes['response']['msg'] ?? 'success'),
            'work_order' => $data['work_order'],
        ],
        'controller' => [
            'controller_updated' => !empty($writeResult['controller_updated']),
            'mirror_synced' => !empty($writeResult['idas_mirror_synced']),
            'verified' => !empty($writeResult['verified']),
            'counts' => $writeResult['counts'] ?? [],
            'job_switch' => [
                'switch_status' => 1,
                'target_job_id' => $targetJobId,
                'current_job_id' => (int)$switchResult['current_job_id'],
            ],
        ],
        'context_saved' => true,
        'SN' => $context['SN'] ?? '',
    ]);
} catch (HuarongMesResponseException $e) {
    // 華榮原始錯誤碼/訊息保留，方便現場直接判讀 1001~1005。
    huarong_barcode_response(422, [
        'success' => false,
        'source' => 'mes',
        'code' => $e->getMesCode(),
        'message' => $e->getMessage(),
        'mes_response' => $e->getMesResponse(),
    ]);
} catch (JobSwitchException $e) {
    huarong_barcode_response($e->getHttpStatus(), [
        'success' => false,
        'source' => 'job_switch',
        'code' => $e->getApiCode(),
        'message' => $e->getMessage(),
        'target_job_id' => isset($targetJobId) ? $targetJobId : 0,
        'current_job_id' => $e->getCurrentJobId(),
    ]);
} catch (JobConfigApiException $e) {
    huarong_barcode_response($e->getHttpStatus(), [
        'success' => false,
        'source' => 'controller',
        'code' => $e->getErrorCode(),
        'message' => $e->getMessage(),
        'details' => $e->getDetails(),
    ]);
} catch (InvalidArgumentException $e) {
    huarong_barcode_response(400, ['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('[huarong_barcode] ' . $e->getMessage());
    $status = strpos($e->getMessage(), 'Base URL') !== false ? 503 : 502;
    huarong_barcode_response($status, ['success' => false, 'message' => $e->getMessage()]);
}
