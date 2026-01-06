$(document).ready(function () {
    /*const sectionMap = {
        "Controller": "Controller_Setting",
        "System": "System_Setting",
        "Barcode": "Barcode_Setting",
        "Connect": "Connect_Setting",
        "Update": "iDas-Update_Setting"
    };

    const lastSection = sessionStorage.getItem('last_section') || 'Controller_Setting';

    $('.divMode').addClass('hidden').removeClass('active');
    $('#' + lastSection).removeClass('hidden').addClass('active');

    $('.button').removeClass('active');
    if (lastSection === 'Controller_Setting') $('#bnt1').addClass('active');
    if (lastSection === 'System_Setting') $('#bnt2').addClass('active');
    if (lastSection === 'Barcode_Setting') $('#bnt3').addClass('active');
    if (lastSection === 'Connect_Setting') $('#bnt4').addClass('active');
    if (lastSection === 'iDas-Update_Setting') $('#bnt5').addClass('active');*/

    getCurrentSystemTime();
});

function suppressRangeHints(on = true) {
  document.documentElement.classList.toggle('suppress-range-hints', !!on);
  // 或者用 body：document.body.classList.toggle('suppress-range-hints', !!on);
}

function change_datetime() {
    var newTime = document.getElementById("newTime").value;
    var language = getCookie('language') || 'default';

    var messages = {
        'zh-tw': {
            'select': '請選擇時間',
            'success': '設定成功',
            'fail': '設定失敗',
            'error': '通訊錯誤，請稍後再試。'
        },
        'zh-cn': {
            'select': '请选择时间',
            'success': '设置成功',
            'fail': '设置失败',
            'error': '通信错误，请稍后再试。'
        },
        'default': {
            'select': 'Please select a time',
            'success': 'Success',
            'fail': 'Failed',
            'error': 'Communication error. Please try again later.'
        }
    };

    var msg = messages[language] || messages['default'];

    if (!newTime) {
        alert(msg.select);
        return;
    }

    document.getElementById('spinner').style.display = 'block';

    $.ajax({
        type: "POST",
        url: "?url=Settings/edit_system_date",
        data: { datetime: newTime },
        dataType: "json",
        success: function(response) {
            document.getElementById('spinner').style.display = 'none';

            if (response.error) {
                alertify.alert(msg.fail, response.error);
            } else {
                alertify.alert(msg.success, msg.success);
                setTimeout(function () {
                    alertify.closeAll();
                    location.reload(); // ✅ 自動重整
                }, 3000); // ✅ 自動關閉時間：3秒
                document.getElementById('Controller_Setting').style.display = "none";
                document.getElementById('System_Setting').style.display = "block";
            }

        },
        error: function() {
            document.getElementById('spinner').style.display = 'none';
            alertify.alert(msg.fail, msg.error);
        }
    });
}




function getCurrentSystemTime() {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var serverTime = xhr.responseText.trim().replace(/-/g, "/");
            var serverDateTime = new Date(serverTime);
            updateCurrentTime(serverDateTime);
        }
    };
    xhr.open("GET", "?url=Settings/get_system_time", true);
    xhr.send();
}

function updateCurrentTime(serverDateTime) {
    var el = document.getElementById("currentSystemTime");

    function pad(n) {
        return n < 10 ? '0' + n : n;
    }

    function formatTime(date) {
        var year = date.getFullYear();
        var month = pad(date.getMonth() + 1);
        var day = pad(date.getDate());
        var hour = date.getHours();
        var minute = pad(date.getMinutes());
        var second = pad(date.getSeconds());

        let isPM = hour >= 12;
        let period = isPM ? '下午' : '上午';

        // 使用 12 小時制顯示
        let hour12 = hour % 12 || 12;

        return `${year}/${month}/${day} ${period} ${pad(hour12)}:${minute}:${second}`;
    }

    // 初次顯示
    let lastText = formatTime(serverDateTime);
    el.innerText = lastText;

    // 每秒更新一次，但只有在內容變化時才更新畫面，避免閃爍
    setInterval(function () {
        serverDateTime.setSeconds(serverDateTime.getSeconds() + 1);
        let currentText = formatTime(serverDateTime);

        if (el.innerText !== currentText) {
            el.innerText = currentText;
        }
    }, 1000);
}




