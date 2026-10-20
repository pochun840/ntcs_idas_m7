<?php
declare(strict_types=1);
require_once __DIR__.'/HuarongBarcodeService.php';
require_once __DIR__.'/MesUploadLogService.php';
require_once __DIR__.'/MesApiSettingsService.php';
require_once __DIR__.'/HuarongTighteningReportService.php';
require_once __DIR__.'/HuarongProductBatchService.php';
require_once __DIR__.'/MesTestMode.php';
/** Offline samples use a separate table; real reporting never reads this table. */
final class BarcodeMockRecipeService
{
    public function receive(PDO $db, string $barcode): array
    {
        if (!MesTestMode::enabled()) throw new RuntimeException('假資料僅允許在測試模式使用');
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
        $tightening=$this->tighteningSample($context);
        $logs=new MesUploadLogService($db);
        $context['baseline_result_id']=0;
        $sourcePath=idas_path('controller_root','ntcs_data.db');
        if (is_file($sourcePath)) {
            $source=new PDO('sqlite:'.$sourcePath);$source->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$source->exec('PRAGMA query_only=ON');
            $context['baseline_result_id']=(int)$source->query('SELECT COALESCE(MAX(id),0) FROM ntcs_data')->fetchColumn();
        }
        $db->exec('CREATE TABLE IF NOT EXISTS mes_mock_work_order_context (id INTEGER PRIMARY KEY AUTOINCREMENT, context_json TEXT NOT NULL, created_at TEXT NOT NULL)');
        $created=date('Y-m-d H:i:s');
        $db->beginTransaction();
        try {
        $stmt=$db->prepare('INSERT INTO mes_mock_work_order_context(context_json,created_at) VALUES(?,?)');
        $stmt->execute([json_encode($context,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),$created]);
        $mockContextId=(int)$db->lastInsertId();
        $logs->append('barcode','mock:'.$mockContextId,$barcode,'MOCK',
            $data['job']['job_name'],'mock_received','',['barcode'=>$barcode,'station'=>(string)$data['work_order']['station']],['kind'=>'mock','http_status'=>null,'raw_body'=>json_encode($mes,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)]);
        $logs->append('tightening','mock:'.$mockContextId,$barcode,'MOCK',$data['job']['job_name'],'mock_received','',$tightening);
        $db->commit();
        } catch (Throwable $e) { if ($db->inTransaction()) $db->rollBack(); throw $e; }
        return ['success'=>true,'status'=>'mock_recipe_received','test_mode'=>true,'mock'=>true,
            'message'=>'假資料測試：未呼叫 MES，假工單另存，不寫入控制器',
            'barcode'=>$barcode,'source'=>'POST','received_at'=>$created,'mes_called'=>false,
            'mes_response'=>$mes,'mock_mes_responses'=>$responses,'job_config'=>$recipe,'work_order_context'=>$context,
            'mock_tightening_payload'=>$tightening,'mock_tightening_created'=>true,'mock_context_id'=>$mockContextId,'controller_updated'=>false];
    }
    /** Entirely synthetic results: never read or insert controller operation records. */
    private function tighteningSample(array $context): array
    {
        $rows=[];$ranges=[];$counter=0;$id=0;
        foreach ($context['screw_inf'] as $screw) {
            $quantity=(int)($screw['screw_quantity'] ?? 0);
            if ($quantity<1 || $quantity>1000) throw new RuntimeException('Invalid mock screw quantity');
            $start=$counter+1;
            for ($i=0;$i<$quantity;$i++) {
                $counter++;
                $record=['id'=>++$id,'barcode'=>$context['SN'],'job_name'=>$context['job_name'],
                    'last_screw_count'=>$counter,'fasten_status'=>4,'final_fasten_torque'=>920-$i%5,
                    'rpm'=>107,'final_fasten_angle'=>108+$i%4,'lo_torque'=>800,'hi_torque'=>1100,'user_id'=>'MOCK-222'];
                // Demonstrate a failed attempt and retry at the same physical screw.
                if ($counter===1) {$ng=$record;$ng['fasten_status']=7;$rows[]=$ng;$record['id']=++$id;}
                $rows[]=$record;
            }
            $ranges[(string)$screw['screw_item']]=[$start,$counter];
        }
        // Explicit synthetic positions do not establish real controller semantics.
        $engine=new HuarongProductBatchService(['counter_verified'=>true,'counter_field'=>'last_screw_count','counter_direction'=>'up','material_ranges'=>$ranges]);
        $last=array_pop($rows);
        if ($rows && $engine->assemble($rows,$context)['complete']) throw new RuntimeException('Mock product ended before final screw');
        $rows[]=$last;$result=$engine->assemble($rows,$context);
        if (!$result['complete']) throw new RuntimeException('Mock product did not complete');
        return $result['payload'];
    }
}
