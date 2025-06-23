<?php

class Job{
    private $db_iDas;
    // 在建構子將 Database 物件實例化
    public function __construct(){
        
        $db_instance = new Database;
        $this->db_iDas = $db_instance->getDb_das();
  

    }

    #取得所有Job
    public function getJobs() {

        $sql = "SELECT JOB_lst.*, IFNULL(COUNT(SEQ_lst.JOBID), 0) AS total_seq  
                FROM JOB_lst
                LEFT JOIN SEQ_lst ON JOB_lst.JOBID = SEQ_lst.JOBID 
                WHERE JOB_lst.JOBID != ''
                      AND JOB_lst.JOBID != 0 
                      AND JOB_lst.JOBID != 221
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
   
        $sql_select = "SELECT COUNT(*) AS count FROM SEQ_lst WHERE JOBID = ?";
        $statement_select = $this->db_iDas->prepare($sql_select);
        $statement_select->execute([$jobid]);
        $row = $statement_select->fetch(PDO::FETCH_ASSOC);
    
        // 如果存在對應的資料，則刪除
        if ($row['count'] > 0) {
            $sql_delete = "DELETE FROM SEQ_lst WHERE JOBID = ?";
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
        $sql_select = "SELECT COUNT(*) AS count FROM STEP_lst WHERE JOBID = ?";
        $statement_select = $this->db_iDas->prepare($sql_select);
        $statement_select->execute([$jobid]);
        $row = $statement_select->fetch(PDO::FETCH_ASSOC);
    
        //如果存在對應的資料，則刪除
        if ($row['count'] > 0) {
            $sql_delete = "DELETE FROM STEP_lst WHERE JOBID = ? ";
            $statement_delete = $this->db_iDas->prepare($sql_delete);
            $results = $statement_delete->execute([$jobid]);
    
            return $results;
        } else {
            return false; 
        }
    }
    

    #新增JOB
    public function create_job($jobdata) {
        
        $sql = "INSERT INTO `JOB_lst` (JOBID, JOBname, type, time, act, ok_job, ok_job_stop, output_unified, input_unified,job_unit)
                VALUES (:job_id, :job_name, :type, :time ,:act, :ok_job, :ok_job_stop, :output_unified, :input_unified,:job_unit)";
    
        $jobdata['job_id'] = intval($jobdata['job_id']);
        $statement = $this->db_iDas->prepare($sql);
    
        $statement->bindValue(':job_id', $jobdata['job_id']);
        $statement->bindValue(':job_name', $jobdata['job_name']);
        $statement->bindValue(':type', isset($jobdata['type']) ? intval($jobdata['type']) : 1); 
        $statement->bindValue(':act', isset($jobdata['act']) ? intval($jobdata['act']) : 0); 
        $statement->bindValue(':ok_job', isset($jobdata['ok_job']) ? intval($jobdata['ok_job']) : 1);
        $statement->bindValue(':ok_job_stop', isset($jobdata['ok_job_stop']) ? intval($jobdata['ok_job_stop']) : 0); 
        $statement->bindValue(':output_unified', isset($jobdata['output_unified']) ? intval($jobdata['output_unified']) : 0); 
        $statement->bindValue(':input_unified', isset($jobdata['input_unified']) ? intval($jobdata['input_unified']) : 0); 
        $statement->bindValue(':job_unit', isset($jobdata['job_unit']) ? intval($jobdata['job_unit']) : 0); 
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

        $sql= " SELECT *  FROM STEP_lst WHERE JOBID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$old_jobid]);
        
        return $statement->fetchall();     
    }


    

    public function copy_sequence_by_job_id($new_temp_seq) {
        // 准备 SQL 语句，插入数据到 SEQ_lst 表
        $sql = "INSERT INTO `SEQ_lst` (
                    JOBID, SEQID, SEQname, type, time, act, skip, seq_repeat, timeout, 
                    ok_seq, ok_stop, countType, ok_screw, ng_stop, ng_unscrew, interrupt_alarm, 
                    accu_angle, Thread_Calcu, unscrew_mode, unscrew_force, unscrew_rpm, unscrew_dir, 
                    image, message, delay, input, input_signal, output, output_signal, output_durat, 
                    addtion, unscrew_count_switch, unscrew_torque_threshold, seq_unit,unscrew_angle_threshold,
                    dt_time,tt_time
                ) 
                VALUES (
                    :JOBID, :SEQID, :SEQname, :type, :time, :act, :skip, :seq_repeat, :timeout, 
                    :ok_seq, :ok_stop, :countType, :ok_screw, :ng_stop, :ng_unscrew, :interrupt_alarm, 
                    :accu_angle, :Thread_Calcu, :unscrew_mode, :unscrew_force, :unscrew_rpm, :unscrew_dir, 
                    :image, :message, :delay, :input, :input_signal, :output, :output_signal, :output_durat, 
                    :addtion, :unscrew_count_switch, :unscrew_torque_threshold, :seq_unit, :unscrew_angle_threshold,
                    :dt_time, :tt_time
                )";
    

        $statement = $this->db_iDas->prepare($sql);
        $insertedrecords = 0;

        foreach ($new_temp_seq as $seq) {
            try {
      
                if ($statement->execute([
                    ':JOBID' => $seq['JOBID'],
                    ':SEQID' => $seq['SEQID'],
                    ':SEQname' => $seq['SEQname'],
                    ':type' => $seq['type'],
                    ':time' => $seq['time'],
                    ':act' => $seq['act'],
                    ':skip' => $seq['skip'],
                    ':seq_repeat' => $seq['seq_repeat'],
                    ':timeout' => $seq['timeout'],
                    ':ok_seq' => $seq['ok_seq'],
                    ':ok_stop' => $seq['ok_stop'],
                    ':countType' => $seq['countType'],
                    ':ok_screw' => $seq['ok_screw'],
                    ':ng_stop' => $seq['ng_stop'],
                    ':ng_unscrew' => $seq['ng_unscrew'],
                    ':interrupt_alarm' => $seq['interrupt_alarm'],
                    ':accu_angle' => $seq['accu_angle'],
                    ':Thread_Calcu' => $seq['Thread_Calcu'],
                    ':unscrew_mode' => $seq['unscrew_mode'],
                    ':unscrew_force' => $seq['unscrew_force'],
                    ':unscrew_rpm' => $seq['unscrew_rpm'],
                    ':unscrew_dir' => $seq['unscrew_dir'],
                    ':image' => $seq['image'],
                    ':message' => $seq['message'],
                    ':delay' => $seq['delay'],
                    ':input' => $seq['input'],
                    ':input_signal' => $seq['input_signal'],
                    ':output' => $seq['output'],
                    ':output_signal' => $seq['output_signal'],
                    ':output_durat' => $seq['output_durat'],
                    ':addtion' => $seq['addtion'],
                    ':unscrew_count_switch' => $seq['unscrew_count_switch'],
                    ':unscrew_torque_threshold' => $seq['unscrew_torque_threshold'],
                    ':seq_unit' => isset($seq['seq_unit']) ? $seq['seq_unit'] : 0,
                    ':unscrew_angle_threshold' => $seq['unscrew_angle_threshold'] ?? 0,
                    ':dt_time' => $seq['dt_time'] ?? 0,
                    ':tt_time' => $seq['tt_time'] ?? 0,



                ])) {
                 
                    $insertedrecords++;
                } else {
            
                    error_log("Failed to execute query for JOBID: " . $seq['JOBID'] . " SEQID: " . $seq['SEQID']);
                }
            } catch (Exception $e) {
         
                error_log("Error inserting record: " . $e->getMessage());
            }
        }
    
  
        return $insertedrecords;
    }
    
    

