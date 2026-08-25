<?php

require_once dirname(__DIR__) . '/app/config/paths.php';

/**
 * Shared database storage policy for NTCS and iController.
 */
class DiskSpaceService
{
    public const WARNING_MB = 100;
    public const BLOCKING_MB = 50;
    public const WARNING_BYTES = self::WARNING_MB * 1048576;
    public const BLOCKING_BYTES = self::BLOCKING_MB * 1048576;

    public static function databaseFreeBytes(): int
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return 1024 * 1024 * 1024 * 1024;
        }

        $bytes = @disk_free_space(IDAS_PATH_DATABASE_ROOT);
        return $bytes === false ? 0 : max(0, (int)$bytes);
    }

    public static function status(int $requiredBytes = 0): array
    {
        $freeBytes = self::databaseFreeBytes();
        $requiredBytes = max(self::BLOCKING_BYTES, $requiredBytes);

        return [
            'free_bytes' => $freeBytes,
            'free_mb' => round($freeBytes / 1048576, 1),
            'warning' => $freeBytes < self::WARNING_BYTES,
            'blocked' => $freeBytes < $requiredBytes,
            'warning_mb' => self::WARNING_MB,
            'blocking_mb' => self::BLOCKING_MB,
            'required_bytes' => $requiredBytes,
            'required_mb' => round($requiredBytes / 1048576, 1),
        ];
    }

    /** Calculate conservative update working space from ZIP metadata. */
    public static function updatePackSpaceRequirement(string $zipPath, int $uploadBytes = 0): array
    {
        $uncompressed = 0;
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'uncompressed_bytes' => 0, 'required_bytes' => 0];
        }

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if (is_array($stat)) {
                $uncompressed += max(0, (int)($stat['size'] ?? 0));
            }
        }
        $zip->close();

        // Upload + extracted tree + staged deployment + 20 MB operating reserve.
        $required = max(
            self::BLOCKING_BYTES,
            max(0, $uploadBytes) + ($uncompressed * 2) + (20 * 1048576)
        );

        return [
            'ok' => true,
            'uncompressed_bytes' => $uncompressed,
            'required_bytes' => $required,
        ];
    }

    /** Prepare storage before an update without deleting production DBs. */
    public static function prepareForUpdate(bool $updateLockOwned = true): array
    {
        require_once __DIR__ . '/DatabaseBackupService.php';

        $before = self::databaseFreeBytes();
        $cleanup = $before < self::WARNING_BYTES
            ? DatabaseBackupService::safeCleanup($before < self::BLOCKING_BYTES, $updateLockOwned)
            : [
                'free_bytes_before' => $before,
                'free_bytes' => $before,
                'actions' => [],
                'busy' => false,
            ];

        $after = (int)($cleanup['free_bytes'] ?? $before);

        return [
            'ok' => $after >= self::BLOCKING_BYTES,
            'warning' => $after < self::WARNING_BYTES,
            'free_bytes_before' => (int)($cleanup['free_bytes_before'] ?? $before),
            'free_bytes' => $after,
            'free_mb' => round($after / 1048576, 1),
            'warning_mb' => self::WARNING_MB,
            'blocking_mb' => self::BLOCKING_MB,
            'cleanup_actions' => $cleanup['actions'] ?? [],
            'cleanup_busy' => !empty($cleanup['busy']),
        ];
    }
}
