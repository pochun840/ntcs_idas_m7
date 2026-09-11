<?php
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self'");

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/replace_job_config_web.php'));
$idasBaseUrl = preg_replace('#/api/[^/]+$#', '', $scriptName);
if (!is_string($idasBaseUrl) || $idasBaseUrl === '' || $idasBaseUrl === $scriptName) {
    $idasBaseUrl = '/idas';
}
$assetVersion = (string)(@filemtime(dirname(__DIR__) . '/public/js/idas_notifications.js') ?: time());

// Follow the same language convention as the rest of iDAS: cookie first, unknown -> en-us.
$langRaw = strtolower(str_replace('_', '-', trim((string)(
    $_COOKIE['language'] ?? $_COOKIE['languages'] ?? $_COOKIE['lang'] ?? 'en-us'
))));
if ($langRaw === 'zh' || $langRaw === 'zh-hant') $langRaw = 'zh-tw';
if ($langRaw === 'zh-hans') $langRaw = 'zh-cn';
if ($langRaw === 'en') $langRaw = 'en-us';
$lang = in_array($langRaw, ['en-us', 'zh-tw', 'zh-cn'], true) ? $langRaw : 'en-us';

$i18nAll = [
    'en-us' => [
        'html_lang' => 'en',
        'title' => 'iDAS JOB / SEQ / STEP Import',
        'subtitle' => 'Add or overwrite controller configuration using JSON',
        'api_tool' => 'API Tool',
        'json_title' => 'JSON Data',
        'json_hint' => 'Paste JSON below or load a .json file.',
        'choose_file' => 'Choose JSON File',
        'no_file' => 'No file selected',
        'format_json' => 'Format JSON',
        'write_db' => 'Write to Controller DB',
        'ready' => 'Ready',
        'api_response' => 'API Response',
        'job_count' => 'JOB',
        'seq_count' => 'SEQ',
        'step_count' => 'STEP',
        'confirm_title' => 'Confirm Write',
        'confirm_message' => 'Write these JOB / SEQ / STEP records to the controller? Records with matching IDs will be overwritten.',
        'confirm_ok' => 'Confirm',
        'cancel' => 'Cancel',
        'error' => 'Error',
        'warning_notice' => 'Warning',
        'success' => 'Success',
        'common_notice_failed' => 'The notification component failed to load. Please refresh the page and try again.',
        'json_valid' => 'JSON format is valid',
        'json_invalid' => 'Invalid JSON: ',
        'file_loaded' => 'Loaded: ',
        'file_invalid' => 'The file is not valid JSON: ',
        'processing' => 'Processing…',
        'redirected' => 'The API POST was redirected. Please refresh the page and try again.',
        'response_invalid' => 'The API response is invalid. The server did not return JSON.',
        'response_json_invalid' => 'The JSON returned by the API is invalid.',
        'write_success_prefix' => 'Added ',
        'write_success_middle' => ', overwritten ',
        'write_success_suffix' => ' successfully.',
        'controller_in_use' => 'The controller is still logged in. Please log out before writing.',
        'controller_unavailable' => 'Unable to verify controller login status. Check the controller connection and try again.',
        'write_failed_detail' => 'Write failed. Check the JSON data.',
        'write_done' => 'Write complete',
        'write_failed' => 'Write failed',
        'connection_failed' => 'Connection or processing failed.',
        'connection_status_failed' => 'Connection or processing failed',
        'records' => 'records',
        'target_title' => 'Target NTCS7 Controllers',
        'target_hint' => 'Enter one or more IPv4 addresses on the same subnet. Before every write, iDAS checks connectivity, /home/kls/NTCS7, the Controller DB, and logout status.',
        'target_ips' => 'Target IP',
        'target_placeholder' => 'Example: 192.168.100.150, 192.168.100.151',
        'check_targets' => 'Check Controllers',
        'checking' => 'Checking controllers…',
        'local_network' => 'Local network',
        'network_unavailable' => 'No active IPv4 network was found.',
        'target_required' => 'Enter at least one target IP address.',
        'all_ready' => 'All target controllers are ready.',
        'some_not_ready' => 'Some target controllers are not ready. Ready controllers can still be written.',
        'no_ready' => 'No target controller is ready to write.',
        'partial_ready' => 'Ready controllers will be written; unavailable controllers will be skipped.',
        'write_precheck' => 'Re-checking all target controllers before write…',
        'write_all_success' => 'All target controllers were updated successfully.',
        'write_partial' => 'Ready controllers were processed; unavailable controllers were skipped.',
        'write_none' => 'No controller was written.',
        'target_count' => 'Targets',
        'col_ip' => 'IP',
        'col_subnet' => 'Subnet',
        'col_connection' => 'Connection',
        'col_root' => '/home/kls/NTCS7',
        'col_controller' => 'Controller',
        'col_result' => 'Result',
        'reachable' => 'Reachable',
        'unreachable' => 'Unreachable',
        'exists' => 'Exists',
        'missing' => 'Missing',
        'logged_out' => 'Logged out',
        'in_use' => 'In use',
        'unknown' => 'Unknown',
        'ready_label' => 'Ready',
        'not_ready_label' => 'Not ready',
        'write_ok_label' => 'Write OK',
        'write_fail_label' => 'Write failed',
        'write_skip_label' => 'Skipped',
        'same_subnet_only' => 'Only IPv4 addresses on an active local subnet are allowed.',
    ],
    'zh-tw' => [
        'html_lang' => 'zh-Hant',
        'title' => 'iDAS JOB / SEQ / STEP 新增／覆蓋',
        'subtitle' => '使用 JSON 新增或覆蓋控制器設定',
        'api_tool' => 'API 工具',
        'json_title' => 'JSON 資料',
        'json_hint' => '可直接貼上 JSON，或載入 .json 檔案。',
        'choose_file' => '選擇 JSON 檔案',
        'no_file' => '尚未選擇檔案',
        'format_json' => '格式化 JSON',
        'write_db' => '寫入控制器 DB',
        'ready' => '準備完成',
        'api_response' => 'API 回應',
        'job_count' => 'JOB',
        'seq_count' => 'SEQ',
        'step_count' => 'STEP',
        'confirm_title' => '操作確認',
        'confirm_message' => '確定要寫入這些 JOB / SEQ / STEP 嗎？相同 ID 的資料會被更新覆蓋。',
        'confirm_ok' => '確定',
        'cancel' => '取消',
        'error' => '錯誤',
        'warning_notice' => '警告',
        'success' => '成功',
        'common_notice_failed' => '共用提示元件載入失敗，請重新整理頁面後再試。',
        'json_valid' => 'JSON 格式正確',
        'json_invalid' => 'JSON 格式錯誤：',
        'file_loaded' => '已載入：',
        'file_invalid' => '檔案不是有效 JSON：',
        'processing' => '處理中…',
        'redirected' => 'API POST 被重新導向，請重新整理頁面後再試。',
        'response_invalid' => 'API 回應格式錯誤，伺服器沒有回傳 JSON。',
        'response_json_invalid' => 'API 回傳的 JSON 格式錯誤。',
        'write_success_prefix' => '新增 ',
        'write_success_middle' => ' 筆、覆蓋 ',
        'write_success_suffix' => ' 筆成功',
        'controller_in_use' => '控制器尚未登出，請先登出後再寫入。',
        'controller_unavailable' => '無法確認控制器登入狀態，請檢查控制器連線後再試。',
        'write_failed_detail' => '寫入失敗，請檢查 JSON 資料。',
        'write_done' => '寫入完成',
        'write_failed' => '寫入失敗',
        'connection_failed' => '連線或處理失敗。',
        'connection_status_failed' => '連線或處理失敗',
        'records' => '筆',
        'target_title' => '目標 NTCS7 控制器',
        'target_hint' => '可輸入同網段的一台或多台 IPv4。每次寫入前都會重新檢查網路是否可通、/home/kls/NTCS7、控制器 DB 與登出狀態。',
        'target_ips' => '目標 IP',
        'target_placeholder' => '例如：192.168.100.150, 192.168.100.151',
        'check_targets' => '檢查控制器',
        'checking' => '正在檢查控制器…',
        'local_network' => '本機網段',
        'network_unavailable' => '找不到可用的 IPv4 網路。',
        'target_required' => '請至少輸入一個目標 IP。',
        'all_ready' => '所有目標控制器皆可寫入。',
        'some_not_ready' => '部分目標控制器尚未符合寫入條件，可寫入的控制器仍可繼續。',
        'no_ready' => '目前沒有任何目標控制器符合寫入條件。',
        'partial_ready' => '只會寫入符合條件的控制器，不可寫入的控制器會自動略過。',
        'write_precheck' => '寫入前重新檢查所有目標控制器…',
        'write_all_success' => '所有目標控制器寫入完成。',
        'write_partial' => '可寫入的控制器已處理，不可寫入的控制器已略過。',
        'write_none' => '沒有任何控制器完成寫入。',
        'target_count' => '目標',
        'col_ip' => 'IP',
        'col_subnet' => '網段',
        'col_connection' => '連線',
        'col_root' => '/home/kls/NTCS7',
        'col_controller' => '控制器',
        'col_result' => '結果',
        'reachable' => '可連線',
        'unreachable' => '無法連線',
        'exists' => '存在',
        'missing' => '不存在',
        'logged_out' => '已登出',
        'in_use' => '使用中',
        'unknown' => '未知',
        'ready_label' => '可寫入',
        'not_ready_label' => '不可寫入',
        'write_ok_label' => '寫入成功',
        'write_fail_label' => '寫入失敗',
        'write_skip_label' => '略過',
        'same_subnet_only' => '僅允許目前有效網卡同網段的 IPv4。',
    ],
    'zh-cn' => [
        'html_lang' => 'zh-Hans',
        'title' => 'iDAS JOB / SEQ / STEP 新增／覆盖',
        'subtitle' => '使用 JSON 新增或覆盖控制器设置',
        'api_tool' => 'API 工具',
        'json_title' => 'JSON 数据',
        'json_hint' => '可直接粘贴 JSON，或载入 .json 文件。',
        'choose_file' => '选择 JSON 文件',
        'no_file' => '尚未选择文件',
        'format_json' => '格式化 JSON',
        'write_db' => '写入控制器 DB',
        'ready' => '准备完成',
        'api_response' => 'API 响应',
        'job_count' => 'JOB',
        'seq_count' => 'SEQ',
        'step_count' => 'STEP',
        'confirm_title' => '操作确认',
        'confirm_message' => '确定要写入这些 JOB / SEQ / STEP 吗？相同 ID 的数据会被更新覆盖。',
        'confirm_ok' => '确定',
        'cancel' => '取消',
        'error' => '错误',
        'warning_notice' => '警告',
        'success' => '成功',
        'common_notice_failed' => '共用提示组件载入失败，请刷新页面后再试。',
        'json_valid' => 'JSON 格式正确',
        'json_invalid' => 'JSON 格式错误：',
        'file_loaded' => '已载入：',
        'file_invalid' => '文件不是有效 JSON：',
        'processing' => '处理中…',
        'redirected' => 'API POST 被重新导向，请刷新页面后再试。',
        'response_invalid' => 'API 响应格式错误，服务器没有返回 JSON。',
        'response_json_invalid' => 'API 返回的 JSON 格式错误。',
        'write_success_prefix' => '新增 ',
        'write_success_middle' => ' 笔、覆盖 ',
        'write_success_suffix' => ' 笔成功',
        'controller_in_use' => '控制器尚未登出，请先登出后再写入。',
        'controller_unavailable' => '无法确认控制器登入状态，请检查控制器连接后再试。',
        'write_failed_detail' => '写入失败，请检查 JSON 数据。',
        'write_done' => '写入完成',
        'write_failed' => '写入失败',
        'connection_failed' => '连接或处理失败。',
        'connection_status_failed' => '连接或处理失败',
        'records' => '笔',
        'target_title' => '目标 NTCS7 控制器',
        'target_hint' => '可输入同网段的一台或多台 IPv4。每次写入前都会重新检查网络是否可通、/home/kls/NTCS7、控制器 DB 与登出状态。',
        'target_ips' => '目标 IP',
        'target_placeholder' => '例如：192.168.100.150, 192.168.100.151',
        'check_targets' => '检查控制器',
        'checking' => '正在检查控制器…',
        'local_network' => '本机网段',
        'network_unavailable' => '找不到可用的 IPv4 网络。',
        'target_required' => '请至少输入一个目标 IP。',
        'all_ready' => '所有目标控制器皆可写入。',
        'some_not_ready' => '部分目标控制器尚未符合写入条件，可写入的控制器仍可继续。',
        'no_ready' => '目前没有任何目标控制器符合写入条件。',
        'partial_ready' => '只会写入符合条件的控制器，不可写入的控制器会自动跳过。',
        'write_precheck' => '写入前重新检查所有目标控制器…',
        'write_all_success' => '所有目标控制器写入完成。',
        'write_partial' => '可写入的控制器已处理，不可写入的控制器已跳过。',
        'write_none' => '没有任何控制器完成写入。',
        'target_count' => '目标',
        'col_ip' => 'IP',
        'col_subnet' => '网段',
        'col_connection' => '连接',
        'col_root' => '/home/kls/NTCS7',
        'col_controller' => '控制器',
        'col_result' => '结果',
        'reachable' => '可连接',
        'unreachable' => '无法连接',
        'exists' => '存在',
        'missing' => '不存在',
        'logged_out' => '已登出',
        'in_use' => '使用中',
        'unknown' => '未知',
        'ready_label' => '可写入',
        'not_ready_label' => '不可写入',
        'write_ok_label' => '写入成功',
        'write_fail_label' => '写入失败',
        'write_skip_label' => '跳过',
        'same_subnet_only' => '仅允许目前有效网卡同网段的 IPv4。',
    ],
];
$t = $i18nAll[$lang];
function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="<?php echo h($t['html_lang']); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo h($t['title']); ?></title>
  <link rel="stylesheet" href="<?php echo h($idasBaseUrl); ?>/public/css/alertify_min.css?v=<?php echo h($assetVersion); ?>">
  <link rel="stylesheet" href="<?php echo h($idasBaseUrl); ?>/public/css/default_min.css?v=<?php echo h($assetVersion); ?>">
  <link rel="stylesheet" href="<?php echo h($idasBaseUrl); ?>/public/css/idas_notifications.css?v=<?php echo h($assetVersion); ?>">
  <style>
    :root{--navy:#24365f;--blue:#1769c2;--blue-dark:#1058a4;--bg:#eef2f7;--panel:#fff;--line:#dce3ed;--text:#182230;--muted:#68758a;--soft:#f7f9fc;--warn-bg:#fff8e8;--warn:#c47a00;--success:#148044;--error:#c83232}
    *{box-sizing:border-box}
    body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,"Microsoft JhengHei","PingFang TC","PingFang SC",sans-serif;-webkit-font-smoothing:antialiased}
    button,input,textarea{font:inherit}
    .page{width:min(1180px,calc(100% - 40px));margin:24px auto 36px}
    .hero{position:relative;overflow:hidden;padding:24px 28px;background:var(--navy);color:#fff;border-radius:14px 14px 0 0;box-shadow:0 10px 30px rgba(36,54,95,.12)}
    .hero:after{content:"";position:absolute;width:210px;height:210px;border-radius:50%;right:-70px;top:-105px;background:rgba(255,255,255,.06)}
    .hero-top{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;position:relative;z-index:1}
    .hero h1{font-size:24px;line-height:1.3;margin:0 0 7px;font-weight:700;letter-spacing:.1px}
    .hero .subtitle{margin:0;color:#dce6fb;font-size:14px;line-height:1.5}
    .tag{flex:none;padding:6px 10px;border:1px solid rgba(255,255,255,.28);border-radius:999px;background:rgba(255,255,255,.09);font-size:12px;font-weight:700;letter-spacing:.25px}
    .content{background:var(--panel);border:1px solid #d9e0ea;border-top:0;border-radius:0 0 14px 14px;box-shadow:0 12px 32px rgba(31,47,70,.07);padding:24px 28px 28px}
    .target-card,.editor-card,.response-card{border:1px solid var(--line);border-radius:10px;background:#fff;overflow:hidden}
    .target-card{margin-bottom:16px}
    .target-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:15px 16px;background:var(--soft);border-bottom:1px solid var(--line)}
    .target-title{font-size:16px;font-weight:700}.target-hint{margin-top:4px;color:var(--muted);font-size:12px;line-height:1.5;max-width:760px}
    .network-badge{flex:none;padding:6px 9px;border-radius:999px;background:#e8f1ff;color:#2c568b;font-size:11px;font-weight:700}
    .target-body{padding:15px 16px}
    .target-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end}
    .target-field label{display:block;margin:0 0 7px;font-size:13px;font-weight:700;color:#33445d}
    .target-input{display:block;width:100%;height:72px;min-height:72px;max-height:160px;resize:vertical;border:1px solid #cbd5e1;border-radius:7px;padding:10px 12px;background:#fff;color:#16233a;font:13px/1.5 Consolas,"SFMono-Regular","Courier New",monospace}
    .target-input:focus{outline:3px solid rgba(42,127,220,.18);border-color:#6fa7df}
    .target-meta{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:9px;color:var(--muted);font-size:11px}
    .target-table-wrap{display:none;margin-top:13px;border:1px solid var(--line);border-radius:8px;overflow:auto}
    .target-table{width:100%;border-collapse:collapse;min-width:760px;font-size:12px}
    .target-table th,.target-table td{padding:9px 10px;border-bottom:1px solid #edf1f5;text-align:left;vertical-align:middle}
    .target-table th{background:#f7f9fc;color:#536177;font-weight:700;white-space:nowrap}.target-table tr:last-child td{border-bottom:0}
    .state{display:inline-flex;align-items:center;gap:5px;font-weight:700;white-space:nowrap}.state:before{content:"";width:7px;height:7px;border-radius:50%;background:#9aa6b6}.state.ok{color:var(--success)}.state.ok:before{background:var(--success)}.state.bad{color:var(--error)}.state.bad:before{background:var(--error)}.state.warn{color:#b87500}.state.warn:before{background:#d18a11}
    .editor-card,.response-card{border:1px solid var(--line);border-radius:10px;background:#fff;overflow:hidden}
    .editor-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 16px;border-bottom:1px solid var(--line);background:var(--soft)}
    .editor-title{font-weight:700;font-size:16px}
    .editor-hint{margin-top:3px;color:var(--muted);font-size:12px}
    .toolbar{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap}
    #jsonFile{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
    .file-name{max-width:220px;color:var(--muted);font-size:12px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:38px;border:1px solid transparent;border-radius:7px;padding:8px 14px;font-size:14px;font-weight:700;cursor:pointer;transition:background .15s,border-color .15s,box-shadow .15s,transform .05s;white-space:nowrap;text-decoration:none}
    .btn:active{transform:translateY(1px)}
    .btn-secondary{background:#fff;border-color:#cbd5e1;color:#314158}
    .btn-secondary:hover{background:#f5f7fa;border-color:#aeb9c8}
    .btn-primary{background:var(--blue);color:#fff;box-shadow:0 3px 8px rgba(23,105,194,.18)}
    .btn-primary:hover{background:var(--blue-dark)}
    .btn:focus-visible,textarea:focus-visible{outline:3px solid rgba(42,127,220,.22);outline-offset:1px}
    .btn:disabled{opacity:.55;cursor:not-allowed;box-shadow:none;transform:none}
    .editor-body{padding:0}
    textarea{display:block;width:100%;height:430px;min-height:300px;resize:vertical;padding:16px 18px;border:0;background:#fbfcfe;color:#13223b;font:13px/1.58 Consolas,"SFMono-Regular","Courier New",monospace;tab-size:2}
    textarea:focus{outline:0;background:#fff}
    .editor-foot{display:flex;align-items:center;justify-content:space-between;gap:15px;padding:13px 16px;border-top:1px solid var(--line);background:#fff}
    .left-foot{display:flex;align-items:center;gap:12px;min-width:0;flex-wrap:wrap}
    .status{display:inline-flex;align-items:center;gap:7px;font-size:13px;font-weight:700;color:var(--muted)}
    .status:before{content:"";width:8px;height:8px;border-radius:50%;background:#98a4b5}
    .status.success{color:var(--success)}.status.success:before{background:var(--success)}
    .status.warning{color:#b87500}.status.warning:before{background:#d18a11}
    .status.error{color:var(--error)}.status.error:before{background:var(--error)}
    .counts{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
    .count-pill{padding:4px 8px;border-radius:999px;background:#edf3fb;color:#415573;font-size:11px;font-weight:700}
    .response-card{display:none;margin-top:16px}
    .response-title{padding:11px 15px;border-bottom:1px solid #26344a;background:#111c2f;color:#dce8ff;font-size:13px;font-weight:700}
    pre{margin:0;padding:16px;background:#101827;color:#eaf1ff;white-space:pre-wrap;word-break:break-word;max-height:320px;overflow:auto;font:12px/1.55 Consolas,"Courier New",monospace}
    @media(max-width:760px){
      .page{width:100%;margin:0}.hero{border-radius:0;padding:20px}.content{border-left:0;border-right:0;border-bottom:0;border-radius:0;padding:16px}.hero h1{font-size:20px}.tag{display:none}
      .target-head{flex-direction:column}.network-badge{align-self:flex-start}.target-row{grid-template-columns:1fr}.target-row .btn{width:100%}.editor-head{align-items:flex-start;flex-direction:column}.toolbar{justify-content:flex-start;width:100%}.file-name{max-width:100%}.editor-foot{align-items:stretch;flex-direction:column}.editor-foot .btn-primary{width:100%}textarea{height:48vh;min-height:300px;padding:14px}
    }
    @media(max-width:430px){.toolbar .btn{flex:1 1 auto}.file-name{flex-basis:100%}.content{padding:12px}}
  </style>
</head>
<body>
<div class="page">
  <header class="hero">
    <div class="hero-top">
      <div>
        <h1><?php echo h($t['title']); ?></h1>
        <p class="subtitle"><?php echo h($t['subtitle']); ?></p>
      </div>
      <span class="tag"><?php echo h($t['api_tool']); ?></span>
    </div>
  </header>

  <main class="content">
    <section class="target-card">
      <div class="target-head">
        <div>
          <div class="target-title"><?php echo h($t['target_title']); ?></div>
          <div class="target-hint"><?php echo h($t['target_hint']); ?></div>
        </div>
        <span id="networkBadge" class="network-badge"><?php echo h($t['local_network']); ?>: --</span>
      </div>
      <div class="target-body">
        <div class="target-row">
          <div class="target-field">
            <label for="targetIps"><?php echo h($t['target_ips']); ?></label>
            <textarea id="targetIps" class="target-input" rows="2" spellcheck="false" placeholder="<?php echo h($t['target_placeholder']); ?>"></textarea>
          </div>
          <button id="checkTargetsButton" class="btn btn-secondary" type="button">⌁ <?php echo h($t['check_targets']); ?></button>
        </div>
        <div class="target-meta">
          <span><?php echo h($t['same_subnet_only']); ?></span>
          <span>•</span>
          <span id="targetCount"><?php echo h($t['target_count']); ?> 0</span>
        </div>
        <div id="targetTableWrap" class="target-table-wrap">
          <table class="target-table">
            <thead><tr>
              <th><?php echo h($t['col_ip']); ?></th>
              <th><?php echo h($t['col_subnet']); ?></th>
              <th><?php echo h($t['col_connection']); ?></th>
              <th><?php echo h($t['col_root']); ?></th>
              <th><?php echo h($t['col_controller']); ?></th>
              <th><?php echo h($t['col_result']); ?></th>
            </tr></thead>
            <tbody id="targetTableBody"></tbody>
          </table>
        </div>
      </div>
    </section>

    <section class="editor-card">
      <div class="editor-head">
        <div>
          <div class="editor-title"><?php echo h($t['json_title']); ?></div>
          <div class="editor-hint"><?php echo h($t['json_hint']); ?></div>
        </div>
        <div class="toolbar">
          <input id="jsonFile" type="file" accept="application/json,.json">
          <label for="jsonFile" class="btn btn-secondary" role="button">↥ <?php echo h($t['choose_file']); ?></label>
          <span id="fileName" class="file-name"><?php echo h($t['no_file']); ?></span>
          <button id="formatButton" class="btn btn-secondary" type="button">{ } <?php echo h($t['format_json']); ?></button>
        </div>
      </div>
      <div class="editor-body">
        <textarea id="jsonBody" aria-label="<?php echo h($t['json_title']); ?>" spellcheck="false">{
  "JOB_lst": [
    {
      "JOBID": 1,
      "JOBname": "JOB-1",
      "type": 1,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "ok_job": 1,
      "ok_job_stop": 0,
      "output_unified": 0,
      "input_unified": 0,
      "job_unit": 1
    }
  ],
  "SEQ_lst": [
    {
      "JOBID": 1,
      "SEQID": 1,
      "SEQname": "SEQ-1",
      "type": 0,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "skip": 0,
      "seq_repeat": 1,
      "timeout": 20,
      "ok_seq": 1,
      "ok_stop": 0,
      "countType": 1,
      "ok_screw": 1,
      "ng_stop": 0,
      "ng_unscrew": 0,
      "interrupt_alarm": 1,
      "accu_angle": 1,
      "Thread_Calcu": 31,
      "unscrew_mode": 1,
      "unscrew_force": 50,
      "unscrew_rpm": 300,
      "unscrew_dir": 0,
      "image": null,
      "message": null,
      "delay": 0,
      "input": 0,
      "input_signal": 1,
      "output": 0,
      "output_signal": 1,
      "output_durat": 100,
      "addtion": null,
      "unscrew_count_switch": 0,
      "unscrew_torque_threshold": 0,
      "seq_unit": 1,
      "unscrew_angle_threshold": 0,
      "dt_time": 0,
      "tt_time": 0,
      "total_angle_limit": 0,
      "total_angle_lower": 0
    }
  ],
  "STEP_lst": [
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 1,
      "STEPname": "STEP-1",
      "type": 0,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "StepSwitch": 1,
      "StepRPM": 500,
      "StepOption": 2,
      "StepTime": 1000,
      "StepAngle": 3000,
      "StepTorque": 1.0,
      "StepDirection": 1,
      "StepDelay": 0,
      "StepMoniByWin": 0,
      "StepLimiHi": 30,
      "StepLimiLo": 30,
      "StepHiAngle": 30600,
      "StepLoAngle": 0,
      "StepHiTorque": 1.2,
      "StepLoTorque": 0.8,
      "StepAccelerateOffset": 0.2,
      "StepAccelerateOffsetSign": 43,
      "StepEnableTorqueOffset": 0,
      "StepTorqueOffset": 0,
      "StepTorqueOffsetSign": 43,
      "StepEnableDownShift": 0,
      "StepTorqueDownShift": 0,
      "StepRPMDownShift": 0,
      "StepEnableThreshold": 0,
      "StepTorqueTS": 0,
      "StepReTry": 0,
      "StepUnScrew": 1,
      "StepReTryTorq": 0,
      "StepReTryAngl": 0,
      "StepAngleRecord": 0,
      "StepAutoDetectAngle": 0,
      "InterruptAlarm": 1,
      "OverAngleStop": 1,
      "KValue": 100,
      "step_unit": 1
    },
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 2,
      "STEPname": "STEP-2",
      "type": 0,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "StepSwitch": 1,
      "StepRPM": 500,
      "StepOption": 2,
      "StepTime": 1000,
      "StepAngle": 3000,
      "StepTorque": 1.0,
      "StepDirection": 1,
      "StepDelay": 0,
      "StepMoniByWin": 0,
      "StepLimiHi": 30,
      "StepLimiLo": 30,
      "StepHiAngle": 30600,
      "StepLoAngle": 0,
      "StepHiTorque": 1.2,
      "StepLoTorque": 0.8,
      "StepAccelerateOffset": 0.2,
      "StepAccelerateOffsetSign": 43,
      "StepEnableTorqueOffset": 0,
      "StepTorqueOffset": 0,
      "StepTorqueOffsetSign": 43,
      "StepEnableDownShift": 0,
      "StepTorqueDownShift": 0,
      "StepRPMDownShift": 0,
      "StepEnableThreshold": 0,
      "StepTorqueTS": 0,
      "StepReTry": 0,
      "StepUnScrew": 1,
      "StepReTryTorq": 0,
      "StepReTryAngl": 0,
      "StepAngleRecord": 0,
      "StepAutoDetectAngle": 0,
      "InterruptAlarm": 1,
      "OverAngleStop": 1,
      "KValue": 100,
      "step_unit": 1
    },
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 3,
      "STEPname": "STEP-3",
      "type": 0,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "StepSwitch": 1,
      "StepRPM": 500,
      "StepOption": 2,
      "StepTime": 1000,
      "StepAngle": 3000,
      "StepTorque": 1.0,
      "StepDirection": 1,
      "StepDelay": 0,
      "StepMoniByWin": 0,
      "StepLimiHi": 30,
      "StepLimiLo": 30,
      "StepHiAngle": 30600,
      "StepLoAngle": 0,
      "StepHiTorque": 1.2,
      "StepLoTorque": 0.8,
      "StepAccelerateOffset": 0.2,
      "StepAccelerateOffsetSign": 43,
      "StepEnableTorqueOffset": 0,
      "StepTorqueOffset": 0,
      "StepTorqueOffsetSign": 43,
      "StepEnableDownShift": 0,
      "StepTorqueDownShift": 0,
      "StepRPMDownShift": 0,
      "StepEnableThreshold": 0,
      "StepTorqueTS": 0,
      "StepReTry": 0,
      "StepUnScrew": 1,
      "StepReTryTorq": 0,
      "StepReTryAngl": 0,
      "StepAngleRecord": 0,
      "StepAutoDetectAngle": 0,
      "InterruptAlarm": 1,
      "OverAngleStop": 1,
      "KValue": 100,
      "step_unit": 1
    },
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 4,
      "STEPname": "STEP-4",
      "type": 0,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "StepSwitch": 1,
      "StepRPM": 500,
      "StepOption": 2,
      "StepTime": 1000,
      "StepAngle": 3000,
      "StepTorque": 1.0,
      "StepDirection": 1,
      "StepDelay": 0,
      "StepMoniByWin": 0,
      "StepLimiHi": 30,
      "StepLimiLo": 30,
      "StepHiAngle": 30600,
      "StepLoAngle": 0,
      "StepHiTorque": 1.2,
      "StepLoTorque": 0.8,
      "StepAccelerateOffset": 0.2,
      "StepAccelerateOffsetSign": 43,
      "StepEnableTorqueOffset": 0,
      "StepTorqueOffset": 0,
      "StepTorqueOffsetSign": 43,
      "StepEnableDownShift": 0,
      "StepTorqueDownShift": 0,
      "StepRPMDownShift": 0,
      "StepEnableThreshold": 0,
      "StepTorqueTS": 0,
      "StepReTry": 0,
      "StepUnScrew": 1,
      "StepReTryTorq": 0,
      "StepReTryAngl": 0,
      "StepAngleRecord": 0,
      "StepAutoDetectAngle": 0,
      "InterruptAlarm": 1,
      "OverAngleStop": 1,
      "KValue": 100,
      "step_unit": 1
    },
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 5,
      "STEPname": "STEP-5",
      "type": 0,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "StepSwitch": 1,
      "StepRPM": 500,
      "StepOption": 2,
      "StepTime": 1000,
      "StepAngle": 3000,
      "StepTorque": 1.0,
      "StepDirection": 1,
      "StepDelay": 0,
      "StepMoniByWin": 0,
      "StepLimiHi": 30,
      "StepLimiLo": 30,
      "StepHiAngle": 30600,
      "StepLoAngle": 0,
      "StepHiTorque": 1.2,
      "StepLoTorque": 0.8,
      "StepAccelerateOffset": 0.2,
      "StepAccelerateOffsetSign": 43,
      "StepEnableTorqueOffset": 0,
      "StepTorqueOffset": 0,
      "StepTorqueOffsetSign": 43,
      "StepEnableDownShift": 0,
      "StepTorqueDownShift": 0,
      "StepRPMDownShift": 0,
      "StepEnableThreshold": 0,
      "StepTorqueTS": 0,
      "StepReTry": 0,
      "StepUnScrew": 1,
      "StepReTryTorq": 0,
      "StepReTryAngl": 0,
      "StepAngleRecord": 0,
      "StepAutoDetectAngle": 0,
      "InterruptAlarm": 1,
      "OverAngleStop": 1,
      "KValue": 100,
      "step_unit": 1
    }
  ]
}</textarea>
      </div>
      <div class="editor-foot">
        <div class="left-foot">
          <span id="status" class="status"><?php echo h($t['ready']); ?></span>
          <div class="counts" aria-live="polite">
            <span id="jobCount" class="count-pill">JOB 0</span>
            <span id="seqCount" class="count-pill">SEQ 0</span>
            <span id="stepCount" class="count-pill">STEP 0</span>
          </div>
        </div>
        <button id="sendButton" class="btn btn-primary" type="button">✓ <?php echo h($t['write_db']); ?></button>
      </div>
    </section>

    <section id="responseCard" class="response-card" aria-live="polite">
      <div class="response-title"><?php echo h($t['api_response']); ?></div>
      <pre id="response"></pre>
    </section>
  </main>
</div>

<script src="<?php echo h($idasBaseUrl); ?>/public/js/alertify_min.js?v=<?php echo h($assetVersion); ?>"></script>
<script src="<?php echo h($idasBaseUrl); ?>/public/js/idas_notifications.js?v=<?php echo h($assetVersion); ?>"></script>
<script>
(function () {
  'use strict';
  const T = <?php echo json_encode($t, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  const body = document.getElementById('jsonBody');
  const file = document.getElementById('jsonFile');
  const fileName = document.getElementById('fileName');
  const send = document.getElementById('sendButton');
  const statusElement = document.getElementById('status');
  const responseElement = document.getElementById('response');
  const responseCard = document.getElementById('responseCard');
  const targetIps = document.getElementById('targetIps');
  const targetCount = document.getElementById('targetCount');
  const targetTableWrap = document.getElementById('targetTableWrap');
  const targetTableBody = document.getElementById('targetTableBody');
  const checkTargetsButton = document.getElementById('checkTargetsButton');
  const networkBadge = document.getElementById('networkBadge');
  const apiEndpoint = <?php echo json_encode($idasBaseUrl . '/api/remote_job_config.php', JSON_UNESCAPED_SLASHES); ?>;

  function pad2(value) {
    return String(value).padStart(2, '0');
  }

  function getComputerLocalTime() {
    const now = new Date();
    return now.getFullYear() + '-' +
      pad2(now.getMonth() + 1) + '-' +
      pad2(now.getDate()) + ' ' +
      pad2(now.getHours()) + ':' +
      pad2(now.getMinutes()) + ':' +
      pad2(now.getSeconds());
  }

  function applyComputerTime(data) {
    const currentTime = getComputerLocalTime();
    ['JOB_lst', 'SEQ_lst', 'STEP_lst'].forEach(function (listName) {
      if (!Array.isArray(data[listName])) return;
      data[listName].forEach(function (item) {
        if (item && typeof item === 'object') {
          item.time = currentTime;
        }
      });
    });
    return data;
  }

  function refreshJsonTimeFromComputer() {
    const data = applyComputerTime(JSON.parse(body.value));
    body.value = JSON.stringify(data, null, 2);
    updateCounts();
    return data;
  }

  function parseTargets() {
    const seen = Object.create(null);
    return targetIps.value.split(/[\s,;]+/).map(function (value) { return value.trim(); }).filter(function (value) {
      if (!value || seen[value]) return false;
      seen[value] = true;
      return true;
    });
  }

  function updateTargetCount() {
    targetCount.textContent = T.target_count + ' ' + parseTargets().length;
  }

  function stateHtml(text, state) {
    const span = document.createElement('span');
    span.className = 'state ' + (state || '');
    span.textContent = text;
    return span;
  }

  function renderTargetChecks(checks, writeResults) {
    targetTableBody.textContent = '';
    const writeMap = Object.create(null);
    if (Array.isArray(writeResults)) {
      writeResults.forEach(function (item) { if (item && item.ip) writeMap[item.ip] = item; });
    }
    if (!Array.isArray(checks) || checks.length === 0) {
      targetTableWrap.style.display = 'none';
      return;
    }
    checks.forEach(function (item) {
      const tr = document.createElement('tr');
      const cells = [];
      function addText(text) {
        const td = document.createElement('td');
        td.textContent = text == null || text === '' ? '--' : String(text);
        tr.appendChild(td);
        return td;
      }
      addText(item.ip || '--');
      addText(item.network || '--');
      const connection = document.createElement('td');
      connection.appendChild(stateHtml(item.reachable ? T.reachable : T.unreachable, item.reachable ? 'ok' : 'bad'));
      tr.appendChild(connection);
      const root = document.createElement('td');
      root.appendChild(stateHtml(item.controller_root_exists ? T.exists : T.missing, item.controller_root_exists ? 'ok' : 'bad'));
      tr.appendChild(root);
      const controller = document.createElement('td');
      const loggedOut = item.controller_logged_out;
      controller.appendChild(stateHtml(loggedOut === true ? T.logged_out : (loggedOut === false ? T.in_use : T.unknown), loggedOut === true ? 'ok' : (loggedOut === false ? 'bad' : 'warn')));
      tr.appendChild(controller);
      const result = document.createElement('td');
      const writeResult = writeMap[item.ip];
      if (writeResult) {
        const skipped = !!writeResult.skipped;
        const label = writeResult.success ? T.write_ok_label : (skipped ? T.write_skip_label : T.write_fail_label);
        const state = writeResult.success ? 'ok' : (skipped ? 'warn' : 'bad');
        result.appendChild(stateHtml(label, state));
        if (writeResult.message && writeResult.message !== 'Write complete') result.appendChild(document.createTextNode(' · ' + writeResult.message));
      } else {
        const resultText = item.ready ? T.ready_label : T.not_ready_label;
        result.appendChild(stateHtml(resultText, item.ready ? 'ok' : 'bad'));
        if (item.message && item.message !== 'Ready') result.appendChild(document.createTextNode(' · ' + item.message));
      }
      tr.appendChild(result);
      targetTableBody.appendChild(tr);
    });
    targetTableWrap.style.display = 'block';
  }

  function sanitizeApiResponse(value) {
    if (Array.isArray(value)) return value.map(sanitizeApiResponse);
    if (!value || typeof value !== 'object') return value;
    const cleaned = {};
    Object.keys(value).forEach(function (key) {
      if (key === 'controller_database' || key === 'controller_root' || key === 'backup') return;
      cleaned[key] = sanitizeApiResponse(value[key]);
    });
    return cleaned;
  }

  async function callRemoteApi(payload) {
    const httpResponse = await fetch(apiEndpoint + '?request=' + Date.now(), {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify(payload),
      cache: 'no-store'
    });
    const responseText = await httpResponse.text();
    const responseType = String(httpResponse.headers.get('Content-Type') || '').toLowerCase();
    if (responseType.indexOf('application/json') === -1) {
      throw new Error(httpResponse.redirected ? T.redirected : T.response_invalid);
    }
    let responseData;
    try {
      responseData = JSON.parse(responseText);
    } catch (error) {
      throw new Error(T.response_json_invalid);
    }
    return {httpResponse: httpResponse, data: responseData};
  }

  async function checkTargets(showNotice) {
    const targets = parseTargets();
    if (!targets.length) {
      showUnifiedNotice(T.error, T.target_required, 'error');
      return null;
    }
    checkTargetsButton.disabled = true;
    showStatus(T.checking, '');
    try {
      const result = await callRemoteApi({action: 'check', targets: targets});
      const checks = result.data && result.data.data ? result.data.data.checks : [];
      renderTargetChecks(checks);
      const allReady = !!(result.data && result.data.data && result.data.data.all_ready);
      const readyCount = checks.filter(function (item) { return !!(item && item.ready); }).length;
      const anyReady = readyCount > 0;
      const message = allReady ? T.all_ready : (anyReady ? T.some_not_ready : T.no_ready);
      showStatus(message, allReady ? 'success' : (anyReady ? 'warning' : 'error'));
      if (showNotice) showUnifiedNotice(allReady ? T.success : T.warning_notice, message, allReady ? 'success' : 'warning');
      return {allReady: allReady, anyReady: anyReady, readyCount: readyCount, checks: checks, response: result.data};
    } catch (error) {
      showUnifiedNotice(T.error, error.message || T.connection_failed, 'error');
      showStatus(T.connection_status_failed, 'error');
      return null;
    } finally {
      checkTargetsButton.disabled = false;
    }
  }

  async function loadNetworkInfo() {
    try {
      const httpResponse = await fetch(apiEndpoint + '?action=network&request=' + Date.now(), {cache: 'no-store'});
      const data = await httpResponse.json();
      if (!httpResponse.ok || !data.success || !data.data) throw new Error(T.network_unavailable);
      const networks = Array.isArray(data.data.networks) ? data.data.networks : [];
      networkBadge.textContent = T.local_network + ': ' + (networks.length ? networks.map(function (item) { return item.cidr; }).join(', ') : '--');
      if (!targetIps.value.trim() && data.data.preferred_ip) {
        targetIps.value = data.data.preferred_ip;
        updateTargetCount();
      }
    } catch (error) {
      networkBadge.textContent = T.local_network + ': --';
      showStatus(T.network_unavailable, 'error');
    }
  }

  function showStatus(text, type) {
    statusElement.textContent = text;
    statusElement.className = 'status' + (type ? ' ' + type : '');
  }

  function updateCounts() {
    try {
      const data = JSON.parse(body.value);
      document.getElementById('jobCount').textContent = T.job_count + ' ' + (Array.isArray(data.JOB_lst) ? data.JOB_lst.length : 0);
      document.getElementById('seqCount').textContent = T.seq_count + ' ' + (Array.isArray(data.SEQ_lst) ? data.SEQ_lst.length : 0);
      document.getElementById('stepCount').textContent = T.step_count + ' ' + (Array.isArray(data.STEP_lst) ? data.STEP_lst.length : 0);
    } catch (e) {
      document.getElementById('jobCount').textContent = T.job_count + ' -';
      document.getElementById('seqCount').textContent = T.seq_count + ' -';
      document.getElementById('stepCount').textContent = T.step_count + ' -';
    }
  }

  function showUnifiedNotice(title, message, type) {
    if (window.IdasNotify && typeof window.IdasNotify.show === 'function') {
      window.IdasNotify.show({title: title, message: message, type: type || 'info'});
      return true;
    }
    showStatus(title + ': ' + message, type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'error'));
    return false;
  }

  function confirmWrite(precheck) {
    const targets = parseTargets();
    let message = T.confirm_message + ' (' + T.target_count + ': ' + targets.length + ')';
    if (precheck && !precheck.allReady) {
      message += '\n\n' + T.partial_ready + ' (' + T.ready_label + ': ' + precheck.readyCount + ' / ' + targets.length + ')';
    }
    if (window.alertify && typeof window.alertify.confirm === 'function') {
      return new Promise(function (resolve) {
        const dialog = window.alertify.confirm(T.confirm_title, message,
          function () { resolve(true); },
          function () { resolve(false); }
        );
        dialog.set('labels', {ok: T.confirm_ok, cancel: T.cancel});
      });
    }
    showUnifiedNotice(T.error, T.common_notice_failed, 'error');
    return Promise.resolve(false);
  }

  document.getElementById('formatButton').addEventListener('click', function () {
    try {
      body.value = JSON.stringify(JSON.parse(body.value), null, 2);
      updateCounts();
      showStatus(T.json_valid, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.json_invalid + error.message, 'error');
      showStatus(T.json_invalid + error.message, 'error');
    }
  });

  file.addEventListener('change', async function () {
    if (!file.files[0]) return;
    fileName.textContent = file.files[0].name;
    try {
      body.value = await file.files[0].text();
      body.value = JSON.stringify(JSON.parse(body.value), null, 2);
      updateCounts();
      showStatus(T.file_loaded + file.files[0].name, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.file_invalid + error.message, 'error');
      showStatus(T.file_invalid + error.message, 'error');
    }
  });

  targetIps.addEventListener('input', updateTargetCount);
  checkTargetsButton.addEventListener('click', function () { checkTargets(true); });
  body.addEventListener('input', updateCounts);

  send.addEventListener('click', async function () {
    let payload;
    try {
      payload = refreshJsonTimeFromComputer();
    } catch (error) {
      showUnifiedNotice(T.error, T.json_invalid + error.message, 'error');
      showStatus(T.json_invalid + error.message, 'error');
      return;
    }

    const targets = parseTargets();
    if (!targets.length) {
      showUnifiedNotice(T.error, T.target_required, 'error');
      showStatus(T.target_required, 'error');
      return;
    }

    const precheck = await checkTargets(false);
    if (!precheck) return;
    if (!precheck.anyReady) {
      showUnifiedNotice(T.warning_notice, T.no_ready, 'warning');
      return;
    }
    if (!precheck.allReady) {
      showUnifiedNotice(T.warning_notice, T.partial_ready, 'warning');
    }

    if (!(await confirmWrite(precheck))) return;

    send.disabled = true;
    checkTargetsButton.disabled = true;
    responseCard.style.display = 'none';
    showStatus(T.write_precheck, '');
    try {
      // The backend ALWAYS runs the full preflight again immediately before
      // writing.  The separate Check button is only for the operator's view.
      const result = await callRemoteApi({action: 'write', targets: targets, payload: payload});
      const responseData = result.data;
      responseElement.textContent = JSON.stringify(sanitizeApiResponse(responseData), null, 2);
      responseCard.style.display = 'block';

      const batchData = responseData && responseData.data ? responseData.data : {};
      renderTargetChecks(batchData.checks || [], batchData.results || []);

      if (responseData && responseData.success) {
        showUnifiedNotice(T.success, T.write_all_success, 'success');
        showStatus(T.write_all_success, 'success');
      } else if (batchData && batchData.any_success) {
        showUnifiedNotice(T.warning_notice, T.write_partial, 'warning');
        showStatus(T.write_partial, 'warning');
      } else if (responseData && responseData.error && responseData.error.code === 'NO_READY_TARGETS') {
        showUnifiedNotice(T.warning_notice, T.no_ready, 'warning');
        showStatus(T.no_ready, 'error');
      } else {
        const message = (responseData && responseData.error && responseData.error.message) || T.write_none;
        showUnifiedNotice(T.error, message, 'error');
        showStatus(T.write_none, 'error');
      }
    } catch (error) {
      responseElement.textContent = error.message;
      responseCard.style.display = 'block';
      showUnifiedNotice(T.error, error.message || T.connection_failed, 'error');
      showStatus(T.connection_status_failed, 'error');
    } finally {
      send.disabled = false;
      checkTargetsButton.disabled = false;
    }
  });

  updateTargetCount();
  loadNetworkInfo();

  try {
    refreshJsonTimeFromComputer();
  } catch (error) {
    updateCounts();
  }
}());
</script>
</body>
</html>
