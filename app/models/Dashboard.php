<?php

class Dashboard{
    private $db;//condb control box
    private $db_dev;//devdb tool
    private $db_data;//devdb tool
    private $dbh;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
        $this->db = new Database;
        $this->db = $this->db->getDb();

        $this->db_dev = new Database;
        $this->db_dev = $this->db_dev->getDb_dev();

        $this->db_data = new Database;
        $this->db_data = $this->db_data->getDb_data();

        $this->dbh = new Database;

    }

    //驗證job id是否重複
    public function get_last_data()
    {
        $sql = "SELECT * FROM data ORDER BY system_sn DESC LIMIT 1";
        $statement = $this->db_data->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }

    //get tool max,min rpm
    public function get_tool_info()
    {
        $sql = "SELECT *,
                   CASE tool_minrpm 
                       WHEN '20' 
                           THEN '60' 
                       ELSE '60' 
                   END tool_minrpm 
                FROM tool_info";
        $statement = $this->db_dev->prepare($sql);
        $results = $statement->execute();
        $rows = $statement->fetch(PDO::FETCH_ASSOC);

        return $rows;
    }

    //return datalog csv for graph
    public function get_device_datalog_frequency()
    {
        $sql = "SELECT device_datalog_frequency  FROM device ";
        $statement = $this->db->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row['device_datalog_frequency'];
       
    }

    //get tool max,min rpm
    public function get_tool_info_unit_convert()
    {
        $sql = "SELECT * FROM tool_info";
        $statement = $this->db_dev->prepare($sql);
        $results = $statement->execute();
        $rows = $statement->fetch(PDO::FETCH_ASSOC);

        $sql2 = "SELECT device_torque_unit FROM device";
        $statement2 = $this->db->prepare($sql2);
        $results2 = $statement2->execute();
        $rows2 = $statement2->fetch(PDO::FETCH_ASSOC);

        // device_torque_unit
        // 0: 公斤米
        // 1: 牛頓米 起子預設是牛頓米
        // 2: 公斤公分
        // 3: 英鎊英寸

        //使用時機 1. output 輸出給前端時，要依據目前系統的扭力單位設定，顯示對應的扭力數值
        //使用時機 2. input 寫入資料庫時，要依據目前系統的扭力單位設定，將數值轉換為牛頓米寫到資料庫中


        return $rows;
    }

    public function get_info($no, $chat_mode) {
        $resultarr = array();
        
        if (!empty($no)) {
            #檔案類型
            $file_arr = array('_0p5', '_1p0', '_2p0');
            $csv_array = array();
            $resultarr = array();
            
            foreach ($file_arr as $v_f) {
                // public/data/DATALOG_20241126074447_DEVICE_0000009437_0p5.csv
                //$infile = "../public/data/DATALOG_000000".$no.$v_f.".csv";
                //$infile = "../public/data/DATALOG_20241126074447_DEVICE_000000" . $no . $v_f . ".csv";
                $infile = "../public/data/DATALOG_20241220150526_DEVICE_".$no."_0p5.csv";
                // echo $infile; die();
                if (file_exists($infile)) {
                    $csvdata_tmp = file_get_contents($infile);
                    
                    if (!empty($csvdata_tmp)) {
                        $csvdata = $csvdata_tmp;
                        $lines = explode("\n", $csvdata);
                        $csv_array = array_map('str_getcsv', $lines);
                        break;
                    }
                }
            }
            
            if (empty($csv_array)) {
                $resultarr = null;
            } else {
                $position = (int)$chat_mode;
                
                // 如果是 $chat_mode == "5"，則先獲取 $chat_mode == "1" 和 $chat_mode == "3" 的結果
                if ($chat_mode == "5") {
                    // 取得 chat_mode == "1" 的結果並儲存至 $resultarr['torque']
                    $resultarr['torque'] = $this->get_info($no, "1");
                    // 取得 chat_mode == "3" 的結果並儲存至 $resultarr['rpm']
                    $resultarr['rpm'] = $this->get_info($no, "3");
                }
    
                // 處理當前的 $chat_mode 邏輯
                foreach ($csv_array as $subarray) {
                    if (isset($subarray[$position])) {
                        // 如果是 chat_mode == "5" 或 "6"
                        if ($chat_mode == "5" || $chat_mode == "6") {
                            if ($chat_mode == "6" && $position == 6) {
                                $resultarr['torque'][] = $subarray[1];
                            } else {
                                // 存儲數據到 torque
                                $resultarr['torque'][] = $subarray[$position];
                            }
                        } else {
                            $resultarr[] = $subarray[$position];
                        }
                    }
                }
            }
        }
    
        return $resultarr;
    }

    public function get_csv_first_column($no) {
        $first_column = array();
        
        // 檔案類型
        $file_arr = array('_0p5', '_1p0', '_2p0');
        
        foreach ($file_arr as $v_f) {
            //$infile = "../public/data/DATALOG_20241126074447_DEVICE_000000" . $no . $v_f . ".csv";
            $infile = "../public/data/DATALOG_20241220150526_DEVICE_".$no."_0p5.csv";
            //echo $infile;die();
            if (file_exists($infile)) {
                $csvdata_tmp = file_get_contents($infile);
                
                if (!empty($csvdata_tmp)) {
                    $csvdata = $csvdata_tmp;
                    $lines = explode("\n", $csvdata);
                    $csv_array = array_map('str_getcsv', $lines);
                    
                    // 取得每行的第一個欄位 (即 A 欄位)
                    foreach ($csv_array as $subarray) {
                        // 確保該行有數據
                        if (isset($subarray[0])) {
                            $first_column[] = $subarray[0];  // 將 A 欄位的數據加入
                        }
                    }
                    break;  // 若找到檔案後，就退出循環
                }
            }
        }
        return $first_column;
    }

    public function get_Data(){

        $sql = "SELECT * FROM ntcs_data ORDER BY data_time DESC LIMIT 1";
        $statement = $this->db_data->prepare($sql);
        if ($statement) { 
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }
    
        return [];
    }

    public function get_data_up(){
        
        $sql = "SELECT * FROM ntcs_data ORDER BY data_time DESC LIMIT 1";
        $statement = $this->db_data->prepare($sql);
        if ($statement) { 
            $statement->execute();
            $row = $statement->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        }
    
        return [];

    }


    
    
    
}
