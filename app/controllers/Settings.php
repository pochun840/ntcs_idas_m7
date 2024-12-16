<?php

class Settings extends Controller
{
    private $SettingModel;
    private $AdminModel;
    private $ToolModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->SettingModel = $this->model('Setting');
        $this->AdminModel = $this->model('Admin');
        $this->ToolModel = $this->model('Tool');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
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

        $iDAS_version = $idas_version['config_value'];

        $barcodes = $this->GetBarcodes();
        
        $data = array();
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
            'idas_version'   => $iDAS_version

        );

        if($isMobile){
            $this->view('setting/index_m', $data);
        }else{
            $this->view('setting/index', $data);
        }
       

    }

    /*public function job_tree(){   
     
        //select all job
        $jobs = $this->SettingModel->GetAllJobs();
        //select all sequence
        $seqs = $this->SettingModel->GetAllSequences();
        //select all step
        $steps = $this->SettingModel->GetAllSteps();

        // var_dump($steps);
        $data_array = array();
        foreach ($jobs as $key => $value) {
            $temp = ["id" => 'job_'.$value['job_id'], "parent" => "#", "text" => $value['job_name'] ];
            $data_array[] = $temp;
        }
        foreach ($seqs as $key => $value) {
            $temp = ["id" => 'job_'.$value['job_id'].'_'.'seq_'.$value['sequence_id'], "parent" => 'job_'.$value['job_id'], "text" => $value['sequence_name'] ];
            $data_array[] = $temp;
        }
        foreach ($steps as $key => $value) {
            $temp = ["id" => $value['job_id'].'_'.$value['sequence_id'].'_'.$value['step_id'], "parent" => 'job_'.$value['job_id'].'_'.'seq_'.$value['sequence_id'], "text" => $value['step_name'] ];
            $data_array[] = $temp;
        }

        echo json_encode($data_array);
    }*/

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

            if($result){// copy DB
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

    public function control_setting()
    {
        $input_check = true;

        if( !empty($_POST['control_id']) && isset($_POST['control_id'])  ){
            $con_setting['control_id'] = $_POST['control_id'];
        }else{ 
            $input_check = false; 
        }

        if( !empty($_POST['control_name']) && isset($_POST['control_name'])){
            $con_setting['control_name'] = $_POST['control_name'];
        }else{ 
            $input_check = false; 
        }
  
        if( !empty($_POST['storage_warning']) && isset($_POST['storage_warning'])){
            $con_setting['storage_warning'] = $_POST['storage_warning'];
        }else{ 
            $input_check = false; 
        }

        
        if( !empty($_POST['torque_filter']) && isset($_POST['torque_filter'])){
            $con_setting['torque_filter'] = $_POST['torque_filter'];
        }else{ 
            $input_check = false; 
        }

        if( !empty($_POST['lang_val']) && isset($_POST['lang_val'])){
            $lang_val =  $_POST['lang_val'];
            intval($lang_val);
        }else{ 
            $lang_val = 0;
        }

        $con_setting['lang_val']  = $lang_val;


        if( !empty($_POST['unit_val']) && isset($_POST['unit_val'])){
            $unit_val =  $_POST['unit_val'];
            intval($unit_val);
        }else{ 
            $unit_val = 0;
        }

        $con_setting['unit_val']  =$unit_val;

        if( !empty($_POST['counting_method']) && isset($_POST['counting_method'])){
            $con_setting['counting_method']  =  $_POST['counting_method'];
        }else{ 
            $con_setting['counting_method']  =  $_POST['counting_method']; 
        }

        if( !empty($_POST['circular_archive']) && isset($_POST['circular_archive'])){
            $con_setting['circular_archive']  =  $_POST['circular_archive'];
        }else{ 
            $con_setting['circular_archive']  =  $_POST['circular_archive']; 
        }

        if( !empty($_POST['blackout_recovery']) && isset($_POST['blackout_recovery'])){
            $con_setting['blackout_recovery']  =  $_POST['blackout_recovery'];
        }else{ 
            $con_setting['blackout_recovery']  =  $_POST['blackout_recovery']; 
        }
        if( !empty($_POST['buzzer_mode']) && isset($_POST['buzzer_mode'])  ){
            $con_setting['buzzer_mode'] = $_POST['buzzer_mode'];
        }else{ 
            $con_setting['buzzer_mode'] = $_POST['buzzer_mode'];
        }
       


        if($input_check){
          $res = $this->SettingModel->GetControllerInfo_count($con_setting['control_id']);
          if($res['count'] =="1"){
        
            $result = $this->SettingModel->Controller_Setting($con_setting);
            if($result){
                $res_msg = 'edit:'. $con_setting['control_id'].'success';
            }else{
                $res_msg = 'edit:'. $con_setting['control_id'].'fail';
            }
            echo $res_msg;

          }

        }    
    }

    public function edit_system_date()
    {
        if( PHP_OS_FAMILY == 'Linux'){
            $dateTime = $_POST["datetime"];
            // 驗證日期時間格式
            if (!preg_match("/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/", $dateTime)) {
                // echo "請提供有效的日期和時間格式（YYYY-MM-DD HH:MM:SS）。";
                echo json_encode(array('error' => '請提供有效的日期和時間格式（YYYY-MM-DD HH:MM:SS）。'));
                exit;
            }
            exec("sudo timedatectl set-ntp no");
            $rr = exec("sudo date -s ".$dateTime." ");
            exec("sudo hwclock --systohc ");

            if($rr != false){
                $this->logMessage('set date -s '.$dateTime.' success');
            }else{
                $this->logMessage('set date -s '.$dateTime.' fail');
            }


            echo json_encode(array('error' => '','result' => $rr));
            exit();
        }
    }

    public function get_system_time()
    {
        header("Content-Type: text/plain; charset=utf-8");
        
        date_default_timezone_set("GMT0");
        $systemTime = date('Y-m-d H:i:s');
        echo $systemTime;
    }

    public function firmware_reset()
    {
        // code...
    }

    public function export_sysytem_config()
    {
        
        if (PHP_OS_FAMILY == 'Linux') {
            // Linux 路徑配置
            $file_path = '/var/www/html/database/';
            $files = [
                'KLS_NTCS.Lin',  // 原來的 .Lin 檔案
                'ntcs_barcode.db', // 原來的 .db 檔案
                'ntcs_data.db', // 原來的 .db 檔案
                'ntcs_device.db' // 原來的 .db 檔案
            ];
        } else {
    
            $files = [
                '../KLS_NTCS.Lin',
                '../ntcs_barcode.db',
                '../ntcs_data.db',
                '../ntcs_device.db'
            ];
        }

        $zip = new ZipArchive();
        $zip_filename = 'data.zip'; 

        if ($zip->open($zip_filename, ZipArchive::CREATE) !== TRUE) {
            echo json_encode(array('status' => 'error', 'message' => 'Unable to create ZIP file.'));
            exit();
        }

        foreach ($files as $file) {
            $file_path = realpath($file); 

            if (file_exists($file_path)) {
            
                $file_info = pathinfo($file_path);
                $file_extension = $file_info['extension'];

                if ($file_extension === 'db') {
                    $cfgContent = file_get_contents($file_path);
                    if (strpos($cfgContent, 'table - device') !== false) {
                        $cfgContent = preg_replace('/table - device.*?\n/', '', $cfgContent);
                    }

                    $zip->addFromString($file_info['filename'] . '.cfg', $cfgContent);
                } else {
            
                    $zip->addFile($file_path, $file_info['basename']);
                }
            } else {
                echo json_encode(array('status' => 'error', 'message' => 'File not found: ' . $file));
                exit();
            }
        }

    
        $zip->close();
        header("Content-Type: application/zip");
        header("Content-Disposition: attachment; filename=" . $zip_filename);
        header("Content-Length: " . filesize($zip_filename));

        readfile($zip_filename);

        unlink($zip_filename);

        exit();
    }


    public function system_storage()
    {
        $EMMC_BASE = "/home/kls/tcc/resource/db_emmc/"; //目標目錄路徑
        if( PHP_OS_FAMILY == 'Linux'){
            $size = 0;
            foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($EMMC_BASE)) as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }

            $gigatmp = $size / 1024 / 1024 / 1024;
            $device_diskfull_percent = ceil(($gigatmp / 1.1) * 100);

            echo "{$device_diskfull_percent}";
        }else{
            echo "X";
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

    public function delete_files()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $data = json_decode(file_get_contents("php://input"), true);
            $filesToDelete = $data["files"];

            if( PHP_OS_FAMILY == 'Linux'){
                $folderPath = "/home/kls/tcc/resource/db_emmc"; // 修改為你的資料夾路徑
            }else{
                $folderPath = "../"; // 修改為你的資料夾路徑
            }

            $result = ["message" => ""];

            foreach ($filesToDelete as $fileName) {
                $filePath = $folderPath . "/" . $fileName;
                if (file_exists($filePath) && is_file($filePath)) {
                    if (unlink($filePath)) {
                        $result["message"] .= "成功刪除檔案：$fileName\n";
                        $this->logMessage('delete DB success:'. json_encode($result).'');
                    } else {
                        $result["message"] .= "無法刪除檔案：$fileName\n";
                        $this->logMessage('delete DB fail:'. json_encode($result).'');
                    }
                } else {
                    $result["message"] .= "檔案不存在：$fileName\n";
                }
            }

            echo json_encode($result);
        } else {
            echo json_encode(["message" => "無效的請求方法"]);
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



    //把  /var/www/html/database/data.db 備份為 /var/www/html/database/data_bk.db
    //並把 data_bk.db 再另存一個.db 檔名為iDas_data.db
    public function Sync_check_db() {
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }
    
        $input_check = true;
        if (!empty($_POST['argument']) && isset($_POST['argument'])) {
            $argument = $_POST['argument'];
        } else {
            $argument = '';
        }
    
        $argument = 'D2C';
        $Das_DB_Location = '/var/www/html/database/iDas_data.db'; // iDas 資料庫路徑
        $Con_DB_Location = '/var/www/html/database/data.db'; // 控制器資料庫路徑
        $Backup_DB_Location = '/var/www/html/database/data_bk.db'; // 備份資料庫路徑
    
        if (!empty($argument)) {
            if (PHP_OS_FAMILY == 'Linux' && $argument == 'D2C') {
    
                // 時間差異提醒
                if (filemtime($Con_DB_Location) > filemtime($Das_DB_Location)) {
                    $notice = $text['system_sync_notice'] . date("Y-m-d H:i:s.", filemtime($Con_DB_Location));
                }
    
                // DB 欄位差異判斷
                if (!$this->Database_Column_Diff()) {
                    $warning .= 'DB 結構不相同';
                }
    
                // 備份並複製文件
                $res_backup = $this->SettingModel->backup_CopyFile($Con_DB_Location, $Backup_DB_Location);
    
                if ($res_backup) {
                    // 複製備份文件為 iDas_data.db
                    if (file_exists($Backup_DB_Location)) {
                        if (file_exists($Das_DB_Location)) {
                            unlink($Das_DB_Location); // 刪除已存在的 iDas_data.db
                        }
                        copy($Backup_DB_Location, $Das_DB_Location); // 複製備份文件為 iDas_data.db
                        $res_msg = "同步成功";
                        $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg);
                    } else {
                        $res_msg = "備份文件不存在";
                        $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg);
                    }
                } else {
                    $res_msg = "備份錯誤";
                    $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg);
                }
    
                echo $res_msg;
            }
        }
    }
    
    
    public  function Sync_check_db_load(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }
   
        if (!empty($_POST['argument']) && isset($_POST['argument'])) {
            $argument = $_POST['argument'];
        }else{
            $argument = '';
        }

        $Das_DB_Location = '/var/www/html/database/iDas_data.db'; //idas 
        $Con_DB_Location = '/var/www/html/database/data.db'; //控制器

        if(!empty($argument)){
            if( PHP_OS_FAMILY == 'Linux' && $argument == 'C2D'){

                //時間差異提醒
                if( filemtime($Con_DB_Location) > filemtime($Das_DB_Location) ){
                    $notice = $text['system_sync_notice'].date("Y-m-d H:i:s.", filemtime($Con_DB_Location));
                }

                //DB欄位差異判斷
                if(!$this->Database_Column_Diff()){
                    $warning .= 'DB is different';
                }


                $sourceFile = '/var/www/html/database/data.db';
                $backupFile = '/var/www/html/database/data_bk.db';
                $newFile = '/var/www/html/database/iDas_data.db';

                $res  = $this->SettingModel->backupRemoveAndCopyDatabase($sourceFile, $backupFile, $newFile);
                $result = array();
                if($res){
                    $res_msg  = "SYNC Success";
                    $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg);
                }else{
                    $res_msg  = "SYNC Error";
                    $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg);
                }

            }
        }
    }
        


    
    
    //get barcode
    public function GetBarcodes()
    {
        $barcodes = $this->SettingModel->GetAllBarcodes();

        return $barcodes;
    }

    public function show_Barcodes(){

        $isMobile = $this->isMobileCheck();
        $barcode_list = '';
        $barcodes = $this->SettingModel->GetAllBarcodes();
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
                    $barcode_list .= '<tr>';
    
                    echo $barcode_list;
                }

            }
          

        }

    }

    //update barcode
    public function Update_Barcode()
    {
        $input_check = true;
        $barcode = array();
        //$error_message = '';
        if( !empty($_POST['barcode_name']) && isset($_POST['barcode_name'])  ){
            $barcode['barcode_name'] = $_POST['barcode_name'];
            /*if (strlen($barcode['barcode_name']) > 54) {
                $input_check = false;
            }*/
        }else{ 
            $input_check = false;
        }
        if( !empty($_POST['barcode_from']) && isset($_POST['barcode_from'])  ){
            $barcode['barcode_range_from'] = $_POST['barcode_from'];
        }else{ 
            $input_check = false;
        }
        if( !empty($_POST['barcode_count']) && isset($_POST['barcode_count'])  ){
            $barcode['barcode_range_count'] = $_POST['barcode_count'];
        }else{ 
            $input_check = false;
        }
        
        if( isset($_POST['barcode_job'])  ){
            $barcode['barcode_job'] = $_POST['barcode_job'];
        }else{ 
            $input_check = false;
        }

        if( isset($_POST['barcode_mode'])  ){
            $barcode['barcode_mode'] = $_POST['barcode_mode'];
        }else{ 
            $input_check = false;
        }

        if( isset($_POST['barcode_seq'])  ){
            $barcode['barcode_seq'] = $_POST['barcode_seq'];
        }else{ 
            $barcode['barcode_seq'] = "";
        }
        
        
        if($input_check){
            $barcode_result = $this->SettingModel->Update_Barcode($barcode);
            if($barcode_result){
                $res_msg = 'edit barcode :'. $barcode['barcode_name'].' success';
            }else{
                $res_msg = 'edit barcode :'. $barcode['barcode_name'].' fail';
            }
            echo $res_msg;    
        }
    }

    public function GetJobSeq()
    {
        $input_check = true;
        $error_message = '';
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $job_id = $_POST['job_id'];
        }else{ 
            $input_check = false;
            $error_message .= "job_id,";
        }

        if($input_check){

 
            $result = $this->SettingModel->get_seq_list($job_id);
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
                $res_msg = 'delete  barcode :'. $barcode['job_id'].' success';
           }else{
                $res_msg = 'delete  barcode :'. $barcode['job_id'].' fail';
           }
           echo $res_msg;
        }
      
    }

    public function iDas_Update()
    {
        $filename = 'update_package.pack';
        $file_location = '';
        $message = '';
        if( PHP_OS_FAMILY == 'Linux'){
            $file_location = '/mnt/ramdisk/';
        }else{//windows暫不考慮升級，可能整包升級
            $file_location = $_SERVER['DOCUMENT_ROOT'].'/';
            echo json_encode(["message" => 'not for windows']);
            exit();
        }

        if(empty($_FILES)){
            echo json_encode(["message" => 'no file']);
            exit();
        }

        if ( 0 < $_FILES['file']['error'] ) {
            echo json_encode(["message" => $_FILES['file']['error']]);
            exit();
        } else {
            //將檔案移到指定位置
            $result =  move_uploaded_file($_FILES['file']['tmp_name'], $file_location . $filename);
        }

        $extract_result = $this->Extract_File($file_location,$filename);
        $file_path = $file_location.'package_temp/package/verify';
        
        if (file_exists($file_path) && $extract_result) {
            $str = file_get_contents($file_path); //將整個檔案內容讀入到一個字串中
            $str = str_replace("\r\n", "<br />", $str);
            $verify_data = json_decode($str,true);
            $result = $verify_data;

            $package_version = $verify_data['Package_Version'];
            $match_gtcs_version = $verify_data['Match_GTCS_Version'];
            $match_gtcs_db_version = $verify_data['Match_GTCS_DB_Version'];

            $current_device_info = $this->SettingModel->get_update_info();

            //gtcs與gtcs db版本與更新包相符才會將檔案升級
            if( $match_gtcs_version == $current_device_info['device_version'] && $match_gtcs_db_version == $current_device_info['tcscondb_version'] ){
                if( PHP_OS_FAMILY == 'Linux'){
                    $destination = '/var/www/html/tcc/';
                }else{
                    $destination = $file_location.'/tcc';
                }
                exec("sudo chmod 777 -R /var/www/html/tcc");

                $this->copyFolder($file_location.'/package_temp/package/das',$destination); //複製資料夾
                
                //update current idas version
                $this->SettingModel->update_idas_vesrion($package_version);
                $this->SettingModel->update_idas_match_gtcs_app_version($match_gtcs_version);
                //update file permissions
                exec("sudo chmod 777 -R /var/www/html/das");

            }else{
                $message = 'version not match';
            
            }

            $this->deleteFolder($file_location.'package_temp'); //刪除資料夾
            unlink($file_location.''.$filename); //刪除檔案

        }else{
            $this->deleteFolder($file_location.'/package_temp'); //刪除資料夾
            unlink($file_location.''.$filename); //刪除檔案
            $message = 'wrong file';
        }

        echo json_encode(["message" => $message]);
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
        
        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }
        $global_downshift_arr = array();
        $input_check = true;
        if(!empty($_POST['global_downshift_torque']) && isset($_POST['global_downshift_torque'])){
            $global_downshift_arr['global_downshift_torque'] = $_POST['global_downshift_torque'];
        }else{ 
            $input_check = false;
        }

        if(!empty($_POST['global_downshift_speed']) && isset($_POST['global_downshift_speed'])){
            $global_downshift_arr['global_downshift_speed'] = $_POST['global_downshift_speed'];
        }else{ 
            $input_check = false;
        }

        if($input_check){
            $result = $this->SettingModel->edit_feature_global_downshift($global_downshift_arr);

            if( $result){
                $res_msg = $text['success'];
                $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg );
            }else{
                $res_msg = $text['fail'];
                $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg );
            }

        }
    }


    public function edit_background_color(){
        
        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $color_arr = array();
        $input_check = true;
        if(!empty($_POST['okjobcolor']) && isset($_POST['okjobcolor'])){
            $color_arr['okjobcolor'] = $_POST['okjobcolor'];
        }else{ 
            $input_check = false;
        }

        if(!empty($_POST['okseqcolor']) && isset($_POST['okseqcolor'])){
            $color_arr['okseqcolor'] = $_POST['okseqcolor'];
        }else{ 
            $input_check = false;
        }

        if($input_check){
            $result = $this->SettingModel->edit_feature_color($color_arr);

            if( $result){
                $res_msg = $text['success'];
                $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg );
            }else{
                $res_msg = $text['fail'];
                $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg );
            }

        }




    }
    public function edit_feature_pwd(){

        
        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }


        $input_check = true;
        $pwd_arr = array();

        if(!empty($_POST['clear_seq']) && isset($_POST['clear_seq'])){
            $pwd_arr['clearseq_button_pwd'] = $_POST['clear_seq'];
        }else{ 
            $input_check = false;
        }

        if(!empty($_POST['clear']) && isset($_POST['clear'])){
            $pwd_arr['clear_button_pwd'] = $_POST['clear'];
        }else{ 
            $input_check = false;
        }

        if(!empty($_POST['confirm']) && isset($_POST['confirm'])){
            $pwd_arr['confirm_button_pwd'] = $_POST['confirm'];
        }else{ 
            $input_check = false;
        }

        if(!empty($_POST['enable']) && isset($_POST['enable'])){
            $pwd_arr['enable_button_pwd'] = $_POST['enable'];
        }else{ 
            $input_check = false;
        }

        if(!empty($_POST['disable']) && isset($_POST['disable'])){
            $pwd_arr['disable_button_pwd'] = $_POST['disable'];
        }else{ 
            $input_check = false;
        }

        if(!empty($_POST['skip']) && isset($_POST['skip'])){
            $pwd_arr['skip_button_pwd'] = $_POST['skip'];
        }else{ 
            $input_check = false;
        }

        if($input_check){
            $result = $this->SettingModel->edit_feature_pwd($pwd_arr);

            if( $result){
                $res_msg = $text['success'];
                $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg );
            }else{
                $res_msg = $text['fail'];
                $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg );
            }

        }


    }


    public function Import_Config()
    {
        $file_location = '';
        $result = '';

        if(empty($_FILES)){
            echo json_encode(["Error" => 'no file']);
            exit();
        }


        if( PHP_OS_FAMILY == 'Linux'){
            /*$this->logMessage('Import config start');

            $destination = "/mnt/ramdisk/FTP/iDas.cfg";
            //將檔案移到指定位置
            $result =  move_uploaded_file($_FILES['file']['tmp_name'], $destination);

            if ($result) {
                require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
                $modbus = new ModbusMaster("127.0.0.1", "TCP");
                try {
                    $modbus->port = 502;
                    $modbus->timeout_sec = 10;
                    $data = array(1, 26948, 24947);
                    $dataTypes = array("INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT");

                    // FC 16
                    $modbus->writeMultipleRegister(0, 506, $data, $dataTypes);
                    $this->logMessage('modbus write 506 ,array = '.implode("','", $data));
                    $this->logMessage('modbus status:'.$modbus->status);
                    $this->logMessage('Import config end');
                    echo json_encode(array('error' => ''));
                    exit();

                } catch (Exception $e) {
                    // Print error information if any
                    // echo $modbus;
                    // echo $e;
                    $this->logMessage('modbus write 506 fail');
                    $this->logMessage('modbus status:'.$modbus->status);
                    $this->logMessage('Import config end');
                    echo json_encode(array('error' => 'modbus error'));
                    exit();
                }
            } else {
                $this->logMessage('copy db error');
                $this->logMessage('Import config end');
                echo json_encode(array('error' => 'copy db error'));
                exit();
            }*/

        }else{
            // $this->logMessage('Import config start');
            $destination = "../KLS_NTCS_IDAS.Lin";
            $result =  move_uploaded_file($_FILES['file']['tmp_name'], $destination);
            if($result){
                echo json_encode(["error" => '']);
                exit();
            }else{
                echo json_encode(["error" => 'fail']);
                exit();
            }            
        }

        echo json_encode(["message" => $result]);
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
    function Database_Column_Diff()
    {
        $dbPath1 = '/var/www/html/database/iDas_data.db';
        $dbPath2 = '/var/www/html/database/data.db';

        if ($this->validateTableStructure($dbPath1, $dbPath2)) {
            echo "两个数据库的表结构相同。\n";
        } else {
            echo "两个数据库的表结构不同。\n";
            return false;
        }

        //確認idas的設定db沒有null
        $result = $this->checkForNullValues($dbPath1);
        if(!$result){
            return false;
        }else{
            return true;
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
}