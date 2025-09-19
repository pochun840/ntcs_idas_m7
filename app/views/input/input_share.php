<script>
var job_id; 
var input_event;
var temp;
var tempA;
var tempB;
var selectedValue;
var old_input_event;
var all_job;
var buttonDisabled = false;
var backgroundColorYellow = false;
var input_job;
var temp_event;
let jobTempData = {}; 

$(document).ready(function () {
    highlight_row_input('input_table');
 
    var all_input_job = '<?php echo $data['focused_jobid']?>';
    job_id = all_input_job;
    input_job = all_input_job;
    if(job_id){
        get_input_by_job_id(job_id);
        document.getElementById('Button_Select').disabled = true;
        document.getElementById('job_id').style.backgroundColor = 'yellow';
    }

});

document.addEventListener('DOMContentLoaded', function() {
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      var headerElements = document.querySelectorAll('.ajs-header');
      headerElements.forEach(function(headerElement) {
        headerElement.parentNode.removeChild(headerElement);
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
});

document.getElementById("Event_Option").onchange = function() {
    var selectedValue = this.value; 
    handleEventChange(selectedValue); 
};

// 放在共用工具區
function enableRadioById(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.disabled = false;
  el.classList.remove('disabled_input');
  el.style.color = '';
  el.style.pointerEvents = '';
  el.style.opacity = '';
}

function disableRadioById(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.disabled = true;
  el.classList.add('disabled_input');
  el.style.color = 'gray';
  el.style.pointerEvents = 'none';
  el.style.opacity = '0.6';
}

function hasAnySourceEvents(jobid) {
  const data = jobTempData && jobTempData[jobid];
  return !!(data && Array.isArray(data.temp_event) && data.temp_event.length > 0);
}




// 從 temp / jobTempData 解析出「被使用的 pin 編號」
function extractUsedPinsFromTemp(arr) {
  if (!Array.isArray(arr)) return new Set();
  const pins = new Set();
  arr.forEach(id => {
    // 允許 pin3_high / pin3_low / edit_pin3_high / edit_pin3_low 等格式
    const m = String(id).match(/(?:^|_)pin(\d+)/i);
    if (m && m[1]) pins.add(String(parseInt(m[1], 10)));
  });
  return pins;
}

// 解鎖所有編輯用 pin 單選鈕
function unlockAllEditPins() {
  document.querySelectorAll('input[type="radio"][id^="edit_pin"]').forEach(r => {
    r.disabled = false;
    r.classList.remove('disabled_input');
    r.style.color = '';
    r.style.pointerEvents = '';
    r.style.opacity = '';
  });
}

function disableUsedPinsExcept(currentPin) {
  const data = (window.jobTempData && window.jobTempData[window.job_id]) || {};
  const used = extractUsedPinsFromTemp(data.temp || window.temp || []);
  used.delete(String(currentPin));
  document.querySelectorAll('input[type="radio"][id^="edit_pin"]').forEach(r => {
    const m = r.id.match(/^edit_pin(\d+)_/i);
    if (!m) return;
    const pinNum = m[1];
    const shouldDisable = used.has(String(pinNum));
    r.disabled = shouldDisable;
    r.classList.toggle('disabled_input', shouldDisable);
    r.style.color = shouldDisable ? 'gray' : '';
    r.style.pointerEvents = shouldDisable ? 'none' : '';
    r.style.opacity = shouldDisable ? '0.6' : '';
  });
}


/** UI 小工具：切換按鈕樣式與文字 */
function updateUnifiedUI(isOn) {
  const btn = document.getElementById('btn-unified');
  if (!btn) return;
  btn.classList.toggle('is-on', !!isOn);
  btn.innerText = isOn ? 'Unified：ON' : 'Unified：OFF';
}

/** 頁面載入時：讀 DB 狀態並「校正」畫面與內部變數 */
document.addEventListener('DOMContentLoaded', () => {
  if (!window.job_id) return;

  $.get('?url=Job/get_input_unified', { jobid: job_id }, function (res) {
    try {
      const data = typeof res === 'string' ? JSON.parse(res) : res;
      const isOn = Number(data?.val) === 1;

      // 只做「校正」，不呼叫 handleUnifiedJobEvent() 以避免誤切換
      if (isOn) {
        // 若 DB 是開，但目前尚未以本 job_id 對齊 → 對齊一次
        if (window.input_job !== window.job_id) {
          if (typeof enableButton === 'function') enableButton();
          if (typeof resetBackgroundColor === 'function') resetBackgroundColor();
          if (typeof alignsubmit === 'function') alignsubmit(job_id); // 對齊成 ON
          window.input_job = window.job_id; // 明確標記目前已是本 job 的 unified
        }
      } else {
        // 若 DB 是關，但目前卻還是本 job_id → 關掉一次
        
        if (window.input_job === window.job_id) {
          if (typeof enableButton === 'function') enableButton();
          if (typeof resetBackgroundColor === 'function') resetBackgroundColor();
          if (typeof resetalignsubmit === 'function') resetalignsubmit(job_id); // 對齊成 OFF
          window.input_job = null;
        }
      }

      updateUnifiedUI(isOn);
      window._unifiedOn = isOn; // 記住目前 DB 狀態（避免每次點都先打 GET）
    } catch (e) {
      console.warn('get_input_unified parse error', e);
    }
  });
});

/** 在 crud_job_event('unified') 中調用的封裝：先更新 DB，再呼叫既有切換機制 */
function toggleUnifiedWithDB() {
    // 依「現在畫面狀態」預測下一個狀態：如果目前不是本 job → 這次會切到 ON，反之切到 OFF
    const willTurnOn = (window.input_job !== window.job_id);
    console.log(job_id);
    console.log(willTurnOn);
  
    // 先送 DB（確保重整後能還原）
    $.post(
        '?url=Jobs/set_input_unified',
        { jobid: job_id, val: willTurnOn ? 1 : 0 },
        function (resp) {
            // 若後端沒正確設 Content-Type: application/json，
            // 這裡可能拿到字串，先嘗試解析
            if (typeof resp === 'string') {
            try { resp = JSON.parse(resp); } catch(e) {}
            }
            console.log('server resp:', resp);

            if (resp && (resp.ok === true || resp.status === 'ok')) {
            handleUnifiedJobEvent();
            const on = ('unified' in resp) ? !!resp.unified : willTurnOn;
            window._unifiedOn = on;
            updateUnifiedUI(on);
            if (resp.focused_jobid != null) window.BOOT_FOCUSED_JOBID = String(resp.focused_jobid);
            } else {
            const msg = (resp && (resp.msg || resp.message)) || 'Failed to update unified state.';
            if (window.alertify) alertify.error(msg);
            }
        },
        'json' // 告訴 jQuery 期待 JSON（需後端回傳可被當作 JSON 解析）
        ).fail(function (xhr) {
        if (window.alertify) alertify.error('Request failed.');
        console.error('set_input_unified error:', xhr.responseText || xhr);
    });
}

// Div Mode
function toggleDivs() {
    var tableInputSetting = document.getElementById('TableInputSetting');
    var tableDataInput = document.getElementById('TableDataInput');

    if (tableInputSetting.style.display === 'none') {
        tableInputSetting.style.display = 'block';
        tableDataInput.style.display = 'none';
    } else {
        tableInputSetting.style.display = 'none';
        tableDataInput.style.display = 'block';
    }
}

function showTableInputSetting() {
    document.getElementById('TableInputSetting').style.display = 'block';
    document.getElementById('TableDataInput').style.display = 'none';

    document.getElementById('input_menu').style.display = 'block';
}

// Get the modal
var modal = document.getElementById('newinput');


window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

function toggleDivs() {
    var tableInputSetting = document.getElementById('TableInputSetting');
    var tableDataInput = document.getElementById('TableDataInput');

    if (tableInputSetting.style.display === 'none') {
        tableInputSetting.style.display = 'block';
        tableDataInput.style.display = 'none';
    } else {
        tableInputSetting.style.display = 'none';
        tableDataInput.style.display = 'block';
    }
}

function getLanguageMessage(cookieName) {
    var value = "; " + document.cookie;
    var parts = value.split("; " + cookieName + "=");
    var language = (parts.length == 2) ? parts.pop().split(";").shift() : '';
    var message;
    if (language === 'en-us') {
        message =  'Please select the event to delete';
    } else if (language === 'zh-cn') {
        message =  '请选择要删除的事件';
    } else if (language === 'zh-tw') {
        message =  '請點選要刪除的事件';
    } else {
        message =  'Please select the event to delete';
    }
    alertify.alert(message);
}



// 🟩 個別處理邏輯封裝

function handleNewEvent() {
    disableRadioList(temp);
    disableOptions('#Event_Option', tempA, false, true);       // ✅ 重置後再禁用 tempA
    disableOptions('#Event_Option', temp_event, true, false);  // ✅ 只針對 temp_event 顯示灰色，不重設

    showOverlay();
    document.getElementById('newinput').style.display = 'block';
}

function handleEditEvent() {
    disableOptions('#edit_Event_Option');
    disableRadioList(temp);

    get_input_info(job_id, input_event);
    handleEventChange(input_event);
}

function handleCopyEvent() {
    // 建議從後端渲染階段先塞好 window.jobinfo 全域變數
    const jobName = window.jobinfo?.[job_id]?.JOBname || '';
    document.getElementById("from_job_id").value = job_id;
    document.getElementById("from_job_name").value = jobName;

    // disable 已選的 job
    const selectElement = document.getElementById('JobSelect1');
    Array.from(selectElement.options).forEach(opt => {
        if (opt.value === job_id) {
            opt.disabled = true;
            opt.classList.add('disabled_input');
        }
    });
    document.getElementById('copyinput').style.display = 'block';
    const selectedRows = document.querySelectorAll('#input_jobid_select tr.selected');
}

function handleUnifiedEvent() {
    enableButton();
    resetBackgroundColor();
    if (input_job !== job_id) {
        alignsubmit(job_id);
    } else {
        resetalignsubmit(job_id);
    }
}

// 🟩 共用輔助函式

function disableRadioList(radioIds) {

    console.log(radioIds);
    if (Array.isArray(radioIds)) {
        radioIds.forEach(id => {
            const radio = document.getElementById(id);
            if (radio?.type === 'radio') {
                radio.disabled = true;
            }
        });
    }
}



function tablesubmit(keyno){
    if(keyno =='show'){
        document.getElementById('TableDataInput').style.display = 'block';
        document.getElementById('input_menu').style.display = 'none';
        get_input_by_job_id(job_id);
    }
}


function get_input_by_job_id(jobid, arg2) {
  if (!jobid) return;

  // 第二參數允許是 callback 或 { autoOpenNewInput, closeModals, callback }
  let opts = {};
  let callback = null;
  if (typeof arg2 === 'function') {
    callback = arg2;
  } else if (arg2 && typeof arg2 === 'object') {
    opts = arg2;
    callback = typeof arg2.callback === 'function' ? arg2.callback : null;
  }

  const autoOpenNewInput = !!opts.autoOpenNewInput; // 預設 false：不要自動打開 #newinput
  const closeModals      = !!opts.closeModals;      // 可選：刷新後保險關閉任何 modal

  $.ajax({
    url: "?url=Inputs/get_input_by_job_id",
    method: "POST",
    data: { jobid: jobid },
    success: function (response) {
      // 1) 安全解析
      let data;
      try {
        data = (typeof response === 'string') ? JSON.parse(response) : response;
      } catch (e) {
        console.error("parse get_input_by_job_id response failed:", e, response);
        return;
      }

      // 2) 取值並保底成陣列
      const job_inputlist = data?.job_inputlist || '';
      temp       = Array.isArray(data?.temp)       ? data.temp       : [];
      tempA      = Array.isArray(data?.tempA)      ? data.tempA      : [];
      temp_event = Array.isArray(data?.temp_event) ? data.temp_event : [];

      // 3) 更新列表與欄位
      const listEl = document.getElementById("input_jobid_select");
      if (listEl) listEl.innerHTML = job_inputlist;
      const jobSel = document.getElementById("JobSelect");
      if (jobSel) jobSel.style.display = 'none';
      const jobIdInput = document.getElementById("job_id");
      if (jobIdInput) jobIdInput.value = jobid;

      // 4) 更新快取：後續「編輯/複製」要依這份資料判斷
      jobTempData[jobid] = { temp, tempA, temp_event };

      // 5) 清空目前選取，避免殘值
      input_event = null;
      old_input_event = null;

      // 6) 用事件委派綁一次 click（避免重複綁定）
      const table = document.getElementById('input_jobid_select');
      if (table && !table._boundRowClick) {
        table.addEventListener('click', function (e) {
          const tr = e.target.closest('tr');
          if (!tr || !table.contains(tr)) return;
          table.querySelectorAll('tr').forEach(r => r.classList.remove('selected'));
          input_event = tr.className;
          old_input_event = tr.className;
          tr.classList.add('selected');
        });
        table._boundRowClick = true;
      }

      // 7) 語系切換按鈕文字
      if (typeof updateInputButtonLabels === 'function') {
        updateInputButtonLabels(getCookie?.('language'));
      }

      // 8) 是否要依快取狀態禁用項目並「打開新增視窗」
      //    只有在你真的要進入「新增事件」流程時才交給 handleNewJobEvent() 去開 modal
      if (autoOpenNewInput && typeof handleNewJobEvent === 'function') {
        handleNewJobEvent();
      }

      // 9) 如果要求刷新後強制關閉所有 modal（避免 #newinput 被又打開）
      if (closeModals) {
        const close = id => document.getElementById(id)?.style.setProperty('display', 'none', 'important');
        close('newinput');
        close('edit_input');
      }

      // 10) 外部 callback
      if (typeof callback === 'function') callback();
    },
    error: function (xhr, status, error) {
      console.error("AJAX request failed:", status, error);
    }
  });
}


// ✅ 抽出語系文字對應表
const inputLabelMap = {
    "zh-cn": {
        101: "禁用", 102: "启用", 103: "颗数清除", 104: "确认", 105: "启动", 106: "拆螺丝",
        107: "工序清除", 108: "重启", 109: "一次感应", 110: "自定义1", 111: "自定义2",
        112: "自定义3", 113: "自定义4", 114: "自定义5",115: "自由旋转",116: "跳工序"
    },
    "zh-tw": {
        101: "禁用", 102: "Enable", 103: "清除顆數", 104: "確認", 105: "啟動", 106: "反向",
        107: "工序清除", 108: "重啟", 109: "一次感應", 110: "自定義1", 111: "自定義2",
        112: "自定義3", 113: "自定義4", 114: "自定義5",115: "自由旋轉",116: "跳工序"
    },
    "en-us": {
        101: "Disable", 102: "Enable", 103: "Clear Count", 104: "Confirm", 105: "Start", 106: "Unscrew",
        107: "Clear Step", 108: "Restart", 109: "Once Sense", 110: "Custom1", 111: "Custom2",
        112: "Custom3", 113: "Custom4", 114: "Custom5",115: "FreeRotate",116: "Skip"
    }
};

// ✅ 更新按鈕標籤
function updateInputButtonLabels(language) {
    const map = inputLabelMap[language] || inputLabelMap["en-us"];
    Object.keys(map).forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = map[id];
    });
}