    public function copy_step_by_job_id($new_temp_step) {
        
        $sql = "INSERT INTO STEP_lst (
                    JOBID, SEQID, StepSelect, STEPname, type, time, act, 
                    StepSwitch, StepRPM, StepOption, StepTime, StepAngle, StepTorque, 
                    StepDirection, StepDelay, StepMoniByWin, StepLimiHi, StepLimiLo, 
                    StepHiAngle, StepLoAngle, StepHiTorque, StepLoTorque, StepAccelerateOffset, 
                    StepAccelerateOffsetSign, StepEnableTorqueOffset, StepTorqueOffset, 
                    StepTorqueOffsetSign, StepEnableDownShift, StepTorqueDownShift, 
                    StepRPMDownShift, StepEnableThreshold, StepTorqueTS, StepReTry, 
                    StepUnScrew, StepReTryTorq, StepReTryAngl, StepAngleRecord, 
                    StepAutoDetectAngle, InterruptAlarm, OverAngleStop, KValue, step_unit
                ) VALUES (
                    :JOBID, :SEQID, :StepSelect, :STEPname, :type, :time, :act, 
                    :StepSwitch, :StepRPM, :StepOption, :StepTime, :StepAngle, :StepTorque, 
                    :StepDirection, :StepDelay, :StepMoniByWin, :StepLimiHi, :StepLimiLo, 
                    :StepHiAngle, :StepLoAngle, :StepHiTorque, :StepLoTorque, :StepAccelerateOffset, 
                    :StepAccelerateOffsetSign, :StepEnableTorqueOffset, :StepTorqueOffset, 
                    :StepTorqueOffsetSign, :StepEnableDownShift, :StepTorqueDownShift, 
                    :StepRPMDownShift, :StepEnableThreshold, :StepTorqueTS, :StepReTry, 
                    :StepUnScrew, :StepReTryTorq, :StepReTryAngl, :StepAngleRecord, 
                    :StepAutoDetectAngle, :InterruptAlarm, :OverAngleStop, :KValue, :step_unit
                )";
    
        
        $statement = $this->db_iDas->prepare($sql);
        $insertedrecords = 0;
    
