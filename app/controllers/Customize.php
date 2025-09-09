<?php

class Customize extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;

    public function __construct()
    {
        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
    }

    // 取得所有Jobs
    public function index(){
        $isMobile = $this->isMobileCheck();
        $job_list = $this->SettingModel->get_job_list();
        $data = [
            'isMobile' => $isMobile,
            'job_list' => $job_list,
        ];
        $this->view('customize/index', $data);
    }

   

    /**
     * POST /?url=Customize/save_positions
     * 前端 payload: { rows: [{read_pos, input_pos, result}, ...] }
     * 寫入 CSV 至 /var/www/html/temp，檔名：customize_<SN>_YYYYMMDDhhmmss[_n].csv
     * 回傳 JSON：{res_type, res_msg, affected, filename, download_url}
     */

   
    public function save_positions(){

        header('Content-Type: application/json; charset=utf-8');

        // --- i18n 簡訊息 ---
        $lang = strtolower($_SESSION['language'] ?? 'en-us');
        $M = [
            'en-us' => [
                'invalid' => 'Invalid payload.',
                'empty'   => 'No rows to save.',
                'ok'      => 'Saved successfully.',
                'server'  => 'Server error.',
            ],
            'zh-tw' => [
                'invalid' => '傳入資料格式不正確。',
                'empty'   => '沒有可儲存的資料。',
                'ok'      => '已儲存成功。',
                'server'  => '伺服器錯誤。',
            ],
            'zh-cn' => [
                'invalid' => '传入数据格式不正确。',
                'empty'   => '没有可保存的数据。',
                'ok'      => '保存成功。',
                'server'  => '服务器错误。',
            ],
        ];
        $msg = $M[$lang] ?? $M['en-us'];

        // --- 讀取 JSON ---
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data) || !isset($data['rows']) || !is_array($data['rows'])) {
            http_response_code(400);
            echo json_encode(['res_type'=>'Error','res_msg'=>$msg['invalid']], JSON_UNESCAPED_UNICODE);
            return;
        }

        // === 清理資料 + 計算每列 $final（要回傳到前端顯示在 result-<row_id>）===
        $rowsForCsv  = [];  // CSV 用
        $rowsForResp = [];  // 回傳前端用（讓前端把值塞回 UI）
        foreach ($data['rows'] as $r) {
            if (!is_array($r)) continue;

            $row_id = isset($r['row_id']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$r['row_id']) : '';
            $read   = isset($r['read_pos'])  ? trim((string)$r['read_pos'])  : '';
            $input  = isset($r['input_pos']) ? trim((string)$r['input_pos']) : '';
            $result = isset($r['result'])    ? strtoupper(trim((string)$r['result'])) : '';
            if (!in_array($result, ['', 'OK', 'NG'], true)) $result = '';

            $read  = mb_substr($read,  0, 128, 'UTF-8');
            $input = mb_substr($input, 0, 128, 'UTF-8');

            $final = '';
            if ($read !== '' && $input !== '') {
                $readPos = (int)$read;
                try {
                    $bytes = 1;
                    if (method_exists($this, 'RegMap')) {
                        $bytes = (int)$this->RegMap($readPos);
                        if ($bytes < 1) $bytes = 1;
                    }
                    $val = $this->get_modbus_api($readPos, $bytes);

                    if (is_array($val))        $final = implode(',', array_map('strval', $val));
                    elseif ($val === null)     $final = '';
                    elseif (is_scalar($val))   $final = (string)$val;
                    else                       $final = json_encode($val, JSON_UNESCAPED_UNICODE);
                } catch (\Throwable $e) {
                    $final = '';
                }
            }

            if (!($read === '' && $input === '' && $result === '')) {
                $rowsForCsv[] = ['read_pos' => $read, 'input_pos' => $input];
            }

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
        $filename = "customize_{$deviceSn}.csv";
        $fullpath = rtrim($baseDir, '/\\') . '/' . $filename;
        $tmpPath  = $fullpath . '.tmp';  // 先寫 tmp，再原子覆蓋

        // --- 寫 CSV（覆蓋模式 + 原子替換） ---
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

            // 可選：上鎖避免多程序同時寫同一 tmp（非必要，但更穩）
            @flock($fp, LOCK_EX);

            // UTF-8 BOM（Excel 友善）
            fwrite($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // 表頭
            fputcsv($fp, ['NO', 'Read Position', 'Input Position']);

            // 內容
            $i = 1;
            foreach ($rowsForCsv as $r) {
                fputcsv($fp, [$i++, $r['read_pos'], $r['input_pos']]);
            }

            // 解鎖 & 關閉
            @flock($fp, LOCK_UN);
            fclose($fp);
            @chmod($tmpPath, 0644);

            // Windows 上 rename 不一定能覆蓋；先刪舊檔再換名
            if (file_exists($fullpath)) { @unlink($fullpath); }
            if (!@rename($tmpPath, $fullpath)) {
                // 失敗則清理 tmp 並報錯
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
        require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

        $ip = CONTROLLER_IP;
        $port = 502;
        $unitId = 0;
        $startAddress = $a;
        $quantity = $b;  // 每個「暫存器」= 16-bit (= 2 bytes)

        try {
            $modbus = new ModbusMaster($ip, "TCP");
            $modbus->port = $port;
            $modbus->timeout_sec = 10;

            $raw = $modbus->readMultipleRegisters($unitId, $startAddress, $quantity);

            // --- 正規化成 16-bit words（大端）---
            $words = [];
            if (is_string($raw)) {
                // 二進位字串 -> 16-bit 大端
                $words = array_values(unpack('n*', $raw));
            } elseif (is_array($raw)) {
                // 可能回 bytes 或 words
                $isBytes = !empty($raw) && max($raw) <= 0xFF;
                if ($isBytes) {
                    for ($i = 0; $i + 1 < count($raw); $i += 2) {
                        $hi = $raw[$i] & 0xFF;
                        $lo = $raw[$i + 1] & 0xFF;
                        $words[] = ($hi << 8) | $lo;
                    }
                } else {
                    foreach ($raw as $v) $words[] = (int)$v;
                }
            } else {
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
        static $map = [];      // 精確位址對應：addr => bytes
        static $ranges = [];   // 連續區間： [beg, end, bytes]

        if (!$inited) {
            $inited = true;

            // 小工具：一次加入多個位址
            $add = function (int $size, array $addresses) use (&$map) {
                foreach ($addresses as $a) {
                    $map[(int)$a] = $size;
                }
            };

            // 4.1 鎖附結果資訊
            $add(1, range(4096, 4101));                         // FASTEN_YEAR..FASTEN_SEC
            $add(2, [4102]);                                    // FASTEN_CONTROLLER_SN
            $add(10, [4112, 4122, 4132]);                       // TOOL_MN / TOOL_SN / JOB_NAME
            $add(6, [4138]);                                    // SEQ_NAME
            $add(1, [4144,4145,4148,4149,4150,4151,4152,4153,   // 單位元組欄位
                    4154,4157,4158,4161,4162,4163,4164,4167,
                    4168,4169]);
            $add(2, [4146,4155,4159,4165,4170,4172,4174,4176,   // 兩位元組欄位
                    4178,4180,4182,4184,4188,4190]);
            $add(1, [4186, 4187]);                              // DOWNSHITF_RPM / FIND_RPM
            $add(50, [4192]);                                   // FASTEN_BARCODE

            // 目前條碼（CURRENT/INPUT_BARCODE 都在 396，50 bytes）
            $add(50, [396]);

            // ===== 這裡是「連續區間」：每點固定 2 bytes =====
            // 4.3 過程資料（含 Torque/Angle/RPM/Power/Time）
            $ranges[] = [8192, 12190, 2];   // Torque
            $ranges[] = [12192, 16190, 2];  // Angle
            $ranges[] = [16192, 18191, 2];  // RPM
            $ranges[] = [18192, 20191, 2];  // Power
            $ranges[] = [20192, 22191, 2];  // Time

            // 4.2 進階步驟資料（這段在規格中是連續偶數位址，統一 2 bytes）
            $ranges[] = [4242, 4272, 2];
            $ranges[] = [4400, 4426, 2];

            // 需要再擴充其他位址 → 直接照上面 $add(...) 或 $ranges[] 加就好
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



}
