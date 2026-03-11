<?php
$stepData = $data['step'] ?? [];

$stepName             = $stepData['STEPname'] ?? '';
$stepOption           = (string)($stepData['StepOption'] ?? '2');
$stepTorque           = $stepData['StepTorque'] ?? '';
$stepAngle            = $stepData['StepAngle'] ?? '';
$stepTime             = $stepData['StepTime'] ?? '';
$stepHiTorque         = $stepData['StepHiTorque'] ?? '';
$stepLoTorque         = $stepData['StepLoTorque'] ?? '';
$stepHiAngle          = $stepData['StepHiAngle'] ?? '';
$stepLoAngle          = $stepData['StepLoAngle'] ?? '';
$stepMoniByWin        = (string)($stepData['StepMoniByWin'] ?? '0');
$stepLimiHi           = $stepData['StepLimiHi'] ?? '';
$stepLimiLo           = $stepData['StepLimiLo'] ?? '';
$interruptAlarm       = (string)($stepData['InterruptAlarm'] ?? '0');
$overAngleStop        = (string)($stepData['OverAngleStop'] ?? '0');
$stepDirection        = (string)($stepData['StepDirection'] ?? '1');
$stepDelay            = $stepData['StepDelay'] ?? '';
$stepRPM              = $stepData['StepRPM'] ?? '';
$kValue               = $stepData['KValue'] ?? '';
$stepTorqueOffsetSign = (string)($stepData['StepTorqueOffsetSign'] ?? '43');
$stepTorqueOffset     = $stepData['StepTorqueOffset'] ?? '';
$stepEnableThreshold  = (string)($stepData['StepEnableThreshold'] ?? '0');
$stepTorqueTS         = $stepData['StepTorqueTS'] ?? '';
$stepEnableDownShift  = (string)($stepData['StepEnableDownShift'] ?? '0');
$stepTorqueDownShift  = $stepData['StepTorqueDownShift'] ?? '';
$stepRPMDownShift     = $stepData['StepRPMDownShift'] ?? '';
?>

<link rel="stylesheet" type="text/css" href="<?php echo URLROOT; ?>css/add_seq_step_m.css">

