<!-- =====================================================
     Setting Account Tab / table user
     放在 setting/index.php 的 Account 分頁內容區塊
     ===================================================== -->
<div id="AccountDisplay" class="setting-account-page">
    <div class="table-container account-table-container">
        <div class="scrollbar" id="style-accounttable">
            <table id="account_user_table" class="table w3-table account-user-table">
                <thead id="header-table">
                    <tr class="w3-dark-grey">
                        <th>No</th>
                        <th>User Name</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody id="accountUserTbody" style="font-size: 1.8vmin;text-align: center;">
                    <tr><td colspan="3">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer account-footer">
        <div class="buttonbox">
            <input type="button" value="New"    onclick="settingAccountAction('new')">
            <input type="button" value="Edit"   onclick="settingAccountAction('edit')">
            <input type="button" value="Delete" onclick="settingAccountAction('delete')">
        </div>
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
                            <input type="text" class="form-control input-ms" id="account_username" maxlength="20" autocomplete="off">
                        </div>
                    </div>

                    <div class="row account-form-row">
                        <div class="col-5 t1">Password :</div>
                        <div class="col-5 t2">
                            <input type="password" class="form-control input-ms" id="account_password" maxlength="20" autocomplete="off">
                        </div>
                    </div>

                    <div class="row account-form-row">
                        <div class="col-5 t1">Confirm Password :</div>
                        <div class="col-5 t2">
                            <input type="password" class="form-control input-ms" id="account_confirm_password" maxlength="20" autocomplete="off">
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

<script>
// =====================================================
// Setting Account / table user
// Rule: A-Z / a-z / 0-9
// =====================================================
var selectedAccountUser = null;
var selectedAccountPassword = '';
var settingAccountRecordMap = {};
var settingAccountMode = 'new';

function settingAccountApi(path) {
    return window.location.protocol + '//' + window.location.hostname + '/idas/public/?url=Settings/' + path;
}

function settingAccountAlert(message) {
    if (window.alertify && typeof alertify.alert === 'function') {
        alertify.alert(message);
    } else {
        alert(message);
    }
}

function settingAccountValidText(value) {
    // 因為現有範例有 steve01 / peter02，所以數字包含 0~9。
    // 若真的要排除 0，改成 /^[A-Za-z1-9]+$/
    return /^[A-Za-z0-9]+$/.test(value);
}

function settingAccountEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


function settingAccountPickPassword(row) {
    row = row || {};

    // Backend versions may use different field names. Try all common names.
    var candidates = [
        row.passwd,
        row.password,
        row.pwd,
        row.PASSWD,
        row.PASSWORD,
        row.PWD,
        row.user_passwd,
        row.user_password
    ];

    for (var i = 0; i < candidates.length; i++) {
        if (candidates[i] !== undefined && candidates[i] !== null && String(candidates[i]) !== '') {
            return String(candidates[i]);
        }
    }

    return '';
}

function settingAccountRememberRecord(row) {
    row = row || {};
    var name = String(row.name || row.username || row.user_name || '').trim();
    var passwd = settingAccountPickPassword(row);

    if (name !== '') {
        settingAccountRecordMap[name.toLowerCase()] = row;
    }

    return passwd;
}

function settingAccountFindPasswordByUsername(username) {
    username = String(username || '').trim();

    if (username === '') {
        return '';
    }

    var row = settingAccountRecordMap[username.toLowerCase()];
    var passwd = settingAccountPickPassword(row);

    if (passwd !== '') {
        return passwd;
    }

    // Fallback: read from selected table row.
    var selectedRow = document.querySelector('#AccountDisplay .account-user-row.selected, #AccountDisplay .account-user-row.active');
    if (selectedRow) {
        passwd = selectedRow.getAttribute('data-passwd') || '';
        if (passwd !== '') {
            return passwd;
        }
    }

    // Fallback: find by data-name.
    var rows = document.querySelectorAll('#AccountDisplay .account-user-row');
    for (var i = 0; i < rows.length; i++) {
        if ((rows[i].getAttribute('data-name') || '').toLowerCase() === username.toLowerCase()) {
            passwd = rows[i].getAttribute('data-passwd') || '';
            if (passwd !== '') {
                return passwd;
            }
        }
    }

    return '';
}

