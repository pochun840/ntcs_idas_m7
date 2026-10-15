<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/service/OperationMesReportService.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? '')!=='GET') {header('Allow: GET');http_response_code(405);echo json_encode(['success'=>false]);exit;}
$id=is_string($_GET['id'] ?? null)?filter_var($_GET['id'],FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]):false;
if ($id===false) {http_response_code(400);echo json_encode(['success'=>false,'message'=>'id must be a positive integer']);exit;}
if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
try {
    $payload=(new OperationMesReportService())->preview($id, true);
    echo json_encode(['success'=>true,'id'=>$id,'test_mode'=>MesTestMode::enabled(),'payload'=>$payload],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch(Throwable $e){error_log('[operation_mes_preview] '.$e->getMessage());http_response_code(503);echo json_encode(['success'=>false,'id'=>$id,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);}
