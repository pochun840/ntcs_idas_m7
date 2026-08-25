<?php

require_once dirname(__DIR__) . '/app/config/paths.php';
require_once __DIR__ . '/DiskSpaceService.php';

/**
 * Shared DB backup, verification and safe-cleanup implementation.
 * Production DB files and failed/pending update evidence are never deleted.
 */
class DatabaseBackupService
{
    public const DEFAULT_BACKUP_KEEP = 5;
    public const DEFAULT_BACKUP_MAX_AGE_DAYS = 5;
    public const SUCCESS_BACKUP_MAX_AGE_DAYS = 5;
    public const FAILED_BACKUP_MAX_AGE_DAYS = 5;
    public const TEMP_BACKUP_MAX_AGE_DAYS = 5;

    public static function backupDirectory(): string
    {
        return idas_path('database_root', 'update_backups');
    }

    public static function cleanupUpdateBackups(
        string $backupDir,
        int $keep = self::DEFAULT_BACKUP_KEEP,
        int $maxAgeDays = self::DEFAULT_BACKUP_MAX_AGE_DAYS
    ): int {
        if (PHP_OS_FAMILY !== 'Linux' || !is_dir($backupDir)) {
            return 0;
        }

        $files = glob(rtrim($backupDir, '/') . '/idas_backup_before_update_*.zip') ?: [];
        $files = array_values(array_filter($files, static function (string $path): bool {
            $name = basename($path);
            return strpos($name, '.failed') === false && strpos($name, '.pending') === false;
        }));
        usort($files, static function (string $a, string $b): int {
            return (int)@filemtime($b) <=> (int)@filemtime($a);
        });

        $deleted = 0;
        $cutoff = time() - max(1, $maxAgeDays) * 86400;
        foreach ($files as $index => $path) {
            if ($index === 0) {
                continue;
            }
            $tooMany = $index >= max(1, $keep);
            $tooOld = (int)@filemtime($path) < $cutoff;
            if (($tooMany || $tooOld) && is_file($path) && @unlink($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public static function removeUpdateTempDirectory(string $path): bool
    {
        $base = IDAS_PATH_DATABASE_ROOT . '/';
        $normalized = rtrim(str_replace('\\', '/', $path), '/') . '/';
        if (strpos($normalized, $base . '.idas_update_rollback_') !== 0 || !is_dir($path)) {
            return false;
        }

        $items = @scandir($path);
        if (!is_array($items)) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $child = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . $item;
            if (is_dir($child) && !is_link($child)) {
                if (!self::removeUpdateTempDirectory($child)) {
                    return false;
                }
            } elseif (!@unlink($child)) {
                return false;
            }
        }

        return @rmdir($path);
    }

    public static function safeCleanup(bool $aggressive = false, bool $updateLockOwned = false): array
    {
        $dir = IDAS_PATH_DATABASE_ROOT;
        $before = DiskSpaceService::databaseFreeBytes();
        $result = [
            'ok' => true,
            'busy' => false,
            'free_bytes_before' => $before,
            'free_bytes' => $before,
            'freed_bytes' => 0,
            'actions' => [],
            'pending_marked_failed' => 0,
        ];

        if (PHP_OS_FAMILY !== 'Linux' || !is_dir($dir) || !is_writable($dir)) {
            return $result;
        }

        $updateGuard = null;
        if (!$updateLockOwned) {
            $updateGuard = @fopen($dir . '/.idas_update.lock', 'c');
            if (!$updateGuard || !@flock($updateGuard, LOCK_EX | LOCK_NB)) {
                if ($updateGuard) {
                    @fclose($updateGuard);
                }
                $result['ok'] = false;
                $result['busy'] = true;
                return $result;
            }
        }

        $lock = @fopen($dir . '/.idas_database_cleanup.lock', 'c');
        if (!$lock || !@flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) {
                @fclose($lock);
            }
            if ($updateGuard) {
                @flock($updateGuard, LOCK_UN);
                @fclose($updateGuard);
            }
            $result['ok'] = false;
            $result['busy'] = true;
            return $result;
        }

        try {
            $deletedBackups = self::cleanupUpdateBackups(
                $dir . '/update_backups',
                $aggressive ? 1 : self::DEFAULT_BACKUP_KEEP,
                self::DEFAULT_BACKUP_MAX_AGE_DAYS
            );
            if ($deletedBackups > 0) {
                $result['actions'][] = 'successful_backups:' . $deletedBackups;
            }

            $failedCutoff = time() - self::FAILED_BACKUP_MAX_AGE_DAYS * 86400;
            foreach (glob($dir . '/update_backups/*.pending.zip') ?: [] as $pending) {
                if ((int)@filemtime($pending) >= $failedCutoff) {
                    continue;
                }
                $failed = substr($pending, 0, -strlen('.pending.zip')) . '.failed-stale.zip';
                if (!file_exists($failed) && @rename($pending, $failed)) {
                    $result['pending_marked_failed']++;
                    $result['actions'][] = 'pending_marked_failed:' . basename($failed);
                }
            }

            foreach (glob($dir . '/update_backups/*.failed*.zip') ?: [] as $failed) {
                if ((int)@filemtime($failed) < $failedCutoff && @unlink($failed)) {
                    $result['actions'][] = 'stale_failed_backup:' . basename($failed);
                }
            }

            $generations = $aggressive ? [3, 2, 1] : [3];
            foreach (['idas_icontroller_reboot.log', 'idas_update.log', 'lin_refresh.log'] as $logName) {
                foreach ($generations as $generation) {
                    $path = $dir . '/' . $logName . '.' . $generation;
                    if (is_file($path) && @unlink($path)) {
                        $result['actions'][] = 'log:' . basename($path);
                    }
                }
            }

            $tempCutoff = time() - self::TEMP_BACKUP_MAX_AGE_DAYS * 86400;
            foreach ([$dir . '/*.tmp.*', $dir . '/.*.tmp.*', $dir . '/.*.preflight_*'] as $pattern) {
                foreach (glob($pattern) ?: [] as $path) {
                    if (is_file($path) && (int)@filemtime($path) < $tempCutoff && @unlink($path)) {
                        $result['actions'][] = 'stale_temp:' . basename($path);
                    }
                }
            }

            foreach (glob($dir . '/.idas_update_rollback_*', GLOB_ONLYDIR) ?: [] as $rollbackDir) {
                if ((int)@filemtime($rollbackDir) < $tempCutoff && self::removeUpdateTempDirectory($rollbackDir)) {
                    $result['actions'][] = 'stale_rollback:' . basename($rollbackDir);
                }
            }

            clearstatcache();
            $result['free_bytes'] = DiskSpaceService::databaseFreeBytes();
            $result['freed_bytes'] = max(0, $result['free_bytes'] - $before);
            @touch($dir . '/.idas_database_maintenance.stamp');
            @chmod($dir . '/.idas_database_maintenance.stamp', 0660);

            $logLine = date('Y-m-d H:i:s') . ' [DB CLEANUP] mode=' . ($aggressive ? 'aggressive' : 'normal')
                . ' before=' . $before . ' after=' . $result['free_bytes']
                . ' freed=' . $result['freed_bytes'] . ' actions=' . implode(',', $result['actions']) . PHP_EOL;
            @file_put_contents($dir . '/idas_update.log', $logLine, FILE_APPEND | LOCK_EX);
            @chmod($dir . '/idas_update.log', 0660);

            return $result;
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
            if ($updateGuard) {
                @flock($updateGuard, LOCK_UN);
                @fclose($updateGuard);
            }
        }
    }

    public static function quickCheckProductionDatabases(): array
    {
        $dir = PHP_OS_FAMILY === 'Linux' ? IDAS_PATH_DATABASE_ROOT : dirname(__DIR__, 2);
        $candidates = ['das.db', 'KLS_NTCS_IDAS.Lin', 'ntcs_barcode_IDAS.db', 'ntcs_device_IDAS.db', 'ntcs_data.db'];
        $checked = [];
        $errors = [];

        foreach ($candidates as $name) {
            $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $name;
            if (!is_file($path)) {
                continue;
            }
            try {
                $pdo = new PDO('sqlite:' . $path, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $rows = $pdo->query('PRAGMA quick_check')->fetchAll(PDO::FETCH_COLUMN);
                $checked[] = $name;
                if (count($rows) !== 1 || strtolower(trim((string)$rows[0])) !== 'ok') {
                    $errors[$name] = implode('; ', array_map('strval', $rows));
                }
            } catch (Throwable $e) {
                $errors[$name] = $e->getMessage();
            }
        }

        return ['ok' => empty($errors), 'checked' => $checked, 'errors' => $errors];
    }

    public static function createPreUpdateBackupZip(
        string $currentVersion,
        string $targetVersion,
        string $reason,
        string $packFilename,
        string $packSha256
    ): string {
        if (PHP_OS_FAMILY !== 'Linux' || !is_dir(IDAS_PATH_DATABASE_ROOT)) {
            return '';
        }

        $backupDir = self::backupDirectory();
        if (!is_dir($backupDir) && !@mkdir($backupDir, 0770, true) && !is_dir($backupDir)) {
            throw new RuntimeException("Cannot create backup directory: {$backupDir}");
        }
        @chmod($backupDir, 0770);

        $safeCurrent = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $currentVersion ?: 'unknown');
        $safeTarget = preg_replace('/[^A-Za-z0-9_.-]+/', '_', $targetVersion ?: 'unknown');
        $timestamp = date('Ymd_His');
        $zipPath = $backupDir . "/idas_backup_before_update_{$safeCurrent}_to_{$safeTarget}_{$timestamp}.pending.zip";

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create backup zip: {$zipPath}");
        }

        $files = ['KLS_NTCS_IDAS.Lin', 'ntcs_barcode_IDAS.db', 'ntcs_device_IDAS.db', 'ntcs_data.db', 'das.db'];
        $added = 0;
        foreach ($files as $file) {
            $path = idas_path('database_root', $file);
            if (is_file($path) && is_readable($path)) {
                $zip->addFile($path, 'database/' . $file);
                $added++;
            }
        }

        $metadata = [
            'generated_at' => date('Y-m-d H:i:s'),
            'current_version' => $currentVersion,
            'target_version' => $targetVersion,
            'reason' => $reason,
            'pack_filename' => $packFilename,
            'pack_sha256' => $packSha256,
            'db_file_count' => $added,
        ];
        $zip->addFromString('backup_info.json', json_encode(
            $metadata,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ));
        $zip->close();

        if (!is_file($zipPath) || (int)@filesize($zipPath) <= 0) {
            throw new RuntimeException("Backup zip not generated: {$zipPath}");
        }

        $verifyZip = new ZipArchive();
        $verifyResult = $verifyZip->open($zipPath, ZipArchive::CHECKCONS);
        $valid = $added >= 1
            && $verifyResult === true
            && $verifyZip->locateName('backup_info.json') !== false;
        if ($verifyResult === true) {
            $verifyZip->close();
        }
        if (!$valid) {
            $failedPath = preg_replace('/\.pending\.zip$/', '.failed-invalid.zip', $zipPath);
            if (is_string($failedPath)) {
                @rename($zipPath, $failedPath);
            }
            throw new RuntimeException("Backup ZIP verification failed: {$zipPath}");
        }

        @chmod($zipPath, 0660);
        return $zipPath;
    }

    public static function markPreUpdateBackup(string $backupPath, string $status): string
    {
        if ($backupPath === '' || !is_file($backupPath)) {
            return $backupPath;
        }

        $status = $status === 'success' ? 'success' : 'failed';
        $target = preg_replace('/\.pending\.zip$/', '.' . $status . '.zip', $backupPath);
        if (!is_string($target) || $target === $backupPath) {
            $target = preg_replace('/\.zip$/', '.' . $status . '.zip', $backupPath);
        }
        if (is_string($target) && $target !== $backupPath && @rename($backupPath, $target)) {
            @chmod($target, 0660);
            return $target;
        }

        return $backupPath;
    }
}
