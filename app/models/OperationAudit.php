<?php

class OperationAudit
{
    private $db_login;

    public function __construct()
    {
        $db = new Database();

        // 改用 das.db
        // 對應 Database.php 裡面的：
        // 'iDas_login' => BASE_PATH . 'das.db'
        $this->db_login = $db->getDb_das_login();
    }

    public function createTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS operation_audit_log (
                log_id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),

                user_id INTEGER DEFAULT NULL,
                operator TEXT DEFAULT '',
                client_ip TEXT DEFAULT '',
                device_id INTEGER DEFAULT NULL,

                module TEXT NOT NULL,
                action TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'SUCCESS',

                job_id INTEGER DEFAULT NULL,
                seq_id INTEGER DEFAULT NULL,
                step_id INTEGER DEFAULT NULL,

                source_job_id INTEGER DEFAULT NULL,
                source_seq_id INTEGER DEFAULT NULL,
                source_step_id INTEGER DEFAULT NULL,

                target_job_id INTEGER DEFAULT NULL,
                target_seq_id INTEGER DEFAULT NULL,
                target_step_id INTEGER DEFAULT NULL,

                title TEXT DEFAULT '',
                message TEXT DEFAULT '',

                before_json TEXT DEFAULT NULL,
                after_json TEXT DEFAULT NULL,
                order_before_json TEXT DEFAULT NULL,
                order_after_json TEXT DEFAULT NULL,
                request_json TEXT DEFAULT NULL
            )
        ";

        return $this->db_login->exec($sql) !== false;
    }

    public function write(array $data): bool
    {
        $this->createTable();

        $operator = $_COOKIE['username'] ?? '';

        $defaults = [
            'user_id' => $operator,
            'operator' => $operator,
            'client_ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'device_id' => null,

            'module' => '',
            'action' => '',
            'status' => 'SUCCESS',

            'job_id' => null,
            'seq_id' => null,
            'step_id' => null,

            'source_job_id' => null,
            'source_seq_id' => null,
            'source_step_id' => null,

            'target_job_id' => null,
            'target_seq_id' => null,
            'target_step_id' => null,

            'title' => '',
            'message' => '',

            'before_json' => null,
            'after_json' => null,
            'order_before_json' => null,
            'order_after_json' => null,
            'request_json' => null,
        ];

        $data = array_merge($defaults, $data);

        $sql = "
            INSERT INTO operation_audit_log (
                user_id, operator, client_ip, device_id,
                module, action, status,
                job_id, seq_id, step_id,
                source_job_id, source_seq_id, source_step_id,
                target_job_id, target_seq_id, target_step_id,
                title, message,
                before_json, after_json,
                order_before_json, order_after_json,
                request_json
            ) VALUES (
                :user_id, :operator, :client_ip, :device_id,
                :module, :action, :status,
                :job_id, :seq_id, :step_id,
                :source_job_id, :source_seq_id, :source_step_id,
                :target_job_id, :target_seq_id, :target_step_id,
                :title, :message,
                :before_json, :after_json,
                :order_before_json, :order_after_json,
                :request_json
            )
        ";

        $stmt = $this->db_login->prepare($sql);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $stmt->bindValue(':' . $key, $value);
        }

        return $stmt->execute();
    }
}