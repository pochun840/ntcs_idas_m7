<?php if (idas_is_icontroller()): ?>
<div class="container-ms">
     <div class="buttonbox" style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding-right: 10px; width: 100%;">
    
    <?php foreach(LANGUAGE as $key =>$val){?>
        <input type="button" name='<?php echo $val[1];?>' id= '<?php echo $val[1];?>'  value="<?php echo $val[0];?>" onclick="language_change()" >
    <?php } ?>
    </div>
    <div>
        <h1 class="col-ms-3 pt-3" style="font-size: 50px; text-align: center; color: #fff"><?php echo $text['login_text']; ?></h1>
    </div>
    <form class="pt-4" action="?url=Logins" method="POST"> 
        <select id='username' name='username' class="custom-select">
            <?php foreach($data['account'] as $kc =>$vc){?>
                <option><?php echo $vc['name'];?></option>
            <?php } ?>
        </select>
        <input type="password" name="password" placeholder="<?php echo $text['password_text']; ?>" required>
        <button type="submit"><?php echo $text['login_text']; ?></button>
    </form>
</div>

  <script>

    function language_change() {
        var language = event.target.id;
        $.ajax({
        type: "POST",
        url: "?url=Dashboards/change_language",
        data: {'language':language},
        dataType: "json",
        encode: true,
        async: false,
        }).done(function (data) {
            location.reload();
        });
    }

    $(document).ready(function () {
        <?php 
            if($data['error_message'] != ''){
                echo "IdasNotify.alert('",$data['error_message'],"')";
            }
        ?>
    });

  </script>
<style>

html, body
{
    margin: 0;
    padding: 0px;
    background-color: #000000;
    background-image: url(./img/vn.jpg);
    background-size: cover;
    background-position: center;
}

.container-ms
{
    width: 100%;
    margin: 0 auto;
    padding: 20px;
}

.center-content
{
    display: flex;
    justify-content: center;
    align-items: center;
}

    /* Button Language */
.buttonbox input
{
    margin-bottom: 5px;
}

.buttonbox input
{
    border: none;
    outline: none;
    width:90px;
    height: 40px;
    background: #888888;
    color: #fff;
    font-size: 18px;
    border: 1px outset #666;
    border-radius: 5px;
    text-align:center;
}
.buttonbox input:hover
{
    cursor: pointer;
    background:#333300;
    color: white;
}

.buttonbox input:active
{
    background-color: #DDDDDD;
    box-shadow: 0 5px #666;
    transform: translateY(4px);
}

