<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/service/MesApiSettingsService.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { header('Allow: POST'); http_response_code(405); echo json_encode(['success'=>false]); exit; }
    if (strpos(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') === false) { http_response_code(415); echo json_encode(['success'=>false]); exit; }
    $body = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($body) || !is_string($body['url'] ?? null)) throw new InvalidArgumentException('Invalid URL');
    $url = MesApiSettingsService::validate($body['url']);
    if ($url === '') throw new InvalidArgumentException('URL is required');
    if (!function_exists('curl_init')) { http_response_code(503); echo json_encode(['success'=>false,'reason'=>'curl_unavailable']); exit; }
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_NOBODY=>true, CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_TIMEOUT=>5, CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_PROTOCOLS=>CURLPROTO_HTTP | CURLPROTO_HTTPS, CURLOPT_SSL_VERIFYPEER=>true,
        CURLOPT_SSL_VERIFYHOST=>2, CURLOPT_HTTPHEADER=>['Accept: application/json']]);
    $result = curl_exec($ch);
    $errno = curl_errno($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $elapsed = (int)round(curl_getinfo($ch, CURLINFO_TOTAL_TIME)*1000);
    curl_close($ch);
    echo json_encode(['success'=>true,'reachable'=>$result !== false && $http > 0,
        'http_status'=>$http,'elapsed_ms'=>$elapsed,'curl_errno'=>$errno,
        'reason'=>$errno === 28 ? 'timeout' : ($errno === 6 ? 'dns' : ($errno === 7 ? 'connection' : ($errno !== 0 ? 'transport' : 'http')))]);
} catch (InvalidArgumentException $e) {
    http_response_code(400); echo json_encode(['success'=>false,'reason'=>'invalid_url']);
} catch (Throwable $e) {
    error_log('[mes_connection_test] '.$e->getMessage()); http_response_code(503); echo json_encode(['success'=>false,'reason'=>'server']);
}
