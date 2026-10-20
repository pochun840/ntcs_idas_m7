<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/service/OperationMesReportService.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function operationNotifyReply(int $status,array $body): void {http_response_code($status);echo json_encode($body,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);exit;}
$method=$_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method,['GET','POST'],true)) {header('Allow: GET, POST');operationNotifyReply(405,['ok'=>false]);}
$token=getenv('IDAS_OPERATION_NOTIFY_TOKEN');
if ($token!==false && $token!=='' && !hash_equals($token,(string)($_SERVER['HTTP_X_IDAS_TOKEN'] ?? ''))) operationNotifyReply(401,['ok'=>false,'message'=>'Invalid notification token']);
$input=$method==='GET'?$_GET:json_decode((string)file_get_contents('php://input'),true);
$id=is_array($input)?filter_var($input['id'] ?? null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):false;
if ($id===false) operationNotifyReply(400,['ok'=>false,'message'=>'id must be a positive integer']);
// Release any caller session before making the nested local API request.
if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
try {
    $lock=fopen(idas_path('database_root','.mes_operation_upload_'.$id.'.lock'),'c');
    if (!$lock || !flock($lock,LOCK_EX | LOCK_NB)) operationNotifyReply(409,['ok'=>false,'id'=>$id,'message'=>'同一 ID 正在上報，請稍後重試']);
    $result=(new OperationMesReportService())->upload($id);
    flock($lock,LOCK_UN);fclose($lock);operationNotifyReply(200,$result);
} catch (Throwable $e) {
    error_log('[idas_operation_notify] '.$e->getMessage());
    operationNotifyReply($e->getCode()===409?409:503,['ok'=>false,'id'=>$id,'mes_uploaded'=>false,'message'=>$e->getMessage()]);
}
