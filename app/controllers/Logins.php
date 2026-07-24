<?php
/* Default Login Accounts Fix V2 + Login Cookie Cleanup + Short Animation V1 */

class Logins extends Controller
{
    private $AdminModel;
    private $LoginModel;
    private $stepModel;
    private $AuditModel;
    Private $deviceId;
    private $currentUserLaw = 1;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->LoginModel = $this->model('Login');
        $this->stepModel = $this->model('Steptcc');
        $this->AdminModel = $this->model('Admin');
        $this->AuditModel = $this->model('OperationAudit');

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


    private function isLogoutRequest(array $url): bool
    {
        $path0 = strtolower((string)($url[0] ?? ''));
        $path1 = strtolower((string)($url[1] ?? ''));

        return $path1 === 'logout'
            || !empty($_POST['logout_audit'])
            || !empty($_GET['logout_audit'])
            || (
                isset($_SERVER['REQUEST_URI'])
                && preg_match('/url=(In|Logins)\/logout/i', (string)$_SERVER['REQUEST_URI'])
            );
    }

    private function respondLogoutRequest(): void
    {
        $this->logout(true);

        $isAjax = !empty($_POST['logout_audit'])
            || !empty($_GET['logout_audit'])
            || (
                isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            )
            || (
                isset($_SERVER['HTTP_ACCEPT'])
                && stripos((string)$_SERVER['HTTP_ACCEPT'], 'application/json') !== false
            );

        if ($isAjax) {
            $this->sendLoginJson(true, 'Logout logged.');
        }

        header('Location: /idas/public/?url=In');
        exit();
    }