function settingAccountFillPasswordInputs(passwd) {
    var passwordInput = document.getElementById('account_password');
    var confirmInput = document.getElementById('account_confirm_password');

    passwd = String(passwd || '');

    if (passwordInput) {
        passwordInput.value = passwd;
    }

    if (confirmInput) {
        confirmInput.value = passwd;
    }

    resetSettingAccountPasswordMask();
}

function settingAccountReloadSelectedPassword() {
    if (!selectedAccountUser) {
        return;
    }

    settingAccountPost('account_user_list', {}).then(function(json) {
        if (!json || !json.success) {
            return;
        }

        settingAccountRecordMap = {};

        (json.records || []).forEach(function(row) {
            settingAccountRememberRecord(row);
        });

        var passwd = settingAccountFindPasswordByUsername(selectedAccountUser);
        selectedAccountPassword = passwd;

        if (settingAccountMode === 'edit' && passwd !== '') {
            settingAccountFillPasswordInputs(passwd);
        }
    }).catch(function(err) {
        console.warn('Reload selected account password failed:', err);
    });
}

function settingAccountPost(path, data) {
    var formData = new FormData();
    Object.keys(data || {}).forEach(function(key) {
        formData.append(key, data[key]);
    });

    return fetch(settingAccountApi(path), {
        method: 'POST',
        body: formData,
        cache: 'no-store'
    }).then(function(res) {
        return res.text();
    }).then(function(text) {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Account API raw response:', text);
            throw new Error('API response is not JSON.');
        }
    });
}

function loadSettingAccountUsers() {
    settingAccountPost('account_user_list', {}).then(function(json) {
        if (!json.success) {
            settingAccountAlert(json.res_msg || 'Load account failed.');
            return;
        }

        renderSettingAccountUsers(json.records || []);
    }).catch(function(err) {
        settingAccountAlert(err.message || 'Load account failed.');
    });
}

function renderSettingAccountUsers(records) {
    var tbody = document.getElementById('accountUserTbody');
    if (!tbody) return;

    selectedAccountUser = null;
    selectedAccountPassword = '';
    settingAccountRecordMap = {};

    if (!records.length) {
        tbody.innerHTML = '<tr><td colspan="3">No Data</td></tr>';
        return;
    }

    tbody.innerHTML = records.map(function(row, index) {
        var name = settingAccountEscape(row.name || '');
        var passwd = settingAccountEscape(settingAccountRememberRecord(row));
        var dateText = settingAccountEscape(row.date || row.time || '');

        return '<tr class="account-user-row" data-name="' + name + '" data-passwd="' + passwd + '" onclick="selectSettingAccountUser(this)">' +
            '<td>' + (index + 1) + '</td>' +
            '<td>' + name + '</td>' +
            '<td>' + dateText + '</td>' +
            '</tr>';
    }).join('');
}

function selectSettingAccountUser(row) {
    document.querySelectorAll('.account-user-row').forEach(function(tr) {
        tr.classList.remove('active');
    });

    row.classList.add('active');
    selectedAccountUser = row.getAttribute('data-name') || '';
    selectedAccountPassword = row.getAttribute('data-passwd') || settingAccountFindPasswordByUsername(selectedAccountUser);
}


