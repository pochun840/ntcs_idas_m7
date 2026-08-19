<?php if (defined('IS_ICONTROLLER') && IS_ICONTROLLER): ?>
<?php $ver = date('YmdHis'); ?>
<link rel="stylesheet" href="<?=URLROOT;?>css/tcc_main.css?v=<?=$ver;?>">

<div class="container-ms">
    <div class="main-content">
        <div class="center-content w3-center">
            <div style="text-shadow:3px 5px 0 #444;" class="wrapper w3-center w3-text-red">
                <div class="buttonbox" style=" top: 2%;right: 10px;text-align: right;position: absolute;">
                <input type="button" name="" value="<?php echo $text['logout_text'];?>" onclick="logout()" >
                <input type="button" name="" value="简中" data-language="zh-cn" onclick="language_change('zh-cn');" >
                <input type="button" name="" value="繁中" data-language="zh-tw" onclick="language_change('zh-tw');">
                <input type="button" name="" value="English" data-language="en-us" onclick="language_change('en-us');">
                </div>

     
                <div style="margin-top: 3%">   
                    <h1 class="col-ms-3 pt-5"  style="font-size: 50px;"><?php echo TITLE_INDEX; ?></h1>
                    <div style="text-shadow:2px 2px 0 #444; font-size: 30px" class="text w3-center w3-text-yellow"><?php echo SUBTITLE_INDEX; ?></div>
                </div>
            </div>

            <div class="button">
                <!-- Hàng 1: 3 nút -->
                <div class="row">
                    <button class="menu-item blue" id="job_manager" onclick="window.location.href='?url=Jobs/index'"><span style="visibility: hidden;">Job</span></button>
                    <button class="menu-item green" id="io_input" onclick="window.location.href='?url=Inputs/index'"><span style="visibility: hidden;">IO Input</span></button>
                    <button class="menu-item orange" id="io_output" onclick="window.location.href='?url=Outputs/index'"><span style="visibility: hidden;">IO Output</span></button>
                </div>

                <!-- Hàng 2: 4 nút -->
                <div class="row">
                    <button class="menu-item purple" id="operation" onclick="window.location.href='?url=Dashboards/operation'"><span style="visibility: hidden;">Operation</span></button>
                    <button class="menu-item lightblue" id="data" onclick="window.location.href='?url=Data/index'"><span style="visibility: hidden;">Data</span></button>
                    <button class="menu-item pink" id="tool" onclick="window.location.href='?url=Tools/index'"><span style="visibility: hidden;">Tool</span></button>
                    <button class="menu-item PaleGreen" id="setting" onclick="window.location.href='?url=Settings/index'"><span style="visibility: hidden;">Setting</span></button>
                </div>

                <div class="row">
                    <button class="menu-item lime" id="remote" onclick="window.location.href='?url=Remotes'"><span style="visibility: hidden;">Remotes</span></button>
                    <button class="menu-item lime" id="agent"  onclick="window.location.href='?url=Agents'" ><span style="visibility: hidden;">Agent</span></button>
                    <button class="menu-item indigo" id="load" onclick="DB_sync_idas('C2D')"><span style="visibility: hidden;">Load</span></button>
                    <button class="menu-item deep-orange" id="save" onclick="DB_sync_idas('D2C')"><span style="visibility: hidden;">Save</span></button>
                </div>
            </div>            
            
        </div>
    </div>
</div>

</body>

