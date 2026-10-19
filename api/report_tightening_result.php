<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/libraries/Database.php';
require_once dirname(__DIR__) . '/service/HuarongTighteningReportService.php';
require_once dirname(__DIR__) . '/service/HuarongTighteningSenderService.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

function report_response(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') { http_response_code(204); exit; }
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    report_response(405, ['code' => 4003, 'msg' => '報文格式錯誤', 'data' => ['save_status' => 0, 'message' => '只允許 POST']]);
}

try {
    $config = require dirname(__DIR__) . '/app/config/huarong_mes.php';
    $contextFile = (string)($config['context_file'] ?? '');
    if ($contextFile === '' || !is_file($contextFile) || !is_readable($contextFile)) {
        report_response(409, ['code' => 4001, 'msg' => '上報失敗', 'data' => ['save_status' => 0, 'message' => '沒有 READY MES Context，請先完成接口 1']]);
    }
    $context = json_decode((string)file_get_contents($contextFile), true);
    if (!is_array($context) || ($context['status'] ?? '') !== 'READY') {
        report_response(409, ['code' => 4001, 'msg' => '上報失敗', 'data' => ['save_status' => 0, 'message' => 'MES Context 尚未 READY']]);
    }
    $sn = trim((string)($context['SN'] ?? ''));
    if ($sn === '') report_response(400, ['code' => 4002, 'msg' => 'SN 不能為空', 'data' => ['save_status' => 0, 'message' => 'READY Context 缺少 SN']]);

    $db = (new Database())->getDb_data();
    $mapper = new HuarongTighteningReportService();
    $rows = $mapper->fetchProductRows($db, $context);
    if (!$rows) {
        report_response(404, ['code' => 4001, 'msg' => '上報失敗', 'data' => ['SN' => $sn, 'save_status' => 0, 'message' => '目前沒有新的鎖附結果']]);
    }
    if (!$mapper->isProductComplete($rows)) {
        report_response(409, ['code' => 4001, 'msg' => '產品尚未完成', 'data' => ['SN' => $sn, 'save_status' => 0, 'message' => '等待 fasten_status=6']]);
    }
    $payload = $mapper->buildPayload($rows, $context);
    $sender = new HuarongTighteningSenderService($config);
    $sent = $sender->send($payload);
    $mes = $sent['response'];

    if (!isset($mes['code']) || (int)$mes['code'] !== 0 || (int)($mes['data']['save_status'] ?? 0) !== 1) {
        report_response(422, is_array($mes) ? $mes : ['code' => 4001, 'msg' => '上報失敗', 'data' => ['SN' => $sn, 'save_status' => 0]]);
    }

    // MES 確認成功後才前移游標；失敗時保留同一筆，下一次可重試，不會漏資料。
    $context['last_reported_result_id'] = (int)$rows[count($rows) - 1]['id'];
    $context['last_reported_at'] = date('Y-m-d H:i:s');
    $context['status'] = 'REPORTED';
    $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($json === false || file_put_contents($contextFile, $json . PHP_EOL, LOCK_EX) === false) {
        throw new RuntimeException('MES Context 更新上報游標失敗');
    }

    report_response(200, $mes);
} catch (InvalidArgumentException $e) {
    $code = strpos($e->getMessage(), 'SN') !== false ? 4002 : 4003;
    report_response(400, ['code' => $code, 'msg' => $code === 4002 ? 'SN 不能為空' : '報文格式錯誤', 'data' => ['save_status' => 0, 'message' => $e->getMessage()]]);
} catch (Throwable $e) {
    error_log('[huarong_report] ' . $e->getMessage());
    report_response(502, ['code' => 4001, 'msg' => '上報失敗', 'data' => ['save_status' => 0, 'message' => $e->getMessage()]]);
}
