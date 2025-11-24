<?php

class Agents extends Controller
{
    private $AdminModel;
    private $SettingModel;
    Private $deviceId;
    private $res_agent;

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->AdminModel = $this->model('Admin');
        $this->SettingModel = $this->model('Setting');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();
        # 啟動 agent 
        $this->res_agent =  $this->runAgentInitial();


    }

    // 取得所有info
    public function index(){

        $isMobile = $this->isMobileCheck();
        $device_info = $this->SettingModel->GetControllerInfo();
        $agent_server_ip = $this->AdminModel->Get_Das_Config('agent_server_ip');

        $data = [
            'isMobile' => $isMobile,
            'agent_server_ip' => $agent_server_ip,
            'device_info' => $device_info,
            'agent_icon' => 'true',
        ];
        
        $this->view('agent/index', $data);
    }

}
?>