    private function loginCurrentLang(): string
    {
        $raw = strtolower(trim((string)($_COOKIE['languages'] ?? ($_COOKIE['language'] ?? ($_COOKIE['lang'] ?? 'en-us')))));
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

    private function loginAuditText(string $key, array $vars = []): string
    {
        $dict = [
            'en-us' => [
                'LOGIN_TITLE'       => 'Login',
                'LOGIN_FAIL_TITLE'  => 'Login Fail',
                'QR_LOGIN_TITLE'    => 'QR Code Login',
                'QR_LOGIN_FAIL_TITLE' => 'QR Code Login Fail',
                'LOGOUT_TITLE'      => 'Logout',
                'LOGIN_SUCCESS_MSG' => 'Login success user: {user}',
                'LOGIN_FAIL_MSG'    => 'Login failed user: {user}, reason: {reason}',
                'QR_LOGIN_SUCCESS_MSG' => 'QR Code login success user: {user}',
                'QR_LOGIN_FAIL_MSG' => 'QR Code login failed user: {user}, reason: {reason}',
                'LOGOUT_MSG'        => 'Logout user: {user}',
                'USER_TARGET'       => 'User: {user}',
            ],
            'zh-tw' => [
                'LOGIN_TITLE'       => '登入',
                'LOGIN_FAIL_TITLE'  => '登入失敗',
                'QR_LOGIN_TITLE'    => 'QR Code 登入',
                'QR_LOGIN_FAIL_TITLE' => 'QR Code 登入失敗',
                'LOGOUT_TITLE'      => '登出',
                'LOGIN_SUCCESS_MSG' => '使用者登入成功：{user}',
                'LOGIN_FAIL_MSG'    => '使用者登入失敗：{user}，原因：{reason}',
                'QR_LOGIN_SUCCESS_MSG' => 'QR Code 使用者登入成功：{user}',
                'QR_LOGIN_FAIL_MSG' => 'QR Code 使用者登入失敗：{user}，原因：{reason}',
                'LOGOUT_MSG'        => '使用者登出：{user}',
                'USER_TARGET'       => '使用者：{user}',
            ],
            'zh-cn' => [
                'LOGIN_TITLE'       => '登入',
                'LOGIN_FAIL_TITLE'  => '登入失败',
                'QR_LOGIN_TITLE'    => 'QR Code 登录',
                'QR_LOGIN_FAIL_TITLE' => 'QR Code 登录失败',
                'LOGOUT_TITLE'      => '登出',
                'LOGIN_SUCCESS_MSG' => '使用者登入成功：{user}',
                'LOGIN_FAIL_MSG'    => '使用者登入失败：{user}，原因：{reason}',
                'QR_LOGIN_SUCCESS_MSG' => 'QR Code 使用者登录成功：{user}',
                'QR_LOGIN_FAIL_MSG' => 'QR Code 使用者登录失败：{user}，原因：{reason}',
                'LOGOUT_MSG'        => '使用者登出：{user}',
                'USER_TARGET'       => '使用者：{user}',
            ],
        ];

        $lang = $this->loginCurrentLang();
        $text = $dict[$lang][$key] ?? $dict['en-us'][$key] ?? $key;

        foreach ($vars as $name => $value) {
            $text = str_replace('{' . $name . '}', (string)$value, $text);
        }

        return $text;
    }

    private function isQrLoginRequest(): bool
    {
        return !empty($_POST['qr_ajax_login'])
            || !empty($_POST['qr_payload'])
            || !empty($_POST['usr'])
            || !empty($_POST['USR']);
    }

    private function loginAuditMethod(): string
    {
        return $this->isQrLoginRequest() ? 'QR_CODE' : 'MANUAL';
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



    private function loginCookiePaths(): array
    {
        return ['/', '/idas', '/idas/public'];
    }

    private function clearLoginCookie(string $name): void
    {
        foreach ($this->loginCookiePaths() as $path) {
            setcookie($name, '', time() - 3600, $path);
        }
    }

    private function clearLoginAuthCookies(): void
    {
        foreach (['username', 'auth_token', 'user_law'] as $name) {
            $this->clearLoginCookie($name);
        }
    }

    private function setLoginCookie(string $name, string $value, int $seconds = 600): void
    {
        setcookie($name, $value, time() + $seconds, '/');
    }

    private function sendLoginPageNoStoreHeaders(): void
    {
        if (!headers_sent()) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }

    private function normalizeLoginUserLaw($law): int
    {
        $law = (int)$law;
        return in_array($law, [1, 3], true) ? $law : 1;
    }

    private function setAccountLawCookie(int $law, int $seconds = 600): void
    {
        $law = $this->normalizeLoginUserLaw($law);
        $this->setLoginCookie('user_law', (string)$law, $seconds);
    }

    private function clearAccountLawCookie(): void
    {
        $this->clearLoginCookie('user_law');
    }

    public function index($url){
        $url = is_array($url) ? $url : [];

        //先做資料庫檔案完整性檢查
        $repairResult = $this->checkAndRepairDatabaseFiles();
        // 登入時檢查 Barcode DB 是否支援一個 JOB 多筆 Barcode；不支援時自動從 Controller DB 重建並轉移舊資料。
        $repairResult['ntcs_barcode_schema'] = $this->checkAndRepairBarcodeMultiJobSchema();

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

        // Direct logout request from dashboard logout() JS.
        // Must be handled before normal login/auth checks, otherwise logout may only clear cookies on client side and no audit row is written.
        if ($this->isLogoutRequest($url)) {
            $this->respondLogoutRequest();
        }
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

        // QR / 手動 Login 使用 AJAX 驗證成功後，前端顯示 1 秒成功動畫再跳頁。
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


                // 非無痕模式可能殘留舊路徑 Cookie（/idas、/idas/public），先清掉再寫入新登入狀態。
                $cookieSeconds = 600;
                $userLaw = (int)($_SESSION['user_law'] ?? $this->currentUserLaw);
                $this->clearLoginAuthCookies();
                $this->setLoginCookie('username', (string)$username, $cookieSeconds);
                $this->setLoginCookie('auth_token', (string)$authToken, $cookieSeconds);
                $this->setAccountLawCookie($userLaw, $cookieSeconds);

                $isQrLoginAudit = $this->isQrLoginRequest();
                $this->writeLoginAudit([
                    'action'   => 'LOGIN',
                    'status'   => 'SUCCESS',
                    'username' => (string)$username,
                    'operator' => (string)$username,
                    'title'    => $this->loginAuditText($isQrLoginAudit ? 'QR_LOGIN_TITLE' : 'LOGIN_TITLE'),
                    'message'  => $this->loginAuditText($isQrLoginAudit ? 'QR_LOGIN_SUCCESS_MSG' : 'LOGIN_SUCCESS_MSG', ['user' => (string)$username]),
                    'after_json' => [
                        'login_method' => $this->loginAuditMethod(),
                        'qr_payload_present' => !empty($_POST['qr_payload']),
                        'language' => $this->loginCurrentLang(),
                    ],
                ]);

                if ($isAjaxLogin) {
                    $this->sendLoginJson(true, 'Login success.', [
                        'redirect_url'    => '/idas/public/?url=Dashboards',
                        // 前端備援寫入 Cookie，避免部分瀏覽器在非無痕模式下第一次跳頁還讀到舊 Cookie。
                        'username'        => (string)$username,
                        'auth_token'      => (string)$authToken,
                        'user_law'        => (string)$userLaw,
                        'cookie_seconds'  => $cookieSeconds
                    ]);
                }

                header('Location: /idas/public/?url=Dashboards');
                exit;
            }else{
                // 用戶未登錄或身份驗證超時，跳轉到登錄頁面
                $failureCode = $this->getCredentialFailureCode($username);
                $failureMsg = $this->loginText($failureCode);

                $isQrLoginAudit = $this->isQrLoginRequest();
                $this->writeLoginAudit([
                    'action'   => 'LOGIN',
                    'status'   => 'FAIL',
                    'username' => (string)$username,
                    'operator' => (string)$username,
                    'title'    => $this->loginAuditText($isQrLoginAudit ? 'QR_LOGIN_FAIL_TITLE' : 'LOGIN_FAIL_TITLE'),
                    'message'  => $this->loginAuditText($isQrLoginAudit ? 'QR_LOGIN_FAIL_MSG' : 'LOGIN_FAIL_MSG', ['user' => (string)$username, 'reason' => $failureCode]),
                    'after_json' => [
                        'code' => $failureCode,
                        'login_method' => $this->loginAuditMethod(),
                        'qr_payload_present' => !empty($_POST['qr_payload']),
                        'language' => $this->loginCurrentLang(),
                    ],
                ]);

                $this->logout(false);

                if ($isAjaxLogin) {
                    $this->sendLoginJson(false, $failureMsg, ['code' => $failureCode]);
                }

                $data['error_message'] = $failureMsg;
                $this->sendLoginPageNoStoreHeaders();
                $this->view('login/index', $data);
                exit();
            }

        }else{

            if ($this->isAuthenticated() || $exception ) { //切換語系例外
                // 用戶已登錄，繼續處理其他操作
                return true;
            } else {
                // 用戶未登錄或身份驗證超時，跳轉到登錄頁面
                $this->logout(false);
                $this->sendLoginPageNoStoreHeaders();
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
                // 令牌有效，可以根據需要刷新 Cookie 的過期時間。
                $cookieSeconds = 600;
                $this->setLoginCookie('username', (string)$username, $cookieSeconds);
                $this->setLoginCookie('auth_token', (string)$authToken, $cookieSeconds);
                $this->setAccountLawCookie((int)($_SESSION['user_law'] ?? $this->currentUserLaw), $cookieSeconds);
                return true;
            }
        }

        return false;
    }