<style type="text/css">
    .form-control {
        width: auto !important;
        display: initial !important;
    }
    .form-control.is-invalid {
        padding-right: inherit !important;
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
    .is-invalid ~ .invalid-feedback {
        display: inline !important;
    }
</style>

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <header id="header">
            <h3><?php echo ($data['type'] == 'edit') ? $text['edit_step'] : $text['add_step']; ?></h3>
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
        <input id="step_torque_unit" value="<?php echo $data['step_torque_unit']; ?>">

        <input id="check_target_tor_lo" value="<?php echo $data['tools_info']['check_target_tor_lo']; ?>">
        <input id="check_target_tor_hi" value="<?php echo $data['tools_info']['check_target_tor_hi']; ?>">
        <input id="check_hi_tor_before" value="<?php echo $data['tools_info']['check_hi_tor_before']; ?>">
        <input id="check_hi_tor_after" value="<?php echo $data['tools_info']['check_hi_tor_after']; ?>">
        <input id="check_lo_tor_before" value="<?php echo $data['tools_info']['check_lo_tor_before']; ?>">
        <input id="check_lo_tor_after" value="<?php echo $data['tools_info']['check_lo_tor_after']; ?>">
        <input id="check_lo_rpm" value="<?php echo $data['tools_info']['check_lo_rpm']; ?>">
        <input id="check_hi_rpm" value="<?php echo $data['tools_info']['check_hi_rpm']; ?>">
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:3vmin;color:#000;" for="job_id"><?php echo $text['job_id']; ?> :</label>
                <input type="text" id="JOBID" name="JOBID" size="7" maxlength="20"
                       value="<?php echo $data['JOBID']; ?>" disabled
                       style="height:28px; font-size:3vmin; text-align:center; background-color:#DDDDDD; border:0; margin:3px;">

                <label style="font-size:3vmin;color:#000;" for="seq_id"><?php echo $text['seq_id']; ?> :</label>
                <input type="text" id="SEQID" name="SEQID" size="7" maxlength="20"
                       value="<?php echo $data['SEQID']; ?>" disabled
                       style="height:28px; font-size:3vmin; text-align:center; background-color:#DDDDDD; border:0; margin:3px;">

                <label style="font-size:3vmin;color:#000;" for="step_id"><?php echo $text['step_id']; ?> :</label>
                <input type="text" id="StepSelect" name="StepSelect" size="7" maxlength="20"
                       value="<?php echo $data['StepSelect']; ?>" disabled
                       style="height:28px; font-size:3vmin; text-align:center; background-color:#DDDDDD; border:0; margin:3px;">

                <?php $url = '?url=Step/index/' . $data['JOBID'] . "/" . $data['SEQID']; ?>
                <button id="back_btn" type="button" onclick="window.location.href='<?php echo $url; ?>';">
                    <?php echo $text['return']; ?>
                </button>

                <?php if ($data['type'] == 'edit') { ?>
                <div style="display:none;">
                    <input type="text" id="mode" value="<?php echo $data['mode']; ?>">
                </div>
                <?php } ?>
            </div>

            <div class="new-container">
                <div class="newStep-scrollbar" id="style-newStep">
                    <div class="newStep-force-overflow">
                        <div style="background-color:#F2F1F1; padding-left:2%; padding-right:2%;">

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['step_name']; ?>:</div>
                                <div class="col-5 t2">
                                    <input id="STEPname" class="t2 form-control small-input" value="<?php echo htmlspecialchars($stepName); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['target_type']; ?>:</div>
                                <div class="col t2">
                                    <select id="StepOption" class="t2 form-select" onchange="updateLabel()" style="width:149px;">
                                        <?php
                                        $options = array(
                                            2 => $text['Torque'],
                                            1 => $text['Angle'],
                                        );
                                        foreach ($options as $value => $label) {
                                            echo '<option value="' . $value . '" ' . (($stepOption === (string)$value) ? 'selected' : '') . '>' . $label . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <hr style="border:1px solid #ccc; width:96%; margin:5px 0;">

                            <div class="row">
                                <?php if ($stepOption === '2') { ?>
                                    <div class="col-6 t1" id="targetLabel"><?php echo $text['Target_Torque']; ?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <?php } else { ?>
                                    <div class="col-6 t1" id="targetLabel"><?php echo $text['Target_Angle']; ?> :</div>
                                <?php } ?>

                                <div class="col-5 t2" id="StepTorque_item" style="<?php echo ($stepOption === '2') ? 'display:block;' : 'display:none;'; ?>">
                                    <input id="StepTorque" class="t2 form-control small-input" value="<?php echo htmlspecialchars($stepTorque); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-5 t2" id="StepAngle_item" style="<?php echo ($stepOption === '1') ? 'display:block;' : 'display:none;'; ?>">
                                    <input id="StepAngle" class="t2 form-control small-input" value="<?php echo htmlspecialchars($stepAngle); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>

                                <div class="col-5 t2" id="StepTime_item" style="display:none;">
                                    <input id="StepTime" class="t2 form-control small-input" value="<?php echo htmlspecialchars($stepTime); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <hr style="border:1px solid #ccc; width:96%; margin:5px 0;">

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['High_Torque']; ?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <div class="col-5 t2">
                                    <input id="StepHiTorque" type="text" class="t2 form-control small-input" value="<?php echo htmlspecialchars($stepHiTorque); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Low_Torque']; ?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <div class="col-5 t2">
                                    <input id="StepLoTorque" type="text" class="t2 form-control small-input" value="<?php echo htmlspecialchars($stepLoTorque); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row" id="show_tor">
                                <div class="col">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_0" value="1" <?php echo ($stepMoniByWin === "1" && $stepOption === "2") ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="monitoring_torque_window"><?php echo $text['Monitor torque by window']; ?>:</label>
                                    </div>
                                    <div class="ps-0" style="display:inline-block;">
                                        <label class="form-check-label" for="monitor_torque_upper"><?php echo $text['Upper_text'] . '(%)'; ?></label>
                                        <input id="step_limit_hi_tor" class="t2 form-control small-input" style="width:40px !important;"
                                               value='<?php echo ($data['type'] == 'edit' && (($stepMoniByWin === "-1") || ($stepMoniByWin === "0" && $stepLimiHi !== ''))) ? htmlspecialchars($stepLimiHi) : ''; ?>'>
                                        <div class="invalid-feedback"></div>

                                        <label class="form-check-label ps-3" for="monitor_torque_lower"><?php echo $text['Lower_text'] . '(%)'; ?></label>
                                        <input id="step_limit_lo_tor" class="t2 form-control small-input" style="width:40px !important;"
                                               value='<?php echo ($data['type'] == 'edit' && (($stepMoniByWin === "-1") || ($stepMoniByWin === "0" && $stepLimiLo !== ''))) ? htmlspecialchars($stepLimiLo) : ''; ?>'>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <hr style="border:1px solid #ccc; width:96%; margin:5px 0;">

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['High_Angle']; ?>:</div>
                                <div class="col-5 t2">
                                    <input id="StepHiAngle" class="t2 form-control small-input" value="<?php echo htmlspecialchars($stepHiAngle); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Low_Angle']; ?>:</div>
                                <div class="col-5 t2">
                                    <input id="StepLoAngle" class="t2 form-control small-input" value="<?php echo htmlspecialchars($stepLoAngle); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row" id="show_ang">
                                <div class="col">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="StepMoniByWin" id="StepMoniByWin_1" value="1" <?php echo ($stepMoniByWin === "1" && $stepOption === "1") ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="monitoring_angle_window"><?php echo $text['Monitor angle by window']; ?></label>
                                    </div>
                                    <div class="ps-0" style="display:inline-block;">
                                        <label class="form-check-label" for="monitor_angle_upper"><?php echo $text['Upper_text'] . '(%)'; ?></label>
                                        <input id="step_limit_hi_ang" class="t2 form-control small-input" style="width:40px !important;"
                                               value='<?php echo ($data['type'] == 'edit' && (($stepMoniByWin === "1") || ($stepMoniByWin === "0" && $stepLimiHi !== ''))) ? htmlspecialchars($stepLimiHi) : ''; ?>'>
                                        <div class="invalid-feedback"></div>

                                        <label class="form-check-label ps-3" for="monitor_angle_upper"><?php echo $text['Lower_text'] . '(%)'; ?></label>
                                        <input id="step_limit_lo_ang" class="t2 form-control small-input" style="width:40px !important;"
                                               value='<?php echo ($data['type'] == 'edit' && (($stepMoniByWin === "1") || ($stepMoniByWin === "0" && $stepLimiLo !== ''))) ? htmlspecialchars($stepLimiLo) : ''; ?>'>
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                            </div>

                            <hr style="border:1px solid #ccc; width:96%; margin:5px 0;">

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Interrupt_Alarm']; ?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="interrupt_alarm" id="interrupt_alarm_off" value="0" <?php echo ($interruptAlarm === '0') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="interrupt_alarm_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="interrupt_alarm" id="interrupt_alarm_on" value="1" <?php echo ($interruptAlarm === '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="interrupt_alarm_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <div id="over_angle_stop_item">
                                <div class="row">
                                    <div class="col-6 t1"><?php echo $text['Over_Angle_Stop']; ?>:</div>
                                    <div class="col t2">
                                        <div class="form-check form-check-inline zoom">
                                            <input class="form-check-input" type="radio" name="over_angle_stop" id="over_angle_stop_off" value="0" <?php echo ($overAngleStop === '0') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="over_angle_stop_off"><?php echo $text['switch_off']; ?></label>
                                        </div>
                                        <div class="form-check form-check-inline zoom">
                                            <input class="form-check-input" type="radio" name="over_angle_stop" id="over_angle_stop_on" value="1" <?php echo ($overAngleStop === '1') ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="over_angle_stop_on"><?php echo $text['switch_on']; ?></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['direction']; ?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom" style="margin-right:18px;">
                                        <input class="form-check-input" type="radio" name="StepDirection" id="StepDirection_cw" value="1" <?php echo ($stepDirection === '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="direction_cw"><?php echo $text['CW']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="StepDirection" id="StepDirection_ccw" value="0" <?php echo ($stepDirection === '0') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="direction_ccw"><?php echo $text['CCW']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 t1"><?php echo $text['Delay Time']; ?> (<?php echo $text['Second']; ?>):</div>
                                <div class="col-5 t2">
                                    <input id="StepDelay" class="t2 form-control small-input" value="<?php echo htmlspecialchars($stepDelay); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row form-row speed-inline-row">
                                <div class="form-label-col t1">
                                    <?php echo $text['Run_Down_Speed']; ?>:
                                </div>
                                <div class="form-input-col t2">
                                    <input id="StepRPM"
                                           class="t2 form-control small-input fixed-input"
                                           value="<?php echo htmlspecialchars($stepRPM); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row" style="display:none;">
                                <div class="col-6 t1"><?php echo $text['Acceleration_text']; ?>:</div>
                                <div class="col-5 t2">
                                    <input id="k_value" class="t2 form-control small-input" value="<?php echo htmlspecialchars($kValue); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row form-row form-row-top">
                                <div class="form-label-col t1">
                                    <?php echo $text['Joint_Offset']; ?> (<?php echo $text[$data['torque_unit']]; ?>):
                                </div>

                                <div class="form-input-col t2 joint-offset-wrap">
                                    <label class="radio-inline" for="join_offset_plus">
                                        <input class="form-check-input" type="radio" name="StepTorqueOffsetSign" id="join_offset_plus" value="43" <?php echo ($stepTorqueOffsetSign === '43') ? 'checked' : ''; ?>>
                                        <span><?php echo $text['Plus_text']; ?></span>
                                    </label>

                                    <label class="radio-inline" for="join_offset_minus">
                                        <input class="form-check-input" type="radio" name="StepTorqueOffsetSign" id="join_offset_minus" value="45" <?php echo ($stepTorqueOffsetSign === '45') ? 'checked' : ''; ?>>
                                        <span><?php echo $text['Minus_text']; ?></span>
                                    </label>

                                    <input id="StepTorqueOffset" class="t2 form-control small-input offset-input" value="<?php echo htmlspecialchars($stepTorqueOffset); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <hr style="border:1px solid #ccc; width:96%; margin:5px 0;">

                            <div class="row option-row">
                                <div class="col-6 t1 option-title"><?php echo $text['Threshold_Type']; ?>:</div>
                                <div class="col-6 t2 radio-group pretty-radio-group">
                                    <label class="form-check pretty-check" for="threshold_mode_off">
                                        <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_off" onclick="toggleStepTorqueTS()" value="0" <?php echo ($stepEnableThreshold === '0') ? 'checked' : ''; ?>>
                                        <span class="form-check-label"><?php echo $text['switch_off']; ?></span>
                                    </label>

                                    <label class="form-check pretty-check" for="threshold_mode_torque">
                                        <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_torque" onclick="toggleStepTorqueTS()" value="2" <?php echo ($stepEnableThreshold === '2') ? 'checked' : ''; ?>>
                                        <span class="form-check-label"><?php echo $text['torque']; ?></span>
                                    </label>

                                    <label class="form-check pretty-check" for="threshold_mode_angle">
                                        <input class="form-check-input" type="radio" name="StepEnableThreshold" id="threshold_mode_angle" onclick="toggleStepTorqueTS()" value="1" <?php echo ($stepEnableThreshold === '1') ? 'checked' : ''; ?>>
                                        <span class="form-check-label"><?php echo $text['angle']; ?></span>
                                    </label>
                                </div>
                            </div>

                            <div class="row form-row" id="threshold_block">
                                <div class="form-label-col t1" id="show_torque" style="display:block;">
                                    <?php echo $text['Threshold_Torque']; ?> (<?php echo $text[$data['torque_unit']]; ?>):
                                </div>

                                <div class="form-label-col t1" id="show_angle" style="display:none;">
                                    <?php echo $text['Threshold_Angle']; ?> :
                                </div>

                                <div class="form-input-col t2" id="StepTorqueTS_block">
                                    <input type="text"
                                           id="StepTorqueTS"
                                           name="StepTorqueTS"
                                           class="form-control form-control-sm fixed-input"
                                           value="<?php echo is_numeric($stepTorqueTS) ? htmlspecialchars($stepTorqueTS) : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <hr style="border:1px solid #ccc; width:96%; margin:5px 0;">

                            <div class="row option-row">
                                <div class="col-6 t1 option-title"><?php echo $text['Downshift']; ?>:</div>
                                <div class="col-6 t2 radio-group pretty-radio-group">
                                    <label class="form-check pretty-check" for="downshift_mode_off">
                                        <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_off" value="0" onclick="toggleDownShift()" <?php echo ($stepEnableDownShift === '0') ? 'checked' : ''; ?>>
                                        <span class="form-check-label"><?php echo $text['switch_off']; ?></span>
                                    </label>

                                    <label class="form-check pretty-check" for="downshift_mode_torque">
                                        <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_torque" value="2" onclick="toggleDownShift()" <?php echo ($stepEnableDownShift === '2') ? 'checked' : ''; ?>>
                                        <span class="form-check-label"><?php echo $text['torque']; ?></span>
                                    </label>

                                    <label class="form-check pretty-check" for="downshift_mode_angle">
                                        <input class="form-check-input" type="radio" name="StepEnableDownShift" id="downshift_mode_angle" value="1" onclick="toggleDownShift()" <?php echo ($stepEnableDownShift === '1') ? 'checked' : ''; ?>>
                                        <span class="form-check-label"><?php echo $text['angle']; ?></span>
                                    </label>
                                </div>
                            </div>

                            <div class="row form-row" id="downshift_block">
                                <div class="form-label-col t1" id="show_downshift_torque" style="display:block;">
                                    <?php echo $text['Downshift_Torque']; ?> (<?php echo $text[$data['torque_unit']]; ?>):
                                </div>

                                <div class="form-label-col t1" id="show_downshift_angle" style="display:none;">
                                    <?php echo $text['Downshift_Angle']; ?>:
                                </div>

                                <div class="form-input-col t2" id="StepTorqueDownShift_block">
                                    <input id="StepTorqueDownShift"
                                           class="form-control form-control-sm fixed-input"
                                           value="<?php echo is_numeric($stepTorqueDownShift) ? htmlspecialchars($stepTorqueDownShift) : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row form-row speed-inline-row" id="downshift_speed_block" style="display:none;">
                                <div class="form-label-col t1">
                                    <?php echo $text['Downshift_Speed']; ?>:
                                </div>

                                <div class="form-input-col t2">
                                    <input id="StepRPMDownShift"
                                           class="form-control form-control-sm fixed-input"
                                           value="<?php echo htmlspecialchars($stepRPMDownShift); ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                        </div>

                        <div style="text-align:center; padding:20px 12px 24px;">
                            <button id="button1"
                                    class="button w3-button w3-border w3-round-large"
                                    onclick="save_or_edit_step(<?php echo (int)($data['type'] == 'edit'); ?>)">
                                <?php echo $text['save']; ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once '../app/views/inc/include_spinner.php'; ?>
</div>

<?php if ($_SESSION['privilege'] != 'admin') { ?>
<script>
  $(document).ready(function () {
    disableAllButtonsAndInputs();
    var returnBtn = document.getElementById("back_btn");
    if (returnBtn) returnBtn.disabled = false;
  });
</script>
<?php } ?>

<?php require_once '../app/views/step/add_step_share.php'; ?>
<?php require APPROOT . 'views/inc/footer.php'; ?>

<style>
/* ===== 基本輸入框 ===== */
.form-control {
  width: 100% !important;
  display: block !important;
  box-sizing: border-box;
  min-width: 0;
}

.form-control.is-invalid {
  padding-right: inherit !important;
}

.is-invalid ~ .invalid-feedback {
  display: block !important;
}

/* ===== 頁面容器 ===== */
.container-ms {
  padding: 8px;
}

/* ===== top nav ===== */
.topnav {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 12px;
}

.topnav label {
  margin: 0;
  white-space: nowrap;
}

.topnav input[disabled] {
  height: 32px;
  font-size: 16px;
  text-align: center;
  background-color: #DDDDDD;
  border: 0;
  padding: 4px 8px;
  min-width: 90px;
}

#back_btn {
  height: 32px;
  padding: 0 10px;
  font-size: 14px;
}

/* ===== Scroll 區 ===== */
.new-container {
  padding-bottom: 16px;
}

.newStep-scrollbar {
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  max-height: calc(100vh - 180px);
  padding-bottom: 8px;
}

/* ===== Save Button ===== */
#button1 {
  display: inline-block !important;
  visibility: visible !important;
  opacity: 1 !important;
  width: 100%;
  max-width: 320px;
  height: 48px;
  font-size: 20px;
}

/* ===== 小輸入框 ===== */
#step_limit_hi_tor,
#step_limit_lo_tor,
#step_limit_hi_ang,
#step_limit_lo_ang {
  width: 64px !important;
  display: inline-block !important;
  text-align: center;
}

