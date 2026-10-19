<?php

/**
 * 高效率 TCP 字串傳送函式（防止連線卡頓與讀取阻塞）
 *
 * @param string $host       Server 的 IP 或網域（建議直接用 IP）
 * @param int    $port       Server 的 Port
 * @param string $data       要傳送的字串內容
 * @param int    $connectTimeout 連線逾時（秒）
 * @param int    $readTimeout    讀取回應逾時（秒）
 * @return string|bool       成功時傳回 Server 回傳的字串（若 Server 無回應則傳回空字串），失敗傳回 false
 */
function sendTcpMessageOptimized(string $host, int $port, string $data, int $connectTimeout = 3, int $readTimeout = 2) {
    // 使用 STREAM_CLIENT_PERSISTENT 啟用長連線，複用連線能大幅提升速度
    $remoteSocket = "tcp://{$host}:{$port}";
    $errno = 0;
    $errstr = '';

    $client = @stream_socket_client(
        $remoteSocket, 
        $errno, 
        $errstr, 
        $connectTimeout, 
        STREAM_CLIENT_CONNECT | STREAM_CLIENT_PERSISTENT
    );

    if (!$client) {
        error_log("TCP 連線失敗: [{$errno}] {$errstr}");
        return false;
    }

    // 確保訊息有結尾換行符（根據你的 Server 協定調整，通常需要 \n 或 \r\n）
    if (substr($data, -1) !== "\n") {
        $data .= "\n";
    }

    // 寫入資料
    $fwrite = fwrite($client, $data);
    if ($fwrite === false) {
        error_log("TCP 寫入資料失敗");
        return false;
    }

    // 設定「讀取資料」的逾時時間，避免 Server 不回應時持續卡住
    stream_set_timeout($client, $readTimeout);

    // 讀取 Server 回應（改用 fread 只讀取前 8192 bytes，速度極快）
    $response = fread($client, 8192);

    // 檢查讀取是否逾時
    $info = stream_get_meta_data($client);
    if ($info['timed_out']) {
        error_log("TCP 讀取回應逾時（但資料可能已成功送出）");
        // 如果你的應用不在乎 Server 的回傳值，這裡也可以直接 return "";
        return false; 
    }

    // 注意：因為開啟了長連線（PERSISTENT），這裡「不要」呼叫 fclose($client)，讓連線保持活著供下一次使用。
    return $response;
}

// ==================== 測試執行 ====================

$serverIp = '192.168.0.75'; // 建議直接用 IP，避免 DNS 解析耗時
$serverPort = 4545;
$message = 'IDAS_READ_4097';

echo "開始傳送...\n";
$startTime = microtime(true);

$result = sendTcpMessageOptimized($serverIp, $serverPort, $message);

$endTime = microtime(true);
$executionTime = round(($endTime - $startTime), 4);
echo "執行花費時間: {$executionTime} 秒\n";

if ($result !== false) {
    echo "傳送成功！\n";
    echo "Server 回傳內容: " . $result . "\n";
} else {
    echo "傳送失敗，請檢查網路、防火牆或 Server 狀態。\n";
}