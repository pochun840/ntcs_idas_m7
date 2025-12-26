<?php

class Tools extends Controller
{
    private $ToolModel;
    private $MiscellaneousModel;
    private $DataModel;
    Private $deviceId;

    public function __construct()
    {
        $this->ToolModel = $this->model('Tool');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->DataModel = $this->model('Datas');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();


    }

    // 取得所有info
    public function index(){

        // 同步控制器資料庫（ntcs_data.db）至 iDAS
        $this->ntcs_data_db_sysnc();

        // ------------------------------
        // Tool Spec Sync (Web-triggered)
        // ------------------------------
        // Gate(10s): 限制同步檢查頻率，避免每個 request 都打 DB
        // Lock:     使用 flock 防止多 request 同步造成重複/競態
        // Sync:     只在來源(controller)與目的(iDAS)值不同時才更新
        // Note:     非 cron；沒有 request 就不會自動同步
        if ($this->shouldRunToolSpecSync(10)) {
            $this->runOnceWithFlag(
                '/var/www/html/database',        // lock / state 檔案目錄
                '.tool_spec_sync',               // 任務鎖名稱（key）
                fn() => $this->check_tools_info()// 同步 ntcs_tool_test 規格值
            );
        }

        // ===== Tool Spec Sync UI Status =====
        $tool_sync_state = 'synced'; // 預設：已套用

        $stateFn = '/var/www/html/database/.tool_spec_sync.json';
        if (is_file($stateFn)) {
            $state = json_decode((string)@file_get_contents($stateFn), true);
            if (is_array($state)) {
                // controller 與 iDAS 規格不同 → 套用中
                if (($state['last_values'] ?? null) !== null) {
                    // 用 last_sync_at 判斷是否剛更新過 controller 但尚未同步
                    if ((time() - (int)($state['last_sync_at'] ?? 0)) < 30) {
                        $tool_sync_state = 'applying';
                    }
                }
            }
        }

        $data['tool_sync_state'] = $tool_sync_state;


        $isMobile = $this->isMobileCheck();
        $Tool_Info = $this->ToolModel->GetToolInfo();
        $Tool_Info = end($Tool_Info);

        $controllers_info = $this->ToolModel->GetControllerInfo();

        $device_id = isset( $this->deviceId) ? (int) $this->deviceId : 1; 
        $unitId = ($device_id >= 1 && $device_id <= 255) ? $device_id : 1;

        $MAC      = $this->getMacAddress();
        $ip_addr  = $this->getIp();
        $netmask  = $this->get_netmask('eth0');

        // 取得預設路由介面與 gateway
        list($iface, $gw) = $this->getDefaultIfaceAndGateway();

        // === Normalize gateway from ip route (DHCP 下 via 可能是 .255；以 ip route 為準) ===
        $route = trim(@shell_exec('ip -4 route show default 2>/dev/null') ?: '');
        $gw_via = null;
        if ($route && preg_match('/\bdefault\s+via\s+([0-9.]+)/', $route, $m)) {
            $gw_via = $m[1];
        }
        if (!empty($gw_via)) { $gw = $gw_via; }

        // === Detect IPv4 method: DHCP / static / unknown ===
        $net_method  = 'unknown';
        $method_info = ['method' => 'unknown', 'detector' => null, 'raw' => null];
        if (!empty($iface)) {
            $method_info = $this->detectIpv4Method($iface);
            $net_method  = $method_info['method'];
        }

        // 先準備預設值，避免 $iface 為 null 直接呼叫造成 TypeError
        $gateway_temp = ['ip' => null, 'broadcast' => null];

        if ($iface) {
            // 介面已知，直接抓 IPv4 與廣播
            $gateway_temp = $this->getIPv4AndBroadcast($iface);
        } elseif (!empty($ip_addr)) {
            // 介面未知：用現有 IP + netmask 算出廣播（fallback）
            $mask  = $netmask ?: '255.255.255.0';
            $bcast = $this->_calcBroadcast($ip_addr, $mask);
            $gateway_temp = ['ip' => $ip_addr, 'broadcast' => $bcast];
        }

        // 若還沒有 gateway（ip route / proc 都沒撈到），用自動偵測再試一次
        if (empty($gw)) {
            $gw = $this->getGatewayAuto($iface ?: 'eth0');
        }

        // 🔁 Fallback：iface 取不到或抓不到 broadcast，就用目前 IP+子網遮罩自行計算
        if ((!$iface || empty($gateway_temp['broadcast'])) && $ip_addr) {
            $mask  = $netmask ?: '255.255.255.0';
            $bcast = $this->_calcBroadcast($ip_addr, $mask);
            $gateway_temp = ['ip' => $ip_addr, 'broadcast' => $bcast];
        }

        $version = $this->getFirmwareVersion();


        // 版本資訊
        $tools_version    = $this->get_tools_version($unitId) / 100; 
        $firmware_version = $this->get_firmware_version($unitId) / 100; 
        $upgrade_ver      = $this->get_upgrade_version();

        // 起子型號
        $tools_type_temp = $this->get_tools_type($unitId);
        if (!empty($tools_type_temp)){
            $this->ToolModel->update_tools($tools_type_temp);
            $Tool_Info['tool_type'] = $tools_type_temp['model'];
        }

        // 起子序號
        $tools_type_tmp = $this->get_tools_sn($unitId); 

        if (!empty($tools_type_tmp)){
            $this->ToolModel->update_tools_sn($tools_type_tmp);
            $Tool_Info['tool_sn'] = $tools_type_tmp['model'];
        }

        // 轉換扭力單位（補預設值）
        $unit_name = 'N·m';
        if (!empty($controllers_info)){
            $step_torque_unit = (int)$controllers_info['torque_unit'];
            $unit_map  = $this->MiscellaneousModel->details('torque_unit');
            if (isset($unit_map[$step_torque_unit])) {
                $unit_name  = $unit_map[$step_torque_unit];
            }
        }

        if (!empty($Tool_Info)){
            // 扭力 value 從 DB 取出來都要除以 1000
            $minTorque = (float)$Tool_Info['min_torque'] / 1000;
            $maxTorque = (float)$Tool_Info['max_torque'] / 1000;
            $low_torque_arr  = $this->MiscellaneousModel->convert_all_torque_units($minTorque, 1);
            $high_torque_arr = $this->MiscellaneousModel->convert_all_torque_units($maxTorque, 1);
            if (isset($low_torque_arr[$unit_name]))  $Tool_Info['min_torque'] = $low_torque_arr[$unit_name];
            if (isset($high_torque_arr[$unit_name])) $Tool_Info['max_torque'] = $high_torque_arr[$unit_name];
        }

        $gw_display = $gw;
        
        if ($net_method === 'DHCP') {
            $gw_display = $this->getBroadcast('eth0');
        }else{
            $gw_display = $this->getBroadcast('eth0');
        }

        // 組資料（確保 gateway/broadcast 映射正確）
        $data = [
            'isMobile'        => $isMobile,
            'Tool_Info'       => $Tool_Info,
            'Controllers_Info'=> $controllers_info,
            'IP'              => $ip_addr,
            'netmask'         => $netmask,
            'broadcast'       => $gateway_temp['broadcast'],
            'gateway'         => $gw_display,
            'gateway_ip'      => $gw, // 相容舊鍵名
            'unit_name'       => $unit_name,
            'MAC'             => $MAC,
            'image_version'   => is_array($version ?? null) ? ($version['version_info'] ?? null) : null,
            'tools_version'   => $tools_version,
            'firmware_version'=> $firmware_version,
            'upgrade_ver'     => $upgrade_ver,
            'ipv4_method'     => $net_method,
            'ipv4_method_meta'=> $method_info,
        ];


        $this->view('tool/index', $data);
    }

