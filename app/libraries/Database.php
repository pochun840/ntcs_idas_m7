<?php

class Database
{
    // 定義一些操作 Database 的變數，例如：
    private $dbh;
    private $stmt;
    private $error;

    private $db_con;// db con
    private $db_dev;// db dev
    private $db_data;// db dev
    private $db_iDas;//iDas db
    private $db_iDas_login;
    private $db_iDas_device;
    private $db_barcode;
    private $db_iDas_tools;
    public function __construct()
    {

        $this->iDasDB_Initail();

        $data_db_name = "ntcs_data.db";
        $data_barcode_name = "ntcs_barcode.db";
        $data_device_name  = 'ntcs_device.db';

        $base_path_linux = '/var/www/html/database/';
        $base_path_other = '../';
       


        // 透過 PHP_OS_FAMILY 判斷，目前執行的系統，決定要採用的DB路徑
        if (PHP_OS_FAMILY == 'Linux') {
            if (!defined('BASE_PATH')) {
                define('BASE_PATH', '/var/www/html/database/');
            }
            $db_paths = [
                'iDas' => 'iDas_data.db',
                'iDas_login' => 'das.db',
                'iDas_device' => 'data.db',
                'data' => file_exists(BASE_PATH. $data_db_name) ? $data_db_name : 'data.db',
            ];
            
            foreach ($db_paths as $key => $db_name) {
                $this->{'db_' . $key} = new PDO('sqlite:' . BASE_PATH. $db_name);
                $this->setUtf8Encoding($this->{'db_' . $key});
            }
            
        } else {
            if (!defined('BASE_PATH')) {
                define('BASE_PATH', '../');
            }
            $db_paths = [
                'data' => file_exists(BASE_PATH . $data_db_name) ? $data_db_name : 'data.db',
                'iDas' => 'KLS_NTCS_IDAS.Lin',
                'iDas_login' => 'das.db',
                'iDas_device' => 'data_device.db',
                'iDas_tools' => 'ntcs_device.db',
                'barcode' => 'ntcs_barcode.db',
            ];
            
            foreach ($db_paths as $key => $db_name) {
                $this->{'db_' . $key} = new PDO('sqlite:' . BASE_PATH . $db_name);
                $this->setUtf8Encoding($this->{'db_' . $key});
            }
        }

      
        
    
    }

    private function setUtf8Encoding($db) {
        $db->exec('set names utf-8');
    } 

    // Prepare statement with query
    public function query($query){
        return $this->db_con->query($query);
    }

    public function getDb() {
        if ($this->db_con instanceof PDO) {
            return $this->db_con;
        }
    }

    public function getDb_dev() {
        if ($this->db_dev instanceof PDO) {
            return $this->db_dev;
        }
    }

    public function getDb_data() {
        if ($this->db_data instanceof PDO) {
            return $this->db_data;
        }
    }

    public function getDb_das() {
        if ($this->db_iDas instanceof PDO) {
            return $this->db_iDas;
        }
    }

    public function getDb_das_login() {
        if ($this->db_iDas_login instanceof PDO) {
            return $this->db_iDas_login;
        }
    }

    public function getDb_das_device() {
        if ($this->db_iDas_device instanceof PDO) {
            return $this->db_iDas_device;
        }
    }

    public function getDb_das_tools() {
        if ($this->db_iDas_tools instanceof PDO) {
            return $this->db_iDas_tools;
        }
    }

    public function getDb_das_barcode() {
        if ($this->db_barcode instanceof PDO) {
            return $this->db_barcode;
        }
    }


    public function get_tool_rpm()
    {
        $sql = "SELECT tool_maxrpm,tool_minrpm FROM tool_info";
        $statement = $this->db_dev->prepare($sql);
        $results = $statement->execute();
        $rows = $statement->fetch();

        return $rows;
    }

    private function iDasDB_Initail()
    {
        if( PHP_OS_FAMILY == 'Linux'){
            $source = "/home/kls/tcc/resource/db_emmc/data.db";
            $destination = "/home/kls/tcc/resource/db_emmc/iDas-data.db";
            $source1 = "/home/kls/tcc/resource/db_emmc/data.db";
            $destination1 = "/home/kls/tcc/resource/db_emmc/iDas-data.db";
        }else{
             $source = "/var/www/html/database/data.db";
            $destination = "/var/www/html/database/iDasdata.db";
            $source1 = "/var/www/html/database/data.db";
            $destination1 = "/var/www/html/database/iDas-data.db";
        }

        if( file_exists($source) && !file_exists($destination)){
            copy($source, $destination);
        }
        if( file_exists($source1) && !file_exists($destination1)){            
            copy($source1, $destination1);
        }
    }

}
