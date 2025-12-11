<?php

class Dashboards extends Controller
{
    private $DashboardModel;
    private $AdminModel;
    private $MiscellaneousModel;
    private $DataModel;
    private $SettingModel;
    Private $deviceId;
    private $device_torque_unit;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {


        $this->DashboardModel = $this->model('Dashboard');
        $this->AdminModel = $this->model('Admin');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();

        $this->device_torque_unit = $this->get_torque_unit_from_controller();
        




    }

    // 取得所有Jobs
    public function index(){

        $this->ntcs_data_db_sysnc();
        
        $isMobile = $this->isMobileCheck();
        $agent_type = $this->AdminModel->Get_Das_Config('agent_type');

        $data = [
            'isMobile' => $isMobile,
            'agent_type' => $agent_type,
        ];

        if($isMobile){
            $this->view('dashboards/index_m', $data);
        }else{
            $this->view('dashboards/index', $data);
        }


    }

   
    public function operation() {

      
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $isMobile           = $this->isMobileCheck();
        //鎖附紀錄裡面的資料
        $data_info          = $this->DashboardModel->get_Data() ?? [];
        $controller_info    = $this->SettingModel->GetControllerInfo();
        $status_arr         = $this->MiscellaneousModel->details('status');
        $unit_arr           = $this->MiscellaneousModel->details('modbus_torque_unit');
        $unit_arr_device    = $this->MiscellaneousModel->details('torque_unit');
        $decimals_arr       = $this->MiscellaneousModel->details('decimals');
        $res_device         = $this->SettingModel->GetControllerInfo();




        // ⭐ 自動同步 CSV
        $this->auto_fix_and_sync_csv();

        $this->cleanCsvKeepLast10Core();

        $new_unit = $this->device_torque_unit;

       
        $torque_unit = (int)($controller_info['torque_unit'] ?? 1);


        $chart_unit_name    = $unit_arr_device[$new_unit] ?? 'N.m';
        // 多語系顯示（如 kgf.cm → 公斤.公分）
        $chart_unit_label = $text[$chart_unit_name] ?? $chart_unit_name;


        // 顯示用的最終鎖付值與單位
        if (!empty($data_info['fasten_status'])) {
            $data_info['error_message'] = $error_message['ERR_' . $data_info['error_message']] ?? $data_info['error_message'];
            $fastenStatus = (string)$data_info['fasten_status'];
            $color = 'green';

            if (in_array($fastenStatus, ['5'])) {
                $controller_info = $this->SettingModel->GetControllerInfo();
                $color = ($controller_info['okseqcolor'] ?? 0) == 0 ? 'green' : 'yellow';
            } elseif (in_array($fastenStatus, ['6'])) {
                $controller_info = $this->SettingModel->GetControllerInfo();
                $color = ($controller_info['okjobcolor'] ?? 0) == 0 ? 'green' : 'yellow';
            } elseif (in_array($fastenStatus, ['7','8'])) {
                $color = 'red';
            }

            $data_info['fasten_status_text']       = $status_arr[$fastenStatus] ?? '';
            $data_info['result_status_color_text'] = $color;
            $data_info['torque_unit']              = (int)($data_info['torque_unit'] ?? 1);

            
            $data_info['final_fasten_torque'] = $data_info['final_fasten_torque'];
            $data_info['final_torque_unit']   = $chart_unit_name;
            $data_info['final_fasten_torque_temp'] = $data_info['final_fasten_torque'];


            
            /*if ($device_torque_unit != $data_info['torque_unit']) {
      
                $conv = $this->MiscellaneousModel->convert_all_torque_units(
                    $data_info['final_fasten_torque'], $data_info['torque_unit'], $device_torque_unit
                );
                $data_info['final_fasten_torque_temp'] = $conv[$unit_arr[$device_torque_unit]] ?? $data_info['final_fasten_torque'];
            } else {
                
               
                $data_info['final_fasten_torque_temp'] = $data_info['final_fasten_torque'];
            }

            $data_info['final_fasten_torque'] = $data_info['final_fasten_torque_temp'];
            $data_info['final_torque_unit']   = $chart_unit_name;*/
        }

        //$decimal_places = $decimals_arr[$torque_unit] ?? 3;
        /*if (!empty($data_info)) {
            $data_info['final_fasten_torque'] = number_format((float)$data_info['final_fasten_torque'], $decimal_places);
        }*/

        // 目前資料 id
        $id = null;
        $first_data = $this->get_current_data();
        // $get_operation_id = $this->get_operation_id();
        if (!empty($first_data)) $id = $first_data['id'];

        // ★ 允許 chart 到 7
        $chart_mode    = (isset($_GET['chart']) && $_GET['chart'] >= 1 && $_GET['chart'] <= 7) ? (int)$_GET['chart'] : 1;
        $chat_mode_arr = $chart_mode;

      

   
        // 選單文字
        $chart_menu_arr = $this->MiscellaneousModel->details('chart_menu');
        $chart_mode_arr = $this->MiscellaneousModel->details('chart_mode');

        // ★ label 也用 2 的文本（chart=7 等同 2）
        $label_mode  = ($chart_mode === 7 ? 2 : $chart_mode);
        $label_text  = $chart_mode_arr[$label_mode] ?? ($chart_mode_arr[2] ?? '');
        $echart_name = explode(" v.s ", $label_text);

        // steps（後面會裁長度）
        $step_only = $this->DashboardModel->get_step_only($id);


        // 組回傳
        $data = [
            'isMobile'       => $isMobile,
            //'chart_info'     => $temp_chart,
            'echart_name'    => $echart_name,
            'chart_mode'     => $chart_mode,
            'chart_menu_arr' => $chart_menu_arr,
            'data_info'      => $data_info,
            'status_arr'     => $status_arr,
            'chart_unit_name' =>$chart_unit_name,
            'text'           => $text ?? [],
            'chart_unit_label' => $chart_unit_label,
        ];


        // AJAX 或一般頁面
        if (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            isset($_GET['ajax'])
        ) {
            header('Content-Type: application/json');
            echo json_encode($data);
            return;
        }

    
  



        if ($isMobile) {
            $this->view('dashboards/operation_m', $data);
        } else {
            $this->view('dashboards/operation', $data);
        }
    }





