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


    /**
     * 取得折線圖用的最新 N 筆鎖附資料。
     *
     * 重點：
     * 1. 先依 rowid DESC 抓最新資料。
     * 2. 外層再依 rid ASC 排回舊 → 新，讓折線圖由左到右顯示時間順序。
     * 3. LIMIT 強制限制在 1~100，避免前端錯誤參數造成大量查詢。
     *
     * @param string $type  ALL / OK / NOK
     * @param int    $limit 預設 25 筆
     * @return array
     */
    public function getLineChartData($type = 'ALL', $limit = 25) {

        if (is_null($this->db_data)) {
            return [];
        }

        $type  = strtoupper(trim((string)$type));
        $limit = (int)$limit;

        if ($limit <= 0) {
            $limit = 25;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        $where = '';
        if ($type === 'OK') {
            $where = 'WHERE fasten_status IN (4,5,6)';
        } elseif ($type === 'NOK' || $type === 'NG') {
            $where = 'WHERE fasten_status IN (7,8)';
        }

        $sql = "
            SELECT *
            FROM (
                SELECT rowid AS rid, *
                FROM ntcs_data
                {$where}
                ORDER BY rowid DESC
                LIMIT :limit
            ) AS recent_data
            ORDER BY rid ASC
        ";

        try {
            $statement = $this->db_data->prepare($sql);
            if ($statement === false) {
                return [];
            }

            $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }


    /**
     * Toruqe_line_chart 匯出 CSV：依時間區間抓最新 25 筆。
     * SQL 規則：
     * SELECT * FROM ntcs_data
     * WHERE data_time BETWEEN ...
     * ORDER BY data_time DESC LIMIT 25
     */
    public function getLineChartCsvExportData($start_date, $end_date, $limit = 25) {

        if (is_null($this->db_data)) {
            return [];
        }

        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 25;
        }
        if ($limit > 25) {
            $limit = 25;
        }

        $sql = "
            SELECT *
            FROM ntcs_data
            WHERE data_time BETWEEN :start_date AND :end_date
            ORDER BY data_time DESC
            LIMIT :limit
        ";

        try {
            $statement = $this->db_data->prepare($sql);
            if ($statement === false) {
                return [];
            }

            $statement->bindValue(':start_date', $start_date, PDO::PARAM_STR);
            $statement->bindValue(':end_date', $end_date, PDO::PARAM_STR);
            $statement->bindValue(':limit', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
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