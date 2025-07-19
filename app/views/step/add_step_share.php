<script>
    window.addEventListener('DOMContentLoaded', () => {
        if (typeof toggleStepTorqueTS === 'function') toggleStepTorqueTS();
        if (typeof toggleDownShift === 'function') toggleDownShift();
    });

    document.addEventListener("DOMContentLoaded", function () {
        getCheckboxValue();
    });


    function updateLabel() {
        const rawUnit = '<?php echo $data['torque_unit']; ?>';
        const language = getCookie('language') || 'default';
        const selectVal = parseInt(document.getElementById('StepOption').value);
        const label = document.getElementById('targetLabel');

        // 翻譯單位
        const unitLabels = {
            'kgf.cm': { 'zh-cn': '公斤公分', 'zh-tw': '公斤公分',   'default': 'kgf.cm' },
            'lbf.in': { 'zh-cn': '英磅英吋', 'zh-tw': '英磅英吋',   'default': 'lbf.in' },
            'N.m':    { 'zh-cn': '牛顿米',   'zh-tw': '牛頓米',     'default': 'N.m' },
            'kgf.m':  { 'zh-cn': '公斤米',   'zh-tw': '公斤公尺',   'default': 'kgf.m' },
            'cN.m' :  { 'zh-cn': '厘牛米',   'zh-tw' : '厘牛頓米',  'default': 'cN.m'},
        };
        const translatedUnit = unitLabels[rawUnit]?.[language] || rawUnit;

        // 標籤文字 (改用 mapping)
        const labelTexts = {
            'zh-cn': {
                2: '目标扭矩',
                1: '目标角度'
            },
            'zh-tw': {
                2: '目標扭力',
                1: '目標角度'
            },
            'default': {
                2: 'Target Torque',
                1: 'Target Angle'
            }
        };
        const textSet = labelTexts[language] || labelTexts['default'];
        const labelPrefix = textSet[selectVal] || 'Target';

        // 更新標籤文字
        label.textContent = selectVal === 2
            ? `${labelPrefix} (${translatedUnit}):`
            : `${labelPrefix}:`;

        // 先全部隱藏
        ['StepTorque_item', 'StepAngle_item'].forEach(id => {
            document.getElementById(id)?.style.setProperty('display', 'none');
        });

        // 根據 selectVal 顯示對應區塊
        if (selectVal === 2) {
            document.getElementById('StepTorque_item')?.style.setProperty('display', 'block');
        } else if (selectVal === 1) {
            document.getElementById('StepAngle_item')?.style.setProperty('display', 'block');
        }

        // 額外區塊顯示控制
        const showTor = document.getElementById('show_tor');
        const showAng = document.getElementById('show_ang');

        if (showTor && showAng) {
            if (selectVal === 2) {
                showTor.style.display = 'block';
                showAng.style.display = 'none';
            } else{
                showTor.style.display = 'none';
                showAng.style.display = 'block';
            }
        }

        // 控制欄位 enable/disable
        const enableMap = {
            2: ['StepMoniByWin_0', 'step_limit_hi_tor', 'step_limit_lo_tor'],
            1: ['StepMoniByWin_1', 'step_limit_hi_ang', 'step_limit_lo_ang']
        };

        const allFields = [
            'StepMoniByWin_0', 'StepMoniByWin_1',
            'step_limit_hi_tor', 'step_limit_lo_tor',
            'step_limit_hi_ang', 'step_limit_lo_ang'
        ];

        // 全部先 disable
        allFields.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.disabled = true;
        });

        // 再啟用需要的欄位
        if (enableMap[selectVal]) {
            enableMap[selectVal].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.disabled = false;
            });
        }
    }






    function toggleStepTorqueTS() {
        const dataType = "<?php echo $data['type']; ?>";
        const stepTorqueTS = document.getElementById('StepTorqueTS');
        const stepTorqueTSBlock = document.getElementById('StepTorqueTS_block'); 
        const showTorque = document.getElementById('show_torque');
        const showAngle = document.getElementById('show_angle');
        const thresholdBlock = document.getElementById('threshold_block');

        const isModeOff = document.getElementById('threshold_mode_off').checked;
        const isModeTorque = document.getElementById('threshold_mode_torque').checked;
        const isModeAngle = document.getElementById('threshold_mode_angle').checked;

        // 初始全部隱藏 + 停用
        [showTorque, showAngle, stepTorqueTSBlock].forEach(el => {
            if (el) el.style.display = 'none';
        });
        if (stepTorqueTS) stepTorqueTS.disabled = true;

        // 顯示 torque 模式
        if (isModeTorque) {
            if (stepTorqueTSBlock) stepTorqueTSBlock.style.display = 'block';
            if (stepTorqueTS) {
                stepTorqueTS.disabled = false;
                if (dataType === 'new') {
                    const stepUnit = parseInt(document.getElementById('step_torque_unit')?.value ?? 1);
                    const decimals = {
                        0: 2,
                        1: 3,
                        2: 2,
                        3: 4,
                        4: 1
                    };
                    const places = decimals[stepUnit] ?? 3;
                    stepTorqueTS.value = (0).toFixed(places);
                }else{
                    const stepUnit = parseInt(document.getElementById('step_torque_unit')?.value ?? 1);
                    const decimals = {
                        0: 2,
                        1: 3,
                        2: 2,
                        3: 4,
                        4: 1
                    };
                    const places = decimals[stepUnit] ?? 3;
                    stepTorqueTS.value = (0).toFixed(places);
                }
            }
            if (showTorque) showTorque.style.display = 'block';
        }

        // 顯示 angle 模式
        else if (isModeAngle) {
            if (stepTorqueTSBlock) stepTorqueTSBlock.style.display = 'block';
            if (stepTorqueTS) stepTorqueTS.disabled = false;
            if (showAngle) showAngle.style.display = 'block';

            stepTorqueTS.value = 0;
        }

        // threshold 整塊區域
        if (thresholdBlock) {
            thresholdBlock.style.display = isModeOff ? 'none' : 'flex';
        }
    }






    function toggleDownShift() {
        
        const dataType = "<?php echo $data['type']; ?>";

        const StepTorqueDownShift = document.getElementById('StepTorqueDownShift');
        const StepRPMDownShift = document.getElementById('StepRPMDownShift');
        const StepTorqueDownShift_block = document.getElementById('StepTorqueDownShift_block');
        const showDownshiftTorque = document.getElementById('show_downshift_torque');
        const showDownshiftAngle = document.getElementById('show_downshift_angle');
        const downshiftBlock = document.getElementById('downshift_block');
        const downshiftSpeedBlock = document.getElementById('downshift_speed_block');

        const isModeOff = document.getElementById('downshift_mode_off').checked;
        const isModeTorque = document.getElementById('downshift_mode_torque').checked;
        const isModeAngle = document.getElementById('downshift_mode_angle').checked;

        // 預設全部隱藏與 disabled
        if (showDownshiftTorque) showDownshiftTorque.style.display = 'none';
        if (showDownshiftAngle) showDownshiftAngle.style.display = 'none';
        if (StepTorqueDownShift) {
            StepTorqueDownShift.disabled = true;
            StepTorqueDownShift.style.display = 'none';
        }
        if (StepRPMDownShift) StepRPMDownShift.disabled = true;
        if (StepTorqueDownShift_block) StepTorqueDownShift_block.style.display = 'none'; // ✅ 預設隱藏整塊

        // TORQUE 模式
        if (isModeTorque) {
            if (StepTorqueDownShift_block) StepTorqueDownShift_block.style.display = 'flex'; // ✅ 顯示整塊
            if (StepTorqueDownShift) {
                StepTorqueDownShift.style.display = 'block';
                StepTorqueDownShift.disabled = false;
                if (dataType === 'new') {
                    StepTorqueDownShift.value = 0;
                }
            }
            if (StepRPMDownShift) StepRPMDownShift.disabled = false;
            if (showDownshiftTorque) showDownshiftTorque.style.display = 'block';
        }

        // ANGLE 模式
        else if (isModeAngle) {
            if (StepTorqueDownShift_block) StepTorqueDownShift_block.style.display = 'flex'; // ✅ 顯示整塊
            if (StepTorqueDownShift) {
                StepTorqueDownShift.style.display = 'block';
                StepTorqueDownShift.disabled = false;
            }
            if (StepRPMDownShift) StepRPMDownShift.disabled = false;
            if (showDownshiftAngle) showDownshiftAngle.style.display = 'block';
        }

        // 控制附加區塊
        if (downshiftBlock) downshiftBlock.style.display = isModeOff ? 'none' : 'flex';
        if (downshiftSpeedBlock) downshiftSpeedBlock.style.display = isModeOff ? 'none' : 'flex';
    }



    var dataType ='<?php echo $data['type'];?>'

    if (dataType === 'new') {

        var next_step_id = "<?php echo $data['next_step_id'];?>";
        document.getElementById("STEPname").value = "STEP-" + next_step_id;
        document.getElementById("StepAngle").value = 3000;
        document.getElementById("StepTorque").value  = document.getElementById('tool_min_torque').value;
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
        document.getElementById('StepHiTorque').value = document.getElementById('tool_high_torque').value;
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
        let StepMoniByWin = getCheckboxValue();


        /*if (StepOption == 0) {
            StepAngle = 0;
        } else {
            StepTorque = 0;
        }*/
        let StepTime = 0;

        let StepLimiHi, StepLimiLo;
        if (StepMoniByWin == 0) {
            StepLimiHi = document.getElementById("step_limit_hi_tor")?.value || "30";
            StepLimiLo = document.getElementById("step_limit_lo_tor")?.value || "30";
        } else if (StepMoniByWin == 1) {
            StepLimiHi = document.getElementById("step_limit_hi_ang")?.value || "30";
            StepLimiLo = document.getElementById("step_limit_lo_ang")?.value || "30";
        } else {
            StepLimiHi = "30";
            StepLimiLo = "30";
        }


        let interrupt_alarm = document.querySelector('input[name="interrupt_alarm"]:checked');
        let over_angle_stop = document.querySelector('input[name="over_angle_stop"]:checked');
        let StepDirection = document.querySelector('input[name="StepDirection"]:checked');
        let StepDelay = document.getElementById("StepDelay").value;
        let StepRPM = document.getElementById("StepRPM").value;
        let KValue = document.getElementById("k_value").value;
        let StepTorqueOffsetSign = document.querySelector('input[name="StepTorqueOffsetSign"]:checked');
        let StepTorqueOffset = document.getElementById("StepTorqueOffset").value;
        let StepEnableDownShift = document.querySelector('input[name="StepEnableDownShift"]:checked')?.value ?? null;
        let StepEnableThreshold = document.querySelector('input[name="StepEnableThreshold"]:checked')?.value ?? null;
        let StepTorqueTS = document.getElementById("StepTorqueTS").value;
        let StepTorqueDownShift = document.getElementById("StepTorqueDownShift").value;
        let StepRPMDownShift = document.getElementById("StepRPMDownShift").value;
        let step_unit = document.getElementById("step_torque_unit").value;
        let time = new Date().toISOString().slice(0, 19).replace('T', ' ');

        const lang = getCookie('language') || 'en';
        const i18n = {
            'en': {
                title: "Warning",
                threshold: "This will remove all existing steps' Threshold settings. Continue?",
                downshift: "This will remove all existing steps' Downshift settings. Continue?",
                cancel: "Cancel",
                ok: "OK"
            },
            'zh-tw': {
                title: "警告",
                threshold: "此操作將移除所有既有步驟的 Threshold 設定，是否繼續？",
                downshift: "此操作將移除所有既有步驟的 Downshift 設定，是否繼續？",
                cancel: "取消",
                ok: "確定"
            },
            'zh-cn': {
                title: "警告",
                threshold: "此操作将移除所有既有步骤的 Threshold 设置，是否继续？",
                downshift: "此操作将移除所有既有步骤的 Downshift 设置，是否继续？",
                cancel: "取消",
                ok: "确定"
            }
        };


        const text = i18n[lang] || i18n['en'];
        const Tool_Max_Torque = parseFloat(document.getElementById('tool_max_torque').value);
        const Tool_Min_Torque = parseFloat(document.getElementById('tool_min_torque').value);
        const Tool_Max_RPM = parseFloat(document.getElementById('tool_max_rpm').value);
        const Tool_Min_RPM = parseFloat(document.getElementById('tool_min_rpm').value);
        const Tool_Max_Torque_Diff = parseFloat(document.getElementById('tool_max_torque_diff').value);
        
        let check = input_check();
        console.log(check);
    
        
        if (check.valid) {
            if (StepEnableThreshold !== "0") {
                alertify.confirm(
                    text.title,
                    text.threshold,
                    function () {
                        submit_step_ajax();
                    },
                    function () {
                        return;
                    }
                ).set('labels', {ok: text.ok, cancel: text.cancel});
            }else if(StepEnableDownShift !=="0"){
                alertify.confirm(
                    text.title,
                    text.downshift,
                    function () {
                        submit_step_ajax();
                    },
                    function () {
                        return;
                    }
                ).set('labels', {ok: text.ok, cancel: text.cancel});

            }else {
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
            data.append("step_unit",step_unit);

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


    function getCheckboxValue(clickedId) {
        var checkbox0 = document.getElementById("StepMoniByWin_0");
        var checkbox1 = document.getElementById("StepMoniByWin_1");
        var stepHiTorque = document.getElementById("StepHiTorque");
        var stepLoTorque = document.getElementById("StepLoTorque");
        var stepHiAngle = document.getElementById("StepHiAngle");
        var stepLoAngle = document.getElementById("StepLoAngle");

        var check_val = -1;

        if (clickedId === "StepMoniByWin_0") {
            checkbox0.checked = true;
            checkbox1.checked = false;
            check_val = 0;
        } else if (clickedId === "StepMoniByWin_1") {
            checkbox0.checked = false;
            checkbox1.checked = true;
            check_val = 1;
        }

        var disableTorque = check_val === 0;
        var disableAngle = check_val === 1;

        [stepHiTorque, stepLoTorque].forEach(function (el) {
            if (el) el.disabled = disableTorque;
        });
        [stepHiAngle, stepLoAngle].forEach(function (el) {
            if (el) el.disabled = disableAngle;
        });

        console.log(check_val);
        return check_val;
    }




    

    function input_check() {


        const decimals = {
            0: 2,
            1: 3,
            2: 2,
            3: 4,
            4: 1
        };

        const torque_unit = parseInt(document.getElementById('step_torque_unit')?.value ?? 1);
        const precision = decimals[torque_unit] ?? 3;
        const increment = parseFloat((1 / Math.pow(10, precision)).toFixed(precision));


        const Tool_Max_Torque = document.getElementById('check_target_tor_hi').value; //target_tor
        const Tool_Min_Torque = document.getElementById('check_target_tor_lo').value; //target_tor

        const check_hi_tor_before = document.getElementById('check_hi_tor_before').value; //check_hi_tor_before
        const check_hi_tor_after  = document.getElementById('check_hi_tor_after').value; //check_hi_tor_after


        const check_lo_tor_before = document.getElementById('check_lo_tor_before').value; //check_lo_tor_before
        const check_lo_tor_after  = document.getElementById('check_lo_tor_after').value; //check_lo_tor_after

        const Tool_Max_RPM = parseFloat(document.getElementById('tool_max_rpm').value);
        const Tool_Min_RPM = parseFloat(document.getElementById('tool_min_rpm').value);
        const Tool_Max_Torque_Diff = parseFloat(document.getElementById('tool_max_torque_diff').value);


        const idsToCheck = ['tool_max_torque', 'tool_min_torque', 'tool_max_rpm', 'tool_min_rpm', 'tool_max_torque_diff'];

        let toolError = false;
        idsToCheck.forEach(id => {
            const el = document.getElementById(id);
            const value = parseFloat(el.value);
            const feedback = el.nextElementSibling;
            if (isNaN(value)) {
                el.classList.add("is-invalid");
                if (feedback && feedback.classList.contains("invalid-feedback")) {
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

        if (toolError) {
            return { valid: false, errors: ["Tool config invalid"] };
        }

        const StepOption = parseInt(document.getElementById("StepOption").value);
        const StepMoniByWin = getCheckboxValue();
        const StepTorqueVal = parseFloat(document.getElementById("StepTorque").value);
        const delta = Tool_Min_Torque * 0.05;

        const StepEnableThreshold = document.querySelector('input[name="StepEnableThreshold"]:checked')?.value ?? "0";
        const StepEnableDownShift = document.querySelector('input[name="StepEnableDownShift"]:checked')?.value ?? "0";

        const limits = {
            torque: {
                torque: { min: Tool_Min_Torque, max: Tool_Max_Torque },
                torqueTS: { min: check_lo_tor_before, max: Tool_Min_Torque },
                torqueDownshift: { min: 0, max: Tool_Min_Torque },
                rpmDownshift: { min: Tool_Min_RPM, max: Tool_Max_RPM },
                limitHi: { min: check_hi_tor_before, max: check_hi_tor_after },
                limitLo: { min: check_lo_tor_before, max: check_lo_tor_after }
            },
            angle: {
                angle: { min: 1, max: 30600 },
                rpmDownshift: { min: Tool_Min_RPM, max: Tool_Max_RPM },
                limitHi: { min: parseFloat(document.getElementById("StepAngle")?.value || 0), max: 30600 },
                limitLo: { min: 0, max: parseFloat(document.getElementById("StepHiAngle")?.value || 0) }
            }
        };

        let conditions = [
            { id: 'StepRPM', pattern: /^\d{1,4}$/, min: Tool_Min_RPM, max: Tool_Max_RPM },
            //{ id: 'k_value', pattern: /^(0(\.\d{1,2})?|1(\.\d{2})?|2(\.([0-4]{1}[0-9]{1}|50)))$/, min: 0, max: 2.5 },
            { id: 'StepRPMDownShift', pattern: /^\d{1,4}$/, ...limits.torque.rpmDownshift },
            { id: 'StepTorqueDownShift', pattern: /^\d{1,4}(\.\d{1})?$/, ...limits.torque.torqueDownshift },
            { id: 'StepTorqueTS', pattern: /^\d{1,4}(\.\d{1})?$/, ...limits.torque.torqueTS }
        ];

        if (StepEnableThreshold === "0") {
            conditions = conditions.filter(c => c.id !== 'StepTorqueTS');
        }

        if (StepEnableDownShift === "0") {
            conditions = conditions.filter(c => !['StepTorqueDownShift', 'StepRPMDownShift'].includes(c.id));
        }

        if (StepEnableThreshold === "1") {
            const ts = conditions.find(c => c.id === 'StepTorqueTS');
            if (ts) ts.min = 0, ts.max = 99999;
        }

        if (StepEnableDownShift === "1") {
            const td = conditions.find(c => c.id === 'StepTorqueDownShift');
            if (td) td.min = 0, td.max = 99999;
        }

        if (StepEnableDownShift === "2") {
            const td = conditions.find(c => c.id === 'StepTorqueDownShift');
            if (td) td.min = Tool_Min_Torque, td.max = Tool_Max_Torque;
        }

        if (StepOption === 2) {
            conditions.push(
                { id: 'StepTorque', pattern: /^\d{1,5}(\.\d{1,4})?$/, ...limits.torque.torque },
                { id: 'StepHiTorque', pattern: /^\d{1,6}(\.\d{1,4})?$/, ...limits.torque.limitHi },
                { id: 'StepLoTorque', pattern: /^\d{1,6}(\.\d{1,4})?$/, ...limits.torque.limitLo },
                { id: StepMoniByWin == 0 ? 'step_limit_hi_tor' : 'step_limit_hi_ang', pattern: /^\d{1,3}$/, min: 0, max: 100 },
                { id: StepMoniByWin == 0 ? 'step_limit_lo_tor' : 'step_limit_lo_ang', pattern: /^\d{1,3}$/, min: 0, max: 100 }
            );
        } else if (StepOption === 1) {
            conditions.push(
                { id: 'StepAngle', pattern: /^\d{1,5}$/, ...limits.angle.angle },
                { id: 'StepHiAngle', pattern: /^\d{1,5}$/, ...limits.angle.limitHi },
                { id: 'StepLoAngle', pattern: /^\d{1,5}$/, ...limits.angle.limitLo },
                { id: StepMoniByWin == 0 ? 'step_limit_hi_tor' : 'step_limit_hi_ang', pattern: /^\d{1,3}$/, min: 0, max: 100 },
                { id: StepMoniByWin == 0 ? 'step_limit_lo_tor' : 'step_limit_lo_ang', pattern: /^\d{1,3}$/, min: 0, max: 100 }
            );
        }


        //新增 StepDelay 驗證 (共用在兩種 StepOption)
        conditions.push({
            id: 'StepDelay',
            pattern: /^(?:[0-9](?:\.\d)?|9\.9)$/, 
            min: 0,
            max: 9.9
        });


        // ✅ validateInput 子函數
        function validateInput(el, pattern, min, max) {
            if (!el) return false;
            const val = el.value.trim();
            const parsed = parseFloat(val);
            const feedback = el.nextElementSibling;

            const roundTo = (num, digits) => {
                const parsed = parseFloat(num);
                if (isNaN(parsed)) return NaN;
            return parsed.toFixed(digits); 
            };


            const parsedRounded = roundTo(parsed, precision);
            const minRounded = (min !== null && !isNaN(min)) ? roundTo(min, precision) : null;
            const maxRounded = (max !== null && !isNaN(max)) ? roundTo(max, precision) : null;

            const invalid = (
                val === "" || isNaN(parsed) ||
                (minRounded !== null && parsedRounded < minRounded) ||
                (maxRounded !== null && parsedRounded > maxRounded) ||
                !pattern.test(val)
            );

            if (invalid) {
                el.classList.add("is-invalid");
                if (feedback && feedback.classList.contains("invalid-feedback")) {
                    feedback.innerText =
                        (minRounded !== null && maxRounded !== null)
                            ? `Range: ${minRounded} ~ ${maxRounded}`
                            : `Invalid input`;
                    feedback.classList.add("d-block");
                    feedback.style.display = "block";
                }
            } else {
                el.classList.remove("is-invalid");
                if (feedback && feedback.classList.contains("invalid-feedback")) {
                    feedback.innerText = '';
                    feedback.classList.remove("d-block");
                    feedback.style.display = "none";
                }
            }

            return !invalid;
        }


        // 執行驗證
        let isValid = true;
        let errorList = [];

        conditions.forEach(cond => {
            const el = document.getElementById(cond.id);
            const valid = validateInput(el, cond.pattern, cond.min, cond.max);
            if (!valid) {
                isValid = false;
                errorList.push(cond.id);
            }
        });

        return {
            valid: isValid,
            errors: errorList
        };
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



</script>

