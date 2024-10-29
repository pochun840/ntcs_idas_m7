<?php require APPROOT . 'views/inc/header.php'; ?>

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/w3.css" type="text/css">
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tool.css" type="text/css">



<div class="container">
    <div class="w3-text-white w3-center" style="padding: 10px">
        <table>
            <tr id="header">
                <td width="100%">
                    <h3><?php echo $text['system_connect_setting']; ?></h3>
                </td>
                <td>
                    <button class="w3-btn w3-round-large" style="height:50px;padding: 0" onclick="window.location.href='./?url=Dashboards'"> <img src="../public/img/btn_home.png"></button>
                </td>
            </tr>
        </table>
    </div>

    <div class="table-responsive" style=" padding: 10px; ">
        <form id="edit_max_link" onsubmit="set_max_link();return false;" method="post" style=" padding: 10px; background-color: #f1f1f1!important; " >
            <div style=" font-weight: bolder; "><?php echo $text['system_connect_number']; ?></div>
            <input type="text"name="max_user"id="max_user"inputmode="numeric"pattern="[0-9]*" min='1' size="20" maxlength="15" required class="w3-submit w3-border" style="font-size: 14px; height: 32px; padding: 5px;" required>&nbsp;
            <input type="submit" value="<?php echo $text['save']; ?>" style="font-size: 16px; height: 35px; width: 80px" class="w3-submit w3-border w3-round-large">
            <span><?php echo $text['system_connect_max_number']; ?>：<?php echo $data['max_user']; ?></span>
        </form>
    </div>

    <div class="table-responsive" style=" padding: 10px; ">
        <form id="edit_guest_password" onsubmit="set_guest_password();return false;" method="post" style=" padding: 10px; background-color: #f1f1f1!important; " >
            <div style=" font-weight: bolder; "><?php echo $text['system_connect_guest_pwd']; ?></div>
            <input type="password" id="new_password" placeholder="<?php echo $text['system_new_password']; ?>" size="20" maxlength="15" required class="w3-submit w3-border" style="font-size: 14px; height: 32px; padding: 5px;">&nbsp;
            <input type="password" id="comfirm_password" placeholder="<?php echo $text['system_confirm_password']; ?>" size="20" maxlength="15" required class="w3-submit w3-border" style="font-size: 14px; height: 32px">&nbsp;
            <input type="submit" value="<?php echo $text['save']; ?>" style="font-size: 16px; height: 35px; width: 80px" class="w3-submit w3-border w3-round-large">
        </form>
    </div>

    <div class="table-responsive" style=" padding: 10px; ">
        <form id="agent_ip" onsubmit="set_agent_ip();return false;" method="post" style=" padding: 10px; background-color: #f1f1f1!important; " >
            <div style=" font-weight: bolder; ">Agent IP</div>
            <input type="text"name="agent_server_ip"id="agent_server_ip" required class="w3-submit w3-border" style="font-size: 14px; height: 32px; padding: 5px;" required>&nbsp;
            <input type="submit" value="<?php echo $text['save']; ?>" style="font-size: 16px; height: 35px; width: 80px" class="w3-submit w3-border w3-round-large">
            <span>Agent IP：<?php echo $data['agent_server_ip']; ?></span>
        </form>
    </div>

    <div class="table-responsive" style=" padding: 10px; ">
        <form id="agent_type_form" onsubmit="set_agent_type();return false;" method="post" style=" padding: 10px; background-color: #f1f1f1!important; " >
            <div style=" font-weight: bolder; ">Agent Type</div>
            &nbsp;
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
            
            <input type="submit" value="<?php echo $text['save']; ?>" style="font-size: 16px; height: 35px; width: 80px" class="w3-submit w3-border w3-round-large">
            

        </form>
        <div style=" padding: 10px; background-color: #f1f1f1!important; ">

        <span style="padding-right: 10px;">Client Status：<div id="c_status" style="display:inline-block;"></div></span>
        <span>Server Status：<div id="s_status" style="display:inline-block;"></div></span>
        <button class="w3-submit w3-border w3-round-large" type="button" style="font-size: 16px; height: 35px; width: 80px" onclick="StatusCheck();">Check</button>
        <button class="w3-submit w3-border w3-round-large" type="button" style="font-size: 16px; height: 35px; width: 80px" onclick="StatusCheck('start');">START</button>
        <button class="w3-submit w3-border w3-round-large" type="button" style="font-size: 16px; height: 35px; width: 80px" onclick="StatusCheck('stop');">STOP</button>
        </div>

    </div>

    <form action="?url=Admins/DeleteSession" method="post" style=" padding: 10px; ">
        <div class="table-responsive">
        <table class="table">
            <tr class="table-success">
                <th class='' style=' text-align: center; '><?php echo $text['select']; ?></th>
                <th class=''><?php echo $text['system_connect_username']; ?></th>
                <th class=''>IP</th>
                <th class=''><?php echo $text['system_connect_timestamp']; ?></th>
            </tr>
            <?php
            
            foreach ($data['active_session'] as $row){
                echo "<tr class='table-light'>";
                echo "<td class='' style=' text-align: center; '><input type='checkbox' style='zoom: 2;' name='sessions[]' value='{$row['id']}'></td>";
                echo "<td class=''>{$row['username']}</td>";
                echo "<td class=''>{$row['ip']}</td>";
                echo "<td class=''>{$row['timestamp']}</td>";
                echo "</tr>";
            }

            ?>
        </table>
        </div>
        <?php
            if($_SESSION['privilege'] == 'admin'){
                echo "<input type='submit' value='{$text['delete_text']}'>";
            }
        ?>
    </form>

