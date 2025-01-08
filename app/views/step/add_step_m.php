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

    .small-input {
    width: 130px; /* 縮小寬度 */
    height: 2rem; /* 固定高度 */
}

.t1{font-size: 17px; margin: 5px 0px; display: flex; align-items: center;}
.t2{font-size: 17px; margin: 5px 0px;}
</style>

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <header>
            <h3>  <h3><?php echo ($data['type'] == 'edit') ? $text['edit_step']  : $text['add_step']; ?></h3></h3>
        </header>
    </div>

    <div style="display:none;">
        <input id="tool_max_torque" value="<?php echo floatval($data['tools_info']['max_torque']); ?>">
        <input id="tool_min_torque" value="<?php echo floatval($data['tools_info']['min_torque']); ?>">
        <input id="tool_max_rpm" value="<?php echo $data['tools_info']['max_rpm']; ?>">
        <input id="tool_min_rpm" value="<?php echo $data['tools_info']['min_rpm']; ?>">
        
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav" style="display: flex; justify-content: center;">
            <div class="info-box" style="border: 1px solid #ccc; padding: 10px; display: flex; align-items: center; background-color: #FFFFFF;">
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%"><?php echo $text['job_id']; ?> : </div>
                    <div class="col-1 t2">
                        <input style="width: 80%;height:20px; 2vmin; background-color: #FFFFFF" type="text" id="JOBID" name="JOBID" size="10" maxlength="20" value="<?php echo $data['JOBID'];?>" disabled>
                    </div>
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%"><?php echo $text['seq_id'];?> : </div>
                    <div class="col-1 t2">
                        <input style="width: 80%;height:20px; 2vmin; background-color: #FFFFFF" type="text" id="SEQID" name="SEQID" size="10" maxlength="20" value="<?php echo $data['SEQID'];?>" disabled>
                    </div>
                    <div class="col-2" style="font-size: 2vmin; padding-left: 3%"><?php echo $text['step_id'];?> : </div>
                    <div class="col-1 t2">
                        <input style="width:80%;height:20px; 2vmin; background-color: #FFFFFF" type="text" id="StepSelect" name="StepSelect" size="10" maxlength="20" value="<?php echo $data['StepSelect'];?>" disabled>
                    </div>

                    <div class="col t2" style=" text-align: right; ">
                        <div class="button-column">
                            <button id="return" onclick="history.go(-1);"><?php echo $text['return']; ?></button>
                        </div>
                    </div>
                </div>
                <?php if($data['type'] == 'edit'){ ?>
                <div style="display: none;">
                    <input type="" id="mode" value="<?php echo $data['mode']; ?>">
                </div>
                <?php } ?>
            </div>

            <div class="container" style="max-width: none;background-color: #F2F1F1;">
                <div class="row">
                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['step_name'];?>:</div>
                            <div class="col-7">
                            <input id="STEPname" class="form-control form-control-sm"   value="<?php echo ($data['type'] == 'edit') ? $data['step']['STEPname'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['target_type'];?>:</div>
                            <div class="col-7">
                                <select id="StepOption"  onchange="updateLabel()">
                                    <?php 
                                            $options = array(
                                                0 => $text['Torque'],
                                                1 => $text['Angle'],
                                                2 => $text['Time']
                                            );

                                            foreach ($options as $value => $label) {
                                                echo '<option value="' . $value . '" ' . (($data['type'] == 'edit' && $data['step']['StepOption'] == $value) ? 'selected' : '') . '>' . $label . '</option>';
                                            }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <?php if($data['type'] == 'edit'){?>
                                <?php   if($data['step']['StepOption'] == 0 ){?>   
                                    <div class="col-5"  id="targetLabel" ><?php echo $text['Target_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <?php }else if($data['step']['StepOption'] == 1 ){?>
                                    <div class="col-5"  id="targetLabel" ><?php echo $text['Target_Angle'];?> :</div>
                                <?php }else if($data['step']['StepOption'] == 2 ) {?>
                                    <div class="col-5"  id="targetLabel" ><?php echo $text['Target_Time'];?>:</div>
                                <?php } ?>

                            <?php }else{?>
                                <div class="col-5"  id="targetLabel" ><?php echo $text['Target_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                            <?php } ?>
                             
                        
                            <div class="col-7" id='StepTorque_item' style="display: block;" >
                                <input id="StepTorque"  class="form-control form-control-sm"  value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorque'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                            <div class="col-7" id='StepAngle_item' style="display: none;" >
                                <input id="StepAngle"   class="form-control form-control-sm"  value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepAngle'] : ''; ?>"  >
                                <div class="invalid-feedback"></div>
                            </div>

                            <div class="col-7" id='StepTime_item' style="display:  none;" >
                                <input id="StepTime"  class="form-control form-control-sm+"  value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTime'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>

                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['High_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                            <div class="col-7">
                                <input id="StepHiTorque" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepHiTorque'] : ''; ?>" >
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Low_Torque']?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                            <div class="col-7">
                                <input id="StepLoTorque" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepLoTorque'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-12">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_0" value="0" onchange="getCheckboxValue()"
                                  <?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 0) ? 'checked' : ''; ?>>
                                  <label class="form-check-label" for="monitoring_torque_window"><?php echo $text['Monitor torque by window']; ?>:</label>
                                </div>
                                <div class="ps-0" style="display:inline-block;">
                                    <label class="form-check-label" for="monitor_torque_upper"><?php echo $text['Upper_text'].'(%)'; ?></label>
                                    <input id="StepLimiHi" class="form-control form-control-sm" style=" width: 40px !important;" value= '<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 0 && $data['step']['StepLimiHi']) ? $data['step']['StepLimiHi'] : ''; ?>'>
                                    <div class="invalid-feedback"></div>
                                    <label class="form-check-label ps-3" for="monitor_torque_lower"><?php echo $text['Lower_text'].'(%)'; ?></label>
                                    <input id="StepLimiLo" class="form-control form-control-sm" style=" width: 40px !important;" value= '<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 0 && $data['step']['StepLimiLo']) ? $data['step']['StepLimiLo'] : ''; ?>'>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['High_Angle'];?>:</div>
                            <div class="col-7">
                                <input id="StepHiAngle" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepHiAngle'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Low_Angle'];?>:</div>
                            <div class="col-7 ">
                                <input id="StepLoAngle" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepLoAngle'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-12">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_1" value="1" onchange="getCheckboxValue()" 
                                  <?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 1) ? 'checked' : ''; ?>>
                                  <label class="form-check-label" for="monitoring_angle_window"><?php echo $text['Monitor angle by window']; ?></label>
                                </div>
                                <div class="ps-0" style="display:inline-block;">
                                    <label class="form-check-label" for="monitor_angle_upper"><?php echo $text['Upper_text'].'(%)'; ?></label>
                                    <input id="StepLimiHi" class="form-control form-control-sm" style=" width: 40px !important; " value ='<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 1 && $data['step']['StepLimiHi']) ? $data['step']['StepLimiHi'] : ''; ?>'>
                                    <div class="invalid-feedback"></div>
                                    <label class="form-check-label ps-3" for="monitor_angle_upper"><?php echo $text['Lower_text'].'(%)'; ?></label>
                                    <input id="StepLimiLo" class="form-control form-control-sm" style=" width: 40px !important; " value ='<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == 1 && $data['step']['StepLimiLo']) ? $data['step']['StepLimiLo'] : ''; ?>'>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Interrupt_Alarm'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Over_Angle_Stop'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['direction'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Delay Time'];?> (<?php echo $text['Second'];?>):</div>
                            <div class="col-7">
                                <input id="StepDelay" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepDelay'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Run_Down_Speed'];?> :</div>
                            <div class="col-7">
                                <input id="StepRPM" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepRPM'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Acceleration_text'];?>:</div>
                            <div class="col-7">
                                <input id="k_value" class="form-control form-control-sm"   value="<?php echo ($data['type'] == 'edit') ? $data['step']['KValue'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Joint_Offset'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                            <div class="col-7">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="StepTorqueOffsetSign" id="join_offset_plus" value="43"
                                  <?php echo ($data['type'] == 'edit' && $data['step']['StepTorqueOffsetSign'] == 43) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="join_offset_plus"><?php echo $text['Plus_text']; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="StepTorqueOffsetSign" id="join_offset_minus" value="45"
                                  <?php echo ($data['type'] == 'edit' && $data['step']['StepTorqueOffsetSign'] == 45) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="join_offset_minus"><?php echo $text['Minus_text']; ?></label>
                                  
                                </div>
                                <input id="StepTorqueOffset" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorqueOffset'] : ''; ?>">
                        
                                
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Threshold_Type'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5" id="show_torque" style="display:block;" ><?php echo $text['Threshold_Torque'];?> 
                                (<?php echo $text[$data['torque_unit']]; ?>):
                            </div>
                            <div class="col-5" id="show_angle"  style="display:none;" ><?php echo $text['Threshold_Angle'];?> :</div>
                            <div class="col-7">
                                <input id="StepTorqueTS" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorqueTS'] : ''; ?> ">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <hr class="hr" />
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Downshift'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;" >
                            <div class="col-5" id="show_downshift_torque" style="display:block;" ><?php echo $text['Downshift_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                            <div class="col-5" id="show_downshift_angle" style="display:none;" ><?php echo $text['Downshift_Angle'];?> </div>
                            <div class="col-7">
                                <input id="StepTorqueDownShift" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorqueDownShift'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Downshift_Speed'];?> :</div>
                            <div class="col-7">
                                <input id="StepRPMDownShift" class="form-control form-control-sm" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepRPMDownShift'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="w3-center" style="margin: 10px 30px 0px 0;">
                <button style="height: 50px; width: 100px; font-size: 25px" id="button1" class="button button3" onclick="<?php echo $data['type'] == 'edit' ? 'edit_step()' : 'save_step()'; ?>"><?php echo $text['save']; ?></button>
            </div>
        </div>
    </div>
