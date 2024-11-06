<link rel="stylesheet" href="<?php echo URLROOT; ?>css/w3.css" type="text/css">
<link rel="stylesheet" type="text/css" href="<?php echo URLROOT; ?>css/target_torque_angle.css">
<link rel="stylesheet" href="<?php echo URLROOT; ?>/css/datatables.min.css">

<style type="text/css">
    .form-control
    {
        width: auto!important;
        display: initial!important;
    }
    .form-control.is-invalid
    {
        padding-right:inherit!important;
    }
    .is-invalid~.invalid-feedback
    {
        display: inline!important;
    }
.t1{font-size: 17px; margin: 5px 0px; display: flex; align-items: center;}
.t2{font-size: 17px; margin: 5px 0px;}
</style>

<div class="container-ms" id ="your_container_id">
    <div class="w3-text-white w3-center">
        <header>
            <h3><?php echo ($data['type'] == 'edit') ? $text['edit_seq'] : $text['new_seq']; ?></h3>
        </header>
    </div>
    <div style="display:none;">
        <input id="tool_max_torque" value="<?php echo $data['tools_info']['max_torque']; ?>">
        <input id="tool_min_torque" value="<?php echo $data['tools_info']['min_torque']; ?>">
        <input id="tool_max_rpm" value="<?php echo $data['tools_info']['max_rpm']; ?>">
        <input id="tool_min_rpm" value="<?php echo $data['tools_info']['min_rpm']; ?>">
    </div>


    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <div class="row t1">
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%"><?php echo $text['job_id']; ?> : </div>
                    <div class="col-1 t2">
                    	<input style="width: 100%;height:35px; font-size:18px;text-align: center; background-color: #DDDDDD" type="text" id="job_id" name="job_id" size="10" maxlength="20" value="<?php echo $data['job_id'];?>" disabled>
                    </div>
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%"><?php echo $text['seq_id'];?> : </div>
                    <div class="col-1 t2">
                        <input style="width: 100%;height:35px; font-size:18px;text-align: center; background-color: #DDDDDD" type="text" id="seq_id" name="seq_id" size="10" maxlength="20" value="<?php echo $data['seq_id'];?>" disabled>
                    </div>
                    

                    <div class="col t2" style=" text-align: right; ">
                        <div class="button-column">
                            <button id="return" onclick="history.go(-1);"><?php echo $text['return']; ?></button>
                        </div>
                    </div>
                </div>
                
            </div>

            <div class="container" style="max-width: none;background-color: #F2F1F1;">
                <div class="row">
                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3"><?php echo $text['seq_name'];?>:</div>
                            <div class="col-9">
                                <input id="SEQname" class="form-control" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['SEQname'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <hr style="border: 1px solid #ccc; width: 60%; margin: 20px 0;">

                        <div class="col-12 row t2 mt-3">
                            <div class="col-3"><?php echo $text['tightening_repeat'];?>:</div>
                            <div class="col-9">
                                <input id="seq_repeat" class="form-control" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['seq_repeat'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3"><?php echo $text['Timeout'];?> (sec):</div>
                            <div class="col-9">
                                <input id="timeout" lass="form-control"  value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['timeout'] : ''; ?>">(0-60)
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3"><?php echo $text['OK-Sequence'];?>:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ok_seq" id="ok_seq_off" value="0" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_seq'] == 0) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="ok_seq_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ok_seq" id="ok_seq_on" value="1"
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_seq'] == 1) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="ok_seq_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Sequence Stop:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ok_stop" id="seq_stop_off" value="0"
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_stop'] == 0) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="seq_stop_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ok_stop" id="seq_stop_on" value="1"
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_stop'] == 1) ? 'checked' : ''; ?>>
                                  <label class="form-check-label" for="seq_stop_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Reverse Count:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="unscrew_count_switch" id="unscrew_count_switch_off" value="0"
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_count_switch'] == 0) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="unscrew_count_switch_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="unscrew_count_switch" id="unscrew_count_switch_on" value="1" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_count_switch'] == 1) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="unscrew_count_switch_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3"><?php echo $text['NG_Stop'];?> (0-9):</div>
                            <div class="col-9">
                                <select id="ng_stop" class="form-select" style="font-size: 14px; width: 60px;">
                                <?php 
                                    for ($i = 0; $i <= 9; $i++) {
                                        echo '<option value="' . $i . '" ' . (($data['type'] == 'edit' && $data['sequences']['ng_stop'] == $i) ? 'selected' : '') . '>' . $i . '</option>';
                                    }
                                ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">NG Reverse:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ng_unscrew" id="ng_unscrew_off" value="0"
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['ng_unscrew'] == 0) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="ng_unscrew_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ng_unscrew" id="ng_unscrew_on" value="1" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['ng_unscrew'] == 1) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="ng_unscrew_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Accumulate Angle:</div>
                            <div class="col-9">
                                <!-- <input id="accumulate_angle" value="123456"> -->
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="accu_angle" id="accu_angle_off" value="0" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['accu_angle'] == 0) ? 'checked' : ''; ?>  >
                                  <label class="form-check-label" for="accu_angle_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="accu_angle" id="accu_angle_on" value="1" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['accu_angle'] == 1) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="accu_angle_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Angle Calculation (Step):</div>
                            <div class="col-9">
                                <?php if($data['type'] =="edit"){
                                    $digits = str_split($data['sequences']['Thread_Calcu']);                              
                                }else{


                                }?>
                               
                                <?php for ($i = 1; $i <= 5; $i++) { ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="Thread_Calcu_<?php echo $i; ?>" value="<?php echo $i; ?>" onchange="getCheckboxValue()" >
                                        <label class="form-check-label" for="Thread_Calcu_<?php echo $i; ?>"><?php echo $i; ?></label>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>

                        <hr style="border: 1px solid #ccc; width: 60%; margin: 20px 0;">

                    </div>
                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3">
                            <div class="col-4 fw-bolder"><?php echo $text['Reverse'];?></div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4">Reverse Mode:</div>
                            <div class="col-8">
                                <!-- <input id="reverse_mode" value="123456"> -->
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_auto" value="0" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 0) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="unscrew_mode_auto"><?php echo "Auto"; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_custom" value="1" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 1) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="unscrew_mode_custom"><?php echo "Custom"; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" id="div_speed">
                            <div class="col-4"><?php echo $text['reverse_rpm'];?> (rpm):</div>
                            <div class="col-8">
                                <input id="unscrew_rpm" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_rpm'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" id="div_torque_threshold">
                            <div class="col-4">Torque Threshold (kgf-cm):</div>
                            <div class="col-8">
                                <input id="unscrew_torque_threshold"class="form-control" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_torque_threshold'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" id="div_direction">
                            <div class="col-4">Direction:</div>
                            <div class="col-8">
                                <!-- <input id="direction" value="123456"> -->
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="unscrew_dir" id="unscrew_dir_cw" value="0"
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_dir'] == 0) ? 'checked' : ''; ?>  >
                                  <label class="form-check-label" for="direction_cw"><?php echo $text['CW']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="unscrew_dir" id="unscrew_dir_ccw" value="1" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_dir'] == 1) ? 'checked' : ''; ?>>
                                  <label class="form-check-label" for="direction_ccw"><?php echo $text['CCW']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" id="div_force">
                            <div class="col-4">Force (%):</div>
                            <div class="col-8">
                                <!-- <input id="force" value="123456"> -->
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_on" value="0"
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 0) ? 'checked' : ''; ?>  >
                                  <label class="form-check-label" for="force_on"><?php echo $text['switch_on']; ?></label>
                                  <input  id="unscrew_force" class="form-control" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_force'] : ''; ?>" style="width: 50%!important;min-width: 50%!important;display: inline-block!important;">
                                  <div class="invalid-feedback"></div>
                                </div>
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_unlimit" value="1" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 1) ? 'checked' : ''; ?>  >
                                  <label class="form-check-label" for="force_unlimit"><?php echo 'Unlimited'; ?></label>
                                </div>
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_off" value="2" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 2) ? 'checked' : ''; ?>  >
                                  <label class="form-check-label" for="force_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w3-center" style="margin: 20px 30px 0px 0;">
                <button style="height: 50px; width: 100px; font-size: 25px" id="button1" class="button button3"  onclick="<?php echo $data['type'] == 'new' ? 'save_sequence()' : 'edit_sequence()'; ?>">
                    <?php echo $text['save']; ?>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    var dataType = "<?php echo $data['type']; ?>";
    window.onload = function() {
        if (dataType === 'new') {
            //SEQ頁面 預設值
            document.getElementById("seq_repeat").value = 5;
            document.getElementById("timeout").value = 60;
            document.getElementById("ok_seq_on").checked = true;
            document.getElementById("seq_stop_on").checked = true;
            document.getElementById("unscrew_count_switch_on").checked = true;
            document.getElementById("ng_unscrew_on").checked = true;
            document.getElementById("accu_angle_on").checked = true;
            document.getElementById("unscrew_mode_auto").checked = true;
            document.getElementById("unscrew_rpm").value = 150;
            //document.getElementById("unscrew_torque_threshold").value =2.6;
            document.getElementById("unscrew_dir_cw").checked = true;
            document.getElementById("unscrew_mode_on").checked = true;

            for (let i = 1; i <= 5; i++) {
                document.getElementById("Thread_Calcu_" + i).checked = true;
            }
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
                url: '?url=Sequences/create_seq',
                type: 'POST',
                data: data,
                processData: false, 
                contentType: false, 
                success: function(response) {
                    // 處理成功回應
                    var responseData = JSON.parse(response);
                    console.log(responseData);
                    alertify.alert(responseData.res_type, responseData.res_msg, function() {
                        window.location.href = '../public/?url=Sequences/index/' + job_id; 
                    }); 
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
                    // 處理成功回應
                    var responseData = JSON.parse(response);
                    console.log(responseData);
                    alertify.alert(responseData.res_type, responseData.res_msg, function() {
                        window.location.href = '../public/?url=Sequences/index/' + job_id; 
                    }); 
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
                    case 0: total += 16; break; // 第1个
                    case 1: total += 8; break;  // 第2个
                    case 2: total += 4; break;  // 第3个
                    case 3: total += 2; break;  // 第4个
                    case 4: total += 1; break;  // 第5个
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
        //unscrew_torque_threshold
        let conditions = [
            { id: 'SEQname', pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/, min: null, max: null },
            { id: 'seq_repeat', pattern: /^\d{0,4}$/, min: 1, max: 99 },
            { id: 'timeout', pattern: /^\d{0,5}?$/, min: 0, max: 60 },
            { id: 'ng_stop', pattern: /^\d{0,5}?$/, min: 0, max: 9 },
            { id: 'unscrew_rpm', pattern: /^\d{1,3}$/, min: Tool_Min_RPM, max: Tool_Max_RPM },
            { id: 'unscrew_torque_threshold', pattern: /^\d{1,3}$/, min: 0, max: Tool_Max_Torque },
            { id: 'unscrew_force', pattern: /^\d{1,3}$/, min: 1, max: 100 },
        ];

        let isFormValid = true;
        conditions.forEach(function(input) {
            var element = document.getElementById(input.id);
            var value = element.value.trim();

            if(input.id != 'SEQname'){
                var nextSibling = element.nextElementSibling;
                if (nextSibling) {
                    nextSibling.innerHTML = input.min + ' ~ ' + input.max;
                }
            }

            if (value === "") {
                element.classList.add("is-invalid");
                isFormValid = false;
            } else if (!input.pattern.test(value)) {
                // element.value = "";
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


</script>




