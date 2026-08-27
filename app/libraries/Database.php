<?php

require_once dirname(__DIR__) . '/config/paths.php';

class Database
{
    private $db_data;
    private $db_iDas;
    private $db_iDas_login;
    private $db_barcode;
    private $db_iDas_tools;
    private $db_iDas_agent;
    private $db_con;

    public function __construct()
    {
        $isLinux = PHP_OS_FAMILY === 'Linux';
        $isLocal = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1']);

        $sourceBase = $isLinux && !$isLocal ? IDAS_PATH_CONTROLLER_ROOT . '/' : '../';
        $targetBase = $isLinux && !$isLocal ? IDAS_PATH_DATABASE_ROOT . '/' : '../';

        $fileMap = [
            'KLS_NTCS.Lin'      => 'KLS_NTCS_IDAS.Lin',
            'ntcs_device.db'    => 'ntcs_device_IDAS.db',
            'ntcs_barcode.db'   => 'ntcs_barcode_IDAS.db',
        ];

        foreach ($fileMap as $sourceFile => $targetFile) {
            $srcPath = $sourceBase . $sourceFile;
            $destPath = $targetBase . $targetFile;

            if (!file_exists($destPath) && file_exists($srcPath)) {
                @copy($srcPath, $destPath);
                @chmod($destPath, 0777);
                //("✅ Copied: $srcPath -> $destPath");
            }
        }

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', $targetBase);
        }

        $db_paths = [
            'data'        => $isLinux && !$isLocal 
                                ? idas_path('controller_root', 'ntcs_data.db') 
                                : '../ntcs_data.db',
            'iDas'        => BASE_PATH . 'KLS_NTCS_IDAS.Lin',
            'iDas_login'  => BASE_PATH . 'das.db',
            'iDas_tools'  => BASE_PATH . 'ntcs_device_IDAS.db',
            'iDas_agent'  => BASE_PATH . 'ntcs_device_temp.db',
            'barcode'     => BASE_PATH . 'ntcs_barcode_IDAS.db',
        ];

        foreach ($db_paths as $key => $path) {
            if (!file_exists($path)) {
                //error_log("❌ Database file not found: $path");
                continue;
            }
            if (!is_readable($path)) {
                //error_log("❌ Database file not readable: $path");
                continue;
            }
            try {
                $pdo = idas_sqlite_connect($path);
                $this->setUtf8Encoding($pdo);
                $this->{'db_' . $key} = $pdo;
            } catch (PDOException $e) {
                error_log('[iDAS DB] Failed to connect [' . $key . '] path=' . $path . ': ' . $e->getMessage());
            }
        }
    }

    private function setUtf8Encoding($db) {
        $db->exec('PRAGMA encoding = "UTF-8"');
    }

    public function getDb_data()        { return $this->db_data ?? null; }
    public function getDb_das()         { return $this->db_iDas ?? null; }
    public function getDb_das_login()   { return $this->db_iDas_login ?? null; }
    public function getDb_das_tools()   { return $this->db_iDas_tools ?? null; }
    public function getDb_das_barcode() { return $this->db_barcode ?? null; }
    public function getDb()             { return $this->db_con ?? null; }
    public function getDb_das_agent()   { return $this->db__iDas_agent ?? null; }

  
}
