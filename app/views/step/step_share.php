<script>
    
  function toggleStepTorqueTS() {
    const stepTorqueTS = document.getElementById('StepTorqueTS');
    const showTorque = document.getElementById('show_torque');
    const showAngle = document.getElementById('show_angle');
    
    // 預設隱藏 show_torque，顯示 show_angle
    showTorque.style.display = 'none';
    showAngle.style.display = 'none';

    if (document.getElementById('threshold_mode_off').checked) {
        // 如果選擇了 "off" 模式
        stepTorqueTS.disabled = true;
        stepTorqueTS.value = 0;     
        showTorque.style.display = 'block'; // 顯示扭力
    } else if (document.getElementById('threshold_mode_torque').checked) {
        // 如果選擇了 "torque" 模式
        stepTorqueTS.disabled = false;
        stepTorqueTS.value = document.getElementById('tool_min_torque').value; // 設置最小扭力值
        showTorque.style.display = 'block'; // 顯示扭力
    } else if (document.getElementById('threshold_mode_angle').checked) {
        // 如果選擇了 "angle" 模式
        showAngle.style.display = 'block'; // 顯示角度
    }
}


function toggleDownShift() {
    const StepTorqueDownShift = document.getElementById('StepTorqueDownShift');
    const StepRPMDownShift = document.getElementById('StepRPMDownShift');
    const showDownshiftTorque = document.getElementById('show_downshift_torque');
    const showDownshiftAngle = document.getElementById('show_downshift_angle');

    // 預設隱藏所有顯示元素
    showDownshiftTorque.style.display = 'none';
    showDownshiftAngle.style.display = 'none';

    // 設置元素是否禁用的狀態
    StepTorqueDownShift.disabled = false;
    StepRPMDownShift.disabled = false;

    // 根據所選模式來控制顯示和禁用狀態
    if (document.getElementById('downshift_mode_off').checked) {
        // "off" 模式：禁用所有相關元素，顯示扭力
        StepTorqueDownShift.disabled = true;  
        StepRPMDownShift.disabled = true;    
        showDownshiftTorque.style.display = 'block'; 
    } else if (document.getElementById('downshift_mode_torque').checked) {
        // "torque" 模式：啟用所有相關元素，顯示扭力
        showDownshiftTorque.style.display = 'block'; 
        StepTorqueDownShift.value = document.getElementById('tool_min_torque').value; 
    } else if (document.getElementById('downshift_mode_angle').checked) {
        // "angle" 模式：顯示角度
        showDownshiftAngle.style.display = 'block'; 
    }
}

//新增 step的預設值
function setDefaultStepFormValues() {
    const settings = {
        "interrupt_alarm_off": true,
        "over_angle_stop_off": true,
        "StepDirection_cw": true,
        "join_offset_plus": true,
        "threshold_mode_off": true,
        "downshift_mode_off": true,
        "StepLimiHi": 0,
        "StepLimiLo": 0,
        "StepHiAngle": 0,
        "StepLoAngle": 0,
        "StepDelay": 0,
        "StepRPM": 100,
        "k_value": 0,
        "StepTorqueTS": 0,
        "StepTorqueDownShift": 0,
        "StepRPMDownShift": 100,
        "StepTorqueOffset": 0.0
    };

    for (const id in settings) {
        const el = document.getElementById(id);
        if (!el) continue;

        if (typeof settings[id] === "boolean") {
            el.checked = settings[id];
        } else {
            el.value = settings[id];
        }
    }
}


var dataType = "<?php echo $data['type']; ?>";
if (dataType === 'new') {
    setDefaultStepFormValues();
}
if (dataType === 'new' || dataType === 'edit') {
    updateLabel();
}


