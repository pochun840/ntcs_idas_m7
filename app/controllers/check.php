<?php

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

    
    public function ajax_check_device_id(){
        
        header('Content-Type: application/json; charset=utf-8');

        try {

            /* =====================================================
            * 1️⃣ 讀 Controller device_id
            * ===================================================== */
            $controllerId = $this->getControllerDeviceIdOnly1();

            if ($controllerId === null) {
                echo json_encode([
                    'res_type'      => 'OK',
                    'online'        => false,
                    'device_id'     => null,
                    'idas_id'       => null,
                    'changed'       => false,
                    'initialized'   => false,
                    'idas_db_exists'=> null,   // controller offline 時不判斷
                    'idas_db_ok'    => null,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            /* =====================================================
            * 2️⃣ iDAS DB existence + readable
            * ===================================================== */
            $idasDb = '/var/www/html/database/ntcs_device_IDAS.db';
            $idasDbExists = (is_file($idasDb) && is_readable($idasDb));

            $idasId  = null;
            $idasDbOk = false;

            if ($idasDbExists) {

                $db = new PDO('sqlite:' . $idasDb, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 2,
                ]);

                $db->exec('PRAGMA busy_timeout = 2000');

                // ✅ 再加一道：確認 table 存在（避免 DB 檔存在但 schema 不對）
                $tableOk = (bool)$db->query("
                    SELECT 1
                    FROM sqlite_master
                    WHERE type='table' AND name='ntcs_device_test'
                    LIMIT 1
                ")->fetchColumn();

                if ($tableOk) {
                    $idasDbOk = true;

                    $stmt = $db->query("
                        SELECT device_id
                        FROM ntcs_device_test
                        WHERE device_id IS NOT NULL
                        ORDER BY rowid DESC
                        LIMIT 1
                    ");

                    $val = $stmt->fetchColumn();
                    if ($val !== false && $val !== null && $val !== '' && is_numeric($val)) {
                        $idasId = (int)$val;
                    }
                }
            }

            /* =====================================================
            * 3️⃣ 判斷 Controller 是否在線
            * ===================================================== */
            $online = $this->isControllerOnline();

            /* =====================================================
            * 4️⃣ 第一次初始化（iDAS DB 不存在 / 或 table 不存在 / 或 device_id 尚未寫入）
            * ===================================================== */
            if ($idasId === null) {
                echo json_encode([
                    'res_type'      => 'OK',
                    'device_id'     => $controllerId,
                    'idas_id'       => null,
                    'online'        => $online,
                    'changed'       => false,
                    'initialized'   => true,
                    'idas_db_exists'=> $idasDbExists,
                    'idas_db_ok'    => $idasDbOk,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            /* =====================================================
            * 5️⃣ ⭐ Controller ID 改變 ⭐
            * ===================================================== */
            if ($controllerId !== $idasId) {
                echo json_encode([
                    'res_type'      => 'OK',
                    'device_id'     => $controllerId,
                    'idas_id'       => $idasId,
                    'online'        => $online,
                    'changed'       => true,
                    'initialized'   => false,
                    'idas_db_exists'=> $idasDbExists,
                    'idas_db_ok'    => $idasDbOk,
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            /* =====================================================
            * 6️⃣ 正常心跳
            * ===================================================== */
            echo json_encode([
                'res_type'      => 'OK',
                'device_id'     => $controllerId,
                'idas_id'       => $idasId,
                'online'        => $online,
                'changed'       => false,
                'initialized'   => false,
                'idas_db_exists'=> $idasDbExists,
                'idas_db_ok'    => $idasDbOk,
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
            // ⭐ 直接做真正 sync（等同 reload index）
            $newId = $this->ntcs_device_db_sysnc();

            echo json_encode([
                'res_type'  => 'OK',
                'device_id' => $newId
            ], JSON_UNESCAPED_UNICODE);

        } catch (Throwable $e) {
            echo json_encode([
                'res_type' => 'ERROR'
            ]);
        }
        exit;
    }




    // =========================
    // 只讀 controller ID
    // =========================
    private function getControllerDeviceIdOnly1(): ?int
    {
        $dbPath = '/home/kls/NTCS7/ntcs_device.db';

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

            echo json_encode([
                'res_type'  => $ok ? 'OK' : 'ERROR',
                'device_id' => $id
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
            $idasDb = '/var/www/html/database/ntcs_device_IDAS.db';

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


    private function isControllerOnline(): bool
    {
        // ⭐ 3秒快取（避免瘋狂TCP連線）
        if (self::$onlineCache !== null && (time() - self::$onlineCacheTime) < 3) {
            return self::$onlineCache;
        }

        $ip   = CONTROLLER_IP;
        $port = 502;

        $sock = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$sock) {
            self::$onlineCache = false;
            self::$onlineCacheTime = time();
            return false;
        }

        socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ["sec"=>1,"usec"=>0]);
        socket_set_option($sock, SOL_SOCKET, SO_SNDTIMEO, ["sec"=>1,"usec"=>0]);

        $result = @socket_connect($sock, $ip, $port);
        socket_close($sock);

        self::$onlineCache = ($result !== false);
        self::$onlineCacheTime = time();

        return self::$onlineCache;
    }




}
