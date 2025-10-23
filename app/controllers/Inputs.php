<?php

class Inputs extends Controller
{

    private $InputModel;
    private $MiscellaneousModel;
    private $jobModel;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->InputModel = $this->model('Input');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->jobModel = $this->model('Job');
    }

    // 取得所有Inputs
    public function index(){

        $this->ntcs_data_db_sysnc();

        //要檢查是否有alljobinput，有的話要直接帶入
        $isMobile = $this->isMobileCheck();
        $joblist  = $this->InputModel->get_job_list();
        
        $event    = $this->MiscellaneousModel->details('io_input');

        $focused_jobid = $this->jobModel->getUnifiedJobId_by_input();
        $device_data = $this->InputModel->get_input_by_job_temp($focused_jobid);

        if(!empty($joblist)){
            $job_list_new = array();
            foreach($joblist as $kk =>$vv){
                $job_list_new[$vv['JOBID']] =$vv;  
            }
        }




        $data = array();
        $data = array(
            'isMobile'      => $isMobile,
            'job_list'      => $joblist,
            'event'         => $event,
            'job_list_new'  => $job_list_new,
            'device_data'   => $device_data,  
            'focused_jobid' => $focused_jobid 

        );

        if($isMobile){
            $this->view('input/index_m', $data);
        }else{
            $this->view('input/index', $data);
        }
    }

    // get_input_by_job_id
    public function get_input_by_job_id($job_id) {
        $input_check = true;
        $mode = $_POST['mode'] ?? 'edit'; // 預設 edit 模式

        if (!empty($_POST['jobid'])) {
            $job_id = $_POST['jobid'];
        } else {
            $input_check = false;
        }

        $temp = [];        // radio 禁用 ID 清單
        $tempA = [];       // select 隱藏
        $temp_event = [];  // select 灰色
        $tempAB = [];      // 不禁用的名單
        $job_inputlist = '';

        if ($input_check) {

            // ✅ 若是從 job_id = 1 複製，則不帶入任何事件
            if ($mode === 'copy' && $job_id == 1) {
                echo json_encode([
                    'job_inputlist' => '',
                    'temp' => [],
                    'tempA' => [],
                    'temp_event' => [],
                    'isTemplate' => true
                ]);
                return;
            }

            $event = $this->MiscellaneousModel->details('io_input');
            $job_inputs = $this->InputModel->get_input_by_job_id($job_id);

            if (!empty($job_inputs)) {
                foreach ($job_inputs as $vv) {

                    // radio 禁用用 ID
                    if (!empty($vv['Pin'])) {
                        $pin = $vv['Pin'];
                        $temp[] = "pin{$pin}_high";
                        $temp[] = "pin{$pin}_low";
                        $temp[] = "edit_pin{$pin}_high";
                        $temp[] = "edit_pin{$pin}_low";
                        $tempA[] = $pin;
                    }

                    // 被使用的事件 ID
                    if (!empty($vv['EvenID'])) {
                        $temp_event[] = $vv['EvenID'];
                    }

        
                    // 1) 先正規化：全部轉成字串並去重
                    $temp_event = array_values(array_unique(array_map('strval', $temp_event)));

                    // 2) 101/102 成對補齊（只要有任一個就補齊另一個）
                    $pair = ['101', '102'];
                    if (count(array_intersect($pair, $temp_event)) > 0) {
                        foreach ($pair as $id) {
                            if (!in_array($id, $temp_event, true)) {
                                $temp_event[] = $id;
                            }
                        }
                    }

                    // 3) 互斥規則：105/106 與 115
                    $has115 = in_array('115', $temp_event, true);
                    if($has115){
                        foreach (['105', '106'] as $id) {
                            if (!in_array($id, $temp_event, true)) {
                                $temp_event[] = $id; // 補齊缺少的 105/106
                            }
                        }
                    }

                    $has105 = in_array('105', $temp_event, true);
                    if($has105){
                        foreach (['115'] as $id) {
                            if (!in_array($id, $temp_event, true)) {
                                $temp_event[] = $id; // 補齊缺少的 115
                            }
                        }
                    }

                    $has106 = in_array('106', $temp_event, true);
                    if($has106){
                        foreach (['115'] as $id) {
                            if (!in_array($id, $temp_event, true)) {
                                $temp_event[] = $id; // 補齊缺少的 115
                            }
                        }
                    }
                    
                    
                    // 表格輸出
                    $isMobile = $this->isMobileCheck();
                    if ($isMobile) {
                        $img = ($vv['signal'] == 1)
                            ? '<img src="./img/high.png" style="max-width: 50px;">'
                            : '<img src="./img/low.png" style="max-width: 50px;">';

                        $job_inputlist .= "<tr data-event='{$vv['EvenID']}'>";
                        $job_inputlist .= "<td id='{$vv['EvenID']}'>{$event[$vv['EvenID']]}</td>";
                        $job_inputlist .= "<td>{$vv['Pin']}</td>";
                        $job_inputlist .= "<td>{$img}</td>";
                        $job_inputlist .= "</tr>";
                    } else {
                        $Wp_Ready_Confirm = ($vv['Wp_Ready_Confirm'] == 1) ? "YES" : "NO";
                        $job_inputlist .= "<tr data-event='{$vv['EvenID']}'>";
                        $job_inputlist .= "<td id='{$vv['EvenID']}'>{$event[$vv['EvenID']]}</td>";
                        $job_inputlist .= $this->InputModel->generateTableCell($vv['Pin'], $vv['signal']);
                        $job_inputlist .= "<td>{$Wp_Ready_Confirm}</td>";
                        $job_inputlist .= "<td>1</td>";
                        $job_inputlist .= "<td>EVENT</td>";
                        $job_inputlist .= "</tr>";
                    }
                }
            }
        }

        // 確保 temp_event 是 array
        if (empty($temp_event)) {
            $temp_event = [];
        }

        // 輸出 JSON
        echo json_encode([
            'job_inputlist' => $job_inputlist,
            'temp' => $temp,
            'tempA' => $tempA,
            'temp_event' => $temp_event,
            'language' =>$_COOKIE['language'],
        ]);
    }


    public function check_job_event_conflict($value='')
    {
        $input_check = true;
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $job_id = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }
        if( !empty($_POST['input_event']) && isset($_POST['input_event'])  ){
           $input_event = $_POST['input_event'];
        }else{ 
            $input_check = false; 
        }

        if($input_check){
            $job_inputs = $this->InputModel->check_job_event_conflict($job_id,$input_event);
            if(empty($job_inputs)){
                $job_inputs = 'no_data';
            } 
            print_r($job_inputs);   
        }
    }

    public function create_input_event()
    {

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $event    = $this->MiscellaneousModel->details('io_input');

        $input_check = true;
        $input_data = array();

        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $input_data['JOBID'] = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }

        if( !empty($_POST['input_event']) && isset($_POST['input_event'])  ){
            $input_data['EvenID'] = $_POST['input_event'];
        }else{ 
            $input_check = false; 
        }

        if( !empty($_POST['input_pin']) && isset($_POST['input_pin'])  ){
            $input_data['Pin'] = intval($_POST['input_pin']);
        }else{ 
            $input_check = false; 
        }

        $input_data['signal'] = $_POST['input_wave'];

        if( isset($_POST['gateconfirm'])  ){
            $input_data['Wp_Ready_Confirm'] = $_POST['gateconfirm'];
        }else{ 
            $input_check = false; 
        }

        if( isset($_POST['pagemode'])  ){
            $input_data['pagemode'] = $_POST['pagemode'];
        }else{ 
            $input_check = false; 
        }

        if( isset($_POST['input_seqid'])  ){
            $input_data['input_seqid'] = $_POST['input_seqid'];
        }else{ 
            $input_check = false; 
        }

        if($input_check){
            
            $count = $this->InputModel->check_job_event_conflict($input_data['JOBID'],$input_data['EvenID']);
            if(!$count){
               
                $res  = $this->InputModel->create_input($input_data);
                $result = array();
                if($res){
                    $res_type = 'Success';
                    $res_msg  = $text['new_event']."  ".$text['job_id'].':'.$input_data['JOBID'].','.$text['event'].':'.$text[$event[$input_data['EvenID']]]."  ".$text['success'];
                }else{
                    $res_type = 'Error';
                    $res_msg  = $text['new_event']."  ".$text['job_id'].':'.$input_data['JOBID'].','.$text['event'].':'.$text[$event[$input_data['EvenID']]]."  ".$text['fail'];
                }
                
                $result = array(
                    'res_type' => $res_type,
                    'res_msg'  => $res_msg 
                );
    
                echo json_encode($result);

            }
        }
    }

        public function edit_input_event(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $event    = $this->MiscellaneousModel->details('io_input');
        
        $input_check = true;
        $input_data = array();
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $input_data['JOBID'] = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }

        if( !empty($_POST['input_event']) && isset($_POST['input_event'])  ){
            $input_data['EvenID'] = $_POST['input_event'];
        }else{ 
            $input_check = false; 
        }

        if( !empty($_POST['input_pin']) && isset($_POST['input_pin'])  ){
            $input_data['Pin'] = intval($_POST['input_pin']);
        }else{ 
            $input_check = false; 
        }

 
        $input_data['signal'] = $_POST['input_wave'];
        $input_data['old_input_event'] = $_POST['old_input_event'];


        if($input_data['EvenID'] != "109"){
            $input_data['gateconfirm'] = '';
        }else{
            $input_data['gateconfirm'] = $_POST['gateconfirm'];
            
        }

 

        if($input_check){

            //檢查 
            $ans_check = $this->InputModel->check_input_event($input_data['JOBID'],$input_data['EvenID'],$input_data['old_input_event']);
            $deleted   = $this->InputModel->check_input_event_wave($input_data['JOBID'],$input_data['Pin'],$input_data['signal']);
            $count     = $this->InputModel->check_job_event_conflict($input_data['JOBID'],$input_data['EvenID']);
            $ans       = $this->InputModel->delete_input_event_by_id($input_data['JOBID'],$input_data['EvenID']);

            $res  = $this->InputModel->create_input($input_data);

            $result = array();
            if($res){
                $res_type = 'Success';
                $res_msg  = $text['edit_event']."  ".$text['job_id'].':'.$input_data['JOBID'].','.$text['event'].':'.$text[$event[$input_data['EvenID']]]."  ".$text['success'];
            } else {
                $res_type = 'Error';
                $res_msg  = $text['edit_event']."  ".$text['job_id'].':'.$input_data['JOBID'].','.$text['event'].':'.$text[$event[$input_data['EvenID']]]."  ".$text['fail'];
            }
            
            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg 
            );

            echo json_encode($result);
        }
    }



    public function copy_input_event(){


        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $input_check = true;
        if (!empty($_POST['from_job_id']) && isset($_POST['from_job_id'])) {
            $input_job_id = $_POST['from_job_id'];
        } else {
            $input_check = false;
        }
        if (!empty($_POST['to_job_id']) && isset($_POST['to_job_id'])) {
            $to_job_id = $_POST['to_job_id'];
            $this->InputModel->delete_input_by_id($to_job_id);
        } else {
            $input_check = false;
        }
        

        if ($input_check) {
            $job_inputs_from = $this->InputModel->check_job_event($input_job_id);
            if (!empty($job_inputs_from)) {
                $input_data = array();
                foreach ($job_inputs_from as $key => $val) {
                
                    if (isset($val['JOBID'])) {
                        $input_data[$key]['JOBID'] = $to_job_id;
                    } else {
                        continue; 
                    }

                    $input_data[$key]['EvenID'] = $val['EvenID'];
                    $input_data[$key]['Pin'] = $val['Pin'];
                    $input_data[$key]['signal'] = $val['signal'];
                    $input_data[$key]['Wp_Ready_Confirm'] = $val['Wp_Ready_Confirm'];
                    $res = $this->InputModel->create_input($input_data[$key]);
             
                }

                $result = array();
                if($res){
                    $res_type = 'Success';
                    $res_msg  = $text['copy_input'].$to_job_id.$text['success'];
                } else {
                    $res_type = 'Error';
                    $res_msg  = $text['copy_input'].$to_job_id.$text['fail'];
                }
                $result = array(
                    'res_type' => $res_type,
                    'res_msg'  => $res_msg 
                );
    
                echo json_encode($result);      
               

            }
        }

    }


    public function delete_input(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $event    = $this->MiscellaneousModel->details('io_input');

        $input_check = true;
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $job_id = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }
        if( !empty($_POST['input_event']) && isset($_POST['input_event'])  ){
            $input_event  = $_POST['input_event'];
        }else{ 
            $input_check = false; 
        }

        if($input_check){

            $count = $this->InputModel->check_job_event_count($job_id,$input_event);
            if($count > 0){
                $res = $this->InputModel->delete_input_event_by_id($job_id,$input_event);
                
                if ($res) {
                    $res_type = 'Success';
                    $res_msg  = $text['del_event']."  ".$text['job_id'].':'.$job_id.','.$text['event'].':'.$text[$event[$input_event]]."  ".$text['success'];
                } else {
                    $res_type = 'Error';
                    $res_msg  = $text['del_event']."  ".$text['job_id'].':'.$job_id.','.$text['event'].':'.$text[$event[$input_event]]."  ".$text['fail'];
                }
            }else{
                $res_type = 'Error';
                $res_msg  = $text['alert_message_1'];
            }
            
            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg 
            );

            echo json_encode($result);
        }     
    }

    public function input_alljob()
    {
        $input_check = true;
        if( isset($_POST['job_id']) && $_POST['job_id'] >= 0 ){
            $input_job_id = $_POST['job_id'];
        }else if(isset($_POST['job_id_new']) && $_POST['job_id_new'] >= 0){
            $input_job_id = '';
        }else{
            $input_check = false; 
        }
        if($input_check){
            $res = $this->InputModel->set_input_alljob($input_job_id);
            if($res){
                $res_msg ='set inputall job:'.$input_job_id.' copyDB success';
            }else{
                $res_msg ='set inputall job:'.$input_job_id.' copyDB fail';
            }
            echo $res_msg;   
        }
    } 


    public function input_alljob_cancel(){
        $jobid = $_POST['job_id'] ?? null;

        if(!empty($jobid)){
            $res = $this->InputModel->set_input_alljob($jobid);
            if($res){
                $res_msg ='set inputall job:'.$input_job_id.' copyDB success';
            }else{
                $res_msg ='set inputall job:'.$input_job_id.' copyDB fail';
            }
            echo $res_msg; 
        }

        
    }
    
    
}

?>