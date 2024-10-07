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
        if( PHP_OS_FAMILY == 'Linux'){
            $con_db = new PDO('sqlite:/var/www/html/database/data_device.db'); 
        }else{
            $con_db = new PDO('sqlite:../data_device.db'); 
            
        }

        $con_db->exec('set names utf-8'); 
        $sql = 'SELECT * FROM device';
        $statement = $con_db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;        
    }


}
