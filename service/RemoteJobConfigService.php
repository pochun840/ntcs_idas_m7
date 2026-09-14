<?php

require_once __DIR__ . '/JobConfigErrorCodes.php';


/**
 * LAN-only helper for checking and writing JOB / SEQ / STEP configuration to
 * multiple NTCS7 controllers.  Browser traffic stays on the local iDAS server;
 * this service performs the server-to-server HTTP requests.
 *
 * PHP 7.x compatible.
 */
final class RemoteJobConfigService
{
    private const MAX_TARGETS = 253;
    private const CONCURRENCY = 5;
    private const CONNECT_TIMEOUT_SECONDS = 2;
    private const PROBE_TIMEOUT_SECONDS = 3;
    private const PREVIEW_TIMEOUT_SECONDS = 8;
    private const WRITE_TIMEOUT_SECONDS = 20;
    private const RETRY_COUNT = 2;

    private $idasBaseUrl;
    private $commandRunner;
    private $localPreflight;
    private $localWrite;
    private $localPreview;
    private $networks;

    public function __construct(string $idasBaseUrl, callable $localPreflight, callable $localWrite, ?callable $commandRunner = null, ?callable $localPreview = null)
    {
        $this->idasBaseUrl = '/' . trim($idasBaseUrl, '/');
        if ($this->idasBaseUrl === '/') $this->idasBaseUrl = '/idas';
        $this->localPreflight = $localPreflight;
        $this->localWrite = $localWrite;
        $this->localPreview = $localPreview;
        $this->commandRunner = $commandRunner ?: static function ($command) {
            $output = @shell_exec($command);
            return is_string($output) ? $output : '';
        };
    }

    public static function maxTargets(): int { return self::MAX_TARGETS; }
    public static function concurrency(): int { return self::CONCURRENCY; }

    public function networkInfo(): array
    {
        $networks = $this->getLocalNetworks();
        return [
            'networks' => $networks,
            'preferred_ip' => isset($networks[0]['ip']) ? $networks[0]['ip'] : null,
            'max_targets' => self::MAX_TARGETS,
            'concurrency' => self::CONCURRENCY,
        ];
    }

    public function normalizeTargets($targets): array
    {
        if (!is_array($targets) || $targets === []) {
            throw new InvalidArgumentException('At least one target IPv4 address is required');
        }

        $result = [];
        foreach ($targets as $target) {
            $ip = trim((string)$target);
            if ($ip === '') continue;
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                throw new InvalidArgumentException('Invalid target IPv4 address: ' . $ip);
            }
            if ($ip === '0.0.0.0' || strpos($ip, '127.') === 0 || strpos($ip, '169.254.') === 0 || $ip === '192.168.7.7') {
                throw new InvalidArgumentException('Target IPv4 address is not allowed: ' . $ip);
            }
            $result[$ip] = $ip;
        }

