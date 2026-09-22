<?php

declare(strict_types=1);

/** 華榮 MES 接口 2 HTTP Sender。 */
final class HuarongTighteningSenderService
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(array $payload): array
    {
        if (!empty($this->config['mock_enabled'])) {
            $file = (string)($this->config['report_mock_response_file'] ?? '');
            if ($file === '' || !is_file($file) || !is_readable($file)) {
                throw new RuntimeException('華榮 MES 接口 2 Mock Response 檔案不存在或無法讀取');
            }
            $response = json_decode((string)file_get_contents($file), true);
            if (!is_array($response) || json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('華榮 MES 接口 2 Mock Response JSON 格式錯誤');
            }
            if (isset($response['data']) && is_array($response['data'])) {
                $response['data']['SN'] = (string)($payload['SN'] ?? '');
                $response['data']['receive_time'] = date('Y-m-d H:i:s');
            }
            return ['http_code' => 200, 'response' => $response];
        }

        $baseUrl = rtrim(trim((string)($this->config['base_url'] ?? '')), '/');
        if ($baseUrl === '') throw new RuntimeException('華榮 MES Base URL 尚未設定');
        if (!function_exists('curl_init')) throw new RuntimeException('PHP cURL extension 未安裝');

        $url = $baseUrl . '/' . ltrim((string)($this->config['report_path'] ?? '/api/report_tightening_result'), '/');
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) throw new RuntimeException('接口 2 JSON 建立失敗');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int)($this->config['connect_timeout'] ?? 3),
            CURLOPT_TIMEOUT => (int)($this->config['timeout'] ?? 5),
        ]);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) throw new RuntimeException('MES 連線失敗：' . $error);
        if ($httpCode < 200 || $httpCode >= 300) throw new RuntimeException('MES HTTP 狀態異常：' . $httpCode);
        $response = json_decode((string)$body, true);
        if (!is_array($response) || json_last_error() !== JSON_ERROR_NONE) throw new RuntimeException('MES Response 不是有效 JSON');
        return ['http_code' => $httpCode, 'response' => $response];
    }
}
