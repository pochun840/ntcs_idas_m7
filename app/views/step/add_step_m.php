

<link rel="stylesheet" type="text/css" href="<?php echo URLROOT; ?>css/add_seq_step_m.css">

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
    .small-input {
    max-width: 100%;
    height: 2rem;
    font-size: 13px;
    }

    .custom-style {
        font-size: 12px;
        margin-right: 5px;
    }

        
    .is-invalid~.invalid-feedback
    {
        display: inline!important;
    }

</style>



<div class="container-ms">
    <div class="w3-text-white w3-center">
        <header id="header">
 	        <h3><?php echo ($data['type'] == 'edit') ? $text['edit_step']  : $text['add_step']; ?></h3>
        </header>
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

        <input id="check_target_tor_lo" value="<?php echo $data['tools_info']['check_target_tor_lo'];?>"> 
        <input id="check_target_tor_hi" value="<?php echo $data['tools_info']['check_target_tor_hi'];?>"> 
        <input id="check_hi_tor_before" value="<?php echo $data['tools_info']['check_hi_tor_before'];?>"> 
        <input id="check_hi_tor_after"  value="<?php echo $data['tools_info']['check_hi_tor_after'];?>"> 
        <input id="check_lo_tor_before" value="<?php echo $data['tools_info']['check_lo_tor_before'];?>"> 
        <input id="check_lo_tor_after"  value="<?php echo $data['tools_info']['check_lo_tor_after'];?>"> 
        <input id="check_lo_rpm" value="<?php echo $data['tools_info']['check_lo_rpm'];?>"> 
        <input id="check_hi_rpm" value="<?php echo $data['tools_info']['check_hi_rpm'];?>"> 
    </div>

    <div class="main-content">
        <div class="center-content">
             <div class="topnav">
                <label style="font-size:3vmin;color: #000;" for="job_id"><?php echo $text['job_id'];?> :</label>
                <input type="text" id="JOBID" name="JOBID" size="7" maxlength="20" value="<?php echo $data['JOBID'];?>" disabled
                style="height:28px; font-size:3vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:3vmin;color: #000;" for="seq_id"><?php echo $text['seq_id'];?> :</label>
                <input type="text" id="SEQID" name="SEQID" size="7" maxlength="20" value="<?php echo $data['SEQID'];?>" disabled
                style="height:28px; font-size:3vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:3vmin;color: #000;" for="step_id"><?php echo $text['step_id'];?> :</label>
                <input type="text" id="StepSelect" name="StepSelect" size="7" maxlength="20" value="<?php echo $data['StepSelect'];?>" disabled
                style="height:28px; font-size:3vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <?php $url = '?url=Step/index/' . $data['JOBID']."/".$data['SEQID']; ?>
                <button id="back_btn" type="button" onclick="window.location.href='<?php echo $url; ?>';"><?php echo $text['return']; ?></button>

                <?php if($data['type'] == 'edit'){ ?>
                <div style="display: none;">
                    <input type="" id="mode" value="<?php echo $data['mode']; ?>">
                </div>
                <?php } ?>
            </div>

            <div class="new-container">
                <div class="newStep-scrollbar" id="style-newStep">
                    <div class="newStep-force-overflow">
                        <div style="background-color: #F2F1F1; padding-left: 2%">
                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['step_name'];?>:</div>
                                <div class="col-5 t2">
                                    <input id="STEPname" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['step']['STEPname'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>    
                            </div>
                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['target_type'];?>:</div>
                                <div class="col t2">
                                    <select id="StepOption" class="t2 form-select" onchange="updateLabel()" style="width: 149px;">
                                        <?php 
                                            $options = array(
                                                2 => $text['Torque'],
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

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 5px 0px;">

                            <div class="row">
                                <?php if($data['type'] == 'edit'){?>
                                    <?php   if($data['step']['StepOption'] == 2 ){?>   
                                        <div class="col-6 t1"  id="targetLabel" ><?php echo $text['Target_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                    <?php }else if($data['step']['StepOption'] == 1 ){?>
                                        <div class="col-6 t1"  id="targetLabel" ><?php echo $text['Target_Angle'];?> :</div>
                                    <?php } ?>

                                <?php }else{?>
                                    <div class="col-6 t1"  id="targetLabel" ><?php echo $text['Target_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <?php } ?>
                                
                            
                                <div class="col-5 t2" id='StepTorque_item' style="display: block;" >
                                    <input id="StepTorque" class="t2 form-control small-input"  value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorque'] : ''; ?>">

                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col-5 t2" id='StepAngle_item' style="display: none;" >
                                    <input id="StepAngle"   class="t2 form-control small-input"  value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepAngle'] : ''; ?>"  >
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-5 t2" id='StepTime_item' style="display:  none;" >
                                    <input id="StepTime"  class="t2 form-control small-input"  value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTime'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 5px 0px;">

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['High_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <div class="col-5 t2">
                                    <input id="StepHiTorque" type="text"  class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepHiTorque'] : ''; ?>" >
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Low_Torque']?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <div class="col-5 t2">
                                    <input id="StepLoTorque"  type="text"  class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepLoTorque'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="row"  id='show_tor' >
                                <div class="col">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_0" value="1" <?php echo ($data['step']['StepMoniByWin'] == "1" && $data['step']['StepOption'] == "2") ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="monitoring_torque_window"><?php echo $text['Monitor torque by window']; ?>:</label>
                                    </div>
                                    <div class="ps-0" style="display:inline-block;">
                                        <label class="form-check-label" for="monitor_torque_upper"><?php echo $text['Upper_text'].'(%)'; ?></label>
                                        <input id="step_limit_hi_tor" class="t2 form-control small-input" style=" width: 40px !important;" 
                                            value= '<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == "-1" || $data['step']['StepMoniByWin'] == "0" && $data['step']['StepLimiHi']) ? $data['step']['StepLimiHi'] : ''; ?>'>
                                        <div class="invalid-feedback"></div>

                                        <label class="form-check-label ps-3" for="monitor_torque_lower"><?php echo $text['Lower_text'].'(%)'; ?></label>
                                        <input id="step_limit_lo_tor" class="t2 form-control small-input" style=" width: 40px !important;" 
                                            value= '<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == "-1" || $data['step']['StepMoniByWin'] == "0" && $data['step']['StepLimiLo']) ? $data['step']['StepLimiLo'] : ''; ?>'>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 5px 0px;">

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['High_Angle'];?>:</div>
                                <div class="col-5 t2">
                                    <input id="StepHiAngle" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepHiAngle'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Low_Angle'];?>:</div>
                                <div class="col-5 t2">
                                    <input id="StepLoAngle" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepLoAngle'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="row" id='show_ang'>
                                <div class="col">
                                    <div class="form-check form-check-inline "  >
                                        <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_1" value="1" <?php echo ($data['step']['StepMoniByWin'] == "1" && $data['step']['StepOption'] == "1") ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="monitoring_angle_window"><?php echo $text['Monitor angle by window']; ?></label>
                                    </div>
                                    <div class="ps-0" style="display:inline-block;">
                                        <label class="form-check-label" for="monitor_angle_upper"><?php echo $text['Upper_text'].'(%)'; ?></label>
                                        <input id="step_limit_hi_ang" class="t2 form-control small-input" style=" width: 40px !important; " 
                                            value ='<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == "1"  || $data['step']['StepMoniByWin'] == "0" && $data['step']['StepLimiHi']) ? $data['step']['StepLimiHi'] : ''; ?>'>
                                        <div class="invalid-feedback"></div>

                                        <label class="form-check-label ps-3" for="monitor_angle_upper"><?php echo $text['Lower_text'].'(%)'; ?></label>
                                        <input id="step_limit_lo_ang"class="t2 form-control small-input" style=" width: 40px !important; " 
                                            value ='<?php echo ($data['type'] == 'edit' && $data['step']['StepMoniByWin'] == "1"  || $data['step']['StepMoniByWin'] == "0" && $data['step']['StepLimiLo']) ? $data['step']['StepLimiLo'] : ''; ?>'>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 5px 0px;">

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Interrupt_Alarm'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="interrupt_alarm" id="interrupt_alarm_off" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['InterruptAlarm'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="interrupt_alarm_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                        <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="interrupt_alarm" id="interrupt_alarm_on" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['InterruptAlarm'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="interrupt_alarm_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>
                            <div  id='over_angle_stop_item'>
                                <div class="row">
                                    <div class="col-6 t1"><?php echo $text['Over_Angle_Stop'];?>:</div>
                                    <div class="col t2">
                                        <div class="form-check form-check-inline zoom">
                                            <input class="form-check-input" type="radio" name="over_angle_stop" id="over_angle_stop_off" value="0"
                                            <?php echo ($data['type'] == 'edit' && $data['step']['OverAngleStop'] == 0) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="over_angle_stop_off"><?php echo $text['switch_off']; ?></label>
                                        </div>
                                            <div class="form-check form-check-inline zoom">
                                            <input class="form-check-input" type="radio" name="over_angle_stop" id="over_angle_stop_on" value="1"
                                            <?php echo ($data['type'] == 'edit' && $data['step']['OverAngleStop'] == 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="over_angle_stop_on"><?php echo $text['switch_on']; ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['direction'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom" style="margin-right: 18px;">
                                        <input class="form-check-input" type="radio" name="StepDirection" id="StepDirection_cw" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepDirection'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="direction_cw"><?php echo $text['CW']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepDirection" id="StepDirection_ccw" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepDirection'] == 0) ? 'checked' : ''; ?>> 
                                        <label class="form-check-label" for="direction_ccw"><?php echo $text['CCW']; ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Delay Time'];?> (<?php echo $text['Second'];?>):</div>
                                <div class="col-5 t2">
                                    <input id="StepDelay" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepDelay'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="row " >
                                <div class="col-6 t1"><?php echo $text['Run_Down_Speed'];?> :</div>
                                <div class="col-5 t1">
                                    <input id="StepRPM" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepRPM'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="row" style="display: none;">
                                <div class="col-6 t1"><?php echo $text['Acceleration_text'];?>:</div>
                                <div class="col-5 t2">
                                    <input id="k_value" class="t2 form-control small-input"   value="<?php echo ($data['type'] == 'edit') ? $data['step']['KValue'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        
                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Joint_Offset'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <div class="col t2 radio-group">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepTorqueOffsetSign" id="join_offset_plus" value="43"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepTorqueOffsetSign'] == 43) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="join_offset_plus"><?php echo $text['Plus_text']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepTorqueOffsetSign" id="join_offset_minus" value="45"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepTorqueOffsetSign'] == 45) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="join_offset_minus"><?php echo $text['Minus_text']; ?></label>
                                    </div>
                                    <div class="col-3 t2">
                                        <input id="StepTorqueOffset" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepTorqueOffset'] : ''; ?>">
                                         <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 5px 0px;">

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Threshold_Type'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <div class="col t2 radio-group">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_off"  onclick="toggleStepTorqueTS()" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableThreshold'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="threshold_mode_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_torque" onclick="toggleStepTorqueTS()" value="2" 
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableThreshold'] == 2) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="threshold_mode_torque"><?php echo $text['torque']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_angle" onclick="toggleStepTorqueTS()" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableThreshold'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="threshold_mode_angle"><?php echo $text['angle']; ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 t1" id="show_torque" style="display:block;" ><?php echo $text['Threshold_Torque'];?> 
                                    (<?php echo $text[$data['torque_unit']]; ?>):
                                </div>
                                <div class="col-6 t1" id="show_angle"  style="display:none;" ><?php echo $text['Threshold_Angle'];?> :</div>
                                <div class="col-5 t2" id="StepTorqueTS_block" >
                                    <input type="text" id="StepTorqueTS" name="StepTorqueTS" class="form-control form-control-sm" style="display: none;" value="<?= ($data['type'] === 'edit' && is_numeric($data['step']['StepTorqueTS'])) ? $data['step']['StepTorqueTS'] : '' ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 5px 0px;">

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Downshift'];?>:</div>
                                <div class="col t2 radio-group">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_off" value="0" onclick="toggleDownShift()"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableDownShift'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="downshift_mode_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_torque" value="2" onclick="toggleDownShift()"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableDownShift'] == 2) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="downshift_mode_torque"><?php echo $text['torque']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_angle" value="1" onclick="toggleDownShift()"
                                        <?php echo ($data['type'] == 'edit' && $data['step']['StepEnableDownShift'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="downshift_mode_angle"><?php echo $text['angle']; ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-6 t1" id="show_downshift_torque" style="display:block;" ><?php echo $text['Downshift_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <div class="col-6 t1" id="show_downshift_angle" style="display:none;" ><?php echo $text['Downshift_Angle'];?> </div>
                                <div class="col-5 t2" id="StepTorqueDownShift_block" >
                                    <input id="StepTorqueDownShift" class="form-control form-control-sm" value="<?= ($data['type'] === 'edit' && is_numeric($data['step']['StepTorqueDownShift'])) ? $data['step']['StepTorqueDownShift'] : '' ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="row"  id="downshift_speed_block"  >
                                <div class="col-6 t1"><?php echo $text['Downshift_Speed'];?> :</div>
                                <div class="col-5 t2">
                                    <input id="StepRPMDownShift" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['step']['StepRPMDownShift'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div style="text-align: center;margin-top: 30px; margin-bottom:0px">
                <button id="button1" class="button w3-button w3-border w3-round-large"  onclick="save_or_edit_step(<?php echo (int)($data['type'] == 'edit'); ?>)" ><?php echo $text['save']; ?></button>
            </div>
        </div>
    </div>

    <!-- 加载動畫 OP -->
       <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->

</div>




<?php if($_SESSION['privilege'] != 'admin'){ ?>
<script>
  $(document).ready(function () {
    disableAllButtonsAndInputs()
    document.getElementById("return").disabled = false;
  });
</script>
<?php } ?>
<?php require_once '../app/views/step/add_step_share.php';?>

<?php require APPROOT . 'views/inc/footer.php'; ?>

<style>
    #StepOption 
    {
        font-size: 15px;
        border: 1px solid #DADADA;
    }

    /* Mặc định: giữ nguyên hàng ngang */
    .radio-group 
    {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        max-width: 100%;
    }

    /* Khi quay dọc: vẫn giữ hàng ngang, chỉ thụt vào */
    @media screen and (orientation: portrait) 
    {
        .radio-group 
        {
            padding-left: 15%;
            flex-wrap: nowrap; /* giữ trên 1 hàng nếu có thể */
            gap: 0.5rem;
        }
    }
</style>