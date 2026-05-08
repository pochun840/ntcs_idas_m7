<?php

class OperationAudit
{
    private $db_login;

    /** 操作紀錄資料保留天數：只保留最近 180 天 */
    private const RETENTION_DAYS = 180;

    /**
     * Operation Audit 欄位白名單。
     * 目的：
     * 1. 避免 controller 多帶 target / 其他欄位時，PDO bind 到不存在的 placeholder 導致整筆寫入失敗。
     * 2. 讓舊版 operation_audit_log table 自動補欄位，避免 no column named xxx。
     */
    private $columns = [
        'user_id' => 'INTEGER DEFAULT NULL',
        'operator' => "TEXT DEFAULT ''",
        'client_ip' => "TEXT DEFAULT ''",
        'device_id' => 'INTEGER DEFAULT NULL',

        'module' => "TEXT NOT NULL DEFAULT ''",
        'action' => "TEXT NOT NULL DEFAULT ''",
        'status' => "TEXT NOT NULL DEFAULT 'SUCCESS'",

        'job_id' => 'INTEGER DEFAULT NULL',
        'seq_id' => 'INTEGER DEFAULT NULL',
        'step_id' => 'INTEGER DEFAULT NULL',

        'source_job_id' => 'INTEGER DEFAULT NULL',
        'source_seq_id' => 'INTEGER DEFAULT NULL',
        'source_step_id' => 'INTEGER DEFAULT NULL',

        'target_job_id' => 'INTEGER DEFAULT NULL',
        'target_seq_id' => 'INTEGER DEFAULT NULL',
        'target_step_id' => 'INTEGER DEFAULT NULL',

        // 保留 target 欄位，讓前端若要直接使用 row.target 也可以。
        'target' => "TEXT DEFAULT ''",

        'title' => "TEXT DEFAULT ''",
        'message' => "TEXT DEFAULT ''",

        'before_json' => 'TEXT DEFAULT NULL',
        'after_json' => 'TEXT DEFAULT NULL',
        'order_before_json' => 'TEXT DEFAULT NULL',
        'order_after_json' => 'TEXT DEFAULT NULL',
        'request_json' => 'TEXT DEFAULT NULL'
    ];

    public function __construct()
    {
        $db = new Database();

        // iDAS / APP 操作紀錄統一寫入 das.db
        // 對應 Database.php：
        // 'iDas_login' => BASE_PATH . 'das.db'
        $this->db_login = $db->getDb_das_login();

        if ($this->db_login instanceof PDO) {
            $this->db_login->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db_login->exec('PRAGMA busy_timeout = 5000');
        }
    }

    private function isDbReady(): bool
    {
        if (!$this->db_login instanceof PDO) {
            error_log('[OPERATION AUDIT FAIL] das.db connection is not available.');
            return false;
        }

        return true;
    }

    public function createTable(): bool
    {
        if (!$this->isDbReady()) {
            return false;
        }

        $sql = "
            CREATE TABLE IF NOT EXISTS operation_audit_log (
                log_id INTEGER PRIMARY KEY AUTOINCREMENT,
                created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime')),

                user_id INTEGER DEFAULT NULL,
                operator TEXT DEFAULT '',
                client_ip TEXT DEFAULT '',
                device_id INTEGER DEFAULT NULL,

                module TEXT NOT NULL DEFAULT '',
                action TEXT NOT NULL DEFAULT '',
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

                target TEXT DEFAULT '',

                title TEXT DEFAULT '',
                message TEXT DEFAULT '',

                before_json TEXT DEFAULT NULL,
                after_json TEXT DEFAULT NULL,
                order_before_json TEXT DEFAULT NULL,
                order_after_json TEXT DEFAULT NULL,
                request_json TEXT DEFAULT NULL
            )
        ";

        $this->db_login->exec($sql);
        $this->migrateTableColumns();

        return true;
    }

    /**
     * 舊資料庫如果已經存在 operation_audit_log，
     * CREATE TABLE IF NOT EXISTS 不會補新欄位，所以這裡主動 ALTER TABLE。
     */
    private function migrateTableColumns(): void
    {
        $existing = [];

        $stmt = $this->db_login->query("PRAGMA table_info(operation_audit_log)");
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rows as $row) {
            if (isset($row['name'])) {
                $existing[$row['name']] = true;
            }
        }