function updateLabel() {
    var StepTorque_value = '<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorque'] : '0'; ?>';
    var StepAngle_value = '<?php echo ($data['type'] == 'edit') ? $data['step']['StepAngle'] : '0'; ?>';
    var StepTime_value = '<?php echo ($data['type'] == 'edit') ? $data['step']['StepTime'] : '0'; ?>';
    var unit = '<?php echo $data['torque_unit']; ?>';
    
    const select_val = document.getElementById('StepOption');
    const label = document.getElementById('targetLabel');
    const input_name = document.getElementsByName('targetLabel')[0];

    var language = getCookie('language');


    const unitMapping = {
        'kgf.cm': {
            'zh-cn': '公斤公分',
            'zh-tw': '公斤公分',
            'default': 'kgf.cm'
        },
        'lbf.in': {
            'zh-cn': '英磅英吋',
            'zh-tw': '英磅英吋',
            'default': 'lbf.in'
        },
        'N.m': {
            'zh-cn': '牛顿米',
            'zh-tw': '牛頓米',
            'default': 'N.m'
        },
        'kgf.m': {
            'zh-cn': '公斤米',
            'zh-tw': '公斤公尺',
            'default': 'kgf.m'
        }
    };

    
    unit = unitMapping[unit] ? unitMapping[unit][language] || unitMapping[unit]['default'] : unit;

    
    const labelMapping = {
        'zh-cn': {
            0: `目标扭矩 (${unit}):`,
            1: '目标角度:',
            2: '目标时间:'
        },
        'zh-tw': {
            0: `目標扭力 (${unit}):`,
            1: '目標角度:',
            2: '目標時間:'
        },
        'default': {
            0: `Target Torque (${unit}):`,
            1: 'Target Angle:',
            2: 'Target Time:'
        }
    };
    
    label.textContent = labelMapping[language] ? labelMapping[language][select_val.value] : labelMapping['default'][select_val.value];

    
    document.getElementById('StepTorque_item').style.display = select_val.value == 0 ? 'block' : 'none';
    document.getElementById('StepAngle_item').style.display = select_val.value == 1 ? 'block' : 'none';
    document.getElementById('StepTime_item').style.display = select_val.value == 2 ? 'block' : 'none';
}

