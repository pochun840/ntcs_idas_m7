<?php

/**
 * Read-only platform / protocol / DB diagnostic collector.
 *
 * IMPORTANT:
 * - This class NEVER writes to DB.
 * - It NEVER triggers Controller/iDAS synchronization.
 * - It is intended for the hidden guest-accessible maintenance page:
 *   ?url=Settings/platform_debug
 */
class PlatformDebug
{
    private const FLAG_FILE = IDAS_PATH_UPGRADE_ROOT . '/icontroller';
    private const CONTROLLER_DB = IDAS_PATH_CONTROLLER_ROOT . '/ntcs_device.db';
    private const IDAS_DB = IDAS_PATH_DATABASE_ROOT . '/ntcs_device_IDAS.db';
    private const DEVICE_TABLE = 'ntcs_device_test';

    public static function collect(): array
    {
        $flag = self::inspectFlag();
        $controller = self::inspectDeviceDb(self::CONTROLLER_DB);
        $idas = self::inspectDeviceDb(self::IDAS_DB);
        $php = self::inspectPhpRuntime();
        $phpErrors = self::inspectPhpErrorLog($php);
        $functionChecks = self::inspectReadOnlyFunctions($flag, $controller, $idas, $php);
        $syntaxChecks = self::inspectPhpSyntax();

        $issues = [];
        $warnings = [];

        if (!$flag['exists']) {
            $warnings[] = 'Platform flag file is missing; system safely defaults to NTCS.';
        } elseif (!$flag['readable']) {
            $warnings[] = 'Platform flag file is not readable; system safely defaults to NTCS.';
        } elseif (!$flag['valid']) {
            $warnings[] = 'Platform flag value is invalid; system safely defaults to NTCS.';
        }

        foreach (['pdo', 'pdo_sqlite', 'sqlite3', 'json'] as $requiredExtension) {
            if (empty($php['extensions'][$requiredExtension])) {
                $issues[] = 'Required PHP extension is missing: ' . $requiredExtension;
            }
        }

        foreach (['curl', 'mbstring', 'openssl'] as $recommendedExtension) {
            if (empty($php['extensions'][$recommendedExtension])) {
                $warnings[] = 'Recommended PHP extension is missing: ' . $recommendedExtension;
            }
        }

        if (!empty($phpErrors['recent_errors'])) {
            $warnings[] = 'Recent PHP errors were found in the configured error log.';
        }

        foreach ($functionChecks as $check) {
            if (($check['status'] ?? '') === 'ng') {
                $issues[] = 'Function/component check failed: ' . ($check['name'] ?? 'Unknown');
            } elseif (($check['status'] ?? '') === 'warning') {
                $warnings[] = 'Function/component warning: ' . ($check['name'] ?? 'Unknown');
            }
        }

        foreach (($syntaxChecks['failed'] ?? []) as $syntaxFailure) {
            $issues[] = 'PHP syntax error: '
                . ($syntaxFailure['relative_path'] ?? $syntaxFailure['path'] ?? 'Unknown')
                . (!empty($syntaxFailure['line']) ? ' (line ' . $syntaxFailure['line'] . ')' : '');
        }

        if (!empty($syntaxChecks['error'])) {
            $warnings[] = 'PHP Syntax Check unavailable: ' . $syntaxChecks['error'];
        }

        foreach ([
            'Controller DB' => $controller,
            'iDAS DB' => $idas,
        ] as $label => $db) {
            if (!$db['exists']) {
                $issues[] = $label . ' does not exist.';
                continue;
            }
            if (($db['size_bytes'] ?? 0) <= 0) {
                $issues[] = $label . ' size is 0 bytes.';
            }
            if (!$db['readable']) {
                $issues[] = $label . ' is not readable.';
            }
            if (!$db['sqlite_open']) {
                $issues[] = $label . ' cannot be opened as SQLite.'
                    . ($db['error'] !== '' ? ' (' . $db['error'] . ')' : '');
                continue;
            }
            if (!$db['table_exists']) {
                $issues[] = $label . ' is missing table ' . self::DEVICE_TABLE . '.';
                continue;
            }
            if (!$db['row_exists']) {
                $issues[] = $label . ' has no row in ' . self::DEVICE_TABLE . '.';
            }
        }

        $controllerDeviceId = $controller['device_id'];
        $idasDeviceId = $idas['device_id'];
        $controllerProtocol = $controller['modbus_type'];
        $idasProtocol = $idas['modbus_type'];
        $controllerPort = $controller['server_port'];
        $idasPort = $idas['server_port'];

        $deviceIdSync = self::sameNullable($controllerDeviceId, $idasDeviceId);
        $modbusTypeSync = self::sameNullable($controllerProtocol, $idasProtocol);
        $portSync = self::sameNullable($controllerPort, $idasPort);

        if ($controller['row_exists'] && $idas['row_exists']) {
            if (!$deviceIdSync) {
                $issues[] = 'Controller DB / iDAS DB Device ID mismatch.';
            }
            if (!$modbusTypeSync) {
                $issues[] = 'Controller DB / iDAS DB modbus_type mismatch.';
            }
            if (!$portSync) {
                $issues[] = 'Controller DB / iDAS DB Server Port mismatch.';
            }
        }

        $activeProtocol = $controllerProtocol !== null ? $controllerProtocol : $idasProtocol;
        $activePort = $controllerPort !== null ? $controllerPort : $idasPort;
        $expectedPort = null;
        $protocolPortOk = null;

        if ($activeProtocol !== null) {
            $expectedPort = idas_protocol_server_port((int)$activeProtocol, $activePort);
            if ($expectedPort !== null && $activePort !== null) {
                $protocolPortOk = ((int)$activePort === (int)$expectedPort);
            }

            if ($protocolPortOk === false) {
                $issues[] = sprintf(
                    '%s requires Server Port %d, current port is %s.',
                    self::protocolName($activeProtocol),
                    (int)$expectedPort,
                    $activePort === null ? 'N/A' : (string)$activePort
                );
            }
        }

        $runtimePlatform = idas_is_icontroller() ? 'i-controller' : 'KL-NTCS';
        $runtimeFlag = idas_is_icontroller() ? 1 : 0;

        return [
            'generated_at' => date('Y-m-d H:i:s'),
            'platform' => [
                'name' => $runtimePlatform,
                'id' => defined('IDAS_PLATFORM') ? (string)IDAS_PLATFORM : ($runtimeFlag ? 'icontroller' : 'ntcs'),
                'runtime_flag' => $runtimeFlag,
                'flag_file' => $flag,
            ],
            'controller' => [
                'device_id' => $controllerDeviceId,
                'modbus_type' => $controllerProtocol,
                'protocol' => self::protocolName($controllerProtocol),
                'server_port' => $controllerPort,
                'wifi_raw' => $controller['wifi_raw'],
            ],
            'idas' => [
                'device_id' => $idasDeviceId,
                'modbus_type' => $idasProtocol,
                'protocol' => self::protocolName($idasProtocol),
                'server_port' => $idasPort,
                'wifi_raw' => $idas['wifi_raw'],
            ],
            'database' => [
                'controller' => $controller,
                'idas' => $idas,
            ],
            'php' => $php,
            'php_errors' => $phpErrors,
            'function_checks' => $functionChecks,
            'syntax_checks' => $syntaxChecks,
            'consistency' => [
                'device_id' => $deviceIdSync,
                'modbus_type' => $modbusTypeSync,
                'server_port' => $portSync,
                'protocol_port' => $protocolPortOk,
                'expected_port' => $expectedPort,
            ],
            'issues' => array_values(array_unique($issues)),
            'warnings' => array_values(array_unique($warnings)),
            'overall_ok' => count($issues) === 0,
        ];
    }

