<?php
if (!function_exists('settingAccountScriptDetectLocale')) {
    function settingAccountScriptDetectLocale() {
        global $text;

        // 1) 優先抓 cookie language / lang
        $cookieLang = '';
        if (isset($_COOKIE['language'])) {
            $cookieLang = strtolower(trim((string)$_COOKIE['language']));
        } elseif (isset($_COOKIE['lang'])) {
            $cookieLang = strtolower(trim((string)$_COOKIE['lang']));
        }

        $cookieLang = str_replace('_', '-', $cookieLang);

        if ($cookieLang === 'zh-tw' || $cookieLang === 'zh-hant' || $cookieLang === 'tw') {
            return 'zh-tw';
        }

        if ($cookieLang === 'zh-cn' || $cookieLang === 'zh-hans' || $cookieLang === 'cn') {
            return 'zh-cn';
        }

        if ($cookieLang === 'en' || $cookieLang === 'en-us') {
            return 'en-us';
        }

        // 2) cookie 沒有時才用頁面文字判斷
        $settingText = isset($text['setting']) ? (string)$text['setting'] : '';
        $accountText = isset($text['account']) ? (string)$text['account'] : '';

        if ($settingText === '設定' || $accountText === '帳號') {
            return 'zh-tw';
        }

        if ($settingText === '设定' || $accountText === '账号') {
            return 'zh-cn';
        }

        return 'en-us';
    }
}

if (!function_exists('settingAccountScriptFallbacks')) {
    function settingAccountScriptFallbacks() {
        $locale = settingAccountScriptDetectLocale();

        $dict = [
            'en-us' => [
                'account_loading' => 'Loading...',
                'account_load_failed' => 'Load failed',
                'account_load_failed_full' => 'Load account failed.',
                'account_no_data' => 'No Data',
                'account_alertify_not_loaded' => 'AlertifyJS confirm is not loaded.',
                'account_alertify_ok' => 'OK',
                'account_alertify_cancel' => 'CANCEL',
                'account_csv_only' => 'Only CSV file is allowed.',
                'account_import_response_not_json' => 'Import response is not JSON.',
                'account_import_success' => 'Import success.',
                'account_import_failed' => 'Import failed.',
                'account_new_title' => 'New Account',
                'account_edit_title' => 'Edit Account',
                'account_no' => 'No',
                'account_user_name' => 'User Name',
                'account_date' => 'Date',
                'account_username' => 'Username',
                'account_password' => 'Password',
                'account_confirm_password' => 'Confirm Password',
                'account_permission' => 'Permission',
                'account_permission_rule' => 'Permission must be 1: guest or 3: operator.',
                'account_new' => 'New',
                'account_edit' => 'Edit',
                'account_delete' => 'Delete',
                'account_save' => 'Save',
                'account_close' => 'Close',
                'account_import_confirm' => 'Import accounts from CSV?',
                'account_import_mode_confirm' => 'Select import mode:',
                'account_import_append' => 'Append',
                'account_import_overwrite' => 'Overwrite',
                'account_import_overwrite_confirm' => 'Overwrite will delete all accounts except service, guest, and admin. Continue?',
                'account_upload_confirm' => 'This will sync the iDAS user list to the controller and overwrite the controller user table. Continue?',
                'account_upload_to_controller' => 'Sync to Controller',
                'account_csv_template' => 'CSV Template',
                'account_builtin_account' => 'Built-in',
                'account_admin_account' => 'Admin',
                'account_total_accounts' => 'Accounts',
                'account_last_sync_time' => 'Last sync',
                'account_never_synced' => '-' ,
                'account_sync_status' => 'Sync status',
                'account_sync_success' => 'Success',
                'account_sync_failed' => 'Failed',
                'account_sync_rows' => 'Rows',
                'account_search_placeholder' => 'Search account...',
                'account_processing' => 'Processing...',
                'account_protected_action' => 'This account is protected and cannot be deleted.',
                'account_protected_create_action' => 'This account is protected and cannot be created.',
                'account_admin_rename_blocked' => 'The admin account name cannot be changed.',
                'account_select_one' => 'Please select one account.',
                'account_delete_confirm_prefix' => 'Are you sure you want to delete this account?<br><br>Account: ',
                'account_delete_confirm' => 'Are you sure you want to delete this account?<br><br>Account: {account}',
                'account_delete_success' => 'Delete success.',
                'account_delete_failed' => 'Delete failed.',
                'account_hide_password' => 'Hide password',
                'account_show_password' => 'Show password',
                'account_username_empty' => 'Username cannot be empty.',
                'account_username_rule' => 'Username must be 6 to 8 characters and only allows A-Z, a-z, 0-9.',
                'account_username_chars' => 'Username only allows A-Z, a-z, 0-9.',
                'account_password_empty' => 'Password cannot be empty.',
                'account_password_rule' => 'Password must be exactly 4 digits, 0-9.',
                'account_confirm_diff' => 'Confirm password is different.',
                'account_save_success' => 'Save success.',
                'account_save_failed' => 'Save failed.',
                'account_upload_success' => 'Upload success.',
                'account_upload_failed' => 'Upload failed.',
                'account_api_not_json' => 'API response is not JSON.',
                'account_http_status' => 'HTTP Status',
                'account_raw_response' => 'Raw response'
            ],
            'zh-tw' => [
                'account_loading' => '載入中...',
                'account_load_failed' => '載入失敗',
                'account_load_failed_full' => '帳號載入失敗。',
                'account_no_data' => '無資料',
                'account_alertify_not_loaded' => 'AlertifyJS confirm 未載入。',
                'account_alertify_ok' => '確定',
                'account_alertify_cancel' => '取消',
                'account_csv_only' => '只允許 CSV 檔案。',
                'account_import_response_not_json' => '匯入回應不是 JSON。',
                'account_import_success' => '匯入成功。',
                'account_import_failed' => '匯入失敗。',
                'account_new_title' => '新增帳號',
                'account_edit_title' => '編輯帳號',
                'account_no' => '編號',
                'account_user_name' => '使用者名稱',
                'account_date' => '日期',
                'account_username' => '使用者名稱',
                'account_password' => '密碼',
                'account_confirm_password' => '確認密碼',
                'account_permission' => '權限',
                'account_permission_rule' => '權限必須選擇 1: guest 或 3: operator。',
                'account_new' => '新增',
                'account_edit' => '編輯',
                'account_delete' => '刪除',
                'account_save' => '儲存',
                'account_close' => '關閉',
                'account_import_confirm' => '是否要從 CSV 匯入帳號？',
                'account_import_mode_confirm' => '請選擇匯入方式：',
                'account_import_append' => '新增',
                'account_import_overwrite' => '覆蓋',
                'account_import_overwrite_confirm' => '覆蓋會刪除除了 service、guest、admin 以外的所有帳號，確定繼續？',
                'account_upload_confirm' => '此動作會將 iDAS 使用者清單同步到控制器，並覆蓋控制器的 user table。確定繼續？',
                'account_upload_to_controller' => '同步到控制器',
                'account_csv_template' => 'CSV 範本',
                'account_builtin_account' => '內建帳號',
                'account_admin_account' => '管理員',
                'account_total_accounts' => '帳號數',
                'account_last_sync_time' => '最後同步時間',
                'account_never_synced' => '-',
                'account_sync_status' => '同步狀態',
                'account_sync_success' => '成功',
                'account_sync_failed' => '失敗',
                'account_sync_rows' => '筆數',
                'account_search_placeholder' => '搜尋帳號...',
                'account_processing' => '處理中...',
                'account_protected_action' => '此帳號受保護，無法刪除。',
                'account_protected_create_action' => '此帳號受保護，無法新增。',
                'account_admin_rename_blocked' => 'admin 帳號名稱不可變更。',
                'account_select_one' => '請選擇一個帳號。',
                'account_delete_confirm_prefix' => '是否確定刪除此帳號？<br><br>帳號：',
                'account_delete_confirm' => '是否確定刪除此帳號？<br><br>帳號：{account}',
                'account_delete_success' => '刪除成功。',
                'account_delete_failed' => '刪除失敗。',
                'account_hide_password' => '隱藏密碼',
                'account_show_password' => '顯示密碼',
                'account_username_empty' => '使用者名稱不可空白。',
                'account_username_rule' => '使用者名稱必須為 6 到 8 個字元，且只允許 A-Z、a-z、0-9。',
                'account_username_chars' => '使用者名稱只允許 A-Z、a-z、0-9。',
                'account_password_empty' => '密碼不可空白。',
                'account_password_rule' => '密碼必須為 4 碼數字 0-9。',
                'account_confirm_diff' => '確認密碼不同。',
                'account_save_success' => '儲存成功。',
                'account_save_failed' => '儲存失敗。',
                'account_upload_success' => '上傳成功。',
                'account_upload_failed' => '上傳失敗。',
                'account_api_not_json' => 'API 回應不是 JSON。',
                'account_http_status' => 'HTTP 狀態',
                'account_raw_response' => '原始回應'
            ],
            'zh-cn' => [
                'account_loading' => '载入中...',
                'account_load_failed' => '载入失败',
                'account_load_failed_full' => '账号载入失败。',
                'account_no_data' => '无资料',
                'account_alertify_not_loaded' => 'AlertifyJS confirm 未载入。',
                'account_alertify_ok' => '确定',
                'account_alertify_cancel' => '取消',
                'account_csv_only' => '只允许 CSV 文件。',
                'account_import_response_not_json' => '导入回应不是 JSON。',
                'account_import_success' => '导入成功。',
                'account_import_failed' => '导入失败。',
                'account_new_title' => '新增账号',
                'account_edit_title' => '编辑账号',
                'account_no' => '编号',
                'account_user_name' => '使用者名称',
                'account_date' => '日期',
                'account_username' => '使用者名称',
                'account_password' => '密码',
                'account_confirm_password' => '确认密码',
                'account_permission' => '权限',
                'account_permission_rule' => '权限必须选择 1: guest 或 3: operator。',
                'account_new' => '新增',
                'account_edit' => '编辑',
                'account_delete' => '删除',
                'account_save' => '储存',
                'account_close' => '关闭',
                'account_import_confirm' => '是否要从 CSV 导入账号？',
                'account_import_mode_confirm' => '请选择导入方式：',
                'account_import_append' => '新增',
                'account_import_overwrite' => '覆盖',
                'account_import_overwrite_confirm' => '覆盖会删除除了 service、guest、admin 以外的所有账号，确定继续？',
                'account_upload_confirm' => '此操作会将 iDAS 使用者清单同步到控制器，并覆盖控制器的 user table。确定继续？',
                'account_upload_to_controller' => '同步到控制器',
                'account_csv_template' => 'CSV 范本',
                'account_builtin_account' => '内建账号',
                'account_admin_account' => '管理员',
                'account_total_accounts' => '账号数',
                'account_last_sync_time' => '最后同步时间',
                'account_never_synced' => '-',
                'account_sync_status' => '同步状态',
                'account_sync_success' => '成功',
                'account_sync_failed' => '失败',
                'account_sync_rows' => '笔数',
                'account_search_placeholder' => '搜寻账号...',
                'account_processing' => '处理中...',
                'account_protected_action' => '此账号受保护，无法删除。',
                'account_protected_create_action' => '此账号受保护，无法新增。',
                'account_admin_rename_blocked' => 'admin 账号名称不可变更。',
                'account_select_one' => '请选择一个账号。',
                'account_delete_confirm_prefix' => '是否确定删除此账号？<br><br>账号：',
                'account_delete_confirm' => '是否确定删除此账号？<br><br>账号：{account}',
                'account_delete_success' => '删除成功。',
                'account_delete_failed' => '删除失败。',
                'account_hide_password' => '隐藏密码',
                'account_show_password' => '显示密码',
                'account_username_empty' => '使用者名称不可空白。',
                'account_username_rule' => '使用者名称必须为 6 到 8 个字符，且只允许 A-Z、a-z、0-9。',
                'account_username_chars' => '使用者名称只允许 A-Z、a-z、0-9。',
                'account_password_empty' => '密码不可空白。',
                'account_password_rule' => '密码必须为 4 码数字 0-9。',
                'account_confirm_diff' => '确认密码不同。',
                'account_save_success' => '储存成功。',
                'account_save_failed' => '储存失败。',
                'account_upload_success' => '上传成功。',
                'account_upload_failed' => '上传失败。',
                'account_api_not_json' => 'API 回应不是 JSON。',
                'account_http_status' => 'HTTP 状态',
                'account_raw_response' => '原始回应'
            ],
        ];

        return $dict[$locale] ?? $dict['en-us'];
    }
}

if (!function_exists('settingAccountScriptTextArray')) {
    function settingAccountScriptTextArray() {
        global $text;

        $fallbacks = settingAccountScriptFallbacks();
        $out = [];

        foreach ($fallbacks as $key => $default) {
            $out[$key] = (isset($text[$key]) && (string)$text[$key] !== '') ? $text[$key] : $default;
        }

        return $out;
    }
}
?>
<script>
/* Account Password Mask Fix V7 */

