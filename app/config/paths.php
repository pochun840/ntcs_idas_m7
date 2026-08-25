<?php

/**
 * iDAS production path configuration.
 *
 * Keep every platform-dependent filesystem root in this file.  The optional
 * environment variables make the same source tree usable by installation,
 * service and test processes without changing application code.
 */
$idasPathConfig = [
    'home_root'       => getenv('IDAS_HOME_ROOT') ?: '/home/kls',
    'web_root'        => getenv('IDAS_WEB_ROOT') ?: '/var/www/html',
    'idas_root'       => getenv('IDAS_ROOT') ?: '/var/www/html/idas',
    'database_root'   => getenv('IDAS_DATABASE_ROOT') ?: '/var/www/html/database',
    'temp_root'       => getenv('IDAS_TEMP_ROOT') ?: '/var/www/html/temp',
    'extracted_root'  => getenv('IDAS_EXTRACTED_ROOT') ?: '/var/www/html/extracted',
    'controller_root' => getenv('IDAS_CONTROLLER_ROOT') ?: '/home/kls/NTCS7',
    'legacy_controller_root' => getenv('IDAS_LEGACY_CONTROLLER_ROOT') ?: '/home/kls/NTCS',
    'controller_resource_root' => getenv('IDAS_CONTROLLER_RESOURCE_ROOT') ?: '/home/kls/tcc/resource/db_emmc',
    'upgrade_root'    => getenv('IDAS_UPGRADE_ROOT') ?: '/home/kls/upgrade',
    'ramdisk_root'    => getenv('IDAS_RAMDISK_ROOT') ?: '/mnt/ramdisk',
    'ramdisk_ftp'     => getenv('IDAS_RAMDISK_FTP') ?: '/mnt/ramdisk/ftp',
    'service_root'    => getenv('IDAS_SERVICE_ROOT') ?: '/var/www/html/idas/service',
    'public_ftp'      => getenv('IDAS_PUBLIC_FTP') ?: '/var/www/html/idas/public/ftp',
];

foreach ($idasPathConfig as $idasPathKey => $idasPathValue) {
    $idasPathConfig[$idasPathKey] = rtrim(str_replace('\\', '/', (string)$idasPathValue), '/');
}

defined('IDAS_PATH_HOME_ROOT')       || define('IDAS_PATH_HOME_ROOT', $idasPathConfig['home_root']);
defined('IDAS_PATH_WEB_ROOT')        || define('IDAS_PATH_WEB_ROOT', $idasPathConfig['web_root']);
defined('IDAS_PATH_IDAS_ROOT')       || define('IDAS_PATH_IDAS_ROOT', $idasPathConfig['idas_root']);
defined('IDAS_PATH_DATABASE_ROOT')   || define('IDAS_PATH_DATABASE_ROOT', $idasPathConfig['database_root']);
defined('IDAS_PATH_TEMP_ROOT')       || define('IDAS_PATH_TEMP_ROOT', $idasPathConfig['temp_root']);
defined('IDAS_PATH_EXTRACTED_ROOT')  || define('IDAS_PATH_EXTRACTED_ROOT', $idasPathConfig['extracted_root']);
defined('IDAS_PATH_CONTROLLER_ROOT') || define('IDAS_PATH_CONTROLLER_ROOT', $idasPathConfig['controller_root']);
defined('IDAS_PATH_LEGACY_CONTROLLER_ROOT') || define('IDAS_PATH_LEGACY_CONTROLLER_ROOT', $idasPathConfig['legacy_controller_root']);
defined('IDAS_PATH_CONTROLLER_RESOURCE_ROOT') || define('IDAS_PATH_CONTROLLER_RESOURCE_ROOT', $idasPathConfig['controller_resource_root']);
defined('IDAS_PATH_UPGRADE_ROOT')    || define('IDAS_PATH_UPGRADE_ROOT', $idasPathConfig['upgrade_root']);
defined('IDAS_PATH_RAMDISK_ROOT')    || define('IDAS_PATH_RAMDISK_ROOT', $idasPathConfig['ramdisk_root']);
defined('IDAS_PATH_RAMDISK_FTP')     || define('IDAS_PATH_RAMDISK_FTP', $idasPathConfig['ramdisk_ftp']);
defined('IDAS_PATH_SERVICE_ROOT')    || define('IDAS_PATH_SERVICE_ROOT', $idasPathConfig['service_root']);
defined('IDAS_PATH_PUBLIC_FTP')      || define('IDAS_PATH_PUBLIC_FTP', $idasPathConfig['public_ftp']);

if (!function_exists('idas_path')) {
    function idas_path(string $key, string $append = ''): string
    {
        static $paths = null;

        if ($paths === null) {
            $paths = [
                'home_root'       => IDAS_PATH_HOME_ROOT,
                'web_root'        => IDAS_PATH_WEB_ROOT,
                'idas_root'       => IDAS_PATH_IDAS_ROOT,
                'database_root'   => IDAS_PATH_DATABASE_ROOT,
                'temp_root'       => IDAS_PATH_TEMP_ROOT,
                'extracted_root'  => IDAS_PATH_EXTRACTED_ROOT,
                'controller_root' => IDAS_PATH_CONTROLLER_ROOT,
                'legacy_controller_root' => IDAS_PATH_LEGACY_CONTROLLER_ROOT,
                'controller_resource_root' => IDAS_PATH_CONTROLLER_RESOURCE_ROOT,
                'upgrade_root'    => IDAS_PATH_UPGRADE_ROOT,
                'ramdisk_root'    => IDAS_PATH_RAMDISK_ROOT,
                'ramdisk_ftp'     => IDAS_PATH_RAMDISK_FTP,
                'service_root'    => IDAS_PATH_SERVICE_ROOT,
                'public_ftp'      => IDAS_PATH_PUBLIC_FTP,
            ];
        }

        if (!array_key_exists($key, $paths)) {
            throw new InvalidArgumentException('Unknown iDAS path key: ' . $key);
        }

        return $append === ''
            ? $paths[$key]
            : $paths[$key] . '/' . ltrim(str_replace('\\', '/', $append), '/');
    }
}
