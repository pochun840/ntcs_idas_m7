<?php
class Jobs extends Controller
{
    private $jobModel;
    private $DashboardModel;
    private $ToolModel;
    private $SettingModel;
    private $MiscellaneousModel;
    private $sequenceModel;
    private $stepModel;
 
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->jobModel = $this->model('Job');
        $this->DashboardModel = $this->model('Dashboard');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->sequenceModel = $this->model('Sequence');
        $this->stepModel = $this->model('Steptcc');
        $this->ToolModel = $this->model('Tool');
        $this->SettingModel = $this->model('Setting');

    }

    // 取得所有Jobs
    public function index(){
        $data = array();

        $isMobile  = $this->isMobileCheck();
        $jobs      = $this->jobModel->getJobs();
        $direction = $this->MiscellaneousModel->details('reverse_direction');

        // 取得下個可用的 job_id
        $next_job_id_arr = $this->jobModel->get_head_job_id();
        $next_job_id = (int)$next_job_id_arr['missing_id'];

        // 避開 0 與 221
        $invalid_ids = [0, 221];
        while (in_array($next_job_id, $invalid_ids)) {
            $next_job_id++;
        }

        // 計算 jobIdInt
        if (!empty($jobs)) {
            $lastRow  = end($jobs);
            $jobIdInt = intval($lastRow['JOBID']) + 1;
        } else {
            $lastRow  = 1;
            $jobIdInt = 1;
        }
        

        $data = array(
            'jobint' => $jobIdInt,
            'jobs' => $jobs,
            'next_job_id' => $next_job_id,
        );



        if ($isMobile) {
            $this->view('jobs/job_management_m', $data);
        } else {
            $this->view('jobs/job_management', $data);
        }
    }




    #create 
    public function create_job(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if(isset($_POST['jobidnew'])){
            $jobName = $_POST['jobname_val'];
            $validationResult = $this->MiscellaneousModel->validateName($jobName); 
            if ($validationResult === true) {
                $jobdata = array(
                    'job_id' => $_POST['jobidnew'],
                    'job_name' => $jobName,
                    'type' => 1,
                    'time' =>  date('Y-m-d H:i:s'),
                    'act' => 0,
                    'ok_job' => $_POST['job_ok_val'],
                    'ok_job_stop' => $_POST['stop_job_ok_val'],
                    'output_unified' => 0,
                    'input_unified'  => 0,
                    'job_unit' => 0
          
                );
            }
      
            $job_count = $this->jobModel->countjob();
            if($job_count >= 100) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $error_message['job_id']);
                exit();
            }
    
            $res = $this->jobModel->create_job($jobdata);
            //利用 jobid  去新增seq  && step 

            
            $res_device = $this->SettingModel->GetControllerInfo();
            $device_torque_unit = (int)$res_device['torque_unit'];

            
            $seq_result = $this->sequenceModel->createDefaultSeq($jobdata['job_id'],$device_torque_unit);  

            $tools_temp = $this->getConvertedToolInfo();
            
            if(!empty($tools_temp )){
                $step_res = $this->stepModel->createDefaultStep($jobdata['job_id'],$seq_result['seq_id'],$tools_temp['torque'],$tools_temp['max_torque'],$tools_temp['min_torque'],$device_torque_unit);
            }

            $result = array();
            if($res){
                $res_msg  = $text['New']."  ".$text['job_id'].':'. $jobdata['job_id']."  ".$text['success'];
                $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg);
            }else{
                $res_msg  = $text['New']."  ".$text['job_id'].':'.$jobdata['job_id']."  ".$text['fail'];
                $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg);
            }
            
                      
        }
    }

    public function update_job(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $jobdata  = array();
        if(isset($_POST['jobid'])){

            $jobdata = array(
                'job_id' => $_POST['jobid'],
                'job_name' => $_POST['jobname'],
                'ok_job' => $_POST['jobokValue'],
                'ok_job_stop' => $_POST['stopjobValue']

            );

            $res = $this->jobModel->update_job_by_id($jobdata);
            $result = array();
            if($res){
                $res_msg = $text['Edit']."  ".$text['job_id'].':'. $jobdata['job_id']."  ".$text['success'];
                $this->MiscellaneousModel->generateErrorResponse('Succes', $res_msg );
            }else{
                $res_msg = $text['Edit']."  ".$text['job_id'].':'. $jobdata['job_id']."  ".$text['fail'];
                $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg );
            }

        } 
    
    }

    #delete 
    public function delete_jobid() {

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }
 
        $jobid = $_POST['jobid'] ?? null;
        if(!empty($jobid)){

            $res = $this->jobModel->delete_job_by_id($jobid);
            $ans = $this->jobModel->delete_sequence_by_job_id($jobid);
            $an1 = $this->jobModel->delete_step_by_job_id($jobid);
            $an2 = $this->jobModel->delete_input_by_job_id($jobid);
            $an3 = $this->jobModel->delete_output_by_job_id($jobid);

            $result = array();
            if($res){
                $res_msg = $text['Delete']."  ".$text['job_id'].':'. $jobid."  ".$text['success'];
                $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg );
            }else{
                $res_msg = $text['Delete']."  ".$text['job_id'].':'. $jobid."  ".$text['fail'];
                $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg );
            }

        }
   
    }

    public function search_job($jobid){
        $jobid = $_POST['jobid'] ?? null;
        if(!empty($jobid)){
            $res  = $this->jobModel->search_jobinfo($jobid);
            print_r($res);
        }
    }

    public function check_job_type(){
        $jobid = $_POST['new_jobid'] ?? null;
        if(!empty($jobid)){
            $res  = $this->jobModel->job_id_repeat($jobid);
            echo  $res;
        }
        
    }

    #copy 
    public function copy_job_data(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $old_jobid   = $_POST['old_jobid'] ?? null;
        $old_jobname = $_POST['old_jobname'] ?? null;
        $new_jobid   = $_POST['new_jobid'] ?? null;
        $new_jobname = $_POST['new_jobname'] ?? null;

        if(!empty($old_jobid)){
            $job_count = $this->jobModel->countjob();
            if($job_count >= 100) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $error_message['job_id']);
                exit();
            }else{
           
                $old_res = $this->jobModel->search_jobinfo($old_jobid);

                $this->jobModel->del_job_type($new_jobid);
                $this->jobModel->del_seq_type($new_jobid);
                $this->jobModel->del_step_type($new_jobid);
         
                if(!empty($old_res)){

                    #取得 unscrew_power && 	unscrew_rpm && unscrew_direction
                    $jobdata = array(
                        'job_id'      => $_POST['new_jobid'],
                        'job_name'    => $_POST['new_jobname'],
                        'ok_job'      => $old_res['ok_job'],
                        'ok_job_stop' => $old_res['ok_job_stop']

                    );
                    $res = $this->jobModel->create_job($jobdata);
                    //用job_id 找出對應的seq && step
                    $select_seq  = $this->jobModel->search_seqinfo($old_jobid); 
                    $select_step = $this->jobModel->search_stepnfo($old_jobid); 
                    
                    if(!empty($select_seq)){

                        $new_temp_seq = array();
                        foreach($select_seq as $key =>$val){
                 
                            $new_temp_seq[$key]['JOBID'] = $new_jobid;
                            $new_temp_seq[$key]['SEQID'] = $val['SEQID'];
                            $new_temp_seq[$key]['SEQname'] = $val['SEQname'];
                            $new_temp_seq[$key]['time'] = $val['time'];
                            $new_temp_seq[$key]['type'] = $val['type'];
                            $new_temp_seq[$key]['act'] = $val['act'];
                            $new_temp_seq[$key]['skip'] = $val['skip']; 
                            $new_temp_seq[$key]['seq_repeat'] = $val['seq_repeat']; 
                            $new_temp_seq[$key]['timeout'] = $val['timeout']; 
                            $new_temp_seq[$key]['ok_seq'] = $val['ok_seq']; 
                            $new_temp_seq[$key]['ok_stop'] = $val['ok_stop']; 
                            $new_temp_seq[$key]['countType'] = $val['countType'];
                            $new_temp_seq[$key]['ok_screw'] = $val['ok_screw'];
                            $new_temp_seq[$key]['ng_stop'] = $val['ng_stop'];
                            $new_temp_seq[$key]['ng_unscrew'] = $val['ng_unscrew'];
                            $new_temp_seq[$key]['interrupt_alarm'] = $val['interrupt_alarm'];
                            $new_temp_seq[$key]['accu_angle'] = $val['accu_angle'];
                            $new_temp_seq[$key]['Thread_Calcu'] = $val['Thread_Calcu'];
                            $new_temp_seq[$key]['unscrew_mode'] = $val['unscrew_mode'];
                            $new_temp_seq[$key]['unscrew_force'] = $val['unscrew_force'];
                            $new_temp_seq[$key]['unscrew_rpm'] = $val['unscrew_rpm'];
                            $new_temp_seq[$key]['unscrew_dir'] = $val['unscrew_dir'];
                            $new_temp_seq[$key]['image'] = $val['image'];
                            $new_temp_seq[$key]['message'] = $val['message'];
                            $new_temp_seq[$key]['delay'] = $val['delay'];
                            $new_temp_seq[$key]['input'] = $val['input'];
                            $new_temp_seq[$key]['input_signal'] = $val['input_signal'];
                            $new_temp_seq[$key]['output'] = $val['output'];
                            $new_temp_seq[$key]['output_signal'] = $val['output_signal'];
                            $new_temp_seq[$key]['output_durat'] = $val['output_durat'];
                            $new_temp_seq[$key]['addtion'] = $val['addtion'];
                            $new_temp_seq[$key]['unscrew_count_switch'] = $val['unscrew_count_switch'];
                            $new_temp_seq[$key]['unscrew_torque_threshold'] = $val['unscrew_torque_threshold'];
                            $nre_temp_seq[$key]['seq_unit'] = $val['seq_unit'];
                            $nre_temp_seq[$key]['unscrew_angle_threshold'] = $val['unscrew_angle_threshold'];
                            $nre_temp_seq[$key]['dt_time'] = $val['dt_time'];
                            $nre_temp_seq[$key]['tt_time'] = $val['tt_time'];
                            
                        }

                        $insertedrecords = $this->jobModel->copy_sequence_by_job_id($new_temp_seq);  
    
                    }

                    if(!empty($select_step)){
                        $new_temp_step = array();
                        $temp_step = array();
                        $temp_step = $select_step;
                       
                        foreach($temp_step as $k_step =>$v_step){

                            $new_temp_step[$k_step]['JOBID'] = $new_jobid;
                            $new_temp_step[$k_step]['SEQID'] = $v_step['SEQID'];
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
                      
                        $res = $this->jobModel->copy_step_by_job_id($new_temp_step);     
                    }
                    
                    if($res){
                        $res_msg = $text['Copy']."  ".$text['job_id'].':'. $_POST['new_jobid']."  ".$text['success'];
                        $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg );
                    }else{
                        $res_msg = $text['Copy']."  ".$text['job_id'].':'. $_POST['new_jobid']."  ".$text['fail'];
                        $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg );
                    }
                    
                }
            }
        
        }

    }


    public function getConvertedToolInfo(){

        $Tool_Info = $this->ToolModel->GetToolInfo();
        $Tool_Info = end($Tool_Info);

        $temp = [];

        if (!empty($Tool_Info)) {
            // 取得 Controller 設定的 torque unit
            $res_device = $this->ToolModel->GetControllerInfo();
            $device_torque_unit = (int)$res_device['torque_unit'];

            // 對應 torque 單位名稱
            $unit_arr = $this->MiscellaneousModel->details('torque_unit');
            $unit_name = $unit_arr[$device_torque_unit];

            // 從 DB 取出的 torque 值要先 /1000 (假設 DB 單位是 N.m)
            $minTorqueNm = (float)$Tool_Info['min_torque'] / 1000;
            $maxTorqueNm = ((float)$Tool_Info['max_torque'] / 1000) * 1.1;
       

            // 轉換所有 torque 單位
            $low_torque_arr = $this->MiscellaneousModel->convert_all_torque_units($minTorqueNm, 1,false);
            $high_torque_arr = $this->MiscellaneousModel->convert_all_torque_units($maxTorqueNm, 1,false);

            // 定義需要補正的單位、補正值、與小數位
            $corrections = [
                'kgf.m'  => ['add' => 0.0001, 'decimals' => 4],
                'kgf.cm' => ['add' => 0.01,   'decimals' => 2],
                'Lbf.in' => ['add' => 0.01,   'decimals' => 2],
                'N.m'    => ['add' => 0.001,  'decimals' => 3],
                'cN.m'   => ['add' => 0.1,    'decimals' => 1],

            ];

            foreach ($corrections as $unit => $info) {
                if (isset($high_torque_arr[$unit])) {
                    $value = (float)$high_torque_arr[$unit];
                    $value += $info['add'];
                    $value = $this->MiscellaneousModel->roundToNDecimals($value, $info['decimals']);
                    $high_torque_arr[$unit] = number_format($value, $info['decimals'], '.', '');
                }
            }

            // 存回 Tool_Info，僅保留目標單位
            $Tool_Info['min_torque'] = $low_torque_arr[$unit_name];
            $Tool_Info['max_torque'] = $high_torque_arr[$unit_name];
            $Tool_Info['torque_unit_name'] = $unit_name;

            // 產生 temp array
            $temp['torque'] = $Tool_Info['min_torque'];
            $temp['max_torque'] = $Tool_Info['max_torque'];

            // 計算小數位數
            $decimal_digits = $this->getDecimalDigits($Tool_Info['min_torque']);

            if ($decimal_digits > 0) {
                // 例如小數位數為 3 → 產生 0.001
                $temp['min_torque'] = "0." . str_repeat("0", $decimal_digits - 1) . "0";
            } else {
                $temp['min_torque'] = "0";
            }
        }

        return  $temp;
    }

    /**
     * 計算一個數字的小數位數
     * @param float|string $number
     * @return int
     */
    private function getDecimalDigits($number){

        $number = (string)$number;
        if (strpos($number, '.') !== false) {
            return strlen(substr(strrchr($number, '.'), 1));
        }
        return 0;
    }


}

?>
