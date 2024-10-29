<?php
class Datas{
    private $db;//condb control box
    private $db_dev;//devdb tool
    private $db_data;//devdb tool
    private $dbh;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db = new Database;
        $this->db = $this->db->getDb();

        $this->db_data = new Database;
        $this->db_data = $this->db_data->getDb_data();

        $this->dbh = new Database;

    }

    public function getData($type)
    {
        $sql = "SELECT * FROM data ORDER BY data_time DESC LIMIT 100 ";
        if($type == 'OK'){
            $sql = "SELECT * FROM ( SELECT * FROM data WHERE fasten_status = 4 or fasten_status = 5 or fasten_status = 6 ORDER BY data_time DESC LIMIT 100 ) AS recent_data ORDER BY data_time DESC;";
        }
        if($type == 'NOK'){
            $sql = "SELECT * FROM ( SELECT * FROM data WHERE fasten_status = 7 or fasten_status = 8 ORDER BY data_time DESC LIMIT 100 ) AS recent_data ORDER BY data_time DESC;";
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

    public function get_range_data($start_date,$end_date)
    {
        $sql = "SELECT * FROM data 
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

}