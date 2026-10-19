<?php
$lang = strtolower((string)($_COOKIE['language'] ?? ($_SESSION['language'] ?? 'en-us')));
$lang = str_replace('_', '-', $lang);
if ($lang === 'en') {
    $lang = 'en-us';
}
if (!in_array($lang, ['en-us', 'zh-tw', 'zh-cn'], true)) {
    $lang = 'en-us';
}

$PD_I18N = [
    'en-us' => [
        'title' => 'Platform / Protocol / Port / DB Debug',
        'refresh' => 'Refresh',
        'back' => 'Back to Settings',
        'system_status' => 'System Status',
        'platform' => 'Platform',
        'protocol' => 'Protocol',
        'server_port' => 'Server Port',
        'snapshot' => 'Read-only snapshot',
        'idas_platform' => 'IDAS_PLATFORM',
        'resolved_flag' => 'Resolved Flag',
        'flag_file' => 'Flag File',
        'raw_flag_value' => 'Raw Flag Value',
        'flag_file_status' => 'Flag File Status',
        'default_warning' => 'DEFAULT / WARNING',
        'runtime_controller' => 'Runtime Controller',
        'device_id' => 'Device ID',
        'modbus_type' => 'Modbus Type',
        'expected_port' => 'Expected Port',
        'wifi_raw' => 'WiFi Raw',
        'controller_db' => 'Controller DB',
        'idas_db' => 'iDAS DB',
        'path' => 'Path',
        'size' => 'Size',
        'readable' => 'Readable',
        'writable' => 'Writable',
        'sqlite_open' => 'SQLite Open',
        'table' => 'Table',
        'row' => 'Row',
        'db_health' => 'DB Health',
        'error' => 'Error',
        'controller_db_values' => 'Controller DB Values',
        'idas_db_values' => 'iDAS DB Values',
        'network_mode' => 'Network Mode',
        'static_ip' => 'Static IP',
        'mask' => 'Mask',
        'gateway' => 'Gateway',
        'consistency' => 'Consistency',
        'device_id_sync' => 'Device ID Sync',
        'modbus_type_sync' => 'Modbus Type Sync',
        'server_port_sync' => 'Server Port Sync',
        'protocol_port' => 'Protocol ↔ Port',
        'expected' => 'expected',
        'issues' => 'Issues',
        'warnings' => 'Warnings',
        'no_issues' => 'No issues detected.',
        'readonly_note' => 'This page is read-only. Refresh only re-reads the flag file and SQLite databases; it does not write settings or trigger DB synchronization.',
        'php_debug' => 'PHP Runtime Debug',
        'php_version' => 'PHP Version',
        'php_sapi' => 'PHP SAPI',
        'os_arch' => 'OS / Architecture',
        'server_software' => 'Web Server',
        'document_root' => 'Document Root',
        'script_filename' => 'Script Filename',
        'loaded_ini' => 'Loaded php.ini',
        'scanned_ini' => 'Additional INI Files',
        'display_errors' => 'display_errors',
        'log_errors' => 'log_errors',
        'error_log' => 'error_log',
        'error_reporting' => 'error_reporting',
        'memory_limit' => 'memory_limit',
        'max_execution_time' => 'max_execution_time',
        'max_input_time' => 'max_input_time',
        'upload_max_filesize' => 'upload_max_filesize',
        'post_max_size' => 'post_max_size',
        'max_file_uploads' => 'max_file_uploads',
        'default_socket_timeout' => 'default_socket_timeout',
        'timezone' => 'Timezone',
        'php_extensions' => 'PHP Extensions',
        'extension' => 'Extension',
        'loaded' => 'Loaded',
        'php_error_debug' => 'PHP Error Debug',
        'error_log_path' => 'Error Log Path',
        'error_log_exists' => 'Error Log Exists',
        'error_log_readable' => 'Error Log Readable',
        'error_log_size' => 'Error Log Size',
        'last_modified' => 'Last Modified',
        'recent_php_errors' => 'Recent PHP Errors',
        'no_recent_php_errors' => 'No recent PHP errors detected.',
        'function_check' => 'Function / Component Check',
        'check_name' => 'Check',
        'status' => 'Status',
        'detail' => 'Detail',
        'syntax_check' => 'PHP Syntax Check',
        'php_cli' => 'PHP CLI',
        'checked_files' => 'Checked Files',
        'syntax_ok' => 'Syntax OK',
        'syntax_ng' => 'Syntax NG',
        'file' => 'File',
        'line' => 'Line',
        'syntax_message' => 'Message',
        'no_syntax_errors' => 'No PHP syntax errors detected.',
        'standalone_syntax' => 'Standalone Syntax Check',
        'download_report' => 'Download Debug Report',
        'controller_test' => 'Test Controller',
        'controller_diag' => 'Controller Communication Diagnostics',
        'not_tested' => 'Not tested yet. Press Test Controller.',
        'testing' => 'Testing...',
        'tcp_connect' => 'TCP Connect',
        'response_time' => 'Response Time',
        'register_read' => 'Register Read',
        'registers' => 'Registers',
        'file_integrity' => 'System File Integrity',
        'owner_group' => 'Owner / Group',
        'permissions' => 'Permissions',
        'sha256' => 'SHA-256',
        'db_schema' => 'DB Schema / Integrity',
        'required_columns' => 'Required Columns',
        'missing_columns' => 'Missing Columns',
        'integrity' => 'Integrity',
        'disk_ramdisk' => 'Disk / RAMDISK',
        'total' => 'Total',
        'free' => 'Free',
        'free_percent' => 'Free %',
        'mounted' => 'Mounted',
        'service_status' => 'Service Status',
        'service' => 'Service',
        'version_image' => 'Version / Image Information',
        'idas_version' => 'iDAS Version',
        'idas_model' => 'iDAS Model',
        'device_sn' => 'Device SN',
        'device_name' => 'Device Name',
        'mcb_fw' => 'MCB FW Version',
        'device_db_version' => 'Device DB Version',
        'device_image_version' => 'Device Image Version',
        'device_version' => 'Device Version',
        'upgrade_files' => 'Upgrade / Image Files',
        'modified_at' => 'Modified At',
        'recent_logs' => 'Recent System Logs',
        'log_name' => 'Log',
        'log_lines' => 'Recent Lines',
        'no_log_lines' => 'No readable log lines.',
        'exists' => 'Exists',
        'capability_summary' => 'Debug Capability Summary',
        'db_quick_check' => 'DB PRAGMA quick_check',
        'controller_test_summary' => 'Controller Communication Test',
        'download_report_summary' => 'Download Debug Report',
        'readonly_mode' => 'Read-only Mode',
        'emergency_syntax_summary' => 'Emergency Syntax Check',
        'language_support' => 'Language Support',
        'php_syntax_summary' => 'PHP Syntax',
        'js_syntax_summary' => 'JS Syntax',
        'build_zip' => 'Build / ZIP Integrity',
        'available' => 'AVAILABLE',
        'not_tested_status' => 'NOT TESTED',
        'languages_status' => 'en-us / zh-tw / zh-cn',
        'yes' => 'YES',
        'no' => 'NO',
        'ok' => 'OK',
        'ng' => 'NG',
        'na' => 'N/A',
    ],
    'zh-tw' => [
        'title' => '平台 / 通訊協議 / 通訊埠 / DB 除錯',
        'refresh' => '重新整理',
        'back' => '返回設定',
        'system_status' => '系統狀態',
        'platform' => '平台',
        'protocol' => '通訊協議',
        'server_port' => '伺服器通訊埠',
        'snapshot' => '唯讀快照時間',
        'idas_platform' => 'IDAS_PLATFORM',
        'resolved_flag' => '判斷結果',
        'flag_file' => '判斷檔案',
        'raw_flag_value' => '原始旗標值',
        'flag_file_status' => '旗標檔案狀態',
        'default_warning' => '預設值 / 警告',
        'runtime_controller' => '控制器目前狀態',
        'device_id' => '裝置 ID',
        'modbus_type' => '通訊類型值',
        'expected_port' => '預期通訊埠',
        'wifi_raw' => 'WiFi 原始值',
        'controller_db' => '控制器 DB',
        'idas_db' => 'iDAS DB',
        'path' => '路徑',
        'size' => '大小',
        'readable' => '可讀取',
        'writable' => '可寫入',
        'sqlite_open' => 'SQLite 開啟',
        'table' => '資料表',
        'row' => '資料列',
        'db_health' => 'DB 狀態',
        'error' => '錯誤',
        'controller_db_values' => '控制器 DB 資料',
        'idas_db_values' => 'iDAS DB 資料',
        'network_mode' => '網路模式',
        'static_ip' => '固定 IP',
        'mask' => '子網路遮罩',
        'gateway' => '閘道',
        'consistency' => '一致性檢查',
        'device_id_sync' => '裝置 ID 同步',
        'modbus_type_sync' => '通訊類型同步',
        'server_port_sync' => '伺服器通訊埠同步',
        'protocol_port' => '通訊協議 ↔ 通訊埠',
        'expected' => '預期',
        'issues' => '異常項目',
        'warnings' => '警告',
        'no_issues' => '未偵測到異常。',
        'readonly_note' => '此頁面為唯讀。重新整理只會重新讀取旗標檔案與 SQLite DB，不會寫入設定，也不會觸發 DB 同步。',
        'php_debug' => 'PHP 執行環境除錯',
        'php_version' => 'PHP 版本',
        'php_sapi' => 'PHP SAPI',
        'os_arch' => '作業系統 / 架構',
        'server_software' => 'Web Server',
        'document_root' => '網站根目錄',
        'script_filename' => '執行檔案',
        'loaded_ini' => '載入的 php.ini',
        'scanned_ini' => '額外 INI 設定檔',
        'display_errors' => 'display_errors',
        'log_errors' => 'log_errors',
        'error_log' => 'error_log',
        'error_reporting' => 'error_reporting',
        'memory_limit' => 'memory_limit',
        'max_execution_time' => 'max_execution_time',
        'max_input_time' => 'max_input_time',
        'upload_max_filesize' => 'upload_max_filesize',
        'post_max_size' => 'post_max_size',
        'max_file_uploads' => 'max_file_uploads',
        'default_socket_timeout' => 'default_socket_timeout',
        'timezone' => '時區',
        'php_extensions' => 'PHP 擴充模組',
        'extension' => '擴充模組',
        'loaded' => '載入狀態',
        'php_error_debug' => 'PHP 錯誤除錯',
        'error_log_path' => '錯誤紀錄路徑',
        'error_log_exists' => '錯誤紀錄存在',
        'error_log_readable' => '錯誤紀錄可讀取',
        'error_log_size' => '錯誤紀錄大小',
        'last_modified' => '最後更新時間',
        'recent_php_errors' => '最近 PHP 錯誤',
        'no_recent_php_errors' => '未偵測到近期 PHP 錯誤。',
        'function_check' => 'Function / 元件檢查',
        'check_name' => '檢查項目',
        'status' => '狀態',
        'detail' => '詳細資訊',
        'syntax_check' => 'PHP 語法檢查',
        'php_cli' => 'PHP CLI',
        'checked_files' => '檢查檔案數',
        'syntax_ok' => '語法正常',
        'syntax_ng' => '語法異常',
        'file' => '檔案',
        'line' => '行號',
        'syntax_message' => '錯誤訊息',
        'no_syntax_errors' => '未偵測到 PHP 語法錯誤。',
        'standalone_syntax' => '獨立語法檢查',
        'download_report' => '下載 Debug 報告',
        'controller_test' => '測試控制器',
        'controller_diag' => '控制器通訊診斷',
        'not_tested' => '尚未測試，請按「測試控制器」。',
        'testing' => '測試中...',
        'tcp_connect' => 'TCP 連線',
        'response_time' => '回應時間',
        'register_read' => '暫存器讀取',
        'registers' => '暫存器',
        'file_integrity' => '系統檔案完整性',
        'owner_group' => '擁有者 / 群組',
        'permissions' => '權限',
        'sha256' => 'SHA-256',
        'db_schema' => 'DB Schema / 完整性',
        'required_columns' => '必要欄位',
        'missing_columns' => '缺少欄位',
        'integrity' => '完整性',
        'disk_ramdisk' => '磁碟 / RAMDISK',
        'total' => '總容量',
        'free' => '可用容量',
        'free_percent' => '可用 %',
        'mounted' => '掛載狀態',
        'service_status' => '服務狀態',
        'service' => '服務',
        'version_image' => '版本 / Image 資訊',
        'idas_version' => 'iDAS 版本',
        'idas_model' => 'iDAS 型號',
        'device_sn' => '裝置序號',
        'device_name' => '裝置名稱',
        'mcb_fw' => 'MCB 韌體版本',
        'device_db_version' => '裝置 DB 版本',
        'device_image_version' => '裝置 Image 版本',
        'device_version' => '裝置版本',
        'upgrade_files' => 'Upgrade / Image 檔案',
        'modified_at' => '最後更新時間',
        'recent_logs' => '最近系統紀錄',
        'log_name' => '紀錄',
        'log_lines' => '最近內容',
        'no_log_lines' => '沒有可讀取的紀錄內容。',
        'exists' => '存在',
        'capability_summary' => 'Debug 功能總覽',
        'db_quick_check' => 'DB PRAGMA quick_check',
        'controller_test_summary' => '控制器通訊測試',
        'download_report_summary' => '下載 Debug 報告',
        'readonly_mode' => '唯讀模式',
        'emergency_syntax_summary' => '緊急語法檢查',
        'language_support' => '語系支援',
        'php_syntax_summary' => 'PHP 語法',
        'js_syntax_summary' => 'JS 語法',
        'build_zip' => 'Build / ZIP 完整性',
        'available' => '可使用',
        'not_tested_status' => '尚未測試',
        'languages_status' => 'en-us / zh-tw / zh-cn',
        'yes' => '是',
        'no' => '否',
        'ok' => '正常',
        'ng' => '異常',
        'na' => '無資料',
    ],
    'zh-cn' => [
        'title' => '平台 / 通讯协议 / 通讯端口 / DB 调试',
        'refresh' => '刷新',
        'back' => '返回设置',
        'system_status' => '系统状态',
        'platform' => '平台',
        'protocol' => '通讯协议',
        'server_port' => '服务器通讯端口',
        'snapshot' => '只读快照时间',
        'idas_platform' => 'IDAS_PLATFORM',
        'resolved_flag' => '判断结果',
        'flag_file' => '判断文件',
        'raw_flag_value' => '原始标志值',
        'flag_file_status' => '标志文件状态',
        'default_warning' => '默认值 / 警告',
        'runtime_controller' => '控制器当前状态',
        'device_id' => '设备 ID',
        'modbus_type' => '通讯类型值',
        'expected_port' => '预期通讯端口',
        'wifi_raw' => 'WiFi 原始值',
        'controller_db' => '控制器 DB',
        'idas_db' => 'iDAS DB',
        'path' => '路径',
        'size' => '大小',
        'readable' => '可读取',
        'writable' => '可写入',
        'sqlite_open' => 'SQLite 打开',
        'table' => '数据表',
        'row' => '数据行',
        'db_health' => 'DB 状态',
        'error' => '错误',
        'controller_db_values' => '控制器 DB 数据',
        'idas_db_values' => 'iDAS DB 数据',
        'network_mode' => '网络模式',
        'static_ip' => '固定 IP',
        'mask' => '子网掩码',
        'gateway' => '网关',
        'consistency' => '一致性检查',
        'device_id_sync' => '设备 ID 同步',
        'modbus_type_sync' => '通讯类型同步',
        'server_port_sync' => '服务器通讯端口同步',
        'protocol_port' => '通讯协议 ↔ 通讯端口',
        'expected' => '预期',
        'issues' => '异常项目',
        'warnings' => '警告',
        'no_issues' => '未检测到异常。',
        'readonly_note' => '此页面为只读。刷新只会重新读取标志文件与 SQLite DB，不会写入设置，也不会触发 DB 同步。',
        'php_debug' => 'PHP 运行环境调试',
        'php_version' => 'PHP 版本',
        'php_sapi' => 'PHP SAPI',
        'os_arch' => '操作系统 / 架构',
        'server_software' => 'Web Server',
        'document_root' => '网站根目录',
        'script_filename' => '执行文件',
        'loaded_ini' => '加载的 php.ini',
        'scanned_ini' => '额外 INI 配置文件',
        'display_errors' => 'display_errors',
        'log_errors' => 'log_errors',
        'error_log' => 'error_log',
        'error_reporting' => 'error_reporting',
        'memory_limit' => 'memory_limit',
        'max_execution_time' => 'max_execution_time',
        'max_input_time' => 'max_input_time',
        'upload_max_filesize' => 'upload_max_filesize',
        'post_max_size' => 'post_max_size',
        'max_file_uploads' => 'max_file_uploads',
        'default_socket_timeout' => 'default_socket_timeout',
        'timezone' => '时区',
        'php_extensions' => 'PHP 扩展模块',
        'extension' => '扩展模块',
        'loaded' => '加载状态',
        'php_error_debug' => 'PHP 错误调试',
        'error_log_path' => '错误日志路径',
        'error_log_exists' => '错误日志存在',
        'error_log_readable' => '错误日志可读取',
        'error_log_size' => '错误日志大小',
        'last_modified' => '最后更新时间',
        'recent_php_errors' => '最近 PHP 错误',
        'no_recent_php_errors' => '未检测到近期 PHP 错误。',
        'function_check' => 'Function / 组件检查',
        'check_name' => '检查项目',
        'status' => '状态',
        'detail' => '详细信息',
        'syntax_check' => 'PHP 语法检查',
        'php_cli' => 'PHP CLI',
        'checked_files' => '检查文件数',
        'syntax_ok' => '语法正常',
        'syntax_ng' => '语法异常',
        'file' => '文件',
        'line' => '行号',
        'syntax_message' => '错误信息',
        'no_syntax_errors' => '未检测到 PHP 语法错误。',
        'standalone_syntax' => '独立语法检查',
        'download_report' => '下载 Debug 报告',
        'controller_test' => '测试控制器',
        'controller_diag' => '控制器通讯诊断',
        'not_tested' => '尚未测试，请按“测试控制器”。',
        'testing' => '测试中...',
        'tcp_connect' => 'TCP 连接',
        'response_time' => '响应时间',
        'register_read' => '寄存器读取',
        'registers' => '寄存器',
        'file_integrity' => '系统文件完整性',
        'owner_group' => '拥有者 / 群组',
        'permissions' => '权限',
        'sha256' => 'SHA-256',
        'db_schema' => 'DB Schema / 完整性',
        'required_columns' => '必要字段',
        'missing_columns' => '缺少字段',
        'integrity' => '完整性',
        'disk_ramdisk' => '磁盘 / RAMDISK',
        'total' => '总容量',
        'free' => '可用容量',
        'free_percent' => '可用 %',
        'mounted' => '挂载状态',
        'service_status' => '服务状态',
        'service' => '服务',
        'version_image' => '版本 / Image 信息',
        'idas_version' => 'iDAS 版本',
        'idas_model' => 'iDAS 型号',
        'device_sn' => '设备序列号',
        'device_name' => '设备名称',
        'mcb_fw' => 'MCB 固件版本',
        'device_db_version' => '设备 DB 版本',
        'device_image_version' => '设备 Image 版本',
        'device_version' => '设备版本',
        'upgrade_files' => 'Upgrade / Image 文件',
        'modified_at' => '最后更新时间',
        'recent_logs' => '最近系统日志',
        'log_name' => '日志',
        'log_lines' => '最近内容',
        'no_log_lines' => '没有可读取的日志内容。',
        'exists' => '存在',
        'capability_summary' => 'Debug 功能总览',
        'db_quick_check' => 'DB PRAGMA quick_check',
        'controller_test_summary' => '控制器通讯测试',
        'download_report_summary' => '下载 Debug 报告',
        'readonly_mode' => '只读模式',
        'emergency_syntax_summary' => '紧急语法检查',
        'language_support' => '语言支持',
        'php_syntax_summary' => 'PHP 语法',
        'js_syntax_summary' => 'JS 语法',
        'build_zip' => 'Build / ZIP 完整性',
        'available' => '可使用',
        'not_tested_status' => '尚未测试',
        'languages_status' => 'en-us / zh-tw / zh-cn',
        'yes' => '是',
        'no' => '否',
        'ok' => '正常',
        'ng' => '异常',
        'na' => '无数据',
    ],
];

