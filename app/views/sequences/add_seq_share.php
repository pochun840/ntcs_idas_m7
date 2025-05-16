<script>
    var dataType = "<?php echo $data['type']; ?>";
    window.onload = function() {
        if (dataType === 'new') {
            //SEQ頁面 預設值
            document.getElementById("seq_repeat").value = 5;
            document.getElementById("timeout").value = 60;
            document.getElementById("dt_time").value = 0;
            document.getElementById("tt_time").value = 0;
            document.getElementById("ok_seq_on").checked = true;
            document.getElementById("seq_stop_on").checked = true;
            document.getElementById("unscrew_count_switch_on").checked = true;
            document.getElementById("ng_unscrew_on").checked = true;
            document.getElementById("accu_angle_on").checked = true;
            document.getElementById("unscrew_mode_auto").checked = true;
            document.getElementById("unscrew_rpm").value = 150;
            document.getElementById("unscrew_dir_cw").checked = true;
            document.getElementById("unscrew_forcemode_on").checked = true;


            for (let i = 1; i <= 5; i++) {
                document.getElementById("Thread_Calcu_" + i).checked = true;
            }

            ['unscrew_rpm', 'unscrew_torque_threshold', 'unscrew_dir_cw', 'unscrew_dir_ccw', 'unscrew_forcemode_on', 'unscrew_forcemode_unlimit', 'unscrew_forcemode_off', 'unscrew_force'].forEach(id => document.getElementById(id).disabled = true);
         
        }

        if(dataType === 'edit'){
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            let displayedValue = '<?php echo isset($data['sequences']['Thread_Calcu']) ? $data['sequences']['Thread_Calcu'] : ''; ?>';
            setCheckboxesByValue(displayedValue);
        }
    };
    
    // 新增 sequence
    function save_sequence() {
        
        let data = new FormData();
        let job_id = document.getElementById("job_id").value;
        let seq_id = document.getElementById("seq_id").value;
        let SEQname = document.getElementById("SEQname").value;
        let time = new Date().toISOString().slice(0, 19).replace('T', ' '); 


        data.append("job_id", job_id);
        data.append("SEQID", seq_id);
        data.append("SEQname", SEQname);
        data.append("time", time);
        data.append("type", 0);
        data.append("act", 0);
        data.append("skip", 0);
        data.append("seq_repeat", document.getElementById("seq_repeat").value);
        data.append("timeout", document.getElementById("timeout").value);
        data.append("dt_time", document.getElementById("dt_time").value);
        data.append("tt_time", document.getElementById("tt_time").value);

        let ok_seqElement = document.querySelector('input[name="ok_seq"]:checked');
        data.append("ok_seq_val", ok_seqElement ? ok_seqElement.value : null);

        let ok_stopElement = document.querySelector('input[name="ok_stop"]:checked');
        data.append("ok_stop_val", ok_stopElement ? ok_stopElement.value : null);

        data.append("countType", 0);
        data.append("ok_screw", 1);
        data.append("ng_stop", document.getElementById('ng_stop').value);

        let ng_unscrew = document.querySelector('input[name="ng_unscrew"]:checked');
        data.append("ng_unscrew_val", ng_unscrew ? ng_unscrew.value : null);

        data.append("interrupt_alarm", 1);

        let accu_angle = document.querySelector('input[name="accu_angle"]:checked');
        data.append("accu_angle_val", accu_angle ? accu_angle.value : null);

        let angle_calculation_data = getCheckboxValue(); 
        data.append("angle_calculation_data", angle_calculation_data);


        let unscrew_mode = document.querySelector('input[name="unscrew_mode"]:checked');
        data.append("unscrew_mode_val", unscrew_mode ? unscrew_mode.value : null);

        let unscrew_forcemode = document.querySelector('input[name="unscrew_forcemode"]:checked');
        data.append("unscrew_forcemode_val", unscrew_forcemode ? unscrew_forcemode.value : null);
        data.append("unscrew_force", document.getElementById("unscrew_force").value);

        data.append("unscrew_rpm", document.getElementById("unscrew_rpm").value);
        data.append("unscrew_torque_threshold", document.getElementById("unscrew_torque_threshold").value);

        let unscrew_dir = document.querySelector('input[name="unscrew_dir"]:checked');
        data.append("unscrew_dir_val", unscrew_dir ? unscrew_dir.value : 0);

        data.append("image", '');
        data.append("message", '');
        data.append("delay", 0);
        data.append("input", 0);
        data.append("input_signal", 0);
        data.append("output", 0);
        data.append("output_signal", 1);
        data.append("output_durat", 100);
        data.append("addtion", '');

        let unscrew_count_switch = document.querySelector('input[name="unscrew_count_switch"]:checked');
        data.append("unscrew_count_switch_val", unscrew_count_switch ? unscrew_count_switch.value : null);

        let check = input_check();
        if(check){
            document.getElementById('spinner').style.display = 'block';
            $.ajax({
                url: '?url=Sequences/create_seq',
                type: 'POST',
                data: data,
                processData: false, 
                contentType: false, 
                success: function(response) {
                     const job_id = document.getElementById("job_id").value;
                    success_response_seq(response, 'spinner', '../public/?url=Sequences/index/' + job_id);
                },
                error: function(xhr, status, error) {
                    // 處理錯誤
                    console.error('Error:', error);
                }
            });
        }
    }

    //edit sequence
    function edit_sequence(){
        let job_id = document.getElementById("job_id").value;
        let seq_id = document.getElementById("seq_id").value;
        let SEQname = document.getElementById("SEQname").value;
        let time = new Date().toISOString().slice(0, 19).replace('T', ' '); 


        let data = new FormData();
        data.append("job_id", job_id);
        data.append("SEQID", seq_id);
        data.append("SEQname", SEQname);
        data.append("time", time);
        data.append("type", 0);
        data.append("act", 0);
        data.append("skip", 0);
        data.append("seq_repeat", document.getElementById("seq_repeat").value);
        data.append("timeout", document.getElementById("timeout").value);
        data.append("dt_time", document.getElementById("dt_time").value);
        data.append("tt_time", document.getElementById("tt_time").value);

        let ok_seqElement = document.querySelector('input[name="ok_seq"]:checked');
        data.append("ok_seq_val", ok_seqElement ? ok_seqElement.value : null);

        let ok_stopElement = document.querySelector('input[name="ok_stop"]:checked');
        data.append("ok_stop_val", ok_stopElement ? ok_stopElement.value : null);

        data.append("countType", 0);
        data.append("ok_screw", 1);
        data.append("ng_stop", document.getElementById('ng_stop').value);

        let ng_unscrew = document.querySelector('input[name="ng_unscrew"]:checked');
        data.append("ng_unscrew_val", ng_unscrew ? ng_unscrew.value : null);

        data.append("interrupt_alarm", 1);

        let accu_angle = document.querySelector('input[name="accu_angle"]:checked');
        data.append("accu_angle_val", accu_angle ? accu_angle.value : null);

        let angle_calculation_data = getCheckboxValue(); 
        data.append("angle_calculation_data", angle_calculation_data);

   
        let unscrew_mode = document.querySelector('input[name="unscrew_mode"]:checked');
        data.append("unscrew_mode_val", unscrew_mode ? unscrew_mode.value : null);
        

        let unscrew_forcemode = document.querySelector('input[name="unscrew_forcemode"]:checked');
        data.append("unscrew_forcemode_val", unscrew_forcemode ? unscrew_forcemode.value : null);
        data.append("unscrew_force", document.getElementById("unscrew_force").value);

        data.append("unscrew_rpm", document.getElementById("unscrew_rpm").value);
        data.append("unscrew_torque_threshold", document.getElementById("unscrew_torque_threshold").value);

        let unscrew_dir = document.querySelector('input[name="unscrew_dir"]:checked');
        data.append("unscrew_dir_val", unscrew_dir ? unscrew_dir.value : 0);

        data.append("image", '');
        data.append("message", '');
        data.append("delay", 0);
        data.append("input", 0);
        data.append("input_signal", 0);
        data.append("output", 0);
        data.append("output_signal", 1);
        data.append("output_durat", 100);
        data.append("addtion", '');

        let unscrew_count_switch = document.querySelector('input[name="unscrew_count_switch"]:checked');
        data.append("unscrew_count_switch_val", unscrew_count_switch ? unscrew_count_switch.value : null);

        let check = input_check();
        if(check){
            $.ajax({
                url: '?url=Sequences/edit_seq',
                type: 'POST',
                data: data,
                processData: false, 
                contentType: false, 
                success: function(response) {
                        const job_id = document.getElementById("job_id").value;
                        success_response_seq(response, 'spinner', '../public/?url=Sequences/index/' + job_id);
                },
                error: function(xhr, status, error) {
                    // 處理錯誤
                    console.error('Error:', error);
                }
            });
        }


    }

    function getCheckboxValue() {
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
        
        // 先清空所有勾選狀態
        checkboxes.forEach(checkbox => checkbox.checked = false);

        // 根據值進行勾選
        if (value >= 16) {
            checkboxes[0].checked = true; // 第1個
            value -= 16;
        }
        if (value >= 8) {
            checkboxes[1].checked = true; // 第2個
            value -= 8;
        }
        if (value >= 4) {
            checkboxes[2].checked = true; // 第3個
            value -= 4;
        }
        if (value >= 2) {
            checkboxes[3].checked = true; // 第4個
            value -= 2;
        }
        if (value >= 1) {
            checkboxes[4].checked = true; // 第5個
        }
    }


    function input_check(argument) {
        let Tool_Max_Torque = document.getElementById('tool_max_torque').value;
        let Tool_Min_Torque = document.getElementById('tool_min_torque').value;
        let Tool_Max_RPM = document.getElementById('tool_max_rpm').value;
        let Tool_Min_RPM = document.getElementById('tool_min_rpm').value;

        //判斷 name="unscrew_forcemode" 選取的value
        var selectedValue = document.querySelector('input[name="unscrew_forcemode"]:checked')?.value;

        // 檢查 unscrew_mode_auto 是否被選中
        var isAutoMode = document.getElementById('unscrew_mode_auto')?.checked;
      
        let conditions = [
            { id: 'SEQname', pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/, min: null, max: null },
            { id: 'seq_repeat', pattern: /^\d{0,4}$/, min: 1, max: 99 },
            { id: 'timeout', pattern: /^\d{0,5}?$/, min: 0, max: 60 },
            { id: 'dt_time', pattern: /^\d{0,5}?$/, min: 0, max: 99 },
            { id: 'tt_time', pattern: /^\d{0,5}?$/, min: 0, max: 6000 },
            { id: 'ng_stop', pattern: /^\d{0,5}?$/, min: 0, max: 9 },
            { id: 'unscrew_rpm', pattern: /^\d{1,3}$/, min: Tool_Min_RPM, max: Tool_Max_RPM },
            { id: 'unscrew_torque_threshold', pattern: /^\d{1,3}(\.\d{1})?$/, min: 0, max: Tool_Max_Torque },
            { id: 'unscrew_force', pattern: /^\d{1,3}$/, min: 1, max: 100 },
        ];

        let isFormValid = true;
        conditions.forEach(function(input) {
            var element = document.getElementById(input.id);
            var value = element.value.trim();

            // 如果 unscrew_mode_auto 被選中, 跳過 unscrew_torque_threshold 和 unscrew_force 的驗證
            if (isAutoMode && (input.id === 'unscrew_torque_threshold' || input.id === 'unscrew_force')) {
                return;
            }
            

            if(input.id != 'SEQname'){
                var nextSibling = element.nextElementSibling;
                if (nextSibling) {
                    nextSibling.innerHTML = input.min + ' ~ ' + input.max;
                }
            }


            if (selectedValue != 0 && input.id === 'unscrew_force') {
                toggleDisableAndError(input.id, true);  
                return; 
            }

            if (value === "") {
                element.classList.add("is-invalid");
                isFormValid = false;
            } else if (!input.pattern.test(value)) {
                element.classList.add("is-invalid");
                isFormValid = false;
            } else if (input.min !== null && parseFloat(value) < input.min) {
                element.classList.add("is-invalid");
                isFormValid = false;
            } else if (input.max !== null && parseFloat(value) > input.max) {
                element.classList.add("is-invalid");
                isFormValid = false;
            } else {
                element.classList.remove("is-invalid");
            }

        });

        console.log(conditions)

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
        var autoModeRadio = document.getElementById('unscrew_mode_auto');
        var customModeRadio = document.getElementById('unscrew_mode_custom');
    
        var inputElements = document.querySelectorAll('#div_speed input, #div_torque_threshold input, #div_direction input, #div_force input');
        
        if (autoModeRadio.checked) {
            inputElements.forEach(function(element) {
                element.disabled = true;
            });
        } else if (customModeRadio.checked) {
            inputElements.forEach(function(element) {
                element.disabled = false;
            });
        }
    }

    // 監聽頁面加載完成後執行函數
    document.addEventListener('DOMContentLoaded', function() {
    // 初始化時根據當前選中的單選按鈕來決定輸入框是否禁用
    toggleInputsBasedOnMode();

    // 綁定單選按鈕的事件監聽器，當狀態變化時觸發
    document.getElementById('unscrew_mode_auto').addEventListener('change', toggleInputsBasedOnMode);
    document.getElementById('unscrew_mode_custom').addEventListener('change', toggleInputsBasedOnMode);
  });


  //排序
  function sendRowInfoArray() {

    var jobid = '<?php echo $data['job_id']?>';
    var dataToSend = {
        jobid: jobid,
        rowInfoArray: rowInfoArray
    };

    console.log(dataToSend);


    $.ajax({
        url: "?url=Sequences/adjustment_order", 
        method: "POST",
        data: dataToSend,
        success: function(response) {
            console.log(response);
            history.go(0); 
        },
        error: function(xhr, status, error) {
            console.error('Error sending data:', error);
        }
    });
}




</script>
