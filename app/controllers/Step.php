<?php

class Step extends Controller
{
   
    // 在建構子中將 Post 物件（Model）實例化
    private $MiscellaneousModel;
    private $stepModel;
    private $sequenceModel;
    private $SettingModel;
    private $ToolModel;
    private $AuditModel;
    Private $deviceId;

    public function __construct(){
    
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->stepModel = $this->model('Steptcc');
        $this->sequenceModel = $this->model('Sequence');
        $this->SettingModel = $this->model('Setting');
        $this->ToolModel = $this->model('Tool');
        $this->AuditModel = $this->model('OperationAudit');

        #該死的需求 去撈控制器的資料庫 同步找出modbus id 
        $this->deviceId = $this->ntcs_device_db_sysnc();

    }




    /**
     * N7 相容規則：扭力補償值有輸入且數值 > 0 時啟用，否則關閉。
     * StepTorqueOffsetSign 只代表正負號，不影響是否啟用補償。
     */
    private function resolveStepEnableTorqueOffset($offsetValue): int
    {
        $raw = trim((string)$offsetValue);

        if ($raw === '' || !is_numeric($raw)) {
            return 0;
        }

        return ((float)$raw > 0) ? 1 : 0;
    }


    private function stepListSnapshot($jobid, $seqid): array
    {
        return [
            'steps' => $this->stepModel->getStep($jobid, $seqid),
        ];
    }

    private function stepAuditSnapshot($jobid, $seqid, $stepid = null): array
    {
        if ($stepid === null || $stepid === '') {
            return $this->stepListSnapshot($jobid, $seqid);
        }

        return [
            'step' => $this->stepModel->getStepNo($jobid, $seqid, $stepid),
        ];
    }

    private function stepOrderSnapshot($jobid, $seqid): array
    {
        if ($jobid === null || $jobid === '' || $seqid === null || $seqid === '') {
            return [];
        }

        $rows = $this->stepModel->getStep($jobid, $seqid);
        $order = [];

        foreach ($rows as $row) {
            $order[] = [
                'StepSelect' => isset($row['StepSelect']) ? (int)$row['StepSelect'] : null,
                'STEPname'   => $row['STEPname'] ?? '',
                'StepOption' => $row['StepOption'] ?? null,
                'StepTorque' => $row['StepTorque'] ?? null,
                'StepAngle'  => $row['StepAngle'] ?? null,
            ];
        }

        return $order;
    }

    private function getSeqIdFromStepRows(array $rowInfoArray)
    {
        if (isset($_POST['SEQID']) && $_POST['SEQID'] !== '') {
            return (int)$_POST['SEQID'];
        }

        if (isset($_POST['seqid']) && $_POST['seqid'] !== '') {
            return (int)$_POST['seqid'];
        }

        foreach ($rowInfoArray as $row) {
            if (isset($row['SEQID']) && $row['SEQID'] !== '') {
                return (int)$row['SEQID'];
            }
        }

        return null;
    }

    private function detectStepMoveAction(array $before, array $after, string $default = 'EDIT'): string
    {
        $beforeKeys = [];
        foreach ($before as $row) {
            $name = trim((string)($row['STEPname'] ?? ''));
            $key = ($name !== '') ? ('name:' . $name) : ('step:' . (string)($row['StepSelect'] ?? ''));
            $beforeKeys[] = $key;
        }

        foreach ($after as $afterIndex => $row) {
            $name = trim((string)($row['STEPname'] ?? ''));
            $key = ($name !== '') ? ('name:' . $name) : ('step:' . (string)($row['StepSelect'] ?? ''));
            $beforeIndex = array_search($key, $beforeKeys, true);

            if ($beforeIndex !== false && $beforeIndex !== $afterIndex) {
                return ($afterIndex < $beforeIndex) ? 'UP' : 'DOWN';
            }
        }

        return $default;
    }

    /**
     * Step Audit: 取得排序異動的來源/目標 StepSelect。
     * 目的：讓操作紀錄「目標」可顯示成 Step ID: [原本位置, 新位置]。
     */
    private function detectStepMoveInfo(array $before, array $after, string $default = 'EDIT'): array
    {
        $info = [
            'action'         => $default,
            'source_step_id' => null,
            'target_step_id' => null,
        ];

        $beforeMap = [];
        foreach ($before as $beforeIndex => $row) {
            $name = trim((string)($row['STEPname'] ?? ''));
            $key = ($name !== '') ? ('name:' . $name) : ('step:' . (string)($row['StepSelect'] ?? ''));

            $beforeMap[$key] = [
                'index'   => $beforeIndex,
                'step_id' => isset($row['StepSelect']) ? (int)$row['StepSelect'] : null,
            ];
        }

        foreach ($after as $afterIndex => $row) {
            $name = trim((string)($row['STEPname'] ?? ''));
            $key = ($name !== '') ? ('name:' . $name) : ('step:' . (string)($row['StepSelect'] ?? ''));

            if (!isset($beforeMap[$key])) {
                continue;
            }

            $beforeRow = $beforeMap[$key];
            if ((int)$beforeRow['index'] !== (int)$afterIndex) {
                $info['action']         = ($afterIndex < $beforeRow['index']) ? 'UP' : 'DOWN';
                $info['source_step_id'] = $beforeRow['step_id'];
                $info['target_step_id'] = isset($row['StepSelect']) ? (int)$row['StepSelect'] : null;
                return $info;
            }
        }

        return $info;
    }


