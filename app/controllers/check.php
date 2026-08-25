<?php
/*
 * Single-codebase platform switch.
 * /home/kls/upgrade/icontroller = 1 -> i-controller implementation
 * 0 / missing / invalid -> NTCS implementation
 */
if (idas_is_icontroller()) {
class Check extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    private $deviceId = null;
    private static $onlineCache = null;
    private static $onlineCacheTime = 0;


    public function __construct(){

        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');

        // ❌ 絕對不要在 constructor sync
        $this->deviceId = null;
    }


    // 頁面入口（只有這裡才 sync）
    public function index()
    {
        // 使用者真正進頁 / reload 時才同步
        //$this->deviceId = $this->ntcs_device_db_sysnc();
    }

    public function sync_device_identity(){

        header('Content-Type: application/json; charset=utf-8');

        try {

            /*
             * Controller → iDAS identity sync.
             *
             * 注意：這支只能在前端確認「控制器已手動重新啟動」且使用者按下
             * Popup 確定後呼叫。
             *
             * 同步來源：/home/kls/NTCS7/ntcs_device.db
             * 同步目的：/var/www/html/database/ntcs_device_IDAS.db
             * 同步欄位：device_id + modbus_type
             */
            $controllerIdentity = $this->getControllerDeviceIdentity();
            $controllerId   = $controllerIdentity['device_id'] ?? null;
            $controllerType = $controllerIdentity['modbus_type'] ?? null;

            if ($controllerId === null && $controllerType === null) {
                echo json_encode([
                    'res_type' => 'ERROR',
                    'msg'      => 'controller identity not found'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $deviceIdSynced = true;
            if ($controllerId !== null) {
                $deviceIdSynced = $this->writeDeviceIdToIdasDb((int)$controllerId);
            }

            $modbusTypeSynced = true;
            if ($controllerType !== null) {
                $modbusTypeSynced = $this->writeModbusTypeToIdasDb((int)$controllerType);
            }

            if (!$deviceIdSynced || !$modbusTypeSynced) {
                echo json_encode([
                    'res_type'             => 'ERROR',
                    'msg'                  => 'sync failed',
                    'device_id_synced'     => $deviceIdSynced,
                    'modbus_type_synced'   => $modbusTypeSynced,
                    'device_id'            => $controllerId,
                    'modbus_type'          => $controllerType,
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($controllerId !== null) {
                $exp = time() + 86400 * 30;
                setcookie('temp_device_id', (string)$controllerId, $exp, '/', '', false, true);
                setcookie('temp_device_id_ts', (string)time(), $exp, '/', '', false, true);
                $_COOKIE['temp_device_id']    = (string)$controllerId;
                $_COOKIE['temp_device_id_ts'] = (string)time();
            }

            idas_write_identity_state('idas_controller_identity_baseline', [
                'device_id' => $controllerId,
                'modbus_type' => $controllerType,
            ]);
            idas_clear_identity_state('idas_controller_restart_pending');

            echo json_encode([
                'res_type'            => 'OK',
                'msg'                 => 'device identity synced',
                'device_id'           => $controllerId,
                'modbus_type'         => $controllerType,
                'device_id_synced'    => $deviceIdSynced,
                'modbus_type_synced'  => $modbusTypeSynced
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {

            echo json_encode([
                'res_type' => 'ERROR',
                'msg' => 'sync failed'
            ], JSON_UNESCAPED_UNICODE);
        }
    }



    
    public function ajax_check_device_id(){
        
        header('Content-Type: application/json; charset=utf-8');

        try {

            /* =====================================================
            * 1️⃣ 讀 Controller identity：device_id + modbus_type
            * ===================================================== */
            $controllerIdentity = $this->getControllerDeviceIdentity();
            $controllerId       = $controllerIdentity['device_id'];
            $controllerType     = $controllerIdentity['modbus_type'];
            $pendingChange      = idas_read_identity_state('idas_controller_restart_pending') ?? [];

            if ($controllerId === null && $controllerType === null) {
                echo json_encode([
                    'res_type'             => 'OK',
                    'online'               => false,
                    'device_id'            => null,
                    'idas_id'              => null,
                    'modbus_type'          => null,
                    'idas_modbus_type'     => null,
                    'changed'              => false,
                    'id_changed'           => false,
                    'modbus_type_changed'  => false,
                    'change_type'          => 'none',
                    'initialized'          => false,
                    'idas_db_exists'       => null,
                    'idas_db_ok'           => null,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            /* =====================================================
            * 2️⃣ 讀 iDAS identity：device_id + modbus_type
            * ===================================================== */
            $idasIdentity = $this->getIdasDeviceIdentity();
            $idasId       = $idasIdentity['device_id'];
            $idasType     = $idasIdentity['modbus_type'];
            $idasDbExists = $idasIdentity['db_exists'];
            $idasDbOk     = $idasIdentity['db_ok'];

            /*
             * Server-side baseline survives browser refresh and Controller reboot.
             * It detects changes even when another save path updates both DB files
             * before this polling request can compare them.
             */
            $serverBaseline = idas_read_identity_state('idas_controller_identity_baseline');
            if ($serverBaseline === null) {
                idas_write_identity_state('idas_controller_identity_baseline', [
                    'device_id' => $idasId ?? $controllerId,
                    'modbus_type' => $idasType ?? $controllerType,
                ]);
                $serverBaseline = [
                    'device_id' => $idasId ?? $controllerId,
                    'modbus_type' => $idasType ?? $controllerType,
                ];
            }

            $baselineIdChanged = isset($serverBaseline['device_id'])
                && $controllerId !== null
                && (int)$serverBaseline['device_id'] !== (int)$controllerId;
            $baselineTypeChanged = isset($serverBaseline['modbus_type'])
                && $controllerType !== null
                && (int)$serverBaseline['modbus_type'] !== (int)$controllerType;

            /* =====================================================
            * 3️⃣ 判斷 Controller 是否在線
            * ===================================================== */
            $online = $this->isControllerOnline();

            /* =====================================================
            * 4️⃣ 第一次初始化（iDAS DB 不存在 / table 不存在 / device_id 尚未寫入）
            * ===================================================== */
            if ($idasId === null) {
                echo json_encode([
                    'res_type'             => 'OK',
                    'device_id'            => $controllerId,
                    'idas_id'              => null,
                    'modbus_type'          => $controllerType,
                    'idas_modbus_type'     => $idasType,
                    'online'               => $online,
                    'changed'              => false,
                    'id_changed'           => false,
                    'modbus_type_changed'  => false,
                    'change_type'          => 'none',
                    'initialized'          => true,
                    'idas_db_exists'       => $idasDbExists,
                    'idas_db_ok'           => $idasDbOk,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            /* =====================================================
            * 5️⃣ Controller ID / MODBUS TYPE 改變
            * ===================================================== */
            $idChanged = $baselineIdChanged || !empty($pendingChange['id_changed']) || (
                $controllerId !== null
                && $idasId !== null
                && $controllerId !== $idasId
            );

            $modbusTypeChanged = $baselineTypeChanged || !empty($pendingChange['modbus_type_changed']) || (
                $controllerType !== null
                && $idasType !== null
                && $controllerType !== $idasType
            );

            $changed = ($idChanged || $modbusTypeChanged);

            if ($idChanged && $modbusTypeChanged) {
                $changeType = 'both';
            } elseif ($idChanged) {
                $changeType = 'device_id';
            } elseif ($modbusTypeChanged) {
                $changeType = 'modbus_type';
            } else {
                $changeType = 'none';
            }

            echo json_encode([
                'res_type'             => 'OK',
                'device_id'            => $controllerId,
                'idas_id'              => $idasId,
                'modbus_type'          => $controllerType,
                'idas_modbus_type'     => $idasType,
                'online'               => $online,
                'changed'              => $changed,
                'id_changed'           => $idChanged,
                'modbus_type_changed'  => $modbusTypeChanged,
                'change_type'          => $changeType,
                'initialized'          => false,
                'idas_db_exists'       => $idasDbExists,
                'idas_db_ok'           => $idasDbOk,
                // changed=true 時，前端只能顯示 Banner 並等待人工重新啟動；不可立即同步。
                'restart_required'       => $changed,
                'manual_reboot_required' => $changed,
                'sync_after_popup'       => $changed,
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            echo json_encode([
                'res_type' => 'ERROR',
                'msg'      => 'exception'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }




    // ⭐ 強制同步 controller → iDAS
    public function ajax_force_sync_device_id(){
        
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 舊流程相容用：實際同步邏輯與 sync_device_identity 相同。
            // 只能在前端流程確認後呼叫，不可在偵測到 changed=true 時自動呼叫。
            $controllerIdentity = $this->getControllerDeviceIdentity();
            $controllerId = $controllerIdentity['device_id'] ?? null;
            $controllerType = $controllerIdentity['modbus_type'] ?? null;

            if ($controllerId === null && $controllerType === null) {
                echo json_encode([
                    'res_type' => 'ERROR',
                    'msg'      => 'controller identity not found'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $deviceIdSynced = true;
            if ($controllerId !== null) {
                $deviceIdSynced = $this->writeDeviceIdToIdasDb((int)$controllerId);
            }

            $modbusTypeSynced = true;
            if ($controllerType !== null) {
                $modbusTypeSynced = $this->writeModbusTypeToIdasDb((int)$controllerType);
            }

            if ($controllerId !== null) {
                $exp = time() + 86400 * 30;
                setcookie('temp_device_id', (string)$controllerId, $exp, '/', '', false, true);
                setcookie('temp_device_id_ts', (string)time(), $exp, '/', '', false, true);
                $_COOKIE['temp_device_id']    = (string)$controllerId;
                $_COOKIE['temp_device_id_ts'] = (string)time();
            }

            echo json_encode([
                'res_type'            => ($deviceIdSynced && $modbusTypeSynced) ? 'OK' : 'ERROR',
                'device_id'           => $controllerId,
                'modbus_type'         => $controllerType,
                'device_id_synced'    => $deviceIdSynced,
                'modbus_type_synced'  => $modbusTypeSynced
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            echo json_encode([
                'res_type' => 'ERROR'
            ]);
        }
        exit;
    }





    /**
     * 讀取 Controller 端 identity。
     * 來源：/home/kls/NTCS7/ntcs_device.db
     */
    private function getControllerDeviceIdentity(): array
    {
        return $this->readDeviceIdentityFromDb(idas_path('controller_root', 'ntcs_device.db'));
    }


    /**
     * 讀取 iDAS 端 identity。
     * 來源：/var/www/html/database/ntcs_device_IDAS.db
     */
    private function getIdasDeviceIdentity(): array
    {
        return $this->readDeviceIdentityFromDb(idas_path('database_root', 'ntcs_device_IDAS.db'));
    }


    /**
     * 一次讀取 ntcs_device_test.device_id + modbus_type。
     * modbus_type：0 = MODBUS TCP, 1 = MODBUS RTU, 2 = OP
     */
    private function readDeviceIdentityFromDb(string $dbPath): array
    {
        $identity = [
            'db_exists'     => is_file($dbPath) && is_readable($dbPath),
            'db_ok'         => false,
            'device_id'     => null,
            'modbus_type'   => null,
        ];

        if (!$identity['db_exists']) {
            return $identity;
        }

        try {
            $db = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2,
            ]);

            $db->exec('PRAGMA busy_timeout = 2000');

            $tableOk = (bool)$db->query("
                SELECT 1
                FROM sqlite_master
                WHERE type='table' AND name='ntcs_device_test'
                LIMIT 1
            ")->fetchColumn();

            if (!$tableOk) {
                return $identity;
            }

            $identity['db_ok'] = true;

            $columnRows = $db->query("PRAGMA table_info('ntcs_device_test')")
                ->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_column($columnRows, 'name');

            $selectFields = ['device_id'];
            if (in_array('modbus_type', $columns, true)) {
                $selectFields[] = 'modbus_type';
            }

            $stmt = $db->query("
                SELECT " . implode(', ', $selectFields) . "
                FROM ntcs_device_test
                ORDER BY rowid DESC
                LIMIT 1
            ");

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return $identity;
            }

            if (
                isset($row['device_id'])
                && $row['device_id'] !== null
                && $row['device_id'] !== ''
                && is_numeric($row['device_id'])
            ) {
                $deviceId = (int)$row['device_id'];
                if ($deviceId >= 1 && $deviceId <= 255) {
                    $identity['device_id'] = $deviceId;
                }
            }

            if (
                array_key_exists('modbus_type', $row)
                && $row['modbus_type'] !== null
                && $row['modbus_type'] !== ''
                && is_numeric($row['modbus_type'])
            ) {
                $modbusType = (int)$row['modbus_type'];
                if (in_array($modbusType, [0, 1, 2], true)) {
                    $identity['modbus_type'] = $modbusType;
                }
            }

            return $identity;

        } catch (Throwable $e) {
            return $identity;
        }
    }


    // =========================
    // 只讀 controller ID
    // =========================
    private function getControllerDeviceIdOnly1(): ?int
    {
        $dbPath = idas_path('controller_root', 'ntcs_device.db');

        // 檔案存在 + 可讀
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            $db = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // SQLite 讀取偶發鎖住時，稍等一下（毫秒）
                PDO::ATTR_TIMEOUT => 2,
            ]);

            // 更明確：若被鎖，等待 2000ms
            $db->exec('PRAGMA busy_timeout = 2000');

            // 若表可能多筆，建議指定排序依據（你可改成你們最可靠的欄位）
            $stmt = $db->query("
                SELECT device_id
                FROM ntcs_device_test
                WHERE device_id IS NOT NULL
                ORDER BY rowid DESC
                LIMIT 1
            ");

            $val = $stmt->fetchColumn();

            // fetchColumn 可能回 false / null / string
            if ($val === false || $val === null || $val === '') {
                return null;
            }

            // 嚴格數字檢查，避免 'NULL' / 'abc' 之類的髒資料
            if (!is_numeric($val)) {
                return null;
            }

            return (int)$val;

        } catch (Throwable $e) {
            return null;
        }
    }



    public function ajax_set_device_id_session(){

        session_start();
        $_SESSION['device_id'] = $_POST['device_id'] ?? null;
        echo json_encode(['res_type'=>'OK']);
    }

    public function ajax_apply_device_id(): void{
        
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = isset($_POST['device_id']) ? (int)$_POST['device_id'] : 0;

            if ($id <= 0) {
                echo json_encode(['res_type'=>'ERROR','reason'=>'invalid_id']);
                exit;
            }

            $ok = $this->writeDeviceIdToIdasDb($id);

            $modbusType = null;
            $modbusTypeOk = true;
            if (isset($_POST['modbus_type']) && $_POST['modbus_type'] !== '') {
                $modbusType = (int)$_POST['modbus_type'];
                if (!in_array($modbusType, [0, 1, 2], true)) {
                    echo json_encode(['res_type'=>'ERROR','reason'=>'invalid_modbus_type']);
                    exit;
                }
                $modbusTypeOk = $this->writeModbusTypeToIdasDb($modbusType);
            }

            echo json_encode([
                'res_type'           => ($ok && $modbusTypeOk) ? 'OK' : 'ERROR',
                'device_id'          => $id,
                'modbus_type'        => $modbusType,
                'modbus_type_synced' => $modbusTypeOk
            ]);
            exit;

        } catch (Throwable $e) {
            echo json_encode(['res_type'=>'ERROR']);
            exit;
        }
    }


    /**
     * 將 device_id 寫入 iDAS DB（ntcs_device_IDAS.db）
     * ntcs_device_test 永遠只有一筆 → 直接 UPDATE
     */
    private function writeDeviceIdToIdasDb(int $deviceId): bool
    {
        try {
            $idasDb = idas_path('database_root', 'ntcs_device_IDAS.db');

            if (!is_file($idasDb)) {
                return false;
            }

            $db = new PDO('sqlite:' . $idasDb);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ⭐ 直接更新第一筆
            $stmt = $db->prepare("
                UPDATE ntcs_device_test
                SET device_id = :id
                WHERE rowid = 1
            ");

            $stmt->execute([
                ':id' => $deviceId
            ]);

            return true;

        } catch (Throwable $e) {
            return false;
        }
    }




    /**
     * 將 modbus_type 寫入 iDAS DB（ntcs_device_IDAS.db）
     * 0 = MODBUS TCP, 1 = MODBUS RTU, 2 = OP
     */
    private function writeModbusTypeToIdasDb(int $modbusType): bool
    {
        if (!in_array($modbusType, [0, 1, 2], true)) {
            return false;
        }

        try {
            $idasDb = idas_path('database_root', 'ntcs_device_IDAS.db');

            if (!is_file($idasDb) || !is_readable($idasDb) || !is_writable($idasDb)) {
                return false;
            }

            $db = new PDO('sqlite:' . $idasDb);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec('PRAGMA busy_timeout = 3000');

            $columnExists = (bool)$db->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'modbus_type' LIMIT 1"
            )->fetchColumn();

            if (!$columnExists) {
                return false;
            }

            $stmt = $db->prepare("
                UPDATE ntcs_device_test
                SET modbus_type = :modbus_type
                WHERE rowid = 1
            ");

            $stmt->bindValue(':modbus_type', $modbusType, PDO::PARAM_INT);
            $stmt->execute();

            return true;

        } catch (Throwable $e) {
            return false;
        }
    }


    private function isControllerOnline(): bool
    {
        // 3 秒快取：同一個 AJAX request 內避免重複 TCP connect。
        if (self::$onlineCache !== null && (time() - self::$onlineCacheTime) < 3) {
            return self::$onlineCache;
        }

        $identity = $this->getControllerDeviceIdentity();
        $controllerType = $identity['modbus_type'] ?? null;
        if ($controllerType === null) {
            $controllerType = $this->get_modbus_type_from_controller();
        }

        $online = false;

        // MODBUS TYPE = 2：OP Protocol，優先使用 Controller DB wifi 解析出的實際 OP endpoint。
        if ((int)$controllerType === 2) {
            try {
                foreach ($this->getOpProtocolCandidates(null, 4545) as $endpoint) {
                    $host = trim((string)($endpoint['host'] ?? ''));
                    $port = (int)($endpoint['port'] ?? 4545);
                    if ($host === '' || $port <= 0) {
                        continue;
                    }
                    if ($this->canConnectTcp($host, $port, 0.7)) {
                        $online = true;
                        break;
                    }
                }
            } catch (Throwable $e) {
                $online = false;
            }
        } else {
            // TCP / RTU：依網路設定中的 server port 檢查。
            $ip = defined('CONTROLLER_IP') ? (string)CONTROLLER_IP : '127.0.0.1';
            $tcpPort = $this->getControllerTcpPort(502);
            $online = $this->canConnectTcp($ip, $tcpPort, 0.7);
        }

        self::$onlineCache = $online;
        self::$onlineCacheTime = time();

        return self::$onlineCache;
    }


    private function canConnectTcp(string $host, int $port, float $timeout = 0.7): bool
    {
        $host = trim($host);
        if ($host === '' || $port <= 0 || $port > 65535) {
            return false;
        }

        $errno = 0;
        $errstr = '';
        $client = @stream_socket_client(
            'tcp://' . $host . ':' . $port,
            $errno,
            $errstr,
            max(0.1, $timeout),
            STREAM_CLIENT_CONNECT
        );

        if (is_resource($client)) {
            fclose($client);
            return true;
        }

        return false;
    }



}
} else {
class Check extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    private $deviceId = null;
    private static $onlineCache = null;
    private static $onlineCacheTime = 0;


    public function __construct(){

        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');

        // ❌ 絕對不要在 constructor sync
        $this->deviceId = null;
    }


    // 頁面入口（只有這裡才 sync）
    public function index()
    {
        // 使用者真正進頁 / reload 時才同步
        //$this->deviceId = $this->ntcs_device_db_sysnc();
    }

    public function sync_device_identity(){

        header('Content-Type: application/json; charset=utf-8');

        try {

            /*
             * Controller → iDAS identity sync.
             *
             * 注意：這支只能在前端確認「控制器已手動重新啟動」且使用者按下
             * Popup 確定後呼叫。
             *
             * 同步來源：/home/kls/NTCS7/ntcs_device.db
             * 同步目的：/var/www/html/database/ntcs_device_IDAS.db
             * 同步欄位：device_id + modbus_type
             */
            $controllerIdentity = $this->getControllerDeviceIdentity();
            $controllerId   = $controllerIdentity['device_id'] ?? null;
            $controllerType = $controllerIdentity['modbus_type'] ?? null;
            $controllerPort = $controllerIdentity['server_port'] ?? null;

            if ($controllerId === null && $controllerType === null && $controllerPort === null) {
                echo json_encode([
                    'res_type' => 'ERROR',
                    'msg'      => 'controller identity not found'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            $deviceIdSynced = true;
            if ($controllerId !== null) {
                $deviceIdSynced = $this->writeDeviceIdToIdasDb((int)$controllerId);
            }

            $modbusTypeSynced = true;
            if ($controllerType !== null) {
                $modbusTypeSynced = $this->writeModbusTypeToIdasDb((int)$controllerType);
            }

            // Server Port only belongs to MODBUS TCP. OP always uses 4545 at
            // runtime and must never write 4545 into the wifi DB field.
            $serverPortSynced = true;
            if ((int)$controllerType === IDAS_PROTOCOL_TCP && $controllerPort !== null) {
                $serverPortSynced = $this->writeServerPortToIdasDb((int)$controllerPort);
            }

            if (!$deviceIdSynced || !$modbusTypeSynced || !$serverPortSynced) {
                echo json_encode([
                    'res_type'             => 'ERROR',
                    'msg'                  => 'sync failed',
                    'device_id_synced'     => $deviceIdSynced,
                    'modbus_type_synced'   => $modbusTypeSynced,
                    'server_port_synced'   => $serverPortSynced,
                    'device_id'            => $controllerId,
                    'modbus_type'          => $controllerType,
                    'server_port'          => $controllerPort,
                ], JSON_UNESCAPED_UNICODE);
                return;
            }

            if ($controllerId !== null) {
                $exp = time() + 86400 * 30;
                setcookie('temp_device_id', (string)$controllerId, $exp, '/', '', false, true);
                setcookie('temp_device_id_ts', (string)time(), $exp, '/', '', false, true);
                $_COOKIE['temp_device_id']    = (string)$controllerId;
                $_COOKIE['temp_device_id_ts'] = (string)time();
            }

            /*
             * Final DB consistency check.
             * Banner can only be cleared when Controller DB and iDAS DB
             * contain identical Device ID / Protocol / Server Port values.
             */
            $controllerVerify = $this->readDeviceIdentityFromDb(
                idas_path('controller_root', 'ntcs_device.db')
            );
            $idasVerify = $this->readDeviceIdentityFromDb(
                idas_path('database_root', 'ntcs_device_IDAS.db')
            );

            $deviceIdEqual = (
                ($controllerVerify['device_id'] ?? null)
                === ($idasVerify['device_id'] ?? null)
            );
            $modbusTypeEqual = (
                ($controllerVerify['modbus_type'] ?? null)
                === ($idasVerify['modbus_type'] ?? null)
            );
            // RTU/OP do not own a TCP server-port setting. Preserve the wifi
            // value and exclude it from identity consistency checks.
            $serverPortEqual = true;
            if ((int)($controllerVerify['modbus_type'] ?? -1) === IDAS_PROTOCOL_TCP) {
                $serverPortEqual = (
                    ($controllerVerify['server_port'] ?? null)
                    === ($idasVerify['server_port'] ?? null)
                );
            }

            $dbConsistent = (
                $deviceIdEqual
                && $modbusTypeEqual
                && $serverPortEqual
            );

            if (!$dbConsistent) {
                echo json_encode([
                    'res_type'            => 'ERROR',
                    'msg'                 => 'controller/iDAS DB not consistent',
                    'db_consistent'       => false,
                    'device_id_equal'     => $deviceIdEqual,
                    'modbus_type_equal'   => $modbusTypeEqual,
                    'server_port_equal'   => $serverPortEqual,
                    'device_id'           => $controllerVerify['device_id'] ?? null,
                    'idas_id'             => $idasVerify['device_id'] ?? null,
                    'modbus_type'         => $controllerVerify['modbus_type'] ?? null,
                    'idas_modbus_type'    => $idasVerify['modbus_type'] ?? null,
                    'server_port'         => $controllerVerify['server_port'] ?? null,
                    'idas_server_port'    => $idasVerify['server_port'] ?? null,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                return;
            }

            idas_write_identity_state('idas_controller_identity_baseline', [
                'device_id' => $controllerId,
                'modbus_type' => $controllerType,
                'server_port' => $controllerPort,
            ]);
            idas_clear_identity_state('idas_controller_restart_pending');

            echo json_encode([
                'res_type'            => 'OK',
                'msg'                 => 'device identity synced',
                'device_id'           => $controllerId,
                'modbus_type'         => $controllerType,
                'server_port'         => $controllerPort,
                'device_id_synced'    => $deviceIdSynced,
                'modbus_type_synced'  => $modbusTypeSynced,
                'server_port_synced'  => $serverPortSynced,
                'db_consistent'       => true,
                'device_id_equal'     => true,
                'modbus_type_equal'   => true,
                'server_port_equal'   => true
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {

            echo json_encode([
                'res_type' => 'ERROR',
                'msg' => 'sync failed'
            ], JSON_UNESCAPED_UNICODE);
        }
    }


    /**
     * Fast DB-only change detection. Never opens MODBUS/OP connections, so a
     * newly changed ID/protocol cannot delay the red restart Banner.
     */
    public function ajax_check_device_change_fast()
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

        // This endpoint is read-only. Release the PHP session immediately so
        // a slow Controller communication request cannot block this probe.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {
            $controller = $this->getControllerDeviceIdentity();
            $idas = $this->getIdasDeviceIdentity();
            $pending = idas_read_identity_state('idas_controller_restart_pending') ?? [];

            $controllerId = $controller['device_id'] ?? null;
            $controllerType = $controller['modbus_type'] ?? null;
            $controllerPort = $controller['server_port'] ?? null;
            $idasId = $idas['device_id'] ?? null;
            $idasType = $idas['modbus_type'] ?? null;
            $idasPort = $idas['server_port'] ?? null;

            $baseline = idas_read_identity_state('idas_controller_identity_baseline');
            if ($baseline === null) {
                $baseline = [
                    'device_id' => $idasId ?? $controllerId,
                    'modbus_type' => $idasType ?? $controllerType,
                    'server_port' => $idasPort ?? $controllerPort,
                ];
                idas_write_identity_state('idas_controller_identity_baseline', $baseline);
            }

            $idChanged = !empty($pending['id_changed']) || (
                $controllerId !== null
                && (
                    ($idasId !== null && (int)$controllerId !== (int)$idasId)
                    || (isset($baseline['device_id']) && (int)$controllerId !== (int)$baseline['device_id'])
                )
            );
            $typeChanged = !empty($pending['modbus_type_changed']) || (
                $controllerType !== null
                && (
                    ($idasType !== null && (int)$controllerType !== (int)$idasType)
                    || (isset($baseline['modbus_type']) && (int)$controllerType !== (int)$baseline['modbus_type'])
                )
            );
            $portChanged = !empty($pending['server_port_changed']);
            if ((int)$controllerType === IDAS_PROTOCOL_TCP && $controllerPort !== null) {
                $portChanged = $portChanged
                    || ($idasPort !== null && (int)$controllerPort !== (int)$idasPort)
                    || (
                        (int)($baseline['modbus_type'] ?? -1) === IDAS_PROTOCOL_TCP
                        && isset($baseline['server_port'])
                        && (int)$controllerPort !== (int)$baseline['server_port']
                    );
            }

            $count = ($idChanged ? 1 : 0) + ($typeChanged ? 1 : 0) + ($portChanged ? 1 : 0);
            $changeType = $count > 1
                ? 'both'
                : ($idChanged ? 'device_id' : ($typeChanged ? 'modbus_type' : ($portChanged ? 'server_port' : 'none')));

            echo json_encode([
                'res_type' => 'OK',
                'changed' => $count > 0,
                'id_changed' => $idChanged,
                'modbus_type_changed' => $typeChanged,
                'server_port_changed' => $portChanged,
                'change_type' => $changeType,
                'device_id' => $controllerId,
                'idas_id' => $idasId,
                'modbus_type' => $controllerType,
                'idas_modbus_type' => $idasType,
                'server_port' => $controllerPort,
                'idas_server_port' => $idasPort,
                'online_check_deferred' => true,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            echo json_encode(['res_type' => 'ERROR', 'msg' => 'fast change check failed']);
        }
    }



    
    public function ajax_check_device_id(){
        
        header('Content-Type: application/json; charset=utf-8');

        // Controller startup/protocol checks may take several seconds. They do
        // not write session data, so never hold the session lock while waiting.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        try {

            /* =====================================================
            * 1️⃣ 讀 Controller identity：device_id + modbus_type
            * ===================================================== */
            $controllerIdentity = $this->getControllerDeviceIdentity();
            $controllerId       = $controllerIdentity['device_id'];
            $controllerType     = $controllerIdentity['modbus_type'];
            $controllerPort     = $controllerIdentity['server_port'];
            $pendingChange      = idas_read_identity_state('idas_controller_restart_pending') ?? [];
            $pendingChanged     = !empty($pendingChange['id_changed'])
                || !empty($pendingChange['modbus_type_changed'])
                || !empty($pendingChange['server_port_changed']);

            if ($controllerId === null && $controllerType === null && $controllerPort === null) {
                echo json_encode([
                    'res_type'             => 'OK',
                    'online'               => false,
                    'device_id'            => null,
                    'idas_id'              => null,
                    'modbus_type'          => null,
                    'idas_modbus_type'     => null,
                    'server_port'          => null,
                    'idas_server_port'     => null,
                    'changed'              => $pendingChanged,
                    'id_changed'           => !empty($pendingChange['id_changed']),
                    'modbus_type_changed'  => !empty($pendingChange['modbus_type_changed']),
                    'server_port_changed'  => !empty($pendingChange['server_port_changed']),
                    'db_consistent'        => true,
                    'change_type'          => !empty($pendingChange['change_type']) ? $pendingChange['change_type'] : 'none',
                    'initialized'          => false,
                    'idas_db_exists'       => null,
                    'idas_db_ok'           => null,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            /* =====================================================
            * 2️⃣ 讀 iDAS identity：device_id + modbus_type
            * ===================================================== */
            $idasIdentity = $this->getIdasDeviceIdentity();
            $idasId       = $idasIdentity['device_id'];
            $idasType     = $idasIdentity['modbus_type'];
            $idasPort     = $idasIdentity['server_port'];
            $idasDbExists = $idasIdentity['db_exists'];
            $idasDbOk     = $idasIdentity['db_ok'];

            /* Persistent baseline: independent of browser localStorage. */
            $serverBaseline = idas_read_identity_state('idas_controller_identity_baseline');
            if ($serverBaseline === null) {
                $serverBaseline = [
                    'device_id' => $idasId ?? $controllerId,
                    'modbus_type' => $idasType ?? $controllerType,
                    'server_port' => $idasPort ?? $controllerPort,
                ];
                idas_write_identity_state('idas_controller_identity_baseline', $serverBaseline);
            }

            $baselineIdChanged = isset($serverBaseline['device_id'])
                && $controllerId !== null
                && (int)$serverBaseline['device_id'] !== (int)$controllerId;
            $baselineTypeChanged = isset($serverBaseline['modbus_type'])
                && $controllerType !== null
                && (int)$serverBaseline['modbus_type'] !== (int)$controllerType;
            $baselinePortChanged = (int)$controllerType === IDAS_PROTOCOL_TCP
                && (int)($serverBaseline['modbus_type'] ?? -1) === IDAS_PROTOCOL_TCP
                && isset($serverBaseline['server_port'])
                && $controllerPort !== null
                && (int)$serverBaseline['server_port'] !== (int)$controllerPort;

            /* =====================================================
            * 3️⃣ 判斷 Controller 是否在線
            * ===================================================== */
            // TCP connect alone is not enough after Device ID / Protocol has
            // changed. Verify a real protocol read with the Controller DB's
            // newest identity before offering synchronization.
            $online = $this->isControllerOnline()
                && $this->isControllerIdentityReady($controllerId, $controllerType);

            /* =====================================================
            * 4️⃣ 第一次初始化（iDAS DB 不存在 / table 不存在 / device_id 尚未寫入）
            * ===================================================== */
            if ($idasId === null) {
                echo json_encode([
                    'res_type'             => 'OK',
                    'device_id'            => $controllerId,
                    'idas_id'              => null,
                    'modbus_type'          => $controllerType,
                    'idas_modbus_type'     => $idasType,
                    'server_port'          => $controllerPort,
                    'idas_server_port'     => $idasPort,
                    'online'               => $online,
                    'changed'              => $pendingChanged,
                    'id_changed'           => !empty($pendingChange['id_changed']),
                    'modbus_type_changed'  => !empty($pendingChange['modbus_type_changed']),
                    'server_port_changed'  => !empty($pendingChange['server_port_changed']),
                    'db_consistent'        => true,
                    'change_type'          => !empty($pendingChange['change_type']) ? $pendingChange['change_type'] : 'none',
                    'initialized'          => true,
                    'idas_db_exists'       => $idasDbExists,
                    'idas_db_ok'           => $idasDbOk,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            /* =====================================================
            * 5️⃣ Controller ID / MODBUS TYPE 改變
            * ===================================================== */
            $idChanged = $baselineIdChanged || !empty($pendingChange['id_changed']) || (
                $controllerId !== null
                && $idasId !== null
                && $controllerId !== $idasId
            );

            $modbusTypeChanged = $baselineTypeChanged || !empty($pendingChange['modbus_type_changed']) || (
                $controllerType !== null
                && $idasType !== null
                && $controllerType !== $idasType
            );

            // Only MODBUS TCP persists/compares Server Port. OP is fixed at
            // 4545 and RTU does not use a TCP Server Port.
            $serverPortChanged = $baselinePortChanged || !empty($pendingChange['server_port_changed']);
            if ((int)$controllerType === IDAS_PROTOCOL_TCP) {
                $serverPortChanged = $serverPortChanged || (
                    $controllerPort !== null
                    && $idasPort !== null
                    && $controllerPort !== $idasPort
                );
            }

            $changed = ($idChanged || $modbusTypeChanged || $serverPortChanged);

            $changeCount = ($idChanged ? 1 : 0)
                + ($modbusTypeChanged ? 1 : 0)
                + ($serverPortChanged ? 1 : 0);

            if ($changeCount > 1) {
                $changeType = 'both';
            } elseif ($idChanged) {
                $changeType = 'device_id';
            } elseif ($modbusTypeChanged) {
                $changeType = 'modbus_type';
            } elseif ($serverPortChanged) {
                $changeType = 'server_port';
            } else {
                $changeType = 'none';
            }

            echo json_encode([
                'res_type'             => 'OK',
                'device_id'            => $controllerId,
                'idas_id'              => $idasId,
                'modbus_type'          => $controllerType,
                'idas_modbus_type'     => $idasType,
                'server_port'          => $controllerPort,
                'idas_server_port'     => $idasPort,
                'online'               => $online,
                'changed'              => $changed,
                'id_changed'           => $idChanged,
                'modbus_type_changed'  => $modbusTypeChanged,
                'server_port_changed'  => $serverPortChanged,
                'effective_server_port'=> ((int)$controllerType === IDAS_PROTOCOL_OP)
                    ? IDAS_SERVER_PORT_OP
                    : $controllerPort,
                'db_consistent'        => !$changed,
                'change_type'          => $changeType,
                'initialized'          => false,
                'idas_db_exists'       => $idasDbExists,
                'idas_db_ok'           => $idasDbOk,
                'system_boot_id'       => $this->getSystemBootId(),
                // changed=true 時，前端只能顯示 Banner 並等待人工重新啟動；不可立即同步。
                'restart_required'       => $changed,
                'manual_reboot_required' => $changed,
                'sync_after_popup'       => $changed,
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (Throwable $e) {
            echo json_encode([
                'res_type' => 'ERROR',
                'msg'      => 'exception'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }




    // ⭐ 強制同步 controller → iDAS
    public function ajax_force_sync_device_id(){
        
        header('Content-Type: application/json; charset=utf-8');

        try {
            // 舊流程相容用：實際同步邏輯與 sync_device_identity 相同。
            // 只能在前端流程確認後呼叫，不可在偵測到 changed=true 時自動呼叫。
            $controllerIdentity = $this->getControllerDeviceIdentity();
            $controllerId = $controllerIdentity['device_id'] ?? null;
            $controllerType = $controllerIdentity['modbus_type'] ?? null;
            $controllerPort = $controllerIdentity['server_port'] ?? null;

            if ($controllerId === null && $controllerType === null && $controllerPort === null) {
                echo json_encode([
                    'res_type' => 'ERROR',
                    'msg'      => 'controller identity not found'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $deviceIdSynced = true;
            if ($controllerId !== null) {
                $deviceIdSynced = $this->writeDeviceIdToIdasDb((int)$controllerId);
            }

            $modbusTypeSynced = true;
            if ($controllerType !== null) {
                $modbusTypeSynced = $this->writeModbusTypeToIdasDb((int)$controllerType);
            }

            $serverPortSynced = true;
            if ((int)$controllerType === IDAS_PROTOCOL_TCP && $controllerPort !== null) {
                $serverPortSynced = $this->writeServerPortToIdasDb((int)$controllerPort);
            }

            if ($controllerId !== null) {
                $exp = time() + 86400 * 30;
                setcookie('temp_device_id', (string)$controllerId, $exp, '/', '', false, true);
                setcookie('temp_device_id_ts', (string)time(), $exp, '/', '', false, true);
                $_COOKIE['temp_device_id']    = (string)$controllerId;
                $_COOKIE['temp_device_id_ts'] = (string)time();
            }

            echo json_encode([
                'res_type'            => ($deviceIdSynced && $modbusTypeSynced && $serverPortSynced) ? 'OK' : 'ERROR',
                'device_id'           => $controllerId,
                'modbus_type'         => $controllerType,
                'device_id_synced'    => $deviceIdSynced,
                'modbus_type_synced'  => $modbusTypeSynced,
                'server_port_synced'  => $serverPortSynced,
                'server_port'         => $controllerPort
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            echo json_encode([
                'res_type' => 'ERROR'
            ]);
        }
        exit;
    }





    /**
     * 讀取 Controller 端 identity。
     * 來源：/home/kls/NTCS7/ntcs_device.db
     */
    private function getControllerDeviceIdentity(): array
    {
        return $this->readDeviceIdentityFromDb(idas_path('controller_root', 'ntcs_device.db'));
    }


    /**
     * 讀取 iDAS 端 identity。
     * 來源：/var/www/html/database/ntcs_device_IDAS.db
     */
    private function getIdasDeviceIdentity(): array
    {
        return $this->readDeviceIdentityFromDb(idas_path('database_root', 'ntcs_device_IDAS.db'));
    }


    /**
     * 一次讀取 ntcs_device_test.device_id + modbus_type。
     * modbus_type：0 = MODBUS TCP, 1 = MODBUS RTU, 2 = OP
     */
    private function readDeviceIdentityFromDb(string $dbPath): array
    {
        $identity = [
            'db_exists'     => is_file($dbPath) && is_readable($dbPath),
            'db_ok'         => false,
            'device_id'     => null,
            'modbus_type'   => null,
            'server_port'   => null,
        ];

        if (!$identity['db_exists']) {
            return $identity;
        }

        try {
            $db = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 2,
            ]);

            $db->exec('PRAGMA busy_timeout = 2000');

            $tableOk = (bool)$db->query("
                SELECT 1
                FROM sqlite_master
                WHERE type='table' AND name='ntcs_device_test'
                LIMIT 1
            ")->fetchColumn();

            if (!$tableOk) {
                return $identity;
            }

            $identity['db_ok'] = true;

            $columnRows = $db->query("PRAGMA table_info('ntcs_device_test')")
                ->fetchAll(PDO::FETCH_ASSOC);
            $columns = array_column($columnRows, 'name');

            $selectFields = ['device_id'];
            if (in_array('modbus_type', $columns, true)) {
                $selectFields[] = 'modbus_type';
            }
            if (in_array('wifi', $columns, true)) {
                $selectFields[] = 'wifi';
            }

            $stmt = $db->query("
                SELECT " . implode(', ', $selectFields) . "
                FROM ntcs_device_test
                ORDER BY rowid DESC
                LIMIT 1
            ");

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return $identity;
            }

            if (
                isset($row['device_id'])
                && $row['device_id'] !== null
                && $row['device_id'] !== ''
                && is_numeric($row['device_id'])
            ) {
                $deviceId = (int)$row['device_id'];
                if ($deviceId >= 1 && $deviceId <= 255) {
                    $identity['device_id'] = $deviceId;
                }
            }

            if (
                array_key_exists('modbus_type', $row)
                && $row['modbus_type'] !== null
                && $row['modbus_type'] !== ''
                && is_numeric($row['modbus_type'])
            ) {
                $modbusType = (int)$row['modbus_type'];
                if (in_array($modbusType, [0, 1, 2], true)) {
                    $identity['modbus_type'] = $modbusType;
                }
            }

            if (array_key_exists('wifi', $row) && $row['wifi'] !== null) {
                $wifiParts = explode('_', (string)$row['wifi']);
                if (isset($wifiParts[2]) && ctype_digit(trim((string)$wifiParts[2]))) {
                    $serverPort = (int)trim((string)$wifiParts[2]);
                    if ($serverPort >= 1 && $serverPort <= 65535) {
                        $identity['server_port'] = $serverPort;
                    }
                }
            }

            return $identity;

        } catch (Throwable $e) {
            return $identity;
        }
    }


    // =========================
    // 只讀 controller ID
    // =========================
    private function getControllerDeviceIdOnly1(): ?int
    {
        $dbPath = idas_path('controller_root', 'ntcs_device.db');

        // 檔案存在 + 可讀
        if (!is_file($dbPath) || !is_readable($dbPath)) {
            return null;
        }

        try {
            $db = new PDO('sqlite:' . $dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // SQLite 讀取偶發鎖住時，稍等一下（毫秒）
                PDO::ATTR_TIMEOUT => 2,
            ]);

            // 更明確：若被鎖，等待 2000ms
            $db->exec('PRAGMA busy_timeout = 2000');

            // 若表可能多筆，建議指定排序依據（你可改成你們最可靠的欄位）
            $stmt = $db->query("
                SELECT device_id
                FROM ntcs_device_test
                WHERE device_id IS NOT NULL
                ORDER BY rowid DESC
                LIMIT 1
            ");

            $val = $stmt->fetchColumn();

            // fetchColumn 可能回 false / null / string
            if ($val === false || $val === null || $val === '') {
                return null;
            }

            // 嚴格數字檢查，避免 'NULL' / 'abc' 之類的髒資料
            if (!is_numeric($val)) {
                return null;
            }

            return (int)$val;

        } catch (Throwable $e) {
            return null;
        }
    }



    public function ajax_set_device_id_session(){

        session_start();
        $_SESSION['device_id'] = $_POST['device_id'] ?? null;
        echo json_encode(['res_type'=>'OK']);
    }

    public function ajax_apply_device_id(): void{
        
        header('Content-Type: application/json; charset=utf-8');

        try {
            $id = isset($_POST['device_id']) ? (int)$_POST['device_id'] : 0;

            if ($id <= 0) {
                echo json_encode(['res_type'=>'ERROR','reason'=>'invalid_id']);
                exit;
            }

            $ok = $this->writeDeviceIdToIdasDb($id);

            $modbusType = null;
            $modbusTypeOk = true;
            if (isset($_POST['modbus_type']) && $_POST['modbus_type'] !== '') {
                $modbusType = (int)$_POST['modbus_type'];
                if (!in_array($modbusType, [0, 1, 2], true)) {
                    echo json_encode(['res_type'=>'ERROR','reason'=>'invalid_modbus_type']);
                    exit;
                }
                $modbusTypeOk = $this->writeModbusTypeToIdasDb($modbusType);
            }

            echo json_encode([
                'res_type'           => ($ok && $modbusTypeOk) ? 'OK' : 'ERROR',
                'device_id'          => $id,
                'modbus_type'        => $modbusType,
                'modbus_type_synced' => $modbusTypeOk
            ]);
            exit;

        } catch (Throwable $e) {
            echo json_encode(['res_type'=>'ERROR']);
            exit;
        }
    }


    /**
     * 將 device_id 寫入 iDAS DB（ntcs_device_IDAS.db）
     * ntcs_device_test 永遠只有一筆 → 直接 UPDATE
     */
    private function writeDeviceIdToIdasDb(int $deviceId): bool
    {
        try {
            $idasDb = idas_path('database_root', 'ntcs_device_IDAS.db');

            if (!is_file($idasDb)) {
                return false;
            }

            $db = new PDO('sqlite:' . $idasDb);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // ⭐ 直接更新第一筆
            $stmt = $db->prepare("
                UPDATE ntcs_device_test
                SET device_id = :id
                WHERE rowid = 1
            ");

            $stmt->execute([
                ':id' => $deviceId
            ]);

            return true;

        } catch (Throwable $e) {
            return false;
        }
    }




    /**
     * 將 modbus_type 寫入 iDAS DB（ntcs_device_IDAS.db）
     * 0 = MODBUS TCP, 1 = MODBUS RTU, 2 = OP
     */
    private function writeModbusTypeToIdasDb(int $modbusType): bool
    {
        if (!in_array($modbusType, [0, 1, 2], true)) {
            return false;
        }

        try {
            $idasDb = idas_path('database_root', 'ntcs_device_IDAS.db');

            if (!is_file($idasDb) || !is_readable($idasDb) || !is_writable($idasDb)) {
                return false;
            }

            $db = new PDO('sqlite:' . $idasDb);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec('PRAGMA busy_timeout = 3000');

            $columnExists = (bool)$db->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'modbus_type' LIMIT 1"
            )->fetchColumn();

            if (!$columnExists) {
                return false;
            }

            $stmt = $db->prepare("
                UPDATE ntcs_device_test
                SET modbus_type = :modbus_type
                WHERE rowid = 1
            ");

            $stmt->bindValue(':modbus_type', $modbusType, PDO::PARAM_INT);
            $stmt->execute();

            return true;

        } catch (Throwable $e) {
            return false;
        }
    }



    /**
     * 將 Controller Server Port 同步到 iDAS DB 的 ntcs_device_test.wifi。
     * 只更新 wifi 第 3 欄 Port，其餘 mode / IP / mask / gateway 保留。
     */
    private function writeServerPortToIdasDb(int $serverPort): bool
    {
        if ($serverPort < 1 || $serverPort > 65535) {
            return false;
        }

        $idasDb = idas_path('database_root', 'ntcs_device_IDAS.db');
        if (!is_file($idasDb) || !is_readable($idasDb) || !is_writable($idasDb)) {
            return false;
        }

        try {
            $db = new PDO('sqlite:' . $idasDb);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->exec('PRAGMA busy_timeout = 3000');

            $columnExists = (bool)$db->query(
                "SELECT 1 FROM pragma_table_info('ntcs_device_test') WHERE name = 'wifi' LIMIT 1"
            )->fetchColumn();

            if (!$columnExists) {
                return false;
            }

            $wifi = $db->query(
                "SELECT wifi FROM ntcs_device_test ORDER BY rowid DESC LIMIT 1"
            )->fetchColumn();

            $parts = explode('_', (string)($wifi === false || $wifi === null ? '' : $wifi));
            while (count($parts) < 5) {
                $parts[] = '';
            }

            if (!in_array((int)($parts[0] ?? 1), [1, 2], true)) {
                $parts[0] = '1';
            }
            if (trim((string)($parts[3] ?? '')) === '') {
                $parts[3] = '255.255.255.0';
            }

            $parts[2] = (string)$serverPort;
            $newWifi = implode('_', array_slice($parts, 0, 5));

            // Identity reader uses latest row, so update/verify that exact same row.
            $targetRowId = $db->query(
                "SELECT rowid FROM ntcs_device_test ORDER BY rowid DESC LIMIT 1"
            )->fetchColumn();

            if ($targetRowId === false || $targetRowId === null) {
                return false;
            }

            /*
             * ntcs_device_test is a single-row configuration table.
             * Update the existing configuration row directly; do not depend on rowid.
             */
            $rowCount = (int)$db->query(
                "SELECT COUNT(*) FROM ntcs_device_test"
            )->fetchColumn();

            if ($rowCount !== 1) {
                return false;
            }

            $stmt = $db->prepare(
                "UPDATE ntcs_device_test SET wifi = :wifi"
            );
            $stmt->execute([':wifi' => $newWifi]);

            @chmod($idasDb, 0777);

            // Force pending filesystem writes out before the immediate verification read.
            if (function_exists('exec')) {
                @exec('sync');
            }
            usleep(300000);

            // Fresh connection for verification prevents reading a stale PDO snapshot.
            $db = null;
            $verifyDb = new PDO('sqlite:' . $idasDb);
            $verifyDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $verifyDb->exec('PRAGMA busy_timeout = 3000');

            $verify = $verifyDb->query(
                "SELECT wifi FROM ntcs_device_test LIMIT 1"
            )->fetchColumn();

            $verifyParts = explode('_', (string)$verify);
            return isset($verifyParts[2]) && (int)$verifyParts[2] === $serverPort;

        } catch (Throwable $e) {
            return false;
        }
    }


    /**
     * PHP-managed token for the current system boot.
     *
     * /run and /dev/shm are volatile filesystems: the token remains stable
     * across PHP/Apache restarts but disappears after a real controller reboot.
     * PHP then creates a new token without reading /proc boot_id.
     */
    private function getSystemBootId(): ?string
    {
        $paths = [];

        // Preferred location when the deployment has prepared /run/idas.
        $runDir = '/run/idas';
        if (is_dir($runDir) || @mkdir($runDir, 0775, true)) {
            $paths[] = $runDir . '/boot_token';
        }

        // Standard world-writable tmpfs fallback; no root/setup requirement.
        if (is_dir('/dev/shm') && is_writable('/dev/shm')) {
            $paths[] = '/dev/shm/idas_boot_token';
        }

        foreach (array_unique($paths) as $path) {
            try {
                if (is_link($path)) {
                    continue;
                }

                $existing = is_file($path)
                    ? trim((string)@file_get_contents($path))
                    : '';
                if (preg_match('/^[a-f0-9]{32}$/', $existing)) {
                    return $existing;
                }

                $token = bin2hex(random_bytes(16));

                // Exclusive creation prevents two simultaneous AJAX requests
                // from producing different tokens during first boot access.
                $handle = @fopen($path, 'x');
                if (is_resource($handle)) {
                    $written = @fwrite($handle, $token);
                    @fflush($handle);
                    @fclose($handle);
                    @chmod($path, 0664);
                    if ($written === strlen($token)) {
                        return $token;
                    }
                }

                // Another request may have won the exclusive-create race.
                $created = is_file($path)
                    ? trim((string)@file_get_contents($path))
                    : '';
                if (preg_match('/^[a-f0-9]{32}$/', $created)) {
                    return $created;
                }
            } catch (Throwable $e) {
                // Try the next volatile location.
            }
        }

        return null;
    }


    /**
     * Confirm that the controller has actually applied its newest identity.
     * A successful TCP connect can still be the old service before reboot.
     */
    private function isControllerIdentityReady($deviceId, $modbusType): bool
    {
        $unitId = is_numeric($deviceId) ? (int)$deviceId : 0;
        $type = is_numeric($modbusType) ? (int)$modbusType : -1;

        if ($unitId < 1 || $unitId > 255 || !in_array($type, [0, 1, 2], true)) {
            return false;
        }

        try {
            return $this->protocol_read_register($unitId, 29002) !== null;
        } catch (Throwable $e) {
            return false;
        }
    }


    private function isControllerOnline(): bool
    {
        // 3 秒快取：同一個 AJAX request 內避免重複 TCP connect。
        if (self::$onlineCache !== null && (time() - self::$onlineCacheTime) < 3) {
            return self::$onlineCache;
        }

        /*
         * NTCS Controller-master online detection.
         * Server Port 若由控制器端修改，重開機後必須用 Controller DB
         * 最新的 Server Port 判斷是否重新上線，不能永遠固定檢查 502。
         */
        $identity = $this->getControllerDeviceIdentity();
        $controllerType = $identity['modbus_type'] ?? null;
        $controllerPort = $identity['server_port'] ?? null;

        if ($controllerType === null) {
            $controllerType = $this->get_modbus_type_from_controller();
        }

        $online = false;

        if ((int)$controllerType === IDAS_PROTOCOL_OP) {
            // OP 固定 4545，沿用既有 OP endpoint candidate。
            try {
                foreach ($this->getOpProtocolCandidates(null, IDAS_SERVER_PORT_OP) as $endpoint) {
                    $host = trim((string)($endpoint['host'] ?? ''));
                    $port = (int)($endpoint['port'] ?? IDAS_SERVER_PORT_OP);

                    if ($host === '' || $port <= 0 || $port > 65535) {
                        continue;
                    }

                    if ($this->canConnectTcp($host, $port, 0.7)) {
                        $online = true;
                        break;
                    }
                }
            } catch (Throwable $e) {
                $online = false;
            }
        } else {
            // TCP / RTU 優先使用 Controller DB 最新 Server Port；讀不到才 fallback 502。
            $ip = defined('CONTROLLER_IP') ? (string)CONTROLLER_IP : '127.0.0.1';
            $port = (
                is_numeric($controllerPort)
                && (int)$controllerPort >= 1
                && (int)$controllerPort <= 65535
            ) ? (int)$controllerPort : IDAS_SERVER_PORT_TCP;

            $online = $this->canConnectTcp($ip, $port, 0.7);
        }

        self::$onlineCache = $online;
        self::$onlineCacheTime = time();

        return self::$onlineCache;
    }


    private function canConnectTcp(string $host, int $port, float $timeout = 0.7): bool
    {
        $host = trim($host);
        if ($host === '' || $port <= 0 || $port > 65535) {
            return false;
        }

        $errno = 0;
        $errstr = '';
        $client = @stream_socket_client(
            'tcp://' . $host . ':' . $port,
            $errno,
            $errstr,
            max(0.1, $timeout),
            STREAM_CLIENT_CONNECT
        );

        if (is_resource($client)) {
            fclose($client);
            return true;
        }

        return false;
    }



}
}
