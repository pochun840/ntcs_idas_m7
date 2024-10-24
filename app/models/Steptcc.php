<?php

class Steptcc{
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


    #透過 job_id 及 seq_id 取得當前有幾個step
    public function countstep($jobid, $seqid){

        $sql = "SELECT COUNT(*) as count FROM STEP_lst WHERE JOBID = ? AND SEQID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid, $seqid]);
        $result = $statement->fetch();
        return $result['count'];
    }

    #透過job_id 及 seq_id 取得對應的step
    public function getStep($job_id, $seq_id) {

        $sql = "SELECT * FROM STEP_lst WHERE JOBID = ? AND SEQID = ? ORDER BY 	StepSelect	 ASC ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$job_id, $seq_id]);
        return $statement->fetchAll();
    }

    public function getStep_count($job_id, $seq_id) {

        $sql = "SELECT COUNT(*) as total  FROM STEP_lst WHERE JOBID = ? AND SEQID = ? ORDER BY 	StepSelect	 ASC ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$job_id, $seq_id]);
        return $statement->fetchAll();
    }

    #透過job_id 及 seq_id 及 step_id取得對應的資料
    public function getStepNo($jobid,$seqid,$stepid){

        $sql = "SELECT * FROM STEP_lst WHERE JOBID = ? AND 	SEQID = ? AND StepSelect = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid, $seqid, $stepid]);
        return $statement->fetchAll();

    }

    #檢查同一個seq中所建立的Step Target Torque 只能有一個
    public function check_step_target($jobid,$seqid){

        $sql = "SELECT COUNT(*) AS count_records FROM step WHERE job_id = ? AND sequence_id = ?  AND target_option = '0'  ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid, $seqid]);
        return $statement->fetchAll();
    }

    #COPY專用 檢查被複製的Step_id 有沒有設置Target Torque
    public function check_copy_step($jobid,$seqid,$stepid){
        
        $sql = "SELECT target_option  FROM step WHERE job_id = ? AND sequence_id = ?  AND step_id = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid,$seqid,$stepid]);
        return $statement->fetchAll();
    }



    #透過 job_id 及 seq_id 及 step_id 刪除對應的資料
    public function delete_step_id($jobid, $seqid, $stepid) {

        $sql = "DELETE FROM STEP_lst WHERE JOBID = ? AND SEQID = ? AND StepSelect = ?";
        $statement = $this->db_iDas->prepare($sql);
        
        $results = $statement->execute([$jobid, $seqid, $stepid]);
    
        if ($results) {
            $sql_update = "UPDATE STEP_lst SET StepSelect = StepSelect - 1 WHERE JOBID = ? AND SEQID = ? AND StepSelect > ?";
            $statement_update = $this->db_iDas->prepare($sql_update);
            
            if ($statement_update) {
                $statement_update->execute([$jobid, $seqid, $stepid]);
            } else {
                echo "Failed to prepare update statement: " . implode(", ", $this->db_iDas->errorInfo());
            }
        } else {
            echo "Failed to execute delete statement: " . implode(", ", $statement->errorInfo());
        }
    
        return $results;
    }
    


