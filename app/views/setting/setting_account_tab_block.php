<?php
if (!function_exists('settingAccountTabLocale')) {
    function settingAccountTabLocale() {
        $raw = '';
        if (isset($_COOKIE['language'])) {
            $raw = strtolower(trim((string)$_COOKIE['language']));
        } elseif (isset($_COOKIE['lang'])) {
            $raw = strtolower(trim((string)$_COOKIE['lang']));
        }

        $raw = str_replace('_', '-', $raw);

        if ($raw === 'zh-tw' || $raw === 'zh-hant' || $raw === 'tw') return 'zh-tw';
        if ($raw === 'zh-cn' || $raw === 'zh-hans' || $raw === 'cn') return 'zh-cn';
        if ($raw === 'en' || $raw === 'en-us') return 'en-us';

        return 'en-us';
    }
}

if (!function_exists('settingAccountTabTextMap')) {
    function settingAccountTabTextMap() {
        $lang = settingAccountTabLocale();

        $dict = [
            'en-us' => [
                'account_no' => 'No',
                'account_user_name' => 'User Name',
                'account_date' => 'Date',
                'account_loading' => 'Loading...',
                'account_new' => 'New',
                'account_edit' => 'Edit',
                'account_delete' => 'Delete',
                'account_new_title' => 'New Account',
                'account_edit_title' => 'Edit Account',
                'account_username' => 'Username',
                'account_password' => 'Password',
                'account_confirm_password' => 'Confirm Password',
                'account_permission' => 'Permission',
                'account_permission_rule' => 'Permission must be 1: guest or 3: operator.',
                'account_save' => 'Save',
                'account_close' => 'Close',
                'account_show_password' => 'Show password',
                'account_hide_password' => 'Hide password',
            ],
            'zh-tw' => [
                'account_no' => '編號',
                'account_user_name' => '使用者名稱',
                'account_date' => '日期',
                'account_loading' => '載入中...',
                'account_new' => '新增',
                'account_edit' => '編輯',
                'account_delete' => '刪除',
                'account_new_title' => '新增帳號',
                'account_edit_title' => '編輯帳號',
                'account_username' => '使用者名稱',
                'account_password' => '密碼',
                'account_confirm_password' => '確認密碼',
                'account_permission' => '權限',
                'account_permission_rule' => '權限必須選擇 1: guest 或 3: operator。',
                'account_save' => '儲存',
                'account_close' => '關閉',
                'account_show_password' => '顯示密碼',
                'account_hide_password' => '隱藏密碼',
            ],
            'zh-cn' => [
                'account_no' => '编号',
                'account_user_name' => '使用者名称',
                'account_date' => '日期',
                'account_loading' => '载入中...',
                'account_new' => '新增',
                'account_edit' => '编辑',
                'account_delete' => '删除',
                'account_new_title' => '新增账号',
                'account_edit_title' => '编辑账号',
                'account_username' => '使用者名称',
                'account_password' => '密码',
                'account_confirm_password' => '确认密码',
                'account_permission' => '权限',
                'account_permission_rule' => '权限必须选择 1: guest 或 3: operator。',
                'account_save' => '储存',
                'account_close' => '关闭',
                'account_show_password' => '显示密码',
                'account_hide_password' => '隐藏密码',
            ],
        ];

        return $dict[$lang] ?? $dict['en-us'];
    }
}

if (!function_exists('settingAccountTabT')) {
    function settingAccountTabT($key) {
        $map = settingAccountTabTextMap();
        return htmlspecialchars($map[$key] ?? $key, ENT_QUOTES, 'UTF-8');
    }
}

