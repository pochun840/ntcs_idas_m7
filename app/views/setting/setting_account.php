<!-- setting_account OP -->
<?php
// User management view is separated from setting/index.php.
// Path on controller: /var/www/html/idas/app/views/setting/setting_account.php

if (!function_exists('settingAccountDetectLocale')) {
    function settingAccountDetectLocale() {
        global $text;

        // 1) 優先抓 cookie language / lang，避免 PHP $text 判斷失準
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

if (!function_exists('settingAccountLocalFallback')) {
    function settingAccountLocalFallback($key, $default = '') {
        $locale = settingAccountDetectLocale();

        $dict = [
            'en-us' => [
                'account' => 'Account',
                'account_no' => 'No',
                'account_user_name' => 'User Name',
                'account_select_tab' => 'Please select Account tab',
                'account_username' => 'Username',
                'account_password' => 'Password',
                'account_confirm_password' => 'Confirm Password',
                'account_permission' => 'Permission',
                'account_import' => 'Import',
                'account_export' => 'Export',
                'account_upload' => 'Sync to Controller',
                'account_csv_template' => 'CSV Template',
                'account_total_accounts' => 'Accounts',
                'account_last_sync_time' => 'Last sync',
                'account_sync_status' => 'Sync status',
                'account_sync_success' => 'Success',
                'account_sync_failed' => 'Failed',
                'account_search_placeholder' => 'Search account...',
                'account_new_title' => 'New Account',
                'account_edit_title' => 'Edit Account',
                'account_show_password' => 'Show password',
                'save' => 'Save',
                'close' => 'Close',
                'New' => 'New',
                'Edit' => 'Edit',
                'Delete' => 'Delete',
            ],
            'zh-tw' => [
                'account' => '帳號',
                'account_no' => '編號',
                'account_user_name' => '使用者名稱',
                'account_select_tab' => '請選擇帳號分頁',
                'account_username' => '使用者名稱',
                'account_password' => '密碼',
                'account_confirm_password' => '確認密碼',
                'account_permission' => '權限',
                'account_import' => '匯入',
                'account_export' => '匯出',
                'account_upload' => '同步到控制器',
                'account_csv_template' => 'CSV 範本',
                'account_total_accounts' => '帳號數',
                'account_last_sync_time' => '最後同步時間',
                'account_sync_status' => '同步狀態',
                'account_sync_success' => '成功',
                'account_sync_failed' => '失敗',
                'account_search_placeholder' => '搜尋帳號...',
                'account_new_title' => '新增帳號',
                'account_edit_title' => '編輯帳號',
                'account_show_password' => '顯示密碼',
                'save' => '儲存',
                'close' => '關閉',
                'New' => '新增',
                'Edit' => '編輯',
                'Delete' => '刪除',
            ],
            'zh-cn' => [
                'account' => '账号',
                'account_no' => '编号',
                'account_user_name' => '使用者名称',
                'account_select_tab' => '请选择账号分页',
                'account_username' => '使用者名称',
                'account_password' => '密码',
                'account_confirm_password' => '确认密码',
                'account_permission' => '权限',
                'account_import' => '导入',
                'account_export' => '导出',
                'account_upload' => '同步到控制器',
                'account_csv_template' => 'CSV 范本',
                'account_total_accounts' => '账号数',
                'account_last_sync_time' => '最后同步时间',
                'account_sync_status' => '同步状态',
                'account_sync_success' => '成功',
                'account_sync_failed' => '失败',
                'account_search_placeholder' => '搜寻账号...',
                'account_new_title' => '新增账号',
                'account_edit_title' => '编辑账号',
                'account_show_password' => '显示密码',
                'save' => '储存',
                'close' => '关闭',
                'New' => '新增',
                'Edit' => '编辑',
                'Delete' => '删除',
            ],
        ];

        return $dict[$locale][$key] ?? $default;
    }
}

if (!function_exists('settingAccountViewText')) {
    function settingAccountViewText($key, $default = '') {
        global $text;

        if (isset($text[$key]) && (string)$text[$key] !== '') {
            $value = $text[$key];
        } else {
            $value = settingAccountLocalFallback($key, $default);
        }

        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('settingAccountLabelText')) {
    function settingAccountLabelText($key, $default = '') {
        $value = html_entity_decode(settingAccountViewText($key, $default), ENT_QUOTES, 'UTF-8');

        // Remove trailing colon variants from language file or old hard-coded text.
        $value = preg_replace('/\s*[:：]+\s*$/u', '', (string)$value);

        $locale = settingAccountDetectLocale();
        $colon = ($locale === 'en-us') ? ' :' : '：';

        return htmlspecialchars($value . $colon, ENT_QUOTES, 'UTF-8');
    }
}

?>
            <div id="AccountDisplay" class="setting-account-page" style="display:none;">
                <div class="account-summary-bar">
                    <div class="account-summary-left">
                        <span id="accountUserCountText"><?php echo settingAccountViewText('account_total_accounts', 'Accounts'); ?>: 0</span>
                        <span id="accountLastSyncText"><?php echo settingAccountViewText('account_last_sync_time', 'Last sync'); ?>: -</span>
                        <span id="accountSyncStatusText"><?php echo settingAccountViewText('account_sync_status', 'Sync status'); ?>: -</span>
                    </div>
                    <div class="account-summary-right">
                        <input id="accountSearchInput" class="account-search-input" type="text" placeholder="<?php echo settingAccountViewText('account_search_placeholder', 'Search account...'); ?>" autocomplete="off">
                    </div>
                </div>
                <div class="table-container account-table-container">
                    <div class="scrollbar" id="style-accounttable">
                        <div class="scrollbar-force-overflow">
                            <table id="account_user_table" class="table w3-table account-user-table">
                                <thead id="header-table">
                                    <tr class="w3-dark-grey">
                                        <th><?php echo settingAccountViewText('account_no', 'No'); ?></th>
                                        <th><?php echo settingAccountViewText('account_user_name', 'User Name'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="accountUserTbody" style="font-size: 1.8vmin;text-align: center;">
                                    <tr><td colspan="2"><?php echo settingAccountViewText('account_select_tab', 'Please select Account tab'); ?></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="footer account-footer">
                    <div class="buttonbox">
                        <input id="account_new_btn" type="button" value="<?php echo settingAccountViewText('New', 'New'); ?>" onclick="settingAccountAction('new')">
                        <input id="account_edit_btn" type="button" value="<?php echo settingAccountViewText('Edit', 'Edit'); ?>" onclick="settingAccountAction('edit')">
                        <input id="account_delete_btn" type="button" value="<?php echo settingAccountViewText('Delete', 'Delete'); ?>" onclick="settingAccountAction('delete')">
                        <input id="account_import_btn" type="button" value="<?php echo settingAccountViewText('account_import', 'Import'); ?>" onclick="settingAccountAction('import')">
                        <input id="account_export_btn" type="button" value="<?php echo settingAccountViewText('account_export', 'Export'); ?>" onclick="settingAccountAction('export')">
                        <input id="account_upload_controller_btn" class="account-sync-controller-btn" type="button" value="<?php echo settingAccountViewText('account_upload', 'Sync to Controller'); ?>" onclick="settingAccountAction('upload_controller')">
                    </div>
                    <input id="account_import_file" type="file" accept=".csv,text/csv" style="display:none" onchange="importSettingAccountFile(this)">
                </div>

                <!-- New / Edit Account Modal -->
                <div id="settingAccountModal" class="modal">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content w3-animate-zoom setting-account-modal">
                            <header class="w3-container modal-header">
                                <span onclick="closeSettingAccountModal();" class="w3-button w3-red w3-display-topright account-modal-x">&times;</span>
                                <h3 id="settingAccountModalTitle"><?php echo settingAccountViewText('account_new_title', 'New Account'); ?></h3>
                            </header>

                            <div class="modal-body account-modal-body">
                                <input type="hidden" id="account_old_username" value="">

                                <div class="row account-form-row">
                                    <div class="col-5 t1" id="settingAccountUsernameLabel"><?php echo settingAccountLabelText('account_username', 'Username'); ?></div>
                                    <div class="col-5 t2">
                                        <input type="text" class="form-control input-ms" id="account_username" maxlength="8" minlength="6" pattern="[A-Za-z0-9]{6,8}" autocomplete="off" oninput="this.value=this.value.replace(/[^A-Za-z0-9]/g,'').slice(0,8)">
                                    </div>
                                </div>

                                <div class="row account-form-row">
                                    <div class="col-5 t1" id="settingAccountPasswordLabel"><?php echo settingAccountLabelText('account_password', 'Password'); ?></div>
                                    <div class="col-5 t2">
                                        <div class="account-password-wrap">
                                            <input type="password" class="form-control input-ms" id="account_password" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,4)">
                                            <button type="button" class="password-eye-btn" onclick="toggleSettingAccountPassword('account_password', this)" title="<?php echo settingAccountViewText('account_show_password', 'Show password'); ?>" aria-label="<?php echo settingAccountViewText('account_show_password', 'Show password'); ?>">
                                                <span class="eye-symbol">&#128065;</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row account-form-row">
                                    <div class="col-5 t1" id="settingAccountConfirmPasswordLabel"><?php echo settingAccountLabelText('account_confirm_password', 'Confirm Password'); ?></div>
                                    <div class="col-5 t2">
                                        <div class="account-password-wrap">
                                            <input type="password" class="form-control input-ms" id="account_confirm_password" maxlength="4" inputmode="numeric" pattern="[0-9]{4}" autocomplete="off" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,4)">
                                            <button type="button" class="password-eye-btn" onclick="toggleSettingAccountPassword('account_confirm_password', this)" title="<?php echo settingAccountViewText('account_show_password', 'Show password'); ?>" aria-label="<?php echo settingAccountViewText('account_show_password', 'Show password'); ?>">
                                                <span class="eye-symbol">&#128065;</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="row account-form-row">
                                    <div class="col-5 t1" id="settingAccountPermissionLabel"><?php echo settingAccountLabelText('account_permission', 'Permission'); ?></div>
                                    <div class="col-5 t2">
                                        <select class="form-control input-ms" id="account_law" autocomplete="off">
                                            <option value="1" selected>guest</option>
                                            <option value="3">operator</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer justify-content-center">
                                <button id="settingAccountSaveBtn" class="button-modal" onclick="saveSettingAccount();"><?php echo settingAccountViewText('save', 'Save'); ?></button>
                                <button id="settingAccountCloseBtn" class="button-modal closebtn" onclick="closeSettingAccountModal();"><?php echo settingAccountViewText('close', 'Close'); ?></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<?php require_once '../app/views/setting/setting_account_style.php';?>
<?php require_once '../app/views/setting/setting_account_script.php';?>
<!-- setting_account ED -->
