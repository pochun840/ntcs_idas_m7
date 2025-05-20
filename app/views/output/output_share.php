<script>
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




function toggleOnputTimeCommon(prefix, inputId, checked, option) {
    const inputElement = document.getElementById(inputId);
    if (!inputElement) return;

    // 避免不必要的動作（你預留的，但沒用，可根據需求加上）
    if ((inputElement.type === 'checkbox' || inputElement.type === 'radio') &&
        inputElement.checked !== checked) {
        // 可加入額外處理
    }

    const regex = new RegExp(`^${prefix}pin(\\d+)_\\d+$`);
    const match = inputId.match(regex);

    if (match) {
        const newId = `${prefix}time${match[1]}`;
        const timeElement = document.getElementById(newId);
        if (timeElement) {
            timeElement.disabled = (option == 1 || option == 3);
        }
    }
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
                $.ajax({
                    url: "?url=Outputs/copy_output",
                    method: "POST",
                    data: { 
                        from_job_id: job_id,
                        to_job_id: to_job_id
                    },
                    success: function(response) {

                        var responseData = JSON.parse(response);
                        alertify.alert(responseData.res_type, responseData.res_msg, function() {
                            get_output_by_job_id(job_id);
                        });

                        document.getElementById('copy_output').style.display='none';

                    },
                    error: function(xhr, status, error) {
                        
                    }
                });
        
            } 
        } else {
            // cancel
        }
    });
    document.getElementById('copy_output').style.display='none';
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

function crud_job_event(argument) {
    const table = document.getElementById('output_table');
    const selectedRow = table.querySelector('tr.selected');
    if (selectedRow) {
        output_event = selectedRow.getAttribute('data-event');
        const pinElement = selectedRow.querySelector('[data-outputpin]');
        output_pinval = pinElement ? pinElement.getAttribute('data-outputpin') : '';
        del_output_val = output_event;
    }

    switch (argument) {
        case 'del':
            if (job_id && del_output_val) {
                delete_output_id(job_id, del_output_val);
            }
            break;

        case 'new':
            if (!job_id) return;

            disableElements(temp);

            // 過濾出非 edit 的 pin，單獨處理
            const filtered_array = temp.filter(id => id.includes('pin') && !id.includes('edit_pin'));
            disableElements(filtered_array);

            document.getElementById('new_output').style.display = 'block';

            const eventOption = document.getElementById('Event_Option');
            eventOption.addEventListener('change', function () {
                const selectedValue = eventOption.value;
                const lockEvents = ['7', '8', '9'];
                const shouldLock = lockEvents.includes(selectedValue);
                toggleElementsInRange(1, 11, 2, shouldLock);
                if (!shouldLock) disableElements(filtered_array);
            });

            document.querySelectorAll('#Event_Option option').forEach(opt => {
                if (tempA.includes(opt.value)) opt.disabled = true;
            });

            break;

        case 'edit':
            if (!job_id || !output_event) return;

            const rows = document.querySelectorAll('#output_jobid_select tr.selected');
            if (!rows.length) {
                getLanguageMessage('language');
                return;
            }

            disableElements(temp);

            // disable 所有 edit_pin radio 和 edit_time
            temp.filter(id => id.includes('edit_pin')).forEach(id => {
                const match = id.match(/(edit_pin\d+)_(\d+)/);
                if (!match) return;
                const baseId = match[1].replace('edit_pin', '');
                for (let i = 0; i <= 2; i++) {
                    const el = document.getElementById(`edit_pin${baseId}_${i}`);
                    if (el) el.disabled = true;
                }

                const timeEl = document.getElementById(`edit_time${baseId}`);
                if (timeEl) timeEl.disabled = true;
            });

            // 啟用目前選中的 pin 對應的 radio + time
            if (output_pinval) {
                ['0', '1', '2'].forEach(i => {
                    const id = `edit_pin${output_pinval}_${i}`;
                    const el = document.getElementById(id);
                    if (el) el.disabled = false;
                });
                const timeEl = document.getElementById(`edit_time${output_pinval}`);
                if (timeEl) timeEl.disabled = false;
            }

            get_output_info(job_id, output_event);
            break;

        case 'copy':
            if (!job_id || !output_event) return;

            const jobinfo = window.job_list_new || {};
            document.getElementById("from_job_id").value = job_id;
            document.getElementById("from_job_name").value = jobinfo[job_id]?.JOBname || '';

            document.querySelectorAll('#JobSelect1 option').forEach(opt => {
                if (opt.value == job_id) {
                    opt.disabled = true;
                    opt.classList.add('disabled_input');
                }
            });

            const selectedCopyRows = document.querySelectorAll('#output_jobid_select tr.selected');
            if (selectedCopyRows.length > 0) {
                document.getElementById('copyinput').style.display = 'block';
            } else {
                getLanguageMessage('language');
            }

            break;

        case 'unified':
            if (!job_id) return;

            enableButton();
            resetBackgroundColor();
            if (output_job !== job_id) {
                alignsubmit(job_id);
            } else {
                resetalignsubmit(job_id);
            }
            break;
    }
}

function disableElements(idArray) {
    if (!Array.isArray(idArray)) return;
    idArray.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.disabled = true;
    });
}

function toggleElementsInRange(start, end, waveMax = 1, disable = true, includeTime = true, pinPrefix = 'pin', timePrefix = 'time') {
    for (let i = start; i <= end; i++) {
        // 控制 pin{i}_{j}
        for (let j = 0; j <= waveMax; j++) {
            const id = `${pinPrefix}${i}_${j}`;
            const el = document.getElementById(id);
            if (el) el.disabled = disable;
        }

        // 控制 time{i}
        if (includeTime) {
            const timeId = `${timePrefix}${i}`;
            const timeEl = document.getElementById(timeId);
            if (timeEl) timeEl.disabled = disable;
        }
    }
}



</script>