function handleEventChange(selectedValue) {
    if(selectedValue ==109){
        document.getElementById('work_goc').style.display = 'block';
    }else{
        document.getElementById('work_goc').style.display = 'none';
    }
}

function edit_handleEventChange(selectedValue,gateconfirm) {
    if(selectedValue ==109){
        document.getElementById('edit_work_goc').style.display = 'block';
    }else{
        document.getElementById('edit_work_goc').style.display = 'none';
    }

}

function edit_input_id() {
    const ONCE_SENSE_EVENT = 109;

    const getInputValue = id => document.getElementById(id)?.value || '';
    const getRadioValue = name => {
        const selected = document.querySelector(`input[name="${name}"]:checked`);
        return selected ? selected.value : 0;
    };

    const input_event = getInputValue("edit_Event_Option");
    const pinval = collectPinValues('input[name="edit_pin_option"]');
    const pin_old = pinval[0]?.id || '';
    const input_wave = pinval[0]?.value || '';
    const input_pin = pin_old.match(/\d+/)?.[0] || '';
    const gateconfirm = (parseInt(input_event) === ONCE_SENSE_EVENT) ? getRadioValue("edit_gateconfirm") : 0;

    const pagemode = 1;
    const input_seqid = 0;

    // 防止外部變數不存在
    if (typeof job_id === 'undefined' || typeof old_input_event === 'undefined') {
        console.error("Missing required context variable: job_id or old_input_event");
        return;
    }

    if (job_id) {
        document.getElementById('spinner').style.display = 'block';

        $.ajax({
            url: "?url=Inputs/edit_input_event",
            method: "POST",
            data: {
                job_id,
                input_event,
                input_pin,
                input_wave,
                gateconfirm,
                pagemode,
                input_seqid,
                old_input_event
            },
            success: function (response) {
                input_success_res(response, job_id, get_input_by_job_id, 'edit_input');
                hideOverlay();
            },
            error: function (xhr, status, error) {
                console.error("edit_input_event failed:", status, error);
                alertify.alert("Error", "Failed to update input event.");
            }
        });
    }
}

