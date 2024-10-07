<?php

class Database
{
    // 定義一些操作 Database 的變數，例如：
    private $dbh;
    private $stmt;
    private $error;

    private $db_con;// db con
    //private $db_dev;// db dev
    private $db_data;// db dev
    private $db_iDas;//iDas db
    private $db_iDas_login;
    private $db_iDas_device;
    public function __construct()
    {
        // 透過 PDO 建立資料庫連線
        // 實例化 PDO
        // 為避免控制器與iDas同時寫入sqlite3導致 db lock，iDas先將db複製出來，最後再透過call modbus的方式去更新db
        // 1.將DB複製一份到ramdisk根目錄，名稱調整為iDas-tcscon.db與iDas-tcsdev.db
        $this->iDasDB_Initail();


        // 透過 PHP_OS_FAMILY 判斷，目前執行的系統，決定要採用的DB路徑
        $Year = date("Y");// data db 用西元年命名
        $data_db_name = "data".$Year.".db";


        /*if(PHP_OS_FAMILY != 'Linux' ){
            //local



        }else{
            //linux

        }*/
        if( PHP_OS_FAMILY == 'Linux'){

            $this->db_con = new PDO('sqlite:/var/www/html/database/iDas-data.db');
            //$this->db_dev = new PDO('sqlite:/var/www/html/database/iDas-data.db'); 
            if( file_exists('/var/www/html/database/'.$data_db_name) ){
                $this->db_data = new PDO('sqlite:/var/www/html/database/'.$data_db_name); //local
            }else{
                $this->db_data = new PDO('sqlite:/var/www/html/das/data.db'); //local

            }
            
            $this->db_iDas = new PDO('sqlite:/var/www/html/database/data.db'); //local
            $this->db_iDas_login = new PDO('sqlite:/var/www/html/database/das.db'); 
            $this->db_iDas_device = new PDO('sqlite:/var/www/html/database/data_device.db'); 
            
        }else{
            $this->db_con = new PDO('sqlite:../data.db'); //local
            //$this->db_dev = new PDO('sqlite:../data.db'); //local
            if(file_exists('../'.$data_db_name)){
                $this->db_data = new PDO('sqlite:../'.$data_db_name); //local
            }else{
                $this->db_data = new PDO('sqlite:../data.db'); //local
            }
            $this->db_iDas = new PDO('sqlite:../data.db'); //local
            $this->db_iDas_login = new PDO('sqlite:../das.db'); //local
            $this->db_iDas_device = new PDO('sqlite:../data_device.db'); 

        }
        $this->db_con->exec('set names utf-8'); 
        //$this->db_dev->exec('set names utf-8'); 
        $this->db_data->exec('set names utf-8'); 
        $this->db_iDas->exec('set names utf-8'); 
        $this->db_iDas_login->exec('set names utf-8'); 
        $this->db_iDas_device->exec('set names utf-8'); 

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


    /*public function get_tool_rpm()
    {
        $sql = "SELECT tool_maxrpm,tool_minrpm FROM tool_info";
        $statement = $this->db_dev->prepare($sql);
        $results = $statement->execute();
        $rows = $statement->fetch();

        return $rows;
    }*/

    private function iDasDB_Initail()
    {
        if( PHP_OS_FAMILY == 'Linux'){
            $source = "/var/www/html/database/data.db";
            $destination = "/var/www/html/database/iDas-data.db";
            $source1 = "/var/www/html/database/data.db";
            $destination1 = "/var/www/html/database/iDas-data.db";
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
