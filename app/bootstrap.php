<?php
$idasDebugMode = filter_var(getenv('IDAS_DEBUG') ?: '0', FILTER_VALIDATE_BOOLEAN);
ini_set('display_errors', $idasDebugMode ? '1' : '0');
ini_set('display_startup_errors', $idasDebugMode ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (!function_exists('idas_startup_retry_page')) {
    function idas_startup_retry_page(string $reason = ''): void
    {
        http_response_code(503);
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Retry-After: 2');

        $reasonHtml = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
        $startupLang = strtolower((string)($_COOKIE['language'] ?? 'zh-tw'));
        if ($startupLang === 'en') {
            $startupLang = 'en-us';
        }
        $startupTexts = [
            'zh-tw' => ['title' => 'iDAS 正在啟動', 'waiting' => '系統服務尚未完全就緒，正在背景重新連線。', 'failed' => '系統尚未完成啟動，已停止自動重試。您可以稍後重試或返回首頁。', 'retry' => '重新嘗試', 'home' => '返回首頁'],
            'zh-cn' => ['title' => 'iDAS 正在启动', 'waiting' => '系统服务尚未完全就绪，正在后台重新连接。', 'failed' => '系统尚未完成启动，已停止自动重试。您可以稍后重试或返回首页。', 'retry' => '重新尝试', 'home' => '返回首页'],
            'en-us' => ['title' => 'iDAS is starting', 'waiting' => 'System services are not ready. Reconnecting in the background.', 'failed' => 'iDAS is still unavailable. Automatic retries have stopped. Try again later or return to the home page.', 'retry' => 'Try again', 'home' => 'Home'],
        ];
        $startupText = $startupTexts[$startupLang] ?? $startupTexts['en-us'];

        echo '<!doctype html><html lang="zh-Hant"><head>';
        echo '<meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>iDAS Starting</title>';
        echo '<style>';
        echo 'html,body{height:100%;margin:0;font-family:Arial,"Microsoft JhengHei",sans-serif;background:#f5f6f7;color:#222}';
        echo '.wrap{height:100%;display:flex;align-items:center;justify-content:center}';
        echo '.card{background:#fff;border:1px solid #ddd;border-radius:8px;padding:28px 36px;box-shadow:0 2px 10px rgba(0,0,0,.08);text-align:center;max-width:520px}';
        echo '.title{font-size:22px;font-weight:700;margin-bottom:12px}.msg{font-size:16px;line-height:1.6}.sub{margin-top:10px;font-size:13px;color:#666}';
        echo '.spin{width:42px;height:42px;margin:0 auto 18px;border:4px solid #d9dee3;border-top-color:#2878c8;border-radius:50%;animation:idas-spin .9s linear infinite}';
        echo '.actions{display:none;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:20px}.retry,.home{padding:10px 22px;border-radius:6px;font-size:15px;cursor:pointer}.retry{border:0;background:#2878c8;color:#fff}.home{border:1px solid #aeb7c0;background:#fff;color:#333}.failed{color:#b3261e} @keyframes idas-spin{to{transform:rotate(360deg)}}';
        echo '</style></head><body>';
        echo '<div class="wrap"><div class="card">';
        echo '<div class="spin" aria-hidden="true"></div>';
        echo '<div class="title">' . htmlspecialchars($startupText['title'], ENT_QUOTES, 'UTF-8') . '</div>';
        echo '<div class="msg">' . htmlspecialchars($startupText['waiting'], ENT_QUOTES, 'UTF-8') . '</div>';
        if ($reasonHtml !== '') {
            echo '<div class="sub">' . $reasonHtml . '</div>';
        }
        echo '<div id="idas-startup-actions" class="actions"><button id="idas-startup-retry" class="retry" type="button">' . htmlspecialchars($startupText['retry'], ENT_QUOTES, 'UTF-8') . '</button><button id="idas-startup-home" class="home" type="button">' . htmlspecialchars($startupText['home'], ENT_QUOTES, 'UTF-8') . '</button></div>';
        echo '</div></div>';
        echo '<script>';
        echo '(function(){';
        echo 'var delay=2000,limit=8000,started=Date.now(),stopped=false;';
        echo 'var original=new URL(location.href);original.searchParams.delete("_startup_retry");original.searchParams.delete("_idas_boot");original.searchParams.delete("_idas_web_probe");';
        echo 'function showActions(){if(stopped)return;stopped=true;var a=document.getElementById("idas-startup-actions"),m=document.querySelector(".msg");if(a)a.style.display="flex";if(m){m.className="msg failed";m.textContent=' . json_encode($startupText['failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';}}';
        echo 'function probe(){if(stopped)return;if(Date.now()-started>=limit){showActions();return;}var u=new URL(original.toString());u.searchParams.set("_startup_probe",String(Date.now()));fetch(u.toString(),{cache:"no-store",credentials:"same-origin",headers:{"X-iDAS-Startup-Probe":"1"}}).then(function(r){if(!r.ok)throw 0;return r.text();}).then(function(body){if(body.indexOf("idas-startup-actions")!==-1)throw 0;try{sessionStorage.removeItem("idas_startup_retry_count");}catch(e){}location.replace(original.toString());}).catch(function(){setTimeout(probe,delay);});}';
        echo 'document.getElementById("idas-startup-retry").onclick=function(){location.href=original.toString();};';
        echo 'document.getElementById("idas-startup-home").onclick=function(){var u=new URL(original.toString());u.search="";u.searchParams.set("url","Dashboards");location.href=u.toString();};';
        echo 'setTimeout(probe,delay);setTimeout(showActions,limit);';
        echo '})();';
        echo '</script>';
        echo '</body></html>';
        exit;
    }
}


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
        idas_startup_retry_page('Waiting for ' . basename($requiredFile));
    }
}

require_once $configFile;
require_once $controllerFile;

if (!class_exists('Controller', false)) {
    error_log('[iDAS bootstrap] Controller alias was not created.');
    idas_startup_retry_page('Initializing Controller framework class.');
}

require_once $coreFile;

if (!class_exists('Core', false)) {
    error_log('[iDAS bootstrap] Core class was not created.');
    idas_startup_retry_page('Initializing Core framework class.');
}

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
