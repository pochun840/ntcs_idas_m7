<?php

class Tools extends Controller
{
    private $ToolModel;
    private $MiscellaneousModel;
    private $DataModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->ToolModel = $this->model('Tool');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->DataModel = $this->model('Datas');
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
        list($iface, $gw) = $this->getDefaultIfaceAndGateway();
        $gateway_temp = $this->getIPv4AndBroadcast($iface);
        $version = $this->getFirmwareVersion();

        //起子的版本
        $tools_version = $this->get_tools_version() / 100; 

        //韌體的版本
        $firmware_version = $this->get_firmware_version()/ 100; 

        //系統的版本
        $upgrade_ver = $this->get_upgrade_version();

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
            'gateway' => $gateway_temp['broadcast'],
            'unit_name' => $unit_name,
            'MAC' => $MAC,
            'image_version' => $version['version_info'],
            'tools_version' => $tools_version,
            'firmware_version' => $firmware_version,
            'upgrade_ver' => $upgrade_ver

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

    /**
     * 取得網路資訊（default gateway / 介面 / IPv4 / netmask / broadcast）
     * - 先從 /proc/net/route 找 default route（不需外部指令）
     * - 再用 ip 指令抓該介面的 IP/Mask/Broadcast（若可用）
     * 回傳：
     * [
     *   'gateway'   => '192.168.0.1' | null,
     *   'iface'     => 'eth0'        | null,
     *   'ip'        => '192.168.0.97'| null,
     *   'netmask'   => '255.255.255.0'| null,
     *   'broadcast' => '192.168.0.255'| null,
     * ]
     */
    public function getNetworkInfo(): array {
        $res = ['gateway'=>null,'iface'=>null,'ip'=>null,'netmask'=>null,'broadcast'=>null];

        // 1) 從 /proc/net/route 取 default gateway + 介面（純 PHP）
        $file = '/proc/net/route';
        if (is_readable($file) && ($f = @fopen($file, 'r'))) {
            while (($line = fgets($f)) !== false) {
                $line = trim($line);
                if ($line === '' || stripos($line, 'Iface') === 0) continue;
                $cols = preg_split('/\s+/', $line);
                if (count($cols) < 4) continue;
                [$iface, $destHex, $gwHex, $flags] = [$cols[0], $cols[1], $cols[2], $cols[3]];

                // default route: Destination == 00000000，且旗標包含 U/G
                if (strcasecmp($destHex, '00000000') === 0) {
                    $res['iface']   = $iface;
                    $res['gateway'] = $this->_hexLittleToIp($gwHex);
                    break;
                }
            }
            fclose($f);
        }

        // 若沒有抓到 iface，就結束（至少回 gateway/null）
        if (empty($res['iface'])) return $res;

        // 2) 用 ip 指令抓該 iface 的 ip/prefix/broadcast（若可用）
        $ipbin = $this->_whichIp();
        if ($ipbin) {
            $out = @shell_exec($ipbin.' -o -f inet addr show dev ' . escapeshellarg($res['iface']) . ' 2>/dev/null');
            // 例：2: eth0    inet 192.168.0.97/24 brd 192.168.0.255 scope global eth0
            if ($out) {
                if (preg_match('/\binet\s+([0-9.]+)\/(\d+)\b/', $out, $m)) {
                    $res['ip'] = $m[1];
                    $prefix = (int)$m[2];
                    $res['netmask'] = $this->_prefixToMask($prefix);
                }
                if (preg_match('/\bbrd\s+([0-9.]+)/', $out, $m)) {
                    $res['broadcast'] = $m[1];
                }
            }
        }

        // 3) 若沒拿到 broadcast，但有 ip+mask → 自行計算
        if (!$res['broadcast'] && $res['ip'] && $res['netmask']) {
            $res['broadcast'] = $this->_calcBroadcast($res['ip'], $res['netmask']);
        }

        return $res;
    }

    // ---------- 小工具 ----------

    public function _hexLittleToIp(string $hex): ?string {
        $hex = strtolower(trim($hex));
        if ($hex === '' || $hex === '00000000') return null;
        $hex = str_pad($hex, 8, '0', STR_PAD_LEFT);
        $b = array_reverse(str_split($hex, 2)); // little-endian → big-endian
        return implode('.', array_map(fn($x)=>hexdec($x), $b));
    }

    public function _whichIp(): ?string {
        foreach (['/sbin/ip','/usr/sbin/ip','/bin/ip','/usr/bin/ip'] as $p) {
            if (is_executable($p)) return $p;
        }
        return null;
    }

    public function _prefixToMask(int $prefix): string {
        $mask = $prefix === 0 ? 0 : (0xFFFFFFFF << (32 - $prefix)) & 0xFFFFFFFF;
        return long2ip($mask);
    }