function enableButton() {
    var button = document.getElementById('Button_Select');
    if (button.disabled) {
        button.disabled = false;
    }
}

function resetBackgroundColor() {
    var jobInput = document.getElementById('job_id');
    if (jobInput.style.backgroundColor === 'yellow') {
        jobInput.style.backgroundColor = '';
    }
}

function collectPinValues(selector) {
    var pinOptions = document.querySelectorAll(selector);
    var selectedValues = [];

    pinOptions.forEach(function(option) {
        if (option.checked){ 
            var radioInfo = {
                id: option.id,
                value: option.value
            };
            selectedValues.push(radioInfo);
        }
    });

    return selectedValues;
}



function alignsubmit(job_id) {
    if (job_id) {
        $.ajax({
            url: "?url=Inputs/input_alljob",
            method: "POST",
            data: {
                job_id: job_id
            },
            success: function (response) {
                get_input_by_job_id(job_id);
                buttonDisabled = !buttonDisabled;
                document.getElementById('Button_Select').disabled = buttonDisabled;
     
                backgroundColorYellow = !backgroundColorYellow;
                if (backgroundColorYellow){
                    document.getElementById('job_id').style.backgroundColor = 'yellow';
                }else{
                    document.getElementById('job_id').style.backgroundColor = '';
                }
            },
            error: function (xhr, status, error) {

            }
        });
    }
}


