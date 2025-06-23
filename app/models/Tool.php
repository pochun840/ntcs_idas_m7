<?php

class Tool{
    private $db;//condb control box
    private $db_data;//devdb tool
    private $dbh;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
       

        $this->db_iDas_tools = new Database;
        $this->db_iDas_tools = $this->db_iDas_tools->getDb_das_tools();


        $this->dbh = new Database;

    }

    public function GetToolInfo()
    {
        $sql = "SELECT * FROM " . TABLE_NTCS_TOOLS;
        $statement = $this->db_iDas_tools->prepare($sql);
        $statement->execute();
        $row = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $row;
    }

    public function GetControllerInfo()
    {
        $sql = "SELECT * FROM ".TABLE_NTCS_DEVICE;
        $statement = $this->db_iDas_tools->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }


}
