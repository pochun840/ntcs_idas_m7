<div id="Connect_Setting" class="divMode" style="display: none">
    <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_connect_setting'];?></div>
            <div class="col t1"><?php echo $text['system_agent_ip'];?>:</div>
            <div class="row t2 border-bottom">
                <div class="col t2">
                    <form id="agent_ip" style="margin: 3px 0px; margin-left: 10%" method="post">
                        <input type="text" name="agent_server_ip" id="agent_server_ip" size="15" required class="t3 w3-submit w3-border w3-round">
                        <div class="invalid-feedback"></div><br>
                        <span><?php echo $text['system_agent_ip'];?> : <?php echo $data['agent_server_ip']; ?></span> 
                        <input type="button" value="Save" onclick="set_agent_ip()" class="all-btn w3-submit w3-border w3-round-large" style="float: right">
                    </form>
                </div>
            </div>

            <div class="col t1"><?php echo $text['system_agent_type'];?>:</div>
            <div class="row t2">
                <div class="col t2">
                    <form id="agent_type_form"  method="post" style="margin: 3px 0px; margin-left: 9%">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="agent_type" id="agent_type_0" value="0">
                            <label class="form-check-label" for="agent_type_0">None</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="agent_type" id="agent_type_1" value="1">
                            <label class="form-check-label" for="agent_type_1"><?php $text['system_agent_client'];?></label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="agent_type" id="agent_type_2" value="2" required>
                            <label class="form-check-label" for="agent_type_2">Server</label>
                        </div>
                        <input type="button" value="Save" onclick="set_agent_type()" class="all-btn w3-submit w3-border w3-round-large" style="float: right">
                </div>
                <div class="row">
                    <div class="col t2" style="margin: 3px 0px; margin-left: 9%">
                        <span>Client Status:<div id="c_status" style="display:inline-block;"></div></span>
                        <span>Server Status:<div id="s_status" style="display:inline-block;"></div></span>

                        <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px"  onclick="StatusCheck()"  ><?php echo $text['system_agent_check'];?></button>
                        <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px;" onclick="StatusCheck('start')"><?php echo $text['system_agent_start'];?></button>
                        <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px"  onclick="StatusCheck('stop')" ><?php echo $text['system_agent_stop'];?></button>
                    </div>
                </div>
    </div>
</div>