
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

    private function accountUserValidateText(string $value, string $label): void
    {
        if ($value === '') {
            throw new Exception($label . ' cannot be empty.');
        }

        // A-Z / a-z / 0-9 only
        // 若你真的要排除 0，改成：/^[A-Za-z1-9]+$/
        if (!preg_match('/^[A-Za-z0-9]+$/', $value)) {
            throw new Exception($label . ' only allows A-Z, a-z, 0-9.');
        }
    }

    private function accountUserAssertTable(PDO $db): void
    {
        $exists = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user'")->fetchColumn();
        if (!$exists) {
            throw new Exception('table user not found.');
        }
    }

    public function account_user_list(): void
    {
        try {
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

    public function account_user_create(): void
    {
        try {
            $name     = $this->accountUserClean($_POST['username'] ?? '');
            $password = $this->accountUserClean($_POST['password'] ?? '');
            $confirm  = $this->accountUserClean($_POST['confirm_password'] ?? '');
            $law      = isset($_POST['law']) ? (int)$_POST['law'] : 1;

            $this->accountUserValidateText($name, 'Username');
            $this->accountUserValidateText($password, 'Password');

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
            $oldName  = $this->accountUserClean($_POST['old_username'] ?? '');
            $name     = $this->accountUserClean($_POST['username'] ?? '');
            $password = $this->accountUserClean($_POST['password'] ?? '');
            $confirm  = $this->accountUserClean($_POST['confirm_password'] ?? '');
            $law      = isset($_POST['law']) ? (int)$_POST['law'] : 1;

            $this->accountUserValidateText($oldName, 'Old username');
            $this->accountUserValidateText($name, 'Username');

            if ($password !== '') {
                $this->accountUserValidateText($password, 'Password');
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