function delete_input_id(job_id, input_event) {
  if (!job_id) return;

  // 多語系字串
  var lang = getCookie('language');
  var title = 'Delete Event';
  var text  = 'Are you sure you want to delete this event?';
  var okText = 'OK';
  var cancelText = 'Cancel';
  var errMsg = 'Delete failed, please try again later.';
  var cancelledMsg = 'Cancelled';

  if (lang === 'zh-tw') {
    title = '刪除事件';
    text  = '你確定要刪除此事件嗎？';
    okText = '確定';
    cancelText = '取消';
    errMsg = '刪除失敗，請稍後再試！';
    cancelledMsg = '已取消';
  } else if (lang === 'zh-cn') {
    title = '删除事件';
    text  = '你确定要删除该事件吗？';
    okText = '确定';
    cancelText = '取消';
    errMsg = '删除失败，请稍后再试！';
    cancelledMsg = '已取消';
  }

  // 關閉所有相關 modal（加上 !important，避免被樣式覆蓋）
  const closeModals = () => {
    ['newinput', 'edit_input'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.style.setProperty('display', 'none', 'important');
    });
  };

  alertify
    .confirm(
      title,
      text,
      function onOk() {
        // 顯示遮罩＋spinner
        document.querySelector(".main-content")?.classList.add("overlay-active");
        document.getElementById('spinner').style.display = 'block';

        $.ajax({
          url: "?url=Inputs/delete_input",
          method: "POST",
          data: { job_id: job_id, input_event: input_event },
          success: function (response) {
            // 讓 input_success_res 先刷新列表；刷新完成後再關 modal/遮罩
            input_success_res(
              response,
              job_id,
              function afterRefresh() {
                closeModals();   // 確保 #newinput / #edit_input 都是關的
                hideOverlay();   // 關半透明遮罩
              },
              'edit_input'       // 保持原本要隱藏的元素 id
            );
          },
          error: function () {
            alertify.error(errMsg);
            document.querySelector(".main-content")?.classList.remove("overlay-active");
            document.getElementById('spinner').style.display = 'none';
          }
        });
      },
      function onCancel() {
        closeModals(); // 取消時也關掉
        document.querySelector(".main-content")?.classList.remove("overlay-active");
        hideOverlay();
        // alertify.message(cancelledMsg);
      }
    )
    .set('labels', { ok: okText, cancel: cancelText });
}




function setSelectDisabled(disabled) {
    var btn = document.getElementById('Button_Select');
    if (!btn) return;
    btn.disabled = !!disabled;
    btn.classList.toggle('is-disabled', !!disabled);
  }

  // 小工具：統一更新 UI（黃色 + value + 按鈕 disabled）
  function updateUnifiedUI(isOn, value) {
    var el = document.getElementById('job_id');
    if (!el) return;

    el.classList.toggle('unified', !!isOn);
    if (typeof value !== 'undefined' && value !== null) {
      el.value = String(value);
    }
    setSelectDisabled(!!isOn);
  }

  // 進頁面：如果後端有聚焦的 job（$focusedJobId），就顯示並上黃色，且禁用 Button_Select
  document.addEventListener('DOMContentLoaded', function () {
    var focusedJobId = <?php echo json_encode(isset($focusedJobId) ? $focusedJobId : null, JSON_UNESCAPED_UNICODE); ?>;
    var hasVal = (focusedJobId !== null && String(focusedJobId).length > 0);
    updateUnifiedUI(hasVal, hasVal ? focusedJobId : '');
  });

function crud_job_event(action) {
    if (!job_id) return;

    switch (action) {
        case 'new':
            
            // 如果 work_goc 顯示中，先隱藏它
            const workGocEl = document.getElementById('work_goc');
            if (workGocEl && getComputedStyle(workGocEl).display === 'block') {
                workGocEl.style.display = 'none';
            }
            
            // ✅ 重設畫面項目狀態
            resetElementsByPrefix();

            // ✅ 根據目前 job_id 快取資料，禁用已使用的項目
            handleNewJobEvent();

            // ✅ 開啟 modal
            const newInputModal = document.getElementById('newinput');
            if (getComputedStyle(newInputModal).display === 'none') {
                showOverlay();
                newInputModal.style.display = 'block';
            }

            // ✅ 綁定：禁止點視窗外關閉（只綁一次）
            bindPreventOutsideCloseNewInput();

        break;

        case 'del':
            document.querySelector(".main-content").classList.add("overlay-active");
            if (input_event) {
                showOverlay();
                delete_input_id(job_id, input_event);
            }
        break;

        case 'edit':
            document.querySelector(".main-content").classList.add("overlay-active");
            if (!input_event) return;

            const selectedEditRows = document.querySelectorAll('#input_jobid_select tr.selected');
            if (!selectedEditRows.length) {
                return;
            }
            showOverlay();
            handleEditJobEvent();   
        break;

        case 'copy': {
                        const messages = {
                            'en-us': "No event available to copy",
                            'zh-tw': "沒有可複製的事件",
                            'zh-cn': "没有可复制的事件"
                        };
                        const lang = getCookie('language');
                        const msg = messages[lang] || messages['en-us'];

                        document.querySelector(".main-content").classList.add("overlay-active");

                        // ✅ 用快取判斷來源是否有任何事件
                        if (!hasAnySourceEvents(job_id)) {
                            if (typeof alertify !== 'undefined') {
                            alertify.alert(msg);
                            setTimeout(() => alertify.closeAll(), 3000);
                            }
                            document.querySelector(".main-content")?.classList.remove("overlay-active");
                            if (typeof hideOverlay === 'function') hideOverlay();
                            return;
                        }

                        // ✅ 真的有事件才開「複製」對話框
                        handleCopyJobEvent();
                        showOverlay();
                        break;
                    }



       
        case 'unified':
            // 保留你既有機制
            handleUnifiedJobEvent();

            // 依目前顯示狀態做 UI 切換：
            // 若現在是黃色→執行 unified 後視為關閉（恢復灰色 + 啟用按鈕）
            // 若現在不是黃色→執行 unified 後視為開啟（變黃色 + 禁用按鈕）
            (function () {
            var el = document.getElementById('job_id');
            if (!el) return;

            var willTurnOn = !el.classList.contains('unified');
            // 變成 ON 時，把輸入框顯示目前的 job_id（全域變數）
            var valueToShow = willTurnOn ? (window.job_id || el.value || '') : el.value;

            updateUnifiedUI(willTurnOn, valueToShow);
            })();
        break;

        default:
            console.warn(`Unknown action type: ${action}`);
        break;
    }
}