function controller_save(){
  // 取值 & 去除前後空白
  const trim = (v) => (v == null ? '' : String(v).trim());

  const control_id_old = trim(document.getElementById('control_id_old')?.value); // 舊ID（'' 代表 NULL）
  const control_id     = trim(document.getElementById('control_id')?.value);     // 螢幕上顯示的 ID（當新ID）
  const control_name   = trim(document.getElementById('control_name')?.value);
  const storage_warning = trim(document.getElementById('storage_warning')?.value);
  const torque_filter   = trim(document.getElementById('torque_filter')?.value);
  const lang_val        = trim(document.getElementById('select_language')?.value);
  const unit_val        = trim(document.getElementById('select_torque_unit')?.value);
  const counting_method_val   = trim(document.querySelector('input[name="counting_method"]:checked')?.value);
  const circular_archive_val  = trim(document.querySelector('input[name="circular_archive"]:checked')?.value);
  const blackout_recovery_val = trim(document.querySelector('input[name="blackout_recovery"]:checked')?.value);
  const buzzer_val            = trim(document.querySelector('input[name="buzzer_mode"]:checked')?.value);
  const global_downshift_torque = trim(document.getElementById('global_downshift_torque')?.value);
  const global_downshift_speed  = trim(document.getElementById('global_downshift_speed')?.value);

  // 你的原本驗證
  let check = input_check_setting();
  if (!check) return;

  // 只有當使用者真的改了 ID，才送 control_id_new；否則送空字串（後端視為不更改）
  const control_id_new = (control_id !== control_id_old) ? control_id : '';

  $.ajax({
    url: "?url=Settings/control_setting",
    method: "POST",
    data: {
      // 後端會把 '' 視為 NULL（舊ID可為 NULL；新ID空字串代表不改）
      control_id: control_id_old,
      control_id_new: control_id_new,

      control_name: control_name,
      lang_val: lang_val,
      unit_val: unit_val,
      storage_warning: storage_warning,
      torque_filter: torque_filter,
      counting_method: counting_method_val,
      circular_archive: circular_archive_val,
      blackout_recovery: blackout_recovery_val,
      buzzer_mode: buzzer_val,
      global_downshift_torque: global_downshift_torque,
      global_downshift_speed: global_downshift_speed
    },
    success: function(response) {
      suppressRangeHints(true);
      handleAjaxResponse(response); // 你原本的處理
    },
    error: function(xhr, status, error) {
      // 依需求處理
    }
  });
}




