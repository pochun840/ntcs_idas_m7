<?php

class Check extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    Private $deviceId;
    
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();

    }

    // 取得所有Jobs
    public function index(){

    }

    public function ajax_check_device_id(){

        header('Content-Type: application/json; charset=utf-8');

        // 前端目前認知的 device_id
        $current = isset($_POST['current_device_id']) && $_POST['current_device_id'] !== ''
            ? (int)$_POST['current_device_id']
            : null;

        // 是否強制重新同步 
        $new = $this->ntcs_device_db_sysnc(false);

        // 預設回傳
        $changed = false;
        $initialized = false;

        // 判斷是否初始化完成
        if ($current === null && $new !== null) {
            $initialized = true; // 第一次取得 device_id
        }

        // 正常情境：兩邊都有值才比較
        if ($current !== null && $new !== null && $new !== $current) {
            $changed = true;
        }

        echo json_encode([
            'res_type'    => 'OK',
            'device_id'   => $new,          // 最新 device_id（可能為 null）
            'changed'     => $changed,      // 是否真的變更
            'initialized' => $initialized,  // 是否為首次初始化
            'server_time' => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }


    public function runAgentInitial() {

        // & 表示背景執行，立即結束
        $cmd = 'sudo /usr/bin/php /var/www/html/ntcs_idas/service/agent_initial.php > /dev/null 2>&1 &';

        // 只要執行指令，不等待結果
        shell_exec($cmd);

        // 回傳 JSON 給前端（避免畫面有多餘輸出）
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'output'  => 'Agent start triggered (background mode)'
        ]);
        exit;
    }

    

  

    
}