<?php

declare(strict_types=1);

require_once __DIR__ . '/JobConfigErrorCodes.php';

/** Converts controller probe/write responses into the public API result shape. */
final class JobConfigRemoteResultService
{
    public function status(string $ip, bool $sameSubnet, bool $reachable, bool $rootExists, $loggedOut, string $code, string $message, $network = null, array $extra = []): array
    {
        $ready = $sameSubnet && $reachable && $rootExists && $loggedOut === true;
        return [
            'ip' => $ip,
            'same_subnet' => $sameSubnet,
            'network' => is_array($network) ? $network['cidr'] : null,
            'reachable' => $reachable,
            'controller_root_exists' => $rootExists,
            'controller_logged_out' => $loggedOut,
            'ready' => $ready,
            'code' => $code,
            'message' => $message,
            'details' => $extra,
        ];
    }

    public function probeResponseToStatus(string $ip, $network, $response): array
    {
        if (!is_array($response)) {
            return $this->status($ip, true, false, false, null, JobConfigErrorCodes::NO_RESPONSE, 'No response from target', $network);
        }
        if (empty($response['transport_ok'])) {
            return $this->status($ip, true, false, false, null, JobConfigErrorCodes::TARGET_UNREACHABLE, (string)($response['error'] ?? 'Target is unreachable'), $network);
        }
        $json = isset($response['json']) && is_array($response['json']) ? $response['json'] : null;
        if ($json === null) {
            return $this->status($ip, true, true, false, null, JobConfigErrorCodes::PROBE_INVALID_RESPONSE, 'Target responded but the NTCS7 probe API is unavailable or invalid', $network, ['http_status' => $response['http_status'] ?? 0]);
        }
        $data = isset($json['data']) && is_array($json['data']) ? $json['data'] : [];
        if (($data['product'] ?? '') !== 'NTCS7' || (int)($data['api_protocol'] ?? 0) < 1) {
            return $this->status($ip, true, true, false, null, JobConfigErrorCodes::NOT_NTCS7, 'Target is reachable but did not identify as NTCS7', $network);
        }
        $rootExists = !empty($data['controller_root_exists']);
        $loggedOut = array_key_exists('controller_logged_out', $data) ? $data['controller_logged_out'] : null;
        $ready = !empty($json['success']) && !empty($data['ready']);
        $code = $ready ? JobConfigErrorCodes::READY : (string)($data['code'] ?? ($json['error']['code'] ?? JobConfigErrorCodes::TARGET_NOT_READY));
        $message = $ready ? 'Ready' : (string)($data['message'] ?? ($json['error']['message'] ?? 'Target is not ready'));
        return $this->status($ip, true, true, $rootExists, $loggedOut, $code, $message, $network, $this->sanitizePublicData($data));
    }

    public function sanitizePublicData($value)
    {
        if (!is_array($value)) return $value;
        foreach (['controller_database', 'controller_root', 'backup'] as $key) unset($value[$key]);
        foreach ($value as $key => $item) {
            if (is_array($item)) $value[$key] = $this->sanitizePublicData($item);
        }
        return $value;
    }

    /**
     * Read operation details from the current flat response or the legacy
     * top-level data object. A remote endpoint normally returns one result,
     * but matching by IP keeps the parser safe if that changes later.
     */
    public function operationResultData(array $json, ?string $targetIp = null): array
    {
        if (isset($json['data']) && is_array($json['data'])) {
            return $this->sanitizePublicData($json['data']);
        }

        $fallback = null;
        foreach (isset($json['results']) && is_array($json['results']) ? $json['results'] : [] as $result) {
            if (!is_array($result) || !isset($result['data']) || !is_array($result['data'])) continue;
            if ($fallback === null && !empty($result['success'])) $fallback = $result['data'];
            if ($targetIp !== null && (string)($result['ip'] ?? '') === $targetIp) {
                return $this->sanitizePublicData($result['data']);
            }
        }

        return $fallback === null ? [] : $this->sanitizePublicData($fallback);
    }

    public function writeResponseToResult(string $ip, $response): array
    {
        $elapsedMs = is_array($response) ? (int)($response['elapsed_ms'] ?? 0) : 0;
        if (is_array($response) && isset($response['json']['elapsed_ms'])) $elapsedMs = (int)$response['json']['elapsed_ms'];
        if (!is_array($response) || empty($response['transport_ok'])) {
            return [
                'ip' => $ip,
                'success' => false,
                'code' => JobConfigErrorCodes::TARGET_UNREACHABLE,
                'message' => is_array($response) ? (string)($response['error'] ?? 'Target is unreachable') : 'No response from target',
                'elapsed_ms' => $elapsedMs,
            ];
        }
        $json = isset($response['json']) && is_array($response['json']) ? $response['json'] : null;
        if ($json === null) {
            return [
                'ip' => $ip,
                'success' => false,
                'code' => JobConfigErrorCodes::WRITE_INVALID_RESPONSE,
                'message' => 'Target did not return a valid JSON write response',
                'http_status' => $response['http_status'] ?? 0,
                'elapsed_ms' => $elapsedMs,
            ];
        }
        if (!empty($json['success'])) {
            return [
                'ip' => $ip,
                'success' => true,
                'code' => JobConfigErrorCodes::WRITE_OK,
                'message' => 'Write complete',
                'data' => $this->operationResultData($json, $ip),
                'elapsed_ms' => $elapsedMs,
            ];
        }
        return [
            'ip' => $ip,
            'success' => false,
            'code' => (string)($json['error']['code'] ?? JobConfigErrorCodes::REMOTE_WRITE_FAILED),
            'message' => (string)($json['error']['message'] ?? 'Remote write failed'),
            'data' => $this->sanitizePublicData($json),
            'elapsed_ms' => $elapsedMs,
        ];
    }
}
