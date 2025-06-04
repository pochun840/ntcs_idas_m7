<?php
class Datas{

    private $db_data;
    // 在建構子將 Database 物件實例化
    public function __construct(){

        $db_instance = new Database;
        $this->db_data = $db_instance->getDb_data();



    }

    public function getData($type){
        
        $sql = "SELECT * FROM ntcs_data ORDER BY data_time DESC LIMIT 100 ";
        if($type == 'OK'){
            $sql = "SELECT * FROM ( SELECT * FROM ntcs_data WHERE fasten_status in('4')  ORDER BY data_time DESC LIMIT 100 ) AS recent_data ORDER BY data_time DESC ";
        }
        if($type == 'NOK'){
            $sql = "SELECT * FROM ( SELECT * FROM ntcs_data WHERE fasten_status  in('7','8')  ORDER BY data_time DESC LIMIT 100 ) AS recent_data ORDER BY data_time DESC ";
        }
        
        $statement = $this->db_data->prepare($sql);
        if($statement != false){
            $results = $statement->execute();
            $row = $statement->fetchall(PDO::FETCH_ASSOC);

            return $row;
        }else{
            return array();
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

}