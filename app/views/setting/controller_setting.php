<div id="Controller_Setting" class="divMode">
    <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['controller_setting'];?></div>
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_id'];?>:</div>
        <div class="col-3 t2">
            <input type="hidden" id="control_id_old" value="<?php echo htmlspecialchars($data['controller_info']['device_id'] ?? '', ENT_QUOTES); ?>">
            <input id="control_id" name="control_id" type="number" max=255  min=1 maxlength="3" value="<?php echo isset($data['controller_info']['device_id']) ? $data['controller_info']['device_id'] : ''; ?>" class="t3 form-control"  required  disabled>
        </div>
    </div>    
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_name'];?>:</div>
        <div class="col-3 t2">
            <input id="control_name" name="control_name" maxlength="12" type="text" value="<?php echo isset($data['controller_info']['device_name']) ? $data['controller_info']['device_name'] : ''; ?>" class="t3 form-control"  required>
            <div class="invalid-feedback"></div>
        </div>
        
    </div>    

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_diskfull_warning'];?>:</div>
        <div class="col-3 t2">
            <input id="storage_warning" name="storage_warning" maxlength="12" type="text" value="<?php echo isset($data['controller_info']['storage_warning']) ? $data['controller_info']['storage_warning'] : ''; ?>" class="t3 form-control"  required>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_torque_filter'];?>:</div>
        <div class="col-3 t2">
            <input id="torque_filter" name="torque_filter" maxlength="12" type="text" value="<?php echo isset($data['controller_info']['torque_filter']) ? $data['controller_info']['torque_filter'] : ''; ?>" class="t3 form-control"  required>
            
        </div>
    </div>
   
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['torque_unit'];?>:</div>
        <div class="col t2">
            <select id="select_torque_unit" name="select_torque_unit">
                <?php foreach($data['torque_unit'] as $k_unit =>$v_unit){?>
                    <option value="<?php echo $k_unit; ?>" 
                        <?php echo ($k_unit == $data['controller_info']['torque_unit']) ? 'selected' : ''; ?>>
                    <?php echo $text[$v_unit]; ?>
                    </option>
                <?php } ?>
            </select>
        </div>
    </div> 

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_language'];?>:</div>
        <div class="col t2">
        
        <select id="select_language" name="select_language">
            <?php foreach($data['lang_arr'] as $k_lang => $v_lang) { ?>
                <option value="<?php echo $k_lang; ?>" 
                        <?php echo ($k_lang == $data['controller_info']['language']) ? 'selected' : ''; ?>>
                    <?php echo $v_lang; ?>
                </option>
            <?php } ?>
        </select>
        </div>
    </div> 

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Circular Archive_text'];?>:</div>
        <div class="col t2" >
            <div class="col-1 form-check form-check-inline">
                <input class="form-check-input" type="radio" id="circular_archive_off" name="circular_archive"  value="0"  <?php echo $data['controller_info']['circular_archive'] == 0 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="circular_archive_off"><?php echo $text['switch_off'];?></label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" id="circular_archive_on" name="circular_archive"  value="1"  <?php echo $data['controller_info']['circular_archive'] == 1 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="circular_archive_on"><?php echo $text['switch_on'];?></label>
            </div>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_batch'];?>:</div>
        <div class="col t2" >
            <div class="col-1 form-check form-check-inline" style='white-space: nowrap;' >
                <input class="form-check-input" type="radio" name="counting_method" id="dec" value="1"  <?php echo $data['controller_info']['counting_method'] == 1 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="dec"><?php echo $text['system_inc'];?></label>
            </div>
            <div class="form-check form-check-inline" style='white-space: nowrap;' >
                <input class="form-check-input" type="radio" name="counting_method" id="inc" value="0"  <?php echo $data['controller_info']['counting_method'] == 0 ? 'checked="checked"' : ''; ?> >
                <label class="form-check-label" for="inc"><?php echo $text['system_dec'];?></label>
            </div>
        </div>
    </div>
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Blackout Recovery_text'];?>:</div>
        <div class="col t2">
            <div class="col-1 form-check form-check-inline">
                <input class="form-check-input" type="radio" name="blackout_recovery" id="blackout_recovery_off" value="0"  <?php echo $data['controller_info']['blackout_recovery'] == 0 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="blackout_recovery_off"><?php echo $text['switch_off'];?></label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="blackout_recovery" id="blackout_recovery_on" value="1_1"  <?php echo $data['controller_info']['blackout_recovery'] == "1_1" ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="blackout_recovery_on"><?php echo $text['switch_on'];?></label>
            </div>
        </div>
    </div>
    
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_buzzer'];?>:</div>
        <div class="col t2">
            <div class="col-1 form-check form-check-inline">
                <input class="form-check-input" type="radio" name="buzzer_mode" id="buzzer_mod_off" value="0"  <?php echo $data['controller_info']['buzzer_mode'] == 0 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="buzzer_mod_off"><?php echo $text['switch_off'];?></label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="buzzer_mode" id="buzzer_mod_on" value="1"  <?php echo $data['controller_info']['buzzer_mode'] == 1 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="buzzer_mod_on"><?php echo $text['switch_on'];?></label>
            </div>
        </div>
    </div>
 

    <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['Downshift'];?></div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Downshift_Torque_temp'];?>(%):</div>
        <div class="col-3 t2">
            <input id="global_downshift_torque" name="global_downshift_torque" maxlength="12" type="text" value="<?php echo isset($data['controller_info']['global_downshift_torque']) ? $data['controller_info']['global_downshift_torque'] : ''; ?>" class="t3 form-control"  required>
           
        </div>
    </div>
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Downshift_Speed_temp'];?>(%):</div>
        <div class="col-3 t2">
            <input id="global_downshift_speed" name="global_downshift_speed" maxlength="12" type="text" value="<?php echo isset($data['controller_info']['global_downshift_speed']) ? $data['controller_info']['global_downshift_speed'] : ''; ?>" class="t3 form-control"  required>
      
        </div>
    </div>

    <div style="text-align: center;margin-top: 30px;">
        <button class="all-btn w3-button w3-border w3-round-large" id="downshift_save" onclick="controller_save()"><?php echo $text['save'];?></button>
    </div>
    <hr class="hr">

    <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['Button_Access_With_Password_text'];?></div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Clear_Seq_Button_text'];?>:</div>
        <div class="col-3 t2">
            <input id="clearseq_button_pwd" name="clearseq_button_pwd" maxlength="4" type="text" value="<?php echo isset($data['controller_info']['clearseq_button_pwd']) ? $data['controller_info']['clearseq_button_pwd'] : ''; ?>"  class="t3 form-control"  required>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Clear_button_text'];?>:</div>
        <div class="col-3 t2">
            <input id="clear_button_pwd" name="clear_button_pwd" maxlength="4" type="text" value="<?php echo isset($data['controller_info']['clear_button_pwd']) ? $data['controller_info']['clear_button_pwd'] : ''; ?>" class="t3 form-control"  required>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Confirm_button_text'];?>:</div>
        <div class="col-3 t2">
            <input id="confirm_button_pwd" name="confirm_button_pwd" maxlength="4" type="text" value="<?php echo isset($data['controller_info']['confirm_button_pwd']) ? $data['controller_info']['confirm_button_pwd'] : ''; ?>" class="t3 form-control"  required>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Enable_button_text'];?>:</div>
        <div class="col-3 t2">
            <input id="enable_button_pwd" name="enable_button_pwd" maxlength="4" type="text" value="<?php echo isset($data['controller_info']['enable_button_pwd']) ? $data['controller_info']['enable_button_pwd'] : ''; ?>" class="t3 form-control"  required>
        </div>
    </div>


    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Disable_button_text'];?>:</div>
        <div class="col-3 t2">
            <input id="disable_button_pwd" name="disable_button_pwd" maxlength="4" type="text" value="<?php echo isset($data['controller_info']['disable_button_pwd']) ? $data['controller_info']['disable_button_pwd'] : ''; ?>" class="t3 form-control"  required>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['Skip_button_text'];?>:</div>
        <div class="col-3 t2">
            <input id="skip_button_pwd" name="skip_button_pwd" maxlength="4" type="text" value="<?php echo isset($data['controller_info']['skip_button_pwd']) ? $data['controller_info']['skip_button_pwd'] : ''; ?>" class="t3 form-control"  required>
        </div>
    </div>

    <div style="text-align: center;margin-top: 30px;">
        <button class="all-btn w3-button w3-border w3-round-large" id="save_pwd" onclick="save_pwd()"><?php echo $text['save'];?></button>
    </div>
    <hr class="hr">

    <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['Background_Color_text'];?></div>

    
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['OK_Sequence'];?>:</div>

        <!-- 用 d-flex 讓兩顆 radio 同行排列 -->
        <div class="col t2 d-flex align-items-center">
            <div class="form-check form-check-inline me-3">
                <input class="form-check-input" type="radio"
                    name="okseqcolor" id="okseqcolor_green" value="0"
                    <?php echo $data['controller_info']['okseqcolor'] == 0 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="okseqcolor_green">
                    <?php echo $text['green_text']; ?>
                </label>
            </div>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio"
                    name="okseqcolor" id="okseqcolor_yellow" value="1"
                    <?php echo $data['controller_info']['okseqcolor'] == 1 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="okseqcolor_yellow">
                    <?php echo $text['yellow_text']; ?>
                </label>
            </div>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['job_ok'];?>:</div>

        <div class="col t2 d-flex align-items-center">
            <div class="form-check form-check-inline me-3">
                <input class="form-check-input" type="radio"
                    name="okjobcolor" id="okjobcolor_green" value="0"
                    <?php echo $data['controller_info']['okjobcolor'] == 0 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="okjobcolor_green">
                    <?php echo $text['green_text']; ?>
                </label>
            </div>

            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio"
                    name="okjobcolor" id="okjobcolor_yellow" value="1"
                    <?php echo $data['controller_info']['okjobcolor'] == 1 ? 'checked="checked"' : ''; ?>>
                <label class="form-check-label" for="okjobcolor_yellow">
                    <?php echo $text['yellow_text']; ?>
                </label>
            </div>
        </div>
    </div>

    <div style="text-align: center;margin-top: 30px; margin-bottom:10px">
        <button class="all-btn w3-button w3-border w3-round-large" id="cc_save" onclick="background_save()"><?php echo $text['save'];?></button>
    </div>

</div>

