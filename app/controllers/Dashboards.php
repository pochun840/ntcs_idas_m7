<?php

class Dashboards extends Controller
{
    private $DashboardModel;
    private $AdminModel;
    private $MiscellaneousModel;
    private $DataModel;
    private $SettingModel;
    Private $deviceId;

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
        $data_info          = $this->DashboardModel->get_Data() ?? [];
        $controller_info    = $this->SettingModel->GetControllerInfo();
        $status_arr         = $this->MiscellaneousModel->details('status');
        $unit_arr           = $this->MiscellaneousModel->details('modbus_torque_unit');
        $decimals_arr       = $this->MiscellaneousModel->details('decimals');
        $res_device         = $this->SettingModel->GetControllerInfo();

        // ⭐ 自動同步 CSV
        $this->auto_fix_and_sync_csv();

        $this->cleanCsvKeepLast10Core();



        $torque_unit = (int)($data_info['torque_unit'] ?? 1);
        $chart_unit_name    = $unit_arr[$torque_unit] ?? 'N.m';

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

        // ★ 取資料：chart=7 時改抓 2 的資料
        /*$fetch_mode  = ($chart_mode === 7 ? 2 : $chart_mode);
        $csvdata_arr = $this->DashboardModel->get_info($fetch_mode, $id);

        // 用 mode5 原始扭力（轉單位後）得出統一扭力範圍
        $unified_min_torque = 0;
        $unified_max_torque = 100;
        $torque_range_mode5 = $this->DashboardModel->get_info(5, $id);
        if (!empty($torque_range_mode5['torque'])) {
            $torque_vals = array_slice($torque_range_mode5['torque'], 1);
            if (!empty($torque_vals)) {
                $torque_vals_converted = array_map(function($val) use ($torque_unit) {
                    return (float)$this->MiscellaneousModel->convert_single_torque_unit($val, 1, $torque_unit);
                }, $torque_vals);
                $unified_min_torque = min($torque_vals_converted);
                $unified_max_torque = max($torque_vals_converted);
            }
        }

        // RPM 範圍
        $unified_min_rpm = null;
        $unified_max_rpm = null;
        $rpm_range_mode3 = $this->DashboardModel->get_info(3, $id);
        if (!empty($rpm_range_mode3['rpm'])) {
            $rpm_vals = array_slice($rpm_range_mode3['rpm'], 1);
            if (!empty($rpm_vals)) {
                $unified_min_rpm = min($rpm_vals);
                $unified_max_rpm = max($rpm_vals);
            }
        }*/