    public static function inspectPhpSyntax(?string $projectRoot = null): array
    {
        if ($projectRoot === null || $projectRoot === '') {
            // PlatformDebug.php -> app/libraries -> project root
            $projectRoot = realpath(__DIR__ . '/../..') ?: (__DIR__ . '/../..');
        }

        $projectRoot = rtrim((string)$projectRoot, DIRECTORY_SEPARATOR);
        $phpBinary = self::resolvePhpCliBinary();

        $result = [
            'project_root' => $projectRoot,
            'php_binary' => $phpBinary,
            'available' => false,
            'checked_count' => 0,
            'ok_count' => 0,
            'ng_count' => 0,
            'passed' => [],
            'failed' => [],
            'error' => '',
        ];

        if ($phpBinary === '') {
            $result['error'] = 'PHP CLI binary was not found or is not executable.';
            return $result;
        }

        if (!function_exists('exec')) {
            $result['error'] = 'PHP exec() is not available.';
            return $result;
        }

        $disabledFunctions = array_filter(array_map(
            'trim',
            explode(',', (string)ini_get('disable_functions'))
        ));

        if (in_array('exec', $disabledFunctions, true)) {
            $result['error'] = 'PHP exec() is disabled.';
            return $result;
        }

        $result['available'] = true;

        $files = self::collectPhpFiles($projectRoot);

        foreach ($files as $path) {
            $output = [];
            $exitCode = 1;
            $command = escapeshellarg($phpBinary)
                . ' -n -l '
                . escapeshellarg($path)
                . ' 2>&1';

            exec($command, $output, $exitCode);

            $message = trim(implode("\n", $output));
            $relativePath = self::relativePath($path, $projectRoot);

            $entry = [
                'path' => $path,
                'relative_path' => $relativePath,
                'ok' => ($exitCode === 0),
                'exit_code' => $exitCode,
                'line' => self::extractPhpErrorLine($message),
                'message' => $message,
            ];

            $result['checked_count']++;

            if ($entry['ok']) {
                $result['ok_count']++;
                $result['passed'][] = $entry;
            } else {
                $result['ng_count']++;
                $result['failed'][] = $entry;
            }
        }

        return $result;
    }