</html>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const langRaw = getCookie('language') || 'en-us';
    const language = (langRaw.toLowerCase() === 'en') ? 'en-us' : langRaw.toLowerCase();

    alertify.defaults.glossary = {
        title: (language === 'zh-tw') ? '提示' :
            (language === 'zh-cn') ? '提示' : 'Notification',
        ok: (language === 'zh-tw') ? '確定' :
            (language === 'zh-cn') ? '确定' : 'OK',
        cancel: (language === 'zh-tw') ? '取消' :
                (language === 'zh-cn') ? '取消' : 'Cancel'
    };
});

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
                window.location.reload();
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

    // ★ 新增：OK / Cancel 語系
    const okText = (language === 'zh-cn') ? '确定' :
                   (language === 'zh-tw') ? '確定' : 'OK';
    const cancelText = (language === 'zh-cn') ? '取消' :
                       (language === 'zh-tw') ? '取消' : 'Cancel';

    const title       = titles[language]?.[argument]   || titles["default"][argument];
    const message     = messages[language]?.[argument] || messages["default"][argument];
    const syncingText = syncingTexts[language]         || syncingTexts["default"];
    const errorText   = errorMessages[language]        || errorMessages["default"];

    // ★ 新增：安全解析 JSON 的 helper
    function parseJsonSafe(response) {
        if (response && typeof response === 'object') {
            return response; // 已經是物件
        }
        if (typeof response !== 'string') {
            console.error("Unexpected response type:", typeof response, response);
            return null;
        }
        const trimmed = response.trim();
        if (!trimmed) return null;
        try {
            return JSON.parse(trimmed);
        } catch (e) {
            console.error("JSON.parse failed:", e, response);
            return null;
        }
    }

    alertify.confirm(title, message, function () {
        $.ajax({
            url: "?url=Settings/get_controller_login",
            method: "POST",
            // dataType: "text", // 可加可不加，保險一點就加
            success: function (response) {
                const result = parseJsonSafe(response);

                // 檢查基本格式
                if (!result || typeof result !== 'object' || typeof result.result === 'undefined') {
                    console.error("Unexpected login response format:", response);
                    showAlertAutoClose('Error', errorText.json);
                    return;
                }

                // 後端說「不能同步」（例：控制器有人登入）
                if (!result.result) {
                    showAlertAutoClose('Error', result.res_msg || errorText.check);
                    return;
                }

                // ✅ 通過檢查：開始同步
                startSyncProcess(argument, syncingText, errorText);
            },
            error: function (xhr, status, error) {
                console.error("AJAX login check failed:", status, error);
                console.error("Response text:", xhr.responseText);
                showAlertAutoClose('Error', errorText.check);
            }
        });
    }, function () {})
    .set('labels', { ok: okText, cancel: cancelText });

    function startSyncProcess(argument, syncingText, errorText) {
        let progress = 0;
        const totalSeconds = 3;
        const intervalTime = (totalSeconds * 1000) / 100;

        addOverlay();
        createProgressDialog(syncingText);

        const interval = setInterval(() => {
            progress += 1;
            const progressBar = document.getElementById('animatedProgressBar');
            if (progressBar) progressBar.style.width = progress + "%";

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
                    // dataType: "text",
                    success: function (response) {
                        const res = parseJsonSafe(response);

                        if (!res || typeof res !== 'object' || typeof res.res_type === 'undefined') {
                            console.error("Unexpected sync response format:", response);
                            showAlertAutoClose('Error', errorText.json);
                            setTimeout(removeOverlay, 3000);
                            return;
                        }

                        showAlertAutoClose(res.res_type, res.res_msg);
                        setTimeout(() => {
                            removeOverlay();
                            if (res.res_type === "Success") window.location.reload();
                        }, 3000);
                    },
                    error: function (xhr, status, error) {
                        console.error("Sync failed:", status, error);
                        console.error("Response text:", xhr.responseText);
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
            default:    return '';
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
            padding: "20px", background: "#fff", borderRadius: "20px",
            boxShadow: "0 0 20px rgba(0,0,0,0.3)", zIndex: "9999",
            width: "300px", textAlign: "center", fontFamily: "Arial"
        });

        dialog.innerHTML = `
            <div id="syncText" style="margin-bottom: 10px; font-size: 16px; font-weight: bold;">
                ${syncingText} 0%
            </div>
            <div class="spinner"></div>
            <div class="progress-bar-container" style="margin-top: 15px;">
                <div class="progress-bar-fill" id="animatedProgressBar"></div>
            </div>
            <style>
            .spinner {
                margin: 10px auto;
                width: 32px;
                height: 32px;
                border: 4px solid #ddd;
                border-top: 4px solid #00bfff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .progress-bar-container {
                width: 100%;
                height: 20px;
                background: #e0e0e0;
                border-radius: 10px;
                overflow: hidden;
            }
            .progress-bar-fill {
                height: 100%;
                width: 0%;
                background: linear-gradient(270deg, #4facfe, #00f2fe);
                background-size: 400% 400%;
                animation: gradientMove 4s ease infinite;
                border-radius: 10px;
                transition: width 0.2s ease-in-out;
            }

            @keyframes gradientMove {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            </style>
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

        
    #remote {
        background: url("<?php echo $text['img_remote']; ?>") no-repeat;
    }
    #remote:hover {
        background: url("<?php echo $text['img_remote_hover']; ?>") no-repeat;
    }
    
            
</style>
<?php else: ?>
<?php $idasMenuUserLaw = (string)($_COOKIE['user_law'] ?? '1'); ?>
<?php $ver = date('YmdHis'); ?>
<link rel="stylesheet" href="<?=URLROOT;?>css/tcc_main.css?v=<?=$ver;?>">

<div class="container-ms">
    <div class="main-content">
        <div class="center-content w3-center">
            <div style="text-shadow:3px 5px 0 #444;" class="wrapper w3-center w3-text-red">
                <div class="buttonbox" style=" top: 2%;right: 10px;text-align: right;position: absolute;">
                <input type="button" name="" value="<?php echo $text['logout_text'];?>" onclick="logout()" >
                <input type="button" name="" value="简中" data-language="zh-cn" onclick="language_change('zh-cn');" >
                <input type="button" name="" value="繁中" data-language="zh-tw" onclick="language_change('zh-tw');">
                <input type="button" name="" value="English" data-language="en-us" onclick="language_change('en-us');">
                </div>

     
                <div style="margin-top: 3%">   
                    <h1 class="col-ms-3 pt-5"  style="font-size: 50px;"><?php echo TITLE_INDEX; ?></h1>
                    <div style="text-shadow:2px 2px 0 #444; font-size: 30px" class="text w3-center w3-text-yellow"><?php echo SUBTITLE_INDEX; ?></div>
                </div>
            </div>

            <div class="button">
                <!-- Hàng 1: 3 nút -->
                <div class="row">
                    <button class="menu-item blue" id="job_manager" onclick="window.location.href='?url=Jobs/index'"><span style="visibility: hidden;">Job</span></button>
                    <button class="menu-item green" id="io_input" onclick="window.location.href='?url=Inputs/index'"><span style="visibility: hidden;">IO Input</span></button>
                    <button class="menu-item orange" id="io_output" onclick="window.location.href='?url=Outputs/index'"><span style="visibility: hidden;">IO Output</span></button>
                </div>

                <!-- Hàng 2: 4 nút -->
                <div class="row">
                    <button class="menu-item purple" id="operation" onclick="window.location.href='?url=Dashboards/operation'"><span style="visibility: hidden;">Operation</span></button>
                    <button class="menu-item lightblue" id="data" onclick="window.location.href='?url=Data/index'"><span style="visibility: hidden;">Data</span></button>
                    <button class="menu-item pink" id="tool" onclick="window.location.href='?url=Tools/index'"><span style="visibility: hidden;">Tool</span></button>
                    <button class="menu-item PaleGreen" id="setting" onclick="window.location.href='?url=Settings/index'"><span style="visibility: hidden;">Setting</span></button>
                </div>

                <div class="row">
                    <button class="menu-item lime" id="remote" onclick="window.location.href='?url=Remotes'"><span style="visibility: hidden;">Remotes</span></button>
                    <button class="menu-item lime" id="agent"  onclick="window.location.href='?url=Agents'" ><span style="visibility: hidden;">Agent</span></button>
                    <button class="menu-item indigo" id="load" onclick="DB_sync_idas('C2D')"><span style="visibility: hidden;">Load</span></button>
                    <button class="menu-item deep-orange" id="save" onclick="DB_sync_idas('D2C')"><span style="visibility: hidden;">Save</span></button>
                </div>
            </div>            
            
        </div>
    </div>
</div>

</body>

</html>

<script>
(function () {
    var currentLaw = <?php echo json_encode($idasMenuUserLaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

    if (String(currentLaw) !== '3') {
        return;
    }

    // Operator 權限：MENU 頁面限制上傳/下載同步與代理功能，其他選單仍可點擊。
    var deniedMenuIds = {
        load: true,  // 下載 / Controller DB -> iDAS
        save: true,  // 上傳 / iDAS DB -> Controller
        agent: true  // 代理：Operator / law=3 不允許進入
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.menu-item').forEach(function (btn) {
            if (!btn || !deniedMenuIds[btn.id]) {
                return;
            }

            btn.removeAttribute('onclick');
            btn.disabled = true;
            btn.setAttribute('aria-disabled', 'true');
            btn.classList.add('operator-disabled');
            btn.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                return false;
            }, true);
        });
    });
})();
</script>
<style>
.menu-item.operator-disabled {
    position: relative !important;
    opacity: 0.35 !important;
    filter: grayscale(1) !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}
/* 禁止圖示由 public/js/all.js 的 .idas-operator-forbidden-badge 統一產生，避免 ::after 重複顯示 */
.menu-item.operator-disabled:hover {
    transform: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const langRaw = getCookie('language') || 'en-us';
    const language = (langRaw.toLowerCase() === 'en') ? 'en-us' : langRaw.toLowerCase();

    alertify.defaults.glossary = {
        title: (language === 'zh-tw') ? '提示' :
            (language === 'zh-cn') ? '提示' : 'Notification',
        ok: (language === 'zh-tw') ? '確定' :
            (language === 'zh-cn') ? '确定' : 'OK',
        cancel: (language === 'zh-tw') ? '取消' :
                (language === 'zh-cn') ? '取消' : 'Cancel'
    };
});

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
                window.location.reload();
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

    // ★ 新增：OK / Cancel 語系
    const okText = (language === 'zh-cn') ? '确定' :
                   (language === 'zh-tw') ? '確定' : 'OK';
    const cancelText = (language === 'zh-cn') ? '取消' :
                       (language === 'zh-tw') ? '取消' : 'Cancel';

    const title       = titles[language]?.[argument]   || titles["default"][argument];
    const message     = messages[language]?.[argument] || messages["default"][argument];
    const syncingText = syncingTexts[language]         || syncingTexts["default"];
    const errorText   = errorMessages[language]        || errorMessages["default"];

    // ★ 新增：安全解析 JSON 的 helper
    function parseJsonSafe(response) {
        if (response && typeof response === 'object') {
            return response; // 已經是物件
        }
        if (typeof response !== 'string') {
            console.error("Unexpected response type:", typeof response, response);
            return null;
        }
        const trimmed = response.trim();
        if (!trimmed) return null;
        try {
            return JSON.parse(trimmed);
        } catch (e) {
            console.error("JSON.parse failed:", e, response);
            return null;
        }
    }

    alertify.confirm(title, message, function () {
        $.ajax({
            url: "?url=Settings/get_controller_login",
            method: "POST",
            // dataType: "text", // 可加可不加，保險一點就加
            success: function (response) {
                const result = parseJsonSafe(response);

                // 檢查基本格式
                if (!result || typeof result !== 'object' || typeof result.result === 'undefined') {
                    console.error("Unexpected login response format:", response);
                    showAlertAutoClose('Error', errorText.json);
                    return;
                }

                // 後端說「不能同步」（例：控制器有人登入）
                if (!result.result) {
                    showAlertAutoClose('Error', result.res_msg || errorText.check);
                    return;
                }

                // ✅ 通過檢查：開始同步
                startSyncProcess(argument, syncingText, errorText);
            },
            error: function (xhr, status, error) {
                console.error("AJAX login check failed:", status, error);
                console.error("Response text:", xhr.responseText);
                showAlertAutoClose('Error', errorText.check);
            }
        });
    }, function () {})
    .set('labels', { ok: okText, cancel: cancelText });

    function startSyncProcess(argument, syncingText, errorText) {
        let progress = 0;
        const totalSeconds = 3;
        const intervalTime = (totalSeconds * 1000) / 100;

        addOverlay();
        createProgressDialog(syncingText);

        const interval = setInterval(() => {
            progress += 1;
            const progressBar = document.getElementById('animatedProgressBar');
            if (progressBar) progressBar.style.width = progress + "%";

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
                    // dataType: "text",
                    success: function (response) {
                        const res = parseJsonSafe(response);

                        if (!res || typeof res !== 'object' || typeof res.res_type === 'undefined') {
                            console.error("Unexpected sync response format:", response);
                            showAlertAutoClose('Error', errorText.json);
                            setTimeout(removeOverlay, 3000);
                            return;
                        }

                        showAlertAutoClose(res.res_type, res.res_msg);
                        setTimeout(() => {
                            removeOverlay();
                            if (res.res_type === "Success") window.location.reload();
                        }, 3000);
                    },
                    error: function (xhr, status, error) {
                        console.error("Sync failed:", status, error);
                        console.error("Response text:", xhr.responseText);
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
            default:    return '';
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
            padding: "20px", background: "#fff", borderRadius: "20px",
            boxShadow: "0 0 20px rgba(0,0,0,0.3)", zIndex: "9999",
            width: "300px", textAlign: "center", fontFamily: "Arial"
        });

        dialog.innerHTML = `
            <div id="syncText" style="margin-bottom: 10px; font-size: 16px; font-weight: bold;">
                ${syncingText} 0%
            </div>
            <div class="spinner"></div>
            <div class="progress-bar-container" style="margin-top: 15px;">
                <div class="progress-bar-fill" id="animatedProgressBar"></div>
            </div>
            <style>
            .spinner {
                margin: 10px auto;
                width: 32px;
                height: 32px;
                border: 4px solid #ddd;
                border-top: 4px solid #00bfff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }

            .progress-bar-container {
                width: 100%;
                height: 20px;
                background: #e0e0e0;
                border-radius: 10px;
                overflow: hidden;
            }
            .progress-bar-fill {
                height: 100%;
                width: 0%;
                background: linear-gradient(270deg, #4facfe, #00f2fe);
                background-size: 400% 400%;
                animation: gradientMove 4s ease infinite;
                border-radius: 10px;
                transition: width 0.2s ease-in-out;
            }

            @keyframes gradientMove {
                0% { background-position: 0% 50%; }
                50% { background-position: 100% 50%; }
                100% { background-position: 0% 50%; }
            }
            </style>
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

        
    #remote {
        background: url("<?php echo $text['img_remote']; ?>") no-repeat;
    }
    #remote:hover {
        background: url("<?php echo $text['img_remote_hover']; ?>") no-repeat;
    }
    
            
</style>
<?php endif; ?>

<?php
$platformLabel = (defined('IS_ICONTROLLER') && IS_ICONTROLLER)
    ? 'i-controller'
    : 'KL-NTCS';
?>
<div class="idas-platform-label">
    <?php echo htmlspecialchars($platformLabel, ENT_QUOTES, 'UTF-8'); ?>
</div>

<style>
.idas-platform-label {
    position: fixed;
    right: 18px;
    bottom: 12px;
    z-index: 9999;
    padding: 4px 10px;
    font-size: 18px;
    font-weight: 600;
    line-height: 1.2;
    color: #f5e600;
    text-shadow: 2px 2px 0 #444;
    background: rgba(0, 0, 0, 0.22);
    border-radius: 4px;
    pointer-events: none;
    user-select: none;
}
</style>

