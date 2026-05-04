<?php

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
            $sql = 'SELECT * FROM "user" WHERE name = :name';
            $statement = $this->db_iDas->prepare($sql);
            $statement->bindValue(':name', $username);
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
