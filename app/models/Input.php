<?php

class Input{   
    private $db_iDas;
    private $db_iDas_device;

    // 在建構子將 Database 物件實例化
    public function __construct(){
        
        $db_instance = new Database();             
        $this->db_iDas = $db_instance->getDb_das();       
        $this->db_iDas_device = $db_instance->getDb_das_device();

    }

    //get_input_by_job_id
    public function get_input_by_job_id($job_id)
    {   
        $sql = "SELECT * FROM JOBInput_lst WHERE JOBID = ? ORDER BY CASE WHEN EvenID >= 200 THEN 0 ELSE 1 END, EvenID ";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$job_id]);
        $row = $statement->fetchall(PDO::FETCH_ASSOC);

        return $row;
    }

    //get device_input_alljob
    public function get_input_alljob()
    {
        $sql = "SELECT * FROM device";

        // 檢查資料庫連線
        if (!($this->db_iDas_device instanceof PDO)) {
            error_log("❌ db_iDas_device is not a valid PDO instance");
            die("❌ 無效的資料庫連線 (db_iDas_device)");
        }

        $statement = $this->db_iDas_device->prepare($sql);

        // 檢查 prepare 是否成功
        if (!$statement) {
            $errorInfo = $this->db_iDas_device->errorInfo();
            error_log("❌ SQL Prepare Failed: $sql");
            error_log("❌ Error Info: " . print_r($errorInfo, true));
            die("❌ SQL 準備失敗: 請檢查資料表 device 是否存在");
        }

        $statement->execute();

        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row;
    }


    //get all job
    public function get_job_list()
    {
        $sql = " SELECT  * FROM  JOB_lst  WHERE JOBID NOT IN('0','221') ORDER BY JOBID ASC ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result;
    }

    public function check_job_event_conflict($input_job_id,$input_event){
        
        $sql = "SELECT *  FROM JOBInput_lst WHERE JOBID = ? AND EvenID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$input_job_id,$input_event]);
        $rows = $statement->fetch();

        return $rows;
    }

    public function check_job_event_count($input_job_id,$input_event){
        
        $sql = "SELECT count(*)  FROM JOBInput_lst WHERE JOBID = ? AND EvenID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$input_job_id,$input_event]);
        $count = $statement->fetchColumn();
        
        return (int)$count;
    }

    public function check_job_event($input_job_id){
        
        $sql = "SELECT *  FROM JOBInput_lst WHERE JOBID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$input_job_id]);
        $rows = $statement->fetchAll();

        return $rows;

    }

    public function create_input($input_data) {   
        $sql = "INSERT INTO `JOBInput_lst` (JOBID, Pin, EvenID, signal, Wp_Ready_Confirm) ";
        $sql .= "VALUES (:JOBID, :Pin, :EvenID, :signal, :Wp_Ready_Confirm) ";

        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':JOBID', $input_data['JOBID']);
        $statement->bindValue(':Pin', $input_data['Pin']);
        $statement->bindValue(':EvenID', $input_data['EvenID']);
        $statement->bindValue(':signal', $input_data['signal']);
        $statement->bindValue(':Wp_Ready_Confirm', $input_data['Wp_Ready_Confirm'] ?? 0);

        $results = $statement->execute();

        return $results;
    }


    //delete input by job_id
    public function delete_input_by_id($job_id){

        $sql= "DELETE FROM JOBInput_lst WHERE JOBID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$job_id]);

        return $results;
    }


    //delete input by job_id and event_id
    public function delete_input_event_by_id($job_id,$input_event){
        $sql= "DELETE FROM JOBInput_lst WHERE JOBID = ? AND EvenID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$job_id,$input_event]);

        return $results;
    }

    //set input_alljob
    public function set_input_alljob($input_job_id) {
        try {
            // 1. 取得目前狀態
            $sqlCheck = "SELECT input_unified FROM JOB_lst WHERE JOBID = ?";
            $stmtCheck = $this->db_iDas->prepare($sqlCheck);
            $stmtCheck->execute([$input_job_id]);
            $currentStatus = $stmtCheck->fetchColumn();

            if ($currentStatus === false) {
                return false; // JOBID 不存在
            }

            if ($currentStatus == '1') {
                // 2. 如果目前是 1 → 改成 0（取消選取）
                $sql = "UPDATE JOB_lst SET input_unified = '0' WHERE JOBID = ?";
                $stmt = $this->db_iDas->prepare($sql);
                return $stmt->execute([$input_job_id]);
            } else {
                // 3. 如果目前是 0 → 將該 JOB 設 1，其餘全部設 0
                $this->db_iDas->beginTransaction();

                // 先把所有 JOB 設 0
                $sqlReset = "UPDATE JOB_lst SET input_unified = '0'";
                $this->db_iDas->exec($sqlReset);

                // 再把指定 JOB 設 1
                $sqlUpdate = "UPDATE JOB_lst SET input_unified = '1' WHERE JOBID = ?";
                $stmtUpdate = $this->db_iDas->prepare($sqlUpdate);
                $stmtUpdate->execute([$input_job_id]);

                $this->db_iDas->commit();
                return true;
            }
        } catch (Exception $e) {
            if ($this->db_iDas->inTransaction()) {
                $this->db_iDas->rollBack();
            }
            error_log("Error in set_input_alljob: " . $e->getMessage());
            return false;
        }
    }

    public function generateTableCell($value,$value2) {
        if($value >= 2 && $value <= 12){
            $tableCells = "";
            for($i = 2; $i <= 12; $i++){
                if($i == $value){ 
                    if($value2 == 1){
                        $img = '<img src="./img/high.png" style="max-width: 50px;">';
                    }else{
                        $img = '<img src="./img/low.png" style="max-width: 50px;">';
                    }
                    $tableCells .= "<td>".$img."</td>";
                }else{
                    $tableCells .= "<td></td>";
                }
            }
            return $tableCells;
        }else{
            return ""; 
        }
    }


    public function get_input_by_job_temp($jobid): array{

        $jobid = (int)$jobid;
        if ($jobid <= 0) return [];

        $pdo = $this->db_iDas;
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // TODO: 換成你實際的 inputs 資料表與欄位
        $stmt = $pdo->prepare("
            SELECT * 
            FROM JOBInput_lst
            WHERE JOBID = :jobid
        ");
        $stmt->execute([':jobid' => $jobid]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }   
}