        // 組 chart payload
        /*$temp_chart = null;
        if (!empty($csvdata_arr)) {
            if ($chart_mode !== 5) {
                $csvdata_arr = array_slice($csvdata_arr, 1); // 去表頭
                $temp_chart  = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val, $angle_as_x);
            } else {
                if (isset($csvdata_arr['torque'], $csvdata_arr['rpm']) &&
                    is_array($csvdata_arr['torque']) && is_array($csvdata_arr['rpm']) &&
                    !empty($csvdata_arr['torque'])   && !empty($csvdata_arr['rpm'])) {
                    array_shift($csvdata_arr['torque']);
                    array_shift($csvdata_arr['rpm']);
                    $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val);
                }
            }

            if (!empty($temp_chart)) {
                // chart=6：把 chart=4 的 Y（及上下界）覆寫進來；X 仍是 angle
                if ($chart_mode === 6) {
                    $csv_for_mode4 = $this->DashboardModel->get_info(4, $id);
                    if (is_array($csv_for_mode4) && !empty($csv_for_mode4)) {
                        $csv_for_mode4 = array_slice($csv_for_mode4, 1); // 去表頭
                        $chart4 = $this->ChartData(4, $csv_for_mode4, 4, $x_val, $angle_as_x);

                        $lenX = isset($temp_chart['x_val']) ? count($temp_chart['x_val']) : 0;
                        $lenY = isset($chart4['y_val'])     ? count($chart4['y_val'])     : 0;
                        $len  = min($lenX, $lenY);

                        if ($len > 0) {
                            $temp_chart['y_val'] = array_slice($chart4['y_val'], 0, $len);
                            $temp_chart['y_min'] = $chart4['min'] ?? $temp_chart['y_min'] ?? null;
                            $temp_chart['y_max'] = $chart4['max'] ?? $temp_chart['y_max'] ?? null;

                            // 若前端有用 min/max_torque 也一併帶上
                            $temp_chart['min_torque'] = $chart4['min'] ?? $temp_chart['min_torque'] ?? 0;
                            $temp_chart['max_torque'] = $chart4['max'] ?? $temp_chart['max_torque'] ?? 0;

                            // steps 裁到相同長度
                            if (!empty($step_only) && is_array($step_only)) {
                                $steps = $step_only;
                                if (!empty($steps) && !is_numeric(reset($steps))) array_shift($steps);
                                $steps = array_values($steps);
                                $temp_chart['steps'] = array_slice($steps, 0, $len);
                            } else {
                                $temp_chart['steps'] = array_fill(0, $len, 'S1');
                            }
                        }
                    }
                } else {
                    // 非 6：處理 steps 對齊
                    if (is_array($step_only) && !empty($step_only)) {
                        $steps = $step_only;
                        if (!empty($steps) && !is_numeric(reset($steps))) array_shift($steps);
                        $steps = array_values($steps);
                        $len   = isset($temp_chart['x_val']) ? count($temp_chart['x_val']) : 0;
                        if ($len > 0) $steps = array_slice($steps, 0, $len);
                        $temp_chart['steps'] = $steps;
                    } else {
                        $temp_chart['steps'] = array_fill(0, count($temp_chart['x_val'] ?? []), 'S1');
                    }
                }

                // 1/4/5/6 共用扭力範圍（★ 7 等同 2，不要帶扭力範圍）
                if (in_array($chart_mode, [1,4,5,6], true)) {
                    $temp_chart['min_torque'] = $unified_min_torque;
                    $temp_chart['max_torque'] = $unified_max_torque;
                }

                // RPM 範圍（只有 5 用）
                if ($chart_mode == 5 && $unified_max_rpm > $unified_min_rpm) {
                    $temp_chart['min_rpm'] = $unified_min_rpm;
                    $temp_chart['max_rpm'] = $unified_max_rpm;
                }
                if (!isset($temp_chart['min_torque'])) $temp_chart['min_torque'] = 0;
                if (!isset($temp_chart['max_torque'])) $temp_chart['max_torque'] = 100;

                $temp_chart['chart_unit_name'] = $chart_unit_name;
            }
        }*/



