<?php

class Sequences extends Controller
{
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct(){

        $this->sequenceModel = $this->model('Sequence');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
    }

    // 取得所有Sequences
    public function index($job_id){
        
        if( isset($job_id) && !empty($job_id) ){

        }else{
            $job_id = 1;
        }

   
        $sequences  = $this->sequenceModel->getSequences_by_job_id($job_id);
        $unit_arr   = $this->MiscellaneousModel->details('torque_unit');

        if(empty($sequences)){
            $seq_id = 1;
        }else{
            $seq_id = count($sequences) + 1 ;
        }


        $isMobile = $this->isMobileCheck();
     
        $data =array();
        $data = array(
            'sequences' => $sequences,
            'job_id' => $job_id,
            'unit_arr' => $unit_arr,
            'seq_id' => $seq_id,
            'old_seqid' => '',


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

        
        if(isset($_POST['jobid'])){
            
            #如果 POST 中沒有，則使用預設值
            $jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : 0;
            $seqid = isset($_POST['seqid']) ? intval($_POST['seqid']) : 0;

            $k_value = isset($_POST['k_value']) ? floatval($_POST['k_value']) : 100.0;
            $tighten_repeat = isset($_POST['tighten_repeat']) ? intval($_POST['tighten_repeat']) : 1;
            $seq_ok = isset($_POST['seq_ok']) ? intval($_POST['seq_ok']) : 0;
            $stop_seq_ok = isset($_POST['stop_seq_ok']) ? intval($_POST['stop_seq_ok']) : 0;
            $opt_val = isset($_POST['opt_val']) ? intval($_POST['opt_val']) : '';
            $ng_stop = isset($_POST['ng_stop']) ? intval($_POST['ng_stop']) : 0;
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;  

            $seq_name = $_POST['seq_name'];
            
           
      
            #驗證seq_name 
            if(!$this->MiscellaneousModel->seq_validate($seq_name, 'name')) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $text['error_seq_name']);
                exit();
            }


            #驗證顆數
            if(!$this->MiscellaneousModel->seq_validate($tighten_repeat, 'tightenRepeat')) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $error_message['tightening_repeat']);
                exit();
            }


            #驗證k_value
            if(!$this->MiscellaneousModel->seq_validate($k_value, 'kValue')) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $error_message['ok_time']);
                exit();
            }

            #驗證offset
            if(!$this->MiscellaneousModel->seq_validate($offset, 'offset')) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $error_message['joint_offset_val']);
                exit();
            }
            
            $jobdata = array(
                'job_id' => $jobid,
                'sequence_id' => $seqid,
                'sequence_name' => $seq_name,
                'sequence_enable' => 1,
                'tightening_repeat' => $tighten_repeat,
                'ng_stop' => $ng_stop,
                'seq_ok'  => $seq_ok,
                'stop_seq_ok' => $stop_seq_ok, 
                'opt' => $opt_val,
                'k_value' => $k_value,
                'offset' => $offset,
            );


            if(!empty($jobdata['offset'])){
                $jobdata['offset'] = sprintf("%+03d", $jobdata['offset']);
            }
           
            $mode = "create";
            $res = $this->sequenceModel->create_seq($mode,$jobdata);
            $result = array();
            if($res){
                $res_type = 'Success';
                $res_msg  = $text['new_seq'].':'. $jobdata['sequence_id']."  ".$text['success'];
            }else{
                $res_type = 'Error';
                $res_msg  = $text['new_seq'].':'. $jobdata['sequence_id']."  ".$text['fail'];
            }
            
            $result = array(
                'res_type' => $res_type,
                'res_msg'  => $res_msg 
            );

            echo json_encode($result);

        }

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
            $res11 = $this->sequenceModel->delete_step_by_job_id($jobid,$seqid);
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
            print_r($res[0]);
        }

    }

    public function edit_seq(){


        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if(isset($_POST['jobid'])){

            $jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : 0;
            $seqid = isset($_POST['seqid']) ? intval($_POST['seqid']) : 0;
            $seq_name = $_POST['seq_name'];
            $tighten_repeat = isset($_POST['tightening_repeat']) ? intval($_POST['tightening_repeat']) : 1;
            $ng_stop = isset($_POST['ng_stop']) ? intval($_POST['ng_stop']) : 0;
            $seq_ok = isset($_POST['seq_ok']) ? intval($_POST['seq_ok']) : 0;
            $stop_seq_ok = isset($_POST['stop_seq_ok']) ? intval($_POST['stop_seq_ok']) : 0;
            $opt_val = isset($_POST['opt_val']) ? intval($_POST['opt_val']) : 0;
            $k_value = isset($_POST['k_value']) ? floatval($_POST['k_value']) : 100.0;
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;  

            #驗證seq_name 
            if(!$this->MiscellaneousModel->seq_validate($seq_name, 'name')) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $text['error_seq_name']);
                exit();
            }


            #驗證顆數
            if(!$this->MiscellaneousModel->seq_validate($tighten_repeat, 'tightenRepeat')) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $error_message['tightening_repeat']);
                exit();
            }
    
            #驗證k_value
            if(!$this->MiscellaneousModel->seq_validate($k_value, 'kValue')) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $error_message['ok_time']);
                exit();
            }

            #驗證offset
            if(!$this->MiscellaneousModel->seq_validate($offset, 'offset')) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $error_message['joint_offset_val']);
                exit();
            }


            $offset = sprintf("%+03d", $offset);

            $seq_count = $this->sequenceModel->countseq($jobid);
            $seq_count = intval($seq_count);
           
            #檢查job 
            if($seq_count >= 50) {
                echo "The maximum number of steps has been reached, unable to continue copying seqs";
                return;
            }
           
            $jobdata = array(
                'job_id' => $jobid,
                'sequence_id' => $seqid,
                'sequence_name' =>$seq_name,
                'sequence_enable' => 1,
                'tightening_repeat' => $tighten_repeat,
                'ng_stop' => $ng_stop,
                'seq_ok'  => $seq_ok,
                'stop_seq_ok' =>$stop_seq_ok,
                'opt' => $opt_val,
                'k_value' => $k_value,
                'offset' => $offset,
            );
           
            $res = $this->sequenceModel->update_seq_by_id($jobdata);
            $result = array();
            if($res){
                $res_type = 'Success';
                $res_msg  = $text['edit_seq'].':'. $seqid."  ".$text['success'];
            }else{
                $res_type = 'Error';
                $res_msg  = $text['edit_seq'].':'. $seqid."  ".$text['fail'];
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

            //var_dump($success);
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
            $this->sequenceModel->update_seq_type($seq_data) ;
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
            foreach($old_res as $kk =>$vv){
                $new_temp_seq[$kk]['job_id'] = $vv['job_id'];
                $new_temp_seq[$kk]['sequence_id'] = $newseqid;
                $new_temp_seq[$kk]['sequence_name'] = $newseqname;
                $new_temp_seq[$kk]['sequence_enable'] = $vv['sequence_enable'];
                $new_temp_seq[$kk]['tightening_repeat'] = $vv['tightening_repeat'];
                $new_temp_seq[$kk]['ng_stop'] = $vv['ng_stop']; 
                $new_temp_seq[$kk]['seq_ok'] = $vv['seq_ok']; 
                $new_temp_seq[$kk]['stop_seq_ok'] = $vv['stop_seq_ok']; 
                $new_temp_seq[$kk]['opt'] = $vv['opt']; 
                $new_temp_seq[$kk]['k_value'] = $vv['k_value']; 
                $new_temp_seq[$kk]['offset'] = $vv['offset'];

            }  

            $rows = $this->sequenceModel->copy_seq_by_seq_id($new_temp_seq);
     
        }
        if(!empty($select_step)){
            $new_temp_step = array();
            foreach($select_step as $k_step =>$v_step){
                $new_temp_step[$k_step]['job_id'] = $v_step['job_id'];
                $new_temp_step[$k_step]['sequence_id'] = $newseqid;
                $new_temp_step[$k_step]['step_id'] = $v_step['step_id'];
                $new_temp_step[$k_step]['target_option'] =$v_step['target_option'];
                $new_temp_step[$k_step]['target_torque'] = $v_step['target_torque'];
                $new_temp_step[$k_step]['target_angle'] = $v_step['target_angle'];
                $new_temp_step[$k_step]['target_delaytime'] = $v_step['target_delaytime'];
                $new_temp_step[$k_step]['hi_torque'] = $v_step['hi_torque'];
                $new_temp_step[$k_step]['lo_torque'] = $v_step['lo_torque'];
                $new_temp_step[$k_step]['hi_angle'] = $v_step['hi_angle'];
                $new_temp_step[$k_step]['lo_angle'] = $v_step['lo_angle'];
                $new_temp_step[$k_step]['rpm'] = $v_step['rpm'];
                $new_temp_step[$k_step]['direction'] = $v_step['direction'];
                $new_temp_step[$k_step]['downshift'] = $v_step['downshift'];
                $new_temp_step[$k_step]['threshold_torque'] = $v_step['threshold_torque'];
                $new_temp_step[$k_step]['downshift_torque'] = $v_step['downshift_torque'];
                $new_temp_step[$k_step]['downshift_speed'] = $v_step['downshift_speed'];

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
                
            }
            


        }
    }
}
?>
