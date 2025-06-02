<?php

class Database
{
    private $db_con;
    private $db_dev;
    private $db_data;
    private $db_iDas;
    private $db_iDas_login;
    private $db_iDas_device;
    private $db_barcode;
    private $db_iDas_tools;

    public function __construct()
    {
        // ✅ 選擇 base path（Linux 控制器 vs 本機）
        $isLinux = PHP_OS_FAMILY === 'Linux';
        $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);
        $basePath = $isLinux && !$isLocal ? '/var/www/html/database/' : '../';

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', $basePath);
        }

        // ✅ 資料庫對應表
        $db_paths = [
            'data'        => 'ntcs_data.db',
            'iDas'        => 'KLS_NTCS_IDAS.Lin',
            'iDas_login'  => 'das.db',
            'iDas_device' => 'data_device.db',
            'iDas_tools'  => 'ntcs_device.db',
            'barcode'     => 'ntcs_barcode.db',
        ];

        foreach ($db_paths as $key => $db_name) {
            $path = BASE_PATH . $db_name;

            if (!file_exists($path)) {
                error_log("❌ Database file not found: $path");
                continue;
            }

            if (!is_readable($path)) {
                error_log("❌ Database file not readable: $path");
                continue;
            }

            try {
                $pdo = new PDO('sqlite:' . $path);
                $this->setUtf8Encoding($pdo);
                $this->{'db_' . $key} = $pdo;
            } catch (PDOException $e) {
                error_log("❌ Failed to connect to DB [$key]: " . $e->getMessage());
                continue;
            }
        }
    }

    private function setUtf8Encoding($db)
    {
        // SQLite 使用 PRAGMA
        $db->exec('PRAGMA encoding = "UTF-8"');
    }

    public function query($query)
    {
        return $this->db_con instanceof PDO ? $this->db_con->query($query) : null;
    }

    // Getter functions
    public function getDb()             { return $this->db_con ?? null; }
    public function getDb_dev()        { return $this->db_dev ?? null; }
    public function getDb_data()       { return $this->db_data ?? null; }
    public function getDb_das()        { return $this->db_iDas ?? null; }
    public function getDb_das_login()  { return $this->db_iDas_login ?? null; }
    public function getDb_das_device() { return $this->db_iDas_device ?? null; }
    public function getDb_das_tools()  { return $this->db_iDas_tools ?? null; }
    public function getDb_das_barcode(){ return $this->db_barcode ?? null; }
}
