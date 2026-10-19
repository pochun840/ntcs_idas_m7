<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
function uploadRetryReply(int $http,array $data): void {http_response_code($http);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') {header('Allow: POST');uploadRetryReply(405,['success'=>false]);}
if (($_SERVER['HTTP_X_IDAS_PAGE_MONITOR'] ?? '')!=='1' || ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')==='cross-site') uploadRetryReply(403,['success'=>false]);
$origin=$_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin!=='' && strtolower((string)parse_url($origin,PHP_URL_HOST).(parse_url($origin,PHP_URL_PORT)?':'.parse_url($origin,PHP_URL_PORT):''))!==strtolower((string)($_SERVER['HTTP_HOST'] ?? ''))) uploadRetryReply(403,['success'=>false]);
$body=json_decode((string)file_get_contents('php://input'),true);
$logId=is_array($body)?filter_var($body['log_id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):false;
if ($logId===false) uploadRetryReply(400,['success'=>false,'message'=>'log_id must be positive']);
if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
require_once dirname(__DIR__).'/service/OperationMesReportService.php';
function uploadRetrySync(int $id,string $status,string $message): void {
    $path=idas_path('database_root','mes_operation_watcher.db');if (!is_file($path)) return;
    try {
        $db=new PDO('sqlite:'.$path);$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->exec('PRAGMA busy_timeout=5000');
        $exists=$db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='queue'")->fetchColumn();if (!$exists) return;
        $stmt=$db->prepare('UPDATE queue SET status=?,message=?,updated_at=? WHERE record_id=?');$stmt->execute([$status,$message,date('c'),$id]);$stmt->closeCursor();
    } catch(Throwable $e) {error_log('[mes_retry_monitor_sync] '.$e->getMessage());}
}

try {
    $logs=new MesUploadLogService();$row=$logs->find($logId);
    if (!$row || !in_array($row['status'],['failed','test_failed'],true)) uploadRetryReply(409,['success'=>false,'message'=>'Retry attempt changed; refresh history']);
    if ($row['kind']==='barcode') {
        require_once dirname(__DIR__).'/service/BarcodeRecipeFlowService.php';
        [$http,$result]=(new BarcodeRecipeFlowService())->receive((string)$row['barcode'],(string)$row['source'],$logId);
        uploadRetryReply($http,['success'=>($result['success'] ?? false)===true,'result'=>$result,'message'=>$result['message'] ?? '','test_mode'=>MesTestMode::enabled()]);
    }
    if ($row['kind']!=='tightening') uploadRetryReply(400,['success'=>false]);
    $id=filter_var($row['reference'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
    if ($id===false) uploadRetryReply(400,['success'=>false,'message'=>'Invalid operation ID']);
    $lock=fopen(idas_path('database_root','.mes_operation_upload_'.$id.'.lock'),'c');
    if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) uploadRetryReply(409,['success'=>false,'message'=>'Operation is processing; refresh history']);
    try {
        $latest=$logs->latestForReference('tightening',(string)$id);
        if (!$latest || (int)$latest['id']!==$logId) uploadRetryReply(409,['success'=>false,'message'=>'Newer attempt exists; refresh history']);
        $result=(new OperationMesReportService())->upload($id);
        uploadRetrySync($id,'done',(string)($result['status'] ?? 'ok'));
    } finally {flock($lock,LOCK_UN);fclose($lock);}
    uploadRetryReply(200,['success'=>true,'result'=>$result,'test_mode'=>MesTestMode::enabled()]);
} catch(Throwable $e) {if (isset($id) && is_int($id)) uploadRetrySync($id,'failed',$e->getMessage());error_log('[mes_upload_retry] '.$e->getMessage());uploadRetryReply(503,['success'=>false,'message'=>$e->getMessage()]);}
