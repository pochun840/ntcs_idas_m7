<?php

class Tools extends Controller
{
    private $ToolModel;
    private $MiscellaneousModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->ToolModel = $this->model('Tool');
         $this->MiscellaneousModel = $this->model('Miscellaneous');
    }

    // 取得所有info
    public function index(){

        $isMobile = $this->isMobileCheck();
        $Tool_Info = $this->ToolModel->GetToolInfo();
        $Tool_Info = end($Tool_Info);

        $controllers_info = $this->ToolModel->GetControllerInfo();
        $MAC = $this->getMacAddress();
        $ip_addr = $this->getIp();


        if(!empty($controllers_info)){
            $step_torque_unit = (int)$controllers_info['torque_unit'];
            $unit_name  = $this->MiscellaneousModel->details('torque_unit');
            $unit_name  = $unit_name[$step_torque_unit];
        }

        if(!empty($Tool_Info)){
           
            //扭力value 從DB 取出來 都要除以1000
            $minTorque = (float)$Tool_Info['min_torque']/1000;
            $maxTorque = (float)$Tool_Info['max_torque']/1000;
            $low_torque_arr  = $this->MiscellaneousModel->convert_all_torque_units($minTorque, 1);
            $high_torque_arr = $this->MiscellaneousModel->convert_all_torque_units($maxTorque, 1);
            $Tool_Info['min_torque'] = $low_torque_arr[$unit_name];
            $Tool_Info['max_torque'] = $high_torque_arr[$unit_name];

        }

  

        $data = [
            'isMobile' => $isMobile,
            'Tool_Info' => $Tool_Info,
            'Controllers_Info' => $controllers_info,
            'IP' => $ip_addr,
            'unit_name' => $unit_name,
            'MAC' => $MAC,
        ];

        $this->view('tool/index', $data);
    }

    public function getMacAddress(){

        if( PHP_OS_FAMILY == 'Linux'){
            $output = shell_exec("ip link show");

            preg_match('/link\/ether (\w{2}:\w{2}:\w{2}:\w{2}:\w{2}:\w{2})/', $output, $matches);
            if (!empty($matches)) {
                return strtoupper($matches[1]);
            } else {
                return false;
            }

        }else{
            $MAC = exec('getmac');
            $MAC = strtok($MAC, ' ');
            $MAC = str_replace('-',':',$MAC);
            return $MAC;
        }
        
    }

    public function getIp()
    {
        if( PHP_OS_FAMILY == 'Linux'){
            // $eth0Ip = '';
            // $eth0Ip = trim(shell_exec("/sbin/ip -o -4 addr list eth0 | awk '{print $4}' | cut -d/ -f1"));
            $Ips = trim(shell_exec("/sbin/ip -o -4 addr list  | awk '{print $4}' | cut -d/ -f1"));
            $Ip = explode(PHP_EOL, $Ips);
            
            return strtoupper($Ip[1]);
        }else{
            $host_addr= gethostname();
            $ip_addr = gethostbyname($host_addr);
            return strtoupper($ip_addr);
        }
    }

}
?>