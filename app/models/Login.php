<?php
/*
 * Single-codebase platform switch.
 * /home/kls/upgrade/icontroller = 1 -> i-controller implementation
 * 0 / missing / invalid -> NTCS implementation
 */
if (idas_is_icontroller()) {
class Login{
    private $db_iDas;
    private $db_iDas_login;

    // 在建構子將 Database 物件實例化
    public function __construct(){

        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();

        $this->db_iDas_login = new Database;
        $this->db_iDas_login  = $this->db_iDas_login->getDb_das_login();

    }


    //取得控制器的帳戶
    public function get_account(){
        $sql = "SELECT * FROM `user` ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute();
        $row = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $row;
    }

    // 取得控制器登入密碼
    public function getpwd($username)
    {
        $sql = "SELECT * FROM user WHERE name = :name ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':name', $username);
        $statement->execute();        
        
        return $statement->fetch();
    }

    // 取得iDas登入密碼
    public function GetiDasPwd()
    {
        $sql = "SELECT * FROM `users` ";
        $statement = $this->db_iDas_login->prepare($sql);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    #紀錄登錄
    public function logLoginAttempt($ip) {

        
        // 插入登录尝试记录
        /*$stmt = $this->db_iDas_login->prepare("INSERT INTO login_attempts (ip) VALUES (:ip)");
        $stmt->bindValue(':ip', $ip);
        $stmt->execute();

        // 获取当前记录数量
        $result = $this->db_iDas_login->query("SELECT COUNT(*) AS count FROM login_attempts");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $count = $row['count'];
        
        // 如果记录数量超过100条，删除最旧的记录
        $max_records = 100;
        if ($count > $max_records) {
            $delete_count = $count - $max_records;
            $this->db_iDas_login->exec("DELETE FROM login_attempts WHERE id IN (SELECT id FROM login_attempts ORDER BY id ASC LIMIT $delete_count)");
        }*/


    }

    // 检查同时登录用户数是否达到限制
    public function GetConcurrentUsers($session_id) {

        // 查询当前活动会话的数量
        $result = $this->db_iDas_login->query("SELECT COUNT(*) AS active_sessions FROM active_sessions WHERE session_id <> '".$session_id."'");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $active_sessions = $row['active_sessions'];

        return $active_sessions;
    }

    // 定期清理过期的会话记录
    public function cleanExpiredSessions() {
        // 计算过期时间戳 10分鐘
        date_default_timezone_set('UTC');
        $expired_timestamp = date('Y-m-d H:i:s',time() - 600);


        // 删除过期的会话记录
        $stmt = $this->db_iDas_login->prepare("DELETE FROM active_sessions WHERE timestamp < :expired_timestamp");
        $stmt->bindValue(':expired_timestamp', $expired_timestamp);
        $stmt->execute();

    }

    public function active_sessions($username,$session_id,$ip)
    {
        //0.先清理過期的session
        //1.先確認是否達連線上限
        //2.如果已達連線上限，回傳false
        //3.如果未達連線上限，寫入db
        //4.檢查session id是否存在
        //5.如果存在update time
        //6.如果不存在insert
        date_default_timezone_set('UTC');
        
        $row = $this->session_exist_check($session_id);

        if($row == false){
            $stmt = $this->db_iDas_login->prepare("INSERT INTO active_sessions (username, session_id, ip) VALUES (:username, :session_id, :ip)");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':session_id', $session_id);
            $stmt->bindValue(':ip', $ip);
            $stmt->execute();
        }else{
            $stmt = $this->db_iDas_login->prepare("UPDATE active_sessions SET timestamp = :time_now WHERE session_id  = :session_id ");
            $stmt->bindValue(':time_now', date('Y-m-d H:i:s'));
            $stmt->bindValue(':session_id', $session_id);
            $stmt->execute();
        }

    }

    public function session_exist_check($session_id)
    {
        $stmt = $this->db_iDas_login->prepare("SELECT session_id FROM active_sessions WHERE session_id = :session_id");
        $stmt->bindValue(':session_id', $session_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    // 检查同时登录用户数是否达到限制
    public function get_max_user() {

        // 查询当前活动会话的数量
        $result = $this->db_iDas_login->query("SELECT * FROM config WHERE config_name = 'max_concurrent_users' ");
        $row = $result->fetch(PDO::FETCH_ASSOC);
        $max_user = $row['config_value'];

        return (int)$max_user;
    }





}
} else {
class Login{
    private $db_iDas;
    private $db_iDas_login;

    // 在建構子將 Database 物件實例化
    public function __construct(){
        try {
            $database = new Database;
            $this->db_iDas = $database->getDb_das();
        } catch (Throwable $e) {
            $this->db_iDas = null;
        }

        try {
            $databaseLogin = new Database;
            $this->db_iDas_login = $databaseLogin->getDb_das_login();
        } catch (Throwable $e) {
            $this->db_iDas_login = null;
        }
    }

    private function hasDasDb(): bool
    {
        return $this->db_iDas instanceof PDO;
    }

    private function hasLoginDb(): bool
    {
        return $this->db_iDas_login instanceof PDO;
    }

    //取得控制器的帳戶
    public function get_account(){
        if (!$this->hasDasDb()) {
            return [];
        }

        try {
            $sql = 'SELECT * FROM "user"';
            $statement = $this->db_iDas->prepare($sql);
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    // 取得控制器登入密碼
    public function getpwd($username)
    {
        if (!$this->hasDasDb()) {
            return false;
        }

        try {
            // QR scanner 有時會把帳號英文字母轉成大寫；這裡改成大小寫不敏感查詢。
            $sql = 'SELECT * FROM "user" WHERE LOWER(TRIM(name)) = LOWER(TRIM(:name)) LIMIT 1';
            $statement = $this->db_iDas->prepare($sql);
            $statement->bindValue(':name', trim((string)$username));
            $statement->execute();
            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }

    // 取得iDas登入密碼
    public function GetiDasPwd()
    {
        if (!$this->hasLoginDb()) {
            return false;
        }

        try {
            $sql = 'SELECT * FROM "users"';
            $statement = $this->db_iDas_login->prepare($sql);
            $statement->execute();
            return $statement->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }

    #紀錄登錄
    public function logLoginAttempt($ip) {
        // 目前不寫入，保留介面避免呼叫端錯誤。
        return true;
    }

    // 檢查同時登入使用者數
    public function GetConcurrentUsers($session_id) {
        if (!$this->hasLoginDb()) {
            return 0;
        }

        try {
            $stmt = $this->db_iDas_login->prepare('SELECT COUNT(*) AS active_sessions FROM active_sessions WHERE session_id <> :session_id');
            $stmt->bindValue(':session_id', $session_id);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($row['active_sessions'] ?? 0);
        } catch (Throwable $e) {
            return 0;
        }
    }

    // 定期清理過期的會話記錄
    public function cleanExpiredSessions() {
        if (!$this->hasLoginDb()) {
            return false;
        }

        try {
            date_default_timezone_set('UTC');
            $expired_timestamp = date('Y-m-d H:i:s', time() - 600);
            $stmt = $this->db_iDas_login->prepare('DELETE FROM active_sessions WHERE timestamp < :expired_timestamp');
            $stmt->bindValue(':expired_timestamp', $expired_timestamp);
            $stmt->execute();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function active_sessions($username,$session_id,$ip)
    {
        if (!$this->hasLoginDb()) {
            // 登入 DB 不存在時，不讓 API 變成 Fatal error。
            return false;
        }

        try {
            date_default_timezone_set('UTC');
            $row = $this->session_exist_check($session_id);

            if($row == false){
                $stmt = $this->db_iDas_login->prepare('INSERT INTO active_sessions (username, session_id, ip) VALUES (:username, :session_id, :ip)');
                $stmt->bindValue(':username', $username);
                $stmt->bindValue(':session_id', $session_id);
                $stmt->bindValue(':ip', $ip);
                $stmt->execute();
            }else{
                $stmt = $this->db_iDas_login->prepare('UPDATE active_sessions SET timestamp = :time_now WHERE session_id = :session_id');
                $stmt->bindValue(':time_now', date('Y-m-d H:i:s'));
                $stmt->bindValue(':session_id', $session_id);
                $stmt->execute();
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function session_exist_check($session_id)
    {
        if (!$this->hasLoginDb()) {
            return false;
        }

        try {
            $stmt = $this->db_iDas_login->prepare('SELECT session_id FROM active_sessions WHERE session_id = :session_id');
            $stmt->bindValue(':session_id', $session_id);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return false;
        }
    }

    // 取得最大連線數
    public function get_max_user() {
        if (!$this->hasLoginDb()) {
            return 1;
        }

        try {
            $result = $this->db_iDas_login->query("SELECT * FROM config WHERE config_name = 'max_concurrent_users'");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            return (int)($row['config_value'] ?? 1);
        } catch (Throwable $e) {
            return 1;
        }
    }
}
}
