<?php

class Output{

    private $db_iDas;
    private $db_iDas_device;
    // 在建構子將 Database 物件實例化
    public function __construct(){
        
        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();

        $this->db_iDas_device = new Database;
        $this->db_iDas_device = $this->db_iDas_device->getDb_das_device();


    }

    //get_input_by_job_id
    public function get_output_by_job_id($output_job_id)
    {   
        $sql = "SELECT * FROM JOBOutput_lst  WHERE JOBID = ?  ";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$output_job_id]);
        $row = $statement->fetchall(PDO::FETCH_ASSOC);

        return $row;
    }

    public function check_job_output_conflict($job_id,$event_id)
    {
        $sql = "SELECT count(*) as count FROM JOBOutput_lst  WHERE JOBID = ? AND EvenID = ?";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute([$job_id,$event_id]);
        $rows = $statement->fetch();

        if ($rows['count'] > 0) {
            return true; // job event已存在
        }else{
            return false; // job event不存在
        }
    }

    public function check_job_event_conflict($output_job_id,$output_event){
        
        $sql = "SELECT JOBID, Pin, EvenID, signal, durate  FROM JOBOutput_lst WHERE JOBID = ?  AND EvenID =? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$output_job_id,$output_event]);
        $rows = $statement->fetch(PDO::FETCH_ASSOC);
        return $rows;
    }

    public function check_event_conflict($output_job_id,$output_event){
        
        $sql = "SELECT count(*) FROM JOBOutput_lst WHERE JOBID = ? AND EvenID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$output_job_id,$output_event]);
        $count = $statement->fetchColumn();

        return (int)$count;
    }

    public function check_event_conflict_by_job_id($output_job_id){
        
        $sql = "SELECT * FROM JOBOutput_lst  WHERE JOBID = ? AND EvenID NOT IN('6','7','8') ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$output_job_id]);
        $rows = $statement->fetch(PDO::FETCH_ASSOC);

        return $rows;
    }


    
    public function check_event_pin_by_job_id($output_job_id,$wave){
        
        $sql = "SELECT * FROM output WHERE output_job_id = ? AND  wave = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$output_job_id,$wave]);
        $rows = $statement->fetch(PDO::FETCH_ASSOC);

        return !empty($rows);
    }


    public function create_output($output_data) {    

        // 預設值處理
        if (!isset($output_data['durate']) || $output_data['durate'] === '' || $output_data['durate'] === null) {
            $output_data['durate'] = 100;
        }

        $output_data['stop_trig'] = 1;
        $output_data['cycle'] = 1;

        $sql = "INSERT INTO `JOBOutput_lst` (JOBID, Pin, EvenID, signal, durate, stop_trig, cycle) ";
        $sql .= "VALUES (:jobid, :pin, :evenid, :signal, :durate, :stop_trig, :cycle) ";        

        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':jobid', $output_data['JOBID']);
        $statement->bindValue(':pin', $output_data['Pin']);
        $statement->bindValue(':evenid', $output_data['EvenID']);
        $statement->bindValue(':signal', $output_data['signal']); 
        $statement->bindValue(':durate', $output_data['durate']); 
        $statement->bindValue(':stop_trig', $output_data['stop_trig']);
        $statement->bindValue(':cycle', $output_data['cycle']); 
        $results = $statement->execute();

        return $results;
    }




    public function edit_output($output_data) {

        // 預設 durate 值
        if (!isset($output_data['durate']) || $output_data['durate'] === '' || $output_data['durate'] === null) {
            $output_data['durate'] = 100;
        }

        $sql = "UPDATE `JOBOutput_lst` 
                SET EvenID = :EvenID, 
                    Pin = :Pin,
                    signal = :signal, 
                    durate = :durate 
                WHERE EvenID = :EvenID AND JOBID = :JOBID";

        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':JOBID', $output_data['JOBID']);
        $statement->bindValue(':EvenID', $output_data['EvenID']);
        $statement->bindValue(':Pin', $output_data['Pin']);
        $statement->bindValue(':signal', $output_data['signal']);
        $statement->bindValue(':durate', $output_data['durate']);
        $results = $statement->execute();
        
        return $results;
    }


    public function copy_output_by_id($from_job_id,$to_job_id){
        // 判斷job_id是否存在，若存在就先把舊的刪除
        // $dupli_flag true:表示job_id已存在 false:表示job_id不存在
        if(true){//先刪除再複製
            $this->delete_output_by_id($to_job_id);
        }
        $sql= "INSERT INTO JOBOutput_lst ( JOBID,Pin,EvenID,signal,durate,stop_trig,cycle )
                SELECT  ?,Pin,EvenID,signal,durate,stop_trig,cycle
                FROM    JOBOutput_lst
                WHERE JOBID = ? ";
        $statement = $this->db_iDas->prepare($sql);

        return $results = $statement->execute([$to_job_id,$from_job_id]);
    }

    //delete output by job_id
    public function delete_output_by_id($job_id){

        $sql= "DELETE FROM JOBOutput_lst  WHERE JOBID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$job_id]);

        return $results;
    }
    //delete output by job_id and event_id
    public function delete_output_event_by_id($output_job_id,$output_event){

        $sql= "DELETE FROM JOBOutput_lst WHERE JOBID = ? AND EvenID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$output_job_id,$output_event]);

        return $results;
    }


    public function generateTableCell($value,$value2) {
        if($value >= 1 && $value <= 11){
            $tableCells = "";
            for($i = 1; $i <= 11; $i++){
                if($i == $value){ 
                    if($value2 == 0){
                        $img = '<img src="./img/signal01.png" style="max-width: 50px;">';
                    }else if($value2 == 1){
                        $img = '<img src="./img/signal02.png" style="max-width: 50px;">';
                    }else if($value2 == 2){
                        $img = '<img src="./img/trigger.png" style="max-width: 50px;">';
                    }else{
                        $img = '';
                    }
                    $tableCells .= "<td  data-outputpin = '".$value."'>".$img."</td>";
                }else{
                    $tableCells .= "<td></td>";
                }
            }
            return $tableCells;
        }else{
            return "";  
        }
    }


    public function set_output_alljob($output_job_id) {

        try {
            // 1. 取得目前狀態
            $sqlCheck = "SELECT output_unified FROM JOB_lst WHERE JOBID = ?";
            $stmtCheck = $this->db_iDas->prepare($sqlCheck);
            $stmtCheck->execute([$output_job_id]);
            $currentStatus = $stmtCheck->fetchColumn();

            //var_dump($currentStatus);

            if ($currentStatus === false) {
                return false; // JOBID 不存在
            }

            if ($currentStatus == '1') {

                //echo "eert";die();

                // 2. 如果目前是 1 → 改成 0（取消選取）
                $sql = "UPDATE JOB_lst SET output_unified = '0' WHERE JOBID = ?";
                $stmt = $this->db_iDas->prepare($sql);
                return $stmt->execute([$output_job_id]);
            } else {
                // 3. 如果目前是 0 → 將該 JOB 設 1，其餘全部設 0
                $this->db_iDas->beginTransaction();

                // 先把所有 JOB 設 0
                $sqlReset = "UPDATE JOB_lst SET output_unified = '0'";
                $this->db_iDas->exec($sqlReset);

                // 再把指定 JOB 設 1
                $sqlUpdate = "UPDATE JOB_lst SET output_unified = '1' WHERE JOBID = ?";
                $stmtUpdate = $this->db_iDas->prepare($sqlUpdate);
                $stmtUpdate->execute([$output_job_id]);

                $this->db_iDas->commit();
                return true;
            }
        } catch (Exception $e) {
            if ($this->db_iDas->inTransaction()) {
                $this->db_iDas->rollBack();
            }
            error_log("Error in set_output_alljob: " . $e->getMessage());
            return false;
        }

       
    }

    public function check_output_unified_by_job_id($job_id) {
        try {
            $sql = "SELECT output_unified FROM JOB_lst WHERE JOBID = ?";
            $stmt = $this->db_iDas->prepare($sql);
            $stmt->execute([$job_id]);
            $result = $stmt->fetchColumn();

            // 如果沒有找到該 JOBID，回傳 null 或 false
            if ($result === false) {
                return null; // 或 return false;
            }

            // 回傳布林值：true = 1，false = 0
            return ($result == '1');
        } catch (Exception $e) {
            error_log("Error in check_output_unified_by_job_id: " . $e->getMessage());
            return false;
        }
    }

    
    public function get_output_by_job_temp(): array{

        $pdo = $this->db_iDas;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 1) 先找出 output_unified = 1 的 JOBID
        $sqlJob = "SELECT JOBID FROM JOB_lst WHERE output_unified = 1 LIMIT 1";
        $jobid = $pdo->query($sqlJob)->fetchColumn();

        if (!$jobid) {
            return []; // 沒有任何 JOB 被設為 unified
        }

        // 2) 查詢該 JOB 的輸出列表
        $sql = "SELECT * FROM JOBOutput_lst WHERE JOBID = ? ORDER BY JOBID ASC";
        $statement = $pdo->prepare($sql);
        $statement->execute([$jobid]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }


}