function bindPreventOutsideCloseNewInput() {
    if (window._bindPreventOutsideCloseNewInput) return; // 已綁過就不重綁
    window._bindPreventOutsideCloseNewInput = true;

    const modal   = document.getElementById('newinput');
    const overlay = document.getElementById('modal-overlay');

    if (!modal) return;

    // 內部點擊不往外冒泡（防止被全域 click handler 關閉）
    modal.addEventListener('click', e => e.stopPropagation());

    // 若有 overlay，也避免 overlay 的 click 關掉視窗
    overlay?.addEventListener('click', e => e.stopPropagation());

    // 捕獲階段攔截外部點擊，確保任何全域 click 不會關掉它
    document.addEventListener('click', function (e) {
        if (modal.style.display === 'block' && !modal.contains(e.target)) {
            // 什麼都不做，只是阻止往下傳
            e.stopPropagation();
        }
    }, true); // ← 用捕獲階段
}


let allowCloseNewInput = true;

function handleNewJobEvent() {
    const data = jobTempData[job_id] || { temp: [], tempA: [], temp_event: [] };

    // ✅ 重設按鈕狀態
    resetElementsByPrefix();

    // ✅ 清除所有禁用狀態並重新套用
    disableOptions('#Event_Option', [], false, true);
    disableElementsByIdList(data.temp);
    disableOptions('#Event_Option', data.tempA, false, false);  // 隱藏
    disableOptions('#Event_Option', data.temp_event, true, false); // 灰色禁用

    // ✅ 如果目前選中的 <option> 是已禁用的，就清空選擇
    const selectEl = document.getElementById('Event_Option');
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    if (selectedOption && selectedOption.disabled) {
        selectEl.value = "-1"; // 切回預設提示選項
        selectEl.dispatchEvent(new Event('change')); // 若有需要觸發 onchange
    }

    // ✅ 顯示 Modal（如果尚未開啟）
    const newInputModal = document.getElementById('newinput');
    const isCurrentlyVisible = getComputedStyle(newInputModal).display !== 'none';
    if (!isCurrentlyVisible) {
        showOverlay();
        newInputModal.style.display = 'block';
    }
}



function resetElementsByPrefix() {
    const selectors = [
        '[id^="pin"]',
        '[id^="edit_pin"]'
    ];
    const elements = document.querySelectorAll(selectors.join(','));
    elements.forEach(el => {
        el.disabled = false;
        el.classList.remove('disabled_input');
        el.style.color = '';
    });
}



function handleEditJobEvent() {

  // ❌ 這行會把所有已用 pin 鎖死，導致不能換別的 pin
  // disableRadioList(temp);

  // 交給 get_input_info 在資料回來後，依目前事件 pin 做更精準的鎖定
  get_input_info(job_id, input_event);
  handleEventChange(input_event);
}

function parsePhpArrayDumpToObject(txt) {
  // 解析單層 print_r：Array ( [JOBID] => 1 [JOBname] => XXX )
  const obj = {};
  if (typeof txt !== 'string') return obj;
  const oneLine = txt.replace(/\r?\n/g, ' ').replace(/\s+/g, ' ').trim();
  const re = /\[\s*([^\]]+?)\s*\]\s*=>\s*([^[]+?)(?=\s*\[\s*|$)/g;
  let m;
  while ((m = re.exec(oneLine)) !== null) {
    const key = m[1].trim();
    let val = m[2].trim();
    if ((val.startsWith('"') && val.endsWith('"')) || (val.startsWith("'") && val.endsWith("'"))) {
      val = val.slice(1, -1);
    }
    obj[key] = val;
  }
  return obj;
}

function handleCopyJobEvent() {
  // 清空舊資料
  const fromIdEl = document.getElementById("from_job_id");
  const fromNameEl = document.getElementById("from_job_name");
  if (fromIdEl) fromIdEl.value = '';
  if (fromNameEl) fromNameEl.value = '';

  // 先放 job_id
  if (fromIdEl) fromIdEl.value = job_id;

  // 顯示 modal + overlay（先開，避免等待期間卡流程）
  showOverlay();
  const modal = document.getElementById('copyinput');
  if (modal) modal.style.display = 'block';

  // 重置 select：全部啟用 → 再禁用同 job_id
  const select = document.getElementById('JobSelect1');
  if (select) {
    Array.from(select.options).forEach(opt => {
      opt.disabled = false;
      opt.classList.remove('disabled_input');
    });
    Array.from(select.options).forEach(opt => {
      if (opt.value === String(job_id)) {
        opt.disabled = true;
        opt.classList.add('disabled_input');
      }
    });
  }

  // 後端實際回傳不一定是 JSON（常見是 print_r），用 text + 雙路解析
  $.ajax({
    url: "?url=Jobs/search_job",
    type: "POST",
    data: { jobid: job_id },
    dataType: "text"
  })
  .done(function (txt) {
    let jobName = '';

    // 路 1：嘗試 JSON
    try {
      const res = JSON.parse(txt);
      if (res && res.res_type === "success") {
        const jobs = Array.isArray(res.jobs) ? res.jobs : (res.jobs ? [res.jobs] : []);
        const hit = jobs.find(j => String(j.JOBID) === String(job_id));
        if (hit) jobName = hit.JOBname || hit.JOBNAME || hit.job_name || '';
      }
    } catch (e) {
      // 路 2：print_r 解析
      const obj = parsePhpArrayDumpToObject(txt);
      if (String(obj.JOBID) === String(job_id)) {
        jobName = obj.JOBname || obj.JOBNAME || obj.job_name || '';
      }
    }

    if (fromNameEl) fromNameEl.value = jobName;
  })
  .fail(function (xhr, status, error) {
    console.error("AJAX 取得 Job 名稱錯誤:", error, xhr && xhr.responseText);
    if (fromNameEl) fromNameEl.value = '';
    // 視需要決定要不要自動關 overlay；這裡先不關，讓使用者自行關閉
    // hideOverlay();
  });
}






function handleUnifiedJobEvent() {
    enableButton();
    resetBackgroundColor();

    if (input_job !== job_id) {
        alignsubmit(job_id);
    } else {
        resetalignsubmit(job_id);
    }
}


function disableElementsByIdList(ids) {
    if (!Array.isArray(ids)) return;

    ids.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.disabled = true;
            el.classList.add('disabled_input');
            el.style.color = 'gray';
        }
    });
}


