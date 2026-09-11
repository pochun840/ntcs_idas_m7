<?php

final class JobConfigApiException extends RuntimeException
{
    private $httpStatus;
    private $errorCode;
    private $details;

    public function __construct(int $httpStatus, string $errorCode, string $message, array $details = [])
    {
        parent::__construct($message);
        $this->httpStatus = $httpStatus;
        $this->errorCode = $errorCode;
        $this->details = $details;
    }

    public function getHttpStatus(): int { return $this->httpStatus; }
    public function getErrorCode(): string { return $this->errorCode; }
    public function getDetails(): array { return $this->details; }
}

/**
 * Adds or updates JOB/SEQ/STEP directly in the Controller database and then refreshes
 * the read-only iDAS mirror used by the web application.
 */
final class JobConfigReplaceService
{
    private const RESERVED_JOB_IDS = [0, 221];
    private const MAX_BODY_BYTES = 67108864;

    private $controllerDatabasePath;
    private $idasMirrorPath;
    private $deviceDatabasePath;
    private $controllerFactory;
    private $clock;

    public function __construct(array $options = [])
    {
        $databaseRoot = $options['database_root'] ?? IDAS_PATH_DATABASE_ROOT;
        $controllerRoot = $options['controller_root'] ?? IDAS_PATH_CONTROLLER_ROOT;
        $this->controllerDatabasePath = $options['controller_database_path'] ?? $controllerRoot . '/KLS_NTCS.Lin';
        $this->idasMirrorPath = $options['idas_mirror_path'] ?? $databaseRoot . '/KLS_NTCS_IDAS.Lin';
        $this->deviceDatabasePath = $options['device_database_path'] ?? $databaseRoot . '/ntcs_device_IDAS.db';
        $this->controllerFactory = $options['controller_factory'] ?? static function () { return new Controller(); };
        $this->clock = $options['clock'] ?? static function () { return date('Y-m-d H:i:s'); };
    }

    public static function maxBodyBytes(): int { return self::MAX_BODY_BYTES; }

    /**
     * Verify the local Controller is safe to receive a JOB/SEQ/STEP write.
     * This is also used by the LAN probe endpoint before a remote batch write.
     */
    public function preflight(): array
    {
        $controllerRoot = dirname($this->controllerDatabasePath);
        if (!is_dir($controllerRoot) || !is_readable($controllerRoot)) {
            throw new JobConfigApiException(503, 'CONTROLLER_ROOT_NOT_FOUND', 'Controller root was not found or is not readable');
        }
        if (!is_file($this->controllerDatabasePath) || !is_readable($this->controllerDatabasePath) || !is_writable($this->controllerDatabasePath)) {
            throw new JobConfigApiException(503, 'CONTROLLER_DATABASE_NOT_WRITABLE', 'Controller database was not found or is not writable');
        }

        $this->assertControllerLoggedOut();

        return [
            'controller_root_exists' => true,
            'controller_database_exists' => true,
            'controller_database_readable' => true,
            'controller_database_writable' => true,
            'controller_logged_out' => true,
            'ready' => true,
        ];
    }

    public function replace(array $payload): array
    {
        $normalized = $this->normalizeNativeAndValidate($payload);
        $this->preflight();

        $lockPath = $this->controllerDatabasePath . '.job-config-api.lock';
        $lock = @fopen($lockPath, 'c');
        if (!$lock || !@flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) @fclose($lock);
            throw new JobConfigApiException(409, 'WRITE_BUSY', 'Another JOB configuration write is running');
        }

