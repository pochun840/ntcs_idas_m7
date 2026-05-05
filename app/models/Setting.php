<?php

class Setting{

    private $db_data;//devdb tool
    private $db_iDas;
    private $db_iDas_device;
    private $db_barcode;
    private $db_iDas_tools;
    private $db_iDas_login;
    private $dbh;
    private $db_das;

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



    public function Controller_Setting($con_setting){
        // 舊ID（WHERE 用）
        $device_id_old = $con_setting['control_id'] ?? null;
        // 新ID（SET 用）；沒提供就沿用舊ID（= 不改）
        $device_id_new = $con_setting['control_id_new'] ?? $device_id_old;

        // 舊 ID 為 NULL（或空字串）→ 用 IS NULL；否則用等號
        $useIsNull = is_null($device_id_old) || $device_id_old === '';


        $sql = "UPDATE " . TABLE_NTCS_DEVICE . " 
            SET device_id               = :device_id_new,
                device_name             = :device_name,
                storage_warning         = :storage_warning,
                torque_filter           = :torque_filter,
                language                = :language,
                torque_unit             = :torque_unit,
                circular_archive        = :circular_archive,
                counting_method         = :counting_method,
                blackout_recovery       = :blackout_recovery,
                buzzer_mode             = :buzzer_mode,
                global_downshift_torque = :global_downshift_torque,
                global_downshift_speed  = :global_downshift_speed
            WHERE " . ($useIsNull ? "device_id IS NULL" : "device_id = :device_id_old");

        $st = $this->db_iDas_tools->prepare($sql);

        // ★ 一定要綁 :device_id_new
        if ($device_id_new === null || $device_id_new === '') {
            // 若你允許把新 ID 設回 NULL，就用 PARAM_NULL；否則可在此擋掉並回傳 false
            $st->bindValue(':device_id_new', null, PDO::PARAM_NULL);
        } else {
            $st->bindValue(':device_id_new', $device_id_new, PDO::PARAM_STR);
        }

        // 其他欄位（依你資料型別可用 PARAM_INT/STR）
        $st->bindValue(':device_name',             $con_setting['control_name']);
        $st->bindValue(':storage_warning',         $con_setting['storage_warning']);
        $st->bindValue(':torque_filter',           $con_setting['torque_filter']);
        $st->bindValue(':language',                $con_setting['lang_val']);
        $st->bindValue(':torque_unit',             $con_setting['unit_val']);
        $st->bindValue(':circular_archive',        $con_setting['circular_archive']);
        $st->bindValue(':counting_method',         $con_setting['counting_method']);
        $st->bindValue(':blackout_recovery',       $con_setting['blackout_recovery']);
        $st->bindValue(':buzzer_mode',             $con_setting['buzzer_mode']);
        $st->bindValue(':global_downshift_torque', $con_setting['global_downshift_torque']);
        $st->bindValue(':global_downshift_speed',  $con_setting['global_downshift_speed']);

        // 只有在非 NULL 的情況才綁 WHERE 的舊 ID
        if (!$useIsNull) {
            $st->bindValue(':device_id_old', $device_id_old, PDO::PARAM_STR);
        }

        $ok = $st->execute();
        // （可選）你也可以檢查受影響筆數：$rows = $st->rowCount();
        return $ok;
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


    public function Update_Barcode(array $barcode): bool
    {
        if (!isset($barcode['barcode_job']) || !is_numeric($barcode['barcode_job'])) {
            return false;
        }

        $newJobId = (int)$barcode['barcode_job'];
        $oldJobId = isset($barcode['barcode_job_old']) && is_numeric($barcode['barcode_job_old'])
            ? (int)$barcode['barcode_job_old']
            : $newJobId; // 新增模式時，兩者相同

        try {
            $this->db_barcode->beginTransaction();

            // ✅ 刪「舊 job_id」那筆
            $sqlDel = "DELETE FROM " . TABLE_NTCS_BARCODE . " WHERE job_id = :job_id";
            $stmtDel = $this->db_barcode->prepare($sqlDel);
            $stmtDel->bindValue(':job_id', $oldJobId, PDO::PARAM_INT);
            $stmtDel->execute();

            // ✅ 插入「新 job_id」
            $sqlIns = "
                INSERT INTO " . TABLE_NTCS_BARCODE . 
                " (job_id, barcode, range_from, range_count, barcode_mode, seq_id)
                VALUES
                (:job_id, :barcode, :range_from, :range_count, :barcode_mode, :seq_id)
            ";

            $stmtIns = $this->db_barcode->prepare($sqlIns);
            $stmtIns->bindValue(':job_id', $newJobId, PDO::PARAM_INT);
            $stmtIns->bindValue(':barcode', (string)($barcode['barcode_name'] ?? ''), PDO::PARAM_STR);
            $stmtIns->bindValue(':range_from', (int)($barcode['barcode_range_from'] ?? 1), PDO::PARAM_INT);
            $stmtIns->bindValue(':range_count', (int)($barcode['barcode_range_count'] ?? 0), PDO::PARAM_INT);
            $stmtIns->bindValue(':barcode_mode', (int)($barcode['barcode_mode'] ?? -1), PDO::PARAM_INT);
            $stmtIns->bindValue(':seq_id', (int)($barcode['barcode_seq'] ?? -1), PDO::PARAM_INT);

            $ok = $stmtIns->execute();

            $this->db_barcode->commit();
            return (bool)$ok;

        } catch (Throwable $e) {
            if ($this->db_barcode->inTransaction()) {
                $this->db_barcode->rollBack();
            }
            return false;
        }
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
        $sql = "SELECT JOBID,SEQID,SEQname FROM SEQ_lst  WHERE  JOBID = :JOBID AND skip = 0 order by SEQID  ASC ";
        $statement = $this->db_iDas ->prepare($sql);
        $statement->bindValue(':JOBID', $job_id);
        $results = $statement->execute();
        $rows = $statement->fetchall(PDO::FETCH_ASSOC);

        return $rows;
    }

    // 單一 job_id
    public function delete_barcodes_by_job(int $jobId): int
    {
        if ($jobId <= 0) return 0;
        return $this->delete_barcodes_by_jobs([$jobId]);
    }

    // 多個 job_id
    public function delete_barcodes_by_jobs(array $jobIds): int
    {
        // 正規化：正整數、去重
        $jobIds = array_values(array_unique(array_filter(array_map(
            fn($v) => (is_numeric($v) && (int)$v > 0) ? (int)$v : null,
            $jobIds
        ))));

        if (empty($jobIds)) return 0;

        // ⚠️ SQLite 參數上限（預設 999），保守抓 900
        $CHUNK = 900;

        $totalAffected = 0;
        try {
            $this->db_barcode->beginTransaction();

            for ($i = 0; $i < count($jobIds); $i += $CHUNK) {
                $chunk = array_slice($jobIds, $i, $CHUNK);
                $placeholders = implode(',', array_fill(0, count($chunk), '?'));
                // 調整成你的實際表與欄位
                $sql = "DELETE FROM " . TABLE_NTCS_BARCODE . " WHERE job_id IN ($placeholders)";
                $stmt = $this->db_barcode->prepare($sql);

                foreach ($chunk as $idx => $id) {
                    $stmt->bindValue($idx + 1, $id, PDO::PARAM_INT);
                }

                $stmt->execute();
                $totalAffected += $stmt->rowCount();
            }

            $this->db_barcode->commit();
            return $totalAffected;

        } catch (Throwable $e) { // 用 Throwable 比 Exception 更保險
            if ($this->db_barcode->inTransaction()) {
                $this->db_barcode->rollBack();
            }
            // 這裡可改為 log 再回 0
            return 0;
        }
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
        $statement->bindValue(':clearseq_button_pwd', $pwd_arr['clear_seq']);
        $statement->bindValue(':clear_button_pwd', $pwd_arr['clear']);
        $statement->bindValue(':confirm_button_pwd', $pwd_arr['confirm']);
        $statement->bindValue(':enable_button_pwd', $pwd_arr['enable']);
        $statement->bindValue(':disable_button_pwd', $pwd_arr['disable']);
        $statement->bindValue(':skip_button_pwd', $pwd_arr['skip']);
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

    public function check_idas_first_login($new_version){

        $exist = $this->check_das_config('idas_first_login');

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

        $sql = "SELECT JOBID,SEQID,SEQname FROM SEQ_lst WHERE JOBID = :JOBID AND skip = 0 order by SEQID ASC ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':JOBID', $job_id);
        $results = $statement->execute();
        $rows = $statement->fetchall(PDO::FETCH_ASSOC);

        return $rows;
    }

    




    private function operationAuditTableExists(): bool
    {
        $stmt = $this->db_iDas_login->prepare("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'operation_audit_log'");
        $stmt->execute();
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function getOperationAuditLogs(int $limit = 100): array
    {
        if (!$this->operationAuditTableExists()) {
            return [];
        }

        if ($limit <= 0 || $limit > 500) {
            $limit = 100;
        }

        $sql = "
            SELECT
                log_id,
                created_at,
                user_id,
                operator,
                client_ip,
                device_id,
                module,
                action,
                status,
                job_id,
                seq_id,
                step_id,
                source_job_id,
                source_seq_id,
                source_step_id,
                target_job_id,
                target_seq_id,
                target_step_id,
                title,
                message
            FROM operation_audit_log
            ORDER BY log_id DESC
            LIMIT :limit
        ";

        $stmt = $this->db_iDas_login->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    private function operationAuditNormalizeCsvKey($key): string
    {
        $key = strtolower(trim((string)$key));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? $key;
        return trim($key, '_');
    }

    private function operationAuditCsvLooksLikeHeader(array $row): bool
    {
        $known = [
            'time', 'date', 'datetime', 'timestamp', 'created_at', 'log_time',
            'user', 'username', 'operator', 'module', 'source', 'category',
            'action', 'event', 'status', 'level', 'severity',
            'message', 'msg', 'content', 'detail', 'description',
            'job_id', 'jobid', 'seq_id', 'seqid', 'step_id', 'stepid'
        ];

        foreach ($row as $cell) {
            $key = $this->operationAuditNormalizeCsvKey($cell);
            if (in_array($key, $known, true)) {
                return true;
            }
        }

        return false;
    }

    private function operationAuditIsDateLike($value): bool
    {
        $value = trim((string)$value);
        if ($value === '') {
            return false;
        }

        return (bool)preg_match('/^\d{4}[-\/]\d{1,2}[-\/]\d{1,2}(?:[ T]\d{1,2}:\d{1,2}(?::\d{1,2})?)?/', $value);
    }

    private function operationAuditFirstValue(array $assoc, array $keys): string
    {
        foreach ($keys as $key) {
            $normalized = $this->operationAuditNormalizeCsvKey($key);
            if (isset($assoc[$normalized]) && trim((string)$assoc[$normalized]) !== '') {
                return trim((string)$assoc[$normalized]);
            }
        }

        return '';
    }

    private function operationAuditDetectStatus(string $text): string
    {
        $upper = strtoupper($text);

        if (strpos($upper, 'ERROR') !== false || strpos($upper, 'FAIL') !== false || strpos($upper, 'NG') !== false) {
            return 'ERROR';
        }
        if (strpos($upper, 'WARN') !== false || strpos($upper, 'WARNING') !== false) {
            return 'WARNING';
        }
        if (strpos($upper, 'SUCCESS') !== false || strpos($upper, 'OK') !== false) {
            return 'SUCCESS';
        }

        return 'INFO';
    }

    private function operationAuditBuildAppLogRow(array $cols, array $assoc, int $lineNo): array
    {
        $rawText = trim(implode(' | ', array_map('strval', $cols)));

        $createdAt = $this->operationAuditFirstValue($assoc, [
            'created_at', 'datetime', 'timestamp', 'time', 'date', 'log_time'
        ]);

        $message = $this->operationAuditFirstValue($assoc, [
            'message', 'msg', 'content', 'detail', 'description', 'event_message'
        ]);

        // 無 header 或 header 沒有 message 時，第一欄是時間就把後面欄位合併成 message。
        if ($message === '' && count($cols) > 1 && $this->operationAuditIsDateLike($cols[0] ?? '')) {
            $message = trim(implode(' | ', array_slice($cols, 1)));
        }

        if ($message === '') {
            $message = $rawText;
        }

        if ($createdAt === '' && isset($cols[0]) && $this->operationAuditIsDateLike($cols[0])) {
            $createdAt = trim((string)$cols[0]);
        }

        $operator = $this->operationAuditFirstValue($assoc, [
            'operator', 'user', 'username', 'account'
        ]);

        $module = $this->operationAuditFirstValue($assoc, [
            'module', 'source', 'category', 'tag'
        ]);
        if ($module === '') {
            $module = 'APP';
        }

        $action = $this->operationAuditFirstValue($assoc, [
            'action', 'event', 'function', 'operation'
        ]);
        if ($action === '') {
            $action = 'LOG';
        }

        $status = $this->operationAuditFirstValue($assoc, [
            'status', 'level', 'severity', 'result'
        ]);
        if ($status === '') {
            $status = $this->operationAuditDetectStatus($rawText);
        }

        $jobId = $this->operationAuditFirstValue($assoc, ['job_id', 'jobid', 'job']);
        $seqId = $this->operationAuditFirstValue($assoc, ['seq_id', 'seqid', 'seq']);
        $stepId = $this->operationAuditFirstValue($assoc, ['step_id', 'stepid', 'step']);

        return [
            'log_id' => $lineNo,
            'created_at' => $createdAt,
            'user_id' => $operator,
            'operator' => $operator,
            'client_ip' => '',
            'device_id' => null,
            'module' => $module,
            'action' => $action,
            'status' => $status,
            'job_id' => is_numeric($jobId) ? (int)$jobId : null,
            'seq_id' => is_numeric($seqId) ? (int)$seqId : null,
            'step_id' => is_numeric($stepId) ? (int)$stepId : null,
            'source_job_id' => null,
            'source_seq_id' => null,
            'source_step_id' => null,
            'target_job_id' => null,
            'target_seq_id' => null,
            'target_step_id' => null,
            'target' => 'APP #' . $lineNo,
            'title' => 'APP Log',
            'message' => $message,
        ];
    }

    public function getAppOperationLogs(int $limit = 100): array
    {
        if ($limit <= 0 || $limit > 500) {
            $limit = 100;
        }

        $csvPath = '/home/kls/NTCS7/ntcs_log.csv';
        if (!is_file($csvPath) || !is_readable($csvPath)) {
            return [];
        }

        $fp = @fopen($csvPath, 'r');
        if (!$fp) {
            return [];
        }

        $rows = [];
        $header = null;
        $lineNo = 0;

        while (($cols = fgetcsv($fp)) !== false) {
            $lineNo++;

            // 空白列略過
            $nonEmpty = false;
            foreach ($cols as $cell) {
                if (trim((string)$cell) !== '') {
                    $nonEmpty = true;
                    break;
                }
            }
            if (!$nonEmpty) {
                continue;
            }

            if ($header === null && $lineNo === 1 && $this->operationAuditCsvLooksLikeHeader($cols)) {
                $header = array_map(function ($cell) {
                    return $this->operationAuditNormalizeCsvKey($cell);
                }, $cols);
                continue;
            }

            $assoc = [];
            if (is_array($header)) {
                foreach ($header as $idx => $key) {
                    if ($key !== '') {
                        $assoc[$key] = $cols[$idx] ?? '';
                    }
                }
            }

            $rows[] = $this->operationAuditBuildAppLogRow($cols, $assoc, $lineNo);
        }

        fclose($fp);

        usort($rows, function ($a, $b) {
            $ta = !empty($a['created_at']) ? strtotime((string)$a['created_at']) : false;
            $tb = !empty($b['created_at']) ? strtotime((string)$b['created_at']) : false;

            if ($ta !== false && $tb !== false && $ta !== $tb) {
                return $tb <=> $ta;
            }

            return ((int)($b['log_id'] ?? 0)) <=> ((int)($a['log_id'] ?? 0));
        });

        return array_slice($rows, 0, $limit);
    }

    public function getOperationAuditLogDetail(int $logId): ?array
    {
        if (!$this->operationAuditTableExists()) {
            return null;
        }

        $stmt = $this->db_iDas_login->prepare("SELECT * FROM operation_audit_log WHERE log_id = :log_id LIMIT 1");
        $stmt->bindValue(':log_id', $logId, PDO::PARAM_INT);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

}