    public function create_step($step_data) {
   
    
        if (empty($step_data['JOBID'])) {
            return false; 
        }
        
        $sql = "INSERT INTO `STEP_lst` (JOBID, SEQID, StepSelect, STEPname, type, time, act, StepSwitch, StepRPM, StepOption, StepTime, StepAngle, StepTorque, StepDirection, StepDelay, StepMoniByWin, StepLimiHi, StepLimiLo, StepHiAngle, StepLoAngle, StepHiTorque, StepLoTorque, StepAccelerateOffset, StepAccelerateOffsetSign, StepEnableTorqueOffset, StepTorqueOffset, StepTorqueOffsetSign, StepEnableDownShift, StepTorqueDownShift, StepRPMDownShift, StepEnableThreshold, StepTorqueTS, StepReTry, StepUnScrew, StepReTryTorq, StepReTryAngl, StepAngleRecord, StepAutoDetectAngle, InterruptAlarm, OverAngleStop) ";
        $sql .= "VALUES (:jobid, :seqid, :step_select, :stepname, :type, :time, :act, :step_switch, :step_rpm, :step_option, :step_time, :step_angle, :step_torque, :step_direction, :step_delay, :step_moni_by_win, :step_limi_hi, :step_limi_lo, :step_hi_angle, :step_lo_angle, :step_hi_torque, :step_lo_torque, :step_accelerate_offset, :step_accelerate_offset_sign, :step_enable_torque_offset, :step_torque_offset, :step_torque_offset_sign, :step_enable_down_shift, :step_torque_down_shift, :step_rpm_down_shift, :step_enable_threshold, :step_torque_ts, :step_retry, :step_unscrew, :step_retry_torq, :step_retry_angl, :step_angle_record, :step_auto_detect_angle, :interrupt_alarm, :over_angle_stop);";
    
        if ($this->db_iDas === null) {
            echo "数据库连接无效。";
            return false;
        }
    
        $statement = $this->db_iDas->prepare($sql);
        
        // 检查 prepare 是否成功
        if (!$statement) {
            echo "SQL 错误: " . implode(", ", $this->db_iDas->errorInfo());
            return false;
        }
     
        
        $statement->bindValue(':jobid', $step_data['JOBID']);
        $statement->bindValue(':seqid', $step_data['SEQID']);
        $statement->bindValue(':step_select', $step_data['StepSelect']);
        $statement->bindValue(':stepname', $step_data['STEPname']);
        $statement->bindValue(':type', $step_data['type']);
        $statement->bindValue(':time', $step_data['time']);
        $statement->bindValue(':act', $step_data['act']);
        $statement->bindValue(':step_switch', $step_data['StepSwitch']);
        $statement->bindValue(':step_rpm', $step_data['StepRPM']);
        $statement->bindValue(':step_option', $step_data['StepOption']);
        $statement->bindValue(':step_time', $step_data['StepTime']);
        $statement->bindValue(':step_angle', $step_data['StepAngle']);
        $statement->bindValue(':step_torque',$step_data['StepTorque']);
        $statement->bindValue(':step_direction', $step_data['StepDirection']);
        $statement->bindValue(':step_delay', $step_data['StepDelay']);
        $statement->bindValue(':step_moni_by_win',$step_data['StepMoniByWin']);
        $statement->bindValue(':step_limi_hi', $step_data['StepLimiHi']);
        $statement->bindValue(':step_limi_lo', $step_data['StepLimiLo']);
        $statement->bindValue(':step_hi_angle', $step_data['StepHiAngle']);
        $statement->bindValue(':step_lo_angle', $step_data['StepLoAngle']);
        $statement->bindValue(':step_hi_torque', $step_data['StepHiTorque']);
        $statement->bindValue(':step_lo_torque', $step_data['StepLoTorque']);
        $statement->bindValue(':step_accelerate_offset', $step_data['StepAccelerateOffset']);
        $statement->bindValue(':step_accelerate_offset_sign', $step_data['StepAccelerateOffsetSign']);
        $statement->bindValue(':step_enable_torque_offset', $step_data['StepEnableTorqueOffset']);
        $statement->bindValue(':step_torque_offset', $step_data['StepTorqueOffset']);
        $statement->bindValue(':step_torque_offset_sign', $step_data['StepTorqueOffsetSign']);
        $statement->bindValue(':step_enable_down_shift', $step_data['StepEnableDownShift']);
        $statement->bindValue(':step_torque_down_shift', $step_data['StepTorqueDownShift']);
        $statement->bindValue(':step_rpm_down_shift', $step_data['StepRPMDownShift']);
        $statement->bindValue(':step_enable_threshold', $step_data['StepEnableThreshold']);
        $statement->bindValue(':step_torque_ts', $step_data['StepTorqueTS']);
        $statement->bindValue(':step_retry', $step_data['StepReTry']);
        $statement->bindValue(':step_unscrew', $step_data['StepUnScrew']);
        $statement->bindValue(':step_retry_torq', $step_data['StepReTryTorq']);
        $statement->bindValue(':step_retry_angl', $step_data['StepReTryAngl']);
        $statement->bindValue(':step_angle_record', $step_data['StepAngleRecord']);
        $statement->bindValue(':step_auto_detect_angle', $step_data['StepAutoDetectAngle']);
        $statement->bindValue(':interrupt_alarm', $step_data['InterruptAlarm']);
        $statement->bindValue(':over_angle_stop', $step_data['OverAngleStop']);
    
        // 执行查询并检查结果
        $results = $statement->execute();
        if (!$results) {
            echo "执行错误: " . implode(", ", $statement->errorInfo());
        }
    
        return $results;
    }
    


