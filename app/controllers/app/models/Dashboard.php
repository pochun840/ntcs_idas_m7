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


    public function get_info($chat_mode, $id){
        
        $chat_mode = (int)$chat_mode;

        // === 1. 找 CSV ===
        $csv_folder = "/mnt/ramdisk/ftp/";
        $csv_files  = glob($csv_folder . $id . "_*.csv");

        if (empty($csv_files)) return [];

        // 最新檔案
        usort($csv_files, fn($a, $b) => filectime($b) - filectime($a));
        $latest_file = $csv_files[0];

        // 讀檔
        $lines = file($latest_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!$lines) return [];

        // === 2. 轉成陣列 ===
        $rows = array_map('str_getcsv', $lines);
        if (empty($rows)) return [];

        // === 3. 丟掉 header ===
        array_shift($rows);

        // === 4. 統一每列至少有 8 欄 ===
        foreach ($rows as &$r) {
            for ($i = 0; $i < 8; $i++) {
                if (!isset($r[$i]) || trim($r[$i]) === "") {
                    $r[$i] = "0";   // string "0" → 避免 floatval(null) 變 0 但 isset 判斷失敗
                }
            }
        }
        unset($r);

        /*
            CSV 欄位標準化：

            0 => time
            1 => torque
            2 => angle
            3 => rpm
            4 => step_local
            5 => step_global
            6 => torque_calc?
            7 => angle_calc?
        */

        // === 5. Mode 5：Torque + RPM（雙軸）=========================
        if ($chat_mode === 5) {
            $torque = array_map('floatval', array_column($rows, 1));
            $rpm    = array_map('floatval', array_column($rows, 3));

            return [
                'torque' => $torque,
                'rpm'    => $rpm
            ];
        }

        // === 6. Mode 1 / 4 / 6：Torque =============================
        if (in_array($chat_mode, [1, 4, 6], true)) {
            // Mode 6 在 ChartData 會用 Angle 作 X，所以這裡 torque 沒問題
            return array_map('floatval', array_column($rows, 1));
        }

        // === 7. Mode 2：Angle（固定 index 2）======================
        if ($chat_mode === 2) {
            return array_map('floatval', array_column($rows, 2));
        }

        // === 8. Mode 3：RPM（固定 index 3）========================
        if ($chat_mode === 3) {
            return array_map('floatval', array_column($rows, 3));
        }

        // === 9. Mode 7（Step 分析）→ 抓第 4 欄 ====================
        if ($chat_mode === 7) {
            return array_map('floatval', array_column($rows, 4)); // step_local
        }

        // === 10. 其他模式 fallback（不建議，但安全）===============
        return array_map('floatval', array_column($rows, $chat_mode));
    }




    public function get_step_only($id) {
        $csv_folder = "/mnt/ramdisk/ftp/";
        $csv_files = glob($csv_folder . $id . "_*.csv");
        if (empty($csv_files)) return [];

        // 取最新檔案
        usort($csv_files, fn($a, $b) => filectime($b) - filectime($a));
        $latest_file = $csv_files[0];

        $csv_content = file_get_contents($latest_file);
        if (empty($csv_content)) return [];

        $lines = explode("\n", $csv_content);
        $csv_array = array_filter(array_map('str_getcsv', $lines)); // 過濾空行

        $step = [];
        foreach ($csv_array as $index => $row) {
            if ($index === 0) continue; // 跳過表頭
            if (isset($row[4])) {
                $step[] = $row[4]; // Step = E欄
            }
        }

        return $step;
    }


    public function get_csv_first_column($id) {
        $first_column = array();
        $folder = "/mnt/ramdisk/ftp/";

        // 取得所有以指定 ID 開頭且結尾為 .csv 的檔案
        $file_list = glob($folder . $id . "__*.csv");

        if (!empty($file_list)) {
            // 根據建立時間從新到舊排序
            usort($file_list, function ($a, $b) {
                return filectime($b) - filectime($a);
            });

            $latest_file = $file_list[0]; // 最新的一個符合條件的檔案

            if (file_exists($latest_file)) {
                $csvdata_tmp = file_get_contents($latest_file);

                if (!empty($csvdata_tmp)) {
                    $lines = explode("\n", $csvdata_tmp);
                    $csv_array = array_map('str_getcsv', $lines);

                    foreach ($csv_array as $subarray) {
                        if (isset($subarray[0])) {
                            $first_column[] = $subarray[0]; // 取出第一欄
                        }
                    }
                }
            }
        }

        return $first_column;
    }


    public function get_Data(){

        $sql = "SELECT * FROM ntcs_data ORDER BY  rowid DESC LIMIT 1 ";
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