/* Login */
:focus { outline: none; }
::-webkit-input-placeholder { color: #DEDFDF; }
::-moz-placeholder { color: #DEDFDF; }
:-moz-placeholder { color: #DEDFDF; }
::-ms-input-placeholder { color: #DEDFDF; }

form
{
    float: center;
    max-width: 600px;
    height: 370px;
    margin: 0 auto;
    padding: 15vmin;

}

input[type=radio] { display: none; }

input[type=text],
input[type=password]
{
    background: #fff;
    border: none;
    border-radius: 8px;
    font-size: 25px;
    font-family: 'Raleway', sans-serif;
    height: 72px;
    width: 100%;
    margin-bottom: 10px;
    opacity: 1;
    text-indent: 20px;
    transition: all .2s ease-in-out;
}

button
{
    background: #079BCF;
    border: none;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    font-family: 'Raleway', sans-serif;
    font-size: 30px;
    height: 72px;
    width: 100%;
    margin-bottom: 10px;
    overflow: hidden;
    transition: all .3s cubic-bezier(.6,0,.4,1);
}

button
{
    display: block;
    line-height: 72px;
    position: relative;
    top: 0px;
    transform: translate3d(0,0,0);
}

button:hover
{
    background: #007BA5;
}


.custom-select {
    background: #fff;
    border: none;
    border-radius: 8px;
    font-size: 25px;
    font-family: 'Raleway', sans-serif;
    height: 72px;
    width: 100%;
    margin-bottom: 10px;
    opacity: 1;
    text-indent: 20px;
    transition: all .2s ease-in-out;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    position: relative;
    padding-right: 30px; /* Space for the custom arrow */
}

.custom-select::after {
    content: '▼'; /* Custom arrow (you can change this symbol to anything you like) */
    font-size: 20px;
    color: #333; /* Adjust color */
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none; /* Makes the arrow non-interactive */
}


</style>
<?php else: ?>
<?php
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}
?>
<div class="container-ms">
     <div class="buttonbox" style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; padding-right: 10px; width: 100%;">
    
    <?php foreach(LANGUAGE as $key =>$val){?>
        <input type="button" name='<?php echo $val[1];?>' id= '<?php echo $val[1];?>'  value="<?php echo $val[0];?>" onclick="language_change()" >
    <?php } ?>
    </div>
    <div>
        <h1 class="col-ms-3 pt-3" style="font-size: 50px; text-align: center; color: #fff"><?php echo $text['login_text']; ?></h1>
    </div>

    <form id="loginForm" class="pt-4" action="?url=Logins" method="POST">
        <select id="username" name="username" class="custom-select">
            <?php foreach($data['account'] as $kc =>$vc){
                $accountName = htmlspecialchars((string)($vc['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            ?>
                <option value="<?php echo $accountName; ?>"><?php echo $accountName; ?></option>
            <?php } ?>
        </select>
        <div class="login-password-wrap">
            <input id="login_password" type="password" name="password" placeholder="<?php echo $text['password_text']; ?>" required oninput="syncLoginPasswordMirrorFromPassword()">
            <input id="login_password_plain" type="text" class="login-password-plain" placeholder="<?php echo $text['password_text']; ?>" autocomplete="off" style="display:none;" oninput="syncLoginPasswordMirrorFromPlain()">
        </div>
        <input id="qr_payload" type="hidden" name="qr_payload" value="">
        <button type="submit" id="manualLoginBtn"><?php echo $text['login_text']; ?></button>
        <!--<button type="button" id="qrLoginOpenBtn" class="qr-login-btn" onclick="openQrLoginModal()">QR Code Login</button>-->
        <div id="loginPageError" class="login-page-error" style="display:none;"></div>
    </form>

    <!-- QR Code Login Modal：只支援掃碼槍 / USB QR Scanner，不使用相機、不上傳圖片 -->
    <div id="qrLoginModal" class="qr-modal" style="display:none;">
        <div class="qr-modal-card">
            <div class="qr-modal-header">
                <span id="qrLoginModalTitle">QR Code Login</span>
                <button type="button" class="qr-close-btn" onclick="closeQrLoginModal()">&times;</button>
            </div>

            <div class="qr-modal-body">
                <div class="qr-status" id="qrLoginStatus"><span id="qrLoginStatusText">Please scan QR Code</span></div>

                <textarea
                    id="qrScannerInput"
                    class="qr-scanner-input"
                    placeholder='Place the cursor here and scan the QR Code'
                    autocomplete="off"
                    autocapitalize="off"
                    spellcheck="false"
                    oninput="queueQrManualParse()"
                    onkeydown="qrScannerKeydown(event)"
                ></textarea>

                
                <div class="qr-keyboard-note" id="qrKeyboardNote">
                    使用 QRCode 登入時，鍵盤需切換為小寫英數輸入模式
                </div>
            </div>
        </div>
    </div>

    <div id="qrHintModal" class="qr-hint-modal" style="display:none;">
        <div class="qr-hint-card">
            <div class="qr-hint-header">
                <span id="qrHintTitle">QR Login Notice</span>
                <button type="button" class="qr-hint-close-btn" onclick="closeQrHintModal(false)">&times;</button>
            </div>
            <div class="qr-hint-body">
                <div class="qr-hint-message" id="qrHintMessage"></div>
                <div class="qr-hint-checklist">
                    <div id="qrHintStep1">1. Please switch the input method to <b>ENG / English</b>.</div>
                    <div id="qrHintStep2">2. Please turn <b>Caps Lock OFF</b>.</div>
                    <div id="qrHintStep3">3. Please set the QR scanner keyboard layout to <b>US English</b>.</div>
                    <div id="qrHintStep4">4. The QR Code should scan as: <b>{"usr":"abcd123","pwd":"0734"}</b></div>
                </div>
                <div class="qr-hint-preview-wrap" id="qrHintPreviewWrap" style="display:none;">
                    <div class="qr-hint-preview-title" id="qrHintPreviewTitle">Scanned text:</div>
                    <pre id="qrHintPreview"></pre>
                </div>
                <div class="qr-hint-actions">
                    <button type="button" id="qrHintTryAgainBtn" class="qr-hint-primary" onclick="closeQrHintModal(true)">Try Again</button>
                    <button type="button" id="qrHintCloseBtn" class="qr-hint-secondary" onclick="closeQrHintModal(false)">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="qrLoginLoadingOverlay" class="qr-login-loading-overlay" style="display:none;">
        <div class="qr-login-ambient">
            <span></span><span></span><span></span><span></span>
        </div>

        <div class="qr-login-loading-card">
            <div class="qr-login-card-glow"></div>
            <div class="qr-login-scanner-line"></div>

            <div class="qr-login-spinner-wrap">
                <div class="qr-login-orbit qr-login-orbit-1"><span></span></div>
                <div class="qr-login-orbit qr-login-orbit-2"><span></span></div>
                <div class="qr-login-orbit qr-login-orbit-3"><span></span></div>
                <div class="qr-login-spinner-ring"></div>
                <div class="qr-login-spinner-core">✓</div>
            </div>

            <div class="qr-login-loading-title" id="qrLoginLoadingTitle">Verifying Login</div>
            <div class="qr-login-loading-subtitle" id="qrLoginLoadingText">Checking account and password...</div>

            <div class="qr-login-progress">
                <span></span>
            </div>

            <div class="qr-login-loading-dots">
                <span></span><span></span><span></span>
            </div>
        </div>
    </div>
</div>

<script>
    let qrInputTimer = null;
    let qrGlobalBuffer = '';
    let qrGlobalLastTime = 0;
    let loginSubmitting = false;
    const LOGIN_SUCCESS_REDIRECT_DELAY_MS = 1000;


    function qrLoginGetCookie(name) {
        const parts = document.cookie ? document.cookie.split(';') : [];
        const prefix = name + '=';
        for (let i = 0; i < parts.length; i++) {
            const item = parts[i].trim();
            if (item.indexOf(prefix) === 0) {
                try {
                    return decodeURIComponent(item.substring(prefix.length));
                } catch (e) {
                    return item.substring(prefix.length);
                }
            }
        }
        return '';
    }

    function qrLoginGetLang() {
        let lang = String(qrLoginGetCookie('language') || qrLoginGetCookie('lang') || 'en-us')
            .trim()
            .toLowerCase()
            .replace('_', '-');

        if (lang === 'zh-tw' || lang === 'tw' || lang === 'zh-hant') return 'zh-tw';
        if (lang === 'zh-cn' || lang === 'cn' || lang === 'zh-hans') return 'zh-cn';
        return 'en-us';
    }

    function qrLoginText(key) {
        const lang = qrLoginGetLang();
        const dict = {
            'en-us': {
                qr_login_btn: 'QR Code Login',
                qr_modal_title: 'QR Code Login',
                qr_status_scan: 'Please scan QR Code',
                qr_waiting_scan: 'Waiting for scan... Please scan QR Code now.',
                login_show_password: 'Show password',
                login_hide_password: 'Hide password',
                login_submitting: 'Logging in...',
                qr_placeholder: 'Place the cursor here and scan the QR Code',
                qr_keyboard_note: 'When using QRCode login, switch the keyboard to lowercase alphanumeric input mode.',

                login_verify_title: 'Verifying Login',
                login_verify_text: 'Checking account and password...',
                qr_verify_title: 'Verifying QR Login',
                qr_verify_text: 'Checking QR account and password...',
                login_success_title: 'Login Success',
                login_success_redirect: '',
                login_redirecting_status: 'Login success. Redirecting...',
                qr_scan_success_checking: 'QR scan successful. Checking account...',
                qr_empty: 'QR text is empty.',
                qr_format_error_status: 'QR format error.',
                qr_format_error_title: 'QR Format Error',
                qr_format_error_msg: 'The QR Code text could not be parsed.\nPlease check the keyboard input method, Caps Lock, and scanner keyboard layout.',
                qr_missing_status: 'QR missing usr or pwd.',
                qr_missing_title: 'QR Missing Account or Password',
                qr_missing_msg: 'The QR Code was read, but usr / pwd was not found.\nPlease confirm the QR format and scanner keyboard setting.',
                qr_login_failed_title: 'QR Login Failed',
                qr_login_failed_msg_suffix: '\nPlease check the account/password in DB, keyboard input method, Caps Lock, and scanner keyboard layout.',
                qr_login_error_title: 'QR Login Error',
                qr_login_error_msg_suffix: '\nPlease check the scanner input and try again.',
                qr_form_not_found: 'Login form not found.',
                login_failed: 'Login failed.',
                login_error_USER_NOT_FOUND: 'Account does not exist.',
                login_error_PASSWORD_ERROR: 'Incorrect password.',
                login_error_LOGIN_EXPIRED: 'Login expired. Please login again.',
                qr_account_not_found_suffix: '\nPlease confirm the QR account exists in the iDAS user list.',
                qr_password_error_suffix: '\nPlease confirm the password in the QR Code or user table.',
                login_response_not_json: 'Login response is not JSON.',
                login_success_msg: 'Login success.',

                qr_hint_title: 'QR Login Notice',
                qr_hint_step_1: '1. Please switch the input method to <b>ENG / English</b>.',
                qr_hint_step_2: '2. Please turn <b>Caps Lock OFF</b>.',
                qr_hint_step_3: '3. Please set the QR scanner keyboard layout to <b>US English</b>.',
                qr_hint_step_4: '4. The QR Code should scan as: <b>{"usr":"abcd123","pwd":"0734"}</b>',
                qr_hint_scanned_text: 'Scanned text:',
                qr_hint_try_again: 'Try Again',
                qr_hint_close: 'Close'
            },
            'zh-tw': {
                qr_login_btn: 'QR Code 登入',
                qr_modal_title: 'QR Code 登入',
                qr_status_scan: '請掃描 QR Code',
                qr_waiting_scan: '等待掃描中...請立即掃描 QR Code。',
                login_show_password: '顯示密碼',
                login_hide_password: '隱藏密碼',
                login_submitting: '登入中...',
                qr_placeholder: '請將游標停在這裡後掃描 QR Code',
                qr_keyboard_note: '使用 QRCode 登入時，鍵盤需切換為小寫英數輸入模式。',

                login_verify_title: '登入驗證中',
                login_verify_text: '正在確認帳號與密碼...',
                qr_verify_title: 'QR Code 登入驗證中',
                qr_verify_text: '正在確認 QR Code 帳號與密碼...',
                login_success_title: '登入成功',
                login_success_redirect: '',
                login_redirecting_status: '登入成功，準備進入系統...',
                qr_scan_success_checking: 'QR Code 掃描成功，正在確認帳號...',
                qr_empty: 'QR Code 內容是空的。',
                qr_format_error_status: 'QR Code 格式錯誤。',
                qr_format_error_title: 'QR Code 格式錯誤',
                qr_format_error_msg: '無法解析 QR Code 文字。\n請確認輸入法、Caps Lock 與掃碼槍鍵盤配置。',
                qr_missing_status: 'QR Code 缺少 usr 或 pwd。',
                qr_missing_title: 'QR Code 缺少帳號或密碼',
                qr_missing_msg: '已讀取 QR Code，但找不到 usr / pwd。\n請確認 QR Code 格式與掃碼槍鍵盤設定。',
                qr_login_failed_title: 'QR Code 登入失敗',
                qr_login_failed_msg_suffix: '\n請確認資料庫帳號密碼、輸入法、Caps Lock 與掃碼槍鍵盤配置。',
                qr_login_error_title: 'QR Code 登入錯誤',
                qr_login_error_msg_suffix: '\n請確認掃描內容後再試一次。',
                qr_form_not_found: '找不到登入表單。',
                login_failed: '登入失敗。',
                login_error_USER_NOT_FOUND: '帳號不存在。',
                login_error_PASSWORD_ERROR: '密碼錯誤。',
                login_error_LOGIN_EXPIRED: '登入已逾時，請重新登入。',
                qr_account_not_found_suffix: '\n請確認 QR Code 帳號是否存在於 iDAS 使用者清單。',
                qr_password_error_suffix: '\n請確認 QR Code 內的密碼或 user table 密碼。',
                login_response_not_json: '登入回應不是 JSON。',
                login_success_msg: '登入成功。',

                qr_hint_title: 'QR Code 登入提醒',
                qr_hint_step_1: '1. 請先將輸入法切換為 <b>ENG / 英文</b>。',
                qr_hint_step_2: '2. 請確認 <b>Caps Lock 已關閉</b>。',
                qr_hint_step_3: '3. 請將 QR 掃碼槍鍵盤配置設定為 <b>US English</b>。',
                qr_hint_step_4: '4. QR Code 掃描內容應為：<b>{"usr":"abcd123","pwd":"0734"}</b>',
                qr_hint_scanned_text: '掃描到的文字：',
                qr_hint_try_again: '重新掃描',
                qr_hint_close: '關閉'
            },
            'zh-cn': {
                qr_login_btn: 'QR Code 登录',
                qr_modal_title: 'QR Code 登录',
                qr_status_scan: '请扫描 QR Code',
                qr_waiting_scan: '等待扫描中...请立即扫描 QR Code。',
                login_show_password: '显示密码',
                login_hide_password: '隐藏密码',
                login_submitting: '登录中...',
                qr_placeholder: '请将光标停在这里后扫描 QR Code',
                qr_keyboard_note: '使用 QRCode 登录时，键盘需切换为小写英数输入模式。',

                login_verify_title: '登录验证中',
                login_verify_text: '正在确认帐号与密码...',
                qr_verify_title: 'QR Code 登录验证中',
                qr_verify_text: '正在确认 QR Code 帐号与密码...',
                login_success_title: '登录成功',
                login_success_redirect: '',
                login_redirecting_status: '登录成功，准备进入系统...',
                qr_scan_success_checking: 'QR Code 扫描成功，正在确认帐号...',
                qr_empty: 'QR Code 内容是空的。',
                qr_format_error_status: 'QR Code 格式错误。',
                qr_format_error_title: 'QR Code 格式错误',
                qr_format_error_msg: '无法解析 QR Code 文字。\n请确认输入法、Caps Lock 与扫码枪键盘配置。',
                qr_missing_status: 'QR Code 缺少 usr 或 pwd。',
                qr_missing_title: 'QR Code 缺少帐号或密码',
                qr_missing_msg: '已读取 QR Code，但找不到 usr / pwd。\n请确认 QR Code 格式与扫码枪键盘设置。',
                qr_login_failed_title: 'QR Code 登录失败',
                qr_login_failed_msg_suffix: '\n请确认数据库帐号密码、输入法、Caps Lock 与扫码枪键盘配置。',
                qr_login_error_title: 'QR Code 登录错误',
                qr_login_error_msg_suffix: '\n请确认扫描内容后再试一次。',
                qr_form_not_found: '找不到登录表单。',
                login_failed: '登录失败。',
                login_error_USER_NOT_FOUND: '账号不存在。',
                login_error_PASSWORD_ERROR: '密码错误。',
                login_error_LOGIN_EXPIRED: '登录已逾时，请重新登录。',
                qr_account_not_found_suffix: '\n请确认 QR Code 账号是否存在于 iDAS 使用者清单。',
                qr_password_error_suffix: '\n请确认 QR Code 内的密码或 user table 密码。',
                login_response_not_json: '登录回应不是 JSON。',
                login_success_msg: '登录成功。',

                qr_hint_title: 'QR Code 登录提醒',
                qr_hint_step_1: '1. 请先将输入法切换为 <b>ENG / 英文</b>。',
                qr_hint_step_2: '2. 请确认 <b>Caps Lock 已关闭</b>。',
                qr_hint_step_3: '3. 请将 QR 扫码枪键盘配置设置为 <b>US English</b>。',
                qr_hint_step_4: '4. QR Code 扫描内容应为：<b>{"usr":"abcd123","pwd":"0734"}</b>',
                qr_hint_scanned_text: '扫描到的文字：',
                qr_hint_try_again: '重新扫描',
                qr_hint_close: '关闭'
            }
        };

        return (dict[lang] && dict[lang][key]) || (dict['en-us'] && dict['en-us'][key]) || key;
    }

    function applyQrLoginBlockI18n() {
        const btn = document.getElementById('qrLoginOpenBtn');
        const title = document.getElementById('qrLoginModalTitle');
        const statusText = document.getElementById('qrLoginStatusText');
        const statusBox = document.getElementById('qrLoginStatus');
        const input = document.getElementById('qrScannerInput');
        const keyboardNote = document.getElementById('qrKeyboardNote');
        const passwordToggleBtn = document.getElementById('loginPasswordToggleBtn');

        const hintTitle = document.getElementById('qrHintTitle');
        const hintStep1 = document.getElementById('qrHintStep1');
        const hintStep2 = document.getElementById('qrHintStep2');
        const hintStep3 = document.getElementById('qrHintStep3');
        const hintStep4 = document.getElementById('qrHintStep4');
        const hintPreviewTitle = document.getElementById('qrHintPreviewTitle');
        const hintTryAgainBtn = document.getElementById('qrHintTryAgainBtn');
        const hintCloseBtn = document.getElementById('qrHintCloseBtn');

        if (btn) btn.innerText = qrLoginText('qr_login_btn');
        if (title) title.innerText = qrLoginText('qr_modal_title');
        if (statusText && !statusText.dataset.keepDynamic) {
            statusText.innerText = qrLoginText('qr_status_scan');
        } else if (statusBox && !statusBox.dataset.keepDynamic) {
            statusBox.innerText = qrLoginText('qr_status_scan');
        }
        if (input) input.placeholder = qrLoginText('qr_placeholder');
        if (keyboardNote) keyboardNote.innerText = qrLoginText('qr_keyboard_note');
        if (passwordToggleBtn) {
            var passwordInput = document.getElementById('login_password');
            var showText = passwordToggleBtn.classList.contains('is-visible') ? qrLoginText('login_hide_password') : qrLoginText('login_show_password');
            passwordToggleBtn.setAttribute('aria-label', showText);
            passwordToggleBtn.setAttribute('title', showText);
        }

        if (hintTitle) hintTitle.innerText = qrLoginText('qr_hint_title');
        if (hintStep1) hintStep1.innerHTML = qrLoginText('qr_hint_step_1');
        if (hintStep2) hintStep2.innerHTML = qrLoginText('qr_hint_step_2');
        if (hintStep3) hintStep3.innerHTML = qrLoginText('qr_hint_step_3');
        if (hintStep4) hintStep4.innerHTML = qrLoginText('qr_hint_step_4');
        if (hintPreviewTitle) hintPreviewTitle.innerText = qrLoginText('qr_hint_scanned_text');
        if (hintTryAgainBtn) hintTryAgainBtn.innerText = qrLoginText('qr_hint_try_again');
        if (hintCloseBtn) hintCloseBtn.innerText = qrLoginText('qr_hint_close');
    }

    function language_change() {
        var language = event.target.id;
        $.ajax({
        type: "POST",
        url: "?url=Dashboards/change_language",
        data: {'language':language},
        dataType: "json",
        encode: true,
        async: false,
        }).done(function (data) {
            location.reload();
        });
    }

    $(document).ready(function () {
        hideQrLoginLoading();
        bindManualLoginSuccessDelay();
        applyQrLoginBlockI18n();
        <?php 
            if($data['error_message'] != ''){
                echo "showLoginPageError('", htmlspecialchars((string)$data['error_message'], ENT_QUOTES, 'UTF-8'), "')";
            }
        ?>
    });

    function openQrLoginModal() {
        const modal = document.getElementById('qrLoginModal');
        const input = document.getElementById('qrScannerInput');

        if (modal) modal.style.display = 'flex';
        applyQrLoginBlockI18n();
        setQrStatus(qrLoginText('qr_waiting_scan'));

        if (input) {
            input.value = '';
            setTimeout(function(){
                input.focus();
                input.select();
            }, 150);
        }
    }

    function closeQrLoginModal() {
        const modal = document.getElementById('qrLoginModal');
        if (modal) modal.style.display = 'none';
    }

    function setQrStatus(message) {
        const textEl = document.getElementById('qrLoginStatusText');
        const boxEl = document.getElementById('qrLoginStatus');
        if (textEl) {
            textEl.innerText = message;
        } else if (boxEl) {
            boxEl.innerText = message;
        }
        if (boxEl) boxEl.dataset.keepDynamic = '1';
    }

    function showQrLoginLoading(title, message, mode) {
        const overlay = document.getElementById('qrLoginLoadingOverlay');
        const titleEl = document.getElementById('qrLoginLoadingTitle');
        const textEl = document.getElementById('qrLoginLoadingText');

        const finalMode = mode || 'checking';

        if (overlay) {
            overlay.classList.remove('is-checking', 'is-success');
            overlay.classList.add(finalMode === 'success' ? 'is-success' : 'is-checking');
        }

        if (titleEl) {
            titleEl.innerText = title || qrLoginText('login_verify_title');
            titleEl.dataset.dynamicText = '1';
        }

        if (textEl) {
            if (message === '') {
                textEl.innerText = '';
                textEl.style.display = 'none';
            } else {
                textEl.innerText = message || qrLoginText('login_verify_text');
                textEl.style.display = 'block';
            }
            textEl.dataset.dynamicText = '1';
        }

        if (overlay) overlay.style.display = 'flex';
    }

    function hideQrLoginLoading() {
        const overlay = document.getElementById('qrLoginLoadingOverlay');
        if (overlay) overlay.style.display = 'none';
    }

    function setLoginButtonsDisabled(disabled) {
        const manualBtn = document.getElementById('manualLoginBtn');
        const qrBtn = document.getElementById('qrLoginOpenBtn');
        const langButtons = document.querySelectorAll('.buttonbox input');

        if (manualBtn) {
            if (disabled) {
                manualBtn.dataset.originalText = manualBtn.dataset.originalText || manualBtn.innerText;
                manualBtn.innerText = qrLoginText('login_submitting');
            } else if (manualBtn.dataset.originalText) {
                manualBtn.innerText = manualBtn.dataset.originalText;
            }
            manualBtn.disabled = !!disabled;
            manualBtn.classList.toggle('is-disabled', !!disabled);
        }

        if (qrBtn) {
            qrBtn.disabled = !!disabled;
            qrBtn.classList.toggle('is-disabled', !!disabled);
        }

        Array.prototype.forEach.call(langButtons, function(btn) {
            btn.disabled = !!disabled;
            btn.classList.toggle('is-disabled', !!disabled);
        });
    }

    function showLoginPageError(message) {
        const box = document.getElementById('loginPageError');
        if (!box) return;
        box.innerHTML = escapeHtml(message || qrLoginText('login_failed')).replace(/\n/g, '<br>');
        box.style.display = 'block';
    }

    function clearLoginPageError() {
        const box = document.getElementById('loginPageError');
        if (!box) return;
        box.innerHTML = '';
        box.style.display = 'none';
    }

    function getLoginErrorMessage(json) {
        const code = json && json.code ? String(json.code) : '';
        if (code) {
            const translated = qrLoginText('login_error_' + code);
            if (translated !== 'login_error_' + code) return translated;
        }
        return json && json.res_msg ? json.res_msg : qrLoginText('login_failed');
    }

    function getQrLoginFailureSuffix(json) {
        const code = json && json.code ? String(json.code) : '';
        if (code === 'USER_NOT_FOUND') return qrLoginText('qr_account_not_found_suffix');
        if (code === 'PASSWORD_ERROR') return qrLoginText('qr_password_error_suffix');
        return qrLoginText('qr_login_failed_msg_suffix');
    }


    function syncLoginPasswordMirrorFromPassword() {
        const passwordInput = document.getElementById('login_password');
        const plainInput = document.getElementById('login_password_plain');
        if (passwordInput && plainInput) {
            plainInput.value = passwordInput.value;
        }
    }

    function syncLoginPasswordMirrorFromPlain() {
        const passwordInput = document.getElementById('login_password');
        const plainInput = document.getElementById('login_password_plain');
        if (passwordInput && plainInput) {
            passwordInput.value = plainInput.value;
        }
    }

    function setLoginPasswordVisible(visible) {
        const passwordInput = document.getElementById('login_password');
        const plainInput = document.getElementById('login_password_plain');
        const btn = document.getElementById('loginPasswordToggleBtn');
        if (!passwordInput) return;

        if (plainInput) {
            if (visible) {
                plainInput.value = passwordInput.value;
                passwordInput.style.display = 'none';
                plainInput.style.display = 'block';
                plainInput.focus();
                try { plainInput.setSelectionRange(plainInput.value.length, plainInput.value.length); } catch (e) {}
            } else {
                passwordInput.value = plainInput.value;
                plainInput.style.display = 'none';
                passwordInput.style.display = 'block';
                passwordInput.focus();
                try { passwordInput.setSelectionRange(passwordInput.value.length, passwordInput.value.length); } catch (e) {}
            }
        } else {
            passwordInput.type = visible ? 'text' : 'password';
            passwordInput.style.webkitTextSecurity = visible ? 'none' : '';
            passwordInput.focus();
        }

        const label = visible ? qrLoginText('login_hide_password') : qrLoginText('login_show_password');
        if (btn) {
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
            btn.classList.toggle('is-visible', !!visible);
        }
    }

    function toggleLoginPasswordVisibility() {
        const btn = document.getElementById('loginPasswordToggleBtn');
        setLoginPasswordVisible(!(btn && btn.classList.contains('is-visible')));
    }

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    const LOGIN_COOKIE_PATHS = ['/', '/idas', '/idas/public'];
    const LOGIN_AUTH_COOKIE_NAMES = ['username', 'auth_token', 'user_law'];

    function clearLoginCookieByName(name) {
        if (!name) return;
        LOGIN_COOKIE_PATHS.forEach(function(path) {
            document.cookie = name + '=; path=' + path + '; max-age=0; SameSite=Lax';
            document.cookie = name + '=; path=' + path + '; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax';
        });
    }

    function clearStaleLoginCookies() {
        LOGIN_AUTH_COOKIE_NAMES.forEach(clearLoginCookieByName);
    }

    function setLoginCookieFromResponse(name, value, seconds) {
        if (!name || value === undefined || value === null || value === '') return;
        const maxAge = parseInt(seconds || 600, 10);
        clearLoginCookieByName(name);
        document.cookie = name + '=' + encodeURIComponent(String(value)) + '; path=/; max-age=' + maxAge + '; SameSite=Lax';
    }

    function applyLoginCookiesFromResponse(json) {
        if (!json || typeof json !== 'object') return;
        const seconds = json.cookie_seconds || 600;
        setLoginCookieFromResponse('username', json.username, seconds);
        setLoginCookieFromResponse('auth_token', json.auth_token, seconds);
        setLoginCookieFromResponse('user_law', json.user_law, seconds);
    }

    function showQrHintModal(title, message, scannedText) {
        const modal = document.getElementById('qrHintModal');
        const titleEl = document.getElementById('qrHintTitle');
        const messageEl = document.getElementById('qrHintMessage');
        const previewWrap = document.getElementById('qrHintPreviewWrap');
        const preview = document.getElementById('qrHintPreview');

        if (titleEl) titleEl.innerText = title || qrLoginText('qr_hint_title');
        if (messageEl) messageEl.innerHTML = escapeHtml(message || '').replace(/\n/g, '<br>');

        if (previewWrap && preview) {
            const scanned = String(scannedText || '').trim();
            if (scanned !== '') {
                preview.textContent = scanned;
                previewWrap.style.display = 'block';
            } else {
                preview.textContent = '';
                previewWrap.style.display = 'none';
            }
        }

        if (modal) modal.style.display = 'flex';
    }

    function closeQrHintModal(clearInput) {
        const modal = document.getElementById('qrHintModal');
        const input = document.getElementById('qrScannerInput');

        if (modal) modal.style.display = 'none';
        if (clearInput && input) input.value = '';
        if (input) {
            setTimeout(function(){ input.focus(); }, 80);
        }
    }

    function bindManualLoginSuccessDelay() {
        const form = document.getElementById('loginForm');
        if (!form || form.dataset.loginDelayBound === '1') return;

        form.dataset.loginDelayBound = '1';

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            clearLoginPageError();

            if (loginSubmitting) return;

            // 手動登入也改成 AJAX 驗證成功後，顯示 1 秒成功動畫再跳頁。
            submitLoginWithSuccessDelay(form, {
                isQr: false,
                verifyTitle: qrLoginText('login_verify_title'),
                verifyText: qrLoginText('login_verify_text')
            });
        });
    }

    function qrScannerKeydown(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            parseQrManualInput();
        }
    }

    function queueQrManualParse() {
        clearTimeout(qrInputTimer);
        qrInputTimer = setTimeout(function() {
            const input = document.getElementById('qrScannerInput');
            const value = input ? input.value.trim() : '';

            // 掃碼槍有些機型輸入較慢，延遲解析，避免資料尚未完整就判斷錯誤。
            if (value.indexOf('{') !== -1 || value.indexOf('usr') !== -1 || value.indexOf('pwd') !== -1) {
                parseQrManualInput();
            }
        }, 450);
    }

    function parseQrManualInput() {
        const input = document.getElementById('qrScannerInput');
        const value = input ? input.value.trim() : '';
        if (!value) {
            setQrStatus(qrLoginText('qr_empty'));
            if (input) input.focus();
            return;
        }
        handleQrLoginPayload(value);
    }

    function normalizeQrScannerText(text) {
        let payload = String(text || '');

        // 移除常見不可見控制字元，並處理部分掃碼槍/輸入法造成的全形符號。
        payload = payload
            .replace(/^\uFEFF/, '')
            .replace(/[\u0000-\u001F\u007F]/g, '')
            .replace(/[“”＂]/g, '"')
            .replace(/[‘’]/g, "'")
            .replace(/｛/g, '{')
            .replace(/｝/g, '}')
            .replace(/：/g, ':')
            .replace(/，/g, ',')
            .trim();

        // 若掃碼槍輸出 URL encoded，例如 %7B%22usr%22...
        if (payload.indexOf('%7B') !== -1 || payload.indexOf('%22') !== -1) {
            try {
                payload = decodeURIComponent(payload);
            } catch (e) {
                // decode 失敗就保留原文字
            }
        }

        return payload.trim();
    }

    function extractJsonFromQrText(text) {
        let payload = normalizeQrScannerText(text);

        const start = payload.indexOf('{');
        const end = payload.lastIndexOf('}');

        if (start !== -1 && end !== -1 && end >= start) {
            payload = payload.substring(start, end + 1);
        }

        // 標準 JSON：{"usr":"abcd123","pwd":"0734"}
        try {
            return JSON.parse(payload);
        } catch (e) {
            // 兼容單引號 JSON：{'usr':'abcd123','pwd':'0734'}
            const singleQuoteJson = payload.replace(/'/g, '"');
            try {
                return JSON.parse(singleQuoteJson);
            } catch (e2) {
                // 兼容掃碼槍輸出成 usr=abcd123,pwd=0734 或 usr:abcd123,pwd:0734
                const usrMatch = payload.match(/(?:usr|username|user)\s*[:=]\s*["']?([^"',;\s}]+)["']?/i);
                const pwdMatch = payload.match(/(?:pwd|password|pass)\s*[:=]\s*["']?([^"',;\s}]+)["']?/i);

                if (usrMatch && pwdMatch) {
                    return {
                        usr: usrMatch[1],
                        pwd: pwdMatch[1]
                    };
                }

                throw e;
            }
        }
    }

    function getQrField(data, keys) {
        if (!data || typeof data !== 'object') return '';

        for (let i = 0; i < keys.length; i++) {
            const key = keys[i];
            if (data[key] !== undefined && data[key] !== null) {
                return String(data[key]).trim();
            }
        }

        // 支援 QR key 大小寫不同，例如 USR/PWD、User/Pass。
        const lowerMap = {};
        Object.keys(data).forEach(function(k) {
            lowerMap[String(k).toLowerCase()] = data[k];
        });

        for (let i = 0; i < keys.length; i++) {
            const lowerKey = String(keys[i]).toLowerCase();
            if (lowerMap[lowerKey] !== undefined && lowerMap[lowerKey] !== null) {
                return String(lowerMap[lowerKey]).trim();
            }
        }

        return '';
    }

    function handleQrLoginPayload(rawText) {
        let data;
        try {
            data = extractJsonFromQrText(rawText);
        } catch (e) {
            setQrStatus(qrLoginText('qr_format_error_status'));
            showQrHintModal(
                qrLoginText('qr_format_error_title'),
                qrLoginText('qr_format_error_msg'),
                rawText
            );
            refocusQrInput(false);
            return;
        }

        const username = getQrField(data, ['usr', 'username', 'user', 'name', 'account']);
        const password = getQrField(data, ['pwd', 'password', 'pass']);

        if (!username || !password) {
            setQrStatus(qrLoginText('qr_missing_status'));
            showQrHintModal(
                qrLoginText('qr_missing_title'),
                qrLoginText('qr_missing_msg'),
                rawText
            );
            refocusQrInput(false);
            return;
        }

        fillLoginFormAndSubmit(username, password, rawText);
    }

    function refocusQrInput(clearValue) {
        const input = document.getElementById('qrScannerInput');
        if (input) {
            if (clearValue !== false) {
                input.value = '';
            }
            setTimeout(function(){ input.focus(); }, 80);
        }
    }

    function fillLoginFormAndSubmit(username, password, rawPayload) {
        const form = document.getElementById('loginForm');
        const usernameEl = document.getElementById('username');
        const passwordEl = document.getElementById('login_password');
        const qrPayloadEl = document.getElementById('qr_payload');

        if (!form || !usernameEl || !passwordEl) {
            setQrStatus(qrLoginText('qr_form_not_found'));
            return;
        }

        // 如果 QR 帳號大小寫和 DB 不同，例如 ABCD123 vs abcd123，
        // 優先使用下拉選單中既有帳號值，避免後端查不到帳號。
        let optionExists = false;
        let finalUsername = username;
        Array.prototype.forEach.call(usernameEl.options, function(opt) {
            if (String(opt.value).toLowerCase() === String(username).toLowerCase()) {
                optionExists = true;
                finalUsername = opt.value;
            }
        });

        if (!optionExists) {
            const option = new Option(username, username, true, true);
            usernameEl.add(option);
            finalUsername = username;
        }

        usernameEl.value = finalUsername;
        passwordEl.value = password;
        if (typeof syncLoginPasswordMirrorFromPassword === 'function') syncLoginPasswordMirrorFromPassword();
        if (qrPayloadEl) qrPayloadEl.value = rawPayload || JSON.stringify({usr: finalUsername, pwd: password});

        submitLoginWithSuccessDelay(form, {
            isQr: true,
            verifyTitle: qrLoginText('qr_verify_title'),
            verifyText: qrLoginText('qr_verify_text')
        });
    }

    async function submitLoginWithSuccessDelay(form, options) {
        if (loginSubmitting) return;
        loginSubmitting = true;
        setLoginButtonsDisabled(true);
        clearLoginPageError();

        options = options || {};
        const isQrLogin = options.isQr === true;
        const actionUrl = form.getAttribute('action') || '?url=Logins';
        const fetchUrl = actionUrl + (actionUrl.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
        if (typeof syncLoginPasswordMirrorFromPlain === 'function') syncLoginPasswordMirrorFromPlain();
        const formData = new FormData(form);

        // 讓 Logins.php 回傳 JSON，而不是立即 redirect。
        formData.set('ajax_login', '1');
        formData.set('force_json', '1');
        if (isQrLogin) {
            formData.append('qr_ajax_login', '1');
            setQrStatus(qrLoginText('qr_scan_success_checking'));
        } else {
            const qrPayloadEl = document.getElementById('qr_payload');
            if (qrPayloadEl) qrPayloadEl.value = '';
            formData.set('qr_payload', '');
        }

        showQrLoginLoading(
            options.verifyTitle || qrLoginText('login_verify_title'),
            options.verifyText || qrLoginText('login_verify_text'),
            'checking'
        );

        // 非無痕模式容易殘留舊 path 的登入 Cookie；送出前先清掉，成功後再由後端與前端備援重寫。
        clearStaleLoginCookies();

        try {
            const res = await fetch(fetchUrl, {
                method: 'POST',
                body: formData,
                cache: 'no-store',
                credentials: 'same-origin',
                redirect: 'follow',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json, text/plain, */*'
                }
            });

            const responseText = await res.text();
            let json;
            try {
                json = JSON.parse(responseText);
            } catch (e) {
                // 兼容舊版 Logins.php：成功後直接 redirect 到 Dashboards，fetch 會拿到 HTML。
                // 這種情況視為登入成功，仍保留 1 秒動畫後跳頁。
                const finalUrl = String(res.url || '');
                const looksLikeDashboard =
                    res.redirected === true ||
                    finalUrl.indexOf('url=Dashboards') !== -1 ||
                    finalUrl.indexOf('/Dashboards') !== -1 ||
                    responseText.indexOf('url=Dashboards') !== -1 ||
                    responseText.indexOf('Dashboard') !== -1 ||
                    responseText.indexOf('Dashboards') !== -1;

                if (looksLikeDashboard) {
                    json = {
                        success: true,
                        res_type: 'Success',
                        res_msg: qrLoginText('login_success_msg'),
                        redirect_url: finalUrl.indexOf('url=Dashboards') !== -1 ? finalUrl : '/idas/public/?url=Dashboards'
                    };
                } else {
                    console.error('[LOGIN] Non JSON response:', responseText.substring(0, 800));
                    throw new Error(qrLoginText('login_response_not_json'));
                }
            }

            const loginSuccess =
                json &&
                (
                    json.success === true ||
                    String(json.status || '').toLowerCase() === 'success' ||
                    String(json.res_type || '').toLowerCase() === 'success' ||
                    String(json.res_type || '').toLowerCase() === 'ok'
                );

            if (!loginSuccess) {
                loginSubmitting = false;
                setLoginButtonsDisabled(false);
                hideQrLoginLoading();

                const msg = getLoginErrorMessage(json);
                if (isQrLogin) {
                    setQrStatus(msg);
                    showQrHintModal(
                        qrLoginText('qr_login_failed_title'),
                        msg + getQrLoginFailureSuffix(json),
                        document.getElementById('qr_payload') ? document.getElementById('qr_payload').value : ''
                    );
                    refocusQrInput();
                } else {
                    showLoginPageError(msg);
                }
                return;
            }

            applyLoginCookiesFromResponse(json);
            setQrStatus(qrLoginText('login_redirecting_status'));
            showQrLoginLoading(qrLoginText('login_success_title'), qrLoginText('login_success_redirect'), 'success');

            const redirectUrl = json.redirect_url || '/idas/public/?url=Dashboards';
            const redirectTarget = redirectUrl + (redirectUrl.indexOf('?') === -1 ? '?' : '&') + '_login=' + Date.now();
            setTimeout(function() {
                window.location.replace(redirectTarget);
            }, LOGIN_SUCCESS_REDIRECT_DELAY_MS);

        } catch (e) {
            loginSubmitting = false;
            setLoginButtonsDisabled(false);
            hideQrLoginLoading();

            const msg = e.message || qrLoginText('login_failed');
            if (isQrLogin) {
                setQrStatus(msg);
                showQrHintModal(
                    qrLoginText('qr_login_error_title'),
                    msg + qrLoginText('qr_login_error_msg_suffix'),
                    document.getElementById('qr_payload') ? document.getElementById('qr_payload').value : ''
                );
                refocusQrInput();
            } else {
                showLoginPageError(msg);
            }
        }
    }

    // 掃碼槍全頁支援：不用點 QR Code Login，只要掃到 JSON + Enter 也會登入。
    // 為避免影響手動輸入，只有掃到 { ... } 格式才會觸發登入。
    document.addEventListener('keydown', function(e) {
        const modal = document.getElementById('qrLoginModal');
        const isModalOpen = modal && modal.style.display !== 'none';
        const targetId = e.target && e.target.id ? e.target.id : '';

        // Modal 內的 textarea 已有自己的 Enter 處理，避免重複送出。
        if (targetId === 'qrScannerInput') return;

        const now = Date.now();
        if (now - qrGlobalLastTime > 120) {
            qrGlobalBuffer = '';
        }
        qrGlobalLastTime = now;

        if (e.key === 'Enter') {
            const text = qrGlobalBuffer.trim();
            qrGlobalBuffer = '';

            if (text.indexOf('{') !== -1 && text.indexOf('}') !== -1) {
                e.preventDefault();
                handleQrLoginPayload(text);
            } else if (isModalOpen) {
                e.preventDefault();
                parseQrManualInput();
            }
            return;
        }

        if (e.key && e.key.length === 1) {
            qrGlobalBuffer += e.key;
            if (qrGlobalBuffer.length > 500) {
                qrGlobalBuffer = qrGlobalBuffer.slice(-500);
            }
        }
    }, true);
</script>
<style>

html, body
{
    margin: 0;
    padding: 0px;
    background-color: #000000;
    background-image: url(./img/vn.jpg);
    background-size: cover;
    background-position: center;
}

.container-ms
{
    width: 100%;
    margin: 0 auto;
    padding: 20px;
}

.center-content
{
    display: flex;
    justify-content: center;
    align-items: center;
}

    /* Button Language */
.buttonbox input
{
    margin-bottom: 5px;
}

.buttonbox input
{
    border: none;
    outline: none;
    width:90px;
    height: 40px;
    background: #888888;
    color: #fff;
    font-size: 18px;
    border: 1px outset #666;
    border-radius: 5px;
    text-align:center;
}
.buttonbox input:hover
{
    cursor: pointer;
    background:#333300;
    color: white;
}

.buttonbox input:active
{
    background-color: #DDDDDD;
    box-shadow: 0 5px #666;
    transform: translateY(4px);
}

/* Login */
:focus { outline: none; }
::-webkit-input-placeholder { color: #DEDFDF; }
::-moz-placeholder { color: #DEDFDF; }
:-moz-placeholder { color: #DEDFDF; }
::-ms-input-placeholder { color: #DEDFDF; }

form
{
    float: center;
    max-width: 600px;
    height: 470px;
    margin: 0 auto;
    padding: 10vmin 15vmin;

}

input[type=radio] { display: none; }

input[type=text],
input[type=password]
{
    background: #fff;
    border: none;
    border-radius: 8px;
    font-size: 25px;
    font-family: 'Raleway', sans-serif;
    height: 72px;
    width: 100%;
    margin-bottom: 10px;
    opacity: 1;
    text-indent: 20px;
    transition: all .2s ease-in-out;
}

.login-password-wrap {
    position: relative;
    width: 100%;
}

.login-password-wrap input[type=password],
.login-password-wrap input[type=text],
.login-password-wrap .login-password-plain {
    padding-right: 68px;
    box-sizing: border-box;
}

.login-password-wrap .login-password-plain {
    background: #fff;
    border: none;
    border-radius: 8px;
    font-size: 25px;
    font-family: 'Raleway', sans-serif;
    height: 72px;
    width: 100%;
    margin-bottom: 10px;
    opacity: 1;
    text-indent: 20px;
    transition: all .2s ease-in-out;
}

.login-password-toggle {
    display: none !important;
}

.login-password-toggle {
    position: absolute;
    top: 0;
    right: 0;
    width: 64px;
    height: 72px;
    line-height: 72px;
    margin: 0;
    border-radius: 0 8px 8px 0;
    background: transparent;
    color: #333;
    font-size: 0;
    border-left: 1px solid #e1e1e1;
    display: flex;
    align-items: center;
    justify-content: center;
}

.login-password-toggle:hover {
    background: rgba(0, 0, 0, 0.08);
}

.login-password-eye {
    font-size: 26px;
    line-height: 1;
}

.login-password-toggle.is-visible::after {
    content: "";
    position: absolute;
    width: 30px;
    height: 3px;
    border-radius: 3px;
    background: #333;
    transform: rotate(-45deg);
}

.login-page-error {
    width: 100%;
    box-sizing: border-box;
    margin-top: 10px;
    padding: 12px 14px;
    border-radius: 8px;
    background: rgba(190, 30, 45, 0.92);
    color: #fff;
    font-size: 18px;
    font-weight: 800;
    line-height: 1.45;
    text-align: center;
    box-shadow: 0 6px 18px rgba(0, 0, 0, .25);
}

button
{
    background: #079BCF;
    border: none;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    font-family: 'Raleway', sans-serif;
    font-size: 30px;
    height: 72px;
    width: 100%;
    margin-bottom: 10px;
    overflow: hidden;
    transition: all .3s cubic-bezier(.6,0,.4,1);
}

button
{
    display: block;
    line-height: 72px;
    position: relative;
    top: 0px;
    transform: translate3d(0,0,0);
}

button:hover
{
    background: #007BA5;
}

button:disabled,
button.is-disabled,
.buttonbox input:disabled,
.buttonbox input.is-disabled {
    cursor: not-allowed !important;
    opacity: .62;
    transform: none !important;
    box-shadow: none !important;
}

.qr-login-btn {
    background: #4a545d;
    border: 1px solid rgba(255,255,255,.25);
    font-size: 26px;
}

.qr-login-btn:hover {
    background: #2e3942;
}

.custom-select {
    background: #fff;
    border: none;
    border-radius: 8px;
    font-size: 25px;
    font-family: 'Raleway', sans-serif;
    height: 72px;
    width: 100%;
    margin-bottom: 10px;
    opacity: 1;
    text-indent: 20px;
    transition: all .2s ease-in-out;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    position: relative;
    padding-right: 30px; /* Space for the custom arrow */
}

.custom-select::after {
    content: '▼'; /* Custom arrow (you can change this symbol to anything you like) */
    font-size: 20px;
    color: #333; /* Adjust color */
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none; /* Makes the arrow non-interactive */
}

.qr-modal {
    position: fixed;
    z-index: 9999;
    inset: 0;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, .68);
}

.qr-modal-card {
    width: min(680px, 92vw);
    background: #f4f4f4;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 10px 35px rgba(0,0,0,.45);
}

.qr-modal-header {
    height: 58px;
    padding: 0 16px 0 22px;
    background: #5f5f5f;
    color: #fff;
    font-size: 26px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.qr-close-btn {
    width: 54px;
    height: 54px;
    line-height: 54px;
    margin: 0;
    border-radius: 0;
    background: #f44336;
    font-size: 42px;
    padding: 0;
}

.qr-modal-body {
    padding: 18px 22px 22px;
}

.qr-status {
    min-height: 38px;
    margin-bottom: 12px;
    padding: 8px 12px;
    border-radius: 8px;
    background: #fff8dc;
    color: #333;
    font-size: 17px;
    font-weight: 700;
}

.qr-status::before {
    content: "";
    display: inline-block;
    width: 10px;
    height: 10px;
    margin-right: 8px;
    border-radius: 50%;
    background: #22aa55;
    box-shadow: 0 0 0 0 rgba(34,170,85,.55);
    animation: qrWaitingPulse 1.2s infinite;
}

.qr-scanner-input {
    width: 100%;
    min-height: 108px;
    box-sizing: border-box;
    margin-bottom: 12px;
    padding: 14px;
    border: 3px solid #079BCF;
    border-radius: 8px;
    font-size: 20px;
    resize: none;
}

.qr-scanner-input:focus {
    box-shadow: 0 0 0 4px rgba(7,155,207,.18);
}

.qr-keyboard-note {
    margin-top: 10px;
    padding: 12px 14px;
    border-radius: 8px;
    background: #fff8dc;
    color: #3a2a10;
    border: 1px solid #ead8a3;
    font-size: 17px;
    font-weight: 800;
    line-height: 1.45;
}

.qr-keyboard-note::before {
    content: "⚠ ";
    font-weight: 900;
}

.qr-help-text {
    color: #444;
    font-size: 17px;
    font-weight: 700;
    text-align: center;
}



.qr-hint-modal {
    position: fixed;
    z-index: 10020;
    inset: 0;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, .72);
}

.qr-hint-card {
    width: min(720px, 92vw);
    background: #f4f4f4;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 12px 38px rgba(0,0,0,.48);
}

.qr-hint-header {
    height: 58px;
    padding: 0 16px 0 22px;
    background: #5f5f5f;
    color: #fff;
    font-size: 26px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.qr-hint-close-btn {
    width: 54px;
    height: 54px;
    line-height: 54px;
    margin: 0;
    border-radius: 0;
    background: #f44336;
    font-size: 42px;
    padding: 0;
}

.qr-hint-body {
    padding: 18px 22px 22px;
}

.qr-hint-message {
    padding: 12px 14px;
    border-radius: 8px;
    background: #fff8dc;
    color: #333;
    font-size: 18px;
    font-weight: 800;
    line-height: 1.45;
    margin-bottom: 12px;
}

.qr-hint-checklist {
    padding: 12px 14px;
    border-radius: 8px;
    background: #fff;
    color: #222;
    font-size: 17px;
    font-weight: 700;
    line-height: 1.8;
    border: 1px solid #ddd;
}

.qr-hint-preview-wrap {
    margin-top: 12px;
}

.qr-hint-preview-title {
    color: #333;
    font-size: 16px;
    font-weight: 800;
    margin-bottom: 6px;
}

.qr-hint-preview {
    min-height: 54px;
    max-height: 130px;
    overflow: auto;
    padding: 10px 12px;
    border-radius: 8px;
    background: #20242a;
    color: #fff;
    font-size: 16px;
    white-space: pre-wrap;
    word-break: break-all;
}

.qr-hint-actions {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-top: 18px;
}

.qr-hint-actions button {
    width: 160px;
    height: 52px;
    line-height: 52px;
    margin: 0;
    font-size: 20px;
    font-weight: 800;
}

.qr-hint-primary {
    background: #079BCF;
}

.qr-hint-secondary {
    background: #6f7c86;
}

.qr-login-loading-overlay {
    position: fixed;
    inset: 0;
    z-index: 10050;
    display: none;
    align-items: center;
    justify-content: center;
    background:
        radial-gradient(circle at 20% 20%, rgba(7,155,207,.34), transparent 32%),
        radial-gradient(circle at 80% 18%, rgba(68,184,137,.28), transparent 28%),
        radial-gradient(circle at 50% 86%, rgba(255,186,73,.18), transparent 34%),
        rgba(8, 13, 24, .86);
    backdrop-filter: blur(6px);
    overflow: hidden;
}

.qr-login-ambient {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
}

.qr-login-ambient span {
    position: absolute;
    width: 160px;
    height: 160px;
    border-radius: 999px;
    background: rgba(255,255,255,.10);
    filter: blur(2px);
    animation: qrLoginAmbientFloat 8s ease-in-out infinite;
}

.qr-login-ambient span:nth-child(1) { left: 10%; top: 18%; }
.qr-login-ambient span:nth-child(2) { right: 12%; top: 24%; width: 110px; height: 110px; animation-delay: -2.2s; }
.qr-login-ambient span:nth-child(3) { left: 22%; bottom: 12%; width: 120px; height: 120px; animation-delay: -4.4s; }
.qr-login-ambient span:nth-child(4) { right: 22%; bottom: 18%; width: 90px; height: 90px; animation-delay: -6.2s; }

.qr-login-loading-card {
    position: relative;
    width: min(500px, 90vw);
    padding: 38px 30px 30px;
    border-radius: 26px;
    background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(232,241,249,.94));
    border: 1px solid rgba(255,255,255,.72);
    box-shadow: 0 26px 72px rgba(0,0,0,.46), inset 0 1px 0 rgba(255,255,255,.85);
    text-align: center;
    overflow: hidden;
    transform: translateY(0) scale(1);
    animation: qrLoginCardIn .34s ease-out both;
}

.qr-login-card-glow {
    position: absolute;
    inset: -50%;
    background:
        conic-gradient(from 0deg,
            transparent 0deg,
            rgba(7,155,207,.22) 70deg,
            transparent 120deg,
            rgba(68,184,137,.20) 210deg,
            transparent 280deg,
            rgba(255,186,73,.18) 330deg,
            transparent 360deg);
    animation: qrLoginCardGlow 5.5s linear infinite;
    pointer-events: none;
}

.qr-login-scanner-line {
    position: absolute;
    left: -25%;
    top: 0;
    width: 150%;
    height: 3px;
    background: linear-gradient(90deg, transparent, rgba(7,155,207,.88), rgba(68,184,137,.8), transparent);
    box-shadow: 0 0 18px rgba(7,155,207,.85);
    animation: qrLoginScannerLine 2.1s ease-in-out infinite;
    pointer-events: none;
}

.qr-login-spinner-wrap {
    position: relative;
    width: 136px;
    height: 136px;
    margin: 0 auto 20px;
    z-index: 1;
}

.qr-login-spinner-ring {
    position: absolute;
    inset: 8px;
    border-radius: 50%;
    border: 8px solid rgba(7,155,207,.14);
    border-top-color: #079BCF;
    border-right-color: #44b889;
    filter: drop-shadow(0 0 10px rgba(7,155,207,.28));
    animation: qrLoginSpin .95s linear infinite;
}

.qr-login-orbit {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 1px dashed rgba(7,155,207,.28);
    animation: qrLoginOrbit 2.8s linear infinite;
}

.qr-login-orbit span {
    position: absolute;
    top: 8px;
    left: 50%;
    width: 12px;
    height: 12px;
    margin-left: -6px;
    border-radius: 50%;
    background: #079BCF;
    box-shadow: 0 0 12px rgba(7,155,207,.82);
}

.qr-login-orbit-2 {
    inset: 14px;
    animation-duration: 3.8s;
    animation-direction: reverse;
}

.qr-login-orbit-2 span {
    background: #44b889;
    box-shadow: 0 0 12px rgba(68,184,137,.82);
}

.qr-login-orbit-3 {
    inset: 26px;
    animation-duration: 2.4s;
}

.qr-login-orbit-3 span {
    background: #ffba49;
    box-shadow: 0 0 12px rgba(255,186,73,.82);
}

.qr-login-spinner-core {
    position: absolute;
    inset: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background:
        radial-gradient(circle at 30% 22%, rgba(255,255,255,.35), transparent 28%),
        linear-gradient(180deg, #0ba5d9, #078fbe);
    color: #fff;
    font-size: 48px;
    font-weight: 900;
    box-shadow: 0 10px 28px rgba(7,155,207,.35), inset 0 1px 0 rgba(255,255,255,.35);
    animation: qrLoginPulse 1.2s ease-in-out infinite;
}

.qr-login-loading-title {
    position: relative;
    z-index: 1;
    color: #203040;
    font-size: 32px;
    font-weight: 950;
    letter-spacing: .5px;
    margin-bottom: 8px;
    text-shadow: 0 1px 0 rgba(255,255,255,.9);
}

.qr-login-loading-subtitle {
    position: relative;
    z-index: 1;
    color: #526272;
    font-size: 18px;
    font-weight: 800;
}

.qr-login-progress {
    position: relative;
    z-index: 1;
    width: 72%;
    height: 8px;
    margin: 20px auto 0;
    border-radius: 999px;
    background: rgba(40, 66, 86, .12);
    overflow: hidden;
}

.qr-login-progress span {
    display: block;
    width: 42%;
    height: 100%;
    border-radius: 999px;
    background: linear-gradient(90deg, #079BCF, #44b889, #ffba49);
    box-shadow: 0 0 14px rgba(7,155,207,.42);
    animation: qrLoginProgress 1.35s ease-in-out infinite;
}

.qr-login-loading-dots {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 18px;
}

.qr-login-loading-dots span {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #079BCF;
    animation: qrLoginDot 1.2s infinite ease-in-out;
}

.qr-login-loading-dots span:nth-child(2) { animation-delay: .15s; background: #44b889; }
.qr-login-loading-dots span:nth-child(3) { animation-delay: .30s; background: #ffba49; }

.qr-login-loading-overlay.is-success .qr-login-spinner-ring,
.qr-login-loading-overlay.is-success .qr-login-orbit {
    animation-play-state: paused;
    opacity: .26;
}

.qr-login-loading-overlay.is-success .qr-login-spinner-core {
    background:
        radial-gradient(circle at 30% 22%, rgba(255,255,255,.42), transparent 30%),
        linear-gradient(180deg, #44b889, #1b9f67);
    animation: qrLoginSuccessPop .58s cubic-bezier(.2,1.6,.3,1) both;
}

.qr-login-loading-overlay.is-success .qr-login-spinner-core::after {
    content: '';
    position: absolute;
    inset: -18px;
    border-radius: 50%;
    border: 3px solid rgba(68,184,137,.50);
    animation: qrLoginSuccessRipple .85s ease-out both;
}

.qr-login-loading-overlay.is-success .qr-login-progress span {
    width: 100%;
    animation: none;
    background: linear-gradient(90deg, #44b889, #1b9f67);
}

.qr-login-loading-overlay.is-success .qr-login-loading-title {
    color: #176942;
}

.qr-login-loading-overlay.is-success .qr-login-loading-dots {
    display: none;
}

@keyframes qrLoginSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes qrLoginOrbit {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes qrLoginPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.065); }
}

@keyframes qrLoginDot {
    0%, 80%, 100% { transform: translateY(0); opacity: .45; }
    40% { transform: translateY(-7px); opacity: 1; }
}

@keyframes qrLoginProgress {
    0% { transform: translateX(-115%); }
    55% { transform: translateX(95%); }
    100% { transform: translateX(210%); }
}

@keyframes qrLoginScannerLine {
    0% { transform: translateY(0); opacity: .15; }
    45% { opacity: 1; }
    100% { transform: translateY(270px); opacity: .15; }
}

@keyframes qrLoginCardGlow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes qrLoginAmbientFloat {
    0%, 100% { transform: translate3d(0, 0, 0) scale(1); opacity: .45; }
    50% { transform: translate3d(18px, -22px, 0) scale(1.12); opacity: .85; }
}

@keyframes qrLoginCardIn {
    from { opacity: 0; transform: translateY(14px) scale(.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes qrWaitingPulse {
    0% { box-shadow: 0 0 0 0 rgba(34,170,85,.55); }
    70% { box-shadow: 0 0 0 9px rgba(34,170,85,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,170,85,0); }
}

@keyframes qrLoginSuccessPop {
    0% { transform: scale(.65); opacity: .2; }
    60% { transform: scale(1.16); opacity: 1; }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes qrLoginSuccessRipple {
    from { transform: scale(.65); opacity: .75; }
    to { transform: scale(1.7); opacity: 0; }
}

@media (max-width: 900px) {
    form {
        padding: 8vmin;
        height: auto;
    }

    .qr-modal-header {
        font-size: 22px;
    }
}

</style>
<?php endif; ?>
