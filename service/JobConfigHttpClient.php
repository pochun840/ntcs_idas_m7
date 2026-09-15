<?php

declare(strict_types=1);

/** Executes bounded, retryable JSON HTTP requests to controller APIs. */
final class JobConfigHttpClient
{
    private const CONCURRENCY = 5;
    private const CONNECT_TIMEOUT_SECONDS = 2;
    private const DEFAULT_TIMEOUT_SECONDS = 20;
    private const RETRY_COUNT = 2;

    public static function concurrency(): int
    {
        return self::CONCURRENCY;
    }

    public function requestMany(array $requests): array
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
                $handle = curl_init();
                $headers = ['Accept: application/json', 'Connection: close'];
                $options = [
                    CURLOPT_URL => $request['url'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
                    CURLOPT_TIMEOUT => (int)($request['timeout'] ?? self::DEFAULT_TIMEOUT_SECONDS),
                    CURLOPT_HTTPHEADER => $headers,
                ];
                if ($request['method'] === 'POST') {
                    $headers[] = 'Content-Type: application/json';
                    $options[CURLOPT_HTTPHEADER] = $headers;
                    $options[CURLOPT_POST] = true;
                    $options[CURLOPT_POSTFIELDS] = (string)$request['body'];
                }
                curl_setopt_array($handle, $options);
                curl_multi_add_handle($multi, $handle);
                $handles[$key] = $handle;
            }

            do {
                $status = curl_multi_exec($multi, $running);
                if ($running) curl_multi_select($multi, 0.2);
            } while ($running && $status === CURLM_OK);

            foreach ($handles as $key => $handle) {
                $body = curl_multi_getcontent($handle);
                $error = curl_error($handle);
                $httpStatus = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
                $elapsedMs = max(0, (int)round(((float)curl_getinfo($handle, CURLINFO_TOTAL_TIME)) * 1000));
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
                curl_multi_remove_handle($multi, $handle);
                curl_close($handle);
            }
            curl_multi_close($multi);

            foreach ($batchKeys as $key) {
                if (!$this->shouldRetryTransport($results[$key])) continue;
                for ($retry = 0; $retry < self::RETRY_COUNT; $retry++) {
                    usleep((int)(250000 * ($retry + 1)));
                    $retryResult = $this->requestOneStream($requests[$key]);
                    $results[$key] = $retryResult;
                    if (!$this->shouldRetryTransport($retryResult)) break;
                }
            }
        }
        return $results;
    }

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
                'timeout' => (int)($request['timeout'] ?? self::DEFAULT_TIMEOUT_SECONDS),
                'ignore_errors' => true,
                'follow_location' => 0,
            ],
        ]);
        $body = @file_get_contents($request['url'], false, $context);
        $httpStatus = 0;
        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $httpStatus = (int)$match[1];
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
