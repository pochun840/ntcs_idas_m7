(function (window, document) {
    'use strict';

    var activeTimer = null;
    var activeCallback = null;
    var original = {};
    var sweetAlertInstallTimer = null;

    var ICONS = {
        success: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>',
        error: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5m0 3h.01"/><path d="M10.3 4.4 3.2 17a2 2 0 0 0 1.8 3h14a2 2 0 0 0 1.8-3L13.7 4.4a2 2 0 0 0-3.4 0Z"/></svg>',
        warning: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5m0 3h.01"/><path d="M10.3 4.4 3.2 17a2 2 0 0 0 1.8 3h14a2 2 0 0 0 1.8-3L13.7 4.4a2 2 0 0 0-3.4 0Z"/></svg>',
        info: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 11v6m0-10h.01"/><circle cx="12" cy="12" r="9"/></svg>'
    };

    function plainText(value) {
        if (value === null || typeof value === 'undefined') return '';
        var holder = document.createElement('div');
        holder.innerHTML = String(value)
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/(?:li|p|div)>/gi, '\n');
        return (holder.textContent || holder.innerText || '').trim();
    }

    function inferType(title, message, explicitType) {
        if (explicitType && ICONS[explicitType]) return explicitType;
        var value = (String(title || '') + ' ' + String(message || '')).toLowerCase();
        if (/error|fail|failed|invalid|unable|錯誤|失敗|異常|无效|錯誤|逾時|timeout/.test(value)) return 'error';
        if (/warning|warn|警告|注意|確認|确认/.test(value)) return 'warning';
        if (/success|saved|complete|completed|done|成功|完成|已儲存|已保存/.test(value)) return 'success';
        return 'info';
    }

    function defaultTitle(type) {
        var lang = String((document.cookie.match(/(?:^|; )language=([^;]*)/) || [])[1] || 'en-us').toLowerCase();
        if (lang === 'zh-tw') return { success: '成功', error: '錯誤', warning: '警告', info: '提示' }[type];
        if (lang === 'zh-cn') return { success: '成功', error: '错误', warning: '警告', info: '提示' }[type];
        return { success: 'Success', error: 'Error', warning: 'Warning', info: 'Information' }[type];
    }

    function durationFor(type, message) {
        var base = type === 'success' ? 1900 : (type === 'error' ? 3600 : 2800);
        return Math.min(6000, base + Math.max(0, String(message || '').length - 45) * 22);
    }

    function ensureRegion() {
        var region = document.getElementById('idasNotificationRegion');
        if (region) return region;
        region = document.createElement('div');
        region.id = 'idasNotificationRegion';
        region.className = 'idas-notification-region';
        region.setAttribute('aria-live', 'polite');
        region.setAttribute('aria-atomic', 'true');
        document.body.appendChild(region);
        return region;
    }

    function close(invokeCallback) {
        if (activeTimer) {
            window.clearTimeout(activeTimer);
            activeTimer = null;
        }
        var card = document.querySelector('#idasNotificationRegion .idas-notification-card');
        var callback = activeCallback;
        activeCallback = null;
        if (!card) {
            if (invokeCallback && typeof callback === 'function') callback();
            return;
        }
        card.classList.add('is-leaving');
        window.setTimeout(function () {
            var region = document.getElementById('idasNotificationRegion');
            if (region) region.innerHTML = '';
            if (invokeCallback && typeof callback === 'function') callback();
        }, 190);
    }

    function show(options) {
        options = options || {};
        var title = plainText(options.title);
        var message = plainText(options.message);
        var type = inferType(title, message, options.type);
        if (!title || title.toLowerCase() === type) title = defaultTitle(type);

        if (activeTimer) {
            window.clearTimeout(activeTimer);
            activeTimer = null;
        }
        activeCallback = null;
        var region = ensureRegion();
        region.innerHTML = '';
        region.innerHTML = '<div class="idas-notification-card is-' + type + '" role="status">'
            + '<div class="idas-notification-icon">' + ICONS[type] + '</div>'
            + '<div class="idas-notification-title"></div>'
            + (message ? '<div class="idas-notification-message"></div>' : '')
            + '</div>';
        region.querySelector('.idas-notification-title').textContent = title;
        var messageElement = region.querySelector('.idas-notification-message');
        if (messageElement) messageElement.textContent = message;
        activeCallback = typeof options.onClose === 'function' ? options.onClose : null;
        activeTimer = window.setTimeout(function () { close(true); }, options.duration || durationFor(type, message));
        return { set: function () { return this; }, setting: function () { return this; }, close: function () { close(true); } };
    }

    function parseAlertArguments(args) {
        var values = Array.prototype.slice.call(args);
        var callback = null;
        if (typeof values[values.length - 1] === 'function') callback = values.pop();
        if (values.length >= 2) return { title: values[0], message: values[1], onClose: callback };
        return { title: '', message: values[0], onClose: callback };
    }

    function notifyAlert() {
        if (!arguments.length && original.alert) return original.alert.apply(window.alertify, arguments);
        return show(parseAlertArguments(arguments));
    }

    function notifyByType(type, message, callback) {
        return show({ title: '', message: message, type: type, onClose: callback });
    }

    function notifySweetAlert() {
        var args = Array.prototype.slice.call(arguments);
        var options = (args[0] && typeof args[0] === 'object') ? args[0] : {
            title: args[0], text: args[1], icon: args[2]
        };
        if (options.showCancelButton || options.showDenyButton || options.input || options.preConfirm) {
            return original.swalFire ? original.swalFire.apply(window.Swal, args) : Promise.resolve({ isDismissed: true });
        }
        var type = String(options.icon || '').toLowerCase();
        if (type === 'question' && original.swalFire) return original.swalFire.apply(window.Swal, args);
        show({ title: options.title || '', message: options.text || options.html || '', type: ICONS[type] ? type : undefined });
        return Promise.resolve({ isConfirmed: true, isDismissed: false, value: true });
    }

    function installAlertifyAdapter() {
        if (!window.alertify || window.alertify.__idasNotificationInstalled) return false;
        var api = window.alertify;
        original.alert = api.alert;
        original.success = api.success;
        original.error = api.error;
        original.warning = api.warning;
        original.message = api.message;
        original.closeAll = api.closeAll;
        original.dismissAll = api.dismissAll;

        api.alert = function () {
            if (!arguments.length) return original.alert.apply(api, arguments);
            return show(parseAlertArguments(arguments));
        };
        ['success', 'error', 'warning', 'message'].forEach(function (method) {
            api[method] = function (message) {
                return show({ title: '', message: message, type: method === 'message' ? 'info' : method });
            };
        });
        if (typeof original.closeAll === 'function') {
            api.closeAll = function () {
                close(false);
                return original.closeAll.apply(api, arguments);
            };
        }
        if (typeof original.dismissAll === 'function') {
            api.dismissAll = function () {
                close(false);
                return original.dismissAll.apply(api, arguments);
            };
        }
        api.__idasNotificationInstalled = true;
        return true;
    }

    function installNativeAlertAdapter() {
        if (window.alert && window.alert.__idasNotificationInstalled) return;
        original.nativeAlert = window.alert;
        var adapter = function (message) {
            return show({ title: '', message: message });
        };
        adapter.__idasNotificationInstalled = true;
        window.alert = adapter;
    }

    function installSweetAlertAdapter() {
        if (!window.Swal || typeof window.Swal.fire !== 'function' || window.Swal.__idasNotificationInstalled) {
            return false;
        }
        original.swalFire = window.Swal.fire.bind(window.Swal);
        window.Swal.fire = function () {
            return notifySweetAlert.apply(null, arguments);
        };
        window.Swal.__idasNotificationInstalled = true;
        return true;
    }

    function waitForSweetAlert() {
        var attempts = 0;
        sweetAlertInstallTimer = window.setInterval(function () {
            attempts += 1;
            if (installSweetAlertAdapter() || attempts >= 40) {
                window.clearInterval(sweetAlertInstallTimer);
                sweetAlertInstallTimer = null;
            }
        }, 250);
    }

    function confirmLanguage() {
        var match = document.cookie.match(/(?:^|; )language=([^;]*)/);
        var lang = String(match ? decodeURIComponent(match[1]) : 'en-us').toLowerCase();
        if (lang === 'zh-tw') return { title: '操作確認', dangerTitle: '確認危險操作', ok: '確定', dangerOk: '確定刪除' };
        if (lang === 'zh-cn') return { title: '操作确认', dangerTitle: '确认危险操作', ok: '确定', dangerOk: '确认删除' };
        return { title: 'Confirmation', dangerTitle: 'Confirm action', ok: 'OK', dangerOk: 'Delete' };
    }

    function enhanceConfirmDialog(dialog) {
        if (!dialog) return;
        var cancel = dialog.querySelector('.ajs-button.ajs-cancel');
        var ok = dialog.querySelector('.ajs-button.ajs-ok');
        if (!cancel || !ok) return;
        if (cancel.hidden || cancel.classList.contains('ajs-hidden') || cancel.getAttribute('aria-hidden') === 'true') return;

        var content = dialog.querySelector('.ajs-content');
        var text = (content ? content.textContent : dialog.textContent) || '';
        var danger = /delete|remove|clear|reset|overwrite|downgrade|刪除|删除|清除|重置|覆蓋|覆盖|降版/i.test(text);
        var labels = confirmLanguage();
        dialog.classList.add('idas-confirm-dialog');
        dialog.classList.toggle('is-danger', danger);

        var header = dialog.querySelector('.ajs-header');
        if (header && (!header.textContent.trim() || /^(提示|notification|alertifyjs)$/i.test(header.textContent.trim()))) {
            header.textContent = danger ? labels.dangerTitle : labels.title;
        }
        var isDelete = danger && /刪除|删除|delete|remove/i.test(text);
        ok.textContent = isDelete ? labels.dangerOk : labels.ok;

        if (content && !content.querySelector('.idas-confirm-icon')) {
            var icon = document.createElement('div');
            icon.className = 'idas-confirm-icon';
            icon.setAttribute('aria-hidden', 'true');
            icon.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 8v5m0 3h.01"/>'
                + '<path d="M10.3 4.4 3.2 17a2 2 0 0 0 1.8 3h14a2 2 0 0 0 1.8-3L13.7 4.4a2 2 0 0 0-3.4 0Z"/></svg>';
            content.insertBefore(icon, content.firstChild);
        }

        window.setTimeout(function () {
            ok.textContent = isDelete ? labels.dangerOk : labels.ok;
            if (document.body.contains(cancel)) cancel.focus();
        }, 60);
    }

    function installConfirmEnhancer() {
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                    if (!node || node.nodeType !== 1) return;
                    if (node.matches && node.matches('.ajs-dialog')) enhanceConfirmDialog(node);
                    if (node.closest) enhanceConfirmDialog(node.closest('.ajs-dialog'));
                    Array.prototype.forEach.call(node.querySelectorAll ? node.querySelectorAll('.ajs-dialog') : [], enhanceConfirmDialog);
                });
            });
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
        Array.prototype.forEach.call(document.querySelectorAll('.ajs-dialog'), enhanceConfirmDialog);
    }

    window.IdasNotify = {
        show: show,
        alert: notifyAlert,
        success: function (message, callback) { return notifyByType('success', message, callback); },
        error: function (message, callback) { return notifyByType('error', message, callback); },
        warning: function (message, callback) { return notifyByType('warning', message, callback); },
        message: function (message, callback) { return notifyByType('info', message, callback); },
        swal: notifySweetAlert,
        close: function () { close(false); },
        install: function () {
            installAlertifyAdapter();
            installNativeAlertAdapter();
            installSweetAlertAdapter();
        }
    };
    installNativeAlertAdapter();
    installConfirmEnhancer();
    if (!installSweetAlertAdapter()) waitForSweetAlert();
    if (!installAlertifyAdapter()) {
        document.addEventListener('DOMContentLoaded', installAlertifyAdapter, { once: true });
        window.setTimeout(installAlertifyAdapter, 0);
    }
}(window, document));