    public function _calcBroadcast(string $ip, string $netmask): ?string {
        $ipL = ip2long($ip); $mL = ip2long($netmask);
        if ($ipL === false || $mL === false) return null;
        $bL = ($ipL & $mL) | (~$mL & 0xFFFFFFFF);
        return long2ip($bL);
    }

    
    public function get_upgrade_version(string $path = '/home/kls/upgrade/version'): string{
        // 讀檔（用 sudo cat），把錯誤丟到 /dev/null 避免噪音
        $cmd = 'sudo cat ' . escapeshellarg($path) . ' 2>/dev/null';
        $txt = shell_exec($cmd) ?? '';

        // 去掉開頭底線、前後空白，再取第一行
        return preg_split('/\R/', trim(ltrim($txt, '_')))[0] ?? '';
    }


    /**
     * Linux 網路資訊工具：可靠取得預設路由介面、IPv4、Broadcast（廣播位址）、Gateway
     * - 先讀 /proc/net/route（快 & 不依賴外部指令），失敗再用 `ip route`
     * - 取 IPv4/廣播：優先 `ip -j`，退回 `ip -4 -o`
     * - 廣播位址計算使用 32-bit 安全的位元組法
     */

    /** 執行命令並回傳 [exitCode, stdout(string)] */
    public function runCmd(string $cmd): array {
        $out = [];
        $code = 0;
        exec($cmd, $out, $code);
        return [$code, implode("\n", $out)];
    }

    /** 以 /proc/net/route 取得 [iface, gateway]；失敗回傳 [null, null] */
    public function getDefaultIfaceGatewayFromProc(): array {
        $path = '/proc/net/route';
        if (!is_readable($path)) return [null, null];

        $fh = fopen($path, 'r');
        if (!$fh) return [null, null];

        // 欄位: Iface Destination Gateway Flags RefCnt Use Metric Mask MTU Window IRTT
        fgets($fh); // skip header
        while (($line = fgets($fh)) !== false) {
            $fields = preg_split('/\s+/', trim($line));
            if (count($fields) < 11) continue;
            [$iface, $destHex, $gwHex] = [$fields[0], strtoupper($fields[1]), strtoupper($fields[2])];
            if ($destHex !== '00000000') continue;

            // 小端 32-bit hex → IPv4
            $gwLong = hexdec($gwHex);
            $gw = inet_ntop(pack('V', $gwLong)); // 'V' = little-endian unsigned long
            fclose($fh);
            return [$iface, $gw];
        }
        fclose($fh);
        return [null, null];
    }

