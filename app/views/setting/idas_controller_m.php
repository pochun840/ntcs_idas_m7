<style>
/* Controller Setting mobile layout. Scoped to this page only. */
@media (max-width: 768px) {
    #Controller_Setting {
        width: 100%;
        min-height: auto !important;
        padding: 14px 16px calc(28px + env(safe-area-inset-bottom));
        overflow-x: hidden !important;
        box-sizing: border-box;
    }
    #Controller_Setting > .col.t1 {
        width: 100%;
        padding: 0 0 14px !important;
        font-size: 19px;
        line-height: 1.35;
    }
    #Controller_Setting .setting_scrollbar,
    #Controller_Setting .setting_force-overflow {
        width: 100%;
        height: auto !important;
        min-height: 0 !important;
        max-height: none !important;
        overflow: visible !important;
    }
    #Controller_Setting .setting_force-overflow > .col.t1 {
        width: 100%;
        padding: 14px 0 9px !important;
        font-size: 17px;
        font-weight: 700;
    }
    #Controller_Setting .row.t2 {
        display: block;
        width: 100%;
        margin: 0 0 16px;
    }
    #Controller_Setting .row.t2 > .t1,
    #Controller_Setting .row.t2 > .t2 {
        width: 100%;
        max-width: none;
        flex: 0 0 100%;
        padding: 0;
    }
    #Controller_Setting .row.t2 > .t1 {
        margin-bottom: 7px;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.35;
    }
    #Controller_Setting input.form-control,
    #Controller_Setting select {
        display: block;
        width: 100% !important;
        max-width: none !important;
        min-height: 46px;
        padding: 9px 12px;
        border-radius: 6px;
        box-sizing: border-box;
        font-size: 16px;
    }
    #Controller_Setting input:disabled,
    #Controller_Setting input[readonly] {
        opacity: 1;
        color: #70777d;
        background: #eef1f3;
        -webkit-text-fill-color: #70777d;
    }
    #Controller_Setting .row.t2 > .t2 {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    #Controller_Setting .protocol-options {
        display: flex !important;
    }
    #Controller_Setting .form-check-inline {
        display: flex;
        align-items: center;
        width: calc(50% - 5px) !important;
        min-width: 0;
        min-height: 48px;
        margin: 0 !important;
        padding: 0 10px;
        border: 1px solid #cbd3da;
        border-radius: 7px;
        background: #fff;
        box-sizing: border-box;
    }
    #Controller_Setting .form-check-input {
        flex: 0 0 auto;
        width: 20px;
        height: 20px;
        margin: 0 8px 0 0;
    }
    #Controller_Setting .form-check-label {
        display: flex;
        align-items: center;
        width: 100%;
        min-height: 46px;
        margin: 0;
        font-size: 15px;
        cursor: pointer;
    }
    #Controller_Setting .protocol-options .form-check-inline {
        width: calc(33.333% - 7px) !important;
    }
    #Controller_Setting .protocol-server-port-hint {
        margin-top: 6px;
        color: #687078;
        font-size: 13px;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }
    #Controller_Setting hr.hr {
        margin: 24px 0 8px;
    }
    #Controller_Setting button.all-btn {
        width: 100%;
        min-height: 48px;
        padding: 10px 18px;
        border-radius: 7px;
        font-size: 17px;
        font-weight: 600;
        touch-action: manipulation;
    }
    #Controller_Setting button.all-btn:disabled {
        opacity: .65;
        cursor: wait;
    }
}

