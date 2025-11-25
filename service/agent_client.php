<?php
declare(strict_types=1);

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use function Swoole\Coroutine\run;

/* 兼容：若沒定義 WebSocket opcode 常數就補上 */
defined('WEBSOCKET_OPCODE_TEXT') || define('WEBSOCKET_OPCODE_TEXT', 1);
defined('WEBSOCKET_OPCODE_PING') || define('WEBSOCKET_OPCODE_PING', 9);
defined('WEBSOCKET_OPCODE_PONG') || define('WEBSOCKET_OPCODE_PONG', 10);
defined('SOCKET_ETIMEDOUT')      || define('SOCKET_ETIMEDOUT', 110);

/* ===== 讀取 iDAS 設定，決定 AGENT_IP ===== */
$db_iDas = new PDO('sqlite:/var/www/html/database/das.db');
$db_iDas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$agent_type = (int)($db_iDas->query("SELECT config_value FROM config WHERE config_name='agent_type'")
                 ->fetchColumn() ?? 1);
$agent_ip   = (string)($db_iDas->query("SELECT config_value FROM config WHERE config_name='agent_server_ip'")
                 ->fetchColumn() ?? '127.0.0.1');
$db_iDas = null;


if ($agent_type === 2) { // local 模式
    $agent_ip = '127.0.0.1';
}

define('AGENT_IP', $agent_ip);

/* ===== 共用參數 ===== */
const SEND_INTERVAL_SEC = 1;   // 每秒送一次 payload
const PING_INTERVAL_SEC = 20;  //
const BACKOFF_MAX       = 15;  // 最大重連回退秒數

/* ===== 連線＋送資料的通用函式 ===== */
function connectAndStream(string $host, int $port, callable $payloadFn, ?callable $onServerMsg = null, ?callable $onLog = null): void
{
    $stop = false;
    if (function_exists('pcntl_async_signals')) {
        pcntl_async_signals(true);
        pcntl_signal(SIGINT,  function() use (&$stop){ $stop = true; });
        pcntl_signal(SIGTERM, function() use (&$stop){ $stop = true; });
    }
    $log = $onLog ?? function(string $level, string $msg) use ($port) {
        error_log(sprintf('[%s][%d] %s', strtoupper($level), $port, $msg));
    };

    $backoff   = 1;
    while (!$stop) {
        $client = new Client($host, $port, false);
        if (method_exists($client, 'set')) {
            $client->set([
                'timeout'         => 5.0,
                'connect_timeout' => 5.0,
                'keep_alive'      => false,
            ]);
        }

        $ok = $client->upgrade('/');
        if (!$ok) {
            $log('error', sprintf('upgrade failed errCode=%d', (int)$client->errCode));
            $client->close();
            Coroutine::sleep($backoff);
            $backoff = min($backoff * 2, BACKOFF_MAX);
            continue;
        }

        $log('info', 'connected');
        $backoff = 1;

        // reader：避免 server 推訊息時堆積阻塞
        $reader = Coroutine::create(function() use ($client, $onServerMsg, $log, &$stop) {
            while (!$stop && $client->connected) {
                $frame = $client->recv(1.0);
                if ($frame === false) {
                    if ($client->errCode && $client->errCode !== SOCKET_ETIMEDOUT) {
                        $log('error', sprintf('recv error errCode=%d', (int)$client->errCode));
                        break;
                    }
                    continue;
                }
                if ($frame === null) {
                    $log('info', 'server closed connection');
                    break;
                }
                // 處理 server 訊息（TEXT / PING / 等）
                $opcode = $frame->opcode ?? WEBSOCKET_OPCODE_TEXT;
                if ($opcode === WEBSOCKET_OPCODE_PING) {
                    $client->push('', WEBSOCKET_OPCODE_PONG);
                } elseif ($opcode === WEBSOCKET_OPCODE_TEXT) {
                    if ($onServerMsg) { ($onServerMsg)($frame->data); }
                    // $log('info', 'server says: ' . $frame->data);
                }
            }
        });

        // writer：定時送 
        $lastPingAt = microtime(true);
        while (!$stop && $client->connected) {
            // 心跳
            if ((microtime(true) - $lastPingAt) >= PING_INTERVAL_SEC) {
                $client->push('', WEBSOCKET_OPCODE_PING);
                $lastPingAt = microtime(true);
            }

            // 取得 payload（字串）
            $payload = (string)($payloadFn() ?? '');
            if ($payload === '') {
                $payload = json_encode(['message' => 'empty payload'], JSON_UNESCAPED_UNICODE);
            }

            $ok = $client->push($payload, WEBSOCKET_OPCODE_TEXT, true);
            if (!$ok) {
                $log('error', sprintf('push failed errCode=%d', (int)$client->errCode));
                break;
            }

            Coroutine::sleep(SEND_INTERVAL_SEC);
        }

        if ($client->connected) { $client->close(); }
        Coroutine::sleep($backoff);
        $backoff = min($backoff * 2, BACKOFF_MAX);
    }

    $log('info', 'stream terminated');
}

