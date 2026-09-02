<?php

class Admins extends Controller
{
    // 在建構子中將 Post 物件（Model）實例化
    private $MiscellaneousModel;
    private $AdminModel;
    Private $deviceId;
    private $res_agent;

    public function __construct()
    {
        $this->AdminModel = $this->model('Admin');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        
        #該死的需求 去撈控制器的資料庫 同步找出modbus id 

        # 啟動 agent 
        //$this->res_agent =  $this->runAgentInitial();



    }

    // 取得所有info
    public function index(){

        $isMobile = $this->isMobileCheck();
        $active_session = $this->AdminModel->GetActiveSession();
        $max_user = $this->AdminModel->Get_Das_Config('max_concurrent_users');
        $agent_server_ip = $this->AdminModel->Get_Das_Config('agent_server_ip');
        $agent_type = $this->AdminModel->Get_Das_Config('agent_type');

        $data = [
            'isMobile' => $isMobile,
            'active_session' => $active_session,
            'max_user' => $max_user,
            'agent_server_ip' => $agent_server_ip,
            'agent_type' => $agent_type,
        ];

        if($_SESSION['privilege'] == 'admin'){
            $this->view('admin/index', $data);
        }else{
            $this->view('dashboards/index', $data);
        }
        
    }

    //
    public function DeleteSession()
    {
        if (isset($_POST['sessions']) && is_array($_POST['sessions'])) {
            $sessionsToDelete = $_POST['sessions'];
            $result = $this->AdminModel->DeleteSession($sessionsToDelete);
        }
        $redirect_page = '../public/?url=Settings';
        header('Location:'  .$redirect_page);
        die();
    }

    //
    public function EditMaxLink()
    {
        $result = false;
        if (isset($_POST['max_user']) && is_numeric($_POST['max_user'])  && $_POST['max_user'] >= 1 ) {
            $max_user = $_POST['max_user'];
            $result = $this->AdminModel->Edit_Max_Link($max_user);
            if($result){
                $res_msg = 'Edit: Number of Connection  success';
            }else{
                $res_msg = 'Edit: Number of Connection  fail';
            }
            echo $res_msg;
        }
    }

    //
    public function EditGuestPwd()
    {
        $result = false;
        $error_message = '';
        // code... table `device`
        if( !empty($_POST['new_password']) && isset($_POST['new_password'])  ){
            $new_password = $_POST['new_password'];
        }else{ 
            $input_check = false; 
            $error_message .= "new_password,";
        }
        if( !empty($_POST['comfirm_password']) && isset($_POST['comfirm_password'])  ){
            $comfirm_password = $_POST['comfirm_password'];
        }else{ 
            $input_check = false; 
            $error_message .= "comfirm_password,";
        }

        if ( $new_password != '') {
            $result = $this->AdminModel->Edit_Guest_Password($new_password);
        }else{
            $result = false;
        }
        
        if($result){
            $res_msg = 'edit: guest password  success';
        }else{
            $res_msg = 'edit: guest password  fail';
        }
        echo $res_msg;

    }

    //
    public function SetAgentIp(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if (isset($_POST['agent_server_ip'])) {
            $ip = $_POST['agent_server_ip'];
            $result = $this->AdminModel->Set_Agent_Ip($ip);
        }

        if($result){
            $res_ip = $ip;
            $res_msg = $text['Edit'].' IP:'.$res_ip."  ".$text['success'];
            $this->MiscellaneousModel->generateErrorResponse('Success', $res_msg,$res_ip);

        }else{
            $res_ip = $ip;
            $res_msg = $text['Edit'].' IP:'.$res_ip."  ".$text['fail'];
            $this->MiscellaneousModel->generateErrorResponse('Error', $res_msg,$res_ip);
        }
    }

    //
    public function SetAgentType(){
        
        // ------------------------------
        // Language
        // ------------------------------
        $lang = $_SESSION['language'] ?? 'zh-tw';
        $lang = strtolower((string)$lang);
        if ($lang === 'en') $lang = 'en-us';
        if (!in_array($lang, ['en-us', 'zh-tw', 'zh-cn'], true)) $lang = 'zh-tw';

        // ------------------------------
        // i18n messages
        // ------------------------------
        $MSG = [
            'success' => [
                'zh-tw' => 'Agent 類型更新成功',
                'zh-cn' => 'Agent 类型更新成功',
                'en-us' => 'Agent type updated successfully',
            ],
            'fail' => [
                'zh-tw' => 'Agent 類型更新失敗',
                'zh-cn' => 'Agent 类型更新失败',
                'en-us' => 'Failed to update agent type',
            ],
            'invalid' => [
                'zh-tw' => '參數錯誤：agent_type 必須為 0~2',
                'zh-cn' => '参数错误：agent_type 必须为 0~2',
                'en-us' => 'Invalid parameter: agent_type must be 0~2',
            ],
        ];

        // ------------------------------
        // Validate
        // ------------------------------
        $result = false;

        if (isset($_POST['agent_type']) && is_numeric($_POST['agent_type'])) {

            $agent_type = (int)$_POST['agent_type'];

            if ($agent_type >= 0 && $agent_type <= 2) {
                $result = $this->AdminModel->Set_Das_Config('agent_type', $agent_type);
            } else {
                // invalid range
                $this->MiscellaneousModel->generateErrorResponse('Error', $MSG['invalid'][$lang]);
                return;
            }

        } else {
            // missing / non-numeric
            $this->MiscellaneousModel->generateErrorResponse('Error', $MSG['invalid'][$lang]);
            return;
        }

        // ------------------------------
        // Response
        // ------------------------------
        if ($result) {
            $this->MiscellaneousModel->generateErrorResponse('Success', $MSG['success'][$lang]);
        } else {
            $this->MiscellaneousModel->generateErrorResponse('Error', $MSG['fail'][$lang]);
        }
    }


