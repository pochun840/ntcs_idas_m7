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
                    <button id="bnt5" name="Account_Display" class="button" onclick="SettingOpenButtonFinal('Account')"><?php echo $text['account'] ?? 'Account'; ?></button>
                <?php } ?>
                <button id="bnt6" name="iDas_Display" class="button" onclick="SettingOpenButtonFinal('Update')">iDAS</button>
                <?php if ($is_admin_login) { ?>
                    <button id="bnt7" name="Operation_Audit_Log_Display" class="button operation-audit-top-button" onclick="SettingOpenButtonFinal('AuditLog')">operation_audit_log</button>
                <?php } ?>
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
            <div id="AccountDisplay" class="setting-account-page" style="display:none;">
                <div class="table-container account-table-container">
                    <div class="scrollbar" id="style-accounttable">
                        <div class="scrollbar-force-overflow">
                            <table id="account_user_table" class="table w3-table account-user-table">
                                <thead id="header-table">
                                    <tr class="w3-dark-grey">
                                        <th>No</th>
                                        <th>User Name</th>
                                    </tr>
                                </thead>
                                <tbody id="accountUserTbody" style="font-size: 1.8vmin;text-align: center;">
                                    <tr><td colspan="2">Please select Account tab</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="footer account-footer">
                    <div class="buttonbox">
                        <input id="account_new_btn" type="button" value="New" onclick="settingAccountAction('new')">
                        <input id="account_edit_btn" type="button" value="Edit" onclick="settingAccountAction('edit')">
                        <input id="account_delete_btn" type="button" value="Delete" onclick="settingAccountAction('delete')">
                        <input id="account_import_btn" type="button" value="Import" onclick="settingAccountAction('import')">
                        <input id="account_export_btn" type="button" value="Export" onclick="settingAccountAction('export')">
                        <input id="account_upload_controller_btn" type="button" value="Upload" onclick="settingAccountAction('upload_controller')">
                    </div>
                    <input id="account_import_file" type="file" accept=".csv,text/csv" style="display:none" onchange="importSettingAccountFile(this)">
                </div>

                <!-- New / Edit Account Modal -->
                <div id="settingAccountModal" class="modal">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content w3-animate-zoom setting-account-modal">
                            <header class="w3-container modal-header">
                                <span onclick="closeSettingAccountModal();" class="w3-button w3-red w3-display-topright account-modal-x">&times;</span>
                                <h3 id="settingAccountModalTitle">New Account</h3>
                            </header>

                            <div class="modal-body account-modal-body">
                                <input type="hidden" id="account_old_username" value="">

                                <div class="row account-form-row">
                                    <div class="col-5 t1">Username :</div>
                                    <div class="col-5 t2">
                                        <input type="text" class="form-control input-ms" id="account_username" maxlength="8" minlength="6" pattern="[A-Za-z0-9]{6,8}" autocomplete="off" oninput="this.value=this.value.replace(/[^A-Za-z0-9]/g,'').slice(0,8)">
                                    </div>
                                </div>

                                <div class="row account-form-row">
                                    <div class="col-5 t1">Password :</div>
                                    <div class="col-5 t2">
                                        <div class="account-password-wrap">
                                            <input type="password" class="form-control input-ms" id="account_password" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" autocomplete="new-password" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,4)">
                                            <button type="button" class="password-eye-btn" onclick="toggleSettingAccountPassword('account_password', this)" title="Show password" aria-label="Show password">
                                                <span class="eye-symbol">&#128065;</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row account-form-row">
                                    <div class="col-5 t1">Confirm Password :</div>
                                    <div class="col-5 t2">
                                        <div class="account-password-wrap">
                                            <input type="password" class="form-control input-ms" id="account_confirm_password" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" autocomplete="new-password" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,4)">
                                            <button type="button" class="password-eye-btn" onclick="toggleSettingAccountPassword('account_confirm_password', this)" title="Show password" aria-label="Show password">
                                                <span class="eye-symbol">&#128065;</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer justify-content-center">
                                <button class="button-modal" onclick="saveSettingAccount();">Save</button>
                                <button class="button-modal closebtn" onclick="closeSettingAccountModal();">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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

