<?php

class Settings extends Controller
{
    private $SettingModel;
    private $AdminModel;
    private $ToolModel;
    private $MiscellaneousModel;
    private $DataModel;
    private $stepModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->SettingModel = $this->model('Setting');
        $this->AdminModel = $this->model('Admin');
        $this->ToolModel = $this->model('Tool');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->DataModel = $this->model('Datas');
        $this->stepModel = $this->model('Steptcc');
    }

    // 取得所有info
    public function index(){


        $this->ntcs_data_db_sysnc();
        
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
        $file_location = '';
        $result = '';

        if(empty($_FILES)){
            echo json_encode(["Error" => 'no file']);
            exit();
        }

        // 取得控制器的id
        $controller_info = (array)($this->SettingModel->GetControllerInfo() ?? []);
        $device_id = isset($controller_info['device_id']) ? (int)$controller_info['device_id'] : 1;
        $unitId = ($device_id >= 1 && $device_id <= 512) ? $device_id : 1;

        if( PHP_OS_FAMILY == 'Linux'){
            $this->logMessage('firmware update start');

            // $destination = "/mnt/ramdisk/FTP/iDas.cfg";
            // 固定檔名（副檔名從上傳檔案抓取）
            $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $fixedName = "iDAS." . $extension;

            $destination = "/mnt/ramdisk/ftp/" . $fixedName;

            // 固定給 Modbus 的檔名字串
            $filenameWithoutExtension = "iDAS";
            //將檔案移到指定位置
            $result =  move_uploaded_file($_FILES['file']['tmp_name'], $destination);
            $name_int16 = $this->asciiToHexToInt($filenameWithoutExtension);

            if ($result) {
                require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
                $modbus = new ModbusMaster("127.0.0.1", "TCP");
                try {
                    $modbus->port = 502;
                    $modbus->timeout_sec = 10;
                    $data = array(1, $name_int16[0], $name_int16[1], $name_int16[2], $name_int16[3], $name_int16[4], $name_int16[5], $name_int16[6], $name_int16[7], $name_int16[8], $name_int16[9], $name_int16[10], $name_int16[11]);
                    $dataTypes = array("INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT");

                    // FC 16
                    $modbus->writeMultipleRegister( $unitId , 480, $data, $dataTypes);
                    $this->logMessage('modbus write 480 ,array = '.implode("','", $data));
                    $this->logMessage('modbus status:'.$modbus->status);
                    $this->logMessage('firmware update end');
                    //自動重新啟動控制器
                    $modbus->writeMultipleRegister( $unitId , 462, array(1), $dataTypes);

                    echo json_encode(array('error' => ''));
                    exit();

                } catch (Exception $e) {
                    // Print error information if any
                    // echo $modbus;
                    // echo $e;
                    $this->logMessage('modbus write 480 fail');
                    $this->logMessage('modbus status:'.$modbus->status);
                    $this->logMessage('firmware update end');
                    echo json_encode(array('error' => 'modbus error'));
                    exit();
                }
            } else {
                $this->logMessage('copy db error');
                $this->logMessage('firmware update end');
                echo json_encode(array('error' => 'copy db error'));
                exit();
            }

        }else{//windows暫不考慮升級，可能整包升級
            // $this->logMessage('Import config start');
            $file_location = $_SERVER['DOCUMENT_ROOT'].'/';
            echo json_encode(["Error" => 'not for windows']);
            exit();
        }

        echo json_encode(["message" => $result]);
    }

    function asciiToHexToInt($input) {
        // 將 ASCII 字元轉換為十六進位
        $hex = bin2hex($input);

        // 將十六進位字串以每 4 個字元為一組進行分割
        $chunks = str_split($hex, 4);

        $result = array();
        foreach ($chunks as $chunk) {
            // 將每組 4 個字元的十六進位轉換為整數
            $result[] = hexdec($chunk);
        }

        return $result;
    }


    public function export_sysytem_config() {
        if (PHP_OS_FAMILY !== 'Linux') {
            http_response_code(400);
            echo json_encode(['error' => 'Only supported on Linux']);
            return;
        }

        
        // 取得控制器的id
        $controller_info = (array)($this->SettingModel->GetControllerInfo() ?? []);
        $device_id = isset($controller_info['device_id']) ? (int)$controller_info['device_id'] : 1; 
        $unitId = ($device_id >= 1 && $device_id <= 512) ? $device_id : 1;


        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        // 1) 觸發控制器產檔
        try {
            $modbus = new ModbusMaster("127.0.0.1", "TCP");
            $modbus->port = 502;
            $data = [1];
            $dataTypes = ["INT"]; // 長度與 data 一致
            $modbus->writeMultipleRegister($unitId, 505, $data, $dataTypes);
            $this->logMessage('modbus write 505 ok');
        } catch (Exception $e) {
            $this->logMessage('modbus write 505 fail: ' . $e->getMessage());
            echo json_encode(['error' => 'modbus error']);
            return;
        }

        // 2) 基本資訊
        $controller_info = $this->SettingModel->GetControllerInfo();
        $sn = preg_replace('/[^A-Za-z0-9_\-]/', '_', $controller_info['device_sn'] ?? 'UNKNOWN');
        $system_date = trim(shell_exec("date '+%Y%m%d%H%M%S'")) ?: date('YmdHis');
        $linNameInZip = "con_{$sn}_{$system_date}.Lin";

        // 3) 等候檔案出現（並確認大小穩定）
        $candidates = [
            "/mnt/ramdisk/ftp/KLS_NTCS.Lin",
            "/home/kls/NTCS7/KLS_NTCS.Lin",
            "/var/www/html/database/KLS_NTCS_IDAS.Lin",
        ];
        $srcLin = null;
        $deadline = microtime(true) + 8.0; // 最多 8 秒
        while (microtime(true) < $deadline && !$srcLin) {
            foreach ($candidates as $p) {
                if (is_file($p)) {
                    clearstatcache(true, $p);
                    $s1 = filesize($p);
                    usleep(200000); // 200ms
                    clearstatcache(true, $p);
                    $s2 = filesize($p);
                    if ($s1 > 0 && $s1 === $s2) { // 大小穩定才使用
                        $srcLin = $p;
                        break;
                    }
                }
            }
            if (!$srcLin) usleep(200000);
        }

        // 4) 打包
        $zipPath = "/mnt/ramdisk/ftp/NTCS_Config.zip";
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            $this->logMessage("Cannot create zip: {$zipPath}");
            echo json_encode(['error' => 'cannot create zip']);
            return;
        }

        if ($srcLin) {
            $zip->addFile($srcLin, $linNameInZip);
        } else {
            $this->logMessage('KLS_NTCS.Lin not found or not stable — skipped');
            // 想要更明顯也可加提示檔：
            // $zip->addFromString("README.txt", "KLS_NTCS.Lin not ready at packaging time.\n");
        }

       $barcode = "/var/www/html/database/ntcs_barcode_IDAS.db";
        if (is_file($barcode)) {
            // 保留原本的名稱（如果你不需要，可以刪掉這行）
            //$zip->addFile($barcode, "ntcs_barcode.cfg");

            // 新增一個帶時間戳的檔名，例如：ntcs_barcode_2025-11-13_141115.db
            $barcodeTime = date('Y-m-d_His'); // 格式：2025-11-13_141115
            $barcodeZipName = "ntcs_barcode_{$barcodeTime}.db";
            $zip->addFile($barcode, $barcodeZipName);
        } else {
            $this->logMessage("file not found: {$barcode}");
        }

        $zip->close();

        // 5) 送下載
        header("Content-Type: application/zip");
        header('Content-Disposition: attachment; filename=NTCS_Config_Pack.zip');
        header("Content-Length: " . filesize($zipPath));
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Pragma: no-cache");
        readfile($zipPath);
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
        $controller_info = (array)($this->SettingModel->GetControllerInfo() ?? []);
        $device_id   = $controller_info['device_id'];
        $idas_result = $this->idas_check($device_id);

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


         // 取得控制器的id
        $controller_info = (array)($this->SettingModel->GetControllerInfo() ?? []);
        $device_id = isset($controller_info['device_id']) ? (int)$controller_info['device_id'] : 1;
        $unitId = ($device_id >= 1 && $device_id <= 512) ? $device_id : 1;



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

        // Modbus slave ID 合理範圍通常是  1~512
        $unitId = $device_id;
        if ($unitId < 1 || $unitId > 512) {
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

        $isMobile = $this->isMobileCheck();
        $barcode_list = '';
        $barcodes = $this->SettingModel->GetAllBarcodes();
        $barcode_mode = $this->MiscellaneousModel->details('barcode_mode');
        if(!empty($barcodes)){
            
            if(!$isMobile){

                foreach($barcodes as $kk =>$vv){
                    $barcode_list = '<tr style="text-align: center; vertical-align: middle;" >';
                    $barcode_list .= "<td><input class='form-check-input' type='checkbox' name='barcode_check' id='barcode_check' style='zoom:1.2' value='".$vv['job_id']."'></td>";
                    $barcode_list .= '<td>'.$vv['job_id'].'</td>';
                    $barcode_list .= '<td>'.$vv['JOBname'].'</td>';
                    $barcode_list .= '<td>'.$vv['barcode'].'</td>';
                    $barcode_list .= '<td>'.$vv['range_from'].'</td>';
                    $barcode_list .= '<td>'.$vv['range_count'].'</td>';
                    $barcode_list .= '<td>'.$barcode_mode[$vv['barcode_mode']].'</td>';
                    $barcode_list .= '<tr>';
    
                    echo $barcode_list;
                }

            }else{
                foreach($barcodes as $kk =>$vv){
                    $barcode_list = '<tr style="text-align: center; vertical-align: middle;" >';
                    $barcode_list .= "<td><input class='form-check-input' type='checkbox' name='barcode_check' id='barcode_check' style='zoom:1.2' value='".$vv['job_id']."'></td>";
                    $barcode_list .= '<td>'.$vv['job_id'].'</td>';
                    $barcode_list .= '<td>'.$vv['JOBname'].'</td>';
                    $barcode_list .= '<td>'.$vv['barcode'].'</td>';
                    $barcode_list .= '<td>'.$vv['range_from'].'</td>';
                    $barcode_list .= '<td>'.$vv['range_count'].'</td>';
                    $barcode_list .= '<td>'.$barcode_mode[$vv['barcode_mode']].'</td>';
                    $barcode_list .= '<tr>';
    
                    echo $barcode_list;
                }

            }
          

        }

    }

    //update barcode
    public function Update_Barcode(){


        // 語系載入
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        // 欄位定義（key 對應 + 是否必填）
        $fields = [
            'barcode_name'  => ['key' => 'barcode_name',         'required' => true],
            'barcode_from'  => ['key' => 'barcode_range_from',   'required' => true],
            'barcode_count' => ['key' => 'barcode_range_count',  'required' => true],
            'barcode_job'   => ['key' => 'barcode_job',          'required' => true],
            'barcode_mode'  => ['key' => 'barcode_mode',         'required' => true],
            'barcode_seq'   => ['key' => 'barcode_seq',          'required' => false],
        ];

        $barcode = [];
        $input_check = true;

        foreach ($fields as $post_key => $map) {
            if (isset($_POST[$post_key]) && ($_POST[$post_key] !== '' || !$map['required'])) {
                $barcode[$map['key']] = $_POST[$post_key];
            } elseif ($map['required']) {
                $input_check = false;
            } else {
                $barcode[$map['key']] = ""; // 非必填欄位預設值
            }
        }



        if($barcode['barcode_seq'] == "-1"){
            $barcode['barcode_seq'] = "";
        }


        if ($input_check) {
            $barcode_result = $this->SettingModel->Update_Barcode($barcode);

            $res_type = $barcode_result ? 'Success' : 'Error';
            $res_msg = ($barcode_result ? 'edit barcode :' : 'edit barcode :') . $barcode['barcode_name'] . ($barcode_result ? ' success' : ' fail');
            $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
        } else {
            $res_msg = $text['input_error'] ?? 'Required fields missing.';
            $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg);
        }
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

        //header('Content-Type: application/json; charset=utf-8');

        // 只支援 job_id
        $jobId = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
        if ($jobId <= 0) {
            echo  $this->MiscellaneousModel->generateErrorResponse('Error', 'No job_id provided.');
        }

        try {
            // Model 實作：delete_barcodes_by_job(int $jobId): int
            // 回傳受影響筆數 (affected rows)
            $affected = (int)($this->SettingModel->delete_barcodes_by_job($jobId) ?? 0);

            if ($affected > 0) {
                echo  $this->MiscellaneousModel->generateErrorResponse(
                    'Success',
                    "Job #{$jobId} barcodes deleted, affected: {$affected}"
                );
            }

            echo  $this->MiscellaneousModel->generateErrorResponse(
                'Error',
                "No barcodes found for job #{$jobId}."
            );

        } catch (Throwable $e) {
            echo  $this->MiscellaneousModel->generateErrorResponse(
                'Error',
                'Delete by job failed: ' . $e->getMessage()
            );
        }
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

            // 13. 部署
            $target_directory = $_SERVER['DOCUMENT_ROOT'] . '/ntcs_idas/';
            if (!is_dir($target_directory)) mkdir($target_directory, 0777, true);
            $this->copyDirectory($main_folder, $target_directory);

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
                'en-us' => 'Update successful. Files have been moved to the "ntcs_idas" directory.',
                'zh-tw' => '更新成功，已將檔案移動至 ntcs_idas 目錄。',
                'zh-cn' => '更新成功，已将文件移动至 ntcs_idas 目录。',
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

        // 取得控制器的id
        $controller_info = (array)($this->SettingModel->GetControllerInfo() ?? []);
        $device_id = isset($controller_info['device_id']) ? (int)$controller_info['device_id'] : 1; 
        $unitId = ($device_id >= 1 && $device_id <= 512) ? $device_id : 1;



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
        foreach ($_COOKIE as $key => $value) {
            setcookie($key, '', time() - 3600, '/');
        }
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
        $device_id = isset($controller_info['device_id'])
            ? (int)$controller_info['device_id']
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



}