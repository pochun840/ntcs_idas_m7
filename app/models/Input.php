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
        $sql = "SELECT * FROM JOBInput_lst WHERE JOBID = ? ORDER BY CASE WHEN EvenID >= 200 THEN 0 ELSE 1 END, EvenID ";
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
        $sql = " SELECT  * FROM  JOB_lst  ORDER BY JOBID ASC ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();
        return $result;
    }

    public function check_job_event_conflict($input_job_id,$input_event){
        
        $sql = "SELECT *  FROM JOBInput_lst WHERE JOBID = ? AND EvenID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$input_job_id,$input_event]);
        $rows = $statement->fetch();

        return $rows;
    }

    public function check_job_event($input_job_id){
        
        $sql = "SELECT *  FROM JOBInput_lst WHERE JOBID = ? ";
        $statement = $this->db_iDas->prepare($sql);
        $statement->execute([$input_job_id]);
        $rows = $statement->fetchAll();

        return $rows;

    }

    public function create_input($input_data) {   


        $sql = "INSERT INTO `JOBInput_lst` (JOBID, Pin, EvenID, signal, Wp_Ready_Confirm) ";
        $sql .= "VALUES (:JOBID, :Pin, :EvenID, :signal, :Wp_Ready_Confirm) ";
    
        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':JOBID', $input_data['JOBID']);
        $statement->bindValue(':Pin', $input_data['Pin']);
        $statement->bindValue(':EvenID', $input_data['EvenID']);
        $statement->bindValue(':signal', $input_data['signal']);
        $statement->bindValue(':Wp_Ready_Confirm', 0);
    
        $results = $statement->execute();
    
        return $results;
    }

   


    //delete input by job_id
    public function delete_input_by_id($job_id){

        $sql= "DELETE FROM JOBInput_lst WHERE JOBID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$job_id]);

        return $results;
    }


    //delete input by job_id and event_id
    public function delete_input_event_by_id($job_id,$input_event){
        $sql= "DELETE FROM JOBInput_lst WHERE JOBID = ? AND EvenID = ?";
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$job_id,$input_event]);

        return $results;
    }

    //set input_alljob
    public function set_input_alljob($input_job_id) {
        $sql = "UPDATE JOB_lst SET input_unified = CASE 
                    WHEN input_unified = '1' THEN '0' 
                    WHEN input_unified = '0' THEN '1' 
                    ELSE input_unified 
                 END 
                 WHERE JOBID = ?";
        
        $statement = $this->db_iDas->prepare($sql);
        $results = $statement->execute([$input_job_id]);
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
