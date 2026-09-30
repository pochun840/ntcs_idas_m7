<?php
/*
 * Single-codebase platform switch.
 * /home/kls/upgrade/icontroller = 1 -> i-controller implementation
 * 0 / missing / invalid -> NTCS implementation
 */

class Remotes extends Controller
{
    public function __construct(){
            
            $this->DataModel = $this->model('Datas');
            $this->SettingModel = $this->model('Setting');
            $this->MiscellaneousModel = $this->model('Miscellaneous');
            $this->ToolModel = $this->model('Tool');

            #該死的需求 去撈控制器的資料庫 同步找出modbus id 

        }

    /** Resolve the local controller Unit ID without probing login register 29002. */
    private function remoteControllerUnitId(): int
    {
        foreach ([
            idas_path('controller_root', 'ntcs_device.db'),
            idas_path('database_root', 'ntcs_device_IDAS.db'),
        ] as $dbPath) {
            if (!is_file($dbPath) || !is_readable($dbPath)) {
                continue;
            }
            try {
                $db = idas_sqlite_connect($dbPath);
                $value = $db->query('SELECT device_id FROM ntcs_device_test LIMIT 1')->fetchColumn();
                if ($value !== false && filter_var($value, FILTER_VALIDATE_INT, [
                    'options' => ['min_range' => 1, 'max_range' => 255],
                ]) !== false) {
                    return (int)$value;
                }
            } catch (Throwable $e) {
                // Try the iDAS copy if the controller DB is temporarily unavailable.
            }
        }
        return 1;
    }

    /** The NTCS base class has no getControllerTcpPort(); read this controller's wifi setting. */
    private function remoteControllerTcpPort(): int
    {
        foreach ([
            idas_path('controller_root', 'ntcs_device.db'),
            idas_path('database_root', 'ntcs_device_IDAS.db'),
        ] as $dbPath) {
            if (!is_file($dbPath) || !is_readable($dbPath)) {
                continue;
            }
            try {
                $db = idas_sqlite_connect($dbPath);
                $wifi = $db->query('SELECT wifi FROM ntcs_device_test LIMIT 1')->fetchColumn();
                if (!is_string($wifi)) {
                    continue;
                }
                $parts = explode('_', $wifi);
                $portText = trim((string)($parts[2] ?? ''));
                if (ctype_digit($portText)) {
                    $port = (int)$portText;
                    if ($port >= 1 && $port <= 65535) {
                        return $port;
                    }
                }
            } catch (Throwable $e) {
                // Try the iDAS copy when the controller settings are unavailable.
            }
        }
        return 502;
    }

    public function get_current_job($value=''){

            $error_message = '';
            $device_id = $this->remoteControllerUnitId();

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

    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    Private $deviceId;
    private $ToolModel;
    
    // 在建構子中將 Post 物件（Model）實例化

    // 取得所有Jobs

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

    /** Legacy command route: share the exact service used by api/job_switch.php. */
    public function Change_Job(...$args)
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->denyChangeJobIfOperator();
        require_once dirname(__DIR__, 2) . '/service/JobSwitchService.php';
        $job = filter_var($_GET['job_id'] ?? null, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 100]]);
        $seq = filter_var($_GET['seq_id'] ?? null, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 50]]);
        if ($job === false || $seq === false) {
            echo json_encode(['error' => 'job_id,seq_id,', 'msg' => 'Invalid JOB / SEQ'], JSON_UNESCAPED_UNICODE);
            exit();
        }
        try {
            $result = (new JobSwitchService())->switchJob((int)$job, (int)$seq);
            echo json_encode(array_merge($result, [
                'error' => '', 'command_sent' => true, 'verified' => true,
                'switch_path' => 'JobSwitchService',
            ]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            $this->logMessage('remote change job fail: ' . $e->getMessage());
            echo json_encode(['error' => 'protocol fail', 'msg' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit();
    }
}
