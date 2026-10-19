<?php

class License{
    private $db;//condb control box
    private $db_dev;//devdb tool
    private $db_iDas;//iDas db

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db = new Database;
        $this->db = $this->db->getDb();

        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();

    }

    public function LicenseDB_Initial($tableName)
    {
        // 檢查表是否存在
        $tableExists = false;
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name='".$tableName."' ";
        $result = $this->db_iDas->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name='".$tableName."' ");
        $result->execute();

        $results = $result->fetchAll();
        if (count($results) > 0) {
            $tableExists = true;
        }

        // 如果表不存在，則建立表
        if (!$tableExists) {
            $sql = "CREATE TABLE `".$tableName."` (
                    id    INTEGER,
                    issuance_type INTEGER,
                    trial_period  INTEGER,
                    enter_date    TEXT DEFAULT CURRENT_TIMESTAMP,
                    expired_date  TEXT,
                    license_key   TEXT,
                    PRIMARY KEY(id AUTOINCREMENT)
                )";
            $createTable = $this->db_iDas->prepare($sql);
            $createTable = $createTable->execute();
            if ($createTable) {
                // echo "Table '$tableName' created successfully";
            } else {
                // echo "Error creating table: ";
            }
        }
    }

    public function ActiveKey_Repeat_Check($key)
    {
        $sql = "SELECT count(*) as count FROM license WHERE license_key = :license_key";
        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':license_key', $key);
        $results = $statement->execute();
        $rows = $statement->fetch();

        if ($rows['count'] > 0) {
            return true; // job_id已存在
        }else{
            return false; // job_id不存在
        }
    }

    public function Activate_iDas($key_data,$license_key,$expired_date)
    {
        $sql = "INSERT INTO `license` ('issuance_type','trial_period','expired_date','license_key')
                    VALUES (:issuance_type,:trial_period,:expired_date,:license_key)";
        $statement = $this->db_iDas->prepare($sql);
        $statement->bindValue(':issuance_type', $key_data['issuance_type']);
        $statement->bindValue(':trial_period', $key_data['trial_period']);
        $statement->bindValue(':expired_date', $expired_date);
        $statement->bindValue(':license_key', $license_key);
        $results = $statement->execute();
    }



}