    public function getMacAddress(){
        if (PHP_OS_FAMILY == 'Linux'){
            $output = shell_exec("ip link show");
            preg_match('/link\/ether (\w{2}:\w{2}:\w{2}:\w{2}:\w{2}:\w{2})/', $output, $matches);
            if (!empty($matches)) return strtoupper($matches[1]);
            return false;
        } else {
            $MAC = exec('getmac');
            $MAC = strtok($MAC, ' ');
            $MAC = str_replace('-',':',$MAC);
            return $MAC;
        }
    }

    public function getIp(){
        if (PHP_OS_FAMILY == 'Linux'){
            $Ips = trim(shell_exec("/sbin/ip -o -4 addr list  | awk '{print $4}' | cut -d/ -f1"));
            $Ip = explode(PHP_EOL, $Ips);
            return strtoupper($Ip[1] ?? ($Ip[0] ?? ''));
        } else {
            $host_addr= gethostname();
            $ip_addr  = gethostbyname($host_addr);
            return strtoupper($ip_addr);
        }
    }

    public function get_subnet($ip, $netmask = '255.255.255.0') {
        $ip_long = ip2long($ip);
        $mask_long = ip2long($netmask);
        $subnet_long = $ip_long & $mask_long;
        return long2ip($subnet_long);
    }