/* ===== 你的資料來源 #1：DB 最新一筆 ===== */
function GetLastResult(): string {
    if (!file_exists('/home/kls/NTCS7/ntcs_data.db')) {
        return json_encode(['message' => 'data db not found'], JSON_UNESCAPED_UNICODE);
    }

    try {
        $db = new PDO('sqlite:/home/kls/NTCS7/ntcs_data.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $row = $db->query("SELECT * FROM ntcs_data ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $db = null;
        if (!$row) return json_encode(['message' => 'ntcs_data table is empty'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        return json_encode(['message' => 'db error', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    // 裝置名稱（可快取）
    static $device_name = null;
    if ($device_name === null && file_exists('/var/www/html/database/ntcs_device_temp.db')) {
        try {
            $d = new PDO('sqlite:/var/www/html/database/ntcs_device_temp.db');
            $device_name = (string)($d->query("SELECT device_name FROM ntcs_device_test")->fetchColumn() ?? 'unknown');
            $d = null;
        } catch (Throwable $e) {
            $device_name = 'unknown';
        }
    }
    $row['device_name'] = $device_name ?? 'unknown';
    $row['client_ip']   = getIp();

    return json_encode($row, JSON_UNESCAPED_UNICODE);
}






/* ===== 你的資料來源 #2：CSV 自定義 ===== */
function csvNoHeaderToJson(){

    $csvPath = '/var/www/html/temp/customize.csv';
    
    if (!is_file($csvPath)) {
        return json_encode(['error' => 'csv not found'], JSON_UNESCAPED_UNICODE);
    }
    $fp = @fopen($csvPath, 'r');
    if (!$fp) {
        return json_encode(['error' => 'cannot open csv'], JSON_UNESCAPED_UNICODE);
    }

    $dbh = new PDO('sqlite:/home/kls/NTCS7/ntcs_data.db');
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $res = $dbh->query("SELECT * FROM ntcs_data ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];


    $rows = [];
    $isFirst = true;

    while (($cols = fgetcsv($fp)) !== false) {
        // 去除第一格可能的 UTF-8 BOM
        if ($isFirst && isset($cols[0])) {
            $cols[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$cols[0]);
            $isFirst = false;
        }

        // 取欄位（沒有就補空字串）
        $noRaw     = isset($cols[0]) ? trim((string)$cols[0]) : '';
        $readRaw   = isset($cols[1]) ? trim((string)$cols[1]) : '';
        $resultRaw = isset($cols[3]) ? trim((string)$cols[3]) : '';

        // 略過全空行
        if ($noRaw === '' && $readRaw === '' && $resultRaw === '') {
            continue;
        }

        // ——— 偵測並略過表頭（不分大小寫）———
        $looksHeader =
            preg_match('/^no$/i', $noRaw) ||
            preg_match('/^read\s*position$/i', $readRaw) ||
            preg_match('/^result$/i', $resultRaw);
        if ($looksHeader) {
            continue; // 直接跳過表頭
        }

        // read_position：去掉開頭的 "#<數字>"（空白可有可無）
        // 例： "#36 threshold_angle"、"#36threshold_angle"、"# 36   threshold_angle" → "threshold_angle"
        $read = preg_replace('/^\s*#\s*\d+\s*/u', '', $readRaw);

        // no 盡量轉數字
        $no = ctype_digit($noRaw) ? (int)$noRaw : $noRaw;

        $rows[] = [
            'no'             => $no,
            'read_position'  => $read,
            'result'         => $res[$read],
        ];
    }

    fclose($fp);
    return json_encode(['rows' => $rows], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}



/* ===== 取得本機 IP（盡量非 127.0.0.1） ===== */
function getIp(): string {
    if (PHP_OS_FAMILY === 'Linux') {
        $Ips = trim(shell_exec("/sbin/ip -o -4 addr list | awk '{print \$4}' | cut -d/ -f1"));
        $Ip  = explode(PHP_EOL, $Ips);
        foreach ($Ip as $candidate) {
            $candidate = trim($candidate);
            if ($candidate && $candidate !== '127.0.0.1') return strtoupper($candidate);
        }
        return '127.0.0.1';
    }
    return strtoupper(gethostbyname(gethostname()));
}

/* ===== 啟動兩個連線：9501 與 9502 ===== */
run(function () {
    // 9501：送 DB 最新一筆
    Coroutine::create(function() {
        connectAndStream(
            AGENT_IP,
            9501,
            fn() => GetLastResult(),
            null,
            fn($lvl,$msg) => error_log(sprintf('[9501][%s] %s', strtoupper($lvl), $msg))
        );
    });

    // 9502：送 customize.csv
    Coroutine::create(function() {
        connectAndStream(
            AGENT_IP,
            9502,
            fn() => csvNoHeaderToJson(),
            null,
            fn($lvl,$msg) => error_log(sprintf('[9502][%s] %s', strtoupper($lvl), $msg))
        );
    });

    // 主協程留著即可（或做其它事）
    while (true) { Coroutine::sleep(5); }
});
