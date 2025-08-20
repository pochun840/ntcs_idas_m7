<script>

    window.roundHalfUp = function (val, digits) {
        const n = Number(val);
        if (!Number.isFinite(n)) return NaN;
        const f = Math.pow(10, digits);
        const eps = 1 / (f * 1000);       // 比 1/f 小很多，推開二進位誤差
        return Math.round((n + eps) * f) / f;
    };

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



    var dataType = "<?php echo $data['type']; ?>";
    var seq_unit = "<?php echo $data['torque_unit_code'];?>";
    window.onload = function() {
        if (dataType === 'new') {
            //SEQ頁面 預設值
            document.getElementById("seq_repeat").value = 1;
            document.getElementById("timeout").value = 20;
            document.getElementById("dt_time").value = 0;
            document.getElementById("tt_time").value = 0;
            document.getElementById("ok_seq_on").checked = true;
            document.getElementById("seq_stop_off").checked = true;
            document.getElementById("unscrew_count_switch_off").checked = true;
            document.getElementById("ng_unscrew_off").checked = true;
            document.getElementById("accu_angle_on").checked = true;
            document.getElementById("unscrew_mode_auto").checked = true;
            document.getElementById("unscrew_rpm").value = 300;
            document.getElementById("unscrew_dir_ccw").checked = true;
            document.getElementById("unscrew_forcemode_on").checked = true;
            document.getElementById("unscrew_angle_threshold").value = 0;
            document.getElementById("unscrew_force").value = 50;

            if(seq_unit ==0 ){
                document.getElementById('unscrew_torque_threshold').value = "0.00";
            }
            if(seq_unit == 1){
                document.getElementById('unscrew_torque_threshold').value = "0.000";
            }
            if(seq_unit == 2){
                document.getElementById('unscrew_torque_threshold').value = "0.000";
            }

            if(seq_unit == 3){
                document.getElementById('unscrew_torque_threshold').value = "0.0000";
            }

             if(seq_unit == 4){
                document.getElementById('unscrew_torque_threshold').value = "0.0";
            }


            for (let i = 1; i <= 5; i++) {
                document.getElementById("Thread_Calcu_" + i).checked = true;
            }

            ['unscrew_rpm','unscrew_angle_threshold' ,'unscrew_torque_threshold', 'unscrew_dir_cw', 'unscrew_dir_ccw', 'unscrew_forcemode_on', 'unscrew_forcemode_unlimit', 'unscrew_forcemode_off', 'unscrew_force'].forEach(id => document.getElementById(id).disabled = true);
         
        }

        if(dataType === 'edit'){
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            let displayedValue = '<?php echo isset($data['sequences']['Thread_Calcu']) ? $data['sequences']['Thread_Calcu'] : ''; ?>';
            setCheckboxesByValue(displayedValue);

        }
        
    };
    
    // 儲存與編輯 sequence 共用函式
    function buildSequenceFormData() {
        const data = new FormData();
        const time = new Date().toISOString().slice(0, 19).replace('T', ' ');

        const seq_unit = "<?php echo $data['torque_unit_code'];?>";
     

        data.append("job_id", document.getElementById("job_id").value);
        data.append("SEQID", document.getElementById("seq_id").value);
        data.append("SEQname", document.getElementById("SEQname").value);
        data.append("time", time);
        data.append("type", 0);
        data.append("act", 1);
        data.append("skip", 0);
        data.append("seq_repeat", document.getElementById("seq_repeat").value);
        data.append("timeout", document.getElementById("timeout").value);
        data.append("dt_time", document.getElementById("dt_time").value);
        data.append("tt_time", document.getElementById("tt_time").value);

        data.append("ok_seq_val", document.querySelector('input[name="ok_seq"]:checked')?.value ?? null);
        data.append("ok_stop_val", document.querySelector('input[name="ok_stop"]:checked')?.value ?? null);
        data.append("countType", 1);
        data.append("ok_screw", 1);
        data.append("ng_stop", document.getElementById('ng_stop').value);
        data.append("ng_unscrew_val", document.querySelector('input[name="ng_unscrew"]:checked')?.value ?? null);
        data.append("interrupt_alarm", 1);
        data.append("accu_angle_val", document.querySelector('input[name="accu_angle"]:checked')?.value ?? null);
        data.append("angle_calculation_data", getCheckboxValue_seq());
        data.append("unscrew_mode_val", document.querySelector('input[name="unscrew_mode"]:checked')?.value ?? null);
        data.append("unscrew_forcemode_val", document.querySelector('input[name="unscrew_forcemode"]:checked')?.value ?? null);
        data.append("unscrew_force", document.getElementById("unscrew_force").value);
        data.append("unscrew_rpm", document.getElementById("unscrew_rpm").value);
        data.append("unscrew_torque_threshold", document.getElementById("unscrew_torque_threshold").value);
        data.append("unscrew_angle_threshold", document.getElementById("unscrew_angle_threshold").value);
        data.append("unscrew_dir_val", document.querySelector('input[name="unscrew_dir"]:checked')?.value ?? 0);
        data.append("image", '');
        data.append("message",'');
        data.append("delay", 0);
        data.append("input", 0);
        data.append("input_signal", 1);
        data.append("output", 0);
        data.append("output_signal", 1);
        data.append("output_durat", 100);
        data.append("addtion", '');
        data.append("unscrew_count_switch_val", document.querySelector('input[name="unscrew_count_switch"]:checked')?.value ?? null);
        data.append("seq_unit",seq_unit);

        
        return data;
    }

    // 新增 sequence
    function save_sequence() {


        const check = input_check_seq();
        if (!check) return;

        const data = buildSequenceFormData();
        document.getElementById('spinner').style.display = 'block';

        $.ajax({
            url: '?url=Sequences/create_seq',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
                const job_id = document.getElementById("job_id").value;
                success_response_seq(response, 'spinner', `../public/?url=Sequences/index/${job_id}`);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }

    // 編輯 sequence
    function edit_sequence() {
        const check = input_check_seq();
        if (!check) return;

        const data = buildSequenceFormData();
        document.getElementById('spinner').style.display = 'block';

        $.ajax({
            url: '?url=Sequences/edit_seq',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
                const job_id = document.getElementById("job_id").value;
                success_response_seq(response, 'spinner', `../public/?url=Sequences/index/${job_id}`);
            },
            error: function (xhr, status, error) {
                console.error('Error:', error);
            }
        });
    }

    
    function getCheckboxValue_seq() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        let total = 0;
        let isChecked = false;  

        checkboxes.forEach((checkbox, index) => {
            if (checkbox.checked) {
                isChecked = true;  
           
                switch (index) {
                    case 0: total += 16; break; // 第1個
                    case 1: total += 8; break;  // 第2個
                    case 2: total += 4; break;  // 第3個
                    case 3: total += 2; break;  // 第4個
                    case 4: total += 1; break;  // 第5個
                }
            }
        });


        return total;
    }


    function setCheckboxesByValue(value) {

        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        
        checkboxes.forEach((checkbox, i) => {
            // 高位元先排前面，所以 index 0 檢查 bit 4
            const bit = 4 - i;
            checkbox.checked = (value & (1 << bit)) !== 0;
        });
    }

    
    function input_check_seq() {
        // --- 讀系統上限/下限 ---
        const Tool_Max_Torque = parseFloat(document.getElementById('tool_max_torque')?.value ?? '');
        const Tool_Min_Torque = parseFloat(document.getElementById('tool_min_torque')?.value ?? '');
        const Tool_Max_RPM    = parseFloat(document.getElementById('tool_max_rpm')?.value ?? '');
        const Tool_Min_RPM    = parseFloat(document.getElementById('tool_min_rpm')?.value ?? '');

        // --- forcemode 與 auto mode ---
        const selectedForceMode = document.querySelector('input[name="unscrew_forcemode"]:checked')?.value ?? null;
        const isAutoMode        = !!document.getElementById('unscrew_mode_auto')?.checked;

        // --- unscrew_mode（0 才需要對 threshold 做四捨五入）---
        const unscrewModeVal = document.querySelector('input[name="unscrew_mode"]:checked')?.value ?? null;

        // --- 讀扭力單位並決定小數位 ---
        // 0: kgf.cm→2, 1: N·m→3, 2: lbf·in→2, 3: kgf·m→4, 4: cN·m→1
        const UNIT_DECIMALS = { 0: 2, 1: 3, 2: 2, 3: 4, 4: 1 };
        const unitEl =
            document.getElementById('seq_unit_code') ||
            document.getElementById('seq_unit_code') ||
            document.getElementById('seq_unit_code');

        const torque_unit   = parseInt(unitEl?.value ?? 1, 10);
        const baseDecimals  = UNIT_DECIMALS.hasOwnProperty(torque_unit) ? UNIT_DECIMALS[torque_unit] : 3;
        const roundDecimals = baseDecimals + 1; // ★ 需求：多一位做四捨五入

        // 針對 unscrew_torque_threshold 動態產出允許小數位的正則（最多 roundDecimals 位）
        const torqueThreshPattern = new RegExp(`^\\d{1,6}(?:\\.\\d{1,${roundDecimals}})?$`);

        // --- 欄位規則 ---
        let conditions = [
            { id: 'SEQname',                  pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/, min: null,            max: null },
            { id: 'seq_repeat',               pattern: /^\d{0,4}$/,                    min: 1,               max: 99 },
            { id: 'timeout',                  pattern: /^\d{0,5}?$/,                   min: 0,               max: 60 },
            { id: 'dt_time',                  pattern: /^\d{0,5}?$/,                   min: 0,               max: 99 },
            { id: 'tt_time',                  pattern: /^\d{0,5}?$/,                   min: 0,               max: 6000 },
            { id: 'ng_stop',                  pattern: /^\d{0,5}?$/,                   min: 0,               max: 9 },
            { id: 'unscrew_rpm',              pattern: /^\d{1,3}$/,                    min: Tool_Min_RPM,    max: Tool_Max_RPM },
            { id: 'unscrew_torque_threshold', pattern: torqueThreshPattern,            min: 0,               max: Tool_Max_Torque },
            { id: 'unscrew_angle_threshold',  pattern: /^\d{1,5}(?:\.\d{1})?$/,        min: 0,               max: 30600 },
            { id: 'unscrew_force',            pattern: /^([0-9][0-9]?|100)$/,          min: 0,               max: 100 },
        ];

        let isFormValid = true;

        conditions.forEach((input) => {
            const element = document.getElementById(input.id);
            if (!element) return;

            let value = (element.value ?? '').trim();

            // Auto 模式：跳過這幾個欄位驗證
            if (isAutoMode && (input.id === 'unscrew_torque_threshold' || input.id === 'unscrew_force' || input.id === 'unscrew_angle_threshold' || input.id === 'unscrew_rpm')) {
                return;
            }

            // 顯示 min~max 提示（SEQname 不顯示）
            if (input.id !== 'SEQname') {
                const nextSibling = element.nextElementSibling;
                if (nextSibling) {
                    const hasMin = Number.isFinite(input.min);
                    const hasMax = Number.isFinite(input.max);
                    nextSibling.innerHTML = (hasMin ? input.min : '') + ' ~ ' + (hasMax ? input.max : '');
                }
            }

            // forcemode != 0 時，unscrew_force 停用並跳過
            if (selectedForceMode !== '0' && input.id === 'unscrew_force') {
                if (typeof toggleDisableAndError === 'function') {
                    toggleDisableAndError(input.id, true);
                } else {
                    element.disabled = true;
                    element.classList.remove('is-invalid');
                    const fb = element.nextElementSibling;
                    if (fb?.classList.contains('invalid-feedback')) {
                        fb.innerText = '';
                        fb.classList.remove('d-block');
                        fb.style.display = 'none';
                    }
                }
                return;
            } else if (selectedForceMode === '0' && input.id === 'unscrew_force') {
                // 若回到 0，確保可輸入
                element.disabled = false;
            }

            // ★ unscrew_mode == 0：依單位「多一位判斷」，但回填為 base 位顯示（與即時行為一致）
            if (input.id === 'unscrew_torque_threshold' && unscrewModeVal === '0' && value !== '') {
            const n = Number(value);
            if (Number.isFinite(n)) {
                const rounded = (typeof roundHalfUp === 'function')
                ? roundHalfUp(n, baseDecimals)     // baseDecimals 由你的程式計算：UNIT_DECIMALS[torque_unit]
                : Number(n.toFixed(baseDecimals));
                element.value = rounded.toFixed(baseDecimals); // 固定 base 位
                value = element.value;
            }
            // 允許輸入 base+1 位（提交前已被回填為 base 位）
            element.setAttribute('pattern', `^\\d{1,6}(?:\\.\\d{1,${roundDecimals}})?$`);
            element.setAttribute('inputmode', 'decimal');
            element.setAttribute('step', String(1 / Math.pow(10, baseDecimals)));
            }


            // --- 驗證 ---
            // 空值
            if (value === '') {
                element.classList.add('is-invalid');
                isFormValid = false;
                return;
            }

            // 格式
            if (!input.pattern.test(value)) {
                element.classList.add('is-invalid');
                isFormValid = false;
                return;
            }

            // 範圍
            const numVal = parseFloat(value);
            if (Number.isFinite(input.min) && numVal < input.min) {
                element.classList.add('is-invalid');
                isFormValid = false;
                return;
            }
            if (Number.isFinite(input.max) && numVal > input.max) {
                element.classList.add('is-invalid');
                isFormValid = false;
                return;
            }

            // OK
            element.classList.remove('is-invalid');
        });

        return isFormValid;
    }




    function toggleDisableAndError(elementId, disable) {
        const element = document.getElementById(elementId);
        if (!element) {
            console.warn(`Element with ID '${elementId}' not found.`);
            return;
        }

        element.disabled = disable;
        if (disable) {
            element.classList.remove('is-invalid'); 
        }
    }


    function toggleInputsBasedOnMode() {
        const autoModeRadio = document.getElementById('unscrew_mode_auto');
        const customModeRadio = document.getElementById('unscrew_mode_custom');

        const inputElements = document.querySelectorAll(
            '#div_speed input, #div_torque_threshold input, #div_angle_threshold input, #div_direction input, #div_force input'
        );

        const enable = customModeRadio?.checked === true;
        inputElements.forEach(el => el.disabled = !enable);
    }

    // 綁定與初始化
    document.addEventListener('DOMContentLoaded', function () {
        toggleInputsBasedOnMode();

        document.getElementById('unscrew_mode_auto')?.addEventListener('change', toggleInputsBasedOnMode);
        document.getElementById('unscrew_mode_custom')?.addEventListener('change', toggleInputsBasedOnMode);
    });
    
</script>