function settingAccountGetEditPassword(username) {
    username = String(username || '').trim();

    // 1) row/cache fallback first
    var cached = '';

    if (typeof selectedAccountPassword !== 'undefined' && selectedAccountPassword) {
        cached = String(selectedAccountPassword);
    }

    if (!cached && typeof settingAccountFindPasswordByUsername === 'function') {
        cached = String(settingAccountFindPasswordByUsername(username) || '');
    }

    if (!cached) {
        var row = document.querySelector('#AccountDisplay .account-user-row.selected, #AccountDisplay .account-user-row.active, #accountUserTbody .account-user-row.selected, #accountUserTbody .account-user-row.active');
        if (row) {
            cached = String(row.getAttribute('data-passwd') || '');
        }
    }

    // 2) Always ask backend. If backend fails, use cached.
    return settingAccountPost('account_user_get_password', {
        username: username
    }).then(function(json) {
        if (json && json.success && json.passwd !== undefined && json.passwd !== null && String(json.passwd) !== '') {
            return String(json.passwd);
        }

        if (cached !== '') {
            return cached;
        }

        throw new Error(json && json.res_msg ? json.res_msg : 'Get password failed.');
    }).catch(function(err) {
        if (cached !== '') {
            return cached;
        }

        // 3) fallback: account_user_list
        return settingAccountPost('account_user_list', {}).then(function(json) {
            var records = (json && json.records) ? json.records : [];
            for (var i = 0; i < records.length; i++) {
                var row = records[i] || {};
                var name = String(row.name || row.username || row.user_name || '');
                if (name.toLowerCase() === username.toLowerCase()) {
                    var p = row.passwd || row.password || row.pwd || row.PASSWD || row.PASSWORD || row.PWD || '';
                    if (p !== undefined && p !== null && String(p) !== '') {
                        return String(p);
                    }
                }
            }

            throw err;
        });
    });
}

function settingAccountSetMaskedPassword(realPassword) {
    var p1 = document.getElementById('account_password');
    var p2 = document.getElementById('account_confirm_password');

    realPassword = String(realPassword || '');

    // 顯示固定 ****，但真正密碼存在 data-real-password。
    // 這樣一定會看到 ****，不會因瀏覽器 password manager / reset 而看起來空白。
    if (p1) {
        p1.type = 'text';
        p1.value = realPassword ? '****' : '';
        p1.setAttribute('data-real-password', realPassword);
        p1.setAttribute('data-mask-mode', realPassword ? 'masked' : 'empty');
        p1.setAttribute('autocomplete', 'off');
    }

    if (p2) {
        p2.type = 'text';
        p2.value = realPassword ? '****' : '';
        p2.setAttribute('data-real-password', realPassword);
        p2.setAttribute('data-mask-mode', realPassword ? 'masked' : 'empty');
        p2.setAttribute('autocomplete', 'off');
    }

    document.querySelectorAll('#settingAccountModal .password-eye-btn').forEach(function(btn) {
        btn.classList.remove('is-visible');
        btn.setAttribute('title', saT('account_show_password', 'Show password'));
        btn.setAttribute('aria-label', saT('account_show_password', 'Show password'));
    });
}

function settingAccountOpenEditWithMaskedPassword(username) {
    document.getElementById('settingAccountModalTitle').innerText = saT('account_edit_title', 'Edit Account');
    document.getElementById('account_old_username').value = username;
    document.getElementById('account_username').value = username;

    // 先不開視窗，等密碼回來再開。
    settingAccountGetEditPassword(username).then(function(realPassword) {
        selectedAccountPassword = realPassword;

        settingAccountSetMaskedPassword(realPassword);
        openSettingAccountModal();

        // 防止 openSettingAccountModal 或其他舊函式重設欄位。
        window.setTimeout(function() { settingAccountSetMaskedPassword(realPassword); }, 0);
        window.setTimeout(function() { settingAccountSetMaskedPassword(realPassword); }, 150);
        window.setTimeout(function() { settingAccountSetMaskedPassword(realPassword); }, 400);
    }).catch(function(err) {
        console.error('[Account Edit] get password failed:', err);
        settingAccountAlert((err && err.message) ? err.message : 'Get password failed.');
    });
}

