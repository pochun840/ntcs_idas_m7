
document.addEventListener('DOMContentLoaded', function() {
    function updateTime() {
        var currentTime = new Date();
        var hours = currentTime.getHours();
        var minutes = currentTime.getMinutes();
        var seconds = currentTime.getSeconds();
        var period = hours >= 12 ? 'PM' : 'AM';
    
        hours = hours % 12;
        hours = hours ? hours : 12; // 0 should be 12
        minutes = minutes < 10 ? '0'+minutes : minutes;
        seconds = seconds < 10 ? '0'+seconds : seconds;
    
        var timeString = currentTime.getFullYear() + '-' + 
                            ('0' + (currentTime.getMonth() + 1)).slice(-2) + '-' + 
                            ('0' + currentTime.getDate()).slice(-2) + ' ' + 
                            ('0' + hours).slice(-2) + ':' + 
                            ('0' + minutes).slice(-2) + ':' + 
                            ('0' + seconds).slice(-2) + ' ' + period;
    
        document.getElementById('currentSystemTime').innerText = timeString;
    }
    
    updateTime();
    // 每秒更新一次時間
    setInterval(updateTime, 1000);
    
});

function cc_save(){

    var control_id = document.getElementById('control_id').value;
    if (isNaN(control_id) || control_id < 1 || control_id > 250) {
        return false;
    }

    var control_name = document.getElementById('control_name').value;
    var selectElement = document.getElementById('select_language');
    var selectedValue = selectElement.value;
    var batch_val = document.querySelector('input[name="batch-mode-option"]:checked').value;
    var buzzer_val = document.querySelector('input[name="buzzer-option"]:checked').value;

    if(control_id){
        $.ajax({
            url: "?url=Settings/control_setting",
            method: "POST",
            data:{ 
                control_id: control_id,
                control_name: control_name,
                lang_val: selectedValue,
                batch_val:batch_val,
                buzzer_val:buzzer_val

            },
            success: function(response) {
                //history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }
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
        alert("請確保所有欄位都只包含 0-9 的數字，並且最多四位。");
        return; // 如果驗證失敗，停止函式執行
    }

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
        success: function(response) {
            var responseData = JSON.parse(response);
            alertify.alert(responseData.res_type, responseData.res_msg, function() {
                history.go(0);
            });         
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
            var responseData = JSON.parse(response);
            alertify.alert(responseData.res_type, responseData.res_msg, function() {
                history.go(0);
            });    
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });

}

//設置 	global-downshift
function downshift_save(){

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
            var responseData = JSON.parse(response);
            alertify.alert(responseData.res_type, responseData.res_msg, function() {
                history.go(0);
            });    
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });

}

//barcode mode 選擇
function toggleBarcodeSeq() {
    let barcodeMode = document.getElementById('barcode_mode');
    let barcodeSeq = document.getElementById('barcode_seq');
    
    // Check the selected value
    if (barcodeMode.value === '1' || barcodeMode.value === '2') {
        barcodeSeq.disabled = true; 
        document.getElementById("barcode_select_seq").style.display='none';
    } else {
        barcodeSeq.disabled = false;
        document.getElementById("barcode_select_seq").style.display='block';
    }
}


