(function (window, document, $) {
    'use strict';

    if (!$ || typeof window.alertify === 'undefined') return;

    var activeRequest = null;
    var syncBusy = false;
    var finishTimer = null;
    var stageTimers = [];
    var progressValue = 0;
    var syncStartedAt = 0;
    var REQUEST_TIMEOUT_MS = 45000;
    var MIN_STAGE_DISPLAY_MS = 1200;

    var COPY = {
        'zh-tw': {
            title: {
                D2C: '更新控制器設定資料',
                C2D: '儲存 iDAS 設定資料'
            },
            confirm: {
                D2C: '目前控制器上的設定資料將被覆蓋，確定要繼續嗎？',
                C2D: '目前 iDAS 上的設定資料將被覆蓋，確定要繼續嗎？'
            },
            stages: {
                D2C: {
                    prepare: '準備設定資料...',
                    controllerCheck: '檢查控制器狀態...',
                    sourceCheck: '檢查來源資料...',
                    processing: '正在更新控制器設定資料...',
                    verifying: '正在驗證控制器設定資料...',
                    complete: '控制器設定資料更新完成'
                },
                C2D: {
                    prepare: '準備設定資料...',
                    controllerCheck: '檢查控制器狀態...',
                    sourceCheck: '檢查來源資料...',
                    processing: '正在儲存設定資料到 iDAS...',
                    verifying: '正在驗證 iDAS 設定資料...',
                    complete: 'iDAS 設定資料儲存完成'
                }
            },
            controllerLoggedIn: '目前控制器尚未登出，請先登出控制器後再進行操作。',
            loginCheck: '無法確認控制器狀態',
            invalid: '伺服器回傳資料格式錯誤',
            failed: '設定資料處理失敗，請稍後再試',
            verifyFailed: '設定資料驗證失敗',
            timeout: '處理逾時，後端可能仍在執行，請稍後確認設定資料。',
            ok: '確定',
            cancel: '取消'
        },
        'zh-cn': {
            title: {
                D2C: '更新控制器配置数据',
                C2D: '保存 iDAS 配置数据'
            },
            confirm: {
                D2C: '目前控制器上的配置数据将被覆盖，确定要继续吗？',
                C2D: '目前 iDAS 上的配置数据将被覆盖，确定要继续吗？'
            },
            stages: {
                D2C: {
                    prepare: '准备配置数据...',
                    controllerCheck: '检查控制器状态...',
                    sourceCheck: '检查来源数据...',
                    processing: '正在更新控制器配置数据...',
                    verifying: '正在验证控制器配置数据...',
                    complete: '控制器配置数据更新完成'
                },
                C2D: {
                    prepare: '准备配置数据...',
                    controllerCheck: '检查控制器状态...',
                    sourceCheck: '检查来源数据...',
                    processing: '正在保存配置数据到 iDAS...',
                    verifying: '正在验证 iDAS 配置数据...',
                    complete: 'iDAS 配置数据保存完成'
                }
            },
            controllerLoggedIn: '目前控制器尚未登出，请先登出控制器后再进行操作。',
            loginCheck: '无法确认控制器状态',
            invalid: '服务器回传数据格式错误',
            failed: '配置数据处理失败，请稍后再试',
            verifyFailed: '配置数据验证失败',
            timeout: '处理超时，后端可能仍在执行，请稍后确认配置数据。',
            ok: '确定',
            cancel: '取消'
        },
        'en-us': {
            title: {
                D2C: 'Update Controller Configuration',
                C2D: 'Save iDAS Configuration'
            },
            confirm: {
                D2C: 'The current controller configuration will be replaced. Continue?',
                C2D: 'The current iDAS configuration will be replaced. Continue?'
            },
            stages: {
                D2C: {
                    prepare: 'Preparing configuration data...',
                    controllerCheck: 'Checking controller status...',
                    sourceCheck: 'Checking source data...',
                    processing: 'Updating controller configuration...',
                    verifying: 'Verifying controller configuration...',
                    complete: 'Controller configuration updated'
                },
                C2D: {
                    prepare: 'Preparing configuration data...',
                    controllerCheck: 'Checking controller status...',
                    sourceCheck: 'Checking source data...',
                    processing: 'Saving configuration data to iDAS...',
                    verifying: 'Verifying iDAS configuration...',
                    complete: 'iDAS configuration saved'
                }
            },
            controllerLoggedIn: 'The controller is still logged in. Log out before continuing.',
            loginCheck: 'Unable to verify controller status',
            invalid: 'Invalid response from server',
            failed: 'Configuration update failed. Please try again later.',
            verifyFailed: 'Configuration verification failed',
            timeout: 'The request timed out. Processing may still continue on the server; verify the configuration shortly.',
            ok: 'OK',
            cancel: 'Cancel'
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

    function loginCheckAllowed(response) {
        if (!response || typeof response.result === 'undefined') return null;
        if (response.result === true || response.result === 1) return true;
        if (response.result === false || response.result === 0) return false;
        var value = String(response.result).trim().toLowerCase();
        if (value === 'true' || value === '1' || value === 'yes') return true;
        if (value === 'false' || value === '0' || value === 'no' || value === '') return false;
        return null;
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

    function showProgress(direction, text, percent) {
        removeProgress();
        progressValue = 0;
        syncStartedAt = Date.now();
        var overlay = document.createElement('div');
        overlay.id = 'idasSyncOverlay';
        overlay.className = 'idas-sync-overlay';
        overlay.innerHTML = '<div id="idasSyncDialog" class="idas-sync-dialog" data-direction="' + direction + '"'
            + ' role="status" aria-live="polite" aria-atomic="true">'
            + '<div class="idas-sync-icon">' + directionIcon(direction) + '</div>'
            + '<div id="idasSyncText" class="idas-sync-title"></div>'
            + '<div class="idas-sync-track" role="progressbar" aria-label="Configuration update progress" '
            + 'aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">'
            + '<div class="idas-sync-bar" style="width:0%"></div></div>'
            + '<div id="idasSyncPercent" class="idas-sync-percent">0%</div></div>';
        document.body.appendChild(overlay);
        setStage(percent || 5, text);
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

    function setProgressText(text) {
        var element = document.getElementById('idasSyncText');
        if (element) element.textContent = text;
    }

    function setStage(percent, text) {
        updateProgress(percent);
        setProgressText(text);
    }

    function clearStageTimers() {
        stageTimers.forEach(function (timer) { window.clearTimeout(timer); });
        stageTimers = [];
    }

    function scheduleProcessingStages(stages) {
        clearStageTimers();
        stageTimers.push(window.setTimeout(function () {
            setStage(55, stages.processing);
        }, 320));
        stageTimers.push(window.setTimeout(function () {
            setStage(82, stages.verifying);
        }, 820));
        stageTimers.push(window.setTimeout(function () {
            // 後端尚未完成前最多停在 90%，100% 僅能由成功回應觸發。
            updateProgress(90);
        }, 1800));
    }

    function finishProgress(state, text) {
        var dialog = document.getElementById('idasSyncDialog');
        if (!dialog) return;
        clearStageTimers();
        if (state === 'success') updateProgress(100);
        dialog.classList.add('is-finished', 'is-' + state);
        var icon = dialog.querySelector('.idas-sync-icon');
        if (icon) icon.innerHTML = resultIcon(state === 'success');
        setProgressText(text);
    }

    function removeProgress() {
        clearStageTimers();
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

    function friendlyFailure(copy, result) {
        var raw = String((result && (result.message || result.res_msg)) || '').toLowerCase();
        var failedStage = String(result && result.failed_stage || '').toLowerCase();
        if (failedStage.indexOf('verify') !== -1 || raw.indexOf('verification') !== -1 || raw.indexOf('integrity') !== -1) {
            return copy.verifyFailed;
        }
        return copy.failed;
    }

    function finalizeAfterMinimumDisplay(callback) {
        var elapsed = Date.now() - syncStartedAt;
        var wait = Math.max(0, MIN_STAGE_DISPLAY_MS - elapsed);
        window.setTimeout(callback, wait);
    }

    function startSync(direction, copy) {
        var stages = copy.stages[direction];
        syncStartedAt = Date.now();
        setStage(25, stages.sourceCheck);
        scheduleProcessingStages(stages);

        activeRequest = $.ajax({
            url: syncUrl(direction),
            method: 'POST',
            data: { argument: direction },
            timeout: REQUEST_TIMEOUT_MS,
            global: false
        }).done(function (response) {
            var result = parseResponse(response);
            if (!result) {
                finalizeAfterMinimumDisplay(function () {
                    finishProgress('failure', copy.invalid);
                    cleanup(3200, false);
                });
                return;
            }

            var successful = responseSuccess(result);
            finalizeAfterMinimumDisplay(function () {
                if (successful) {
                    // 只有後端覆蓋 + 驗證都成功回應後，才允許顯示 100%。
                    finishProgress('success', stages.complete);
                    cleanup(2400, true);
                } else {
                    finishProgress('failure', friendlyFailure(copy, result));
                    cleanup(3200, false);
                }
            });
        }).fail(function (xhr, status) {
            var timedOut = status === 'timeout';
            var message = timedOut ? copy.timeout : copy.failed;
            finalizeAfterMinimumDisplay(function () {
                finishProgress(timedOut ? 'timeout' : 'failure', message);
                cleanup(timedOut ? 5000 : 3200, false);
            });
        });
    }

    function showLoginError(title, message) {
        removeProgress();
        lockButtons(false);
        syncBusy = false;
        activeRequest = null;
        if (window.IdasNotify && typeof window.IdasNotify.show === 'function') {
            window.IdasNotify.show({ title: title, message: message, type: 'error', duration: 6000 });
        } else if (window.IdasNotify && typeof window.IdasNotify.alert === 'function') {
            window.IdasNotify.alert(title, message);
        } else {
            window.alertify.alert(title, message);
        }
    }

    function confirmAndSync(direction) {
        if (syncBusy || (direction !== 'D2C' && direction !== 'C2D')) return;
        var copy = COPY[language()];
        var stages = copy.stages[direction];

        window.alertify.confirm(copy.title[direction], copy.confirm[direction], function () {
            if (syncBusy) return;
            syncBusy = true;
            lockButtons(true);
            window.alertify.dismissAll();

            window.setTimeout(function () {
                showProgress(direction, stages.prepare, 5);

                window.setTimeout(function () {
                    setStage(15, stages.controllerCheck);

                    activeRequest = $.ajax({
                        url: '?url=Settings/get_controller_login',
                        method: 'POST',
                        timeout: 15000,
                        global: false
                    }).done(function (response) {
                        var result = parseResponse(response);
                        var allowed = loginCheckAllowed(result);
                        if (allowed === null) {
                            showLoginError(copy.title[direction], copy.invalid);
                            return;
                        }
                        if (!allowed) {
                            var message = Number(result.login) === 1
                                ? copy.controllerLoggedIn
                                : ((result && (result.message || result.res_msg)) || copy.loginCheck);
                            showLoginError(copy.title[direction], message);
                            return;
                        }
                        startSync(direction, copy);
                    }).fail(function (xhr, status) {
                        var message = status === 'timeout' ? copy.timeout : copy.loginCheck;
                        showLoginError(copy.title[direction], message);
                    });
                }, 180);
            }, 220);
        }, function () {}).set('labels', { ok: copy.ok, cancel: copy.cancel });
    }

    window.DB_sync_idas = confirmAndSync;
}(window, document, window.jQuery));