function disableOptions(selector, values = [], gray = false, reset = false) {
    if (!Array.isArray(values)) return; // 不是陣列直接跳出

    const valueSet = values.map(String); // 全轉字串，避免型別不一致
    const options = document.querySelectorAll(`${selector} option`);
    if (!options.length) return;

    options.forEach(opt => {
        // 如果 reset=true，先重置所有選項
        if (reset) {
            opt.disabled = false;
            opt.style.color = '';
            opt.classList.remove('disabled_input');
            opt.style.display = ''; // 確保顯示
        }

        // 根據 values 設定 disabled 狀態
        if (valueSet.includes(opt.value)) {
            opt.disabled = true;
            opt.classList.add('disabled_input');
            if (gray) {
                opt.style.color = 'gray'; // 灰色標示
            } else {
                opt.style.display = 'none'; // 如果沒灰色，用隱藏
            }
        }
    });
}




function disableSelectOptions(selector) {
    const select = document.querySelector(selector);
    if (!select) return;
    select.disabled = true;
    Array.from(select.options).forEach(opt => {
        opt.disabled = true;
        opt.classList.add('disabled_input');
    });
}

function create_input_id(){
 
    var input_event = document.getElementById("Event_Option").value;
    var pinval      = collectPinValues('input[name="pin_option"]');
    var pin_old   = pinval[0]['id'];
    var input_wave  = pinval[0]['value'];
    var pagemode    = 1;
    var input_seqid = 0;

    //檢查是否有選擇 pin，如果沒有就中斷
    if (!pinval || pinval.length === 0) {
        return;
    }

    if(input_event == 109){
        var selectedOption = document.querySelector('input[name="gateconfirm"]:checked');
        var gateconfirm    = selectedOption ? selectedOption.value : 0;
    }else{
        var gateconfirm	 = 0;
    }


    var input_pin = pin_old.match(/\d+/)[0];
    if(job_id){
       document.getElementById('spinner').style.display = 'block';

        $.ajax({
            url: "?url=Inputs/create_input_event",
            method: "POST",
            data: { 
                job_id: job_id,
                input_event: input_event,
                input_pin: 	input_pin,
                input_wave: input_wave,
                gateconfirm: gateconfirm,
                pagemode: pagemode,
                input_seqid: input_seqid
            },
            success: function (response) {
                input_success_res(response, job_id, get_input_by_job_id, 'newinput');
                get_input_by_job_id(job_id);
                hideOverlay();

            },
            error: function(xhr, status, error) {
                
            }
        });

    }
}

function clearNewInputForm() {
    // 清空下拉選單
    const eventOption = document.getElementById("Event_Option");
    if (eventOption) eventOption.selectedIndex = 0;

    // 清除 pin 選擇
    document.querySelectorAll('input[name="pin_option"]').forEach(el => el.checked = false);

    // 清除 gateconfirm（如有）
    document.querySelectorAll('input[name="gateconfirm"]').forEach(el => el.checked = false);

    // 其他欄位如有可自行加上清空
    // document.getElementById("some_field")?.value = '';
}

function copy_input_id() {
  var language = getCookie('language') || 'en-us';
  var messages = {
    'zh-cn': '若设定已存在，将会取代原有设定',
    'zh-tw': '若設定已存在，將會取代原有設定',
    'en-us': 'If the job input already exists, it will replace the original setting'
  };
  var text_info = messages[language] || messages['en-us'];

  var labels = {
    'zh-cn': { ok: '确定', cancel: '取消', err: '复制失败，请稍后再试！', cancelled: '已取消', none: '没有可复制的事件' },
    'zh-tw': { ok: '確定', cancel: '取消', err: '複製失敗，請稍後再試！', cancelled: '已取消', none: '沒有可複製的事件' },
    'en-us': { ok: 'OK', cancel: 'Cancel', err: 'Copy failed, please try again later.', cancelled: 'Cancelled', none: 'No event available to copy' }
  };
  var okText = labels[language]?.ok || labels['en-us'].ok;
  var cancelText = labels[language]?.cancel || labels['en-us'].cancel;
  var errMsg = labels[language]?.err || labels['en-us'].err;
  var cancelledMsg = labels[language]?.cancelled || labels['en-us'].cancelled;
  var noneMsg = labels[language]?.none || labels['en-us'].none;

  // ✅ 來源沒有任何事件 → 直接擋下
  if (!hasAnySourceEvents(job_id)) {
    alertify.alert(noneMsg);
    setTimeout(() => alertify.closeAll(), 2500);
    return;
  }

  var confirmDialog = alertify
    .confirm(text_info, function (confirmed) {
      clearTimeout(autoCancelTimer);
      if (!confirmed) {
        alertify.message(cancelledMsg);
        return;
      }

      var to_job_id = document.getElementById("JobSelect1").value;
      if (!to_job_id) return;

      // ✅ 防呆：不可複製到自己
      if (String(to_job_id) === String(job_id)) {
        alertify.alert(labels[language]?.same || 'Target job must be different.');
        setTimeout(() => alertify.closeAll(), 2500);
        return;
      }

      document.getElementById('spinner').style.display = 'block';

      $.ajax({
        url: "?url=Inputs/copy_input_event",
        method: "POST",
        data: { from_job_id: job_id, to_job_id: to_job_id },
        success: function (response) {
          input_success_res(response, job_id, get_input_by_job_id, 'copyinput');
          hideOverlay();
        },
        error: function () {
          alertify.error(errMsg);
          document.getElementById('spinner').style.display = 'none';
          document.querySelector(".main-content").classList.remove("overlay-active");
        }
      });
    })
    .set('labels', { ok: okText, cancel: cancelText });

  var autoCancelTimer = setTimeout(function () {
    alertify.closeAll();
  }, 3000);
}


