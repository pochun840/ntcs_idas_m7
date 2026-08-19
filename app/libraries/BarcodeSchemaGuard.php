<?php

/**
 * Enforce Barcode 1:1 mapping for ntcs_barcode_test.
 *
 * Rules:
 *   - one JOB can have only one Barcode
 *   - one Barcode can belong to only one JOB
 *
 * Legacy multi-barcode data is migrated deterministically: newest rowid wins.
 */
class BarcodeSchemaGuard
{
    public static function ensureOneToOne(string $dbPath, string $tableName = 'ntcs_barcode_test'): array
    {
        $result = [
            'status' => 'ok',
            'action' => 'none',
            'message' => 'barcode_db_one_to_one_ok',
            'rows_before' => 0,
            'rows_after' => 0,
            'dropped_invalid' => 0,
            'dropped_job_conflict' => 0,
            'dropped_barcode_conflict' => 0,
            'backup' => '',
        ];

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $tableName)) {
            $result['status'] = 'failed';
            $result['message'] = 'invalid_table_name';
            return $result;
        }

        if ($dbPath === '' || !is_file($dbPath) || @filesize($dbPath) <= 0) {
            $result['status'] = 'failed';
            $result['message'] = 'barcode_db_missing_or_empty';
            return $result;
        }

        try {
            $pdo = self::openDatabase($dbPath);

            if (!self::tableExists($pdo, $tableName)) {
                self::createOneToOneTable($pdo, $tableName);
                $result['status'] = 'repaired';
                $result['action'] = 'create_one_to_one_table';
                $result['message'] = 'barcode_db_one_to_one_created';
                return $result;
            }

            $rows = self::readRows($pdo, $tableName);
            $result['rows_before'] = count($rows);

            if (self::isOneToOneSchema($pdo, $tableName)) {
                $result['rows_after'] = count($rows);
                return $result;
            }

            // Keep a file-level safety copy before changing legacy schema.
            $backupPath = $dbPath . '.before_barcode_one_to_one_' . date('YmdHis') . '.bak';
            if (@copy($dbPath, $backupPath)) {
                @chmod($backupPath, 0666);
                $result['backup'] = $backupPath;
            }

            [$keptRows, $stats] = self::deduplicateNewestWins($rows);
            $result['rows_after'] = count($keptRows);
            $result['dropped_invalid'] = $stats['invalid'];
            $result['dropped_job_conflict'] = $stats['job_conflict'];
            $result['dropped_barcode_conflict'] = $stats['barcode_conflict'];

            self::rebuildOneToOneTable($pdo, $tableName, $keptRows);

            if (!self::isOneToOneSchema($pdo, $tableName)) {
                throw new RuntimeException('one_to_one_schema_verification_failed');
            }

            $check = strtolower(trim((string)$pdo->query('PRAGMA integrity_check')->fetchColumn()));
            if ($check !== 'ok') {
                throw new RuntimeException('sqlite_integrity_check_failed: ' . $check);
            }

            $result['status'] = 'repaired';
            $result['action'] = 'migrate_to_one_to_one';
            $result['message'] = 'barcode_db_one_to_one_repaired';
            return $result;

        } catch (Throwable $e) {
            $result['status'] = 'failed';
            $result['message'] = $e->getMessage();
            return $result;
        }
    }

    private static function openDatabase(string $dbPath): PDO
    {
        $pdo = new PDO('sqlite:' . $dbPath);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA busy_timeout = 5000');
        return $pdo;
    }

    private static function tableExists(PDO $pdo, string $tableName): bool
    {
        $stmt = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=:name LIMIT 1");
        $stmt->execute([':name' => $tableName]);
        return (bool)$stmt->fetchColumn();
    }

    private static function isOneToOneSchema(PDO $pdo, string $tableName): bool
    {
        $columns = $pdo->query('PRAGMA table_info(' . $tableName . ')')->fetchAll(PDO::FETCH_ASSOC);
        $required = ['job_id', 'barcode', 'range_from', 'range_count', 'barcode_mode', 'seq_id'];
        $found = [];
        $jobPrimaryKey = false;
        $jobNotNull = false;
        $barcodeNotNull = false;

        foreach ($columns as $column) {
            $name = strtolower((string)($column['name'] ?? ''));
            if ($name === '') continue;
            $found[] = $name;
            if ($name === 'job_id') {
                $jobPrimaryKey = ((int)($column['pk'] ?? 0) > 0);
                $jobNotNull = ((int)($column['notnull'] ?? 0) === 1) || $jobPrimaryKey;
            }
            if ($name === 'barcode') {
                $barcodeNotNull = ((int)($column['notnull'] ?? 0) === 1);
            }
        }

        foreach ($required as $column) {
            if (!in_array($column, $found, true)) return false;
        }
        if (!$jobNotNull || !$barcodeNotNull) return false;

        $jobUnique = $jobPrimaryKey;
        $barcodeUnique = false;
        $indexes = $pdo->query('PRAGMA index_list(' . $tableName . ')')->fetchAll(PDO::FETCH_ASSOC);

        foreach ($indexes as $index) {
            if ((int)($index['unique'] ?? 0) !== 1) continue;
            $indexName = (string)($index['name'] ?? '');
            if ($indexName === '') continue;

            $safeIndexName = str_replace("'", "''", $indexName);
            $indexColumns = $pdo->query("PRAGMA index_info('{$safeIndexName}')")->fetchAll(PDO::FETCH_ASSOC);
            $names = array_values(array_filter(array_map(
                static fn($row) => strtolower((string)($row['name'] ?? '')),
                $indexColumns
            )));

            if (count($names) === 1 && $names[0] === 'job_id') $jobUnique = true;
            if (count($names) === 1 && $names[0] === 'barcode') $barcodeUnique = true;
        }

        return $jobUnique && $barcodeUnique;
    }

    private static function readRows(PDO $pdo, string $tableName): array
    {
        $columnsInfo = $pdo->query('PRAGMA table_info(' . $tableName . ')')->fetchAll(PDO::FETCH_ASSOC);
        $columns = array_map(
            static fn($row) => strtolower((string)($row['name'] ?? '')),
            $columnsInfo
        );

        $select = [
            'rowid AS __rowid',
            in_array('job_id', $columns, true)       ? 'job_id' : 'NULL AS job_id',
            in_array('barcode', $columns, true)      ? 'barcode' : "'' AS barcode",
            in_array('range_from', $columns, true)   ? 'range_from' : '1 AS range_from',
            in_array('range_count', $columns, true)  ? 'range_count' : '0 AS range_count',
            in_array('barcode_mode', $columns, true) ? 'barcode_mode' : '0 AS barcode_mode',
            in_array('seq_id', $columns, true)       ? 'COALESCE(seq_id, -1) AS seq_id' : '-1 AS seq_id',
        ];

        return $pdo->query(
            'SELECT ' . implode(', ', $select) . ' FROM ' . $tableName . ' ORDER BY rowid ASC'
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private static function deduplicateNewestWins(array $rows): array
    {
        $seenJobs = [];
        $seenBarcodes = [];
        $kept = [];
        $stats = ['invalid' => 0, 'job_conflict' => 0, 'barcode_conflict' => 0];

        for ($i = count($rows) - 1; $i >= 0; $i--) {
            $row = $rows[$i];
            $jobId = isset($row['job_id']) && is_numeric($row['job_id']) ? (int)$row['job_id'] : 0;
            $barcode = trim((string)($row['barcode'] ?? ''));

            if ($jobId <= 0 || $barcode === '') {
                $stats['invalid']++;
                continue;
            }
            if (isset($seenJobs[$jobId])) {
                $stats['job_conflict']++;
                continue;
            }
            if (isset($seenBarcodes[$barcode])) {
                $stats['barcode_conflict']++;
                continue;
            }

            $seenJobs[$jobId] = true;
            $seenBarcodes[$barcode] = true;
            $kept[] = [
                'job_id' => $jobId,
                'barcode' => $barcode,
                'range_from' => isset($row['range_from']) && is_numeric($row['range_from']) ? (int)$row['range_from'] : 1,
                'range_count' => isset($row['range_count']) && is_numeric($row['range_count']) ? (int)$row['range_count'] : 0,
                'barcode_mode' => isset($row['barcode_mode']) && is_numeric($row['barcode_mode']) ? (int)$row['barcode_mode'] : 0,
                'seq_id' => isset($row['seq_id']) && is_numeric($row['seq_id']) ? (int)$row['seq_id'] : -1,
            ];
        }

        // Restore natural old-to-new display order after reverse conflict resolution.
        return [array_reverse($kept), $stats];
    }

    private static function createOneToOneTable(PDO $pdo, string $tableName): void
    {
        $pdo->beginTransaction();
        try {
            self::createTableSql($pdo, $tableName);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private static function rebuildOneToOneTable(PDO $pdo, string $tableName, array $rows): void
    {
        $oldTable = $tableName . '_legacy_' . date('YmdHis') . '_' . substr(md5((string)microtime(true)), 0, 6);

        $pdo->beginTransaction();
        try {
            $pdo->exec('ALTER TABLE ' . $tableName . ' RENAME TO ' . $oldTable);
            self::createTableSql($pdo, $tableName);

            $insert = $pdo->prepare(
                'INSERT INTO ' . $tableName . ' (job_id, barcode, range_from, range_count, barcode_mode, seq_id) '
                . 'VALUES (:job_id, :barcode, :range_from, :range_count, :barcode_mode, :seq_id)'
            );

            foreach ($rows as $row) {
                $insert->execute([
                    ':job_id' => (int)$row['job_id'],
                    ':barcode' => (string)$row['barcode'],
                    ':range_from' => (int)$row['range_from'],
                    ':range_count' => (int)$row['range_count'],
                    ':barcode_mode' => (int)$row['barcode_mode'],
                    ':seq_id' => (int)$row['seq_id'],
                ]);
            }

            $pdo->exec('DROP TABLE ' . $oldTable);
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private static function createTableSql(PDO $pdo, string $tableName): void
    {
        $pdo->exec(
            'CREATE TABLE ' . $tableName . ' ('
            . 'job_id INTEGER NOT NULL UNIQUE, '
            . 'barcode TEXT NOT NULL UNIQUE, '
            . 'range_from INTEGER NOT NULL DEFAULT 1, '
            . 'range_count INTEGER NOT NULL DEFAULT 0, '
            . 'barcode_mode INTEGER NOT NULL DEFAULT 0, '
            . 'seq_id INTEGER NOT NULL DEFAULT -1'
            . ')'
        );
    }
}
