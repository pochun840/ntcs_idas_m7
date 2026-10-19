<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if(($_SERVER['REQUEST_METHOD'] ?? '')!=='POST'){header('Allow: POST');http_response_code(405);echo json_encode(['success'=>false]);exit;}
try {
 require_once dirname(__DIR__).'/service/BarcodeModbusReadService.php';
 require_once dirname(__DIR__).'/service/BarcodeRecipeFlowService.php';
 $read=(new BarcodeModbusReadService())->read();
 [$http,$body]=(new BarcodeRecipeFlowService())->receive($read['barcode'],'MODBUS');http_response_code($http);echo json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){http_response_code(503);echo json_encode(['success'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);}
