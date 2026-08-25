<?php

require_once __DIR__ . '/paths.php';
require_once IDAS_PATH_IDAS_ROOT . '/service/DiskSpaceService.php';
require_once IDAS_PATH_IDAS_ROOT . '/service/DatabaseBackupService.php';
require_once IDAS_PATH_IDAS_ROOT . '/service/SystemConfigExportService.php';
require_once IDAS_PATH_IDAS_ROOT . '/service/SystemConfigImportService.php';
require_once IDAS_PATH_IDAS_ROOT . '/service/ControllerSyncService.php';
require_once IDAS_PATH_IDAS_ROOT . '/service/LocalizationService.php';
require_once IDAS_PATH_IDAS_ROOT . '/service/ApiResponseService.php';
require_once IDAS_PATH_IDAS_ROOT . '/service/DataExportService.php';

//sudo chmod -R 777 /var/www/html/idas
// sudo chmod -R 777  var/www/html/idas/public/ftp
//sudo chmod -R 777 /var/www/html/database
//sudo rm -rf /var/www/html/extracted
//sudo rm -rf /var/www/html/idas  
//sudo chown -R www-data:www-data /var/www/html/idas/app/views/step
//12345678rd
//

date_default_timezone_set(trim(shell_exec('cat /etc/timezone')));

// App 根目錄，這是引入 app 資料夾裡的資源用的
define('APPROOT', dirname(dirname(__FILE__)) . '/');


/* ============================================================
 * Platform switch
 * /home/kls/upgrade/icontroller
 *   0 / missing / invalid => NTCS (default, existing behavior)
 *   1                   => i-controller variant files
 * ============================================================ */
define('ICONTROLLER_FLAG_FILE', idas_path('upgrade_root', 'icontroller'));

function is_i_controller(): bool
{
    if (PHP_OS_FAMILY !== 'Linux') {
        return false;
    }

    $flagFile = ICONTROLLER_FLAG_FILE;
    if (!is_file($flagFile) || !is_readable($flagFile)) {
        return false;
    }

    $value = @file_get_contents($flagFile);
    if ($value === false) {
        return false;
    }

    return trim((string)$value) === '1';
}

define('IS_ICONTROLLER', is_i_controller());
define('IDAS_PLATFORM', IS_ICONTROLLER ? 'icontroller' : 'ntcs');

/*
 * ============================================================
 * Central platform / protocol policy
 * ============================================================
 */
define('IDAS_PROTOCOL_TCP', 0);
define('IDAS_PROTOCOL_RTU', 1);
define('IDAS_PROTOCOL_OP',  2);

define('IDAS_SERVER_PORT_TCP', 502);
define('IDAS_SERVER_PORT_OP',  4545);

function idas_is_icontroller(): bool
{
    return IS_ICONTROLLER;
}

function idas_network_settings_enabled(): bool
{
    return IS_ICONTROLLER;
}

function idas_controller_identity_editable(): bool
{
    return IS_ICONTROLLER;
}

function idas_protocol_editable(): bool
{
    return IS_ICONTROLLER;
}

function idas_protocol_has_fixed_server_port(int $protocol): bool
{
    // OP is fixed. TCP 502 is only a default and may be edited.
    return $protocol === IDAS_PROTOCOL_OP;
}

/**
 * TCP -> 502
 * OP  -> 4545
 * RTU -> preserve current value (or null when not supplied)
 */
function idas_protocol_server_port(int $protocol, ?int $currentPort = null): ?int
{
    if ($protocol === IDAS_PROTOCOL_OP) {
        return IDAS_SERVER_PORT_OP;
    }

    if ($protocol === IDAS_PROTOCOL_TCP) {
        // TCP defaults to 502, but an existing custom value is preserved.
        return $currentPort ?? IDAS_SERVER_PORT_TCP;
    }

    // RTU preserves its current value.
    return $currentPort;
}

/** Resolve an i-controller variant file when enabled. */
function idas_platform_app_file(string $relative): string
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    $defaultFile = APPROOT . $relative;

    if (IS_ICONTROLLER) {
        $info = pathinfo($relative);
        $dir  = isset($info['dirname']) && $info['dirname'] !== '.'
            ? rtrim($info['dirname'], '/') . '/'
            : '';
        $name = $info['filename'] ?? '';
        $ext  = isset($info['extension']) ? '.' . $info['extension'] : '';

        $iControllerFile = APPROOT
            . $dir
            . $name
            . '_icontroller'
            . $ext;

        if (is_file($iControllerFile)) {
            return $iControllerFile;
        }
    }

    return $defaultFile;
}

