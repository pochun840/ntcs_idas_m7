<div id="Connect_Setting" class="divMode" style="display: none; overflow-x: hidden;" >

    <div class="row">
        <div class="col t1" style="padding-left: 3%; font-weight: bold; padding-top: 1.5%; font-size: 1.1em;">
            <?php echo $text['system_connect_setting']; ?>
        </div>
    </div>

    <!-- Agent IP -->
    <div class="row t2 align-items-center" style="margin: 10px 0;">
        <div class="col-3 t1">Agent IP:</div>
        <div class="col">
            <form id="agent_ip" class="form-inline" method="post">
                <input type="text" name="agent_server_ip" id="agent_server_ip" size="15"
                    required class="t3 w3-submit w3-border w3-round">
                <span style="margin-left: 10px;"></span>
                <input type="submit" value="<?php echo $text['save']; ?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right;">
            </form>
        </div>
    </div>

    <!-- Agent Type -->
    <div class="row t2" style="margin: 10px 0;">
        <div class="col-3 t1">Agent Type:</div>
        <div class="col">
            <form id="agent_type_form" method="post">
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="agent_type" id="agent_type_0" value="0">
                    <label class="form-check-label" for="agent_type_0">None</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="agent_type" id="agent_type_1" value="1">
                    <label class="form-check-label" for="agent_type_1">Client</label>
                </div>
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="agent_type" id="agent_type_2" value="2" required>
                    <label class="form-check-label" for="agent_type_2">Server</label>
                </div>

                <input type="button" onclick="set_agent_type()" value="<?php echo $text['save']; ?>"
                    class="all-btn w3-submit w3-border w3-round-large" style="float: right;">
            </form>
        </div>
    </div>

    <!-- Status -->
    <div class="row t2" style="margin: 10px 0;">
        <div class="col-3 t1"></div>
        <div class="col">
            <div style="margin-bottom: 5px;">
                <span>Client Status: </span><span id="c_status" style="display:inline-block;"></span>&nbsp;&nbsp;
                <span>Server Status: </span><span id="s_status" style="display:inline-block;"></span>
            </div>

            <div>
                <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px" onclick="StatusCheck()">Check</button>
                <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px" onclick="StatusCheck('start')">START</button>
                <button class="all-btn w3-button w3-border w3-round-large" style="margin: 5px" onclick="StatusCheck('stop')">STOP</button>
            </div>
        </div>
    </div>
</div>
