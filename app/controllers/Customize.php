<?php
/*
 * Single-codebase platform switch.
 * /home/kls/upgrade/icontroller = 1 -> i-controller implementation
 * 0 / missing / invalid -> NTCS implementation
 */
if (idas_is_icontroller()) {
class Customize extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    Private $deviceId;


    public function __construct(){

        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();

    }

    // 取得所有Jobs
    public function index(){

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }
        
        $isMobile = $this->isMobileCheck();
        $job_list = $this->SettingModel->get_job_list();
        $data_button = $this->MiscellaneousModel->details('customize');
   
        //判斷 csv 是否存在
        $data_csv = $this->load_customize_csv_arrays();

        $data = [
            'isMobile' => $isMobile,
            'job_list' => $job_list,
            'data_button' => $data_button,
            'data_csv' => $data_csv,
            'text' => isset($text) ? $text : []
        ];
        $this->view('customize/index', $data);
    }



    /**
     * 讀取 /var/www/html/temp/customize.csv
     * 回傳：
     *   [
     *     'no'             => [1, 2, ...],             // int[]
     *     'read_position'  => ['#26 fasten_status', ...], // string[]
     *     'input_position' => [1000, 2000, ...],       // float[]（非數字則為 null）
     *     'result'         => ['OK', '12.3', ...]      // string[]
     *   ]
     * 失敗（檔案不存在/不可讀/表頭不符）則回傳 null
     */
    public function load_customize_csv_arrays(string $file = '/var/www/html/temp/customize.csv'): ?array{

        if (!is_file($file) || !is_readable($file)) return null;
        $fh = @fopen($file, 'r');
        if (!$fh) return null;

        // 處理 UTF-8 BOM
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($fh);

        // 讀表頭
        $headers = fgetcsv($fh);
        if ($headers === false || !is_array($headers)) { fclose($fh); return null; }

        // 表頭正規化（大小寫不敏感、空白→底線、去除非 \w）
        $norm = static function($s){
            $s = strtolower(trim($s ?? ''));
            $s = preg_replace('/\s+/', '_', $s);
            return preg_replace('/[^\w]/', '', $s);
        };
        $hmap = [];
        foreach ($headers as $i => $h) $hmap[$norm($h)] = $i;

        // 需求欄位索引
        $idxNO  = $hmap['no']             ?? null;
        $idxRP  = $hmap['read_position']  ?? null;
        $idxIP  = $hmap['input_position'] ?? null;
        $idxRES = $hmap['result']         ?? null;
        if ($idxNO===null || $idxRP===null || $idxRES===null) {
            fclose($fh);
            return null;
        }

        $res = [
            'no'             => [],
            'read_position'  => [],
            'input_position' => [],
            'result'         => [],
        ];

        while (($row = fgetcsv($fh)) !== false) {
            if (!is_array($row)) continue;

            // 跳過全空列
            $allEmpty = true;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') { $allEmpty = false; break; }
            }
            if ($allEmpty) continue;

            // 取值並轉型
            $no  = (int)trim((string)($row[$idxNO]  ?? ''));
            $rp  = trim((string)($row[$idxRP]  ?? ''));
            $ipS = ($idxIP === null) ? '' : trim((string)($row[$idxIP] ?? ''));
            $re  = trim((string)($row[$idxRES] ?? ''));

            $ip  = ($ipS === '' ? null : (is_numeric($ipS) ? (float)$ipS : null));

            $res['no'][]             = $no;
            $res['read_position'][]  = $rp;
            $res['input_position'][] = $ip;
            $res['result'][]         = $re;
        }
        fclose($fh);

        // 全部都空就算失敗
        if (empty($res['no']) && empty($res['read_position']) && empty($res['input_position']) && empty($res['result'])) {
            return null;
        }
        return $res;
    }



    /**
     * POST /?url=Customize/save_positions
     * 前端 payload: { rows: [{read_pos, input_pos, result}, ...] }
     * 寫入 CSV 至 /var/www/html/temp，檔名：customize_<SN>_YYYYMMDDhhmmss[_n].csv
     * 回傳 JSON：{res_type, res_msg, affected, filename, download_url}
     */
   
    public function save_positions(){

        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');

        // 同時支援 JSON 與 x-www-form-urlencoded
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST; // 舊式表單也吃
        }

        $rows  = $data['rows'] ?? [];
        $clear = !empty($data['clear']); // 前端會傳 1

        // 相容舊版前端：三欄都空也視為清空
        $compatAllEmpty = empty($rows)
                    && empty($data['no'])
                    && empty($data['read_position'])
                    && empty($data['input_position']);

        // --- i18n 訊息 ---
        $lang = strtolower($_SESSION['language'] ?? 'en-us');
        $M = [
            'en-us' => ['invalid'=>'Invalid payload.','empty'=>'No rows to save.','ok'=>'Saved successfully.','server'=>'Server error.'],
            'zh-tw' => ['invalid'=>'傳入資料格式不正確。','empty'=>'沒有可儲存的資料。','ok'=>'已儲存成功。','server'=>'伺服器錯誤。'],
            'zh-cn' => ['invalid'=>'传入数据格式不正确。','empty'=>'没有可保存的数据。','ok'=>'保存成功。','server'=>'服务器错误。'],
        ];
        $msg = $M[$lang] ?? $M['en-us'];

        // === 空設定：清空伺服端資料 ===
        if ($clear || $compatAllEmpty) {
            $csvFile = '/var/www/html/temp/customize.csv';
            $ok = true;

            if (is_file($csvFile)) {
                $ok = @unlink($csvFile);
                if (!$ok) { // 刪不掉就截斷
                    $fp = @fopen($csvFile, 'w');
                    if ($fp) { fclose($fp); $ok = true; }
                }
            }

            echo json_encode([
                'res_type' => $ok ? 'OK' : 'Error',
                'res_msg'  => $ok ? ($msg['ok'] ?? 'OK') : ($msg['server'] ?? 'Server error'),
                'rows'     => []   // 前端 success 內會用到 resp.rows
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // === 檢查 payload ===
        if (!is_array($rows)) {
            http_response_code(400);
            echo json_encode(['res_type'=>'Error','res_msg'=>$msg['invalid']], JSON_UNESCAPED_UNICODE);
            return;
        }

        // === 清理資料 + 計算每列 $final（前端寫回 result-<row_id>）===
        $rowsForCsv  = [];
        $rowsForResp = [];
        $columnsWL   = self::ntcsColumns(); // 欄位白名單

        foreach ($rows as $r) {
            if (!is_array($r)) continue;

            $row_id = isset($r['row_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$r['row_id']) : '';

            // 來源資訊（db / modbus）
            $read_src       = isset($r['read_src'])       ? strtolower(trim((string)$r['read_src']))  : '';
            $read_db_index  = isset($r['read_db_index'])  ? trim((string)$r['read_db_index'])         : '';
            $read_db_name   = isset($r['read_db_name'])   ? trim((string)$r['read_db_name'])          : '';

            // Modbus/手動欄位
            $read   = isset($r['read_pos'])  ? trim((string)$r['read_pos'])  : '';
            $input  = isset($r['input_pos']) ? trim((string)$r['input_pos']) : '';
            $result = isset($r['result'])    ? strtoupper(trim((string)$r['result'])) : '';
            if (!in_array($result, ['', 'OK', 'NG'], true)) $result = '';

            // 長度限制
            $read         = mb_substr($read,  0, 128, 'UTF-8');
            $input        = mb_substr($input, 0, 128, 'UTF-8');
            $read_db_name = mb_substr($read_db_name, 0, 128, 'UTF-8');

            // 顯示於 CSV 的讀取欄位文字
            $csvReadText = '';
            if ($read_src === 'db' && $read_db_index !== '') {
                $idx = (int)$read_db_index;
                $col = $columnsWL[$idx] ?? '';
                $tag = '#'.$idx;
                $csvReadText = $col ? ($tag.' '.$col) : $tag;
            } else {
                $csvReadText = $read;
            }

            // === 計算 $final：分流 ===
            $final = '';

            if ($read_src === 'db' && $read_db_index !== '') {
                // ===== DB 讀取模式：改用 get_operation_info() 取最新一筆 =====
                $idx = (int)$read_db_index;
                $col = $columnsWL[$idx] ?? null;

                if ($col && preg_match('/^\w+$/', $col)) {
                    try {
                        $lastRow = $this->DataModel->get_operation_info();  // 取最後一筆
                        if (is_array($lastRow) && array_key_exists($col, $lastRow)) {
                            $val = $lastRow[$col];
                            if (is_array($val))        $final = implode(',', array_map('strval', $val));
                            elseif ($val === null)     $final = '';
                            elseif (is_scalar($val))   $final = (string)$val;
                            else                       $final = json_encode($val, JSON_UNESCAPED_UNICODE);
                        } else {
                            $final = '';
                        }
                    } catch (\Throwable $e) {
                        $final = '';
                    }
                } else {
                    $final = ''; // 白名單沒有或欄名不合法
                }

            } else {
                // ===== Modbus 讀取模式（維持原規則） =====
                if ($read !== '' && preg_match('/^\d+$/', $read)){
                    $readPos = (int)$read;
                    try {
                        $bytes = 1;
                        if (method_exists($this, 'RegMap')) {
                            $bytes = (int)$this->RegMap($readPos);
                            if ($bytes < 1) $bytes = 1;
                        }
                        $val = $this->get_modbus_api($readPos, $bytes);

                        /* 先除以 1000 的欄位 */
                        if (in_array($readPos, [4170,4171,4155,4156,4172,4173,4174,4175,4182,4183,4184,4185,4242,4243,4246,4247,4250,4251,4254,4255,4258,4259], true)) {
                            if (is_numeric($val)) {
                                $val = $val / 1000;
                            } elseif (is_array($val)) {
                                $val = array_map(static function($x){
                                    return is_numeric($x) ? ($x / 1000) : $x;
                                }, $val);
                            }
                        }

                        /* 移除尾端 0 / 近零 */
                        if (is_array($val)) {
                            while (!empty($val) && is_numeric(end($val)) && (float)end($val) == 0.0) {
                                array_pop($val);
                            }
                            if (count($val) === 0)       { $val = 0; }
                            elseif (count($val) === 1)   { $val = $val[0]; }
                        }
                        $TAIL_ZERO_EPS = 1e-3;
                        if (is_array($val)) {
                            while (!empty($val)) {
                                $last = end($val);
                                $isZeroish = is_numeric($last) && abs((float)$last) <= $TAIL_ZERO_EPS;
                                if ($isZeroish) array_pop($val);
                                else break;
                            }
                            if (count($val) === 0)       { $val = 0; }
                            elseif (count($val) === 1)   { $val = $val[0]; }
                        }

                        if (is_array($val))        $final = implode(',', array_map('strval', $val));
                        elseif ($val === null)     $final = '';
                        elseif (is_scalar($val))   $final = (string)$val;
                        else                       $final = json_encode($val, JSON_UNESCAPED_UNICODE);
                    } catch (\Throwable $e) {
                        $final = '';
                    }
                }
            }

            // 收集要寫入 CSV 的行（只留 NO / Read Position / result）
            if (!($csvReadText === '' && $input === '' && $result === '')) {
                $rowsForCsv[] = [
                    'read_pos' => $csvReadText,
                    'result'   => $final,   // 寫出計算後的結果
                ];
            }

            // 回寫前端結果欄
            if ($row_id !== '') {
                $rowsForResp[] = [
                    'row_id'    => $row_id,
                    'result_id' => 'result-' . $row_id,
                    'result'    => $final,
                ];
            }
        }

        if (empty($rowsForCsv)) {
            echo json_encode([
                'res_type' => 'OK',
                'res_msg'  => $msg['empty'],
                'affected' => 0,
                'rows'     => $rowsForResp,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // --- 取得裝置序號（可選） ---
        $deviceSn = 'UnknownSN';
        try {
            $info = $this->SettingModel->GetControllerInfo();
            if (!empty($info['device_sn'])) {
                $deviceSn = preg_replace('/[^\w\-]+/', '', (string)$info['device_sn']);
            }
        } catch (\Throwable $e) { /* 忽略 */ }

        // --- 固定 Linux 路徑與對外 URL ---
        $baseDir = '/var/www/html/temp';
        $baseUrl = '/temp';

        $dirCheck = self::ensureExportDir($baseDir, 0777, 'www-data', 'www-data');
        if (!$dirCheck['ok']) {
            http_response_code(500);
            echo json_encode([
                'res_type'=>'Error',
                'res_msg' => $msg['server'],
                'detail'  => $dirCheck['error'],
                'rows'    => $rowsForResp,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // --- 固定檔名；每次覆蓋 ---
        $filename = "customize.csv";
        $fullpath = rtrim($baseDir, '/\\') . '/' . $filename;
        $tmpPath  = $fullpath . '.tmp';

        try {
            $fp = @fopen($tmpPath, 'w');
            if (!$fp) {
                http_response_code(500);
                echo json_encode([
                    'res_type'=>'Error',
                    'res_msg' =>$msg['server'],
                    'detail'  =>'fopen tmp failed',
                    'rows'    => $rowsForResp,
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            @flock($fp, LOCK_EX);

            // UTF-8 BOM（Excel 友善）
            fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // 表頭（已移除 Input Position）
            fputcsv($fp, ['NO', 'Read Position', 'result']);

            // 內容
            $i = 1;
            foreach ($rowsForCsv as $r) {
                fputcsv($fp, [$i++, $r['read_pos'], $r['result'] ?? '']);
            }

            @flock($fp, LOCK_UN);
            fclose($fp);
            @chmod($tmpPath, 0644);

            if (file_exists($fullpath)) { @unlink($fullpath); }
            if (!@rename($tmpPath, $fullpath)) {
                @unlink($tmpPath);
                http_response_code(500);
                echo json_encode([
                    'res_type'=>'Error',
                    'res_msg' =>$msg['server'],
                    'detail'  =>'rename failed',
                    'rows'    => $rowsForResp,
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $downloadUrl = rtrim($baseUrl, '/').'/'.$filename;

            echo json_encode([
                'res_type'     => 'OK',
                'res_msg'      => $msg['ok'],
                'affected'     => count($rowsForCsv),
                'filename'     => $filename,
                'download_url' => $downloadUrl,
                'rows'         => $rowsForResp,
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            if (isset($fp) && is_resource($fp)) { @flock($fp, LOCK_UN); fclose($fp); }
            @unlink($tmpPath);
            http_response_code(500);
            echo json_encode([
                'res_type'=>'Error',
                'res_msg'=>$msg['server'],
                'detail'=>$e->getMessage(),
                'rows'   => $rowsForResp,
            ], JSON_UNESCAPED_UNICODE);
        }
    }



    /**
     * ntcs_data 欄位白名單（依索引對應欄位）
     * 供 DB 模式以索引安全映射到實際欄位名
     */
    private static function ntcsColumns(): array {
        $columns = [
            "id","system_sn","data_time","device_type","device_id","device_sn",
            "tool_type","tool_sn","tool_status","job_id","job_name","sequence_id",
            "sequence_name","step_id","torque_unit","target_type","target_torque",
            "target_angle","target_time","fasten_time","final_fasten_torque",
            "final_fasten_angle","total_fasten_angle","count_type","last_screw_count",
            "total_screw_count","fasten_status","error_message","fasten_direction","rpm",
            "hi_torque","lo_torque","hi_angle","lo_angle","delay_ttime","threshold_torque",
            "threshold_angle","downshift_torque","downshift_angle","downshift_speed",
            "final_tool_voltage","final_tool_current","barcode",
        ];
        // ★ 與前端索引一致：43..52 = step1~step5 的 [torque, angle]
        for ($i = 1; $i <= 5; $i++) {
            $columns[] = "step{$i}_last_torque"; // 43,45,47,49,51
            $columns[] = "step{$i}_last_angle";  // 44,46,48,50,52
        }
        return $columns;
    }




    /**
     * 確保輸出資料夾存在且可寫；不存在則遞迴建立。
     * @return array{ok:bool,error?:string}
     */
    private static function ensureExportDir(string $dir, int $perm = 0777, string $user = 'www-data', string $group = 'www-data'): array{

        try {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, $perm, true) && !is_dir($dir)) {
                    return ['ok' => false, 'error' => 'mkdir failed'];
                }
            }
            @chmod($dir, $perm);
            @chown($dir, $user);
            @chgrp($dir, $group);

            // 可寫測試
            $test = rtrim($dir, '/\\') . '/.__wtest';
            if (@file_put_contents($test, '1') === false) {
                return ['ok' => false, 'error' => 'write test failed'];
            }
            @unlink($test);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }


    public function get_modbus_api(int $a, int $b = 1) {
        
        require_once '../app/config/config.php';

        // Modbus slave ID 合理範圍通常是 1~255
        $unitId = (int)$this->deviceId;
        if ($unitId < 1 || $unitId > 255) {
            $unitId = 1;
        }

        $startAddress = $a;
        $quantity = $b;  // 每個「暫存器」= 16-bit (= 2 bytes)

        try {
            // MODBUS TCP / OP 由 protocol_read_registers 自動切換。
            $words = $this->protocol_read_registers($unitId, $startAddress, $quantity);
            if (!is_array($words) || empty($words)) {
                return null;
            }

            // --- 規則：b > 3 時自動轉碼為 ASCII 字串 ---
            if ($quantity > 3) {
                // 以「每個 word 高位在前」打包 -> 字串，並去掉尾端 NUL
                // 備註：等同於 big-endian 內部位元組順序；若設備是小端，可改 'v*'
                $bin  = call_user_func_array('pack', array_merge(['n*'], $words));
                $text = rtrim($bin, "\0");
                // 可選：只保留可列印的 ASCII（避免雜訊）
                $text = preg_replace('/[^\x20-\x7E]/', '', $text);
                return $text;
            }

            // --- 其它情況維持原樣 ---
            if ($quantity == 1) {
                return $words[0] ?? null;   // 單一暫存器 -> 單值（例如 0x07E9 => 2025）
            }

            // b = 2 或 3 -> 回傳整個 16-bit 陣列
            return $words;

        } catch (\Throwable $e) {
            return null;
        }
    }


    public function RegMap($addr, $default = null){

        static $inited = false;
        static $map = [];      // 精確位址對應：addr => bytes（words）
        static $ranges = [];   // 連續區間： [beg, end, bytes]

        if (!$inited) {
            $inited = true;

            // 小工具：一次加入多個起點位址（每個起點固定回傳 size 個 16-bit words）
            $add = function (int $size, array $starts) use (&$map) {
                foreach ($starts as $a) {
                    $map[(int)$a] = $size;
                }
            };

            // 4.1 鎖附結果資訊
            $add(1, range(4096, 4101)); // FASTEN_YEAR..FASTEN_SEC（每點 1 word）

            // 控制器序號：4102~4111，任意起點都讀滿 10 words
            $add(10, range(4102, 4111));

            // 起子型號：4112~4121，任意起點都讀滿 10 words
            $add(10, range(4112, 4121));

            // 起子序號：4122~4131，任意起點都讀滿 10 words
            // （若現場不需要亦可移除，不影響其餘邏輯）
            $add(10, range(4122, 4131));

            // JOB_NAME：4132~4137，任意起點讀滿 6 words（確保 4132 與 4133 會不同）
            $add(6, range(4132, 4137));

            // SEQ_NAME：4138~4143，任意起點讀滿 6 words
            $add(6, range(4138, 4143));

            // 目標扭力：4170~4171，任意起點讀滿 2 words
            $add(2, range(4170, 4171));

            // 目標角度：4176~4177，任意起點讀滿 2 words
            $add(2, range(4176, 4177));

            // 鎖附角度：4159~4160，任意起點讀滿 2 words
            $add(2, range(4159, 4160));
            
            // 鎖附總角度：4190~4191，任意起點讀滿 2 words
            $add(2, range(4190, 4191));

            // 扭力上限：4172~4173，任意起點讀滿 2 words
            $add(2, range(4172, 4173));

            // 扭力下限：4174~4175，任意起點讀滿 2 words
            $add(2, range(4174, 4175));

            // 角度上限：4178~4179，任意起點讀滿 2 words
            $add(2, range(4178, 4179));

            // 角度下限：4180~4181，任意起點讀滿 2 words
            $add(2, range(4180, 4181));

            // 門檻點扭力：4182~4183，任意起點讀滿 2 words
            $add(2, range(4182, 4183));

            // 降速點扭力：4184~4185，任意起點讀滿 2 words
            $add(2, range(4184, 4185));

            // 步驟1扭力：4242~4243，任意起點讀滿 2 words
            $add(2, range(4242, 4243));

            // 步驟1角度：4244~4245，任意起點讀滿 2 words
            $add(2, range(4244, 4245));

            // 步驟2扭力：4246~4247，任意起點讀滿 2 words
            $add(2, range(4246, 4247));

            // 步驟2角度：4248~4249，任意起點讀滿 2 words
            $add(2, range(4248, 4249));

            // 步驟3扭力：4250~4251，任意起點讀滿 2 words
            $add(2, range(4250, 4251));

            // 步驟3角度：4252~4253，任意起點讀滿 2 words
            $add(2, range(4252, 4253));

            // 步驟4扭力：4254~4255，任意起點讀滿 2 words
            $add(2, range(4254, 4255));

            // 步驟4角度：4256~4257，任意起點讀滿 2 words
            $add(2, range(4256, 4257));

            
            // 步驟5扭力：4258~4259，任意起點讀滿 2 words
            $add(2, range(4258, 4259));

            // 步驟5角度：4260~4261，任意起點讀滿 2 words
            $add(2, range(4260, 4261));

            //鎖附條碼：4192~4241，任意起點讀滿 50 words
             $add(50, range(4192, 4241));


            // 其他單/雙 word 欄位（照你原表）
            $add(1, [4144,4145,4148,4149,4150,4151,4152,4153,4154,4157,4158,4161,4162,4163,4164,4167,4168,4169]);
            $add(2, [4146,4155,4159,4165,4170,4172,4174,4176,4178,4180,4182,4184,4188,4190]);
            $add(1, [4186, 4187]);    // DOWNSHIFT_RPM / FIND_RPM

            // 字串型大區塊
            $add(50, [4192]);         // FASTEN_BARCODE（50 bytes）
            $add(50, [396]);          // CURRENT/INPUT_BARCODE（50 bytes）

            // ===== 連續區間（每點固定 2 bytes = 1 word）=====
            // 4.3 過程資料（Torque/Angle/RPM/Power/Time）
            $ranges[] = [8192, 12190, 2];  // Torque
            $ranges[] = [12192, 16190, 2]; // Angle
            $ranges[] = [16192, 18191, 2]; // RPM
            $ranges[] = [18192, 20191, 2]; // Power
            $ranges[] = [20192, 22191, 2]; // Time

            // 4.2 進階步驟資料
            $ranges[] = [4242, 4272, 2];
            $ranges[] = [4400, 4426, 2];
        }

        $a = (int)$addr;

        // 先檢查是否落在「區間」
        foreach ($ranges as [$beg, $end, $size]) {
            if ($a >= $beg && $a <= $end) {
                return $size;
            }
        }

        // 再查精確位址
        return $map[$a] ?? $default; // 找不到就回 $default（預設 null）
    }




    public function get_list() {
        $this->view('service/client9502'); 
    }


    public function get_api(){
        header('Content-Type: application/json; charset=utf-8');

        $lang = strtolower($_COOKIE['language'] ?? 'en-us');
        $M = [
            'en-us' => ['invalid'=>'Invalid payload.','ok'=>'OK','server'=>'Server error.'],
            'zh-tw' => ['invalid'=>'傳入資料格式不正確。','ok'=>'OK','server'=>'伺服器錯誤。'],
            'zh-cn' => ['invalid'=>'传入数据格式不正确。','ok'=>'OK','server'=>'服务器错误。'],
        ];
        $msg = $M[$lang] ?? $M['en-us'];

        // —— 讀取 JSON ——
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['res_type'=>'Error','res_msg'=>$msg['invalid']], JSON_UNESCAPED_UNICODE);
            return;
        }

        // 支援兩種形態：rows[] 或 no/read_position/input_position 三陣列
        $clientRows = [];
        if (isset($data['rows']) && is_array($data['rows'])) {
            $clientRows = $data['rows'];
        } else {
            $nos  = isset($data['no']) ? (array)$data['no'] : [];
            $rps  = isset($data['read_position']) ? (array)$data['read_position'] : [];
            $ips  = isset($data['input_position']) ? (array)$data['input_position'] : [];
            $len  = max(count($nos), count($rps), count($ips));
            for ($i=0; $i<$len; $i++){
                $clientRows[] = [
                    'no'             => $nos[$i]  ?? null,
                    'read_position'  => $rps[$i]  ?? null,
                    'input_position' => $ips[$i]  ?? null,
                ];
            }
        }

        if (empty($clientRows)) {
            echo json_encode([
                'res_type'=>'OK',
                'res_msg' =>$msg['ok'],
                'server_time'=>date('c'),
                'affected'=>0,
                'rows'=>[],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // 限制最多 100 列，避免過度負載
        if (count($clientRows) > 100) {
            $clientRows = array_slice($clientRows, 0, 100);
        }

        $columnsWL = self::ntcsColumns();

        // 先抓一次最新一筆 DB 資料，供所有 DB 模式使用
        $lastRow = null;
        try {
            if (isset($this->DataModel) && method_exists($this->DataModel, 'get_operation_info')) {
                $lastRow = $this->DataModel->get_operation_info();
            } elseif (method_exists($this, 'get_operation_info')) {
                $lastRow = $this->DataModel->get_operation_info();
            }
            if (!is_array($lastRow)) $lastRow = null;
        } catch (\Throwable $e) { $lastRow = null; }

        // 工具：把任意值轉成字串結果
        $toString = static function($val){
            if (is_array($val))      return implode(',', array_map('strval', $val));
            if ($val === null)       return '';
            if (is_scalar($val))     return (string)$val;
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        };

        $rowsOut = [];

        foreach ($clientRows as $r) {
            if (!is_array($r)) continue;

            $no   = isset($r['no']) ? (int)$r['no'] : null;
            $rp   = trim((string)($r['read_position']  ?? ''));
            $ip   = trim((string)($r['input_position'] ?? ''));

            $mode   = '';   // 'db' | 'modbus' | ''
            $final  = '';
            $column = null; // DB 模式欄位名

            // 如果 $lastRow 存在，且 fasten_status ∈ [4,5,6]，同時 error_message 為 "0"
            // 就把 error_message 改成 "1"
            if (!empty($lastRow) && ($lastRow['error_message'] ?? null) === '0'
                && in_array((string)($lastRow['fasten_status'] ?? ''), ['4','5','6'], true)) {
                $lastRow['error_message'] = '1';
            }

            // 判斷模式
            if (preg_match('/^#\s*(\d+)(?:\s+.*)?$/', $rp, $m)) {
                // —— DB 模式 —— ex: "#26 fasten_status"
                $mode = 'db';
                $idx  = (int)$m[1];
                $col  = $columnsWL[$idx] ?? null;
                if ($col && preg_match('/^\w+$/', $col) && is_array($lastRow) && array_key_exists($col, $lastRow)) {
                    $column = $col;
                    $final  = $toString($lastRow[$col]);
                } else {
                    $final = '';
                }
            } elseif ($rp !== '' && preg_match('/^\d+$/', $rp)) {
                // —— Modbus 模式：只要 read 是數字就讀（input 可省略）
                $mode = 'modbus';
                $readPos = (int)$rp;
                try {
                    $bytes = 1;
                    if (method_exists($this, 'RegMap')) {
                        $bytes = (int)$this->RegMap($readPos);
                        if ($bytes < 1) $bytes = 1;
                    }
                    $val = $this->get_modbus_api($readPos, $bytes);
                    /* === 通用規則：若回傳為 16-bit 陣列，移除前導 0 ===
                    範例：
                        [0, 1093]   -> 1093
                        [0, 0, 25]  -> 25
                        [0]         -> 0      （全為 0 的情況保留單一 0）
                        [12, 0]     -> "12,0" （非前導 0 不移除）
                    */
                    if (is_array($val)) {
                        // 去掉前導 0
                        $i = 0;
                        $n = count($val);
                        while ($i < $n && (int)$val[$i] === 0) $i++;
                        if ($i > 0) {
                            $val = array_slice($val, $i);
                            // 若切完空陣列，表示全是 0，統一回傳 0
                            if (count($val) === 0) $val = 0;
                            // 若只剩一個數值，直接降維成純量，方便前端顯示
                            elseif (count($val) === 1) $val = $val[0];
                        }
                    }

                    /* === 先除以 1000 === */
                    if (in_array($readPos, [4170, 4171,4155,4156,4172,4173,4174,4175,4182,4183,4184,4185,4242,4243,4246,4247,4250,4251,4254,4255,4258,4259], true)) {
                        if (is_numeric($val)) {
                            $val = $val / 1000;
                        } elseif (is_array($val)) {
                            $val = array_map(static function($x){
                                return is_numeric($x) ? ($x / 1000) : $x;
                            }, $val);

                            /* ★ 再移除「尾端」的 0，避免 0.24,0 這類字串 */
                            while (!empty($val) && is_numeric(end($val)) && (float)end($val) == 0.0) {
                                array_pop($val);
                            }
                            if (count($val) === 0)       { $val = 0; }
                            elseif (count($val) === 1)   { $val = $val[0]; }
                        }
                    }

                    //總鎖附時間 先除以 1000
                    if (in_array($readPos, [4158], true)) {
                        if (is_numeric($val)) {
                            $val = $val / 1000;
                        } elseif (is_array($val)) {
                            $val = array_map(static function($x){
                                return is_numeric($x) ? ($x / 1000) : $x;
                            }, $val);

                            /* ★ 再移除「尾端」的 0，避免 0.24,0 這類字串 */
                            while (!empty($val) && is_numeric(end($val)) && (float)end($val) == 0.0) {
                                array_pop($val);
                            }
                            if (count($val) === 0)       { $val = 0; }
                            elseif (count($val) === 1)   { $val = $val[0]; }
                        }
                    }

                    
                    /* === 通用：移除「尾端 0」(例: [2343,0] -> 2343) === */
                    if (is_array($val)) {
                        while (!empty($val) && is_numeric(end($val)) && (float)end($val) == 0.0) {
                            array_pop($val);
                        }
                        if (count($val) === 0)       { $val = 0; }
                        elseif (count($val) === 1)   { $val = $val[0]; }
                    }

                    /* === 通用：移除「尾端近零」(例: [2343,0] / [0.264,0.001] -> 2343 / 0.264) === */
                    /* 可調整的近零門檻：1e-3 表示 <= 0.001 當作 0 */
                    $TAIL_ZERO_EPS = 1e-3;

                    if (is_array($val)) {
                        while (!empty($val)) {
                            $last = end($val);
                            $isZeroish = is_numeric($last) && abs((float)$last) <= $TAIL_ZERO_EPS;
                            if ($isZeroish) array_pop($val);
                            else break;
                        }
                        if (count($val) === 0)       { $val = 0; }
                        elseif (count($val) === 1)   { $val = $val[0]; }
                    }


                    $final = $toString($val);


                } catch (\Throwable $e) {
                    $final = '';
                }
            } else {
                // 不明格式 → 不處理
                $mode  = '';
                $final = '';
            }

            $rowsOut[] = [
                'no'         => $no,
                'result'     => $final,
                'read_mode'  => $mode,
                'column'     => $column, // 只有 DB 模式才會有值
            ];
        }

        echo json_encode([
            'res_type'    => 'OK',
            'res_msg'     => $msg['ok'],
            'server_time' => date('c'),
            'affected'    => count($rowsOut),
            'rows'        => $rowsOut,
        ], JSON_UNESCAPED_UNICODE);
    }


    public function fillCsvResults(string $csvPath = '/var/www/html/temp/customize.csv'): array{
        // 1) 取 DB 最新一筆（用你的 Model）
        $lastRow = null;
        try {
            if (isset($this->DataModel) && method_exists($this->DataModel, 'get_operation_info')) {
                $lastRow = $this->DataModel->get_operation_info();
            } elseif (method_exists($this, 'get_operation_info')) {
                $lastRow = $this->DataModel->get_operation_info();
            }
            if (!is_array($lastRow)) $lastRow = null;
        } catch (\Throwable $e) { $lastRow = null; }

        $toString = static function($val){
            if (is_array($val))  return implode(',', array_map('strval', $val));
            if ($val === null)   return '';
            if (is_scalar($val)) return (string)$val;
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        };

        // 2) 讀 CSV
        if (!is_file($csvPath)) return [];
        $fp = @fopen($csvPath, 'r');
        if (!$fp) return [];

        $rows = [];
        $isFirst = true;

        while (($cols = fgetcsv($fp)) !== false) {
            if ($isFirst && isset($cols[0])) {
                // 去 BOM
                $cols[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$cols[0]);
                $isFirst = false;
            }

            $noRaw   = isset($cols[0]) ? trim((string)$cols[0]) : '';
            $rpRaw   = isset($cols[1]) ? trim((string)$cols[1]) : '';
            $ipRaw   = isset($cols[2]) ? trim((string)$cols[2]) : '';
            $resRaw  = isset($cols[3]) ? trim((string)$cols[3]) : '';

            // 跳過空行
            if ($noRaw === '' && $rpRaw === '' && $ipRaw === '' && $resRaw === '') continue;

            // 偵測表頭
            $maybeHeader = preg_match('/^no$/i',$noRaw) ||
                        preg_match('/^read\s*position$/i',$rpRaw) ||
                        preg_match('/^input\s*position$/i',$ipRaw) ||
                        preg_match('/^result$/i',$resRaw);
            if ($maybeHeader) {
                // 保留表頭到最前面，後續覆寫時會重建表頭，所以這裡不加入 $rows
                continue;
            }

            // 3) 正規化 Read Position
            // 支援 "#36 threshold_angle" → "threshold_angle"
            $rpNorm = preg_replace('/^\s*#\s*\d+\s*/', '', $rpRaw);

            // 4) 依規則取值
            $final = '';

            // 4-1) 先嘗試 DB：Read Position 直接等於欄位名
            if ($lastRow && $rpNorm !== '' && preg_match('/^\w+$/', $rpNorm) && array_key_exists($rpNorm, $lastRow)) {
                $final = $toString($lastRow[$rpNorm]);

            // 4-2)（選用）Modbus：Read/Input 都是純數字才讀
            } elseif ($rpRaw !== '' && preg_match('/^\d+$/', $rpRaw) && $ipRaw !== '' && preg_match('/^\d+$/', $ipRaw)) {
                try {
                    $readPos = (int)$rpRaw;
                    $bytes = method_exists($this,'RegMap') ? max(1,(int)$this->RegMap($readPos)) : 1;
                    $val = $this->get_modbus_api($readPos, $bytes);
                    $final = $toString($val);
                } catch (\Throwable $e) {
                    $final = '';
                }
            }

            $rows[] = [
                'no'             => ctype_digit($noRaw) ? (int)$noRaw : $noRaw,
                'read_position'  => $rpRaw,
                'input_position' => $ipRaw,
                'result'         => $final,   // ★ 填好的結果
            ];
        }
        fclose($fp);

        // 5) 覆寫回 CSV（友善 Excel：加 BOM + 表頭）
        $dir = dirname($csvPath);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $tmp = $csvPath . '.tmp';
        $out = fopen($tmp, 'w');
        if ($out) {
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['no','read_position','input_position','result']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['no'], $r['read_position'], $r['input_position'], $r['result']]);
            }
            fclose($out);
            @unlink($csvPath);
            @rename($tmp, $csvPath);
            @chmod($csvPath, 0644);
        }

        return $rows;
    }


}
} else {
class Customize extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    Private $deviceId;


    public function __construct(){

        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();

    }

    // 取得所有Jobs
    public function index(){

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) {
            include $file;
        }
        
        $isMobile = $this->isMobileCheck();
        $job_list = $this->SettingModel->get_job_list();
        $data_button = $this->MiscellaneousModel->details('customize');
   
        //判斷 csv 是否存在
        $data_csv = $this->load_customize_csv_arrays();

        $data = [
            'isMobile' => $isMobile,
            'job_list' => $job_list,
            'data_button' => $data_button,
            'data_csv' => $data_csv,
            'text' => isset($text) ? $text : []
        ];
        $this->view('customize/index', $data);
    }

    /**
     * Operator / law = 3：只能瀏覽，不允許自定義儲存寫入。
     */
    private function isOperatorLaw3(): bool{

        $lawKeys  = ['user_law', 'law', 'userLaw', 'user_level', 'permission', 'role_law'];
        $roleKeys = ['role', 'user_role', 'account_role', 'permission_name'];
        $session  = $_SESSION ?? [];

        $privilege = strtolower(trim((string)($session['privilege'] ?? '')));
        if (in_array($privilege, ['admin', 'administrator', 'guest'], true)) {
            return false;
        }
        if ($privilege === 'operator') {
            return true;
        }

        foreach ($lawKeys as $key) {
            if (isset($session[$key]) && is_numeric($session[$key])) {
                return ((int)$session[$key] === 3);
            }
        }

        foreach ($roleKeys as $key) {
            if (isset($session[$key])) {
                $role = strtolower(trim((string)$session[$key]));
                if ($role === 'operator') return true;
                if (in_array($role, ['admin', 'administrator', 'guest'], true)) return false;
            }
        }

        // 只有沒有明確 SESSION 身分時才 fallback 到 cookie，避免 guest/admin 被舊 cookie 誤判。
        $hasSessionIdentity = isset($session['privilege']) || isset($session['username']) || isset($session['user']) || isset($session['account']);
        if ($hasSessionIdentity) {
            return false;
        }

        foreach ($lawKeys as $key) {
            if (isset($_COOKIE[$key]) && is_numeric($_COOKIE[$key]) && (int)$_COOKIE[$key] === 3) {
                return true;
            }
        }

        foreach ($roleKeys as $key) {
            if (isset($_COOKIE[$key]) && strtolower(trim((string)$_COOKIE[$key])) === 'operator') {
                return true;
            }
        }

        return false;
    }

    private function denyOperatorWriteJson(): void{

        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo json_encode([
            'res_type' => 'Error',
            'res_code' => 'OPERATOR_WRITE_DENIED',
            'res_msg'  => 'Operator permission is read-only.'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }




    /**
     * 讀取 /var/www/html/temp/customize.csv
     * 回傳：
     *   [
     *     'no'             => [1, 2, ...],             // int[]
     *     'read_position'  => ['#26 fasten_status', ...], // string[]
     *     'input_position' => [1000, 2000, ...],       // float[]（非數字則為 null）
     *     'result'         => ['OK', '12.3', ...]      // string[]
     *   ]
     * 失敗（檔案不存在/不可讀/表頭不符）則回傳 null
     */
    public function load_customize_csv_arrays(string $file = '/var/www/html/temp/customize.csv'): ?array{

        if (!is_file($file) || !is_readable($file)) return null;
        $fh = @fopen($file, 'r');
        if (!$fh) return null;

        // 處理 UTF-8 BOM
        $bom = fread($fh, 3);
        if ($bom !== "\xEF\xBB\xBF") rewind($fh);

        // 讀表頭
        $headers = fgetcsv($fh);
        if ($headers === false || !is_array($headers)) { fclose($fh); return null; }

        // 表頭正規化（大小寫不敏感、空白→底線、去除非 \w）
        $norm = static function($s){
            $s = strtolower(trim($s ?? ''));
            $s = preg_replace('/\s+/', '_', $s);
            return preg_replace('/[^\w]/', '', $s);
        };
        $hmap = [];
        foreach ($headers as $i => $h) $hmap[$norm($h)] = $i;

        // 需求欄位索引
        $idxNO  = $hmap['no']             ?? null;
        $idxRP  = $hmap['read_position']  ?? null;
        $idxIP  = $hmap['input_position'] ?? null;
        $idxRES = $hmap['result']         ?? null;
        if ($idxNO===null || $idxRP===null || $idxRES===null) {
            fclose($fh);
            return null;
        }

        $res = [
            'no'             => [],
            'read_position'  => [],
            'input_position' => [],
            'result'         => [],
        ];

        while (($row = fgetcsv($fh)) !== false) {
            if (!is_array($row)) continue;

            // 跳過全空列
            $allEmpty = true;
            foreach ($row as $cell) {
                if (trim((string)$cell) !== '') { $allEmpty = false; break; }
            }
            if ($allEmpty) continue;

            // 取值並轉型
            $no  = (int)trim((string)($row[$idxNO]  ?? ''));
            $rp  = trim((string)($row[$idxRP]  ?? ''));
            $ipS = ($idxIP === null) ? '' : trim((string)($row[$idxIP] ?? ''));
            $re  = trim((string)($row[$idxRES] ?? ''));

            $ip  = ($ipS === '' ? null : (is_numeric($ipS) ? (float)$ipS : null));

            $res['no'][]             = $no;
            $res['read_position'][]  = $rp;
            $res['input_position'][] = $ip;
            $res['result'][]         = $re;
        }
        fclose($fh);

        // 全部都空就算失敗
        if (empty($res['no']) && empty($res['read_position']) && empty($res['input_position']) && empty($res['result'])) {
            return null;
        }
        return $res;
    }



    /**
     * POST /?url=Customize/save_positions
     * 前端 payload: { rows: [{read_pos, input_pos, result}, ...] }
     * 寫入 CSV 至 /var/www/html/temp，檔名：customize_<SN>_YYYYMMDDhhmmss[_n].csv
     * 回傳 JSON：{res_type, res_msg, affected, filename, download_url}
     */
   
    public function save_positions(){

        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        if ($this->isOperatorLaw3()) {
            $this->denyOperatorWriteJson();
            return;
        }

        header('Content-Type: application/json; charset=utf-8');

        // 同時支援 JSON 與 x-www-form-urlencoded
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST; // 舊式表單也吃
        }

        $rows  = $data['rows'] ?? [];
        $clear = !empty($data['clear']); // 前端會傳 1

        // 相容舊版前端：三欄都空也視為清空
        $compatAllEmpty = empty($rows)
                    && empty($data['no'])
                    && empty($data['read_position'])
                    && empty($data['input_position']);

        // --- i18n 訊息 ---
        $lang = strtolower($_SESSION['language'] ?? 'en-us');
        $M = [
            'en-us' => ['invalid'=>'Invalid payload.','empty'=>'No rows to save.','ok'=>'Saved successfully.','server'=>'Server error.'],
            'zh-tw' => ['invalid'=>'傳入資料格式不正確。','empty'=>'沒有可儲存的資料。','ok'=>'已儲存成功。','server'=>'伺服器錯誤。'],
            'zh-cn' => ['invalid'=>'传入数据格式不正确。','empty'=>'没有可保存的数据。','ok'=>'保存成功。','server'=>'服务器错误。'],
        ];
        $msg = $M[$lang] ?? $M['en-us'];

        // === 空設定：清空伺服端資料 ===
        if ($clear || $compatAllEmpty) {
            $csvFile = '/var/www/html/temp/customize.csv';
            $ok = true;

            if (is_file($csvFile)) {
                $ok = @unlink($csvFile);
                if (!$ok) { // 刪不掉就截斷
                    $fp = @fopen($csvFile, 'w');
                    if ($fp) { fclose($fp); $ok = true; }
                }
            }

            echo json_encode([
                'res_type' => $ok ? 'OK' : 'Error',
                'res_msg'  => $ok ? ($msg['ok'] ?? 'OK') : ($msg['server'] ?? 'Server error'),
                'rows'     => []   // 前端 success 內會用到 resp.rows
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // === 檢查 payload ===
        if (!is_array($rows)) {
            http_response_code(400);
            echo json_encode(['res_type'=>'Error','res_msg'=>$msg['invalid']], JSON_UNESCAPED_UNICODE);
            return;
        }

        // === 清理資料 + 計算每列 $final（前端寫回 result-<row_id>）===
        $rowsForCsv  = [];
        $rowsForResp = [];
        $columnsWL   = self::ntcsColumns(); // 欄位白名單

        foreach ($rows as $r) {
            if (!is_array($r)) continue;

            $row_id = isset($r['row_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$r['row_id']) : '';

            // 來源資訊（db / modbus）
            $read_src       = isset($r['read_src'])       ? strtolower(trim((string)$r['read_src']))  : '';
            $read_db_index  = isset($r['read_db_index'])  ? trim((string)$r['read_db_index'])         : '';
            $read_db_name   = isset($r['read_db_name'])   ? trim((string)$r['read_db_name'])          : '';

            // Modbus/手動欄位
            $read   = isset($r['read_pos'])  ? trim((string)$r['read_pos'])  : '';
            $input  = isset($r['input_pos']) ? trim((string)$r['input_pos']) : '';
            $result = isset($r['result'])    ? strtoupper(trim((string)$r['result'])) : '';
            if (!in_array($result, ['', 'OK', 'NG'], true)) $result = '';

            // 長度限制
            $read         = mb_substr($read,  0, 128, 'UTF-8');
            $input        = mb_substr($input, 0, 128, 'UTF-8');
            $read_db_name = mb_substr($read_db_name, 0, 128, 'UTF-8');

            // 顯示於 CSV 的讀取欄位文字
            $csvReadText = '';
            if ($read_src === 'db' && $read_db_index !== '') {
                $idx = (int)$read_db_index;
                $col = $columnsWL[$idx] ?? '';
                $tag = '#'.$idx;
                $csvReadText = $col ? ($tag.' '.$col) : $tag;
            } else {
                $csvReadText = $read;
            }

            // === 計算 $final：分流 ===
            $final = '';

            if ($read_src === 'db' && $read_db_index !== '') {
                // ===== DB 讀取模式：改用 get_operation_info() 取最新一筆 =====
                $idx = (int)$read_db_index;
                $col = $columnsWL[$idx] ?? null;

                if ($col && preg_match('/^\w+$/', $col)) {
                    try {
                        $lastRow = $this->DataModel->get_operation_info();  // 取最後一筆
                        if (is_array($lastRow) && array_key_exists($col, $lastRow)) {
                            $val = $lastRow[$col];
                            if (is_array($val))        $final = implode(',', array_map('strval', $val));
                            elseif ($val === null)     $final = '';
                            elseif (is_scalar($val))   $final = (string)$val;
                            else                       $final = json_encode($val, JSON_UNESCAPED_UNICODE);
                        } else {
                            $final = '';
                        }
                    } catch (\Throwable $e) {
                        $final = '';
                    }
                } else {
                    $final = ''; // 白名單沒有或欄名不合法
                }

            } else {
                // ===== Modbus 讀取模式（維持原規則） =====
                if ($read !== '' && preg_match('/^\d+$/', $read)){
                    $readPos = (int)$read;
                    try {
                        $bytes = 1;
                        if (method_exists($this, 'RegMap')) {
                            $bytes = (int)$this->RegMap($readPos);
                            if ($bytes < 1) $bytes = 1;
                        }
                        $val = $this->get_modbus_api($readPos, $bytes);

                        /* 先除以 1000 的欄位 */
                        if (in_array($readPos, [4170,4171,4155,4156,4172,4173,4174,4175,4182,4183,4184,4185,4242,4243,4246,4247,4250,4251,4254,4255,4258,4259], true)) {
                            if (is_numeric($val)) {
                                $val = $val / 1000;
                            } elseif (is_array($val)) {
                                $val = array_map(static function($x){
                                    return is_numeric($x) ? ($x / 1000) : $x;
                                }, $val);
                            }
                        }

                        /* 移除尾端 0 / 近零 */
                        if (is_array($val)) {
                            while (!empty($val) && is_numeric(end($val)) && (float)end($val) == 0.0) {
                                array_pop($val);
                            }
                            if (count($val) === 0)       { $val = 0; }
                            elseif (count($val) === 1)   { $val = $val[0]; }
                        }
                        $TAIL_ZERO_EPS = 1e-3;
                        if (is_array($val)) {
                            while (!empty($val)) {
                                $last = end($val);
                                $isZeroish = is_numeric($last) && abs((float)$last) <= $TAIL_ZERO_EPS;
                                if ($isZeroish) array_pop($val);
                                else break;
                            }
                            if (count($val) === 0)       { $val = 0; }
                            elseif (count($val) === 1)   { $val = $val[0]; }
                        }

                        if (is_array($val))        $final = implode(',', array_map('strval', $val));
                        elseif ($val === null)     $final = '';
                        elseif (is_scalar($val))   $final = (string)$val;
                        else                       $final = json_encode($val, JSON_UNESCAPED_UNICODE);
                    } catch (\Throwable $e) {
                        $final = '';
                    }
                }
            }

            // 收集要寫入 CSV 的行（只留 NO / Read Position / result）
            if (!($csvReadText === '' && $input === '' && $result === '')) {
                $rowsForCsv[] = [
                    'read_pos' => $csvReadText,
                    'result'   => $final,   // 寫出計算後的結果
                ];
            }

            // 回寫前端結果欄
            if ($row_id !== '') {
                $rowsForResp[] = [
                    'row_id'    => $row_id,
                    'result_id' => 'result-' . $row_id,
                    'result'    => $final,
                ];
            }
        }

        if (empty($rowsForCsv)) {
            echo json_encode([
                'res_type' => 'OK',
                'res_msg'  => $msg['empty'],
                'affected' => 0,
                'rows'     => $rowsForResp,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // --- 取得裝置序號（可選） ---
        $deviceSn = 'UnknownSN';
        try {
            $info = $this->SettingModel->GetControllerInfo();
            if (!empty($info['device_sn'])) {
                $deviceSn = preg_replace('/[^\w\-]+/', '', (string)$info['device_sn']);
            }
        } catch (\Throwable $e) { /* 忽略 */ }

        // --- 固定 Linux 路徑與對外 URL ---
        $baseDir = '/var/www/html/temp';
        $baseUrl = '/temp';

        $dirCheck = self::ensureExportDir($baseDir, 0777, 'www-data', 'www-data');
        if (!$dirCheck['ok']) {
            http_response_code(500);
            echo json_encode([
                'res_type'=>'Error',
                'res_msg' => $msg['server'],
                'detail'  => $dirCheck['error'],
                'rows'    => $rowsForResp,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // --- 固定檔名；每次覆蓋 ---
        $filename = "customize.csv";
        $fullpath = rtrim($baseDir, '/\\') . '/' . $filename;
        $tmpPath  = $fullpath . '.tmp';

        try {
            $fp = @fopen($tmpPath, 'w');
            if (!$fp) {
                http_response_code(500);
                echo json_encode([
                    'res_type'=>'Error',
                    'res_msg' =>$msg['server'],
                    'detail'  =>'fopen tmp failed',
                    'rows'    => $rowsForResp,
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            @flock($fp, LOCK_EX);

            // UTF-8 BOM（Excel 友善）
            fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // 表頭（已移除 Input Position）
            fputcsv($fp, ['NO', 'Read Position', 'result']);

            // 內容
            $i = 1;
            foreach ($rowsForCsv as $r) {
                fputcsv($fp, [$i++, $r['read_pos'], $r['result'] ?? '']);
            }

            @flock($fp, LOCK_UN);
            fclose($fp);
            @chmod($tmpPath, 0644);

            if (file_exists($fullpath)) { @unlink($fullpath); }
            if (!@rename($tmpPath, $fullpath)) {
                @unlink($tmpPath);
                http_response_code(500);
                echo json_encode([
                    'res_type'=>'Error',
                    'res_msg' =>$msg['server'],
                    'detail'  =>'rename failed',
                    'rows'    => $rowsForResp,
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $downloadUrl = rtrim($baseUrl, '/').'/'.$filename;

            echo json_encode([
                'res_type'     => 'OK',
                'res_msg'      => $msg['ok'],
                'affected'     => count($rowsForCsv),
                'filename'     => $filename,
                'download_url' => $downloadUrl,
                'rows'         => $rowsForResp,
            ], JSON_UNESCAPED_UNICODE);

        } catch (\Throwable $e) {
            if (isset($fp) && is_resource($fp)) { @flock($fp, LOCK_UN); fclose($fp); }
            @unlink($tmpPath);
            http_response_code(500);
            echo json_encode([
                'res_type'=>'Error',
                'res_msg'=>$msg['server'],
                'detail'=>$e->getMessage(),
                'rows'   => $rowsForResp,
            ], JSON_UNESCAPED_UNICODE);
        }
    }



    /**
     * ntcs_data 欄位白名單（依索引對應欄位）
     * 供 DB 模式以索引安全映射到實際欄位名
     */
    private static function ntcsColumns(): array {
        $columns = [
            "id","system_sn","data_time","device_type","device_id","device_sn",
            "tool_type","tool_sn","tool_status","job_id","job_name","sequence_id",
            "sequence_name","step_id","torque_unit","target_type","target_torque",
            "target_angle","target_time","fasten_time","final_fasten_torque",
            "final_fasten_angle","total_fasten_angle","count_type","last_screw_count",
            "total_screw_count","fasten_status","error_message","fasten_direction","rpm",
            "hi_torque","lo_torque","hi_angle","lo_angle","delay_ttime","threshold_torque",
            "threshold_angle","downshift_torque","downshift_angle","downshift_speed",
            "final_tool_voltage","final_tool_current","barcode",
        ];
        // ★ 與前端索引一致：43..52 = step1~step5 的 [torque, angle]
        for ($i = 1; $i <= 5; $i++) {
            $columns[] = "step{$i}_last_torque"; // 43,45,47,49,51
            $columns[] = "step{$i}_last_angle";  // 44,46,48,50,52
        }
        return $columns;
    }




    /**
     * 確保輸出資料夾存在且可寫；不存在則遞迴建立。
     * @return array{ok:bool,error?:string}
     */
    private static function ensureExportDir(string $dir, int $perm = 0777, string $user = 'www-data', string $group = 'www-data'): array{

        try {
            if (!is_dir($dir)) {
                if (!@mkdir($dir, $perm, true) && !is_dir($dir)) {
                    return ['ok' => false, 'error' => 'mkdir failed'];
                }
            }
            @chmod($dir, $perm);
            @chown($dir, $user);
            @chgrp($dir, $group);

            // 可寫測試
            $test = rtrim($dir, '/\\') . '/.__wtest';
            if (@file_put_contents($test, '1') === false) {
                return ['ok' => false, 'error' => 'write test failed'];
            }
            @unlink($test);
            return ['ok' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }


    public function get_modbus_api(int $a, int $b = 1) {
        
        require_once '../app/config/config.php';

        // Modbus slave ID 合理範圍通常是 1~255
        $unitId = (int)$this->deviceId;
        if ($unitId < 1 || $unitId > 255) {
            $unitId = 1;
        }

        $startAddress = $a;
        $quantity = $b;  // 每個「暫存器」= 16-bit (= 2 bytes)

        try {
            // MODBUS TCP / OP 由 protocol_read_registers 自動切換。
            $words = $this->protocol_read_registers($unitId, $startAddress, $quantity);
            if (!is_array($words) || empty($words)) {
                return null;
            }

            // --- 規則：b > 3 時自動轉碼為 ASCII 字串 ---
            if ($quantity > 3) {
                // 以「每個 word 高位在前」打包 -> 字串，並去掉尾端 NUL
                // 備註：等同於 big-endian 內部位元組順序；若設備是小端，可改 'v*'
                $bin  = call_user_func_array('pack', array_merge(['n*'], $words));
                $text = rtrim($bin, "\0");
                // 可選：只保留可列印的 ASCII（避免雜訊）
                $text = preg_replace('/[^\x20-\x7E]/', '', $text);
                return $text;
            }

            // --- 其它情況維持原樣 ---
            if ($quantity == 1) {
                return $words[0] ?? null;   // 單一暫存器 -> 單值（例如 0x07E9 => 2025）
            }

            // b = 2 或 3 -> 回傳整個 16-bit 陣列
            return $words;

        } catch (\Throwable $e) {
            return null;
        }
    }


    public function RegMap($addr, $default = null){

        static $inited = false;
        static $map = [];      // 精確位址對應：addr => bytes（words）
        static $ranges = [];   // 連續區間： [beg, end, bytes]

        if (!$inited) {
            $inited = true;

            // 小工具：一次加入多個起點位址（每個起點固定回傳 size 個 16-bit words）
            $add = function (int $size, array $starts) use (&$map) {
                foreach ($starts as $a) {
                    $map[(int)$a] = $size;
                }
            };

            // 4.1 鎖附結果資訊
            $add(1, range(4096, 4101)); // FASTEN_YEAR..FASTEN_SEC（每點 1 word）

            // 控制器序號：4102~4111，任意起點都讀滿 10 words
            $add(10, range(4102, 4111));

            // 起子型號：4112~4121，任意起點都讀滿 10 words
            $add(10, range(4112, 4121));

            // 起子序號：4122~4131，任意起點都讀滿 10 words
            // （若現場不需要亦可移除，不影響其餘邏輯）
            $add(10, range(4122, 4131));

            // JOB_NAME：4132~4137，任意起點讀滿 6 words（確保 4132 與 4133 會不同）
            $add(6, range(4132, 4137));

            // SEQ_NAME：4138~4143，任意起點讀滿 6 words
            $add(6, range(4138, 4143));

            // 目標扭力：4170~4171，任意起點讀滿 2 words
            $add(2, range(4170, 4171));

            // 目標角度：4176~4177，任意起點讀滿 2 words
            $add(2, range(4176, 4177));

            // 鎖附角度：4159~4160，任意起點讀滿 2 words
            $add(2, range(4159, 4160));
            
            // 鎖附總角度：4190~4191，任意起點讀滿 2 words
            $add(2, range(4190, 4191));

            // 扭力上限：4172~4173，任意起點讀滿 2 words
            $add(2, range(4172, 4173));

            // 扭力下限：4174~4175，任意起點讀滿 2 words
            $add(2, range(4174, 4175));

            // 角度上限：4178~4179，任意起點讀滿 2 words
            $add(2, range(4178, 4179));

            // 角度下限：4180~4181，任意起點讀滿 2 words
            $add(2, range(4180, 4181));

            // 門檻點扭力：4182~4183，任意起點讀滿 2 words
            $add(2, range(4182, 4183));

            // 降速點扭力：4184~4185，任意起點讀滿 2 words
            $add(2, range(4184, 4185));

            // 步驟1扭力：4242~4243，任意起點讀滿 2 words
            $add(2, range(4242, 4243));

            // 步驟1角度：4244~4245，任意起點讀滿 2 words
            $add(2, range(4244, 4245));

            // 步驟2扭力：4246~4247，任意起點讀滿 2 words
            $add(2, range(4246, 4247));

            // 步驟2角度：4248~4249，任意起點讀滿 2 words
            $add(2, range(4248, 4249));

            // 步驟3扭力：4250~4251，任意起點讀滿 2 words
            $add(2, range(4250, 4251));

            // 步驟3角度：4252~4253，任意起點讀滿 2 words
            $add(2, range(4252, 4253));

            // 步驟4扭力：4254~4255，任意起點讀滿 2 words
            $add(2, range(4254, 4255));

            // 步驟4角度：4256~4257，任意起點讀滿 2 words
            $add(2, range(4256, 4257));

            
            // 步驟5扭力：4258~4259，任意起點讀滿 2 words
            $add(2, range(4258, 4259));

            // 步驟5角度：4260~4261，任意起點讀滿 2 words
            $add(2, range(4260, 4261));

            //鎖附條碼：4192~4241，任意起點讀滿 50 words
             $add(50, range(4192, 4241));


            // 其他單/雙 word 欄位（照你原表）
            $add(1, [4144,4145,4148,4149,4150,4151,4152,4153,4154,4157,4158,4161,4162,4163,4164,4167,4168,4169]);
            $add(2, [4146,4155,4159,4165,4170,4172,4174,4176,4178,4180,4182,4184,4188,4190]);
            $add(1, [4186, 4187]);    // DOWNSHIFT_RPM / FIND_RPM

            // 字串型大區塊
            $add(50, [4192]);         // FASTEN_BARCODE（50 bytes）
            $add(50, [396]);          // CURRENT/INPUT_BARCODE（50 bytes）

            // ===== 連續區間（每點固定 2 bytes = 1 word）=====
            // 4.3 過程資料（Torque/Angle/RPM/Power/Time）
            $ranges[] = [8192, 12190, 2];  // Torque
            $ranges[] = [12192, 16190, 2]; // Angle
            $ranges[] = [16192, 18191, 2]; // RPM
            $ranges[] = [18192, 20191, 2]; // Power
            $ranges[] = [20192, 22191, 2]; // Time

            // 4.2 進階步驟資料
            $ranges[] = [4242, 4272, 2];
            $ranges[] = [4400, 4426, 2];
        }

        $a = (int)$addr;

        // 先檢查是否落在「區間」
        foreach ($ranges as [$beg, $end, $size]) {
            if ($a >= $beg && $a <= $end) {
                return $size;
            }
        }

        // 再查精確位址
        return $map[$a] ?? $default; // 找不到就回 $default（預設 null）
    }




    public function get_list() {
        $this->view('service/client9502'); 
    }


    public function get_api(){
        header('Content-Type: application/json; charset=utf-8');

        $lang = strtolower($_COOKIE['language'] ?? 'en-us');
        $M = [
            'en-us' => ['invalid'=>'Invalid payload.','ok'=>'OK','server'=>'Server error.'],
            'zh-tw' => ['invalid'=>'傳入資料格式不正確。','ok'=>'OK','server'=>'伺服器錯誤。'],
            'zh-cn' => ['invalid'=>'传入数据格式不正确。','ok'=>'OK','server'=>'服务器错误。'],
        ];
        $msg = $M[$lang] ?? $M['en-us'];

        // —— 讀取 JSON ——
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['res_type'=>'Error','res_msg'=>$msg['invalid']], JSON_UNESCAPED_UNICODE);
            return;
        }

        // 支援兩種形態：rows[] 或 no/read_position/input_position 三陣列
        $clientRows = [];
        if (isset($data['rows']) && is_array($data['rows'])) {
            $clientRows = $data['rows'];
        } else {
            $nos  = isset($data['no']) ? (array)$data['no'] : [];
            $rps  = isset($data['read_position']) ? (array)$data['read_position'] : [];
            $ips  = isset($data['input_position']) ? (array)$data['input_position'] : [];
            $len  = max(count($nos), count($rps), count($ips));
            for ($i=0; $i<$len; $i++){
                $clientRows[] = [
                    'no'             => $nos[$i]  ?? null,
                    'read_position'  => $rps[$i]  ?? null,
                    'input_position' => $ips[$i]  ?? null,
                ];
            }
        }

        if (empty($clientRows)) {
            echo json_encode([
                'res_type'=>'OK',
                'res_msg' =>$msg['ok'],
                'server_time'=>date('c'),
                'affected'=>0,
                'rows'=>[],
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // 限制最多 100 列，避免過度負載
        if (count($clientRows) > 100) {
            $clientRows = array_slice($clientRows, 0, 100);
        }

        $columnsWL = self::ntcsColumns();

        // 先抓一次最新一筆 DB 資料，供所有 DB 模式使用
        $lastRow = null;
        try {
            if (isset($this->DataModel) && method_exists($this->DataModel, 'get_operation_info')) {
                $lastRow = $this->DataModel->get_operation_info();
            } elseif (method_exists($this, 'get_operation_info')) {
                $lastRow = $this->DataModel->get_operation_info();
            }
            if (!is_array($lastRow)) $lastRow = null;
        } catch (\Throwable $e) { $lastRow = null; }

        // 工具：把任意值轉成字串結果
        $toString = static function($val){
            if (is_array($val))      return implode(',', array_map('strval', $val));
            if ($val === null)       return '';
            if (is_scalar($val))     return (string)$val;
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        };

        $rowsOut = [];

        foreach ($clientRows as $r) {
            if (!is_array($r)) continue;

            $no   = isset($r['no']) ? (int)$r['no'] : null;
            $rp   = trim((string)($r['read_position']  ?? ''));
            $ip   = trim((string)($r['input_position'] ?? ''));

            $mode   = '';   // 'db' | 'modbus' | ''
            $final  = '';
            $column = null; // DB 模式欄位名

            // 如果 $lastRow 存在，且 fasten_status ∈ [4,5,6]，同時 error_message 為 "0"
            // 就把 error_message 改成 "1"
            if (!empty($lastRow) && ($lastRow['error_message'] ?? null) === '0'
                && in_array((string)($lastRow['fasten_status'] ?? ''), ['4','5','6'], true)) {
                $lastRow['error_message'] = '1';
            }

            // 判斷模式
            if (preg_match('/^#\s*(\d+)(?:\s+.*)?$/', $rp, $m)) {
                // —— DB 模式 —— ex: "#26 fasten_status"
                $mode = 'db';
                $idx  = (int)$m[1];
                $col  = $columnsWL[$idx] ?? null;
                if ($col && preg_match('/^\w+$/', $col) && is_array($lastRow) && array_key_exists($col, $lastRow)) {
                    $column = $col;
                    $final  = $toString($lastRow[$col]);
                } else {
                    $final = '';
                }
            } elseif ($rp !== '' && preg_match('/^\d+$/', $rp)) {
                // —— Modbus 模式：只要 read 是數字就讀（input 可省略）
                $mode = 'modbus';
                $readPos = (int)$rp;
                try {
                    $bytes = 1;
                    if (method_exists($this, 'RegMap')) {
                        $bytes = (int)$this->RegMap($readPos);
                        if ($bytes < 1) $bytes = 1;
                    }
                    $val = $this->get_modbus_api($readPos, $bytes);
                    /* === 通用規則：若回傳為 16-bit 陣列，移除前導 0 ===
                    範例：
                        [0, 1093]   -> 1093
                        [0, 0, 25]  -> 25
                        [0]         -> 0      （全為 0 的情況保留單一 0）
                        [12, 0]     -> "12,0" （非前導 0 不移除）
                    */
                    if (is_array($val)) {
                        // 去掉前導 0
                        $i = 0;
                        $n = count($val);
                        while ($i < $n && (int)$val[$i] === 0) $i++;
                        if ($i > 0) {
                            $val = array_slice($val, $i);
                            // 若切完空陣列，表示全是 0，統一回傳 0
                            if (count($val) === 0) $val = 0;
                            // 若只剩一個數值，直接降維成純量，方便前端顯示
                            elseif (count($val) === 1) $val = $val[0];
                        }
                    }

                    /* === 先除以 1000 === */
                    if (in_array($readPos, [4170, 4171,4155,4156,4172,4173,4174,4175,4182,4183,4184,4185,4242,4243,4246,4247,4250,4251,4254,4255,4258,4259], true)) {
                        if (is_numeric($val)) {
                            $val = $val / 1000;
                        } elseif (is_array($val)) {
                            $val = array_map(static function($x){
                                return is_numeric($x) ? ($x / 1000) : $x;
                            }, $val);

                            /* ★ 再移除「尾端」的 0，避免 0.24,0 這類字串 */
                            while (!empty($val) && is_numeric(end($val)) && (float)end($val) == 0.0) {
                                array_pop($val);
                            }
                            if (count($val) === 0)       { $val = 0; }
                            elseif (count($val) === 1)   { $val = $val[0]; }
                        }
                    }

                    //總鎖附時間 先除以 1000
                    if (in_array($readPos, [4158], true)) {
                        if (is_numeric($val)) {
                            $val = $val / 1000;
                        } elseif (is_array($val)) {
                            $val = array_map(static function($x){
                                return is_numeric($x) ? ($x / 1000) : $x;
                            }, $val);

                            /* ★ 再移除「尾端」的 0，避免 0.24,0 這類字串 */
                            while (!empty($val) && is_numeric(end($val)) && (float)end($val) == 0.0) {
                                array_pop($val);
                            }
                            if (count($val) === 0)       { $val = 0; }
                            elseif (count($val) === 1)   { $val = $val[0]; }
                        }
                    }

                    
                    /* === 通用：移除「尾端 0」(例: [2343,0] -> 2343) === */
                    if (is_array($val)) {
                        while (!empty($val) && is_numeric(end($val)) && (float)end($val) == 0.0) {
                            array_pop($val);
                        }
                        if (count($val) === 0)       { $val = 0; }
                        elseif (count($val) === 1)   { $val = $val[0]; }
                    }

                    /* === 通用：移除「尾端近零」(例: [2343,0] / [0.264,0.001] -> 2343 / 0.264) === */
                    /* 可調整的近零門檻：1e-3 表示 <= 0.001 當作 0 */
                    $TAIL_ZERO_EPS = 1e-3;

                    if (is_array($val)) {
                        while (!empty($val)) {
                            $last = end($val);
                            $isZeroish = is_numeric($last) && abs((float)$last) <= $TAIL_ZERO_EPS;
                            if ($isZeroish) array_pop($val);
                            else break;
                        }
                        if (count($val) === 0)       { $val = 0; }
                        elseif (count($val) === 1)   { $val = $val[0]; }
                    }


                    $final = $toString($val);


                } catch (\Throwable $e) {
                    $final = '';
                }
            } else {
                // 不明格式 → 不處理
                $mode  = '';
                $final = '';
            }

            $rowsOut[] = [
                'no'         => $no,
                'result'     => $final,
                'read_mode'  => $mode,
                'column'     => $column, // 只有 DB 模式才會有值
            ];
        }

        echo json_encode([
            'res_type'    => 'OK',
            'res_msg'     => $msg['ok'],
            'server_time' => date('c'),
            'affected'    => count($rowsOut),
            'rows'        => $rowsOut,
        ], JSON_UNESCAPED_UNICODE);
    }


    public function fillCsvResults(string $csvPath = '/var/www/html/temp/customize.csv'): array{
        // 1) 取 DB 最新一筆（用你的 Model）
        $lastRow = null;
        try {
            if (isset($this->DataModel) && method_exists($this->DataModel, 'get_operation_info')) {
                $lastRow = $this->DataModel->get_operation_info();
            } elseif (method_exists($this, 'get_operation_info')) {
                $lastRow = $this->DataModel->get_operation_info();
            }
            if (!is_array($lastRow)) $lastRow = null;
        } catch (\Throwable $e) { $lastRow = null; }

        $toString = static function($val){
            if (is_array($val))  return implode(',', array_map('strval', $val));
            if ($val === null)   return '';
            if (is_scalar($val)) return (string)$val;
            return json_encode($val, JSON_UNESCAPED_UNICODE);
        };

        // 2) 讀 CSV
        if (!is_file($csvPath)) return [];
        $fp = @fopen($csvPath, 'r');
        if (!$fp) return [];

        $rows = [];
        $isFirst = true;

        while (($cols = fgetcsv($fp)) !== false) {
            if ($isFirst && isset($cols[0])) {
                // 去 BOM
                $cols[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$cols[0]);
                $isFirst = false;
            }

            $noRaw   = isset($cols[0]) ? trim((string)$cols[0]) : '';
            $rpRaw   = isset($cols[1]) ? trim((string)$cols[1]) : '';
            $ipRaw   = isset($cols[2]) ? trim((string)$cols[2]) : '';
            $resRaw  = isset($cols[3]) ? trim((string)$cols[3]) : '';

            // 跳過空行
            if ($noRaw === '' && $rpRaw === '' && $ipRaw === '' && $resRaw === '') continue;

            // 偵測表頭
            $maybeHeader = preg_match('/^no$/i',$noRaw) ||
                        preg_match('/^read\s*position$/i',$rpRaw) ||
                        preg_match('/^input\s*position$/i',$ipRaw) ||
                        preg_match('/^result$/i',$resRaw);
            if ($maybeHeader) {
                // 保留表頭到最前面，後續覆寫時會重建表頭，所以這裡不加入 $rows
                continue;
            }

            // 3) 正規化 Read Position
            // 支援 "#36 threshold_angle" → "threshold_angle"
            $rpNorm = preg_replace('/^\s*#\s*\d+\s*/', '', $rpRaw);

            // 4) 依規則取值
            $final = '';

            // 4-1) 先嘗試 DB：Read Position 直接等於欄位名
            if ($lastRow && $rpNorm !== '' && preg_match('/^\w+$/', $rpNorm) && array_key_exists($rpNorm, $lastRow)) {
                $final = $toString($lastRow[$rpNorm]);

            // 4-2)（選用）Modbus：Read/Input 都是純數字才讀
            } elseif ($rpRaw !== '' && preg_match('/^\d+$/', $rpRaw) && $ipRaw !== '' && preg_match('/^\d+$/', $ipRaw)) {
                try {
                    $readPos = (int)$rpRaw;
                    $bytes = method_exists($this,'RegMap') ? max(1,(int)$this->RegMap($readPos)) : 1;
                    $val = $this->get_modbus_api($readPos, $bytes);
                    $final = $toString($val);
                } catch (\Throwable $e) {
                    $final = '';
                }
            }

            $rows[] = [
                'no'             => ctype_digit($noRaw) ? (int)$noRaw : $noRaw,
                'read_position'  => $rpRaw,
                'input_position' => $ipRaw,
                'result'         => $final,   // ★ 填好的結果
            ];
        }
        fclose($fp);

        // 5) 覆寫回 CSV（友善 Excel：加 BOM + 表頭）
        $dir = dirname($csvPath);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $tmp = $csvPath . '.tmp';
        $out = fopen($tmp, 'w');
        if ($out) {
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['no','read_position','input_position','result']);
            foreach ($rows as $r) {
                fputcsv($out, [$r['no'], $r['read_position'], $r['input_position'], $r['result']]);
            }
            fclose($out);
            @unlink($csvPath);
            @rename($tmp, $csvPath);
            @chmod($csvPath, 0644);
        }

        return $rows;
    }


}
}
