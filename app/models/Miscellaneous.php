<?php

class Miscellaneous{
    private $db;//condb control box
    private $db_data;//devdb tool
    private $dbh;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db_iDas_tools = new Database;
        $this->db_iDas_tools = $this->db_iDas_tools->getDb_das_tools();

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
                4 => 'cN.m',
                
            );

            //新的
            $ntcs_array = array(
                0 => 'kgf-cm',
                1 => 'N.m',
                2 => 'lbf.in',
                3 => 'kgf.m',
                4 => 'cN.m',
                
            );

            /*
            0 : kgf-cm
            1 : N.m
            2 : lbf.in
            3 : kgf.m
            */
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
                5 => 'Torque/Speed'
            );
        }

        if($mode == "chart_menu"){
            $array = array(
                1 => array('name'=>'Torque Time', 'id'=>'torque_time'),
                2 => array('name'=>'Angle Time',  'id'=>'angle_time'),
                3 => array('name'=>'RPM Time',    'id'=>'rpm_time'),
                4 => array('name'=>'Torque Angle','id'=>'torque_angle'),
                5 => array('name'=>'Torque Speed','id'=>'torque_speed'),
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

        if($mode == "status_ntcs"){
            $array = array(
                1 => 'OK', 
                2 => 'NG',
                3 => 'OK-SEQ',
                4 => 'OK-JOB'
            );

        }

        if($mode =="lang"){
            $array = array(
                1 => 'English',
                2 => '繁體中文',
                3 => '簡體中文',
            );    
        }

        if($mode =="sample_rate"){
            $array = array(
                0 => '0.5',
                1 => '1.0',
                2 => '2.0',
            );    
        }

        if($mode =="barcode_mode"){
            $array = array(
                1 => 'BS',
                2 => 'BS (free)',
                3 => 'Switch Job / Seq',
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


    #扭力單位轉換;
    public function unit_transduction($torValue, $inputType,$TransType){
        
        #輸入扭力單位
        $inputType = (int)$inputType;
        
        #輸出扭力單位
        $TransType = (int)$TransType;

        $new_TorqueUnit = [
            "kgf.cm" => 0,
            "N.m"    => 1,
            "lbf.in" => 2,
            "kgf.m"  => 3,
            "cN.m"   => 4
        ];

        $convertedValues = array();
        if (!is_array($torValue)) {
            $torValue = [$torValue];
        }

        foreach($torValue as $torValue){
            $torValue = floatval($torValue);

            #當輸入的單位是N.m
            if($inputType == $new_TorqueUnit["N.m"]){

                if($TransType == $new_TorqueUnit["kgf.m"]){
                    $convertedValues[] = round($torValue * 0.102, 4); // N.m 轉換成 kgf.m
                }elseif($TransType == $new_TorqueUnit["kgf.cm"]){
                    $convertedValues[] = round($torValue * 10.2, 2); // N.m 轉換成 Kgf·cm
                }elseif($TransType == $new_TorqueUnit["lbf.in"]){
                    $convertedValues[] = round($torValue * 10.2 * 0.86805, 2); // N.m 轉換成 lbf.in
                }elseif($TransType == $new_TorqueUnit["N.m"]){
                    $convertedValues[] = round($torValue, 3); // N.m 轉換成 N.m（保持不變）
                }elseif($TransType == $new_TorqueUnit["cN.m"]){
                    $convertedValues[] = round(round($torValue * 10.2, 2) * 9.80392156, 1); //N.m 轉換成 cN.m
                }
            } 

            #當輸入的單位是kgf.m
            elseif($inputType == $new_TorqueUnit["kgf.m"]){
                
                if($TransType == $new_TorqueUnit["kgf.m"]){
                    $convertedValues[] = round($torValue, 4); // kgf.m 轉換成 kgf.m（保持不變）
                }elseif($TransType == $new_TorqueUnit["kgf.cm"]){
                    $convertedValues[] = round($torValue * 100, 2); // kgf.m 轉換成 Kgf·cm
                }elseif($TransType == $new_TorqueUnit["lbf.in"]){
                    $convertedValues[] = round($torValue * 100 * 0.86805, 2); // kgf.m 轉換成 lbf.in
                }else if($TransType == $new_TorqueUnit["N.m"]){
                    $convertedValues[] = round($torValue * 9.80392156, 3); // kgf.m 轉換成 N·m
                }elseif($TransType == $new_TorqueUnit["cN.m"]){
                    $convertedValues[] = round(round($torValue * 100, 2) * 9.80392156, 1);  // kgf.m 轉換成 cN·m
                }   
            }

            #當輸入的單位是Kgf·cm
            elseif ($inputType == $new_TorqueUnit["kgf.cm"]){

                if($TransType == $new_TorqueUnit["kgf.m"]){
                    $convertedValues[] = round($torValue * 0.01, 4); // Kgf·cm 轉換成 kgf.m
                }elseif($TransType == $new_TorqueUnit["kgf.cm"]){
                    $convertedValues[] = round($torValue, 2); // Kgf·cm 轉換成 Kgf·cm（保持不變）
                }elseif($TransType == $new_TorqueUnit["lbf.in"]){
                    $convertedValues[] = round($torValue * 0.86805, 2); // Kgf·cm 轉換成 lbf.in
                }elseif($TransType == $new_TorqueUnit["N.m"]){
                    $convertedValues[] = round($torValue * 0.0980392156, 3); // Kgf·cm 轉換成 N·m
                }elseif($TransType == $new_TorqueUnit["cN.m"]){
                    $convertedValues[]  =  round($torValue * 9.80392156, 1);  // kgf.cm 轉換成 cN·m
                }

            }

            #當輸入的單位是lbf.in
            elseif ($inputType == $new_TorqueUnit["lbf.in"]){

                if($TransType == $new_TorqueUnit["kgf.m"]){
                    $convertedValues[] = round($torValue * 1.152 * 0.01, 4); // lbf.in 轉換成 kgf.m
                }elseif($TransType == $new_TorqueUnit["kgf.cm"]){
                    $convertedValues[] = round($torValue * 1.152, 2); // lbf.in轉換成 Kgf·cm
                }elseif($TransType == $new_TorqueUnit["lbf.in"]){
                    $convertedValues[] = round($torValue, 2); // lbf.in 轉換成 lbf.in（保持不變）
                }elseif($TransType == $new_TorqueUnit["N.m"]){
                    $convertedValues[] = round($torValue * 0.11294117637119998, 3); // lbf.in 轉換成 N·m
                }elseif($TransType == $new_TorqueUnit["cN.m"]){
                    $convertedValues[] = round(round($torValue * 1.152, 2) * 9.80392156, 1); // lbf.in 轉換成 cN·m
                }
            }

            #當輸入的單位是cN·m
            elseif($inputType == $new_TorqueUnit["cN.m"]){
                
                if($TransType == $new_TorqueUnit["kgf.m"]){
                    $convertedValues[] =  round(round($torValue * 0.102, 2) * 0.01, 4); // cN·m 轉換成 kgf.m
                }elseif($TransType == $new_TorqueUnit["kgf.cm"]){
                    $convertedValues[] =  round( $torValue * 0.102,2); // cN·m 轉換成 kgf.m
                }elseif($TransType == $new_TorqueUnit["lbf.in"]){
                    $convertedValues[] =  round(round($torValue * 0.102, 2) * 0.86805, 2); // cN·m 轉換成 lbf.in
                }else if($TransType == $new_TorqueUnit["N.m"]){
                    $convertedValues[] =  round(round($torValue * 0.102, 2) * 0.0980392156, 3); // cN·m 轉換成 N·m
                }elseif($TransType == $new_TorqueUnit["cN.m"]){
                    $convertedValues[] = round($torValue, 1);  // cN·m 轉換成 cN·m（保持不變）
                }

            }


        }

        return $convertedValues;
    
    }


        public function convert_all_torque_units($value, $inputType) {
        $unit_names = [
            0 => "kgf.m",
            1 => "N.m",
            2 => "kgf.cm",
            3 => "lbf.in",
            4 => "cN.m"
        ];

        $decimals = [
            0 => 4, // kgf.m
            1 => 3, // N.m
            2 => 2, // kgf.cm
            3 => 2, // lbf.in
            4 => 1  // cN.m
        ];

        if (!is_numeric($value) || !isset($unit_names[$inputType])) {
            return "Invalid input.";
        }

        $value = floatval($value);

        // Step 1: 先轉換為 N.m
        switch ($inputType) {
            case 0: $Nm = $value * 9.80392156; break; // kgf.m → N.m
            case 1: $Nm = $value; break;              // N.m
            case 2: $Nm = $value * 0.0980392156; break; // kgf.cm → N.m
            case 3: $Nm = $value * 0.1129411763712; break; // lbf.in → N.m
            case 4: $Nm = $value * 0.001; break; // cN.m → N.m
            default: return "Invalid unit index.";
        }

        $result = [];

        // Step 2: 從 N.m 轉換為所有單位
        foreach ($unit_names as $targetType => $unitName) {
            switch ($targetType) {
                case 0: $converted = $Nm * 0.102; break; // N.m → kgf.m
                case 1: $converted = $Nm; break;
                case 2: $converted = $Nm * 10.2; break;
                case 3: $converted = $Nm * 10.2 * 0.86805; break;
                case 4: $converted = $Nm * 100; break; // N.m → cN.m
            }

            // 四捨五入，保留固定小數位（不去尾）
            $rounded = round($converted, $decimals[$targetType]);
            $result[$unitName] = number_format($rounded, $decimals[$targetType], '.', '');
        }

        return $result;
    }


    public function get_unit_name_by_index($index) {
        $unit_map = [
            0 => "kgf.m",
            1 => "N.m",
            2 => "kgf.cm",
            3 => "lbf.in",
            4 => "cN.m"
        ];
        return isset($unit_map[$index]) ? $unit_map[$index] : null;
    }

    public function batch_convert_grouped_by_unit_chart(array $values, int $inputType) {
        $unit_keys = ["kgf.m", "N.m", "kgf.cm", "lbf.in", "cN.m"];
        $result = array_fill_keys($unit_keys, []); // 預設空陣列

        foreach ($values as $val) {
            if (!is_numeric($val)) continue;

            $converted = $this->convert_all_torque_units($val, $inputType);
            foreach ($converted as $unit => $convertedValue) {
                $result[$unit][] = $convertedValue;
            }
        }

        return $result;
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


    //取得最大最小轉速 及 最大最小扭力   
    public function getToolSpecifications() {
        $sql = "SELECT max_rpm, min_rpm, max_torque, min_torque FROM " . TABLE_NTCS_TOOLS;
        $statement = $this->db_iDas_tools->prepare($sql);
        $statement->execute();

        $result = $statement->fetch(PDO::FETCH_ASSOC);
    
        if ($result) {
            return $result; 
        } else {
            return null; 
        }
    }
}
