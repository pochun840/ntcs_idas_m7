<?php require APPROOT . 'views/inc/header.php'; ?>

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

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <header>
            <h3><?php echo $text['normal_step']; ?></h3>
        </header>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <div class="row t1">
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%"><?php echo $text['job_id']; ?> : </div>
                    <div class="col-1 t2">
                    	<input style="width: 100%;height:35px; font-size:18px;text-align: center; background-color: #DDDDDD" type="text" id="job_id" name="job_id" size="10" maxlength="20" value="<?php echo $data['job_id'];?>" disabled>
                    </div>
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%">Sequence ID : </div>
                    <div class="col-1 t2">
                        <input style="width: 100%;height:35px; font-size:18px;text-align: center; background-color: #DDDDDD" type="text" id="seq_id" name="seq_id" size="10" maxlength="20" value="<?php echo $data['seq_id'];?>" disabled>
                    </div>
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%">Sequence Type : </div>
                    <div class="col-1 t2">
                        <input style="width: 100%;height:35px; font-size:18px;text-align: center; background-color: #DDDDDD" type="text" id="seq_type" name="seq_type" size="10" maxlength="20" value="<?php echo $data['seq_type'];?>" disabled>
                    </div>

                    <div class="col t2" style=" text-align: right; ">
                        <div class="button-column">
                            <button id="return" onclick="history.go(-1);"><?php echo $text['return']; ?></button>
                        </div>
                    </div>
                </div>
                <?php if($data['mode'] == 'edit'){ ?>
                <div style="display: none;">
                    <input type="" id="mode" value="<?php echo $data['mode']; ?>">
                    <input type="" id="data_seq_name" value="<?php echo $data['seq_data']['SEQname']; ?>">
                    <input type="" id="data_seq_repeat" value="<?php echo $data['seq_data']['seq_repeat']; ?>">
                    <input type="" id="data_ok_seq" value="<?php echo $data['seq_data']['ok_seq']; ?>">
                    <input type="" id="data_ok_stop" value="<?php echo $data['seq_data']['ok_stop']; ?>">
                    <input type="" id="data_unscrew_count" value="<?php echo $data['seq_data']['unscrew_count']; ?>">
                    <input type="" id="data_ng_stop" value="<?php echo $data['seq_data']['ng_stop']; ?>">
                    <input type="" id="data_ng_unscrew" value="<?php echo $data['seq_data']['ng_unscrew']; ?>">
                    <input type="" id="data_accu_angle" value="<?php echo $data['seq_data']['accu_angle']; ?>">
                    <input type="" id="data_unscrew_mode" value="<?php echo $data['seq_data']['unscrew_mode']; ?>">
                    <input type="" id="data_unscrew_force" value="<?php echo $data['seq_data']['unscrew_force']; ?>">
                    <input type="" id="data_unscrew_rpm" value="<?php echo $data['seq_data']['unscrew_rpm']; ?>">
                    <input type="" id="data_unscrew_dir" value="<?php echo $data['seq_data']['unscrew_dir']; ?>">
                    <input type="" id="data_unscrew_torque_threshold" value="<?php echo $data['seq_data']['unscrew_torque_threshold']; ?>">
                    <input type="" id="data_delay" value="<?php echo $data['seq_data']['delay']; ?>">
                </div>
                <?php } ?>
            </div>

            <div class="container" style="max-width: none;background-color: #F2F1F1;">
                <div class="row">
                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Sequence Name:</div>
                            <div class="col-9">
                                <input id="seq_name" value="">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Tightening Repeat:</div>
                            <div class="col-9">
                                <input id="tightening_repeat" value="1">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Timeout (sec):</div>
                            <div class="col-9">
                                <input id="timeout" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">OK Sequence:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ok_seq_option" id="ok_seq_off" value="0">
                                  <label class="form-check-label" for="ok_seq_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ok_seq_option" id="ok_seq_on" value="1">
                                  <label class="form-check-label" for="ok_seq_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Sequence Stop:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="seq_stop_option" id="seq_stop_off" value="0">
                                  <label class="form-check-label" for="seq_stop_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="seq_stop_option" id="seq_stop_on" value="1">
                                  <label class="form-check-label" for="seq_stop_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Reverse Count:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="reverse_count_option" id="reverse_count_off" value="0">
                                  <label class="form-check-label" for="reverse_count_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="reverse_count_option" id="reverse_count_on" value="1">
                                  <label class="form-check-label" for="reverse_count_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">NG Stop (0-9):</div>
                            <div class="col-9">
                                <input id="ng_stop" type="number" max=9 min=0 value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">NG Reverse:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ng_reverse_option" id="ng_reverse_off" value="0">
                                  <label class="form-check-label" for="ng_reverse_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="ng_reverse_option" id="ng_reverse_on" value="1">
                                  <label class="form-check-label" for="ng_reverse_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Accumulate Angle:</div>
                            <div class="col-9">
                                <!-- <input id="accumulate_angle" value="123456"> -->
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="accumulate_angle_option" id="accumulate_angle_off" value="0">
                                  <label class="form-check-label" for="accumulate_angle_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="accumulate_angle_option" id="accumulate_angle_on" value="1">
                                  <label class="form-check-label" for="accumulate_angle_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Angle Calculation (Step):</div>
                            <div class="col-9">
                                <!-- <input id="angle_calculation" value="123456"> -->
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="inlineCheckbox1" value="1">
                                    <label class="form-check-label" for="inlineCheckbox1">1</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="inlineCheckbox2" value="2">
                                    <label class="form-check-label" for="inlineCheckbox2">2</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="inlineCheckbox3" value="3">
                                    <label class="form-check-label" for="inlineCheckbox3">3</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="inlineCheckbox4" value="4">
                                    <label class="form-check-label" for="inlineCheckbox4">4</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="inlineCheckbox5" value="5">
                                    <label class="form-check-label" for="inlineCheckbox5">5</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3">
                            <div class="col-4 fw-bolder">Reverse</div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4">Reverse Mode:</div>
                            <div class="col-8">
                                <!-- <input id="reverse_mode" value="123456"> -->
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="reverse_mode_option" id="reverse_mode_off" value="0">
                                  <label class="form-check-label" for="reverse_mode_off"><?php echo $text['auto']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="reverse_mode_option" id="reverse_mode_on" value="1">
                                  <label class="form-check-label" for="reverse_mode_on"><?php echo $text['custom']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" id="div_speed">
                            <div class="col-4">Speed (rpm):</div>
                            <div class="col-8">
                                <input id="speed" value="150">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" id="div_torque_threshold">
                            <div class="col-4">Torque Threshold (kgf-cm):</div>
                            <div class="col-8">
                                <input id="torque_threshold" value="123456">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" id="div_direction">
                            <div class="col-4">Direction:</div>
                            <div class="col-8">
                                <!-- <input id="direction" value="123456"> -->
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="direction_option" id="direction_cw" value="1">
                                  <label class="form-check-label" for="direction_cw"><?php echo $text['CW']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="direction_option" id="direction_ccw" value="0">
                                  <label class="form-check-label" for="direction_ccw"><?php echo $text['CCW']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" id="div_force">
                            <div class="col-4">Force (%):</div>
                            <div class="col-8">
                                <!-- <input id="force" value="123456"> -->
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio" name="force_option" id="force_on" value="1">
                                  <label class="form-check-label" for="force_on"><?php echo $text['switch_on']; ?></label>
                                  <input class="" id="force_number" style=" width: 50%; height: 25px;min-width: 20px; ">
                                </div>
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio" name="force_option" id="force_unlimit" value="2">
                                  <label class="form-check-label" for="force_unlimit"><?php echo 'Unlimited'; ?></label>
                                </div>
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio" name="force_option" id="force_off" value="0">
                                  <label class="form-check-label" for="force_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w3-center" style="margin: 20px 30px 0px 0;">
                <button style="height: 50px; width: 100px; font-size: 25px" id="button1" class="button button3" onclick="save_sequence();"><?php echo $text['save']; ?></button>
            </div>
        </div>
    </div>
