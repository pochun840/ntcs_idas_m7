<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '')!=='GET'){header('Allow: GET');http_response_code(405);echo json_encode(['success'=>false]);exit;}
$post=null;$postError='';
try {require_once dirname(__DIR__).'/service/BarcodeReceiveStateService.php';$post=(new BarcodeReceiveStateService())->latest();}
catch(Throwable $e){$postError=$e->getMessage();error_log('[idas_barcode_state] '.$postError);}
try {require_once dirname(__DIR__).'/service/BarcodeModbusReadService.php';$modbus=(new BarcodeModbusReadService())->read();}
catch(Throwable $e){$modbus=['success'=>false,'barcode'=>null,'message'=>$e->getMessage()];}
$data=$post;
if($modbus['success']){
 $data=['barcode'=>$modbus['barcode'],'received_at'=>$modbus['read_at'],'receipt_id'=>'modbus:'.hash('sha256',$modbus['barcode']),'source'=>'MODBUS'];
 // A POST delivery status applies only to the same barcode.
 if($post && $post['barcode']===$modbus['barcode']) foreach(['mes_status','mes_message'] as $key)$data[$key]=$post[$key] ?? '';
}elseif($data){$data['source']='POST';}
if(!$modbus['success'] && !$post && $postError!==''){http_response_code(503);echo json_encode(['success'=>false]);exit;}
echo json_encode(['success'=>true,'fallback_needed'=>$modbus['success']&&trim($modbus['barcode'])!==''&&(!$post||$post['barcode']!==trim($modbus['barcode'])),'data'=>$data,'sources'=>['post'=>($post&&($post['source'] ?? 'POST')==='POST'?$post:null),'modbus'=>$modbus]],JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