    private function auditValueFilled($value): bool
    {
        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Audit Target 統一格式。
     * 與 APP 顯示一致：
     * - Job ID: 1; Seq ID: 1; Step ID: 2
     * - Job ID: 1; Seq ID: 1; Step ID: [2,1]  // 排序 UP / DOWN
     */
    private function buildStepAuditTarget(array $payload): string
    {
        $parts = [];

        $jobId = $payload['target_job_id'] ?? ($payload['job_id'] ?? null);
        if ($this->auditValueFilled($jobId)) {
            $parts[] = 'Job ID: ' . $jobId;
        }

        $seqId = $payload['target_seq_id'] ?? ($payload['seq_id'] ?? null);
        if ($this->auditValueFilled($seqId)) {
            $parts[] = 'Seq ID: ' . $seqId;
        }

        $action = strtoupper((string)($payload['action'] ?? ''));
        $sourceStepId = $payload['source_step_id'] ?? null;
        $targetStepId = $payload['target_step_id'] ?? null;
        $stepId = $payload['step_id'] ?? null;

        if (($action === 'UP' || $action === 'DOWN')
            && $this->auditValueFilled($sourceStepId)
            && $this->auditValueFilled($targetStepId)) {
            $parts[] = 'Step ID: [' . $sourceStepId . ',' . $targetStepId . ']';
        } else {
            $displayStepId = $this->auditValueFilled($targetStepId) ? $targetStepId : $stepId;
            if ($this->auditValueFilled($displayStepId)) {
                $parts[] = 'Step ID: ' . $displayStepId;
            }
        }

        return !empty($parts) ? implode('; ', $parts) : '-';
    }

    private function writeStepAudit(array $data): void
    {
        try {
            if (!isset($this->AuditModel)) {
                return;
            }

            $operator = $_COOKIE['username'] ?? '';
            $defaults = [
                'user_id'      => $operator,
                'operator'     => $operator,
                'client_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
                'device_id'    => $this->deviceId ?? null,
                'module'       => 'STEP',
                'status'       => 'SUCCESS',
                'request_json' => $_POST,
            ];

            $payload = array_merge($defaults, $data);

            if ((!isset($payload['target_step_id']) || !$this->auditValueFilled($payload['target_step_id']))
                && isset($payload['step_id']) && $this->auditValueFilled($payload['step_id'])) {
                $payload['target_step_id'] = $payload['step_id'];
            }

            if (!isset($payload['target']) || !$this->auditValueFilled($payload['target'])) {
                $payload['target'] = $this->buildStepAuditTarget($payload);
            }

            $this->AuditModel->write($payload);
        } catch (Throwable $e) {
            // Audit log 失敗不能影響原本 Step 功能
            error_log('[STEP AUDIT FAIL] ' . $e->getMessage());
        }
    }

    public function index($job_id,$seq_id){

        
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



        if( isset($job_id) && !empty($job_id)  && isset($seq_id) && !empty($seq_id)){

        }else{
            $job_id = 1;
            $seq_id = 1;
        }


        $isMobile = $this->isMobileCheck();
        $step = $this->stepModel->getStep($job_id, $seq_id);
        $target_option = $this->MiscellaneousModel->details("target_option");
        $torque_unit   = $this->MiscellaneousModel->details("torque_unit");
        $target_option_change = $this->MiscellaneousModel->details("target_option");
        $direction = $this->MiscellaneousModel->details('reverse_direction');
        $unit_arr  = $this->MiscellaneousModel->details('torque_unit');
        $seqinfo   = $this->sequenceModel->search_seqinfo($job_id,$seq_id);
        $specs     = $this->MiscellaneousModel->getToolSpecifications();

        $total_step = count($step);
       
        // 同步控制器資料庫（ntcs_data.db）至 iDAS
        $this->ntcs_data_db_sysnc();
    

        //$this->ntcs_device_db_load();

        
        if(empty($step)){
            $stepid_new = 1;
        }else{
            $stepid_new = count($step) + 1 ;
        }

        $torque_unit   = $this->MiscellaneousModel->details("torque_unit");
        $res_device = $this->SettingModel->GetControllerInfo();
        if(!empty($res_device)){
            $unit = $res_device['torque_unit'];
            $unit_name = $torque_unit[$unit];
        }
    
        $data = array(
            'isMobile' => $isMobile,
            'step' => $step,
            'target_option' => $target_option_change,
            'target_option_change' =>$target_option_change,
            'direction' => $direction,
            'job_id' => $job_id,
            'seq_id' => $seq_id,
            'stepid_new' => $stepid_new,
            'unit_arr' => $unit_arr,
            'unit' => $unit,
            'seq_id' => $seq_id,
            'unit_name' => $unit_name,
            'specs' => $specs,
            'total_step' => $total_step

        );


        if($isMobile){
            $this->view('step/index_m', $data);
        }else{
            $this->view('step/index', $data);
        }
        
        
    }
    

    /**
     * Step threshold / downshift final validation.
     * Rules:
     * - Target torque + torque monitor: threshold/downshift torque < target torque
     * - Target torque + angle monitor: threshold/downshift angle < angle upper limit
     * - Target angle + angle monitor: threshold/downshift angle < target angle
     * - Target angle + torque monitor: threshold/downshift torque < torque upper limit
     * Equality is not allowed.
     */
    private function validateThresholdDownshiftLimitsFromPost(array $post): array
    {
        $stepOption = (int)($post['StepOption'] ?? 0);              // 2=target torque, 1=target angle
        $thMode     = (int)($post['StepEnableThreshold'] ?? 0);     // 0=off, 1=angle, 2=torque
        $dsMode     = (int)($post['StepEnableDownShift'] ?? 0);     // 0=off, 1=angle, 2=torque

        $stepUnit = (int)($post['step_unit'] ?? 1);
        $decimals = $this->MiscellaneousModel->details('decimals');
        $places = isset($decimals[$stepUnit]) ? (int)$decimals[$stepUnit] : 3;

        $asNumber = static function ($value) {
            if ($value === null || $value === '') {
                return null;
            }
            return is_numeric($value) ? (float)$value : null;
        };

        $values = [
            'StepTorque'          => $asNumber($post['StepTorque'] ?? null),
            'StepAngle'           => $asNumber($post['StepAngle'] ?? null),
            'StepHiTorque'        => $asNumber($post['StepHiTorque'] ?? null),
            'StepHiAngle'         => $asNumber($post['StepHiAngle'] ?? null),
            'StepTorqueTS'        => $asNumber($post['StepTorqueTS'] ?? null),
            'StepTorqueDownShift' => $asNumber($post['StepTorqueDownShift'] ?? null),
        ];

        $langRaw = strtolower(trim((string)($_COOKIE['language'] ?? $_COOKIE['lang'] ?? 'en-us')));
        $langRaw = str_replace('_', '-', $langRaw);
        if ($langRaw === 'en') {
            $langRaw = 'en-us';
        }
        $lang = in_array($langRaw, ['zh-tw', 'zh-cn', 'en-us'], true) ? $langRaw : 'en-us';

        $labels = [
            'en-us' => [
                'title' => 'Validation Error',
                'threshold_torque' => 'Threshold torque',
                'threshold_angle' => 'Threshold angle',
                'downshift_torque' => 'Downshift torque',
                'downshift_angle' => 'Downshift angle',
                'target_torque' => 'target torque',
                'target_angle' => 'target angle',
                'torque_upper' => 'torque upper limit',
                'angle_upper' => 'angle upper limit',
                'must_less' => '%s must be less than %s.',
            ],
            'zh-tw' => [
                'title' => '檢核錯誤',
                'threshold_torque' => '門檻點扭力',
                'threshold_angle' => '門檻點角度',
                'downshift_torque' => '降速點扭力',
                'downshift_angle' => '降速點角度',
                'target_torque' => '目標扭力',
                'target_angle' => '目標角度',
                'torque_upper' => '扭力上限',
                'angle_upper' => '角度上限',
                'must_less' => '%s 需小於 %s',
            ],
            'zh-cn' => [
                'title' => '检核错误',
                'threshold_torque' => '门槛点扭力',
                'threshold_angle' => '门槛点角度',
                'downshift_torque' => '降速点扭力',
                'downshift_angle' => '降速点角度',
                'target_torque' => '目标扭力',
                'target_angle' => '目标角度',
                'torque_upper' => '扭力上限',
                'angle_upper' => '角度上限',
                'must_less' => '%s 需小于 %s',
            ],
        ];
        $t = $labels[$lang] ?? $labels['en-us'];

        $makeError = static function ($fieldLabel, $targetLabel) use ($t) {
            return [
                'res_type' => 'Error',
                'res_msg'  => sprintf($t['must_less'], $fieldLabel, $targetLabel),
            ];
        };

        $check = function (string $fieldKey, int $mode, string $targetKey, bool $isThreshold) use ($values, $places, $t, $makeError) {
            if ($mode === 0) {
                return null;
            }

            $value = $values[$fieldKey] ?? null;
            $target = $values[$targetKey] ?? null;
            if ($value === null || $target === null) {
                return null;
            }

            $isTorque = ($mode === 2);
            if ($isTorque) {
                $valueCompare = round((float)$value, $places);
                $targetCompare = round((float)$target, $places);
                $fieldLabel = $isThreshold ? $t['threshold_torque'] : $t['downshift_torque'];
            } else {
                $valueCompare = (int)floor((float)$value);
                $targetCompare = (int)floor((float)$target);
                $fieldLabel = $isThreshold ? $t['threshold_angle'] : $t['downshift_angle'];
            }

            $targetLabel = $t['target_torque'];
            if ($targetKey === 'StepAngle') {
                $targetLabel = $t['target_angle'];
            } elseif ($targetKey === 'StepHiTorque') {
                $targetLabel = $t['torque_upper'];
            } elseif ($targetKey === 'StepHiAngle') {
                $targetLabel = $t['angle_upper'];
            }

            if (!($valueCompare < $targetCompare)) {
                return $makeError($fieldLabel, $targetLabel);
            }

            return null;
        };

        if ($stepOption === 2) {
            // Target torque: torque threshold/downshift < target torque; angle threshold/downshift < angle upper limit.
            if ($err = $check('StepTorqueTS', $thMode, $thMode === 2 ? 'StepTorque' : 'StepHiAngle', true)) return $err;
            if ($err = $check('StepTorqueDownShift', $dsMode, $dsMode === 2 ? 'StepTorque' : 'StepHiAngle', false)) return $err;
        } elseif ($stepOption === 1) {
            // Target angle: angle threshold/downshift < target angle; torque threshold/downshift < torque upper limit.
            if ($err = $check('StepTorqueTS', $thMode, $thMode === 1 ? 'StepAngle' : 'StepHiTorque', true)) return $err;
            if ($err = $check('StepTorqueDownShift', $dsMode, $dsMode === 1 ? 'StepAngle' : 'StepHiTorque', false)) return $err;
        }

        return [];
    }

    public function create_step() {
        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $decimals_arr = $this->MiscellaneousModel->details("decimals");

        if (isset($_POST['JOBID'])) {
            $JOBID = intval($_POST['JOBID'] ?? 0);
            $SEQID = intval($_POST['SEQID'] ?? 0);
            $StepEnableThreshold = intval($_POST['StepEnableThreshold'] ?? 0);
            $StepEnableDownShift = intval($_POST['StepEnableDownShift'] ?? 0);

            // 後端二次檢核：門檻 / 降轉必須嚴格小於對應目標或上限，避免前端被繞過。
            $validationError = $this->validateThresholdDownshiftLimitsFromPost($_POST);
            if (!empty($validationError)) {
                echo json_encode($validationError, JSON_UNESCAPED_UNICODE);
                return;
            }

            // New Step 可能會重設其他 Step 的 threshold/downshift，所以先抓整個 seq 快照。
            $before = $this->stepListSnapshot($JOBID, $SEQID);
            $orderBefore = $this->stepOrderSnapshot($JOBID, $SEQID);

            // ✅ 若這個 step 啟用了 threshold（≠ 0），先重設前面所有 step 的 threshold 為 0
            if ($StepEnableThreshold !== 0) {
                $this->stepModel->resetAllThresholds($JOBID, $SEQID);
            }

            // ✅ 若這個 step 啟用了 DownShift，先重設所有 step 的 DownShift 設定為 0
            if ($StepEnableDownShift !== 0) {
                $this->stepModel->resetAllDownShifts($JOBID, $SEQID);
            }

            // 🔽 繼續原本流程...
            $step_data = [
                'JOBID' => $JOBID,
                'SEQID' => $SEQID,
                'StepSelect' => intval($_POST['StepSelect'] ?? 0),
                'STEPname' => $_POST['STEPname'] ?? '',
                'type' => 0,
                'time' => $_POST['time'] ?? '',
                'act' => 0,
                'StepSwitch' => 1,
                'StepRPM' => intval($_POST['StepRPM'] ?? 0),
                'StepOption' => intval($_POST['StepOption'] ?? -1),
                'StepTime' => intval($_POST['StepTime'] ?? 1000),
                'StepAngle' => intval($_POST['StepAngle'] ?? 0),
                'StepTorque' => floatval($_POST['StepTorque'] ?? 0),
                'StepDirection' => intval($_POST['StepDirection'] ?? 0),
                'StepDelay' => ($_POST['StepDelay'] ?? 0) > 0 ? intval($_POST['StepDelay'] * 1000) : 0,
                'StepMoniByWin' => intval($_POST['StepMoniByWin'] ?? -1),
                'StepLimiHi' => intval($_POST['StepLimiHi'] ?? 0),
                'StepLimiLo' => intval($_POST['StepLimiLo'] ?? 0),
                'StepHiAngle' => intval($_POST['StepHiAngle'] ?? 0),
                'StepLoAngle' => intval($_POST['StepLoAngle'] ?? 0),
                'StepHiTorque' => floatval($_POST['StepHiTorque'] ?? 0),
                'StepLoTorque' => floatval($_POST['StepLoTorque'] ?? 0),
                'StepAccelerateOffset' => intval($_POST['StepAccelerateOffset'] ?? 0.2),
                'StepAccelerateOffsetSign' => intval($_POST['StepAccelerateOffsetSign'] ?? 43),
                'StepEnableTorqueOffset' => $this->resolveStepEnableTorqueOffset($_POST['StepTorqueOffset'] ?? ''),
                'StepTorqueOffset' =>  round(floatval($_POST['StepTorqueOffset'] ?? 0),3),
                'StepTorqueOffsetSign' => intval($_POST['StepTorqueOffsetSign'] ?? 0),
                'StepEnableDownShift' => intval($_POST['StepEnableDownShift'] ?? 0),
                'StepTorqueDownShift' => number_format(round(floatval($_POST['StepTorqueDownShift'] ?? 0), 3), 3, '.', ''),
                'StepRPMDownShift' => intval($_POST['StepRPMDownShift'] ?? 0),
                'StepTorqueTS' => number_format(round(floatval($_POST['StepTorqueTS'] ?? 0), 3), 3, '.', ''),
                'StepEnableThreshold' => $StepEnableThreshold,
                'StepReTry' => 1,
                'StepUnScrew' => 1,
                'StepReTryTorq' => 0,
                'StepReTryAngl' => 0,
                'StepAngleRecord' => 0,
                'StepAutoDetectAngle' => 0,
                'InterruptAlarm' => intval($_POST['InterruptAlarm'] ?? 1),
                'OverAngleStop' => intval($_POST['OverAngleStop'] ?? 1),
                'KValue' => round(floatval($_POST['KValue'] ?? 0), 1),
                'step_unit' => intval($_POST['step_unit'] ?? 0)
            ];

            if(!empty($step_data)){
                $decimals_arr = $this->MiscellaneousModel->details("decimals");
                $places = $decimals_arr[$_POST['step_unit'] ?? 2] ?? 2;

                $step_data['StepTorque']   = number_format(round((float)($_POST['StepTorque'] ?? 0),   $places), $places, '.', '');
                $step_data['StepHiTorque'] = number_format(round((float)($_POST['StepHiTorque'] ?? 0), $places), $places, '.', '');
                $step_data['StepLoTorque'] = number_format(round((float)($_POST['StepLoTorque'] ?? 0), $places), $places, '.', '');

                if($StepEnableThreshold == 2){
                    $step_data['StepTorqueTS']   = number_format(round((float)($_POST['StepTorqueTS'] ?? 0),   $places), $places, '.', '');
                }

                if($step_data['StepEnableDownShift'] == 2){
                    $step_data['StepTorqueDownShift']   = number_format(round((float)($_POST['StepTorqueDownShift'] ?? 0),   $places), $places, '.', '');
                }
            }

            $res = $this->stepModel->create_step($step_data);
            $after = $res ? $this->stepListSnapshot($JOBID, $SEQID) : null;
            $orderAfter = $res ? $this->stepOrderSnapshot($JOBID, $SEQID) : null;

            $this->writeStepAudit([
                'action'            => 'NEW',
                'status'            => $res ? 'SUCCESS' : 'FAIL',
                'job_id'            => (int)$JOBID,
                'seq_id'            => (int)$SEQID,
                'step_id'           => (int)$step_data['StepSelect'],
                'title'             => $res ? 'New Step' : 'New Step Fail',
                'message'           => ($res ? 'Create step id: ' : 'Create step failed id: ') . $step_data['StepSelect'],
                'before_json'       => $before,
                'after_json'        => $after,
                'order_before_json' => $orderBefore,
                'order_after_json'  => $orderAfter,
            ]);

            $text = $text ?? [];
            $res_type = $res ? 'Success' : 'Error';
            $res_msg = $text['new_step'] . ':' . $step_data['StepSelect'] . ($res ? ' ' . $text['success'] : ' ' . $text['fail']);

            $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
        }
    }




    public function edit_step(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        $decimals_arr = $this->MiscellaneousModel->details("decimals");

        if(isset($_POST['JOBID'])){

            $JOBID = isset($_POST['JOBID']) ? intval($_POST['JOBID']) : 0;
            $SEQID = isset($_POST['SEQID']) ? intval($_POST['SEQID']) : 0;
            $StepSelect = isset($_POST['StepSelect']) ? intval($_POST['StepSelect']) : 0;
            $StepEnableThreshold = isset($_POST['StepEnableThreshold']) ? intval($_POST['StepEnableThreshold']) : 0;

            // 後端二次檢核：門檻 / 降轉必須嚴格小於對應目標或上限，避免前端被繞過。
            $validationError = $this->validateThresholdDownshiftLimitsFromPost($_POST);
            if (!empty($validationError)) {
                echo json_encode($validationError, JSON_UNESCAPED_UNICODE);
                return;
            }

            // Edit Step 可能會清除其他 Step 的 threshold，所以先抓整個 seq 快照。
            $before = $this->stepListSnapshot($JOBID, $SEQID);

            // 當前 step 有設定 Threshold
            if ($StepEnableThreshold > 0) {
                // 查詢之前有啟用 threshold 的 step（排除自己）
                $prev_steps = $this->stepModel->getPreviousStepsWithThreshold($JOBID, $SEQID, $StepSelect);

                if (!empty($prev_steps)) {
                    // 將之前的步驟全部清除 threshold 設定
                    $this->stepModel->resetPreviousStepsThreshold($JOBID, $SEQID, $prev_steps[0]['StepSelect']);
                }
            }

            $step_data = [
                'JOBID' => $JOBID,
                'SEQID' => $SEQID,
                'StepSelect' => intval($_POST['StepSelect'] ?? 0),
                'STEPname' => $_POST['STEPname'] ?? '',
                'type' => 0,
                'time' => $_POST['time'] ?? '',
                'act' => 0,
                'StepSwitch' => 1,
                'StepRPM' => intval($_POST['StepRPM'] ?? 0),
                'StepOption' => intval($_POST['StepOption'] ?? -1),
                'StepTime' => intval($_POST['StepTime'] ?? 1000),
                'StepAngle' => intval($_POST['StepAngle'] ?? 0),
                'StepTorque' => floatval($_POST['StepTorque'] ?? 0),
                'StepDirection' => intval($_POST['StepDirection'] ?? 0),
                'StepDelay' => (int) round(((float)($_POST['StepDelay'] ?? 0)) * 1000, 0, PHP_ROUND_HALF_UP),
                'StepMoniByWin' => intval($_POST['StepMoniByWin'] ?? 0),
                'StepLimiHi' => intval($_POST['StepLimiHi'] ?? 0),
                'StepLimiLo' => intval($_POST['StepLimiLo'] ?? 0),
                'StepHiAngle' => intval($_POST['StepHiAngle'] ?? 0),
                'StepLoAngle' => intval($_POST['StepLoAngle'] ?? 0),
                'StepHiTorque' => floatval($_POST['StepHiTorque'] ?? 0),
                'StepLoTorque' => floatval($_POST['StepLoTorque'] ?? 0),
                'StepAccelerateOffset' => intval($_POST['StepAccelerateOffset'] ?? 43),
                'StepAccelerateOffsetSign' => intval($_POST['StepAccelerateOffsetSign'] ?? 0),
                'StepEnableTorqueOffset' => $this->resolveStepEnableTorqueOffset($_POST['StepTorqueOffset'] ?? ''),
                'StepTorqueOffset' =>  round(floatval($_POST['StepTorqueOffset'] ?? 0),3),
                'StepTorqueOffsetSign' => intval($_POST['StepTorqueOffsetSign'] ?? 0),
                'StepEnableDownShift' => intval($_POST['StepEnableDownShift'] ?? 0),
                'StepTorqueDownShift' => number_format(round(floatval($_POST['StepTorqueDownShift'] ?? 0), 3), 3, '.', ''),
                'StepRPMDownShift' => intval($_POST['StepRPMDownShift'] ?? 0),
                'StepTorqueTS' => number_format(round(floatval($_POST['StepTorqueTS'] ?? 0), 3), 3, '.', ''),
                'StepEnableThreshold' => $StepEnableThreshold,
                'StepReTry' => 0,
                'StepUnScrew' => 1,
                'StepReTryTorq' => 0,
                'StepReTryAngl' => 0,
                'StepAngleRecord' => 0,
                'StepAutoDetectAngle' => 0,
                'InterruptAlarm' => intval($_POST['InterruptAlarm'] ?? 1),
                'OverAngleStop' => intval($_POST['OverAngleStop'] ?? 1),
                'KValue' => round(floatval($_POST['KValue'] ?? 0), 2),
                'step_unit' => intval($_POST['step_unit'] ?? 0)
            ];

            if(!empty($step_data)){
                $decimals_arr = $this->MiscellaneousModel->details("decimals");
                $places = $decimals_arr[$_POST['step_unit'] ?? 2] ?? 2;

                $step_data['StepTorque']   = number_format(round((float)($_POST['StepTorque'] ?? 0),   $places), $places, '.', '');
                $step_data['StepHiTorque'] = number_format(round((float)($_POST['StepHiTorque'] ?? 0), $places), $places, '.', '');
                $step_data['StepLoTorque'] = number_format(round((float)($_POST['StepLoTorque'] ?? 0), $places), $places, '.', '');

                if($StepEnableThreshold == 2){
                    $step_data['StepTorqueTS']   = number_format(round((float)($_POST['StepTorqueTS'] ?? 0),   $places), $places, '.', '');
                }

                if($step_data['StepEnableDownShift'] == 2){
                    $step_data['StepTorqueDownShift']   = number_format(round((float)($_POST['StepTorqueDownShift'] ?? 0),   $places), $places, '.', '');
                }
            }

            $res = $this->stepModel->update_step_by_id($step_data);
            $after = $res ? $this->stepListSnapshot($JOBID, $SEQID) : null;

            $this->writeStepAudit([
                'action'      => 'EDIT',
                'status'      => $res ? 'SUCCESS' : 'FAIL',
                'job_id'      => (int)$JOBID,
                'seq_id'      => (int)$SEQID,
                'step_id'     => (int)$StepSelect,
                'title'       => $res ? 'Edit Step' : 'Edit Step Fail',
                'message'     => ($res ? 'Edit step id: ' : 'Edit step failed id: ') . $StepSelect,
                'before_json' => $before,
                'after_json'  => $after,
            ]);

            $result = array(
                'res_type' => $res ? 'Success' : 'Error',
                'res_msg'  => $text['edit_step'] . ':' . $_POST['StepSelect'] . ($res ? "  " . $text['success'] : "  " . $text['fail'])
            );

            echo json_encode($result);
        }
    }



        public function delete_step(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if(isset($_POST['stepid'])){

            $jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : '';
            $seqid = isset($_POST['seqid']) ? intval($_POST['seqid']) : '';
            $stepid = isset($_POST['stepid']) ? intval($_POST['stepid']) : '';

            if(!empty($stepid)){
                $before = $this->stepListSnapshot($jobid, $seqid);
                $orderBefore = $this->stepOrderSnapshot($jobid, $seqid);

                $res = $this->stepModel->delete_step_id($jobid, $seqid, $stepid);
                $after = $res ? $this->stepListSnapshot($jobid, $seqid) : null;
                $orderAfter = $res ? $this->stepOrderSnapshot($jobid, $seqid) : null;

                $this->writeStepAudit([
                    'action'            => 'DELETE',
                    'status'            => $res ? 'SUCCESS' : 'FAIL',
                    'job_id'            => (int)$jobid,
                    'seq_id'            => (int)$seqid,
                    'step_id'           => (int)$stepid,
                    'title'             => $res ? 'Delete Step' : 'Delete Step Fail',
                    'message'           => ($res ? 'Delete step id: ' : 'Delete step failed id: ') . $stepid,
                    'before_json'       => $before,
                    'after_json'        => $after,
                    'order_before_json' => $orderBefore,
                    'order_after_json'  => $orderAfter,
                ]);

                if($res){
                    $res_type = 'Success';
                    $res_msg = $text['del_step'].':'. $stepid."  ".$text['success'];
                    $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
                }else{
                    $res_type = 'Error';
                    $res_msg = $text['del_step'].':'. $stepid."  ".$text['fail'];
                    $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
                }
            }
        }
    }

        public function copy_step(){

        $file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }

        if(isset($_POST['jobid'])){

            #如果 POST 中沒有，則使用預設值
            $jobid = isset($_POST['jobid']) ? intval($_POST['jobid']) : '';
            $seqid = isset($_POST['seqid']) ? intval($_POST['seqid']) : '';
            $stepid = isset($_POST['stepid']) ? intval($_POST['stepid']) : 0;
            $stepid_new = isset($_POST['stepid_new']) ? intval($_POST['stepid_new']) : '';
            $step_count = $this->stepModel->countstep($jobid, $seqid);
            $step_count = intval($step_count);

            $before = $this->stepAuditSnapshot($jobid, $seqid, $stepid);
            $orderBefore = $this->stepOrderSnapshot($jobid, $seqid);
            $old_res= $this->stepModel->getStepNo($jobid,$seqid,$stepid);

            if(!empty($old_res)){

                $step_name = "STEP-".$stepid_new;
                $step_data = array(
                    'JOBID'                    => $jobid,
                    'SEQID'                    => $seqid,
                    'StepSelect'               => $stepid_new,
                    'STEPname'                 => $step_name,
                    'type'                     => $old_res[0]['type'],
                    'time'                     => $old_res[0]['time'],
                    'act'                      => $old_res[0]['act'],
                    'StepSwitch'               => $old_res[0]['StepSwitch'],
                    'StepRPM'                  => $old_res[0]['StepRPM'],
                    'StepOption'               => $old_res[0]['StepOption'],
                    'StepTime'                 => $old_res[0]['StepTime'],
                    'StepAngle'                => $old_res[0]['StepAngle'],
                    'StepTorque'               => $old_res[0]['StepTorque'],
                    'StepDirection'            => $old_res[0]['StepDirection'],
                    'StepDelay'                => $old_res[0]['StepDelay'],
                    'StepMoniByWin'            => $old_res[0]['StepMoniByWin'],
                    'StepLimiHi'               => $old_res[0]['StepLimiHi'],
                    'StepLimiLo'               => $old_res[0]['StepLimiLo'],
                    'StepHiAngle'              => $old_res[0]['StepHiAngle'],
                    'StepLoAngle'              => $old_res[0]['StepLoAngle'],
                    'StepHiTorque'             => $old_res[0]['StepHiTorque'],
                    'StepLoTorque'             => $old_res[0]['StepLoTorque'],
                    'StepAccelerateOffset'     => $old_res[0]['StepAccelerateOffset'],
                    'StepAccelerateOffsetSign' => $old_res[0]['StepAccelerateOffsetSign'],
                    'StepEnableTorqueOffset'   => $this->resolveStepEnableTorqueOffset($old_res[0]['StepTorqueOffset'] ?? ''),
                    'StepTorqueOffset'         => $old_res[0]['StepTorqueOffset'],
                    'StepTorqueOffsetSign'     => $old_res[0]['StepTorqueOffsetSign'],
                    'StepEnableDownShift'      => $old_res[0]['StepEnableDownShift'],
                    'StepTorqueDownShift'      => $old_res[0]['StepTorqueDownShift'],
                    'StepRPMDownShift'         => $old_res[0]['StepRPMDownShift'],
                    'StepEnableThreshold'      => $old_res[0]['StepEnableThreshold'],
                    'StepTorqueTS'             => $old_res[0]['StepTorqueTS'],
                    'StepReTry'                => $old_res[0]['StepReTry'],
                    'StepUnScrew'              => $old_res[0]['StepUnScrew'],
                    'StepReTryTorq'            => $old_res[0]['StepReTryTorq'],
                    'StepReTryAngl'            => $old_res[0]['StepReTryAngl'],
                    'StepAngleRecord'          => $old_res[0]['StepAngleRecord'],
                    'StepAutoDetectAngle'      => $old_res[0]['StepAutoDetectAngle'],
                    'InterruptAlarm'           => $old_res[0]['InterruptAlarm'],
                    'OverAngleStop'            => $old_res[0]['OverAngleStop'],
                    'KValue'                   => $old_res[0]['KValue'],
                    'step_unit'                => $old_res[0]['step_unit'],
                );

                $res = $this->stepModel->create_step($step_data);
                $after = $res ? $this->stepAuditSnapshot($jobid, $seqid, $stepid_new) : null;
                $orderAfter = $res ? $this->stepOrderSnapshot($jobid, $seqid) : null;

                $this->writeStepAudit([
                    'action'            => 'COPY',
                    'status'            => $res ? 'SUCCESS' : 'FAIL',
                    'job_id'            => (int)$jobid,
                    'seq_id'            => (int)$seqid,
                    'step_id'           => (int)$stepid_new,
                    'source_step_id'    => (int)$stepid,
                    'target_step_id'    => (int)$stepid_new,
                    'title'             => $res ? 'Copy Step' : 'Copy Step Fail',
                    'message'           => ($res ? 'Copy step from ' : 'Copy step failed from ') . $stepid . ' to ' . $stepid_new,
                    'before_json'       => $before,
                    'after_json'        => $after,
                    'order_before_json' => $orderBefore,
                    'order_after_json'  => $orderAfter,
                ]);

                if($res){
                    $res_type = $text['success'];
                    $res_msg  = $text['copy_step'].':'.$stepid_new."  ".$text['success'];
                    $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
                }else{
                    $res_type = $text['fail'];
                    $res_msg  = $text['copy_step'].':'.$stepid_new."  ".$text['fail'];
                    $this->MiscellaneousModel->generateErrorResponse($res_type, $res_msg);
                }
            } else {
                $this->writeStepAudit([
                    'action'         => 'COPY',
                    'status'         => 'FAIL',
                    'job_id'         => (int)$jobid,
                    'seq_id'         => (int)$seqid,
                    'step_id'        => (int)$stepid_new,
                    'source_step_id' => (int)$stepid,
                    'target_step_id' => (int)$stepid_new,
                    'title'          => 'Copy Step Fail',
                    'message'        => 'Source step not found: ' . $stepid,
                    'before_json'    => $before,
                    'after_json'     => null,
                ]);
            }
        }
    }

