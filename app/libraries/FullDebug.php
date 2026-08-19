<?php

/**
 * Extended read-only diagnostics for the iDAS Platform Debug page.
 *
 * No settings/DB writes, no reboot, no firmware update, no DB sync.
 */
class FullDebug
{
    private const CONTROLLER_DB = '/home/kls/NTCS7/ntcs_device.db';
    private const IDAS_DB = '/var/www/html/database/ntcs_device_IDAS.db';
    private const DEVICE_TABLE = 'ntcs_device_test';

    public static function collect(): array
    {
        $fileIntegrity = self::inspectFiles();
        $dbDiagnostics = [
            'controller' => self::inspectDb(self::CONTROLLER_DB),
            'idas' => self::inspectDb(self::IDAS_DB),
        ];
        $disks = self::inspectDisks();
        $services = self::inspectServices();
        $versions = self::inspectVersions();
        $logs = self::inspectLogs();
        $jsSyntax = self::inspectJsSyntax();
        $build = self::inspectBuildManifest();

        $issues = [];
        $warnings = [];

        foreach ($fileIntegrity as $item) {
            if (($item['status'] ?? '') === 'ng') {
                $issues[] = 'Critical file problem: ' . ($item['relative_path'] ?? $item['path'] ?? 'Unknown');
            }
        }

        foreach ($dbDiagnostics as $name => $diag) {
            $label = $name === 'controller' ? 'Controller DB' : 'iDAS DB';
            if (($diag['integrity_ok'] ?? null) === false) {
                $issues[] = $label . ' PRAGMA quick_check failed.';
            }
            foreach (($diag['missing_columns'] ?? []) as $column) {
                $issues[] = $label . ' missing column: ' . $column;
            }
        }

        foreach ($disks as $disk) {
            if (($disk['exists'] ?? false) === false) {
                $issues[] = 'Required path does not exist: ' . ($disk['path'] ?? 'Unknown');
            } elseif (($disk['writable'] ?? false) === false && !empty($disk['write_expected'])) {
                $warnings[] = 'Path is not writable: ' . ($disk['path'] ?? 'Unknown');
            }

            if (($disk['free_percent'] ?? null) !== null && (float)$disk['free_percent'] < 5.0) {
                $warnings[] = 'Low free disk space: ' . ($disk['path'] ?? 'Unknown');
            }
        }

        foreach ($logs as $log) {
            if (!empty($log['configured']) && !empty($log['exists']) && empty($log['readable'])) {
                $warnings[] = 'Log exists but is not readable: ' . ($log['path'] ?? 'Unknown');
            }
        }

        if (($jsSyntax['available'] ?? false) && (int)($jsSyntax['ng_count'] ?? 0) > 0) {
            $issues[] = 'JavaScript syntax check failed.';
        } elseif (!($jsSyntax['available'] ?? false) && !empty($jsSyntax['error'])) {
            $warnings[] = 'JavaScript syntax check unavailable: ' . $jsSyntax['error'];
        }

        if (($build['available'] ?? false) && empty($build['zip_integrity_ok'])) {
            $issues[] = 'Build / ZIP integrity manifest reports NG.';
        } elseif (!($build['available'] ?? false)) {
            $warnings[] = 'Build manifest is unavailable.';
        }

        return [
            'file_integrity' => $fileIntegrity,
            'db_diagnostics' => $dbDiagnostics,
            'disks' => $disks,
            'services' => $services,
            'versions' => $versions,
            'logs' => $logs,
            'js_syntax' => $jsSyntax,
            'build' => $build,
            'issues' => array_values(array_unique($issues)),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    public static function merge(array $base, array $extra): array
    {
        foreach (['issues', 'warnings'] as $key) {
            $base[$key] = array_values(array_unique(array_merge(
                (array)($base[$key] ?? []),
                (array)($extra[$key] ?? [])
            )));
        }

        foreach (['file_integrity', 'db_diagnostics', 'disks', 'services', 'versions', 'logs', 'js_syntax', 'build'] as $key) {
            $base[$key] = $extra[$key] ?? [];
        }

        $base['capability_summary'] = self::buildCapabilitySummary($base);
        $base['overall_ok'] = empty($base['issues']);
        return $base;
    }

    private static function projectRoot(): string
    {
        return realpath(__DIR__ . '/../..') ?: (__DIR__ . '/../..');
    }

    private static function inspectFiles(): array
    {
        $root = self::projectRoot();

        $files = [
            'public/index.php',
            'app/bootstrap.php',
            'app/config/config.php',
            'app/controllers/Settings.php',
            'app/libraries/Controller.php',
            'app/libraries/PlatformDebug.php',
            'app/libraries/FullDebug.php',
            'app/models/Setting.php',
            'app/views/setting/platform_debug.php',
            'public/js/settings.js',
            'public/css/setting.css',
        ];

        $result = [];
        foreach ($files as $relative) {
            $path = $root . '/' . $relative;
            clearstatcache(true, $path);

            $exists = is_file($path);
            $size = $exists ? @filesize($path) : false;
            $size = $size === false ? 0 : (int)$size;
            $readable = $exists && is_readable($path);
            $writable = $exists && is_writable($path);
            $owner = null;
            $group = null;
            $perms = null;
            $sha256 = null;

            if ($exists) {
                $ownerId = @fileowner($path);
                $groupId = @filegroup($path);

                if ($ownerId !== false) {
                    if (function_exists('posix_getpwuid')) {
                        $pw = @posix_getpwuid((int)$ownerId);
                        $owner = is_array($pw) ? ($pw['name'] ?? (string)$ownerId) : (string)$ownerId;
                    } else {
                        $owner = (string)$ownerId;
                    }
                }

                if ($groupId !== false) {
                    if (function_exists('posix_getgrgid')) {
                        $gr = @posix_getgrgid((int)$groupId);
                        $group = is_array($gr) ? ($gr['name'] ?? (string)$groupId) : (string)$groupId;
                    } else {
                        $group = (string)$groupId;
                    }
                }

                $mode = @fileperms($path);
                if ($mode !== false) {
                    $perms = substr(sprintf('%o', $mode), -4);
                }

                if ($readable && $size > 0 && function_exists('hash_file')) {
                    $hash = @hash_file('sha256', $path);
                    $sha256 = $hash === false ? null : $hash;
                }
            }

            $status = ($exists && $size > 0 && $readable) ? 'ok' : 'ng';

            $result[] = [
                'relative_path' => $relative,
                'path' => $path,
                'exists' => $exists,
                'size_bytes' => $size,
                'readable' => $readable,
                'writable' => $writable,
                'owner' => $owner,
                'group' => $group,
                'permissions' => $perms,
                'sha256' => $sha256,
                'status' => $status,
            ];
        }

        return $result;
    }

    private static function inspectDb(string $path): array
    {
        $requiredColumns = [
            'device_sn',
            'device_id',
            'device_name',
            'wifi',
            'modbus_type',
            'mcb_fw_version',
            'device_db_version',
            'device_image_version',
            'device_version',
        ];

        $result = [
            'path' => $path,
            'exists' => is_file($path),
            'readable' => is_readable($path),
            'table' => self::DEVICE_TABLE,
            'table_exists' => false,
            'columns' => [],
            'required_columns' => $requiredColumns,
            'missing_columns' => [],
            'integrity_result' => null,
            'integrity_ok' => null,
            'error' => '',
        ];

        if (!$result['exists'] || !$result['readable']) {
            return $result;
        }

        try {
            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 1500');

            $stmt = $pdo->prepare(
                "SELECT 1 FROM sqlite_master WHERE type='table' AND name=:name LIMIT 1"
            );
            $stmt->execute([':name' => self::DEVICE_TABLE]);
            $result['table_exists'] = (bool)$stmt->fetchColumn();

            if ($result['table_exists']) {
                $rows = $pdo->query(
                    "PRAGMA table_info('" . self::DEVICE_TABLE . "')"
                )->fetchAll(PDO::FETCH_ASSOC);
                $result['columns'] = array_values(array_map(
                    static fn($row) => (string)($row['name'] ?? ''),
                    $rows ?: []
                ));
                $result['missing_columns'] = array_values(array_diff(
                    $requiredColumns,
                    $result['columns']
                ));
            } else {
                $result['missing_columns'] = $requiredColumns;
            }

            $quick = $pdo->query('PRAGMA quick_check')->fetchAll(PDO::FETCH_COLUMN);
            $quick = array_values(array_map('strval', $quick ?: []));
            $result['integrity_result'] = $quick;
            $result['integrity_ok'] = count($quick) === 1 && strtolower(trim($quick[0])) === 'ok';
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
            $result['integrity_ok'] = false;
        }

        return $result;
    }

    private static function inspectDisks(): array
    {
        $targets = [
            ['/var/www/html/idas', false],
            ['/var/www/html/database', true],
            ['/mnt/ramdisk', true],
            ['/mnt/ramdisk/ftp', true],
        ];

        $result = [];
        foreach ($targets as [$path, $writeExpected]) {
            $exists = is_dir($path);
            $total = $exists ? @disk_total_space($path) : false;
            $free = $exists ? @disk_free_space($path) : false;
            $total = $total === false ? null : (int)$total;
            $free = $free === false ? null : (int)$free;
            $used = ($total !== null && $free !== null) ? max(0, $total - $free) : null;
            $freePercent = ($total && $free !== null) ? round(($free / $total) * 100, 2) : null;

            $mounted = null;
            if ($path === '/mnt/ramdisk' && self::canExec()) {
                $out = [];
                $code = 1;
                @exec('/usr/bin/mountpoint -q /mnt/ramdisk 2>/dev/null', $out, $code);
                if ($code === 127 || !is_executable('/usr/bin/mountpoint')) {
                    $mounted = null;
                } else {
                    $mounted = ($code === 0);
                }
            }

            $result[] = [
                'path' => $path,
                'exists' => $exists,
                'writable' => $exists && is_writable($path),
                'write_expected' => $writeExpected,
                'total_bytes' => $total,
                'free_bytes' => $free,
                'used_bytes' => $used,
                'free_percent' => $freePercent,
                'mounted' => $mounted,
            ];
        }

        return $result;
    }

    private static function inspectServices(): array
    {
        $result = [];

        $result[] = self::systemdService('apache2', 'Apache');
        $result[] = self::processService('uvicorn', 'FastAPI / uvicorn', '[u]vicorn');
        $result[] = self::processService('usr_responder', 'usr_responder.py', '[u]sr_responder\.py');

        $ramdisk = [
            'id' => 'ramdisk',
            'name' => 'RAMDISK',
            'status' => is_dir('/mnt/ramdisk') ? 'ok' : 'ng',
            'detail' => is_dir('/mnt/ramdisk') ? '/mnt/ramdisk exists' : '/mnt/ramdisk missing',
        ];
        if (self::canExec() && is_executable('/usr/bin/mountpoint')) {
            $out = [];
            $code = 1;
            @exec('/usr/bin/mountpoint -q /mnt/ramdisk 2>/dev/null', $out, $code);
            $ramdisk['status'] = $code === 0 ? 'ok' : 'warning';
            $ramdisk['detail'] = $code === 0 ? '/mnt/ramdisk mounted' : '/mnt/ramdisk exists but mountpoint check failed';
        }
        $result[] = $ramdisk;

        return $result;
    }

    private static function systemdService(string $service, string $label): array
    {
        if (!self::canExec()) {
            return [
                'id' => $service,
                'name' => $label,
                'status' => 'warning',
                'detail' => 'exec unavailable',
            ];
        }

        $systemctl = is_executable('/usr/bin/systemctl') ? '/usr/bin/systemctl'
            : (is_executable('/bin/systemctl') ? '/bin/systemctl' : '');

        if ($systemctl === '') {
            return [
                'id' => $service,
                'name' => $label,
                'status' => 'warning',
                'detail' => 'systemctl unavailable',
            ];
        }

        $out = [];
        $code = 1;
        @exec(
            escapeshellarg($systemctl) . ' is-active ' . escapeshellarg($service) . ' 2>&1',
            $out,
            $code
        );
        $detail = trim(implode(' ', $out));

        return [
            'id' => $service,
            'name' => $label,
            'status' => $code === 0 ? 'ok' : 'warning',
            'detail' => $detail !== '' ? $detail : ('exit=' . $code),
        ];
    }

    private static function processService(string $id, string $label, string $pattern): array
    {
        if (!self::canExec()) {
            return [
                'id' => $id,
                'name' => $label,
                'status' => 'warning',
                'detail' => 'exec unavailable',
            ];
        }

        $pgrep = is_executable('/usr/bin/pgrep') ? '/usr/bin/pgrep'
            : (is_executable('/bin/pgrep') ? '/bin/pgrep' : '');

        if ($pgrep === '') {
            return [
                'id' => $id,
                'name' => $label,
                'status' => 'warning',
                'detail' => 'pgrep unavailable',
            ];
        }

        $out = [];
        $code = 1;
        @exec(
            escapeshellarg($pgrep) . ' -af ' . escapeshellarg($pattern) . ' 2>&1',
            $out,
            $code
        );

        return [
            'id' => $id,
            'name' => $label,
            'status' => $code === 0 ? 'ok' : 'warning',
            'detail' => $code === 0 ? trim(implode(' | ', $out)) : 'not detected',
        ];
    }

    private static function inspectVersions(): array
    {
        $root = self::projectRoot();

        $info = [];
        $infoPath = $root . '/info.json';
        if (is_file($infoPath) && is_readable($infoPath)) {
            $decoded = json_decode((string)@file_get_contents($infoPath), true);
            if (is_array($decoded)) {
                $info = $decoded;
            }
        }

        $db = self::readDeviceVersionFields(self::CONTROLLER_DB);

        $flagPath = '/home/kls/upgrade/icontroller';
        $flagRaw = is_readable($flagPath) ? trim((string)@file_get_contents($flagPath)) : null;

        $upgradeFiles = [];
        $upgradeDir = '/home/kls/upgrade';
        if (is_dir($upgradeDir) && is_readable($upgradeDir)) {
            $items = @scandir($upgradeDir);
            if (is_array($items)) {
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..') continue;
                    $path = $upgradeDir . '/' . $item;
                    if (is_file($path)) {
                        $upgradeFiles[] = [
                            'name' => $item,
                            'size_bytes' => (int)(@filesize($path) ?: 0),
                            'modified_at' => (($m = @filemtime($path)) !== false) ? date('Y-m-d H:i:s', $m) : null,
                        ];
                    }
                }
            }
        }

        return [
            'platform' => idas_is_icontroller() ? 'i-controller' : 'KL-NTCS',
            'flag_value' => $flagRaw,
            'idas_version' => $info['idas_version'] ?? null,
            'idas_model' => $info['IDAS'] ?? null,
            'device_sn' => $db['device_sn'] ?? null,
            'device_name' => $db['device_name'] ?? null,
            'mcb_fw_version' => $db['mcb_fw_version'] ?? null,
            'device_db_version' => $db['device_db_version'] ?? null,
            'device_image_version' => $db['device_image_version'] ?? null,
            'device_version' => $db['device_version'] ?? null,
            'upgrade_files' => $upgradeFiles,
        ];
    }

    private static function readDeviceVersionFields(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) return [];

        try {
            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $fields = [
                'device_sn',
                'device_name',
                'mcb_fw_version',
                'device_db_version',
                'device_image_version',
                'device_version',
            ];

            $columns = $pdo->query(
                "PRAGMA table_info('" . self::DEVICE_TABLE . "')"
            )->fetchAll(PDO::FETCH_ASSOC);

            $available = array_map(
                static fn($row) => (string)($row['name'] ?? ''),
                $columns ?: []
            );

            $select = array_values(array_intersect($fields, $available));
            if (!$select) return [];

            $quoted = array_map(static fn($f) => '"' . str_replace('"', '""', $f) . '"', $select);
            $row = $pdo->query(
                'SELECT ' . implode(',', $quoted)
                . ' FROM ' . self::DEVICE_TABLE
                . ' ORDER BY rowid DESC LIMIT 1'
            )->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : [];
        } catch (Throwable $e) {
            return [];
        }
    }