    /** 從 `ip route` 取得 [iface, gateway]；失敗回傳 [null, null] */
    public function getDefaultIfaceGatewayFromIp(): array {
        // 例: "default via 192.168.0.1 dev eth0 ..."
        [, $txt] = $this->runCmd("ip route 2>/dev/null");
        foreach (explode("\n", $txt) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, 'default ') !== 0) continue;
            $parts = preg_split('/\s+/', $line);
            $gw = null; $iface = null;
            foreach ($parts as $i => $p) {
                if ($p === 'via' && isset($parts[$i+1])) $gw = $parts[$i+1];
                if ($p === 'dev' && isset($parts[$i+1])) $iface = $parts[$i+1];
            }
            if ($iface && $gw) return [$iface, $gw];
        }
        return [null, null];
    }

    /** 取得預設路由的介面與 gateway；優先 /proc，退回 ip route */
    public function getDefaultIfaceAndGateway(): array {
        [$iface, $gw] = $this->getDefaultIfaceGatewayFromProc();
        if ($iface && $gw) return [$iface, $gw];
        return $this->getDefaultIfaceGatewayFromIp();
    }

    /** 32-bit 安全：從 IP/CIDR 計算廣播位址（位元組法） */
    public function calcBroadcastFromIpCidr(string $ip, int $cidr): ?string {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return null;
        if ($cidr < 0 || $cidr > 32) return null;

        $bin = @inet_pton($ip);
        if ($bin === false || strlen($bin) !== 4) return null;
        $bytes = unpack('C4', $bin);

        // 產生 4 個遮罩位元組
        $maskBytes = [0,0,0,0];
        $full = intdiv($cidr, 8);
        $rem  = $cidr % 8;
        for ($i = 0; $i < 4; $i++) {
            if ($i < $full) {
                $maskBytes[$i] = 0xFF;
            } elseif ($i === $full && $rem > 0) {
                $maskBytes[$i] = (0xFF << (8 - $rem)) & 0xFF;
            } else {
                $maskBytes[$i] = 0x00;
            }
        }

        // broadcast = (ip & mask) | (~mask)
        $bcast = [];
        for ($i = 1; $i <= 4; $i++) {
            $ipb = $bytes[$i];
            $msk = $maskBytes[$i-1];
            $bcast[] = (($ipb & $msk) | (~$msk & 0xFF)) & 0xFF;
        }
        return inet_ntop(pack('C4', ...$bcast));
    }

    /**
     * 取得指定介面的 IPv4 與 Broadcast（廣播位址）
     * 回傳: ['ip' => 'A.B.C.D', 'broadcast' => 'A.B.C.255']（若無法取得則為 null）
     */
    public function getIPv4AndBroadcast(string $iface): array {
        // 先試 ip -j（較穩定）
        $cmdJ = sprintf("ip -j -4 addr show dev %s scope global 2>/dev/null", escapeshellarg($iface));
        [$codeJ, $json] = $this->runCmd($cmdJ);
        if ($codeJ === 0 && trim($json) !== '') {
            $arr = json_decode($json, true);
            if (is_array($arr) && isset($arr[0]['addr_info']) && is_array($arr[0]['addr_info'])) {
                foreach ($arr[0]['addr_info'] as $info) {
                    if (($info['scope'] ?? '') !== 'global') continue;
                    $ip = $info['local'] ?? null;
                    $b  = $info['broadcast'] ?? null;
                    if ($ip && $b) return ['ip' => $ip, 'broadcast' => $b];
                    if ($ip && isset($info['prefixlen'])) {
                        $b2 = $this->calcBroadcastFromIpCidr($ip, (int)$info['prefixlen']);
                        return ['ip' => $ip, 'broadcast' => $b2];
                    }
                }
            }
        }

        // 退回 ip -4 -o 正則
        $cmd = sprintf("ip -4 -o addr show dev %s scope global 2>/dev/null", escapeshellarg($iface));
        [, $line] = $this->runCmd($cmd);
        $line = trim($line);
        if ($line !== '') {
            if (preg_match('/inet\s+([0-9.]+)\/(\d+)(?:.*?\bbrd\s+([0-9.]+))?/', $line, $m)) {
                $ip = $m[1]; $cidr = (int)$m[2];
                $b  = $m[3] ?? null;
                if (!$b) $b = $this->calcBroadcastFromIpCidr($ip, $cidr);
                return ['ip' => $ip, 'broadcast' => $b];
            }
        }
        return ['ip' => null, 'broadcast' => null];
    }

    /** 只回傳廣播位址；若找不到回 null */
    public function getBroadcastOnly(): ?string {
        [$iface, ] = $this->getDefaultIfaceAndGateway();
        if (!$iface) return null;
        $info = $this->getIPv4AndBroadcast($iface);
        return $info['broadcast'] ?? null;
    }

    /** 只回傳預設 gateway；若找不到回 null */
    public function getGatewayOnly(): ?string {
        [, $gw] = $this->getDefaultIfaceAndGateway();
        return $gw ?: null;
    }

    /**
     * 自動偵測 IP 與「-b 要帶的值」並呼叫 Ethernet_Setting
     * 預設使用 **broadcast** 當 -b；若要改帶 gateway，把 $useBroadcastAsB = false
     */
    public function applyEthernetSettingAuto(bool $useBroadcastAsB = true): array {
        [$iface, $gw] = $this->getDefaultIfaceAndGateway();
        if (!$iface) {
            return ['ok' => false, 'msg' => '找不到預設路由介面', 'stdout' => '', 'cmd' => null];
        }

        $info = $this->getIPv4AndBroadcast($iface);
        $ip = $info['ip'] ?? null;
        $broadcast = $info['broadcast'] ?? null;

        if (!$ip) {
            return ['ok' => false, 'msg' => '無法取得 IPv4', 'stdout' => '', 'cmd' => null];
        }

        $bArg = $useBroadcastAsB ? ($broadcast ?? '') : ($gw ?? '');
        if ($bArg === '') {
            return ['ok' => false, 'msg' => $useBroadcastAsB ? '無法取得廣播位址' : '無法取得 Gateway', 'stdout' => '', 'cmd' => null];
        }

        $cmd = sprintf(
            'sudo /home/kls/NTCS7/Ethernet_Setting -i %s -b %s 2>&1',
            escapeshellarg($ip),
            escapeshellarg($bArg)
        );
        [$code, $stdout] = $this->runCmd($cmd);

        return [
            'ok'        => ($code === 0),
            'msg'       => $code === 0 ? 'done' : "exit code $code",
            'stdout'    => $stdout,
            'cmd'       => $cmd,
            'iface'     => $iface,
            'ip'        => $ip,
            'broadcast' => $broadcast,
            'gateway'   => $gw,
            'b_used'    => $bArg,
        ];
    }



}
