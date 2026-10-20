<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/config/paths.php';
require_once __DIR__.'/HuarongProductBatchService.php';
require_once __DIR__.'/HuarongTighteningSenderService.php';
require_once __DIR__.'/MesUploadLogService.php';
require_once __DIR__.'/MesTestMode.php';
require_once __DIR__.'/OperationRecordApiClient.php';
final class OperationMesReportService
{
    private $state;
    private $recordApi;
    private bool $lastPreviewMock=false;
    private $policy;
    public function previewUsesMock(): bool { return $this->lastPreviewMock; }
    public function __construct(?OperationRecordApiClient $recordApi=null,?array $policy=null) {
        $this->state=$this->open(idas_path('database_root','das.db'));
        $this->recordApi=$recordApi ?? new OperationRecordApiClient();$this->policy=$policy;
        $this->state->exec('CREATE TABLE IF NOT EXISTS mes_work_order_context (id INTEGER PRIMARY KEY AUTOINCREMENT, baseline_id INTEGER NOT NULL, context_json TEXT NOT NULL)');
        $this->state->exec("CREATE TABLE IF NOT EXISTS mes_product_batch_v2 (id INTEGER PRIMARY KEY AUTOINCREMENT, mode TEXT NOT NULL, context_key TEXT NOT NULL, start_after INTEGER NOT NULL, last_id INTEGER NOT NULL, terminal_id INTEGER, rows_json TEXT NOT NULL, payload_json TEXT, status TEXT NOT NULL, message TEXT NOT NULL DEFAULT '', updated_at TEXT NOT NULL)");
        $this->state->exec('CREATE INDEX IF NOT EXISTS mes_product_batch_lookup ON mes_product_batch_v2(mode,context_key,last_id)');
    }
    private function open(string $path): PDO
    {
        if (!is_file($path)) throw new RuntimeException('Database not found: '.$path);
        $db=new PDO('sqlite:'.$path);$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);$db->exec('PRAGMA busy_timeout=5000');return $db;
    }
    public function baseline(): int
    {
        // Barcode receipt baseline only; upload records come from the HTTP API.
        $data=$this->open(idas_path('controller_root','ntcs_data.db'));
        $data->exec('PRAGMA query_only=ON');
        return (int)$data->query('SELECT COALESCE(MAX(id),0) FROM ntcs_data')->fetchColumn();
    }
    public function saveContext(array $mes,int $baseline): array
    {
        $data=$mes['data'];$work=$data['work_order'];
        $context=['work_order'=>$work, 'work_order_no'=>$work['work_order_no'] ?? '', 'SN'=>$work['SN'] ?? '', 'item'=>$work['item'] ?? '', 'process_name'=>$work['process_name'] ?? '',
            'station'=>$work['station'] ?? '', 'screw_inf'=>$work['screw_inf'] ?? [],
            'job_name'=>$data['job']['job_name'] ?? '', 'baseline_result_id'=>$baseline];
        foreach (['SN','item','process_name','station'] as $field) {
            if (trim((string)$context[$field]) === '') throw new RuntimeException('MES 工單缺少 '.$field);
        }
        $json=json_encode($context,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $stmt=$this->state->prepare('INSERT INTO mes_work_order_context(baseline_id,context_json) VALUES(?,?)');$stmt->execute([$baseline,$json]);
        return $context;
    }

    private function context(int $id,array $record,bool $test): array {
        if (!$test) {
            $q=$this->state->prepare('SELECT id,baseline_id,context_json FROM mes_work_order_context WHERE baseline_id<? ORDER BY baseline_id DESC,id DESC LIMIT 1');$q->execute([$id]);$saved=$q->fetch(PDO::FETCH_ASSOC);$q->closeCursor();
            if (!$saved) throw new RuntimeException('沒有對應 MES 工單；請先成功執行條碼接口，再產生新的鎖附紀錄');
            $context=json_decode($saved['context_json'],true,512,JSON_THROW_ON_ERROR);
            $key='barcode:'.$saved['id'];$baseline=(int)$saved['baseline_id'];
        } else {
            $exists=$this->state->query("SELECT name FROM sqlite_master WHERE type='table' AND name='mes_mock_work_order_context'")->fetchColumn();
            if (!$exists) throw new RuntimeException('請先使用假資料測試建立測試工單，再產生鎖附紀錄');
            $saved=null;$context=null;
            $q=$this->state->query('SELECT id,context_json FROM mes_mock_work_order_context ORDER BY id DESC');
            while ($row=$q->fetch(PDO::FETCH_ASSOC)) {
                $candidate=json_decode($row['context_json'],true,512,JSON_THROW_ON_ERROR);
                if (isset($candidate['baseline_result_id']) && (int)$candidate['baseline_result_id']<$id) {$saved=$row;$context=$candidate;break;}
            }
            $q->closeCursor();
            if (!$saved) throw new RuntimeException('測試工單沒有有效紀錄起點；請重新使用假資料測試後再鎖附');
            $key='mock:'.$saved['id'];$baseline=(int)$context['baseline_result_id'];
        }
        if (trim((string)($record['barcode'] ?? ''))!==trim((string)($context['SN'] ?? '')) || trim((string)($context['SN'] ?? ''))==='') throw new RuntimeException('鎖附條碼與對應工單 SN 不一致，已停止累積');
        return ['context'=>$context,'key'=>$key,'baseline'=>$baseline];
    }
    private function findBatch(string $mode,string $key,int $id): ?array {
        $q=$this->state->prepare('SELECT * FROM mes_product_batch_v2 WHERE mode=? AND context_key=? AND start_after<? ORDER BY id DESC LIMIT 1');$q->execute([$mode,$key,$id]);$row=$q->fetch(PDO::FETCH_ASSOC);$q->closeCursor();return $row ?: null;
    }
    private function collect(int $id,bool $test,bool $persist): array {
        $mode=$test?'test':'production';
        $saved=$this->state->prepare('SELECT * FROM mes_product_batch_v2 WHERE mode=? AND start_after<? AND terminal_id>=? AND payload_json IS NOT NULL ORDER BY id DESC LIMIT 1');
        $saved->execute([$mode,$id,$id]);$snapshot=$saved->fetch(PDO::FETCH_ASSOC);$saved->closeCursor();
        if ($snapshot) return ['batch'=>$snapshot,'complete'=>true,'payload'=>json_decode($snapshot['payload_json'],true,512,JSON_THROW_ON_ERROR)];
        $record=$this->recordApi->fetch($id);$binding=$this->context($id,$record,$test);$mode=$test?'test':'production';
        $batch=$this->findBatch($mode,$binding['key'],$id);
        if ($batch && $batch['payload_json']!==null && $id<=(int)$batch['terminal_id']) return ['batch'=>$batch,'complete'=>true,'payload'=>json_decode($batch['payload_json'],true,512,JSON_THROW_ON_ERROR)];
        if ($batch && $batch['terminal_id']!==null && $id>(int)$batch['terminal_id']) {
            if ($batch['status']==='blocked') throw new RuntimeException('前一產品尚未完成報文處理，請先處理其完成 ID：'.$batch['terminal_id']);
            $after=(int)$batch['terminal_id'];$batch=null;
        } else $after=$batch?(int)$batch['start_after']:$binding['baseline'];
        $rows=$batch?json_decode($batch['rows_json'],true,512,JSON_THROW_ON_ERROR):[];
        $cursor=$batch?(int)$batch['last_id']:$after;
        if ($id<$cursor) return ['batch'=>$batch,'complete'=>false,'status'=>'accumulating','result_count'=>count($rows)];
        while ($cursor<$id) {
            $page=$this->recordApi->fetchRange($cursor,$id);
            if (!$page) throw new RuntimeException('產品鎖附區間缺少指定紀錄：'.$id);
            foreach ($page as $row) $rows[]=$row;
            $cursor=(int)end($page)['id'];
            if (count($rows)>20000) throw new RuntimeException('單一產品累積紀錄超過上限，請核對產品分界');
        }
        $engine=new HuarongProductBatchService($this->policy);
        try {
            $analysis=$engine->assemble($rows,$binding['context'],false);
            if ($analysis['complete']) {
                $cursor=(int)$analysis['terminal_id'];
                $rows=array_values(array_filter($rows,static function($row) use($cursor){return (int)$row['id']<=$cursor;}));
            }
            $result=$engine->assemble($rows,$binding['context']);
        }
        catch(Throwable $e) {
            if ($persist) $this->storeBatch($batch,$mode,$binding['key'],$after,$cursor,$rows,null,'blocked',$e->getMessage(),$cursor);
            throw $e;
        }
        $payload=$result['payload'];
        if ($persist) {
            $status=$result['complete']?'pending':'accumulating';
            $batch=$this->storeBatch($batch,$mode,$binding['key'],$after,$cursor,$rows,$payload,$status,'',$result['terminal_id']);
        }
        $result['batch']=$batch;$result['context']=$binding['context'];return $result;
    }
    private function storeBatch(?array $batch,string $mode,string $key,int $after,int $last,array $rows,?array $payload,string $status,string $message,?int $terminal): array {
        $q=$this->state->prepare('INSERT OR REPLACE INTO mes_product_batch_v2(id,mode,context_key,start_after,last_id,terminal_id,rows_json,payload_json,status,message,updated_at) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
        $q->execute([$batch['id'] ?? null,$mode,$key,$after,$last,$terminal,json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$payload===null?null:json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),$status,$message,date('c')]);$q->closeCursor();
        $q=$this->state->prepare('SELECT * FROM mes_product_batch_v2 WHERE id=?');$q->execute([$batch['id'] ?? (int)$this->state->lastInsertId()]);$saved=$q->fetch(PDO::FETCH_ASSOC);$q->closeCursor();return $saved;
    }
    public function preview(int $id,bool $previewOnly=false): array {
        if ($id<1) throw new InvalidArgumentException('id must be a positive integer');
        $lock=$this->lock();
        try {
            $this->lastPreviewMock=MesTestMode::enabled();
            $result=$this->collect($id,$this->lastPreviewMock,false);
            if (!$result['complete']) throw new RuntimeException('產品尚在累積，尚未到最後一顆；不產生上傳報文');
            return $result['payload'];
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }
    private function lock() {
        $lock=fopen(idas_path('database_root','.mes_product_batch.lock'),'c');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) {if($lock)fclose($lock);throw new RuntimeException('產品報文正在處理，請稍後重試',409);}return $lock;
    }
    private function setStatus(int $batchId,string $status,string $message=''): void {
        $q=$this->state->prepare('UPDATE mes_product_batch_v2 SET status=?,message=?,updated_at=? WHERE id=?');$q->execute([$status,$message,date('c'),$batchId]);$q->closeCursor();
    }
    private function uploadOne(int $id): array {
        if ($id<1) throw new InvalidArgumentException('id must be a positive integer');
        $test=MesTestMode::enabled();$batch=null;$payload=null;
        try {
            $result=$this->collect($id,$test,true);$batch=$result['batch'];
            if (!$result['complete']) return ['ok'=>true,'status'=>'accumulating','id'=>$id,'test_mode'=>$test,'mes_uploaded'=>false,'mes_called'=>false,'completed_screws'=>$result['completed_screws'] ?? null,'required_screws'=>$result['required_screws'] ?? null,'result_count'=>$result['result_count']];
            $terminal=(int)$batch['terminal_id'];$payload=$result['payload'];
            if (in_array($batch['status'],['sent','test_saved'],true)) return ['ok'=>true,'status'=>'duplicate_product','id'=>$terminal,'test_mode'=>$test,'mes_uploaded'=>!$test,'mes_called'=>false];
            if (in_array($batch['status'],['sending','uncertain'],true)) throw new RuntimeException('前次 MES 結果不確定，請先核對 MES；禁止自動重送整包');
            if ($test!==MesTestMode::enabled()) throw new RuntimeException('MES 模式已變更，請重新執行');
            $logs=new MesUploadLogService($this->state);
            if ($test) {
                $this->state->beginTransaction();
                try {
                    $logs->append('tightening',(string)$terminal,$payload['SN'],'TEST_PRODUCT',$payload['process_name'],'test_skipped','',$payload);
                    $this->setStatus((int)$batch['id'],'test_saved');$this->state->commit();
                } catch(Throwable $e){if($this->state->inTransaction())$this->state->rollBack();throw $e;}
                return ['ok'=>true,'status'=>'test_product_saved','id'=>$terminal,'test_mode'=>true,'mes_uploaded'=>false,'mes_called'=>false,'payload'=>$payload];
            }
            // Do not silently resend an old per-record deployment's successful rows.
            $exists=$this->state->query("SELECT name FROM sqlite_master WHERE type='table' AND name='mes_operation_upload'")->fetchColumn();
            if ($exists) {
                $q=$this->state->prepare("SELECT COUNT(*) FROM mes_operation_upload WHERE record_id>? AND record_id<=? AND status='sent'");$q->execute([(int)$batch['start_after'],$terminal]);$sent=(int)$q->fetchColumn();$q->closeCursor();
                if ($sent) throw new RuntimeException('此產品包含舊版已上傳紀錄，請核對 MES 後以新工單測試整包上傳');
            }
            require_once __DIR__.'/MesApiSettingsService.php';
            $settings=(new MesApiSettingsService())->read();
            if (MesApiSettingsService::validate($settings['mes_tightening_api_url'])==='') throw new RuntimeException('MES tightening API URL is not configured');
            if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension 未安裝');
            $this->setStatus((int)$batch['id'],'sending');
            $config=require dirname(__DIR__).'/app/config/huarong_mes.php';$config['mock_enabled']=false;$config['timeout']=10;
            try {$answer=(new HuarongTighteningSenderService($config))->send($payload);}
            catch(Throwable $e) {$this->setStatus((int)$batch['id'],'uncertain',$e->getMessage());throw $e;}
            $response=$answer['response'];
            if (!isset($response['code']) || !is_numeric($response['code']) || (float)$response['code']!==0.0 || (string)($response['data']['save_status'] ?? '')!=='1') {
                $this->setStatus((int)$batch['id'],'failed');throw new RuntimeException('MES 未確認保存成功：'.(string)($response['msg'] ?? 'Invalid response'));
            }
            if ((isset($response['data']['SN']) && (string)$response['data']['SN']!==$payload['SN']) || (isset($response['data']['work_order_no']) && (string)$response['data']['work_order_no']!==$payload['work_order_no'])) {
                $this->setStatus((int)$batch['id'],'uncertain');throw new RuntimeException('MES 回應工單或 SN 不一致，請人工核對');
            }
            $this->state->beginTransaction();
            try {
                $this->setStatus((int)$batch['id'],'sent');
                $logs->append('tightening',(string)$terminal,$payload['SN'],'PRODUCT',$payload['process_name'],'sent','',$payload);$this->state->commit();
            } catch(Throwable $e){if($this->state->inTransaction())$this->state->rollBack();$this->setStatus((int)$batch['id'],'uncertain',$e->getMessage());throw $e;}
            return ['ok'=>true,'status'=>'mes_uploaded','id'=>$terminal,'mes_uploaded'=>true,'payload'=>$payload,'mes_response'=>$response];
        } catch(Throwable $e) {
            // The immutable payload stays on the product row for exact retries.
            if ($batch && $payload && !in_array($batch['status'],['sent','test_saved'],true)) {
                $q=$this->state->prepare('SELECT status FROM mes_product_batch_v2 WHERE id=?');$q->execute([$batch['id']]);$state=$q->fetchColumn();$q->closeCursor();
                if (!in_array($state,['sent','test_saved','uncertain','sending'],true)) $this->setStatus((int)$batch['id'],'failed',$e->getMessage());
            }
            if (!$batch || !in_array($batch['status'],['sent','test_saved','sending','uncertain'],true)) {
                try {(new MesUploadLogService($this->state))->append('tightening',(string)($batch['terminal_id'] ?? $id),(string)($payload['SN'] ?? ''),$test?'TEST_PRODUCT':'PRODUCT',(string)($payload['process_name'] ?? ''),isset($state)&&in_array($state,['uncertain','sending'],true)?'uncertain':($test?'test_failed':'failed'),$e->getMessage(),$payload);}
                catch(Throwable $logError){error_log('[mes_product_log] '.$logError->getMessage());}
            }
            throw $e;
        }
    }
    public function upload(int $id): array {
        $lock=$this->lock();
        try {
            $products=[];
            for ($i=0;$i<100;$i++) {
                $result=$this->uploadOne($id);$products[]=$result;
                if ($result['status']==='accumulating' || (int)$result['id']>=$id) {if(count($products)>1)$result['products']=$products;return $result;}
            }
            throw new RuntimeException('產品回補超過上限，請分段處理紀錄');
        } finally {flock($lock,LOCK_UN);fclose($lock);}
    }
}
