

<link rel="stylesheet" type="text/css" href="<?php echo URLROOT; ?>css/add_seq_step.css">
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
</style>

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo ($data['type'] == 'edit') ? $text['edit_step']  : $text['add_step']; ?></h3></td>
            </tr>
        </table>
    </div>

   
    <div style="display:none;">
        <input id="tool_max_torque" value="<?php echo $data['tools_info']['max_torque']; ?>">
        <input id="tool_max_torque_diff" value="<?php echo $data['tools_info']['tool_high_torque']; ?>">
        <input id="tool_min_torque" value="<?php echo $data['tools_info']['min_torque']; ?>">
        <input id="tool_high_torque" value="<?php echo $data['tools_info']['tool_high_torque']; ?>">
        <input id="tool_low_torque" value="<?php echo $data['tools_info']['tool_low_torque']; ?>">
        <input id="tool_max_rpm" value="<?php echo $data['tools_info']['max_rpm']; ?>">
        <input id="tool_min_rpm" value="<?php echo $data['tools_info']['min_rpm']; ?>">
        <input id="step_torque_unit" value="<?php echo $data['step_torque_unit'];?>"> 
    </div>

    <div class="main-content">
        <div class="center-content">
             <div class="topnav">
                <label style="font-size:20px;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="JOBID" name="JOBID" size="8" maxlength="20" value="<?php echo $data['JOBID'];?>" disabled
                style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:20px;color: #000; padding-left: 2%" for="seq_id"><?php echo $text['seq_id'];?> :</label>&nbsp;
                <input type="text" id="SEQID" name="SEQID" size="8" maxlength="20" value="<?php echo $data['SEQID'];?>" disabled
                style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:20px;color: #000; padding-left: 2%" for="step_id"><?php echo $text['step_id'];?> :</label>&nbsp;
                <input type="text" id="StepSelect" name="StepSelect" size="8" maxlength="20" value="<?php echo $data['StepSelect'];?>" disabled
                style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <button id="back_btn" type="button" onclick="history.go(-1);"><?php echo $text['return']; ?></button>

                <?php if($data['type'] == 'edit'){ ?>
                <div style="display: none;">
                    <input type="" id="mode" value="<?php echo $data['mode']; ?>">
                </div>
                <?php } ?>
            </div>

            <div class="new-container">
                <div class="scrollbar" id="style-newStep">
                    <div class="force-overflow">
                        <div class="row" style="background-color: #F2F1F1;">
                            <div class="col-md-6 t2">
                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['step_name'];?>:</div>
                                    <div class="col-9">
                                        <input id="STEPname" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['STEPname'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['target_type'];?>:</div>
                                    <div class="col-9">
                                        <select id="StepOption" onchange="updateLabel()" style="width: 195px">
                                            <?php 
                                                    $options = array(
                                                        0 => $text['Torque'],
                                                        1 => $text['Angle'],
                                                        //2 => $text['Time']
                                                    );

                                                    foreach ($options as $value => $label) {
                                                        echo '<option value="' . $value . '" ' . (($data['type'] == 'edit' && $data['step']['StepOption'] == $value) ? 'selected' : '') . '>' . $label . '</option>';
                                                    }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <hr class="hr">

                                <div class="col-12 row t2 mt-3">
                                    <?php
                                        $type = $data['type'];
                                        $stepOption = ($type === 'edit') ? (int)($data['step']['StepOption'] ?? 0) : 0;
                                        $torqueUnitName = $text[$data['torque_unit']] ?? $data['torque_unit'];

                                        // 標籤
                                        $label = ($stepOption === 0) 
                                            ? $text['Target_Torque'] . " ($torqueUnitName):" 
                                            : $text['Target_Angle'] . " :";

                                        // 控制欄位顯示
                                        $torqueDisplay = ($stepOption === 0) ? 'block' : 'none';
                                        $angleDisplay  = ($stepOption === 1) ? 'block' : 'none';
                                    ?>

                                    <div class="col-12 row t2 mt-3">
                                        <div class="col-3" id="targetLabel"><?php echo $label; ?></div>
                                        <div class="col-9" id="StepTorque_item" style="display: <?php echo $torqueDisplay; ?>;">
                                            <input id="StepTorque" class="form-control form-control-sm"
                                                value="<?php echo ($type === 'edit') ? htmlspecialchars($data['step']['StepTorque']) : ''; ?>">
                                            <div class="invalid-feedback"></div>
                                        </div>

                                        <div class="col-9" id="StepAngle_item" style="display: <?php echo $angleDisplay; ?>;">
                                            <input id="StepAngle" class="form-control form-control-sm"
                                                value="<?php echo ($type === 'edit') ? htmlspecialchars($data['step']['StepAngle']) : ''; ?>">
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="hr" />

                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['High_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                    <div class="col-9">
                                        <input id="StepHiTorque"  type="number" step="0.0001" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepHiTorque'] : ''; ?>" >
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['Low_Torque']?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                    <div class="col-9">
                                        <input id="StepLoTorque" type="number" step="0.0001" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepLoTorque'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3" id='show_tor' >
                                    <div class="col-12">
                                        <div class="form-check form-check-inline" >
                                        <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_0" value="0" onchange="getCheckboxValue()"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="monitoring_torque_window"><?php echo $text['Monitor torque by window']; ?>:</label>
                                        </div>
                                        <div class="ps-5" style="display:inline-block;">
                                            <label class="form-check-label" for="monitor_torque_upper"><?php echo $text['Upper_text'].'(%)'; ?></label>
                                            <input id="step_limit_hi_tor"  name= '' class="form-control form-control-sm" style=" width: 40px !important;" value= '<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 0 && $data['step']['StepLimiHi']) ? $data['step']['StepLimiHi'] : ''; ?>'>
                                            <div class="invalid-feedback"></div>
                                            <label class="form-check-label ps-3" for="monitor_torque_lower"><?php echo $text['Lower_text'].'(%)'; ?></label>
                                            <input id="step_limit_lo_tor" name= '' class="form-control form-control-sm" style=" width: 40px !important;" value= '<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 0 && $data['step']['StepLimiLo']) ? $data['step']['StepLimiLo'] : ''; ?>'>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>

                                 <hr class="hr">

                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['High_Angle'];?>:</div>
                                    <div class="col-9">
                                        <input id="StepHiAngle" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepHiAngle'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['Low_Angle'];?>:</div>
                                    <div class="col-9">
                                        <input id="StepLoAngle" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepLoAngle'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3" id='show_ang' >
                                    <div class="col-12" >
                                        <div class="form-check form-check-inline" >
                                        <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_1" value="1" onchange="getCheckboxValue()" 
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="monitoring_angle_window"><?php echo $text['Monitor angle by window']; ?></label>
                                        </div>
                                        <div class="ps-5" style="display:inline-block;">
                                            <label class="form-check-label" for="monitor_angle_upper"><?php echo $text['Upper_text'].'(%)'; ?></label>
                                            <input id="step_limit_hi_ang"  class="form-control form-control-sm" style=" width: 40px !important; " value ='<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 1 && $data['step']['StepLimiHi']) ? $data['step']['StepLimiHi'] : ''; ?>'>
                                            <div class="invalid-feedback"></div>
                                            <label class="form-check-label ps-3" for="monitor_angle_upper"><?php echo $text['Lower_text'].'(%)'; ?></label>
                                            <input id="step_limit_lo_ang"  class="form-control form-control-sm" style=" width: 40px !important; " value ='<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 1 && $data['step']['StepLimiLo']) ? $data['step']['StepLimiLo'] : ''; ?>'>
                                            <div class="invalid-feedback"></div>
                                        </div>
                                    </div>
                                </div>

                                <hr class="hr">

                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['Interrupt_Alarm'];?>:</div>
                                    <div class="col-9">
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="interrupt_alarm" id="interrupt_alarm_off" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['InterruptAlarm'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="interrupt_alarm_off"><?php echo $text['switch_off']; ?></label>
                                        </div>
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="interrupt_alarm" id="interrupt_alarm_on" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['InterruptAlarm'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="interrupt_alarm_on"><?php echo $text['switch_on']; ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['Over_Angle_Stop'];?>:</div>
                                    <div class="col-9">
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="over_angle_stop" id="over_angle_stop_off" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['OverAngleStop'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="over_angle_stop_off"><?php echo $text['switch_off']; ?></label>
                                        </div>
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="over_angle_stop" id="over_angle_stop_on" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['OverAngleStop'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="over_angle_stop_on"><?php echo $text['switch_on']; ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['direction'];?>:</div>
                                    <div class="col-9">
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepDirection" id="StepDirection_cw" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepDirection'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="direction_cw"><?php echo $text['CW']; ?></label>
                                        </div>
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepDirection" id="StepDirection_ccw" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepDirection'] == 1) ? 'checked' : ''; ?>> 
                                        <label class="form-check-label" for="direction_ccw"><?php echo $text['CCW']; ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['Delay Time'];?> (<?php echo $text['Second'];?>):</div>
                                    <div class="col-9">
                                        <input id="StepDelay" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepDelay'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['Run_Down_Speed'];?> :</div>
                                    <div class="col-9">
                                        <input id="StepRPM" min="20" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepRPM'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-12 row t2 mt-3">
                                    <div class="col-3"><?php echo $text['Acceleration_text'];?>:</div>
                                    <div class="col-9">
                                        <input id="k_value" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['KValue'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 t2">
                                <div class="col-12 row t2 mt-3 ps-4">
                                    <div class="col-4"><?php echo $text['Joint_Offset'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                    <div class="col-8">
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepTorqueOffsetSign" id="join_offset_plus" value="43"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepTorqueOffsetSign'] == 43) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="join_offset_plus"><?php echo $text['Plus_text']; ?></label>
                                        </div>
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepTorqueOffsetSign" id="join_offset_minus" value="45"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepTorqueOffsetSign'] == 45) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="join_offset_minus"><?php echo $text['Minus_text']; ?></label>
                                        <input id="StepTorqueOffset" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorqueOffset'] : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                                <hr class="hr" />
                                <div class="col-12 row t2 mt-3 ps-4">
                                    <div class="col-4"><?php echo $text['Threshold_Type'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                    <div class="col-8">
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_off"  onclick="toggleStepTorqueTS()" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableThreshold'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="threshold_mode_off"><?php echo $text['switch_off']; ?></label>
                                        </div>
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_torque" onclick="toggleStepTorqueTS()" value="2" 
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableThreshold'] == 2) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="threshold_mode_torque"><?php echo $text['torque']; ?></label>
                                        </div>
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_angle" onclick="toggleStepTorqueTS()" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableThreshold'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="threshold_mode_angle"><?php echo $text['angle']; ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div id="threshold_block" class="col-12 row t2 mt-3 ps-4" style="display: flex;">
                                    <div class="col-4" id="show_torque" style="display: none;"><?php echo $text['Threshold_Torque'];?>:</div>
                                    <div class="col-4" id="show_angle" style="display: none;"><?php echo $text['Threshold_Angle'];?>:</div>
                                    <div class="col-8" id="StepTorqueTS_block" >
                                        <input type="text" id="StepTorqueTS" name="StepTorqueTS" class="form-control form-control-sm" style="display: none;" value="<?php echo ($data['type'] === 'edit' && isset($data['step']['StepTorqueTS'])) ? number_format((float)$data['step']['StepTorqueTS'], 4) : ''; ?>">
                                    </div>
                                </div>

                                <hr class="hr">

                                <div class="col-12 row t2 mt-3 ps-4">
                                    <div class="col-4"><?php echo $text['Downshift'];?>:</div>
                                    <div class="col-8">
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_off" value="0" onclick="toggleDownShift()"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableDownShift'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="downshift_mode_off"><?php echo $text['switch_off']; ?></label>
                                        </div>
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_torque" value="2" onclick="toggleDownShift()"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableDownShift'] == 2) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="downshift_mode_torque"><?php echo $text['torque']; ?></label>
                                        </div>
                                        <div class="form-check form-check-inline ">
                                        <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_angle" value="1" onclick="toggleDownShift()"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableDownShift'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="downshift_mode_angle"><?php echo $text['angle']; ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div id="downshift_block" class="col-12 row t2 mt-3 ps-4">
                                    <div class="col-4" id="show_downshift_torque" style="display:block;" ><?php echo $text['Downshift_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                    <div class="col-4" id="show_downshift_angle" style="display:none;" ><?php echo $text['Downshift_Angle'];?> </div>
                                    <div class="col-8" id="StepTorqueDownShift_block"  >
                                        <input id="StepTorqueDownShift" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorqueDownShift'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div id="downshift_speed_block"  class="col-12 row t2 mt-3 ps-4" >
                                    <div class="col-4"><?php echo $text['Downshift_Speed'];?> :</div>
                                    <div class="col-8">
                                        <input id="StepRPMDownShift" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepRPMDownShift'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="w3-center" style="margin: 10px 30px 0px 0;">
                <button style="height:50px;width:100px;font-size:25px" id="button1" class="button button3" onclick="save_or_edit_step(<?php echo (int)($data['type'] == 'edit'); ?>)"><?php echo $text['save']; ?></button>
            </div>
        </div>
    </div>

    
    <!-- 加载動畫 OP -->
       <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->

</div>

<?php require_once '../app/views/step/add_step_share.php';?>

<?php if($_SESSION['privilege'] != 'admin'){ ?>
<script>
  $(document).ready(function () {
    disableAllButtonsAndInputs()
    document.getElementById("return").disabled = false;
  });
</script>
<?php } ?>


<?php require APPROOT . 'views/inc/footer.php'; ?>

<style>
  #StepOption {
    font-size: 14px;
    width: 165px;
    border: 1px solid #DADADA;
  }
</style>