//透過JOBID 取得對應的SEQ
function fetchSeqList() {
    const jobId = document.getElementById('barcode_job').value;

    const defaultOption = document.createElement('option');
    defaultOption.value = "-1";
    defaultOption.textContent = "<?php echo $text['system_barcode_select_seq_m'];?>";
    
    if (jobId === '-1') {
        document.getElementById('barcode_seq').innerHTML = ''; 
        document.getElementById('barcode_seq').appendChild(defaultOption);
        return;
    }

    $.ajax({
        url: '?url=Settings/GetJobSeq',
        type: 'POST',
        data: { job_id: jobId },
        success: function(response) {
            const seqList = JSON.parse(response);
            const barcodeSeq = document.getElementById('barcode_seq');
            barcodeSeq.innerHTML = ''; 
            barcodeSeq.appendChild(defaultOption); 

            const fragment = document.createDocumentFragment();
            seqList.forEach(seq => {
                const option = document.createElement('option');
                option.value = seq.SEQID;
                option.textContent = `${seq.SEQID} ${seq.SEQname}`;
                fragment.appendChild(option);
            });
            const currentOptions = barcodeSeq.getElementsByTagName('option');
            if (currentOptions.length > 0 && currentOptions[0].value === "-1") {
                barcodeSeq.removeChild(currentOptions[0]);
            }
            barcodeSeq.appendChild(fragment);
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });
}



function Export_SystemConfig(argument) {
    var xhr = new XMLHttpRequest();
    xhr.responseType = "blob";  
    
    xhr.onload = function() {
        if (xhr.status === 200) {
            var a = document.createElement("a");
            a.href = window.URL.createObjectURL(xhr.response);
            a.download = "data.zip";  
            a.style.display = "none";
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        } else {
            console.error("Download failed with status: " + xhr.status);
        }
    };

  
    xhr.open("GET", "?url=Settings/export_sysytem_config", true);
    xhr.send();
}


function Import_SystemConfig(){

    var bbs = document.getElementById("import-file-uploader").files[0];
    var form = new FormData();
    form.append("file", bbs)
    var url = '?url=Settings/Import_Config';        

    if(bbs == undefined){
        
    }else{
        $.ajax({ // 提醒
            type: "POST",
            processData: false,
            cache: false,
            contentType: false,
            data: form,
            dataType: "json",
            url: url,
            beforeSend: function() {
                $('#overlay').removeClass('hidden');
            },
        }).done(function(result) { //成功且有回傳值才會執行
            $('#overlay').addClass('hidden');
            document.getElementById("import-file-uploader").value = '';
        });
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
            $('#overlay').removeClass('hidden');
        },
    }).done(function(result) {
        $('#overlay').addClass('hidden');
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
    else
    {
        //alert("Function ["+ ButtonMode +"] is under constructing ...");
    }
}



function getCookie(name) 
{
    var nameEQ = name + "=";
    //alert(document.cookie);
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

function set_agent_ip(){
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
                //history.go(0);
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
    var work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M9.001.666A8.336 8.336 0 0 0 .668 8.999c0 4.6 3.733 8.334 8.333 8.334s8.334-3.734 8.334-8.334S13.6.666 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Zm-1.666-4.833L5.168 8.666 4.001 9.833l3.334 3.333L14 6.499l-1.166-1.166-5.5 5.5Z" fill="#1E8E3E" fill-rule="evenodd"></path></svg>';
    var not_work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M11.16 5.666 9 7.824 6.843 5.666 5.668 6.841l2.158 2.158-2.158 2.159 1.175 1.175 2.158-2.159 2.159 2.159 1.175-1.175-2.159-2.159 2.159-2.158-1.175-1.175ZM9 .666A8.326 8.326 0 0 0 .668 8.999a8.326 8.326 0 0 0 8.333 8.334 8.326 8.326 0 0 0 8.334-8.334A8.326 8.326 0 0 0 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Z" fill="#D93025" fill-rule="evenodd"></path></svg>';

    var url = '?url=Admins/AgentTest';
    if(action == 'start'){
        url = '?url=Admins/StartAgent';
    }
    if(action == 'stop'){
        url = '?url=Admins/CloseAgent';
    }

    if(action ){
        $.ajax({
            url: url,
            method: "POST",
            data:{ 
           
            },
            success: function(response) {
                console.log(response);
        
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }

}

function idas_update() {
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
   
}


function update_barcode(){
    var barcode_name  = document.getElementById("barcode_name").value;
    var barcode_from  = document.getElementById("barcode_from").value;
    var barcode_count = document.getElementById("barcode_count").value;
    var barcode_mode  = document.querySelector("select[name='barcode_mode']").value;
    var barcode_job   = document.querySelector("select[name='barcode_job']").value;
    var barcode_seq   = document.querySelector("select[name='barcode_seq']").value;
    
    if(barcode_name){
        $.ajax({
            url: "?url=Settings/Update_Barcode",
            method: "POST",
            data:{ 
                barcode_name: barcode_name,
                barcode_from: barcode_from,
                barcode_count: barcode_count,
                barcode_job: barcode_job,
                barcode_seq: barcode_seq,
                barcode_mode: barcode_mode
            },
            success: function(response) {
                console.log(response);
                //alert(response);
                $.ajax({
                    url: "?url=Settings/show_Barcodes",
                    method: "GET",
                    success: function(html) {
                        $('#total_barcodes').html(html);  
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching barcodes:", error);
                    }
                });
            },
            error: function(xhr, status, error) {
                
            }
        });   

    }  
}

function delete_barcode() {
    var del_barcode_id = [];
    var checkboxes = document.querySelectorAll('input[name="barcode_check"]:checked');
    
    checkboxes.forEach(function (checkbox) {
        del_barcode_id.push(checkbox.value);
    });
    
    if(del_barcode_id){
        $.ajax({
            url: "?url=Settings/delete_barcodes",
            method: "POST",
            data:{ 
                del_barcode_id: del_barcode_id

            },
            success: function(response) {
                //console.log(response);
                //alert(response);
                $.ajax({
                    url: "?url=Settings/show_Barcodes",
                    method: "GET",
                    success: function(html) {
                        $('#total_barcodes').html(html);  
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching barcodes:", error);
                    }
                });
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }
    
}
