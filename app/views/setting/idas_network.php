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
$networkPortLocked = idas_protocol_has_fixed_server_port($controllerModbusType);
?>
<div id="Network_Setting" class="divMode" style="display: none; overflow-x: hidden;">
    <div class="col t1" style="padding-left: 3%; font-weight: bold; padding-top: 1%;">
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
            <input id="network_static_ip" type="text" maxlength="15"
                value="<?php echo htmlspecialchars($staticNetworkIp, ENT_QUOTES, 'UTF-8'); ?>"
                class="t3 form-control network-setting-input" autocomplete="off">
        </div>
    </div>

    <div class="row t2 network-setting-row">
        <div class="col-3 t1"><?php echo htmlspecialchars((string)$text['network_subnet_mask'], ENT_QUOTES, 'UTF-8'); ?>:</div>
        <div class="col-3 t2">
            <input id="network_subnet_mask" type="text" maxlength="15"
                value="<?php echo htmlspecialchars($networkMask, ENT_QUOTES, 'UTF-8'); ?>"
                class="t3 form-control network-setting-input" autocomplete="off">
        </div>
    </div>

    <div class="row t2 network-setting-row">
        <div class="col-3 t1"><?php echo htmlspecialchars((string)$text['network_gateway_ip'], ENT_QUOTES, 'UTF-8'); ?>:</div>
        <div class="col-3 t2">
            <input id="network_gateway_ip" type="text" maxlength="15"
                value="<?php echo htmlspecialchars($networkGateway, ENT_QUOTES, 'UTF-8'); ?>"
                class="t3 form-control network-setting-input" autocomplete="off">
        </div>
    </div>

    <div class="row t2 network-setting-row">
        <div class="col-3 t1"><?php echo htmlspecialchars((string)$text['network_server_port'], ENT_QUOTES, 'UTF-8'); ?>:</div>
        <div class="col-5 t2 network-port-wrap">
            <input id="network_server_port" type="number" min="1" max="65535" step="1"
                value="<?php echo (int)$networkPort; ?>"
                class="t3 form-control network-setting-port" autocomplete="off"
                <?php echo $networkPortLocked ? 'readonly' : ''; ?>>
            <span class="network-reboot-note">
                (<?php echo htmlspecialchars((string)$text['network_reboot_note'], ENT_QUOTES, 'UTF-8'); ?>)
            </span>
        </div>
    </div>

    <div style="text-align: center; margin-top: 30px; margin-bottom: 20px;">
        <button class="all-btn w3-button w3-border w3-round-large" id="network_setting_save" onclick="saveNetworkSetting()">
            <?php echo htmlspecialchars((string)$text['save'], ENT_QUOTES, 'UTF-8'); ?>
        </button>
    </div>
</div>

<script>
(function () {
    const NETWORK_PROTOCOL_TYPE = <?php echo (int)$controllerModbusType; ?>;
    const NETWORK_PROTOCOL_TCP = <?php echo (int)IDAS_PROTOCOL_TCP; ?>;
    const NETWORK_PROTOCOL_OP = <?php echo (int)IDAS_PROTOCOL_OP; ?>;
    const NETWORK_PORT_TCP = <?php echo (int)IDAS_SERVER_PORT_TCP; ?>;
    const NETWORK_PORT_OP = <?php echo (int)IDAS_SERVER_PORT_OP; ?>;

    function applyProtocolServerPort() {
        const portField = document.getElementById('network_server_port');
        if (!portField) return;

        if (NETWORK_PROTOCOL_TYPE === NETWORK_PROTOCOL_TCP) {
            portField.value = String(NETWORK_PORT_TCP);
            portField.readOnly = true;
        } else if (NETWORK_PROTOCOL_TYPE === NETWORK_PROTOCOL_OP) {
            portField.value = String(NETWORK_PORT_OP);
            portField.readOnly = true;
        } else {
            // RTU 沒有固定 TCP Server Port，保留目前設定值。
            portField.readOnly = false;
        }
    }

    window.NETWORK_SETTING_I18N = {
        save: <?php echo json_encode((string)$text['save'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        saving: <?php echo json_encode((string)$text['network_saving'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        invalidStaticIp: <?php echo json_encode((string)$text['network_invalid_static_ip'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        invalidMask: <?php echo json_encode((string)$text['network_invalid_mask'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        invalidGateway: <?php echo json_encode((string)$text['network_invalid_gateway'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
        invalidPort: <?php echo json_encode((string)$text['network_invalid_port'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
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

    window.saveNetworkSetting = function () {
        const saveBtn = document.getElementById('network_setting_save');
        if (!saveBtn || saveBtn.disabled) return;

        const staticMode = document.getElementById('network_mode_static')?.checked === true;
        const mode = staticMode ? 2 : 1;
        const staticIp = String(document.getElementById('network_static_ip')?.value || '').trim();
        const mask = String(document.getElementById('network_subnet_mask')?.value || '').trim();
        const gateway = String(document.getElementById('network_gateway_ip')?.value || '').trim();

        // 儲存前再次依通訊協議校正 Port，避免 DOM 被手動修改。
        applyProtocolServerPort();
        const portText = String(document.getElementById('network_server_port')?.value || '').trim();
        const port = Number(portText);
        const i18n = window.NETWORK_SETTING_I18N || {};

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
        if (!Number.isInteger(port) || port < 1 || port > 65535) {
            showNetworkError(i18n.invalidPort || 'Server port must be between 1 and 65535.');
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
                gateway: gateway,
                server_port: port
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

            showNetworkSuccess(message);
        }).fail(function () {
            showNetworkError(i18n.saveFailed || 'Unable to save network settings.');
        }).always(function () {
            saveBtn.disabled = false;
            saveBtn.classList.remove('network-save-busy');
            saveBtn.textContent = i18n.save || 'Save';
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        toggleNetworkModeFields();
        applyProtocolServerPort();
    });
})();
</script>
