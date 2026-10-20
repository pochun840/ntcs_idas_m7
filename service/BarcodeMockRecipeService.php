<?php
declare(strict_types=1);
require_once __DIR__.'/HuarongBarcodeService.php';
require_once __DIR__.'/MesUploadLogService.php';
require_once __DIR__.'/MesApiSettingsService.php';
/** Offline samples use a separate table; real reporting never reads this table. */
final class BarcodeMockRecipeService
{
    public function receive(PDO $db, string $barcode): array
    {
        $raw=file_get_contents(dirname(__DIR__).'/app/config/barcode_test_fixture.json');
        $mes=json_decode((string)$raw,true,512,JSON_THROW_ON_ERROR);
        if (!is_array($mes)) throw new RuntimeException('Invalid mock recipe');
        $mes['data']['work_order']['SN']=$barcode;
        $settings=(new MesApiSettingsService())->read();
        if ($settings['mes_station']!=='') $mes['data']['work_order']['station']=MesApiSettingsService::validateStation($settings['mes_station']);
        $config=require dirname(__DIR__).'/app/config/huarong_mes.php';
        $mapper=new HuarongBarcodeService($config);
        $data=$mapper->assertSuccessfulResponse($mes);
        $recipe=array_merge(['action'=>'write'],$mapper->mapJobConfig($data));
        $responses=[$mes];$jobNames=[$data['job']['job_name']];
        $context=['mock'=>true,'work_order'=>$data['work_order'],'work_order_no'=>$data['work_order']['work_order_no'],
            'SN'=>$barcode,'item'=>$data['work_order']['item'],'process_name'=>$data['work_order']['process_name'],
            'station'=>$data['work_order']['station'],'screw_inf'=>$data['work_order']['screw_inf'],
            'job_name'=>$data['job']['job_name'],'job_names'=>$jobNames];
        $db->exec('CREATE TABLE IF NOT EXISTS mes_mock_work_order_context (id INTEGER PRIMARY KEY AUTOINCREMENT, context_json TEXT NOT NULL, created_at TEXT NOT NULL)');
        $created=date('Y-m-d H:i:s');
        $stmt=$db->prepare('INSERT INTO mes_mock_work_order_context(context_json,created_at) VALUES(?,?)');
        $stmt->execute([json_encode($context,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$created]);
        $mockContextId=(int)$db->lastInsertId();
        (new MesUploadLogService($db))->append('barcode','mock:'.$mockContextId,$barcode,'MOCK',
            $data['job']['job_name'],'mock_received','',['barcode'=>$barcode,'station'=>(string)$data['work_order']['station']],['kind'=>'mock','http_status'=>null,'raw_body'=>json_encode($mes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
        return ['success'=>true,'status'=>'mock_recipe_received','test_mode'=>true,'mock'=>true,
            'message'=>'假資料測試：未呼叫 MES，假工單另存，不寫入控制器',
            'barcode'=>$barcode,'source'=>'POST','received_at'=>$created,'mes_called'=>false,
            'mes_response'=>$mes,'mock_mes_responses'=>$responses,'job_config'=>$recipe,'work_order_context'=>$context,
            'mock_context_id'=>$mockContextId,'controller_updated'=>false];
    }
}
