<?php

class Settings extends Controller
{
    private $SettingModel;
    private $AdminModel;
    private $ToolModel;
    private $MiscellaneousModel;
    private $DataModel;
    private $stepModel;
    Private $deviceId;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct(){

        
        $this->SettingModel = $this->model('Setting');
        $this->AdminModel = $this->model('Admin');
        $this->ToolModel = $this->model('Tool');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->DataModel = $this->model('Datas');
        $this->stepModel = $this->model('Steptcc');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();


    }

    // 取得所有info
    public function index(){


        // 同步控制器資料庫（ntcs_data.db）至 iDAS
        $this->ntcs_data_db_sysnc();

        // ------------------------------
        // Tool Spec Sync (Web-triggered)
        // ------------------------------
        // Gate(10s): 限制同步檢查頻率，避免每個 request 都打 DB
        // Lock:     使用 flock 防止多 request 同步造成重複/競態
        // Sync:     只在來源(controller)與目的(iDAS)值不同時才更新
        // Note:     非 cron；沒有 request 就不會自動同步
        if ($this->shouldRunToolSpecSync(10)) {
            $this->runOnceWithFlag(
                '/var/www/html/database',        // lock / state 檔案目錄
                '.tool_spec_sync',               // 任務鎖名稱（key）
                fn() => $this->check_tools_info()// 同步 ntcs_tool_test 規格值
            );
        }

        


        $isMobile = $this->isMobileCheck();

        $lang = $this->MiscellaneousModel->details('lang');
        $torque_unit = $this->MiscellaneousModel->details('torque_unit');
        $sample_rate = $this->MiscellaneousModel->details('sample_rate');
        $controller_info = $this->SettingModel->GetControllerInfo();
        $active_session = $this->AdminModel->GetActiveSession();
        $iDas_Vesion = $this->AdminModel->Get_Das_Config('idas_version');
        $max_user = $this->AdminModel->Get_Das_Config('max_concurrent_users');
        $agent_server_ip = $this->AdminModel->Get_Das_Config('agent_server_ip');
        $agent_type = $this->AdminModel->Get_Das_Config('agent_type');
        $job_list = $this->SettingModel->get_job_list();
        $barcode_mode = $this->MiscellaneousModel->details('barcode_mode');
        $idas_version = $this->SettingModel->get_idas_version();
        $disk_usage_percent = $this->SettingModel->system_storage();

        $history_year_arr = $this->DataModel->get_data_for_year();

        $iDAS_version = $idas_version['config_value'];

        $barcodes = $this->GetBarcodes();
        
        $data = array(
            'lang_arr'        => $lang,
            'controller_info' => $controller_info,
            'active_session'  => $active_session,
            'iDas_Vesion'     => $iDas_Vesion,
            'max_user'        => $max_user,
            'agent_server_ip' => $agent_server_ip,
            'agent_type'      => $agent_type,
            'job_list'        => $job_list,
            'barcodes'        => $barcodes,
            'torque_unit'     => $torque_unit,
            'sample_rate'     => $sample_rate,
            'barcode_mode'    => $barcode_mode,
            'idas_version'   => $iDAS_version,
            'disk_usage_percent' => $disk_usage_percent,
            'history_year_arr' => $history_year_arr 

        );

        if($isMobile){
            $this->view('setting/index_m', $data);
        }else{
            $this->view('setting/index', $data);
        }
       
    }

    //修改密碼 
    public function edit_password(){

        $conset = array();
        $input_check = true;
        if( !empty($_POST['device_id']) && isset($_POST['device_id'])  ){
            $conset['device_id'] = $_POST['device_id'];
        }else{ 
            $input_check = false; 
        }

        if( !empty($_POST['new_password']) && isset($_POST['new_password'])  ){
             $conset['new_password']  = $_POST['new_password'];
        }else{ 
            $input_check = false; 
        }
        

        if ($input_check) {
            $result = $this->SettingModel->Edit_Login_Password($conset);
            if($result){
                $res_msg = 'edit:'. $conset['device_id'].'password  success';
            }else{
                $res_msg = 'edit:'. $conset['device_id'].'password  fail';
            }
            echo $res_msg;

        }else{
            $result = false;
        }
    
    }


    public function edit_permission()
    {
        //default array
        $input_check = true;
        $error_message = '';
        $priviledge = [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1];

        if( !empty($_POST['Permission_Confirm']) && isset($_POST['Permission_Confirm'])  ){
            if($_POST['Permission_Confirm'] == 'false'){
                $Permission_Confirm = 1;
            }else{
                $Permission_Confirm = 0;
            }
        }else{ 
            $input_check = false; 
            $error_message .= "Permission_Confirm,";
        }

        if( !empty($_POST['Permission_Clear']) && isset($_POST['Permission_Clear'])  ){
            if($_POST['Permission_Clear'] == 'false'){
                $Permission_Clear = 1;
            }else{
                $Permission_Clear = 0;
            }
        }else{ 
            $input_check = false; 
            $error_message .= "Permission_Clear,";
        }

        if( !empty($_POST['Permission_Seq_Clear']) && isset($_POST['Permission_Seq_Clear'])  ){
            if($_POST['Permission_Seq_Clear'] == 'false'){
                $Permission_Seq_Clear = 1;
            }else{
                $Permission_Seq_Clear = 0;
            }
        }else{ 
            $input_check = false; 
            $error_message .= "Permission_Seq_Clear,";
        }

        if( !empty($_POST['Permission_SW']) && isset($_POST['Permission_SW'])  ){
            if($_POST['Permission_SW'] == 'false'){
                $Permission_SW = 0;
            }else{
                $Permission_SW = 1;
            }
        }else{ 
            $input_check = false; 
            $error_message .= "Permission_SW,";
        }

        if( !empty($_POST['Permission_Export']) && isset($_POST['Permission_Export'])  ){
            if($_POST['Permission_Export'] == 'false'){
                $Permission_Export = 0;
            }else{
                $Permission_Export = 1;
            }
        }else{ 
            $input_check = false; 
            $error_message .= "Permission_Export,";
        }

        if( !empty($_POST['Permission_Barcode']) && isset($_POST['Permission_Barcode'])  ){
            if($_POST['Permission_Barcode'] == 'false'){
                $Permission_Barcode = 0;
            }else{
                $Permission_Barcode = 1;
            }
        }else{ 
            $input_check = false; 
            $error_message .= "Permission_Barcode,";
        }

        if($input_check){

            $priviledge[12] = $Permission_Confirm;
            $priviledge[11] = $Permission_Clear;
            $priviledge[10] = $Permission_Seq_Clear;
            $priviledge[13] = $Permission_Export;
            $priviledge[14] = $Permission_SW;
            $priviledge[15] = $Permission_Barcode;

            $array2int = $this->bitArrayToDecimal($priviledge);
            $result = $this->SettingModel->Edit_Priviledge($array2int);

            if($result){
                $copy_result =  $this->copyDB_to_RamdiskDB();
                if($copy_result){
                    $this->logMessage('edit_permission:set '.$array2int.' copyDB success');
                }else{
                    $this->logMessage('edit_permission:set '.$array2int.' copyDB fail');
                }
            }


            echo json_encode(array('error' => ''));
            exit();
        }else{
            echo json_encode(array('error' => $error_message));
            exit();
        }
        
    }

    private function intTo16BitArray($value) {
        // 確保值在 0 到 65535 的範圍內
        $value = max(0, min(65535, $value));

         // 將值拆分為二進制位元的陣列
        $bitArray = [];
        for ($i = 15; $i >= 0; $i--) {
            $bitArray[] = ($value >> $i) & 1;
        }
        
        // 返回二進制位元的陣列
        return $bitArray;
    }

    private function bitArrayToDecimal($bitArray) {
        // 確保陣列長度為 16
        if (count($bitArray) !== 16) {
            throw new InvalidArgumentException("陣列長度必須為 16");
        }
        
        // 將位元陣列轉換為十進位整數
        $decimalValue = 0;
        for ($i = 15; $i >= 0; $i--) {
            $decimalValue += $bitArray[$i] * pow(2, 15 - $i);
        }
        
        return $decimalValue;
    }

    
    public function control_setting() {

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }



        $con_setting = [];
        $input_check = true;

        $get = function($key, $default = null) {
            return isset($_POST[$key]) && $_POST[$key] !== '' ? $_POST[$key] : $default;
        };

        // ===== 必填欄位（除了 ID 新舊外）=====
        $required_fields = ['control_name', 'storage_warning', 'torque_filter'];
        foreach ($required_fields as $field) {
            $val = $get($field);
            if ($val === null) {
                $input_check = false;
            } else {
                $con_setting[$field] = $val;
            }
        }

        // ===== 舊/新 ID 讀取（把空字串視為 NULL）=====
        $control_id_raw     = isset($_POST['control_id']) ? $_POST['control_id'] : null;
        $control_id_old     = ($control_id_raw === '' ? null : $control_id_raw);

        $control_id_new_raw = isset($_POST['control_id_new']) ? $_POST['control_id_new'] : $control_id_old;
        $control_id_new     = ($control_id_new_raw === '' ? null : $control_id_new_raw);

        // 寫回設定陣列（Model 需要）
        $con_setting['control_id']     = $control_id_old;   // WHERE 用
        $con_setting['control_id_new'] = $control_id_new;   // SET 用

        // ===== 可選欄位（含預設）=====
        $con_setting['lang_val'] = (int)$get('lang_val', 0);
        $con_setting['unit_val'] = (int)$get('unit_val', 0);

        $optional_fields = [
            'counting_method',
            'circular_archive',
            'blackout_recovery',
            'buzzer_mode',
            'global_downshift_torque',
            'global_downshift_speed'
        ];
        foreach ($optional_fields as $field) {
            $con_setting[$field] = $get($field, '');
        }

        // 基本數值型別正規化（選用：避免字串進 DB）
        foreach (['counting_method','circular_archive','blackout_recovery','buzzer_mode','lang_val','unit_val'] as $nf) {
            if (isset($con_setting[$nf]) && $con_setting[$nf] !== '') $con_setting[$nf] = (int)$con_setting[$nf];
        }
        foreach (['global_downshift_torque','global_downshift_speed','storage_warning','torque_filter'] as $nf) {
            if (isset($con_setting[$nf]) && $con_setting[$nf] !== '') $con_setting[$nf] = $con_setting[$nf] + 0;
        }

        if (!$input_check) {
            $res_type = $text['fail'] ?? 'Fail';
            $res_msg  = $text['form_invalid'] ?? 'Invalid input';
            $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
            return;
        }

        // ===== 舊 ID 存在性檢查（舊 ID 非 NULL 才檢查；NULL 交由 Model 用 IS NULL 去更新）=====
        $exists = true;
        if ($control_id_old !== null) {
            $res = $this->SettingModel->GetControllerInfo_count($control_id_old);
            $exists = ((int)($res['count'] ?? 0) === 1);
        }
        if (!$exists) {
            $res_type = $text['fail'] ?? 'Fail';
            $res_msg  = $text['not_found'] ?? 'Controller not found';
            $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
            return;
        }

        // ===== 如要更改 ID，檢查新 ID 是否已存在（新 ID 非 NULL 才檢查）=====
        $is_change_id = ($control_id_old !== $control_id_new);
        if ($is_change_id && $control_id_new !== null) {
            $resNew = $this->SettingModel->GetControllerInfo_count($control_id_new);
            if ((int)($resNew['count'] ?? 0) !== 0) {
                $res_type = $text['fail'] ?? 'Fail';
                $res_msg  = $text['device_id_conflict'] ?? 'Target device_id already exists';
                $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
                return;
            }
        }

