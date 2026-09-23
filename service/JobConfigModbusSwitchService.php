<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

/** Send the same Modbus TCP command as Remotes::Change_Job (registers 463/464). */
final class JobConfigModbusSwitchService
{
    private $unitId;
    private $port;

    public function __construct(int $unitId = 1, int $port = 502)
    {
        if ($unitId < 1 || $unitId > 255 || $port < 1 || $port > 65535) {
            throw new InvalidArgumentException('Invalid Modbus unit ID or TCP port');
        }
        $this->unitId = $unitId;
        $this->port = $port;
    }

    public function switchJob(string $ip, int $job, int $seq, ?string $barcode = null): void
    {
        $client = new ModbusMaster($ip, 'TCP');
        $client->port = $this->port;
        $client->timeout_sec = 4;
        // The barcode can trigger its own JOB selection on the controller.
        // Write it first, then explicitly select the requested JOB and SEQ.
        if ($barcode !== null) $this->writeBarcode($ip, $barcode);

        // Follow the established JobSwitchService sequence: the controller
        // consumes JOB and SEQ commands separately, with a short interval.
        $client->writeMultipleRegister($this->unitId, 463, [$job], ['INT']);
        usleep(120000);
        $client->writeMultipleRegister($this->unitId, 464, [$seq], ['INT']);

        // The command registers are not state. Check actual JOB/SEQ at 4305/4306.
        $client->timeout_sec = 1;
        $lastValues = null;
        $lastError = null;
        $deadline = microtime(true) + 6;
        for ($attempt = 0; $attempt < 20 && microtime(true) < $deadline; $attempt++) {
            if ($attempt !== 0) usleep(250000);
            try {
                $values = self::decodeWords($client->readMultipleRegisters($this->unitId, 4305, 2));
                if (count($values) >= 2 && $values[0] === $job && $values[1] === $seq) return;
                $lastValues = $values;
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
            }
        }
        $actual = $lastValues !== null ? implode('/', $lastValues) : 'unavailable';
        throw new RuntimeException('Modbus command sent; 4305/4306 returned ' . $actual .
            ' (expected ' . $job . '/' . $seq . ')' . ($lastError !== null ? '; last read error: ' . $lastError : ''));
    }

    /** Input barcode occupies 50 consecutive Modbus registers starting at 396. */
    public function writeBarcode(string $ip, string $barcode): void
    {
        if ($barcode === '' || strlen($barcode) > 50 || !preg_match('/^[\x20-\x7E]+$/D', $barcode)) {
            throw new InvalidArgumentException('Mapped barcode must contain 1–50 printable ASCII bytes');
        }
        // The controller exposes a 50-register ASCII field. Each character
        // occupies one register; clear every unused register to avoid stale
        // characters left by a previously scanned longer barcode.
        $words = [];
        for ($i = 0, $length = strlen($barcode); $i < 50; $i++) {
            $words[] = $i < $length ? ord($barcode[$i]) : 0;
        }
        $client = new ModbusMaster($ip, 'TCP');
        $client->port = $this->port;
        $client->timeout_sec = 4;
        $client->writeMultipleRegister($this->unitId, 396, $words, array_fill(0, 50, 'INT'));
    }

    private static function decodeWords($raw): array
    {
        if (is_string($raw)) {
            $values = @unpack('n*', $raw);
            return $values ? array_values($values) : [];
        }
        if (!is_array($raw) || !$raw) return [];
        $raw = array_values(array_map('intval', $raw));
        if (count($raw) % 2 === 0 && max($raw) <= 255) {
            $words = [];
            for ($i = 0; $i < count($raw); $i += 2) $words[] = (($raw[$i] & 255) << 8) | ($raw[$i + 1] & 255);
            return $words;
        }
        return $raw;
    }
}