    public function logout($writeAudit = true) {
        $username = (string)($_POST['username'] ?? ($_GET['username'] ?? ($_COOKIE['username'] ?? '')));

        if ($writeAudit && $username !== '') {
            $this->writeLoginAudit([
                'action'   => 'LOGOUT',
                'status'   => 'SUCCESS',
                'username' => $username,
                'operator' => $username,
                'title'    => $this->loginAuditText('LOGOUT_TITLE'),
                'message'  => $this->loginAuditText('LOGOUT_MSG', ['user' => $username]),
            ]);
        }

        // 清除所有可能殘留路徑的登入 Cookie，避免非無痕瀏覽器第一次登入被舊 Cookie 干擾。
        $this->clearLoginAuthCookies();

        if (session_status() === PHP_SESSION_ACTIVE) {
            unset($_SESSION['privilege'], $_SESSION['user_law']);
        }

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

            // 依 user.law 區分登入權限：
            // law=3 為 operator，主選單只允許 Job / Seq / Step 相關瀏覽；CRUD 按鈕停用。
            // law=1 保留原本 guest/admin 行為。
            $userLaw = $this->normalizeLoginUserLaw($pwd['law'] ?? 1);
            $this->currentUserLaw = $userLaw;
            $_SESSION['user_law'] = $userLaw;
            $_SESSION['privilege'] = ($userLaw === 3)
                ? 'operator'
                : (((string)$username === 'guest') ? 'guest' : 'admin');

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
    

    private function getClientIp(): string
    {
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            return (string)$_SERVER["HTTP_CLIENT_IP"];
        }

        if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $parts = explode(',', (string)$_SERVER["HTTP_X_FORWARDED_FOR"]);
            return trim((string)$parts[0]);
        }

