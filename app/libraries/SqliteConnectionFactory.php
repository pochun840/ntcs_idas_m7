<?php

/** Central SQLite connection policy shared by NTCS and iController. */
final class SqliteConnectionFactory
{
    private const BUSY_TIMEOUT_MS = 5000;

    public static function connect(string $path, array $options = []): PDO
    {
        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO('sqlite:' . $path, null, null, $options + $defaults);
        $pdo->exec('PRAGMA busy_timeout = ' . self::BUSY_TIMEOUT_MS);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA encoding = "UTF-8"');
        return $pdo;
    }
}

if (!function_exists('idas_sqlite_connect')) {
    function idas_sqlite_connect(string $path, array $options = []): PDO
    {
        return SqliteConnectionFactory::connect($path, $options);
    }
}
