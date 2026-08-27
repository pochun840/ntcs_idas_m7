<?php

class Outputs extends Controller
{

    private $OutputModel;
    private $InputModel;
    private $MiscellaneousModel;
    private $jobModel;
    Private $deviceId;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->OutputModel = $this->model('Output');
        $this->InputModel = $this->model('Input');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->jobModel = $this->model('Job');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 

    }

    // 取得所有Jobs
    public function index(){


        //要檢查是否有alljobinput，有的話要直接帶入
        $isMobile     = $this->isMobileCheck();
        $joblist      = $this->InputModel->get_job_list();
        $event_output = $this->MiscellaneousModel->details('io_output');
        $device_data  = $this->OutputModel->get_output_by_job_temp();

        $focused_jobid = $this->jobModel->getUnifiedJobId_by_output();

        if(!empty($joblist)){
            $job_list_new = array();
            foreach($joblist as $kk =>$vv){
                $job_list_new[$vv['JOBID']] =$vv;  
            }
        }else{
            $job_list_new = '';
        }

        $this->ntcs_data_db_sysnc();
        


        $data = array(
            'isMobile'     => $isMobile,
            'job_list'     => $joblist,
            'event_output' => $event_output,
            'job_list_new' => $job_list_new,
            'device_data'  => $device_data,
            'focused_jobid' =>  $focused_jobid 
        );

        if($isMobile){
            $this->view('output/index_m', $data);
        }else{
            $this->view('output/index', $data);
        }
        
    }

    public function get_output_by_job_id() {
        $event_output = $this->MiscellaneousModel->details('io_output');
        $job_id = $_POST['job_id'] ?? null;

        $temp = [];
        $tempA = [];
        $job_outputlist = '';

        if (!empty($job_id)) {
            $job_outputs = $this->OutputModel->get_output_by_job_id($job_id);


            //檢查 JOBID 有無被套用(unified)
            $check = $this->OutputModel->check_output_unified_by_job_id($job_id);  
            $isMobile = $this->isMobileCheck();

            foreach ($job_outputs ?? [] as $vv) {
                $pin = $vv['Pin'] ?? '';
                $event_id = $vv['EvenID'] ?? '';
                $signal = $vv['signal'] ?? 0;
                $durate = ($signal == 1) ? ($vv['durate'] ?: '100') : '';

                // 累積 pin 狀態
                if (!empty($pin)) {
                    $temp[] = "pin{$pin}_{$signal}";
                    $temp[] = "edit_pin{$pin}_{$signal}";
                }

                // 累積事件 ID
                $skipIds = [12, 13, 14, 15, 16];//自定義1~自定義5
                if (!empty($event_id) && !in_array((int)$event_id, $skipIds, true)) {
                    $tempA[] = $event_id;
                }
                        
                $label = $event_output[$event_id] ?? '';
                if ($isMobile) {
                    $imgSrc = './img/trigger.png';
                    if ($signal == 0) $imgSrc = './img/signal01.png';
                    if ($signal == 1) $imgSrc = './img/signal02.png';
                    $imgTag = "<img src=\"{$imgSrc}\" style=\"max-width: 50px;\">";

                    $job_outputlist .= "<tr data-event=\"{$event_id}\">";
                    $job_outputlist .= '<td class="evt-label" data-eid="' . (int)$event_id . '">' . htmlspecialchars($label, ENT_QUOTES) . '</td>';
                    $job_outputlist .= "<td data-outputpin=\"{$pin}\">{$pin}</td>";
                    $job_outputlist .= "<td>{$imgTag}</td>";
                    $job_outputlist .= "<td>{$durate}</td>";
                    $job_outputlist .= "</tr>";
                } else {
                    $job_outputlist .= "<tr data-event=\"{$event_id}\">";
                    $job_outputlist .= '<td class="evt-label" data-eid="' . (int)$event_id . '">' . htmlspecialchars($label, ENT_QUOTES) . '</td>';
                    $job_outputlist .= $this->OutputModel->generateTableCell($pin, $signal);
                    $job_outputlist .= "<td>{$durate}</td>";
                    $job_outputlist .= "</tr>";
                }
            }
        }

        echo json_encode([
            'job_outputlist' => $job_outputlist,
            'temp' => $temp,
            'tempA' => $tempA,
            'language' => $_COOKIE['language'] ?? 'en-us',
            'focused_jobid' =>  $job_id,
            'check_jobid_unified' => $check

        ]);
        
    }


    public function check_job_output_conflict($value='')
    {
        $input_check = true;
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $job_id = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }
        if( !empty($_POST['event_id']) && isset($_POST['event_id'])  ){
            $event_id = $_POST['event_id'];
        }else{ 
            $input_check = false; 
        }

        if($input_check){
            $job_inputs = $this->OutputModel->check_job_output_conflict($job_id,$event_id);    
        }

        echo json_encode($job_inputs);
    }

    public function create_output_event()
    {

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $event = $this->MiscellaneousModel->details('io_output');
        $input_check = true;
        $output_data = array();
      
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $output_data['JOBID'] = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }
        if( !empty($_POST['output_pin']) && isset($_POST['output_pin'])  ){
            $output_data['Pin'] = $_POST['output_pin'];
        }else{ 
            $input_check = false; 
        }
        if( !empty($_POST['output_event']) && isset($_POST['output_event'])  ){
            $output_data['EvenID'] = $_POST['output_event'];
        }else{ 
            $input_check = false; 
        }
        
        if(!empty($_POST['wave'])){
            $output_data['signal'] = $_POST['wave'];
        }else{ 
            $output_data['signal'] = 0;
        }

        if($output_data['signal'] != 1){
            $output_data['durate'] = 100;
        }

        if($_POST['wave'] == "1"){
            $output_data['durate'] = $_POST["wave_on"];
        }


        if($input_check){
      
            $res = $this->OutputModel->create_output($output_data);
            $result = array();
            if($res){
                $res_type = 'Success';
                $res_msg = $text['new_event'].$text['job_id'].':'.$output_data['JOBID'].','.$text['event'].':'.$text[$event[$output_data['EvenID']]]."  ".$text['success'];
            }else{
                $res_type = 'Error';
                $res_msg = $text['new_event'].$text['job_id'].':'.$output_data['JOBID'].','.$text['event'].':'.$text[$event[$output_data['EvenID']]]."  ".$text['fail'];
            }
            
            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg 
            );

            echo json_encode($result);
        }
       
    }

    public function copy_output()
    {
        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }
        
        $input_check = true;
        if( !empty($_POST['from_job_id']) && isset($_POST['from_job_id'])  ){
            $output_job_id = $_POST['from_job_id'];
        }else{ 
            $input_check = false; 
        }
        if( !empty($_POST['to_job_id']) && isset($_POST['to_job_id'])  ){
            $to_job_id = $_POST['to_job_id'];
            $this->OutputModel->delete_output_by_id($to_job_id);
        }else{ 
            $input_check = false; 
        }

        if($input_check){
            $job_outputs_from = $this->OutputModel->get_output_by_job_id($output_job_id);
            if (!empty($job_outputs_from)) {
                $output_data = array();
                foreach ($job_outputs_from as $key => $val) {
                
                    if (isset($val['JOBID'])) {
                        $output_data[$key]['JOBID'] = $to_job_id;
                    } else {
                        continue; 
                    }

                    $output_data[$key]['Pin']    = $val['Pin'];
                    $output_data[$key]['EvenID'] = $val['EvenID'];
                    $output_data[$key]['signal'] = $val['signal'];
                    $output_data[$key]['durate'] = $val['durate'];


                    if($output_data[$key]['signal'] != 1 ){
                        $output_data[$key]['durate'] = 100;
                    }

                    
                    $res = $this->OutputModel->create_output($output_data[$key]);
                    $result = array();
                    if($res){
                        $res_type = 'Success';
                        $res_msg = $text['copy_output']."  ".$text['success'];
                    }else{
                        $res_type = 'Error';
                        $res_msg = $text['copy_output']."  ".$text['fail'];
                    }
        
                }

                $result = array(
                    'res_type' => $res_type,
                    'res_msg'  => $res_msg 
                );
        
                echo json_encode($result);
            }
        }
    }

    public function delete_output(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $event    = $this->MiscellaneousModel->details('io_output');

        $input_check = true;
        
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])){
            $output_job_id	 = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }

        if( !empty($_POST['output_event']) && isset($_POST['output_event'])  ){
            $output_event = $_POST['output_event'];
        }else{ 
            $input_check = false; 
        }
        
        if( !empty($_POST['output_pin']) && isset($_POST['output_pin'])  ){
              $output_pin = $_POST['output_pin'];
        }else{
             $input_check = false; 
        }

        if($input_check){

    
            $count = $this->OutputModel->check_event_conflict($output_job_id,$output_event);
            if ($count > 0){
                $res = $this->OutputModel->delete_output_event_by_id($output_job_id,$output_event,$output_pin);
                if($res){
                    $res_type = 'Success';
                    $res_msg  = $text['del_event'].$text['job_id'].':'.$output_job_id.','.$text['event'].':'.$text[$event[$output_event]]."  ".$text['success'];
                }else{
                    $res_type = 'Error';
                    $res_msg  = $text['del_event'].$text['job_id'].':'.$output_job_id.','.$text['event'].':'.$text[$event[$output_event]]."  ".$text['fail'];
                }
                
            }else{
                $res_type = 'Error';
                $res_msg  = $text['alert_message_1'];
            }
        
            
            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg,
            );

           
  
            echo json_encode($result);
        }
    }

    public function output_alljob(){
        $input_check = true;
        if( isset($_POST['job_id_new']) && $_POST['job_id_new'] >= 0 ){
            $output_job_id = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }
         $output_job_id = $_POST['job_id'];
        if( $output_job_id){
            $res = $this->OutputModel->set_output_alljob($output_job_id);
            if ($res) {
                $res_msg = 'set outputall job:'.$output_job_id.' success';
            } else {
                $res_msg  = 'set outputall job:'.$output_job_id.' fail';
            }
            echo $res_msg;
        }

       
    }  
    
    public function check_job_event() {
        $input_check = true;

        if (!empty($_POST['job_id']) && isset($_POST['job_id'])) {
            $output_job_id = $_POST['job_id'];
        } else {
            $input_check = false;
        }

        if (!empty($_POST['output_event']) && isset($_POST['output_event'])) {
            $output_event = $_POST['output_event'];
        } else {
            $input_check = false;
        }

        if ($input_check) {
            $job_outputs = $this->OutputModel->check_job_event_conflict($output_job_id, $output_event);

            if (empty($job_outputs)) {
                echo json_encode(['status' => 'no_data']);
                return;
            }

            // 若 signal 不是 1，durate 要清空
            if (isset($job_outputs['signal']) && $job_outputs['signal'] != '1') {
                $job_outputs['durate'] = '';
            }

            header('Content-Type: application/json');
            echo json_encode($job_outputs);
        }
    }


    public function edit_output_event(){

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $eventMap = $this->MiscellaneousModel->details('io_output'); // EvenID => i18n key
       

        $result = ['res_type' => 'Error', 'res_msg' => ''];

        // ---- 讀取輸入 ----
        $jobId  = $_POST['job_id']           ?? null;
        $pin    = $_POST['output_pin']       ?? null;
        $newEv  = $_POST['output_event']     ?? null;     // 目標事件 (ex: NG)
        $signal = isset($_POST['wave']) ? (int)$_POST['wave'] : 0;
        $durate = $_POST['wave_on']          ?? '';
        // 可忽略 old_output_event，因為我們改成「先刪後建」
        // $oldEv  = $_POST['old_output_event'] ?? null;

        if (!$jobId || !$pin || !$newEv) {
            $label = $text[$eventMap[$newEv] ?? $newEv] ?? $newEv;
            $result['res_msg'] = $text['edit_event'].$text['job_id'].':'.$jobId.','.$text['event'].':'.$label.'  '.$text['fail'];
            echo json_encode($result); return;
        }

        // signal=0/2 時你的需求是固定 100（照你目前程式）
        if ($signal === 0 || $signal === 2) {
            $durate = 100;
        }

        if(!empty($_POST['job_id'])){
            
            $this->OutputModel->delete_job_old_event($jobId,$_POST['old_output_event'],$_POST['old_output_pin']);
            $this->OutputModel->insert_output_event($jobId, $pin, $newEv, $signal, $durate);

            $label = $text[$eventMap[$newEv] ?? $newEv] ?? $newEv;
            $result['res_type'] = 'Success';
            $result['res_msg']  = $text['edit_event'].$text['job_id'].':'.$jobId.','.$text['event'].':'.$label.'  '.$text['success'];
            echo json_encode($result);
        }

        
    }   





    public function get_other_event_by_job_id(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }  
        
        $input_check = true;
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $output_job_id = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }

        if($input_check){
            $res   = $this->OutputModel->check_event_conflict_by_job_id($output_job_id);
            echo "<pre>";
            print_r($res);
            echo "</pre>";

        }
    }


    public function set_output_unified(){

    
    }



    public function check_jobid_unified(){
        
        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }  

        $input_check = true;
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $output_job_id = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }

        $check = $this->OutputModel->check_output_unified_by_job_id($output_job_id);  

        return $check;

    }
}

?>