// =====================================================
// Setting Account / table user
// Rule:
// 1) User List hides Law column.
// 2) Username: A-Z / a-z / 0-9, 6~8 chars.
// 3) Password: exactly 4 digits, 0~9.
// =====================================================
var selectedAccountUser = null;
var settingAccountMode = 'new';
var settingAccountLoaded = false;

function settingAccountApi(path) {
    return '?url=Settings/' + path;
}

function configureSettingAccountAlertifyNoTitle() {
    if (!window.alertify) return;

    try {
        // AlertifyJS v1：清掉預設標題 AlertifyJS
        if (alertify.defaults && alertify.defaults.glossary) {
            alertify.defaults.glossary.title = '';
        }
    } catch (e) {}
}

function settingAccountAlert(message) {
    configureSettingAccountAlertifyNoTitle();
    if (window.alertify && typeof alertify.alert === 'function') {
        alertify.alert(message);
    } else {
        alert(String(message).replace(/<[^>]*>/g, ''));
    }
}

function settingAccountConfirm(message, onOk, onCancel) {
    configureSettingAccountAlertifyNoTitle();

    if (window.alertify && typeof alertify.confirm === 'function') {
        alertify.confirm(
            message,
            function(e) {
                // 相容 AlertifyJS v1 與舊版：舊版 Cancel 可能會回 false。
                if (e === false) {
                    if (typeof onCancel === 'function') onCancel();
                    return;
                }

                if (typeof onOk === 'function') onOk();
            },
            function() {
                if (typeof onCancel === 'function') onCancel();
            }
        );
    } else {
        // 專案應使用 AlertifyJS confirm；若未載入，不執行危險動作。
        settingAccountAlert('AlertifyJS confirm is not loaded.');
        if (typeof onCancel === 'function') onCancel();
    }
}

function settingAccountValidText(value) {
    // 既有帳號 key 檢查：允許 A-Z / a-z / 0-9。
    return /^[A-Za-z0-9]+$/.test(value);
}

function settingAccountValidUsername(value) {
    // 新帳號 / 改名：6~8 字元，只允許 A-Z / a-z / 0-9。
    return /^[A-Za-z0-9]{6,8}$/.test(value);
}

function settingAccountValidPassword(value) {
    // 密碼固定 4 碼數字，允許 0000。
    return /^[0-9]{4}$/.test(value);
}

function settingAccountEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function settingAccountPost(path, data) {
    var formData = new FormData();

    Object.keys(data || {}).forEach(function(key) {
        formData.append(key, data[key]);
    });

    return fetch(settingAccountApi(path), {
        method: 'POST',
        body: formData,
        cache: 'no-store',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(function(res) {
        return res.text().then(function(text) {
            return {
                status: res.status,
                ok: res.ok,
                text: text
            };
        });
    }).then(function(result) {
        var text = result.text || '';

        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Account API status:', result.status);
            console.error('Account API raw response:', text);

            var raw = text
                ? text.replace(/</g, '&lt;').replace(/>/g, '&gt;').substring(0, 1200)
                : '[EMPTY RESPONSE]';

            throw new Error(
                'API response is not JSON.<br>' +
                'HTTP Status: ' + result.status + '<br>' +
                'Raw response:<br><pre style="text-align:left;white-space:pre-wrap;max-height:260px;overflow:auto;">' + raw + '</pre>'
            );
        }
    });
}

function loadSettingAccountUsers() {
    var tbody = document.getElementById('accountUserTbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="2">Loading...</td></tr>';

    settingAccountPost('account_user_list', {}).then(function(json) {
        settingAccountLoaded = true;

        if (!json.success) {
            if (tbody) tbody.innerHTML = '<tr><td colspan="2">Load failed</td></tr>';
            settingAccountAlert(json.res_msg || 'Load account failed.');
            return;
        }

        renderSettingAccountUsers(json.records || []);
    }).catch(function(err) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="2">Load failed</td></tr>';
        settingAccountAlert(err.message || 'Load account failed.');
    });
}

function renderSettingAccountUsers(records) {
    var tbody = document.getElementById('accountUserTbody');
    if (!tbody) return;

    selectedAccountUser = null;

    // 隱藏內建帳號 Kls / kls
    records = (records || []).filter(function(row) {
        return String(row.name || '').toLowerCase() !== 'kls';
    });

    if (!records.length) {
        tbody.innerHTML = '<tr><td colspan="2">No Data</td></tr>';
        return;
    }

    tbody.innerHTML = records.map(function(row, index) {
        var name = settingAccountEscape(row.name || '');

        return '<tr class="account-user-row" data-name="' + name + '" onclick="selectSettingAccountUser(this)">' +
            '<td>' + (index + 1) + '</td>' +
            '<td>' + name + '</td>' +
            '</tr>';
    }).join('');
}

function selectSettingAccountUser(row) {
    // 與 Job 頁面一致：點選列時使用 selected class
    document.querySelectorAll('.account-user-row').forEach(function(tr) {
        tr.classList.remove('selected');
    });

    row.classList.add('selected');
    selectedAccountUser = row.getAttribute('data-name') || '';
}


function exportSettingAccounts() {
    // Export account/password CSV. Browser will download directly.
    window.location.href = settingAccountApi('account_user_export') + '&t=' + Date.now();
}

function uploadSettingAccountsToController() {
    settingAccountPost('account_user_upload_controller', {}).then(function(json) {
        settingAccountAlert(json.res_msg || (json.success ? 'Upload success.' : 'Upload failed.'));
        if (json.success) {
            loadSettingAccountUsers();
        }
    }).catch(function(err) {
        settingAccountAlert(err.message || 'Upload failed.');
    });
}

function importSettingAccounts() {
    var fileInput = document.getElementById('account_import_file');
    if (!fileInput) return;

    fileInput.value = '';
    fileInput.click();
}

function importSettingAccountFile(input) {
    if (!input || !input.files || !input.files.length) return;

    var file = input.files[0];
    var fileName = file && file.name ? file.name : '';

    if (!/\.csv$/i.test(fileName)) {
        settingAccountAlert('Only CSV file is allowed.');
        input.value = '';
        return;
    }

    var formData = new FormData();
    formData.append('account_file', file);

    fetch(settingAccountApi('account_user_import'), {
        method: 'POST',
        body: formData,
        cache: 'no-store',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(function(res) {
        return res.text().then(function(text) {
            return {
                status: res.status,
                text: text
            };
        });
    }).then(function(result) {
        var json;
        try {
            json = JSON.parse(result.text || '');
        } catch (e) {
            console.error('Account import API status:', result.status);
            console.error('Account import API raw response:', result.text);
            throw new Error('Import response is not JSON.');
        }

        var msg = json.res_msg || (json.success ? 'Import success.' : 'Import failed.');
        if (json.errors && json.errors.length) {
            msg += '<br><br>' + json.errors.map(settingAccountEscape).join('<br>');
        }

        settingAccountAlert(msg);

        if (json.success) {
            loadSettingAccountUsers();
        }
    }).catch(function(err) {
        settingAccountAlert(err.message || 'Import failed.');
    }).finally(function() {
        input.value = '';
    });
}

function settingAccountAction(mode) {
    settingAccountMode = mode;

    if (mode === 'new') {
        document.getElementById('settingAccountModalTitle').innerText = 'New Account';
        document.getElementById('account_old_username').value = '';
        document.getElementById('account_username').value = '';
        document.getElementById('account_password').value = '';
        document.getElementById('account_confirm_password').value = '';
        resetSettingAccountPasswordMask();
        openSettingAccountModal();
        return;
    }

    if (mode === 'export') {
        exportSettingAccounts();
        return;
    }

    if (mode === 'import') {
        settingAccountConfirm(
            'Import accounts from CSV?<br>Only table user in KLS_NTCS_IDAS.Lin will be modified.<br>Built-in Kls account will be skipped.',
            function() {
                importSettingAccounts();
            }
        );
        return;
    }

    if (mode === 'upload_controller') {
        settingAccountConfirm(
            'Upload user list to controller DB?<br><br>Target: /home/kls/NTCS7/KLS_NTCS.Lin<br>Only table user will be modified.<br>Other tables will not be changed.',
            function() {
                uploadSettingAccountsToController();
            }
        );
        return;
    }

    if (!selectedAccountUser) {
        settingAccountAlert('Please select one account.');
        return;
    }

    if (mode === 'edit') {
        document.getElementById('settingAccountModalTitle').innerText = 'Edit Account';
        document.getElementById('account_old_username').value = selectedAccountUser;
        document.getElementById('account_username').value = selectedAccountUser;
        document.getElementById('account_password').value = '';
        document.getElementById('account_confirm_password').value = '';
        resetSettingAccountPasswordMask();
        openSettingAccountModal();
        return;
    }

    if (mode === 'delete') {
        settingAccountConfirm(
            'Are you sure you want to delete this account?<br><br>Account: ' + settingAccountEscape(selectedAccountUser),
            function() {
                settingAccountPost('account_user_delete', {
                    username: selectedAccountUser
                }).then(function(json) {
                    settingAccountAlert(json.res_msg || (json.success ? 'Delete success.' : 'Delete failed.'));
                    if (json.success) loadSettingAccountUsers();
                }).catch(function(err) {
                    settingAccountAlert(err.message || 'Delete failed.');
                });
            }
        );
        return;
    }
}

function openSettingAccountModal() {
    var modal = document.getElementById('settingAccountModal');
    if (modal) modal.style.display = 'block';
}

function closeSettingAccountModal() {
    var modal = document.getElementById('settingAccountModal');
    if (modal) modal.style.display = 'none';
    resetSettingAccountPasswordMask();
}

function toggleSettingAccountPassword(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;

    var isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';

    if (btn) {
        if (isHidden) {
            btn.classList.add('is-visible');
            btn.setAttribute('title', 'Hide password');
            btn.setAttribute('aria-label', 'Hide password');
        } else {
            btn.classList.remove('is-visible');
            btn.setAttribute('title', 'Show password');
            btn.setAttribute('aria-label', 'Show password');
        }
    }
}

function resetSettingAccountPasswordMask() {
    var password = document.getElementById('account_password');
    var confirmPassword = document.getElementById('account_confirm_password');

    if (password) password.type = 'password';
    if (confirmPassword) confirmPassword.type = 'password';

    document.querySelectorAll('#settingAccountModal .password-eye-btn, #settingAccountModal .password-toggle-btn').forEach(function(btn) {
        btn.classList.remove('is-visible');
        btn.setAttribute('title', 'Show password');
        btn.setAttribute('aria-label', 'Show password');
    });
}

function saveSettingAccount() {
    var oldUsername = document.getElementById('account_old_username').value.trim();
    var username = document.getElementById('account_username').value.trim();
    var password = document.getElementById('account_password').value.trim();
    var confirmPassword = document.getElementById('account_confirm_password').value.trim();

    if (!username) {
        settingAccountAlert('Username cannot be empty.');
        return;
    }

    // New 或 Edit 改名時，Username 必須符合 6~8 字元規則。
    // Edit 未改名時允許既有 guest/admin/user1 等舊帳號繼續修改密碼或刪除。
    if (settingAccountMode === 'new' || username !== oldUsername) {
        if (!settingAccountValidUsername(username)) {
            settingAccountAlert('Username must be 6 to 8 characters and only allows A-Z, a-z, 0-9.');
            return;
        }
    } else if (!settingAccountValidText(username)) {
        settingAccountAlert('Username only allows A-Z, a-z, 0-9.');
        return;
    }

    if (settingAccountMode === 'new' && !password) {
        settingAccountAlert('Password cannot be empty.');
        return;
    }

    if (password !== '') {
        if (!settingAccountValidPassword(password)) {
            settingAccountAlert('Password must be exactly 4 digits, 0-9.');
            return;
        }

        if (password !== confirmPassword) {
            settingAccountAlert('Confirm password is different.');
            return;
        }
    }

    var path = settingAccountMode === 'edit' ? 'account_user_update' : 'account_user_create';
    var payload = {
        old_username: oldUsername,
        username: username,
        password: password,
        confirm_password: confirmPassword,
        law: 1
    };

    settingAccountPost(path, payload).then(function(json) {
        settingAccountAlert(json.res_msg || (json.success ? 'Save success.' : 'Save failed.'));
        if (json.success) {
            closeSettingAccountModal();
            loadSettingAccountUsers();
        }
    }).catch(function(err) {
        settingAccountAlert(err.message || 'Save failed.');
    });
}


document.addEventListener('DOMContentLoaded', function() {
    configureSettingAccountAlertifyNoTitle();

    // 依 cookie username 控制 Account tab 顯示。
    applySettingAccountVisibility();

    // 預設顯示 Controller，避免所有區塊都被隱藏時畫面空白。
    // 只有 admin 才允許預設進 Account。
    const activeBtn = document.querySelector('.w3-center .button.active');
    if (activeBtn && activeBtn.id === 'bnt5' && isSettingAdminUser()) {
        SettingOpenButtonFinal('Account');
    } else {
        SettingOpenButtonFinal('Controller');
    }
});

</script>    

<style>
/* =====================================================
   Setting Account page - Job style fixed V2
   目標：Account 分頁仿照 Job 頁面，不讓 New/Edit/Delete 跑到左下角
   ===================================================== */
#AccountDisplay.setting-account-page {
    position: relative;
    width: 100%;
    min-height: calc(100vh - 125px);
    padding-bottom: 105px;
    box-sizing: border-box;
}

#AccountDisplay .account-table-container {
    width: 96%;
    margin: 18px auto 0 auto;
    overflow: hidden;
}

#AccountDisplay #style-accounttable {
    float: none;
    width: 100%;
    height: calc(100vh - 255px);
    min-height: 270px;
    max-height: 430px;
    overflow-y: auto;
    overflow-x: hidden;
}

