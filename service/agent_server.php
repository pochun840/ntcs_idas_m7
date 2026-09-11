<?php
use Swoole\WebSocket\Server;

$server = new Server("0.0.0.0", 9501);

$server->set([
    'worker_num'               => 1,
    'max_request'              => 0,
    'dispatch_mode'            => 3,
    'package_max_length'       => 4 * 1024 * 1024,
]);

// Track the device represented by each 9501 connection.  The latest fd for
// an IP wins, preventing a late close from removing a newly reconnected row.
$connectionPortByFd = [];
$clientIpByFd = [];
$currentFdByClientIp = [];

// 安全檢查已升級為 WS
$wsReady = function (Server $server, int $fd): bool {
    if (method_exists($server, 'isEstablished')) {
        return $server->isEstablished($fd);
    }
    $info = $server->connection_info($fd);
    return is_array($info) && !empty($info);
};

// 取得 fd 所在的伺服器埠（9501 或 9502）
$getPort = function (Server $server, int $fd): int {
    $info = $server->connection_info($fd);
    return (int)($info['server_port'] ?? 0);
};

// 只對「相同埠」的連線廣播
$broadcastSamePort = function (Server $server, int $fromPort, string $msg) use ($wsReady, $getPort) {
    foreach ($server->connections as $cfd) {
        if (!$wsReady($server, $cfd)) continue;
        if ($getPort($server, $cfd) !== $fromPort) continue; // 🔴 過濾不同埠
        $server->push($cfd, $msg);
    }
};

/** open */
$onOpen = function (Server $server, $request) use ($wsReady, $broadcastSamePort, &$connectionPortByFd) {
    $fd   = $request->fd;
    $port = (int)($request->server['server_port'] ?? 0);
    $ip   = $request->server['remote_addr'] ?? 'unknown';
    $connectionPortByFd[$fd] = $port;

    if ($wsReady($server, $fd)) {
        $server->push($fd, "Welcome to the server! (fd=$fd, port=$port, ip=$ip)");
    }
    // ✅ 只通知同一個埠上的同行
    $broadcastSamePort($server, $port, "Client {$fd} connected on port {$port}");
};

/** message */

$onMessage = function (Server $server, $frame) use ($broadcastSamePort, &$clientIpByFd, &$currentFdByClientIp) {
    // 找出發話者所在的埠
    $info = $server->connection_info($frame->fd);
    $port = (int)($info['server_port'] ?? 0);

    if ($port === 9502) {
        // 9502：移除 "rows":[ 與結尾的 ]，只送出 rows 內部物件（以逗號串接）
        $data = json_decode($frame->data, true);

        if (json_last_error() === JSON_ERROR_NONE && isset($data['rows']) && is_array($data['rows'])) {
            $payload = implode(',', array_map(
                fn($r) => json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $data['rows']
            ));
            $broadcastSamePort($server, $port, $payload);
            return;
        }

        // 格式不是預期，就原樣轉發
        $broadcastSamePort($server, $port, (string)$frame->data);
        return;
    }

    if ($port === 9501) {
        $payload = json_decode((string)$frame->data, true);
        $clientIp = is_array($payload) ? trim((string)($payload['client_ip'] ?? '')) : '';
        $previousIp = (string)($clientIpByFd[$frame->fd] ?? '');

        // Local Server mode keeps the same loopback WebSocket connection while
        // the controller moves between Ethernet and Wi-Fi.  Explicitly retire
        // the previous advertised IP so the Agent page never keeps a stale row.
        if ($previousIp !== '' && $previousIp !== $clientIp
            && (($currentFdByClientIp[$previousIp] ?? null) === $frame->fd)) {
            unset($currentFdByClientIp[$previousIp]);
            $broadcastSamePort($server, 9501, json_encode([
                'agent_event' => ($clientIp === '' ? 'offline' : 'ip_changed'),
                'client_ip' => ($clientIp === '' ? $previousIp : $clientIp),
                'old_client_ip' => $previousIp,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        if ($clientIp !== '') {
            $clientIpByFd[$frame->fd] = $clientIp;
            $currentFdByClientIp[$clientIp] = $frame->fd;
        } else {
            unset($clientIpByFd[$frame->fd]);
        }
    }

    // 9501：保留 "Client X said: ..."
    $msg = "Client {$frame->fd} said: {$frame->data}";
    $broadcastSamePort($server, $port, $msg);
};


/** close */
$onClose = function (Server $server, $fd) use (
    $broadcastSamePort,
    &$connectionPortByFd,
    &$clientIpByFd,
    &$currentFdByClientIp
) {
    $port = (int)($connectionPortByFd[$fd] ?? 0);
    $clientIp = (string)($clientIpByFd[$fd] ?? '');

    unset($connectionPortByFd[$fd], $clientIpByFd[$fd]);

    if ($port === 9501 && $clientIp !== ''
        && (($currentFdByClientIp[$clientIp] ?? null) === $fd)) {
        unset($currentFdByClientIp[$clientIp]);
        $broadcastSamePort($server, 9501, json_encode([
            'agent_event' => 'offline',
            'client_ip'   => $clientIp,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
};

// 綁定到主埠（9501）
$server->on('open', $onOpen);
$server->on('message', $onMessage);
$server->on('close', $onClose);

// 第二個 WebSocket 埠：9502
$port2 = $server->listen('0.0.0.0', 9502, SWOOLE_SOCK_TCP);
$port2->set([
    'open_http_protocol'      => true,
    'open_websocket_protocol' => true,
]);

// 也用同一組 handler（已經有同埠過濾了）
$port2->on('open', $onOpen);
$port2->on('message', $onMessage);
$port2->on('close', $onClose);

$server->start();