/** Resolve an i-controller variant asset when enabled. */
function idas_asset_url(string $relative): string
{
    $relative = ltrim(str_replace('\\', '/', $relative), '/');
    return URLROOT . $relative;
}

/** Persistent Controller identity baseline used by NTCS change detection. */
function idas_identity_state_path(string $name): string
{
    return IDAS_PATH_DATABASE_ROOT . '/' . $name . '.json';
}

function idas_read_identity_state(string $name): ?array
{
    $path = idas_identity_state_path($name);
    if (!is_file($path) || !is_readable($path)) {
        return null;
    }
    $data = json_decode((string)@file_get_contents($path), true);
    return is_array($data) ? $data : null;
}

function idas_write_identity_state(string $name, array $data): bool
{
    $path = idas_identity_state_path($name);
    $tmp = $path . '.tmp.' . getmypid();
    $data['updated_at'] = time();
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || @file_put_contents($tmp, $json, LOCK_EX) === false) {
        @unlink($tmp);
        return false;
    }
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    return true;
}

function idas_clear_identity_state(string $name): void
{
    $path = idas_identity_state_path($name);
    if (is_file($path)) {
        @unlink($path);
    }
}

/** Rotate controller logs in place; database files are never moved. */
function idas_rotate_database_log(string $path, int $maxBytes = 524288, int $keep = 3): bool
{
    if (PHP_OS_FAMILY !== 'Linux' || $maxBytes < 1024 || $keep < 1) return false;
    $databaseDir = IDAS_PATH_DATABASE_ROOT . '/';
    $realDir = realpath(dirname($path));
    if ($realDir === false || rtrim($realDir, '/') . '/' !== $databaseDir || !is_file($path)) return false;
    clearstatcache(true, $path);
    if ((int)@filesize($path) <= $maxBytes) return false;

    $lock = @fopen($path . '.rotate.lock', 'c');
    if (!$lock || !@flock($lock, LOCK_EX | LOCK_NB)) {
        if ($lock) @fclose($lock);
        return false;
    }
    try {
        clearstatcache(true, $path);
        if ((int)@filesize($path) <= $maxBytes) return false;
        @unlink($path . '.' . $keep);
        for ($index = $keep - 1; $index >= 1; $index--) {
            $from = $path . '.' . $index;
            if (is_file($from)) @rename($from, $path . '.' . ($index + 1));
        }
        if (!@rename($path, $path . '.1')) return false;
        @touch($path);
        @chmod($path, 0660);
        for ($index = 1; $index <= $keep; $index++) {
            if (is_file($path . '.' . $index)) @chmod($path . '.' . $index, 0660);
        }
        return true;
    } finally {
        @flock($lock, LOCK_UN);
        @fclose($lock);
    }
}

/** Clean successful/legacy update backups while preserving failed/pending files. */
function idas_cleanup_update_backups(
    string $backupDir,
    int $keep = DatabaseBackupService::DEFAULT_BACKUP_KEEP,
    int $maxAgeDays = DatabaseBackupService::DEFAULT_BACKUP_MAX_AGE_DAYS
): int
{
    return DatabaseBackupService::cleanupUpdateBackups($backupDir, $keep, $maxAgeDays);
}

/** Return free bytes for the production database filesystem. */
function idas_database_free_bytes(): int
{
    return DiskSpaceService::databaseFreeBytes();
}

/** Recursively remove only a previously validated update temporary directory. */
function idas_remove_update_temp_directory(string $path): bool
{
    return DatabaseBackupService::removeUpdateTempDirectory($path);
}

/**
 * Safe database cleanup shared by NTCS and iController.
 * Never removes production DB files or failed/pending backup evidence.
 */
function idas_safe_database_cleanup(bool $aggressive = false, bool $updateLockOwned = false): array
{
    return DatabaseBackupService::safeCleanup($aggressive, $updateLockOwned);
}

/** Validate readable production SQLite databases before an update mutates files. */
function idas_quick_check_production_databases(): array
{
    return DatabaseBackupService::quickCheckProductionDatabases();
}