    public function change_language(){

        $error_message = '';
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if( !empty($_POST['language']) && isset($_POST['language'])  ){
            $language = $_POST['language'];
        }else{ 
            $input_check = false; 
            $error_message .= "language,";
        }
        $_SESSION['language'] = $language;

        $response = array(
            'language' => $language,
            'result' => true,
        );
        echo json_encode($response);
    

    }



    public function get_latest_csv() {

        $dir = "/var/www/html/ntcs_idas/public/ftp";
        $files = glob($dir . "/*.csv");

        if (!$files) {
            echo json_encode(["status" => false, "msg" => "no csv"]);
            return;
        }

        // 取最新文件
        usort($files, fn($a,$b) => filemtime($b) - filemtime($a));
        $file = $files[0];

        // -------------------------
        // ⭐ CSV STABILIZER（最終版）
        // -------------------------
        $stable = false;
        $maxTry = 5;          // 最多 5 次（0.5 秒）
        $interval = 100000;   // 每次睡眠 0.1 秒（微秒）
        $lastHash = null;

        for ($i = 0; $i < $maxTry; $i++) {

            $text = @file_get_contents($file);
            if ($text === false) break;

            $hash = hash("crc32b", $text);

            if ($lastHash === null) {
                // 第一次讀
                $lastHash = $hash;
            }
            else if ($hash === $lastHash) {
                // 🎉 CSV 已穩定
                $stable = true;
                break;
            }
            else {
                // CSV 還在寫入 → 更新 baseline
                $lastHash = $hash;
            }

            usleep($interval);
        }

        echo json_encode([
            "status" => true,
            "csv"    => "/ntcs_idas/public/ftp/" . basename($file),
            "stable" => $stable
        ]);
    }

    public function get_current_data(){

        $status_arr = $this->MiscellaneousModel->details('status');
        $unit_arr   = $this->MiscellaneousModel->details('modbus_torque_unit');
        $new_unit = $this->device_torque_unit;
        $current_data = $this->DataModel->get_operation_info(); 
        


        return $current_data;
    
    }


