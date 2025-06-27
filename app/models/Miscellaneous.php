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
                1 => 'N.m',
                2 => 'lbf.in',
                3 => 'kgf.m',
                4 => 'cN.m',
                
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


        if($mode =="decimals"){
            $array = array(
                0 => 4, // KGF-M
                1 => 3, // N.m
                2 => 2, // KGF-cm
                3 => 2, // Lbf
                4 => 1  // cN.m
            );

        }


        $decimals = [
    0 => 4, // KGF-M
    1 => 3, // N.m
    2 => 2, // KGF-cm
    3 => 2, // Lbf
    4 => 1  // cN.m
];

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

    public function convert_all_torque_units($value, $inputType, $useExcelMode = true) {
        $unit_names = [
            0 => "kgf.m",
            1 => "N.m",
            2 => "kgf.cm",
            3 => "Lbf.in",
            4 => "cN.m"
        ];

        $decimals = [
            0 => 4,
            1 => 3,
            2 => 2,
            3 => 2,
            4 => 3  // ✅ 建議保留更多位數，方便轉換
        ];

        if (!is_numeric($value) || !isset($unit_names[$inputType])) {
            return "Invalid input.";
        }

        $value = floatval($value);

        // ✅ Step 1: 將輸入單位轉為 N.m 作為中介
        switch ($inputType) {
            case 0: $Nm = $value * 9.80665; break;             // kgf.m → N.m
            case 1: $Nm = $value; break;                       // N.m
            case 2: $Nm = $value * 0.0980665; break;           // kgf.cm → N.m
            case 3: $Nm = $value * 0.112984829333; break;      // Lbf.in → N.m
            case 4: $Nm = $value * 0.01; break;                // cN.m → N.m
            default: return "Invalid unit index.";
        }

        $result = [];

        // ✅ Step 2: N.m 轉為目標單位
        foreach ($unit_names as $targetType => $unitName) {
            if ($useExcelMode) {
                switch ($targetType) {
                    case 0: $converted = $Nm * 0.10197; break;        // N.m → kgf.m (Excel)
                    case 1: $converted = $Nm; break;
                    case 2: $converted = $Nm * 10.2; break;
                    case 3: $converted = $Nm * 8.85411; break;
                    case 4: $converted = $Nm * 100; break;            // N.m → cN.m
                    default: continue 2;
                }
            } else {
                switch ($targetType) {
                    case 0: $converted = $Nm / 9.80665; break;
                    case 1: $converted = $Nm; break;
                    case 2: $converted = $Nm / 0.0980665; break;
                    case 3: $converted = $Nm / 0.112984829333; break;
                    case 4: $converted = $Nm * 100; break;
                    default: continue 2;
                }
            }

            // ✅ 四捨五入 & 保留固定位數（.000）
            $rounded = round($converted, $decimals[$targetType]);
            $result[$unitName] = number_format($rounded, $decimals[$targetType], '.', '');
        }

        return $result;
    }



    public function convert_single_torque_unit($value, $from_unit, $to_unit, $useExcelMode = true) {
        if (!is_numeric($value)) return 0;

        $value = floatval($value);

        // 小數點保留位數定義（根據單位）
        $decimals = [
            0 => 4, // kgf.m
            1 => 3, // N.m
            2 => 2, // kgf.cm
            3 => 2, // Lbf.in
            4 => 1  // cN.m
        ];

        // Step 1：先轉成 N.m（中介單位）
        switch ($from_unit) {
            case 0: $Nm = $value * 9.80665; break;
            case 1: $Nm = $value; break;
            case 2: $Nm = $value * 0.0980665; break;
            case 3: $Nm = $value * 0.112984829333; break;
            case 4: $Nm = $value * 0.01; break;
            default: return 0;
        }

        // Step 2：再轉成目標單位
        if ($useExcelMode) {
            switch ($to_unit) {
                case 0: $converted = $Nm * 0.10197; break;
                case 1: $converted = $Nm; break;
                case 2: $converted = $Nm * 10.2; break;
                case 3: $converted = $Nm * 8.85411; break;
                case 4: $converted = $Nm * 100; break;
                default: return 0;
            }
        } else {
            switch ($to_unit) {
                case 0: $converted = $Nm / 9.80665; break;
                case 1: $converted = $Nm; break;
                case 2: $converted = $Nm / 0.0980665; break;
                case 3: $converted = $Nm / 0.112984829333; break;
                case 4: $converted = $Nm * 100; break;
                default: return 0;
            }
        }

        // 回傳固定格式的小數點字串
        $dec = $decimals[$to_unit] ?? 3;
        return number_format(round($converted, $dec), $dec, '.', '');
    }



    public function convert_tool_torque_info($tools, $torque_unit, $unit_name, $decimals_arr, $device_torque_unit){

        $precision = $decimals_arr[$torque_unit] ?? 3;

        foreach (['min_torque', 'max_torque'] as $key) {
            if (!empty($tools[$key]) && is_numeric($tools[$key])) {

                // ➤ 先從 N.mm → N.m
                $value_nm = $tools[$key] / 1000;

                $result = $this->convert_and_format_torque_full(
                    $value_nm,
                    $torque_unit,
                    $unit_name
                );

                $converted_value = round($result['raw_converted'], $precision);

                $tools[$key] = $converted_value;

                $formatted_value = number_format($converted_value, $precision);

                if ($key === 'max_torque') {
                    $tools['tools_high_torque_diff'] = $formatted_value;
                    $tools['tool_high_torque'] = number_format($converted_value * 1.10, $precision);
                    $tools['max_torque'] = $formatted_value;
                }

                if ($key === 'min_torque') {
                    $tools['min_torque'] = $formatted_value;
                }
            }
        }

        // ➤ tool_low_torque
        $converted_zero = $this->convert_single_torque_unit(
            0,
            $device_torque_unit,
            $torque_unit
        );

        $converted_zero = floatval($converted_zero);

        $tools['tool_low_torque'] = number_format(
            $converted_zero,
            $precision
        );

        return $tools;
    }



    /*public function convert_single_torque_unit($value, $from_unit, $to_unit) {
        $result = $this->convert_all_torque_units($value, $from_unit);
        $unit_map = [
            0 => 'kgf.m',
            1 => 'N.m',
            2 => 'kgf.cm',
            3 => 'Lbf.in',
            4 => 'cN.m'
        ];
        $target_unit = $unit_map[$to_unit] ?? null;
        return $target_unit && isset($result[$target_unit]) ? (float)$result[$target_unit] : 0;
    }*/


   

    public function get_unit_name_by_index($index) {
        $unit_map = [
            0 => "kgf.m",
            1 => "N.m",
            2 => "kgf.cm",
            3 => "Lbf.in",
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



    public function convert_and_format_torque_full($raw_value, $to_unit_id, $unit_name){

            // 原始為 N.mm → 換算成 N.m
            $base_value = $raw_value / 1000;

            // 執行轉換
            $converted = $this->convert_single_torque_unit($base_value, 1, $to_unit_id);

            // 抓出實際數值
            $converted_value = is_array($converted)
                ? ($converted[$unit_name] ?? 0)
                : (is_numeric($converted) ? $converted : 0);

            // 小數位設定
            $decimals = [
                0 => 4, // KGF-M
                1 => 3, // N.m
                2 => 2, // KGF-cm
                3 => 2, // Lbf
                4 => 1  // cN.m
            ];
            $precision = isset($decimals[$to_unit_id]) ? $decimals[$to_unit_id] : 3;

            return [
                'converted_value' => number_format($converted_value, $precision),
                'high_torque'     => number_format($converted_value * 1.10, $precision),
                'raw_converted'   => $converted_value
            ];
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
        exit;
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
