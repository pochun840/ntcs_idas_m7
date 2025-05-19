<script>

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

function crud_job_event(action) {
    if (!job_id) return;

    switch (action) {
        case 'new':
            handleNewEvent();
            break;

        case 'del':
            if (!input_event) return;
            delete_input_id(job_id, input_event);
            break;

        case 'edit':
            if (!input_event) return;
            if (!document.querySelectorAll('#input_jobid_select tr.selected').length) {
                getLanguageMessage('language');
                return;
            }
            handleEditEvent();
            break;

        case 'copy':
            if (!input_event) return;
            handleCopyEvent();
            break;

        case 'unified':
            handleUnifiedEvent();
            break;

        default:
            console.warn("Unsupported event:", action);
            break;
    }
}

// 🟩 個別處理邏輯封裝

function handleNewEvent() {
    disableRadioList(temp);
    disableOptions('#Event_Option', tempA);
    disableOptions('#Event_Option', temp_event, true); // 變灰顯示

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

    const selectedRows = document.querySelectorAll('#input_jobid_select tr.selected');
    if (selectedRows.length > 0) {
        document.getElementById('copyinput').style.display = 'block';
    } else {
        getLanguageMessage('language');
    }
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

function disableOptions(selector, values = [], gray = false) {
    const options = document.querySelectorAll(`${selector} option`);
    options.forEach(opt => {
        if (values.length === 0 || values.includes(opt.value)) {
            opt.disabled = true;
            opt.classList.add('disabled_input');
            if (gray) opt.style.color = 'gray';
        }
    });
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
                document.getElementById('edit_input').style.display = 'none';
                const responseData = JSON.parse(response);

                alertify.alert(responseData.res_type, responseData.res_msg, function () {
                    get_input_by_job_id(job_id);
                });
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

</script>