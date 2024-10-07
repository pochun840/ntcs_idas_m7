<?php

class Login{
    private $db;//condb control box
    private $db_dev;//devdb tool
    private $db_iDas;//iDas db

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db = new Database;
        $this->db = $this->db->getDb();

        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();

        $this->db_iDas_login = new Database;
        $this->db_iDas_login  = $this->db_iDas_login->getDb_das_login();

    }

    // 取得控制器登入密碼
    public function getpwd()
    {
        $sql = "SELECT operator_loginflag,operator_adminpwd,operator_priviledge FROM operator";
        $statement = $this->db_iDas_login->prepare($sql);
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
        $stmt = $this->db_iDas_login->prepare("INSERT INTO login_attempts (ip) VALUES (:ip)");
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
        }
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
