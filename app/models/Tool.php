<?php

class Tool{
    private $db;//condb control box
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

    /*public function GetControllerInfo()
    {
        $sql = "SELECT * FROM device ";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    public function GetDeviceInfo()
    {
        $sql = "SELECT * FROM device_info ";
        $statement = $this->db_dev->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    public function GetToolInfo()
    {
        $sql = "SELECT * FROM tool_info ";
        $statement = $this->db_dev->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }*/

}
