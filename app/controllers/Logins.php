<?php

class Logins extends Controller
{
    private $AdminModel;
    private $LoginModel;
    private $stepModel;
    Private $deviceId;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->LoginModel = $this->model('Login');
        $this->stepModel = $this->model('Steptcc');
        $this->AdminModel = $this->model('Admin');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();

    }

    private function isAccountUserApiRequest($url): bool
    {
        return isset($url[0], $url[1])
            && $url[0] === 'Settings'
            && in_array($url[1], [
                'account_user_list',
                'account_user_create',
                'account_user_update',
                'account_user_delete'
            ], true);
    }

    private function sendLoginJson(bool $ok, string $msg, array $extra = []): void
    {
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


    /**
     * QR Code login payload support.
     * 支援 QR 內容：{"usr":"abcd123","pwd":"0734"}
     * 也支援部分掃碼槍輸出格式：usr=abcd123,pwd=0734 / usr:abcd123,pwd:0734。
     * 最後統一轉成既有 username / password 登入流程。
     */
    private function normalizeQrLoginPost(): void
    {
        $qrData = [];

        if (!empty($_POST['qr_payload']) && is_string($_POST['qr_payload'])) {
            $qrData = $this->parseQrLoginPayload((string)$_POST['qr_payload']);
            if (is_array($qrData)) {
                // 支援 QR key 大小寫不同，例如 USR/PWD、User/Pass。
                $qrData = array_change_key_case($qrData, CASE_LOWER);
            }
        }

        if (empty($_POST['username'])) {
            if (!empty($qrData['usr'])) {
                $_POST['username'] = $qrData['usr'];
            } elseif (!empty($qrData['username'])) {
                $_POST['username'] = $qrData['username'];
            } elseif (!empty($qrData['user'])) {
                $_POST['username'] = $qrData['user'];
            } elseif (!empty($qrData['name'])) {
                $_POST['username'] = $qrData['name'];
            } elseif (!empty($qrData['account'])) {
                $_POST['username'] = $qrData['account'];
            } elseif (isset($_POST['usr'])) {
                $_POST['username'] = $_POST['usr'];
            } elseif (isset($_POST['USR'])) {
                $_POST['username'] = $_POST['USR'];
            }
        }

        if (empty($_POST['password'])) {
            if (!empty($qrData['pwd'])) {
                $_POST['password'] = $qrData['pwd'];
            } elseif (!empty($qrData['password'])) {
                $_POST['password'] = $qrData['password'];
            } elseif (!empty($qrData['pass'])) {
                $_POST['password'] = $qrData['pass'];
            } elseif (isset($_POST['pwd'])) {
                $_POST['password'] = $_POST['pwd'];
            } elseif (isset($_POST['PWD'])) {
                $_POST['password'] = $_POST['PWD'];
            }
        }
    }

    /**
     * 掃碼槍內容容錯解析：
     * 1. 標準 JSON：{"usr":"abcd123","pwd":"0734"}
     * 2. 單引號：{'usr':'abcd123','pwd':'0734'}
     * 3. key/value：usr=abcd123,pwd=0734 或 usr:abcd123,pwd:0734
     * 4. URL encoded：%7B%22usr%22...
     */
    private function parseQrLoginPayload(string $raw): array
    {
        $payload = trim($raw);

        // 移除 BOM / 控制字元，並把常見全形符號、智慧引號轉回標準符號。
        $payload = preg_replace('/^\xEF\xBB\xBF/', '', $payload) ?? $payload;
        $payload = preg_replace('/[\x00-\x1F\x7F]/u', '', $payload) ?? $payload;
        $payload = strtr($payload, [
            '“' => '"',
            '”' => '"',
            '＂' => '"',
            '‘' => "'",
            '’' => "'",
            '｛' => '{',
            '｝' => '}',
            '：' => ':',
            '，' => ',',
        ]);
        $payload = trim($payload);

        // 部分掃碼槍/中介軟體會輸出 URL encoded 字串。
        if (stripos($payload, '%7B') !== false || stripos($payload, '%22') !== false || stripos($payload, '%3A') !== false) {
            $decoded = rawurldecode($payload);
            if (is_string($decoded) && $decoded !== '') {
                $payload = trim($decoded);
            }
        }

        // 若前後帶入其他文字，只擷取 JSON 區段。
        $jsonStart = strpos($payload, '{');
        $jsonEnd   = strrpos($payload, '}');
        $jsonPayload = $payload;
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd >= $jsonStart) {
            $jsonPayload = substr($payload, $jsonStart, $jsonEnd - $jsonStart + 1);
        }

        $json = json_decode($jsonPayload, true);
        if (is_array($json)) {
            return $json;
        }

        // 兼容單引號 JSON。
        $singleQuoteJson = str_replace("'", '"', $jsonPayload);
        $json = json_decode($singleQuoteJson, true);
        if (is_array($json)) {
            return $json;
        }

        // 兼容 usr=abcd123,pwd=0734 或 usr:abcd123,pwd:0734。
        $usr = null;
        $pwd = null;
        if (preg_match('/(?:usr|username|user)\s*[:=]\s*["\']?([^"\',;\s}]+)/i', $payload, $m)) {
            $usr = $m[1];
        }
        if (preg_match('/(?:pwd|password|pass)\s*[:=]\s*["\']?([^"\',;\s}]+)/i', $payload, $m)) {
            $pwd = $m[1];
        }

        if ($usr !== null && $pwd !== null) {
            return [
                'usr' => $usr,
                'pwd' => $pwd,
            ];
        }

        return [];
    }


    public function index($url){

        //先做資料庫檔案完整性檢查
        $repairResult = $this->checkAndRepairDatabaseFiles();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['sessionid'] = session_id();
        $_SESSION['privilege'] = '';
        $error_message = '';
        $authToken = '';
        $account = $this->LoginModel->get_account();


        $targetDir = '/var/www/html/extracted';
        $this->deleteDirectory($targetDir);

       
        $data = [
            'error_message' => $error_message,
            'account' => $account
        ];

        //例外狀況，切換語系
        $exception = false;
        if(isset($url[1])){
            if($url[0] == 'Dashboards' && $url[1] == 'change_language' ){
                $exception = true;
            }
        }

        // QR Code login: 先把 qr_payload / usr / pwd 正規化成 username / password。
        $this->normalizeQrLoginPost();

        // QR / 手動 Login 使用 AJAX 驗證成功後，前端顯示 3 秒成功動畫再跳頁。
        $isAjaxLogin = !empty($_POST['ajax_login']) || !empty($_POST['qr_ajax_login']);

        // Account API 必須回 JSON，不可以回登入頁 HTML，否則前端 JSON.parse 會失敗
        if ($this->isAccountUserApiRequest($url)) {
            if ($this->isAuthenticated()) {
                return true;
            }
            $this->sendLoginJson(false, 'Login expired. Please login again.');
        }

        //判斷有沒有post password
        //有post就驗證password
        //沒有就單純檢查cookies
        if( !empty($_POST['password']) && isset($_POST['password'])  ){
            //login attempt
            $_POST['username'] = trim((string)($_POST['username'] ?? ''));
            $_POST['password'] = trim((string)($_POST['password'] ?? ''));
        
            $this->logLoginAttempt();

            $username = $_POST['username'];
            $password = $_POST['password'];
            $authToken = hash('sha256', $password);
            

            
            if($this->verifyCredentials($username,$authToken)){
                                
                // 同步控制器資料庫（ntcs_data.db）至 iDAS
                $this->ntcs_data_db_sysnc();
                $this->set_ver();

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


                if (PHP_OS_FAMILY === 'Linux') {
                    $dir = '/mnt/ramdisk/ftp';

                    if (@chmod($dir, 0777)) {
                        // chmod 成功即可，不要在 redirect 前 echo，避免 headers already sent。
                    } else {
                        // 如果 PHP chmod 失敗 → 改用 sudo
                        $cmd = 'sudo chmod 777 ' . escapeshellarg($dir);
                        $output = shell_exec($cmd . ' 2>&1');

                        // 檢查結果
                        clearstatcache(); // 清快取
                        $perms = substr(sprintf('%o', fileperms($dir)), -4);
                        if ($perms == '0777') {
                            /*echo json_encode([
                                "status" => "success",
                                "message" => "✅ sudo chmod 成功"
                            ]);*/
                        } else {
                            if ($isAjaxLogin) {
                                $this->sendLoginJson(false, "sudo chmod failed: " . (string)$output);
                            }

                            echo json_encode([
                                "status" => "error",
                                "message" => "❌ sudo chmod 失敗，結果：" . $output
                            ]);
                        }
                    }
                }


                setcookie('username', $username, time() + 600, '/');
                setcookie('auth_token', $authToken, time() + 600, '/');

                if ($isAjaxLogin) {
                    $this->sendLoginJson(true, 'Login success.', [
                        'redirect_url' => '/idas/public/?url=Dashboards'
                    ]);
                }

                header('Location: /idas/public/?url=Dashboards');
                exit;
            }else{
                // 用戶未登錄或身份驗證超時，跳轉到登錄頁面
                $this->logout();

                if ($isAjaxLogin) {
                    $this->sendLoginJson(false, 'Username or password is incorrect.');
                }

                $this->view('login/index', $data);
                exit();
            }

        }else{

            if ($this->isAuthenticated() || $exception ) { //切換語系例外
                // 用戶已登錄，繼續處理其他操作
                return true;
            } else {
                // 用戶未登錄或身份驗證超時，跳轉到登錄頁面
                $this->logout();
                $this->view('login/index', $data);
                exit();
            }
        }

    }

    public function isAuthenticated() {
        if (isset($_COOKIE['auth_token']) && isset($_COOKIE['username'])) {
            $authToken = $_COOKIE['auth_token'];
            
            // 解密和驗證令牌的有效性，根據需要進行自定義驗證
            $username = $_COOKIE['username'];
            $valid_check = $this->verifyCredentials($username,$authToken);

            if ($valid_check !== false) {
                // 令牌有效，可以根據需要刷新 Cookie 的過期時間
                setcookie('username', $username, time() + 600, '/');
                setcookie('auth_token', $authToken, time() + 600, '/');
                return true;
            }
        }

        return false;
    }


    public function logout() {
        setcookie('username', '', time() - 3600, '/');
        setcookie('auth_token', '', time() - 3600, '/');

    }

    // 验证用户提交的用户名和密码
    public function verifyCredentials($username, $authToken) {
        $pwd = $this->LoginModel->getpwd($username);

        // 找不到帳號或 DB 讀取失敗時，直接驗證失敗，不輸出 Notice。
        if (!$pwd || !is_array($pwd) || !isset($pwd['passwd'])) {
            return false;
        }

        $input  = $authToken;
        $output = hash('sha256', trim((string)$pwd['passwd']));

        if ($input == $output) {
            // 登入成功寫入 active_sessions 資料庫。
            // 注意：active_sessions 寫入失敗不應被誤判為帳號密碼錯誤，避免 QR 登入顯示錯誤訊息。
            $sessionOk = $this->active_sessions((string)$username);
            if (!$sessionOk) {
                error_log('[LOGIN] active_sessions write failed for user: ' . (string)$username);
            }

            $_SESSION['privilege'] = 'admin';
            return true;
        }

        return false;
    }

    public function logLoginAttempt()
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }else{
            $ip = $_SERVER["REMOTE_ADDR"];
        }
        $this->LoginModel->logLoginAttempt($ip);
    }
    
    public function active_sessions($username)
    {
        //0.先清理過期的session
        //1.先確認是否達連線上限
        //2.如果已達連線上限，回傳false
        //3.如果未達連線上限，寫入db
        //4.檢查session id是否存在
        //5.如果存在update time
        //6.如果不存在insert
        //$max_concurrent_users = $this->Max_User();//連線數量限制
        $session_id = session_id();

        if (!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }else{
            $ip = $_SERVER["REMOTE_ADDR"];
        }

        //清理過期的session
        $this->LoginModel->cleanExpiredSessions();
        //確認目前連線數量，排除目前的session_id
        $concurrent_users = $this->LoginModel->GetConcurrentUsers($session_id);

        if( $username == 'guest'){
            $this->Users_Uplimit();
            return false;
        }else{
            $this->LoginModel->active_sessions($username,$session_id,$ip);
            return true;
        }

    }

    //連線數達到上限時，直接從這邊跳回登入畫面，並帶error message
    public function Users_Uplimit()
    {
        $error_message = '連線數已達上限';
        $authToken = '';
        $iDas_Vesion = $this->AdminModel->Get_Das_Config('idas_version');
        $data = [
            'error_message' => $error_message,
            'iDas_Vesion' => $iDas_Vesion,
        ];

        $this->logout();
        $this->view('login/index', $data);
        exit();
    }

    public function Max_User()
    {
        $reslut = $this->LoginModel->get_max_user();
        return $reslut;
    }

    public function Activation_Check()
    {
        //判斷是否已授權，如果未授權就導回登入頁
        $auth_status = $this->AdminModel->Get_Das_Config('activate_status');
        $iDas_Vesion = $this->AdminModel->Get_Das_Config('idas_version');
        $data = [
            'error_message' => '',
            'iDas_Vesion' => $iDas_Vesion,
        ];

        if($auth_status == 0){//未授權
            $data['error_message'] = 'inactive';
            $this->logout();
            $this->view('login/index', $data);
            exit();
        }else if($auth_status == 1){//試用版，要再判斷到期日
            // date_default_timezone_set('UTC');
            $expired_date = $this->AdminModel->Get_Das_Config('expired_date');
            $today = date("Y-m-d");
            if($today > $expired_date){
                $data['error_message'] = 'expired';
                $this->logout();
                $this->view('login/index', $data);
                exit();
            }else{
                return true;
            }

        }else if($auth_status == 2){//永久授權
            return true;
        }else{
            $data['error_message'] = 'inactive';
            $this->logout();
            $this->view('login/index', $data);
            exit();
        }
    }


    public function deleteDirectory($dir) {

        if (!file_exists($dir)) return true;
        if (!is_dir($dir)) return unlink($dir);

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') continue;
            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
        }

        return rmdir($dir);
    }

    // #IDAS上傳 20250624 修改：僅保留步驟 10 與 12
    public function set_ver($debug = false) {

        $info_json_url ='/var/www/html/idas/info.json';
        $verify_data = json_decode(@file_get_contents($info_json_url), true);

        $iDas_Version = $this->AdminModel->Get_Das_Config('idas_version');
        $this->AdminModel->Set_Das_Config('idas_version', $verify_data['idas_version']);  
    }


    /**
     * 檢查 database 目錄底下 IDAS 檔案是否為 0KB
     * 若為 0KB 或不存在，則從 controller 端複製來源檔案覆蓋
     */
    private function checkAndRepairDatabaseFiles(): array
    {
        $baseDir = '/var/www/html/database/';
        $srcDir  = '/home/kls/NTCS7/';

        $files = [
            'KLS_NTCS_IDAS.Lin'     => 'KLS_NTCS.Lin',
            'ntcs_barcode_IDAS.db'  => 'ntcs_barcode.db',
            'ntcs_device_IDAS.db'   => 'ntcs_device.db',
        ];

        $result = [];

        // 確保目標目錄存在
        if (!is_dir($baseDir)) {
            mkdir($baseDir, 0777, true);
        }

        foreach ($files as $targetName => $sourceName) {

            $targetPath = $baseDir . $targetName;
            $sourcePath = $srcDir  . $sourceName;

            $needsRepair = false;

            // 檔案不存在
            if (!file_exists($targetPath)) {
                $needsRepair = true;
            }
            // 檔案大小為 0
            elseif (filesize($targetPath) === 0) {
                $needsRepair = true;
            }

            if ($needsRepair) {

                if (file_exists($sourcePath) && filesize($sourcePath) > 0) {

                    if (copy($sourcePath, $targetPath)) {
                        $result[$targetName] = 'repaired';
                    } else {
                        $result[$targetName] = 'copy_failed';
                    }

                } else {
                    $result[$targetName] = 'source_missing';
                }

            } else {
                $result[$targetName] = 'ok';
            }
        }

        return $result;
    }



}