/** Calculate a conservative update working-space requirement from ZIP metadata. */
function idas_update_pack_space_requirement(string $zipPath, int $uploadBytes = 0): array
{
    return DiskSpaceService::updatePackSpaceRequirement($zipPath, $uploadBytes);
}

/** Best-effort log maintenance, throttled to once every five minutes. */
function idas_database_maintenance_tick(): void
{
    if (PHP_OS_FAMILY !== 'Linux') return;
    $dir = IDAS_PATH_DATABASE_ROOT;
    if (!is_dir($dir) || !is_writable($dir)) return;
    $stamp = $dir . '/.idas_database_maintenance.stamp';
    if (is_file($stamp) && (time() - (int)@filemtime($stamp)) < 300) return;
    $lock = @fopen($dir . '/.idas_database_maintenance.lock', 'c');
    if (!$lock || !@flock($lock, LOCK_EX | LOCK_NB)) {
        if ($lock) @fclose($lock);
        return;
    }
    try {
        clearstatcache(true, $stamp);
        if (is_file($stamp) && (time() - (int)@filemtime($stamp)) < 300) return;
        @touch($stamp);
        @chmod($stamp, 0660);
        foreach (['idas_icontroller_reboot.log', 'idas_update.log', 'lin_refresh.log'] as $name) {
            idas_rotate_database_log($dir . '/' . $name, 524288, 3);
        }
        idas_safe_database_cleanup(false);
    } finally {
        @flock($lock, LOCK_UN);
        @fclose($lock);
    }
}

function idas_record_controller_restart_pending(array $change): bool
{
    $old = idas_read_identity_state('idas_controller_restart_pending') ?? [];
    // A newly saved setting starts a new restart cycle.
    unset($old['restart_scheduled_at']);
    foreach (['id_changed', 'modbus_type_changed', 'server_port_changed'] as $key) {
        $old[$key] = !empty($old[$key]) || !empty($change[$key]);
    }
    foreach ($change as $key => $value) {
        if ($value !== null) {
            $old[$key] = $value;
        }
    }
    $count = (!empty($old['id_changed']) ? 1 : 0)
        + (!empty($old['modbus_type_changed']) ? 1 : 0)
        + (!empty($old['server_port_changed']) ? 1 : 0);
    $old['change_type'] = $count > 1
        ? 'both'
        : (!empty($old['id_changed'])
            ? 'device_id'
            : (!empty($old['modbus_type_changed'])
                ? 'modbus_type'
                : (!empty($old['server_port_changed']) ? 'server_port' : 'none')));
    return idas_write_identity_state('idas_controller_restart_pending', $old);
}

// URL 根目錄，這是引入 public 資料夾裡的資源，或是頁面跳轉時用的
define('URLROOT', '../public/'); //local用

// 網站名稱
define('SITENAME', 'iDAS');

// iDAS連線模式 0:單機版 1:連線版
define('IDASMODE', '1');

// 設定語言狀態
$language = array(
	0=>array('简中','zh-cn'),
	1=>array('繁中','zh-tw'),
	2=>array('English','en-us'),
);
define('LANGUAGE',$language);




define('CONTROLLER_IP', '127.0.0.1');

// 每次刷新都取最新時間，避免快取
// Release suffix guarantees that browsers do not reuse the pre-fix
// settings.js whose platform block was skipped before IS_ICONTROLLER existed.
define('ASSET_VERSION', (string)(@filemtime(__FILE__) ?: '20260825') . '-v20-ui14');

//table - barcode 
define('TABLE_NTCS_BARCODE', 'ntcs_barcode_test');

//table - tools
define('TABLE_NTCS_TOOLS', 'ntcs_tool_test');


//table - device
define('TABLE_NTCS_DEVICE', 'ntcs_device_test');


// 抓取APP的檔案名稱，判斷是哪一個品牌
$brand = get_iconmode_from_ver();
//var_dump($brand_code);
//if()
//$brand = 0;//預設值帶kilews