$pdText = $PD_I18N[$lang];

function pd_t(string $key): string {
    global $pdText;
    return $pdText[$key] ?? $key;
}

$debug = isset($data['debug']) && is_array($data['debug']) ? $data['debug'] : [];
$platform = (array)($debug['platform'] ?? []);
$controller = (array)($debug['controller'] ?? []);
$idas = (array)($debug['idas'] ?? []);
$db = (array)($debug['database'] ?? []);
$controllerDb = (array)($db['controller'] ?? []);
$idasDb = (array)($db['idas'] ?? []);
$php = (array)($debug['php'] ?? []);
$phpErrors = (array)($debug['php_errors'] ?? []);
$functionChecks = (array)($debug['function_checks'] ?? []);
$syntaxChecks = (array)($debug['syntax_checks'] ?? []);
$fileIntegrity = (array)($debug['file_integrity'] ?? []);
$dbDiagnostics = (array)($debug['db_diagnostics'] ?? []);
$disks = (array)($debug['disks'] ?? []);
$services = (array)($debug['services'] ?? []);
$versions = (array)($debug['versions'] ?? []);
$logs = (array)($debug['logs'] ?? []);
$capabilitySummary = (array)($debug['capability_summary'] ?? []);
$buildInfo = (array)($debug['build'] ?? []);
$jsSyntax = (array)($debug['js_syntax'] ?? []);
$consistency = (array)($debug['consistency'] ?? []);
$issues = (array)($debug['issues'] ?? []);
$warnings = (array)($debug['warnings'] ?? []);
$overallOk = !empty($debug['overall_ok']);