var settingAccountI18n = <?php echo json_encode(settingAccountScriptTextArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
var settingAccountClientFallbackI18n = {
    'en-us': {
        account_protected_action: 'This account is protected and cannot be deleted.',
        account_protected_edit_action: 'This account is protected and cannot be edited.',
        account_protected_save_action: 'This account is protected and cannot be saved.',
        account_protected_create_action: 'This account is protected and cannot be created.',
        account_protected_rename_blocked: 'This built-in account name cannot be changed.',
        account_admin_rename_blocked: 'The admin account name cannot be changed.',
        account_guest_rename_blocked: 'The guest account name cannot be changed.',
        account_permission_rule: 'Permission must be 1: guest or 3: operator.'
    },
    'zh-tw': {
        account_protected_action: '此帳號受保護，無法刪除。',
        account_protected_edit_action: '此帳號受保護，無法編輯。',
        account_protected_save_action: '此帳號受保護，無法儲存。',
        account_protected_create_action: '此帳號受保護，無法新增。',
        account_protected_rename_blocked: '內建帳號名稱不可變更。',
        account_admin_rename_blocked: 'admin 帳號名稱不可變更。',
        account_guest_rename_blocked: 'guest 帳號名稱不可變更。',
        account_permission_rule: '權限必須選擇 1: guest 或 3: operator。'
    },
    'zh-cn': {
        account_protected_action: '此账号受保护，无法删除。',
        account_protected_edit_action: '此账号受保护，无法编辑。',
        account_protected_save_action: '此账号受保护，无法储存。',
        account_protected_create_action: '此账号受保护，无法新增。',
        account_protected_rename_blocked: '内建账号名称不可变更。',
        account_admin_rename_blocked: 'admin 账号名称不可变更。',
        account_guest_rename_blocked: 'guest 账号名称不可变更。',
        account_permission_rule: '权限必须选择 1: guest 或 3: operator。'
    }
};
function settingAccountClientLang() {
    var lang = '';
    try {
        var cookie = document.cookie || '';
        var m = cookie.match(/(?:^|;\s*)(?:language|lang)=([^;]+)/i);
        if (m && m[1]) lang = decodeURIComponent(m[1]);
    } catch (e) {}

    lang = String(lang || '').toLowerCase().replace('_', '-');
    if (lang === 'zh-tw' || lang === 'zh-hant' || lang === 'tw') return 'zh-tw';
    if (lang === 'zh-cn' || lang === 'zh-hans' || lang === 'cn' || lang === 'zh') return 'zh-cn';
    if (lang === 'en' || lang === 'en-us') return 'en-us';

    var bodyText = '';
    try { bodyText = (document.body && document.body.innerText) ? document.body.innerText : ''; } catch (e) {}
    if (/設定|帳號|使用者名稱|儲存|關閉/.test(bodyText)) return 'zh-tw';
    if (/设定|账号|使用者名称|储存|关闭/.test(bodyText)) return 'zh-cn';
    return 'en-us';
}
function saT(key, fallback) {
    var direct = settingAccountI18n[key];
    if (direct && !(/^This account is protected/.test(String(direct)) && settingAccountClientLang() !== 'en-us')) {
        return direct;
    }
    var lang = settingAccountClientLang();
    return (settingAccountClientFallbackI18n[lang] && settingAccountClientFallbackI18n[lang][key]) || direct || fallback || key;
}


function settingAccountStripColonText(value) {
    return String(value === null || value === undefined ? '' : value).replace(/\s*[:：]+\s*$/g, '');
}

function settingAccountCurrentLangForLabel() {
    var lang = 'en-us';

    try {
        var cookie = document.cookie || '';
        var m = cookie.match(/(?:^|;\s*)(?:language|lang)=([^;]+)/i);
        if (m && m[1]) {
            lang = decodeURIComponent(m[1]).toLowerCase().replace('_', '-');
        }
    } catch (e) {}

    if (lang === 'zh-tw' || lang === 'zh-hant' || lang === 'tw') return 'zh-tw';
    if (lang === 'zh-cn' || lang === 'zh-hans' || lang === 'cn') return 'zh-cn';
    return 'en-us';
}

function settingAccountLabelI18n(key, fallback) {
    var base = '';

    if (typeof saT === 'function') {
        base = saT(key, fallback || key);
    } else {
        base = fallback || key;
    }

    base = settingAccountStripColonText(base);

    return base + (settingAccountCurrentLangForLabel() === 'en-us' ? ' :' : '：');
}

function applySettingAccountColonFix() {
    var usernameLabel = document.getElementById('settingAccountUsernameLabel');
    var passwordLabel = document.getElementById('settingAccountPasswordLabel');
    var confirmLabel = document.getElementById('settingAccountConfirmPasswordLabel');
    var permissionLabel = document.getElementById('settingAccountPermissionLabel');

    // 舊版 tab block 沒有 label id 時，改抓 modal 裡前三個 .t1。
    if (!usernameLabel || !passwordLabel || !confirmLabel || !permissionLabel) {
        var labels = document.querySelectorAll('#settingAccountModal .account-form-row .t1');
        usernameLabel = usernameLabel || labels[0];
        passwordLabel = passwordLabel || labels[1];
        confirmLabel = confirmLabel || labels[2];
        permissionLabel = permissionLabel || labels[3];
    }

    if (usernameLabel) usernameLabel.innerText = settingAccountLabelI18n('account_username', 'Username');
    if (passwordLabel) passwordLabel.innerText = settingAccountLabelI18n('account_password', 'Password');
    if (confirmLabel) confirmLabel.innerText = settingAccountLabelI18n('account_confirm_password', 'Confirm Password');
    if (permissionLabel) permissionLabel.innerText = settingAccountLabelI18n('account_permission', 'Permission');

    // 順手同步表頭與按鈕，避免 Account tab 有些區塊仍維持英文。
    var table = document.getElementById('account_user_table');
    if (table) {
        var ths = table.querySelectorAll('thead th');
        if (ths[0]) ths[0].innerText = saT('account_no', 'No');
        if (ths[1]) ths[1].innerText = saT('account_user_name', 'User Name');
        if (ths[2]) ths[2].innerText = saT('account_date', 'Date');
    }

    var newBtn = document.getElementById('account_new_btn');
    var editBtn = document.getElementById('account_edit_btn');
    var deleteBtn = document.getElementById('account_delete_btn');
    var saveBtn = document.getElementById('settingAccountSaveBtn') || document.querySelector('#settingAccountModal .modal-footer .button-modal:not(.closebtn)');
    var closeBtn = document.getElementById('settingAccountCloseBtn') || document.querySelector('#settingAccountModal .modal-footer .closebtn');

    if (newBtn) newBtn.value = saT('account_new', 'New');
    if (editBtn) editBtn.value = saT('account_edit', 'Edit');
    if (deleteBtn) deleteBtn.value = saT('account_delete', 'Delete');
    if (saveBtn) saveBtn.innerText = saT('account_save', 'Save');
    if (closeBtn) closeBtn.innerText = saT('account_close', 'Close');
}


// =====================================================
// Setting Account / table user
// Rule:
// 1) User List hides Law column.
// 2) Username: A-Z / a-z / 0-9, 6~8 chars.
// 3) Password: exactly 4 digits, 0~9.
// =====================================================
var selectedAccountUser = null;
var selectedAccountPassword = '';
var selectedAccountLaw = '1';
var settingAccountRecordMap = {};
var settingAccountMode = 'new';
var settingAccountLoaded = false;
var settingAccountAllRecords = [];
var settingAccountBusy = false;

function settingAccountApi(path) {
    return '?url=Settings/' + path;
}

function configureSettingAccountAlertifyNoTitle() {
    if (!window.alertify) return;

    try {
        // AlertifyJS v1：清掉預設標題 AlertifyJS
        if (alertify.defaults && alertify.defaults.glossary) {
            alertify.defaults.glossary.title = '';
            alertify.defaults.glossary.ok = saT('account_alertify_ok', 'OK');
            alertify.defaults.glossary.cancel = saT('account_alertify_cancel', 'CANCEL');
        }
    } catch (e) {}
}

/**
 * 只清掉 AlertifyJS header 內的 title 文字。
 * 注意：不要 display:none 整個 header，否則不同版本 AlertifyJS 可能會破版。
 */
function cleanupSettingAccountAlertifyTitle() {
    window.setTimeout(function() {
        var headers = document.querySelectorAll('.alertify .ajs-header, .alertifyjs .ajs-header, .ajs-dialog .ajs-header');

        Array.prototype.forEach.call(headers, function(header) {
            Array.prototype.forEach.call(header.childNodes, function(node) {
                // 只移除直接文字節點，例如：AlertifyJS
                if (node.nodeType === 3) {
                    node.nodeValue = '';
                }
            });
        });
    }, 0);
}

function settingAccountAlert(message) {
    configureSettingAccountAlertifyNoTitle();
    if (window.alertify && typeof IdasNotify.alert === 'function') {
        IdasNotify.alert(message);
        cleanupSettingAccountAlertifyTitle();
    } else {
        IdasNotify.alert(String(message).replace(/<[^>]*>/g, ''));
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
        cleanupSettingAccountAlertifyTitle();
    } else {
        // 專案應使用 AlertifyJS confirm；若未載入，不執行危險動作。
        settingAccountAlert(saT('account_alertify_not_loaded', 'AlertifyJS confirm is not loaded.'));
        if (typeof onCancel === 'function') onCancel();
    }
}

function settingAccountImportModeDialog(onAppend, onOverwrite, onCancel) {
    var oldOverlay = document.getElementById('settingAccountImportModeOverlay');
    if (oldOverlay && oldOverlay.parentNode) {
        oldOverlay.parentNode.removeChild(oldOverlay);
    }

    var overlay = document.createElement('div');
    overlay.id = 'settingAccountImportModeOverlay';
    overlay.style.position = 'fixed';
    overlay.style.left = '0';
    overlay.style.top = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.background = 'rgba(0,0,0,0.35)';
    overlay.style.zIndex = '9999';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';

    var box = document.createElement('div');
    box.style.width = '420px';
    box.style.maxWidth = '90%';
    box.style.background = '#fff';
    box.style.boxShadow = '0 12px 30px rgba(0,0,0,0.35)';
    box.style.borderRadius = '2px';
    box.style.overflow = 'hidden';
    box.style.fontSize = '16px';

    var body = document.createElement('div');
    body.style.padding = '28px 36px';
    body.style.lineHeight = '1.8';
    body.style.color = '#111';
    body.innerHTML = settingAccountEscape(saT('account_import_mode_confirm', 'Select import mode:'));

    var footer = document.createElement('div');
    footer.style.display = 'flex';
    footer.style.justifyContent = 'flex-end';
    footer.style.gap = '18px';
    footer.style.borderTop = '1px solid #eee';
    footer.style.padding = '14px 22px';

    function makeButton(text, handler, primary) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.innerText = text;
        btn.style.minWidth = '72px';
        btn.style.height = '34px';
        btn.style.border = 'none';
        btn.style.background = 'transparent';
        btn.style.cursor = 'pointer';
        btn.style.fontWeight = '700';
        btn.style.color = primary ? '#1e88e5' : '#222';
        btn.onclick = function() {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
            if (typeof handler === 'function') handler();
        };
        return btn;
    }

    footer.appendChild(makeButton(saT('account_import_append', 'Append'), onAppend, true));
    footer.appendChild(makeButton(saT('account_import_overwrite', 'Overwrite'), onOverwrite, true));
    footer.appendChild(makeButton(saT('account_alertify_cancel', 'CANCEL'), onCancel, false));

    box.appendChild(body);
    box.appendChild(footer);
    overlay.appendChild(box);
    document.body.appendChild(overlay);
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

function settingAccountNormalizeLaw(value) {
    value = String(value === undefined || value === null || value === '' ? '1' : value).replace(/[^0-9]/g, '');
    return value === '3' ? '3' : '1';
}

function settingAccountPickLaw(row) {
    row = row || {};
    var candidates = [row.law, row.LAW, row.permission, row.Permission, row.PERMISSION];
    for (var i = 0; i < candidates.length; i++) {
        if (candidates[i] !== undefined && candidates[i] !== null && String(candidates[i]) !== '') {
            return settingAccountNormalizeLaw(candidates[i]);
        }
    }
    return '1';
}

function settingAccountFindLawByUsername(username) {
    username = String(username || '').trim();
    if (username === '') return '1';

    var row = settingAccountRecordMap[username.toLowerCase()];
    if (row) return settingAccountPickLaw(row);

    var selectedRow = document.querySelector('#AccountDisplay .account-user-row.selected, #AccountDisplay .account-user-row.active');
    if (selectedRow && (selectedRow.getAttribute('data-name') || '').toLowerCase() === username.toLowerCase()) {
        return settingAccountNormalizeLaw(selectedRow.getAttribute('data-law') || '1');
    }

    var rows = document.querySelectorAll('#AccountDisplay .account-user-row');
    for (var i = 0; i < rows.length; i++) {
        if ((rows[i].getAttribute('data-name') || '').toLowerCase() === username.toLowerCase()) {
            return settingAccountNormalizeLaw(rows[i].getAttribute('data-law') || '1');
        }
    }

    return '1';
}

function settingAccountSetLawInput(value) {
    var lawInput = document.getElementById('account_law');
    if (lawInput) lawInput.value = settingAccountNormalizeLaw(value);
}

function settingAccountCurrentLawForSave() {
    var lawInput = document.getElementById('account_law');
    return settingAccountNormalizeLaw(lawInput ? lawInput.value : '1');
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
                saT('account_api_not_json', 'API response is not JSON.') + '<br>' +
                saT('account_http_status', 'HTTP Status') + ': ' + result.status + '<br>' +
                saT('account_raw_response', 'Raw response') + ':<br><pre style="text-align:left;white-space:pre-wrap;max-height:260px;overflow:auto;">' + raw + '</pre>'
            );
        }
    });
}

