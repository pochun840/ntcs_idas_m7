<?php
declare(strict_types=1);
/** Read exactly one committed operation through the existing iDAS JSON API. */
final class OperationRecordApiClient
{
    private $baseUrl;
    public function __construct(?string $baseUrl=null)
    {
        $configured=getenv('IDAS_OPERATION_API_BASE_URL');
        if ($baseUrl===null && $configured!==false && $configured!=='') $baseUrl=$configured;
        if ($baseUrl===null) {
            $script=str_replace('\\','/',(string)($_SERVER['SCRIPT_NAME'] ?? ''));
            $base=preg_match('#^(/[A-Za-z0-9_./-]*)/api/[^/]+\.php$#D',$script,$match)?$match[1]:'/idas';
            $baseUrl='http://127.0.0.1'.$base.'/api';
        }
        $parts=parse_url($baseUrl);
        if (!$parts || !filter_var($baseUrl,FILTER_VALIDATE_URL) || !in_array($parts['scheme'] ?? '',['http','https'],true) || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) throw new InvalidArgumentException('Invalid operation API base URL');
        $this->baseUrl=rtrim($baseUrl,'/');
    }
    public function fetch(int $id): array
    {
        if ($id<1) throw new InvalidArgumentException('id must be a positive integer');
        if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension 未安裝');
        $url=$this->baseUrl.'/get_operation_api.php?type=json&id='.$id;
        $ch=curl_init($url);
        if ($ch===false) throw new RuntimeException('無法建立鎖附紀錄 API 請求');
        curl_setopt_array($ch,[CURLOPT_HTTPGET=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>10,
            CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>['Accept: application/json'],CURLOPT_PROXY=>'']);
        $body=curl_exec($ch);$error=curl_error($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        if ($body===false) throw new RuntimeException('讀取鎖附紀錄 API 失敗：'.$error);
        if ($http<200 || $http>=300) throw new RuntimeException('鎖附紀錄 API HTTP 狀態異常：'.$http);
        try {$rows=json_decode((string)$body,true,512,JSON_THROW_ON_ERROR);}
        catch(Throwable $e){throw new RuntimeException('鎖附紀錄 API 未回傳有效 JSON');}
        if (!is_array($rows) || ($rows!==[] && array_keys($rows)!==range(0,count($rows)-1))) throw new RuntimeException('鎖附紀錄 API 回應必須為紀錄陣列');
        if ($rows===[]) throw new RuntimeException('指定鎖附紀錄尚不存在：'.$id);
        if (count($rows)!==1 || !is_array($rows[0])) throw new RuntimeException('鎖附紀錄 API 未回傳唯一指定紀錄：'.$id);
        $record=$rows[0];
        $returnedId=filter_var($record['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if ($returnedId===false || $returnedId!==$id) throw new RuntimeException('鎖附紀錄 API 回傳 ID 不一致：'.$id);
        return $record;
    }
}