</div>


<script>
  window.addEventListener('DOMContentLoaded', (event) => {
    toggleStepTorqueTS();  
    toggleDownShift();

  });

  function toggleStepTorqueTS() {
    const stepTorqueTS = document.getElementById('StepTorqueTS');
    const showTorque = document.getElementById('show_torque');
    const showAngle = document.getElementById('show_angle');
    
    // 預設隱藏 show_torque，顯示 show_angle
    showTorque.style.display = 'none';
    showAngle.style.display = 'none';

    if (document.getElementById('threshold_mode_off').checked) {
        // 如果選擇了 "off" 模式
        stepTorqueTS.disabled = true;
        stepTorqueTS.value = 0;     
        showTorque.style.display = 'block'; // 顯示扭力
    } else if (document.getElementById('threshold_mode_torque').checked) {
        // 如果選擇了 "torque" 模式
        stepTorqueTS.disabled = false;
        stepTorqueTS.value = document.getElementById('tool_min_torque').value; // 設置最小扭力值
        showTorque.style.display = 'block'; // 顯示扭力
    } else if (document.getElementById('threshold_mode_angle').checked) {
        // 如果選擇了 "angle" 模式
        showAngle.style.display = 'block'; // 顯示角度
    }
}


function toggleDownShift() {
    const StepTorqueDownShift = document.getElementById('StepTorqueDownShift');
    const StepRPMDownShift = document.getElementById('StepRPMDownShift');
    const showDownshiftTorque = document.getElementById('show_downshift_torque');
    const showDownshiftAngle = document.getElementById('show_downshift_angle');

    // 預設隱藏所有顯示元素
    showDownshiftTorque.style.display = 'none';
    showDownshiftAngle.style.display = 'none';

    // 設置元素是否禁用的狀態
    StepTorqueDownShift.disabled = false;
    StepRPMDownShift.disabled = false;

    // 根據所選模式來控制顯示和禁用狀態
    if (document.getElementById('downshift_mode_off').checked) {
        // "off" 模式：禁用所有相關元素，顯示扭力
        StepTorqueDownShift.disabled = true;  
        StepRPMDownShift.disabled = true;    
        showDownshiftTorque.style.display = 'block'; 
    } else if (document.getElementById('downshift_mode_torque').checked) {
        // "torque" 模式：啟用所有相關元素，顯示扭力
        showDownshiftTorque.style.display = 'block'; 
        StepTorqueDownShift.value = document.getElementById('tool_min_torque').value; 
    } else if (document.getElementById('downshift_mode_angle').checked) {
        // "angle" 模式：顯示角度
        showDownshiftAngle.style.display = 'block'; 
    }
}


  
    var dataType = "<?php echo $data['type']; ?>";
    if (dataType === 'new') {
        
        document.getElementById("interrupt_alarm_off").checked = true;
        document.getElementById("over_angle_stop_off").checked = true;
        document.getElementById("StepDirection_cw").checked = true;
        document.getElementById("join_offset_plus").checked = true;
        document.getElementById("threshold_mode_off").checked = true;
        document.getElementById("downshift_mode_off").checked = true;
        document.getElementById("StepLimiHi").value = 0;
        document.getElementById("StepLimiLo").value = 0;
        document.getElementById("StepHiAngle").value = 0;
        document.getElementById("StepLoAngle").value = 0;
        document.getElementById("StepDelay").value = 0;
        document.getElementById("StepRPM").value = 100;
        document.getElementById("k_value").value = 0;
        document.getElementById("StepTorqueTS").value = 0;
        document.getElementById("StepTorqueDownShift").value = 0;
        document.getElementById("StepRPMDownShift").value = 100;
        document.getElementById("StepTorqueOffset").value = 0.0;



        updateLabel();
    } 
    if(dataType === 'edit'){
        updateLabel();
    }



    function updateLabel() {
        var StepTorque_value = '<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorque'] : '0'; ?>';
        var StepAngle_value = '<?php echo ($data['type'] == 'edit') ? $data['step']['StepAngle'] : '0'; ?>';
        var StepTime_value = '<?php echo ($data['type'] == 'edit') ? $data['step']['StepTime'] : '0'; ?>';
        var unit = '<?php echo $data['torque_unit']; ?>';
        
        const select_val = document.getElementById('StepOption');
        const label = document.getElementById('targetLabel');
        const input_name = document.getElementsByName('targetLabel')[0];

        var language = getCookie('language');

   
        const unitMapping = {
            'kgf.cm': {
                'zh-cn': '公斤公分',
                'zh-tw': '公斤公分',
                'default': 'kgf.cm'
            },
            'lbf.in': {
                'zh-cn': '英磅英吋',
                'zh-tw': '英磅英吋',
                'default': 'lbf.in'
            },
            'N.m': {
                'zh-cn': '牛顿米',
                'zh-tw': '牛頓米',
                'default': 'N.m'
            },
            'kgf.m': {
                'zh-cn': '公斤米',
                'zh-tw': '公斤公尺',
                'default': 'kgf.m'
            }
        };

      
        unit = unitMapping[unit] ? unitMapping[unit][language] || unitMapping[unit]['default'] : unit;

       
        const labelMapping = {
            'zh-cn': {
                0: `目标扭矩 (${unit}):`,
                1: '目标角度:',
                2: '目标时间:'
            },
            'zh-tw': {
                0: `目標扭力 (${unit}):`,
                1: '目標角度:',
                2: '目標時間:'
            },
            'default': {
                0: `Target Torque (${unit}):`,
                1: 'Target Angle:',
                2: 'Target Time:'
            }
        };
        
        label.textContent = labelMapping[language] ? labelMapping[language][select_val.value] : labelMapping['default'][select_val.value];

     
        document.getElementById('StepTorque_item').style.display = select_val.value == 0 ? 'block' : 'none';
        document.getElementById('StepAngle_item').style.display = select_val.value == 1 ? 'block' : 'none';
        document.getElementById('StepTime_item').style.display = select_val.value == 2 ? 'block' : 'none';
    }



    function save_step() {

        let data = new FormData();

        let job_id = document.getElementById("JOBID").value;
        let seq_id = document.getElementById("SEQID").value;
        let StepSelect = document.getElementById("StepSelect").value;
        let STEPname = document.getElementById("STEPname").value;
        let StepOption = document.getElementById("StepOption").value;
        let StepTorque = document.getElementById("StepTorque").value;
        let StepHiTorque = document.getElementById("StepHiTorque").value;
        let StepLoTorque = document.getElementById("StepLoTorque").value;
        let StepMoniByWin = getCheckboxValue();
        let StepLimiHi = document.getElementById("StepLimiHi").value;
        let StepLimiLo = document.getElementById("StepLimiLo").value;
        let interrupt_alarm = document.querySelector('input[name="interrupt_alarm"]:checked');
        let over_angle_stop = document.querySelector('input[name="over_angle_stop"]:checked');
        let StepDirection   = document.querySelector('input[name="StepDirection"]:checked');
        let StepDelay = document.getElementById("StepDelay").value;
        let StepRPM = document.getElementById("StepRPM").value;
        let KValue  = document.getElementById("k_value").value;
        let StepTorqueOffsetSign = document.querySelector('input[name="StepTorqueOffsetSign"]:checked');
        let StepTorqueOffset = document.getElementById("StepTorqueOffset").value;

        let StepEnableThreshold  = document.querySelector('input[name="StepEnableThreshold"]:checked');
        let StepTorqueTS = document.getElementById("StepTorqueTS").value;
        let StepEnableDownShift =  document.querySelector('input[name="StepEnableDownShift"]:checked');
        let StepTorqueDownShift = document.getElementById("StepTorqueDownShift").value;
        let StepRPMDownShift = document.getElementById("StepRPMDownShift").value;
        let time = new Date().toISOString().slice(0, 19).replace('T', ' '); 

        data.append("JOBID", job_id);
        data.append("SEQID", seq_id);
        data.append("StepSelect",StepSelect);
        data.append("STEPname",STEPname);
        data.append("time",time);
        data.append("StepOption",StepOption);
        data.append("StepTorque",StepTorque);
        data.append("StepMoniByWin",StepMoniByWin);
        data.append("StepLimiHi",StepLimiHi);
        data.append("StepLimiLo",StepLimiLo);
        data.append("InterruptAlarm", interrupt_alarm  ? interrupt_alarm.value : null);
        data.append("OverAngleStop", over_angle_stop  ? over_angle_stop.value : null);
        data.append("StepDirection",StepDirection ? StepDirection.value : null);
        data.append("StepDelay",StepDelay);
        data.append("StepRPM",StepRPM); 

        data.append("StepTorqueOffset",StepTorqueOffset.value);
        data.append("StepTorqueOffsetSign",StepTorqueOffsetSign);
        //data.append("StepEnableThreshold",StepEnableThreshold.value);
        data.append("StepTorqueTS",StepTorqueTS);
        data.append("StepEnableDownShift",StepEnableDownShift.value);
        data.append("StepTorqueDownShift",StepTorqueDownShift);
        data.append("StepRPMDownShift",StepRPMDownShift);
        data.append("StepHiTorque",StepHiTorque);
        data.append("StepLoTorque",StepLoTorque);
        data.append("KValue",KValue);
        let check =input_check();

        //alert(check);
        if(check){
                $.ajax({
                url: '?url=Step/create_step',
                type: 'POST',
                data: data,
                processData: false, 
                contentType: false, 
                success: function(response) {
                    // 處理成功回應
                    var responseData = JSON.parse(response);
                    //console.log(responseData);
                    alertify.alert(responseData.res_type, responseData.res_msg, function() {
                        window.location.href = '../public/?url=Step/index/' + job_id + '/'+ seq_id; 
                    });
                },
                error: function(xhr, status, error) {
                    // 處理錯誤
                    console.error('Error:', error);
                }
            });
            
        }

    }


   
    function edit_step(){
        
        let data = new FormData();

        let job_id = document.getElementById("JOBID").value;
        let seq_id = document.getElementById("SEQID").value;
        let StepSelect = document.getElementById("StepSelect").value;
        let STEPname = document.getElementById("STEPname").value;
        let StepOption = document.getElementById("StepOption").value;
        let StepTorque = (document.getElementById("StepTorque") && document.getElementById("StepTorque").value) || "";
        let StepAngle = (document.getElementById("StepAngle") && document.getElementById("StepAngle").value) || "";
        let StepHiTorque = document.getElementById("StepHiTorque").value;
        let StepLoTorque = document.getElementById("StepLoTorque").value;
        let StepMoniByWin = getCheckboxValue();
        let StepLimiHi = document.getElementById("StepLimiHi").value;
        let StepLimiLo = document.getElementById("StepLimiLo").value;
        let interrupt_alarm = document.querySelector('input[name="interrupt_alarm"]:checked');
        let over_angle_stop = document.querySelector('input[name="over_angle_stop"]:checked');
        let StepDirection   = document.querySelector('input[name="StepDirection"]:checked');
        let StepDelay = document.getElementById("StepDelay").value;
        let StepRPM = document.getElementById("StepRPM").value;
        let KValue  = document.getElementById("k_value").value;
        let StepTorqueOffset = document.getElementById("StepTorqueOffset").value; 
        let StepTorqueOffsetSign  = document.querySelector('input[name="StepTorqueOffsetSign"]:checked');
        let StepEnableThreshold  = document.querySelector('input[name="StepEnableThreshold"]:checked');
        let StepTorqueTS = document.getElementById("StepTorqueTS").value;
        let StepEnableDownShift =  document.querySelector('input[name="StepEnableDownShift"]:checked');
        let StepTorqueDownShift = document.getElementById("StepTorqueDownShift").value;
        let StepRPMDownShift = document.getElementById("StepRPMDownShift").value;
        let time = new Date().toISOString().slice(0, 19).replace('T', ' '); 

        data.append("JOBID", job_id);
        data.append("SEQID", seq_id);
        data.append("StepSelect",StepSelect);
        data.append("STEPname",STEPname);
        data.append("time",time);
        data.append("StepOption",StepOption);
        data.append("StepAngle",StepAngle);
        data.append("StepTorque",StepTorque);
        data.append("StepMoniByWin",StepMoniByWin);
        data.append("StepLimiHi",StepLimiHi);
        data.append("StepLimiLo",StepLimiLo);
        data.append("InterruptAlarm", interrupt_alarm  ? interrupt_alarm.value : null);
        data.append("OverAngleStop", over_angle_stop  ? over_angle_stop.value : null);
        data.append("StepDirection",StepDirection ? StepDirection.value : null);
        data.append("StepDelay",StepDelay);
        data.append("StepRPM",StepRPM); 
        data.append("KValue",KValue);
     
        data.append("StepTorqueOffset",StepTorqueOffset);
        data.append("StepTorqueOffsetSign",StepTorqueOffsetSign.value);
        data.append("StepEnableThreshold",StepEnableThreshold.value);
        data.append("StepTorqueTS",StepTorqueTS);
        data.append("StepEnableDownShift",StepEnableDownShift.value);
        data.append("StepTorqueDownShift",StepTorqueDownShift);
        data.append("StepRPMDownShift",StepRPMDownShift);
        data.append("StepHiTorque",StepHiTorque);
        data.append("StepLoTorque",StepLoTorque);
        
        let check_step = input_check();
        //alert(check_step);

        if(check_step){
            $.ajax({
            url: '?url=Step/edit_step',
            type: 'POST',
            data: data,
            processData: false, 
            contentType: false, 
            success: function(response) {
                // 處理成功回應
                var responseData = JSON.parse(response);
                //console.log(responseData);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    window.location.href = '../public/?url=Step/index/' + job_id +'/' + seq_id; 
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
        var checkbox0 = document.getElementById("StepMoniByWin_0");
        var checkbox1 = document.getElementById("StepMoniByWin_1");

        var check_val = -1;
        if (checkbox0.checked) {
            checkbox1.checked = false; 

            check_val = 0;
        } else if (checkbox1.checked) {
            checkbox0.checked = false; 
            check_val = 1;
        } else {
        }
        return check_val;
    } 
    
    
    function input_check(argument) {
        let Tool_Max_Torque = parseFloat(document.getElementById('tool_max_torque').value);
        let Tool_Min_Torque = parseFloat(document.getElementById('tool_min_torque').value);
        let Tool_Max_RPM = document.getElementById('tool_max_rpm').value;
        let Tool_Min_RPM = document.getElementById('tool_min_rpm').value;
        let hi_angle_max = 30600;
        let hi_angle_min = document.getElementById('StepAngle').value;

        let StepOption = document.getElementById("StepOption").value;
        let Target_Torque_value = document.getElementById('StepTorque').value;
        let delta = Number.parseFloat(Tool_Min_Torque * 0.05).toFixed(4);

        let  StepEnableThreshold = document.querySelector('input[name="StepEnableThreshold"]:checked');

        /*if(StepEnableThreshold.value == 0){
            Tool_Max_Torque = 0;
            Tool_Min_Torque = 0;
        }*/
        //alert(Tool_Min_Torque);
 
        if(StepOption ==0){

            //torque
            let offset_max = 0;
            if( parseFloat(Tool_Max_Torque*1.08 - Target_Torque_value).toFixed(2) >= parseFloat(Target_Torque_value*0.3).toFixed(4) ){
                offset_max = parseFloat(Target_Torque_value*0.3).toFixed(4);
            }else{
                offset_max = parseFloat(Tool_Max_Torque*1.08 - Target_Torque_value).toFixed(2);
            }
            let offset_min = 0;
            let aa = Number.parseFloat(Tool_Min_Torque*0.7 - Target_Torque_value ).toFixed(4);
            let bb = Number.parseFloat(Target_Torque_value*0.3).toFixed(4);
            if( aa >= -bb ){
                offset_min = aa;
            }else{
                offset_min = -bb;
            }




            hi_angle_max = 30600
            hi_angle_min = 0;
            //lo_angle_max =  document.getElementById('StepHiAngle').value;
            lo_angle_max = 0;
            lo_angle_min = 0;
            hi_torque_max = Number.parseFloat(Tool_Max_Torque*1.1).toFixed(4);
            hi_torque_min = Number.parseFloat( parseFloat(Target_Torque_value) + parseFloat(delta) ).toFixed(4);
            lo_torque_max = Number.parseFloat( parseFloat(Target_Torque_value) - parseFloat(delta) ).toFixed(4);
            lo_torque_min = 0;
            join_offset_max = offset_max;
            join_offset_min = offset_min;
            torque_threshold_max = document.getElementById('StepTorque').value;
            torque_threshold_min = 0;
            downshift_torque_max = document.getElementById('StepTorque').value;
            downshift_torque_min = 0;
            downshift_speed_max = document.getElementById('StepRPMDownShift').value;
            downshift_speed_min = Tool_Min_RPM;

            angle_threshold_max = 30600;
            angle_threshold_min = 0;
            downshift_angle_max = 30600;
            downshift_angle_min = 0;

        }else if(StepOption ==1){

            //Angle 
            let offset_max = Number.parseFloat(Tool_Max_Torque*0.3).toFixed(4);
            let offset_min = parseFloat(Tool_Min_Torque*0.7 - document.getElementById('StepHiTorque').value ).toFixed(4);
            if(Math.abs(offset_min) > Math.abs(offset_max)){
                offset_min = offset_max * -1;
            }

 

            hi_angle_max = 30600;
            hi_angle_min = document.getElementById('StepAngle').value;
            lo_angle_max =  document.getElementById('StepHiAngle').value;
            lo_angle_min = 0;
            hi_torque_max = Number.parseFloat(Tool_Max_Torque*1.1).toFixed(4);
            hi_torque_min = 0;
            lo_torque_max = (document.getElementById('StepHiAngle').value - delta).toFixed(4);
            lo_torque_min = 0;
            join_offset_max = offset_max;
            join_offset_min = offset_min;
            torque_threshold_max = parseFloat(document.getElementById('StepHiTorque').value);
            torque_threshold_min = 0;
            downshift_torque_max = Tool_Max_Torque;
            downshift_torque_min = 0
            downshift_speed_max = document.getElementById('StepRPMDownShift').value;
            downshift_speed_min = Tool_Min_RPM;

            angle_threshold_max = document.getElementById('StepAngle').value;
            angle_threshold_min = 0;
            downshift_angle_max = 30600;
            downshift_angle_min = 0;

        }else{

            //time
            let offset_max = 99999;
            let offset_min = 0;
  

            hi_angle_max = 99999;
            hi_angle_min = 0;
            lo_angle_max = document.getElementById('StepHiAngle').value;
            lo_angle_min = 0;
            hi_torque_max = 99999;
            hi_torque_min = 0;
            lo_torque_max = 99999;
            lo_torque_min = 0;
            join_offset_max = offset_max;
            join_offset_min = offset_min;
            torque_threshold_max = 99999;
            torque_threshold_min = 0;
            downshift_torque_max = 99999;
            downshift_torque_min = 0;
            downshift_speed_max = 99999;
            downshift_speed_min = 0;

            angle_threshold_max = 99999;
            angle_threshold_min = 0;
            downshift_angle_max = 99999;
            downshift_angle_min = 0;

        }

        let conditions  = [];
        if (StepOption == 0) {
        // StepOption == 0: 需要驗證 StepTorque，不需要驗證 StepAngle 和 StepTime
            conditions = [
                { id: 'StepTorque', pattern: /^\d{1,5}(\.\d{1})?$/, min: Tool_Min_Torque, max: Tool_Max_Torque }
            ];
        } else if (StepOption == 1) {
            // StepOption == 1: 需要驗證 StepAngle，不需要驗證 StepTorque 和 StepTime
            conditions = [
                { id: 'StepAngle', pattern: /^\d{0,5}?$/, min: 1, max: 30600 }
            ];
        } else if (StepOption == 2) {
            // StepOption == 2: 需要驗證 StepTime，不需要驗證 StepTorque 和 StepAngle
            conditions = [
                { id: 'StepTime', pattern: /^\d{0,5}?$/, min: 0, max: 20 }
            ];
        }

        //unscrew_torque_threshold
         conditions = [
            { id: 'STEPname', pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/, min: null, max: null },
            { id: 'StepDelay', pattern: /^\d{0,4}$/, min: 0, max: 2000 }, 
            { id: 'StepRPM', pattern: /^\d{0,4}$/, min: Tool_Min_RPM, max: Tool_Max_RPM },
            { id: 'k_value', pattern: /^(0(\.\d{1,2})?|1(\.\d{2})?|2(\.([0-4]{1}[0-9]{1}|50)))$/, min: 0, max: 2.50 },  
            { id: 'StepRPMDownShift',pattern: /^\d{0,4}$/, min: Tool_Min_RPM, max: Tool_Max_RPM},
            { id: 'StepTorqueTS', pattern: /^\d{0,4}(\.\d{1})?$/, min: Tool_Min_Torque, max: Tool_Max_Torque },
            { id: 'StepTorqueDownShift', pattern: /^\d{0,4}(\.\d{1})?$/, min: Tool_Min_Torque, max: Tool_Max_Torque },
            { id: 'StepHiTorque',pattern: /^\d{0,6}(\.\d{0,4})?$/, min: lo_torque_min, max: hi_torque_max },
            { id: 'StepLoTorque',pattern: /^\d{0,6}(\.\d{0,4})?$/, min: lo_torque_min, max: hi_torque_max },
            { id: 'StepHiAngle', pattern: /^\d{0,5}?$/, min: hi_angle_min, max: 30600 },
            { id: 'StepLoAngle', pattern: /^\d{1,6}$/, min: lo_angle_min, max: lo_angle_max },
            { id: 'StepLimiHi',pattern: /^\d{0,3}$/, min: 0, max:100 },
            { id: 'StepLimiLo',pattern: /^\d{0,3}$/, min: 0, max:100 },
            //{ id: 'StepTorque', pattern: /^\d{1,5}(\.\d{1})?$/, min: Tool_Min_Torque, max: Tool_Max_Torque },
            //{ id: 'StepAngle', pattern: /^\d{0,5}?$/, min: 1, max: 30600 },
            //{ id: 'StepTime', pattern: /^\d{0,5}?$/, min: 0, max: 20 },
   
        ];

        if (StepOption == 0) {
            // 當 StepOption == 0 時，不需要驗證 StepAngle 和 StepTime
            conditions.push(
                { id: 'StepTorque', pattern: /^\d{1,5}(\.\d{1})?$/, min: Tool_Min_Torque, max: Tool_Max_Torque },
              
            );
        } else if (StepOption == 1) {
            // 保留所有欄位驗證
            conditions.push(
                { id: 'StepAngle', pattern: /^\d{0,5}?$/, min: 1, max: 30600 },
            );
        } else if (StepOption == 2) {
            // 保留所有欄位驗證
            conditions.push(
                { id: 'StepTime', pattern: /^\d{0,5}?$/, min: 0, max: 20 },
            );
        }


        let isFormValid = true;
        let errorMessages = []; 
        conditions.forEach(function(input) {
            var element = document.getElementById(input.id);
            var value = element.value.trim();

 
            if(input.id != 'STEPname'){
                var nextSibling = element.nextElementSibling;
                if (nextSibling) {
                    nextSibling.innerHTML = input.min + ' ~ ' + input.max;
                }
            }

            if (value === "") {
                element.classList.add("is-invalid");
                errorMessages.push(input.message); // 儲存錯誤訊息
                isFormValid = false;
            } else if (!input.pattern.test(value)) {
                element.classList.add("is-invalid");
                errorMessages.push(input.message); // 儲存錯誤訊息
                isFormValid = false;
            } else if (input.min !== null && parseFloat(value) < input.min) {
                element.classList.add("is-invalid");
                errorMessages.push(input.message); // 儲存錯誤訊息
                isFormValid = false;
            } else if (input.max !== null && parseFloat(value) > input.max) {
                element.classList.add("is-invalid");
                errorMessages.push(input.message); // 儲存錯誤訊息
                isFormValid = false;
            } else {
                element.classList.remove("is-invalid");
            }

        });

        
        console.log(isFormValid);
        if (!isFormValid) {
            //alert("以下欄位有錯誤：\n" + errorMessages.join("\n"));
            return false;  // 只要有錯誤，回傳 false
        }

        return true;

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

<style>
  #StepOption {
    font-size: 14px;
    width: 165px;
    border: 1px solid #DADADA;
  }
</style>