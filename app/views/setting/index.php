<div class="container-ms">



    <?php
        $is_admin_login = (isset($_COOKIE['username']) && strtolower(trim((string)$_COOKIE['username'])) === 'admin');
    ?>
    <!-- Setting Account V5: fixed AccountDisplay show/hide -->
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['setting'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>
    <div class="main-content">
        <div class="center-content">
            <div class="w3-center">
                <button id="bnt1" name="Controller_Display" class="button active" onclick="SettingOpenButtonFinal('Controller')"><?php echo $text['controller_setting'];?></button>
                <button id="bnt2" name="System_Display" class="button" onclick="SettingOpenButtonFinal('System')"><?php echo $text['system_setting'];?></button>
                <button id="bnt3" name="Barcode_Display" class="button" onclick="SettingOpenButtonFinal('Barcode')"><?php echo $text['system_barcode_setting'] ;?></button>
                <button id="bnt4" name="Connect_Display" class="button" onclick="SettingOpenButtonFinal('Connect')"><?php echo $text['system_connect_setting'];?></button>
                <?php if ($is_admin_login) { ?>
                    <button id="bnt5" name="Account_Display" class="button" onclick="SettingOpenButtonFinal('Account')"><?php echo $text['account_text']; ?></button>
                <?php } ?>
                <button id="bnt6" name="iDas_Display" class="button" onclick="SettingOpenButtonFinal('Update')">iDAS</button>
                
            </div>
        
            <!-- idas_controller OP -->
                <?php require_once '../app/views/setting/idas_controller.php';?>
            <!-- idas_controller ED -->

            <!-- idas_system OP -->
                <?php require_once '../app/views/setting/idas_system.php';?>
            <!-- idas_system ED -->
               

            <!-- idas_barcode OP -->
                <?php require_once '../app/views/setting/idas_barcode.php';?>
            <!-- idas_barcode ED -->


            <!-- idas_agent OP -->
                <?php require_once '../app/views/setting/idas_agent.php';?>
            <!-- idas_agent ED -->


            <!-- setting_account OP -->
                <?php require_once '../app/views/setting/setting_account.php';?>
            <!-- setting_account ED -->


            <!-- operation_audit_log OP -->
                <?php require_once '../app/views/setting/operation_audit_log.php';?>
            <!-- operation_audit_log ED -->


            <!-- idas_update OP -->
                <?php require_once '../app/views/setting/idas_update.php';?>
            <!-- idas_update ED -->

        </div>
    </div>  
    
    <!-- 加载動畫 OP -->
        <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->

</div>

<script>





function button_save_password_gust(){

    var device_id = <?php echo $data['controller_info']['device_id'];?>;

    var pass_guest1 = document.getElementById('new_password_guest').value;
    var pass_guest2 = document.getElementById('comfirm_password_guest').value;

    //正規化 密碼格式(1個英文+1個數字,長度:4)
    var pattern = /^(?=.*[A-Za-z])(?=.*\d).{4,}$/;
    if(pass_guest1 == pass_guest2 && pattern.test(pass_guest1)){
        $.ajax({
            url: "?url=Admins/EditGuestPwd",
            method: "POST",
            data:{ 
                device_id: device_id,
                new_password: pass_guest1

            },
            success: function(response) {
                alert(response);
                history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }else{
        alert('密碼格式不符合要求');
    }
    
}


/*const fileUploader = document.querySelector('#file-uploader');

function idas_update() {
    let ff = document.querySelector('#file-uploader').files;
    let bb = document.getElementById("file-uploader").files[0];
    let form = new FormData();
    form.append("file", bb)

    let url = '?url=Settings/iDas_Update';
    $.ajax({ // 提醒
        type: "POST",
        processData: false,
        cache: false,
        contentType: false,
        data: form,
        dataType: "json",
        url: url,
        beforeSend: function() {
            $('#overlay').removeClass('hidden');
        },
    }).done(function(result) { //成功且有回傳值才會執行
        $('#overlay').addClass('hidden');

        if (result.message != '') {
            Swal.fire({ // DB sync notice
                title: 'Error',
                text: result.message,
            })
        } else {
            Swal.fire('', '', 'success');
            setTimeout(function() {history.go(0)}, 2000);
        }
        document.getElementById("file-uploader").value = '';
        
    });
}*/






// =====================================================
// Account tab permission
// Only cookie username=admin can see/use Account tab.
// =====================================================
function getSettingCookie(name) {
    var target = name + '=';
    var parts = (document.cookie || '').split(';');

    for (var i = 0; i < parts.length; i++) {
        var item = parts[i].trim();
        if (item.indexOf(target) === 0) {
            try {
                return decodeURIComponent(item.substring(target.length));
            } catch (e) {
                return item.substring(target.length);
            }
        }
    }

    return '';
}

function isSettingAdminUser() {
    return String(getSettingCookie('username') || '').trim().toLowerCase() === 'admin';
}

function applySettingAccountVisibility() {
    var canUseAccount = isSettingAdminUser();
    var accountBtn = document.getElementById('bnt5');
    var accountDisplay = document.getElementById('AccountDisplay');
    var auditBtn = document.getElementById('bnt7');
    var auditDisplay = document.getElementById('OperationAuditLogDisplay');

    if (accountBtn) {
        accountBtn.style.display = canUseAccount ? '' : 'none';
        accountBtn.disabled = !canUseAccount;
    }

    if (auditBtn) {
        auditBtn.style.display = canUseAccount ? '' : 'none';
        auditBtn.disabled = !canUseAccount;
    }

    if (!canUseAccount && accountDisplay) {
        accountDisplay.style.display = 'none';
    }

    if (!canUseAccount && auditDisplay) {
        auditDisplay.style.display = 'none';
    }

    return canUseAccount;
}

function SettingOpenButtonFinal(ButtonMode) {
    // 非 admin：禁止開啟 Account / operation_audit_log，避免直接呼叫 JS 進入。
    if ((ButtonMode === 'Account' || ButtonMode === 'AuditLog') && !isSettingAdminUser()) {
        applySettingAccountVisibility();
        ButtonMode = 'Controller';
    }

    const sections = {
        "Controller": "Controller_Setting",
        "System": "System_Setting",
        "Barcode": "Barcode_Setting",
        "Connect": "Connect_Setting",
        "Account": "AccountDisplay",
        "AuditLog": "OperationAuditLogDisplay",
        "Update": "iDas-Update_Setting"
    };

    const buttons = {
        "Controller": "bnt1",
        "System": "bnt2",
        "Barcode": "bnt3",
        "Connect": "bnt4",
        "Account": "bnt5",
        "AuditLog": "bnt7",
        "Update": "bnt6"
    };

    // 隱藏所有區塊 + 移除按鈕 active 樣式
    for (const key in sections) {
        const sectionEl = document.getElementById(sections[key]);
        const buttonEl = document.getElementById(buttons[key]);

        if (sectionEl) sectionEl.style.display = "none";
        if (buttonEl) buttonEl.classList.remove("active");
    }

    // 顯示對應區塊 + 加上 active 樣式
    if (sections[ButtonMode] && buttons[ButtonMode]) {
        const targetSection = document.getElementById(sections[ButtonMode]);
        const targetButton = document.getElementById(buttons[ButtonMode]);

        if (targetSection) {
            targetSection.style.display = "block";
        }
        if (targetButton) targetButton.classList.add("active");

        if (ButtonMode === "Account") {
            loadSettingAccountUsers();
        }

        if (ButtonMode === "AuditLog" && typeof loadSettingOperationAuditLogs === "function") {
            loadSettingOperationAuditLogs();
        }
    } else {
        console.warn("Unknown ButtonMode:", ButtonMode);
    }
}


// 保留相容：如果其他程式呼叫 OpenButton，也會走新版；
// 但本頁按鈕改呼叫 SettingOpenButtonFinal，避免外部舊 settings.js 覆蓋造成 under constructing。
window.SettingOpenButtonFinal = SettingOpenButtonFinal;
window.OpenButton = SettingOpenButtonFinal;

/* Setting Account JS moved to setting_account_script.php */


document.addEventListener('DOMContentLoaded', function() {
    configureSettingAccountAlertifyNoTitle();

    // 依 cookie username 控制 Account / AuditLog tab 顯示。
    applySettingAccountVisibility();

    // 支援從 Data 頁導入：?url=Settings/index&tab=AuditLog / Account / Update ...
    var params = new URLSearchParams(window.location.search);
    var tab = params.get('tab');
    var allowTabs = ['Controller', 'System', 'Barcode', 'Connect', 'Account', 'AuditLog', 'Update'];

    if (tab && allowTabs.indexOf(tab) !== -1) {
        SettingOpenButtonFinal(tab);
        return;
    }

    // 預設顯示 Controller，避免所有區塊都被隱藏時畫面空白。
    var activeBtn = document.querySelector('.w3-center .button.active');
    if (activeBtn && activeBtn.id === 'bnt5' && isSettingAdminUser()) {
        SettingOpenButtonFinal('Account');
    } else {
        SettingOpenButtonFinal('Controller');
    }
});

</script>    

<!-- Setting Account CSS moved to setting_account_style.php -->
<script>
(function(){
    function bindSettingTabButtonsFinal() {
        var map = {
            bnt1: 'Controller',
            bnt2: 'System',
            bnt3: 'Barcode',
            bnt4: 'Connect',
            bnt5: 'Account',
            bnt6: 'Update',
            bnt7: 'AuditLog'
        };

        Object.keys(map).forEach(function(id) {
            var btn = document.getElementById(id);
            if (!btn) return;

            // 非 admin 不綁 Account / operation_audit_log 按鈕，並保持隱藏。
            if ((id === 'bnt5' || id === 'bnt7') && !isSettingAdminUser()) {
                btn.style.display = 'none';
                btn.disabled = true;
                btn.onclick = null;
                return;
            }

            btn.onclick = function(e) {
                if (e && typeof e.preventDefault === 'function') e.preventDefault();
                if (typeof SettingOpenButtonFinal === 'function') {
                    SettingOpenButtonFinal(map[id]);
                }
                return false;
            };
        });
    }

    // 最後再綁一次，避免外部舊 settings.js 蓋掉 onclick。
    window.addEventListener('load', function(){
        applySettingAccountVisibility();

        if (typeof window.SettingOpenButtonFinal === 'function') {
            window.OpenButton = window.SettingOpenButtonFinal;
        } else if (typeof window.OpenButton === 'function') {
            window.SettingOpenButtonFinal = window.OpenButton;
        }
        bindSettingTabButtonsFinal();
    });
})();
</script>

<!-- Account QRCode download moved to setting_account_script.php -->
<!-- FORCE Account QRCode column patch V5 moved to setting_account_script.php -->
<!-- Account static i18n force patch V6 moved to setting_account_script.php -->
<!-- QRCode download button i18n force patch V7 moved to setting_account_script.php -->
