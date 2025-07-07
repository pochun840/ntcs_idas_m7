<?php
class Datas{

    private $db_data;
    // 在建構子將 Database 物件實例化
    public function __construct(){

        $db = new Database();
        $this->db_data = $db->getDb_data();


    }

    public function getData($type) {
        $directory = '/home/kls'; // 指定目錄路徑

        // 取得目錄中的檔案和子目錄列表
        $fileList = scandir($directory);
        $fileList = array_diff($fileList, array('.', '..'));

        // ✅ 取得現有連線
        /*$db_data = $this->getDb_data();
        if ($db_data === null) {
            error_log("❌ 無法連線到 ntcs_data.db");
            return [];
        }*/

        // 預設 SQL
        $sql = "SELECT * FROM ntcs_data ORDER BY data_time DESC LIMIT 100";

        if ($type == 'OK') {
            $sql = "
                SELECT *
                FROM (
                    SELECT * 
                    FROM ntcs_data
                    WHERE fasten_status in ('4')
                    ORDER BY data_time DESC
                    LIMIT 100
                ) AS recent_data
                ORDER BY data_time DESC
            ";
        }

        if ($type == 'NOK') {
            $sql = "
                SELECT *
                FROM (
                    SELECT * 
                    FROM ntcs_data
                    WHERE fasten_status in ('7', '8')
                    ORDER BY data_time DESC
                    LIMIT 100
                ) AS recent_data
                ORDER BY data_time DESC
            ";
        }

        $statement = $this->db_data->prepare($sql);
        if ($statement !== false) {
            $statement->execute();
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            return $rows;
        } else {
            error_log("❌ prepare SQL 失敗: $sql");
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
    
    $data_db_path = '/home/kls/NTCS7/ntcs_data.db';

    if (!file_exists($data_db_path)) {
        error_log("❌ 檔案不存在: $data_db_path");
        return [];
    }

    try {
        $db_data = new PDO('sqlite:' . $data_db_path);
        $db_data->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "SELECT * FROM ntcs_data ORDER BY id DESC LIMIT 1";
        $stmt = $db_data->query($sql);

        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

        return $rows;
    } catch (PDOException $e) {
        error_log("❌ SQLite 連線失敗: " . $e->getMessage());
        return [];
    }
}





}