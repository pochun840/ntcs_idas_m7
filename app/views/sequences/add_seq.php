
<link rel="stylesheet" type="text/css" href="<?php echo URLROOT; ?>css/add_seq_step.css">

<div class="container-ms" id ="your_container_id">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo ($data['type'] == 'edit') ? $text['edit_seq'] : $text['new_seq']; ?></h3></td>
            </tr>
        </table>
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
                <label style="font-size:20px;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="8" maxlength="20" value="<?php echo $data['job_id'];?>" disabled
                style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:20px;color: #000; padding-left: 2%" for="seq_id"><?php echo $text['seq_id'];?> :</label>&nbsp;
                <input type="text" id="seq_id" name="seq_id" size="8" maxlength="20" value="<?php echo $data['seq_id'];?>" disabled
                style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <button id="back_btn" type="button" onclick="history.go(-1);"><?php echo $text['return']; ?></button>
            </div>


            <div class="new-container">
                <div class="row">
                    <div class="col-md-6">
                        <div class="row t2 mt-3">
                            <div class="col-3"><?php echo $text['seq_name'];?>:</div>
                            <div class="col-9">
                                <input id="SEQname" class="form-control" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['SEQname'] : 'SEQ-'.$data['next_seq_id']; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                     
                        <div class="row t2 mt-3">
                            <div class="col-3"><?php echo $text['tightening_repeat'];?>:</div>
                            <div class="col-9">
                                <input id="seq_repeat" class="form-control" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['seq_repeat'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3">
                            <div class="col-3"><?php echo $text['Timeout'];?> (sec):</div>
                            <div class="col-9">
                                <input id="timeout" class="form-control"  value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['timeout'] : ''; ?>">(0-60)
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-12 row t2 mt-3">
                            <div class="col-3"><?php echo "DT";?> (sec):</div>
                            <div class="col-9">
                                <input id="dt_time" class="form-control"  value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['dt_time'] : ''; ?>">(0-99)
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-12 row t2 mt-3">
                            <div class="col-3"><?php echo "TT";?> (sec):</div>
                            <div class="col-9">
                                <input id="tt_time" class="form-control"  value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['tt_time'] : ''; ?>">(0-6000)
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
                            <div class="col-3"><?php echo $text['OK_Sequence_Stop'];?>:</div>
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
                            <div class="col-3"><?php echo $text['Accumulate_Angle'];?>:</div>
                            <div class="col-9">
                               
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
                            <div class="col-3"><?php echo $text['Angle_Calculation'];?> (<?php echo $text['step'];?>):</div>
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

                       
                    </div>
                    
                    <div class="col-md-6 t2">
                        <div class="col-12 row t2 mt-3">
                            <div class="col-4 fw-bolder"><?php echo $text['Reverse'];?></div>
                        </div>

                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4"><?php echo $text['rev_count'];?>:</div>
                            <div class="col-8">
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

                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4"><?php echo $text['NG_Reverse'];?>:</div>
                            <div class="col-8">
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

                        <div class="col-12 row t2 mt-3 ps-4">
                            <div class="col-4"><?php echo $text['Reverse_mode'];?>:</div>
                            <div class="col-8">
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
                        <div class="col-12 row t2 mt-3 ps-4" id="div_speed">
                            <div class="col-4"><?php echo $text['reverse_rpm'];?> (rpm):</div>
                            <div class="col-8">
                                <input id="unscrew_rpm" class="form-control"  value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_rpm'] : ''; ?>">
                            </div>
                        </div>
                        <div class="col-12 row t2 mt-3 ps-4" id="div_torque_threshold">
                            <div class="col-4"><?php echo $text['Threshold_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                            <div class="col-8">
                                <input id="unscrew_torque_threshold"class="form-control" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_torque_threshold'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                          <div class="col-12 row t2 mt-3 ps-4" id="div_angle_threshold">
                            <div class="col-4"><?php echo $text['Threshold_Angle'];?> </div>
                            <div class="col-8">
                                <input id="unscrew_angle_threshold"class="form-control" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_angle_threshold'] : ''; ?>">
                                <div class="invalid-feedback"></div>
                            </div>
                        </div>

                        <div class="col-12 row t2 mt-3 ps-4" id="div_direction">
                            <div class="col-4"><?php echo $text['direction'];?>:</div>
                            <div class="col-8">
                                
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
                            <div class="col-4"><?php echo $text['Force'];?> (%):</div>
                            <div class="col-8">
                        
                                <div class="form-check form-check-inline col-md-3">
                                  <input class="form-check-input" type="radio" name="unscrew_forcemode" id="unscrew_forcemode_on" value="0"
                                  <?php  echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] >= 1 && $data['sequences']['unscrew_force'] <= 100) ? 'checked' : ''; ?> >
                                  <label class="form-check-label" for="force_on"><?php echo $text['switch_on']; ?></label>
                                  <input  id="unscrew_force"  class="form-control" value="<?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] >= 1 && $data['sequences']['unscrew_force'] <= 100) ? $data['sequences']['unscrew_force'] : ''; ?>" style="width: 50%!important;min-width: 50%!important;display: inline-block!important;">
                                  <br>
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