        foreach ($this->columns as $column => $definition) {
            if (!isset($existing[$column])) {
                $this->db_login->exec("ALTER TABLE operation_audit_log ADD COLUMN {$column} {$definition}");
            }
        }
    }

    /**
     * 清除超過保留天數的操作紀錄。
     * 規則：
     * - 只保留最近 180 天資料
     * - created_at 小於目前時間 - 180 天的資料會刪除
     * - 清除失敗只寫 error_log，不影響原本寫入 / 查詢流程
     */
    private function cleanupOldLogs(): void
    {
        try {
            if (!$this->isDbReady()) {
                return;
            }

            $days = max(1, (int)self::RETENTION_DAYS);

            $stmt = $this->db_login->prepare("
                DELETE FROM operation_audit_log
                WHERE datetime(created_at) < datetime('now', 'localtime', '-' || :days || ' days')
            ");
            $stmt->bindValue(':days', $days, PDO::PARAM_INT);
            $stmt->execute();

        } catch (Throwable $e) {
            error_log('[OPERATION AUDIT CLEANUP FAIL] ' . $e->getMessage());
        }
    }


    private function defaultData(): array
    {
        $operator = $_COOKIE['username'] ?? '';

        return [
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

            'target' => '',

            'title' => '',
            'message' => '',

            'before_json' => null,
            'after_json' => null,
            'order_before_json' => null,
            'order_after_json' => null,
            'request_json' => null
        ];
    }

    private function normalizeValue($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }

    public function write(array $data): bool
    {
        try {
            if (!$this->createTable()) {
                return false;
            }

            // 每次寫入前先清理超過 180 天的舊操作紀錄。
            $this->cleanupOldLogs();

            $data = array_merge($this->defaultData(), $data);

            // 只保留 table 真的有的欄位。
            // 這行是本次修正重點：避免額外欄位 target_old / xxx 造成 bindValue 失敗。
            $allowed = array_flip(array_keys($this->columns));
            $data = array_intersect_key($data, $allowed);

            // 如果 controller 沒給 target_xxx，依 job / seq / step 自動補一份，方便前端統一顯示。
            if (($data['target_job_id'] === null || $data['target_job_id'] === '') && $data['job_id'] !== null && $data['job_id'] !== '') {
                $data['target_job_id'] = $data['job_id'];
            }

            if (($data['target_seq_id'] === null || $data['target_seq_id'] === '') && $data['seq_id'] !== null && $data['seq_id'] !== '') {
                $data['target_seq_id'] = $data['seq_id'];
            }

            if (($data['target_step_id'] === null || $data['target_step_id'] === '') && $data['step_id'] !== null && $data['step_id'] !== '') {
                $data['target_step_id'] = $data['step_id'];
            }

            if ($data['target'] === '' || $data['target'] === null) {
                $data['target'] = $this->buildTargetText($data);
            }

            $columns = array_keys($data);
            $columnSql = implode(', ', $columns);
            $placeholderSql = ':' . implode(', :', $columns);

            $sql = "INSERT INTO operation_audit_log ({$columnSql}) VALUES ({$placeholderSql})";
            $stmt = $this->db_login->prepare($sql);

            foreach ($data as $key => $value) {
                $value = $this->normalizeValue($value);

                if ($value === null) {
                    $stmt->bindValue(':' . $key, null, PDO::PARAM_NULL);
                } else {
                    $stmt->bindValue(':' . $key, $value);
                }
            }

            return $stmt->execute();
        } catch (Throwable $e) {
            error_log('[OPERATION AUDIT WRITE FAIL] ' . $e->getMessage());
            return false;
        }
    }

    private function buildTargetText(array $data): string
    {
        $parts = [];

        $jobId = $data['target_job_id'] ?? ($data['job_id'] ?? null);
        $seqId = $data['target_seq_id'] ?? ($data['seq_id'] ?? null);
        $stepId = $data['target_step_id'] ?? ($data['step_id'] ?? null);

        if ($jobId !== null && $jobId !== '') {
            $parts[] = 'Job ID: ' . $jobId;
        }

        if ($seqId !== null && $seqId !== '') {
            $parts[] = 'Seq ID: ' . $seqId;
        }

        if ($stepId !== null && $stepId !== '') {
            $parts[] = 'Step ID: ' . $stepId;
        }

        return !empty($parts) ? implode('; ', $parts) : '-';
    }

    public function latest(int $limit = 100): array
    {
        if (!$this->createTable()) {
            return [];
        }

        // 查詢前也清理一次，避免長時間沒有新增時舊資料仍顯示。
        $this->cleanupOldLogs();

        $limit = max(1, min(500, $limit));

        $stmt = $this->db_login->prepare("
            SELECT *
            FROM operation_audit_log
            ORDER BY log_id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
