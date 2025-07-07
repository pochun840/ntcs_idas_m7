<?php

class Logins extends Controller
{
    private $AdminModel;
    private $LoginModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->LoginModel = $this->model('Login');
    }

    // 取得所有Jobs
    public function index($url){
        session_start();
        $device_info = $this->Device_Info();
        $_SESSION['sessionid'] = session_id();
        $_SESSION['privilege'] = '';
        $error_message = '';
        $authToken = '';
        $account = $this->LoginModel->get_account();

       
        $data = [
            'error_message' => $error_message,
            'device_info' => $device_info,
            'account' => $account
        ];

        //例外狀況，切換語系
        $exception = false;
        if(isset($url[1])){
            if($url[0] == 'Dashboards' && $url[1] == 'change_language' ){
                $exception = true;
            }
        }

        //判斷有沒有post password
        //有post就驗證password
        //沒有就單純檢查cookies
        if( !empty($_POST['password']) && isset($_POST['password'])  ){
            //login attempt
            $_POST['username'] = trim($_POST['username']);
            $_POST['password'] = trim($_POST['password']);
        
            $this->logLoginAttempt();

            $username = $_POST['username'];
            $password = $_POST['password'];
            $authToken = hash('sha256', $password);
            
            
            if($this->verifyCredentials($username,$authToken)){


                //
                //$this->ntcs_data_db_sysnc();

                setcookie('username', $username, time() + 600, '/');
                setcookie('auth_token', $authToken, time() + 600, '/');
                return true;
            }else{
                // 用戶未登錄或身份驗證超時，跳轉到登錄頁面
                $this->logout();
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
    public function verifyCredentials($username,$authToken) {
        // 自定義的身份驗證邏輯，根據實際情況進行驗證
        // 返回 true 表示驗證成功，false 表示驗證失敗
        // 可以與數據庫或其他存儲進行比對驗證
        $pwd = $this->LoginModel->getpwd($username); //控制器密碼
        // $pwd2 = $this->LoginModel->GetiDasPwd(); //idas密碼
        $input = $authToken;
        $output = hash('sha256', $pwd['passwd']);
        // $output2 = hash('sha256', $pwd2['password']);

        if($input == $output){
            //登入成功寫入 active_sessions 資料庫
            $reslut = $this->active_sessions('admin');

            if($reslut){
                $_SESSION['privilege'] = 'admin';
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
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


    public function ntcs_data_db_sysnc() {
        
        header('Content-Type: application/json');

        $src = '/home/kls/NTCS7/ntcs_data.db';
        $dst = '/var/www/html/database/ntcs_data.db';

        if (!file_exists($src)) {
            echo json_encode([
                "status" => "error",
                "message" => "來源檔案不存在！"
            ]);
            return;
        }

        $src_mtime = filemtime($src);
        $dst_mtime = file_exists($dst) ? filemtime($dst) : 0;

        if ($src_mtime > $dst_mtime) {
            if (copy($src, $dst)) {
                chmod($dst, 0777);
                echo json_encode([
                    "status" => "success",
                    "message" => "同步完成！"
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "同步失敗！"
                ]);
            }
        } else {
            echo json_encode([
                "status" => "ok",
                "message" => "無需同步。"
            ]);
        }
    }

}
