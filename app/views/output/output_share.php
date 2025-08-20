<script>
var job_id; 
var output_event;
var temp;
var tempA;
var buttonDisabled = false;
var backgroundColorYellow = false;
var output_job;
var all_job;
var del_output_val;
var output_pinval;
var temp_event;
$(document).ready(function () {
    highlight_row_input('output_table');

    var all_output_job = '<?php echo $data['device_data']['device_output_all_job']?>';
    job_id = all_output_job ;
    output_job = all_output_job;
    if(job_id){
        get_output_by_job_id(job_id);
        document.getElementById('Button_Select').disabled = true;
        setJobIdHighlight(true);
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

var modal = document.getElementById('newinput');

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
// 提前宣告供事件處理用（避免多次綁定）
let filteredPins = [];

function handleEventChange(e) {
    const selectedId = e.target.value;
    const isGroupA = ['7', '8', '9'].includes(selectedId);
    const isGroupB = ['12', '13', '14', '15', '16'].includes(selectedId);

    toggleElementsInRange(1, 11, 2, isGroupA);

    if (!isGroupA) {
        disableElements(filteredPins);
    }

    if (isGroupB) {
        // Disable pin1~11
        for (let i = 1; i <= 11; i++) {
            const pin = document.getElementById(`pin${i}_1`);
            if (pin) pin.disabled = true;
        }

        // Enable time1~11
        for (let i = 1; i <= 11; i++) {
            const time = document.getElementById(`time${i}`);
            if (time) time.disabled = false;
        }
    }
}


let unifiedFlag = 0;
function crud_job_event(argument) {
    const table = document.getElementById('output_table');
    const jobSelect = document.getElementById('JobNameSelect');
    const eventOption = document.getElementById('Event_Option');
    const selectedRow = table.querySelector('tr.selected');
    const job_id = jobSelect?.value ?? null;

    if (!job_id) return;

    if (selectedRow) {
        output_event = selectedRow.getAttribute('data-event');
        output_pinval = selectedRow.querySelector('[data-outputpin]')?.getAttribute('data-outputpin') || null;
        del_output_val = output_event;
    }

    const showModal = (id) => document.getElementById(id).style.display = 'block';

    switch (argument) {
        case 'del':
            if (del_output_val) {
                showOverlay();
                delete_output_id(job_id, del_output_val);
            }
        break;

        case 'new':
            output_event = null;
            output_pinval = null;
            del_output_val = null;
            temp = [];
            tempA = [];
            temp_event = [];

            table.querySelectorAll('tr.selected').forEach(row => row.classList.remove('selected'));

            $.ajax({
                url: "?url=Outputs/get_output_by_job_id",
                method: "POST",
                data: { job_id },
                async: false,
                success: function (response) {
                    let data = JSON.parse(response);
                    temp = Array.isArray(data.temp) ? data.temp : [];
                    tempA = Array.isArray(data.tempA) ? data.tempA : [];
                    temp_event = Array.isArray(data.temp_event) ? data.temp_event : [];
                },
                error: function (xhr, status, error) {
                    console.error("取得 job output 設定失敗:", status, error);
                    temp = tempA = temp_event = [];
                }
            });

            if (eventOption) eventOption.selectedIndex = 0;

            //清除 radio 的勾選狀態
            temp.forEach(id => {
                const radio = document.getElementById(id);
                if (radio?.type === 'radio') {
                    radio.checked = false;
                }
            });


            clearAndDisableRadios(temp);
            resetTimeFields(1, 11);

            filteredPins = temp.filter(id => id.includes('pin') && !id.includes('edit_pin'));
            disableElements(filteredPins);

            showOverlay();
            showModal('new_output');

            // ✅ 避免重複綁定 change 事件
            eventOption.removeEventListener('change', handleEventChange);
            eventOption.addEventListener('change', handleEventChange);

            disableOptions('#Event_Option', tempA, false, true);
            disableOptions('#Event_Option', temp_event, true, false);

            // ✅ Event_Option == 12 時，禁用 pin*_1
            if (eventOption?.value === "12") {
                for (let i = 1; i <= 11; i++) {
                    const pinEl = document.getElementById(`pin${i}_1`);
                    if (pinEl) pinEl.disabled = true;
                }
            }
            
            
            // ✅ 更新按鈕狀態
            syncButtonSelectState();
            
            console.log(eventOption.value);
            console.log(unifiedFlag);
        break;

    
        case 'edit': {
                if (!output_event) return;
                showOverlay();

                const selectedEditRows = document.querySelectorAll('#output_jobid_select tr.selected');
                if (!selectedEditRows.length) return;

                if (!output_pinval) return; // 需要先有被選到的 pin (從列表 data-outputpin 來)

                // 先向後端拿資料並渲染 DOM
                get_output_info(job_id, output_event);

                // 等待 DOM 更新完成後再處理互動邏輯
                setTimeout(() => {
                    const currentPinNo = String(output_pinval);
                    const basePin  = `edit_pin${currentPinNo}`;
                    const baseTime = `edit_time${currentPinNo}`;

                    const p0     = document.getElementById(`${basePin}_0`);
                    const p1     = document.getElementById(`${basePin}_1`);
                    const p2     = document.getElementById(`${basePin}_2`);
                    const timeEl = document.getElementById(baseTime);

                    // ---- 由列表 DOM 收集「已有事件」的 pins（有 data-outputpin 的列表示該 pin 已被佔用）
                    const usedPins = Array.from(document.querySelectorAll('#output_jobid_select [data-outputpin]'))
                        .map(el => parseInt(el.getAttribute('data-outputpin'), 10))
                        .filter(n => Number.isFinite(n));

                    // ---- 先把所有 edit_pin* 依規則鎖/開
                    const allPinRadios = document.querySelectorAll('input[type="radio"][id^="edit_pin"]');
                    allPinRadios.forEach(r => {
                        const m = r.id.match(/^edit_pin(\d+)_\d$/);
                        if (!m) return;
                        const pinNum = m[1];
                        if (pinNum === currentPinNo) {
                            // 正在編輯的這一組 → 一律可操作
                            r.disabled = false;
                        } else {
                            // 非當前組：若該 pin 已有事件 → 鎖；若沒有事件 → 放行（可選）
                            const alreadyUsed = usedPins.includes(parseInt(pinNum, 10));
                            r.disabled = alreadyUsed;
                        }
                    });

                    // ---- 所有 edit_timeX：非當前組一律禁用（避免在編輯畫面硬改別組的時間）
                    const allTimeInputs = document.querySelectorAll('input[id^="edit_time"]');
                    allTimeInputs.forEach(t => {
                        const m = t.id.match(/^edit_time(\d+)$/);
                        if (!m) return;
                        const pinNum = m[1];
                        if (pinNum !== currentPinNo) {
                            t.disabled = true;
                            // 不清空，避免資料丟失；想清 UI 可自行加：t.value = '';
                        }
                    });

                    // ---- Helpers：禁用時暫存值、啟用時還原值（避免切換 radio 時把原值弄丟）
                    const saveAndDisable = (el) => {
                        if (!el) return;
                        if (el.value !== '') el.dataset.prev = el.value; // 暫存
                        el.disabled = true;
                    };
                    const enableAndRestore = (el) => {
                        if (!el) return;
                        el.disabled = false;
                        if (el.value === '' && el.dataset.prev != null) el.value = el.dataset.prev; // 還原
                    };

                    // 使用者輸入時更新暫存，避免還原到舊值
                    if (timeEl) {
                        timeEl.addEventListener('input', () => { timeEl.dataset.prev = timeEl.value; });
                    }

                    // ---- 確保當前組三顆 radio + time 先可操作
                    [p0, p1, p2, timeEl].forEach(el => el && (el.disabled = false));

                    // ---- 依勾選狀態，同步時間欄位啟用與值保存/還原
                    const sync = () => {
                        if (p0?.checked) {
                            // _0：time 禁用（保存值）
                            saveAndDisable(timeEl);
                            if (p1) p1.disabled = false;
                            if (p2) p2.disabled = false;
                        } else if (p1?.checked) {
                            // _1：time 啟用（還原值）
                            enableAndRestore(timeEl);
                            if (p0) p0.disabled = false;
                            if (p2) p2.disabled = false;
                        } else if (p2?.checked) {
                            // _2：time 禁用（保存值）
                            saveAndDisable(timeEl);
                            if (p0) p0.disabled = false;
                            if (p1) p1.disabled = false;
                        } else {
                            // 沒選：預設禁用 time（保存值），三顆可選
                            saveAndDisable(timeEl);
                            if (p0) p0.disabled = false;
                            if (p1) p1.disabled = false;
                            if (p2) p2.disabled = false;
                        }
                    };

                    // ---- 綁定三顆 radio 的切換事件
                    [p0, p1, p2].forEach(el => el && el.addEventListener('change', sync));

                    // ---- 進場先同步一次（若一開始是 _1 且 time 有值，會保留下來）
                    sync();

                }, 100);

                break;
            }
        case 'copy':

            
            const messages = {
                'en-us': "No event available to copy",
                'zh-tw': "沒有可複製的事件",
                'zh-cn': "没有可复制的事件"
            };

            // 假設語言來源 (可改成你系統裡的語言判斷方式)
            var language = getCookie('language');
            const msg = messages[language] || messages['en-us'];


            if (!output_event) {
                 if (typeof alertify !== 'undefined') {
                    alertify.alert(msg, function() {
                        // callback：使用者按下 OK 時
                    });

                    // 3 秒後自動關閉 alert 視窗
                    setTimeout(function() {
                        alertify.closeAll(); 
                    }, 3000);
                }

                document.querySelector(".main-content")?.classList.remove("overlay-active");
                if (typeof hideOverlay === 'function') hideOverlay();

                return;

            }
            showOverlay();

            const jobinfo = <?php echo json_encode($data['job_list_new']); ?>;
            document.getElementById("from_job_id").value = job_id;
            document.getElementById("from_job_name").value = jobinfo[job_id]?.JOBname ?? "";

            const jobSelect1 = document.getElementById('JobSelect1');
            Array.from(jobSelect1.options).forEach(opt => {
                if (opt.value === job_id) {
                    opt.disabled = true;
                    opt.classList.add('disabled_input');
                }
            });

            const selectedRows = document.querySelectorAll('#output_jobid_select tr.selected');
            showModal('copy_output');
          
        break;

        case 'unified':
            if (unifiedFlag === 0) {
                // ✅ 第一次點擊 → 設成 1
                unifiedFlag = 1;
                enableButton();
                resetBackgroundColor();
                alignsubmit(job_id);
            } else {
                // ✅ 第二次點擊 → 設回 0
                unifiedFlag = 0;
                resetalignsubmit(job_id);
            }

            console.log(unifiedFlag);

            // ✅ 每次點擊後都更新按鈕狀態
            syncButtonSelectState();
        break;

        default:
            console.warn(`Unknown action: ${argument}`);
        break;
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


function toggleElementsInRange(start, end, suffix, disable) {
    for (var i = start; i <= end; i++) {
    
        for (var j = 0; j <= 1; j++) { 
            var id = 'pin' + i + '_' + j;
            var element = document.getElementById(id);
            if (element) {
                element.disabled = disable;
            }
        }

        var timeId = 'time' + i;
        //console.log(timeId);
        var timeElement = document.getElementById(timeId);
        if (timeElement) {
            timeElement.disabled = disable;
        }
    }
}



var old_output_event; 
var output_event;
function job_confirm(){
    var jobid = document.getElementById("JobNameSelect").value;
    localStorage.setItem("jobid", jobid);
    job_id = jobid;
    all_job = jobid;

    if(jobid){
        
        // 清空表格
        document.getElementById("output_jobid_select").innerHTML = "";

        // 重置 output_event / old_output_event
        output_event = null;
        old_output_event = null;

        // 清空所有 radio 的勾選
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.checked = false;
            radio.disabled = false; // 同時解除 disable 狀態 (如果需要)
        });

        // 重置時間欄位 (如有)
        if (typeof resetTimeFields === 'function') {
            resetTimeFields(1, 11);
        }

        $.ajax({
            url: "?url=Outputs/get_output_by_job_id",
            method: "POST",
            data:{ 
                job_id: job_id,
            },
            success: function(response) {
                var data = JSON.parse(response);
                var job_outputlist = data.job_outputlist;

                temp = Array.isArray(data.temp) ? data.temp : [];
                tempA = Array.isArray(data.tempA) ? data.tempA : [];


                document.getElementById("output_jobid_select").innerHTML = job_outputlist;
                document.getElementById("JobSelect").style.display = 'none';
                document.getElementById("job_id").value = job_id;
            
                var rows = document.querySelectorAll('#output_jobid_select tr');
                rows.forEach(function(row) {
                    row.addEventListener('click', function() { 

                        row.getAttribute('data-event');
                        output_event = row.getAttribute('data-event');
                   
 
                    });
                });

                var language = getCookie('language');
                if(language == "zh-cn"){
                    document.getElementById('1') && (document.getElementById('1').textContent = 'OK');
                    document.getElementById('2') && (document.getElementById('2').textContent = 'NG');
                    document.getElementById('3') && (document.getElementById('3').textContent = '超出上限');
                    document.getElementById('4') && (document.getElementById('4').textContent = '低于下限');
                    document.getElementById('5') && (document.getElementById('5').textContent = '工序完成信号');
                    document.getElementById('6') && (document.getElementById('6').textContent = '工作任务完成信号');
                    document.getElementById('7') && (document.getElementById('7').textContent = '马达信号');
                    document.getElementById('8') && (document.getElementById('8').textContent = '启动信号');
                    document.getElementById('9') && (document.getElementById('9').textContent = '拆螺丝');
                    document.getElementById('10') && (document.getElementById('10').textContent = '条码');
                    document.getElementById('11') && (document.getElementById('11').textContent = 'BS');
                    document.getElementById('12') && (document.getElementById('12').textContent = '自定义1');
                    document.getElementById('13') && (document.getElementById('13').textContent = '自定义2');
                    document.getElementById('14') && (document.getElementById('14').textContent = '自定义3');
                    document.getElementById('15') && (document.getElementById('15').textContent = '自定义4');
                    document.getElementById('16') && (document.getElementById('16').textContent = '自定义5');

                } 
                else if(language == "zh-tw"){
                    document.getElementById('1') && (document.getElementById('1').textContent = 'OK');
                    document.getElementById('2') && (document.getElementById('2').textContent = 'NG');
                    document.getElementById('3') && (document.getElementById('3').textContent = '超出上限');
                    document.getElementById('4') && (document.getElementById('4').textContent = '低於下限');
                    document.getElementById('5') && (document.getElementById('5').textContent = '工序完成信號');
                    document.getElementById('6') && (document.getElementById('6').textContent = '完工信號');
                    document.getElementById('7') && (document.getElementById('7').textContent = '馬達信號');
                    document.getElementById('8') && (document.getElementById('8').textContent = '啟動信號');
                    document.getElementById('9') && (document.getElementById('9').textContent = '拆螺絲');
                    document.getElementById('10') && (document.getElementById('10').textContent = '條碼');
                    document.getElementById('11') && (document.getElementById('11').textContent = 'BS');
                    document.getElementById('12') && (document.getElementById('12').textContent = '自定義1');
                    document.getElementById('13') && (document.getElementById('13').textContent = '自定義2');
                    document.getElementById('14') && (document.getElementById('14').textContent = '自定義3');
                    document.getElementById('15') && (document.getElementById('15').textContent = '自定義4');
                    document.getElementById('16') && (document.getElementById('16').textContent = '自定義5');
                }

            },
            error: function(xhr, status, error) {
            
            }
        });
    }
}

//delete
function delete_output_id(job_id,del_output_val){

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
                    url: "?url=Outputs/delete_output",
                   method: "POST",
                   data: { 
                       job_id: job_id,
                       output_event: del_output_val,
                   },
                   success: function(response) {
                        input_success_res(response, job_id, get_output_by_job_id, 'edit_input');
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


function input_success_res(response, job_id, callbackFn, hideElementId = 'newinput') {
    var responseData = JSON.parse(response);
    alertify.alert(responseData.res_type, responseData.res_msg);

    setTimeout(function () {
        alertify.closeAll();
        document.querySelector(".main-content").classList.remove("overlay-active");
        document.getElementById('spinner').style.display = 'none';

        if (typeof callbackFn === 'function') {
            callbackFn(job_id);
        }
    }, 2000);

    const hideEl = document.getElementById(hideElementId);
    if (hideEl) hideEl.style.display = 'none';
}


function setJobIdHighlight(active) {
  const el = document.getElementById('job_id');
  if (!el) return;
  el.classList.toggle('bg-yellow', !!active);
  // 永遠清掉 inline，避免和 class 打架
  el.style.backgroundColor = '';
}

// 放在檔案頂部（全域一次）
// 用來避免多次連續 AJAX 時，舊回應覆蓋新狀態
let _getOutputReqId = 0;

function get_output_by_job_id(job_id) {
  const reqId = ++_getOutputReqId;

  // 進函式就先清掉黃底（避免等待期間閃黃）
  setJobIdHighlight(false);

  const jobIdEl = document.getElementById("job_id");
  if (jobIdEl) jobIdEl.value = job_id || '';

  $.ajax({
    url: "?url=Outputs/get_output_by_job_id",
    method: "POST",
    data: { job_id: job_id },
    dataType: "json",
    success: function (data) {
      if (reqId !== _getOutputReqId) return;

      const job_outputlist = data?.job_outputlist ?? '';
      let language = (getCookie('language') || data?.language || data?.languange || 'en-us').toLowerCase();
      if (language === 'en') language = 'en-us';
      if (!['en-us', 'zh-tw', 'zh-cn'].includes(language)) language = 'en-us';

      const temp  = Array.isArray(data?.temp)  ? data.temp  : [];
      const tempA = Array.isArray(data?.tempA) ? data.tempA : [];

      // 渲染表格
      const listEl = document.getElementById("output_jobid_select");
      if (listEl) listEl.innerHTML = job_outputlist;
      const jobSelectWrap = document.getElementById("JobSelect");
      if (jobSelectWrap) jobSelectWrap.style.display = 'none';

      // 綁列點擊事件
      if (listEl) {
        listEl.querySelectorAll('tr').forEach(function (row) {
          row.addEventListener('click', function () {
            window.output_event = this.className;
          });
        });
      }

      // 依 unifiedFlag 及資料量控制黃底
      const listEmpty = (job_outputlist.trim() === '');
      const noTemp    = (temp.length === 0);
      const noTempA   = (tempA.length === 0);
      const hasAnyData = !(listEmpty && noTemp && noTempA);

      if (jobIdEl) {
        jobIdEl.classList.remove('bg-yellow');       // 先清掉
        jobIdEl.style.backgroundColor = '';          // 清 inline
        if (unifiedFlag !== 0 && hasAnyData) {
          jobIdEl.classList.add('bg-yellow');        // 只有 unified 模式 + 有資料 才黃
        }
        if (!hasAnyData) {
          jobIdEl.value = '';                        // 沒資料時清空顯示
        }
      }

      // 依 unifiedFlag 決定 Button_Select 是否可用
      const btn = document.getElementById('Button_Select');
      if (btn) {
        const enable = (unifiedFlag === 0);
        btn.disabled = !enable;
        btn.classList.toggle('disabled', !enable);
        btn.classList.toggle('disabled_input', !enable);
        btn.setAttribute('aria-disabled', String(!enable));
      }

      // ===== 語系文字（1~16）=====
      const labels = {
        'en-us': {
          1:'OK',2:'NG',3:'NG -High',4:'NG - Low',
          5:'OK - Sequence',6:'OK - Job ',7:'Tool Running',8:'Tool Trigger',
          9:'Reverse',10:'BS',11:'Barcode',12:'UserDefine1',13:'UserDefine2',14:'UserDefine3',15:'UserDefine4',16:'UserDefine5'
        },
        'zh-tw': {
          1:'OK',2:'NG',3:'超出上限',4:'低於下限',
          5:'工序完成信號',6:'工作完成信號',7:'馬達信號',8:'啟動信號',
          9:'拆螺絲',10:'條碼停止',11:'條碼',12:'自定義1',13:'自定義2',14:'自定義3',15:'自定義4',16:'自定義5'
        },
        'zh-cn': {
          1:'OK',2:'NG',3:'超出上限',4:'低于下限',
          5:'工序完成信号',6:'工作完成信号',7:'马达信号',8:'启动信号',
          9:'拆螺丝',10:'条码停止',11:'条码',12:'自定义1',13:'自定义2',14:'自定义3',15:'自定义4',16:'自定义5'
        }
      };
      const L = labels[language] || labels['en-us'];
      for (let i = 1; i <= 16; i++) {
        const el = document.getElementById(String(i));
        if (el && L[i]) el.textContent = L[i];
      }
      // ===== 語系文字結束 =====

      // 最後再同步一次（若外面還有其它 UI 規則）
      if (typeof syncButtonSelectState === 'function') {
        syncButtonSelectState();
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX request failed:", status, error, xhr?.responseText);
    }
  });
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


function create_output_id() {

    var output_event = document.getElementById("Event_Option").value;
    var pinval = collectPinValues('input[name="pin_option"]');

    if (pinval.length > 0) {
        var pin_old = pinval[0]['id']; 
        var wave = pinval[0]['value'];

        
        var match = pin_old.match(/\d+/); 
        var output_pin = match ? parseInt(match[0]) : null;
        
        var time_ms = 'time' + output_pin;
        var wave_on = document.getElementById(time_ms).value;

        //  加進來的檢查邏輯
        const skipEvents = [7,8,9,10,11,12,13,14,15,16];

        var language = getCookie('language');

        var messages = {
            'en-us': "Please enter a wave value between 100 and 10000.",
            'zh-tw': "範圍介於100和10000之間。",
            'zh-cn': "范围介于100和10000之间。"
        };

        if (!language) {
            language = 'en-us';
        }

        if (wave == 1 && !skipEvents.includes(Number(output_event))) {
            if (wave_on < 100 || wave_on > 10000) {
                alertify.alert(messages[language]);
                setTimeout(function () {
                    alertify.closeAll();
                }, 3000);
                return false; // 🔁 更語意化：中止且表示驗證失敗
            }
        }

        if (job_id) {
            document.getElementById('spinner').style.display = 'block';

            $.ajax({
                url: "?url=Outputs/create_output_event",
                method: "POST",
                data: { 
                    job_id: job_id,
                    output_pin: output_pin,
                    output_event: output_event,
                    wave: wave,
                    wave_on: wave_on
                },
                success: function(response) {
                    output_success_res(response, job_id, get_output_by_job_id, 'new_output');
                    hideOverlay();
                    resetBackgroundColor();
                },
                error: function(xhr, status, error) {
                    console.error("AJAX request failed:", status, error);
                }
            });
        }
    } else {
        console.error("No pinval found or pinval[0] is undefined.");
    }
}


function edit_output_id(){
    var output_event = document.getElementById("edit_event_option").value;
    var pinval       = collectPinValues('input[name="edit_pin_option"]');
    var pin_old      = pinval[0]['id'];
    var wave         = pinval[0]['value'];
    var match        = pin_old.match(/\d+/); 
    var output_pin   = match ? parseInt(match[0]) : null;

    var time_ms = 'edit_time'+ output_pin;
    var wave_on =  document.getElementById(time_ms).value;
    if(job_id){

        document.getElementById('spinner').style.display = 'block';

        $.ajax({
            url: "?url=Outputs/edit_output_event",
            method: "POST",
            data: { 
                job_id: job_id,
                output_pin: output_pin,
                output_event: output_event,
                wave: wave,
                wave_on: wave_on,
                old_output_event: old_output_event
            },
            success: function(response) {
                output_success_res(response, job_id, get_output_by_job_id, 'edit_output');
                hideOverlay();

            },
            error: function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
            }
        });         
    }
}
function resetalignsubmit(job_id) {
    unifiedFlag = 0;   // ✅ 執行 resetalignsubmit 時設回 0

    var job_id_new = 0;
    if(job_id_new == 0){
        $.ajax({
            url: "?url=Outputs/output_alljob",
            method: "POST",
            data: {
                job_id_new: job_id_new
            },
            success: function (response) {
                get_output_by_job_id(job_id);
            },
            error: function (xhr, status, error) {

            }
        });

    }

}
function alignsubmit(job_id) {
    if (job_id) {
        $.ajax({
            url: "?url=Outputs/output_alljob",
            method: "POST",
            data: {
                job_id: job_id
            },
            success: function (response) {
                get_output_by_job_id(job_id);
            
                buttonDisabled = !buttonDisabled;
                document.getElementById('Button_Select').disabled = buttonDisabled;
     
                backgroundColorYellow = !backgroundColorYellow;
                if (backgroundColorYellow) {
                    setJobIdHighlight(true);
                } else {
                    document.getElementById('job_id').style.backgroundColor = '';
                }
            },
            error: function (xhr, status, error) {

            }
        });
    }
}
//copy
function copy_output_id(){

    var language = getCookie('language');
    if(language == "zh-cn"){
        var text_info ='若设定已存在，将会取代原有设定';
    }else if(language == "zh-tw"){
        var text_info ='若設定已存在，將會取代原有設定';
    }else{
        var text_info ='If the job input already exists, it will replace the original setting';
    }
    alertify.confirm( text_info, function (e) {
        if (e) {
            var to_job_id = document.getElementById("JobSelect1").value;
            if(to_job_id){
                document.getElementById('spinner').style.display = 'block';
                $.ajax({
                    url: "?url=Outputs/copy_output",
                    method: "POST",
                    data: { 
                        from_job_id: job_id,
                        to_job_id: to_job_id
                    },
                    success: function(response) {
                        output_success_res(response, job_id, get_output_by_job_id, 'copy_output');
                        hideOverlay();

                    },
                    error: function(xhr, status, error) {
                        
                    }
                });
        
            } 
        } else {
            // cancel
            hideOverlay();
        }
    });
    document.getElementById('copy_output').style.display='none';
}

function updateInputsBasedOnRadioSelection() {
   
    for (let i = 1; i <= 11; i++) {
        let radioId = 'pin' + i + '_3';
        let inputId = 'time' + i;
        
        let radioElement = document.getElementById(radioId);
        let inputElement = document.getElementById(inputId);

        if (radioElement && inputElement) {
            inputElement.disabled = !radioElement.checked;
        }
    }
}

function get_output_info(job_id, output_event) {
    if (!job_id || !output_event) return;

    $.ajax({
        url: "?url=Outputs/check_job_event",
        method: "POST",
        data: { 
            job_id: job_id,
            output_event: output_event
        },
        success: function(response) {
            if (response === 'no_data' || !response) {
                getLanguageMessage('language');
                return;
            }
            
            for (let i = 1; i <= 11; i++) {
                for (let j = 0; j <= 2; j++) {
                    const radio = document.getElementById(`edit_pin${i}_${j}`);
                    if (radio) {
                        radio.checked = false;
                        radio.disabled = true;
                    }
                }

                const timeInput = document.getElementById(`edit_time${i}`);
                if (timeInput) {
                    timeInput.value = '';
                    timeInput.disabled = true;
                }
            }


            // ✅ 顯示編輯視窗
            document.getElementById('edit_output').style.display = 'block';

            // ✅ 確保是 JSON，這段基於後端已 json_encode 回傳
            const output_pin = response.Pin;
            const signal = response.signal;
            const wave = response.EvenID;
            const wave_on = response.durate;

            // ✅ 設定事件選單
            const eventOption = document.getElementById("edit_event_option");
            if (eventOption) eventOption.value = wave;

            // ✅ 設定對應 radio
            const radioId = `edit_pin${output_pin}_${signal}`;
            const radio = document.getElementById(radioId);
            if (radio) {
                radio.disabled = false;
                radio.checked = true;
            }

            // ✅ 設定時間欄位
            const timeInput = document.getElementById(`edit_time${output_pin}`);
            if (timeInput) {
                timeInput.disabled = (signal == 2); // signal=2 禁用時間欄位
                timeInput.value = (wave_on === '' || wave_on === '0') ? '' : wave_on;
            }

            // ✅ 完工/馬達/啟動信號 → 禁用 pin0, pin1
            if (wave == 6 || wave == 7 || wave == 8) {
                for (let i = 1; i <= 11; i++) {
                    const pin0 = document.getElementById(`edit_pin${i}_0`);
                    const pin1 = document.getElementById(`edit_pin${i}_1`);
                    if (pin0) pin0.disabled = true;
                    if (pin1) pin1.disabled = true;
                }

                // ✅ 同時 disable 對應 pinX_3（trigger）
                if (Array.isArray(temp)) {
                    const triggerList = temp
                        .filter(id => id.includes("edit_pin"))
                        .map(id => id.slice(0, -1) + '3');
                    triggerList.forEach(id => {
                        const trig = document.getElementById(id);
                        if (trig?.type === 'radio') trig.disabled = true;
                    });
                }
            }

            old_output_even = output_event;
        },
        error: function(xhr, status, error) {
            console.error("AJAX request failed:", status, error);
        }
    });
}




function toggleOnputTime(inputId, checked, option) {
    var inputElement = document.getElementById(inputId);
    if (!inputElement) {
        return; 
    }

    if (inputElement.type === 'checkbox' || inputElement.type === 'radio') {

        if (inputElement.checked !== checked) {
           
        }
    }
    
 
    if(option == 1 || option == 3){
        var newId = inputId.replace(/^pin(\d+)_\d+$/, 'time$1');
        
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = true;
        }
    }else{
        var newId = inputId.replace(/^pin(\d+)_\d+$/, 'time$1');
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = false;
        }
    }    
}





function toggleOnputTime_edit(inputId, checked, option) {
    var inputElement = document.getElementById(inputId);
    
    if (!inputElement) {
        return; 
    }

   
    if (inputElement.type === 'checkbox' || inputElement.type === 'radio') {

        if (inputElement.checked !== checked) {
           
        }
    }

    if(option == 1 || option == 3){
        var newId = inputId.replace(/^edit_pin(\d+)_\d+$/, 'edit_time$1');
        
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = true;
        }
    }else{
        var newId = inputId.replace(/^edit_pin(\d+)_\d+$/, 'edit_time$1');
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = false;
        }
    }    
}


function disableElements(filtered_array) {
    // 生成新的 id 数组，去除末尾的数字并添加 "_0", "_1", "_2" 和 "time1" 到 "time11"
    let new_array = filtered_array
        .map(item => item.replace(/_\d$/, ''))  // 去除原始字符串末尾的数字
        .flatMap(item => {
            let result = [
                item + "_0",
                item + "_1",
                item + "_2"
            ];

            // 新增 "time" + 1 到 11
            for (let i = 1; i <= 11; i++) {
                result.push("time" + i);
            }

            return result;
        });

    // 遍历新生成的 id 数组，如果元素存在就禁用它
    new_array.forEach(id => {
        let element = document.getElementById(id); 
        if (element) {
            element.disabled = true;  // 禁用该元素
        }
    });
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


function showOverlay() {
    document.getElementById("modal-overlay").style.display = "block";
}

function hideOverlay() {
    document.getElementById("modal-overlay").style.display = "none";
}

function output_success_res(response, job_id, callbackFn, hideElementId = 'newinput') {
    var responseData = JSON.parse(response);
    alertify.alert(responseData.res_type, responseData.res_msg);

    setTimeout(function () {
        alertify.closeAll();
        document.querySelector(".main-content").classList.remove("overlay-active");
        document.getElementById('spinner').style.display = 'none';

        if (typeof callbackFn === 'function') {
            callbackFn(job_id);
        }
    }, 2000);

    const hideEl = document.getElementById(hideElementId);
    if (hideEl) hideEl.style.display = 'none';
}


function clearAndDisableRadios(ids = []) {
    ids.forEach(id => {
        const radio = document.getElementById(id);
        if (radio?.type === 'radio') {
            radio.checked = false;
            radio.disabled = true;
        }
    });
}

function resetTimeFields(start, end) {
    for (let i = start; i <= end; i++) {
        const el = document.getElementById(`time${i}`);
        if (el) {
            el.value = '';
            el.disabled = false;
        }
    }
}

function disableAllPinsAndTimes(count) {
    for (let i = 1; i <= count; i++) {
        const pin = document.getElementById(`pin${i}_1`);
        const time = document.getElementById(`time${i}`);
        if (pin) pin.disabled = true;
        if (time) time.disabled = true;
    }
}

function disableOptions(selector, values = [], gray = false, reset = false) {

    if (!Array.isArray(values)) {
        console.warn("disableOptions: 'values' 不是陣列，自動轉換");
        values = [values];
    }

    const valueSet = values.map(String);

    const options = document.querySelectorAll(`${selector} option`);
    if (!options.length) return;

    options.forEach(opt => {
        if (reset) {
            opt.disabled = false;
            opt.style.color = '';
            opt.classList.remove('disabled_input');
        }

        if (valueSet.includes(opt.value)) {
            opt.disabled = true;
            opt.classList.add('disabled_input');
            if (gray) opt.style.color = 'gray';
        }
    });
}


// ✅ 抽成共用復原函式
function restoreUnifiedState(options = {}) {
    const {
        // 允許自訂 selector 以符合你的頁面
        buttonsSelector = '.unified-btn, .apply-btn',   // 被 enableButton() 放開的按鈕
        rowsSelector    = '#output_jobid_select tr, #output_table tr', // 被 resetBackgroundColor() 影響的列
        highlightClasses = ['highlight', 'selected'],   // 需要移除的樣式 class
    } = options;

    // 1) 還原按鈕狀態（若專案有 disableButton()，優先用）
    if (typeof disableButton === 'function') {
        disableButton();
    } else {
        document.querySelectorAll(buttonsSelector).forEach(btn => {
            btn.disabled = true;
        });
    }

    // 2) 還原背景/選取（若專案有 restoreBackgroundColor()，優先用）
    if (typeof restoreBackgroundColor === 'function') {
        restoreBackgroundColor();
    } else {
        document.querySelectorAll(rowsSelector).forEach(tr => {
            // 清除可能的 inline 樣式
            tr.style.backgroundColor = '';
            // 清除可能加上的 class
            highlightClasses.forEach(cls => tr.classList.remove(cls));
        });
    }

    // 3) 關閉 overlay（若有）
    if (typeof hideOverlay === 'function') {
        hideOverlay();
    }
}


function syncButtonSelectState() {
    const btnSelect = document.getElementById('Button_Select');
    if (btnSelect) {
        if (unifiedFlag === 0) {
            btnSelect.disabled = false;  // 可點
        } else {
            btnSelect.disabled = true;   // 鎖住
        }
    }

    // ✅ unifiedFlag 為 0 時，檢查 job_id 是否黃底 → 清除
    if (unifiedFlag === 0) {
        const jobIdEl = document.getElementById('job_id');
        if (jobIdEl) {
            const hasYellowClass   = jobIdEl.classList?.contains('bg-yellow');
            const inlineIsYellow   = (jobIdEl.style.backgroundColor || '').toLowerCase() === 'yellow';
            const computedIsYellow = window.getComputedStyle(jobIdEl).backgroundColor === 'rgb(255, 255, 0)';

            if (hasYellowClass || inlineIsYellow || computedIsYellow) {
                jobIdEl.classList.remove('bg-yellow');
                jobIdEl.style.backgroundColor = ''; // 清除 inline 設定
            }
        }
    }
}

</script>

<style>
    #output_table td,
    #output_table th {
        width: 100px; 
        padding: 10px;
    }
</style>