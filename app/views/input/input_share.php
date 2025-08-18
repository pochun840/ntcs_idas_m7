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
 
    var all_input_job = '<?php echo $data['device_data']['device_input_all_job']?>';
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


function get_input_by_job_id(jobid) {
    if (!jobid) return;

    $.ajax({
        url: "?url=Inputs/get_input_by_job_id",
        method: "POST",
        data: { jobid: jobid },
        success: function(response) {
            const data = JSON.parse(response);
            const job_inputlist = data.job_inputlist;
            temp = data.temp;
            tempA = data.tempA;

            document.getElementById("input_jobid_select").innerHTML = job_inputlist;
            document.getElementById("JobSelect").style.display = 'none';
            document.getElementById("job_id").value = jobid;

            // 綁定每列點擊事件
            document.querySelectorAll('#input_jobid_select tr').forEach(row => {
                row.addEventListener('click', function () {
                    input_event = this.className;
                });
            });

            // 語系切換顯示
            updateInputButtonLabels(getCookie('language'));

            // 畫面同步禁用該 job 的項目
            handleNewJobEvent();
        },
        error: function(xhr, status, error) {
            console.error("AJAX request failed:", status, error);
        }
    });
}

// ✅ 抽出語系文字對應表
const inputLabelMap = {
    "zh-cn": {
        101: "禁用", 102: "启用", 103: "颗数清除", 104: "确认", 105: "启动", 106: "拆螺丝",
        107: "工序清除", 108: "重启", 109: "一次感应", 110: "自定义1", 111: "自定义2",
        112: "自定义3", 113: "自定义4", 114: "自定义5"
    },
    "zh-tw": {
        101: "禁用", 102: "Enable", 103: "清除顆數", 104: "確認", 105: "啟動", 106: "拆螺絲",
        107: "工序清除", 108: "重啟", 109: "一次感應", 110: "自定義1", 111: "自定義2",
        112: "自定義3", 113: "自定義4", 114: "自定義5"
    },
    "en": {
        101: "Disable", 102: "Enable", 103: "Clear Count", 104: "Confirm", 105: "Start", 106: "Unscrew",
        107: "Clear Step", 108: "Restart", 109: "Once Sense", 110: "Custom1", 111: "Custom2",
        112: "Custom3", 113: "Custom4", 114: "Custom5"
    }
};

// ✅ 更新按鈕標籤
function updateInputButtonLabels(language) {
    const map = inputLabelMap[language] || inputLabelMap["en"];
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


function delete_input_id(job_id,input_event){

   var language = getCookie('language');
   var text_info, title;

   if (language === "zh-cn") {
       text_info = '你确定吗？';
       title = '刪除任務';
   } else if (language === "zh-tw") {
       text_info = '你確定嗎？';
       title = '刪除任務';
   } else {
       text_info = 'Are you sure?';
       title = 'Delete Event';
   }
   
   if (job_id) {
       alertify.confirm(
           title, // 標題
           text_info, // 提示文字
           function() {
               //使用者選擇「是」後執行刪除動作
               document.getElementById('spinner').style.display = 'block';

               $.ajax({
                   url: "?url=Inputs/delete_input",
                   method: "POST",
                   data: { 
                       job_id: job_id,
                       input_event: input_event
                   },
                   success: function(response) {
                        input_success_res(response, job_id, get_input_by_job_id, 'edit_input');
                        hideOverlay();
                   },
                   error: function(xhr, status, error) {
                       alertify.error("刪除失敗，請稍後再試！");
                       document.querySelector(".main-content").classList.remove("overlay-active"); 
                       document.getElementById('spinner').style.display = 'none';
                   }
               });
            },
            function() {
                // 取消 callback 可選寫在這裡（目前略過）
                document.querySelector(".main-content").classList.remove("overlay-active");
                hideOverlay();
            }
        ).set('labels', {ok:'YES', cancel:'NO'}); // 修改按鈕文字
    }
}



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

        case 'copy':

            document.querySelector(".main-content").classList.add("overlay-active");
            if (!input_event) return;

            
            handleCopyJobEvent();
            showOverlay();
            break;

        case 'unified':
            //showOverlay();
            handleUnifiedJobEvent();
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
    //disableSelectOptions('#edit_Event_Option');
    disableRadioList(temp);

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

   // 建立 confirm 視窗
   var confirmDialog = alertify.confirm(text_info, function (confirmed) {
       // 使用者點選後取消自動關閉計時器
       clearTimeout(autoCancelTimer);
       if (!confirmed) return;

       var to_job_id = document.getElementById("JobSelect1").value;
       if (!to_job_id) return;

       document.getElementById('spinner').style.display = 'block';

       $.ajax({
           url: "?url=Inputs/copy_input_event",
           method: "POST",
           data: {
               from_job_id: job_id,
               to_job_id: to_job_id
           },
           success: function (response) {
             input_success_res(response, job_id, get_input_by_job_id, 'copyinput');
             hideOverlay();
           },
           error: function () {
               alertify.error("複製失敗，請稍後再試！");
               document.getElementById('spinner').style.display = 'none';
               document.querySelector(".main-content").classList.remove("overlay-active");
           }
       });
   });

   // 自動取消邏輯：3 秒後自動關閉 confirm 視窗
   var autoCancelTimer = setTimeout(function () {
       alertify.closeAll(); // 關閉 alertify 視窗
       // 可選提示
       //alertify.message("自動取消複製操作");
   }, 3000);
}

