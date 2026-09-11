<?php

declare(strict_types=1);

// Dedicated POST endpoint used by the Web page. Keeping GET out of this
// endpoint prevents an HTTP redirect from being mistaken for an API result.
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('Allow: POST');
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST is required'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require __DIR__ . '/replace_job_config.php';