    public function get_netmask($interface = 'eth0') {
        $output = shell_exec("ip -o -f inet addr show $interface | awk '{print $4}'");
        if (preg_match('/\d+\.\d+\.\d+\.\d+\/(\d+)/', $output, $matches)) {
            $cidr = (int)$matches[1];
            return long2ip(-1 << (32 - $cidr));
        }
        return null;
    }

    /** 只取 gateway（IPv4） */
    public function get_gateway_ip(): ?string {
        // a) /proc/net/route
        $file = '/proc/net/route';
        if (is_readable($file) && ($fh = @fopen($file, 'r'))) {
            while (($line = fgets($fh)) !== false) {
                $line = trim($line);
                if ($line === '' || stripos($line, 'Iface') === 0) continue;
                $cols = preg_split('/\s+/', $line);
                if (count($cols) < 3) continue;

                $destHex = strtoupper($cols[1]);
                $gateHex = strtoupper($cols[2]);
                if ($destHex !== '00000000') continue;

                $le = hexdec($gateHex);
                $be = unpack('N', pack('V', $le))[1];
                $gw = long2ip($be);
                fclose($fh);
                if ($gw && filter_var($gw, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return $gw;
                break;
            }
            fclose($fh);
        }

        // b) 備援：多種系統指令
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

            if (preg_match('/\bdefault\s+via\s+([0-9]{1,3}(?:\.[0-9]{1,3}){3})\b/i', $out, $m)) {
                if (filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return $m[1];
            }
            if (preg_match('/^0\.0\.0\.0\s+([0-9]{1,3}(?:\.[0-9]{1,3}){3})\s+/m', $out, $m)) {
                if (filter_var($m[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return $m[1];
            }
        }
        return null;
    }

    /**
     * 從 /proc/net/route 解析預設閘道（回傳字串）
     */
    private function gatewayFromProc(): ?string{
        $path = '/proc/net/route';
        if (!is_readable($path)) return null;

        $fh = @fopen($path, 'r');
        if (!$fh) return null;

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

            $gwHex = strtoupper($parts[$map['Gateway']]); // 小端序 hex
            if (!preg_match('/^[0-9A-F]{8}$/', $gwHex)) continue;

            $bytes = array_reverse(str_split($gwHex, 2)); // little-endian → big-endian
            $ip = implode('.', array_map(fn($b) => hexdec($b), $bytes));
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { $gw = $ip; break; }
        }
        fclose($fh);
        return $gw;
    }

    public function getFirmwareVersion($file = '/boot/firmware/version') {
        if (is_readable($file)) {
            $raw = trim(file_get_contents($file));
        } else {
            $cmd = sprintf('sudo cat %s 2>/dev/null', escapeshellarg($file));
            $raw = trim(shell_exec($cmd));
        }
        if ($raw === '') return null;

        $result = [];
        if (preg_match('/^Ver:\s*(.+?)\s+Date:/', $raw, $m)) {
            $result['version_info'] = trim($m[1]);
        }

        $parts = preg_split('/\s*Date:\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($parts as $part) {
            if (preg_match('/^(\d{4}\/\d{1,2}\/\d{1,2})\s*(.+)$/s', trim($part), $m)) {
                $date = $m[1];
                $content = trim($m[2]);
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

                if (strcasecmp($destHex, '00000000') === 0) {
                    $res['iface']   = $iface;
                    $res['gateway'] = $this->_hexLittleToIp($gwHex);
                    break;
                }
            }
            fclose($f);
        }

        if (empty($res['iface'])) return $res;

        // 2) 用 ip 指令抓該 iface 的 ip/prefix/broadcast（若可用）
        $ipbin = $this->_whichIp();
        if ($ipbin) {
            $out = @shell_exec($ipbin.' -o -f inet addr show dev ' . escapeshellarg($res['iface']) . ' 2>/dev/null');
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
        $cmd = 'sudo cat ' . escapeshellarg($path) . ' 2>/dev/null';
        $txt = shell_exec($cmd) ?? '';
        return preg_split('/\R/', trim(ltrim($txt, '_')))[0] ?? '';
    }

    /** 執行命令並回傳 [exitCode, stdout(string)] */
    public function runCmd(string $cmd): array {
        $out = [];
        $code = 0;
        exec($cmd, $out, $code);
        return [$code, implode("\n", $out)];
    }

    /** 確保 default route 存在（Static 模式常用） */
    private function ensureDefaultRoute(string $iface, string $gw): void {
        if (!$iface || !$gw) return;
        $out = trim(@shell_exec('ip -4 route show default 2>/dev/null')) ?: '';
        $iface_q = preg_quote($iface, '/');
        $gw_q    = preg_quote($gw, '/');
        $ok  = preg_match('/\bvia\s+' . $gw_q . '\b.*\bdev\s+' . $iface_q . '\b/', $out);
        if (!$ok) {
            @shell_exec('sudo ip route replace default via ' . escapeshellarg($gw) . ' dev ' . escapeshellarg($iface) . ' 2>/dev/null');
        }
    }

    /** 取得預設路由的 [iface, gateway]；可省略參數自動偵測（不相依其他方法） */
    public function getDefaultIfaceAndGateway(?string $iface = null): array {
        // 1) 從 /proc/net/route 解析（零外部指令）
        $file = '/proc/net/route';
        if (is_readable($file) && ($fh = @fopen($file, 'r'))) {
            while (($line = fgets($fh)) !== false) {
                $line = trim($line);
                if ($line === '' || stripos($line, 'Iface') === 0) continue;
                $cols = preg_split('/\s+/', $line);
                if (count($cols) < 3) continue;

                $if      = $cols[0];
                $destHex = strtoupper($cols[1]);
                $gateHex = strtoupper($cols[2]);

                if ($iface && strcasecmp($if, $iface) !== 0) continue; // 若指定介面，需匹配
                if ($destHex !== '00000000') continue;                 // default route

                $le = hexdec($gateHex);
                $be = unpack('N', pack('V', $le))[1];
                $gw = long2ip($be);
                fclose($fh);
                return [$if ?: null, $gw ?: null];
            }
            fclose($fh);
        }

        // 2) 備援：用 iproute2 查詢 default（可選擇性鎖定 iface）
        $cmd = $iface
            ? 'ip -4 route show dev ' . escapeshellarg($iface) . ' default 2>/dev/null'
            : 'ip -4 route show default 2>/dev/null';
        $out = @shell_exec($cmd);
        $out = is_string($out) ? trim($out) : '';
        if ($out !== '') {
            $gw  = null;
            $dev = $iface;
            if (preg_match('/\bvia\s+([0-9.]+)/', $out, $m)) $gw = $m[1];
            if (!$dev && preg_match('/\bdev\s+(\S+)/', $out, $m)) $dev = $m[1];
            if ($dev || $gw) return [$dev ?: null, $gw ?: null];
        }
        return [null, null];
    }

    /** 32-bit 安全：從 IP/CIDR 計算廣播位址（位元組法） */
    public function calcBroadcastFromIpCidr(string $ip, int $cidr): ?string {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) return null;
        if ($cidr < 0 || $cidr > 32) return null;

        $bin = @inet_pton($ip);
        if ($bin === false || strlen($bin) !== 4) return null;
        $bytes = unpack('C4', $bin);

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
    public function getIPv4AndBroadcast(?string $iface): array {
        if (!$iface) return ['ip' => null, 'broadcast' => null];

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

        // 退回 ip -4 -o
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
     * 預設使用 gateway 當 -b（用於 static 環境較直覺）
     */
    public function applyEthernetSettingAuto(bool $useBroadcastAsB = false): array {
        [$iface, $gw] = $this->getDefaultIfaceAndGateway();
        if (!$iface) {
            return ['ok' => false, 'msg' => '找不到預設路由介面', 'stdout' => '', 'cmd' => null];
        }

        $info = $this->getIPv4AndBroadcast($iface);
        $ip  = $info['ip'] ?? null;
        $brd = $info['broadcast'] ?? null;
        if (!$ip) {
            return ['ok' => false, 'msg' => '無法取得 IPv4', 'stdout' => '', 'cmd' => null];
        }

        // 用什麼當 -b 參數
        $bArg = $useBroadcastAsB ? ($brd ?? '') : ($gw ?? '');
        if ($bArg === '') {
            return ['ok' => false, 'msg' => $useBroadcastAsB ? '無法取得廣播位址' : '無法取得 Gateway', 'stdout' => '', 'cmd' => null];
        }

        // ✅ 只有當「用 gateway 當 -b」且同時有 iface/gw 時，才補 default route
        if (!$useBroadcastAsB && $iface && $gw) {
            $this->ensureDefaultRoute($iface, $gw);
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
            'broadcast' => $brd,
            'gateway'   => $gw,
            'b_used'    => $bArg,
        ];
    }

    /** 從 dhclient -v 的輸出擷取 Gateway（抓 DHCPACK 行的 "from <IP>"），失敗回傳 null */
    private function getGatewayFromDhclient(string $iface = 'eth0'): ?string {
        $bin = null;
        foreach (['/sbin/dhclient', '/usr/sbin/dhclient', trim(shell_exec('command -v dhclient 2>/dev/null'))] as $p) {
            if (!$p) continue;
            if ($p[0] === '/' ? is_executable($p) : $p !== '') { $bin = $p; break; }
        }
        if (!$bin) return null;

        $cmd = sprintf('sudo %s -v %s 2>&1', escapeshellarg($bin), escapeshellarg($iface));
        $out = shell_exec($cmd);
        if (!is_string($out) || $out === '') return null;

        if (preg_match_all('/\bDHCPACK\b[^\n]*\bfrom\s+([0-9]{1,3}(?:\.[0-9]{1,3}){3})/i', $out, $m) && !empty($m[1])) {
            return end($m[1]);
        }
        if (preg_match_all('/\bDHCPOFFER\b[^\n]*\bfrom\s+([0-9]{1,3}(?:\.[0-9]{1,3}){3})/i', $out, $m2) && !empty($m2[1])) {
            return end($m2[1]);
        }
        return null;
    }

    /** 從 dhclient/dhcpcd lease 檔抓 option routers（最後一筆） */
    private function getGatewayFromLease(string $iface = 'eth0'): ?string {
        $cands = array_merge(
            glob('/var/lib/dhcp/dhclient*.lease*') ?: [],
            glob('/var/lib/dhcp/*.leases') ?: [],
            glob('/var/lib/dhclient/*.lease') ?: [],
            glob('/var/lib/dhcpcd/*.lease') ?: []
        );
        foreach ($cands as $p) {
            if (!is_readable($p)) continue;
            $txt = @file_get_contents($p);
            if ($txt && preg_match('/interface\s+"'.preg_quote($iface,'/').'"/', $txt) || strpos($txt, "interface=$iface") !== false) {
                if (preg_match_all('/option\s+routers\s+([0-9.]+)\s*;/i', $txt, $mm) && !empty($mm[1])) {
                    return end($mm[1]);
                }
            }
        }
        return null;
    }

    /** 自動抓 gateway：先 /proc/ip route → 若沒拿到，再用 lease，最後才跑 dhclient */
    public function getGatewayAuto(?string $iface = null): ?string {
        if ($iface === null) {
            [$ifaceGuess, $gw] = $this->getDefaultIfaceAndGateway();
            if ($gw) return $gw;
            $iface = $ifaceGuess ?: 'eth0';
        }

        $gw = $this->getGatewayFromLease($iface);
        if ($gw) return $gw;

        return $this->getGatewayFromDhclient($iface);
    }

    /** 檢查系統是否有某指令 */
    private function _cmdExists(string $bin): bool {
        $out = @shell_exec('command -v ' . escapeshellarg($bin) . ' 2>/dev/null');
        return is_string($out) && trim($out) !== '';
    }

    /** 判斷指定介面的 IPv4 取得方式（DHCP / static / unknown），多層備援 */
    private function detectIpv4Method(string $iface): array {
        if (!$iface) return ['method' => 'unknown', 'detector' => null, 'raw' => null];

        // 1) NetworkManager（nmcli）
        if ($this->_cmdExists('nmcli')) {
            $devs = @shell_exec('nmcli -t -f DEVICE,STATE dev 2>/dev/null') ?: '';
            foreach (explode("\n", trim($devs)) as $row) {
                if ($row === '') continue;
                [$dev, $state] = array_pad(explode(':', $row, 2), 2, null);
                if ($dev === $iface && $state === 'connected') {
                    $conn = @shell_exec('nmcli -t -g GENERAL.CONNECTION dev show ' . escapeshellarg($iface) . ' 2>/dev/null');
                    $conn = is_string($conn) ? trim($conn) : '';
                    if ($conn !== '') {
                        $m = @shell_exec('nmcli -t -g ipv4.method connection show ' . escapeshellarg($conn) . ' 2>/dev/null');
                        $m = is_string($m) ? trim($m) : '';
                        if ($m !== '') {
                            $map = ['auto' => 'DHCP', 'manual' => 'static', 'shared' => 'static', 'disabled' => 'static'];
                            return ['method' => ($map[$m] ?? 'unknown'), 'detector' => 'nmcli', 'raw' => $m];
                        }
                    }
                }
            }
        }

        // 2) systemd-networkd（networkctl）
        if ($this->_cmdExists('networkctl')) {
            $s = @shell_exec('networkctl status ' . escapeshellarg($iface) . ' 2>/dev/null') ?: '';
            if ($s !== '') {
                if (preg_match('/DHCP4:\s*(yes|no)/i', $s, $m)) {
                    return ['method' => (strtolower($m[1]) === 'yes' ? 'DHCP' : 'static'), 'detector' => 'networkctl', 'raw' => strtolower($m[1])];
                }
            }
        }

        // 3) ip addr 顯示是否為 dynamic（有些發行版會標記）
        if ($this->_cmdExists('ip')) {
            $line = @shell_exec('ip -o -4 addr show dev ' . escapeshellarg($iface) . ' 2>/dev/null') ?: '';
            if ($line !== '') {
                if (preg_match('/\bdynamic\b/', $line)) {
                    return ['method' => 'DHCP', 'detector' => 'ip-addr', 'raw' => 'dynamic'];
                }
            }
        }

        // 4) DHCP 租約檔（dhclient/dhcpcd）
        $cands = array_merge(
            glob('/var/lib/dhcp/dhclient*.lease*') ?: [],
            glob('/var/lib/dhcp/*.leases') ?: [],
            glob('/var/lib/dhclient/*.lease') ?: [],
            glob('/var/lib/dhcpcd/*.lease') ?: []
        );
        foreach ($cands as $f) {
            if (!is_readable($f)) continue;
            $txt = @file_get_contents($f);
            if (!is_string($txt)) continue;
            if (preg_match('/interface\s+"'.preg_quote($iface,'/').'"/', $txt) || strpos($txt, "interface=$iface") !== false) {
                return ['method' => 'DHCP', 'detector' => 'lease-files', 'raw' => basename($f)];
            }
        }

        return ['method' => 'unknown', 'detector' => null, 'raw' => null];
    }


    public function getBroadcast($iface = 'eth0'){


        // 1) 優先：ip 命令（NetworkManager 與非 NM 都可）
        $cmd = 'ip -o -4 addr show dev ' . escapeshellarg($iface) . ' 2>/dev/null';
        @exec($cmd, $out, $rc);
        if ($rc === 0 && !empty($out)) {
            $text = implode("\n", $out);
            if (preg_match('/\bbrd\s+([0-9.]+)/', $text, $m)) {
                return $m[1];
            }
        }

        // 2) 退回：ifconfig（新舊兩種格式皆支援）
        $cmd = '/sbin/ifconfig ' . escapeshellarg($iface) . ' 2>/dev/null || ifconfig ' . escapeshellarg($iface) . ' 2>/dev/null';
        $out = [];
        @exec($cmd, $out, $rc);
        if (!empty($out)) {
            $text = implode("\n", $out);

            // 新式：... netmask 255.255.255.0 broadcast 192.168.0.255
            if (preg_match('/\bbroadcast\s+([0-9.]+)/i', $text, $m)) {
                return $m[1];
            }
            // 舊式：... Bcast:192.168.0.255
            if (preg_match('/\bBcast:([0-9.]+)/', $text, $m)) {
                return $m[1];
            }

            // 3) 最後備援：用 IP + netmask 計算 broadcast
            // 新式：inet 192.168.0.166  netmask 255.255.255.0 ...
            if (preg_match('/\binet\s+([0-9.]+)\s+.*?\bnetmask\s+([0-9.]+)/i', $text, $mm)) {
                $ip = $mm[1];
                $mask = $mm[2];
            }
            // 舊式：inet addr:192.168.0.166  Mask:255.255.255.0
            elseif (preg_match('/\binet\s+(?:addr:)?([0-9.]+)\s+.*?\bMask:([0-9.]+)/i', $text, $mm)) {
                $ip = $mm[1];
                $mask = $mm[2];
            } else {
                $ip = $mask = null;
            }

            if ($ip && $mask) {
                $ipL   = ip2long($ip);
                $maskL = ip2long($mask);
                if ($ipL !== false && $maskL !== false) {
                    $bcast = ($ipL & $maskL) | (~$maskL & 0xFFFFFFFF);
                    return long2ip($bcast);
                }
            }
        }

        return null;
    }
}
