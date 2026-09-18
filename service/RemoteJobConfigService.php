<?php

require_once __DIR__ . '/JobConfigErrorCodes.php';
require_once __DIR__ . '/JobConfigApiConfig.php';
require_once __DIR__ . '/JobConfigNetworkService.php';
require_once __DIR__ . '/JobConfigHttpClient.php';
require_once __DIR__ . '/JobConfigRemoteResultService.php';


/**
 * LAN-only helper for checking and writing JOB / SEQ / STEP configuration to
 * multiple NTCS7 controllers.  Browser traffic stays on the local iDAS server;
 * this service performs the server-to-server HTTP requests.
 *
 * PHP 7.x compatible.
 */
final class RemoteJobConfigService
{
    private const PROBE_TIMEOUT_SECONDS = 3;
    private const PREVIEW_TIMEOUT_SECONDS = 8;
    private const WRITE_TIMEOUT_SECONDS = 20;

    private $idasBaseUrl;
    private $localPreflight;
    private $localWrite;
    private $localPreview;
    private $networkService;
    private $httpClient;
    private $resultService;

    public function __construct(string $idasBaseUrl, callable $localPreflight, callable $localWrite, ?callable $commandRunner = null, ?callable $localPreview = null)
    {
        $this->idasBaseUrl = '/' . trim($idasBaseUrl, '/');
        if ($this->idasBaseUrl === '/') $this->idasBaseUrl = '/idas';
        $this->localPreflight = $localPreflight;
        $this->localWrite = $localWrite;
        $this->localPreview = $localPreview;
        $this->networkService = new JobConfigNetworkService($commandRunner);
        $this->httpClient = new JobConfigHttpClient();
        $this->resultService = new JobConfigRemoteResultService();
    }

    public static function maxTargets(): int { return JobConfigNetworkService::maxTargets(); }
    public static function concurrency(): int { return JobConfigHttpClient::concurrency(); }

    public function networkInfo(): array
    {
        return array_merge($this->networkService->networkInfo(), [
            'concurrency' => JobConfigHttpClient::concurrency(),
        ]);
    }

    public function normalizeTargets($targets): array
    {
        return $this->networkService->normalizeTargets($targets);
    }

    /**
     * Pre-check every target.  No write is performed here.
     */
    public function checkTargets(array $targets): array
    {
        $targets = $this->normalizeTargets($targets);
        $networks = $this->networkService->localNetworks();
        if ($networks === []) {
            throw new RuntimeException('No active IPv4 network was found');
        }

        $results = [];
        $remoteRequests = [];

        foreach ($targets as $ip) {
            $match = $this->networkService->matchedNetwork($ip, $networks);
            if ($match === null) {
                $results[$ip] = $this->resultService->status($ip, false, false, false, false, JobConfigErrorCodes::NOT_SAME_SUBNET, 'Target is not on an active local subnet', null);
                continue;
            }
            if (!$this->networkService->isUsableHostAddress($ip, $match)) {
                $results[$ip] = $this->resultService->status($ip, true, false, false, false, 'INVALID_HOST_ADDRESS', 'Network or broadcast address cannot be used', $match);
                continue;
            }

            if ($this->networkService->isLocalIp($ip, $networks)) {
                try {
                    $data = call_user_func($this->localPreflight);
                    $results[$ip] = $this->resultService->status($ip, true, true, true, !empty($data['controller_logged_out']), JobConfigErrorCodes::READY, 'Ready', $match, $data);
                } catch (Throwable $e) {
                    $code = method_exists($e, 'getErrorCode') ? (string)$e->getErrorCode() : JobConfigErrorCodes::LOCAL_PREFLIGHT_FAILED;
                    $rootOk = $code !== JobConfigErrorCodes::CONTROLLER_ROOT_NOT_FOUND;
                    $loggedOut = $code === JobConfigErrorCodes::CONTROLLER_IN_USE ? false : null;
                    $results[$ip] = $this->resultService->status($ip, true, true, $rootOk, $loggedOut, $code, $e->getMessage(), $match);
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
            $responses = $this->httpClient->requestMany($remoteRequests);
            foreach ($remoteRequests as $ip => $unused) {
                $match = $this->networkService->matchedNetwork($ip, $networks);
                $response = isset($responses[$ip]) ? $responses[$ip] : null;
                $results[$ip] = $this->resultService->probeResponseToStatus($ip, $match, $response);
            }
        }

        $ordered = [];
        foreach ($targets as $ip) {
            if (!isset($results[$ip])) {
                $match = $this->networkService->matchedNetwork($ip, $networks);
                $results[$ip] = $this->resultService->status(
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
        $networks = $this->networkService->localNetworks();
        $results = [];
        $remoteRequests = [];

        foreach ($checks as $check) {
            $ip = (string)($check['ip'] ?? '');
            if ($ip === '') continue;
            if (empty($check['ready'])) {
                $results[$ip] = ['ip'=>$ip,'success'=>false,'skipped'=>true,'code'=>JobConfigErrorCodes::SKIPPED_NOT_READY,'message'=>(string)($check['message'] ?? 'Target is not ready')];
                continue;
            }
            if ($this->networkService->isLocalIp($ip, $networks)) {
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
                'body' => json_encode(array_merge(['api_version'=>JobConfigApiConfig::VERSION,'action'=>'preview'], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => self::PREVIEW_TIMEOUT_SECONDS,
            ];
        }

        if ($remoteRequests !== []) {
            $responses = $this->httpClient->requestMany($remoteRequests);
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
                $results[$ip] = ['ip'=>$ip,'success'=>true,'skipped'=>false,'code'=>JobConfigErrorCodes::PREVIEW_OK,'message'=>'Preview complete','data'=>$this->resultService->operationResultData($json, $ip)];
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

        $networks = $this->networkService->localNetworks();
        $remoteRequests = [];

        foreach ($readyTargets as $ip) {
            if ($this->networkService->isLocalIp($ip, $networks)) {
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
                'body' => json_encode(array_merge(['api_version'=>JobConfigApiConfig::VERSION,'action'=>'write'], $payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => self::WRITE_TIMEOUT_SECONDS,
            ];
        }

        if ($remoteRequests !== []) {
            $responses = $this->httpClient->requestMany($remoteRequests);
            foreach ($remoteRequests as $ip => $unused) {
                $response = isset($responses[$ip]) ? $responses[$ip] : null;
                $result = $this->resultService->writeResponseToResult($ip, $response);
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
        return $this->networkService->isRemoteAddressOnLocalSubnet($remoteAddress);
    }

    private function remoteUrl(string $ip, string $path): string
    {
        return 'http://' . $ip . $this->idasBaseUrl . $path;
    }


}
