<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/service/JobConfigNetworkService.php';
require_once dirname(__DIR__) . '/service/JobConfigModbusSwitchService.php';
require_once dirname(__DIR__) . '/service/JobConfigControllerSettingsService.php';
require_once dirname(__DIR__) . '/service/JobConfigHttpClient.php';
require_once dirname(__DIR__) . '/app/config/paths.php';

function mappedSwitchBarcode(int $job, int $seq): ?string
{
    $path = idas_path('database_root', 'das.db');
    if (!is_file($path) || !is_readable($path)) throw new RuntimeException('Barcode mapping database is unavailable');
    $db = idas_sqlite_connect($path);
    $table = $db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='barcode_step_mapping'")->fetchColumn();
    if (!$table) return null;
    $query = $db->prepare('SELECT raw_barcode FROM barcode_step_mapping WHERE job_id=:job AND seq_id=:seq AND barcode_mode=3 ORDER BY updated_at DESC, id DESC LIMIT 1');
    $query->execute([':job' => $job, ':seq' => $seq]);
    $barcode = $query->fetchColumn();
    if ($barcode === false) return null;
    $barcode = (string)$barcode;
    if ($barcode === '' || strlen($barcode) > 50 || !preg_match('/^[\x20-\x7E]+$/D', $barcode)) {
        throw new RuntimeException('Mapped switch barcode must contain 1–50 printable ASCII bytes');
    }
    return $barcode;
}

function switch_response(int $status, array $data): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    switch_response(405, ['success' => false, 'error' => ['message' => 'POST is required']]);
}
if (session_status() === PHP_SESSION_NONE) @session_start();
if (isset($_SESSION['user_law']) && (int)$_SESSION['user_law'] === 3) {
    switch_response(403, ['success' => false, 'error' => ['message' => 'Operator cannot switch jobs']]);
}
$raw = file_get_contents('php://input', false, null, 0, 8193);
if ($raw === false || strlen($raw) > 8192) switch_response(413, ['success' => false, 'error' => ['message' => 'Request too large']]);
$input = json_decode($raw, true);
if (!is_array($input)) switch_response(400, ['success' => false, 'error' => ['message' => 'Invalid JSON']]);
$job = filter_var($input['job_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
$seq = filter_var($input['seq_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 50]]);
if ($job === false || $seq === false) switch_response(400, ['success' => false, 'error' => ['message' => 'Invalid JOB / SEQ ID']]);
try {
    $network = new JobConfigNetworkService();
    $targets = $network->normalizeTargets($input['targets'] ?? []);
    $networks = $network->localNetworks();
    if (!$networks) switch_response(409, ['success' => false, 'error' => ['message' => 'No active local network']]);
    $barcode = mappedSwitchBarcode((int)$job, (int)$seq);
    $results = [];
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/replace_job_config_switch.php'));
    $base = preg_replace('#/api/[^/]+$#', '', $scriptName);
    if (!is_string($base) || $base === '' || $base === $scriptName) $base = '/idas';
    $settingsRequests = [];
    foreach ($targets as $ip) {
        $subnet = $network->matchedNetwork($ip, $networks);
        if ($subnet !== null && $network->isUsableHostAddress($ip, $subnet)) {
            $settingsRequests[$ip] = [
                'method' => 'GET',
                'url' => 'http://' . $ip . $base . '/api/controller_modbus_settings.php',
                'timeout' => 5,
            ];
        }
    }
    $settingsResponses = (new JobConfigHttpClient())->requestMany($settingsRequests);
    foreach ($targets as $ip) {
        $subnet = $network->matchedNetwork($ip, $networks);
        if ($subnet === null || !$network->isUsableHostAddress($ip, $subnet)) {
            $results[$ip] = ['ip' => $ip, 'success' => false, 'message' => 'Target is outside the local subnet'];
            continue;
        }
        try {
            $response = $settingsResponses[$ip] ?? null;
            $json = is_array($response) ? ($response['json'] ?? null) : null;
            if (!is_array($response) || empty($response['transport_ok']) || (int)$response['http_status'] !== 200 || !is_array($json) || empty($json['success']) || !is_array($json['data'] ?? null)) {
                throw new RuntimeException('Unable to read this controller Modbus settings');
            }
            $settings = $json['data'];
            if ((int)($settings['modbus_type'] ?? 0) === 2) throw new RuntimeException('This controller uses OP protocol, not Modbus TCP');
            $switcher = new JobConfigModbusSwitchService((int)($settings['unit_id'] ?? 0), (int)($settings['port'] ?? 0));
            $switcher->switchJob($ip, (int)$job, (int)$seq, $barcode);
            $results[$ip] = ['ip' => $ip, 'success' => true, 'barcode_written' => $barcode !== null,
                'message' => $barcode !== null ? 'Modbus JOB/SEQ verified; barcode written at 396' : 'Modbus JOB/SEQ verified; no switch barcode mapped'];
        } catch (Throwable $e) {
            $results[$ip] = ['ip' => $ip, 'success' => false, 'message' => $e->getMessage()];
        }
    }
    $results = array_values($results);
    switch_response(200, ['success' => !in_array(false, array_column($results, 'success'), true), 'results' => $results]);
} catch (Throwable $e) {
    switch_response(400, ['success' => false, 'error' => ['message' => $e->getMessage()]]);
}
