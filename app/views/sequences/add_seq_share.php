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
            document.getElementById("total_angle_limit").value = 0;

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
         data.append("total_angle_limit", document.getElementById("total_angle_limit").value);

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
        // 若語系剛切換過，保險刷新一次按鈕文字
        if (typeof refreshAlertifyI18n === 'function') refreshAlertifyI18n();

        const lang = (getCookie('language') || 'en-us').toLowerCase();
        const i18nErr =
            lang === 'zh-tw' ? '新增工序失敗，請稍後再試！' :
            lang === 'zh-cn' ? '新增工序失败，请稍后再试！' :
                            'Failed to create sequence. Please try again later.';

        // 表單前置檢查
        const ok = typeof input_check_seq === 'function' ? input_check_seq() : true;
        if (!ok) return;

        // 準備資料
        let data;
        try {
            data = buildSequenceFormData(); // 預期回傳 FormData
        } catch (e) {
            console.error('buildSequenceFormData error:', e);
            alertify.error(i18nErr);
            return;
        }

        // 防止重複點擊
        const btn = document.getElementById('save_seq_btn');
        if (btn) btn.disabled = true;

        // 顯示遮罩 + spinner
        document.querySelector('.main-content')?.classList.add('overlay-active');
        document.getElementById('spinner').style.display = 'block';

        $.ajax({
            url: '?url=Sequences/create_seq',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
            const job_id = document.getElementById('job_id')?.value;
            // 交給你既有封裝：會關 spinner/提示並導回列表
            success_response_seq(response, 'spinner', `../public/?url=Sequences/index/${job_id}`);
            },
            error: function (xhr, status, error) {
            console.error('save_sequence error:', status, error);
            alertify.error(i18nErr);
            // 出錯時手動關 spinner
            document.getElementById('spinner').style.display = 'none';
            },
            complete: function () {
            // 無論成功失敗都移除遮罩、恢復按鈕
            document.querySelector('.main-content')?.classList.remove('overlay-active');
            if (btn) btn.disabled = false;
            }
        });
    }


    // 編輯 sequence
    function edit_sequence() {
        // 若語系剛切換過，保險刷新一次按鈕語系
        if (typeof refreshAlertifyI18n === 'function') refreshAlertifyI18n();

        const lang = (getCookie('language') || 'en-us').toLowerCase();
        const i18nErr =
            lang === 'zh-tw' ? '編輯工序失敗，請稍後再試！' :
            lang === 'zh-cn' ? '编辑工序失败，请稍后再试！' :
                            'Failed to update sequence. Please try again later.';

        // 表單檢查
        const ok = (typeof input_check_seq === 'function') ? input_check_seq() : true;
        if (!ok) return;

        // 準備 FormData
        let data;
        try {
            data = buildSequenceFormData(); // 應為 FormData
        } catch (e) {
            console.error('buildSequenceFormData error:', e);
            alertify.error(i18nErr);
            return;
        }

        // 防重複提交
        const btn = document.getElementById('edit_seq_btn');
        if (btn) btn.disabled = true;

        // 顯示遮罩 + spinner
        document.querySelector('.main-content')?.classList.add('overlay-active');
        document.getElementById('spinner').style.display = 'block';

        $.ajax({
            url: '?url=Sequences/edit_seq',
            type: 'POST',
            data: data,
            processData: false,
            contentType: false,
            success: function (response) {
            const job_id = document.getElementById('job_id')?.value;
            success_response_seq(response, 'spinner', `../public/?url=Sequences/index/${job_id}`);
            },
            error: function (xhr, status, error) {
            console.error('edit_sequence error:', status, error);
            alertify.error(i18nErr);
            // 失敗時關 spinner
            document.getElementById('spinner').style.display = 'none';
            },
            complete: function () {
            // 成功/失敗皆清理
            document.querySelector('.main-content')?.classList.remove('overlay-active');
            if (btn) btn.disabled = false;
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
        // === 多語詞庫 ===
        const I18N = {
            'en-us': {
            dialogTitle: 'Warning',
            empty: '“{label}” cannot be empty.',
            format: '“{label}” contains invalid characters.',
            range: '“{label}” must be between {min} and {max}.',
            minOnly: '“{label}” must be ≥ {min}.',
            maxOnly: '“{label}” must be ≤ {max}.',
            ok: 'OK',
            labels: {
                SEQname: 'Sequence Name',
                seq_repeat: 'Repeat',
                timeout: 'Timeout (s)',
                dt_time: 'DT Time (s)',
                tt_time: 'TT Time (s)',
                unscrew_rpm: 'Reverse RPM',
                unscrew_torque_threshold: 'Reverse Torque Threshold',
                unscrew_angle_threshold: 'Reverse Angle Threshold',
                unscrew_force: 'Reverse Force (%)',
                total_angle_limit: 'total_angle',
            },
            },
            'zh-tw': {
            dialogTitle: '警告',
            empty: '{label}   不可為空白。',
            format: '{label}  含有不允許的字元。',
            range: '{label}   必須介於 {min} ~ {max} 之間。',
            minOnly: '{label} 必須 ≥ {min}。',
            maxOnly: '{label} 必須 ≤ {max}。',
            ok: '確定',
            labels: {
                SEQname: '工序名稱',
                seq_repeat: '顆數',
                timeout: '超時鎖附(秒)',
                dt_time: '顆數間隔時間 (秒)',
                tt_time: '工序完成時間(秒)',
                unscrew_rpm: '拆螺絲轉速 (RPM)',
                unscrew_torque_threshold: '門檻點扭力',
                unscrew_angle_threshold: '門檻點角度',
                unscrew_force: '反轉力度 (%)',
                total_angle_limit: '總角度上限',

            },
            },
            'zh-cn': {
            dialogTitle: '警告',
            empty: '{label}   不能为空。',
            format: '{label}  包含不允许的字符。',
            range: '{label}   必须介于 {min} ~ {max} 之间。',
            minOnly: '{label} 必须 ≥ {min}。',
            maxOnly: '{label} 必须 ≤ {max}。',
            ok: '确定',
            labels: {
                SEQname: '工序名称',
                seq_repeat: '颗数',
                timeout: '超时锁附(秒)',
                dt_time: '颗数间隔时间(秒)',
                tt_time: '工序完成时间(秒)',
                unscrew_rpm: '拆螺丝转速 (RPM)',
                unscrew_torque_threshold: '门槛点扭力',
                unscrew_angle_threshold: '门槛点角度',
                unscrew_force: '反转力度 (%)',
                total_angle_limit: '总角度上限',

            },
            },
        };

        // --- 語系正規化 ---
        function normalizeLang(raw) {
            const x = String(raw || '').toLowerCase();
            if (!x) return 'en-us';
            if (x === 'en') return 'en-us';
            if (x.startsWith('zh')) {
            if (x.includes('tw') || x.includes('hk') || x.includes('mo') || x.includes('hant')) return 'zh-tw';
            if (x.includes('cn') || x.includes('sg') || x.includes('hans')) return 'zh-cn';
            return 'zh-tw';
            }
            return x;
        }

        const langCookie =
            (typeof getCookie === 'function' && getCookie('language')) ||
            document.documentElement.getAttribute('lang') ||
            'en-us';
        const lang = normalizeLang(langCookie);
        const dict = I18N[lang] || I18N['en-us'];

        const t = (key, params = {}) => {
            let s = dict[key] ?? I18N['en-us'][key] ?? key;
            Object.entries(params).forEach(([k, v]) => {
            s = s.replaceAll(`{${k}}`, String(v));
            });
            return s;
        };
        const labelOf = (id) =>
            document.getElementById(id)?.getAttribute('data-label') ||
            dict.labels?.[id] ||
            id;

        const markAndFocusInvalid = (el) => {
            if (!el) return;
            el.classList.add('is-invalid');
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            setTimeout(() => el.focus({ preventScroll: true }), 0);
        };

        // 彈窗（雙保險：全域/單次都設 OK 語系）
        const alertPopup = (msg, el) => {
            if (window.alertify?.defaults?.glossary) {
            try {
                alertify.defaults.glossary.ok = dict.ok;
            } catch (e) {}
            }
            alertify
            .alert(dict.dialogTitle, msg, () => el && el.focus())
            .set('movable', false)
            .set('labels', { ok: dict.ok });
        };

        // 隱藏欄位後方提示
        const hideInlineHint = (el) => {
            const sib = el?.nextElementSibling;
            if (!sib) return;
            const classes = ['invalid-feedback', 'text-danger', 'form-text', 'help-block', 'valid-feedback', 'range-hint'];
            if (classes.some((c) => sib.classList?.contains(c))) {
            sib.textContent = '';
            sib.classList.remove('d-block');
            sib.style.display = 'none';
            }
        };

        // --- 讀系統上限/下限 ---
        const Tool_Max_Torque = parseFloat(document.getElementById('tool_max_torque')?.value ?? '');
        const Tool_Min_Torque = parseFloat(document.getElementById('tool_min_torque')?.value ?? '');
        const Tool_Max_RPM = parseFloat(document.getElementById('tool_max_rpm')?.value ?? '');
        const Tool_Min_RPM = parseFloat(document.getElementById('tool_min_rpm')?.value ?? '');

        // --- forcemode 與 auto mode ---
        const selectedForceMode = document.querySelector('input[name="unscrew_forcemode"]:checked')?.value ?? null; // '0' or others
        const isAutoMode = !!document.getElementById('unscrew_mode_auto')?.checked;

        // --- unscrew_mode（0 才需要對 threshold 做四捨五入）---
        const unscrewModeVal = document.querySelector('input[name="unscrew_mode"]:checked')?.value ?? null; // '0'/'1'...

        // --- 讀扭力單位並決定小數位 ---
        // 0: kgf.cm→2, 1: N·m→3, 2: lbf·in→2, 3: kgf·m→4, 4: cN·m→1
        const UNIT_DECIMALS = { 0: 2, 1: 3, 2: 2, 3: 4, 4: 1 };
        const unitEl = document.getElementById('seq_unit_code');
        const torque_unit = parseInt(unitEl?.value ?? 1, 10);
        const baseDecimals = Object.prototype.hasOwnProperty.call(UNIT_DECIMALS, torque_unit)
            ? UNIT_DECIMALS[torque_unit]
            : 3;
        const roundDecimals = baseDecimals + 1; // 允許多一位輸入
        const torqueThreshPattern = new RegExp(`^\\d{1,6}(?:\\.\\d{1,${roundDecimals}})?$`);

        // --- 欄位規則 ---
        const conditions = [
            { id: 'SEQname', pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/, min: null, max: null },
            { id: 'seq_repeat', pattern: /^\d{0,4}$/, min: 1, max: 99 },
            { id: 'timeout', pattern: /^\d{0,5}$/, min: 0, max: 60 },
            { id: 'dt_time', pattern: /^\d{0,5}$/, min: 0, max: 99 },
            { id: 'tt_time', pattern: /^\d{0,5}$/, min: 0, max: 6000 },
            { id: 'ng_stop', pattern: /^\d{0,5}$/, min: 0, max: 9 },
            { id: 'unscrew_rpm', pattern: /^\d+$/, min: Tool_Min_RPM, max: Tool_Max_RPM },
            { id: 'unscrew_torque_threshold', pattern: torqueThreshPattern, min: 0, max: Tool_Max_Torque },
            { id: 'unscrew_angle_threshold', pattern: /^\d+(?:\.\d{1})?$/, min: 0, max: 30600 },
            { id: 'unscrew_force', pattern: /^\d+$/, min: 0, max: 100 },
            { id: 'total_angle_limit', pattern: /^\d+$/, min: 0, max: 30600},
        ];

        // 彈窗提示的欄位
        const POPUP_FIELDS = new Set([
            'SEQname',
            'seq_repeat',
            'timeout',
            'dt_time',
            'tt_time',
            'unscrew_rpm',
            'unscrew_torque_threshold',
            'unscrew_angle_threshold',
            'unscrew_force',
            'total_angle_limit'
        ]);

        let isFormValid = true;
        let popupShown = false; // 避免一次跳多個彈窗

        // 先把所有欄位後方提示移除/隱藏
        conditions.forEach((rule) => hideInlineHint(document.getElementById(rule.id)));

        // 驗證
        conditions.forEach((input) => {
            const element = document.getElementById(input.id);
            if (!element) return;

            let value = (element.value ?? '').trim();

            // Auto 模式：跳過反轉相關欄位
            if (
            isAutoMode &&
            (input.id === 'unscrew_torque_threshold' ||
                input.id === 'unscrew_force' ||
                input.id === 'unscrew_angle_threshold' ||
                input.id === 'unscrew_rpm')
            ) {
            element.classList.remove('is-invalid');
            return;
            }

            // forcemode != 0 時，unscrew_force 停用並跳過
            if (selectedForceMode !== '0' && input.id === 'unscrew_force') {
            element.disabled = true;
            element.classList.remove('is-invalid');
            hideInlineHint(element);
            return;
            } else if (selectedForceMode === '0' && input.id === 'unscrew_force') {
            element.disabled = false;
            }

            // unscrew_mode == 0：允許多一位輸入，但提交前回填 base 位
            if (input.id === 'unscrew_torque_threshold' && unscrewModeVal === '0' && value !== '') {
            const n = Number(value);
            if (Number.isFinite(n)) {
                const rounded = Number(n.toFixed(baseDecimals));
                element.value = rounded.toFixed(baseDecimals);
                value = element.value;
            }
            element.setAttribute('pattern', `^\\d{1,6}(?:\\.\\d{1,${roundDecimals}})?$`);
            element.setAttribute('inputmode', 'decimal');
            element.setAttribute('step', String(1 / Math.pow(10, baseDecimals)));
            }

            // --- 驗證：空值 ---
            if (value === '') {
            element.classList.add('is-invalid');
            isFormValid = false;

            if (!popupShown && POPUP_FIELDS.has(input.id)) {
                popupShown = true;
                markAndFocusInvalid(element);
                alertPopup(t('empty', { label: labelOf(input.id) }), element);
            }
            return;
            }

            // --- 驗證：格式 ---
            if (!input.pattern.test(value)) {
            element.classList.add('is-invalid');
            isFormValid = false;

            if (!popupShown && POPUP_FIELDS.has(input.id)) {
                popupShown = true;
                markAndFocusInvalid(element);
                alertPopup(t('format', { label: labelOf(input.id) }), element);
            }
            return;
            }

            // --- 驗證：範圍（僅對數值型欄位）
            const numVal = Number(value);
            const isNumeric = !Number.isNaN(numVal);
            const hasMin = Number.isFinite(input.min);
            const hasMax = Number.isFinite(input.max);

            if (isNumeric && hasMin && numVal < input.min) {
            element.classList.add('is-invalid');
            isFormValid = false;

            if (!popupShown && POPUP_FIELDS.has(input.id)) {
                popupShown = true;
                markAndFocusInvalid(element);
                const msg = hasMax
                ? t('range', { label: labelOf(input.id), min: input.min, max: input.max })
                : t('minOnly', { label: labelOf(input.id), min: input.min });
                alertPopup(msg, element);
            }
            return;
            }
            if (isNumeric && hasMax && numVal > input.max) {
            element.classList.add('is-invalid');
            isFormValid = false;

            if (!popupShown && POPUP_FIELDS.has(input.id)) {
                popupShown = true;
                markAndFocusInvalid(element);
                const msg = hasMin
                ? t('range', { label: labelOf(input.id), min: input.min, max: input.max })
                : t('maxOnly', { label: labelOf(input.id), max: input.max });
                alertPopup(msg, element);
            }
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