        $result = array_values($result);
        if ($result === []) throw new InvalidArgumentException('At least one target IPv4 address is required');
        if (count($result) > self::MAX_TARGETS) {
            throw new InvalidArgumentException('At most ' . self::MAX_TARGETS . ' target controllers are allowed');
        }
        return $result;
    }

    /**
     * Pre-check every target.  No write is performed here.
     */
    public function checkTargets(array $targets): array
    {
        $targets = $this->normalizeTargets($targets);
        $networks = $this->getLocalNetworks();
        if ($networks === []) {
            throw new RuntimeException('No active IPv4 network was found');
        }

        $results = [];
        $remoteRequests = [];

        foreach ($targets as $ip) {
            $match = $this->matchedNetwork($ip, $networks);
            if ($match === null) {
                $results[$ip] = $this->status($ip, false, false, false, false, JobConfigErrorCodes::NOT_SAME_SUBNET, 'Target is not on an active local subnet', null);
                continue;
            }
            if (!$this->isUsableHostAddress($ip, $match)) {
                $results[$ip] = $this->status($ip, true, false, false, false, 'INVALID_HOST_ADDRESS', 'Network or broadcast address cannot be used', $match);
                continue;
            }

            if ($this->isLocalIp($ip, $networks)) {
                try {
                    $data = call_user_func($this->localPreflight);
                    $results[$ip] = $this->status($ip, true, true, true, !empty($data['controller_logged_out']), JobConfigErrorCodes::READY, 'Ready', $match, $data);
                } catch (Throwable $e) {
                    $code = method_exists($e, 'getErrorCode') ? (string)$e->getErrorCode() : JobConfigErrorCodes::LOCAL_PREFLIGHT_FAILED;
                    $rootOk = $code !== JobConfigErrorCodes::CONTROLLER_ROOT_NOT_FOUND;
                    $loggedOut = $code === JobConfigErrorCodes::CONTROLLER_IN_USE ? false : null;
                    $results[$ip] = $this->status($ip, true, true, $rootOk, $loggedOut, $code, $e->getMessage(), $match);
                }
                continue;
            }

            $remoteRequests[$ip] = [
                'method' => 'GET',
                'url' => $this->remoteUrl($ip, '/api/remote_controller_probe.php?request=' . rawurlencode((string)microtime(true))),
                'body' => null,
                'timeout' => self::PROBE_TIMEOUT_SECONDS,
            ];
        }

        if ($remoteRequests !== []) {
            $responses = $this->requestMany($remoteRequests);
            foreach ($remoteRequests as $ip => $unused) {
                $match = $this->matchedNetwork($ip, $networks);
                $response = isset($responses[$ip]) ? $responses[$ip] : null;
                $results[$ip] = $this->probeResponseToStatus($ip, $match, $response);
            }
        }

        $ordered = [];
        foreach ($targets as $ip) {
            if (!isset($results[$ip])) {
                $match = $this->matchedNetwork($ip, $networks);
                $results[$ip] = $this->status(
                    $ip,
                    $match !== null,
                    false,
                    false,
                    null,
                    JobConfigErrorCodes::NO_RESPONSE,
                    'No check result was returned for target',
                    $match
                );
            }
            $ordered[] = $results[$ip];
        }
        return $ordered;
    }

    public function previewTargets(array $targets, array $payload): array
    {
        $targets = $this->normalizeTargets($targets);
        $checks = $this->checkTargets($targets);
        $networks = $this->getLocalNetworks();
        $results = [];
        $remoteRequests = [];

        foreach ($checks as $check) {
            $ip = (string)($check['ip'] ?? '');
            if ($ip === '') continue;
            if (empty($check['ready'])) {
                $results[$ip] = ['ip'=>$ip,'success'=>false,'skipped'=>true,'code'=>JobConfigErrorCodes::SKIPPED_NOT_READY,'message'=>(string)($check['message'] ?? 'Target is not ready')];
                continue;
            }
            if ($this->isLocalIp($ip, $networks)) {
                try {
                    if (!is_callable($this->localPreview)) throw new RuntimeException('Local preview is unavailable');
                    $data = call_user_func($this->localPreview, $payload);
                    $results[$ip] = ['ip'=>$ip,'success'=>true,'skipped'=>false,'code'=>JobConfigErrorCodes::PREVIEW_OK,'message'=>'Preview complete','data'=>$data];
                } catch (Throwable $e) {
                    $results[$ip] = ['ip'=>$ip,'success'=>false,'skipped'=>false,'code'=>method_exists($e,'getErrorCode')?(string)$e->getErrorCode():JobConfigErrorCodes::PREVIEW_FAILED,'message'=>$e->getMessage()];
                }
                continue;
            }
            $remoteRequests[$ip] = [
                'method' => 'POST',
                'url' => $this->remoteUrl($ip, '/api/replace_job_config.php?request=' . rawurlencode((string)microtime(true))),
                'body' => json_encode(array_merge(['api_version'=>1,'action'=>'preview'], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => self::PREVIEW_TIMEOUT_SECONDS,
            ];
        }

        if ($remoteRequests !== []) {
            $responses = $this->requestMany($remoteRequests);
            foreach ($remoteRequests as $ip => $unused) {
                $response = $responses[$ip] ?? null;
                if (!is_array($response) || empty($response['transport_ok'])) {
                    $results[$ip] = ['ip'=>$ip,'success'=>false,'skipped'=>false,'code'=>JobConfigErrorCodes::TARGET_UNREACHABLE,'message'=>'Target is unreachable'];
                    continue;
                }
                $json = isset($response['json']) && is_array($response['json']) ? $response['json'] : null;
                if ($json === null || empty($json['success'])) {
                    $results[$ip] = ['ip'=>$ip,'success'=>false,'skipped'=>false,'code'=>(string)($json['error']['code'] ?? JobConfigErrorCodes::PREVIEW_FAILED),'message'=>(string)($json['error']['message'] ?? 'Preview failed')];
                    continue;
                }
                $results[$ip] = ['ip'=>$ip,'success'=>true,'skipped'=>false,'code'=>JobConfigErrorCodes::PREVIEW_OK,'message'=>'Preview complete','data'=>$this->sanitizePublicData($json['data'] ?? [])];
            }
        }

        $ordered=[];$success=0;$skipped=0;$failed=0;
        foreach ($targets as $ip) {
            if (!isset($results[$ip])) $results[$ip]=['ip'=>$ip,'success'=>false,'skipped'=>true,'code'=>JobConfigErrorCodes::SKIPPED_NOT_READY,'message'=>'Target is not ready'];
            $ordered[]=$results[$ip];
            if (!empty($results[$ip]['success'])) $success++; elseif (!empty($results[$ip]['skipped'])) $skipped++; else $failed++;
        }
        return ['success'=>$success===count($targets),'any_success'=>$success>0,'total_count'=>count($targets),'success_count'=>$success,'failed_count'=>$failed,'skipped_count'=>$skipped,'summary'=>['total'=>count($targets),'ready'=>$success,'success'=>$success,'failed'=>$failed,'skipped'=>$skipped],'checks'=>$checks,'results'=>$ordered];
    }

    /**
     * Re-check every target immediately before the write.  Readiness is handled
     * per controller: a target that fails the subnet / connectivity /
     * /home/kls/NTCS7 / logout checks is skipped, while every target that is
     * ready is still written.  A bad controller must never block a good one.
     */
    public function writeTargets(array $targets, array $payload): array
    {
        $batchStartedAt = microtime(true);
        $targets = $this->normalizeTargets($targets);
        $checks = $this->checkTargets($targets);
        $readyTargets = [];
        $results = [];

        foreach ($checks as $check) {
            $ip = (string)($check['ip'] ?? '');
            if ($ip === '') continue;
            if (!empty($check['ready'])) {
                $readyTargets[] = $ip;
                continue;
            }
            $results[$ip] = [
                'ip' => $ip,
                'success' => false,
                'skipped' => true,
                'code' => JobConfigErrorCodes::SKIPPED_NOT_READY,
                'message' => (string)($check['message'] ?? 'Target is not ready'),
                'preflight_code' => (string)($check['code'] ?? JobConfigErrorCodes::TARGET_NOT_READY),
                'elapsed_ms' => 0,
            ];
        }

        if ($readyTargets === []) {
            $ordered = [];
            foreach ($targets as $ip) {
                if (isset($results[$ip])) $ordered[] = $results[$ip];
            }
            return [
                'success' => false,
                'any_success' => false,
                'partial_success' => false,
                'no_ready_targets' => true,
                'preflight_failed' => true,
                'total_count' => count($targets),
                'ready_count' => 0,
                'success_count' => 0,
                'failed_count' => 0,
                'skipped_count' => count($ordered),
                'summary' => ['total'=>count($targets),'ready'=>0,'success'=>0,'failed'=>0,'skipped'=>count($ordered)],
                'checks' => $checks,
                'results' => $ordered,
                'elapsed_ms' => max(0, (int)round((microtime(true) - $batchStartedAt) * 1000)),
            ];
        }

        $networks = $this->getLocalNetworks();
        $remoteRequests = [];

        foreach ($readyTargets as $ip) {
            if ($this->isLocalIp($ip, $networks)) {
                $targetStartedAt = microtime(true);
                try {
                    $data = call_user_func($this->localWrite, $payload);
                    $results[$ip] = [
                        'ip' => $ip,
                        'success' => true,
                        'skipped' => false,
                        'code' => JobConfigErrorCodes::WRITE_OK,
                        'message' => 'Write complete',
                        'data' => $data,
                        'elapsed_ms' => max(0, (int)round((microtime(true) - $targetStartedAt) * 1000)),
                    ];
                } catch (Throwable $e) {
                    $results[$ip] = [
                        'ip' => $ip,
                        'success' => false,
                        'skipped' => false,
                        'code' => method_exists($e, 'getErrorCode') ? (string)$e->getErrorCode() : JobConfigErrorCodes::LOCAL_WRITE_FAILED,
                        'message' => $e->getMessage(),
                        'elapsed_ms' => max(0, (int)round((microtime(true) - $targetStartedAt) * 1000)),
                    ];
                }
                continue;
            }

            $remoteRequests[$ip] = [
                'method' => 'POST',
                'url' => $this->remoteUrl($ip, '/api/replace_job_config.php?request=' . rawurlencode((string)microtime(true))),
                'body' => json_encode(array_merge(['api_version'=>1,'action'=>'write'], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => self::WRITE_TIMEOUT_SECONDS,
            ];
        }

        if ($remoteRequests !== []) {
            $responses = $this->requestMany($remoteRequests);
            foreach ($remoteRequests as $ip => $unused) {
                $response = isset($responses[$ip]) ? $responses[$ip] : null;
                $result = $this->writeResponseToResult($ip, $response);
                $result['skipped'] = false;
                $results[$ip] = $result;
            }
        }

        $ordered = [];
        $successCount = 0;
        $failedCount = 0;
        $skippedCount = 0;
        foreach ($targets as $ip) {
            if (!isset($results[$ip])) {
                $results[$ip] = [
                    'ip' => $ip,
                    'success' => false,
                    'skipped' => true,
                    'code' => JobConfigErrorCodes::SKIPPED_NOT_READY,
                    'message' => 'Target is not ready',
                    'elapsed_ms' => 0,
                ];
            }
            $ordered[] = $results[$ip];
            if (!empty($results[$ip]['success'])) {
                $successCount++;
            } elseif (!empty($results[$ip]['skipped'])) {
                $skippedCount++;
            } else {
                $failedCount++;
            }
        }

        $allSuccess = $successCount === count($targets);
        $anySuccess = $successCount > 0;

        return [
            'success' => $allSuccess,
            'any_success' => $anySuccess,
            'partial_success' => $anySuccess && !$allSuccess,
            'no_ready_targets' => false,
            'preflight_failed' => false,
            'total_count' => count($targets),
            'ready_count' => count($readyTargets),
            'success_count' => $successCount,
            'failed_count' => $failedCount,
            'skipped_count' => $skippedCount,
            'summary' => ['total'=>count($targets),'ready'=>count($readyTargets),'success'=>$successCount,'failed'=>$failedCount,'skipped'=>$skippedCount],
            'checks' => $checks,
            'results' => $ordered,
            'elapsed_ms' => max(0, (int)round((microtime(true) - $batchStartedAt) * 1000)),
        ];
    }

    public function isRemoteAddressOnLocalSubnet(string $remoteAddress): bool
    {
        if (strpos($remoteAddress, '::ffff:') === 0) $remoteAddress = substr($remoteAddress, 7);
        if ($remoteAddress === '127.0.0.1' || $remoteAddress === '::1') return true;
        if (!filter_var($remoteAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
        return $this->matchedNetwork($remoteAddress, $this->getLocalNetworks()) !== null;
    }

    private function getLocalNetworks(): array
    {
        if (is_array($this->networks)) return $this->networks;
        $run = $this->commandRunner;
        $ipBinary = is_executable('/sbin/ip') ? '/sbin/ip' : (is_executable('/usr/sbin/ip') ? '/usr/sbin/ip' : 'ip');
        $output = (string)call_user_func($run, $ipBinary . ' -o -4 addr show up scope global 2>/dev/null');
        $routeOutput = (string)call_user_func($run, $ipBinary . ' -o -4 route get 1.1.1.1 2>/dev/null');
        $preferredIp = null;
        if (preg_match('/\bsrc\s+([0-9.]+)/', $routeOutput, $m)) $preferredIp = $m[1];

        $networks = [];
        foreach (preg_split('/\r?\n/', trim($output)) ?: [] as $line) {
            if (!preg_match('/^\d+:\s+([^\s]+)\s+inet\s+([0-9.]+)\/(\d+)/', trim($line), $m)) continue;
            $iface = preg_replace('/@.*$/', '', (string)$m[1]);
            $ip = (string)$m[2];
            $prefix = (int)$m[3];
            if ($prefix < 1 || $prefix > 32) continue;
            if (preg_match('/^(lo|docker\d*|br-|veth|virbr|tun|tap|wg|tailscale)/i', $iface)) continue;
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) continue;
            if (strpos($ip, '127.') === 0 || strpos($ip, '169.254.') === 0 || $ip === '192.168.7.7') continue;
            $networks[$ip . '/' . $prefix] = [
                'interface' => $iface,
                'ip' => $ip,
                'prefix' => $prefix,
                'netmask' => $this->prefixToNetmask($prefix),
                'network' => $this->networkAddress($ip, $prefix),
                'broadcast' => $this->broadcastAddress($ip, $prefix),
                'cidr' => $this->networkAddress($ip, $prefix) . '/' . $prefix,
            ];
        }

        $networks = array_values($networks);
        usort($networks, static function ($a, $b) use ($preferredIp) {
            if ($preferredIp !== null) {
                if ($a['ip'] === $preferredIp && $b['ip'] !== $preferredIp) return -1;
                if ($b['ip'] === $preferredIp && $a['ip'] !== $preferredIp) return 1;
            }
            return strcmp($a['interface'], $b['interface']);
        });
        $this->networks = $networks;
        return $networks;
    }

    private function matchedNetwork(string $targetIp, array $networks)
    {
        foreach ($networks as $network) {
            if ($this->sameSubnet($targetIp, $network['ip'], (int)$network['prefix'])) return $network;
        }
        return null;
    }

    private function isLocalIp(string $ip, array $networks): bool
    {
        foreach ($networks as $network) if ($network['ip'] === $ip) return true;
        return false;
    }

    private function isUsableHostAddress(string $ip, array $network): bool
    {
        $prefix = (int)$network['prefix'];
        if ($prefix >= 31) return true;
        return $ip !== $network['network'] && $ip !== $network['broadcast'];
    }

    private function sameSubnet(string $a, string $b, int $prefix): bool
    {
        $la = ip2long($a);
        $lb = ip2long($b);
        if ($la === false || $lb === false) return false;
        if ($prefix <= 0) return true;
        $mask = (0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF;
        return (($la & $mask) === ($lb & $mask));
    }

    private function prefixToNetmask(int $prefix): string
    {
        $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);
        return long2ip($mask);
    }

    private function networkAddress(string $ip, int $prefix): string
    {
        $long = ip2long($ip);
        $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);
        return long2ip($long & $mask);
    }

    private function broadcastAddress(string $ip, int $prefix): string
    {
        $long = ip2long($ip);
        $mask = $prefix === 0 ? 0 : ((0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF);
        return long2ip(($long & $mask) | ((~$mask) & 0xFFFFFFFF));
    }

    private function remoteUrl(string $ip, string $path): string
    {
        return 'http://' . $ip . $this->idasBaseUrl . $path;
    }

    private function status(string $ip, bool $sameSubnet, bool $reachable, bool $rootExists, $loggedOut, string $code, string $message, $network = null, array $extra = []): array
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

    private function probeResponseToStatus(string $ip, $network, $response): array
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

    private function sanitizePublicData($value)
    {
        if (!is_array($value)) return $value;
        foreach (['controller_database', 'controller_root', 'backup'] as $key) unset($value[$key]);
        foreach ($value as $key => $item) {
            if (is_array($item)) $value[$key] = $this->sanitizePublicData($item);
        }
        return $value;
    }

    private function writeResponseToResult(string $ip, $response): array
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
                'data' => $this->sanitizePublicData($json['data'] ?? []),
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

    /**
     * Execute HTTP requests in groups of five.  cURL multi is preferred; a
     * stream fallback keeps older installations functional when cURL is absent.
     */
    private function requestMany(array $requests): array
    {
        if (function_exists('curl_multi_init') && function_exists('curl_init')) {
            return $this->requestManyCurl($requests);
        }
        $results = [];
        foreach ($requests as $key => $request) {
            $result = $this->requestOneStream($request);
            for ($retry = 0; $retry < self::RETRY_COUNT && $this->shouldRetryTransport($result); $retry++) {
                usleep((int)(250000 * ($retry + 1)));
                $result = $this->requestOneStream($request);
            }
            $results[$key] = $result;
        }
        return $results;
    }

    private function requestManyCurl(array $requests): array
    {
        $results = [];
        $keys = array_keys($requests);
        for ($offset = 0; $offset < count($keys); $offset += self::CONCURRENCY) {
            $batchKeys = array_slice($keys, $offset, self::CONCURRENCY);
            $multi = curl_multi_init();
            $handles = [];
            foreach ($batchKeys as $key) {
                $request = $requests[$key];
                $ch = curl_init();
                $headers = ['Accept: application/json', 'Connection: close'];
                $options = [
                    CURLOPT_URL => $request['url'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
                    CURLOPT_TIMEOUT => (int)($request['timeout'] ?? self::WRITE_TIMEOUT_SECONDS),
                    CURLOPT_HTTPHEADER => $headers,
                ];
                if ($request['method'] === 'POST') {
                    $headers[] = 'Content-Type: application/json';
                    $options[CURLOPT_HTTPHEADER] = $headers;
                    $options[CURLOPT_POST] = true;
                    $options[CURLOPT_POSTFIELDS] = (string)$request['body'];
                }
                curl_setopt_array($ch, $options);
                curl_multi_add_handle($multi, $ch);
                $handles[$key] = $ch;
            }

            do {
                $status = curl_multi_exec($multi, $running);
                if ($running) curl_multi_select($multi, 0.2);
            } while ($running && $status === CURLM_OK);

            foreach ($handles as $key => $ch) {
                $body = curl_multi_getcontent($ch);
                $error = curl_error($ch);
                $httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $elapsedMs = max(0, (int)round(((float)curl_getinfo($ch, CURLINFO_TOTAL_TIME)) * 1000));
                $transportOk = $error === '' && $body !== false && $httpStatus > 0;
                $json = $transportOk ? json_decode((string)$body, true) : null;
                $results[$key] = [
                    'transport_ok' => $transportOk,
                    'http_status' => $httpStatus,
                    'body' => is_string($body) ? $body : '',
                    'json' => is_array($json) ? $json : null,
                    'error' => $error,
                    'elapsed_ms' => $elapsedMs,
                ];
                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);
            }
            curl_multi_close($multi);
            foreach ($batchKeys as $key) {
                if (!$this->shouldRetryTransport($results[$key])) continue;
                for ($retry = 0; $retry < self::RETRY_COUNT; $retry++) {
                    usleep((int)(250000 * ($retry + 1)));
                    $retryResult = $this->requestOneStream($requests[$key]);
                    if (!$this->shouldRetryTransport($retryResult)) { $results[$key] = $retryResult; break; }
                    $results[$key] = $retryResult;
                }
            }
        }
        return $results;
    }


    /** Retry only transport/network failures. HTTP/application errors are final. */
    private function shouldRetryTransport($result): bool
    {
        return !is_array($result) || empty($result['transport_ok']);
    }

    private function requestOneStream(array $request): array
    {
        $startedAt = microtime(true);
        $headers = "Accept: application/json\r\nConnection: close\r\n";
        if ($request['method'] === 'POST') $headers .= "Content-Type: application/json\r\n";
        $context = stream_context_create([
            'http' => [
                'method' => $request['method'],
                'header' => $headers,
                'content' => $request['method'] === 'POST' ? (string)$request['body'] : '',
                'timeout' => (int)($request['timeout'] ?? self::WRITE_TIMEOUT_SECONDS),
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);
        $body = @file_get_contents($request['url'], false, $context);
        $httpStatus = 0;
        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $httpStatus = (int)$m[1];
        }
        $transportOk = $body !== false && $httpStatus > 0;
        $json = $transportOk ? json_decode((string)$body, true) : null;
        return [
            'transport_ok' => $transportOk,
            'http_status' => $httpStatus,
            'body' => is_string($body) ? $body : '',
            'json' => is_array($json) ? $json : null,
            'error' => $transportOk ? '' : 'HTTP connection failed',
            'elapsed_ms' => max(0, (int)round((microtime(true) - $startedAt) * 1000)),
        ];
    }
}
