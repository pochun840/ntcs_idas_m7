<?php

final class SystemConfigExportService
{
    public static function download(array $controllerInfo, callable $logger): void
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            http_response_code(400);
            echo json_encode(['error' => 'Only supported on Linux']);
            return;
        }

        $clientTs = preg_replace('/[^0-9]/', '', (string)($_GET['client_ts'] ?? ''));
        if (strlen($clientTs) !== 14) $clientTs = date('YmdHis');
        $sn = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($controllerInfo['device_sn'] ?? 'UNKNOWN'));
        $product = (defined('IS_ICONTROLLER') && IS_ICONTROLLER) ? 'iController' : 'NTCS7';
        $zipName = "{$product}_Config_{$sn}_{$clientTs}.zip";
        $zipPath = IDAS_PATH_RAMDISK_FTP . '/' . $zipName;
        $syslogTmp = IDAS_PATH_RAMDISK_FTP . "/syslog_snapshot_{$clientTs}.log";

        if (!is_dir(IDAS_PATH_RAMDISK_FTP) && !@mkdir(IDAS_PATH_RAMDISK_FTP, 0775, true)) {
            echo json_encode(['error' => 'temporary directory unavailable']);
            return;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $logger("Cannot create zip: {$zipPath}");
            echo json_encode(['error' => 'cannot create zip']);
            return;
        }

        $files = [
            [IDAS_PATH_CONTROLLER_ROOT . '/KLS_NTCS.Lin', "con_{$sn}_{$clientTs}.Lin", 'LIN'],
            [IDAS_PATH_DATABASE_ROOT . '/ntcs_barcode_IDAS.db', "bc_{$sn}_{$clientTs}.db", 'Barcode DB'],
            [IDAS_PATH_CONTROLLER_ROOT . '/ntcs_log.csv', "datalog_{$clientTs}.csv", 'Data log'],
        ];
        foreach ($files as [$source, $name, $label]) {
            if (is_file($source)) $zip->addFile($source, $name);
            else $logger("{$label} not found: {$source}");
        }

        if (is_file('/var/log/syslog')) {
            $command = sprintf('sudo /bin/cp %s %s && sudo /bin/chmod 644 %s 2>/dev/null',
                escapeshellarg('/var/log/syslog'), escapeshellarg($syslogTmp), escapeshellarg($syslogTmp));
            exec($command, $unused, $status);
            if ($status === 0 && is_file($syslogTmp) && filesize($syslogTmp) > 0) {
                $zip->addFile($syslogTmp, "syslog_{$clientTs}.log");
            } else $logger('Syslog copy failed (sudo permission)');
        }
        $zip->close();

        if (!is_file($zipPath)) {
            @unlink($syslogTmp);
            echo json_encode(['error' => 'zip not generated']);
            return;
        }
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . rawurlencode($zipName) . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        readfile($zipPath);
        @unlink($syslogTmp);
        @unlink($zipPath);
        exit;
    }
}
