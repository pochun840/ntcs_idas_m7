
<?php
    $moniByWin = isset($data['step']['StepMoniByWin']) ? $data['step']['StepMoniByWin'] : null;
    $stepOption = isset($data['step']['StepOption']) ? $data['step']['StepOption'] : null;
?>
<script>

    document.addEventListener('DOMContentLoaded', () => {
    const isVisible = (el) => !!el && getComputedStyle(el).display !== 'none';

    // StepTorqueTS（門檻點扭力）
    const ts = document.getElementById('StepTorqueTS');
    if (ts && (ts.value === '' || ts.value == null)) {
        const showTorque = document.getElementById('show_torque');
        const showAngle  = document.getElementById('show_angle');
        if (isVisible(showTorque) || isVisible(showAngle)) {
        ts.value = '0';
        }
    }

    // StepTorqueDownShift（降速點扭力）
    const tsd = document.getElementById('StepTorqueDownShift');
    if (tsd && (tsd.value === '' || tsd.value == null)) {
        const showDownTorque = document.getElementById('show_downshift_torque');
        const showDownAngle  = document.getElementById('show_downshift_angle');
        if (isVisible(showDownTorque) || isVisible(showDownAngle)) {
        tsd.value = '0';
        }
    }
    });


    window.addEventListener('DOMContentLoaded', () => {
        if (typeof toggleStepTorqueTS === 'function') toggleStepTorqueTS();
        if (typeof toggleDownShift === 'function') toggleDownShift();
    });

    

    document.addEventListener("DOMContentLoaded", function () {
        const isEditMode = '<?php echo $data["type"]; ?>' === 'edit';

        const cb0 = document.getElementById("StepMoniByWin_0");
        const cb1 = document.getElementById("StepMoniByWin_1");
        const hiTorqueInput = document.getElementById("StepHiTorque");
        const loTorqueInput = document.getElementById("StepLoTorque");
        const hiAngleInput = document.getElementById("StepHiAngle");
        const loAngleInput = document.getElementById("StepLoAngle");

        function toggleInputsByCheckbox() {
            // 只讓 cb0 控制 Torque 欄位
            if (cb0?.checked) {
                hiTorqueInput.disabled = true;
                loTorqueInput.disabled = true;
            } else {
                hiTorqueInput.disabled = false;
                loTorqueInput.disabled = false;
            }

            // 只讓 cb1 控制 Angle 欄位
            if (cb1?.checked) {
                hiAngleInput.disabled = true;
                loAngleInput.disabled = true;
                hiTorqueInput.disabled = false;
                loTorqueInput.disabled = false;

            } else {
                hiAngleInput.disabled = false;
                loAngleInput.disabled = false;
            }
        }

        if (!isEditMode) {
            getCheckboxValue();
            updateLabel();
            cb0.checked = false;
            cb1.checked = false;
        } else {
            getCheckboxValue();
            
            const forceCheck0 = <?php echo ($moniByWin === "1" && $stepOption === "2") ? 'true' : 'false'; ?>;
            const forceCheck1 = <?php echo ($moniByWin === "1" && $stepOption === "1") ? 'true' : 'false'; ?>;

            cb0.checked = forceCheck0;
            cb1.checked = forceCheck1;
        }

        // ⚠️ 要在設定完 checkbox 後再呼叫
        toggleInputsByCheckbox();

        const isDisabled = document.getElementById("StepHiTorque").disabled;
        //console.log("StepHiTorque 是否 disabled：", isDisabled);

        cb0?.addEventListener("change", toggleInputsByCheckbox);
        cb1?.addEventListener("change", toggleInputsByCheckbox);
    });

    function toggleStepTorqueTS() {
        const dataType = "<?php echo $data['type']; ?>";
        const stepTorqueTS = document.getElementById('StepTorqueTS');
        const stepTorqueTSBlock = document.getElementById('StepTorqueTS_block');
        const showTorque = document.getElementById('show_torque');
        const showAngle = document.getElementById('show_angle');
        const thresholdBlock = document.getElementById('threshold_block');

        const isModeOff = document.getElementById('threshold_mode_off')?.checked;
        const isModeTorque = document.getElementById('threshold_mode_torque')?.checked;
        const isModeAngle = document.getElementById('threshold_mode_angle')?.checked;

        //初始全部隱藏 + 停用
        [showTorque, showAngle, stepTorqueTSBlock].forEach(el => {
            if (el) el.style.display = 'none';
        });
        if (stepTorqueTS) stepTorqueTS.disabled = true;

        //取小數設定一次
        const stepUnit = parseInt(document.getElementById('step_torque_unit')?.value ?? 1);
        const decimals = {
            0: 2,
            1: 3,
            2: 2,
            3: 4,
            4: 1
        };
        const places = decimals[stepUnit] ?? 3;

        //Torque 模式
        if (isModeTorque) {
            if (stepTorqueTSBlock) stepTorqueTSBlock.style.display = 'block';
            if (stepTorqueTS) {
                stepTorqueTS.disabled = false;
                if (dataType === 'new') {
                    stepTorqueTS.value = (0).toFixed(places);
                }
            }
            if (showTorque) showTorque.style.display = 'block';
        }

        //Angle 模式
        else if (isModeAngle) {
            if (stepTorqueTSBlock) stepTorqueTSBlock.style.display = 'block';
            if (stepTorqueTS) {
                stepTorqueTS.disabled = false;
                if (dataType === 'new') {
                    stepTorqueTS.value = 0;
                }
            }
            if (showAngle) showAngle.style.display = 'block';
        }

        //整塊顯示控制
        if (thresholdBlock) {
            thresholdBlock.style.display = isModeOff ? 'none' : 'flex';
        }

        //setTimeout(() => bindRoundedWhenVisible('StepTorqueTS', 3), 100);

    }



    function toggleDownShift() {
        const dataType = "<?php echo $data['type']; ?>";

        const StepTorqueDownShift = document.getElementById('StepTorqueDownShift');
        const StepRPMDownShift = document.getElementById('StepRPMDownShift');
        const StepTorqueDownShift_block = document.getElementById('StepTorqueDownShift_block');
        const showDownshiftTorque = document.getElementById('show_downshift_torque');
        const showDownshiftAngle  = document.getElementById('show_downshift_angle');
        const downshiftBlock      = document.getElementById('downshift_block');
        const downshiftSpeedBlock = document.getElementById('downshift_speed_block');

        const isModeOff    = document.getElementById('downshift_mode_off')?.checked;
        const isModeTorque = document.getElementById('downshift_mode_torque')?.checked;
        const isModeAngle  = document.getElementById('downshift_mode_angle')?.checked;

        // 先全部隱藏/disabled
        if (showDownshiftTorque) showDownshiftTorque.style.display = 'none';
        if (showDownshiftAngle)  showDownshiftAngle.style.display  = 'none';
        if (StepTorqueDownShift) {
            StepTorqueDownShift.disabled = true;
            StepTorqueDownShift.style.display = 'none';
        }
        if (StepRPMDownShift) StepRPMDownShift.disabled = true;
        if (StepTorqueDownShift_block) StepTorqueDownShift_block.style.display = 'none';

        // 依扭力單位決定小數位數（供 TORQUE 模式用）
        const decimalsMap = { 0:2, 1:3, 2:2, 3:4, 4:1 };
        const stepUnit = parseInt(document.getElementById('step_torque_unit')?.value ?? 1, 10);
        const places = decimalsMap[stepUnit] ?? 3;
        const stepVal = (1 / Math.pow(10, places)).toFixed(places);

        // 工具：移除先前綁的處理器
        const removeHandler = (el, key) => {
            if (!el) return;
            if (el[key]) {
            el.removeEventListener('input', el[key]);
            el.removeEventListener('blur',  el[key]);
            el[key] = null;
            }
        };

        // 工具：整數-only
        const applyIntegerOnly = (el) => {
            if (!el) return;

            // 先移除可能存在的 decimal handler
            removeHandler(el, '_decimalHandler');

            // 屬性切到整數模式
            el.setAttribute('inputmode', 'numeric');
            el.setAttribute('pattern', '^\\d+$');
            el.setAttribute('step', '1');
            el.setAttribute('data-allow-decimals', '0');

            // 立刻把現值轉成整數
            const n = Math.floor(Number(el.value));
            el.value = Number.isFinite(n) ? String(n) : '0';

            // 綁 input/blur，過濾成純數字並去小數
            const h = (e) => {
            const raw = e.target.value;
            // 只留 0-9
            let cleaned = raw.replace(/\D+/g, '');
            // 去掉前導 0（保留單個 0）
            if (cleaned.length > 1) cleaned = cleaned.replace(/^0+/, '') || '0';
            e.target.value = cleaned;
            };
            el.addEventListener('input', h);
            el.addEventListener('blur',  h);
            el._integerHandler = h;
        };

        // 工具：可小數（TORQUE 模式），限制到 places 位
        const applyDecimalMode = (el, maxPlaces) => {
            if (!el) return;

            // 先移除可能存在的 integer handler
            removeHandler(el, '_integerHandler');

            el.setAttribute('inputmode', 'decimal');
            el.setAttribute('pattern', `^\\d+(?:\\.\\d{0,${maxPlaces}})?$`);
            el.setAttribute('step', (1 / Math.pow(10, maxPlaces)).toFixed(maxPlaces));
            el.setAttribute('data-allow-decimals', String(maxPlaces));

            // 修剪目前值的小數位
            const fix = (v) => {
            v = String(v || '').replace(/[^\d.]/g, '');
            // 僅保留第一個小數點
            const parts = v.split('.');
            if (parts.length > 1) {
                const intPart = parts.shift();
                const frac    = parts.join('').slice(0, maxPlaces);
                return frac.length ? `${intPart}.${frac}` : intPart;
            }
            return parts[0] || '';
            };
            el.value = fix(el.value);

            const h = (e) => { e.target.value = fix(e.target.value); };
            el.addEventListener('input', h);
            el.addEventListener('blur',  h);
            el._decimalHandler = h;
        };

        // === TORQUE 模式 ===
        if (isModeTorque) {
            if (StepTorqueDownShift_block) StepTorqueDownShift_block.style.display = 'flex';
            if (StepTorqueDownShift) {
            StepTorqueDownShift.style.display = 'block';
            StepTorqueDownShift.disabled = false;
            if (dataType === 'new') StepTorqueDownShift.value = (0).toFixed(places);
            applyDecimalMode(StepTorqueDownShift, places);
            }
            if (StepRPMDownShift) StepRPMDownShift.disabled = false;
            if (showDownshiftTorque) showDownshiftTorque.style.display = 'block';
        }
        // === ANGLE 模式（一定是整數）===
        else if (isModeAngle) {
            if (StepTorqueDownShift_block) StepTorqueDownShift_block.style.display = 'flex';
            if (StepTorqueDownShift) {
            StepTorqueDownShift.style.display = 'block';
            StepTorqueDownShift.disabled = false;

            // 新增或原本空值 → 0；否則取整數（floor）
            if (dataType === 'new' || StepTorqueDownShift.value === '' || StepTorqueDownShift.value == null) {
                StepTorqueDownShift.value = '0';
            } else {
                const n = Math.floor(Number(StepTorqueDownShift.value));
                StepTorqueDownShift.value = Number.isFinite(n) ? String(n) : '0';
            }

            // 套用整數-only 規則（屬性 + 輸入過濾）
            applyIntegerOnly(StepTorqueDownShift);
            }
            if (StepRPMDownShift) StepRPMDownShift.disabled = false;
            if (showDownshiftAngle) showDownshiftAngle.style.display = 'block';
        }

        // 外層區塊開關
        if (downshiftBlock)      downshiftBlock.style.display      = isModeOff ? 'none' : 'flex';
        if (downshiftSpeedBlock) downshiftSpeedBlock.style.display = isModeOff ? 'none' : 'flex';
        }




    var dataType ='<?php echo $data['type'];?>'

    if (dataType === 'new') {

        var next_step_id = "<?php echo $data['next_step_id'];?>";
        document.getElementById("STEPname").value = "STEP-" + next_step_id;
        document.getElementById("StepAngle").value = 3000;
        document.getElementById("StepTorque").value  = document.getElementById('check_target_tor_lo').value;
        document.getElementById("interrupt_alarm_off").checked = true;
        document.getElementById("over_angle_stop_off").checked = true;
        document.getElementById("StepDirection_cw").checked = true;
        document.getElementById("join_offset_plus").checked = true;
        document.getElementById("threshold_mode_off").checked = true;
        document.getElementById("downshift_mode_off").checked = true;
        document.getElementById("step_limit_hi_tor").value = 30;
        document.getElementById("step_limit_lo_tor").value = 30;
        document.getElementById("step_limit_hi_ang").value = 30;
        document.getElementById("step_limit_lo_ang").value = 30;
        document.getElementById("StepHiAngle").value = 30600;
        document.getElementById("StepLoAngle").value = 0;
        document.getElementById('StepHiTorque').value = document.getElementById('check_hi_tor_after').value;
        document.getElementById("StepLoTorque").value = document.getElementById('tool_low_torque').value;
        document.getElementById("StepDelay").value = 0;
        document.getElementById("StepRPM").value = 500;
        document.getElementById("k_value").value = 100;
        
        document.getElementById("StepTorqueDownShift").value = 0;
        document.getElementById("StepRPMDownShift").value = 0;
        document.getElementById("StepTorqueOffset").value = 0.0;

        updateLabel();
    } 
    if(dataType === 'edit'){
        updateLabel();
        checkAndDisableDownshiftIfNotLastStep();
    }




    function save_or_edit_step(isEdit = false) {

        let data = new FormData();

        let job_id = document.getElementById("JOBID").value;
        let seq_id = document.getElementById("SEQID").value;
        let StepSelect = document.getElementById("StepSelect").value;
        let STEPname = document.getElementById("STEPname").value;
        let StepOption = document.getElementById("StepOption").value;
        let StepTorque = document.getElementById("StepTorque").value;
        let StepAngle = document.getElementById("StepAngle").value;
        let StepHiTorque = document.getElementById("StepHiTorque").value;
        let StepLoTorque = document.getElementById("StepLoTorque").value;
        let StepHiAngle = document.getElementById("StepHiAngle").value;
        let StepLoAngle = document.getElementById("StepLoAngle").value;

        // 監控視窗設定：任一選項有勾即設為 1
        let StepMoniByWin = 0;
        if (document.getElementById("StepMoniByWin_0")?.checked) StepMoniByWin = 1;
        else if (document.getElementById("StepMoniByWin_1")?.checked) StepMoniByWin = 1;

        // ★ 判斷是否為 step>1（任一依據 >1 就視為 >1）
        const stepNumSelect = Number(String(StepSelect).trim());
        const stepNumSeq    = Number(String(seq_id).trim());
        const isStepGt1 = (Number.isFinite(stepNumSelect) && stepNumSelect > 1) ||
                        (Number.isFinite(stepNumSeq)    && stepNumSeq    > 1);

        let StepTime = 1000;

        let StepLimiHi, StepLimiLo;
        if (StepOption == 2) {
            StepLimiHi = document.getElementById("step_limit_hi_tor")?.value || "0";
            StepLimiLo = document.getElementById("step_limit_lo_tor")?.value || "0";
        } else if (StepOption == 1) {
            StepLimiHi = document.getElementById("step_limit_hi_ang")?.value || "0";
            StepLimiLo = document.getElementById("step_limit_lo_ang")?.value || "0";
        }

        let interrupt_alarm = document.querySelector('input[name="interrupt_alarm"]:checked');
        let over_angle_stop = document.querySelector('input[name="over_angle_stop"]:checked');
        let StepDirection   = document.querySelector('input[name="StepDirection"]:checked');

        let StepDelayInput = document.getElementById("StepDelay");
        let StepDelay = parseFloat(Number(StepDelayInput.value).toFixed(3));
        StepDelayInput.value = StepDelay.toFixed(3); // 固定 3 位小數

        let StepRPM = document.getElementById("StepRPM").value;
        let KValue  = document.getElementById("k_value").value;
        let StepTorqueOffsetSign = document.querySelector('input[name="StepTorqueOffsetSign"]:checked');
        let StepTorqueOffset     = document.getElementById("StepTorqueOffset").value;

        let StepEnableDownShift = document.querySelector('input[name="StepEnableDownShift"]:checked')?.value ?? null;
        let StepEnableThreshold = document.querySelector('input[name="StepEnableThreshold"]:checked')?.value ?? null;
        let StepTorqueTS        = document.getElementById("StepTorqueTS").value;
        let StepTorqueDownShift = document.getElementById("StepTorqueDownShift").value;
        let StepRPMDownShift    = document.getElementById("StepRPMDownShift").value;

        let step_unit = document.getElementById("step_torque_unit").value;
        let time = new Date().toISOString().slice(0, 19).replace('T', ' ');

        const lang = getCookie('language') || 'en';
        const i18n = {
            'en': {
                title: "Warning",
                threshold: "This will remove all existing steps' Threshold settings. Continue?",
                downshift: "This will remove all existing steps' Downshift settings. Continue?",
                both: "This will remove all existing steps' Threshold and Downshift settings. Continue?",
                cancel: "Cancel",
                ok: "OK"
            },
            'zh-tw': {
                title: "警告",
                threshold: "此操作將移除所有既有步驟的 Threshold 設定，是否繼續？",
                downshift: "此操作將移除所有既有步驟的 Downshift 設定，是否繼續？",
                both: "此操作將移除所有既有步驟的 Threshold 與 Downshift 設定，是否繼續？",
                cancel: "取消",
                ok: "確定"
            },
            'zh-cn': {
                title: "警告",
                threshold: "此操作将移除所有既有步骤的 Threshold 设置，是否继续？",
                downshift: "此操作将移除所有既有步骤的 Downshift 设置，是否继续？",
                both: "此操作将移除所有既有步骤的 Threshold 和 Downshift 设置，是否继续？",
                cancel: "取消",
                ok: "确定"
            }
        };
        const text = i18n[lang] || i18n['en'];

        // 這些工具上限值目前未在本函式內直接使用，但保留以利擴充
        const Tool_Max_Torque      = parseFloat(document.getElementById('tool_max_torque').value);
        const Tool_Min_Torque      = parseFloat(document.getElementById('tool_min_torque').value);
        const Tool_Max_RPM         = parseFloat(document.getElementById('tool_max_rpm').value);
        const Tool_Min_RPM         = parseFloat(document.getElementById('tool_min_rpm').value);
        const Tool_Max_Torque_Diff = parseFloat(document.getElementById('tool_max_torque_diff').value);

        // 先做前端驗證
        let check = input_check();
        console.log(check);

        if (check.valid) {
            const needConfirmThreshold = StepEnableThreshold !== "0";
            const needConfirmDownshift = StepEnableDownShift !== "0";

            // ★ 規則：只有「step > 1」且有啟用 Threshold/Downshift 才顯示提示訊息
            if (isStepGt1 && (needConfirmThreshold || needConfirmDownshift)) {
                // 兩者都啟用時，顯示合併訊息，避免連續跳兩次對話框
                const msg = (needConfirmThreshold && needConfirmDownshift)
                    ? text.both
                    : (needConfirmThreshold ? text.threshold : text.downshift);

                alertify.confirm(
                    text.title,
                    msg,
                    function () { submit_step_ajax(); },
                    function () { return; }
                ).set('labels', { ok: text.ok, cancel: text.cancel });

            } else {
                // step == 1 或未啟用任何一項 → 直接送出
                submit_step_ajax();
            }
        }

        function submit_step_ajax() {

            data.append("JOBID", job_id);
            data.append("SEQID", seq_id);
            data.append("StepSelect", StepSelect);
            data.append("STEPname", STEPname);
            data.append("time", time);
            data.append("StepOption", StepOption);
            data.append("StepTorque", StepTorque);
            data.append("StepAngle", StepAngle);
            data.append("StepTime", StepTime);
            data.append("StepMoniByWin", StepMoniByWin);
            data.append("StepLimiHi", StepLimiHi);
            data.append("StepLimiLo", StepLimiLo);
            data.append("InterruptAlarm", interrupt_alarm ? interrupt_alarm.value : null);
            data.append("OverAngleStop", over_angle_stop ? over_angle_stop.value : null);
            data.append("StepDirection", StepDirection ? StepDirection.value : null);
            data.append("StepDelay", StepDelay);
            data.append("StepRPM", StepRPM);
            data.append("StepTorqueOffset", StepTorqueOffset);
            data.append("StepTorqueOffsetSign", StepTorqueOffsetSign ? StepTorqueOffsetSign.value : null);
            data.append("StepEnableThreshold", StepEnableThreshold);
            data.append("StepTorqueTS", StepTorqueTS);
            data.append("StepEnableDownShift", StepEnableDownShift);
            data.append("StepTorqueDownShift", StepTorqueDownShift);
            data.append("StepRPMDownShift", StepRPMDownShift);
            data.append("StepHiTorque", StepHiTorque);
            data.append("StepLoTorque", StepLoTorque);
            data.append("StepHiAngle", StepHiAngle);
            data.append("StepLoAngle", StepLoAngle);
            data.append("KValue", KValue);
            data.append("step_unit", step_unit);

            document.querySelector(".main-content").classList.add("overlay-active");
            document.getElementById("spinner").style.display = 'block';

            $.ajax({
                url: isEdit ? '?url=Step/edit_step' : '?url=Step/create_step',
                type: 'POST',
                data: data,
                processData: false,
                contentType: false,
                success: function (response) {
                    const responseData = JSON.parse(response);
                    success_response_seq(response, 'spinner', `../public/?url=Step/index/${job_id}/${seq_id}`);
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                }
            });
        }
    }




    function getCheckboxValue(clickedId = null) {
        const checkbox0 = document.getElementById("StepMoniByWin_0");
        const checkbox1 = document.getElementById("StepMoniByWin_1");

        let check_val = -1;

        if (clickedId === "StepMoniByWin_0" && checkbox0.checked) {
            checkbox1.checked = false;
            check_val = 0;
        } else if (clickedId === "StepMoniByWin_1" && checkbox1.checked) {
            checkbox0.checked = false;
            check_val = 1;
        } else {
            if (checkbox0.checked && !checkbox1.checked) {
                check_val = 0;
            } else if (!checkbox0.checked && checkbox1.checked) {
                check_val = 1;
            } else if (checkbox0.checked && checkbox1.checked) {
                checkbox1.checked = false;
                check_val = 0;
            } else {
                // ✅ 預設扭力模式
                checkbox0.checked = false;
                check_val = 0;
            }
        }

        const stepHiTorque = document.getElementById("StepHiTorque");
        const stepLoTorque = document.getElementById("StepLoTorque");
        const stepHiAngle  = document.getElementById("StepHiAngle");
        const stepLoAngle  = document.getElementById("StepLoAngle");

        // ✅ Torque 欄位永遠可填
        [stepHiTorque, stepLoTorque].forEach(el => el && (el.disabled = false));

        // ✅ 只有當 check_val === 0（扭力模式）時 disable Angle
        const disableAngle = (check_val === 0);
        [stepHiAngle, stepLoAngle].forEach(el => el && (el.disabled = disableAngle));

        document.getElementById("StepHiTorque").disabled = false;



    }


    function updateLabel() {
        const rawUnit = '<?php echo $data['torque_unit']; ?>';
        const language = getCookie('language') || 'default';
        const selectVal = parseInt(document.getElementById('StepOption').value);
        const label = document.getElementById('targetLabel');

        const unitLabels = {
            'kgf.cm': { 'zh-cn': '公斤.公分', 'zh-tw': '公斤.公分', 'default': 'kgf.cm' },
            'lbf.in': { 'zh-cn': '磅.英吋', 'zh-tw': '磅.英吋', 'default': 'lbf.in' },
            'N.m':    { 'zh-cn': '牛顿.米',  'zh-tw': '牛頓.米',   'default': 'N.m' },
            'kgf.m':  { 'zh-cn': '公斤.米',   'zh-tw': '公斤.米', 'default': 'kgf.m' },
            'cN.m':   { 'zh-cn': '牛頓.厘米','zh-tw': '牛頓.厘米', 'default': 'cN.m' },
        };
        const translatedUnit = unitLabels[rawUnit]?.[language] || rawUnit;

        const labelTexts = {
            'zh-cn': { 2: '目标扭矩', 1: '目标角度' },
            'zh-tw': { 2: '目標扭力', 1: '目標角度' },
            'default': { 2: 'Target Torque', 1: 'Target Angle' }
        };
        const textSet = labelTexts[language] || labelTexts['default'];
        const labelPrefix = textSet[selectVal] || 'Target';

        label.textContent = (selectVal === 2)
            ? `${labelPrefix} (${translatedUnit}):`
            : `${labelPrefix}:`;

        // 顯示主欄位區塊
        ['StepTorque_item', 'StepAngle_item'].forEach(id =>
            document.getElementById(id)?.style.setProperty('display', 'none')
        );
        if (selectVal === 2) {
            document.getElementById('StepTorque_item')?.style.setProperty('display', 'block');
            document.getElementById('over_angle_stop_item')?.style.setProperty('display', 'block');
        } else if (selectVal === 1) {
            document.getElementById('StepAngle_item')?.style.setProperty('display', 'block');
            document.getElementById('over_angle_stop_item')?.style.setProperty('display', 'none');
        }

        // 額外欄位顯示切換
        const showTor = document.getElementById('show_tor');
        const showAng = document.getElementById('show_ang');
        if (showTor && showAng) {
            showTor.style.display = selectVal === 2 ? 'block' : 'none';
            showAng.style.display = selectVal === 1 ? 'block' : 'none';
        }

        // 全部 disable，再依選項啟用
        const allFields = [
            'StepMoniByWin_0', 'StepMoniByWin_1',
            'step_limit_hi_tor', 'step_limit_lo_tor',
            'step_limit_hi_ang', 'step_limit_lo_ang'
        ];
        allFields.forEach(id => document.getElementById(id)?.setAttribute('disabled', true));

        const enableMap = {
            2: ['StepMoniByWin_0', 'step_limit_hi_tor', 'step_limit_lo_tor'],
            1: ['StepMoniByWin_1', 'step_limit_hi_ang', 'step_limit_lo_ang']
        };
        enableMap[selectVal]?.forEach(id => document.getElementById(id)?.removeAttribute('disabled'));

        // ✅ 強制取消勾選兩個監控 checkbox
        const checkbox0 = document.getElementById('StepMoniByWin_0');
        const checkbox1 = document.getElementById('StepMoniByWin_1');
        if (checkbox0) checkbox0.checked = false;
        if (checkbox1) checkbox1.checked = false;
        getCheckboxValue();  // ✅ 確保 UI 狀態與 disabled 同步

        // 上下限欄位全部啟用
        ['StepHiTorque', 'StepLoTorque', 'StepHiAngle', 'StepLoAngle'].forEach(id =>
            document.getElementById(id)?.removeAttribute('disabled')
        );

        document.getElementById("StepHiTorque").disabled = false;
    }



    function checkAndDisableDownshiftIfNotLastStep() {
        const job_id = document.getElementById("JOBID")?.value;
        const seq_id = document.getElementById("SEQID")?.value;
        const step_id = document.getElementById("StepSelect")?.value;

        if (job_id && seq_id && step_id) {
            $.post("?url=Step/check_step_is_last", {
                jobid: job_id,
                seqid: seq_id,
                stepid: step_id
            }, function (response) {
                let res = JSON.parse(response);
                if (res.is_last === "N") {
                    disableDownshiftFields();
                }
            });
        }

    function disableDownshiftFields() {
            const fields = [
                "downshift_mode_off",
                "downshift_mode_torque",
                "downshift_mode_angle",
                "StepTorqueDownShift",
                "StepRPMDownShift"
            ];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = true;
            });
        }
    }


    function input_check() {
        const decimals = { 0: 2, 1: 3, 2: 2, 3: 4, 4: 1 };
        const torque_unit = parseInt(document.getElementById('step_torque_unit')?.value ?? 1, 10);
        const precision = decimals[torque_unit] ?? 3;
        const increment = parseFloat((1 / Math.pow(10, precision)).toFixed(precision));

        const Tool_Max_Torque = document.getElementById('check_target_tor_hi').value;
        const Tool_Min_Torque = document.getElementById('check_target_tor_lo').value;

        const check_hi_tor_before = document.getElementById('check_hi_tor_before').value;
        const check_hi_tor_after  = document.getElementById('check_hi_tor_after').value;
        const check_hi_tor_after_temp  = document.getElementById('check_hi_tor_after').value;
        const check_lo_tor_before = document.getElementById('check_lo_tor_before').value;
        const check_lo_tor_after  = document.getElementById('check_lo_tor_after').value;

        const check_target_tor_lo_raw = parseFloat(Tool_Min_Torque || 0);
        const check_target_tor_hi_raw = parseFloat(Tool_Max_Torque || 0);
        const check_target_torque_raw = document.getElementById('StepTorque').value;

        const Tool_Max_RPM = document.getElementById('check_hi_rpm').value;
        const Tool_Min_RPM = document.getElementById('check_lo_rpm').value;
        const minStepLoTorque = parseFloat((parseFloat(check_target_torque_raw) - increment).toFixed(precision));
        const rpm_check = document.getElementById('StepRPM').value;

        // helper
        const roundTo = (num, digits) => {
            const n = Number(num);
            return isNaN(n) ? NaN : parseFloat(n.toFixed(digits));
        };

        // ★ 依 precision 取得 StepTorque 的「顯示精度」值，作為 StepHiTorque 的下限
        const stepTorqueRounded = roundTo(check_target_torque_raw, precision);

        // ★ 各扭力單位的最小增量（以「你要的結果」為準）
        const unitBumpMap = {
        0: 0.001,   // kgf.cm
        1: 0.001,   // N·m
        2: 0.0001,  // lbf·in
        3: 0.1,     // cN·m
        // 其他單位或未知單位，用原本精度的最小增量 fallback
        4: increment
        };

        // 取出對應增量（沒有就用 increment 當保底）
        const bump = Object.prototype.hasOwnProperty.call(unitBumpMap, torque_unit)
        ? unitBumpMap[torque_unit]
        : increment;

        // ★ 新的下限：StepHiTorque 必須大於（而非等於）StepTorque
        const dynamicHiMin = Number.isFinite(stepTorqueRounded)
        ? stepTorqueRounded + bump
        : check_target_tor_lo_raw;
        

        // 先確認工具設定欄位合法
        const idsToCheck = ['tool_max_torque', 'tool_min_torque', 'tool_max_rpm', 'tool_min_rpm', 'tool_max_torque_diff'];
        let toolError = false;
        idsToCheck.forEach(id => {
            const el = document.getElementById(id);
            if (!el) return;
            const value = parseFloat(el.value);
            const feedback = el.nextElementSibling;
            if (isNaN(value)) {
                el.classList.add("is-invalid");
                if (feedback?.classList.contains("invalid-feedback")) {
                    feedback.innerText = "Invalid number";
                    feedback.classList.add("d-block");
                    feedback.style.display = "block";
                }
                toolError = true;
            } else {
                el.classList.remove("is-invalid");
                if (feedback) {
                    feedback.innerText = '';
                    feedback.classList.remove("d-block");
                    feedback.style.display = "none";
                }
            }
        });
        if (toolError) return { valid: false, errors: ["Tool config invalid"] };

        const StepOption = parseInt(document.getElementById("StepOption").value, 10);
        const StepEnableThreshold = document.querySelector('input[name="StepEnableThreshold"]:checked')?.value ?? "0";
        const StepEnableDownShift = document.querySelector('input[name="StepEnableDownShift"]:checked')?.value ?? "0";

        // 正確取得 StepMoniByWin：0=未勾、1=角度視窗、2=扭力視窗
        const StepMoniByWin = document.getElementById("StepMoniByWin_0")?.checked
            ? 2 // 扭力視窗
            : (document.getElementById("StepMoniByWin_1")?.checked ? 1 : 0); // 角度視窗 / 未勾

        const limits = {
            torque: {
                torque: { min: check_target_tor_lo_raw, max: check_target_tor_hi_raw },
                torqueTS: { min: check_lo_tor_before, max: minStepLoTorque },
                torqueDownshift: { min: check_lo_tor_before, max: minStepLoTorque },
                rpmDownshift_1: { min: Tool_Min_RPM, max: rpm_check },
                // ★ StepHiTorque 的 min 改成動態跟 StepTorque（四捨五入到 precision）
                limitHi: { min: dynamicHiMin, max: check_hi_tor_after },
                limitLo: { min: check_lo_tor_before, max: minStepLoTorque }
            },
            angle: {
                angle: { min: 1, max: 30600 },
                limitHi: { min: 1, max: 30600 },
                limitLo: { min: 0, max: Math.max(0, parseFloat(document.getElementById("StepAngle")?.value || 1)) }
            }
        };

        // 條件清單
        let conditions = [
            { id: 'StepRPM', pattern: /^\d{1,4}$/, min: Tool_Min_RPM, max: Tool_Max_RPM },
            { id: 'StepRPMDownShift', pattern: /^\d{1,4}$/, ...limits.torque.rpmDownshift_1 },
            { id: 'StepTorqueDownShift', pattern: /^\d{1,5}(\.\d{1,9})?$/, ...limits.torque.torqueDownshift },
            { id: 'StepTorqueTS', pattern: /^\d{1,5}(\.\d{1,9})?$/, ...limits.torque.torqueTS, noRangeMessage: true }
        ];

        
        // Threshold 模式微調（保留你原本的設定，僅確保 noRangeMessage 為 true）
        if (StepEnableThreshold === "1") {
        const ts = conditions.find(c => c.id === 'StepTorqueTS');
        if (ts) { ts.min = 0; ts.max = 99999; ts.integerOnly = true; ts.noRangeMessage = true; }
        }
        if (StepEnableThreshold === "2") {
        const ts = conditions.find(c => c.id === 'StepTorqueTS');
        if (ts) {
            ts.min = parseFloat(check_lo_tor_before);
            ts.max = parseFloat((parseFloat(check_target_torque_raw) - increment).toFixed(precision));
            ts.integerOnly = false;
            ts.roundBeforeCompare = true;
            ts.precision = precision;
            ts.noRangeMessage = true; // ← 也關掉 Range
        }
        }


        // 依 threshold/downshift 開關調整
        if (StepEnableThreshold === "0") {
            conditions = conditions.filter(c => c.id !== 'StepTorqueTS');
        }
        if (StepEnableDownShift === "0") {
            conditions = conditions.filter(c => !['StepTorqueDownShift', 'StepRPMDownShift'].includes(c.id));
        }
        if (StepEnableDownShift === "1") {
            const td = conditions.find(c => c.id === 'StepTorqueDownShift');
            if (td) {
                td.min = 0;
                td.max = parseFloat(document.getElementById("StepAngle")?.value || 0);
                td.integerOnly = true;
            }
        }
        if (StepEnableDownShift === "2") {
            const td = conditions.find(c => c.id === 'StepTorqueDownShift');
            if (td) {
                td.min = check_lo_tor_before;
                td.max = minStepLoTorque;
            }
        }
        if (StepEnableThreshold === "1") {
            const ts = conditions.find(c => c.id === 'StepTorqueTS');
            if (ts) { ts.min = 0; ts.max = 99999; ts.integerOnly = true; }
        }
        if (StepEnableThreshold === "2") {
            const ts = conditions.find(c => c.id === 'StepTorqueTS');
            if (ts) {
                ts.min = parseFloat(check_lo_tor_before);
                ts.max = parseFloat((parseFloat(check_target_torque_raw) - increment).toFixed(precision));
                ts.integerOnly = false;
                ts.roundBeforeCompare = true;
                ts.precision = precision;
            }
        }

        // ★ 關掉 StepOption=1 && DownShift=2 時的 DownShift Range 顯示與範圍驗證（只做格式檢查 + 交叉驗證）
        if (StepOption === 1 && StepEnableDownShift === "2") {
            const td = conditions.find(c => c.id === 'StepTorqueDownShift');
            if (td) {
                td.min = null;
                td.max = null;
                td.noRangeMessage = true; // 告訴 validator 不要顯示 Range
            }
        }

        // StepOption 分支
        const pattern0to99 = /^(?:[0-9]|[1-9][0-9])$/; // 0~99
        if (StepOption === 2) {

            const hiAngEl = document.getElementById('StepHiAngle');
            const loAngEl = document.getElementById('StepLoAngle');

            const hiAngVal = Number(hiAngEl?.value);
            const loAngMax = (Number.isFinite(hiAngVal) && hiAngVal > 0) ? (hiAngVal - 1) : 30600;
            
            conditions.push(
                { id: 'StepTorque',   pattern: /^\d{1,5}(\.\d{1,4})?$/, ...limits.torque.torque },
                { id: 'StepHiTorque', pattern: /^\d{1,6}(\.\d{1,4})?$/, ...limits.torque.limitHi },
                { id: 'StepHiAngle',  pattern: /^\d{1,5}$/, ...limits.angle.limitHi },
                { id: 'StepLoAngle',  pattern: /^\d{1,5}$/, min: 0, max: loAngMax, integerOnly: true, noRangeMessage: true }
            );

            // 同步：HiAngle 改變時即時更新 LoAngle 的 max（只綁一次）
            if (hiAngEl && loAngEl && !hiAngEl.dataset.syncLoMaxBound) {
                const syncLoMax = () => {
                    const h  = Number(hiAngEl.value);
                    const mx = (Number.isFinite(h) && h > 0) ? (h - 1) : 30600;
                    loAngEl.setAttribute('max', mx);

                    // 如目前 lo 超過新上限，夾回上限（可選）
                    const v = Number(loAngEl.value);
                    if (Number.isFinite(v) && v > mx) loAngEl.value = String(mx);
                };
                syncLoMax();
                hiAngEl.addEventListener('input', syncLoMax);
                hiAngEl.dataset.syncLoMaxBound = '1'; // 打標記避免重複綁定
            }

            if (StepMoniByWin === 2) {
                conditions.push(
                    { id: 'step_limit_hi_tor', pattern: pattern0to99, min: 0, max: 99, integerOnly: true },
                    { id: 'step_limit_lo_tor', pattern: pattern0to99, min: 0, max: 99, integerOnly: true }
                );
            }
        } else if (StepOption === 1) {
            const stepAngleVal = parseFloat(document.getElementById('StepAngle')?.value || 0);
            conditions.push(
                { id: 'StepAngle',   pattern: /^\d{1,5}$/, ...limits.angle.angle },
                { id: 'StepHiAngle', pattern: /^\d{1,5}$/, ...limits.angle.limitHi },
                { id: 'StepLoAngle', pattern: /^\d{1,5}$/, min: limits.angle.limitLo.min, max: stepAngleVal > 0 ? stepAngleVal - 1 : limits.angle.limitLo.max, noRangeMessage: true },
                { id: 'StepHiTorque', pattern: /^\d{1,5}(\.\d{1,4})?$/, min: check_target_tor_lo_raw, max: check_hi_tor_after_temp, precision, roundBeforeCompare: true }
            );
            if (StepMoniByWin === 1) {
                conditions.push(
                    { id: 'step_limit_hi_ang', pattern: pattern0to99, min: 0, max: 99, integerOnly: true,allowBlank: true },
                    { id: 'step_limit_lo_ang', pattern: pattern0to99, min: 0, max: 99, integerOnly: true,allowBlank: true }
                );
            }
            const stepAngleEl = document.getElementById('StepAngle');
            const stepLoAngleEl = document.getElementById('StepLoAngle');
            if (stepAngleEl && stepLoAngleEl) {
                stepAngleEl.addEventListener('input', () => {
                    const val = parseFloat(stepAngleEl.value);
                    stepLoAngleEl.setAttribute('max', val > 0 ? val - 1 : limits.angle.limitLo.max);
                });
            }
        }

        // StepDelay
        conditions.push({
            id: 'StepDelay',
            pattern: /^(?:[0-9](?:\.\d{1,4})?|9\.9)$/,
            min: 0,
            max: 9.9,
            precision: 3,
            roundBeforeCompare: true
        });

      

        function validateInput(el, pattern, min, max, cond = {}) {
            if (!el) return false;
            const val = el.value.trim();

            // ⬇️ 可空欄位：空字串直接通過，且清掉錯誤樣式
            if (cond.allowBlank === true && val === "") {
                el.classList.remove("is-invalid");
                const fb = el.nextElementSibling;
                if (fb?.classList.contains("invalid-feedback")) {
                    fb.innerText = '';
                    fb.classList.remove("d-block");
                    fb.style.display = "none";
                }
                return true;
            }

            const parsed = Number(val);
            const feedback = el.nextElementSibling;
            const integerOnly = cond.integerOnly === true ||
                ['StepRPM','StepRPMDownShift','StepAngle','StepHiAngle','StepLoAngle',
                'step_limit_hi_tor','step_limit_lo_tor','step_limit_hi_ang','step_limit_lo_ang'].includes(el.id);

            const prec = cond.precision ?? 3;
            const shouldRound = cond.roundBeforeCompare === true;

            const roundTo = (num, digits) => {
                const n = Number(num);
                return isNaN(n) ? NaN : parseFloat(n.toFixed(digits));
            };

            const parsedRounded = integerOnly ? Math.floor(parsed) : (shouldRound ? roundTo(parsed, prec) : parsed);
            const minRounded = (min != null) ? (integerOnly ? Math.floor(min) : roundTo(min, prec)) : null;
            const maxRounded = (max != null) ? (integerOnly ? Math.floor(max) : roundTo(max, prec)) : null;

            let invalid = (val === "" || isNaN(parsed) ||
                (minRounded !== null && parsedRounded < minRounded) ||
                (maxRounded !== null && parsedRounded > maxRounded) ||
                !pattern.test(val));

            if (['step_limit_hi_tor','step_limit_lo_tor','step_limit_hi_ang','step_limit_lo_ang'].includes(el.id) && !Number.isInteger(parsed)) {
                invalid = true;
            }

            if (invalid) {
                el.classList.add("is-invalid");
                if (feedback?.classList.contains("invalid-feedback")) {
                    if (cond.noRangeMessage === true) {
                        feedback.innerText = "Invalid input";
                    } else {
                        const displayMin = (minRounded === null) ? "" : (integerOnly ? parseInt(minRounded, 10) : minRounded.toFixed(prec));
                        const displayMax = (maxRounded === null) ? "" : (integerOnly ? parseInt(maxRounded, 10) : maxRounded.toFixed(prec));
                        feedback.innerText = (minRounded !== null && maxRounded !== null)
                            ? `Range: ${displayMin} ~ ${displayMax}`
                            : `Invalid input`;
                    }
                    feedback.classList.add("d-block");
                    feedback.style.display = "block";
                }
            } else {
                el.classList.remove("is-invalid");
                if (feedback?.classList.contains("invalid-feedback")) {
                    feedback.innerText = '';
                    feedback.classList.remove("d-block");
                    feedback.style.display = "none";
                }
            }
            return !invalid;
        }


        let isValid = true;
        let errorList = [];

        // 逐條驗證
        conditions.forEach(cond => {
            const el = document.getElementById(cond.id);
            const valid = validateInput(el, cond.pattern, cond.min, cond.max, cond);
            if (!valid) { isValid = false; errorList.push(cond.id); }
        });

        // StepTorqueOffset（僅 StepOption=2）
        const offsetEl = document.getElementById('StepTorqueOffset');
        const torqueVal = parseFloat(check_target_torque_raw ?? 0);
        if (!(torqueVal < check_target_tor_lo_raw || torqueVal > check_target_tor_hi_raw)) {
            if (offsetEl && StepOption === 2) {
                const offset = parseFloat(offsetEl.value);
                const target = parseFloat(check_target_torque_raw);
                const specMin = parseFloat(Tool_Min_Torque);
                const specMax = parseFloat(Tool_Max_Torque);
                const feedback = offsetEl.nextElementSibling;
                if (!isNaN(offset) && !isNaN(target) && !isNaN(specMin) && !isNaN(specMax)) {
                    const offsetMaxBy30 = target * 0.3;
                    const offsetMaxBySpec = (specMax * 1.08) - target;
                    const offsetMinBySpec = (specMin * 0.7) - target;
                    const offsetMax = Math.min(offsetMaxBy30, offsetMaxBySpec);
                    const offsetMin = Math.max(-offsetMaxBy30, offsetMinBySpec);
                    if (offset < offsetMin || offset > offsetMax) {
                        offsetEl.classList.add("is-invalid");
                        if (feedback?.classList.contains("invalid-feedback")) {
                            feedback.innerText = `Offset Range: ${(target - offsetMaxBy30).toFixed(precision)} ~ ${(target + offsetMaxBy30).toFixed(precision)}`;
                            feedback.classList.add("d-block"); feedback.style.display = "block";
                        }
                        isValid = false; errorList.push('StepTorqueOffset');
                    } else {
                        offsetEl.classList.remove("is-invalid");
                        if (feedback?.classList.contains("invalid-feedback")) { feedback.innerText = ''; feedback.classList.remove("d-block"); feedback.style.display = "none"; }
                    }
                }
            }
        }

        // StepLoTorque < StepHiTorque
        const loTorqueEl = document.getElementById('StepLoTorque');
        const hiTorqueEl = document.getElementById('StepHiTorque');
        if (loTorqueEl && hiTorqueEl) {
            const lo = parseFloat(loTorqueEl.value);
            const hi = parseFloat(hiTorqueEl.value);
            const feedback = loTorqueEl.nextElementSibling;
            if (!isNaN(lo) && !isNaN(hi) && lo >= hi) {
                loTorqueEl.classList.add("is-invalid");
                if (feedback?.classList.contains("invalid-feedback")) {
                    feedback.innerText = "Must be less than StepHiTorque";
                    feedback.classList.add("d-block"); feedback.style.display = "block";
                }
                isValid = false; errorList.push("StepLoTorque");
            }
        }

        // StepLoAngle < StepAngle（相等也擋）
        if (StepOption === 1) {
            const stepAngleEl = document.getElementById('StepAngle');
            const stepLoAngleEl = document.getElementById('StepLoAngle');
            if (stepAngleEl && stepLoAngleEl) {
                const stepAngle = parseFloat(stepAngleEl.value);
                const stepLoAngle = parseFloat(stepLoAngleEl.value);
                const feedback = stepLoAngleEl.nextElementSibling;

                if (!isNaN(stepAngle) && !isNaN(stepLoAngle) && stepLoAngle >= stepAngle) {
                    stepLoAngleEl.classList.add("is-invalid");
                    if (feedback?.classList.contains("invalid-feedback")) {
                        feedback.innerText = "Must be less than StepAngle";
                        feedback.classList.add("d-block");
                        feedback.style.display = "block";
                    }
                    isValid = false;
                    errorList.push("StepLoAngle");
                }
            }
        }

        // ---- 針對百分比欄位的硬性檢查（依 StepOption 決定要驗證誰） ----
        (function enforcePercentFields() {
            let percentIds = [];
            if (StepOption === 2) {
                percentIds = ['step_limit_hi_tor', 'step_limit_lo_tor'];
            } else if (StepOption === 1) {
                // ⚠️ 不要檢查 step_limit_hi_ang / step_limit_lo_ang，交給 enforceStepOption1PercentOptional()
                return;
            } else {
                return;
            }

            percentIds.forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;

                el.setAttribute('min', '0');
                el.setAttribute('max', '99');
                el.setAttribute('inputmode', 'numeric');

                const val = el.value.trim();
                const n = Number(val);
                const fb = el.nextElementSibling;

                const bad = (
                    val === "" ||
                    !Number.isFinite(n) ||
                    !Number.isInteger(n) ||
                    n < 0 || n > 99 ||
                    !/^(?:[0-9]|[1-9][0-9])$/.test(val)
                );

                if (bad) {
                    el.classList.add("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = "Range: 0 ~ 99";
                        fb.classList.add("d-block");
                        fb.style.display = "block";
                    }
                    isValid = false;
                    errorList.push(id);
                } else {
                    el.classList.remove("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = '';
                        fb.classList.remove("d-block");
                        fb.style.display = "none";
                    }
                }
            });
        })();


        // ---- 交叉驗證：Threshold=2 & DownShift=1 時，StepTorqueDownShift(整數) < StepTorqueTS(整數)
        (function enforceDownshiftVsTS() {
            if (StepEnableThreshold === "2" && StepEnableDownShift === "1") {
                const dsEl = document.getElementById('StepTorqueDownShift'); // DownShift
                const tsEl = document.getElementById('StepTorqueTS');        // Threshold
                if (!dsEl || !tsEl) return;

                const dsVal = Number(dsEl.value);
                const tsVal = Number(tsEl.value);

                const dsInt = Math.floor(dsVal);
                const tsInt = Math.floor(tsVal);

                const fb = dsEl.nextElementSibling;

                if (Number.isFinite(dsVal) && Number.isFinite(tsVal)) {
                    if (!(dsInt < tsInt)) {
                        dsEl.classList.add("is-invalid");
                        if (fb?.classList.contains("invalid-feedback")) {
                            fb.innerText = "Must be less than StepTorqueTS (integer compare)";
                            fb.classList.add("d-block");
                            fb.style.display = "block";
                        }
                        isValid = false;
                        errorList.push('StepTorqueDownShift');
                    } else {
                        dsEl.classList.remove("is-invalid");
                        if (fb?.classList.contains("invalid-feedback")) {
                            fb.innerText = '';
                            fb.classList.remove("d-block");
                            fb.style.display = "none";
                        }
                    }
                }
            }
        })();

        // ---- 交叉驗證（新增）：StepOption==1 時 DownShift 的上限關係（不顯示 Range）----
        (function enforceDownshiftUpperBounds() {
            if (StepOption !== 1) return;

            const dsEl = document.getElementById('StepTorqueDownShift');
            if (!dsEl) return;

            const fb = dsEl.nextElementSibling;
            const raw = dsEl.value;
            const dsNum = Number(raw);
            if (!Number.isFinite(dsNum)) return; // 交給一般驗證處理

            const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            const lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
            const i18n = {
                'zh-tw': { lessThanAngle: '必須小於目標角度', lessThanHiTq: '必須小於扭力上限' },
                'zh-cn': { lessThanAngle: '必须小于目标角度', lessThanHiTq: '必须小于扭矩上限' },
                'en-us': { lessThanAngle: 'Must be less than target angle', lessThanHiTq: 'Must be less than StepHiTorque' },
            };
            const T = i18n[lang] || i18n['en-us'];

            const setInvalid = (msg) => {
                dsEl.classList.add("is-invalid");
                if (fb?.classList.contains("invalid-feedback")) {
                    fb.innerText = msg || 'Invalid input';
                    fb.classList.add("d-block");
                    fb.style.display = "block";
                }
                isValid = false;
                errorList.push('StepTorqueDownShift');
            };
            const clearInvalid = () => {
                dsEl.classList.remove("is-invalid");
                if (fb?.classList.contains("invalid-feedback")) {
                    fb.innerText = '';
                    fb.classList.remove("d-block");
                    fb.style.display = "none";
                }
            };

            if (StepEnableDownShift === "2") {
                const hiEl = document.getElementById('StepHiTorque');
                const hiVal = Number(hiEl?.value);
                if (Number.isFinite(hiVal)) {
                    if (!(dsNum < hiVal)) setInvalid(T.lessThanHiTq);
                    else clearInvalid();
                }
            } else if (StepEnableDownShift === "1") {
                const angEl = document.getElementById('StepAngle');
                const angVal = Number(angEl?.value);
                if (Number.isFinite(angVal)) {
                    const dsInt  = Math.floor(dsNum);
                    const angInt = Math.floor(angVal);
                    if (!(dsInt < angInt)) setInvalid(T.lessThanAngle);
                    else clearInvalid();
                }
            }
        })();


        // ---- 交叉驗證：StepOption==2 時，StepLoAngle 必須小於 StepHiAngle ----
        (function enforceLoAngleLessThanHiAngleForOption2() {
        if (StepOption !== 2) return;

        const loEl = document.getElementById('StepLoAngle');
        const hiEl = document.getElementById('StepHiAngle');
        if (!loEl || !hiEl) return;

        const lo = Number(loEl.value);
        const hi = Number(hiEl.value);
        const fb = loEl.nextElementSibling;

        if (Number.isFinite(lo) && Number.isFinite(hi) && lo >= hi) {
            loEl.classList.add('is-invalid');
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = 'Must be less than StepHiAngle';
            fb.classList.add('d-block');
            fb.style.display = 'block';
            }
            isValid = false;
            if (!errorList.includes('StepLoAngle')) errorList.push('StepLoAngle');
        } else {
            loEl.classList.remove('is-invalid');
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
            }
        }
        })();


        // ---- 交叉驗證：StepOption==2 && StepEnableDownShift==2 時，StepTorqueDownShift 必須小於 StepTorque ----
        (function enforceDownshiftLessThanTorqueForOption2() {
            if (StepOption !== 2 || StepEnableDownShift !== "2") return;

            const dsEl = document.getElementById('StepTorqueDownShift');
            const tqEl = document.getElementById('StepTorque');
            if (!dsEl || !tqEl) return;

            const dsVal = Number(dsEl.value);
            const tqVal = Number(tqEl.value);
            const fb = dsEl.nextElementSibling;

            if (Number.isFinite(dsVal) && Number.isFinite(tqVal)) {
                if (!(dsVal < tqVal)) {
                    dsEl.classList.add("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = "Must be less than StepTorque";
                        fb.classList.add("d-block");
                        fb.style.display = "block";
                    }
                    isValid = false;
                    errorList.push('StepTorqueDownShift');
                } else {
                    dsEl.classList.remove("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = '';
                        fb.classList.remove("d-block");
                        fb.style.display = "none";
                    }
                }
            }
        })();


        // ---- 交叉驗證：StepOption==2 && StepEnableThreshold==2 時，StepTorqueTS 必須小於 StepTorque ----
        (function enforceThresholdLessThanTorqueForOption2() {
            if (StepOption !== 2 || StepEnableThreshold !== "2") return;

            const tsEl = document.getElementById('StepTorqueTS'); // Threshold
            const tqEl = document.getElementById('StepTorque');   // Target torque
            if (!tsEl || !tqEl) return;

            const tsVal = Number(tsEl.value);
            const tqVal = Number(tqEl.value);
            const fb = tsEl.nextElementSibling;

            if (Number.isFinite(tsVal) && Number.isFinite(tqVal)) {
                if (!(tsVal < tqVal)) {
                    tsEl.classList.add("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = "Must be less than StepTorque";
                        fb.classList.add("d-block");
                        fb.style.display = "block";
                    }
                    isValid = false;
                    errorList.push('StepTorqueTS');
                } else {
                    tsEl.classList.remove("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = '';
                        fb.classList.remove("d-block");
                        fb.style.display = "none";
                    }
                }
            }
        })();

        // ---- 交叉驗證：StepOption==2 && StepEnableThreshold==1 時，StepTorqueTS 必須小於 StepHiAngle ----
        (function enforceThresholdLessThanHiAngleForOption2() {
            if (StepOption !== 2 || StepEnableThreshold !== "1") return;

            const tsEl = document.getElementById('StepTorqueTS');   // Threshold
            const hiAngEl = document.getElementById('StepHiAngle'); // HiAngle
            if (!tsEl || !hiAngEl) return;

            const tsVal = Number(tsEl.value);
            const hiAngVal = Number(hiAngEl.value);
            const fb = tsEl.nextElementSibling;

            if (Number.isFinite(tsVal) && Number.isFinite(hiAngVal)) {
                if (!(tsVal < hiAngVal)) {
                    tsEl.classList.add("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = "Must be less than StepHiAngle";
                        fb.classList.add("d-block");
                        fb.style.display = "block";
                    }
                    isValid = false;
                    errorList.push('StepTorqueTS');
                } else {
                    tsEl.classList.remove("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = '';
                        fb.classList.remove("d-block");
                        fb.style.display = "none";
                    }
                }
            }
        })();

        // ---- 交叉驗證：StepOption==2 && StepEnableDownShift==1 時，StepTorqueDownShift 必須小於 StepHiAngle ----
        (function enforceDownshiftLessThanHiAngleForOption2() {
            if (StepOption !== 2 || StepEnableDownShift !== "1") return;

            const dsEl = document.getElementById('StepTorqueDownShift'); // DownShift
            const hiAngEl = document.getElementById('StepHiAngle');      // HiAngle
            if (!dsEl || !hiAngEl) return;

            const dsVal = Number(dsEl.value);
            const hiAngVal = Number(hiAngEl.value);
            const fb = dsEl.nextElementSibling;

            if (Number.isFinite(dsVal) && Number.isFinite(hiAngVal)) {
                if (!(dsVal < hiAngVal)) {
                    dsEl.classList.add("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = "Must be less than StepAngle";
                        fb.classList.add("d-block");
                        fb.style.display = "block";
                    }
                    isValid = false;
                    errorList.push('StepTorqueDownShift');
                } else {
                    dsEl.classList.remove("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = '';
                        fb.classList.remove("d-block");
                        fb.style.display = "none";
                    }
                }
            }
        })();


        // ---- 交叉驗證：StepOption==1 時 step_limit_hi_ang/lo_ang 可以為空，但若有值必須 ≤99 ----
        (function enforceStepOption1PercentOptional() {
            if (StepOption !== 1) return;

            ['step_limit_hi_ang', 'step_limit_lo_ang'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;

                const val = el.value.trim();
                const fb = el.nextElementSibling;

                if (val === "") {
                    // 空值直接通過
                    el.classList.remove("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = '';
                        fb.classList.remove("d-block");
                        fb.style.display = "none";
                    }
                    return;
                }

                const n = Number(val);
                const bad = (
                    !Number.isFinite(n) ||
                    !Number.isInteger(n) ||
                    n < 0 || n > 99
                );

                if (bad) {
                    el.classList.add("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = "Range: 0 ~ 99 (or leave blank)";
                        fb.classList.add("d-block");
                        fb.style.display = "block";
                    }
                    isValid = false;
                    errorList.push(id);
                } else {
                    el.classList.remove("is-invalid");
                    if (fb?.classList.contains("invalid-feedback")) {
                        fb.innerText = '';
                        fb.classList.remove("d-block");
                        fb.style.display = "none";
                    }
                }
            });
        })();

        // ---- 交叉驗證：StepOption==2 時，StepLoTorque 必須小於 StepTorque ----
        (function enforceLoTorqueLessThanTargetForOption2() {
            if (StepOption !== 2) return;

            const loEl = document.getElementById('StepLoTorque');
            const tqEl = document.getElementById('StepTorque');
            if (!loEl || !tqEl) return;

            const loVal = Number(loEl.value);
            const tqVal = Number(tqEl.value);
            if (!Number.isFinite(loVal) || !Number.isFinite(tqVal)) return;

            // 取語言（與你上面用法一致）
            const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            const lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
            const i18n = {
                'zh-tw': { lessThanTq: '必須小於目標扭力' },
                'zh-cn': { lessThanTq: '必须小于目标扭矩' },
                'en-us': { lessThanTq: 'Must be less than StepTorque' },
            };
            const T = i18n[lang] || i18n['en-us'];

            // 依 precision 四捨五入後再比較
            const loRounded = roundTo(loVal, precision);
            const tqRounded = roundTo(tqVal, precision);

            const fb = loEl.nextElementSibling;
            if (!(loRounded < tqRounded)) {
                loEl.classList.add('is-invalid');
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = T.lessThanTq;
                    fb.classList.add('d-block');
                    fb.style.display = 'block';
                }
                isValid = false;
                errorList.push('StepLoTorque');
            } else {
                loEl.classList.remove('is-invalid');
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }
            }
        })();


        // ---- 交叉驗證：StepOption==2 時，StepLoTorque 必須小於 StepHiTorque ----
        (function enforceLoTorqueLessThanHiTorqueForOption2() {
            if (StepOption !== 2) return;

            const loEl = document.getElementById('StepLoTorque');
            const hiEl = document.getElementById('StepHiTorque');
            if (!loEl || !hiEl) return;

            const lo = Number(loEl.value);
            const hi = Number(hiEl.value);
            if (!Number.isFinite(lo) || !Number.isFinite(hi)) return;

            // 取語言（cookie: language），預設 zh-tw
            const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            let lang =
                (typeof getCookie === 'function' && getCookie('language')) ||
                getCookieSafe('language') ||
                'zh-tw';
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

            const i18n = {
                'zh-tw': { lessThanHiTq: '必須小於 StepHiTorque' },
                'zh-cn': { lessThanHiTq: '必须小于 StepHiTorque' },
                'en-us': { lessThanHiTq: 'Must be less than StepHiTorque' },
            };
            const T = i18n[lang] || i18n['en-us'];

            // 依 precision 四捨五入後比較
            const loRounded = roundTo(lo, precision);
            const hiRounded = roundTo(hi, precision);

            const fb = loEl.nextElementSibling;
            if (!(loRounded < hiRounded)) {
                loEl.classList.add('is-invalid');
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = T.lessThanHiTq;
                    fb.classList.add('d-block');
                    fb.style.display = 'block';
                }
                isValid = false;
                if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
            } else {
                loEl.classList.remove('is-invalid');
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }
            }
        })();


        // ---- 交叉驗證：StepOption==2 時，StepLoTorque 必須小於 StepTorque；且 StepTorque 不可等於 StepLoTorque ----
        (function enforceLoTorqueVsTargetForOption2() {
            if (StepOption !== 2) return;

            const loEl = document.getElementById('StepLoTorque');
            const tqEl = document.getElementById('StepTorque');
            if (!loEl || !tqEl) return;

            const loVal = Number(loEl.value);
            const tqVal = Number(tqEl.value);
            if (!Number.isFinite(loVal) || !Number.isFinite(tqVal)) return;

            // 取語言（cookie: language），預設 zh-tw
            const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            let lang =
                (typeof getCookie === 'function' && getCookie('language')) ||
                getCookieSafe('language') ||
                'zh-tw';
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

            const i18n = {
                'zh-tw': {
                    lessThanTq: '必須小於 StepTorque',
                    notEqualLo: '不可等於 StepLoTorque',
                },
                'zh-cn': {
                    lessThanTq: '必须小于 StepTorque',
                    notEqualLo: '不可等于 StepLoTorque',
                },
                'en-us': {
                    lessThanTq: 'Must be less than StepTorque',
                    notEqualLo: 'Must not equal StepLoTorque',
                },
            };
            const T = i18n[lang] || i18n['en-us'];

            // 依 precision 四捨五入後比較（避免浮點誤差）
            const loRounded = roundTo(loVal, precision);
            const tqRounded = roundTo(tqVal, precision);

            const fbLo = loEl.nextElementSibling;
            const fbTq = tqEl.nextElementSibling;

            // 先清乾淨
            const clearInvalid = (el, fb) => {
                el.classList.remove('is-invalid');
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }
            };
            clearInvalid(loEl, fbLo);
            clearInvalid(tqEl, fbTq);

            if (loRounded > tqRounded) {
                // lo > tq -> 標記 StepLoTorque 錯
                loEl.classList.add('is-invalid');
                if (fbLo?.classList.contains('invalid-feedback')) {
                    fbLo.innerText = T.lessThanTq;
                    fbLo.classList.add('d-block');
                    fbLo.style.display = 'block';
                }
                isValid = false;
                if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
            } else if (loRounded === tqRounded) {
                // lo == tq -> 針對需求：標記 StepTorque 錯，顯示「不可等於 StepLoTorque」
                tqEl.classList.add('is-invalid');
                if (fbTq?.classList.contains('invalid-feedback')) {
                    fbTq.innerText = T.notEqualLo;
                    fbTq.classList.add('d-block');
                    fbTq.style.display = 'block';
                }
                isValid = false;
                if (!errorList.includes('StepTorque')) errorList.push('StepTorque');
            }
        })();


        // ---- 交叉驗證：StepOption==2 時，若 StepLoTorque 與 StepHiTorque 或 StepTorque 相等，顯示「扭力下限要小於扭力上限」 ----
        (function enforceEqualitiesForOption2() {
            if (StepOption !== 2) return;

            const loEl = document.getElementById('StepLoTorque');
            const hiEl = document.getElementById('StepHiTorque');
            const tqEl = document.getElementById('StepTorque');
            if (!loEl || !hiEl || !tqEl) return;

            const loVal = Number(loEl.value);
            const hiVal = Number(hiEl.value);
            const tqVal = Number(tqEl.value);
            if (!Number.isFinite(loVal) || !Number.isFinite(hiVal) || !Number.isFinite(tqVal)) return;

            // 語系
            const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            let lang =
                (typeof getCookie === 'function' && getCookie('language')) ||
                getCookieSafe('language') ||
                'zh-tw';
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

            const i18n = {
                'zh-tw': { loLtHi: '扭力下限要小於扭力上限' },
                'zh-cn': { loLtHi: '扭矩下限要小于扭矩上限' },
                'en-us': { loLtHi: 'Torque lower limit must be less than upper limit' },
            };
            const T = i18n[lang] || i18n['en-us'];

            // 以 precision 四捨五入後再比較，避免浮點誤差
            const loR = roundTo(loVal, precision);
            const hiR = roundTo(hiVal, precision);
            const tqR = roundTo(tqVal, precision);

            const equalLoHi = loR === hiR;
            const equalLoTq = loR === tqR;

            if (equalLoHi || equalLoTq) {
                const fbLo = loEl.nextElementSibling;

                // 標記下限錯誤，顯示「下限 < 上限」
                loEl.classList.add('is-invalid');
                if (fbLo?.classList.contains('invalid-feedback')) {
                    fbLo.innerText = T.loLtHi;
                    fbLo.classList.add('d-block');
                    fbLo.style.display = 'block';
                }

                // 清除可能由其它等號驗證留下在 hi/tq 的錯誤，避免重複訊息
                [hiEl, tqEl].forEach(el => {
                    const fb = el?.nextElementSibling;
                    el?.classList.remove('is-invalid');
                    if (fb?.classList.contains('invalid-feedback')) {
                        fb.innerText = '';
                        fb.classList.remove('d-block');
                        fb.style.display = 'none';
                    }
                });

                isValid = false;
                if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
            }
        })();


        // ---- 交叉驗證：StepOption==1 && StepEnableThreshold==2 時，StepTorqueTS 必須小於 StepHiTorque ----
        (function enforceTSLessThanHiTorqueForOption1() {
            if (StepOption !== 1 || StepEnableThreshold !== "2") return;

            const tsEl   = document.getElementById('StepTorqueTS');   // Threshold
            const hiTqEl = document.getElementById('StepHiTorque');   // Hi torque
            if (!tsEl || !hiTqEl) return;

            const tsVal   = Number(tsEl.value);
            const hiTqVal = Number(hiTqEl.value);
            if (!Number.isFinite(tsVal) || !Number.isFinite(hiTqVal)) return;

            // 語系
            const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            let lang =
                (typeof getCookie === 'function' && getCookie('language')) ||
                getCookieSafe('language') ||
                'zh-tw';
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

            const i18n = {
                'zh-tw': { lessThanHiTq: '必須小於 StepHiTorque' },
                'zh-cn': { lessThanHiTq: '必须小于 StepHiTorque' },
                'en-us': { lessThanHiTq: 'Must be less than StepHiTorque' },
            };
            const T = i18n[lang] || i18n['en-us'];

            // 依 precision 四捨五入後比較，避免浮點誤差
            const tsR   = roundTo(tsVal,   precision);
            const hiTqR = roundTo(hiTqVal, precision);

            const fb = tsEl.nextElementSibling;
            if (!(tsR < hiTqR)) {
                tsEl.classList.add('is-invalid');
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = T.lessThanHiTq;
                    fb.classList.add('d-block');
                    fb.style.display = 'block';
                }
                isValid = false;
                if (!errorList.includes('StepTorqueTS')) errorList.push('StepTorqueTS');
            } else {
                tsEl.classList.remove('is-invalid');
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }
            }
        })();








        return { valid: isValid, errors: errorList };
    }




    function bindRoundedWhenVisible(id, digits = 3) {
        const el = document.getElementById(id);
        if (!el || el.dataset.blurFixed) return;

        // ➤ 不管是否可見，都先綁一次（確保 blur 有綁定）
        bindRoundedBlur(id, digits);

        // ➤ 若還不可見，再用 observer 監聽顯示變化
        const observer = new MutationObserver(() => {
            if (el.offsetParent !== null && !el.dataset.blurFixed) {
                bindRoundedBlur(id, digits);
                observer.disconnect();
            }
        });

        observer.observe(el, { attributes: true, attributeFilter: ['style', 'class'] });
    }


    function bindRoundedBlur(id, digits = 3) {
        const el = document.getElementById(id);
        if (!el || el.dataset.blurFixed) return;

        el.addEventListener('blur', function () {
            const raw = this.value;
            const val = parseFloat(raw);
            if (!isNaN(val)) {
                const rounded = val.toFixed(digits);
                this.value = rounded;
                if (typeof input_check === 'function') input_check();
            }
        });

        el.dataset.blurFixed = "1";
    }

