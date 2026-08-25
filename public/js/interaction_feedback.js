(function () {
    'use strict';

    var LOCK_MS = 900;
    var AJAX_ACTION_WINDOW_MS = 1200;
    var ACTION_TIMEOUT_MS = 15000;
    var mutationActionPattern = /save|add|new|create|edit|update|delete|remove|copy|import|upload|apply|sync|confirm|submit|firmware|download/i;
    var transientActionPattern = /history|export|download|chart|customize/i;
    var pendingActionButton = null;
    var pendingActionAt = 0;
    var lastJqueryAjaxAt = 0;

    function getButton(target) {
        if (!target || !target.closest) return null;
        return target.closest('button, input[type="button"], input[type="submit"], [role="button"]');
    }

    function actionDescriptor(button) {
        return [
            button.id || '', button.name || '', button.getAttribute('onclick') || '',
            button.getAttribute('data-action') || '', button.className || '',
            button.textContent || '', button.value || ''
        ].join(' ');
    }

    function usesDialogErrorFeedback(button) {
        return !!button && /save|create|add|new|edit|update|copy|delete|remove|新增|儲存|保存|編輯|修改|複製|刪除/i.test(actionDescriptor(button));
    }

    function shouldLock(button) {
        if (!button || button.disabled || button.dataset.idasNoLock === '1') return false;
        if (button.closest('.ajs-footer')) return false;
        return button.dataset.idasLock === '1' || mutationActionPattern.test(actionDescriptor(button));
    }

    function lockBriefly(button) {
        if (!shouldLock(button)) return true;
        if (button.dataset.idasClickLocked === '1') return false;
        button.dataset.idasClickLocked = '1';
        button.classList.add('idas-click-locked');
        button.setAttribute('aria-disabled', 'true');
        window.setTimeout(function () {
            if (button.dataset.idasAjaxBusy === '1') return;
            delete button.dataset.idasClickLocked;
            button.classList.remove('idas-click-locked');
            if (!button.disabled) button.removeAttribute('aria-disabled');
        }, LOCK_MS);
        return true;
    }

    function optionContainer(input) {
        return input.closest('.form-check, .form-check-inline');
    }

    function refreshOptions(root) {
        (root || document).querySelectorAll('input[type="radio"], input[type="checkbox"]').forEach(function (input) {
            var container = optionContainer(input);
            if (container) container.classList.toggle('idas-option-selected', input.checked);
        });
    }

    function pulseInvalid(element) {
        if (!element || element.dataset.idasInvalidAnimating === '1') return;
        element.dataset.idasInvalidAnimating = '1';
        element.classList.remove('idas-invalid-pulse');
        void element.offsetWidth;
        element.classList.add('idas-invalid-pulse');
        window.setTimeout(function () {
            element.classList.remove('idas-invalid-pulse');
            delete element.dataset.idasInvalidAnimating;
        }, 320);
    }

    function setAjaxBusy(button, enabled) {
        if (!button) return;
        var scope = button.closest('.modal, form, .setting-content, .settings-content');
        if (enabled) button.classList.remove('idas-action-success', 'idas-action-failure');
        button.dataset.idasAjaxBusy = enabled ? '1' : '0';
        button.dataset.idasState = enabled ? 'busy' : 'idle';
        button.classList.toggle('idas-action-busy', enabled);
        button.classList.toggle('idas-click-locked', enabled);
        button.setAttribute('aria-busy', enabled ? 'true' : 'false');
        if (scope) {
            scope.classList.toggle('idas-scope-busy', enabled);
            scope.setAttribute('aria-busy', enabled ? 'true' : 'false');
        }
        if (enabled) button.setAttribute('aria-disabled', 'true');
        else {
            delete button.dataset.idasAjaxBusy;
            delete button.dataset.idasClickLocked;
            button.removeAttribute('aria-busy');
            if (scope) scope.removeAttribute('aria-busy');
            if (!button.disabled) button.removeAttribute('aria-disabled');
        }
    }

    function responseSucceeded(data) {
        var value = data;
        if (typeof value === 'string') {
            try { value = JSON.parse(value); } catch (ignore) { return true; }
        }
        if (!value || typeof value !== 'object') return true;

        // 新版 API 明確提供 success 時，以 success 為準。
        if (typeof value.success !== 'undefined') {
            var success = String(value.success).toLowerCase();
            return value.success === true || value.success === 1 || success === 'true' || success === '1';
        }

        // 舊版常以 Info 當作新增成功的提示標題，不能視為失敗。
        // 只有明確的錯誤代碼或錯誤類型才顯示紅色。
        var code = String(value.code || '').toLowerCase();
        if (code && /(^|_)(fail|failed|error|ng|invalid|denied|timeout)($|_)/.test(code)) return false;

        var type = String(value.res_type || '').toLowerCase();
        if (type === 'fail' || type === 'failed' || type === 'error' || type === 'ng' || type === 'false' || type === '0') return false;
        return true;
    }

    function beginAsyncAction(request, explicitButton) {
        var button = explicitButton || pendingActionButton;
        if (!button || (!explicitButton && (Date.now() - pendingActionAt) > AJAX_ACTION_WINDOW_MS)) return null;
        var context = { button: button, request: request || null, finished: false, succeeded: true, timer: null };
        if (request) request._idasFeedbackContext = context;
        var count = Number(button.dataset.idasAjaxCount || 0) + 1;
        button.dataset.idasAjaxCount = String(count);
        setAjaxBusy(button, true);
        context.timer = window.setTimeout(function () { finishAsyncAction(context, null); }, ACTION_TIMEOUT_MS);
        return context;
    }

    function finishAsyncAction(context, succeeded) {
        if (!context || context.finished) return;
        context.finished = true;
        window.clearTimeout(context.timer);
        var button = context.button;
        if (succeeded === false) button.dataset.idasAjaxResult = 'failure';
        var count = Math.max(0, Number(button.dataset.idasAjaxCount || 1) - 1);
        button.dataset.idasAjaxCount = String(count);
        if (count > 0) return;
        var failed = button.dataset.idasAjaxResult === 'failure';
        setAjaxBusy(button, false);
        delete button.dataset.idasAjaxCount;
        delete button.dataset.idasAjaxResult;
        if (succeeded === null) return;
        if (failed) showActionFailure(button);
        else window.iDASInteractionFeedback.success(button);
        pendingActionButton = null;
    }

    function showActionFailure(button, duration) {
        if (!button) return;
        button.classList.remove('idas-action-busy');
        // 新增、儲存、編輯、複製與刪除可能接續多個背景 AJAX；
        // 輔助請求不應讓操作按鈕短暫變紅，錯誤仍由 alert／欄位驗證呈現。
        if (usesDialogErrorFeedback(button)) {
            button.classList.remove('idas-action-failure');
            return;
        }
        button.classList.add('idas-action-failure');
        button.dataset.idasState = 'failure';
        var live = document.getElementById('idas-action-status');
        if (live) live.textContent = 'Action failed';
        window.setTimeout(function () {
            button.classList.remove('idas-action-failure');
            button.dataset.idasState = 'idle';
        }, Number(duration) || 1200);
    }

    function installAjaxFeedback() {
        if (!window.jQuery || !window.jQuery.fn) return;
        window.jQuery(document)
            .on('ajaxSend.idasFeedback', function (_event, xhr) {
                var settings = arguments[2] || {};
                var method = String(settings.type || settings.method || 'GET').toUpperCase();
                if (method === 'GET' || settings.idasFeedback === false) return;
                lastJqueryAjaxAt = Date.now();
                var explicit = settings.idasButton;
                if (typeof explicit === 'string') explicit = document.querySelector(explicit);
                beginAsyncAction(xhr, explicit && explicit.nodeType === 1 ? explicit : null);
            })
            .on('ajaxSuccess.idasFeedback', function (_event, _xhr, _settings, data) {
                var context = _xhr._idasFeedbackContext;
                if (!context) return;
                context.succeeded = responseSucceeded(data);
            })
            .on('ajaxError.idasFeedback', function (_event, xhr) {
                var context = xhr._idasFeedbackContext;
                if (context) context.succeeded = false;
            })
            .on('ajaxComplete.idasFeedback', function (_event, xhr) {
                var context = xhr._idasFeedbackContext;
                if (!context) return;
                finishAsyncAction(context, context.succeeded);
            });
    }

    function installFetchFeedback() {
        if (!window.fetch || window.fetch.__idasFeedbackWrapped) return;
        var nativeFetch = window.fetch;
        var wrappedFetch = function () {
            var args = arguments;
            var input = args[0];
            var options = args[1] || {};
            var method = String(options.method || (input && input.method) || 'GET').toUpperCase();
            var context = method === 'GET' ? null : beginAsyncAction(null);
            return nativeFetch.apply(this, arguments).then(function (response) {
                if (context && response && response.ok !== false && response.clone) {
                    var contentType = response.headers && response.headers.get ? (response.headers.get('content-type') || '') : '';
                    if (contentType.indexOf('json') !== -1) {
                        response.clone().json().then(function (data) {
                            finishAsyncAction(context, responseSucceeded(data));
                        }, function () { finishAsyncAction(context, true); });
                    } else finishAsyncAction(context, true);
                } else finishAsyncAction(context, response && response.ok !== false);
                return response;
            }, function (error) {
                finishAsyncAction(context, false);
                throw error;
            });
        };
        wrappedFetch.__idasFeedbackWrapped = true;
        window.fetch = wrappedFetch;
    }

    function installXhrFeedback() {
        if (!window.XMLHttpRequest || window.XMLHttpRequest.prototype.send.__idasFeedbackWrapped) return;
        var nativeOpen = window.XMLHttpRequest.prototype.open;
        var nativeSend = window.XMLHttpRequest.prototype.send;
        window.XMLHttpRequest.prototype.open = function (method) {
            this._idasMethod = String(method || 'GET').toUpperCase();
            return nativeOpen.apply(this, arguments);
        };
        var wrappedSend = function () {
            var xhr = this;
            var isJqueryTransport = (Date.now() - lastJqueryAjaxAt) < 100;
            var context = (!isJqueryTransport && xhr._idasMethod !== 'GET') ? beginAsyncAction(xhr) : null;
            if (context) {
                xhr.addEventListener('loadend', function () {
                    var succeeded = xhr.status >= 200 && xhr.status < 400;
                    if (succeeded) succeeded = responseSucceeded(xhr.responseText);
                    finishAsyncAction(context, succeeded);
                }, { once: true });
            }
            return nativeSend.apply(xhr, arguments);
        };
        wrappedSend.__idasFeedbackWrapped = true;
        window.XMLHttpRequest.prototype.send = wrappedSend;
    }

    function installInvalidObserver() {
        if (!window.MutationObserver || !document.body) return;
        new MutationObserver(function (records) {
            records.forEach(function (record) {
                var element = record.target;
                if (element.classList && element.classList.contains('is-invalid')) pulseInvalid(element);
            });
        }).observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class', 'aria-invalid'] });
    }

    window.iDASInteractionFeedback = {
        busy: function (button, enabled) {
            if (button) button.classList.toggle('idas-action-busy', enabled !== false);
        },
        success: function (button, duration) {
            if (!button) return;
            setAjaxBusy(button, false);
            button.classList.add('idas-action-success');
            button.dataset.idasState = 'success';
            var live = document.getElementById('idas-action-status');
            if (live) live.textContent = 'Action completed';
            window.setTimeout(function () {
                button.classList.remove('idas-action-success');
                button.dataset.idasState = 'idle';
            }, Number(duration) || 1100);
        },
        failure: showActionFailure,
        error: pulseInvalid,
        refreshOptions: refreshOptions
    };

    function initialize() {
        document.documentElement.classList.add('idas-ui-enhanced');
        if (!document.getElementById('idas-action-status')) {
            var live = document.createElement('div');
            live.id = 'idas-action-status';
            live.setAttribute('aria-live', 'polite');
            live.setAttribute('aria-atomic', 'true');
            live.style.cssText = 'position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;';
            document.body.appendChild(live);
        }
        refreshOptions(document);
        document.querySelectorAll('button, input[type="button"], input[type="submit"], [role="button"]').forEach(function (button) {
            if (button.dataset.idasAction) return;
            var descriptor = actionDescriptor(button);
            var match = descriptor.match(/save|create|add|new|edit|update|copy|delete|remove/i);
            if (match) button.dataset.idasAction = match[0].toLowerCase();
            if (!button.dataset.idasState) button.dataset.idasState = 'idle';
        });
        installInvalidObserver();
        installAjaxFeedback();
        installFetchFeedback();
        installXhrFeedback();
    }

    document.addEventListener('change', function (event) {
        if (event.target && (event.target.matches('input[type="radio"]') || event.target.matches('input[type="checkbox"]'))) {
            refreshOptions(document);
        }
    });

    document.addEventListener('click', function (event) {
        var button = getButton(event.target);
        if (button && shouldLock(button)) {
            pendingActionButton = button;
            pendingActionAt = Date.now();
        }
        if (button && !lockBriefly(button)) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }
        if (button && transientActionPattern.test(actionDescriptor(button))) {
            button.classList.add('idas-action-busy');
            window.setTimeout(function () {
                if (button.dataset.idasAjaxCount) return;
                button.classList.remove('idas-action-busy');
            }, 550);
        }
        var row = event.target && event.target.closest ? event.target.closest('tbody tr') : null;
        if (row) {
            row.classList.add('idas-row-pressed');
            window.setTimeout(function () { row.classList.remove('idas-row-pressed'); }, 220);
        }
    }, true);

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
