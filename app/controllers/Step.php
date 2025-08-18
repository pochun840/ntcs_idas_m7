<?php

class Step extends Controller
{
   
    // 在建構子中將 Post 物件（Model）實例化
    private $MiscellaneousModel;
    private $stepModel;
    private $sequenceModel;
    private $SettingModel;
    private $ToolModel;
    public function __construct()
    {
        //$this->ToolModel = $this->model('Tool');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->stepModel = $this->model('Steptcc');
        $this->sequenceModel = $this->model('Sequence');
        $this->SettingModel = $this->model('Setting');
        $this->ToolModel = $this->model('Tool');
        
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
        $target_option_change = $this->MiscellaneousModel->details("target_option");
        $direction = $this->MiscellaneousModel->details('reverse_direction');
        $unit_arr  = $this->MiscellaneousModel->details('torque_unit');
        $seqinfo   = $this->sequenceModel->search_seqinfo($job_id,$seq_id);
        $specs     = $this->MiscellaneousModel->getToolSpecifications();

        $total_step = count($step);
        $this->ntcs_data_db_sysnc();

        
        if(empty($step)){
            $stepid_new = 1;
        }else{
            $stepid_new = count($step) + 1 ;
        }

        $torque_unit   = $this->MiscellaneousModel->details("torque_unit");
        $res_device = $this->SettingModel->GetControllerInfo();
        if(!empty($res_device)){
            $unit = $res_device['torque_unit'];
            $unit_name = $torque_unit[$unit];
        }
    
        $data = array(
            'isMobile' => $isMobile,
            'step' => $step,
            'target_option' => $target_option_change,
            'target_option_change' =>$target_option_change,
            'direction' => $direction,
            'job_id' => $job_id,
            'seq_id' => $seq_id,
            'stepid_new' => $stepid_new,
            'unit_arr' => $unit_arr,
            'unit' => $unit,
            'seq_id' => $seq_id,
            'unit_name' => $unit_name,
            'specs' => $specs,
            'total_step' => $total_step

        );


        if($isMobile){
            $this->view('step/index_m', $data);
        }else{
            $this->view('step/index', $data);
        }
        
        
    }
    
