<?php

class Sequences extends Controller
{
    // 在建構子中將 Post 物件（Model）實例化
    private $sequenceModel;
    private $MiscellaneousModel;
    private $SettingModel;
    private $ToolModel;
    private $stepModel;
    private $AuditModel;
    Private $deviceId;

    public function __construct(){

        $this->sequenceModel = $this->model('Sequence');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->SettingModel = $this->model('Setting');
        $this->ToolModel = $this->model('Tool');
        $this->stepModel = $this->model('Steptcc');
        $this->AuditModel = $this->model('OperationAudit');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();

    }

    /**
     * Seq Audit: 快照單筆 Seq + 底下 Step。
     * 注意：Audit 寫入 das.db；實際資料快照仍從 KLS_NTCS_IDAS.Lin 讀取。
     */
    private function seqAuditSnapshot($jobid, $seqid = null): array
    {
        if ($seqid === null || $seqid === '') {
            return [
                'seqs' => $this->sequenceModel->getSequences_by_job_id($jobid),
            ];
        }

        return [
            'seq'   => $this->sequenceModel->search_seqinfo($jobid, $seqid),
            'steps' => $this->sequenceModel->search_stepinfo($jobid, $seqid),
        ];
    }

    /**
     * Seq Audit: 取得目前排序。
     * 用 SEQID 排序，但內容同時保留 SEQname，因為 UP/DOWN 後 SEQID 會被重新編號。
     */
    private function seqOrderSnapshot($jobid): array
    {
        $rows = $this->sequenceModel->getSequences_by_job_id($jobid);

        usort($rows, function($a, $b) {
            return ((int)($a['SEQID'] ?? 0)) <=> ((int)($b['SEQID'] ?? 0));
        });

        $order = [];
        foreach ($rows as $row) {
            $order[] = [
                'SEQID'   => (int)($row['SEQID'] ?? 0),
                'SEQname' => (string)($row['SEQname'] ?? ''),
                'skip'    => isset($row['skip']) ? (int)$row['skip'] : null,
            ];
        }

        return $order;
    }

    /**
     * Seq Audit: 從排序前後判斷是 UP 或 DOWN。
     * 以 SEQname 判斷 row identity；若無法判斷，預設回傳 EDIT。
     */
    private function detectSeqMoveAction(array $before, array $after): string
    {
        $beforeNames = array_map(function($row) {
            return (string)($row['SEQname'] ?? '');
        }, $before);

        $afterNames = array_map(function($row) {
            return (string)($row['SEQname'] ?? '');
        }, $after);

        foreach ($afterNames as $afterIndex => $name) {
            if ($name === '') {
                continue;
            }

            $beforeIndex = array_search($name, $beforeNames, true);
            if ($beforeIndex !== false && $beforeIndex !== $afterIndex) {
                return ($afterIndex < $beforeIndex) ? 'UP' : 'DOWN';
            }
        }

        return 'EDIT';
    }

    /**
     * Seq Audit: 寫入異動紀錄。
     * user_id 依需求與 operator 存相同值。
     * Audit 寫入失敗不能影響原本功能。
     */
    private function writeSeqAudit(array $data): void
    {
        try {
            if (!isset($this->AuditModel)) {
                return;
            }

            $operator = $_COOKIE['username'] ?? '';

            $defaults = [
                'user_id'      => $operator,
                'operator'     => $operator,
                'client_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
                'device_id'    => $this->deviceId ?? null,
                'module'       => 'SEQ',
                'status'       => 'SUCCESS',
                'request_json' => $_POST,
            ];

            $this->AuditModel->write(array_merge($defaults, $data));
        } catch (Throwable $e) {
            error_log('[SEQ AUDIT FAIL] ' . $e->getMessage());
        }
    }

    // 取得所有Sequences
    public function index($job_id){
        
        if( isset($job_id) && !empty($job_id) ){

        }else{
            $job_id = 1;
        }

        $sequences  = $this->sequenceModel->getSequences_by_job_id($job_id);
        $unit_arr   = $this->MiscellaneousModel->details('torque_unit');

        $total_seq = (int)$this->sequenceModel->countseq($job_id);


        if(empty($sequences)){
            $seq_id = 1;
            $next_seq_id = $seq_id;
        }else{
            $seq_id = count($sequences) + 1 ;
            $next_seq_id = $seq_id;
        }

            
        // 同步控制器資料庫（ntcs_data.db）至 iDAS
        $this->ntcs_data_db_sysnc();

        // ------------------------------
        // Tool Spec Sync (Web-triggered)
        // ------------------------------
        // Gate(10s): 限制同步檢查頻率，避免每個 request 都打 DB
        // Lock:     使用 flock 防止多 request 同步造成重複/競態
        // Sync:     只在來源(controller)與目的(iDAS)值不同時才更新
        // Note:     非 cron；沒有 request 就不會自動同步
        if ($this->shouldRunToolSpecSync(10)) {
            $this->runOnceWithFlag(
                '/var/www/html/database',        // lock / state 檔案目錄
                '.tool_spec_sync',               // 任務鎖名稱（key）
                fn() => $this->check_tools_info()// 同步 ntcs_tool_test 規格值
            );
        }

        
        
        
        
        //$this->ntcs_device_db_load();

        $isMobile = $this->isMobileCheck();
     
        $data =array();
        $data = array(
            'sequences' => $sequences,
            'job_id' => $job_id,
            'unit_arr' => $unit_arr,
            'seq_id' => $seq_id,
            'old_seqid' => '',
            'total_seq' => $total_seq,
            'next_seq_id' => $next_seq_id
        );

        if($isMobile){
            $this->view('sequences/index_m', $data);
        }else{
            $this->view('sequences/index', $data);
        }
    }

