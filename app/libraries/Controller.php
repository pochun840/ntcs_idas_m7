<?php

class Controller
{
    // 載入 model
    public function model($model)
    {
        require_once '../app/models/' . $model . '.php';
        return new $model();
    }

    // 載入 view
    // 其中 view 可能有需要從 Controller 帶過去的資料，故多了 $data 陣列作為第二個參數
    public function view($view, array $data = [])
    {
        $this->language_auto(); //從瀏覽器帶入語系
        //multi language
        $language = array("language"=>$_SESSION['language']);
        $data = array_merge($data,$language);
        
        //權限
        $privilege = array("privilege"=>$_SESSION['privilege']);
        $data = array_merge($data,$privilege);

        // 如果檔案存在就引入它
        if(file_exists('../app/views/' . $view . '.php')){

            if(file_exists('../app/language/' . $data['language'] . '.php')){
                require_once '../app/language/' . $data['language'] . '.php';
            } else { //預設採用英文
                require_once '../app/language/en-us.php';
            }

            require_once '../app/views/inc/header.php';
            require_once '../app/views/' . $view . '.php';
            require_once '../app/views/inc/footer.php';
            
        } else {
            die('View does not exist');
        }
    }

    public function language_auto($value='')
    {
        // 如果$_SESSION['language'] 未設定 或為空 就從瀏覽器訊息帶入
        if( !isset($_SESSION['language']) || $_SESSION['language'] == '' ){
            $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 4);
            if (preg_match("/zh-cn/i", $lang)){
                $_SESSION['language'] = 'zh-cn';
            }else if(preg_match("/zh-tw/i", $lang)){
                $_SESSION['language'] = 'zh-tw';
            }else if(preg_match("/en/i", $lang)){
                $_SESSION['language'] = 'en-us';
            }else{//預設
                $_SESSION['language'] = 'en-us';
            }
        }

