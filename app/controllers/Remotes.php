<?php
/*
 * Single-codebase platform switch.
 * /home/kls/upgrade/icontroller = 1 -> i-controller implementation
 * 0 / missing / invalid -> NTCS implementation
 */
if (defined('IS_ICONTROLLER') && IS_ICONTROLLER) {
class Remotes extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    Private $deviceId;
    private $ToolModel;
    
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct(){
        
        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->ToolModel = $this->model('Tool');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();


    }

    // 取得所有Jobs
    public function index(){

   
        // 同步控制器資料庫（ntcs_data.db）至 iDAS
        $this->ntcs_data_db_sysnc();
        


        $isMobile = $this->isMobileCheck();
        $job_list = $this->SettingModel->get_job_list();

        $data = [
            'isMobile' => $isMobile,
            'job_list' => $job_list,
        ];
        
        $this->view('remote/index', $data);

    }

    public function Change_Job($value=''){

        header('Content-Type: application/json; charset=utf-8');

        $error_message = '';
        $input_check = true;

        if (isset($_GET['job_id']) && $_GET['job_id'] !== '') {
            $job_id = (int)$_GET['job_id'];
            if ($job_id <= 0) {
                $input_check = false;
                $error_message .= "job_id,";
            }
        } else {
            $input_check = false;
            $error_message .= "job_id,";
        }

        // seq_id 允許 0，避免控制器目前回傳 Seq 0 時被 empty() 判斷成未填。
        if (isset($_GET['seq_id']) && $_GET['seq_id'] !== '') {
            $seq_id = (int)$_GET['seq_id'];
            if ($seq_id < 0) {
                $input_check = false;
                $error_message .= "seq_id,";
            }
        } else {
            $input_check = false;
            $error_message .= "seq_id,";
        }

        $device_id = (int)$this->deviceId;
        if ($device_id < 1 || $device_id > 255) {
            $input_check = false;
            $error_message .= 'device_id,';
        }

        if ($input_check && PHP_OS_FAMILY == 'Linux') {
            try {
                $isOp = $this->is_op_protocol_enabled();

                if ($isOp) {
                    /*
                     * OP 協議一次只能送一筆，但位址仍要保留 Modbus Register 對應關係：
                     *   463 = Change Job ID
                     *   464 = Change Seq ID
                     *
                     * 前一版改成 463 / 463 會造成 Controller 端無法正確切換工序。
                     * 正確做法是「一筆一筆送」，不是「兩筆都送同一個位址」。
                     */
                    $opCommands = [];

                    $opCommands[] = 'IDAS_WRITE_463_' . $job_id;
                    $jobOk = $this->op_write(463, $job_id);
                    if (!$jobOk) {
                        throw new RuntimeException('OP write job_id failed');
                    }

                    // OP Controller 一次處理一筆命令，兩筆命令中間保留短暫處理時間。
                    usleep(120000);

                    $opCommands[] = 'IDAS_WRITE_464_' . $seq_id;
                    $seqOk = $this->op_write(464, $seq_id);
                    if (!$seqOk) {
                        throw new RuntimeException('OP write seq_id failed');
                    }

                    // OP WRITE 本身沒有 ACK；改讀 Controller 真實狀態 4305/4306 驗證切換結果。
                    // 最多等待約 1 秒，避免 TCP 有送出、Controller 卻沒有真正套用時誤報成功。
                    $verified = $this->protocol_wait_for_register_values(
                        $device_id,
                        4305,
                        [$job_id, $seq_id],
                        5,
                        200000
                    );
                    if (!$verified) {
                        throw new RuntimeException('OP write sent but controller JOB/SEQ read-back verification failed');
                    }

                    echo json_encode([
                        'error' => '',
                        'protocol' => 'OP',
                        'job_id' => $job_id,
                        'seq_id' => $seq_id,
                        'op_commands' => $opCommands,
                        'op_change_job_register_pair' => true,
                        'verified' => true,
                        'verify_register_start' => 4305,
                    ], JSON_UNESCAPED_UNICODE);
                    exit();
                }

                $ok = $this->protocol_write_registers($device_id, 463, [$job_id, $seq_id]);
                if (!$ok) {
                    throw new RuntimeException('protocol write failed');
                }

                echo json_encode([
                    'error' => '',
                    'protocol' => 'MODBUS',
                    'job_id' => $job_id,
                    'seq_id' => $seq_id,
                ], JSON_UNESCAPED_UNICODE);
                exit();
            } catch (Throwable $e) {
                $this->logMessage('remote change job fail: ' . $e->getMessage());
                echo json_encode([
                    'error' => 'protocol fail',
                    'msg' => $e->getMessage(),
                    'protocol' => $this->is_op_protocol_enabled() ? 'OP' : 'MODBUS',
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }
        } else {
            echo json_encode(['error' => $error_message], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }


    public function get_current_job($value=''){

        $error_message = '';
        $device_id = (int)$this->deviceId;

        if ($device_id < 1 || $device_id > 255) {
            $error_message = 'device_id,';
        }

        if (PHP_OS_FAMILY == 'Linux' && $error_message === '') {
            try {
                $recData = $this->protocol_read_registers($device_id, 4305, 3);

                $data = [];
                $data['jod_id']  = (int)($recData[0] ?? 0);
                $data['seq_id']  = (int)($recData[1] ?? 0);
                $data['step_id'] = (int)($recData[2] ?? 0);

                echo json_encode(['error' => '', 'result' => $data]);
                exit();
            } catch (Throwable $e) {
                $this->logMessage('protocol read 4305 fail: ' . $e->getMessage());
                echo json_encode(['error' => 'protocol fail']);
                exit();
            }
        } else {
            echo json_encode(['error' => $error_message]);
            exit();
        }
    }


    
}
} else {
class Remotes extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    Private $deviceId;
    private $ToolModel;
    
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct(){
        
        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->ToolModel = $this->model('Tool');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();


    }

    // 取得所有Jobs
    public function index(){

   
        // 同步控制器資料庫（ntcs_data.db）至 iDAS
        $this->ntcs_data_db_sysnc();
        


        $isMobile = $this->isMobileCheck();
        $job_list = $this->SettingModel->get_job_list();

        $data = [
            'isMobile' => $isMobile,
            'job_list' => $job_list,
        ];
        
        $this->view('remote/index', $data);

    }


    /**
     * 取得目前登入者權限等級。
     * law = 3 為 Operator，只可瀏覽，不允許切換工作。
     */
    private function getCurrentUserLaw(): ?int{

        $keys = ['user_law', 'law', 'userLaw', 'user_level', 'permission', 'role_law'];
        $sources = [$_SESSION ?? [], $_COOKIE ?? []];

        foreach ($sources as $source) {
            foreach ($keys as $key) {
                if (isset($source[$key]) && is_numeric($source[$key])) {
                    return (int)$source[$key];
                }
            }
        }

        return null;
    }

    /**
     * 判斷是否為 Operator 權限。
     */
    private function isOperatorLogin(): bool{

        $law = $this->getCurrentUserLaw();
        if ($law === 3) {
            return true;
        }

        // 備援：部分舊版可能只存角色名稱，避免未帶 law 時被繞過。
        $roleKeys = ['role', 'user_role', 'account_role', 'permission_name'];
        $sources = [$_SESSION ?? [], $_COOKIE ?? []];

        foreach ($sources as $source) {
            foreach ($roleKeys as $key) {
                if (isset($source[$key])) {
                    $role = strtolower(trim((string)$source[$key]));
                    if ($role === 'operator') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Operator 切換工作限制訊息。
     */
    private function getOperatorRemoteDeniedMessage(): string{

        $lang = strtolower(trim((string)($_SESSION['language'] ?? $_COOKIE['language'] ?? 'zh-tw')));

        if ($lang === 'zh-cn') {
            return 'Operator 权限不允许切换工作。';
        }

        if ($lang === 'en-us' || $lang === 'en') {
            return 'Operator permission is not allowed to switch job.';
        }

        return 'Operator 權限不允許切換工作。';
    }

    /**
     * 後端防呆：避免直接打 Remotes/change_job URL 繞過前端按鈕限制。
     */
    private function denyChangeJobIfOperator(): void{

        if (!$this->isOperatorLogin()) {
            return;
        }

        while (ob_get_level()) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }

        echo json_encode([
            'success'  => false,
            'error'    => 'OPERATOR_REMOTE_DENIED',
            'res_type' => 'Error',
            'res_code' => 'OPERATOR_REMOTE_DENIED',
            'res_msg'  => $this->getOperatorRemoteDeniedMessage(),
            'msg'      => $this->getOperatorRemoteDeniedMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function Change_Job($value=''){

        header('Content-Type: application/json; charset=utf-8');

        // Operator 權限不可切換 Controller 目前工作（後端防繞過）。
        $this->denyChangeJobIfOperator();

        $error_message = '';
        $input_check = true;

        if (isset($_GET['job_id']) && $_GET['job_id'] !== '') {
            $job_id = (int)$_GET['job_id'];
            if ($job_id <= 0) {
                $input_check = false;
                $error_message .= "job_id,";
            }
        } else {
            $input_check = false;
            $error_message .= "job_id,";
        }

        // seq_id 允許 0，避免控制器目前回傳 Seq 0 時被 empty() 判斷成未填。
        if (isset($_GET['seq_id']) && $_GET['seq_id'] !== '') {
            $seq_id = (int)$_GET['seq_id'];
            if ($seq_id < 0) {
                $input_check = false;
                $error_message .= "seq_id,";
            }
        } else {
            $input_check = false;
            $error_message .= "seq_id,";
        }

        $device_id = (int)$this->deviceId;
        if ($device_id < 1 || $device_id > 255) {
            $input_check = false;
            $error_message .= 'device_id,';
        }

        if ($input_check && PHP_OS_FAMILY == 'Linux') {
            try {
                $isOp = $this->is_op_protocol_enabled();

                if ($isOp) {
                    /*
                     * OP 協議一次只能送一筆，但位址仍要保留 Modbus Register 對應關係：
                     *   463 = Change Job ID
                     *   464 = Change Seq ID
                     *
                     * 前一版改成 463 / 463 會造成 Controller 端無法正確切換工序。
                     * 正確做法是「一筆一筆送」，不是「兩筆都送同一個位址」。
                     */
                    $opCommands = [];

                    $opCommands[] = 'IDAS_WRITE_463_' . $job_id;
                    $jobOk = $this->op_write(463, $job_id);
                    if (!$jobOk) {
                        throw new RuntimeException('OP write job_id failed');
                    }

                    // OP Controller 一次處理一筆命令，兩筆命令中間保留短暫處理時間。
                    usleep(120000);

                    $opCommands[] = 'IDAS_WRITE_464_' . $seq_id;
                    $seqOk = $this->op_write(464, $seq_id);
                    if (!$seqOk) {
                        throw new RuntimeException('OP write seq_id failed');
                    }

                    // 不在後端強制判定讀回失敗，避免 Controller 切換需要較長時間時誤報。
                    // 前端會延遲後呼叫 get_current_job() 讀取實際狀態。
                    echo json_encode([
                        'error' => '',
                        'protocol' => 'OP',
                        'job_id' => $job_id,
                        'seq_id' => $seq_id,
                        'op_commands' => $opCommands,
                        'op_change_job_register_pair' => true,
                    ], JSON_UNESCAPED_UNICODE);
                    exit();
                }

                $ok = $this->protocol_write_registers($device_id, 463, [$job_id, $seq_id]);
                if (!$ok) {
                    throw new RuntimeException('protocol write failed');
                }

                echo json_encode([
                    'error' => '',
                    'protocol' => 'MODBUS',
                    'job_id' => $job_id,
                    'seq_id' => $seq_id,
                ], JSON_UNESCAPED_UNICODE);
                exit();
            } catch (Throwable $e) {
                $this->logMessage('remote change job fail: ' . $e->getMessage());
                echo json_encode([
                    'error' => 'protocol fail',
                    'msg' => $e->getMessage(),
                    'protocol' => $this->is_op_protocol_enabled() ? 'OP' : 'MODBUS',
                ], JSON_UNESCAPED_UNICODE);
                exit();
            }
        } else {
            echo json_encode(['error' => $error_message], JSON_UNESCAPED_UNICODE);
            exit();
        }
    }


    public function get_current_job($value=''){

        $error_message = '';
        $device_id = (int)$this->deviceId;

        if ($device_id < 1 || $device_id > 255) {
            $error_message = 'device_id,';
        }

        if (PHP_OS_FAMILY == 'Linux' && $error_message === '') {
            try {
                $recData = $this->protocol_read_registers($device_id, 4305, 3);

                $data = [];
                $data['jod_id']  = (int)($recData[0] ?? 0);
                $data['seq_id']  = (int)($recData[1] ?? 0);
                $data['step_id'] = (int)($recData[2] ?? 0);

                echo json_encode(['error' => '', 'result' => $data]);
                exit();
            } catch (Throwable $e) {
                $this->logMessage('protocol read 4305 fail: ' . $e->getMessage());
                echo json_encode(['error' => 'protocol fail']);
                exit();
            }
        } else {
            echo json_encode(['error' => $error_message]);
            exit();
        }
    }


    
}
}
