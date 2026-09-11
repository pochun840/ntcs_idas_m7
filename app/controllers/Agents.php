<?php

class Agents extends Controller
{
    private $AdminModel;
    private $SettingModel;
    Private $deviceId;
    private $res_agent;
    private $MiscellaneousModel;

    /*
        7  => GTCS 10
        8  => NTCS 10
        9  => TCC  
        10 => MTCS 
        11 => NTCS 7
        
    */

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->AdminModel = $this->model('Admin');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 

        # 啟動 agent 
        //$this->res_agent =  $this->runAgentInitial();

    }

    // 取得所有info
    public function index(){

        $isMobile = $this->isMobileCheck();
        $device_info = $this->SettingModel->GetControllerInfo();
        $agent_server_ip = $this->AdminModel->Get_Das_Config('agent_server_ip');
        $agent_type = (int)$this->AdminModel->Get_Das_Config('agent_type');
        if ($agent_type === 2) {
            $currentServerIp = $this->getCurrentRequestIpv4();
            if ($currentServerIp !== '') {
                // In Server mode this device is the Agent endpoint.  Keep the
                // saved value in sync with the address that is actually being
                // used to open this page after a Wi-Fi/Ethernet switch.
                if ($currentServerIp !== (string)$agent_server_ip) {
                    $this->AdminModel->Set_Agent_Ip($currentServerIp);
                }
                $agent_server_ip = $currentServerIp;
            }
        }
        $controller_device_info = $this->MiscellaneousModel->get_controller_device_info();



        if(!empty($controller_device_info)){
            $device_info['device_name'] = $controller_device_info['device_name'];
        }
        $device_info['device_name'] = $controller_device_info['device_name'];

        $data = [
            'isMobile' => $isMobile,
            'agent_server_ip' => $agent_server_ip,
            'agent_type' => $agent_type,
            'device_info' => $device_info,
            'agent_icon' => 'true',
            'device_name' =>  $device_info['device_name'] 
        ];
        
        $this->view('agent/index', $data);
    }

    private function getCurrentRequestIpv4(): string
    {
        $candidates = [
            preg_replace('/:\d+$/', '', trim((string)($_SERVER['HTTP_HOST'] ?? ''))),
            trim((string)($_SERVER['SERVER_ADDR'] ?? '')),
        ];

        foreach ($candidates as $candidate) {
            if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && strpos($candidate, '127.') !== 0
                && strpos($candidate, '169.254.') !== 0
                && $candidate !== '192.168.7.7') {
                return strtoupper($candidate);
            }
        }

        return '';
    }

}
?>