        $stage = $this->controllerDatabasePath . '.api-stage.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $backup = null;
        try {
            // Re-check immediately after the write lock is acquired.  This
            // catches a Controller login or filesystem change between the
            // batch preflight and the actual database write.
            $this->preflight();
            $this->createConsistentSnapshot($this->controllerDatabasePath, $stage);
            $counts = $this->writeStage($stage, $normalized);

            $backup = $this->makeBackup();
            // Update the live Controller SQLite file in-place. This preserves the
            // inode used by the long-running Controller process while the SQL
            // transaction guarantees all-or-nothing JOB/SEQ/STEP upsert.
            $this->writeStage($this->controllerDatabasePath, $normalized);

            $mirrorSynced = $this->syncIdasMirror();

            $this->rotateBackups(5);
            return $this->result($normalized, $counts, true, $mirrorSynced);
        } catch (JobConfigApiException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new JobConfigApiException(500, 'DATABASE_ERROR', 'JOB configuration write failed');
        } finally {
            @unlink($stage);
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }
    }

    /** Accept the Controller database's native table/column JSON format. */
    private function normalizeNativeAndValidate(array $payload): array
    {
        $tables = ['JOB_lst', 'SEQ_lst', 'STEP_lst'];
        $this->rejectUnknownKeys($payload, $tables, '$');
        foreach ($tables as $table) {
            if (!isset($payload[$table]) || !is_array($payload[$table]) || $payload[$table] === []) {
                $this->validation($table . ' must be a non-empty array', '$.' . $table);
            }
        }
        if (count($payload['JOB_lst']) > 100) {
            $this->validation('JOB_lst supports at most 100 JOB rows', '$.JOB_lst');
        }

        $jobs = [];
        $jobIds = [];
        foreach (array_values($payload['JOB_lst']) as $index => $job) {
            $path = '$.JOB_lst[' . $index . ']';
            if (!is_array($job)) $this->validation('row must be an object', $path);
            $this->rejectUnknownKeys($job, $this->jobColumns(), $path);
            $jobId = $this->requiredInt($job, 'JOBID', 1, 100, $path);
            if (in_array($jobId, self::RESERVED_JOB_IDS, true)) $this->validation('reserved JOB ID', $path . '.JOBID');
            if (isset($jobIds[$jobId])) $this->validation('duplicate JOBID', $path . '.JOBID');
            $jobIds[$jobId] = true;
            $row = array_replace($this->jobDefaults($jobId), $job);
            $row['JOBID'] = $jobId;
            $row['JOBname'] = $this->text($row['JOBname'], 1, 64, $path . '.JOBname');
            $jobs[] = $row;
        }

        $sequences = [];
        $sequenceKeys = [];
        $sequenceCountByJob = [];
        foreach (array_values($payload['SEQ_lst']) as $index => $sequence) {
            $path = '$.SEQ_lst[' . $index . ']';
            if (!is_array($sequence)) $this->validation('row must be an object', $path);
            $this->rejectUnknownKeys($sequence, $this->sequenceColumns(), $path);
            $jobId = $this->requiredInt($sequence, 'JOBID', 1, 100, $path);
            $seqId = $this->requiredInt($sequence, 'SEQID', 1, 50, $path);
            if (!isset($jobIds[$jobId])) $this->validation('JOBID does not exist in JOB_lst', $path . '.JOBID');
            $sequenceCountByJob[$jobId] = ($sequenceCountByJob[$jobId] ?? 0) + 1;
            if ($sequenceCountByJob[$jobId] > 50) $this->validation('each JOB supports at most 50 SEQ rows', $path);
            $key = $jobId . ':' . $seqId;
            if (isset($sequenceKeys[$key])) $this->validation('duplicate JOBID/SEQID', $path);
            $sequenceKeys[$key] = true;
            $row = array_replace($this->sequenceDefaults($jobId, $seqId), $sequence);
            $row['JOBID'] = $jobId;
            $row['SEQID'] = $seqId;
            $row['SEQname'] = $this->text($row['SEQname'], 1, 64, $path . '.SEQname');
            $sequences[] = $row;
        }

        $steps = [];
        $stepKeys = [];
        $stepCountBySequence = [];
        foreach (array_values($payload['STEP_lst']) as $index => $step) {
            $path = '$.STEP_lst[' . $index . ']';
            if (!is_array($step)) $this->validation('row must be an object', $path);
            $this->rejectUnknownKeys($step, $this->stepColumns(), $path);
            $jobId = $this->requiredInt($step, 'JOBID', 1, 100, $path);
            $seqId = $this->requiredInt($step, 'SEQID', 1, 50, $path);
            $stepId = $this->requiredInt($step, 'StepSelect', 1, 5, $path);
            $sequenceKey = $jobId . ':' . $seqId;
            if (!isset($sequenceKeys[$sequenceKey])) $this->validation('JOBID/SEQID does not exist in SEQ_lst', $path);
            $key = $sequenceKey . ':' . $stepId;
            if (isset($stepKeys[$key])) $this->validation('duplicate JOBID/SEQID/StepSelect', $path);
            $stepKeys[$key] = true;
            $stepCountBySequence[$sequenceKey] = ($stepCountBySequence[$sequenceKey] ?? 0) + 1;
            if ($stepCountBySequence[$sequenceKey] > 5) $this->validation('each SEQ supports at most 5 STEP rows', $path);
            $row = array_replace($this->stepDefaults($jobId, $seqId, $stepId), $step);
            $row['JOBID'] = $jobId;
            $row['SEQID'] = $seqId;
            $row['StepSelect'] = $stepId;
            $row['STEPname'] = $this->text($row['STEPname'], 1, 64, $path . '.STEPname');
            $this->validateStep($row, $path);
            $steps[] = $row;
        }

        foreach ($sequenceKeys as $key => $unused) {
            if (($stepCountBySequence[$key] ?? 0) < 1) {
                $this->validation('every SEQ_lst row needs at least one STEP_lst row', '$.STEP_lst');
            }
        }

        return ['jobs' => $jobs, 'sequences' => $sequences, 'steps' => $steps];
    }

    private function validateStep(array $s, string $path): void
    {
        foreach (['StepSwitch', 'StepDirection', 'InterruptAlarm', 'OverAngleStop'] as $key) {
            if (!in_array((int)$s[$key], [0, 1], true)) $this->validation($key . ' must be 0 or 1', $path);
        }
        if (!in_array((int)$s['StepOption'], [0, 1, 2], true)) $this->validation('StepOption must be 0 (Time), 1 (Angle), or 2 (Torque)', $path . '.StepOption');
        $minimumRpm = (int)$s['StepSelect'] === 1 ? 50 : 100;
        if ((float)$s['StepRPM'] < $minimumRpm) $this->validation('rpm must be at least ' . $minimumRpm, $path . '.rpm');
        if ((float)$s['StepLoAngle'] < 0 || (float)$s['StepHiAngle'] > 30600 || (float)$s['StepLoAngle'] >= (float)$s['StepHiAngle']) {
            $this->validation('angle_lower must be less than angle_upper (0..30600)', $path);
        }
        if ((float)$s['StepLoTorque'] < 0 || (float)$s['StepLoTorque'] >= (float)$s['StepHiTorque']) {
            $this->validation('torque_lower must be less than torque_upper', $path);
        }
        if ((int)$s['StepOption'] === 1 && !((float)$s['StepLoAngle'] < (float)$s['StepAngle'] && (float)$s['StepAngle'] < (float)$s['StepHiAngle'])) {
            $this->validation('StepAngle must be between StepLoAngle and StepHiAngle', $path . '.StepAngle');
        }
        if ((int)$s['StepOption'] === 2 && !((float)$s['StepLoTorque'] < (float)$s['StepTorque'] && (float)$s['StepTorque'] < (float)$s['StepHiTorque'])) {
            $this->validation('StepTorque must be between StepLoTorque and StepHiTorque', $path . '.StepTorque');
        }
        if ((int)$s['StepOption'] === 0 && (float)$s['StepTime'] <= 0) {
            $this->validation('StepTime must be greater than 0 in Time mode', $path . '.StepTime');
        }
    }

    private function writeStage(string $stage, array $data): array
    {
        $pdo = idas_sqlite_connect($stage, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $tables = ['JOB_lst', 'SEQ_lst', 'STEP_lst'];
        $primaryKeys = [
            'JOB_lst' => ['JOBID'],
            'SEQ_lst' => ['JOBID', 'SEQID'],
            'STEP_lst' => ['JOBID', 'SEQID', 'StepSelect'],
        ];
        $columns = [];
        foreach ($tables as $table) {
            $exists = $pdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($table))->fetchColumn();
            if (!$exists) throw new JobConfigApiException(500, 'DATABASE_SCHEMA_MISMATCH', 'Missing table: ' . $table);
            $columns[$table] = array_column($pdo->query('PRAGMA table_info(' . $table . ')')->fetchAll(PDO::FETCH_ASSOC), 'name');
        }

        try {
            $pdo->beginTransaction();
            $counts = [
                'jobs' => 0,
                'sequences' => 0,
                'steps' => 0,
                'inserted' => ['jobs' => 0, 'sequences' => 0, 'steps' => 0],
                'updated' => ['jobs' => 0, 'sequences' => 0, 'steps' => 0],
            ];
            foreach ($data['jobs'] as $job) {
                $operation = $this->upsertRow($pdo, 'JOB_lst', $columns['JOB_lst'], $primaryKeys['JOB_lst'], $job);
                $counts['jobs']++;
                $counts[$operation]['jobs']++;
            }
            foreach ($data['sequences'] as $sequence) {
                $operation = $this->upsertRow($pdo, 'SEQ_lst', $columns['SEQ_lst'], $primaryKeys['SEQ_lst'], $sequence);
                $counts['sequences']++;
                $counts[$operation]['sequences']++;
            }
            foreach ($data['steps'] as $step) {
                $operation = $this->upsertRow($pdo, 'STEP_lst', $columns['STEP_lst'], $primaryKeys['STEP_lst'], $step);
                $counts['steps']++;
                $counts[$operation]['steps']++;
            }
            $check = strtolower(trim((string)$pdo->query('PRAGMA quick_check')->fetchColumn()));
            if ($check !== 'ok') throw new RuntimeException('SQLite quick_check failed: ' . $check);
            $pdo->commit();
        } catch (JobConfigApiException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $reason = (string)$e->getMessage();
            if (stripos($reason, 'database is locked') !== false || stripos($reason, 'database is busy') !== false) {
                throw new JobConfigApiException(409, 'CONTROLLER_DATABASE_BUSY', 'Controller database is busy; retry later');
            }
            throw new JobConfigApiException(400, 'DATABASE_WRITE_REJECTED', 'Database rejected the JOB configuration', ['reason' => $e->getMessage()]);
        }

        return $counts;
    }

    private function upsertRow(PDO $pdo, string $table, array $tableColumns, array $primaryKeys, array $row): string
    {
        $values = array_intersect_key($row, array_flip($tableColumns));
        if ($values === []) throw new RuntimeException('No compatible columns for ' . $table);
        foreach ($primaryKeys as $primaryKey) {
            if (!array_key_exists($primaryKey, $values)) throw new RuntimeException('Missing primary key ' . $primaryKey . ' for ' . $table);
        }

        $names = array_keys($values);
        $quoted = array_map(static function ($name) { return '"' . str_replace('"', '""', $name) . '"'; }, $names);
        $placeholders = array_map(static function ($name) { return ':' . $name; }, $names);
        $where = [];
        foreach ($primaryKeys as $primaryKey) $where[] = '"' . str_replace('"', '""', $primaryKey) . '" = :pk_' . $primaryKey;
        $exists = $pdo->prepare('SELECT 1 FROM "' . $table . '" WHERE ' . implode(' AND ', $where) . ' LIMIT 1');
        foreach ($primaryKeys as $primaryKey) $exists->bindValue(':pk_' . $primaryKey, $values[$primaryKey]);
        $exists->execute();

        if ($exists->fetchColumn()) {
            $updates = [];
            foreach ($names as $name) {
                if (!in_array($name, $primaryKeys, true)) {
                    $updates[] = '"' . str_replace('"', '""', $name) . '" = :set_' . $name;
                }
            }
            if ($updates !== []) {
                $stmt = $pdo->prepare('UPDATE "' . $table . '" SET ' . implode(',', $updates) . ' WHERE ' . implode(' AND ', $where));
                foreach ($names as $name) {
                    if (!in_array($name, $primaryKeys, true)) $stmt->bindValue(':set_' . $name, $values[$name]);
                }
                foreach ($primaryKeys as $primaryKey) $stmt->bindValue(':pk_' . $primaryKey, $values[$primaryKey]);
                $stmt->execute();
            }
            return 'updated';
        }

        $stmt = $pdo->prepare('INSERT INTO "' . $table . '" (' . implode(',', $quoted) . ') VALUES (' . implode(',', $placeholders) . ')');
        foreach ($values as $name => $value) $stmt->bindValue(':' . $name, $value);
        $stmt->execute();
        return 'inserted';
    }

    private function createConsistentSnapshot(string $source, string $target): void
    {
        @unlink($target);
        try {
            $pdo = idas_sqlite_connect($source, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $pdo->exec("VACUUM INTO " . $pdo->quote($target));
            $pdo = null;
        } catch (Throwable $e) {
            @unlink($target);
            $pdo = idas_sqlite_connect($source, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            try { $pdo->exec('PRAGMA wal_checkpoint(FULL)'); } catch (Throwable $ignored) {}
            $pdo = null;
            if (!@copy($source, $target)) throw new RuntimeException('Snapshot copy failed');
        }
        if (!is_file($target)) throw new RuntimeException('Snapshot was not created');
    }

    private function makeBackup(): string
    {
        $backup = $this->controllerDatabasePath . '.api-backup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(2));
        try {
            // KLS_NTCS.Lin normally runs in WAL mode. VACUUM INTO creates a
            // consistent one-file backup containing the committed WAL pages.
            $this->createConsistentSnapshot($this->controllerDatabasePath, $backup);
            $pdo = idas_sqlite_connect($backup, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $healthy = strtolower(trim((string)$pdo->query('PRAGMA quick_check')->fetchColumn())) === 'ok';
            $pdo = null;
        } catch (Throwable $e) {
            $healthy = false;
        }
        if (!$healthy) {
            @unlink($backup);
            throw new JobConfigApiException(500, 'BACKUP_FAILED', 'Could not back up the current database');
        }
        return $backup;
    }

    private function rotateBackups(int $keep): void
    {
        $files = glob($this->controllerDatabasePath . '.api-backup-*') ?: [];
        rsort($files, SORT_STRING);
        foreach (array_slice($files, $keep) as $file) @unlink($file);
    }

    private function syncIdasMirror(): bool
    {
        $temporary = $this->idasMirrorPath . '.api-stage.' . getmypid() . '.' . bin2hex(random_bytes(4));
        try {
            $directory = dirname($this->idasMirrorPath);
            if (!is_dir($directory) || !is_writable($directory)) return false;
            $this->createConsistentSnapshot($this->controllerDatabasePath, $temporary);
            $mode = is_file($this->idasMirrorPath) ? @fileperms($this->idasMirrorPath) : false;
            if (!@rename($temporary, $this->idasMirrorPath)) return false;
            if ($mode !== false) @chmod($this->idasMirrorPath, $mode & 0777);
            return true;
        } catch (Throwable $ignored) {
            return false;
        } finally {
            @unlink($temporary);
        }
    }

    private function assertControllerLoggedOut(): void
    {
        $controller = call_user_func($this->controllerFactory);
        $unitId = $this->activeUnitId();
        $status = $controller->idas_check($unitId);
        if (!is_array($status) || !empty($status['error']) || !array_key_exists('result', $status) || $status['result'] === null) {
            throw new JobConfigApiException(502, 'CONTROLLER_STATUS_UNAVAILABLE', 'Could not read Controller login status');
        }
        if ((int)$status['result'] !== 0) {
            throw new JobConfigApiException(409, 'CONTROLLER_IN_USE', 'Controller must be logged out before writing JOB configuration');
        }
    }

    private function activeUnitId(): int
    {
        if (!is_file($this->deviceDatabasePath)) return 1;
        try {
            $pdo = idas_sqlite_connect($this->deviceDatabasePath, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $columns = array_column($pdo->query('PRAGMA table_info(ntcs_device_test)')->fetchAll(PDO::FETCH_ASSOC), 'name');
            foreach (['device_id', 'control_id', 'modbus_id'] as $column) {
                if (in_array($column, $columns, true)) {
                    $value = (int)$pdo->query('SELECT "' . $column . '" FROM ntcs_device_test LIMIT 1')->fetchColumn();
                    if ($value >= 1 && $value <= 255) return $value;
                }
            }
        } catch (Throwable $ignored) {
        }
        return 1;
    }

    private function result(array $normalized, array $counts, bool $applied, bool $mirrorSynced): array
    {
        return [
            'operation' => 'upsert',
            'controller_updated' => $applied,
            'idas_mirror_synced' => $mirrorSynced,
            'counts' => $counts,
            'warnings' => $mirrorSynced ? [] : ['Controller DB was updated, but the iDAS mirror could not be refreshed'],
        ];
    }

    private function jobDefaults(int $jobId): array
    {
        return ['JOBID'=>$jobId,'JOBname'=>'JOB-'.$jobId,'type'=>1,'time'=>call_user_func($this->clock),'act'=>0,'ok_job'=>1,'ok_job_stop'=>0,'output_unified'=>0,'input_unified'=>0,'job_unit'=>0];
    }

    private function sequenceDefaults(int $jobId, int $seqId): array
    {
        return ['JOBID'=>$jobId,'SEQID'=>$seqId,'SEQname'=>'SEQ-'.$seqId,'type'=>0,'time'=>call_user_func($this->clock),'act'=>0,'skip'=>0,'seq_repeat'=>1,'timeout'=>20,'ok_seq'=>1,'ok_stop'=>0,'countType'=>1,'ok_screw'=>1,'ng_stop'=>0,'ng_unscrew'=>0,'interrupt_alarm'=>1,'accu_angle'=>1,'Thread_Calcu'=>31,'unscrew_mode'=>1,'unscrew_force'=>50,'unscrew_rpm'=>300,'unscrew_dir'=>0,'image'=>null,'message'=>null,'delay'=>0,'input'=>0,'input_signal'=>1,'output'=>0,'output_signal'=>1,'output_durat'=>100,'addtion'=>null,'unscrew_count_switch'=>0,'unscrew_torque_threshold'=>0,'seq_unit'=>1,'unscrew_angle_threshold'=>0,'dt_time'=>0,'tt_time'=>0,'total_angle_limit'=>0,'total_angle_lower'=>0];
    }

    private function stepDefaults(int $jobId, int $seqId, int $stepId): array
    {
        return ['JOBID'=>$jobId,'SEQID'=>$seqId,'StepSelect'=>$stepId,'STEPname'=>'STEP-'.$stepId,'type'=>0,'time'=>call_user_func($this->clock),'act'=>0,'StepSwitch'=>1,'StepRPM'=>500,'StepOption'=>2,'StepTime'=>1000,'StepAngle'=>3000,'StepTorque'=>1.0,'StepDirection'=>1,'StepDelay'=>0,'StepMoniByWin'=>0,'StepLimiHi'=>30,'StepLimiLo'=>30,'StepHiAngle'=>30600,'StepLoAngle'=>0,'StepHiTorque'=>2.0,'StepLoTorque'=>0.1,'StepAccelerateOffset'=>0.2,'StepAccelerateOffsetSign'=>43,'StepEnableTorqueOffset'=>0,'StepTorqueOffset'=>0,'StepTorqueOffsetSign'=>43,'StepEnableDownShift'=>0,'StepTorqueDownShift'=>0,'StepRPMDownShift'=>0,'StepEnableThreshold'=>0,'StepTorqueTS'=>0,'StepReTry'=>0,'StepUnScrew'=>1,'StepReTryTorq'=>0,'StepReTryAngl'=>0,'StepAngleRecord'=>0,'StepAutoDetectAngle'=>0,'InterruptAlarm'=>1,'OverAngleStop'=>1,'KValue'=>100,'step_unit'=>1];
    }

    private function jobColumns(): array
    {
        return ['JOBID','JOBname','type','time','act','ok_job','ok_job_stop','output_unified','input_unified','job_unit'];
    }

    private function sequenceColumns(): array
    {
        return ['JOBID','SEQID','SEQname','type','time','act','skip','seq_repeat','timeout','ok_seq','ok_stop','countType','ok_screw','ng_stop','ng_unscrew','interrupt_alarm','accu_angle','Thread_Calcu','unscrew_mode','unscrew_force','unscrew_rpm','unscrew_dir','image','message','delay','input','input_signal','output','output_signal','output_durat','addtion','unscrew_count_switch','unscrew_torque_threshold','seq_unit','unscrew_angle_threshold','dt_time','tt_time','total_angle_limit','total_angle_lower'];
    }

    private function stepColumns(): array
    {
        return ['JOBID','SEQID','StepSelect','STEPname','type','time','act','StepSwitch','StepRPM','StepOption','StepTime','StepAngle','StepTorque','StepDirection','StepDelay','StepMoniByWin','StepLimiHi','StepLimiLo','StepHiAngle','StepLoAngle','StepHiTorque','StepLoTorque','StepAccelerateOffset','StepAccelerateOffsetSign','StepEnableTorqueOffset','StepTorqueOffset','StepTorqueOffsetSign','StepEnableDownShift','StepTorqueDownShift','StepRPMDownShift','StepEnableThreshold','StepTorqueTS','StepReTry','StepUnScrew','StepReTryTorq','StepReTryAngl','StepAngleRecord','StepAutoDetectAngle','InterruptAlarm','OverAngleStop','KValue','step_unit'];
    }

    private function requiredInt(array $data, string $key, int $min, int $max, string $path): int
    {
        if (!array_key_exists($key, $data) || filter_var($data[$key], FILTER_VALIDATE_INT) === false) $this->validation($key . ' must be an integer', $path . '.' . $key);
        $value = (int)$data[$key];
        if ($value < $min || $value > $max) $this->validation($key . " must be between {$min} and {$max}", $path . '.' . $key);
        return $value;
    }

    private function text($value, int $min, int $max, string $path): string
    {
        if (!is_string($value)) $this->validation('must be a string', $path);
        $value = trim($value);
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
        if ($length < $min || $length > $max) $this->validation("length must be {$min}..{$max}", $path);
        return $value;
    }

    private function rejectUnknownKeys(array $data, array $allowed, string $path): void
    {
        $unknown = array_values(array_diff(array_keys($data), $allowed));
        if ($unknown !== []) $this->validation('unknown field: ' . $unknown[0], $path . '.' . $unknown[0]);
    }

    private function validation(string $message, string $path): void
    {
        throw new JobConfigApiException(400, 'VALIDATION_ERROR', $message, ['path' => $path]);
    }
}
