<?php

class Steptcc{
    private $db_iDas;

    // 在建構子將 Database 物件實例化
    public function __construct(){

        $db_instance = new Database;
        $this->db_iDas = $db_instance->getDb_das();

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
        
        $sql = "INSERT INTO `STEP_lst` (JOBID, SEQID, StepSelect, STEPname, type, time, act, StepSwitch, StepRPM, StepOption, StepTime, StepAngle, StepTorque, StepDirection, StepDelay, StepMoniByWin, StepLimiHi, StepLimiLo, StepHiAngle, StepLoAngle, StepHiTorque, StepLoTorque, StepAccelerateOffset, StepAccelerateOffsetSign, StepEnableTorqueOffset, StepTorqueOffset, StepTorqueOffsetSign, StepEnableDownShift, StepTorqueDownShift, StepRPMDownShift, StepEnableThreshold, StepTorqueTS, StepReTry, StepUnScrew, StepReTryTorq, StepReTryAngl, StepAngleRecord, StepAutoDetectAngle, InterruptAlarm, OverAngleStop,KValue,step_unit ) ";
        $sql .= "VALUES (:jobid, :seqid, :step_select, :stepname, :type, :time, :act, :step_switch, :step_rpm, :step_option, :step_time, :step_angle, :step_torque, :step_direction, :step_delay, :step_moni_by_win, :step_limi_hi, :step_limi_lo, :step_hi_angle, :step_lo_angle, :step_hi_torque, :step_lo_torque, :step_accelerate_offset, :step_accelerate_offset_sign, :step_enable_torque_offset, :step_torque_offset, :step_torque_offset_sign, :step_enable_down_shift, :step_torque_down_shift, :step_rpm_down_shift, :step_enable_threshold, :step_torque_ts, :step_retry, :step_unscrew, :step_retry_torq, :step_retry_angl, :step_angle_record, :step_auto_detect_angle, :interrupt_alarm, :over_angle_stop,:KValue,:step_unit);";
    
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
        
        $step_data['StepAccelerateOffset'] =  0.2;
        //$step_data['InterruptAlarm']= 1;
        //$step_data['OverAngleStop']=1;


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
        $statement->bindValue(':KValue', $step_data['KValue']);
        $statement->bindValue(':step_unit', $step_data['step_unit']);
      
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
        $step_data['StepAccelerateOffset'] =  0.2;


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
                    OverAngleStop = :over_angle_stop,
                    KValue = :KValue,
                    step_unit = :step_unit
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
        $statement->bindValue(':KValue', $step_data['KValue']);
        $statement->bindValue(':step_unit', $step_data['step_unit']);


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

    public function check_step_is_last($jobid, $seqid, $stepid) {
        $sql = "SELECT StepSelect  FROM STEP_lst WHERE JOBID = ? AND SEQID = ? ORDER BY StepSelect ASC";
        $stmt = $this->db_iDas->prepare($sql);

        if (!$stmt) {
            // 顯示 SQL 語法錯誤資訊
            die("SQL prepare error: " . implode(", ", $this->db_iDas->errorInfo()));
        }

        $stmt->execute([$jobid, $seqid]);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $is_last = (!empty($rows) && end($rows) == $stepid) ? 'Y' : 'N';

        return [
            'count' => count($rows),
            'is_last' => $is_last
        ];
    }


    public function getStepsWithThresholds($jobid, $seqid) {

        $sql = "SELECT step_id, StepEnableThreshold FROM STEP_lst WHERE JOBID = ? AND SEQID = ?";
        $stmt = $this->db_iDas->prepare($sql);
        $stmt->execute([$jobid, $seqid]);
        return $stmt->fetchAll();
    }


    public function resetThresholdsByStepIds($jobid, $seqid, $stepIds) {
        if (empty($stepIds)) return;

        $placeholders = implode(',', array_fill(0, count($stepIds), '?'));
        $sql = "UPDATE STEP_lst SET StepEnableThreshold = 0, StepTorqueTS = 0
                WHERE JOBID = ? AND SEQID = ? AND step_id IN ($placeholders)";
        $params = array_merge([$jobid, $seqid], $stepIds);

        $stmt = $this->db_iDas->prepare($sql);
        $stmt->execute($params);
    }

    //清除 Threshold
    public function resetAllThresholds($jobid, $seqid) {
        $sql = "UPDATE STEP_lst SET StepEnableThreshold = 0, StepTorqueTS = 0 WHERE JOBID = ? AND SEQID = ?";
        $stmt = $this->db_iDas->prepare($sql);
        $stmt->execute([$jobid, $seqid]);
        return true;
    }


    //清除 DownShift
    public function resetAllDownShifts($jobid, $seqid) {
        $sql = "UPDATE STEP_lst SET StepEnableDownShift = 0, StepTorqueDownShift = 0, StepRPMDownShift = 0 WHERE JOBID = ? AND SEQID = ?";
        $stmt = $this->db_iDas->prepare($sql);
        $stmt->execute([$jobid, $seqid]);
        return true;
    }


    public function resetAllOtherThresholds($jobid, $seqid, $keep_step_select) {
        $sql = "UPDATE STEP_lst 
                SET StepEnableThreshold = 0, StepTorqueTS = 0
                WHERE JOBID = ? AND SEQID = ? AND StepSelect <> ?";
        $stmt = $this->db_iDas->prepare($sql);
        return $stmt->execute([$jobid, $seqid, $keep_step_select]);
    }


    public function getPreviousStepsWithThreshold($jobid, $seqid, $current_step_id) {
        $sql = "SELECT StepSelect FROM STEP_lst
                WHERE JOBID = ? AND SEQID = ? AND StepSelect <> ?
                AND StepEnableThreshold > 0 AND StepTorqueTS > 0";
        $stmt = $this->db_iDas->prepare($sql);
        $stmt->execute([$jobid, $seqid, $current_step_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function resetPreviousStepsThreshold($jobid, $seqid, $current_step_id) {
        $sql = "UPDATE STEP_lst 
                SET StepEnableThreshold = 0, StepTorqueTS = 0
                WHERE JOBID = ? AND SEQID = ? AND StepSelect =  ?
                AND StepEnableThreshold > 0";
        $stmt = $this->db_iDas->prepare($sql);
        return $stmt->execute([$jobid, $seqid, $current_step_id]);
    }


     /**
     * 查詢該 JOB + SEQ 下最大的 STEP ID
     */
    public function getMaxStepID($job_id, $seq_id){

        $sql = "SELECT MAX(	StepSelect) as max_step 
                FROM STEP_lst 
                WHERE JOBID = :jobid AND SEQID = :seqid";

        $stmt = $this->db_iDas->prepare($sql);
        $stmt->bindValue(':jobid', $job_id, PDO::PARAM_INT);
        $stmt->bindValue(':seqid', $seq_id, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return !empty($row['max_step']) ? intval($row['max_step']) : 0;
    }


    public function get_success_data_by_step() {

        $sql = "SELECT JOBID, SEQID, StepSelect, StepDelay 
                FROM STEP_lst 
                WHERE StepDelay > 0 AND JOBID NOT IN (0, 221)";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute();
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) return 0;

        $this->db_iDas->beginTransaction();

        $update_sql = "UPDATE STEP_lst 
                    SET StepDelay = ? 
                    WHERE JOBID = ? AND SEQID = ? AND StepSelect = ?";
        $update_stmt = $this->db_iDas->prepare($update_sql);

        foreach ($rows as $row) {
            $original = $row['StepDelay'];
            $floatVal = floatval($original);

            // 判斷是否為整數（ex: 3.0、5.0）且數值 < 10，視為「秒」
            if (fmod($floatVal, 1.0) === 0.0 && $floatVal < 10) {
                $newDelay = (int)($floatVal * 1000);  // 例如 3 → 3000
            } else {
                // 小數截斷至第 2 位（無條件捨去） → 毫秒
                $truncated = floor($floatVal * 100) / 100;
                $newDelay = (int)floor($truncated * 1000);
            }

            // Retry 機制（最多 3 次）
            for ($retry = 0; $retry < 3; $retry++) {
                try {
                    $update_stmt->execute([
                        $newDelay,
                        $row['JOBID'],
                        $row['SEQID'],
                        $row['StepSelect']
                    ]);
                    break;
                } catch (PDOException $e) {
                    if (stripos($e->getMessage(), 'locked') === false || $retry == 2) {
                        $this->db_iDas->rollBack();
                        throw $e;
                    }
                    usleep(100000); // wait 100ms
                }
            }
        }

        $this->db_iDas->commit();
        return count($rows);
    }



    /**
     * 建立預設 STEP，對應 JS 預設值
     */
    public function createDefaultStep( $job_id,$seq_id,$tool_min_torque,$tool_high_torque,$tool_low_torque,$torque,$device_torque_unit){


        $array = array(
            0 => 2, // KGF-m
            1 => 3, // N.m
            2 => 2, // KGF-cm
            3 => 4, // Lbf.in
            4 => 1  // cN.m
        );
        $precision = $decimals_arr[$device_torque_unit] ?? 3;

        $max_step_id = $this->getMaxStepID($job_id, $seq_id);

        $new_step_id = $max_step_id + 1;
        $step_data = [
            'JOBID' => $job_id,
            'SEQID' => $seq_id,
            'StepSelect' => 1,
            'STEPname' => 'STEP-' . $new_step_id,
            'type' => 0,
            'time' => date('Y-m-d H:i:s'),
            'act' => 0,
            'StepSwitch' => 1,
            'StepRPM' => 500,
            'StepOption' => 2,
            'StepTime' => 1000,
            'StepAngle' => 3000,
            'StepTorque' =>  number_format((float)$torque, $precision, '.', ''),
            'StepDirection' => 1,   // cw
            'StepDelay' => 0,
            'StepMoniByWin' => 0,
            'StepLimiHi' => 30,
            'StepLimiLo' => 30,
            'StepHiAngle' => 30600,
            'StepLoAngle' => 0,
            'StepHiTorque'   => number_format((float)$tool_high_torque, $precision, '.', ''),
            'StepLoTorque'   => number_format((float)$tool_low_torque, $precision, '.', ''),
            'StepAccelerateOffset' => 0.2,
            'StepAccelerateOffsetSign' => 43,
            'StepEnableTorqueOffset' => 0,
            'StepTorqueOffset' => 0,
            'StepTorqueOffsetSign' => 43,
            'StepEnableDownShift' => 0,
            'StepTorqueDownShift' => 0,
            'StepRPMDownShift' => 0,
            'StepEnableThreshold' => 0,
            'StepTorqueTS' => 0,
            'StepReTry' => 0,
            'StepUnScrew' => 1,
            'StepReTryTorq' => 0,
            'StepReTryAngl' => 0,
            'StepAngleRecord' => 0,
            'StepAutoDetectAngle' => 0,
            'InterruptAlarm' => 1,
            'OverAngleStop' => 1,
            'KValue' => 100,
            'step_unit' => $device_torque_unit,
        ];

        return $this->create_step($step_data);
    }

}