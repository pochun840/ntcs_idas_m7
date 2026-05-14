<?php
/* Default Login Accounts Fix V2 */

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


    private function loginCurrentLang(): string
    {
        $raw = strtolower(trim((string)($_COOKIE['language'] ?? ($_COOKIE['lang'] ?? 'en-us'))));
        $raw = str_replace('_', '-', $raw);

        if ($raw === 'zh-tw' || $raw === 'zh-hant' || $raw === 'tw') return 'zh-tw';
        if ($raw === 'zh-cn' || $raw === 'zh-hans' || $raw === 'cn') return 'zh-cn';
        return 'en-us';
    }

    private function loginText(string $key): string
    {
        $dict = [
            'en-us' => [
                'LOGIN_EXPIRED' => 'Login expired. Please login again.',
                'USER_NOT_FOUND' => 'Account does not exist.',
                'PASSWORD_ERROR' => 'Incorrect password.',
                'LOGIN_FAILED' => 'Login failed.',
            ],
            'zh-tw' => [
                'LOGIN_EXPIRED' => '登入已逾時，請重新登入。',
                'USER_NOT_FOUND' => '帳號不存在。',
                'PASSWORD_ERROR' => '密碼錯誤。',
                'LOGIN_FAILED' => '登入失敗。',
            ],
            'zh-cn' => [
                'LOGIN_EXPIRED' => '登录已逾时，请重新登录。',
                'USER_NOT_FOUND' => '账号不存在。',
                'PASSWORD_ERROR' => '密码错误。',
                'LOGIN_FAILED' => '登录失败。',
            ],
        ];

        $lang = $this->loginCurrentLang();
        return $dict[$lang][$key] ?? $dict['en-us'][$key] ?? $key;
    }

    private function getCredentialFailureCode(string $username): string
    {
        $username = trim($username);
        if ($username === '') {
            return 'USER_NOT_FOUND';
        }

        try {
            $row = $this->LoginModel->getpwd($username);
            if (!$row || !is_array($row) || !isset($row['passwd'])) {
                return 'USER_NOT_FOUND';
            }
        } catch (Throwable $e) {
            return 'LOGIN_FAILED';
        }

        return 'PASSWORD_ERROR';
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

        // Login page safety check:
        // Ensure both controller DB and iDAS DB have default login accounts.
        // Required accounts:
        //   admin / 0734 / law=1
        //   guest / 000  / law=1
        // Required by Account login / QR login / account management flows.
        $this->ensureDefaultLoginUsersDatabases();

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
        // 也支援 force_json / X-Requested-With / Accept: application/json，避免回傳 HTML 造成前端 JSON.parse 失敗。
        $isAjaxLogin = !empty($_POST['ajax_login'])
            || !empty($_POST['qr_ajax_login'])
            || !empty($_POST['force_json'])
            || (
                isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            )
            || (
                isset($_SERVER['HTTP_ACCEPT'])
                && stripos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false
            );

        // Account API 必須回 JSON，不可以回登入頁 HTML，否則前端 JSON.parse 會失敗
        if ($this->isAccountUserApiRequest($url)) {
            if ($this->isAuthenticated()) {
                return true;
            }
            $this->sendLoginJson(false, $this->loginText('LOGIN_EXPIRED'), ['code' => 'LOGIN_EXPIRED']);
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

                $failureCode = $this->getCredentialFailureCode($username);
                $failureMsg = $this->loginText($failureCode);

                if ($isAjaxLogin) {
                    $this->sendLoginJson(false, $failureMsg, ['code' => $failureCode]);
                }

                $data['error_message'] = $failureMsg;
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
            /*
             * Guest Login Fix V1
             * 以前 guest 在 active_sessions() 內會直接呼叫 Users_Uplimit()，
             * 造成 AJAX fetch 無法取得正常 JSON，畫面卡在 Verifying Login。
             *
             * 登入成功後，active_sessions 寫入失敗不應該變成密碼錯誤。
             */
            $sessionOk = $this->active_sessions((string)$username);
            if (!$sessionOk) {
                error_log('[LOGIN] active_sessions write failed or skipped for user: ' . (string)$username);
            }

            // 保留既有邏輯：admin 權限仍可由系統後續判斷。
            // 若未來要依 DB 欄位 law 區分，可在這裡擴充。
            $_SESSION['privilege'] = ((string)$username === 'guest') ? 'guest' : 'admin';

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
        $session_id = session_id();

        if (!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }else{
            $ip = $_SERVER["REMOTE_ADDR"];
        }

        /*
         * Guest Login Fix V1
         * 原本程式：
         * if ($username == 'guest') {
         *     $this->Users_Uplimit();
         *     return false;
         * }
         *
         * Users_Uplimit() 會 logout + 輸出 login view + exit，
         * 造成 guest 密碼正確也無法登入，前端 fetch 會得到錯誤/HTML。
         *
         * 新版：
         * guest 也允許登入；active_sessions DB 寫入失敗只記 log，不中斷登入。
         */
        try {
            $this->LoginModel->cleanExpiredSessions();

            // guest 也寫入 active_sessions；如果 login DB 沒有 active_sessions，也不影響登入。
            $ok = $this->LoginModel->active_sessions($username, $session_id, $ip);
            if (!$ok) {
                error_log('[LOGIN] active_sessions skipped/failed for user: ' . (string)$username);
            }

            return true;
        } catch (Throwable $e) {
            error_log('[LOGIN] active_sessions exception for user ' . (string)$username . ': ' . $e->getMessage());
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
     * Ensure default login users exist in both login user databases.
     *
     * Controller DB:
     *   /home/kls/NTCS7/KLS_NTCS.lin or /home/kls/NTCS7/KLS_NTCS.Lin
     * iDAS DB:
     *   /var/www/html/database/KLS_NTCS_IDAS.Lin
     *
     * If table user does not have these accounts, insert them:
     *   admin / 0734 / law=1
     *   guest / 000  / law=1
     *
     * This method is intentionally best-effort: login page must not crash if a DB
     * is temporarily missing, locked, or has a different schema. Errors are logged.
     */
    private function ensureDefaultLoginUsersDatabases(): array
    {
        $paths = [];

        if (PHP_OS_FAMILY === 'Linux') {
            // User request uses .lin, existing project paths may use .Lin. Linux is case-sensitive,
            // so support both and update whichever exists. If neither exists, prefer .lin.
            $controllerCandidates = [
                '/home/kls/NTCS7/KLS_NTCS.lin',
                '/home/kls/NTCS7/KLS_NTCS.Lin',
            ];

            $controllerPath = $controllerCandidates[0];
            foreach ($controllerCandidates as $candidate) {
                if (is_file($candidate)) {
                    $controllerPath = $candidate;
                    break;
                }
            }

            $paths['controller'] = $controllerPath;
            $paths['idas']       = '/var/www/html/database/KLS_NTCS_IDAS.Lin';
        } else {
            // Development / Windows fallback paths.
            $paths['controller'] = __DIR__ . '/../../database/KLS_NTCS.Lin';
            $paths['idas']       = __DIR__ . '/../../database/KLS_NTCS_IDAS.Lin';
        }

        $result = [];
        foreach ($paths as $key => $path) {
            $result[$key] = $this->ensureDefaultLoginUsersInDb($path);
        }

        return $result;
    }

    private function ensureDefaultLoginUsersInDb(string $dbPath): string
    {
        try {
            if (!is_file($dbPath)) {
                error_log('[LOGIN] Default login users check skipped, DB not found: ' . $dbPath);
                return 'db_not_found';
            }

            if (!is_readable($dbPath)) {
                error_log('[LOGIN] Default login users check skipped, DB not readable: ' . $dbPath);
                return 'db_not_readable';
            }

            if (!is_writable($dbPath)) {
                error_log('[LOGIN] Default login users check skipped, DB not writable: ' . $dbPath);
                return 'db_not_writable';
            }

            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec('PRAGMA busy_timeout = 3000');

            $tableExists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user'")->fetchColumn();
            if (!$tableExists) {
                error_log('[LOGIN] Default login users check skipped, table user not found: ' . $dbPath);
                return 'table_user_not_found';
            }

            $columns = $db->query('PRAGMA table_info("user")')->fetchAll(PDO::FETCH_ASSOC);
            $columnNames = [];
            foreach ($columns as $column) {
                if (!empty($column['name'])) {
                    $columnNames[] = (string)$column['name'];
                }
            }

            foreach (['name', 'passwd', 'law'] as $requiredColumn) {
                if (!in_array($requiredColumn, $columnNames, true)) {
                    error_log('[LOGIN] Default login users check skipped, missing column ' . $requiredColumn . ': ' . $dbPath);
                    return 'missing_column_' . $requiredColumn;
                }
            }

            $requiredUsers = [
                ['name' => 'admin', 'passwd' => '0734', 'law' => 1],
                ['name' => 'guest', 'passwd' => '000',  'law' => 1],
            ];

            $created = [];
            $exists = [];

            foreach ($requiredUsers as $user) {
                $stmt = $db->prepare('SELECT COUNT(*) FROM "user" WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name))');
                $stmt->execute([':name' => $user['name']]);

                if ((int)$stmt->fetchColumn() > 0) {
                    $exists[] = $user['name'];
                    continue;
                }

                $insertColumns = ['name', 'passwd', 'law'];
                $insertValues = [
                    ':name'   => $user['name'],
                    ':passwd' => $user['passwd'],
                    ':law'    => (int)$user['law'],
                ];

                // Some deployed schemas have a NOT NULL sn column. If present, choose max(sn)+1.
                if (in_array('sn', $columnNames, true)) {
                    $nextSn = (int)$db->query('SELECT COALESCE(MAX(sn), 0) + 1 FROM "user"')->fetchColumn();
                    $insertColumns = array_merge(['sn'], $insertColumns);
                    $insertValues = array_merge([':sn' => $nextSn], $insertValues);
                }

                $quotedColumns = array_map(function($column) {
                    return '"' . str_replace('"', '""', $column) . '"';
                }, $insertColumns);

                $placeholders = array_keys($insertValues);
                $sql = 'INSERT INTO "user" (' . implode(', ', $quotedColumns) . ') VALUES (' . implode(', ', $placeholders) . ')';
                $insert = $db->prepare($sql);
                $insert->execute($insertValues);

                $created[] = $user['name'];
            }

            @chmod($dbPath, 0666);

            if (!empty($created)) {
                return 'created_' . implode('_', $created);
            }

            return 'exists_' . implode('_', $exists);
        } catch (Throwable $e) {
            error_log('[LOGIN] Default login users check failed for ' . $dbPath . ': ' . $e->getMessage());
            return 'error';
        }
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

