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
        <input id="login_password" type="password" name="password" placeholder="<?php echo $text['password_text']; ?>" required>
        <input id="qr_payload" type="hidden" name="qr_payload" value="">
        <button type="submit"><?php echo $text['login_text']; ?></button>
        <button type="button" id="qrLoginOpenBtn" class="qr-login-btn" onclick="openQrLoginModal()">QR Code Login</button>
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
                    <div>1. Please switch the input method to <b>ENG / English</b>.</div>
                    <div>2. Please turn <b>Caps Lock OFF</b>.</div>
                    <div>3. Please set the QR scanner keyboard layout to <b>US English</b>.</div>
                    <div>4. The QR Code should scan as: <b>{"usr":"abcd123","pwd":"0734"}</b></div>
                </div>
                <div class="qr-hint-preview-wrap" id="qrHintPreviewWrap" style="display:none;">
                    <div class="qr-hint-preview-title">Scanned text:</div>
                    <pre id="qrHintPreview"></pre>
                </div>
                <div class="qr-hint-actions">
                    <button type="button" class="qr-hint-primary" onclick="closeQrHintModal(true)">Try Again</button>
                    <button type="button" class="qr-hint-secondary" onclick="closeQrHintModal(false)">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div id="qrLoginLoadingOverlay" class="qr-login-loading-overlay" style="display:none;">
        <div class="qr-login-loading-card">
            <div class="qr-login-spinner-wrap">
                <div class="qr-login-spinner-ring"></div>
                <div class="qr-login-spinner-core">✓</div>
            </div>
            <div class="qr-login-loading-title" id="qrLoginLoadingTitle">Verifying QR Login</div>
            <div class="qr-login-loading-subtitle" id="qrLoginLoadingText">Checking account and password...</div>
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
                qr_placeholder: 'Place the cursor here and scan the QR Code'
            },
            'zh-tw': {
                qr_login_btn: 'QR Code 登入',
                qr_modal_title: 'QR Code 登入',
                qr_status_scan: '請掃描 QR Code',
                qr_placeholder: '請將游標停在這裡後掃描 QR Code'
            },
            'zh-cn': {
                qr_login_btn: 'QR Code 登录',
                qr_modal_title: 'QR Code 登录',
                qr_status_scan: '请扫描 QR Code',
                qr_placeholder: '请将光标停在这里后扫描 QR Code'
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

        if (btn) btn.innerText = qrLoginText('qr_login_btn');
        if (title) title.innerText = qrLoginText('qr_modal_title');
        if (statusText) {
            statusText.innerText = qrLoginText('qr_status_scan');
        } else if (statusBox && !statusBox.dataset.keepDynamic) {
            statusBox.innerText = qrLoginText('qr_status_scan');
        }
        if (input) input.placeholder = qrLoginText('qr_placeholder');
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
                echo "alert('",$data['error_message'],"')";
            }
        ?>
    });

    function openQrLoginModal() {
        const modal = document.getElementById('qrLoginModal');
        const input = document.getElementById('qrScannerInput');

        if (modal) modal.style.display = 'flex';
        applyQrLoginBlockI18n();
        setQrStatus(qrLoginText('qr_status_scan'));

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

    function showQrLoginLoading(title, message) {
        const overlay = document.getElementById('qrLoginLoadingOverlay');
        const titleEl = document.getElementById('qrLoginLoadingTitle');
        const textEl = document.getElementById('qrLoginLoadingText');

        if (titleEl) titleEl.innerText = title || 'Verifying QR Login';
        if (textEl) textEl.innerText = message || 'Checking account and password...';
        if (overlay) overlay.style.display = 'flex';
    }

    function hideQrLoginLoading() {
        const overlay = document.getElementById('qrLoginLoadingOverlay');
        if (overlay) overlay.style.display = 'none';
    }

    function escapeHtml(text) {
        return String(text || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showQrHintModal(title, message, scannedText) {
        const modal = document.getElementById('qrHintModal');
        const titleEl = document.getElementById('qrHintTitle');
        const messageEl = document.getElementById('qrHintMessage');
        const previewWrap = document.getElementById('qrHintPreviewWrap');
        const preview = document.getElementById('qrHintPreview');

        if (titleEl) titleEl.innerText = title || 'QR Login Notice';
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

            if (loginSubmitting) return;

            // 手動登入也改成 AJAX 驗證成功後，顯示 3 秒成功動畫再跳頁。
            submitLoginWithSuccessDelay(form, {
                isQr: false,
                verifyTitle: 'Verifying Login',
                verifyText: 'Checking account and password...'
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
            setQrStatus('QR text is empty.');
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
            setQrStatus('QR format error.');
            showQrHintModal(
                'QR Format Error',
                'The QR Code text could not be parsed.\nPlease check the keyboard input method, Caps Lock, and scanner keyboard layout.',
                rawText
            );
            refocusQrInput(false);
            return;
        }

        const username = getQrField(data, ['usr', 'username', 'user', 'name', 'account']);
        const password = getQrField(data, ['pwd', 'password', 'pass']);

        if (!username || !password) {
            setQrStatus('QR missing usr or pwd.');
            showQrHintModal(
                'QR Missing Account or Password',
                'The QR Code was read, but usr / pwd was not found.\nPlease confirm the QR format and scanner keyboard setting.',
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
            setQrStatus('Login form not found.');
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
        if (qrPayloadEl) qrPayloadEl.value = rawPayload || JSON.stringify({usr: finalUsername, pwd: password});

        submitLoginWithSuccessDelay(form, {
            isQr: true,
            verifyTitle: 'Verifying QR Login',
            verifyText: 'Checking account and password...'
        });
    }

    async function submitLoginWithSuccessDelay(form, options) {
        if (loginSubmitting) return;
        loginSubmitting = true;

        options = options || {};
        const isQrLogin = options.isQr === true;
        const actionUrl = form.getAttribute('action') || '?url=Logins';
        const fetchUrl = actionUrl + (actionUrl.indexOf('?') === -1 ? '?' : '&') + 't=' + Date.now();
        const formData = new FormData(form);

        // 讓 Logins.php 回傳 JSON，而不是立即 redirect。
        formData.set('ajax_login', '1');
        formData.set('force_json', '1');
        if (isQrLogin) {
            formData.append('qr_ajax_login', '1');
            setQrStatus('QR scan successful. Checking account...');
        } else {
            const qrPayloadEl = document.getElementById('qr_payload');
            if (qrPayloadEl) qrPayloadEl.value = '';
            formData.set('qr_payload', '');
        }

        showQrLoginLoading(
            options.verifyTitle || 'Verifying Login',
            options.verifyText || 'Checking account and password...'
        );

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
                // 這種情況視為登入成功，仍保留 3 秒動畫後跳頁。
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
                        res_msg: 'Login success.',
                        redirect_url: finalUrl.indexOf('url=Dashboards') !== -1 ? finalUrl : '/idas/public/?url=Dashboards'
                    };
                } else {
                    console.error('[LOGIN] Non JSON response:', responseText.substring(0, 800));
                    throw new Error('Login response is not JSON.');
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
                hideQrLoginLoading();

                const msg = json && json.res_msg ? json.res_msg : 'Login failed.';
                if (isQrLogin) {
                    setQrStatus(msg);
                    showQrHintModal(
                        'QR Login Failed',
                        msg + '\nPlease check the account/password in DB, keyboard input method, Caps Lock, and scanner keyboard layout.',
                        document.getElementById('qr_payload') ? document.getElementById('qr_payload').value : ''
                    );
                    refocusQrInput();
                } else {
                    alert(msg);
                }
                return;
            }

            setQrStatus('Login success. Redirecting...');
            showQrLoginLoading('Login Success', 'Redirecting in 3 seconds...');

            const redirectUrl = json.redirect_url || '/idas/public/?url=Dashboards';
            setTimeout(function() {
                window.location.href = redirectUrl;
            }, 3000);

        } catch (e) {
            loginSubmitting = false;
            hideQrLoginLoading();

            const msg = e.message || 'Login failed.';
            if (isQrLogin) {
                setQrStatus(msg);
                showQrHintModal(
                    'QR Login Error',
                    msg + '\nPlease check the scanner input and try again.',
                    document.getElementById('qr_payload') ? document.getElementById('qr_payload').value : ''
                );
                refocusQrInput();
            } else {
                alert(msg);
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
    align-items: center;
    justify-content: center;
    background: rgba(10, 18, 30, .78);
    backdrop-filter: blur(2px);
}

.qr-login-loading-card {
    width: min(460px, 90vw);
    padding: 34px 28px 28px;
    border-radius: 20px;
    background: linear-gradient(180deg, rgba(255,255,255,.96), rgba(240,245,250,.96));
    box-shadow: 0 18px 50px rgba(0,0,0,.35);
    text-align: center;
}

.qr-login-spinner-wrap {
    position: relative;
    width: 118px;
    height: 118px;
    margin: 0 auto 18px;
}

.qr-login-spinner-ring {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    border: 8px solid rgba(7,155,207,.16);
    border-top-color: #079BCF;
    border-right-color: #44b889;
    animation: qrLoginSpin 1.1s linear infinite;
}

.qr-login-spinner-core {
    position: absolute;
    inset: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: linear-gradient(180deg, #0ba5d9, #078fbe);
    color: #fff;
    font-size: 44px;
    font-weight: 900;
    box-shadow: 0 8px 20px rgba(7,155,207,.28);
    animation: qrLoginPulse 1.25s ease-in-out infinite;
}

.qr-login-loading-title {
    color: #243240;
    font-size: 30px;
    font-weight: 900;
    margin-bottom: 8px;
}

.qr-login-loading-subtitle {
    color: #4c5a67;
    font-size: 18px;
    font-weight: 700;
}

.qr-login-loading-dots {
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

.qr-login-loading-dots span:nth-child(2) { animation-delay: .15s; }
.qr-login-loading-dots span:nth-child(3) { animation-delay: .30s; }

@keyframes qrLoginSpin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

@keyframes qrLoginPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.06); }
}

@keyframes qrLoginDot {
    0%, 80%, 100% { transform: translateY(0); opacity: .45; }
    40% { transform: translateY(-6px); opacity: 1; }
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
