<?php

class Sequence{
    private $db;//condb control box
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

    #取得所有sequences
    public function getSequences_by_job_id($job_id){

        $sql ="SELECT seq.*,count(ns.SEQID) as total_step FROM SEQ_lst as seq LEFT JOIN STEP_lst as ns ON seq.SEQID = ns.SEQID AND seq.JOBID = ns.JOBID WHERE seq.JOBID = '".$job_id."' group by seq.JOBID,seq.SEQID ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute();
        return $statement->fetchall();

    }

    #透過 job_id  取得當前有幾個seq
    public function countseq($jobid ){
        $sql = "SELECT COUNT(*) as count FROM SEQ_lst WHERE JOBID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid]);
        $result = $statement->fetch();
        return $result['count'];
    }

    #新增sequence
    public function create_seq($mode, $seq_data) {


        if(intval($seq_data['job_id']) > 100 || intval($seq_data['SEQID']) > 100) {
           
            return false; 
        }

        
        $sql = "INSERT INTO `SEQ_lst` (JOBID, SEQID, SEQname, type, time, act, skip, seq_repeat, timeout, ok_seq, ok_stop, countType, ok_screw, ng_stop, ng_unscrew, interrupt_alarm, accu_angle, Thread_Calcu, unscrew_mode, unscrew_force, unscrew_rpm, unscrew_dir, image, message, delay, input, input_signal, output, output_signal, output_durat, addtion, unscrew_count_switch, unscrew_torque_threshold,seq_unit,unscrew_angle_threshold,dt_time,tt_time)"; 
        $sql.= "VALUES (:JOBID, :SEQID, :SEQname, :type, :time, :act, :skip, :seq_repeat, :timeout, :ok_seq, :ok_stop, :countType, :ok_screw, :ng_stop, :ng_unscrew, :interrupt_alarm, :accu_angle, :Thread_Calcu, :unscrew_mode, :unscrew_force, :unscrew_rpm, :unscrew_dir, :image, :message, :delay, :input, :input_signal, :output, :output_signal, :output_durat, :addtion, :unscrew_count_switch, :unscrew_torque_threshold,:seq_unit,:unscrew_angle_threshold,:dt_time,:tt_time);";
        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':JOBID', $seq_data['job_id']);
        $statement->bindValue(':SEQID', $seq_data['SEQID']);
        $statement->bindValue(':SEQname', $seq_data['SEQname']);
        $statement->bindValue(':type', $seq_data['type']);
        $statement->bindValue(':time', $seq_data['time']);
        $statement->bindValue(':act', $seq_data['act']);
        $statement->bindValue(':skip', $seq_data['skip']);
        $statement->bindValue(':seq_repeat', $seq_data['seq_repeat']);
        $statement->bindValue(':timeout', $seq_data['timeout']);
        $statement->bindValue(':ok_seq', $seq_data['ok_seq']);
        $statement->bindValue(':ok_stop', $seq_data['ok_stop']);
        $statement->bindValue(':countType', $seq_data['countType']);
        $statement->bindValue(':ok_screw', $seq_data['ok_screw']);
        $statement->bindValue(':ng_stop', $seq_data['ng_stop']);
        $statement->bindValue(':ng_unscrew', $seq_data['ng_unscrew']);
        $statement->bindValue(':interrupt_alarm', $seq_data['interrupt_alarm']);
        $statement->bindValue(':accu_angle', $seq_data['accu_angle']);
        $statement->bindValue(':Thread_Calcu', $seq_data['Thread_Calcu']);
        $statement->bindValue(':unscrew_mode', $seq_data['unscrew_mode']);
        $statement->bindValue(':unscrew_force', $seq_data['unscrew_force']);
        $statement->bindValue(':unscrew_rpm', $seq_data['unscrew_rpm']);
        $statement->bindValue(':unscrew_dir', $seq_data['unscrew_dir']);
        $statement->bindValue(':image', $seq_data['image']);
        $statement->bindValue(':message', $seq_data['message']);
        $statement->bindValue(':delay', $seq_data['delay']);
        $statement->bindValue(':input', $seq_data['input']);
        $statement->bindValue(':input_signal', $seq_data['input_signal']);
        $statement->bindValue(':output', $seq_data['output']);
        $statement->bindValue(':output_signal', $seq_data['output_signal']);
        $statement->bindValue(':output_durat', $seq_data['output_durat']);
        $statement->bindValue(':addtion', $seq_data['addtion']);
        $statement->bindValue(':unscrew_count_switch', $seq_data['unscrew_count_switch']);
        $statement->bindValue(':unscrew_torque_threshold', $seq_data['unscrew_torque_threshold']);
        $statement->bindValue(':seq_unit', $seq_data['seq_unit']);
        $statement->bindValue(':unscrew_angle_threshold', $seq_data['unscrew_angle_threshold']);
        $statement->bindValue(':dt_time', $seq_data['dt_time']);
        $statement->bindValue(':tt_time', $seq_data['tt_time']);
        $results = $statement->execute();