        setcookie('language', $_SESSION['language'], time() + (365 * 24 * 60 * 60), '/');
        
    }


    public function logMessage($message) {
        $timestamp = date("Y-m-d H:i:s");
        $logMessage = "[$timestamp] $message\n";
       
    }

    public function isMobileCheck($value='')
    {
        //Detect special conditions devices
        $iPod = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        if(stripos($_SERVER['HTTP_USER_AGENT'],"Android") && stripos($_SERVER['HTTP_USER_AGENT'],"mobile")){
            $Android = true;
        }else if(stripos($_SERVER['HTTP_USER_AGENT'],"Android")){
            $Android = false;
            $AndroidTablet = true;
        }else{
            $Android = false;
            $AndroidTablet = false;
        }
        $webOS = stripos($_SERVER['HTTP_USER_AGENT'],"webOS");
        $BlackBerry = stripos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
        $RimTablet= stripos($_SERVER['HTTP_USER_AGENT'],"RIM Tablet");
        //do something with this information
        if( $iPod || $iPhone || $iPad || $Android || $AndroidTablet || $webOS || $BlackBerry || $RimTablet){
            return true;
        }else{
            return false;
        }
    }


    //權限驗證function
    public function LoginCheck($value='')
    {
        if( PHP_OS_FAMILY == 'Linux'){
            $con_db = new PDO('sqlite:/var/www/html/database/das.db'); 
        }else{
            $con_db = new PDO('sqlite:../data.db'); 
        }

        $con_db->exec('set names utf-8'); 
        $sql = 'SELECT * FROM operator';
        $statement = $con_db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['operator_loginflag'];        
    }

    //扭力單位轉換
    public function unitConvert($torValue, $inputType, $TransType) {
        $torValue = floatval($torValue);
        $inputType = (int)($inputType);
        $TransType = (int)($TransType);

        $TorqueUnit = [
            "N_M" => 1,
            "KGF_M" => 0,
            "KGF_CM" => 2,
            "LBF_IN" => 3
        ];


        if ($inputType === $TorqueUnit["N_M"]) {
            if ($TransType === $TorqueUnit["KGF_M"]) {
                return round($torValue * 0.102, 4);
            } elseif ($TransType === $TorqueUnit["KGF_CM"]) {
                return round($torValue * 10.2, 2);
            } elseif ($TransType === $TorqueUnit["LBF_IN"]) {
                return round($torValue * 10.2 * 0.86805, 2);
            } elseif ($TransType === $TorqueUnit["N_M"]) {
                return round($torValue, 3);
            }
        } elseif ($inputType === $TorqueUnit["KGF_M"]) {
            if ($TransType === $TorqueUnit["KGF_M"]) {
                return round($torValue, 4);
            } elseif ($TransType === $TorqueUnit["KGF_CM"]) {
                return round($torValue * 100, 2);
            } elseif ($TransType === $TorqueUnit["LBF_IN"]) {
                return round($torValue * 100 * 0.86805, 2);
            } elseif ($TransType === $TorqueUnit["N_M"]) {
                return round($torValue * 9.80392156, 3);
            }
        } elseif ($inputType === $TorqueUnit["KGF_CM"]) {
            if ($TransType === $TorqueUnit["KGF_M"]) {
                return round($torValue * 0.01, 4);
            } elseif ($TransType === $TorqueUnit["KGF_CM"]) {
                return round($torValue, 2);
            } elseif ($TransType === $TorqueUnit["LBF_IN"]) {
                return round($torValue * 0.86805, 2);
            } elseif ($TransType === $TorqueUnit["N_M"]) {
                return round($torValue * 0.0980392156, 3);
            }
        } elseif ($inputType === $TorqueUnit["LBF_IN"]) {
            if ($TransType === $TorqueUnit["KGF_M"]) {
                return round($torValue * 1.152 * 0.01, 4);
            } elseif ($TransType === $TorqueUnit["KGF_CM"]) {
                return round($torValue * 1.152, 2);
            } elseif ($TransType === $TorqueUnit["LBF_IN"]) {
                return round($torValue, 2);
            } elseif ($TransType === $TorqueUnit["N_M"]) {
                return round($torValue * 0.11294117637119998, 3);
            }
        }
    }

    public function TorqueDelta($toolMaxTorque, $device_torque_unit) {
        $delta = 0.0;
        $capTrq = 0.0;
        $checkTorque = 0;

        switch ($toolMaxTorque) {
            case 1:
                $delta = $this->unitConvert(0.0006, 1, $device_torque_unit);
                break;
            case 3:
                $delta = $this->unitConvert(0.002, 1, $device_torque_unit);
                break;
            case 5:
                $delta = $this->unitConvert(0.003, 1, $device_torque_unit);
                break;
            case 7:
                $delta = $this->unitConvert(0.004, 1, $device_torque_unit);
                break;
            case 12:
                $delta = $this->unitConvert(0.007, 1, $device_torque_unit);
                break;
            case 16:
            case 18:
                $delta = $this->unitConvert(0.01, 1, $device_torque_unit);
                break;
            case 25:
                $delta = $this->unitConvert(0.014, 1, $device_torque_unit);
                break;
            default:
                $delta = $this->unitConvert(0.003, 1, $device_torque_unit);
        }

        return $delta;
    }

    //取得tcscon device table資訊
    public function Device_Info()
    {
        try {
            if (PHP_OS_FAMILY === 'Linux') {
                $db_path = '/var/www/html/database/data_device.db';
            } else {
                $db_path = '../data_device.db';
            }

            if (!file_exists($db_path)) {
                throw new Exception("❌ Database file not found: $db_path");
            }

            $con_db = new PDO('sqlite:' . $db_path);
            $con_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $con_db->exec('PRAGMA encoding = "UTF-8"');

            $sql = 'SELECT * FROM device';

            $statement = $con_db->prepare($sql);
            if (!$statement) {
                $errorInfo = $con_db->errorInfo();
                throw new Exception("❌ SQL prepare failed: " . $errorInfo[2]);
            }

            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);

            return $row;
        } catch (Exception $e) {
            error_log($e->getMessage());
            echo $e->getMessage(); // 或回傳空陣列 return [];
            return null;
        }
    }

    

    //用起子的狀態 來判斷是否可以匯入匯出資料???? 
    public function idas_check(){

        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;  // 使用定義的常數
        $port = 502;
        $unitId = 0;
        $startAddress = 4345;
        $quantity = 1;

        $response = ['result' => null, 'error' => ''];

        // 驗證 IP 格式
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $response['error'] = "無效的 IP 位址：$ip";
            echo json_encode($response);
            return;
        }

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            $response['result'] = $data[1] ?? null;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }

        return $response;
    }

        
    //取得控制器 目前用了多少容量
    public function check_controller_size(){

        require_once '../app/config/config.php';  // 載入常數
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;  // 使用定義的常數
        $port = 502;
        $unitId = 0;
        $startAddress = 269;
        $quantity = 1;

        $response = ['result' => null, 'error' => ''];

        // 驗證 IP 格式
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            $response['error'] = "無效的 IP 位址：$ip";
            echo json_encode($response);
            return;
        }

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            // 功能碼 FC3: 讀取保持暫存器
            $data = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            $response['result'] = $data[1] ?? null;

        } catch (Exception $e) {
            $response['error'] = $e->getMessage() ?: 'Modbus 通訊失敗';
        }

        echo json_encode($response);
    }

    /*public function Call_Controller_Job()
    {
        //get controller ip
        $controller_ip = $this->EquipmentModel->GetControllerIP(1);

        $input_check = true;
        $error_message = '';
        if( !empty($_POST['job_id']) && isset($_POST['job_id'])  ){
            $job_id = $_POST['job_id'];
        }else{ 
            $input_check = false;
            $error_message .= "job_id,";
        }
        if( !empty($_POST['seq_id']) && isset($_POST['seq_id'])  ){
            $seq_id = $_POST['seq_id'];
        }else{ 
            $input_check = false;
            $error_message .= "seq_id,";
        }

        if ($input_check) {
            require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
            $modbus = new ModbusMaster($controller_ip, "TCP");
            try {
                $modbus->port = 502;
                $modbus->timeout_sec = 10;
                $dataTypes = array("INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT");
                
                // FC 16
                $test = array(0);
                $test1 = array(1);
                $modbus->writeMultipleRegister(0, 517, $test, $dataTypes);//起子禁用
                echo $modbus->status;
                exit();

            } catch (Exception $e) {
             
                $this->logMessage('operation-1','result-2',json_encode( array('job_id'=> $job_id,'seq_id'=> $seq_id, 'raw' => $_POST ) ));
                echo $modbus->status;
                exit();
            }
        }else{
            echo json_encode(array('error' => $error_message));
            exit();
        }

        echo json_encode($job_detail);
        exit();
        
    }*/


}