    public function create_step() {
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $decimals_arr = $this->MiscellaneousModel->details("decimals");


        if (isset($_POST['JOBID'])) {
            $JOBID = intval($_POST['JOBID'] ?? 0);
            $SEQID = intval($_POST['SEQID'] ?? 0);
            $StepEnableThreshold = intval($_POST['StepEnableThreshold'] ?? 0);
            $StepEnableDownShift = intval($_POST['StepEnableDownShift'] ?? 0);

            // ✅ 若這個 step 啟用了 threshold（≠ 0），先重設前面所有 step 的 threshold 為 0
            if ($StepEnableThreshold !== 0) {
                $this->stepModel->resetAllThresholds($JOBID, $SEQID);
            }

            // ✅ 若這個 step 啟用了 DownShift，先重設所有 step 的 DownShift 設定為 0
            if ($StepEnableDownShift !== 0) {
                $this->stepModel->resetAllDownShifts($JOBID, $SEQID);
            }



            // 🔽 繼續原本流程...
            $step_data = [
                'JOBID' => $JOBID,
                'SEQID' => $SEQID,
                'StepSelect' => intval($_POST['StepSelect'] ?? 0),
                'STEPname' => $_POST['STEPname'] ?? '',
                'type' => 0,
                'time' => $_POST['time'] ?? '',
                'act' => 0,
                'StepSwitch' => 1,
                'StepRPM' => intval($_POST['StepRPM'] ?? 0),
                'StepOption' => intval($_POST['StepOption'] ?? -1),
                'StepTime' => intval($_POST['StepTime'] ?? 1000),
                'StepAngle' => intval($_POST['StepAngle'] ?? 0),
                'StepTorque' => floatval($_POST['StepTorque'] ?? 0),
                'StepDirection' => intval($_POST['StepDirection'] ?? 0),
                'StepDelay' => ($_POST['StepDelay'] ?? 0) > 0 ? intval($_POST['StepDelay'] * 1000) : 0,
                'StepMoniByWin' => intval($_POST['StepMoniByWin'] ?? -1),
                'StepLimiHi' => intval($_POST['StepLimiHi'] ?? 0),
                'StepLimiLo' => intval($_POST['StepLimiLo'] ?? 0),
                'StepHiAngle' => intval($_POST['StepHiAngle'] ?? 0),
                'StepLoAngle' => intval($_POST['StepLoAngle'] ?? 0),
                'StepHiTorque' => floatval($_POST['StepHiTorque'] ?? 0),
                'StepLoTorque' => floatval($_POST['StepLoTorque'] ?? 0),
                'StepAccelerateOffset' => intval($_POST['StepAccelerateOffset'] ?? 0.2),
                'StepAccelerateOffsetSign' => intval($_POST['StepAccelerateOffsetSign'] ?? 43),
                'StepEnableTorqueOffset' => intval($_POST['StepEnableTorqueOffset'] ?? 0),
                'StepTorqueOffset' =>  round(floatval($_POST['StepTorqueOffset'] ?? 0),3),
                'StepTorqueOffsetSign' => intval($_POST['StepTorqueOffsetSign'] ?? 0),
                'StepEnableDownShift' => intval($_POST['StepEnableDownShift'] ?? 0),
                'StepTorqueDownShift' => number_format(round(floatval($_POST['StepTorqueDownShift'] ?? 0), 3), 3, '.', ''),
                'StepRPMDownShift' => intval($_POST['StepRPMDownShift'] ?? 0),
                'StepTorqueTS' => number_format(round(floatval($_POST['StepTorqueTS'] ?? 0), 3), 3, '.', ''),
                'StepEnableThreshold' => $StepEnableThreshold,
                'StepReTry' => 1,
                'StepUnScrew' => 1,
                'StepReTryTorq' => 0,
                'StepReTryAngl' => 0,
                'StepAngleRecord' => 0,
                'StepAutoDetectAngle' => 0,
                'InterruptAlarm' => intval($_POST['InterruptAlarm'] ?? 1),
                'OverAngleStop' => intval($_POST['OverAngleStop'] ?? 1),
                'KValue' => round(floatval($_POST['KValue'] ?? 0), 1),
                'step_unit' => intval($_POST['step_unit'] ?? 0)
            ];


            if(!empty($step_data)){
                $decimals_arr = $this->MiscellaneousModel->details("decimals");
                $places = $decimals_arr[$_POST['step_unit'] ?? 2] ?? 2;

                $step_data['StepTorque']   = number_format(round((float)($_POST['StepTorque'] ?? 0),   $places), $places, '.', '');
                $step_data['StepHiTorque'] = number_format(round((float)($_POST['StepHiTorque'] ?? 0), $places), $places, '.', '');
                $step_data['StepLoTorque'] = number_format(round((float)($_POST['StepLoTorque'] ?? 0), $places), $places, '.', '');

                if($StepEnableThreshold == 2){
                    $step_data['StepTorqueTS']   = number_format(round((float)($_POST['StepTorqueTS'] ?? 0),   $places), $places, '.', '');
                }

                if($step_data['StepEnableDownShift'] == 2){
                    $step_data['StepTorqueDownShift']   = number_format(round((float)($_POST['StepTorqueDownShift'] ?? 0),   $places), $places, '.', '');
                }

            }

            $res = $this->stepModel->create_step($step_data);
            $text = $text ?? [];
            $res_type = $res ? 'Success' : 'Error';
            $res_msg = $text['new_step'] . ':' . $step_data['StepSelect'] . ($res ? ' ' . $text['success'] : ' ' . $text['fail']);

            $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
        }
    }




