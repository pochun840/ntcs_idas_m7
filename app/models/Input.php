<?php

class Input{
    private $db;//condb control box
    private $dbh;
    private $db_iDas;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db = new Database;
        $this->db = $this->db->getDb();

        $this->dbh = new Database;


        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();

        $this->db_iDas_device = new Database;
        $this->db_iDas_device = $this->db_iDas_device->getDb_das_device();


    }

    //get_input_by_job_id
    public function get_input_by_job_id($job_id)
    {   
        $sql = "SELECT * FROM input WHERE input_job_id = ? ORDER BY CASE WHEN input_event >= 200 THEN 0 ELSE 1 END, input_event";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$job_id]);
        $row = $statement->fetchall(PDO::FETCH_ASSOC);

        return $row;
    }

    //get device_input_alljob
    public function get_input_alljob()
    {   
        $sql = "SELECT * FROM device ";
        $statement = $this->db_iDas_device->prepare($sql);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    //get all job
    public function get_job_list()
    {
        $sql = " SELECT  * FROM job  ORDER BY job_id ASC ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result;
    }

    public function check_job_event_conflict($input_job_id,$input_event){
        
        $sql = "SELECT *  FROM input WHERE input_job_id = ? AND input_event = ?";
        $statement = $this->db->prepare($sql);
        $statement->execute([$input_job_id,$input_event]);
        $rows = $statement->fetch();

        return $rows;
    }

    public function check_job_event($input_job_id){
        
        $sql = "SELECT *  FROM input WHERE input_job_id = ? ";
        $statement = $this->db->prepare($sql);
        $statement->execute([$input_job_id]);
        $rows = $statement->fetchAll();

        return $rows;

    }

    public function create_input($jobdata){   
    
        $sql = "INSERT INTO `input` (input_job_id, input_event, input_pin, input_wave, gateconfirm, pagemode, input_seqid) ";
        $sql .= "VALUES (:input_job_id, :input_event, :input_pin, :input_wave, :gateconfirm, :pagemode, :input_seqid);";

        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':input_job_id', $jobdata['input_job_id']);
        $statement->bindValue(':input_event', $jobdata['input_event']);
        $statement->bindValue(':input_pin', $jobdata['input_pin']);
        $statement->bindValue(':input_wave', $jobdata['input_wave']);
        $statement->bindValue(':gateconfirm', $jobdata['gateconfirm']);
        $statement->bindValue(':pagemode', $jobdata['pagemode']);
        $statement->bindValue(':input_seqid', $jobdata['input_seqid']);

        $results = $statement->execute();

        return $results;
    }

    public function edit_input($jobdata){

        $sql = "UPDATE `input` 
                    SET input_event = :input_event, 
                        input_pin  = :input_pin,
                        input_wave = :input_wave, 
                        input_pin  = :input_pin,
                        gateconfirm = :gateconfirm, 
                        pagemode = :pagemode, 
                        input_seqid = :input_seqid ";
        $sql .= "WHERE input_event = :input_event  AND input_job_id = :input_job_id;";

        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':input_job_id', $jobdata['input_job_id']);
        $statement->bindValue(':input_event', $jobdata['input_event']);
        $statement->bindValue(':input_pin', $jobdata['input_pin']);
        $statement->bindValue(':input_wave', $jobdata['input_wave']);
        $statement->bindValue(':gateconfirm', $jobdata['gateconfirm']);
        $statement->bindValue(':pagemode', $jobdata['pagemode']);
        $statement->bindValue(':input_seqid', $jobdata['input_seqid']);
        $results = $statement->execute();
        return $results;
    }


    public function copy_input_by_id($from_job_id,$to_job_id){
        // 判斷job_id是否存在，若存在就先把舊的刪除
        // $dupli_flag true:表示job_id已存在 false:表示job_id不存在
        if(true){//先刪除再複製
            $this->delete_input_by_id($to_job_id);
        }
        $sql= "INSERT INTO input ( input_jobid,input_event,input_pin1,input_pin2,input_pin3,input_pin4,input_pin5,input_pin6,input_pin7,input_pin8,input_pin9,input_pin10,input_gateconfirm,input_pagemode )
                SELECT  ?,input_event,input_pin1,input_pin2,input_pin3,input_pin4,input_pin5,input_pin6,input_pin7,input_pin8,input_pin9,input_pin10,input_gateconfirm,input_pagemode
                FROM    input
                WHERE input_jobid = ? ";
        $statement = $this->db->prepare($sql);

        return $results = $statement->execute([$to_job_id,$from_job_id]);
    }

    //delete input by job_id
    public function delete_input_by_id($job_id){

        $sql= "DELETE FROM input WHERE input_job_id = ?";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute([$job_id]);

        return $results;
    }


    //delete input by job_id and event_id
    public function delete_input_event_by_id($job_id,$input_event){
        $sql= "DELETE FROM input WHERE input_job_id = ? AND input_event = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$job_id,$input_event]);

        return $results;
    }

    //set input_alljob
    public function set_input_alljob($input_job_id){
        $sql = "UPDATE device SET device_input_all_job = ?";
        $statement = $this->db_iDas_device->prepare($sql);
        $results   = $statement->execute([$input_job_id]);
        return $results;
    }

    public function generateTableCell($value,$value2) {
        if($value >= 2 && $value <= 10){
            $tableCells = "";
            for($i = 2; $i <= 10; $i++){
                if($i == $value){ 
                    if($value2 == 1){
                        $img = '<img src="./img/high.png" style="max-width: 50px;">';
                    }else{
                        $img = '<img src="./img/low.png" style="max-width: 50px;">';
                    }
                    $tableCells .= "<td>".$img."</td>";
                }else{
                    $tableCells .= "<td></td>";
                }
            }
            return $tableCells;
        }else{
            return ""; 
        }
    }
}
