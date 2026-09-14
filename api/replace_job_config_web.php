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

// This page can be opened directly or included by replace_job_config.php.
// Resolve the installed iDAS version locally so the standalone entry point
// never depends on functions declared by the API controller.
$idasApiVersion = 'unknown';
$infoRaw = @file_get_contents(dirname(__DIR__) . '/info.json');
if (is_string($infoRaw) && trim($infoRaw) !== '') {
    $infoData = json_decode($infoRaw, true);
    if (is_array($infoData)) {
        $versionValue = trim((string)($infoData['idas_version'] ?? ''));
        if ($versionValue !== '') $idasApiVersion = $versionValue;
    }
}

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
        'api_tool' => 'Deployment Tool',
        'section_targets' => 'Target Controllers',
        'deployment_mode' => 'Deployment Mode',
        'mode_single' => 'Single Controller',
        'mode_multi' => 'Multiple Controllers',
        'mode_single_hint' => 'Deploy to one NTCS7 controller.',
        'mode_multi_hint' => 'Deploy the same configuration to multiple NTCS7 controllers.',
        'single_target_ip' => 'Controller IP',
        'single_placeholder' => 'Example: 192.168.100.150',
        'section_controllers' => 'Controller Status',
        'section_configuration' => 'Configuration',
        'section_deployment' => 'Deployment',
        'section_result' => 'Deployment Result',
        'controllers_empty' => 'Check controllers to view availability and write status.',
        'config_summary' => 'Configuration Summary',
        'deployment_hint' => 'Preview changes first, then write only to controllers that pass all checks.',
        'result_hint' => 'Batch results appear here after preview or write.',
        'json_title' => 'JSON Data',
        'json_hint' => 'Paste JSON below or load a .json file.',
        'choose_file' => 'Choose JSON File',
        'no_file' => 'No file selected',
        'format_json' => 'Format JSON',
        'write_db' => 'Write to Controller DB',
        'preview' => 'Preview Changes',
        'previewing' => 'Checking changes…',
        'preview_done' => 'Preview complete',
        'inserted' => 'Add',
        'updated' => 'Overwrite',
        'verified' => 'Verified',
        'retry_failed' => 'Retry Failed',
        'retrying_failed' => 'Retrying failed controllers…',
        'no_failed_targets' => 'There are no failed controllers to retry.',
        'api_details' => 'Technical details',
        'expand_json' => 'Expand JSON',
        'collapse_json' => 'Collapse JSON',
        'download_sample' => 'Download Sample JSON',
        'copy_mes_request' => 'Copy MES Request',
        'copy_curl' => 'Copy curl Command',
        'copy_curl_success' => 'curl command copied.',
        'copy_curl_failed' => 'Unable to copy the curl command.',
        'export_json' => 'Export Result JSON',
        'export_csv' => 'Export Result CSV',
        'export_ready' => 'Deployment result exported.',
        'no_result_export' => 'There is no deployment result to export yet.',
        'request_too_large' => 'Request is too large to send.',
        'copy_success' => 'MES API request copied.',
        'copy_failed' => 'Unable to copy the MES API request.',
        'download_ready' => 'Sample JSON downloaded.',
        'preview_summary' => 'Change Summary',
        'overall_status' => 'Overall Status',
        'overall_all_success' => 'All successful',
        'overall_partial' => 'Partially successful',
        'overall_all_failed' => 'Failed',
        'overall_preview' => 'Preview ready',
        'recheck_after_write' => 'Controller status refreshed after write.',
        'result_success' => 'Success',
        'result_skipped' => 'Skipped',
        'result_failed' => 'Failed',
        'elapsed_time' => 'Elapsed time',
        'err_default_target_unavailable' => 'No valid controller IP is configured in iDAS Settings → Connection.',
        'err_not_same_subnet' => 'Target is not on an active local subnet.',
        'err_target_unreachable' => 'Target is unreachable.',
        'err_controller_in_use' => 'Controller is not logged out.',
        'err_root_missing' => 'NTCS7 directory is missing.',
        'err_not_ntcs7' => 'Target did not identify as NTCS7.',
        'err_invalid_response' => 'Invalid response from target.',
        'err_verify_failed' => 'Write verification failed.',
        'err_write_failed' => 'Write failed.',
        'err_not_ready' => 'Target is not ready.',
        'leave_warning' => 'There are unsaved JSON changes or an operation is still running.',
        'confirm_ready' => 'Writable',
        'confirm_skipped' => 'Will skip',
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
        'target_hint' => 'Enter one or more IPv4 addresses on the same subnet. Before every write, iDAS verifies connectivity, NTCS7 availability, and controller logout status.',
        'target_ips' => 'Target IP',
        'target_placeholder' => "Example:\n192.168.100.150\n192.168.100.151",
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
        'col_root' => 'NTCS7',
        'col_controller' => 'Controller',
        'col_result' => 'Result',
        'col_elapsed' => 'Elapsed',
        'reachable' => 'Reachable',
        'unreachable' => 'Unreachable',
        'exists' => 'Exists',
        'missing' => 'Missing',
        'logged_out' => 'Logged out',
        'in_use' => 'Not logged out',
        'unknown' => 'Unknown',
        'no_check_result' => 'No check result',
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
        'api_tool' => '部署工具',
        'section_targets' => '目標控制器',
        'deployment_mode' => '部署模式',
        'mode_single' => '單台控制器',
        'mode_multi' => '多台控制器',
        'mode_single_hint' => '部署到單一 NTCS7 控制器。',
        'mode_multi_hint' => '將同一份設定部署到多台 NTCS7 控制器。',
        'single_target_ip' => '控制器 IP',
        'single_placeholder' => '例如：192.168.100.150',
        'section_controllers' => '控制器狀態',
        'section_configuration' => '設定資料',
        'section_deployment' => '部署',
        'section_result' => '部署結果',
        'controllers_empty' => '請先檢查控制器，以查看連線與可寫入狀態。',
        'config_summary' => '設定摘要',
        'deployment_hint' => '建議先預覽異動，再寫入通過所有檢查的控制器。',
        'result_hint' => '預覽或寫入完成後，批次結果會顯示在這裡。',
        'json_title' => 'JSON 資料',
        'json_hint' => '可直接貼上 JSON，或載入 .json 檔案。',
        'choose_file' => '選擇 JSON 檔案',
        'no_file' => '尚未選擇檔案',
        'format_json' => '格式化 JSON',
        'write_db' => '寫入控制器 DB',
        'preview' => '預覽異動',
        'previewing' => '正在比對異動…',
        'preview_done' => '異動預覽完成',
        'inserted' => '新增',
        'updated' => '覆蓋',
        'verified' => '驗證完成',
        'retry_failed' => '重試失敗設備',
        'retrying_failed' => '正在重試失敗設備…',
        'no_failed_targets' => '目前沒有失敗設備需要重試。',
        'api_details' => '技術詳細資訊',
        'expand_json' => '展開 JSON',
        'collapse_json' => '收合 JSON',
        'download_sample' => '下载示例 JSON',
        'copy_mes_request' => '复制 MES API Request',
        'copy_curl' => '复制 curl 命令',
        'copy_curl_success' => 'curl 命令已复制。',
        'copy_curl_failed' => '无法复制 curl 命令。',
        'export_json' => '导出结果 JSON',
        'export_csv' => '导出结果 CSV',
        'export_ready' => '部署结果已导出。',
        'no_result_export' => '目前没有可导出的部署结果。',
        'request_too_large' => 'Request 数据过大，无法发送。',
        'copy_success' => '已复制 MES API Request。',
        'copy_failed' => '无法复制 MES API Request。',
        'download_ready' => '示例 JSON 已下载。',
        'preview_summary' => '变更摘要',
        'overall_status' => '整体状态',
        'overall_all_success' => '全部成功',
        'overall_partial' => '部分成功',
        'overall_all_failed' => '执行失败',
        'overall_preview' => '预览完成',
        'download_sample' => '下載範例 JSON',
        'copy_mes_request' => '複製 MES API Request',
        'copy_curl' => '複製 curl 指令',
        'copy_curl_success' => 'curl 指令已複製。',
        'copy_curl_failed' => '無法複製 curl 指令。',
        'export_json' => '匯出結果 JSON',
        'export_csv' => '匯出結果 CSV',
        'export_ready' => '部署結果已匯出。',
        'no_result_export' => '目前沒有可匯出的部署結果。',
        'request_too_large' => 'Request 資料過大，無法送出。',
        'copy_success' => '已複製 MES API Request。',
        'copy_failed' => '無法複製 MES API Request。',
        'download_ready' => '範例 JSON 已下載。',
        'preview_summary' => '異動摘要',
        'overall_status' => '整體狀態',
        'overall_all_success' => '全部成功',
        'overall_partial' => '部分成功',
        'overall_all_failed' => '執行失敗',
        'overall_preview' => '預覽完成',
        'recheck_after_write' => '寫入後已重新確認控制器狀態。',
        'result_success' => '成功',
        'result_skipped' => '略過',
        'result_failed' => '失敗',
        'elapsed_time' => '花費時間',
        'err_default_target_unavailable' => 'iDAS「設定 → 連線」尚未設定有效的控制器 IP。',
        'err_not_same_subnet' => '目標 IP 不在目前有效的本機網段內。',
        'err_target_unreachable' => '目標控制器無法連線。',
        'err_controller_in_use' => '控制器尚未登出。',
        'err_root_missing' => 'NTCS7 目錄不存在。',
        'err_not_ntcs7' => '目標設備不是 NTCS7。',
        'err_invalid_response' => '目標控制器回應格式錯誤。',
        'err_verify_failed' => '寫入後驗證失敗。',
        'err_write_failed' => '寫入失敗。',
        'err_not_ready' => '目標控制器尚未符合寫入條件。',
        'leave_warning' => '目前有尚未寫入的 JSON 變更，或仍有操作執行中。',
        'confirm_ready' => '可寫入',
        'confirm_skipped' => '將略過',
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
        'target_hint' => '可輸入同網段的一台或多台 IPv4。每次寫入前都會重新確認連線、NTCS7 可用狀態與控制器是否已登出。',
        'target_ips' => '目標 IP',
        'target_placeholder' => "例如：\n192.168.100.150\n192.168.100.151",
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
        'col_root' => 'NTCS7',
        'col_controller' => '控制器',
        'col_result' => '結果',
        'col_elapsed' => '花費時間',
        'reachable' => '可連線',
        'unreachable' => '無法連線',
        'exists' => '存在',
        'missing' => '不存在',
        'logged_out' => '已登出',
        'in_use' => '未登出',
        'unknown' => '未知',
        'no_check_result' => '未取得檢查結果',
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
        'api_tool' => '部署工具',
        'section_targets' => '目标控制器',
        'deployment_mode' => '部署模式',
        'mode_single' => '单台控制器',
        'mode_multi' => '多台控制器',
        'mode_single_hint' => '部署到单一 NTCS7 控制器。',
        'mode_multi_hint' => '将同一份设置部署到多台 NTCS7 控制器。',
        'single_target_ip' => '控制器 IP',
        'single_placeholder' => '例如：192.168.100.150',
        'section_controllers' => '控制器状态',
        'section_configuration' => '设置数据',
        'section_deployment' => '部署',
        'section_result' => '部署结果',
        'controllers_empty' => '请先检查控制器，以查看连接与可写入状态。',
        'config_summary' => '设置摘要',
        'deployment_hint' => '建议先预览变更，再写入通过所有检查的控制器。',
        'result_hint' => '预览或写入完成后，批次结果会显示在这里。',
        'json_title' => 'JSON 数据',
        'json_hint' => '可直接粘贴 JSON，或载入 .json 文件。',
        'choose_file' => '选择 JSON 文件',
        'no_file' => '尚未选择文件',
        'format_json' => '格式化 JSON',
        'write_db' => '写入控制器 DB',
        'preview' => '预览变更',
        'previewing' => '正在比对变更…',
        'preview_done' => '变更预览完成',
        'inserted' => '新增',
        'updated' => '覆盖',
        'verified' => '验证完成',
        'retry_failed' => '重试失败设备',
        'retrying_failed' => '正在重试失败设备…',
        'no_failed_targets' => '目前没有失败设备需要重试。',
        'api_details' => '技术详细信息',
        'expand_json' => '展开 JSON',
        'collapse_json' => '收起 JSON',
        'recheck_after_write' => '写入后已重新确认控制器状态。',
        'result_success' => '成功',
        'result_skipped' => '跳过',
        'result_failed' => '失败',
        'elapsed_time' => '耗时',
        'err_default_target_unavailable' => 'iDAS“设置 → 连接”尚未设置有效的控制器 IP。',
        'err_not_same_subnet' => '目标 IP 不在当前有效的本机网段内。',
        'err_target_unreachable' => '目标控制器无法连接。',
        'err_controller_in_use' => '控制器尚未登出。',
        'err_root_missing' => 'NTCS7 目录不存在。',
        'err_not_ntcs7' => '目标设备不是 NTCS7。',
        'err_invalid_response' => '目标控制器响应格式错误。',
        'err_verify_failed' => '写入后验证失败。',
        'err_write_failed' => '写入失败。',
        'err_not_ready' => '目标控制器尚未符合写入条件。',
        'leave_warning' => '目前有尚未写入的 JSON 变更，或仍有操作执行中。',
        'confirm_ready' => '可写入',
        'confirm_skipped' => '将跳过',
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
        'target_hint' => '可输入同网段的一台或多台 IPv4。每次写入前都会重新确认连接、NTCS7 可用状态与控制器是否已登出。',
        'target_ips' => '目标 IP',
        'target_placeholder' => "例如：\n192.168.100.150\n192.168.100.151",
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
        'col_root' => 'NTCS7',
        'col_controller' => '控制器',
        'col_result' => '结果',
        'col_elapsed' => '耗时',
        'reachable' => '可连接',
        'unreachable' => '无法连接',
        'exists' => '存在',
        'missing' => '不存在',
        'logged_out' => '已登出',
        'in_use' => '未登出',
        'unknown' => '未知',
        'no_check_result' => '未取得检查结果',
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
    :root{--navy:#21345d;--navy-2:#2c4678;--blue:#1769c2;--blue-dark:#1058a4;--bg:#edf2f7;--panel:#fff;--line:#d8e0ea;--line-soft:#e9eef4;--text:#182230;--muted:#68758a;--soft:#f7f9fc;--success:#148044;--error:#c83232;--warn:#b87500}
    *{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--text);font-family:Arial,"Microsoft JhengHei","PingFang TC","PingFang SC",sans-serif;-webkit-font-smoothing:antialiased} button,input,textarea{font:inherit}
    .page{width:min(1220px,calc(100% - 36px));margin:18px auto 32px}.hero{position:relative;overflow:hidden;padding:18px 24px;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff;border-radius:13px;box-shadow:0 9px 24px rgba(29,48,84,.12)}.hero:after{content:"";position:absolute;width:230px;height:230px;border-radius:50%;right:-80px;top:-135px;background:rgba(255,255,255,.05)}.hero-top{display:flex;align-items:center;justify-content:space-between;gap:18px;position:relative;z-index:1}.hero h1{font-size:23px;line-height:1.25;margin:0 0 5px;font-weight:700}.hero .subtitle{margin:0;color:#dce6fb;font-size:12px;line-height:1.5}.tag{display:none}
    .content{padding:18px 0 0;display:grid;gap:14px}.section-card,.response-card{border:1px solid var(--line);border-radius:12px;background:var(--panel);box-shadow:0 4px 16px rgba(31,47,70,.045);overflow:hidden}.section-head{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:14px 16px;background:var(--soft);border-bottom:1px solid var(--line)}.section-title{font-size:15px;font-weight:800;color:#243650}.section-hint{margin-top:4px;color:var(--muted);font-size:12px;line-height:1.5;max-width:820px}.section-body{padding:15px 16px}.network-badge{flex:none;padding:6px 9px;border-radius:999px;background:#e8f1ff;color:#2c568b;font-size:11px;font-weight:700}
    .mode-block{margin-bottom:14px}.mode-label{display:block;margin-bottom:7px;font-size:12px;font-weight:800;color:#40516b}.mode-switch{display:inline-grid;grid-template-columns:1fr 1fr;padding:3px;border:1px solid #cbd5e1;border-radius:10px;background:#f4f7fb;gap:3px}.mode-option{position:relative}.mode-option input{position:absolute;opacity:0;pointer-events:none}.mode-option span{display:block;min-width:138px;padding:8px 14px;border-radius:7px;color:#536177;font-size:12px;font-weight:800;text-align:center;cursor:pointer;transition:.15s}.mode-option input:checked+span{background:#fff;color:var(--blue);box-shadow:0 1px 5px rgba(31,47,70,.12)}.mode-option input:focus-visible+span{outline:3px solid rgba(42,127,220,.22);outline-offset:1px}.mode-option span:hover{background:#eef4fb}.mode-option input:checked+span:hover{background:#fff}.mode-hint{margin-top:7px;color:var(--muted);font-size:11px}.target-mode-panel[hidden]{display:none!important}.single-target-input{display:block;width:100%;height:40px;border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;background:#fff;color:#16233a;font:13px/1.45 Consolas,"SFMono-Regular","Courier New",monospace}.single-target-input:focus{outline:3px solid rgba(42,127,220,.16);border-color:#6fa7df}.target-row{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end}.target-field label{display:block;margin:0 0 7px;font-size:12px;font-weight:800;color:#40516b}.target-input{display:block;width:100%;height:64px;min-height:64px;max-height:160px;resize:vertical;border:1px solid #cbd5e1;border-radius:8px;padding:9px 12px;background:#fff;color:#16233a;font:13px/1.45 Consolas,"SFMono-Regular","Courier New",monospace}.target-input:focus{outline:3px solid rgba(42,127,220,.16);border-color:#6fa7df}.target-meta{margin-top:8px;color:var(--muted);font-size:11px}
    .mini-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px;margin-top:12px}.mini-stat{position:relative;padding:10px 12px 10px 14px;border:1px solid var(--line-soft);border-radius:9px;background:#fafcff;overflow:hidden}.mini-stat:before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:#9aa6b6}.mini-stat:nth-child(2):before{background:var(--success)}.mini-stat:nth-child(3):before{background:#d18a11}.mini-stat-label{display:block;color:var(--muted);font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.45px}.mini-stat-value{display:block;margin-top:3px;color:#243650;font-size:19px;font-weight:800}.controllers-empty{padding:18px;color:var(--muted);font-size:12px;text-align:center;background:#fbfcfe}.target-table-wrap{display:none;overflow:auto}.target-table{width:100%;border-collapse:collapse;min-width:760px;font-size:12px}.target-table th,.target-table td{padding:10px 12px;border-bottom:1px solid var(--line-soft);text-align:left;vertical-align:middle}.target-table th{background:#f9fbfd;color:#536177;font-weight:800;white-space:nowrap}.target-table tr:last-child td{border-bottom:0}.state{display:inline-flex;align-items:center;gap:6px;padding:4px 8px;border-radius:999px;border:1px solid #dbe3ec;background:#f7f9fc;color:#64748b;font-weight:800;white-space:nowrap;font-size:11px}.state:before{content:"";width:6px;height:6px;border-radius:50%;background:#9aa6b6}.state.ok{color:var(--success);background:#effaf4;border-color:#ccebd9}.state.ok:before{background:var(--success)}.state.bad{color:var(--error);background:#fff2f2;border-color:#f2cccc}.state.bad:before{background:var(--error)}.state.warn{color:var(--warn);background:#fff8ea;border-color:#f1dfb7}.state.warn:before{background:#d18a11}
    .config-layout{display:grid;gap:12px}.config-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.config-stat{border:1px solid var(--line-soft);border-radius:10px;padding:12px 14px;background:#fafcff}.config-stat span{display:block}.config-stat .label{font-size:11px;color:var(--muted);font-weight:700}.config-stat .value{margin-top:3px;font-size:22px;font-weight:800;color:#294165}.editor-shell{border:1px solid var(--line);border-radius:10px;overflow:hidden}.editor-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:10px 12px;background:#f8fafc;border-bottom:1px solid var(--line)}.toolbar{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap}#jsonFile{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}.file-name{max-width:240px;color:var(--muted);font-size:11px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.editor-body{display:block}.editor-shell.collapsed .editor-body{display:none}.editor-shell.collapsed .editor-toolbar{border-bottom:0}textarea#jsonBody{display:block;width:100%;height:390px;min-height:280px;resize:vertical;padding:16px 18px;border:0;background:#fbfcfe;color:#13223b;font:13px/1.58 Consolas,"SFMono-Regular","Courier New",monospace;tab-size:2}textarea#jsonBody:focus{outline:0;background:#fff}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:38px;border:1px solid transparent;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:800;cursor:pointer;transition:background .15s,border-color .15s,box-shadow .15s,transform .05s;white-space:nowrap;text-decoration:none}.btn:active{transform:translateY(1px)}.btn-secondary{background:#fff;border-color:#cbd5e1;color:#314158}.btn-secondary:hover{background:#f5f7fa;border-color:#aeb9c8}.btn-primary{background:var(--blue);color:#fff;box-shadow:0 3px 8px rgba(23,105,194,.18)}.btn-primary:hover{background:var(--blue-dark)}.btn:focus-visible,input:focus-visible,textarea:focus-visible{outline:3px solid rgba(42,127,220,.22);outline-offset:1px}.btn:disabled{opacity:.55;cursor:not-allowed;box-shadow:none;transform:none}
    .api-tools{display:flex;align-items:center;justify-content:flex-end;gap:8px;flex-wrap:wrap;padding-top:2px}.btn-neutral{background:#f8fafc;border-color:#d8e0ea;color:#40516b}.btn-neutral:hover{background:#eef3f8;border-color:#c2ccd8}.preview-summary{display:none;margin-top:12px;border:1px solid var(--line-soft);border-radius:10px;background:#fbfcfe;padding:12px}.preview-summary-title{font-size:12px;font-weight:800;color:#40516b;margin-bottom:9px}.preview-summary-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px}.preview-summary-item{border:1px solid var(--line-soft);border-radius:8px;background:#fff;padding:9px 10px}.preview-summary-item strong{display:block;color:#294165;font-size:12px;margin-bottom:5px}.preview-summary-item span{display:block;color:var(--muted);font-size:11px;line-height:1.55}.overall-result{display:none;margin:14px 16px 0;padding:10px 12px;border-radius:9px;border:1px solid var(--line);font-size:12px;font-weight:800}.overall-result.success{display:block;color:var(--success);background:#effaf4;border-color:#ccebd9}.overall-result.warning{display:block;color:var(--warn);background:#fff8ea;border-color:#f1dfb7}.overall-result.error{display:block;color:var(--error);background:#fff2f2;border-color:#f2cccc}.overall-result.preview{display:block;color:var(--blue);background:#eef6ff;border-color:#cfe1f6}..deployment-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:16px;align-items:center}.deployment-copy{color:var(--muted);font-size:12px;line-height:1.6}.status{display:inline-flex;align-items:center;gap:7px;margin-top:8px;font-size:12px;font-weight:800;color:var(--muted)}.status:before{content:"";width:8px;height:8px;border-radius:50%;background:#98a4b5}.status.success{color:var(--success)}.status.success:before{background:var(--success)}.status.warning{color:var(--warn)}.status.warning:before{background:#d18a11}.status.error{color:var(--error)}.status.error:before{background:var(--error)}.action-buttons{display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap}.result-placeholder{padding:16px;color:var(--muted);font-size:12px}.result-metrics{display:none;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;padding:14px 16px 0}.result-metric{padding:11px 13px;border:1px solid var(--line-soft);border-radius:9px;background:#fafcff}.result-metric-label{display:block;color:var(--muted);font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.35px}.result-metric-value{display:block;margin-top:3px;font-size:21px;font-weight:900}.result-metric.success .result-metric-value{color:var(--success)}.result-metric.skipped .result-metric-value{color:var(--warn)}.result-metric.failed .result-metric-value{color:var(--error)}.batch-summary{display:none;padding:12px 16px 14px;color:#34465f;font-size:13px;line-height:1.7}.response-card{display:none}.response-title{display:block;cursor:pointer;padding:12px 15px;background:#111c2f;color:#dce8ff;font-size:12px;font-weight:800;user-select:none}.response-title::-webkit-details-marker{display:none}.response-title:after{content:' ▾';float:right}.response-card[open] .response-title:after{content:' ▴'}pre{margin:0;padding:16px;background:#101827;color:#eaf1ff;white-space:pre-wrap;word-break:break-word;max-height:320px;overflow:auto;font:12px/1.55 Consolas,"Courier New",monospace}
    @media(max-width:760px){.preview-summary-grid{grid-template-columns:1fr}.api-tools{justify-content:flex-start}.mode-switch{display:grid;width:100%}.mode-option span{min-width:0}.page{width:100%;margin:0}.hero{border-radius:0;padding:18px 16px}.hero h1{font-size:20px}.tag{display:none}.content{padding:12px}.section-head{flex-direction:column}.network-badge{align-self:flex-start}.target-row,.deployment-grid{grid-template-columns:1fr}.target-row .btn,.action-buttons .btn-primary{width:100%}.editor-toolbar{align-items:flex-start;flex-direction:column}.toolbar{justify-content:flex-start;width:100%}.file-name{max-width:100%}.action-buttons{justify-content:stretch}textarea#jsonBody{height:48vh;min-height:300px;padding:14px}}
    @media(max-width:430px){.content{padding:10px}.mini-stats,.config-summary,.result-metrics{gap:6px}.mini-stat,.config-stat{padding:9px}.mini-stat-value{font-size:16px}.config-stat .value{font-size:18px}.toolbar .btn{flex:1 1 auto}.file-name{flex-basis:100%}.section-body{padding:12px}}
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
    <section class="section-card">
      <div class="section-head"><div><div class="section-title"><?php echo h($t['section_targets']); ?></div><div class="section-hint"><?php echo h($t['target_hint']); ?></div></div><span id="networkBadge" class="network-badge"><?php echo h($t['local_network']); ?>: --</span></div>
      <div class="section-body">
        <div class="mode-block">
          <span class="mode-label"><?php echo h($t['deployment_mode']); ?></span>
          <div class="mode-switch" role="radiogroup" aria-label="<?php echo h($t['deployment_mode']); ?>">
            <label class="mode-option"><input id="modeSingle" type="radio" name="deploymentMode" value="single" checked><span><?php echo h($t['mode_single']); ?></span></label>
            <label class="mode-option"><input id="modeMulti" type="radio" name="deploymentMode" value="multi"><span><?php echo h($t['mode_multi']); ?></span></label>
          </div>
          <div id="modeHint" class="mode-hint"><?php echo h($t['mode_single_hint']); ?></div>
        </div>
        <div id="singleTargetPanel" class="target-mode-panel">
          <div class="target-row"><div class="target-field"><label for="singleTargetIp"><?php echo h($t['single_target_ip']); ?></label><input id="singleTargetIp" class="single-target-input" type="text" inputmode="decimal" autocomplete="off" spellcheck="false" placeholder="<?php echo h($t['single_placeholder']); ?>"></div><button id="checkTargetsButton" class="btn btn-secondary" type="button">⌁ <?php echo h($t['check_targets']); ?></button></div>
        </div>
        <div id="multiTargetPanel" class="target-mode-panel" hidden>
          <div class="target-row"><div class="target-field"><label for="targetIps"><?php echo h($t['target_ips']); ?></label><textarea id="targetIps" class="target-input" rows="2" spellcheck="false" placeholder="<?php echo h($t['target_placeholder']); ?>"></textarea></div><button id="checkTargetsButtonMulti" class="btn btn-secondary" type="button">⌁ <?php echo h($t['check_targets']); ?></button></div>
        </div>
        <div class="target-meta"><?php echo h($t['same_subnet_only']); ?></div>
        <div id="multiStats" class="mini-stats" aria-live="polite" style="display:none"><div class="mini-stat"><span class="mini-stat-label"><?php echo h($t['target_count']); ?></span><strong id="targetCountValue" class="mini-stat-value">0</strong></div><div class="mini-stat"><span class="mini-stat-label"><?php echo h($t['confirm_ready']); ?></span><strong id="readyCountValue" class="mini-stat-value">--</strong></div><div class="mini-stat"><span class="mini-stat-label"><?php echo h($t['confirm_skipped']); ?></span><strong id="skippedCountValue" class="mini-stat-value">--</strong></div></div>
        <span id="targetCount" style="display:none"><?php echo h($t['target_count']); ?> 0</span>
      </div>
    </section>
    <section class="section-card"><div class="section-head"><div><div class="section-title"><?php echo h($t['section_controllers']); ?></div><div class="section-hint"><?php echo h($t['controllers_empty']); ?></div></div></div><div id="targetTableEmpty" class="controllers-empty"><?php echo h($t['controllers_empty']); ?></div><div id="targetTableWrap" class="target-table-wrap"><table class="target-table"><thead><tr><th><?php echo h($t['col_ip']); ?></th><th><?php echo h($t['col_subnet']); ?></th><th><?php echo h($t['col_connection']); ?></th><th><?php echo h($t['col_root']); ?></th><th><?php echo h($t['col_controller']); ?></th><th><?php echo h($t['col_result']); ?></th><th><?php echo h($t['col_elapsed']); ?></th></tr></thead><tbody id="targetTableBody"></tbody></table></div></section>
    <section class="section-card"><div class="section-head"><div><div class="section-title"><?php echo h($t['section_configuration']); ?></div><div class="section-hint"><?php echo h($t['json_hint']); ?></div></div></div><div class="section-body config-layout"><div class="config-summary" aria-label="<?php echo h($t['config_summary']); ?>"><div class="config-stat"><span class="label"><?php echo h($t['job_count']); ?></span><span id="jobCount" class="value">0</span></div><div class="config-stat"><span class="label"><?php echo h($t['seq_count']); ?></span><span id="seqCount" class="value">0</span></div><div class="config-stat"><span class="label"><?php echo h($t['step_count']); ?></span><span id="stepCount" class="value">0</span></div></div><div id="editorShell" class="editor-shell"><div class="editor-toolbar"><strong><?php echo h($t['json_title']); ?></strong><div class="toolbar"><input id="jsonFile" type="file" accept="application/json,.json"><label for="jsonFile" class="btn btn-secondary" role="button">↥ <?php echo h($t['choose_file']); ?></label><span id="fileName" class="file-name"><?php echo h($t['no_file']); ?></span><button id="formatButton" class="btn btn-secondary" type="button">{ } <?php echo h($t['format_json']); ?></button><button id="editorToggleButton" class="btn btn-secondary" type="button" aria-expanded="true">▴ <?php echo h($t['collapse_json']); ?></button></div></div><div id="editorBody" class="editor-body"><textarea id="jsonBody" aria-label="<?php echo h($t['json_title']); ?>" spellcheck="false">{
  "JOB_lst": [
    {
      "JOBID": 1,
      "JOBname": "JOB-1",
      "type": 1,
      "time": "2026-09-11 00:00:00",
      "act": 1,
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
}</textarea></div></div><div class="api-tools"><button id="downloadSampleButton" class="btn btn-neutral" type="button">↓ <?php echo h($t['download_sample']); ?></button><button id="copyMesButton" class="btn btn-neutral" type="button">⧉ <?php echo h($t['copy_mes_request']); ?></button><button id="copyCurlButton" class="btn btn-neutral" type="button">⌘ <?php echo h($t['copy_curl']); ?></button></div></div></section>
    <section class="section-card"><div class="section-head"><div><div class="section-title"><?php echo h($t['section_deployment']); ?></div><div class="section-hint"><?php echo h($t['deployment_hint']); ?></div></div></div><div class="section-body"><div class="deployment-grid"><div class="deployment-copy"><?php echo h($t['partial_ready']); ?><br><span id="status" class="status"><?php echo h($t['ready']); ?></span></div><div class="action-buttons"><button id="previewButton" class="btn btn-secondary" type="button">≋ <?php echo h($t['preview']); ?></button><button id="retryFailedButton" class="btn btn-secondary" type="button" style="display:none">↻ <?php echo h($t['retry_failed']); ?></button><button id="sendButton" class="btn btn-primary" type="button">✓ <?php echo h($t['write_db']); ?></button></div></div><div id="previewSummary" class="preview-summary"><div class="preview-summary-title"><?php echo h($t['preview_summary']); ?></div><div class="preview-summary-grid"><div class="preview-summary-item"><strong>JOB</strong><span id="previewJobs">--</span></div><div class="preview-summary-item"><strong>SEQ</strong><span id="previewSeqs">--</span></div><div class="preview-summary-item"><strong>STEP</strong><span id="previewSteps">--</span></div></div></div></div></section>
    <section class="section-card"><div class="section-head"><div><div class="section-title"><?php echo h($t['section_result']); ?></div><div class="section-hint"><?php echo h($t['result_hint']); ?></div></div></div><div id="overallResult" class="overall-result" aria-live="polite"></div><div id="resultPlaceholder" class="result-placeholder"><?php echo h($t['result_hint']); ?></div><div id="resultMetrics" class="result-metrics" aria-live="polite"><div class="result-metric success"><span class="result-metric-label"><?php echo h($t['result_success']); ?></span><strong id="resultSuccessValue" class="result-metric-value">0</strong></div><div class="result-metric skipped"><span class="result-metric-label"><?php echo h($t['result_skipped']); ?></span><strong id="resultSkippedValue" class="result-metric-value">0</strong></div><div class="result-metric failed"><span class="result-metric-label"><?php echo h($t['result_failed']); ?></span><strong id="resultFailedValue" class="result-metric-value">0</strong></div></div><div id="batchSummary" class="batch-summary" aria-live="polite"></div><div id="resultExportTools" class="api-tools" style="display:none;padding:0 16px 14px"><button id="exportResultJsonButton" class="btn btn-neutral" type="button">↓ <?php echo h($t['export_json']); ?></button><button id="exportResultCsvButton" class="btn btn-neutral" type="button">↓ <?php echo h($t['export_csv']); ?></button></div></section>
    <details id="responseCard" class="response-card" aria-live="polite"><summary class="response-title"><?php echo h($t['api_details']); ?></summary><pre id="response"></pre></details>
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
  const previewButton = document.getElementById('previewButton');
  const retryFailedButton = document.getElementById('retryFailedButton');
  const formatButton = document.getElementById('formatButton');
  const editorShell = document.getElementById('editorShell');
  const editorToggleButton = document.getElementById('editorToggleButton');
  const downloadSampleButton = document.getElementById('downloadSampleButton');
  const copyMesButton = document.getElementById('copyMesButton');
  const copyCurlButton = document.getElementById('copyCurlButton');
  const exportResultJsonButton = document.getElementById('exportResultJsonButton');
  const exportResultCsvButton = document.getElementById('exportResultCsvButton');
  const resultExportTools = document.getElementById('resultExportTools');
  const previewSummary = document.getElementById('previewSummary');
  const previewJobs = document.getElementById('previewJobs');
  const previewSeqs = document.getElementById('previewSeqs');
  const previewSteps = document.getElementById('previewSteps');
  const overallResult = document.getElementById('overallResult');
  const resultMetrics = document.getElementById('resultMetrics');
  const resultSuccessValue = document.getElementById('resultSuccessValue');
  const resultSkippedValue = document.getElementById('resultSkippedValue');
  const resultFailedValue = document.getElementById('resultFailedValue');
  const batchSummary = document.getElementById('batchSummary');
  let lastFailedTargets = [];
  let busy = false;
  let lastExportResult = null;
  const MAX_REQUEST_BYTES = 67174400;
  function makeRequestKey(prefix) { return (prefix || 'IDAS') + '-' + Date.now() + '-' + Math.random().toString(36).slice(2, 10); }
  let jsonDirty = false;
  const statusElement = document.getElementById('status');
  const responseElement = document.getElementById('response');
  const responseCard = document.getElementById('responseCard');
  const targetIps = document.getElementById('targetIps');
  const singleTargetIp = document.getElementById('singleTargetIp');
  const singleTargetPanel = document.getElementById('singleTargetPanel');
  const multiTargetPanel = document.getElementById('multiTargetPanel');
  const multiStats = document.getElementById('multiStats');
  const modeSingle = document.getElementById('modeSingle');
  const modeMulti = document.getElementById('modeMulti');
  const modeHint = document.getElementById('modeHint');
  const checkTargetsButtonMulti = document.getElementById('checkTargetsButtonMulti');
  let deploymentMode = 'single';
  let renderedTargetsKey = '';
  const targetCount = document.getElementById('targetCount');
  const targetCountValue = document.getElementById('targetCountValue');
  const readyCountValue = document.getElementById('readyCountValue');
  const skippedCountValue = document.getElementById('skippedCountValue');
  const targetTableEmpty = document.getElementById('targetTableEmpty');
  const resultPlaceholder = document.getElementById('resultPlaceholder');
  const targetTableWrap = document.getElementById('targetTableWrap');
  const targetTableBody = document.getElementById('targetTableBody');
  const checkTargetsButton = document.getElementById('checkTargetsButton');
  const networkBadge = document.getElementById('networkBadge');
  const apiEndpoint = <?php echo json_encode($idasBaseUrl . '/api/replace_job_config.php', JSON_UNESCAPED_SLASHES); ?>;
  const apiVersion = <?php echo json_encode($idasApiVersion, JSON_UNESCAPED_SLASHES); ?>;

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

  function isValidIpv4(value) {
    const parts = String(value || '').split('.');
    return parts.length === 4 && parts.every(function (part) {
      return /^\d{1,3}$/.test(part) && Number(part) >= 0 && Number(part) <= 255;
    });
  }

  function parseTargets() {
    const seen = Object.create(null);
    const source = deploymentMode === 'single' ? singleTargetIp.value : targetIps.value;
    const values = deploymentMode === 'single' ? [String(source || '').trim()] : String(source || '').split(/[\s,;]+/).map(function (value) { return value.trim(); });
    return values.filter(function (value) {
      if (!value || seen[value] || !isValidIpv4(value)) return false;
      seen[value] = true;
      return true;
    });
  }

  function ipv4SortValue(ip) {
    const parts = String(ip).split('.');
    if (parts.length !== 4 || parts.some(function (p) { return !/^\d+$/.test(p) || Number(p) > 255; })) return Number.MAX_SAFE_INTEGER;
    return parts.reduce(function (n, p) { return n * 256 + Number(p); }, 0);
  }

  function normalizeTargetInput() {
    const targets = parseTargets().sort(function (a, b) {
      const av = ipv4SortValue(a), bv = ipv4SortValue(b);
      return av === bv ? a.localeCompare(b) : av - bv;
    });
    if (deploymentMode === 'single') {
      singleTargetIp.value = targets.length ? targets[0] : '';
    } else {
      targetIps.value = targets.join('\n');
    }
    updateTargetCount();
    return targets;
  }

  function setDeploymentMode(mode, preserveValue) {
    const next = mode === 'multi' ? 'multi' : 'single';
    if (next === deploymentMode && preserveValue !== false) return;
    const currentTargets = parseTargets();
    deploymentMode = next;
    modeSingle.checked = next === 'single';
    modeMulti.checked = next === 'multi';
    singleTargetPanel.hidden = next !== 'single';
    multiTargetPanel.hidden = next !== 'multi';
    multiStats.style.display = next === 'multi' ? 'grid' : 'none';
    modeHint.textContent = next === 'single' ? T.mode_single_hint : T.mode_multi_hint;
    retryFailedButton.style.display = next === 'multi' && lastFailedTargets.length ? '' : 'none';
    if (preserveValue !== false && currentTargets.length) {
      if (next === 'single') singleTargetIp.value = currentTargets[0];
      else targetIps.value = currentTargets.join('\n');
    }
    targetTableBody.textContent = '';
    targetTableWrap.style.display = 'none';
    if (targetTableEmpty) targetTableEmpty.style.display = 'block';
    updateTargetCount();
  }

  function setBusy(value) {
    busy = !!value;
    [send, previewButton, retryFailedButton, checkTargetsButton, checkTargetsButtonMulti, formatButton, downloadSampleButton, copyMesButton, copyCurlButton, exportResultJsonButton, exportResultCsvButton].forEach(function (el) { if (el) el.disabled = busy; });
    file.disabled = busy;
    targetIps.disabled = busy;
    singleTargetIp.disabled = busy;
    modeSingle.disabled = busy;
    modeMulti.disabled = busy;
  }

  function payloadCounts(payload) {
    return {
      jobs: Array.isArray(payload && payload.JOB_lst) ? payload.JOB_lst.length : 0,
      sequences: Array.isArray(payload && payload.SEQ_lst) ? payload.SEQ_lst.length : 0,
      steps: Array.isArray(payload && payload.STEP_lst) ? payload.STEP_lst.length : 0
    };
  }

  function targetsKey(targets) {
    return (Array.isArray(targets) ? targets.slice() : [])
      .filter(Boolean)
      .sort(function (a, b) { return ipv4SortValue(a) - ipv4SortValue(b); })
      .join('|');
  }

  function clearRenderedTargetChecks() {
    renderedTargetsKey = '';
    targetTableBody.textContent = '';
    targetTableWrap.style.display = 'none';
    if (targetTableEmpty) targetTableEmpty.style.display = 'block';
    if (readyCountValue) readyCountValue.textContent = '--';
    if (skippedCountValue) skippedCountValue.textContent = '--';
    lastFailedTargets = [];
    retryFailedButton.style.display = 'none';
  }

  function updateTargetCount() {
    const currentTargets = parseTargets();
    const count = currentTargets.length;
    targetCount.textContent = T.target_count + ' ' + count;
    if (targetCountValue) targetCountValue.textContent = String(count);
    if (renderedTargetsKey && renderedTargetsKey !== targetsKey(currentTargets)) {
      clearRenderedTargetChecks();
    }
    if (targetTableWrap.style.display === 'none') {
      if (readyCountValue) readyCountValue.textContent = '--';
      if (skippedCountValue) skippedCountValue.textContent = '--';
    }
  }

  function errorMessageFromCode(code, fallback) {
    const map = {
      DEFAULT_TARGET_UNAVAILABLE: T.err_default_target_unavailable,
      NOT_SAME_SUBNET: T.err_not_same_subnet,
      REMOTE_NOT_SAME_SUBNET: T.err_not_same_subnet,
      TARGET_UNREACHABLE: T.err_target_unreachable,
      NO_RESPONSE: T.err_target_unreachable,
      CONTROLLER_IN_USE: T.err_controller_in_use,
      CONTROLLER_ROOT_NOT_FOUND: T.err_root_missing,
      NOT_NTCS7: T.err_not_ntcs7,
      PROBE_INVALID_RESPONSE: T.err_invalid_response,
      WRITE_INVALID_RESPONSE: T.err_invalid_response,
      VERIFY_FAILED: T.err_verify_failed,
      WRITE_VERIFY_FAILED: T.err_verify_failed,
      REMOTE_WRITE_FAILED: T.err_write_failed,
      LOCAL_WRITE_FAILED: T.err_write_failed,
      SKIPPED_NOT_READY: T.err_not_ready,
      TARGET_NOT_READY: T.err_not_ready
    };
    return (code && map[code]) ? map[code] : (fallback || '');
  }

  function stateHtml(text, state) {
    const span = document.createElement('span');
    span.className = 'state ' + (state || '');
    span.textContent = text;
    return span;
  }

  function renderTargetChecks(checks, writeResults, elapsedMs) {
    targetTableBody.textContent = '';
    const currentTargets = parseTargets();
    const checkMap = Object.create(null);
    if (Array.isArray(checks)) {
      checks.forEach(function (item) { if (item && item.ip) checkMap[item.ip] = item; });
    }
    if (currentTargets.length) {
      checks = currentTargets.map(function (ip) {
        return checkMap[ip] || {
          ip: ip,
          same_subnet: false,
          network: null,
          reachable: null,
          controller_root_exists: null,
          controller_logged_out: null,
          ready: null,
          code: 'NO_CHECK_RESULT',
          message: T.no_check_result,
          check_missing: true
        };
      });
    }
    const writeMap = Object.create(null);
    if (Array.isArray(writeResults)) {
      writeResults.forEach(function (item) { if (item && item.ip) writeMap[item.ip] = item; });
    }
    if (!Array.isArray(checks) || checks.length === 0) {
      renderedTargetsKey = '';
      targetTableWrap.style.display = 'none';
      if (targetTableEmpty) targetTableEmpty.style.display = 'block';
      if (readyCountValue) readyCountValue.textContent = '--';
      if (skippedCountValue) skippedCountValue.textContent = '--';
      return;
    }
    renderedTargetsKey = targetsKey(checks.map(function (item) { return item && item.ip; }));
    const knownItems = checks.filter(function (item) { return !!(item && !item.check_missing); }).length;
    const readyItems = checks.filter(function (item) { return !!(item && item.ready === true); }).length;
    const skippedItems = checks.filter(function (item) { return !!(item && !item.check_missing && item.ready === false); }).length;
    if (readyCountValue) readyCountValue.textContent = knownItems ? String(readyItems) : '--';
    if (skippedCountValue) skippedCountValue.textContent = knownItems ? String(skippedItems) : '--';
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
      const reachable = item.reachable;
      connection.appendChild(stateHtml(reachable === true ? T.reachable : (reachable === false ? T.unreachable : T.unknown), reachable === true ? 'ok' : (reachable === false ? 'bad' : 'warn')));
      tr.appendChild(connection);
      const root = document.createElement('td');
      const rootExists = item.controller_root_exists;
      root.appendChild(stateHtml(rootExists === true ? T.exists : (rootExists === false ? T.missing : T.unknown), rootExists === true ? 'ok' : (rootExists === false ? 'bad' : 'warn')));
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
        const writeMessage = errorMessageFromCode(writeResult.code, writeResult.message);
        if (writeMessage && writeResult.message !== 'Write complete') result.appendChild(document.createTextNode(' · ' + writeMessage));
      } else {
        const ready = item.ready;
        const resultText = ready === true ? T.ready_label : (ready === false ? T.not_ready_label : T.no_check_result);
        result.appendChild(stateHtml(resultText, ready === true ? 'ok' : (ready === false ? 'bad' : 'warn')));
        const itemMessage = errorMessageFromCode(item.code, item.message);
        if (itemMessage && item.message !== 'Ready') result.appendChild(document.createTextNode(' · ' + itemMessage));
      }
      tr.appendChild(result);
      const elapsed = document.createElement('td');
      const operationElapsedMs = Number(elapsedMs || 0);
      elapsed.textContent = writeResult ? ((operationElapsedMs / 1000).toFixed(2) + ' s') : '--';
      tr.appendChild(elapsed);
      targetTableBody.appendChild(tr);
    });
    targetTableWrap.style.display = 'block';
    if (targetTableEmpty) targetTableEmpty.style.display = 'none';
  }

  function buildMesRequest(payload, action) {
    const request = Object.assign({api_version: apiVersion, action: action || 'write'}, payload || {});
    // A normal MES request uses the controller IP configured in iDAS.
    // Only an explicit multi-controller deployment needs targets.
    if (deploymentMode === 'multi') request.targets = normalizeTargetInput();
    return request;
  }

  function updatePreviewSummary(inserted, updated) {
    function textFor(key) {
      return T.inserted + ' ' + Number(inserted[key] || 0) + ' · ' + T.updated + ' ' + Number(updated[key] || 0);
    }
    previewJobs.textContent = textFor('jobs');
    previewSeqs.textContent = textFor('sequences');
    previewSteps.textContent = textFor('steps');
    previewSummary.style.display = 'block';
  }

  function updateOverallResult(mode, successCount, skippedCount, failedCount) {
    overallResult.className = 'overall-result';
    if (mode === 'preview') {
      overallResult.textContent = T.overall_status + ': ' + T.overall_preview;
      overallResult.classList.add('preview');
      return;
    }
    if (successCount > 0 && skippedCount === 0 && failedCount === 0) {
      overallResult.textContent = T.overall_status + ': ' + T.overall_all_success;
      overallResult.classList.add('success');
    } else if (successCount > 0) {
      overallResult.textContent = T.overall_status + ': ' + T.overall_partial;
      overallResult.classList.add('warning');
    } else {
      overallResult.textContent = T.overall_status + ': ' + T.overall_all_failed;
      overallResult.classList.add('error');
    }
  }

  function showBatchSummary(data, mode) {
    lastExportResult = data || null;
    if (resultExportTools) resultExportTools.style.display = lastExportResult ? 'flex' : 'none';
    if (!data || typeof data !== 'object') { batchSummary.style.display = 'none'; return; }
    const results = Array.isArray(data.results) ? data.results : [];
    const summary = data.summary && typeof data.summary === 'object' ? data.summary : {};
    function summaryNumber(key, legacyKey) {
      if (summary[key] !== undefined && summary[key] !== null) return Number(summary[key] || 0);
      return Number(data[legacyKey] || 0);
    }
    const successCount = summaryNumber('success', 'success_count');
    const skippedCount = summaryNumber('skipped', 'skipped_count');
    const failedCount = summaryNumber('failed', 'failed_count');
    let inserted = {jobs:0,sequences:0,steps:0};
    let updated = {jobs:0,sequences:0,steps:0};
    let verifiedCount = 0;
    results.forEach(function (r) {
      const d = r && r.data ? r.data : null;
      const c = d && d.counts ? d.counts : (d && d.preview && d.preview.counts ? d.preview.counts : null);
      if (c) {
        const ci = c.inserted || {};
        const cu = c.updated || {};
        ['jobs','sequences','steps'].forEach(function (k) { inserted[k] += Number(ci[k] || 0); updated[k] += Number(cu[k] || 0); });
      }
      if (d && d.verified) verifiedCount++;
    });
    updatePreviewSummary(inserted, updated);
    const lines = [];
    lines.push('<strong>' + (mode === 'preview' ? T.preview_done : T.write_done) + '</strong>');
    lines.push(T.inserted + ': JOB ' + inserted.jobs + ' / SEQ ' + inserted.sequences + ' / STEP ' + inserted.steps);
    lines.push(T.updated + ': JOB ' + updated.jobs + ' / SEQ ' + updated.sequences + ' / STEP ' + updated.steps);
    if (mode !== 'preview') lines.push(T.verified + ': ' + verifiedCount + ' / ' + successCount);
    updateOverallResult(mode, successCount, skippedCount, failedCount);
    lines.push(T.success + ': ' + successCount + ' · ' + T.write_skip_label + ': ' + skippedCount + ' · ' + T.write_fail_label + ': ' + failedCount);
    const elapsedMs = Number(data.elapsed_ms || 0);
    if (elapsedMs >= 0 && mode !== 'preview') lines.push(T.elapsed_time + ': ' + (elapsedMs / 1000).toFixed(2) + ' s');
    if (resultSuccessValue) resultSuccessValue.textContent = String(successCount);
    if (resultSkippedValue) resultSkippedValue.textContent = String(skippedCount);
    if (resultFailedValue) resultFailedValue.textContent = String(failedCount);
    if (resultMetrics) resultMetrics.style.display = 'grid';
    batchSummary.innerHTML = lines.join('<br>');
    batchSummary.style.display = 'block';
    if (resultPlaceholder) resultPlaceholder.style.display = 'none';
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

  function utf8ByteLength(text) {
    if (window.TextEncoder) return new TextEncoder().encode(text).length;
    return unescape(encodeURIComponent(text)).length;
  }

  async function copyTextToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(text);
      return;
    }
    const temp = document.createElement('textarea');
    temp.value = text;
    temp.style.position = 'fixed';
    temp.style.opacity = '0';
    document.body.appendChild(temp);
    temp.select();
    const copied = document.execCommand('copy');
    temp.remove();
    if (!copied) throw new Error('copy failed');
  }

  function downloadBlob(filename, content, type) {
    const blob = new Blob([content], {type: type});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 0);
  }

  function csvCell(value) {
    const text = value == null ? '' : String(value);
    return '"' + text.replace(/"/g, '""') + '"';
  }

  function exportResultCsv(data) {
    const results = Array.isArray(data && data.results) ? data.results : (data && data.data && Array.isArray(data.data.results) ? data.data.results : []);
    const lines = [['IP','Status','Code','Message','Elapsed ms']];
    results.forEach(function (item) {
      let status = 'failed';
      if (item && (item.success === true || item.written === true)) status = 'success';
      else if (item && (item.skipped === true || item.ready === false)) status = 'skipped';
      lines.push([item.ip || '', status, item.code || item.error_code || '', item.message || '', Number((item && item.elapsed_ms) || (data && data.elapsed_ms) || 0)]);
    });
    const csv = '\ufeff' + lines.map(function (row) { return row.map(csvCell).join(','); }).join('\r\n');
    downloadBlob('job_config_result_' + Date.now() + '.csv', csv, 'text/csv;charset=utf-8');
  }

  async function callRemoteApi(payload) {
    const action = payload && payload.action ? payload.action : 'check';
    const timeoutMs = action === 'write' ? 30000 : (action === 'preview' ? 15000 : 8000);
    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    const timer = controller ? setTimeout(function () { controller.abort(); }, timeoutMs) : null;
    let httpResponse;
    try {
      const requestBody = JSON.stringify(payload);
      if (utf8ByteLength(requestBody) > MAX_REQUEST_BYTES) throw new Error(T.request_too_large);
      httpResponse = await fetch(apiEndpoint + '?request=' + Date.now(), {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: requestBody,
        cache: 'no-store',
        signal: controller ? controller.signal : undefined
      });
    } catch (error) {
      if (error && error.name === 'AbortError') throw new Error(T.connection_failed);
      throw error;
    } finally {
      if (timer) clearTimeout(timer);
    }
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

  // Accept both the current flat response and the legacy {data:{...}} shape.
  function operationData(responseData) {
    if (!responseData || typeof responseData !== 'object') return {};
    const nested = responseData.data && typeof responseData.data === 'object' ? responseData.data : null;
    const hasFlatOperation = Array.isArray(responseData.checks) || Array.isArray(responseData.results) || (responseData.summary && typeof responseData.summary === 'object');
    const normalized = Object.assign({}, hasFlatOperation || !nested ? responseData : nested);
    ['success', 'action', 'api_version', 'elapsed_ms', 'error'].forEach(function (key) {
      if (normalized[key] === undefined && responseData[key] !== undefined) normalized[key] = responseData[key];
    });
    return normalized;
  }

  async function checkTargets(showNotice, manageBusy) {
    if (manageBusy === undefined) manageBusy = true;
    if (manageBusy && busy) return null;
    const targets = parseTargets();
    if (!targets.length) {
      showUnifiedNotice(T.error, T.target_required, 'error');
      return null;
    }
    if (manageBusy) setBusy(true);
    showStatus(T.checking, '');
    try {
      const result = await callRemoteApi({api_version: apiVersion, action: 'check', targets: targets});
      const checkData = operationData(result.data);
      const checks = Array.isArray(checkData.checks) ? checkData.checks : [];
      renderTargetChecks(checks);
      // Legacy responses use an outer success=true for a valid HTTP/API call,
      // while data.all_ready carries the actual controller readiness result.
      // Current flat responses expose that readiness directly as success.
      const allReady = typeof checkData.all_ready === 'boolean'
        ? checkData.all_ready
        : checkData.success === true;
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
      if (manageBusy) setBusy(false);
    }
  }

  async function loadNetworkInfo() {
    try {
      const httpResponse = await fetch(apiEndpoint + '?action=network&request=' + Date.now(), {cache: 'no-store'});
      const data = await httpResponse.json();
      if (!httpResponse.ok || !data.success || !data.data) throw new Error(T.network_unavailable);
      const networks = Array.isArray(data.data.networks) ? data.data.networks : [];
      networkBadge.textContent = T.local_network + ': ' + (networks.length ? networks.map(function (item) { return item.cidr; }).join(', ') : '--');
      if (data.data.preferred_ip) {
        if (!singleTargetIp.value.trim()) singleTargetIp.value = data.data.preferred_ip;
        if (!targetIps.value.trim()) targetIps.value = data.data.preferred_ip;
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
      document.getElementById('jobCount').textContent = String(Array.isArray(data.JOB_lst) ? data.JOB_lst.length : 0);
      document.getElementById('seqCount').textContent = String(Array.isArray(data.SEQ_lst) ? data.SEQ_lst.length : 0);
      document.getElementById('stepCount').textContent = String(Array.isArray(data.STEP_lst) ? data.STEP_lst.length : 0);
    } catch (e) {
      document.getElementById('jobCount').textContent = '-';
      document.getElementById('seqCount').textContent = '-';
      document.getElementById('stepCount').textContent = '-';
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

  function confirmWrite(precheck, targets, payload) {
    const counts = payloadCounts(payload);
    const targetList = Array.isArray(targets) ? targets : parseTargets();
    const readyCount = precheck ? Number(precheck.readyCount || 0) : targetList.length;
    const skippedCount = Math.max(0, targetList.length - readyCount);
    let message = T.confirm_message;
    message += '\n\n' + T.target_count + ': ' + targetList.length;
    message += '\n' + T.confirm_ready + ': ' + readyCount;
    message += '\n' + T.confirm_skipped + ': ' + skippedCount;
    message += '\nJOB: ' + counts.jobs + ' / SEQ: ' + counts.sequences + ' / STEP: ' + counts.steps;
    if (precheck && !precheck.allReady) message += '\n\n' + T.partial_ready;
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

  function updateRetryTargets(batchData) {
    lastFailedTargets = Array.isArray(batchData && batchData.results) ? batchData.results
      .filter(function (r) { return r && !r.success; })
      .map(function (r) { return r.ip; })
      .filter(Boolean) : [];
    retryFailedButton.style.display = deploymentMode === 'multi' && lastFailedTargets.length ? '' : 'none';
  }

  function showApiDetails(responseData) {
    responseElement.textContent = JSON.stringify(sanitizeApiResponse(responseData), null, 2);
    responseCard.style.display = 'block';
    responseCard.open = false;
  }

  async function executeWrite(targets, payload, isRetry) {
    if (busy) return;
    setBusy(true);
    responseCard.style.display = 'none';
    showStatus(isRetry ? T.retrying_failed : T.write_precheck, '');
    try {
      let effectivePrecheck = null;
      if (isRetry) {
        const checked = await callRemoteApi({api_version:apiVersion, action:'check', targets:targets});
        const checkedData = operationData(checked.data);
        const checks = Array.isArray(checkedData.checks) ? checkedData.checks : [];
        const readyCount = checks.filter(function (item) { return !!(item && item.ready); }).length;
        effectivePrecheck = {allReady: readyCount === targets.length, anyReady: readyCount > 0, readyCount: readyCount, checks: checks};
        renderTargetChecks(checks);
      } else {
        effectivePrecheck = await checkTargets(false, false);
      }
      if (!effectivePrecheck || !effectivePrecheck.anyReady) {
        showUnifiedNotice(T.warning_notice, T.no_ready, 'warning');
        showStatus(T.no_ready, 'error');
        return;
      }
      if (!(await confirmWrite(effectivePrecheck, targets, payload))) return;

      const result = await callRemoteApi(Object.assign({api_version:apiVersion, action:'write', request_key:makeRequestKey(isRetry ? 'IDAS-RETRY' : 'IDAS-WRITE'), targets:targets}, payload));
      const responseData = result.data;
      showApiDetails(responseData);
      const batchData = operationData(responseData);
      renderTargetChecks(batchData.checks || [], batchData.results || [], batchData.elapsed_ms);
      showBatchSummary(batchData, 'write');
      updateRetryTargets(batchData);

      // Refresh controller availability after deployment. This does not rewrite anything.
      try {
        const refreshed = await callRemoteApi({api_version:apiVersion, action:'check', targets:targets});
        const refreshedData = operationData(refreshed.data);
        const refreshedChecks = Array.isArray(refreshedData.checks) ? refreshedData.checks : [];
        if (refreshedChecks.length) renderTargetChecks(refreshedChecks, batchData.results || [], batchData.elapsed_ms);
      } catch (recheckError) {
        // Keep the deployment result intact if the post-write status refresh fails.
      }

      if (responseData && responseData.success) {
        jsonDirty = false;
        showUnifiedNotice(T.success, T.write_all_success, 'success');
        showStatus(T.write_all_success, 'success');
      } else if (Number(batchData && batchData.summary && batchData.summary.success || 0) > 0) {
        showUnifiedNotice(T.warning_notice, T.write_partial, 'warning');
        showStatus(T.write_partial, 'warning');
      } else if (responseData && responseData.error && responseData.error.code === 'NO_READY_TARGETS') {
        showUnifiedNotice(T.warning_notice, T.no_ready, 'warning');
        showStatus(T.no_ready, 'error');
      } else {
        const err = responseData && responseData.error ? responseData.error : {};
        const message = errorMessageFromCode(err.code, err.message) || T.write_none;
        showUnifiedNotice(T.error, message, 'error');
        showStatus(T.write_none, 'error');
      }
    } catch (error) {
      responseElement.textContent = error.message || T.connection_failed;
      responseCard.style.display = 'block';
      responseCard.open = false;
      showUnifiedNotice(T.error, error.message || T.connection_failed, 'error');
      showStatus(T.connection_status_failed, 'error');
    } finally {
      setBusy(false);
    }
  }

  downloadSampleButton.addEventListener('click', function () {
    if (busy) return;
    try {
      const payload = refreshJsonTimeFromComputer();
      const request = buildMesRequest(payload, 'write');
      const blob = new Blob([JSON.stringify(request, null, 2)], {type: 'application/json;charset=utf-8'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'job_config_api_sample.json';
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(function () { URL.revokeObjectURL(url); }, 0);
      showUnifiedNotice(T.success, T.download_ready, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.json_invalid + error.message, 'error');
    }
  });

  copyMesButton.addEventListener('click', async function () {
    if (busy) return;
    try {
      const payload = refreshJsonTimeFromComputer();
      const request = buildMesRequest(payload, 'write');
      request.request_key = makeRequestKey('MES');
      const text = JSON.stringify(request, null, 2);
      await copyTextToClipboard(text);
      showUnifiedNotice(T.success, T.copy_success, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.copy_failed, 'error');
    }
  });

  copyCurlButton.addEventListener('click', async function () {
    if (busy) return;
    try {
      const endpoint = new URL(apiEndpoint, window.location.href).href;
      const command = 'curl.exe -X POST "' + endpoint + '" -H "Content-Type: application/json" --data-binary "@job_config.json"';
      await copyTextToClipboard(command);
      showUnifiedNotice(T.success, T.copy_curl_success, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.copy_curl_failed, 'error');
    }
  });

  exportResultJsonButton.addEventListener('click', function () {
    if (!lastExportResult) { showUnifiedNotice(T.warning_notice, T.no_result_export, 'warning'); return; }
    downloadBlob('job_config_result_' + Date.now() + '.json', JSON.stringify(lastExportResult, null, 2), 'application/json;charset=utf-8');
    showUnifiedNotice(T.success, T.export_ready, 'success');
  });

  exportResultCsvButton.addEventListener('click', function () {
    if (!lastExportResult) { showUnifiedNotice(T.warning_notice, T.no_result_export, 'warning'); return; }
    exportResultCsv(lastExportResult);
    showUnifiedNotice(T.success, T.export_ready, 'success');
  });

  editorToggleButton.addEventListener('click', function () {
    const collapsed = editorShell.classList.toggle('collapsed');
    editorToggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    editorToggleButton.textContent = (collapsed ? '▾ ' + T.expand_json : '▴ ' + T.collapse_json);
  });

  formatButton.addEventListener('click', function () {
    if (busy) return;
    try {
      body.value = JSON.stringify(JSON.parse(body.value), null, 2);
      jsonDirty = true;
      updateCounts();
      showStatus(T.json_valid, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.json_invalid + error.message, 'error');
      showStatus(T.json_invalid + error.message, 'error');
    }
  });

  file.addEventListener('change', async function () {
    if (busy || !file.files[0]) return;
    fileName.textContent = file.files[0].name;
    try {
      body.value = await file.files[0].text();
      body.value = JSON.stringify(JSON.parse(body.value), null, 2);
      jsonDirty = true;
      updateCounts();
      showStatus(T.file_loaded + file.files[0].name, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.file_invalid + error.message, 'error');
      showStatus(T.file_invalid + error.message, 'error');
    }
  });

  modeSingle.addEventListener('change', function () { if (modeSingle.checked) setDeploymentMode('single', true); });
  modeMulti.addEventListener('change', function () { if (modeMulti.checked) setDeploymentMode('multi', true); });
  singleTargetIp.addEventListener('input', updateTargetCount);
  singleTargetIp.addEventListener('blur', normalizeTargetInput);
  singleTargetIp.addEventListener('paste', function (event) {
    const text = event.clipboardData ? event.clipboardData.getData('text') : '';
    const pastedTargets = String(text || '').split(/[\s,;]+/).filter(function (v) { return isValidIpv4(v.trim()); });
    if (pastedTargets.length > 1) {
      event.preventDefault();
      targetIps.value = pastedTargets.join('\n');
      setDeploymentMode('multi', false);
      normalizeTargetInput();
    }
  });
  targetIps.addEventListener('input', updateTargetCount);
  targetIps.addEventListener('blur', normalizeTargetInput);
  targetIps.addEventListener('paste', function () { setTimeout(normalizeTargetInput, 0); });
  checkTargetsButton.addEventListener('click', function () { checkTargets(true, true); });
  checkTargetsButtonMulti.addEventListener('click', function () { checkTargets(true, true); });
  body.addEventListener('input', function () { jsonDirty = true; updateCounts(); });

  previewButton.addEventListener('click', async function () {
    if (busy) return;
    let payload;
    try { payload = refreshJsonTimeFromComputer(); }
    catch (error) { showUnifiedNotice(T.error, T.json_invalid + error.message, 'error'); return; }
    const targets = normalizeTargetInput();
    if (!targets.length) { showUnifiedNotice(T.error, T.target_required, 'error'); return; }
    setBusy(true);
    showStatus(T.previewing, '');
    try {
      const result = await callRemoteApi(Object.assign({api_version:apiVersion, action:'preview', targets:targets}, payload));
      const data = operationData(result.data);
      renderTargetChecks(data.checks || [], data.results || [], data.elapsed_ms);
      showBatchSummary(data, 'preview');
      showApiDetails(result.data);
      showStatus(T.preview_done, Number(data && data.summary && data.summary.success || 0) > 0 ? 'success' : 'warning');
    } catch (error) {
      showUnifiedNotice(T.error, error.message || T.connection_failed, 'error');
      showStatus(T.connection_status_failed, 'error');
    } finally {
      setBusy(false);
    }
  });

  retryFailedButton.addEventListener('click', async function () {
    if (busy) return;
    if (!lastFailedTargets.length) {
      showUnifiedNotice(T.warning_notice, T.no_failed_targets, 'warning');
      return;
    }
    let payload;
    try { payload = refreshJsonTimeFromComputer(); }
    catch (error) { showUnifiedNotice(T.error, T.json_invalid + error.message, 'error'); return; }
    await executeWrite(lastFailedTargets.slice(), payload, true);
  });

  send.addEventListener('click', async function () {
    if (busy) return;
    let payload;
    try {
      payload = refreshJsonTimeFromComputer();
    } catch (error) {
      showUnifiedNotice(T.error, T.json_invalid + error.message, 'error');
      showStatus(T.json_invalid + error.message, 'error');
      return;
    }
    const targets = normalizeTargetInput();
    if (!targets.length) {
      showUnifiedNotice(T.error, T.target_required, 'error');
      showStatus(T.target_required, 'error');
      return;
    }
    await executeWrite(targets, payload, false);
  });

  window.addEventListener('beforeunload', function (event) {
    if (!busy && !jsonDirty) return;
    event.preventDefault();
    event.returnValue = T.leave_warning;
    return T.leave_warning;
  });

  setDeploymentMode('single', false);
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
