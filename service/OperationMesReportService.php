<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/paths.php';
require_once __DIR__ . '/HuarongTighteningReportService.php';
require_once __DIR__ . '/HuarongTighteningSenderService.php';
require_once __DIR__.'/MesUploadLogService.php';
require_once __DIR__.'/MesTestMode.php';
require_once __DIR__.'/OperationRecordApiClient.php';
final class OperationMesReportService
{
    private $state;
    private $recordApi;
    private $record = [];
    public function __construct(?OperationRecordApiClient $recordApi=null)
    {
        $this->state=$this->open(idas_path('database_root','das.db'));
        $this->recordApi=$recordApi ?? new OperationRecordApiClient();
        $this->state->exec('CREATE TABLE IF NOT EXISTS mes_work_order_context (id INTEGER PRIMARY KEY AUTOINCREMENT, baseline_id INTEGER NOT NULL, context_json TEXT NOT NULL)');
        $this->state->exec("CREATE TABLE IF NOT EXISTS mes_operation_upload (record_id INTEGER PRIMARY KEY, status TEXT NOT NULL, payload_json TEXT, message TEXT NOT NULL DEFAULT '', updated_at TEXT NOT NULL)");
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
    public function saveContext(array $mes,int $baseline): void
    {
        $data=$mes['data'];$work=$data['work_order'];
        $context=['SN'=>$work['SN'] ?? '', 'item'=>$work['item'] ?? '', 'process_name'=>$work['process_name'] ?? '',
            'station'=>$work['station'] ?? '', 'screw_inf'=>$work['screw_inf'] ?? [],
            'job_name'=>$data['job']['job_name'] ?? '', 'baseline_result_id'=>$baseline];
        foreach (['SN','item','process_name','station'] as $field) {
            if (trim((string)$context[$field]) === '') throw new RuntimeException('MES 工單缺少 '.$field);
        }
        $json=json_encode($context,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $stmt=$this->state->prepare('INSERT INTO mes_work_order_context(baseline_id,context_json) VALUES(?,?)');$stmt->execute([$baseline,$json]);
    }
    private function mark(int $id,string $status,?string $payload,string $message): void
    {
        $stmt=$this->state->prepare('INSERT OR REPLACE INTO mes_operation_upload(record_id,status,payload_json,message,updated_at) VALUES(?,?,?,?,?)');
        $stmt->execute([$id,$status,$payload,$message,date('c')]);
        $stmt->closeCursor();
        if (in_array($status,['sent','failed'],true)) {
            try {
                $row=$this->record;
                if (!$row && $payload) {
                    $savedPayload=json_decode($payload,true);
                    if (is_array($savedPayload)) $row=['barcode'=>$savedPayload['SN'] ?? '', 'job_name'=>$savedPayload['process_name'] ?? ''];
                }
                (new MesUploadLogService($this->state))->append('tightening',(string)$id,(string)($row['barcode'] ?? ''),'',(string)($row['job_name'] ?? ''),$status,$message);
            } catch(Throwable $e){error_log('[mes_upload_log] '.$e->getMessage());}
        }
    }
    public function preview(int $id, bool $previewOnly = false): array
    {
        if ($id<1) throw new InvalidArgumentException('id must be a positive integer');
        $record=$this->recordApi->fetch($id);
        $this->record=$record;
        // Map MES fields directly from the requested tightening record.
        $context = [
            'item' => (string)$id,
            'process_name' => trim((string)($record['job_name'] ?? '')),
            'station' => 1,
            'SN' => trim((string)($record['barcode'] ?? '')),
            'screw_inf' => [[
                'screw_item' => (string)$id,
                'screw_quantity' => (int)($record['total_screw_count'] ?? 0),
            ]],
        ];
        if (!$previewOnly && !MesTestMode::enabled() && $context['SN'] === '') throw new RuntimeException('指定紀錄 barcode 為空，無法作為 MES SN：'.$id);
        if (!$previewOnly && $context['process_name'] === '') throw new RuntimeException('指定紀錄 job_name 為空，無法作為 MES process_name：'.$id);
        $mapper=new HuarongTighteningReportService();
        // Allow a single action; complete_staus still reflects actual OK-JOB status.
        // Use final torque for MES Rl as requested.
        $record['peak_fasten_torque'] = $record['final_fasten_torque'] ?? null;
        $payload=$mapper->buildPayload([$record],$context,true,$previewOnly || MesTestMode::enabled(),$previewOnly);
        if (!$previewOnly && empty($payload['runParam_inf'])) throw new RuntimeException('指定紀錄沒有可上報的有效鎖附結果：'.$id);
        return $payload;
    }
    public function upload(int $id): array
    {
        if ($id<1) throw new InvalidArgumentException('id must be a positive integer');
        $this->record=[];
        if (MesTestMode::enabled()) {
            // notify_new_operation holds the per-ID lock across this check and append.
            new MesUploadLogService($this->state);
            $stmt=$this->state->prepare("SELECT id FROM mes_upload_log WHERE kind='tightening' AND reference=? AND status='test_skipped' LIMIT 1");
            $stmt->execute([(string)$id]);$tested=$stmt->fetchColumn()!==false;$stmt->closeCursor();
            if ($tested) return ['ok'=>true,'status'=>'duplicate_test','id'=>$id,'test_mode'=>true,'mes_uploaded'=>false,'mes_called'=>false];
            try {
                $payload=$this->preview($id);
                (new MesUploadLogService($this->state))->append('tightening',(string)$id,(string)($this->record['barcode'] ?? ''),'',(string)($this->record['job_name'] ?? ''),'test_skipped');
                return ['ok'=>true,'status'=>'test_skipped','id'=>$id,'test_mode'=>true,'mes_uploaded'=>false,'mes_called'=>false,'payload'=>$payload];
            } catch(Throwable $e) {
                try {(new MesUploadLogService($this->state))->append('tightening',(string)$id,(string)($this->record['barcode'] ?? ''),'',(string)($this->record['job_name'] ?? ''),'test_failed',$e->getMessage());}
                catch(Throwable $logError){error_log('[mes_upload_log] '.$logError->getMessage());}
                throw $e;
            }
        }

        $stmt=$this->state->prepare('SELECT * FROM mes_operation_upload WHERE record_id=?');$stmt->execute([$id]);$saved=$stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();
        if ($saved && $saved['status']==='sent') return ['ok'=>true,'status'=>'duplicate','id'=>$id,'mes_uploaded'=>true];
        $payloadJson=$saved['payload_json'] ?? null;
        try {
            if (!$payloadJson) {
                $payload=$this->preview($id);
                $payloadJson=json_encode($payload,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $this->mark($id,'pending',$payloadJson,'');
            }
            $payload=json_decode($payloadJson,true,512,JSON_THROW_ON_ERROR);
            if (trim((string)($payload['SN'] ?? ''))==='') throw new RuntimeException('正式上傳 SN 不能為空');
            $config=require dirname(__DIR__) . '/app/config/huarong_mes.php';
            $config['mock_enabled']=false;$config['timeout']=10;
            $result=(new HuarongTighteningSenderService($config))->send($payload);$response=$result['response'];
            if (!isset($response['code']) || !is_numeric($response['code']) || (float)$response['code']!==0.0 ||
                (string)($response['data']['save_status'] ?? '')!=='1') throw new RuntimeException('MES 未確認保存成功：'.(string)($response['msg'] ?? 'Invalid response'));
            if (isset($response['data']['SN']) && (string)$response['data']['SN']!==(string)$payload['SN']) throw new RuntimeException('MES 回應 SN 不一致');
            $this->mark($id,'sent',$payloadJson,'');
            return ['ok'=>true,'status'=>'mes_uploaded','id'=>$id,'mes_uploaded'=>true,'mes_response'=>$response];
        } catch (Throwable $e) {
            $this->mark($id,'failed',$payloadJson,$e->getMessage());throw $e;
        }
    }
}
