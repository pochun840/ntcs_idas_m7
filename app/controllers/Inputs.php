<?php

class Inputs extends Controller
{
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->InputModel = $this->model('Input');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->jobModel = $this->model('Job');
    }

    // 取得所有Inputs
    public function index(){

        //要檢查是否有alljobinput，有的話要直接帶入
        $isMobile = $this->isMobileCheck();
        $joblist  = $this->InputModel->get_job_list();
        $event    = $this->MiscellaneousModel->details('io_input');
        $device_data = $this->InputModel->get_input_alljob();

        if(!empty($joblist)){
            $job_list_new = array();
            foreach($joblist as $kk =>$vv){
                $job_list_new[$vv['JOBID']] =$vv;  
            }
        }
        $data = array();
        $data = array(
            'isMobile'     => $isMobile,
            'job_list'     => $joblist,
            'event'        => $event,
            'job_list_new' => $job_list_new,
            'device_data'  => $device_data,   
        );

        if($isMobile){
            $this->view('input/index_m', $data);
        }else{
            $this->view('input/index', $data);
        }
    }

    // get_input_by_job_id
    public function get_input_by_job_id($job_id){

        $input_check = true;
        if (!empty($_POST['jobid']) && isset($_POST['jobid'])) {
            $job_id = $_POST['jobid'];
        } else {
            $input_check = false; 
        }

        if ($input_check) {

            $event = $this->MiscellaneousModel->details('io_input');
            $job_inputs = $this->InputModel->get_input_by_job_id($job_id);
            $temp  = array(); 
            $tempA = array();
            $tempB = array();
            $job_inputlist = ''; 

    
            if (!empty($job_inputs)) {
                foreach ($job_inputs as $kk => $vv) {


                    if (!empty($vv['Pin'])) {
                        $pin_number = $vv['Pin'];
                        $gateconfirm = $vv['Wp_Ready_Confirm'];
                        $temp[] = "pin" . $pin_number . "_high";
                        $temp[] = "pin" . $pin_number . "_low";
                        $temp[] = "edit_pin" . $pin_number . "_high";
                        $temp[] = "edit_pin" . $pin_number . "_low";
                        $temp[] = "check_".$gateconfirm;

                    }

                    if (!empty($vv['Pin'])) {
                        $tempA[] = $vv['Pin'];
                    }

                
                    
                    $isMobile = $this->isMobileCheck();

                    if($isMobile){

                        if($vv['signal'] == 1){
                            $img = '<img src="./img/high.png" style="max-width: 50px;">';
                        }else{
                            $img = '<img src="./img/low.png" style="max-width: 50px;">';
                        }
                        

                        $job_inputlist .= "<tr data-event = '".$vv['Pin']."' >";
                        $job_inputlist .= "<td id='".$vv['Pin']."'>".$event[$vv['input_event']]."</td>";
                        $job_inputlist .= '<td>'.$vv['Pin'].'</td>';
                        $job_inputlist .= '<td>'.$img.'</td>';
                        $job_inputlist .= '</tr>';
                        
                    }else{
    
                        $job_inputlist .= "<tr data-event = '".$vv['EvenID']."' >";
                        $job_inputlist .= "<td id='".$vv['EvenID']."'>".$event[$vv['EvenID']]."</td>";
                        $job_inputlist .= $this->InputModel->generateTableCell($vv['Pin'],$vv['signal']);
                        $job_inputlist .= '<td>NO</td>';
                        $job_inputlist .= '<td>1</td>';
                        $job_inputlist .= '<td>EVENT</td>';
                        $job_inputlist .= '</tr>';
                    }
    
                    
                }

            }
        }
        
        $response = array(
            'job_inputlist' => $job_inputlist,
            'temp' => $temp,
            'tempA' => $tempA,
            'tempB' => $tempB
            
        );
        echo json_encode($response);
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
        }

        print_r($job_inputs);
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

        if( !empty($_POST['input_wave']) && isset($_POST['input_wave'])  ){
            $input_data['signal'] = $_POST['input_wave'];
        }else{ 
            $input_check = false; 
        }

        if( isset($_POST['gateconfirm'])  ){
            $input_data['gateconfirm'] = $_POST['gateconfirm'];
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

    public function edit_input_event()
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

        if( !empty($_POST['input_wave']) && isset($_POST['input_wave'])  ){
            $input_data['signal'] = $_POST['input_wave'];
        }else{ 
            $input_check = false; 
        }

        if( isset($_POST['gateconfirm'])){
            $input_data['Wp_Ready_Confirm'] = $_POST['gateconfirm'];
        }else{ 
            $input_check = false; 
        }

        

        if($input_check){
            $count = $this->InputModel->check_job_event_conflict($input_data['JOBID'],$input_data['EvenID']);
            $ans  = $this->InputModel->delete_input_event_by_id($input_data['JOBID'],$input_data['EvenID']);
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
            $res = $this->InputModel->delete_input_event_by_id($job_id,$input_event);
            $result = array();
            if ($res) {
                $res_type = 'Success';
                $res_msg  = $text['del_event']."  ".$text['job_id'].':'.$job_id.','.$text['event'].':'.$text[$event[$input_event]]."  ".$text['success'];
            } else {
                $res_type = 'Error';
                $res_msg  = $text['del_event']."  ".$text['job_id'].':'.$job_id.','.$text['event'].':'.$text[$event[$input_event]]."  ".$text['fail'];
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
    
    
    public function input_alljob_cancel()
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
            $res = $this->InputModel->cancel_input_alljob($input_job_id);
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