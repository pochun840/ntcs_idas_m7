<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/config/paths.php';
final class MesUploadLogService
{
    private $db;
    public function __construct(?PDO $db=null)
    {
        if ($db===null) {
            $path=idas_path('database_root','das.db');
            if (!is_file($path)) throw new RuntimeException('das.db not found');
            $db=new PDO('sqlite:'.$path);
        }
        // Reuse the caller connection to avoid locking against its own cursor.
        $this->db=$db;
        $this->db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $this->db->exec('PRAGMA busy_timeout=5000');
        $this->db->exec("CREATE TABLE IF NOT EXISTS mes_upload_log (id INTEGER PRIMARY KEY AUTOINCREMENT, kind TEXT NOT NULL, reference TEXT NOT NULL, barcode TEXT NOT NULL, source TEXT NOT NULL, job_name TEXT NOT NULL, status TEXT NOT NULL, created_at TEXT NOT NULL)");
        $this->db->exec('CREATE INDEX IF NOT EXISTS mes_upload_log_kind_id ON mes_upload_log(kind,id)');
        $columns=$this->db->query('PRAGMA table_info(mes_upload_log)')->fetchAll(PDO::FETCH_COLUMN,1);
        if (!in_array('message',$columns,true)) {
            try {$this->db->exec("ALTER TABLE mes_upload_log ADD COLUMN message TEXT NOT NULL DEFAULT ''");}
            catch(PDOException $e) {
                $columns=$this->db->query('PRAGMA table_info(mes_upload_log)')->fetchAll(PDO::FETCH_COLUMN,1);
                if (!in_array('message',$columns,true)) throw $e;
            }
        }
        $this->db->exec('CREATE INDEX IF NOT EXISTS mes_upload_log_reference_id ON mes_upload_log(kind,reference,id)');

    }
    public function append(string $kind,string $reference,string $barcode,string $source,string $jobName,string $status,string $message = ''): void
    {
        $stmt=$this->db->prepare('INSERT INTO mes_upload_log(kind,reference,barcode,source,job_name,status,created_at,message) VALUES(?,?,?,?,?,?,?,?)');
        $stmt->execute([$kind,$reference,$barcode,$source,$jobName,$status,date('c'),$message]);
        $stmt->closeCursor();
    }
    public function page(string $kind,int $page,int $anchor=0): array
    {
        $this->db->beginTransaction();
        try {
            if ($anchor === 0) {
                $stmt=$this->db->prepare('SELECT COALESCE(MAX(id),0) FROM mes_upload_log WHERE kind=?');
                $stmt->execute([$kind]);$anchor=(int)$stmt->fetchColumn();
            }
            $stmt=$this->db->prepare('SELECT COUNT(*) FROM mes_upload_log WHERE kind=? AND id<=?');
            $stmt->execute([$kind,$anchor]);$total=(int)$stmt->fetchColumn();
            $pages=max(1,(int)ceil($total/10));$page=max(1,min($page,$pages));
            $stmt=$this->db->prepare('SELECT id,reference,barcode,source,job_name,status,created_at,message FROM mes_upload_log WHERE kind=:kind AND id<=:anchor ORDER BY id DESC LIMIT 10 OFFSET :offset');
            $stmt->bindValue(':kind',$kind,PDO::PARAM_STR);
            $stmt->bindValue(':anchor',$anchor,PDO::PARAM_INT);
            $stmt->bindValue(':offset',($page-1)*10,PDO::PARAM_INT);
            $stmt->execute();$rows=$stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $this->db->commit();
            foreach ($rows as &$row) {
                $latest=$this->latestForReference($kind,(string)$row['reference']);
                $row['can_retry']=in_array($row['status'],['failed','test_failed'],true) && $latest && (int)$latest['id']===(int)$row['id'];
                if ($kind==='barcode' && $row['can_retry']) {
                    $current=$this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='idas_barcode_received'")->fetchColumn();
                    if (!$current) $row['can_retry']=false;
                    else {$q=$this->db->query('SELECT receipt_id FROM idas_barcode_received WHERE id=1');$receipt=$q->fetchColumn();$q->closeCursor();$row['can_retry']=$receipt===$row['reference'];}
                }
            }
            unset($row);
            return ['rows'=>$rows,'page'=>$page,'pages'=>$pages,'total'=>$total,'anchor'=>$anchor,'page_size'=>10];
        } catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    public function find(int $id): ?array
    {
        $stmt=$this->db->prepare('SELECT * FROM mes_upload_log WHERE id=?');$stmt->execute([$id]);$row=$stmt->fetch(PDO::FETCH_ASSOC);$stmt->closeCursor();return $row ?: null;
    }
    public function latestForReference(string $kind,string $reference): ?array
    {
        $stmt=$this->db->prepare('SELECT * FROM mes_upload_log WHERE kind=? AND reference=? ORDER BY id DESC LIMIT 1');$stmt->execute([$kind,$reference]);$row=$stmt->fetch(PDO::FETCH_ASSOC);$stmt->closeCursor();return $row ?: null;
    }
    public function recent(string $kind): array
    {
        $stmt=$this->db->prepare('SELECT id,reference,barcode,source,job_name,status,created_at,message FROM mes_upload_log WHERE kind=? ORDER BY id DESC LIMIT 50');
        $stmt->execute([$kind]);return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
