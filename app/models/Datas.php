<?php
class Datas{

    private $db_data;
    // 在建構子將 Database 物件實例化
    public function __construct(){

        $db = new Database();
        $this->db_data = $db->getDb_data();


    }

    public function getData($type) {
        if (PHP_OS_FAMILY === 'Linux') {
            $directory = '/home/kls'; // 指定目錄路徑

            // 取得目錄中的檔案和子目錄列表
            $fileList = scandir($directory);
            $fileList = array_diff($fileList, array('.', '..'));

        }

        // 預設 SQL
        $sql = "SELECT * FROM ntcs_data ORDER BY rowid DESC LIMIT 100";

        if ($type == 'OK') {
            $sql = "
                SELECT *
                FROM (
                    SELECT rowid AS rid, *
                    FROM ntcs_data
                    WHERE fasten_status IN (4,5,6)
                    ORDER BY rid DESC
                    LIMIT 100
                ) AS recent_data
                ORDER BY rid DESC;
            ";
        }

        if ($type == 'NOK') {
            $sql = "
                SELECT *
                FROM (
                    SELECT rowid AS rid, *
                    FROM ntcs_data
                    WHERE fasten_status IN (7,8)
                    ORDER BY rid DESC
                    LIMIT 100
                ) AS recent_data
                ORDER BY rid DESC;
            ";
        }

        $statement = $this->db_data->prepare($sql);
        if ($statement !== false) {
            $statement->execute();
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $rows;
        } else {
            //error_log("❌ prepare SQL 失敗: $sql");
            return [];
        }
    }


    public function get_range_data($start_date,$end_date){

        $sql = "SELECT * FROM ntcs_data 
                WHERE data_time BETWEEN '".$start_date."' AND '".$end_date."'
                ORDER BY data_time DESC LIMIT 10000";
                
        $statement = $this->db_data->prepare($sql);
        
        if($statement != false){
            $results = $statement->execute();
            $row = $statement->fetchall(PDO::FETCH_ASSOC);

            return $row;
        }else{
            return array();
        }
    }

    public function get_data_for_year(){

        $sql = "SELECT strftime('%Y', data_time) AS year, COUNT(*) AS total FROM ntcs_data GROUP BY year ORDER BY year ASC";
        $statement = $this->db_data->prepare($sql);
        if($statement != false){
            $statement->execute();
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

            $result = [
                'year' => [],
                'total' => []
            ];

            foreach ($rows as $row) {
                $result['year'][] = $row['year'];
                $result['total'][] = $row['total'];
            }

            return $result;
        } else {
            return ['year' => [], 'total' => []];
        }
    }


    
    public function get_operation_info() {

        if (is_null($this->db_data)) {
            return null;
        }
    
        $sql = "SELECT * FROM ntcs_data ORDER BY rowid DESC LIMIT 1";
    
        try {
            $statement = $this->db_data->prepare($sql);
            $statement->execute();
            $result = $statement->fetch(PDO::FETCH_ASSOC); 
            $statement = null; // 釋放資源
            return $result ?: null; // 沒資料也回傳 null
        } catch (PDOException $e) {
            $statement = null; // 釋放資源
            return null; // 發生錯誤也回傳 null
        }
    }
    




}