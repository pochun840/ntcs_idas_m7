<?php

class Setting{

    private $db_data;//devdb tool
    private $db_iDas;
    private $db_iDas_device;
    private $db_barcode;
    private $db_iDas_tools;
    private $db_iDas_login;
    private $dbh;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db_iDas_tools = new Database;
        $this->db_iDas_tools = $this->db_iDas_tools->getDb_das_tools();

        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();


        $this->db_barcode = new Database;
        $this->db_barcode = $this->db_barcode->getDb_das_barcode();

        $this->db_iDas_login = new Database;
        $this->db_iDas_login  = $this->db_iDas_login->getDb_das_login();



        $this->dbh = new Database;

    }

    public function GetControllerInfo()
    {
        $sql = "SELECT * FROM " . TABLE_NTCS_DEVICE;
        $statement = $this->db_iDas_tools->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);


        return $row;
    }


    public function GetControllerInfo_count($control_id){

        $sql = "SELECT count(*) AS count FROM " .TABLE_NTCS_DEVICE." WHERE device_id = :device_id"; 
        $statement = $this->db_iDas_tools->prepare($sql);
        $statement->bindValue(':device_id', $control_id);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC); 
        return $row;
    }


    public function system_storage(){
        
        $EMMC_BASE = "/var/www/html/database/";
        $TOTAL_CAPACITY_GB = 1.1;  // 預設總容量（可從 config 抽出）

        $percent = 'X';

        if (PHP_OS_FAMILY === 'Linux') {
            $size = 0;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($EMMC_BASE)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }

            $gigatmp = $size / 1024 / 1024 / 1024;
            $percent = ceil(($gigatmp / $TOTAL_CAPACITY_GB) * 100);
        }

        return $percent;
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
        $sql = "UPDATE " . TABLE_NTCS_DEVICE . " 
        SET device_name = :device_name,
            storage_warning =:storage_warning,
            torque_filter=:torque_filter,
            language = :language,
            torque_unit =:torque_unit,
            circular_archive =:circular_archive,
            counting_method = :counting_method,
            blackout_recovery = :blackout_recovery,
            buzzer_mode = :buzzer_mode
        WHERE device_id = :device_id";

        $statement = $this->db_iDas_tools->prepare($sql);


        $statement->bindValue(':device_name', $con_setting['control_name']);
        $statement->bindValue(':language', $con_setting['lang_val']);
        $statement->bindValue(':torque_unit', $con_setting['unit_val']);
        $statement->bindValue(':storage_warning',$con_setting['storage_warning']);
        $statement->bindValue('torque_filter',$con_setting['torque_filter']);
        $statement->bindValue(':circular_archive',$con_setting['circular_archive']);
        $statement->bindValue(':counting_method',$con_setting['counting_method']);
        $statement->bindValue(':blackout_recovery',$con_setting['blackout_recovery']);
        $statement->bindValue(':buzzer_mode', $con_setting['buzzer_mode']);
        $statement->bindValue(':device_id', $con_setting['control_id']);

        // 執行查詢並返回結果
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

    public function GetAllBarcodes(){
    
        $sqlBarcode = "SELECT * FROM " . TABLE_NTCS_BARCODE;
        $statementBarcode = $this->db_barcode->prepare($sqlBarcode);
        $statementBarcode->execute();
        $barcodeRows = $statementBarcode->fetchAll(PDO::FETCH_ASSOC);


        $sqlJob = "SELECT JOBID, JOBname FROM JOB_lst";
        $statementJob = $this->db_iDas->prepare($sqlJob);
        $statementJob->execute();
        $jobRows = $statementJob->fetchAll(PDO::FETCH_ASSOC);

   
        foreach ($barcodeRows as $key => &$barcodeRow) {
            $jobMatched = false; 
            foreach ($jobRows as $jobRow) {
     
                if ($barcodeRow['job_id'] == $jobRow['JOBID']) {
                    $barcodeRow['JOBname'] = $jobRow['JOBname'];
                    $jobMatched = true; 
                    break;
                }
            }
            if (!$jobMatched) {
                unset($barcodeRows[$key]);
            }
        }
        return $barcodeRows; 
    }


    public function Update_Barcode($barcode)
    {
        if( $this->check_barcode_conflict($barcode['barcode_job']) ){ 

        
            $sql = "UPDATE ".TABLE_NTCS_BARCODE." 
                    SET barcode = :barcode,
                        range_from  = :range_from,
                        range_count = :range_count,
                        barcode_mode = :barcode_mode,
                        seq_id =:seq_id
                    WHERE job_id = :job_id ";
            $statement = $this->db_barcode->prepare($sql);
            $statement->bindValue(':barcode', $barcode['barcode_name']);
            $statement->bindValue(':range_from', $barcode['barcode_range_from']);
            $statement->bindValue(':range_count', $barcode['barcode_range_count']);
            $statement->bindValue(':job_id',$barcode['barcode_job']);
            $statement->bindValue(':seq_id',$barcode['barcode_seq']);
            $statement->bindValue(':barcode_mode',$barcode['barcode_mode']);
            $results = $statement->execute();


        }else{ //不存在，用insert

            $sql = "INSERT INTO ".TABLE_NTCS_BARCODE." (job_id, barcode, range_from, range_count, barcode_mode, seq_id) 
            VALUES (:job_id, :barcode, :range_from, :range_count, :barcode_mode, :seq_id)";
    
            $statement = $this->db_barcode->prepare($sql);
            $statement->bindValue(':job_id', $barcode['barcode_job']); 
            $statement->bindValue(':barcode', $barcode['barcode_name']);
            $statement->bindValue(':range_from', $barcode['barcode_range_from']);
            $statement->bindValue(':range_count', $barcode['barcode_range_count']);
            $statement->bindValue(':barcode_mode', $barcode['barcode_mode']); 
            $statement->bindValue(':seq_id', $barcode['barcode_seq']); 
            $results = $statement->execute();


        }

        return $results;
    }

    public function check_barcode_conflict($job_id){
        
        $sql = "SELECT count(*) as count FROM ".TABLE_NTCS_BARCODE."  WHERE job_id = :job_id ";
        $statement = $this->db_barcode->prepare($sql);
        $statement->bindValue(':job_id', $job_id);
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
        $sql = "SELECT * FROM JOB_lst ORDER BY  JOBID ";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute();
        $rows = $statement->fetchall(PDO::FETCH_ASSOC);

        $filtered_job_list = array_filter($rows, function($job) {
            return $job['JOBID'] != 0 && $job['JOBID'] != 221;
        });

        
        return array_values($filtered_job_list);


    }

    //get all job seq
    public function get_seq_list($job_id)
    {
        $sql = "SELECT JOBID,SEQID,SEQname FROM SEQ_lst  WHERE  JOBID = :JOBID AND act = 1 order by SEQID  ASC ";
        $statement = $this->db_iDas ->prepare($sql);
        $statement->bindValue(':JOBID', $job_id);
        $results = $statement->execute();
        $rows = $statement->fetchall(PDO::FETCH_ASSOC);

        return $rows;
    }

    //delete job barcdoe
    public function delete_job_barcode($barcode){

        foreach($barcode['job_id'] as $key =>$val){

            $sql = "DELETE FROM " . TABLE_NTCS_BARCODE . " WHERE job_id = :job_id ";
            $statement = $this->db_barcode->prepare($sql);
            $statement->bindValue(':job_id', $val[0]);
            $results = $statement->execute();
    
            
        }

        return $results;
    }


    public function edit_feature_pwd($pwd_arr){

        $sql = "UPDATE " . TABLE_NTCS_DEVICE . "
                SET clearseq_button_pwd = :clearseq_button_pwd,
                clear_button_pwd = :clear_button_pwd,
                confirm_button_pwd = :confirm_button_pwd,
                enable_button_pwd = :enable_button_pwd,
                disable_button_pwd = :disable_button_pwd,
                skip_button_pwd = :skip_button_pwd ";
        $statement = $this->db_iDas_tools->prepare($sql);
        $statement->bindValue(':clearseq_button_pwd', $pwd_arr['clearseq_button_pwd']);
        $statement->bindValue(':clear_button_pwd', $pwd_arr['clear_button_pwd']);
        $statement->bindValue(':confirm_button_pwd', $pwd_arr['confirm_button_pwd']);
        $statement->bindValue(':enable_button_pwd', $pwd_arr['enable_button_pwd']);
        $statement->bindValue(':disable_button_pwd', $pwd_arr['disable_button_pwd']);
        $statement->bindValue(':skip_button_pwd', $pwd_arr['skip_button_pwd']);
        $results = $statement->execute();

        return $results;

    }


    public function edit_feature_color($color_arr){

        $sql = "UPDATE " . TABLE_NTCS_DEVICE . "
                SET okseqcolor = :okseqcolor,
                okjobcolor = :okjobcolor";
        $statement = $this->db_iDas_tools->prepare($sql);
        $statement->bindValue(':okseqcolor', $color_arr['okseqcolor']);
        $statement->bindValue(':okjobcolor', $color_arr['okjobcolor']);
        $results = $statement->execute();

        return $results;

    }

    
    public function edit_feature_global_downshift($global_downshift_arr){

        $sql = "UPDATE " . TABLE_NTCS_DEVICE . "
                SET global_downshift_torque = :global_downshift_torque,
               global_downshift_speed = :global_downshift_speed";
        $statement = $this->db_iDas_tools->prepare($sql);
        $statement->bindValue(':global_downshift_torque', $global_downshift_arr['global_downshift_torque']);
        $statement->bindValue(':global_downshift_speed', $global_downshift_arr['global_downshift_speed']);
        $results = $statement->execute();

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
        $sql = "SELECT * FROM ". TABLE_NTCS_DEVICE;
        $statement = $this->db_iDas_tools->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['torque_unit'];
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

    public function get_idas_version(){
        $sql = "SELECT * FROM config where id = '9' ";
        $statement = $this->db_iDas_login->prepare($sql);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    public function get_seq_list_for_modbus($job_id){

        $sql = "SELECT JOBID,SEQID,SEQname FROM SEQ_lst WHERE JOBID = :JOBID AND act = 1 order by SEQID ASC ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':JOBID', $job_id);
        $results = $statement->execute();
        $rows = $statement->fetchall(PDO::FETCH_ASSOC);

        return $rows;
    }



}
