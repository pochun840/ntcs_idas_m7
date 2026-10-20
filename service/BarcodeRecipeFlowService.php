<?php
declare(strict_types=1);
require_once __DIR__.'/BarcodeReceiveStateService.php';
require_once __DIR__.'/MesTestMode.php';
require_once __DIR__.'/MesBarcodeSenderService.php';
require_once __DIR__.'/OperationMesReportService.php';
/** Both barcode sources use one serialized MES recipe workflow. */
final class BarcodeRecipeFlowService
{
 public function receive(string $barcode,string $source,?int $retryLogId=null,bool $mockRecipe=false): array
 {
  $barcode=trim($barcode);if($barcode==='')return [400,['success'=>false,'message'=>'barcode 必須為非空字串']];
  if($mockRecipe && ($source!=='POST' || !MesTestMode::enabled())) return [409,['success'=>false,'message'=>'假資料僅允許在測試模式手動使用']];
  $lock=fopen(idas_path('database_root','.barcode_recipe.lock'),'c');
  if(!$lock||!flock($lock,LOCK_EX|LOCK_NB)){if($lock)fclose($lock);return [409,['success'=>false,'message'=>'條碼處理中，請稍後重試']];}
  try {
   $db=new PDO('sqlite:'.idas_path('database_root','das.db'));$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->exec('PRAGMA busy_timeout=5000');
   $db->exec('CREATE TABLE IF NOT EXISTS idas_barcode_recipe_result (id INTEGER PRIMARY KEY CHECK(id=1), barcode TEXT NOT NULL, source TEXT NOT NULL, http_status INTEGER NOT NULL, response_json TEXT NOT NULL)');
   if($mockRecipe) {
       require_once __DIR__.'/BarcodeMockRecipeService.php';
       return [200,(new BarcodeMockRecipeService())->receive($db,$barcode)];
   }
   $state=new BarcodeReceiveStateService();$latest=$state->latest();
   $cached=$db->query('SELECT * FROM idas_barcode_recipe_result WHERE id=1')->fetch(PDO::FETCH_ASSOC);
   if ($retryLogId!==null) {
       $logs=new MesUploadLogService($db);$attempt=$logs->find($retryLogId);
       if (!$attempt || $attempt['kind']!=='barcode' || $attempt['barcode']!==$barcode || !in_array($attempt['status'],['failed','test_failed'],true)) return [409,['success'=>false,'message'=>'Retry attempt is invalid; refresh history']];
       $newest=$logs->latestForReference('barcode',(string)$attempt['reference']);
       if (!$newest || (int)$newest['id']!==$retryLogId || !$latest || $latest['receipt_id']!==$attempt['reference']) return [409,['success'=>false,'message'=>'Barcode receipt changed; refresh history']];
   }

   // Modbus polling must never retry the same receipt on every refresh.
   if($retryLogId===null&&$source==='MODBUS'&&$latest&&$latest['barcode']===$barcode&&(($latest['mes_status'] ?? '')==='test_skipped')===MesTestMode::enabled()){return [200,['success'=>true,'status'=>'already_received','mes_called'=>false]];}
   // A late POST for a successful Modbus receipt reuses its actual result.
   if($retryLogId===null&&$source==='POST'&&((json_decode($cached['response_json'] ?? '{}',true)['test_mode'] ?? false)===MesTestMode::enabled())&&$cached&&$cached['source']==='MODBUS'&&$cached['barcode']===$barcode&&(int)$cached['http_status']===200&&$latest&&$latest['barcode']===$barcode){$body=json_decode($cached['response_json'],true);$body['mes_called']=false;
       if (isset($body['mes_response']) && empty($body['test_mode'])) {
           try { $body['job_config']=$this->convertRecipe($body['mes_response']); }
           catch(Throwable $e) { return [502,['success'=>false,'status'=>'mes_failed','message'=>$e->getMessage(),'controller_updated'=>false]]; }
       }
       return [200,$body];}
   $received=$state->save($barcode,$source);
   error_log('[idas_barcode_received] source='.$source.' barcode='.json_encode($barcode,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
   $sender=null;
   try {

    if (MesTestMode::enabled()) {
        $settings=(new MesApiSettingsService())->read();
        $testPayload=['barcode'=>$barcode,'station'=>$settings['mes_station']];
        $state->delivery($received['receipt_id'],'test_skipped','',$testPayload,['kind'=>'test_skipped','http_status'=>null,'raw_body'=>null]);
        $body=['success'=>true,'status'=>'test_skipped','test_mode'=>true,'barcode'=>$barcode,'source'=>$source,'receipt_id'=>$received['receipt_id'],'mes_called'=>false,'controller_updated'=>false,'message'=>'測試模式：未呼叫 MES，未取得配方'];
        $stmt=$db->prepare('INSERT OR REPLACE INTO idas_barcode_recipe_result VALUES(1,?,?,?,?)');
        $stmt->execute([$barcode,$source,200,json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);$stmt->closeCursor();
        return [200,$body];
    }
    $report=new OperationMesReportService();$baseline=$report->baseline();
    $sender=new MesBarcodeSenderService();$mes=$sender->send($barcode);$jobConfig=$this->convertRecipe($mes['response']);$workContext=$report->saveContext($mes['response'],$baseline);
    $state->delivery($received['receipt_id'],'sent','',$sender->getRequestPayload(),$sender->getResponseDetails());$http=200;
    $body=['success'=>true,'status'=>'recipe_received','message'=>'條碼已上傳，已取得 MES 配方','barcode'=>$barcode,'source'=>$source,'received_at'=>$received['received_at'],'receipt_id'=>$received['receipt_id'],'mes_called'=>true,'mes_http_status'=>$mes['http_status'],'mes_response'=>$mes['response'],'job_config'=>$jobConfig,'work_order_context'=>$workContext,'controller_updated'=>false];
   }catch(Throwable $e){$state->delivery($received['receipt_id'],'failed',$e->getMessage(),$sender?$sender->getRequestPayload():null,$sender?$sender->getResponseDetails():['kind'=>'not_called','http_status'=>null,'raw_body'=>null]);$http=502;$body=['success'=>false,'status'=>'mes_failed','message'=>$e->getMessage(),'barcode'=>$barcode,'source'=>$source,'received_at'=>$received['received_at'],'receipt_id'=>$received['receipt_id'],'controller_updated'=>false];error_log('[idas_barcode_mes] '.$e->getMessage());}
   $stmt=$db->prepare('INSERT OR REPLACE INTO idas_barcode_recipe_result VALUES(1,?,?,?,?)');$stmt->execute([$barcode,$source,$http,json_encode($body,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]);
   return [$http,$body];
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
 /** Ready-to-POST body for replace_job_config.php; conversion does not write DB. */
 private function convertRecipe(array $response): array
 {
  $config=require dirname(__DIR__).'/app/config/huarong_mes.php';
  $mapper=new HuarongBarcodeService($config);
  return array_merge(['action'=>'write'],$mapper->mapJobConfig($mapper->assertSuccessfulResponse($response)));
 }

}
