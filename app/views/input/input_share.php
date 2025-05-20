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


function delete_input_id(jobid,input_event){

    if(job_id){
        $.ajax({
            url: "?url=Inputs/delete_input",
            method: "POST",
            data: { 
                job_id: job_id,
                input_event: input_event,
             
            },
            success: function(response) {
            
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    get_input_by_job_id(job_id);
                });

            },
            error: function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
            }
        });     
    }

}


function crud_job_event(action) {
    if (!job_id) return;

    switch (action) {
        case 'new':
            handleNewJobEvent();
            break;

        case 'del':
            if (input_event) {
                delete_input_id(job_id, input_event);
            }
            break;

        case 'edit':
            if (!input_event) return;

            const selectedEditRows = document.querySelectorAll('#input_jobid_select tr.selected');
            if (!selectedEditRows.length) {
                getLanguageMessage('language');
                return;
            }

            handleEditJobEvent();
            break;

        case 'copy':
            if (!input_event) return;

            handleCopyJobEvent();
            break;

        case 'unified':
            handleUnifiedJobEvent();
            break;

        default:
            console.warn(`Unknown action type: ${action}`);
            break;
    }
}

function handleNewJobEvent() {
    disableRadioList(temp);
    disableOptions('#Event_Option', tempA);
    disableOptions('#Event_Option', temp_event, true); // 顯示灰色但禁用

    document.getElementById('newinput').style.display = 'block';
}

function handleEditJobEvent() {
    //disableSelectOptions('#edit_Event_Option');
    disableRadioList(temp);

    get_input_info(job_id, input_event);
    handleEventChange(input_event);
}

function handleCopyJobEvent() {
    const from_job_name = window.jobinfo?.[job_id]?.JOBname || '';
    document.getElementById("from_job_id").value = job_id;
    document.getElementById("from_job_name").value = from_job_name;

    const options = document.querySelectorAll('#JobSelect1 option');
    options.forEach(opt => {
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


function handleUnifiedJobEvent() {
    enableButton();
    resetBackgroundColor();

    if (input_job !== job_id) {
        alignsubmit(job_id);
    } else {
        resetalignsubmit(job_id);
    }
}


function disableRadioList(ids) {
    if (!Array.isArray(ids)) return;
    ids.forEach(id => {
        const radio = document.getElementById(id);
        if (radio?.type === 'radio') {
            radio.disabled = true;
        }
    });
}

function disableOptions(selector, values = [], gray = false, reset = false) {
    if (!Array.isArray(values)) {
        console.warn("disableOptions: 'values' 不是陣列，自動轉換");
        values = [values]; // 或 `return` 看你要強制還是忽略
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
        //alertify.error("請選擇一個 Pin！");
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
            success: function(response) {

                document.getElementById('newinput').style.display='none';
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    get_input_by_job_id(job_id);
                });
            },
            error: function(xhr, status, error) {
                
            }
        });

    }
}


function copy_input_id(){
    var language = getCookie('language');
    if(language == "zh-cn"){
        var text_info ='若设定已存在，将会取代原有设定';
    }else if(language == "zh-tw"){
        var text_info ='若設定已存在，將會取代原有設定';
    }else{
        var text_info ='If the job input already exists, it will replace the original setting';
    }
    alertify.confirm( text_info , function (e) {
        if (e) {
            var to_job_id = document.getElementById("JobSelect1").value;
            if(to_job_id){
                $.ajax({
                    url: "?url=Inputs/copy_input_event",
                    method: "POST",
                    data: { 
                        from_job_id: job_id,
                        to_job_id: to_job_id
                    },
                    success: function(response) {
                        
                        document.getElementById('copyinput').style.display='none';
                        var responseData = JSON.parse(response);
                        alertify.alert(responseData.res_type, responseData.res_msg, function() {
                            get_input_by_job_id(job_id);
                        });

                        
                    },
                    error: function(xhr, status, error) {
                        
                    }
                });
        
            }

        } else {
            // cancel
        }
    });

}



function get_input_by_job_id(jobid){
    $.ajax({
        url: "?url=Inputs/get_input_by_job_id",
        method: "POST",
        data: { 
            jobid: jobid,
        },
        success: function(response) {

            var data = JSON.parse(response);
            var job_inputlist = data.job_inputlist;
            temp = data.temp;
            tempA = data.tempA;

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
                    getLanguageMessage('language');
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
                temp = data.temp;
                tempA = data.tempA;
                temp_event = data.temp_event;

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

</script>



<style>
    #input_table td,
    #input_table th {
        width: 100px; 
        padding: 10px;
    }
</style>