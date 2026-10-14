<?php
declare(strict_types=1);
require_once __DIR__.'/BarcodeReceiveStateService.php';
require_once __DIR__.'/MesBarcodeSenderService.php';
require_once __DIR__.'/OperationMesReportService.php';
/** Both barcode sources use one serialized MES recipe workflow. */
final class BarcodeRecipeFlowService
{
 public function receive(string $barcode,string $source): array
 {
  $barcode=trim($barcode);if($barcode==='')return [400,['success'=>false,'message'=>'barcode 必須為非空字串']];
  $lock=fopen(idas_path('database_root','.barcode_recipe.lock'),'c');
  if(!$lock||!flock($lock,LOCK_EX|LOCK_NB)){if($lock)fclose($lock);return [409,['success'=>false,'message'=>'條碼處理中，請稍後重試']];}
  try {
   $db=new PDO('sqlite:'.idas_path('database_root','das.db'));$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->exec('PRAGMA busy_timeout=5000');
   $db->exec('CREATE TABLE IF NOT EXISTS idas_barcode_recipe_result (id INTEGER PRIMARY KEY CHECK(id=1), barcode TEXT NOT NULL, source TEXT NOT NULL, http_status INTEGER NOT NULL, response_json TEXT NOT NULL)');
   $state=new BarcodeReceiveStateService();$latest=$state->latest();
   $cached=$db->query('SELECT * FROM idas_barcode_recipe_result WHERE id=1')->fetch(PDO::FETCH_ASSOC);
   // Modbus polling must never retry the same receipt on every refresh.
   if($source==='MODBUS'&&$latest&&$latest['barcode']===$barcode){return [200,['success'=>true,'status'=>'already_received','mes_called'=>false]];}
   // A late POST for a successful Modbus receipt reuses its actual result.
   if($source==='POST'&&$cached&&$cached['source']==='MODBUS'&&$cached['barcode']===$barcode&&(int)$cached['http_status']===200&&$latest&&$latest['barcode']===$barcode){$body=json_decode($cached['response_json'],true);$body['mes_called']=false;return [200,$body];}
   $received=$state->save($barcode,$source);
   error_log('[idas_barcode_received] source='.$source.' barcode='.json_encode($barcode,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
   try {
    $report=new OperationMesReportService();$baseline=$report->baseline();
    $mes=(new MesBarcodeSenderService())->send($barcode);$report->saveContext($mes['response'],$baseline);
    $state->delivery($received['receipt_id'],'sent','');$http=200;
    $body=['success'=>true,'status'=>'recipe_received','message'=>'條碼已上傳，已取得 MES 配方','barcode'=>$barcode,'source'=>$source,'received_at'=>$received['received_at'],'receipt_id'=>$received['receipt_id'],'mes_called'=>true,'mes_http_status'=>$mes['http_status'],'mes_response'=>$mes['response'],'controller_updated'=>false];
   }catch(Throwable $e){$state->delivery($received['receipt_id'],'failed',$e->getMessage());$http=502;$body=['success'=>false,'status'=>'mes_failed','message'=>$e->getMessage(),'barcode'=>$barcode,'source'=>$source,'received_at'=>$received['received_at'],'receipt_id'=>$received['receipt_id'],'controller_updated'=>false];error_log('[idas_barcode_mes] '.$e->getMessage());}
   $stmt=$db->prepare('INSERT OR REPLACE INTO idas_barcode_recipe_result VALUES(1,?,?,?,?)');$stmt->execute([$barcode,$source,$http,json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
   return [$http,$body];
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
}