    public function update_step_by_id($step_data){
        
        if (empty($step_data['JOBID']) || empty($step_data['SEQID']) || empty($step_data['StepSelect'])) {
            return false; 
        }


        $sql = "UPDATE `STEP_lst` SET 
                    STEPname = :stepname,
                    type = :type,
                    time = :time,
                    act = :act,
                    StepSwitch = :step_switch,
                    StepRPM = :step_rpm,
                    StepOption = :step_option,
                    StepTime = :step_time,
                    StepAngle = :step_angle,
                    StepTorque = :step_torque,
                    StepDirection = :step_direction,
                    StepDelay = :step_delay,
                    StepMoniByWin = :step_moni_by_win,
                    StepLimiHi = :step_limi_hi,
                    StepLimiLo = :step_limi_lo,
                    StepHiAngle = :step_hi_angle,
                    StepLoAngle = :step_lo_angle,
                    StepHiTorque = :step_hi_torque,
                    StepLoTorque = :step_lo_torque,
                    StepAccelerateOffset = :step_accelerate_offset,
                    StepAccelerateOffsetSign = :step_accelerate_offset_sign,
                    StepEnableTorqueOffset = :step_enable_torque_offset,
                    StepTorqueOffset = :step_torque_offset,
                    StepTorqueOffsetSign = :step_torque_offset_sign,
                    StepEnableDownShift = :step_enable_down_shift,
                    StepTorqueDownShift = :step_torque_down_shift,
                    StepRPMDownShift = :step_rpm_down_shift,
                    StepEnableThreshold = :step_enable_threshold,
                    StepTorqueTS = :step_torque_ts,
                    StepReTry = :step_retry,
                    StepUnScrew = :step_unscrew,
                    StepReTryTorq = :step_retry_torq,
                    StepReTryAngl = :step_retry_angl,
                    StepAngleRecord = :step_angle_record,
                    StepAutoDetectAngle = :step_auto_detect_angle,
                    InterruptAlarm = :interrupt_alarm,
                    OverAngleStop = :over_angle_stop
                WHERE JOBID = :jobid AND SEQID = :seqid  AND StepSelect = :step_select;";


        if ($this->db_iDas === null) {
            echo "数据库连接无效。";
            return false;
        }

        $statement = $this->db_iDas->prepare($sql);

        // 检查 prepare 是否成功
        if (!$statement) {
            echo "SQL 错误: " . implode(", ", $this->db_iDas->errorInfo());
            return false;
        }

        $statement->bindValue(':jobid', $step_data['JOBID']);
        $statement->bindValue(':seqid', $step_data['SEQID']);
        $statement->bindValue(':step_select', $step_data['StepSelect']);
        $statement->bindValue(':stepname', $step_data['STEPname']);
        $statement->bindValue(':type', $step_data['type']);
        $statement->bindValue(':time', $step_data['time']);
        $statement->bindValue(':act', $step_data['act']);
        $statement->bindValue(':step_switch', $step_data['StepSwitch']);
        $statement->bindValue(':step_rpm', $step_data['StepRPM']);
        $statement->bindValue(':step_option', $step_data['StepOption']);
        $statement->bindValue(':step_time', $step_data['StepTime']);
        $statement->bindValue(':step_angle', $step_data['StepAngle']);
        $statement->bindValue(':step_torque', $step_data['StepTorque']);
        $statement->bindValue(':step_direction', $step_data['StepDirection']);
        $statement->bindValue(':step_delay', $step_data['StepDelay']);
        $statement->bindValue(':step_moni_by_win', $step_data['StepMoniByWin']);
        $statement->bindValue(':step_limi_hi', $step_data['StepLimiHi']);
        $statement->bindValue(':step_limi_lo', $step_data['StepLimiLo']);
        $statement->bindValue(':step_hi_angle', $step_data['StepHiAngle']);
        $statement->bindValue(':step_lo_angle', $step_data['StepLoAngle']);
        $statement->bindValue(':step_hi_torque', $step_data['StepHiTorque']);
        $statement->bindValue(':step_lo_torque', $step_data['StepLoTorque']);
        $statement->bindValue(':step_accelerate_offset', $step_data['StepAccelerateOffset']);
        $statement->bindValue(':step_accelerate_offset_sign', $step_data['StepAccelerateOffsetSign']);
        $statement->bindValue(':step_enable_torque_offset', $step_data['StepEnableTorqueOffset']);
        $statement->bindValue(':step_torque_offset', $step_data['StepTorqueOffset']);
        $statement->bindValue(':step_torque_offset_sign', $step_data['StepTorqueOffsetSign']);
        $statement->bindValue(':step_enable_down_shift', $step_data['StepEnableDownShift']);
        $statement->bindValue(':step_torque_down_shift', $step_data['StepTorqueDownShift']);
        $statement->bindValue(':step_rpm_down_shift', $step_data['StepRPMDownShift']);
        $statement->bindValue(':step_enable_threshold', $step_data['StepEnableThreshold']);
        $statement->bindValue(':step_torque_ts', $step_data['StepTorqueTS']);
        $statement->bindValue(':step_retry', $step_data['StepReTry']);
        $statement->bindValue(':step_unscrew', $step_data['StepUnScrew']);
        $statement->bindValue(':step_retry_torq', $step_data['StepReTryTorq']);
        $statement->bindValue(':step_retry_angl', $step_data['StepReTryAngl']);
        $statement->bindValue(':step_angle_record', $step_data['StepAngleRecord']);
        $statement->bindValue(':step_auto_detect_angle', $step_data['StepAutoDetectAngle']);
        $statement->bindValue(':interrupt_alarm', $step_data['InterruptAlarm']);
        $statement->bindValue(':over_angle_stop', $step_data['OverAngleStop']);
        $results = $statement->execute();


        return $results;


    }