#AccountDisplay .scrollbar-force-overflow {
    min-height: auto;
}

#AccountDisplay #account_user_table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

/* User List 隱藏 Law 後，No 欄縮小、User Name 欄放大 */
#AccountDisplay #account_user_table th:first-child,
#AccountDisplay #account_user_table td:first-child {
    width: 25%;
}

#AccountDisplay #account_user_table th:nth-child(2),
#AccountDisplay #account_user_table td:nth-child(2) {
    width: 75%;
}

#AccountDisplay #account_user_table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background-color: #616161;
    color: #fff;
    font-size: 18px;
    height: 40px;
    text-align: center;
    vertical-align: middle;
}

#AccountDisplay #account_user_table th,
#AccountDisplay #account_user_table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}

#AccountDisplay #account_user_table tbody td {
    height: 36px;
    font-size: 17px;
}

#AccountDisplay #account_user_table tbody tr {
    cursor: pointer;
}

#AccountDisplay #account_user_table tbody tr:nth-child(odd) td {
    background-color: #eeeeee;
}

#AccountDisplay #account_user_table tbody tr:nth-child(even) td {
    background-color: #ffffff;
}

/* 與 Job 頁面一致：onclick 選取列使用 .selected */
#AccountDisplay #account_user_table tbody tr.selected,
#AccountDisplay #account_user_table tbody tr.selected td {
    background-color: #9AC0CD !important;
    color: #000000;
    font-weight: normal;
}

