<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_main.css" type="text/css">

<div class="container-ms">
    <div class="main-content">
        <div class="center-content w3-center">
            <div style="text-shadow:3px 5px 0 #444;" class="wrapper w3-center w3-text-red">
                <div class="buttonbox" style=" top: 2%;right: 10px;text-align: right;position: absolute;">
                <input type="button" name="" value="Logout" onclick="logout()" >
                <input type="button" name="" value="简中" data-language="zh-cn" onclick="language_change('zh-cn');" >
                <input type="button" name="" value="繁中" data-language="zh-tw" onclick="language_change('zh-tw');">
                <input type="button" name="" value="English" data-language="en-us" onclick="language_change('en-us');">
                </div>

     
                <div style="margin-top: 3%">   
                    <h1 class="col-ms-3 pt-5"  style="font-size: 50px;"><?php echo TITLE_INDEX; ?></h1>
                    <div style="text-shadow:2px 2px 0 #444; font-size: 30px" class="text w3-center w3-text-yellow"><?php echo SUBTITLE_INDEX; ?></div>
                </div>
            </div>

            <div class="button col pt-5">
                <button class="menu-item blue" id="job_manager" style="font-size: 20px;" onclick="window.location.href='?url=Jobs/index'"><span style="visibility: hidden;">Job</span></button>
                <button class="menu-item green" id="io_input" style="font-size: 20px;"   onclick="window.location.href='?url=Inputs/index'"><span style="visibility: hidden;">IO Input</span></button>
                <button class="menu-item orange" id="io_output" style="font-size: 20px"  onclick="window.location.href='?url=Outputs/index'"><span style="visibility: hidden;">IO Output</span></button>
                <br><br>
                <button class="menu-item purple" id="operation" style="font-size: 20px" onclick="window.location.href='?url=Dashboards/operation'"><span style="visibility: hidden;">Operation</span></button>
                <button class="menu-item lightblue" id="data" style="font-size: 20px" onclick="window.location.href='?url=Data/index'"><span style="visibility: hidden;">Data</span></button>
                <button class="menu-item pink" id="tool" style="font-size: 20px" onclick="window.location.href='?url=Tools/index'"><span style="visibility: hidden;">Tool</span></button>
                <button class="menu-item PaleGreen" id="setting" style="font-size: 20px;" onclick="window.location.href='?url=Settings/index'"><span style="visibility: hidden;">Setting</span></button>
                <br><br>
               
                <?php if($_SESSION['privilege'] == 'admin'){ ?>
                <div>
                    <?php if($data['agent_type'] == '2'){ ?>
                            <button class="menu-item lime" id="agent" style="font-size: 24px"  onclick="window.location.href='?url=Agents'" ><span style="visibility: hidden;">Agent</span></button>
                    <?php } ?>
                            <button class="menu-item indigo" id="load" style="font-size: 24px" onclick="DB_sync_idas('C2D')"><span style="visibility: hidden;">Load</span></button>
                            <button class="menu-item deep-orange" id="save" style="font-size: 24px;" onclick="DB_sync_idas('D2C')"><span style="visibility: hidden;">Save</span></button>
                </div>
                <?php } ?>

            </div>
        </div>
    </div>
</div>

</body>

</html>
<script>

