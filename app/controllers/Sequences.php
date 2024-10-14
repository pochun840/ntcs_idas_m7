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

        if(isset($_POST['job_id'])){
        
            // 初始化數據陣列
            $seq_data = array(
                'job_id' => $_POST['job_id'] ?? null,
                'SEQID'  => $_POST['SEQID'] ?? null,
                'SEQname' => $_POST['SEQname'] ?? null,
                'time' => $_POST['time'] ?? null,
                'type' => $_POST['type'] ?? null,
                'act' => $_POST['act'] ?? null,
                'skip' => $_POST['skip'] ?? null,
                'seq_repeat' => $_POST['seq_repeat'] ?? null,
                'timeout' => $_POST['timeout'] ?? null,
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
                'unscrew_force' => $_POST['unscrew_force_val'] ?? null,
                'unscrew_rpm' => $_POST['unscrew_rpm'] ?? null,
                'unscrew_dir' => $_POST['unscrew_dir_val'] ?? null,
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

            );

            if ($seq_data['job_id'] === null ||$seq_data['SEQID'] === null ||$seq_data['SEQname'] === null) {
                echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
                exit;
            }


            $mode = "create";
            $res = $this->sequenceModel->create_seq($mode,$seq_data);
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
        }

        $data = array(
            'seq_info' => $res,
            'job_id' => $job_id,
            'seq_id' => $seq_id
        );
    }

    public function edit_seq(){


        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }


    
  
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
                'unscrew_force' => $_POST['unscrew_force_val'] ?? null,
                'unscrew_rpm' => $_POST['unscrew_rpm'] ?? null,
                'unscrew_dir' => $_POST['unscrew_dir_val'] ?? null,
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
                $new_temp_seq[$kk_seq]['addtion'] ='';
                $new_temp_seq[$kk_seq]['unscrew_count_switch'] = $val['unscrew_count_switch'];
                $new_temp_seq[$kk_seq]['unscrew_torque_threshold'] = $val['unscrew_torque_threshold'];
                

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


    public function variation($job_id = null, $seq_id = null) {
        // 如果沒有提供 job_id，則設為預設值 1
        if (empty($job_id)) {
            $job_id = 1;
        }
    
        // 如果沒有提供 seq_id，則視為新增，並設為空值
        if (empty($seq_id)) {
            // 根據 job_id 取得所有相關的 sequences
            $sequences = $this->sequenceModel->getSequences_by_job_id($job_id);
            
            // 如果沒有找到任何 sequences，則將 seq_id 設為 1；否則，將 seq_id 設為 sequences 數量 + 1
            if (empty($sequences)) {
                $seq_id = 1;
            } else {
                $seq_id = count($sequences) + 1;
            }
            $type = 'new';
        } else {

            $type = 'edit';
            $res = $this->sequenceModel->search_seqinfo($job_id, $seq_id);
            
            if (empty($res)) {
                echo "Job ID 或 Sequence ID 無效！";
                return;
            }
            
            $sequences = $res[0];
        }
    
        $data = array(
            'sequences' => $sequences,
            'job_id' => $job_id,
            'seq_id' => $seq_id,
            'type' => $type
        );
        echo $this->view('sequences/add_seq', $data);
    }
        
}
?>