function get_input_info() {
  
  if (!job_id) return;

  $.ajax({
    url: "?url=Inputs/check_job_event_conflict",
    method: "POST",
    data: { job_id: job_id, input_event: input_event },
    success: function (resp) {
      if (!resp || resp === 'no_data') return;

      // --- 標準化回傳 ---
      let obj = {};
      if (typeof resp === 'object') {
        obj.Pin              = resp.Pin ?? resp.pin ?? resp.PIN ?? '';
        obj.signal           = resp.signal ?? resp.Signal ?? resp.SIGNAL ?? '';
        obj.EvenID           = resp.EvenID ?? resp.event ?? resp.EID ?? '';
        obj.Wp_Ready_Confirm = resp.Wp_Ready_Confirm ?? resp.gateconfirm ?? resp.ready ?? '';
      } else {
        const txt = String(resp);
        const pick = (re) => (txt.match(re) || [,''])[1];
        obj.Pin              = pick(/\[Pin]\s*=>\s*([^\s]+)/);
        obj.signal           = pick(/\[signal]\s*=>\s*([^\s]+)/);
        obj.EvenID           = pick(/\[EvenID]\s*=>\s*([^\s]+)/);
        obj.Wp_Ready_Confirm = pick(/\[Wp_Ready_Confirm]\s*=>\s*([^\s]+)/);
      }

      // --- 轉型並檢查 ---
      const pinNum  = parseInt(obj.Pin, 10);
      const signal  = Number.isFinite(parseInt(obj.signal, 10)) ? parseInt(obj.signal, 10) : 1; // 預設 high
      const eventId = Number.isFinite(parseInt(obj.EvenID, 10))  ? parseInt(obj.EvenID, 10)  : 0;
      const gateReady = parseInt(obj.Wp_Ready_Confirm, 10) || 0;
      if (!Number.isFinite(pinNum)) {
        console.warn('Invalid Pin from backend:', obj.Pin);
        return;
      }

      // --- 開啟編輯視窗 ---
      if (typeof showOverlay === 'function') showOverlay();
      const editModal = document.getElementById('edit_input');
      if (editModal) editModal.style.display = 'block';

      // --- 協助函式：徹底啟用/禁用單顆 radio（含樣式） ---
      const fullyEnableRadio = (id) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.disabled = false;
        el.classList.remove('disabled_input');
        el.style.color = '';
        el.style.pointerEvents = '';
        el.style.opacity = '';
      };

      // --- 先全解鎖，再鎖其它事件已用的 pins（排除本事件的 pin） ---
      if (typeof unlockAllEditPins === 'function') unlockAllEditPins();
      if (typeof disableUsedPinsExcept === 'function') disableUsedPinsExcept(String(pinNum));

      // --- 勾選並啟用當前 pin 的高/低兩顆 ---
      const highId = `edit_pin${pinNum}_high`;
      const lowId  = `edit_pin${pinNum}_low`;
      const currId  = (signal === 1 ? highId : lowId);
      const otherId = (signal === 1 ? lowId  : highId);

      fullyEnableRadio(currId);
      fullyEnableRadio(otherId);
      const currRadio = document.getElementById(currId);
      if (currRadio) currRadio.checked = true;

      // --- 設定事件下拉、一次感應區塊 ---
      const eventSel = document.getElementById('edit_Event_Option');
      const gocWrap  = document.getElementById('edit_work_goc');

      if (eventSel) {
        eventSel.value = String(eventId);
        eventSel.onchange = function () {
          const v = parseInt(this.value, 10);
          if (gocWrap) gocWrap.style.display = (v === 109 ? 'block' : 'none');
        };
      }

      if (gocWrap) {
        if (eventId === 109) {
          gocWrap.style.display = 'block';
          const g1 = document.getElementById('edit_gateconfirm_1');
          const g0 = document.getElementById('edit_gateconfirm_0');
          if (g1) g1.checked = (gateReady === 1);
          if (g0) g0.checked = (gateReady === 0);
        } else {
          gocWrap.style.display = 'none';
          const g1 = document.getElementById('edit_gateconfirm_1');
          const g0 = document.getElementById('edit_gateconfirm_0');
          if (g1) g1.checked = false;
          if (g0) g0.checked = false;
        }
      }

      // --- 紀錄舊事件（供送出時使用） ---
      window.old_input_event = eventId;
    },
    error: function (xhr, status, error) {
      console.error("check_job_event_conflict failed:", status, error, xhr && xhr.responseText);
    }
  });
}


function resetalignsubmit(job_id) {

    var job_id_new = 0;

    $.ajax({
        url: "?url=Inputs/input_alljob_cancel",
        method: "POST",
        data: {
            job_id: job_id
        },
        success: function (response) {
            get_input_by_job_id(job_id);
        },
        error: function (xhr, status, error) {

        }
    });

}


