<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';

/**
 * Barcode-only Modbus writer for register 396.
 * JOB/SEQ switching is intentionally owned only by JobSwitchService.
 * The legacy filename is retained so deployments can replace one file safely.
 */
final class JobConfigBarcodeModbusService
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
}
