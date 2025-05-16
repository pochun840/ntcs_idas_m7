<link rel="stylesheet" type="text/css" href="<?php echo URLROOT; ?>css/target_torque_angle.css">
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
    width: 130px; /* 縮小寬度 */
    height: 2rem; /* 固定高度 */
}

.custom-style {
    font-size: 12px;
    margin-right: 5px;
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
            <div class="topnav" style="display: flex; justify-content: center;">
                <div class="info-box" style="border: 1px solid #ccc; padding: 10px; display: flex; align-items: center; background-color: #FFFFFF;">
                    <div class="info-item" style="display: flex; align-items: center; margin-right: 10px;">
                        <div class="info-label" style="font-size: 2vmin; margin-right: 5px;">
                            <?php echo $text['job_id']; ?> :
                        </div>
                        <div class="info-value">
                            <input type="text" id="job_id" name="job_id" value="<?php echo $data['job_id']; ?>" disabled style="width: 80px; height: 20px; font-size: 2vmin; text-align: center; background-color: #ddd; border: 1px solid #ccc; box-sizing: border-box; padding: 5px;">
                        </div>
                    </div>
                    <div class="info-item" style="display: flex; align-items: center; margin-right: 10px;">
                        <div class="info-label" style="font-size: 2vmin; margin-right: 5px;">
                            <?php echo $text['seq_id']; ?> :
                        </div>
                        <div class="info-value">
                            <input type="text" id="seq_id" name="seq_id" value="<?php echo $data['seq_id']; ?>" disabled style="width: 80px; height: 20px; font-size: 2vmin; text-align: center; background-color: #ddd; border: 1px solid #ccc; box-sizing: border-box; padding: 5px;">
                        </div>
                    </div>
                    
                    <div class="button-container" style="margin-left: auto;">
                        <button id="return" onclick="history.go(-1);" style="background-color: #dc3545; color: white; border: none; padding: 8px 16px; text-align: center; text-decoration: none; display: inline-block; font-size: 16px; cursor: pointer; border-radius: 5px;">
                            <?php echo $text['return']; ?>
                        </button>
                    </div>
                </div>
            </div>

            <div class="container" style="max-width: none;background-color: #F2F1F1;">
                <div class="row">
                    <div class="col-md-6 t2">
                    <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                        <div class="col-5" ><?php echo $text['seq_name'];?>:</div>
                        <div class="col-7">
                            <input id="SEQname" class="form-control small-input" style="width: 40%;"  value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['SEQname'] : ''; ?>">
                            <div class="invalid-feedback"></div>
                        </div>
                    </div>

                        <hr style="border: 1px solid #ccc; width: 100%; margin: 20px 0;">

                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['tightening_repeat'];?>:</div>
                            <div class="col-7">
                                <input id="seq_repeat"  class="form-control small-input" style="width: 60%;"   value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['seq_repeat'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Timeout'];?> (sec):</div>
                            <div class="col-7">
                                <input id="timeout" class="form-control small-input" style="idth: 60%;"  value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['timeout'] : ''; ?>">(0-60)
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo "DT";?> (sec):</div>
                            <div class="col-7">
                                <input id="dt_time" class="form-control small-input" style="idth: 60%;"  value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['timeout'] : ''; ?>">(0-60)
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo "TT";?> (sec):</div>
                            <div class="col-7">
                                <input id="tt_time" class="form-control small-input" style="idth: 60%;"  value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['timeout'] : ''; ?>">(0-60)
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div  class="col-5"><?php echo $text['OK-Sequence'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['OK_Sequence_Stop'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['rev_count'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['NG_Stop'];?> (0-9):</div>
                            <div class="col-7">
                                <select id="ng_stop" class="form-control small-input" style="width: 60%;" >
                                <?php 
                                    for ($i = 0; $i <= 9; $i++) {
                                        echo '<option value="' . $i . '" ' . (($data['type'] == 'edit' && $data['sequences']['ng_stop'] == $i) ? 'selected' : '') . '>' . $i . '</option>';
                                    }
                                ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['NG_Reverse'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Accumulate_Angle'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Angle_Calculation'];?> (<?php echo $text['step'];?>):</div>
                            <div class="col-7">
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
                        <hr style="border: 1px solid #ccc; width: 100%; margin: 20px 0;">
                    </div>
                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3">
                            <div class="col-4 fw-bolder"><?php echo $text['Reverse'];?></div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;">
                            <div class="col-5"><?php echo $text['Reverse_mode'];?>:</div>
                            <div class="col-7">
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_auto" value="0" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 0) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="unscrew_mode_auto"><?php echo $text['Auto_text'] ; ?></label>
                                </div>
                                <div class="form-check form-check-inline ">
                                  <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_custom" value="1" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 1) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="unscrew_mode_custom"><?php echo $text['Custom_text']; ?></label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;"  id="div_speed">
                            <div class="col-5"><?php echo $text['reverse_rpm'];?> (rpm):</div>
                            <div class="col-7">
                                <input id="unscrew_rpm" class="form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_rpm'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;" id="div_torque_threshold">
                            <div class="col-5"><?php echo $text['Threshold_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                            <div class="col-7">
                                <input id="unscrew_torque_threshold"class="form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_torque_threshold'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;" id="div_direction">
                            <div class="col-5"><?php echo $text['direction'];?>:</div>
                            <div class="col-7">
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
                        <div class="col-12 row t2 mt-3" style="font-size: 13px; margin-right: 5px;" id="div_force">
                            <div class="col-5"><?php echo $text['Force'];?> (%):</div>
                            <div class="col-7">
    
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio" name="unscrew_forcemode" id="unscrew_forcemode_on" value="0"
                                  <?php  echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] >= 1 && $data['sequences']['unscrew_force'] <= 100) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="force_on"><?php echo $text['switch_on']; ?></label>
                                  <input  id="unscrew_force"  class="form-control small-input" value="<?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] >= 1 && $data['sequences']['unscrew_force'] <= 100) ? $data['sequences']['unscrew_force'] : ''; ?>" style="width: 60%!important;min-width: 60%!important;display: inline-block!important;">
                                  <div class="invalid-feedback"></div>
                                </div>
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio" name="unscrew_forcemode" id="unscrew_forcemode_unlimit" value="1" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force']  > 101) ? 'checked' : ''; ?>  >
                                  <label class="form-check-label" for="force_unlimit"><?php echo $text['Unlimited_text']; ?></label>
                                </div>
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio"  name="unscrew_forcemode" id="unscrew_forcemode_off" value="2" 
                                  <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] == 0) ? 'checked' : ''; ?>  >
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

       <!-- 加载動畫 OP -->
       <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->

    
</div>


<?php require_once '../app/views/sequences/add_seq_share.php';?>