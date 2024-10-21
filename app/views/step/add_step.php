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
                        <input style="width: 100%;height:35px; font-size:18px;text-align: center; background-color: #DDDDDD" type="text" id="JOBID" name="JOBID" size="10" maxlength="20" value="<?php echo $data['JOBID'];?>" disabled>
                    </div>
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%">Sequence ID : </div>
                    <div class="col-1 t2">
                        <input style="width: 100%;height:35px; font-size:18px;text-align: center; background-color: #DDDDDD" type="text" id="SEQID" name="SEQID" size="10" maxlength="20" value="<?php echo $data['SEQID'];?>" disabled>
                    </div>
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%">Step ID : </div>
                    <div class="col-1 t2">
                        <input style="width: 100%;height:35px; font-size:18px;text-align: center; background-color: #DDDDDD" type="text" id="seq_type" name="seq_type" size="10" maxlength="20" value="<?php echo $data['StepSelect'];?>" disabled>
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
                </div>
                <?php } ?>
            </div>

            <div class="container" style="max-width: none;background-color: #F2F1F1;">
                <div class="row">
                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Step Name:</div>
                            <div class="col-9">
                                <input id="STEPname" class="form-control form-control-sm" value="">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Target Type:</div>
                            <div class="col-9">
                                <select id="StepOption" class="form-control form-control-sm">
                                    <option value="0">Torque</option>
                                    <option value="1">Angle</option>
                                    <option value="2">Time</option>
                                    <!--<option value="3">Tapping`</option>-->
                                </select>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Target Torque (kgf-cm):</div>
                            <div class="col-9">
                                <input id="StepTorque" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Hi Toque (kgf-cm):</div>
                            <div class="col-9">
                                <input id="StepHiTorque" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Lo Toque (kgf-cm):</div>
                            <div class="col-9">
                                <input id="StepLoTorque" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-12">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="checkbox" name="interrupt_alarm" id="monitoring_torque_window" value="0">
                                  <label class="form-check-label" for="monitoring_torque_window"><?php echo 'Monitoring torque by window:'; ?></label>
                                </div>
                                <div class="ps-5" style="display:inline-block;">
                                    <label class="form-check-label" for="monitor_torque_upper"><?php echo 'Upper(%)'; ?></label>
                                    <input id="monitor_torque_upper" class="form-control form-control-sm" style=" width: 40px !important; ">
                                    <label class="form-check-label ps-3" for="monitor_torque_upper"><?php echo 'Lower(%)'; ?></label>
                                    <input id="monitor_torque_lower" class="form-control form-control-sm" style=" width: 40px !important; ">
                                </div>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Hi Angle (degree):</div>
                            <div class="col-9">
                                <input id="hi_angle" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Lo Angle (degree):</div>
                            <div class="col-9">
                                <input id="lo_angle" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-12">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="checkbox" name="interrupt_alarm" id="monitoring_angle_window" value="0">
                                  <label class="form-check-label" for="monitoring_angle_window"><?php echo 'Monitoring angle by window:'; ?></label>
                                </div>
                                <div class="ps-5" style="display:inline-block;">
                                    <label class="form-check-label" for="monitor_angle_upper"><?php echo 'Upper(%)'; ?></label>
                                    <input id="monitor_angle_upper" class="form-control form-control-sm" style=" width: 40px !important; ">
                                    <label class="form-check-label ps-3" for="monitor_angle_upper"><?php echo 'Lower(%)'; ?></label>
                                    <input id="monitor_angle_lower" class="form-control form-control-sm" style=" width: 40px !important; ">
                                </div>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Interrupt Alarm:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="interrupt_alarm" id="interrupt_alarm_off" value="0">
                                  <label class="form-check-label" for="interrupt_alarm_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="interrupt_alarm" id="interrupt_alarm_on" value="1">
                                  <label class="form-check-label" for="interrupt_alarm_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Over Angle Stop:</div>
                            <div class="col-9">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="over_angle_stop" id="over_angle_stop_off" value="0">
                                  <label class="form-check-label" for="over_angle_stop_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="over_angle_stop" id="over_angle_stop_on" value="1">
                                  <label class="form-check-label" for="over_angle_stop_on"><?php echo $text['switch_on']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Direction:</div>
                            <div class="col-9">
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
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Delay (sec):</div>
                            <div class="col-9">
                                <input id="delay_sec" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Run Down Speed (rpm):</div>
                            <div class="col-9">
                                <input id="rpm" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">K Value:</div>
                            <div class="col-9">
                                <input id="k_value" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4">Join Offset (kgf-cm):</div>
                            <div class="col-8">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="join_offset_option" id="join_offset_plus" value="0">
                                  <label class="form-check-label" for="join_offset_plus"><?php echo 'Plus'; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="join_offset_option" id="join_offset_minus" value="1">
                                  <label class="form-check-label" for="join_offset_minus"><?php echo 'Minus'; ?></label>
                                  <input id="hi_angle" class="form-control form-control-sm" value="">
                                </div>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4">Threshold Mode (kgf-cm):</div>
                            <div class="col-8">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="threshold_mode_option" id="threshold_mode_off" value="0">
                                  <label class="form-check-label" for="threshold_mode_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="threshold_mode_option" id="threshold_mode_torque" value="1">
                                  <label class="form-check-label" for="threshold_mode_torque"><?php echo $text['torque']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="threshold_mode_option" id="threshold_mode_angle" value="2">
                                  <label class="form-check-label" for="threshold_mode_angle"><?php echo $text['angle']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4">Torque Threshold (kgf-cm):</div>
                            <div class="col-8">
                                <input id="torque_threshold" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4">Downshift:</div>
                            <div class="col-8">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="downshift_mode_option" id="downshift_mode_off" value="0">
                                  <label class="form-check-label" for="downshift_mode_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="downshift_mode_option" id="downshift_mode_torque" value="1">
                                  <label class="form-check-label" for="downshift_mode_torque"><?php echo $text['torque']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="downshift_mode_option" id="downshift_mode_angle" value="2">
                                  <label class="form-check-label" for="downshift_mode_angle"><?php echo $text['angle']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" >
                            <div class="col-4">Downshift Torque (kgf-cm):</div>
                            <div class="col-8">
                                <input id="downshift_torque" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" >
                            <div class="col-4">Downshift Speed (rpm):</div>
                            <div class="col-8">
                                <input id="downshift_speed" class="form-control form-control-sm" value="0">
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="w3-center" style="margin: 10px 30px 0px 0;">
                <button style="height: 50px; width: 100px; font-size: 25px" id="button1" class="button button3" onclick="save_sequence();"><?php echo $text['save']; ?></button>
            </div>
        </div>
    </div>
</div>

<script>
 var dataType = "<?php echo $data['type']; ?>";
 window.onload = function() {
        if (dataType === 'new') {
      
            // document.getElementById("seq_repeat").value = 5;
            // document.getElementById("timeout").value = 60;
            // document.getElementById("ok_seq_on").checked = true;
            // document.getElementById("seq_stop_on").checked = true;
            // document.getElementById("unscrew_count_switch_on").checked = true;
            // document.getElementById("ng_unscrew_on").checked = true;
            // document.getElementById("accu_angle_on").checked = true;
            // document.getElementById("unscrew_mode_auto").checked = true;
            // document.getElementById("unscrew_rpm").value = 150;
            // document.getElementById("unscrew_torque_threshold").value = 12345;
        }
    };

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