<?php

class Settings extends Controller
{
    private $SettingModel;
    private $AdminModel;
    private $ToolModel;
    private $MiscellaneousModel;
    private $DataModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->SettingModel = $this->model('Setting');
        $this->AdminModel = $this->model('Admin');
        $this->ToolModel = $this->model('Tool');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->DataModel = $this->model('Datas');
    }

    // 取得所有info
    public function index(){

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

        // 必填欄位驗證
        $required_fields = ['control_id', 'control_name', 'storage_warning', 'torque_filter'];
        foreach ($required_fields as $field) {
            $val = $get($field);
            if ($val === null) {
                $input_check = false;
            } else {
                $con_setting[$field] = $val;
            }
        }

        // 可選欄位（含預設值）
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
            $con_setting[$field] = $get($field, ''); // 空字串作為預設值
        }

        // 若前面驗證通過
        if ($input_check) {
            $res = $this->SettingModel->GetControllerInfo_count($con_setting['control_id']);

            if ($res['count'] === "1") {
                $result = $this->SettingModel->Controller_Setting($con_setting);

                if ($result) {
                    $res_msg = $text['success'] ?? 'Success';
                    $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg);
                } else {
                    $res_msg = $text['fail'] ?? 'Fail';
                    $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg);
                }
            } else {
                $res_msg = $text['not_found'] ?? 'Controller not found';
                $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg);
            }
        } else {
            $res_msg = $text['form_invalid'] ?? 'Invalid input';
            $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg);
        }
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

    public function firmware_reset()
    {
        // code...
    }



    public function export_sysytem_config() {
        
        if (PHP_OS_FAMILY == 'Linux') {
            require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
            $modbus = new ModbusMaster("127.0.0.1", "TCP");
            try {
                $modbus->port = 502;
                $data = [1];
                $dataTypes = array_fill(0, 16, "INT");
                $modbus->writeMultipleRegister(0, 505, $data, $dataTypes);
                $this->logMessage('modbus write 505 ,array = ' . implode("','", $data));

                // 要打包的檔案與對應名稱
                $files = [
                    "/mnt/ramdisk/ftp/KLS_NTCS.Lin"      => "KLS_NTCS.Lin",
                    "/mnt/ramdisk/ftp/ntcs_barcode.db"   => "ntcs_barcode.cfg"
                ];

                $zipPath = "/mnt/ramdisk/ftp/NTCS_Config_Pack.zip";
                $zip = new ZipArchive();
                if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
                    throw new Exception("無法建立 zip 檔案");
                }

                foreach ($files as $filePath => $nameInZip) {
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, $nameInZip);
                    }
                }
                $zip->close();

                // 設定下載 header
                header("Content-Type: application/zip");
                header("Content-Disposition: attachment; filename=NTCS_Config_Pack.zip");
                header("Content-Length: " . filesize($zipPath));
                readfile($zipPath);
                exit();

            } catch (Exception $e) {
                $this->logMessage('modbus write 505 fail');
                echo json_encode(['error' => 'modbus error']);
                exit();
            }
        }
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


        if (!empty($_POST['del_year_id']) && isset($_POST['del_year_id'])) {
            $del_year_id = $_POST['del_year_id'];
        } else {
            echo json_encode([
                'result' => false,
                'res_type' => 'Error',
                'res_msg' => 'Invalid input'
            ]);
            return;
        }

        $temp_del_year = $del_year_id[0]; // 只處理第一筆

        // 檢查是否可以刪除（Modbus 狀態檢查）
        $idas_result = $this->idas_check();
        if ($idas_result['result'] != 0) {
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

            $modbus->writeMultipleRegister(0, 517, $year, $dataTypes);

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

    public function Sync_check_db() {

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $argument = $_POST['argument'] ?? '';

        $src1         = '/var/www/html/database/KLS_NTCS_IDAS.Lin';
        $midPath1     = '/mnt/ramdisk/11.Lin';
        $finalPath1   = '/mnt/ramdisk/ftp/11.Lin';

        $src2         = '/var/www/html/database/ntcs_barcode_IDAS.db';
        $midPath2     = '/mnt/ramdisk/11.db';
        $finalPath2   = '/mnt/ramdisk/ftp/11.db';

        $renamedPath1 = '/mnt/ramdisk/ftp/11_tmp.Lin';
        $renamedPath2 = '/mnt/ramdisk/ftp/11_db_temp.db';

        if (PHP_OS_FAMILY === 'Linux' && $argument === 'D2C') {

            // Check if source files exist
            if (!file_exists($src1) || !file_exists($src2)) {
                $missingFiles = [];
                if (!file_exists($src1)) $missingFiles[] = 'KLS_NTCS_IDAS.Lin';
                if (!file_exists($src2)) $missingFiles[] = 'ntcs_barcode_IDAS.db';

                return $this->MiscellaneousModel->generateErrorResponse(
                    'Error',
                    'Source file(s) missing: ' . implode(', ', $missingFiles)
                );
            }

            require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
            $modbus = new ModbusMaster("127.0.0.1", "TCP");
            $modbus->port = 502;
            $modbus->timeout_sec = 10;

            try {
                // ----------- Sync LIN File -----------
                if (!$this->safeCopy($src1, $midPath1)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to copy $src1");
                }
                @chmod($midPath1, 0777);

                if (!$this->safeCopy($midPath1, $finalPath1)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to copy to $finalPath1");
                }
                unlink($midPath1);
                $this->logMessage("$src1 copied to FTP");

                $this->notifyModbus($modbus, [1, 12593], "LIN");

                // Rename LIN file
                if (!$this->safeCopy($finalPath1, $renamedPath1)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to rename LIN file");
                }
                unlink($finalPath1);
                $this->logMessage("$finalPath1 renamed to $renamedPath1");

                usleep(1_000_000); // sleep 1 sec

                // ----------- Sync DB File -----------
                if (!$this->safeCopy($src2, $midPath2)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to copy $src2");
                }
                @chmod($midPath2, 0777);

                if (!$this->safeCopy($midPath2, $finalPath2)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to copy to $finalPath2");
                }
                unlink($midPath2);
                $this->logMessage("$src2 copied to FTP");

                $this->notifyModbus($modbus, [1, 12593], "DB");

                // Rename DB file
                if (!$this->safeCopy($finalPath2, $renamedPath2)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to rename DB file");
                }
                unlink($finalPath2);
                $this->logMessage("$finalPath2 renamed to $renamedPath2");

                return $this->MiscellaneousModel->generateErrorResponse('Success', 'SYNC ' . ($text['success'] ?? 'success'));

            } catch (Exception $e) {
                $this->logMessage('Modbus write fail: ' . $e->getMessage());
                return $this->MiscellaneousModel->generateErrorResponse('Error', 'Modbus communication failed');
            }
        }

        return $this->MiscellaneousModel->generateErrorResponse('Error', 'Invalid sync argument or unsupported OS');
    }


    public function Sync_check_db_load(){

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $argument = $_POST['argument'] ?? '';

        $src1 = '/home/kls/NTCS7/KLS_NTCS.Lin';
        $dst1 = '/var/www/html/database/KLS_NTCS_IDAS.Lin';
        $tmp1 = '/mnt/ramdisk/ftp/11.Lin';

        $src2 = '/home/kls/NTCS7/ntcs_barcode.db';
        $dst2 = '/var/www/html/database/ntcs_barcode_IDAS.db';
        $tmp2 = '/mnt/ramdisk/ftp/11_tmp.db';

        $Con_DB_Location = $src1;
        $Das_DB_Location = $dst1;

        if (!empty($argument) && PHP_OS_FAMILY === 'Linux' && $argument === 'C2D') {
            require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
            $modbus = new ModbusMaster("127.0.0.1", "TCP");
            $modbus->port = 502;
            $modbus->timeout_sec = 10;

            try {
                // 第一步：複製 KLS_NTCS.Lin 到 KLS_NTCS_IDAS.Lin
                if (!copy($src1, $dst1)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', '複製 KLS_NTCS.Lin 失敗');
                }

                // 時間差異提醒
                if (filemtime($Con_DB_Location) > filemtime($Das_DB_Location)) {
                    $notice = $text['system_sync_notice'] . date("Y-m-d H:i:s.", filemtime($Con_DB_Location));
                    $this->logMessage($notice);
                }

                // DB欄位差異判斷
                $this->Database_Column_Diff($src1, $dst1);

                // SHA1 驗證檔案一致性
                if (sha1_file($src1) !== sha1_file($dst1)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', '.Lin 檔案 SHA1 不一致');
                }

                // 建立 /mnt/ramdisk/ftp/11.Lin 檔案
                if (!copy($dst1, $tmp1)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', '建立 11.Lin 失敗');
                }

                // 使用 Modbus 通知 .Lin 同步完成
                $data1 = [1, 12593];
                $dataTypes1 = array_fill(0, count($data1), 'INT');
                $modbus->writeMultipleRegister(0, 506, $data1, $dataTypes1);
                $this->logMessage('Modbus 寫入 (.Lin)：' . implode(',', $data1));

                // 刪除 11.Lin 檔案
                if (file_exists($tmp1)) {
                    unlink($tmp1);
                }

                // 延遲 1 秒
                usleep(1000000);

                // 第二步：複製 ntcs_barcode.db 到 ntcs_barcode_IDAS.db
                if (!copy($src2, $dst2)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', '複製 ntcs_barcode.db 失敗');
                }

                // 時間差異提醒
                if (filemtime($src2) > filemtime($dst2)) {
                    $notice = $text['system_sync_notice'] . date("Y-m-d H:i:s.", filemtime($src2));
                    $this->logMessage($notice);
                }

                // DB欄位差異判斷
                $this->Database_Column_Diff($src2, $dst2);

                // SHA1 驗證檔案一致性
                if (sha1_file($src2) !== sha1_file($dst2)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', '.db 檔案 SHA1 不一致');
                }

                // 建立 /mnt/ramdisk/ftp/11_tmp.db 檔案
                if (!copy($dst2, $tmp2)) {
                    return $this->MiscellaneousModel->generateErrorResponse('Error', '建立 11_tmp.db 失敗');
                }

                // 使用 Modbus 通知 .db 同步完成
                $data2 = [1, 12593, 24436, 28016];
                $dataTypes2 = array_fill(0, count($data2), 'INT');
                $modbus->writeMultipleRegister(0, 506, $data2, $dataTypes2);
                $this->logMessage('Modbus 寫入 (.db)：' . implode(',', $data2));

                // 刪除 11_tmp.db 檔案
                if (file_exists($tmp2)) {
                    unlink($tmp2);
                }

                return $this->MiscellaneousModel->generateErrorResponse('Success', '' . ($text['success'] ?? 'success'));

            } catch (Exception $e) {
                $errorMessage = 'Modbus 錯誤：' . $e->getMessage();
                $trace = $e->getTraceAsString();

                $this->logMessage($errorMessage);
                $this->logMessage("Stack trace:\n" . $trace);

                file_put_contents('/tmp/modbus_error.log',
                    date('Y-m-d H:i:s') . " - " . $errorMessage . "\n" . $trace . "\n\n",
                    FILE_APPEND
                );

                return $this->MiscellaneousModel->generateErrorResponse('Success', 'SYNC ' . ($text['success'] ?? 'success'));
            }
        }

        return $this->MiscellaneousModel->generateErrorResponse('Error', '非法的參數或非支援的作業系統');
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
    private function notifyModbus($modbus, $data, $tag = "") {
        $dataTypes = array_fill(0, 16, 'INT');
        $payload = array_merge($data, array_fill(0, 16 - count($data), 0));

        $modbus->writeMultipleRegister(0, 506, $payload, $dataTypes);
        $this->logMessage("Modbus write ($tag): " . implode(',', $payload));
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



    public function GetJobBarcode()
    {
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

        $input_check = true;
        $barcode = array();
        if(!empty($_POST['del_barcode_id']) && isset($_POST['del_barcode_id'])){
            $barcode['job_id'] = $_POST['del_barcode_id'];
        }else{ 
            $input_check = false;
        }


        if($input_check){
            $res = $this->SettingModel->delete_job_barcode($barcode);

            if($res){
                $res_msg = 'del barcode :'. $barcode['job_id'][0].'success';
                $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg );

           }else{
                $res_msg = 'del barcode :'. $barcode['job_id'][0].'fail';
                $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg );
           }
        }
      
    }


    #IDAS上傳 20250624 修改
    public function iDas_Update() {

        // 1. 紀錄目前 PHP 的上傳限制，方便除錯
        $maxUpload = ini_get('upload_max_filesize');
        $postMax = ini_get('post_max_size');
        error_log("目前 upload_max_filesize: $maxUpload");
        error_log("目前 post_max_size: $postMax");

        // 2. 載入語系檔，供 $text 語系變數使用
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        // 3. 取得目前 iDAS 版本
        $iDas_Version = $this->AdminModel->Get_Das_Config('idas_version');

        // 4. 根據系統平台（Linux 或 Windows）設定根目錄與解壓縮路徑
        $file_location = (PHP_OS_FAMILY === 'Linux') ? '/var/www/html/' : $_SERVER['DOCUMENT_ROOT'] . '/';
        $extract_path = $file_location . 'extracted/';
        $main_folder = ''; // 後面會指定為解壓出來的主資料夾路徑

        try {
            // 5. 驗證上傳檔案是否存在且無錯誤
            if (empty($_FILES['file']) || $_FILES['file']['error'] !== 0) {
                $msg = empty($_FILES['file']) ? 'No file uploaded.' : 'File upload error: ' . $_FILES['file']['error'];
                return $this->sendResponse('Error', $msg);
            }

            // 6. 檢查檔案大小（限制為 30MB 以內）
            if ($_FILES['file']['size'] > 30 * 1024 * 1024) {
                return $this->sendResponse('Error', '檔案大小超過限制：30MB');
            }

            // 7. 驗證副檔名必須為 .pack
            $uploaded_filename = $_FILES['file']['name'];
            if (strtolower(pathinfo($uploaded_filename, PATHINFO_EXTENSION)) !== 'pack') {
                return $this->sendResponse('Error', '上傳檔案必須為 .pack 格式，目前為：' . $uploaded_filename);
            }

            // 8. 使用 ZipArchive 解壓縮 .pack 檔案
            $zip = new ZipArchive();
            if ($zip->open($_FILES['file']['tmp_name']) !== TRUE) {
                return $this->sendResponse('Error', '無法開啟 .pack 更新檔案');
            }

            // 9. 若解壓縮目錄不存在就先建立
            if (!is_dir($extract_path)) mkdir($extract_path, 0777, true);

            // 10. 解壓縮至指定目錄
            if (!$zip->extractTo($extract_path)) {
                $zip->close();
                return $this->sendResponse('Error', '解壓縮失敗');
            }
            $zip->close();

            // 11. 找出解壓縮後的主資料夾
            $folders = array_filter(scandir($extract_path), fn($f) => is_dir($extract_path . $f) && !in_array($f, ['.', '..']));
            if (empty($folders)) {
                return $this->sendResponse('Error', '未找到解壓縮資料夾');
            }

            // 12. 指定主資料夾與 info.json 路徑
            $main_folder = $extract_path . reset($folders);
            $info_json_url = $main_folder . "/info.json";

            // 13. 檢查 info.json 是否存在
            if (!file_exists($info_json_url)) {
                return $this->sendResponse('Error', '缺少 info.json，無法驗證更新檔');
            }

            // 14. 解析 info.json，取得更新檔版本資訊
            $verify_data = json_decode(@file_get_contents($info_json_url), true);
            if (!$verify_data || !isset($verify_data['idas_version'])) {
                return $this->sendResponse('Error', 'info.json 格式錯誤或缺少 idas_version');
            }

            // 15. 比對版本：如果更新檔比目前版本還舊，就不更新
            $match_tcc_version = $verify_data['idas_version'];
            if (version_compare($match_tcc_version, $iDas_Version, '<')) {
                return $this->sendResponse(
                    'Error',
                    "更新檔版本低於目前版本，無法更新（目前版本：$iDas_Version，更新版本：$match_tcc_version）"
                );
            }

            // 16. 將新版本寫入 config 表
            $this->AdminModel->Set_Das_Config('idas_version', $verify_data['idas_version']);

            // 17. 指定最終目標目錄（部署到 /ntcs_idas/ 下）
            $target_directory = $_SERVER['DOCUMENT_ROOT'] . '/ntcs_idas/';
            if (!is_dir($target_directory)) mkdir($target_directory, 0777, true);

            // 18. 複製解壓出來的檔案到正式目錄
            $this->copyDirectory($main_folder, $target_directory);

            // 19. 強制登出控制器使用者（安全性與更新重啟）
            $this->setting_logout();

            // 20. 成功更新回應
            return $this->sendResponse('Success', '更新成功，已將檔案移動至 ntcs_idas 目錄');

        } finally {
            // 21. 無論成功或失敗，清除主資料夾與解壓縮目錄
            if (!empty($main_folder) && is_dir($main_folder)) {
                $this->deleteDirectory($main_folder);
            }
            if (is_dir($extract_path)) {
                $this->deleteDirectory($extract_path);
            }
        }
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


    
    public function edit_feature_pwd(){

        // 載入語系檔
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        // 欲接收的欄位對應鍵名
        $fields = [
            'clear_seq' => 'clearseq_button_pwd',
            'clear'     => 'clear_button_pwd',
            'confirm'   => 'confirm_button_pwd',
            'enable'    => 'enable_button_pwd',
            'disable'   => 'disable_button_pwd',
            'skip'      => 'skip_button_pwd'
        ];

        $pwd_arr = [];
        $input_check = true;

        // 統一檢查每個欄位是否存在並賦值
        foreach ($fields as $post_key => $pwd_key) {
            if (!empty($_POST[$post_key])) {
                $pwd_arr[$pwd_key] = $_POST[$post_key];
            } else {
                $input_check = false;
            }
        }

        // 有錯就不送出
        if (!$input_check) {
            $this->MiscellaneousModel->generateErrorResponse('Error', $text['input_error'] ?? 'Input missing.');
            return;
        }

        // 寫入設定
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
        $cfg_path = $ftp_dir . "iDas.cfg";
        $lin_path = $ftp_dir . "iDas.Lin";
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

            $modbus->writeMultipleRegister(0, 506, $data, $dataTypes);
            $this->logMessage("modbus write 506 ,array = " . implode("','", $data));
            $this->logMessage("modbus status: " . $modbus->status);
            $this->logMessage("Import config end");

            // 成功回應
            $this->MiscellaneousModel->generateErrorResponse('Success', 'Import successful.');

            // 第二次寫入 Modbus
            $modbus->writeMultipleRegister(0, 462, [1], $dataTypes);

        } catch (Exception $e) {
            // 錯誤處理
            $this->logMessage('modbus write 506 fail');
            $this->logMessage('modbus status: ' . $modbus->status);
            $this->logMessage('Import config end');
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Modbus error.');
        }
    }








    public function FirmwareUpdate()
    {
        $file_location = '';
        $result = '';

        if(empty($_FILES)){
            echo json_encode(["Error" => 'no file']);
            exit();
        }


        if( PHP_OS_FAMILY == 'Linux'){
            /*$this->logMessage('firmware update start');

            // $destination = "/mnt/ramdisk/FTP/iDas.cfg";
            $destination = "/mnt/ramdisk/FTP/".$_FILES['file']['name'];
            $filenameWithoutExtension = pathinfo($_FILES['file']['name'], PATHINFO_FILENAME);
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
                    $modbus->writeMultipleRegister(0, 480, $data, $dataTypes);
                    $this->logMessage('modbus write 480 ,array = '.implode("','", $data));
                    $this->logMessage('modbus status:'.$modbus->status);
                    $this->logMessage('firmware update end');
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
            }*/

        }else{
            // $this->logMessage('Import config start');
            $file_location = $_SERVER['DOCUMENT_ROOT'].'/';
            echo json_encode(["Error" => 'not for windows']);
            exit();
        }

        echo json_encode(["message" => $result]);
    }

    function asciiToHexToInt($input) {
        // 将 ASCII 字符转换为十六进制
        $hex = bin2hex($input);

        // 将十六进制字符串按每 4 个字符为一组进行分割
        $chunks = str_split($hex, 4);

        $result = array();
        foreach ($chunks as $chunk) {
            // 将每组 4 个字符的十六进制转换为整数
            $result[] = hexdec($chunk);
        }

        return $result;
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
        // ✅ 檢查是否可同步（Modbus 工具狀態）
        $idas_result = $this->idas_check();

        if (!isset($idas_result['result']) || (int)$idas_result['result'] != 0) {
            echo json_encode([
                'result'   => false,
                'login'    => 0,
                'res_type' => 'SuccessError',
                'res_msg'  => 'Tool not disabled'
            ]);
            return;
        }else{
             echo json_encode([
                'result'   => true,
                'login'    => 1,
                'res_type' => 'Success',
                'res_msg'  => 'Tool is disabled, login status returned'
            ]);
            return;

        }

        // ✅ 檢查控制器登入狀態
        /*$Controller_Info = $this->ToolModel->GetControllerInfo();

        if (!empty($Controller_Info)) {
            $user_logIn = isset($Controller_Info['user_logIn']) ? (int)$Controller_Info['user_logIn'] : 1;

            echo json_encode([
                'result'   => true,
                'login'    => $user_logIn,  // ✅ 根據真實狀態
                'res_type' => 'Success',
                'res_msg'  => 'Tool is disabled, login status returned'
            ]);
            return;
        }*/

        // ✅ 若 Controller info 取得失敗，預設視為登入中（保守處理）
        /*echo json_encode([
            'result'   => false,
            'login'    => 1,
            'res_type' => 'Error',
            'res_msg'  => 'Controller info not found'
        ]);*/
    }    
}