/* ===== window monitor row ===== */
#show_tor .ps-0,
#show_ang .ps-0 {
  display: flex !important;
  flex-wrap: wrap;
  align-items: center;
  gap: 8px 12px;
  padding-left: 0 !important;
}

/* ===== 避免文字拆字 ===== */
label, .col-3, .col-4, .col-6 {
  word-break: break-word;
  white-space: normal;
}

.form-check-label {
  word-break: keep-all;
  white-space: nowrap;
}

/* ===== Radio Group ===== */
.radio-group {
  display: flex;
  flex-wrap: wrap !important;
  align-items: center;
  justify-content: flex-start !important;
  gap: 8px 14px;
  padding-left: 0 !important;
  margin-left: 0 !important;
  max-width: 100%;
}

/* ===== Option 區塊 ===== */
.option-row {
  margin-top: 6px;
  margin-bottom: 10px;
}

.option-title {
  margin-bottom: 6px;
  font-weight: 600;
  text-align: left;
  padding-left: 12px;
}

.option-row .col-12 {
  padding-left: 0 !important;
  padding-right: 0 !important;
}

/* ===== 極簡 radio ===== */
.pretty-radio-group {
  display: flex;
  flex-wrap: wrap !important;
  align-items: center;
  justify-content: center !important;
  gap: 8px 14px;
  padding-left: 0 !important;
  margin-left: 0 !important;
}

