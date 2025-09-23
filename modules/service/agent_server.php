<?php
use Swoole\WebSocket\Server;

$server = new Server("0.0.0.0", 9501);

$server->set([
    'worker_num'               => 2,
    'max_request'              => 0,
    'dispatch_mode'            => 3,
    'heartbeat_check_interval' => 30,
    'heartbeat_idle_time'      => 60,
    'package_max_length'       => 4 * 1024 * 1024,
]);

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
$onOpen = function (Server $server, $request) use ($wsReady, $broadcastSamePort) {
    $fd   = $request->fd;
    $port = (int)($request->server['server_port'] ?? 0);
    $ip   = $request->server['remote_addr'] ?? 'unknown';

    if ($wsReady($server, $fd)) {
        $server->push($fd, "Welcome to the server! (fd=$fd, port=$port, ip=$ip)");
    }
    // ✅ 只通知同一個埠上的同行
    $broadcastSamePort($server, $port, "Client {$fd} connected on port {$port}");
};

/** message */
$onMessage = function (Server $server, $frame) use ($wsReady, $broadcastSamePort) {
    // 找出發話者所在的埠
    $info = $server->connection_info($frame->fd);
    $port = (int)($info['server_port'] ?? 0);
    $msg  = "Client {$frame->fd} said: {$frame->data}";
    // ✅ 只廣播給同埠
    $broadcastSamePort($server, $port, $msg);
};

/** close */
$onClose = function (Server $server, $fd) use ($wsReady, $broadcastSamePort) {
    // 找出關閉者所在的埠
    $info = $server->connection_info($fd);
    $port = (int)($info['server_port'] ?? 0);
    // ✅ 只通知同埠
    $broadcastSamePort($server, $port, "Client {$fd} disconnected");
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