/* Footer buttons：參考 Job 頁面，固定下方置中 */
#AccountDisplay .account-footer {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    padding: 12px 20px 22px 20px;
    background-color: #ffffff;
    text-align: center;
    z-index: 2;
    box-sizing: border-box;
}

#AccountDisplay .account-footer .buttonbox {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 28px;
    padding: 0;
    margin: 0 auto;
}

#AccountDisplay .account-footer .buttonbox input {
    width: 100px;
    height: 50px;
    background: #333333;
    color: #ffffff;
    font-size: 20px;
    border: 2px outset #ffffff;
    border-radius: 10px;
    transition: color 0.3s, background-color 0.3s, transform 0.1s;
}

#AccountDisplay .account-footer .buttonbox input:hover {
    cursor: pointer;
    background: #DDDDDD;
    color: #000000;
}

#AccountDisplay .account-footer .buttonbox input:active {
    background-color: #DDDDDD;
    box-shadow: 0 5px #666;
    transform: translateY(4px);
}

/* Modal：仿 Job modal，尺寸固定避免破版 */
#settingAccountModal.modal {
    display: none;
    position: fixed;
    z-index: 30;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    padding-top: 42px;
    overflow: auto;
    background: rgba(255, 255, 255, 0.35);
}

#settingAccountModal .setting-account-modal {
    width: 58%;
    min-width: 620px;
    max-width: 760px;
    margin: 0 auto;
    background-color: #AAAAAA;
    border: 1px solid #888;
}

#settingAccountModal .modal-header {
    position: relative;
    background-color: #686767;
    height: 56px;
    color: #FFFFFF;
    font-size: 20px;
    display: flex;
    align-items: center;
    padding-left: 18px;
}

#settingAccountModal .modal-header h3 {
    margin: 0;
    font-size: 22px;
    font-weight: 500;
}

