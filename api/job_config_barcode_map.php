<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
require_once dirname(__DIR__) . '/app/config/paths.php';

function barcodeMapReply(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $path = idas_path('database_root', 'das.db');
    if (!is_file($path)) barcodeMapReply(503, ['success'=>false,'message'=>'das.db unavailable']);
    $db = idas_sqlite_connect($path);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('PRAGMA busy_timeout=5000');
    $schema = 'CREATE TABLE IF NOT EXISTS barcode_step_mapping (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        barcode TEXT NOT NULL UNIQUE,
        raw_barcode TEXT NOT NULL,
        range_from INTEGER NOT NULL CHECK(range_from BETWEEN 1 AND 54),
        range_count INTEGER NOT NULL CHECK(range_count BETWEEN 1 AND 54),
        barcode_mode INTEGER NOT NULL CHECK(barcode_mode BETWEEN 1 AND 3),
        job_id INTEGER NOT NULL CHECK(job_id BETWEEN 1 AND 100),
        seq_id INTEGER NOT NULL CHECK(seq_id BETWEEN 1 AND 50),
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )';
    $db->exec($schema);
    // Upgrade the existing table in place while keeping its IDs, barcodes and JOB/SEQ pairs.
    $columns = array_column($db->query('PRAGMA table_info(barcode_step_mapping)')->fetchAll(PDO::FETCH_ASSOC), 'name');
    if (in_array('step_id', $columns, true)) {
        $db->beginTransaction();
        try {
            $db->exec('CREATE TABLE barcode_job_seq_upgrade (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                barcode TEXT NOT NULL UNIQUE,
                raw_barcode TEXT NOT NULL,
                range_from INTEGER NOT NULL CHECK(range_from BETWEEN 1 AND 54),
                range_count INTEGER NOT NULL CHECK(range_count BETWEEN 1 AND 54),
                barcode_mode INTEGER NOT NULL CHECK(barcode_mode BETWEEN 1 AND 3),
                job_id INTEGER NOT NULL CHECK(job_id BETWEEN 1 AND 100),
                seq_id INTEGER NOT NULL CHECK(seq_id BETWEEN 1 AND 50),
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )');
            $db->exec('INSERT INTO barcode_job_seq_upgrade (id,barcode,raw_barcode,range_from,range_count,barcode_mode,job_id,seq_id,created_at,updated_at)
                SELECT id,barcode,raw_barcode,range_from,range_count,barcode_mode,job_id,seq_id,created_at,updated_at FROM barcode_step_mapping');
            $db->exec('DROP TABLE barcode_step_mapping');
            $db->exec('ALTER TABLE barcode_job_seq_upgrade RENAME TO barcode_step_mapping');
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'GET') {
        $scan = (string)($_GET['scan'] ?? '');
        if ($scan !== '') {
            if (strlen($scan) > 1024) barcodeMapReply(400, ['success'=>false,'message'=>'Barcode too long']);
            $stmt = $db->prepare('SELECT * FROM barcode_step_mapping WHERE substr(:barcode, range_from, range_count) = barcode AND length(:length_barcode) >= range_from ORDER BY id DESC LIMIT 1');
            $stmt->execute([':barcode'=>$scan,':length_barcode'=>$scan]);
            barcodeMapReply(200, ['success'=>true,'mapping'=>$stmt->fetch(PDO::FETCH_ASSOC) ?: null]);
        }
        $items = $db->query('SELECT * FROM barcode_step_mapping ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
        barcodeMapReply(200, ['success'=>true,'mappings'=>$items]);
    }
    if (!in_array($method, ['POST','DELETE'], true)) {
        header('Allow: GET, POST, DELETE');
        barcodeMapReply(405, ['success'=>false,'message'=>'Method not allowed']);
    }
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && parse_url($origin, PHP_URL_HOST) !== ($_SERVER['HTTP_HOST'] ?? '')) {
        // Host may include a port; compare parsed names instead.
        $host = explode(':', (string)($_SERVER['HTTP_HOST'] ?? ''))[0];
        if (parse_url($origin, PHP_URL_HOST) !== $host) barcodeMapReply(403, ['success'=>false,'message'=>'Invalid origin']);
    }
    $request = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($request)) barcodeMapReply(400, ['success'=>false,'message'=>'JSON body required']);
    if ($method === 'DELETE') {
        $id = filter_var($request['id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) barcodeMapReply(400, ['success'=>false,'message'=>'Invalid ID']);
        $stmt = $db->prepare('DELETE FROM barcode_step_mapping WHERE id=:id');
        $stmt->execute([':id'=>$id]);
        barcodeMapReply($stmt->rowCount() ? 200 : 404, ['success'=>(bool)$stmt->rowCount()]);
    }
    $raw = trim((string)($request['raw_barcode'] ?? ''));
    $from = filter_var($request['range_from'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>54]]);
    $count = filter_var($request['range_count'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>54]]);
    $mode = filter_var($request['barcode_mode'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>3]]);
    $job = filter_var($request['job_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>100]]);
    $seq = filter_var($request['seq_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>50]]);
    if ($raw === '' || strlen($raw) > 1024 || !$from || !$count || !$mode || !$job || !$seq || strlen($raw) < $from) {
        barcodeMapReply(400, ['success'=>false,'message'=>'Invalid barcode mapping fields']);
    }
    $barcode = substr($raw, $from - 1, $count);
    $stmt = $db->prepare('INSERT INTO barcode_step_mapping (barcode,raw_barcode,range_from,range_count,barcode_mode,job_id,seq_id)
        VALUES (:barcode,:raw,:from,:count,:mode,:job,:seq)
        ON CONFLICT(barcode) DO UPDATE SET raw_barcode=excluded.raw_barcode,range_from=excluded.range_from,range_count=excluded.range_count,
        barcode_mode=excluded.barcode_mode,job_id=excluded.job_id,seq_id=excluded.seq_id,updated_at=CURRENT_TIMESTAMP');
    $stmt->execute([':barcode'=>$barcode,':raw'=>$raw,':from'=>$from,':count'=>$count,':mode'=>$mode,':job'=>$job,':seq'=>$seq]);
    barcodeMapReply(200, ['success'=>true,'barcode'=>$barcode]);
} catch (Throwable $e) {
    error_log('[barcode_step_mapping] '.$e->getMessage());
    barcodeMapReply(503, ['success'=>false,'message'=>'Barcode mapping database unavailable']);
}
