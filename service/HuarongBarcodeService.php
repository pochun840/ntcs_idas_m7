<?php

declare(strict_types=1);

/**
 * 華榮 MES 接口 1：Barcode -> MES -> 配方轉換。
 *
 * 本 Service 負責：
 * 1. 以控制器 device_id 作為 station_code。
 * 2. POST /api/barcode 並解析 MES JSON Response。
 * 3. 保存 work_order Context，供接口 2 上報鎖附結果使用。
 * 4. 將 MES job/seq/steps 轉為既有 JobConfigReplaceService 可接受的格式。
 *
 * 真正寫入 Controller 仍交給既有 JobConfigReplaceService，避免重複兩套 DB 寫入邏輯。
 */
final class HuarongBarcodeService
{
    private const DEVICE_DB_PATH = '/home/kls/NTCS7/ntcs_device.db';

    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function buildRequest(string $barcode): array
    {
        $barcode = trim($barcode);
        if ($barcode === '') {
            throw new InvalidArgumentException('barcode 不能為空');
        }

        return [
            'barcode' => $barcode,
            // 華榮欄位名稱維持 station_code，值使用控制器 device_id。
            'station_code' => $this->getDeviceId(),
        ];
    }

    public function send(string $barcode): array
    {
        $payload = $this->buildRequest($barcode);

        if (!empty($this->config['mock_enabled'])) {
            $mockFile = (string)($this->config['mock_response_file'] ?? '');
            if ($mockFile === '' || !is_file($mockFile) || !is_readable($mockFile)) {
                throw new RuntimeException('華榮 MES Mock Response 檔案不存在或無法讀取');
            }
            $mockRaw = file_get_contents($mockFile);
            $response = json_decode((string)$mockRaw, true);
            if (!is_array($response) || json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('華榮 MES Mock Response JSON 格式錯誤');
            }
            // 測試 Barcode 同步成工單 SN，方便確認本次 Context / 接口 2 使用的是哪一筆測試。
            if (isset($response['data']['work_order']) && is_array($response['data']['work_order'])) {
                $response['data']['work_order']['SN'] = $barcode;
            }
            return [
                'sent' => false,
                'mock' => true,
                'http_code' => 200,
                'payload' => $payload,
                'response' => $response,
            ];
        }

        // 接口 1 的完整位置由 iDAS app/config/config.php 管理。
        if (!defined('HUARONG_MES_BARCODE_URL')) {
            require_once dirname(__DIR__) . '/app/config/config.php';
        }
        $url = trim((string)HUARONG_MES_BARCODE_URL);
        $scheme = strtolower((string)parse_url($url, PHP_URL_SCHEME));
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('華榮 MES 條碼 API 完整 URL 尚未設定或格式錯誤（app/config/config.php）');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL extension 未安裝');
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Barcode JSON 建立失敗');
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int)($this->config['connect_timeout'] ?? 3),
            CURLOPT_TIMEOUT => (int)($this->config['timeout'] ?? 5),
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseBody === false) {
            throw new RuntimeException('MES 連線失敗：' . $curlError);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new RuntimeException('MES HTTP 狀態異常：' . $httpCode);
        }

        $response = json_decode((string)$responseBody, true);
        if (!is_array($response) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('MES Response 不是有效 JSON');
        }

        return [
            'sent' => true,
            'mock' => false,
            'http_code' => $httpCode,
            'payload' => $payload,
            'response' => $response,
        ];
    }

    public function assertSuccessfulResponse(array $response): array
    {
        $code = $response['code'] ?? null;
        if (!is_numeric($code)) {
            throw new RuntimeException('MES Response 缺少有效 code');
        }
        if ((int)$code !== 0) {
            $msg = trim((string)($response['msg'] ?? 'MES Barcode 校驗失敗'));
            throw new HuarongMesResponseException((int)$code, $msg !== '' ? $msg : 'MES Barcode 校驗失敗', $response);
        }
        if (!isset($response['data']) || !is_array($response['data'])) {
            throw new RuntimeException('MES Response 缺少 data');
        }
        foreach (['work_order', 'job', 'seq', 'steps'] as $key) {
            if (!array_key_exists($key, $response['data'])) {
                throw new RuntimeException('MES Response 缺少 data.' . $key);
            }
        }
        if (!is_array($response['data']['work_order']) || !is_array($response['data']['job']) ||
            !is_array($response['data']['seq']) || !is_array($response['data']['steps']) ||
            $response['data']['steps'] === []) {
            throw new RuntimeException('MES Response 配方資料格式錯誤');
        }
        return $response['data'];
    }

    public function saveContext(array $data, array $requestPayload, int $baselineResultId = 0): array
    {
        $workOrder = $data['work_order'];
        $context = [
            'barcode' => (string)($requestPayload['barcode'] ?? ''),
            'station_code' => (string)($requestPayload['station_code'] ?? ''),
            'item' => (string)($workOrder['item'] ?? ''),
            'SN' => (string)($workOrder['SN'] ?? ''),
            'process_name' => (string)($workOrder['process_name'] ?? ''),
            // Controller JOB name used by API 2 to match ntcs_data.job_name.
            'job_name' => trim((string)($data['job']['job_name'] ?? '')),
            'station' => $workOrder['station'] ?? '',
            'screw_inf' => isset($workOrder['screw_inf']) && is_array($workOrder['screw_inf']) ? $workOrder['screw_inf'] : [],
            'received_at' => date('Y-m-d H:i:s'),
            'status' => 'READY',
            // 只允許接口 2 上報 Barcode/配方完成之後產生的新鎖附結果。
            'baseline_result_id' => max(0, $baselineResultId),
            'last_reported_result_id' => max(0, $baselineResultId),
        ];

        $path = (string)($this->config['context_file'] ?? dirname(__DIR__) . '/public/huarong_mes_context.json');
        $dir = dirname($path);
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new RuntimeException('MES Context 目錄不可寫入');
        }
        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false || file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
            throw new RuntimeException('MES Context 保存失敗');
        }
        return $context;
    }

    /** 將華榮 Response 配方轉成既有單台 Job Config API schema。 */
    public function mapJobConfig(array $data): array
    {
        $job = $data['job'];
        $seq = $data['seq'];
        $steps = $data['steps'];

        $jobId = $this->intRequired($job, 'job_id', 1, 100);
        $seqId = $this->intRequired($seq, 'seq_id', 1, 50);
        $torqueScale = (float)($this->config['torque_scale'] ?? 100);
        if ($torqueScale <= 0) $torqueScale = 100;

        $jobRow = [
            'JOBID' => $jobId,
            'JOBname' => $this->stringRequired($job, 'job_name'),
            'ok_job' => $this->intValue($job, 'job_ok_job', 1),
            'ok_job_stop' => $this->intValue($job, 'job_ok_job_stop', 0),
        ];

        $threadCalcu = 0;
        $weights = [1 => 16, 2 => 8, 3 => 4, 4 => 2, 5 => 1];
        foreach ($weights as $index => $weight) {
            if ($this->intValue($seq, 'seq_angle_cal' . $index, 0) !== 0) $threadCalcu += $weight;
        }

        $seqRow = [
            'JOBID' => $jobId,
            'SEQID' => $seqId,
            'SEQname' => $this->stringRequired($seq, 'seq_name'),
            'seq_repeat' => $this->intValue($seq, 'seq_tightening_repeat', 1),
            'ng_stop' => $this->intValue($seq, 'seq_ng_stop', 0),
            'ok_seq' => $this->intValue($seq, 'seq_ok_seq', 1),
            'ok_stop' => $this->intValue($seq, 'seq_ok_seq_stop', 0),
            'timeout' => $this->intValue($seq, 'seq_timeout', 20),
            'unscrew_mode' => $this->intValue($seq, 'seq_unscrew_mode', 1),
            'unscrew_force' => $this->intValue($seq, 'seq_unscrew_force', 50),
            'unscrew_rpm' => $this->intValue($seq, 'seq_unscrew_rpm', 300),
            'unscrew_dir' => $this->intValue($seq, 'seq_unscrew_direction', 0),
            'unscrew_count_switch' => $this->intValue($seq, 'seq_reverse_count', 0),
            'unscrew_torque_threshold' => $this->torque($seq['seq_unscrew_thres_tor'] ?? 0, $torqueScale),
            'ng_unscrew' => $this->intValue($seq, 'seq_ng_reverse', 0),
            'accu_angle' => $this->intValue($seq, 'seq_accumulate_angle', 0),
            'Thread_Calcu' => $threadCalcu,
            'unscrew_angle_threshold' => $this->intValue($seq, 'seq_unscrew_thres_ang', 0),
            'total_angle_limit' => $this->intValue($seq, 'seq_total_angle_hi', 0),
            'total_angle_lower' => $this->intValue($seq, 'seq_total_angle_lo', 0),
        ];

        $stepRows = [];
        foreach (array_values($steps) as $index => $step) {
            if (!is_array($step)) throw new RuntimeException('MES steps[' . $index . '] 格式錯誤');
            $stepId = $this->intRequired($step, 'step_id', 1, 5);
            $option = $this->intValue($step, 'step_target_option', 2); // 0=Time, 1=Angle, 2=Torque
            $thresholdEnabled = $this->intValue($step, 'step_threshold_enable', 0) !== 0;
            $downshiftEnabled = $this->intValue($step, 'step_downshift_enable', 0) !== 0;

            // Controller DB 以 StepEnableThreshold/DownShift 表示觸發類型：1=Angle, 2=Torque。
            // 目標角度時優先使用角度門檻；其餘使用扭力門檻。
            $triggerMode = $option === 1 ? 1 : 2;
            $thresholdValue = $triggerMode === 1
                ? $this->number($step['step_threshold_angle'] ?? 0)
                : $this->torque($step['step_threshold_torque'] ?? 0, $torqueScale);
            $downshiftValue = $triggerMode === 1
                ? $this->number($step['step_downshift_angle'] ?? 0)
                : $this->torque($step['step_downshift_torque'] ?? 0, $torqueScale);

            $offset = $this->torque($step['step_offset'] ?? 0, $torqueScale);
            $offsetSign = $this->intValue($step, 'step_offset_sign', 0) === 1 ? 45 : 43; // '-' / '+' ASCII

            $stepRows[] = [
                'JOBID' => $jobId,
                'SEQID' => $seqId,
                'StepSelect' => $stepId,
                'STEPname' => trim((string)($step['step_name'] ?? ('STEP-' . $stepId))),
                'StepSwitch' => $this->intValue($step, 'step_id_enable', 1),
                'StepRPM' => $this->intValue($step, 'step_rpm', 0),
                'StepOption' => $option,
                'StepTime' => (int)round($this->number($step['step_target_time'] ?? 0) * 1000),
                'StepAngle' => $this->intValue($step, 'step_target_angle', 0),
                'StepTorque' => $this->torque($step['step_target_torque'] ?? 0, $torqueScale),
                'StepDirection' => $this->intValue($step, 'step_direction', 0),
                'StepDelay' => (int)round($this->number($step['step_delay_time'] ?? 0) * 1000),
                'StepMoniByWin' => ($this->number($step['step_hi_torque'] ?? 0) > 0 || $this->number($step['step_lo_torque'] ?? 0) > 0) ? 1 : 0,
                'StepHiAngle' => $this->intValue($step, 'step_hi_angle', 0),
                'StepLoAngle' => $this->intValue($step, 'step_lo_angle', 0),
                'StepHiTorque' => $this->torque($step['step_hi_torque'] ?? 0, $torqueScale),
                'StepLoTorque' => $this->torque($step['step_lo_torque'] ?? 0, $torqueScale),
                'StepEnableTorqueOffset' => $offset != 0.0 ? 1 : 0,
                'StepTorqueOffset' => $offset,
                'StepTorqueOffsetSign' => $offsetSign,
                'StepAccelerateOffset' => $this->number($step['step_step_acce_slope'] ?? 0),
                'StepEnableThreshold' => $thresholdEnabled ? $triggerMode : 0,
                'StepTorqueTS' => $thresholdEnabled ? $thresholdValue : 0,
                'StepEnableDownShift' => $downshiftEnabled ? $triggerMode : 0,
                'StepTorqueDownShift' => $downshiftEnabled ? $downshiftValue : 0,
                'StepRPMDownShift' => $this->intValue($step, 'step_downshift_speed', 0),
                'StepAngleRecord' => $this->intValue($step, 'step_step_record_angle', 0),
                'InterruptAlarm' => $this->intValue($step, 'step_interrupt_alarm', 1),
                'OverAngleStop' => $this->intValue($step, 'step_over_angle_stop', 1),
                'KValue' => $this->number($step['step_k_value'] ?? 0),
            ];
        }

        return ['JOB_lst' => [$jobRow], 'SEQ_lst' => [$seqRow], 'STEP_lst' => $stepRows];
    }

    private function getDeviceId(): string
    {
        if (!is_file(self::DEVICE_DB_PATH) || !is_readable(self::DEVICE_DB_PATH)) {
            throw new RuntimeException('無法讀取 ntcs_device.db');
        }
        try {
            $pdo = new PDO('sqlite:' . self::DEVICE_DB_PATH);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt = $pdo->query('SELECT device_id FROM ntcs_device_test WHERE device_id IS NOT NULL AND TRIM(CAST(device_id AS TEXT)) <> "" LIMIT 1');
            $deviceId = $stmt->fetchColumn();
        } catch (Throwable $e) {
            throw new RuntimeException('讀取控制器 device_id 失敗：' . $e->getMessage(), 0, $e);
        }
        if ($deviceId === false || trim((string)$deviceId) === '') {
            throw new RuntimeException('ntcs_device_test 找不到有效的 device_id');
        }
        return trim((string)$deviceId);
    }

    private function intRequired(array $row, string $key, int $min, int $max): int
    {
        if (!array_key_exists($key, $row) || filter_var($row[$key], FILTER_VALIDATE_INT) === false) {
            throw new RuntimeException('MES 配方欄位 ' . $key . ' 必須為整數');
        }
        $value = (int)$row[$key];
        if ($value < $min || $value > $max) throw new RuntimeException('MES 配方欄位 ' . $key . ' 超出範圍');
        return $value;
    }

    private function intValue(array $row, string $key, int $default): int
    {
        if (!array_key_exists($key, $row) || !is_numeric($row[$key])) return $default;
        return (int)$row[$key];
    }

    private function stringRequired(array $row, string $key): string
    {
        $value = trim((string)($row[$key] ?? ''));
        if ($value === '') throw new RuntimeException('MES 配方欄位 ' . $key . ' 不能為空');
        return $value;
    }

    private function number($value): float
    {
        return is_numeric($value) ? (float)$value : 0.0;
    }

    private function torque($value, float $scale): float
    {
        return round($this->number($value) / $scale, 3);
    }
}

final class HuarongMesResponseException extends RuntimeException
{
    private int $mesCode;
    private array $mesResponse;

    public function __construct(int $mesCode, string $message, array $mesResponse)
    {
        parent::__construct($message);
        $this->mesCode = $mesCode;
        $this->mesResponse = $mesResponse;
    }

    public function getMesCode(): int { return $this->mesCode; }
    public function getMesResponse(): array { return $this->mesResponse; }
}