    #查詢step data
    public function search_stepinfo(){

        $input_check = true;
        if(!empty($_POST['jobid']) && isset($_POST['jobid'])){
            $jobid = $_POST['jobid'];
        }else{
            $input_check = false; 
        }

        if(!empty($_POST['seqid']) && isset($_POST['seqid'])){
            $seqid = $_POST['seqid'];
        }else{
            $input_check = false; 
        }


        if(!empty($_POST['stepid']) && isset($_POST['stepid'])){
            $stepid  = $_POST['stepid'];
        }else{
            $input_check = false; 
        }

        if($input_check){

            $check = $this->stepModel->check_step_target($jobid, $seqid);
            $check_count = intval($check[0]['count_records']);

            $res = $this->stepModel->getStepNo($jobid, $seqid, $stepid);
            $res['check_count'] = $check_count;
            print_r($res);
        }

    }
        
    #排序step
        public function adjustment_order(){

        if (isset($_POST['JOBID']) && isset($_POST['rowInfoArray'])) {
            $JOBID = $_POST['JOBID'];
            $rowInfoArray = $_POST['rowInfoArray'];
            $SEQID = $this->getSeqIdFromStepRows(is_array($rowInfoArray) ? $rowInfoArray : []);

            $orderBefore = $SEQID ? $this->stepOrderSnapshot($JOBID, $SEQID) : [];
            $res = false;
            $errorMessage = '';

            try {
                $res = $this->stepModel->swapupdate($JOBID,$rowInfoArray);
            } catch (Throwable $e) {
                $errorMessage = $e->getMessage();
                error_log('[STEP ORDER FAIL] ' . $errorMessage);
            }

            $orderAfter = $SEQID ? $this->stepOrderSnapshot($JOBID, $SEQID) : [];
            $moveInfo = $res
                ? $this->detectStepMoveInfo($orderBefore, $orderAfter, 'EDIT')
                : [
                    'action'         => 'EDIT',
                    'source_step_id' => null,
                    'target_step_id' => null,
                ];
            $moveAction = $moveInfo['action'];

            $this->writeStepAudit([
                'action'            => $moveAction,
                'status'            => $res ? 'SUCCESS' : 'FAIL',
                'job_id'            => (int)$JOBID,
                'seq_id'            => $SEQID !== null ? (int)$SEQID : null,
                // 排序時存 source/target，操作紀錄目標會顯示：Step ID: [2,1]
                'step_id'           => $moveInfo['target_step_id'],
                'source_step_id'    => $moveInfo['source_step_id'],
                'target_step_id'    => $moveInfo['target_step_id'],
                'title'             => $res ? ('Step ' . $moveAction) : 'Step Order Fail',
                'message'           => $res ? 'Step order changed' : ('Step order change failed' . ($errorMessage !== '' ? ': ' . $errorMessage : '')),
                'before_json'       => null,
                'after_json'        => null,
                'order_before_json' => $orderBefore,
                'order_after_json'  => $orderAfter,
            ]);
        } else {

        }

    }