    public function auto_fix_and_sync_csv(){

        $sourceDir = '/mnt/ramdisk/ftp';
        $targetDir = '/var/www/html/ntcs_idas/public/ftp';

        // Debug log
        @mkdir($targetDir, 0777, true);
        $logFile = $targetDir . '/sync_debug.log';
        $fp = @fopen($logFile, "a");

        $log = function($msg) use ($fp) {
            if ($fp) fwrite($fp, "[" . date('Y-m-d H:i:s') . "] $msg\n");
        };

        $log("==== auto_fix_and_sync_csv START ====");

        // Step 1：找來源 CSV
        $files = glob($sourceDir . '/*.csv');
        if (!$files) {
            $log("No source CSV found");
            return;
        }

        // ★ Step 1-1：依檔名前面連續數字排序（DESC）
        usort($files, function($a, $b) {

            // 抓前導數字，例如 535、45、007
            preg_match('/^(\d+)/', basename($a), $ma);
            preg_match('/^(\d+)/', basename($b), $mb);

            $na = intval($ma[1] ?? 0);
            $nb = intval($mb[1] ?? 0);

            return $nb <=> $na; // 數字 DESC（最大在最前）
        });

        // 最新的 CSV（依前導數字最大）
        $src  = $files[0];
        $name = basename($src);

        $log("Latest CSV (by leading number) = $name");

        // 目標檔案
        $dest = $targetDir . "/" . $name;
        $temp = $targetDir . "/." . $name . ".tmp";

        // Step 2：原子 copy
        if (!file_exists($dest) || filesize($src) !== filesize($dest)) {

            $log("Copying using temp…");

            // copy → temp file
            if (!@copy($src, $temp)) {
                $log("ERROR: temp copy failed");
                return;
            }

            // rename → final file（原子操作）
            if (!@rename($temp, $dest)) {
                $log("ERROR: rename failed");
                return;
            }

            $log("Copied OK → $name");

        } else {
            $log("No change, skip copy");
        }

        // Step 3：只保留最新一個 CSV（依前導數字排序）
        $targetFiles = glob($targetDir . '/*.csv');

        if ($targetFiles && count($targetFiles) > 1) {

            // 單純用前導數字排序
            usort($targetFiles, function($a, $b) {

                preg_match('/^(\d+)/', basename($a), $ma);
                preg_match('/^(\d+)/', basename($b), $mb);

                $na = intval($ma[1] ?? 0);
                $nb = intval($mb[1] ?? 0);

                return $nb <=> $na;
            });

            // 最新（最大數字）保留，其他刪除
            $delete = array_slice($targetFiles, 1);

            foreach ($delete as $del) {
                @unlink($del);
                $log("Deleted old CSV: " . basename($del));
            }
        }

        $log("==== auto_fix_and_sync_csv END ====");
        if ($fp) fclose($fp);
    }












    public function cleanCsvKeepLast10Core(){

        $dir = '/var/www/html/ntcs_idas/public/ftp';

        if (!is_dir($dir)) {
            return [false, "目錄不存在：{$dir}"];
        }

        // 抓所有 .csv 檔
        $pattern = rtrim($dir, '/') . '/*.csv';
        $files = glob($pattern);

        if (!$files || count($files) <= 1) {
            // 沒有或少於等於 10 筆，不需要刪
            return [true, "目前 CSV 數量 <= 10，無需刪除"];
        }

        // 依照「最後修改時間」由新到舊排序
        usort($files, function ($a, $b) {
            $ma = @filemtime($a) ?: 0;
            $mb = @filemtime($b) ?: 0;
            // 新的在前面
            return $mb <=> $ma;
        });

        // 保留前 1 筆，其餘刪除
        $keep   = array_slice($files, 0, 1);
        $delete = array_slice($files, 1);

        $deleted = [];
        $failed  = [];

        foreach ($delete as $file) {
            if (@is_file($file)) {
                if (@unlink($file)) {
                    $deleted[] = basename($file);
                } else {
                    $failed[] = basename($file);
                }
            }
        }

        $msg = "總共檔案數：" . count($files) .
            "，保留：" . count($keep) .
            "，刪除：" . count($deleted);

        if ($failed) {
            $msg .= "，刪除失敗：" . implode(',', $failed);
            return [false, $msg];
        }

        return [true, $msg];
    }










}
?>