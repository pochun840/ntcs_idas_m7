<?php
class Jobs extends Controller
{
    private $jobModel;
    private $DashboardModel;
    private $ToolModel;
    private $SettingModel;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->jobModel = $this->model('Job');
        $this->DashboardModel = $this->model('Dashboard');
        $this->MiscellaneousModel = $this->model('Miscellaneous');


    }

    // 取得所有Jobs
    public function index(){
        $data = array();
        

        $isMobile  = $this->isMobileCheck();
        $jobs      = $this->jobModel->getJobs();
        $direction = $this->MiscellaneousModel->details('reverse_direction');


        if(!empty($jobs)){
            $lastRow  = end($jobs);
            $jobIdInt = intval($lastRow['JOBID']) + 1 ;   
        }else{
            $lastRow  = ""; 
            $jobIdInt = "";
        }
        ;

        $data = array(
            'jobint' => $jobIdInt,
            'jobs' => $jobs
        );
        
        if($isMobile){
            $this->view('jobs/job_management_m',$data);
        }else{
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

            $jobdata = array(
                'job_id' => $_POST['jobidnew'],
                'job_name' => $_POST['jobname_val'],
                'type' => 1,
                'time' =>  date('Y-m-d H:i:s'),
                'act' => 0,
                'ok_job' => $_POST['job_ok_val'],
                'stop_job_ok' => $_POST['stop_job_ok_val'],
                'output_unified' => 0,
                'input_unified'  => 0
      
            );

   
    
            $job_count = $this->jobModel->countjob();
            if($job_count >= 100) {
                $this->MiscellaneousModel->generateErrorResponse('Error', $error_message['job_id']);
                exit();
            }
    
            $res = $this->jobModel->create_job($jobdata);
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
            // $ans = $this->jobModel->delete_sequence_by_job_id($jobid);
            // $an1 = $this->jobModel->delete_step_by_job_id($jobid);
            // $an2 = $this->jobModel->delete_input_by_job_id($jobid);
            // $an3 = $this->jobModel->delete_output_by_job_id($jobid);

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
                    //$select_step = $this->jobModel->search_stepnfo($old_jobid); 

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
                            
                        }

                        $insertedrecords = $this->jobModel->copy_sequence_by_job_id($new_temp_seq);                
                    }

                    /*if(!empty($select_step)){
                        $new_temp_step = array();
                        
                        foreach($select_step as $key_step =>$val_step){

                            $new_temp_step[$key_step]['job_id'] = $new_jobid;
                            $new_temp_step[$key_step]['sequence_id'] = $val_step['sequence_id'];
                            $new_temp_step[$key_step]['step_id'] = $val_step['step_id'];
                            $new_temp_step[$key_step]['target_option'] = $val_step['target_option']; 
                            $new_temp_step[$key_step]['target_torque'] = $val_step['target_torque'];
                            $new_temp_step[$key_step]['target_angle'] = $val_step['target_angle'];
                            $new_temp_step[$key_step]['target_delaytime'] = $val_step['target_delaytime'];
                            $new_temp_step[$key_step]['hi_torque'] = $val_step['hi_torque'];
                            $new_temp_step[$key_step]['lo_torque'] = $val_step['lo_torque'];
                            $new_temp_step[$key_step]['hi_angle'] = $val_step['hi_angle'];
                            $new_temp_step[$key_step]['lo_angle'] = $val_step['lo_angle'];
                            $new_temp_step[$key_step]['rpm'] = $val_step['rpm'];
                            $new_temp_step[$key_step]['direction'] = $val_step['direction'];
                            $new_temp_step[$key_step]['downshift'] = $val_step['downshift'];
                            $new_temp_step[$key_step]['threshold_torque'] = $val_step['threshold_torque'];
                            $new_temp_step[$key_step]['downshift_torque'] = $val_step['downshift_torque'];
                            $new_temp_step[$key_step]['downshift_speed'] = $val_step['downshift_speed'];
                        }
                      
                        $res = $this->jobModel->copy_step_by_job_id($new_temp_step);     
                    }*/
                    
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

}

?>
