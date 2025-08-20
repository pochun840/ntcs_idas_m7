<?php

class Tool{
    private $db;//condb control box
    private $db_data;//devdb tool
    private $dbh;
    private $db_iDas_tools;

    // 在建構子將 Database 物件實例化
    public function __construct()
    {
       

        $this->db_iDas_tools = new Database;
        $this->db_iDas_tools = $this->db_iDas_tools->getDb_das_tools();


        $this->dbh = new Database;

    }

    public function GetToolInfo()
    {
        $sql = "SELECT * FROM " . TABLE_NTCS_TOOLS;
        $statement = $this->db_iDas_tools->prepare($sql);
        $statement->execute();
        $row = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $row;
    }

    public function GetControllerInfo()
    {
        $sql = "SELECT * FROM ".TABLE_NTCS_DEVICE;
        $statement = $this->db_iDas_tools->prepare($sql);
        $results = $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return $row;
    }



    public function update_tools($tools_type_temp){

        // 允許傳陣列或字串
        $model = is_array($tools_type_temp) ? ($tools_type_temp['model'] ?? null) : $tools_type_temp;
        if ($model === null) return ['ok' => false, 'error' => 'model is missing'];
        $model = trim((string)$model);

        $pdo = $this->db_iDas_tools;
        if (!($pdo instanceof PDO)) return ['ok' => false, 'error' => 'DB handle is not PDO'];
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 目標表：你指定為 ntcs_tool_test
        $table = 'ntcs_tool_test';

        // 1) 找出實際欄位名（常見同義名）
        $col = $this->resolveToolTypeColumn($pdo, $table, [
            'tool_type','tools_type','tooltype','tool','tool_model','model','screwdriver_model'
        ]);

        // 2) 若找不到，新增欄位 tool_type
        if ($col === null) {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `tool_type` VARCHAR(64) NOT NULL DEFAULT ''");
            } else {
                // sqlite / 其他
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `tool_type` TEXT");
            }
            $col = 'tool_type';
        }

        // 3) 只更新一筆
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $sql = "UPDATE `{$table}` SET `{$col}` = :v LIMIT 1";
        } else {
            // SQLite 沒有 UPDATE ... LIMIT，改用 rowid 子查詢
            $sql = "UPDATE {$table} SET {$col} = :v WHERE rowid IN (SELECT rowid FROM {$table} LIMIT 1)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':v', $model, PDO::PARAM_STR);
        $stmt->execute();

        return ['ok' => true, 'affected' => $stmt->rowCount(), 'table' => $table, 'column' => $col, 'model' => $model];
    }


    public function update_tools_sn($tools_type_tmp){
        
        // 允許傳陣列或字串
        $model = is_array($tools_type_tmp) ? ($tools_type_tmp['model'] ?? null) : $tools_type_tmp;
        if ($model === null) return ['ok' => false, 'error' => 'model is missing'];
        $model = trim((string)$model);

        $pdo = $this->db_iDas_tools;
        if (!($pdo instanceof PDO)) return ['ok' => false, 'error' => 'DB handle is not PDO'];
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // 目標表：你指定為 ntcs_tool_test
        $table = 'ntcs_tool_test';

        // 1) 找出實際欄位名（常見同義名）
        $col = $this->resolveToolTypeColumn($pdo, $table, [
            'tool_sn'
        ]);

        // 2) 若找不到，新增欄位 tool_type
        if ($col === null) {
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `tool_sn` VARCHAR(64) NOT NULL DEFAULT ''");
            } else {
                // sqlite / 其他
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `tool_sn` TEXT");
            }
            $col = 'tool_type';
        }

        // 3) 只更新一筆
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'mysql') {
            $sql = "UPDATE `{$table}` SET `{$col}` = :v LIMIT 1";
        } else {
            // SQLite 沒有 UPDATE ... LIMIT，改用 rowid 子查詢
            $sql = "UPDATE {$table} SET {$col} = :v WHERE rowid IN (SELECT rowid FROM {$table} LIMIT 1)";
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':v', $model, PDO::PARAM_STR);
        $stmt->execute();

        return ['ok' => true, 'affected' => $stmt->rowCount(), 'table' => $table, 'column' => $col, 'model' => $model];
    }


    /**
     * 找出表中存在的欄位名（大小寫不敏感）；若都不存在回傳 null
     */
    private function resolveToolTypeColumn(PDO $pdo, string $table, array $candidates): ?string
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $cols = [];

        if ($driver === 'mysql') {
            $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
            $q = $pdo->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl"
            );
            $q->execute([':db'=>$dbName, ':tbl'=>$table]);
            $cols = $q->fetchAll(PDO::FETCH_COLUMN);
        } else {
            // sqlite：PRAGMA table_info
            $q = $pdo->query("PRAGMA table_info(`{$table}`)");
            $rows = $q ? $q->fetchAll(PDO::FETCH_ASSOC) : [];
            foreach ($rows as $r) {
                if (isset($r['name'])) $cols[] = $r['name'];
            }
        }

        $map = array_change_key_case(array_flip($cols), CASE_LOWER);
        foreach ($candidates as $c) {
            if (isset($map[strtolower($c)])) return $cols[$map[strtolower($c)]];
        }
        return null;
    }





}
