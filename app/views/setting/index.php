
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_setting.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['setting'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>
    <div class="main-content">
        <div class="center-content">
            <div class="w3-center">
                <button id="bnt1" name="Controller_Display" class="button active" onclick="OpenButton('Controller')"><?php echo $text['controller_setting'];?></button>
                <button id="bnt2" name="System_Display" class="button" onclick="OpenButton('System')"><?php echo $text['system_setting'];?></button>
                <button id="bnt3" name="Barcode_Display" class="button" onclick="OpenButton('Barcode')"><?php echo $text['system_barcode_setting'] ;?></button>
                <button id="bnt4" name="Connect_Display" class="button" onclick="OpenButton('Connect')"><?php echo $text['system_connect_setting'];?></button>
                <button id="bnt5" name="iDas_Display" class="button" onclick="OpenButton('Update')">iDAS</button>
            </div>
        
            <div id="Controller_Setting" class="divMode">
                <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['controller_setting'];?></div>
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_id'];?>:</div>
                    <div class="col-3 t2">
                        <input id="control_id" name="control_id" type="number" max=250 min=1 maxlength="3" value="<?php echo $data['controller_info']['device_id'];?>" class="t3 form-control"  required>
                    </div>
                </div>    
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_name'];?>:</div>
                    <div class="col-3 t2">
                        <input id="control_name" name="control_name" maxlength="12" type="text" value="<?php echo $data['controller_info']['device_name'];?>" class="t3 form-control"  required>
                    </div>
                </div>    
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_language'];?>:</div>
                    <div class="col-3 t2">
                        <select class="form-select" id="select_language" name="select_language">
                            <?php foreach($data['lang_arr'] as $k_lang =>$v_lang){?>
                            <option value="<?php echo $k_lang;?>"  <?php echo $k_lang == $data['controller_info']['device_language'] ? 'selected' : ''; ?> ><?php echo $v_lang;?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>    
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_batch'];?>:</div>
                    <div class="col t2" >
      			      	<div class="col-1 form-check form-check-inline">
        				    <input class="form-check-input" type="radio" name="batch-mode-option" id="dec" value="1"  <?php echo $data['controller_info']['batch'] == 1 ? 'checked="checked"' : ''; ?>>
            				<label class="form-check-label" for="dec"><?php echo $text['system_dec'];?></label>
            			</div>
            			<div class="form-check form-check-inline">
            			    <input class="form-check-input" type="radio" name="batch-mode-option" id="inc" value="2"  <?php echo $data['controller_info']['batch'] == 2 ? 'checked="checked"' : ''; ?> >
            				<label class="form-check-label" for="inc"><?php echo $text['system_inc'];?></label>
            			</div>
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_buzzer'];?>:</div>
                    <div class="col t2">
      			      	<div class="col-1 form-check form-check-inline">
           				    <input class="form-check-input" type="radio" name="buzzer-option" id="buzzer-on" value="1"  <?php echo $data['controller_info']['buzzer_mode'] == 1 ? 'checked="checked"' : ''; ?>>
               				<label class="form-check-label" for="buzzer-on"><?php echo $text['switch_on'];?></label>
               			</div>
              			<div class="form-check form-check-inline">
               			    <input class="form-check-input" type="radio" name="buzzer-option" id="buzzer-off" value="2"  <?php echo $data['controller_info']['buzzer_mode'] == 2 ? 'checked="checked"' : ''; ?>>
               				<label class="form-check-label" for="buzzer-off"><?php echo $text['switch_off'];?></label>
               			</div>
                    </div>
                </div>
                <div style="text-align: center;margin-top: 50px;">
                    <button class="all-btn w3-button w3-border w3-round-large" id="cc_save" onclick="cc_save()"><?php echo $text['save'];?></button>
                </div>
            </div>

            <div id="System_Setting" class="divMode" style="display: none">
                <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_setting'];?></div>
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_password'];?>:</div>
                    <div class="col t2">
                        <form id="edit_password"  style="margin: 3px 0px">
                            <input type="password" id="new_password" size="15" placeholder="<?php echo $text['system_new_password'];?>" maxlength="10" required class="t3 w3-submit w3-border w3-round">&nbsp;
                            <input type="password" id="comfirm_password" size="15" placeholder="<?php echo $text['system_confirm_password'];?>" maxlength="10" required class="t3 w3-submit w3-border w3-round">
                            <input type="button" value="<?php echo $text['save'];?>" onclick="edit_password()" class="all-btn w3-submit w3-border w3-round-large" style="float: right">
                        </form>
                    </div>        
                </div>          
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_sys_date'];?>(UTC):</div>
                    <div class="col t2">
                        <form style="margin: 3px 0px">
                            <span id="currentSystemTime"></span>&nbsp;
                            <input type="datetime-local" id="newTime" value="" required class="t3 w3-submit w3-border w3-round">
                            <input type="button" value="<?php echo $text['save'];?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right" onclick="time_save()">
                        </form>
                    </div>        
                </div>          
                <div class="row t2">
                    <div class="col t1"><?php echo $text['system_export_config'];?>:</div>
                    <div class="col t2">
                        <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Export_SystemConfig();"><?php echo $text['system_export_config'];?></button>
                    </div>        
                </div>          
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_import_config'];?>:</div>
                    <div class="col t2">
                        <input type="file" id="import-file-uploader" data-target="import-file-uploader" accept=".cfg" class="t3 w3-submit w3-border w3-round">
                        <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Import_SystemConfig();"><?php echo $text['system_import_config'];?></button>
                    </div>        
                </div>          
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_firmware_update'];?>:</div>
                    <div class="col t2">
                        <input type="file" id="firmware-file-uploader" data-target="firmware-file-uploader" accept=".cfg" class="t3 w3-submit w3-border w3-round">
                        <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Firmware_Update();"><?php echo $text['system_firmware_update'];?></button>
                    </div>        
                </div>          
            </div>

            <div id="Barcode_Setting" class="divMode" style="display: none">
                <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_barcode_setting'] ;?></div>
                <div class="table-container">
                    <div class="scrollbar" id="style-table">
                        <div class="scrollbar-force-overflow">
                            <table id="job_table" class="table w3-table-all w3-hoverable">
                                <thead id="header-table">
                                    <tr class="w3-dark-grey">
                                        <th></th>
                                        <th><?php echo $text['job_id'];?></th>
                                        <th><?php echo $text['job_name'];?></th>
                                        <th><?php echo $text['system_barcode'];?></th>
                                        <th><?php echo $text['system_barcode_from'];?></th>
                                        <th><?php echo $text['system_barcode_to'];?></th>
                                    </tr>
                                </thead>

                                <tbody style="font-size: 1.8vmin;text-align: center;" id='total_barcodes'>
                                  
                                    <?php foreach ($data['barcodes'] as $k_b =>$v_b){?>
                                        <tr>
                                            <td style="text-align: center; vertical-align: middle;" >
                                                <input class="form-check-input" type="checkbox" name="barcode_check" id="barcode_check" value="<?php echo $v_b['barcode_selected_job'];?>" style="zoom:1.2">
                                            </td> 
                                            <td><?php echo $v_b['barcode_selected_job'];?></td>
                                            <td><?php echo $v_b['job_name'];?></td>
                                            <td><?php echo $v_b['barcode'];?></td>
                                            <td><?php echo $v_b['barcode_range_from'];?></td>
                                            <td><?php echo $v_b['barcode_range_count'];?></td>
                                        </tr>
                                    <?php } ?>
                                  
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                 
                <hr>
                               
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_barcode'];?>:</div>
                    <div class="col-6 t2">
                        <input id="barcode_name" name="barcode_name" style="height: 32px" type="text" value="" maxlength="54" class="form-control" required>
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_barcode_match_from'];?>:</div>
                    <div class="col-3 t2">
                        <input id="barcode_from" name="barcode_from" style="height: 32px" type="text" value="" class="form-control">
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_barcode_match_to'];?>:</div>
                    <div class="col-3 t2">
                        <input id="barcode_count" name="barcode_count" style="height: 32px" type="text" value="" class="form-control">
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['select_job'];?>:</div>
                    <div class="col-3 t2">
                        <select class="form-select" id="barcode_job" name="barcode_job">
                            <option value="-1"><?php echo $text['system_barcode_select_job_m'];?></option>
                                <?php
                                foreach ($data['job_list'] as $key => $value) {?>
                                    <option value='<?php echo $value['job_id'];?>'><?php echo $value['job_id']." ".$value['job_name'];?></option>
                                <?php }?>
                                
                        </select>
                    </div>
                </div>
                <div style="text-align: center;margin-top: 50px;">
                    <button class="all-btn w3-button w3-border w3-round-large" onclick="update_barcode()" ><?php echo $text['save'];?></button>&nbsp;&nbsp;
                    <button class="all-btn w3-button w3-border w3-round-large" onclick="delete_barcode()" ><?php echo $text['delete_text'];?></button>
                </div>               
            </div>

            <div id="Connect_Setting" class="divMode" style="display: none">
                <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_connect_setting'];?></div>
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_connect_number'];?>:</div>
                    <div class="col t2">
                        <form id="edit_max_link" style="margin: 3px 0px" method="post">
                            <input type="text" name="max_user" id="max_user" inputmode="numeric" pattern="[0-9]*" min='1' size="15" maxlength="2" required class="t3 w3-submit w3-border w3-round">&nbsp;
                            <span><?php echo $text['system_connect_max_number'];?> : <?php echo $data['max_user']; ?></span>
                            <input type="button" onclick="set_max_link()" value="<?php echo $text['save'];?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right">
                        </form>
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_connect_guest_pwd'];?>:</div>
                    <div class="col t2">
                        <form id="edit_guest_password" method="post" style="margin: 3px 0px">
                            <input type="password" id="new_password_guest" size="15" placeholder="<?php echo $text['system_new_password'];?>" maxlength="10" required class="t3 w3-submit w3-border w3-round">&nbsp;
                            <input type="password" id="comfirm_password_guest" size="15" placeholder="<?php echo $text['system_confirm_password'];?>" maxlength="10" required class="t3 w3-submit w3-border w3-round">
                            <input type="button"  onclick="button_save_password_gust()"value="<?php echo $text['save'];?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right">
                        </form>
                    </div>        
                </div>          
                <div class="row t2">
                    <div class="col-3 t1">Agent IP:</div>
                    <div class="col t2">
                        <form id="agent_ip" style="margin: 3px 0px" method="post">
                            <input type="text" name="agent_server_ip" id="agent_server_ip" size="15" required class="t3 w3-submit w3-border w3-round">&nbsp;
                            <span>Agent IP : <?php echo $data['agent_server_ip']; ?></span>
                            <input type="submit" value="<?php echo $text['save'];?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right">
                        </form>
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-3 t1">Agent Type:</div>
                    <div class="col t2">
                        <form id="agent_type_form" method="post">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="agent_type" id="agent_type_0" value="0">
                                <label class="form-check-label" for="agent_type_0">None</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="agent_type" id="agent_type_1" value="1">
                                <label class="form-check-label" for="agent_type_1">Client</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="agent_type" id="agent_type_2" value="2" required>
                                <label class="form-check-label" for="agent_type_2">Server</label>
                            </div>

                            <input type="button" onclick="set_agent_type()" value="<?php echo $text['save'];?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right">
                        </form>
                    </div>
                    <div class="row">
                        <div class="col-3 t1"></div>
                        <div class="col t2">
                            <span>Client Status:<div id="c_status" style="display:inline-block;"></div></span>
                            <span>Server Status:<div id="s_status" style="display:inline-block;"></div></span>

                            <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px"  onclick="StatusCheck()" >Check</button>
                            <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px;" onclick="StatusCheck('start')" >START</button>
                            <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px"  onclick="StatusCheck('stop')" >STOP</button>
                        </div>
                    </div>
                </div>

                <hr>
                
                <form action="" method="post" style="padding: 0px 15px; ">
                    <div class="table-responsive" style="overflow-y: auto; margin-bottom: 20px">
                        <div class="scrollbar" id="style-table">
                            <div class="scrollbar-force-overflow">
                                <table class="table w3-table-all w3-hoverable">
                                    <thead id="header-table">
                                        <tr class="w3-dark-grey">
                                            <th><?php echo $text['select'];?></th>
                                            <th><?php echo $text['system_connect_username'];?></th>
                                            <th>IP</th>
                                            <th><?php echo $text['system_connect_timestamp'];?></th>
                                        </tr>
                                    </thead>
                                    <tbody style="font-size: 1.8vmin;text-align: center;">
                                        <?php foreach($data['active_session'] as $key =>$val){?>
                                            <tr>
                                                    <td style="text-align: center; vertical-align: middle;">
                                                        <input class="form-check-input" type="checkbox" name="barcode_check" id="" value="<?php echo $val['id'];?>" style="zoom:1.2">
                                                    </td>
                                                    <td><?php echo $val['username'];?></td>
                                                    <td><?php echo $val['ip'];?></td>
                                                    <td><?php echo $val['timestamp'];?></td>
                                            </tr>
                                        <?php } ?>
                                       
                                    </tbody>
                                </table>
                            </div>    
                        </div>  
                    </div>
                    <?php  if($_SESSION['privilege'] == 'admin'){?>
                            <input type="submit" value="<?php echo $text['Delete'];?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right;">
                    <?php } ?>
                </form>
            </div>

            <div id="iDas-Update_Setting" class="divMode" style="display: none;">
               <div class="row t2" style="padding-top: 30px">
                    <div class="col-3 t1">Current iDAS Version:</div>
                    <div class="col-3 t2">
                       
                        <input id="idas_software_version" name="idas_software_version" type="text" value="<?php echo $data['iDas_Vesion'];?>" style="height: 32px" class="form-control" value="" disabled>
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-3 t1">Match Controller Version:</div>
                    <div class="col-3 t2">
                        <input id="match_control_version" name="match_control_version" type="text" value="" style="height: 32px" class="form-control" disabled>
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-3 t1">Upload file:</div>
                    <div class="col-3 t2">
                        <input type="file" id="file-uploader" data-target="file-uploader" accept=".pack" class="form-control" style="height: 32px">
                    </div>
                </div>

                <div style="text-align: center;margin-top:50px;">
                    <input class="all-btn w3-submit w3-border w3-round-large" type="button" value="Update" onclick='idas_update();'>
                </div> 
            </div>
        </div>
    </div>        
</div>

<script>


function edit_password(){
    var new_password = document.getElementById('new_password').value;
    var comfirm_password = document.getElementById('comfirm_password').value;

    var device_id = <?php echo $data['controller_info']['device_id'];?>;

    if(new_password == comfirm_password){
        $.ajax({
            url: "?url=Settings/edit_password",
            method: "POST",
            data:{ 
                device_id: device_id,
                new_password: new_password

            },
            success: function(response) {
                alert(response);
                history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }else{
        //alert("請確認密碼");
        return false;
    }
}

function time_save(){
    var newTime = document.getElementById('newTime').value;
    var device_id = <?php echo $data['controller_info']['device_id'];?>;

    //console.log(newTime);
    if(newTime){
        $.ajax({
            url: "?url=Settings/edit_system_date",
            method: "POST",
            data:{ 
                device_id: device_id,
                newTime: newTime

            },
            success: function(response){
                alert(response);
                //history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });       
    }

}


function button_save_password_gust(){

    var device_id = <?php echo $data['controller_info']['device_id'];?>;

    var pass_guest1 = document.getElementById('new_password_guest').value;
    var pass_guest2 = document.getElementById('comfirm_password_guest').value;

    //正規化 密碼格式(1個英文+1個數字,長度:4)
    var pattern = /^(?=.*[A-Za-z])(?=.*\d).{4,}$/;
    if(pass_guest1 == pass_guest2 && pattern.test(pass_guest1)){
        $.ajax({
            url: "?url=Admins/EditGuestPwd",
            method: "POST",
            data:{ 
                device_id: device_id,
                new_password: pass_guest1

            },
            success: function(response) {
                alert(response);
                history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }else{
        alert('密碼格式不符合要求');
    }
    
}


/*const fileUploader = document.querySelector('#file-uploader');

function idas_update() {
    let ff = document.querySelector('#file-uploader').files;
    let bb = document.getElementById("file-uploader").files[0];
    let form = new FormData();
    form.append("file", bb)

    let url = '?url=Settings/iDas_Update';
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

        if (result.message != '') {
            Swal.fire({ // DB sync notice
                title: 'Error',
                text: result.message,
            })
        } else {
            Swal.fire('', '', 'success');
            setTimeout(function() {history.go(0)}, 2000);
        }
        document.getElementById("file-uploader").value = '';
        
    });
}*/

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
</script>    