#settingAccountModal .account-modal-x {
    position: absolute;
    top: 6px;
    right: 10px;
    width: 44px;
    height: 44px;
    line-height: 36px;
    text-align: center;
    font-size: 32px;
    padding: 0;
}

#settingAccountModal .account-modal-body {
    background-color: #D8D8D8;
    padding: 44px 48px 70px 48px;
}

#settingAccountModal .account-form-row {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
}

#settingAccountModal .account-form-row .t1 {
    display: flex;
    align-items: center;
    justify-content: flex-start;
    width: 280px;
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #000;
}

#settingAccountModal .account-form-row .t2 {
    width: 230px;
    margin: 0;
}

#settingAccountModal .account-form-row .t2 input {
    width: 100%;
    height: 36px;
    font-size: 18px;
    border-radius: 7px;
    border: 1px solid #dddddd;
    background: #ffffff;
    padding: 4px 8px;
    box-sizing: border-box;
}

/* Password eye mask toggle：用眼睛符號取代 Show / Hide 文字 */
#settingAccountModal .account-password-wrap {
    position: relative;
    width: 100%;
}

#settingAccountModal .account-password-wrap input {
    width: 100%;
    padding-right: 44px;
}

#settingAccountModal .password-eye-btn,
#settingAccountModal .password-toggle-btn {
    position: absolute;
    top: 50%;
    right: 4px;
    transform: translateY(-50%);
    width: 36px;
    height: 30px;
    border: none;
    border-radius: 6px;
    background: transparent;
    color: #444444;
    cursor: pointer;
    padding: 0;
    font-size: 0; /* 保險：即使舊版按鈕還有 Show/Hide 文字，也不顯示 */
    display: flex;
    align-items: center;
    justify-content: center;
}

#settingAccountModal .password-eye-btn::before,
#settingAccountModal .password-toggle-btn::before {
    content: "\1F441";
    font-size: 22px;
    line-height: 1;
}

#settingAccountModal .password-eye-btn .eye-symbol,
#settingAccountModal .password-toggle-btn .eye-symbol {
    display: none; /* 使用 ::before 統一顯示，避免不同瀏覽器排版差異 */
}

#settingAccountModal .password-eye-btn.is-visible::after,
#settingAccountModal .password-toggle-btn.is-visible::after {
    content: "";
    position: absolute;
    width: 26px;
    height: 3px;
    border-radius: 3px;
    background: #444444;
    transform: rotate(-45deg);
}

#settingAccountModal .password-eye-btn:hover,
#settingAccountModal .password-toggle-btn:hover {
    background: rgba(0, 0, 0, 0.08);
}

#settingAccountModal .modal-footer {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 26px;
    background-color: #686767;
    height: 64px;
    padding: 0;
}

#settingAccountModal .button-modal {
    background-color: #3333ff;
    border: 1px outset #CCFFFF;
    border-radius: 8px;
    color: white;
    padding: 0;
    text-align: center;
    font-size: 20px;
    cursor: pointer;
    height: 42px;
    width: 96px;
}

#settingAccountModal .button-modal.closebtn {
    background-color: #888899;
}

#settingAccountModal .button-modal:hover {
    cursor: pointer;
    background: #336699;
    color: white;
}



/* operation_audit_log top tab button */
.operation-audit-top-button {
    min-width: 168px;
    padding-left: 10px !important;
    padding-right: 10px !important;
}

/* AlertifyJS：移除彈跳視窗預設 title/header（例如 AlertifyJS） */
.alertify .ajs-header,
.alertifyjs .ajs-header,
.ajs-dialog .ajs-header {
    display: none !important;
}

.alertify .ajs-dialog,
.alertifyjs .ajs-dialog {
    padding-top: 0 !important;
}

@media (max-width: 900px) {
    #AccountDisplay .account-footer .buttonbox {
        gap: 14px;
    }

    #AccountDisplay .account-footer .buttonbox input {
        width: 88px;
        height: 46px;
        font-size: 18px;
    }

    #settingAccountModal .setting-account-modal {
        width: 92%;
        min-width: 0;
    }

    #settingAccountModal .account-form-row {
        display: block;
    }

    #settingAccountModal .account-form-row .t1,
    #settingAccountModal .account-form-row .t2 {
        width: 100%;
        margin-bottom: 6px;
    }
}
</style>

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
