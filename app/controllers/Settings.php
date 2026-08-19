<?php
/*
 * Single-codebase platform switch.
 * /home/kls/upgrade/icontroller = 1 -> i-controller implementation
 * 0 / missing / invalid -> NTCS implementation
 */
if (idas_is_icontroller()) {
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
        $network_setting = $this->SettingModel->GetNetworkSetting();
        $network_setting['current_ip'] = $this->getCurrentNetworkIp();
        if (empty($network_setting['static_ip']) && !empty($network_setting['current_ip'])) {
            $network_setting['static_ip'] = $network_setting['current_ip'];
        }
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
            'network_setting' => $network_setting,
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

    /**
     * PC 版「網路設定」儲存 API。
     * 儲存到 Controller / iDAS 的 ntcs_device_test.wifi，實際網路切換於重新啟動後生效。
     */
    public function save_network_setting()
    {
        header('Content-Type: application/json; charset=utf-8');

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        $msg = function ($key, $fallback) use (&$text) {
            return isset($text[$key]) ? (string)$text[$key] : $fallback;
        };

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            echo json_encode([
                'ok' => false,
                'message' => $msg('network_invalid_request', 'Invalid request.'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $mode = isset($_POST['network_mode']) ? (int)$_POST['network_mode'] : -1;
        $staticIp = trim((string)($_POST['static_ip'] ?? ''));
        $mask = trim((string)($_POST['mask'] ?? ''));
        $gateway = trim((string)($_POST['gateway'] ?? ''));
        $portRaw = trim((string)($_POST['server_port'] ?? ''));

        // Server Port 必須與目前通訊協議一致。
        // 0 = TCP  -> 502
        // 2 = OP   -> 4545
        // 1 = RTU  -> 保留前端設定值
        $controllerInfo = (array)($this->SettingModel->GetControllerInfo() ?? []);
        $controllerModbusType = isset($controllerInfo['modbus_type'])
            ? (int)$controllerInfo['modbus_type']
            : IDAS_PROTOCOL_TCP;

        $policyPort = idas_protocol_server_port($controllerModbusType, null);
        if ($policyPort !== null) {
            $portRaw = (string)$policyPort;
        }

        if (!in_array($mode, [1, 2], true)) {
            echo json_encode(['ok' => false, 'message' => $msg('network_invalid_mode', 'Invalid network mode.')], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($portRaw === '' || !ctype_digit($portRaw)) {
            echo json_encode(['ok' => false, 'message' => $msg('network_invalid_port', 'Server port must be between 1 and 65535.')], JSON_UNESCAPED_UNICODE);
            return;
        }
        $port = (int)$portRaw;
        if ($port < 1 || $port > 65535) {
            echo json_encode(['ok' => false, 'message' => $msg('network_invalid_port', 'Server port must be between 1 and 65535.')], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Static 模式必須有完整的 IPv4 設定；DHCP 模式允許保留空的備用 Static 設定。
        if ($mode === 2) {
            if (!filter_var($staticIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                echo json_encode(['ok' => false, 'message' => $msg('network_invalid_static_ip', 'Invalid static IP address.')], JSON_UNESCAPED_UNICODE);
                return;
            }
            if (!filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !$this->isValidIpv4Netmask($mask)) {
                echo json_encode(['ok' => false, 'message' => $msg('network_invalid_mask', 'Invalid subnet mask.')], JSON_UNESCAPED_UNICODE);
                return;
            }
            if (!filter_var($gateway, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                echo json_encode(['ok' => false, 'message' => $msg('network_invalid_gateway', 'Invalid gateway IP address.')], JSON_UNESCAPED_UNICODE);
                return;
            }
        } else {
            if ($staticIp !== '' && !filter_var($staticIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                echo json_encode(['ok' => false, 'message' => $msg('network_invalid_static_ip', 'Invalid static IP address.')], JSON_UNESCAPED_UNICODE);
                return;
            }
            if ($mask === '') {
                $mask = '255.255.255.0';
            } elseif (!filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) || !$this->isValidIpv4Netmask($mask)) {
                echo json_encode(['ok' => false, 'message' => $msg('network_invalid_mask', 'Invalid subnet mask.')], JSON_UNESCAPED_UNICODE);
                return;
            }
            if ($gateway !== '' && !filter_var($gateway, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                echo json_encode(['ok' => false, 'message' => $msg('network_invalid_gateway', 'Invalid gateway IP address.')], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        $result = $this->SettingModel->SaveNetworkSetting([
            'mode' => $mode,
            'static_ip' => $staticIp,
            'mask' => $mask,
            'gateway' => $gateway,
            'port' => $port,
        ]);

        if (empty($result['ok'])) {
            $this->logMessage('[NetworkSetting] save failed: ' . (string)($result['error'] ?? 'unknown'));
            echo json_encode([
                'ok' => false,
                'message' => $msg('network_save_failed', 'Unable to save network settings.'),
                'error' => (string)($result['error'] ?? ''),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }

        $changed = !empty($result['changed']);
        $this->logMessage(
            '[NetworkSetting] save success mode=' . $mode .
            ', static_ip=' . $staticIp .
            ', port=' . $port .
            ', mask=' . $mask .
            ', gateway=' . $gateway .
            ', changed=' . ($changed ? '1' : '0')
        );

        $restartResult = [
            'scheduled' => false,
            'message' => '',
        ];

        if ($changed && PHP_OS_FAMILY === 'Linux') {
            $restartResult = $this->scheduleModbusTypeLinuxRestart();

            $this->logMessage(
                '[NetworkSetting] auto restart scheduled=' .
                (!empty($restartResult['scheduled']) ? '1' : '0') .
                ', message=' . (string)($restartResult['message'] ?? '')
            );
        }

        $restartScheduled = !empty($restartResult['scheduled']);

        if ($changed) {
            if ($restartScheduled) {
                $message = $msg(
                    'network_save_success_auto_reboot',
                    'Network settings saved. The controller will restart automatically in 5 seconds.'
                );
            } else {
                $message = $msg(
                    'network_save_success_reboot_failed',
                    'Network settings saved, but automatic restart could not be scheduled. Please restart the controller manually.'
                );
            }
        } else {
            $message = $msg('network_no_change', 'No network setting changes.');
        }

        echo json_encode([
            'ok' => true,
            'changed' => $changed,
            'requires_reboot' => $changed,
            'restart_scheduled' => $restartScheduled,
            'restart_delay_seconds' => $restartScheduled ? 5 : 0,
            'restart_message' => (string)($restartResult['message'] ?? ''),
            'mode' => $mode,
            'static_ip' => $staticIp,
            'server_port' => $port,
            'wifi' => (string)($result['wifi'] ?? ''),
            'message' => $message,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function getCurrentNetworkIp()
    {
        $candidates = [];

        if (!empty($_SERVER['SERVER_ADDR'])) {
            $candidates[] = trim((string)$_SERVER['SERVER_ADDR']);
        }

        if (function_exists('gethostname')) {
            $host = @gethostname();
            if ($host) {
                $resolved = @gethostbyname($host);
                if ($resolved && $resolved !== $host) {
                    $candidates[] = trim((string)$resolved);
                }
            }
        }

        if (function_exists('shell_exec')) {
            $hostnameI = @shell_exec('hostname -I 2>/dev/null');
            if (is_string($hostnameI) && trim($hostnameI) !== '') {
                foreach (preg_split('/\\s+/', trim($hostnameI)) as $ip) {
                    $candidates[] = trim((string)$ip);
                }
            }
        }

        $fallback = '';
        foreach (array_unique($candidates) as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }
            if ($fallback === '') {
                $fallback = $ip;
            }
            if (strpos($ip, '127.') !== 0) {
                return $ip;
            }
        }

        return $fallback;
    }

    private function isValidIpv4Netmask($mask)
    {
        if (!filter_var($mask, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $long = ip2long($mask);
        if ($long === false) {
            return false;
        }

        $unsigned = sprintf('%u', $long);
        $bits = str_pad(decbin((int)$unsigned), 32, '0', STR_PAD_LEFT);

        // 合法 mask 必須是連續的 1，後面接連續的 0。
        return (bool)preg_match('/^1*0*$/', $bits);
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

        // ===== 舊/新 ID 讀取 =====
        // 前端 control_id_new='' 的語意是「ID 沒有變更」，不能解讀成 NULL。
        $control_id_raw = isset($_POST['control_id']) ? trim((string)$_POST['control_id']) : '';
        $control_id_old = ($control_id_raw === '') ? null : $control_id_raw;

        $control_id_new_raw = isset($_POST['control_id_new'])
            ? trim((string)$_POST['control_id_new'])
            : '';
        $control_id_new = ($control_id_new_raw === '')
            ? $control_id_old
            : $control_id_new_raw;

        // Device ID 必須是 1~255 的整數。
        foreach (['old' => $control_id_old, 'new' => $control_id_new] as $idRole => $idValue) {
            if ($idValue === null || !ctype_digit((string)$idValue)) {
                $input_check = false;
                continue;
            }
            $idInt = (int)$idValue;
            if ($idInt < 1 || $idInt > 255) {
                $input_check = false;
            }
        }

        if ($control_id_old !== null) $control_id_old = (int)$control_id_old;
        if ($control_id_new !== null) $control_id_new = (int)$control_id_new;

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
            'modbus_type',
            'global_downshift_torque',
            'global_downshift_speed'
        ];
        foreach ($optional_fields as $field) {
            $con_setting[$field] = $get($field, '');
        }

        /*
         * 通訊協議類型：
         * 0 = TCP
         * 1 = RTU
         * 2 = OP
         */
        $modbusTypeRaw = (string)($con_setting['modbus_type'] ?? '0');

        if (!in_array($modbusTypeRaw, ['0', '1', '2'], true)) {
            $input_check = false;
        }

        // 基本數值型別正規化，避免字串直接寫入 DB。
        foreach ([
            'counting_method',
            'circular_archive',
            'blackout_recovery',
            'buzzer_mode',
            'modbus_type',
            'lang_val',
            'unit_val'
        ] as $nf) {
            if (
                isset($con_setting[$nf])
                && $con_setting[$nf] !== ''
            ) {
                $con_setting[$nf] = (int)$con_setting[$nf];
            }
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

        /*
         * 儲存前先取得目前資料庫中的 Communication protocol。
         *
         * 0 = TCP
         * 1 = RTU
         * 2 = OP
         *
         * Device ID 或通訊協議任一真正改變時，Linux 都需要重新啟動。
         */
        $currentControllerInfo = (array)(
            $this->SettingModel->GetControllerInfo()
            ?? []
        );

        $oldModbusType = (int)(
            $currentControllerInfo['modbus_type']
            ?? 0
        );

        $newModbusType = (int)(
            $con_setting['modbus_type']
            ?? 0
        );

        $modbusTypeChanged = (
            $oldModbusType !== $newModbusType
        );

        // ===== 執行更新 =====
        $ok = $this->SettingModel->Controller_Setting(
            $con_setting
        );

        if (!$ok) {
            $this->respondControllerSettingJson([
                'success' => false,
                'res_type' => $text['fail'] ?? 'Fail',
                'res_msg' => $text['fail'] ?? 'Fail',
                'device_id_changed' => false,
                'modbus_type_changed' => false,
                'controller_device_db_synced' => false,
                'restart_required' => false,
                'restart_scheduled' => false
            ], 500);
        }

        /*
         * 通訊協議切換時，同步更新 Network wifi 內的 Server Port。
         * TCP(0) => 502
         * OP(2)  => 4545
         * RTU(1) 不強制修改。
         */
        $networkPortSynced = true;
        $networkPortSyncError = '';

        $requiredPort = idas_protocol_server_port($newModbusType, null);

        if ($requiredPort !== null) {
            $networkSetting = (array)($this->SettingModel->GetNetworkSetting() ?? []);

            if ((int)($networkSetting['port'] ?? 0) !== $requiredPort) {
                $networkSave = $this->SettingModel->SaveNetworkSetting([
                    'mode' => (int)($networkSetting['mode'] ?? 1),
                    'static_ip' => (string)($networkSetting['static_ip'] ?? ''),
                    'mask' => (string)($networkSetting['mask'] ?? '255.255.255.0'),
                    'gateway' => (string)($networkSetting['gateway'] ?? ''),
                    'port' => $requiredPort,
                ]);

                $networkPortSynced = !empty($networkSave['ok']);
                $networkPortSyncError = (string)($networkSave['error'] ?? '');

                $this->logMessage(
                    '[ControllerSetting] protocol port sync modbus_type='
                    . $newModbusType
                    . ', port=' . $requiredPort
                    . ', success=' . ($networkPortSynced ? '1' : '0')
                    . ($networkPortSyncError !== '' ? ', error=' . $networkPortSyncError : '')
                );
            }
        }

        if (!$networkPortSynced) {
            /*
             * SaveNetworkSetting() already rolls both wifi DBs back internally.
             * Restore Controller identity/protocol too, so the request cannot
             * end in "protocol changed but required port was not persisted".
             */
            $rollbackSetting = $con_setting;
            $rollbackSetting['control_id'] = $control_id_new;
            $rollbackSetting['control_id_new'] = $control_id_old;
            $rollbackSetting['modbus_type'] = $oldModbusType;

            $identityRollbackOk = $this->SettingModel->Controller_Setting(
                $rollbackSetting
            );

            $this->logMessage(
                '[ControllerSetting] network port sync failed; identity rollback='
                . ($identityRollbackOk ? 'success' : 'failed')
                . ', error=' . $networkPortSyncError
            );

            $this->respondControllerSettingJson([
                'success' => false,
                'res_type' => $text['fail'] ?? 'Fail',
                'res_msg' => 'Protocol/Network port synchronization failed.',
                'network_port_synced' => false,
                'network_port_sync_error' => $networkPortSyncError,
                'identity_rollback_ok' => $identityRollbackOk,
                'restart_required' => false,
                'restart_scheduled' => false
            ], 500);
        }

        $restartRequired = (
            PHP_OS_FAMILY === 'Linux'
            && ($is_change_id || $modbusTypeChanged)
        );

        $restartResult = [
            'scheduled' => false,
            'message' => ''
        ];

        if ($restartRequired) {
            $restartResult =
                $this->scheduleModbusTypeLinuxRestart();

            $this->logMessage(
                sprintf(
                    'Controller identity changed: device_id %s -> %s, modbus_type %d -> %d; id_changed=%s; protocol_changed=%s; restart scheduled=%s; message=%s',
                    (string)$control_id_old,
                    (string)$control_id_new,
                    $oldModbusType,
                    $newModbusType,
                    $is_change_id ? 'true' : 'false',
                    $modbusTypeChanged ? 'true' : 'false',
                    !empty($restartResult['scheduled']) ? 'true' : 'false',
                    (string)($restartResult['message'] ?? '')
                )
            );
        }

        $this->respondControllerSettingJson([
            'success' => true,
            'res_type' => $text['success'] ?? 'Success',
            'res_msg' => $text['success'] ?? 'Success',
            'old_device_id' => $control_id_old,
            'new_device_id' => $control_id_new,
            'device_id_changed' => $is_change_id,
            'old_modbus_type' => $oldModbusType,
            'new_modbus_type' => $newModbusType,
            'modbus_type_changed' => $modbusTypeChanged,
            'network_port_synced' => $networkPortSynced,
            'network_port_sync_error' => $networkPortSyncError,
            'server_port' => idas_protocol_server_port($newModbusType, null),
            'controller_device_db_synced' => (
                PHP_OS_FAMILY !== 'Linux'
                || $ok
            ),
            'controller_device_db_path' => (
                PHP_OS_FAMILY === 'Linux'
                    ? '/home/kls/NTCS7/ntcs_device.db'
                    : ''
            ),
            'restart_required' => $restartRequired,
            'restart_scheduled' => !empty(
                $restartResult['scheduled']
            ),
            'restart_delay_seconds' => $restartRequired
                ? 5
                : 0,
            'restart_message' => (string)(
                $restartResult['message']
                ?? ''
            )
        ]);
    }

    private function scheduleModbusTypeLinuxRestart(): array
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return [
                'scheduled' => false,
                'message' => 'Restart is only supported on Linux.'
            ];
        }

        if (!function_exists('exec')) {
            return [
                'scheduled' => false,
                'message' => 'PHP exec() is not available.'
            ];
        }

        $disabledFunctions = array_map(
            'trim',
            explode(
                ',',
                (string)ini_get('disable_functions')
            )
        );

        if (in_array('exec', $disabledFunctions, true)) {
            return [
                'scheduled' => false,
                'message' => 'PHP exec() is disabled.'
            ];
        }

        $sudo = '';

        foreach (
            [
                '/usr/bin/sudo',
                '/bin/sudo'
            ] as $candidate
        ) {
            if (is_executable($candidate)) {
                $sudo = $candidate;
                break;
            }
        }

        if ($sudo === '') {
            return [
                'scheduled' => false,
                'message' => 'sudo command was not found.'
            ];
        }

        $systemctl = '';

        foreach (
            [
                '/usr/bin/systemctl',
                '/bin/systemctl'
            ] as $candidate
        ) {
            if (is_executable($candidate)) {
                $systemctl = $candidate;
                break;
            }
        }

        if ($systemctl === '') {
            return [
                'scheduled' => false,
                'message' => 'systemctl command was not found.'
            ];
        }

        /*
         * 先確認 www-data 有免密碼執行 systemctl reboot 的權限。
         * 若沒有權限，設定仍會儲存，但不會假裝排程成功。
         */
        $permissionCommand = escapeshellarg($sudo)
            . ' -n -l '
            . escapeshellarg($systemctl)
            . ' reboot 2>&1';

        $permissionOutput = [];
        $permissionExitCode = 1;

        exec(
            $permissionCommand,
            $permissionOutput,
            $permissionExitCode
        );

        if ($permissionExitCode !== 0) {
            return [
                'scheduled' => false,
                'message' => !empty($permissionOutput)
                    ? implode(' ', $permissionOutput)
                    : 'www-data is not allowed to reboot Linux.'
            ];
        }

        /*
         * PHP 啟動獨立背景程序：
         * 1. 先等待 5 秒。
         * 2. 再透過 sudo 執行 systemctl reboot。
         *
         * nohup + 背景執行可避免 HTTP Request 結束時程序被中止。
         */
        $delayedCommand = 'sleep 5; '
            . escapeshellarg($sudo)
            . ' -n '
            . escapeshellarg($systemctl)
            . ' reboot';

        $backgroundCommand = 'nohup /bin/sh -c '
            . escapeshellarg($delayedCommand)
            . ' >/dev/null 2>&1 < /dev/null &';

        $output = [];
        $exitCode = 1;

        exec(
            $backgroundCommand,
            $output,
            $exitCode
        );

        if ($exitCode !== 0) {
            return [
                'scheduled' => false,
                'message' => !empty($output)
                    ? implode(' ', $output)
                    : 'Unable to start the PHP reboot task.'
            ];
        }

        return [
            'scheduled' => true,
            'message' => 'Linux restart scheduled in 5 seconds by PHP.'
        ];
    }

    private function respondControllerSettingJson(
        array $payload,
        int $statusCode = 200
    ): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header(
                'Content-Type: application/json; charset=utf-8'
            );
            header(
                'Cache-Control: no-store, no-cache, must-revalidate'
            );
        }

        echo json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        exit;
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

        $respond = static function(array $payload, int $status = 200): void {
            http_response_code($status);
            echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        };

        if (PHP_OS_FAMILY !== 'Linux') {
            $respond(['error' => 'Firmware update is only supported on Linux.'], 400);
        }

        if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
            // When post_max_size is exceeded PHP discards both $_POST and $_FILES.
            // Detect that case so a large, valid firmware is not misreported as "no file selected".
            $contentLength = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
            $postMaxRaw = (string)ini_get('post_max_size');
            $iniBytes = static function(string $value): int {
                $value = trim($value);
                if ($value === '') return 0;
                $last = strtolower(substr($value, -1));
                $number = (float)$value;
                switch ($last) {
                    case 'g': $number *= 1024;
                    case 'm': $number *= 1024;
                    case 'k': $number *= 1024;
                }
                return (int)$number;
            };
            $postMaxBytes = $iniBytes($postMaxRaw);

            if ($contentLength > 0 && $postMaxBytes > 0 && $contentLength > $postMaxBytes) {
                $respond([
                    'error' => 'Firmware upload exceeds the server POST limit (' . $postMaxRaw . ').',
                    'code' => 'POST_TOO_LARGE',
                    'post_max_size' => $postMaxRaw,
                ], 413);
            }

            $respond(['error' => 'Please select a firmware ZIP file.'], 400);
        }

        $upload = $_FILES['file'];
        $uploadError = isset($upload['error']) ? (int)$upload['error'] : UPLOAD_ERR_NO_FILE;
        if ($uploadError !== UPLOAD_ERR_OK) {
            if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
                $respond([
                    'error' => 'Firmware upload exceeds the server upload limit (' . (string)ini_get('upload_max_filesize') . ').',
                    'code' => 'UPLOAD_TOO_LARGE',
                    'upload_max_filesize' => (string)ini_get('upload_max_filesize'),
                ], 413);
            }
            $respond(['error' => 'Firmware upload failed (code ' . $uploadError . ').'], 400);
        }

        $tmpName = (string)($upload['tmp_name'] ?? '');
        $originalName = basename((string)($upload['name'] ?? ''));
        if ($tmpName === '' || $originalName === '' || !is_uploaded_file($tmpName)) {
            $respond(['error' => 'Invalid firmware upload.'], 400);
        }

        if (strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION)) !== 'zip') {
            $respond(['error' => 'Firmware file must be a .zip file.'], 400);
        }

        // Validate the ZIP before notifying Controller.
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $zipResult = $zip->open($tmpName, ZipArchive::CHECKCONS);
            if ($zipResult !== true) {
                $respond(['error' => 'Invalid or corrupted firmware ZIP file.'], 400);
            }
            $zip->close();
        }

        $device_id = isset($this->deviceId) ? (int)$this->deviceId : 1;
        $unitId = ($device_id >= 1 && $device_id <= 255) ? $device_id : 1;
        $isOp = $this->is_op_protocol_enabled();
        $protocolName = $isOp ? 'OP' : 'MODBUS TCP';

        $this->logMessage('[FirmwareUpdate] start original=' . $originalName . ', protocol=' . $protocolName . ', unitId=' . $unitId);

        $ftpDir = '/mnt/ramdisk/ftp';
        if (!is_dir($ftpDir) || !is_writable($ftpDir)) {
            $respond(['error' => 'Firmware upload directory is not writable: ' . $ftpDir], 500);
        }

        /*
         * Firmware filename rule:
         * - Keep the original uploaded ZIP filename. Do not create FW_xxx aliases.
         * - Controller receives the filename stem in R481~R500 (20 registers = 40 bytes).
         * - The .zip extension is stored in FTP but is not written to the filename registers.
         * - Spaces are allowed (for example: NTCS7_ALL_V0115 1.zip).
         */
        $maxControllerStemBytes = 40;
        $originalStem = (string)pathinfo($originalName, PATHINFO_FILENAME);

        if ($originalStem === '') {
            $respond(['error' => 'Firmware filename is invalid.'], 400);
        }

        // R481~R500 can carry at most 40 bytes of the filename stem.
        // Preserve the original name exactly; reject an unsupported name instead of silently renaming it.
        if (strlen($originalStem) > $maxControllerStemBytes) {
            $respond([
                'error' => 'Firmware filename is too long. The filename before .zip must be 40 bytes or fewer.',
                'code' => 'FIRMWARE_FILENAME_TOO_LONG',
                'max_stem_bytes' => $maxControllerStemBytes,
                'filename' => $originalName,
            ], 400);
        }

        if (preg_match('/[^\x20-\x7E]/', $originalStem)) {
            $respond([
                'error' => 'Firmware filename must use printable ASCII characters.',
                'code' => 'FIRMWARE_FILENAME_INVALID',
                'filename' => $originalName,
            ], 400);
        }

        $safeStem = $originalStem;
        $controllerFileName = $originalName;
        $destination = $ftpDir . '/' . $controllerFileName;

        if (file_exists($destination) && !@unlink($destination)) {
            $respond(['error' => 'Unable to replace existing firmware file in FTP directory.'], 500);
        }

        if (!move_uploaded_file($tmpName, $destination)) {
            $this->logMessage('[FirmwareUpdate] move_uploaded_file failed destination=' . $destination);
            $respond(['error' => 'Firmware upload failed.'], 500);
        }
        @chmod($destination, 0664);

        $name_int16 = $this->asciiToHexToInt_temp($safeStem);
        // R481~R500 = 20 registers = 40 bytes. Pad unused registers with zero.
        $name_int16 = array_slice(array_pad($name_int16, 20, 0), 0, 20);

        // Retry wrapper. Do not change Controller.php OP core; keep this firmware-specific.
        $writeRegistersWithRetry = function(int $startAddress, array $values, int $attempts = 3) use ($unitId): bool {
            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    if ($this->protocol_write_registers($unitId, $startAddress, $values)) {
                        return true;
                    }
                } catch (Throwable $e) {
                    $this->logMessage('[FirmwareUpdate] write registers exception addr=' . $startAddress . ', attempt=' . $attempt . ', error=' . $e->getMessage());
                }
                if ($attempt < $attempts) {
                    usleep(200000 * $attempt);
                }
            }
            return false;
        };

        $writeRegisterWithRetry = function(int $address, int $value, int $attempts = 3) use ($unitId): bool {
            for ($attempt = 1; $attempt <= $attempts; $attempt++) {
                try {
                    if ($this->protocol_write_register($unitId, $address, $value)) {
                        return true;
                    }
                } catch (Throwable $e) {
                    $this->logMessage('[FirmwareUpdate] write register exception addr=' . $address . ', attempt=' . $attempt . ', error=' . $e->getMessage());
                }
                if ($attempt < $attempts) {
                    usleep(200000 * $attempt);
                }
            }
            return false;
        };

        /*
         * IMPORTANT for OP:
         * R480 is the trigger. OP writes are individual commands, so writing R480 first
         * can make Controller start before R481~R500 are ready. Always write filename
         * registers first, then trigger R480=1 last. This order is safe for TCP too.
         */
        if (!$writeRegistersWithRetry(481, $name_int16, 3)) {
            $this->logMessage('[FirmwareUpdate] filename register write failed stem=' . $safeStem . ', protocol=' . $protocolName);
            $respond([
                'error' => 'Failed to send firmware filename to Controller.',
                'protocol' => $protocolName,
                'controller_filename' => $controllerFileName,
            ], 502);
        }

        // Small settle time is important when OP expands the 20 registers into 20 commands.
        if ($isOp) {
            usleep(150000);
        }

        if (!$writeRegisterWithRetry(480, 1, 3)) {
            $this->logMessage('[FirmwareUpdate] trigger write failed stem=' . $safeStem . ', protocol=' . $protocolName);
            $respond([
                'error' => 'Firmware file was uploaded, but Controller update trigger failed.',
                'protocol' => $protocolName,
                'controller_filename' => $controllerFileName,
            ], 502);
        }

        $this->logMessage('[FirmwareUpdate] success original=' . $originalName . ', controller=' . $controllerFileName . ', protocol=' . $protocolName . ', unitId=' . $unitId);

        $respond([
            'error' => '',
            'success' => true,
            'protocol' => $protocolName,
            'original_filename' => $originalName,
            'controller_filename' => $controllerFileName,
            'message' => 'Firmware update command sent successfully.',
        ]);
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

        // 執行寫入刪除年份；MODBUS TCP / OP 由 protocol_write_registers 自動切換。
        $year = [(int)$temp_del_year];

        try {
            if (!$this->protocol_write_registers($unitId, 517, $year)) {
                throw new RuntimeException('protocol write failed');
            }

            echo json_encode([
                'result' => true,
                'res_type' => 'Success',
                'res_msg' => $text['delete_text'].$text['success'] 
            ]);
        } catch (Throwable $e) {
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
            // OP 保護：若控制器端已是 OP(modbus_type=2)，D2C 前先把 iDAS DB 同步成 2，避免覆蓋回 TCP。
            $controllerModbusType = $this->readModbusTypeFromDb($dst3);
            if ($controllerModbusType === 2) {
                $this->writeModbusTypeToDb($src3, 2);
            }

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

        // D2C 前先強制套用『同一個 JOB 僅一筆 Barcode』規則。
        if (!$this->ensureBarcodeSingleJobPolicy($src2)) {
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Barcode database single-JOB policy check failed');
        }

        // 初始化通訊協議；MODBUS TCP / OP 由 notifyModbus 自動切換。
        $modbus = null;

        try {
            // ----------- Sync LIN File（簡化：直接 src → final → rename）-----------
            if (!$this->safeCopy($src1, $finalPath1)) {
                $this->MiscellaneousModel->generateErrorResponse('Error', "Failed to copy $src1 to $finalPath1");
            }
            @chmod($finalPath1, 0777);
            $this->logMessage("$src1 copied to $finalPath1");

            // 通知控制器有新 LIN
            $this->notifyModbus($modbus, [1, 12593], "LIN");

            // OP 控制器收到第一個同步通知後會立即開始處理檔案；
            // 留一小段時間再送 Barcode DB 通知，降低第二次通知撞到忙碌期的機率。
            if ($this->is_op_protocol_enabled()) {
                usleep(350000);
            }

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

        } catch (Throwable $e) {
            $this->logMessage('Protocol write fail: ' . $e->getMessage());
            $this->MiscellaneousModel->generateErrorResponse('Error', 'Protocol communication failed');
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
                        @chmod($dst, 0666);
                        $copiedCount++;

                        // Barcode C2D：複製後立即恢復「同一個 JOB 只能一筆 Barcode」。
                        if (basename($src) === 'ntcs_barcode.db') {
                            if (!$this->ensureBarcodeSingleJobPolicy($dst)) {
                                return $this->MiscellaneousModel->generateErrorResponse('Error', 'Barcode database single-JOB policy check failed after C2D');
                            }
                        }

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
        
        $controller_info = (array)($this->SettingModel->GetControllerInfo() ?? []);

        $device_id = isset($controller_info['device_id'])
            ? (int)$controller_info['device_id']
            : 1;

        $unitId = $device_id;
        if ($unitId < 1 || $unitId > 255) {
            $unitId = 1;
        }

        $isOp = $this->is_op_protocol_enabled();
        $data = array_values($data);

        /*
         * MODBUS TCP 原本是一次寫 16 registers，因此保留 16-word payload。
         *
         * OP 不同：protocol_write_registers() 會把每一個 register 拆成一條
         * IDAS_WRITE_xxx 指令並各自建立 TCP 連線。同步通知真正有意義的資料
         * 只有呼叫端傳入的 [1, 12593]，若再補 14 個 0，等於每次通知要額外
         * 建立 14 次沒有必要的 OP socket，控制器開始處理第一個通知後，後面
         * 任一連線短暫失敗就會造成「同步其實成功，但最後跳通訊失敗」。
         *
         * 因此 OP 僅送實際資料，並針對整筆通知做短暫 retry；不改 Controller.php
         * 的 OP READ/WRITE 核心，避免影響已實機驗證正常的 OP 行為。
         */
        if ($isOp) {
            $payload = $data;
            $maxAttempts = 4;
            $retryDelayUs = [0, 250000, 500000, 1000000];

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                if ($attempt > 1) {
                    usleep($retryDelayUs[$attempt - 1]);
                }

                if ($this->protocol_write_registers($unitId, 506, $payload)) {
                    $this->logMessage(
                        "Protocol write ($tag) [unitId=$unitId, protocol=OP, attempt=$attempt]: " .
                        implode(',', $payload)
                    );
                    return;
                }

                $this->logMessage(
                    "Protocol write retry ($tag) [unitId=$unitId, protocol=OP, attempt=$attempt/$maxAttempts]"
                );
            }

            throw new RuntimeException("Protocol write 506 failed ({$tag}) after OP retry");
        }

        $payload = array_merge($data, array_fill(0, max(0, 16 - count($data)), 0));
        $payload = array_slice($payload, 0, 16);

        if (!$this->protocol_write_registers($unitId, 506, $payload)) {
            throw new RuntimeException("Protocol write 506 failed ({$tag})");
        }

        $this->logMessage(
            "Protocol write ($tag) [unitId=$unitId, protocol=MODBUS]: " . implode(',', $payload)
        );
    }





    
    private function ensureBarcodeSingleJobPolicy(string $dbPath): bool
    {
        if ($dbPath === '' || !is_file($dbPath) || filesize($dbPath) <= 0) {
            $this->logMessage('[BarcodeSingle] DB missing/empty: ' . $dbPath);
            return false;
        }

        $tableName = 'ntcs_barcode_test';
        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $tableStmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=:name LIMIT 1");
            $tableStmt->execute([':name' => $tableName]);
            if (!$tableStmt->fetch(PDO::FETCH_ASSOC)) return false;

            $columnsInfo = $pdo->query('PRAGMA table_info(' . $tableName . ')')->fetchAll(PDO::FETCH_ASSOC);
            $columns = [];
            $jobIdIsPk = false;
            foreach ($columnsInfo as $column) {
                $name = strtolower((string)($column['name'] ?? ''));
                $columns[$name] = true;
                if ($name === 'job_id' && (int)($column['pk'] ?? 0) > 0) $jobIdIsPk = true;
            }
            if (!isset($columns['job_id'])) return false;

            $pdo->beginTransaction();
            if (!isset($columns['seq_id'])) {
                $pdo->exec('ALTER TABLE ' . $tableName . ' ADD COLUMN seq_id INTEGER DEFAULT -1');
            }

            $duplicateCount = (int)$pdo->query(
                'SELECT COALESCE(SUM(cnt - 1), 0) FROM (' .
                ' SELECT COUNT(*) AS cnt FROM ' . $tableName .
                ' WHERE job_id IS NOT NULL GROUP BY job_id HAVING COUNT(*) > 1' .
                ')'
            )->fetchColumn();

            if ($duplicateCount > 0) {
                $pdo->exec(
                    'DELETE FROM ' . $tableName .
                    ' WHERE rowid NOT IN (' .
                    ' SELECT MIN(rowid) FROM ' . $tableName .
                    ' WHERE job_id IS NOT NULL GROUP BY job_id' .
                    ') AND job_id IS NOT NULL'
                );
            }

            if (!$jobIdIsPk) {
                $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS ux_ntcs_barcode_test_job_id ON ' . $tableName . ' (job_id)');
            }

            $pdo->commit();
            $this->logMessage('[BarcodeSingle] policy ok: ' . $dbPath . ', duplicates_removed=' . $duplicateCount);
            return true;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
            $this->logMessage('[BarcodeSingle] policy failed: ' . $dbPath . ' :: ' . $e->getMessage());
            return false;
        }
    }


    //get barcode
    public function GetBarcodes(){

        $this->ensureBarcodeSingleJobPolicy('/var/www/html/database/ntcs_barcode_IDAS.db');
        $barcodes = $this->SettingModel->GetAllBarcodes();
        return $barcodes;
    }


    public function show_Barcodes(){
        
        $this->ensureBarcodeSingleJobPolicy('/var/www/html/database/ntcs_barcode_IDAS.db');
        $barcodes = $this->SettingModel->GetAllBarcodes();
        $barcode_mode = $this->MiscellaneousModel->details('barcode_mode');

        if (empty($barcodes)) {
            return;
        }

        foreach ($barcodes as $k => $v) {
            $rowid = (int)($v['barcode_rowid'] ?? 0);

            echo '<tr style="text-align:center; vertical-align:middle;">';

            echo '<td>
                <input
                    class="form-check-input barcode-check"
                    type="checkbox"
                    name="barcode_check"
                    id="barcode_check_' . $rowid . '"
                    value="1"
                    data-id="' . $rowid . '"
                    data-barcode-rowid="' . $rowid . '"
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
            'barcode_id'        => ['key' => 'barcode_id',          'required' => false],
            'barcode_name'      => ['key' => 'barcode_name',        'required' => true],
            'barcode_from'      => ['key' => 'barcode_range_from',  'required' => true],
            'barcode_count'     => ['key' => 'barcode_range_count', 'required' => true],
            'barcode_job'       => ['key' => 'barcode_job',         'required' => true],
            'barcode_job_old'   => ['key' => 'barcode_job_old',     'required' => false],
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

        if (($barcode['barcode_id'] ?? '') === '' || !is_numeric($barcode['barcode_id'])) {
            $barcode['barcode_id'] = '';
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
        * 是否為編輯模式：仍以 SQLite rowid 識別單筆；
        * 業務規則恢復為「同一個 JOB 只能有一筆 Barcode」。
        * ================================================= */
        $isEdit = !empty($barcode['barcode_id']) && is_numeric($barcode['barcode_id']);
        $currentRowId = $isEdit ? (int)$barcode['barcode_id'] : 0;
        $targetJobId  = (int)$barcode['barcode_job'];

        if (!$this->ensureBarcodeSingleJobPolicy('/var/www/html/database/ntcs_barcode_IDAS.db')) {
            echo json_encode(['res_type' => 'error', 'res_msg' => 'Barcode database single-JOB policy check failed.']);
            exit;
        }

        if ($this->SettingModel->check_barcode_conflict($targetJobId, $currentRowId)) {
            echo json_encode([
                'res_type' => 'error',
                'res_msg'  => sprintf(
                    $text['barcode_job_already_exists'] ?? 'JOB %d already has a barcode. Each JOB can only have one barcode.',
                    $targetJobId
                )
            ]);
            exit;
        }

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
                'res_type'   => 'info',
                'res_msg'    => $msg,
                'barcode_id' => (int)($barcode['barcode_id'] ?? 0)
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
                'res_type'   => 'error',
                'res_msg'    => $msg,
                'barcode_id' => (int)($barcode['barcode_id'] ?? 0)
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

        // 前端傳 SQLite rowid array。rowid 不是新增欄位，是 SQLite 內建單筆識別值。
        $barcodeIds = $_POST['barcode_id'] ?? [];

        if (!is_array($barcodeIds) || empty($barcodeIds)) {
            echo json_encode([
                'res_type' => 'error',
                'res_msg'  => $text['barcode_delete_no_select'] ?? 'Please select at least one barcode.'
            ]);
            exit;
        }

        // CRUD 前保證 schema 符合同 JOB 單一 Barcode。
        if (!$this->ensureBarcodeSingleJobPolicy('/var/www/html/database/ntcs_barcode_IDAS.db')) {
            echo json_encode([
                'res_type' => 'error',
                'res_msg'  => 'Barcode database single-JOB policy check failed.'
            ]);
            exit;
        }

        // 使用 rowid 精準刪除選取的單筆 Barcode。
        $affectedTotal = (int)$this->SettingModel->delete_barcodes_by_rowids($barcodeIds);

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

    /**
     * iDAS Pack 預檢 API：只解析 .pack 的 info.json，不安裝。
     * 前端選檔後呼叫，用來自動判斷是否為降版本並顯示提示。
     * 正式上傳時 iDas_Update() 仍會再次驗證，避免前端被繞過。
     */
    public function check_idas_pack_version() {
        header('Content-Type: application/json; charset=utf-8');

        $MAX_SIZE  = 30 * 1024 * 1024;
        $limitText = '30MB';

        try {
            if (empty($_FILES['file'])) {
                return $this->sendJsonResponse(false, $this->t('ERR_NO_FILE'));
            }

            if ($_FILES['file']['error'] !== 0) {
                return $this->sendJsonResponse(false, $this->t('ERR_UPLOAD_ERROR', [
                    'code' => (string)$_FILES['file']['error'],
                ]));
            }

            if ($_FILES['file']['size'] > $MAX_SIZE) {
                return $this->sendJsonResponse(false, $this->t('ERR_SIZE_LIMIT', [
                    'limit' => $limitText,
                ]));
            }

            $uploadedFilename = basename((string)($_FILES['file']['name'] ?? ''));
            if (strtolower(pathinfo($uploadedFilename, PATHINFO_EXTENSION)) !== 'pack') {
                return $this->sendJsonResponse(false, $this->t('ERR_EXT', [
                    'filename' => $uploadedFilename,
                ]));
            }

            // 先檢查 zip 內容，避免不安全路徑或錯誤 pack 被後續流程使用。
            $this->validateIdasPackZip((string)$_FILES['file']['tmp_name']);

            $packInfo = $this->readIdasPackInfoFromZip((string)$_FILES['file']['tmp_name']);
            if (empty($packInfo['idas_version'])) {
                return $this->sendJsonResponse(false, $this->t('ERR_BAD_INFO'));
            }

            $currentRaw = (string)$this->AdminModel->Get_Das_Config('idas_version');
            $targetRaw  = (string)$packInfo['idas_version'];
            $state      = $this->buildIdasVersionState($currentRaw, $targetRaw);

            return $this->sendJsonResponse(true, '', array_merge($state, [
                'filename' => $uploadedFilename,
            ]));
        } catch (Throwable $e) {
            return $this->sendJsonResponse(false, $e->getMessage());
        }
    }

    public function iDas_Update($debug = false) {
        $maxUpload = ini_get('upload_max_filesize');
        $postMax   = ini_get('post_max_size');
        error_log("[iDAS UPDATE] upload_max_filesize: $maxUpload");
        error_log("[iDAS UPDATE] post_max_size: $postMax");

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $iDas_Version = (string)$this->AdminModel->Get_Das_Config('idas_version');
        $allowDowngrade = $this->isTruthyFlag($_POST['allow_downgrade'] ?? false);

        $file_location = (PHP_OS_FAMILY === 'Linux') ? '/var/www/html/' : rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/') . '/';
        $extract_path  = $file_location . 'extracted/';
        $main_folder   = '';
        $lockHandle    = null;
        $backupPath    = '';
        $rollbackSnapshot = '';
        $deploymentStarted = false;
        $hadTargetDirectory = false;
        $versionCommitted = false;
        $databaseMutationStarted = false;

        $root = $file_location;
        $target_directory  = $root . 'idas/';
        $staging_directory = $root . 'idas_new/';
        $old_directory     = $root . 'idas_old/';

        $MAX_SIZE  = 30 * 1024 * 1024;
        $limitText = '30MB';

        try {
            if (empty($_FILES['file'])) {
                return $this->sendResponse('Error', $this->t('ERR_NO_FILE'));
            }
            if ($_FILES['file']['error'] !== 0) {
                return $this->sendResponse('Error', $this->t('ERR_UPLOAD_ERROR', ['code' => (string)$_FILES['file']['error']]));
            }
            if ($_FILES['file']['size'] > $MAX_SIZE) {
                return $this->sendResponse('Error', $this->t('ERR_SIZE_LIMIT', ['limit' => $limitText]));
            }

            $uploaded_filename = basename((string)$_FILES['file']['name']);
            if (strtolower(pathinfo($uploaded_filename, PATHINFO_EXTENSION)) !== 'pack') {
                return $this->sendResponse('Error', $this->t('ERR_EXT', ['filename' => $uploaded_filename]));
            }

            $lockHandle = $this->acquireIdasUpdateLock();

            $packSha256 = @hash_file('sha256', (string)$_FILES['file']['tmp_name']) ?: 'UNKNOWN';
            $packSize   = (string)($_FILES['file']['size'] ?? 0);
            $this->logMessage("[iDAS UPDATE] upload start: file={$uploaded_filename}, size={$packSize}, sha256={$packSha256}");

            $this->validateIdasPackZip((string)$_FILES['file']['tmp_name']);

            if (is_dir($extract_path)) {
                $this->deleteDirectory($extract_path);
            }
            if (!is_dir($extract_path) && !@mkdir($extract_path, 0777, true) && !is_dir($extract_path)) {
                return $this->sendResponse('Error', $this->t('ERR_EXTRACT'));
            }

            $zip = new ZipArchive();
            if ($zip->open($_FILES['file']['tmp_name']) !== TRUE) {
                return $this->sendResponse('Error', $this->t('ERR_OPEN_PACK'));
            }
            if (!$zip->extractTo($extract_path)) {
                $zip->close();
                return $this->sendResponse('Error', $this->t('ERR_EXTRACT'));
            }
            $zip->close();

            $main_folder = $this->locateExtractedIdasMainFolder($extract_path);
            $this->assertIdasPackStructure($main_folder);

            if ($debug) {
                error_log('[iDAS UPDATE] extracted package files:');
                $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($main_folder));
                foreach ($rii as $fileObj) {
                    if (!$fileObj->isDir()) error_log($fileObj->getPathname());
                }
            }

            $info_json_url = $main_folder . '/info.json';
            $verify_data = json_decode(@file_get_contents($info_json_url), true);
            if (!$verify_data || !isset($verify_data['idas_version'])) {
                return $this->sendResponse('Error', $this->t('ERR_BAD_INFO'));
            }

            $match_tcc_version_raw = (string)$verify_data['idas_version'];
            $versionState = $this->buildIdasVersionState($iDas_Version, $match_tcc_version_raw);

            $current_version_display = $versionState['current_version'];
            $target_version_display  = $versionState['pack_version'];
            $isDowngrade       = (bool)$versionState['is_downgrade'];
            $isSpecialSwitch   = (bool)$versionState['is_special_switch'];
            $requiresConfirm   = (bool)$versionState['requires_confirm'];
            $requiresDbRebuild = (bool)$versionState['requires_db_rebuild'];

            // 降版本沿用原本 schema preflight；任何需要 DB rebuild 的流程都先確認 Controller 來源 DB 健康。
            if ($isDowngrade) {
                $schemaCheck = $this->checkControllerIdasDbSchemaCompatibilityForDowngrade();
                if (!$schemaCheck['compatible']) {
                    $detail = (string)($schemaCheck['detail'] ?? 'schema mismatch');
                    $this->logMessage("[iDAS UPDATE] downgrade stopped: controller/iDAS DB schema mismatch; {$detail}");
                    return $this->sendResponseWithCode(
                        'Error',
                        $this->t('ERR_DB_SCHEMA_MISMATCH_MSG'),
                        'DB_SCHEMA_MISMATCH',
                        [
                            'res_title' => $this->t('ERR_DB_SCHEMA_MISMATCH_TITLE'),
                            'schema_detail' => $detail,
                        ]
                    );
                }
            }

            if ($requiresDbRebuild) {
                $this->assertIdasDbRebuildSourcesHealthy();
            }

            if ($requiresConfirm && !$allowDowngrade) {
                if ($isDowngrade) {
                    return $this->sendResponse('Error', $this->t('ERR_VERSION_LOW', [
                        'current' => $current_version_display,
                        'update'  => $target_version_display,
                    ]));
                }
                if ($isSpecialSwitch) {
                    return $this->sendResponse('Error', $this->t('ERR_SPECIAL_SWITCH_CONFIRM', [
                        'current' => $current_version_display,
                        'update'  => $target_version_display,
                    ]));
                }
            }

            if ($requiresConfirm && $allowDowngrade) {
                $this->logMessage(
                    "[iDAS UPDATE] risky update confirmed: status={$versionState['status_key']}, current={$current_version_display}, target={$target_version_display}, file={$uploaded_filename}, db_rebuild=" . ($requiresDbRebuild ? 'yes' : 'no')
                );
            }

            try {
                $backupPath = $this->createIDasPreUpdateBackupZip(
                    $current_version_display,
                    $target_version_display,
                    (string)$versionState['status_key'],
                    $uploaded_filename,
                    $packSha256
                );
                if ($backupPath !== '') {
                    $this->logMessage("[iDAS UPDATE] pre-update backup created: {$backupPath}");
                }
            } catch (Throwable $e) {
                $this->logMessage('[iDAS UPDATE] pre-update backup failed: ' . $e->getMessage());
                return $this->sendResponse('Error', $this->t('ERR_BACKUP_FAILED', ['detail' => $e->getMessage()]));
            }

            // Transaction rollback snapshot：DB + Controller LIN。idas_version 此時仍保持舊值。
            $rollbackSnapshot = $this->createIdasUpdateRollbackSnapshot();
            $verify_data['idas_version'] = $target_version_display;

            // ===== Program staging =====
            if (is_dir($staging_directory)) {
                $this->deleteDirectory($staging_directory);
            }
            $this->copyDirectory($main_folder, $staging_directory);
            $this->assertIdasInstalledProgramHealthy($staging_directory, $target_version_display);

            if (is_dir($old_directory)) {
                $this->deleteDirectory($old_directory);
            }

            $hadTargetDirectory = is_dir($target_directory);
            $deploymentStarted = true;

            if ($hadTargetDirectory) {
                if (!@rename($target_directory, $old_directory)) {
                    throw new RuntimeException('Unable to move current idas directory to idas_old');
                }
            }

            if (!@rename($staging_directory, $target_directory)) {
                throw new RuntimeException('Unable to activate idas_new directory');
            }

            $this->assertIdasInstalledProgramHealthy($target_directory, $target_version_display);

            // ===== DB transaction =====
            // 降版 + Standard↔SA349 雙向切換都從 /home/kls/NTCS7 重建。
            if ($requiresDbRebuild) {
                $databaseMutationStarted = true;
                $this->rebuildIDasDatabaseForVersionTransition(
                    $current_version_display,
                    $target_version_display,
                    (string)$versionState['status_key']
                );
            }

            // 非 SA349 移除 admin；rollback snapshot 同時保護 Controller LIN 與 iDAS LIN。
            if (($versionState['pack_suffix'] ?? '') !== 'SA349') {
                $databaseMutationStarted = true;
            }
            $this->removeAdminUserForNonSa349Version($target_version_display);

            // ===== Post-check before commit =====
            $this->assertIdasPostUpdateHealthy(
                $target_directory,
                $target_version_display,
                $requiresDbRebuild
            );

            // 版本號必須直到所有程式 / DB / post-check 成功後才 commit。
            $storedBeforeCommit = $this->normalizeIdasVersionDisplay((string)$this->AdminModel->Get_Das_Config('idas_version'));
            if ($storedBeforeCommit !== $current_version_display) {
                throw new RuntimeException(
                    "idas_version changed before commit: expected={$current_version_display}, actual={$storedBeforeCommit}"
                );
            }

            if (!$this->AdminModel->Set_Das_Config('idas_version', $target_version_display)) {
                throw new RuntimeException('Failed to commit idas_version');
            }
            $versionCommitted = true;

            $storedAfterCommit = $this->normalizeIdasVersionDisplay((string)$this->AdminModel->Get_Das_Config('idas_version'));
            if ($storedAfterCommit !== $target_version_display) {
                throw new RuntimeException(
                    "idas_version commit verification failed: expected={$target_version_display}, actual={$storedAfterCommit}"
                );
            }

            @exec('sync');

            // Commit 完成後才清舊程式與 transaction snapshot。
            if (is_dir($old_directory)) {
                try {
                    $this->deleteDirectory($old_directory);
                } catch (Throwable $cleanupError) {
                    $this->logMessage('[iDAS UPDATE] warning: idas_old cleanup failed: ' . $cleanupError->getMessage());
                }
            }
            if ($rollbackSnapshot !== '') {
                $this->deleteIdasUpdateRollbackSnapshot($rollbackSnapshot);
                $rollbackSnapshot = '';
            }

            // 清 legacy 只做 best effort，不應在 commit 後因 cleanup 失敗把成功更新判成失敗。
            $legacy_directory = $root . 'ntcs_idas/';
            if (is_dir($legacy_directory)) {
                try {
                    $this->deleteDirectory($legacy_directory);
                    $this->logMessage("[iDAS UPDATE] legacy folder removed: {$legacy_directory}");
                } catch (Throwable $cleanupError) {
                    $this->logMessage('[iDAS UPDATE] warning: remove legacy folder failed: ' . $cleanupError->getMessage());
                }
            }

            $this->setting_logout();

            if ($debug) {
                return $this->sendResponse('Success', $this->t('SUC_DEBUG'));
            }

            return $this->sendResponse('Success', $this->t('SUC_OK'));

        } catch (Throwable $e) {
            $this->logMessage('[iDAS UPDATE] transaction failed: ' . $e->getMessage());
            $rollbackErrors = [];

            if ($deploymentStarted) {
                try {
                    $this->rollbackIdasProgramDeployment(
                        $target_directory,
                        $old_directory,
                        $staging_directory,
                        $hadTargetDirectory
                    );
                } catch (Throwable $rollbackError) {
                    $rollbackErrors[] = 'program: ' . $rollbackError->getMessage();
                }
            }

            $dbRollbackOk = true;
            if ($rollbackSnapshot !== '' && $databaseMutationStarted) {
                try {
                    $this->restoreIdasUpdateRollbackSnapshot($rollbackSnapshot);
                } catch (Throwable $rollbackError) {
                    $dbRollbackOk = false;
                    $rollbackErrors[] = 'database: ' . $rollbackError->getMessage();
                }
            }

            if ($rollbackSnapshot !== '' && ($dbRollbackOk || !$databaseMutationStarted)) {
                $this->deleteIdasUpdateRollbackSnapshot($rollbackSnapshot);
                $rollbackSnapshot = '';
            }

            if ($versionCommitted) {
                try {
                    $this->AdminModel->Set_Das_Config('idas_version', $current_version_display ?? $iDas_Version);
                } catch (Throwable $rollbackError) {
                    $rollbackErrors[] = 'version: ' . $rollbackError->getMessage();
                }
            }

            @exec('sync');

            $rollbackText = empty($rollbackErrors)
                ? 'rollback completed'
                : 'rollback error: ' . implode(' | ', $rollbackErrors);
            $this->logMessage('[iDAS UPDATE] ' . $rollbackText);

            return $this->sendResponse('Error', $this->t('ERR_UPDATE_ROLLBACK', [
                'detail' => $e->getMessage(),
                'rollback' => $rollbackText,
            ]));

        } finally {
            if ($lockHandle) {
                $this->releaseIdasUpdateLock($lockHandle);
            }

            if (!$debug) {
                if (!empty($main_folder) && is_dir($main_folder)) {
                    try { $this->deleteDirectory($main_folder); } catch (Throwable $ignore) {}
                }
                if (is_dir($extract_path)) {
                    try { $this->deleteDirectory($extract_path); } catch (Throwable $ignore) {}
                }
                if (is_dir($staging_directory)) {
                    try { $this->deleteDirectory($staging_directory); } catch (Throwable $ignore) {}
                }
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



    /**
     * 建立 iDAS 更新鎖，避免多個請求同時執行更新。
     */
    private function acquireIdasUpdateLock() {
        $lockDir = (PHP_OS_FAMILY === 'Linux') ? '/var/www/html/database' : sys_get_temp_dir();
        if (!is_dir($lockDir)) {
            @mkdir($lockDir, 0777, true);
        }

        $lockPath = rtrim($lockDir, '/') . '/.idas_update.lock';
        $handle = @fopen($lockPath, 'c');
        if (!$handle) {
            throw new Exception($this->t('ERR_UPDATE_LOCK'));
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            @fclose($handle);
            throw new Exception($this->t('ERR_UPDATE_BUSY'));
        }

        @ftruncate($handle, 0);
        @fwrite($handle, 'pid=' . getmypid() . PHP_EOL . 'time=' . date('Y-m-d H:i:s') . PHP_EOL);
        @fflush($handle);

        return $handle;
    }

    private function releaseIdasUpdateLock($handle): void {
        if (is_resource($handle)) {
            @flock($handle, LOCK_UN);
            @fclose($handle);
        }
    }

    /**
     * 檢查 .pack 內部路徑，避免 Zip Slip / 絕對路徑 / Windows 磁碟路徑。
     */
    private function validateIdasPackZip(string $packPath): void {
        $zip = new ZipArchive();
        if ($zip->open($packPath) !== true) {
            throw new Exception($this->t('ERR_OPEN_PACK'));
        }

        $hasInfoJson = false;
        $totalUncompressed = 0;
        $maxUncompressed = 220 * 1024 * 1024; // 避免異常壓縮炸彈；一般 iDAS pack 遠低於此值

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $entryName = (string)$zip->getNameIndex($i);
            $entry = str_replace('\\', '/', $entryName);

            if ($entry === '' || strpos($entry, "\0") !== false) {
                $zip->close();
                throw new Exception($this->t('ERR_PACK_UNSAFE'));
            }

            if ($entry[0] === '/' || preg_match('/^[A-Za-z]:\//', $entry) || preg_match('#(^|/)\.\.(/|$)#', $entry)) {
                $zip->close();
                throw new Exception($this->t('ERR_PACK_UNSAFE'));
            }

            if (basename($entry) === 'info.json') {
                $hasInfoJson = true;
            }

            if (is_array($stat) && isset($stat['size'])) {
                $totalUncompressed += (int)$stat['size'];
                if ($totalUncompressed > $maxUncompressed) {
                    $zip->close();
                    throw new Exception($this->t('ERR_PACK_TOO_LARGE_AFTER_EXTRACT'));
                }
            }
        }

        $zip->close();

        if (!$hasInfoJson) {
            throw new Exception($this->t('ERR_MISSING_INFO'));
        }
    }

    /**
     * 找出解壓後的 iDAS 主資料夾；支援 info.json 在根目錄或第一層資料夾。
     */
    private function locateExtractedIdasMainFolder(string $extractPath): string {
        $extractPath = rtrim($extractPath, '/');

        if (is_file($extractPath . '/info.json')) {
            return $extractPath;
        }

        $candidates = [];
        foreach (scandir($extractPath) ?: [] as $name) {
            if ($name === '.' || $name === '..') continue;
            $path = $extractPath . '/' . $name;
            if (is_dir($path) && is_file($path . '/info.json')) {
                $candidates[] = $path;
            }
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        if (count($candidates) > 1) {
            throw new Exception($this->t('ERR_BAD_PACK_STRUCTURE'));
        }

        throw new Exception($this->t('ERR_NO_FOLDER'));
    }

    /**
     * 檢查更新包是否像 iDAS 程式包，避免拿錯 pack。
     */
    private function assertIdasPackStructure(string $mainFolder): void {
        if (!is_file($mainFolder . '/info.json')) {
            throw new Exception($this->t('ERR_MISSING_INFO'));
        }

        $hasApp    = is_dir($mainFolder . '/app');
        $hasPublic = is_dir($mainFolder . '/public');

        if (!$hasApp || !$hasPublic) {
            throw new Exception($this->t('ERR_BAD_PACK_STRUCTURE'));
        }
    }

    /**
     * 統一產出版本判斷狀態：數字版號判斷升降，suffix 判斷特規版切換。
     */
    private function buildIdasVersionState(string $currentRaw, string $targetRaw): array {
        $currentDisplay = $this->normalizeIdasVersionDisplay($currentRaw);
        $targetDisplay  = $this->normalizeIdasVersionDisplay($targetRaw);
        $currentBase    = $this->normalizeIdasVersionBase($currentRaw);
        $targetBase     = $this->normalizeIdasVersionBase($targetRaw);
        $currentSuffix  = $this->normalizeIdasVersionSuffix($currentRaw);
        $targetSuffix   = $this->normalizeIdasVersionSuffix($targetRaw);

        $baseCompare = version_compare($targetBase, $currentBase);
        $isDowngrade = ($baseCompare < 0);
        $isUpgrade   = ($baseCompare > 0);
        $isSameBase  = ($baseCompare === 0);

        // 數字版號相同但 suffix 不同，例如 0.72 ↔ 0.72_SA349。
        $isSpecialSwitch = ($currentSuffix !== $targetSuffix);
        $isSameVersion   = ($isSameBase && !$isSpecialSwitch);

        // SA349 edition boundary 必須雙向重建：Standard → SA349、SA349 → Standard 都不能沿用舊 iDAS DB。
        // 一般降版本也維持原本規則：安裝 pack 後從 Controller 來源重建 iDAS DB。
        $currentIsSa349       = ($currentSuffix === 'SA349');
        $targetIsSa349        = ($targetSuffix === 'SA349');
        $isSa349ToStandard    = ($currentIsSa349 && !$targetIsSa349);
        $isStandardToSa349    = (!$currentIsSa349 && $targetIsSa349);
        $isSa349EditionSwitch = ($currentIsSa349 !== $targetIsSa349);
        $requiresDbRebuild    = $isDowngrade || $isSa349EditionSwitch;
        $requiresConfirm      = $isDowngrade || $isSpecialSwitch;

        if ($isDowngrade) {
            $statusKey = 'downgrade';
        } elseif ($isUpgrade) {
            $statusKey = $isSpecialSwitch ? 'upgrade_special' : 'upgrade';
        } elseif ($isSpecialSwitch) {
            $statusKey = 'special_switch';
        } else {
            $statusKey = 'same';
        }

        return [
            'current_version'       => $currentDisplay,
            'pack_version'          => $targetDisplay,
            'current_base'          => $currentBase,
            'pack_base'             => $targetBase,
            'current_suffix'        => $currentSuffix,
            'pack_suffix'           => $targetSuffix,
            'is_downgrade'          => $isDowngrade,
            'is_same_version'       => $isSameVersion,
            'is_upgrade'            => $isUpgrade,
            'is_special_switch'     => $isSpecialSwitch,
            'is_sa349_to_standard'   => $isSa349ToStandard,
            'is_standard_to_sa349'   => $isStandardToSa349,
            'is_sa349_edition_switch'=> $isSa349EditionSwitch,
            'requires_db_rebuild'    => $requiresDbRebuild,
            'requires_confirm'      => $requiresConfirm,
            'status_key'            => $statusKey,
        ];
    }

    /**
     * 更新前備份目前 iDAS DB，方便異常時回復。
     */
    private function createIDasPreUpdateBackupZip(
        string $currentVersion,
        string $targetVersion,
        string $reason,
        string $packFilename,
        string $packSha256
    ): string {
        if (PHP_OS_FAMILY !== 'Linux') {
            return '';
        }

        $databaseDir = '/var/www/html/database';
        if (!is_dir($databaseDir)) {
            return '';
        }

        $backupDir = $databaseDir . '/update_backups';
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0777, true) && !is_dir($backupDir)) {
            throw new Exception("Cannot create backup directory: {$backupDir}");
        }

        $safeCurrent = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $currentVersion ?: 'unknown');
        $safeTarget  = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $targetVersion ?: 'unknown');
        $ts = date('Ymd_His');
        $zipName = "idas_backup_before_update_{$safeCurrent}_to_{$safeTarget}_{$ts}.zip";
        $zipPath = $backupDir . '/' . $zipName;

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("Cannot create backup zip: {$zipPath}");
        }

        $files = [
            'KLS_NTCS_IDAS.Lin',
            'ntcs_barcode_IDAS.db',
            'ntcs_device_IDAS.db',
            'ntcs_data.db',
            'das.db',
        ];

        $added = 0;
        foreach ($files as $file) {
            $path = $databaseDir . '/' . $file;
            if (is_file($path) && is_readable($path)) {
                $zip->addFile($path, 'database/' . $file);
                $added++;
            }
        }

        $meta = [
            'generated_at'    => date('Y-m-d H:i:s'),
            'current_version' => $currentVersion,
            'target_version'  => $targetVersion,
            'reason'          => $reason,
            'pack_filename'   => $packFilename,
            'pack_sha256'     => $packSha256,
            'db_file_count'   => $added,
        ];
        $zip->addFromString('backup_info.json', json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $zip->close();

        if (!is_file($zipPath) || filesize($zipPath) <= 0) {
            throw new Exception("Backup zip not generated: {$zipPath}");
        }

        @chmod($zipPath, 0666);
        $this->cleanupIDasPreUpdateBackups($backupDir, 5);

        return $zipPath;
    }

    private function cleanupIDasPreUpdateBackups(string $backupDir, int $keep = 5): void {
        $files = glob(rtrim($backupDir, '/') . '/idas_backup_before_update_*.zip') ?: [];
        usort($files, function($a, $b) {
            return filemtime($b) <=> filemtime($a);
        });

        foreach (array_slice($files, max(0, $keep)) as $old) {
            if (is_file($old)) {
                @unlink($old);
            }
        }
    }

    /**
     * 降版本前檢查 Controller DB 與 iDAS DB 的 SQLite schema 是否一致。
     *
     * 注意：這裡只做檢查，不做任何搬移、重建或同步。
     * 若格式不同，更新流程會在正式覆蓋程式與 DB 前中止。
     */
    private function checkControllerIdasDbSchemaCompatibilityForDowngrade(): array {
        if (PHP_OS_FAMILY !== 'Linux') {
            return ['compatible' => true, 'detail' => 'schema check skipped on non-Linux OS'];
        }

        $pairs = [
            'KLS_NTCS.Lin'    => ['/home/kls/NTCS7/KLS_NTCS.Lin',    '/var/www/html/database/KLS_NTCS_IDAS.Lin'],
            'ntcs_barcode.db' => ['/home/kls/NTCS7/ntcs_barcode.db', '/var/www/html/database/ntcs_barcode_IDAS.db'],
            'ntcs_device.db'  => ['/home/kls/NTCS7/ntcs_device.db',  '/var/www/html/database/ntcs_device_IDAS.db'],
            'ntcs_data.db'    => ['/home/kls/NTCS7/ntcs_data.db',    '/var/www/html/database/ntcs_data.db'],
        ];

        $diffs = [];
        foreach ($pairs as $name => [$controllerDb, $idasDb]) {
            if (!is_file($controllerDb) || !is_readable($controllerDb)) {
                $diffs[] = "{$name}: controller DB not found or unreadable ({$controllerDb})";
                continue;
            }
            if (!is_file($idasDb) || !is_readable($idasDb)) {
                $diffs[] = "{$name}: iDAS DB not found or unreadable ({$idasDb})";
                continue;
            }

            try {
                $controllerSchema = $this->getSqliteSchemaSignature($controllerDb);
                $idasSchema       = $this->getSqliteSchemaSignature($idasDb);
            } catch (Throwable $e) {
                $diffs[] = "{$name}: schema read failed ({$e->getMessage()})";
                continue;
            }

            $schemaDiff = $this->diffSqliteSchemaSignature($controllerSchema, $idasSchema);
            if (!empty($schemaDiff)) {
                $diffs[] = "{$name}: " . implode('; ', array_slice($schemaDiff, 0, 6));
            }
        }

        if (!empty($diffs)) {
            return [
                'compatible' => false,
                'detail' => implode(' | ', $diffs),
            ];
        }

        return ['compatible' => true, 'detail' => 'controller/iDAS DB schema compatible'];
    }

    /**
     * 取得 SQLite schema 簽章。
     * 比對 table 名稱、欄位名稱、欄位型別、notnull、pk。
     */
    private function getSqliteSchemaSignature(string $dbPath): array {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $tableStmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
        $tableNames = $tableStmt->fetchAll(PDO::FETCH_COLUMN);

        $schema = [];
        foreach ($tableNames as $tableName) {
            $quotedTable = '"' . str_replace('"', '""', (string)$tableName) . '"';
            $colStmt = $pdo->query("PRAGMA table_info({$quotedTable})");
            $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);

            $schema[(string)$tableName] = array_map(function($column) {
                return [
                    'name'    => (string)($column['name'] ?? ''),
                    'type'    => strtoupper(trim((string)($column['type'] ?? ''))),
                    'notnull' => (int)($column['notnull'] ?? 0),
                    'pk'      => (int)($column['pk'] ?? 0),
                ];
            }, $columns);
        }

        return $schema;
    }

    /**
     * 比對兩份 SQLite schema 簽章。
     */
    private function diffSqliteSchemaSignature(array $controllerSchema, array $idasSchema): array {
        $diffs = [];

        $controllerTables = array_keys($controllerSchema);
        $idasTables = array_keys($idasSchema);

        $missingInIdas = array_values(array_diff($controllerTables, $idasTables));
        $extraInIdas   = array_values(array_diff($idasTables, $controllerTables));

        if (!empty($missingInIdas)) {
            $diffs[] = 'missing table in iDAS: ' . implode(',', $missingInIdas);
        }
        if (!empty($extraInIdas)) {
            $diffs[] = 'extra table in iDAS: ' . implode(',', $extraInIdas);
        }

        foreach (array_intersect($controllerTables, $idasTables) as $table) {
            $controllerCols = $controllerSchema[$table];
            $idasCols       = $idasSchema[$table];

            if (count($controllerCols) !== count($idasCols)) {
                $diffs[] = "{$table}: column count differs (controller=" . count($controllerCols) . ', iDAS=' . count($idasCols) . ')';
                continue;
            }

            foreach ($controllerCols as $idx => $controllerCol) {
                $idasCol = $idasCols[$idx] ?? null;
                if ($idasCol === null || $controllerCol !== $idasCol) {
                    $cName = $controllerCol['name'] ?? ('#' . $idx);
                    $iName = is_array($idasCol) ? ($idasCol['name'] ?? ('#' . $idx)) : 'missing';
                    $diffs[] = "{$table}: column #{$idx} differs (controller={$cName}, iDAS={$iName})";
                    break;
                }
            }
        }

        return $diffs;
    }

    /**
     * 讀取 .pack 內的 info.json，不解壓整包。
     * 支援 info.json 位於根目錄或第一層資料夾內。
     */
    private function readIdasPackInfoFromZip(string $packPath): array {
        $zip = new ZipArchive();
        if ($zip->open($packPath) !== true) {
            throw new Exception($this->t('ERR_OPEN_PACK'));
        }

        $infoJson = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = (string)$zip->getNameIndex($i);
            if (basename($entryName) === 'info.json') {
                $infoJson = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();

        if ($infoJson === null || $infoJson === false || trim((string)$infoJson) === '') {
            throw new Exception($this->t('ERR_MISSING_INFO'));
        }

        $data = json_decode((string)$infoJson, true);
        if (!is_array($data) || empty($data['idas_version'])) {
            throw new Exception($this->t('ERR_BAD_INFO'));
        }

        return $data;
    }


    /**
     * JSON API 回應工具，不使用 generateErrorResponse，避免預檢 API 被包成既有 alert 格式。
     */
    private function sendJsonResponse(bool $success, string $message = '', array $extra = []): void {
        while (ob_get_level()) {
            @ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message,
        ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }



    /**
     * HTML checkbox / FormData flag parser.
     * 只有明確傳入 1 / true / on / yes 才視為啟用。
     */
    private function isTruthyFlag($value): bool {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string)$value));
        return in_array($value, ['1', 'true', 'on', 'yes', 'y'], true);
    }


    /**
     * 取得 iDAS 版本中的數字 base version，用於 version_compare。
     * 例：0.72.0_SA349 → 0.72、0.72_SA349 → 0.72、0.74 → 0.74。
     */
    private function normalizeIdasVersionBase(string $version): string {
        $version = trim($version);
        if ($version === '') {
            return '0.0.0';
        }

        if (preg_match('/\d+(?:\.\d+)*/', $version, $matches)) {
            $base = $matches[0];
            $parts = explode('.', $base);
            while (count($parts) > 2 && end($parts) === '0') {
                array_pop($parts);
            }
            return implode('.', $parts);
        }

        return '0.0.0';
    }


    /**
     * 取得 iDAS 版本 suffix / branch，例如 0.72_SA349 → SA349。
     */
    private function normalizeIdasVersionSuffix(string $version): string {
        $version = trim($version);
        if ($version === '') {
            return '';
        }

        $normalized = str_replace(['-', '+'], '_', $version);
        if (preg_match('/^\s*\d+(?:\.\d+)*_([A-Za-z0-9][A-Za-z0-9_]*)\s*$/', $normalized, $matches)) {
            return strtoupper($matches[1]);
        }

        return '';
    }


    /**
     * 統一 iDAS 版本顯示格式。
     * 例：0.72.0_SA349 → 0.72_SA349。
     */
    private function normalizeIdasVersionDisplay(string $version): string {
        $version = trim($version);
        if ($version === '') {
            return '';
        }

        $displayBase = $this->normalizeIdasVersionBase($version);
        $suffix = $this->normalizeIdasVersionSuffix($version);

        return $suffix !== '' ? $displayBase . '_' . $suffix : $displayBase;
    }



    /**
     * 降版本時重建 iDAS DB。
     *
     * 規則：
     * - 只在 iDAS 降版本或 SA349 切回一般版本且使用者完成確認時呼叫。
     * - 呼叫時機：舊版 .pack 安裝完成後，再從 /home/kls/NTCS7 複製乾淨 DB 回 iDAS。
     * - 只處理 iDAS 使用的 LIN / Barcode / Device / Data DB。
     * - 不清空、不覆蓋 das.db，保留登入帳號、權限與 iDAS config。
     * - 先將來源複製到同目錄暫存檔，確認成功後才替換目的檔，降低半更新風險。
     */
    /**
     * 所有需要重建 DB 的版本轉換，在切程式前先確認 Controller 來源 DB 可讀且 SQLite 健康。
     */
    private function assertIdasDbRebuildSourcesHealthy(): void {
        if (PHP_OS_FAMILY !== 'Linux') {
            throw new Exception('Database rebuild is only supported on Linux.');
        }

        foreach ([
            '/home/kls/NTCS7/KLS_NTCS.Lin',
            '/home/kls/NTCS7/ntcs_barcode.db',
            '/home/kls/NTCS7/ntcs_device.db',
            '/home/kls/NTCS7/ntcs_data.db',
        ] as $sourcePath) {
            if (!is_file($sourcePath) || !is_readable($sourcePath) || (int)@filesize($sourcePath) <= 0) {
                throw new Exception("DB rebuild source missing/unreadable/empty: {$sourcePath}");
            }
            $this->assertSqliteHealthy($sourcePath);
        }
    }

    /**
     * 建立 Update transaction rollback snapshot。
     * iDAS DB 全部保留；另外保留 Controller KLS_NTCS.Lin，因 Standard 版本會移除 admin。
     */
    private function createIdasUpdateRollbackSnapshot(): string {
        if (PHP_OS_FAMILY !== 'Linux') {
            return '';
        }

        $baseDir = '/var/www/html/database';
        $snapshotDir = $baseDir . '/.idas_update_rollback_' . date('Ymd_His') . '_' . getmypid();
        if (!@mkdir($snapshotDir, 0777, true) && !is_dir($snapshotDir)) {
            throw new Exception("Cannot create rollback snapshot: {$snapshotDir}");
        }

        $items = [
            '/var/www/html/database/KLS_NTCS_IDAS.Lin'    => 'database/KLS_NTCS_IDAS.Lin',
            '/var/www/html/database/ntcs_barcode_IDAS.db'=> 'database/ntcs_barcode_IDAS.db',
            '/var/www/html/database/ntcs_device_IDAS.db' => 'database/ntcs_device_IDAS.db',
            '/var/www/html/database/ntcs_data.db'        => 'database/ntcs_data.db',
            '/home/kls/NTCS7/KLS_NTCS.Lin'               => 'controller/KLS_NTCS.Lin',
        ];

        $manifest = [];
        try {
            foreach ($items as $source => $relative) {
                $exists = is_file($source);
                $manifest[] = [
                    'destination' => $source,
                    'relative' => $relative,
                    'existed' => $exists,
                ];
                if (!$exists) {
                    continue;
                }

                $dest = $snapshotDir . '/' . $relative;
                $parent = dirname($dest);
                if (!is_dir($parent) && !@mkdir($parent, 0777, true) && !is_dir($parent)) {
                    throw new Exception("Cannot create snapshot folder: {$parent}");
                }
                if (!@copy($source, $dest)) {
                    throw new Exception("Rollback snapshot copy failed: {$source}");
                }
                if (@filesize($source) !== @filesize($dest) || (int)@filesize($dest) <= 0) {
                    throw new Exception("Rollback snapshot size mismatch: {$source}");
                }
                @chmod($dest, 0666);
                $this->assertSqliteHealthy($dest);
            }

            $manifestPath = $snapshotDir . '/manifest.json';
            if (@file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
                throw new Exception('Cannot write rollback snapshot manifest');
            }
        } catch (Throwable $e) {
            try { $this->deleteDirectory($snapshotDir); } catch (Throwable $ignore) {}
            throw $e;
        }

        $this->logMessage("[iDAS UPDATE] transaction rollback snapshot created: {$snapshotDir}");
        return $snapshotDir;
    }

    private function restoreIdasUpdateRollbackSnapshot(string $snapshotDir): void {
        $manifestPath = rtrim($snapshotDir, '/') . '/manifest.json';
        $manifest = json_decode((string)@file_get_contents($manifestPath), true);
        if (!is_array($manifest)) {
            throw new Exception("Invalid rollback manifest: {$manifestPath}");
        }

        foreach ($manifest as $item) {
            $destination = (string)($item['destination'] ?? '');
            $relative = (string)($item['relative'] ?? '');
            $existed = !empty($item['existed']);
            if ($destination === '' || $relative === '') {
                throw new Exception('Invalid rollback manifest item');
            }

            if (!$existed) {
                if (is_file($destination) && !@unlink($destination)) {
                    throw new Exception("Rollback remove failed: {$destination}");
                }
                continue;
            }

            $source = rtrim($snapshotDir, '/') . '/' . $relative;
            if (!is_file($source)) {
                throw new Exception("Rollback source missing: {$source}");
            }
            $this->atomicCopyFile($source, $destination, true);
            $this->logMessage("[iDAS UPDATE] rollback restored: {$destination}");
        }
    }

    private function deleteIdasUpdateRollbackSnapshot(string $snapshotDir): void {
        if ($snapshotDir !== '' && is_dir($snapshotDir)) {
            try {
                $this->deleteDirectory($snapshotDir);
            } catch (Throwable $e) {
                $this->logMessage('[iDAS UPDATE] warning: rollback snapshot cleanup failed: ' . $e->getMessage());
            }
        }
    }

    private function atomicCopyFile(string $source, string $destination, bool $sqliteCheck = false): void {
        if (!is_file($source) || !is_readable($source)) {
            throw new Exception("Atomic copy source unavailable: {$source}");
        }

        $parent = dirname($destination);
        if (!is_dir($parent) && !@mkdir($parent, 0777, true) && !is_dir($parent)) {
            throw new Exception("Atomic copy destination folder unavailable: {$parent}");
        }

        $tmp = $parent . '/.' . basename($destination) . '.idas_atomic_' . getmypid() . '_' . bin2hex(random_bytes(3));
        if (!@copy($source, $tmp)) {
            throw new Exception("Atomic copy failed: {$source} -> {$tmp}");
        }

        try {
            $sourceSize = @filesize($source);
            $tmpSize = @filesize($tmp);
            if ($sourceSize === false || $tmpSize === false || $sourceSize !== $tmpSize || $tmpSize <= 0) {
                throw new Exception("Atomic copy size mismatch: {$source}");
            }
            if ($sqliteCheck) {
                $this->assertSqliteHealthy($tmp);
            }
            @chmod($tmp, 0666);
            if (!@rename($tmp, $destination)) {
                $err = error_get_last();
                throw new Exception("Atomic replace failed: {$destination}; " . ($err['message'] ?? 'unknown error'));
            }
            @chmod($destination, 0666);
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    /**
     * 降版本或 Standard↔SA349 時重建 iDAS DB。
     * 先把全部來源建到 staging 並驗證，再開始 commit；任何一筆失敗會把所有 DB 還原。
     */
    private function rebuildIDasDatabaseForVersionTransition(string $currentVersion, string $targetVersion, string $reason = 'version_transition'): void {
        if (PHP_OS_FAMILY !== 'Linux') {
            throw new Exception('Database rebuild is only supported on Linux.');
        }

        $sourceDir   = '/home/kls/NTCS7';
        $databaseDir = '/var/www/html/database';
        $copyMap = [
            'KLS_NTCS.Lin'    => 'KLS_NTCS_IDAS.Lin',
            'ntcs_barcode.db' => 'ntcs_barcode_IDAS.db',
            'ntcs_device.db'  => 'ntcs_device_IDAS.db',
            'ntcs_data.db'    => 'ntcs_data.db',
        ];

        $this->assertIdasDbRebuildSourcesHealthy();
        if (!is_writable($databaseDir)) {
            throw new Exception("Database directory is not writable: {$databaseDir}");
        }

        $token = date('Ymd_His') . '_' . getmypid();
        $stageDir  = $databaseDir . '/.idas_db_stage_' . $token;
        $backupDir = $databaseDir . '/.idas_db_commit_backup_' . $token;
        if (!@mkdir($stageDir, 0777, true) || !@mkdir($backupDir, 0777, true)) {
            throw new Exception('Cannot create DB transaction staging/backup folders');
        }

        $originalExists = [];
        try {
            $this->logMessage("[iDAS UPDATE] DB rebuild staging start: reason={$reason}, current={$currentVersion}, target={$targetVersion}");

            // Stage ALL first.
            foreach ($copyMap as $sourceName => $targetName) {
                $sourcePath = $sourceDir . '/' . $sourceName;
                $stagePath  = $stageDir . '/' . $targetName;
                if (!@copy($sourcePath, $stagePath)) {
                    throw new Exception("DB stage copy failed: {$sourcePath}");
                }
                if (@filesize($sourcePath) !== @filesize($stagePath) || (int)@filesize($stagePath) <= 0) {
                    throw new Exception("DB stage size mismatch: {$sourceName}");
                }
                @chmod($stagePath, 0666);
                $this->assertSqliteHealthy($stagePath);
            }

            // Backup ALL current targets before first commit.
            foreach ($copyMap as $sourceName => $targetName) {
                $targetPath = $databaseDir . '/' . $targetName;
                $backupPath = $backupDir . '/' . $targetName;
                $originalExists[$targetName] = is_file($targetPath);
                if ($originalExists[$targetName]) {
                    if (!@copy($targetPath, $backupPath)) {
                        throw new Exception("DB commit backup failed: {$targetPath}");
                    }
                    @chmod($backupPath, 0666);
                    $this->assertSqliteHealthy($backupPath);
                }
            }

            // Commit staged files. rename() over an existing file on the same FS is atomic.
            foreach ($copyMap as $sourceName => $targetName) {
                $stagePath  = $stageDir . '/' . $targetName;
                $targetPath = $databaseDir . '/' . $targetName;
                if (!@rename($stagePath, $targetPath)) {
                    $err = error_get_last();
                    throw new Exception("DB commit failed: {$targetName}; " . ($err['message'] ?? 'unknown error'));
                }
                @chmod($targetPath, 0666);
                $this->assertSqliteHealthy($targetPath);
            }

            $this->logMessage("[iDAS UPDATE] DB rebuild commit complete: reason={$reason}");
        } catch (Throwable $e) {
            // Internal DB rollback: restore every target, not only the file that failed.
            foreach ($copyMap as $sourceName => $targetName) {
                $targetPath = $databaseDir . '/' . $targetName;
                $backupPath = $backupDir . '/' . $targetName;
                try {
                    if (!empty($originalExists[$targetName])) {
                        if (is_file($backupPath)) {
                            $this->atomicCopyFile($backupPath, $targetPath, true);
                        }
                    } elseif (is_file($targetPath)) {
                        @unlink($targetPath);
                    }
                } catch (Throwable $restoreError) {
                    $this->logMessage('[iDAS UPDATE] DB internal rollback warning: ' . $restoreError->getMessage());
                }
            }
            throw $e;
        } finally {
            if (is_dir($stageDir)) {
                try { $this->deleteDirectory($stageDir); } catch (Throwable $ignore) {}
            }
            if (is_dir($backupDir)) {
                try { $this->deleteDirectory($backupDir); } catch (Throwable $ignore) {}
            }
        }
    }



    /**
     * 非 SA349 版本要移除 Controller / iDAS LIN DB 裡的 admin 帳號。
     *
     * 呼叫時機：.pack 安裝完成、必要的降版本/SA349 DB 重建完成之後。
     * 原因：如果先刪 admin，再做 DB 重建，/home/kls/NTCS7/KLS_NTCS.Lin
     * 會再次被複製到 /var/www/html/database/KLS_NTCS_IDAS.Lin，admin 會被帶回來。
     */
    private function removeAdminUserForNonSa349Version(string $activeVersion): void {
        if (PHP_OS_FAMILY !== 'Linux') {
            $this->logMessage('[iDAS UPDATE] skip remove admin user: non-Linux environment');
            return;
        }

        $suffix = $this->normalizeIdasVersionSuffix($activeVersion);
        if ($suffix === 'SA349') {
            $this->logMessage("[iDAS UPDATE] keep admin user because active version is SA349: {$activeVersion}");
            return;
        }

        $dbList = [
            '/home/kls/NTCS7/KLS_NTCS.Lin',
            '/var/www/html/database/KLS_NTCS_IDAS.Lin',
        ];

        $this->logMessage("[iDAS UPDATE] remove admin user start for non-SA349 version: {$activeVersion}");
        foreach ($dbList as $dbPath) {
            $this->removeAdminUserFromUserTable($dbPath);
        }
        $this->logMessage("[iDAS UPDATE] remove admin user end for non-SA349 version: {$activeVersion}");
    }


    /**
     * 從 SQLite table `user` 移除 admin 帳號。
     *
     * 目前登入程式主要使用 user.name；為了相容不同 LIN schema，若存在
     * username / user_name / account / account_name 也一併作為 admin 帳號欄位判斷。
     */
    private function removeAdminUserFromUserTable(string $dbPath): void {
        if (!is_file($dbPath)) {
            throw new Exception("LIN DB not found: {$dbPath}");
        }
        if (!is_readable($dbPath)) {
            throw new Exception("LIN DB is not readable: {$dbPath}");
        }
        if (!is_writable($dbPath)) {
            throw new Exception("LIN DB is not writable: {$dbPath}");
        }

        try {
            $db = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3,
            ]);
            $db->exec('PRAGMA busy_timeout = 3000');

            $tableOk = (bool)$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='user' LIMIT 1")->fetchColumn();
            if (!$tableOk) {
                $this->logMessage("[iDAS UPDATE] skip remove admin user, table user not found: {$dbPath}");
                return;
            }

            $columnRows = $db->query("PRAGMA table_info('user')")->fetchAll(PDO::FETCH_ASSOC);
            $columns = [];
            foreach ($columnRows as $columnRow) {
                if (isset($columnRow['name'])) {
                    $columns[strtolower((string)$columnRow['name'])] = (string)$columnRow['name'];
                }
            }

            $candidateColumns = ['name', 'username', 'user_name', 'account', 'account_name'];
            $whereParts = [];
            $quote = static function(string $identifier): string {
                return '"' . str_replace('"', '""', $identifier) . '"';
            };

            foreach ($candidateColumns as $candidateColumn) {
                if (isset($columns[$candidateColumn])) {
                    $whereParts[] = 'LOWER(TRIM(CAST(' . $quote($columns[$candidateColumn]) . ' AS TEXT))) = :admin';
                }
            }

            if (empty($whereParts)) {
                $this->logMessage("[iDAS UPDATE] skip remove admin user, no account column found in table user: {$dbPath}");
                return;
            }

            $db->beginTransaction();
            $stmt = $db->prepare('DELETE FROM "user" WHERE ' . implode(' OR ', $whereParts));
            $stmt->bindValue(':admin', 'admin', PDO::PARAM_STR);
            $stmt->execute();
            $rows = (int)$stmt->rowCount();
            $db->commit();

            @chmod($dbPath, 0666);
            $this->logMessage("[iDAS UPDATE] remove admin user from {$dbPath}; rows={$rows}");
        } catch (Throwable $e) {
            if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
                $db->rollBack();
            }
            throw new Exception("Remove admin user failed in {$dbPath}: " . $e->getMessage());
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
            'ERR_UPDATE_BUSY'   => [
                'en-us' => 'iDAS is updating. Please wait until the current update finishes.',
                'zh-tw' => 'iDAS 正在更新中，請等待目前更新完成後再操作。',
                'zh-cn' => 'iDAS 正在更新中，请等待当前更新完成后再操作。',
            ],
            'ERR_UPDATE_LOCK'   => [
                'en-us' => 'Unable to create the update lock file.',
                'zh-tw' => '無法建立更新鎖定檔。',
                'zh-cn' => '无法建立更新锁定文件。',
            ],
            'ERR_PACK_UNSAFE'   => [
                'en-us' => 'The update package contains an unsafe path and has been rejected.',
                'zh-tw' => '更新包內含不安全路徑，已拒絕更新。',
                'zh-cn' => '更新包包含不安全路径，已拒绝更新。',
            ],
            'ERR_PACK_TOO_LARGE_AFTER_EXTRACT' => [
                'en-us' => 'The update package is too large after extraction.',
                'zh-tw' => '更新包解壓後容量過大。',
                'zh-cn' => '更新包解压后容量过大。',
            ],
            'ERR_BAD_PACK_STRUCTURE' => [
                'en-us' => 'Invalid iDAS update package structure. Please confirm this is an NTCS iDAS 7 package.',
                'zh-tw' => 'iDAS 更新包結構不正確，請確認是否為 NTCS iDAS 7 更新包。',
                'zh-cn' => 'iDAS 更新包结构不正确，请确认是否为 NTCS iDAS 7 更新包。',
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
                'en-us' => 'Update version is lower than current (current: {current}, update: {update}). Please select the package again and wait for automatic version detection before uploading.',
                'zh-tw' => '更新檔版本低於目前版本，無法更新（目前：{current}，更新：{update}）。請重新選擇更新檔，並等待系統完成自動版本判斷後再上傳。',
                'zh-cn' => '更新包版本低于当前版本，无法更新（当前：{current}，更新：{update}）。请重新选择更新包，并等待系统完成自动版本判断后再上传。',
            ],
            'ERR_SPECIAL_SWITCH_CONFIRM' => [
                'en-us' => 'Special version switch detected (current: {current}, update: {update}). Please select the package again and wait for automatic version detection before uploading.',
                'zh-tw' => '偵測到特殊版本切換（目前：{current}，更新：{update}）。請重新選擇更新檔，並等待系統完成自動版本判斷後再上傳。',
                'zh-cn' => '检测到特殊版本切换（当前：{current}，更新：{update}）。请重新选择更新包，并等待系统完成自动版本判断后再上传。',
            ],
            'ERR_BACKUP_FAILED' => [
                'en-us' => 'Pre-update backup failed: {detail}',
                'zh-tw' => '更新前備份失敗：{detail}',
                'zh-cn' => '更新前备份失败：{detail}',
            ],
            'ERR_DB_SCHEMA_MISMATCH_TITLE' => [
                'en-us' => 'Database format mismatch. Update stopped.',
                'zh-tw' => '資料庫格式不相容，更新已中止',
                'zh-cn' => '数据库格式不兼容，更新已中止',
            ],
            'ERR_DB_SCHEMA_MISMATCH_MSG' => [
                'en-us' => "Downgrade detected, but the Controller DB format is different from the iDAS DB format.

To prevent the system from reading incompatible data after downgrade, this update has been stopped. System files and databases have not been changed.

Please confirm that the Controller DB and iDAS DB versions are compatible, or use a matching update package and try again.",
                'zh-tw' => "偵測到降版本，但 Controller DB 與 iDAS DB 的資料庫格式不相容。

為避免降版本後系統無法正常讀取資料，本次更新已中止，尚未變更系統檔案與資料庫。

請確認 Controller 與 iDAS 的資料庫版本是否相容，或改用對應版本的更新包後再重新執行。",
                'zh-cn' => "检测到降版本，但 Controller DB 与 iDAS DB 的数据库格式不兼容。

为避免降版本后系统无法正常读取数据，本次更新已中止，尚未变更系统文件与数据库。

请确认 Controller 与 iDAS 的数据库版本是否兼容，或改用对应版本的更新包后再重新执行。",
            ],
            'ERR_DOWNGRADE_DB_RESET' => [
                'en-us' => 'Downgrade database rebuild failed: {detail}',
                'zh-tw' => '降版本 iDAS DB 重建失敗：{detail}',
                'zh-cn' => '降版本 iDAS DB 重建失败：{detail}',
            ],
            'ERR_UPDATE_ROLLBACK' => [
                'en-us' => 'Update failed and rollback was executed. Error: {detail}. Rollback: {rollback}.',
                'zh-tw' => '更新失敗，系統已執行 rollback。錯誤：{detail}。Rollback：{rollback}。',
                'zh-cn' => '更新失败，系统已执行 rollback。错误：{detail}。Rollback：{rollback}。',
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
            'SUC_BACKUP'        => [
                'en-us' => 'Backup file: {backup}',
                'zh-tw' => '備份檔案：{backup}',
                'zh-cn' => '备份文件：{backup}',
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
        while (ob_get_level()) {
            @ob_end_clean();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'res_type' => $type,
            'res_msg' => $msg,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return null;
    }

    /**
     * iDAS Update 專用 JSON 回應：保留既有 res_type / res_msg，額外帶 error_code 給前端判斷。
     */
    private function sendResponseWithCode(string $type, string $msg, string $errorCode, array $extra = []) {
        while (ob_get_level()) {
            @ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array_merge([
            'result'     => strtolower($type) === 'success',
            'res_type'   => $type,
            'res_msg'    => $msg,
            'error_code' => $errorCode,
        ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return null;
    }

    private function assertIdasInstalledProgramHealthy(string $directory, string $expectedVersion): void {
        $directory = rtrim($directory, '/');
        foreach ([
            $directory . '/info.json',
            $directory . '/app/bootstrap.php',
            $directory . '/public/index.php',
        ] as $requiredFile) {
            if (!is_file($requiredFile) || !is_readable($requiredFile) || (int)@filesize($requiredFile) <= 0) {
                throw new Exception("Installed program check failed: {$requiredFile}");
            }
        }

        $info = json_decode((string)@file_get_contents($directory . '/info.json'), true);
        $actual = is_array($info) ? $this->normalizeIdasVersionDisplay((string)($info['idas_version'] ?? '')) : '';
        if ($actual !== $expectedVersion) {
            throw new Exception("Installed program version mismatch: expected={$expectedVersion}, actual={$actual}");
        }
    }

    private function assertIdasPostUpdateHealthy(string $targetDirectory, string $targetVersion, bool $dbWasRebuilt): void {
        $this->assertIdasInstalledProgramHealthy($targetDirectory, $targetVersion);

        $databaseDir = '/var/www/html/database';
        $dbFiles = [
            'KLS_NTCS_IDAS.Lin',
            'ntcs_barcode_IDAS.db',
            'ntcs_device_IDAS.db',
            'ntcs_data.db',
            'das.db',
        ];
        foreach ($dbFiles as $dbName) {
            $dbPath = $databaseDir . '/' . $dbName;
            if (!is_file($dbPath) || !is_readable($dbPath) || (int)@filesize($dbPath) <= 0) {
                throw new Exception("Post-check DB missing/unreadable/empty: {$dbPath}");
            }
            $this->assertSqliteHealthy($dbPath);
        }

        if ($dbWasRebuilt) {
            $pairs = [
                ['/home/kls/NTCS7/KLS_NTCS.Lin',    $databaseDir . '/KLS_NTCS_IDAS.Lin'],
                ['/home/kls/NTCS7/ntcs_barcode.db', $databaseDir . '/ntcs_barcode_IDAS.db'],
                ['/home/kls/NTCS7/ntcs_device.db',  $databaseDir . '/ntcs_device_IDAS.db'],
                ['/home/kls/NTCS7/ntcs_data.db',    $databaseDir . '/ntcs_data.db'],
            ];
            foreach ($pairs as [$sourceDb, $idasDb]) {
                $diff = $this->diffSqliteSchemaSignature(
                    $this->getSqliteSchemaSignature($sourceDb),
                    $this->getSqliteSchemaSignature($idasDb)
                );
                if (!empty($diff)) {
                    throw new Exception('Post-check rebuilt DB schema mismatch: ' . basename($idasDb) . ' - ' . implode('; ', array_slice($diff, 0, 3)));
                }
            }
        }

        $this->logMessage('[iDAS UPDATE] post-check passed: program + DB integrity/schema');
    }

    private function rollbackIdasProgramDeployment(
        string $targetDirectory,
        string $oldDirectory,
        string $stagingDirectory,
        bool $hadTargetDirectory
    ): void {
        if (is_dir($oldDirectory)) {
            if (is_dir($targetDirectory)) {
                $failedDirectory = rtrim($targetDirectory, '/') . '_failed_' . date('Ymd_His') . '_' . getmypid();
                if (!@rename($targetDirectory, $failedDirectory)) {
                    // 只有 rename 失敗才改成刪除，避免 target 與 old 混在一起。
                    $this->deleteDirectory($targetDirectory);
                }
                if (is_dir($failedDirectory)) {
                    try { $this->deleteDirectory($failedDirectory); } catch (Throwable $ignore) {}
                }
            }
            if (!@rename($oldDirectory, $targetDirectory)) {
                throw new Exception('Program rollback failed: cannot restore idas_old');
            }
            $this->logMessage('[iDAS UPDATE] program rollback restored idas_old -> idas');
        } elseif (!$hadTargetDirectory && is_dir($targetDirectory)) {
            $this->deleteDirectory($targetDirectory);
            $this->logMessage('[iDAS UPDATE] program rollback removed newly installed idas (no previous target)');
        }

        if (is_dir($stagingDirectory)) {
            try { $this->deleteDirectory($stagingDirectory); } catch (Throwable $ignore) {}
        }
    }

    private function copyDirectory($source, $destination) {
        if (!is_dir($source)) {
            throw new Exception("Copy source directory not found: {$source}");
        }
        if (!is_dir($destination) && !@mkdir($destination, 0777, true) && !is_dir($destination)) {
            throw new Exception("Cannot create destination directory: {$destination}");
        }

        $entries = scandir($source);
        if ($entries === false) {
            throw new Exception("Cannot scan source directory: {$source}");
        }

        foreach ($entries as $file) {
            if (in_array($file, ['.', '..'], true)) continue;
            $src = $source . '/' . $file;
            $dst = $destination . '/' . $file;
            if (is_dir($src)) {
                $this->copyDirectory($src, $dst);
            } else {
                if (file_exists($dst) && !@unlink($dst)) {
                    throw new Exception("Cannot replace staging file: {$dst}");
                }
                if (!@copy($src, $dst)) {
                    throw new Exception("File copy failed: {$src} -> {$dst}");
                }
                if (@filesize($src) !== @filesize($dst)) {
                    throw new Exception("File copy size mismatch: {$src}");
                }
            }
        }
    }

    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        $entries = scandir($dir);
        if ($entries === false) {
            throw new Exception("Cannot scan directory for delete: {$dir}");
        }
        foreach ($entries as $file) {
            if (in_array($file, ['.', '..'], true)) continue;
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } elseif (file_exists($path) && !@unlink($path)) {
                throw new Exception("Cannot delete file: {$path}");
            }
        }
        if (is_dir($dir) && !@rmdir($dir)) {
            throw new Exception("Cannot remove directory: {$dir}");
        }
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

        header('Content-Type: application/json; charset=utf-8');

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        $lang = strtolower($_COOKIE['language'] ?? ($_SESSION['language'] ?? 'en-us'));
        if ($lang === 'en') {
            $lang = 'en-us';
        }
        if (!in_array($lang, ['en-us', 'zh-tw', 'zh-cn'], true)) {
            $lang = 'en-us';
        }

        $msg = [
            'en-us' => [
                'no_file'       => 'Please upload a valid configuration file format.',
                'bad_upload'    => 'Please upload a valid configuration file format.',
                'bad_name'      => 'Please upload a valid configuration file format.',
                'bad_ext'       => 'Please upload a valid configuration file format.',
                'not_sqlite'    => 'Please upload a valid configuration file format.',
                'missing_table' => 'Please upload a valid configuration file format.',
                'bad_dir'       => 'Upload directory is not writable.',
                'replace_fail'  => 'Failed to replace iDAS system database.',
                'copy_fail'     => 'Failed to prepare Controller import file.',
                'success'       => 'Import successful.',
                'protocol_error'=> 'Controller communication failed.',
                'controller_logged_in' => 'Please log out of the Controller before importing system data.',
                'controller_status_error' => 'Unable to confirm the Controller login status.',
            ],
            'zh-tw' => [
                'no_file'       => '請上傳正確的設定檔格式。',
                'bad_upload'    => '請上傳正確的設定檔格式。',
                'bad_name'      => '請上傳正確的設定檔格式。',
                'bad_ext'       => '請上傳正確的設定檔格式。',
                'not_sqlite'    => '請上傳正確的設定檔格式。',
                'missing_table' => '請上傳正確的設定檔格式。',
                'bad_dir'       => '上傳目錄不可寫入。',
                'replace_fail'  => '替換 iDAS 系統資料失敗。',
                'copy_fail'     => '建立控制器匯入檔案失敗。',
                'success'       => '匯入成功。',
                'protocol_error'=> '控制器通訊失敗。',
                'controller_logged_in' => '匯入系統資料前，請先將控制器登出。',
                'controller_status_error' => '無法確認控制器登入狀態。',
            ],
            'zh-cn' => [
                'no_file'       => '请上传正确的设置文件格式。',
                'bad_upload'    => '请上传正确的设置文件格式。',
                'bad_name'      => '请上传正确的设置文件格式。',
                'bad_ext'       => '请上传正确的设置文件格式。',
                'not_sqlite'    => '请上传正确的设置文件格式。',
                'missing_table' => '请上传正确的设置文件格式。',
                'bad_dir'       => '上传目录不可写入。',
                'replace_fail'  => '替换 iDAS 系统资料失败。',
                'copy_fail'     => '建立控制器导入文件失败。',
                'success'       => '导入成功。',
                'protocol_error'=> '控制器通讯失败。',
                'controller_logged_in' => '导入系统资料前，请先将控制器登出。',
                'controller_status_error' => '无法确认控制器登录状态。',
            ],
        ];
        $T = $msg[$lang];

        $fail = function (string $key) use ($T) {
            $this->MiscellaneousModel->generateErrorResponse('Error', $T[$key] ?? $key);
            return;
        };

        // 1. Upload + filename validation.
        if (empty($_FILES) || !isset($_FILES['file'])) {
            return $fail('no_file');
        }
        if (($_FILES['file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return $fail('bad_upload');
        }

        $originalName = basename((string)($_FILES['file']['name'] ?? ''));
        $fileInfo     = pathinfo($originalName);
        $ext          = strtolower($fileInfo['extension'] ?? '');
        $tmpFile      = (string)($_FILES['file']['tmp_name'] ?? '');

        // Only: con_<ControllerSN>_<YYYYMMDDHHMMSS>.Lin
        if (!preg_match('/^con_[A-Za-z0-9_-]+_\d{14}\.Lin$/', $originalName)) {
            return $fail('bad_name');
        }
        if ($ext !== 'lin') {
            return $fail('bad_ext');
        }
        if ($tmpFile === '' || !is_uploaded_file($tmpFile)) {
            return $fail('bad_upload');
        }

        // 2. SQLite / required table validation.
        try {
            $this->assertSqliteHealthy($tmpFile);

            $checkDb = new PDO('sqlite:' . $tmpFile);
            $checkDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $checkDb->prepare(
                "SELECT name FROM sqlite_master WHERE type='table' AND name=:table LIMIT 1"
            );
            $stmt->execute([':table' => 'SEQ_type']);
            $hasSeqType = (bool)$stmt->fetchColumn();
            $checkDb = null;
        } catch (Throwable $e) {
            $this->logMessage('[Import_Config] SQLite validation failed: ' . $e->getMessage());
            return $fail('not_sqlite');
        }

        if (!$hasSeqType) {
            return $fail('missing_table');
        }

        // 3. Controller must be logged out before DB replacement / notification.
        $unitId = isset($this->deviceId) ? (int)$this->deviceId : 1;
        if ($unitId < 1 || $unitId > 255) {
            $controllerInfo = (array)($this->SettingModel->GetControllerInfo() ?? []);
            $unitId = (int)($controllerInfo['device_id'] ?? 1);
        }
        if ($unitId < 1 || $unitId > 255) {
            $unitId = 1;
        }

        try {
            $loginCheck = $this->idas_check($unitId);
            if (!is_array($loginCheck) || !empty($loginCheck['error']) || !array_key_exists('result', $loginCheck) || $loginCheck['result'] === null) {
                $detail = is_array($loginCheck) ? (string)($loginCheck['error'] ?? '') : 'invalid idas_check response';
                $this->logMessage('[Import_Config] Controller login-status check failed: ' . $detail);
                return $fail('controller_status_error');
            }
            if ((int)$loginCheck['result'] !== 0) {
                $this->logMessage('[Import_Config] blocked: Controller login register=' . (int)$loginCheck['result']);
                return $fail('controller_logged_in');
            }
        } catch (Throwable $e) {
            $this->logMessage('[Import_Config] Controller login-status exception: ' . $e->getMessage());
            return $fail('controller_status_error');
        }

        // 4. Replace local iDAS DB first. Existing DB is backed up by replaceSqliteDbFile().
        $idasDb = '/var/www/html/database/KLS_NTCS_IDAS.Lin';
        try {
            $this->replaceSqliteDbFile($tmpFile, $idasDb, true);
            $this->assertSqliteHealthy($idasDb);
        } catch (Throwable $e) {
            $this->logMessage('[Import_Config] replace KLS_NTCS_IDAS.Lin failed: ' . $e->getMessage());
            return $fail('replace_fail');
        }

        // 5. Prepare the exact DB that Controller will read: /mnt/ramdisk/ftp/11.Lin.
        $ftpDir = '/mnt/ramdisk/ftp';
        $linPath = $ftpDir . '/11.Lin';

        if (!is_dir($ftpDir) && !@mkdir($ftpDir, 0777, true)) {
            return $fail('bad_dir');
        }
        if (!is_writable($ftpDir)) {
            return $fail('bad_dir');
        }

        @unlink($linPath);
        if (!$this->safeCopy($idasDb, $linPath)) {
            return $fail('copy_fail');
        }
        @chmod($linPath, 0666);

        try {
            $srcSize = @filesize($idasDb);
            $dstSize = @filesize($linPath);
            if ($srcSize === false || $dstSize === false || $srcSize !== $dstSize || $dstSize < 1024) {
                throw new RuntimeException('11.Lin size verification failed');
            }
            $this->assertSqliteHealthy($linPath);
        } catch (Throwable $e) {
            $this->logMessage('[Import_Config] 11.Lin verification failed: ' . $e->getMessage());
            return $fail('copy_fail');
        }

        // 6. Notify Controller using the existing, OP-stable notifyModbus implementation.
        //    [1, 12593] => R506=1, R507=0x3131 ("11").
        try {
            $this->notifyModbus(null, [1, 12593], 'IMPORT_CONFIG_LIN');
            $this->logMessage(
                '[Import_Config] success: replaced KLS_NTCS_IDAS.Lin, prepared /mnt/ramdisk/ftp/11.Lin, notified Controller'
            );
            $this->MiscellaneousModel->generateErrorResponse('Success', $T['success']);
        } catch (Throwable $e) {
            $this->logMessage('[Import_Config] Controller notify failed: ' . $e->getMessage());
            $this->MiscellaneousModel->generateErrorResponse('Error', $T['protocol_error']);
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

        // 額外回傳實際通訊協議與判斷 register，方便現場直接確認 OP / MODBUS 路徑。
        $response['protocol'] = $this->is_op_protocol_enabled() ? 'OP' : 'MODBUS';
        $response['register'] = 29002;
        $response['device_id'] = $device_id;

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
        if ($this->is_op_protocol_enabled()) {
            usleep(350000);
        }

        // Barcode
        $this->safeCopy(
            '/var/www/html/database/ntcs_barcode_IDAS.db',
            '/mnt/ramdisk/ftp/11.db'
        );
        $this->notifyModbus($modbus, [1, 12593], 'DB');
    }














}
} else {
class Settings extends Controller
{
    private $SettingModel;
    private $AdminModel;
    private $ToolModel;
    private $MiscellaneousModel;
    private $DataModel;
    private $stepModel;
    Private $deviceId;
    private $AuditModel;

    // Uploaded sequence images are normalized to a fixed display size.
    // private const SEQ_IMG_WIDTH = 1140;
    // private const SEQ_IMG_HEIGHT = 800;
    private const SEQ_IMG_WIDTH  = 800;
    private const SEQ_IMG_HEIGHT = 1140;
    private const SEQ_IMG_DPI = 72;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct(){

        
        $this->SettingModel = $this->model('Setting');
        $this->AdminModel = $this->model('Admin');
        $this->ToolModel = $this->model('Tool');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->DataModel = $this->model('Datas');
        $this->stepModel = $this->model('Step');
        $this->AuditModel = $this->model('OperationAudit');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id
        // 使用 Modbus TCP 時必須先解析實際 unitId，避免預設 1 造成 Protocol read failed。
        $this->deviceId = $this->ntcs_device_db_sysnc();


    }

    /**
     * 強制 Barcode DB 使用 1:1 規則：
     * - 一個 JOB 只能有一個 Barcode
     * - 同一 Barcode 只能屬於一個 JOB
     */
    private function ensureBarcodeOneToOneDb(string $dbPath): array
    {
        $result = BarcodeSchemaGuard::ensureOneToOne($dbPath, 'ntcs_barcode_test');
        $this->logMessage('[Barcode DB 1:1] ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if (($result['status'] ?? 'failed') === 'failed') {
            throw new Exception('Barcode DB 1:1 migration failed: ' . (string)($result['message'] ?? 'unknown'));
        }

        return $result;
    }


    /**
     * 取得目前應使用的 Modbus TCP Unit ID。
     * 優先使用 ntcs_device_db_sysnc() 解析出的實際 device_id，
     * 再退回 cookie / iDAS DB controller info，最後才使用 1。
     */
    private function getActiveModbusUnitId(): int
    {
        $candidates = [];

        if (isset($this->deviceId) && $this->deviceId !== null && $this->deviceId !== '') {
            $candidates[] = (int)$this->deviceId;
        }

        if (!empty($_COOKIE['temp_device_id'])) {
            $candidates[] = (int)$_COOKIE['temp_device_id'];
        }

        try {
            $controllerInfo = (array)($this->SettingModel->GetControllerInfo() ?? []);
            if (isset($controllerInfo['device_id'])) {
                $candidates[] = (int)$controllerInfo['device_id'];
            }
        } catch (Throwable $e) {
            error_log('[Settings][Modbus] GetControllerInfo device_id failed: ' . $e->getMessage());
        }

        foreach ($candidates as $id) {
            if ($id >= 1 && $id <= 255) {
                return $id;
            }
        }

        return 1;
    }


    /**
     * 統一寫入控制器暫存器。
     *
     * - MODBUS TCP / RTU：走 Controller::protocol_write_registers()
     * - OP 協議：同一個 protocol_write_registers() 會自動轉成 IDAS_WRITE_xxx_xxx
     *
     * 避免 Settings.php 在 OP 協議時仍直接 new ModbusMaster，造成
     *「Modbus communication failed」或「Protocol read failed」。
     */
    private function writeControllerRegisters(int $startAddress, array $values, string $tag = ''): void
    {
        $unitId = $this->getActiveModbusUnitId();
        $values = array_values(array_map('intval', $values));

        if ($values === []) {
            return;
        }

        if (method_exists($this, 'protocol_write_registers')) {
            $ok = $this->protocol_write_registers($unitId, $startAddress, $values);
            if (!$ok) {
                throw new Exception('Protocol write failed' . ($tag !== '' ? ': ' . $tag : ''));
            }

            $protocol = (method_exists($this, 'is_op_protocol_enabled') && $this->is_op_protocol_enabled())
                ? 'OP'
                : 'MODBUS';
            $this->logMessage("{$protocol} write ({$tag}) [unitId={$unitId}, start={$startAddress}]: " . implode(',', $values));
            return;
        }

        // 舊版 Controller.php 尚未有 protocol_write_registers() 時的保底。
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
        $modbus = new ModbusMaster('127.0.0.1', 'TCP');
        $modbus->port = 502;
        $modbus->timeout_sec = 10;
        $types = array_fill(0, count($values), 'INT');
        $modbus->writeMultipleRegister($unitId, $startAddress, $values, $types);
        $this->logMessage("MODBUS fallback write ({$tag}) [unitId={$unitId}, start={$startAddress}]: " . implode(',', $values));
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
        // if ($this->shouldRunToolSpecSync(10)) {
        //     $this->runOnceWithFlag(
        //         '/var/www/html/database',        // lock / state 檔案目錄
        //         '.tool_spec_sync',               // 任務鎖名稱（key）
        //         fn() => $this->check_tools_info()// 同步 ntcs_tool_test 規格值
        //     );
        // }

        $isMobile = $this->isMobileCheck();

        $lang = $this->MiscellaneousModel->details('lang');
        $torque_unit = $this->MiscellaneousModel->details('torque_unit');
        $sample_rate = $this->MiscellaneousModel->details('sample_rate');
        $controller_info = $this->SettingModel->GetControllerInfo();
        $active_session = $this->AdminModel->GetActiveSession();
        $iDas_Vesion = $this->normalizeIdasVersionDisplay($this->AdminModel->Get_Das_Config('idas_version'));
        $max_user = $this->AdminModel->Get_Das_Config('max_concurrent_users');
        $agent_server_ip = $this->AdminModel->Get_Das_Config('agent_server_ip');
        $agent_type = $this->AdminModel->Get_Das_Config('agent_type');
        $job_list = $this->SettingModel->get_job_list();
        $barcode_mode = $this->MiscellaneousModel->details('barcode_mode');
        $idas_version = $this->SettingModel->get_idas_version();
        $disk_usage_percent = $this->SettingModel->system_storage();

        $history_year_arr = $this->DataModel->get_data_for_year();

        $iDAS_version = $this->normalizeIdasVersionDisplay($idas_version['config_value'] ?? $iDas_Vesion);

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


    public function SetAgentIp(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $ip = '';
        $result = false;

        // ✅ 檢查輸入
        if (isset($_POST['agent_server_ip']) && $_POST['agent_server_ip'] !== '') {
            $ip = $_POST['agent_server_ip'];
            $result = $this->AdminModel->Set_Agent_Ip($ip);
        }

        // ✅ 回傳
        if($result){
            $res_msg = $text['Edit'].' IP:'.$ip." ".$text['success'];
            $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg, $ip);
        }else{
            $res_msg = $text['Edit'].' IP:'.$ip." ".$text['fail'];
            $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg, $ip);
        }
    }


    //修改密碼
    public function edit_password(){

        header('Content-Type: application/json; charset=utf-8');

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        $password        = trim((string)($_POST['new_password'] ?? ''));
        $confirmPassword = trim((string)($_POST['confirm_password'] ?? ($_POST['comfirm_password'] ?? '')));
        $deviceId        = trim((string)($_POST['device_id'] ?? ''));

        if ($deviceId === '') {
            $controllerInfo = $this->SettingModel->GetControllerInfo();
            $deviceId = (string)($controllerInfo['device_id'] ?? '1');
        }

        $msgInput = $text['input_error'] ?? 'Input error';

        // 密碼不可空白，只允許數字，支援 4~10 碼
        if ($password === '' || $confirmPassword === '') {
            echo json_encode([
                'result'   => false,
                'res_type' => 'Error',
                'res_msg'  => $msgInput . ': password is required.'
            ]);
            exit;
        }

        if (!preg_match('/^\d{4,10}$/', $password)) {
            echo json_encode([
                'result'   => false,
                'res_type' => 'Error',
                'res_msg'  => $msgInput . ': password must be 4-10 digits.'
            ]);
            exit;
        }

        if ($password !== $confirmPassword) {
            echo json_encode([
                'result'   => false,
                'res_type' => 'Error',
                'res_msg'  => $msgInput . ': password confirmation does not match.'
            ]);
            exit;
        }

        $conset = [
            'device_id'    => $deviceId,
            'new_password' => $password,
        ];

        $result = $this->SettingModel->Edit_Login_Password($conset);

        echo json_encode([
            'result'   => (bool)$result,
            'res_type' => $result ? 'Success' : 'Error',
            'res_msg'  => $result
                ? ($text['success'] ?? 'Success')
                : ($text['fail'] ?? 'Fail')
        ]);
        exit;
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

        // 通訊協議不可在一般 Controller Setting 儲存時被默認寫回 0。
        // 前端有送 modbus_type，但舊版後端沒有接，Model 又以 ?? 0 寫 DB，
        // 因此 OP(2) 可能被意外覆蓋成 MODBUS TCP(0)。
        $currentControllerInfo = (array)($this->SettingModel->GetControllerInfo() ?? []);

        /*
         * NTCS 模式（icontroller = 0）：
         * Device ID 與通訊協議為唯讀。
         * 即使前端被繞過直接送 POST，也強制保留目前 Controller 值。
         */
        if (!idas_is_icontroller()) {
            $currentDeviceId = $currentControllerInfo['device_id'] ?? null;
            if ($currentDeviceId !== null && $currentDeviceId !== '') {
                $control_id_old = $currentDeviceId;
                $control_id_new = $currentDeviceId;
                $con_setting['control_id'] = $currentDeviceId;
                $con_setting['control_id_new'] = $currentDeviceId;
            }
        }

        $currentModbusType = isset($currentControllerInfo['modbus_type'])
            && is_numeric($currentControllerInfo['modbus_type'])
            && in_array((int)$currentControllerInfo['modbus_type'], [0, 1, 2], true)
                ? (int)$currentControllerInfo['modbus_type']
                : 0;

        $modbusTypeRaw = $get('modbus_type', null);

        if (!idas_is_icontroller()) {
            // NTCS 模式禁止修改通訊協議，永遠保留目前值。
            $modbusTypeRaw = (string)$currentModbusType;
        }

        if ($modbusTypeRaw === null) {
            // Missing field means preserve current setting, never force MODBUS TCP.
            $con_setting['modbus_type'] = $currentModbusType;
        } elseif (!is_numeric($modbusTypeRaw) || !in_array((int)$modbusTypeRaw, [0, 1, 2], true)) {
            $input_check = false;
            $con_setting['modbus_type'] = $currentModbusType;
        } else {
            $con_setting['modbus_type'] = (int)$modbusTypeRaw;
        }
        $modbusTypeChanged = ($con_setting['modbus_type'] !== $currentModbusType);

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
        foreach (['counting_method','circular_archive','blackout_recovery','buzzer_mode','lang_val','unit_val','modbus_type'] as $nf) {
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

            // Keep the legacy res_type/res_msg fields, but also expose structured
            // metadata for settings.js. Protocol changes require a manual controller
            // reboot; do not pretend an automatic reboot was scheduled.
            echo json_encode([
                'res_type' => $res_type,
                'res_msg' => $res_msg,
                'success' => true,
                'modbus_type' => (int)$con_setting['modbus_type'],
                'modbus_type_changed' => $modbusTypeChanged,
                'restart_required' => $modbusTypeChanged,
                'restart_scheduled' => false,
                'manual_reboot_required' => $modbusTypeChanged,
                'restart_delay_seconds' => 0,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $res_type = $text['fail'] ?? 'Fail';
        $res_msg  = $text['fail'] ?? 'Fail';
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
        $device_id = $this->getActiveModbusUnitId();
        $unitId = $device_id;

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
        * MODBUS TCP / OP 協議都走統一 protocol layer。
        * ============================= */
        try {

            // R480=1 + R481~R490 filename
            $data = array_merge([1], $name_int16);
            $this->writeControllerRegisters(480, $data, 'FIRMWARE_UPDATE_FILENAME');

            $this->logMessage(
                "write R480~R490 OK, filename={$filenameWithoutExtension}, unitId={$unitId}"
            );

            $this->logMessage('firmware update end');

            echo json_encode(["error" => ""]);
            exit();

        } catch (Exception $e) {
            $this->logMessage('protocol write error: ' . $e->getMessage());
            $this->logMessage('firmware update end');
            echo json_encode(["error" => "communication error"]);
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
        $device_id = $this->getActiveModbusUnitId();
        $unitId = $device_id;
        $idas_result = $this->idas_check( $unitId);

        if ($idas_result['result'] != 1) {
            echo json_encode([
                'result' => false,
                'res_type' => 'Error',
                'res_msg' => 'Tool not disabled'
            ]);
            return;
        }

        // 執行刪除年份通知：MODBUS TCP / OP 協議都走統一 protocol layer。
        $year = array($temp_del_year);

        try {
            $this->writeControllerRegisters(517, $year, 'DELETE_HISTORY_YEAR');

            echo json_encode([
                'result' => true,
                'res_type' => 'Success',
                'res_msg' => $text['delete_text'].$text['success'] 
            ]);
        } catch (Exception $e) {
            $this->logMessage('delete_files protocol write failed: ' . $e->getMessage());
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


    /**
     * DB Sync 操作紀錄
     * 跟 JOB create job 一樣寫入 operation_audit_log
     */
    private function writeDbSyncAudit($action, $status, $message, $extra = []){

        try {
            if (!isset($this->AuditModel)) {
                $this->AuditModel = $this->model('OperationAudit');
            }

            $operator = $_COOKIE['username'] ?? ($_SESSION['username'] ?? '');

            $payload = [
                'module'       => 'DB_SYNC',
                'action'       => $action,
                'status'       => $status,
                'device_id'    => $this->getActiveModbusUnitId(),

                // DB Sync 沒有 job / seq / step
                'job_id'       => null,
                'seq_id'       => null,
                'step_id'      => null,

                'user_id'      => $operator,
                'operator'     => $operator,
                'client_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
                'title'        => 'DB Sync',
                'message'      => $message,

                'before_json'  => null,
                'after_json'   => $extra,
                'request_json' => $_POST,
            ];

            if (method_exists($this->AuditModel, 'write')) {
                $this->AuditModel->write($payload);
            } else {
                error_log('[OperationAudit][DB_SYNC] OperationAudit::write() not found');
            }

        } catch (Throwable $e) {
            // 寫 log 失敗不能影響同步功能
            error_log('[OperationAudit][DB_SYNC] write failed: ' . $e->getMessage());
        }
    }

    /**
     * DB Sync 統一回傳
     * 回傳前先寫 operation_audit_log
     */
    private function syncDbAuditResponse($resType, $resMsg, $action, $extra = []){

        $status = (strtolower((string)$resType) === 'success') ? 'SUCCESS' : 'FAIL';

        $this->writeDbSyncAudit($action, $status, $resMsg, $extra);

        return $this->MiscellaneousModel->generateErrorResponse($resType, $resMsg);
    }

    /**
     * 在 D2C 覆蓋 ntcs_device.db 前，保留控制器目前的 Wi-Fi 設定。
     *
     * 流程：
     * 1. 從 Controller DB 的 ntcs_device_test.wifi 讀取現值。
     * 2. 依資料列順序寫入 iDAS DB 的 ntcs_device_test.wifi。
     * 3. 完成後才允許 iDAS DB 覆蓋 Controller DB。
     *
     * @return int 已同步的資料列數
     */
    private function preserveControllerWifiToIdasDeviceDb(
        string $controllerDb,
        string $idasDb
    ): int {

        if (!is_file($controllerDb) || !is_readable($controllerDb)) {
            throw new Exception("Controller device DB not found or unreadable: {$controllerDb}");
        }

        if (!is_file($idasDb) || !is_readable($idasDb) || !is_writable($idasDb)) {
            throw new Exception("iDAS device DB not found, unreadable or unwritable: {$idasDb}");
        }

        // 覆寫前先確認兩份 SQLite DB 都是健康的。
        $this->assertSqliteHealthy($controllerDb);
        $this->assertSqliteHealthy($idasDb);

        $controllerPdo = new PDO('sqlite:' . $controllerDb);
        $idasPdo       = new PDO('sqlite:' . $idasDb);

        foreach ([$controllerPdo, $idasPdo] as $pdo) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            // DB 正在被其它程序短暫使用時，最多等待 5 秒，避免立即出現 database is locked。
            $pdo->exec('PRAGMA busy_timeout = 5000');
        }

        // 確認來源與目的 DB 都存在 ntcs_device_test.wifi 欄位。
        foreach ([
            'Controller' => $controllerPdo,
            'iDAS'       => $idasPdo,
        ] as $label => $pdo) {
            $columns = $pdo->query("PRAGMA table_info(ntcs_device_test)")->fetchAll();
            if (empty($columns)) {
                throw new Exception("{$label} DB table not found: ntcs_device_test");
            }

            $columnNames = array_map(
                static fn(array $column): string => (string)($column['name'] ?? ''),
                $columns
            );

            if (!in_array('wifi', $columnNames, true)) {
                throw new Exception("{$label} DB column not found: ntcs_device_test.wifi");
            }
        }

        // 使用 rowid 依資料列順序配對；一般 ntcs_device_test 為單筆資料，
        // 若未來變成多筆，也不會把第一筆 wifi 任意套用到全部資料。
        $controllerRows = $controllerPdo
            ->query('SELECT rowid AS sync_rowid, wifi FROM ntcs_device_test ORDER BY rowid')
            ->fetchAll();

        $idasRows = $idasPdo
            ->query('SELECT rowid AS sync_rowid FROM ntcs_device_test ORDER BY rowid')
            ->fetchAll();

        if (empty($controllerRows)) {
            throw new Exception('Controller DB has no ntcs_device_test data');
        }

        if (count($controllerRows) !== count($idasRows)) {
            throw new Exception(sprintf(
                'ntcs_device_test row count mismatch (Controller=%d, iDAS=%d)',
                count($controllerRows),
                count($idasRows)
            ));
        }

        try {
            $idasPdo->beginTransaction();

            $update = $idasPdo->prepare(
                'UPDATE ntcs_device_test SET wifi = :wifi WHERE rowid = :rowid'
            );

            foreach ($controllerRows as $index => $controllerRow) {
                $targetRowId = (int)$idasRows[$index]['sync_rowid'];
                $wifiValue   = $controllerRow['wifi'] ?? null;

                if ($wifiValue === null) {
                    $update->bindValue(':wifi', null, PDO::PARAM_NULL);
                } elseif (is_int($wifiValue)) {
                    $update->bindValue(':wifi', $wifiValue, PDO::PARAM_INT);
                } else {
                    $update->bindValue(':wifi', (string)$wifiValue, PDO::PARAM_STR);
                }

                $update->bindValue(':rowid', $targetRowId, PDO::PARAM_INT);
                $update->execute();
            }

            $idasPdo->commit();

            // 回讀確認每一列的 wifi 確實已寫入 iDAS DB。
            $verifiedRows = $idasPdo
                ->query('SELECT rowid AS sync_rowid, wifi FROM ntcs_device_test ORDER BY rowid')
                ->fetchAll();

            foreach ($controllerRows as $index => $controllerRow) {
                $sourceWifi = $controllerRow['wifi'] ?? null;
                $targetWifi = $verifiedRows[$index]['wifi'] ?? null;

                $sameValue = ($sourceWifi === null && $targetWifi === null)
                    || ($sourceWifi !== null && $targetWifi !== null && (string)$sourceWifi === (string)$targetWifi);

                if (!$sameValue) {
                    throw new Exception('Controller wifi verification failed at row ' . ($index + 1));
                }
            }

            // 若 DB 使用 WAL，先把 wifi 變更 checkpoint 回主 DB，之後才能安全複製單一 .db 檔。
            $journalMode = strtolower((string)$idasPdo->query('PRAGMA journal_mode')->fetchColumn());
            if ($journalMode === 'wal') {
                $checkpoint = $idasPdo->query('PRAGMA wal_checkpoint(TRUNCATE)')->fetch(PDO::FETCH_NUM);
                if (is_array($checkpoint) && (int)($checkpoint[0] ?? 0) !== 0) {
                    throw new Exception('SQLite WAL checkpoint is busy');
                }
            }
        } catch (Throwable $e) {
            if ($idasPdo->inTransaction()) {
                $idasPdo->rollBack();
            }
            throw new Exception('Failed to preserve controller wifi value: ' . $e->getMessage());
        }

        // 關閉連線後再做完整性檢查，避免複製時仍持有 DB handle。
        $controllerPdo = null;
        $idasPdo = null;
        clearstatcache(true, $idasDb);

        // 寫入完成後再次確認 iDAS DB 完整性。
        $this->assertSqliteHealthy($idasDb);

        return count($controllerRows);
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
        $device_id = $this->getActiveModbusUnitId();
        $unitId = $device_id;

        $auditExtra = [
            'direction' => 'D2C',
            'argument'  => $argument,
            'device_id' => $device_id,
            'unit_id'   => $unitId,
            'files'     => [
                'lin' => [
                    'src'     => $src1,
                    'final'   => $finalPath1,
                    'renamed' => $renamedPath1,
                ],
                'barcode' => [
                    'src'     => $src2,
                    'final'   => $finalPath2,
                    'renamed' => $renamedPath2,
                ],
                'device' => [
                    'src' => $src3,
                    'dst' => $dst3,
                ],
            ],
        ];

        // 只處理 Linux + D2C，其它情況直接回錯誤
        if (PHP_OS_FAMILY !== 'Linux' || $argument !== 'D2C') {
            return $this->syncDbAuditResponse('Error', 'Invalid sync argument or unsupported OS', 'SYNC_D2C', $auditExtra);
        }

        // ✅ Device DB 同步前，先保留 Controller 目前的 wifi 設定。
        // 流程：dst3.wifi → src3.wifi → src3 整份覆蓋回 dst3。
        if (!file_exists($src3) || !file_exists($dst3)) {
            $missingDeviceFiles = [];
            if (!file_exists($src3)) $missingDeviceFiles[] = $src3;
            if (!file_exists($dst3)) $missingDeviceFiles[] = $dst3;

            $auditExtra['failed_stage'] = 'missing_device_db';
            $auditExtra['missing_files'] = $missingDeviceFiles;

            return $this->syncDbAuditResponse(
                'Error',
                'Device DB file(s) missing: ' . implode(', ', $missingDeviceFiles),
                'SYNC_D2C',
                $auditExtra
            );
        }

        try {
            // 1. Controller DB 的 wifi value 複寫到 iDAS DB。
            $wifiRows = $this->preserveControllerWifiToIdasDeviceDb($dst3, $src3);
            $auditExtra['device_wifi_preserved'] = true;
            $auditExtra['device_wifi_row_count'] = $wifiRows;
            $this->logMessage("Controller wifi value preserved to iDAS device DB, rows={$wifiRows}");

            // 2. 完成 wifi 保留後，再將更新後的 iDAS device DB 覆蓋回 Controller。
            // 使用同目錄 temp + atomic rename，避免直接 copy 中途失敗留下半份 SQLite DB。
            $this->replaceSqliteDbFile($src3, $dst3, false);

            @chmod($dst3, 0777);
            $this->logMessage("Updated iDAS device DB copied to Controller: {$src3} -> {$dst3}");

            // 3. 延續原本的後續 DB 同步流程。
            $this->get_db_sync($unitId);

        } catch (Throwable $e) {
            $auditExtra['failed_stage'] = 'preserve_wifi_and_copy_device_db';
            $auditExtra['exception'] = $e->getMessage();
            $this->logMessage('Device DB sync failed: ' . $e->getMessage());

            return $this->syncDbAuditResponse(
                'Error',
                'Device DB synchronization failed',
                'SYNC_D2C',
                $auditExtra
            );
        }

        //  檢查原始檔案是否存在
        if (!file_exists($src1) || !file_exists($src2)) {
            $missingFiles = [];
            if (!file_exists($src1)) $missingFiles[] = 'KLS_NTCS_IDAS.Lin';
            if (!file_exists($src2)) $missingFiles[] = 'ntcs_barcode_IDAS.db';

            $auditExtra['failed_stage'] = 'missing_source_files';
            $auditExtra['missing_files'] = $missingFiles;
            return $this->syncDbAuditResponse(
                'Error',
                'Source file(s) missing: ' . implode(', ', $missingFiles),
                'SYNC_D2C',
                $auditExtra
            );
        }

        // Barcode DB 在送往 Controller 前先強制收斂成 1:1。
        try {
            $auditExtra['barcode_one_to_one'] = $this->ensureBarcodeOneToOneDb($src2);
        } catch (Throwable $e) {
            $auditExtra['failed_stage'] = 'barcode_one_to_one_before_d2c';
            $auditExtra['exception'] = $e->getMessage();
            return $this->syncDbAuditResponse('Error', 'Barcode DB validation failed', 'SYNC_D2C', $auditExtra);
        }

        // 通知控制器時改走 protocol layer。
        // MODBUS TCP 使用 ModbusMaster；OP 協議會自動轉成 IDAS_WRITE_xxx_xxx。
        $modbus = null;

        try {
            // ----------- Sync LIN File（簡化：直接 src → final → rename）-----------
            if (!$this->safeCopy($src1, $finalPath1)) {
                $auditExtra['failed_stage'] = 'copy_lin_to_ftp';
                return $this->syncDbAuditResponse('Error', "Failed to copy $src1 to $finalPath1", 'SYNC_D2C', $auditExtra);
            }
            @chmod($finalPath1, 0777);
            $this->logMessage("$src1 copied to $finalPath1");

            // 通知控制器有新 LIN
            $this->notifyModbus($modbus, [1, 12593], "LIN");

            if (!$this->safeCopy($finalPath1, $renamedPath1)) {
                $auditExtra['failed_stage'] = 'rename_lin_file';
                return $this->syncDbAuditResponse('Error', "Failed to rename LIN file", 'SYNC_D2C', $auditExtra);
            }
            @unlink($finalPath1);
            $this->logMessage("$finalPath1 renamed to $renamedPath1");

            // 控制器收到 LIN 通知後需要時間處理檔案；太快接續 DB 通知容易 Protocol read failed。
            usleep(1_000_000);

            // ----------- Sync DB File (barcode)（一樣簡化）-----------
            if (!$this->safeCopy($src2, $finalPath2)) {
                $auditExtra['failed_stage'] = 'copy_barcode_to_ftp';
                return $this->syncDbAuditResponse('Error', "Failed to copy $src2 to $finalPath2", 'SYNC_D2C', $auditExtra);
            }
            @chmod($finalPath2, 0777);
            $this->logMessage("$src2 copied to $finalPath2");

            // 通知控制器有新 DB
            $this->notifyModbus($modbus, [1, 12593], "DB");

            if (!$this->safeCopy($finalPath2, $renamedPath2)) {
                $auditExtra['failed_stage'] = 'rename_barcode_file';
                return $this->syncDbAuditResponse('Error', "Failed to rename DB file", 'SYNC_D2C', $auditExtra);
            }
            @unlink($finalPath2);
            $this->logMessage("$finalPath2 renamed to $renamedPath2");

            // ✅ 最後回傳成功訊息（純 JSON）
            return $this->syncDbAuditResponse('Success', 'SYNC ' . ($text['success'] ?? 'success'), 'SYNC_D2C', $auditExtra);

        } catch (Exception $e) {
            $this->logMessage('Modbus write fail: ' . $e->getMessage());
            $auditExtra['failed_stage'] = 'modbus_communication';
            $auditExtra['exception'] = $e->getMessage();
            return $this->syncDbAuditResponse('Error', 'Modbus communication failed', 'SYNC_D2C', $auditExtra);
        }
    }

    public function Sync_check_db_load() {
        header('Content-Type: application/json; charset=utf-8');

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $argument = $_POST['argument'] ?? '';

        // 定義來源與目的地檔案清單（Controller → iDAS）
        $fileList = [
            '/home/kls/NTCS7/KLS_NTCS.Lin'      => '/var/www/html/database/KLS_NTCS_IDAS.Lin',
            '/home/kls/NTCS7/ntcs_barcode.db'   => '/var/www/html/database/ntcs_barcode_IDAS.db',
            '/home/kls/NTCS7/ntcs_device.db'    => '/var/www/html/database/ntcs_device_IDAS.db',
            '/home/kls/NTCS7/ntcs_data.db'      => '/var/www/html/database/ntcs_data.db',
        ];

        $auditExtra = [
            'direction' => 'C2D',
            'argument'  => $argument,
            'files'     => $fileList,
            'copied'    => [],
        ];

        if (empty($argument) || PHP_OS_FAMILY !== 'Linux') {
            return $this->syncDbAuditResponse('Error', 'Invalid sync argument or unsupported OS', 'SYNC_C2D', $auditExtra);
        }

        if ($argument === 'C2D') {
            $copiedCount = 0;
            foreach ($fileList as $src => $dst) {
                if (file_exists($src)) {
                    try {
                        // Barcode DB 使用 SQLite atomic replace，避免直接 copy 覆寫目前已開啟的 DB 檔。
                        if (basename($src) === 'ntcs_barcode.db') {
                            $this->replaceSqliteDbFile($src, $dst, false);
                            $copiedOk = true;
                        } else {
                            $copiedOk = copy($src, $dst);
                        }
                    } catch (Throwable $copyException) {
                        $copiedOk = false;
                        $auditExtra['copy_exception'] = $copyException->getMessage();
                    }

                    if ($copiedOk) {
                        $copiedCount++;
                        $auditExtra['copied'][] = [
                            'src' => $src,
                            'dst' => $dst,
                        ];

                        // 如果是特定檔案可額外執行後處理
                        if (basename($src) === 'KLS_NTCS.Lin') {
                            //$this->stepModel->get_success_data_by_step();
                        }
                    } else {
                        $auditExtra['failed_stage'] = 'copy_file';
                        $auditExtra['failed_src'] = $src;
                        $auditExtra['failed_dst'] = $dst;
                        return $this->syncDbAuditResponse('Error', "Failed to copy: $src -> $dst", 'SYNC_C2D', $auditExtra);
                    }
                } else {
                    $auditExtra['failed_stage'] = 'source_file_not_found';
                    $auditExtra['missing_src'] = $src;
                    return $this->syncDbAuditResponse('Error', "Source file not found: $src", 'SYNC_C2D', $auditExtra);
                }
            }

            $auditExtra['copied_count'] = $copiedCount;

            try {
                $barcodeDst = '/var/www/html/database/ntcs_barcode_IDAS.db';
                $auditExtra['barcode_one_to_one'] = $this->ensureBarcodeOneToOneDb($barcodeDst);
            } catch (Throwable $e) {
                $auditExtra['failed_stage'] = 'barcode_one_to_one_after_c2d';
                $auditExtra['exception'] = $e->getMessage();
                return $this->syncDbAuditResponse('Error', 'Barcode DB validation failed after sync', 'SYNC_C2D', $auditExtra);
            }

            return $this->syncDbAuditResponse(
                'Success',
                "SYNC" . ($text['success'] ?? 'success'),
                'SYNC_C2D',
                $auditExtra
            );
        }

        // 預留其他參數（例如 D2C）
        return $this->syncDbAuditResponse('Error', 'Invalid sync argument', 'SYNC_C2D', $auditExtra);
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
     * 發送控制器同步訊號。
     *
     * 舊版只支援 ModbusMaster；新版統一走 protocol layer：
     * - TCP / RTU：寫 Modbus register 506 起算
     * - OP：寫 IDAS_WRITE_506_xxx / IDAS_WRITE_507_xxx ...
     */
    private function notifyModbus($modbus = null, $data = [], $tag = ""){
        $data = is_array($data) ? array_values($data) : [];
        $payload = array_merge($data, array_fill(0, max(0, 16 - count($data)), 0));
        $payload = array_slice($payload, 0, 16);

        $this->writeControllerRegisters(506, $payload, $tag);
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
        * 回傳結果 - Huí chuán jiéguǒ
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
            $barcodeError = method_exists($this->SettingModel, 'getLastBarcodeError')
                ? $this->SettingModel->getLastBarcodeError()
                : '';

            if ($barcodeError === 'job_conflict') {
                $msg = sprintf(
                    $text['barcode_job_conflict'] ?? 'Job %d already has a barcode. Please edit or delete the existing barcode first.',
                    (int)$barcode['barcode_job']
                );
            } elseif ($barcodeError === 'barcode_conflict') {
                $msg = sprintf(
                    $text['barcode_value_conflict'] ?? 'Barcode "%s" is already assigned to another Job.',
                    $barcode['barcode_name']
                );
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
            }

            echo json_encode([
                'res_type' => 'error',
                'res_msg'  => $msg,
                'error_code' => $barcodeError
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
    /**
     * iDAS pack version pre-check.
     *
     * This endpoint only reads info.json from the uploaded .pack and does not install it.
     * It is used by the UI to automatically show the downgrade confirmation only when needed.
     */
    public function check_idas_pack_version(){

        header('Content-Type: application/json; charset=utf-8');

        try {
            if (empty($_FILES['file'])) {
                $msg = $this->t('ERR_NO_FILE');
                echo json_encode([
                    'success' => false,
                    'res_type' => 'Error',
                    'res_msg'  => $msg,
                    'message'  => $msg,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($_FILES['file']['error'] !== 0) {
                $msg = $this->t('ERR_UPLOAD_ERROR', ['code' => (string)$_FILES['file']['error']]);
                echo json_encode([
                    'success' => false,
                    'res_type' => 'Error',
                    'res_msg'  => $msg,
                    'message'  => $msg,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $uploadedFilename = (string)($_FILES['file']['name'] ?? '');
            if (strtolower(pathinfo($uploadedFilename, PATHINFO_EXTENSION)) !== 'pack') {
                $msg = $this->t('ERR_EXT', ['filename' => $uploadedFilename]);
                echo json_encode([
                    'success' => false,
                    'res_type' => 'Error',
                    'res_msg'  => $msg,
                    'message'  => $msg,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $currentRaw = (string)$this->AdminModel->Get_Das_Config('idas_version');
            $packInfo   = $this->readIdasPackInfoFromZip((string)$_FILES['file']['tmp_name']);

            $currentDisplay = $this->normalizeIdasVersionDisplay($currentRaw);
            $targetDisplay  = $this->normalizeIdasVersionDisplay($packInfo['idas_version']);
            $currentBase    = $this->normalizeIdasVersionBase($currentRaw);
            $targetBase     = $this->normalizeIdasVersionBase($packInfo['idas_version']);
            $currentProfile = $this->getIdasVersionProfile($currentRaw);
            $targetProfile  = $this->getIdasVersionProfile($packInfo['idas_version']);
            $isDowngrade    = version_compare($targetBase, $currentBase, '<');
            $isSameVersion  = version_compare($targetBase, $currentBase, '==');
            $isUpgrade      = version_compare($targetBase, $currentBase, '>');
            $isProfileSwitch = ($currentProfile !== $targetProfile);
            $requiresDbRebuild = $this->shouldRebuildDbForUpdate($currentProfile, $targetProfile, $isDowngrade);
            $requiresConfirm   = $this->shouldRequireUpdateConfirm($currentProfile, $targetProfile, $isDowngrade);

            if ($currentProfile === 'SA349' && $targetProfile !== 'SA349') {
                $statusKey = 'STATUS_SA349_TO_STANDARD_REBUILD';
            } elseif ($isDowngrade) {
                $statusKey = 'STATUS_DOWNGRADE';
            } elseif ($isProfileSwitch) {
                $statusKey = 'STATUS_PROFILE_SWITCH';
            } elseif ($isSameVersion) {
                $statusKey = 'STATUS_SAME_VERSION';
            } else {
                $statusKey = 'STATUS_UPGRADE';
            }

            echo json_encode([
                'success'             => true,
                'res_type'            => 'OK',
                'message'             => '',
                'current_version'     => $currentDisplay,
                'pack_version'        => $targetDisplay,
                'pack_version_raw'    => (string)$packInfo['idas_version'],
                'current_profile'     => $currentProfile,
                'pack_profile'        => $targetProfile,
                'is_downgrade'        => $isDowngrade,
                'is_same_version'     => $isSameVersion,
                'is_upgrade'          => $isUpgrade,
                'is_profile_switch'   => $isProfileSwitch,
                'is_special_switch'   => $isProfileSwitch,
                'requires_confirm'    => $requiresConfirm,
                'requires_db_rebuild' => $requiresDbRebuild,
                'status_key'          => $statusKey,
                'status_text'         => $this->t($statusKey),
                'db_action_text'      => $this->t($requiresDbRebuild ? 'DB_ACTION_REBUILD' : 'DB_ACTION_KEEP'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;

        } catch (Throwable $e) {
            $msg = $e->getMessage();
            echo json_encode([
                'success' => false,
                'res_type' => 'Error',
                'res_msg'  => $msg,
                'message'  => $msg,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
    }

    #IDAS上傳 20250624 修改
    public function iDas_Update($debug = false) {

        // NTCS iDAS 10 update hotfix: keep AJAX response as clean JSON, even if file operations emit warnings.
        @set_time_limit(300);
        @ini_set('max_execution_time', '300');
        @ini_set('default_socket_timeout', '300');
        if (ob_get_level() === 0) {
            ob_start();
        } else {
            ob_start();
        }

        // 1. 紀錄上傳限制
        $maxUpload = ini_get('upload_max_filesize');
        $postMax   = ini_get('post_max_size');
        error_log("[iDAS UPDATE] upload_max_filesize: $maxUpload");
        error_log("[iDAS UPDATE] post_max_size: $postMax");

        // 2. 載入語系檔（保留你原本機制）
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        // 3. 當前版本
        $iDas_Version = (string)$this->AdminModel->Get_Das_Config('idas_version');

        // 4. 設定路徑
        $file_location = (PHP_OS_FAMILY === 'Linux') ? '/var/www/html/' : $_SERVER['DOCUMENT_ROOT'] . '/';
        $extract_path  = $file_location . 'extracted/';
        $main_folder   = '';
        $updateLockFp  = null;
        $dbPreflightFiles = [];
        $deployedNewIdas = false;

        // 上傳大小上限（訊息顯示用）
        $MAX_SIZE     = 80 * 1024 * 1024;
        $limitText    = '80MB';

        try {
            // 5. 後端更新鎖：避免兩個瀏覽器 / 兩個 request 同時執行更新。
            $updateLockFp = $this->acquireIdasUpdateLock();

            // 6. 驗證上傳
            if (empty($_FILES['file'])) {
                $contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
                $clientFileName = trim((string)($_POST['client_file_name'] ?? ''));
                $clientFileSize = trim((string)($_POST['client_file_size'] ?? ''));
                return $this->sendResponse('Error', $this->t('ERR_NO_FILE_DETAIL', [
                    'name' => $clientFileName !== '' ? $clientFileName : '-',
                    'size' => $clientFileSize !== '' ? $clientFileSize : '-',
                    'length' => (string)$contentLength,
                    'post_max' => (string)ini_get('post_max_size'),
                    'upload_max' => (string)ini_get('upload_max_filesize'),
                ]));
            }
            if ($_FILES['file']['error'] !== 0) {
                return $this->sendResponse('Error', $this->t('ERR_UPLOAD_ERROR', ['code' => (string)$_FILES['file']['error']]));
            }

            // 6. 大小限制
            if ($_FILES['file']['size'] > $MAX_SIZE) {
                return $this->sendResponse('Error', $this->t('ERR_SIZE_LIMIT', ['limit' => $limitText]));
            }

            // 7. 副檔名檢查
            $uploaded_filename = (string)$_FILES['file']['name'];
            if (strtolower(pathinfo($uploaded_filename, PATHINFO_EXTENSION)) !== 'pack') {
                return $this->sendResponse('Error', $this->t('ERR_EXT', ['filename' => $uploaded_filename]));
            }

            // 8. 解壓縮
            $zip = new ZipArchive();
            if ($zip->open($_FILES['file']['tmp_name']) !== TRUE) {
                return $this->sendResponse('Error', $this->t('ERR_OPEN_PACK'));
            }

            // 清掉前一次殘留的 extracted，避免 scandir() 抓到舊資料夾導致更新錯包。
            if (is_dir($extract_path)) {
                $this->deleteDirectory($extract_path);
            }
            if (!is_dir($extract_path) && !@mkdir($extract_path, 0777, true)) {
                $zip->close();
                return $this->sendResponse('Error', 'Cannot create extract folder: ' . $extract_path);
            }
            if (!$zip->extractTo($extract_path)) {
                $zip->close();
                return $this->sendResponse('Error', $this->t('ERR_EXTRACT'));
            }
            $zip->close();

            // 9. 找本次解壓縮的主資料夾：以 info.json 為準，不使用 reset(scandir())，避免吃到殘留舊資料。
            $main_folder = $this->findExtractedIdasPackRoot($extract_path);
            if ($main_folder === '') {
                return $this->sendResponse('Error', $this->t('ERR_NO_FOLDER'));
            }

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

            // 11. 比對版本：正式上傳仍需後端再次驗證，避免繞過前端預檢。
            $targetVersionRaw     = (string)$verify_data['idas_version'];
            $currentVersionBase   = $this->normalizeIdasVersionBase($iDas_Version);
            $targetVersionBase    = $this->normalizeIdasVersionBase($targetVersionRaw);
            $currentVersionLabel  = $this->normalizeIdasVersionDisplay($iDas_Version);
            $targetVersionLabel   = $this->normalizeIdasVersionDisplay($targetVersionRaw);
            $currentProfile       = $this->getIdasVersionProfile($iDas_Version);
            $targetProfile        = $this->getIdasVersionProfile($targetVersionRaw);
            $isDowngrade          = version_compare($targetVersionBase, $currentVersionBase, '<');
            $isProfileSwitch      = ($currentProfile !== $targetProfile);
            $requiresDbRebuild    = $this->shouldRebuildDbForUpdate($currentProfile, $targetProfile, $isDowngrade);
            $requiresConfirm      = $this->shouldRequireUpdateConfirm($currentProfile, $targetProfile, $isDowngrade);
            $isSa349ToNonSa349    = ($currentProfile === 'SA349' && $targetProfile !== 'SA349');
            $allowUpdateConfirm   = $this->isTruthy($_POST['allow_downgrade'] ?? '0');

            if ($requiresConfirm && !$allowUpdateConfirm) {
                return $this->sendResponse('Error', $this->t('ERR_UPDATE_CONFIRM_REQUIRED', [
                    'current' => $currentVersionLabel,
                    'update'  => $targetVersionLabel,
                ]));
            }

            if ($requiresConfirm) {
                error_log("[iDAS UPDATE] update confirm allowed: current={$currentVersionLabel}({$currentProfile}), target={$targetVersionLabel}({$targetProfile}), downgrade=" . ($isDowngrade ? '1' : '0') . ", profile_switch=" . ($isProfileSwitch ? '1' : '0') . ", db_rebuild=" . ($requiresDbRebuild ? '1' : '0') . ", sa349_to_non_sa349=" . ($isSa349ToNonSa349 ? '1' : '0'));
            }

            // 12. 若此次需要重建 DB，必須在切換程式資料夾前先完成 preflight。
            //     這裡只檢查 / 測試複製 temp，不會覆蓋正式 DB。
            if ($requiresDbRebuild) {
                $dbPreflightFiles = $this->preflightIdasDbRebuildFromNtcs7();
                error_log('[iDAS UPDATE] database rebuild preflight OK: ' . json_encode($dbPreflightFiles, JSON_UNESCAPED_SLASHES));
            }

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
                if (!@rename($target_directory, $old_directory)) {
                    $err = error_get_last();
                    throw new Exception('Rename current idas to idas_old failed: ' . ($err['message'] ?? 'unknown'));
                }
            }

            // 5️⃣ 新版 → 正式上線（瞬間完成）
            if (!@rename($staging_directory, $target_directory)) {
                $err = error_get_last();
                // 嘗試復原舊版，避免半更新。
                if (is_dir($old_directory) && !is_dir($target_directory)) {
                    @rename($old_directory, $target_directory);
                }
                throw new Exception('Rename idas_new to idas failed: ' . ($err['message'] ?? 'unknown'));
            }

            $deployedNewIdas = true;

            // 6️⃣ old 先保留到 DB rebuild 與版本寫入成功後才刪除，避免 DB 失敗時無法 rollback。

            sleep(1);
            exec("sync"); //強制將ram寫回硬碟，避免控制器馬上關機時會遺失資料
            sleep(1);

            // 12. 清除舊 ntcs_idas 資料夾（若存在）
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

            // 13. 需要重建 DB 時，從 /home/kls/NTCS7 複製乾淨 DB 回 iDAS。
            //     preflight 已在切換程式前完成；這裡才正式 atomic replace。
            //     規則：STANDARD -> STANDARD 降版本重建；SA349 -> 非 SA349 一律重建，且移除 Lin user.admin 與 law=3 使用者。
            if ($requiresDbRebuild) {
                $copiedFiles = $this->rebuildIdasDbFromNtcs7ForDowngrade($isSa349ToNonSa349);
                error_log('[iDAS UPDATE] database rebuild finished: ' . json_encode($copiedFiles, JSON_UNESCAPED_SLASHES));
                exec("sync");
            }

            // 14. 寫入 config 表：0.72.0_SA349 會正規化為 0.72_SA349 顯示。
            $this->AdminModel->Set_Das_Config('idas_version', $targetVersionLabel);

            // 15. 全部成功後才刪 old，避免 DB rebuild 失敗時無法 rollback。
            if (is_dir($old_directory)) {
                $this->deleteDirectory($old_directory);
            }

            // 16. 登出使用者
            $this->setting_logout();

            // Debug：保留 extracted
            if ($debug) {
                return $this->sendResponse('Success', $this->t('SUC_DEBUG'), $this->updateForceLogoutPayload());
            }

            return $this->sendResponse('Success', $this->t('SUC_OK'), $this->updateForceLogoutPayload());

        } catch (Throwable $e) {
            error_log('[iDAS UPDATE] failed: ' . $e->getMessage());

            // 若程式已切換但後續 DB rebuild / 版本寫入失敗，優先還原 idas_old，避免半更新。
            if (!empty($deployedNewIdas) && !empty($old_directory) && is_dir($old_directory) && !empty($target_directory)) {
                try {
                    if (is_dir($target_directory)) {
                        $this->deleteDirectory($target_directory);
                    }
                    if (@rename($old_directory, $target_directory)) {
                        error_log('[iDAS UPDATE] rollback idas_old -> idas success');
                    } else {
                        $rollbackErr = error_get_last();
                        error_log('[iDAS UPDATE] rollback idas_old -> idas failed: ' . ($rollbackErr['message'] ?? 'unknown'));
                    }
                } catch (Throwable $rollbackException) {
                    error_log('[iDAS UPDATE] rollback exception: ' . $rollbackException->getMessage());
                }
            }

            return $this->sendResponse('Error', $e->getMessage());
        } finally {
            if (is_resource($updateLockFp)) {
                @flock($updateLockFp, LOCK_UN);
                @fclose($updateLockFp);
                error_log('[iDAS UPDATE] update lock released');
            }

            // 非 Debug 才刪暫存
            if (!$debug) {
                if (!empty($main_folder) && is_dir($main_folder)) $this->deleteDirectory($main_folder);
                if (is_dir($extract_path)) $this->deleteDirectory($extract_path);
            }
        }
    }

    private function findExtractedIdasPackRoot(string $extractPath): string{

        $extractPath = rtrim($extractPath, '/\\') . '/';

        if (is_file($extractPath . 'info.json')) {
            return rtrim($extractPath, '/');
        }

        $items = @scandir($extractPath);
        if (!is_array($items)) {
            return '';
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $dir = $extractPath . $item;
            if (is_dir($dir) && is_file($dir . '/info.json')) {
                return $dir;
            }
        }

        return '';
    }


    private function readIdasPackInfoFromZip(string $zipPath): array{

        if (!is_file($zipPath)) {
            throw new Exception($this->t('ERR_OPEN_PACK'));
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== TRUE) {
            throw new Exception($this->t('ERR_OPEN_PACK'));
        }

        $infoJson = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = (string)$zip->getNameIndex($i);
            if (preg_match('#(^|/)info\.json$#i', $name)) {
                $infoJson = $zip->getFromIndex($i);
                break;
            }
        }
        $zip->close();

        if ($infoJson === null || $infoJson === false || trim((string)$infoJson) === '') {
            throw new Exception($this->t('ERR_MISSING_INFO'));
        }

        $info = json_decode((string)$infoJson, true);
        if (!is_array($info) || empty($info['idas_version'])) {
            throw new Exception($this->t('ERR_BAD_INFO'));
        }

        return $info;
    }

    private function normalizeIdasVersionDisplay($version): string{

        $version = trim((string)$version);
        if ($version === '') {
            return '';
        }

        // 0.72.0_SA349 → 0.72_SA349
        if (preg_match('/^(\d+\.\d+)\.0(_[A-Za-z0-9][A-Za-z0-9._-]*)$/', $version, $matches)) {
            return $matches[1] . $matches[2];
        }

        return $version;
    }

    private function normalizeIdasVersionBase($version): string{

        $version = $this->normalizeIdasVersionDisplay($version);
        $version = preg_replace('/[_-].*$/', '', $version);

        return trim((string)$version) !== '' ? (string)$version : '0.0.0';
    }

    private function getIdasVersionProfile($version): string{

        $version = strtoupper($this->normalizeIdasVersionDisplay($version));

        if (preg_match('/(^|[_-])SA[_-]?349($|[_-])/', $version)) {
            return 'SA349';
        }

        return 'STANDARD';
    }

    private function shouldRebuildDbForUpdate(string $currentProfile, string $targetProfile, bool $isDowngrade): bool{

        // SA349 特規版切回非 SA349 版本一定重建 DB，避免 SA349 專用資料殘留。
        // 例如：0.75_SA349 -> 0.75、0.75_SA349 -> 0.76、0.76_SA349 -> 0.77。
        if ($currentProfile === 'SA349' && $targetProfile !== 'SA349') {
            return true;
        }

        // 只有標準版 -> 標準版降版本時才因降版重建 DB。
        // SA349 -> SA349 降版本仍維持 SA349 DB 架構，不重建 DB。
        if ($isDowngrade && $currentProfile === 'STANDARD' && $targetProfile === 'STANDARD') {
            return true;
        }

        return false;
    }

    private function shouldRequireUpdateConfirm(string $currentProfile, string $targetProfile, bool $isDowngrade): bool{

        // 降版本或 STANDARD / SA349 互切，都需要使用者明確確認。
        return $isDowngrade || ($currentProfile !== $targetProfile);
    }

    private function isTruthy($value): bool{
        if (is_bool($value)) {
            return $value;
        }
        $value = strtolower(trim((string)$value));
        return in_array($value, ['1', 'true', 'yes', 'on'], true);
    }

    private function acquireIdasUpdateLock()
    {
        $lockDir = (PHP_OS_FAMILY === 'Linux') ? '/var/www/html/database' : sys_get_temp_dir();
        if (!is_dir($lockDir) || !is_writable($lockDir)) {
            throw new Exception('Update lock directory is not writable: ' . $lockDir);
        }

        $lockPath = rtrim($lockDir, '/\\') . '/.idas_update.lock';
        $fp = @fopen($lockPath, 'c');
        if (!$fp) {
            throw new Exception('Unable to create update lock file: ' . $lockPath);
        }

        if (!@flock($fp, LOCK_EX | LOCK_NB)) {
            @fclose($fp);
            throw new Exception($this->t('ERR_UPDATE_LOCKED'));
        }

        @ftruncate($fp, 0);
        @fwrite($fp, date('Y-m-d H:i:s') . ' pid=' . getmypid() . ' ip=' . ($_SERVER['REMOTE_ADDR'] ?? '') . PHP_EOL);
        @fflush($fp);
        @chmod($lockPath, 0666);

        error_log('[iDAS UPDATE] update lock acquired: ' . $lockPath);
        return $fp;
    }

    private function getIdasDbRebuildFileMapFromNtcs7(): array
    {
        return [
            '/home/kls/NTCS7/KLS_NTCS.Lin'    => '/var/www/html/database/KLS_NTCS_IDAS.Lin',
            '/home/kls/NTCS7/ntcs_barcode.db' => '/var/www/html/database/ntcs_barcode_IDAS.db',
            '/home/kls/NTCS7/ntcs_device.db'  => '/var/www/html/database/ntcs_device_IDAS.db',
            '/home/kls/NTCS7/ntcs_data.db'    => '/var/www/html/database/ntcs_data.db',
        ];
    }

    /**
     * DB rebuild preflight.
     * 在程式資料夾切換前執行：確認來源 DB 可讀、可複製到目的資料夾 temp，且 .Lin / .db 都能通過 SQLite integrity_check。
     * 這個函式不會覆蓋正式 DB。
     */
    private function preflightIdasDbRebuildFromNtcs7(): array
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            throw new Exception('DB rebuild preflight is only supported on Linux.');
        }

        $fileMap = $this->getIdasDbRebuildFileMapFromNtcs7();
        $checked = [];

        error_log('[iDAS UPDATE] database rebuild preflight start');

        foreach ($fileMap as $src => $dst) {
            clearstatcache(true, $src);
            if (!is_file($src) || !is_readable($src)) {
                throw new Exception("DB rebuild source file missing or unreadable: {$src}");
            }

            $srcSize = @filesize($src);
            if ($srcSize === false || $srcSize < 1024) {
                throw new Exception("DB rebuild source file is empty or too small: {$src}");
            }

            // .Lin 實際上也是 SQLite，這裡直接檢查來源檔。
            $this->assertSqliteHealthy($src);

            $dstDir = dirname($dst);
            if (!is_dir($dstDir) || !is_writable($dstDir)) {
                throw new Exception("DB rebuild destination directory is not writable: {$dstDir}");
            }

            // 測試實際 copy 到目的資料夾 temp，避免等程式切換後才發現權限 / 空間 / copy 問題。
            $tmp = $dstDir . '/.' . basename($dst) . '.preflight_' . getmypid() . '_' . str_replace('.', '', uniqid('', true));
            if (!@copy($src, $tmp)) {
                $err = error_get_last();
                @unlink($tmp);
                throw new Exception("DB rebuild preflight copy failed: {$src} -> {$tmp}. " . ($err['message'] ?? 'unknown'));
            }
            @chmod($tmp, 0666);

            $tmpSize = @filesize($tmp);
            if ($tmpSize === false || $tmpSize !== $srcSize) {
                @unlink($tmp);
                throw new Exception("DB rebuild preflight size mismatch: {$src} -> {$tmp}");
            }

            $this->assertSqliteHealthy($tmp);
            @unlink($tmp);

            $checked[] = [
                'src' => $src,
                'dst' => $dst,
                'size' => $srcSize,
            ];

            error_log("[iDAS UPDATE] database rebuild preflight OK: {$src} -> {$dst}");
        }

        error_log('[iDAS UPDATE] database rebuild preflight end');

        return $checked;
    }

    private function removeSqliteDbFileSet(string $dbPath): void
    {
        foreach ([$dbPath, $dbPath . '-wal', $dbPath . '-shm', $dbPath . '-journal'] as $path) {
            if (file_exists($path) && !@unlink($path)) {
                $err = error_get_last();
                throw new Exception('Remove old DB file failed: ' . $path . '. ' . ($err['message'] ?? 'unknown'));
            }
        }
        clearstatcache(true, $dbPath);
    }

    /**
     * SA349 -> non-SA349 cleanup for KLS_NTCS_IDAS.Lin user table.
     *
     * Rule:
     * - Remove built-in admin account.
     * - Remove every user row with law = 3 (Operator accounts).
     *
     * This is used only during SA349 -> non-SA349 rebuild. It intentionally
     * does not touch guest / kls / law=1 accounts.
     */
    private function removeSa349RestrictedUsersFromLinUserTable(string $linPath): array
    {
        if (!is_file($linPath) || !is_writable($linPath)) {
            throw new Exception('KLS_NTCS_IDAS.Lin is missing or not writable: ' . $linPath);
        }

        $db = new PDO('sqlite:' . $linPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA busy_timeout = 3000;');

        $exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user'")->fetchColumn();
        if (!$exists) {
            throw new Exception('KLS_NTCS_IDAS.Lin table user not found; cannot remove SA349 restricted users.');
        }

        $columns = $db->query('PRAGMA table_info("user")')->fetchAll(PDO::FETCH_ASSOC);
        $hasNameColumn = false;
        $hasLawColumn  = false;
        foreach ($columns as $column) {
            $columnName = strtolower((string)($column['name'] ?? ''));
            if ($columnName === 'name') {
                $hasNameColumn = true;
            }
            if ($columnName === 'law') {
                $hasLawColumn = true;
            }
        }
        if (!$hasNameColumn) {
            throw new Exception('KLS_NTCS_IDAS.Lin table user has no name column; cannot remove admin.');
        }

        $adminStmt = $db->prepare('DELETE FROM "user" WHERE LOWER(TRIM(CAST("name" AS TEXT))) = :name');
        $adminStmt->execute([':name' => 'admin']);
        $adminAffected = (int)$adminStmt->rowCount();

        $law3Affected = 0;
        if ($hasLawColumn) {
            $law3Stmt = $db->prepare('DELETE FROM "user" WHERE CAST("law" AS INTEGER) = :law');
            $law3Stmt->execute([':law' => 3]);
            $law3Affected = (int)$law3Stmt->rowCount();
        } else {
            error_log('[iDAS UPDATE] SA349 -> non-SA349 cleanup skipped law=3 removal because user.law column is missing: ' . $linPath);
        }

        $db = null;

        $this->assertSqliteHealthy($linPath);
        @chmod($linPath, 0666);
        error_log('[iDAS UPDATE] SA349 -> non-SA349 removed restricted users from KLS_NTCS_IDAS.Lin user table, admin=' . $adminAffected . ', law3=' . $law3Affected);

        return [
            'admin' => $adminAffected,
            'law3'  => $law3Affected,
        ];
    }

    /**
     * Rebuild iDAS DB by copying clean controller DB from NTCS7.
     * Used for downgrades and SA349 -> non-SA349 switches.
     * das.db is intentionally not touched.
     * When SA349 -> non-SA349, KLS_NTCS_IDAS.Lin is recreated from /home/kls/NTCS7/KLS_NTCS.Lin and user.admin / law=3 users are removed.
     */
    private function rebuildIdasDbFromNtcs7ForDowngrade(bool $removeAdminFromLinUserTable = false): array{

        if (PHP_OS_FAMILY !== 'Linux') {
            throw new Exception('DB rebuild is only supported on Linux.');
        }

        $fileMap = $this->getIdasDbRebuildFileMapFromNtcs7();

        $copied = [];
        error_log('[iDAS UPDATE] database rebuild start');
        error_log('[iDAS UPDATE] keep /var/www/html/database/das.db unchanged');

        // Preflight first: do not replace any DB until all required source files and destination folders are ready.
        foreach ($fileMap as $src => $dst) {
            if (!is_file($src) || !is_readable($src)) {
                throw new Exception("DB rebuild source file missing or unreadable: {$src}");
            }

            $dstDir = dirname($dst);
            if (!is_dir($dstDir) || !is_writable($dstDir)) {
                throw new Exception("DB rebuild destination directory is not writable: {$dstDir}");
            }

            if (@filesize($src) <= 0) {
                throw new Exception("DB rebuild source file is empty: {$src}");
            }
        }

        foreach ($fileMap as $src => $dst) {
            $isLinDb = (basename($dst) === 'KLS_NTCS_IDAS.Lin');
            $removeOldLinBeforeCopy = ($removeAdminFromLinUserTable && $isLinDb);

            if ($removeOldLinBeforeCopy && is_file($dst)) {
                $backup = $dst . '.sa349_to_standard_bak_' . date('Ymd_His');
                if (!@copy($dst, $backup)) {
                    $err = error_get_last();
                    throw new Exception('Backup old KLS_NTCS_IDAS.Lin failed before SA349 removal: ' . ($err['message'] ?? 'unknown'));
                }
                @chmod($backup, 0666);
                $this->removeSqliteDbFileSet($dst);
                error_log('[iDAS UPDATE] SA349 -> non-SA349 removed old KLS_NTCS_IDAS.Lin before rebuild: ' . $dst);
            }

            // replaceSqliteDbFile() will copy to temp, run SQLite integrity_check, optionally back up old DB, then atomic rename.
            $this->replaceSqliteDbFile($src, $dst, !$removeOldLinBeforeCopy);

            $barcodeSchemaResult = null;
            if (basename($dst) === 'ntcs_barcode_IDAS.db') {
                $barcodeSchemaResult = $this->ensureBarcodeOneToOneDb($dst);
            }

            $restrictedUsersRemoved = null;
            if ($removeAdminFromLinUserTable && $isLinDb) {
                $restrictedUsersRemoved = $this->removeSa349RestrictedUsersFromLinUserTable($dst);
            }

            clearstatcache(true, $dst);
            if (!is_file($dst) || filesize($dst) <= 0) {
                throw new Exception("Rebuilt DB is invalid or empty: {$dst}");
            }

            $copiedItem = [
                'src' => $src,
                'dst' => $dst,
            ];
            if ($restrictedUsersRemoved !== null) {
                $copiedItem['user_admin_removed'] = (int)($restrictedUsersRemoved['admin'] ?? 0);
                $copiedItem['user_law3_removed']  = (int)($restrictedUsersRemoved['law3'] ?? 0);
            }
            if ($barcodeSchemaResult !== null) {
                $copiedItem['barcode_one_to_one'] = $barcodeSchemaResult;
            }
            $copied[] = $copiedItem;

            error_log("[iDAS UPDATE] database rebuild copied: {$src} -> {$dst}" . ($restrictedUsersRemoved !== null ? ", user.admin removed=" . (int)($restrictedUsersRemoved['admin'] ?? 0) . ", user.law3 removed=" . (int)($restrictedUsersRemoved['law3'] ?? 0) : ''));
        }

        error_log('[iDAS UPDATE] database rebuild end');

        return $copied;
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
            'ERR_UPDATE_LOCKED' => [
                'en-us' => 'System update is already in progress. Please try again later.',
                'zh-tw' => '系統更新中，請稍後再試。',
                'zh-cn' => '系统更新中，请稍后再试。',
            ],
            'ERR_VERSION_LOW'   => [
                'en-us' => 'Update version is lower than current (current: {current}, update: {update}).',
                'zh-tw' => '更新檔版本低於目前版本，無法更新（目前：{current}，更新：{update}）。',
                'zh-cn' => '更新包版本低于当前版本，无法更新（当前：{current}，更新：{update}）。',
            ],
            'ERR_UPDATE_CONFIRM_REQUIRED' => [
                'en-us' => 'This update requires confirmation (current: {current}, update: {update}).',
                'zh-tw' => '此更新需要先勾選確認後才能繼續（目前：{current}，更新：{update}）。',
                'zh-cn' => '此更新需要先勾选确认后才能继续（当前：{current}，更新：{update}）。',
            ],
            'STATUS_DOWNGRADE'  => [
                'en-us' => 'Downgrade',
                'zh-tw' => '降版本',
                'zh-cn' => '降版本',
            ],
            'STATUS_PROFILE_SWITCH' => [
                'en-us' => 'Profile switch',
                'zh-tw' => '版本類型切換',
                'zh-cn' => '版本类型切换',
            ],
            'STATUS_SA349_TO_STANDARD_REBUILD' => [
                'en-us' => 'SA349 to non-SA349 version, DB rebuild required; user.admin and law=3 users will be removed',
                'zh-tw' => 'SA349 切回非 SA349，需重建 DB 並移除 user.admin 與 law=3 使用者',
                'zh-cn' => 'SA349 切回非 SA349，需重建 DB 并移除 user.admin 与 law=3 使用者',
            ],
            'DB_ACTION_REBUILD' => [
                'en-us' => 'Rebuild iDAS DB',
                'zh-tw' => '重建 iDAS DB',
                'zh-cn' => '重建 iDAS DB',
            ],
            'DB_ACTION_KEEP' => [
                'en-us' => 'Keep current DB',
                'zh-tw' => '保留目前 DB',
                'zh-cn' => '保留目前 DB',
            ],
            'STATUS_SAME_VERSION' => [
                'en-us' => 'Same version',
                'zh-tw' => '同版本更新',
                'zh-cn' => '同版本更新',
            ],
            'STATUS_UPGRADE'    => [
                'en-us' => 'Upgrade',
                'zh-tw' => '升版本',
                'zh-cn' => '升版本',
            ],
            'SUC_DEBUG'         => [
                'en-us' => 'Update successful (Debug mode: extracted folder retained).',
                'zh-tw' => '更新成功（Debug模式：保留 extracted 資料夾）。',
                'zh-cn' => '更新成功（调试模式：保留 extracted 文件夹）。',
            ],
            'SUC_OK'            => [
                'en-us' => 'Update successful. Please log in again.',
                'zh-tw' => '更新成功，請重新登入。',
                'zh-cn' => '更新成功，请重新登录。',
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

    
    private function updateForceLogoutPayload(): array
    {
        return [
            'force_logout' => true,
            'redirect_url' => '/idas/public/?url=In',
        ];
    }

    private function sendResponse($type, $msg, array $extra = []) {
        // iDAS update endpoint must return pure JSON.
        // File operations may emit PHP warnings; clear buffers to prevent jQuery JSON parse errors.
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode(array_merge([
            'res_type' => (string)$type,
            'res_msg'  => (string)$msg,
        ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }

    private function copyDirectory($source, $destination) {
        if (!is_dir($source)) {
            throw new Exception('Source directory not found: ' . $source);
        }

        if (!is_dir($destination) && !@mkdir($destination, 0777, true)) {
            $err = error_get_last();
            throw new Exception('Create directory failed: ' . $destination . '. ' . ($err['message'] ?? ''));
        }

        $items = @scandir($source);
        if (!is_array($items)) {
            throw new Exception('Read directory failed: ' . $source);
        }

        foreach ($items as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }

            $src = $source . '/' . $file;
            $dst = $destination . '/' . $file;

            if (is_dir($src)) {
                $this->copyDirectory($src, $dst);
                continue;
            }

            if (file_exists($dst) && !@unlink($dst)) {
                $err = error_get_last();
                throw new Exception('Remove old file failed: ' . $dst . '. ' . ($err['message'] ?? ''));
            }

            if (!@copy($src, $dst)) {
                $err = error_get_last();
                throw new Exception('Copy file failed: ' . $src . ' -> ' . $dst . '. ' . ($err['message'] ?? ''));
            }
            @chmod($dst, 0666);
        }
    }

    private function deleteDirectory($dir) {
        if (!is_dir($dir)) return;

        $items = @scandir($dir);
        if (!is_array($items)) {
            throw new Exception('Read directory failed: ' . $dir);
        }

        foreach ($items as $file) {
            if (in_array($file, ['.', '..'], true)) {
                continue;
            }
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } elseif (file_exists($path) && !@unlink($path)) {
                $err = error_get_last();
                throw new Exception('Delete file failed: ' . $path . '. ' . ($err['message'] ?? ''));
            }
        }

        if (!@rmdir($dir)) {
            $err = error_get_last();
            throw new Exception('Delete directory failed: ' . $dir . '. ' . ($err['message'] ?? ''));
        }
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

        header('Content-Type: application/json; charset=utf-8');

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        $lang = strtolower($_COOKIE['language'] ?? ($_SESSION['language'] ?? 'en-us'));
        if ($lang === 'en') {
            $lang = 'en-us';
        }
        if (!in_array($lang, ['en-us', 'zh-tw', 'zh-cn'], true)) {
            $lang = 'en-us';
        }

        $msg = [
            'en-us' => [
                'invalid_config_format' => 'Please upload a valid configuration file format.',
                'no_file'       => 'Please upload a valid configuration file format.',
                'bad_upload'    => 'Please upload a valid configuration file format.',
                'bad_name'      => 'Invalid file name. Only con_<SN>_YYYYMMDDHHMMSS.Lin is supported.',
                'bad_ext'       => 'Invalid file extension. Only .Lin is supported.',
                'not_sqlite'    => 'The selected .Lin file is not a valid SQLite configuration database.',
                'missing_table' => 'The selected .Lin file is not a valid NTCS system configuration file.',
                'bad_dir'       => 'Upload directory is not writable.',
                'move_fail'     => 'Failed to save import file.',
                'success'       => 'Import successful.',
                'modbus_error'  => 'Controller communication failed.',
                'controller_logged_in' => 'Please log out of the controller before importing system data.',
            ],
            'zh-tw' => [
                'invalid_config_format' => '請上傳正確的設定檔格式。',
                'no_file'       => '請上傳正確的設定檔格式。',
                'bad_upload'    => '請上傳正確的設定檔格式。',
                'bad_name'      => '檔案名稱錯誤，只支援 con_<SN>_YYYYMMDDHHMMSS.Lin。',
                'bad_ext'       => '副檔名錯誤，只支援 .Lin。',
                'not_sqlite'    => '選取的 .Lin 不是有效的 SQLite 系統設定資料庫。',
                'missing_table' => '選取的 .Lin 不是有效的 NTCS 系統設定檔。',
                'bad_dir'       => '上傳目錄不可寫入。',
                'move_fail'     => '儲存匯入檔案失敗。',
                'success'       => '匯入成功。',
                'modbus_error'  => '控制器通訊失敗。',
                'controller_logged_in' => '請先將控制器登出，再匯入系統資料。',
            ],
            'zh-cn' => [
                'invalid_config_format' => '请上传正确的设置文件格式。',
                'no_file'       => '请上传正确的设置文件格式。',
                'bad_upload'    => '请上传正确的设置文件格式。',
                'bad_name'      => '文件名称错误，只支持 con_<SN>_YYYYMMDDHHMMSS.Lin。',
                'bad_ext'       => '扩展名错误，只支持 .Lin。',
                'not_sqlite'    => '选择的 .Lin 不是有效的 SQLite 系统设置数据库。',
                'missing_table' => '选择的 .Lin 不是有效的 NTCS 系统设置文件。',
                'bad_dir'       => '上传目录不可写入。',
                'move_fail'     => '保存导入文件失败。',
                'success'       => '导入成功。',
                'modbus_error'  => '控制器通讯失败。',
                'controller_logged_in' => '请先将控制器登出，再导入系统资料。',
            ],
        ];
        $T = $msg[$lang];

        $fail = function (string $key) use ($T) {
            $this->MiscellaneousModel->generateErrorResponse('Error', $T[$key] ?? $key);
            return;
        };

        if (empty($_FILES) || !isset($_FILES['file'])) {
            return $fail('no_file');
        }

        if (($_FILES['file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return $fail('bad_upload');
        }

        $originalName = basename((string)($_FILES['file']['name'] ?? ''));
        $fileInfo     = pathinfo($originalName);
        $ext          = strtolower($fileInfo['extension'] ?? '');
        $tmpFile      = (string)($_FILES['file']['tmp_name'] ?? '');

        // 正式匯入格式需與 export_sysytem_config() 一致：
        // con_<controller SN>_YYYYMMDDHHMMSS.Lin
        // 例如：con_NTR211859_20260813141200.Lin
        if ($ext !== 'lin') {
            return $fail('bad_ext');
        }

        if (!preg_match('/^con_[A-Za-z0-9_-]+_[0-9]{14}\.Lin$/', $originalName)) {
            return $fail('bad_name');
        }

        // 必須是完整 SQLite DB，且含 NTCS 設定 DB 的識別 table SEQ_type。
        try {
            $checkDb = new PDO('sqlite:' . $tmpFile);
            $checkDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $integrity = $checkDb->query('PRAGMA integrity_check')->fetchColumn();
            if (strtolower(trim((string)$integrity)) !== 'ok') {
                $checkDb = null;
                return $fail('not_sqlite');
            }

            $stmt = $checkDb->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name = :table LIMIT 1");
            $stmt->execute([':table' => 'SEQ_type']);
            $hasSeqType = (bool)$stmt->fetchColumn();
            $checkDb = null;
        } catch (Throwable $e) {
            error_log('[Import_Config] SQLite validate failed: ' . $e->getMessage());
            return $fail('not_sqlite');
        }

        if (!$hasSeqType) {
            return $fail('missing_table');
        }

        // 匯入前控制器必須登出。29002 由統一 protocol layer 讀取，
        // 因此 MODBUS TCP / OP 都使用同一個判斷流程。
        $device_id = $this->getActiveModbusUnitId();
        $loginCheck = $this->idas_check($device_id);
        if (!is_array($loginCheck) || !empty($loginCheck['error'])) {
            $this->logMessage('Import config: controller login-state check failed: ' . (string)($loginCheck['error'] ?? 'unknown'));
            return $fail('modbus_error');
        }
        if ((int)($loginCheck['result'] ?? 0) !== 0) {
            return $fail('controller_logged_in');
        }

        $ftpDir = '/mnt/ramdisk/ftp/';
        if (!is_dir($ftpDir) && !@mkdir($ftpDir, 0777, true)) {
            return $fail('bad_dir');
        }
        if (!is_writable($ftpDir)) {
            return $fail('bad_dir');
        }

        // 原始檔名只做驗證；控制器實際仍使用短暫存檔名 ftp/iDas.Lin，避免檔名長度與協議差異。
        $linPath = $ftpDir . 'iDas.Lin';
        if (is_file($linPath)) {
            @unlink($linPath);
        }

        if (!move_uploaded_file($tmpFile, $linPath)) {
            return $fail('move_fail');
        }
        @chmod($linPath, 0666);

        $unitId = $device_id;

        try {
            $data = [1, 26948, 24947]; // iDas

            // MODBUS TCP / OP 協議都走統一 protocol layer。
            $this->writeControllerRegisters(506, $data, 'IMPORT_CONFIG_FILENAME');
            $this->logMessage('Import config: ' . $originalName . ' validated, staged as iDas.Lin, protocol write 506 OK');

            // 要求控制器套用匯入檔
            $this->writeControllerRegisters(462, [1], 'IMPORT_CONFIG_APPLY');

            $this->MiscellaneousModel->generateErrorResponse('Success', $T['success']);

        } catch (Exception $e) {
            $this->logMessage('Import config protocol write failed: ' . $e->getMessage());
            if (is_file($linPath)) {
                @unlink($linPath);
            }
            $this->MiscellaneousModel->generateErrorResponse('Error', $T['modbus_error']);
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
        // 更新成功後強制登出：清除登入相關 cookie 與 session。
        // 語系 cookie 保留，避免回到登入頁時語系被重置。
        $cookieNames = ['username', 'auth_token', 'user_law', 'PHPSESSID'];
        foreach ($cookieNames as $key) {
            setcookie($key, '', time() - 3600, '/');
            unset($_COOKIE[$key]);
        }

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 3600,
                    $params['path'] ?? '/',
                    $params['domain'] ?? '',
                    (bool)($params['secure'] ?? false),
                    (bool)($params['httponly'] ?? false)
                );
            }
            @session_destroy();
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
        $device_id = $this->getActiveModbusUnitId();

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
                'res_type' => 'Error',
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

    private function syncFilesToController($modbus = null): void {

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


        // ===============================
    // 圖片資料夾路徑
    // ===============================
    private function getSeqImgDir(): string
    {
        return '/home/kls/NTCS7/Message';
    }

    // ===============================
    // 取得圖片清單
    // ===============================
    public function get_seq_images(){
        
        header('Content-Type: application/json; charset=utf-8');

        $dir = $this->getSeqImgDir();

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        if (!is_dir($dir)) {
            echo json_encode([
                'result' => false,
                'msg' => $this->seqImageText('image_dir_not_found')
            ]);
            exit;
        }

        $allowedExt = ['jpg', 'jpeg', 'png', 'bmp'];
        $files = array_diff(scandir($dir), ['.', '..']);
        $result = [];

        foreach ($files as $file) {
            $fullPath = $dir . '/' . $file;

            if (!is_file($fullPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                continue;
            }

            $imageInfo = @getimagesize($fullPath);
            $imageWidth = (is_array($imageInfo) && isset($imageInfo[0])) ? (int)$imageInfo[0] : 0;
            $imageHeight = (is_array($imageInfo) && isset($imageInfo[1])) ? (int)$imageInfo[1] : 0;

            $result[] = [
                'name' => $file,
                'url'  => '/idas/public/?url=Settings/show_seq_image&name=' . rawurlencode($file),
                // 檔案容量，單位 bytes。前端會顯示成 KB / MB。
                'file_size' => filesize($fullPath) ?: 0,
                // 圖片尺寸，單位 px。
                'width' => $imageWidth,
                'height' => $imageHeight,
                'dimension' => ($imageWidth > 0 && $imageHeight > 0) ? ($imageWidth . ' x ' . $imageHeight) : '',
                'time' => filemtime($fullPath) ?: 0
            ];
        }

        // 上傳時間排序：新 → 舊
        usort($result, function ($a, $b) {
            return ($b['time'] ?? 0) <=> ($a['time'] ?? 0);
        });

        // 不回傳 time 給前端
        $result = array_map(function ($item) {
            unset($item['time']);
            return $item;
        }, $result);

        echo json_encode([
            'result' => true,
            'files'  => $result
        ]);
        exit;
    }


    // ===============================
    // 顯示圖片（給 <img src=""> 預覽用）
    // ===============================
    public function show_seq_image()
    {
        $fileName = $_GET['name'] ?? '';
        $fileName = basename($fileName);

        $dir = $this->getSeqImgDir();
        $basePath = realpath($dir);
        $fullPath = realpath($dir . '/' . $fileName);

        if (
            !$fileName ||
            !$basePath ||
            !$fullPath ||
            strpos($fullPath, $basePath) !== 0 ||
            !is_file($fullPath)
        ) {
            http_response_code(404);
            exit('Image not found');
        }

        $mime = mime_content_type($fullPath);
        if (!$mime) {
            $mime = 'application/octet-stream';
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($fullPath));
        readfile($fullPath);
        exit;
    }

    // ===============================
    // 圖片裁切：center crop -> resize to 1600 x 1900, output JPEG
    // 原始上傳檔只當暫存來源，不另外保存。
    // ===============================
    private function normalizeSeqImage(string $src, string $dst, string $mime): bool
    {
       

        if (!function_exists('imagecreatetruecolor')) {
            return false;
        }

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/pjpeg':
                $srcImg = @imagecreatefromjpeg($src);
                break;
            case 'image/png':
                $srcImg = @imagecreatefrompng($src);
                break;
            case 'image/bmp':
            case 'image/x-ms-bmp':
                $srcImg = function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($src) : false;
                break;
            default:
                return false;
        }

        if (!$srcImg) {
            return false;
        }

        $srcW = imagesx($srcImg);
        $srcH = imagesy($srcImg);

        if ($srcW <= 0 || $srcH <= 0) {
            imagedestroy($srcImg);
            return false;
        }

        $targetW = self::SEQ_IMG_WIDTH;
        $targetH = self::SEQ_IMG_HEIGHT;
        $srcRatio = $srcW / $srcH;
        $targetRatio = $targetW / $targetH;

        // Center crop: keep the middle, crop left/right or top/bottom as needed.
        if ($srcRatio > $targetRatio) {
            $cropH = $srcH;
            $cropW = (int)round($srcH * $targetRatio);
            $cropX = (int)round(($srcW - $cropW) / 2);
            $cropY = 0;
        } else {
            $cropW = $srcW;
            $cropH = (int)round($srcW / $targetRatio);
            $cropX = 0;
            $cropY = (int)round(($srcH - $cropH) / 2);
        }

        $canvas = imagecreatetruecolor($targetW, $targetH);
        if (!$canvas) {
            imagedestroy($srcImg);
            return false;
        }

        // JPEG has no alpha; use white background for transparent PNG/BMP.
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, $targetW, $targetH, $white);

        $ok = imagecopyresampled(
            $canvas,
            $srcImg,
            0,
            0,
            $cropX,
            $cropY,
            $targetW,
            $targetH,
            $cropW,
            $cropH
        );

        if ($ok) {
            $ok = imagejpeg($canvas, $dst, 90);
            if ($ok) {
                $this->setJpegDpi($dst, self::SEQ_IMG_DPI);
            }
        }

        imagedestroy($srcImg);
        imagedestroy($canvas);

        return (bool)$ok;
    }

    // Best-effort JFIF DPI patch for JPEG files created by GD.
    private function setJpegDpi(string $path, int $dpi): void
    {
        $data = @file_get_contents($path);
        if ($data === false || strlen($data) < 18) {
            return;
        }

        // SOI + APP0(JFIF) marker, then JFIF\0 identifier.
        if (substr($data, 0, 4) !== "\xFF\xD8\xFF\xE0" || substr($data, 6, 5) !== "JFIF\x00") {
            return;
        }

        $dpi = max(1, min(65535, $dpi));
        $density = chr(($dpi >> 8) & 0xFF) . chr($dpi & 0xFF);

        // Offset 13 = density units. 1 = dots per inch.
        $data[13] = "\x01";
        $data = substr_replace($data, $density, 14, 2);
        $data = substr_replace($data, $density, 16, 2);

        @file_put_contents($path, $data);
    }

    private function seqImageLang(): string
    {
        $raw = strtolower((string)(
            $_COOKIE['languages']
            ?? $_COOKIE['language']
            ?? $_COOKIE['lang']
            ?? $_SERVER['HTTP_ACCEPT_LANGUAGE']
            ?? 'en-us'
        ));
        $raw = str_replace('_', '-', $raw);

        if ($raw === 'zh-tw' || strpos($raw, 'zh-hant') !== false || strpos($raw, 'tw') !== false || strpos($raw, 'hk') !== false || strpos($raw, 'mo') !== false) {
            return 'zh-tw';
        }
        if ($raw === 'zh-cn' || strpos($raw, 'zh-hans') !== false || strpos($raw, 'cn') !== false || strpos($raw, 'sg') !== false) {
            return 'zh-cn';
        }
        return 'en-us';
    }

    private function seqImageText(string $key, array $vars = []): string
    {
        static $dict = [
            'upload_success' => [
                'en-us' => 'Upload success.',
                'zh-tw' => '上傳成功。',
                'zh-cn' => '上传成功。',
            ],
            'upload_failed' => [
                'en-us' => 'Upload failed.',
                'zh-tw' => '上傳失敗。',
                'zh-cn' => '上传失败。',
            ],
            'delete_success' => [
                'en-us' => 'Delete success.',
                'zh-tw' => '刪除成功。',
                'zh-cn' => '删除成功。',
            ],
            'delete_failed' => [
                'en-us' => 'Delete failed.',
                'zh-tw' => '刪除失敗。',
                'zh-cn' => '删除失败。',
            ],
            'no_image_selected' => [
                'en-us' => 'Please select image.',
                'zh-tw' => '請選擇圖片。',
                'zh-cn' => '请选择图片。',
            ],
            'image_dir_not_found' => [
                'en-us' => 'Image directory not found.',
                'zh-tw' => '找不到圖片資料夾。',
                'zh-cn' => '找不到图片文件夹。',
            ],
            'no_image_uploaded' => [
                'en-us' => 'No image uploaded.',
                'zh-tw' => '未上傳圖片。',
                'zh-cn' => '未上传图片。',
            ],
            'upload_dir_not_writable' => [
                'en-us' => 'Upload directory is not writable.',
                'zh-tw' => '上傳資料夾無法寫入。',
                'zh-cn' => '上传文件夹无法写入。',
            ],
            'invalid_image_type' => [
                'en-us' => 'Only BMP, PNG, JPEG, and JPG files are allowed.',
                'zh-tw' => '只接受 BMP、PNG、JPEG、JPG 四種圖片格式。',
                'zh-cn' => '只接受 BMP、PNG、JPEG、JPG 四种图片格式。',
            ],
            'image_limit_reached' => [
                'en-us' => 'The maximum number of images is 300. You cannot upload more images.',
                'zh-tw' => '圖片已達 300 張上限，無法再上傳。',
                'zh-cn' => '图片已达 300 张上限，无法再上传。',
            ],
            'selection_limit_exceeded' => [
                'en-us' => 'You can upload up to 6 images at a time.',
                'zh-tw' => '一次最多只能上傳 6 張圖片。',
                'zh-cn' => '一次最多只能上传 6 张图片。',
            ],
            'upload_busy' => [
                'en-us' => 'Another image upload is being processed. Please try again shortly.',
                'zh-tw' => '目前已有圖片上傳作業正在處理，請稍後再試。',
                'zh-cn' => '当前已有图片上传作业正在处理，请稍后再试。',
            ],
            'upload_lock_failed' => [
                'en-us' => 'Unable to create the image upload lock.',
                'zh-tw' => '無法建立圖片上傳鎖定檔。',
                'zh-cn' => '无法创建图片上传锁定文件。',
            ],
        ];

        $lang = $this->seqImageLang();
        $msg = $dict[$key][$lang] ?? ($dict[$key]['en-us'] ?? $key);
        if (!empty($vars)) {
            foreach ($vars as $name => $value) {
                $msg = str_replace('{' . $name . '}', (string)$value, $msg);
            }
        }
        return $msg;
    }

    // ===============================
    // 上傳圖片
    // ===============================
    public function upload_seq_images(){
        
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        try {
            $dir = $this->getSeqImgDir();

            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            if (!is_dir($dir) || !is_writable($dir)) {
                throw new Exception($this->seqImageText('upload_dir_not_writable')); // . ': ' . $dir
            }

            if (empty($_FILES['images'])) {
                throw new Exception($this->seqImageText('no_image_uploaded'));
            }

            $maxFilesPerRequest = 6;
            $uploadNames = $_FILES['images']['name'] ?? [];
            $uploadCount = is_array($uploadNames)
                ? count($uploadNames)
                : 0;

            if (
                $uploadCount <= 0
                || $uploadCount > $maxFilesPerRequest
            ) {
                http_response_code(422);

                echo json_encode([
                    'result' => false,
                    'code' => 'SELECTION_LIMIT_EXCEEDED',
                    'max_files' => $maxFilesPerRequest,
                    'msg' => $this->seqImageText(
                        'selection_limit_exceeded'
                    )
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            /*
             * 防止連點、多分頁或多瀏覽器同時重複執行
             * GD 裁切與 JPG 輸出。
             */
            $uploadLockPath = sys_get_temp_dir()
                . DIRECTORY_SEPARATOR
                . 'idas_seq_image_upload.lock';

            $uploadLockHandle = @fopen(
                $uploadLockPath,
                'c+'
            );

            if ($uploadLockHandle === false) {
                throw new Exception(
                    $this->seqImageText(
                        'upload_lock_failed'
                    )
                );
            }

            if (!@flock(
                $uploadLockHandle,
                LOCK_EX | LOCK_NB
            )) {
                @fclose($uploadLockHandle);
                http_response_code(409);

                echo json_encode([
                    'result' => false,
                    'code' => 'UPLOAD_BUSY',
                    'msg' => $this->seqImageText(
                        'upload_busy'
                    )
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            // Only BMP, PNG, JPEG, JPG are accepted.
            // GIF / WEBP and other formats are rejected here even if the browser allows them.
            $allowedMime = [
                'image/jpeg'     => 'jpg',
                'image/jpg'      => 'jpg',
                'image/pjpeg'    => 'jpg',
                'image/png'      => 'png',
                'image/bmp'      => 'bmp',
                'image/x-ms-bmp' => 'bmp',
            ];

            $maxImages = 300;
            $allowedStoredExt = ['jpg', 'jpeg', 'png', 'bmp'];
            $existingFiles = [];
            foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
                $fullPath = $dir . '/' . $file;
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (is_file($fullPath) && in_array($ext, $allowedStoredExt, true)) {
                    $existingFiles[strtolower($file)] = true;
                }
            }
            $imageCount = count($existingFiles);
            $reservedNewNames = [];

            $success = [];
            $failed = [];

            $names    = $_FILES['images']['name'] ?? [];
            $tmpNames = $_FILES['images']['tmp_name'] ?? [];
            $errors   = $_FILES['images']['error'] ?? [];
            $sizes    = $_FILES['images']['size'] ?? [];

            foreach ($names as $i => $originalName) {
                $tmp  = $tmpNames[$i] ?? '';
                $err  = $errors[$i] ?? UPLOAD_ERR_NO_FILE;
                $size = $sizes[$i] ?? 0;

                if ($err !== UPLOAD_ERR_OK) {
                    $failed[] = $originalName . ' (upload error code=' . $err . ')';
                    continue;
                }

                if (!is_uploaded_file($tmp)) {
                    $failed[] = $originalName . ' (invalid upload source)';
                    continue;
                }

                if ($size <= 0) {
                    $failed[] = $originalName . ' (empty file)';
                    continue;
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if (!$finfo) {
                    $failed[] = $originalName . ' (cannot open finfo)';
                    continue;
                }

                $mime = finfo_file($finfo, $tmp);
                finfo_close($finfo);

                if (!isset($allowedMime[$mime])) {
                    $failed[] = $originalName . ' (' . $this->seqImageText('invalid_image_type') . ')';
                    continue;
                }

                $baseName = strtolower(pathinfo($originalName, PATHINFO_FILENAME));
                $baseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $baseName);
                $baseName = trim($baseName, '_');

                if ($baseName === '') {
                    $baseName = 'img_' . date('YmdHis') . '_' . $i;
                }

                // Controller image storage uses normalized JPG output.
                // Same final filename means overwrite by force; do not create _1, _2, ... files.
                $finalName = $baseName . '.jpg';
                $target = $dir . '/' . $finalName;
                $finalNameKey = strtolower($finalName);

                $isOverwrite = isset($existingFiles[$finalNameKey]) || isset($reservedNewNames[$finalNameKey]);
                if (!$isOverwrite && $imageCount >= $maxImages) {
                    $failed[] = $originalName . ' (' . $this->seqImageText('image_limit_reached') . ')';
                    continue;
                }

                if ($this->normalizeSeqImage($tmp, $target, $mime)) {
                    if (!$isOverwrite) {
                        $existingFiles[$finalNameKey] = true;
                        $reservedNewNames[$finalNameKey] = true;
                        $imageCount++;
                    }
                    @chmod($target, 0666);
                    $success[] = $finalName;
                } else {
                    $detail = 'crop failed; mime=' . $mime
                        . '; gd=' . (function_exists('imagecreatetruecolor') ? 'yes' : 'no')
                        . '; writable=' . (is_writable($dir) ? 'yes' : 'no');

                    error_log('[SEQ IMG UPLOAD] ' . $originalName . ' ' . $detail);
                    $failed[] = $originalName . ' (' . $detail . ')';
                }
            }

            $msg = count($success) > 0 ? $this->seqImageText('upload_success') : $this->seqImageText('upload_failed');
            if (!empty($failed)) {
                $msg .= ' ' . implode('; ', $failed);
            }

            if (
                isset($uploadLockHandle)
                && is_resource($uploadLockHandle)
            ) {
                @flock($uploadLockHandle, LOCK_UN);
                @fclose($uploadLockHandle);
            }

            echo json_encode([
                'result'  => count($success) > 0,
                'success' => $success,
                'failed'  => $failed,
                'processed_count' => count($success),
                'requested_count' => $uploadCount,
                'msg'     => $msg
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            if (
                isset($uploadLockHandle)
                && is_resource($uploadLockHandle)
            ) {
                @flock($uploadLockHandle, LOCK_UN);
                @fclose($uploadLockHandle);
            }

            while (ob_get_level()) {
                ob_end_clean();
            }

            header('Content-Type: application/json; charset=utf-8');

            error_log('[SEQ IMG UPLOAD ERROR] ' . $e->getMessage());

            http_response_code(500);

            echo json_encode([
                'result' => false,
                'msg'    => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }


    // ===============================
    // 刪除圖片
    // ===============================
    public function delete_seq_images()
    {
        header('Content-Type: application/json; charset=utf-8');

        $names = $_POST['names'] ?? [];

        if (!is_array($names) || empty($names)) {
            echo json_encode([
                'result' => false,
                'msg' => $this->seqImageText('no_image_selected')
            ]);
            exit;
        }

        $dir = realpath($this->getSeqImgDir());

        if (!$dir) {
            echo json_encode([
                'result' => false,
                'msg' => $this->seqImageText('image_dir_not_found')
            ]);
            exit;
        }

        $deleted = [];
        $failed = [];

        foreach ($names as $name) {
            $safeName = basename($name);
            $fullPath = realpath($dir . '/' . $safeName);

            if (!$fullPath || strpos($fullPath, $dir) !== 0 || !is_file($fullPath)) {
                $failed[] = $safeName;
                continue;
            }

            if (@unlink($fullPath)) {
                $deleted[] = $safeName;
            } else {
                $failed[] = $safeName;
            }
        }

        echo json_encode([
            'result'  => count($deleted) > 0,
            'deleted' => $deleted,
            'failed'  => $failed,
            'msg'     => count($deleted) > 0 ? $this->seqImageText('delete_success') : $this->seqImageText('delete_failed')
        ]);
        exit;
    }


    private function accountUserDbPath(): string
    {
        $candidates = [];

        if (PHP_OS_FAMILY === 'Linux') {
            $candidates[] = '/var/www/html/database/KLS_NTCS_IDAS.Lin';
            $candidates[] = '/home/kls/NTCS7/KLS_NTCS.Lin';
            $candidates[] = '/home/kls/NTCS7/KLS_NTCS.Lin';
        } else {
            $candidates[] = __DIR__ . '/../../database/KLS_NTCS_IDAS.Lin';
            $candidates[] = __DIR__ . '/../../../database/KLS_NTCS_IDAS.Lin';
            $candidates[] = '../database/KLS_NTCS_IDAS.Lin';
        }

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return $candidates[0] ?? '/var/www/html/database/KLS_NTCS_IDAS.Lin';
    }

    private function accountUserDb(): PDO
    {
        $dbPath = $this->accountUserDbPath();

        if (!is_file($dbPath)) {
            throw new Exception('Account DB not found: ' . $dbPath);
        }

        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $db;
    }

    private function accountUserJson(bool $ok, string $msg, array $extra = []): void
    {
        while (ob_get_level()) {
            ob_end_clean();
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

    private function accountUserLocale(): string
    {
        $raw = strtolower(str_replace('_', '-', (string)($_COOKIE['language'] ?? $_COOKIE['lang'] ?? $_SESSION['language'] ?? '')));
        if ($raw === 'zh-tw' || $raw === 'zh-hant' || $raw === 'tw') return 'zh-tw';
        if ($raw === 'zh-cn' || $raw === 'zh-hans' || $raw === 'cn' || $raw === 'zh') return 'zh-cn';
        return 'en-us';
    }

    private function accountUserFallbackText(string $key, string $default = ''): string
    {
        $lang = $this->accountUserLocale();
        $dict = [
            'en-us' => [
                'account_only_admin' => 'Only admin can use Account setting.',
                'account_cannot_be_empty_suffix' => 'cannot be empty.',
                'account_only_allows_suffix' => 'only allows A-Z, a-z, 0-9.',
                'account_username_rule_suffix' => 'must be 6 to 8 characters and only allows A-Z, a-z, 0-9.',
                'account_password_rule_suffix' => 'must be exactly 4 digits, 0-9.',
                'account_table_not_found' => 'table user not found.',
            ],
            'zh-tw' => [
                'account_only_admin' => '只有 admin 可以使用帳號設定。',
                'account_cannot_be_empty_suffix' => '不可空白。',
                'account_only_allows_suffix' => '只允許 A-Z、a-z、0-9。',
                'account_username_rule_suffix' => '必須為 6 到 8 個字元，且只允許 A-Z、a-z、0-9。',
                'account_password_rule_suffix' => '必須為 4 碼數字 0-9。',
                'account_table_not_found' => '找不到 user 資料表。',
            ],
            'zh-cn' => [
                'account_only_admin' => '只有 admin 可以使用账号设定。',
                'account_cannot_be_empty_suffix' => '不可空白。',
                'account_only_allows_suffix' => '只允许 A-Z、a-z、0-9。',
                'account_username_rule_suffix' => '必须为 6 到 8 个字符，且只允许 A-Z、a-z、0-9。',
                'account_password_rule_suffix' => '必须为 4 码数字 0-9。',
                'account_table_not_found' => '找不到 user 数据表。',
            ],
        ];

        return $dict[$lang][$key] ?? $dict['en-us'][$key] ?? $default;
    }

    private function accountUserText(string $key, string $default = ''): string
    {
        static $accountText = null;

        if ($accountText === null) {
            $accountText = [];
            try {
                $file = $this->MiscellaneousModel->lang_load();
                if (!empty($file) && is_file($file)) {
                    $text = [];
                    include $file;
                    if (isset($text) && is_array($text)) {
                        $accountText = $text;
                    }
                }
            } catch (Throwable $e) {
                $accountText = [];
            }
        }

        if (isset($accountText[$key]) && (string)$accountText[$key] !== '') {
            return (string)$accountText[$key];
        }

        return $this->accountUserFallbackText($key, $default);
    }

    private function accountUserFormatText(string $key, string $default = '', array $vars = []): string
    {
        $msg = $this->accountUserText($key, $default);
        foreach ($vars as $k => $v) {
            $msg = str_replace('{' . $k . '}', (string)$v, $msg);
        }
        return $msg;
    }

    private function accountUserProtectedNames(): array
    {
        return ['kls', 'guest', 'admin'];
    }

    private function accountUserIsProtectedName(string $name): bool
    {
        return in_array(strtolower(trim($name)), $this->accountUserProtectedNames(), true);
    }

    private function accountUserRequireAdmin(): void
    {
        // Account 管理功能只允許 cookie username=admin 使用。
        if ($this->accountUserCurrentUsername() !== 'admin') {
            $this->accountUserJson(false, $this->accountUserText('account_only_admin', 'Only admin can use Account setting.'));
        }
    }

    private function accountUserValidateText(string $value, string $label): void
    {
        if ($value === '') {
            throw new Exception($label . ' ' . $this->accountUserText('account_cannot_be_empty_suffix', 'cannot be empty.'));
        }

        // 既有帳號 key 檢查：允許 A-Z / a-z / 0-9。
        if (!preg_match('/^[A-Za-z0-9]+$/', $value)) {
            throw new Exception($label . ' ' . $this->accountUserText('account_only_allows_suffix', 'only allows A-Z, a-z, 0-9.'));
        }
    }

    private function accountUserValidateUsername(string $value, string $label = 'Username'): void
    {
        if ($value === '') {
            throw new Exception($label . ' ' . $this->accountUserText('account_cannot_be_empty_suffix', 'cannot be empty.'));
        }

        // 新帳號 / 改名：6~8 字元，只允許 A-Z / a-z / 0-9。
        if (!preg_match('/^[A-Za-z0-9]{6,8}$/', $value)) {
            throw new Exception($label . ' ' . $this->accountUserText('account_username_rule_suffix', 'must be 6 to 8 characters and only allows A-Z, a-z, 0-9.'));
        }
    }

    private function accountUserValidatePassword(string $value, string $label = 'Password'): void
    {
        if ($value === '') {
            throw new Exception($label . ' ' . $this->accountUserText('account_cannot_be_empty_suffix', 'cannot be empty.'));
        }

        // 密碼固定 4 碼數字，允許 0000。
        if (!preg_match('/^[0-9]{4}$/', $value)) {
            throw new Exception($label . ' ' . $this->accountUserText('account_password_rule_suffix', 'must be exactly 4 digits, 0-9.'));
        }
    }

    private function accountUserAssertTable(PDO $db): void
    {
        $exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user'")->fetchColumn();
        if (!$exists) {
            throw new Exception($this->accountUserText('account_table_not_found', 'table user not found.'));
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


    /**
     * Ensure a default account exists in the opened account user table.
     * If the account already exists, keep the existing password/law unchanged.
     */
    private function accountUserEnsureDefaultAccount(PDO $db, string $accountName, string $password, int $law = 1): bool
    {
        $this->accountUserAssertTable($db);

        $accountName = trim($accountName);
        $password = trim($password);

        if ($accountName === '' || $password === '') {
            throw new Exception('Default account name/password cannot be empty.');
        }

        $columns = $this->accountUserTableColumns($db);
        if (!in_array('name', $columns, true) || !in_array('passwd', $columns, true)) {
            throw new Exception('user table must contain name and passwd columns.');
        }

        $stmt = $db->prepare('SELECT COUNT(*) FROM `user` WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))');
        $stmt->execute([':name' => $accountName]);
        if ((int)$stmt->fetchColumn() > 0) {
            return false;
        }

        $insertColumns = [];
        $params = [];

        if (in_array('sn', $columns, true)) {
            $insertColumns[] = 'sn';
            $params[':sn'] = (int)$db->query('SELECT COALESCE(MAX(sn), -1) + 1 FROM `user`')->fetchColumn();
        }

        $insertColumns[] = 'name';
        $params[':name'] = $accountName;

        $insertColumns[] = 'passwd';
        $params[':passwd'] = $password;

        if (in_array('law', $columns, true)) {
            $insertColumns[] = 'law';
            $params[':law'] = $law;
        }

        $columnSql = implode(', ', array_map([$this, 'accountUserQuoteIdentifier'], $insertColumns));
        $placeholders = implode(', ', array_keys($params));

        $stmt = $db->prepare('INSERT INTO `user` (' . $columnSql . ') VALUES (' . $placeholders . ')');
        $stmt->execute($params);

        return true;
    }

    private function accountUserEnsureDefaultAdmin(PDO $db): bool
    {
        return $this->accountUserEnsureDefaultAccount($db, 'admin', '0734', 1);
    }

    private function accountUserEnsureDefaultGuest(PDO $db): bool
    {
        return $this->accountUserEnsureDefaultAccount($db, 'guest', '0000', 1);
    }

    private function accountUserEnsureDefaultProtectedAccounts(PDO $db): void
    {
        $this->accountUserEnsureDefaultAdmin($db);
        $this->accountUserEnsureDefaultGuest($db);
    }

    public function account_user_upload_controller(): void
    {
        $targetDb = null;

        try {
            $this->accountUserRequireAdmin();

            if (PHP_OS_FAMILY !== 'Linux') {
                throw new Exception($this->accountUserText('account_upload_controller_linux_only', 'Sync to controller is only supported on Linux.'));
            }

            $sourcePath = $this->accountUserIdasDbPathStrict();
            $targetPath = $this->accountUserControllerDbPath();

            if (!is_file($sourcePath)) {
                throw new Exception($this->accountUserFormatText('account_upload_controller_source_missing', 'Source iDAS DB not found: {path}', ['path' => $sourcePath]));
            }
            if (!is_file($targetPath)) {
                throw new Exception($this->accountUserFormatText('account_upload_controller_target_missing', 'Target controller DB not found: {path}', ['path' => $targetPath]));
            }
            if (!is_writable($targetPath) || !is_writable(dirname($targetPath))) {
                throw new Exception($this->accountUserFormatText('account_upload_controller_target_not_writable', 'Target controller DB or folder is not writable: {path}', ['path' => $targetPath]));
            }

            $sourceDb = $this->accountUserOpenSqliteFile($sourcePath);
            $targetDb = $this->accountUserOpenSqliteFile($targetPath);

            $this->accountUserAssertTable($sourceDb);
            $this->accountUserAssertTable($targetDb);

            // Safety: before syncing, make sure the iDAS source DB has admin.
            $this->accountUserEnsureDefaultProtectedAccounts($sourceDb);

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
                throw new Exception($this->accountUserText('account_upload_controller_empty', 'Source user table has no data. Sync aborted.'));
            }

            // Backup target DB before touching table user only.
            $backupPath = $targetPath . '.user_backup_' . date('Ymd_His');
            if (!@copy($targetPath, $backupPath)) {
                throw new Exception($this->accountUserFormatText('account_upload_controller_backup_failed', 'Backup target DB failed: {path}', ['path' => $backupPath]));
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

            // Safety: after full mirror, guarantee controller DB still has admin.
            $this->accountUserEnsureDefaultProtectedAccounts($targetDb);

            $targetDb->commit();
            @chmod($targetPath, 0666);
            @exec('sync');

            $this->accountUserJson(true, $this->accountUserFormatText('account_upload_controller_success', 'Upload user list to controller success. Rows: {rows}.', ['rows' => count($rows)]), [
                'rows'        => count($rows),
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'backup_path' => $backupPath,
            ]);
        } catch (Throwable $e) {
            if ($targetDb instanceof PDO && $targetDb->inTransaction()) {
                $targetDb->rollBack();
            }

            $this->accountUserJson(false, $this->accountUserFormatText('account_upload_controller_failed', 'Upload user list to controller failed: {error}', ['error' => $e->getMessage()]));
        }
    }

    public function account_user_list(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            // kls 帳號需要顯示在 Account 清單，但儲存/刪除會被保護。
            $rows = $db->query("
                SELECT sn, name, passwd, law
                FROM `user`
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


    public function account_user_get_password(): void
    {
        try {
            $this->accountUserRequireAdmin();

            $username = isset($_POST['username']) ? $this->accountUserClean((string)$_POST['username']) : '';
            $this->accountUserValidateText($username, 'Username');

            // kls 帳號允許開啟查看資料，但不可儲存/刪除。
            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $statement = $db->prepare("
                SELECT CAST(passwd AS TEXT) AS passwd
                FROM `user`
                WHERE LOWER(name) = LOWER(:name)
                LIMIT 1
            ");
            $statement->execute([
                ':name' => $username,
            ]);

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception('Account not found.');
            }

            $this->accountUserJson(true, 'OK', [
                'username' => $username,
                'passwd'   => (string)($row['passwd'] ?? ''),
            ]);
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }


    public function account_user_export(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            // Export all accounts, including built-in Kls / kls account.
            $rows = $db->query("
                SELECT sn, name, passwd, law
                FROM `user`
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
            // CSV hides internal law column. law is managed internally and defaults to 1 on import.
            fputcsv($fp, ['No', 'User Name', 'Password']);

            $no = 1;
            foreach ($rows as $row) {
                fputcsv($fp, [
                    $no++,
                    $row['name'] ?? '',
                    $this->accountUserExcelText((string)($row['passwd'] ?? '')),
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
                throw new Exception($this->accountUserText('account_cannot_open_csv', 'Cannot open uploaded CSV file.'));
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $header = fgetcsv($fp);
            if (!$header || count($header) < 2) {
                fclose($fp);
                throw new Exception($this->accountUserText('account_csv_invalid', 'CSV format invalid. Header must include User Name and Password.'));
            }

            // Normalize header names from export: No, User Name, Password.
            // Older CSV files with Law column are still accepted, but law is ignored and fixed to 1.
            $headerMap = [];
            foreach ($header as $idx => $col) {
                $key = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string)$col)));
                $key = str_replace([' ', '_', '-'], '', $key);
                $headerMap[$key] = $idx;
            }

            $nameIndex = $headerMap['username'] ?? $headerMap['name'] ?? 1;
            $passIndex = $headerMap['password'] ?? $headerMap['passwd'] ?? 2;
            // Law is intentionally not exposed in CSV. Always import accounts with default law = 1.

            $importMode = (string)($_POST['import_mode'] ?? 'append');
            $importMode = ($importMode === 'overwrite') ? 'overwrite' : 'append';
            $protectedUsers = ['kls', 'guest', 'admin'];

            $inserted = 0;
            $updated  = 0;
            $skipped  = 0;
            $lineNo   = 1;
            $errors   = [];
            $rowsToImport = [];

            // First pass: validate the whole CSV before touching DB.
            while (($row = fgetcsv($fp)) !== false) {
                $lineNo++;

                // Skip blank lines.
                if (count(array_filter($row, function($v) { return trim((string)$v) !== ''; })) === 0) {
                    continue;
                }

                $name = $this->accountUserCsvText((string)($row[$nameIndex] ?? ''));
                $password = $this->accountUserCsvText((string)($row[$passIndex] ?? ''));
                $law = 1;
                $nameLower = strtolower($name);

                // Built-in accounts are protected and will not be imported or modified.
                if (in_array($nameLower, $protectedUsers, true)) {
                    $skipped++;
                    continue;
                }

                try {
                    $this->accountUserValidateUsername($name, $this->accountUserText('account_username', 'Username'));
                    $this->accountUserValidatePassword($password, $this->accountUserText('account_password', 'Password'));
                } catch (Throwable $e) {
                    $errors[] = 'Line ' . $lineNo . ': ' . $e->getMessage();
                    continue;
                }

                $rowsToImport[] = [
                    'name' => $name,
                    'passwd' => $password,
                    'law' => $law,
                ];
            }

            fclose($fp);

            if (!empty($errors)) {
                throw new Exception($this->accountUserText('account_csv_validation_failed', 'CSV validation failed. No data was imported.') . ' ' . implode(' ', array_slice($errors, 0, 10)));
            }

            $db->beginTransaction();

            if ($importMode === 'overwrite') {
                // Only table user is modified. Built-in accounts are protected.
                $db->exec("DELETE FROM `user` WHERE LOWER(name) NOT IN ('kls', 'guest', 'admin')");
            }

            foreach ($rowsToImport as $row) {
                $name = $row['name'];
                $password = $row['passwd'];
                $law = $row['law'];

                $stmt = $db->prepare('SELECT COUNT(*) FROM `user` WHERE LOWER(name) = LOWER(:name)');
                $stmt->execute([':name' => $name]);
                $exists = (int)$stmt->fetchColumn() > 0;

                if ($exists) {
                    $stmt = $db->prepare('UPDATE `user` SET passwd = :passwd, law = :law WHERE LOWER(name) = LOWER(:name)');
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

            // Safety: Append / Overwrite import must never leave iDAS without admin/guest.
            $beforeProtectedCount = $inserted;
            if ($this->accountUserEnsureDefaultAdmin($db)) {
                $inserted++;
            }
            if ($this->accountUserEnsureDefaultGuest($db)) {
                $inserted++;
            }

            $db->commit();

            $message = $this->accountUserFormatText('account_import_result', 'Import success. Inserted: {inserted}, Updated: {updated}, Skipped: {skipped}.', ['inserted' => $inserted, 'updated' => $updated, 'skipped' => $skipped]);

            $this->accountUserJson(true, $message, [
                'mode'     => $importMode,
                'inserted' => $inserted,
                'updated'  => $updated,
                'skipped'  => $skipped,
                'errors'   => [],
            ]);
        } catch (Throwable $e) {
            if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
                $db->rollBack();
            }

            if (isset($fp) && is_resource($fp)) {
                fclose($fp);
            }

            $this->accountUserJson(false, $this->accountUserFormatText('account_import_account_failed', 'Import account failed: {error}', ['error' => $e->getMessage()]));
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

            $this->accountUserValidateUsername($name, $this->accountUserText('account_username', 'Username'));
            if ($this->accountUserIsProtectedName($name)) {
                throw new Exception($this->accountUserText('account_protected_create_action', 'This account is protected and cannot be created.'));
            }
            $this->accountUserValidatePassword($password, $this->accountUserText('account_password', 'Password'));

            if ($password !== $confirm) {
                throw new Exception($this->accountUserText('account_confirm_diff', 'Confirm password is different.'));
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $stmt = $db->prepare("SELECT COUNT(*) FROM `user` WHERE name = :name");
            $stmt->execute([':name' => $name]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception($this->accountUserText('account_username_exists', 'Username already exists.'));
            }

            $nextSn = (int)$db->query("SELECT COALESCE(MAX(sn), -1) + 1 FROM `user`")->fetchColumn();

            $stmt = $db->prepare("INSERT INTO `user` (sn, name, passwd, law) VALUES (:sn, :name, :passwd, :law)");
            $stmt->execute([
                ':sn'     => $nextSn,
                ':name'   => $name,
                ':passwd' => $password,
                ':law'    => $law,
            ]);

            $this->accountUserJson(true, $this->accountUserText('account_new_success', 'New Account success.'));
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

            $this->accountUserValidateText($oldName, $this->accountUserText('account_old_username', 'Old username'));

            $oldNameLower = strtolower($oldName);
            $nameLower = strtolower($name);

            // admin / guest cannot be deleted or renamed, but their password can be edited.
            // kls can be opened for viewing, but saving is blocked.
            if ($this->accountUserIsProtectedName($oldName)) {
                if ($oldNameLower === 'kls') {
                    throw new Exception($this->accountUserText('account_protected_save_action', 'This account is protected and cannot be saved.'));
                }

                if ($nameLower !== $oldNameLower) {
                    throw new Exception($this->accountUserText($oldNameLower === 'guest' ? 'account_guest_rename_blocked' : 'account_admin_rename_blocked', $oldNameLower . ' account name cannot be changed.'));
                }
            } elseif ($this->accountUserIsProtectedName($name)) {
                throw new Exception($this->accountUserText('account_protected_rename_blocked', 'This built-in account name cannot be changed.'));
            }

            if ($oldName !== $name) {
                $this->accountUserValidateUsername($name, $this->accountUserText('account_username', 'Username'));
            } else {
                // 允許既有 admin/user1 等舊帳號在未改名時繼續修改密碼。
                $this->accountUserValidateText($name, $this->accountUserText('account_username', 'Username'));
            }

            if ($password !== '') {
                $this->accountUserValidatePassword($password, $this->accountUserText('account_password', 'Password'));
                if ($password !== $confirm) {
                    throw new Exception($this->accountUserText('account_confirm_diff', 'Confirm password is different.'));
                }
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $stmt = $db->prepare("SELECT COUNT(*) FROM `user` WHERE name = :name");
            $stmt->execute([':name' => $oldName]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new Exception($this->accountUserText('account_not_found', 'Account not found.'));
            }

            if ($oldName !== $name) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM `user` WHERE name = :name");
                $stmt->execute([':name' => $name]);
                if ((int)$stmt->fetchColumn() > 0) {
                    throw new Exception($this->accountUserText('account_username_exists', 'Username already exists.'));
                }
            }

            if ($oldNameLower === 'admin' || $oldNameLower === 'guest') {
                // admin / guest cannot be renamed or deleted, but password editing is allowed.
                if ($password !== '') {
                    $stmt = $db->prepare("UPDATE `user` SET passwd = :passwd, law = :law WHERE LOWER(name) = :old_name_lower");
                    $stmt->execute([
                        ':passwd' => $password,
                        ':law' => $law,
                        ':old_name_lower' => $oldNameLower,
                    ]);
                }
            } elseif ($password === '') {
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

            $this->accountUserJson(true, $this->accountUserText('account_edit_success', 'Edit Account success.'));
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }

    public function account_user_delete(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $name = $this->accountUserClean($_POST['username'] ?? '');
            $this->accountUserValidateText($name, $this->accountUserText('account_username', 'Username'));
            if ($this->accountUserIsProtectedName($name)) {
                throw new Exception($this->accountUserText('account_protected_action', 'This account is protected and cannot be deleted.'));
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $count = (int)$db->query("SELECT COUNT(*) FROM `user`")->fetchColumn();
            if ($count <= 1) {
                throw new Exception($this->accountUserText('account_last_delete_error', 'Cannot delete the last account.'));
            }

            $stmt = $db->prepare("DELETE FROM `user` WHERE name = :name");
            $stmt->execute([':name' => $name]);

            if ($stmt->rowCount() <= 0) {
                throw new Exception($this->accountUserText('account_not_found', 'Account not found.'));
            }

            $this->accountUserJson(true, $this->accountUserText('account_delete_account_success', 'Delete Account success.'));
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }


    
    /**
     * Read iDAS operation audit logs without depending on SettingModel.
     * This keeps Data/AuditLog working even when the Setting model has not
     * been updated with getOperationAuditLogs().
     */
    private function getOperationAuditLogs(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));

        $info = $this->openOperationAuditDb();
        if (!$info) {
            return [];
        }

        /** @var PDO $db */
        $db = $info['db'];
        $table = $info['table'];
        $columns = $this->operationAuditColumns($db, $table);

        $orderColumn = $this->operationAuditFirstExistingColumn($columns, [
            'log_id', 'id', 'created_at', 'time', 'data_time', 'rowid'
        ]);

        $orderSql = '';
        if ($orderColumn === 'rowid') {
            $orderSql = ' ORDER BY rowid DESC';
        } elseif ($orderColumn !== '') {
            $orderSql = ' ORDER BY ' . $this->operationAuditQuoteIdentifier($orderColumn) . ' DESC';
        }

        $sql = 'SELECT * FROM ' . $this->operationAuditQuoteIdentifier($table) . $orderSql . ' LIMIT ' . (int)$limit;
        $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        $no = 1;
        foreach ($rows as $row) {
            $result[] = $this->normalizeOperationAuditRow($row, $no++);
        }

        return $result;
    }

    private function getOperationAuditLogDetail(int $logId): ?array
    {
        $info = $this->openOperationAuditDb();
        if (!$info) {
            return null;
        }

        /** @var PDO $db */
        $db = $info['db'];
        $table = $info['table'];
        $columns = $this->operationAuditColumns($db, $table);
        $idColumn = $this->operationAuditFirstExistingColumn($columns, ['log_id', 'id']);

        if ($idColumn === '') {
            return null;
        }

        $stmt = $db->prepare(
            'SELECT * FROM ' . $this->operationAuditQuoteIdentifier($table) .
            ' WHERE ' . $this->operationAuditQuoteIdentifier($idColumn) . ' = :id LIMIT 1'
        );
        $stmt->execute([':id' => $logId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->normalizeOperationAuditRow($row, 1) : null;
    }

    private function openOperationAuditDb(): ?array
    {
       $dbPaths = [
            '/var/www/html/database/das.db',
            '/var/www/html/database/KLS_NTCS_IDAS.Lin',
            '/var/www/html/database/ntcs_device_IDAS.db',
            '/var/www/html/database/ntcs_data.db',
            '/home/kls/NTCS7/KLS_NTCS.Lin',
            '/home/kls/NTCS7/ntcs_data.db',
            __DIR__ . '/../../../database/das.db',
            __DIR__ . '/../../../database/KLS_NTCS_IDAS.Lin',
            __DIR__ . '/../../../database/operation_audit_log.db',
        ];

        $preferredTables = [
            'operation_audit_log',
            'operation_audit_logs',
            'audit_log',
            'audit_logs',
            'idas_operation_audit_log',
        ];

        foreach ($dbPaths as $path) {
            if (!is_file($path) || !is_readable($path)) {
                continue;
            }

            try {
                $db = new PDO('sqlite:' . $path);
                $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

                $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
                if (!is_array($tables) || empty($tables)) {
                    continue;
                }

                foreach ($preferredTables as $name) {
                    if (in_array($name, $tables, true)) {
                        return ['db' => $db, 'table' => $name, 'path' => $path];
                    }
                }

                foreach ($tables as $table) {
                    $lower = strtolower((string)$table);
                    if (strpos($lower, 'audit') !== false && strpos($lower, 'log') !== false) {
                        return ['db' => $db, 'table' => (string)$table, 'path' => $path];
                    }
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return null;
    }

    private function operationAuditColumns(PDO $db, string $table): array
    {
        $stmt = $db->query('PRAGMA table_info(' . $this->operationAuditQuoteIdentifier($table) . ')');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $columns = [];

        foreach ($rows as $row) {
            if (isset($row['name']) && $row['name'] !== '') {
                $columns[] = (string)$row['name'];
            }
        }

        return $columns;
    }

    private function operationAuditFirstExistingColumn(array $columns, array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if ($candidate === 'rowid') {
                return 'rowid';
            }

            foreach ($columns as $column) {
                if (strtolower($column) === strtolower($candidate)) {
                    return $column;
                }
            }
        }

        return '';
    }

    private function operationAuditQuoteIdentifier(string $name): string
    {
        return '"' . str_replace('"', '""', $name) . '"';
    }

    private function operationAuditPick(array $row, array $keys, $default = '')
    {
        foreach ($keys as $key) {
            foreach ($row as $actualKey => $value) {
                if (strtolower((string)$actualKey) === strtolower((string)$key)) {
                    return $value;
                }
            }
        }

        return $default;
    }

    private function normalizeOperationAuditRow(array $row, int $fallbackId = 0): array
    {
        $logId = $this->operationAuditPick($row, ['log_id', 'id', 'sn', 'rowid'], $fallbackId);

        return array_merge($row, [
            'log_id' => $logId,
            'id' => $this->operationAuditPick($row, ['id', 'log_id', 'sn'], $logId),
            'created_at' => $this->operationAuditPick($row, ['created_at', 'time', 'data_time', 'datetime', 'date_time'], ''),
            'operator' => $this->operationAuditPick($row, ['operator', 'user_name', 'username', 'user', 'user_id'], ''),
            'user_id' => $this->operationAuditPick($row, ['user_id', 'operator', 'user_name', 'username', 'user'], ''),
            'client_ip' => $this->operationAuditPick($row, ['client_ip', 'ip'], ''),
            'device_id' => $this->operationAuditPick($row, ['device_id'], ''),
            'module' => $this->operationAuditPick($row, ['module', 'category'], ''),
            'action' => $this->operationAuditPick($row, ['action', 'operation'], ''),
            'status' => $this->operationAuditPick($row, ['status', 'level'], ''),
            'job_id' => $this->operationAuditPick($row, ['job_id'], ''),
            'seq_id' => $this->operationAuditPick($row, ['seq_id', 'sequence_id'], ''),
            'step_id' => $this->operationAuditPick($row, ['step_id'], ''),
            'source_job_id' => $this->operationAuditPick($row, ['source_job_id'], ''),
            'source_seq_id' => $this->operationAuditPick($row, ['source_seq_id'], ''),
            'source_step_id' => $this->operationAuditPick($row, ['source_step_id'], ''),
            'target_job_id' => $this->operationAuditPick($row, ['target_job_id'], ''),
            'target_seq_id' => $this->operationAuditPick($row, ['target_seq_id'], ''),
            'target_step_id' => $this->operationAuditPick($row, ['target_step_id'], ''),
            'target' => $this->operationAuditPick($row, ['target', 'object', 'title'], ''),
            'title' => $this->operationAuditPick($row, ['title'], ''),
            'message' => $this->operationAuditPick($row, ['message', 'msg', 'target', 'title'], ''),
            'before_json' => $this->operationAuditPick($row, ['before_json'], ''),
            'after_json' => $this->operationAuditPick($row, ['after_json'], ''),
            'order_before_json' => $this->operationAuditPick($row, ['order_before_json'], ''),
            'order_after_json' => $this->operationAuditPick($row, ['order_after_json'], ''),
            'request_json' => $this->operationAuditPick($row, ['request_json'], ''),
        ]);
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
                // APP 監控資料來源固定讀 ntcs_log.csv。
                // CSV 欄位格式：date,time,user,action,module,target
                $rows = $this->getAppOperationLogsFromCsv($limit);
            } else {
                $rows = $this->getOperationAuditLogs($limit);
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

    /**
     * 讀取 APP 操作紀錄 CSV。
     *
     * ntcs_log.csv 格式：
     * 0 date   例：2026-05-07
     * 1 time   例：05:29.39.9
     * 2 user   例：guest
     * 3 action 例：Add / Edit
     * 4 module 例：Job Editor / Sequence Management
     * 5 target 例：Job ID: 1; Seq ID: 2
     */
    private function getAppOperationLogsFromCsv(int $limit = 100): array
    {
        $csvPath = $this->getAppOperationLogCsvPath();
        if ($csvPath === '' || !is_file($csvPath) || !is_readable($csvPath)) {
            return [];
        }

        $rows = [];
        $fp = fopen($csvPath, 'r');
        if (!$fp) {
            return [];
        }

        $lineNo = 0;
        while (($cols = fgetcsv($fp)) !== false) {
            $lineNo++;

            // 跳過空行或欄位不足的資料
            if (!is_array($cols) || count($cols) < 5) {
                continue;
            }

            $date   = trim((string)($cols[0] ?? ''));
            $time   = $this->normalizeAppLogTime(trim((string)($cols[1] ?? '')));
            $user   = trim((string)($cols[2] ?? ''));
            $action = trim((string)($cols[3] ?? ''));
            $module = trim((string)($cols[4] ?? ''));
            $target = trim((string)($cols[5] ?? ''));

            // 避免完全空資料進入前端
            if ($date === '' && $time === '' && $user === '' && $action === '' && $module === '' && $target === '') {
                continue;
            }

            $createdAt = trim($date . ' ' . $time);
            $messageParts = array_filter([$user, $action, $module, $target], function ($v) {
                return trim((string)$v) !== '';
            });

            $rows[] = [
                // 保留多組 key，避免前端目前用不同名稱取值時顯示空白
                'id'         => $lineNo,
                'log_id'     => $lineNo,
                'source'     => 'app',
                'created_at' => $createdAt,
                'time'       => $createdAt,
                'date'       => $date,
                // 前端 operation_audit_log.php 主要讀 operator / user_id，
                // 也保留 user_name / username / user，避免不同版本 View 顯示空白。
                'operator'   => $user,
                'user_id'    => $user,
                'user_name'  => $user,
                'username'   => $user,
                'user'       => $user,
                'module'     => $module,
                'action'     => $action,
                'target'     => $target,
                'level'      => 'INFO',
                // 用 INFO 交給前端依語系轉成「資訊 / INFO」，不要後端固定中文。
                'status'     => 'INFO',
                // Message 不再重複放 user，因為 user 已有獨立欄位。
                'message'    => implode(' | ', array_filter([$action, $module, $target], function ($v) {
                    return trim((string)$v) !== '';
                })),
                'raw'        => $cols,
            ];
        }
        fclose($fp);

        // CSV 通常舊資料在上、新資料在下；前端監控要顯示最新在最上面
        $rows = array_reverse($rows);

        return array_slice($rows, 0, $limit);
    }

    /**
     * APP 操作紀錄檔路徑。
     */
    private function getAppOperationLogCsvPath(): string
    {
        $paths = [
            '/home/kls/NTCS/ntcs_log.csv',
            '/mnt/ramdisk/ftp/ntcs_log.csv',
            '/var/www/html/database/ntcs_log.csv',
        ];

        foreach ($paths as $path) {
            if (is_file($path) && is_readable($path)) {
                return $path;
            }
        }

        // 回傳主要路徑，方便後續 debug 知道預期位置
        return $paths[0];
    }

    /**
     * 將 APP CSV 時間 05:29.39.9 轉成 05:29:39.9，顯示較清楚。
     */
    private function normalizeAppLogTime(string $time): string
    {
        if (preg_match('/^(\d{1,2}):(\d{2})\.(\d{2})(\.\d+)?$/', $time, $m)) {
            return sprintf('%02d:%s:%s%s', (int)$m[1], $m[2], $m[3], $m[4] ?? '');
        }

        return $time;
    }

    public function operation_audit_log_detail(): void
    {
        try {
            $this->accountUserRequireAdmin();

            $logId = isset($_POST['log_id']) ? (int)$_POST['log_id'] : 0;
            if ($logId <= 0) {
                throw new Exception('Invalid log_id.');
            }

            $row = $this->getOperationAuditLogDetail($logId);
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
}