        return $results;

    }

    public function copy_seq_by_seq_id($new_temp_seq) {
        
        $sql = "INSERT INTO `SEQ_lst` 
                (JOBID, SEQID, SEQname, type, time, act, skip, seq_repeat, timeout, 
                ok_seq, ok_stop, countType, ok_screw, ng_stop, ng_unscrew, interrupt_alarm, 
                accu_angle, Thread_Calcu, unscrew_mode, unscrew_force, unscrew_rpm, unscrew_dir, 
                image, message, delay, input, input_signal, output, output_signal, output_durat, 
                addtion, unscrew_count_switch, unscrew_torque_threshold,seq_unit) 
                VALUES 
                (:JOBID, :SEQID, :SEQname, :type, :time, :act, :skip, :seq_repeat, :timeout, 
                :ok_seq, :ok_stop, :countType, :ok_screw, :ng_stop, :ng_unscrew, :interrupt_alarm, 
                :accu_angle, :Thread_Calcu, :unscrew_mode, :unscrew_force, :unscrew_rpm, :unscrew_dir, 
                :image, :message, :delay, :input, :input_signal, :output, :output_signal, :output_durat, 
                :addtion, :unscrew_count_switch, :unscrew_torque_threshold,:seq_unit);";
        
       
        $statement = $this->db_iDas->prepare($sql);
        $insertedrecords = 0;
    
        foreach ($new_temp_seq as $seq) {
            try {
               
                if (is_array($seq) && isset(
                    $seq['JOBID'], $seq['SEQID'], $seq['SEQname'], $seq['type'], $seq['time'],
                    $seq['act'], $seq['skip'], $seq['seq_repeat'], $seq['timeout'], $seq['ok_seq'], 
                    $seq['ok_stop'], $seq['countType'], $seq['ok_screw'], $seq['ng_stop'], 
                    $seq['ng_unscrew'], $seq['interrupt_alarm'], $seq['accu_angle'], $seq['Thread_Calcu'], 
                    $seq['unscrew_mode'], $seq['unscrew_force'], $seq['unscrew_rpm'], $seq['unscrew_dir'], 
                    $seq['image'], $seq['message'], $seq['delay'], $seq['input'], $seq['input_signal'], 
                    $seq['output'], $seq['output_signal'], $seq['output_durat'], $seq['addtion'], 
                    $seq['unscrew_count_switch'], $seq['unscrew_torque_threshold']
                )) {
                   
                    if ($statement->execute($seq)) {
                        $insertedrecords++;
                    } else {
                      
                        $errorInfo = $statement->errorInfo();
                        echo "SQL Error: " . $errorInfo[2] . "\n";
                        $binded_sql = $this->generate_sql_with_values($sql, $seq);
                        echo "可执行的 SQL 语句：\n" . $binded_sql . "\n";
                    }
                } else {
                    echo "缺少必要字段: " . print_r($seq, true);
                }
            } catch (PDOException $e) {
                echo "PDOException: " . $e->getMessage();
            }
        }
        return $insertedrecords;
    }
    
    /**
     * 生成带有绑定值的 SQL 语句，方便调试
     *
     * @param string $sql 原始 SQL 查询
     * @param array $seq 参数数组
     * @return string 带有绑定值的 SQL 查询
     */
    private function generate_sql_with_values($sql, $seq) {
        // 替换 SQL 中的占位符为实际的值
        foreach ($seq as $key => $value) {
            // 使用 PDO 占位符（如 :JOBID）替换为实际的值
            $sql = str_replace(':' . $key, $this->quote_value($value), $sql);
        }
        return $sql;
    }
    
    /**
     * 安全地将值转换为 SQL 可以识别的格式
     *
     * @param mixed $value 值
     * @return string 转换后的值
     */
    private function quote_value($value) {
        // 如果是字符串，添加引号
        if (is_string($value)) {
            return "'" . addslashes($value) . "'";
        }
        // 对于整数或布尔值，直接返回
        return $value;
    }

    
    
    

    public function copy_step_by_seq_id($new_temp_step){

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

    #刪除sequences
    public function delete_seq_by_id($jobid,$seqid){

        
        $sql= " DELETE FROM SEQ_lst WHERE JOBID = ? AND SEQID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$jobid, $seqid]);

        if ($seqid != 100 ) {
            $sql_update = "UPDATE SEQ_lst  SET SEQID = SEQID - 1 WHERE JOBID = ? AND SEQID > ?";
            $statement_update = $this->db_iDas->prepare($sql_update);
            $statement_update->execute([$jobid, $seqid]);
        }   
        return $results;

    }

    public function delete_step_by_job_id($jobid,$seqid){

        $sql= "DELETE FROM STEP_lst WHERE  JOBID = ? AND SEQID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$jobid, $seqid]);

        return $results;
    }


    #查詢 單筆的sequences
    public function search_seqinfo($jobid,$seqid){

        $sql= " SELECT *  FROM SEQ_lst WHERE JOBID = ? AND SEQID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid, $seqid]);
        
        return $statement->fetchall();

    }

    #修改 sequences
    public function update_seq_by_id($seq_data){


        if(intval($seq_data['JOBID']) > 100 || intval($seq_data['SEQID']) > 100) {   
            return false; 
        }

        $sql = "UPDATE `SEQ_lst`  SET 
                    SEQname = :SEQname, 
                    type = :type, 
                    time = :time, 
                    act = :act, 
                    skip = :skip, 
                    seq_repeat = :seq_repeat, 
                    timeout = :timeout, 
                    ok_seq = :ok_seq, 
                    ok_stop = :ok_stop, 
                    countType = :countType, 
                    ok_screw = :ok_screw, 
                    ng_stop = :ng_stop, 
                    ng_unscrew = :ng_unscrew, 
                    interrupt_alarm = :interrupt_alarm, 
                    accu_angle = :accu_angle, 
                    Thread_Calcu = :Thread_Calcu, 
                    unscrew_mode = :unscrew_mode, 
                    unscrew_force = :unscrew_force, 
                    unscrew_rpm = :unscrew_rpm, 
                    unscrew_dir = :unscrew_dir, 
                    image = :image, 
                    message = :message, 
                    delay = :delay, 
                    input = :input, 
                    input_signal = :input_signal, 
                    output = :output, 
                    output_signal = :output_signal, 
                    output_durat = :output_durat, 
                    addtion = :addtion, 
                    unscrew_count_switch = :unscrew_count_switch, 
                    unscrew_torque_threshold = :unscrew_torque_threshold,
                    seq_unit =:seq_unit
                WHERE  JOBID = :JOBID  AND SEQID = :SEQID";

        $statement = $this->db_iDas->prepare($sql);

        $statement->bindValue(':JOBID', $seq_data['JOBID']);
        $statement->bindValue(':SEQID', $seq_data['SEQID']);
        $statement->bindValue(':SEQname', $seq_data['SEQname']);
        $statement->bindValue(':type', $seq_data['type']);
        $statement->bindValue(':time', $seq_data['time']);
        $statement->bindValue(':act', $seq_data['act']);
        $statement->bindValue(':skip', $seq_data['skip']);
        $statement->bindValue(':seq_repeat', $seq_data['seq_repeat']);
        $statement->bindValue(':timeout', $seq_data['timeout']);
        $statement->bindValue(':ok_seq', $seq_data['ok_seq']);
        $statement->bindValue(':ok_stop', $seq_data['ok_stop']);
        $statement->bindValue(':countType', $seq_data['countType']);
        $statement->bindValue(':ok_screw', $seq_data['ok_screw']);
        $statement->bindValue(':ng_stop', $seq_data['ng_stop']);
        $statement->bindValue(':ng_unscrew', $seq_data['ng_unscrew']);
        $statement->bindValue(':interrupt_alarm', $seq_data['interrupt_alarm']);
        $statement->bindValue(':accu_angle', $seq_data['accu_angle']);
        $statement->bindValue(':Thread_Calcu', $seq_data['Thread_Calcu']);
        $statement->bindValue(':unscrew_mode', $seq_data['unscrew_mode']);
        $statement->bindValue(':unscrew_force', $seq_data['unscrew_force']);
        $statement->bindValue(':unscrew_rpm', $seq_data['unscrew_rpm']);
        $statement->bindValue(':unscrew_dir', $seq_data['unscrew_dir']);
        $statement->bindValue(':image', $seq_data['image']);
        $statement->bindValue(':message', $seq_data['message']);
        $statement->bindValue(':delay', $seq_data['delay']);
        $statement->bindValue(':input', $seq_data['input']);
        $statement->bindValue(':input_signal', $seq_data['input_signal']);
        $statement->bindValue(':output', $seq_data['output']);
        $statement->bindValue(':output_signal', $seq_data['output_signal']);
        $statement->bindValue(':output_durat', $seq_data['output_durat']);
        $statement->bindValue(':addtion', $seq_data['addtion']);
        $statement->bindValue(':unscrew_count_switch', $seq_data['unscrew_count_switch']);
        $statement->bindValue(':unscrew_torque_threshold', $seq_data['unscrew_torque_threshold']);
        $statement->bindValue(':seq_unit', $seq_data['seq_unit']);
    
        $results = $statement->execute();

        return $results;


    }

    #修改單筆的sequence的狀態
    public function check_seq_type($jobid, $seqid, $type_value) {
        $sql = "UPDATE `SEQ_lst` SET act = :act WHERE JOBID = :JOBID AND SEQID = :SEQID ";
        $statement = $this->db_iDas->prepare($sql);
    
        $statement->bindValue(':act', $type_value);
        $statement->bindValue(':JOBID', $jobid);
        $statement->bindValue(':SEQID', $seqid);
        
        $success = $statement->execute();    
        return $success;
    }

    public function update_seq_type($seq_data) {
        $sql = "UPDATE `SEQ_lst` SET act = :act WHERE JOBID = :JOBID AND SEQID = :SEQID ";
        $statement = $this->db_iDas->prepare($sql);
    
        $statement->bindValue(':act', $seq_data['type_value']);
        $statement->bindValue(':JOBID', $seq_data['jobid']);
        $statement->bindValue(':SEQID', $seq_data['seqid']);
        
        $success = $statement->execute();     
        return $success;
    }


    #用jobid seqid oldseqname 查詢該筆的所有資料
    public function search_old_data($jobid,$seqid,$oldseqname){

        $sql= " SELECT * FROM SEQ_lst WHERE JOBID = ? AND SEQID = ? AND SEQname = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid,$seqid,$oldseqname]);
        $rows = $statement->fetch();

        return $rows;
    }


    public function swapupdate($jobid, $rowInfoArray,$new_info) {
        $temp = array();
        foreach ($rowInfoArray as $k_s => $v_s) {
            $sql = "SELECT SEQID FROM SEQ_lst WHERE JOBID = ? AND SEQname = ? ";
            $statement = $this->db_iDas->prepare($sql);
            $statement->execute([$jobid, $v_s['SEQname']]);
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $new_val = 'New_Value'.($k_s + 1);
                $update_sql = "UPDATE SEQ_lst SET SEQID = ? WHERE JOBID = ? AND SEQname = ? ";

                $update_statement = $this->db_iDas->prepare($update_sql);
                $update_statement->execute([$new_val, $jobid, $v_s['SEQname']]);


                $rows_count = $update_statement->rowCount();
                if ($rows_count  > 0){
                    $new_val = 'New_Value'.($k_s + 1);
                    $updated_sequence_id = preg_replace('/[^0-9]/', '', $new_val);
                    
                    $update_id_sql = "UPDATE SEQ_lst SET SEQID = ? WHERE JOBID = ? AND SEQname = ? ";
                    $update_id_statement = $this->db_iDas->prepare($update_id_sql);
                    $update_id_statement->execute([$updated_sequence_id, $jobid, $v_s['SEQname']]);                  
                }

            }

            //最終再次檢查 強制把 欄位sequence_id 不是數字的 通通移除
            $force_update_sql = "UPDATE SEQ_lst SET SEQID = CAST(REPLACE(SEQID, 'New_Value', '') AS UNSIGNED) WHERE JOBID =  ? ";
            $force_update_statement = $this->db_iDas->prepare($force_update_sql);
            $force_update_statement->execute([$jobid]);
            


        }

        if(!empty($new_info)){

            foreach($new_info as $key =>$val){
                $new_val = $key; 
                $sequence_id = $val['SEQID'];

                $sql_select = "SELECT count(*) FROM STEP_lst WHERE job_id = :jobid AND SEQID = :SEQID";
                $select_statement = $this->db_iDas->prepare($sql_select);

                $select_statement->bindValue(':jobid', $jobid);
                $select_statement->bindValue(':SEQID', $sequence_id);
    
                // 執行查詢
                $select_statement->execute();
                $count = $select_statement->fetchColumn();
                if ($count > 0) {

                    $sql_step = "UPDATE STEP_lst SET SEQID = '".$key."' WHERE JOBID = '".$jobid."' AND SEQID = '". $val['SEQID']."' ";
                    $update_statement = $this->db_iDas->prepare($sql_step);
    
                    $update_statement->execute();


                    $sql_step = "UPDATE STEP_lst SET SEQID  = :new_val WHERE JOBID = :JOBID AND SEQID = :SEQID";
                    $update_statement = $pdo->prepare($sql_step);
                    
                    $update_statement->bindValue(':new_val', $key, PDO::PARAM_INT);
                    $update_statement->bindValue(':JOBID', $jobid, PDO::PARAM_INT);
                    $update_statement->bindValue(':SEQID', $sequence_id, PDO::PARAM_INT);
        
                    $update_statement->execute();

                    
                }else{
                  
                }

            }

        }
        return true;
   
    }
    
    


    #驗證seq id是否重複
    public function sequence_id_repeat($jobid,$seqid)
    {
        $sql = "SELECT count(*) as count FROM SEQ_lst WHERE JOBID AND SEQID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$jobid,$seqid]);
        $rows = $statement->fetch();

        if ($rows['count'] > 0) {

            //如果有的話
            $sql_d = "DELETE FROM step WHERE  JOBID = ? AND SEQID = ? ";
            $statement = $this->db_iDas->prepare($sql_d);
            $results_d = $statement->execute([$jobid, $seqid]);

            return "True"; // sequence_id已存在
        }else{
            return "False"; // sequence_id不存在
        }


    }


    public function search_stepinfo($jobid,$seqid){

        $sql= " SELECT *  FROM STEP_lst WHERE JOBID = ? AND SEQID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid,$seqid]);
        
        return $statement->fetchall();

    }


    #用 $jobid,$newseqid 尋找有沒有對應的資料
    #有的話就刪除唷
    public function del_seq_type($jobid, $newseqid) {
        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM SEQ_lst WHERE JOBID = ? AND SEQID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid, $newseqid]);
        $count = $statement->fetchColumn();
        $count = intval($count);
       
        //var_dump($count);
        //die();
        if ($count > 0) {
            #如果資料存在，則刪除
            $deleteSql = "DELETE FROM SEQ_lst  WHERE JOBID = ? AND SEQID = ?";
            $deleteStatement = $this->db_iDas->prepare($deleteSql);
            $deleteStatement->execute([$jobid, $newseqid]);
    
            return true;
        } else {
            return false;
        }
    }

    public function del_step_type($jobid, $newseqid){

        #查詢資料是否存在
        $sql = "SELECT COUNT(*) FROM STEP_lst WHERE JOBID = ? AND SEQID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid, $newseqid]);
        $count = $statement->fetchColumn();
        $count = intval($count);

        if ($count > 0) {
            #如果資料存在，則刪除
            $delete_step_sql = "DELETE FROM STEP_lst  WHERE JOBID = ? AND SEQID = ?";
            $deleteStatement = $this->db_iDas->prepare($delete_step_sql);
            $deleteStatement->execute([$jobid, $newseqid]);
    
            return true;
        } else {
            return false;
        }
    }


    
}
