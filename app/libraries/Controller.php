<?php
/*
 * Single-codebase platform switch.
 * /home/kls/upgrade/icontroller = 1 -> i-controller implementation
 * 0 / missing / invalid -> NTCS implementation
 */
trait LazyDeviceIdResolver
{
    private static $lazyDeviceIdResolved = false;
    private static $lazyDeviceIdValue = null;

    /** Resolve Controller Device ID only when a feature actually needs it. */
    protected function lazyDeviceId($fallback = null)
    {
        if (!self::$lazyDeviceIdResolved) {
            self::$lazyDeviceIdResolved = true;
            $value = $this->ntcs_device_db_sysnc();
            self::$lazyDeviceIdValue = ($value !== null && (int)$value >= 1 && (int)$value <= 255)
                ? (int)$value
                : null;
        }

        return self::$lazyDeviceIdValue ?? $fallback;
    }
}

class IControllerBaseController
{
    use LazyDeviceIdResolver;
    // OP protocol runtime cache: avoid reading SQLite / retrying fallback endpoints on every command.
    protected static $opProtocolCandidatesCache = null;
    protected static $opLastEndpoint = null;

    // 載入 model
    public function model($model)
    {
        $modelFile = idas_platform_app_file('models/' . $model . '.php');
        require_once $modelFile;
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

        $viewFile = idas_platform_app_file('views/' . $view . '.php');

        if (!file_exists($viewFile)) {
            header("HTTP/1.1 404 Not Found");
            echo "View not found: {$view}";
            exit;
        }

        $languageFile = idas_platform_app_file('language/' . $data['language'] . '.php');
        if (is_file($languageFile)) {
            require_once $languageFile;
        } else {
            require_once idas_platform_app_file('language/en-us.php');
        }

        require_once '../app/views/inc/header.php';
        require_once $viewFile;
        require_once '../app/views/inc/footer.php';
    }

