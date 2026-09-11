<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/paths.php';

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use function Swoole\Coroutine\run;

/* 兼容：若沒定義 WebSocket opcode 常數就補上 */
defined('WEBSOCKET_OPCODE_TEXT') || define('WEBSOCKET_OPCODE_TEXT', 1);
defined('WEBSOCKET_OPCODE_PING') || define('WEBSOCKET_OPCODE_PING', 9);
defined('WEBSOCKET_OPCODE_PONG') || define('WEBSOCKET_OPCODE_PONG', 10);
defined('SOCKET_ETIMEDOUT')      || define('SOCKET_ETIMEDOUT', 110);

/* ===== 讀取 iDAS 設定，決定 AGENT_IP ===== */
$db_iDas = idas_sqlite_connect(idas_path('database_root', 'das.db'));
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
define('AGENT_TYPE_AT_START', $agent_type);

/* ===== 共用參數 ===== */
const SEND_INTERVAL_SEC = 1;   // 每秒送一次 payload
const PING_INTERVAL_SEC = 20;  //
const BACKOFF_MAX       = 15;  // 最大重連回退秒數
const AGENT_FACTORY_DEFAULT_IPV4 = '192.168.7.7'; // 出廠預設備援 IP，不顯示於 Agent

/*
 * 9501 / 9502 are two coroutines in the same process.  Register the process
 * signal handler only once so stopping Agent terminates both connections.
 */
$GLOBALS['agent_stop_requested'] = false;
if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGINT, static function (): void {
        $GLOBALS['agent_stop_requested'] = true;
    });
    pcntl_signal(SIGTERM, static function (): void {
        $GLOBALS['agent_stop_requested'] = true;
    });
}

function agentStopRequested(): bool
{
    return !empty($GLOBALS['agent_stop_requested']);
}

function agentModeStillMatches(): bool
{
    try {
        $db = idas_sqlite_connect(idas_path('database_root', 'das.db'));
        $currentType = (int)($db->query(
            "SELECT config_value FROM config WHERE config_name='agent_type'"
        )->fetchColumn() ?? -1);
        $db = null;
        return $currentType === (int)AGENT_TYPE_AT_START;
    } catch (Throwable $e) {
        // A temporary DB read failure must not tear down a healthy connection.
        return true;
    }
}

function agentInterruptibleSleep(float $seconds): void
{
    $remaining = max(0.0, $seconds);
    while ($remaining > 0.0 && !agentStopRequested()) {
        $slice = min(0.2, $remaining);
        Coroutine::sleep($slice);
        $remaining -= $slice;
    }
}