@media (max-width: 380px) {
    #Controller_Setting {
        padding-left: 12px;
        padding-right: 12px;
    }
    #Controller_Setting .protocol-options {
        display: block !important;
    }
    #Controller_Setting .form-check-inline {
        width: 100% !important;
    }
    #Controller_Setting .protocol-options .form-check-inline {
        width: 100% !important;
        margin-bottom: 8px !important;
    }
    #Controller_Setting .row.t2 > .t2 .form-check-inline:not(:last-child) {
        margin-bottom: 8px !important;
    }
}
</style>
<?php if (idas_is_icontroller()): ?>
<div id="Controller_Setting" class="divMode">
    <div class="col t1" style="font-weight: bold; padding-top: 1%;">
        <?php echo $text['controller_setting']; ?>
    </div>

    <div class="setting_scrollbar" id="style-setting">
        <div class="setting_force-overflow">

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_id']; ?>:</div>
                <div class="col t2">
                    <input type="hidden" id="control_id_old" value="<?php echo htmlspecialchars($data['controller_info']['device_id'] ?? '', ENT_QUOTES); ?>">
                    <input id="control_id"
                           name="control_id"
                           type="number"
                           max="255"
                           min="1"
                           maxlength="3"
                           value="<?php echo htmlspecialchars($data['controller_info']['device_id'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_name']; ?>:</div>
                <div class="col t2">
                    <input id="control_name"
                           name="control_name"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['device_name'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_diskfull_warning']; ?>:</div>
                <div class="col t2">
                    <input id="storage_warning"
                           name="storage_warning"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['storage_warning'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_torque_filter']; ?>:</div>
                <div class="col t2">
                    <input id="torque_filter"
                           name="torque_filter"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['torque_filter'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['torque_unit']; ?>:</div>
                <div class="col t2">
                    <select id="select_torque_unit" name="select_torque_unit">
                        <?php foreach($data['torque_unit'] as $k_unit => $v_unit){ ?>
                            <option value="<?php echo $k_unit; ?>"
                                <?php echo ($k_unit == ($data['controller_info']['torque_unit'] ?? '')) ? 'selected' : ''; ?>>
                                <?php echo $text[$v_unit]; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_language']; ?>:</div>
                <div class="col-4 t2">
                    <select id="select_language" name="select_language">
                        <?php foreach($data['lang_arr'] as $k_lang => $v_lang){ ?>
                            <option value="<?php echo $k_lang; ?>"
                                <?php echo ($k_lang == ($data['controller_info']['language'] ?? '')) ? 'selected' : ''; ?>>
                                <?php echo $v_lang; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            
            <?php
                $controller_modbus_type = isset($data['controller_info']['modbus_type']) ? (int)$data['controller_info']['modbus_type'] : 0;
                if (!in_array($controller_modbus_type, [0, 1, 2], true)) {
                    $controller_modbus_type = 0;
                }

                $idas_lang = $_COOKIE['language'] ?? ($_SESSION['language'] ?? 'zh-tw');
                $protocol_label = $text['communication_protocol']
                    ?? $text['system_communication_protocol']
                    ?? (($idas_lang === 'en-us') ? 'Communication Protocol' : (($idas_lang === 'zh-cn') ? '通讯协议' : '通訊協議'));
            ?>
           

<div class="row t2">
                <div class="col-6 t1"><?php echo $text['Circular Archive_text']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="circular_archive"
                               value="0"
                               <?php echo (($data['controller_info']['circular_archive'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label"><?php echo $text['switch_off']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="circular_archive"
                               value="1"
                               <?php echo (($data['controller_info']['circular_archive'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label"><?php echo $text['switch_on']; ?></label>
                    </div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_batch']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="counting_method"
                               id="dec"
                               value="1"
                               <?php echo (($data['controller_info']['counting_method'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="dec"><?php echo $text['system_inc']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="counting_method"
                               id="inc"
                               value="0"
                               <?php echo (($data['controller_info']['counting_method'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="inc"><?php echo $text['system_dec']; ?></label>
                    </div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Blackout Recovery_text']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="blackout_recovery"
                               id="blackout_recovery_off"
                               value="0"
                               <?php echo (($data['controller_info']['blackout_recovery'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="blackout_recovery_off"><?php echo $text['switch_off']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="blackout_recovery"
                               id="blackout_recovery_on"
                               value="1_1"
                               <?php echo (($data['controller_info']['blackout_recovery'] ?? '') == "1_1") ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="blackout_recovery_on"><?php echo $text['switch_on']; ?></label>
                    </div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_buzzer']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="buzzer_mode"
                               id="buzzer_mod_off"
                               value="0"
                               <?php echo (($data['controller_info']['buzzer_mode'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="buzzer_mod_off"><?php echo $text['switch_off']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="buzzer_mode"
                               id="buzzer_mod_on"
                               value="1"
                               <?php echo (($data['controller_info']['buzzer_mode'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="buzzer_mod_on"><?php echo $text['switch_on']; ?></label>
                    </div>
                </div>
            </div>

             <div class="row t2 protocol-setting-row">
                <div class="col-6 t1"><?php echo htmlspecialchars((string)$protocol_label, ENT_QUOTES, 'UTF-8'); ?>:</div>
                <div class="col t2 protocol-options">
                    <div class="col-4 form-check form-check-inline" style="white-space: nowrap;">
                        <input class="form-check-input" type="radio" name="modbus_type" id="modbus_type_tcp_m" value="0" <?php echo $controller_modbus_type === 0 ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="modbus_type_tcp_m">TCP</label>
                    </div>
                    <div class="col-4 form-check form-check-inline" style="white-space: nowrap;">
                        <input class="form-check-input" type="radio" name="modbus_type" id="modbus_type_rtu_m" value="1" <?php echo $controller_modbus_type === 1 ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="modbus_type_rtu_m">RTU</label>
                    </div>
                    <div class="form-check form-check-inline" style="white-space: nowrap;">
                        <input class="form-check-input" type="radio" name="modbus_type" id="modbus_type_op_m" value="2" <?php echo $controller_modbus_type === 2 ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="modbus_type_op_m">OP</label>
                    </div>
                </div>
            </div>

            <?php
                $controller_network_port = isset($data['network_setting']['port'])
                    ? (int)$data['network_setting']['port']
                    : 502;
                $controller_server_port = idas_protocol_server_port(
                    $controller_modbus_type,
                    $controller_network_port
                );
                $server_port_label = $text['server_port']
                    ?? $text['system_server_port']
                    ?? (($idas_lang === 'en-us') ? 'Server Port' : (($idas_lang === 'zh-cn') ? '服务器端口' : '伺服器連接埠'));
                $server_port_hint = ($idas_lang === 'en-us')
                    ? 'TCP default 502 (editable) / RTU keeps current port (editable) / OP fixed at 4545 (read-only)'
                    : (($idas_lang === 'zh-cn')
                        ? 'TCP 默认 502（可修改） / RTU 保留当前端口（可修改） / OP 固定 4545（不可修改）'
                        : 'TCP 預設 502（可修改） / RTU 保留目前連接埠（可修改） / OP 固定 4545（不可修改）');
            ?>
            <div class="row t2 protocol-server-port-row">
                <div class="col-6 t1"><?php echo htmlspecialchars((string)$server_port_label, ENT_QUOTES, 'UTF-8'); ?>:</div>
                <div class="col t2">
                    <input id="controller_server_port"
                           type="number"
                           class="t3 form-control protocol-server-port-input protocol-server-port-editable"
                           value="<?php echo (int)$controller_server_port; ?>"
                           data-rtu-port="<?php echo (int)$controller_network_port; ?>"
                           min="1"
                           max="65535"
                           inputmode="numeric">
                    <div class="protocol-server-port-hint"><?php echo htmlspecialchars($server_port_hint, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <div class="col t1" style="font-weight: bold; padding-top: 1%;">
                <?php echo $text['Downshift']; ?>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Downshift_Torque_temp']; ?>(%):</div>
                <div class="col-3 t2">
                    <input id="global_downshift_torque"
                           name="global_downshift_torque"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['global_downshift_torque'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Downshift_Speed_temp']; ?>(%):</div>
                <div class="col-3 t2">
                    <input id="global_downshift_speed"
                           name="global_downshift_speed"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['global_downshift_speed'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button class="all-btn w3-button w3-border w3-round-large"
                        id="downshift_save"
                        onclick="window.controller_save()">
                    <?php echo $text['save']; ?>
                </button>
            </div>

            <hr class="hr">

            <div class="col t1" style="font-weight: bold; padding-top: 1%;">
                <?php echo $text['Button_Access_With_Password_text']; ?>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Clear_Seq_Button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="clearseq_button_pwd"
                           name="clearseq_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['clearseq_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Clear_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="clear_button_pwd"
                           name="clear_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['clear_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Confirm_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="confirm_button_pwd"
                           name="confirm_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['confirm_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Enable_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="enable_button_pwd"
                           name="enable_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['enable_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Disable_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="disable_button_pwd"
                           name="disable_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['disable_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Skip_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="skip_button_pwd"
                           name="skip_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['skip_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button class="all-btn w3-button w3-border w3-round-large"
                        id="save_pwd"
                        onclick="save_pwd()">
                    <?php echo $text['save']; ?>
                </button>
            </div>

            <hr class="hr">

            <div class="col t1" style="font-weight: bold; padding-top: 1%;">
                <?php echo $text['Background_Color_text']; ?>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['OK_Sequence']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="okseqcolor"
                               id="okseqcolor_green"
                               value="0"
                               <?php echo (($data['controller_info']['okseqcolor'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="okseqcolor_green"><?php echo $text['green_text']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="okseqcolor"
                               id="okseqcolor_yellow"
                               value="1"
                               <?php echo (($data['controller_info']['okseqcolor'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="okseqcolor_yellow"><?php echo $text['yellow_text']; ?></label>
                    </div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['job_ok']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="okjobcolor"
                               id="okjobcolor_green"
                               value="0"
                               <?php echo (($data['controller_info']['okjobcolor'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="okjobcolor_green"><?php echo $text['green_text']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="okjobcolor"
                               id="okjobcolor_yellow"
                               value="1"
                               <?php echo (($data['controller_info']['okjobcolor'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="okjobcolor_yellow"><?php echo $text['yellow_text']; ?></label>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px; margin-bottom: 10px;">
                <button class="all-btn w3-button w3-border w3-round-large"
                        id="cc_save"
                        onclick="background_save()">
                    <?php echo $text['save']; ?>
                </button>
            </div>

        </div>
    </div>
</div>

<script>
function input_check_setting(argument) {

    let conditions = [
        { id: 'control_id', pattern: /^\d{0,4}$/, min: 1, max: 255 },
        { id: 'control_name', pattern: /^[a-zA-Z0-9_\u4E00-\u9FA5\-]+$/, min: null, max: null },
        { id: 'storage_warning', pattern: /^\d{0,4}$/, min: 50, max: 95 },
        { id: 'torque_filter', pattern: /^\d{1,3}(\.\d{1,6})?$/, min: 0.0, max: 200 },
        { id: 'global_downshift_torque', pattern: /^\d{0,5}?$/, min: 0, max: 1000 },
        { id: 'global_downshift_speed', pattern: /^\d{0,5}?$/, min: 0, max: 100 },
    ];

    let isFormValid = true;

    conditions.forEach(function(input) {
        var element = document.getElementById(input.id);
        if (!element) return;

        var value = element.value.trim();

        if(input.id != 'control_name'){
            var nextSibling = element.nextElementSibling;
            if (nextSibling) {
                nextSibling.innerHTML = input.min + ' ~ ' + input.max;
            }
        }

        if (value === "") {
            element.classList.add("is-invalid");
            isFormValid = false;
        } else if (!input.pattern.test(value)) {
            element.classList.add("is-invalid");
            isFormValid = false;
        } else if (input.min !== null && parseFloat(value) < input.min) {
            element.classList.add("is-invalid");
            isFormValid = false;
        } else if (input.max !== null && parseFloat(value) > input.max) {
            element.classList.add("is-invalid");
            isFormValid = false;
        } else {
            element.classList.remove("is-invalid");
        }
    });

    return isFormValid;
}
</script>
<?php else: ?>
<div id="Controller_Setting" class="divMode">
    <div class="col t1" style="font-weight: bold; padding-top: 1%;">
        <?php echo $text['controller_setting']; ?>
    </div>

    <div class="setting_scrollbar" id="style-setting">
        <div class="setting_force-overflow">

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_id']; ?>:</div>
                <div class="col t2">
                    <input type="hidden" id="control_id_old" value="<?php echo htmlspecialchars($data['controller_info']['device_id'] ?? '', ENT_QUOTES); ?>">
                    <input id="control_id"
                           name="control_id"
                           type="number"
                           max="255"
                           min="1"
                           maxlength="3"
                           value="<?php echo htmlspecialchars($data['controller_info']['device_id'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required
                           disabled>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_name']; ?>:</div>
                <div class="col t2">
                    <input id="control_name"
                           name="control_name"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['device_name'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                    <div class="invalid-feedback"></div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_diskfull_warning']; ?>:</div>
                <div class="col t2">
                    <input id="storage_warning"
                           name="storage_warning"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['storage_warning'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_torque_filter']; ?>:</div>
                <div class="col t2">
                    <input id="torque_filter"
                           name="torque_filter"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['torque_filter'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['torque_unit']; ?>:</div>
                <div class="col t2">
                    <select id="select_torque_unit" name="select_torque_unit">
                        <?php foreach($data['torque_unit'] as $k_unit => $v_unit){ ?>
                            <option value="<?php echo $k_unit; ?>"
                                <?php echo ($k_unit == ($data['controller_info']['torque_unit'] ?? '')) ? 'selected' : ''; ?>>
                                <?php echo $text[$v_unit]; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_language']; ?>:</div>
                <div class="col-4 t2">
                    <select id="select_language" name="select_language">
                        <?php foreach($data['lang_arr'] as $k_lang => $v_lang){ ?>
                            <option value="<?php echo $k_lang; ?>"
                                <?php echo ($k_lang == ($data['controller_info']['language'] ?? '')) ? 'selected' : ''; ?>>
                                <?php echo $v_lang; ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            
            <?php
                $controller_modbus_type = isset($data['controller_info']['modbus_type']) ? (int)$data['controller_info']['modbus_type'] : 0;
                if (!in_array($controller_modbus_type, [0, 1, 2], true)) {
                    $controller_modbus_type = 0;
                }

                $idas_lang = $_COOKIE['language'] ?? ($_SESSION['language'] ?? 'zh-tw');
                $protocol_label = $text['communication_protocol']
                    ?? $text['system_communication_protocol']
                    ?? (($idas_lang === 'en-us') ? 'Communication Protocol' : (($idas_lang === 'zh-cn') ? '通讯协议' : '通訊協議'));
            ?>
           

<div class="row t2">
                <div class="col-6 t1"><?php echo $text['Circular Archive_text']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="circular_archive"
                               value="0"
                               <?php echo (($data['controller_info']['circular_archive'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label"><?php echo $text['switch_off']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="circular_archive"
                               value="1"
                               <?php echo (($data['controller_info']['circular_archive'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label"><?php echo $text['switch_on']; ?></label>
                    </div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_batch']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="counting_method"
                               id="dec"
                               value="1"
                               <?php echo (($data['controller_info']['counting_method'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="dec"><?php echo $text['system_inc']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="counting_method"
                               id="inc"
                               value="0"
                               <?php echo (($data['controller_info']['counting_method'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="inc"><?php echo $text['system_dec']; ?></label>
                    </div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Blackout Recovery_text']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="blackout_recovery"
                               id="blackout_recovery_off"
                               value="0"
                               <?php echo (($data['controller_info']['blackout_recovery'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="blackout_recovery_off"><?php echo $text['switch_off']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="blackout_recovery"
                               id="blackout_recovery_on"
                               value="1_1"
                               <?php echo (($data['controller_info']['blackout_recovery'] ?? '') == "1_1") ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="blackout_recovery_on"><?php echo $text['switch_on']; ?></label>
                    </div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['system_buzzer']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="buzzer_mode"
                               id="buzzer_mod_off"
                               value="0"
                               <?php echo (($data['controller_info']['buzzer_mode'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="buzzer_mod_off"><?php echo $text['switch_off']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="buzzer_mode"
                               id="buzzer_mod_on"
                               value="1"
                               <?php echo (($data['controller_info']['buzzer_mode'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="buzzer_mod_on"><?php echo $text['switch_on']; ?></label>
                    </div>
                </div>
            </div>

            <div class="row t2 protocol-setting-row">
                <div class="col-6 t1"><?php echo htmlspecialchars((string)$protocol_label, ENT_QUOTES, 'UTF-8'); ?>:</div>
                <div class="col t2 protocol-options">
                    <div class="col-4 form-check form-check-inline" style="white-space: nowrap;">
                        <input class="form-check-input" type="radio" name="modbus_type" disabled="disabled" id="modbus_type_tcp_m" value="0" <?php echo $controller_modbus_type === 0 ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="modbus_type_tcp_m">TCP</label>
                    </div>
                    <div class="col-4 form-check form-check-inline" style="white-space: nowrap;">
                        <input class="form-check-input" type="radio" name="modbus_type" disabled="disabled" id="modbus_type_rtu_m" value="1" <?php echo $controller_modbus_type === 1 ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="modbus_type_rtu_m">RTU</label>
                    </div>
                    <div class="form-check form-check-inline" style="white-space: nowrap;">
                        <input class="form-check-input" type="radio" name="modbus_type" disabled="disabled" id="modbus_type_op_m" value="2" <?php echo $controller_modbus_type === 2 ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="modbus_type_op_m">OP</label>
                    </div>
                </div>
            </div>

            <?php
                $controller_network_port = isset($data['network_setting']['port'])
                    ? (int)$data['network_setting']['port']
                    : 502;
                $controller_server_port = $controller_network_port;
                $server_port_label = $text['server_port']
                    ?? $text['system_server_port']
                    ?? (($idas_lang === 'en-us') ? 'Server Port' : (($idas_lang === 'zh-cn') ? '服务器端口' : '伺服器連接埠'));
            ?>
            <div class="row t2 protocol-server-port-row">
                <div class="col-6 t1"><?php echo htmlspecialchars((string)$server_port_label, ENT_QUOTES, 'UTF-8'); ?>:</div>
                <div class="col t2">
                    <input id="controller_server_port"
                           type="number"
                           class="t3 form-control protocol-server-port-input"
                           value="<?php echo (int)$controller_server_port; ?>"
                           data-rtu-port="<?php echo (int)$controller_network_port; ?>"
                           readonly
                           aria-readonly="true"
                           inputmode="numeric">
                </div>
            </div>

            <div class="col t1" style="font-weight: bold; padding-top: 1%;">
                <?php echo $text['Downshift']; ?>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Downshift_Torque_temp']; ?>(%):</div>
                <div class="col-3 t2">
                    <input id="global_downshift_torque"
                           name="global_downshift_torque"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['global_downshift_torque'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Downshift_Speed_temp']; ?>(%):</div>
                <div class="col-3 t2">
                    <input id="global_downshift_speed"
                           name="global_downshift_speed"
                           maxlength="12"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['global_downshift_speed'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button class="all-btn w3-button w3-border w3-round-large"
                        id="downshift_save"
                        onclick="window.controller_save()">
                    <?php echo $text['save']; ?>
                </button>
            </div>

            <hr class="hr">

            <div class="col t1" style="font-weight: bold; padding-top: 1%;">
                <?php echo $text['Button_Access_With_Password_text']; ?>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Clear_Seq_Button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="clearseq_button_pwd"
                           name="clearseq_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['clearseq_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Clear_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="clear_button_pwd"
                           name="clear_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['clear_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Confirm_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="confirm_button_pwd"
                           name="confirm_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['confirm_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Enable_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="enable_button_pwd"
                           name="enable_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['enable_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Disable_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="disable_button_pwd"
                           name="disable_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['disable_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['Skip_button_text']; ?>:</div>
                <div class="col-3 t2">
                    <input id="skip_button_pwd"
                           name="skip_button_pwd"
                           maxlength="4"
                           type="text"
                           value="<?php echo htmlspecialchars($data['controller_info']['skip_button_pwd'] ?? '', ENT_QUOTES); ?>"
                           class="t3 form-control"
                           required>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px;">
                <button class="all-btn w3-button w3-border w3-round-large"
                        id="save_pwd"
                        onclick="save_pwd()">
                    <?php echo $text['save']; ?>
                </button>
            </div>

            <hr class="hr">

            <div class="col t1" style="font-weight: bold; padding-top: 1%;">
                <?php echo $text['Background_Color_text']; ?>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['OK_Sequence']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="okseqcolor"
                               id="okseqcolor_green"
                               value="0"
                               <?php echo (($data['controller_info']['okseqcolor'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="okseqcolor_green"><?php echo $text['green_text']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="okseqcolor"
                               id="okseqcolor_yellow"
                               value="1"
                               <?php echo (($data['controller_info']['okseqcolor'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="okseqcolor_yellow"><?php echo $text['yellow_text']; ?></label>
                    </div>
                </div>
            </div>

            <div class="row t2">
                <div class="col-6 t1"><?php echo $text['job_ok']; ?>:</div>
                <div class="col t2">
                    <div class="col-4 form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="okjobcolor"
                               id="okjobcolor_green"
                               value="0"
                               <?php echo (($data['controller_info']['okjobcolor'] ?? '') == 0) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="okjobcolor_green"><?php echo $text['green_text']; ?></label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input"
                               type="radio"
                               name="okjobcolor"
                               id="okjobcolor_yellow"
                               value="1"
                               <?php echo (($data['controller_info']['okjobcolor'] ?? '') == 1) ? 'checked="checked"' : ''; ?>>
                        <label class="form-check-label" for="okjobcolor_yellow"><?php echo $text['yellow_text']; ?></label>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 20px; margin-bottom: 10px;">
                <button class="all-btn w3-button w3-border w3-round-large"
                        id="cc_save"
                        onclick="background_save()">
                    <?php echo $text['save']; ?>
                </button>
            </div>

        </div>
    </div>
</div>

<script>
function input_check_setting(argument) {

    let conditions = [
        { id: 'control_id', pattern: /^\d{0,4}$/, min: 1, max: 255 },
        { id: 'control_name', pattern: /^[a-zA-Z0-9_\u4E00-\u9FA5\-]+$/, min: null, max: null },
        { id: 'storage_warning', pattern: /^\d{0,4}$/, min: 50, max: 95 },
        { id: 'torque_filter', pattern: /^\d{1,3}(\.\d{1,6})?$/, min: 0.0, max: 200 },
        { id: 'global_downshift_torque', pattern: /^\d{0,5}?$/, min: 0, max: 1000 },
        { id: 'global_downshift_speed', pattern: /^\d{0,5}?$/, min: 0, max: 100 },
    ];

    let isFormValid = true;

    conditions.forEach(function(input) {
        var element = document.getElementById(input.id);
        if (!element) return;

        var value = element.value.trim();

        if(input.id != 'control_name'){
            var nextSibling = element.nextElementSibling;
            if (nextSibling) {
                nextSibling.innerHTML = input.min + ' ~ ' + input.max;
            }
        }

        if (value === "") {
            element.classList.add("is-invalid");
            isFormValid = false;
        } else if (!input.pattern.test(value)) {
            element.classList.add("is-invalid");
            isFormValid = false;
        } else if (input.min !== null && parseFloat(value) < input.min) {
            element.classList.add("is-invalid");
            isFormValid = false;
        } else if (input.max !== null && parseFloat(value) > input.max) {
            element.classList.add("is-invalid");
            isFormValid = false;
        } else {
            element.classList.remove("is-invalid");
        }
    });

    return isFormValid;
}
</script>
<?php endif; ?>
