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

        $this->ntcs_data_db_sysnc();
        $isMobile = $this->isMobileCheck();
        $Tool_Info = $this->ToolModel->GetToolInfo();
        $Tool_Info = end($Tool_Info);

        $controllers_info = $this->ToolModel->GetControllerInfo();
        $MAC = $this->getMacAddress();
        $ip_addr = $this->getIp();
        $netmask = $this->get_netmask('eth0');
        $gateway = $this->get_gateway_ip();
        $version = $this->getFirmwareVersion();

        //起子的版本
        $tools_version = $this->get_tools_version() / 100; 

        //韌體的版本
        $firmware_version = $this->get_firmware_version()/ 100; 

        //起子的型號
        $tools_type_temp =$this->get_tools_type();
        if(!empty($tools_type_temp)){
           $this->ToolModel->update_tools($tools_type_temp);
           $Tool_Info['tool_type'] = $tools_type_temp['model'];
        }


        //起子的序號
        $tools_type_tmp =$this->get_tools_sn(); 
        if(!empty($tools_type_tmp)){
            $this->ToolModel->update_tools_sn($tools_type_tmp);
            $Tool_Info['tool_sn'] = $tools_type_tmp['model'];
        }
       

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
            'netmask' => $netmask,
            'gateway' => $gateway,
            'unit_name' => $unit_name,
            'MAC' => $MAC,
            'image_version' => $version['version_info'],
            'tools_version' => $tools_version,
            'firmware_version' => $firmware_version

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

    public function getIp(){
        
        if( PHP_OS_FAMILY == 'Linux'){
            $Ips = trim(shell_exec("/sbin/ip -o -4 addr list  | awk '{print $4}' | cut -d/ -f1"));
            $Ip = explode(PHP_EOL, $Ips);
            
            return strtoupper($Ip[1]);
        }else{
            $host_addr= gethostname();
            $ip_addr = gethostbyname($host_addr);
            return strtoupper($ip_addr);
        }
    }

    public function get_subnet($ip, $netmask = '255.255.255.0') {
        $ip_long = ip2long($ip);
        $mask_long = ip2long($netmask);
        $subnet_long = $ip_long & $mask_long;
        return long2ip($subnet_long);
    }

    public  function get_netmask($interface = 'eth0') {
        $output = shell_exec("ip -o -f inet addr show $interface | awk '{print $4}'");
        if (preg_match('/\d+\.\d+\.\d+\.\d+\/(\d+)/', $output, $matches)) {
            $cidr = (int)$matches[1];
            return long2ip(-1 << (32 - $cidr));
        }
    return null;
    }

    public function get_gateway_ip(): ?string{

        // 1) 先試 /proc/net/route（Linux 通用，無需 shell）
        $gw = $this->gatewayFromProc();
        if ($gw) return $gw;

        // 2) 再試多個系統指令（不同發行版路徑不同）
        $cmds = [
            '/sbin/ip -4 route show default',
            '/usr/sbin/ip -4 route show default',
            'ip -4 route show default',
            'ip route show default',
            '/sbin/route -n',
            '/usr/sbin/route -n',
            'route -n',
            '/bin/netstat -rn',
            '/usr/bin/netstat -rn',
            'netstat -rn',
        ];

        foreach ($cmds as $cmd) {
            $out = @shell_exec($cmd . ' 2>/dev/null');
            if (!is_string($out) || $out === '') continue;

            // ip route: "default via 192.168.1.1 dev eth0 proto dhcp metric 100"
            if (preg_match('/\bdefault\s+via\s+([0-9]{1,3}(?:\.[0-9]{1,3}){3})\b/i', $out, $m)) {
                if (filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return $m[1];
            }
            // route -n / netstat -rn:
            // "0.0.0.0  192.168.1.1  0.0.0.0  UG 100 0 0 eth0"
            if (preg_match('/^0\.0\.0\.0\s+([0-9]{1,3}(?:\.[0-9]{1,3}){3})\s+/m', $out, $m)) {
                if (filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return $m[1];
            }
        }

        // 都失敗就回 null
        return null;
    }

    /**
     * 從 /proc/net/route 解析預設閘道
     *  - 找到 Destination 為 00000000 的列
     *  - Gateway 為小端序 16 進位，要翻轉成 IPv4
     */
    private function gatewayFromProc(): ?string{
        
        $path = '/proc/net/route';
        if (!is_readable($path)) return null;

        $fh = @fopen($path, 'r');
        if (!$fh) return null;

        // 解析表頭以取得欄位索引（避免不同核心版本欄位順序差異）
        $header = fgets($fh);
        if ($header === false) { fclose($fh); return null; }
        $cols = preg_split('/\s+/', trim($header));
        $map  = array_flip($cols); // e.g. ['Iface'=>0,'Destination'=>1,'Gateway'=>2,'Flags'=>3,...]

        $gw = null;
        while (($line = fgets($fh)) !== false) {
            $parts = preg_split('/\s+/', trim($line));
            if (!isset($parts[$map['Destination']], $parts[$map['Gateway']], $parts[$map['Flags']])) continue;

            $destHex = strtoupper($parts[$map['Destination']]);
            $flags   = intval($parts[$map['Flags']]); // bitmask: 0x1=UP, 0x2=GATEWAY
            if ($destHex !== '00000000') continue;
            if (($flags & 0x2) === 0) continue; // 必須是 GATEWAY

            $gwHex = strtoupper($parts[$map['Gateway']]); // 小端序 hex，例如 "0101A8C0" = 192.168.1.1
            if (!preg_match('/^[0-9A-F]{8}$/', $gwHex)) continue;

            // 轉成 IPv4：每兩位一組，反轉順序，再轉十進位
            $bytes = array_reverse(str_split($gwHex, 2)); // ['C0','A8','01','01'] -> 192.168.1.1
            $ip = implode('.', array_map(fn($b) => hexdec($b), $bytes));
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { $gw = $ip; break; }
        }
        fclose($fh);
        return $gw;
    }


    public function getFirmwareVersion($file = '/boot/firmware/version') {
        // 讀檔案
        if (is_readable($file)) {
            $raw = trim(file_get_contents($file));
        } else {
            // sudo 讀
            $cmd = sprintf('sudo cat %s 2>/dev/null', escapeshellarg($file));
            $raw = trim(shell_exec($cmd));
        }

        if ($raw === '') {
            return null;
        }

        // 解析結果
        $result = [];

        // 版本號
        if (preg_match('/^Ver:\s*(.+?)\s+Date:/', $raw, $m)) {
            $result['version_info'] = trim($m[1]);
        }

        // 以 Date: 拆分
        $parts = preg_split('/\s*Date:\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($parts as $part) {
            if (preg_match('/^(\d{4}\/\d{1,2}\/\d{1,2})\s*(.+)$/s', trim($part), $m)) {
                $date = $m[1];
                $content = trim($m[2]);

                // 把多行切成陣列（用數字或字母開頭的項目拆）
                $items = preg_split('/(?=\d+\.\s|[a-z]\.\s)/i', $content, -1, PREG_SPLIT_NO_EMPTY);
                $items = array_map('trim', $items);

                $result['logs'][] = [
                    'date' => $date,
                    'changes' => $items
                ];
            }
        }

        return $result;
    }


}
?>