    #create 

    #create 
    public function create_seq(){


        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

            // 初始化數據陣列
            if($_POST['unscrew_forcemode_val'] == 0){
                $_POST['unscrew_force'] = $_POST['unscrew_force'];
            }else if($_POST['unscrew_forcemode_val'] == 1){
                $_POST['unscrew_force'] = 101;
            }else{
                $_POST['unscrew_force'] = 0;
            }

            // ---- 扭力單位 -> 小數位數對照（與前端一致）----
            $decimalsByUnit = [ 0=>2, 1=>3, 2=>2, 3=>4, 4=>1 ];
            $seqUnit = (int)($_POST['seq_unit'] ?? 1);

            // 原本允許的位數
            $basePrecision = $decimalsByUnit[$seqUnit] ?? 3;

            // 四捨五入時看多一位
            $roundPrecision = $basePrecision + 1;

            // 最後輸出的位數（回存）
            $outputPrecision = $basePrecision;

            // 四捨五入處理函式
            $roundTorque = function($val) use ($roundPrecision, $outputPrecision) {
                if ($val === '' || $val === null) return $val;
                $n = (float)str_replace(',', '.', $val);

                // 先四捨五入到 roundPrecision
                $rounded = round($n, $roundPrecision, PHP_ROUND_HALF_UP);

                // 再格式化到 outputPrecision（補齊尾端 0）
                return number_format($rounded, $outputPrecision, '.', '');
            };

            // --- 套用到 $_POST['unscrew_torque_threshold'] ---
            if (isset($_POST['unscrew_torque_threshold'])) {
                $_POST['unscrew_torque_threshold'] = $roundTorque($_POST['unscrew_torque_threshold']);
            }

            $seq_data = array(
                'job_id' => $_POST['job_id'] ?? null,
                'SEQID'  => $_POST['SEQID'] ?? null,
                'SEQname' => $_POST['SEQname'] ?? null,
                'time' => $_POST['time'] ?? null,
                'type' => $_POST['type'] ?? null,
                'act' => $_POST['act'] ?? 0,
                'skip' => $_POST['skip'] ?? 0,
                'seq_repeat' => $_POST['seq_repeat'] ?? null,
                'timeout' => $_POST['timeout'] ?? null,
                'dt_time' => $_POST['dt_time'] ?? 0,
                'tt_time' => $_POST['tt_time'] ?? 0,
                'ok_seq' => $_POST['ok_seq_val'] ?? null,
                'ok_stop' => $_POST['ok_stop_val'] ?? null,
                'countType' =>$_POST['countType'] ?? 1,
                'ok_screw'  =>$_POST['ok_screw'] ?? 1,
                'ng_stop' => $_POST['ng_stop'] ?? null,
                'ng_unscrew' => $_POST['ng_unscrew_val'] ?? null,
                'interrupt_alarm' => $_POST['interrupt_alarm'] ?? null,
                'accu_angle' => $_POST['accu_angle_val'] ?? null,
                'Thread_Calcu' => $_POST['angle_calculation_data'] ?? null,
                'unscrew_mode' => $_POST['unscrew_mode_val'] ?? 1,
                'unscrew_force' => $_POST['unscrew_force'] ?? 50,
                'unscrew_rpm' => $_POST['unscrew_rpm'] ?? 300,
                'unscrew_dir' => $_POST['unscrew_dir_val'] ?? 0,
                'image' => $_POST['image'] ?? '',
                'message' => $_POST['message'] ?? '',
                'delay' => $_POST['delay'] ?? null,
                'input' => $_POST['input'] ?? null,
                'input_signal' => $_POST['input_signal'] ?? 1,
                'output' => $_POST['output'] ?? null,
                'output_signal' => $_POST['output_signal'] ?? 1,
                'output_durat' => $_POST['output_durat'] ?? 100,
                'addtion' => $_POST['addtion'] ?? null,
                'unscrew_count_switch' => $_POST['unscrew_count_switch_val'] ?? null,
                'unscrew_torque_threshold' => $_POST['unscrew_torque_threshold'] ?? null,
                'seq_unit' => $_POST['seq_unit'] ?? 0,
                'unscrew_angle_threshold' => $_POST['unscrew_angle_threshold'] ?? 0,
                'dt_time' => $_POST['dt_time'] ?? 0,
                'tt_time' => $_POST['tt_time'] ?? 0

            );

            if ($seq_data['job_id'] === null ||$seq_data['SEQID'] === null ||$seq_data['SEQname'] === null) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            $mode = "create";
            $res = $this->sequenceModel->create_seq($mode,$seq_data);

            if($res){
                // 只有 Seq 建立成功後才建立預設 Step，避免產生孤兒 Step。
                $res_device = $this->SettingModel->GetControllerInfo();
                $device_torque_unit = (int)$res_device['torque_unit'];

                $tools_temp = $this->getConvertedToolInfo();
                if(!empty($tools_temp)){
                    $this->stepModel->createDefaultStep(
                        $seq_data['job_id'],
                        $seq_data['SEQID'],
                        $tools_temp['torque'],
                        $tools_temp['max_torque'],
                        $tools_temp['min_torque'],
                        $tools_temp['torque'],
                        $device_torque_unit
                    );
                }

                $after = $this->seqAuditSnapshot($seq_data['job_id'], $seq_data['SEQID']);
                $this->writeSeqAudit([
                    'action'      => 'NEW',
                    'status'      => 'SUCCESS',
                    'job_id'      => (int)$seq_data['job_id'],
                    'seq_id'      => (int)$seq_data['SEQID'],
                    'title'       => 'New Sequence',
                    'message'     => 'Create sequence id: ' . $seq_data['SEQID'],
                    'before_json' => null,
                    'after_json'  => $after,
                ]);

                $res_type = 'Success';
                $res_msg  = $text['new_seq'].':'. $seq_data['SEQID']."  ".$text['success'];
                $this->MiscellaneousModel->generateErrorResponse($text['success'], $res_msg);
            }else{
                $this->writeSeqAudit([
                    'action'      => 'NEW',
                    'status'      => 'FAIL',
                    'job_id'      => (int)$seq_data['job_id'],
                    'seq_id'      => (int)$seq_data['SEQID'],
                    'title'       => 'New Sequence Fail',
                    'message'     => 'Create sequence failed id: ' . $seq_data['SEQID'],
                    'before_json' => null,
                    'after_json'  => null,
                ]);

                $res_type = 'Error';
                $res_msg  = $text['new_seq'].':'. $seq_data['SEQID']."  ".$text['fail'];
            }

            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg
            );

