<?php

// ⭐⭐⭐ 必須放最前面（第一行下面）⭐⭐⭐
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


/*
 * ============================================================
 * Emergency PHP Syntax Check
 * ============================================================
 * URL: /idas/public/?syntax_check=1
 *
 * Runs BEFORE app/bootstrap.php / MVC.
 * Therefore it still works when Settings.php has a Parse error.
 * Read-only: PHP CLI lint only; checked files are not executed.
 */
if (isset($_GET['syntax_check']) && (string)$_GET['syntax_check'] === '1') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }

    $lang = 'en-us';

    $t = [
        'title' => 'Emergency PHP Syntax Check',
        'refresh' => 'Refresh',
        'root' => 'Project Root',
        'binary' => 'PHP CLI',
        'checked' => 'Checked Files',
        'ok' => 'Syntax OK',
        'ng' => 'Syntax NG',
        'file' => 'File',
        'line' => 'Line',
        'message' => 'Message',
        'all_ok' => 'No PHP syntax errors detected.',
        'unavailable' => 'PHP Syntax Check unavailable',
        'readonly' => 'Read-only syntax check. Checked PHP files are not included or executed.',
    ];

    $h = static function ($v): string {
        return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
    };

    $projectRoot = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');

    $candidates = [];
    if (defined('PHP_BINARY') && PHP_BINARY !== '') $candidates[] = PHP_BINARY;
    $candidates[] = '/usr/bin/php';
    $candidates[] = '/usr/local/bin/php';
    $candidates[] = '/bin/php';

    $phpBinary = '';
    foreach (array_unique($candidates) as $candidate) {
        if (is_file($candidate) && is_executable($candidate)) {
            $phpBinary = $candidate;
            break;
        }
    }

    $files = [];
    foreach ([
        $projectRoot . '/index.php',
        $projectRoot . '/app/bootstrap.php',
        $projectRoot . '/app/config/config.php',
    ] as $f) {
        if (is_file($f)) $files[$f] = true;
    }

    foreach ([
        'app/controllers',
        'app/models',
        'app/libraries',
        'app/views',
        'api',
    ] as $relDir) {
        $dir = $projectRoot . '/' . $relDir;
        if (!is_dir($dir)) continue;

        try {
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $fi) {
                if ($fi->isFile() && strtolower($fi->getExtension()) === 'php') {
                    $files[$fi->getPathname()] = true;
                }
            }
        } catch (Throwable $e) {
        }
    }

    $paths = array_keys($files);
    sort($paths, SORT_STRING);

    $result = [
        'available' => false,
        'error' => '',
        'checked' => 0,
        'ok' => 0,
        'ng' => 0,
        'failed' => [],
    ];

    $disabled = array_filter(array_map(
        'trim',
        explode(',', (string)ini_get('disable_functions'))
    ));

    if ($phpBinary === '') {
        $result['error'] = 'PHP CLI binary was not found.';
    } elseif (!function_exists('exec')) {
        $result['error'] = 'PHP exec() is not available.';
    } elseif (in_array('exec', $disabled, true)) {
        $result['error'] = 'PHP exec() is disabled.';
    } else {
        $result['available'] = true;

        foreach ($paths as $file) {
            $output = [];
            $exitCode = 1;

            exec(
                escapeshellarg($phpBinary) . ' -n -l ' . escapeshellarg($file) . ' 2>&1',
                $output,
                $exitCode
            );

            $message = trim(implode("\n", $output));
            $relative = strpos($file, $projectRoot . DIRECTORY_SEPARATOR) === 0
                ? substr($file, strlen($projectRoot) + 1)
                : $file;

            $line = null;
            if (preg_match('/\bon line\s+(\d+)\b/i', $message, $m)) {
                $line = (int)$m[1];
            }

            $result['checked']++;

            if ($exitCode === 0) {
                $result['ok']++;
            } else {
                $result['ng']++;
                $result['failed'][] = [
                    'file' => $relative,
                    'line' => $line,
                    'message' => $message,
                ];
            }
        }
    }

    header('Content-Type: text/html; charset=utf-8');
    ?>