function input_check_setting(argument) {

    // —— 一次性注入 CSS：關掉 inline 紅字；灰字範圍提示不受 is-invalid 影響 —— //
    (function ensureNoInlineErrorCSS(){
        var id = 'hide-inline-invalid-feedback-style';
        if (!document.getElementById(id)) {
        var style = document.createElement('style');
        style.id = id;
        style.textContent = `
            .is-invalid ~ .invalid-feedback,
            .was-validated .form-control:invalid ~ .invalid-feedback,
            .was-validated .form-select:invalid ~ .invalid-feedback {
            display: none !important;
            }
            .range-hint.form-text { display: block !important; }
            .is-invalid ~ .range-hint.form-text { display: none !important; }
        `;
        document.head.appendChild(style);
        }
    })();

    // 取得語系
    const getCookieSafe = (name) => {
        try { const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
        catch { return null; }
    };
    let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : (getCookieSafe('language') || 'zh-tw')) || 'zh-tw';
    lang = String(lang).toLowerCase();
    if (lang === 'en') lang = 'en-us';
    if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

    // 字串資源
    const LABELS = {
        'en-us': { control_id: 'Device ID',control_name:'Device Name', storage_warning:'Storage Warning (%)', torque_filter:'Torque Filter', global_downshift_torque:'Downshift Torque', global_downshift_speed:'Downshift Speed' },
        'zh-tw': { control_id: '設備編號',control_name:'設備名稱', storage_warning:'容量警示(%)', torque_filter:'扭力過濾', global_downshift_torque:'降檔扭力', global_downshift_speed:'降檔速' },
        'zh-cn': { control_id: '设备编号',control_name:'设备名称', storage_warning:'容量警示(%)', torque_filter:'扭力过滤', global_downshift_torque:'降档扭力', global_downshift_speed:'降档速度' }
    }[lang];

    const I18N = {
        'en-us': { title:'Warning', ok:'OK', required:'{FIELD} is required.', format:'{FIELD} has invalid format.', range:'{FIELD} is out of range ({RANGE}).' },
        'zh-tw': { title:'警告', ok:'確定', required:'{FIELD} 為必填。', format:'{FIELD} 格式不正確。', range:'{FIELD} 超出範圍（{RANGE}）。' },
        'zh-cn': { title:'警告', ok:'确定', required:'{FIELD} 为必填。', format:'{FIELD} 格式不正确。', range:'{FIELD} 超出范围（{RANGE}）。' }
    }[lang];

    // 也準備 Cancel 文案（以防未來 confirm 使用）
    const OK_TEXT = { 'en-us':'OK', 'zh-tw':'確定', 'zh-cn':'确定' }[lang];
    const CANCEL_TEXT = { 'en-us':'Cancel', 'zh-tw':'取消', 'zh-cn':'取消' }[lang];

    // 先嘗試設定全域（支援的 alertify 版本會吃到）
    try {
        if (window.alertify?.defaults?.glossary) {
        alertify.defaults.glossary.ok = OK_TEXT;
        alertify.defaults.glossary.cancel = CANCEL_TEXT;
        }
    } catch (_) {}

    // —— Hint 工具 —— //
    function getHintEl(el) {
        if (!el) return null;
        const id = el.id;
        let hint = el.parentElement?.querySelector(`[data-hint-for="${id}"]`);
        if (hint) return hint;
        hint = el.parentElement?.querySelector('.range-hint');
        if (hint) return hint;
        let sib = el.nextElementSibling, step = 0;
        while (sib && step < 3) {
        if (sib.matches('.range-hint, .invalid-feedback, .form-text, .help-block, .text-danger')) return sib;
        sib = sib.nextElementSibling; step++;
        }
        return null;
    }
    function neutralizeHint(el) {
        const h = getHintEl(el);
        if (!h) return null;
        h.classList.remove('text-danger','invalid-feedback','d-block');
        h.classList.add('form-text','range-hint');
        h.style.display = 'none';
        h.textContent = '';
        return h;
    }
    function hideHint(el) {
        const h = getHintEl(el) || neutralizeHint(el);
        if (!h) return;
        h.textContent = '';
        h.style.display = 'none';
        h.classList.remove('text-danger','invalid-feedback','d-block');
        h.classList.add('form-text','range-hint');
    }
    function showRangeHint(el, min, max) {
        const h = getHintEl(el) || neutralizeHint(el);
        if (!h) return;
        if (min == null || max == null) { hideHint(el); return; }
        h.textContent = `${min} ~ ${max}`;
        h.style.display = 'block';
        h.classList.remove('text-danger','invalid-feedback','d-block');
        h.classList.add('form-text','range-hint');
    }

    // 規則
    const conditions = [
        { id:'control_id',              label:LABELS.control_id,              pattern:/^\d{0,4}$/,                      min:1,   max:255   },
        { id:'control_name',            label:LABELS.control_name,            pattern:/^[a-zA-Z0-9_\u4E00-\u9FA5\-]+$/, min:null, max:null },
        { id:'storage_warning',         label:LABELS.storage_warning,         pattern:/^\d{0,4}$/,                      min:50,   max:95   },
        { id:'torque_filter',           label:LABELS.torque_filter,           pattern:/^\d{1,3}(\.\d{1,6})?$/,          min:0.0,  max:200  },
        { id:'global_downshift_torque', label:LABELS.global_downshift_torque, pattern:/^\d{0,5}?$/,                     min:0,    max:100  },
        { id:'global_downshift_speed',  label:LABELS.global_downshift_speed,  pattern:/^\d{0,5}?$/,                     min:0,    max:100  },
    ];

    let isFormValid = true;
    const errors = [];
    let firstInvalidEl = null;
    const passedFields = new Set();

    // 先驗證，不顯示 hint
    conditions.forEach((input) => {
        const el = document.getElementById(input.id);
        if (!el) return;

        const value = (el.value || '').trim();

        el.classList.remove('is-invalid');
        hideHint(el);

        const pushErr = (msg) => {
        isFormValid = false;
        errors.push(msg);
        el.classList.add('is-invalid');
        hideHint(el);
        if (!firstInvalidEl) firstInvalidEl = el;
        };

        if (value === '') { pushErr(I18N.required.replace('{FIELD}', input.label)); return; }
        if (!input.pattern.test(value)) { pushErr(I18N.format.replace('{FIELD}', input.label)); return; }

        const num = parseFloat(value);
        if (input.min !== null && !Number.isNaN(num) && num < input.min) {
        const range = (input.min !== null && input.max !== null) ? `${input.min} ~ ${input.max}` : `≥ ${input.min}`;
        pushErr(I18N.range.replace('{FIELD}', input.label).replace('{RANGE}', range)); return;
        }
        if (input.max !== null && !Number.isNaN(num) && num > input.max) {
        const range = (input.min !== null && input.max !== null) ? `${input.min} ~ ${input.max}` : `≤ ${input.max}`;
        pushErr(I18N.range.replace('{FIELD}', input.label).replace('{RANGE}', range)); return;
        }

        passedFields.add(input.id);
    });

    // 驗證後再決定是否顯示範圍
    if (isFormValid) {
        conditions.forEach((input) => {
        if (input.id === 'control_name') return;
        const el = document.getElementById(input.id);
        if (!el) return;
        if (passedFields.has(input.id)) showRangeHint(el, input.min, input.max);
        else hideHint(el);
        });
    } else {
        conditions.forEach((input) => {
        const el = document.getElementById(input.id);
        if (el) hideHint(el);
        });
    }

    // 彈窗顯示錯誤（OK 有語系）
    if (!isFormValid && errors.length > 0) {
        const body = errors.map(e => `<div>${e}</div>`).join('');
        if (!window._alertingSettingsForm) {
        window._alertingSettingsForm = true;
        alertify
            .alert(I18N.title, body, function () {
            try { firstInvalidEl?.focus(); firstInvalidEl?.select?.(); } catch {}
            window._alertingSettingsForm = false;
            })
            .set('labels', { ok: OK_TEXT }); // ★ 單次保險
        }
    }

    return isFormValid;
}