            echo json_encode($result);

        }

    

        public function getConvertedToolInfo() {

        $Tool_Info = $this->ToolModel->GetToolInfo();
        $Tool_Info = end($Tool_Info); // 只取最後一筆

        $temp = [];

        if (!empty($Tool_Info)) {
            // 取得 Controller 設定的 torque unit
            $res_device = $this->ToolModel->GetControllerInfo();
            $device_torque_unit = (int)$res_device['torque_unit'];

            // 對應 torque 單位名稱
            $unit_arr = $this->MiscellaneousModel->details('torque_unit');
            $unit_name = $unit_arr[$device_torque_unit];

            // 對應每個 torque 單位的小數位
            $decimals_arr = $this->MiscellaneousModel->details('decimals');
            $torque_decimals = $decimals_arr[$device_torque_unit] ?? 3;

            // 從 DB 取出的 torque 值要先 /1000 (假設 DB 單位是 N.m)
            $minTorqueNm = (float)$Tool_Info['min_torque'] / 1000;
            $maxTorqueNm = ((float)$Tool_Info['max_torque'] / 1000) * 1.1;

            // 轉換所有 torque 單位
            $low_torque_arr = $this->MiscellaneousModel->convert_all_torque_units($minTorqueNm, 1, false);
            $high_torque_arr = $this->MiscellaneousModel->convert_all_torque_units($maxTorqueNm, 1, false);

            // 強制小數格式（不補值，只格式化）
            $Tool_Info['min_torque'] = number_format((float)$low_torque_arr[$unit_name], $torque_decimals, '.', '');
            $Tool_Info['max_torque'] = number_format((float)$high_torque_arr[$unit_name], $torque_decimals, '.', '');
            $Tool_Info['torque_unit_name'] = $unit_name;

            // 產生 temp array（給 JS 使用）
            $temp['torque'] = $Tool_Info['min_torque'];
            $temp['max_torque'] = $Tool_Info['max_torque'];

            // 計算 min torque 對應的小數位 → 最小有效單位
            if ($torque_decimals > 0) {
                $temp['min_torque'] = "0." . str_repeat("0", $torque_decimals - 1) . "0";
            } else {
                $temp['min_torque'] = "1";
            }

        }

        return $temp;
    }

    public function delete_seq(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $jobid = $_POST['jobid'] ?? null;
        $seqid = $_POST['seqid'] ?? null;

        if(!empty($jobid)){
            $result = array();
            $before = (!empty($seqid)) ? $this->seqAuditSnapshot($jobid, $seqid) : null;
            $orderBefore = $this->seqOrderSnapshot($jobid);

            $res = $this->sequenceModel->delete_seq_by_id($jobid,$seqid);
            $orderAfter = $this->seqOrderSnapshot($jobid);

            if($res){
                $this->writeSeqAudit([
                    'action'            => 'DELETE',
                    'status'            => 'SUCCESS',
                    'job_id'            => (int)$jobid,
                    'seq_id'            => !empty($seqid) ? (int)$seqid : null,
                    'title'             => 'Delete Sequence',
                    'message'           => 'Delete sequence id: ' . $seqid,
                    'before_json'       => $before,
                    'after_json'        => null,
                    'order_before_json' => $orderBefore,
                    'order_after_json'  => $orderAfter,
                ]);

                $res_type = $text['success'];
                $res_msg  = $text['del_seq'].':'. $seqid."  ".$text['success'];
                $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg );
            }else{
                $this->writeSeqAudit([
                    'action'            => 'DELETE',
                    'status'            => 'FAIL',
                    'job_id'            => (int)$jobid,
                    'seq_id'            => !empty($seqid) ? (int)$seqid : null,
                    'title'             => 'Delete Sequence Fail',
                    'message'           => 'Delete sequence failed id: ' . $seqid,
                    'before_json'       => $before,
                    'after_json'        => null,
                    'order_before_json' => $orderBefore,
                    'order_after_json'  => $orderAfter,
                ]);

                $res_type = $text['fail'];
                $res_msg  = $text['del_seq'].':'. $seqid."  ".$text['fail'];
                $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg );
            }

        }
    }


    #查詢seq data
    public function search_seqinfo(){

        $jobid = $_POST['jobid'] ?? null;
        $seqid = $_POST['seqid'] ?? null;

        if(!empty($jobid)){
            $res  = $this->sequenceModel->search_seqinfo($jobid,$seqid);
        }

        $data = array(
            'seq_info' => $res,
            'job_id' => $jobid,
            'seq_id' => $seqid
        );
    }

    public function edit_seq(){


        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if($_POST['unscrew_forcemode_val'] == 0){
            $_POST['unscrew_force'] = $_POST['unscrew_force'];
        }else if($_POST['unscrew_forcemode_val'] == 1){
            $_POST['unscrew_force'] = 101;
        }else{
            $_POST['unscrew_force'] = 0;
        }

        // ---- 扭力單位 -> 小數位數對照（與前端一致）----
        $decimalsByUnit = [ 0=>2, 1=>3, 2=>2, 3=>4, 4=>1 ];
        $seqUnit = (int)($_POST['seq_unit'] ?? 1);

        // 原本允許的位數
        $basePrecision = $decimalsByUnit[$seqUnit] ?? 3;

        // 四捨五入時看多一位
        $roundPrecision = $basePrecision + 1;

        // 最後輸出的位數（回存）
        $outputPrecision = $basePrecision;

        // 四捨五入處理函式
        $roundTorque = function($val) use ($roundPrecision, $outputPrecision) {
            if ($val === '' || $val === null) return $val;
            $n = (float)str_replace(',', '.', $val);

            // 先四捨五入到 roundPrecision
            $rounded = round($n, $roundPrecision, PHP_ROUND_HALF_UP);

            // 再格式化到 outputPrecision（補齊尾端 0）
            return number_format($rounded, $outputPrecision, '.', '');
        };

        // --- 套用到 $_POST['unscrew_torque_threshold'] ---
        if (isset($_POST['unscrew_torque_threshold'])) {
            $_POST['unscrew_torque_threshold'] = $roundTorque($_POST['unscrew_torque_threshold']);
        }

        //取得當下 IDAS 設定的扭力單位
        $res_device = $this->SettingModel->GetControllerInfo();
        $device_torque_unit = (int)$res_device['torque_unit'];

        if(isset($_POST['job_id'])){

            // 初始化數據陣列
            $seq_data = array(
                'JOBID' => $_POST['job_id'] ?? null,
                'SEQID'  => $_POST['SEQID'] ?? null,
                'SEQname' => $_POST['SEQname'] ?? null,
                'time' => $_POST['time'] ?? null,
                'type' => $_POST['type'] ?? null,
                'act' => $_POST['act'] ?? null,
                'skip' => $_POST['skip'] ?? null,
                'seq_repeat' => $_POST['seq_repeat'] ?? null,
                'timeout' => $_POST['timeout'] ?? null,
                'dt_time' => $_POST['dt_time'] ?? 0,
                'tt_time' => $_POST['tt_time'] ?? 0,
                'ok_seq' => $_POST['ok_seq_val'] ?? null,
                'ok_stop' => $_POST['ok_stop_val'] ?? null,
                'countType' =>$_POST['countType'] ?? 1,
                'ok_screw'  =>$_POST['ok_screw'] ?? 1,
                'ng_stop' => $_POST['ng_stop'] ?? null,
                'ng_unscrew' => $_POST['ng_unscrew_val'] ?? null,
                'interrupt_alarm' => $_POST['interrupt_alarm'] ?? null,
                'accu_angle' => $_POST['accu_angle_val'] ?? null,
                'Thread_Calcu' => $_POST['angle_calculation_data'] ?? null,
                'unscrew_mode' => $_POST['unscrew_mode_val'] ?? null,
                'unscrew_force' => $_POST['unscrew_force'] ?? null,
                'unscrew_rpm' => $_POST['unscrew_rpm'] ?? null,
                'unscrew_dir' => $_POST['unscrew_dir_val'] ?? 0,
                'image' => $_POST['image'] ?? null,
                'message' => $_POST['message'] ?? null,
                'delay' => $_POST['delay'] ?? null,
                'input' => $_POST['input'] ?? null,
                'input_signal' => $_POST['input_signal'] ?? null,
                'output' => $_POST['output'] ?? null,
                'output_signal' => $_POST['output_signal'] ?? null,
                'output_durat' => $_POST['output_durat'] ?? null,
                'addtion' => $_POST['addtion'] ?? null,
                'unscrew_count_switch' => $_POST['unscrew_count_switch_val'] ?? null,
                'unscrew_torque_threshold' => $_POST['unscrew_torque_threshold'] ?? null,
                'seq_unit' => $_POST['seq_unit'] ?? $device_torque_unit,
                'unscrew_angle_threshold' => $_POST['unscrew_angle_threshold'] ?? 0,
                'dt_time' => $_POST['dt_time'] ?? 0,
                'tt_time' => $_POST['tt_time'] ?? 0,
                'total_angle_limit' => $_POST['total_angle_limit'] ?? 0,
                'total_angle_lower' => $_POST['total_angle_lower'] ?? 0,

            );

            if ($seq_data['JOBID'] === null || $seq_data['SEQID'] === null ||$seq_data['SEQname'] === null) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }

            $before = $this->seqAuditSnapshot($seq_data['JOBID'], $seq_data['SEQID']);
            $res = $this->sequenceModel->update_seq_by_id($seq_data);
            $result = array();
            if($res){
                $after = $this->seqAuditSnapshot($seq_data['JOBID'], $seq_data['SEQID']);
                $this->writeSeqAudit([
                    'action'      => 'EDIT',
                    'status'      => 'SUCCESS',
                    'job_id'      => (int)$seq_data['JOBID'],
                    'seq_id'      => (int)$seq_data['SEQID'],
                    'title'       => 'Edit Sequence',
                    'message'     => 'Edit sequence id: ' . $seq_data['SEQID'],
                    'before_json' => $before,
                    'after_json'  => $after,
                ]);

                $res_type = 'Success';
                $res_msg  = $text['edit_seq'].':'.$seq_data['SEQID']."  ".$text['success'];
            }else{
                $this->writeSeqAudit([
                    'action'      => 'EDIT',
                    'status'      => 'FAIL',
                    'job_id'      => (int)$seq_data['JOBID'],
                    'seq_id'      => (int)$seq_data['SEQID'],
                    'title'       => 'Edit Sequence Fail',
                    'message'     => 'Edit sequence failed id: ' . $seq_data['SEQID'],
                    'before_json' => $before,
                    'after_json'  => null,
                ]);

                $res_type = 'Error';
                $res_msg  = $text['edit_seq'].':'.$seq_data['SEQID']."  ".$text['fail'];
            }

            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg
            );

            echo json_encode($result);
        }
    }


    public function check_seq_type(){
        
        $jobid = $_POST['jobid'] ?? null;
        $seqid = $_POST['newseqid'] ?? null;
        

        if(!empty($seqid)){
            $res  = $this->sequenceModel->sequence_id_repeat($jobid,$seqid);
            if($res == "True"){

            }
            echo  $res;
        }
      

    }

    //check_seq_enable
    public function check_seq_enable(){

        $input_check = true;
        if(!empty($_POST)){
            $seq_data = array();
            $seq_data = $_POST;
        }else{
            $input_check = false; 
        }

        if($input_check){
            $jobid = $seq_data['jobid'] ?? null;
            $seqid = $seq_data['seqid'] ?? null;
            $skip  = isset($seq_data['skip']) ? (int)$seq_data['skip'] : null;

            $before = (!empty($jobid) && !empty($seqid))
                ? $this->seqAuditSnapshot($jobid, $seqid)
                : null;

            $res = $this->sequenceModel->update_seq_type($seq_data);

            /*$after = (!empty($jobid) && !empty($seqid) && $res)
                ? $this->seqAuditSnapshot($jobid, $seqid)
                : null;

            $this->writeSeqAudit([
                'action'      => 'EDIT',
                'status'      => $res ? 'SUCCESS' : 'FAIL',
                'job_id'      => !empty($jobid) ? (int)$jobid : null,
                'seq_id'      => !empty($seqid) ? (int)$seqid : null,
                'title'       => 'Sequence Enable Change',
                'message'     => ($skip === 0)
                                    ? 'Enable sequence id: ' . $seqid
                                    : 'Disable sequence id: ' . $seqid,
                'before_json' => $before,
                'after_json'  => $after,
            ]);*/
        }
    }

    public function copy_seq_data(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $jobid = $_POST['jobid'] ?? null;
        $seqid = $_POST['seqid'] ?? null;
        $newseqid = $_POST['newseqid'] ?? null;
        $oldseqname = $_POST['oldseqname'] ?? null;
        $newseqname = $_POST['newseqname'] ?? null;

        $rows = false;
        $rows_temp = 0;
        $before = (!empty($jobid) && !empty($seqid)) ? $this->seqAuditSnapshot($jobid, $seqid) : null;
        $targetBefore = (!empty($jobid) && !empty($newseqid)) ? $this->seqAuditSnapshot($jobid, $newseqid) : null;

        //用jobid 及 seqid 去找出 對應的資料
        $old_res = $this->sequenceModel->search_seqinfo($jobid,$seqid);

        $this->sequenceModel->del_seq_type($jobid,$newseqid);
        $this->sequenceModel->del_step_type($jobid,$newseqid);

        $select_step = $this->sequenceModel->search_stepinfo($jobid,$seqid);
        if(!empty($old_res)){
            $new_temp_seq = array();
            foreach($old_res as $kk_seq =>$val){
                $new_temp_seq[$kk_seq]['JOBID'] = $val['JOBID'];
                $new_temp_seq[$kk_seq]['SEQID'] = $newseqid;
                $new_temp_seq[$kk_seq]['SEQname'] = $newseqname;
                $new_temp_seq[$kk_seq]['time'] = $val['time'];
                $new_temp_seq[$kk_seq]['type'] = $val['type'];
                $new_temp_seq[$kk_seq]['act'] = $val['act'];
                $new_temp_seq[$kk_seq]['skip'] = $val['skip'];
                $new_temp_seq[$kk_seq]['seq_repeat'] = $val['seq_repeat'];
                $new_temp_seq[$kk_seq]['timeout'] = $val['timeout'];
                $new_temp_seq[$kk_seq]['ok_seq'] = $val['ok_seq'];
                $new_temp_seq[$kk_seq]['ok_stop'] = $val['ok_stop'];
                $new_temp_seq[$kk_seq]['countType'] = $val['countType'];
                $new_temp_seq[$kk_seq]['ok_screw'] = $val['ok_screw'];
                $new_temp_seq[$kk_seq]['ng_stop'] = $val['ng_stop'];
                $new_temp_seq[$kk_seq]['ng_unscrew'] = $val['ng_unscrew'];
                $new_temp_seq[$kk_seq]['interrupt_alarm'] = $val['interrupt_alarm'];
                $new_temp_seq[$kk_seq]['accu_angle'] = $val['accu_angle'];
                $new_temp_seq[$kk_seq]['Thread_Calcu'] = $val['Thread_Calcu'];
                $new_temp_seq[$kk_seq]['unscrew_mode'] = $val['unscrew_mode'];
                $new_temp_seq[$kk_seq]['unscrew_force'] = $val['unscrew_force'];
                $new_temp_seq[$kk_seq]['unscrew_rpm'] = $val['unscrew_rpm'];
                $new_temp_seq[$kk_seq]['unscrew_dir'] = $val['unscrew_dir'];
                $new_temp_seq[$kk_seq]['image'] = '';
                $new_temp_seq[$kk_seq]['message'] = '';
                $new_temp_seq[$kk_seq]['delay'] = $val['delay'];
                $new_temp_seq[$kk_seq]['input'] = $val['input'];
                $new_temp_seq[$kk_seq]['input_signal'] = $val['input_signal'];
                $new_temp_seq[$kk_seq]['output'] = $val['output'];
                $new_temp_seq[$kk_seq]['output_signal'] = $val['output_signal'];
                $new_temp_seq[$kk_seq]['output_durat'] = $val['output_durat'];
                $new_temp_seq[$kk_seq]['addtion'] = null;
                $new_temp_seq[$kk_seq]['unscrew_count_switch'] = $val['unscrew_count_switch'];
                $new_temp_seq[$kk_seq]['unscrew_torque_threshold'] = $val['unscrew_torque_threshold'];
                $new_temp_seq[$kk_seq]['seq_unit'] = $val['seq_unit'];
                $new_temp_seq[$kk_seq]['unscrew_angle_threshold'] = $val['unscrew_angle_threshold'];
                $new_temp_seq[$kk_seq]['dt_time'] = $val['dt_time'];
                $new_temp_seq[$kk_seq]['tt_time'] = $val['tt_time'];
                $new_temp_seq[$kk_seq]['total_angle_limit'] = $val['total_angle_limit'];
                $new_temp_seq[$kk_seq]['total_angle_lower'] = $val['total_angle_lower'];
            }

            $rows = $this->sequenceModel->copy_seq_by_seq_id($new_temp_seq);
        }
        if(!empty($select_step)){
            $new_temp_step = array();
            foreach($select_step as $k_step =>$v_step){
                $new_temp_step[$k_step]['JOBID'] = $v_step['JOBID'];
                $new_temp_step[$k_step]['SEQID'] = $newseqid;
                $new_temp_step[$k_step]['StepSelect'] = $v_step['StepSelect'];
                $new_temp_step[$k_step]['STEPname'] = $v_step['STEPname'];
                $new_temp_step[$k_step]['type'] = $v_step['type'];
                $new_temp_step[$k_step]['time'] = $v_step['time'];
                $new_temp_step[$k_step]['act'] = $v_step['act'];
                $new_temp_step[$k_step]['StepSwitch'] = $v_step['StepSwitch'];
                $new_temp_step[$k_step]['StepRPM'] = $v_step['StepRPM'];
                $new_temp_step[$k_step]['StepOption'] = $v_step['StepOption'];
                $new_temp_step[$k_step]['StepTime'] = $v_step['StepTime'];
                $new_temp_step[$k_step]['StepAngle'] = $v_step['StepAngle'];
                $new_temp_step[$k_step]['StepTorque'] = $v_step['StepTorque'];
                $new_temp_step[$k_step]['StepDirection'] = $v_step['StepDirection'];
                $new_temp_step[$k_step]['StepDelay'] = $v_step['StepDelay'];
                $new_temp_step[$k_step]['StepMoniByWin'] = $v_step['StepMoniByWin'];
                $new_temp_step[$k_step]['StepLimiHi'] = $v_step['StepLimiHi'];
                $new_temp_step[$k_step]['StepLimiLo'] = $v_step['StepLimiLo'];
                $new_temp_step[$k_step]['StepHiAngle'] = $v_step['StepHiAngle'];
                $new_temp_step[$k_step]['StepLoAngle'] = $v_step['StepLoAngle'];
                $new_temp_step[$k_step]['StepLoAngle'] = $v_step['StepLoAngle'];
                $new_temp_step[$k_step]['StepHiTorque'] = $v_step['StepHiTorque'];
                $new_temp_step[$k_step]['StepLoTorque'] = $v_step['StepLoTorque'];
                $new_temp_step[$k_step]['StepAccelerateOffset'] = $v_step['StepAccelerateOffset'];
                $new_temp_step[$k_step]['StepAccelerateOffsetSign'] = $v_step['StepAccelerateOffsetSign'];
                $new_temp_step[$k_step]['StepEnableTorqueOffset'] = $v_step['StepEnableTorqueOffset'];
                $new_temp_step[$k_step]['StepTorqueOffset'] = $v_step['StepTorqueOffset'];
                $new_temp_step[$k_step]['StepTorqueOffsetSign'] = $v_step['StepTorqueOffsetSign'];
                $new_temp_step[$k_step]['StepEnableDownShift']  = $v_step['StepEnableDownShift'];
                $new_temp_step[$k_step]['StepTorqueDownShift'] = $v_step['StepTorqueDownShift'];
                $new_temp_step[$k_step]['StepRPMDownShift'] = $v_step['StepRPMDownShift'];
                $new_temp_step[$k_step]['StepEnableThreshold'] = $v_step['StepEnableThreshold'];
                $new_temp_step[$k_step]['StepTorqueTS'] = $v_step['StepTorqueTS'];
                $new_temp_step[$k_step]['StepReTry'] = $v_step['StepReTry'];
                $new_temp_step[$k_step]['StepUnScrew'] = $v_step['StepUnScrew'];
                $new_temp_step[$k_step]['StepReTryTorq'] = $v_step['StepReTryTorq'];
                $new_temp_step[$k_step]['StepReTryAngl'] = $v_step['StepReTryAngl'];
                $new_temp_step[$k_step]['StepAngleRecord'] = $v_step['StepAngleRecord'];
                $new_temp_step[$k_step]['StepAutoDetectAngle'] = $v_step['StepAutoDetectAngle'];
                $new_temp_step[$k_step]['InterruptAlarm'] = $v_step['InterruptAlarm'];
                $new_temp_step[$k_step]['OverAngleStop'] = $v_step['OverAngleStop'];
                $new_temp_step[$k_step]['KValue'] = $v_step['KValue'];
                $new_temp_step[$k_step]['step_unit'] = $v_step['step_unit'];

            }

            $rows_temp = $this->sequenceModel->copy_step_by_seq_id($new_temp_step);
        }

        if($rows){
            $after = $this->seqAuditSnapshot($jobid, $newseqid);
            $this->writeSeqAudit([
                'action'        => 'COPY',
                'status'        => 'SUCCESS',
                'job_id'        => (int)$jobid,
                'seq_id'        => (int)$newseqid,
                'source_seq_id' => (int)$seqid,
                'target_seq_id' => (int)$newseqid,
                'title'         => 'Copy Sequence',
                'message'       => 'Copy sequence from ' . $seqid . ' to ' . $newseqid,
                'before_json'   => [
                    'source' => $before,
                    'target_before' => $targetBefore,
                ],
                'after_json'    => $after,
            ]);

            $res_type = 'Success';
            $res_msg  = $text['Copy_Sequence'].':'.$newseqid."  ".$text['success'];
        }else{
            $this->writeSeqAudit([
                'action'        => 'COPY',
                'status'        => 'FAIL',
                'job_id'        => !empty($jobid) ? (int)$jobid : null,
                'seq_id'        => !empty($newseqid) ? (int)$newseqid : null,
                'source_seq_id' => !empty($seqid) ? (int)$seqid : null,
                'target_seq_id' => !empty($newseqid) ? (int)$newseqid : null,
                'title'         => 'Copy Sequence Fail',
                'message'       => 'Copy sequence failed from ' . $seqid . ' to ' . $newseqid,
                'before_json'   => [
                    'source' => $before,
                    'target_before' => $targetBefore,
                ],
                'after_json'    => null,
            ]);

            $res_type = 'Error';
            $res_msg  = $text['Copy_Sequence'].':'.$newseqid."  ".$text['fail'];
        }

        $result = array(
            'res_type' => $res_type,
            'res_msg'  => $res_msg
        );

        echo json_encode($result);

    }

   
    #seq 排序
    public function adjustment_order(){

        if(isset($_POST['jobid'])){

            $jobid = $_POST['jobid'];
            $rowInfoArray = $_POST['rowInfoArray'] ?? [];
            if(!empty($rowInfoArray)){
                $new_info = array();
                $index = 1;
                foreach ($rowInfoArray as $v_s) {
                    $new_info[$index] = $v_s;
                    $index++;
                }

                $orderBefore = $this->seqOrderSnapshot($jobid);
                $res = false;
                $errorMessage = '';

                try {
                    $res = $this->sequenceModel->swapupdate($jobid,$rowInfoArray,$new_info);
                } catch (Throwable $e) {
                    $res = false;
                    $errorMessage = $e->getMessage();
                    error_log('[SEQ ORDER FAIL] ' . $errorMessage);
                }

                $orderAfter = $this->seqOrderSnapshot($jobid);
                $moveAction = $res ? $this->detectSeqMoveAction($orderBefore, $orderAfter) : 'EDIT';

                $this->writeSeqAudit([
                    'action'            => $moveAction,
                    'status'            => $res ? 'SUCCESS' : 'FAIL',
                    'job_id'            => (int)$jobid,
                    'title'             => 'Sequence ' . $moveAction,
                    'message'           => $res ? 'Sequence order changed' : ('Sequence order change failed' . ($errorMessage !== '' ? ': ' . $errorMessage : '')),
                    'order_before_json' => $orderBefore,
                    'order_after_json'  => $orderAfter,
                ]);
                
                if($res){
                    $res_msg = 'success';
                }else{
                    $res_msg = 'fail';
                }
                echo $res_msg;
                //die();
            }
            



        }
    }


    public function variation($job_id = null, $seq_id = null) {
        
        // 預設 job_id 為 1
        $job_id = $job_id ?? 1;

        $torque_unit_code = $this->SettingModel->Get_System_Toq_Unit();
        $unit_arr = $this->MiscellaneousModel->details('torque_unit');
        $torque_unit = $unit_arr[$torque_unit_code] ?? 'N.m';
        $decimals_arr = $this->MiscellaneousModel->details("decimals");

        $res_device = $this->SettingModel->GetControllerInfo();
        $device_torque_unit = (int)$res_device['torque_unit'];

        $tools_info = $this->ToolModel->GetToolInfo()[0] ?? [];

        if (!empty($tools_info)) {
            foreach (['max_rpm', 'min_rpm'] as $key) {
                if (isset($tools_info[$key])) {
                    $val = (float) $tools_info[$key];

                    // 檢查是否為整數
                    if (floor($val) == $val) {
                        // 若為整數 → 輸出為沒有小數的字串
                        $tools_info[$key] = (string) intval($val);
                    } else {
                        // 若有小數 → 保留原數值
                        $tools_info[$key] = (string) $val;
                    }
                }
            }

            $from_unit = 1;
            $tools_temp = $this->MiscellaneousModel->prepareToolTorqueValues($tools_info,$from_unit,$device_torque_unit,$decimals_arr);
            if(!empty($tools_temp)){
                $tools_info['max_torque'] = $tools_temp['max_torque'];
                $tools_info['min_torque'] = $tools_temp['min_torque'];
            }
        }


        $isMobile = $this->isMobileCheck();

        // 判斷模式：新增或編輯
        if (empty($seq_id)) {
            // 新增模式：取得目前 job_id 下的 sequence 數量
            $existing_sequences = $this->sequenceModel->getSequences_by_job_id($job_id);
            $seq_id = empty($existing_sequences) ? 1 : count($existing_sequences) + 1;
            $sequences = [];
            $type = 'new';
            $next_seq_id = $seq_id;
        } else {
            // 編輯模式：查詢指定 sequence 資料
            $res = $this->sequenceModel->search_seqinfo($job_id, $seq_id);

            if (empty($res)) {
                echo "無效的 Job ID 或 Sequence ID！";
                return;
            }

            $sequences = $res[0];
            $type = 'edit';
            $next_seq_id = $sequences['SEQID'];

            if(!empty($res[0])){

                $res_device = $this->SettingModel->GetControllerInfo();
                $device_torque_unit = (int)$res_device['torque_unit'];
                $torque_arr = $this->MiscellaneousModel->details("torque_unit");
                $unit_name = $torque_arr[$device_torque_unit];

                $seq_torque_unit = (int)$sequences['seq_unit'];



                if($sequences['unscrew_mode'] != "0" ){
                    $temp = $this->MiscellaneousModel->convert_seq_torque($sequences['unscrew_torque_threshold'],$device_torque_unit, $unit_name);
                    $sequences['unscrew_torque_threshold'] = $temp['converted_value'];
                }else{
                
                    if($device_torque_unit  != $seq_torque_unit){
                        //控制器的扭力單位 與 SEQ的扭力單位 不同,才需要做單位的換算
                        $res1= $this->MiscellaneousModel->convert_step_torque($sequences['unscrew_torque_threshold'],$sequences['seq_unit'],$device_torque_unit); 
                        $sequences['unscrew_torque_threshold'] = $res1['converted_value'];   
                    }
                }

                
            }


        }
        $data = [
            'sequences'     => $sequences,
            'job_id'        => $job_id,
            'seq_id'        => $seq_id,
            'tools_info'    => $tools_info,
            'type'          => $type,
            'torque_unit'   => $torque_unit,
            'next_seq_id'   => $next_seq_id,
            'torque_unit_code' => $torque_unit_code
        ];
        
        $this->view($isMobile ? 'sequences/add_seq_m' : 'sequences/add_seq', $data);
    }

        
}
?>
