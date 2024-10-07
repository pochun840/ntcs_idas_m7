<?php

class Output{
    private $db;//condb control box
    private $dbh;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        

        $this->dbh = new Database;

        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();

        $this->db_iDas_device = new Database;
        $this->db_iDas_device = $this->db_iDas_device->getDb_das_device();


    }

    //get_input_by_job_id
    public function get_output_by_job_id($output_job_id)
    {   
        $sql = "SELECT * FROM output WHERE output_job_id = ? ORDER BY output_event";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$output_job_id]);
        $row = $statement->fetchall(PDO::FETCH_ASSOC);

        return $row;
    }

    public function check_job_output_conflict($job_id,$event_id)
    {
        $sql = "SELECT count(*) as count FROM output WHERE output_jobid = ? AND output_event = ?";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute([$job_id,$event_id]);
        $rows = $statement->fetch();

        if ($rows['count'] > 0) {
            return true; // job event已存在
        }else{
            return false; // job event不存在
        }
    }

    public function check_job_event_conflict($output_job_id,$output_event){
        
        $sql = "SELECT *   FROM output WHERE output_job_id = ? AND output_event = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$output_job_id,$output_event]);
        $rows = $statement->fetch(PDO::FETCH_ASSOC);

        return $rows;
    }

    public function check_event_conflict($output_job_id,$output_event){
        
        $sql = "SELECT count(*) FROM output WHERE output_job_id = ? AND output_event = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$output_job_id,$output_event]);
        $count = $statement->fetchColumn();

        return (int)$count;
    }

    public function check_event_conflict_by_job_id($output_job_id){
        
        $sql = "SELECT * FROM output WHERE output_job_id = ? AND output_event NOT IN('6','7','8') ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$output_job_id]);
        $rows = $statement->fetch(PDO::FETCH_ASSOC);

        return $rows;
    }


    
    public function check_event_pin_by_job_id($output_job_id,$wave){
        
        $sql = "SELECT * FROM output WHERE output_job_id = ? AND  wave = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$output_job_id,$wave]);
        $rows = $statement->fetch(PDO::FETCH_ASSOC);

        return !empty($rows);
    }


    public function create_output($jobdata){    
        $sql = "INSERT INTO `output` (output_job_id, output_pin, output_event, wave, wave_on) ";
        $sql .= "VALUES (:output_job_id, :output_pin, :output_event, :wave, :wave_on);";

        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':output_job_id', $jobdata['output_job_id']);
        $statement->bindValue(':output_pin', $jobdata['output_pin']);
        $statement->bindValue(':output_event', $jobdata['output_event']);
        $statement->bindValue(':wave', $jobdata['wave']);
        $statement->bindValue(':wave_on', $jobdata['wave_on']);
        $results = $statement->execute();

        return $results;
    }


    public function edit_output($jobdata){

        $sql = "UPDATE `output` 
        SET output_event = :output_event, 
            output_pin  = :output_pin,
            wave = :wave, 
            wave_on = :wave_on ";
        $sql .= "WHERE output_event = :output_event  AND output_job_id = :output_job_id ";

        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':output_job_id', $jobdata['output_job_id']);
        $statement->bindValue(':output_event', $jobdata['output_event']);
        $statement->bindValue(':output_pin', $jobdata['output_pin']);
        $statement->bindValue(':wave', $jobdata['wave']);
        $statement->bindValue(':wave_on', $jobdata['wave_on']);
        $results = $statement->execute();
        return $results;

    } 

    public function copy_output_by_id($from_job_id,$to_job_id){
        // 判斷job_id是否存在，若存在就先把舊的刪除
        // $dupli_flag true:表示job_id已存在 false:表示job_id不存在
        if(true){//先刪除再複製
            $this->delete_output_by_id($to_job_id);
        }
        $sql= "INSERT INTO output ( output_jobid,output_pin,output_event,wave,wave_on,wave_off )
                SELECT  ?,output_pin,output_event,wave,wave_on,wave_off 
                FROM    output
                WHERE output_jobid = ? ";
        $statement = $this->db->prepare($sql);

        return $results = $statement->execute([$to_job_id,$from_job_id]);
    }

    //delete output by job_id
    public function delete_output_by_id($job_id){

        $sql= "DELETE FROM output WHERE output_job_id = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$job_id]);

        return $results;
    }
    //delete output by job_id and event_id
    public function delete_output_event_by_id($output_job_id,$output_event){

        $sql= "DELETE FROM output WHERE output_job_id = ? AND output_event = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$output_job_id,$output_event]);

        return $results;
    }

    //set input_alljob
    public function set_output_alljob($output_job_id){

        $sql= "UPDATE device SET device_output_all_job = ? ";
        $statement = $this->db_iDas_device->prepare($sql);
        $results = $statement->execute([$output_job_id]);

        return $results;
    }
    
    public function generateTableCell($value,$value2) {
        if($value >= 1 && $value <= 11){
            $tableCells = "";
            for($i = 1; $i <= 11; $i++){
                if($i == $value){ 
                    if($value2 == 1){
                        $img = '<img src="./img/signal01.png" style="max-width: 50px;">';
                    }else if($value2 == 2){
                        $img = '<img src="./img/signal02.png" style="max-width: 50px;">';
                    }else if($value2 == 3){
                        $img = '<img src="./img/trigger.png" style="max-width: 50px;">';
                    }else{
                        $img = '';
                    }
                    $tableCells .= "<td  data-outputpin = '".$value."'>".$img."</td>";
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
