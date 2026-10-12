<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/paths.php';
final class BarcodeReceiveStateService
{
    private $db;
    public function __construct()
    {
        $path = rtrim(IDAS_PATH_DATABASE_ROOT, '/\\') . '/das.db';
        if (!is_file($path)) throw new RuntimeException('das.db not found');
        $this->db = new PDO('sqlite:' . $path);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA busy_timeout=5000');
        $this->db->exec('CREATE TABLE IF NOT EXISTS idas_barcode_received (id INTEGER PRIMARY KEY CHECK(id=1), barcode TEXT NOT NULL, received_at TEXT NOT NULL, receipt_id TEXT NOT NULL)');
    }
    private function ensureDeliveryColumns(): void
    {
        $columns=$this->db->query('PRAGMA table_info(idas_barcode_received)')->fetchAll(PDO::FETCH_COLUMN,1);
        foreach (['mes_status'=>"TEXT NOT NULL DEFAULT 'received'",'mes_message'=>"TEXT NOT NULL DEFAULT ''"] as $column=>$definition) {
            if (!in_array($column,$columns,true)) {
                try {$this->db->exec('ALTER TABLE idas_barcode_received ADD COLUMN '.$column.' '.$definition);}
                catch (PDOException $e) {
                    $current=$this->db->query('PRAGMA table_info(idas_barcode_received)')->fetchAll(PDO::FETCH_COLUMN,1);
                    if (!in_array($column,$current,true)) throw $e;
                }
            }
        }
    }
    public function delivery(string $receiptId,string $status,string $message): void
    {
        $this->ensureDeliveryColumns();
        $stmt=$this->db->prepare('UPDATE idas_barcode_received SET mes_status=?,mes_message=? WHERE id=1 AND receipt_id=?');
        $stmt->execute([$status,$message,$receiptId]);
    }
    public function save(string $barcode): array
    {
        $this->ensureDeliveryColumns();
        $record = ['barcode'=>$barcode, 'received_at'=>date('c'), 'receipt_id'=>bin2hex(random_bytes(16)), 'success'=>true];
        $stmt=$this->db->prepare("INSERT OR REPLACE INTO idas_barcode_received (id,barcode,received_at,receipt_id,mes_status,mes_message) VALUES (1,?,?,?,'sending','')");
        $stmt->execute([$barcode,$record['received_at'],$record['receipt_id']]);
        return $record;
    }
    public function latest(): ?array
    {
        $this->ensureDeliveryColumns();
        $row=$this->db->query('SELECT barcode,received_at,receipt_id,mes_status,mes_message FROM idas_barcode_received WHERE id=1')->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
        // Recover a receipt left pending when a request terminates unexpectedly.
        $receivedAt=strtotime($row['received_at']);
        if ($row['mes_status'] === 'sending' && $receivedAt !== false && time()-$receivedAt >= 15) {
            $stmt=$this->db->prepare("UPDATE idas_barcode_received SET mes_status='failed',mes_message=? WHERE id=1 AND receipt_id=? AND mes_status='sending'");
            $stmt->execute(['MES 傳送逾時或未完成（超過 15 秒）',$row['receipt_id']]);
            $row=$this->db->query('SELECT barcode,received_at,receipt_id,mes_status,mes_message FROM idas_barcode_received WHERE id=1')->fetch(PDO::FETCH_ASSOC);
            if (!$row) return null;
        }
        $row['success']=true;
        return $row;
    }
}
