<?php

// ⭐⭐⭐ 必須放最前面（第一行下面）⭐⭐⭐
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../app/bootstrap.php';
$init = new Core();