    private static function inspectJsSyntax(): array
    {
        $root = self::projectRoot();
        $result = [
            'available' => false,
            'node_binary' => '',
            'checked_count' => 0,
            'ok_count' => 0,
            'ng_count' => 0,
            'failed' => [],
            'error' => '',
        ];

        if (!self::canExec()) {
            $result['error'] = 'exec unavailable';
            return $result;
        }

        $node = '';
        foreach (['/usr/bin/node', '/usr/local/bin/node', '/bin/node'] as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                $node = $candidate;
                break;
            }
        }

        if ($node === '') {
            $result['error'] = 'node binary not found';
            return $result;
        }

        $result['available'] = true;
        $result['node_binary'] = $node;

        $files = [];
        $dir = $root . '/public/js';
        if (is_dir($dir)) {
            try {
                $it = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
                );
                foreach ($it as $fi) {
                    if ($fi->isFile() && strtolower($fi->getExtension()) === 'js') {
                        $files[$fi->getPathname()] = true;
                    }
                }
            } catch (Throwable $e) {
            }
        }

        $paths = array_keys($files);
        sort($paths, SORT_STRING);

        foreach ($paths as $file) {
            $out = [];
            $code = 1;
            @exec(escapeshellarg($node) . ' --check ' . escapeshellarg($file) . ' 2>&1', $out, $code);

            $result['checked_count']++;
            if ($code === 0) {
                $result['ok_count']++;
            } else {
                $result['ng_count']++;
                $result['failed'][] = [
                    'relative_path' => self::relativePath($file, $root),
                    'message' => trim(implode("\n", $out)),
                ];
            }
        }

        return $result;
    }

    private static function inspectBuildManifest(): array
    {
        $path = self::projectRoot() . '/build_manifest.json';
        $result = [
            'available' => false,
            'path' => $path,
            'build_time' => null,
            'source_package' => null,
            'zip_integrity_ok' => null,
            'php_syntax_ok' => null,
            'js_syntax_ok' => null,
            'sha256' => null,
            'error' => '',
        ];

        if (!is_file($path) || !is_readable($path)) {
            $result['error'] = 'build_manifest.json missing or unreadable';
            return $result;
        }

        $decoded = json_decode((string)@file_get_contents($path), true);
        if (!is_array($decoded)) {
            $result['error'] = 'invalid build_manifest.json';
            return $result;
        }

        $result['available'] = true;
        foreach (['build_time','source_package','zip_integrity_ok','php_syntax_ok','js_syntax_ok','sha256'] as $key) {
            if (array_key_exists($key, $decoded)) {
                $result[$key] = $decoded[$key];
            }
        }
        return $result;
    }

    private static function buildCapabilitySummary(array $data): array
    {
        $fileIntegrity = (array)($data['file_integrity'] ?? []);
        $db = (array)($data['db_diagnostics'] ?? []);
        $disks = (array)($data['disks'] ?? []);
        $services = (array)($data['services'] ?? []);
        $logs = (array)($data['logs'] ?? []);
        $versions = (array)($data['versions'] ?? []);
        $syntax = (array)($data['syntax_checks'] ?? []);
        $js = (array)($data['js_syntax'] ?? []);
        $build = (array)($data['build'] ?? []);

        $fileOk = true;
        foreach ($fileIntegrity as $item) {
            if (($item['status'] ?? '') !== 'ok') { $fileOk = false; break; }
        }

        $schemaOk = true;
        $integrityOk = true;
        foreach (['controller', 'idas'] as $key) {
            $diag = (array)($db[$key] ?? []);
            if (empty($diag['table_exists']) || !empty($diag['missing_columns'])) $schemaOk = false;
            if (($diag['integrity_ok'] ?? null) !== true) $integrityOk = false;
        }

        $diskOk = true;
        foreach ($disks as $disk) {
            if (empty($disk['exists'])) { $diskOk = false; break; }
        }

        $serviceStatus = 'ok';
        foreach ($services as $service) {
            $status = (string)($service['status'] ?? 'warning');
            if ($status === 'ng') { $serviceStatus = 'ng'; break; }
            if ($status === 'warning') $serviceStatus = 'warning';
        }

        $logsStatus = 'ok';
        foreach ($logs as $log) {
            if (!empty($log['configured']) && !empty($log['exists']) && empty($log['readable'])) {
                $logsStatus = 'warning'; break;
            }
        }

        $versionOk = !empty($versions['platform']) && array_key_exists('idas_version', $versions);

        $phpSyntax = !empty($syntax['available'])
            ? ((int)($syntax['ng_count'] ?? 0) === 0 ? 'ok' : 'ng')
            : 'warning';

        $jsSyntax = !empty($js['available'])
            ? ((int)($js['ng_count'] ?? 0) === 0 ? 'ok' : 'ng')
            : 'warning';

        $buildStatus = !empty($build['available'])
            ? (!empty($build['zip_integrity_ok']) ? 'ok' : 'ng')
            : 'warning';

        return [
            ['key'=>'file_integrity','status'=>$fileOk ? 'ok' : 'ng'],
            ['key'=>'db_schema','status'=>$schemaOk ? 'ok' : 'ng'],
            ['key'=>'db_quick_check','status'=>$integrityOk ? 'ok' : 'ng'],
            ['key'=>'disk_ramdisk','status'=>$diskOk ? 'ok' : 'ng'],
            ['key'=>'controller_test','status'=>'not_tested'],
            ['key'=>'service_status','status'=>$serviceStatus],
            ['key'=>'recent_logs','status'=>$logsStatus],
            ['key'=>'version_image','status'=>$versionOk ? 'ok' : 'ng'],
            ['key'=>'download_report','status'=>'available'],
            ['key'=>'readonly_mode','status'=>'ok'],
            ['key'=>'emergency_syntax','status'=>'available'],
            ['key'=>'language_support','status'=>'languages'],
            ['key'=>'php_syntax','status'=>$phpSyntax],
            ['key'=>'js_syntax','status'=>$jsSyntax],
            ['key'=>'build_zip','status'=>$buildStatus],
        ];
    }

    private static function inspectLogs(): array
    {
        $phpErrorLog = trim((string)ini_get('error_log'));

        $targets = [
            ['PHP Error Log', $phpErrorLog],
            ['Apache Error Log', '/var/log/apache2/error.log'],
            ['Syslog', '/var/log/syslog'],
            ['iDAS App Log', '/var/www/html/idas/app/log/logfile.log'],
            ['iDAS Log', '/var/www/html/idas/log/logfile.log'],
        ];

        $result = [];
        foreach ($targets as [$name, $path]) {
            $configured = $path !== '' && !in_array(strtolower($path), ['syslog', 'stderr'], true);
            $exists = $configured && is_file($path);
            $readable = $exists && is_readable($path);
            $lines = [];
            $error = '';

            if ($readable) {
                try {
                    $lines = self::tailLines($path, 30, 131072);
                } catch (Throwable $e) {
                    $error = $e->getMessage();
                }
            }

            $result[] = [
                'name' => $name,
                'path' => $path,
                'configured' => $configured,
                'exists' => $exists,
                'readable' => $readable,
                'size_bytes' => $exists ? (int)(@filesize($path) ?: 0) : 0,
                'modified_at' => ($exists && ($m = @filemtime($path)) !== false) ? date('Y-m-d H:i:s', $m) : null,
                'lines' => $lines,
                'error' => $error,
            ];
        }

        return $result;
    }

    private static function tailLines(string $path, int $maxLines, int $maxBytes): array
    {
        $size = @filesize($path);
        if ($size === false || $size <= 0) return [];

        $bytes = min((int)$size, $maxBytes);
        $fp = @fopen($path, 'rb');
        if (!$fp) throw new RuntimeException('Unable to open log.');

        try {
            if ($bytes < $size) fseek($fp, -$bytes, SEEK_END);
            $data = (string)fread($fp, $bytes);
        } finally {
            fclose($fp);
        }

        $lines = preg_split('/\r\n|\r|\n/', $data) ?: [];
        $lines = array_values(array_filter($lines, static fn($v) => trim((string)$v) !== ''));
        return count($lines) > $maxLines ? array_slice($lines, -$maxLines) : $lines;
    }

    private static function canExec(): bool
    {
        if (!function_exists('exec')) return false;
        $disabled = array_filter(array_map(
            'trim',
            explode(',', (string)ini_get('disable_functions'))
        ));
        return !in_array('exec', $disabled, true);
    }
}