    public function language_auto($value='')
    {
        // 如果 $_SESSION['language'] 未設定或為空，就從瀏覽器語系帶入。
        // AJAX / wget / curl 不一定有 HTTP_ACCEPT_LANGUAGE，所以必須有預設值。
        if (!isset($_SESSION['language']) || $_SESSION['language'] == '') {
            $acceptLang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-us');
            $lang = substr($acceptLang, 0, 5);

            if (preg_match("/zh-cn/i", $lang)) {
                $_SESSION['language'] = 'zh-cn';
            } else if (preg_match("/zh-tw/i", $lang)) {
                $_SESSION['language'] = 'zh-tw';
            } else if (preg_match("/en/i", $lang)) {
                $_SESSION['language'] = 'en-us';
            } else {
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
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $iPod = stripos($ua,"iPod");
        $iPhone = stripos($ua,"iPhone");
        $iPad = stripos($ua,"iPad");
        if(stripos($ua,"Android") && stripos($ua,"mobile")){
            $Android = true;
        }else if(stripos($ua,"Android")){
            $Android = false;
            $AndroidTablet = true;
        }else{
            $Android = false;
            $AndroidTablet = false;
        }
        $webOS = stripos($ua,"webOS");
        $BlackBerry = stripos($ua,"BlackBerry");
        $RimTablet= stripos($ua,"RIM Tablet");
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
            $con_db = idas_sqlite_connect(idas_path('database_root', 'das.db')); 
        }else{
            $con_db = idas_sqlite_connect('../data.db'); 
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
                $db_path = idas_path('database_root', 'data_device.db');
            } else {
                $db_path = '../data_device.db';
            }

            if (!file_exists($db_path)) {
                throw new Exception("❌ Database file not found: $db_path");
            }

            $con_db = idas_sqlite_connect($db_path);
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
        $srcController = idas_path('controller_root', 'ntcs_device.db');            // 控制器端 ntcs_device.db
        $tempDbPath    = idas_path('database_root', 'ntcs_device_temp.db');// iDAS 暫存
        $idasDbPath    = idas_path('database_root', 'ntcs_device_IDAS.db');// iDAS 正式用的 device DB

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
            $pdo = idas_sqlite_connect($dbPath);
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
     * 讀取指定 SQLite DB 的 ntcs_device_test.modbus_type。
     * 0 = MODBUS TCP, 1 = MODBUS RTU, 2 = OP Protocol
     */
    protected function readModbusTypeFromDb(string $dbPath): ?int
    {
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            $pdo = idas_sqlite_connect($dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 2000');

            $columnExists = (bool)$pdo->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'modbus_type' LIMIT 1"
            )->fetchColumn();

            if (!$columnExists) {
                return null;
            }

            $value = $pdo->query(
                "SELECT modbus_type FROM ntcs_device_test WHERE modbus_type IS NOT NULL ORDER BY rowid DESC LIMIT 1"
            )->fetchColumn();

            if ($value === false || $value === null || $value === '' || !is_numeric($value)) {
                return null;
            }

            $type = (int)$value;
            return in_array($type, [0, 1, 2], true) ? $type : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * 將 modbus_type 寫入指定 SQLite DB。
     */
    protected function writeModbusTypeToDb(string $dbPath, int $modbusType): bool
    {
        if (!in_array($modbusType, [0, 1, 2], true)) {
            return false;
        }

        if (!is_file($dbPath) || !is_readable($dbPath) || !is_writable($dbPath)) {
            return false;
        }

        try {
            $pdo = idas_sqlite_connect($dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 3000');

            $columnExists = (bool)$pdo->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'modbus_type' LIMIT 1"
            )->fetchColumn();

            if (!$columnExists) {
                return false;
            }

            $stmt = $pdo->prepare("UPDATE ntcs_device_test SET modbus_type = :type");
            $stmt->bindValue(':type', $modbusType, PDO::PARAM_INT);
            $stmt->execute();
            @chmod($dbPath, 0777);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * 取得目前控制器通訊協議。
     *
     * 注意：這裡只負責「判斷目前要使用哪一種通訊協議」，不可直接把
     * Controller 端的 modbus_type 寫回 iDAS DB。
     *
     * 原因：Check/ajax_check_device_id 需要比對
     * /home/kls/NTCS7/ntcs_device.db 與
     * /var/www/html/database/ntcs_device_IDAS.db 的 modbus_type 是否不同。
     * 如果共用通訊層在判斷協議時就先自動寫回 iDAS DB，前端就會偵測不到
     *「控制器端 MODBUS TYPE 已變更」的狀態，也就不會出現紅色 Banner / Popup。
     *
     * 正確同步時機：
     * - Check 偵測到 changed = true 後，前端顯示提醒。
     * - 再由 Check/sync_device_identity 或 ajax_force_sync_device_id 走原本同步流程。
     */
    public function get_modbus_type_from_controller(): int
    {
        $controllerDb = idas_path('controller_root', 'ntcs_device.db');
        $idasDb       = idas_path('database_root', 'ntcs_device_IDAS.db');
        $tempDb       = idas_path('database_root', 'ntcs_device_temp.db');

        // 優先以 Controller 實際 DB 為準，讓 iDAS 通訊可立即切到正確協議；
        // 但不要在這裡同步寫回 iDAS DB，避免 Check 偵測不到差異。
        $controllerType = $this->readModbusTypeFromDb($controllerDb);
        if ($controllerType !== null) {
            return $controllerType;
        }

        // Controller DB 讀不到時，再使用 iDAS DB。
        $idasType = $this->readModbusTypeFromDb($idasDb);
        if ($idasType !== null) {
            return $idasType;
        }

        // 最後才使用 temp DB。
        $tempType = $this->readModbusTypeFromDb($tempDb);
        if ($tempType !== null) {
            return $tempType;
        }

        return 0;
    }

    public function is_op_protocol_enabled(): bool
    {
        return $this->get_modbus_type_from_controller() === 2;
    }

    protected function getProtocolHost(): string
    {
        require_once '../app/config/config.php';
        $host = defined('CONTROLLER_IP') ? trim((string)CONTROLLER_IP) : '';
        return $host !== '' ? $host : '127.0.0.1';
    }

    /**
     * 取得 Controller Modbus TCP server port。
     * 網路設定儲存在 ntcs_device_test.wifi：mode_ip_port_mask_gateway。
     * Controller DB 為主要來源，讀不到時才 fallback iDAS / temp DB。
     */
    protected function getControllerTcpPort(int $defaultPort = 502): int
    {
        foreach ([
            idas_path('controller_root', 'ntcs_device.db'),
            idas_path('database_root', 'ntcs_device_IDAS.db'),
            idas_path('database_root', 'ntcs_device_temp.db'),
        ] as $dbPath) {
            $port = $this->readControllerTcpPortFromDb($dbPath);
            if ($port !== null) {
                return $port;
            }
        }

        return ($defaultPort >= 1 && $defaultPort <= 65535) ? $defaultPort : 502;
    }

    /**
     * wifi 的第 3 欄就是 server port；DHCP 模式即使 Static IP 為空也必須能讀到 port。
     */
    protected function readControllerTcpPortFromDb(string $dbPath): ?int
    {
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            $pdo = idas_sqlite_connect($dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 2000');

            $columnExists = (bool)$pdo->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'wifi' LIMIT 1"
            )->fetchColumn();
            if (!$columnExists) {
                return null;
            }

            $wifi = $pdo->query(
                "SELECT wifi FROM ntcs_device_test WHERE wifi IS NOT NULL AND wifi <> '' ORDER BY rowid DESC LIMIT 1"
            )->fetchColumn();
            if ($wifi === false || $wifi === null || trim((string)$wifi) === '') {
                return null;
            }

            $parts = explode('_', trim((string)$wifi));
            if (count($parts) >= 3) {
                $candidate = trim((string)$parts[2]);
                if (ctype_digit($candidate)) {
                    $port = (int)$candidate;
                    if ($port >= 1 && $port <= 65535) {
                        return $port;
                    }
                }
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }

    /**
     * 從 ntcs_device_test.wifi 解析控制器連線資訊。
     * 常見格式：1_192.168.0.75_4545_255.255.255.0_192.168.0.255
     */
    protected function parseControllerWifiEndpoint($wifi, int $defaultPort = 4545): ?array
    {
        $wifi = trim((string)$wifi);
        if ($wifi === '') {
            return null;
        }

        $parts = preg_split('/[_\s,;]+/', $wifi);
        $host = null;
        $port = null;

        foreach ($parts as $idx => $part) {
            $part = trim((string)$part);
            if ($host === null && filter_var($part, FILTER_VALIDATE_IP)) {
                $host = $part;

                // IP 後面第一個合法數字通常就是 port。
                for ($j = $idx + 1; $j < count($parts); $j++) {
                    $candidate = trim((string)$parts[$j]);
                    if (ctype_digit($candidate)) {
                        $candidatePort = (int)$candidate;
                        if ($candidatePort > 0 && $candidatePort <= 65535) {
                            $port = $candidatePort;
                            break;
                        }
                    }
                }
                break;
            }
        }

        if ($host === null) {
            return null;
        }

        return [
            'host' => $host,
            'port' => $port ?: $defaultPort,
        ];
    }

    protected function readControllerWifiEndpointFromDb(string $dbPath, int $defaultPort = 4545): ?array
    {
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            $pdo = idas_sqlite_connect($dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 2000');

            $columnExists = (bool)$pdo->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'wifi' LIMIT 1"
            )->fetchColumn();

            if (!$columnExists) {
                return null;
            }

            $wifi = $pdo->query(
                "SELECT wifi FROM ntcs_device_test WHERE wifi IS NOT NULL AND wifi <> '' ORDER BY rowid DESC LIMIT 1"
            )->fetchColumn();

            if ($wifi === false || $wifi === null || $wifi === '') {
                return null;
            }

            return $this->parseControllerWifiEndpoint($wifi, $defaultPort);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * OP 連線候選清單。
     *
     * OP Server 有些版本只綁定實體 IP，不一定會聽 127.0.0.1。
     * 因此 OP 不應只吃 CONTROLLER_IP，而是優先從 Controller DB 的 wifi 欄位解析
     * 例如 1_192.168.0.75_4545_255.255.255.0_192.168.0.255。
     */
    protected function getOpProtocolCandidates(?string $host = null, int $port = 4545): array
    {
        $candidates = [];
        $add = function($h, $p) use (&$candidates) {
            $h = trim((string)$h);
            $p = (int)$p;
            if ($h === '' || $p <= 0 || $p > 65535) {
                return;
            }
            $key = $h . ':' . $p;
            $candidates[$key] = ['host' => $h, 'port' => $p];
        };

        // Explicit host is used as-is. This keeps test/debug calls predictable.
        if ($host !== null && trim((string)$host) !== '') {
            $add($host, $port);
            return array_values($candidates);
        }

        // Same PHP request: put the last successful endpoint first.
        if (is_array(self::$opLastEndpoint ?? null)) {
            $add(self::$opLastEndpoint['host'] ?? '', self::$opLastEndpoint['port'] ?? 4545);
        }

        // Same PHP request: reuse the candidate list so every OP command does not re-open SQLite DBs.
        if (is_array(self::$opProtocolCandidatesCache)) {
            foreach (self::$opProtocolCandidatesCache as $endpoint) {
                $add($endpoint['host'] ?? '', $endpoint['port'] ?? 4545);
            }
            return array_values($candidates);
        }

        foreach ([
            idas_path('controller_root', 'ntcs_device.db'),
            idas_path('database_root', 'ntcs_device_IDAS.db'),
            idas_path('database_root', 'ntcs_device_temp.db'),
        ] as $dbPath) {
            $endpoint = $this->readControllerWifiEndpointFromDb($dbPath, 4545);
            if ($endpoint) {
                // OP standard port is 4545. Also add 4545 explicitly in case DB still contains 502.
                $add($endpoint['host'], $endpoint['port']);
                $add($endpoint['host'], 4545);
            }
        }

        $add($this->getProtocolHost(), 4545);
        $add('127.0.0.1', 4545);

        self::$opProtocolCandidatesCache = array_values($candidates);
        return array_values($candidates);
    }

    protected function rememberOpEndpoint(string $host, int $port): void
    {
        if ($host !== '' && $port > 0 && $port <= 65535) {
            self::$opLastEndpoint = ['host' => $host, 'port' => $port];
        }
    }

    protected function setOpStreamTimeout($client, float $seconds): void
    {
        $seconds = max(0.05, $seconds);
        $sec = (int)floor($seconds);
        $usec = (int)(($seconds - $sec) * 1000000);
        @stream_set_timeout($client, $sec, $usec);
    }

    protected function readOpStreamLine($client, int $maxBytes = 8192): string
    {
        $response = '';
        while (!feof($client) && strlen($response) < $maxBytes) {
            $chunk = fread($client, 1024);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $response .= $chunk;
            if (strpos($response, "\n") !== false || strpos($response, "\r") !== false) {
                break;
            }
        }
        return trim($response);
    }

    protected function openOpClient(string $host, int $port, float $connectTimeout)
    {
        $errno = 0;
        $errstr = '';
        return @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            max(0.05, $connectTimeout),
            STREAM_CLIENT_CONNECT
        );
    }

    /**
     * 將 OP payload 完整寫入 socket。
     * fwrite() 可能只送出部分 bytes，不能只用 > 0 當作整筆命令已送完。
     */
    protected function writeOpPayloadFully($client, string $payload): bool
    {
        $length = strlen($payload);
        $offset = 0;
        $zeroWrites = 0;

        while ($offset < $length) {
            $written = @fwrite($client, substr($payload, $offset));
            if ($written === false) {
                return false;
            }
            if ($written === 0) {
                $zeroWrites++;
                if ($zeroWrites >= 3) {
                    return false;
                }
                usleep(20000);
                continue;
            }

            $zeroWrites = 0;
            $offset += $written;
        }

        @fflush($client);
        return $offset === $length;
    }

    protected function normalizeModbusResponseToRegisters($raw): array
    {
        if (is_string($raw)) {
            $values = @unpack('n*', $raw);
            return $values ? array_values($values) : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        if ($raw === []) {
            return [];
        }

        $raw = array_values(array_map('intval', $raw));

        // phpmodbus 常見回傳為 byte array；若數量為偶數且每個值 <= 255，轉成 16-bit word。
        $isByteArray = (count($raw) % 2 === 0 && max($raw) <= 0xFF);
        if ($isByteArray) {
            $words = [];
            for ($i = 0; $i + 1 < count($raw); $i += 2) {
                $words[] = (($raw[$i] & 0xFF) << 8) | ($raw[$i + 1] & 0xFF);
            }
            return $words;
        }

        return $raw;
    }

    public function op_send(string $command, ?string $host = null, int $port = 4545, float $connectTimeout = 1.0, float $readTimeout = 1.0): array
    {
        $command = strtoupper(trim($command));
        $isWriteCommand = (strpos($command, 'IDAS_WRITE_') === 0);

        $baseResult = [
            'ok' => false,
            'command' => $command,
            'host' => $host ?: '',
            'port' => $port,
            'response' => '',
            'parsed' => null,
            'error' => '',
            'attempts' => [],
        ];

        if ($command === '') {
            $baseResult['error'] = 'OP command is empty';
            return $baseResult;
        }

        $candidates = $this->getOpProtocolCandidates($host, $port);
        if (empty($candidates)) {
            $baseResult['error'] = 'No OP endpoint candidate';
            return $baseResult;
        }

        foreach ($candidates as $endpoint) {
            $tryHost = (string)$endpoint['host'];
            $tryPort = (int)$endpoint['port'];

            $result = $baseResult;
            $result['host'] = $tryHost;
            $result['port'] = $tryPort;
            $result['attempts'] = [];

            $client = $this->openOpClient($tryHost, $tryPort, $connectTimeout);

            if (!$client) {
                $result['error'] = 'TCP connect failed';
                $baseResult['attempts'][] = [
                    'host' => $tryHost,
                    'port' => $tryPort,
                    'ok' => false,
                    'error' => $result['error'],
                ];
                continue;
            }

            try {
                $this->setOpStreamTimeout($client, $readTimeout);
                $payload = $command . (substr($command, -1) === "\n" ? '' : "\n");

                if (!$this->writeOpPayloadFully($client, $payload)) {
                    $result['error'] = 'TCP write failed or incomplete payload';
                    $baseResult['attempts'][] = [
                        'host' => $tryHost,
                        'port' => $tryPort,
                        'ok' => false,
                        'error' => $result['error'],
                    ];
                    continue;
                }

                /*
                 * OP WRITE is fire-and-forget on current controller firmware:
                 *   - READ returns NTCS_RETURN_xxx_x
                 *   - WRITE accepts the ASCII command but does not reply.
                 * Do not wait for read timeout after WRITE. This is the biggest speed gain.
                 */
                if ($isWriteCommand) {
                    $this->rememberOpEndpoint($tryHost, $tryPort);
                    $result['ok'] = true;
                    $result['response'] = '';
                    $result['parsed'] = [
                        'type' => 'WRITE_NO_RESPONSE',
                        'address' => null,
                        'value' => null,
                        'raw' => '',
                    ];
                    $result['accepted_no_response'] = true;
                    $result['write_sent_only'] = true;
                    $result['error'] = '';
                    $baseResult['attempts'][] = [
                        'host' => $tryHost,
                        'port' => $tryPort,
                        'ok' => true,
                        'accepted_no_response' => true,
                        'write_sent_only' => true,
                    ];
                    $result['attempts'] = $baseResult['attempts'];
                    return $result;
                }

                $response = $this->readOpStreamLine($client);
                $meta = stream_get_meta_data($client);

                if ($response === '') {
                    $result['error'] = !empty($meta['timed_out']) ? 'TCP read timeout' : 'OP empty response';
                    $baseResult['attempts'][] = [
                        'host' => $tryHost,
                        'port' => $tryPort,
                        'ok' => false,
                        'error' => $result['error'],
                    ];
                    continue;
                }

                $this->rememberOpEndpoint($tryHost, $tryPort);
                $result['ok'] = true;
                $result['response'] = $response;
                $result['parsed'] = $this->parse_op_return($response);
                $baseResult['attempts'][] = [
                    'host' => $tryHost,
                    'port' => $tryPort,
                    'ok' => true,
                    'response' => $response,
                ];
                $result['attempts'] = $baseResult['attempts'];
                return $result;
            } catch (Throwable $e) {
                $result['error'] = $e->getMessage();
                $baseResult['attempts'][] = [
                    'host' => $tryHost,
                    'port' => $tryPort,
                    'ok' => false,
                    'error' => $result['error'],
                ];
                continue;
            } finally {
                if (is_resource($client)) {
                    fclose($client);
                }
            }
        }

        $baseResult['error'] = 'All OP endpoints failed';
        if (!empty($baseResult['attempts'])) {
            $last = end($baseResult['attempts']);
            if (!empty($last['error'])) {
                $baseResult['error'] .= ': ' . $last['error'];
            }
        }

        return $baseResult;
    }

    protected function op_read_registers_one_socket(int $startAddress, int $quantity, float $connectTimeout = 1.0, float $readTimeout = 1.0): ?array
    {
        if ($quantity <= 0) {
            return [];
        }

        $candidates = $this->getOpProtocolCandidates(null, 4545);
        foreach ($candidates as $endpoint) {
            $tryHost = (string)($endpoint['host'] ?? '');
            $tryPort = (int)($endpoint['port'] ?? 4545);
            if ($tryHost === '') {
                continue;
            }

            $client = $this->openOpClient($tryHost, $tryPort, $connectTimeout);
            if (!$client) {
                continue;
            }

            $values = [];
            $ok = true;

            try {
                $this->setOpStreamTimeout($client, $readTimeout);
                for ($i = 0; $i < $quantity; $i++) {
                    $address = $startAddress + $i;
                    $command = "IDAS_READ_{$address}\n";
                    $written = fwrite($client, $command);
                    if ($written === false || $written <= 0) {
                        $ok = false;
                        break;
                    }

                    $response = $this->readOpStreamLine($client);
                    if ($response === '') {
                        $ok = false;
                        break;
                    }

                    $parsed = $this->parse_op_return($response);
                    if (!is_array($parsed) || ($parsed['type'] ?? '') !== 'RETURN') {
                        $ok = false;
                        break;
                    }
                    if ((int)($parsed['address'] ?? -1) !== $address) {
                        $ok = false;
                        break;
                    }

                    $value = $parsed['value'] ?? null;
                    if (!is_numeric($value)) {
                        $ok = false;
                        break;
                    }
                    $values[] = (int)$value;
                }
            } catch (Throwable $e) {
                $ok = false;
            } finally {
                if (is_resource($client)) {
                    fclose($client);
                }
            }

            if ($ok && count($values) === $quantity) {
                $this->rememberOpEndpoint($tryHost, $tryPort);
                return $values;
            }
        }

        return null;
    }

    public function parse_op_return(string $rawResponse): ?array
    {
        $rawResponse = strtoupper(trim($rawResponse));
        if ($rawResponse === '') {
            return null;
        }

        if (preg_match('/^NTCS_RETURN_(\d+)_(.+)$/', $rawResponse, $matches)) {
            return [
                'type' => 'RETURN',
                'address' => (int)$matches[1],
                'value' => $matches[2],
                'raw' => $rawResponse,
            ];
        }

        if (preg_match('/^NTCS_([A-Z]+)_(.*)$/', $rawResponse, $matches)) {
            return [
                'type' => $matches[1],
                'address' => null,
                'value' => $matches[2],
                'raw' => $rawResponse,
            ];
        }

        if (in_array($rawResponse, ['OK', 'SUCCESS', 'WRITE_OK', 'NTCS_OK'], true)) {
            return [
                'type' => 'OK',
                'address' => null,
                'value' => null,
                'raw' => $rawResponse,
            ];
        }

        return [
            'type' => 'UNKNOWN',
            'address' => null,
            'value' => null,
            'raw' => $rawResponse,
        ];
    }

    public function op_read(int $address, int $maxAttempts = 2, int $retryDelayUs = 120000): ?int
    {
        $maxAttempts = max(1, min(3, $maxAttempts));
        $lastError = 'unknown';
        $lastAttempts = [];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $result = $this->op_send("IDAS_READ_{$address}");
            $lastAttempts = is_array($result['attempts'] ?? null) ? $result['attempts'] : [];

            if (!empty($result['ok'])) {
                $parsed = $result['parsed'] ?? null;
                if (is_array($parsed) && ($parsed['type'] ?? '') === 'RETURN') {
                    if ((int)($parsed['address'] ?? -1) === $address) {
                        $value = $parsed['value'] ?? null;
                        if (is_numeric($value)) {
                            return (int)$value;
                        }
                        $lastError = 'OP return value is not numeric';
                    } else {
                        $lastError = 'OP return address mismatch';
                    }
                } else {
                    $lastError = 'OP response is not RETURN';
                }
            } else {
                $lastError = (string)($result['error'] ?? 'OP send failed');
            }

            if ($attempt < $maxAttempts) {
                usleep(max(20000, $retryDelayUs));
            }
        }

        $attemptText = [];
        foreach ($lastAttempts as $item) {
            $attemptText[] = sprintf(
                '%s:%s=%s%s',
                (string)($item['host'] ?? '?'),
                (string)($item['port'] ?? '?'),
                !empty($item['ok']) ? 'ok' : 'fail',
                !empty($item['error']) ? '(' . $item['error'] . ')' : ''
            );
        }
        $this->logMessage(
            '[OP] READ failed address=' . $address .
            ', retries=' . $maxAttempts .
            ', error=' . $lastError .
            ', endpoints=' . implode(',', $attemptText)
        );

        return null;
    }

    public function op_write(int $address, $value): bool
    {
        $value = is_numeric($value) ? (int)$value : strtoupper(trim((string)$value));
        $result = $this->op_send("IDAS_WRITE_{$address}_{$value}");

        if (empty($result['ok'])) {
            return false;
        }

        $parsed = $result['parsed'] ?? null;
        if (!is_array($parsed)) {
            return !empty($result['accepted_no_response'])
                || trim((string)($result['response'] ?? '')) !== '';
        }

        $type = (string)($parsed['type'] ?? '');
        return in_array($type, ['RETURN', 'OK', 'SUCCESS', 'WRITE', 'WRITE_NO_RESPONSE'], true);
    }

    public function protocol_read_register(int $unitId, int $address): ?int
    {
        if ($this->is_op_protocol_enabled()) {
            return $this->op_read($address);
        }

        require_once '../app/config/config.php';
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        try {
            $modbus = new ModbusMaster($this->getProtocolHost(), 'TCP');
            $modbus->port = $this->getControllerTcpPort(502);
            $modbus->timeout_sec = 10;
            $raw = $modbus->readMultipleRegisters($unitId, $address, 1);
            $words = $this->normalizeModbusResponseToRegisters($raw);
            return $words[0] ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function protocol_read_registers(int $unitId, int $startAddress, int $quantity): array
    {
        if ($quantity <= 0) {
            return [];
        }

        if ($this->is_op_protocol_enabled()) {
            // OP protocol can only send one command at a time.
            // To improve speed, keep one TCP connection and send/read one command sequentially.
            $values = $this->op_read_registers_one_socket($startAddress, $quantity);
            if ($values !== null) {
                return $values;
            }

            // Fallback for older OP servers that close the socket after every command.
            $values = [];
            for ($i = 0; $i < $quantity; $i++) {
                $value = $this->op_read($startAddress + $i);
                if ($value === null) {
                    throw new RuntimeException('OP read failed at address ' . ($startAddress + $i));
                }
                $values[] = $value;
            }
            return $values;
        }

        require_once '../app/config/config.php';
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $modbus = new ModbusMaster($this->getProtocolHost(), 'TCP');
        $modbus->port = $this->getControllerTcpPort(502);
        $modbus->timeout_sec = 10;
        $raw = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);
        return $this->normalizeModbusResponseToRegisters($raw);
    }

    public function protocol_write_register(int $unitId, int $address, $value): bool
    {
        if ($this->is_op_protocol_enabled()) {
            return $this->op_write($address, $value);
        }

        require_once '../app/config/config.php';
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        try {
            $modbus = new ModbusMaster($this->getProtocolHost(), 'TCP');
            $modbus->port = $this->getControllerTcpPort(502);
            $modbus->timeout_sec = 10;
            $modbus->writeMultipleRegister($unitId, $address, [(int)$value], ['INT']);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function protocol_write_registers(int $unitId, int $startAddress, array $values): bool
    {
        if ($values === []) {
            return true;
        }

        if ($this->is_op_protocol_enabled()) {
            foreach (array_values($values) as $offset => $value) {
                if (!$this->op_write($startAddress + $offset, $value)) {
                    return false;
                }
            }
            return true;
        }

        require_once '../app/config/config.php';
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        try {
            $modbus = new ModbusMaster($this->getProtocolHost(), 'TCP');
            $modbus->port = $this->getControllerTcpPort(502);
            $modbus->timeout_sec = 10;
            $types = array_fill(0, count($values), 'INT');
            $modbus->writeMultipleRegister($unitId, $startAddress, array_values($values), $types);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
    /**
     * 寫入命令後以實際狀態暫存器驗證是否生效。
     * 適合 JOB/SEQ 這類「命令 register」：不直接讀回命令位址，而讀 Controller 真實狀態。
     */
    public function protocol_wait_for_register_values(
        int $unitId,
        int $startAddress,
        array $expectedValues,
        int $maxAttempts = 5,
        int $delayUs = 200000
    ): bool {
        $expectedValues = array_values(array_map('intval', $expectedValues));
        if ($expectedValues === []) {
            return true;
        }

        $maxAttempts = max(1, min(10, $maxAttempts));
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $actual = $this->protocol_read_registers($unitId, $startAddress, count($expectedValues));
                $actual = array_values(array_map('intval', $actual));
                if ($actual === $expectedValues) {
                    return true;
                }
            } catch (Throwable $e) {
                $this->logMessage(
                    '[PROTOCOL VERIFY] read failed start=' . $startAddress .
                    ', attempt=' . $attempt .
                    ', error=' . $e->getMessage()
                );
            }

            if ($attempt < $maxAttempts) {
                usleep(max(20000, $delayUs));
            }
        }

        return false;
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
        
        $tempDbPath = idas_path('database_root', 'ntcs_device_temp.db');
        $idasDbPath = idas_path('database_root', 'ntcs_device_IDAS.db');

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
            $pdoIdas = idas_sqlite_connect($idasDbPath);
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
    //判斷控制器的登入登出
    public function idas_check($device_id){

        require_once '../app/config/config.php';

        $ip = CONTROLLER_IP;
        $startAddress = 29002;
        $response = ['result' => null, 'error' => ''];

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $response['error'] = "無效的 IP 位址：$ip";
            return $response;
        }

        $unitId = (int)$device_id;
        if ($unitId < 1 || $unitId > 255) {
            $unitId = 1;
        }

        $protocol = $this->is_op_protocol_enabled() ? 'OP' : 'MODBUS';

        try {
            $value = $this->protocol_read_register($unitId, $startAddress);
            if ($value === null) {
                throw new RuntimeException('Protocol read failed');
            }
            $response['result'] = $value;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';

            $endpoint = '';
            if ($protocol === 'OP' && is_array(self::$opLastEndpoint ?? null)) {
                $endpoint = (string)(self::$opLastEndpoint['host'] ?? '') . ':' . (string)(self::$opLastEndpoint['port'] ?? '');
            }
            $this->logMessage(
                '[LOGIN CHECK] failed protocol=' . $protocol .
                ', register=29002' .
                ', unitId=' . $unitId .
                ($endpoint !== '' ? ', endpoint=' . $endpoint : '') .
                ', error=' . $response['error']
            );
        }

        return $response;
    }



    public function get_tools_version($unitId){
        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;
        return $this->protocol_read_register($unitId, 29003);
    }

    


    public function get_firmware_version($unitId){
        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;
        return $this->protocol_read_register($unitId, 29004);
    }



    public function get_db_sync($unitId){
        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;
        $response = ['result' => null, 'error' => ''];
        try {
            $value = $this->protocol_read_register($unitId, 29006);
            if ($value === null) {
                throw new RuntimeException('Protocol read failed');
            }
            $response['result'] = $value;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }
        return json_encode($response);
    }



    
    public function get_operation_id(){
        $response = ['result' => null, 'error' => ''];
        try {
            $value = $this->protocol_read_register(1, 4165);
            if ($value === null) {
                throw new RuntimeException('Protocol read failed');
            }
            $response['result'] = $value;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }
        return json_encode($response);
    }



    public function get_data_info(){
        $response = ['result' => null, 'error' => ''];
        try {
            $values = $this->protocol_read_registers(1, 4097, 64);
            $response['result'] = $values;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }
        echo json_encode($response);
    }



    //起子sn
    public function get_tools_sn($unitId = 1) {

        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;

        $startAddress = 4122;
        $quantity = 10;

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
            $regs = $this->protocol_read_registers($unitId, $startAddress, $quantity);
            if (!is_array($regs) || empty($regs)) {
                throw new RuntimeException('No data returned from protocol');
            }

            $asciiBE = $regsToAscii($regs, 'BE', true, true);
            $asciiLE = $regsToAscii($regs, 'LE', true, true);

            $response['ok']            = true;
            $response['raw_registers'] = array_map('intval', $regs);
            $response['ascii_be']      = $asciiBE;
            $response['ascii_le']      = $asciiLE;
            $response['model']         = $asciiBE;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }

        return $response;
    }



    public function get_controller_sn($unitId = 1) {

        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;

        $startAddress = 4102;
        $quantity = 10;

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
            $regs = $this->protocol_read_registers($unitId, $startAddress, $quantity);
            if (!is_array($regs) || empty($regs)) {
                throw new RuntimeException('No data returned from protocol');
            }

            $asciiBE = $regsToAscii($regs, 'BE', true, true);
            $asciiLE = $regsToAscii($regs, 'LE', true, true);

            $response['ok']            = true;
            $response['raw_registers'] = array_map('intval', $regs);
            $response['ascii_be']      = $asciiBE;
            $response['ascii_le']      = $asciiLE;
            $response['model']         = $asciiBE;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }

        return $response;
    }


    //起子型號
    public function get_tools_type($unitId = 1) {

        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;

        $startAddress = 4112;
        $quantity = 10;

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
            $regs = $this->protocol_read_registers($unitId, $startAddress, $quantity);
            if (!is_array($regs) || empty($regs)) {
                throw new RuntimeException('No data returned from protocol');
            }

            $asciiBE = $regsToAscii($regs, 'BE', true, true);
            $asciiLE = $regsToAscii($regs, 'LE', true, true);

            $response['ok']            = true;
            $response['raw_registers'] = array_map('intval', $regs);
            $response['ascii_be']      = $asciiBE;
            $response['ascii_le']      = $asciiLE;
            $response['model']         = $asciiBE;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }

        return $response;
    }



    public function ntcs_data_db_sysnc() {
        $this->sync_db(
            idas_path('controller_root', 'ntcs_data.db'),
            idas_path('database_root', 'ntcs_data.db')
        );

        $this->sync_ntcs_tool_data();
    }




    public function ntcs_device_db_load() {
        $this->sync_db(
            idas_path('controller_root', 'ntcs_device.db'),
            idas_path('database_root', 'ntcs_device_IDAS.db')
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
        $srcDB = idas_path('controller_root', 'ntcs_device.db');
        $dstDB = idas_path('database_root', 'ntcs_device_IDAS.db');

        if (!file_exists($srcDB) || !file_exists($dstDB)) {
            return;
        }

        try {
            // 1) 連到來源 DB
            $src = idas_sqlite_connect($srcDB);
            $src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1a) 取 device_id 和 device_version
            $deviceInfo = $src->query("SELECT device_id, device_version FROM ntcs_device_test")->fetchAll(PDO::FETCH_ASSOC);

            // 1b) 取 ntcs_tool_test 全表資料
            $toolData = $src->query("SELECT * FROM ntcs_tool_test")->fetchAll(PDO::FETCH_ASSOC);

            // 關閉來源 DB
            $src = null;

            // 2) 連到目標 DB
            $dst = idas_sqlite_connect($dstDB);
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
                $db_path = idas_path('database_root', 'KLS_NTCS_IDAS.Lin');
            } else {
                $db_path = '../KLS_NTCS_IDAS.Lin';
            }

            if (!file_exists($db_path)) {
                throw new Exception("❌ Database file not found: $db_path");
            }

            $con_db = idas_sqlite_connect($db_path);
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
            $db_path = idas_path('database_root', 'ntcs_device_IDAS.db');
            if (!file_exists($db_path)) {
                throw new Exception("❌ ntcs_tool_test 資料庫不存在: $db_path");
            }

            $pdo = idas_sqlite_connect($db_path);
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
        $cmd = 'sudo /usr/bin/php ' . escapeshellarg(idas_path('service_root', 'agent_initial.php')) . ' > /dev/null 2>&1 &';
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

        $srcDB = idas_path('controller_root', 'ntcs_device.db');

        if (!file_exists($srcDB)) {
            //error_log("⚠ ntcs_device.db 不存在");
            return null;
        }

        try {
            $src = idas_sqlite_connect($srcDB);
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

        $srcDb    = idas_path('controller_root', 'ntcs_device.db');
        $destDb   = idas_path('database_root', 'ntcs_device_IDAS.db');
        $stateFn  = idas_path('database_root', '.tool_spec_sync.json');
        $stableFn = idas_path('database_root', '.tool_spec_stable.json');

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
            $srcPdo = idas_sqlite_connect($srcDb, [
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
            $destPdo = idas_sqlite_connect($destDb, [
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

        $stateFn = idas_path('database_root', '.tool_spec_sync.json');

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

        $db = idas_sqlite_connect($dbPath);
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

        $destDb = idas_path('controller_root', 'ntcs_device.db');
        if (!is_file($destDb)) {
            return null;
        }

        try {
            $db = idas_sqlite_connect($destDb);
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
        $dbPath = idas_path('controller_root', 'ntcs_device.db');

        // ---------- 基本檢查 ----------
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            // ---------- 開啟 SQLite ----------
            $pdo = idas_sqlite_connect($dbPath);
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

class NtcsBaseController
{
    use LazyDeviceIdResolver;
    // OP protocol runtime cache: avoid reading SQLite / retrying fallback endpoints on every command.
    protected static $opProtocolCandidatesCache = null;
    protected static $opLastEndpoint = null;

    // OP runtime registers 29002~29004 are often requested together in the same page.
    // Cache them for the current PHP request so OP can use one TCP connection:
    // connect 4545 -> READ 29002 -> READ 29003 -> READ 29004 -> close.
    protected static $opRuntimeRegisterCache = null;

    // Current OP firmware does not ACK WRITE commands. Keep a proven processing gap
    // between consecutive writes to avoid overwhelming the controller command parser.
    protected const OP_MULTI_WRITE_GAP_US = 120000;

    // 載入 model
    public function model($model)
    {
        $modelFile = idas_platform_app_file('models/' . $model . '.php');
        require_once $modelFile;
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

        $viewFile = idas_platform_app_file('views/' . $view . '.php');

        if (!file_exists($viewFile)) {
            header("HTTP/1.1 404 Not Found");
            echo "View not found: {$view}";
            exit;
        }

        $languageFile = idas_platform_app_file('language/' . $data['language'] . '.php');
        if (is_file($languageFile)) {
            require_once $languageFile;
        } else {
            require_once idas_platform_app_file('language/en-us.php');
        }

        require_once '../app/views/inc/header.php';
        require_once $viewFile;
        require_once '../app/views/inc/footer.php';
    }

    public function language_auto($value='')
    {
        // 如果 $_SESSION['language'] 未設定或為空，就從瀏覽器語系帶入。
        // AJAX / wget / curl 不一定有 HTTP_ACCEPT_LANGUAGE，所以必須有預設值。
        if (!isset($_SESSION['language']) || $_SESSION['language'] == '') {
            $acceptLang = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-us');
            $lang = substr($acceptLang, 0, 5);

            if (preg_match("/zh-cn/i", $lang)) {
                $_SESSION['language'] = 'zh-cn';
            } else if (preg_match("/zh-tw/i", $lang)) {
                $_SESSION['language'] = 'zh-tw';
            } else if (preg_match("/en/i", $lang)) {
                $_SESSION['language'] = 'en-us';
            } else {
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
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $iPod = stripos($ua,"iPod");
        $iPhone = stripos($ua,"iPhone");
        $iPad = stripos($ua,"iPad");
        if(stripos($ua,"Android") && stripos($ua,"mobile")){
            $Android = true;
        }else if(stripos($ua,"Android")){
            $Android = false;
            $AndroidTablet = true;
        }else{
            $Android = false;
            $AndroidTablet = false;
        }
        $webOS = stripos($ua,"webOS");
        $BlackBerry = stripos($ua,"BlackBerry");
        $RimTablet= stripos($ua,"RIM Tablet");
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
            $con_db = idas_sqlite_connect(idas_path('database_root', 'das.db')); 
        }else{
            $con_db = idas_sqlite_connect('../data.db'); 
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
                $db_path = idas_path('database_root', 'data_device.db');
            } else {
                $db_path = '../data_device.db';
            }

            if (!file_exists($db_path)) {
                throw new Exception("❌ Database file not found: $db_path");
            }

            $con_db = idas_sqlite_connect($db_path);
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
        $srcController = idas_path('controller_root', 'ntcs_device.db');            // 控制器端 ntcs_device.db
        $tempDbPath    = idas_path('database_root', 'ntcs_device_temp.db');// iDAS 暫存
        $idasDbPath    = idas_path('database_root', 'ntcs_device_IDAS.db');// iDAS 正式用的 device DB

        // === 0) Cookie 只有在仍與 Controller DB 相同時才能使用 ===
        $cacheTtl = 10; // 秒

        if (
            !$forceRefresh &&
            isset($_COOKIE['temp_device_id'], $_COOKIE['temp_device_id_ts'])
        ) {
            $cid = (int)$_COOKIE['temp_device_id'];
            $ts  = (int)$_COOKIE['temp_device_id_ts'];

            if ($cid >= 1 && $cid <= 255 && $ts > 0 && (time() - $ts) < $cacheTtl) {
                $controllerIdNow = $this->readDeviceIdFromDb($srcController);
                if ($controllerIdNow !== null && $controllerIdNow === $cid) {
                    return $cid;
                }

                // Controller ID has changed. Never communicate with the stale
                // cached unit id during the identity transition.
                setcookie('temp_device_id', '', time() - 3600, '/', '', false, true);
                setcookie('temp_device_id_ts', '', time() - 3600, '/', '', false, true);
                unset($_COOKIE['temp_device_id'], $_COOKIE['temp_device_id_ts']);
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

                    // Do not update the iDAS identity mirror here. Check.php
                    // must still see the difference, ask for confirmation,
                    // then synchronize ID / protocol / TCP port together.
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
            $pdo = idas_sqlite_connect($dbPath);
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
     * 讀取指定 SQLite DB 的 ntcs_device_test.modbus_type。
     * 0 = MODBUS TCP, 1 = MODBUS RTU, 2 = OP Protocol
     */
    protected function readModbusTypeFromDb(string $dbPath): ?int
    {
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            $pdo = idas_sqlite_connect($dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 2000');

            $columnExists = (bool)$pdo->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'modbus_type' LIMIT 1"
            )->fetchColumn();

            if (!$columnExists) {
                return null;
            }

            $value = $pdo->query(
                "SELECT modbus_type FROM ntcs_device_test WHERE modbus_type IS NOT NULL ORDER BY rowid DESC LIMIT 1"
            )->fetchColumn();

            if ($value === false || $value === null || $value === '' || !is_numeric($value)) {
                return null;
            }

            $type = (int)$value;
            return in_array($type, [0, 1, 2], true) ? $type : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * 將 modbus_type 寫入指定 SQLite DB。
     */
    protected function writeModbusTypeToDb(string $dbPath, int $modbusType): bool
    {
        if (!in_array($modbusType, [0, 1, 2], true)) {
            return false;
        }

        if (!is_file($dbPath) || !is_readable($dbPath) || !is_writable($dbPath)) {
            return false;
        }

        try {
            $pdo = idas_sqlite_connect($dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 3000');

            $columnExists = (bool)$pdo->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'modbus_type' LIMIT 1"
            )->fetchColumn();

            if (!$columnExists) {
                return false;
            }

            $stmt = $pdo->prepare("UPDATE ntcs_device_test SET modbus_type = :type");
            $stmt->bindValue(':type', $modbusType, PDO::PARAM_INT);
            $stmt->execute();
            @chmod($dbPath, 0777);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * 取得目前控制器通訊協議。
     *
     * 注意：這裡只負責「判斷目前要使用哪一種通訊協議」，不可直接把
     * Controller 端的 modbus_type 寫回 iDAS DB。
     *
     * 原因：Check/ajax_check_device_id 需要比對
     * /home/kls/NTCS7/ntcs_device.db 與
     * /var/www/html/database/ntcs_device_IDAS.db 的 modbus_type 是否不同。
     * 如果共用通訊層在判斷協議時就先自動寫回 iDAS DB，前端就會偵測不到
     *「控制器端 MODBUS TYPE 已變更」的狀態，也就不會出現紅色 Banner / Popup。
     *
     * 正確同步時機：
     * - Check 偵測到 changed = true 後，前端顯示提醒。
     * - 再由 Check/sync_device_identity 或 ajax_force_sync_device_id 走原本同步流程。
     */
    public function get_modbus_type_from_controller(): int
    {
        $controllerDb = idas_path('controller_root', 'ntcs_device.db');
        $idasDb       = idas_path('database_root', 'ntcs_device_IDAS.db');
        $tempDb       = idas_path('database_root', 'ntcs_device_temp.db');

        // 優先以 Controller 實際 DB 為準，讓 iDAS 通訊可立即切到正確協議；
        // 但不要在這裡同步寫回 iDAS DB，避免 Check 偵測不到差異。
        $controllerType = $this->readModbusTypeFromDb($controllerDb);
        if ($controllerType !== null) {
            return $controllerType;
        }

        // Controller DB 讀不到時，再使用 iDAS DB。
        $idasType = $this->readModbusTypeFromDb($idasDb);
        if ($idasType !== null) {
            return $idasType;
        }

        // 最後才使用 temp DB。
        $tempType = $this->readModbusTypeFromDb($tempDb);
        if ($tempType !== null) {
            return $tempType;
        }

        return 0;
    }

    public function is_op_protocol_enabled(): bool
    {
        return $this->get_modbus_type_from_controller() === 2;
    }

    protected function getProtocolHost(): string
    {
        require_once '../app/config/config.php';
        $host = defined('CONTROLLER_IP') ? trim((string)CONTROLLER_IP) : '';
        return $host !== '' ? $host : '127.0.0.1';
    }

    /**
     * 從 ntcs_device_test.wifi 解析控制器連線資訊。
     * 常見格式：1_192.168.0.75_4545_255.255.255.0_192.168.0.255
     */
    protected function parseControllerWifiEndpoint($wifi, int $defaultPort = 4545): ?array
    {
        $wifi = trim((string)$wifi);
        if ($wifi === '') {
            return null;
        }

        $parts = preg_split('/[_\s,;]+/', $wifi);
        $host = null;
        $port = null;

        foreach ($parts as $idx => $part) {
            $part = trim((string)$part);
            if ($host === null && filter_var($part, FILTER_VALIDATE_IP)) {
                $host = $part;

                // IP 後面第一個合法數字通常就是 port。
                for ($j = $idx + 1; $j < count($parts); $j++) {
                    $candidate = trim((string)$parts[$j]);
                    if (ctype_digit($candidate)) {
                        $candidatePort = (int)$candidate;
                        if ($candidatePort > 0 && $candidatePort <= 65535) {
                            $port = $candidatePort;
                            break;
                        }
                    }
                }
                break;
            }
        }

        if ($host === null) {
            return null;
        }

        return [
            'host' => $host,
            'port' => $port ?: $defaultPort,
        ];
    }

    protected function readControllerWifiEndpointFromDb(string $dbPath, int $defaultPort = 4545): ?array
    {
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            $pdo = idas_sqlite_connect($dbPath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 2000');

            $columnExists = (bool)$pdo->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'wifi' LIMIT 1"
            )->fetchColumn();

            if (!$columnExists) {
                return null;
            }

            $wifi = $pdo->query(
                "SELECT wifi FROM ntcs_device_test WHERE wifi IS NOT NULL AND wifi <> '' ORDER BY rowid DESC LIMIT 1"
            )->fetchColumn();

            if ($wifi === false || $wifi === null || $wifi === '') {
                return null;
            }

            return $this->parseControllerWifiEndpoint($wifi, $defaultPort);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * OP 連線候選清單。
     *
     * OP Server 有些版本只綁定實體 IP，不一定會聽 127.0.0.1。
     * 因此 OP 不應只吃 CONTROLLER_IP，而是優先從 Controller DB 的 wifi 欄位解析
     * 例如 1_192.168.0.75_4545_255.255.255.0_192.168.0.255。
     */
    protected function getOpProtocolCandidates(?string $host = null, int $port = 4545): array
    {
        $candidates = [];
        $add = function($h, $p) use (&$candidates) {
            $h = trim((string)$h);
            $p = (int)$p;
            if ($h === '' || $p <= 0 || $p > 65535) {
                return;
            }
            $key = $h . ':' . $p;
            $candidates[$key] = ['host' => $h, 'port' => $p];
        };

        // Explicit host is used as-is. This keeps test/debug calls predictable.
        if ($host !== null && trim((string)$host) !== '') {
            $add($host, $port);
            return array_values($candidates);
        }

        // Same PHP request: put the last successful endpoint first.
        if (is_array(self::$opLastEndpoint ?? null)) {
            $add(self::$opLastEndpoint['host'] ?? '', self::$opLastEndpoint['port'] ?? 4545);
        }

        // Same PHP request: reuse the candidate list so every OP command does not re-open SQLite DBs.
        if (is_array(self::$opProtocolCandidatesCache)) {
            foreach (self::$opProtocolCandidatesCache as $endpoint) {
                $add($endpoint['host'] ?? '', $endpoint['port'] ?? 4545);
            }
            return array_values($candidates);
        }

        foreach ([
            idas_path('controller_root', 'ntcs_device.db'),
            idas_path('database_root', 'ntcs_device_IDAS.db'),
            idas_path('database_root', 'ntcs_device_temp.db'),
        ] as $dbPath) {
            $endpoint = $this->readControllerWifiEndpointFromDb($dbPath, 4545);
            if ($endpoint) {
                // wifi supplies the controller IP only. OP is always 4545 and
                // its port is intentionally not stored in the DB.
                $add($endpoint['host'], 4545);
            }
        }

        $add($this->getProtocolHost(), 4545);
        $add('127.0.0.1', 4545);

        self::$opProtocolCandidatesCache = array_values($candidates);
        return array_values($candidates);
    }

    protected function rememberOpEndpoint(string $host, int $port): void
    {
        if ($host !== '' && $port > 0 && $port <= 65535) {
            self::$opLastEndpoint = ['host' => $host, 'port' => $port];
        }
    }

    protected function setOpStreamTimeout($client, float $seconds): void
    {
        $seconds = max(0.05, $seconds);
        $sec = (int)floor($seconds);
        $usec = (int)(($seconds - $sec) * 1000000);
        @stream_set_timeout($client, $sec, $usec);
    }

    protected function readOpStreamLine($client, int $maxBytes = 8192): string
    {
        $response = '';
        while (!feof($client) && strlen($response) < $maxBytes) {
            $chunk = fread($client, 1024);
            if ($chunk === false || $chunk === '') {
                break;
            }
            $response .= $chunk;
            if (strpos($response, "\n") !== false || strpos($response, "\r") !== false) {
                break;
            }
        }
        return trim($response);
    }

    protected function openOpClient(string $host, int $port, float $connectTimeout)
    {
        $errno = 0;
        $errstr = '';
        return @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            max(0.05, $connectTimeout),
            STREAM_CLIENT_CONNECT
        );
    }

    /**
     * Write the complete ASCII OP command. fwrite() may legally write only part of
     * a buffer, so treating any positive byte count as success can truncate commands.
     */
    protected function writeOpPayload($client, string $payload): bool
    {
        $length = strlen($payload);
        $offset = 0;

        while ($offset < $length) {
            $written = @fwrite($client, substr($payload, $offset));
            if ($written === false || $written <= 0) {
                return false;
            }
            $offset += $written;
        }

        @fflush($client);
        return true;
    }

    protected function normalizeModbusResponseToRegisters($raw): array
    {
        if (is_string($raw)) {
            $values = @unpack('n*', $raw);
            return $values ? array_values($values) : [];
        }

        if (!is_array($raw)) {
            return [];
        }

        if ($raw === []) {
            return [];
        }

        $raw = array_values(array_map('intval', $raw));

        // phpmodbus 常見回傳為 byte array；若數量為偶數且每個值 <= 255，轉成 16-bit word。
        $isByteArray = (count($raw) % 2 === 0 && max($raw) <= 0xFF);
        if ($isByteArray) {
            $words = [];
            for ($i = 0; $i + 1 < count($raw); $i += 2) {
                $words[] = (($raw[$i] & 0xFF) << 8) | ($raw[$i + 1] & 0xFF);
            }
            return $words;
        }

        return $raw;
    }

    public function op_send(string $command, ?string $host = null, int $port = 4545, float $connectTimeout = 1.0, float $readTimeout = 1.0): array
    {
        $command = strtoupper(trim($command));
        $isWriteCommand = (strpos($command, 'IDAS_WRITE_') === 0);

        $baseResult = [
            'ok' => false,
            'command' => $command,
            'host' => $host ?: '',
            'port' => $port,
            'response' => '',
            'parsed' => null,
            'error' => '',
            'attempts' => [],
        ];

        if ($command === '') {
            $baseResult['error'] = 'OP command is empty';
            return $baseResult;
        }

        $candidates = $this->getOpProtocolCandidates($host, $port);
        if (empty($candidates)) {
            $baseResult['error'] = 'No OP endpoint candidate';
            return $baseResult;
        }

        foreach ($candidates as $endpoint) {
            $tryHost = (string)$endpoint['host'];
            $tryPort = (int)$endpoint['port'];

            $result = $baseResult;
            $result['host'] = $tryHost;
            $result['port'] = $tryPort;
            $result['attempts'] = [];

            $client = $this->openOpClient($tryHost, $tryPort, $connectTimeout);

            if (!$client) {
                $result['error'] = 'TCP connect failed';
                $baseResult['attempts'][] = [
                    'host' => $tryHost,
                    'port' => $tryPort,
                    'ok' => false,
                    'error' => $result['error'],
                ];
                continue;
            }

            try {
                $this->setOpStreamTimeout($client, $readTimeout);
                $payload = $command . (substr($command, -1) === "\n" ? '' : "\n");

                if (!$this->writeOpPayload($client, $payload)) {
                    $result['error'] = 'TCP write failed';
                    $baseResult['attempts'][] = [
                        'host' => $tryHost,
                        'port' => $tryPort,
                        'ok' => false,
                        'error' => $result['error'],
                    ];
                    continue;
                }

                /*
                 * OP WRITE is fire-and-forget on current controller firmware:
                 *   - READ returns NTCS_RETURN_xxx_x
                 *   - WRITE accepts the ASCII command but does not reply.
                 * Do not wait for read timeout after WRITE. This is the biggest speed gain.
                 */
                if ($isWriteCommand) {
                    $this->rememberOpEndpoint($tryHost, $tryPort);
                    $result['ok'] = true;
                    $result['response'] = '';
                    $result['parsed'] = [
                        'type' => 'WRITE_NO_RESPONSE',
                        'address' => null,
                        'value' => null,
                        'raw' => '',
                    ];
                    $result['accepted_no_response'] = true;
                    $result['write_sent_only'] = true;
                    $result['error'] = '';
                    $baseResult['attempts'][] = [
                        'host' => $tryHost,
                        'port' => $tryPort,
                        'ok' => true,
                        'accepted_no_response' => true,
                        'write_sent_only' => true,
                    ];
                    $result['attempts'] = $baseResult['attempts'];
                    return $result;
                }

                $response = $this->readOpStreamLine($client);
                $meta = stream_get_meta_data($client);

                if ($response === '') {
                    $result['error'] = !empty($meta['timed_out']) ? 'TCP read timeout' : 'OP empty response';
                    $baseResult['attempts'][] = [
                        'host' => $tryHost,
                        'port' => $tryPort,
                        'ok' => false,
                        'error' => $result['error'],
                    ];
                    continue;
                }

                $this->rememberOpEndpoint($tryHost, $tryPort);
                $result['ok'] = true;
                $result['response'] = $response;
                $result['parsed'] = $this->parse_op_return($response);
                $baseResult['attempts'][] = [
                    'host' => $tryHost,
                    'port' => $tryPort,
                    'ok' => true,
                    'response' => $response,
                ];
                $result['attempts'] = $baseResult['attempts'];
                return $result;
            } catch (Throwable $e) {
                $result['error'] = $e->getMessage();
                $baseResult['attempts'][] = [
                    'host' => $tryHost,
                    'port' => $tryPort,
                    'ok' => false,
                    'error' => $result['error'],
                ];
                continue;
            } finally {
                if (is_resource($client)) {
                    fclose($client);
                }
            }
        }

        $baseResult['error'] = 'All OP endpoints failed';
        if (!empty($baseResult['attempts'])) {
            $last = end($baseResult['attempts']);
            if (!empty($last['error'])) {
                $baseResult['error'] .= ': ' . $last['error'];
            }
        }

        return $baseResult;
    }

    protected function op_read_registers_one_socket(int $startAddress, int $quantity, float $connectTimeout = 1.0, float $readTimeout = 1.0): ?array
    {
        if ($quantity <= 0) {
            return [];
        }

        $candidates = $this->getOpProtocolCandidates(null, 4545);
        foreach ($candidates as $endpoint) {
            $tryHost = (string)($endpoint['host'] ?? '');
            $tryPort = (int)($endpoint['port'] ?? 4545);
            if ($tryHost === '') {
                continue;
            }

            $client = $this->openOpClient($tryHost, $tryPort, $connectTimeout);
            if (!$client) {
                continue;
            }

            $values = [];
            $ok = true;

            try {
                $this->setOpStreamTimeout($client, $readTimeout);
                for ($i = 0; $i < $quantity; $i++) {
                    $address = $startAddress + $i;
                    $command = "IDAS_READ_{$address}\n";
                    if (!$this->writeOpPayload($client, $command)) {
                        $ok = false;
                        break;
                    }

                    $response = $this->readOpStreamLine($client);
                    if ($response === '') {
                        $ok = false;
                        break;
                    }

                    $parsed = $this->parse_op_return($response);
                    if (!is_array($parsed) || ($parsed['type'] ?? '') !== 'RETURN') {
                        $ok = false;
                        break;
                    }
                    if ((int)($parsed['address'] ?? -1) !== $address) {
                        $ok = false;
                        break;
                    }

                    $value = $parsed['value'] ?? null;
                    if (!is_numeric($value)) {
                        $ok = false;
                        break;
                    }
                    $values[] = (int)$value;
                }
            } catch (Throwable $e) {
                $ok = false;
            } finally {
                if (is_resource($client)) {
                    fclose($client);
                }
            }

            if ($ok && count($values) === $quantity) {
                $this->rememberOpEndpoint($tryHost, $tryPort);
                return $values;
            }
        }

        return null;
    }

    public function parse_op_return(string $rawResponse): ?array
    {
        $rawResponse = strtoupper(trim($rawResponse));
        if ($rawResponse === '') {
            return null;
        }

        if (preg_match('/^NTCS_RETURN_(\d+)_(.+)$/', $rawResponse, $matches)) {
            return [
                'type' => 'RETURN',
                'address' => (int)$matches[1],
                'value' => $matches[2],
                'raw' => $rawResponse,
            ];
        }

        if (preg_match('/^NTCS_([A-Z]+)_(.*)$/', $rawResponse, $matches)) {
            return [
                'type' => $matches[1],
                'address' => null,
                'value' => $matches[2],
                'raw' => $rawResponse,
            ];
        }

        if (in_array($rawResponse, ['OK', 'SUCCESS', 'WRITE_OK', 'NTCS_OK'], true)) {
            return [
                'type' => 'OK',
                'address' => null,
                'value' => null,
                'raw' => $rawResponse,
            ];
        }

        return [
            'type' => 'UNKNOWN',
            'address' => null,
            'value' => null,
            'raw' => $rawResponse,
        ];
    }

    public function op_read(int $address): ?int
    {
        $result = $this->op_send("IDAS_READ_{$address}");
        if (empty($result['ok'])) {
            return null;
        }

        $parsed = $result['parsed'] ?? null;
        if (!is_array($parsed) || ($parsed['type'] ?? '') !== 'RETURN') {
            return null;
        }

        if ((int)($parsed['address'] ?? -1) !== $address) {
            return null;
        }

        $value = $parsed['value'] ?? null;
        return is_numeric($value) ? (int)$value : null;
    }

    /**
     * Runtime status block used by login/tool/firmware status.
     * First access to any address in 29002~29004 reads all three on one OP socket,
     * then later calls in the same request reuse the values. If the controller closes
     * the socket after each command, fall back to the original single-read behavior.
     */
    protected function op_read_runtime_register(int $address): ?int
    {
        if ($address < 29002 || $address > 29004) {
            return $this->op_read($address);
        }

        if (is_array(self::$opRuntimeRegisterCache)) {
            return array_key_exists($address, self::$opRuntimeRegisterCache)
                ? self::$opRuntimeRegisterCache[$address]
                : null;
        }

        // false means the one-socket optimization already failed in this request.
        if (self::$opRuntimeRegisterCache !== false) {
            $values = $this->op_read_registers_one_socket(29002, 3);
            if (is_array($values) && count($values) === 3) {
                self::$opRuntimeRegisterCache = [
                    29002 => (int)$values[0],
                    29003 => (int)$values[1],
                    29004 => (int)$values[2],
                ];
                return self::$opRuntimeRegisterCache[$address];
            }
            self::$opRuntimeRegisterCache = false;
        }

        return $this->op_read($address);
    }

    public function op_write(int $address, $value): bool
    {
        $value = is_numeric($value) ? (int)$value : strtoupper(trim((string)$value));
        $result = $this->op_send("IDAS_WRITE_{$address}_{$value}");

        if (empty($result['ok'])) {
            return false;
        }

        $parsed = $result['parsed'] ?? null;
        if (!is_array($parsed)) {
            return !empty($result['accepted_no_response'])
                || trim((string)($result['response'] ?? '')) !== '';
        }

        $type = (string)($parsed['type'] ?? '');
        return in_array($type, ['RETURN', 'OK', 'SUCCESS', 'WRITE', 'WRITE_NO_RESPONSE'], true);
    }

    public function protocol_read_register(int $unitId, int $address): ?int
    {
        if ($this->is_op_protocol_enabled()) {
            return $this->op_read_runtime_register($address);
        }

        require_once '../app/config/config.php';
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        try {
            $modbus = new ModbusMaster($this->getProtocolHost(), 'TCP');
            $modbus->port = 502;
            $modbus->timeout_sec = 10;
            $raw = $modbus->readMultipleRegisters($unitId, $address, 1);
            $words = $this->normalizeModbusResponseToRegisters($raw);
            return $words[0] ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function protocol_read_registers(int $unitId, int $startAddress, int $quantity): array
    {
        if ($quantity <= 0) {
            return [];
        }

        if ($this->is_op_protocol_enabled()) {
            // OP protocol can only send one command at a time.
            // To improve speed, keep one TCP connection and send/read one command sequentially.
            $values = $this->op_read_registers_one_socket($startAddress, $quantity);
            if ($values !== null) {
                return $values;
            }

            // Fallback for older OP servers that close the socket after every command.
            $values = [];
            for ($i = 0; $i < $quantity; $i++) {
                $value = $this->op_read($startAddress + $i);
                if ($value === null) {
                    throw new RuntimeException('OP read failed at address ' . ($startAddress + $i));
                }
                $values[] = $value;
            }
            return $values;
        }

        require_once '../app/config/config.php';
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $modbus = new ModbusMaster($this->getProtocolHost(), 'TCP');
        $modbus->port = 502;
        $modbus->timeout_sec = 10;
        $raw = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);
        return $this->normalizeModbusResponseToRegisters($raw);
    }

    public function protocol_write_register(int $unitId, int $address, $value): bool
    {
        if ($this->is_op_protocol_enabled()) {
            return $this->op_write($address, $value);
        }

        require_once '../app/config/config.php';
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        try {
            $modbus = new ModbusMaster($this->getProtocolHost(), 'TCP');
            $modbus->port = 502;
            $modbus->timeout_sec = 10;
            $modbus->writeMultipleRegister($unitId, $address, [(int)$value], ['INT']);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function protocol_write_registers(int $unitId, int $startAddress, array $values): bool
    {
        if ($values === []) {
            return true;
        }

        if ($this->is_op_protocol_enabled()) {
            $opValues = array_values($values);
            $lastIndex = count($opValues) - 1;

            foreach ($opValues as $offset => $value) {
                if (!$this->op_write($startAddress + $offset, $value)) {
                    return false;
                }

                // OP WRITE is fire-and-forget on current firmware. A short gap between
                // consecutive commands is required so a burst (for example R506~R521)
                // is not accepted by TCP faster than the controller can process it.
                if ($offset < $lastIndex) {
                    usleep(self::OP_MULTI_WRITE_GAP_US);
                }
            }
            return true;
        }

        require_once '../app/config/config.php';
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        try {
            $modbus = new ModbusMaster($this->getProtocolHost(), 'TCP');
            $modbus->port = 502;
            $modbus->timeout_sec = 10;
            $types = array_fill(0, count($values), 'INT');
            $modbus->writeMultipleRegister($unitId, $startAddress, array_values($values), $types);
            return true;
        } catch (Throwable $e) {
            return false;
        }
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
        
        $tempDbPath = idas_path('database_root', 'ntcs_device_temp.db');
        $idasDbPath = idas_path('database_root', 'ntcs_device_IDAS.db');

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
            $pdoIdas = idas_sqlite_connect($idasDbPath);
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
    //判斷控制器的登入登出
    public function idas_check($device_id){

        require_once '../app/config/config.php';

        $ip = CONTROLLER_IP;
        $startAddress = 29002;
        $response = ['result' => null, 'error' => ''];

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $response['error'] = "無效的 IP 位址：$ip";
            return $response;
        }

        $unitId = (int)$device_id;
        if ($unitId < 1 || $unitId > 255) {
            $unitId = 1;
        }

        try {
            $value = $this->protocol_read_register($unitId, $startAddress);
            if ($value === null) {
                throw new RuntimeException('Protocol read failed');
            }
            $response['result'] = $value;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }

        return $response;
    }



    public function get_tools_version($unitId){
        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;
        return $this->protocol_read_register($unitId, 29003);
    }

    


    public function get_firmware_version($unitId){
        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;
        return $this->protocol_read_register($unitId, 29004);
    }



    public function get_db_sync($unitId){
        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;
        $response = ['result' => null, 'error' => ''];
        try {
            $value = $this->protocol_read_register($unitId, 29006);
            if ($value === null) {
                throw new RuntimeException('Protocol read failed');
            }
            $response['result'] = $value;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }
        return json_encode($response);
    }



    
    public function get_operation_id(){
        $response = ['result' => null, 'error' => ''];
        try {
            $value = $this->protocol_read_register(1, 4165);
            if ($value === null) {
                throw new RuntimeException('Protocol read failed');
            }
            $response['result'] = $value;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }
        return json_encode($response);
    }



    public function get_data_info(){
        $response = ['result' => null, 'error' => ''];
        try {
            $values = $this->protocol_read_registers(1, 4097, 64);
            $response['result'] = $values;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }
        echo json_encode($response);
    }



    //起子sn
    public function get_tools_sn($unitId = 1) {

        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;

        $startAddress = 4122;
        $quantity = 10;

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
            $regs = $this->protocol_read_registers($unitId, $startAddress, $quantity);
            if (!is_array($regs) || empty($regs)) {
                throw new RuntimeException('No data returned from protocol');
            }

            $asciiBE = $regsToAscii($regs, 'BE', true, true);
            $asciiLE = $regsToAscii($regs, 'LE', true, true);

            $response['ok']            = true;
            $response['raw_registers'] = array_map('intval', $regs);
            $response['ascii_be']      = $asciiBE;
            $response['ascii_le']      = $asciiLE;
            $response['model']         = $asciiBE;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }

        return $response;
    }



    public function get_controller_sn($unitId = 1) {

        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;

        $startAddress = 4102;
        $quantity = 10;

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
            $regs = $this->protocol_read_registers($unitId, $startAddress, $quantity);
            if (!is_array($regs) || empty($regs)) {
                throw new RuntimeException('No data returned from protocol');
            }

            $asciiBE = $regsToAscii($regs, 'BE', true, true);
            $asciiLE = $regsToAscii($regs, 'LE', true, true);

            $response['ok']            = true;
            $response['raw_registers'] = array_map('intval', $regs);
            $response['ascii_be']      = $asciiBE;
            $response['ascii_le']      = $asciiLE;
            $response['model']         = $asciiBE;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }

        return $response;
    }


    //起子型號
    public function get_tools_type($unitId = 1) {

        $unitId = (int)$unitId;
        if ($unitId < 1 || $unitId > 255) $unitId = 1;

        $startAddress = 4112;
        $quantity = 10;

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
            $regs = $this->protocol_read_registers($unitId, $startAddress, $quantity);
            if (!is_array($regs) || empty($regs)) {
                throw new RuntimeException('No data returned from protocol');
            }

            $asciiBE = $regsToAscii($regs, 'BE', true, true);
            $asciiLE = $regsToAscii($regs, 'LE', true, true);

            $response['ok']            = true;
            $response['raw_registers'] = array_map('intval', $regs);
            $response['ascii_be']      = $asciiBE;
            $response['ascii_le']      = $asciiLE;
            $response['model']         = $asciiBE;
        } catch (Throwable $e) {
            $response['error'] = $e->getMessage() ?: '通訊失敗';
        }

        return $response;
    }



    public function ntcs_data_db_sysnc() {
        $this->sync_db(
            idas_path('controller_root', 'ntcs_data.db'),
            idas_path('database_root', 'ntcs_data.db')
        );

        $this->sync_ntcs_tool_data();
    }




    public function ntcs_device_db_load() {
        $this->sync_db(
            idas_path('controller_root', 'ntcs_device.db'),
            idas_path('database_root', 'ntcs_device_IDAS.db')
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
        $srcDB = idas_path('controller_root', 'ntcs_device.db');
        $dstDB = idas_path('database_root', 'ntcs_device_IDAS.db');

        if (!file_exists($srcDB) || !file_exists($dstDB)) {
            return;
        }

        try {
            // 1) 連到來源 DB
            $src = idas_sqlite_connect($srcDB);
            $src->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // 1a) 取 device_id 和 device_version
            $deviceInfo = $src->query("SELECT device_id, device_version FROM ntcs_device_test")->fetchAll(PDO::FETCH_ASSOC);

            // 1b) 取 ntcs_tool_test 全表資料
            $toolData = $src->query("SELECT * FROM ntcs_tool_test")->fetchAll(PDO::FETCH_ASSOC);

            // 關閉來源 DB
            $src = null;

            // 2) 連到目標 DB
            $dst = idas_sqlite_connect($dstDB);
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
                $db_path = idas_path('database_root', 'KLS_NTCS_IDAS.Lin');
            } else {
                $db_path = '../KLS_NTCS_IDAS.Lin';
            }

            if (!file_exists($db_path)) {
                throw new Exception("❌ Database file not found: $db_path");
            }

            $con_db = idas_sqlite_connect($db_path);
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
            $db_path = idas_path('database_root', 'ntcs_device_IDAS.db');
            if (!file_exists($db_path)) {
                throw new Exception("❌ ntcs_tool_test 資料庫不存在: $db_path");
            }

            $pdo = idas_sqlite_connect($db_path);
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
        $cmd = 'sudo /usr/bin/php ' . escapeshellarg(idas_path('service_root', 'agent_initial.php')) . ' > /dev/null 2>&1 &';
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

        $srcDB = idas_path('controller_root', 'ntcs_device.db');

        if (!file_exists($srcDB)) {
            //error_log("⚠ ntcs_device.db 不存在");
            return null;
        }

        try {
            $src = idas_sqlite_connect($srcDB);
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

        $srcDb    = idas_path('controller_root', 'ntcs_device.db');
        $destDb   = idas_path('database_root', 'ntcs_device_IDAS.db');
        $stateFn  = idas_path('database_root', '.tool_spec_sync.json');
        $stableFn = idas_path('database_root', '.tool_spec_stable.json');

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
            $srcPdo = idas_sqlite_connect($srcDb, [
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
            $destPdo = idas_sqlite_connect($destDb, [
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

        $stateFn = idas_path('database_root', '.tool_spec_sync.json');

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

        $db = idas_sqlite_connect($dbPath);
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

        $destDb = idas_path('controller_root', 'ntcs_device.db');
        if (!is_file($destDb)) {
            return null;
        }

        try {
            $db = idas_sqlite_connect($destDb);
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
        $dbPath = idas_path('controller_root', 'ntcs_device.db');

        // ---------- 基本檢查 ----------
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            // ---------- 開啟 SQLite ----------
            $pdo = idas_sqlite_connect($dbPath);
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

/*
 * Deterministic platform base-controller alias.
 * Both implementations are declared unconditionally. Platform selection
 * only chooses which implementation is exposed as Controller.
 */
$selectedControllerBase = idas_is_icontroller()
    ? 'IControllerBaseController'
    : 'NtcsBaseController';

if (!class_exists($selectedControllerBase, false)) {
    throw new RuntimeException('Selected iDAS base Controller implementation is unavailable: ' . $selectedControllerBase);
}

if (!class_exists('Controller', false)) {
    class_alias($selectedControllerBase, 'Controller');
}

if (!class_exists('Controller', false)) {
    throw new RuntimeException('iDAS Controller alias initialization failed.');
}
