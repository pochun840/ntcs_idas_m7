<?php

class Job{
    private $db;//condb control box
    private $db_dev;//devdb tool
    private $dbh;
    private $tool_max_rpm;
    private $tool_min_rpm;
    private $db_iDas;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();
  

    }

    #取得所有Job
    public function getJobs() {

        $sql = "SELECT JOB_lst.*, IFNULL(COUNT(SEQ_lst.JOBID), 0) AS total_seq  
                FROM JOB_lst
                LEFT JOIN SEQ_lst ON JOB_lst.JOBID = SEQ_lst.JOBID 
                WHERE JOB_lst.JOBID != ''
                GROUP BY JOB_lst.JOBID";

        $statement = $this->db_iDas->prepare($sql);
    
        if (!$statement) {
            throw new Exception('SQL Error: ' . implode(', ', $this->db_iDas->errorInfo()));
        }
    
        if (!$statement->execute()) {
            throw new Exception('Execute Error: ' . implode(', ', $statement->errorInfo()));
        }
    
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
    

    #刪除JOB 
    public function delete_job_by_id($jobid){

        $sql= "DELETE FROM JOB_lst WHERE JOBID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$jobid]);
        return $results;
    }

    #刪除sequence
    public function delete_sequence_by_job_id($jobid) {
   
        $sql_select = "SELECT COUNT(*) AS count FROM sequence WHERE job_id = ?";
        $statement_select = $this->db_iDas->prepare($sql_select);
        $statement_select->execute([$jobid]);
        $row = $statement_select->fetch(PDO::FETCH_ASSOC);
    
        // 如果存在對應的資料，則刪除
        if ($row['count'] > 0) {
            $sql_delete = "DELETE FROM sequence WHERE job_id = ?";
            $statement_delete = $this->db_iDas->prepare($sql_delete);
            $results = $statement_delete->execute([$jobid]);
    
            return $results;
        } else {
          
            return false; 
        }
    }
    
    #刪除step 
    public function delete_step_by_job_id($jobid) {
        
        //首先查詢是否存在對應的資料
        $sql_select = "SELECT COUNT(*) AS count FROM step WHERE job_id = ?";
        $statement_select = $this->db_iDas->prepare($sql_select);
        $statement_select->execute([$jobid]);
        $row = $statement_select->fetch(PDO::FETCH_ASSOC);
    
        //如果存在對應的資料，則刪除
        if ($row['count'] > 0) {
            $sql_delete = "DELETE FROM step WHERE job_id = ?";
            $statement_delete = $this->db_iDas->prepare($sql_delete);
            $results = $statement_delete->execute([$jobid]);
    
            return $results;
        } else {
            return false; 
        }
    }
    

    #新增JOB
    public function create_job($jobdata) {
        
        $sql = "INSERT INTO `JOB_lst` (JOBID, JOBname, type, time, act, ok_job, ok_job_stop, output_unified, input_unified)
                VALUES (:job_id, :job_name, :type, :time ,:act, :ok_job, :ok_job_stop, :output_unified, :input_unified)";
    
        $jobdata['job_id'] = intval($jobdata['job_id']);
        $statement = $this->db_iDas->prepare($sql);
    
        $statement->bindValue(':job_id', $jobdata['job_id']);
        $statement->bindValue(':job_name', $jobdata['job_name']);
        $statement->bindValue(':type', isset($jobdata['type']) ? intval($jobdata['type']) : 1); 
        $statement->bindValue(':act', isset($jobdata['act']) ? intval($jobdata['act']) : 0); 
        $statement->bindValue(':ok_job', isset($jobdata['ok_job']) ? intval($jobdata['ok_job']) : 1);
        $statement->bindValue(':ok_job_stop', isset($jobdata['ok_job_stop']) ? intval($jobdata['ok_job_stop']) : 1); 
        $statement->bindValue(':output_unified', isset($jobdata['output_unified']) ? intval($jobdata['output_unified']) : 0); 
        $statement->bindValue(':input_unified', isset($jobdata['input_unified']) ? intval($jobdata['input_unified']) : 0); 
        $statement->bindValue(':time', date('Y-m-d H:i:s')); 
        $results = $statement->execute();    
    
        return $results;
    }
    
    #修改JOB
    public function update_job_by_id($jobdata){
        
        $sql = "UPDATE `JOB_lst` SET  
                JOBname = :job_name, 
                time = :time,
                ok_job = :ok_job,
                ok_job_stop = :ok_job_stop
                WHERE JOBID = :job_id ";

        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':job_name', $jobdata['job_name']);

        $statement->bindValue(':time', date('Y-m-d H:i:s'));
        $statement->bindValue(':ok_job', $jobdata['ok_job']);
        $statement->bindValue(':ok_job_stop', $jobdata['ok_job_stop']);
        $statement->bindValue(':job_id', intval($jobdata['job_id'])); 
        $results = $statement->execute();

        return $results;

    }

    #查詢JOB 
    public function search_jobinfo($jobid){
        $sql= "SELECT * FROM JOB_lst WHERE JOBID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid]);
        $rows = $statement->fetch();
        return $rows;
    }


    #計算 有幾個JOB
    public function countjob(){

        $sql = "SELECT  COUNT(*) as count FROM JOB_lst ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute();
        $result = $statement->fetch();
        return $result['count'];
    }


    #驗證job id是否重複
    public function job_id_repeat($jobid)
    {
        $sql = "SELECT count(*) as count FROM JOB_lst WHERE JOBID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$jobid]);
        $rows = $statement->fetch();

        if ($rows['count'] > 0) {
            return "True"; // job_id已存在

        }else{
            return "False"; // job_id不存在
        }
    }

    #查詢job_id對應的seq
    public function search_seqinfo($old_jobid){

        $sql= " SELECT *  FROM SEQ_lst WHERE JOBID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$old_jobid]);
        
        return $statement->fetchall();

    }




    public function search_stepnfo($old_jobid){

        $sql= " SELECT *  FROM step WHERE job_id = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$old_jobid]);
        
        return $statement->fetchall();     
    }


    

    public function copy_sequence_by_job_id($new_temp_seq) {
      
        $sql = "INSERT INTO `SEQ_lst` (JOBID, SEQID, SEQname, type, time, act, skip, seq_repeat, timeout, ok_seq, ok_stop, countType, ok_screw, ng_stop, ng_unscrew, interrupt_alarm, accu_angle, Thread_Calcu, unscrew_mode, unscrew_force, unscrew_rpm, unscrew_dir, image, message, delay, input, input_signal, output, output_signal, output_durat, addtion, unscrew_count_switch, unscrew_torque_threshold)"; 
        $sql.= "VALUES (:JOBID, :SEQID, :SEQname, :type, :time, :act, :skip, :seq_repeat, :timeout, :ok_seq, :ok_stop, :countType, :ok_screw, :ng_stop, :ng_unscrew, :interrupt_alarm, :accu_angle, :Thread_Calcu, :unscrew_mode, :unscrew_force, :unscrew_rpm, :unscrew_dir, :image, :message, :delay, :input, :input_signal, :output, :output_signal, :output_durat, :addtion, :unscrew_count_switch, :unscrew_torque_threshold);";
        
        
        $statement = $this->db_iDas->prepare($sql);
        $insertedrecords = 0; 
        foreach ($new_temp_seq as $seq) {            
            if ($statement->execute($seq)) {
                $insertedrecords++;
            }
        }
        return $insertedrecords;
    }
    

    /*public function copy_step_by_job_id($new_temp_step){
        $sql = "INSERT INTO `step` (job_id, sequence_id, step_id, target_option, target_torque, target_angle, target_delaytime, hi_torque, lo_torque, hi_angle, lo_angle, rpm, direction, downshift, threshold_torque, 	downshift_torque,downshift_speed )";
        $sql .= " VALUES (:job_id,:sequence_id,:step_id,:target_option,:target_torque,:target_angle,:target_delaytime,:hi_torque,:lo_torque,:hi_angle,:lo_angle,:rpm,:direction,:downshift,:threshold_torque,:downshift_torque,:downshift_speed )";
        
        $statement = $this->db_iDas->prepare($sql);
        $insertedrecords = 0; 
        foreach ($new_temp_step as $seq) {            
            if ($statement->execute($seq)) {
                $insertedrecords++;
            }
        }
        return $insertedrecords;

    }*/


    #用 $jobid 尋找有沒有對應的資料
    #有的話就刪除唷
    public function del_job_type($new_jobid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM JOB_lst WHERE JOBID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM JOB_lst  WHERE JOBID= ? ";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$new_jobid]);

            return true;
        } else {
            return false;
        }
    }

    public function del_seq_type($new_jobid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM SEQ_lst WHERE JOBID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM SEQ_lst  WHERE JOBID = ? ";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$new_jobid]);

            return true;
        } else {
            return false;
        }
    }


    public function del_step_type($new_jobid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM STEP_lst WHERE JOBID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM STEP_lst  WHERE JOBID = ? ";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$new_jobid]);

            return true;
        } else {
            return false;
        }
    }


    public function delete_input_by_job_id($new_jobid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM input WHERE input_job_id = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM input  WHERE input_job_id = ? ";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$new_jobid]);

            return true;
        } else {
            return false;
        }
    }


    public function delete_output_by_job_id($new_jobid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM output WHERE output_job_id  = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM output  WHERE  output_job_id	 = ? ";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$new_jobid]);

            return true;
        } else {
            return false;
        }
    }






}
