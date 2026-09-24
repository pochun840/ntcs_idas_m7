<?php
declare(strict_types=1);

/**
 * Controller callback: GET or POST when a new ntcs_data row has been committed.
 * GET accepts ?id=123; POST accepts JSON {"id":123}. Both may omit the ID.
 * The iDAS server then calls its
 * existing get_operation_api.php?type=json&limit=1 endpoint.
 */
require_once dirname(__DIR__) . '/app/config/paths.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function notifyReply(int $code, array $data): void
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? '';
if ($method !== 'GET' && $method !== 'POST') {
    header('Allow: GET, POST');
    notifyReply(405, ['ok' => false, 'message' => 'GET or POST required']);
}

// Optional shared secret for deployments where other hosts can reach this URL.
$token = getenv('IDAS_OPERATION_NOTIFY_TOKEN');
if ($token !== false && $token !== '' && !hash_equals($token, (string)($_SERVER['HTTP_X_IDAS_TOKEN'] ?? ''))) {
    notifyReply(401, ['ok' => false, 'message' => 'Invalid notification token']);
}

if ($method === 'GET') {
    $payload = $_GET;
} else {
    $input = file_get_contents('php://input');
    if ($input === false) notifyReply(400, ['ok' => false, 'message' => 'Cannot read request']);
    $payload = trim($input) === '' ? [] : json_decode($input, true);
    if (!is_array($payload) || ($payload !== [] && array_values($payload) === $payload)) {
        notifyReply(400, ['ok' => false, 'message' => 'Expected a JSON object']);
    }
}
$requestedId = null;
if (array_key_exists('id', $payload)) {
    $requestedId = filter_var($payload['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($requestedId === false) notifyReply(400, ['ok' => false, 'message' => 'id must be a positive integer']);
}

try {
    $statePath = idas_path('database_root', '.operation_notify_cursor.json');
    $lock = fopen($statePath . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) throw new RuntimeException('Cannot lock notification state');

    $lastId = 0;
    if (is_file($statePath)) {
        $saved = json_decode((string)file_get_contents($statePath), true);
        if (!is_array($saved) || !isset($saved['last_id']) || !is_numeric($saved['last_id'])) {
            throw new RuntimeException('Notification state is invalid');
        }
        $lastId = (int)$saved['last_id'];
    }
    if ($requestedId !== null && $requestedId <= $lastId) {
        notifyReply(200, ['ok' => true, 'status' => 'duplicate', 'last_id' => $lastId]);
    }

    // The export API lives on this same machine; loopback keeps working when
    // its LAN/Wi-Fi address changes. Override only for a nonstandard setup.
    $url = getenv('IDAS_OPERATION_EXPORT_URL') ?: 'http://127.0.0.1/idas/api/get_operation_api.php?type=json&limit=1';
    $response = @file_get_contents($url, false, stream_context_create(['http' => [
        'method' => 'GET', 'timeout' => 5, 'ignore_errors' => true,
        'header' => "Accept: application/json\r\nConnection: close\r\n",
    ]]));
    $statusLine = $http_response_header[0] ?? '';
    if ($response === false || !preg_match('/^HTTP\/\S+ 200(?:\s|$)/', $statusLine)) {
        throw new RuntimeException('get_operation_api request failed: ' . $statusLine);
    }
    $records = json_decode($response, true);
    if (!is_array($records) || !isset($records[0]) || !is_array($records[0]) ||
        !isset($records[0]['id']) || filter_var($records[0]['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        throw new RuntimeException('get_operation_api returned no valid record');
    }
    $latestId = (int)$records[0]['id'];
    if ($requestedId !== null && $latestId < $requestedId) {
        notifyReply(409, ['ok' => false, 'message' => 'Record not yet visible in get_operation_api', 'last_id' => $lastId]);
    }
    if ($latestId <= $lastId) {
        notifyReply(200, ['ok' => true, 'status' => 'duplicate', 'last_id' => $lastId]);
    }

    $encoded = json_encode(['last_id' => $latestId]);
    if ($encoded === false || file_put_contents($statePath, $encoded, LOCK_EX) === false) {
        throw new RuntimeException('Cannot save notification state');
    }
    notifyReply(200, ['ok' => true, 'status' => 'new_record', 'last_id' => $latestId, 'record' => $records[0]]);
} catch (Throwable $e) {
    error_log('[idas_operation_notify] ' . $e->getMessage());
    notifyReply(503, ['ok' => false, 'message' => $e->getMessage()]);
}
