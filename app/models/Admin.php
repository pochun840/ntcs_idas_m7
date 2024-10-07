<?php

class Admin{
    private $db;//condb control box
    private $db_data;//devdb tool
    private $dbh;
    private $db_iDas;//iDas db

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db = new Database;
        $this->db = $this->db->getDb();

        $this->db_data = new Database;
        $this->db_data = $this->db_data->getDb_data();

        $this->dbh = new Database;

        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();

        $this->db_iDas_login = new Database;
        $this->db_iDas_login = $this->db_iDas_login->getDb_das_login();

    }

    public function GetActiveSession(){

        $sql ="SELECT * FROM active_sessions ";
        $statement = $this->db_iDas_login ->prepare($sql);
        $statement->execute();
        $results = $statement->fetchAll();
        return $results;
    }
    

    public function Get_Das_Config($config_name)
    {
        $result = $this->db_iDas_login->query("SELECT * FROM config WHERE config_name = '".trim($config_name)."' ");
        $rows = $result->fetch(PDO::FETCH_ASSOC);

        return $rows['config_value'];
    }

    public function DeleteSession($sessionsToDelete){

        foreach ($sessionsToDelete as $sessionId) {
            // 执行删除操作
            $stmt = $this->db_iDas_login->prepare("DELETE FROM active_sessions WHERE id = :sessionId");
            $stmt->bindValue(':sessionId', $sessionId);
            $stmt->execute();
        }
    }

    public function Edit_Guest_Password($new_password)
    {
        $sql = "UPDATE `users` SET password = ? WHERE id = 1 AND username = 'guest'";
        $statement = $this->db_iDas_login->prepare($sql);
        $results = $statement->execute([$new_password]);

        return $results;
    }

    public function Edit_Max_Link($max_user)
    {
        $sql = "UPDATE `config` SET config_value = :max_user WHERE config_name = 'max_concurrent_users' ";
        $statement = $this->db_iDas_login->prepare($sql);
        $statement->bindValue(':max_user', $max_user);
        $results = $statement->execute();

        return $results;
    }

    public function Set_Agent_Ip($ip)
    {
        $sql = "UPDATE `config` SET config_value = :ip WHERE config_name = 'agent_server_ip' ";
        $statement = $this->db_iDas_login->prepare($sql);
        $statement->bindValue(':ip', $ip);
        $results = $statement->execute();

        return $results;
    }

    public function Set_Das_Config($config_name,$value)
    {
        //check config_name exist 
        $sql = "SELECT COUNT(*) as count FROM  `config` WHERE config_name = :config_name ";
        $statement = $this->db_iDas_login->prepare($sql);
        $statement->bindValue(':config_name', $config_name);
        $results = $statement->execute();
        $results = $statement->fetch();

        if($results['count'] > 0){//表示config已存在，用update
            $sql = "UPDATE `config` SET config_value = :value WHERE config_name = :config_name ";
            $statement = $this->db_iDas_login->prepare($sql);
            $statement->bindValue(':config_name', $config_name);
            $statement->bindValue(':value', $value);
            $results = $statement->execute();
        }else{//表示config不存在，用insert
            $sql = "INSERT INTO `config` (config_name, config_value) VALUES (:config_name, :value) ";
            $statement = $this->db_iDas_login->prepare($sql);
            $statement->bindValue(':config_name', $config_name);
            $statement->bindValue(':value', $value);
            $results = $statement->execute();
        }

        return $results;
    }

    public function Edit_Csv_Path($file_path)
    {
        $sql = "UPDATE `config` SET config_value = :max_user WHERE config_name = 'max_concurrent_users' ";
        $statement = $this->db_iDas_login->prepare($sql);
        $statement->bindValue(':max_user', $max_user);
        $results = $statement->execute();

        return $results;
    }

    public function Change_Screw_Local($tool_type)
    {   
        //先清空
        /*$del_sql = 'DELETE FROM `tool_info`';
        $statement = $this->db_dev->prepare($del_sql);
        $del_result = $statement->execute();

        //再新增
        $sql = " INSERT INTO `tool_info` ('tool_type','tool_sn','tool_tmdswversion','tool_tmdhwversion','tool_maintain_counts','tool_maintain_cycles','tool_total_counts','tool_maxtorque','tool_mintorque','tool_maxrpm','tool_minrpm','tool_calibration','tool_tmdtemp','tool_leverthreshold','tool_pushthreshold','tool_trigger','tool_led','tool_motor_voltage','SID1','SID2','SID3','SID4','SID5','SID6','SID7','SID8','SID9','SID10','SID11','SID12','SID13','SID14','SID15','SID16','SID17','SID18','SID19','SID20','SID21','SID22','SID23','SID24','SID25','SID26') VALUES (:tool_type_name,:tool_sn,'1.09','0.01','2095','1000000','2095',:tool_max_torque,:tool_min_torque,:tool_max_rpm,:tool_min_rpm,'1.839','80.0','50','50','2','1','40','0.0','80.0','30000.0','2114','0','100.0','2048.0','2','980','2000','35.0','45.0','3.0','18.37','2','1','1000','2000','10000.0','2048.0','200','0','2000','200','1','0') ";
        $statement = $this->db_dev->prepare($sql);

        //default value
        $tool_type_name = 'SGT-CS';
        $tool_sn = 'PTS';
        $tool_max_torque = '3.0';
        $tool_min_torque = '0.2';
        $tool_max_rpm = 980;
        $tool_min_rpm = 60;

        switch ($tool_type) {
            case 'SGT-CS301':
                $tool_type_name = 'SGT-CS301';
                $tool_sn = 'PTS';
                $tool_max_torque = '1.0';
                $tool_min_torque = '0.1';
                $tool_max_rpm = 980;
                $tool_min_rpm = 60;
                break;
            case 'SGT-CS302F':
                $tool_type_name = 'SGT-CS302F';
                $tool_sn = 'PTS';
                $tool_max_torque = '2.4';
                $tool_min_torque = '0.3';
                $tool_max_rpm = 2000;
                $tool_min_rpm = 140;
                break;
            case 'SGT-CS303':
                $tool_type_name = 'SGT-CS303';
                $tool_sn = 'PTS';
                $tool_max_torque = '3.0';
                $tool_min_torque = '0.375';
                $tool_max_rpm = 980;
                $tool_min_rpm = 60;
                break;
            case 'SGT-CS503':
                $tool_type_name = 'SGT-CS503';
                $tool_sn = 'PTS';
                $tool_max_torque = '3.0';
                $tool_min_torque = '0.375';
                $tool_max_rpm = 1600;
                $tool_min_rpm = 60;
                break;
            case 'SGT-CS505':
                $tool_type_name = 'SGT-CS505';
                $tool_sn = 'PTS';
                $tool_max_torque = '5.0';
                $tool_min_torque = '0.625';
                $tool_max_rpm = 1100;
                $tool_min_rpm = 60;
                break;
            case 'SGT-CS507':
                $tool_type_name = 'SGT-CS507';
                $tool_sn = 'PTS';
                $tool_max_torque = '7.0';
                $tool_min_torque = '0.875';
                $tool_max_rpm = 660;
                $tool_min_rpm = 60;
                break;
            case 'SGT-CS712':
                $tool_type_name = 'SGT-CS712';
                $tool_sn = 'PTS';
                $tool_max_torque = '12.0';
                $tool_min_torque = '1.5';
                $tool_max_rpm = 800;
                $tool_min_rpm = 40;
                break;
            case 'SGT-CS718':
                $tool_type_name = 'SGT-CS718';
                $tool_sn = 'PTS';
                $tool_max_torque = '18';
                $tool_min_torque = '2.25';
                $tool_max_rpm = 550;
                $tool_min_rpm = 30;
                break;
            case 'SGT-CS725':
                $tool_type_name = 'SGT-CS725';
                $tool_sn = 'PTS';
                $tool_max_torque = '25.0';
                $tool_min_torque = '3.125';
                $tool_max_rpm = 350;
                $tool_min_rpm = 20;
                break;
            
            default:
                return false;
                break;
        }

        $statement->bindValue(':tool_type_name', $tool_type_name);
        $statement->bindValue(':tool_sn', $tool_sn);
        $statement->bindValue(':tool_max_torque', $tool_max_torque);
        $statement->bindValue(':tool_min_torque', $tool_min_torque);
        $statement->bindValue(':tool_max_rpm', $tool_max_rpm);
        $statement->bindValue(':tool_min_rpm', $tool_min_rpm);

        $result = $statement->execute();

        if($result){
            return true;
        }else{
            return false;
        }*/

    }


}