        foreach ($new_temp_step as $step) {
            try {

                if ($statement->execute([
                    ':JOBID' => $step['JOBID'],
                    ':SEQID' => $step['SEQID'],
                    ':StepSelect' => $step['StepSelect'],
                    ':STEPname' => $step['STEPname'],
                    ':type' => $step['type'],
                    ':time' => $step['time'],
                    ':act' => $step['act'],
                    ':StepSwitch' => $step['StepSwitch'],
                    ':StepRPM' => $step['StepRPM'],
                    ':StepOption' => $step['StepOption'],
                    ':StepTime' => $step['StepTime'],
                    ':StepAngle' => $step['StepAngle'],
                    ':StepTorque' => $step['StepTorque'],
                    ':StepDirection' => $step['StepDirection'],
                    ':StepDelay' => $step['StepDelay'],
                    ':StepMoniByWin' => $step['StepMoniByWin'],
                    ':StepLimiHi' => $step['StepLimiHi'],
                    ':StepLimiLo' => $step['StepLimiLo'],
                    ':StepHiAngle' => $step['StepHiAngle'],
                    ':StepLoAngle' => $step['StepLoAngle'],
                    ':StepHiTorque' => $step['StepHiTorque'],
                    ':StepLoTorque' => $step['StepLoTorque'],
                    ':StepAccelerateOffset' => $step['StepAccelerateOffset'],
                    ':StepAccelerateOffsetSign' => $step['StepAccelerateOffsetSign'],
                    ':StepEnableTorqueOffset' => $step['StepEnableTorqueOffset'],
                    ':StepTorqueOffset' => $step['StepTorqueOffset'],
                    ':StepTorqueOffsetSign' => $step['StepTorqueOffsetSign'],
                    ':StepEnableDownShift' => $step['StepEnableDownShift'],
                    ':StepTorqueDownShift' => $step['StepTorqueDownShift'],
                    ':StepRPMDownShift' => $step['StepRPMDownShift'],
                    ':StepEnableThreshold' => $step['StepEnableThreshold'],
                    ':StepTorqueTS' => $step['StepTorqueTS'],
                    ':StepReTry' => $step['StepReTry'],
                    ':StepUnScrew' => $step['StepUnScrew'],
                    ':StepReTryTorq' => $step['StepReTryTorq'],
                    ':StepReTryAngl' => $step['StepReTryAngl'],
                    ':StepAngleRecord' => $step['StepAngleRecord'],
                    ':StepAutoDetectAngle' => $step['StepAutoDetectAngle'],
                    ':InterruptAlarm' => $step['InterruptAlarm'],
                    ':OverAngleStop' => $step['OverAngleStop'],
                    ':KValue' => $step['KValue'],
                    ':step_unit' => isset($step['step_unit']) ? $step['step_unit'] : 0,
                ])) {

                    $insertedrecords++;
                } else {

                    error_log("Failed to execute query for JOBID: " . $step['JOBID'] . " SEQID: " . $step['SEQID']);
                }
            } catch (Exception $e) {
                error_log("Error inserting record: " . $e->getMessage());
            }
        }
    
        return $insertedrecords;
    }

    

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
        $sql = "SELECT COUNT(*) FROM JOBInput_lst WHERE JOBID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM JOBInput_lst  WHERE JOBID = ? ";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$new_jobid]);

            return true;
        } else {
            return false;
        }
    }


    public function delete_output_by_job_id($new_jobid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM JOBOutput_lst WHERE JOBID  = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$new_jobid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM JOBOutput_lst  WHERE JOBID	 = ? ";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$new_jobid]);

            return true;
        } else {
            return false;
        }
    }


    //查詢 job_id 還沒有 被使用的 取出 最小值
    public function get_head_job_id() {

        // 檢查 job_id 是否有 1，如果沒有就直接返回 1
        $query = "SELECT JOBID FROM JOB_lst WHERE JOBID  = 1 ";
        $statement = $this->db_iDas->prepare($query);
        $statement->execute();
    
        $result = $statement->fetch();
        if (!$result) {
            return array('missing_id' => 1); // 如果 job_id = 1 不存在，返回 1
        }
    
        // 如果 job_id = 1 存在，查找最小的可用 job_id
        $query = "SELECT JOBID + 1 AS missing_id
                    FROM JOB_lst
                    WHERE (JOBID + 1) NOT IN (SELECT JOBID FROM JOB_lst)
                    ORDER BY missing_id
                    LIMIT 1";
    
        $statement = $this->db_iDas->prepare($query);
        $statement->execute();
    
        return $statement->fetch();
    }
    





}
