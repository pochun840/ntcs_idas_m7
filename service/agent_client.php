<?php

use Swoole\Coroutine;
use Swoole\Coroutine\Http\Client;
use function Swoole\Coroutine\run;


//1.idas_db, get server _ip
//2.data_db, send data
//3.sendlog_db, save last 10,000 send log and success or not

// $Year = date("Y");// data db 用西元年命名
// $data_db_name = "data".$Year.".db";
// $db_data = new PDO('sqlite:/var/www/html/database/'.$data_db_name); //鎖附結果DB

$db_iDas = new PDO('sqlite:/var/www/html/database/das.db'); //das設定DB

$result = $db_iDas->query("SELECT * FROM config WHERE config_name = 'agent_type' ");
$rows = $result->fetch(PDO::FETCH_ASSOC);
$agent_type = $rows['config_value'];

$result = $db_iDas->query("SELECT * FROM config WHERE config_name = 'agent_server_ip' ");
$rows = $result->fetch(PDO::FETCH_ASSOC);
$agent_ip = $rows['config_value'];

$db_iDas = null;
$result = null;

if ($agent_type == 2) {
    $agent_ip = '127.0.0.1';
}

define("AGENT_IP", $agent_ip);


run(function () {
    $client = new Client(AGENT_IP, 9501);
    $ret = $client->upgrade('/');//變成websocket
    while(true) {
        if ($ret) { // 確認是否連線成功
            while(true) {
                
                $message = GetLastResult();
                $res = $client->push($message);
                // var_dump($client->recv());
                // var_dump($res);
                // var_dump(date("Y-m-d H:i:s"));
                if( $res == false ) { //斷線時會在進到重連的步驟
                    $ret = false;
                    break;
                }

                Coroutine::sleep(1);//發送頻率 每秒1次
            }
        }else{ //連線不成功，每3秒會重試一次
            $ret = $client->upgrade('/');
            Coroutine::sleep(30);
        }
    }
});



function GetLastResult() {
    $row = [];

    if (file_exists('/home/kls/NTCS7/ntcs_data.db')) {
        $db_data = new PDO('sqlite:/home/kls/NTCS7/ntcs_data.db');
        $result = $db_data->query("SELECT * FROM ntcs_data ORDER BY id DESC LIMIT 1");

        $row = $result->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return json_encode(['message' => 'ntcs_data table is empty']);
        }

        if (file_exists('/var/www/html/database/ntcs_device_IDAS.db')) {
            $db_tcscon = new PDO('sqlite:/var/www/html/database/ntcs_device_IDAS.db');
            $result2 = $db_tcscon->query("SELECT * FROM ntcs_device_test");
            $device_info = $result2->fetch(PDO::FETCH_ASSOC);

            if ($device_info && isset($device_info['device_name'])) {
                $row['device_name'] = $device_info['device_name'];
            } else {
                $row['device_name'] = 'unknown';
            }

            $db_tcscon = null;
        }

        $row['client_ip'] = getIp();

        $db_data = null;
        return json_encode($row);
    } else {
        return json_encode(['message' => 'data db not found']);
    }
}



function getIp(){

    if( PHP_OS_FAMILY == 'Linux'){
        // $eth0Ip = '';
        // $eth0Ip = trim(shell_exec("/sbin/ip -o -4 addr list eth0 | awk '{print $4}' | cut -d/ -f1"));
        $Ips = trim(shell_exec("/sbin/ip -o -4 addr list  | awk '{print $4}' | cut -d/ -f1"));
        $Ip = explode(PHP_EOL, $Ips);
        
        return strtoupper($Ip[1]);
    }else{
        $host_addr= gethostname();
        $ip_addr = gethostbyname($host_addr);
        return strtoupper($ip_addr);
    }
}