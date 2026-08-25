<?php

final class SystemConfigImportService
{
    public static function normalizeLanguage(string $language): string
    {
        $language = strtolower($language);
        if ($language === 'en') $language = 'en-us';
        return in_array($language, ['en-us', 'zh-tw', 'zh-cn'], true) ? $language : 'en-us';
    }

    public static function validateUploadedLin(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) return ['ok' => false, 'error' => 'bad_upload'];
        $name = basename((string)($file['name'] ?? ''));
        $tmp = (string)($file['tmp_name'] ?? '');
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'lin') return ['ok' => false, 'error' => 'bad_ext'];
        if (!preg_match('/^con_[A-Za-z0-9_-]+_[0-9]{14}\.Lin$/i', $name)) return ['ok' => false, 'error' => 'bad_name'];
        if ($tmp === '' || !is_uploaded_file($tmp)) return ['ok' => false, 'error' => 'bad_upload'];
        try {
            $db = new PDO('sqlite:' . $tmp);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            if (strtolower((string)$db->query('PRAGMA integrity_check')->fetchColumn()) !== 'ok') return ['ok' => false, 'error' => 'not_sqlite'];
            $table = $db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='SEQ_type' LIMIT 1")->fetchColumn();
            if (!$table) return ['ok' => false, 'error' => 'missing_table'];
            self::normalizeServiceAccount($db);
            return ['ok' => true, 'tmp_name' => $tmp, 'name' => $name];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'not_sqlite', 'exception' => $e->getMessage()];
        }
    }

    /** The only account migration supported by NTCS7/iController: kls -> service. */
    public static function normalizeServiceAccount(PDO $db): array
    {
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        if (!$db->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name='user' LIMIT 1")->fetchColumn())
            return ['changed' => false, 'reason' => 'user_table_missing'];
        $columns = array_map(static fn($c) => strtolower((string)($c['name'] ?? '')), $db->query('PRAGMA table_info("user")')->fetchAll(PDO::FETCH_ASSOC));
        if (!in_array('name', $columns, true)) throw new RuntimeException('Uploaded LIN user table does not contain the name column.');
        $find = $db->prepare('SELECT COUNT(*) FROM "user" WHERE LOWER(TRIM("name")) = :name');
        $find->execute([':name' => 'kls']); $kls = (int)$find->fetchColumn();
        if ($kls === 0) return ['changed' => false, 'reason' => 'kls_not_found'];
        $find->execute([':name' => 'service']); $service = (int)$find->fetchColumn();
        $db->beginTransaction();
        try {
            if ($service > 0) {
                $stmt = $db->prepare('DELETE FROM "user" WHERE LOWER(TRIM("name")) = :name');
                $stmt->execute([':name' => 'kls']); $action = 'removed_duplicate_kls';
            } else {
                $stmt = $db->prepare('UPDATE "user" SET "name" = :service WHERE LOWER(TRIM("name")) = :kls');
                $stmt->execute([':service' => 'service', ':kls' => 'kls']); $action = 'renamed_kls_to_service';
            }
            $db->commit();
            return ['changed' => true, 'action' => $action, 'legacy_rows' => $kls];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }
}