</div>

<script>
	$(document).ready(function () {

        let mode = document.getElementById('mode').value;

        if(mode == 'new'){
            //帶入預設值
            $('input[name=direction_option]:checked').val()
            document.getElementById("ok_seq_off").checked = true;
            document.getElementById("seq_stop_off").checked = true;
            document.getElementById("reverse_count_off").checked = true;
            document.getElementById("ng_stop").value = 0;
            document.getElementById("ng_reverse_on").checked = true;
            document.getElementById("accumulate_angle_on").checked = true;
            document.getElementById("reverse_mode_off").checked = true;
            document.getElementById("speed").value = 300;
            document.getElementById("torque_threshold").value = 0;
            document.getElementById("direction_ccw").checked = true;
            document.getElementById("force_on").checked = true;
            document.getElementById("force_number").value = 110;
            document.getElementById("tightening_repeat").value = 1;
            document.getElementById("timeout").value = 60;
        }

        if(mode == 'edit'){
            //帶入資料
            document.getElementById("seq_name").value = document.getElementById("data_seq_name").value
            document.getElementById("tightening_repeat").value = document.getElementById("data_seq_repeat").value
            document.getElementById("timeout").value = document.getElementById("data_delay").value
            document.getElementById("ng_stop").value = document.getElementById("data_ng_stop").value
            document.getElementById("speed").value = document.getElementById("data_unscrew_rpm").value
            document.getElementById("torque_threshold").value = document.getElementById("data_unscrew_torque_threshold").value
            document.getElementById("force_number").value = document.getElementById("data_unscrew_force").value

            let ok_seq = document.getElementById("data_ok_seq").value;
            let seq_stop = document.getElementById("data_ok_stop").value;
            let reverse_count = document.getElementById("data_unscrew_count").value;
            let ng_reverse = document.getElementById("data_ng_unscrew").value;
            let accu_angle = document.getElementById("data_accu_angle").value;
            let reverse_mode = document.getElementById("data_unscrew_mode").value;
            let revers_direction = document.getElementById("data_unscrew_dir").value;

            if(ok_seq == 0){
                document.getElementById("ok_seq_off").checked = true;
            }else{
                document.getElementById("ok_seq_on").checked = true;
            }
            if(seq_stop == 0){
                document.getElementById("seq_stop_off").checked = true;
            }else{
                document.getElementById("seq_stop_on").checked = true;
            }
            if(reverse_count == 0){
                document.getElementById("reverse_count_off").checked = true;
            }else{
                document.getElementById("reverse_count_on").checked = true;
            }
            if(ng_reverse == 0){
                document.getElementById("ng_reverse_off").checked = true;
            }else{
                document.getElementById("ng_reverse_on").checked = true;
            }
            if(accu_angle == 0){
                document.getElementById("accumulate_angle_off").checked = true;
            }else{
                document.getElementById("accumulate_angle_on").checked = true;
            }
            if(reverse_mode == 0){
                document.getElementById("reverse_mode_off").checked = true;
            }else{
                document.getElementById("reverse_mode_on").checked = true;
            }
            if(revers_direction == 0){
                document.getElementById("direction_ccw").checked = true;
            }else{
                document.getElementById("direction_cw").checked = true;
            }
            
        }

        //主動觸發change事件
        const change_event = new Event("change");
        document.querySelector('input[name="reverse_mode_option"]').dispatchEvent(change_event)
	});


    function save_sequence() {
        let job_id = document.getElementById("job_id").value;
        let seq_id = document.getElementById("seq_id").value;
        let seq_type = 0;
        
        let data = new FormData()
        data.append("job_id", job_id)
        data.append("seq_id", seq_id)
        data.append("seq_type", seq_type)
        data.append("seq_name", document.getElementById("seq_name").value)
        data.append("tightening_repeat", document.getElementById("tightening_repeat").value)
        data.append("timeout", document.getElementById("timeout").value)
        data.append("ok_seq", $('input[name=ok_seq_option]:checked').val())
        data.append("seq_stop", $('input[name=seq_stop_option]:checked').val())
        data.append("reverse_count_option", $('input[name=reverse_count_option]:checked').val())
        data.append("ng_stop", document.getElementById("ng_stop").value)
        data.append("ng_reverse_option", $('input[name=ng_reverse_option]:checked').val())
        data.append("accumulate_angle_option", $('input[name=accumulate_angle_option]:checked').val())
        
        data.append("reverse_mode", $('input[name=reverse_mode_option]:checked').val())
        data.append("speed", document.getElementById("speed").value)
        data.append("torque_threshold", document.getElementById("torque_threshold").value)
        data.append("direction", $('input[name=direction_option]:checked').val())
        data.append("force_option", $('input[name=force_option]:checked').val())
        data.append("force_number", document.getElementById("force_number").value)

        // console.log( getCheckboxValue() )
        let angle_calculation_data = getCheckboxValue()
        data.append("angle_calculation", "123")


        console.log(data)

        for (var pair of data.entries()) {
            console.log(pair[0]+ ', ' + pair[1]); 
        }


        let check = true

        if(check){
            $.ajax({
              type: "POST",
              url: "?url=Sequences/create_seq",
              data: data,
              dataType: "json",
              contentType: false, //required
              processData: false, // required
              beforeSend: function() {
                $('#overlay').removeClass('hidden');
              },
            }).done(function (data) {//成功且有回傳值才會執行
                $('#overlay').addClass('hidden');
              console.log(data);
              location.href = "?url=Sequences/index/"+job_id;
            }).fail(function() {
                // history.go(0);
                // document.getElementById('activate_key').value = ''
            });
        }else{
            $('#new_seq_save').prop('disabled', false);
        }

    }

    function getCheckboxValue() {  
      
        var l1 = document.getElementById("inlineCheckbox1");  
        var l2 = document.getElementById("inlineCheckbox2");  
        var l3 = document.getElementById("inlineCheckbox3");  
        var l4 = document.getElementById("inlineCheckbox4");  
        var l5 = document.getElementById("inlineCheckbox5");  
         
        var res = "";   
        if (l1.checked == true){  
            var pl1 = document.getElementById("inlineCheckbox1").value;  
            res = pl1 + ",";   
        }   
        if (l2.checked == true){  
            var pl2 = document.getElementById("inlineCheckbox2").value;  
            res = res + pl2 + ",";   
        }
        if (l3.checked == true){  
            var pl3 = document.getElementById("inlineCheckbox3").value;  
            res = res + pl3 + ",";   
        }
        if (l4.checked == true){  
            var pl4 = document.getElementById("inlineCheckbox4").value;  
            res = res + pl4 + ",";   
        }
        if (l5.checked == true){  
            var pl5 = document.getElementById("inlineCheckbox5").value;  
            res = res + pl5 + ",";   
        }

        return res;  
    }  

    //監控option 變化
    document.querySelectorAll('input[name="reverse_mode_option"]').forEach(function(radio) {
      radio.addEventListener('change', function() {
        // 获取选中的radio的值
        var selectedValue = document.querySelector('input[name="reverse_mode_option"]:checked').value;
        // 根据值来显示或隐藏特定的div
        if (selectedValue === '1') {
          $("#div_speed *").removeAttr("disabled")
          $("#div_torque_threshold *").removeAttr("disabled")
          $("#div_direction *").removeAttr("disabled")
          $("#div_force *").removeAttr("disabled")
        } else {
          $("#div_speed *").attr("disabled", "disabled").off('click');
          $("#div_torque_threshold *").attr("disabled", "disabled").off('click');
          $("#div_direction *").attr("disabled", "disabled").off('click');
          $("#div_force *").attr("disabled", "disabled").off('click');
        }
      });
    });


</script>

<?php if($_SESSION['privilege'] != 'admin'){ ?>
<script>
  $(document).ready(function () {
    disableAllButtonsAndInputs()
    document.getElementById("return").disabled = false;
  });
</script>
<?php } ?>


<?php require APPROOT . 'views/inc/footer.php'; ?>