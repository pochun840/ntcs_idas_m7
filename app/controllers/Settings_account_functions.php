
    /* =====================================================
     * Setting Account / table user
     * Rule: only English letters and numbers are allowed.
     *       Regex uses 0-9 because existing examples like steve01/admin0734 need 0.
     * ===================================================== */

    private function accountUserDbPath(): string
    {
        $candidates = [];

        if (PHP_OS_FAMILY === 'Linux') {
            $candidates[] = '/var/www/html/database/KLS_NTCS_IDAS.Lin';
            $candidates[] = '/home/kls/NTCS7/KLS_NTCS.Lin';
        } else {
            $candidates[] = __DIR__ . '/../../database/KLS_NTCS_IDAS.Lin';
            $candidates[] = __DIR__ . '/../../../database/KLS_NTCS_IDAS.Lin';
            $candidates[] = '../database/KLS_NTCS_IDAS.Lin';
        }

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        // default path for error display
        return $candidates[0] ?? '/var/www/html/database/KLS_NTCS_IDAS.Lin';
    }

    private function accountUserDb(): PDO
    {
        $dbPath = $this->accountUserDbPath();

        if (!is_file($dbPath)) {
            throw new Exception('Account DB not found: ' . $dbPath);
        }

        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $db;
    }

    private function accountUserJson(bool $ok, string $msg, array $extra = []): void
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo json_encode(array_merge([
            'success'  => $ok,
            'res_type' => $ok ? 'Success' : 'Error',
            'res_msg'  => $msg,
        ], $extra), JSON_UNESCAPED_UNICODE);
        exit();
    }

    private function accountUserClean(string $value): string
    {
        return trim($value);
    }

    private function accountUserCurrentUsername(): string
    {
        return strtolower(trim((string)($_COOKIE['username'] ?? '')));
    }

    private function accountUserRequireAdmin(): void
    {
        // Account 管理功能只允許 cookie username=admin 使用。
        if ($this->accountUserCurrentUsername() !== 'admin') {
            $this->accountUserJson(false, 'Only admin can use Account setting.');
        }
    }

    private function accountUserValidateText(string $value, string $label): void
    {
        if ($value === '') {
            throw new Exception($label . ' cannot be empty.');
        }

        // 既有帳號 key 檢查：允許 A-Z / a-z / 0-9。
        if (!preg_match('/^[A-Za-z0-9]+$/', $value)) {
            throw new Exception($label . ' only allows A-Z, a-z, 0-9.');
        }
    }

    private function accountUserValidateUsername(string $value, string $label = 'Username'): void
    {
        if ($value === '') {
            throw new Exception($label . ' cannot be empty.');
        }

        // 新帳號 / 改名：6~8 字元，只允許 A-Z / a-z / 0-9。
        if (!preg_match('/^[A-Za-z0-9]{6,8}$/', $value)) {
            throw new Exception($label . ' must be 6 to 8 characters and only allows A-Z, a-z, 0-9.');
        }
    }

    private function accountUserValidatePassword(string $value, string $label = 'Password'): void
    {
        if ($value === '') {
            throw new Exception($label . ' cannot be empty.');
        }

        // 密碼固定 4 碼數字，允許 0000。
        if (!preg_match('/^[0-9]{4}$/', $value)) {
            throw new Exception($label . ' must be exactly 4 digits, 0-9.');
        }
    }

    private function accountUserAssertTable(PDO $db): void
    {
        $exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user'")->fetchColumn();
        if (!$exists) {
            throw new Exception('table user not found.');
        }
    }

    private function accountUserIdasDbPathStrict(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            return '/var/www/html/database/KLS_NTCS_IDAS.Lin';
        }

        return $this->accountUserDbPath();
    }

    private function accountUserControllerDbPath(): string
    {
        if (PHP_OS_FAMILY === 'Linux') {
            return '/home/kls/NTCS7/KLS_NTCS.Lin';
        }

        return __DIR__ . '/../../../database/KLS_NTCS.Lin';
    }

    private function accountUserOpenSqliteFile(string $dbPath): PDO
    {
        if (!is_file($dbPath)) {
            throw new Exception('DB file not found: ' . $dbPath);
        }
        if (!is_readable($dbPath)) {
            throw new Exception('DB file is not readable: ' . $dbPath);
        }

        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $db;
    }

    private function accountUserQuoteIdentifier(string $name): string
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name)) {
            throw new Exception('Invalid column name: ' . $name);
        }

        return '"' . str_replace('"', '""', $name) . '"';
    }

    private function accountUserTableColumns(PDO $db): array
    {
        $rows = $db->query('PRAGMA table_info("user")')->fetchAll(PDO::FETCH_ASSOC);
        $columns = [];

        foreach ($rows as $row) {
            if (isset($row['name']) && $row['name'] !== '') {
                $columns[] = (string)$row['name'];
            }
        }

        return $columns;
    }

    public function account_user_upload_controller(): void
    {
        $targetDb = null;

        try {
            $this->accountUserRequireAdmin();

            if (PHP_OS_FAMILY !== 'Linux') {
                throw new Exception('Upload to controller DB is only supported on Linux.');
            }

            $sourcePath = $this->accountUserIdasDbPathStrict();
            $targetPath = $this->accountUserControllerDbPath();

            if (!is_file($sourcePath)) {
                throw new Exception('Source iDAS DB not found: ' . $sourcePath);
            }
            if (!is_file($targetPath)) {
                throw new Exception('Target controller DB not found: ' . $targetPath);
            }
            if (!is_writable($targetPath) || !is_writable(dirname($targetPath))) {
                throw new Exception('Target controller DB or folder is not writable: ' . $targetPath);
            }

            $sourceDb = $this->accountUserOpenSqliteFile($sourcePath);
            $targetDb = $this->accountUserOpenSqliteFile($targetPath);

            $this->accountUserAssertTable($sourceDb);
            $this->accountUserAssertTable($targetDb);

            $sourceColumns = $this->accountUserTableColumns($sourceDb);
            $targetColumns = $this->accountUserTableColumns($targetDb);
            $copyColumns = array_values(array_intersect($targetColumns, $sourceColumns));

            if (empty($copyColumns)) {
                throw new Exception('No matching columns found between source and target user table.');
            }
            if (!in_array('name', $copyColumns, true) || !in_array('passwd', $copyColumns, true)) {
                throw new Exception('Target/source user table must contain name and passwd columns.');
            }

            $columnSql = implode(', ', array_map([$this, 'accountUserQuoteIdentifier'], $copyColumns));
            $orderSql = in_array('sn', $sourceColumns, true) ? ' ORDER BY "sn" ASC' : ' ORDER BY rowid ASC';

            $rows = $sourceDb->query('SELECT ' . $columnSql . ' FROM "user"' . $orderSql)->fetchAll(PDO::FETCH_ASSOC);
            if (empty($rows)) {
                throw new Exception('Source user table has no data. Upload aborted.');
            }

            // Backup target DB before touching table user only.
            $backupPath = $targetPath . '.user_backup_' . date('Ymd_His');
            if (!@copy($targetPath, $backupPath)) {
                throw new Exception('Backup target DB failed: ' . $backupPath);
            }
            @chmod($backupPath, 0666);

            $targetDb->beginTransaction();

            // Only table user is modified. Other tables are untouched.
            $targetDb->exec('DELETE FROM "user"');

            $placeholders = implode(', ', array_map(function($col) {
                return ':' . $col;
            }, $copyColumns));

            $insertSql = 'INSERT INTO "user" (' . $columnSql . ') VALUES (' . $placeholders . ')';
            $insertStmt = $targetDb->prepare($insertSql);

            foreach ($rows as $row) {
                $params = [];
                foreach ($copyColumns as $col) {
                    $params[':' . $col] = $row[$col] ?? null;
                }
                $insertStmt->execute($params);
            }

            $targetDb->commit();
            @chmod($targetPath, 0666);
            @exec('sync');

            $this->accountUserJson(true, 'Upload user list to controller success. Rows: ' . count($rows) . '.', [
                'rows'        => count($rows),
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'backup_path' => $backupPath,
            ]);
        } catch (Throwable $e) {
            if ($targetDb instanceof PDO && $targetDb->inTransaction()) {
                $targetDb->rollBack();
            }

            $this->accountUserJson(false, 'Upload user list to controller failed: ' . $e->getMessage());
        }
    }

    public function account_user_list(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $rows = $db->query("SELECT sn, name, passwd, law FROM user ORDER BY sn ASC")->fetchAll();

            $this->accountUserJson(true, 'OK', [
                'records' => $rows,
                'count'   => count($rows),
            ]);
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }


    public function account_user_get_password(): void
    {
        try {
            $this->accountUserRequireAdmin();

            $username = isset($_POST['username']) ? $this->accountUserClean((string)$_POST['username']) : '';
            $this->accountUserValidateText($username, 'Username');

            if (strtolower($username) === 'kls') {
                throw new Exception('Built-in account is hidden.');
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $statement = $db->prepare("
                SELECT CAST(passwd AS TEXT) AS passwd
                FROM `user`
                WHERE LOWER(name) = LOWER(:name)
                  AND LOWER(name) <> 'kls'
                LIMIT 1
            ");
            $statement->execute([
                ':name' => $username,
            ]);

            $row = $statement->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                throw new Exception('Account not found.');
            }

            $this->accountUserJson(true, 'OK', [
                'username' => $username,
                'passwd'   => (string)($row['passwd'] ?? ''),
            ]);
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }


    public function account_user_create(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $name     = $this->accountUserClean($_POST['username'] ?? '');
            $password = $this->accountUserClean($_POST['password'] ?? '');
            $confirm  = $this->accountUserClean($_POST['confirm_password'] ?? '');
            $law      = isset($_POST['law']) ? (int)$_POST['law'] : 1;

            $this->accountUserValidateUsername($name, 'Username');
            $this->accountUserValidatePassword($password, 'Password');

            if ($password !== $confirm) {
                throw new Exception('Confirm password is different.');
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $stmt = $db->prepare("SELECT COUNT(*) FROM user WHERE name = :name");
            $stmt->execute([':name' => $name]);
            if ((int)$stmt->fetchColumn() > 0) {
                throw new Exception('Username already exists.');
            }

            $nextSn = (int)$db->query("SELECT COALESCE(MAX(sn), -1) + 1 FROM user")->fetchColumn();

            $stmt = $db->prepare("INSERT INTO user (sn, name, passwd, law) VALUES (:sn, :name, :passwd, :law)");
            $stmt->execute([
                ':sn'     => $nextSn,
                ':name'   => $name,
                ':passwd' => $password,
                ':law'    => $law,
            ]);

            $this->accountUserJson(true, 'New Account success.');
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }

    public function account_user_update(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $oldName  = $this->accountUserClean($_POST['old_username'] ?? '');
            $name     = $this->accountUserClean($_POST['username'] ?? '');
            $password = $this->accountUserClean($_POST['password'] ?? '');
            $confirm  = $this->accountUserClean($_POST['confirm_password'] ?? '');
            $law      = isset($_POST['law']) ? (int)$_POST['law'] : 1;

            $this->accountUserValidateText($oldName, 'Old username');
            if ($oldName !== $name) {
                $this->accountUserValidateUsername($name, 'Username');
            } else {
                // 允許既有 guest/admin/user1 等舊帳號在未改名時繼續修改密碼。
                $this->accountUserValidateText($name, 'Username');
            }

            if ($password !== '') {
                $this->accountUserValidatePassword($password, 'Password');
                if ($password !== $confirm) {
                    throw new Exception('Confirm password is different.');
                }
            }

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $stmt = $db->prepare("SELECT COUNT(*) FROM user WHERE name = :name");
            $stmt->execute([':name' => $oldName]);
            if ((int)$stmt->fetchColumn() === 0) {
                throw new Exception('Account not found.');
            }

            if ($oldName !== $name) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM user WHERE name = :name");
                $stmt->execute([':name' => $name]);
                if ((int)$stmt->fetchColumn() > 0) {
                    throw new Exception('Username already exists.');
                }
            }

            if ($password === '') {
                $stmt = $db->prepare("UPDATE user SET name = :name, law = :law WHERE name = :old_name");
                $stmt->execute([
                    ':name'     => $name,
                    ':law'      => $law,
                    ':old_name' => $oldName,
                ]);
            } else {
                $stmt = $db->prepare("UPDATE user SET name = :name, passwd = :passwd, law = :law WHERE name = :old_name");
                $stmt->execute([
                    ':name'     => $name,
                    ':passwd'   => $password,
                    ':law'      => $law,
                    ':old_name' => $oldName,
                ]);
            }

            $this->accountUserJson(true, 'Edit Account success.');
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }

    public function account_user_delete(): void
    {
        try {
            $this->accountUserRequireAdmin();
            $name = $this->accountUserClean($_POST['username'] ?? '');
            $this->accountUserValidateText($name, 'Username');

            $db = $this->accountUserDb();
            $this->accountUserAssertTable($db);

            $count = (int)$db->query("SELECT COUNT(*) FROM user")->fetchColumn();
            if ($count <= 1) {
                throw new Exception('Cannot delete the last account.');
            }

            $stmt = $db->prepare("DELETE FROM user WHERE name = :name");
            $stmt->execute([':name' => $name]);

            if ($stmt->rowCount() <= 0) {
                throw new Exception('Account not found.');
            }

            $this->accountUserJson(true, 'Delete Account success.');
        } catch (Throwable $e) {
            $this->accountUserJson(false, $e->getMessage());
        }
    }
