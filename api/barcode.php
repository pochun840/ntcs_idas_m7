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

const HUARONG_BARCODE_FILE = '/home/kls/NTCS7/barcode.txt';

/**
 * Read one barcode from the controller-side barcode file.
 * The file is created as an empty 0-byte file when it does not exist.
 */
function huarong_barcode_read_file(string $path): string
{
    $dir = dirname($path);
    if (!is_dir($dir)) {
        throw new RuntimeException('Barcode 目錄不存在：' . $dir);
    }

    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        throw new RuntimeException('無法建立或開啟 Barcode 檔案：' . $path);
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException('無法鎖定 Barcode 檔案');
        }
        rewind($fp);
        $raw = stream_get_contents($fp);
        flock($fp, LOCK_UN);
        return trim((string)$raw);
    } finally {
        fclose($fp);
    }
}

/**
 * Clear the barcode file only when it still contains the barcode processed by
 * this request. This prevents a newly-scanned barcode from being erased by an
 * older request that finishes later.
 */
function huarong_barcode_clear_if_same(string $path, string $processedBarcode): bool
{
    $fp = @fopen($path, 'c+');
    if ($fp === false) {
        throw new RuntimeException('無法開啟 Barcode 檔案進行清空：' . $path);
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            throw new RuntimeException('無法鎖定 Barcode 檔案進行清空');
        }

        rewind($fp);
        $current = trim((string)stream_get_contents($fp));
        if ($current !== $processedBarcode) {
            flock($fp, LOCK_UN);
            return false;
        }

        rewind($fp);
        if (!ftruncate($fp, 0)) {
            throw new RuntimeException('Barcode 檔案清空失敗');
        }
        fflush($fp);
        flock($fp, LOCK_UN);
        return true;
    } finally {
        fclose($fp);
    }
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

$raw = (string)file_get_contents('php://input');
$body = [];
if (trim($raw) !== '') {
    $decoded = json_decode($raw, true);
    if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
        huarong_barcode_response(400, ['success' => false, 'message' => 'JSON 格式錯誤']);
    }
    $body = $decoded;
}

// 保留 POST barcode/Barcode 供人工測試；正式流程未帶 Barcode 時，
// 從 /home/kls/NTCS7/barcode.txt 取得待處理條碼。
$barcode = trim((string)($body['barcode'] ?? $body['Barcode'] ?? ''));
$barcodeSource = 'request';
if ($barcode === '') {
    try {
        $barcode = huarong_barcode_read_file(HUARONG_BARCODE_FILE);
        $barcodeSource = 'file';
    } catch (Throwable $e) {
        huarong_barcode_response(500, ['success' => false, 'message' => $e->getMessage()]);
    }
}
if ($barcode === '') {
    huarong_barcode_response(400, [
        'success' => false,
        'message' => 'Barcode 檔案目前沒有待處理條碼',
        'barcode_file' => HUARONG_BARCODE_FILE,
    ]);
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

    // 只有 Barcode 是從檔案取得，而且 MES、Recipe 寫入、Job Switch、Context
    // 全部成功後才清空。若處理期間檔案已被寫入下一個 Barcode，則保留新值。
    $barcodeFileCleared = null;
    if ($barcodeSource === 'file') {
        $barcodeFileCleared = huarong_barcode_clear_if_same(HUARONG_BARCODE_FILE, $barcode);
    }

    huarong_barcode_response(200, [
        'success' => true,
        'message' => 'Barcode 校驗成功，JOB/SEQ/STEP 已寫入控制器',
        'barcode_source' => $barcodeSource,
        'barcode_file_cleared' => $barcodeFileCleared,
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
                'switch_status' => (int)($switchResult['switch_status'] ?? 0),
                'target_job_id' => $targetJobId,
                'seq_id' => (int)($switchResult['seq_id'] ?? 1),
                'protocol' => $switchResult['protocol'] ?? null,
                'device_id' => $switchResult['device_id'] ?? null,
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
