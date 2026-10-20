<?php
 declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/paths.php';
final class MesApiSettingsService
{
    public const KEYS = ['mes_station', 'mes_barcode_api_url', 'mes_tightening_api_url'];
    private $db;
    public function __construct()
    {
        $path = rtrim(IDAS_PATH_DATABASE_ROOT, '/\\') . '/das.db';
        if (!is_file($path)) throw new RuntimeException('das.db not found');
        $this->db = new PDO('sqlite:' . $path);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA busy_timeout=5000');
    }
    public static function validate(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';
        $parts = parse_url($url);
        if (strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL) || !$parts ||
            !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) ||
            isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])) {
            throw new InvalidArgumentException('Enter a complete HTTP/HTTPS URL');
        }
        return $url;
    }
    public static function validateStation($station, bool $required = true): string
    {
        if (!is_string($station) && !is_int($station)) throw new InvalidArgumentException('station must be a string');
        $station=trim((string)$station);
        if (($required && $station==='') || strlen($station)>64 || preg_match('/[\x00-\x1F\x7F]/',$station)) {
            throw new InvalidArgumentException('Enter a station of 1 to 64 bytes');
        }
        return $station;
    }
    public function read(): array
    {
        $result = array_fill_keys(self::KEYS, '');
        $stmt = $this->db->prepare('SELECT config_value FROM config WHERE config_name = ? ORDER BY id DESC LIMIT 1');
        foreach (self::KEYS as $key) {
            $stmt->execute([$key]);
            $value=$stmt->fetchColumn();
            $result[$key] = $value===false ? '' : (string)$value;
        }
        return $result;
    }
    public function save(array $values): array
    {
        $clean = [];
        $existing=$this->read();
        foreach (self::KEYS as $key) {
            if ($key==='mes_station') {
                $clean[$key]=array_key_exists($key,$values)?self::validateStation($values[$key]):$existing[$key];
                continue;
            }
            if (!isset($values[$key]) || !is_string($values[$key])) throw new InvalidArgumentException('Both API fields are required');
            $clean[$key] = self::validate($values[$key]);
        }
        $this->db->beginTransaction();
        try {
            foreach ($clean as $key => $value) {
                $stmt = $this->db->prepare('SELECT id FROM config WHERE config_name = ? LIMIT 1');
                $stmt->execute([$key]);
                if ($stmt->fetchColumn() !== false) {
                    $stmt = $this->db->prepare('UPDATE config SET config_value = ? WHERE config_name = ?');
                    $stmt->execute([$value, $key]);
                } else {
                    $stmt = $this->db->prepare('INSERT INTO config (config_name, config_value) VALUES (?, ?)');
                    $stmt->execute([$key, $value]);
                }
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $clean;
    }
}