</script>
<script>
// === 1) 定義函式（掛到 window，避免 ReferenceError） ===
(function () {
  function setupTorqueInputs(opts) {
    const {
      unitSelect,            // 例如 '#step_torque_unit'
      fields,                // 例如 ['#StepTorque','#StepHiTorque','#StepLoTorque']
      decimalsByUnit,        // 儲存精度對照
      extraInputDecimals={}, // 允許多輸入的小數位（每單位）
      padOnBlur=true
    } = opts;

    const unitEl = document.querySelector(unitSelect);
    const inputEls = fields.map(s => document.querySelector(s)).filter(Boolean);
    if (!unitEl || inputEls.length === 0) return;

    const getUnit = () => parseInt(unitEl.value ?? 1, 10);
    const targetPrecision = () => (decimalsByUnit[getUnit()] ?? 3);
    const allowedInputDecimals = () => (decimalsByUnit[getUnit()] ?? 3) + (extraInputDecimals[getUnit()] ?? 0);

    function applyAttrs(el) {
      const allow = allowedInputDecimals();
      const storeP = targetPrecision();
      el.setAttribute('inputmode', 'decimal');
      el.setAttribute('pattern', `^\\d+(?:\\.\\d{0,${allow}})?$`);
      el.setAttribute('step', String(1 / Math.pow(10, storeP)));
      el.dataset.allowDecimals = String(allow);
      el.dataset.storePrecision = String(storeP);
    }

    function enforceDecimalPlaces(el) {
      const allow = parseInt(el.dataset.allowDecimals || allowedInputDecimals(), 10);
      let v = (el.value || '').replace(/[^\d.]/g, '');
      const i = v.indexOf('.');
      if (i !== -1) {
        const intPart = v.slice(0, i);
        const fracRaw = v.slice(i + 1).replace(/\./g, '');
        v = intPart + '.' + fracRaw.slice(0, allow);
      }
      el.value = v;
    }

    function roundToStorePrecision(el) {
      if (!padOnBlur) return;
      const v = el.value;
      if (v === '' || v === '.') return;
      const n = Number(v);
      if (!Number.isFinite(n)) return;
      const p = parseInt(el.dataset.storePrecision || targetPrecision(), 10);
      el.value = n.toFixed(p); // 依儲存精度四捨五入
    }

    function clearInvalid(el) {
      const fb = el.nextElementSibling;
      el.classList.remove('is-invalid');
      if (fb && fb.classList.contains('invalid-feedback')) {
        fb.textContent = '';
        fb.classList.remove('d-block');
        fb.style.display = 'none';
      }
    }

    // 綁定
    inputEls.forEach(el => {
      applyAttrs(el);
      if (el.dataset.torqueBound === '1') return;
      el.addEventListener('input', function () { enforceDecimalPlaces(this); clearInvalid(this); });
      el.addEventListener('blur',  function () { roundToStorePrecision(this); });
      el.dataset.torqueBound = '1';
    });

    // 單位切換 → 更新規則並即時裁切
    unitEl.addEventListener('change', () => {
      inputEls.forEach(el => { applyAttrs(el); enforceDecimalPlaces(el); });
    });
  }

  window.setupTorqueInputs = setupTorqueInputs; // 對外暴露
})();
</script>

