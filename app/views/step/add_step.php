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
                        <input style="width: 100%;height:35px; font-size:18px;text-align: center; background-color: #DDDDDD" type="text" id="StepSelect" name="StepSelect" size="10" maxlength="20" value="<?php echo $data['StepSelect'];?>" disabled>
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
                                  <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_0" value="0">
                                  <label class="form-check-label" for="monitoring_torque_window"><?php echo 'Monitoring torque by window:'; ?></label>
                                </div>
                                <div class="ps-5" style="display:inline-block;">
                                    <label class="form-check-label" for="monitor_torque_upper"><?php echo 'Upper(%)'; ?></label>
                                    <input id="StepLimiHi" class="form-control form-control-sm" style=" width: 40px !important; ">
                                    <label class="form-check-label ps-3" for="monitor_torque_upper"><?php echo 'Lower(%)'; ?></label>
                                    <input id="StepLimiLo" class="form-control form-control-sm" style=" width: 40px !important; ">
                                </div>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Hi Angle (degree):</div>
                            <div class="col-9">
                                <input id="StepHiAngle" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Lo Angle (degree):</div>
                            <div class="col-9">
                                <input id="StepLoAngle" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-12">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_1" value="1">
                                  <label class="form-check-label" for="monitoring_angle_window"><?php echo 'Monitoring angle by window:'; ?></label>
                                </div>
                                <div class="ps-5" style="display:inline-block;">
                                    <label class="form-check-label" for="monitor_angle_upper"><?php echo 'Upper(%)'; ?></label>
                                    <input id="StepLimiHi" class="form-control form-control-sm" style=" width: 40px !important; ">
                                    <label class="form-check-label ps-3" for="monitor_angle_upper"><?php echo 'Lower(%)'; ?></label>
                                    <input id="StepLimiLo" class="form-control form-control-sm" style=" width: 40px !important; ">
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
                                  <input class="form-check-input" type="radio" name="StepDirection" id="StepDirection_cw" value="0">
                                  <label class="form-check-label" for="direction_cw"><?php echo $text['CW']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="StepDirection" id="StepDirection_ccw" value="1">
                                  <label class="form-check-label" for="direction_ccw"><?php echo $text['CCW']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Delay (sec):</div>
                            <div class="col-9">
                                <input id="StepDelay" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3">Run Down Speed (rpm):</div>
                            <div class="col-9">
                                <input id="StepRPM" class="form-control form-control-sm" value="0">
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
                                  <input class="form-check-input" type="radio" name="StepTorqueOffset" id="join_offset_plus" value="0">
                                  <label class="form-check-label" for="join_offset_plus"><?php echo 'Plus'; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="StepTorqueOffsetn" id="join_offset_minus" value="1">
                                  <label class="form-check-label" for="join_offset_minus"><?php echo 'Minus'; ?></label>
                                  <input id="StepTorqueOffsetSign" class="form-control form-control-sm" value="">
                                </div>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4">Threshold Mode (kgf-cm):</div>
                            <div class="col-8">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_off" value="0">
                                  <label class="form-check-label" for="threshold_mode_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_torque" value="1">
                                  <label class="form-check-label" for="threshold_mode_torque"><?php echo $text['torque']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_angle" value="2">
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
                                  <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_off" value="0">
                                  <label class="form-check-label" for="downshift_mode_off"><?php echo $text['switch_off']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_torque" value="1">
                                  <label class="form-check-label" for="downshift_mode_torque"><?php echo $text['torque']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_angle" value="2">
                                  <label class="form-check-label" for="downshift_mode_angle"><?php echo $text['angle']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" >
                            <div class="col-4">Downshift Torque (kgf-cm):</div>
                            <div class="col-8">
                                <input id="StepTorqueDownShift" class="form-control form-control-sm" value="0">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" >
                            <div class="col-4">Downshift Speed (rpm):</div>
                            <div class="col-8">
                                <input id="StepRPMDownShift" class="form-control form-control-sm" value="0">
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="w3-center" style="margin: 10px 30px 0px 0;">
                <button style="height: 50px; width: 100px; font-size: 25px" id="button1" class="button button3" onclick="save_seq();"><?php echo $text['save']; ?></button>
            </div>
        </div>
    </div>
