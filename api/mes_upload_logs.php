<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/service/MesUploadLogService.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {header('Allow: GET');http_response_code(405);echo json_encode(['success'=>false]);exit;}
function uploadLogInteger(string $key,int $default,int $minimum): int
{
    if (!isset($_GET[$key])) return $default;
    if (!is_string($_GET[$key])) throw new InvalidArgumentException('Invalid pagination');
    $value=filter_var($_GET[$key],FILTER_VALIDATE_INT,['options'=>['min_range'=>$minimum]]);
    if ($value===false) throw new InvalidArgumentException('Invalid pagination');
    return $value;
}
try {
    $pages=[];$anchors=[];
    foreach (['barcode','tightening'] as $kind){$pages[$kind]=uploadLogInteger($kind.'_page',1,1);$anchors[$kind]=uploadLogInteger($kind.'_anchor',0,0);}
    $logs=new MesUploadLogService();$result=['success'=>true,'pagination'=>[]];
    foreach (['barcode','tightening'] as $kind){
        $page=$logs->page($kind,$pages[$kind],$anchors[$kind]);$result[$kind]=$page['rows'];unset($page['rows']);$result['pagination'][$kind]=$page;
    }
    echo json_encode($result,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch(InvalidArgumentException $e){http_response_code(400);echo json_encode(['success'=>false]);}
catch(Throwable $e){error_log('[mes_upload_logs] '.$e->getMessage());http_response_code(503);echo json_encode(['success'=>false]);}
