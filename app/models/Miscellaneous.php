<?php

class Miscellaneous{
    private $db;//condb control box
    private $db_data;//devdb tool
    private $dbh;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db_iDas = new Database;
        $this->db_iDas = $this->db_iDas->getDb_das();

    }


    public function details($mode){
        
        $array = array();
        if($mode == "reverse_direction"){

            $array = array(
                0 => 'CW',
                1 => 'CCW',
                2 => 'Disable'
                
            );
        }

        if($mode == "torque_unit"){
            $array = array(
                0 => 'kgf.cm',
                1 => 'lbf.in',
                2 => 'kgf.m',
                3 => 'N.m',
                
            );
        }

        if($mode == "target_option" ){
            $array = array(
                0 => 'Torque',
                1 => 'Angle',
                2 => 'Delay Time',
                
            );
        }

        if($mode == "target_option_change" ){
            $array = array(
                1 => 'Angle',
                2 => 'Delay Time',
                
            );
        }

        if($mode =="io_input"){
            $array = array(
                101 => 'Disable',
                102 => 'Enable',
                103 => 'Clear',
                104 => 'Confirm',
                105 => 'Start-IN(Remote)',
                106 => 'Unscrew(Remote)',
                107 => 'Sequence Clear',
                108 => 'Reboot',
                109 => 'Gate Once',
                110 => 'UserDefine1',
                111 => 'UserDefine2',
                112 => 'UserDefine3',
                113 => 'UserDefine4',
                114 => 'UserDefine5',
            );
        }

        if($mode =="io_output"){
            $array = array(
                1   => 'OK',
                2   => 'NG',
                3   => 'NG-High',
                4   => 'NG-Low',
                5   => 'OK-Sequence',
                6   => 'OK-Job',
                7   => 'Tool Runing',
                8   => 'Tool Trigger',
                9   => 'Reverse',
                10  => 'BS',
                11  => 'Barcode',
                12  => 'UserDefine1',
                13  => 'UserDefine2',
                14  => 'UserDefine3',
                15  => 'UserDefine4',
                16  => 'UserDefine5',
            );
        }

        if($mode =="chart_mode"){
            $array = array(
                1 => 'Torque/Time(MS)',
                2 => 'Angle/Time(MS)',
                3 => 'RPM/Time(MS)',
                4 => 'Torque/Angle',
            );
        }

        if($mode == "chart_menu"){
            $array = array(
                1 => array('name'=>'Torque Time', 'id'=>'torque_time'),
                2 => array('name'=>'Angle Time',  'id'=>'angle_time'),
                3 => array('name'=>'RPM Time',    'id'=>'rpm_time'),
                4 => array('name'=>'Torque Angle','id'=>'torque_angle'),
            );
        }


        if($mode == "status"){
            $array = array(
                0 => 'INIT', 
                1 => 'READY',
                2 => 'RUNNING',
                3 => 'REVERSE',
                4 => 'OK',
                5 => 'OK-SEQ',
                6 => 'OK-JOB',
                7 => 'NG',
                8 => 'NS',
                9 => 'SETTING',
                10 => 'EOC',
                11 => 'C1',
                12 => 'C1_ERR',
                13 => 'C2',
                14 => 'C2_ERR',
                15 => 'C4',
                16 => 'C4_ERR',
                17 => 'C5',
                18 => 'C5_ERR',
                19 => 'BS'
            );

        }

        if($mode =="lang"){
            $array = array(
                0 => 'English',
                1 => '繁體中文',
                2 => '簡體中文',
            );    
        }

        return $array;

    }

    #驗證name 
    public function validateName($jobName){
        if (!empty($jobName)) {
            if (preg_match('/^[a-zA-Z0-9-]+$/', $jobName)) {
                if (strlen($jobName) > 12) {
                    return  false;
                } else {
                    return true;
                }
            } else {
                return false;
            }
        }else{
            return false;
        }
    }

    public function validateUnscrewPower($unscrewPower) {
        if (is_numeric($unscrewPower)) {
            if ($unscrewPower > 0 && $unscrewPower <= 10) {
                return true; 
            } else {
                return false; 
            }
        } else {
            return false; 
        }
    }

    public function validate($value, $type) {
        switch ($type) {
            case 'name':
                return !empty($value) && 
                       preg_match('/^[a-zA-Z0-9-]+$/', $value) && 
                       strlen($value) <= 12;
                       
            case 'reverse_power':
                return is_numeric($value) && 
                       $value > 0 && 
                       $value <= 10;
                       
            default:
                return false;
        }
    }


    public function seq_validate($value, $type) {
        switch ($type) {
            // Seq_name
            case 'name':
                return !empty($value) && 
                       preg_match('/^[a-zA-Z0-9-]+$/', $value) && 
                       strlen($value) <= 12;
    
            // 顆數
            case 'tightenRepeat':
                return is_numeric($value) && 
                       $value >= 1 && 
                       $value <= 99;
            
            //join_val
            case 'join_val':
                return !empty($value);
                

            // OKTIME
            case 'okTime':
                return is_numeric($value) && 
                       $value >= 0.0 && 
                       $value <= 9.9;
    
            // K_value
            case 'kValue':
                return is_numeric($value) && 
                       $value >= 30 && 
                       $value <= 300;
    
            // offset
            case 'offset':
                return is_numeric($value) && 
                       $value >= -254 && 
                       $value <= 254;
    
            default:
                return false;
        }
    }


    #扭力單位轉換
    public function unitarr_change($torValues, $inputType, $TransType){
        
        $inputType = (int)$inputType;
        $TransType = (int)$TransType;

        
        $TorqueUnit = [
            "N_M" => 1,
            "KGF_M" => 0,
            "KGF_CM" => 2,
            "LBF_IN" => 3
        ];

        $convertedValues = array();
        foreach($torValues as $torValue) {
           
            $torValue = floatval($torValue);
           
            if ($inputType == $TorqueUnit["N_M"]) {
                if ($TransType == $TorqueUnit["KGF_M"]) {
                
                    $convertedValues[] = round($torValue * 0.102, 4);
                } elseif ($TransType == $TorqueUnit["KGF_CM"]) {
                
                    $convertedValues[] = round($torValue * 10.2, 2);
                } elseif ($TransType == $TorqueUnit["LBF_IN"]) {
                  
                    $convertedValues[] = round($torValue * 10.2 * 0.86805, 2);
                } elseif ($TransType == $TorqueUnit["N_M"]) {
                  
                    $convertedValues[] = round($torValue, 3);
                }
            } 
            
            elseif ($inputType == $TorqueUnit["KGF_M"]) {
                if ($TransType == $TorqueUnit["KGF_M"]) {
                    $convertedValues[] = round($torValue, 4);
                } elseif ($TransType == $TorqueUnit["KGF_CM"]) {
                    $convertedValues[] = round($torValue * 100, 2);
                } elseif ($TransType == $TorqueUnit["LBF_IN"]) {
                    
                    $convertedValues[] = round($torValue * 100 * 0.86805, 2);
                } elseif ($TransType == $TorqueUnit["N_M"]) {
                    $convertedValues[] = round($torValue * 9.80392156, 3);
                }
            }

            elseif ($inputType == $TorqueUnit["KGF_CM"]) {
                if ($TransType == $TorqueUnit["KGF_M"]) {
                    $convertedValues[] = round($torValue * 0.01, 4);
                } elseif ($TransType == $TorqueUnit["KGF_CM"]) {
                    $convertedValues[] = round($torValue, 2);
                } elseif ($TransType == $TorqueUnit["LBF_IN"]) {
                    $convertedValues[] = round($torValue * 0.86805, 2);
                } elseif ($TransType == $TorqueUnit["N_M"]) {
                    $convertedValues[] = round($torValue * 0.0980392156, 3);
                }
            }

            elseif ($inputType == $TorqueUnit["LBF_IN"]) {
                
                if ($TransType == $TorqueUnit["KGF_M"]) {
                    $convertedValues[] = round($torValue * 1.152 * 0.01, 4);
                } elseif ($TransType == $TorqueUnit["KGF_CM"]) {
                    $convertedValues[] = round($torValue * 1.152, 2);
                } elseif ($TransType == $TorqueUnit["LBF_IN"]) {
                    $convertedValues[] = round($torValue, 2);
                } elseif ($TransType == $TorqueUnit["N_M"]) {
                    $convertedValues[] = round($torValue * 0.11294117637119998, 3);
                }
            }
        }

        return $convertedValues;
    }


    public function lang_load(){

        $language = $_COOKIE['language'] ?? 'en-us';
        $language = preg_replace('/[^a-zA-Z0-9_-]/', '', $language); 
    
        $language_file = '../app/language/' . $language . '.php';
        return  $language_file;
     
    }

    public function generateErrorResponse($errorType, $errorMessage) {
        $response = array(
            'res_type' => $errorType,
            'res_msg'  => $errorMessage
        );
        echo json_encode($response);
    }


    public function check_angle($angle) {
        
        //驗證角度是否為數字 及範圍是否為0-9999
        if (is_numeric($angle) && $angle >= 0 && $angle <= 9999) {
            return TRUE;
        }else{
            return FALSE;
        }
    }

    public function check_torque($target_torque, $hi_torque, $lo_torque){
        if ($target_torque == 0) {
            $ans1 = 'FALSE1';
            return $ans1;
        }

        if ($hi_torque < $lo_torque) {
            $ans1 = 'FALSE2';
            return $ans1;
        }

        return TRUE;
        
    }

    function validateTorque($target_torque, $hi_torque, $lo_torque) {
        // 檢查 $target_torque 是否為 0
        if ($target_torque == 0) {
            return "錯誤：目標扭力不得為 0。";
        }
    
        // 檢查 $hi_torque 是否大於 $lo_torque
        if ($hi_torque <= $lo_torque) {
            return "錯誤：高扭力 ($hi_torque) 必須大於低扭力 ($lo_torque)。";
        }
    
        // 如果所有檢查都通過
        return "驗證通過：扭力值有效。";
    }
    
    public function FTP_download($controller_ip, $username, $password)
    {
        /*$remote_file = '/var/www/html/database/tcscon.db';             // 原始远程文件路径
        $copied_file = '/var/www/html/database/tcscon_copy_brian_test.db'; // 复制后的远程文件路径
        $local_file = '../brian_test_20240826_3.db';                             // 本地文件路径
        $local_file_copy = $local_file . '666';  
    
        // 打开本地文件以进行写入
        $handle = fopen($local_file, 'w');
        if (!$handle) {
            echo "無法打開檔案。\n";
            return;
        }
    
        #連線到FTP
        $conn_id = ftp_connect($controller_ip);
        if (!$conn_id) {
            echo "連線到FTP失敗。\n";
            fclose($handle);
            return;
        }
    
        #登入FTP
        $login_result = ftp_login($conn_id, $username, $password);
        if (!$login_result) {
            echo "登入FTP失敗。\n";
            ftp_close($conn_id);
            fclose($handle);
            return;
        }
    
        #下載檔案
        $temp_file = tempnam(sys_get_temp_dir(), 'ftp_');
        if (ftp_get($conn_id, $temp_file, $remote_file, FTP_BINARY)) {
            echo "下載檔案成功。\n";
        } else {
            echo "下載檔案失敗。\n";
            ftp_close($conn_id);
            fclose($handle);
            return;
        }
    
        // 将文件上传到新的目标路径
        if (ftp_put($conn_id, $copied_file, $temp_file, FTP_BINARY)) {
            echo "檔案COPY成功，原始文件".$remote_file. "COPY為".$copied_file."\n";
        } else {
            echo "檔案COPY失敗。\n";
            ftp_close($conn_id);
            fclose($handle);
            return;
        }
    
        #把下載的檔案 移至本機
        if (ftp_fget($conn_id, $handle, $copied_file, FTP_BINARY, 0)) {
            echo "下载成功，保存到 $local_file\n";
            // 可选：复制下载的文件
            if (copy($local_file, $local_file . '666')) {
                echo "檔案COPY，保存為 $local_file.666\n";
            } else {
                echo "檔案COPY失敗。\n";
            }
        } else {
            echo "下载".$copied_file."到".$local_file."失敗。\n";
        }
        
        #關閉FTP
        ftp_close($conn_id);
        fclose($handle);
    
        // 删除临时文件
        //unlink($temp_file);

        #刪除檔案
        if (file_exists($local_file_copy)) {
            if (unlink($local_file_copy)) {
                echo "删除 $local_file_copy 成功。\n";
            } else {
                echo "删除 $local_file_copy 失败。\n";
            }
        }*/
    }


    public function FTP_upload($controller_ip, $username, $password){
        /*$local_file = '../brian_test_20240821.db';       // 本地文件路径
        $remote_file = '/var/www/html/database/tcscon_copy_brian_test.db'; // 远程文件路径

        #確保檔案是否存在
        if (!file_exists($local_file)) {
            echo "檔案不存在。\n";
            return;
        }

        #連線FTP
        $conn_id = ftp_connect($controller_ip);
        if (!$conn_id) {
            echo "連線FTP失敗。\n";
            return;
        }

        #登入FTP
        $login_result = ftp_login($conn_id, $username, $password);
        if (!$login_result) {
            echo "登入FTP失敗。\n";
            ftp_close($conn_id);
            return;
        }

        // 检查檔案是否存在
        if (ftp_size($conn_id, $remote_file) != -1) {
            $backup_file = $remote_file . '.bak';
            if (ftp_rename($conn_id, $remote_file, $backup_file)) {
                echo "備份現有檔案為".$backup_file."\n";
            } else {
                echo "備份現有檔案失敗。\n";
                ftp_close($conn_id);
                return;
            }
        }

        #上傳檔案
        if (ftp_put($conn_id, $remote_file, $local_file, FTP_BINARY)) {
            echo "檔案上傳成功，保存到 $remote_file\n";
        } else {
            echo "檔案上傳失敗。\n";
        }


        //unlink($backup_file);
        #關閉FTP
        ftp_close($conn_id);*/
    }
    
    
    
}
