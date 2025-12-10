
<?php
    $moniByWin = isset($data['step']['StepMoniByWin']) ? $data['step']['StepMoniByWin'] : null;
    $stepOption = isset($data['step']['StepOption']) ? $data['step']['StepOption'] : null;
?>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const langRaw = getCookie('language') || 'en-us';
        const language = (langRaw.toLowerCase() === 'en') ? 'en-us' : langRaw.toLowerCase();

        alertify.defaults.glossary = {
            title: (language === 'zh-tw') ? '提示' :
                (language === 'zh-cn') ? '提示' : 'Notification',
            ok: (language === 'zh-tw') ? '確定' :
                (language === 'zh-cn') ? '确定' : 'OK',
            cancel: (language === 'zh-tw') ? '取消' :
                    (language === 'zh-cn') ? '取消' : 'Cancel'
        };
    });
    

    // === rounding helpers (global, hoisted) ===
    (function (w) {
    function _roundHalfUp(val, digits) {
        const n = Number(val);
        if (!Number.isFinite(n)) return NaN;
        const d = digits ?? 0;
        const f = Math.pow(10, d);
        const eps = 1 / (f * 1000);     // 修正 0.2404999… 的二進位誤差
        return Math.round((n + eps) * f) / f;
    }
    if (typeof w.roundHalfUp !== 'function') w.roundHalfUp = _roundHalfUp;
    if (typeof w.roundTo     !== 'function') w.roundTo     = (num, digits) => _roundHalfUp(num, digits);
    })(window);


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


    // === 統一的精度與輔助 ===
    const DECIMALS_BY_UNIT = { 0:2, 1:3, 2:2, 3:4, 4:1 };

    function getTorquePrecision() {
        const unit = parseInt(document.getElementById('step_torque_unit')?.value ?? 1, 10);
        return DECIMALS_BY_UNIT[unit] ?? 3;
    }

    function getMoniWinValue() {
        const tq  = document.getElementById("StepMoniByWin_0")?.checked; // 扭力窗
        const ang = document.getElementById("StepMoniByWin_1")?.checked; // 角度窗
        return tq ? 2 : (ang ? 1 : 0);
    }

    function bindRoundedWhenVisible(id, digits) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('blur', () => {
            const n = Number(el.value);
            if (!Number.isFinite(n)) return;
            const f = Math.pow(10, digits);
            const eps = 1 / (f * 1000); // 推開二進位誤差
            el.value = (Math.round((n + eps) * f) / f).toFixed(digits);
        });
    }


        

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
        const stepTorqueTS       = document.getElementById('StepTorqueTS');
        const stepTorqueTSBlock  = document.getElementById('StepTorqueTS_block');
        const showTorque         = document.getElementById('show_torque');
        const showAngle          = document.getElementById('show_angle');
        const thresholdBlock     = document.getElementById('threshold_block');

        const isModeOff    = document.getElementById('threshold_mode_off')?.checked;
        const isModeTorque = document.getElementById('threshold_mode_torque')?.checked;
        const isModeAngle  = document.getElementById('threshold_mode_angle')?.checked;

        // 初始：全部隱藏 + 停用
        [showTorque, showAngle, stepTorqueTSBlock].forEach(el => { if (el) el.style.display = 'none'; });
        if (stepTorqueTS) {
            stepTorqueTS.disabled = true;
            // 先清掉上次模式留下的限制
            stepTorqueTS.removeAttribute('pattern');
            stepTorqueTS.removeAttribute('inputmode');
            stepTorqueTS.removeAttribute('step');

            // ✅ 一次性掛勾：記住上一個「合法小數字串」
            if (!stepTorqueTS.dataset.prevHook) {
            stepTorqueTS.dataset.prev = stepTorqueTS.value || '';
            stepTorqueTS.addEventListener('input', function () {
                const v = this.value;
                // 允許空字串、數字、小數點（最多一個）
                if (/^\d*\.?\d*$/.test(v)) this.dataset.prev = v;
            });
            stepTorqueTS.dataset.prevHook = '1';
            }
        }

        // 取小數位數（扭力單位）
        const places = getTorquePrecision();
        const stepVal = (1 / Math.pow(10, places)).toFixed(places);
        const decimalPattern = '^\\d+(?:\\.\\d{0,' + places + '})?$';

        // TORQUE 模式（允許小數）
        if (isModeTorque) {
            if (stepTorqueTSBlock) stepTorqueTSBlock.style.display = 'block';
            if (stepTorqueTS) {
            stepTorqueTS.disabled = false;
            if (dataType === 'new') stepTorqueTS.value = (0).toFixed(places);

            // ✅ 關鍵：正確設定 step / inputmode / pattern（允許小數）
            stepTorqueTS.setAttribute('step', stepVal);
            stepTorqueTS.setAttribute('inputmode', 'decimal');
            stepTorqueTS.setAttribute('pattern', decimalPattern);

            // 離焦四捨五入到當前單位精度（不會把小數吃掉）
            if (!stepTorqueTS.dataset.blurFixed) {
                stepTorqueTS.addEventListener('blur', () => {
                const n = Number(stepTorqueTS.value);
                if (!Number.isFinite(n)) return;
                stepTorqueTS.value = roundHalfUp(n, places).toFixed(places);
                // ✅ 同步 prev，避免後續檢核回填老值
                stepTorqueTS.dataset.prev = stepTorqueTS.value;
                });
                stepTorqueTS.dataset.blurFixed = '1';
            }
            }
            if (showTorque) showTorque.style.display = 'block';
        }

        // ANGLE 模式（純整數度數）
        else if (isModeAngle) {
            if (stepTorqueTSBlock) stepTorqueTSBlock.style.display = 'block';
            if (stepTorqueTS) {
            stepTorqueTS.disabled = false;
            if (dataType === 'new') stepTorqueTS.value = 0;

            // ✅ 整數限制（維持原來邏輯）
            stepTorqueTS.setAttribute('step', '1');
            stepTorqueTS.setAttribute('pattern', '\\d+');
            stepTorqueTS.setAttribute('inputmode', 'numeric');
            // 更新 prev
            stepTorqueTS.dataset.prev = stepTorqueTS.value;
            }
            if (showAngle) showAngle.style.display = 'block';
        }

        // 整塊顯示
        if (thresholdBlock) thresholdBlock.style.display = isModeOff ? 'none' : 'flex';
    }


    function toggleDownShift() {
        const dataType = "<?php echo $data['type']; ?>";

        const ds      = document.getElementById('StepTorqueDownShift');
        const dsRpm   = document.getElementById('StepRPMDownShift');
        const dsBlock = document.getElementById('StepTorqueDownShift_block');
        const showTor = document.getElementById('show_downshift_torque');
        const showAng = document.getElementById('show_downshift_angle');
        const wrap    = document.getElementById('downshift_block');
        const wrapSpd = document.getElementById('downshift_speed_block');

        const isModeOff    = document.getElementById('downshift_mode_off')?.checked;
        const isModeTorque = document.getElementById('downshift_mode_torque')?.checked;
        const isModeAngle  = document.getElementById('downshift_mode_angle')?.checked;

        // 預設全部隱藏/停用
        if (showTor) showTor.style.display = 'none';
        if (showAng) showAng.style.display = 'none';
        if (dsBlock) dsBlock.style.display = 'none';
        if (ds) {
            ds.disabled = true;
            ds.style.display = 'none';
            // 先清掉屬性與舊的事件綁定
            ds.removeAttribute('pattern');
            ds.removeAttribute('inputmode');
            ds.removeAttribute('step');
            if (ds._roundHandler) { ds.removeEventListener('blur', ds._roundHandler); ds._roundHandler = null; }
            if (ds._intHandler)   { ds.removeEventListener('blur', ds._intHandler);   ds._intHandler   = null; }
        }
        if (dsRpm) dsRpm.disabled = true;

        const places = getTorquePrecision(); // 用統一精度

        // TORQUE 模式
        if (isModeTorque) {
            if (dsBlock) dsBlock.style.display = 'flex';
            if (ds) {
            ds.style.display = 'block';
            ds.disabled = false;
            if (dataType === 'new') ds.value = (0).toFixed(places);
            ds.step = (1 / Math.pow(10, places)).toFixed(places);
            ds.inputMode = 'decimal';

            ds._roundHandler = function () {
                const n = Number(ds.value);
                if (!Number.isFinite(n)) return;
                ds.value = roundHalfUp(n, places).toFixed(places);
            };
            ds.addEventListener('blur', ds._roundHandler);
            }
            if (dsRpm) dsRpm.disabled = false;
            if (showTor) showTor.style.display = 'block';
        }
        // ANGLE 模式（整數）
        else if (isModeAngle) {
            if (dsBlock) dsBlock.style.display = 'flex';
            if (ds) {
            ds.style.display = 'block';
            ds.disabled = false;
            if (dataType === 'new') ds.value = '0';
            ds.step = '1';
            ds.pattern = '\\d+';      // 只允許整數
            ds.inputMode = 'numeric';

            ds._intHandler = function () {
                const v = ds.value.trim();
                if (v === '') return;
                const n = Math.floor(Number(v));
                if (Number.isFinite(n)) ds.value = String(n);
            };
            ds.addEventListener('blur', ds._intHandler);
            }
            if (dsRpm) dsRpm.disabled = false;
            if (showAng) showAng.style.display = 'block';
        }

        // 外層顯示
        if (wrap)   wrap.style.display   = isModeOff ? 'none' : 'flex';
        if (wrapSpd) wrapSpd.style.display = isModeOff ? 'none' : 'flex';

        // ⚠️ 刪除原本無條件的 round 綁定
        // bindRoundedBlur('StepTorqueDownShift');
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
            StepLimiHi = document.getElementById("step_limit_hi_tor")?.value || "30";
            StepLimiLo = document.getElementById("step_limit_lo_tor")?.value || "30";
        } else if (StepOption == 1) {
            StepLimiHi = document.getElementById("step_limit_hi_ang")?.value || "30";
            StepLimiLo = document.getElementById("step_limit_lo_ang")?.value || "30";
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

        const lang = (typeof getCookie === 'function' ? (getCookie('language') || 'en') : 'en');
        const i18n = {
            'en':   { title:"Warning", threshold:"This will remove all existing steps' Threshold settings. Continue?", downshift:"This will remove all existing steps' Downshift settings. Continue?", both:"This will remove all existing steps' Threshold and Downshift settings. Continue?", cancel:"Cancel", ok:"OK" },
            'zh-tw':{ title:"警告",    threshold:"此操作將移除所有既有步驟的 Threshold 設定，是否繼續？", downshift:"此操作將移除所有既有步驟的 Downshift 設定，是否繼續？", both:"此操作將移除所有既有步驟的 Threshold 與 Downshift 設定，是否繼續？", cancel:"取消", ok:"確定" },
            'zh-cn':{ title:"警告",    threshold:"此操作将移除所有既有步骤的 Threshold 设置，是否继续？", downshift:"此操作将移除所有既有步骤的 Downshift 设置，是否继续？", both:"此操作将移除所有既有步骤的 Threshold 和 Downshift 设置，是否继续？", cancel:"取消", ok:"确定" }
        };
        const text = i18n[lang] || i18n['en'];

        // 工具上限值（目前未直接用，但保留）
        const Tool_Max_Torque      = parseFloat(document.getElementById('tool_max_torque').value);
        const Tool_Min_Torque      = parseFloat(document.getElementById('tool_min_torque').value);
        const Tool_Max_RPM         = parseFloat(document.getElementById('tool_max_rpm').value);
        const Tool_Min_RPM         = parseFloat(document.getElementById('tool_min_rpm').value);
        const Tool_Max_Torque_Diff = parseFloat(document.getElementById('tool_max_torque_diff').value);

        // 先做前端驗證（強韌化）
        let check;
        try {
            check = (typeof input_check === 'function') ? input_check() : true;
        } catch (e) {
            // 你的 input_check 可能用 throw new Error('__BLOCK_SUBMIT__') 擋提交流程
            if (e && e.message === '__BLOCK_SUBMIT__') return;
            console.error('input_check threw:', e);
            check = { valid: false, errors: [String(e?.message || e)] };
        }

        // 正規化回傳：允許 boolean / object / undefined
        if (check == null) {
            check = { valid: false, errors: [] };
        } else if (typeof check === 'boolean') {
            check = { valid: check, errors: [] };
        } else if (typeof check === 'object') {
            if (typeof check.valid !== 'boolean') check.valid = !!check.valid;
            if (!Array.isArray(check.errors)) check.errors = [];
        } else {
            check = { valid: !!check, errors: [] };
        }

        if (!check.valid) {
            // 如需可在此集中顯示錯誤：console.warn('validation errors:', check.errors);
            return;
        }

        const needConfirmThreshold = StepEnableThreshold !== "0";
        const needConfirmDownshift = StepEnableDownShift !== "0";

        // ★ 只有「step > 1」且有啟用 Threshold/Downshift 才顯示確認
        if (isStepGt1 && (needConfirmThreshold || needConfirmDownshift)) {
            const msg = (needConfirmThreshold && needConfirmDownshift) ? text.both
                    : (needConfirmThreshold ? text.threshold : text.downshift);

            alertify
            .confirm(
                text.title,
                msg,
                function () { submit_step_ajax(); },
                function () { return; }
            )
            .set('labels', { ok: text.ok, cancel: text.cancel });

        } else {
            submit_step_ajax();
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
                // 後端可能回字串或 JSON，這裡都保護
                let resObj = null;
                try { resObj = (typeof response === 'string') ? JSON.parse(response) : response; } catch {}
                // 如果你需要讀錯誤：
                const errors = Array.isArray(resObj?.errors) ? resObj.errors : [];
                if (errors.length) {
                console.warn('server errors:', errors);
                // 這裡可顯示 alertify.error(...) 等
                }
                // 原流程
                success_response_seq(response, 'spinner', `../public/?url=Step/index/${job_id}/${seq_id}`);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            },
            complete: function () {
                // 確保關閉遮罩
                document.querySelector(".main-content").classList.remove("overlay-active");
                document.getElementById("spinner").style.display = 'none';
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
        const dataType = '<?php echo $data['type']; ?>';


        const unitLabels = {
            'kgf.cm': { 'zh-cn': '公斤.公分', 'zh-tw': '公斤.公分', 'default': 'kgf.cm' },
            'lbf.in': { 'zh-cn': '磅.英吋', 'zh-tw': '磅.英吋', 'default': 'lbf.in' },
            'N.m':    { 'zh-cn': '牛顿.米',  'zh-tw': '牛頓.公尺',   'default': 'N.m' },
            'kgf.m':  { 'zh-cn': '公斤.米',   'zh-tw': '公斤.公尺', 'default': 'kgf.m' },
            'cN.m':   { 'zh-cn': '牛頓.厘米','zh-tw': '牛頓.釐米', 'default': 'cN.m' },
        };
        const translatedUnit = unitLabels[rawUnit]?.[language] || rawUnit;

        const labelTexts = {
            'zh-cn': { 2: '目标扭力', 1: '目标角度' },
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

        // ✅ 新增判斷：編輯模式且 StepOption == 2，step_limit_hi_tor && step_limit_lo_tor  若是空值就給 30
        if (dataType == 'edit' && selectVal == 2) {
            ["step_limit_hi_tor", "step_limit_lo_tor"].forEach(id => {
                const el = document.getElementById(id);
                if (el && (el.value === "" || el.value === null)) {
                    el.value = 30;
                }
            });
        }

        if (dataType == 'edit' && selectVal == 1) {
            ["step_limit_hi_ang", "step_limit_lo_ang"].forEach(id => {
                const el = document.getElementById(id);
                if (el && (el.value === "" || el.value === null)) {
                    el.value = 30;
                }
            });
        }
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

    function enforceDownShiftInteger() {
        const downShift = document.querySelector('[name="StepEnableDownShift"]:checked')?.value;
        if (downShift !== "1") return;

        const dsEl = document.getElementById('StepTorqueDownShift');
        if (!dsEl) return;

        let rawVal = dsEl.value.trim();
        if (rawVal.includes('.')) {
            let intVal = parseInt(rawVal, 10);
            dsEl.value = intVal;  // 自動轉成整數
        }
    }



    /** 正規四捨五入 (half-up)，避開 0.2405 這類二進位誤差 */
    function normalizeStepTorque() {
        const el = document.getElementById('StepTorque');
        const digits = getTorquePrecision();   // 用統一的精度函式
        if (!el) return { num: NaN, text: '', precision: digits };

        const raw = el.value.trim();
        const n = Number(raw);
        if (!Number.isFinite(n)) return { num: NaN, text: raw, precision: digits };

        const f = Math.pow(10, digits);
        const eps = 1 / (f * 1000);            // 推開二進位誤差
        const rounded = Math.round((n + eps) * f) / f;

        el.value = rounded.toFixed(digits);     // 回寫並保留尾端 0
        return { num: rounded, text: el.value, precision: digits };
    }


    /********** 共用 Helpers **********/
    function markInvalid(el, key = '__generic__') {
        if (!el) return;
        el.dataset['err_' + key] = '1';
        el.classList.add('is-invalid');
    }
    function unmarkInvalid(el, key = '__generic__') {
        if (!el) return;
        delete el.dataset['err_' + key];
        const stillHas = Object.keys(el.dataset).some(k => k.startsWith('err_'));
        if (!stillHas) el.classList.remove('is-invalid');
    }
    function hideInlineFeedback(el) {
        const fb = el?.nextElementSibling;
        if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
        }
    }

    /** 兼容 radio/select 的 StepOption 取值 */
        function getStepOption() {
        const radio = document.querySelector('input[name="StepOption"]:checked');
        const select = document.getElementById('StepOption');
        const raw = (radio?.value ?? select?.value ?? '').trim();
        const n = Number(raw);
        return Number.isFinite(n) ? n : 0;
    }

    /********** 交叉驗證：StepLoAngle < StepHiAngle（相等也擋；空值也當錯） **********/
    function enforceLoHiAngle(opts = {}) {
        const {
            enforceWhen = [1, 2],
            loSel = '#StepLoAngle',
            hiSel = '#StepHiAngle',
            errorListRef = null, // 傳入 input_check() 的 errorList（可選）
            stepOption = null    // 若外部已算好，可傳進來；否則本函式會自己抓
        } = opts;

        const stepOpt = (stepOption == null) ? getStepOption() : Number(stepOption);
        if (!enforceWhen.includes(stepOpt)) return true;

        const loEl = (typeof loSel === 'string') ? document.querySelector(loSel) : loSel;
        const hiEl = (typeof hiSel === 'string') ? document.querySelector(hiSel) : hiSel;
        if (!loEl || !hiEl) return true;

        const toNum = v => { const s = String(v ?? '').trim(); return s === '' ? NaN : Number(s); };
        const lo = toNum(loEl.value);
        const hi = toNum(hiEl.value);

        // 語系
        function getCookieSafe(name){
            try {
            const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
            return m ? decodeURIComponent(m[1]) : null;
            } catch { return null; }
        }


        let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : (getCookieSafe('language') || 'en-us')) || 'en-us';
        lang = String(lang).toLowerCase();
        if (lang === 'en') lang = 'en-us';
        if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

        const I18N = {
            'en-us': { title: 'Warning', ok: 'OK',   msg: 'Lower limit angle (degrees) must be less than upper limit angle' },
            'zh-tw': { title: '警告',   ok: '確定', msg: '角度下限(度) 需小於 角度上限' },
            'zh-cn': { title: '警告',   ok: '确定', msg: '角度下限（度）必须小于 角度上限' }
        };
        const T = I18N[lang];

        // 只要有一個不是有效數字，或 lo >= hi，就當作不通過
        const bad = !(Number.isFinite(hi) && Number.isFinite(lo)) || (lo >= hi);
        const KEY = 'LoHiAngle';

        if (bad) {
            markInvalid(loEl, KEY);
            hideInlineFeedback(loEl);

            if (!loEl.dataset.alertShownLoHi) {
            loEl.dataset.alertShownLoHi = '1';
            alertify
                .alert(T.title, T.msg, function () {
                try { loEl.focus(); loEl.select?.(); } catch {}
                delete loEl.dataset.alertShownLoHi;
                })
                .set('labels', { ok: T.ok });
            }

            if (Array.isArray(errorListRef) && !errorListRef.includes('StepLoAngle')) {
            errorListRef.push('StepLoAngle');
            }
            return false;
        } else {
            unmarkInvalid(loEl, KEY);
            hideInlineFeedback(loEl);
            delete loEl.dataset.alertShownLoHi;
            return true;
        }
    }

    // === DownShift 角度與 RPM 的整合檢核（支援 StepOption 1/2；彈窗 + i18n）===
    function validateDownshiftAngleAndRPM(opts = {}) {
        const {
            stepOption,                // number，像 parseInt(document.getElementById('StepOption').value,10)
            enableDownShift,           // string '0' | '1' | '2'（radio 的值）
            rpmMin,                    // 允許下限（Number）
            rpmMax,                    // 允許上限（Number）
            errorList,                 // 你的錯誤陣列（會 push 欄位 id）
        } = opts;

        // 僅在：StepOption==2，或 (StepOption==1 且 DownShift==1) 時檢查
        const needCheck =
            (stepOption === 2) ||
            (stepOption === 1 && String(enableDownShift) === "1");

        if (!needCheck) return true;

        const dsEl     = document.getElementById('StepTorqueDownShift'); // 「降速點角度/扭力」欄位（你的專案命名如此）
        const hiAngEl  = document.getElementById('StepHiAngle');
        const rpmDSEl  = document.getElementById('StepRPMDownShift');

        // 元件缺失就略過（交由其他規則或後端保護）
        if (!dsEl || !hiAngEl || !rpmDSEl) return true;

        const dsVal    = Number(dsEl.value);
        const hiAngVal = Number(hiAngEl.value);
        const rpmDSRaw = rpmDSEl.value.trim();
        const rpmDS    = Number(rpmDSRaw);

        // 共用：收起欄位旁 inline 提示
        const hideInline = (el) => {
            const fb = el.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
            }
        };

        // 語系
        function getCookieSafe(name){
            try {
            const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
            return m ? decodeURIComponent(m[1]) : null;
            } catch { return null; }
        }
        let lang = (typeof getLangAndUnit === 'function'
                        ? (getLangAndUnit().lang || 'en-us')
                        : (getCookieSafe('language') || 'en-us'))
                    .toLowerCase();
        if (lang === 'en') lang = 'en-us';
        if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

        const I18N = {
            'en-us': {
            title: 'Warning',
            ok: 'OK',
            dsLtHi: 'Downshift angle (degrees) must be less than upper angle',
            rpmOut: (range) => `Downshift RPM is out of range (${range})`,
            },
            'zh-tw': {
            title: '警告',
            ok: '確定',
            dsLtHi: '降速點角度(度數) 需小於角度上限',
            rpmOut: (range) => `降速點轉速 超出範圍（${range}）`,
            },
            'zh-cn': {
            title: '警告',
            ok: '确定',
            dsLtHi: '降速点角度(度数) 需小于角度上限',
            rpmOut: (range) => `降速点转速 超出范围（${range}）`,
            }
        };
        const T = I18N[lang];

        let ok = true;

        // === (1) 檢查 DownShift 角度 < HiAngle ===
        if (Number.isFinite(dsVal) && Number.isFinite(hiAngVal)) {
            if (!(dsVal < hiAngVal)) {
            dsEl.classList.add('is-invalid');
            hideInline(dsEl);

            // 節流避免一次彈多次
            if (!window._alertingDSltHiAngle) {
                window._alertingDSltHiAngle = true;
                alertify
                .alert(T.title, T.dsLtHi, function () {
                    try { dsEl.focus(); dsEl.select?.(); } catch {}
                    window._alertingDSltHiAngle = false;
                })
                .set('labels', { ok: T.ok });
            }

            ok = false;
            if (Array.isArray(errorList) && !errorList.includes('StepTorqueDownShift')) {
                errorList.push('StepTorqueDownShift');
            }
            } else {
            dsEl.classList.remove('is-invalid');
            hideInline(dsEl);
            }
        }

        // === (2) 檢查 RPMDownShift 在範圍內（整數 1~4 位）===
        const haveMin = Number.isFinite(rpmMin);
        const haveMax = Number.isFinite(rpmMax);
        const pattern = /^\d{1,4}$/;
        const badFormat = (rpmDSRaw === '' || !pattern.test(rpmDSRaw) || !Number.isInteger(rpmDS));
        const badRange  =
            (haveMin && Number.isFinite(rpmDS) && rpmDS < rpmMin) ||
            (haveMax && Number.isFinite(rpmDS) && rpmDS > rpmMax);

        if (badFormat || badRange) {
            rpmDSEl.classList.add('is-invalid');
            hideInline(rpmDSEl);

            let rangeStr = 'N/A';
            if (haveMin && haveMax) rangeStr = `${parseInt(rpmMin,10)} ~ ${parseInt(rpmMax,10)}`;
            else if (haveMin)        rangeStr = `≥ ${parseInt(rpmMin,10)}`;
            else if (haveMax)        rangeStr = `≤ ${parseInt(rpmMax,10)}`;

            if (!window._alertingRPMDSRange) {
            window._alertingRPMDSRange = true;
            alertify
                .alert(T.title, T.rpmOut(rangeStr), function () {
                try { rpmDSEl.focus(); rpmDSEl.select?.(); } catch {}
                window._alertingRPMDSRange = false;
                })
                .set('labels', { ok: T.ok });
            }

            ok = false;
            if (Array.isArray(errorList) && !errorList.includes('StepRPMDownShift')) {
            errorList.push('StepRPMDownShift');
            }
        } else {
            rpmDSEl.classList.remove('is-invalid');
            hideInline(rpmDSEl);
        }

        return ok;
    }




    function input_check() {

        
        var isValid = true;

        const precision = getTorquePrecision();
        const torque_unit = parseInt(document.getElementById('step_torque_unit')?.value ?? 1, 10);
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
        const torquePrecision = precision;

        const stepOpt = parseInt(document.getElementById("StepOption")?.value ?? 0, 10) || 0;


        // 讀 StepTorque，並做「多一位小數」四捨五入，順便回寫到欄位
        const stepTorqueEl = document.getElementById('StepTorque');
        let check_target_torque_raw = stepTorqueEl?.value ?? '';
        let stepTorqueNum = Number(check_target_torque_raw);
        if (stepTorqueEl && Number.isFinite(stepTorqueNum)) {

            stepTorqueNum = roundHalfUp(stepTorqueNum, torquePrecision);
            stepTorqueEl.value = stepTorqueNum.toFixed(torquePrecision); 
            check_target_torque_raw = stepTorqueEl.value;
        }

        // 讀 StepHiTorque / StepLoTorque，依單位「多一位」四捨五入並回寫
        const stepHiTorqueEl = document.getElementById('StepHiTorque');
        const stepLoTorqueEl = document.getElementById('StepLoTorque');

        if (stepHiTorqueEl && stepHiTorqueEl.value !== '') {
            const n = Number(stepHiTorqueEl.value);
            if (Number.isFinite(n)) stepHiTorqueEl.value = n.toFixed(torquePrecision);
        }

        if (stepLoTorqueEl && stepLoTorqueEl.value !== '') {
            const n = Number(stepLoTorqueEl.value);
            if (Number.isFinite(n)) stepLoTorqueEl.value = n.toFixed(torquePrecision);
        }

       
        (function maybeValidateEqLo() {
            const stepOpt = parseInt(document.getElementById("StepOption")?.value ?? 0, 10);

            // 只在 StepOption==2 時檢查是否三者皆相等；若皆相等，直接略過 validateTorqueEqualLo
            if (stepOpt === 2) {
                const tqEl = document.getElementById('StepTorque');
                const hiEl = document.getElementById('StepHiTorque');
                const loEl = document.getElementById('StepLoTorque');

                const tq = Number(tqEl?.value), hi = Number(hiEl?.value), lo = Number(loEl?.value);

                const _round = (typeof roundTo === 'function')
                ? roundTo
                : (n, d = 3) => { const v = Number(n); return isNaN(v) ? NaN : parseFloat(v.toFixed(d)); };
                const p = (typeof precision === 'number') ? precision : 3;

                const tqR = _round(tq, p), hiR = _round(hi, p), loR = _round(lo, p);

                if (Number.isFinite(tqR) && Number.isFinite(hiR) && Number.isFinite(loR) &&
                    tqR === hiR && tqR === loR) {
                // 三者（四捨五入到當前精度）相等 → 不執行 validateTorqueEqualLo
                return;
                }
            }

            // 其餘情況才執行原本檢核
            if (!validateTorqueEqualLo()) {
                isValid = false;
                if (!errorList.includes('StepTorque')) errorList.push('StepTorque');
            }
        })();



        enforceDownShiftInteger();

        const Tool_Max_RPM = document.getElementById('check_hi_rpm').value;
        const Tool_Min_RPM = document.getElementById('check_lo_rpm').value;
        const minStepLoTorque = parseFloat((stepTorqueNum - increment).toFixed(precision));
        const rpm_check = document.getElementById('StepRPM').value;


       // ★ 目標扭力四捨五入到當前顯示精度（用來算 StepHiTorque 的動態下限）
        const stepTorqueRounded = roundHalfUp(check_target_torque_raw, torquePrecision);

        // ★ 以顯示精度推得的基本增量（確保「嚴格大於」）
        const precisionBump = Number((1 / Math.pow(10, torquePrecision)).toFixed(torquePrecision));

        // ★ 針對不同單位的最小可分辨差覆寫（key = 扭力單位代碼）
        // 0: kgf·cm, 1: N·m, 2: lbf·in, 3: cN·m, 4: kgf·m
        const BUMP_OVERRIDE = {
        4: 0.0003 // kgf·m 需要 0.0003（例如 0.0100 → 0.0103）
        };

        // ★ 最終使用的 bump（有覆寫用覆寫，沒有就用精度推得的 increment）
        const bump = Object.prototype.hasOwnProperty.call(BUMP_OVERRIDE, torque_unit)
        ? BUMP_OVERRIDE[torque_unit]
        : precisionBump;

        // ★ 動態下限：StepHiTorque 必須「嚴格大於」 StepTorque
        const dynamicHiMin = Number.isFinite(stepTorqueRounded)
        ? roundTo(stepTorqueRounded + bump, torquePrecision)
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
            { id: 'StepRPM', pattern: /^\d{1,4}$/, min: Tool_Min_RPM, max: Tool_Max_RPM,noRangeMessage: true },
            { id: 'StepRPMDownShift', pattern: /^\d{1,4}$/, ...limits.torque.rpmDownshift_1,noRangeMessage: true },
            { id: 'StepTorqueDownShift', pattern: /^\d{1,5}(\.\d{1,9})?$/, ...limits.torque.torqueDownshift,noRangeMessage: true },
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

        
        // ★★★ 目標角度 + 角度降速：不要用扭力的 min/max 來檢 StepTorqueDownShift
        if (StepOption === 1 && StepEnableDownShift === "1") {
            // 先移除既有扭力規則
            conditions = conditions.filter(c => c.id !== 'StepTorqueDownShift');
                // 改成角度欄位規則（整數 1~30600）
                conditions.push({
                id: 'StepTorqueDownShift',
                pattern: /^\d{1,5}$/,
                min: 1,
                max: 30600,
                integerOnly: true,
                noRangeMessage: true
                });
        }

        // ★★★ 目標扭力(Opt=2) + 角度降速(DS=1)：StepTorqueDownShift 改用「角度」規則，不要套扭力 min/max
        if (StepOption === 2 && StepEnableDownShift === "1") {
            // 先移除原本扭力的規則
            conditions = conditions.filter(c => c.id !== 'StepTorqueDownShift');
            // 改成角度欄位（整數 1~30600）；真正與 StepHiAngle 的關係由後面的 IIFE 交叉檢核處理
            conditions.push({
              id: 'StepTorqueDownShift',
              pattern: /^\d{1,5}$/,
              min: 1,
              max: 30600,
              integerOnly: true,
              noRangeMessage: true
            });
        }


        // StepOption 分支
        const pattern0to99 = /^(?:[0-9]|[1-9][0-9])$/; // 0~99
        if (StepOption === 2) {

                const hiAngEl = document.getElementById('StepHiAngle');
                const loAngEl = document.getElementById('StepLoAngle');

                
                conditions.push(
                    { id: 'StepTorque',    pattern: new RegExp('^\\d{1,5}(?:\\.\\d{1,' + torquePrecision + '})?$'), ...limits.torque.torque },
                    { id: 'StepHiTorque',  pattern: new RegExp('^\\d{1,5}(?:\\.\\d{1,' + torquePrecision + '})?$'), ...limits.torque.limitHi },
                    { id: 'StepHiAngle',  pattern: /^\d{1,5}$/, ...limits.angle.limitHi },
                    { id: 'StepLoAngle',  pattern: /^\d{1,5}$/, min: 0, integerOnly: true, noRangeMessage: true }
                );

                // 同步：HiAngle 改變時即時更新 LoAngle 的 max（只綁一次）
                if (hiAngEl && loAngEl && !hiAngEl.dataset.syncLoMaxBound) {
                    const syncLoMax = () => {
                        const h  = Number(hiAngEl.value);
                        const mx = (Number.isFinite(h) && h > 0) ? (h - 1) : 30600;
                        loAngEl.setAttribute('max', mx);

                        // 如目前 lo 超過新上限，夾回上限（可選）
                        //const v = Number(loAngEl.value);
                        //if (Number.isFinite(v) && v > mx) loAngEl.value = String(mx);
                    };
                    syncLoMax();
                    hiAngEl.addEventListener('input', syncLoMax);
                    hiAngEl.dataset.syncLoMaxBound = '1'; // 打標記避免重複綁定
                }

                if (StepMoniByWin === 2) {
                    const stepOpt = parseInt(document.getElementById("StepOption")?.value ?? 0, 10);
                    const quiet = (stepOpt === 2); // StepOption==2 → 只顯示提示，不彈窗
                    checkStepLimit('step_limit_hi_tor', '上限', quiet);
                    checkStepLimit('step_limit_lo_tor', '下限', quiet);
                }
        } else if (StepOption === 1) {
            const stepAngleVal = parseFloat(document.getElementById('StepAngle')?.value || 0);
            conditions.push(
                { id: 'StepAngle',   pattern: /^\d{1,5}$/, ...limits.angle.angle, noRangeMessage: true },
                { id: 'StepHiAngle', pattern: /^\d{1,5}$/, ...limits.angle.limitHi },
                { id: 'StepLoAngle', pattern: /^\d{1,5}$/, min: limits.angle.limitLo.min, max: stepAngleVal > 0 ? stepAngleVal - 1 : limits.angle.limitLo.max, noRangeMessage: true },
                { id: 'StepHiTorque',  pattern: new RegExp('^\\d{1,5}(?:\\.\\d{1,' + torquePrecision + '})?$'), min: check_target_tor_lo_raw, max: check_hi_tor_after_temp, precision, roundBeforeCompare: true }
            );

            (function addAngPercentCondsIfEditable() {
                if (StepOption !== 1) return;
                const hi = document.getElementById('step_limit_hi_ang');
                const lo = document.getElementById('step_limit_lo_ang');
                const editable = (el) => el && !el.disabled && getComputedStyle(el).display !== 'none';
                if (editable(hi)) conditions.push({ id: 'step_limit_hi_ang', pattern: /^(?:[0-9]|[1-9][0-9])$/, min: 0, max: 99, integerOnly: true });
                if (editable(lo)) conditions.push({ id: 'step_limit_lo_ang', pattern: /^(?:[0-9]|[1-9][0-9])$/, min: 0, max: 99, integerOnly: true });
            })();



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

        //let isValid = true;
        let errorList = [];




        // 逐條驗證
        conditions.forEach(cond => {
            const el = document.getElementById(cond.id);
            const valid = validateInput(el, cond.pattern, cond.min, cond.max, cond);
            if (!valid) { isValid = false; errorList.push(cond.id); }
        });

        // ✅ 只在 StepOption == 1 時，呼叫目標角度用的 Offset 驗證
        if (typeof enforceOffsetForOption1_Angle === 'function') {
            const _opt = Number(document.getElementById('StepOption')?.value) || 0; // 別再宣告 stepOpt，避免撞名
            if (_opt === 1) {
                if (!enforceOffsetForOption1_Angle()) {
                // 同步你的驗證旗標與錯誤清單（這段跟你原本風格一致）
                isValid = false;
                if (Array.isArray(errorList) && !errorList.includes('StepTorqueOffset')) {
                    errorList.push('StepTorqueOffset');
                }
                return false; // 直接中止 input_check()
                }
            }
        }

        
        // ==== Offset 檢核（StepOption==2 扭力目標）：用交集顯示真實區間「(lower ～ upper)」 ==== 
        (function validateTorqueOffsetForOption2(){
            try {
                const StepOption = Number(document.getElementById('StepOption')?.value) || 0;
                if (StepOption !== 2) return;

                // 兼容你的 id：有時是 StepTorque，有時寫成 StepTorquet
                const tqEl  = document.getElementById('StepTorque') || document.getElementById('StepTorquet');
                const offEl = document.getElementById('StepTorqueOffset');
                const loEl  = document.getElementById('check_target_tor_lo');
                const hiEl  = document.getElementById('check_target_tor_hi');
                if (!tqEl || !offEl || !loEl || !hiEl) return;

                const T   = Number(tqEl.value);                 // 目標扭力
                const off = Math.abs(Number(offEl.value) || 0); // 補償值(幅度)
                const Lo  = Number(loEl.value);                 // 規格下限
                const Hi  = Number(hiEl.value);                 // 規格上限
                if (![T, Lo, Hi].every(Number.isFinite)) return;

                // 目前選的 +/− 決定實際符號
                const isMinus = document.getElementById('join_offset_minus')?.checked === true;

                // 語系/單位/精度
                const lu = (typeof getLangAndUnit === 'function') ? getLangAndUnit() : { lang: 'zh-tw', unit: '' };
                let lang = String(lu.lang || 'zh-tw').toLowerCase(); if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';
                const unit = lu.unit || '';
                const SEP  = (lang === 'en-us') ? '~' : '～';

                const unitIsCNm = Number(window.torque_unit) === 4; // 4 = cN·m
                const basePrec  = (typeof window.precision === 'number' && isFinite(window.precision)) ? window.precision : 3;
                const dispPrecision = unitIsCNm ? 1 : basePrec;

                const EPS = Math.pow(10, -(dispPrecision + 2));
                const zfix = (n)=> (Math.abs(n) < EPS ? 0 : n);
                const fmt = (n)=>{
                const s = Number(zfix(n)).toFixed(dispPrecision);
                return s.replace(/\.?0+$/, ''); // 去尾 0，顯示如 -0.72、0.192
                };

                // === 規則交集 ===
                // A) |offset| ≤ 0.3 * T   →   offset ∈ [-0.3T, +0.3T]
                const lowerBy30 = -0.3 * Math.max(0, T);
                const upperBy30 =  0.3 * Math.max(0, T);

                // B) 0.7*Lo ≤ T + offset ≤ 1.08*Hi   →   offset ∈ [0.7*Lo - T, 1.08*Hi - T]
                const loAllowed = 0.70 * Lo;
                const hiAllowed = 1.08 * Hi;
                const lowerBySpec = loAllowed - T;
                const upperBySpec = hiAllowed - T;

                // 交集區間
                const lowerBound = Math.max(lowerBy30,  lowerBySpec);
                const upperBound = Math.min(upperBy30,  upperBySpec);

                // 邊界不合理就放行，避免卡死（理論上不會）
                if (!(lowerBound <= upperBound)) return;

                // 依 radio 決定帶符號的 offset
                const signedOffset = isMinus ? -off : off;

                // 不在區間內 → 顯示錯誤（含區間）
                if (signedOffset < lowerBound - EPS || signedOffset > upperBound + EPS) {
                offEl.classList.add('is-invalid');
                const fb = offEl.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }

                const rangeStr = `${fmt(lowerBound)} ${SEP} ${fmt(upperBound)}`;
                const I18N = {
                    'en-us': { title: 'Warning', msg: `Torque offset (${unit}) is out of range. Allowed (${rangeStr})` },
                    'zh-tw': { title: '警告',   msg: `扭力補償值（${unit}）超出範圍，允許（${rangeStr}）` },
                    'zh-cn': { title: '警告',   msg: `扭矩补偿值（${unit}）超出范围，允许（${rangeStr}）` },
                };
                const ui = I18N[lang] || I18N['zh-tw'];

                try {
                    alertify.alert(ui.title, ui.msg).set('labels', { ok: (lang === 'en-us' ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定')) });
                } catch { window.alert(ui.title + '\n' + ui.msg); }

                if (typeof isValid !== 'undefined') isValid = false;
                if (Array.isArray(errorList) && !errorList.includes('StepTorqueOffset')) {
                    errorList.push('StepTorqueOffset');
                }
                return false;
                } else {
                offEl.classList.remove('is-invalid');
                }
            } catch (e) {
                console.error('[validateTorqueOffsetForOption2] error:', e);
            }
        })();





        (function enforceStepAngleUpperBoundForOption1() {
            const stepOpt = parseInt(document.getElementById('StepOption')?.value ?? 0, 10);
            if (stepOpt !== 1) return;

            const el = document.getElementById('StepAngle');
            if (!el) return;

            const raw = (el.value || '').trim();
            const n = Number(raw);
            if (!Number.isFinite(n)) return;

            const minR = 1;
            const maxR = 30600;

            if (n > maxR) {
                // 標紅，但我們關掉欄位旁邊的 inline 錯誤（改用彈窗）
                el.classList.add('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }

                // 語系（優先共用函式，否則 cookie fallback）
                function getCookieSafe(name) {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
                }
                let lang = (typeof getLangAndUnit === 'function'
                            ? (getLangAndUnit().lang || 'en-us')
                            : (getCookieSafe('language') || 'en-us'))
                            .toLowerCase();
                if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

                const I18N = {
                'en-us': { title: 'Warning', msg: `Target angle (degrees) is out of range. Max allowed: ${maxR}`, ok: 'OK' },
                'zh-tw': { title: '警告',   msg: `目標角度(度數) 超出範圍，最大允許：${maxR}`,           ok: '確定' },
                'zh-cn': { title: '警告',   msg: `目标角度(度数) 超出范围，最大允许：${maxR}`,           ok: '确定' }
                }[lang];

                // 節流，避免一次驗證彈多次
                if (window._alertingStepAngleMaxOpt1) {
                isValid = false;
                if (!errorList.includes('StepAngle')) errorList.push('StepAngle');
                return;
                }
                window._alertingStepAngleMaxOpt1 = true;

                alertify
                .alert(I18N.title, I18N.msg, function () {
                    try { el.focus(); el.select?.(); } catch {}
                    window._alertingStepAngleMaxOpt1 = false;
                })
                .set('labels', { ok: I18N.ok });

                isValid = false;
                if (!errorList.includes('StepAngle')) errorList.push('StepAngle');
            } else {
                // 合法：清掉殘留樣式/inline
                el.classList.remove('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }
            }
        })();


        (function enforceDownshiftAngleGtTargetAngle_Opt1_DS1() {
            // 僅在 StepOption=1 且「角度降速(=1)」時啟用
            const stepOpt = parseInt(document.getElementById('StepOption')?.value ?? 0, 10);
            const dsMode  = document.querySelector('input[name="StepEnableDownShift"]:checked')?.value ?? "0";
            if (stepOpt !== 1 || dsMode !== "1") return;

            const dsEl  = document.getElementById('StepTorqueDownShift'); // 降速點角度（放在這個欄位）
            const tgtEl = document.getElementById('StepAngle');           // 目標角度
            if (!dsEl || !tgtEl) return;

            const dsVal  = Number(dsEl.value);
            const tgtVal = Number(tgtEl.value);
            if (!Number.isFinite(dsVal) || !Number.isFinite(tgtVal)) return;

            // 角度以整數比較（與 Option=1 其它角度檢核一致）
            const dsInt  = Math.floor(dsVal);
            const tgtInt = Math.floor(tgtVal);

            // 只在「降速點角度 > 目標角度」時觸發（若也要擋等於，改成 dsInt >= tgtInt）
            if (dsInt > tgtInt) {
                // 標紅，但把欄位旁邊的 inline 錯誤收掉，改用彈窗
                dsEl.classList.add('is-invalid');
                const fb = dsEl.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }

                // 語系（優先共用函式；否則用 cookie fallback）
                function getCookieSafe(name){
                try { const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
                catch { return null; }
                }
                let lang = (typeof getLangAndUnit === 'function'
                            ? (getLangAndUnit().lang || 'en-us')
                            : (getCookieSafe('language') || 'en-us'))
                            .toLowerCase();
                if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

                const I18N = {
                'en-us': { title: 'Warning', msg: 'Downshift angle (degrees) must be less than Target angle', ok: 'OK' },
                'zh-tw': { title: '警告',   msg: '降速點角度(度) 需小於 目標角度',                          ok: '確定' },
                'zh-cn': { title: '警告',   msg: '降速点角度(度) 需小于 目标角度',                          ok: '确定' }
                }[lang];

                // 節流避免同輪彈多次
                if (!window._alertingDSgtTargetAngle_Opt1) {
                window._alertingDSgtTargetAngle_Opt1 = true;
                alertify
                    .alert(I18N.title, I18N.msg, function () {
                    try { dsEl.focus(); dsEl.select?.(); } catch {}
                    window._alertingDSgtTargetAngle_Opt1 = false;
                    })
                    .set('labels', { ok: I18N.ok });
                }

                // 讓整體驗證失敗並記錄錯誤欄位
                isValid = false;
                if (!errorList.includes('StepTorqueDownShift')) errorList.push('StepTorqueDownShift');
            } else {
                // 合法：清掉紅框與 inline
                dsEl.classList.remove('is-invalid');
                const fb = dsEl.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }
            }
        })();




   

        // StepLoAngle < StepHiAngle（相等也擋）
        if (stepOpt === 1 || stepOpt === 2)  {     
            const stepHiAngleEl   = document.getElementById('StepHiAngle');
            const stepLoAngleEl = document.getElementById('StepLoAngle');

            if (stepHiAngleEl && stepLoAngleEl) {
                const stepHiAngle  = parseFloat(stepHiAngleEl.value);
                const stepLoAngle = parseFloat(stepLoAngleEl.value);

                // 語系：先拿 cookie，再做 normalize
                const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
                };
                let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'en-us';
                lang = String(lang).toLowerCase();
                if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

                const i18n = {
                'en-us': { title: 'Warning', msg: 'Lower limit angle (degrees) must be less than upper limit angle', ok: 'OK' },
                'zh-tw': { title: '警告',   msg: '角度下限(度) 需小於 角度上限',            ok: '確定' },
                'zh-cn': { title: '警告',   msg: '角度下限（度）必须小于 角度上限',            ok: '确定' }
                };
                const T = i18n[lang];

                // 驗證
                if (Number.isFinite(stepHiAngle) && Number.isFinite(stepLoAngle) && stepLoAngle >= stepHiAngle) {

                    // 標記錯誤（保留欄位紅框）
                    stepLoAngleEl.classList.add('is-invalid');

                    // ⚠️ 避免重複彈出多次：用 dataset 做節流
                    if (!stepLoAngleEl.dataset.alertShown) {
                        stepLoAngleEl.dataset.alertShown = '1';
                        alertify
                        .alert(T.title, T.msg, function () {
                            // 關閉後把游標帶回欄位
                            stepLoAngleEl.focus();
                            // 允許下一次再次彈窗
                            delete stepLoAngleEl.dataset.alertShown;
                        })
                        .set('labels', { ok: T.ok });
                    }

                    // 既然改用 alert 視窗了，就把原本 invalid-feedback 收起來避免雙重訊息
                    const fb = stepLoAngleEl.nextElementSibling;
                    if (fb?.classList.contains('invalid-feedback')) {
                        fb.innerText = '';
                        fb.classList.remove('d-block');
                        fb.style.display = 'none';
                    }

                    isValid = false;
                    if (!errorList.includes('StepLoAngle')) errorList.push('StepLoAngle');
                    } else {
                        // 通過時清掉錯誤樣式與節流旗標
                        stepLoAngleEl.classList.remove('is-invalid');
                        delete stepLoAngleEl.dataset.alertShown;
                        const fb = stepLoAngleEl.nextElementSibling;
                        if (fb?.classList.contains('invalid-feedback')) {
                            fb.innerText = '';
                            fb.classList.remove('d-block');
                            fb.style.display = 'none';
                        }
                    }
                }
        }


        // StepLoAngle < StepAngle（相等也擋）
        if (StepOption === 1 ) {
            const stepAngleEl   = document.getElementById('StepAngle');
            const stepLoAngleEl = document.getElementById('StepLoAngle');

            if (stepAngleEl && stepLoAngleEl) {
                const stepAngle   = parseFloat(stepAngleEl.value);
                const stepLoAngle = parseFloat(stepLoAngleEl.value);

                // 語系：先拿 cookie，再做 normalize
                const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
                };
                let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'en-us';
                lang = String(lang).toLowerCase();
                if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

                const i18n = {
                'en-us': { title: 'Warning', msg: 'TargetAngle (degrees) is out of range', ok: 'OK' },
                'zh-tw': { title: '警告',   msg: '目標角度(度數)超出範圍',            ok: '確定' },
                'zh-cn': { title: '警告',   msg: '目标角度(度数)超出范围',            ok: '确定' }
                };
                const T = i18n[lang];

                // 驗證
                if (Number.isFinite(stepAngle) && Number.isFinite(stepLoAngle) && stepLoAngle >= stepAngle) {
                // 標記錯誤（保留欄位紅框）
                stepLoAngleEl.classList.add('is-invalid');

                // ⚠️ 避免重複彈出多次：用 dataset 做節流
                if (!stepLoAngleEl.dataset.alertShown) {
                    stepLoAngleEl.dataset.alertShown = '1';
                    alertify
                    .alert(T.title, T.msg, function () {
                        // 關閉後把游標帶回欄位
                        stepLoAngleEl.focus();
                        // 允許下一次再次彈窗
                        delete stepLoAngleEl.dataset.alertShown;
                    })
                    .set('labels', { ok: T.ok });
                }

                // 既然改用 alert 視窗了，就把原本 invalid-feedback 收起來避免雙重訊息
                const fb = stepLoAngleEl.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }

                isValid = false;
                if (!errorList.includes('StepLoAngle')) errorList.push('StepLoAngle');
                } else {
                // 通過時清掉錯誤樣式與節流旗標
                stepLoAngleEl.classList.remove('is-invalid');
                delete stepLoAngleEl.dataset.alertShown;
                const fb = stepLoAngleEl.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }
                }
            }
        }

        // === 角度下限 < 上限（僅 StepOption == 1；彈跳視窗＋只標紅 StepAngle） ===
        (function angleLessThanHiCheck() {

            let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'en-us';
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

            const TITLE = {
            'zh-tw': '欄位檢查',
            'zh-cn': '字段检查',
            'en-us': 'Validation',
            };
            const MSG = {
            'zh-tw': '目標角度 需小於 角度上限（度）。',
            'zh-cn': '目标角度 需小于 角度上限（度）。',
            'en-us': 'Target Angle must be less than the High Angle.',
            };
            const tt = TITLE[lang] || TITLE['en-us'];
            const mm = MSG[lang]   || MSG['en-us'];

            const elStepOption = document.getElementById('StepOption');
            const elAngle      = document.getElementById('StepAngle');
            const elHiAngle    = document.getElementById('StepHiAngle');
            if (!elStepOption || !elAngle || !elHiAngle) return;

            // 僅 StepOption == 1 檢查
            const stepOpt = parseInt(elStepOption.value ?? '0', 10);
            if (stepOpt !== 1) return;

            const angle = parseFloat(String(elAngle.value).trim());
            const hi    = parseFloat(String(elHiAngle.value).trim());

            // 先清除樣式：只清除/控制 StepAngle；HiAngle 不標紅
            elAngle.classList.remove('is-invalid');
            elHiAngle.classList.remove('is-invalid');

            if (Number.isFinite(angle) && Number.isFinite(hi)) {
            if (!(angle < hi)) {
            // 只標紅 StepAngle；HiAngle 需移除紅框
            elAngle.classList.add('is-invalid');
            elHiAngle.classList.remove('is-invalid');

            // 彈跳視窗（關閉後聚焦到 StepAngle）
            if (typeof alertify !== 'undefined' && alertify?.alert) {
                alertify.alert(mm, function () {
                try { elAngle.focus({ preventScroll: false }); } catch (_) {}
                });
            } else {
                //lert(mm);
                try { elAngle.focus({ preventScroll: false }); } catch (_) {}
            }

            // 阻止送出
            throw new Error('__BLOCK_SUBMIT__');
            }
            }
        })();




        (function enforceStepOption1PercentRequiredWithDialog() {
            const stepOpt = parseInt(document.getElementById('StepOption')?.value ?? 0, 10);
            if (stepOpt !== 1) return;

            const fields = [
                { id: 'step_limit_hi_ang', key: 'hi' },
                { id: 'step_limit_lo_ang', key: 'lo' }
            ];

            const isEditable = (el) => el && !el.disabled && getComputedStyle(el).display !== 'none';

            // === 新增：語系偵測 + I18N + 取 T（修正 T 未定義） ===
            function getLangSafe() {
                try {
                if (typeof getLangAndUnit === 'function') {
                    let l = (getLangAndUnit().lang || '').toLowerCase();
                    if (l === 'en') l = 'en-us';
                    return ['en-us','zh-tw','zh-cn'].includes(l) ? l : 'en-us';
                }
                } catch {}
                try {
                const m = document.cookie.match(/(?:^|; )language=([^;]+)/);
                let l = (m ? decodeURIComponent(m[1]) : 'en-us').toLowerCase();
                if (l === 'en') l = 'en-us';
                return ['en-us','zh-tw','zh-cn'].includes(l) ? l : 'en-us';
                } catch {}
                return 'en-us';
            }

            const I18N = {
                'en-us': {
                title: 'Warning',
                ok: 'OK',
                msg: {
                    hi: 'Please enter (Upper limit) % — required, integer 0–99',
                    lo: 'Please enter (Lower limit) % — required, integer 0–99'
                }
                },
                'zh-tw': {
                title: '警告',
                ok: '確定',
                msg: {
                    hi: '請輸入：(上限)％ — 必填，0~99 的整數',
                    lo: '請輸入：(下限)％ — 必填，0~99 的整數'
                }
                },
                'zh-cn': {
                title: '警告',
                ok: '确定',
                msg: {
                    hi: '请输入：(上限)％ — 必填，0~99 的整数',
                    lo: '请输入：(下限)％ — 必填，0~99 的整数'
                }
                }
            };

            const lang = getLangSafe();
            const T = I18N[lang] || I18N['en-us'];
            // === 以上三塊缺一不可 ===

            fields.forEach(({ id, key }) => {
                const el = document.getElementById(id);
                if (!isEditable(el)) return; // 不可編輯就不驗

                el.setAttribute('min', '0');
                el.setAttribute('max', '99');
                el.setAttribute('inputmode', 'numeric');

                const raw = (el.value || '').trim();
                const n = Number(raw);
                const isInt0to99 = Number.isInteger(n) && n >= 0 && n <= 99 && /^(?:[0-9]|[1-9][0-9])$/.test(raw);
                const bad = (raw === '' || !isInt0to99);

                if (bad) {
                el.classList.add('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }

                // （可選）改用每欄位去重，避免兩個欄位同時錯只彈一次
                if (!window.__alertOnce) window.__alertOnce = {};
                const keyOnce = 'opt1_percent_' + id;
                if (!window.__alertOnce[keyOnce]) {
                    window.__alertOnce[keyOnce] = true;
                    alertify
                    .alert(T.title, T.msg[key], function () {
                        try { el.focus(); el.select?.(); } catch {}
                        window.__alertOnce[keyOnce] = false;
                    })
                    .set('labels', { ok: T.ok });
                }

                isValid = false;
                if (!errorList.includes(id)) errorList.push(id);
                } else {
                el.classList.remove('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
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

       



        // ---- 交叉驗證：StepOption==2 && StepEnableDownShift==2 時，StepTorqueDownShift 必須小於 StepTorque ----
        (function enforceDownshiftLessThanTorqueForOption2() {
            if (StepOption !== 2 || StepEnableDownShift !== "2") return;

            const dsEl = document.getElementById('StepTorqueDownShift');
            const tqEl = document.getElementById('StepTorque');
            if (!dsEl || !tqEl) return;

            const dsVal = Number(dsEl.value);
            const tqVal = Number(tqEl.value);
            const fb = dsEl.nextElementSibling;

            // 讀 cookie 語系
            const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch (e) { return null; }
            };
            const lang = getCookieSafe("lang") || "en";

            // 多語系訊息
            const msgMap = {
                'en-us': "StepTorqueDownShift must be less than Target Torque",
                'zh-tw': "降速扭力 必須小於 目標扭力",
                'zh-cn': "降速扭力 必须小于 目标扭力"
            };
            const msg = msgMap[lang] ?? msgMap.en;

            if (Number.isFinite(dsVal) && Number.isFinite(tqVal)) {
                if (!(dsVal < tqVal)) {
                    dsEl.classList.add("is-invalid");
                    
                    // ⚠️ 改成 alertify.alert 視窗
                    alertify.alert("Validation Error", msg);

                    isValid = false;
                    errorList.push('StepTorqueDownShift');
                } else {
                    dsEl.classList.remove("is-invalid");
                }
            }
        })();


        // ---- 交叉驗證：StepOption==2 && StepEnableThreshold==2 → StepTorqueTS < StepTorque（alertify + i18n + 單位）----
        (function enforceThresholdLessThanTorqueForOption2() {
        if (StepOption !== 2 || StepEnableThreshold !== "2") return;

        const tsEl = document.getElementById('StepTorqueTS'); // Threshold
        const tqEl = document.getElementById('StepTorque');   // Target torque
        if (!tsEl || !tqEl) return;

        const tsValRaw = Number(tsEl.value);
        const tqValRaw = Number(tqEl.value);
        if (!Number.isFinite(tsValRaw) || !Number.isFinite(tqValRaw)) return;

        // 使用既有 roundTo；無則 fallback
        const _roundTo = (typeof roundTo === 'function')
            ? roundTo
            : (num, digits = 3) => {
                const n = Number(num);
                return isNaN(n) ? NaN : parseFloat(n.toFixed(digits));
            };

        const tsVal = _roundTo(tsValRaw, precision);
        const tqVal = _roundTo(tqValRaw, precision);

        if (tsVal < tqVal) {
            // 合法 → 清掉 inline 錯誤
            tsEl.classList.remove("is-invalid");
            const fb = tsEl.nextElementSibling;
            if (fb?.classList.contains("invalid-feedback")) {
            fb.innerText = '';
            fb.classList.remove("d-block");
            fb.style.display = "none";
            }
            return;
        }

        // 不合法：標紅並清掉 inline（改用彈窗）
        tsEl.classList.add("is-invalid");
        const fb = tsEl.nextElementSibling;
        if (fb?.classList.contains("invalid-feedback")) {
            fb.innerText = '';
            fb.classList.remove("d-block");
            fb.style.display = "none";
        }

        // ✅ 用共用函式取得語系與單位
        const { lang, unit } = getLangAndUnit();

        const I18N = {
            'en-us': {
            title: 'Warning',
            msg: `Threshold torque (${unit}) must be less than the target torque`
            },
            'zh-tw': {
            title: '警告',
            msg: `門檻點扭力（${unit}）需小於 目標扭力`
            },
            'zh-cn': {
            title: '警告',
            msg: `门槛点扭力（${unit}）需小于 目标扭力`
            }
        };
        const T = I18N[lang] || I18N['en-us'];
        const OK_LABEL = (lang === 'en-us' ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定'));

        // 避免同一時間重複彈出
        if (window._alertingTSvsTQ) {
            isValid = false;
            if (!errorList.includes('StepTorqueTS')) errorList.push('StepTorqueTS');
            return;
        }
        window._alertingTSvsTQ = true;

        alertify
            .alert(T.title, T.msg, function () {
            try { tsEl.focus(); tsEl.select?.(); } catch {}
            window._alertingTSvsTQ = false;
            })
            .set('labels', { ok: OK_LABEL });

        isValid = false;
        if (!errorList.includes('StepTorqueTS')) errorList.push('StepTorqueTS');
        })();


        // ---- 交叉驗證：StepOption==2 && StepEnableThreshold==1 → StepTorqueTS(角度) < StepHiAngle（彈窗 + 語系，且不自動變 .000）----
        (function enforceThresholdLessThanHiAngleForOption2() {
            if (StepOption !== 2 || StepEnableThreshold !== "1") return;

            const tsEl    = document.getElementById('StepTorqueTS');   // 門檻點「角度」
            const hiAngEl = document.getElementById('StepHiAngle');    // 角度上限
            if (!tsEl || !hiAngEl) return;

            // === 將 TS 明確鎖為「整數角度欄位」，避免被全域小數格式器改成 .000 ===
            tsEl.setAttribute('step', '1');
            tsEl.setAttribute('inputmode', 'numeric');
            tsEl.setAttribute('pattern', '\\d+');
            tsEl.dataset.allowDecimals = '0';
            tsEl.dataset.storePrecision = '0';
            // 若你的全域 formatter 會看這些 data- 屬性來決定是否 toFixed，可同步清掉：
            tsEl.removeAttribute('data-allow-decimals');
            tsEl.removeAttribute('data-store-precision');

            // 只綁一次：失焦就把小數砍掉，避免出現 50000.000
            if (!tsEl.dataset.intSanitizerBound) {
                tsEl.addEventListener('blur', () => {
                const raw = tsEl.value.trim();
                if (raw === '') return;
                const n = Number(raw.replace(/[^\d.-]/g, ''));
                if (Number.isFinite(n)) tsEl.value = String(Math.trunc(n));
                });
                tsEl.dataset.intSanitizerBound = '1';
            }

            // 讀值：若包含小數，立即轉成整數字串（避免後續其他程式把它 toFixed）
            const rawTS = (tsEl.value || '').trim();
            if (/[.,]/.test(rawTS)) {
                tsEl.value = rawTS.split(/[.,]/)[0]; // 直接保留整數部分
            }

            const tsValRaw = Number(tsEl.value);
            const hiAngVal = Number(hiAngEl.value);
            if (!Number.isFinite(tsValRaw) || !Number.isFinite(hiAngVal)) return;

            // 角度用整數比較
            const tsVal = Math.trunc(tsValRaw);
            const hiVal = Math.trunc(hiAngVal);

            if (tsVal < hiVal) {
                // 合法：清除殘留樣式
                tsEl.classList.remove('is-invalid');
                const fb = tsEl.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }
                // 確保欄位保持整數字串
                tsEl.value = String(tsVal);
                return;
            }

            // ❌ 不合法：改用彈窗提示，且把顯示值固定為整數，不要 .000
            tsEl.classList.add('is-invalid');
            tsEl.value = String(tsVal); // ← 關鍵：保持整數外觀

            const fb = tsEl.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
            }

            // 取得語系
            let lang = (typeof getLangAndUnit === 'function'
                ? getLangAndUnit().lang
                : (function () {
                    const getCookieSafe = (name) => {
                    try {
                        const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                        return m ? decodeURIComponent(m[1]) : null;
                    } catch { return null; }
                    };
                    let l = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
                    l = String(l).toLowerCase();
                    if (l === 'en') l = 'en-us';
                    return ['en-us','zh-tw','zh-cn'].includes(l) ? l : 'en-us';
                })()
            );

            const I18N = {
                'en-us': { title: 'Warning', msg: 'Threshold angle (degrees) must be less than upper angle', ok: 'OK' },
                'zh-tw': { title: '警告',   msg: '門檻點角度(度) 需小於角度上限',                     ok: '確定' },
                'zh-cn': { title: '警告',   msg: '门槛点角度(度) 需小于角度上限',                     ok: '确定' }
            };
            const T = I18N[lang] || I18N['en-us'];

            if (!window._alertingTSltHiAngle) {
                window._alertingTSltHiAngle = true;
                alertify
                .alert(T.title, T.msg, function () {
                    window._alertingTSltHiAngle = false;
                    try { tsEl.focus(); tsEl.select?.(); } catch {}
                })
                .set('labels', { ok: T.ok });
            }

            // 回傳結果
            isValid = false;
            if (!errorList.includes('StepTorqueTS')) errorList.push('StepTorqueTS');
        })();




        // ---- 交叉驗證：StepOption==2 && StepEnableDownShift==1 → StepTorqueDownShift < StepHiAngle（彈窗 + 語系）----
        (function enforceDownshiftLessThanHiAngleForOption2() {
            if (StepOption !== 2 || StepEnableDownShift !== "1") return;

            const dsEl    = document.getElementById('StepTorqueDownShift'); // 「降速點角度」放在這個欄位
            const hiAngEl = document.getElementById('StepHiAngle');         // 角度上限
            if (!dsEl || !hiAngEl) return;

            const dsVal    = Number(dsEl.value);
            const hiAngVal = Number(hiAngEl.value);
            if (!Number.isFinite(dsVal) || !Number.isFinite(hiAngVal)) return;

            // 合法就清掉殘留錯誤
            if (dsVal < hiAngVal) {
                dsEl.classList.remove("is-invalid");
                const fb = dsEl.nextElementSibling;
                if (fb?.classList.contains("invalid-feedback")) {
                fb.innerText = '';
                fb.classList.remove("d-block");
                fb.style.display = "none";
                }
                return;
            }

            // 不合法：標紅並關掉 inline 提示（改用彈窗）
            dsEl.classList.add("is-invalid");
            const fb = dsEl.nextElementSibling;
            if (fb?.classList.contains("invalid-feedback")) {
                fb.innerText = '';
                fb.classList.remove("d-block");
                fb.style.display = "none";
            }

            // 語系
            function getCookieSafe(name){
                try {
                const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            }
            let lang = (typeof getLangAndUnit === 'function'
                            ? (getLangAndUnit().lang || 'en-us')
                            : (getCookieSafe('language') || 'en-us'))
                        .toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

            const I18N = {
                'en-us': { title: 'Warning', ok: 'OK',   msg: 'Downshift angle (degrees) must be less than upper angle' },
                'zh-tw': { title: '警告',     ok: '確定', msg: '降速點角度(度) 需小於角度上限' },
                'zh-cn': { title: '警告',     ok: '确定', msg: '降速点角度(度) 需小于角度上限' }
            };
            const T = I18N[lang];

            // 避免同一次驗證彈出多個視窗
            if (window._alertingDSltHiAngleOpt2) {
                if (!errorList.includes('StepTorqueDownShift')) errorList.push('StepTorqueDownShift');
                isValid = false;
                return;
            }
            window._alertingDSltHiAngleOpt2 = true;

            alertify
                .alert(T.title, T.msg, function () {
                try { dsEl.focus(); dsEl.select?.(); } catch {}
                window._alertingDSltHiAngleOpt2 = false;
                })
                .set('labels', { ok: T.ok });

            if (!errorList.includes('StepTorqueDownShift')) errorList.push('StepTorqueDownShift');
            isValid = false;
        })();



        // ---- StepRPMDownShift 用彈跳視窗（含語系與允許範圍）----
        (function enforceStepRPMDownShiftWithDialog() {
            // 若未啟用 DownShift 就略過
            if (StepEnableDownShift === "0") return;

            const el = document.getElementById('StepRPMDownShift');
            if (!el) return;

            const raw = el.value.trim();
            const n = Number(raw);

            // 範圍：你前面 limits.torque.rpmDownshift_1 = { min: Tool_Min_RPM, max: rpm_check }
            const minR = Number(Tool_Min_RPM);
            const maxR = Number(rpm_check);

            // 關掉 inline 的錯誤提示，我們改用彈窗
            const hideInline = (el) => {
                const fb = el.nextElementSibling;
                el.classList.remove('is-invalid');
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }
            };

            // 取語系（沿用你現有做法）
            const getCookieSafe = (name) => {
                try {
                const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

            const I18N = {
                'en-us': { title: 'Warning', msg: 'Downshift RPM is out of range ({RANGE})', ok: 'OK' },
                'zh-tw': { title: '警告',   msg: '降速點轉速 超出範圍 ({RANGE})',           ok: '確定' },
                'zh-cn': { title: '警告',   msg: '降速点转速 超出范围({RANGE})',           ok: '确定' }
            };
            const T = I18N[lang];

            // 計算顯示用範圍字串（皆有 → "min ~ max"；僅一邊有 → "≥ min"/"≤ max"）
            let rangeStr = 'N/A';
            const haveMin = Number.isFinite(minR);
            const haveMax = Number.isFinite(maxR);
            if (haveMin && haveMax) rangeStr = `${parseInt(minR,10)} ~ ${parseInt(maxR,10)}`;
            else if (haveMin)        rangeStr = `≥ ${parseInt(minR,10)}`;
            else if (haveMax)        rangeStr = `≤ ${parseInt(maxR,10)}`;

            // 驗證規則：1~4 位數整數 + 範圍
            const pattern = /^\d{1,4}$/;
            const badFormat = !pattern.test(raw) || !Number.isInteger(n);
            const badRange  =
                (haveMin && Number.isFinite(n) && n < minR) ||
                (haveMax && Number.isFinite(n) && n > maxR);

            if (raw === '' || !Number.isFinite(n) || badFormat || badRange) {
                // 標紅，但不顯示 inline
                el.classList.add('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }

                // 避免同一輪重複彈窗
                if (window._alertingRPMDownShift) {
                isValid = false;
                if (!errorList.includes('StepRPMDownShift')) errorList.push('StepRPMDownShift');
                return;
                }
                window._alertingRPMDownShift = true;

                alertify
                .alert(T.title, T.msg.replace('{RANGE}', rangeStr), function () {
                    try { el.focus(); el.select?.(); } catch {}
                    window._alertingRPMDownShift = false;
                })
                .set('labels', { ok: T.ok });

                isValid = false;
                if (!errorList.includes('StepRPMDownShift')) errorList.push('StepRPMDownShift');
            } else {
                // 合法就清掉任何殘留的 inline 樣式
                hideInline(el);
            }
        })();


       
        // ---- StepOption==2：扭力百分比欄位為「必填」，錯誤用彈窗 + 語系 ----
        (function enforceStepOption2PercentRequired() {
        const stepOpt = parseInt(document.getElementById("StepOption")?.value ?? 0, 10);
        if (stepOpt !== 2) return;

        const fields = [
            { id: 'step_limit_hi_tor', key: 'hi' }, // 上限 %
            { id: 'step_limit_lo_tor', key: 'lo' }  // 下限 %
        ];

        // 取語系（優先用共用函式，否則 cookie fallback）
        let lang = 'zh-tw';
        if (typeof getLangAndUnit === 'function') {
            lang = (getLangAndUnit()?.lang || 'zh-tw').toLowerCase();
        } else {
            try {
            const m = document.cookie.match(/(?:^|; )language=([^;]+)/);
            lang = (m ? decodeURIComponent(m[1]) : 'zh-tw').toLowerCase();
            } catch {}
        }
        if (lang === 'en') lang = 'en-us';
        if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

        const I18N = {
            'en-us': {
            title: 'Warning',
            ok: 'OK',
            msg: {
                hi: 'Please enter (Upper limit) % — integer 0–99',
                lo: 'Please enter (Lower limit) % — integer 0–99'
            }
            },
            'zh-tw': {
            title: '警告',
            ok: '確定',
            msg: {
                hi: '請輸入：(上限)％ — 必須為 0~99 的整數',
                lo: '請輸入：(下限)％ — 必須為 0~99 的整數'
            }
            },
            'zh-cn': {
            title: '警告',
            ok: '确定',
            msg: {
                hi: '请输入：(上限)％ — 必须为 0~99 的整数',
                lo: '请输入：(下限)％ — 必须为 0~99 的整数'
            }
            }
        };
        const T = I18N[lang];

        fields.forEach(({ id, key }) => {
            const el = document.getElementById(id);
            if (!el) return;

            // 基本屬性
            el.setAttribute('min', '0');
            el.setAttribute('max', '99');
            el.setAttribute('inputmode', 'numeric');

            const raw = (el.value || '').trim();
            const n = Number(raw);
            const isInt = Number.isInteger(n);
            const inRange = isInt && n >= 0 && n <= 99 && /^(?:[0-9]|[1-9][0-9])$/.test(raw);

            const bad = (raw === '' || !inRange);

            if (bad) {
            // 標紅但關掉 inline（改用彈窗）
            el.classList.add('is-invalid');
            const fb = el.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
            }

            // 避免同輪多次彈窗
            if (!window._alertingPercentOpt2Req) {
                window._alertingPercentOpt2Req = true;
                alertify
                .alert(T.title, T.msg[key], function () {
                    try { el.focus(); el.select?.(); } catch {}
                    window._alertingPercentOpt2Req = false;
                })
                .set('labels', { ok: T.ok });
            }

            isValid = false;
            if (!errorList.includes(id)) errorList.push(id);
            } else {
            // 合法 → 清錯
            el.classList.remove('is-invalid');
            const fb = el.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
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
                'zh-cn': { lessThanTq: '必须小于目标扭力' },
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


        // ---- 交叉驗證：StepOption==2 時，StepLoTorque 必須小於 StepHiTorque（用 alertify.alert）----
        (function enforceLoTorqueLessThanHiTorqueForOption2() {
        if (StepOption !== 2) return;

        const loEl = document.getElementById('StepLoTorque');
        const hiEl = document.getElementById('StepHiTorque');
        if (!loEl || !hiEl) return;

        const lo = Number(loEl.value);
        const hi = Number(hiEl.value);
        if (!Number.isFinite(lo) || !Number.isFinite(hi)) return;

        // 依 precision 四捨五入後比較（precision/roundTo 已在外層定義）
        const loRounded = roundTo(lo, precision);
        const hiRounded = roundTo(hi, precision);

        if (loRounded < hiRounded) {
            // 合法：清除錯誤樣式 & inline 訊息
            loEl.classList.remove('is-invalid');
            const fb = loEl.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
            }
            return;
        }

        // 不合法：標紅
        loEl.classList.add('is-invalid');

        // 語系（cookie: language）
        const getCookieSafe = (name) => {
            try {
            const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
            return m ? decodeURIComponent(m[1]) : null;
            } catch { return null; }
        };
        let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
        lang = String(lang).toLowerCase();
        if (lang === 'en') lang = 'en-us';
        if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

        const { lang: curLang, unit } = getLangAndUnit();

        const I18N = {
            'en-us': { title: 'Warning', msg: 'Torque lower limit must be less than upper limit' },
            'zh-tw': { title: '警告',   msg: '扭力下限 要小於 扭力上限' },
            'zh-cn': { title: '警告',   msg: '扭力下限 要小于 扭力上限' }
        };
        const T = I18N[lang] || I18N['en-us'];

        // 關閉 inline 的 invalid-feedback（改用彈窗）
        const fb = loEl.nextElementSibling;
        if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
        }

        // 避免重複彈多個
        if (window._alertingLoLtHi) {
            // 已有彈窗就只記錄錯誤狀態
            isValid = false;
            if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
            return;
        }
        window._alertingLoLtHi = true;

        // 彈窗
        alertify
            .alert(T.title, T.msg, function () {
            // OK 後聚焦欄位
            try { loEl.focus(); loEl.select?.(); } catch {}
            window._alertingLoLtHi = false;
            })
            .set('labels', { ok: (lang === 'en-us' ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定')) });

        // 驗證總結果
        isValid = false;
        if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
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


        

        // ---- 交叉驗證：StepOption==2 時，StepLoTorque 必須小於 StepHiTorque（用 alertify.alert）----
        (function enforceLoTorqueLessThanHiTorqueForOption2() {
        if (StepOption !== 2) return;

        const loEl = document.getElementById('StepLoTorque');
        const hiEl = document.getElementById('StepHiTorque');
        if (!loEl || !hiEl) return;

        const lo = Number(loEl.value);
        const hi = Number(hiEl.value);
        if (!Number.isFinite(lo) || !Number.isFinite(hi)) return;

        // 依 precision 四捨五入後比較（precision/roundTo 已在外層定義）
        const loRounded = roundTo(lo, precision);
        const hiRounded = roundTo(hi, precision);

        if (loRounded < hiRounded) {
            // 合法：清除錯誤樣式 & inline 訊息
            loEl.classList.remove('is-invalid');
            const fb = loEl.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
            }
            return;
        }

        // 不合法：標紅
        loEl.classList.add('is-invalid');

        // 語系（cookie: language）
        const getCookieSafe = (name) => {
            try {
            const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
            return m ? decodeURIComponent(m[1]) : null;
            } catch { return null; }
        };
        let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
        lang = String(lang).toLowerCase();
        if (lang === 'en') lang = 'en-us';
        if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

        const I18N = {
            'en-us': { title: 'Warning', msg: 'Torque lower limit must be less than upper limit' },
            'zh-tw': { title: '警告',   msg: '扭力下限 要小於 扭力上限' },
            'zh-cn': { title: '警告',   msg: '扭力下限 要小于 扭力上限' }
        };
        const T = I18N[lang] || I18N['en-us'];

        // 關閉 inline 的 invalid-feedback（改用彈窗）
        const fb = loEl.nextElementSibling;
        if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
        }

        // 避免重複彈多個
        if (window._alertingLoLtHi) {
            // 已有彈窗就只記錄錯誤狀態
            isValid = false;
            if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
            return;
        }
        window._alertingLoLtHi = true;

        // 彈窗
        alertify
            .alert(T.title, T.msg, function () {
            // OK 後聚焦欄位
            try { loEl.focus(); loEl.select?.(); } catch {}
            window._alertingLoLtHi = false;
            })
            .set('labels', { ok: (lang === 'en-us' ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定')) });

        // 驗證總結果
        isValid = false;
        if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
        })();


        // ---- 交叉驗證（更新版）：StepOption==2 → 先檢 Spec(下限=一格, 上限=SpecHi)，再檢 (Lo,Hi) 區間；訊息會依單位補零 ----
        (function enforceTargetTorqueInRangeForOption2() {
            try {
                const StepOption = Number(document.getElementById('StepOption')?.value) || 0;
                if (StepOption !== 2) return;

                const tqEl     = document.getElementById('StepTorque');
                const loEl     = document.getElementById('StepLoTorque');
                const hiEl     = document.getElementById('StepHiTorque');
                const specHiEl = document.getElementById('check_target_tor_hi'); // Spec 上限
                if (!tqEl || !loEl || !hiEl || !specHiEl) return;

                const tq     = Number(tqEl.value);
                const lo     = Number(loEl.value);
                const hi     = Number(hiEl.value);
                const specHi = Number(specHiEl.value);
                if (![tq, lo, hi, specHi].every(Number.isFinite)) return;

                // === 語系/單位 ===
                const lu  = (typeof getLangAndUnit === 'function') ? getLangAndUnit() : { lang: 'zh-cn', unit: 'N·m' };
                let lang  = String(lu.lang || 'zh-cn').toLowerCase(); if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';
                const unit = lu.unit || 'N·m';
                const SEP  = (lang === 'en-us') ? '~' : '～';

                // === 依單位決定顯示精度與一格 ===
                const unitCodeNow =
                Number(document.getElementById('step_torque_unit')?.value ??
                        window.torque_unit ?? 1);          // 0:kgf·cm, 1:N·m, 2:lbf·in, 3:kgf·m, 4:cN·m

                const dispPrecision =
                unitCodeNow === 4 ? 1 :   // cN·m → 0.1
                unitCodeNow === 3 ? 4 :   // kgf·m → 0.0001
                unitCodeNow === 2 ? 2 :   // lbf·in → 0.01
                unitCodeNow === 0 ? 2 :   // kgf·cm → 0.01
                (typeof window.precision === 'number' && isFinite(window.precision) ? window.precision : 3); // N·m → 0.001

                const pow10    = (p)=> Math.pow(10, p);
                const roundTo  = (n,p)=> Math.round(Number(n) * pow10(p)) / pow10(p);
                const fmtPad   = (n)=> Number(n).toFixed(dispPrecision); // ★ 依單位補零顯示
                const oneTick  = 1 / pow10(dispPrecision);               // ★ 一格（MIN_ALLOWED 用這個）
                const MIN_ALLOWED = oneTick;                              // ★ 隨單位變 (N·m=0.001, lbf·in=0.01, kgf·m=0.0001, cN·m=0.1)

                const tqR     = roundTo(tq,     dispPrecision);
                const loR     = roundTo(lo,     dispPrecision);
                const hiR     = roundTo(hi,     dispPrecision);
                const specHiR = roundTo(specHi, dispPrecision);

                // === I18N ===
                const I18N = {
                'en-us': { title: 'Warning', msg: (r) => `Target torque (${unit}) is out of range, range: ${r}` },
                'zh-tw': { title: '警告',   msg: (r) => `目標扭力（${unit}）超出範圍，範圍：${r}` },
                'zh-cn': { title: '警告',   msg: (r) => `目标扭力（${unit}）超出范围，范围：${r}` },
                }[lang];
                const OK = (lang === 'en-us' ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定'));

                const showAlert = (text, flagName, inputEl) => {
                if (!window[flagName]) {
                    window[flagName] = true;
                    inputEl.classList.add('is-invalid');
                    const fb = inputEl.nextElementSibling;
                    if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                    }
                    try {
                    alertify
                        .alert(I18N.title, text, function () {
                        try { inputEl.focus(); inputEl.select?.(); } catch {}
                        window[flagName] = false;
                        })
                        .set('labels', { ok: OK });
                    } catch {
                    window[flagName] = false;
                    window.alert(I18N.title + '\n' + text);
                    }
                }
                };


                // === 新增：目標扭力必須小於扭力上限（含語系 & 扭力單位） ===
                {
                    const tqEl = document.getElementById('StepTorque');
                    const hiEl = document.getElementById('StepHiTorque');

                    if (tqEl && hiEl) {
                        const tq = Number(tqEl.value);
                        const hi = Number(hiEl.value);

                        if (Number.isFinite(tq) && Number.isFinite(hi) && tq > hi) {

                            // ---- 語系取得 ----
                            const getCookieSafe = (name) => {
                                try { const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
                                catch { return null; }
                            };
                            let lang = getCookieSafe('language') || 'zh-tw';
                            lang = String(lang).toLowerCase();
                            if (lang === 'en') lang = 'en-us';
                            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'zh-tw';

                            // ---- 扭力單位取得 ----
                            const unitCode = Number(document.getElementById('step_torque_unit')?.value ?? window.torque_unit ?? 1);
                            const unit = window.UNIT_LABELS?.[unitCode]?.[lang] || 'N·m';

                            // ---- 語系字串 ----
                            const TITLE = (lang === 'en-us') ? 'Warning' : '警告';
                            const OKTXT = (lang === 'en-us') ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定');

                            const msg = (lang === 'en-us')
                                ? `Target torque (${unit}) must be LESS THAN the upper torque limit.`
                                : (lang === 'zh-cn')
                                    ? `目标扭力（${unit}）需小于扭力上限`
                                    : `目標扭力（${unit}）需小於扭力上限`;

                            // ---- 顯示訊息 ----
                            alertify.alert(TITLE, msg).set('labels', { ok: OKTXT });

                            return false; // 直接終止，不再往下檢查
                        }
                    }
                }


                // === ① 先檢 Spec 範圍： [MIN_ALLOWED, specHiR] ===
                if (tqR < MIN_ALLOWED || tqR > specHiR) {
                // Offset 不要標紅
                const offEl = document.getElementById('StepTorqueOffset');
                if (offEl) {
                    offEl.classList.remove('is-invalid');
                    const fbOff = offEl.nextElementSibling;
                    if (fbOff?.classList.contains('invalid-feedback')) {
                    fbOff.innerText = '';
                    fbOff.classList.remove('d-block');
                    fbOff.style.display = 'none';
                    }
                }

                const rangeStr = `${fmtPad(MIN_ALLOWED)}${SEP}${fmtPad(specHiR)}`;   // 例如 N·m: 0.001～2.400
                showAlert(I18N.msg(rangeStr), '_alertingTargetTorqueSpec', tqEl);

                if (typeof isValid !== 'undefined') isValid = false;
                if (Array.isArray(errorList) && !errorList.includes('StepTorque')) {
                    errorList.push('StepTorque');
                }
                
                return false;
                }

                // === ② 再檢 (lo, hi) 開區間（嚴格不含端點），上限仍不可超過 SpecHi ===
                if (!(tqR > loR && tqR < hiR)) {

                    let loDisplay, hiDisplay;

                    if (tqR === hiR) {
                        // ★ 特例：當 StepTorque == StepHiTorque 時，顯示「顯示刻度 ～ (StepHiTorque − 顯示刻度)」
                        // 例如 N·m：0.001 ～ 0.999；cN·m：0.1 ～ 0.9
                        loDisplay = roundTo(MIN_ALLOWED, dispPrecision);
                        hiDisplay = roundTo(hiR - oneTick, dispPrecision);
                        if (Number.isFinite(specHiR)) hiDisplay = Math.min(hiDisplay, specHiR);
                        if (hiDisplay < loDisplay) hiDisplay = loDisplay;
                    } else {
                        // 原本的開區間顯示（依 StepLoTorque/StepHiTorque 算）
                        loDisplay = roundTo(loR + oneTick, dispPrecision);
                        hiDisplay = Math.min(roundTo(hiR - oneTick, dispPrecision), specHiR);
                        if (hiDisplay < loDisplay) hiDisplay = loDisplay;
                    }

                    const rangeStr2 = `${fmtPad(loDisplay)}${SEP}${fmtPad(hiDisplay)}`;
                    showAlert(I18N.msg(rangeStr2), '_alertingTargetTorqueRange', tqEl);

                    if (typeof isValid !== 'undefined') isValid = false;
                    if (Array.isArray(errorList) && !errorList.includes('StepTorque')) {
                        errorList.push('StepTorque');
                    }
                    return;
                }


                // === 通過：清除錯誤樣式 ===
                tqEl.classList.remove('is-invalid');
                const fbOk = tqEl.nextElementSibling;
                if (fbOk?.classList.contains('invalid-feedback')) {
                fbOk.innerText = '';
                fbOk.classList.remove('d-block');
                fbOk.style.display = 'none';
                }
            } catch (e) {
                console.error('[enforceTargetTorqueInRangeForOption2] error:', e);
            }
        })();




        // ---- 交叉驗證：StepLoTorque < StepHiTorque（alertify + 語系）----
        (function enforceLoLtHiTorqueWithI18nDialog() {
        const loTorqueEl = document.getElementById('StepLoTorque');
        const hiTorqueEl = document.getElementById('StepHiTorque');
        if (!loTorqueEl || !hiTorqueEl) return;

        const lo = Number(loTorqueEl.value);
        const hi = Number(hiTorqueEl.value);
        if (!Number.isFinite(lo) || !Number.isFinite(hi)) return;

        // ✅ 使用共用函式取得語系與（已本地化的）扭力單位字串
        const { lang: clang, unit } = (typeof getLangAndUnit === 'function')
            ? getLangAndUnit()
            : { lang: 'zh-tw', unit: '牛頓·公尺' };

        const I18N = {
            'en-us': { title: 'Warning', msg: `Torque lower limit (${unit}) must be less than upper limit` },
            'zh-tw': { title: '警告',   msg: `扭力下限（${unit}）必須小於 扭力上限` },
            'zh-cn': { title: '警告',   msg: `扭力下限（${unit}）必须小于 扭力上限` },
        };
        const T = I18N[clang] || I18N['en-us'];

        if (lo >= hi) {
                // 只標紅「下限」
                loTorqueEl.classList.add('is-invalid');

                // 徹底清除 StepTorque 的紅框與錯誤訊息
                const tqEl = document.getElementById('StepTorque');
                const clearInvalid = (el) => {
                    if (!el) return;
                    el.classList.remove('is-invalid', 'error', 'border-danger');
                    el.removeAttribute('aria-invalid');
                    el.style.borderColor = '';
                    const fb = el.nextElementSibling;
                    if (fb && fb.classList && (fb.classList.contains('invalid-feedback') || fb.classList.contains('error'))) {
                    fb.textContent = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                    }
                };
                clearInvalid(tqEl);

                // 再保險一次（若後面其他檢核又把它染紅，這個會在本輪 call stack 結束後清掉）
                setTimeout(() => clearInvalid(tqEl), 0);

                // 關掉 Lo 的 inline 錯誤文字（改用視窗）
                const feedback = loTorqueEl.nextElementSibling;
                if (feedback?.classList?.contains('invalid-feedback')) {
                    feedback.innerText = '';
                    feedback.classList.remove('d-block');
                    feedback.style.display = 'none';
                }

                // 彈出訊息（節流避免連彈）
                if (!window.__alertifyBusy) {
                    window.__alertifyBusy = true;
                    alertify.alert(T.title, T.msg, function () {
                    window.__alertifyBusy = false;
                    try { loTorqueEl.focus(); loTorqueEl.select?.(); } catch {}
                    });
                }

                // 記錄錯誤：只記 Lo
                isValid = false;
                if (!Array.isArray(errorList)) errorList = [];
                if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
                const idxTq = errorList.indexOf('StepTorque');
                if (idxTq !== -1) errorList.splice(idxTq, 1);
            } else {
                // 通過則清掉 Lo 紅框與 inline 錯誤
                loTorqueEl.classList.remove('is-invalid');
                const feedback = loTorqueEl.nextElementSibling;
                if (feedback?.classList?.contains('invalid-feedback')) {
                    feedback.innerText = '';
                    feedback.classList.remove('d-block');
                    feedback.style.display = 'none';
                }
                // 從 errorList 移除 Lo
                if (Array.isArray(errorList)) {
                    const i = errorList.indexOf('StepLoTorque');
                    if (i !== -1) errorList.splice(i, 1);
                }
            }


        })();



        // ---- 交叉驗證：StepOption==2；StepTorque 必須小於 StepHiTorque（錯誤標在 StepTorque + alertify + 語系；提示範圍用單位顯示刻度）----
        (function enforceTargetLessThanHiTorqueForOption2() {
        try {
            if (StepOption !== 2) return;

            const tqEl = document.getElementById('StepTorque');
            const hiEl = document.getElementById('StepHiTorque');
            if (!tqEl || !hiEl) return;

            const tqVal = Number(tqEl.value);
            const hiVal = Number(hiEl.value);
            if (!Number.isFinite(tqVal) || !Number.isFinite(hiVal)) return;

            // 內部比較用的 round/precision
            const _roundTo = (typeof roundTo === 'function')
            ? roundTo
            : (n, d = 3) => { const v = Number(n); if (!isFinite(v)) return NaN; const f = Math.pow(10, d); return Math.round(v * f) / f; };
            const prec = (typeof precision === 'number' && isFinite(precision)) ? precision : 3;

            const tqRounded = _roundTo(tqVal, prec);
            const hiRounded = _roundTo(hiVal, prec);

            // 清 StepHiTorque 的紅框/inline
            hiEl.classList.remove('is-invalid');
            const hiFb = hiEl.nextElementSibling;
            if (hiFb?.classList.contains('invalid-feedback')) {
            hiFb.innerText = '';
            hiFb.classList.remove('d-block');
            hiFb.style.display = 'none';
            }

            // ✅ 合法（嚴格小於）
            if (tqRounded < hiRounded) {
            tqEl.classList.remove('is-invalid');
            const fb = tqEl.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
            }
            return;
            }

            // ❌ 不通過（等於或大於）：錯誤標在 StepTorque（用彈窗）
            tqEl.classList.add('is-invalid');
            const fb = tqEl.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
            }

            // ── 語系 & 單位 ──────────────────────────────────────────────
            const getCookieSafe = (name) => {
            try {
                const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : null;
            } catch { return null; }
            };
            let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
            lang = String(lang).toLowerCase(); if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

            // 單位文案（可替換為你專案的 UNIT_LABELS）
            const UNIT_TEXT = {
            0: { 'en-us':'kgf·cm', 'zh-tw':'公斤·公分', 'zh-cn':'公斤·公分' },
            1: { 'en-us':'N·m',    'zh-tw':'牛頓·公尺', 'zh-cn':'牛顿·米'   },
            2: { 'en-us':'lbf·in', 'zh-tw':'磅·英吋',   'zh-cn':'磅·英寸'   },
            3: { 'en-us':'kgf·m',  'zh-tw':'公斤·公尺', 'zh-cn':'公斤·米'   },
            4: { 'en-us':'cN·m',   'zh-tw':'牛頓·釐米', 'zh-cn':'牛顿·厘米' },
            };
            const tu = Number(window.torque_unit ?? document.getElementById('step_torque_unit')?.value ?? 1);
            const unitText = (UNIT_TEXT[tu]?.[lang]) ?? 'N·m';

            // === 顯示範圍用「單位的顯示刻度」 ===
            // 1:N·m→0.001、4:cN·m→0.1；其餘依對照（可依你專案調整）
            const DISPLAY_PREC_BY_UNIT = { 0:2, 1:3, 2:2, 3:3, 4:1 };
            const DISPLAY_STEP_BY_UNIT = { 0:0.01, 1:0.001, 2:0.01, 3:0.001, 4:0.1 };

            const dispPrec = DISPLAY_PREC_BY_UNIT[tu] ?? Math.max(0, prec|0);
            const dispStep = DISPLAY_STEP_BY_UNIT[tu] ?? Math.pow(10, -dispPrec);

            // 顯示的下/上界：min=顯示刻度；max=StepHiTorque-顯示刻度（避免等於上限）
            const minDisplay = dispStep;
            let maxDisplay = hiRounded - dispStep;
            if (!isFinite(maxDisplay) || maxDisplay < minDisplay) maxDisplay = minDisplay; // 防呆
            // 對齊顯示刻度（避免 0.899999）
            maxDisplay = Number((Math.round(maxDisplay / dispStep) * dispStep).toFixed(dispPrec));

            const fmtDisplay = (n) => Number(n).toFixed(dispPrec);
            const sep = (lang === 'en-us') ? ' ~ ' : ' ～ ';
            const rangeStr = `${fmtDisplay(minDisplay)}${sep}${fmtDisplay(maxDisplay)}`;

            // 多語訊息
            const i18n = {
            'zh-tw': { title:'警告',   msg:'目標扭力（{unit}）超出範圍，（{range}）', ok:'確定' },
            'zh-cn': { title:'警告',   msg:'目标扭力（{unit}）超出范围，（{range}）', ok:'确定' },
            'en-us': { title:'Warning', msg:'Target torque ({unit}) is out of range ({range})', ok:'OK' },
            }[lang] || { title:'Warning', msg:'Target torque ({unit}) is out of range ({range})', ok:'OK' };

            // 避免重複彈窗
            if (window._alertingTQvsHi) return;
            window._alertingTQvsHi = true;

            if (window.alertify?.alert) {
            alertify
                .alert(i18n.title, i18n.msg.replace('{unit}', unitText).replace('{range}', rangeStr), function () {
                try { tqEl.focus(); tqEl.select?.(); } catch {}
                window._alertingTQvsHi = false;
                })
                .set('labels', { ok: i18n.ok });
            } else {
            alert(`${i18n.title}\n${i18n.msg.replace('{unit}', unitText).replace('{range}', rangeStr)}`);
            window._alertingTQvsHi = false;
            }

            // 全域驗證旗標
            if (typeof isValid !== 'undefined') isValid = false;
            if (Array.isArray(errorList) && !errorList.includes('StepTorque')) errorList.push('StepTorque');
        } catch (err) {
            console.error('[enforceTargetLessThanHiTorqueForOption2] error:', err);
        }
        })();


        // ---- 交叉驗證：StepOption==2 && DownShift==2 → StepTorqueDownShift < StepTorque（alertify + i18n）----
        (function enforceDownshiftLessThanTargetTorque() {
            if (StepOption !== 2) return;
            if (StepEnableDownShift !== "2") return;

            const dsEl = document.getElementById('StepTorqueDownShift'); // 降速扭力
            const tqEl = document.getElementById('StepTorque');          // 目標扭力
            if (!dsEl || !tqEl) return;

            // 1) 先清掉殘留 invalid 狀態（避免第二次擋小數點）
            if (typeof dsEl.setCustomValidity === 'function') dsEl.setCustomValidity('');
            if (typeof tqEl.setCustomValidity === 'function') tqEl.setCustomValidity('');

            const dsValRaw = Number(dsEl.value);
            const tqValRaw = Number(tqEl.value);
            if (!Number.isFinite(dsValRaw) || !Number.isFinite(tqValRaw)) return;

            // 2) 依目前單位精度四捨五入（沿用你的 roundTo / precision）
            const dsVal = roundTo(dsValRaw, precision);
            const tqVal = roundTo(tqValRaw, precision);

            // 3) 收掉 inline 錯誤（使用彈窗）
            const clearInline = (el) => {
                el.classList.remove('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }
            };
            clearInline(dsEl);
            clearInline(tqEl);

            if (dsVal < tqVal) return; // ✅ 合格

            // ❌ 不通過：只標在 StepTorqueDownShift，並確保下一次可輸入小數點
            dsEl.classList.add('is-invalid');

            
            // [ADD] 防止被 number/pattern 鎖成整數，確保下一次可輸入小數點
            dsEl.type = 'text';
            dsEl.removeAttribute('pattern');
            dsEl.setAttribute('inputmode','decimal'); // 可選，讓行動裝置出小數鍵盤




            // 4) 關鍵：取消 invalid 旗標並清空值，避免下一次 '.' 被擋
            if (typeof dsEl.setCustomValidity === 'function') dsEl.setCustomValidity('');
            dsEl.value = '';

            // 5) i18n（沿用你的語系 cookie）
            const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

            const I18N = {
                'en-us': { title: 'Warning', msg: 'Downshift torque must be less than Target torque' },
                'zh-tw': { title: '警告',   msg: '降速扭力 必須小於 目標扭力' },
                'zh-cn': { title: '警告',   msg: '降速扭力 必须小于 目标扭力' }
            };
            const OK = (lang === 'en-us' ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定'));
            const T = I18N[lang] || I18N['en-us'];

            // 6) 避免一次驗證彈多次
            if (window._alertingDSltTQ) {
                isValid = false;
                if (!errorList.includes('StepTorqueDownShift')) errorList.push('StepTorqueDownShift');
                return;
            }
            window._alertingDSltTQ = true;

            // 7) 彈窗後再聚焦，避免與 keydown 過濾時序衝突
            const refocus = () => {
                try { dsEl.focus(); dsEl.select?.(); } catch {}
                window._alertingDSltTQ = false;
            };

            if (typeof alertify !== 'undefined' && alertify?.alert) {
                alertify.alert(T.title, T.msg, refocus).set('labels', { ok: OK });
            } else {
                alert(`${T.title}\n\n${T.msg}`);
                refocus();
            }

            // 8) 回報整體驗證狀態（沿用你的 isValid / errorList）
            isValid = false;
            if (!errorList.includes('StepTorqueDownShift')) errorList.push('StepTorqueDownShift');
        })();


        



        // ---- 交叉驗證：StepOption==1 && Threshold==2 → StepTorqueTS < StepHiTorque（alertify + i18n）----
        (function enforceTSLessThanHiTorqueForOption1() {
            if (StepOption !== 1 || StepEnableThreshold !== "2") return;

            const tsEl = document.getElementById('StepTorqueTS');   // 門檻扭力
            const hiEl = document.getElementById('check_target_tor_hi');   // 扭力上限(起子規格)
            if (!tsEl || !hiEl) return;

            const tsRaw = Number(tsEl.value);
            const hiRaw = Number(hiEl.value);
            if (!Number.isFinite(tsRaw) || !Number.isFinite(hiRaw)) return;

            // 依目前單位精度做四捨五入
            const tsVal = roundTo(tsRaw, precision);
            const hiVal = roundTo(hiRaw, precision);

            // 清除兩者的 inline 錯誤訊息（改用彈窗）
            const clearInline = (el) => {
                el.classList.remove('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }
            };
            clearInline(tsEl);
            clearInline(hiEl);

            if (tsVal < hiVal) return; // OK

            // ❌ 不通過：只標在 StepTorqueTS
            tsEl.classList.add('is-invalid');

            // 語系（cookie: language → 預設 zh-tw；'en' 正規化成 'en-us'）
            const getCookieSafe = (name) => {
                try {
                const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

            const { langd, unit } = getLangAndUnit();
            const I18N = {
                'en-us': { 
                    title: 'Warning', 
                    msg: `Threshold torque (${unit}) must be less than Target Torque`, 
                    ok: 'OK' 
                },
                'zh-tw': { 
                    title: '警告',   
                    msg: `門檻點扭力 (${unit}) 需小於 目標扭力`, 
                    ok: '確定' 
                },
                'zh-cn': { 
                    title: '警告',   
                    msg: `门槛点扭力 (${unit}) 需小于 目标扭力`, 
                    ok: '确定' 
                }
            };
            const T = I18N[lang] || I18N['en-us'];

            // 避免重複彈窗
            if (window._alertingTSltHiTq) {
                isValid = false;
                if (!errorList.includes('StepTorqueTS')) errorList.push('StepTorqueTS');
                return;
            }
            window._alertingTSltHiTq = true;

            alertify
                .alert(T.title, T.msg, function () {
                try { tsEl.focus(); tsEl.select?.(); } catch {}
                window._alertingTSltHiTq = false;
                })
                .set('labels', { ok: T.ok });

            isValid = false;
            if (!errorList.includes('StepTorqueTS')) errorList.push('StepTorqueTS');
        })();


        // ---- StepHiAngle 超出範圍 → 用彈跳視窗（含語系與允許範圍）----
        (function enforceStepHiAngleRangeDialog() {
            const el = document.getElementById('StepHiAngle');
            if (!el) return;

            const raw = (el.value || '').trim();
            const num = Number(raw);

            // 允許範圍（你原本的 limitHi: 1 ~ 30600）
            const minR = 1;
            const maxR = 30600;

            // 只允許 1~5 位整數
            const pattern = /^\d{1,5}$/;
            const badFormat = !pattern.test(raw) || !Number.isInteger(num);
            const badRange  =
                Number.isFinite(num) && (num < minR || num > maxR);

            if (raw === '' || !Number.isFinite(num) || badFormat || badRange) {
                // 標紅 + 關掉 inline 訊息（改用彈窗）
                el.classList.add('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }

                // 語系（共用你的工具函式）
                const { lang } = getLangAndUnit();
                const rangeStr = `${minR} ~ ${maxR}`;

                const I18N = {
                    'en-us': { title: 'Warning', msg: `High angle (degrees) is out of range. Allowed range: ${rangeStr}`, ok: 'OK' },
                    'zh-tw': { title: '警告',   msg: `角度上限(度數) 超出範圍，允許範圍：${rangeStr}`,   ok: '確定' },
                    'zh-cn': { title: '警告',   msg: `角度上限(度数) 超出范围，允许范围：${rangeStr}`,   ok: '确定' }
                };
                const T  = I18N[lang] || I18N['en-us'];
                const OK = T.ok;

                // 節流，避免一次驗證彈多次
                if (window._alertingStepHiAngleRange) {
                    isValid = false;
                    if (!errorList.includes('StepHiAngle')) errorList.push('StepHiAngle');
                    return;
                }
                window._alertingStepHiAngleRange = true;

                alertify
                    .alert(T.title, T.msg, function () {
                        window._alertingStepHiAngleRange = false;
                        try { el.focus(); el.select?.(); } catch {}
                    })
                    .set('labels', { ok: OK });

                isValid = false;
                if (!errorList.includes('StepHiAngle')) errorList.push('StepHiAngle');
            } else {
                // 合法 → 清除錯誤
                el.classList.remove('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }
            }
        })();

        
        // ---- StepDelay 超出範圍 → 用彈跳視窗（含語系與允許範圍）----
        (function enforceStepDelayWithDialog() {
            const el = document.getElementById('StepDelay');
            if (!el) return;

            const raw = (el.value || '').trim();
            const n = Number(raw);

            // 欄位規則
            const minR = 0;
            const maxR = 9.9;
            const prec = 3;
            const pattern = /^(?:[0-9](?:\.\d{1,4})?|9\.9)$/; // 0–9 可到 4 位小數；9.9 為上限

            // 四捨五入 helper（若外部已有 roundTo 就沿用）
            const _roundTo = (typeof roundTo === 'function')
                ? roundTo
                : (num, digits = prec) => {
                    const x = Number(num);
                    if (!Number.isFinite(x)) return NaN;
                    const f = Math.pow(10, digits);
                    return Math.round(x * f) / f;
                };

            const nRounded = _roundTo(n, prec);
            const badFormat = raw === '' || !pattern.test(raw) || !Number.isFinite(n);
            const badRange  = Number.isFinite(nRounded) && (nRounded < minR || nRounded > maxR);

            // 語系（用你的共用 helper）
            const { lang } = (typeof getLangAndUnit === 'function') ? getLangAndUnit() : { lang: 'en-us' };

            const fmt = (v) => Number(v).toFixed(prec);
            const rangeStr = `${fmt(minR)} ~ ${fmt(maxR)}`;

            const I18N = {
                'en-us': { title: 'Warning', msg: `Step delay is out of range. Allowed range: ${rangeStr} s`, ok: 'OK' },
                'zh-tw': { title: '警告',   msg: `步驟延遲超出範圍，範圍：${rangeStr} 秒`,        ok: '確定' },
                'zh-cn': { title: '警告',   msg: `步骤延迟超出范围，范围：${rangeStr} 秒`,        ok: '确定' }
            };
            const T = I18N[lang] || I18N['en-us'];

            if (badFormat || badRange) {
                // 標紅並關掉 inline 提示（改用彈窗）
                el.classList.add('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }

                // 避免重複彈窗
                if (window._alertingStepDelay) {
                    isValid = false;
                    if (!errorList.includes('StepDelay')) errorList.push('StepDelay');
                    return;
                }
                window._alertingStepDelay = true;

                alertify
                    .alert(T.title, T.msg, function () {
                        try { el.focus(); el.select?.(); } catch {}
                        window._alertingStepDelay = false;
                    })
                    .set('labels', { ok: T.ok });

                isValid = false;
                if (!errorList.includes('StepDelay')) errorList.push('StepDelay');
            } else {
                // 合法 → 清除錯誤樣式
                el.classList.remove('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }
            }
        })();



        // ---- 交叉驗證：StepOption==1 且「扭力降速」時，StepTorqueDownShift 必須小於 StepTorque（彈窗 + 語系 + 小數點可再輸入）----
        (function enforceDSTorqueLessThanTarget_WhenOpt1() {
            const stepOpt = parseInt(document.getElementById('StepOption')?.value ?? 0, 10);
            if (stepOpt !== 1) return;

            // "0"=未啟用, "1"=角度降速, "2"=扭力降速
            const dsMode = document.querySelector('input[name="StepEnableDownShift"]:checked')?.value ?? "0";
            if (dsMode !== "2") return;

            const dsEl = document.getElementById('StepTorqueDownShift'); // 降速扭力
            const tqEl = document.getElementById('StepTorque');          // ← 目標扭力（改這裡）
            if (!dsEl || !tqEl) return;

            const dsVal = Number(dsEl.value?.trim() ?? "");
            const tqVal = Number(tqEl.value?.trim() ?? "");
            if (!Number.isFinite(dsVal) || !Number.isFinite(tqVal)) return;

            // 語系
            const getCookieSafe = (name) => { try {
                const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : null;
            } catch { return null; } };
            let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : (getCookieSafe('language') || 'zh-tw')) || 'zh-tw';
            lang = String(lang).toLowerCase(); if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';
            const OK_LABEL = (lang === 'en-us' ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定'));
            const I18N = {
                'en-us': { title: 'Warning', msg: 'Downshift torque must be less than Target torque.' },
                'zh-tw': { title: '警告',   msg: '降速扭力 必須小於 目標扭力。' },
                'zh-cn': { title: '警告',   msg: '降速扭力 必须小于 目标扭力。' }
            }[lang];

            // 通過 → 清錯
            if (dsVal < tqVal) {
                dsEl.classList.remove('is-invalid');
                const fbOK = dsEl.nextElementSibling;
                if (fbOK?.classList.contains('invalid-feedback')) {
                fbOK.innerText = ''; fbOK.classList.remove('d-block'); fbOK.style.display = 'none';
                }
                return;
            }

            // ❌ 不通過：加紅框 + 關掉 inline 提示
            dsEl.classList.add('is-invalid');
            const fb = dsEl.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = ''; fb.classList.remove('d-block'); fb.style.display = 'none';
            }

            // === 關鍵：確保下一次可輸入「.」 ===
            dsEl.type = 'text';                 // 避開 number 的原生限制
            dsEl.removeAttribute('pattern');    // 移除可能的整數正則
            dsEl.setAttribute('inputmode','decimal');

            // 只綁一次捕獲守門與正規化（允許只輸入 '.' 立即顯示）
            if (dsEl.dataset.dotGuardAttached !== '1') {
                const currentPrecision = () => {
                if (typeof window.precision === 'number' && isFinite(window.precision)) return window.precision;
                if (typeof getTorquePrecision === 'function') return Number(getTorquePrecision()) || 3;
                return 3;
                };

                dsEl.addEventListener('keydown', function (e) {
                const isDot = (e.key === '.' || e.key === 'Decimal' || e.key === ',');
                if (!isDot) return;
                const allowDecimal = currentPrecision() > 0;
                if (allowDecimal) {
                    // 放行小數點，但阻斷往上冒泡，避免被全域整數委派攔掉
                    e.stopImmediatePropagation();
                } else {
                    e.preventDefault(); e.stopImmediatePropagation();
                }
                }, true); // capture=true

                dsEl.addEventListener('input', function () {
                const prec = currentPrecision();
                let v = String(this.value ?? '');

                // 允許單獨 '.' 立即顯示
                if (prec > 0 && v === '.') { this.value = '.'; return; }

                // 僅保留數字與一個小數點；限制小數位
                v = v.replace(/[^\d.]/g, '');
                const firstDot = v.indexOf('.');
                if (firstDot !== -1) {
                    v = v.slice(0, firstDot + 1) + v.slice(firstDot + 1).replace(/\./g, '');
                }
                if (prec === 0) {
                    v = v.replace(/\..*$/, '');
                } else if (firstDot !== -1 && v !== '.') {
                    const [ip, dp = ''] = v.split('.');
                    v = ip + '.' + dp.slice(0, prec);
                }
                this.value = v;
                });

                dsEl.addEventListener('paste', function (e) {
                // 放行貼上但阻斷上層委派，避免被清掉小數點
                e.stopImmediatePropagation();
                }, true);

                dsEl.dataset.dotGuardAttached = '1';
            }
            // === 關鍵處理結束 ===

            // 節流避免重複彈窗
            if (!window._alertingDSTorqueVsTarget_Opt1) {
                window._alertingDSTorqueVsTarget_Opt1 = true;
                if (typeof alertify !== 'undefined' && alertify?.alert) {
                alertify.alert(I18N.title, I18N.msg, function () {
                    try { dsEl.focus(); dsEl.select?.(); } catch {}
                    window._alertingDSTorqueVsTarget_Opt1 = false;
                }).set('labels', { ok: OK_LABEL });
                } else {
                alert(`${I18N.title}\n\n${I18N.msg}`);
                try { dsEl.focus(); dsEl.select?.(); } catch {}
                window._alertingDSTorqueVsTarget_Opt1 = false;
                }
            }

            // 阻擋送出
            isValid = false;
            if (!errorList.includes('StepTorqueDownShift')) errorList.push('StepTorqueDownShift');
        })();








        // ---- 交叉驗證 A：StepOption==1 且 DownShift==1 → StepTorqueDownShift < StepAngle（alertify + i18n）----
        (function enforceDSltTargetAngle_Opt1_DS1() {
            if (StepOption !== 1 || StepEnableDownShift !== "1") return;

            const dsEl  = document.getElementById('StepTorqueDownShift'); // 降速點角度（放在這欄）
            const tgtEl = document.getElementById('StepAngle');           // 目標角度
            if (!dsEl || !tgtEl) return;

            const dsInt  = Math.floor(Number(dsEl.value));
            const tgtInt = Math.floor(Number(tgtEl.value));
            if (!Number.isFinite(dsInt) || !Number.isFinite(tgtInt)) return;

            // 關掉欄位旁的紅字（改用彈窗）
            const fb = dsEl.nextElementSibling;
            dsEl.classList.remove('is-invalid');
            if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
            }

            // 依你的敘述：觸發條件是 ds > StepAngle；若也要擋等於，改成 dsInt >= tgtInt
            if (dsInt > tgtInt) {
                // i18n
                function getCookieSafe(name){ try{ const m = document.cookie.match(new RegExp('(?:^|; )'+name+'=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; } catch { return null; } }
                let lang = (typeof getLangAndUnit === 'function' ? (getLangAndUnit().lang || 'en-us') : (getCookieSafe('language') || 'en-us')).toLowerCase();
                if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

                const I18N = {
                'en-us': { title: 'Warning', msg: 'Downshift angle (degrees) must be less than Target angle', ok: 'OK' },
                'zh-tw': { title: '警告',   msg: '降速點角度(度) 需小於 目標角度',                           ok: '確定' },
                'zh-cn': { title: '警告',   msg: '降速点角度(度) 需小于 目标角度',                           ok: '确定' }
                }[lang];

                if (!window._alertingDSgtTargetAngle_Opt1) {
                window._alertingDSgtTargetAngle_Opt1 = true;
                alertify
                    .alert(I18N.title, I18N.msg, function () {
                    try { dsEl.focus(); dsEl.select?.(); } catch {}
                    window._alertingDSgtTargetAngle_Opt1 = false;
                    })
                    .set('labels', { ok: I18N.ok });
                }

                dsEl.classList.add('is-invalid');
                isValid = false;
                if (!errorList.includes('StepTorqueDownShift')) errorList.push('StepTorqueDownShift');
            }
        })();


        // ---- 交叉驗證 B：StepOption==2 且 DownShift==1 → StepTorqueDownShift < StepHiAngle（alertify + i18n）----
        (function enforceDSltHiAngle_Opt2_DS1() {
            if (StepOption !== 2 || StepEnableDownShift !== "1") return;

            const dsEl  = document.getElementById('StepTorqueDownShift'); // 降速點角度（放在這欄）
            const hiEl  = document.getElementById('StepHiAngle');         // 角度上限
            if (!dsEl || !hiEl) return;

            const dsInt = Math.floor(Number(dsEl.value));
            const hiInt = Math.floor(Number(hiEl.value));
            if (!Number.isFinite(dsInt) || !Number.isFinite(hiInt)) return;

            // 關掉欄位旁的紅字（改用彈窗）
            const fb = dsEl.nextElementSibling;
            dsEl.classList.remove('is-invalid');
            if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
            }

            if (!(dsInt < hiInt)) {
                // i18n
                function getCookieSafe(name){ try{ const m = document.cookie.match(new RegExp('(?:^|; )'+name+'=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; } catch { return null; } }
                let lang = (typeof getLangAndUnit === 'function' ? (getLangAndUnit().lang || 'en-us') : (getCookieSafe('language') || 'en-us')).toLowerCase();
                if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

                const I18N = {
                'en-us': { title: 'Warning', msg: 'Downshift angle (degrees) must be less than the upper angle limit', ok: 'OK' },
                'zh-tw': { title: '警告',   msg: '降速點角度(度) 需小於 角度上限',                                ok: '確定' },
                'zh-cn': { title: '警告',   msg: '降速点角度(度) 需小于 角度上限',                                ok: '确定' }
                }[lang];

                if (!window._alertingDSltAngle_Opt2) {
                window._alertingDSltAngle_Opt2 = true;
                alertify
                    .alert(I18N.title, I18N.msg, function () {
                    try { dsEl.focus(); dsEl.select?.(); } catch {}
                    window._alertingDSltAngle_Opt2 = false;
                    })
                    .set('labels', { ok: I18N.ok });
                }

                dsEl.classList.add('is-invalid');
                isValid = false;
                if (!errorList.includes('StepTorqueDownShift')) errorList.push('StepTorqueDownShift');
            }
        })();

        // === [ADD] StepOption==1 時，檢查：StepHiTorque 必須 >= 工具下限（check_target_tor_lo） ===
        (function enforceHiTorqueWithinToolRangeForOption1() {
            try {
                const stepOpt = Number(document.getElementById('StepOption')?.value || 0);
                if (stepOpt !== 1) return;  // 只在 StepOption==1 檢核

                const hiEl   = document.getElementById('StepHiTorque');
                const loEl   = document.getElementById('check_target_tor_lo');      // 顯示/比較用
                const maxEl  = document.getElementById('tool_max_torque_diff');     // 顯示用（右界）

                if (!hiEl || !loEl) return;

                const hiVal = Number(hiEl.value);
                const loVal = Number(loEl.value);
                if (!Number.isFinite(hiVal) || !Number.isFinite(loVal)) return;

                // 觸發條件：上限 < 工具下限
                if (hiVal >= loVal) return;

                // UI：把 StepHiTorque 標紅，關閉 inline feedback（改用彈窗）
                hiEl.classList.add('is-invalid');
                const fb = hiEl.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }

                // 取得語系
                const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
                };
                let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
                lang = String(lang).toLowerCase();
                if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

                // 保留你原本輸入框的小數格式（直接拿 DOM 原字串，含補零）
                const loStr  = (loEl?.value ?? '').trim();               // 左界：id="check_target_tor_lo"（例：0.240）
                const hiStr2 = (maxEl?.value ?? '').trim();              // 右界：id="tool_max_torque_diff"（例：2.640）
                // 若沒有 tool_max_torque_diff，右界改用目前欄位值格式化顯示
                const rightBound = hiStr2 || (Number.isFinite(hiVal) ? String(hiVal) : '');

                // 多語訊息
                const I18N = {
                'zh-tw': { title: '警告',   msg: '扭力上限需位於允許範圍：{range}', ok: '確定', sep: ' ~ ' },
                'zh-cn': { title: '警告',   msg: '扭力上限需位于允许范围：{range}', ok: '确定', sep: ' ~ ' },
                'en-us': { title: 'Warning', msg: 'Torque upper limit must be within the allowed range: {range}', ok: 'OK', sep: ' ~ ' },
                }[lang];

                const rangeStr = `${loStr}${I18N.sep}${rightBound}`;

                // 節流：避免連續彈窗
                if (window._alertingHiVsToolLo) return;
                window._alertingHiVsToolLo = true;

                const showMsg = (title, msg, ok) => {
                if (window.alertify?.alert) {
                    alertify
                    .alert(title, msg, function () {
                        try { hiEl.focus(); hiEl.select?.(); } catch {}
                        window._alertingHiVsToolLo = false;
                    })
                    .set('labels', { ok });
                } else {
                    alert(`${title}\n${msg}`);
                    window._alertingHiVsToolLo = false;
                }
                };

                showMsg(I18N.title, I18N.msg.replace('{range}', rangeStr), I18N.ok);

                // 全域驗證旗標
                if (typeof window.isValid !== 'undefined') window.isValid = false;
                if (Array.isArray(window.errorList) && !window.errorList.includes('StepHiTorque')) {
                window.errorList.push('StepHiTorque');
                }
            } catch (err) {
                console.error('[enforceHiTorqueWithinToolRangeForOption1] error:', err);
            }
        })();





        // ---- StepOption==2 且 StepHiTorque 超過上限 → 用彈窗顯示（含語系 + 當前扭力單位多語 + 範圍 min - max）----
        (function enforceStepHiTorqueNotExceedMaxForOption2() {
            if (StepOption !== 2) return;

            const hiEl = document.getElementById('StepHiTorque');
            if (!hiEl) return;

            const hiVal = Number(hiEl.value);
            if (!Number.isFinite(hiVal)) return;

            // 上限：用前面 limits.torque.limitHi.max 的來源（check_hi_tor_after）
            const hiMaxRaw = Number(check_hi_tor_after);
            if (!Number.isFinite(hiMaxRaw)) return;

            // 下限：動態（StepTorque + 單位增量）
            const hiMinRaw = Number(dynamicHiMin);

            // 依 precision 四捨五入後比較/顯示
            const hiRounded  = roundTo(hiVal,    precision);
            const hiMaxRound = roundTo(hiMaxRaw, precision);
            const hiMinRound = roundTo(hiMinRaw, precision);

            if (hiRounded > hiMaxRound) {
                // 標紅並關掉 inline
                hiEl.classList.add("is-invalid");
                const fb = hiEl.nextElementSibling;
                if (fb?.classList.contains("invalid-feedback")) {
                fb.innerText = '';
                fb.classList.remove("d-block");
                fb.style.display = "none";
                }

                // 取語系（cookie）
                const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
                };
                let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
                lang = String(lang).toLowerCase();
                if (lang === 'en') lang = 'en-us';
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

                // === 單位多語 ===
                // 1) 定義單位鍵 → 多語顯示
                const unitLabels = {
                'kgf.cm': { 'zh-cn': '公斤·公分', 'zh-tw': '公斤·公分', 'default': 'kgf.cm' },
                'lbf.in': { 'zh-cn': '磅·英吋',   'zh-tw': '磅·英吋',   'default': 'lbf.in' },
                'N.m':    { 'zh-cn': '牛顿·米',   'zh-tw': '牛頓·公尺',   'default': 'N·m'    },
                'kgf.m':  { 'zh-cn': '公斤·米',   'zh-tw': '公斤·公尺',   'default': 'kgf.m'  },
                'cN.m':   { 'zh-cn': '牛頓.厘米',   'zh-tw': '牛頓.釐米',   'default': 'cN·m'   },
                };

                // 2) torque_unit 數值 → 單位鍵
                const UNIT_KEY_BY_CODE = {
                    0: 'kgf.cm',
                    1: 'N.m',
                    2: 'lbf.in',
                    3: 'kgf.m',
                    4: 'cN.m',
                };

                // 3) 若 <select id="step_torque_unit"> 有可視文字，嘗試對應到鍵；否則用數值對照
                const tryMapVisibleToKey = (txt) => {
                if (!txt) return null;
                const norm = txt.replace(/\s/g, '').replace('·', '.'); // 規一化
                // 嘗試直接比對鍵
                if (unitLabels[norm]) return norm;
                // 嘗試常見變體
                const variants = {
                    'N.m':'N.m', 'N·m':'N.m', 'Nm':'N.m',
                    'kgf.cm':'kgf.cm','kgf·cm':'kgf.cm',
                    'lbf.in':'lbf.in','lbf·in':'lbf.in',
                    'cN.m':'cN.m','cN·m':'cN.m',
                    'kgf.m':'kgf.m','kgf·m':'kgf.m',
                };
                return variants[norm] || null;
                };

                const selectEl = document.getElementById('step_torque_unit');
                const visibleTxt = selectEl?.options?.[selectEl.selectedIndex]?.text?.trim() || '';
                const fromVisible = tryMapVisibleToKey(visibleTxt);
                const unitKey = fromVisible || UNIT_KEY_BY_CODE[torque_unit] || 'N.m';

                // 4) 依語系取顯示文字
                const unitText =
                (lang === 'zh-cn' && unitLabels[unitKey]?.['zh-cn']) ? unitLabels[unitKey]['zh-cn'] :
                (lang === 'zh-tw' && unitLabels[unitKey]?.['zh-tw']) ? unitLabels[unitKey]['zh-tw'] :
                (unitLabels[unitKey]?.['default'] || unitKey);

                // 範圍文字（min - max）— 只調整顯示用下限：cN·m 顯示多一格(0.1)
                const fmt = (n) => Number(n).toFixed(precision);
                // 額外：cN·m（unitKey === 'cN.m'）時，下限顯示 + 1 個刻度
                const inc = 1 / Math.pow(10, precision);
                const loForDisplay = (unitKey === 'cN.m') ? (hiMinRound + inc) : hiMinRound;

                const rangeStr = `${fmt(loForDisplay)} - ${fmt(hiMaxRound)}`;


                // I18N
                const I18N = {
                'en-us': { title: 'Warning', msg: 'StepHiTorque out of range ({UNIT}). Allowed range: {RANGE}', ok: 'OK' },
                'zh-tw': { title: '警告',   msg: '扭力上限（{UNIT}）超出範圍，允許範圍：{RANGE}',      ok: '確定' },
                'zh-cn': { title: '警告',   msg: '扭力上限（{UNIT}）超出范围，允许范围：{RANGE}',      ok: '确定' }
                };
                const T = I18N[lang] || I18N['en-us'];

                if (window._alertingHiTorqueRange) {
                isValid = false;
                if (!errorList.includes('StepHiTorque')) errorList.push('StepHiTorque');
                return;
                }
                window._alertingHiTorqueRange = true;

                const finalMsg = T.msg
                .replace('{UNIT}', unitText)
                .replace('{RANGE}', rangeStr);

                alertify
                .alert(T.title, finalMsg, function () {
                    try { hiEl.focus(); hiEl.select?.(); } catch {}
                    window._alertingHiTorqueRange = false;
                })
                .set('labels', { ok: T.ok });

                isValid = false;
                if (!errorList.includes('StepHiTorque')) errorList.push('StepHiTorque');
            }
        })();


        (function enforceStepHiTorqueNotExceedMaxForOption1() {
            if (StepOption !== 1) return;

            const hiEl = document.getElementById('StepHiTorque');
            if (!hiEl) return;

            const hiVal = Number(hiEl.value);
            if (!Number.isFinite(hiVal)) return;

            // Option 1 的上限 / 下限來源（與你的 conditions 設定一致）
            const hiMaxRaw = Number(check_hi_tor_after_temp);  // 上限
            const hiMinRaw = Number(dynamicHiMin);             // 下限：嚴格大於 TQ（含單位刻度）


            if (!Number.isFinite(hiMaxRaw)) return;

            // 依目前精度四捨五入後再比較/顯示
            const hiRounded  = roundTo(hiVal,    precision);
            const hiMaxRound = roundTo(hiMaxRaw, precision);
            const hiMinRound = Number.isFinite(hiMinRaw) ? roundTo(hiMinRaw, precision) : null;

            if (hiRounded > hiMaxRound) {
                // 標紅並收掉 inline 的錯誤訊息（改用彈窗）
                hiEl.classList.add("is-invalid");
                const fb = hiEl.nextElementSibling;
                if (fb?.classList.contains("invalid-feedback")) {
                    fb.innerText = '';
                    fb.classList.remove("d-block");
                    fb.style.display = "none";
                }

                // 語系（沿用你其它區塊的 cookie 取法）
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
                if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

                const I18N = {
                    'en-us': { title: 'Warning', msg: 'StepHiTorque is out of range. ', ok: 'OK' },
                    'zh-tw': { title: '警告',   msg: '扭力上限超出範圍',         ok: '確定' },
                    'zh-cn': { title: '警告',   msg: '扭力上限超出范围',         ok: '确定' }
                };
                const T = I18N[lang] || I18N['en-us'];

                const rangeStr = (hiMinRound !== null)
                    ? `${hiMinRound.toFixed(precision)} ~ ${hiMaxRound.toFixed(precision)}`
                    : `<= ${hiMaxRound.toFixed(precision)}`;

                // 避免一次驗證彈出多個視窗
                if (window._alertingHiTorqueRangeOpt1) {
                    isValid = false;
                    if (!errorList.includes('StepHiTorque')) errorList.push('StepHiTorque');
                    return;
                }
                window._alertingHiTorqueRangeOpt1 = true;

                alertify
                    .alert(T.title, T.msg.replace('{RANGE}', rangeStr), function () {
                        try { hiEl.focus(); hiEl.select?.(); } catch {}
                        window._alertingHiTorqueRangeOpt1 = false;
                    })
                    .set('labels', { ok: T.ok });

                // 驗證結果維持原格式
                isValid = false;
                if (!errorList.includes('StepHiTorque')) errorList.push('StepHiTorque');
            }
        })();

        // ---- StepRPM 用彈跳視窗（含語系與允許範圍）----
        (function enforceStepRPMWithDialog() {
            const el = document.getElementById('StepRPM');
            if (!el) return;

            const raw = el.value.trim();
            const n = Number(raw);

            // 範圍：對應你 conditions 的設定
            const minR = Number(Tool_Min_RPM);
            const maxR = Number(Tool_Max_RPM);

            // 取語系（與你現有做法一致）
            const getCookieSafe = (name) => {
                try {
                const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
            };
            let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

            const I18N = {
                'en-us': { title: 'Warning', msg:'RPM out of range between {RANGE}', ok: 'OK' },
                'zh-tw': { title: '警告',   msg: '轉速超出範圍，且介於 {RANGE}',           ok: '確定' },
                'zh-cn': { title: '警告',   msg: '转速超出范围，且介于 {RANGE}',           ok: '确定' }
            };
            const T = I18N[lang];

            // 顯示用範圍字串
            let rangeStr = 'N/A';
            const haveMin = Number.isFinite(minR);
            const haveMax = Number.isFinite(maxR);
            if (haveMin && haveMax) rangeStr = `${parseInt(minR,10)} ~ ${parseInt(maxR,10)}`;
            else if (haveMin)        rangeStr = `≥ ${parseInt(minR,10)}`;
            else if (haveMax)        rangeStr = `≤ ${parseInt(maxR,10)}`;

            // 驗證規則：1~4 位數整數 + 範圍
            const pattern = /^\d{1,4}$/;
            const badFormat = !pattern.test(raw) || !Number.isInteger(n);
            const badRange  =
                (haveMin && Number.isFinite(n) && n < minR) ||
                (haveMax && Number.isFinite(n) && n > maxR);

            if (raw === '' || !Number.isFinite(n) || badFormat || badRange) {
                // 標紅，但關掉 inline 訊息（改用彈窗）
                el.classList.add('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }

                // 避免同一輪重複彈窗
                if (window._alertingStepRPM) {
                isValid = false;
                if (!errorList.includes('StepRPM')) errorList.push('StepRPM');
                return;
                }
                window._alertingStepRPM = true;

                alertify
                .alert(T.title, T.msg.replace('{RANGE}', rangeStr), function () {
                    try { el.focus(); el.select?.(); } catch {}
                    window._alertingStepRPM = false;
                })
                .set('labels', { ok: T.ok });

                isValid = false;
                if (!errorList.includes('StepRPM')) errorList.push('StepRPM');
            } else {
                // 合法就清掉任何殘留的 inline 樣式
                el.classList.remove('is-invalid');
                const fb = el.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }
            }
        })();



        // === DS="2" 時，讓 StepTorqueDownShift 與 StepTorque 完全一致（精度/格式/四捨五入）===
        (function syncDownshiftLikeStepTorque() {
            // 僅 StepOption==2 且 DownShift==2 時才啟用
            const stepOpt = parseInt(document.getElementById("StepOption")?.value ?? 0, 10);
            if (stepOpt !== 2 || StepEnableDownShift !== "2") return;

            const el = document.getElementById('StepTorqueDownShift');
            if (!el) return;

            // 用你前面 input_check() 算好的 precision；若未定義就 fallback 為 getTorquePrecision() 或 3
            const prec =
                Number.isInteger(typeof precision !== 'undefined' ? precision : NaN)
                ? precision
                : (typeof getTorquePrecision === 'function' ? Number(getTorquePrecision()) || 3 : 3);

            // 若有條件陣列就同步規則（可選）
            const td = (typeof conditions !== 'undefined')
                ? conditions.find(c => c.id === 'StepTorqueDownShift')
                : null;
            if (td) {
                td.pattern = new RegExp(`^\\d{1,5}(?:\\.\\d{1,${prec}})?$`);
                td.precision = prec;
                td.roundBeforeCompare = true;
                td.integerOnly = false;
            }

            // UI：與 StepTorque 相同的步進與輸入模式
            el.setAttribute('step', prec > 0 ? `0.${'0'.repeat(prec - 1)}1` : '1');
            el.setAttribute('inputmode', prec > 0 ? 'decimal' : 'numeric');

            // Half-up 四捨五入
            const _roundHalfUp = (typeof roundHalfUp === 'function')
                ? roundHalfUp
                : (n, p) => {
                    const f = Math.pow(10, p);
                    return Math.sign(n) * Math.round(Math.abs(n) * f + 1e-12) / f;
                };

            // 裁切輸入長度
            const clampDigits = (v) => {
                v = String(v ?? '').replace(/[^\d.]/g, '');
                if (prec === 0) return v.replace(/\..*$/, '').slice(0, 5);
                const i = v.indexOf('.');
                if (i !== -1) v = v.slice(0, i + 1) + v.slice(i + 1).replace(/\./g, '');
                const [ip, dp = ''] = v.split('.');
                const intPart = (ip || '').replace(/^0+(?=\d)/, '0').slice(0, 5);
                const decPart = dp.slice(0, prec);
                return decPart ? `${intPart}.${decPart}` : intPart;
            };

            if (!el.dataset.syncLikeTorque) {
                el.addEventListener('input', () => { el.value = clampDigits(el.value); });
                el.addEventListener('blur',  () => {
                const n = Number(el.value);
                if (Number.isFinite(n)) el.value = _roundHalfUp(n, prec).toFixed(prec);
                });
                el.addEventListener('keydown', (e) => {
                if (prec === 0 && (e.key === '.' || e.key === ',' || e.key === 'Decimal')) e.preventDefault();
                });
                el.addEventListener('paste', (e) => {
                if (prec !== 0) return;
                const text = (e.clipboardData || window.clipboardData).getData('text') || '';
                if (/[^\d]/.test(text)) e.preventDefault();
                });
                el.dataset.syncLikeTorque = '1';
            }
        })();


        // ---- 交叉驗證：StepOption==2；StepTorque 超出「工具規格」範圍，但未超過 StepHiTorque → 彈窗 + 語系 ----
        (function enforceTargetTorqueWithinToolSpecButBelowHi() {
            try {
                // 讀 StepOption（避免依賴外層變數）
                const _stepOpt = parseInt(document.getElementById('StepOption')?.value ?? 0, 10);
                if (_stepOpt !== 2) return;

                const tqEl = document.getElementById('StepTorque');
                const hiEl = document.getElementById('StepHiTorque');
                if (!tqEl || !hiEl) return;

                const tqRaw     = Number(tqEl.value);
                const hiRaw     = Number(hiEl.value);

                // 工具規格（若頁面上沒有 Tool_Min_Torque/Tool_Max_Torque，仍可運作）
                const Tool_Min_Torque = Number(window.Tool_Min_Torque ?? NaN);
                const Tool_Max_Torque = Number(window.Tool_Max_Torque ?? NaN);

                // 顯示用範圍取自 check_*（可保留補零），若缺就 fallback 工具規格
                const loStrDom = (document.getElementById('check_target_tor_lo')?.value ?? '').trim();
                const hiStrDom = (document.getElementById('check_target_tor_hi')?.value ?? '').trim();
                const loDomNum = Number(loStrDom);
                const hiDomNum = Number(hiStrDom);

                // roundTo / precision 防呆
                const roundTo = (typeof window.roundTo === 'function')
                ? window.roundTo
                : (n, d = 3) => {
                    const v = Number(n);
                    if (!isFinite(v)) return NaN;
                    const f = Math.pow(10, d);
                    return Math.round(v * f) / f;
                    };
                const precision = (typeof window.precision === 'number' && isFinite(window.precision)) ? window.precision : 3;

                // 語系與單位
                const getCookieSafe = (name) => {
                try {
                    const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
                    return m ? decodeURIComponent(m[1]) : null;
                } catch { return null; }
                };
                let lang = 'en-us', unitText = 'N·m';
                if (typeof window.getLangAndUnit === 'function') {
                const lu = window.getLangAndUnit() || {};
                lang = String(lu.lang || 'en-us').toLowerCase();
                if (lang === 'en') lang = 'en-us';
                unitText = lu.unit || 'N·m';
                } else {
                lang = (getCookieSafe('language') || 'en-us').toLowerCase();
                if (lang === 'en') lang = 'en-us';
                if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';
                const code = parseInt(document.getElementById('step_torque_unit')?.value ?? window.torque_unit ?? 1, 10);
                const UNIT_TEXT = {
                    0: { 'en-us': 'kgf·cm', 'zh-tw': '公斤·公分', 'zh-cn': '公斤·公分' },
                    1: { 'en-us': 'N·m',    'zh-tw': '牛頓·公尺', 'zh-cn': '牛顿·米' },
                    2: { 'en-us': 'lbf·in', 'zh-tw': '磅·英吋',   'zh-cn': '磅·英寸' },
                    3: { 'en-us': 'kgf·m',  'zh-tw': '公斤·公尺', 'zh-cn': '公斤·米' },
                    4: { 'en-us': 'cN·m',   'zh-tw': '牛頓·釐米', 'zh-cn': '牛顿·厘米' },
                };
                unitText = (UNIT_TEXT[code]?.[lang]) || 'N·m';
                }

                // 範圍字串：優先用 DOM 原字串（保留 0 補位），否則用工具規格數值格式化
                const fmt = (n) => Number(n).toFixed(precision);
                const showLoStr =
                (loStrDom && isFinite(loDomNum)) ? loStrDom
                : (isFinite(Tool_Min_Torque) ? fmt(Tool_Min_Torque) : '');
                const showHiStr =
                (hiStrDom && isFinite(hiDomNum)) ? hiStrDom
                : (isFinite(Tool_Max_Torque) ? fmt(Tool_Max_Torque) : '');

                // 用於比較：若 DOM 有數值，用 DOM；否則用工具規格
                const cmpLo = isFinite(loDomNum) ? loDomNum : Tool_Min_Torque;
                const cmpHi = isFinite(hiDomNum) ? hiDomNum : Tool_Max_Torque;

                if (![tqRaw, hiRaw, cmpLo, cmpHi].every(Number.isFinite)) return;

                const tq = roundTo(tqRaw, precision);
                const hi = roundTo(hiRaw, precision);

                // 觸發條件：
                // 1) tq <= hi（沒有超過步驟上限）
                // 2) tq 落在允許範圍之外（以 DOM check_* 範圍為主，缺值才退回工具規格）
                if (tq <= hi && (tq < cmpLo || tq > cmpHi)) {
                // 關閉 inline 錯誤（改用彈窗）
                tqEl.classList.add('is-invalid');
                const fb = tqEl.nextElementSibling;
                if (fb?.classList.contains('invalid-feedback')) {
                    fb.innerText = '';
                    fb.classList.remove('d-block');
                    fb.style.display = 'none';
                }

                // === 修改後訊息：目標扭力（單位）超出範圍，（lo ～ hi）/ 英文用 ~ ===
                const sep = (lang === 'en-us') ? ' ~ ' : ' ～ ';
                const rangeStr = `${showLoStr}${sep}${showHiStr}`;

                const I18N_MAP = {
                    'zh-tw': {
                    title: '警告',
                    ok: '確定',
                    tmpl: (u, r) => `目標扭力（${u}）超出範圍，（${r}）`
                    },
                    'zh-cn': {
                    title: '警告',
                    ok: '确定',
                    tmpl: (u, r) => `目标扭力（${u}）超出范围，（${r}）`
                    },
                    'en-us': {
                    title: 'Warning',
                    ok: 'OK',
                    tmpl: (u, r) => `Target torque (${u}) is out of range (${r})`
                    }
                };
                const I18N = I18N_MAP[lang] || I18N_MAP['en-us'];
                const message = I18N.tmpl(unitText, rangeStr);

                // 節流避免重複彈窗
                if (window._alertingTQvsSpec) {
                    if (typeof window.isValid !== 'undefined') window.isValid = false;
                    if (Array.isArray(window.errorList) && !window.errorList.includes('StepTorque')) window.errorList.push('StepTorque');
                    return;
                }
                window._alertingTQvsSpec = true;

                if (window.alertify?.alert) {
                    alertify
                    .alert(I18N.title, message, function () {
                        try { tqEl.focus(); tqEl.select?.(); } catch {}
                        window._alertingTQvsSpec = false;
                    })
                    .set('labels', { ok: I18N.ok });
                } else {
                    alert(`${I18N.title}\n${message}`);
                    window._alertingTQvsSpec = false;
                }

                // 不觸發 TDZ：僅在已宣告時才寫入
                if (typeof window.isValid !== 'undefined') window.isValid = false;
                if (Array.isArray(window.errorList) && !window.errorList.includes('StepTorque')) window.errorList.push('StepTorque');
                }
            } catch (e) {
                console.error('[enforceTargetTorqueWithinToolSpecButBelowHi] error:', e);
            }
        })();






        // ---- StepOption==2 且 StepTorque == StepHiTorque == StepLoTorque → 彈窗 + 語系 ----
        (function enforceAllEqualTorquesOption2() {
            const stepOptEl = document.getElementById("StepOption");
            const stepOpt = parseInt(stepOptEl?.value ?? 0, 10);
            if (stepOpt !== 2) return;

            const tqEl = document.getElementById('StepTorque');
            const hiEl = document.getElementById('StepHiTorque');
            const loEl = document.getElementById('StepLoTorque');
            if (!tqEl || !hiEl || !loEl) return;

            const tq = Number(tqEl.value);
            const hi = Number(hiEl.value);
            const lo = Number(loEl.value);
            if (!Number.isFinite(tq) || !Number.isFinite(hi) || !Number.isFinite(lo)) return;

            // 四捨五入避免浮點誤差
            const _round = (typeof roundTo === 'function')
                ? roundTo
                : (n, d = 3) => { const v = Number(n); return isNaN(v) ? NaN : parseFloat(v.toFixed(d)); };
            const p = (typeof precision === 'number') ? precision : 3;

            const tqR = _round(tq, p);
            const hiR = _round(hi, p);
            const loR = _round(lo, p);

            if (!(tqR === hiR && hiR === loR)) return; // 只有三者都相等才提示

            // 取得語系與單位字樣
            let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : null);
            if (!lang) {
                try {
                const m = document.cookie.match(/(?:^|; )language=([^;]+)/);
                lang = m ? decodeURIComponent(m[1]) : 'zh-tw';
                } catch { lang = 'zh-tw'; }
            }
            lang = String(lang).toLowerCase();
            if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

            let unitText = (typeof getLangAndUnit === 'function' ? (getLangAndUnit().unit || 'N·m') : 'N·m');
            if (unitText === 'N.m') unitText = 'N·m'; // 小修正常見寫法

            const I18N = {
                'zh-tw': { title: '警告',   ok: '確定', msg: (u) => `扭力下限（${u}）需要小於扭力上限` },
                'zh-cn': { title: '警告',   ok: '确定', msg: (u) => `扭力下限（${u}）需要小于扭力上限` },
                'en-us': { title: 'Warning', ok: 'OK',  msg: (u) => `Torque lower limit (${u}) must be less than upper limit` }
            }[lang];

            // 收掉 inline（避免同時出現兩種提示）
            [loEl, hiEl].forEach(el => {
                const fb = el?.nextElementSibling;
                el?.classList.remove('is-invalid');
                if (fb?.classList.contains('invalid-feedback')) {
                fb.innerText = '';
                fb.classList.remove('d-block');
                fb.style.display = 'none';
                }
            });

            // 標記下限為錯誤
            loEl.classList.add('is-invalid');

            // 避免重複彈窗
            if (window._alertingAllTqEqual) {
                isValid = false;
                if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
                return;
            }
            window._alertingAllTqEqual = true;

            alertify
                .alert(I18N.title, I18N.msg(unitText), function () {
                try { loEl.focus(); loEl.select?.(); } catch {}
                window._alertingAllTqEqual = false;
                })
                .set('labels', { ok: I18N.ok });

            isValid = false;
            if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
        })();



      
        
        // ---- StepOption==2 且 StepTorque == StepHiTorque == StepLoTorque → 只標記下限；StepTorque 不要紅框 ----
        (function enforceAllEqualTorquesOption2_NoRedOnTarget() {
            const stepOpt = parseInt(document.getElementById("StepOption")?.value ?? 0, 10);
            if (stepOpt !== 2) return;

            const tqEl = document.getElementById('StepTorque');
            const hiEl = document.getElementById('StepHiTorque');
            const loEl = document.getElementById('StepLoTorque');
            if (!tqEl || !hiEl || !loEl) return;

            const p = (typeof precision === 'number') ? precision : 3;
            const _round = (typeof roundTo === 'function')
                ? roundTo
                : (n, d = p) => { const v = Number(n); return isNaN(v) ? NaN : parseFloat(v.toFixed(d)); };

            const tqR = _round(Number(tqEl.value), p);
            const hiR = _round(Number(hiEl.value), p);
            const loR = _round(Number(loEl.value), p);
            if (!(Number.isFinite(tqR) && Number.isFinite(hiR) && Number.isFinite(loR))) return;

            if (!(tqR === hiR && hiR === loR)) return; // 只有三者相等時才處理

            // 取得語系與單位
            let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : 'zh-tw') || 'zh-tw';
            lang = String(lang).toLowerCase(); if (lang === 'en') lang = 'en-us';
            if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';
            const unit = (typeof getLangAndUnit === 'function' ? (getLangAndUnit().unit || 'N·m') : 'N·m');

            const I18N = {
                'zh-tw': { title: '警告', ok: '確定', msg: (u) => `扭力下限（${u}）需要小於扭力上限` },
                'zh-cn': { title: '警告', ok: '确定', msg: (u) => `扭力下限（${u}）需要小于扭力上限` },
                'en-us': { title: 'Warning', ok: 'OK', msg: (u) => `Torque lower limit (${u}) must be less than upper limit` }
            }[lang];

            // ✨ 確保 StepTorque 不要紅框、也不記在錯誤清單
            tqEl.classList.remove('is-invalid');
            const tqFb = tqEl.nextElementSibling;
            if (tqFb?.classList.contains('invalid-feedback')) {
                tqFb.innerText = ''; tqFb.classList.remove('d-block'); tqFb.style.display = 'none';
            }
            const idx = errorList.indexOf('StepTorque');
            if (idx !== -1) errorList.splice(idx, 1);

            // 只標記下限為錯誤（並清掉其 inline 提示，改用彈窗）
            loEl.classList.add('is-invalid');
            const loFb = loEl.nextElementSibling;
            if (loFb?.classList.contains('invalid-feedback')) {
                loFb.innerText = ''; loFb.classList.remove('d-block'); loFb.style.display = 'none';
            }

            // 彈跳視窗 + 語系
            if (!window._alertingAllTqEqual) {
                window._alertingAllTqEqual = true;
                alertify
                .alert(I18N.title, I18N.msg(unit), function () {
                    try { loEl.focus(); loEl.select?.(); } catch {}
                    window._alertingAllTqEqual = false;
                })
                .set('labels', { ok: I18N.ok });
            }

            isValid = false;
            if (!errorList.includes('StepLoTorque')) errorList.push('StepLoTorque');
        })();


        //目標角度 + 門檻點角度
        const okTS = validateThresholdAngleVsHiAngle_Opt1({ errorList });
        if (!okTS) isValid = false;

    // === 最後統一套用紅框（避免被其它段落清掉）===
    (function finalizeInvalidStyles(){
        try {
            if (Array.isArray(errorList) && errorList.includes('StepTorque')) {
            const el = document.getElementById('StepTorque');
            if (el) {
                el.classList.add('is-invalid');
                el.dataset.keepInvalid = '1';
            }
            }
        } catch {}
    })();
        

    return { valid: isValid, errors: errorList };
    }



    function validateThresholdAngleVsHiAngle_Opt1({ errorList } = {}) {
        // 僅在：目標角度模式＋門檻點角度
        const stepOpt = parseInt(document.getElementById('StepOption')?.value ?? 0, 10);
        const enTh    = document.querySelector('input[name="StepEnableThreshold"]:checked')?.value ?? "0";
        if (stepOpt !== 1 || enTh !== "1") return true;

        const tsEl = document.getElementById('StepTorqueTS');  // 門檻點角度
        const hiEl = document.getElementById('StepAngle');   // 角度上限
        if (!tsEl || !hiEl) return true;

        // ---- 格式強化：門檻點角度一律整數，不要 .000 ----
        //   1) 移除末端 .0/.00/.000...
        //   2) 若有小數，取整數字串（保留原數值的整數部分）
        (function enforceIntegerDisplay() {
            const raw = String(tsEl.value ?? '');
            if (raw.includes('.')) {
            // 只保留小數點前的整數
            const intStr = raw.split('.')[0].replace(/[^\d]/g, '');
            tsEl.value = intStr || '0';
            }
            // 再移除任何非數字字元
            tsEl.value = String(tsEl.value).replace(/[^\d]/g, '');
            // 提示瀏覽器用整數輸入模式
            tsEl.setAttribute('step', '1');
            tsEl.setAttribute('inputmode', 'numeric');
        })();

        const tsVal = Number(tsEl.value);
        const hiVal = Number(hiEl.value);
        if (!Number.isFinite(tsVal) || !Number.isFinite(hiVal)) return true;

        // ---- i18n ----
        function getCookieSafe(name){
            try {
            const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
            return m ? decodeURIComponent(m[1]) : null;
            } catch { return null; }
        }
        let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : (getCookieSafe('language') || 'en-us')) || 'en-us';
        lang = String(lang).toLowerCase();
        if (lang === 'en') lang = 'en-us';
        if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

        const I18N = {
            'en-us': { title: 'Warning', ok: 'OK',
                    msg: (limit) => `Threshold angle (degrees) must be less than the target angle` },
            'zh-tw': { title: '警告',   ok: '確定',
                    msg: (limit) => `門檻點角度(度)需 小於 目標角度` },
            'zh-cn': { title: '警告',   ok: '确定',
                    msg: (limit) => `门槛点角度（度）需小于目标角度` },
        }[lang];

        // ---- 驗證：TS 必須 <= HiAngle ----
        if (tsVal > hiVal) {
            // 標紅並收掉欄位旁的 inline 提示（避免雙重訊息）
            tsEl.classList.add('is-invalid');
            const fb = tsEl.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
            }

            // 避免重複彈窗
            if (!window._alertingTSgtHiAngle_Opt1) {
            window._alertingTSgtHiAngle_Opt1 = true;
            alertify
                .alert(I18N.title, I18N.msg(hiVal), function () {
                try { tsEl.focus(); tsEl.select?.(); } catch {}
                window._alertingTSgtHiAngle_Opt1 = false;
                })
                .set('labels', { ok: I18N.ok });
            }

            if (Array.isArray(errorList) && !errorList.includes('StepTorqueTS')) {
            errorList.push('StepTorqueTS');
            }
            return false;
        }

        // 通過：清除紅框與殘留提示
        tsEl.classList.remove('is-invalid');
        const fb = tsEl.nextElementSibling;
        if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
        }

        return true;
    }

    

    // ---- 驗證 StepOption==2 且 StepTorque/StepLoTorque 與 StepHiTorque 的關係（訊息含單位與範圍）----
    // ① StepTorque > StepHiTorque 且 StepLoTorque < StepHiTorque → 顯示(最小刻度 ~ #check_target_tor_hi)【保留舊行為條件】
    // ② StepTorque == StepLoTorque 且 StepTorque/StepLoTorque < StepHiTorque → 顯示 #check_target_tor_lo ~ #check_target_tor_hi
    // ③ StepTorque == StepLoTorque 且 StepTorque/StepLoTorque > StepHiTorque → 顯示 #check_target_tor_lo ~ #check_target_tor_hi
    function validateTorqueEqualLo() {
        const stepOpt = parseInt(document.getElementById("StepOption")?.value ?? 0, 10);

        // ==== 共用小工具 ====
        const getCookieSafe = (name) => {
            try { const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
            catch { return null; }
        };
        let lang = (typeof getCookie === 'function' && getCookie('language')) || getCookieSafe('language') || 'zh-tw';
        lang = String(lang).toLowerCase(); if (lang === 'en') lang = 'en-us';
        if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

        const TITLE  = (lang === 'en-us') ? 'Warning' : '警告';
        const OKTXT  = (lang === 'en-us') ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定');
        const SEP    = (lang === 'en-us') ? '~' : '～';

        const unitCode = parseInt(document.getElementById('step_torque_unit')?.value ?? 1, 10);
        const UNIT_TEXT = {
            0: { 'en-us':'kgf·cm', 'zh-tw':'公斤·公分', 'zh-cn':'公斤·公分' },
            1: { 'en-us':'N·m',    'zh-tw':'牛頓·公尺', 'zh-cn':'牛顿·米' },
            2: { 'en-us':'lbf·in', 'zh-tw':'磅·英吋',   'zh-cn':'磅·英寸' },
            3: { 'en-us':'kgf·m',  'zh-tw':'公斤·公尺', 'zh-cn':'公斤·米' },
            4: { 'en-us':'cN·m',   'zh-tw':'牛頓·釐米', 'zh-cn':'牛顿·厘米' },
        };
        const unitText = (UNIT_TEXT[unitCode]?.[lang]) ?? '扭力單位';

        const dispPrecision =
            unitCode === 4 ? 1 :
            unitCode === 3 ? 4 :
            unitCode === 2 ? 2 : 3;

        const roundTo = (n, p = dispPrecision) => {
            const v = Number(n); return Number.isFinite(v) ? Number(v.toFixed(p)) : NaN;
        };
        const fmt     = (n) => Number(n).toFixed(dispPrecision);
        const oneTick = parseFloat((1 / Math.pow(10, dispPrecision)).toFixed(dispPrecision));

        let _alerting = false;
        const alertMsg = (msg, focusEl) => {
            if (_alerting) return;
            _alerting = true;
            try {
            alertify.alert(TITLE, msg, () => {
                _alerting = false;
                try { focusEl?.focus(); focusEl?.select?.(); } catch {}
            }).set('labels', { ok: OKTXT });
            } catch {
            alert(msg);
            _alerting = false;
            try { focusEl?.focus(); focusEl?.select?.(); } catch {}
            }
        };

        const clearInlineError = (el) => {
            if (!el) return;
            el.classList.remove('is-invalid');
            const fb = el.nextElementSibling;
            if (fb?.classList?.contains('invalid-feedback')) {
            fb.innerText = ''; fb.classList.remove('d-block'); fb.style.display = 'none';
            }
        };
        const setInvalid = (el) => {
            if (!el) return;
            el.classList.add('is-invalid');
        };

        // ========== 共同欄位 ==========
        const tqEl    = document.getElementById('StepTorque');
        const loEl    = document.getElementById('StepLoTorque');
        const hiEl    = document.getElementById('StepHiTorque');
        const chkLoEl = document.getElementById('check_target_tor_lo');
        const chkHiEl = document.getElementById('check_target_tor_hi');

        const tq = Number(tqEl?.value);
        const lo = Number(loEl?.value);
        const hi = Number(hiEl?.value);

        const tqR = roundTo(tq), loR = roundTo(lo), hiR = roundTo(hi);

        // 讀取 #check_* 的「顯示字串」（保留補零）與數值
        const cLoStr = (chkLoEl?.value ?? '').trim();
        const cHiStr = (chkHiEl?.value ?? '').trim();
        const specLo = Number(cLoStr);
        const specHi = Number(cHiStr);
        const showLo = cLoStr || fmt(oneTick);
        const showHi = cHiStr || (Number.isFinite(hiR) ? fmt(hiR) : '');

 

        // ================== StepOption == 1（目標角度） ==================
        if (stepOpt === 1) {
            if (!loEl || !hiEl) return true;
            if (!Number.isFinite(lo) || !Number.isFinite(hi)) return true;

            const hiMax = Number.isFinite(specHi) ? roundTo(specHi * 1.10) : NaN;

            if (Number.isFinite(hiMax)) {
            const okHiLo = (lo === 0) ? (hi > 0) : (hi > lo);
            if (hi <= hiMax && okHiLo) { clearInlineError(loEl); clearInlineError(hiEl); return true; }

            const msg =
                (lang === 'en-us')
                ? `Torque high limit must be within: ${fmt(specLo)} ~ ${fmt(hiMax)}`
                : (lang === 'zh-cn')
                ? `扭力上限需位于允许范围：${fmt(specLo)} ~ ${fmt(hiMax)}`
                : `扭力上限需位於允許範圍：${fmt(specLo)} ~ ${fmt(hiMax)}`;
            setInvalid(hiEl);
            alertMsg(msg, hiEl);
            return false;
            }

            if ((lo === 0 && hi > 0) || hi > lo) { clearInlineError(loEl); clearInlineError(hiEl); return true; }

            const msg2 =
            (lang === 'en-us') ? `Torque high limit must be greater than low limit`
            : (lang === 'zh-cn') ? `扭矩上限需大于下限`
            : `扭力上限需大於下限`;
            setInvalid(hiEl);
            alertMsg(msg2, hiEl);
            return false;
        }
        // ================== StepOption==1 結束 ==================

        if (stepOpt !== 2) return true; // 僅在「目標扭力」模式檢查

        // ================== StepOption == 2（目標扭力） ==================
        if (!tqEl || !loEl) return true;
        if (!Number.isFinite(tq) || !Number.isFinite(lo)) return true;

        clearInlineError(tqEl); // 先清掉可能的 inline 錯誤

        // === [新增特例] StepOption==2 且 TQ == HQ → 顯示「顯示刻度 ～ (HQ - 顯示刻度)」 ===
        if (stepOpt === 2 && Number.isFinite(hiR) && tqR === hiR) {
            // oneTick / dispPrecision / fmt / SEP / unitText 都在本函式前面已定義
            let minDisplay = oneTick;
            let maxDisplay = Number((hiR - oneTick).toFixed(dispPrecision));

            // 防呆：避免負數或反轉
            if (!Number.isFinite(maxDisplay) || maxDisplay < minDisplay) {
                maxDisplay = minDisplay;
            }

            const eqRangeStr = `${fmt(minDisplay)} ${SEP} ${fmt(maxDisplay)}`;

            const text =
                (lang === 'en-us')
                ? `Target torque (${unitText}) is out of range. (${eqRangeStr})`
                : (lang === 'zh-cn')
                ? `目標扭力（${unitText}）超出范围，（${eqRangeStr}）`
                : `目標扭力（${unitText}）超出範圍，（${eqRangeStr}）`;

            setInvalid(tqEl);
            alertMsg(text, tqEl);
            return false;
        }


        // === ★ 新規則（無條件）：只要 HQ < TQ，就跳同一句話，且只標紅 HQ ===
        if (Number.isFinite(hiR) && Number.isFinite(tqR) && hiR < tqR) {
            const msg =
            (lang === 'en-us')
                ? `Target torque (${unitText}) must be less than the upper torque limit`
                : (lang === 'zh-cn')
                ? `目標扭力（${unitText}）需 小於 扭力上限`
                : `目標扭力（${unitText}）需 小于 扭力上限`;
            clearInlineError(tqEl);  // ✅ 不標紅 TQ
            setInvalid(hiEl);        // ✅ 只標紅 HQ
            alertMsg(msg, hiEl);
            return false;            // ✅ 直接中斷，避免被下面的分支覆蓋訊息
        }

        // ✅ 一般上限檢查（大於上限才進來；等於已由特例處理）
        if (stepOpt === 2 && Number.isFinite(hiR) && tqR > hiR) {
            const msg =
                (lang === 'en-us')
                ? `Target torque (${unitText}) is out of range. (${showLo} ${SEP} ${showHi})`
                : (lang === 'zh-cn')
                ? `目標扭力（${unitText}）超出范围，（${showLo} ${SEP} ${showHi}）`
                : `目標扭力（${unitText}）超出範圍，（${showLo} ${SEP} ${showHi}）`;
            setInvalid(tqEl);
            alertMsg(msg, tqEl);
            return false;
        }

        // ----（保留舊行為）備援範圍字串：在沒有 check_* 的情況才用 ----
        let rangeStr;
        if (Number.isFinite(hiR)) {
            const loDisp = roundTo(lo, dispPrecision);
            const hiDisp = roundTo(hi, dispPrecision);
            let minAllowed, maxAllowed, lowFmt, hiFmt;
            if (unitCode === 2) { // lbf·in
            minAllowed = roundTo(loDisp - oneTick, 2);
            maxAllowed = roundTo(hiDisp - oneTick, 2);
            lowFmt = 2; hiFmt = 2;
            } else if (unitCode === 0) { // kgf·cm
            const lo2 = roundTo(lo, 2), hi2 = roundTo(hi, 2);
            minAllowed = roundTo(lo2 - 0.01, 2);
            maxAllowed = roundTo(hi2 - 0.01, 2);
            lowFmt = 2; hiFmt = 2;
            } else { // 其他單位
            minAllowed = roundTo(loDisp + oneTick, dispPrecision);
            maxAllowed = roundTo(hiDisp - oneTick, dispPrecision);
            lowFmt = hiFmt = dispPrecision;
            }
            if (maxAllowed < minAllowed) maxAllowed = minAllowed;
            rangeStr = `${minAllowed.toFixed(lowFmt)} ~ ${maxAllowed.toFixed(hiFmt)}`;
        } else {
            rangeStr = `> ${fmt(oneTick)}`;
        }

        // ①（保持舊文案需求）：TQ > HQ 且 Lo < HQ → 顯示 (最小刻度 ~ #check_target_tor_hi)
        if (Number.isFinite(hiR) && (tqR > hiR) && (loR < hiR)) {
            const bareRange = `${fmt(oneTick)}${SEP}${showHi || fmt(hiR)}`;
            const text =
            (lang === 'en-us')
                ? `Target torque (${unitText}) is out of range. (${bareRange})`
                : (lang === 'zh-cn')
                ? `目標扭力（${unitText}）超出范围，（${bareRange}）`
                : `目標扭力（${unitText}）超出範圍，（${bareRange}）`;
            setInvalid(tqEl);
            alertMsg(text, tqEl);
            return false;
        }

        // ② / ③ TQ == Lo：優先用 check_* 範圍
        if (Number.isFinite(loR) && tqR === loR) {
            const finalRange = (cLoStr && cHiStr) ? `${cLoStr} ${SEP} ${cHiStr}` : rangeStr;
            const text =
            (lang === 'en-us')
                ? `Target torque (${unitText}) is out of range. (${finalRange})`
                : (lang === 'zh-cn')
                ? `目標扭力（${unitText}）超出范围，（${finalRange}）`
                : `目標扭力（${unitText}）超出範圍，（${finalRange}）`;
            setInvalid(tqEl);
            alertMsg(text, tqEl);
            return false;
        }

        // 通過
        clearInlineError(tqEl); clearInlineError(loEl); clearInlineError(hiEl);
        return true;
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


    function checkStepLimit(fieldId, label, quiet = false) {
        const el = document.getElementById(fieldId);
        if (!el) return;

        const raw = (el.value || '').trim();

        // 可留白
        if (raw === '') {
            el.classList.remove('is-invalid');
            const fb = el.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
            }
            return;
        }

        const n = Number(raw);
        const bad = (
            !Number.isFinite(n) ||
            !Number.isInteger(n) ||
            n < 0 || n > 99 ||
            !/^(?:[0-9]|[1-9][0-9])$/.test(raw)
        );

        // 語系
        const getCookieSafe = (name) => {
            try { const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
            catch { return null; }
        };
        let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : (getCookieSafe('language') || 'zh-tw')) || 'zh-tw';
        lang = String(lang).toLowerCase();
        if (lang === 'en') lang = 'en-us';
        if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

        const I18N_DIALOG = {
            'en-us': { title: 'Warning', msg: `Please enter: (${label}) %\nAllowed: 0 ~ 99`, ok: 'OK' },
            'zh-tw': { title: '警告',   msg: `請輸入：(${label}) %數\n允許範圍：0 ~ 99`, ok: '確定' },
            'zh-cn': { title: '警告',   msg: `请输入：(${label}) %数\n允许范围：0 ~ 99`, ok: '确定' }
        }[lang];

        const I18N_INLINE = {
            'en-us': 'Range: 0 ~ 99',
            'zh-tw': '範圍：0 ~ 99',
            'zh-cn': '范围：0 ~ 99'
        }[lang];

        if (!bad) {
            el.classList.remove('is-invalid');
            const fb = el.nextElementSibling;
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
            }
            return;
        }

        // ❌ 錯誤：標紅
        el.classList.add('is-invalid');

        const fb = el.nextElementSibling;

        if (quiet) {
            // ✅ 只顯示欄位下方提示，不彈窗
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = I18N_INLINE;
            fb.classList.add('d-block');
            fb.style.display = 'block';
            }
        } else {
            // 🔔 原行為：用彈跳視窗
            if (fb?.classList.contains('invalid-feedback')) {
            fb.innerText = '';
            fb.classList.remove('d-block');
            fb.style.display = 'none';
            }

            const flag = `_alerting_${fieldId}`;
            if (!window[flag]) {
            window[flag] = true;
            alertify
                .alert(I18N_DIALOG.title, I18N_DIALOG.msg, function () {
                try { el.focus(); el.select?.(); } catch {}
                window[flag] = false;
                })
                .set('labels', { ok: I18N_DIALOG.ok });
            }
        }

        // 驗證狀態
        isValid = false;
        if (!errorList.includes(fieldId)) errorList.push(fieldId);
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
        el.value = roundHalfUp(n, p).toFixed(p);
      //const p = parseInt(el.dataset.storePrecision || targetPrecision(), 10);
      //el.value = n.toFixed(p); // 依儲存精度四捨五入
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


window.DECIMALS_BY_UNIT ??= {
  0: 2, 
  1: 3, 
  2: 2, 
  3: 4, 
  4: 1  
};
window.EXTRA_INPUT_DECIMALS ??= 1; // 額外多顯示一位小數

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

// ✅ 共用：取得語系 + 依語系翻譯後的扭力單位文字
function getLangAndUnit() {
  try {
    if (window._getLangAndUnit) return window._getLangAndUnit(); // 你的專案版
  } catch {}
  // fallback
  const cookieLang = (typeof getCookie === 'function' && getCookie('language')) || 'zh-tw';
  const lang = String(cookieLang).toLowerCase() === 'en' ? 'en-us' : String(cookieLang).toLowerCase();
  // 粗略用 <select> 文字當單位
  const unit = document.getElementById('step_torque_unit')?.options?.[
    document.getElementById('step_torque_unit')?.selectedIndex ?? 0
  ]?.text?.trim() || 'N·m';
  return { lang: ['en-us','zh-tw','zh-cn'].includes(lang) ? lang : 'en-us', unit };
}


function getLangAndUnit() {
    
    // 先讓專案內自訂的版本可覆蓋
    try { if (typeof window._getLangAndUnit === 'function') return window._getLangAndUnit(); } catch {}

    // 語系正規化
    const raw = (typeof getCookie === 'function' && getCookie('language')) || 'en-us';
    let lang = String(raw).toLowerCase();
    if (lang === 'en') lang = 'en-us';
    if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

    // 取得扭力單位 code（優先 select，其次全域變數 torque_unit，預設 1 = N·m）
    const sel = document.getElementById('step_torque_unit');
    let code = parseInt(sel?.value ?? (typeof torque_unit !== 'undefined' ? torque_unit : 1), 10);
    if (![0,1,2,3,4].includes(code)) code = 1;

    // 多語對照表（依 code → 顯示字串）
    const UNIT_LABELS = {
        0: { 'en-us':'kgf·cm', 'zh-tw':'公斤·公分', 'zh-cn':'公斤·厘米' },
        1: { 'en-us':'N·m',    'zh-tw':'牛頓·公尺',     'zh-cn':'牛顿·米'     },
        2: { 'en-us':'lbf·in', 'zh-tw':'磅·英吋',   'zh-cn':'磅·英寸'   },
        3: { 'en-us':'kgf·m',  'zh-tw':'公斤·公尺',   'zh-cn':'公斤·米'   },
        4: { 'en-us':'cN·m',   'zh-tw':'牛頓·釐米',   'zh-cn':'牛顿·厘米'   },
    };

    const unit = (UNIT_LABELS[code]?.[lang]) || UNIT_LABELS[1][lang]; // fallback N·m

    // 也一併回傳按鈕字（給 alertify.alert/confirm 使用）
    const ui = {
        title: (lang === 'en-us') ? 'Notification' : '提示',
        ok:    (lang === 'zh-tw') ? '確定' : (lang === 'zh-cn' ? '确定' : 'OK'),
        cancel:(lang === 'en-us') ? 'Cancel' : '取消'
    };

    return { lang, unit, code, ui };
}


// ===== PATCH: Percent fields popup + i18n (unified) =====
function __getLangSafe() {
  // 優先用你專案的 getLangAndUnit()；沒有就吃 cookie；最後預設 en-us
  try {
    if (typeof getLangAndUnit === 'function') {
      let l = (getLangAndUnit().lang || '').toLowerCase();
      if (l === 'en') l = 'en-us';
      return ['en-us','zh-tw','zh-cn'].includes(l) ? l : 'en-us';
    }
  } catch {}
  try {
    const m = document.cookie.match(/(?:^|; )language=([^;]+)/);
    let l = (m ? decodeURIComponent(m[1]) : 'en-us').toLowerCase();
    if (l === 'en') l = 'en-us';
    return ['en-us','zh-tw','zh-cn'].includes(l) ? l : 'en-us';
  } catch {}
  return 'en-us';
}

const __I18N_PERCENT = {
  'en-us': {
    title: 'Warning',
    ok: 'OK',
    msg: { hi: 'Please enter (Upper limit) % — required, integer 0–99',
           lo: 'Please enter (Lower limit) % — required, integer 0–99' }
  },
  'zh-tw': {
    title: '警告',
    ok: '確定',
    msg: { hi: '請輸入：(上限)％ — 必填，0~99 的整數',
           lo: '請輸入：(下限)％ — 必填，0~99 的整數' }
  },
  'zh-cn': {
    title: '警告',
    ok: '确定',
    msg: { hi: '请输入：(上限)％ — 必填，0~99 的整数',
           lo: '请输入：(下限)％ — 必填，0~99 的整数' }
  }
};

function __isEditable(el) {
  return !!el && !el.disabled && getComputedStyle(el).display !== 'none';
}

// 回傳不合格的欄位 id 陣列；同時會顯示 alertify 視窗（多語系）
function enforcePercentRequiredWithDialogForStepOption(stepOption) {
  const lang = __getLangSafe();
  const T = __I18N_PERCENT[lang] || __I18N_PERCENT['en-us'];

  // StepOption==1 → 檢查角度％；StepOption==2 → 檢查扭力％
  const targets = (stepOption === 1)
    ? [{ id: 'step_limit_hi_ang', key: 'hi' }, { id: 'step_limit_lo_ang', key: 'lo' }]
    : (stepOption === 2)
      ? [{ id: 'step_limit_hi_tor', key: 'hi' }, { id: 'step_limit_lo_tor', key: 'lo' }]
      : [];

  if (!window.__alertOnce) window.__alertOnce = Object.create(null);

  const bad = [];

  targets.forEach(({ id, key }) => {
    const el = document.getElementById(id);
    if (!__isEditable(el)) return; // 不可編輯就不驗

    // 基本 HTML 屬性保護
    el.setAttribute('min', '0');
    el.setAttribute('max', '99');
    el.setAttribute('inputmode', 'numeric');

    const raw = (el.value || '').trim();
    const n = Number(raw);
    const ok = Number.isInteger(n) && n >= 0 && n <= 99 && /^(?:[0-9]|[1-9][0-9])$/.test(raw);

    if (!ok) {
      // 標紅、收掉 inline
      el.classList.add('is-invalid');
      const fb = el.nextElementSibling;
      if (fb?.classList.contains('invalid-feedback')) {
        fb.innerText = '';
        fb.classList.remove('d-block');
        fb.style.display = 'none';
      }

      // 避免同欄位重複彈多次
      const dedupeKey = `percent_${id}`;
      if (!window.__alertOnce[dedupeKey]) {
        window.__alertOnce[dedupeKey] = true;
        alertify
          .alert(T.title, T.msg[key], function () {
            try { el.focus(); el.select?.(); } catch {}
            window.__alertOnce[dedupeKey] = false;
          })
          .set('labels', { ok: T.ok });
      }

      bad.push(id);
    } else {
      // 合法就清錯
      el.classList.remove('is-invalid');
      const fb = el.nextElementSibling;
      if (fb?.classList.contains('invalid-feedback')) {
        fb.innerText = '';
        fb.classList.remove('d-block');
        fb.style.display = 'none';
      }
    }
  });

  return bad;
}




// ===== END PATCH =====

function enforceOffsetForOption1_Angle() {
  const stepOpt = Number(document.getElementById('StepOption')?.value || 0);
  if (stepOpt !== 1) return true; // 只在 Option 1（目標角度）驗證

  // 取值小工具
  const numById = (id) => {
    const v = document.getElementById(id)?.value;
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : null;
  };

  const offsetEl = document.getElementById('StepTorqueOffset');
  if (!offsetEl) return true;

  // 補償值（由 radio 決定正負；輸入視為幅度）
  const rawOffset    = parseFloat(offsetEl.value);
  const minusChecked = document.getElementById('join_offset_minus')?.checked ?? false;
  const sign         = minusChecked ? -1 : 1;
  const offsetMag    = Number.isFinite(rawOffset) ? Math.abs(rawOffset) : NaN;   // 幅度 ≥ 0
  const offset       = Number.isFinite(rawOffset) ? sign * offsetMag : NaN;      // 含正負

  // 規格/扭力（Option1 用「扭力上限」當基準）
  const hiTq    = numById('StepHiTorque');        // 扭力上限
  const specMin = numById('check_target_tor_lo'); // 規格下限
  const specMax = numById('check_target_tor_hi'); // 規格上限
  if (![offsetMag, offset, specMin, specMax, hiTq].every(Number.isFinite)) return true;

  // 語系/單位/精度
  let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : 'zh-tw') || 'zh-tw';
  lang = String(lang).toLowerCase(); if (lang === 'en') lang = 'en-us';
  if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';
  const unitText = (typeof getLangAndUnit === 'function' ? (getLangAndUnit().unit || 'N·m') : 'N·m');
  const OK_LABEL = (lang === 'en-us' ? 'OK' : (lang === 'zh-cn' ? '确定' : '確定'));
  const SEP      = (lang === 'en-us') ? '~' : '～';
  const TITLE    = (lang === 'en-us' ? 'Warning' : '警告');

  const pFromCfg = (typeof getTorquePrecision === 'function') ? Number(getTorquePrecision()) : NaN;
  const prec     = Math.min(Math.max(Number.isFinite(pFromCfg) ? pFromCfg : 3, 2), 3);
  const roundN   = (n, p) => { const f = 10 ** p; return Math.round(Number(n) * f) / f; };
  const fmt      = (n) => roundN(n, prec).toFixed(prec);
  const EPS      = 10 ** (-(prec + 2));

  const clearInline = () => {
    const fb = offsetEl.nextElementSibling;
    offsetEl.classList.remove('is-invalid');
    if (fb?.classList.contains('invalid-feedback')) {
      fb.innerText = ''; fb.classList.remove('d-block'); fb.style.display = 'none';
    }
  };

  const alertRange = (lo, hi) => {
    if (window._alertingOffsetOpt1) return;
    window._alertingOffsetOpt1 = true;

    const loTxt = fmt(Math.abs(lo) < EPS ? 0 : lo);
    const hiTxt = fmt(Math.abs(hi) < EPS ? 0 : hi);
    const msg = (lang === 'en-us')
      ? `Torque offset (${unitText}) is out of range, allowed (${loTxt} ${SEP} ${hiTxt}).`
      : (lang === 'zh-cn')
        ? `扭矩补偿值（${unitText}）超出范围，允许(${loTxt} ${SEP} ${hiTxt})。`
        : `扭力補償值（${unitText}）超出範圍，允許(${loTxt} ${SEP} ${hiTxt})。`;

    alertify.alert(TITLE, msg, function () {
      try { offsetEl.focus(); offsetEl.select?.(); } catch {}
      window._alertingOffsetOpt1 = false;
    }).set('labels', { ok: OK_LABEL });
  };

  clearInline();

  // ========= 規則交集 =========
  // A) |offset| ≤ 0.30 × hiTq           → offset ∈ [-0.3*hiTq, +0.3*hiTq]
  const lowerA = -0.30 * Math.max(0, hiTq);
  const upperA =  0.30 * Math.max(0, hiTq);

  // B) 0.70*specMin ≤ hiTq + offset ≤ 1.10*specMax  → offset ∈ [0.70*specMin - hiTq, 1.10*specMax - hiTq]
  const minSum = 0.70 * specMin;
  const maxSum = 1.10 * specMax; // ★ Option1 依需求用 110%
  const lowerB = minSum - hiTq;
  const upperB = maxSum - hiTq;

  // 交集
  const lowerBound = Math.max(lowerA, lowerB);
  const upperBound = Math.min(upperA, upperB);

  if (!(lowerBound <= upperBound)) return true; // 不合理就放行避免卡死

  // 實際帶符號 offset 是否在交集內
  if (offset < lowerBound - EPS || offset > upperBound + EPS) {
    offsetEl.classList.add('is-invalid');
    alertRange(lowerBound, upperBound);
    return false;
  }

  // 通過
  clearInline();
  return true;
}






document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('btnSave');
  if (!btn) {
    return;
  }
  btn.addEventListener('click', save_or_edit_step);
});


document.head.insertAdjacentHTML(
  'beforeend',
  '<style>#StepHiTorque + .invalid-feedback{display:none!important;}</style>'
);

// A) 每次輸入都清掉 invalid 旗標（防殘留）
document.getElementById('StepTorqueDownShift')?.addEventListener('input', function () {
  this.setCustomValidity && this.setCustomValidity('');
});

// B) 若你使用 type="text"（或就算是 number 也沒壞處）—保證只允許 0-9 與單一 '.'
(function () {
  const dsEl = document.getElementById('StepTorqueDownShift');
  if (!dsEl) return;
  dsEl.addEventListener('input', function () {
    const before = this.value;
    // 去掉非數字與非點
    let s = before.replace(/[^0-9.]/g, '');
    // 只保留第一個點
    const i = s.indexOf('.');
    if (i !== -1) s = s.slice(0, i + 1) + s.slice(i + 1).replace(/\./g, '');
    if (s !== before) this.value = s;
  });
})();


</script>


<script>
// 讓 #StepTorqueDownShift 無論何時都能輸入小數點（含關掉警示後）
(function installDownshiftDecimalGuard(){
  const FIELD_ID = 'StepTorqueDownShift';

  // 你專案已有 precision / getTorquePrecision 就會用，沒有就預設 3
  function currentPrecision(){
    if (typeof window.precision === 'number' && isFinite(window.precision)) return window.precision;
    if (typeof getTorquePrecision === 'function') return Number(getTorquePrecision()) || 3;
    return 3;
  }

  function makeFieldDecimalFriendly(){
    const el = document.getElementById(FIELD_ID);
    if (!el) return null;
    el.type = 'text';
    el.setAttribute('inputmode','decimal');
    el.removeAttribute('pattern');            // 避免整數限制
    el.removeAttribute('data-integer-only');  // 避免自定整數鎖
    return el;
  }

  function insertAtCursor(input, ch){
    const start = input.selectionStart ?? input.value.length;
    const end   = input.selectionEnd ?? start;
    let v       = input.value;

    // 只允許一個小數點
    if (ch === '.' && v.includes('.')) return;

    input.value = v.slice(0, start) + ch + v.slice(end);
    const pos = start + ch.length;
    try { input.setSelectionRange(pos, pos); } catch {}

    // 這次 input 事件先擋掉上層清理器（一次性）
    const onceStop = (ev) => { ev.stopImmediatePropagation(); input.removeEventListener('input', onceStop, true); };
    input.addEventListener('input', onceStop, true);

    input.dispatchEvent(new Event('input', { bubbles: true }));
  }

  // 捕獲階段最先處理「.」，確保任何整數委派都攔不到
  document.addEventListener('keydown', function(e){
    const t = e.target;
    if (!t || t.id !== FIELD_ID) return;

    const el = makeFieldDecimalFriendly();
    if (!el) return;

    const prec = currentPrecision();
    const isDot = (e.key === '.' || e.key === 'Decimal' || e.key === ',');
    if (!isDot) return;

    if (prec > 0) {
      e.preventDefault();            // 攔原生與上層
      e.stopImmediatePropagation();
      insertAtCursor(el, '.');       // 手動寫入「.」→ 立刻可見
    } else {
      e.preventDefault();
      e.stopImmediatePropagation();
    }
  }, true); // capture=true

  // 捕獲貼上：允許含小數，避免被上層委派清掉
  document.addEventListener('paste', function(e){
    const t = e.target;
    if (!t || t.id !== FIELD_ID) return;
    const el = makeFieldDecimalFriendly(); if (!el) return;

    const prec = currentPrecision();
    const text = (e.clipboardData || window.clipboardData)?.getData('text') || '';
    if (prec === 0 && /[^\d]/.test(text)) { e.preventDefault(); e.stopImmediatePropagation(); return; }
    e.stopImmediatePropagation();
  }, true);

  // 首次保險
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', makeFieldDecimalFriendly);
  } else {
    makeFieldDecimalFriendly();
  }
})();
</script>

