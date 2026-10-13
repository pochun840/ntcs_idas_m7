<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/paths.php';
require_once __DIR__ . '/HuarongTighteningReportService.php';
require_once __DIR__ . '/HuarongTighteningSenderService.php';
final class OperationMesReportService
{
    // Configure actual MES values when they are not present in ntcs_data.
    private const DIRECT_REPORT_DEFAULTS = [
        'item' => '', 'process_name' => '', 'station' => '',
        'screw_inf' => [], // [['screw_item' => 'actual part number', 'screw_quantity' => 1]]
    ];
    private $state;
    private $data;
    public function __construct()
    {
        $this->state=$this->open(idas_path('database_root','das.db'));
        $this->data=$this->open(idas_path('controller_root','ntcs_data.db'));
        $this->data->exec('PRAGMA query_only=ON');
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
        return (int)$this->data->query('SELECT COALESCE(MAX(id),0) FROM ntcs_data')->fetchColumn();
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
    }
    public function upload(int $id): array
    {
        $stmt=$this->state->prepare('SELECT * FROM mes_operation_upload WHERE record_id=?');$stmt->execute([$id]);$saved=$stmt->fetch(PDO::FETCH_ASSOC);
        if ($saved && $saved['status']==='sent') return ['ok'=>true,'status'=>'duplicate','id'=>$id,'mes_uploaded'=>true];
        $payloadJson=$saved['payload_json'] ?? null;
        try {
            if (!$payloadJson) {
                $stmt=$this->data->prepare('SELECT * FROM ntcs_data WHERE id=?');$stmt->execute([$id]);$record=$stmt->fetch(PDO::FETCH_ASSOC);
                if (!$record) throw new RuntimeException('指定鎖附紀錄尚不存在：'.$id);
                // Report only the requested record; never reuse an unrelated work order.
                $context = self::DIRECT_REPORT_DEFAULTS;
                foreach (['item', 'process_name', 'station'] as $field) {
                    if (isset($record[$field]) && trim((string)$record[$field]) !== '') $context[$field] = $record[$field];
                }
                if (isset($record['screw_inf'])) {
                    $screws = is_array($record['screw_inf']) ? $record['screw_inf'] : json_decode((string)$record['screw_inf'], true);
                    if (!is_array($screws)) throw new RuntimeException('紀錄 screw_inf 不是有效 JSON 陣列');
                    $context['screw_inf'] = $screws;
                }
                $context['SN'] = trim((string)($record['barcode'] ?? ''));
                if ($context['SN'] === '') throw new RuntimeException('指定紀錄 barcode 為空，無法作為 MES SN：'.$id);
                foreach (['item', 'process_name', 'station'] as $field) {
                    if (trim((string)$context[$field]) === '') throw new RuntimeException('直接 ID 上報缺少 '.$field.'；請在 OperationMesReportService.php 的 DIRECT_REPORT_DEFAULTS 設定實際 MES 值');
                }
                if (empty($context['screw_inf'])) throw new RuntimeException('直接 ID 上報缺少 screw_inf；請在 DIRECT_REPORT_DEFAULTS 設定實際 screw_item 與 screw_quantity');
                $mapper=new HuarongTighteningReportService();
                // Allow a single action; complete_staus still reflects actual OK-JOB status.
                $payload=$mapper->buildPayload([$record],$context,true);
                if (empty($payload['runParam_inf'])) throw new RuntimeException('指定紀錄沒有可上報的有效鎖附結果：'.$id);
                $payloadJson=json_encode($payload,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
                $this->mark($id,'pending',$payloadJson,'');
            }
            $payload=json_decode($payloadJson,true,512,JSON_THROW_ON_ERROR);
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
