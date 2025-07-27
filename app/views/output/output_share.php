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

var modal = document.getElementById('newinput');

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

function crud_job_event(argument) {
    const table = document.getElementById('output_table');
    const selectedRow = table.querySelector('tr.selected');

    if (selectedRow) {
        output_event = selectedRow.getAttribute('data-event');
        output_pinval = selectedRow.querySelector('[data-outputpin]')?.getAttribute('data-outputpin') || null;
        del_output_val = output_event;
    }

    if (!job_id) return;

    const showModal = (id) => document.getElementById(id).style.display = 'block';

    switch (argument) {
        case 'del':
            if (del_output_val) {
                showOverlay();
                delete_output_id(job_id, del_output_val);
            }
            break;

        case 'new':
            // 1. 清空全域變數
            output_event = null;
            output_pinval = null;
            del_output_val = null;

            // 2. 移除選取列
            table.querySelectorAll('tr.selected').forEach(row => row.classList.remove('selected'));

            // ✅ 3. AJAX 載入最新的 temp、tempA、temp_event
            $.ajax({
                url: "?url=Outputs/get_output_by_job_id",
                method: "POST",
                data: { job_id },
                async: false,  // 同步保證變數更新
                success: function (response) {
                    let data = JSON.parse(response);
                    temp        = Array.isArray(data.temp)        ? data.temp        : [];
                    tempA       = Array.isArray(data.tempA)       ? data.tempA       : [];
                    temp_event  = Array.isArray(data.temp_event)  ? data.temp_event  : [];
                },
                error: function (xhr, status, error) {
                    console.error("取得 job output 設定失敗:", status, error);
                    temp = tempA = temp_event = []; // fallback 避免後續錯誤
                }
            });

            // 4. 重置事件選單
            const eventOption = document.getElementById('Event_Option');
            if (eventOption) eventOption.selectedIndex = 0;

            // 5. 清除並禁用所有 radio
            clearAndDisableRadios(temp);

            // 6. 清空並啟用 time 欄位
            resetTimeFields(1, 11);

            // 7. 禁用非 edit 的 pin
            const filteredPins = temp.filter(id => id.includes('pin') && !id.includes('edit_pin'));
            disableElements(filteredPins);

            // 8. 顯示表單與遮罩
            showOverlay();
            showModal('new_output');

            // 9. 綁定下拉變更邏輯
            eventOption.addEventListener('change', () => {
                const selectedOptionId = eventOption.value;
                const groupA = ['7', '8', '9'];
                const groupB = ['12', '13', '14', '15', '16'];
                const isSpecial = groupA.includes(selectedOptionId);
                const isGroupB = groupB.includes(selectedOptionId);

                toggleElementsInRange(1, 11, 2, isSpecial);
                if (!isSpecial) disableElements(filteredPins);

                if (isGroupB) disableAllPinsAndTimes(1, 11);
            });

            // 10. 禁用 tempA 對應事件選項
            disableOptions('#Event_Option', tempA, false, true);
            disableOptions('#Event_Option', temp_event, true, false);

        break;






        case 'edit':
            if (!output_event) return;
            showOverlay();
            const selectedEditRows = document.querySelectorAll('#output_jobid_select tr.selected');
            if (!selectedEditRows.length) {
                //getLanguageMessage('language');
                return;
            }

            // 禁用所有 pin radio
            if (Array.isArray(temp)) {
                temp.forEach(id => {
                    const radio = document.getElementById(id);
                    if (radio?.type === 'radio') radio.disabled = true;
                });

                // 處理 edit_pin 組
                temp.filter(id => id.includes("edit_pin")).forEach(id => {
                    const match = id.match(/(edit_pin\d+)_(\d+)/);
                    if (match) {
                        const basePinId = match[1];
                        for (let i = 0; i <= 2; i++) {
                            const pinId = `${basePinId}_${i}`;
                            const pin = document.getElementById(pinId);
                            if (pin?.type === 'radio') pin.disabled = true;
                        }

                        const timeId = `edit_time${basePinId.replace('edit_pin', '')}`;
                        const timeElement = document.getElementById(timeId);
                        if (timeElement) timeElement.disabled = true;
                    }
                });
            }

            // 啟用該事件的指定 pin 控制
            if (output_pinval) {
                ['0', '1', '2'].forEach(suffix => {
                    const el = document.getElementById(`edit_pin${output_pinval}_${suffix}`);
                    if (el) el.disabled = false;
                });

                const timeEl = document.getElementById(`edit_time${output_pinval}`);
                if (timeEl) timeEl.disabled = false;
            }

            
            get_output_info(job_id, output_event);
            break;

        case 'copy':
            if (!output_event) return;
            showOverlay();
            const jobinfo = <?php echo json_encode($data['job_list_new']); ?>;
            document.getElementById("from_job_id").value = job_id;
            document.getElementById("from_job_name").value = jobinfo[job_id]['JOBname'];

            const jobSelect = document.getElementById('JobSelect1');
            Array.from(jobSelect.options).forEach(opt => {
                if (opt.value === job_id) {
                    opt.disabled = true;
                    opt.classList.add('disabled_input');
                }
            });

            const selectedRows = document.querySelectorAll('#output_jobid_select tr.selected');
            if (selectedRows.length > 0) {
                showModal('copy_output');
            } else {
                getLanguageMessage('language');
            }
            break;

        case 'unified':
            enableButton();
            resetBackgroundColor();
            if (output_job !== job_id) {
                alignsubmit(job_id);
            } else {
                resetalignsubmit(job_id);
            }
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
        console.log(timeId);
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


function get_output_by_job_id(job_id){
    $.ajax({
        url: "?url=Outputs/get_output_by_job_id",
        method: "POST",
        data: { 
            job_id: job_id,
        },
        success: function(response) {
            var data = JSON.parse(response);
            var job_outputlist = data.job_outputlist;
            temp = data.temp;
            tempA = data.tempA;

            document.getElementById("output_jobid_select").innerHTML = job_outputlist;
            document.getElementById("JobSelect").style.display = 'none';
            document.getElementById("job_id").value = job_id;
        
            var rows = document.querySelectorAll('#output_jobid_select tr');
            rows.forEach(function(row) {
                row.addEventListener('click', function() { 
                    output_event = this.className; 
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
            console.error("AJAX request failed:", status, error);
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
                    document.getElementById('job_id').style.backgroundColor = 'yellow';
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

function get_output_info(job_id,output_event){
    
    if(job_id && output_event){
     $.ajax({
             url: "?url=Outputs/check_job_event",
             method: "POST",
             data: { 
                 job_id: job_id,
                 output_event: output_event
             },
             success: function(response) {
                if (response === 'no_data') {
                    getLanguageMessage('language');
                    return;
                }

                document.getElementById('edit_output').style.display = 'block';


                var responseJSON = JSON.stringify(response);

                console.log(responseJSON);
                
                var cleanString = responseJSON.replace(/Array|\\n/g, '');
                var cleanString = cleanString.substring(2, cleanString.length - 2);
                var [, job_id] = cleanString.match(/\[JOBID]\s*=>\s*([^ ]+)/) || [, ''];
                var [, output_event] = cleanString.match(/\[EvenID]\s*=>\s*([^ ]+)/) || [, ''];
                var [, output_pin] = cleanString.match(/\[Pin]\s*=>\s*([^ ]+)/) || [, ''];
                var [, wave] = cleanString.match(/\[signal]\s*=>\s*([^ ]+)/) || [, 0];
                var [, wave_on] = cleanString.match(/\[durate]\s*=>\s*([^ ]+)/) || [, 0];


                var edit_output_pin = "edit_pin" + output_pin + "_"+ wave;
                var radioButton = document.getElementById(edit_output_pin);

                if (radioButton) {
                    radioButton.removeAttribute('disabled');  
                } else {
                    console.warn('Radio button not found:', edit_output_pin); 
                }

                var time_ms = 'edit_time'+ output_pin;
                if(wave != 2){
                    var time_id = 'edit_time' + output_pin;
                    var element = document.getElementById(time_id);
                    
                    if(element){
                        element.disabled = true
                    }
                }
                    
                //完工信號 && 馬達信號 && 啟動信號
                if (output_event == 8  || output_event == 6 || output_event == 7 ) {
                  
                    for(let i = 1; i <= 11; i++) {
                        let element1 = document.getElementById(`edit_pin${i}_0`);
                        if (element1) {
                            element1.disabled = true;
                        }
                
                        let element2 = document.getElementById(`edit_pin${i}_1`);
                        if (element2) {
                            element2.disabled = true;
                        }
                    }

                    if (Array.isArray(temp)) {
                        //過濾出包含 "edit_pin" 的字串
                        const filteredArray = temp.filter(item => item.includes("edit_pin"));
                        const updatedArray = filteredArray.map(item => {
                            // 如果字串為空，直接返回
                            if (item.length === 0) {
                                return item;
                            }
                            //強制字串的最後一個字元更換為 '3'
                            return item.slice(0, -1) + '3';
                        });
                        
                        updatedArray.forEach(item => {
                            const radio = document.getElementById(item);
                            if (radio && radio.type === 'radio') {
                                radio.disabled = true;
                            }
                        });

                    }
                    
                }

                let result = edit_output_pin.replace(/^edit_pin/, "");
                result = result.replace(/(_[0-9]{1,2})$/, ""); 

                //檢查id = new_variable是否存在,存在做disabled
                var new_variable = 'edit_time'+ result;
                var element = document.getElementById(new_variable);
                if (element) {
                    element.disabled = true;  
                }
          
                document.getElementById(time_ms).value = (wave_on === 0) ? '' : wave_on;
                old_output_even = output_event;
                if(radioButton){
                    radioButton.checked = true;
                }
                 
                document.querySelector("select[name='edit_event_option']").value = output_event;
                document.getElementById("edit_event_option").onchange = function() {
                    var selectedValue = this.value; 
                };


             },
             error: function(xhr, status, error) {
                 console.error("AJAX request failed:", status, error);
             }
     });      
    }
  
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

</script>

<style>
    #output_table td,
    #output_table th {
        width: 100px; 
        padding: 10px;
    }
</style>