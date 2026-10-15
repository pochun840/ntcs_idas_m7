<?php
declare(strict_types=1);
require_once __DIR__.'/OperationMesReportService.php';
/** Short, serialized scan triggered by the recipe page; no daemon required. */
final class OperationPageMonitorService
{
    private function open(string $path): PDO {
        $db=new PDO('sqlite:'.$path);$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA busy_timeout=5000');return $db;
    }
    public function tick(): array {
        $lock=fopen(idas_path('database_root','.mes_operation_watcher.lock'),'c');
        if (!$lock) throw new RuntimeException('Cannot open operation monitor lock');
        if (!flock($lock,LOCK_EX|LOCK_NB)) {fclose($lock);return ['success'=>true,'busy'=>true];}
        try {return $this->lockedTick();}
        finally {flock($lock,LOCK_UN);fclose($lock);}
    }
    private function lockedTick(): array {
        $path=idas_path('controller_root','ntcs_data.db');
        if (!is_file($path)) throw new RuntimeException('Controller database not found');
        $source=$this->open($path);$source->exec('PRAGMA query_only=ON');
        // Share progress with the optional background worker to prevent duplicate scans.
        $state=$this->open(idas_path('database_root','mes_operation_watcher.db'));
        $state->exec('CREATE TABLE IF NOT EXISTS progress (id INTEGER PRIMARY KEY CHECK(id=1), last_id INTEGER NOT NULL)');
        $state->exec("CREATE TABLE IF NOT EXISTS queue (record_id INTEGER PRIMARY KEY, status TEXT NOT NULL DEFAULT 'pending', attempts INTEGER NOT NULL DEFAULT 0, next_at INTEGER NOT NULL DEFAULT 0, message TEXT NOT NULL DEFAULT '', updated_at TEXT NOT NULL)");
        $cursor=$state->query('SELECT last_id FROM progress WHERE id=1')->fetchColumn();
        $latest=(int)$source->query('SELECT COALESCE(MAX(id),0) FROM ntcs_data')->fetchColumn();
        if ($cursor===false) {
            // Include only the latest existing record on first use, never all history.
            $cursor=max(0,$latest-1);
            $stmt=$state->prepare('INSERT INTO progress(id,last_id) VALUES(1,?)');$stmt->execute([$cursor]);$stmt->closeCursor();
        }
        if ($latest<(int)$cursor) throw new RuntimeException('Controller IDs decreased; review monitor progress after database replacement');
        $stmt=$source->prepare('SELECT id FROM ntcs_data WHERE id>? ORDER BY id LIMIT 100');$stmt->execute([(int)$cursor]);$ids=$stmt->fetchAll(PDO::FETCH_COLUMN);$stmt->closeCursor();
        if ($ids) {
            $state->beginTransaction();
            try {
                $stmt=$state->prepare('INSERT OR IGNORE INTO queue(record_id,updated_at) VALUES(?,?)');
                foreach ($ids as $id) {$stmt->execute([(int)$id,date('c')]);$stmt->closeCursor();}
                $stmt=$state->prepare('UPDATE progress SET last_id=? WHERE id=1');$stmt->execute([(int)end($ids)]);$stmt->closeCursor();$state->commit();
            } catch(Throwable $e) {if ($state->inTransaction()) $state->rollBack();throw $e;}
        }
        // Also include the latest existing record if a daemon initialized its cursor already.
        if ($latest>0) {
            $stmt=$state->prepare('INSERT OR IGNORE INTO queue(record_id,updated_at) VALUES(?,?)');
            $stmt->execute([$latest,date('c')]);$stmt->closeCursor();
        }
        $state->exec("UPDATE queue SET status='uncertain',message='Previous request interrupted; inspect upload history before retry' WHERE status='sending'");
        $stmt=$state->prepare("SELECT record_id,attempts FROM queue WHERE status='pending' AND next_at<=? ORDER BY record_id LIMIT 1");$stmt->execute([time()]);$entry=$stmt->fetch(PDO::FETCH_ASSOC);$stmt->closeCursor();
        if (!$entry) return ['success'=>true,'busy'=>false,'result'=>null];
        $id=(int)$entry['record_id'];$attempts=(int)$entry['attempts']+1;
        $dasPath=idas_path('database_root','das.db');
        if (!is_file($dasPath)) throw new RuntimeException('das.db not found');
        $das=$this->open($dasPath);new MesUploadLogService($das);
        // Same per-ID lock as notify_new_operation, including the history check.
        $idLock=fopen(idas_path('database_root','.mes_operation_upload_'.$id.'.lock'),'c');
        if (!$idLock) throw new RuntimeException('Cannot open operation upload lock');
        if (!flock($idLock,LOCK_EX|LOCK_NB)) {fclose($idLock);return ['success'=>true,'busy'=>true];}
        try {
            $stmt=$das->prepare("SELECT id FROM mes_upload_log WHERE kind='tightening' AND reference=? LIMIT 1");$stmt->execute([(string)$id]);$already=$stmt->fetchColumn()!==false;$stmt->closeCursor();
            $status='done';$message='existing_history';
            if (!$already) {
                $stmt=$state->prepare("UPDATE queue SET status='sending',updated_at=? WHERE record_id=?");$stmt->execute([date('c'),$id]);$stmt->closeCursor();
                try {$result=(new OperationMesReportService())->upload($id);$message=(string)($result['status'] ?? 'ok');}
                catch(Throwable $e) {$status='failed';$message=$e->getMessage();error_log('[idas_operation_page_monitor] id='.$id.' '.$message);}
            }
            $stmt=$state->prepare('UPDATE queue SET status=?,attempts=?,message=?,updated_at=? WHERE record_id=?');
            $stmt->execute([$status,$attempts,$message,date('c'),$id]);$stmt->closeCursor();
            return ['success'=>true,'busy'=>false,'result'=>['id'=>$id,'status'=>$status,'message'=>$message]];
        } finally {flock($idLock,LOCK_UN);fclose($idLock);}
    }
}
