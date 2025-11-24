<?php

class Check extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    Private $deviceId;
    private $res_agent;
    
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();

        # 啟動 agent 
        $this->res_agent =  $this->runAgentInitial();

        

    }

    // 取得所有Jobs
    public function index(){

        

     

    }

    
    public function ajax_check_device_id(){
        header('Content-Type: application/json; charset=utf-8');

        // 前端傳來目前畫面認知的 device_id（從 cookie 或 JS 變數帶）
        $current = isset($_POST['current_device_id']) ? (int)$_POST['current_device_id'] : null;

        // 這邊可以視情況決定要不要強制 refresh
        // - true  → 每次都重新偵測（最保險，但稍微重）
        // - false → 使用你之前加的快取機制（比較省）
        $new = $this->ntcs_device_db_sysnc(false);

        $changed = false;
        if ($new !== null && $current !== null && $new !== $current) {
            $changed = true;
        }

        echo json_encode([
            'res_type'   => 'OK',
            'device_id'  => $new,
            'changed'    => $changed,
        ]);
        exit;
    }
    

  

    
}