    public function variation($job_id = null, $seq_id = null, $stepid = null){
        
        $job_id = $job_id ?? $_GET['job_id'] ?? null;
        $seq_id = $seq_id ?? $_GET['seq_id'] ?? null;
        $stepid = $stepid ?? $_GET['step_id'] ?? null;

        $step = $this->stepModel->getStep($job_id, $seq_id);
        $next_step_id = empty($step) ? 1 : count($step) + 1;

        $torque_arr = $this->MiscellaneousModel->details("torque_unit");
        $decimals_arr = $this->MiscellaneousModel->details("decimals");

        $res_device = $this->SettingModel->GetControllerInfo();
        $device_torque_unit = (int)$res_device['torque_unit'];

        $paramsCount = 0;
        if (!empty($job_id)) $paramsCount++;
        if (!empty($seq_id)) $paramsCount++;
        if (!empty($stepid)) $paramsCount++;

        $type = ($paramsCount === 2) ? 'new' : 'edit';

        $job_id = htmlspecialchars($job_id);
        $seq_id = htmlspecialchars($seq_id);
        $StepSelect = $type === 'new'
            ? $this->stepModel->getStep_count($job_id, $seq_id)[0]['total'] + 1
            : htmlspecialchars($stepid);

        if ($type === 'edit') {
            $res = $this->stepModel->getStepNo($job_id, $seq_id, $stepid);
            $step = $res[0];

            if($step['StepEnableThreshold'] == 2){

                $res1= $this->MiscellaneousModel->convert_step_torque( $step['StepTorqueTS'],$step['step_unit'],$device_torque_unit); 
                if(!empty($res1)){
                    $step['StepTorqueTS'] = $res1['converted_value'];
                }
            }
            if($step['StepEnableDownShift'] == 2){
                $res = $this->MiscellaneousModel->convert_step_torque($step['StepTorqueDownShift'], $step['step_unit'], $device_torque_unit);
                if(!empty($res)){
                    $step['StepTorqueDownShift'] = $res['converted_value'];
                }
            }

            if ($step['StepDelay'] > 0) {
                $step['StepDelay'] = $step['StepDelay'] / 1000;
            }
                    
        } else {
            $step = [];
        }



        $tools = $this->ToolModel->GetToolInfo()[0] ?? [];
        if (!empty($tools)) {
            foreach (['max_rpm', 'min_rpm'] as $key) {
                if (isset($tools[$key])) {
                    $tools[$key] = rtrim(rtrim($tools[$key], '0'), '.');
                }
            }
        }

        if ($type === 'new') {
            $from_unit = 1; // 預設 DB 單位 N.m
            $tools = $this->MiscellaneousModel->prepareToolTorqueValues($tools,$from_unit,$device_torque_unit,$decimals_arr);
            $torque_unit = $device_torque_unit;
            $flag = 'new';

        } else {

            $step_torque_unit = (int)$step['step_unit'];

            if ($step_torque_unit !== $device_torque_unit) {


                $StepTorque_temp = $this->MiscellaneousModel->convert_all_torque_units( $step['StepTorque'], $device_torque_unit); 
                $StepHiTorque_temp = $this->MiscellaneousModel->convert_all_torque_units( $step['StepHiTorque'], $device_torque_unit); 


                $decimals = $decimals_arr[$device_torque_unit] ?? 3;
                $converted_torque      = $this->MiscellaneousModel->convert_all_torque_units($step['StepTorque'], $step_torque_unit);
                $converted_hi_torque   = $this->MiscellaneousModel->convert_all_torque_units($step['StepHiTorque'], $step_torque_unit);
                $converted_lo_torque   = $this->MiscellaneousModel->convert_all_torque_units($step['StepLoTorque'], $step_torque_unit);
                $unit_key = $torque_arr[$device_torque_unit] ?? 'N.m';

                $step['StepTorque']    = number_format($converted_torque[$unit_key]    ?? 0, $decimals, '.', '');
                $step['StepHiTorque']  = number_format($converted_hi_torque[$unit_key] ?? 0, $decimals, '.', '');
                $step['StepLoTorque']  = number_format($converted_lo_torque[$unit_key] ?? 0, $decimals, '.', '');


                if (!empty($tools)) {
                    $tools = $this->MiscellaneousModel->prepareToolTorqueValues($tools,$step_torque_unit,$device_torque_unit,$decimals_arr);
                }

                $torque_unit = $device_torque_unit;
            } else {
                
                if (!empty($tools)) {
                    $tools = $this->MiscellaneousModel->prepareToolTorqueValues($tools,$step_torque_unit,$device_torque_unit,$decimals_arr);
                }
                $torque_unit = $step_torque_unit;
                $decimals = $decimals_arr[$step_torque_unit] ?? 3;
                
                $converted_torque      = $this->MiscellaneousModel->convert_all_torque_units($step['StepTorque'], $step_torque_unit);
                $converted_hi_torque   = $this->MiscellaneousModel->convert_all_torque_units($step['StepHiTorque'], $step_torque_unit);
                $converted_lo_torque   = $this->MiscellaneousModel->convert_all_torque_units($step['StepLoTorque'], $step_torque_unit);
                $unit_key = $torque_arr[$step_torque_unit] ?? 'N.m';

                $step['StepTorque']    = number_format($converted_torque[$unit_key]    ?? 0, $decimals, '.', '');
                $step['StepHiTorque']  = number_format($converted_hi_torque[$unit_key] ?? 0, $decimals, '.', '');
                $step['StepLoTorque']  = number_format($converted_lo_torque[$unit_key] ?? 0, $decimals, '.', '');


            }

         
            $tools['tool_high_torque'] = $step['StepHiTorque'] ?? 0;
            $tools['tool_low_torque'] = $step['StepLoTorque'] ?? 0;
            $flag = 'edit';
        }

        $unit_name = $torque_arr[$torque_unit] ?? 'N.m';
        
        // 製作tor檢核
        $tools_check = $this->ToolModel->GetToolInfo()[0] ?? [];

        if (!empty($tools_check) && $type === "edit") {

            $use_unit = ($step_torque_unit === $device_torque_unit) ? $step_torque_unit : $device_torque_unit;
            //轉換函式
            $convert_torque = function ($raw_value) use ($use_unit, $torque_arr) {
                $nm_value = floatval($raw_value) / 1000;
                $converted = $this->MiscellaneousModel->convert_all_torque_units($nm_value, 1); // 1 => N.m
                return $converted[$torque_arr[$use_unit]] ?? 0;
            };

        

            // 執行轉換與檢查
            $check_target_tor_lo = $convert_torque($tools_check['min_torque']);
            $check_target_tor_hi = $convert_torque($tools_check['max_torque']);

            $check_hi_tor_after  = $check_target_tor_hi * 1.10;
            $decimals = $decimals_arr[$use_unit] ?? 1;

            $check_hi_tor_after = $this->MiscellaneousModel->roundToNDecimals($check_hi_tor_after, $decimals);
            $check_hi_tor_after = number_format($check_hi_tor_after, $decimals, '.', '');

            if($use_unit == 3){
                $check_hi_tor_after = $check_hi_tor_after + 0.0001;
            }
            
            if($use_unit == 2){
                $check_hi_tor_after = $check_hi_tor_after + 0.01;
            }
            if($use_unit == 0){
                $check_hi_tor_after = $check_hi_tor_after + 0.01;
            }


            $tools_check['check_target_tor_lo']   = $check_target_tor_lo;
            $tools_check['check_target_tor_hi']   = $check_target_tor_hi;
            $tools_check['check_hi_tor_before']   = $check_target_tor_hi;
            $tools_check['check_hi_tor_after']    = $check_hi_tor_after;
            $tools_check['check_lo_tor_before']   = number_format(0, $decimals_arr[$use_unit] ?? 3, '.', '');
            $tools_check['check_lo_tor_after']    = $step['StepTorque'] - (1 / pow(10, $decimals_arr[$use_unit] ?? 3));

            $tools_check['check_lo_rpm'] = (int)$tools_check['min_rpm'];
            $tools_check['check_hi_rpm'] = (int)$tools_check['max_rpm'];

    

            $tools = array_merge($tools,$tools_check);
        }else if(!empty($tools_check) && $type === "new"){

                $use_unit =$device_torque_unit;
                //轉換函式
                $convert_torque = function ($raw_value) use ($use_unit, $torque_arr) {
                    $nm_value = floatval($raw_value) / 1000;
                    $converted = $this->MiscellaneousModel->convert_all_torque_units($nm_value, 1); // 1 => N.m
                    return $converted[$torque_arr[$use_unit]] ?? 0;
                };

                // 執行轉換與檢查
                $check_target_tor_lo = $convert_torque($tools_check['min_torque']);
                $check_target_tor_hi = $convert_torque($tools_check['max_torque']);

                $check_hi_tor_after  = $check_target_tor_hi * 1.10;
                $decimals = $decimals_arr[$use_unit] ?? 1;
                $check_hi_tor_after = $this->MiscellaneousModel->roundToNDecimals($check_hi_tor_after, $decimals);
                $check_hi_tor_after = number_format($check_hi_tor_after, $decimals, '.', '');

                if($use_unit == 3){
                    $check_hi_tor_after = $check_hi_tor_after + 0.0001;
                }
                
                if($use_unit == 2){
                    $check_hi_tor_after = $check_hi_tor_after + 0.01;
                }
                if($use_unit == 0){
                    $check_hi_tor_after = $check_hi_tor_after + 0.01;
                }



                $tools_check['check_target_tor_lo']   = $check_target_tor_lo;
                $tools_check['check_target_tor_hi']   = $check_target_tor_hi;
                $tools_check['check_hi_tor_before']   = $check_target_tor_hi;
                $tools_check['check_hi_tor_after']    = $check_hi_tor_after;
                $tools_check['check_lo_tor_before']   = number_format(0, $decimals_arr[$use_unit] ?? 3, '.', '');
                $tools_check['check_lo_tor_after']    = $check_target_tor_lo - (1 / pow(10, $decimals_arr[$use_unit] ?? 3));

                $tools_check['check_lo_rpm'] = (int)$tools_check['min_rpm'];
                $tools_check['check_hi_rpm'] = (int)$tools_check['max_rpm'];



                 $tools = array_merge($tools,$tools_check);
        }


        $isMobile = $this->isMobileCheck();
        $data = [
            'JOBID' => $job_id,
            'SEQID' => $seq_id,
            'StepSelect' => $StepSelect,
            'type' => $type,
            'tools_info' => $tools,
            'torque_unit' => $unit_name,
            'step' => $step,
            'next_step_id' => $next_step_id,
            'step_torque_unit' => $torque_unit
        ];

        if ($isMobile) {
            $this->view('step/add_step_m', $data);
        } else {
            $this->view('step/add_step', $data);
        }
    }


    
    public function check_step_limit() {
  
        $jobid = $_POST['jobid'] ?? 0;
        $seqid = $_POST['seqid'] ?? 0;


        $steps = $this->stepModel->getStepsWithThresholds($jobid, $seqid);
        $stepCount = count($steps); 

        $hasNonZeroThreshold = false; 
        $updateIds = [];          

        // 遍歷每一筆 step，檢查是否有 StepEnableThreshold 不為 0 的情況
        foreach ($steps as $step) {
            if ((int)$step['StepEnableThreshold'] !== 0) {
                $hasNonZeroThreshold = true;
                $updateIds[] = $step['step_id']; 
            }
        }

        // 若有不為 0 的情況（將 StepEnableThreshold 和 StepTorqueTS 設為 0）
        if (!empty($updateIds)) {
            $this->stepModel->resetThresholdsByStepIds($jobid, $seqid, $updateIds);
        }


        echo json_encode([
            'count' => $stepCount,
            'has_nonzero_threshold' => $hasNonZeroThreshold
        ]);
    }

    public function check_step_is_last() {
        $jobid = $_POST['jobid'] ?? 0;
        $seqid = $_POST['seqid'] ?? 0;
        $stepid = $_POST['stepid'] ?? 0;

        $info = $this->stepModel->check_step_is_last($jobid, $seqid, $stepid);

        echo json_encode([
            'is_last' => $info['is_last'],
            'count' => $info['count']
        ]);
    }


    public function roundConservative(float $value, int $decimals = 2): float {
    $eps = 1 / pow(10, $decimals + 3);
    $rounded = round($value + $eps, $decimals);
    $mult = pow(10, $decimals);

    if ($value > $rounded + $eps) {
        $rounded = ($rounded * $mult + 1) / $mult;
    }
    return $rounded;
}

}