/*if($brand_code == false || $brand_code == 'BF01'){ //Kilews or Windows
	$brand = '0';
}else if($brand_code == 'BF02'){ //上海
	$brand = '2';
}else if($brand_code == 'BF04'){ //MyTorq
	$brand = '4';
}else if($brand_code == 'BF05'){ //SUMAKE
	$brand = '5';
}else if($brand_code == 'BF06'){ //DELTA
	$brand = '6';
}else if($brand_code == 'BF07'){ //白牌
	$brand = '7';
}*/


// iDAS出貨版本 0:Kilews 2:上海 shanhai 4:MyTorque 5:晶元SUMAKE 6:DELTA 7:白牌
define('ICONMODE', (int)$brand);

switch (ICONMODE) {
	case 0: // Kilews
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       'KILEWS');
		define('SUBTITLE_INDEX',    'iDAS FOR KL-NTCS-M7');
		define('TITLE_AGENT',       'KILEWS IoT Agent');
		define('DEVICE_TYPE_11',    'KL-NTCS-M7');
	break;

	case 2: // 上海 shanhai
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       'KILEWS');
		define('SUBTITLE_INDEX',    'iDAS FOR KL-EPNC-M7');
		define('TITLE_AGENT',       'EPNC IoT Agent');
		define('DEVICE_TYPE_11',    'KL-EPNC-M7');
	break;

	case 4: // MyTorque
		define('ICON_NORMAL',       URLROOT . 'img/MY-icon/yellow-192x192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/MY-icon/yellow-60x60.png');
		define('ICON_AGENT',        URLROOT . 'img/MY-icon/blue-192x192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/MY-icon/blue-60x60.png');
		define('TITLE_INDEX',       'MYTORQ');
		define('SUBTITLE_INDEX',    'iDAS FOR MY-EVO-M7');
		define('TITLE_AGENT',       'MYTORQ IoT Agent');
		define('DEVICE_TYPE_11',    'MY-EVO-M7');
	break;

	case 5: // 晶元 SUMAKE
		define('ICON_NORMAL',       URLROOT . 'img/Sumake_icon/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/Sumake_icon/60.png');
		define('ICON_AGENT',        URLROOT . 'img/Sumake_icon/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/Sumake_icon/60.png');
		define('TITLE_INDEX',       'SUMAKE');
		define('SUBTITLE_INDEX',    'iDAS FOR SMT-C3');
		define('TITLE_AGENT',       'SUMAKE IoT Agent');
		define('DEVICE_TYPE_11',    'SMT-C3');
	break;

	case 6: // DELTA
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       'DELTA');
		define('SUBTITLE_INDEX',    'iDAS FOR XTCA1');
		define('TITLE_AGENT',       'DELTA IoT Agent');
		define('DEVICE_TYPE_11',    'NTCS-M7');
	break;

	case 7: // 白牌
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       '');
		define('SUBTITLE_INDEX',    'iDAS FOR OPT-GK TRS1');
		define('TITLE_AGENT',       'IoT Agent');
		define('DEVICE_TYPE_11',    'NTCS-M7');
	break;

	default:
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       'KILEWS');
		define('SUBTITLE_INDEX',    'iDAS FOR KL-NTCS-M7');
		define('TITLE_AGENT',       'KILEWS IoT Agent');
		define('DEVICE_TYPE_11',    'NTCS-M7');
	break;
}



function get_iconmode_from_ver()
{
    // 非 Linux 直接回預設
    if (!defined('PHP_OS_FAMILY') || PHP_OS_FAMILY !== 'Linux') {
        return 0; // 預設 Kilews
    }

    $verFile = IDAS_PATH_CONTROLLER_ROOT . '/version';
    if (!is_file($verFile) || !is_readable($verFile)) {
        return 0;
    }

    $content = file_get_contents($verFile);
    if ($content === false) {
        return 0;
    }

    // 取第一行
    $lines = preg_split("/\r\n|\n|\r/", trim($content));
    $firstLine = isset($lines[0]) ? trim((string)$lines[0]) : '';
    if ($firstLine === '') {
        return 0;
    }

    //只取空格前面的識別碼(第一個)
    $brandKey = explode(' ', $firstLine, 2)[0];

    // 品牌對照
    $map = [
        'NTCS7'  => 0, // Kilews
        'EPNC7'  => 2, // 上海
        'SMT-C3' => 5, // SUMAKE
		'MY-EVO' =>4, //MYTORQ
    ];

    return $map[$brandKey] ?? 0;
}

idas_database_maintenance_tick();
