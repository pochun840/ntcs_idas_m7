<div id="Connect_Setting" class="divMode" style="display: none; overflow-x: hidden;" >

    <div class="row">
        <div class="col t1" style="padding-left: 3%; font-weight: bold; padding-top: 1.5%;">
            <?php echo $text['system_connect_setting']; ?>
        </div>
    </div>

    <!-- Agent IP -->
    <div class="row t2 align-items-center">
        <div class="col-3 t1"><?php echo $text['system_agent_ip'];?>:</div>
        <div class="col">
            <div style="display:flex; align-items:flex-start; gap:12px; width:100%; max-width:100%; flex-wrap:wrap;">
                <div style="flex:0 1 420px; max-width:420px; min-width:220px;">
                    <input type="text"
                        name="agent_server_ip"
                        id="agent_server_ip"
                        size="15"
                        value='<?php echo $data['agent_server_ip'];?>'
                        required
                        class="form-control"
                        style="width:100%;">
                    <div class="invalid-feedback"></div>
                </div>

                <input type="button"
                    onclick="agent_ip_save()"
                    value="<?php echo $text['save']; ?>"
                    class="all-btn w3-submit w3-border w3-round-large"
                    style="flex:0 0 auto; white-space:nowrap;">
            </div>
        </div>
    </div>

    <!-- Agent Type -->
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_agent_type'];?>:</div>
        <div class="col">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="agent_type" id="agent_type_0" value="0" <?php if($data['agent_type'] == 0){ echo "checked";} ?> >
                    <label class="form-check-label" for="agent_type_0"><?php echo $text['system_agent_none'];?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="agent_type" id="agent_type_1" value="1" <?php if($data['agent_type'] == 1){ echo "checked";} ?>>
                    <label class="form-check-label" for="agent_type_1"><?php echo $text['system_agent_client'];?></label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="agent_type" id="agent_type_2" value="2" <?php if($data['agent_type'] == 2){ echo "checked";} ?>>
                    <label class="form-check-label" for="agent_type_2"><?php echo $text['system_agent_server'];?></label>
                </div>

                <input type="button" onclick="agent_type_save()" value="<?php echo $text['save']; ?>"
                class="all-btn w3-submit w3-border w3-round-large" >
        </div>
    </div>

    <!-- Status -->
    <div class="row t2">
        <div class="col-3 t1"></div>
        <div class="col">
            <div style="margin-bottom: 5px;">
                <span><?php echo $text['system_client_status'];?>: </span><span id="c_status" style="display:inline-block;"></span>&nbsp;&nbsp;
                <span><?php echo $text['system_server_status'];?>: </span><span id="s_status" style="display:inline-block;"></span>
 
                <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px" onclick="StatusCheck()"><?php echo $text['system_agent_check'];?></button>
                <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px" onclick="StatusCheck('start')"><?php echo $text['system_agent_start'];?></button>
                <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px" onclick="StatusCheck('stop')"><?php echo $text['system_agent_stop'];?></button>
 
            </div>
        </div>
    </div>
</div>
