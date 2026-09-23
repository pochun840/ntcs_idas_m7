<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
function toolReply(int $status, array $value): void {
    http_response_code($status);
    echo json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    toolReply(405, ['success'=>false,'message'=>'GET required']);
}
try {
    require_once dirname(__DIR__) . '/app/config/config.php';
    require_once dirname(__DIR__) . '/app/libraries/Database.php';
    require_once dirname(__DIR__) . '/app/models/Miscellaneous.php';
    require_once dirname(__DIR__) . '/service/JobConfigNetworkService.php';
    require_once dirname(__DIR__) . '/service/JobConfigHttpClient.php';
    $target = trim((string)($_GET['target'] ?? ''));
    if ($target !== '') {
        $network = new JobConfigNetworkService();
        $networks = $network->localNetworks();
        $match = filter_var($target, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $network->matchedNetwork($target, $networks) : null;
        if ($match === null || !$network->isUsableHostAddress($target, $match)) {
            toolReply(400, ['success'=>false,'message'=>'Target must be a controller on the local subnet']);
        }
        if (!$network->isLocalIp($target, $networks)) {
            $base = preg_replace('#/api/[^/]+$#', '', (string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/job_config_form_tool.php'));
            $path = ($base ?: '/idas') . '/api/job_config_form_tool.php';
            $client = new JobConfigHttpClient();
            $responses = $client->requestMany(['tool'=>['method'=>'GET','url'=>'http://' . $target . $path,'body'=>null,'timeout'=>5]]);
            $result = $responses['tool'] ?? [];
            $json = $result['json'] ?? null;
            if (empty($result['transport_ok']) || (int)($result['http_status'] ?? 0) !== 200 || !is_array($json) || empty($json['success'])) {
                toolReply(503, ['success'=>false,'message'=>'Cannot read tool capabilities from target controller']);
            }
            toolReply(200, $json);
        }
    }
    $db = (new Database())->getDb_das_tools();
    if (!($db instanceof PDO)) toolReply(503, ['success'=>false,'message'=>'Tool database unavailable']);
    $unitStatement = $db->query('SELECT torque_unit FROM ' . TABLE_NTCS_DEVICE . ' LIMIT 1');
    $device = $unitStatement ? $unitStatement->fetch(PDO::FETCH_ASSOC) : false;
    if (!is_array($device) || !isset($device['torque_unit']) ||
        !in_array((string)$device['torque_unit'], ['0','1','2','3','4'], true)) {
        toolReply(503, ['success'=>false,'message'=>'Controller torque unit unavailable']);
    }
    $controllerUnit = (int)$device['torque_unit'];
    $statement = $db->query('SELECT min_torque, max_torque, min_rpm, max_rpm FROM ntcs_tool_test LIMIT 1');
    $row = $statement ? $statement->fetch(PDO::FETCH_ASSOC) : false;
    if (!is_array($row)) toolReply(503, ['success'=>false,'message'=>'Tool information unavailable']);
    foreach (['min_torque','max_torque','min_rpm','max_rpm'] as $key) {
        if (!isset($row[$key]) || !is_numeric($row[$key])) toolReply(503, ['success'=>false,'message'=>'Tool information incomplete']);
        $row[$key] = (float)$row[$key];
    }
    if ($row['min_torque'] <= 0 || $row['max_torque'] < $row['min_torque'] || $row['max_rpm'] <= 0) {
        toolReply(503, ['success'=>false,'message'=>'Tool information invalid']);
    }
    // Use the same conversion and rounding path as Step::edit_step().
    $misc = new Miscellaneous();
    $names = $misc->details('torque_unit');
    $decimals = $misc->details('decimals');
    $ranges = [];
    foreach ($names as $unit => $label) {
        $minConverted = $misc->convert_all_torque_units($row['min_torque'] / 1000, 1);
        $maxConverted = $misc->convert_all_torque_units($row['max_torque'] / 1000, 1);
        $precision = $decimals[$unit] ?? 3;
        $min = (float)str_replace(',', '', (string)($minConverted[$label] ?? 0));
        $max = (float)str_replace(',', '', (string)($maxConverted[$label] ?? 0));
        $high = (float)number_format($misc->roundToNDecimals($max * 1.10, $precision), $precision, '.', '');
        if ($unit === 0 || $unit === 2) $high += 0.01;
        if ($unit === 3) $high += 0.0001;
        $ranges[$unit] = ['min'=>$min,'max'=>$max,'high'=>$high,'precision'=>$precision];
    }
    $row['ranges'] = $ranges;
    $row['controller_torque_unit'] = $controllerUnit;
    toolReply(200, ['success'=>true,'tool'=>$row]);
} catch (Throwable $e) {
    error_log('[job_config_form_tool] ' . $e->getMessage());
    toolReply(503, ['success'=>false,'message'=>'Tool information unavailable']);
}