//新增密碼
function save_pwd(){

    var clearSeqPwd = document.getElementById('clearseq_button_pwd').value;
    var clearPwd = document.getElementById('clear_button_pwd').value;
    var confirmPwd = document.getElementById('confirm_button_pwd').value;
    var enablePwd = document.getElementById('enable_button_pwd').value;
    var disablePwd = document.getElementById('disable_button_pwd').value;
    var skipPwd = document.getElementById('skip_button_pwd').value;

    // 驗證函數
    function isValidInput(input) {
        return /^[0-9]{0,4}$/.test(input); // 檢查是否是 0-9 的數字，最多四位
    }

    // 驗證所有欄位
    if (!isValidInput(clearSeqPwd) || 
        !isValidInput(clearPwd) || 
        !isValidInput(confirmPwd) || 
        !isValidInput(enablePwd) || 
        !isValidInput(disablePwd) || 
        !isValidInput(skipPwd)) {
        //alert("請確保所有欄位都只包含 0-9 的數字，並且最多四位。");
        return; // 如果驗證失敗，停止函式執行
    }
    document.getElementById('spinner').style.display = 'block'; // 顯示加載動畫

    $.ajax({
        url: '?url=Settings/edit_feature_pwd', // 替換為你的伺服器端點
        type: 'POST',
        data: {
            clear_seq: clearSeqPwd,
            clear: clearPwd,
            confirm: confirmPwd,
            enable: enablePwd,
            disable: disablePwd,
            skip: skipPwd
        },
        success: function (responseData) {
            handleAjaxResponse(responseData);
        },
        error: function() {
            alert("發生錯誤，請重試。");
        }
    });
}

