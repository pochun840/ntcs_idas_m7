<?php

class Outputs extends Controller
{
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

    public function get_output_by_job_id(){

        $event_output = $this->MiscellaneousModel->details('io_output');

        $input_check = true;
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $job_id = $_POST['job_id'];
        }else{ 
            $input_check = false; 
        }

        if($input_check){
            $job_outputs = $this->OutputModel->get_output_by_job_id($job_id);
            $temp  = array(); 
            $tempA = array();
            $job_outputlist = ''; 
        
            if (!empty($job_outputs)) {
                foreach ($job_outputs as $kk => $vv) {
                    if (!empty($vv['Pin'])) {
                        $pin_number = $vv['Pin'];
                        //如果 $vv['signal'] 空值 補 0
                        $signal_value = !empty($vv['signal']) ? $vv['signal'] : 0;
                        $temp[] = "pin" . $pin_number."_".$signal_value;
                        $temp[] = "edit_pin" . $pin_number."_".$signal_value;
                    }

                    if (!empty($vv['EvenID'])) {
                        $tempA[] = $vv['EvenID'];
                    }

                    $durate = ($vv['durate'] == 0) ? '' : $vv['durate'];

                    $isMobile = $this->isMobileCheck();
                    if($isMobile){

                        if($vv['signal'] == 0){
                            $img = '<img src="./img/signal01.png" style="max-width: 50px;">';
                        }else if($vv['wave'] == 1){
                            $img = '<img src="./img/signal02.png" style="max-width: 50px;">';
                        }else{
                            $img = '<img src="./img/trigger.png" style="max-width: 50px;">';
                        }   


                        $job_outputlist .= "<tr data-event ='".$vv['output_event']."'>";
                        $job_outputlist .= "<td id='".$vv['output_event']."'>".$event_output[$vv['output_event']]."</td>";
                        $job_outputlist .=  "<td data-outputpin = '".$vv['output_pin']."' >".$vv['output_pin']."</td>";
                        $job_outputlist .= '<td>'.$img.'</td>';
                        $job_outputlist .= '<td>'.$vv['wave_on'].'</td>';
                        $job_outputlist .= '</tr>';
                    }else{
                        $job_outputlist .= "<tr data-event ='".$vv['EvenID']."'>";
                        $job_outputlist .= "<td id='".$vv['EvenID']."'>".$event_output[$vv['EvenID']]."</td>";
                        $job_outputlist .= $this->OutputModel->generateTableCell($vv['Pin'],$vv['signal']);
                        $job_outputlist .= '<td>'.$durate.'</td>';
                        $job_outputlist .= '</tr>';
                    }

                   
                }

            }
        }

        $response = array(
            'job_outputlist' => $job_outputlist,
            'temp' => $temp,
            'tempA' => $tempA,
            'languange' => $_SESSION['language']
        );
        echo json_encode($response);
        

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
    
            
            $res = $this->OutputModel->delete_output_event_by_id($output_job_id,$output_event);
            $result = array();
            if($res){
                $res_type = 'Success';
                $res_msg  = $text['del_event'].$text['job_id'].':'.$output_job_id.','.$text['event'].':'.$text[$event[$output_event]]."  ".$text['success'];
            }else{
                $res_type = 'Error';
                $res_msg  = $text['del_event'].$text['job_id'].':'.$output_job_id.','.$text['event'].':'.$text[$event[$output_event]]."  ".$text['fail'];
            }
            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg 
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
                $job_outputs = array();  
            }  
        }
        print_r($job_outputs);

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
        if( !empty($_POST['output_pin']) && isset($_POST['output_pin'])  ){ //new
            $output_data['Pin'] = $_POST['output_pin'];
        }else{ 
            $input_check = false; 
        }
        if( !empty($_POST['output_event']) && isset($_POST['output_event'])  ){ //old
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

        if($output_data['signal'] == 0 || $output_data['signal'] == 2){
            $output_data['durate'] = '';
        }

     

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
            die();

        }



    }
}



?>