$settingAccountTabLocale = settingAccountTabLocale();
$settingAccountTabColon = ($settingAccountTabLocale === 'en-us') ? ' :' : '：';
$settingAccountTabTextJson = json_encode(settingAccountTabTextMap(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

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
                        <th id="accountUserNoHeader"><?php echo settingAccountTabT('account_no'); ?></th>
                        <th id="accountUserNameHeader"><?php echo settingAccountTabT('account_user_name'); ?></th>
                        <th id="accountUserDateHeader"><?php echo settingAccountTabT('account_date'); ?></th>
                    </tr>
                </thead>
                <tbody id="accountUserTbody" style="font-size: 1.8vmin;text-align: center;">
                    <tr><td colspan="3"><?php echo settingAccountTabT('account_loading'); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer account-footer">
        <div class="buttonbox">
            <input type="button" id="account_new_btn" value="<?php echo settingAccountTabT('account_new'); ?>" onclick="settingAccountAction('new')">
            <input type="button" id="account_edit_btn" value="<?php echo settingAccountTabT('account_edit'); ?>" onclick="settingAccountAction('edit')">
            <input type="button" id="account_delete_btn" value="<?php echo settingAccountTabT('account_delete'); ?>" onclick="settingAccountAction('delete')">
        </div>
    </div>

    <!-- New / Edit Account Modal -->
    <div id="settingAccountModal" class="modal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content w3-animate-zoom setting-account-modal">
                <header class="w3-container modal-header">
                    <span onclick="closeSettingAccountModal();" class="w3-button w3-red w3-display-topright account-modal-x">&times;</span>
                    <h3 id="settingAccountModalTitle"><?php echo settingAccountTabT('account_new_title'); ?></h3>
                </header>

                <div class="modal-body account-modal-body">
                    <input type="hidden" id="account_old_username" value="">

                    <div class="row account-form-row">
                        <div class="col-5 t1" id="settingAccountUsernameLabel"><?php echo settingAccountTabT('account_username') . $settingAccountTabColon; ?></div>
                        <div class="col-5 t2">
                            <input type="text" class="form-control input-ms" id="account_username" maxlength="20" autocomplete="off">
                        </div>
                    </div>

                    <div class="row account-form-row">
                        <div class="col-5 t1" id="settingAccountPasswordLabel"><?php echo settingAccountTabT('account_password') . $settingAccountTabColon; ?></div>
                        <div class="col-5 t2">
                            <div class="account-password-wrap">
                                <input type="password" class="form-control input-ms" id="account_password" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,4)">
                                <button type="button" class="password-eye-btn" onclick="toggleSettingAccountPassword('account_password', this)" title="<?php echo settingAccountTabT('account_show_password'); ?>" aria-label="<?php echo settingAccountTabT('account_show_password'); ?>">
                                    <span class="eye-symbol">&#128065;</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row account-form-row">
                        <div class="col-5 t1" id="settingAccountConfirmPasswordLabel"><?php echo settingAccountTabT('account_confirm_password') . $settingAccountTabColon; ?></div>
                        <div class="col-5 t2">
                            <div class="account-password-wrap">
                                <input type="password" class="form-control input-ms" id="account_confirm_password" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,4)">
                                <button type="button" class="password-eye-btn" onclick="toggleSettingAccountPassword('account_confirm_password', this)" title="<?php echo settingAccountTabT('account_show_password'); ?>" aria-label="<?php echo settingAccountTabT('account_show_password'); ?>">
                                    <span class="eye-symbol">&#128065;</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="row account-form-row">
                        <div class="col-5 t1" id="settingAccountPermissionLabel"><?php echo settingAccountTabT('account_permission') . $settingAccountTabColon; ?></div>
                        <div class="col-5 t2">
                            <select class="form-control input-ms" id="account_law" autocomplete="off">
                                <option value="1" selected>1: guest</option>
                                <option value="3">3: operator</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer justify-content-center">
                    <button class="button-modal" id="settingAccountSaveBtn" onclick="saveSettingAccount();"><?php echo settingAccountTabT('account_save'); ?></button>
                    <button class="button-modal closebtn" id="settingAccountCloseBtn" onclick="closeSettingAccountModal();"><?php echo settingAccountTabT('account_close'); ?></button>
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
var settingAccountTabText = <?php echo $settingAccountTabTextJson; ?>;
function saT(key, fallback) {
    return (window.settingAccountTabText && window.settingAccountTabText[key]) || fallback || key;
}
function settingAccountProtectedCreateText() {
    var lang = '<?php echo $settingAccountTabLocale; ?>';
    if (lang === 'zh-tw') return '此帳號受保護，無法新增。';
    if (lang === 'zh-cn') return '此账号受保护，无法新增。';
    return 'This account is protected and cannot be created.';
}
function settingAccountProtectedSaveText() {
    var lang = '<?php echo $settingAccountTabLocale; ?>';
    if (lang === 'zh-tw') return '此帳號受保護，無法儲存。';
    if (lang === 'zh-cn') return '此账号受保护，无法储存。';
    return 'This account is protected and cannot be saved.';
}
function settingAccountProtectedDeleteText() {
    var lang = '<?php echo $settingAccountTabLocale; ?>';
    if (lang === 'zh-tw') return '此帳號受保護，無法刪除。';
    if (lang === 'zh-cn') return '此账号受保护，无法删除。';
    return 'This account is protected and cannot be deleted.';
}
function settingAccountIsProtectedUser(username) {
    var name = String(username || '').trim().toLowerCase();
    return name === 'guest' || name === 'admin' || name === 'kls';
}