function pd_h($value): string {
    if ($value === null || $value === '') {
        return 'N/A';
    }
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function pd_bool($value): string {
    if ($value === null) {
        return pd_t('na');
    }
    return $value ? pd_t('ok') : pd_t('ng');
}

function pd_bool_class($value): string {
    if ($value === null) {
        return 'pd-na';
    }
    return $value ? 'pd-ok' : 'pd-ng';
}

function pd_bytes($bytes): string {
    if ($bytes === null || $bytes === '') return pd_t('na');
    $bytes = (float)$bytes;
    if ($bytes < 1024) return number_format($bytes, 0) . ' B';
    if ($bytes < 1024 * 1024) return number_format($bytes / 1024, 1) . ' KB';
    if ($bytes < 1024 * 1024 * 1024) return number_format($bytes / 1024 / 1024, 1) . ' MB';
    return number_format($bytes / 1024 / 1024 / 1024, 2) . ' GB';
}

function pd_status_label(string $status): string {
    if ($status === 'ok') return pd_t('ok');
    if ($status === 'ng') return pd_t('ng');
    return pd_t('warnings');
}

function pd_status_class(string $status): string {
    if ($status === 'ok') return 'pd-ok';
    if ($status === 'ng') return 'pd-ng';
    return 'pd-na';
}

function pd_summary_label(string $key): string {
    $map = [
        'file_integrity'=>'file_integrity',
        'db_schema'=>'db_schema',
        'db_quick_check'=>'db_quick_check',
        'disk_ramdisk'=>'disk_ramdisk',
        'controller_test'=>'controller_test_summary',
        'service_status'=>'service_status',
        'recent_logs'=>'recent_logs',
        'version_image'=>'version_image',
        'download_report'=>'download_report_summary',
        'readonly_mode'=>'readonly_mode',
        'emergency_syntax'=>'emergency_syntax_summary',
        'language_support'=>'language_support',
        'php_syntax'=>'php_syntax_summary',
        'js_syntax'=>'js_syntax_summary',
        'build_zip'=>'build_zip',
    ];
    return pd_t($map[$key] ?? $key);
}

function pd_summary_status(string $status): array {
    if ($status === 'ok') return ['label'=>pd_t('ok'),'class'=>'pd-ok'];
    if ($status === 'ng') return ['label'=>pd_t('ng'),'class'=>'pd-ng'];
    if ($status === 'available') return ['label'=>pd_t('available'),'class'=>'pd-ok'];
    if ($status === 'not_tested') return ['label'=>pd_t('not_tested_status'),'class'=>'pd-na'];
    if ($status === 'languages') return ['label'=>pd_t('languages_status'),'class'=>'pd-ok'];
    return ['label'=>pd_t('warnings'),'class'=>'pd-na'];
}

function pd_db_health(array $db): ?bool {
    if (empty($db['exists'])) return false;
    if ((int)($db['size_bytes'] ?? 0) <= 0) return false;
    if (empty($db['readable'])) return false;
    if (empty($db['sqlite_open'])) return false;
    if (empty($db['table_exists'])) return false;
    if (empty($db['row_exists'])) return false;
    return true;
}
?>

<style>
.platform-debug-page{
    min-height: calc(100vh - 65px);
    padding: 18px 22px 30px;
    background: #f2f4f7;
    color: #1f2937;
    box-sizing: border-box;
    font-family: Arial, "Noto Sans TC", sans-serif;
}
.platform-debug-toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:16px;
}
.platform-debug-title{
    margin:0;
    font-size:25px;
    font-weight:700;
}
.platform-debug-actions{
    display:flex;
    gap:8px;
    flex-wrap:wrap;
}
.platform-debug-btn{
    height:38px;
    padding:0 16px;
    border:1px solid #aeb6c2;
    border-radius:7px;
    background:#fff;
    cursor:pointer;
    font-size:15px;
}
.platform-debug-btn:hover{ background:#eef2f6; }
.platform-debug-overall{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    padding:16px 18px;
    border-radius:10px;
    margin-bottom:16px;
    background:#fff;
    box-shadow:0 2px 7px rgba(0,0,0,.10);
}
.platform-debug-overall.pd-overall-ok{ border-left:7px solid #2e7d32; }
.platform-debug-overall.pd-overall-ng{ border-left:7px solid #c62828; }
.platform-debug-status-main{
    font-size:24px;
    font-weight:800;
}
.platform-debug-generated{
    color:#667085;
    font-size:14px;
}

.platform-debug-summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;padding:12px}
.platform-debug-summary-item{display:flex;align-items:center;justify-content:space-between;gap:14px;min-height:42px;padding:8px 12px;border:1px solid #e3e7ec;border-radius:7px;background:#fafbfc}
.platform-debug-summary-label{font-weight:600}
.platform-debug-summary-status{flex:0 0 auto;font-weight:800;text-align:right}
@media (max-width:1000px){.platform-debug-summary{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:620px){.platform-debug-summary{grid-template-columns:1fr}}

.platform-debug-grid{
    display:grid;
    grid-template-columns:repeat(2, minmax(0, 1fr));
    gap:14px;
}
.platform-debug-card{
    min-width:0;
    background:#fff;
    border-radius:10px;
    box-shadow:0 2px 7px rgba(0,0,0,.10);
    overflow:hidden;
}
.platform-debug-card.pd-full{ grid-column:1 / -1; }
.platform-debug-card-title{
    padding:11px 14px;
    background:#e8edf3;
    font-size:17px;
    font-weight:700;
    border-bottom:1px solid #d5dbe3;
}
.platform-debug-table{
    width:100%;
    border-collapse:collapse;
    table-layout:fixed;
}
.platform-debug-table th,
.platform-debug-table td{
    padding:9px 12px;
    border-bottom:1px solid #edf0f3;
    text-align:left;
    vertical-align:top;
    overflow-wrap:anywhere;
}
.platform-debug-table th{
    width:38%;
    color:#475467;
    font-weight:600;
    background:#fafbfc;
}
.platform-debug-table tr:last-child th,
.platform-debug-table tr:last-child td{ border-bottom:none; }
.pd-ok{ color:#24752a; font-weight:800; }
.pd-ng{ color:#c62828; font-weight:800; }
.pd-na{ color:#667085; font-weight:700; }
.pd-code{
    font-family:Consolas, "Courier New", monospace;
    font-size:13px;
}
.platform-debug-list{
    margin:0;
    padding:12px 18px 14px 36px;
}
.platform-debug-list li{ margin:6px 0; }
.platform-debug-list.pd-issues li{ color:#b42318; }
.platform-debug-list.pd-warnings li{ color:#9a6700; }
.platform-debug-empty{
    padding:14px;
    color:#667085;
}
.platform-debug-note{
    margin-top:14px;
    padding:10px 12px;
    border-radius:7px;
    background:#fff7df;
    border:1px solid #f2d28a;
    font-size:14px;
}
@media (max-width: 900px){
    .platform-debug-grid{ grid-template-columns:1fr; }
    .platform-debug-card.pd-full{ grid-column:auto; }
}
@media (max-width: 600px){
    .platform-debug-page{ padding:12px 10px 22px; }
    .platform-debug-toolbar{ align-items:flex-start; flex-direction:column; }
    .platform-debug-overall{ align-items:flex-start; flex-direction:column; }
    .platform-debug-title{ font-size:22px; }
    .platform-debug-table th{ width:42%; }
}
</style>

<div class="platform-debug-page">
    <div class="platform-debug-toolbar">
        <h1 class="platform-debug-title"><?php echo pd_h(pd_t('title')); ?></h1>
        <div class="platform-debug-actions">
            <button type="button" class="platform-debug-btn" onclick="location.reload()"><?php echo pd_h(pd_t('refresh')); ?></button>
            <button type="button" class="platform-debug-btn" id="controllerDebugTestBtn" onclick="runControllerDebugTest()"><?php echo pd_h(pd_t('controller_test')); ?></button>
            <button type="button" class="platform-debug-btn" onclick="location.href='?url=Settings/platform_debug_report'"><?php echo pd_h(pd_t('download_report')); ?></button>
            <button type="button" class="platform-debug-btn" onclick="window.open('?syntax_check=1', '_blank')"><?php echo pd_h(pd_t('standalone_syntax')); ?></button>
            <button type="button" class="platform-debug-btn" onclick="location.href='?url=Settings/index'"><?php echo pd_h(pd_t('back')); ?></button>
        </div>
    </div>

    <div class="platform-debug-overall <?php echo $overallOk ? 'pd-overall-ok' : 'pd-overall-ng'; ?>">
        <div>
            <div class="platform-debug-status-main <?php echo $overallOk ? 'pd-ok' : 'pd-ng'; ?>">
                <?php echo pd_h(pd_t('system_status')); ?>: <?php echo pd_h($overallOk ? pd_t('ok') : pd_t('ng')); ?>
            </div>
            <div>
                <?php echo pd_h(pd_t('platform')); ?>: <?php echo pd_h($platform['name'] ?? null); ?>
                &nbsp;|&nbsp;
                <?php echo pd_h(pd_t('protocol')); ?>: <?php echo pd_h($controller['protocol'] ?? null); ?>
                &nbsp;|&nbsp;
                <?php echo pd_h(pd_t('server_port')); ?>: <?php echo pd_h($controller['server_port'] ?? null); ?>
            </div>
        </div>
        <div class="platform-debug-generated">
            <?php echo pd_h(pd_t('snapshot')); ?>: <?php echo pd_h($debug['generated_at'] ?? null); ?>
        </div>
    </div>

    <section class="platform-debug-card pd-full" style="margin-bottom:16px;">
        <div class="platform-debug-card-title"><?php echo pd_h(pd_t('capability_summary')); ?></div>
        <div class="platform-debug-summary">
            <?php foreach ($capabilitySummary as $summaryItem): ?>
                <?php
                $summaryKey = (string)($summaryItem['key'] ?? '');
                $summaryStatus = (string)($summaryItem['status'] ?? 'warning');
                $summaryDisplay = pd_summary_status($summaryStatus);
                $summaryId = $summaryKey === 'controller_test' ? 'controllerCapabilityStatus' : '';
                ?>
                <div class="platform-debug-summary-item">
                    <div class="platform-debug-summary-label"><?php echo pd_h(pd_summary_label($summaryKey)); ?></div>
                    <div <?php echo $summaryId !== '' ? 'id="' . pd_h($summaryId) . '"' : ''; ?>
                         class="platform-debug-summary-status <?php echo pd_h($summaryDisplay['class']); ?>">
                        <?php echo pd_h($summaryDisplay['label']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="platform-debug-grid">
        <section class="platform-debug-card">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('platform')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('platform')); ?></th><td><?php echo pd_h($platform['name'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('idas_platform')); ?></th><td class="pd-code"><?php echo pd_h($platform['id'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('resolved_flag')); ?></th><td><?php echo pd_h($platform['runtime_flag'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('flag_file')); ?></th><td class="pd-code"><?php echo pd_h($platform['flag_file']['path'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('raw_flag_value')); ?></th><td><?php echo pd_h($platform['flag_file']['raw_value'] ?? null); ?></td></tr>
                <tr>
                    <th><?php echo pd_h(pd_t('flag_file_status')); ?></th>
                    <td class="<?php echo !empty($platform['flag_file']['valid']) ? 'pd-ok' : 'pd-na'; ?>">
                        <?php echo pd_h(!empty($platform['flag_file']['valid']) ? pd_t('ok') : pd_t('default_warning')); ?>
                    </td>
                </tr>
            </table>
        </section>

        <section class="platform-debug-card">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('runtime_controller')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('device_id')); ?></th><td><?php echo pd_h($controller['device_id'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('protocol')); ?></th><td><?php echo pd_h($controller['protocol'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('modbus_type')); ?></th><td><?php echo pd_h($controller['modbus_type'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('server_port')); ?></th><td><?php echo pd_h($controller['server_port'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('expected_port')); ?></th><td><?php echo pd_h($consistency['expected_port'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('wifi_raw')); ?></th><td class="pd-code"><?php echo pd_h($controller['wifi_raw'] ?? null); ?></td></tr>
            </table>
        </section>

        <section class="platform-debug-card">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('controller_db')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('path')); ?></th><td class="pd-code"><?php echo pd_h($controllerDb['path'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('size')); ?></th><td><?php echo pd_h($controllerDb['size_human'] ?? null); ?> (<?php echo pd_h($controllerDb['size_bytes'] ?? null); ?> bytes)</td></tr>
                <tr><th><?php echo pd_h(pd_t('readable')); ?></th><td class="<?php echo pd_bool_class($controllerDb['readable'] ?? false); ?>"><?php echo pd_bool($controllerDb['readable'] ?? false); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('writable')); ?></th><td><?php echo pd_h(!empty($controllerDb['writable']) ? pd_t('yes') : pd_t('no')); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('sqlite_open')); ?></th><td class="<?php echo pd_bool_class($controllerDb['sqlite_open'] ?? false); ?>"><?php echo pd_bool($controllerDb['sqlite_open'] ?? false); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('table')); ?></th><td class="<?php echo pd_bool_class($controllerDb['table_exists'] ?? false); ?>"><?php echo !empty($controllerDb['table_exists']) ? 'ntcs_device_test / OK' : 'NG'; ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('row')); ?></th><td class="<?php echo pd_bool_class($controllerDb['row_exists'] ?? false); ?>"><?php echo pd_bool($controllerDb['row_exists'] ?? false); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('db_health')); ?></th><td class="<?php echo pd_bool_class(pd_db_health($controllerDb)); ?>"><?php echo pd_bool(pd_db_health($controllerDb)); ?></td></tr>
                <?php if (!empty($controllerDb['error'])): ?>
                <tr><th><?php echo pd_h(pd_t('error')); ?></th><td class="pd-ng pd-code"><?php echo pd_h($controllerDb['error']); ?></td></tr>
                <?php endif; ?>
            </table>
        </section>

        <section class="platform-debug-card">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('idas_db')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('path')); ?></th><td class="pd-code"><?php echo pd_h($idasDb['path'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('size')); ?></th><td><?php echo pd_h($idasDb['size_human'] ?? null); ?> (<?php echo pd_h($idasDb['size_bytes'] ?? null); ?> bytes)</td></tr>
                <tr><th><?php echo pd_h(pd_t('readable')); ?></th><td class="<?php echo pd_bool_class($idasDb['readable'] ?? false); ?>"><?php echo pd_bool($idasDb['readable'] ?? false); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('writable')); ?></th><td><?php echo pd_h(!empty($idasDb['writable']) ? pd_t('yes') : pd_t('no')); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('sqlite_open')); ?></th><td class="<?php echo pd_bool_class($idasDb['sqlite_open'] ?? false); ?>"><?php echo pd_bool($idasDb['sqlite_open'] ?? false); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('table')); ?></th><td class="<?php echo pd_bool_class($idasDb['table_exists'] ?? false); ?>"><?php echo !empty($idasDb['table_exists']) ? 'ntcs_device_test / OK' : 'NG'; ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('row')); ?></th><td class="<?php echo pd_bool_class($idasDb['row_exists'] ?? false); ?>"><?php echo pd_bool($idasDb['row_exists'] ?? false); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('db_health')); ?></th><td class="<?php echo pd_bool_class(pd_db_health($idasDb)); ?>"><?php echo pd_bool(pd_db_health($idasDb)); ?></td></tr>
                <?php if (!empty($idasDb['error'])): ?>
                <tr><th><?php echo pd_h(pd_t('error')); ?></th><td class="pd-ng pd-code"><?php echo pd_h($idasDb['error']); ?></td></tr>
                <?php endif; ?>
            </table>
        </section>

        <section class="platform-debug-card">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('controller_db_values')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('device_id')); ?></th><td><?php echo pd_h($controllerDb['device_id'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('modbus_type')); ?></th><td><?php echo pd_h($controllerDb['modbus_type'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('network_mode')); ?></th><td><?php echo pd_h($controllerDb['network_mode'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('static_ip')); ?></th><td><?php echo pd_h($controllerDb['static_ip'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('server_port')); ?></th><td><?php echo pd_h($controllerDb['server_port'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('mask')); ?></th><td><?php echo pd_h($controllerDb['mask'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('gateway')); ?></th><td><?php echo pd_h($controllerDb['gateway'] ?? null); ?></td></tr>
            </table>
        </section>

        <section class="platform-debug-card">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('idas_db_values')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('device_id')); ?></th><td><?php echo pd_h($idasDb['device_id'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('modbus_type')); ?></th><td><?php echo pd_h($idasDb['modbus_type'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('network_mode')); ?></th><td><?php echo pd_h($idasDb['network_mode'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('static_ip')); ?></th><td><?php echo pd_h($idasDb['static_ip'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('server_port')); ?></th><td><?php echo pd_h($idasDb['server_port'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('mask')); ?></th><td><?php echo pd_h($idasDb['mask'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('gateway')); ?></th><td><?php echo pd_h($idasDb['gateway'] ?? null); ?></td></tr>
            </table>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('php_debug')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('php_version')); ?></th><td><?php echo pd_h($php['version'] ?? null); ?> (ID <?php echo pd_h($php['version_id'] ?? null); ?>)</td></tr>
                <tr><th><?php echo pd_h(pd_t('php_sapi')); ?></th><td><?php echo pd_h($php['sapi'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('os_arch')); ?></th><td><?php echo pd_h(($php['os_full'] ?? '') . ' / ' . ($php['architecture'] ?? '')); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('server_software')); ?></th><td class="pd-code"><?php echo pd_h($php['server_software'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('document_root')); ?></th><td class="pd-code"><?php echo pd_h($php['document_root'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('script_filename')); ?></th><td class="pd-code"><?php echo pd_h($php['script_filename'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('loaded_ini')); ?></th><td class="pd-code"><?php echo pd_h($php['loaded_ini'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('scanned_ini')); ?></th><td class="pd-code"><?php echo pd_h($php['scanned_ini'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('display_errors')); ?></th><td><?php echo pd_h($php['display_errors'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('log_errors')); ?></th><td><?php echo pd_h($php['log_errors'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('error_log')); ?></th><td class="pd-code"><?php echo pd_h($php['error_log'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('error_reporting')); ?></th><td><?php echo pd_h($php['error_reporting'] ?? null); ?> / <?php echo pd_h($php['error_reporting_hex'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('memory_limit')); ?></th><td><?php echo pd_h($php['memory_limit'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('max_execution_time')); ?></th><td><?php echo pd_h($php['max_execution_time'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('max_input_time')); ?></th><td><?php echo pd_h($php['max_input_time'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('upload_max_filesize')); ?></th><td><?php echo pd_h($php['upload_max_filesize'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('post_max_size')); ?></th><td><?php echo pd_h($php['post_max_size'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('max_file_uploads')); ?></th><td><?php echo pd_h($php['max_file_uploads'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('default_socket_timeout')); ?></th><td><?php echo pd_h($php['default_socket_timeout'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('timezone')); ?></th><td><?php echo pd_h($php['timezone'] ?? null); ?></td></tr>
            </table>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('php_extensions')); ?></div>
            <table class="platform-debug-table">
                <tr>
                    <th><?php echo pd_h(pd_t('extension')); ?></th>
                    <td><?php echo pd_h(pd_t('loaded')); ?></td>
                </tr>
                <?php foreach ((array)($php['extensions'] ?? []) as $extName => $loaded): ?>
                <tr>
                    <th class="pd-code"><?php echo pd_h($extName); ?></th>
                    <td class="<?php echo pd_bool_class((bool)$loaded); ?>"><?php echo pd_bool((bool)$loaded); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('syntax_check')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('php_cli')); ?></th><td class="pd-code"><?php echo pd_h($syntaxChecks['php_binary'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('checked_files')); ?></th><td><?php echo pd_h($syntaxChecks['checked_count'] ?? 0); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('syntax_ok')); ?></th><td class="pd-ok"><?php echo pd_h($syntaxChecks['ok_count'] ?? 0); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('syntax_ng')); ?></th><td class="<?php echo ((int)($syntaxChecks['ng_count'] ?? 0) === 0) ? 'pd-ok' : 'pd-ng'; ?>"><?php echo pd_h($syntaxChecks['ng_count'] ?? 0); ?></td></tr>
                <?php if (!empty($syntaxChecks['error'])): ?>
                <tr><th><?php echo pd_h(pd_t('error')); ?></th><td class="pd-na"><?php echo pd_h($syntaxChecks['error']); ?></td></tr>
                <?php endif; ?>
            </table>

            <?php if (!empty($syntaxChecks['failed'])): ?>
                <table class="platform-debug-table" style="margin-top:10px;">
                    <tr>
                        <th><?php echo pd_h(pd_t('file')); ?></th>
                        <td style="width:10%;font-weight:700;"><?php echo pd_h(pd_t('line')); ?></td>
                        <td><?php echo pd_h(pd_t('syntax_message')); ?></td>
                    </tr>
                    <?php foreach ((array)$syntaxChecks['failed'] as $syntaxFail): ?>
                    <tr>
                        <th class="pd-code"><?php echo pd_h($syntaxFail['relative_path'] ?? $syntaxFail['path'] ?? null); ?></th>
                        <td class="pd-ng"><?php echo pd_h($syntaxFail['line'] ?? null); ?></td>
                        <td class="pd-ng pd-code"><?php echo pd_h($syntaxFail['message'] ?? null); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php elseif (!empty($syntaxChecks['available'])): ?>
                <div class="platform-debug-empty pd-ok"><?php echo pd_h(pd_t('no_syntax_errors')); ?></div>
            <?php endif; ?>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('php_error_debug')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('error_log_path')); ?></th><td class="pd-code"><?php echo pd_h($phpErrors['path'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('error_log_exists')); ?></th><td class="<?php echo pd_bool_class($phpErrors['exists'] ?? false); ?>"><?php echo pd_bool($phpErrors['exists'] ?? false); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('error_log_readable')); ?></th><td class="<?php echo pd_bool_class($phpErrors['readable'] ?? false); ?>"><?php echo pd_bool($phpErrors['readable'] ?? false); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('error_log_size')); ?></th><td><?php echo pd_h($phpErrors['size_bytes'] ?? 0); ?> bytes</td></tr>
                <tr><th><?php echo pd_h(pd_t('last_modified')); ?></th><td><?php echo pd_h($phpErrors['modified_at'] ?? null); ?></td></tr>
                <?php if (!empty($phpErrors['error'])): ?>
                <tr><th><?php echo pd_h(pd_t('error')); ?></th><td class="pd-na"><?php echo pd_h($phpErrors['error']); ?></td></tr>
                <?php endif; ?>
            </table>

            <div class="platform-debug-card-title" style="border-top:1px solid #d5dbe3;">
                <?php echo pd_h(pd_t('recent_php_errors')); ?>
            </div>
            <?php if (!empty($phpErrors['recent_errors'])): ?>
                <ul class="platform-debug-list pd-issues">
                    <?php foreach ((array)$phpErrors['recent_errors'] as $errorLine): ?>
                        <li class="pd-code"><?php echo pd_h($errorLine); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="platform-debug-empty pd-ok"><?php echo pd_h(pd_t('no_recent_php_errors')); ?></div>
            <?php endif; ?>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('function_check')); ?></div>
            <table class="platform-debug-table">
                <tr>
                    <th><?php echo pd_h(pd_t('check_name')); ?></th>
                    <td style="width:18%;font-weight:700;"><?php echo pd_h(pd_t('status')); ?></td>
                    <td><?php echo pd_h(pd_t('detail')); ?></td>
                </tr>
                <?php foreach ($functionChecks as $check): ?>
                    <?php
                    $checkStatus = (string)($check['status'] ?? 'warning');
                    $checkClass = $checkStatus === 'ok' ? 'pd-ok' : ($checkStatus === 'ng' ? 'pd-ng' : 'pd-na');
                    $checkLabel = $checkStatus === 'ok'
                        ? pd_t('ok')
                        : ($checkStatus === 'ng' ? pd_t('ng') : pd_t('warnings'));
                    ?>
                    <tr>
                        <th class="pd-code"><?php echo pd_h($check['name'] ?? null); ?></th>
                        <td class="<?php echo pd_h($checkClass); ?>"><?php echo pd_h($checkLabel); ?></td>
                        <td class="pd-code"><?php echo pd_h($check['detail'] ?? null); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </section>


        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('file_integrity')); ?></div>
            <table class="platform-debug-table">
                <tr>
                    <th><?php echo pd_h(pd_t('file')); ?></th>
                    <td style="width:8%;font-weight:700;"><?php echo pd_h(pd_t('status')); ?></td>
                    <td style="width:10%;font-weight:700;"><?php echo pd_h(pd_t('size')); ?></td>
                    <td style="width:12%;font-weight:700;"><?php echo pd_h(pd_t('owner_group')); ?></td>
                    <td style="width:8%;font-weight:700;"><?php echo pd_h(pd_t('permissions')); ?></td>
                    <td><?php echo pd_h(pd_t('sha256')); ?></td>
                </tr>
                <?php foreach ($fileIntegrity as $fileItem): ?>
                <tr>
                    <th class="pd-code"><?php echo pd_h($fileItem['relative_path'] ?? null); ?></th>
                    <td class="<?php echo pd_h(pd_status_class((string)($fileItem['status'] ?? 'warning'))); ?>"><?php echo pd_h(pd_status_label((string)($fileItem['status'] ?? 'warning'))); ?></td>
                    <td><?php echo pd_h(pd_bytes($fileItem['size_bytes'] ?? null)); ?></td>
                    <td><?php echo pd_h(($fileItem['owner'] ?? 'N/A') . ' / ' . ($fileItem['group'] ?? 'N/A')); ?></td>
                    <td class="pd-code"><?php echo pd_h($fileItem['permissions'] ?? null); ?></td>
                    <td class="pd-code"><?php echo pd_h($fileItem['sha256'] ?? null); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('db_schema')); ?></div>
            <?php foreach (['controller' => pd_t('controller_db'), 'idas' => pd_t('idas_db')] as $dbKey => $dbLabel): ?>
                <?php $diag = (array)($dbDiagnostics[$dbKey] ?? []); ?>
                <table class="platform-debug-table" style="margin-bottom:10px;">
                    <tr><th><?php echo pd_h($dbLabel); ?></th><td class="pd-code"><?php echo pd_h($diag['path'] ?? null); ?></td></tr>
                    <tr><th><?php echo pd_h(pd_t('table')); ?></th><td class="<?php echo pd_bool_class($diag['table_exists'] ?? false); ?>"><?php echo pd_bool($diag['table_exists'] ?? false); ?></td></tr>
                    <tr><th><?php echo pd_h(pd_t('required_columns')); ?></th><td class="pd-code"><?php echo pd_h(implode(', ', (array)($diag['required_columns'] ?? []))); ?></td></tr>
                    <tr><th><?php echo pd_h(pd_t('missing_columns')); ?></th><td class="<?php echo empty($diag['missing_columns']) ? 'pd-ok' : 'pd-ng'; ?>"><?php echo pd_h(empty($diag['missing_columns']) ? pd_t('no') : implode(', ', (array)$diag['missing_columns'])); ?></td></tr>
                    <tr><th><?php echo pd_h(pd_t('integrity')); ?></th><td class="<?php echo pd_bool_class($diag['integrity_ok'] ?? null); ?>"><?php echo pd_bool($diag['integrity_ok'] ?? null); ?><?php if (!empty($diag['integrity_result'])): ?> — <span class="pd-code"><?php echo pd_h(implode(' | ', (array)$diag['integrity_result'])); ?></span><?php endif; ?></td></tr>
                    <?php if (!empty($diag['error'])): ?><tr><th><?php echo pd_h(pd_t('error')); ?></th><td class="pd-ng pd-code"><?php echo pd_h($diag['error']); ?></td></tr><?php endif; ?>
                </table>
            <?php endforeach; ?>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('disk_ramdisk')); ?></div>
            <table class="platform-debug-table">
                <tr>
                    <th><?php echo pd_h(pd_t('path')); ?></th>
                    <td style="width:10%;font-weight:700;"><?php echo pd_h(pd_t('exists')); ?></td>
                    <td style="width:10%;font-weight:700;"><?php echo pd_h(pd_t('writable')); ?></td>
                    <td><?php echo pd_h(pd_t('total')); ?></td>
                    <td><?php echo pd_h(pd_t('free')); ?></td>
                    <td><?php echo pd_h(pd_t('free_percent')); ?></td>
                    <td><?php echo pd_h(pd_t('mounted')); ?></td>
                </tr>
                <?php foreach ($disks as $disk): ?>
                <tr>
                    <th class="pd-code"><?php echo pd_h($disk['path'] ?? null); ?></th>
                    <td class="<?php echo pd_bool_class($disk['exists'] ?? false); ?>"><?php echo pd_bool($disk['exists'] ?? false); ?></td>
                    <td class="<?php echo pd_bool_class($disk['writable'] ?? false); ?>"><?php echo pd_bool($disk['writable'] ?? false); ?></td>
                    <td><?php echo pd_h(pd_bytes($disk['total_bytes'] ?? null)); ?></td>
                    <td><?php echo pd_h(pd_bytes($disk['free_bytes'] ?? null)); ?></td>
                    <td><?php echo pd_h(($disk['free_percent'] ?? null) === null ? pd_t('na') : $disk['free_percent'] . '%'); ?></td>
                    <td class="<?php echo pd_bool_class($disk['mounted'] ?? null); ?>"><?php echo pd_bool($disk['mounted'] ?? null); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>

        <section class="platform-debug-card">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('service_status')); ?></div>
            <table class="platform-debug-table">
                <?php foreach ($services as $service): ?>
                <tr>
                    <th><?php echo pd_h($service['name'] ?? null); ?></th>
                    <td class="<?php echo pd_h(pd_status_class((string)($service['status'] ?? 'warning'))); ?>"><?php echo pd_h(pd_status_label((string)($service['status'] ?? 'warning'))); ?></td>
                    <td class="pd-code"><?php echo pd_h($service['detail'] ?? null); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </section>

        <section class="platform-debug-card">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('version_image')); ?></div>
            <table class="platform-debug-table">
                <tr><th><?php echo pd_h(pd_t('platform')); ?></th><td><?php echo pd_h($versions['platform'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('raw_flag_value')); ?></th><td><?php echo pd_h($versions['flag_value'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('idas_version')); ?></th><td><?php echo pd_h($versions['idas_version'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('idas_model')); ?></th><td><?php echo pd_h($versions['idas_model'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('device_sn')); ?></th><td><?php echo pd_h($versions['device_sn'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('device_name')); ?></th><td><?php echo pd_h($versions['device_name'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('mcb_fw')); ?></th><td><?php echo pd_h($versions['mcb_fw_version'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('device_db_version')); ?></th><td><?php echo pd_h($versions['device_db_version'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('device_image_version')); ?></th><td><?php echo pd_h($versions['device_image_version'] ?? null); ?></td></tr>
                <tr><th><?php echo pd_h(pd_t('device_version')); ?></th><td><?php echo pd_h($versions['device_version'] ?? null); ?></td></tr>
            </table>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('upgrade_files')); ?></div>
            <table class="platform-debug-table">
                <?php if (!empty($versions['upgrade_files'])): ?>
                <tr><th><?php echo pd_h(pd_t('file')); ?></th><td><?php echo pd_h(pd_t('size')); ?></td><td><?php echo pd_h(pd_t('modified_at')); ?></td></tr>
                <?php foreach ((array)$versions['upgrade_files'] as $upgradeFile): ?>
                <tr><th class="pd-code"><?php echo pd_h($upgradeFile['name'] ?? null); ?></th><td><?php echo pd_h(pd_bytes($upgradeFile['size_bytes'] ?? null)); ?></td><td><?php echo pd_h($upgradeFile['modified_at'] ?? null); ?></td></tr>
                <?php endforeach; ?>
                <?php else: ?>
                <tr><td colspan="3" class="pd-na"><?php echo pd_h(pd_t('na')); ?></td></tr>
                <?php endif; ?>
            </table>
        </section>

        <section class="platform-debug-card pd-full" id="controllerCommunicationDebug">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('controller_diag')); ?></div>
            <div id="controllerDebugResult" class="platform-debug-empty pd-na"><?php echo pd_h(pd_t('not_tested')); ?></div>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('recent_logs')); ?></div>
            <?php foreach ($logs as $log): ?>
                <div class="platform-debug-card-title" style="border-top:1px solid #d5dbe3;background:#f7f8fa;">
                    <?php echo pd_h($log['name'] ?? null); ?>
                    — <span class="pd-code"><?php echo pd_h($log['path'] ?? null); ?></span>
                </div>
                <?php if (!empty($log['lines'])): ?>
                <ul class="platform-debug-list">
                    <?php foreach ((array)$log['lines'] as $line): ?>
                    <li class="pd-code"><?php echo pd_h($line); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <div class="platform-debug-empty pd-na"><?php echo pd_h(pd_t('no_log_lines')); ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('consistency')); ?></div>
            <table class="platform-debug-table">
                <tr>
                    <th><?php echo pd_h(pd_t('device_id_sync')); ?></th>
                    <td class="<?php echo pd_bool_class($consistency['device_id'] ?? null); ?>"><?php echo pd_bool($consistency['device_id'] ?? null); ?></td>
                </tr>
                <tr>
                    <th><?php echo pd_h(pd_t('modbus_type_sync')); ?></th>
                    <td class="<?php echo pd_bool_class($consistency['modbus_type'] ?? null); ?>"><?php echo pd_bool($consistency['modbus_type'] ?? null); ?></td>
                </tr>
                <tr>
                    <th><?php echo pd_h(pd_t('server_port_sync')); ?></th>
                    <td class="<?php echo pd_bool_class($consistency['server_port'] ?? null); ?>"><?php echo pd_bool($consistency['server_port'] ?? null); ?></td>
                </tr>
                <tr>
                    <th><?php echo pd_h(pd_t('protocol_port')); ?></th>
                    <td class="<?php echo pd_bool_class($consistency['protocol_port'] ?? null); ?>">
                        <?php echo pd_bool($consistency['protocol_port'] ?? null); ?>
                        <?php if (($consistency['expected_port'] ?? null) !== null): ?>
                            &nbsp;(<?php echo pd_h(pd_t('expected')); ?>: <?php echo pd_h($consistency['expected_port']); ?>)
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </section>

        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('issues')); ?></div>
            <?php if ($issues): ?>
                <ul class="platform-debug-list pd-issues">
                    <?php foreach ($issues as $issue): ?>
                        <li><?php echo pd_h($issue); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="platform-debug-empty pd-ok"><?php echo pd_h(pd_t('no_issues')); ?></div>
            <?php endif; ?>
        </section>

        <?php if ($warnings): ?>
        <section class="platform-debug-card pd-full">
            <div class="platform-debug-card-title"><?php echo pd_h(pd_t('warnings')); ?></div>
            <ul class="platform-debug-list pd-warnings">
                <?php foreach ($warnings as $warning): ?>
                    <li><?php echo pd_h($warning); ?></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
    </div>

    <div class="platform-debug-note">
        <?php echo pd_h(pd_t('readonly_note')); ?>
    </div>
</div>

<script>
async function runControllerDebugTest() {
    const button = document.getElementById('controllerDebugTestBtn');
    const result = document.getElementById('controllerDebugResult');
    if (!button || !result) return;

    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = <?php echo json_encode(pd_t('testing'), JSON_UNESCAPED_UNICODE); ?>;
    result.className = 'platform-debug-empty pd-na';
    result.textContent = <?php echo json_encode(pd_t('testing'), JSON_UNESCAPED_UNICODE); ?>;

    try {
        const response = await fetch('?url=Settings/platform_debug_controller_test', {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin'
        });
        const data = await response.json();

        const cls = data.ok ? 'pd-ok' : 'pd-ng';
        const c = data.tcp_connect || {};
        const r = data.register_read || {};
        const regs = r.registers || {};

        result.className = 'platform-debug-empty ' + cls;

        const capability = document.getElementById('controllerCapabilityStatus');
        if (capability) {
            capability.className = 'platform-debug-summary-status ' + (data.ok ? 'pd-ok' : 'pd-ng');
            capability.textContent = data.ok
                ? <?php echo json_encode(pd_t('ok'), JSON_UNESCAPED_UNICODE); ?>
                : <?php echo json_encode(pd_t('ng'), JSON_UNESCAPED_UNICODE); ?>;
        }

        result.innerHTML =
            '<div><strong>' + (data.ok ? <?php echo json_encode(pd_t('ok'), JSON_UNESCAPED_UNICODE); ?> : <?php echo json_encode(pd_t('ng'), JSON_UNESCAPED_UNICODE); ?>) + '</strong></div>' +
            '<div class="pd-code">Platform=' + escapeDebugHtml(data.platform) +
            ' | Protocol=' + escapeDebugHtml(data.protocol) +
            ' | Device ID=' + escapeDebugHtml(data.device_id) +
            ' | Host=' + escapeDebugHtml(data.host) +
            ' | Port=' + escapeDebugHtml(data.port) + '</div>' +
            '<div><?php echo addslashes(pd_t('tcp_connect')); ?>: ' + (c.ok ? 'OK' : 'NG') +
            ' | <?php echo addslashes(pd_t('response_time')); ?>=' + escapeDebugHtml(c.response_ms) + ' ms' +
            (c.error ? ' | ' + escapeDebugHtml(c.error) : '') + '</div>' +
            '<div><?php echo addslashes(pd_t('register_read')); ?>: ' + (r.ok ? 'OK' : 'NG') +
            ' | <?php echo addslashes(pd_t('response_time')); ?>=' + escapeDebugHtml(r.response_ms) + ' ms' +
            (r.error ? ' | ' + escapeDebugHtml(r.error) : '') + '</div>' +
            '<div class="pd-code">29002=' + escapeDebugHtml(regs['29002']) +
            ' | 29003=' + escapeDebugHtml(regs['29003']) +
            ' | 29004=' + escapeDebugHtml(regs['29004']) + '</div>';
    } catch (e) {
        result.className = 'platform-debug-empty pd-ng';
        result.textContent = 'NG: ' + (e && e.message ? e.message : String(e));
    } finally {
        button.disabled = false;
        button.textContent = originalText;
    }
}

function escapeDebugHtml(value) {
    if (value === null || value === undefined) return 'N/A';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