function settingAccountResolvePasswordForSave(inputId) {
    var input = document.getElementById(inputId);
    if (!input) return '';

    var mode = input.getAttribute('data-mask-mode') || '';
    var real = input.getAttribute('data-real-password') || '';

    if (mode === 'masked' && input.value === '****') {
        return real;
    }

    return input.value.trim();
}

function settingAccountAction(mode) {
    settingAccountMode = mode;

    if (mode === 'new') {
        document.getElementById('settingAccountModalTitle').innerText = 'New Account';
        document.getElementById('account_old_username').value = '';
        document.getElementById('account_username').value = '';
        document.getElementById('account_password').value = '';
        document.getElementById('account_confirm_password').value = '';
        openSettingAccountModal();
        return;
    }

    if (!selectedAccountUser) {
        settingAccountAlert('Please select one account.');
        return;
    }

    if (mode === 'edit') {
        settingAccountOpenEditWithMaskedPassword(selectedAccountUser);
        return;
    }

    if (mode === 'delete') {
        var ok = confirm('Delete Account: ' + selectedAccountUser + ' ?');
        if (!ok) return;

        settingAccountPost('account_user_delete', {
            username: selectedAccountUser
        }).then(function(json) {
            settingAccountAlert(json.res_msg || (json.success ? 'Delete success.' : 'Delete failed.'));
            if (json.success) loadSettingAccountUsers();
        }).catch(function(err) {
            settingAccountAlert(err.message || 'Delete failed.');
        });
    }
}

function openSettingAccountModal() {
    var modal = document.getElementById('settingAccountModal');
    if (modal) modal.style.display = 'block';
}

function closeSettingAccountModal() {
    var modal = document.getElementById('settingAccountModal');
    if (modal) modal.style.display = 'none';
}

function saveSettingAccount() {
    var oldUsername = document.getElementById('account_old_username').value.trim();
    var username = document.getElementById('account_username').value.trim();
    var password = settingAccountResolvePasswordForSave('account_password');
    var confirmPassword = settingAccountResolvePasswordForSave('account_confirm_password');

    if (!username) {
        settingAccountAlert('Username cannot be empty.');
        return;
    }

    if (!settingAccountValidText(username)) {
        settingAccountAlert('Username only allows A-Z, a-z, 0-9.');
        return;
    }

    if (settingAccountMode === 'new') {
        if (!password) {
            settingAccountAlert('Password cannot be empty.');
            return;
        }
    }

    if (password !== '') {
        if (!settingAccountValidText(password)) {
            settingAccountAlert('Password only allows A-Z, a-z, 0-9.');
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
    if (document.getElementById('accountUserTbody')) {
        loadSettingAccountUsers();
    }
});
</script>

<style>
.setting-account-page .account-table-container {
    width: 96%;
    margin: 18px auto 0 auto;
}

.account-user-table th,
.account-user-table td {
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
}

.account-user-table tbody tr {
    cursor: pointer;
}

.account-user-table tbody tr.active td {
    background: #b7d4ff !important;
    color: #000;
    font-weight: 800;
}

.account-footer {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 36px;
}

.setting-account-modal {
    width: 58%;
    min-width: 620px;
    margin: auto;
}

.setting-account-modal .modal-header {
    background: #666;
    color: #fff;
}

.account-modal-x {
    width: 50px;
    margin: 3px;
}

.account-modal-body {
    background: #d8d8d8;
    padding: 42px 48px 70px 48px;
}

.account-form-row {
    align-items: center;
    margin-bottom: 24px;
}

.account-form-row .t1 {
    text-align: right;
    font-size: 20px;
    font-weight: 700;
    color: #000;
}

.account-form-row .t2 input {
    height: 36px;
    font-size: 18px;
    border-radius: 7px;
}

.setting-account-modal .modal-footer {
    background: #666;
}
</style>