function settingAccountApi(path) {
    return window.location.protocol + '//' + window.location.hostname + '/idas/public/?url=Settings/' + path;
}

function settingAccountAlert(message) {
    if (window.alertify && typeof IdasNotify.alert === 'function') {
        IdasNotify.alert(message);
    } else {
        IdasNotify.alert(message);
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

function settingAccountEyeButtonForInput(inputId) {
    var input = document.getElementById(inputId);
    if (!input || !input.parentNode) return null;
    return input.parentNode.querySelector('.password-eye-btn, .password-toggle-btn');
}

function settingAccountSetEyeVisible(btn, visible) {
    if (!btn) return;

    if (visible) {
        btn.classList.add('is-visible');
        btn.setAttribute('title', saT('account_hide_password', 'Hide password'));
        btn.setAttribute('aria-label', saT('account_hide_password', 'Hide password'));
    } else {
        btn.classList.remove('is-visible');
        btn.setAttribute('title', saT('account_show_password', 'Show password'));
        btn.setAttribute('aria-label', saT('account_show_password', 'Show password'));
    }
}

function settingAccountSetMaskedPassword(realPassword) {
    var p1 = document.getElementById('account_password');
    var p2 = document.getElementById('account_confirm_password');

    realPassword = String(realPassword || '').replace(/[^0-9]/g, '').slice(0, 4);

    // Edit Account style:
    //   Password         = real 4 digits + slash-eye icon
    //   Confirm Password = custom **** mask + normal eye icon
    if (p1) {
        p1.type = 'text';
        p1.value = realPassword;
        p1.setAttribute('data-real-password', realPassword);
        p1.setAttribute('data-mask-mode', 'visible');
        p1.setAttribute('autocomplete', 'off');
    }

    if (p2) {
        p2.type = 'text';
        p2.value = realPassword ? '****' : '';
        p2.setAttribute('data-real-password', realPassword);
        p2.setAttribute('data-mask-mode', realPassword ? 'masked' : 'empty');
        p2.setAttribute('autocomplete', 'off');
    }

    settingAccountSetEyeVisible(settingAccountEyeButtonForInput('account_password'), !!realPassword);
    settingAccountSetEyeVisible(settingAccountEyeButtonForInput('account_confirm_password'), false);
}

function settingAccountPrepareNewPasswordInputs() {
    var p1 = document.getElementById('account_password');
    var p2 = document.getElementById('account_confirm_password');

    // New Account uses the same visual rule as Edit Account.
    if (p1) {
        p1.type = 'text';
        p1.value = '';
        p1.setAttribute('data-real-password', '');
        p1.setAttribute('data-mask-mode', 'visible');
        p1.setAttribute('autocomplete', 'off');
    }

    if (p2) {
        p2.type = 'text';
        p2.value = '';
        p2.setAttribute('data-real-password', '');
        p2.setAttribute('data-mask-mode', 'empty');
        p2.setAttribute('autocomplete', 'off');
    }

    settingAccountSetEyeVisible(settingAccountEyeButtonForInput('account_password'), false);
    settingAccountSetEyeVisible(settingAccountEyeButtonForInput('account_confirm_password'), false);
}

function settingAccountPasswordInputChange(input) {
    if (!input) return;

    var value = String(input.value || '').replace(/[^0-9]/g, '').slice(0, 4);
    input.type = 'text';
    input.value = value;
    input.setAttribute('data-real-password', value);
    input.setAttribute('data-mask-mode', 'visible');
    settingAccountSetEyeVisible(settingAccountEyeButtonForInput(input.id), true);
}

function settingAccountUpdateConfirmMask(input, real) {
    if (!input) return;

    real = String(real || '').replace(/[^0-9]/g, '').slice(0, 4);
    input.type = 'text';
    input.value = real ? new Array(real.length + 1).join('*') : '';
    input.setAttribute('data-real-password', real);
    input.setAttribute('data-mask-mode', real ? 'masked' : 'empty');
    settingAccountSetEyeVisible(settingAccountEyeButtonForInput(input.id), false);
}

function settingAccountConfirmPasswordKeydown(event) {
    var input = event.target;
    if (!input) return;

    var mode = input.getAttribute('data-mask-mode') || '';

    // When the user intentionally shows the confirm password, let normal typing happen.
    if (mode === 'visible') {
        return;
    }

    if (event.ctrlKey || event.metaKey || event.altKey || event.key === 'Tab' || event.key === 'ArrowLeft' || event.key === 'ArrowRight' || event.key === 'Home' || event.key === 'End') {
        return;
    }

    var real = String(input.getAttribute('data-real-password') || '').replace(/[^0-9]/g, '').slice(0, 4);

    if (/^[0-9]$/.test(event.key)) {
        event.preventDefault();
        if (real.length < 4) real += event.key;
        settingAccountUpdateConfirmMask(input, real);
        return;
    }

    if (event.key === 'Backspace') {
        event.preventDefault();
        real = real.slice(0, -1);
        settingAccountUpdateConfirmMask(input, real);
        return;
    }

    if (event.key === 'Delete' || event.key === 'Escape') {
        event.preventDefault();
        settingAccountUpdateConfirmMask(input, '');
        return;
    }

    event.preventDefault();
}

function settingAccountConfirmPasswordInputChange(input) {
    if (!input) return;

    var mode = input.getAttribute('data-mask-mode') || '';
    if (mode === 'visible') {
        var value = String(input.value || '').replace(/[^0-9]/g, '').slice(0, 4);
        input.value = value;
        input.setAttribute('data-real-password', value);
        input.setAttribute('data-mask-mode', 'visible');
        settingAccountSetEyeVisible(settingAccountEyeButtonForInput(input.id), true);
    }
}

function settingAccountConfirmPasswordPaste(event) {
    var input = event.target;
    if (!input) return;

    var mode = input.getAttribute('data-mask-mode') || '';
    if (mode === 'visible') return;

    event.preventDefault();
    var text = '';
    if (event.clipboardData && typeof event.clipboardData.getData === 'function') {
        text = event.clipboardData.getData('text') || '';
    }
    settingAccountUpdateConfirmMask(input, text);
}

function installSettingAccountPasswordHandlers() {
    var p1 = document.getElementById('account_password');
    var p2 = document.getElementById('account_confirm_password');

    if (p1 && !p1.getAttribute('data-account-handler-installed')) {
        p1.setAttribute('data-account-handler-installed', '1');
        p1.addEventListener('input', function() { settingAccountPasswordInputChange(p1); });
        p1.addEventListener('paste', function(event) {
            window.setTimeout(function() { settingAccountPasswordInputChange(p1); }, 0);
        });
    }

    if (p2 && !p2.getAttribute('data-account-handler-installed')) {
        p2.setAttribute('data-account-handler-installed', '1');
        p2.addEventListener('keydown', settingAccountConfirmPasswordKeydown);
        p2.addEventListener('input', function() { settingAccountConfirmPasswordInputChange(p2); });
        p2.addEventListener('paste', settingAccountConfirmPasswordPaste);
    }
}

installSettingAccountPasswordHandlers();
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', installSettingAccountPasswordHandlers);
}

function settingAccountOpenEditWithMaskedPassword(username) {
    document.getElementById('settingAccountModalTitle').innerText = saT('account_edit_title', 'Edit Account');
    document.getElementById('account_old_username').value = username;
    document.getElementById('account_username').value = username;
    var lawEditInput = document.getElementById('account_law');
    if (lawEditInput) {
        var selectedRow = document.querySelector('#AccountDisplay .account-user-row.selected, #AccountDisplay .account-user-row.active');
        var selectedLaw = selectedRow ? String(selectedRow.getAttribute('data-law') || '1') : '1';
        lawEditInput.value = (selectedLaw === '3') ? '3' : '1';
    }

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
        document.getElementById('settingAccountModalTitle').innerText = (window.settingAccountTabText && settingAccountTabText.account_new_title) || 'New Account';
        document.getElementById('account_old_username').value = '';
        document.getElementById('account_username').value = '';
        var lawNewInput = document.getElementById('account_law');
        if (lawNewInput) lawNewInput.value = '1';
        document.getElementById('account_username').removeAttribute('readonly');
        document.getElementById('account_username').classList.remove('account-username-readonly');

        selectedAccountPassword = '';

        openSettingAccountModal();
        settingAccountPrepareNewPasswordInputs();
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
        if (settingAccountIsProtectedUser(selectedAccountUser)) {
            settingAccountAlert(settingAccountProtectedDeleteText());
            return;
        }
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


function settingAccountEyeText(key, fallback) {
    if (typeof saT === 'function') return saT(key, fallback);
    if (window.settingAccountTabText && window.settingAccountTabText[key]) return window.settingAccountTabText[key];
    return fallback || key;
}

function toggleSettingAccountPassword(inputId, btn) {
    var input = document.getElementById(inputId);
    if (!input) return;

    var mode = input.getAttribute('data-mask-mode') || '';
    var real = input.getAttribute('data-real-password') || input.value || '';

    if (mode === 'masked' || input.value === '****') {
        input.type = 'text';
        input.value = real;
        input.setAttribute('data-mask-mode', 'visible');
        if (btn) {
            btn.classList.add('is-visible');
            btn.setAttribute('title', settingAccountEyeText('account_hide_password', 'Hide password'));
            btn.setAttribute('aria-label', settingAccountEyeText('account_hide_password', 'Hide password'));
        }
        return;
    }

    if (mode === 'visible') {
        real = input.value || real;
        input.type = 'text';
        input.value = real ? '****' : '';
        input.setAttribute('data-real-password', real);
        input.setAttribute('data-mask-mode', real ? 'masked' : 'empty');
        if (btn) {
            btn.classList.remove('is-visible');
            btn.setAttribute('title', settingAccountEyeText('account_show_password', 'Show password'));
            btn.setAttribute('aria-label', settingAccountEyeText('account_show_password', 'Show password'));
        }
        return;
    }

    input.type = input.type === 'password' ? 'text' : 'password';
    if (btn) btn.classList.toggle('is-visible', input.type === 'text');
}

function resetSettingAccountPasswordMask() {
    var password = document.getElementById('account_password');
    var confirmPassword = document.getElementById('account_confirm_password');
    var isEditMasked =
        settingAccountMode === 'edit' &&
        (
            (password && password.getAttribute('data-real-password')) ||
            (confirmPassword && confirmPassword.getAttribute('data-real-password'))
        );

    if (!isEditMasked) {
        if (password) password.type = 'password';
        if (confirmPassword) confirmPassword.type = 'password';
    }

    document.querySelectorAll('#settingAccountModal .password-eye-btn, #settingAccountModal .password-toggle-btn').forEach(function(btn) {
        btn.classList.remove('is-visible');
        btn.setAttribute('title', settingAccountEyeText('account_show_password', 'Show password'));
        btn.setAttribute('aria-label', settingAccountEyeText('account_show_password', 'Show password'));
    });
}

function openSettingAccountModal() {
    resetSettingAccountPasswordMask();
    var modal = document.getElementById('settingAccountModal');
    if (modal) modal.style.display = 'block';
}

function closeSettingAccountModal() {
    var modal = document.getElementById('settingAccountModal');
    if (modal) modal.style.display = 'none';
    resetSettingAccountPasswordMask();
}

function saveSettingAccount() {
    var oldUsername = document.getElementById('account_old_username').value.trim();
    var username = document.getElementById('account_username').value.trim();
    if (settingAccountMode === 'edit' && String(oldUsername || '').trim().toLowerCase() === 'kls') {
        settingAccountAlert(settingAccountProtectedSaveText());
        return;
    }
    if (settingAccountMode === 'new' && String(username || '').trim().toLowerCase() === 'kls') {
        settingAccountAlert(settingAccountProtectedCreateText());
        return;
    }
    var password = settingAccountResolvePasswordForSave('account_password');
    var confirmPassword = settingAccountResolvePasswordForSave('account_confirm_password');
    var lawInput = document.getElementById('account_law');
    var law = lawInput ? String(lawInput.value || '1') : '1';
    law = (law === '3') ? '3' : '1';

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

    if (!['1', '3'].includes(String(law))) {
        settingAccountAlert((window.settingAccountTabText && window.settingAccountTabText.account_permission_rule) || 'Permission must be 1: guest or 3: operator.');
        return;
    }

    var path = settingAccountMode === 'edit' ? 'account_user_update' : 'account_user_create';
    var payload = {
        old_username: oldUsername,
        username: username,
        password: password,
        confirm_password: confirmPassword,
        law: law
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


<script>
/* =====================================================
   Account modal language force patch
   目的：讓 Account New/Edit modal 的 label/button 跟 cookie language / lang 走同一套語系。
   ===================================================== */
(function() {
    var accountText = <?php echo $settingAccountTabTextJson ?: '{}'; ?>;

    function getAccountCookieLang() {
        var lang = 'en-us';
        try {
            var m = (document.cookie || '').match(/(?:^|;\s*)(?:language|lang)=([^;]+)/i);
            if (m && m[1]) lang = decodeURIComponent(m[1]).toLowerCase().replace('_', '-');
        } catch (e) {}

        if (lang === 'zh-tw' || lang === 'zh-hant' || lang === 'tw') return 'zh-tw';
        if (lang === 'zh-cn' || lang === 'zh-hans' || lang === 'cn') return 'zh-cn';
        return 'en-us';
    }

    function accountColon() {
        return getAccountCookieLang() === 'en-us' ? ' :' : '：';
    }

    function at(key, fallback) {
        if (typeof saT === 'function') {
            try {
                var v = saT(key, '');
                if (v && v !== key) return v;
            } catch (e) {}
        }

        var directMap = {
            account_new: 'New',
            account_edit: 'Edit',
            account_delete: 'Delete',
            account_save: 'save',
            account_close: 'close'
        };

        if (typeof window.settingAccountApplyStaticI18nV6 === 'function') {
            // V6 會另外處理，這裡仍保留本地字典避免 V6 未載入。
        }

        return accountText[key] || accountText[directMap[key]] || fallback || key;
    }

    function setTextById(id, text) {
        var el = document.getElementById(id);
        if (el) el.textContent = text;
    }

    function setValueById(id, text) {
        var el = document.getElementById(id);
        if (el) el.value = text;
    }

    function applyAccountModalLanguage() {
        setTextById('accountUserNoHeader', at('account_no', 'No'));
        setTextById('accountUserNameHeader', at('account_user_name', 'User Name'));
        setTextById('accountUserDateHeader', at('account_date', 'Date'));

        setValueById('account_new_btn', at('account_new', 'New'));
        setValueById('account_edit_btn', at('account_edit', 'Edit'));
        setValueById('account_delete_btn', at('account_delete', 'Delete'));

        setTextById('settingAccountUsernameLabel', at('account_username', 'Username') + accountColon());
        setTextById('settingAccountPasswordLabel', at('account_password', 'Password') + accountColon());
        setTextById('settingAccountConfirmPasswordLabel', at('account_confirm_password', 'Confirm Password') + accountColon());
        setTextById('settingAccountPermissionLabel', at('account_permission', 'Permission') + accountColon());

        setTextById('settingAccountSaveBtn', at('account_save', 'Save'));
        setTextById('settingAccountCloseBtn', at('account_close', 'Close'));

        var modalTitle = document.getElementById('settingAccountModalTitle');
        if (modalTitle) {
            var mode = window.settingAccountMode || 'new';
            modalTitle.textContent = (mode === 'edit') ? at('account_edit_title', 'Edit Account') : at('account_new_title', 'New Account');
        }
    }

    window.applySettingAccountModalLanguage = applyAccountModalLanguage;

    function installAccountLanguageHooks() {
        if (typeof window.settingAccountAction === 'function' && !window.settingAccountAction.__langWrapped) {
            var oldAction = window.settingAccountAction;
            window.settingAccountAction = function(mode) {
                var ret = oldAction.apply(this, arguments);
                setTimeout(applyAccountModalLanguage, 0);
                setTimeout(applyAccountModalLanguage, 80);
                return ret;
            };
            window.settingAccountAction.__langWrapped = true;
        }

        if (typeof window.openSettingAccountModal === 'function' && !window.openSettingAccountModal.__langWrapped) {
            var oldOpen = window.openSettingAccountModal;
            window.openSettingAccountModal = function() {
                var ret = oldOpen.apply(this, arguments);
                setTimeout(applyAccountModalLanguage, 0);
                return ret;
            };
            window.openSettingAccountModal.__langWrapped = true;
        }

        applyAccountModalLanguage();

        if (typeof window.settingAccountApplyStaticI18nV6 === 'function') {
            try { window.settingAccountApplyStaticI18nV6(); } catch (e) {}
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        installAccountLanguageHooks();
        setTimeout(installAccountLanguageHooks, 100);
        setTimeout(installAccountLanguageHooks, 500);
    });

    window.addEventListener('load', function() {
        installAccountLanguageHooks();
        setTimeout(installAccountLanguageHooks, 300);
    });

    setTimeout(installAccountLanguageHooks, 800);
})();
</script>