document.addEventListener('DOMContentLoaded', function() {
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      var headerElements = document.querySelectorAll('.ajs-header');
      headerElements.forEach(function(headerElement) {
        headerElement.parentNode.removeChild(headerElement);
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
});

function language_change(language){
    if( language){
        $.ajax({
            url: "?url=Dashboards/change_language",
            method: "POST",
            data:{ 
                language: language

            },
            success: function(response) {
                history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });

    }
}


function DB_sync_idas(argument) {
    const language = getCookie('language');

    const titles = {
        "zh-cn": { "D2C": '同步 iDas的数据库到控制器', "C2D": '同步控制器的数据库到iDas' },
        "zh-tw": { "D2C": '同步iDas的DB到控制器', "C2D": '同步控制器的DB到iDas' },
        "default": { "D2C": 'Sync iDas DB to controller', "C2D": 'Sync controller DB to iDas' }
    };

    const messages = {
        "zh-cn": { "D2C": '同步后目前控制器上的资料将被覆盖，确认是否同步', "C2D": '同步后目前iDas上的资料将被覆盖，确认是否同步' },
        "zh-tw": { "D2C": '同步後目前控制器上的資料將被覆蓋，確認是否同步', "C2D": '同步後目前iDas上的資料將被覆蓋，確認是否同步' },
        "default": {
            "D2C": "After synchronization, the controller's data will be overwritten. Confirm?",
            "C2D": "After synchronization, iDas data will be overwritten. Confirm?"
        }
    };

    const syncingTexts = {
        "zh-cn": "同步中，请稍候...",
        "zh-tw": "同步中，請稍候...",
        "default": "Syncing, please wait..."
    };

    const errorMessages = {
        "zh-cn": {
            "login": "目前控制器有人登入，无法进行同步！",
            "check": "无法确认控制器登入状态",
            "syncFail": "同步失败，请稍后再试",
            "json": "回传资料错误"
        },
        "zh-tw": {
            "login": "目前控制器有人登入，無法進行同步！",
            "check": "無法確認控制器登入狀態",
            "syncFail": "同步失敗，請稍後再試",
            "json": "回傳資料錯誤"
        },
        "default": {
            "login": "Someone is logged in on the controller. Sync cannot proceed.",
            "check": "Unable to verify controller login status",
            "syncFail": "Synchronization failed. Please try again later.",
            "json": "Invalid response from server"
        }
    };

    const title = titles[language]?.[argument] || titles["default"][argument];
    const message = messages[language]?.[argument] || messages["default"][argument];
    const syncingText = syncingTexts[language] || syncingTexts["default"];
    const errorText = errorMessages[language] || errorMessages["default"];

    alertify.confirm(title, message, function () {
        $.ajax({
            url: "?url=Settings/get_controller_login",
            method: "POST",
            success: function (response) {
                try {
                        const result = JSON.parse(response);
                        if (!result.result) {
                            showAlertAutoClose('Error', result.res_msg || errorText.check);
                            return;
                        }
                        // ✅ 通過檢查：開始同步
                        startSyncProcess(argument, syncingText, errorText);

                } catch (e) {
                    console.error("Login check parse error:", e, response);
                    showAlertAutoClose('Error', errorText.json);
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX login check failed:", status, error);
                showAlertAutoClose('Error', errorText.check);
            }
        });
    }, function () {});

    function startSyncProcess(argument, syncingText, errorText) {
        let progress = 0;
        const totalSeconds = 8;
        const intervalTime = (totalSeconds * 1000) / 100;

        addOverlay();
        createProgressDialog(syncingText);

        const interval = setInterval(() => {
            progress += 1;
            const progressBar = document.getElementById('syncProgress');
            const syncText = document.getElementById('syncText');
            if (progressBar) progressBar.value = progress;
            if (syncText) syncText.innerText = syncingText + ' ' + progress + '%';

            if (progress >= 100) {
                clearInterval(interval);
                removeProgressDialog();

                $.ajax({
                    url: getSyncUrl(argument),
                    method: "POST",
                    data: { argument },
                    success: function (response) {
                        try {
                            const res = JSON.parse(response);
                            showAlertAutoClose(res.res_type, res.res_msg);
                            setTimeout(() => {
                                removeOverlay();
                                if (res.res_type === "Success") history.go(0);
                            }, 3000);
                        } catch (e) {
                            console.error("Response parse error:", e, response);
                            showAlertAutoClose('Error', errorText.json);
                            setTimeout(removeOverlay, 3000);
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("Sync failed:", status, error);
                        showAlertAutoClose('Error', errorText.syncFail);
                        setTimeout(removeOverlay, 3000);
                    }
                });
            }
        }, intervalTime);
    }

    function getSyncUrl(argument) {
        switch (argument) {
            case 'D2C': return '?url=Settings/Sync_check_db';
            case 'C2D': return '?url=Settings/Sync_check_db_load';
            default: return '';
        }
    }

    function showAlertAutoClose(title, message, delay = 3000) {
        const dialog = alertify.alert(title, message);
        dialog.set('onshow', function () {
            setTimeout(() => alertify.dismissAll(), delay);
        });
    }

    function addOverlay() {
        if (document.getElementById('overlayMask')) return;
        const overlay = document.createElement('div');
        overlay.id = 'overlayMask';
        Object.assign(overlay.style, {
            position: 'fixed',
            top: '0', left: '0', width: '100%', height: '100%',
            backgroundColor: 'rgba(0, 0, 0, 0.3)',
            zIndex: '9998'
        });
        document.body.appendChild(overlay);
    }

    function removeOverlay() {
        const overlay = document.getElementById('overlayMask');
        if (overlay) overlay.remove();
    }

    function createProgressDialog(syncingText) {
        const dialog = document.createElement("div");
        dialog.id = "customProgressDialog";
        Object.assign(dialog.style, {
            position: "fixed", top: "30%", left: "50%",
            transform: "translate(-50%, -30%)",
            padding: "20px", background: "#fff", borderRadius: "10px",
            boxShadow: "0 0 10px rgba(0,0,0,0.3)", zIndex: "9999"
        });
        dialog.innerHTML = `
            <div id="syncText" style="margin-bottom: 10px; text-align:center;">${syncingText} 0%</div>
            <progress id="syncProgress" value="0" max="100" style="width: 100%; height: 20px;"></progress>
        `;
        document.body.appendChild(dialog);
    }

    function removeProgressDialog() {
        const dialog = document.getElementById("customProgressDialog");
        if (dialog) dialog.remove();
    }
}




</script>

<style>
    #job_manager {
        background: url("<?php echo $text['img_job']; ?>") no-repeat;
    }
    #job_manager:hover {
        background: url("<?php echo $text['img_job_hover']; ?>") no-repeat;
    }

    #io_input {
        background: url("<?php echo $text['img_io_input']; ?>") no-repeat;
    }
    #io_input:hover {
        background: url("<?php echo $text['img_io_input_hover']; ?>") no-repeat;
    }

    #io_output {
        background: url("<?php echo $text['img_io_output']; ?>") no-repeat;
    }
    #io_output:hover {
        background: url("<?php echo $text['img_io_output_hover']; ?>") no-repeat;
    }

    #operation {
        background: url("<?php echo $text['img_operation']; ?>") no-repeat;
    }
    #operation:hover {
        background: url("<?php echo $text['img_operation_hover']; ?>") no-repeat;
    }
    
    #data {
        background: url("<?php echo $text['img_data']; ?>") no-repeat;
    }
    #data:hover {
        background: url("<?php echo $text['img_data_hover']; ?>") no-repeat;
    }

    #tool {
        background: url("<?php echo $text['img_tool']; ?>") no-repeat;
    }
    #tool:hover {
        background: url("<?php echo $text['img_tool_hover']; ?>") no-repeat;
    }

    #setting {
        background: url("<?php echo $text['img_setting']; ?>") no-repeat;
    }
    #setting:hover {
        background: url("<?php echo $text['img_setting_hover']; ?>") no-repeat;
    }

    #load {
        background: url("<?php echo $text['img_load']; ?>") no-repeat;
    }
    #load:hover {
        background: url("<?php echo $text['img_load_hover']; ?>") no-repeat;
    }

    #save {
        background: url("<?php echo $text['img_save']; ?>") no-repeat;
    }
    #save:hover {
        background: url("<?php echo $text['img_save_hover']; ?>") no-repeat;
    }

    #agent {
        background: url("<?php echo $text['img_agent']; ?>") no-repeat;
    }
    #agent:hover {
        background: url("<?php echo $text['img_agent_hover']; ?>") no-repeat;
    }
            
</style>