function get_input_by_job_id(jobid, callback){
    $.ajax({
        url: "?url=Inputs/get_input_by_job_id",
        method: "POST",
        data: { 
            jobid: jobid,
        },
        success: function(response) {

            var data = JSON.parse(response);
            var job_inputlist = data.job_inputlist;

            temp = Array.isArray(data.temp) ? data.temp : [];
            tempA = Array.isArray(data.tempA) ? data.tempA : [];
            temp_event = Array.isArray(data.temp_event) ? data.temp_event : [];


            // ✅ 更新快取
            jobTempData[jobid] = {
                temp: temp,
                tempA: tempA,
                temp_event: temp_event
            };


            document.getElementById("input_jobid_select").innerHTML = job_inputlist;
            document.getElementById("JobSelect").style.display = 'none';
            document.getElementById("job_id").value = jobid;
        
            var rows = document.querySelectorAll('#input_jobid_select tr');
            rows.forEach(function(row) {
                row.addEventListener('click', function() { 
                    input_event = this.className; 
                });
            });

            var language = getCookie('language');
                if(language == "zh-cn"){

                    document.getElementById('101') && (document.getElementById('101').textContent = '停用');
                    document.getElementById('102') && (document.getElementById('102').textContent = '致能');
                    document.getElementById('103') && (document.getElementById('103').textContent = '清除');
                    document.getElementById('104') && (document.getElementById('104').textContent = '确认');
                    document.getElementById('105') && (document.getElementById('105').textContent = '启动');
                    document.getElementById('106') && (document.getElementById('106').textContent = '反向');
                    document.getElementById('107') && (document.getElementById('107').textContent = '序列清除');
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
                    document.getElementById('101') && (document.getElementById('101').textContent = '停用');
                    document.getElementById('102') && (document.getElementById('102').textContent = '致能');
                    document.getElementById('103') && (document.getElementById('103').textContent = '清除');
                    document.getElementById('104') && (document.getElementById('104').textContent = '確認');
                    document.getElementById('105') && (document.getElementById('105').textContent = '啟動');
                    document.getElementById('106') && (document.getElementById('106').textContent = '反向');
                    document.getElementById('107') && (document.getElementById('107').textContent = '序列清除');
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
            console.error("AJAX request failed:", status, error);
        }
    }); 
}


function get_input_info(){

    if(job_id){
        $.ajax({
            url: "?url=Inputs/check_job_event_conflict",
            method: "POST",
            data: { 
                job_id: job_id,
                input_event: input_event,
            },
            success: function(response) {
                if (response === 'no_data') {
                    return;
                }

                document.getElementById('edit_input').style.display='block';  


                var responseJSON = JSON.stringify(response);
                var cleanString = responseJSON.replace(/Array|\\n/g, '');
                var cleanString = cleanString.substring(2, cleanString.length - 2);

                var [, jobid] = cleanString.match(/\[JOBID]\s*=>\s*([^ ]+)/) || [, null];
                var [, input_event] = cleanString.match(/\[EvenID]\s*=>\s*([^ ]+)/) || [, null];
                var [, input_pin] = cleanString.match(/\[Pin]\s*=>\s*([^ ]+)/) || [, null];
                var [, input_wave] = cleanString.match(/\[signal]\s*=>\s*([^ ]+)/) || [, null];
                var [, gateconfirm] = cleanString.match(/\[Wp_Ready_Confirm]\s*=>\s*([^ ]+)/) || [, null];

        
                if(input_wave == 1){
                    var wave = "_high";
                }else{
                    var wave = "_low";
                }
                
                var edit_input_pin = "edit_pin" + input_pin + wave;
                var radioButton = document.getElementById(edit_input_pin);
                radioButton.removeAttribute('disabled');
                old_input_event = input_event;
                
                if(radioButton){
                    radioButton.checked = true;
                    if(wave == '_high'){
                        var nstr = "edit_pin" + input_pin + '_low';
                    }else{
                        var nstr = "edit_pin" + input_pin + '_high';
                    }
                    var element = document.getElementById(nstr);
                    if(element){
                        element.disabled = false; 
                    } 
                }

                if(input_event != 109){
                    document.getElementById('edit_work_goc').style.display = 'none';
                }else{

                    document.getElementById('edit_work_goc').style.display = 'block';

                    if(gateconfirm == 1){
                        document.getElementById("edit_gateconfirm_1").checked = true;
                    }

                    if(gateconfirm == 0){
                        document.getElementById("edit_gateconfirm_0").checked = true;
                    }

                }
                
                document.querySelector("select[name='edit_Event_Option']").value = input_event;

                document.getElementById("edit_Event_Option").onchange = function() {
                    var selectedValue = this.value; 
                    edit_handleEventChange(selectedValue,gateconfirm); 
                };

             
            },
            error: function(xhr, status, error) {
                
            }
        });
   
        
    }

}


function resetalignsubmit(job_id) {

    var job_id_new = 0;

    if(job_id_new == 0){
        console.log(job_id_new);
        console.log(job_id);
        $.ajax({
            url: "?url=Inputs/input_alljob_cancel",
            method: "POST",
            data: {
                job_id_new: job_id_new
            },
            success: function (response) {
                get_input_by_job_id(job_id);
            },
            error: function (xhr, status, error) {

            }
        });
    }
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
                    document.getElementById('106') && (document.getElementById('106').textContent = '拆螺丝');
                    document.getElementById('107') && (document.getElementById('107').textContent = '工序清除');
                    document.getElementById('108') && (document.getElementById('108').textContent = '重启');
                    document.getElementById('109') && (document.getElementById('109').textContent = '一次感应');
                    document.getElementById('110') && (document.getElementById('110').textContent = '自定义1');
                    document.getElementById('111') && (document.getElementById('111').textContent = '自定义2');
                    document.getElementById('112') && (document.getElementById('112').textContent = '自定义3');
                    document.getElementById('113') && (document.getElementById('113').textContent = '自定义4');
                    document.getElementById('114') && (document.getElementById('114').textContent = '自定义5');
                
                }else if(language =="zh-tw"){
                    document.getElementById('101') && (document.getElementById('101').textContent = '禁用');
                    document.getElementById('102') && (document.getElementById('102').textContent = 'Enable');
                    document.getElementById('103') && (document.getElementById('103').textContent = '清除顆數');
                    document.getElementById('104') && (document.getElementById('104').textContent = '確認');
                    document.getElementById('105') && (document.getElementById('105').textContent = '啟動');
                    document.getElementById('106') && (document.getElementById('106').textContent = '拆螺絲');
                    document.getElementById('107') && (document.getElementById('107').textContent = '工序清除');
                    document.getElementById('108') && (document.getElementById('108').textContent = '重啟');
                    document.getElementById('109') && (document.getElementById('109').textContent = '一次感應');
                    document.getElementById('110') && (document.getElementById('110').textContent = '自定義1');
                    document.getElementById('111') && (document.getElementById('111').textContent = '自定義2');
                    document.getElementById('112') && (document.getElementById('112').textContent = '自定義3');
                    document.getElementById('113') && (document.getElementById('113').textContent = '自定義4');
                    document.getElementById('114') && (document.getElementById('114').textContent = '自定義5');
                }


            },
            error: function(xhr, status, error) {
            
            }
        }); 
    }
}


function input_success_res(response, job_id, callbackFn, hideElementId = 'newinput') {
    const responseData = JSON.parse(response);
    alertify.alert(responseData.res_type, responseData.res_msg);

    setTimeout(() => {
        alertify.closeAll();
        document.querySelector(".main-content").classList.remove("overlay-active");
        document.getElementById('spinner').style.display = 'none';

        // ✅ 清空 select
        const selectEl = document.getElementById('Event_Option');
        if (selectEl) selectEl.value = '-1';

        // ✅ 清空 radio
        document.querySelectorAll('input[name="pin_option"]').forEach(r => r.checked = false);
        document.querySelectorAll('input[name="gateconfirm"]').forEach(r => r.checked = false);

        // ✅ 先更新 jobTempData，再觸發畫面更新
        get_input_by_job_id(job_id, function () {
            //crud_job_event('new');
        });

        const hideEl = document.getElementById(hideElementId);
        if (hideEl) hideEl.style.display = 'none';

    }, 2000);
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