    private static function collectPhpFiles(string $projectRoot): array
    {
        $files = [];

        $priorityFiles = [
            $projectRoot . '/index.php',
            $projectRoot . '/app/bootstrap.php',
            $projectRoot . '/app/config/config.php',
        ];

        foreach ($priorityFiles as $file) {
            if (is_file($file)) {
                $files[$file] = true;
            }
        }

        $scanDirs = [
            $projectRoot . '/app/controllers',
            $projectRoot . '/app/models',
            $projectRoot . '/app/libraries',
            $projectRoot . '/app/views',
            $projectRoot . '/api',
        ];

        foreach ($scanDirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            try {
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator(
                        $dir,
                        FilesystemIterator::SKIP_DOTS
                    )
                );

                foreach ($iterator as $fileInfo) {
                    if (!$fileInfo->isFile()) {
                        continue;
                    }

                    if (strtolower($fileInfo->getExtension()) !== 'php') {
                        continue;
                    }

                    $path = $fileInfo->getPathname();
                    $files[$path] = true;
                }
            } catch (Throwable $e) {
                // Keep best-effort behavior. Existing files will still be checked.
            }
        }

        $paths = array_keys($files);
        sort($paths, SORT_STRING);
        return $paths;
    }

    private static function resolvePhpCliBinary(): string
    {
        $candidates = [];

        if (defined('PHP_BINARY') && PHP_BINARY !== '') {
            $candidates[] = PHP_BINARY;
        }

        $candidates[] = '/usr/bin/php';
        $candidates[] = '/usr/local/bin/php';
        $candidates[] = '/bin/php';

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private static function extractPhpErrorLine(string $message): ?int
    {
        if ($message === '') {
            return null;
        }

        if (preg_match('/\bon line\s+(\d+)\b/i', $message, $m)) {
            return (int)$m[1];
        }

        if (preg_match('/:\s*(\d+)\s*$/m', $message, $m)) {
            return (int)$m[1];
        }

        return null;
    }

    private static function relativePath(string $path, string $root): string
    {
        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strpos($path, $root) === 0) {
            return substr($path, strlen($root));
        }
        return $path;
    }

    private static function inspectPhpErrorLog(array $php): array
    {
        $path = trim((string)($php['error_log'] ?? ''));

        $result = [
            'path' => $path,
            'exists' => false,
            'readable' => false,
            'size_bytes' => 0,
            'modified_at' => null,
            'recent_errors' => [],
            'error' => '',
        ];

        // Some installations use syslog/stderr or an empty setting.
        if ($path === '' || strtolower($path) === 'syslog' || strtolower($path) === 'stderr') {
            $result['error'] = $path === ''
                ? 'error_log path is not configured as a readable file.'
                : 'error_log is configured as ' . $path . ', not a regular file.';
            return $result;
        }

        clearstatcache(true, $path);
        $result['exists'] = is_file($path);

        if (!$result['exists']) {
            $result['error'] = 'Configured PHP error log file does not exist.';
            return $result;
        }

        $result['readable'] = is_readable($path);
        $size = @filesize($path);
        $result['size_bytes'] = $size === false ? 0 : (int)$size;
        $mtime = @filemtime($path);
        $result['modified_at'] = $mtime === false ? null : date('Y-m-d H:i:s', (int)$mtime);

        if (!$result['readable']) {
            $result['error'] = 'Configured PHP error log file is not readable.';
            return $result;
        }

        try {
            $lines = self::tailFileLines($path, 200, 262144);
            $patterns = [
                'fatal error',
                'uncaught ',
                'pdoexception',
                'typeerror',
                'argumentcounterror',
                'parse error',
                'warning:',
                'notice:',
                'deprecated:',
            ];

            $matched = [];
            foreach ($lines as $line) {
                $lower = strtolower($line);
                foreach ($patterns as $pattern) {
                    if (strpos($lower, $pattern) !== false) {
                        $matched[] = trim($line);
                        break;
                    }
                }
            }

            // Show the newest 20 matched lines.
            $result['recent_errors'] = array_slice($matched, -20);
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    private static function inspectReadOnlyFunctions(
        array $flag,
        array $controller,
        array $idas,
        array $php
    ): array {
        $checks = [];

        $add = static function (array &$checks, string $name, string $status, string $detail = ''): void {
            $checks[] = [
                'name' => $name,
                'status' => $status,
                'detail' => $detail,
            ];
        };

        try {
            $platform = idas_is_icontroller() ? 'i-controller' : 'KL-NTCS';
            $add($checks, 'idas_is_icontroller()', 'ok', $platform);
        } catch (Throwable $e) {
            $add($checks, 'idas_is_icontroller()', 'ng', $e->getMessage());
        }

        try {
            $enabled = idas_network_settings_enabled();
            $add($checks, 'idas_network_settings_enabled()', 'ok', $enabled ? 'true' : 'false');
        } catch (Throwable $e) {
            $add($checks, 'idas_network_settings_enabled()', 'ng', $e->getMessage());
        }

        try {
            $tcpPort = idas_protocol_server_port(IDAS_PROTOCOL_TCP, null);
            $opPort = idas_protocol_server_port(IDAS_PROTOCOL_OP, null);
            $rtuPort = idas_protocol_server_port(IDAS_PROTOCOL_RTU, 1234);

            $policyOk = ((int)$tcpPort === IDAS_SERVER_PORT_TCP)
                && ((int)$opPort === IDAS_SERVER_PORT_OP)
                && ((int)$rtuPort === 1234);

            $add(
                $checks,
                'idas_protocol_server_port()',
                $policyOk ? 'ok' : 'ng',
                sprintf('TCP=%s, OP=%s, RTU-preserve=%s', $tcpPort, $opPort, $rtuPort)
            );
        } catch (Throwable $e) {
            $add($checks, 'idas_protocol_server_port()', 'ng', $e->getMessage());
        }

        $add(
            $checks,
            'Platform Flag Reader',
            (!empty($flag['exists']) && !empty($flag['readable']) && !empty($flag['valid'])) ? 'ok' : 'warning',
            'resolved=' . (string)($flag['resolved_value'] ?? 'N/A')
        );

        $pdoOk = !empty($php['extensions']['pdo']);
        $sqlitePdoOk = !empty($php['extensions']['pdo_sqlite']);
        $sqlite3Ok = !empty($php['extensions']['sqlite3']);

        $add($checks, 'PDO Extension', $pdoOk ? 'ok' : 'ng', $pdoOk ? 'loaded' : 'missing');
        $add($checks, 'PDO SQLite Extension', $sqlitePdoOk ? 'ok' : 'ng', $sqlitePdoOk ? 'loaded' : 'missing');
        $add($checks, 'SQLite3 Extension', $sqlite3Ok ? 'ok' : 'ng', $sqlite3Ok ? 'loaded' : 'missing');

        $controllerDbOk = !empty($controller['sqlite_open'])
            && !empty($controller['table_exists'])
            && !empty($controller['row_exists']);
        $idasDbOk = !empty($idas['sqlite_open'])
            && !empty($idas['table_exists'])
            && !empty($idas['row_exists']);

        $add(
            $checks,
            'Controller DB Read',
            $controllerDbOk ? 'ok' : 'ng',
            (string)($controller['path'] ?? '')
        );

        $add(
            $checks,
            'iDAS DB Read',
            $idasDbOk ? 'ok' : 'ng',
            (string)($idas['path'] ?? '')
        );

        if (($controller['modbus_type'] ?? null) !== null && ($controller['server_port'] ?? null) !== null) {
            try {
                $expected = idas_protocol_server_port(
                    (int)$controller['modbus_type'],
                    (int)$controller['server_port']
                );
                $ok = $expected === null || (int)$expected === (int)$controller['server_port'];
                $add(
                    $checks,
                    'Protocol / Port Policy',
                    $ok ? 'ok' : 'ng',
                    'actual=' . (string)$controller['server_port']
                    . ', expected=' . ($expected === null ? 'N/A' : (string)$expected)
                );
            } catch (Throwable $e) {
                $add($checks, 'Protocol / Port Policy', 'ng', $e->getMessage());
            }
        } else {
            $add($checks, 'Protocol / Port Policy', 'warning', 'Controller protocol/port is unavailable.');
        }

        return $checks;
    }

    private static function tailFileLines(string $path, int $maxLines = 200, int $maxBytes = 262144): array
    {
        $size = @filesize($path);
        if ($size === false || $size <= 0) {
            return [];
        }

        $readBytes = min((int)$size, $maxBytes);
        $fp = @fopen($path, 'rb');
        if (!$fp) {
            throw new RuntimeException('Unable to open error log.');
        }

        try {
            if ($readBytes < $size) {
                fseek($fp, -$readBytes, SEEK_END);
            }
            $data = (string)fread($fp, $readBytes);
        } finally {
            fclose($fp);
        }

        $lines = preg_split('/\r\n|\r|\n/', $data) ?: [];
        if (count($lines) > $maxLines) {
            $lines = array_slice($lines, -$maxLines);
        }

        return array_values(array_filter($lines, static fn($line) => trim((string)$line) !== ''));
    }

    private static function inspectPhpRuntime(): array
    {
        $loadedIni = php_ini_loaded_file();
        $scannedIni = php_ini_scanned_files();

        $extensions = [];
        foreach (['pdo', 'pdo_sqlite', 'sqlite3', 'curl', 'mbstring', 'json', 'openssl'] as $ext) {
            $extensions[$ext] = extension_loaded($ext);
        }

        $errorReporting = error_reporting();

        return [
            'version' => PHP_VERSION,
            'version_id' => PHP_VERSION_ID,
            'sapi' => PHP_SAPI,
            'os' => PHP_OS_FAMILY,
            'os_full' => PHP_OS,
            'architecture' => PHP_INT_SIZE === 8 ? '64-bit' : '32-bit',
            'server_software' => (string)($_SERVER['SERVER_SOFTWARE'] ?? ''),
            'document_root' => (string)($_SERVER['DOCUMENT_ROOT'] ?? ''),
            'script_filename' => (string)($_SERVER['SCRIPT_FILENAME'] ?? ''),
            'loaded_ini' => $loadedIni !== false ? $loadedIni : '',
            'scanned_ini' => $scannedIni !== false ? trim((string)$scannedIni) : '',
            'display_errors' => (string)ini_get('display_errors'),
            'log_errors' => (string)ini_get('log_errors'),
            'error_log' => (string)ini_get('error_log'),
            'error_reporting' => $errorReporting,
            'error_reporting_hex' => '0x' . strtoupper(dechex((int)$errorReporting)),
            'memory_limit' => (string)ini_get('memory_limit'),
            'max_execution_time' => (string)ini_get('max_execution_time'),
            'max_input_time' => (string)ini_get('max_input_time'),
            'upload_max_filesize' => (string)ini_get('upload_max_filesize'),
            'post_max_size' => (string)ini_get('post_max_size'),
            'max_file_uploads' => (string)ini_get('max_file_uploads'),
            'default_socket_timeout' => (string)ini_get('default_socket_timeout'),
            'timezone' => (string)date_default_timezone_get(),
            'extensions' => $extensions,
        ];
    }

    private static function inspectFlag(): array
    {
        $path = self::FLAG_FILE;
        $exists = is_file($path);
        $readable = $exists && is_readable($path);
        $raw = null;

        if ($readable) {
            $value = @file_get_contents($path);
            if ($value !== false) {
                $raw = trim((string)$value);
            }
        }

        return [
            'path' => $path,
            'exists' => $exists,
            'readable' => $readable,
            'raw_value' => $raw,
            'valid' => in_array($raw, ['0', '1'], true),
            'resolved_value' => idas_is_icontroller() ? 1 : 0,
        ];
    }

    private static function inspectDeviceDb(string $path): array
    {
        clearstatcache(true, $path);

        $result = [
            'path' => $path,
            'exists' => is_file($path),
            'size_bytes' => 0,
            'size_human' => '0 B',
            'readable' => false,
            'writable' => false,
            'sqlite_open' => false,
            'table_exists' => false,
            'row_exists' => false,
            'columns' => [],
            'device_id' => null,
            'modbus_type' => null,
            'wifi_raw' => null,
            'network_mode' => null,
            'static_ip' => null,
            'server_port' => null,
            'mask' => null,
            'gateway' => null,
            'error' => '',
        ];

        if (!$result['exists']) {
            return $result;
        }

        $size = @filesize($path);
        $result['size_bytes'] = $size === false ? 0 : (int)$size;
        $result['size_human'] = self::formatBytes($result['size_bytes']);
        $result['readable'] = is_readable($path);
        $result['writable'] = is_writable($path);

        if (!$result['readable'] || $result['size_bytes'] <= 0) {
            return $result;
        }

        try {
            $pdo = idas_sqlite_connect($path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA busy_timeout = 1500');
            $result['sqlite_open'] = true;

            $stmt = $pdo->prepare(
                "SELECT 1 FROM sqlite_master WHERE type='table' AND name=:name LIMIT 1"
            );
            $stmt->execute([':name' => self::DEVICE_TABLE]);
            $result['table_exists'] = (bool)$stmt->fetchColumn();

            if (!$result['table_exists']) {
                return $result;
            }

            $columnRows = $pdo->query(
                "PRAGMA table_info('" . self::DEVICE_TABLE . "')"
            )->fetchAll(PDO::FETCH_ASSOC);

            $result['columns'] = array_values(array_map(
                static fn(array $row): string => (string)($row['name'] ?? ''),
                $columnRows ?: []
            ));

            $row = $pdo->query(
                'SELECT * FROM ' . self::DEVICE_TABLE . ' ORDER BY rowid ASC LIMIT 1'
            )->fetch(PDO::FETCH_ASSOC);

            if (!is_array($row) || !$row) {
                return $result;
            }

            $result['row_exists'] = true;
            $result['device_id'] = self::firstField($row, ['device_id', 'control_id', 'controller_id']);
            $result['modbus_type'] = self::toNullableInt(
                self::firstField($row, ['modbus_type', 'protocol_type'])
            );
            $result['wifi_raw'] = array_key_exists('wifi', $row) ? (string)$row['wifi'] : null;

            $wifi = self::parseWifi($result['wifi_raw']);
            $result['network_mode'] = $wifi['mode'];
            $result['static_ip'] = $wifi['static_ip'];
            $result['server_port'] = $wifi['port'];
            $result['mask'] = $wifi['mask'];
            $result['gateway'] = $wifi['gateway'];
        } catch (Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    private static function parseWifi($wifi): array
    {
        $out = [
            'mode' => null,
            'static_ip' => null,
            'port' => null,
            'mask' => null,
            'gateway' => null,
        ];

        if ($wifi === null) {
            return $out;
        }

        $parts = explode('_', (string)$wifi);

        $out['mode'] = isset($parts[0]) && $parts[0] !== ''
            ? self::toNullableInt($parts[0])
            : null;
        $out['static_ip'] = isset($parts[1]) && $parts[1] !== '' ? (string)$parts[1] : null;
        $out['port'] = isset($parts[2]) && $parts[2] !== ''
            ? self::toNullableInt($parts[2])
            : null;
        $out['mask'] = isset($parts[3]) && $parts[3] !== '' ? (string)$parts[3] : null;
        $out['gateway'] = isset($parts[4]) && $parts[4] !== '' ? (string)$parts[4] : null;

        return $out;
    }

    private static function protocolName($protocol): string
    {
        if ($protocol === null || $protocol === '') {
            return 'N/A';
        }

        switch ((int)$protocol) {
            case IDAS_PROTOCOL_TCP:
                return 'TCP';
            case IDAS_PROTOCOL_RTU:
                return 'RTU';
            case IDAS_PROTOCOL_OP:
                return 'OP';
            default:
                return 'Unknown (' . (int)$protocol . ')';
        }
    }

    private static function sameNullable($a, $b): ?bool
    {
        if ($a === null || $b === null) {
            return null;
        }

        return (string)$a === (string)$b;
    }

    private static function firstField(array $row, array $keys)
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }
        return null;
    }

    private static function toNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        return (int)$value;
    }

    private static function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        $kb = $bytes / 1024;
        if ($kb < 1024) {
            return number_format($kb, 1) . ' KB';
        }

        return number_format($kb / 1024, 2) . ' MB';
    }
}
