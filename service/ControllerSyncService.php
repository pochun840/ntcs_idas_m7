<?php

final class ControllerSyncService
{
    private static function copyToTemporary(string $source, string $temporary): bool
    {
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (!in_array($extension, ['db', 'lin', 'sqlite', 'sqlite3'], true)) return @copy($source, $temporary);
        // Include committed WAL pages without copying an actively changing file.
        try {
            @unlink($temporary);
            $pdo = idas_sqlite_connect($source);
            $pdo->exec('VACUUM INTO ' . $pdo->quote($temporary));
            return true;
        } catch (Throwable $e) {
            @unlink($temporary);
            return false;
        }
    }

    private static function verifyDatabaseIfApplicable(string $path, string $source): bool
    {
        // The temporary filename ends in .tmp.<pid>, not the database extension.
        $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        if (!in_array($extension, ['db', 'lin', 'sqlite', 'sqlite3'], true)) return true;
        try {
            $pdo = idas_sqlite_connect($path, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            return strtolower(trim((string)$pdo->query('PRAGMA quick_check')->fetchColumn())) === 'ok';
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Copy through a sibling temporary file so a failed copy never truncates the target. */
    public static function atomicCopy(string $source, string $target, ?callable $logger = null): bool
    {
        if (!is_file($source) || !is_readable($source)) {
            if ($logger) $logger("Source file not found: {$source}");
            return false;
        }
        $directory = dirname($target);
        if (!is_dir($directory) && !@mkdir($directory, 0775, true)) return false;
        $lockPath = (realpath($target) ?: $target) . '.sync.lock';
        $lock = @fopen($lockPath, 'c');
        if (!$lock || !@flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) @fclose($lock);
            if ($logger) $logger("Sync busy: {$target}");
            return false;
        }
        $temporary = $target . '.tmp.' . getmypid();
        try {
            if (!self::copyToTemporary($source, $temporary) || !self::verifyDatabaseIfApplicable($temporary, $source)) {
                @unlink($temporary);
                if ($logger) $logger("Failed copy or integrity check: {$source} -> {$target}");
                return false;
            }
            $mode = @fileperms(is_file($target) ? $target : $source);
            if ($mode !== false && !@chmod($temporary, $mode & 0777)) {
                @unlink($temporary);
                if ($logger) $logger("Failed to preserve permissions: {$target}");
                return false;
            }
            if (!@rename($temporary, $target)) {
                @unlink($temporary);
                if ($logger) $logger("Failed replace: {$source} -> {$target}");
                return false;
            }
        } finally {
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }
        if ($logger) $logger("Copied: {$source} -> {$target}");
        return true;
    }

    public static function synchronize(array $filePairs, callable $notify, ?callable $logger = null): array
    {
        $copied = [];
        foreach ($filePairs as $pair) {
            if (!self::atomicCopy($pair['source'], $pair['target'], $logger)) return ['ok' => false, 'copied' => $copied];
            $copied[] = $pair['target'];
        }
        $notify();
        return ['ok' => true, 'copied' => $copied];
    }
}