function loadSettingAccountUsers() {
    var tbody = document.getElementById('accountUserTbody');
    if (tbody) tbody.innerHTML = '<tr><td colspan="2">' + saT('account_loading', 'Loading...') + '</td></tr>';

    settingAccountPost('account_user_list', {}).then(function(json) {
        settingAccountLoaded = true;

        if (!json.success) {
            if (tbody) tbody.innerHTML = '<tr><td colspan="2">' + saT('account_load_failed', 'Load failed') + '</td></tr>';
            settingAccountAlert(json.res_msg || saT('account_load_failed_full', 'Load account failed.'));
            return;
        }

        settingAccountAllRecords = json.records || [];
        renderSettingAccountUsers(settingAccountAllRecords);
    }).catch(function(err) {
        if (tbody) tbody.innerHTML = '<tr><td colspan="2">' + saT('account_load_failed', 'Load failed') + '</td></tr>';
        settingAccountAlert(err.message || saT('account_load_failed_full', 'Load account failed.'));
    });
}


function settingAccountIsProtectedUser(username) {
    var name = String(username || '').trim().toLowerCase();
    return name === 'guest' || name === 'admin' || name === 'service';
}

function settingAccountProtectedBadge(username) {
    var name = String(username || '').trim().toLowerCase();

    // Protected status is still enforced for guest/admin/service,
    // but only admin shows a visible label in the user list.
    if (name === 'admin') {
        return ' <span class="account-protected-badge">' +
            settingAccountEscape(saT('account_admin_account', 'Admin')) +
            '</span>';
    }

    return '';
}

function updateSettingAccountSummary(count) {
    var countEl = document.getElementById('accountUserCountText');
    var syncEl = document.getElementById('accountLastSyncText');
    var statusEl = document.getElementById('accountSyncStatusText');

    if (countEl) {
        countEl.innerText = saT('account_total_accounts', 'Accounts') + ': ' + (count || 0);
    }

    var lastSync = '';
    var syncStatus = '';
    var syncRows = '';
    try {
        lastSync = localStorage.getItem('accountLastSyncToController') || '';
        syncStatus = localStorage.getItem('accountLastSyncStatus') || '';
        syncRows = localStorage.getItem('accountLastSyncRows') || '';
    } catch (e) {}

    if (syncEl) {
        syncEl.innerText = saT('account_last_sync_time', 'Last sync') + ': ' + (lastSync || saT('account_never_synced', '-'));
    }

    if (statusEl) {
        var statusText = '-';
        if (syncStatus === 'success') {
            statusText = saT('account_sync_success', 'Success');
            if (syncRows !== '') {
                statusText += ' / ' + saT('account_sync_rows', 'Rows') + ': ' + syncRows;
            }
        } else if (syncStatus === 'failed') {
            statusText = saT('account_sync_failed', 'Failed');
        }
        statusEl.innerText = saT('account_sync_status', 'Sync status') + ': ' + statusText;
    }
}

function setSettingAccountBusy(isBusy) {
    settingAccountBusy = !!isBusy;
    var ids = [
        'account_new_btn',
        'account_edit_btn',
        'account_delete_btn',
        'account_import_btn',
        'account_export_btn',
        'account_template_btn',
        'account_upload_controller_btn'
    ];

    ids.forEach(function(id) {
        var btn = document.getElementById(id);
        if (!btn) return;

        if (isBusy) {
            if (!btn.getAttribute('data-original-value')) {
                btn.setAttribute('data-original-value', btn.value || btn.innerText || '');
            }
            btn.disabled = true;
            btn.classList.add('account-action-busy');
        } else {
            var original = btn.getAttribute('data-original-value');
            if (original !== null) {
                if ('value' in btn) btn.value = original;
                else btn.innerText = original;
                btn.removeAttribute('data-original-value');
            }
            btn.disabled = false;
            btn.classList.remove('account-action-busy');
        }
    });
}

function downloadSettingAccountTemplate() {
    var csv = 'No,User Name,Password,Law\n1,user001,1234,1\n';
    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = 'account_user_template.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(function(){ URL.revokeObjectURL(url); }, 0);
}

function renderSettingAccountUsers(records) {
    var tbody = document.getElementById('accountUserTbody');
    if (!tbody) return;

    selectedAccountUser = null;
    selectedAccountPassword = '';
    selectedAccountLaw = '1';
    settingAccountRecordMap = {};

    // service 帳號需要顯示在列表，但儲存/刪除會被保護。
    records = (records || []);

    var searchInput = document.getElementById('accountSearchInput');
    var keyword = searchInput ? String(searchInput.value || '').trim().toLowerCase() : '';
    if (keyword !== '') {
        records = records.filter(function(row) {
            return String(row.name || '').toLowerCase().indexOf(keyword) !== -1;
        });
    }

    if (!records.length) {
        tbody.innerHTML = '<tr><td colspan="2">' + saT('account_no_data', 'No Data') + '</td></tr>';
        updateSettingAccountSummary(0);
        return;
    }

    tbody.innerHTML = records.map(function(row, index) {
        var name = settingAccountEscape(row.name || '');
        var passwd = settingAccountEscape(settingAccountRememberRecord(row));
        var law = settingAccountEscape(settingAccountPickLaw(row));

        return '<tr class="account-user-row" data-name="' + name + '" data-passwd="' + passwd + '" data-law="' + law + '" onclick="selectSettingAccountUser(this)">' +
            '<td>' + (index + 1) + '</td>' +
            '<td>' + name + settingAccountProtectedBadge(row.name || '') + '</td>' +
            '</tr>';
    }).join('');
    updateSettingAccountSummary(records.length);
}

function selectSettingAccountUser(row) {
    // 與 Job 頁面一致：點選列時使用 selected class
    document.querySelectorAll('.account-user-row').forEach(function(tr) {
        tr.classList.remove('selected');
    });

    row.classList.add('selected');
    selectedAccountUser = row.getAttribute('data-name') || '';
    selectedAccountPassword = row.getAttribute('data-passwd') || settingAccountFindPasswordByUsername(selectedAccountUser);
    selectedAccountLaw = settingAccountNormalizeLaw(row.getAttribute('data-law') || settingAccountFindLawByUsername(selectedAccountUser));
}


function exportSettingAccounts() {
    // Export account/password CSV. Browser will download directly.
    window.location.href = settingAccountApi('account_user_export') + '&t=' + Date.now();
}

function uploadSettingAccountsToController() {
    if (settingAccountBusy) return;
    setSettingAccountBusy(true);

    settingAccountPost('account_user_upload_controller', {}).then(function(json) {
        settingAccountAlert(json.res_msg || (json.success ? saT('account_upload_success', 'Upload success.') : saT('account_upload_failed', 'Upload failed.')));

        try {
            var now = new Date();
            var ts = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0') + ' ' + String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');
            localStorage.setItem('accountLastSyncToController', ts);
            localStorage.setItem('accountLastSyncStatus', json.success ? 'success' : 'failed');
            localStorage.setItem('accountLastSyncRows', json.success ? String(json.rows || json.count || '') : '');
        } catch (e) {}

        if (json.success) {
            loadSettingAccountUsers();
        } else {
            updateSettingAccountSummary((document.querySelectorAll('#AccountDisplay .account-user-row') || []).length);
        }
    }).catch(function(err) {
        try {
            var now = new Date();
            var ts = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0') + '-' + String(now.getDate()).padStart(2, '0') + ' ' + String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0') + ':' + String(now.getSeconds()).padStart(2, '0');
            localStorage.setItem('accountLastSyncToController', ts);
            localStorage.setItem('accountLastSyncStatus', 'failed');
            localStorage.setItem('accountLastSyncRows', '');
        } catch (e) {}
        updateSettingAccountSummary((document.querySelectorAll('#AccountDisplay .account-user-row') || []).length);
        settingAccountAlert(err.message || saT('account_upload_failed', 'Upload failed.'));
    }).finally(function() {
        setSettingAccountBusy(false);
    });
}

function importSettingAccounts(importMode) {
    var fileInput = document.getElementById('account_import_file');
    if (!fileInput) return;

    fileInput.value = '';
    fileInput.setAttribute('data-import-mode', importMode || 'append');
    fileInput.click();
}

function importSettingAccountFile(input) {
    if (!input || !input.files || !input.files.length) return;

    var file = input.files[0];
    var fileName = file && file.name ? file.name : '';

    if (!/\.csv$/i.test(fileName)) {
        settingAccountAlert(saT('account_csv_only', 'Only CSV file is allowed.'));
        input.value = '';
        return;
    }

    var formData = new FormData();
    formData.append('account_file', file);
    formData.append('import_mode', input.getAttribute('data-import-mode') || 'append');

    if (settingAccountBusy) return;
    setSettingAccountBusy(true);

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
            throw new Error(saT('account_import_response_not_json', 'Import response is not JSON.'));
        }

        var msg = json.res_msg || (json.success ? saT('account_import_success', 'Import success.') : saT('account_import_failed', 'Import failed.'));
        if (json.errors && json.errors.length) {
            msg += '<br><br>' + json.errors.map(settingAccountEscape).join('<br>');
        }

        settingAccountAlert(msg);

        if (json.success) {
            loadSettingAccountUsers();
        }
    }).catch(function(err) {
        settingAccountAlert(err.message || saT('account_import_failed', 'Import failed.'));
    }).finally(function() {
        setSettingAccountBusy(false);
        input.value = '';
    });
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

    var mode = input.getAttribute('data-mask-mode') || '';

    // Default rule for New Account: password stays masked (****).
    // When the eye is clicked and mode becomes visible, allow clear text editing.
    if (mode === 'visible') {
        var value = String(input.value || '').replace(/[^0-9]/g, '').slice(0, 4);
        input.type = 'text';
        input.value = value;
        input.setAttribute('data-real-password', value);
        input.setAttribute('data-mask-mode', 'visible');
        settingAccountSetEyeVisible(settingAccountEyeButtonForInput(input.id), true);
    }
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
        p1.addEventListener('keydown', settingAccountConfirmPasswordKeydown);
        p1.addEventListener('input', function() { settingAccountPasswordInputChange(p1); });
        p1.addEventListener('paste', settingAccountConfirmPasswordPaste);
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
    selectedAccountLaw = settingAccountFindLawByUsername(username);
    settingAccountSetLawInput(selectedAccountLaw);

    settingAccountGetEditPassword(username).then(function(realPassword) {
        selectedAccountPassword = realPassword;

        // 先開 Modal，因為 openSettingAccountModal() 內會 reset password mask。
        // 開完後再補 ****，避免被 resetSettingAccountPasswordMask() 蓋掉。
        openSettingAccountModal();

        settingAccountSetMaskedPassword(realPassword);

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
    if (settingAccountBusy) return;
    settingAccountMode = mode;

    if (mode === 'new') {
        document.getElementById('settingAccountModalTitle').innerText = saT('account_new_title', 'New Account');
        document.getElementById('account_old_username').value = '';
        document.getElementById('account_username').value = '';
        document.getElementById('account_username').removeAttribute('readonly');
        document.getElementById('account_username').classList.remove('account-username-readonly');

        selectedAccountPassword = '';
        selectedAccountLaw = '1';
        settingAccountSetLawInput('1');

        openSettingAccountModal();
        settingAccountPrepareNewPasswordInputs();
        return;
    }

    if (mode === 'export') {
        exportSettingAccounts();
        return;
    }

    if (mode === 'template') {
        downloadSettingAccountTemplate();
        return;
    }

    if (mode === 'import') {
        settingAccountImportModeDialog(
            function() {
                importSettingAccounts('append');
            },
            function() {
                settingAccountConfirm(
                    saT('account_import_overwrite_confirm', 'Overwrite will delete all accounts except service, guest, and admin. Continue?'),
                    function() {
                        importSettingAccounts('overwrite');
                    }
                );
            },
            function() {
                // Cancel means cancel only. Do not import anything.
            }
        );
        return;
    }

    if (mode === 'upload_controller') {
        settingAccountConfirm(
            saT('account_upload_confirm', 'This will sync the iDAS user list to the controller and overwrite the controller user table. Continue?'),
            function() {
                uploadSettingAccountsToController();
            }
        );
        return;
    }

    if (!selectedAccountUser) {
        settingAccountAlert(saT('account_select_one', 'Please select one account.'));
        return;
    }

    if (mode === 'delete' && settingAccountIsProtectedUser(selectedAccountUser)) {
        settingAccountAlert(saT('account_protected_action', 'This account is protected and cannot be deleted.'));
        return;
    }

    if (mode === 'edit') {
        settingAccountOpenEditWithMaskedPassword(selectedAccountUser);
        return;
    }

    if (mode === 'delete') {
        settingAccountConfirm(
            saT('account_delete_confirm', 'Are you sure you want to delete this account?<br><br>Account: {account}')
                .replace('{account}', settingAccountEscape(selectedAccountUser)),
            function() {
                settingAccountPost('account_user_delete', {
                    username: selectedAccountUser
                }).then(function(json) {
                    settingAccountAlert(json.res_msg || (json.success ? saT('account_delete_success', 'Delete success.') : saT('account_delete_failed', 'Delete failed.')));
                    if (json.success) loadSettingAccountUsers();
                }).catch(function(err) {
                    settingAccountAlert(err.message || saT('account_delete_failed', 'Delete failed.'));
                });
            }
        );
        return;
    }
}

function openSettingAccountModal() {
    applySettingAccountColonFix();
    resetSettingAccountPasswordMask();
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

    var mode = input.getAttribute('data-mask-mode') || '';
    var real = input.getAttribute('data-real-password') || input.value || '';

    if (mode === 'masked' || input.value === '****') {
        input.type = 'text';
        input.value = real;
        input.setAttribute('data-mask-mode', 'visible');

        if (btn) {
            btn.classList.add('is-visible');
            btn.setAttribute('title', saT('account_hide_password', 'Hide password'));
            btn.setAttribute('aria-label', saT('account_hide_password', 'Hide password'));
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
            btn.setAttribute('title', saT('account_show_password', 'Show password'));
            btn.setAttribute('aria-label', saT('account_show_password', 'Show password'));
        }
        return;
    }

    // New account / normal password field behavior.
    if (input.type === 'password') {
        input.type = 'text';
        if (btn) btn.classList.add('is-visible');
    } else {
        input.type = 'password';
        if (btn) btn.classList.remove('is-visible');
    }
}

function resetSettingAccountPasswordMask() {
    var password = document.getElementById('account_password');
    var confirmPassword = document.getElementById('account_confirm_password');

    // Edit mode 的密碼遮蔽由 settingAccountSetMaskedPassword() 控制。
    // 不在這裡改 type / value，避免 Edit 開窗後又被重置成空白。
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
        btn.setAttribute('title', saT('account_show_password', 'Show password'));
        btn.setAttribute('aria-label', saT('account_show_password', 'Show password'));
    });
}

