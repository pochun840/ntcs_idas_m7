<?php
/*
 * Standalone PHP Syntax Check
 *
 * This file intentionally does NOT include app/controllers/Settings.php.
 * It can therefore still diagnose Settings.php when Settings.php itself
 * contains a Parse error.
 *
 * Read-only:
 * - Runs PHP CLI with `-n -l`
 * - Does not include or execute checked project files
 * - Does not write DB/settings
 */

session_start();

$lang = strtolower((string)($_COOKIE['language'] ?? ($_SESSION['language'] ?? 'en-us')));
$lang = str_replace('_', '-', $lang);
if ($lang === 'en') $lang = 'en-us';
if (!in_array($lang, ['en-us', 'zh-tw', 'zh-cn'], true)) $lang = 'en-us';

$I18N = [
    'en-us' => [
        'title' => 'Standalone PHP Syntax Check',
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
    ],
    'zh-tw' => [
        'title' => '獨立 PHP 語法檢查',
        'refresh' => '重新整理',
        'root' => '專案根目錄',
        'binary' => 'PHP CLI',
        'checked' => '檢查檔案數',
        'ok' => '語法正常',
        'ng' => '語法異常',
        'file' => '檔案',
        'line' => '行號',
        'message' => '錯誤訊息',
        'all_ok' => '未偵測到 PHP 語法錯誤。',
        'unavailable' => 'PHP 語法檢查無法使用',
        'readonly' => '此頁為唯讀語法檢查，不會 include 或執行被檢查的 PHP 檔案。',
    ],
    'zh-cn' => [
        'title' => '独立 PHP 语法检查',
        'refresh' => '刷新',
        'root' => '项目根目录',
        'binary' => 'PHP CLI',
        'checked' => '检查文件数',
        'ok' => '语法正常',
        'ng' => '语法异常',
        'file' => '文件',
        'line' => '行号',
        'message' => '错误信息',
        'all_ok' => '未检测到 PHP 语法错误。',
        'unavailable' => 'PHP 语法检查无法使用',
        'readonly' => '此页为只读语法检查，不会 include 或执行被检查的 PHP 文件。',
    ],
];
$t = $I18N[$lang];

function h($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function resolve_php_binary(): string {
    $candidates = [];
    if (defined('PHP_BINARY') && PHP_BINARY !== '') $candidates[] = PHP_BINARY;
    $candidates[] = '/usr/bin/php';
    $candidates[] = '/usr/local/bin/php';
    $candidates[] = '/bin/php';

    foreach (array_unique($candidates) as $candidate) {
        if (is_file($candidate) && is_executable($candidate)) return $candidate;
    }
    return '';
}

function collect_php_files(string $root): array {
    $files = [];
    foreach ([$root . '/index.php', $root . '/app/bootstrap.php', $root . '/app/config/config.php'] as $file) {
        if (is_file($file)) $files[$file] = true;
    }

    foreach (['app/controllers', 'app/models', 'app/libraries', 'app/views', 'api'] as $rel) {
        $dir = $root . '/' . $rel;
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
        } catch (Throwable $e) {}
    }

    $paths = array_keys($files);
    sort($paths, SORT_STRING);
    return $paths;
}

function extract_line(string $message): ?int {
    if (preg_match('/\bon line\s+(\d+)\b/i', $message, $m)) return (int)$m[1];
    if (preg_match('/:\s*(\d+)\s*$/m', $message, $m)) return (int)$m[1];
    return null;
}

function relative_path(string $path, string $root): string {
    $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return strpos($path, $root) === 0 ? substr($path, strlen($root)) : $path;
}

$root = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');
$binary = resolve_php_binary();

$result = [
    'available' => false,
    'error' => '',
    'checked' => 0,
    'ok' => 0,
    'ng' => 0,
    'failed' => [],
];

