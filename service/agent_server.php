<?php
// 建立 WebSocket 伺服器
$server = new swoole_websocket_server("0.0.0.0", 9501);

//避免memory leak
$server->set(array(
    'worker_num' => 2,    //开启两个worker进程 
    'max_request' => 2,   //每个worker进程max request设置为2次 
    'dispatch_mode'=>3,
));

// 處理連接事件
$server->on('open', function ($server, $request) {
    // 當有客戶端連接時
    // echo "Client connected: {$request->fd}\n";
    
    // 在伺服器端保存已連線的客戶端資訊，您可以使用陣列、資料庫或其他方式來管理
    // 這裡使用一個簡單的陣列
    $server->userList[$request->fd] = $request->fd;
    
    // 發送歡迎訊息給新連接的客戶端
    $server->push($request->fd, "Welcome to the server!");
    
    // 向所有客戶端廣播新客戶端已連接的訊息
    foreach ($server->connections as $fd) {
        $server->push($fd, "Client {$request->fd} connected");
        // var_dump($fd);
        $info = $server->getClientInfo($fd);
        // var_dump($info);

    }
    // var_dump($server->connections);

});

// 處理訊息事件
$server->on('message', function ($server, $frame) {
    // 當收到客戶端傳來的訊息時
    // echo "Received message: {$frame->data}\n";
    
    // 在這裡您可以根據訊息進行相應處理
    
    // 向所有客戶端廣播訊息
    foreach ($server->connections as $fd) {
        $server->push($fd, "Client {$frame->fd} said: {$frame->data}");
    }
});

// 處理關閉事件
$server->on('close', function ($server, $fd) {
    // 當有客戶端斷開連接時
    // echo "Client disconnected: {$fd}\n";
    
    // 在伺服器端刪除已斷開連接的客戶端資訊
    unset($server->userList[$fd]);
    
    // 向所有客戶端廣播客戶端已斷開連接的訊息
    foreach ($server->connections as $fd) {
        $server->push($fd, "Client {$fd} disconnected");
    }
});

// 啟動伺服器
$server->start();
