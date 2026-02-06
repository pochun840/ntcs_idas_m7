<?php

class Controller
{
    // 載入 model
    public function model($model)
    {
        require_once '../app/models/' . $model . '.php';
        return new $model();
    }


    // 載入 view
    // 其中 view 可能有需要從 Controller 帶過去的資料，故多了 $data 陣列作為第二個參數
    public function view($view, array $data = []){
        
        // ✅ 防 session 炸裂
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        // ✅ 防 Errors Controller 沒 language_auto
        if (method_exists($this, 'language_auto')) {
            $this->language_auto();
        }

        // 語系
        $language = ["language" => $_SESSION['language'] ?? 'en-us'];
        $data = array_merge($data, $language);

        // 權限
        $privilege = ["privilege" => $_SESSION['privilege'] ?? 0];
        $data = array_merge($data, $privilege);

        $viewFile = '../app/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            header("HTTP/1.1 404 Not Found");
            echo "View not found: {$view}";
            exit;
        }

        if (file_exists('../app/language/' . $data['language'] . '.php')) {
            require_once '../app/language/' . $data['language'] . '.php';
        } else {
            require_once '../app/language/en-us.php';
        }

        require_once '../app/views/inc/header.php';
        require_once $viewFile;
        require_once '../app/views/inc/footer.php';
    }

    public function language_auto($value='')
    {
        // 如果$_SESSION['language'] 未設定 或為空 就從瀏覽器訊息帶入
        if( !isset($_SESSION['language']) || $_SESSION['language'] == '' ){
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 4);
            if (preg_match("/zh-cn/i", $lang)){
                $_SESSION['language'] = 'zh-cn';
            }else if(preg_match("/zh-tw/i", $lang)){
                $_SESSION['language'] = 'zh-tw';
            }else if(preg_match("/en/i", $lang)){
                $_SESSION['language'] = 'en-us';
            }else{//預設
                $_SESSION['language'] = 'en-us';
            }
        }

        setcookie('language', $_SESSION['language'], time() + (365 * 24 * 60 * 60), '/');
        
    }


    public function logMessage($message) {
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[$timestamp] $message\n";
       
    }

    public function isMobileCheck($value='')
    {
        //Detect special conditions devices
        $iPod = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        if(stripos($_SERVER['HTTP_USER_AGENT'],"Android") && stripos($_SERVER['HTTP_USER_AGENT'],"mobile")){
            $Android = true;
        }else if(stripos($_SERVER['HTTP_USER_AGENT'],"Android")){
            $Android = false;
            $AndroidTablet = true;
        }else{
            $Android = false;
            $AndroidTablet = false;
        }
        $webOS = stripos($_SERVER['HTTP_USER_AGENT'],"webOS");
        $BlackBerry = stripos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
        $RimTablet= stripos($_SERVER['HTTP_USER_AGENT'],"RIM Tablet");
        //do something with this information
        if( $iPod || $iPhone || $iPad || $Android || $AndroidTablet || $webOS || $BlackBerry || $RimTablet){
            return true;
        }else{
            return false;
        }
    }



    //權限驗證function
    public function LoginCheck($value='')
    {
        if( PHP_OS_FAMILY == 'Linux'){
            $con_db = new PDO('sqlite:/var/www/html/database/das.db'); 
        }else{
            $con_db = new PDO('sqlite:../data.db'); 
        }

        $con_db->exec('set names utf-8'); 
        $sql = 'SELECT * FROM operator';
        $statement = $con_db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['operator_loginflag'];        
    }

    

    //取得tcscon device table資訊
    public function Device_Info()
    {
        /*try {
            if (PHP_OS_FAMILY === 'Linux') {
                $db_path = '/var/www/html/database/data_device.db';
            } else {
                $db_path = '../data_device.db';
            }

            if (!file_exists($db_path)) {
                throw new Exception("❌ Database file not found: $db_path");
            }

            $con_db = new PDO('sqlite:' . $db_path);
            $con_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $con_db->exec('PRAGMA encoding = "UTF-8"');

            $sql = 'SELECT * FROM device';

            $statement = $con_db->prepare($sql);
            if (!$statement) {
                $errorInfo = $con_db->errorInfo();
                throw new Exception("❌ SQL prepare failed: " . $errorInfo[2]);
            }

            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return $row;
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo $e->getMessage(); // 或回傳空陣列 return [];
            return null;
        }*/
    }



    public function ntcs_device_db_sysnc($forceRefresh = false){

        // 路徑集中放這裡
        $srcController = '/home/kls/NTCS7/ntcs_device.db';            // 控制器端 ntcs_device.db
        $tempDbPath    = '/var/www/html/database/ntcs_device_temp.db';// iDAS 暫存
        $idasDbPath    = '/var/www/html/database/ntcs_device_IDAS.db';// iDAS 正式用的 device DB

        // === 0) 先用 cookie 快取，避免每次都重跑整個流程 ===
        $cacheTtl = 10; // 秒

        if (
            !$forceRefresh &&
            isset($_COOKIE['temp_device_id'], $_COOKIE['temp_device_id_ts'])
        ) {
            $cid = (int)$_COOKIE['temp_device_id'];
            $ts  = (int)$_COOKIE['temp_device_id_ts'];

            if ($cid >= 1 && $cid <= 255 && $ts > 0 && (time() - $ts) < $cacheTtl) {
                // 在快取有效時間內 → 直接回傳，完全不打 DB / Modbus
                return $cid;
            }
        }

        $finalDeviceId = null;   // 最後決定「實際 Modbus 通訊的 device_id」
        $modbusOk      = false;  // 有沒有成功打到 Modbus

        // -------------------------------------------------------
        // 1) 先用「目前 iDAS DB」裡的 device_id 試著打 Modbus（不先 sync）
        // -------------------------------------------------------
        $deviceIdFromIdas = $this->readDeviceIdFromDb($idasDbPath);

        if ($deviceIdFromIdas !== null) {
            $check1 = $this->idas_check($deviceIdFromIdas);

            if (empty($check1['error']) && $check1['result'] !== null) {
                $modbusOk      = true;
                $finalDeviceId = $deviceIdFromIdas;
            }
        }

        // -------------------------------------------------------
        // 2) 若第一步 Modbus 失敗 → 才執行 sync_db + 用 temp DB 再試一次
        // -------------------------------------------------------
        if (!$modbusOk) {
            // 2-1) 控制器 → temp（這邊才做 sync，平常有通就不會跑到這裡）
            $this->sync_db($srcController, $tempDbPath);

            // 2-2) 從 temp DB 讀 device_id
            $deviceIdFromTemp = $this->readDeviceIdFromDb($tempDbPath);

            if ($deviceIdFromTemp !== null) {
                $check2 = $this->idas_check($deviceIdFromTemp);

                if (empty($check2['error']) && $check2['result'] !== null) {
                    $modbusOk      = true;
                    $finalDeviceId = $deviceIdFromTemp;

                    // 2-3) ✅ 不再整個 copy DB
                    //      改成只同步 ntcs_device_test 這張表的內容
                    $this->syncTempDeviceIdToIdas();  // ★ 關鍵在這行
                }
            }
        }

        // -------------------------------------------------------
        // 3) 將「實際 Modbus 通訊的 device_id」寫入 cookie（如果有找到）
        // -------------------------------------------------------
        if ($finalDeviceId !== null) {
            $exp = time() + 86400 * 30; // 30 天
            setcookie('temp_device_id', (string)$finalDeviceId, $exp, '/', '', false, true);
            setcookie('temp_device_id_ts', (string)time(), $exp, '/', '', false, true);

            $_COOKIE['temp_device_id']    = (string)$finalDeviceId;
            $_COOKIE['temp_device_id_ts'] = (string)time();
        }

        return $finalDeviceId;
    }



    /**
     * 小工具：從指定 SQLite DB 抓 ntcs_device_test.device_id
     * 讀不到 / DB 不存在 → 回傳 null
     */
    private function readDeviceIdFromDb($dbPath){

        if (!file_exists($dbPath)) {
            // 不寫 log 也可以，看你要不要
            // error_log('[readDeviceIdFromDb] DB file not found: ' . $dbPath);
            return null;
        }

        try {
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql  = "SELECT device_id FROM ntcs_device_test LIMIT 1";
            $stmt = $pdo->query($sql);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row && isset($row['device_id'])) {
                $id = (int)$row['device_id'];
                if ($id >= 1 && $id <= 255) {
                    return $id;
                }
            }
        } catch (Exception $e) {
            //error_log('[readDeviceIdFromDb] Read device_id failed: ' . $e->getMessage());
        }

        return null;
    }

    
    /**
     * 將 /var/www/html/database/ntcs_device_temp.db 的 device_id
     * 寫入到 /var/www/html/database/ntcs_device_IDAS.db
     *
     * 前提：table ntcs_device_test 理論上只應有一筆資料
     *
     * 規則：
     *   - 若 IDAS DB 已有資料：只 UPDATE device_id 欄位，其餘欄位不異動
     *   - 若 IDAS DB 沒有資料：INSERT 一筆新的，只指定 device_id，其餘欄位走預設值
     *
     * 回傳：
     *   - 成功：對應的 device_id (int)
     *   - 失敗：null
     */
    public function syncTempDeviceIdToIdas(){
        
        $tempDbPath = '/var/www/html/database/ntcs_device_temp.db';
        $idasDbPath = '/var/www/html/database/ntcs_device_IDAS.db';

        // 1) 基本檔案存在檢查
        if (!file_exists($tempDbPath)) {
            //error_log('[syncTempDeviceIdToIdas] temp DB not found: ' . $tempDbPath);
            return null;
        }
        if (!file_exists($idasDbPath)) {
            //error_log('[syncTempDeviceIdToIdas] IDAS DB not found: ' . $idasDbPath);
            return null;
        }

        // 2) 從 temp DB 讀 device_id（共用 readDeviceIdFromDb）
        $deviceId = $this->readDeviceIdFromDb($tempDbPath);

        if ($deviceId === null) {
            //error_log('[syncTempDeviceIdToIdas] No valid device_id found in temp DB.');
            return null;
        }

        // 保險：限制在 1~255
        if ($deviceId < 1 || $deviceId > 255) {
            //error_log('[syncTempDeviceIdToIdas] Invalid device_id range: ' . $deviceId);
            return null;
        }

        // 3) 寫回 IDAS DB
        try {
            $pdoIdas = new PDO('sqlite:' . $idasDbPath);
            $pdoIdas->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 3-1) 檢查目前 IDAS 的 ntcs_device_test 是否已有資料
            $sqlCheck  = "SELECT COUNT(*) AS cnt FROM ntcs_device_test";
            $stmtCheck = $pdoIdas->query($sqlCheck);
            $rowCheck  = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            $rowCount  = isset($rowCheck['cnt']) ? (int)$rowCheck['cnt'] : 0;

            if ($rowCount > 0) {
                // --- 情境一：IDAS 原本就有資料 → 只更新 device_id，其它欄位不動 ---
                $sqlUpdate = "UPDATE ntcs_device_test SET device_id = :device_id";
                $stmtUpd   = $pdoIdas->prepare($sqlUpdate);
                $stmtUpd->execute([':device_id' => $deviceId]);

                // 若超過一筆，寫個 log 提醒（理論上只應有一筆）
                if ($rowCount > 1) {
                    //error_log('[syncTempDeviceIdToIdas] Warning: ntcs_device_test has ' . $rowCount . ' rows, all device_id updated.');
                }

            } else {
                // --- 情境二：IDAS 完全沒資料 → 插入一筆新的，只指定 device_id ---
                $sqlInsert = "INSERT INTO ntcs_device_test (device_id) VALUES (:device_id)";
                $stmtIns   = $pdoIdas->prepare($sqlInsert);
                $stmtIns->execute([':device_id' => $deviceId]);
            }

            @chmod($idasDbPath, 0777);

            //error_log('[syncTempDeviceIdToIdas] Synced device_id=' . $deviceId . ' from temp DB to IDAS DB (update-only for existing row).');

            return $deviceId;

        } catch (Exception $e) {
           //error_log('[syncTempDeviceIdToIdas] Write to IDAS DB failed: ' . $e->getMessage());
            return null;
        }
    }






    //判斷控制器的登入登出
    public function idas_check($device_id){

        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;  // 使用定義的常數

        $port = 502;
        $startAddress = 29002;
        $quantity = 1;

        $response = ['result' => null, 'error' => ''];

        // 驗證 IP 格式
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $response['error'] = "無效的 IP 位址：$ip";
            return $response; 
        }

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($device_id, $startAddress, $quantity);

            $response['result'] = $data[1] ?? null;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }

        return $response;
    }


    public function get_tools_version($unitId){
        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;  // 使用定義的常數
        $port = 502;
        //$unitId = 0;
        $startAddress = 29003;
        $quantity = 1;

        $response = ['result' => null, 'error' => ''];

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            $response['result'] = $data[1] ?? null;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }

        return  $response['result'];
    }
    


    public function get_firmware_version($unitId){
        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;  // 使用定義的常數
        $port = 502;
        //$unitId = 0;
        $startAddress = 29004;
        $quantity = 1;

        $response = ['result' => null, 'error' => ''];

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            $response['result'] = $data[1] ?? null;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }

        return  $response['result'];
    }


    public function get_db_sync($unitId){

        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;  // 使用定義的常數
        $port = 502;
        //$unitId = 0;
        $startAddress = 29006;
        $quantity = 1;

        $response = ['result' => null, 'error' => ''];

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            $response['result'] = $data[1] ?? null;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }

        return json_encode($response);    


    }


    
    public function get_operation_id(){

        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;  // 使用定義的常數
        $port = 502;
        $unitId = 0;
        $startAddress = 4165;
        $quantity = 2;

        $response = ['result' => null, 'error' => ''];

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            $response['result'] = $data[1] ?? null;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }

        return json_encode($response);    
    }


    public function get_data_info(){
        
        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;  // 使用定義的常數
        $port = 502;
        $unitId = 0;
        $startAddress = 4097;
        $quantity = 64;

        $response = ['result' => null, 'error' => ''];

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            $response['result'] = $data[1] ?? null;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }

        echo json_encode($response);
    }


    //起子sn
    public function get_tools_sn($unitId) {

        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;
        $port = 502;
        //$unitId = 0;
        $startAddress = 4122;  // 字串起始暫存器
        $quantity = 10;        // 讀 10 格＝20 bytes

        // 小工具：把 16-bit 暫存器陣列轉成 ASCII
        $regsToAscii = function(array $regs, string $endian = 'BE', bool $stripNul = true, bool $printableOnly = true): string {
            $out = '';
            foreach ($regs as $n) {
                $n  = (int)$n & 0xFFFF;
                $hi = ($n >> 8) & 0xFF;
                $lo =  $n       & 0xFF;
                $bytes = ($endian === 'LE') ? [$lo, $hi] : [$hi, $lo];
                foreach ($bytes as $b) {
                    if ($stripNul && $b === 0x00) continue;
                    if ($printableOnly && ($b < 0x20 || $b > 0x7E)) continue;
                    $out .= chr($b);
                }
            }
            return $out;
        };

        $response = [
            'ok'             => false,
            'error'          => '',
            'unitId'         => $unitId,
            'start'          => $startAddress,
            'quantity'       => $quantity,
            'raw_registers'  => [],
            'ascii_be'       => '',
            'ascii_le'       => '',
            'model'          => '',
        ];

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            if (!is_array($data) || empty($data)) {
                throw new Exception('No data returned from Modbus');
            }

            // 轉成 int 陣列（保底）
            $regs = array_map('intval', $data);

            // 兩種端序的字串
            $asciiBE = $regsToAscii($regs, 'BE', true, true);
            $asciiLE = $regsToAscii($regs, 'LE', true, true);

            // 以 Big-Endian 為主（多數裝置字串是這樣），也可換成 $asciiLE
            $model = $asciiBE;

            $response['ok']            = true;
            $response['raw_registers'] = $regs;
            $response['ascii_be']      = $asciiBE;
            $response['ascii_le']      = $asciiLE;
            $response['model']         = $model;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }
        
        return $response;
    }


    public function get_controller_sn(){

        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;
        $port = 502;
        $unitId = 0;
        $startAddress = 4102;  // 字串起始暫存器
        $quantity = 10;        // 讀 10 格＝20 bytes

        // 小工具：把 16-bit 暫存器陣列轉成 ASCII
        $regsToAscii = function(array $regs, string $endian = 'BE', bool $stripNul = true, bool $printableOnly = true): string {
            $out = '';
            foreach ($regs as $n) {
                $n  = (int)$n & 0xFFFF;
                $hi = ($n >> 8) & 0xFF;
                $lo =  $n       & 0xFF;
                $bytes = ($endian === 'LE') ? [$lo, $hi] : [$hi, $lo];
                foreach ($bytes as $b) {
                    if ($stripNul && $b === 0x00) continue;
                    if ($printableOnly && ($b < 0x20 || $b > 0x7E)) continue;
                    $out .= chr($b);
                }
            }
            return $out;
        };

        $response = [
            'ok'             => false,
            'error'          => '',
            'unitId'         => $unitId,
            'start'          => $startAddress,
            'quantity'       => $quantity,
            'raw_registers'  => [],
            'ascii_be'       => '',
            'ascii_le'       => '',
            'model'          => '',
        ];

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            if (!is_array($data) || empty($data)) {
                throw new Exception('No data returned from Modbus');
            }

            // 轉成 int 陣列（保底）
            $regs = array_map('intval', $data);

            // 兩種端序的字串
            $asciiBE = $regsToAscii($regs, 'BE', true, true);
            $asciiLE = $regsToAscii($regs, 'LE', true, true);

            // 以 Big-Endian 為主（多數裝置字串是這樣），也可換成 $asciiLE
            $model = $asciiBE;

            $response['ok']            = true;
            $response['raw_registers'] = $regs;
            $response['ascii_be']      = $asciiBE;
            $response['ascii_le']      = $asciiLE;
            $response['model']         = $model;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }
        
        return $response;

    }

    //起子型號
    public function get_tools_type($unitId) {

        require_once '../app/config/config.php';  
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;
        $port = 502;
        //$unitId = 0;
        $startAddress = 4112;  // 字串起始暫存器
        $quantity = 10;        // 讀 10 格＝20 bytes

        // 小工具：把 16-bit 暫存器陣列轉成 ASCII
        $regsToAscii = function(array $regs, string $endian = 'BE', bool $stripNul = true, bool $printableOnly = true): string {
            $out = '';
            foreach ($regs as $n) {
                $n  = (int)$n & 0xFFFF;
                $hi = ($n >> 8) & 0xFF;
                $lo =  $n       & 0xFF;
                $bytes = ($endian === 'LE') ? [$lo, $hi] : [$hi, $lo];
                foreach ($bytes as $b) {
                    if ($stripNul && $b === 0x00) continue;
                    if ($printableOnly && ($b < 0x20 || $b > 0x7E)) continue;
                    $out .= chr($b);
                }
            }
            return $out;
        };

        $response = [
            'ok'             => false,
            'error'          => '',
            'unitId'         => $unitId,
            'start'          => $startAddress,
            'quantity'       => $quantity,
            'raw_registers'  => [],
            'ascii_be'       => '',
            'ascii_le'       => '',
            'model'          => '',
        ];

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            if (!is_array($data) || empty($data)) {
                throw new Exception('No data returned from Modbus');
            }

            // 轉成 int 陣列（保底）
            $regs = array_map('intval', $data);

            // 兩種端序的字串
            $asciiBE = $regsToAscii($regs, 'BE', true, true);
            $asciiLE = $regsToAscii($regs, 'LE', true, true);

            // 以 Big-Endian 為主（多數裝置字串是這樣），也可換成 $asciiLE
            $model = $asciiBE;

            $response['ok']            = true;
            $response['raw_registers'] = $regs;
            $response['ascii_be']      = $asciiBE;
            $response['ascii_le']      = $asciiLE;
            $response['model']         = $model;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }
        
        return $response;
    }


    public function ntcs_data_db_sysnc() {
        $this->sync_db(
            '/home/kls/NTCS7/ntcs_data.db',
            '/var/www/html/database/ntcs_data.db'
        );

        $this->sync_ntcs_tool_data();
    }




    public function ntcs_device_db_load() {
        $this->sync_db(
            '/home/kls/NTCS7/ntcs_device.db',
            '/var/www/html/database/ntcs_device_IDAS.db'
        );
    }

    private function sync_db($src, $dst) {
        if (!file_exists($src)) {
            return;
        }

        $src_mtime = filemtime($src);
        $dst_mtime = file_exists($dst) ? filemtime($dst) : 0;

        if ($src_mtime > $dst_mtime) {
            if (copy($src, $dst)) {
                chmod($dst, 0777);
            }
        }
    }


    public function sync_ntcs_tool_data() {
        $srcDB = '/home/kls/NTCS7/ntcs_device.db';
        $dstDB = '/var/www/html/database/ntcs_device_IDAS.db';

        if (!file_exists($srcDB) || !file_exists($dstDB)) {
            return;
        }

        try {
            // 1) 連到來源 DB
            $src = new PDO("sqlite:" . $srcDB);
            $src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1a) 取 device_id 和 device_version
            $deviceInfo = $src->query("SELECT device_id, device_version FROM ntcs_device_test")->fetchAll(PDO::FETCH_ASSOC);

            // 1b) 取 ntcs_tool_test 全表資料
            $toolData = $src->query("SELECT * FROM ntcs_tool_test")->fetchAll(PDO::FETCH_ASSOC);

            // 關閉來源 DB
            $src = null;

            // 2) 連到目標 DB
            $dst = new PDO("sqlite:" . $dstDB);
            $dst->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 2a) 覆蓋 ntcs_tool_test
            $dst->exec("DELETE FROM ntcs_tool_test");

            if (!empty($toolData)) {
                $columns = array_keys($toolData[0]);
                $colList = implode(',', $columns);
                $placeholders = ':' . implode(', :', $columns);
                $stmt = $dst->prepare("INSERT INTO ntcs_tool_test ($colList) VALUES ($placeholders)");

                $dst->beginTransaction();
                foreach ($toolData as $row) {
                    foreach ($row as $key => $val) {
                        $stmt->bindValue(":$key", $val);
                    }
                    $stmt->execute();
                }
                $dst->commit();
            }

            // 2b) 逐筆更新 ntcs_device_test 的 device_version（依 device_id）
            if (!empty($deviceInfo)) {
                $upd = $dst->prepare("UPDATE ntcs_device_test SET device_version = :ver WHERE device_id = :id");
                foreach ($deviceInfo as $row) {
                    $upd->bindValue(':ver', $row['device_version']);
                    $upd->bindValue(':id', $row['device_id']);
                    $upd->execute();
                }
            }

            // 關閉目標 DB
            $dst = null;

        } catch (PDOException $e) {
            //error_log("❌ 資料同步失敗: " . $e->getMessage());
            echo "❌ 資料同步失敗: " . $e->getMessage();
        }
    }


    public function get_success_tools_info(){
        
        try {
            if (PHP_OS_FAMILY === 'Linux') {
                $db_path = '/var/www/html/database/KLS_NTCS_IDAS.Lin';
            } else {
                $db_path = '../KLS_NTCS_IDAS.Lin';
            }

            if (!file_exists($db_path)) {
                throw new Exception("❌ Database file not found: $db_path");
            }

            $con_db = new PDO('sqlite:' . $db_path);
            $con_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $con_db->exec('PRAGMA encoding = "UTF-8"');

            $sql = 'SELECT max_rpm,min_rpm,max_torq,min_torq FROM tools_info';

            $statement = $con_db->prepare($sql);
            if (!$statement) {
                $errorInfo = $con_db->errorInfo();
                throw new Exception("❌ SQL prepare failed: " . $errorInfo[2]);
            }

            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return $row;
        } catch (Exception $e) {
            //error_log($e->getMessage());
            echo $e->getMessage(); // 或回傳空陣列 return [];
            return null;
        }
    }

    public function update_tool_limits_from_tools_info() {
        try {
            // 取得 tools_info 的 max/min 資訊
            $toolsInfo = $this->get_success_tools_info();
            if (!$toolsInfo) {
                throw new Exception("❌ 無法取得 tools_info 資料");
            }

            // 連接 ntcs_tool_test 所在的資料庫
            $db_path = '/var/www/html/database/ntcs_device_IDAS.db';
            if (!file_exists($db_path)) {
                throw new Exception("❌ ntcs_tool_test 資料庫不存在: $db_path");
            }

            $pdo = new PDO('sqlite:' . $db_path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 更新語句：直接更新整張表（假設所有列都需更新）
            $sql = "UPDATE ntcs_tool_test 
                    SET max_torque = :max_torq,
                        min_torque = :min_torq,
                        max_rpm    = :max_rpm,
                        min_rpm    = :min_rpm";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':max_torq' => $toolsInfo['max_torq'],
                ':min_torq' => $toolsInfo['min_torq'],
                ':max_rpm'  => $toolsInfo['max_rpm'],
                ':min_rpm'  => $toolsInfo['min_rpm']
            ]);

            // 成功訊息可留作 log
            // echo "✅ ntcs_tool_test 更新完成";

        } catch (Exception $e) {
            //error_log($e->getMessage());
            echo $e->getMessage();
        }
    }


    public function runAgentInitial()
    {
        // 先檢查 agent 是否有在跑（2 秒 timeout）
        if ($this->isAgentAlive()) {
            return [
                'success' => true,
                'output'  => 'Agent already running (connection OK)'
            ];
        }

        // 超過 2 秒還沒連上 / 連線失敗 → 啟動 agent
        $cmd = 'sudo /usr/bin/php /var/www/html/ntcs_idas/service/agent_initial.php > /dev/null 2>&1 &';
        shell_exec($cmd);

        return [
            'success' => true,
            'output'  => 'Agent start triggered (background mode)'
        ];
    }

    /**
     * 檢查 agent 是否有連線成功
     * 這裡用 TCP 連線測試（請依實際 agent host/port 調整）
     *
     * @return bool true = 連線成功（agent OK）；false = 連線失敗或超時
     */
    private function isAgentAlive(): bool
    {
        // TODO: 依實際 agent 監聽位置修改
        $host    = CONTROLLER_IP;  // 使用定義的常數
        $port    = 9501;     // 例如 WebSocket / agent port
        $timeout = 2;        // 秒

        $errno = 0;
        $errstr = '';

        $start = microtime(true);

        $fp = @fsockopen($host, $port, $errno, $errstr, $timeout);

        if ($fp === false) {
            // 失敗（可能是 timeout 或 port 沒開），視為 agent 不在
            return false;
        }

        // 成功連線 → agent 存活
        fclose($fp);

        // （如果你真的想嚴格判斷「是否超過 2 秒」，也可以這樣）
        $elapsed = microtime(true) - $start;
        if ($elapsed > $timeout) {
            // 雖然有回應，但超過設定 timeout -> 當作失敗
            return false;
        }

        return true;
    }


    public function get_torque_unit_from_controller() {

        $srcDB = '/home/kls/NTCS7/ntcs_device.db';

        if (!file_exists($srcDB)) {
            //error_log("⚠ ntcs_device.db 不存在");
            return null;
        }

        try {
            $src = new PDO("sqlite:" . $srcDB);
            $src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $torque_unit = $src->query("
                SELECT torque_unit 
                FROM ntcs_device_test 
                LIMIT 1
            ")->fetchColumn();

            $src = null;

            if ($torque_unit === false) {
                return null; // 查不到資料
            }

            return (int)$torque_unit;

        } catch (PDOException $e) {
            //error_log("❌ 無法讀取 torque_unit：" . $e->getMessage());
            return null;
        }
    }



    private function pdoDebugSql(string $sql, array $params = []): string{

        // 依 key 長度排序，避免 :id 被 :id2 先替換造成錯誤
        uksort($params, function ($a, $b) {
            return strlen((string)$b) <=> strlen((string)$a);
        });

        foreach ($params as $key => $val) {
            $ph = (strpos((string)$key, ':') === 0) ? (string)$key : ':' . (string)$key;

            if ($val === null) {
                $rep = 'NULL';
            } elseif (is_bool($val)) {
                $rep = $val ? '1' : '0';
            } elseif (is_int($val) || is_float($val)) {
                $rep = (string)$val; // 數字不加引號
            } else {
                // 字串：單引號 escape
                $rep = "'" . str_replace("'", "''", (string)$val) . "'";
            }

            // 用 word boundary 方式替換，避免替到相似字串
            $sql = preg_replace('/' . preg_quote($ph, '/') . '\b/', $rep, $sql);
        }

        return $sql;
    }


    // --------------------------------------------------
    // Debug flag: Tool Spec Sync
    // --------------------------------------------------
    // true  = 啟用 debug log
    // false = 關閉（正式環境建議 false）
    protected const DEBUG_TOOL_SPEC_SYNC = false;

    private function toolSpecDebug(string $reason, array $context = []): void{

        if (!self::DEBUG_TOOL_SPEC_SYNC) {
            return;
        }

        $msg = '[ToolSpecSync] ' . $reason;

        if (!empty($context)) {
            $msg .= ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES);
        }

        //error_log($msg);
    }


    /**
     * runOnceWithFlag
     * - 只負責「鎖」
     * - callback 自己決定要不要同步
     * - callback 成功才寫狀態
     */
    protected function runOnceWithFlag(
        string $flagDir,
        string $flagName,
        callable $callback
    ): void {

        if (PHP_OS_FAMILY !== 'Linux') {
            return;
        }

        $flagDir  = rtrim($flagDir, '/');
        $lockFile = $flagDir . '/' . $flagName . '.lock';

        $fp = @fopen($lockFile, 'c');
        if (!$fp) {
            $this->toolSpecDebug('lock_open_failed', ['file' => $lockFile]);
            return;
        }

        try {
            if (!flock($fp, LOCK_EX | LOCK_NB)) {
                $this->toolSpecDebug('lock_busy');
                return;
            }

            $ok = $callback();

            if ($ok !== true) {
                $this->toolSpecDebug('callback_return_false');
                return;
            }

            $this->toolSpecDebug('sync_success');

        } catch (Throwable $e) {
            $this->toolSpecDebug('exception', ['msg' => $e->getMessage()]);
        } finally {
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
    }



    public function check_tools_info(): bool{
        // ========= 可調參數 =========
        $STABLE_REQUIRED = 1;   // 只需要 1 次穩定
        $MIN_STABLE_SEC  = 5;   // ⭐ 至少穩定存在 5 秒（避免半初始化）
        // ===========================

        if (PHP_OS_FAMILY !== 'Linux') {
            $this->toolSpecDebug('skip_non_linux');
            return false;
        }

        $srcDb    = '/home/kls/NTCS7/ntcs_device.db';
        $destDb   = '/var/www/html/database/ntcs_device_IDAS.db';
        $stateFn  = '/var/www/html/database/.tool_spec_sync.json';
        $stableFn = '/var/www/html/database/.tool_spec_stable.json';

        if (!is_file($srcDb) || !is_file($destDb)) {
            $this->toolSpecDebug('db_file_missing', [
                'src_exists'  => is_file($srcDb),
                'dest_exists' => is_file($destDb),
            ]);
            return false;
        }

        try {
            /* =====================================================
            * 1) 讀取 controller DB
            * ===================================================== */
            $srcPdo = new PDO('sqlite:' . $srcDb, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $src = $srcPdo->query("
                SELECT max_rpm, min_rpm, max_torque, min_torque
                FROM ntcs_tool_test
                LIMIT 1
            ")->fetch();

            if (!$src) {
                $this->toolSpecDebug('src_empty');
                return false;
            }

            $srcVal = [
                'max_rpm'    => (float)$src['max_rpm'],
                'min_rpm'    => (float)$src['min_rpm'],
                'max_torque' => (float)$src['max_torque'],
                'min_torque' => (float)$src['min_torque'],
            ];

            /* =====================================================
            * 2) ⭐ 穩定判斷（1 次 + 最短等待秒數）
            * ===================================================== */
            $now = time();
            $stable = [
                'values'          => $srcVal,
                'count'           => 1,
                'first_seen_ts'   => $now,
                'last_seen_ts'    => $now,
            ];

            if (is_file($stableFn)) {
                $prev = json_decode((string)@file_get_contents($stableFn), true);
                if (is_array($prev) && ($prev['values'] ?? null) === $srcVal) {
                    $stable['count']         = (int)($prev['count'] ?? 0) + 1;
                    $stable['first_seen_ts'] = (int)($prev['first_seen_ts'] ?? $now);
                }
            }

            @file_put_contents($stableFn, json_encode($stable, JSON_PRETTY_PRINT), LOCK_EX);

            // ⭐ 關鍵：至少存在 MIN_STABLE_SEC 秒
            if (
                $stable['count'] < $STABLE_REQUIRED ||
                ($now - $stable['first_seen_ts']) < $MIN_STABLE_SEC
            ) {
                $this->toolSpecDebug('not_stable_yet', [
                    'count' => $stable['count'],
                    'age'   => $now - $stable['first_seen_ts'],
                    'need'  => $MIN_STABLE_SEC,
                ]);
                return false;
            }

            /* =====================================================
            * 3) 與上次「成功同步」值比對
            * ===================================================== */
            if (is_file($stateFn)) {
                $state = json_decode((string)@file_get_contents($stateFn), true);
                if (is_array($state) && ($state['last_values'] ?? null) === $srcVal) {
                    $this->toolSpecDebug('same_as_last_synced', $srcVal);
                    return false;
                }
            }

            /* =====================================================
            * 4) 更新 iDAS DB
            * ===================================================== */
            $destPdo = new PDO('sqlite:' . $destDb, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            $dest = $destPdo->query("
                SELECT rowid, tool_type, max_rpm, min_rpm, max_torque, min_torque
                FROM ntcs_tool_test
                LIMIT 1
            ")->fetch();

            $destPdo->beginTransaction();

            if (!$dest) {
                $destPdo->prepare("
                    INSERT INTO ntcs_tool_test
                        (max_rpm, min_rpm, max_torque, min_torque)
                    VALUES
                        (:max_rpm, :min_rpm, :max_torque, :min_torque)
                ")->execute($srcVal);

                $this->toolSpecDebug('insert_new_row', $srcVal);

            } else {
                $needUpdate =
                    (float)$dest['max_rpm']    !== $srcVal['max_rpm'] ||
                    (float)$dest['min_rpm']    !== $srcVal['min_rpm'] ||
                    (float)$dest['max_torque'] !== $srcVal['max_torque'] ||
                    (float)$dest['min_torque'] !== $srcVal['min_torque'];

                if (!$needUpdate) {
                    $destPdo->rollBack();
                    $this->toolSpecDebug('dest_already_same');
                    return false;
                }

                if (!empty($dest['tool_type'])) {
                    $destPdo->prepare("
                        UPDATE ntcs_tool_test
                        SET
                            max_rpm    = :max_rpm,
                            min_rpm    = :min_rpm,
                            max_torque = :max_torque,
                            min_torque = :min_torque
                        WHERE tool_type = :tool_type
                    ")->execute($srcVal + [
                        ':tool_type' => $dest['tool_type']
                    ]);
                } else {
                    $destPdo->prepare("
                        UPDATE ntcs_tool_test
                        SET
                            max_rpm    = :max_rpm,
                            min_rpm    = :min_rpm,
                            max_torque = :max_torque,
                            min_torque = :min_torque
                        WHERE rowid = :rowid
                    ")->execute($srcVal + [
                        ':rowid' => $dest['rowid']
                    ]);
                }

                $this->toolSpecDebug('update_existing_row', $srcVal);
            }

            $destPdo->commit();

            /* =====================================================
            * 5) 成功寫入後才更新同步狀態
            * ===================================================== */
            @file_put_contents(
                $stateFn,
                json_encode([
                    'source'        => $srcDb,
                    'table'         => 'ntcs_tool_test',
                    'last_sync_at'  => time(),
                    'last_values'   => $srcVal,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
                LOCK_EX
            );

            $this->toolSpecDebug('sync_success', $srcVal);

            return true;

        } catch (Throwable $e) {
            $this->toolSpecDebug('exception', [
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }




    



    /**
     * shouldRunToolSpecSync
     *
     * Web-only 使用的輕量 Gate：
     * - 限制同步觸發頻率（避免每個 request 都跑）
     * - 不影響 runOnceWithFlag 的鎖定邏輯
     *
     * @param int $minIntervalSec 最短間隔秒數（例如 10）
     * @return bool 是否允許觸發同步
     */
    protected function shouldRunToolSpecSync(int $minIntervalSec = 10): bool
    {
        // 只在 Linux 啟用
        if (PHP_OS_FAMILY !== 'Linux') {
            return false;
        }

        $stateFn = '/var/www/html/database/.tool_spec_sync.json';

        // 從未同步過 → 允許
        if (!is_file($stateFn)) {
            return true;
        }

        $raw = @file_get_contents($stateFn);
        if ($raw === false) {
            // 讀不到狀態檔，為保險起見允許
            return true;
        }

        $state = json_decode($raw, true);
        if (!is_array($state)) {
            // JSON 損壞，允許重新同步
            return true;
        }

        $last = (int)($state['last_sync_at'] ?? 0);
        if ($last <= 0) {
            return true;
        }

        // 是否已超過最短間隔
        return (time() - $last) >= $minIntervalSec;
    }

    /**
     * DB Sanity Check
     * 確保 ntcs_device / ntcs_tool table 結構與資料正確
     *
     * @throws Exception
     */
    private function sanityCheckIdentityDB(string $dbPath, string $side): void
    {
        if (!is_file($dbPath)) {
            throw new Exception("{$side} DB not found: {$dbPath}");
        }

        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // ---------- ntcs_device_test ----------
        $cols = $db->query("PRAGMA table_info(ntcs_device_test)")->fetchAll(PDO::FETCH_ASSOC);
        $colNames = array_column($cols, 'name');

        if (!in_array('device_sn', $colNames, true)) {
            throw new Exception("{$side} ntcs_device_test.device_sn missing");
        }

        $cnt = (int)$db->query("SELECT COUNT(*) FROM ntcs_device_test")->fetchColumn();
        if ($cnt !== 1) {
            throw new Exception("{$side} ntcs_device_test row count invalid: {$cnt}");
        }

        // ---------- ntcs_tool_test ----------
        $cols = $db->query("PRAGMA table_info(ntcs_tool_test)")->fetchAll(PDO::FETCH_ASSOC);
        $colNames = array_column($cols, 'name');

        foreach (['tool_type', 'tool_sn'] as $col) {
            if (!in_array($col, $colNames, true)) {
                throw new Exception("{$side} ntcs_tool_test.{$col} missing");
            }
        }

        $cnt = (int)$db->query("SELECT COUNT(*) FROM ntcs_tool_test")->fetchColumn();
        if ($cnt !== 1) {
            throw new Exception("{$side} ntcs_tool_test row count invalid: {$cnt}");
        }
    }

    
    public function get_tools_temp(): ?array{

        $destDb = '/home/kls/NTCS7/ntcs_device.db';
        if (!is_file($destDb)) {
            return null;
        }

        try {
            $db = new PDO('sqlite:' . $destDb);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ⚠️ 明確指定欄位，不用 LIMIT 1 也可視情況加
            $stmt = $db->query("
                SELECT tool_type, tool_sn
                FROM ntcs_device_test
                LIMIT 1
            ");

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }

        

            // ✅ 直接回傳結構化資料
            return [
                'tool_type'   => $row['tool_type'] ?? null,
                'tool_sn'     => $row['tool_sn'] ?? null,
            ];

        } catch (Throwable $e) {
            return null;
        }
    }


    public function getControllerDeviceSN(): ?string
    {
        $dbPath = '/home/kls/NTCS7/ntcs_device.db';

        // ---------- 基本檢查 ----------
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            // ---------- 開啟 SQLite ----------
            $pdo = new PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ---------- 查詢 	device_id ----------
            $sql = "
                SELECT 	device_id
                FROM ntcs_device_test
                LIMIT 1
            ";

            $stmt = $pdo->query($sql);
            $row  = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row || !isset($row['device_id'])) {
                return null;
            }

            $deviceSN = trim((string)$row['device_id']);

            return $deviceSN !== '' ? $deviceSN : null;

        } catch (Throwable $e) {
            // 不要 echo，避免破壞 JSON
            error_log('[getControllerdevice_id] ' . $e->getMessage());
            return null;
        }
    }












}