        // 組回傳
        $data = [
            'isMobile'       => $isMobile,
            //'chart_info'     => $temp_chart,
            'echart_name'    => $echart_name,
            'chart_mode'     => $chart_mode,
            'chart_menu_arr' => $chart_menu_arr,
            'data_info'      => $data_info,
            'status_arr'     => $status_arr,
            'text'           => $text ?? [],
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


       /**
     * 組裝圖表資料
     *
     * @param int   $chat_mode      圖表模式 (1,2,3,4,5,6,7)
     * @param array $csvdata_arr    後端取回的原始資料（可能含鍵：torque/rpm/angle/y_val/x_val）
     * @param mixed $chat_mode_arr  未使用（保留簽名）
     * @param array $x_val          第一欄 X（多半是時間；含表頭時會自動去掉）
     * @param array $angle_as_x     供 4/6 使用的 X（以 mode2 的 angle 或 y_val 為準）
     * @return array                chart payload
     */
    private function ChartData($chat_mode, $csvdata_arr, $chat_mode_arr, $x_val, $angle_as_x = []) {
        $chart_info = [];
        $chat_mode  = (int)$chat_mode;

        // 取得裝置扭力單位與精度
        $res_device          = $this->SettingModel->GetControllerInfo();
        $device_torque_unit  = (int)($res_device['torque_unit'] ?? 1);
        $unit_arr            = $this->MiscellaneousModel->details('torque_unit');
        $unit_name           = $unit_arr[$device_torque_unit] ?? 'N.m';
        $decimals_arr        = $this->MiscellaneousModel->details('decimals');
        $precision           = $decimals_arr[$device_torque_unit] ?? 3;

        // ---- 小工具：正規化序列（去表頭、扁平化、轉 float）
        $normalizeSeries = function($arr) {
            if (!is_array($arr)) return [];
            // 若是關聯陣列（帶子鍵），取第一個常見的子鍵
            if (array_keys($arr) !== range(0, count($arr)-1)) {
                foreach (['torque', 'rpm', 'angle', 'y_val', 'x_val'] as $k) {
                    if (isset($arr[$k]) && is_array($arr[$k])) { $arr = $arr[$k]; break; }
                }
            }
            if (!empty($arr) && !is_numeric(reset($arr))) array_shift($arr); // 去表頭
            $arr = array_values($arr);
            return array_map('floatval', $arr);
        };

        // ---- 小工具：chart=2 的序列做 X（angle 或 y_val）
        $normalizeAngleAsX = function($arr) use ($normalizeSeries) {
            if (!is_array($arr)) return [];
            if (isset($arr['angle']) && is_array($arr['angle']))          $arr = $arr['angle'];
            elseif (isset($arr['y_val']) && is_array($arr['y_val']))      $arr = $arr['y_val'];
            return $normalizeSeries($arr);
        };

        // ---- 扭力單位轉換
        $convertTorque = function($val) use ($device_torque_unit, $precision) {
            $converted = $this->MiscellaneousModel->convert_single_torque_unit($val, 1, $device_torque_unit);
            return round((float)$converted, $precision);
        };

        // ---- 準備 Y 序列（各模式）
        if ($chat_mode === 5) {
            // 扭力 / RPM 雙軸
            $torque = $normalizeSeries($csvdata_arr['torque'] ?? $csvdata_arr);
            $rpm    = $normalizeSeries($csvdata_arr['rpm']    ?? []);

            $torque_converted              = array_map($convertTorque, $torque);
            $chart_info['y_val_torque']    = $torque_converted;
            $chart_info['y_val_rpm']       = $rpm;
            // 兼容：y_val 預設放扭力
            $chart_info['y_val']           = $torque_converted;

            $chart_info['max_torque']      = !empty($torque_converted) ? max($torque_converted) : 0;
            $chart_info['min_torque']      = !empty($torque_converted) ? min($torque_converted) : 0;
            $chart_info['max_rpm']         = !empty($rpm) ? max($rpm) : 0;
            $chart_info['min_rpm']         = !empty($rpm) ? min($rpm) : 0;

            $chart_info['max']             = $chart_info['max_torque'];
            $chart_info['min']             = $chart_info['min_torque'];

        } elseif (in_array($chat_mode, [1,2,3,4,6,7], true)) {
            if (in_array($chat_mode, [1,4,6], true)) {
                // 扭力曲線（單位換算）
                $series = $csvdata_arr;
                if (is_array($csvdata_arr) && isset($csvdata_arr['torque']) && is_array($csvdata_arr['torque'])) {
                    $series = $csvdata_arr['torque'];
                }
                $series                         = $normalizeSeries($series);
                $torque_converted               = array_map($convertTorque, $series);

                $chart_info['y_val']            = $torque_converted;
                $chart_info['max']              = !empty($torque_converted) ? max($torque_converted) : 0;
                $chart_info['min']              = !empty($torque_converted) ? min($torque_converted) : 0;

                $chart_info['y_val_torque']     = $torque_converted;
                $chart_info['max_torque']       = $chart_info['max'];
                $chart_info['min_torque']       = $chart_info['min'];

            } else {
                // 模式 2 / 3 / 7（★ 7 等同 2）
                $series                 = $normalizeSeries($csvdata_arr);
                $chart_info['y_val']    = $series;
                $chart_info['max']      = !empty($series) ? max($series) : 0;
                $chart_info['min']      = !empty($series) ? min($series) : 0;
            }
        } else {
            $chart_info['y_val'] = [];
            $chart_info['max']   = 0;
            $chart_info['min']   = 0;
        }

        $chart_info['unit_name'] = $unit_name;

        // ---- X 軸：一般用第一欄；chart=4/6 用 mode2 的序列（angle/y_val）
        $x_norm = $normalizeSeries($x_val);
        if ($chat_mode === 4 || $chat_mode === 6) {
            $x_from_mode2 = $normalizeAngleAsX($angle_as_x);
            $y_series     = $chart_info['y_val'] ?? [];

            $len = min(count($x_from_mode2), count($y_series));
            if ($len > 0) {
                $chart_info['x_val']      = array_slice($x_from_mode2, 0, $len);
                $chart_info['y_val']      = array_slice($y_series,     0, $len);
                $chart_info['x_axis_from']= 'mode2_y';
            } else {
                $chart_info['x_val']      = array_map(function($v){ return ($v == (int)$v) ? (int)$v : (float)$v; }, $x_norm);
                $chart_info['x_axis_from']= 'x_first_col';
            }

            // 讓前端可以沿用 4 的 y 軸上下界
            $chart_info['y_min'] = $chart_info['min'];
            $chart_info['y_max'] = $chart_info['max'];

        } else {
            $chart_info['x_val']       = array_map(function($v){ return ($v == (int)$v) ? (int)$v : (float)$v; }, $x_norm);
            $chart_info['x_axis_from'] = 'x_first_col';
        }

        /* ----------------------------
        * chart_mode == 7: 角度步驟圖（需要累積角度）
        * 控制器的 Step2, Step3 角度不會從 0 開始畫
        * 必須做「角度累積」→ 才會長得跟控制器 UI 一樣
        * ---------------------------- */
        if ($chat_mode === 7) {
            $angle = $chart_info['y_val'] ?? [];

            $acc = 0;         // 累積角度偏移值
            $prev = 0;        // 前一筆角度
            $fixed = [];

            foreach ($angle as $v) {
                $v = floatval($v);

                // 若角度突然變小 → 視為新步驟開始
                if ($v < $prev) {
                    $acc += $prev;      // 累加前一段總角度
                }

                $fixed[] = $v + $acc;
                $prev = $v;
            }

            $chart_info['y_val'] = $fixed;

            // 重新計算 y 軸範圍
            $chart_info['max'] = !empty($fixed) ? max($fixed) : 0;
            $chart_info['min'] = !empty($fixed) ? min($fixed) : 0;
        }



        return $chart_info;
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
        $current_data = $this->DataModel->get_operation_info(); 

        return $current_data;
    
    }


    public function auto_fix_and_sync_csv(){

        $sourceDir = '/mnt/ramdisk/ftp';
        $targetDir = '/var/www/html/ntcs_idas/public/ftp';

        // Debug log
        $logFile = $targetDir . '/sync_debug.log';
        @mkdir($targetDir, 0777, true);
        $fp = @fopen($logFile, "a");
        $log = function($msg) use ($fp) {
            if ($fp) fwrite($fp, "[".date('Y-m-d H:i:s')."] $msg\n");
        };

        $log("==== auto_fix_and_sync_csv START ====");

        // ----------------------------
        // Step 1：找來源 CSV
        // ----------------------------
        $files = glob($sourceDir . '/*.csv');
        if (!$files) {
            $log("No source CSV found");
            return;
        }

        // 找最新的 CSV
        usort($files, fn($a,$b) => filemtime($b) <=> filemtime($a));
        $src = $files[0];
        $name = basename($src);

        $log("Latest CSV = $name");

        // 目標檔案
        $dest = $targetDir . "/" . $name;
        $temp = $targetDir . "/." . $name . ".tmp";

        // ----------------------------
        // Step 2：**原子 copy**
        // ----------------------------
        if (!file_exists($dest) || filemtime($src) !== filemtime($dest)) {

            $log("Copying using temp…");

            // 先 copy 到 temp file
            if (!@copy($src, $temp)) {
                $log("ERROR: temp copy failed");
                return;
            }

            // 設定與原檔一樣的時間
            @touch($temp, filemtime($src));

            // 原子操作 rename（決不會有「只有半份內容」的情況）
            if (!@rename($temp, $dest)) {
                $log("ERROR: rename failed");
                return;
            }

            $log("Copied OK → $name");
        } else {
            $log("No change, skip copy");
        }

        // ----------------------------
        // Step 3：只保留最新一個 CSV
        // ----------------------------
        $targetFiles = glob($targetDir . '/*.csv');
        if ($targetFiles && count($targetFiles) > 1) {
            usort($targetFiles, fn($a,$b) => filemtime($b) <=> filemtime($a));
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