</div>


<script>
    $(document).ready(function() {
        let agent_type = <?php echo $data['agent_type']; ?>;
        document.getElementById("agent_type_"+agent_type).checked = true;
    });
    function set_max_link(argument) {
        let max_user = document.getElementById('max_user').value;

        $.ajax({ // 提醒
            type: "POST",
            data: { 'max_user': max_user },
            dataType: "json",
            url: "?url=Admins/EditMaxLink",
        }).done(function(notice) { //成功且有回傳值才會執行
            if (notice.error != '') {
                Swal.fire({ // DB sync notice
                    title: 'Error',
                    text: notice.error,
                })
            } else {
                Swal.fire('Saved!', '', 'success');
                document.getElementById('max_user').value = '';
                window.location = window.location.href;
            }
        });
    }


    function set_guest_password(){
        let pass1 = document.getElementById('new_password').value;
        let pass2 = document.getElementById('comfirm_password').value;
        let title = '<?php echo $text['system_password_notice']; ?>';
        if (pass1 === pass2 && pass1 != '') {
            $.ajax({
                type: "POST",
                data: { new_password: pass1, comfirm_password: pass2 },
                dataType: "json",
                url: "?url=Admins/EditGuestPwd",
                beforeSend: function() {
                    // $('#overlay').removeClass('hidden');
                },
            }).done(function(data) { //成功且有回傳值才會執行
                setTimeout(function() {
                    // $('#overlay').addClass('hidden');
                }, 1000);
                if (data.error != '') {
                    alert('sync error');
                }else{
                    Swal.fire('Saved!', '', 'success');
                    document.getElementById('new_password').value = '';
                    document.getElementById('comfirm_password').value = '';
                    // window.location = window.location.href;
                }
            });
        } else {
            Swal.fire('<?php echo $text['system_password_diff']; ?>', '', 'warning');
        }
    }

    function set_agent_ip(argument) {
        let agent_server_ip = document.getElementById('agent_server_ip').value;
        agent_server_ip = agent_server_ip.replace(/\s*/g,""); //去除空白
        $.ajax({ // 提醒
            type: "POST",
            data: { 'ip': agent_server_ip },
            dataType: "json",
            url: "?url=Admins/SetAgentIp",
        }).done(function(notice) { //成功且有回傳值才會執行
            if (notice.error != '') {
                Swal.fire({ // DB sync notice
                    title: 'Error',
                    text: notice.error,
                })
            } else {
                Swal.fire('Saved!', '', 'success');
                document.getElementById('agent_server_ip').value = '';
                window.location = window.location.href;
            }
        });
    }

    function set_agent_type(argument) {
        let agent_type = document.querySelector('input[name="agent_type"]:checked').value;
        $.ajax({ // 提醒
            type: "POST",
            data: { 'agent_type': parseInt(agent_type) },
            dataType: "json",
            url: "?url=Admins/SetAgentType",
        }).done(function(notice) { //成功且有回傳值才會執行
            if (notice.error != '') {
                Swal.fire({ // DB sync notice
                    title: 'Error',
                    text: notice.error,
                })
            } else {
                Swal.fire('Saved!', '', 'success');
                // document.getElementById('agent_server_ip').value = '';
                // window.location = window.location.href;
            }
        });
    }

    function StatusCheck(action) {
        let work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M9.001.666A8.336 8.336 0 0 0 .668 8.999c0 4.6 3.733 8.334 8.333 8.334s8.334-3.734 8.334-8.334S13.6.666 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Zm-1.666-4.833L5.168 8.666 4.001 9.833l3.334 3.333L14 6.499l-1.166-1.166-5.5 5.5Z" fill="#1E8E3E" fill-rule="evenodd"></path></svg>';
        let not_work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M11.16 5.666 9 7.824 6.843 5.666 5.668 6.841l2.158 2.158-2.158 2.159 1.175 1.175 2.158-2.159 2.159 2.159 1.175-1.175-2.159-2.159 2.159-2.158-1.175-1.175ZM9 .666A8.326 8.326 0 0 0 .668 8.999a8.326 8.326 0 0 0 8.333 8.334 8.326 8.326 0 0 0 8.334-8.334A8.326 8.326 0 0 0 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Z" fill="#D93025" fill-rule="evenodd"></path></svg>';

        let url = '?url=Admins/AgentTest';
        if(action == 'start'){
            url = '?url=Admins/StartAgent';
        }
        if(action == 'stop'){
            url = '?url=Admins/CloseAgent';
        }

        $.ajax({ // 提醒
            type: "POST",
            data: { },
            dataType: "json",
            url: url,
            beforeSend: function() {
                $('#overlay').removeClass('hidden');
            },
        }).done(function(result) { //成功且有回傳值才會執行
            $('#overlay').addClass('hidden');
            if(result.server_status == "true"){
                document.getElementById('s_status').innerHTML = work_icon;
            }else{
                document.getElementById('s_status').innerHTML = not_work_icon;
            }
            if(result.server_status == "true"){
                document.getElementById('c_status').innerHTML = work_icon;
            }else{
                document.getElementById('c_status').innerHTML = not_work_icon;
            }
        });
    }

</script>

<?php require APPROOT . 'views/inc/footer.php'; ?>