<!doctype html>
<html lang="<?php echo $h($lang); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $h($t['title']); ?></title>
<style>
body{margin:0;padding:20px;background:#f2f4f7;color:#1f2937;font-family:Arial,"Noto Sans TC",sans-serif}
.wrap{max-width:1500px;margin:auto}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:15px}
h1{font-size:25px;margin:0}
button{height:38px;padding:0 16px;border:1px solid #aeb6c2;border-radius:7px;background:#fff;cursor:pointer}
.card{background:#fff;border-radius:10px;box-shadow:0 2px 7px rgba(0,0,0,.1);overflow:hidden;margin-bottom:14px}
.summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr))}
.item{padding:14px;border-right:1px solid #e5e7eb}
.label{color:#667085;font-size:14px;margin-bottom:5px}.value{font-size:20px;font-weight:800}
.ok{color:#24752a}.ng{color:#c62828}.na{color:#9a6700}
table{width:100%;border-collapse:collapse;table-layout:fixed}
th,td{padding:9px 12px;border-bottom:1px solid #edf0f3;text-align:left;vertical-align:top;overflow-wrap:anywhere}
th{background:#fafbfc;width:28%}.code{font-family:Consolas,"Courier New",monospace;font-size:13px}
.note{padding:12px;background:#fff7df;border:1px solid #f2d28a;border-radius:8px}
@media(max-width:760px){.summary{grid-template-columns:1fr 1fr}.toolbar{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<div class="wrap">
<div class="toolbar">
<h1><?php echo $h($t['title']); ?></h1>
<button onclick="location.reload()"><?php echo $h($t['refresh']); ?></button>
</div>

<div class="card summary">
<div class="item"><div class="label"><?php echo $h($t['checked']); ?></div><div class="value"><?php echo $h($result['checked']); ?></div></div>
<div class="item"><div class="label"><?php echo $h($t['ok']); ?></div><div class="value ok"><?php echo $h($result['ok']); ?></div></div>
<div class="item"><div class="label"><?php echo $h($t['ng']); ?></div><div class="value <?php echo $result['ng'] ? 'ng' : 'ok'; ?>"><?php echo $h($result['ng']); ?></div></div>
<div class="item"><div class="label"><?php echo $h($t['binary']); ?></div><div class="value code"><?php echo $h($phpBinary); ?></div></div>
</div>

<div class="card">
<table>
<tr><th><?php echo $h($t['root']); ?></th><td class="code"><?php echo $h($projectRoot); ?></td></tr>
<?php if (!$result['available']): ?>
<tr><th><?php echo $h($t['unavailable']); ?></th><td class="ng"><?php echo $h($result['error']); ?></td></tr>
<?php endif; ?>
</table>
</div>

<?php if ($result['available'] && $result['ng'] === 0): ?>
<div class="card"><div style="padding:16px" class="ok"><strong><?php echo $h($t['all_ok']); ?></strong></div></div>
<?php endif; ?>

<?php if (!empty($result['failed'])): ?>
<div class="card">
<table>
<tr><th><?php echo $h($t['file']); ?></th><td style="width:8%;font-weight:700"><?php echo $h($t['line']); ?></td><td style="font-weight:700"><?php echo $h($t['message']); ?></td></tr>
<?php foreach ($result['failed'] as $failure): ?>
<tr>
<th class="code"><?php echo $h($failure['file']); ?></th>
<td class="ng"><?php echo $h($failure['line']); ?></td>
<td class="ng code"><?php echo $h($failure['message']); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<div class="note"><?php echo $h($t['readonly']); ?></div>
</div>
</body>
</html>
<?php
    exit;
}


require_once __DIR__ . '/../app/bootstrap.php';

// bootstrap.php already loads Controller and Core explicitly.
$init = new Core();