.pretty-check {
  display: inline-flex !important;
  align-items: center;
  justify-content: flex-start !important;
  gap: 6px;
  margin: 0 !important;
  padding: 2px 0;
  min-width: auto;
  border: none !important;
  border-radius: 0 !important;
  background: transparent !important;
  box-shadow: none !important;
  white-space: nowrap;
}

.pretty-check .form-check-input {
  margin: 0 !important;
  flex: 0 0 auto;
}

.pretty-check .form-check-label {
  margin: 0 !important;
  font-size: 15px;
  line-height: 1.2;
  white-space: nowrap !important;
  word-break: keep-all !important;
  overflow-wrap: normal !important;
}

.pretty-radio-group label,
.pretty-radio-group .form-check-label,
.pretty-check span {
  white-space: nowrap !important;
  word-break: keep-all !important;
  overflow-wrap: normal !important;
}

/* ===== 表單左右對齊樣式 ===== */
.form-row {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.form-row-top {
  align-items: flex-start;
}

.form-label-col {
  width: 48%;
  padding-right: 10px;
  font-weight: 500;
}

.form-input-col {
  width: 52%;
}

.fixed-input {
  max-width: 130px;
}

/* ===== Speed 同一行 ===== */
.speed-inline-row {
  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 0;
}

.speed-inline-row .form-label-col {
  width: 48%;
  margin-bottom: 0;
}

.speed-inline-row .form-input-col {
  width: 52%;
}

.speed-inline-row .fixed-input {
  width: 100% !important;
  max-width: 130px;
}

/* ===== Threshold 同一行 ===== */
#threshold_block {
  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 0;
}

