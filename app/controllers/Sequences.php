<?php

class Sequences extends Controller
{
    // 在建構子中將 Post 物件（Model）實例化
    private $sequenceModel;
    private $MiscellaneousModel;
    private $SettingModel;
    private $ToolModel;
    private $stepModel;
    public function __construct(){

        $this->sequenceModel = $this->model('Sequence');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->SettingModel = $this->model('Setting');
        $this->ToolModel = $this->model('Tool');
        $this->stepModel = $this->model('Steptcc');
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

        $this->ntcs_data_db_sysnc();
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
    public function create_seq(){


        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if(isset($_POST['job_id'])){
        
            // 初始化數據陣列
            if($_POST['unscrew_forcemode_val'] == 0){
                $_POST['unscrew_force'] = $_POST['unscrew_force'];
            }else if($_POST['unscrew_forcemode_val'] == 1){
                $_POST['unscrew_force'] = 101;
            }else{
                $_POST['unscrew_force'] = 0;
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


            $res_device = $this->SettingModel->GetControllerInfo();
            $device_torque_unit = (int)$res_device['torque_unit'];
    
            $tools_temp = $this->getConvertedToolInfo();
            
            if(!empty($tools_temp )){
                $step_res = $this->stepModel->createDefaultStep($seq_data['job_id'],$seq_data['SEQID'],$tools_temp['torque'],$tools_temp['max_torque'],$tools_temp['min_torque'],$tools_temp['torque'],$device_torque_unit);
            }


            $result = array();
            if($res){
                $res_type = 'Success';
                $res_msg  = $text['new_seq'].':'. $seq_data['SEQID']."  ".$text['success'];
            }else{
                $res_type = 'Error';
                $res_msg  = $text['new_seq'].':'. $seq_data['SEQID']."  ".$text['fail'];
            }
            
            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg 
            );

            echo json_encode($result);

        }

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

            $res = $this->sequenceModel->delete_seq_by_id($jobid,$seqid);
            if($res){
                $res_type = 'Success';
                $res_msg  = $text['del_seq'].':'. $seqid."  ".$text['success'];
                $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg );
            }else{
                $res_type = 'Error';
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
                'seq_unit' => $_POST['seq_unit'] ?? $device_torque_unit

            );

            if ($seq_data['JOBID'] === null || $seq_data['SEQID'] === null ||$seq_data['SEQname'] === null) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }


            $res = $this->sequenceModel->update_seq_by_id($seq_data);
            $result = array();
            if($res){
                $res_type = 'Success';
                $res_msg  = $text['edit_seq'].':'.$seq_data['SEQID']."  ".$text['success'];
            }else{
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
            $this->sequenceModel->update_seq_type($seq_data);
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
            $res_type = 'Success';
            $res_msg  = $text['Copy_Sequence'].':'.$newseqid."  ".$text['success'];
        }else{
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
            $rowInfoArray = $_POST['rowInfoArray'];
            if(!empty($rowInfoArray)){
                $new_info = array();
                $index = 1;
                foreach ($rowInfoArray as $v_s) {
                    $new_info[$index] = $v_s;
                    $index++;
                }

                $res = $this->sequenceModel->swapupdate($jobid,$rowInfoArray,$new_info);
                
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


            //
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
                if($sequences['unscrew_mode'] == 0 ){
                    $temp = $this->MiscellaneousModel->convert_seq_torque($sequences['unscrew_torque_threshold'],$device_torque_unit, $unit_name);
                    $sequences['unscrew_torque_threshold'] = $temp['converted_value'];
                }else{
                    
                    $precision = isset( $decimals_arr[$device_torque_unit])
                        ?  $decimals_arr[$device_torque_unit]
                        : 3;

                    $sequences['unscrew_torque_threshold'] = number_format(
                        (float)$sequences['unscrew_torque_threshold'],
                        $precision
                    ); 
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
