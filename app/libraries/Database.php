<?php

class Database
{
    private $db_data;
    private $db_iDas;
    private $db_iDas_login;
    private $db_iDas_device;
    private $db_barcode;
    private $db_iDas_tools;
    private $db_con;

    public function __construct()
    {
        $isLinux = PHP_OS_FAMILY === 'Linux';
        $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);

        $sourceBase = $isLinux && !$isLocal ? '/home/kls/NTCS7/' : '../';
        $targetBase = $isLinux && !$isLocal ? '/var/www/html/database/' : '../';

        // ✅ 要複製的來源檔 => 目標檔名（副檔名統一為 .db）
        $fileMap = [
            'KLS_NTCS.Lin'      => 'KLS_NTCS_IDAS.Lin',
            'ntcs_device.db'    => 'ntcs_device_IDAS.db',
            'ntcs_barcode.db'   => 'ntcs_barcode_IDAS.db',
        ];

        // ✅ 複製（若目標檔案尚不存在）
        foreach ($fileMap as $sourceFile => $targetFile) {
            $srcPath = $sourceBase . $sourceFile;
            $destPath = $targetBase . $targetFile;

           if (!file_exists($destPath) && file_exists($srcPath)) {
                @copy($srcPath, $destPath);
                @chmod($destPath, 0777); // ✅ 開啟所有權限，避免 www-data 無法存取
                error_log("✅ Copied: $srcPath -> $destPath");
            }
        }

        // ✅ 定義路徑常數
        if (!defined('BASE_PATH')) {
            define('BASE_PATH', $targetBase);
        }

        // ✅ 資料庫對應表（副檔名統一）
        $db_paths = [
            'data'        => 'ntcs_data.db',
            'iDas'        => 'KLS_NTCS_IDAS.Lin',
            'iDas_login'  => 'das.db',
            'iDas_device' => 'data_device_local.db',
            'iDas_tools'  => 'ntcs_device_IDAS.db',
            'barcode'     => 'ntcs_barcode_IDAS.db',
        ];

        // ✅ 初始化資料庫連線
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
            }
        }
    }

    private function setUtf8Encoding($db){
        
        $db->exec('PRAGMA encoding = "UTF-8"');
    }

    // ✅ Getter methods
    public function getDb_data()        { return $this->db_data ?? null; }
    public function getDb_das()         { return $this->db_iDas ?? null; }
    public function getDb_das_login()   { return $this->db_iDas_login ?? null; }
    public function getDb_das_device()  { return $this->db_iDas_device ?? null; }
    public function getDb_das_tools()   { return $this->db_iDas_tools ?? null; }
    public function getDb_das_barcode() { return $this->db_barcode ?? null; }
    public function getDb()             { return $this->db_con ?? null; }
    public function getDb_das_agent()   { return $this->db__iDas_agent ?? null; }
}
