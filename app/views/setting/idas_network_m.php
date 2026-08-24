<?php
$networkSetting = isset($data['network_setting']) && is_array($data['network_setting'])
    ? $data['network_setting']
    : [];
$networkMode = isset($networkSetting['mode']) ? (int)$networkSetting['mode'] : 1;
$currentNetworkIp = trim((string)($networkSetting['current_ip'] ?? ''));
$staticNetworkIp = trim((string)($networkSetting['static_ip'] ?? ''));
$networkMask = trim((string)($networkSetting['mask'] ?? '255.255.255.0'));
$networkGateway = trim((string)($networkSetting['gateway'] ?? ''));
$networkPort = isset($networkSetting['port']) ? (int)$networkSetting['port'] : 502;

$controllerModbusType = isset($data['controller_info']['modbus_type'])
    ? (int)$data['controller_info']['modbus_type']
    : IDAS_PROTOCOL_TCP;

// Protocol / Port policy is centralized in app/config/config.php.
$networkPort = idas_protocol_server_port($controllerModbusType, $networkPort);
?>
<style>
/* Network Setting RWD — scoped to this page so desktop/controller layouts and
   the restart/DB workflow are not affected. */
#Network_Setting {
    box-sizing: border-box;
}
#Network_Setting .network-setting-title {
    padding-left: 3%;
    padding-top: 1%;
    font-weight: bold;
}
#Network_Setting .network-setting-row {
    align-items: center;
}
#Network_Setting .network-setting-input {
    min-height: 38px;
}
#Network_Setting .network-mode-options {
    display: flex;
    align-items: center;
    gap: 24px;
}
#Network_Setting .network-mode-options .form-check-inline {
    margin-right: 0;
}
#Network_Setting .network-setting-actions {
    text-align: center;
    margin: 30px 0 20px;
}

@media (max-width: 768px) {
    #Network_Setting {
        width: 100%;
        min-height: auto !important;
        padding: 14px 16px calc(24px + env(safe-area-inset-bottom));
        overflow-x: hidden !important;
    }
    #Network_Setting .network-setting-title {
        width: 100%;
        padding: 0 0 14px;
        font-size: 19px;
        line-height: 1.35;
    }
    #Network_Setting .network-setting-row {
        display: block;
        width: 100%;
        margin: 0 0 16px;
    }
    #Network_Setting .network-setting-row > .t1,
    #Network_Setting .network-setting-row > .t2 {
        width: 100%;
        max-width: none;
        flex: 0 0 100%;
        padding: 0;
    }
    #Network_Setting .network-setting-row > .t1 {
        margin-bottom: 7px;
        font-size: 15px;
        font-weight: 600;
        line-height: 1.35;
    }
    #Network_Setting .network-setting-input {
        display: block;
        width: 100% !important;
        max-width: none !important;
        min-height: 46px;
        padding: 10px 12px;
        border-radius: 6px;
        font-size: 16px; /* Prevents automatic zoom on iOS Safari. */
        box-sizing: border-box;
    }
    #Network_Setting .network-mode-options {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px;
    }
    #Network_Setting .network-mode-options .form-check-inline {
        position: relative;
        display: flex;
        align-items: center;
        min-width: 0;
        min-height: 48px;
        margin: 0;
        padding: 0 12px;
        border: 1px solid #cbd3da;
        border-radius: 7px;
        background: #fff;
    }
    #Network_Setting .network-mode-options .form-check-input {
        flex: 0 0 auto;
        width: 20px;
        height: 20px;
        margin: 0 9px 0 0;
    }
    #Network_Setting .network-mode-options .form-check-label {
        display: flex;
        align-items: center;
        min-height: 46px;
        width: 100%;
        margin: 0;
        font-size: 16px;
        cursor: pointer;
    }
    #Network_Setting .network-field-disabled,
    #Network_Setting input:disabled {
        opacity: 1;
        color: #70777d;
        background: #eef1f3;
        -webkit-text-fill-color: #70777d;
    }
    #Network_Setting .network-setting-actions {
        width: 100%;
        margin: 22px 0 0;
    }
    #Network_Setting #network_setting_save {
        width: 100%;
        min-height: 48px;
        padding: 10px 18px;
        border-radius: 7px;
        font-size: 17px;
        font-weight: 600;
        touch-action: manipulation;
    }
    #Network_Setting #network_setting_save:disabled {
        opacity: .65;
        cursor: wait;
    }
    .ajs-dialog.network-setting-alertify-dialog,
    .network-setting-alertify-dialog .ajs-dialog {
        width: calc(100vw - 28px) !important;
        max-width: 520px !important;
        margin: 14px auto !important;
    }
    .network-setting-alertify-dialog .ajs-content {
        max-height: 65vh;
        overflow-y: auto;
        overflow-wrap: anywhere;
        -webkit-overflow-scrolling: touch;
    }
    .network-setting-alertify-dialog .ajs-footer .ajs-button {
        min-width: 88px;
        min-height: 44px;
        font-size: 16px;
    }
}