        if($con_setting["blackout_recovery"] ==1){
            $con_setting["blackout_recovery"] = "1_1";
        }
        // ===== 執行更新（Model 需為先前已修改的版本：支援 :device_id_new，且 WHERE 可處理 IS NULL）=====
        $ok = $this->SettingModel->Controller_Setting($con_setting);

        if ($ok) {
            $res_type = $text['success'] ?? 'Success';
            $res_msg  = $text['success'] ?? 'Success';
        } else {
            $res_type = $text['fail'] ?? 'Fail';
            $res_msg  = $text['fail'] ?? 'Fail';
        }
        $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
    }





    public function edit_system_date() {

        if (PHP_OS_FAMILY == 'Linux') {
            $dateTime = $_POST["datetime"] ?? '';
            $dateTime = str_replace("T", " ", $dateTime); // YYYY-MM-DD HH:MM
    
            if (!preg_match("/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/", $dateTime)) {
                echo json_encode(['error' => '請提供有效的日期和時間格式（YYYY-MM-DD HH:MM）。']);
                exit;
            }
    
            exec("sudo timedatectl set-ntp no");
            $escapedDateTime = escapeshellarg($dateTime);
            $rr = exec("sudo date -s $escapedDateTime");
            exec("sudo hwclock --systohc");
    
            $this->logMessage("set date -s {$dateTime} " . ($rr !== false ? "success" : "fail"));
    
            echo json_encode(['error' => '', 'result' => $rr]);
        } else {
            echo json_encode(['error' => '非 Linux 系統無法設定時間']);
        }
        exit;
    }
    


    public function get_system_time(){
        
        header("Content-Type: text/plain; charset=utf-8");
        $output = shell_exec("date '+%Y-%m-%d %H:%M:%S'");
    
        echo trim($output);
    }

   


    public function FirmwareUpdate(){

        header('Content-Type: application/json; charset=utf-8');

        /* =============================
        * 1️⃣ 基本檢查
        * ============================= */
        if (empty($_FILES) || empty($_FILES['file'])) {
            echo json_encode(["error" => "no file"]);
            exit();
        }

        if (PHP_OS_FAMILY !== 'Linux') {
            echo json_encode(["error" => "not for windows"]);
            exit();
        }

        // 取得控制器 device id → Modbus unitId
        $device_id = isset($this->deviceId) ? (int)$this->deviceId : 1;
        $unitId = ($device_id >= 1 && $device_id <= 255) ? $device_id : 1;

        $this->logMessage('firmware update start');

        /* =============================
        * 上傳檔案到 FTP 資料夾
        * ============================= */
        $originalName = basename((string)$_FILES['file']['name']);

        if ($originalName === '') {
            echo json_encode(["error" => "invalid filename"]);
            exit();
        }

        $destination = "/mnt/ramdisk/ftp/" . $originalName;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
            $this->logMessage('upload fail');
            echo json_encode(["error" => "upload fail"]);
            exit();
        }

        /* =============================
        *  取檔名（去副檔名）→ ASCII 限制
        * ============================= */
        $filenameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);

        // 只允許 ASCII（控制器限制）
        if ($filenameWithoutExtension === '' || preg_match('/[^\x20-\x7E]/', $filenameWithoutExtension)) {
            echo json_encode(["error" => "filename must be ASCII"]);
            exit();
        }

        // 最多 20 bytes = 10 registers
        if (strlen($filenameWithoutExtension) > 20) {
            echo json_encode(["error" => "filename too long (max 20 chars)"]);
            exit();
        }

        /* =============================
        * ASCII → INT16 registers
        * ============================= */
        $name_int16 = $this->asciiToHexToInt_temp($filenameWithoutExtension);

        // 固定 10 registers（不足補0）
        $name_int16 = array_pad($name_int16, 10, 0);
        $name_int16 = array_slice($name_int16, 0, 10);

        /* =============================
        * 一次寫入 R480~R490 
        * ============================= */
        require_once __DIR__ . '/../../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $modbus = new ModbusMaster("127.0.0.1", "TCP");
        $modbus->port = 502;
        $modbus->timeout_sec = 10;

        try {

            // R480=1 + R481~R490 filename
            $data = array_merge([1], $name_int16);
            $types = array_fill(0, count($data), "INT");

            $modbus->writeMultipleRegister(
                $unitId,
                480,
                $data,
                $types
            );

            $this->logMessage(
                "write R480~R490 OK, filename={$filenameWithoutExtension}, unitId={$unitId}"
            );

            $this->logMessage('firmware update end');

            echo json_encode(["error" => ""]);
            exit();

        } catch (Exception $e) {
            $this->logMessage('modbus error status: ' . $modbus->status);
            $this->logMessage('firmware update end');
            echo json_encode(["error" => "modbus error"]);
            exit();
        }
    }




    public function asciiToHexToInt_temp(string $input): array{

        // ASCII → byte array
        $bytes = array_values(unpack('C*', $input));

        // 奇數 byte 補 0x00
        if (count($bytes) % 2 !== 0) {
            $bytes[] = 0;
        }

        $result = [];

        // 每兩個 byte → 一個 Modbus register
        for ($i = 0; $i < count($bytes); $i += 2) {
            $hi = $bytes[$i];
            $lo = $bytes[$i + 1];
            $result[] = ($hi << 8) | $lo;
        }

        return $result;
    }





    public function export_sysytem_config(){

        /* =====================================================
        * Platform check
        * ===================================================== */
        if (PHP_OS_FAMILY !== 'Linux') {
            http_response_code(400);
            echo json_encode(['error' => 'Only supported on Linux']);
            return;
        }

        /* =====================================================
        * Browser timestamp (client side preferred)
        * ===================================================== */
        $clientTs = (isset($_GET['client_ts']) && $_GET['client_ts'] !== '')
            ? preg_replace('/[^0-9]/', '', $_GET['client_ts'])
            : date('YmdHis');

        if (strlen($clientTs) !== 14) {
            $clientTs = date('YmdHis');
        }

        /* =====================================================
        * Basic info
        * ===================================================== */
        $controller_info = $this->SettingModel->GetControllerInfo();
        $sn = preg_replace('/[^A-Za-z0-9_\-]/', '_', $controller_info['device_sn'] ?? 'UNKNOWN');

        /* =====================================================
        * ZIP name
        * ===================================================== */
        $zipFileName = "NTCS_Config_{$sn}_{$clientTs}.zip";
        $zipPath     = "/mnt/ramdisk/ftp/{$zipFileName}";

        /* =====================================================
        * File names in ZIP
        * ===================================================== */
        $linNameInZip     = "con_{$sn}_{$clientTs}.Lin";
        $barcodeNameInZip = "bc_{$sn}_{$clientTs}.db";
        $logNameInZip     = "ntcs_log_{$clientTs}.csv";
        $syslogNameInZip  = "syslog_{$clientTs}.log";

        /* =====================================================
        * Source paths
        * ===================================================== */
        $srcLin     = "/home/kls/NTCS7/KLS_NTCS.Lin";
        $barcodeDb = "/var/www/html/database/ntcs_barcode_IDAS.db";
        $logCsv    = "/home/kls/NTCS7/ntcs_log.csv";

        // syslog（需 sudo）
        $syslogSrc = "/var/log/syslog";
        $syslogTmp = "/mnt/ramdisk/ftp/syslog_snapshot_{$clientTs}.log";

        /* =====================================================
        * Create ZIP
        * ===================================================== */
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->logMessage("Cannot create zip: {$zipPath}");
            echo json_encode(['error' => 'cannot create zip']);
            return;
        }

        /* =====================================================
        * Add files (best effort)
        * ===================================================== */

        // 1) LIN
        if (is_file($srcLin)) {
            $zip->addFile($srcLin, $linNameInZip);
        } else {
            $this->logMessage("LIN file not found: {$srcLin}");
        }

        // 2) Barcode DB
        if (is_file($barcodeDb)) {
            $zip->addFile($barcodeDb, $barcodeNameInZip);
        } else {
            $this->logMessage("Barcode DB not found: {$barcodeDb}");
        }

        // 3) Log CSV
        if (is_file($logCsv)) {
            $zip->addFile($logCsv, $logNameInZip);
        } else {
            $this->logMessage("Log CSV not found: {$logCsv}");
        }

        // 4) Syslog（整份抓下來，sudo cp + chmod）
        if (is_file($syslogSrc)) {

            $cmd = sprintf(
                'sudo /bin/cp %s %s && sudo /bin/chmod 644 %s 2>/dev/null',
                escapeshellarg($syslogSrc),
                escapeshellarg($syslogTmp),
                escapeshellarg($syslogTmp)
            );

            exec($cmd, $out, $ret);

            if ($ret === 0 && is_file($syslogTmp) && filesize($syslogTmp) > 0) {
                $zip->addFile($syslogTmp, $syslogNameInZip);
            } else {
                $this->logMessage("Syslog copy failed (sudo permission)");
            }
        }

        $zip->close();

        /* =====================================================
        * Send ZIP
        * ===================================================== */
        if (!is_file($zipPath)) {
            echo json_encode(['error' => 'zip not generated']);
            return;
        }

        header("Content-Type: application/zip");
        header('Content-Disposition: attachment; filename="' . rawurlencode($zipFileName) . '"');
        header("Content-Length: " . filesize($zipPath));
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");

        readfile($zipPath);

        /* =====================================================
        * Cleanup
        * ===================================================== */
        @unlink($syslogTmp);
        @unlink($zipPath);

        exit;
    }


    public function get_file_list($value='')
    {
        $year = date("Y");

        if( PHP_OS_FAMILY == 'Linux'){
            $folderPath = "/home/kls/tcc/resource/db_emmc/"; // 修改為你的資料夾路徑
        }else{
            $folderPath = "../"; // 修改為你的資料夾路徑
        }

        $excludeFiles = ["data.db", "tcsdev.db"]; // 要排除的檔案名稱 ,"data{$year}.db"
        $allowedExtensions = ["db"]; // 允許的附檔名


        $fileList = scandir($folderPath);
        // 過濾不要顯示的檔案
        $fileList = array_filter($fileList, function ($fileName) use ($excludeFiles) {
            return !in_array($fileName, $excludeFiles);
        });

        // 過濾只顯示符合條件的檔案
        $fileList = array_filter($fileList, function ($fileName) {
            // 檢查檔案名稱是否以 "data" 開頭且副檔名為 ".db"
            return (strpos($fileName, "data") === 0 && pathinfo($fileName, PATHINFO_EXTENSION) === "db");
        });


        $fileList = array_diff($fileList, array(".", "..")); // 移除 . 和 .. 條目
        echo json_encode(array_values($fileList));
    }



    //刪除鎖附記路的年份
    //取得年份後 用modbus 刪除
    public function delete_files(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }


        $del_year_id = $_POST['del_year_id'][0];
        if(empty($del_year_id)){
            echo json_encode([
                'result' => false,
                'res_type' => 'Error',
                'res_msg' => 'Tool not disabled'
            ]);
            return;
        }



        $temp_del_year = $del_year_id[0]; // 只處理第一筆

        // 檢查是否可以刪除（Modbus 狀態檢查）
        $device_id = isset($this->deviceId) ? (int)$this->deviceId : 1;
        $unitId = ($device_id >= 1 && $device_id <= 255) ? $device_id : 1;
        $idas_result = $this->idas_check( $unitId);

        if ($idas_result['result'] != 1) {
            echo json_encode([
                'result' => false,
                'res_type' => 'Error',
                'res_msg' => 'Tool not disabled'
            ]);
            return;
        }

        // 執行 Modbus 寫入刪除年份
        $controller_ip = CONTROLLER_IP;
        $year = array($temp_del_year);

        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
        $modbus = new ModbusMaster($controller_ip, "TCP");

        try {
            $modbus->port = 502;
            $modbus->timeout_sec = 10;
            $dataTypes = array("INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT");

            $modbus->writeMultipleRegister($device_id, 517, $year, $dataTypes);

            echo json_encode([
                'result' => true,
                'res_type' => 'Success',
                'res_msg' => $text['delete_text'].$text['success'] 
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'result' => false,
                'res_type' => 'Error',
                'res_msg' => $text['delete_text'].$text['fail'] 
            ]);
        }
    }

        


    public function firmware_update() //FTP 上傳檔案大小限制 : 500M
    {
        // code...
    }



    //DB匯入提醒判斷
    public function SyncCheck($value='')
    {
        // session_start();
        /*$this->language_auto(); //從瀏覽器帶入語系
        //multi language
        $language = array("language"=>$_SESSION['language']);
        // 如果檔案存在就引入它
        if(file_exists('../app/language/' . $language['language'] . '.php')){
            require_once '../app/language/' . $language['language'] . '.php';
        } else { //預設語系
            require_once '../app/language/en-us.php';
        }
        
        //C2D可以不判斷
        if ( isset($_GET["way"]) ) {
            $way = $_GET["way"];
        }else if ( isset($_POST["way"]) ) {
            $way = $_POST["way"];
        }

        // 1. filetime
        // 2. db version
        // 3. compare
        $notice = '';
        $warning = '';
        $Das_DB_Location = '/var/www/html/database/iDas-tcscon.db';
        $Con_DB_Location = '/var/www/html/database/tcscon.db';

        if($this->LoginCheck() == 1){
            echo json_encode(array('warning' => $text['system_sync_warning_login']));
            exit();
        }

        if($way == 'C2D'){
            echo json_encode( array('notice'=>'','warning'=>'') );
            exit();
        }

        if( PHP_OS_FAMILY == 'Linux' && $way == 'D2C'){

            //時間差異提醒
            if( filemtime($Con_DB_Location) > filemtime($Das_DB_Location) ){
                $notice = $text['system_sync_notice'].date("Y-m-d H:i:s.", filemtime($Con_DB_Location));
            }

            //DB版本差異判斷
            $C_DB_Version = $this->SettingModel->Get_Controller_DB_version();
            $Controller_Info = $this->SettingModel->GetControllerInfo();
            if ($Controller_Info['tcscondb_version'] < $C_DB_Version) {
                $warning = $text['system_sync_warning'];
            }

            //idas版本驗證 符合match_gtcs_app_version
            $match_gtcs_app_version = $this->AdminModel->Get_Das_Config('match_gtcs_app_version');
            $C_Device_Vesion = $this->SettingModel->Get_Controller_Device_version();
            if($match_gtcs_app_version != $C_Device_Vesion){
                $warning = 'APP Version Not Match';
            }

            //DB欄位差異判斷
            if(!$this->Database_Column_Diff()){
                $warning .= 'DB is different';
            }
            
            //資料是否有Null判斷
        }

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest' && $_SERVER['REQUEST_METHOD'] == 'GET' ) {
            // 这是一个外部的 AJAX 请求
            echo json_encode( array('notice'=>$notice,'warning'=>$warning) );
            exit();
        } else {
            // 这是内部调用
            return array('notice'=>$notice,'warning'=>$warning);
        }*/
        

    }

    /**
     * 將來源 DB 安全覆蓋到目的 DB（temp → atomic rename）
     * - 同目錄 temp 檔，rename 原子替換
     * - 自動備份舊檔 .bak_YYYYmmdd_HHMMSS
     * - 基本檢查：檔案存在、目錄可寫、size、sqlite integrity_check
     */
    private function replaceSqliteDbFile(
        string $srcDb,
        string $dstDb,
        bool $doBackup = true
    ): void {

        if (!is_file($srcDb) || !is_readable($srcDb)) {
            throw new Exception("Source DB not found or unreadable: {$srcDb}");
        }

        $dstDir = dirname($dstDb);
        if (!is_dir($dstDir) || !is_writable($dstDir)) {
            throw new Exception("Destination dir not writable: {$dstDir}");
        }

        // temp 必須在「同一個目的地資料夾」才能 atomic rename
        $tmpDb = $dstDir . '/.' . basename($dstDb) . '.tmp';

        // 1) copy → temp
        if (!@copy($srcDb, $tmpDb)) {
            $err = error_get_last();
            throw new Exception("Copy failed: " . ($err['message'] ?? 'unknown'));
        }

        @chmod($tmpDb, 0666);

        // 2) size sanity check（避免空檔）
        $srcSize = @filesize($srcDb);
        $tmpSize = @filesize($tmpDb);

        if ($srcSize === false || $tmpSize === false || $tmpSize < 1024 || $tmpSize !== $srcSize) {
            @unlink($tmpDb);
            throw new Exception("Size mismatch or too small (src={$srcSize}, tmp={$tmpSize})");
        }

        // 3) sqlite integrity_check（可抓到 copy 失敗/壞檔）
        $this->assertSqliteHealthy($tmpDb);

        // 4) 備份舊檔（可選，但我強烈建議開）
        if ($doBackup && is_file($dstDb)) {
            $bak = $dstDb . '.bak_' . date('Ymd_His');
            if (!@copy($dstDb, $bak)) {
                // 備份失敗不一定要中止，但建議中止以免無法回復
                @unlink($tmpDb);
                throw new Exception("Backup failed: {$bak}");
            }
            @chmod($bak, 0666);
        }

        // 5) atomic replace
        // rename 在同一個 filesystem 上通常是原子替換
        if (!@rename($tmpDb, $dstDb)) {
            $err = error_get_last();
            @unlink($tmpDb);
            throw new Exception("Rename failed: " . ($err['message'] ?? 'unknown'));
        }

        @chmod($dstDb, 0666);
    }

    /**
     * 檢查 sqlite 檔案是否健康（integrity_check）
     */
    private function assertSqliteHealthy(string $dbPath): void
    {
        try {
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // PRAGMA integrity_check 會回傳 'ok' 或錯誤描述
            $res = $db->query("PRAGMA integrity_check;")->fetchColumn();
            if (strtolower((string)$res) !== 'ok') {
                throw new Exception("SQLite integrity_check failed: " . (string)$res);
            }
        } catch (Exception $e) {
            throw new Exception("SQLite health check failed: " . $e->getMessage());
        }
    }

    
    public function Sync_check_db(){
        
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $argument = $_POST['argument'] ?? '';

        $src1         = '/var/www/html/database/KLS_NTCS_IDAS.Lin';
        $finalPath1   = '/mnt/ramdisk/ftp/11.Lin';
        $renamedPath1 = '/mnt/ramdisk/ftp/11_tmp.Lin';

        $src2         = '/var/www/html/database/ntcs_barcode_IDAS.db';
        $finalPath2   = '/mnt/ramdisk/ftp/11.db';
        $renamedPath2 = '/mnt/ramdisk/ftp/11_db_temp.db';

        $src3         = '/var/www/html/database/ntcs_device_IDAS.db';
        $dst3         = '/home/kls/NTCS7/ntcs_device.db';


        // 取得 正確的 Modbus id
        $device_id = isset($this->deviceId) ? (int)$this->deviceId : 1;
        $unitId = ($device_id >= 1 && $device_id <= 255) ? $device_id : 1;



        // 只處理 Linux + D2C，其它情況直接回錯誤
        if (PHP_OS_FAMILY !== 'Linux' || $argument !== 'D2C') {
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Invalid sync argument or unsupported OS');
        }

        // ✅ 先同步 device.db (src3 → dst3)
        if (file_exists($src3)) {
            if (!copy($src3, $dst3)) {
                $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to copy $src3 to $dst3");
            }
            @chmod($dst3, 0777);

            // 🔥 這支通常很肥，如非必要先關掉（如果你要加回來就把這行註解拿掉）
            $this->get_db_sync($unitId);
        }

        //  檢查原始檔案是否存在
        if (!file_exists($src1) || !file_exists($src2)) {
            $missingFiles = [];
            if (!file_exists($src1)) $missingFiles[] = 'KLS_NTCS_IDAS.Lin';
            if (!file_exists($src2)) $missingFiles[] = 'ntcs_barcode_IDAS.db';

            $this->MiscellaneousModel->generateErrorResponse(
                'Error',
                'Source file(s) missing: ' . implode(', ', $missingFiles)
            );
        }

        // 初始化 Modbus
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
        $modbus = new ModbusMaster("127.0.0.1", "TCP");
        $modbus->port        = 502;
        $modbus->timeout_sec = 2;   // 原本 10 → 3，這裡直接壓到 2 秒

        try {
            // ----------- Sync LIN File（簡化：直接 src → final → rename）-----------
            if (!$this->safeCopy($src1, $finalPath1)) {
                $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to copy $src1 to $finalPath1");
            }
            @chmod($finalPath1, 0777);
            $this->logMessage("$src1 copied to $finalPath1");

            // 通知控制器有新 LIN
            $this->notifyModbus($modbus, [1, 12593], "LIN");

            if (!$this->safeCopy($finalPath1, $renamedPath1)) {
                $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to rename LIN file");
            }
            @unlink($finalPath1);
            $this->logMessage("$finalPath1 renamed to $renamedPath1");

            // 🔥 拿掉 usleep(1_000_000) 不再強制多等 1 秒
            // usleep(1_000_000);

            // ----------- Sync DB File (barcode)（一樣簡化）-----------
            if (!$this->safeCopy($src2, $finalPath2)) {
                $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to copy $src2 to $finalPath2");
            }
            @chmod($finalPath2, 0777);
            $this->logMessage("$src2 copied to $finalPath2");

            // 通知控制器有新 DB
            $this->notifyModbus($modbus, [1, 12593], "DB");

            if (!$this->safeCopy($finalPath2, $renamedPath2)) {
                $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to rename DB file");
            }
            @unlink($finalPath2);
            $this->logMessage("$finalPath2 renamed to $renamedPath2");

            // ✅ 最後回傳成功訊息（純 JSON）
            $this->MiscellaneousModel->generateErrorResponse('Success', 'SYNC ' . ($text['success'] ?? 'success'));

        } catch (Exception $e) {
            $this->logMessage('Modbus write fail: ' . $e->getMessage());
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Modbus communication failed');
        }
    }









    public function Sync_check_db_load() {
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $argument = $_POST['argument'] ?? '';

        if (empty($argument) || PHP_OS_FAMILY !== 'Linux') {
            return $this->MiscellaneousModel->generateErrorResponse('Error', 'Invalid sync argument or unsupported OS');
        }

        // 定義來源與目的地檔案清單（Controller → iDAS）
        $fileList = [
            '/home/kls/NTCS7/KLS_NTCS.Lin'      => '/var/www/html/database/KLS_NTCS_IDAS.Lin',
            '/home/kls/NTCS7/ntcs_barcode.db'   => '/var/www/html/database/ntcs_barcode_IDAS.db',
            '/home/kls/NTCS7/ntcs_device.db'    => '/var/www/html/database/ntcs_device_IDAS.db',
            '/home/kls/NTCS7/ntcs_data.db'      => '/var/www/html/database/ntcs_data.db',
        ];

        if ($argument === 'C2D') {
            $copiedCount = 0;
            foreach ($fileList as $src => $dst) {
                if (file_exists($src)) {
                    if (copy($src, $dst)) {
                        $copiedCount++;

                        // 如果是特定檔案可額外執行後處理
                        if (basename($src) === 'KLS_NTCS.Lin') {
                            //$this->stepModel->get_success_data_by_step();
                        }
                    } else {
                        return $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to copy: $src → $dst");
                    }
                } else {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', "Source file not found: $src");
                }
            }

            return $this->MiscellaneousModel->generateErrorResponse(
                'Success',
                "SYNC" . ($text['success'] ?? 'success')
            );
        }

        // 預留其他參數（例如 D2C）
        return $this->MiscellaneousModel->generateErrorResponse('Error', 'Invalid sync argument');
    }

    /**
     * 安全複製檔案，若 copy 失敗會寫 log
     */
    private function safeCopy($src, $dst) {
        if (copy($src, $dst)) {
            $this->logMessage("Copied: $src -> $dst");
            return true;
        } else {
            $this->logMessage("Failed copy: $src -> $dst");
            return false;
        }
    }

    /**
     * 發送 Modbus 訊號
     */
    private function notifyModbus($modbus, $data, $tag = ""){
        
        // 取得控制器資訊
        $controller_info = (array)($this->SettingModel->GetControllerInfo() ?? []);

        // 正確拿出 device_id
        $device_id = isset($controller_info['device_id'])
            ? (int)$controller_info['device_id']
            : 1;   // 沒抓到就先用 1

        // Modbus slave ID 合理範圍通常是  1~255
        $unitId = $device_id;
        if ($unitId < 1 || $unitId > 255) {
            $unitId = 1; 
        }

        $dataTypes = array_fill(0, 16, 'INT');
        $payload   = array_merge($data, array_fill(0, 16 - count($data), 0));

        $modbus->writeMultipleRegister($unitId, 506, $payload, $dataTypes);
        $this->logMessage("Modbus write ($tag) [unitId=$unitId]: " . implode(',', $payload));
    }




    
    //get barcode
    public function GetBarcodes(){

        $barcodes = $this->SettingModel->GetAllBarcodes();
        return $barcodes;
    }


    public function show_Barcodes(){
        
        $barcodes = $this->SettingModel->GetAllBarcodes();
        $barcode_mode = $this->MiscellaneousModel->details('barcode_mode');

        if (empty($barcodes)) {
            return;
        }

        foreach ($barcodes as $k => $v) {

            echo '<tr style="text-align:center; vertical-align:middle;">';

            echo '<td>
                <input
                    class="form-check-input barcode-check"
                    type="checkbox"
                    name="barcode_check"
                    id="barcode_check_' . (int)$v['job_id'] . '_' . $k . '"
                    value="1"
                    data-job-id="' . (int)$v['job_id'] . '"
                    data-job-name="' . htmlspecialchars($v['JOBname'], ENT_QUOTES) . '"
                    data-barcode="' . htmlspecialchars($v['barcode'], ENT_QUOTES) . '"
                    data-range-from="' . (int)$v['range_from'] . '"
                    data-range-count="' . (int)$v['range_count'] . '"
                    data-barcode-mode="' . (int)$v['barcode_mode'] . '"
                    data-seq-id="' . (isset($v['seq_id']) ? (int)$v['seq_id'] : -1) . '"
                    style="zoom:1.2">
            </td>';

            echo '<td>' . (int)$v['job_id'] . '</td>';
            echo '<td>' . htmlspecialchars($v['JOBname'], ENT_QUOTES) . '</td>';
            echo '<td>' . htmlspecialchars($v['barcode'], ENT_QUOTES) . '</td>';
            echo '<td>' . (int)$v['range_from'] . '</td>';
            echo '<td>' . (int)$v['range_count'] . '</td>';
            echo '<td>' . htmlspecialchars($barcode_mode[$v['barcode_mode']] ?? '', ENT_QUOTES) . '</td>';

            echo '</tr>';
        }
    }



    public function Update_Barcode(){
        
        /* ===============================
        * 語系載入
        * =============================== */
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        header('Content-Type: application/json; charset=utf-8');

        /* ===============================
        * 欄位定義
        * =============================== */
        $fields = [
            'barcode_name'      => ['key' => 'barcode_name',        'required' => true],
            'barcode_from'      => ['key' => 'barcode_range_from',  'required' => true],
            'barcode_count'     => ['key' => 'barcode_range_count', 'required' => true],
            'barcode_job'       => ['key' => 'barcode_job',         'required' => true],
            'barcode_job_old'   => ['key' => 'barcode_job_old',     'required' => false], // ⭐ 判斷編輯用
            'barcode_mode'      => ['key' => 'barcode_mode',        'required' => true],
            'barcode_seq'       => ['key' => 'barcode_seq',         'required' => false],
        ];

        $barcode = [];
        $input_check = true;

        foreach ($fields as $post_key => $map) {
            if (isset($_POST[$post_key]) && ($_POST[$post_key] !== '' || !$map['required'])) {
                $barcode[$map['key']] = $_POST[$post_key];
            } elseif ($map['required']) {
                $input_check = false;
            } else {
                $barcode[$map['key']] = '';
            }
        }

        /* ===============================
        * 正規化欄位
        * =============================== */
        if (($barcode['barcode_seq'] ?? '') === '-1') {
            $barcode['barcode_seq'] = '';
        }

        if (($barcode['barcode_job_old'] ?? '') === '') {
            $barcode['barcode_job_old'] = '';
        }

        /* ===============================
        * 欄位驗證失敗
        * =============================== */
        if (!$input_check) {
            echo json_encode([
                'res_type' => 'error',
                'res_msg'  => $text['input_error'] ?? 'Required fields missing.'
            ]);
            exit;
        }

        /* =================================================
        * ⭐ 是否為編輯模式（重點）
        * - 只看是否有 barcode_job_old
        * - 不再用 job_id 查 DB（避免改 job 造成誤判）
        * ================================================= */
        $isEdit = !empty($barcode['barcode_job_old']);

        /* ===============================
        * 儲存（Model 內部處理 delete + insert）
        * =============================== */
        $ok = $this->SettingModel->Update_Barcode($barcode);

        /* ===============================
        * 回傳結果
        * =============================== */
        if ($ok) {
            $msg = $isEdit
                ? sprintf(
                    $text['barcode_edit_success'] ?? 'Barcode "%s" updated successfully.',
                    $barcode['barcode_name']
                )
                : sprintf(
                    $text['barcode_add_success'] ?? 'Barcode "%s" created successfully.',
                    $barcode['barcode_name']
                );

            echo json_encode([
                'res_type' => 'info',
                'res_msg'  => $msg
            ]);
        } else {
            $msg = $isEdit
                ? sprintf(
                    $text['barcode_edit_fail'] ?? 'Failed to update barcode "%s".',
                    $barcode['barcode_name']
                )
                : sprintf(
                    $text['barcode_add_fail'] ?? 'Failed to create barcode "%s".',
                    $barcode['barcode_name']
                );

            echo json_encode([
                'res_type' => 'error',
                'res_msg'  => $msg
            ]);
        }

        exit;
    }







    public function GetJobSeq(){

        $job_id = $_POST['job_id'] ?? null;

        if ($job_id) {
            $result = $this->SettingModel->get_seq_list($job_id);
            echo json_encode($result);
        } else {
            echo json_encode([
                'result' => 'fail',
                'error_message' => 'Missing job_id'
            ]);
        }
        exit();
    }

        
    public function GetJobSeq_for_modbus(){

        $input_check = true;
        $error_message = '';
        
        if( !empty($_GET['job_id']) && isset($_GET['job_id'])  ){
            $job_id = $_GET['job_id'];
        }else{ 
            $input_check = false;
            $error_message .= "job_id,";
        }

        if($input_check){
            $result = $this->SettingModel->get_seq_list_for_modbus($job_id);
            echo json_encode($result);
            exit();
        }else{
            $data = [
                'result' => 'fail',
                'error_message' => $error_message
            ];
            echo json_encode($data);
            exit();
        }


    }



    public function GetJobBarcode(){

        $input_check = true;
        $error_message = '';
        if( !empty($_GET['job_id']) && isset($_GET['job_id'])  ){
            $job_id = $_GET['job_id'];
        }else{ 
            $input_check = false;
            $error_message .= "job_id,";
        }

        if($input_check){
            $result = $this->SettingModel->e($job_id);
            echo json_encode($result);
            exit();
        }else{
            $data = [
                'result' => 'fail',
                'error_message' => $error_message
            ];
            echo json_encode($data);
            exit();
        }
    }

    public function delete_barcodes(){

        /* ===============================
        * 語系載入
        * =============================== */
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        header('Content-Type: application/json; charset=utf-8');

        // 前端傳 array
        $jobIds = $_POST['job_id'] ?? [];

        if (!is_array($jobIds) || empty($jobIds)) {
            echo json_encode([
                'res_type' => 'error',
                'res_msg'  => $text['barcode_delete_no_select'] ?? 'Please select at least one barcode.'
            ]);
            exit;
        }

        // ⭐ 一次刪（交給 Model 處理正規化）
        $affectedTotal = (int)$this->SettingModel->delete_barcodes_by_jobs($jobIds);

        if ($affectedTotal > 0) {
            echo json_encode([
                'res_type' => 'info',
                'res_msg'  => sprintf(
                    $text['barcode_delete_success'] ?? 'Deleted %d barcode(s).',
                    $affectedTotal
                )
            ]);
            exit;
        }

        echo json_encode([
            'res_type' => 'error',
            'res_msg'  => $text['barcode_delete_not_found'] ?? 'No barcode found to delete.'
        ]);
        exit;
    }



    #IDAS上傳 20250624 修改
    public function iDas_Update($debug = false) {
        
        // 1. 紀錄上傳限制
        $maxUpload = ini_get('upload_max_filesize');
        $postMax   = ini_get('post_max_size');
        error_log("[iDAS UPDATE] upload_max_filesize: $maxUpload");
        error_log("[iDAS UPDATE] post_max_size: $postMax");

        // 2. 載入語系檔（保留你原本機制）
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        // 3. 當前版本
        $iDas_Version = $this->AdminModel->Get_Das_Config('idas_version');

        // 4. 設定路徑
        $file_location = (PHP_OS_FAMILY === 'Linux') ? '/var/www/html/' : $_SERVER['DOCUMENT_ROOT'] . '/';
        $extract_path  = $file_location . 'extracted/';
        $main_folder   = '';

        // 上傳大小上限（訊息顯示用）
        $MAX_SIZE     = 30 * 1024 * 1024;
        $limitText    = '30MB';

        try {
            // 5. 驗證上傳
            if (empty($_FILES['file'])) {
                return $this->sendResponse('Error', $this->t('ERR_NO_FILE'));
            }
            if ($_FILES['file']['error'] !== 0) {
                return $this->sendResponse('Error', $this->t('ERR_UPLOAD_ERROR', ['code' => (string)$_FILES['file']['error']]));
            }

            // 6. 大小限制
            if ($_FILES['file']['size'] > $MAX_SIZE) {
                return $this->sendResponse('Error', $this->t('ERR_SIZE_LIMIT', ['limit' => $limitText]));
            }

            // 7. 副檔名檢查
            $uploaded_filename = $_FILES['file']['name'];
            if (strtolower(pathinfo($uploaded_filename, PATHINFO_EXTENSION)) !== 'pack') {
                return $this->sendResponse('Error', $this->t('ERR_EXT', ['filename' => $uploaded_filename]));
            }

            // 8. 解壓縮
            $zip = new ZipArchive();
            if ($zip->open($_FILES['file']['tmp_name']) !== TRUE) {
                return $this->sendResponse('Error', $this->t('ERR_OPEN_PACK'));
            }

            if (!is_dir($extract_path)) mkdir($extract_path, 0777, true);
            if (!$zip->extractTo($extract_path)) {
                $zip->close();
                return $this->sendResponse('Error', $this->t('ERR_EXTRACT'));
            }
            $zip->close();

            // 9. 找資料夾
            $folders = array_filter(scandir($extract_path), fn($f) => is_dir($extract_path . $f) && !in_array($f, ['.', '..']));
            if (empty($folders)) {
                return $this->sendResponse('Error', $this->t('ERR_NO_FOLDER'));
            }

            $main_folder = $extract_path . reset($folders);

            // Debug：列出結構
            if ($debug) {
                error_log("[iDAS UPDATE] 解壓縮目錄結構：");
                $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($main_folder));
                foreach ($rii as $file) {
                    if (!$file->isDir()) error_log($file->getPathname());
                }
            }

            // 10. 解析 info.json
            $info_json_url = $main_folder . "/info.json";
            if (!file_exists($info_json_url)) {
                return $this->sendResponse('Error', $this->t('ERR_MISSING_INFO'));
            }

            $verify_data = json_decode(@file_get_contents($info_json_url), true);
            if (!$verify_data || !isset($verify_data['idas_version'])) {
                return $this->sendResponse('Error', $this->t('ERR_BAD_INFO'));
            }

            // 11. 比對版本
            $match_tcc_version = $verify_data['idas_version'];
            if (version_compare($match_tcc_version, $iDas_Version, '<')) {
                return $this->sendResponse('Error', $this->t('ERR_VERSION_LOW', [
                    'current' => (string)$iDas_Version,
                    'update'  => (string)$match_tcc_version,
                ]));
            }

            // 12. 寫入 config 表
            $this->AdminModel->Set_Das_Config('idas_version', $verify_data['idas_version']);

            /* =====================================================
            ⭐ 安全原子部署（無備份版）
            不會 0KB、不會 ghost file、不會半更新
            ===================================================== */

            $root = $_SERVER['DOCUMENT_ROOT'] . '/';
            $target_directory = $root . 'idas/';
            $staging_directory = $root . 'idas_new/';
            $old_directory = $root . 'idas_old/';

            // 1️⃣ 清 staging
            if (is_dir($staging_directory)) {
                $this->deleteDirectory($staging_directory);
            }

            // 2️⃣ 複製新版本到 staging（安全區）
            $this->copyDirectory($main_folder, $staging_directory);

            // 3️⃣ 刪舊 old（避免堆積）
            if (is_dir($old_directory)) {
                $this->deleteDirectory($old_directory);
            }

            // 4️⃣ 舊版 → old（瞬間完成，不會影響正在執行的 Apache）
            if (is_dir($target_directory)) {
                rename($target_directory, $old_directory);
            }

            // 5️⃣ 新版 → 正式上線（瞬間完成）
            rename($staging_directory, $target_directory);

            // 6️⃣ 現在才刪 old（此時 Apache 已完全切換）
            if (is_dir($old_directory)) {
                $this->deleteDirectory($old_directory);
            }


            sleep(1);
            exec("sync");//強制將ram寫回硬碟，避免控制器馬上關機時會遺失資料
            sleep(1);


            // 13. 清除舊 ntcs_idas 資料夾（若存在）
            $legacy_directory = $root . 'ntcs_idas/';

            if (is_dir($legacy_directory)) {
                $this->deleteDirectory($legacy_directory);
                clearstatcache(true, $legacy_directory);

                if (is_dir($legacy_directory)) {
                    error_log("[iDAS UPDATE] remove legacy folder failed: {$legacy_directory}");
                    return $this->sendResponse('Error', "Remove old folder failed: ntcs_idas");
                }

                error_log("[iDAS UPDATE] legacy folder removed: {$legacy_directory}");
            }


 


            // 14. 登出使用者
            $this->setting_logout();

            // Debug：保留 extracted
            if ($debug) {
                return $this->sendResponse('Success', $this->t('SUC_DEBUG'));
            }

            return $this->sendResponse('Success', $this->t('SUC_OK'));

        } finally {
            // 非 Debug 才刪暫存
            if (!$debug) {
                if (!empty($main_folder) && is_dir($main_folder)) $this->deleteDirectory($main_folder);
                if (is_dir($extract_path)) $this->deleteDirectory($extract_path);
            }
        }
    }


    private function cleanupOldBackups($root){
        
        $dirs = glob($root . 'ntcs_idas_backup_*', GLOB_ONLYDIR);
        if (!$dirs) return;

        foreach ($dirs as $dir) {
            $this->deleteDirectory($dir);
        }
    }




    // === 語系工具（改用 en-us） ===
    private function currentLang(): string {
        // 先看 cookie，再看 Accept-Language，最後預設 en-us
        $raw = strtolower($_COOKIE['language'] ?? ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));

        // 繁中
        if (strpos($raw, 'zh-tw') === 0 || strpos($raw, 'zh-hant') === 0) return 'zh-tw';

        // 簡中
        if (strpos($raw, 'zh-cn') === 0 || strpos($raw, 'zh-hans') === 0 || strpos($raw, 'zh') === 0) return 'zh-cn';

        // 英文（含 en-us / en → 一律正規化成 en-us）
        if (strpos($raw, 'en-us') === 0 || strpos($raw, 'en') === 0) return 'en-us';

        return 'en-us';
    }

    private function t(string $key, array $vars = []): string {
        static $DICT = [
            'ERR_NO_FILE'       => [
                'en-us' => 'No file uploaded.',
                'zh-tw' => '未上傳檔案。',
                'zh-cn' => '未上传文件。',
            ],
            'ERR_UPLOAD_ERROR'  => [
                'en-us' => 'File upload error (code: {code}).',
                'zh-tw' => '檔案上傳錯誤（代碼：{code}）。',
                'zh-cn' => '文件上传错误（代码：{code}）。',
            ],
            'ERR_SIZE_LIMIT'    => [
                'en-us' => 'File exceeds the size limit: {limit}.',
                'zh-tw' => '檔案大小超過限制：{limit}。',
                'zh-cn' => '文件大小超过限制：{limit}。',
            ],
            'ERR_EXT'           => [
                'en-us' => 'Uploaded file must be .pack (got: {filename}).',
                'zh-tw' => '上傳檔案必須為 .pack（目前為：{filename}）。',
                'zh-cn' => '上传文件必须为 .pack（当前为：{filename}）。',
            ],
            'ERR_OPEN_PACK'     => [
                'en-us' => 'Unable to open the .pack update file.',
                'zh-tw' => '無法開啟 .pack 更新檔案。',
                'zh-cn' => '无法打开 .pack 更新文件。',
            ],
            'ERR_EXTRACT'       => [
                'en-us' => 'Failed to extract the update package.',
                'zh-tw' => '解壓縮失敗。',
                'zh-cn' => '解压缩失败。',
            ],
            'ERR_NO_FOLDER'     => [
                'en-us' => 'No extracted folder found.',
                'zh-tw' => '未找到解壓縮資料夾。',
                'zh-cn' => '未找到解压缩文件夹。',
            ],
            'ERR_MISSING_INFO'  => [
                'en-us' => 'Missing info.json; cannot verify the update package.',
                'zh-tw' => '缺少 info.json，無法驗證更新檔。',
                'zh-cn' => '缺少 info.json，无法验证更新包。',
            ],
            'ERR_BAD_INFO'      => [
                'en-us' => 'Invalid info.json or missing "idas_version".',
                'zh-tw' => 'info.json 格式錯誤或缺少 idas_version。',
                'zh-cn' => 'info.json 格式错误或缺少 idas_version。',
            ],
            'ERR_VERSION_LOW'   => [
                'en-us' => 'Update version is lower than current (current: {current}, update: {update}).',
                'zh-tw' => '更新檔版本低於目前版本，無法更新（目前：{current}，更新：{update}）。',
                'zh-cn' => '更新包版本低于当前版本，无法更新（当前：{current}，更新：{update}）。',
            ],
            'SUC_DEBUG'         => [
                'en-us' => 'Update successful (Debug mode: extracted folder retained).',
                'zh-tw' => '更新成功（Debug模式：保留 extracted 資料夾）。',
                'zh-cn' => '更新成功（调试模式：保留 extracted 文件夹）。',
            ],
            'SUC_OK'            => [
                'en-us' => 'Update successful. Files have been moved to the "idas" directory.',
                'zh-tw' => '更新成功，已將檔案移動至 idas 目錄。',
                'zh-cn' => '更新成功，已将文件移动至 idas 目录。',
            ],
        ];

        $lang = $this->currentLang();
        $msg  = $DICT[$key][$lang] ?? ($DICT[$key]['en-us'] ?? $key);

        if ($vars) {
            $repl = [];
            foreach ($vars as $k => $v) $repl['{'.$k.'}'] = $v;
            $msg = strtr($msg, $repl);
        }
        return $msg;
    }

    
    private function sendResponse($type, $msg) {
        $this->MiscellaneousModel->generateErrorResponse($type, $msg);
        exit();
    }

    private function copyDirectory($source, $destination) {
        if (!is_dir($destination)) mkdir($destination, 0777, true);
        foreach (scandir($source) as $file) {
            if (!in_array($file, ['.', '..'])) {
                $src = $source . '/' . $file;
                $dst = $destination . '/' . $file;
                if (is_dir($src)) {
                    $this->copyDirectory($src, $dst);
                } else {
                    if (file_exists($dst)) unlink($dst);
                    copy($src, $dst);
                }
            }
        }
    }

    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $file) {
            if (!in_array($file, ['.', '..'])) {
                $path = $dir . '/' . $file;
                is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
            }
        }
        rmdir($dir);
    }



    public function Extract_File($file_location,$filename)
    {
        // $filename = 'update_package.pack';
        $zip = new ZipArchive;

        if($zip->open($file_location.''.$filename)===TRUE){
            if($zip->setPassword('Vfxh]QaXxZF-eT1L9b@%pJ-F#U>]95Fr_9GQf5]KtZRhXiHXJ-6QW86.gXQdp9yEZK@fxVF!WJ>PXMdK]>eeh*_-=0')){
                $res = $zip->extractTo($file_location.'package_temp/'); //避免覆蓋，將解壓縮資料放進該資料夾
                $zip->close();
                return $res;
            }else{
                return false;
            }
            // echo "解壓縮完成";
        }else{
            return false;
        }

    }

    public function copyFolder($source, $destination) {
        if (is_dir($source)) {
            @mkdir($destination);
            
            $directory = dir($source);

            while (false !== ($entry = $directory->read())) {
                if ($entry == '.' || $entry == '..') {
                    continue;
                }

                if (is_dir("$source/$entry")) {
                    $this->copyFolder("$source/$entry", "$destination/$entry");
                    continue;
                }

                copy("$source/$entry", "$destination/$entry");
            }

            $directory->close();
        } else {
            copy($source, $destination);
        }
    }

    public function deleteFolder($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->deleteFolder($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public function edit_global_downshift(){

        // 載入語系
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        $fields = ['global_downshift_torque', 'global_downshift_speed'];
        $global_downshift_arr = [];
        $input_check = true;

        foreach ($fields as $field) {
            $value = trim($_POST[$field] ?? '');
            if ($value === '') {
                $input_check = false;
            }
            $global_downshift_arr[$field] = $value;
        }

        if ($input_check) {
            $result = $this->SettingModel->edit_feature_global_downshift($global_downshift_arr);

            $res_type = $result ? 'Success' : 'Error';
            $res_msg  = $result ? ($text['success'] ?? 'Operation succeeded.') : ($text['fail'] ?? 'Operation failed.');
            $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
        } else {
            $this->MiscellaneousModel->generateErrorResponse('Error', $text['input_error'] ?? 'Required fields missing.');
        }
    }



    public function edit_background_color(){

        // 載入語系
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        // 欄位定義
        $fields = ['okjobcolor', 'okseqcolor'];
        $color_arr = [];
        $input_valid = true;

        foreach ($fields as $field) {
            $value = trim($_POST[$field] ?? '');
            if ($value === '') {
                $input_valid = false;
            }
            $color_arr[$field] = $value;
        }

        if (!$input_valid) {
            $this->MiscellaneousModel->generateErrorResponse('Error', $text['input_error'] ?? 'Missing required color values.');
            return;
        }

        $result = $this->SettingModel->edit_feature_color($color_arr);
        $res_type = $result ? 'Success' : 'Error';
        $res_msg  = $result ? ($text['success'] ?? 'Update successful.') : ($text['fail'] ?? 'Update failed.');
        $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
    }


    public function edit_feature_pwd() {
        
        // 載入語系檔
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $pwd_arr = array();

        $pwd_arr = [
            'clear_seq' => $_POST['clear_seq'],
            'clear'     => $_POST['clear'],
            'confirm'   => $_POST['confirm'],
            'enable'    => $_POST['enable'],
            'disable'   => $_POST['disable'],
            'skip'      => $_POST['skip'],
        ];
        $result = $this->SettingModel->edit_feature_pwd($pwd_arr);

        if ($result) {
            $this->MiscellaneousModel->generateErrorResponse('Success', $text['success'] ?? 'Saved successfully.');
        } else {
            $this->MiscellaneousModel->generateErrorResponse('Error', $text['fail'] ?? 'Save failed.');
        }
    }


    public function Import_Config(){
        
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        // 取得Modbus的uid
        $device_id = isset($this->deviceId) ? (int)$this->deviceId : 1; 
        $unitId = ($device_id >= 1 && $device_id <= 255) ? $device_id : 1;

        // 驗證上傳
        if (empty($_FILES) || !isset($_FILES['file'])) {
            $this->MiscellaneousModel->generateErrorResponse('Error', 'No file uploaded.');
            return;  // 使用 return 代替 exit()，避免中斷執行
        }

        $file_name = $_FILES['file']['name'];
        $file_info = pathinfo($file_name);
        $ext = strtolower($file_info['extension']);
        $tmp_file = $_FILES['file']['tmp_name'];
        $ftp_dir = "/mnt/ramdisk/";

        // 驗證上傳文件是否為 .pack 格式
        if ($ext !== 'pack') {
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Only .pack files are allowed.');
            return; // 使用 return 代替 exit()
        }

        // 確保目錄存在且可寫
        if (!is_dir($ftp_dir) || !is_writable($ftp_dir)) {
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Upload directory not writable.');
            return; // 使用 return 代替 exit()
        }

        // 儲存 .pack 文件並解壓縮
        $tempZipPath = $ftp_dir . "uploaded_tmp.zip";
        if (!move_uploaded_file($tmp_file, $tempZipPath)) {
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Failed to save uploaded file.');
            return; // 使用 return 代替 exit()
        }

        // 解壓縮檔案
        $zip = new ZipArchive();
        if ($zip->open($tempZipPath) === TRUE) {
            $zip->extractTo($ftp_dir);
            $zip->close();
            unlink($tempZipPath);  // 刪除臨時 zip 檔案
        } else {
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Failed to extract .pack file.');
            return; // 使用 return 代替 exit()
        }

        // 尋找 .cfg 和 .Lin 文件
        $cfg_file = '';
        $lin_file = '';
        foreach (scandir($ftp_dir) as $f) {
            if (preg_match('/\.cfg$/i', $f)) {
                $cfg_file = $f;
            } elseif (preg_match('/\.lin$/i', $f)) {
                $lin_file = $f;
            }
        }

        // 檢查是否找到所需的檔案
        if (!$cfg_file || !$lin_file) {
            $this->MiscellaneousModel->generateErrorResponse('Error', '.cfg or .Lin file not found in .pack.');
            return; // 使用 return 代替 exit()
        }

        // 重新命名文件
        $cfg_path = $ftp_dir . "/ftp/iDas.cfg";
        $lin_path = $ftp_dir . "/ftp/iDas.Lin";
        @rename($ftp_dir . $cfg_file, $cfg_path);
        @rename($ftp_dir . $lin_file, $lin_path);

        // 執行 Modbus 寫入
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
        $modbus = new ModbusMaster("127.0.0.1", "TCP");

        try {
            $modbus->port = 502;
            $modbus->timeout_sec = 10;
            $data = [1, 26948, 24947]; // iDas
            $dataTypes = array_fill(0, 16, "INT");

            $modbus->writeMultipleRegister($unitId, 506, $data, $dataTypes);
            $this->logMessage("modbus write 506 ,array = " . implode("','", $data));
            $this->logMessage("modbus status: " . $modbus->status);
            $this->logMessage("Import config end");

            // 成功回應
            $this->MiscellaneousModel->generateErrorResponse('Success', 'Import successful.');

            // 第二次寫入 Modbus
            $modbus->writeMultipleRegister($unitId, 462, [1], $dataTypes);


            //重啟控制器 
            //$modbus->writeMultipleRegister(0, 462, array(1), $dataTypes);

        } catch (Exception $e) {
            // 錯誤處理
            $this->logMessage('modbus write 506 fail');
            $this->logMessage('modbus status: ' . $modbus->status);
            $this->logMessage('Import config end');
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Modbus error.');
        }
    }

    //DB欄位差異判斷
    public function Database_Column_Diff($dbPath1, $dbPath2){

        // 比對兩個資料庫的表結構
        if ($this->validateTableStructure($dbPath1, $dbPath2)) {
            $this->logMessage("✔ 資料庫結構相同：$dbPath1 vs $dbPath2");
        } else {
            $this->logMessage("✘ 資料庫結構不同：$dbPath1 vs $dbPath2");
            return false;
        }

        // 確認第一個 DB 沒有 null 欄位
        $result = $this->checkForNullValues($dbPath1);
        if (!$result) {
            $this->logMessage("✘ 檢查 $dbPath1 時發現欄位為 NULL");
            return false;
        }

        return true;
    }


    // 連接到SQLite資料庫
    function connectToSQLite($dbPath) {
         try {
             $pdo = new PDO("sqlite:$dbPath");
             $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
             return $pdo;
         } catch (PDOException $e) {
             echo "連線到資料庫失敗: " . $e->getMessage();
             return null;
         }
    }

    // 驗證兩個SQLite資料庫中所有表格的列數和列名是否相同
    function validateTableStructure($dbPath1, $dbPath2) {
         $pdo1 = $this->connectToSQLite($dbPath1);
         $pdo2 = $this->connectToSQLite($dbPath2);

         if (!$pdo1 || !$pdo2) {
             return false;
         }

         $tables1 = $this->getTablesInfo($pdo1);
         $tables2 = $this->getTablesInfo($pdo2);

         if (count($tables1) !== count($tables2)) {
             return false;
         }

         foreach ($tables1 as $table => $columns1) {
             if (!isset($tables2[$table])) {
                 return false;
             }

             $columns2 = $tables2[$table];
             if ($columns1 !== $columns2) {
                 return false;
             }
         }

         return true;
    }

    // 取得資料庫中所有表格的列數和列名
    function getTablesInfo($pdo) {
         $tables = array();

         $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
         $tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

         foreach ($tableNames as $tableName) {
             $stmt = $pdo->query("PRAGMA table_info('$tableName')");
             $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
             $tables[$tableName] = array_map(function($column) {
                 return $column['name'];
             }, $columns);
         }

         return $tables;
    }

    // 檢查SQLite資料庫中所有表格的欄位是否有NULL值
    function checkForNullValues($dbPath) {
         $pdo = $this->connectToSQLite($dbPath);

         if (!$pdo) {
             return false;
         }

         $tables = $this->getTablesInfo($pdo);

         foreach ($tables as $tableName => $columns) {
             foreach ($columns as $column) {
                 $stmt = $pdo->query("SELECT COUNT(*) FROM $tableName WHERE $column IS NULL");
                 $rowCount = $stmt->fetchColumn();
                 if ($rowCount > 0) {
                     // echo "在表 $tableName 的欄位 $column 中發現了 NULL 值。\n";
                     return false;
                 }
             }
         }

         return true;
    } 

    public function setting_logout() {

        // 1. 啟動 session（若尚未啟動）
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 2. 清空 session 資料
        $_SESSION = [];

        // 3. 取得 session cookie 參數
        $params = session_get_cookie_params();

        // 4. 刪除 session cookie
        if (ini_get('session.use_cookies')) {
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );

            // 補一組通用刪除
            setcookie(session_name(), '', time() - 42000, '/');
        }

        // 5. 刪除其他 cookie
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $host = preg_replace('/:\d+$/', '', $host); // 移除 port

        foreach ($_COOKIE as $key => $value) {
            // 基本刪除
            setcookie($key, '', time() - 3600, '/');

            // 帶 host 刪除
            if (!empty($host)) {
                setcookie($key, '', time() - 3600, '/', $host);
            }

            // 空 domain 再補一次
            setcookie($key, '', time() - 3600, '/', '', false, false);

            // 當前請求中同步移除
            unset($_COOKIE[$key]);
        }

        // 6. 銷毀 session
        session_destroy();
        session_write_close();

        // 7. 禁止快取
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        error_log('[iDAS UPDATE] user session destroyed and cookies cleared');
    }


    
    public function get_controller_login() {

        header('Content-Type: application/json; charset=utf-8');

        // 取得語系
        $language = $_COOKIE['language'] ?? 'en-us';

        // 多語系訊息設定
        $messages = [
            'en-us' => [
                'already_logged_in' => 'Controller is already logged in',
                'login_status'      => 'Login status returned'
            ],
            'zh-tw' => [
                'already_logged_in' => '控制器已經登入',
                'login_status'      => '登入狀態已返回'
            ],
            'zh-cn' => [
                'already_logged_in' => '控制器已经登录',
                'login_status'      => '登录状态已返回'
            ]
        ];

        // 找不到對應語言時，預設 en-us
        $msg = $messages[strtolower($language)] ?? $messages['en-us'];

        // 取得控制器 SN（DB）
        $controller_info = (array)($this->SettingModel->GetControllerInfo() ?? []);
        $device_sn_raw   = (string)($controller_info['device_sn'] ?? '');
        $device_sn       = preg_replace('/[^A-Za-z0-9_\-]/', '_', $device_sn_raw);

        // ✅ 檢查是否可同步（Modbus 工具狀態）
        $device_id = isset($this->deviceId)
            ? (int)$this->deviceId
            : 1; // 防呆，沒有就給 1 (依你實際情況調整)

        $idas_result = $this->idas_check($device_id);

        // 防呆，避免 idas_check 回傳 null 或其他型別
        if (!is_array($idas_result)) {
            $idas_result = ['result' => null, 'error' => 'idas_check 回傳異常'];
        }

        $regVal  = $idas_result['result']; // 暫存器值，0 / 1 / null
        $errMsg  = $idas_result['error'];  // 錯誤訊息（若有）

        // 預設回應（假設可同步）
        $response = [
            'result'   => true,
            'login'    => 0,
            'res_type' => 'Success',
            'res_msg'  => $msg['login_status']
        ];

        // 如果 modbus 有錯誤，直接回傳錯誤訊息給前端
        if (!empty($errMsg)) {
            $response = [
                'result'   => false,
                'login'    => 0,
                'res_type' => 'Error',
                'res_msg'  => $errMsg   // 或換成 $msg['login_status'] 也可以，看你要不要曝錯
            ];
        } elseif ($regVal != 0) {
            // 暫存器值 != 0 代表「有人登入，不能同步」
            $response = [
                'result'   => false,
                'login'    => 1,
                'res_type' => 'SuccessError',
                'res_msg'  => $msg['already_logged_in']
            ];
        }

        echo json_encode($response);
        exit;  // 統一在這裡結束，不再往下跑

        // $this->ntcs_data_db_sysnc(); // 這段永遠不會被執行到，如果要用另開一個 action
    }



    /**
     * 同步識別資料（Controller → iDAS）
     *
     * - ntcs_device_test.device_sn
     * - ntcs_tool_test.tool_type
     * - ntcs_tool_test.tool_sn
     *
     * ⚠️ 單向同步，禁止反向
     */
    private function syncIdentityFromControllerToIDAS(
        string $controllerDbPath,
        string $idasDbPath
    ): void {

        if (!is_file($controllerDbPath)) {
            throw new Exception("Controller DB not found");
        }
        if (!is_file($idasDbPath)) {
            throw new Exception("iDAS DB not found");
        }

        $cDb = new PDO('sqlite:' . $controllerDbPath);
        $cDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $iDb = new PDO('sqlite:' . $idasDbPath);
        $iDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        /* ---------- 讀 Controller ---------- */
        $device = $cDb->query(
            "SELECT device_sn FROM ntcs_device_test LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);

        if (!$device || empty($device['device_sn'])) {
            throw new Exception('device_sn not found');
        }

        $tool = $cDb->query(
            "SELECT tool_type, tool_sn FROM ntcs_tool_test LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC);

        if (!$tool) {
            throw new Exception('tool data not found');
        }

        /* ---------- 寫 iDAS（transaction） ---------- */
        $iDb->beginTransaction();

        try {
            // device
            if ((int)$iDb->query("SELECT COUNT(*) FROM ntcs_device_test")->fetchColumn() === 0) {
                $iDb->exec("INSERT INTO ntcs_device_test (device_sn) VALUES ('')");
            }

            $stmt = $iDb->prepare(
                "UPDATE ntcs_device_test SET device_sn = :sn"
            );
            $stmt->execute([':sn' => $device['device_sn']]);

            // tool
            if ((int)$iDb->query("SELECT COUNT(*) FROM ntcs_tool_test")->fetchColumn() === 0) {
                $iDb->exec("INSERT INTO ntcs_tool_test (tool_type, tool_sn) VALUES (NULL, NULL)");
            }

            $stmt = $iDb->prepare(
                "UPDATE ntcs_tool_test
                SET tool_type = :type,
                    tool_sn   = :sn"
            );
            $stmt->execute([
                ':type' => $tool['tool_type'],
                ':sn'   => $tool['tool_sn'],
            ]);

            $iDb->commit();

        } catch (Exception $e) {
            $iDb->rollBack();
            throw $e;
        }
    }

    /**
     * 同步「非識別資料」到 Controller
     * ⚠️ 不碰 device_sn / tool_sn
     */
    private function syncData_IDASToController(int $unitId): void {

        // 只處理 job / seq / step / data
        // 你的原本 get_db_sync 就放這裡
        $this->get_db_sync($unitId);
    }

    private function syncFilesToController(ModbusMaster $modbus): void {

        // LIN
        $this->safeCopy(
            '/var/www/html/database/KLS_NTCS_IDAS.Lin',
            '/mnt/ramdisk/ftp/11.Lin'
        );
        $this->notifyModbus($modbus, [1, 12593], 'LIN');

        // Barcode
        $this->safeCopy(
            '/var/www/html/database/ntcs_barcode_IDAS.db',
            '/mnt/ramdisk/ftp/11.db'
        );
        $this->notifyModbus($modbus, [1, 12593], 'DB');
    }














    /* =====================================================
     * Setting Account / table user
     * Rule: only English letters and numbers are allowed.
     *       Regex uses 0-9 because existing examples like steve01/admin0734 need 0.
     * ===================================================== */

    private function accountUserDb(): PDO
    {
        // 統一使用 Database.php 內的：'iDas' => BASE_PATH . 'KLS_NTCS_IDAS.Lin'
        $database = new Database();
        $db = $database->getDb_das();

        if (!$db instanceof PDO) {
            throw new Exception('Account DB connect failed: iDas / KLS_NTCS_IDAS.Lin');
        }

        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $db;
    }

    private function accountUserJson(bool $ok, string $msg, array $extra = []): void
    {
        // 防止 Notice / Warning / login HTML 混入 JSON，造成前端 JSON.parse 失敗。
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo json_encode(array_merge([
            'success'  => $ok,
            'res_type' => $ok ? 'Success' : 'Error',
            'res_msg'  => $msg,
        ], $extra), JSON_UNESCAPED_UNICODE);
        exit();
    }

    private function accountUserClean(string $value): string
    {
        return trim($value);
    }

    private function accountUserCurrentUsername(): string
    {
        return strtolower(trim((string)($_COOKIE['username'] ?? '')));
    }

    private function accountUserRequireAdmin(): void
    {
        // Account 管理功能只允許 cookie username=admin 使用。
        if ($this->accountUserCurrentUsername() !== 'admin') {
            $this->accountUserJson(false, 'Only admin can use Account setting.');
        }
    }

    private function accountUserValidateText(string $value, string $label): void
    {
        if ($value === '') {
            throw new Exception($label . ' cannot be empty.');
        }

        // 既有帳號 key 檢查：允許 A-Z / a-z / 0-9。
        if (!preg_match('/^[A-Za-z0-9]+$/', $value)) {
            throw new Exception($label . ' only allows A-Z, a-z, 0-9.');
        }
    }

    private function accountUserValidateUsername(string $value, string $label = 'Username'): void
    {
        if ($value === '') {
            throw new Exception($label . ' cannot be empty.');
        }

        // 新帳號 / 改名：6~8 字元，只允許 A-Z / a-z / 0-9。
        if (!preg_match('/^[A-Za-z0-9]{6,8}$/', $value)) {
            throw new Exception($label . ' must be 6 to 8 characters and only allows A-Z, a-z, 0-9.');
        }
    }

    private function accountUserValidatePassword(string $value, string $label = 'Password'): void
    {
        if ($value === '') {
            throw new Exception($label . ' cannot be empty.');
        }

        // 密碼固定 4 碼數字，允許 0000。
        if (!preg_match('/^[0-9]{4}$/', $value)) {
            throw new Exception($label . ' must be exactly 4 digits, 0-9.');
        }
    }

    private function accountUserAssertTable(PDO $db): void
    {
        $exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user'")->fetchColumn();
        if (!$exists) {
            throw new Exception('table user not found.');
        }
    }

    private function accountUserIdasDbPathStrict(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            return '/var/www/html/database/KLS_NTCS_IDAS.Lin';
        }

        return $this->accountUserDbPath();
    }

    private function accountUserControllerDbPath(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            return '/home/kls/NTCS7/KLS_NTCS.Lin';
        }

        return __DIR__ . '/../../../database/KLS_NTCS.Lin';
    }

    private function accountUserOpenSqliteFile(string $dbPath): PDO
    {
        if (!is_file($dbPath)) {
            throw new Exception('DB file not found: ' . $dbPath);
        }
        if (!is_readable($dbPath)) {
            throw new Exception('DB file is not readable: ' . $dbPath);
        }

        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $db;
    }

    private function accountUserQuoteIdentifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new Exception('Invalid column name: ' . $name);
        }

        return '"' . str_replace('"', '""', $name) . '"';
    }

    private function accountUserTableColumns(PDO $db): array
    {
        $rows = $db->query('PRAGMA table_info("user")')->fetchAll(PDO::FETCH_ASSOC);
        $columns = [];

        foreach ($rows as $row) {
            if (isset($row['name']) && $row['name'] !== '') {
                $columns[] = (string)$row['name'];
            }
        }

        return $columns;
    }

    public function account_user_upload_controller(): void
    {
        $targetDb = null;

        try {
            $this->accountUserRequireAdmin();

            if (PHP_OS_FAMILY !== 'Linux') {
                throw new Exception('Upload to controller DB is only supported on Linux.');
            }

            $sourcePath = $this->accountUserIdasDbPathStrict();
            $targetPath = $this->accountUserControllerDbPath();

            if (!is_file($sourcePath)) {
                throw new Exception('Source iDAS DB not found: ' . $sourcePath);
            }
            if (!is_file($targetPath)) {
                throw new Exception('Target controller DB not found: ' . $targetPath);
            }
            if (!is_writable($targetPath) || !is_writable(dirname($targetPath))) {
                throw new Exception('Target controller DB or folder is not writable: ' . $targetPath);
            }

            $sourceDb = $this->accountUserOpenSqliteFile($sourcePath);
            $targetDb = $this->accountUserOpenSqliteFile($targetPath);

            $this->accountUserAssertTable($sourceDb);
            $this->accountUserAssertTable($targetDb);

            $sourceColumns = $this->accountUserTableColumns($sourceDb);
            $targetColumns = $this->accountUserTableColumns($targetDb);
            $copyColumns = array_values(array_intersect($targetColumns, $sourceColumns));

            if (empty($copyColumns)) {
                throw new Exception('No matching columns found between source and target user table.');
            }
            if (!in_array('name', $copyColumns, true) || !in_array('passwd', $copyColumns, true)) {
                throw new Exception('Target/source user table must contain name and passwd columns.');
            }

            $columnSql = implode(', ', array_map([$this, 'accountUserQuoteIdentifier'], $copyColumns));
            $orderSql = in_array('sn', $sourceColumns, true) ? ' ORDER BY "sn" ASC' : ' ORDER BY rowid ASC';

            $rows = $sourceDb->query('SELECT ' . $columnSql . ' FROM "user"' . $orderSql)->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                throw new Exception('Source user table has no data. Upload aborted.');
            }

            // Backup target DB before touching table user only.
            $backupPath = $targetPath . '.user_backup_' . date('Ymd_His');
            if (!@copy($targetPath, $backupPath)) {
                throw new Exception('Backup target DB failed: ' . $backupPath);
            }
            @chmod($backupPath, 0666);

            $targetDb->beginTransaction();

            // Only table user is modified. Other tables are untouched.
            $targetDb->exec('DELETE FROM "user"');

            $placeholders = implode(', ', array_map(function($col) {
                return ':' . $col;
            }, $copyColumns));

            $insertSql = 'INSERT INTO "user" (' . $columnSql . ') VALUES (' . $placeholders . ')';
            $insertStmt = $targetDb->prepare($insertSql);

            foreach ($rows as $row) {
                $params = [];
                foreach ($copyColumns as $col) {
                    $params[':' . $col] = $row[$col] ?? null;
                }
                $insertStmt->execute($params);
            }

            $targetDb->commit();
            @chmod($targetPath, 0666);
            @exec('sync');

            $this->accountUserJson(true, 'Upload user list to controller success. Rows: ' . count($rows) . '.', [
                'rows'        => count($rows),
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'backup_path' => $backupPath,
            ]);
        } catch (Throwable $e) {
            if ($targetDb instanceof PDO && $targetDb->inTransaction()) {
                $targetDb->rollBack();
            }

            $this->accountUserJson(false, 'Upload user list to controller failed: ' . $e->getMessage());
        }
    }

    public function account_user_list(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            // 隱藏內建帳號 Kls / kls，不刪除 DB 資料
            $rows = $db->query("
                SELECT sn, name, passwd, law
                FROM `user`
                WHERE LOWER(name) <> 'kls'
                ORDER BY sn ASC
            ")->fetchAll();

            $this->accountUserJson(true, 'OK', [
                'records' => $rows,
                'count'   => count($rows),
            ]);
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }



    private function accountUserCsvText(string $value): string
    {
        $value = trim($value);

        // Excel formula-text style from export, e.g. ="0000".
        if (preg_match('/^="(.*)"$/s', $value, $m)) {
            return str_replace('""', '"', $m[1]);
        }

        // Remove UTF-8 BOM if present.
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        return trim((string)$value);
    }

    private function accountUserExcelText(string $value): string
    {
        // Force Excel to treat password as text, so 0000 will not become 0.
        return '="' . str_replace('"', '""', $value) . '"';
    }

    public function account_user_export(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            // Export follows Account list rule: hide built-in Kls / kls account.
            $rows = $db->query("
                SELECT sn, name, passwd, law
                FROM `user`
                WHERE LOWER(name) <> 'kls'
                ORDER BY sn ASC
            ")->fetchAll();

            // Clean all previous output to avoid corrupting CSV download.
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            $filename = 'account_password_export_' . date('Ymd_His') . '.csv';

            if (!headers_sent()) {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Cache-Control: no-store, no-cache, must-revalidate');
                header('Pragma: no-cache');
            }

            // UTF-8 BOM for Excel compatibility.
            echo "\xEF\xBB\xBF";

            $fp = fopen('php://output', 'w');
            fputcsv($fp, ['No', 'User Name', 'Password', 'Law']);

            $no = 1;
            foreach ($rows as $row) {
                fputcsv($fp, [
                    $no++,
                    $row['name'] ?? '',
                    $this->accountUserExcelText((string)($row['passwd'] ?? '')),
                    $row['law'] ?? '',
                ]);
            }

            fclose($fp);
            exit();
        } catch (Throwable $e) {
            $this->accountUserJson(false, 'Export account failed: ' . $e->getMessage());
        }
    }

    public function account_user_import(): void
    {
        try {
            $this->accountUserRequireAdmin();
            if (empty($_FILES['account_file']) || !isset($_FILES['account_file']['tmp_name'])) {
                throw new Exception('Please select a CSV file.');
            }

            if ((int)($_FILES['account_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new Exception('Upload failed. Error code: ' . (int)$_FILES['account_file']['error']);
            }

            $tmpName = (string)$_FILES['account_file']['tmp_name'];
            $originalName = (string)($_FILES['account_file']['name'] ?? '');
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ($ext !== 'csv') {
                throw new Exception('Only CSV file is allowed.');
            }

            if (!is_uploaded_file($tmpName) && !is_file($tmpName)) {
                throw new Exception('Uploaded file not found.');
            }

            $fp = fopen($tmpName, 'r');
            if (!$fp) {
                throw new Exception('Cannot open uploaded CSV file.');
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $header = fgetcsv($fp);
            if (!$header || count($header) < 3) {
                fclose($fp);
                throw new Exception('CSV format invalid. Header must include User Name and Password.');
            }

            // Normalize header names from export: No, User Name, Password, Law.
            $headerMap = [];
            foreach ($header as $idx => $col) {
                $key = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string)$col)));
                $key = str_replace([' ', '_', '-'], '', $key);
                $headerMap[$key] = $idx;
            }

            $nameIndex = $headerMap['username'] ?? $headerMap['name'] ?? 1;
            $passIndex = $headerMap['password'] ?? $headerMap['passwd'] ?? 2;
            $lawIndex  = $headerMap['law'] ?? 3;

            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;
            $lineNo   = 1;
            $errors   = [];

            $db->beginTransaction();

            while (($row = fgetcsv($fp)) !== false) {
                $lineNo++;

                // Skip blank lines.
                if (count(array_filter($row, function($v) { return trim((string)$v) !== ''; })) === 0) {
                    continue;
                }

                $name = $this->accountUserCsvText((string)($row[$nameIndex] ?? ''));
                $password = $this->accountUserCsvText((string)($row[$passIndex] ?? ''));
                $lawRaw = $this->accountUserCsvText((string)($row[$lawIndex] ?? '1'));
                $law = ($lawRaw !== '' && is_numeric($lawRaw)) ? (int)$lawRaw : 1;

                // Built-in Kls is protected and will not be imported or modified.
                if (strtolower($name) === 'kls') {
                    $skipped++;
                    continue;
                }

                try {
                    $this->accountUserValidateUsername($name, 'Username');
                    $this->accountUserValidatePassword($password, 'Password');
                } catch (Throwable $e) {
                    $errors[] = 'Line ' . $lineNo . ': ' . $e->getMessage();
                    $skipped++;
                    continue;
                }

                $stmt = $db->prepare('SELECT COUNT(*) FROM `user` WHERE name = :name');
                $stmt->execute([':name' => $name]);
                $exists = (int)$stmt->fetchColumn() > 0;

                if ($exists) {
                    $stmt = $db->prepare('UPDATE `user` SET passwd = :passwd, law = :law WHERE name = :name');
                    $stmt->execute([
                        ':passwd' => $password,
                        ':law'    => $law,
                        ':name'   => $name,
                    ]);
                    $updated++;
                } else {
                    $nextSn = (int)$db->query('SELECT COALESCE(MAX(sn), -1) + 1 FROM `user`')->fetchColumn();
                    $stmt = $db->prepare('INSERT INTO `user` (sn, name, passwd, law) VALUES (:sn, :name, :passwd, :law)');
                    $stmt->execute([
                        ':sn'     => $nextSn,
                        ':name'   => $name,
                        ':passwd' => $password,
                        ':law'    => $law,
                    ]);
                    $inserted++;
                }
            }

            fclose($fp);
            $db->commit();

            $message = 'Import success. Inserted: ' . $inserted . ', Updated: ' . $updated . ', Skipped: ' . $skipped . '.';
            if (!empty($errors)) {
                $message .= ' Some rows were skipped.';
            }

            $this->accountUserJson(true, $message, [
                'inserted' => $inserted,
                'updated'  => $updated,
                'skipped'  => $skipped,
                'errors'   => array_slice($errors, 0, 10),
            ]);
        } catch (Throwable $e) {
            if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
                $db->rollBack();
            }

            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }

            $this->accountUserJson(false, 'Import account failed: ' . $e->getMessage());
        }
    }

    public function account_user_create(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $name     = $this->accountUserClean($_POST['username'] ?? '');
            $password = $this->accountUserClean($_POST['password'] ?? '');
            $confirm  = $this->accountUserClean($_POST['confirm_password'] ?? '');
            $law      = isset($_POST['law']) ? (int)$_POST['law'] : 1;

            $this->accountUserValidateUsername($name, 'Username');
            $this->accountUserValidatePassword($password, 'Password');

            if ($password !== $confirm) {
                throw new Exception('Confirm password is different.');
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $stmt = $db->prepare("SELECT COUNT(*) FROM `user` WHERE name = :name");
            $stmt->execute([':name' => $name]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception('Username already exists.');
            }

            $nextSn = (int)$db->query("SELECT COALESCE(MAX(sn), -1) + 1 FROM `user`")->fetchColumn();

            $stmt = $db->prepare("INSERT INTO `user` (sn, name, passwd, law) VALUES (:sn, :name, :passwd, :law)");
            $stmt->execute([
                ':sn'     => $nextSn,
                ':name'   => $name,
                ':passwd' => $password,
                ':law'    => $law,
            ]);

            $this->accountUserJson(true, 'New Account success.');
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }

    public function account_user_update(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $oldName  = $this->accountUserClean($_POST['old_username'] ?? '');
            $name     = $this->accountUserClean($_POST['username'] ?? '');
            $password = $this->accountUserClean($_POST['password'] ?? '');
            $confirm  = $this->accountUserClean($_POST['confirm_password'] ?? '');
            $law      = isset($_POST['law']) ? (int)$_POST['law'] : 1;

            $this->accountUserValidateText($oldName, 'Old username');
            if ($oldName !== $name) {
                $this->accountUserValidateUsername($name, 'Username');
            } else {
                // 允許既有 guest/admin/user1 等舊帳號在未改名時繼續修改密碼。
                $this->accountUserValidateText($name, 'Username');
            }

            if ($password !== '') {
                $this->accountUserValidatePassword($password, 'Password');
                if ($password !== $confirm) {
                    throw new Exception('Confirm password is different.');
                }
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $stmt = $db->prepare("SELECT COUNT(*) FROM `user` WHERE name = :name");
            $stmt->execute([':name' => $oldName]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new Exception('Account not found.');
            }

            if ($oldName !== $name) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM `user` WHERE name = :name");
                $stmt->execute([':name' => $name]);
                if ((int)$stmt->fetchColumn() > 0) {
                    throw new Exception('Username already exists.');
                }
            }

            if ($password === '') {
                $stmt = $db->prepare("UPDATE `user` SET name = :name, law = :law WHERE name = :old_name");
                $stmt->execute([
                    ':name'     => $name,
                    ':law'      => $law,
                    ':old_name' => $oldName,
                ]);
            } else {
                $stmt = $db->prepare("UPDATE `user` SET name = :name, passwd = :passwd, law = :law WHERE name = :old_name");
                $stmt->execute([
                    ':name'     => $name,
                    ':passwd'   => $password,
                    ':law'      => $law,
                    ':old_name' => $oldName,
                ]);
            }

            $this->accountUserJson(true, 'Edit Account success.');
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }

    public function account_user_delete(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $name = $this->accountUserClean($_POST['username'] ?? '');
            $this->accountUserValidateText($name, 'Username');

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $count = (int)$db->query("SELECT COUNT(*) FROM `user`")->fetchColumn();
            if ($count <= 1) {
                throw new Exception('Cannot delete the last account.');
            }

            $stmt = $db->prepare("DELETE FROM `user` WHERE name = :name");
            $stmt->execute([':name' => $name]);

            if ($stmt->rowCount() <= 0) {
                throw new Exception('Account not found.');
            }

            $this->accountUserJson(true, 'Delete Account success.');
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }


    public function operation_audit_log_list(): void
    {
        try {
            $this->accountUserRequireAdmin();

            $limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 100;
            if ($limit <= 0 || $limit > 500) {
                $limit = 100;
            }

            $source = isset($_POST['source']) ? strtolower(trim((string)$_POST['source'])) : 'idas';
            if (!in_array($source, ['idas', 'app'], true)) {
                $source = 'idas';
            }

            if ($source === 'app') {
                $rows = $this->SettingModel->getAppOperationLogs($limit);
            } else {
                $rows = $this->SettingModel->getOperationAuditLogs($limit);
            }

            $this->accountUserJson(true, 'OK', [
                'source'  => $source,
                'records' => $rows,
                'count'   => count($rows),
            ]);
        } catch (Throwable $e) {
            $this->accountUserJson(false, 'Load operation log failed: ' . $e->getMessage());
        }
    }

    public function operation_audit_log_detail(): void
    {
        try {
            $this->accountUserRequireAdmin();

            $logId = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;
            if ($logId <= 0) {
                throw new Exception('Invalid log_id.');
            }

            $row = $this->SettingModel->getOperationAuditLogDetail($logId);
            if (!$row) {
                throw new Exception('Log not found.');
            }

            $this->accountUserJson(true, 'OK', [
                'record' => $row,
            ]);
        } catch (Throwable $e) {
            $this->accountUserJson(false, 'Load operation_audit_log detail failed: ' . $e->getMessage());
        }
    }

}