function job_confirm(){
    var jobid = document.getElementById("JobNameSelect").value;
    localStorage.setItem("jobid", jobid);
    job_id = jobid;
    all_job = jobid;
    

    if(jobid){
        $.ajax({
            url: "?url=Inputs/get_input_by_job_id",
            method: "POST",
            data:{ 
                jobid: jobid,
            },
            success: function(response) {
                var data = JSON.parse(response);
                var job_inputlist = data.job_inputlist;
                
                temp  = Array.isArray(data.temp) ? data.temp : [];
                tempA = Array.isArray(data.tempA) ? data.tempA : [];


                jobTempData[job_id] = {
                    temp: Array.isArray(data.temp) ? data.temp : [],
                    tempA: Array.isArray(data.tempA) ? data.tempA : [],
                    temp_event: Array.isArray(data.temp_event) ? data.temp_event : []
                };


                document.getElementById("input_jobid_select").innerHTML = job_inputlist;
                document.getElementById("JobSelect").style.display = 'none';
                document.getElementById("job_id").value = jobid;

                var s3Button = document.getElementById('S3');
          
            
                var rows = document.querySelectorAll('#input_jobid_select tr');
                rows.forEach(function(row) {
                    row.addEventListener('click', function() { 
                        input_event = this.className; 
                        old_input_event = this.className;
                    
                    });
                });

                var language = getCookie('language');
                if(language == "zh-cn"){

                    document.getElementById('101') && (document.getElementById('101').textContent = '禁用');
                    document.getElementById('102') && (document.getElementById('102').textContent = '启用');
                    document.getElementById('103') && (document.getElementById('103').textContent = '颗数清除');
                    document.getElementById('104') && (document.getElementById('104').textContent = '确认');
                    document.getElementById('105') && (document.getElementById('105').textContent = '启动');
                    document.getElementById('106') && (document.getElementById('106').textContent = '反向');
                    document.getElementById('107') && (document.getElementById('107').textContent = '工序清除');
                    document.getElementById('108') && (document.getElementById('108').textContent = '重启');
                    document.getElementById('109') && (document.getElementById('109').textContent = '一次感应');
                    document.getElementById('110') && (document.getElementById('110').textContent = '自定义1');
                    document.getElementById('111') && (document.getElementById('111').textContent = '自定义2');
                    document.getElementById('112') && (document.getElementById('112').textContent = '自定义3');
                    document.getElementById('113') && (document.getElementById('113').textContent = '自定义4');
                    document.getElementById('114') && (document.getElementById('114').textContent = '自定义5');
                    document.getElementById('115') && (document.getElementById('115').textContent = '自由旋转');
                    document.getElementById('116') && (document.getElementById('116').textContent = '跳工序');

                
                }else if(language =="zh-tw"){
                    document.getElementById('101') && (document.getElementById('101').textContent = '禁用');
                    document.getElementById('102') && (document.getElementById('102').textContent = 'Enable');
                    document.getElementById('103') && (document.getElementById('103').textContent = '清除顆數');
                    document.getElementById('104') && (document.getElementById('104').textContent = '確認');
                    document.getElementById('105') && (document.getElementById('105').textContent = '啟動');
                    document.getElementById('106') && (document.getElementById('106').textContent = '反向');
                    document.getElementById('107') && (document.getElementById('107').textContent = '工序清除');
                    document.getElementById('108') && (document.getElementById('108').textContent = '重啟');
                    document.getElementById('109') && (document.getElementById('109').textContent = '一次感應');
                    document.getElementById('110') && (document.getElementById('110').textContent = '自定義1');
                    document.getElementById('111') && (document.getElementById('111').textContent = '自定義2');
                    document.getElementById('112') && (document.getElementById('112').textContent = '自定義3');
                    document.getElementById('113') && (document.getElementById('113').textContent = '自定義4');
                    document.getElementById('114') && (document.getElementById('114').textContent = '自定義5');
                    document.getElementById('115') && (document.getElementById('115').textContent = '自由旋轉');
                    document.getElementById('116') && (document.getElementById('116').textContent = '跳工序');
                }


            },
            error: function(xhr, status, error) {
            
            }
        }); 
    }
}


function input_success_res(response, job_id, callbackFn, hideElementId = 'newinput') {
    // 安全解析回傳
    let data;
    try {
        data = (typeof response === 'string') ? JSON.parse(response) : response;
    } catch (e) {
        data = { res_type: 'Info', res_msg: String(response || 'Done') };
    }
    const title = data?.res_type || '';
    const msg   = data?.res_msg  || '';

    // 依語系決定 OK 文案（含常見變體）
    const rawLang = (typeof getCookie === 'function' && getCookie('language')) ||
                    document.documentElement.getAttribute('lang') || 'en-us';
    const l = String(rawLang).toLowerCase();
    let okLabel = 'OK';
    if (l === 'zh-tw' || l.includes('hant') || l.includes('tw') || l.includes('hk') || l.includes('mo')) {
        okLabel = '確定';
    } else if (l === 'zh-cn' || l.includes('hans') || l.includes('cn') || l.includes('sg')) {
        okLabel = '确定';
    }

    // 保險：有些版本要先設全域 glossary 才吃得到
    if (window.alertify?.defaults?.glossary) {
        try { alertify.defaults.glossary.ok = okLabel; } catch (e) {}
    }

    let autoTimer;

    // 顯示彈窗（單次也設 labels，雙保險）
    alertify
        .alert(title, msg, function () {
        clearTimeout(autoTimer);
        finalize();
        })
        .set('labels', { ok: okLabel });

    // 2 秒後自動關閉並收尾
    autoTimer = setTimeout(finalize, 2000);

    function finalize() {
        try { alertify.closeAll(); } catch (e) {}
        document.querySelector(".main-content")?.classList.remove("overlay-active");
        const sp = document.getElementById('spinner');
        if (sp) sp.style.display = 'none';

        // 重置 UI
        const selectEl = document.getElementById('Event_Option');
        if (selectEl) selectEl.value = '-1';
        document.querySelectorAll('input[name="pin_option"]').forEach(r => r.checked = false);
        document.querySelectorAll('input[name="gateconfirm"]').forEach(r => r.checked = false);

        // 更新資料後再回呼
        if (typeof get_input_by_job_id === 'function') {
        get_input_by_job_id(job_id, function () {
            if (typeof callbackFn === 'function') callbackFn();
        });
        } else if (typeof callbackFn === 'function') {
        callbackFn();
        }

        const hideEl = document.getElementById(hideElementId);
        if (hideEl) hideEl.style.display = 'none';
    }
}


function showOverlay() {
    document.getElementById("modal-overlay").style.display = "block";
}

function hideOverlay() {
    document.getElementById("modal-overlay").style.display = "none";
}
</script>



<style>
    #input_table td,
    #input_table th {
        width: 100px; 
        padding: 10px;
    }
</style>