function saveSettingAccount() {
    var oldUsername = document.getElementById('account_old_username').value.trim();
    var username = document.getElementById('account_username').value.trim();
    var password = settingAccountResolvePasswordForSave('account_password');
    var confirmPassword = settingAccountResolvePasswordForSave('account_confirm_password');
    var law = settingAccountCurrentLawForSave();

    if (!username) {
        settingAccountAlert(saT('account_username_empty', 'Username cannot be empty.'));
        return;
    }

    // New 或 Edit 改名時，Username 必須符合 6~8 字元規則。
    // Edit 未改名時允許既有 guest/admin/user1 等舊帳號繼續修改密碼。
    var oldUsernameLower = String(oldUsername || '').toLowerCase();
    var usernameLower = String(username || '').toLowerCase();

    if (settingAccountMode === 'new' && usernameLower === 'service') {
        settingAccountAlert(saT('account_protected_create_action', 'This account is protected and cannot be created.'));
        return;
    }

    if (settingAccountMode === 'edit' && oldUsernameLower === 'service') {
        settingAccountAlert(saT('account_protected_save_action', 'This account is protected and cannot be saved.'));
        return;
    }

    if (settingAccountMode === 'edit' && (oldUsernameLower === 'admin' || oldUsernameLower === 'guest') && usernameLower !== oldUsernameLower) {
        settingAccountAlert(saT(oldUsernameLower === 'guest' ? 'account_guest_rename_blocked' : 'account_admin_rename_blocked', oldUsernameLower + ' account name cannot be changed.'));
        return;
    }

    if (settingAccountMode === 'new' || username !== oldUsername) {
        if (!settingAccountValidUsername(username)) {
            settingAccountAlert(saT('account_username_rule', 'Username must be 6 to 8 characters and only allows A-Z, a-z, 0-9.'));
            return;
        }
    } else if (!settingAccountValidText(username)) {
        settingAccountAlert(saT('account_username_chars', 'Username only allows A-Z, a-z, 0-9.'));
        return;
    }

    if (settingAccountMode === 'new' && !password) {
        settingAccountAlert(saT('account_password_empty', 'Password cannot be empty.'));
        return;
    }

    if (password !== '') {
        if (!settingAccountValidPassword(password)) {
            settingAccountAlert(saT('account_password_rule', 'Password must be exactly 4 digits, 0-9.'));
            return;
        }

        if (password !== confirmPassword) {
            settingAccountAlert(saT('account_confirm_diff', 'Confirm password is different.'));
            return;
        }
    }

    if (!['1', '3'].includes(String(law))) {
        settingAccountAlert(saT('account_permission_rule', 'Permission must be 1: guest or 3: operator.'));
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
        settingAccountAlert(json.res_msg || (json.success ? saT('account_save_success', 'Save success.') : saT('account_save_failed', 'Save failed.')));
        if (json.success) {
            closeSettingAccountModal();
            loadSettingAccountUsers();
        }
    }).catch(function(err) {
        settingAccountAlert(err.message || saT('account_save_failed', 'Save failed.'));
    });
}


