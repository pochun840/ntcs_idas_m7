(function (window, document, $) {
    'use strict';

    if (!$ || typeof window.alertify === 'undefined') return;

    var activeRequest = null;
    var syncBusy = false;
    var finishTimer = null;
    var progressTimer = null;
    var successDelayTimer = null;
    var progressValue = 5;
    var progressStartedAt = 0;
    var REQUEST_TIMEOUT_MS = 45000;
    var PROGRESS_DURATION_MS = 8000;

    var COPY = {
        'zh-tw': {
            title: { D2C: '同步 iDAS 的 DB 到控制器', C2D: '同步控制器的 DB 到 iDAS' },
            confirm: {
                D2C: '同步後目前控制器上的資料將被覆蓋，確認是否同步？',
                C2D: '同步後目前 iDAS 上的資料將被覆蓋，確認是否同步？'
            },
            syncing: { D2C: '正在上傳資料至控制器…', C2D: '正在儲存控制器資料…' },
            checking: '正在確認控制器狀態…', success: '同步完成',
            loginCheck: '無法確認控制器登入狀態', invalid: '伺服器回傳資料格式錯誤',
            failed: '同步失敗，請稍後再試', timeout: '同步請求逾時，後端可能仍在處理，請稍後確認資料。',
            ok: '確定', cancel: '取消'
        },
        'zh-cn': {
            title: { D2C: '同步 iDAS 的数据库到控制器', C2D: '同步控制器的数据库到 iDAS' },
            confirm: {
                D2C: '同步后目前控制器上的资料将被覆盖，确认是否同步？',
                C2D: '同步后目前 iDAS 上的资料将被覆盖，确认是否同步？'
            },
            syncing: { D2C: '正在上传资料至控制器…', C2D: '正在储存控制器资料…' },
            checking: '正在确认控制器状态…', success: '同步完成',
            loginCheck: '无法确认控制器登入状态', invalid: '服务器回传资料格式错误',
            failed: '同步失败，请稍后再试', timeout: '同步请求逾时，后端可能仍在处理，请稍后确认资料。',
            ok: '确定', cancel: '取消'
        },
        'en-us': {
            title: { D2C: 'Sync iDAS DB to controller', C2D: 'Sync controller DB to iDAS' },
            confirm: {
                D2C: "The controller's data will be overwritten. Continue?",
                C2D: 'The iDAS data will be overwritten. Continue?'
            },
            syncing: { D2C: 'Uploading data to controller…', C2D: 'Saving controller data…' },
            checking: 'Checking controller status…', success: 'Synchronization completed',
            loginCheck: 'Unable to verify controller login status', invalid: 'Invalid response from server',
            failed: 'Synchronization failed. Please try again later.',
            timeout: 'The request timed out. Processing may still continue on the server; verify the data shortly.',
            ok: 'OK', cancel: 'Cancel'
        }
    };

    function language() {
        var raw = typeof window.getCookie === 'function' ? window.getCookie('language') : '';
        raw = String(raw || 'en-us').toLowerCase();
        if (raw === 'en') raw = 'en-us';
        return COPY[raw] ? raw : 'en-us';
    }

    function parseResponse(response) {
        if (response && typeof response === 'object') return response;
        if (typeof response !== 'string' || !response.trim()) return null;
        try { return JSON.parse(response.trim()); } catch (error) { return null; }
    }

    function responseSuccess(response) {
        if (!response) return false;
        if (response.success === true) return true;
        var type = String(response.res_type || '').toLowerCase();
        return type === 'success' || type === 'ok' || type === 'info';
    }

    function responseMessage(response, fallback) {
        return (response && (response.message || response.res_msg)) || fallback;
    }

    function syncUrl(direction) {
        if (direction === 'D2C') return '?url=Settings/Sync_check_db';
        if (direction === 'C2D') return '?url=Settings/Sync_check_db_load';
        return '';
    }

    function lockButtons(locked) {
        ['load', 'save'].forEach(function (id) {
            var button = document.getElementById(id);
            if (!button) return;
            button.disabled = locked;
            button.classList.toggle('idas-sync-disabled', locked);
            button.setAttribute('aria-busy', locked ? 'true' : 'false');
        });
    }

    function directionIcon(direction) {
        var arrow = direction === 'C2D'
            ? '<path d="M12 16V3m0 0L7 8m5-5 5 5"/>'
            : '<path d="M12 3v13m0 0-5-5m5 5 5-5"/>';
        return '<svg viewBox="0 0 24 24" aria-hidden="true">' + arrow
            + '<path d="M5 14v5a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-5"/></svg>';
    }

    function resultIcon(successful) {
        return successful
            ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>'
            : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8v5m0 3h.01"/><path d="M10.3 4.4 3.2 17a2 2 0 0 0 1.8 3h14a2 2 0 0 0 1.8-3L13.7 4.4a2 2 0 0 0-3.4 0Z"/></svg>';
    }

    function showProgress(direction, text) {
        removeProgress();
        progressValue = 0;
        progressStartedAt = Date.now();
        var overlay = document.createElement('div');
        overlay.id = 'idasSyncOverlay';
        overlay.className = 'idas-sync-overlay';
        overlay.innerHTML = '<div id="idasSyncDialog" class="idas-sync-dialog" data-direction="' + direction + '"'
            + ' role="status" aria-live="polite" aria-atomic="true">'
            + '<div class="idas-sync-icon">' + directionIcon(direction) + '</div>'
            + '<div id="idasSyncText" class="idas-sync-title"></div>'
            + '<div class="idas-sync-track" role="progressbar" aria-label="Synchronization in progress" '
            + 'aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">'
            + '<div class="idas-sync-bar" style="width:0%"></div></div>'
            + '<div id="idasSyncPercent" class="idas-sync-percent">0%</div></div>';
        document.body.appendChild(overlay);
        setProgressText(text);
        startProgressAnimation();
    }

    function updateProgress(value) {
        progressValue = Math.max(0, Math.min(100, Math.round(value)));
        var bar = document.querySelector('#idasSyncDialog .idas-sync-bar');
        var track = document.querySelector('#idasSyncDialog .idas-sync-track');
        var percent = document.getElementById('idasSyncPercent');
        if (bar) bar.style.width = progressValue + '%';
        if (track) track.setAttribute('aria-valuenow', String(progressValue));
        if (percent) percent.textContent = progressValue + '%';
    }

    function startProgressAnimation() {
        stopProgressAnimation();
        updateProgress(0);
        progressTimer = window.setInterval(function () {
            var elapsed = Date.now() - progressStartedAt;
            updateProgress(Math.min(100, (elapsed / PROGRESS_DURATION_MS) * 100));
            if (elapsed >= PROGRESS_DURATION_MS) stopProgressAnimation();
        }, 80);
    }

    function stopProgressAnimation() {
        if (!progressTimer) return;
        window.clearInterval(progressTimer);
        progressTimer = null;
    }

    function setProgressText(text) {
        var element = document.getElementById('idasSyncText');
        if (element) element.textContent = text;
    }

    function finishProgress(state, text) {
        var dialog = document.getElementById('idasSyncDialog');
        if (!dialog) return;
        stopProgressAnimation();
        if (state === 'success') updateProgress(100);
        dialog.classList.add('is-finished', 'is-' + state);
        var icon = dialog.querySelector('.idas-sync-icon');
        if (icon) {
            icon.innerHTML = resultIcon(state === 'success');
        }
        setProgressText(text);
    }

    function removeProgress() {
        stopProgressAnimation();
        if (successDelayTimer) {
            window.clearTimeout(successDelayTimer);
            successDelayTimer = null;
        }
        if (finishTimer) {
            window.clearTimeout(finishTimer);
            finishTimer = null;
        }
        var overlay = document.getElementById('idasSyncOverlay');
        if (overlay) overlay.remove();
    }

    function cleanup(delay, reload) {
        finishTimer = window.setTimeout(function () {
            removeProgress();
            lockButtons(false);
            syncBusy = false;
            activeRequest = null;
            if (reload) window.location.reload();
        }, delay);
    }

    function startSync(direction, copy) {
        setProgressText(copy.syncing[direction]);

        activeRequest = $.ajax({
            url: syncUrl(direction), method: 'POST', data: { argument: direction },
            timeout: REQUEST_TIMEOUT_MS, global: false
        }).done(function (response) {
            var result = parseResponse(response);
            if (!result) {
                finishProgress('failure', copy.invalid);
                cleanup(3200, false);
                return;
            }
            var successful = responseSuccess(result);
            var message = responseMessage(result, successful ? copy.success : copy.failed);
            if (successful) {
                var remaining = Math.max(0, PROGRESS_DURATION_MS - (Date.now() - progressStartedAt));
                successDelayTimer = window.setTimeout(function () {
                    successDelayTimer = null;
                    finishProgress('success', message);
                    cleanup(3200, true);
                }, remaining);
            } else {
                finishProgress('failure', message);
                cleanup(3200, false);
            }
        }).fail(function (xhr, status) {
            var timedOut = status === 'timeout';
            var message = timedOut ? copy.timeout : copy.failed;
            finishProgress(timedOut ? 'timeout' : 'failure', message);
            cleanup(timedOut ? 5000 : 3200, false);
        });
    }

    function confirmAndSync(direction) {
        if (syncBusy || (direction !== 'D2C' && direction !== 'C2D')) return;
        var copy = COPY[language()];
        window.alertify.confirm(copy.title[direction], copy.confirm[direction], function () {
            if (syncBusy) return;
            syncBusy = true;
            lockButtons(true);
            window.alertify.dismissAll();
            window.setTimeout(function () {
                showProgress(direction, copy.checking);

                activeRequest = $.ajax({
                    url: '?url=Settings/get_controller_login', method: 'POST', timeout: 15000, global: false
                }).done(function (response) {
                var result = parseResponse(response);
                if (!result || typeof result.result === 'undefined') {
                    finishProgress('failure', copy.invalid);
                    cleanup(3200, false);
                    return;
                }
                if (!result.result) {
                    var message = responseMessage(result, copy.loginCheck);
                    finishProgress('failure', message);
                    cleanup(3200, false);
                    return;
                }
                startSync(direction, copy);
                }).fail(function (xhr, status) {
                var message = status === 'timeout' ? copy.timeout : copy.loginCheck;
                finishProgress(status === 'timeout' ? 'timeout' : 'failure', message);
                cleanup(status === 'timeout' ? 5000 : 3200, false);
                });
            }, 260);
        }, function () {}).set('labels', { ok: copy.ok, cancel: copy.cancel });
    }

    window.DB_sync_idas = confirmAndSync;
}(window, document, window.jQuery));