//狀態-顏色設定
function background_save(){
    let  okjob_color_val = document.querySelector('input[name="okjobcolor"]:checked')?.value;
    let  okseq_color_val = document.querySelector('input[name="okseqcolor"]:checked')?.value;

    $.ajax({
        url: '?url=Settings/edit_background_color', 
        type: 'POST',
        data: {
            okjobcolor: okjob_color_val,
            okseqcolor: okseq_color_val
        },
        success: function(response) {
           handleAjaxResponse(response);
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });

}

//設置 	global-downshift
/*function downshift_save(){

    var global_downshift_torque = document.getElementById('global_downshift_torque').value;
    var global_downshift_speed  = document.getElementById('global_downshift_speed').value;

    $.ajax({
        url: '?url=Settings/edit_global_downshift', 
        type: 'POST',
        data: {
            global_downshift_torque: global_downshift_torque,
            global_downshift_speed: global_downshift_speed
        },
        success: function(response) {
            handleAjaxResponse(responseData);
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });

}*/

//barcode mode 選擇
function toggleBarcodeSeq() {
    const barcodeMode = document.getElementById('barcode_mode');
    const barcodeSeq = document.getElementById('barcode_seq');
    const seqContainer = document.getElementById("barcode_select_seq");

    if (barcodeMode.value === '1' || barcodeMode.value === '2') {
        barcodeSeq.disabled = true;
        seqContainer.style.display = 'none';
    } else if (barcodeMode.value === '3') {
        barcodeSeq.disabled = false;
        seqContainer.style.display = 'block';
        //fetchSeqList();  // 自動觸發查詢 SEQ
    } else {
        barcodeSeq.disabled = false;
        seqContainer.style.display = 'block';
    }
}


function Export_SystemConfig() {
    var xhr = new XMLHttpRequest();
    xhr.responseType = "blob";
    xhr.onload = function () {
        if (xhr.status === 200) {
            var a = document.createElement("a");
            a.href = window.URL.createObjectURL(xhr.response);
            a.download = "NTCS_Config_Pack.zip";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
    };
    xhr.open("GET", "?url=Settings/export_sysytem_config", true);
    xhr.send();
}




function Import_SystemConfig() {
    var import_file = document.getElementById("import-file-uploader").files[0];
    var form = new FormData();
    form.append("file", import_file);
    var url = '?url=Settings/Import_Config';
    
    // 語言設置
    var language = getCookie('language') || 'en';
    var text_info, title, confirm_text;
    
    if(language == "zh-cn") {
        text_info = '您確定要導入資料庫檔案嗎？';
        title = '導入配置';
        confirm_text = '您確定要進行此操作嗎？';
    } else if(language == "zh-tw") {
        text_info = '您確定要導入資料庫檔案嗎？';
        title = '導入配置';
        confirm_text = '您確定要進行此操作嗎？';
    } else {
        text_info = 'Are you sure you want to import the database file?';
        title = 'Import Configuration';
        confirm_text = 'Are you sure you want to perform this action?';
    }

    if (import_file) {
        alertify.confirm(confirm_text, function(result) {
            if (result) {
                document.getElementById('spinner').style.display = 'block'; // 顯示加載動畫

                $.ajax({
                    url: url,
                    method: "POST",
                    data: form,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        var responseData = JSON.parse(response);

                        setTimeout(function() {
                            document.getElementById('spinner').style.display = 'none';
                            alertify.alert(responseData.res_type, responseData.res_msg, function() {
                                history.go(0); 
                            });

                            setTimeout(function() {
                                alertify.closeAll(); 
                            }, 3000);
                        }, 1000);
                    },
                    error: function(xhr, status, error) {
                        alertify.alert('Error', 'An error occurred while importing the configuration file.');
                    }
                });
            }
        });
    } else {
        alertify.alert(title, text_info); // 如果 import_file 沒有值，顯示提示訊息
    }
}

function Firmware_Update() {
    var bb_file = document.getElementById("firmware-file-uploader").files[0];
    if (bb_file == undefined) {
        return;
    }

    var form = new FormData();
    form.append("file", bb_file);
    var url = '?url=Settings/FirmwareUpdate';

    $.ajax({
        type: "POST",
        processData: false,
        cache: false,
        contentType: false,
        data: form,
        dataType: "json",
        url: url,
        beforeSend: function() {
            //$('#overlay').removeClass('hidden');
        },
    }).done(function(result) {
        //$('#overlay').addClass('hidden');
        document.getElementById("firmware-file-uploader").value = '';
    });
}



function OpenButton(ButtonMode){

    if (ButtonMode == "Controller")
    {
        document.getElementById('Controller_Setting').style.display = "";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt1').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");   
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "System")
    {
        document.getElementById('System_Setting').style.display = "";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt2').classList.add("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");

    }
    else if (ButtonMode == "Barcode")
    {
        document.getElementById('Barcode_Setting').style.display = "";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt3').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "Connect")
    {
        document.getElementById('Connect_Setting').style.display = "";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt4').classList.add("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "Update")
    {
        document.getElementById('iDas-Update_Setting').style.display = "";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('bnt5').classList.add("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
    }
}



function getCookie(name) 
{
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1);
        if (c.indexOf(nameEQ) != -1) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function set_max_link(argument) {
    var  max_user = document.getElementById('max_user').value;

    if(max_user){
        $.ajax({
            url: "?url=Admins/EditMaxLink",
            method: "POST",
            data:{ 
                max_user: max_user
            },
            success: function(response) {
                console.log(response);
                alert(response);
                //history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   

    }

}

function set_agent_ip_22(){
    var agent_server_ip = document.getElementById('agent_server_ip').value;
    agent_server_ip = agent_server_ip.replace(/\s*/g,""); 
    if(agent_server_ip){
        $.ajax({
            url: "?url=Admins/SetAgentIp",
            method: "POST",
            data:{ 
                ip: agent_server_ip
            },
            success: function(response) {
                console.log(response);
                alert(response);
    
            },
            error: function(xhr, status, error) {
                
            }
        });   

    }

}



function set_agent_type(argument) {
    var  agent_type = document.querySelector('input[name="agent_type"]:checked').value;
    if(agent_type ){
        $.ajax({
            url: "?url=Admins/SetAgentType",
            method: "POST",
            data:{ 
                agent_type: agent_type
            },
            success: function(response) {
                console.log(response);
                alert(response);
                //history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }
 
}

function StatusCheck(action) {
  // 狀態圖示（沿用你的 SVG）
  const work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M9.001.666A8.336 8.336 0 0 0 .668 8.999c0 4.6 3.733 8.334 8.333 8.334s8.334-3.734 8.334-8.334S13.6.666 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Zm-1.666-4.833L5.168 8.666 4.001 9.833l3.334 3.333L14 6.499l-1.166-1.166-5.5 5.5Z" fill="#1E8E3E" fill-rule="evenodd"></path></svg>';
  const not_work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M11.16 5.666 9 7.824 6.843 5.666 5.668 6.841l2.158 2.158-2.158 2.159 1.175 1.175 2.158-2.159 2.159 2.159 1.175-1.175-2.159-2.159 2.159-2.158-1.175-1.175ZM9 .666A8.326 8.326 0 0 0 .668 8.999a8.326 8.326 0 0 0 8.333 8.334 8.326 8.326 0 0 0 8.334-8.334A8.326 8.326 0 0 0 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Z" fill="#D93025" fill-rule="evenodd"></path></svg>';

  // 小工具：把各種回傳（boolean/"true"/"1"/"ok"/"running"...）正規化成布林
  function toBool(v) {
    if (typeof v === 'boolean') return v;
    if (typeof v === 'number')  return v > 0;
    if (typeof v === 'string') {
      const s = v.trim().toLowerCase();
      return ['true','1','ok','on','running','up','yes','y'].includes(s);
    }
    return false;
  }

  // 小工具：安全更新狀態圖示
  function setStatusIcon(elId, isWorking) {
    const el = document.getElementById(elId);
    if (el) el.innerHTML = isWorking ? work_icon : not_work_icon;
  }

  // 依 action 選 URL
  let url = '?url=Admins/AgentTest';
  if (action === 'start') url = '?url=Admins/StartAgent';
  else if (action === 'stop') url = '?url=Admins/CloseAgent';

  $.ajax({
    type: 'POST',
    data: {},
    dataType: 'json',
    url: url,
    beforeSend: function () {
      $('#overlay').removeClass('hidden');
    }
  })
  .done(function (result) {
    setStatusIcon('s_status', toBool(result?.server_status));
    setStatusIcon('c_status', toBool(result?.client_status));
  })
  .fail(function () {
    // 失敗就先標成 not work（也可維持原狀，視需求）
    setStatusIcon('s_status', false);
    setStatusIcon('c_status', false);
  })
  .always(function () {
    $('#overlay').addClass('hidden');
  });
}



/*function idas_update() {

    var ff = document.querySelector('#file-uploader').files;
    var bb = document.getElementById("file-uploader").files[0];
    var form = new FormData();
    form.append("file", bb);

    var url = '?url=Settings/iDas_Update';
    if(url){
        $.ajax({
            type: "POST",
            processData: false,
            cache: false,
            contentType: false,
            data: form,
            dataType: "json",
            url: url,
            success: function(response) {
                console.log(response);
                alert(response);
                document.getElementById("file-uploader").value = '';
            },
            error: function(xhr, status, error) {
                
            }
        });      
    }
   
}*/

function idas_update() {
    var import_file = document.getElementById("file-uploader").files[0];
    var form = new FormData();
    form.append("file", import_file);
    var url = '?url=Settings/iDas_Update';

    // 語言設定
    var language = getCookie('language') || 'en-us';
    var title, confirm_text, empty_file_text;

    if (language === "zh-cn" || language === "zh-tw") {
        title = 'IDAS 更新';
        confirm_text = '您確定要導入 IDAS 更新包嗎？';
        empty_file_text = '請先選擇要上傳的更新檔。';
    } else {
        title = 'IDAS UPDATE';
        confirm_text = 'Are you sure you want to import the IDAS update package?';
        empty_file_text = 'Please select a file to upload.';
    }

    // 未選擇檔案
    if (!import_file) {
        alertify.alert(title, empty_file_text);
        return;
    }

    alertify.confirm(confirm_text, function (result) {
        if (result) {
            document.getElementById('spinner').style.display = 'block'; // 顯示加載動畫

            $.ajax({
                url: url,
                method: "POST",
                data: form,
                processData: false,
                contentType: false,
                dataType: 'json', // ✅ jQuery 自動解析為物件
                success: function (responseData) {
                    // ✅ responseData 已是物件，無需 JSON.parse()
                    setTimeout(function () {
                        document.getElementById('spinner').style.display = 'none';
                        alertify.alert(responseData.res_type, responseData.res_msg, function () {
                            history.go(0); // 重新整理頁面
                        });

                        setTimeout(function () {
                            alertify.closeAll();
                        }, 3000);
                    }, 1000);
                },
                error: function (xhr, status, error) {
                    document.getElementById('spinner').style.display = 'none';
                    alertify.alert('Error', 'An error occurred while uploading the file.');
                    console.error("上傳錯誤：", status, error);
                }
            });
        }
    });
}


function input_check_savebarcode() {

    let conditions = [
        { id: 'barcode_content',  pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]{1,100}$/, min: null, max: null },
        { id: 'barcode_mask_from', pattern: /^[0-9]+$/, min: 1, max: 54 },
        { id: 'barcode_mask_count', pattern: /^[0-9]+$/, min: 1, max: 100 },

    ];

    let isFormValid = true;

    conditions.forEach(function(input) {
        var element = document.getElementById(input.id);
        if (input.id !== 'barcode_content') {
            element.nextElementSibling.innerHTML = `${input.min} ~ ${input.max}`;
        }

        if (!validateInput(element, input.pattern, input.min, input.max)) {
            isFormValid = false;
        }
    });

    return isFormValid;
}

function validateInput(element, pattern, min, max) {
    let value = element.value.trim();
    let isValid = true;

    // 验证空值
    if (value === "") {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 验证正则
    else if (!pattern.test(value)) {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 验证最小值
    else if (min !== null && parseFloat(value) < min) {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 验证最大值
    else if (max !== null && parseFloat(value) > max) {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 通过验证
    else {
        element.classList.remove("is-invalid");
    }

    return isValid;
}


function update_barcode() {

  /* =====================================================
   * 讀取表單欄位（⚠ 一定要放最前面，避免 TDZ）
   * ===================================================== */
  const barcode_id    = document.getElementById("barcode_id")?.value ?? '';
  const barcode_name  = document.getElementById("barcode_name")?.value?.trim() ?? '';
  const barcode_from  = document.getElementById("barcode_from")?.value ?? '';
  const barcode_count = document.getElementById("barcode_count")?.value ?? '';
  const barcode_mode  = document.querySelector("select[name='barcode_mode']")?.value ?? '-1';
  const barcode_job   = document.querySelector("select[name='barcode_job']")?.value ?? '-1';
  const barcode_seq   = document.querySelector("select[name='barcode_seq']")?.value ?? '-1';

  const isEdit = Number(barcode_id) > 0;

  // 🔎 debug（需要時保留，不要可刪）
  console.log('[barcode]', isEdit ? 'EDIT' : 'ADD', barcode_id);

  /* =====================================================
   * 語系偵測
   * ===================================================== */
  const getLang = () => {
    try {
      if (typeof getCookie === 'function' && getCookie('language')) {
        return String(getCookie('language')).toLowerCase();
      }
      const htmlLang = document.documentElement.getAttribute('lang');
      if (htmlLang) return String(htmlLang).toLowerCase();
    } catch (_) {}
    return 'en-us';
  };

  const rawLang = getLang();
  const lang =
    rawLang.includes('zh-tw') || rawLang.includes('hant') || rawLang.includes('tw') ||
    rawLang.includes('hk') || rawLang.includes('mo')
      ? 'zh-tw'
      : rawLang.includes('zh-cn') || rawLang.includes('hans') ||
        rawLang.includes('cn') || rawLang.includes('sg')
          ? 'zh-cn'
          : 'en-us';

  const i18n = {
    'en-us': {
      titleInfo: 'Info',
      titleError: 'Error',
      ok: 'OK',
      cancel: 'Cancel',
      v_job: 'Please select a Job.',
      v_seq: 'Please select a Sequence.',
      v_name: 'Please enter a barcode name.',
      ajaxFail: 'Operation failed. Please try again.',
      ajaxParseFail: 'Invalid server response.',
    },
    'zh-tw': {
      titleInfo: '提示',
      titleError: '錯誤',
      ok: '確定',
      cancel: '取消',
      v_job: '請先選擇工作（Job）。',
      v_seq: '請先選擇序列（SEQ）。',
      v_name: '請輸入條碼名稱。',
      ajaxFail: '操作失敗，請稍後再試。',
      ajaxParseFail: '伺服器回傳格式不正確。',
    },
    'zh-cn': {
      titleInfo: '提示',
      titleError: '错误',
      ok: '确定',
      cancel: '取消',
      v_job: '请先选择工作（Job）。',
      v_seq: '请先选择序列（SEQ）。',
      v_name: '请输入条码名称。',
      ajaxFail: '操作失败，请稍后再试。',
      ajaxParseFail: '服务器返回格式不正确。',
    }
  }[lang];

  /* =====================================================
   * alertify 語系
   * ===================================================== */
  try {
    if (alertify?.defaults?.glossary) {
      alertify.defaults.glossary.ok = i18n.ok;
      alertify.defaults.glossary.cancel = i18n.cancel;
      alertify.defaults.glossary.title = i18n.titleInfo;
    } else if (typeof alertify.okBtn === 'function') {
      alertify.okBtn(i18n.ok).cancelBtn(i18n.cancel);
    }
  } catch (_) {}

  /* =====================================================
   * 表單驗證
   * ===================================================== */
  if (barcode_job === "-1") {
    alertify.alert(i18n.titleInfo, i18n.v_job);
    return;
  }

  if (barcode_mode === "3" && barcode_seq === "-1") {
    alertify.alert(i18n.titleInfo, i18n.v_seq);
    return;
  }

  if (!barcode_name) {
    alertify.alert(i18n.titleInfo, i18n.v_name);
    return;
  }

  /* =====================================================
   * Spinner
   * ===================================================== */
  const spinner = document.getElementById('spinner');
  if (spinner) spinner.style.display = 'block';

  const $ = window.jQuery;

  /* =====================================================
   * AJAX
   * ===================================================== */
  $.ajax({
    url: "?url=Settings/Update_Barcode",
    method: "POST",
    data: {
      barcode_id:    barcode_id,   // ⭐ 新增 / 修改 判斷關鍵
      barcode_name:  barcode_name,
      barcode_from:  barcode_from,
      barcode_count: barcode_count,
      barcode_job:   barcode_job,
      barcode_seq:   barcode_seq,
      barcode_mode:  barcode_mode
    },
    success: function (response) {

      let responseData;
      try {
        responseData = (typeof response === 'object') ? response : JSON.parse(response);
      } catch (e) {
        if (spinner) spinner.style.display = 'none';
        alertify.alert(i18n.titleError, i18n.ajaxParseFail);
        return;
      }

      setTimeout(() => {
        if (spinner) spinner.style.display = 'none';

        const resType = responseData?.res_type === 'error'
          ? i18n.titleError
          : i18n.titleInfo;

        const resMsg = responseData?.res_msg ?? '';

        alertify.alert(resType, resMsg, () => {
          sessionStorage.setItem('Barcode_Setting', 'block');
          sessionStorage.setItem('Controller_Setting', 'none');
        });

        // 自動刷新列表
        setTimeout(() => {
          alertify.closeAll();

          $.ajax({
            url: "?url=Settings/show_Barcodes",
            method: "GET",
            success: function (html) {
              $('#total_barcodes').html(html);
              if (typeof OpenButton === 'function') {
                OpenButton('Barcode');
              }
            },
            error: function () {
              alertify.alert(i18n.titleError, i18n.ajaxFail);
            }
          });

        }, 3000);

      }, 800);
    },
    error: function (xhr, status, error) {
      if (spinner) spinner.style.display = 'none';
      console.error('[Update_Barcode]', status, error, xhr?.responseText);
      alertify.alert(i18n.titleError, i18n.ajaxFail);
    }
  });
}






function agent_ip_save() {
  const ipEl = document.getElementById('agent_server_ip');
  const feedbackEl = (ipEl?.nextElementSibling && ipEl.nextElementSibling.classList.contains('invalid-feedback'))
    ? ipEl.nextElementSibling
    : null;

  // 清除欄位提示
  if (feedbackEl) {
    feedbackEl.textContent = '';
    feedbackEl.style.display = 'none';
  }

  // 語系
  let lang = (typeof getCookie === 'function' ? getCookie('language') : 'en-us') || 'en-us';
  lang = String(lang).toLowerCase().replace('_', '-');
  if (lang === 'en') lang = 'en-us';
  if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

  const MSG = {
    'en-us': 'Please enter a valid IP address.',
    'zh-tw': '請輸入有效的 IP 地址。',
    'zh-cn': '请输入有效的 IP 地址。'
  };
  const TITLE = { 'en-us': 'Warning', 'zh-tw': '警告', 'zh-cn': '警告' }[lang];
  const OKLBL  = { 'en-us': 'OK', 'zh-tw': '確定', 'zh-cn': '确定' }[lang];
  const CCLBL  = { 'en-us': 'Cancel', 'zh-tw': '取消', 'zh-cn': '取消' }[lang];

  const ip = (ipEl.value || '').trim();
  const ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

  // 先清狀態
  ipEl.classList.remove('is-invalid');

  // ❌ 驗證失敗
  if (!ip || !ipRegex.test(ip)) {
    ipEl.classList.add('is-invalid');

    if (!ipEl._bindInvalidClear) {
      ipEl.addEventListener('input', function onIn() {
        ipEl.classList.remove('is-invalid');
        ipEl.removeEventListener('input', onIn);
        ipEl._bindInvalidClear = false;
      });
      ipEl._bindInvalidClear = true;
    }

    alertify.confirm(
      TITLE,
      MSG[lang],
      function onOk() {
        ipEl.focus();
        ipEl.select?.();
      },
      function onCancel() {
        // 取消時，直接清掉紅框
        ipEl.classList.remove('is-invalid');
      }
    ).set('labels', { ok: OKLBL, cancel: CCLBL });

    return; // 不送出
  }

  // ✅ 驗證通過 → 呼叫後端
  const spinner = document.getElementById('spinner');
  if (spinner) spinner.style.display = 'block';

  $.ajax({
    url: "?url=Admins/SetAgentIp",
    method: "POST",
    data: { agent_server_ip: ip },
    success: function (response) {
      let res = {};
      try { res = JSON.parse(response) || {}; } catch {}

      if (spinner) spinner.style.display = 'none';

      alertify.confirm(
        res.res_type || 'Info',
        res.res_msg || 'Done.',
        function onOk() {
          sessionStorage.setItem('Connect_Setting', 'block');
          sessionStorage.setItem('Controller_Setting', 'none');
        },
        function onCancel() {
          // 取消時不用做事
        }
      ).set('labels', { ok: OKLBL, cancel: CCLBL });

      setTimeout(() => alertify.closeAll(), 3000);

      if (res.res_number != null) ipEl.value = res.res_number;
    },
    error: function (xhr) {
      if (spinner) spinner.style.display = 'none';
      alertify.confirm(
        'Error',
        (xhr && xhr.responseText) || 'Request failed.',
        null,
        null
      ).set('labels', { ok: OKLBL, cancel: CCLBL });
    }
  });
}



function agent_type_save() {
  const agent_type = document.querySelector('input[name="agent_type"]:checked')?.value;
  if (!agent_type) return;

  // 語系
  let lang = (typeof getCookie === 'function' ? getCookie('language') : 'en-us') || 'en-us';
  lang = String(lang).toLowerCase().replace('_', '-');
  if (lang === 'en') lang = 'en-us';
  if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

  const OKLBL = { 'en-us': 'OK', 'zh-tw': '確定', 'zh-cn': '确定' }[lang];
  const CCLBL = { 'en-us': 'Cancel', 'zh-tw': '取消', 'zh-cn': '取消' }[lang];

  document.querySelector(".main-content").classList.add("overlay-active");
  document.getElementById('spinner').style.display = 'block';

  $.ajax({
    url: "?url=Admins/SetAgentType",
    method: "POST",
    data: { agent_type },
    success: function (response) {
      let res = {};
      try { res = JSON.parse(response) || {}; } catch {}

      // 改用 confirm → 有 OK + Cancel
      alertify.confirm(
        res.res_type || 'Info',
        res.res_msg || 'Done.',
        function onOk() {
          // OK：保留成功狀態
        },
        function onCancel() {
          // Cancel：可以額外處理，例如還原 radio 按鈕
        }
      ).set('labels', { ok: OKLBL, cancel: CCLBL });

      setTimeout(function () {
        alertify.closeAll();
        document.getElementById('spinner').style.display = 'none';
        document.querySelector(".main-content").classList.remove("overlay-active");
      }, 3000);
    },
    error: function (xhr) {
      document.getElementById('spinner').style.display = 'none';
      document.querySelector(".main-content").classList.remove("overlay-active");

      alertify.confirm(
        'Error',
        (xhr && xhr.responseText) || 'Request failed.',
        null,
        null
      ).set('labels', { ok: OKLBL, cancel: CCLBL });
    }
  });
}


document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('barcode_name');
    if (input) {
        input.addEventListener('input', function() {
            var length = this.value.length;
            document.getElementById('barcode_count').value = length;
        });
    }
});