document.addEventListener('DOMContentLoaded', function() {
    configureSettingAccountAlertifyNoTitle();
    applySettingAccountColonFix();

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

/* =====================================================
   Account QRCode download
   Format: {"usr":"abcd123","pwd":"0734"}
   Offline pure JavaScript QRCode generator, Version 2-L.
   ===================================================== */
function settingAccountQrText(key, fallback) {
    if (typeof saT === 'function') {
        return saT(key, fallback);
    }

    if (typeof settingAccountI18nText === 'function') {
        var txt = settingAccountI18nText(key);
        if (txt && txt !== key) return txt;

        if (key.indexOf('account_') === 0) {
            var shortKey = key.replace(/^account_/, '');
            txt = settingAccountI18nText(shortKey);
            if (txt && txt !== shortKey) return txt;
        }
    }

    return fallback || key;
}

function settingAccountDownloadTextFileNameSafe(value) {
    return String(value || 'account').replace(/[^A-Za-z0-9_\-]/g, '_');
}

function settingAccountTimestamp() {
    var d = new Date();
    var pad = function(n) { return String(n).padStart(2, '0'); };

    return d.getFullYear() +
        pad(d.getMonth() + 1) +
        pad(d.getDate()) + '_' +
        pad(d.getHours()) +
        pad(d.getMinutes()) +
        pad(d.getSeconds());
}

function settingAccountUtf8Bytes(str) {
    var bytes = [];
    str = String(str || '');

    for (var i = 0; i < str.length; i++) {
        var c = str.charCodeAt(i);

        if (c < 0x80) {
            bytes.push(c);
        } else if (c < 0x800) {
            bytes.push(0xC0 | (c >> 6));
            bytes.push(0x80 | (c & 0x3F));
        } else if (c >= 0xD800 && c <= 0xDBFF) {
            i++;
            var c2 = str.charCodeAt(i);
            var code = 0x10000 + (((c & 0x3FF) << 10) | (c2 & 0x3FF));
            bytes.push(0xF0 | (code >> 18));
            bytes.push(0x80 | ((code >> 12) & 0x3F));
            bytes.push(0x80 | ((code >> 6) & 0x3F));
            bytes.push(0x80 | (code & 0x3F));
        } else {
            bytes.push(0xE0 | (c >> 12));
            bytes.push(0x80 | ((c >> 6) & 0x3F));
            bytes.push(0x80 | (c & 0x3F));
        }
    }

    return bytes;
}

function settingAccountQrBitLength(value) {
    var len = 0;
    while (value !== 0) {
        len++;
        value >>>= 1;
    }
    return len;
}

function settingAccountQrCreateData(payload) {
    var dataCodewords = 34; // QR Version 2-L
    var bytes = settingAccountUtf8Bytes(payload);

    if (bytes.length > 32) {
        throw new Error(settingAccountQrText('account_qrcode_payload_too_long', 'QR payload is too long.'));
    }

    var bits = [];

    function put(num, length) {
        for (var i = length - 1; i >= 0; i--) {
            bits.push(((num >>> i) & 1) === 1);
        }
    }

    // Byte mode
    put(0x4, 4);
    put(bytes.length, 8);

    for (var b = 0; b < bytes.length; b++) {
        put(bytes[b], 8);
    }

    // Terminator
    var capacityBits = dataCodewords * 8;
    var remaining = capacityBits - bits.length;
    put(0, Math.min(4, remaining));

    while (bits.length % 8 !== 0) {
        bits.push(false);
    }

    var data = [];
    for (var i = 0; i < bits.length; i += 8) {
        var val = 0;
        for (var j = 0; j < 8; j++) {
            val = (val << 1) | (bits[i + j] ? 1 : 0);
        }
        data.push(val);
    }

    var padBytes = [0xEC, 0x11];
    var padIndex = 0;

    while (data.length < dataCodewords) {
        data.push(padBytes[padIndex % 2]);
        padIndex++;
    }

    return data;
}

function settingAccountQrRsEcc(data) {
    var ecCount = 10; // QR Version 2-L, one block
    var exp = new Array(512);
    var log = new Array(256);

    var x = 1;
    for (var i = 0; i < 255; i++) {
        exp[i] = x;
        log[x] = i;
        x <<= 1;
        if (x & 0x100) x ^= 0x11D;
    }

    for (var i2 = 255; i2 < 512; i2++) {
        exp[i2] = exp[i2 - 255];
    }

    function mul(a, b) {
        if (a === 0 || b === 0) return 0;
        return exp[log[a] + log[b]];
    }

    var gen = [1];
    for (var g = 0; g < ecCount; g++) {
        var next = new Array(gen.length + 1).fill(0);

        for (var n = 0; n < gen.length; n++) {
            next[n] ^= gen[n];
            next[n + 1] ^= mul(gen[n], exp[g]);
        }

        gen = next;
    }

    var msg = data.slice();
    for (var z = 0; z < ecCount; z++) msg.push(0);

    for (var m = 0; m < data.length; m++) {
        var coef = msg[m];
        if (coef !== 0) {
            for (var k = 0; k < gen.length; k++) {
                msg[m + k] ^= mul(gen[k], coef);
            }
        }
    }

    return msg.slice(data.length);
}

function settingAccountQrMakeMatrix(payload) {
    var version = 2;
    var size = 25;
    var data = settingAccountQrCreateData(payload);
    var ecc = settingAccountQrRsEcc(data);
    var codewords = data.concat(ecc);

    function makeBase() {
        var modules = [];
        var reserved = [];

        for (var r = 0; r < size; r++) {
            modules[r] = [];
            reserved[r] = [];

            for (var c = 0; c < size; c++) {
                modules[r][c] = false;
                reserved[r][c] = false;
            }
        }

        function setModule(r, c, dark, res) {
            if (r < 0 || c < 0 || r >= size || c >= size) return;
            modules[r][c] = !!dark;
            if (res) reserved[r][c] = true;
        }

        function finder(row, col) {
            for (var dr = -1; dr <= 7; dr++) {
                for (var dc = -1; dc <= 7; dc++) {
                    var rr = row + dr;
                    var cc = col + dc;

                    if (rr < 0 || cc < 0 || rr >= size || cc >= size) continue;

                    var inFinder = dr >= 0 && dr <= 6 && dc >= 0 && dc <= 6;
                    var dark = false;

                    if (inFinder) {
                        dark = dr === 0 || dr === 6 || dc === 0 || dc === 6 ||
                            (dr >= 2 && dr <= 4 && dc >= 2 && dc <= 4);
                    }

                    setModule(rr, cc, dark, true);
                }
            }
        }

        finder(0, 0);
        finder(size - 7, 0);
        finder(0, size - 7);

        // Alignment pattern for version 2 at (18,18)
        var ar = 18;
        var ac = 18;
        for (var adr = -2; adr <= 2; adr++) {
            for (var adc = -2; adc <= 2; adc++) {
                var dist = Math.max(Math.abs(adr), Math.abs(adc));
                setModule(ar + adr, ac + adc, dist !== 1, true);
            }
        }

        // Timing patterns
        for (var i = 8; i < size - 8; i++) {
            var darkTiming = i % 2 === 0;
            setModule(6, i, darkTiming, true);
            setModule(i, 6, darkTiming, true);
        }

        // Dark module
        setModule(4 * version + 9, 8, true, true);

        // Reserve format info areas
        for (var f = 0; f < 9; f++) {
            if (f !== 6) {
                reserved[8][f] = true;
                reserved[f][8] = true;
            }
        }

        for (var f2 = 0; f2 < 8; f2++) {
            reserved[8][size - 1 - f2] = true;
            reserved[size - 1 - f2][8] = true;
        }

        return {
            modules: modules,
            reserved: reserved
        };
    }

    function putData(base) {
        var modules = base.modules;
        var reserved = base.reserved;
        var bits = [];

        for (var i = 0; i < codewords.length; i++) {
            for (var b = 7; b >= 0; b--) {
                bits.push(((codewords[i] >>> b) & 1) === 1);
            }
        }

        var bitIndex = 0;
        var upward = true;

        for (var col = size - 1; col > 0; col -= 2) {
            if (col === 6) col--;

            for (var rowOffset = 0; rowOffset < size; rowOffset++) {
                var row = upward ? size - 1 - rowOffset : rowOffset;

                for (var c = 0; c < 2; c++) {
                    var cc = col - c;

                    if (!reserved[row][cc]) {
                        modules[row][cc] = bitIndex < bits.length ? bits[bitIndex] : false;
                        bitIndex++;
                    }
                }
            }

            upward = !upward;
        }
    }

    function maskBit(mask, r, c) {
        switch (mask) {
            case 0: return (r + c) % 2 === 0;
            case 1: return r % 2 === 0;
            case 2: return c % 3 === 0;
            case 3: return (r + c) % 3 === 0;
            case 4: return (Math.floor(r / 2) + Math.floor(c / 3)) % 2 === 0;
            case 5: return ((r * c) % 2 + (r * c) % 3) === 0;
            case 6: return (((r * c) % 2 + (r * c) % 3) % 2) === 0;
            case 7: return (((r + c) % 2 + (r * c) % 3) % 2) === 0;
            default: return false;
        }
    }

    function applyMask(base, mask) {
        var modules = base.modules;
        var reserved = base.reserved;

        for (var r = 0; r < size; r++) {
            for (var c = 0; c < size; c++) {
                if (!reserved[r][c] && maskBit(mask, r, c)) {
                    modules[r][c] = !modules[r][c];
                }
            }
        }
    }

    function bchFormat(data) {
        var value = data << 10;
        var generator = 0x537;

        while (settingAccountQrBitLength(value) - settingAccountQrBitLength(generator) >= 0) {
            value ^= generator << (settingAccountQrBitLength(value) - settingAccountQrBitLength(generator));
        }

        return ((data << 10) | value) ^ 0x5412;
    }

    function putFormat(base, mask) {
        var modules = base.modules;
        var dataBits = (1 << 3) | mask; // Error correction L = 01
        var bits = bchFormat(dataBits);

        for (var i = 0; i < 15; i++) {
            var dark = ((bits >> i) & 1) === 1;

            if (i < 6) modules[i][8] = dark;
            else if (i < 8) modules[i + 1][8] = dark;
            else modules[size - 15 + i][8] = dark;

            if (i < 8) modules[8][size - i - 1] = dark;
            else if (i < 9) modules[8][15 - i] = dark;
            else modules[8][15 - i - 1] = dark;
        }

        modules[size - 8][8] = true;
    }

    function penalty(modules) {
        var p = 0;

        // N1
        for (var r = 0; r < size; r++) {
            var runColor = modules[r][0];
            var runLen = 1;

            for (var c = 1; c < size; c++) {
                if (modules[r][c] === runColor) {
                    runLen++;
                } else {
                    if (runLen >= 5) p += 3 + (runLen - 5);
                    runColor = modules[r][c];
                    runLen = 1;
                }
            }
            if (runLen >= 5) p += 3 + (runLen - 5);
        }

        for (var c2 = 0; c2 < size; c2++) {
            var runColor2 = modules[0][c2];
            var runLen2 = 1;

            for (var r2 = 1; r2 < size; r2++) {
                if (modules[r2][c2] === runColor2) {
                    runLen2++;
                } else {
                    if (runLen2 >= 5) p += 3 + (runLen2 - 5);
                    runColor2 = modules[r2][c2];
                    runLen2 = 1;
                }
            }
            if (runLen2 >= 5) p += 3 + (runLen2 - 5);
        }

        // N2
        for (var r3 = 0; r3 < size - 1; r3++) {
            for (var c3 = 0; c3 < size - 1; c3++) {
                var count = 0;
                if (modules[r3][c3]) count++;
                if (modules[r3 + 1][c3]) count++;
                if (modules[r3][c3 + 1]) count++;
                if (modules[r3 + 1][c3 + 1]) count++;
                if (count === 0 || count === 4) p += 3;
            }
        }

        // N3
        var pattern = [true, false, true, true, true, false, true, false, false, false, false];

        function hasPatternLine(line, index) {
            for (var k = 0; k < pattern.length; k++) {
                if (line[index + k] !== pattern[k]) return false;
            }
            return true;
        }

        for (var r4 = 0; r4 < size; r4++) {
            for (var c4 = 0; c4 <= size - 11; c4++) {
                if (hasPatternLine(modules[r4], c4)) p += 40;
            }
        }

        for (var c5 = 0; c5 < size; c5++) {
            var line = [];
            for (var r5 = 0; r5 < size; r5++) line.push(modules[r5][c5]);

            for (var i5 = 0; i5 <= size - 11; i5++) {
                if (hasPatternLine(line, i5)) p += 40;
            }
        }

        // N4
        var darkCount = 0;
        for (var rr = 0; rr < size; rr++) {
            for (var cc = 0; cc < size; cc++) {
                if (modules[rr][cc]) darkCount++;
            }
        }

        var ratio = Math.abs((darkCount * 100 / (size * size)) - 50);
        p += Math.floor(ratio / 5) * 10;

        return p;
    }

    var best = null;
    var bestPenalty = Infinity;

    for (var mask = 0; mask < 8; mask++) {
        var base = makeBase();
        putData(base);
        applyMask(base, mask);
        putFormat(base, mask);

        var score = penalty(base.modules);
        if (score < bestPenalty) {
            bestPenalty = score;
            best = base.modules;
        }
    }

    return best;
}

function settingAccountQrCanvas(payload) {
    var modules = settingAccountQrMakeMatrix(payload);
    var size = modules.length;
    var quiet = 4;
    var scale = 8;
    var canvasSize = (size + quiet * 2) * scale;

    var canvas = document.createElement('canvas');
    canvas.width = canvasSize;
    canvas.height = canvasSize;

    var ctx = canvas.getContext('2d');
    ctx.fillStyle = '#FFFFFF';
    ctx.fillRect(0, 0, canvasSize, canvasSize);

    ctx.fillStyle = '#000000';
    for (var r = 0; r < size; r++) {
        for (var c = 0; c < size; c++) {
            if (modules[r][c]) {
                ctx.fillRect((c + quiet) * scale, (r + quiet) * scale, scale, scale);
            }
        }
    }

    return canvas;
}

function downloadSettingAccountQrCode(username, password, event) {
    if (event && typeof event.stopPropagation === 'function') {
        event.stopPropagation();
    }

    try {
        username = String(username || '');
        password = String(password || '');

        var payload = JSON.stringify({
            usr: username,
            pwd: password
        });

        var canvas = settingAccountQrCanvas(payload);
        var link = document.createElement('a');

        link.download = 'account_qrcode_' +
            settingAccountDownloadTextFileNameSafe(username) +
            '_' + settingAccountTimestamp() + '.png';

        link.href = canvas.toDataURL('image/png');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (e) {
        console.error(e);
        settingAccountAlert(e.message || settingAccountQrText('account_qrcode_failed', 'Generate QR Code failed.'));
    }
}


/* =====================================================
   FORCE Account QRCode column patch V5
   This patch works even when the old 2-column Account table is still used.
   ===================================================== */
(function() {
    function accountQrPatchText(key, fallback) {
        if (typeof saT === 'function') {
            return saT(key, fallback);
        }

        if (typeof settingAccountI18nText === 'function') {
            var value = settingAccountI18nText(key);
            if (value && value !== key) return value;

            if (key.indexOf('account_') === 0) {
                var shortKey = key.replace(/^account_/, '');
                value = settingAccountI18nText(shortKey);
                if (value && value !== shortKey) return value;
            }
        }

        var lang = 'en-us';
        var cookie = document.cookie || '';
        if (cookie.indexOf('language=zh-tw') !== -1) lang = 'zh-tw';
        if (cookie.indexOf('language=zh-cn') !== -1) lang = 'zh-cn';

        var dict = {
            'en-us': {
                account_qrcode: 'QRCode',
                account_qrcode_download: 'Download',
                account_no_data: 'No Data'
            },
            'zh-tw': {
                account_qrcode: 'QRCode',
                account_qrcode_download: '下載',
                account_no_data: '無資料'
            },
            'zh-cn': {
                account_qrcode: 'QRCode',
                account_qrcode_download: '下载',
                account_no_data: '无资料'
            }
        };

        return (dict[lang] && dict[lang][key]) || fallback || key;
    }

    function accountQrPatchEscape(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function accountQrPatchAddStyle() {
        if (document.getElementById('account-qrcode-force-style')) return;

        var style = document.createElement('style');
        style.id = 'account-qrcode-force-style';
        style.textContent = ''
            + '#AccountDisplay #account_user_table th:first-child,'
            + '#AccountDisplay #account_user_table td:first-child{width:20%!important;}'
            + '#AccountDisplay #account_user_table th:nth-child(2),'
            + '#AccountDisplay #account_user_table td:nth-child(2){width:55%!important;}'
            + '#AccountDisplay #account_user_table th:nth-child(3),'
            + '#AccountDisplay #account_user_table td:nth-child(3){width:25%!important;}'
            + '#AccountDisplay .account-qrcode-cell{text-align:center!important;}'
            + '#AccountDisplay .account-qrcode-btn{min-width:92px;height:32px;padding:0 14px;border:1px solid #fff;border-radius:7px;background:#333;color:#fff;font-size:15px;font-weight:700;cursor:pointer;}'
            + '#AccountDisplay .account-qrcode-btn:hover{background:#ddd;color:#000;}';

        document.head.appendChild(style);
    }

    function accountQrPatchHeader() {
        accountQrPatchAddStyle();

        var table = document.getElementById('account_user_table');
        if (!table) return;

        var tr = table.querySelector('thead tr');
        if (!tr) return;

        var ths = tr.querySelectorAll('th');
        if (ths.length >= 3) {
            ths[2].textContent = accountQrPatchText('account_qrcode', 'QRCode');
            return;
        }

        var th = document.createElement('th');
        th.textContent = accountQrPatchText('account_qrcode', 'QRCode');
        tr.appendChild(th);
    }

    function accountQrPatchRender(records) {
        var tbody = document.getElementById('accountUserTbody');
        if (!tbody) return;

        accountQrPatchHeader();
        selectedAccountUser = null;
        selectedAccountPassword = '';

        // service 帳號需要顯示在列表，但儲存/刪除會被保護。
        records = (records || []);

        var searchInput = document.getElementById('accountSearchInput');
        var keyword = searchInput ? String(searchInput.value || '').trim().toLowerCase() : '';
        if (keyword !== '') {
            records = records.filter(function(row) {
                return String(row.name || '').toLowerCase().indexOf(keyword) !== -1;
            });
        }

        if (!records.length) {
            tbody.innerHTML = '<tr><td colspan="3">' + accountQrPatchText('account_no_data', 'No Data') + '</td></tr>';
            if (typeof updateSettingAccountSummary === 'function') updateSettingAccountSummary(0);
            return;
        }

        var btnText = accountQrPatchText('account_qrcode_download', 'Download');

        tbody.innerHTML = records.map(function(row, index) {
            var name = accountQrPatchEscape(row.name || '');
            var passwd = accountQrPatchEscape(settingAccountRememberRecord(row));
            var law = accountQrPatchEscape(typeof settingAccountPickLaw === 'function' ? settingAccountPickLaw(row) : (row.law || '1'));

            return '<tr class="account-user-row" data-name="' + name + '" data-passwd="' + passwd + '" data-law="' + law + '" onclick="selectSettingAccountUser(this)">'
                + '<td>' + (index + 1) + '</td>'
                + '<td>' + name + (typeof settingAccountProtectedBadge === 'function' ? settingAccountProtectedBadge(row.name || '') : '') + '</td>'
                + '<td class="account-qrcode-cell">'
                    + '<button type="button" class="account-qrcode-btn" data-name="' + name + '" data-passwd="' + passwd + '" onclick="downloadSettingAccountQrCodeFromButton(this,event)">'
                        + accountQrPatchEscape(btnText)
                    + '</button>'
                + '</td>'
                + '</tr>';
        }).join('');
        if (typeof updateSettingAccountSummary === 'function') updateSettingAccountSummary(records.length);
    }

    window.downloadSettingAccountQrCodeFromButton = function(btn, event) {
        if (event && typeof event.stopPropagation === 'function') event.stopPropagation();
        var username = btn.getAttribute('data-name') || '';
        var passwd = btn.getAttribute('data-passwd') || '';
        downloadSettingAccountQrCode(username, passwd, event);
    };

    function accountQrPatchInstall() {
        accountQrPatchHeader();

        if (typeof window.renderSettingAccountUsers === 'function' && !window.renderSettingAccountUsers.__qrForcePatched) {
            window.renderSettingAccountUsers = function(records) {
                accountQrPatchRender(records || []);
            };
            window.renderSettingAccountUsers.__qrForcePatched = true;
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        accountQrPatchInstall();
        setTimeout(accountQrPatchInstall, 100);
        setTimeout(accountQrPatchInstall, 500);
    });

    window.addEventListener('load', function() {
        accountQrPatchInstall();
        setTimeout(accountQrPatchInstall, 300);
    });

    // For old pages where Account tab is clicked after load.
    var oldOpen = window.SettingOpenButtonFinal;
    if (typeof oldOpen === 'function' && !oldOpen.__qrForceWrapped) {
        window.SettingOpenButtonFinal = function(mode) {
            var ret = oldOpen.apply(this, arguments);
            if (mode === 'Account') {
                setTimeout(accountQrPatchInstall, 0);
                setTimeout(function() {
                    if (typeof loadSettingAccountUsers === 'function') loadSettingAccountUsers();
                }, 80);
            }
            return ret;
        };
        window.SettingOpenButtonFinal.__qrForceWrapped = true;
        window.OpenButton = window.SettingOpenButtonFinal;
    }

    // Last safety timer
    setTimeout(accountQrPatchInstall, 1000);
})();




/* =====================================================
   Account static i18n force patch V6
   Fix modal labels/buttons and QRCode header/button by cookie language.
   ===================================================== */
(function(){
    function saCookieLangV6(){
        var cookies = document.cookie || '';
        var m = cookies.match(/(?:^|;\s*)(?:language|lang)=([^;]+)/i);
        var lang = m ? decodeURIComponent(m[1]).toLowerCase() : '';
        lang = lang.replace('_','-');
        if (lang === 'zh-hant' || lang === 'tw') return 'zh-tw';
        if (lang === 'zh-hans' || lang === 'cn') return 'zh-cn';
        if (lang === 'zh-tw' || lang === 'zh-cn' || lang === 'en-us' || lang === 'en') return (lang === 'en' ? 'en-us' : lang);
        return 'en-us';
    }

    function saTextV6(key, fallback){
        try {
            if (typeof saT === 'function') {
                var v1 = saT(key, '');
                if (v1 && v1 !== key) return v1;
            }
        } catch (e) {}

        try {
            if (typeof settingAccountI18nText === 'function') {
                var map = {
                    account_no: 'no',
                    account_user_name: 'user_name',
                    account_new_title: 'new_title',
                    account_edit_title: 'edit_title',
                    account_username: 'username_label',
                    account_password: 'password_label',
                    account_confirm_password: 'confirm_password_label',
                    account_import: 'import_btn',
                    account_export: 'export_btn',
                    account_upload: 'upload_btn',
                    account_qrcode: 'account_qrcode',
                    account_qrcode_download: 'account_qrcode_download',
                    account_show_password: 'show_password',
                    account_hide_password: 'hide_password',
                    save: 'save',
                    close: 'close',
                    New: 'new_btn',
                    Edit: 'edit_btn',
                    Delete: 'delete_btn'
                };
                var k = map[key] || key;
                var v2 = settingAccountI18nText(k);
                if (v2 && v2 !== k) return v2;
            }
        } catch (e) {}

        var lang = saCookieLangV6();
        var dict = {
            'en-us': {
                account_no:'No', account_user_name:'User Name', account_new_title:'New Account', account_edit_title:'Edit Account',
                account_username:'Username', account_password:'Password', account_confirm_password:'Confirm Password',
                account_import:'Import', account_export:'Export', account_upload:'Sync to Controller', account_csv_template:'CSV Template', account_qrcode:'QRCode', account_qrcode_download:'Download', account_search_placeholder:'Search account...', account_sync_status:'Sync status', account_last_sync_time:'Last sync', account_total_accounts:'Accounts',
                account_show_password:'Show password', account_hide_password:'Hide password', save:'Save', close:'Close', New:'New', Edit:'Edit', Delete:'Delete'
            },
            'zh-tw': {
                account_no:'編號', account_user_name:'使用者名稱', account_new_title:'新增帳號', account_edit_title:'編輯帳號',
                account_username:'使用者名稱', account_password:'密碼', account_confirm_password:'確認密碼',
                account_import:'匯入', account_export:'匯出', account_upload:'同步到控制器', account_csv_template:'CSV 範本', account_qrcode:'QRCode', account_qrcode_download:'下載', account_search_placeholder:'搜尋帳號...', account_sync_status:'同步狀態', account_last_sync_time:'最後同步時間', account_total_accounts:'帳號數',
                account_show_password:'顯示密碼', account_hide_password:'隱藏密碼', save:'儲存', close:'關閉', New:'新增', Edit:'編輯', Delete:'刪除'
            },
            'zh-cn': {
                account_no:'编号', account_user_name:'使用者名称', account_new_title:'新增账号', account_edit_title:'编辑账号',
                account_username:'使用者名称', account_password:'密码', account_confirm_password:'确认密码',
                account_import:'导入', account_export:'导出', account_upload:'同步到控制器', account_csv_template:'CSV 范本', account_qrcode:'QRCode', account_qrcode_download:'下载', account_search_placeholder:'搜寻账号...', account_sync_status:'同步状态', account_last_sync_time:'最后同步时间', account_total_accounts:'账号数',
                account_show_password:'显示密码', account_hide_password:'隐藏密码', save:'储存', close:'关闭', New:'新增', Edit:'编辑', Delete:'删除'
            }
        };
        return (dict[lang] && dict[lang][key]) || fallback || key;
    }

    function saColonV6(){
        return saCookieLangV6() === 'en-us' ? ' :' : '：';
    }

    function settingAccountApplyStaticI18nV6(){
        try {
            var table = document.getElementById('account_user_table');
            if (table) {
                var ths = table.querySelectorAll('thead th');
                if (ths[0]) ths[0].textContent = saTextV6('account_no', 'No');
                if (ths[1]) ths[1].textContent = saTextV6('account_user_name', 'User Name');
                if (ths[2]) ths[2].textContent = saTextV6('account_qrcode', 'QRCode');
            }

            var rows = document.querySelectorAll('#settingAccountModal .account-form-row .t1');
            if (rows[0]) rows[0].textContent = settingAccountStripColonText(saTextV6('account_username', 'Username')) + saColonV6();
            if (rows[1]) rows[1].textContent = settingAccountStripColonText(saTextV6('account_password', 'Password')) + saColonV6();
            if (rows[2]) rows[2].textContent = settingAccountStripColonText(saTextV6('account_confirm_password', 'Confirm Password')) + saColonV6();
            if (rows[3]) rows[3].textContent = settingAccountStripColonText(saTextV6('account_permission', 'Permission')) + saColonV6();

            var btns = document.querySelectorAll('#settingAccountModal .account-modal-footer .button-modal');
            if (btns[0]) btns[0].textContent = saTextV6('save', 'Save');
            if (btns[1]) btns[1].textContent = saTextV6('close', 'Close');

            var footer = document.querySelector('#AccountDisplay .account-footer .buttonbox');
            if (footer) {
                var newBtn = document.getElementById('account_new_btn');
                var editBtn = document.getElementById('account_edit_btn');
                var delBtn = document.getElementById('account_delete_btn');
                var importBtn = document.getElementById('account_import_btn');
                var exportBtn = document.getElementById('account_export_btn');
                var uploadBtn = document.getElementById('account_upload_controller_btn');
                var templateBtn = document.getElementById('account_template_btn');
                if (newBtn) newBtn.value = saTextV6('New', 'New');
                if (editBtn) editBtn.value = saTextV6('Edit', 'Edit');
                if (delBtn) delBtn.value = saTextV6('Delete', 'Delete');
                if (importBtn) importBtn.value = saTextV6('account_import', 'Import');
                if (exportBtn) exportBtn.value = saTextV6('account_export', 'Export');
                if (uploadBtn) uploadBtn.value = saTextV6('account_upload', 'Sync to Controller');
                if (templateBtn) templateBtn.value = saTextV6('account_csv_template', 'CSV Template');
            }

            var searchInput = document.getElementById('accountSearchInput');
            if (searchInput) searchInput.setAttribute('placeholder', saTextV6('account_search_placeholder', 'Search account...'));

            if (typeof updateSettingAccountSummary === 'function') {
                updateSettingAccountSummary((document.querySelectorAll('#AccountDisplay .account-user-row') || []).length);
            }

            document.querySelectorAll('#AccountDisplay .account-qrcode-btn').forEach(function(btn){
                btn.textContent = saTextV6('account_qrcode_download', 'Download');
            });

            document.querySelectorAll('#settingAccountModal .password-eye-btn').forEach(function(btn){
                var input = btn.parentNode ? btn.parentNode.querySelector('input') : null;
                var isText = !!(input && input.type === 'text');
                var tip = isText ? saTextV6('account_hide_password', 'Hide password') : saTextV6('account_show_password', 'Show password');
                btn.setAttribute('title', tip);
                btn.setAttribute('aria-label', tip);
            });

            if (window.settingAccountMode === 'new') {
                var mt = document.getElementById('settingAccountModalTitle');
                if (mt) mt.textContent = saTextV6('account_new_title', 'New Account');
            } else if (window.settingAccountMode === 'edit') {
                var mt2 = document.getElementById('settingAccountModalTitle');
                if (mt2) mt2.textContent = saTextV6('account_edit_title', 'Edit Account');
            }
        } catch (e) {}
    }

    window.settingAccountApplyStaticI18nV6 = settingAccountApplyStaticI18nV6;

    document.addEventListener('DOMContentLoaded', function(){
        settingAccountApplyStaticI18nV6();
        setTimeout(settingAccountApplyStaticI18nV6, 100);
        setTimeout(settingAccountApplyStaticI18nV6, 600);
    });
    window.addEventListener('load', function(){
        settingAccountApplyStaticI18nV6();
        setTimeout(settingAccountApplyStaticI18nV6, 200);
    });

    var oldOpen = window.OpenButton;
    if (typeof oldOpen === 'function' && !oldOpen.__saV6Wrap) {
        window.OpenButton = function(mode){
            var ret = oldOpen.apply(this, arguments);
            if (mode === 'Account') {
                setTimeout(settingAccountApplyStaticI18nV6, 50);
                setTimeout(settingAccountApplyStaticI18nV6, 300);
            }
            return ret;
        };
        window.OpenButton.__saV6Wrap = true;
    }

    var oldAction = window.settingAccountAction;
    if (typeof oldAction === 'function' && !oldAction.__saV6Wrap) {
        window.settingAccountAction = function(mode){
            var ret = oldAction.apply(this, arguments);
            setTimeout(settingAccountApplyStaticI18nV6, 50);
            setTimeout(settingAccountApplyStaticI18nV6, 200);
            return ret;
        };
        window.settingAccountAction.__saV6Wrap = true;
    }

    var oldRender = window.renderSettingAccountUsers;
    if (typeof oldRender === 'function' && !oldRender.__saV6Wrap) {
        window.renderSettingAccountUsers = function(records){
            var ret = oldRender.apply(this, arguments);
            setTimeout(settingAccountApplyStaticI18nV6, 0);
            return ret;
        };
        window.renderSettingAccountUsers.__saV6Wrap = true;
    }
})();




/* =====================================================
   QRCode download button i18n force patch V7
   Ensure the Download button always follows cookie language.
   ===================================================== */
(function(){
    function saV7Lang(){
        var m = (document.cookie || '').match(/(?:^|;\s*)(?:language|lang)=([^;]+)/i);
        var lang = m ? decodeURIComponent(m[1]).toLowerCase() : 'en-us';
        lang = lang.replace('_','-');
        if (lang === 'tw' || lang === 'zh-hant') return 'zh-tw';
        if (lang === 'cn' || lang === 'zh-hans') return 'zh-cn';
        if (lang === 'en') return 'en-us';
        return lang;
    }
    function saV7DownloadText(){
        var lang = saV7Lang();
        if (typeof saT === 'function') {
            var t = saT('account_qrcode_download', '');
            if (t && t !== 'account_qrcode_download') return t;
        }
        if (typeof settingAccountI18nText === 'function') {
            var t2 = settingAccountI18nText('account_qrcode_download');
            if (t2 && t2 !== 'account_qrcode_download') return t2;
        }
        if (lang === 'zh-tw') return '下載';
        if (lang === 'zh-cn') return '下载';
        return 'Download';
    }
    function applyQrDownloadBtnI18nV7(){
        var txt = saV7DownloadText();
        document.querySelectorAll('#AccountDisplay .account-qrcode-btn').forEach(function(btn){
            btn.textContent = txt;
            btn.value = txt;
            btn.setAttribute('data-i18n-download', txt);
            btn.setAttribute('title', txt);
            btn.setAttribute('aria-label', txt);
        });
        var ths = document.querySelectorAll('#account_user_table thead th');
        if (ths.length >= 3) {
            ths[2].textContent = (saV7Lang() === 'zh-tw' || saV7Lang() === 'zh-cn') ? 'QRCode' : 'QRCode';
        }
    }
    window.applyQrDownloadBtnI18nV7 = applyQrDownloadBtnI18nV7;
    document.addEventListener('DOMContentLoaded', function(){
        applyQrDownloadBtnI18nV7();
        setTimeout(applyQrDownloadBtnI18nV7, 100);
        setTimeout(applyQrDownloadBtnI18nV7, 500);
    });
    window.addEventListener('load', function(){
        applyQrDownloadBtnI18nV7();
        setTimeout(applyQrDownloadBtnI18nV7, 200);
    });
    var oldRender = window.renderSettingAccountUsers;
    if (typeof oldRender === 'function' && !oldRender.__v7wrap) {
        window.renderSettingAccountUsers = function(){
            var ret = oldRender.apply(this, arguments);
            setTimeout(applyQrDownloadBtnI18nV7, 0);
            setTimeout(applyQrDownloadBtnI18nV7, 120);
            return ret;
        };
        window.renderSettingAccountUsers.__v7wrap = true;
    }
    var oldOpen = window.settingAccountAction;
    if (typeof oldOpen === 'function' && !oldOpen.__v7wrap) {
        window.settingAccountAction = function(){
            var ret = oldOpen.apply(this, arguments);
            setTimeout(applyQrDownloadBtnI18nV7, 50);
            return ret;
        };
        window.settingAccountAction.__v7wrap = true;
    }
})();




/* Account search filter + summary refresh */
(function(){
    function bindAccountSearchInput() {
        var input = document.getElementById('accountSearchInput');
        if (!input || input.getAttribute('data-bound') === '1') return;
        input.setAttribute('data-bound', '1');
        input.oninput = function() {
            renderSettingAccountUsers(settingAccountAllRecords || []);
        };
        input.setAttribute('placeholder', saT('account_search_placeholder', 'Search account...'));
        updateSettingAccountSummary((document.querySelectorAll('#AccountDisplay .account-user-row') || []).length);
    }

    document.addEventListener('DOMContentLoaded', bindAccountSearchInput);
    window.addEventListener('load', bindAccountSearchInput);

    var oldLoadSearch = window.loadSettingAccountUsers;
    if (typeof oldLoadSearch === 'function' && !oldLoadSearch.__searchWrap) {
        window.loadSettingAccountUsers = function(){
            bindAccountSearchInput();
            return oldLoadSearch.apply(this, arguments);
        };
        window.loadSettingAccountUsers.__searchWrap = true;
    }
})();

// Account Password Mask DOM hook
document.addEventListener('DOMContentLoaded', function() { applySettingAccountColonFix(); resetSettingAccountPasswordMask(); });


/* Account Password Mask V6 guard disabled.
   Reason: In Edit Account, the old guard refilled account_password when the
   user intentionally cleared it, so the original password immediately came
   back. Password is now loaded only by settingAccountOpenEditWithMaskedPassword(). */
(function() {
    window.settingAccountPasswordGuardDisabled = true;
})();



/* =====================================================
   FINAL FORCE FIX V11
   Reason:
   settingAccountAction is still overwritten by applyQrDownloadBtnI18nV7 wrapper.
   V11 re-installs the final edit handler after all wrappers and keeps it final.
   ===================================================== */
(function () {
    function finalSaTextV11(key, fallback) {
        if (typeof saT === 'function') {
            return saT(key, fallback || key);
        }
        return fallback || key;
    }

    function finalSelectedRowV11() {
        return document.querySelector(
            '#accountUserTbody tr.selected,' +
            '#accountUserTbody tr.active,' +
            '#AccountDisplay .account-user-row.selected,' +
            '#AccountDisplay .account-user-row.active'
        );
    }

    function finalSelectedUserV11() {
        if (typeof selectedAccountUser !== 'undefined' && selectedAccountUser) {
            return String(selectedAccountUser || '');
        }

        var row = finalSelectedRowV11();
        return row ? String(row.getAttribute('data-name') || '') : '';
    }

    function finalSelectedPasswordV11() {
        var row = finalSelectedRowV11();

        if (row && row.dataset && row.dataset.passwd) {
            return String(row.dataset.passwd || '');
        }

        if (typeof selectedAccountPassword !== 'undefined' && selectedAccountPassword) {
            return String(selectedAccountPassword || '');
        }

        var username = finalSelectedUserV11();

        if (typeof settingAccountFindPasswordByUsername === 'function') {
            return String(settingAccountFindPasswordByUsername(username) || '');
        }

        return '';
    }

    function finalSetMaskedPasswordV11(realPassword) {
        var pwdInput = document.getElementById('account_password');
        var confirmInput = document.getElementById('account_confirm_password');

        realPassword = String(realPassword || '');

        if (pwdInput) {
            pwdInput.type = realPassword ? 'text' : 'password';
            pwdInput.value = realPassword ? '****' : '';
            pwdInput.setAttribute('data-real-password', realPassword);
            pwdInput.setAttribute('data-mask-mode', realPassword ? 'masked' : 'empty');
            pwdInput.setAttribute('autocomplete', 'off');
        }

        if (confirmInput) {
            confirmInput.type = realPassword ? 'text' : 'password';
            confirmInput.value = realPassword ? '****' : '';
            confirmInput.setAttribute('data-real-password', realPassword);
            confirmInput.setAttribute('data-mask-mode', realPassword ? 'masked' : 'empty');
            confirmInput.setAttribute('autocomplete', 'off');
        }

        document.querySelectorAll('#settingAccountModal .password-eye-btn, #settingAccountModal .password-toggle-btn').forEach(function(btn) {
            btn.classList.remove('is-visible');
            btn.setAttribute('title', finalSaTextV11('account_show_password', 'Show password'));
            btn.setAttribute('aria-label', finalSaTextV11('account_show_password', 'Show password'));
        });
    }

    function finalOpenEditV11() {
        var username = finalSelectedUserV11();
        var realPassword = finalSelectedPasswordV11();

        if (!username) {
            if (typeof settingAccountAlert === 'function') {
                settingAccountAlert(finalSaTextV11('account_select_one', 'Please select one account.'));
            } else {
                IdasNotify.alert(finalSaTextV11('account_select_one', 'Please select one account.'));
            }
            return false;
        }

        if (typeof settingAccountMode !== 'undefined') {
            settingAccountMode = 'edit';
        }

        if (typeof selectedAccountUser !== 'undefined') {
            selectedAccountUser = username;
        }

        if (typeof selectedAccountPassword !== 'undefined') {
            selectedAccountPassword = realPassword;
        }

        if (typeof selectedAccountLaw !== 'undefined') {
            selectedAccountLaw = (typeof settingAccountFindLawByUsername === 'function') ? settingAccountFindLawByUsername(username) : '1';
        }

        var title = document.getElementById('settingAccountModalTitle');
        var oldUsername = document.getElementById('account_old_username');
        var usernameInput = document.getElementById('account_username');

        if (title) {
            title.innerText = finalSaTextV11('account_edit_title', 'Edit Account');
        }

        if (oldUsername) {
            oldUsername.value = username;
        }

        if (usernameInput) {
            usernameInput.value = username;
            if (String(username || '').toLowerCase() === 'admin') {
                usernameInput.setAttribute('readonly', 'readonly');
                usernameInput.classList.add('account-username-readonly');
            } else {
                usernameInput.removeAttribute('readonly');
                usernameInput.classList.remove('account-username-readonly');
            }
        }

        if (typeof settingAccountSetLawInput === 'function') {
            settingAccountSetLawInput((typeof selectedAccountLaw !== 'undefined') ? selectedAccountLaw : '1');
        }

        if (typeof openSettingAccountModal === 'function') {
            openSettingAccountModal();
        } else {
            var modal = document.getElementById('settingAccountModal');
            if (modal) {
                modal.style.display = 'block';
            }
        }

        finalSetMaskedPasswordV11(realPassword);

        setTimeout(function(){ finalSetMaskedPasswordV11(realPassword); }, 30);
        setTimeout(function(){ finalSetMaskedPasswordV11(realPassword); }, 120);
        setTimeout(function(){ finalSetMaskedPasswordV11(realPassword); }, 300);
        setTimeout(function(){ finalSetMaskedPasswordV11(realPassword); }, 600);

        return false;
    }

    function finalTogglePasswordV11(inputId, btn) {
        var input = document.getElementById(inputId);
        if (!input) return;

        var realPassword = input.getAttribute('data-real-password') || input.value || '';
        var mode = input.getAttribute('data-mask-mode') || '';

        if (mode === 'masked' || input.value === '****') {
            input.type = 'text';
            input.value = realPassword;
            input.setAttribute('data-mask-mode', 'visible');

            if (btn) {
                btn.classList.add('is-visible');
                btn.setAttribute('title', finalSaTextV11('account_hide_password', 'Hide password'));
                btn.setAttribute('aria-label', finalSaTextV11('account_hide_password', 'Hide password'));
            }
            return;
        }

        if (mode === 'visible') {
            realPassword = input.value || realPassword;
            input.type = 'text';
            input.value = realPassword ? '****' : '';
            input.setAttribute('data-real-password', realPassword);
            input.setAttribute('data-mask-mode', realPassword ? 'masked' : 'empty');

            if (btn) {
                btn.classList.remove('is-visible');
                btn.setAttribute('title', finalSaTextV11('account_show_password', 'Show password'));
                btn.setAttribute('aria-label', finalSaTextV11('account_show_password', 'Show password'));
            }
            return;
        }

        input.type = input.type === 'password' ? 'text' : 'password';
    }

    function finalResolvePasswordForSaveV11(inputId) {
        var input = document.getElementById(inputId);
        if (!input) return '';

        var value = String(input.value || '').trim();
        var realPassword = input.getAttribute('data-real-password') || '';

        if (value === '****' && realPassword !== '') {
            return realPassword;
        }

        return value;
    }

    function installFinalPasswordMaskV11() {
        if (window.settingAccountAction && window.settingAccountAction.__finalPasswordMaskV11) {
            return;
        }

        if (
            typeof window.settingAccountAction === 'function' &&
            !window.settingAccountAction.__finalPasswordMaskV11 &&
            !window.__settingAccountActionBaseV11
        ) {
            window.__settingAccountActionBaseV11 = window.settingAccountAction;
        }

        window.settingAccountAction = function(mode) {
            if (mode === 'edit') {
                return finalOpenEditV11();
            }

            if (typeof window.__settingAccountActionBaseV11 === 'function') {
                return window.__settingAccountActionBaseV11.apply(this, arguments);
            }

            return false;
        };

        window.settingAccountAction.__finalPasswordMaskV11 = true;

        window.toggleSettingAccountPassword = finalTogglePasswordV11;
        window.settingAccountResolvePasswordForSave = finalResolvePasswordForSaveV11;

        window.settingAccountPasswordMaskV11 = {
            install: installFinalPasswordMaskV11,
            openEdit: finalOpenEditV11,
            setMaskedPassword: finalSetMaskedPasswordV11,
            selectedPassword: finalSelectedPasswordV11
        };
    }

    installFinalPasswordMaskV11();

    document.addEventListener('DOMContentLoaded', function() {
        installFinalPasswordMaskV11();
        setTimeout(installFinalPasswordMaskV11, 50);
        setTimeout(installFinalPasswordMaskV11, 200);
        setTimeout(installFinalPasswordMaskV11, 800);
    });

    window.addEventListener('load', function() {
        installFinalPasswordMaskV11();
        setTimeout(installFinalPasswordMaskV11, 50);
        setTimeout(installFinalPasswordMaskV11, 300);
        setTimeout(installFinalPasswordMaskV11, 1000);
    });

    // Last safety: V7/i18n wrappers may run later.
    // Keep this for controller pages where scripts are included in unusual order.
    var finalMaskV11Times = 0;
    var finalMaskV11Timer = setInterval(function() {
        finalMaskV11Times++;
        installFinalPasswordMaskV11();

        if (finalMaskV11Times > 30) {
            clearInterval(finalMaskV11Timer);
        }
    }, 300);
})();


/* Account modal language final safety patch */
(function() {
    function safeApplyAccountI18n() {
        try {
            if (typeof applySettingAccountColonFix === 'function') {
                applySettingAccountColonFix();
            }
            if (typeof settingAccountApplyStaticI18nV6 === 'function') {
                settingAccountApplyStaticI18nV6();
            }
        } catch (e) {}
    }

    document.addEventListener('DOMContentLoaded', function() {
        safeApplyAccountI18n();
        setTimeout(safeApplyAccountI18n, 100);
        setTimeout(safeApplyAccountI18n, 500);
    });

    window.addEventListener('load', function() {
        safeApplyAccountI18n();
        setTimeout(safeApplyAccountI18n, 300);
    });

    if (typeof window.openSettingAccountModal === 'function' && !window.openSettingAccountModal.__finalLangWrapped) {
        var oldOpen = window.openSettingAccountModal;
        window.openSettingAccountModal = function() {
            var ret = oldOpen.apply(this, arguments);
            setTimeout(safeApplyAccountI18n, 0);
            return ret;
        };
        window.openSettingAccountModal.__finalLangWrapped = true;
    }
})();


/* =====================================================
   FINAL FIX V12
   New Account password must be masked by default.
   - Password and Confirm Password both show * while typing.
   - Click eye to show clear digits.
   - Save uses data-real-password, not the stars.
   ===================================================== */
(function () {
    function textV12(key, fallback) {
        if (typeof saT === 'function') return saT(key, fallback || key);
        return fallback || key;
    }

    function eyeBtnV12(input) {
        if (!input || !input.parentNode) return null;
        return input.parentNode.querySelector('.password-eye-btn, .password-toggle-btn');
    }

    function setEyeV12(input, visible) {
        var btn = eyeBtnV12(input);
        if (!btn) return;
        if (visible) {
            btn.classList.add('is-visible');
            btn.setAttribute('title', textV12('account_hide_password', 'Hide password'));
            btn.setAttribute('aria-label', textV12('account_hide_password', 'Hide password'));
        } else {
            btn.classList.remove('is-visible');
            btn.setAttribute('title', textV12('account_show_password', 'Show password'));
            btn.setAttribute('aria-label', textV12('account_show_password', 'Show password'));
        }
    }

    function starsV12(len) {
        len = Math.max(0, Math.min(4, parseInt(len || 0, 10)));
        return new Array(len + 1).join('*');
    }

    function digitsV12(value) {
        return String(value || '').replace(/[^0-9]/g, '').slice(0, 4);
    }

    function setMaskedInputV12(input, realPassword) {
        if (!input) return;
        realPassword = digitsV12(realPassword);
        input.type = 'text';
        input.value = realPassword ? starsV12(realPassword.length) : '';
        input.setAttribute('data-real-password', realPassword);
        input.setAttribute('data-mask-mode', realPassword ? 'masked' : 'empty');
        input.setAttribute('autocomplete', 'off');
        setEyeV12(input, false);
    }

    function prepareNewPasswordInputsV12() {
        setMaskedInputV12(document.getElementById('account_password'), '');
        setMaskedInputV12(document.getElementById('account_confirm_password'), '');
    }

    function normalizeMaskedTypingV12(input) {
        if (!input) return;
        var mode = input.getAttribute('data-mask-mode') || '';
        if (mode === 'visible') {
            var visibleDigits = digitsV12(input.value);
            input.type = 'text';
            input.value = visibleDigits;
            input.setAttribute('data-real-password', visibleDigits);
            input.setAttribute('data-mask-mode', 'visible');
            setEyeV12(input, true);
            return;
        }

        var oldReal = digitsV12(input.getAttribute('data-real-password') || '');
        var rawValue = String(input.value || '');
        var typedDigits = digitsV12(rawValue);
        var nextReal = oldReal;

        if (typedDigits !== '') {
            // Mobile/browser autocomplete may append digits to the visible stars.
            // Keep existing real password and append the newly typed digit(s).
            if (rawValue.indexOf('*') !== -1 && oldReal !== '') {
                nextReal = digitsV12(oldReal + typedDigits);
            } else {
                nextReal = typedDigits;
            }
        } else if (rawValue === '') {
            nextReal = '';
        }

        setMaskedInputV12(input, nextReal);
    }

    function togglePasswordV12(inputId, btn) {
        var input = document.getElementById(inputId);
        if (!input) return;

        var mode = input.getAttribute('data-mask-mode') || '';
        var realPassword = digitsV12(input.getAttribute('data-real-password') || input.value || '');

        if (mode === 'visible') {
            setMaskedInputV12(input, realPassword);
            return;
        }

        input.type = 'text';
        input.value = realPassword;
        input.setAttribute('data-real-password', realPassword);
        input.setAttribute('data-mask-mode', 'visible');
        setEyeV12(input, true);
    }

    function resolvePasswordV12(inputId) {
        var input = document.getElementById(inputId);
        if (!input) return '';

        var realPassword = digitsV12(input.getAttribute('data-real-password') || '');
        var value = String(input.value || '').trim();

        if (/^\*+$/.test(value) && realPassword !== '') {
            return realPassword;
        }

        return digitsV12(value || realPassword);
    }

    function installNewPasswordMaskV12() {
        var p1 = document.getElementById('account_password');
        var p2 = document.getElementById('account_confirm_password');

        [p1, p2].forEach(function(input) {
            if (!input || input.getAttribute('data-new-mask-v12-installed')) return;
            input.setAttribute('data-new-mask-v12-installed', '1');
            input.addEventListener('input', function() {
                if (typeof settingAccountMode !== 'undefined' && settingAccountMode === 'new') {
                    normalizeMaskedTypingV12(input);
                }
            });
            input.addEventListener('paste', function() {
                if (typeof settingAccountMode !== 'undefined' && settingAccountMode === 'new') {
                    setTimeout(function(){ normalizeMaskedTypingV12(input); }, 0);
                }
            });
        });

        if (typeof window.toggleSettingAccountPassword !== 'function' || !window.toggleSettingAccountPassword.__newMaskV12) {
            window.toggleSettingAccountPassword = togglePasswordV12;
            window.toggleSettingAccountPassword.__newMaskV12 = true;
        }

        window.settingAccountResolvePasswordForSave = resolvePasswordV12;

        if (typeof window.settingAccountPrepareNewPasswordInputs !== 'function' || !window.settingAccountPrepareNewPasswordInputs.__newMaskV12) {
            window.settingAccountPrepareNewPasswordInputs = prepareNewPasswordInputsV12;
            window.settingAccountPrepareNewPasswordInputs.__newMaskV12 = true;
        }

        if (typeof window.settingAccountAction === 'function' && !window.settingAccountAction.__newPasswordMaskV12) {
            var baseAction = window.settingAccountAction;
            window.settingAccountAction = function(mode) {
                var result = baseAction.apply(this, arguments);
                if (mode === 'new') {
                    setTimeout(prepareNewPasswordInputsV12, 0);
                    setTimeout(prepareNewPasswordInputsV12, 60);
                    setTimeout(prepareNewPasswordInputsV12, 200);
                }
                return result;
            };
            window.settingAccountAction.__newPasswordMaskV12 = true;
        }
    }

    installNewPasswordMaskV12();
    document.addEventListener('DOMContentLoaded', function() {
        installNewPasswordMaskV12();
        setTimeout(installNewPasswordMaskV12, 100);
        setTimeout(installNewPasswordMaskV12, 500);
    });
    window.addEventListener('load', function() {
        installNewPasswordMaskV12();
        setTimeout(installNewPasswordMaskV12, 100);
        setTimeout(installNewPasswordMaskV12, 500);
    });
})();


/* Account Password Mask Fix V13
   New Account: Password and Confirm Password both default to **** while typing.
   Click eye => show clear digits; click again => mask back to ****.
*/
(function() {
    function digitsV13(value) {
        return String(value || '').replace(/[^0-9]/g, '').slice(0, 4);
    }

    function starsV13(len) {
        len = Math.max(0, Math.min(4, parseInt(len || 0, 10)));
        return new Array(len + 1).join('*');
    }

    function textV13(key, fallback) {
        try {
            if (typeof saT === 'function') return saT(key, fallback);
        } catch (e) {}
        return fallback || key;
    }

    function eyeBtnV13(input) {
        if (!input || !input.parentNode) return null;
        return input.parentNode.querySelector('.password-eye-btn, .password-toggle-btn');
    }

    function setEyeV13(input, visible) {
        var btn = eyeBtnV13(input);
        if (!btn) return;
        if (visible) {
            btn.classList.add('is-visible');
            btn.setAttribute('title', textV13('account_hide_password', 'Hide password'));
            btn.setAttribute('aria-label', textV13('account_hide_password', 'Hide password'));
        } else {
            btn.classList.remove('is-visible');
            btn.setAttribute('title', textV13('account_show_password', 'Show password'));
            btn.setAttribute('aria-label', textV13('account_show_password', 'Show password'));
        }
    }

    function setMaskedV13(input, realPassword) {
        if (!input) return;
        realPassword = digitsV13(realPassword);
        input.type = 'text';
        input.value = realPassword ? starsV13(realPassword.length) : '';
        input.setAttribute('data-real-password', realPassword);
        input.setAttribute('data-mask-mode', realPassword ? 'masked' : 'empty');
        input.setAttribute('autocomplete', 'off');
        setEyeV13(input, false);
    }

    function setVisibleV13(input, realPassword) {
        if (!input) return;
        realPassword = digitsV13(realPassword);
        input.type = 'text';
        input.value = realPassword;
        input.setAttribute('data-real-password', realPassword);
        input.setAttribute('data-mask-mode', 'visible');
        input.setAttribute('autocomplete', 'off');
        setEyeV13(input, true);
    }

    function prepareNewPasswordInputsV13() {
        setMaskedV13(document.getElementById('account_password'), '');
        setMaskedV13(document.getElementById('account_confirm_password'), '');
    }

    function updateMaskedTypingV13(input, event) {
        if (!input || typeof settingAccountMode === 'undefined' || settingAccountMode !== 'new') return;

        var mode = input.getAttribute('data-mask-mode') || '';
        var oldReal = digitsV13(input.getAttribute('data-real-password') || '');

        if (mode === 'visible') {
            setVisibleV13(input, input.value);
            return;
        }

        var nextReal = oldReal;
        var inputType = event && event.inputType ? String(event.inputType) : '';
        var data = event && event.data !== null && event.data !== undefined ? String(event.data) : '';
        var dataDigits = digitsV13(data);
        var rawDigits = digitsV13(input.value);

        if (inputType.indexOf('delete') === 0) {
            nextReal = oldReal.slice(0, -1);
        } else if (dataDigits !== '') {
            nextReal = digitsV13(oldReal + dataDigits);
        } else if (rawDigits !== '') {
            // Fallback for browsers that do not provide event.data.
            // If inline handlers or IME produced only the new digit, append it;
            // otherwise use the full numeric value.
            if (oldReal !== '' && rawDigits.length <= 1 && oldReal.length < 4) {
                nextReal = digitsV13(oldReal + rawDigits);
            } else {
                nextReal = rawDigits;
            }
        } else if (String(input.value || '') === '') {
            nextReal = '';
        }

        setMaskedV13(input, nextReal);
    }

    function togglePasswordV13(inputId, btn) {
        var input = document.getElementById(inputId);
        if (!input) return;

        var realPassword = digitsV13(input.getAttribute('data-real-password') || input.value || '');
        var mode = input.getAttribute('data-mask-mode') || '';

        if (mode === 'visible') {
            setMaskedV13(input, realPassword);
        } else {
            setVisibleV13(input, realPassword);
        }
    }

    function resolvePasswordV13(inputId) {
        var input = document.getElementById(inputId);
        if (!input) return '';
        var realPassword = digitsV13(input.getAttribute('data-real-password') || '');
        var value = String(input.value || '').trim();
        if (/^\*+$/.test(value) && realPassword !== '') return realPassword;
        return digitsV13(value || realPassword);
    }

    function installV13() {
        var p1 = document.getElementById('account_password');
        var p2 = document.getElementById('account_confirm_password');

        [p1, p2].forEach(function(input) {
            if (!input) return;

            // Remove old inline filter because it turns **** + typed digit into a clear digit.
            input.removeAttribute('oninput');
            input.setAttribute('maxlength', '4');
            input.setAttribute('inputmode', 'numeric');
            input.setAttribute('pattern', '[0-9]{4}');

            if (!input.getAttribute('data-new-mask-v13-installed')) {
                input.setAttribute('data-new-mask-v13-installed', '1');
                input.addEventListener('input', function(e) {
                    updateMaskedTypingV13(input, e);
                });
                input.addEventListener('paste', function() {
                    if (typeof settingAccountMode !== 'undefined' && settingAccountMode === 'new') {
                        window.setTimeout(function() {
                            var pasted = digitsV13(input.value);
                            setMaskedV13(input, pasted || input.getAttribute('data-real-password') || '');
                        }, 0);
                    }
                });
            }
        });

        window.toggleSettingAccountPassword = togglePasswordV13;
        window.settingAccountResolvePasswordForSave = resolvePasswordV13;
        window.settingAccountPrepareNewPasswordInputs = prepareNewPasswordInputsV13;

        if (typeof window.settingAccountAction === 'function' && !window.settingAccountAction.__newPasswordMaskV13) {
            var baseAction = window.settingAccountAction;
            window.settingAccountAction = function(mode) {
                var result = baseAction.apply(this, arguments);
                if (mode === 'new') {
                    prepareNewPasswordInputsV13();
                    window.setTimeout(prepareNewPasswordInputsV13, 0);
                    window.setTimeout(prepareNewPasswordInputsV13, 80);
                    window.setTimeout(prepareNewPasswordInputsV13, 250);
                }
                return result;
            };
            window.settingAccountAction.__newPasswordMaskV13 = true;
        }
    }

    installV13();
    document.addEventListener('DOMContentLoaded', function() {
        installV13();
        window.setTimeout(installV13, 100);
        window.setTimeout(installV13, 500);
    });
    window.addEventListener('load', function() {
        installV13();
        window.setTimeout(installV13, 100);
        window.setTimeout(installV13, 500);
    });
})();
</script>