    public function swapupdate($JOBID,$rowInfoArray){
        $temp = array();
        foreach ($rowInfoArray as $k_s => $v_s) {
            $sql = "SELECT StepSelect FROM STEP_lst WHERE JOBID = ? AND SEQID = ? ";
            $statement = $this->db_iDas->prepare($sql);
            $statement->execute([$JOBID, $v_s['SEQID']]);
            $result = $statement->fetch(PDO::FETCH_ASSOC);

            
            if ($result){
                
                $new_val = 'New_Value'.($k_s + 1);
                $update_sql = "UPDATE STEP_lst SET StepSelect  = ? WHERE JOBID = ? AND SEQID = ? AND StepSelect  = ?";
                $update_statement = $this->db_iDas->prepare($update_sql);
                $update_statement->execute([$new_val, $JOBID, $v_s['SEQID'], $v_s['StepSelect']]);
                $rows_count = $update_statement->rowCount();

                if ($rows_count  > 0){
                    $new_val = 'New_Value'.($k_s + 1);
                    $updated_step_id = preg_replace('/[^0-9]/', '', $new_val);
                    
                    $update_id_sql = "UPDATE STEP_lst SET StepSelect = ? WHERE JOBID = ? AND SEQID = ? ";
                    $update_id_statement = $this->db_iDas->prepare($update_id_sql);
                    $update_id_statement->execute([$updated_step_id, $jobid, $v_s['SEQID']]);
                }
                else{
             
                }
            }else{
                
            }

            //最終再次檢查 強制把 欄位step_id 不是數字的 通通移除
            $force_update_sql = "UPDATE STEP_lst SET StepSelect  = CAST(REPLACE(StepSelect, 'New_Value', '') AS UNSIGNED) WHERE JOBID =  ? ";
            $force_update_statement = $this->db_iDas->prepare($force_update_sql);
            $force_update_statement->execute([$JOBID]);


        }
        return true;
   
    }

    
}