    public function AgentTest()
    {
        $message = [];
        $message['server_status'] = $this->ProcessCheck('agent_server.php');//1.檢測server.php
        $message['client_status'] = $this->ProcessCheck('agent_client.php');//2.檢測client.php

        echo json_encode($message);
    }

    public function ProcessCheck($processName)
    {
        $pgrepCommand = "pgrep -f  ". escapeshellarg($processName) ." ";
        $pidList = [];
        exec($pgrepCommand, $pidList);
        
        if ( !empty($pidList) && count($pidList) >= 2 ) {
            // $message = "进程正在运行。\n";
            $result = 'true';
        } else {
            // $message = "进程未找到，可能未在运行。\n";
            $result = 'false';
        }

        return $result;
    }

    public function StartAgent(){
        
        $agent_type = $this->AdminModel->Get_Das_Config('agent_type');
        $this->StopService("agent_client.php");
        sleep(1);
        $this->StopService("agent_server.php");
        sleep(1);


        $agent_type = (int)$agent_type;

        if ($agent_type == 1) {
            $this->StartService(idas_path('service_root', 'agent_client.php'));
        }

        if ($agent_type == 2) {
            $this->StartService(idas_path('service_root', 'agent_server.php'));
            sleep(1);
            $this->StartService(idas_path('service_root', 'agent_client.php'));
        }

        $message['server_status'] = $this->ProcessCheck('agent_server.php');//1.檢測server.php
        $message['client_status'] = $this->ProcessCheck('agent_client.php');//2.檢測client.php

        echo json_encode($message);
        
        
    }

    public function CloseAgent()
    {
        $message = [];
        $message['client_status'] = $this->StopService("agent_client.php");//1.檢測server.php
        $message['server_status'] = $this->StopService("agent_server.php");//2.檢測client.php

        // 再把 9501、9502 這兩個埠上的 listener 一次關掉
        $message['ports'] = $this->StopPorts([9501, 9502]);


        echo json_encode($message);
    }

    public function StartService($processName)
    {
        $pgrepCommand = "php " . escapeshellarg($processName) . "  > /dev/null 2>&1 & ";
        $pidList = [];
        exec($pgrepCommand, $pidList);

        if (!empty($pidList)) {
            // $message = "进程正在运行。\n";
            $result = 'true';
        } else {
            // $message = "进程未找到，可能未在运行。\n";
            $result = 'false';
        }

        return $result;
    }

    public function StopService($processName)
    {
        // $processName = "client2.php"; // 要查找和杀死的进程名称
        $message = '';

        // 使用 pgrep 命令查找指定进程名称的 PID
        $pgrepCommand = "pgrep -f " . escapeshellarg($processName) . "";
        $pidList = [];
        exec($pgrepCommand, $pidList);

        // var_dump($pgrepCommand);
        // var_dump($pidList);

        // 如果找到了匹配的 PID，则杀死它们
        if (!empty($pidList)) {
            foreach ($pidList as $pid) {
                // 使用 kill 命令杀死指定 PID 的进程
                $killCommand = "sudo kill " . escapeshellarg($pid);
                exec($killCommand);
                $message .= "Killed process with PID: $pid\n";
            }
        } else {
            $message .= "No matching processes found.\n";
        }

        return $message;

    }

    private function StopPorts(array $ports): array
    {
        $killed = [];
        $notFound = [];

        foreach ($ports as $p) {
            $port = (int)$p;
            if ($port < 1 || $port > 65535) { continue; }

            // 1) 試 fuser 一次關
            $out = []; $code = 0;
            exec("sudo fuser -k {$port}/tcp 2>&1", $out, $code);
            if ($code === 0) { $killed[] = $port; continue; }

            // 2) 退而求其次：用 lsof 抓 LISTEN 的 PID 再 kill
            $pids = [];
            exec("lsof -t -iTCP:{$port} -sTCP:LISTEN 2>/dev/null", $pids);
            if (!empty($pids)) {
                foreach ($pids as $pid) {
                    $pid = (int)$pid;
                    if ($pid > 0) { exec("sudo kill " . escapeshellarg($pid)); }
                }
                $killed[] = $port;
            } else {
                $notFound[] = $port;
            }
        }

        return ['killed' => $killed, 'not_found' => $notFound];
    }



    public function EditCsvPath()
    {
        $result = false;
        $error_message = '';
        if (isset($_POST['file_path']) ) {
            $file_path = $_POST['file_path'];
            $phpPath = str_replace('\\', '/', $file_path);
            // $result = $this->AdminModel->Edit_Csv_Path($file_path);
            $result = $this->AdminModel->Set_Das_Config('csv_file_path',$phpPath);
        }

        if(!$result){
            echo json_encode(array('error' => 'fail'));
            exit();
        }else{
            echo json_encode(array('error' => ''));
            exit();
        }
    }

    //切換local起子設定
    public function Tool_Select()
    {
        if (isset($_POST['tool_type'])) {

            $tool_type = $_POST['tool_type'];

            $result = $this->AdminModel->Change_Screw_Local($tool_type);

            if($result){
                echo json_encode(array('error' => 'success change to '.$tool_type));
                exit();
            }else{
                echo json_encode(array('error' => 'fail change to '.$tool_type));
                exit();
            }
            
        }else{
            echo json_encode(array('error' => 'fail'));
            exit();
        }
        
    }


    
}