</div>

<script>
 var dataType = "<?php echo $data['type']; ?>";
 window.onload = function() {
        if (dataType === 'new') {
      
            document.getElementById("interrupt_alarm_off").checked = true;
            document.getElementById("over_angle_stop_off").checked = true;
            document.getElementById("StepDirection_cw").checked = true;
            document.getElementById("join_offset_plus").checked = true;
            document.getElementById("threshold_mode_torque").checked = true;
            document.getElementById("downshift_mode_torque").checked = true;

        }
    };

    function getCheckboxValue() {
    let selectedValue = null; // 用来保存选中的复选框的值

    // 遍历所有复选框 (ID 可能为 Thread_Calcu_1, Thread_Calcu_2, ..., Thread_Calcu_5)
    for (var i = 0; i <= 1; i++) {
        var checkbox = document.getElementById("StepMoniByWin_" + i);

        if (checkbox.checked) {
            selectedValue = checkbox.value;  // 保存选中的复选框的值
            break;  // 找到一个选中的复选框后，跳出循环
        }
    }

    // 如果有复选框被选中，弹出它的值
    if (selectedValue !== null) {
        //alert("选中的值: " + selectedValue);
    } else {
       //alert("没有选中任何复选框");
    }

    return selectedValue;
}



    function save_seq() {

        let data = new FormData();

        let job_id = document.getElementById("JOBID").value;
        let seq_id = document.getElementById("SEQID").value;
        let StepSelect = document.getElementById("StepSelect").value;
        let STEPname = document.getElementById("STEPname").value;
        let StepOption = document.getElementById("StepOption").value;
        let StepTorque = document.getElementById("StepTorque").value;
        let StepHiTorque = document.getElementById("StepHiTorque").value;
        let StepMoniByWin = getCheckboxValue();
        let StepLimiHi = document.getElementById("StepLimiHi").value;
        let StepLimiLo = document.getElementById("StepLimiLo").value;
        let interrupt_alarm = document.querySelector('input[name="interrupt_alarm"]:checked');
        let over_angle_stop = document.querySelector('input[name="over_angle_stop"]:checked');
        let StepDirection   = document.querySelector('input[name="StepDirection"]:checked');
        let StepDelay = document.getElementById("StepDelay").value;
        let StepRPM = document.getElementById("StepRPM").value;

        data.append("JOBID", job_id);
        data.append("SEQID", seq_id);
        data.append("StepSelect",StepSelect);
        data.append("STEPname",STEPname);
        data.append("StepOption",StepOption);
        data.append("StepTorque",StepTorque);
        data.append("StepMoniByWin",StepMoniByWin);
        data.append("StepLimiHi",StepLimiHi);
        data.append("StepLimiLo",StepLimiLo);
        data.append("interrupt_alarm", interrupt_alarm  ? interrupt_alarm.value : null);
        data.append("over_angle_stop", over_angle_stop  ? over_angle_stop.value : null);
        data.append("StepDirection",StepDirection ? StepDirection.value : null);
        data.append("StepDelay",StepDelay);
        data.append("StepRPM",StepRPM);



    }


    function handleSingleCheckboxSelection(name) {
        let selectedValue = null;  // 存储选中的复选框值

        // 选择所有具有相同 name 的复选框
        document.querySelectorAll(`input[name="${name}"]`).forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // 当当前复选框被选中时
                if (this.checked) {
                    selectedValue = this.value; // 保存选中的值

                    // 打印当前选中的复选框的值
                    alert("选中的值: " + this.value);

                    // 取消其他复选框的选中状态
                    document.querySelectorAll(`input[name="${name}"]`).forEach(otherCheckbox => {
                        if (otherCheckbox !== this) {
                            otherCheckbox.checked = false;
                        }
                    });
                }
            });
        });

        // 返回当前选中的值
        return selectedValue;
    }



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