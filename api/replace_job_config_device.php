<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/service/JobConfigNetworkService.php';
require_once dirname(__DIR__) . '/app/config/paths.php';
function deviceControlReply(int $status,array $data): void {header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');http_response_code($status);echo json_encode($data,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);exit;}
function callDeviceControlApi(string $url, string $action): array
{
    $body = json_encode([($action === 'disable' ? 'disable_flag' : 'enable_flag') => 1]);
    if ($body === false) throw new RuntimeException('Cannot encode device control request');
    if (function_exists('curl_init')) {
        $handle = curl_init($url);
        if ($handle === false) throw new RuntimeException('Cannot create device control request');
        curl_setopt_array($handle, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_TIMEOUT => 60, CURLOPT_FOLLOWLOCATION => false,
        ]);
        $response = curl_exec($handle);
        $status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
        $error = curl_error($handle);
        curl_close($handle);
    } else {
        $response = @file_get_contents($url, false, stream_context_create(['http' => [
            'method' => 'POST', 'content' => $body, 'timeout' => 60,
            'ignore_errors' => true, 'follow_location' => 0,
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\nConnection: close\r\n",
        ]]));
        $status = 0;
        $error = 'Connection failed';
        if (isset($http_response_header[0]) && preg_match('/^HTTP\/\S+\s+(\d{3})/', $http_response_header[0], $match)) {
            $status = (int)$match[1];
        }
    }
    $result = is_string($response) ? json_decode($response, true) : null;
    if (!is_array($result) || $status !== 200 || (int)($result['code'] ?? -1) !== 0 ||
        (int)($result['data']['device_status'] ?? -1) !== ($action === 'disable' ? 0 : 1)) {
        $message = is_array($result) ? ($result['data']['message'] ?? $result['msg'] ?? '') : '';
        throw new RuntimeException('Device control failed (HTTP ' . $status . '): ' .
            (is_string($message) && $message !== '' ? $message : $error));
    }
    return $result;
}


if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') {header('Allow: POST');deviceControlReply(405,['success'=>false]);}
if (session_status()===PHP_SESSION_NONE) @session_start();
if (isset($_SESSION['user_law']) && (int)$_SESSION['user_law']===3) deviceControlReply(403,['success'=>false,'error'=>['message'=>'Operator cannot control devices']]);
if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
$raw=file_get_contents('php://input',false,null,0,8193);
$input=is_string($raw)&&strlen($raw)<=8192?json_decode($raw,true):null;
if (!is_array($input)||($input['confirmed'] ?? false)!==true) deviceControlReply(400,['success'=>false,'error'=>['message'=>'Explicit device control confirmation is required']]);
try {
 $action=$input['action'] ?? '';
 if(!in_array($action,['disable','enable'],true)) throw new RuntimeException('Invalid device control action');
 $network=new JobConfigNetworkService();$targets=$network->normalizeTargets($input['targets'] ?? []);
 if(count($targets)!==1) throw new RuntimeException('Exactly one target is required per device control request');
 $ip=$targets[0];$subnet=$network->matchedNetwork($ip,$network->localNetworks());
 if($subnet===null||!$network->isUsableHostAddress($ip,$subnet)) throw new RuntimeException('Target is outside the local subnet');
 $base=preg_replace('#/api/[^/]+$#','',str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/replace_job_config_device.php')));
 if(!$base) $base='/idas';
 $result=callDeviceControlApi('http://'.$ip.$base.'/api/'.$action.'.php',$action);
 deviceControlReply(200,['success'=>true,'ip'=>$ip,'data'=>$result['data']]);
} catch(Throwable $e){deviceControlReply(502,['success'=>false,'error'=>['message'=>$e->getMessage()]]);}
