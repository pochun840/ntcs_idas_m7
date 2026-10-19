<?php
/**
 * OP 協議 TCP Client 測試檔
 *
 * 用法：
 *   php test_op.php read 4097
 *   php test_op.php write 463 2
 *   php test_op.php send IDAS_READ_4097
 *
 * 預設連線：192.168.10.100:4545
 * 可用環境變數覆蓋：
 *   OP_SERVER_IP=192.168.10.100 OP_SERVER_PORT=4545 php test_op.php read 4097
 */

function op_send(string $command, string $host, int $port, int $connectTimeout = 3, int $readTimeout = 2): array
{
    $command = strtoupper(trim($command));

    $result = [
        'ok' => false,
        'command' => $command,
        'host' => $host,
        'port' => $port,
        'response' => '',
        'parsed' => null,
        'error' => '',
    ];

    if ($command === '') {
        $result['error'] = 'OP command is empty';
        return $result;
    }

    $errno = 0;
    $errstr = '';
    $client = @stream_socket_client(
        "tcp://{$host}:{$port}",
        $errno,
        $errstr,
        $connectTimeout,
        STREAM_CLIENT_CONNECT
    );

    if (!$client) {
        $result['error'] = "TCP connect failed: [{$errno}] {$errstr}";
        return $result;
    }

    try {
        stream_set_timeout($client, $readTimeout);

        $payload = $command;
        if (substr($payload, -1) !== "\n") {
            $payload .= "\n";
        }

        $written = fwrite($client, $payload);
        if ($written === false || $written <= 0) {
            $result['error'] = 'TCP write failed';
            return $result;
        }

        $response = '';
        while (!feof($client) && strlen($response) < 8192) {
            $chunk = fread($client, 1024);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $response .= $chunk;
            if (strpos($response, "\n") !== false) {
                break;
            }
        }

        $response = trim($response);
        $meta = stream_get_meta_data($client);
        if (!empty($meta['timed_out']) && $response === '') {
            $result['error'] = 'TCP read timeout';
            return $result;
        }

        $result['ok'] = true;
        $result['response'] = $response;
        $result['parsed'] = op_parse_return($response);
        return $result;
    } catch (Throwable $e) {
        $result['error'] = $e->getMessage();
        return $result;
    } finally {
        if (is_resource($client)) {
            fclose($client);
        }
    }
}

function op_parse_return(string $rawResponse): ?array
{
    $rawResponse = strtoupper(trim($rawResponse));

    if ($rawResponse === '') {
        return null;
    }

    if (preg_match('/^NTCS_RETURN_(\d+)_(.+)$/', $rawResponse, $matches)) {
        return [
            'type' => 'RETURN',
            'address' => (int)$matches[1],
            'value' => $matches[2],
            'raw' => $rawResponse,
        ];
    }

    if (preg_match('/^NTCS_([A-Z]+)_(.*)$/', $rawResponse, $matches)) {
        return [
            'type' => $matches[1],
            'address' => null,
            'value' => $matches[2],
            'raw' => $rawResponse,
        ];
    }

    return [
        'type' => 'UNKNOWN',
        'address' => null,
        'value' => null,
        'raw' => $rawResponse,
    ];
}

$host = getenv('OP_SERVER_IP') ?: '192.168.10.100';
$port = (int)(getenv('OP_SERVER_PORT') ?: 4545);
$action = strtolower($argv[1] ?? 'read');

switch ($action) {
    case 'read':
        $address = (int)($argv[2] ?? 4097);
        $command = "IDAS_READ_{$address}";
        break;

    case 'write':
        $address = (int)($argv[2] ?? 463);
        $value = strtoupper(trim((string)($argv[3] ?? '2')));
        $command = "IDAS_WRITE_{$address}_{$value}";
        break;

    case 'send':
        $command = (string)($argv[2] ?? 'IDAS_READ_4097');
        break;

    default:
        fwrite(STDERR, "Unknown action: {$action}\n");
        fwrite(STDERR, "Usage: php test_op.php read 4097 | write 463 2 | send IDAS_READ_4097\n");
        exit(1);
}

echo "OP TCP Client\n";
echo "Server : {$host}:{$port}\n";
echo "Command: " . strtoupper(trim($command)) . "\n";

$start = microtime(true);
$result = op_send($command, $host, $port);
$elapsed = round(microtime(true) - $start, 4);

echo "Time   : {$elapsed}s\n";
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

exit($result['ok'] ? 0 : 2);
