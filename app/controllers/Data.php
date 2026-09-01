<?php
/*
 * Single-codebase platform switch.
 * /home/kls/upgrade/icontroller = 1 -> i-controller implementation
 * 0 / missing / invalid -> NTCS implementation
 */

class Data extends Controller
{
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

    public function getreal_time_data() {

            $mode = $_POST['mode'] ?? 'ALL';

            // 根據系統設定路徑
            if(PHP_OS_FAMILY  ==="Linux"){
                $base_path = IDAS_PATH_CONTROLLER_ROOT . '/';
            }else{
                $base_path =  '../';
            }
          
            $db_path = $base_path . "ntcs_data.db";

            if (!file_exists($db_path)) {
                echo json_encode(['success' => false, 'msg' => "資料庫不存在"]);
                return;
            }

            $res_data     = $this->DataModel->getData($mode);
            $unit_arr     = $this->MiscellaneousModel->details('modbus_torque_unit');
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
                //$precision = $decimals_arr[$torque_unit] ?? 3; // 預設顯示三位小數
                //$row['final_fasten_torque'] = number_format((float)$torque_value, $precision);

       
            }

            echo json_encode([
                'success' => true,
                'records' => $res_data,
                'unit_arr' => $unit_arr,
                'status_arr' => $status_arr,
                'color_arr' => $color_arr
            ]);
        }

    public function index(){

            $type = 'ALL';
            
            // 同步控制器資料庫（ntcs_data.db）至 iDAS
            $this->ntcs_data_db_sysnc();

            // ------------------------------
            // Tool Spec Sync (Web-triggered)
            // ------------------------------
            // Gate(10s): 限制同步檢查頻率，避免每個 request 都打 DB
            // Lock:     使用 flock 防止多 request 同步造成重複/競態
            // Sync:     只在來源(controller)與目的(iDAS)值不同時才更新
            // Note:     非 cron；沒有 request 就不會自動同步
            if ($this->shouldRunToolSpecSync(10)) {
                $this->runOnceWithFlag(
                    IDAS_PATH_DATABASE_ROOT,        // lock / state 檔案目錄
                    '.tool_spec_sync',               // 任務鎖名稱（key）
                    fn() => $this->check_tools_info()// 同步 ntcs_tool_test 規格值
                );
            }

            

            $isMobile = $this->isMobileCheck();
            $decimals_arr = $this->MiscellaneousModel->details("decimals");
            // 取得當前年份
            if (PHP_OS_FAMILY === 'Linux') {
                $db_path = IDAS_PATH_DATABASE_ROOT . "/data".date('Y').".db";

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

            //$unit_arr    = $this->MiscellaneousModel->details('torque_unit');
            $unit_arr    = $this->MiscellaneousModel->details('modbus_torque_unit');
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
                //$row['final_fasten_torque'] = "0.0275";

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

    protected function linuxNowOrPhp(): string{

            $ts = date('YmdHis');
            if (PHP_OS_FAMILY === 'Linux') {
                $out = @shell_exec("date '+%Y%m%d%H%M%S' 2>/dev/null");
                $out = is_string($out) ? trim($out) : '';
                if (preg_match('/^\d{14}$/', $out)) $ts = $out;
            }
            return $ts;
        }

    private function normalizeHeader(string $h): string{
            // 將全形空白、非斷行空白都轉成一般空白
            $h = preg_replace('/[\x{00A0}\x{3000}]/u', ' ', $h);

            return strtolower(
                preg_replace('/\s+/', '_', trim($h))
            );
        }

    public function qa_check(){

            $type = 'ALL';
            
            // 同步控制器資料庫（ntcs_data.db）至 iDAS
            $this->ntcs_data_db_sysnc();

            
         
            

            $isMobile = $this->isMobileCheck();
            $decimals_arr = $this->MiscellaneousModel->details("decimals");
            // 取得當前年份
            if (PHP_OS_FAMILY === 'Linux') {
                $db_path = IDAS_PATH_DATABASE_ROOT . "/data".date('Y').".db";

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

    protected function respondError(string $type, string $msg){
            
            if (isset($this->MiscellaneousModel) && method_exists($this->MiscellaneousModel, 'generateErrorResponse')) {
                return $this->MiscellaneousModel->generateErrorResponse($type, $msg);
            }
            if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['res_type'=>$type, 'res_msg'=>$msg], JSON_UNESCAPED_UNICODE);
            return null;
        }

    private function torqueUnitFromModbus($code): string{
            // 來源統一用 MiscellaneousModel
            $unitMap = $this->MiscellaneousModel->details('modbus_torque_unit');

            // 防呆
            if (!is_array($unitMap)) {
                return (string)$code;
            }

            $key = $unitMap[(int)$code] ?? '';

            if ($key === '') {
                return (string)$code;
            }

            // 套語系顯示
            return $this->unitText($key);
        }

    public function unitText(string $unitKey, ?string $lang = null): string
        {
            // language
            $lang = strtolower(trim(
                $lang
                ?? ($_SESSION['language'] ?? $_COOKIE['language'] ?? 'zh-tw')
            ));
            $lang = ($lang === 'en') ? 'en-us' : (($lang === 'zh') ? 'zh-tw' : $lang);
            if (!in_array($lang, ['en-us','zh-tw','zh-cn'], true)) {
                $lang = 'en-us';
            }

            if ($unitKey === '') return '';

            // normalize unit key
            $key = strtolower(trim($unitKey));
            $key = str_replace(['·','*','-','/','／'], '.', $key);
            $key = preg_replace('/\.+/', '.', $key);

            // i18n map
            static $MAP = [
                'n.m'    => ['en-us'=>'N.m',   'zh-tw'=>'N.m',   'zh-cn'=>'N.m'],
                'cn.m'   => ['en-us'=>'cN.m',  'zh-tw'=>'cN.m',  'zh-cn'=>'cN.m'],
                'kgf.m'  => ['en-us'=>'kgf.m', 'zh-tw'=>'kgf.m', 'zh-cn'=>'kgf.m'],
                'kgf.cm' => ['en-us'=>'kgf.cm','zh-tw'=>'kgf.cm','zh-cn'=>'kgf.cm'],
                'lbf.in' => ['en-us'=>'lbf.in','zh-tw'=>'lbf.in','zh-cn'=>'lbf.in'],
            ];

            if (isset($MAP[$key])) {
                return $MAP[$key][$lang];
            }

            // fallback: dot → ·
            return str_replace('.', '·', $unitKey);
        }

    private $DataModel;
    private $MiscellaneousModel;
    private $ToolModel;
    private $SettingModel;
    Private $deviceId;

    // 在建構子中將 Post 物件（Model）實例化

    public function __construct(...$args)
    {
        if (idas_is_icontroller()) {
            $this->__construct__icontroller(...$args);
            return;
        }
        $this->__construct__ntcs(...$args);
    }

    private function __construct__icontroller()
    {
        $this->DataModel = $this->model('Datas');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->ToolModel = $this->model('Tool');
        $this->SettingModel = $this->model('Setting');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 

    }

    private function __construct__ntcs()
    {
        $this->DataModel = $this->model('Datas');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->ToolModel = $this->model('Tool');
        $this->SettingModel = $this->model('Setting');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 

        // 給前端 JS 使用的 Operator 下載限制旗標（非敏感資料）。
        // 目的：Data 主頁、扭力折線圖頁面的匯出/下載按鈕都能立即被前端擋下。
        $this->setOperatorDownloadClientFlag();

    }

    private function getCurrentUserLaw(): ?int{

        $keys = ['user_law', 'law', 'userLaw', 'user_level', 'permission', 'role_law'];
        $session = $_SESSION ?? [];

        foreach ($keys as $key) {
            if (isset($session[$key]) && is_numeric($session[$key])) {
                return (int)$session[$key];
            }
        }

        // 只有在沒有明確 SESSION 身分時，才使用 cookie 當 fallback。
        $hasSessionIdentity = isset($session['privilege']) || isset($session['username']) || isset($session['user']) || isset($session['account']);
        if ($hasSessionIdentity) {
            return null;
        }

        foreach ($keys as $key) {
            if (isset($_COOKIE[$key]) && is_numeric($_COOKIE[$key])) {
                return (int)$_COOKIE[$key];
            }
        }

        return null;
    }

    private function isOperatorLogin(): bool{

        $session = $_SESSION ?? [];
        $privilege = strtolower(trim((string)($session['privilege'] ?? '')));

        // 目前登入者若明確是 admin / guest，優先視為非 Operator，避免舊 cookie 殘留造成誤判。
        if (in_array($privilege, ['admin', 'administrator', 'guest'], true)) {
            return false;
        }
        if ($privilege === 'operator') {
            return true;
        }

        $law = $this->getCurrentUserLaw();
        if ($law === 3) {
            return true;
        }
        if ($law !== null) {
            return false;
        }

        // 備援：部分舊版可能只存角色名稱，避免未帶 law 時被繞過。
        $roleKeys = ['role', 'user_role', 'account_role', 'permission_name'];
        foreach ($roleKeys as $key) {
            if (isset($session[$key])) {
                $role = strtolower(trim((string)$session[$key]));
                if ($role === 'operator') {
                    return true;
                }
                if (in_array($role, ['admin', 'administrator', 'guest'], true)) {
                    return false;
                }
            }
        }

        // 只有在沒有明確 SESSION 身分時，才使用 cookie 當 fallback。
        $hasSessionIdentity = isset($session['privilege']) || isset($session['username']) || isset($session['user']) || isset($session['account']);
        if ($hasSessionIdentity) {
            return false;
        }

        foreach ($roleKeys as $key) {
            if (isset($_COOKIE[$key])) {
                $role = strtolower(trim((string)$_COOKIE[$key]));
                if ($role === 'operator') {
                    return true;
                }
            }
        }

        return false;
    }

    private function setOperatorDownloadClientFlag(): void{

        if (headers_sent()) {
            return;
        }

        $isBlocked = $this->isOperatorLogin();
        $value     = $isBlocked ? '1' : '';
        $expires   = $isBlocked ? (time() + 43200) : (time() - 3600);

        // 同時寫入根路徑與 /idas/public，避免不同頁面路徑讀不到。
        @setcookie('ntcs_operator_download_block', $value, $expires, '/');
        @setcookie('ntcs_operator_download_block', $value, $expires, '/idas/public');
    }

    private function getOperatorDownloadDeniedMessage(): string{

        $lang = strtolower(trim((string)($_SESSION['language'] ?? $_COOKIE['language'] ?? 'zh-tw')));

        if ($lang === 'zh-cn') {
            return 'Operator 权限不允许下载或汇出档案。';
        }

        if ($lang === 'en-us' || $lang === 'en') {
            return 'Operator permission is not allowed to download or export files.';
        }

        return 'Operator 權限不允許下載或匯出檔案。';
    }

    private function denyDownloadIfOperator(): void{

        if (!$this->isOperatorLogin()) {
            return;
        }

        while (ob_get_level()) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo json_encode([
            'success'  => false,
            'res_type' => 'Error',
            'res_code' => 'OPERATOR_DOWNLOAD_DENIED',
            'res_msg'  => $this->getOperatorDownloadDeniedMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function exportData(...$args)
    {
        if (idas_is_icontroller()) {
            return $this->exportData__icontroller(...$args);
        }
        return $this->exportData__ntcs(...$args);
    }

    private function exportData__icontroller(){
        
        /* =====================================================
        * Load language
        * ===================================================== */
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        /* =====================================================
        * Input check
        * ===================================================== */
        if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
            echo json_encode(["error" => "輸入參數不正確"]);
            exit;
        }

        $start_date = $_POST['start_date'] . ':00';
        $end_date   = $_POST['end_date'] . ':00';
        $expert_val = $_POST['expert_val'] ?? '0';

        /* =====================================================
        * Fetch data
        * ===================================================== */
        $dataset = $this->DataModel->get_range_data($start_date, $end_date);
        if (empty($dataset)) {
            echo json_encode(["error" => "無法找到符合條件的資料"]);
            exit;
        }

        $dataset = array_slice($dataset, 0, 10000);

        /* =====================================================
        * Headers
        * ===================================================== */
        $csv_headers      = array_keys($dataset[0]);
        $csv_headers_temp = DataExportService::localizedHeaders($csv_headers, is_array($text ?? null) ? $text : []);

        /* =====================================================
        * Filename
        * ===================================================== */
        $controller_info = $this->SettingModel->GetControllerInfo();
        $device_sn_safe  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $controller_info['device_sn'] ?? 'UNKNOWN');
        $timestamp = trim(shell_exec('date +%Y%m%d%H%M%S'));

        $csv_filename = "data_{$device_sn_safe}_{$timestamp}.csv";
        $zip_filename = "data_{$device_sn_safe}_{$timestamp}.zip";

        /* =====================================================
        * Mapping
        * ===================================================== */
        // Torque unit (MODBUS)
        $unitMap = $this->MiscellaneousModel->details('modbus_torque_unit');
        // Status code
        $status_arr = $this->MiscellaneousModel->details('status');
        $status_arr = is_array($status_arr) ? $status_arr : [];

        /* =====================================================
        * Clear output buffer
        * ===================================================== */
        while (ob_get_level()) {
            ob_end_clean();
        }

        /* =====================================================
        * CSV DIRECT DOWNLOAD
        * ===================================================== */
        if ($expert_val === '0') {

            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename={$csv_filename}");

            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel

            // header row
            fputcsv($out, $csv_headers_temp);

            foreach ($dataset as $row) {
                $ordered = DataExportService::orderedRow(
                    $row, $csv_headers, is_array($unitMap) ? $unitMap : [], $status_arr,
                    function ($unitKey) { return $this->unitText($unitKey); }
                );
                fputcsv($out, $ordered);
            }

            fclose($out);
            exit;
        }

        /* =====================================================
        * ZIP (CSV inside)
        * ===================================================== */
        if ($expert_val === '1') {

            $fh = fopen('php://temp', 'w+');
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, $csv_headers_temp);

            foreach ($dataset as $row) {
                $ordered = DataExportService::orderedRow(
                    $row, $csv_headers, is_array($unitMap) ? $unitMap : [], $status_arr,
                    function ($unitKey) { return $this->unitText($unitKey); }
                );
                fputcsv($fh, $ordered);
            }

            rewind($fh);
            $csv_content = stream_get_contents($fh);
            fclose($fh);

            $zip = new ZipArchive();
            $tmp = tempnam(sys_get_temp_dir(), 'ntcs_') . '.zip';

            if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                echo json_encode(["error" => "無法建立 ZIP"]);
                exit;
            }

            $zip->addFromString($csv_filename, $csv_content);
            $zip->close();

            header('Content-Type: application/zip');
            header("Content-Disposition: attachment; filename={$zip_filename}");
            header('Content-Length: ' . filesize($tmp));

            readfile($tmp);
            @unlink($tmp);
            exit;
        }

        echo json_encode(["error" => "未知的匯出格式"]);
        exit;
    }

    private function exportData__ntcs(){
        
        /* =====================================================
        * Load language
        * ===================================================== */
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }

        // Operator 權限不可下載/匯出檔案（後端防繞過）。
        $this->denyDownloadIfOperator();

        /* =====================================================
        * Input check
        * ===================================================== */
        if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
            echo json_encode(["error" => "輸入參數不正確"]);
            exit;
        }

        $start_date = $_POST['start_date'] . ':00';
        $end_date   = $_POST['end_date'] . ':00';
        $expert_val = $_POST['expert_val'] ?? '0';

        /* =====================================================
        * Fetch data
        * ===================================================== */
        $dataset = $this->DataModel->get_range_data($start_date, $end_date);
        if (empty($dataset)) {
            echo json_encode(["error" => "無法找到符合條件的資料"]);
            exit;
        }

        $dataset = array_slice($dataset, 0, 10000);

        /* =====================================================
        * Headers
        * ===================================================== */
        $csv_headers      = array_keys($dataset[0]);
        $csv_headers_temp = DataExportService::localizedHeaders($csv_headers, is_array($text ?? null) ? $text : []);

        /* =====================================================
        * Filename
        * ===================================================== */
        $controller_info = $this->SettingModel->GetControllerInfo();
        $device_sn_safe  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $controller_info['device_sn'] ?? 'UNKNOWN');
        $timestamp = trim(shell_exec('date +%Y%m%d%H%M%S'));

        $csv_filename = "data_{$device_sn_safe}_{$timestamp}.csv";
        $zip_filename = "data_{$device_sn_safe}_{$timestamp}.zip";

        /* =====================================================
        * Mapping
        * ===================================================== */
        // Torque unit (MODBUS)
        $unitMap = $this->MiscellaneousModel->details('modbus_torque_unit');
        // Status code
        $status_arr = $this->MiscellaneousModel->details('status');
        $status_arr = is_array($status_arr) ? $status_arr : [];

        /* =====================================================
        * Clear output buffer
        * ===================================================== */
        while (ob_get_level()) {
            ob_end_clean();
        }

        /* =====================================================
        * CSV DIRECT DOWNLOAD
        * ===================================================== */
        if ($expert_val === '0') {

            header('Content-Type: text/csv; charset=utf-8');
            header("Content-Disposition: attachment; filename={$csv_filename}");

            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel

            // header row
            fputcsv($out, $csv_headers_temp);

            foreach ($dataset as $row) {
                $ordered = DataExportService::orderedRow(
                    $row, $csv_headers, is_array($unitMap) ? $unitMap : [], $status_arr,
                    function ($unitKey) { return $this->unitText($unitKey); }
                );
                fputcsv($out, $ordered);
            }

            fclose($out);
            exit;
        }

        /* =====================================================
        * ZIP (CSV inside)
        * ===================================================== */
        if ($expert_val === '1') {

            $fh = fopen('php://temp', 'w+');
            fwrite($fh, "\xEF\xBB\xBF");
            fputcsv($fh, $csv_headers_temp);

            foreach ($dataset as $row) {
                $ordered = DataExportService::orderedRow(
                    $row, $csv_headers, is_array($unitMap) ? $unitMap : [], $status_arr,
                    function ($unitKey) { return $this->unitText($unitKey); }
                );
                fputcsv($fh, $ordered);
            }

            rewind($fh);
            $csv_content = stream_get_contents($fh);
            fclose($fh);

            $zip = new ZipArchive();
            $tmp = tempnam(sys_get_temp_dir(), 'ntcs_') . '.zip';

            if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                echo json_encode(["error" => "無法建立 ZIP"]);
                exit;
            }

            $zip->addFromString($csv_filename, $csv_content);
            $zip->close();

            header('Content-Type: application/zip');
            header("Content-Disposition: attachment; filename={$zip_filename}");
            header('Content-Length: ' . filesize($tmp));

            readfile($tmp);
            @unlink($tmp);
            exit;
        }

        echo json_encode(["error" => "未知的匯出格式"]);
        exit;
    }

    public function AuditLog(){

        $isMobile = $this->isMobileCheck();

        $data = array(
            'isMobile' => $isMobile
        );

        $this->view('data/audit_log', $data);
    }

    public function download_file(...$args)
    {
        if (idas_is_icontroller()) {
            return $this->download_file__icontroller(...$args);
        }
        return $this->download_file__ntcs(...$args);
    }

    private function download_file__icontroller() {

        // 僅支援 Linux
        if (PHP_OS_FAMILY !== 'Linux') {
            return $this->respondError('Error', '只支援在 Linux 環境下下載 CSV 壓縮包。');
        }

        $dir = IDAS_PATH_RAMDISK_FTP;
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

    private function download_file__ntcs() {

        // Operator 權限不可下載曲線圖 ZIP（後端防繞過）。
        $this->denyDownloadIfOperator();

        // 僅支援 Linux
        if (PHP_OS_FAMILY !== 'Linux') {
            return $this->respondError('Error', '只支援在 Linux 環境下下載 CSV 壓縮包。');
        }

        $dir = IDAS_PATH_RAMDISK_FTP;
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

    private function normalizeDrawLineRows(array $rows, array $decimals_arr, array $color_arr): array {

        foreach ($rows as $i => &$row) {
            $status = (int)($row['fasten_status'] ?? 0);

            if ($status === 5) {
                $row['row_color'] = $color_arr['okseqcolor_text'] ?? 'status-ok';
            } elseif ($status === 6) {
                $row['row_color'] = $color_arr['okjobcolor_text'] ?? 'status-ok';
            } elseif ($status === 4) {
                $row['row_color'] = 'status-ok';
            } else {
                $row['row_color'] = 'status-ng';
            }

            $torque_raw = $row['final_fasten_torque'] ?? 0;
            $torque_raw = is_numeric($torque_raw) ? (float)$torque_raw : (float)str_replace(',', '', (string)$torque_raw);

            $torque_unit = (int)($row['torque_unit'] ?? 1);
            $precision   = $decimals_arr[$torque_unit] ?? 3;

            $row['final_fasten_torque_raw'] = $torque_raw;
            $row['final_fasten_torque']     = number_format($torque_raw, (int)$precision, '.', '');

            // NO 顯示資料庫主鍵編號。
            // ntcs_data.db 的主鍵欄位是 id；舊版/備援資料可能只有 sn/rid/rowid。
            $dbNo = $row['id'] ?? ($row['sn'] ?? ($row['rid'] ?? ($row['rowid'] ?? ($i + 1))));
            $row['chart_index'] = $dbNo;
            $row['chart_label'] = !empty($row['data_time']) ? (string)$row['data_time'] : (string)$dbNo;
        }
        unset($row);

        return $rows;
    }

    private function getDrawLineUnitLabel(array $rows, array $unit_arr): string {

        if (empty($rows)) {
            return '';
        }

        $last = end($rows);
        $code = (int)($last['torque_unit'] ?? 1);
        $unitKey = $unit_arr[$code] ?? '';

        if ($unitKey === '') {
            return '';
        }

        return $this->unitText($unitKey);
    }

    public function drawLineChart(){

        // 同步控制器資料庫（ntcs_data.db）至 iDAS
        $this->ntcs_data_db_sysnc();

        $isMobile     = $this->isMobileCheck();
        $decimals_arr = $this->MiscellaneousModel->details('decimals');
        $unit_arr     = $this->MiscellaneousModel->details('modbus_torque_unit');
        $status_arr   = $this->MiscellaneousModel->details('status');
        $color_arr    = $this->get_color_type();

        $db_exists = true;
        $db_path   = '';

        if (PHP_OS_FAMILY === 'Linux') {
            $db_path = IDAS_PATH_DATABASE_ROOT . '/data' . date('Y') . '.db';
            $db_exists = file_exists($db_path);
        }

        if ($db_exists && method_exists($this->DataModel, 'getLineChartData')) {
            $res_data     = $this->DataModel->getLineChartData('ALL', 25);
            $res_data_ok  = $this->DataModel->getLineChartData('OK', 25);
            $res_data_nok = $this->DataModel->getLineChartData('NOK', 25);
        } else {
            $res_data     = [];
            $res_data_ok  = [];
            $res_data_nok = [];
        }

        $res_data     = $this->normalizeDrawLineRows($res_data, $decimals_arr, $color_arr);
        $res_data_ok  = $this->normalizeDrawLineRows($res_data_ok, $decimals_arr, $color_arr);
        $res_data_nok = $this->normalizeDrawLineRows($res_data_nok, $decimals_arr, $color_arr);

        $data = array(
            'isMobile'         => $isMobile,
            'res_data'         => $res_data,
            'res_data_ok'      => $res_data_ok,
            'res_data_nok'     => $res_data_nok,
            'unit_arr'         => $unit_arr,
            'status_arr'       => $status_arr,
            'db_exists'        => $db_exists,
            'db_path'          => $db_path,
            'color_arr'        => $color_arr,
            'chart_limit'      => 25,
            'chart_unit_label' => $this->getDrawLineUnitLabel($res_data, $unit_arr)
        );

        $this->view('data/drawline_chart_index', $data);
    }

    public function export_drawline_chart_csv(){

        // Operator 權限不可匯出折線圖 CSV（後端防繞過）。
        $this->denyDownloadIfOperator();

        $start_date = trim((string)($_POST['start_date'] ?? $_GET['start_date'] ?? ''));
        $end_date   = trim((string)($_POST['end_date']   ?? $_GET['end_date']   ?? ''));

        // 前端會送 YYYY-MM-DD HH:MM:SS；這裡再做一次格式防呆。
        $datePattern = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';
        if (!preg_match($datePattern, $start_date) || !preg_match($datePattern, $end_date)) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success'  => false,
                'res_type' => 'Error',
                'res_msg'  => 'Invalid date range.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $this->ntcs_data_db_sysnc();
        } catch (Throwable $e) {
            // 同步失敗不直接中斷，仍嘗試匯出目前 iDAS DB 內的資料。
        }

        if (method_exists($this->DataModel, 'getLineChartCsvExportData')) {
            $dataset = $this->DataModel->getLineChartCsvExportData($start_date, $end_date, 25);
        } else {
            $dataset = [];
        }

        if (empty($dataset)) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success'  => false,
                'res_type' => 'Info',
                'res_msg'  => 'No data to export.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $controller_info = $this->SettingModel->GetControllerInfo();
        $device_sn_safe  = preg_replace('/[^A-Za-z0-9_\-]/', '_', $controller_info['device_sn'] ?? 'UNKNOWN');

        $timestamp = trim((string)@shell_exec('date +%Y%m%d%H%M%S 2>/dev/null'));
        if (!preg_match('/^\d{14}$/', $timestamp)) {
            $timestamp = date('YmdHis');
        }

        $csv_filename = "25_data_{$device_sn_safe}_{$timestamp}.csv";

        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $csv_filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM for Excel

        $csv_headers = array_keys($dataset[0]);
        fputcsv($out, $csv_headers);

        foreach ($dataset as $row) {
            $ordered = [];
            foreach ($csv_headers as $key) {
                $ordered[] = $row[$key] ?? '';
            }
            fputcsv($out, $ordered);
        }

        fclose($out);
        exit;
    }

    public function get_drawline_chart_data(){

        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        $mode  = $_POST['mode']  ?? $_GET['mode']  ?? 'ALL';
        $limit = $_POST['limit'] ?? $_GET['limit'] ?? 25;

        $mode = strtoupper(trim((string)$mode));
        if (!in_array($mode, ['ALL', 'OK', 'NG'], true)) {
            $mode = 'ALL';
        }

        $limit = (int)$limit;
        if ($limit <= 0) {
            $limit = 25;
        }
        if ($limit > 100) {
            $limit = 100;
        }

        try {
            $this->ntcs_data_db_sysnc();
        } catch (Throwable $e) {
            // 同步失敗不直接中斷，仍嘗試讀取 iDAS 目前資料。
        }

        $decimals_arr = $this->MiscellaneousModel->details('decimals');
        $unit_arr     = $this->MiscellaneousModel->details('modbus_torque_unit');
        $status_arr   = $this->MiscellaneousModel->details('status');
        $color_arr    = $this->get_color_type();

        if (method_exists($this->DataModel, 'getLineChartData')) {
            $rows = $this->DataModel->getLineChartData($mode, $limit);
        } else {
            // 舊版 fallback：避免 model 尚未更新時前端完全無資料。
            $rows = array_reverse(array_slice($this->DataModel->getData($mode), 0, $limit));
        }

        $rows = $this->normalizeDrawLineRows($rows, $decimals_arr, $color_arr);
        $unitLabel = $this->getDrawLineUnitLabel($rows, $unit_arr);

        echo json_encode([
            'success'          => true,
            'mode'             => $mode,
            'limit'            => $limit,
            'count'            => count($rows),
            'records'          => $rows,
            'unit_arr'         => $unit_arr,
            'status_arr'       => $status_arr,
            'color_arr'        => $color_arr,
            'chart_unit_label' => $unitLabel,
            'chart'            => [
                'labels' => array_map(function($row) {
                    return $row['chart_label'] ?? '';
                }, $rows),
                'torque' => array_map(function($row) {
                    return (float)($row['final_fasten_torque_raw'] ?? 0);
                }, $rows)
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