function save_step() {
    const data = new FormData();
    const time = new Date().toISOString().slice(0, 19).replace('T', ' ');

    function getInputValue(id) {
        const el = document.getElementById(id);
        return el ? el.value : '';
    }

    function getRadioValue(name) {
        const el = document.querySelector(`input[name="${name}"]:checked`);
        return el ? el.value : '';
    }

    function appendIfExists(key, value) {
        if (value !== null && value !== undefined) {
            data.append(key, value);
        }
    }

    // 普通欄位
    appendIfExists("JOBID", getInputValue("JOBID"));
    appendIfExists("SEQID", getInputValue("SEQID"));
    appendIfExists("StepSelect", getInputValue("StepSelect"));
    appendIfExists("STEPname", getInputValue("STEPname"));
    appendIfExists("StepOption", getInputValue("StepOption"));
    appendIfExists("StepTorque", getInputValue("StepTorque"));
    appendIfExists("StepHiTorque", getInputValue("StepHiTorque"));
    appendIfExists("StepLoTorque", getInputValue("StepLoTorque"));
    appendIfExists("StepMoniByWin", getCheckboxValue());
    appendIfExists("StepLimiHi", getInputValue("StepLimiHi"));
    appendIfExists("StepLimiLo", getInputValue("StepLimiLo"));
    appendIfExists("StepDelay", getInputValue("StepDelay"));
    appendIfExists("StepRPM", getInputValue("StepRPM"));
    appendIfExists("KValue", getInputValue("k_value"));
    appendIfExists("StepTorqueOffset", getInputValue("StepTorqueOffset"));
    appendIfExists("StepTorqueTS", getInputValue("StepTorqueTS"));
    appendIfExists("StepTorqueDownShift", getInputValue("StepTorqueDownShift"));
    appendIfExists("StepRPMDownShift", getInputValue("StepRPMDownShift"));
    appendIfExists("time", time);

    // radio/checkbox
    appendIfExists("InterruptAlarm", getRadioValue("interrupt_alarm"));
    appendIfExists("OverAngleStop", getRadioValue("over_angle_stop"));
    appendIfExists("StepDirection", getRadioValue("StepDirection"));
    appendIfExists("StepTorqueOffsetSign", getRadioValue("StepTorqueOffsetSign"));
    appendIfExists("StepEnableThreshold", getRadioValue("StepEnableThreshold"));
    appendIfExists("StepEnableDownShift", getRadioValue("StepEnableDownShift"));

    // 驗證與 AJAX
    if (input_check_step()) {
        $.ajax({
            url: '?url=Step/create_step',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
                const responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function () {
                    const job_id = getInputValue("JOBID");
                    const seq_id = getInputValue("SEQID");
                    success_response_seq(response, 'spinner', '../public/?url=Step/index/' + job_id + '/' + seq_id);
                });
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }
}

function edit_step() {
    const data = new FormData();
    const time = new Date().toISOString().slice(0, 19).replace('T', ' ');

    // 幫助函式：取 input 值
    const getInputValue = id => {
        const el = document.getElementById(id);
        return el ? el.value : '';
    };

    // 幫助函式：取 radio 值
    const getRadioValue = name => {
        const el = document.querySelector(`input[name="${name}"]:checked`);
        return el ? el.value : '';
    };

    // 幫助函式：append key/value (避免 null/undefined)
    const appendIfExists = (key, value) => {
        if (value !== null && value !== undefined) {
            data.append(key, value);
        }
    };

    // === 資料組裝 ===
    appendIfExists("JOBID", getInputValue("JOBID"));
    appendIfExists("SEQID", getInputValue("SEQID"));
    appendIfExists("StepSelect", getInputValue("StepSelect"));
    appendIfExists("STEPname", getInputValue("STEPname"));
    appendIfExists("time", time);
    appendIfExists("StepOption", getInputValue("StepOption"));
    appendIfExists("StepAngle", getInputValue("StepAngle"));
    appendIfExists("StepTorque", getInputValue("StepTorque"));
    appendIfExists("StepHiTorque", getInputValue("StepHiTorque"));
    appendIfExists("StepLoTorque", getInputValue("StepLoTorque"));
    appendIfExists("StepMoniByWin", getCheckboxValue());
    appendIfExists("StepLimiHi", getInputValue("StepLimiHi"));
    appendIfExists("StepLimiLo", getInputValue("StepLimiLo"));
    appendIfExists("InterruptAlarm", getRadioValue("interrupt_alarm"));
    appendIfExists("OverAngleStop", getRadioValue("over_angle_stop"));
    appendIfExists("StepDirection", getRadioValue("StepDirection"));
    appendIfExists("StepDelay", getInputValue("StepDelay"));
    appendIfExists("StepRPM", getInputValue("StepRPM"));
    appendIfExists("KValue", getInputValue("k_value"));
    appendIfExists("StepTorqueOffset", getInputValue("StepTorqueOffset"));
    appendIfExists("StepTorqueOffsetSign", getRadioValue("StepTorqueOffsetSign"));
    appendIfExists("StepEnableThreshold", getRadioValue("StepEnableThreshold"));
    appendIfExists("StepTorqueTS", getInputValue("StepTorqueTS"));
    appendIfExists("StepEnableDownShift", getRadioValue("StepEnableDownShift"));
    appendIfExists("StepTorqueDownShift", getInputValue("StepTorqueDownShift"));
    appendIfExists("StepRPMDownShift", getInputValue("StepRPMDownShift"));

    // === 驗證後送出 ===
    const check_step = input_check_step();  
    if (check_step) {
        $.ajax({
            url: '?url=Step/edit_step',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
                const responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function () {
                    const job_id = getInputValue("JOBID");
                    const seq_id = getInputValue("SEQID");
                    success_response_seq(response, 'spinner', '../public/?url=Step/index/' + job_id + '/' + seq_id);
                });
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }
}



function input_check_step() {
    const StepOption = parseInt(document.getElementById("StepOption").value);
    const Tool_Max_Torque = parseFloat(document.getElementById('tool_max_torque').value);
    const Tool_Min_Torque = parseFloat(document.getElementById('tool_min_torque').value);
    const Tool_Max_RPM = parseInt(document.getElementById('tool_max_rpm').value);
    const Tool_Min_RPM = parseInt(document.getElementById('tool_min_rpm').value);
    const delta = parseFloat((Tool_Min_Torque * 0.05).toFixed(4));

    // 計算動態限制條件
    const dynamicLimits = getDynamicLimits(StepOption, Tool_Min_Torque, Tool_Max_Torque, delta);

    // 基本通用欄位驗證規則
    const baseConditions = [
        { id: 'STEPname', pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/, min: null, max: null },
        { id: 'StepDelay', pattern: /^\d{0,4}$/, min: 0, max: 2000 },
        { id: 'StepRPM', pattern: /^\d{0,4}$/, min: Tool_Min_RPM, max: Tool_Max_RPM },
        { id: 'k_value', pattern: /^(0(\.\d{1,2})?|1(\.\d{2})?|2(\.([0-4]{1}[0-9]{1}|50)))$/, min: 0, max: 2.5 },
        { id: 'StepRPMDownShift', pattern: /^\d{0,4}$/, min: Tool_Min_RPM, max: Tool_Max_RPM },
        { id: 'StepTorqueTS', pattern: /^\d{0,4}(\.\d{1})?$/, min: Tool_Min_Torque, max: Tool_Max_Torque },
        { id: 'StepTorqueDownShift', pattern: /^\d{0,4}(\.\d{1})?$/, min: Tool_Min_Torque, max: Tool_Max_Torque },
        { id: 'StepHiTorque', pattern: /^\d{0,6}(\.\d{0,4})?$/, min: dynamicLimits.lo_torque_min, max: dynamicLimits.hi_torque_max },
        { id: 'StepLoTorque', pattern: /^\d{0,6}(\.\d{0,4})?$/, min: dynamicLimits.lo_torque_min, max: dynamicLimits.hi_torque_max },
        { id: 'StepHiAngle', pattern: /^\d{0,5}$/, min: dynamicLimits.hi_angle_min, max: dynamicLimits.hi_angle_max },
        { id: 'StepLoAngle', pattern: /^\d{1,6}$/, min: dynamicLimits.lo_angle_min, max: dynamicLimits.lo_angle_max },
        { id: 'StepLimiHi', pattern: /^\d{0,3}$/, min: 0, max: 100 },
        { id: 'StepLimiLo', pattern: /^\d{0,3}$/, min: 0, max: 100 },
    ];

    // 根據 StepOption 加入特定欄位
    if (StepOption === 0) {
        baseConditions.push({ id: 'StepTorque', pattern: /^\d{1,5}(\.\d{1})?$/, min: Tool_Min_Torque, max: Tool_Max_Torque });
    } else if (StepOption === 1) {
        baseConditions.push({ id: 'StepAngle', pattern: /^\d{1,5}$/, min: 1, max: 30600 });
    } else if (StepOption === 2) {
        baseConditions.push({ id: 'StepTime', pattern: /^\d{1,5}$/, min: 0, max: 20 });
    }

    // 開始驗證流程
    let isFormValid = true;
    baseConditions.forEach(input => {
        const result = validateField(input);
        if (!result) isFormValid = false;
    });

    return isFormValid;
}

// ✅ 動態條件限制邏輯
function getDynamicLimits(option, minTor, maxTor, delta) {
    let limits = {
        hi_angle_max: 30600,
        hi_angle_min: 0,
        lo_angle_max: 0,
        lo_angle_min: 0,
        hi_torque_max: (maxTor * 1.1).toFixed(4),
        lo_torque_min: 0
    };

    if (option === 0) {
        limits.hi_torque_min = (parseFloat(document.getElementById('StepTorque').value) + delta).toFixed(4);
        limits.lo_torque_max = (parseFloat(document.getElementById('StepTorque').value) - delta).toFixed(4);
    } else if (option === 1) {
        limits.hi_angle_min = parseInt(document.getElementById('StepAngle').value) || 0;
        limits.lo_angle_max = parseInt(document.getElementById('StepHiAngle').value) || 0;
        limits.lo_torque_max = (limits.lo_angle_max - delta).toFixed(4);
    } else if (option === 2) {
        limits = {
            hi_angle_max: 99999,
            hi_angle_min: 0,
            lo_angle_max: 99999,
            lo_angle_min: 0,
            hi_torque_max: 99999,
            lo_torque_max: 99999,
            lo_torque_min: 0
        };
    }

    return limits;
}

// ✅ 驗證單一欄位
function validateField(input) {
    const element = document.getElementById(input.id);
    if (!element) return true;

    const value = element.value.trim();
    const numberValue = parseFloat(value);
    const isEmpty = value === '';
    const isInvalidFormat = !input.pattern.test(value);
    const isBelowMin = input.min !== null && numberValue < input.min;
    const isAboveMax = input.max !== null && numberValue > input.max;

    const isInvalid = isEmpty || isInvalidFormat || isBelowMin || isAboveMax;

    if (input.id !== 'STEPname') {
        const next = element.nextElementSibling;
        if (next) next.innerHTML = `${input.min ?? '-'} ~ ${input.max ?? '-'}`;
    }

    element.classList.toggle('is-invalid', isInvalid);
    return !isInvalid;
}



function getCheckboxValue() {
    var checkbox0 = document.getElementById("StepMoniByWin_0");
    var checkbox1 = document.getElementById("StepMoniByWin_1");

    var check_val = -1;
    if (checkbox0.checked) {
        checkbox1.checked = false; 

        check_val = 0;
    } else if (checkbox1.checked) {
        checkbox0.checked = false; 
        check_val = 1;
    } else {
    }
    return check_val;
} 

</script>