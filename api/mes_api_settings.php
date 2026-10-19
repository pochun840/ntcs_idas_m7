<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/service/MesApiSettingsService.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST'); http_response_code(405);
        echo json_encode(['success' => false]); exit;
    }
    $service = new MesApiSettingsService();
    if ($method === 'POST') {
        if (strpos(strtolower($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') === false) {
            http_response_code(415); echo json_encode(['success' => false]); exit;
        }
        $body = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($body)) throw new InvalidArgumentException('Invalid JSON');
        $data = $service->save($body);
    } else $data = $service->read();
    echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_SLASHES);
} catch (InvalidArgumentException $e) {
    http_response_code(400); echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('[mes_api_settings] ' . $e->getMessage());
    http_response_code(503); echo json_encode(['success' => false, 'message' => 'Unable to access MES settings']);
}
