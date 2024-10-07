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
        //$tool_rpm = $this->dbh->get_tool_rpm();
        //$this->tool_max_rpm = $tool_rpm['tool_maxrpm'];
        //$this->tool_min_rpm = $tool_rpm['tool_minrpm'];

    }

    #取得所有Job
    public function getJobs(){

        $sql = "SELECT job.*, IFNULL(COUNT(sequence.job_id), 0) as total_seq  
                FROM `job`
                LEFT JOIN sequence on job.job_id = sequence.job_id 
                WHERE job.job_id != ''
                GROUP BY job.job_id ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute();


        return $statement->fetchall();
    }

    #刪除JOB 
    public function delete_job_by_id($jobid){

        $sql= "DELETE FROM job WHERE job_id = ?";
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
    public function create_job($jobdata){
      
        $sql = "INSERT INTO `job` (job_id, job_name, reverse_direction,reverse_rpm,reverse_power, job_ok,stop_job_ok)";
        $sql .= " VALUES (:job_id, :job_name, :reverse_direction, :reverse_rpm, :reverse_power ,:job_ok,:stop_job_ok);";
    
        $jobdata['job_id'] = intval($jobdata['job_id']);
    
        $statement = $this->db_iDas->prepare($sql);
    
        $statement->bindValue(':job_id', $jobdata['job_id']);
        $statement->bindValue(':job_name', $jobdata['job_name']);
        $statement->bindValue(':reverse_power', $jobdata['reverse_power']);
        $statement->bindValue(':reverse_rpm', $jobdata['reverse_rpm']);
        $statement->bindValue(':reverse_direction', $jobdata['reverse_direction']);
        $statement->bindValue(':job_ok', $jobdata['job_ok']);
        $statement->bindValue(':stop_job_ok', $jobdata['stop_job_ok']);
        $results = $statement->execute();    
        return $results;
    }
    
    #修改JOB
    public function update_job_by_id($jobdata){
        
        $sql = "UPDATE `job` SET  
                job_name = :job_name, 
                reverse_direction = :reverse_direction, 
                reverse_rpm = :reverse_rpm, 
                reverse_power = :reverse_power,
                job_ok = :job_ok,
                stop_job_ok =:stop_job_ok
                WHERE job_id = :job_id ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':job_name', $jobdata['job_name']);
        $statement->bindValue(':reverse_power', $jobdata['reverse_power']);
        $statement->bindValue(':reverse_rpm', $jobdata['reverse_rpm']);
        $statement->bindValue(':reverse_direction', $jobdata['reverse_direction']);
        $statement->bindValue(':job_ok', $jobdata['job_ok']);
        $statement->bindValue(':stop_job_ok', $jobdata['stop_job_ok']);
        $statement->bindValue(':job_id', $jobdata['job_id']);
        $results = $statement->execute();

        return $results;

    }

    #查詢JOB 
    public function search_jobinfo($jobid){
        $sql= "SELECT * FROM job WHERE job_id = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid]);
        $rows = $statement->fetch();
        return $rows;
    }


    #計算 有幾個JOB
    public function countjob(){

        $sql = "SELECT  COUNT(*) as count FROM job ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute();
        $result = $statement->fetch();
        return $result['count'];
    }


    #驗證job id是否重複
    public function job_id_repeat($jobid)
    {
        $sql = "SELECT count(*) as count FROM job WHERE job_id = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$jobid]);
        $rows = $statement->fetch();

        if ($rows['count'] > 0) {
            //$this->del_job_type($jobid);
            return "True"; // job_id已存在

        }else{
            return "False"; // job_id不存在
        }
    }

    #查詢job_id對應的seq
    public function search_seqinfo($old_jobid){

        $sql= " SELECT *  FROM sequence WHERE job_id = ? ";
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
      
        $sql = "INSERT INTO `sequence` (job_id, sequence_id, sequence_name, sequence_enable, tightening_repeat, ng_stop, seq_ok, stop_seq_ok, opt, k_value, offset)";
        $sql .= " VALUES (:job_id, :sequence_id, :sequence_name, :sequence_enable, :tightening_repeat, :ng_stop, :seq_ok, :stop_seq_ok, :opt, :k_value, :offset);";
        
        
        $statement = $this->db_iDas->prepare($sql);
        $insertedrecords = 0; 
        foreach ($new_temp_seq as $seq) {            
            if ($statement->execute($seq)) {
                $insertedrecords++;
            }
        }
        return $insertedrecords;
    }
    

    public function copy_step_by_job_id($new_temp_step){
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

    }


    #用 $jobid 尋找有沒有對應的資料
    #有的話就刪除唷
    public function del_job_type($new_jobid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM job WHERE job_id = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM job  WHERE job_id = ? ";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$new_jobid]);

            return true;
        } else {
            return false;
        }
    }

    public function del_seq_type($new_jobid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM sequence WHERE job_id = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM sequence  WHERE job_id = ? ";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$new_jobid]);

            return true;
        } else {
            return false;
        }
    }


    public function del_step_type($new_jobid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM step WHERE job_id = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM step  WHERE job_id = ? ";
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