    public function edit_step(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $decimals_arr = $this->MiscellaneousModel->details("decimals");


        if(isset($_POST['JOBID'])){

            $JOBID = isset($_POST['JOBID']) ? intval($_POST['JOBID']) : 0;
            $SEQID = isset($_POST['SEQID']) ? intval($_POST['SEQID']) : 0;
            $StepSelect = isset($_POST['StepSelect']) ? intval($_POST['StepSelect']) : 0;

            //$StepTorqueTS = isset($_POST['StepTorqueTS']) ? round(floatval($_POST['StepTorqueTS']), 1) : 0;
            $StepEnableThreshold = isset($_POST['StepEnableThreshold']) ? intval($_POST['StepEnableThreshold']) : 0; 


            // 當前 step 有設定 Threshold
            if ($StepEnableThreshold > 0) {
                // 查詢之前有啟用 threshold 的 step（排除自己）
                $prev_steps = $this->stepModel->getPreviousStepsWithThreshold($JOBID, $SEQID, $StepSelect);

                if (!empty($prev_steps)) {
                    // 將之前的步驟全部清除 threshold 設定
                    $this->stepModel->resetPreviousStepsThreshold($JOBID, $SEQID, $prev_steps[0]['StepSelect']);
                }
            }

            

            $step_data = [
                    'JOBID' => $JOBID,
                    'SEQID' => $SEQID,
                    'StepSelect' => intval($_POST['StepSelect'] ?? 0),
                    'STEPname' => $_POST['STEPname'] ?? '',
                    'type' => 0,
                    'time' => $_POST['time'] ?? '',
                    'act' => 0,
                    'StepSwitch' => 1,
                    'StepRPM' => intval($_POST['StepRPM'] ?? 0),
                    'StepOption' => intval($_POST['StepOption'] ?? -1),
                    'StepTime' => intval($_POST['StepTime'] ?? 1000),
                    'StepAngle' => intval($_POST['StepAngle'] ?? 0),
                    'StepTorque' => floatval($_POST['StepTorque'] ?? 0),
                    'StepDirection' => intval($_POST['StepDirection'] ?? 0),
                    'StepDelay' => (int) round(((float)($_POST['StepDelay'] ?? 0)) * 1000, 0, PHP_ROUND_HALF_UP),
                    'StepMoniByWin' => intval($_POST['StepMoniByWin'] ?? 0),
                    'StepLimiHi' => intval($_POST['StepLimiHi'] ?? 0),
                    'StepLimiLo' => intval($_POST['StepLimiLo'] ?? 0),
                    'StepHiAngle' => intval($_POST['StepHiAngle'] ?? 0),
                    'StepLoAngle' => intval($_POST['StepLoAngle'] ?? 0),
                    'StepHiTorque' => floatval($_POST['StepHiTorque'] ?? 0),
                    'StepLoTorque' => floatval($_POST['StepLoTorque'] ?? 0),
                    'StepAccelerateOffset' => intval($_POST['StepAccelerateOffset'] ?? 43),
                    'StepAccelerateOffsetSign' => intval($_POST['StepAccelerateOffsetSign'] ?? 0),
                    'StepEnableTorqueOffset' => intval($_POST['StepEnableTorqueOffset'] ?? 0),
                    'StepTorqueOffset' =>  round(floatval($_POST['StepTorqueOffset'] ?? 0),3),
                    'StepTorqueOffsetSign' => intval($_POST['StepTorqueOffsetSign'] ?? 0),
                    'StepEnableDownShift' => intval($_POST['StepEnableDownShift'] ?? 0),
                    'StepTorqueDownShift' => number_format(round(floatval($_POST['StepTorqueDownShift'] ?? 0), 3), 3, '.', ''),
                    'StepRPMDownShift' => intval($_POST['StepRPMDownShift'] ?? 0),
                    'StepTorqueTS' => number_format(round(floatval($_POST['StepTorqueTS'] ?? 0), 3), 3, '.', ''),
                    'StepEnableThreshold' => $StepEnableThreshold,
                    'StepReTry' => 0,
                    'StepUnScrew' => 1,
                    'StepReTryTorq' => 0,
                    'StepReTryAngl' => 0,
                    'StepAngleRecord' => 0,
                    'StepAutoDetectAngle' => 0,
                    'InterruptAlarm' => intval($_POST['InterruptAlarm'] ?? 1),
                    'OverAngleStop' => intval($_POST['OverAngleStop'] ?? 1),
                    'KValue' => round(floatval($_POST['KValue'] ?? 0), 2),
                    'step_unit' => intval($_POST['step_unit'] ?? 0)
                ];

           
            if(!empty($step_data)){
                $decimals_arr = $this->MiscellaneousModel->details("decimals");
                $places = $decimals_arr[$_POST['step_unit'] ?? 2] ?? 2;

                $step_data['StepTorque']   = number_format(round((float)($_POST['StepTorque'] ?? 0),   $places), $places, '.', '');
                $step_data['StepHiTorque'] = number_format(round((float)($_POST['StepHiTorque'] ?? 0), $places), $places, '.', '');
                $step_data['StepLoTorque'] = number_format(round((float)($_POST['StepLoTorque'] ?? 0), $places), $places, '.', '');

                if($StepEnableThreshold == 2){
                    $step_data['StepTorqueTS']   = number_format(round((float)($_POST['StepTorqueTS'] ?? 0),   $places), $places, '.', '');
                }

                if($step_data['StepEnableDownShift'] == 2){
                    $step_data['StepTorqueDownShift']   = number_format(round((float)($_POST['StepTorqueDownShift'] ?? 0),   $places), $places, '.', '');
                }


            }
      

            $res = $this->stepModel->update_step_by_id($step_data);

            $result = array(
                'res_type' => $res ? 'Success' : 'Error',
                'res_msg'  => $text['edit_step'] . ':' . $_POST['StepSelect'] . ($res ? "  " . $text['success'] : "  " . $text['fail'])
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
                    'KValue'                   => $old_res[0]['KValue'],
                    'step_unit'                => $old_res[0]['step_unit'],

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

    public function variation($job_id = null, $seq_id = null, $stepid = null){
        
        $job_id = $job_id ?? $_GET['job_id'] ?? null;
        $seq_id = $seq_id ?? $_GET['seq_id'] ?? null;
        $stepid = $stepid ?? $_GET['step_id'] ?? null;

        $step = $this->stepModel->getStep($job_id, $seq_id);
        $next_step_id = empty($step) ? 1 : count($step) + 1;

        $torque_arr = $this->MiscellaneousModel->details("torque_unit");
        $decimals_arr = $this->MiscellaneousModel->details("decimals");

        $res_device = $this->SettingModel->GetControllerInfo();
        $device_torque_unit = (int)$res_device['torque_unit'];


        $paramsCount = 0;
        if (!empty($job_id)) $paramsCount++;
        if (!empty($seq_id)) $paramsCount++;
        if (!empty($stepid)) $paramsCount++;



        $type = ($paramsCount === 2) ? 'new' : 'edit';

        $job_id = htmlspecialchars($job_id);
        $seq_id = htmlspecialchars($seq_id);
        $StepSelect = $type === 'new'
            ? $this->stepModel->getStep_count($job_id, $seq_id)[0]['total'] + 1
            : htmlspecialchars($stepid);

        if ($type === 'edit') {
            $res = $this->stepModel->getStepNo($job_id, $seq_id, $stepid);
            $step = $res[0];

            if($step['StepEnableThreshold'] == 2){

                $res1= $this->MiscellaneousModel->convert_step_torque( $step['StepTorqueTS'],$step['step_unit'],$device_torque_unit); 
                if(!empty($res1)){
                    $step['StepTorqueTS'] = $res1['converted_value'];
                }
            }
            if($step['StepEnableDownShift'] == 2){
                $res = $this->MiscellaneousModel->convert_step_torque($step['StepTorqueDownShift'], $step['step_unit'], $device_torque_unit);
                if(!empty($res)){
                    $step['StepTorqueDownShift'] = $res['converted_value'];
                }
            }

            if ($step['StepDelay'] > 0) {
                $step['StepDelay'] = $step['StepDelay'] / 1000;
            }
                    
        } else {
            $step = [];
        }



        $tools = $this->ToolModel->GetToolInfo()[0] ?? [];
        if (!empty($tools)) {
            foreach (['max_rpm', 'min_rpm'] as $key) {
                if (isset($tools[$key])) {
                    $tools[$key] = rtrim(rtrim($tools[$key], '0'), '.');
                }
            }
        }

        if ($type === 'new') {
            $from_unit = 1; // 預設 DB 單位 N.m
            $tools = $this->MiscellaneousModel->prepareToolTorqueValues($tools,$from_unit,$device_torque_unit,$decimals_arr);
            $torque_unit = $device_torque_unit;
            $flag = 'new';

        } else {

            $step_torque_unit = (int)$step['step_unit'];

            if ($step_torque_unit !== $device_torque_unit) {


                $StepTorque_temp = $this->MiscellaneousModel->convert_all_torque_units( $step['StepTorque'], $device_torque_unit); 
                $StepHiTorque_temp = $this->MiscellaneousModel->convert_all_torque_units( $step['StepHiTorque'], $device_torque_unit); 


                $decimals = $decimals_arr[$device_torque_unit] ?? 3;
                $converted_torque      = $this->MiscellaneousModel->convert_all_torque_units($step['StepTorque'], $step_torque_unit);
                $converted_hi_torque   = $this->MiscellaneousModel->convert_all_torque_units($step['StepHiTorque'], $step_torque_unit);
                $converted_lo_torque   = $this->MiscellaneousModel->convert_all_torque_units($step['StepLoTorque'], $step_torque_unit);
                $unit_key = $torque_arr[$device_torque_unit] ?? 'N.m';

                $step['StepTorque']    = number_format($converted_torque[$unit_key]    ?? 0, $decimals, '.', '');
                $step['StepHiTorque']  = number_format($converted_hi_torque[$unit_key] ?? 0, $decimals, '.', '');
                $step['StepLoTorque']  = number_format($converted_lo_torque[$unit_key] ?? 0, $decimals, '.', '');


                if (!empty($tools)) {
                    $tools = $this->MiscellaneousModel->prepareToolTorqueValues($tools,$step_torque_unit,$device_torque_unit,$decimals_arr);
                }

                $torque_unit = $device_torque_unit;
            } else {
                
                if (!empty($tools)) {
                    $tools = $this->MiscellaneousModel->prepareToolTorqueValues($tools,$step_torque_unit,$device_torque_unit,$decimals_arr);
                }
                $torque_unit = $step_torque_unit;
                $decimals = $decimals_arr[$step_torque_unit] ?? 3;
                
                $converted_torque      = $this->MiscellaneousModel->convert_all_torque_units($step['StepTorque'], $step_torque_unit);
                $converted_hi_torque   = $this->MiscellaneousModel->convert_all_torque_units($step['StepHiTorque'], $step_torque_unit);
                $converted_lo_torque   = $this->MiscellaneousModel->convert_all_torque_units($step['StepLoTorque'], $step_torque_unit);
                $unit_key = $torque_arr[$step_torque_unit] ?? 'N.m';

                $step['StepTorque']    = number_format($converted_torque[$unit_key]    ?? 0, $decimals, '.', '');
                $step['StepHiTorque']  = number_format($converted_hi_torque[$unit_key] ?? 0, $decimals, '.', '');
                $step['StepLoTorque']  = number_format($converted_lo_torque[$unit_key] ?? 0, $decimals, '.', '');


            }

         
            $tools['tool_high_torque'] = $step['StepHiTorque'] ?? 0;
            $tools['tool_low_torque'] = $step['StepLoTorque'] ?? 0;
            $flag = 'edit';
        }

        $unit_name = $torque_arr[$torque_unit] ?? 'N.m';
        
        // 製作tor檢核
        $tools_check = $this->ToolModel->GetToolInfo()[0] ?? [];

        if (!empty($tools_check) && $type === "edit") {

            $use_unit = ($step_torque_unit === $device_torque_unit) ? $step_torque_unit : $device_torque_unit;
            //轉換函式
            $convert_torque = function ($raw_value) use ($use_unit, $torque_arr) {
                $nm_value = floatval($raw_value) / 1000;
                $converted = $this->MiscellaneousModel->convert_all_torque_units($nm_value, 1); // 1 => N.m
                return $converted[$torque_arr[$use_unit]] ?? 0;
            };

        

            // 執行轉換與檢查
            $check_target_tor_lo = $convert_torque($tools_check['min_torque']);
            $check_target_tor_hi = $convert_torque($tools_check['max_torque']);

            $check_hi_tor_after  = $check_target_tor_hi * 1.10;
            $decimals = $decimals_arr[$use_unit] ?? 1;

            $check_hi_tor_after = $this->MiscellaneousModel->roundToNDecimals($check_hi_tor_after, $decimals);
            $check_hi_tor_after = number_format($check_hi_tor_after, $decimals, '.', '');

            if($use_unit == 3){
                $check_hi_tor_after = $check_hi_tor_after + 0.0001;
            }
            
            if($use_unit == 2){
                $check_hi_tor_after = $check_hi_tor_after + 0.01;
            }
            if($use_unit == 0){
                $check_hi_tor_after = $check_hi_tor_after + 0.01;
            }


            $tools_check['check_target_tor_lo']   = $check_target_tor_lo;
            $tools_check['check_target_tor_hi']   = $check_target_tor_hi;
            $tools_check['check_hi_tor_before']   = $check_target_tor_hi;
            $tools_check['check_hi_tor_after']    = $check_hi_tor_after;
            $tools_check['check_lo_tor_before']   = number_format(0, $decimals_arr[$use_unit] ?? 3, '.', '');
            $tools_check['check_lo_tor_after']    = $step['StepTorque'] - (1 / pow(10, $decimals_arr[$use_unit] ?? 3));

            $tools_check['check_lo_rpm'] = (int)$tools_check['min_rpm'];
            $tools_check['check_hi_rpm'] = (int)$tools_check['max_rpm'];

    

            $tools = array_merge($tools,$tools_check);
        }else if(!empty($tools_check) && $type === "new"){

                $use_unit =$device_torque_unit;
                //轉換函式
                $convert_torque = function ($raw_value) use ($use_unit, $torque_arr) {
                    $nm_value = floatval($raw_value) / 1000;
                    $converted = $this->MiscellaneousModel->convert_all_torque_units($nm_value, 1); // 1 => N.m
                    return $converted[$torque_arr[$use_unit]] ?? 0;
                };

                // 執行轉換與檢查
                $check_target_tor_lo = $convert_torque($tools_check['min_torque']);
                $check_target_tor_hi = $convert_torque($tools_check['max_torque']);

                $check_hi_tor_after  = $check_target_tor_hi * 1.10;
                $decimals = $decimals_arr[$use_unit] ?? 1;
                $check_hi_tor_after = $this->MiscellaneousModel->roundToNDecimals($check_hi_tor_after, $decimals);
                $check_hi_tor_after = number_format($check_hi_tor_after, $decimals, '.', '');

                if($use_unit == 3){
                    $check_hi_tor_after = $check_hi_tor_after + 0.0001;
                }
                
                if($use_unit == 2){
                    $check_hi_tor_after = $check_hi_tor_after + 0.01;
                }
                if($use_unit == 0){
                    $check_hi_tor_after = $check_hi_tor_after + 0.01;
                }



                $tools_check['check_target_tor_lo']   = $check_target_tor_lo;
                $tools_check['check_target_tor_hi']   = $check_target_tor_hi;
                $tools_check['check_hi_tor_before']   = $check_target_tor_hi;
                $tools_check['check_hi_tor_after']    = $check_hi_tor_after;
                $tools_check['check_lo_tor_before']   = number_format(0, $decimals_arr[$use_unit] ?? 3, '.', '');
                $tools_check['check_lo_tor_after']    = $check_target_tor_lo - (1 / pow(10, $decimals_arr[$use_unit] ?? 3));

                $tools_check['check_lo_rpm'] = (int)$tools_check['min_rpm'];
                $tools_check['check_hi_rpm'] = (int)$tools_check['max_rpm'];



                 $tools = array_merge($tools,$tools_check);
        }


        $isMobile = $this->isMobileCheck();
        $data = [
            'JOBID' => $job_id,
            'SEQID' => $seq_id,
            'StepSelect' => $StepSelect,
            'type' => $type,
            'tools_info' => $tools,
            'torque_unit' => $unit_name,
            'step' => $step,
            'next_step_id' => $next_step_id,
            'step_torque_unit' => $torque_unit
        ];

        if ($isMobile) {
            $this->view('step/add_step_m', $data);
        } else {
            $this->view('step/add_step', $data);
        }
    }


    
    public function check_step_limit() {
  
        $jobid = $_POST['jobid'] ?? 0;
        $seqid = $_POST['seqid'] ?? 0;


        $steps = $this->stepModel->getStepsWithThresholds($jobid, $seqid);
        $stepCount = count($steps); 

        $hasNonZeroThreshold = false; 
        $updateIds = [];          

        // 遍歷每一筆 step，檢查是否有 StepEnableThreshold 不為 0 的情況
        foreach ($steps as $step) {
            if ((int)$step['StepEnableThreshold'] !== 0) {
                $hasNonZeroThreshold = true;
                $updateIds[] = $step['step_id']; 
            }
        }

        // 若有不為 0 的情況（將 StepEnableThreshold 和 StepTorqueTS 設為 0）
        if (!empty($updateIds)) {
            $this->stepModel->resetThresholdsByStepIds($jobid, $seqid, $updateIds);
        }


        echo json_encode([
            'count' => $stepCount,
            'has_nonzero_threshold' => $hasNonZeroThreshold
        ]);
    }

    public function check_step_is_last() {
        $jobid = $_POST['jobid'] ?? 0;
        $seqid = $_POST['seqid'] ?? 0;
        $stepid = $_POST['stepid'] ?? 0;

        $info = $this->stepModel->check_step_is_last($jobid, $seqid, $stepid);

        echo json_encode([
            'is_last' => $info['is_last'],
            'count' => $info['count']
        ]);
    }


    public function roundConservative(float $value, int $decimals = 2): float {
    $eps = 1 / pow(10, $decimals + 3);
    $rounded = round($value + $eps, $decimals);
    $mult = pow(10, $decimals);

    if ($value > $rounded + $eps) {
        $rounded = ($rounded * $mult + 1) / $mult;
    }
    return $rounded;
}

}