<script>
// === 2) 呼叫（等 DOM 就緒後執行） ===
document.addEventListener('DOMContentLoaded', function () {
  // 儲存精度（依你系統）
  const DECIMALS_BY_UNIT = { 0: 2, 1: 3, 2: 2, 3: 4, 4: 1 };

  // 所有單位在「輸入時」都多 1 位（失焦再四捨五入回儲存精度）
  const EXTRA_INPUT_DECIMALS = Object.fromEntries(
    Object.keys(DECIMALS_BY_UNIT).map(k => [k, 1])
  );

  setupTorqueInputs({
    unitSelect: '#step_torque_unit',
    fields: ['#StepTorque', '#StepHiTorque', '#StepLoTorque','#StepTorqueTS','#StepTorqueDownShift'], // 想套用哪些欄位就加哪些
    decimalsByUnit: DECIMALS_BY_UNIT,
    extraInputDecimals: EXTRA_INPUT_DECIMALS,
    padOnBlur: true
  });
});
</script>

<script>
document.addEventListener('change', function (e) {
  if (e.target.name === 'StepEnableThreshold' && e.target.value === '2') {
    const el = document.querySelector('#StepTorqueTS');
    if (el && !el.dataset.torqueBound) {
      setupTorqueInputs({
        unitSelect: '#step_torque_unit',
        fields: ['#StepTorqueTS'],
        decimalsByUnit: DECIMALS_BY_UNIT,
        extraInputDecimals: EXTRA_INPUT_DECIMALS,
        padOnBlur: true
      });
    }
  }
});


document.addEventListener('change', function (e) {
  if (e.target.name === 'StepEnableDownShift' && e.target.value === '2') {
    const el = document.querySelector('#StepTorqueDownShift');
    if (el && !el.dataset.torqueBound) {
      setupTorqueInputs({
        unitSelect: '#step_torque_unit',
        fields: ['#StepTorqueDownShift'],
        decimalsByUnit: DECIMALS_BY_UNIT,
        extraInputDecimals: EXTRA_INPUT_DECIMALS,
        padOnBlur: true
      });
    }
  }
});
</script>
