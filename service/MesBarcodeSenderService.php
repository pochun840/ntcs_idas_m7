<?php
declare(strict_types=1);
require_once __DIR__ . '/MesApiSettingsService.php';
require_once __DIR__ . '/HuarongBarcodeService.php';
final class MesBarcodeSenderService
{
    public const RESPONSE_TIMEOUT_SECONDS = 10;

    public function send(string $barcode): array
    {
        require_once __DIR__.'/MesTestMode.php';
        if (MesTestMode::enabled()) throw new RuntimeException('測試模式：禁止呼叫 MES 條碼 API');
        $settings=(new MesApiSettingsService())->read();
        $url=MesApiSettingsService::validate($settings['mes_barcode_api_url']);
        if ($url === '') throw new RuntimeException('請先設定條碼上傳 API 網址');
        if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension 未安裝');
        $payload=json_encode(['barcode'=>$barcode],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) throw new RuntimeException('無法建立條碼 JSON');
        $ch=curl_init($url);
        curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$payload,
            CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json'],
            CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>self::RESPONSE_TIMEOUT_SECONDS,
            CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2]);
        $raw=curl_exec($ch);$error=curl_error($ch);$errno=curl_errno($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if ($raw === false && $errno === CURLE_OPERATION_TIMEDOUT) throw new RuntimeException('MES 回應逾時（等待上限 '.self::RESPONSE_TIMEOUT_SECONDS.' 秒）');
        if ($raw === false) throw new RuntimeException('MES 連線失敗：'.$error);
        if ($http < 200 || $http >= 300) throw new RuntimeException('MES HTTP 狀態異常：'.$http);
        $response=json_decode($raw,true);
        if (!is_array($response) || json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('MES 回應不是有效 JSON');
        // Documented contract: code=0 plus complete recipe data.
        (new HuarongBarcodeService([]))->assertSuccessfulResponse($response);
        return ['http_status'=>$http,'response'=>$response];
    }
}
