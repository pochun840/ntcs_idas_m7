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
    public function view($view, array $data = [])
    {
        $this->language_auto(); //從瀏覽器帶入語系
        //multi language
        $language = array("language"=>$_SESSION['language']);
        $data = array_merge($data,$language);
        
        //權限
        $privilege = array("privilege"=>$_SESSION['privilege']);
        $data = array_merge($data,$privilege);

        // 如果檔案存在就引入它
        if(file_exists('../app/views/' . $view . '.php')){

            if(file_exists('../app/language/' . $data['language'] . '.php')){
                require_once '../app/language/' . $data['language'] . '.php';
            } else { //預設採用英文
                require_once '../app/language/en-us.php';
            }

            require_once '../app/views/inc/header.php';
            require_once '../app/views/' . $view . '.php';
            require_once '../app/views/inc/footer.php';
            
        } else {
            die('View does not exist');
        }
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
        try {
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
        }
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
            error_log('[readDeviceIdFromDb] Read device_id failed: ' . $e->getMessage());
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
            error_log('[syncTempDeviceIdToIdas] temp DB not found: ' . $tempDbPath);
            return null;
        }
        if (!file_exists($idasDbPath)) {
            error_log('[syncTempDeviceIdToIdas] IDAS DB not found: ' . $idasDbPath);
            return null;
        }

        // 2) 從 temp DB 讀 device_id（共用 readDeviceIdFromDb）
        $deviceId = $this->readDeviceIdFromDb($tempDbPath);

        if ($deviceId === null) {
            error_log('[syncTempDeviceIdToIdas] No valid device_id found in temp DB.');
            return null;
        }

        // 保險：限制在 1~255
        if ($deviceId < 1 || $deviceId > 255) {
            error_log('[syncTempDeviceIdToIdas] Invalid device_id range: ' . $deviceId);
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
                    error_log('[syncTempDeviceIdToIdas] Warning: ntcs_device_test has ' . $rowCount . ' rows, all device_id updated.');
                }

            } else {
                // --- 情境二：IDAS 完全沒資料 → 插入一筆新的，只指定 device_id ---
                $sqlInsert = "INSERT INTO ntcs_device_test (device_id) VALUES (:device_id)";
                $stmtIns   = $pdoIdas->prepare($sqlInsert);
                $stmtIns->execute([':device_id' => $deviceId]);
            }

            @chmod($idasDbPath, 0777);

            error_log('[syncTempDeviceIdToIdas] Synced device_id=' . $deviceId . ' from temp DB to IDAS DB (update-only for existing row).');

            return $deviceId;

        } catch (Exception $e) {
            error_log('[syncTempDeviceIdToIdas] Write to IDAS DB failed: ' . $e->getMessage());
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
            error_log("❌ 資料同步失敗: " . $e->getMessage());
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
            error_log($e->getMessage());
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
            error_log($e->getMessage());
            echo $e->getMessage();
        }
    }


    public function ajax_check_device_id(){
        header('Content-Type: application/json; charset=utf-8');

        // 前端傳來目前畫面認知的 device_id（從 cookie 或 JS 變數帶）
        $current = isset($_POST['current_device_id']) ? (int)$_POST['current_device_id'] : null;

        // 這邊可以視情況決定要不要強制 refresh
        // - true  → 每次都重新偵測（最保險，但稍微重）
        // - false → 使用你之前加的快取機制（比較省）
        $new = $this->ntcs_device_db_sysnc(false);

        $changed = false;
        if ($new !== null && $current !== null && $new !== $current) {
            $changed = true;
        }

        echo json_encode([
            'res_type'   => 'OK',
            'device_id'  => $new,
            'changed'    => $changed,
        ]);
        exit;
    }


}
