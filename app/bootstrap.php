<?php
// Production mode：PHP 錯誤不直接顯示於 Web 畫面，但仍完整寫入 Error Log。
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

/*
 * iDAS reboot-safe bootstrap.
 *
 * Do not depend on the process current working directory or autoload timing
 * for framework base classes. Controller must exist before Core loads
 * Logins.php (class Logins extends Controller).
 */
$appRoot = __DIR__;

$configFile = $appRoot . '/config/config.php';
$controllerFile = $appRoot . '/libraries/Controller.php';
$coreFile = $appRoot . '/libraries/Core.php';

foreach ([$configFile, $controllerFile, $coreFile] as $requiredFile) {
    if (!is_file($requiredFile) || !is_readable($requiredFile)) {
        error_log('[iDAS bootstrap] Required file unavailable: ' . $requiredFile);
        throw new RuntimeException('Required iDAS framework file unavailable: ' . basename($requiredFile));
    }
}

require_once $configFile;
require_once $controllerFile;

require_once $coreFile;

/*
 * Remaining framework/model classes are loaded with absolute paths.
 * Search libraries first to preserve the original MVC behavior.
 */
spl_autoload_register(function ($className) use ($appRoot) {
    $className = basename((string)$className);
    if ($className === '') {
        return;
    }

    $candidates = [
        $appRoot . '/libraries/' . $className . '.php',
        $appRoot . '/models/' . $className . '.php',
        $appRoot . '/controllers/' . $className . '.php',
    ];

    foreach ($candidates as $file) {
        if (is_file($file) && is_readable($file)) {
            require_once $file;
            return;
        }
    }
});
