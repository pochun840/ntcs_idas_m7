<?php

class Agents extends Controller
{
    private $AdminModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->AdminModel = $this->model('Admin');
    }

    // 取得所有info
    public function index(){

        $isMobile = $this->isMobileCheck();
        $device_info = $this->Device_Info();
        $agent_server_ip = $this->AdminModel->Get_Das_Config('agent_server_ip');
        // $Controller_Info = $this->ToolModel->GetControllerInfo();        

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