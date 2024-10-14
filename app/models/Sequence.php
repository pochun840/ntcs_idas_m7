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

        
        $sql = "INSERT INTO `SEQ_lst` (JOBID, SEQID, SEQname, type, time, act, skip, seq_repeat, timeout, ok_seq, ok_stop, countType, ok_screw, ng_stop, ng_unscrew, interrupt_alarm, accu_angle, Thread_Calcu, unscrew_mode, unscrew_force, unscrew_rpm, unscrew_dir, image, message, delay, input, input_signal, output, output_signal, output_durat, addtion, unscrew_count_switch, unscrew_torque_threshold)"; 
        $sql.= "VALUES (:JOBID, :SEQID, :SEQname, :type, :time, :act, :skip, :seq_repeat, :timeout, :ok_seq, :ok_stop, :countType, :ok_screw, :ng_stop, :ng_unscrew, :interrupt_alarm, :accu_angle, :Thread_Calcu, :unscrew_mode, :unscrew_force, :unscrew_rpm, :unscrew_dir, :image, :message, :delay, :input, :input_signal, :output, :output_signal, :output_durat, :addtion, :unscrew_count_switch, :unscrew_torque_threshold);";
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
    
        $results = $statement->execute();

        return $results;

    }

    public function copy_seq_by_seq_id($new_temp_seq) {
        // SQL 查询语句，使用命名占位符
        $sql = "INSERT INTO `SEQ_lst` 
                (JOBID, SEQID, SEQname, type, time, act, skip, seq_repeat, timeout, 
                ok_seq, ok_stop, countType, ok_screw, ng_stop, ng_unscrew, interrupt_alarm, 
                accu_angle, Thread_Calcu, unscrew_mode, unscrew_force, unscrew_rpm, unscrew_dir, 
                image, message, delay, input, input_signal, output, output_signal, output_durat, 
                addtion, unscrew_count_switch, unscrew_torque_threshold) 
                VALUES 
                (:JOBID, :SEQID, :SEQname, :type, :time, :act, :skip, :seq_repeat, :timeout, 
                :ok_seq, :ok_stop, :countType, :ok_screw, :ng_stop, :ng_unscrew, :interrupt_alarm, 
                :accu_angle, :Thread_Calcu, :unscrew_mode, :unscrew_force, :unscrew_rpm, :unscrew_dir, 
                :image, :message, :delay, :input, :input_signal, :output, :output_signal, :output_durat, 
                :addtion, :unscrew_count_switch, :unscrew_torque_threshold);";
        
        // 准备 SQL 语句
        $statement = $this->db_iDas->prepare($sql);
        $insertedrecords = 0;
    
        // 遍历每一个新的 sequence 数据
        foreach ($new_temp_seq as $seq) {
            try {
                // 确保 $seq 是一个包含正确键名的关联数组
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
                    // 执行 SQL 语句，传入关联数组 $seq 作为参数
                    if ($statement->execute($seq)) {
                        $insertedrecords++;
                    } else {
                        // 如果执行失败，记录错误信息并输出可执行的 SQL 语句
                        $errorInfo = $statement->errorInfo();
                        echo "SQL Error: " . $errorInfo[2] . "\n";
    
                        // 生成并输出 SQL 语句，方便在 SQLite 中执行
                        $binded_sql = $this->generate_sql_with_values($sql, $seq);
                        echo "可执行的 SQL 语句：\n" . $binded_sql . "\n";
                    }
                } else {
                    // 如果数据缺失必要字段，记录错误
                    echo "缺少必要字段: " . print_r($seq, true);
                }
            } catch (PDOException $e) {
                // 捕获并处理异常
                echo "PDOException: " . $e->getMessage();
            }
        }
    
        // 返回成功插入的记录数
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
    public function update_seq_by_id($jobdata){


        if(intval($jobdata['job_id']) > 100 || intval($jobdata['sequence_id']) > 100) {   
            return false; 
        }

        $sql = "UPDATE `sequence` SET  sequence_name = :sequence_name,
                                  tightening_repeat = :tightening_repeat, 
                                  ng_stop = :ng_stop, 
                                  seq_ok  =:seq_ok,
                                  stop_seq_ok =:stop_seq_ok,
                                  opt = :opt,
                                  k_value = :k_value,
                                  offset = :offset
        WHERE job_id = :job_id  AND   sequence_id = :sequence_id ";


        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':sequence_name', $jobdata['sequence_name']);
        $statement->bindValue(':tightening_repeat', $jobdata['tightening_repeat']);
        $statement->bindValue(':seq_ok', $jobdata['seq_ok']);
        $statement->bindValue(':stop_seq_ok', $jobdata['stop_seq_ok']);
        $statement->bindValue(':ng_stop', $jobdata['ng_stop']);
        $statement->bindValue(':opt', $jobdata['opt']);
        $statement->bindValue(':k_value', $jobdata['k_value']);
        $statement->bindValue(':offset', $jobdata['offset']);
        $statement->bindValue(':job_id', $jobdata['job_id']);
        $statement->bindValue(':sequence_id', $jobdata['sequence_id']);
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
            $sql = "SELECT sequence_id FROM sequence WHERE job_id = ? AND sequence_name = ? ";
            $statement = $this->db_iDas->prepare($sql);
            $statement->execute([$jobid, $v_s['sequence_name']]);
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $new_val = 'New_Value'.($k_s + 1);
                $update_sql = "UPDATE sequence SET sequence_id = ? WHERE job_id = ? AND sequence_name = ? ";

                $update_statement = $this->db_iDas->prepare($update_sql);
                $update_statement->execute([$new_val, $jobid, $v_s['sequence_name']]);


                $rows_count = $update_statement->rowCount();
                if ($rows_count  > 0){
                    $new_val = 'New_Value'.($k_s + 1);
                    $updated_sequence_id = preg_replace('/[^0-9]/', '', $new_val);
                    
                    $update_id_sql = "UPDATE sequence SET sequence_id = ? WHERE job_id = ? AND sequence_name = ? ";
                    $update_id_statement = $this->db_iDas->prepare($update_id_sql);
                    $update_id_statement->execute([$updated_sequence_id, $jobid, $v_s['sequence_name']]);                  
                }

            }

            //最終再次檢查 強制把 欄位sequence_id 不是數字的 通通移除
            $force_update_sql = "UPDATE sequence SET sequence_id = CAST(REPLACE(sequence_id, 'New_Value', '') AS UNSIGNED) WHERE job_id =  ? ";
            $force_update_statement = $this->db_iDas->prepare($force_update_sql);
            $force_update_statement->execute([$jobid]);
            


        }

        if(!empty($new_info)){

            //var_dump($new_info);die();
            foreach($new_info as $key =>$val){
                $new_val = $key; // 使用陣列的鍵作為 new_val
                $sequence_id = $val['sequence_id'];

                $sql_select = "SELECT count(*) FROM step WHERE job_id = :jobid AND sequence_id = :sequence_id";
                $select_statement = $this->db_iDas->prepare($sql_select);

                $select_statement->bindValue(':jobid', $jobid);
                $select_statement->bindValue(':sequence_id', $sequence_id);
    
                // 執行查詢
                $select_statement->execute();
                $count = $select_statement->fetchColumn();
                if ($count > 0) {

                    $sql_step = "UPDATE step SET sequence_id = '".$key."' WHERE job_id = '".$jobid."' AND sequence_id = '". $val['sequence_id']."' ";
                    $update_statement = $this->db_iDas->prepare($sql_step);
    
                    $update_statement->execute();


                    $sql_step = "UPDATE step SET sequence_id = :new_val WHERE job_id = :jobid AND sequence_id = :sequence_id";
                    $update_statement = $pdo->prepare($sql_step);
                    
                    $update_statement->bindValue(':new_val', $key, PDO::PARAM_INT);
                    $update_statement->bindValue(':jobid', $jobid, PDO::PARAM_INT);
                    $update_statement->bindValue(':sequence_id', $sequence_id, PDO::PARAM_INT);
        
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

          //die();
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
