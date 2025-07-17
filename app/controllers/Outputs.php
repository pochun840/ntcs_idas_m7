<?php

class Outputs extends Controller
{

    private $OutputModel;
    private $InputModel;
    private $MiscellaneousModel;
    private $jobModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->OutputModel = $this->model('Output');
        $this->InputModel = $this->model('Input');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->jobModel = $this->model('Job');
    }

    // 取得所有Jobs
    public function index(){

      
        //要檢查是否有alljobinput，有的話要直接帶入
        $isMobile     = $this->isMobileCheck();
        $joblist      = $this->InputModel->get_job_list();
        $event_output = $this->MiscellaneousModel->details('io_output');
        $device_data  = $this->InputModel->get_input_alljob();

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
            'event_output' => $event_output,
            'job_list_new' => $job_list_new,
            'device_data'  => $device_data,
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
                if (!empty($event_id)) {
                    $tempA[] = $event_id;
                }

                if ($isMobile) {
                    $imgSrc = './img/trigger.png';
                    if ($signal == 0) $imgSrc = './img/signal01.png';
                    if ($signal == 1) $imgSrc = './img/signal02.png';
                    $imgTag = "<img src=\"{$imgSrc}\" style=\"max-width: 50px;\">";

                    $job_outputlist .= "<tr data-event=\"{$event_id}\">";
                    $job_outputlist .= "<td id=\"{$event_id}\">" . ($event_output[$event_id] ?? '') . "</td>";
                    $job_outputlist .= "<td data-outputpin=\"{$pin}\">{$pin}</td>";
                    $job_outputlist .= "<td>{$imgTag}</td>";
                    $job_outputlist .= "<td>{$durate}</td>";
                    $job_outputlist .= "</tr>";
                } else {
                    $job_outputlist .= "<tr data-event=\"{$event_id}\">";
                    $job_outputlist .= "<td id=\"{$event_id}\">" . ($event_output[$event_id] ?? '') . "</td>";
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
            'languange' => $_SESSION['language'] ?? 'en'
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


        if( $_POST['wave_on'] == ""){
            $output_data['durate'] = '';
        }else{
            $output_data['durate'] = $_POST['wave_on'];
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


                    if($output_data[$key]['durate'] == ""){
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
        
                    $result = array(
                        'res_type' => $res_type,
                        'res_msg'  => $res_msg 
                    );
        
                    echo json_encode($result);


                }
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

        if($input_check){
    
            $count = $this->OutputModel->check_event_conflict($output_job_id,$output_event);
            if ($count > 0){
                $res = $this->OutputModel->delete_output_event_by_id($output_job_id,$output_event);
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

    public function output_alljob()
    {
        $input_check = true;
        if( isset($_POST['job_id']) && $_POST['job_id'] >= 0 ){
            $output_job_id = $_POST['job_id'];
        }else if(isset($_POST['job_id_new']) && $_POST['job_id_new'] >= 0){
            $output_job_id  = '';
        }else{ 
            $input_check = false; 
        }

        if($input_check){
            $res = $this->OutputModel->set_output_alljob($output_job_id);
            if ($res) {
                $res_msg = 'set outputall job:'.$output_job_id.' success';
            } else {
                $res_msg  = 'set outputall job:'.$output_job_id.' fail';
            }
            echo $res_msg;
        }

       
    }  
    
    public function check_job_event(){

        $input_check = true;
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $output_job_id  = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }
        if( !empty($_POST['output_event']) && isset($_POST['output_event'])  ){
           $output_event = $_POST['output_event'];
        }else{ 
            $input_check = false; 
        }

        if($input_check){
            $job_outputs = $this->OutputModel->check_job_event_conflict($output_job_id, $output_event);
            if (empty($job_outputs)) {
                $job_outputs = 'no_data'; 
            }
            else{
                if($job_outputs['signal'] !=1){
                    $job_outputs['durate'] = '';
                }                
            }

            print_r($job_outputs);  
        }
     

    }


    public function edit_output_event(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $event  = $this->MiscellaneousModel->details('io_output');
        $result = array();
        
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

        if($_POST['wave_on'] == ""){
            $output_data['durate'] = '';
        }else{
            $output_data['durate'] = $_POST['wave_on'];
        }

        if($output_data['signal'] == 0 || $output_data['signal'] == 2){
            $output_data['durate'] = '';
        }

        // ➤ 這行是關鍵，先給預設值
        $res = false;

        $count = $this->OutputModel->check_event_conflict($output_data['JOBID'],$output_data['EvenID']);
        if ($count > 0){
            $res = $this->OutputModel->edit_output($output_data);
        }

        if($res){
            $res_type = 'Success';
            $res_msg = $text['edit_event'].$text['job_id'].':'.$output_data['JOBID'].','.$text['event'].':'.$text[$event[$output_data['EvenID']]]."  ".$text['success'];
        }else{
            $res_type = 'Error';
            $res_msg = $text['edit_event'].$text['job_id'].':'.$output_data['JOBID'].','.$text['event'].':'.$text[$event[$output_data['EvenID']]]."  ".$text['fail'];
        }

        $result = array(
            'res_type' => $res_type,
            'res_msg'  => $res_msg 
        );

        echo json_encode($result);
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
}



?>