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

        $sql = "SELECT COUNT(*) as count FROM step WHERE job_id = ? AND sequence_id = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$jobid, $seqid]);
        $result = $statement->fetch();
        return $result['count'];
    }

    #透過job_id 及 seq_id 取得對應的step
    public function getStep($job_id, $seq_id) {

        $sql = "SELECT * FROM step WHERE job_id = ? AND sequence_id = ? ORDER BY step_id ASC ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$job_id, $seq_id]);
        return $statement->fetchAll();
    }

    #透過job_id 及 seq_id 及 step_id取得對應的資料
    public function getStepNo($jobid,$seqid,$stepid){

        $sql = "SELECT * FROM step WHERE job_id = ? AND sequence_id = ? AND step_id = ?";
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
    public function delete_step_id($jobid,$seqid,$stepid){

        $sql= " DELETE FROM step WHERE job_id = ? AND sequence_id = ?  AND step_id = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$jobid, $seqid,$stepid]);


        if ($stepid != 4) {
            $sql_update = "UPDATE step SET step_id = step_id - 1 WHERE job_id = ? AND sequence_id = ? AND step_id > ?";
            $statement_update = $this->db_iDas->prepare($sql_update);
            $statement_update->execute([$jobid, $seqid, $stepid]);
        }
            
        return $results;


    }


    public function create_step($mode, $jobdata) {
        echo "<pre>";
        print_r($jobdata);
        echo "</pre>";
    
        if (empty($jobdata['job_id'])) {
            return false; 
        }
        
        $sql = "INSERT INTO `step` (job_id, sequence_id, step_id, target_option, target_torque, target_angle, target_delaytime, hi_torque, lo_torque, hi_angle, lo_angle, rpm, direction, downshift, threshold_torque, downshift_torque, downshift_speed) ";
        $sql .= "VALUES (:job_id, :sequence_id, :step_id, :target_option, :target_torque, :target_angle, :target_delaytime, :hi_torque, :lo_torque, :hi_angle, :lo_angle, :rpm, :direction, :downshift, :threshold_torque, :downshift_torque, :downshift_speed);";
    
        // 检查数据库连接
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
    
        
        // 绑定参数
        $statement->bindValue(':job_id', $jobdata['job_id']);
        $statement->bindValue(':sequence_id', $jobdata['sequence_id']);
        $statement->bindValue(':step_id', $jobdata['step_id']);
        $statement->bindValue(':target_option', $jobdata['target_option']);
        $statement->bindValue(':target_torque', $jobdata['target_torque']);
        $statement->bindValue(':target_angle', $jobdata['target_angle']);
        $statement->bindValue(':target_delaytime', $jobdata['target_delaytime']);
        $statement->bindValue(':hi_torque', $jobdata['hi_torque']);
        $statement->bindValue(':lo_torque', $jobdata['lo_torque']);
        $statement->bindValue(':hi_angle', $jobdata['hi_angle']);
        $statement->bindValue(':lo_angle', $jobdata['lo_angle']);
        $statement->bindValue(':rpm', $jobdata['rpm']);
        $statement->bindValue(':direction', $jobdata['direction']);
        $statement->bindValue(':downshift', $jobdata['downshift']);
        $statement->bindValue(':threshold_torque', $jobdata['threshold_torque']);
        $statement->bindValue(':downshift_torque', $jobdata['downshift_torque']);
        $statement->bindValue(':downshift_speed', $jobdata['downshift_speed']);
    
        // 执行查询并检查结果
        $results = $statement->execute();
        if (!$results) {
            echo "执行错误: " . implode(", ", $statement->errorInfo());
        }
    
        return $results;
    }
    


    public function update_step_by_id($jobdata){


        $sql = "UPDATE `step` SET 
                    target_option = :target_option,
                    target_torque = :target_torque, 
                    target_angle = :target_angle, 
                    target_delaytime = :target_delaytime, 
                    hi_torque = :hi_torque,
                    lo_torque = :lo_torque,
                    hi_angle = :hi_angle,
                    lo_angle = :lo_angle,
                    direction = :direction,
                    downshift = :downshift,
                    threshold_torque = :threshold_torque,
                    downshift_torque = :downshift_torque,
                    downshift_speed = :downshift_speed
        WHERE job_id = :job_id  AND   sequence_id = :sequence_id  AND step_id = :step_id ";
        $statement = $this->db_iDas->prepare($sql);

        $statement->bindValue(':job_id', $jobdata['job_id']);
        $statement->bindValue(':sequence_id', $jobdata['sequence_id']);
        $statement->bindValue(':step_id', $jobdata['step_id']);
        $statement->bindValue(':target_option', $jobdata['target_option']);
        $statement->bindValue(':target_torque', $jobdata['target_torque']);
        $statement->bindValue(':target_angle', $jobdata['target_angle']);
        $statement->bindValue(':target_delaytime', $jobdata['target_delaytime']);
        $statement->bindValue(':hi_torque', $jobdata['hi_torque']);
        $statement->bindValue(':lo_torque', $jobdata['lo_torque']);
        $statement->bindValue(':hi_angle', $jobdata['hi_angle']);
        $statement->bindValue(':lo_angle', $jobdata['lo_angle']);
        $statement->bindValue(':direction', $jobdata['direction']);
        $statement->bindValue(':downshift', $jobdata['downshift']);
        $statement->bindValue(':threshold_torque', $jobdata['threshold_torque']);
        $statement->bindValue(':downshift_torque', $jobdata['downshift_torque']);
        $statement->bindValue(':downshift_speed', $jobdata['downshift_speed']);
        $results = $statement->execute();


        return $results;


    }


    public function swapupdate($jobid,$rowInfoArray){
        $temp = array();
        foreach ($rowInfoArray as $k_s => $v_s) {
            $sql = "SELECT step_id FROM step WHERE job_id = ? AND sequence_id = ? ";
            $statement = $this->db_iDas->prepare($sql);
            $statement->execute([$jobid, $v_s['sequence_id']]);
            $result = $statement->fetch(PDO::FETCH_ASSOC);

            
            if ($result){
                
                $new_val = 'New_Value'.($k_s + 1);
                $update_sql = "UPDATE step SET step_id = ? WHERE job_id = ? AND sequence_id = ? AND step_id = ?";
                $update_statement = $this->db_iDas->prepare($update_sql);
                $update_statement->execute([$new_val, $jobid, $v_s['sequence_id'], $v_s['step_id']]);
                $rows_count = $update_statement->rowCount();

                if ($rows_count  > 0){
                    $new_val = 'New_Value'.($k_s + 1);
                    $updated_step_id = preg_replace('/[^0-9]/', '', $new_val);
                    
                    $update_id_sql = "UPDATE step SET step_id = ? WHERE job_id = ? AND sequence_id = ? ";
                    $update_id_statement = $this->db_iDas->prepare($update_id_sql);
                    $update_id_statement->execute([$updated_step_id, $jobid, $v_s['sequence_id']]);
                }
                else{
                    //echo "ewq";die();
                }
            }else{
                //echo "eew";die();
            }

            //最終再次檢查 強制把 欄位step_id 不是數字的 通通移除
            $force_update_sql = "UPDATE step SET step_id = CAST(REPLACE(step_id, 'New_Value', '') AS UNSIGNED) WHERE job_id =  ? ";
            $force_update_statement = $this->db_iDas->prepare($force_update_sql);
            $force_update_statement->execute([$jobid]);


        }
        return true;
   
    }

    
}