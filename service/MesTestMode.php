<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/app/config/paths.php';
/** Host-wide MES test mode, stored in das.db; controller URLs stay unchanged. */
final class MesTestMode
{
    private static function open(): PDO
    {
        $path=idas_path('database_root','das.db');
        if (!is_file($path)) throw new RuntimeException('das.db not found');
        $db=new PDO('sqlite:'.$path);
        $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA busy_timeout=5000');
        return $db;
    }
    public static function enabled(): bool
    {
        $db=self::open();
        $stmt=$db->prepare("SELECT config_value FROM config WHERE config_name='mes_test_mode' ORDER BY id DESC LIMIT 1");
        $stmt->execute();$value=$stmt->fetchColumn();$stmt->closeCursor();
        return (string)$value==='1';
    }
    public static function setEnabled(bool $enabled): void
    {
        $db=self::open();$db->exec('BEGIN IMMEDIATE');
        try {
            $stmt=$db->prepare("SELECT id FROM config WHERE config_name='mes_test_mode' LIMIT 1");
            $stmt->execute();$exists=$stmt->fetchColumn();$stmt->closeCursor();
            if ($exists===false) $stmt=$db->prepare("INSERT INTO config(config_name,config_value) VALUES('mes_test_mode',?)");
            else $stmt=$db->prepare("UPDATE config SET config_value=? WHERE config_name='mes_test_mode'");
            $stmt->execute([$enabled?'1':'0']);$stmt->closeCursor();$db->exec('COMMIT');
        } catch(Throwable $e){$db->exec('ROLLBACK');throw $e;}
    }
}
