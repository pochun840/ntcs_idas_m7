<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/service/JobConfigApiConfig.php';

/**
 * Compatibility endpoint.
 *
 * New integrations should call /api/replace_job_config.php.
 * This file only normalizes the legacy {targets, payload} request shape and
 * delegates all validation, preview, write and response handling to the
 * unified API so there is one public write path and one write implementation.
 */
header('X-iDAS-Deprecated-Endpoint: true');
header('X-iDAS-Replacement-Endpoint: replace_job_config.php');

$method = $_SERVER['REQUEST_METHOD'] ?? '';

if ($method === 'GET') {
    if (!isset($_GET['action']) || trim((string)$_GET['action']) === '') {
        $_GET['action'] = 'network';
    }
    require __DIR__ . '/replace_job_config.php';
    exit;
}

if ($method === 'POST') {
    $contentType = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($contentType === 'application/json') {
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $request = json_decode($raw, true);
            if (is_array($request) && json_last_error() === JSON_ERROR_NONE) {
                // Legacy v13/v12 shape: {targets:[...], payload:{JOB_lst,...}}
                if (isset($request['payload']) && is_array($request['payload'])) {
                    $payload = $request['payload'];
                    unset($request['payload']);
                    foreach (['JOB_lst', 'SEQ_lst', 'STEP_lst'] as $key) {
                        if (!array_key_exists($key, $request) && array_key_exists($key, $payload)) {
                            $request[$key] = $payload[$key];
                        }
                    }
                }
                if (!isset($request['api_version'])) $request['api_version'] = JobConfigApiConfig::VERSION;
                $GLOBALS['JOB_CONFIG_REQUEST_OVERRIDE'] = $request;
            }
        }
    }
}

require __DIR__ . '/replace_job_config.php';