        return (string)($_SERVER["REMOTE_ADDR"] ?? '');
    }

    private function loginAuditRequestJson(?string $username = null): array
    {
        return [
            'username'      => $username ?? (string)($_POST['username'] ?? ($_COOKIE['username'] ?? '')),
            'ajax_login'    => !empty($_POST['ajax_login']),
            'qr_ajax_login' => !empty($_POST['qr_ajax_login']),
            'force_json'    => !empty($_POST['force_json']),
            'login_method'  => $this->loginAuditMethod(),
            'qr_payload_present' => !empty($_POST['qr_payload']),
            'user_agent'    => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
            'uri'           => (string)($_SERVER['REQUEST_URI'] ?? ''),
            'language'      => $this->loginCurrentLang(),
        ];
    }

    private function writeLoginAudit(array $data): void
    {
        try {
            if (!isset($this->AuditModel)) {
                return;
            }

            $operator = (string)($data['operator'] ?? ($_COOKIE['username'] ?? ($_POST['username'] ?? '')));
            $username = (string)($data['username'] ?? $operator);

            $defaults = [
                'user_id'      => $operator,
                'operator'     => $operator,
                'client_ip'    => $this->getClientIp(),
                'device_id'    => $this->deviceId ?? null,
                'module'       => 'AUTH',
                'status'       => 'SUCCESS',
                'target'       => $username !== '' ? $this->loginAuditText('USER_TARGET', ['user' => $username]) : '-',
                'request_json' => $this->loginAuditRequestJson($username),
                'before_json'  => null,
                'after_json'   => null,
            ];

            $payload = array_merge($defaults, $data);
            unset($payload['username']);

            $this->AuditModel->write($payload);
        } catch (Throwable $e) {
            // Audit log 失敗不能影響登入 / 登出功能
            error_log('[LOGIN AUDIT FAIL] ' . $e->getMessage());
        }
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

        $this->logout(false);
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
                $this->logout(false);
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
     * Login 時依目前 iDAS 版本同步 user table 預設帳號。
     *
     * 規則：
     * - 版本字串包含 SA349：保留 / 補齊 admin、guest。
     * - 版本字串不包含 SA349：只保留 / 補齊 guest，並從下列 DB 的 user table 移除 admin 與 law=3 使用者。
     *
     * Controller DB:
     *   /home/kls/NTCS7/KLS_NTCS.Lin
     * iDAS DB:
     *   /var/www/html/database/KLS_NTCS_IDAS.Lin
     *
     * This method is intentionally best-effort: login page must not crash if a DB
     * is temporarily missing, locked, or has a different schema. Errors are logged.
     */
    private function ensureDefaultLoginUsersDatabases(): array
    {
        $paths = [];
        $isSa349 = $this->isCurrentIdasSa349Profile();

        if (PHP_OS_FAMILY === 'Linux') {
            // Linux is case-sensitive. The production path is KLS_NTCS.Lin; keep .lin as fallback only.
            $controllerCandidates = [
                '/home/kls/NTCS7/KLS_NTCS.Lin',
                '/home/kls/NTCS7/KLS_NTCS.lin',
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

        $result = [
            'idas_version' => $this->getCurrentIdasVersionForLogin(),
            'profile'      => $isSa349 ? 'SA349' : 'STANDARD',
            'databases'    => [],
        ];

        foreach ($paths as $key => $path) {
            $status = $this->ensureDefaultLoginUsersInDb($path, $isSa349);

            if (!$isSa349) {
                $status .= ';' . $this->removeSa349RestrictedUsersFromLoginUserDb($path);
            }

            $result['databases'][$key] = [
                'path'   => $path,
                'status' => $status,
            ];
        }

        return $result;
    }

    private function getCurrentIdasVersionForLogin(): string
    {
        $version = '';

        try {
            $version = trim((string)$this->AdminModel->Get_Das_Config('idas_version'));
        } catch (Throwable $e) {
            error_log('[LOGIN] Read iDAS version from das_config failed: ' . $e->getMessage());
        }

        // Fallback: if config is empty, use /var/www/html/idas/info.json.
        if ($version === '') {
            $infoJson = '/var/www/html/idas/info.json';
            $info = json_decode((string)@file_get_contents($infoJson), true);
            if (is_array($info) && isset($info['idas_version'])) {
                $version = trim((string)$info['idas_version']);
            }
        }

        return $version;
    }

    private function isCurrentIdasSa349Profile(): bool
    {
        return stripos($this->getCurrentIdasVersionForLogin(), 'SA349') !== false;
    }

    private function ensureDefaultLoginUsersInDb(string $dbPath, bool $includeAdmin = true): string
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

            $requiredUsers = [];
            if ($includeAdmin) {
                $requiredUsers[] = ['name' => 'admin', 'passwd' => '0734', 'law' => 1];
            }
            $requiredUsers[] = ['name' => 'guest', 'passwd' => '000',  'law' => 1];

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
     * 非 SA349 iDAS 登入防呆：移除 SA349 專用帳號資料。
     *
     * 規則：
     * - 移除 name = admin。
     * - 移除 law = 3 的所有 Operator 帳號。
     */
    private function removeSa349RestrictedUsersFromLoginUserDb(string $dbPath): string
    {
        try {
            if (!is_file($dbPath)) {
                error_log('[LOGIN] Remove SA349 restricted users skipped, DB not found: ' . $dbPath);
                return 'remove_restricted_db_not_found';
            }

            if (!is_readable($dbPath) || !is_writable($dbPath)) {
                error_log('[LOGIN] Remove SA349 restricted users skipped, DB not readable/writable: ' . $dbPath);
                return 'remove_restricted_db_not_writable';
            }

            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec('PRAGMA busy_timeout = 3000');

            $tableExists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user'")->fetchColumn();
            if (!$tableExists) {
                error_log('[LOGIN] Remove SA349 restricted users skipped, table user not found: ' . $dbPath);
                return 'remove_restricted_table_user_not_found';
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
                error_log('[LOGIN] Remove SA349 restricted users skipped, missing name column: ' . $dbPath);
                return 'remove_restricted_missing_name_column';
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
                error_log('[LOGIN] Remove SA349 restricted users skipped law=3 removal because user.law column is missing: ' . $dbPath);
            }

            try {
                $check = $db->query('PRAGMA integrity_check;')->fetchColumn();
                if (strtolower((string)$check) !== 'ok') {
                    error_log('[LOGIN] SQLite integrity_check after remove SA349 restricted users not ok: ' . $dbPath . ', result=' . (string)$check);
                }
            } catch (Throwable $e) {
                error_log('[LOGIN] SQLite integrity_check after remove SA349 restricted users failed: ' . $dbPath . ', ' . $e->getMessage());
            }

            $db = null;
            @chmod($dbPath, 0666);
            error_log('[LOGIN] Non-SA349 iDAS removed restricted users from user table: ' . $dbPath . ', admin=' . $adminAffected . ', law3=' . $law3Affected);

            return 'removed_admin_' . $adminAffected . '_law3_' . $law3Affected;
        } catch (Throwable $e) {
            error_log('[LOGIN] Remove SA349 restricted users failed for ' . $dbPath . ': ' . $e->getMessage());
            return 'remove_restricted_error';
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





    private function checkAndRepairBarcodeMultiJobSchema(): array
    {
        $targetPath = '/var/www/html/database/ntcs_barcode_IDAS.db';
        $sourcePath = '/home/kls/NTCS7/ntcs_barcode.db';
        $tableName  = 'ntcs_barcode_test';

        $result = [
            'status' => 'ok',
            'action' => 'none',
            'message' => '',
            'old_rows' => 0,
            'merged_rows' => 0,
            'backup' => '',
        ];

        if (PHP_OS_FAMILY !== 'Linux') {
            $result['status'] = 'skipped';
            $result['message'] = 'not_linux';
            return $result;
        }

        try {
            if ($this->barcodeTableSupportsMultiJob($targetPath, $tableName)) {
                return $result;
            }

            $oldRows = $this->readBarcodeRowsFromDb($targetPath, $tableName);
            $result['old_rows'] = count($oldRows);

            if (!is_dir(dirname($targetPath))) {
                @mkdir(dirname($targetPath), 0777, true);
            }

            if (is_file($targetPath)) {
                $backupPath = $targetPath . '.before_multi_barcode_' . date('YmdHis') . '.bak';
                if (@copy($targetPath, $backupPath)) {
                    $result['backup'] = $backupPath;
                }
            }

            $copiedFromSource = false;
            if (is_file($sourcePath) && filesize($sourcePath) > 0) {
                if (@copy($sourcePath, $targetPath)) {
                    $copiedFromSource = true;
                    $result['action'] = 'copy_source_db';
                    @chmod($targetPath, 0666);
                } else {
                    $result['action'] = 'copy_source_failed';
                }
            } else {
                $result['action'] = 'source_missing';
            }

            // 若來源 DB schema 仍不支援，或來源不存在，直接用目前 target DB 重建表結構。
            if (!$this->barcodeTableSupportsMultiJob($targetPath, $tableName)) {
                if (!$this->rebuildBarcodeTableForMultiJob($targetPath, $tableName)) {
                    $result['status'] = 'failed';
                    $result['message'] = $copiedFromSource
                        ? 'copied_source_but_rebuild_failed'
                        : 'source_unavailable_and_rebuild_failed';
                    $this->logBarcodeRepairResult($result);
                    return $result;
                }
                $result['action'] .= '+rebuild_schema';
            }

            $result['merged_rows'] = $this->mergeBarcodeRowsIntoDb($targetPath, $tableName, $oldRows);
            $result['status'] = 'repaired';
            $result['message'] = 'barcode_db_supports_multi_job';
            $this->logBarcodeRepairResult($result);
            return $result;

        } catch (Throwable $e) {
            $result['status'] = 'failed';
            $result['message'] = $e->getMessage();
            $this->logBarcodeRepairResult($result);
            return $result;
        }
    }


    private function barcodeTableSupportsMultiJob(string $dbPath, string $tableName): bool
    {
        if (!is_file($dbPath) || filesize($dbPath) <= 0) {
            return false;
        }

        try {
            $pdo = $this->openSqliteDatabase($dbPath);

            if (!$this->sqliteTableExists($pdo, $tableName)) {
                return false;
            }

            $columns = $pdo->query('PRAGMA table_info(' . $tableName . ')')->fetchAll(PDO::FETCH_ASSOC);
            $hasJobId = false;

            foreach ($columns as $column) {
                $name = strtolower((string)($column['name'] ?? ''));
                if ($name === 'job_id') {
                    $hasJobId = true;
                    if ((int)($column['pk'] ?? 0) > 0) {
                        return false;
                    }
                }
            }

            if (!$hasJobId) {
                return false;
            }

            // 避免有 UNIQUE(job_id) 這種單欄唯一限制，否則仍無法一個 JOB 多筆 Barcode。
            $indexes = $pdo->query('PRAGMA index_list(' . $tableName . ')')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($indexes as $index) {
                if ((int)($index['unique'] ?? 0) !== 1) {
                    continue;
                }

                $indexName = (string)($index['name'] ?? '');
                if ($indexName === '') {
                    continue;
                }

                $safeIndexName = str_replace("'", "''", $indexName);
                $indexColumns = $pdo->query("PRAGMA index_info('{$safeIndexName}')")->fetchAll(PDO::FETCH_ASSOC);
                $columnNames = array_map(
                    static fn($row) => strtolower((string)($row['name'] ?? '')),
                    $indexColumns
                );

                if (count($columnNames) === 1 && $columnNames[0] === 'job_id') {
                    return false;
                }
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }



    private function openSqliteDatabase(string $dbPath): PDO
    {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }



    private function sqliteTableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name LIMIT 1");
        $stmt->bindValue(':name', $tableName, PDO::PARAM_STR);
        $stmt->execute();
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    }


    private function readBarcodeRowsFromDb(string $dbPath, string $tableName): array
    {
        if (!is_file($dbPath) || filesize($dbPath) <= 0) {
            return [];
        }

        try {
            $pdo = $this->openSqliteDatabase($dbPath);
            if (!$this->sqliteTableExists($pdo, $tableName)) {
                return [];
            }

            $columnsInfo = $pdo->query('PRAGMA table_info(' . $tableName . ')')->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_map(
                static fn($row) => strtolower((string)($row['name'] ?? '')),
                $columnsInfo
            );

            $select = [
                in_array('job_id', $columns, true)       ? 'job_id' : 'NULL AS job_id',
                in_array('barcode', $columns, true)      ? 'barcode' : "'' AS barcode",
                in_array('range_from', $columns, true)   ? 'range_from' : '1 AS range_from',
                in_array('range_count', $columns, true)  ? 'range_count' : '0 AS range_count',
                in_array('barcode_mode', $columns, true) ? 'barcode_mode' : '0 AS barcode_mode',
                in_array('seq_id', $columns, true)       ? 'COALESCE(seq_id, -1) AS seq_id' : '-1 AS seq_id',
            ];

            $sql = 'SELECT ' . implode(', ', $select) . ' FROM ' . $tableName . ' ORDER BY rowid ASC';
            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            return is_array($rows) ? $rows : [];
        } catch (Throwable $e) {
            return [];
        }
    }


    private function rebuildBarcodeTableForMultiJob(string $dbPath, string $tableName): bool
    {
        if ($dbPath === '') {
            return false;
        }

        try {
            if (!is_file($dbPath)) {
                if (!is_dir(dirname($dbPath))) {
                    @mkdir(dirname($dbPath), 0777, true);
                }
                @touch($dbPath);
                @chmod($dbPath, 0666);
            }

            $existingRows = $this->readBarcodeRowsFromDb($dbPath, $tableName);
            $pdo = $this->openSqliteDatabase($dbPath);
            $oldTable = $tableName . '_old_' . date('YmdHis');

            $pdo->beginTransaction();

            if ($this->sqliteTableExists($pdo, $tableName)) {
                $pdo->exec('ALTER TABLE ' . $tableName . ' RENAME TO ' . $oldTable);
            }

            $pdo->exec(
                'CREATE TABLE ' . $tableName . ' (
                    job_id INTEGER,
                    barcode TEXT,
                    range_from INTEGER,
                    range_count INTEGER,
                    barcode_mode INTEGER,
                    seq_id INTEGER
                )'
            );

            $insert = $pdo->prepare(
                'INSERT INTO ' . $tableName . ' (
                    job_id,
                    barcode,
                    range_from,
                    range_count,
                    barcode_mode,
                    seq_id
                ) VALUES (
                    :job_id,
                    :barcode,
                    :range_from,
                    :range_count,
                    :barcode_mode,
                    :seq_id
                )'
            );

            foreach ($existingRows as $row) {
                $this->bindBarcodeRowValues($insert, $row);
                $insert->execute();
            }

            if ($this->sqliteTableExists($pdo, $oldTable)) {
                $pdo->exec('DROP TABLE ' . $oldTable);
            }

            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_ntcs_barcode_test_job_id ON ' . $tableName . ' (job_id)');

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return false;
        }
    }


    private function mergeBarcodeRowsIntoDb(string $dbPath, string $tableName, array $oldRows): int
    {
        if (empty($oldRows) || !is_file($dbPath)) {
            return 0;
        }

        try {
            $pdo = $this->openSqliteDatabase($dbPath);
            if (!$this->sqliteTableExists($pdo, $tableName)) {
                return 0;
            }

            $existingRows = $this->readBarcodeRowsFromDb($dbPath, $tableName);
            $seen = [];
            foreach ($existingRows as $row) {
                $seen[$this->barcodeRowKey($row)] = true;
            }

            $insert = $pdo->prepare(
                'INSERT INTO ' . $tableName . ' (
                    job_id,
                    barcode,
                    range_from,
                    range_count,
                    barcode_mode,
                    seq_id
                ) VALUES (
                    :job_id,
                    :barcode,
                    :range_from,
                    :range_count,
                    :barcode_mode,
                    :seq_id
                )'
            );

            $merged = 0;
            $pdo->beginTransaction();

            foreach ($oldRows as $row) {
                $key = $this->barcodeRowKey($row);
                if (isset($seen[$key])) {
                    continue;
                }

                $this->bindBarcodeRowValues($insert, $row);
                if ($insert->execute()) {
                    $seen[$key] = true;
                    $merged++;
                }
            }

            $pdo->commit();
            return $merged;
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return 0;
        }
    }



    private function bindBarcodeRowValues(PDOStatement $statement, array $row): void
    {
        $statement->bindValue(':job_id', isset($row['job_id']) && $row['job_id'] !== '' ? (int)$row['job_id'] : null, isset($row['job_id']) && $row['job_id'] !== '' ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':barcode', (string)($row['barcode'] ?? ''), PDO::PARAM_STR);
        $statement->bindValue(':range_from', isset($row['range_from']) && $row['range_from'] !== '' ? (int)$row['range_from'] : 1, PDO::PARAM_INT);
        $statement->bindValue(':range_count', isset($row['range_count']) && $row['range_count'] !== '' ? (int)$row['range_count'] : 0, PDO::PARAM_INT);
        $statement->bindValue(':barcode_mode', isset($row['barcode_mode']) && $row['barcode_mode'] !== '' ? (int)$row['barcode_mode'] : 0, PDO::PARAM_INT);
        $statement->bindValue(':seq_id', isset($row['seq_id']) && $row['seq_id'] !== '' ? (int)$row['seq_id'] : -1, PDO::PARAM_INT);
    }



    private function barcodeRowKey(array $row): string
    {
        return implode('|', [
            (string)($row['job_id'] ?? ''),
            (string)($row['barcode'] ?? ''),
            (string)($row['range_from'] ?? ''),
            (string)($row['range_count'] ?? ''),
            (string)($row['barcode_mode'] ?? ''),
            (string)($row['seq_id'] ?? -1),
        ]);
    }



    private function logBarcodeRepairResult(array $result): void
    {
        $message = '[Barcode DB repair] ' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (method_exists($this, 'logMessage')) {
            $this->logMessage($message);
            return;
        }

        error_log($message);
    }
}

