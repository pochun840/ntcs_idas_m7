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

    public function get_info($chat_mode) {
        $resultarr = [];

        // 找出最新的 CSV 檔案
        $csv_folder = "/mnt/ramdisk/ftp/";
        $csv_files = glob($csv_folder . "*.csv");

        if (empty($csv_files)) {
            return null;
        }

        // 按建立時間排序，最新的排最前面
        usort($csv_files, function ($a, $b) {
            return filectime($b) - filectime($a);
        });

        $latest_file = $csv_files[0];

        // 讀取內容
        $csv_content = file_get_contents($latest_file);
        if (empty($csv_content)) {
            return null;
        }

        $lines = explode("\n", $csv_content);
        $csv_array = array_map('str_getcsv', $lines);
        $csv_array = array_filter($csv_array); // 避免最後多一行空白

        // 處理資料欄位
        $position = (int)$chat_mode;

        // chat_mode == 5 需要 torque 與 rpm
        if ($chat_mode == 5) {
            return [
                'torque' => $this->get_info(1), // 位置 1: Torque
                'rpm'    => $this->get_info(3)  // 位置 3: RPM
            ];
        }

        // 一般模式下讀取特定欄位資料
        foreach ($csv_array as $row) {
            if (isset($row[$position])) {
                $resultarr[] = $row[$position];
            }
        }

        return $resultarr;
    }



    public function get_csv_first_column($no) {
        
        $first_column = array();

        $folder = "/mnt/ramdisk/ftp/";

        // 取得所有 .csv 結尾的檔案
        $file_list = glob($folder . "*.csv");

        if (!empty($file_list)) {
            // 根據建立時間從新到舊排序
            usort($file_list, function ($a, $b) {
                return filectime($b) - filectime($a);
            });

            $latest_file = $file_list[0]; // 最新的 CSV 檔案

            if (file_exists($latest_file)) {
                $csvdata_tmp = file_get_contents($latest_file);

                if (!empty($csvdata_tmp)) {
                    $lines = explode("\n", $csvdata_tmp);
                    $csv_array = array_map('str_getcsv', $lines);

                    foreach ($csv_array as $subarray) {
                        if (isset($subarray[0])) {
                            $first_column[] = $subarray[0]; // 取第一欄
                        }
                    }
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


    
    
    
}
