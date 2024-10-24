<?php

class Step extends Controller
{
   
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        //$this->ToolModel = $this->model('Tool');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->stepModel = $this->model('Steptcc');
        $this->sequenceModel = $this->model('Sequence');
        $this->SettingModel = $this->model('Setting');
        
    }

    public function index($job_id,$seq_id){
        if( isset($job_id) && !empty($job_id)  && isset($seq_id) && !empty($seq_id)){

        }else{
            $job_id = 1;
            $seq_id = 1;
        }

        $isMobile = $this->isMobileCheck();
        $step = $this->stepModel->getStep($job_id, $seq_id);
        $target_option = $this->MiscellaneousModel->details("target_option");
        $torque_unit   = $this->MiscellaneousModel->details("torque_unit");
        $target_option_change = $this->MiscellaneousModel->details("target_option_change");
        $direction = $this->MiscellaneousModel->details('reverse_direction');
        $unit_arr  = $this->MiscellaneousModel->details('torque_unit');
        $seqinfo   = $this->sequenceModel->search_seqinfo($job_id,$seq_id);
       

        $res_device = $this->SettingModel->GetControllerInfo();
        if(!empty($res_device)){
            $unit = $res_device['torque_unit'];
            $unit_name = $torque_unit[$unit];
        }
        
        if(empty($step)){
            $stepid_new = 1;
        }else{
            $stepid_new = count($step) + 1 ;
        }

       

        
        $data = array(
            'isMobile' => $isMobile,
            'step' => $step,
            'target_option' => $target_option,
            'target_option_change' =>$target_option_change,
            'direction' => $direction,
            'job_id' => $job_id,
            'seq_id' => $seq_id,
            'stepid_new' => $stepid_new,
            'unit_arr' => $unit_arr,
            'unit' => $unit,
            'seq_id' => $seq_id,
            'unit_name' => $unit_name

        );
        if($isMobile){
            $this->view('step/index_m', $data);
        }else{
            $this->view('step/index', $data);
        }
        
        
    }

    public function create_step(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if(isset($_POST['JOBID'])){

            $JOBID = isset($_POST['JOBID']) ? intval($_POST['JOBID']) : 0;
            $SEQID = isset($_POST['SEQID']) ? intval($_POST['SEQID']) : 0;
            $StepSelect = isset($_POST['StepSelect']) ? intval($_POST['StepSelect']) : 0;
            $STEPname = $_POST['STEPname'] ?? '';
            $time = $_POST['time'] ?? '';
            
            $StepRPM = isset($_POST['StepRPM']) ? intval($_POST['StepRPM']) : 0;
            $StepOption = isset($_POST['StepOption']) ? intval($_POST['StepOption']) : -1;
            $StepDirection = isset($_POST['StepDirection']) ? intval($_POST['StepDirection']) : 0;
            $StepDelay = isset($_POST['StepDelay']) ? intval($_POST['StepDelay']) : 0;
            $StepMoniByWin = isset($_POST['StepMoniByWin']) ? intval($_POST['StepMoniByWin']) : -1;
            $StepLimiHi = isset($_POST['StepLimiHi']) ? intval($_POST['StepLimiHi']) : '';
            $StepLimiLo = isset($_POST['StepLimiLo']) ? intval($_POST['StepLimiLo']) : '';
            $StepHiAngle = isset($_POST['StepHiAngle']) ? intval($_POST['StepHiAngle']) : '';
            $StepLoAngle = isset($_POST['StepLoAngle']) ? intval($_POST['StepLoAngle']) : '';
            $StepHiTorque = isset($_POST['StepHiTorque']) ? floatval($_POST['StepHiTorque']) : '';
            $StepLoTorque = isset($_POST['StepLoTorque']) ? floatval($_POST['StepLoTorque']) : '';
            $StepAccelerateOffset = isset($_POST['StepAccelerateOffset']) ? intval($_POST['StepAccelerateOffset']) : 43;//待確認
            $StepAccelerateOffsetSign = isset($_POST['StepAccelerateOffsetSign']) ? intval($_POST['StepAccelerateOffsetSign']) : 0; //待確認
            $StepTorqueOffset = isset($_POST['StepTorqueOffset']) ? intval($_POST['StepTorqueOffset']) : 43; 
            $StepTorqueOffsetSign = isset($_POST['StepTorqueOffsetSign']) ? intval($_POST['StepTorqueOffsetSign']) : 0;
            $StepEnableTorqueOffset = isset($_POST['StepEnableTorqueOffset']) ? intval($_POST['StepEnableTorqueOffset']) : 0; //待確認
            $StepEnableDownShift =  isset($_POST['StepEnableDownShift']) ? intval($_POST['StepEnableDownShift']) : 0;
            $StepTorqueDownShift =  isset($_POST['StepTorqueDownShift']) ? intval($_POST['StepTorqueDownShift']) : 0; 
            $StepRPMDownShift = isset($_POST['StepRPMDownShift']) ? intval($_POST['StepRPMDownShift']) : 0; 
            $StepTorqueTS = isset($_POST['StepTorqueTS']) ? floatval($_POST['StepTorqueTS']) : 0;
            $StepEnableThreshold = isset($_POST['StepEnableThreshold']) ? intval($_POST['StepEnableThreshold']) : 0; 
            $InterruptAlarm = isset($_POST['InterruptAlarm']) ? intval($_POST['InterruptAlarm']) : 0; 
            $OverAngleStop =  isset($_POST['OverAngleStop']) ? intval($_POST['OverAngleStop']) : 0; 
            //初始化 
            $StepTorque = '';
            $StepAngle  = '';
            $StepTime   = '';

            switch ($StepOption) {
                case 0:
                    $StepTorque = $_POST['StepTorque'] ?? '';
                    break;
                case 1:
                    $StepAngle = $_POST['StepTorque'] ?? '';
                    break;
                case 2:
                    $StepTime = $_POST['StepTorque'] ?? '';
                    break;
            }



            $step_data = array(
                'JOBID'  => $JOBID,
                'SEQID'  => $SEQID,
                'StepSelect' => $StepSelect,
                'STEPname' => $STEPname,
                'type'  => 0, //待確認
                'time'  => $time,
                'act' => 0, //待確認
                'StepSwitch' => 1, //待確認
                'StepRPM' => $StepRPM,
                'StepOption' => $StepOption,
                'StepTime' => $StepTime,
                'StepAngle' => $StepAngle,
                'StepTorque' => $StepTorque,
                'StepDirection' => $StepDirection,
                'StepDelay' => $StepDelay,
                'StepMoniByWin' => $StepMoniByWin,
                'StepLimiHi' => $StepLimiHi,
                'StepLimiLo' => $StepLimiLo,
                'StepHiAngle' => $StepHiAngle,
                'StepLoAngle' => $StepLoAngle,
                'StepHiTorque' => $StepHiTorque,
                'StepLoTorque' => $StepLoTorque,
                'StepAccelerateOffset' =>$StepAccelerateOffset,
                'StepAccelerateOffsetSign' => $StepAccelerateOffsetSign,
                'StepEnableTorqueOffset' => $StepEnableTorqueOffset,
                'StepTorqueOffset' => $StepTorqueOffset,
                'StepTorqueOffsetSign' => $StepTorqueOffsetSign,
                'StepEnableDownShift' => $StepEnableDownShift,
                'StepTorqueDownShift' => $StepTorqueDownShift,
                'StepRPMDownShift' => $StepRPMDownShift,
                'StepTorqueTS' => $StepTorqueTS,
                'StepEnableThreshold' => $StepEnableThreshold,
                'StepReTry' => 1, //待確認
                'StepUnScrew' => 1, //待確認
                'StepReTryTorq' => 0,//待確認
                'StepReTryAngl' => 0,//待確認
                'StepAngleRecord' => 0, //待確認
                'StepAutoDetectAngle' => 0,//待確認
                'InterruptAlarm' => $InterruptAlarm,
                'OverAngleStop' => $OverAngleStop

            );           
            
            $res = $this->stepModel->create_step($step_data);
            $result = array();
            if($res){
                $res_type = 'Success';
                $res_msg = $text['new_step'].':'.$step_data['StepSelect']."  ".$text['success'];
                $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
            }else{
                $res_type = 'Error';
                $res_msg = $text['new_step'].':'.$step_data['StepSelect']."  ".$text['fail'];
                $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
            }
        }

    }

    public function edit_step(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if(isset($_POST['JOBID'])){

            $JOBID = isset($_POST['JOBID']) ? intval($_POST['JOBID']) : 0;
            $SEQID = isset($_POST['SEQID']) ? intval($_POST['SEQID']) : 0;
            $StepSelect = isset($_POST['StepSelect']) ? intval($_POST['StepSelect']) : 0;
            $STEPname = $_POST['STEPname'] ?? '';
            $time = $_POST['time'] ?? '';
            
            $StepRPM = isset($_POST['StepRPM']) ? intval($_POST['StepRPM']) : 0;
            $StepOption = isset($_POST['StepOption']) ? intval($_POST['StepOption']) : -1;
            $StepDirection = isset($_POST['StepDirection']) ? intval($_POST['StepDirection']) : 0;
            $StepDelay = isset($_POST['StepDelay']) ? intval($_POST['StepDelay']) : 0;
            $StepMoniByWin = isset($_POST['StepMoniByWin']) ? intval($_POST['StepMoniByWin']) : -1;
            $StepLimiHi = isset($_POST['StepLimiHi']) ? intval($_POST['StepLimiHi']) : '';
            $StepLimiLo = isset($_POST['StepLimiLo']) ? intval($_POST['StepLimiLo']) : '';
            $StepHiAngle = isset($_POST['StepHiAngle']) ? intval($_POST['StepHiAngle']) : '';
            $StepLoAngle = isset($_POST['StepLoAngle']) ? intval($_POST['StepLoAngle']) : '';
            $StepHiTorque = isset($_POST['StepHiTorque']) ? floatval($_POST['StepHiTorque']) : '';
            $StepLoTorque = isset($_POST['StepLoTorque']) ? floatval($_POST['StepLoTorque']) : '';
            $StepAccelerateOffset = isset($_POST['StepAccelerateOffset']) ? intval($_POST['StepAccelerateOffset']) : 43;//待確認
            $StepAccelerateOffsetSign = isset($_POST['StepAccelerateOffsetSign']) ? intval($_POST['StepAccelerateOffsetSign']) : 0; //待確認
            $StepTorqueOffset = isset($_POST['StepTorqueOffset']) ? intval($_POST['StepTorqueOffset']) : 43; 
            $StepTorqueOffsetSign = isset($_POST['StepTorqueOffsetSign']) ? intval($_POST['StepTorqueOffsetSign']) : 0;
            $StepEnableTorqueOffset = isset($_POST['StepEnableTorqueOffset']) ? intval($_POST['StepEnableTorqueOffset']) : 0; //待確認
            $StepEnableDownShift =  isset($_POST['StepEnableDownShift']) ? intval($_POST['StepEnableDownShift']) : 0;
            $StepTorqueDownShift =  isset($_POST['StepTorqueDownShift']) ? intval($_POST['StepTorqueDownShift']) : 0; 
            $StepRPMDownShift = isset($_POST['StepRPMDownShift']) ? intval($_POST['StepRPMDownShift']) : 0; 
            $StepTorqueTS = isset($_POST['StepTorqueTS']) ? floatval($_POST['StepTorqueTS']) : 0;
            $StepEnableThreshold = isset($_POST['StepEnableThreshold']) ? intval($_POST['StepEnableThreshold']) : 0; 
            $InterruptAlarm = isset($_POST['InterruptAlarm']) ? intval($_POST['InterruptAlarm']) : 0; 
            $OverAngleStop =  isset($_POST['OverAngleStop']) ? intval($_POST['OverAngleStop']) : 0; 
            //初始化 
            $StepTorque = '';
            $StepAngle  = '';
            $StepTime   = '';

            switch ($StepOption) {
                case 0:
                    $StepTorque = $_POST['StepTorque'] ?? '';
                    break;
                case 1:
                    $StepAngle = $_POST['StepTorque'] ?? '';
                    break;
                case 2:
                    $StepTime = $_POST['StepTorque'] ?? '';
                    break;
            }



            $step_data = array(
                'JOBID'  => $JOBID,
                'SEQID'  => $SEQID,
                'StepSelect' => $StepSelect,
                'STEPname' => $STEPname,
                'type'  => 0, //待確認
                'time'  => $time,
                'act' => 0, //待確認
                'StepSwitch' => 1, //待確認
                'StepRPM' => $StepRPM,
                'StepOption' => $StepOption,
                'StepTime' => $StepTime,
                'StepAngle' => $StepAngle,
                'StepTorque' => $StepTorque,
                'StepDirection' => $StepDirection,
                'StepDelay' => $StepDelay,
                'StepMoniByWin' => $StepMoniByWin,
                'StepLimiHi' => $StepLimiHi,
                'StepLimiLo' => $StepLimiLo,
                'StepHiAngle' => $StepHiAngle,
                'StepLoAngle' => $StepLoAngle,
                'StepHiTorque' => $StepHiTorque,
                'StepLoTorque' => $StepLoTorque,
                'StepAccelerateOffset' =>$StepAccelerateOffset,
                'StepAccelerateOffsetSign' => $StepAccelerateOffsetSign,
                'StepEnableTorqueOffset' => $StepEnableTorqueOffset,
                'StepTorqueOffset' => $StepTorqueOffset,
                'StepTorqueOffsetSign' => $StepTorqueOffsetSign,
                'StepEnableDownShift' => $StepEnableDownShift,
                'StepTorqueDownShift' => $StepTorqueDownShift,
                'StepRPMDownShift' => $StepRPMDownShift,
                'StepTorqueTS' => $StepTorqueTS,
                'StepEnableThreshold' => $StepEnableThreshold,
                'StepReTry' => 1, //待確認
                'StepUnScrew' => 1, //待確認
                'StepReTryTorq' => 0,//待確認
                'StepReTryAngl' => 0,//待確認
                'StepAngleRecord' => 0, //待確認
                'StepAutoDetectAngle' => 0,//待確認
                'InterruptAlarm' => $InterruptAlarm,
                'OverAngleStop' => $OverAngleStop

            );           

            

            $res = $this->stepModel->update_step_by_id($step_data);
            $result = array();
            if($res){
                $res_type = 'Success';
                $res_msg  = $text['edit_step'].':'. $_POST['StepSelect']."  ".$text['success'];
            }else{
                $res_type = 'Error';
                $res_msg  = $text['edit_step'].':'. $_POST['StepSelect']."  ".$text['fail'];
            }
            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg 
            );

            echo json_encode($result);
        }
    }


    public function delete_step(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }
        
        if(isset($_POST['stepid'])){
            
            $jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : '';
            $seqid = isset($_POST['seqid']) ? intval($_POST['seqid']) : '';
            $stepid = isset($_POST['stepid']) ? intval($_POST['stepid']) : '';    
            
            if(!empty($stepid)){
                $res = $this->stepModel->delete_step_id($jobid, $seqid, $stepid);
                $result = array();
                if($res){
                    $res_type = 'Success';
                    $res_msg = $text['del_step'].':'. $stepid."  ".$text['success'];
                    $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
                }else{
                    $res_type = 'Error';
                    $res_msg = $text['del_step'].':'. $stepid."  ".$text['fail'];
                    $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
                }

            }
      
        }
    
    }

    public function copy_step(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if(isset($_POST['jobid'])){

            #如果 POST 中沒有，則使用預設值
            $jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : '';
            $seqid = isset($_POST['seqid']) ? intval($_POST['seqid']) : '';
            $stepid = isset($_POST['stepid']) ? intval($_POST['stepid']) : 0;
            $stepid_new = isset($_POST['stepid_new']) ? intval($_POST['stepid_new']) : '';
            $step_count = $this->stepModel->countstep($jobid, $seqid);
            $step_count = intval($step_count);

              
            $old_res= $this->stepModel->getStepNo($jobid,$seqid,$stepid);
      
            if(!empty($old_res)){
                $step_data = array(
                    'JOBID'                    => $jobid,
                    'SEQID'                    => $seqid,
                    'StepSelect'               => $stepid_new,
                    'STEPname'                 => $old_res[0]['STEPname'],
                    'type'                     => $old_res[0]['type'],
                    'time'                     => $old_res[0]['time'],
                    'act'                      => $old_res[0]['act'],
                    'StepSwitch'               => $old_res[0]['StepSwitch'],
                    'StepRPM'                  => $old_res[0]['StepRPM'],
                    'StepOption'               => $old_res[0]['StepOption'],
                    'StepTime'                 => $old_res[0]['StepTime'],
                    'StepAngle'                => $old_res[0]['StepAngle'],
                    'StepTorque'               => $old_res[0]['StepTorque'],
                    'StepDirection'            => $old_res[0]['StepDirection'],
                    'StepDelay'                => $old_res[0]['StepDelay'],
                    'StepMoniByWin'            => $old_res[0]['StepMoniByWin'],
                    'StepLimiHi'               => $old_res[0]['StepLimiHi'],
                    'StepLimiLo'               => $old_res[0]['StepLimiLo'],
                    'StepHiAngle'              => $old_res[0]['StepHiAngle'],
                    'StepLoAngle'              => $old_res[0]['StepLoAngle'],
                    'StepHiTorque'             => $old_res[0]['StepHiTorque'],
                    'StepLoTorque'             => $old_res[0]['StepLoTorque'],
                    'StepAccelerateOffset'     => $old_res[0]['StepAccelerateOffset'],
                    'StepAccelerateOffsetSign' => $old_res[0]['StepAccelerateOffsetSign'],
                    'StepEnableTorqueOffset'   => $old_res[0]['StepEnableTorqueOffset'],
                    'StepTorqueOffset'         => $old_res[0]['StepTorqueOffset'],
                    'StepTorqueOffsetSign'     => $old_res[0]['StepTorqueOffsetSign'],
                    'StepEnableDownShift'      => $old_res[0]['StepEnableDownShift'],
                    'StepTorqueDownShift'      => $old_res[0]['StepTorqueDownShift'],
                    'StepRPMDownShift'         => $old_res[0]['StepRPMDownShift'],
                    'StepEnableThreshold'      => $old_res[0]['StepEnableThreshold'],
                    'StepTorqueTS'             => $old_res[0]['StepTorqueTS'],
                    'StepReTry'                => $old_res[0]['StepReTry'],
                    'StepUnScrew'              => $old_res[0]['StepUnScrew'],
                    'StepReTryTorq'            => $old_res[0]['StepReTryTorq'],
                    'StepReTryAngl'            => $old_res[0]['StepReTryAngl'],
                    'StepAngleRecord'          => $old_res[0]['StepAngleRecord'],
                    'StepAutoDetectAngle'      => $old_res[0]['StepAutoDetectAngle'],
                    'InterruptAlarm'           => $old_res[0]['InterruptAlarm'],
                    'OverAngleStop'            => $old_res[0]['OverAngleStop'],

                );

        


                $res = $this->stepModel->create_step($step_data);
                if($res){
                    $res_type = 'Success';
                    $res_msg  = $text['copy_step'].':'.$stepid_new."  ".$text['success'];
                    $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
                }else{
                    $res_type = 'Error';
                    $res_msg  = $text['copy_step'].':'.$stepid_new."  ".$text['fail'];
                    $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
                }


            }
          
        }

    }

    #查詢step data
    public function search_stepinfo(){

        
        $input_check = true;
        if(!empty($_POST['jobid']) && isset($_POST['jobid'])){
            $jobid = $_POST['jobid'];
        }else{
            $input_check = false; 
        }

        if(!empty($_POST['seqid']) && isset($_POST['seqid'])){
            $seqid = $_POST['seqid'];
        }else{
            $input_check = false; 
        }


        if(!empty($_POST['stepid']) && isset($_POST['stepid'])){
            $stepid  = $_POST['stepid'];
        }else{
            $input_check = false; 
        }

        if($input_check){

            $check = $this->stepModel->check_step_target($jobid, $seqid);
            $check_count = intval($check[0]['count_records']);

            $res = $this->stepModel->getStepNo($jobid, $seqid, $stepid);
            $res['check_count'] = $check_count;
            print_r($res);
        }

    }
        
    #排序step
    public function adjustment_order(){

        if (isset($_POST['JOBID']) && isset($_POST['rowInfoArray'])) {
            $JOBID = $_POST['JOBID'];
            $rowInfoArray = $_POST['rowInfoArray'];

            $this->stepModel->swapupdate($JOBID,$rowInfoArray);
        } else {
            
        }
        
    }

    public function variation($job_id = null, $seq_id = null,$stepid =null) {

        $job_id  = $job_id ?? $_GET['job_id'] ?? null;
        $seq_id  = $seq_id ?? $_GET['seq_id'] ?? null;
        $stepid  = $stepid ?? $_GET['step_id'] ?? null; 

        // 计算参数数量
        $paramsCount = 0;
        if (!empty($job_id)) $paramsCount++;
        if (!empty($seq_id)) $paramsCount++;
        if (!empty($stepid)) $paramsCount++;


        if($paramsCount === 2) {
            $type = 'new';
            $job_id = htmlspecialchars($job_id);
            $seq_id = htmlspecialchars($seq_id);
            $res = $this->stepModel->getStep_count($job_id, $seq_id);
            $StepSelect = $res[0]['total'] + 1;

        } else  {
            $type = 'edit';
            $job_id = htmlspecialchars($job_id);
            $seq_id = htmlspecialchars($seq_id);
            $StepSelect = htmlspecialchars($stepid);
            $res = $this->stepModel->getStepNo($job_id, $seq_id, $stepid);
            $step = $res[0];
        } 

        $data = array(
            'JOBID' => $job_id,
            'SEQID' => $seq_id,
            'StepSelect' => $StepSelect,
            'type' => $type
        );

        //如果是 
        if ($type == 'edit') {
            $data['step'] = $step; 
        }
        

        echo $this->view('step/add_step',$data);
    }


}