/* ===== 連線＋送資料的通用函式 ===== */
function connectAndStream(string $host, int $port, callable $payloadFn, ?callable $onServerMsg = null, ?callable $onLog = null): void
{
    $log = $onLog ?? function(string $level, string $msg) use ($port) {
        error_log(sprintf('[%s][%d] %s', strtoupper($level), $port, $msg));
    };

    $backoff   = 1;
    while (!agentStopRequested()) {
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
            agentInterruptibleSleep($backoff);
            $backoff = min($backoff * 2, BACKOFF_MAX);
            continue;
        }

        $log('info', 'connected');
        $backoff = 1;

        // reader：避免 server 推訊息時堆積阻塞
        $reader = Coroutine::create(function() use ($client, $onServerMsg, $log) {
            while (!agentStopRequested() && $client->connected) {
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
        while (!agentStopRequested() && $client->connected) {
            // If Client was changed to Server/None without a successful stop,
            // terminate this old process so it cannot remain on the old server.
            if (!agentModeStillMatches()) {
                $GLOBALS['agent_stop_requested'] = true;
                break;
            }

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

            agentInterruptibleSleep(SEND_INTERVAL_SEC);
        }

        if ($client->connected) { $client->close(); }
        agentInterruptibleSleep($backoff);
        $backoff = min($backoff * 2, BACKOFF_MAX);
    }

    $log('info', 'stream terminated');
}

/* ===== 你的資料來源 #1：DB 最新一筆 ===== */
function GetLastResult(): string {
    if (!file_exists(idas_path('controller_root', 'ntcs_data.db'))) {
        return json_encode(['message' => 'data db not found'], JSON_UNESCAPED_UNICODE);
    }

    try {
        $db = idas_sqlite_connect(idas_path('controller_root', 'ntcs_data.db'));
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $row = $db->query("SELECT * FROM ntcs_data ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $db = null;
        if (!$row) return json_encode(['message' => 'ntcs_data table is empty'], JSON_UNESCAPED_UNICODE);
    } catch (Throwable $e) {
        return json_encode(['message' => 'db error', 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }

    // 裝置名稱
    try {
        $d = idas_sqlite_connect(idas_path('controller_root', 'ntcs_device.db'));
        $device_name = (string)($d->query("SELECT device_name FROM ntcs_device_test")->fetchColumn() ?? 'unknown');
        $d = null;
    } catch (Throwable $e) {
        $device_name = 'unknown';
    }


    $row['device_name'] = $device_name ?? 'unknown';
    $row['client_ip']   = getIp();

    return json_encode($row, JSON_UNESCAPED_UNICODE);
}






/* ===== 你的資料來源 #2：CSV 自定義 ===== */
function csvNoHeaderToJson(){

    $csvPath = idas_path('temp_root', 'customize.csv');
    
    if (!is_file($csvPath)) {
        return json_encode(['error' => 'csv not found'], JSON_UNESCAPED_UNICODE);
    }
    $fp = @fopen($csvPath, 'r');
    if (!$fp) {
        return json_encode(['error' => 'cannot open csv'], JSON_UNESCAPED_UNICODE);
    }

    $dbh = idas_sqlite_connect(idas_path('controller_root', 'ntcs_data.db'));
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



/* ===== 取得本機實際使用中的 IPv4 ===== */
function isUsableAgentIpv4(string $candidate): bool
{
    if (!filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return false;
    }

    return strpos($candidate, '127.') !== 0
        && strpos($candidate, '169.254.') !== 0
        && $candidate !== '0.0.0.0'
        && $candidate !== AGENT_FACTORY_DEFAULT_IPV4;
}

function isValidAgentInterfaceName(string $interface): bool
{
    return $interface !== ''
        && preg_match('/^[A-Za-z0-9_.:@-]+$/', $interface) === 1
        && preg_match('/^(lo|docker\d*|br-|veth|virbr|tun|tap)/i', $interface) !== 1;
}

function agentIpv4FromRoute(string $routeOutput): string
{
    if (preg_match('/\bsrc\s+([0-9]{1,3}(?:\.[0-9]{1,3}){3})\b/', $routeOutput, $matches)) {
        $candidate = trim((string)$matches[1]);
        if (isUsableAgentIpv4($candidate)) {
            return $candidate;
        }
    }

    return '';
}

function agentRouteInterface(string $routeLine): string
{
    if (preg_match('/\bdev\s+([^\s]+)/', $routeLine, $matches)) {
        $interface = preg_replace('/@.*$/', '', trim((string)$matches[1]));
        return isValidAgentInterfaceName($interface) ? $interface : '';
    }

    return '';
}

function agentRouteMetric(string $routeLine): int
{
    if (preg_match('/\bmetric\s+(\d+)\b/i', $routeLine, $matches)) {
        return (int)$matches[1];
    }

    return 0;
}

/**
 * Read a tiny sysfs value directly first.  This avoids depending on shell_exec
 * for carrier/operstate, which is important on controller images that restrict
 * PHP shell commands.
 */
function agentReadSysfs(string $path, ?callable $run = null): string
{
    $value = @file_get_contents($path);
    if (is_string($value)) {
        return trim($value);
    }

    if ($run !== null) {
        return trim((string)$run('cat ' . escapeshellarg($path) . ' 2>/dev/null'));
    }

    return '';
}

function agentInterfaceCarrierState(string $interface, callable $run): ?bool
{
    if (!isValidAgentInterfaceName($interface)) {
        return false;
    }

    $carrier = agentReadSysfs('/sys/class/net/' . $interface . '/carrier', $run);
    if ($carrier === '1') {
        return true;
    }
    if ($carrier === '0') {
        return false;
    }

    return null;
}

function agentInterfaceOperState(string $interface, callable $run): string
{
    if (!isValidAgentInterfaceName($interface)) {
        return '';
    }

    return strtolower(agentReadSysfs('/sys/class/net/' . $interface . '/operstate', $run));
}

function agentInterfaceIsWireless(string $interface, callable $run): bool
{
    if (!isValidAgentInterfaceName($interface)) {
        return false;
    }

    // sysfs is the most reliable and does not require optional iw tools.
    if (is_dir('/sys/class/net/' . $interface . '/wireless')
        || is_link('/sys/class/net/' . $interface . '/phy80211')) {
        return true;
    }

    // Fallback for test/minimal systems where sysfs detection is unavailable.
    $probe = trim((string)$run(
        '[ -d ' . escapeshellarg('/sys/class/net/' . $interface . '/wireless')
        . ' ] && echo 1 || true'
    ));
    if ($probe === '1') {
        return true;
    }

    $iwDev = trim((string)$run(
        'command -v iw >/dev/null 2>&1 && iw dev ' . escapeshellarg($interface) . ' info 2>/dev/null'
    ));
    return $iwDev !== '';
}

/**
 * Return true/false when Wi-Fi association can be positively determined,
 * otherwise null.  Do not treat "interface UP" as Wi-Fi connected.
 */
function agentWirelessAssociationState(string $interface, callable $run): ?bool
{
    if (!isValidAgentInterfaceName($interface)) {
        return false;
    }

    $iwAvailable = trim((string)$run('command -v iw 2>/dev/null')) !== '';
    if ($iwAvailable) {
        $iwOutput = trim((string)$run(
            'iw dev ' . escapeshellarg($interface) . ' link 2>/dev/null'
        ));
        if (preg_match('/\bNot connected\b/i', $iwOutput)) {
            return false;
        }
        if (preg_match('/\bConnected to\b/i', $iwOutput)) {
            return true;
        }
    }

    $iwgetidAvailable = trim((string)$run('command -v iwgetid 2>/dev/null')) !== '';
    if ($iwgetidAvailable) {
        $ssid = trim((string)$run(
            'iwgetid ' . escapeshellarg($interface) . ' --raw 2>/dev/null'
        ));
        return $ssid !== '';
    }

    $wpaCliAvailable = trim((string)$run('command -v wpa_cli 2>/dev/null')) !== '';
    if ($wpaCliAvailable) {
        $status = (string)$run(
            'wpa_cli -i ' . escapeshellarg($interface) . ' status 2>/dev/null'
        );
        if (preg_match('/^wpa_state=COMPLETED$/mi', $status)) {
            return true;
        }
        if (preg_match('/^wpa_state=(?:DISCONNECTED|INACTIVE|SCANNING|INTERFACE_DISABLED)$/mi', $status)) {
            return false;
        }
    }

    return null;
}

function isAgentInterfaceActive(string $interface, callable $run, string $ipBinary): bool
{
    if (!isValidAgentInterfaceName($interface)) {
        return false;
    }

    $carrierState = agentInterfaceCarrierState($interface, $run);
    if ($carrierState === false) {
        return false;
    }

    $operState = agentInterfaceOperState($interface, $run);
    if (in_array($operState, ['down', 'lowerlayerdown', 'notpresent'], true)) {
        return false;
    }

    $linkOutput = trim((string)$run(
        $ipBinary . ' -o link show dev ' . escapeshellarg($interface) . ' 2>/dev/null'
    ));
    if ($linkOutput !== '' && (
        preg_match('/\bNO-CARRIER\b/', $linkOutput)
        || preg_match('/\bstate\s+DOWN\b/i', $linkOutput)
    )) {
        return false;
    }

    $isWireless = agentInterfaceIsWireless($interface, $run);
    if ($isWireless) {
        $associationState = agentWirelessAssociationState($interface, $run);

        // Wi-Fi is only active when association/carrier is real.  This is the
        // key fix for a removed Wi-Fi connection that leaves wlan0, its IPv4,
        // and even a default route behind for a while.
        if ($associationState === false) {
            return false;
        }
        if ($associationState === true) {
            return true;
        }

        // Optional Wi-Fi tools may not exist on the controller image.  In that
        // case require kernel carrier + a non-dormant operational state. Never
        // fall back to merely "state UP/UNKNOWN" for wireless interfaces.
        if ($carrierState === true) {
            return !in_array($operState, ['dormant', 'down', 'lowerlayerdown', 'notpresent'], true);
        }

        return false;
    }

    // Wired interface: carrier is decisive when available.
    if ($carrierState === true) {
        return true;
    }

    // Compatibility fallback for unusual/minimal kernels without carrier.
    return $linkOutput !== ''
        && preg_match('/\bLOWER_UP\b/', $linkOutput) === 1
        && preg_match('/\bstate\s+UP\b/i', $linkOutput) === 1;
}

function agentInterfaceIpv4(string $interface, callable $run, string $ipBinary): string
{
    if (!isValidAgentInterfaceName($interface)) {
        return '';
    }

    $addressOutput = (string)$run(
        $ipBinary . ' -o -4 addr show dev ' . escapeshellarg($interface)
        . ' scope global 2>/dev/null'
    );

    if (preg_match_all(
        '/\binet\s+([0-9]{1,3}(?:\.[0-9]{1,3}){3})\//',
        $addressOutput,
        $matches
    )) {
        foreach ($matches[1] as $candidate) {
            $candidate = trim((string)$candidate);
            if (isUsableAgentIpv4($candidate)) {
                return $candidate;
            }
        }
    }

    return '';
}

function agentSortedDefaultRouteLines(string $routeOutput): array
{
    $lines = preg_split('/\r?\n/', trim($routeOutput)) ?: [];
    $routes = [];

    foreach ($lines as $index => $line) {
        $line = trim((string)$line);
        if ($line === '') {
            continue;
        }
        $routes[] = [
            'line' => $line,
            'metric' => agentRouteMetric($line),
            'index' => (int)$index,
        ];
    }

    usort($routes, static function (array $a, array $b): int {
        if ($a['metric'] === $b['metric']) {
            return $a['index'] <=> $b['index'];
        }
        return $a['metric'] <=> $b['metric'];
    });

    return array_column($routes, 'line');
}

function agentIpFromRouteLine(string $routeLine, callable $run, string $ipBinary): string
{
    $interface = agentRouteInterface($routeLine);
    if ($interface === '' || !isAgentInterfaceActive($interface, $run, $ipBinary)) {
        return '';
    }

    $candidate = agentIpv4FromRoute($routeLine);
    if ($candidate !== '') {
        // A stale route can still contain the old Wi-Fi src.  Verify that the
        // source address is still attached to this *active* interface.
        $addresses = (string)$run(
            $ipBinary . ' -o -4 addr show dev ' . escapeshellarg($interface)
            . ' scope global 2>/dev/null'
        );
        if (preg_match('/\binet\s+' . preg_quote($candidate, '/') . '\//', $addresses)) {
            return strtoupper($candidate);
        }
    }

    $candidate = agentInterfaceIpv4($interface, $run, $ipBinary);
    return $candidate !== '' ? strtoupper($candidate) : '';
}

function getIp(?callable $commandRunner = null): string
{
    if (PHP_OS_FAMILY !== 'Linux') {
        $candidate = strtoupper((string)gethostbyname(gethostname()));
        return isUsableAgentIpv4($candidate) ? $candidate : '';
    }

    $run = $commandRunner ?? static function (string $command): string {
        $output = @shell_exec($command);
        return is_string($output) ? $output : '';
    };

    $ipBinary = is_executable('/sbin/ip')
        ? '/sbin/ip'
        : (is_executable('/usr/sbin/ip') ? '/usr/sbin/ip' : 'ip');
    $serverIp = defined('AGENT_IP') ? trim((string)AGENT_IP) : '';

    // Client mode: ask the kernel which interface/source would reach the
    // configured Agent server. The configured server IP is a destination only;
    // it is never copied into client_ip.
    if (filter_var($serverIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
        && strpos($serverIp, '127.') !== 0) {
        $routeToServer = trim((string)$run(
            $ipBinary . ' -o -4 route get ' . escapeshellarg($serverIp) . ' 2>/dev/null'
        ));
        if ($routeToServer !== '') {
            $candidate = agentIpFromRouteLine($routeToServer, $run, $ipBinary);
            if ($candidate !== '') {
                return $candidate;
            }
        }
    }

    // Server mode connects locally through 127.0.0.1. Choose the active LAN
    // interface from default routes. Stale routes are harmless because every
    // route is revalidated against carrier/association before its IP is used.
    $defaultRoutes = (string)$run($ipBinary . ' -o -4 route show default 2>/dev/null');
    foreach (agentSortedDefaultRouteLines($defaultRoutes) as $routeLine) {
        $candidate = agentIpFromRouteLine($routeLine, $run, $ipBinary);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    // Some controller networks have no default gateway. Inspect all global IPv4
    // addresses, but accept only interfaces whose physical link is active.
    $addresses = (string)$run($ipBinary . ' -o -4 addr show scope global 2>/dev/null');
    $candidates = [];
    if (preg_match_all(
        '/^\d+:\s+([^\s]+)\s+inet\s+([0-9]{1,3}(?:\.[0-9]{1,3}){3})\//m',
        $addresses,
        $matches,
        PREG_SET_ORDER
    )) {
        $routeMetrics = [];
        foreach (agentSortedDefaultRouteLines($defaultRoutes) as $routeLine) {
            $routeInterface = agentRouteInterface($routeLine);
            if ($routeInterface !== '' && !isset($routeMetrics[$routeInterface])) {
                $routeMetrics[$routeInterface] = agentRouteMetric($routeLine);
            }
        }

        foreach ($matches as $index => $match) {
            $interface = preg_replace('/@.*$/', '', (string)$match[1]);
            $candidate = trim((string)$match[2]);
            if (!isValidAgentInterfaceName($interface)
                || !isUsableAgentIpv4($candidate)
                || !isAgentInterfaceActive($interface, $run, $ipBinary)) {
                continue;
            }

            $candidates[] = [
                'ip' => $candidate,
                'metric' => $routeMetrics[$interface] ?? PHP_INT_MAX,
                'index' => (int)$index,
            ];
        }
    }

    if ($candidates) {
        usort($candidates, static function (array $a, array $b): int {
            if ($a['metric'] === $b['metric']) {
                return $a['index'] <=> $b['index'];
            }
            return $a['metric'] <=> $b['metric'];
        });
        return strtoupper((string)$candidates[0]['ip']);
    }

    // No usable physical LAN link is active.  Send an empty client_ip so the
    // Agent server retires the old row instead of keeping the previous Wi-Fi IP.
    return '';
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
    while (!agentStopRequested()) { agentInterruptibleSleep(0.2); }
});
