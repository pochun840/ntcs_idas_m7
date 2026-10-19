<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') {header('Allow: POST');http_response_code(405);echo json_encode(['success'=>false]);exit;}
// Browser requests must originate from the recipe page's origin.
if (($_SERVER['HTTP_X_IDAS_PAGE_MONITOR'] ?? '')!=='1') {http_response_code(403);echo json_encode(['success'=>false]);exit;}
$origin=$_SERVER['HTTP_ORIGIN'] ?? '';
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '')==='cross-site' || ($origin!=='' && (strtolower((string)parse_url($origin,PHP_URL_HOST)).(parse_url($origin,PHP_URL_PORT)?':'.parse_url($origin,PHP_URL_PORT):''))!==strtolower((string)($_SERVER['HTTP_HOST'] ?? '')))) {
    http_response_code(403);echo json_encode(['success'=>false,'message'=>'Cross-origin request rejected']);exit;
}
if (session_status()===PHP_SESSION_ACTIVE) session_write_close();
require_once dirname(__DIR__).'/service/OperationPageMonitorService.php';
try {
    $result=(new OperationPageMonitorService())->tick();
    $result['test_mode']=MesTestMode::enabled();
    echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
} catch(Throwable $e) {error_log('[idas_operation_page_monitor] '.$e->getMessage());http_response_code(503);echo json_encode(['success'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
