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

    public function get_info($id) {

        $csv_folder = IDAS_PATH_RAMDISK_FTP . '/';
        $files = glob($csv_folder . $id . "_*.csv");

        if (!$files) {
            return [
                "x"      => [],
                "torque" => [],
                "rpm"    => [],
                "angle"  => [],
                "step"   => [],
            ];
        }

        usort($files, fn($a, $b) => filectime($b) - filectime($a));
        $file = $files[0];

        $fp = fopen($file, "r");
        if (!$fp) return [];

        // 讀 header
        $header = fgetcsv($fp);

        $x      = [];
        $torque = [];
        $angle  = [];
        $rpm    = [];
        $step   = [];

        $i = 0;
        while (($r = fgetcsv($fp)) !== false) {

            $t = isset($r[0]) ? floatval($r[0]) : $i;
            $tor = isset($r[1]) ? floatval($r[1]) : 0;
            $ang = isset($r[2]) ? floatval($r[2]) : 0;
            $rp  = isset($r[3]) ? floatval($r[3]) : 0;
            $st  = isset($r[4]) ? $r[4] : "1";

            $x[]      = $t;
            $torque[] = $tor;
            $angle[]  = $ang;
            $rpm[]    = $rp;
            $step[]   = $st;

            $i++;
        }
        fclose($fp);

        return [
            "x"      => $x,
            "torque" => $torque,
            "rpm"    => $rpm,
            "angle"  => $angle,
            "step"   => $step,
        ];
    }




    public function get_step_only($id) {
        $csv_folder = IDAS_PATH_RAMDISK_FTP . '/';
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
        $folder = IDAS_PATH_RAMDISK_FTP . '/';

        // 取得所有以指定 ID 開頭且結尾為 .csv 的檔案
        $file_list = glob($folder . $id . "_*.csv");

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