@media (max-width: 380px) {
    #Network_Setting {
        padding-left: 12px;
        padding-right: 12px;
    }
    #Network_Setting .network-mode-options {
        grid-template-columns: 1fr;
    }
}
</style>
<div id="Network_Setting" class="divMode" style="display: none; overflow-x: hidden;">
    <div class="col t1 network-setting-title">
        <?php echo htmlspecialchars((string)$text['network_setting'], ENT_QUOTES, 'UTF-8'); ?>
    </div>

    <div class="row t2 network-setting-row">
        <div class="col-3 t1"><?php echo htmlspecialchars((string)$text['network_mode'], ENT_QUOTES, 'UTF-8'); ?>:</div>
        <div class="col t2 network-mode-options">
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="network_mode" id="network_mode_dynamic" value="1"
                    <?php echo $networkMode === 1 ? 'checked="checked"' : ''; ?>
                    onchange="toggleNetworkModeFields()">
                <label class="form-check-label" for="network_mode_dynamic">
                    <?php echo htmlspecialchars((string)$text['network_dynamic'], ENT_QUOTES, 'UTF-8'); ?>
                </label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="network_mode" id="network_mode_static" value="2"
                    <?php echo $networkMode === 2 ? 'checked="checked"' : ''; ?>
                    onchange="toggleNetworkModeFields()">
                <label class="form-check-label" for="network_mode_static">
                    <?php echo htmlspecialchars((string)$text['network_static'], ENT_QUOTES, 'UTF-8'); ?>
                </label>
            </div>
        </div>
    </div>

    <div class="row t2 network-setting-row">
        <div class="col-3 t1"><?php echo htmlspecialchars((string)$text['network_current_ip'], ENT_QUOTES, 'UTF-8'); ?>:</div>
        <div class="col-3 t2">
            <input id="network_current_ip" type="text"
                value="<?php echo htmlspecialchars($currentNetworkIp, ENT_QUOTES, 'UTF-8'); ?>"
                class="t3 form-control network-setting-input" readonly>
        </div>
    </div>

    <div class="row t2 network-setting-row">
        <div class="col-3 t1"><?php echo htmlspecialchars((string)$text['network_static_ip'], ENT_QUOTES, 'UTF-8'); ?>:</div>
        <div class="col-3 t2">
            <input id="network_static_ip" type="text" maxlength="15" inputmode="decimal"
                value="<?php echo htmlspecialchars($staticNetworkIp, ENT_QUOTES, 'UTF-8'); ?>"
                class="t3 form-control network-setting-input" autocomplete="off">
        </div>
    </div>

    <div class="row t2 network-setting-row">
        <div class="col-3 t1"><?php echo htmlspecialchars((string)$text['network_subnet_mask'], ENT_QUOTES, 'UTF-8'); ?>:</div>
        <div class="col-3 t2">
            <input id="network_subnet_mask" type="text" maxlength="15" inputmode="decimal"
                value="<?php echo htmlspecialchars($networkMask, ENT_QUOTES, 'UTF-8'); ?>"
                class="t3 form-control network-setting-input" autocomplete="off">
        </div>
    </div>

    <div class="row t2 network-setting-row">
        <div class="col-3 t1"><?php echo htmlspecialchars((string)$text['network_gateway_ip'], ENT_QUOTES, 'UTF-8'); ?>:</div>
        <div class="col-3 t2">
            <input id="network_gateway_ip" type="text" maxlength="15" inputmode="decimal"
                value="<?php echo htmlspecialchars($networkGateway, ENT_QUOTES, 'UTF-8'); ?>"
                class="t3 form-control network-setting-input" autocomplete="off">
        </div>
    </div>
    <!-- Server Port is managed by Controller Setting. -->

    <div class="network-setting-actions">
        <button class="all-btn w3-button w3-border w3-round-large" id="network_setting_save" onclick="saveNetworkSetting()">
            <?php echo htmlspecialchars((string)$text['save'], ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </div>
</div>

<script>
(function () {
    window.NETWORK_SETTING_I18N = {
        save: <?php echo json_encode((string)$text['save'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        saving: <?php echo json_encode((string)$text['network_saving'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        invalidStaticIp: <?php echo json_encode((string)$text['network_invalid_static_ip'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        invalidMask: <?php echo json_encode((string)$text['network_invalid_mask'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        invalidGateway: <?php echo json_encode((string)$text['network_invalid_gateway'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        saveFailed: <?php echo json_encode((string)$text['network_save_failed'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        newIpHint: <?php echo json_encode((string)$text['network_new_ip_hint'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        dialogTitle: <?php echo json_encode((string)$text['network_setting'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        successTitle: <?php echo json_encode((string)$text['success'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        errorTitle: <?php echo json_encode((string)$text['fail'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        ok: <?php echo json_encode((string)$text['confirm'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
    };

    window.toggleNetworkModeFields = function () {
        const staticMode = document.getElementById('network_mode_static')?.checked === true;
        ['network_static_ip', 'network_subnet_mask', 'network_gateway_ip'].forEach(function (id) {
            const field = document.getElementById(id);
            if (!field) return;
            field.disabled = !staticMode;
            field.classList.toggle('network-field-disabled', !staticMode);
        });
    };

    function isIpv4(value) {
        const parts = String(value || '').trim().split('.');
        if (parts.length !== 4) return false;
        return parts.every(function (part) {
            if (!/^\d{1,3}$/.test(part)) return false;
            const n = Number(part);
            return n >= 0 && n <= 255 && String(n) === String(Number(part));
        });
    }

    function isNetmask(value) {
        if (!isIpv4(value)) return false;
        const bits = value.split('.').map(function (part) {
            return Number(part).toString(2).padStart(8, '0');
        }).join('');
        return /^1*0*$/.test(bits);
    }

    function escapeNetworkDialogHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatNetworkDialogMessage(message) {
        return '<div class="network-setting-alert-message">'
            + escapeNetworkDialogHtml(message).replace(/\r?\n/g, '<br>')
            + '</div>';
    }

    function showNetworkDialog(title, message) {
        const i18n = window.NETWORK_SETTING_I18N || {};
        const dialogTitle = title || i18n.dialogTitle || 'Network Setting';
        const okLabel = i18n.ok || 'OK';

        if (window.alertify && typeof alertify.alert === 'function') {
            const dialog = alertify.alert(dialogTitle, formatNetworkDialogMessage(message));
            dialog.set({
                labels: { ok: okLabel },
                closable: false,
                movable: false,
                pinnable: false,
                resizable: false
            });

            // 沿用系統 Alertify dialog；只加識別 class，方便後續維護。
            try {
                if (dialog.elements && dialog.elements.dialog) {
                    dialog.elements.dialog.classList.add('network-setting-alertify-dialog');
                }
            } catch (_) {}
            return dialog;
        }

        // 理論上設定頁已載入 Alertify；保留 fallback 避免 library 載入失敗時無提示。
        alert(dialogTitle + '\n\n' + String(message || ''));
        return null;
    }

    function showNetworkError(message) {
        const i18n = window.NETWORK_SETTING_I18N || {};
        return showNetworkDialog(i18n.errorTitle || i18n.dialogTitle || 'Error', message);
    }

    function showNetworkSuccess(message) {
        const i18n = window.NETWORK_SETTING_I18N || {};
        return showNetworkDialog(i18n.successTitle || i18n.dialogTitle || 'Success', message);
    }

    function showNetworkRestartLifecycle(result, saveBtn, mode, staticIp, currentIp) {
        if (window.IS_ICONTROLLER !== true) return;
        const lang = typeof window.getSettingsLanguage === 'function'
            ? window.getSettingsLanguage()
            : String(document.cookie.match(/(?:^|;\s*)language=([^;]+)/)?.[1] || 'en-us').toLowerCase();
        const messages = lang === 'zh-tw' ? {
            title: '控制器重新啟動', countdown: '網路設定已同步更新，控制器將在 {n} 秒後自動重新啟動。',
            rebooting: '控制器正在重新啟動，請稍候。', recovered: '控制器已重新上線，正在自動重新載入頁面。',
            failed: '無法自動排程重新啟動，請手動重新啟動控制器。', timeout: '尚未偵測到控制器重新上線，請確認控制器狀態後重試。',
            retry: '立即重試', home: '返回首頁'
        } : lang === 'zh-cn' ? {
            title: '控制器重新启动', countdown: '网络设置已同步更新，控制器将在 {n} 秒后自动重新启动。',
            rebooting: '控制器正在重新启动，请稍候。', recovered: '控制器已重新上线，正在自动重新加载页面。',
            failed: '无法自动安排重新启动，请手动重新启动控制器。', timeout: '尚未检测到控制器重新上线，请确认控制器状态后重试。',
            retry: '立即重试', home: '返回首页'
        } : {
            title: 'Controller Restart', countdown: 'Network settings were synchronized. The controller will restart automatically in {n} seconds.',
            rebooting: 'The controller is restarting. Please wait.', recovered: 'The controller is online again. Reloading the page automatically.',
            failed: 'Automatic restart could not be scheduled. Please restart the controller manually.', timeout: 'The controller has not returned online. Check its status and try again.',
            retry: 'Retry now', home: 'Back to Home'
        };
        const delay = Number(result?.restart_delay_seconds) > 0 ? Number(result.restart_delay_seconds) : 5;
        const changedStaticIp = mode === 2 && isIpv4(staticIp) && staticIp !== currentIp;
        const portPart = window.location.port ? ':' + window.location.port : '';
        const targetOrigin = changedStaticIp ? window.location.protocol + '//' + staticIp + portPart : '';
        if (window.iDASRestartManager) {
            window.iDASRestartManager.start({
                scope: 'network',
                delay: delay,
                title: messages.title,
                countdown: function (seconds) { return messages.countdown.replace('{n}', seconds); },
                rebooting: messages.rebooting,
                recovered: messages.recovered,
                timeout: messages.timeout,
                retry: messages.retry,
                home: messages.home,
                failed: messages.failed,
                scheduleUrl: '?url=Settings/schedule_network_restart',
                saveButton: saveBtn,
                targetOrigin: targetOrigin
            });
            return;
        }

        // Compatibility fallback for installations with stale cached JS.
        let remaining = delay;
        let started = false;
        let timer = null;
        const esc = escapeNetworkDialogHtml;
        const countdownHtml = (seconds) => {
            const n = Math.max(0, Number(seconds) || 0);
            const width = Math.max(0, Math.min(100, (n / delay) * 100));
            return '<div style="text-align:center;line-height:1.55">'
                + '<div aria-live="polite" style="font-size:52px;font-weight:700;color:#d2322d;line-height:1.1;margin:4px 0 12px">' + n + '</div>'
                + '<div style="height:8px;background:#e7e7e7;border-radius:8px;overflow:hidden;margin:0 0 14px"><div style="height:100%;width:' + width + '%;background:#d2322d;border-radius:8px;transition:width .95s linear"></div></div>'
                + '<div>' + esc(messages.countdown.replace('{n}', n)) + '</div></div>';
        };
        const waitingHtml = (message) => '<style>@keyframes idasNetworkRestartSpin{to{transform:rotate(360deg)}}</style>'
            + '<div style="text-align:center;line-height:1.55;padding:10px 0"><div role="status" aria-live="polite" style="width:42px;height:42px;margin:2px auto 18px;border:5px solid #dfe4e8;border-top-color:#2b80c5;border-radius:50%;animation:idasNetworkRestartSpin .9s linear infinite"></div><div>' + esc(message) + '</div></div>';
        const timeoutHtml = () => '<div style="text-align:center;line-height:1.55;padding:8px 0"><div style="color:#d2322d;margin-bottom:18px">' + esc(messages.timeout) + '</div>'
            + '<button type="button" onclick="window.location.reload()" style="padding:9px 18px;margin:4px;border:0;border-radius:5px;background:#2b80c5;color:#fff">' + esc(messages.retry) + '</button>'
            + '<button type="button" onclick="window.location.href=\'?url=Dashboards\'" style="padding:9px 18px;margin:4px;border:1px solid #aaa;border-radius:5px;background:#fff;color:#333">' + esc(messages.home) + '</button></div>';

        const dialog = alertify.alert();
        const footer = (show) => { if (dialog?.elements?.footer) dialog.elements.footer.style.display = show ? '' : 'none'; };
        const basePath = window.location.pathname.endsWith('/')
            ? window.location.pathname
            : window.location.pathname.replace(/[^/]*$/, '');

        const waitForOnline = () => {
            const waitStarted = Date.now();
            let offlineObserved = false;
            let finished = false;

            const probe = () => {
                if (finished) return;
                if (Date.now() - waitStarted >= 90000) {
                    finished = true;
                    dialog.setContent(timeoutHtml());
                    return;
                }

                const probeOrigin = offlineObserved && targetOrigin
                    ? targetOrigin
                    : window.location.origin;
                const image = new Image();
                let settled = false;
                const next = () => { if (!finished) setTimeout(probe, 1000); };
                const timeoutId = setTimeout(function () {
                    if (settled) return;
                    settled = true;
                    offlineObserved = true;
                    next();
                }, 2500);

                image.onload = function () {
                    if (settled || finished) return;
                    settled = true;
                    clearTimeout(timeoutId);
                    if (!offlineObserved) {
                        next();
                        return;
                    }
                    finished = true;
                    dialog.setContent(waitingHtml(messages.recovered));
                    setTimeout(function () {
                        if (targetOrigin) {
                            window.location.href = targetOrigin + basePath + '?url=Settings%2Findex';
                        } else {
                            window.location.reload();
                        }
                    }, 800);
                };
                image.onerror = function () {
                    if (settled || finished) return;
                    settled = true;
                    clearTimeout(timeoutId);
                    offlineObserved = true;
                    next();
                };
                image.src = probeOrigin + basePath + 'font/img/touch-icon.png?_idas_restart_probe=' + Date.now();
            };

            setTimeout(probe, 700);
        };

        dialog.setHeader(messages.title);
        dialog.setContent(countdownHtml(remaining));
        dialog.set({ closable: false, movable: false, pinnable: false, resizable: false, onshow: function () {
            if (started) return;
            started = true;
            footer(false);
            setTimeout(function () {
                $.ajax({ url: '?url=Settings/schedule_network_restart', method: 'POST', dataType: 'json', timeout: 5000 })
                    .done(function (scheduled) {
                        if (!scheduled || scheduled.success !== true || scheduled.restart_scheduled !== true) {
                            dialog.setContent(esc(messages.failed + (scheduled?.res_msg ? ' (' + scheduled.res_msg + ')' : '')));
                            footer(true);
                            saveBtn.disabled = false;
                            return;
                        }
                        timer = setInterval(function () {
                            remaining -= 1;
                            if (remaining <= 0) {
                                clearInterval(timer);
                                dialog.setContent(waitingHtml(messages.rebooting));
                                waitForOnline();
                                return;
                            }
                            dialog.setContent(countdownHtml(remaining));
                        }, 1000);
                    }).fail(function (xhr) {
                        const serverMessage = xhr?.responseJSON?.res_msg || '';
                        dialog.setContent(esc(messages.failed + (serverMessage ? ' (' + serverMessage + ')' : '')));
                        footer(true);
                        saveBtn.disabled = false;
                    });
            }, 100);
        }});
        dialog.show();
    }

    window.saveNetworkSetting = function () {
        const saveBtn = document.getElementById('network_setting_save');
        if (!saveBtn || saveBtn.disabled) return;

        const staticMode = document.getElementById('network_mode_static')?.checked === true;
        const mode = staticMode ? 2 : 1;
        const staticIp = String(document.getElementById('network_static_ip')?.value || '').trim();
        const mask = String(document.getElementById('network_subnet_mask')?.value || '').trim();
        const gateway = String(document.getElementById('network_gateway_ip')?.value || '').trim();
        const i18n = window.NETWORK_SETTING_I18N || {};
        let keepSaveDisabled = false;

        if (staticMode && !isIpv4(staticIp)) {
            showNetworkError(i18n.invalidStaticIp || 'Invalid static IP address.');
            return;
        }
        if (staticMode && !isNetmask(mask)) {
            showNetworkError(i18n.invalidMask || 'Invalid subnet mask.');
            return;
        }
        if (staticMode && !isIpv4(gateway)) {
            showNetworkError(i18n.invalidGateway || 'Invalid gateway IP address.');
            return;
        }

        saveBtn.disabled = true;
        saveBtn.classList.add('network-save-busy');
        saveBtn.textContent = i18n.saving || 'Saving...';

        $.ajax({
            url: '?url=Settings/save_network_setting',
            method: 'POST',
            dataType: 'json',
            data: {
                network_mode: mode,
                static_ip: staticIp,
                mask: mask,
                gateway: gateway
            }
        }).done(function (result) {
            if (!result || result.ok !== true) {
                showNetworkError((result && result.message) || i18n.saveFailed || 'Unable to save network settings.');
                return;
            }

            let message = result.message || '';
            const currentIp = String(document.getElementById('network_current_ip')?.value || '').trim();
            if (result.changed && mode === 2 && staticIp && staticIp !== currentIp) {
                message += '\n' + (i18n.newIpHint || '') + staticIp;
            }

            if (result.changed && result.requires_reboot === true && result.restart_pending_saved === true) {
                keepSaveDisabled = true;
                showNetworkRestartLifecycle(result, saveBtn, mode, staticIp, currentIp);
                return;
            }
            showNetworkSuccess(message);
        }).fail(function () {
            showNetworkError(i18n.saveFailed || 'Unable to save network settings.');
        }).always(function () {
            if (!keepSaveDisabled) {
                saveBtn.disabled = false;
                saveBtn.classList.remove('network-save-busy');
                saveBtn.textContent = i18n.save || 'Save';
            }
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        toggleNetworkModeFields();
    });
})();
</script>
