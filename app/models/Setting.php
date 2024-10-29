<?php

class Setting{
    //private $db;//condb control box
    //private $db_dev;//devdb tool
    private $db_data;//devdb tool
    //private $db_das;//devdb tool
    private $db_iDas;
    private $db_iDas_device;
    private $dbh;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db_iDas_device = new Database;
        $this->db_iDas_device = $this->db_iDas_device->getDb_das_device();

        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();

        $this->dbh = new Database;

    }

    public function GetControllerInfo()
    {
        $sql = "SELECT * FROM device ";
        $statement = $this->db_iDas_device->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);


        return $row;
    }


    public function GetControllerInfo_count($control_id){

        $sql = "SELECT count(*) AS count FROM device WHERE device_id = :device_id";
        $statement = $this->db_iDas_device->prepare($sql);
        $statement->bindValue(':device_id', $control_id);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC); 


    
        return $row;
    }

    /*public function GetDeviceInfo()
    {
        $sql = "SELECT * FROM device_info ";
        $statement = $this->db_dev->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }*/

    public function GetToolInfo()
    {
        $sql = "SELECT * FROM tool_info ";
        $statement = $this->db_dev->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    public function GetOperator_priviledge()
    {
        $sql = "SELECT operator_priviledge FROM operator ";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['operator_priviledge'];
    }

    public function GetAllJobs()
    {
        $sql = "SELECT * FROM job ORDER BY job_id";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetchall(PDO::FETCH_ASSOC);

        return $row;
    }

    public function GetAllSequences()
    {
        $sql = "SELECT * FROM sequence ORDER BY job_id,sequence_id";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetchall(PDO::FETCH_ASSOC);

        return $row;
    }

    public function GetAllSteps()
    {
        $sql = "SELECT job_id,sequence_id,step_id,step_name FROM normalstep WHERE 1 
                union 
                SELECT job_id,sequence_id,step_id,step_name FROM advancedstep WHERE 1 ORDER BY job_id,sequence_id,step_id ";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetchall(PDO::FETCH_ASSOC);

        return $row;
    }


    public function Edit_Login_Password($conset){
        
      
        $conset['device_id'] = intval($conset['device_id']);
    
        try {
      
            $sql = "UPDATE `device` 
                    SET device_password = :device_password
                    WHERE device_id = :device_id";
    
            $statement = $this->db_iDas_device->prepare($sql);
    
            if ($statement === false) {
                $errorInfo = $this->db_iDas_device->errorInfo();
                throw new Exception("Failed to prepare SQL statement: " . $errorInfo[2]);
            }
    
            $statement->bindValue(':device_password', $conset['new_password']);
            $statement->bindValue(':device_id', $conset['device_id']);
    
            $results = $statement->execute();
    
            if ($results === false) {
                $errorInfo = $statement->errorInfo();
                throw new Exception("Failed to execute SQL statement: " . $errorInfo[2]);
            }
    
            $sql_1 = "UPDATE `operator` 
                      SET operator_adminpwd = :operator_adminpwd
                      WHERE operator_loginflag = :operator_loginflag";
    
            $statement_1 = $this->db_iDas->prepare($sql_1);
    
            if ($statement_1 === false) {
                $errorInfo = $this->db_iDas->errorInfo();
                throw new Exception("Failed to prepare SQL statement: " . $errorInfo[2]);
            }
    
            $statement_1->bindValue(':operator_adminpwd', $conset['new_password']);
            $statement_1->bindValue(':operator_loginflag', 1);
    
            $results1 = $statement_1->execute();
    
            if ($results1 === false) {
                $errorInfo = $statement_1->errorInfo();
                throw new Exception("Failed to execute SQL statement: " . $errorInfo[2]);
            }
    
            return $results && $results1;
        } catch (Exception $e) {
            $this->logMessage('Error: ' . $e->getMessage());
            echo json_encode(array('error' => $e->getMessage()));
            return false;
        }
    }

    public function system_date_edit($conset){

        $conset['device_id'] = intval($conset['device_id']);
        
        $sql = "UPDATE `device` 
        SET device_time = :device_time
        WHERE device_id = :device_id";

        $statement = $this->db_iDas_device->prepare($sql);
        $statement->bindValue(':device_time', $conset['newTime']);
        $statement->bindValue(':device_id', $conset['device_id']);
        $results = $statement->execute();
        return  $results;

    }
    

    public function Edit_Priviledge($value)
    {   
        if($value >= 65472 && $value <= 65535){
            $sql = "UPDATE `operator` SET operator_priviledge = ? ";
            $statement = $this->db->prepare($sql);
            $results = $statement->execute([$value]);
        }else{
            $results = false;
        }
        // $row = $statement->fetchall(PDO::FETCH_ASSOC);

        return $results;
    }

    public function Controller_Setting($con_setting)
    {
       
        $sql = "UPDATE `device` 
        SET device_name = :device_name,
            device_language = :language_val,
            batch = :batch,
            buzzer_mode = :buzzer_mode
        WHERE device_id = :device_id ";
        
        $statement = $this->db_iDas_device->prepare($sql);
        $statement->bindValue(':device_name', $con_setting['control_name']);
        $statement->bindValue(':language_val', $con_setting['lang_val']);
        $statement->bindValue(':batch', $con_setting['batch_val']);
        $statement->bindValue(':buzzer_mode', $con_setting['buzzer_val'] );
        $statement->bindValue(':device_id', $con_setting['control_id'] );

        $results = $statement->execute();


        return $results;
    }

    public function Get_Controller_DB_version()
    {
        // code...
        $Controller_db_con = new PDO('sqlite:/home/kls/tcc/resource/db_emmc/data.db'); //測試機
        $sql = "SELECT * FROM `device` ";
        $statement = $Controller_db_con->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['tcscondb_version'];
    }

    public function Get_Controller_Device_version()
    {
        // code...
        $Controller_db_con = new PDO('sqlite:/home/kls/tcc/resource/db_emmc/data.db'); //測試機
        $sql = "SELECT * FROM `device_info` ";
        $statement = $Controller_db_con->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['device_version'];
    }

    public function GetAllBarcodes()
    {
        $sql = "SELECT barcode.*,job.job_name FROM barcode left join `job` on barcode_selected_job = job_id order by barcode_selected_job";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute();
        $rows = $statement->fetchall(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function Update_Barcode($barcode)
    {
        if( $this->check_barcode_conflict($barcode['barcode_job']) ){ 

        
            $sql = "UPDATE `barcode` 
                    SET barcode = :barcode,
                        barcode_range_from  = :barcode_range_from,
                        barcode_range_count = :barcode_range_count,
                    WHERE barcode_selected_job = :barcode_selected_job ";
            $statement = $this->db_iDas->prepare($sql);
            $statement->bindValue(':barcode', $barcode['barcode_name']);
            $statement->bindValue(':barcode_range_from', $barcode['barcode_range_from']);
            $statement->bindValue(':barcode_range_count', $barcode['barcode_range_count']);
            $statement->bindValue(':barcode_selected_job',$barcode['barcode_job']);
            $results = $statement->execute();


        }else{ //不存在，用insert

            $sql = "INSERT INTO `barcode` ('barcode','barcode_range_from','barcode_range_count','barcode_selected_job')
                    VALUES (:barcode,:barcode_range_from,:barcode_range_count,:barcode_selected_job )";
            $statement = $this->db_iDas->prepare($sql);
            $statement->bindValue(':barcode', $barcode['barcode_name']);
            $statement->bindValue(':barcode_range_from', $barcode['barcode_range_from']);
            $statement->bindValue(':barcode_range_count', $barcode['barcode_range_count']);
            $statement->bindValue(':barcode_selected_job', $barcode['barcode_job']);
            $results = $statement->execute();

        }

        return $results;
    }

    public function check_barcode_conflict($job_id){
        
        $sql = "SELECT count(*) as count FROM barcode WHERE barcode_selected_job = :barcode_selected_job";
        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':barcode_selected_job', $job_id);
        $results = $statement->execute();
        $rows = $statement->fetch();
        
        if ($rows['count'] > 0) {
            return true; // job event已存在
        }else{
            return false; // job event不存在
        }

    }


    //get all job
    public function get_job_list()
    {
        $sql = "SELECT * FROM job ORDER BY job_id";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute();
        $rows = $statement->fetchall(PDO::FETCH_ASSOC);

        return $rows;
    }

    //get all job seq
    public function get_seq_list($job_id)
    {
        $sql = "SELECT job_id,sequence_id,sequence_name FROM sequence WHERE job_id = :job_id AND sequence_enable = 1 order by sequence_id";
        $statement = $this->db->prepare($sql);
        $statement->bindValue(':job_id', $job_id);
        $results = $statement->execute();
        $rows = $statement->fetchall(PDO::FETCH_ASSOC);

        return $rows;
    }

    //get job barcdoe
    public function get_job_barcode($job_id)
    {
        $sql = "SELECT * FROM barcode WHERE barcode_selected_job = :job_id ";
        $statement = $this->db->prepare($sql);
        $statement->bindValue(':job_id', $job_id);
        $results = $statement->execute();
        $rows = $statement->fetchall(PDO::FETCH_ASSOC);

        return $rows;
    }

    //delete job barcdoe
    public function delete_job_barcode($barcode)
    {
        foreach($barcode as $key =>$val){
            $sql = "DELETE FROM barcode WHERE barcode_selected_job = :job_id ";
            $statement = $this->db_iDas->prepare($sql);
            $statement->bindValue(':job_id', $val);
            $results = $statement->execute();
    
            
        }

        return $results;
    }

    //get update information
    public function get_update_info()
    {
        //1.tcscondb_version from tcscon.db device table
        //2.device_version from tcsdev.db device_info table
        //3.tcsdevdb_version from tcsdev.db device_info table
        $results = array();

        // $controller_info = $this->GetControllerInfo();//666 Get_Controller_DB_version
        // $device_info = $this->GetDeviceInfo();
        
        // $results['tcscondb_version'] = $controller_info['tcscondb_version'];
        // $results['device_version'] = $device_info['device_version'];
        // $results['tcsdevdb_version'] = $device_info['tcsdevdb_version'];

        //判斷控制器本身 而非idas複製出來的db
        $controller_db_version = $this->Get_Controller_DB_version();//666 Get_Controller_DB_version
        $device_version = $this->Get_Controller_Device_version();

        $results['tcscondb_version'] = trim($controller_db_version);
        $results['device_version'] = trim($device_version);

        return $results;
    }

    public function update_idas_vesrion($new_version)
    {

        $exist = $this->check_das_config('idas_version');

        if($exist){
            $sql = "UPDATE `config` 
                    SET config_value = :new_version
                    WHERE config_name = 'idas_version' ";
        }else{
            $sql = "INSERT INTO `config` ('config_name','config_value' )
                    VALUES ('idas_version',:new_version )";
        }

        $statement = $this->db_das->prepare($sql);
        $statement->bindValue(':new_version', $new_version);
        $results = $statement->execute();

        return $results;
    }

    public function update_idas_match_gtcs_app_version($new_version)
    {

        $exist = $this->check_das_config('match_gtcs_app_version');

        if($exist){
            $sql = "UPDATE `config` 
                    SET config_value = :new_version
                    WHERE config_name = 'match_gtcs_app_version' ";
        }else{
            $sql = "INSERT INTO `config` ('config_name','config_value' )
                    VALUES ('match_gtcs_app_version',:new_version )";
        }

        $statement = $this->db_das->prepare($sql);
        $statement->bindValue(':new_version', $new_version);
        $results = $statement->execute();

        return $results;
    }

    public function check_das_config($config_name)
    {
        $sql = "SELECT count(*) as count FROM `config` WHERE config_name = :config_name";
        $statement = $this->db_das->prepare($sql);
        $statement->bindValue(':config_name', $config_name);
        $results = $statement->execute();
        $rows = $statement->fetch();

        if ($rows['count'] > 0) {
            return true; // job event已存在
        }else{
            return false; // job event不存在
        }
    }

    public function Get_System_Toq_Unit()
    {
        $sql = "SELECT device_torque_unit FROM device";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['device_torque_unit'];
    }

    public function backup_CopyFile($sourceFile, $backupFile) {
        // 檢查來源文件是否存在
        if (!file_exists($sourceFile)) {
            echo "來源文件不存在: $sourceFile\n"; // 输出调试信息
            return false; // 如果來源文件不存在，返回 false
        } else {
            echo "來源文件存在: $sourceFile\n"; // 输出调试信息
        }
    
        // 創建備份文件
        if (file_exists($backupFile)) {
            unlink($backupFile); // 如果備份文件已存在，刪除它
            echo "備份文件已存在，已刪除: $backupFile\n"; // 输出调试信息
        } else {
            echo "備份文件不存在，準備創建: $backupFile\n"; // 输出调试信息
        }
    
        // 複製來源文件到備份文件
        if (!copy($sourceFile, $backupFile)) {
            echo "複製失敗: $sourceFile 到 $backupFile\n"; // 输出调试信息
            return false; // 如果複製失敗，返回 false
        }
    
        echo "複製成功: $sourceFile 到 $backupFile\n"; // 输出调试信息
        return true; // 成功時返回 true
    }
    


    public function backupRemoveAndCopyDatabase($sourceFile, $backupFile, $newFile) {
        // 檢查源文件是否存在
        if (!file_exists($sourceFile)) {
            return false; // 如果源文件不存在，返回錯誤
        }
    
        // 嘗試備份源文件
        if (!copy($sourceFile, $backupFile)) {
            return false; // 如果備份失敗，返回錯誤
        }
    
        // 嘗試將新的資料庫文件複製到指定位置
        if (!copy($newFile, $sourceFile)) {
            return false; // 如果複製失敗，返回錯誤
        }
    
        return true; // 成功完成所有操作
    }

}
