<?php

class Data extends Controller
{
    private $DataModel;
    private $MiscellaneousModel;
    private $ToolModel;
    private $SettingModel;
    Private $deviceId;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->DataModel = $this->model('Datas');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->ToolModel = $this->model('Tool');
        $this->SettingModel = $this->model('Setting');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();

    }

    // 取得所有Jobs
    public function index(){

        $type = 'ALL';
        $this->ntcs_data_db_sysnc();
        $isMobile = $this->isMobileCheck();
        $decimals_arr = $this->MiscellaneousModel->details("decimals");
        // 取得當前年份
        if (PHP_OS_FAMILY === 'Linux') {
            $db_path = "/var/www/html/database/data".date('Y').".db";

            // 檢查資料庫是否存在
            $db_exists = file_exists($db_path);
    
            if ($db_exists) {
                $res_data     = $this->DataModel->getData('ALL');
                $res_data_ok  = $this->DataModel->getData('OK');
                $res_data_nok = $this->DataModel->getData('NOK');
            } else {
                $res_data     = [];
                $res_data_ok  = [];
                $res_data_nok = [];
            }

        }else{
            $res_data     = $this->DataModel->getData('ALL');
            $res_data_ok  = $this->DataModel->getData('OK');
            $res_data_nok = $this->DataModel->getData('NOK');
            $db_exists = '';
            $db_path = '';
        }


        $unit_arr    = $this->MiscellaneousModel->details('torque_unit');
        $status_arr  = $this->MiscellaneousModel->details('status');
        $color_arr   = $this->get_color_type();

        foreach ($res_data as &$row) {
            $status = $row['fasten_status'];

            // 加入 NG 顏色或 OK 顏色分類
            if ($status == 5) {
                $row['row_color'] = $color_arr['okseqcolor_text'];
            }else if($status == 6) {
                $row['row_color'] = $color_arr['okseqcolor_text'];
            }else if ($status == 4){
                $row['row_color'] = 'status-ok';
            }else{
                $row['row_color'] = 'status-ng';
            } 

            $torque_value = $row['final_fasten_torque'] ?? 0;
            $torque_unit  = $row['torque_unit'] ?? 1; // 預設為 N.m
            $precision = $decimals_arr[$torque_unit] ?? 3; // 預設顯示三位小數
            $row['final_fasten_torque'] = number_format((float)$torque_value, $precision);

        }

        $data = array(
            'isMobile'      => $isMobile,
            'res_data'      => $res_data,
            'res_data_ok'   => $res_data_ok,
            'res_data_nok'  => $res_data_nok,
            'unit_arr'      => $unit_arr,
            'status_arr'    => $status_arr,
            'db_exists'     => $db_exists,
            'db_path'       => $db_path,
            'color_arr'     => $color_arr
        );

        $this->view('data/index', $data);
    }



    public function exportData() {


        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        $input_check = true;

        // 取得控制器資訊（含序號）
        $controller_info = $this->SettingModel->GetControllerInfo();

        // 檢查開始/結束日期（補上秒）
        if (!empty($_POST['start_date']) && isset($_POST['start_date'])) {
            $start_date = $_POST['start_date'] . ":00";
        } else {
            $input_check = false;
        }

        if (!empty($_POST['end_date']) && isset($_POST['end_date'])) {
            $end_date = $_POST['end_date'] . ":00";
        } else {
            $input_check = false;
        }

        // 匯出格式：0=CSV, 1=ZIP(內含CSV)
        $expert_val = $_POST['expert_val'] ?? "0";

        if (!$input_check) {
            echo json_encode(["error" => "輸入參數不正確"]);
            exit();
        }

        // 撈資料
        $dataset = $this->DataModel->get_range_data($start_date, $end_date);
        if (count($dataset) === 0) {
            echo json_encode(["error" => "無法找到符合條件的資料"]);
            exit();
        }

        // 限制最多 10,000 筆
        $dataset       = array_slice($dataset, 0, 10000);
        $csv_headers   = array_keys($dataset[0]); // 取欄位鍵名（用來決定輸出順序）
        $csv_headers_temp = $csv_headers;         // 這是要「顯示」的表頭

        if (!empty($text) && is_array($text)) {
            foreach ($csv_headers as $key => $val) {
                // 將顯示用表頭改成中文（找不到翻譯就用原鍵名）
                $csv_headers_temp[$key] = $text[$val] ?? $val;
            }
        }

        // ---- 取得系統時區並設定 PHP 時區（只有在未設定時才設定）----
        $system_timezone = trim(@exec('timedatectl show -p Timezone --value 2>/dev/null'));
        if (empty($system_timezone)) {
            // 抓不到完整名稱，用縮寫（可能像 CST）
            $system_timezone = trim(@exec('date +%Z'));
        }
        if (empty(ini_get('date.timezone'))) {
            @date_default_timezone_set(!empty($system_timezone) ? $system_timezone : 'Asia/Taipei');
        }

        // ---- 取得時間字串（到秒）。先嘗試使用系統 date；失敗就用 PHP 時間 ----
        $timestamp_str = null;
        if (function_exists('shell_exec')) {
            $timestamp_str = trim(@shell_exec("date '+%Y%m%d%H%M%S'"));
        }
        if (empty($timestamp_str)) {
            // Fallback：用 PHP 的時間（已設時區）
            $timestamp_str = date('YmdHis');
        }

        // ---- 安全處理 device_sn（避免非法字元進入檔名）----
        $device_sn_safe = preg_replace('/[^A-Za-z0-9_\-]/', '_', $controller_info['device_sn'] ?? 'UNKNOWN');

        // ---- 組檔名 ----
        $csv_filename = "data_{$device_sn_safe}_{$timestamp_str}.csv";
        $zip_filename = "data_{$device_sn_safe}_{$timestamp_str}.zip";

        // 清掉可能的既有輸出緩衝，避免 header 被吃掉
        if (function_exists('ob_get_length') && ob_get_length()) {
            @ob_end_clean();
        }

        if ($expert_val === "0") {
            // ---------------- CSV 直接下載 ----------------
            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename={$csv_filename}");

            $output = fopen('php://output', 'w');

            // 需要 Excel 友善可視需求加入 BOM：
            fwrite($output, "\xEF\xBB\xBF");

            // 表頭

            fputcsv($output, $csv_headers_temp);

            // 資料列仍依「鍵名順序」輸出
            foreach ($dataset as $row) {
                $ordered = [];
                foreach ($csv_headers as $h) {
                    $ordered[] = $row[$h] ?? '';
                }
                fputcsv($output, $ordered);
            }
            fclose($output);
            exit();

        } elseif ($expert_val === "1") {
            // ---------------- 產 CSV 字串 -> 打包成 ZIP 再下載 ----------------
            // 用 fputcsv 正確產生 CSV 內容（避免逗號/引號/換行破壞）
            $fh = fopen('php://temp', 'w+');

            // 需要 Excel 友善可選擇寫入 BOM：
            fwrite($fh, "\xEF\xBB\xBF");

            fputcsv($fh, $csv_headers_temp);
            foreach ($dataset as $row) {
                $ordered = [];
                foreach ($csv_headers as $h) {
                    $ordered[] = $row[$h] ?? '';
                }
                fputcsv($fh, $ordered);
            }
            rewind($fh);
            $csv_content = stream_get_contents($fh);
            fclose($fh);

            // 準備 ZIP 暫存檔
            if (!class_exists('ZipArchive')) {
                echo json_encode(["error" => "伺服器未啟用 ZipArchive 模組"]);
                exit();
            }

            $zip = new ZipArchive();
            $temp_zip_path = tempnam(sys_get_temp_dir(), 'ntcs_zip');
            // Windows 上 tempnam 已含副檔名，另建 .zip 檔避免某些系統無副檔名問題
            $temp_zip_final = $temp_zip_path . '.zip';

            if ($zip->open($temp_zip_final, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                // 將 CSV 內容加到 ZIP，內部檔名使用 $csv_filename
                $zip->addFromString($csv_filename, $csv_content);
                $zip->close();

                header('Content-Type: application/zip');
                header("Content-Disposition: attachment; filename={$zip_filename}");
                header('Content-Length: ' . filesize($temp_zip_final));
                readfile($temp_zip_final);

                @unlink($temp_zip_final);
                // 某些系統也需要刪除 tempnam 原檔
                @unlink($temp_zip_path);
                exit();
            } else {
                echo json_encode(["error" => "無法建立 ZIP 檔案"]);
                // 清理殘留
                @unlink($temp_zip_final);
                @unlink($temp_zip_path);
                exit();
            }
        } else {
            echo json_encode(["error" => "未知的匯出格式參數"]);
            exit();
        }
    }



    public function getreal_time_data() {

        $mode = $_POST['mode'] ?? 'ALL';

        // 根據系統設定路徑
        if(PHP_OS_FAMILY  ==="Linux"){
            $base_path = '/home/kls/NTCS7/';
        }else{
            $base_path =  '../';
        }
      
        $db_path = $base_path . "ntcs_data.db";


        if (!file_exists($db_path)) {
            echo json_encode(['success' => false, 'msg' => "資料庫不存在"]);
            return;
        }

        $res_data     = $this->DataModel->getData($mode);
        $unit_arr     = $this->MiscellaneousModel->details('torque_unit');
        $status_arr   = $this->MiscellaneousModel->details('status');
        $decimals_arr = $this->MiscellaneousModel->details("decimals");

        $color_arr  = $this->get_color_type();

        // 加入對應的顏色到每筆資料
        foreach ($res_data as &$row) {
            $status = $row['fasten_status'];

            //

            if($status == 5) {
                $row['row_color'] = $color_arr['okseqcolor_text'];
            }else if ($status == 6) {
                $row['row_color'] = $color_arr['okjobcolor_text'];
            }else if($status == 4){
                $row['row_color'] = 'status-ok';
            }else{
                $row['row_color'] = 'status-ng';
            }

            $torque_value = $row['final_fasten_torque'] ?? 0;
            $torque_unit  = $row['torque_unit'] ?? 1; // 預設為 N.m
            $precision = $decimals_arr[$torque_unit] ?? 3; // 預設顯示三位小數
            $row['final_fasten_torque'] = number_format((float)$torque_value, $precision);

   
        }

        echo json_encode([
            'success' => true,
            'records' => $res_data,
            'unit_arr' => $unit_arr,
            'status_arr' => $status_arr,
            'color_arr' => $color_arr
        ]);
    }



    public function get_color_type(){

        $Controller_Info = $this->ToolModel->GetControllerInfo();   

        if(!empty($Controller_Info)){
            $color_arr = array();
            $color_arr['okseqcolor'] = $Controller_Info['okseqcolor'];
            $color_arr['okjobcolor'] = $Controller_Info['okjobcolor'];

            if(!empty($color_arr)){
                if( $color_arr['okseqcolor']  == 0){
                    $color_arr['okseqcolor_text'] = 'status-ok';
                }else{
                    $color_arr['okseqcolor_text'] = 'status-warn';
                }

                if( $color_arr['okjobcolor']  == 0){
                    $color_arr['okjobcolor_text'] = 'status-ok';
                }else{
                    $color_arr['okjobcolor_text'] = 'status-warn';
                }
            }
            
        }   

        return $color_arr;

    }
        
    public function assignRowColor(&$rows, $color_arr, $force_ng = false) {
        foreach ($rows as &$row) {
            if ($force_ng) {
                $row['row_color'] = 'status-ng';
                continue;
            }

            $status = $row['fasten_status'];

            if ($status == 5) {
                $row['row_color'] = $color_arr['okseqcolor_text'] ?? 'status-ok';
            } elseif ($status == 6) {
                $row['row_color'] = $color_arr['okjobcolor_text'] ?? 'status-ok';
            } elseif ($status == 4) {
                $row['row_color'] = 'status-ok';
            } else {
                $row['row_color'] = 'status-ng';
            }
        }
    }

    
    public function download_file() {

        // 僅支援 Linux
        if (PHP_OS_FAMILY !== 'Linux') {
            return $this->respondError('Error', '只支援在 Linux 環境下下載 CSV 壓縮包。');
        }

        $dir = '/mnt/ramdisk/ftp';
        if (!is_dir($dir) || !is_readable($dir)) {
            return $this->respondError('Error', "資料夾無法讀取：{$dir}");
        }

        // 收集 CSV：解析開頭流水號與時間戳（作為排序依據）
        $entries = [];
        try {
            $it = new DirectoryIterator($dir);
            foreach ($it as $f) {
                if (!$f->isFile() || !$f->isReadable()) continue;
                $name = $f->getFilename();
                $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if ($ext !== 'csv') continue;

                $full   = $f->getPathname();
                $serial = -1; // 找不到就設為 -1（排最後）
                if (preg_match('/^(\d+)__/', $name, $m)) {
                    $serial = (int)$m[1];
                }

                // 解析檔名中的 14 碼時間戳；沒有就用 mtime
                $ts = 0;
                if (preg_match('/_(\d{14})(?:_|\.csv$)/i', $name, $m)) {
                    $dt = DateTime::createFromFormat('YmdHis', $m[1]);
                    if ($dt) $ts = $dt->getTimestamp();
                }
                if ($ts <= 0) {
                    $mtime = @filemtime($full);
                    if ($mtime !== false) $ts = (int)$mtime;
                }

                $entries[] = [
                    'path'   => $full,
                    'name'   => $name,
                    'serial' => $serial,
                    'ts'     => $ts,
                ];
            }
        } catch (Throwable $e) {
            return $this->respondError('Error', '掃描資料夾失敗：' . $e->getMessage());
        }

        // ★ 沒資料：回傳 JSON，讓前端彈「沒有曲線圖可下載」的提示
 
        
        if (empty($entries)) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
                header('Cache-Control: no-store, no-cache, must-revalidate');
            }
            echo json_encode([
                'res_type' => 'Info',
                'res_code' => 'NO_CURVE_DATA',
                'res_msg'  => 'No curve data'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 排序：先流水號(大→小)，再時間(新→舊)，再檔名
        usort($entries, function ($a, $b) {
            $as = $a['serial']; $bs = $b['serial'];
            if ($as < 0 && $bs >= 0) return 1;   // 無流水號者排後
            if ($bs < 0 && $as >= 0) return -1;
            if ($as !== $bs) return $bs <=> $as;                 // 流水號大→前
            if ($a['ts'] !== $b['ts']) return $b['ts'] <=> $a['ts']; // 新→前
            return strcmp($a['name'], $b['name']);
        });

        if (!class_exists('ZipArchive')) {
            return $this->respondError('Error', '伺服器未安裝 ZipArchive 擴充，無法建立 ZIP。');
        }

        // ZIP 檔名用 Linux 系統時間（fallback: PHP date）
        $ts          = $this->linuxNowOrPhp();
        $zipBasename = "csv_bundle_{$ts}.zip";
        $tmpZip      = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $zipBasename;

        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return $this->respondError('Error', '無法建立 ZIP 壓縮檔。');
        }

        foreach ($entries as $e) {
            $zip->addFile($e['path'], $e['name']); // 保留原檔名
        }
        $zip->close();

        if (!is_file($tmpZip) || !is_readable($tmpZip)) {
            return $this->respondError('Error', 'ZIP 產生失敗或不可讀取。');
        }

        // 串流下載
        @set_time_limit(0);
        if (function_exists('ob_get_level')) { while (ob_get_level() > 0) { @ob_end_clean(); } }
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $zipBasename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($tmpZip));
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $fp = fopen($tmpZip, 'rb');
        if ($fp) {
            while (!feof($fp)) { echo fread($fp, 8192); flush(); }
            fclose($fp);
        } else {
            @unlink($tmpZip);
            return $this->respondError('Error', '無法讀取 ZIP 檔案。');
        }
        @unlink($tmpZip);
        exit;
    }



    /** 取 Linux 系統時間（失敗退回 PHP date） */
    protected function linuxNowOrPhp(): string{

        $ts = date('YmdHis');
        if (PHP_OS_FAMILY === 'Linux') {
            $out = @shell_exec("date '+%Y%m%d%H%M%S' 2>/dev/null");
            $out = is_string($out) ? trim($out) : '';
            if (preg_match('/^\d{14}$/', $out)) $ts = $out;
        }
        return $ts;
    }

    /** 統一錯誤回應（沿用你的 MiscellaneousModel；沒有就回 JSON） */
    protected function respondError(string $type, string $msg){
        
        if (isset($this->MiscellaneousModel) && method_exists($this->MiscellaneousModel, 'generateErrorResponse')) {
            return $this->MiscellaneousModel->generateErrorResponse($type, $msg);
        }
        if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['res_type'=>$type, 'res_msg'=>$msg], JSON_UNESCAPED_UNICODE);
        return null;
    }

    public function qa_check(){
           $type = 'ALL';
        $this->ntcs_data_db_sysnc();
        $isMobile = $this->isMobileCheck();
        $decimals_arr = $this->MiscellaneousModel->details("decimals");
        // 取得當前年份
        if (PHP_OS_FAMILY === 'Linux') {
            $db_path = "/var/www/html/database/data".date('Y').".db";

            // 檢查資料庫是否存在
            $db_exists = file_exists($db_path);
    
            if ($db_exists) {
                $res_data     = $this->DataModel->getData('ALL');
                $res_data_ok  = $this->DataModel->getData('OK');
                $res_data_nok = $this->DataModel->getData('NOK');
            } else {
                $res_data     = [];
                $res_data_ok  = [];
                $res_data_nok = [];
            }

        }else{
            $res_data     = $this->DataModel->getData('ALL');
            $res_data_ok  = $this->DataModel->getData('OK');
            $res_data_nok = $this->DataModel->getData('NOK');
            $db_exists = '';
            $db_path = '';
        }


        $unit_arr    = $this->MiscellaneousModel->details('torque_unit');
        $status_arr  = $this->MiscellaneousModel->details('status');
        $color_arr   = $this->get_color_type();

        foreach ($res_data as &$row) {
            $status = $row['fasten_status'];

            // 加入 NG 顏色或 OK 顏色分類
            if ($status == 5) {
                $row['row_color'] = $color_arr['okseqcolor_text'];
            }else if($status == 6) {
                $row['row_color'] = $color_arr['okseqcolor_text'];
            }else if ($status == 4){
                $row['row_color'] = 'status-ok';
            }else{
                $row['row_color'] = 'status-ng';
            } 

            $torque_value = $row['final_fasten_torque'] ?? 0;
            $torque_unit  = $row['torque_unit'] ?? 1; // 預設為 N.m
            $precision = $decimals_arr[$torque_unit] ?? 3; // 預設顯示三位小數
            $row['final_fasten_torque'] = number_format((float)$torque_value, $precision);

        }

        $data = array(
            'isMobile'      => $isMobile,
            'res_data'      => $res_data,
            'res_data_ok'   => $res_data_ok,
            'res_data_nok'  => $res_data_nok,
            'unit_arr'      => $unit_arr,
            'status_arr'    => $status_arr,
            'db_exists'     => $db_exists,
            'db_path'       => $db_path,
            'color_arr'     => $color_arr
        );

        $this->view('data/qa_check', $data);
    }









}
?>