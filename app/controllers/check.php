<?php

class Check extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    private $deviceId = null;

    public function __construct()
    {
        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');

        // ❗ constructor 絕對不能做 sync
    }

    // 頁面入口（只有這裡才 sync）
    public function index()
    {
        // 使用者真正進頁 / reload 時才同步
        $this->deviceId = $this->ntcs_device_db_sysnc();
    }

    // =========================
    // AJAX：只檢查，不同步
    // =========================
    public function ajax_check_device_id()
    {
        header('Content-Type: application/json; charset=utf-8');

        $current = isset($_POST['current_device_id']) && $_POST['current_device_id'] !== ''
            ? (int)$_POST['current_device_id']
            : null;

        // ❗ 只讀 controller DB
        $new = $this->getControllerDeviceIdOnly();

        // 初始化（第一次知道 ID）
        if ($current === null && $new !== null) {
            echo json_encode([
                'res_type'    => 'OK',
                'device_id'   => $new,
                'changed'     => false,
                'initialized' => true,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 真正變更（這一段現在「一定會進來」）
        if ($current !== null && $new !== null && $current !== $new) {
            echo json_encode([
                'res_type'    => 'OK',
                'device_id'   => $new,
                'changed'     => true,
                'initialized' => false,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 無變化
        echo json_encode([
            'res_type'    => 'OK',
            'device_id'   => $new,
            'changed'     => false,
            'initialized' => false,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // =========================
    // 只讀 controller ID
    // =========================
    private function getControllerDeviceIdOnly(): ?int
    {
        try {
            $dbPath = '/home/kls/NTCS7/ntcs_device.db';
            if (!is_file($dbPath)) {
                return null;
            }

            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $db->query("
                SELECT modbus_id
                FROM ntcs_device
                LIMIT 1
            ");

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row && isset($row['modbus_id'])
                ? (int)$row['modbus_id']
                : null;

        } catch (Throwable $e) {
            return null;
        }
    }
}