if ($binary === '') {
    $result['error'] = 'PHP CLI binary was not found.';
} elseif (!function_exists('exec')) {
    $result['error'] = 'PHP exec() is not available.';
} elseif (in_array('exec', array_filter(array_map('trim', explode(',', (string)ini_get('disable_functions')))), true)) {
    $result['error'] = 'PHP exec() is disabled.';
} else {
    $result['available'] = true;

    foreach (collect_php_files($root) as $file) {
        $out = [];
        $code = 1;

        exec(
            escapeshellarg($binary) . ' -n -l ' . escapeshellarg($file) . ' 2>&1',
            $out,
            $code
        );

        $message = trim(implode("\n", $out));
        $result['checked']++;

        if ($code === 0) {
            $result['ok']++;
        } else {
            $result['ng']++;
            $result['failed'][] = [
                'file' => relative_path($file, $root),
                'line' => extract_line($message),
                'message' => $message,
            ];
        }
    }
}
?>
<!doctype html>
<html lang="<?php echo h($lang); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo h($t['title']); ?></title>
<style>
body{margin:0;padding:20px;background:#f2f4f7;color:#1f2937;font-family:Arial,"Noto Sans TC",sans-serif}
.wrap{max-width:1400px;margin:auto}
.toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:15px}
h1{font-size:25px;margin:0}
button{height:38px;padding:0 16px;border:1px solid #aeb6c2;border-radius:7px;background:#fff;cursor:pointer}
.card{background:#fff;border-radius:10px;box-shadow:0 2px 7px rgba(0,0,0,.1);overflow:hidden;margin-bottom:14px}
.summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0}
.item{padding:14px;border-right:1px solid #e5e7eb}
.label{color:#667085;font-size:14px;margin-bottom:5px}
.value{font-size:20px;font-weight:800}
.ok{color:#24752a}.ng{color:#c62828}.na{color:#9a6700}
table{width:100%;border-collapse:collapse;table-layout:fixed}
th,td{padding:9px 12px;border-bottom:1px solid #edf0f3;text-align:left;vertical-align:top;overflow-wrap:anywhere}
th{background:#fafbfc;width:28%}
.code{font-family:Consolas,"Courier New",monospace;font-size:13px}
.note{padding:12px;background:#fff7df;border:1px solid #f2d28a;border-radius:8px}
@media(max-width:760px){.summary{grid-template-columns:1fr 1fr}.toolbar{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<div class="wrap">
<div class="toolbar">
    <h1><?php echo h($t['title']); ?></h1>
    <button onclick="location.reload()"><?php echo h($t['refresh']); ?></button>
</div>

<div class="card summary">
    <div class="item"><div class="label"><?php echo h($t['checked']); ?></div><div class="value"><?php echo h($result['checked']); ?></div></div>
    <div class="item"><div class="label"><?php echo h($t['ok']); ?></div><div class="value ok"><?php echo h($result['ok']); ?></div></div>
    <div class="item"><div class="label"><?php echo h($t['ng']); ?></div><div class="value <?php echo $result['ng'] ? 'ng' : 'ok'; ?>"><?php echo h($result['ng']); ?></div></div>
    <div class="item"><div class="label"><?php echo h($t['binary']); ?></div><div class="value code"><?php echo h($binary); ?></div></div>
</div>

<div class="card">
<table>
<tr><th><?php echo h($t['root']); ?></th><td class="code"><?php echo h($root); ?></td></tr>
<?php if (!$result['available']): ?>
<tr><th><?php echo h($t['unavailable']); ?></th><td class="ng"><?php echo h($result['error']); ?></td></tr>
<?php endif; ?>
</table>
</div>

<?php if ($result['available'] && $result['ng'] === 0): ?>
<div class="card"><div style="padding:16px" class="ok"><strong><?php echo h($t['all_ok']); ?></strong></div></div>
<?php endif; ?>

<?php if (!empty($result['failed'])): ?>
<div class="card">
<table>
<tr>
    <th><?php echo h($t['file']); ?></th>
    <td style="width:8%;font-weight:700"><?php echo h($t['line']); ?></td>
    <td style="font-weight:700"><?php echo h($t['message']); ?></td>
</tr>
<?php foreach ($result['failed'] as $failure): ?>
<tr>
    <th class="code"><?php echo h($failure['file']); ?></th>
    <td class="ng"><?php echo h($failure['line']); ?></td>
    <td class="ng code"><?php echo h($failure['message']); ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<div class="note"><?php echo h($t['readonly']); ?></div>
</div>
</body>
</html>