#threshold_block .form-label-col {
  width: 48%;
  margin-bottom: 0;
}

#threshold_block .form-input-col {
  width: 52%;
}

#StepTorqueTS {
  width: 100% !important;
  max-width: 130px !important;
}

/* ===== Downshift 同一行 ===== */
#downshift_block {
  display: flex !important;
  align-items: center !important;
  justify-content: flex-start !important;
  gap: 0;
}

#downshift_block .form-label-col {
  width: 48%;
  margin-bottom: 0;
}

#downshift_block .form-input-col {
  width: 52%;
}

#StepTorqueDownShift {
  width: 100% !important;
  max-width: 130px !important;
}

/* ===== Joint Offset ===== */
.joint-offset-wrap {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px 14px;
}

.radio-inline {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
  margin: 0;
}

.radio-inline input[type="radio"] {
  margin: 0;
}

.offset-input {
  width: 70px !important;
  max-width: 70px !important;
}

/* ===== 手機版 ===== */
@media (max-width: 767px) {
  .form-row {
    align-items: flex-start;
  }

  .form-label-col {
    width: 50%;
    padding-right: 8px;
  }

  .form-input-col {
    width: 50%;
  }

  .fixed-input {
    max-width: 126px;
  }

  .joint-offset-wrap {
    gap: 8px 12px;
  }

  .offset-input {
    width: 64px !important;
    max-width: 64px !important;
  }

  .pretty-radio-group {
    gap: 8px 12px;
  }

  .pretty-check {
    min-width: auto;
    padding: 2px 0;
  }

  .pretty-check .form-check-label {
    font-size: 14px;
  }

  .speed-inline-row {
    align-items: center !important;
  }

  .speed-inline-row .form-label-col {
    width: 50%;
    padding-right: 8px;
  }

  .speed-inline-row .form-input-col {
    width: 50%;
  }

  .speed-inline-row .fixed-input {
    max-width: 126px;
  }

  /* threshold 同一行 */
  #threshold_block {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 0;
  }

  #threshold_block .form-label-col {
    width: 50%;
    padding-right: 8px;
    margin-bottom: 0;
  }

  #threshold_block .form-input-col {
    width: 50%;
  }

  #StepTorqueTS {
    width: 100% !important;
    max-width: 126px !important;
  }

  /* downshift 同一行 */
  #downshift_block {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 0;
  }

  #downshift_block .form-label-col {
    width: 50%;
    padding-right: 8px;
    margin-bottom: 0;
  }

  #downshift_block .form-input-col {
    width: 50%;
  }

  #StepTorqueDownShift {
    width: 100% !important;
    max-width: 126px !important;
  }

  /* threshold 不要下推 */
  #show_torque,
  #show_angle {
    margin-bottom: 0 !important;
    font-weight: 500;
  }

  /* downshift 不要下推 */
  #show_downshift_torque,
  #show_downshift_angle {
    margin-bottom: 0 !important;
    font-weight: 500;
  }
}

/* ===== Desktop ===== */
@media (min-width: 768px) {
  .topnav {
    gap: 12px 16px;
  }

  #step_limit_hi_tor,
  #step_limit_lo_tor,
  #step_limit_hi_ang,
  #step_limit_lo_ang {
    width: 56px !important;
  }

  /* threshold 保持 flex，同一行 */
  #threshold_block {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
  }

  #threshold_block .form-label-col {
    width: 140px;
    padding-right: 10px;
    margin-bottom: 0;
  }

  #threshold_block .form-input-col {
    width: auto;
    flex: 1;
  }

  #StepTorqueTS {
    width: 130px !important;
    max-width: 130px !important;
  }

  /* downshift 保持 flex，同一行 */
  #downshift_block {
    display: flex !important;
    align-items: center !important;
    justify-content: flex-start !important;
  }

  #downshift_block .form-label-col {
    width: 140px;
    padding-right: 10px;
    margin-bottom: 0;
  }

  #downshift_block .form-input-col {
    width: auto;
    flex: 1;
  }

  #StepTorqueDownShift {
    width: 130px !important;
    max-width: 130px !important;
  }

  #button1 {
    width: 240px;
    font-size: 22px;
  }
}
</style>