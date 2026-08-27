
/* ===== Shared NTCS / i-controller Protocol -> Server Port preview ===== */
(function installControllerProtocolPortPreview() {
  function getPortInput() {
    return document.getElementById('controller_server_port');
  }

  function resolvePort(protocol, input) {
    if (String(protocol) === '0') return 502;
    if (String(protocol) === '2') return 4545;

    const rtuPort = Number(input?.dataset?.rtuPort || input?.value || 502);
    return Number.isInteger(rtuPort) && rtuPort >= 1 && rtuPort <= 65535
      ? rtuPort
      : 502;
  }

  function refreshControllerProtocolServerPort() {
    const input = getPortInput();
    const selected = document.querySelector('input[name="modbus_type"]:checked');

    if (!input || !selected) return;

    const protocol = String(selected.value);

    if (window.IS_ICONTROLLER) {
      /*
       * i-controller owns Protocol / Server Port editing.
       * Only i-controller applies protocol-based frontend defaults.
       */
      input.value = String(resolvePort(protocol, input));

      const opLocked = protocol === '2';
      input.readOnly = opLocked;
      input.setAttribute('aria-readonly', opLocked ? 'true' : 'false');
      input.classList.toggle('protocol-server-port-editable', !opLocked);
      input.classList.toggle('protocol-server-port-locked', opLocked);
    } else {
      /*
       * NTCS is Controller-master.
       * IMPORTANT: never rewrite the PHP/DB-rendered Server Port here.
       * Example: Controller/iDAS DB Port = 501 must stay 501, not be reset to TCP default 502.
       */
      input.readOnly = true;
      input.setAttribute('aria-readonly', 'true');
      input.classList.remove('protocol-server-port-editable');
      input.classList.add('protocol-server-port-locked');
    }
  }

  document.addEventListener('change', function(event) {
    const target = event.target;
    if (target?.matches?.('input[name="modbus_type"]')) {
      refreshControllerProtocolServerPort();
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshControllerProtocolServerPort, { once: true });
  } else {
    refreshControllerProtocolServerPort();
  }

  window.refreshControllerProtocolServerPort = refreshControllerProtocolServerPort;
})();

// iController performs verified DB synchronization followed by an automatic
// five-second reboot. It must not restore NTCS/manual-reboot Banner state.
if (window.IS_ICONTROLLER) {
  try {
    localStorage.removeItem('idas_device_identity_wait_manual_reboot_v2');
  } catch (_) {}
  document.addEventListener('DOMContentLoaded', function () {
    var legacyBanner = document.getElementById('rebootBanner');
    if (legacyBanner && legacyBanner.parentNode) {
      legacyBanner.parentNode.removeChild(legacyBanner);
    }
  });
}

/*
 * Single-codebase JavaScript (no eval)
 * /home/kls/upgrade/icontroller = 1 -> i-controller
 * otherwise -> NTCS
 */
if (window.IS_ICONTROLLER) {
$(document).ready(function () {
    /*const sectionMap = {
        "Controller": "Controller_Setting",
        "System": "System_Setting",
        "Barcode": "Barcode_Setting",
        "Connect": "Connect_Setting",
        "Update": "iDas-Update_Setting"
    };

    const lastSection = sessionStorage.getItem('last_section') || 'Controller_Setting';

    $('.divMode').addClass('hidden').removeClass('active');
    $('#' + lastSection).removeClass('hidden').addClass('active');

    $('.button').removeClass('active');
    if (lastSection === 'Controller_Setting') $('#bnt1').addClass('active');
    if (lastSection === 'System_Setting') $('#bnt2').addClass('active');
    if (lastSection === 'Barcode_Setting') $('#bnt3').addClass('active');
    if (lastSection === 'Connect_Setting') $('#bnt4').addClass('active');
    if (lastSection === 'iDas-Update_Setting') $('#bnt5').addClass('active');*/

    getCurrentSystemTime();
    initIdasPackVersionCheck();
});

function suppressRangeHints(on = true) {
  document.documentElement.classList.toggle('suppress-range-hints', !!on);
  // 或者用 body：document.body.classList.toggle('suppress-range-hints', !!on);
}

function change_datetime() {
    var newTime = document.getElementById("newTime").value;
    var language = getCookie('language') || 'default';

    var messages = {
        'zh-tw': {
            'select': '請選擇時間',
            'success': '設定成功',
            'fail': '設定失敗',
            'error': '通訊錯誤，請稍後再試。'
        },
        'zh-cn': {
            'select': '请选择时间',
            'success': '设置成功',
            'fail': '设置失败',
            'error': '通信错误，请稍后再试。'
        },
        'default': {
            'select': 'Please select a time',
            'success': 'Success',
            'fail': 'Failed',
            'error': 'Communication error. Please try again later.'
        }
    };

    var msg = messages[language] || messages['default'];

    if (!newTime) {
        IdasNotify.alert(msg.select);
        return;
    }

    document.getElementById('spinner').style.display = 'block';

    $.ajax({
        type: "POST",
        url: "?url=Settings/edit_system_date",
        data: { datetime: newTime },
        dataType: "json",
        success: function(response) {
            document.getElementById('spinner').style.display = 'none';

            if (response.error) {
                IdasNotify.alert(msg.fail, response.error);
            } else {
                IdasNotify.alert(msg.success, msg.success);
                setTimeout(function () {
                    alertify.closeAll();
                    location.reload(); // ✅ 自動重整
                }, 3000); // ✅ 自動關閉時間：3秒
                document.getElementById('Controller_Setting').style.display = "none";
                document.getElementById('System_Setting').style.display = "block";
            }

        },
        error: function() {
            document.getElementById('spinner').style.display = 'none';
            IdasNotify.alert(msg.fail, msg.error);
        }
    });
}




function getCurrentSystemTime() {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var serverTime = xhr.responseText.trim().replace(/-/g, "/");
            var serverDateTime = new Date(serverTime);
            updateCurrentTime(serverDateTime);
        }
    };
    xhr.open("GET", "?url=Settings/get_system_time", true);
    xhr.send();
}

function updateCurrentTime(serverDateTime) {
    var el = document.getElementById("currentSystemTime");

    function pad(n) {
        return n < 10 ? '0' + n : n;
    }

    function formatTime(date) {
        var year = date.getFullYear();
        var month = pad(date.getMonth() + 1);
        var day = pad(date.getDate());
        var hour = date.getHours();
        var minute = pad(date.getMinutes());
        var second = pad(date.getSeconds());

        let isPM = hour >= 12;
        let period = isPM ? '下午' : '上午';

        // 使用 12 小時制顯示
        let hour12 = hour % 12 || 12;

        return `${year}/${month}/${day} ${period} ${pad(hour12)}:${minute}:${second}`;
    }

    // 初次顯示
    let lastText = formatTime(serverDateTime);
    el.innerText = lastText;

    // 每秒更新一次，但只有在內容變化時才更新畫面，避免閃爍
    setInterval(function () {
        serverDateTime.setSeconds(serverDateTime.getSeconds() + 1);
        let currentText = formatTime(serverDateTime);

        if (el.innerText !== currentText) {
            el.innerText = currentText;
        }
    }, 1000);
}





function getSettingsCookieValue(name) {
  const prefix = encodeURIComponent(name) + '=';
  const cookies = String(document.cookie || '').split(';');
  for (const cookie of cookies) {
    const value = cookie.trim();
    if (value.indexOf(prefix) === 0) {
      try {
        return decodeURIComponent(value.substring(prefix.length));
      } catch (_) {
        return value.substring(prefix.length);
      }
    }
  }
  return '';
}

function getSettingsLanguage() {
  let language = String(
    window.IDAS_LANGUAGE
    || (typeof getCookie === 'function' ? getCookie('language') : '')
    || getSettingsCookieValue('language')
    || document.documentElement.lang
    || navigator.language
    || 'en-us'
  ).trim().toLowerCase().replace(/_/g, '-');

  if (language === 'zh' || language === 'zh-tw' || language.startsWith('zh-hant') || language === 'tw') {
    return 'zh-tw';
  }
  if (language === 'zh-cn' || language.startsWith('zh-hans') || language === 'cn') {
    return 'zh-cn';
  }
  if (language === 'en' || language.startsWith('en-')) {
    return 'en-us';
  }
  return 'en-us';
}
window.getSettingsLanguage = getSettingsLanguage;

// Shared only by iController automatic-restart flows. NTCS keeps its
// controller-master/manual-reboot workflow and never enters this manager.
window.iDASRestartManager = (function () {
  let active = false;

  const esc = (value) => String(value ?? '')
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;').replace(/'/g, '&#039;');

  function start(options) {
    if (window.IS_ICONTROLLER !== true || active || !window.alertify || !window.jQuery) return false;
    active = true;
    const delay = Math.max(1, Number(options.delay) || 5);
    const dialog = IdasNotify.alert();
    const footer = (show) => {
      if (dialog?.elements?.footer) dialog.elements.footer.style.display = show ? '' : 'none';
    };
    const release = () => {
      active = false;
      if (options.saveButton) options.saveButton.disabled = false;
    };
    const countdownHtml = (seconds) => {
      const n = Math.max(0, Number(seconds) || 0);
      const width = Math.max(0, Math.min(100, (n / delay) * 100));
      return '<div style="text-align:center;line-height:1.55">'
        + '<div aria-live="polite" style="font-size:52px;font-weight:700;color:#d2322d;line-height:1.1;margin:4px 0 12px">' + n + '</div>'
        + '<div style="height:8px;background:#e7e7e7;border-radius:8px;overflow:hidden;margin:0 0 14px"><div style="height:100%;width:' + width + '%;background:#d2322d;border-radius:8px;transition:width .2s linear"></div></div>'
        + '<div>' + esc(options.countdown(n)) + '</div></div>';
    };
    const waitingHtml = (message) => '<style>@keyframes idasRestartSpin{to{transform:rotate(360deg)}}</style>'
      + '<div style="text-align:center;line-height:1.55;padding:10px 0"><div role="status" aria-live="polite" style="width:42px;height:42px;margin:2px auto 18px;border:5px solid #dfe4e8;border-top-color:#2b80c5;border-radius:50%;animation:idasRestartSpin .9s linear infinite"></div><div>' + esc(message) + '</div></div>';
    const timeoutHtml = () => '<div style="text-align:center;line-height:1.55;padding:8px 0"><div style="color:#d2322d;margin-bottom:18px">' + esc(options.timeout) + '</div>'
      + '<button type="button" onclick="window.location.reload()" style="padding:9px 18px;margin:4px;border:0;border-radius:5px;background:#2b80c5;color:#fff">' + esc(options.retry) + '</button>'
      + '<button type="button" onclick="window.location.href=\'?url=Dashboards\'" style="padding:9px 18px;margin:4px;border:1px solid #aaa;border-radius:5px;background:#fff;color:#333">' + esc(options.home) + '</button></div>';

    function waitForRecovery() {
      const startedAt = Date.now();
      const basePath = window.location.pathname.endsWith('/') ? window.location.pathname : window.location.pathname.replace(/[^/]*$/, '');
      let offlineObserved = false;
      let finished = false;
      const probe = () => {
        if (finished) return;
        if (Date.now() - startedAt >= 90000) {
          finished = true;
          dialog.setContent(timeoutHtml());
          footer(true);
          release();
          return;
        }
        const origin = offlineObserved && options.targetOrigin ? options.targetOrigin : window.location.origin;
        const image = new Image();
        let settled = false;
        const next = () => { if (!finished) setTimeout(probe, 1000); };
        const probeTimeout = setTimeout(() => {
          if (settled) return;
          settled = true;
          offlineObserved = true;
          next();
        }, 2500);
        image.onerror = () => {
          if (settled || finished) return;
          settled = true; clearTimeout(probeTimeout); offlineObserved = true; next();
        };
        image.onload = () => {
          if (settled || finished) return;
          settled = true; clearTimeout(probeTimeout);
          if (!offlineObserved) { next(); return; }
          if (options.targetOrigin) {
            finished = true;
            dialog.setContent(waitingHtml(options.recovered));
            setTimeout(() => { window.location.href = options.targetOrigin + basePath + '?url=Settings%2Findex'; }, 500);
            return;
          }
          $.ajax({
            url: '?url=Settings/restart_ready&scope=' + encodeURIComponent(options.scope || 'controller'),
            method: 'GET', dataType: 'json', timeout: 2500, cache: false
          }).done((ready) => {
            if (!ready || ready.ready !== true) { next(); return; }
            finished = true;
            dialog.setContent(waitingHtml(options.recovered));
            setTimeout(() => window.location.reload(), 500);
          }).fail(next);
        };
        image.src = origin + basePath + 'img/touch-icon.png?_idas_restart_probe=' + Date.now();
      };
      setTimeout(probe, 500);
    }

    let shown = false;
    dialog.setHeader(options.title);
    dialog.setContent(countdownHtml(delay));
    dialog.set({ closable: false, movable: false, pinnable: false, resizable: false, onshow: function () {
      if (shown) return;
      shown = true;
      footer(false);
      setTimeout(() => $.ajax({ url: options.scheduleUrl, method: 'POST', dataType: 'json', timeout: 5000 })
        .done((scheduled) => {
          if (!scheduled || scheduled.success !== true || scheduled.restart_scheduled !== true) {
            dialog.setContent(esc(options.failed + (scheduled?.res_msg ? ' (' + scheduled.res_msg + ')' : '')));
            footer(true); release(); return;
          }
          if (options.saveButton) options.saveButton.disabled = true;
          // Use a wall-clock deadline; background-tab timer throttling cannot
          // stretch or repeat the five-second countdown.
          const deadline = Date.now() + delay * 1000;
          let last = delay;
          const tick = () => {
            const remaining = Math.max(0, Math.ceil((deadline - Date.now()) / 1000));
            if (remaining !== last) { last = remaining; dialog.setContent(countdownHtml(remaining)); }
            if (remaining <= 0) { dialog.setContent(waitingHtml(options.rebooting)); waitForRecovery(); return; }
            setTimeout(tick, 200);
          };
          tick();
        }).fail((xhr) => {
          const serverMessage = xhr?.responseJSON?.res_msg || '';
          dialog.setContent(esc(options.failed + (serverMessage ? ' (' + serverMessage + ')' : '')));
          footer(true); release();
        }), 100);
    }});
    dialog.show();
    return true;
  }
  return { start: start, isActive: () => active };
})();

function getControllerIdentityRestartMessages(language, seconds, result) {
  const delay = Number.isFinite(Number(seconds)) && Number(seconds) > 0 ? Math.round(Number(seconds)) : 5;
  const idChanged = result?.device_id_changed === true || result?.device_id_changed === 'true' || result?.device_id_changed == 1;
  const protocolChanged = result?.modbus_type_changed === true || result?.modbus_type_changed === 'true' || result?.modbus_type_changed == 1;
  const portChanged = result?.server_port_changed === true || result?.server_port_changed === 'true' || result?.server_port_changed == 1;
  const oldId = result?.old_device_id ?? '';
  const newId = result?.new_device_id ?? '';
  const protocolName = (v) => ({0:'TCP', 1:'RTU', 2:'OP'}[Number(v)] ?? String(v ?? ''));
  const oldProtocol = protocolName(result?.old_modbus_type);
  const newProtocol = protocolName(result?.new_modbus_type);
  const oldPort = result?.old_server_port ?? '';
  const newPort = result?.server_port ?? '';

  function buildDetail(lang) {
    const parts = [];
    if (idChanged) {
      parts.push(lang === 'zh-cn'
        ? `控制器 ID 已由 ${oldId} 变更为 ${newId}`
        : lang === 'zh-tw'
          ? `控制器 ID 已由 ${oldId} 變更為 ${newId}`
          : `Controller ID changed from ${oldId} to ${newId}`);
    }
    if (protocolChanged) {
      parts.push(lang === 'zh-cn'
        ? `通讯协议已由 ${oldProtocol} 变更为 ${newProtocol}`
        : lang === 'zh-tw'
          ? `通訊協議已由 ${oldProtocol} 變更為 ${newProtocol}`
          : `communication protocol changed from ${oldProtocol} to ${newProtocol}`);
    }
    if (portChanged) {
      parts.push(lang === 'zh-cn'
        ? `Server Port 已由 ${oldPort} 变更为 ${newPort}`
        : lang === 'zh-tw'
          ? `Server Port 已由 ${oldPort} 變更為 ${newPort}`
          : `Server Port changed from ${oldPort} to ${newPort}`);
    }
    if (!parts.length) return '';
    return parts.join(lang === 'en-us' ? ', ' : '，') + (lang === 'en-us' ? '.' : '。');
  }

  const detail = {
    'zh-tw': buildDetail('zh-tw'),
    'zh-cn': buildDetail('zh-cn'),
    'en-us': buildDetail('en-us')
  };

  const messages = {
    'zh-tw': {
      title: '控制器重新啟動',
      scheduled: detail['zh-tw'] + ` 兩個資料庫已同步更新，控制器將於約 ${delay} 秒後自動重新啟動。`,
      preparing: detail['zh-tw'] + ` 兩個資料庫已同步更新，控制器將在 ${delay} 秒後自動重新啟動。`,
      rebooting: detail['zh-tw'] + ' 控制器正在重新啟動，請稍候。',
      recovered: '控制器已重新上線，正在自動重新載入頁面。',
      timeout: '尚未偵測到控制器重新上線，請確認控制器狀態後重試。',
      retry: '立即重試',
      home: '返回首頁',
      failed: detail['zh-tw'] + ' 兩個資料庫已同步更新，但無法自動排程重新啟動，請手動重新啟動控制器。'
    },
    'zh-cn': {
      title: '控制器重新启动',
      scheduled: detail['zh-cn'] + ` 两个数据库已同步更新，控制器将于约 ${delay} 秒后自动重新启动。`,
      preparing: detail['zh-cn'] + ` 两个数据库已同步更新，控制器将在 ${delay} 秒后自动重新启动。`,
      rebooting: detail['zh-cn'] + ' 控制器正在重新启动，请稍候。',
      recovered: '控制器已重新上线，正在自动重新加载页面。',
      timeout: '尚未检测到控制器重新上线，请确认控制器状态后重试。',
      retry: '立即重试',
      home: '返回首页',
      failed: detail['zh-cn'] + ' 两个数据库已同步更新，但无法自动安排重新启动，请手动重新启动控制器。'
    },
    'en-us': {
      title: 'Controller Restart',
      scheduled: detail['en-us'] + ` Both databases were updated successfully. Controller will restart automatically in about ${delay} seconds.`,
      preparing: detail['en-us'] + ` Both databases were updated successfully. Controller will restart automatically in ${delay} seconds.`,
      rebooting: detail['en-us'] + ' The controller is restarting. Please wait.',
      recovered: 'The controller is online again. Reloading the page automatically.',
      timeout: 'The controller has not returned online. Check its status and try again.',
      retry: 'Retry now',
      home: 'Back to Home',
      failed: detail['en-us'] + ' Both databases were updated successfully, but automatic restart could not be scheduled. Please restart the controller manually.'
    }
  };
  return messages[language] || messages['en-us'];
}

function showControllerRestartDialogAndSchedule(result, saveButton) {
  if (window.IS_ICONTROLLER !== true) return;
  const language = getSettingsLanguage();
  const delay = Number(result?.restart_delay_seconds) > 0
    ? Number(result.restart_delay_seconds)
    : 5;
  const text = getControllerIdentityRestartMessages(language, delay, result);
  const manualBannerV20 = document.getElementById('rebootBanner');
  if (manualBannerV20?.parentNode) manualBannerV20.parentNode.removeChild(manualBannerV20);
  try { localStorage.removeItem('idas_device_identity_wait_manual_reboot_v2'); } catch (_) {}
  if (window.iDASRestartManager) {
    window.iDASRestartManager.start({
      scope: 'controller',
      delay: delay,
      title: text.title,
      countdown: (seconds) => getControllerIdentityRestartMessages(language, seconds, result).preparing,
      rebooting: text.rebooting,
      recovered: text.recovered,
      timeout: text.timeout,
      retry: text.retry,
      home: text.home,
      failed: text.failed,
      scheduleUrl: '?url=Settings/schedule_controller_restart',
      saveButton: saveButton
    });
    return;
  }

  // Compatibility fallback for older pages that did not load the shared
  // manager. Current V20 pages always return above.
  let remaining = delay;
  let timer = null;
  let scheduleStarted = false;

  const escapeRestartHtml = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');

  const countdownContent = (message, seconds) => {
    const safeSeconds = Math.max(0, Number(seconds) || 0);
    const progress = Math.max(0, Math.min(100, (safeSeconds / delay) * 100));
    return `
      <div style="text-align:center;line-height:1.55">
        <div aria-live="polite" aria-label="${safeSeconds}" style="font-size:52px;font-weight:700;color:#d2322d;line-height:1.1;margin:4px 0 12px">${safeSeconds}</div>
        <div style="height:8px;background:#e7e7e7;border-radius:8px;overflow:hidden;margin:0 0 14px">
          <div style="height:100%;width:${progress}%;background:#d2322d;border-radius:8px;transition:width .95s linear"></div>
        </div>
        <div>${escapeRestartHtml(message)}</div>
      </div>`;
  };

  const waitingContent = (message) => `
    <style>@keyframes idasControllerRestartSpin { to { transform: rotate(360deg); } }</style>
    <div style="text-align:center;line-height:1.55;padding:10px 0">
      <div role="status" aria-live="polite" style="width:42px;height:42px;margin:2px auto 18px;border:5px solid #dfe4e8;border-top-color:#2b80c5;border-radius:50%;animation:idasControllerRestartSpin .9s linear infinite"></div>
      <div>${escapeRestartHtml(message)}</div>
    </div>`;

  const timeoutContent = () => `
    <div style="text-align:center;line-height:1.55;padding:8px 0">
      <div style="color:#d2322d;margin-bottom:18px">${escapeRestartHtml(text.timeout)}</div>
      <button type="button" onclick="window.location.reload()" style="padding:9px 18px;margin:4px;border:0;border-radius:5px;background:#2b80c5;color:#fff;cursor:pointer">${escapeRestartHtml(text.retry)}</button>
      <button type="button" onclick="window.location.href='?url=Dashboards'" style="padding:9px 18px;margin:4px;border:1px solid #aaa;border-radius:5px;background:#fff;color:#333;cursor:pointer">${escapeRestartHtml(text.home)}</button>
    </div>`;


  // iController reboots itself. Never leave the NTCS/manual-reboot Banner on
  // screen while showing the automatic restart countdown.
  var manualBanner = document.getElementById('rebootBanner');
  if (manualBanner && manualBanner.parentNode) {
    manualBanner.parentNode.removeChild(manualBanner);
  }
  try {
    localStorage.removeItem('idas_device_identity_wait_manual_reboot_v2');
  } catch (_) {}

  // Configure the lifecycle callback BEFORE show(). Calling
  // IdasNotify.alert(title, message) first may display the modal immediately,
  // causing a subsequently registered onshow handler to be missed.
  const dialog = IdasNotify.alert();
  const setDialogFooterVisible = (visible) => {
    if (dialog?.elements?.footer) {
      dialog.elements.footer.style.display = visible ? '' : 'none';
    }
  };
  const waitForControllerBack = () => {
    const startedAt = Date.now();
    let offlineObserved = false;
    let finished = false;
    const basePath = window.location.pathname.endsWith('/')
      ? window.location.pathname
      : window.location.pathname.replace(/[^/]*$/, '');

    const probe = () => {
      if (finished) return;
      if ((Date.now() - startedAt) >= 90000) {
        finished = true;
        dialog.setContent(timeoutContent());
        return;
      }

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
        dialog.setContent(waitingContent(text.recovered));
        setTimeout(function () { window.location.reload(); }, 800);
      };
      image.onerror = function () {
        if (settled || finished) return;
        settled = true;
        clearTimeout(timeoutId);
        offlineObserved = true;
        next();
      };
      image.src = basePath + 'img/touch-icon.png?_idas_restart_probe=' + Date.now();
    };

    setTimeout(probe, 700);
  };
  dialog.setHeader(text.title);
  dialog.setContent(countdownContent(text.preparing, remaining));
  dialog.set({
    closable: false,
    movable: false,
    pinnable: false,
    resizable: false,
    onshow: function () {
      if (scheduleStarted) return;
      scheduleStarted = true;
      setDialogFooterVisible(false);

      // Let the browser paint the dialog first. Then ask PHP to start the same
      // proven background reboot task used by Network Setting
      // (nohup -> sleep 5 -> sudo systemctl reboot). The visible countdown
      // starts only after PHP confirms that task was launched.
      setTimeout(function () {
        $.ajax({
          url: '?url=Settings/schedule_controller_restart',
          method: 'POST',
          dataType: 'json',
          timeout: 5000,
          success: function (restartResult) {
            if (!restartResult || restartResult.success !== true || restartResult.restart_scheduled !== true) {
              dialog.setContent(text.failed + (restartResult?.res_msg ? ` (${restartResult.res_msg})` : ''));
              setDialogFooterVisible(true);
              if (saveButton) saveButton.disabled = false;
              return;
            }

            if (saveButton) saveButton.disabled = true;
            timer = setInterval(function () {
              remaining -= 1;
              if (remaining <= 0) {
                clearInterval(timer);
                dialog.setContent(waitingContent(text.rebooting));
                waitForControllerBack();
                return;
              }
              dialog.setContent(countdownContent(
                getControllerIdentityRestartMessages(language, remaining, result).preparing,
                remaining
              ));
            }, 1000);
          },
          error: function (xhr) {
            let serverMessage = '';
            try {
              serverMessage = xhr.responseJSON?.res_msg || JSON.parse(xhr.responseText || '{}').res_msg || '';
            } catch (_) {}
            dialog.setContent(text.failed + (serverMessage ? ` (${serverMessage})` : ''));
            setDialogFooterVisible(true);
            if (saveButton) saveButton.disabled = false;
          }
        });
      }, 100);
    }
  });
  dialog.show();
}

function controller_save(){
  // 取值 & 去除前後空白
  const trim = (v) => (v == null ? '' : String(v).trim());

  const control_id_old = trim(document.getElementById('control_id_old')?.value); // 舊ID（'' 代表 NULL）
  const control_id     = trim(document.getElementById('control_id')?.value);     // 螢幕上顯示的 ID（當新ID）
  const control_name   = trim(document.getElementById('control_name')?.value);
  const storage_warning = trim(document.getElementById('storage_warning')?.value);
  const torque_filter   = trim(document.getElementById('torque_filter')?.value);
  const lang_val        = trim(document.getElementById('select_language')?.value);
  const unit_val        = trim(document.getElementById('select_torque_unit')?.value);
  const counting_method_val   = trim(document.querySelector('input[name="counting_method"]:checked')?.value);
  const circular_archive_val  = trim(document.querySelector('input[name="circular_archive"]:checked')?.value);
  const blackout_recovery_val = trim(document.querySelector('input[name="blackout_recovery"]:checked')?.value);
  const buzzer_val            = trim(document.querySelector('input[name="buzzer_mode"]:checked')?.value);
  const modbus_type_val       = trim(document.querySelector('input[name="modbus_type"]:checked')?.value ?? '0');
  const controller_server_port = trim(document.getElementById('controller_server_port')?.value);
  const global_downshift_torque = trim(document.getElementById('global_downshift_torque')?.value);
  const global_downshift_speed  = trim(document.getElementById('global_downshift_speed')?.value);

  // 你的原本驗證
  let check = input_check_setting();
  if (!check) return;

  // NTCS Server Port is editable; i-controller remains readonly.
  const controllerServerPortEl = document.getElementById('controller_server_port');
  if (controllerServerPortEl && !controllerServerPortEl.readOnly) {
    const serverPortNumber = Number(controller_server_port);
    if (!Number.isInteger(serverPortNumber) || serverPortNumber < 1 || serverPortNumber > 65535) {
      IdasNotify.alert('Error', 'Server Port must be between 1 and 65535.');
      return;
    }
  }

  // 只有當使用者真的改了 ID，才送 control_id_new；否則送空字串（後端視為不更改）
  const control_id_new = (control_id !== control_id_old) ? control_id : '';
  const saveButton = document.getElementById('downshift_save');
  if (saveButton?.dataset?.saving === '1') return;
  if (saveButton) {
    saveButton.dataset.saving = '1';
    saveButton.disabled = true;
  }
  let keepSaveDisabled = false;

  $.ajax({
    url: "?url=Settings/control_setting",
    method: "POST",
    data: {
      // 後端會把 '' 視為 NULL（舊ID可為 NULL；新ID空字串代表不改）
      control_id: control_id_old,
      control_id_new: control_id_new,

      control_name: control_name,
      lang_val: lang_val,
      unit_val: unit_val,
      storage_warning: storage_warning,
      torque_filter: torque_filter,
      counting_method: counting_method_val,
      circular_archive: circular_archive_val,
      blackout_recovery: blackout_recovery_val,
      buzzer_mode: buzzer_val,
      modbus_type: modbus_type_val,
      server_port: controller_server_port,
      global_downshift_torque: global_downshift_torque,
      global_downshift_speed: global_downshift_speed
    },
    success: function(response) {
      suppressRangeHints(true);

      let result = response;
      if (typeof result === 'string') {
        try {
          result = JSON.parse(result);
        } catch (error) {
          handleAjaxResponse(response);
          return;
        }
      }

      const responseFlag = (value) => value === true || value === 1 || value === '1' || value === 'true';
      const requestSucceeded = result && (responseFlag(result.success) || result.res_type === 'OK');

      if (requestSucceeded) {
        // 後端已同步驗證兩個 DB；立即把隱藏的 old ID 更新成新 ID，避免自動 reboot 排程失敗時再次 Save 還拿舊 ID 當 WHERE。
        if (result.new_device_id != null && result.new_device_id !== '') {
          const oldIdEl = document.getElementById('control_id_old');
          const idEl = document.getElementById('control_id');
          if (oldIdEl) oldIdEl.value = String(result.new_device_id);
          if (idEl) idEl.value = String(result.new_device_id);
        }
      }

      if (requestSucceeded && result.server_port != null) {
        const portEl = document.getElementById('controller_server_port');
        if (portEl) {
          portEl.value = String(result.server_port);
          if (String(result.modbus_type) === '1') {
            portEl.dataset.rtuPort = String(result.server_port);
          }
        }
      }

      const identityChanged = result && (
        responseFlag(result.device_id_changed)
        || responseFlag(result.modbus_type_changed)
        || responseFlag(result.server_port_changed)
      );
      const shouldAutoRestart = window.IS_ICONTROLLER === true
        && requestSucceeded
        && (responseFlag(result.restart_required) || identityChanged);

      if (shouldAutoRestart) {
        if (!responseFlag(result.both_databases_verified)) {
          IdasNotify.alert('Error', 'Controller/iDAS database verification failed. Restart was cancelled.');
          return;
        }
        keepSaveDisabled = true;
        showControllerRestartDialogAndSchedule(result, saveButton);
        return;
      }

      handleAjaxResponse(typeof response === 'string' ? response : JSON.stringify(response));
    },
    error: function(xhr, status, error) {
      let message = 'There was an issue with the request.';
      try {
        const result = JSON.parse(xhr.responseText || '{}');
        if (result.res_msg) message = result.res_msg;
      } catch (_) {}
      IdasNotify.alert('Error', message);
    },
    complete: function() {
      if (saveButton) {
        delete saveButton.dataset.saving;
        if (!keepSaveDisabled) saveButton.disabled = false;
      }
    }
  });
}

// Inline HTML handlers execute in the global Window scope. A function
// declared inside the i-controller platform block is block-scoped in modern
// browsers, so expose the supported save entry point explicitly.
window.controller_save = controller_save;




function input_check_setting(argument) {

    // —— 一次性注入 CSS：關掉 inline 紅字；灰字範圍提示不受 is-invalid 影響 —— //
    (function ensureNoInlineErrorCSS(){
        var id = 'hide-inline-invalid-feedback-style';
        if (!document.getElementById(id)) {
        var style = document.createElement('style');
        style.id = id;
        style.textContent = `
            .is-invalid ~ .invalid-feedback,
            .was-validated .form-control:invalid ~ .invalid-feedback,
            .was-validated .form-select:invalid ~ .invalid-feedback {
            display: none !important;
            }
            .range-hint.form-text { display: block !important; }
            .is-invalid ~ .range-hint.form-text { display: none !important; }
        `;
        document.head.appendChild(style);
        }
    })();

    // 取得語系
    const getCookieSafe = (name) => {
        try { const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
        catch { return null; }
    };
    let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : (getCookieSafe('language') || 'zh-tw')) || 'zh-tw';
    lang = String(lang).toLowerCase();
    if (lang === 'en') lang = 'en-us';
    if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

    // 字串資源
    const LABELS = {
        'en-us': { control_id: 'Device ID',control_name:'Device Name', storage_warning:'Storage Warning (%)', torque_filter:'Torque Filter', global_downshift_torque:'Downshift Torque', global_downshift_speed:'Downshift Speed' },
        'zh-tw': { control_id: '設備編號',control_name:'設備名稱', storage_warning:'容量警示(%)', torque_filter:'扭力過濾', global_downshift_torque:'降檔扭力', global_downshift_speed:'降檔速' },
        'zh-cn': { control_id: '设备编号',control_name:'设备名称', storage_warning:'容量警示(%)', torque_filter:'扭力过滤', global_downshift_torque:'降档扭力', global_downshift_speed:'降档速度' }
    }[lang];

    const I18N = {
        'en-us': { title:'Warning', ok:'OK', required:'{FIELD} is required.', format:'{FIELD} has invalid format.', range:'{FIELD} is out of range ({RANGE}).' },
        'zh-tw': { title:'警告', ok:'確定', required:'{FIELD} 為必填。', format:'{FIELD} 格式不正確。', range:'{FIELD} 超出範圍（{RANGE}）。' },
        'zh-cn': { title:'警告', ok:'确定', required:'{FIELD} 为必填。', format:'{FIELD} 格式不正确。', range:'{FIELD} 超出范围（{RANGE}）。' }
    }[lang];

    // 也準備 Cancel 文案（以防未來 confirm 使用）
    const OK_TEXT = { 'en-us':'OK', 'zh-tw':'確定', 'zh-cn':'确定' }[lang];
    const CANCEL_TEXT = { 'en-us':'Cancel', 'zh-tw':'取消', 'zh-cn':'取消' }[lang];

    // 先嘗試設定全域（支援的 alertify 版本會吃到）
    try {
        if (window.alertify?.defaults?.glossary) {
        alertify.defaults.glossary.ok = OK_TEXT;
        alertify.defaults.glossary.cancel = CANCEL_TEXT;
        }
    } catch (_) {}

    // —— Hint 工具 —— //
    function getHintEl(el) {
        if (!el) return null;
        const id = el.id;
        let hint = el.parentElement?.querySelector(`[data-hint-for="${id}"]`);
        if (hint) return hint;
        hint = el.parentElement?.querySelector('.range-hint');
        if (hint) return hint;
        let sib = el.nextElementSibling, step = 0;
        while (sib && step < 3) {
        if (sib.matches('.range-hint, .invalid-feedback, .form-text, .help-block, .text-danger')) return sib;
        sib = sib.nextElementSibling; step++;
        }
        return null;
    }
    function neutralizeHint(el) {
        const h = getHintEl(el);
        if (!h) return null;
        h.classList.remove('text-danger','invalid-feedback','d-block');
        h.classList.add('form-text','range-hint');
        h.style.display = 'none';
        h.textContent = '';
        return h;
    }
    function hideHint(el) {
        const h = getHintEl(el) || neutralizeHint(el);
        if (!h) return;
        h.textContent = '';
        h.style.display = 'none';
        h.classList.remove('text-danger','invalid-feedback','d-block');
        h.classList.add('form-text','range-hint');
    }
    function showRangeHint(el, min, max) {
        const h = getHintEl(el) || neutralizeHint(el);
        if (!h) return;
        if (min == null || max == null) { hideHint(el); return; }
        h.textContent = `${min} ~ ${max}`;
        h.style.display = 'block';
        h.classList.remove('text-danger','invalid-feedback','d-block');
        h.classList.add('form-text','range-hint');
    }

    // 規則
    const conditions = [
        { id:'control_id',              label:LABELS.control_id,              pattern:/^\d{0,4}$/,                      min:1,   max:255   },
        { id:'control_name',            label:LABELS.control_name,            pattern:/^[a-zA-Z0-9_\u4E00-\u9FA5\-]+$/, min:null, max:null },
        { id:'storage_warning',         label:LABELS.storage_warning,         pattern:/^\d{0,4}$/,                      min:50,   max:95   },
        { id:'torque_filter',           label:LABELS.torque_filter,           pattern:/^\d{1,3}(\.\d{1,6})?$/,          min:0.0,  max:200  },
        { id:'global_downshift_torque', label:LABELS.global_downshift_torque, pattern:/^\d{0,5}?$/,                     min:0,    max:100  },
        { id:'global_downshift_speed',  label:LABELS.global_downshift_speed,  pattern:/^\d{0,5}?$/,                     min:0,    max:100  },
    ];

    let isFormValid = true;
    const errors = [];
    let firstInvalidEl = null;
    const passedFields = new Set();

    // 先驗證，不顯示 hint
    conditions.forEach((input) => {
        const el = document.getElementById(input.id);
        if (!el) return;

        const value = (el.value || '').trim();

        el.classList.remove('is-invalid');
        hideHint(el);

        const pushErr = (msg) => {
        isFormValid = false;
        errors.push(msg);
        el.classList.add('is-invalid');
        hideHint(el);
        if (!firstInvalidEl) firstInvalidEl = el;
        };

        if (value === '') { pushErr(I18N.required.replace('{FIELD}', input.label)); return; }
        if (!input.pattern.test(value)) { pushErr(I18N.format.replace('{FIELD}', input.label)); return; }

        const num = parseFloat(value);
        if (input.min !== null && !Number.isNaN(num) && num < input.min) {
        const range = (input.min !== null && input.max !== null) ? `${input.min} ~ ${input.max}` : `≥ ${input.min}`;
        pushErr(I18N.range.replace('{FIELD}', input.label).replace('{RANGE}', range)); return;
        }
        if (input.max !== null && !Number.isNaN(num) && num > input.max) {
        const range = (input.min !== null && input.max !== null) ? `${input.min} ~ ${input.max}` : `≤ ${input.max}`;
        pushErr(I18N.range.replace('{FIELD}', input.label).replace('{RANGE}', range)); return;
        }

        passedFields.add(input.id);
    });

    // 驗證後再決定是否顯示範圍
    if (isFormValid) {
        conditions.forEach((input) => {
        if (input.id === 'control_name') return;
        const el = document.getElementById(input.id);
        if (!el) return;
        if (passedFields.has(input.id)) showRangeHint(el, input.min, input.max);
        else hideHint(el);
        });
    } else {
        conditions.forEach((input) => {
        const el = document.getElementById(input.id);
        if (el) hideHint(el);
        });
    }

    // 彈窗顯示錯誤（OK 有語系）
    if (!isFormValid && errors.length > 0) {
        const body = errors.map(e => `<div>${e}</div>`).join('');
        if (!window._alertingSettingsForm) {
        window._alertingSettingsForm = true;
        alertify
            .alert(I18N.title, body, function () {
            try { firstInvalidEl?.focus(); firstInvalidEl?.select?.(); } catch {}
            window._alertingSettingsForm = false;
            })
            .set('labels', { ok: OK_TEXT }); // ★ 單次保險
        }
    }

    return isFormValid;
}




//新增密碼
function save_pwd(){

    var clearSeqPwd = document.getElementById('clearseq_button_pwd').value;
    var clearPwd = document.getElementById('clear_button_pwd').value;
    var confirmPwd = document.getElementById('confirm_button_pwd').value;
    var enablePwd = document.getElementById('enable_button_pwd').value;
    var disablePwd = document.getElementById('disable_button_pwd').value;
    var skipPwd = document.getElementById('skip_button_pwd').value;

    // 驗證函數
    function isValidInput(input) {
        return /^[0-9]{0,4}$/.test(input); // 檢查是否是 0-9 的數字，最多四位
    }

    // 驗證所有欄位
    if (!isValidInput(clearSeqPwd) || 
        !isValidInput(clearPwd) || 
        !isValidInput(confirmPwd) || 
        !isValidInput(enablePwd) || 
        !isValidInput(disablePwd) || 
        !isValidInput(skipPwd)) {
        //IdasNotify.alert("請確保所有欄位都只包含 0-9 的數字，並且最多四位。");
        return; // 如果驗證失敗，停止函式執行
    }
    document.getElementById('spinner').style.display = 'block'; // 顯示加載動畫

    $.ajax({
        url: '?url=Settings/edit_feature_pwd', // 替換為你的伺服器端點
        type: 'POST',
        data: {
            clear_seq: clearSeqPwd,
            clear: clearPwd,
            confirm: confirmPwd,
            enable: enablePwd,
            disable: disablePwd,
            skip: skipPwd
        },
        success: function (responseData) {
            handleAjaxResponse(responseData);
        },
        error: function() {
            IdasNotify.alert("發生錯誤，請重試。");
        }
    });
}

//狀態-顏色設定
function background_save(){
    let  okjob_color_val = document.querySelector('input[name="okjobcolor"]:checked')?.value;
    let  okseq_color_val = document.querySelector('input[name="okseqcolor"]:checked')?.value;

    $.ajax({
        url: '?url=Settings/edit_background_color', 
        type: 'POST',
        data: {
            okjobcolor: okjob_color_val,
            okseqcolor: okseq_color_val
        },
        success: function(response) {
           handleAjaxResponse(response);
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });

}

//設置 	global-downshift
/*function downshift_save(){

    var global_downshift_torque = document.getElementById('global_downshift_torque').value;
    var global_downshift_speed  = document.getElementById('global_downshift_speed').value;

    $.ajax({
        url: '?url=Settings/edit_global_downshift', 
        type: 'POST',
        data: {
            global_downshift_torque: global_downshift_torque,
            global_downshift_speed: global_downshift_speed
        },
        success: function(response) {
            handleAjaxResponse(responseData);
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });

}*/

//barcode mode 選擇
function toggleBarcodeSeq() {
    const barcodeMode = document.getElementById('barcode_mode');
    const barcodeSeq = document.getElementById('barcode_seq');
    const seqContainer = document.getElementById("barcode_select_seq");

    if (barcodeMode.value === '1' || barcodeMode.value === '2') {
        barcodeSeq.disabled = true;
        seqContainer.style.display = 'none';
    } else if (barcodeMode.value === '3') {
        barcodeSeq.disabled = false;
        seqContainer.style.display = 'block';
        //fetchSeqList();  // 自動觸發查詢 SEQ
    } else {
        barcodeSeq.disabled = false;
        seqContainer.style.display = 'block';
    }
}


function Export_SystemConfig() {

    // ⭐ 取得瀏覽器時間 YYYYMMDDHHmmss
    function getBrowserTimestamp() {
        const d = new Date();
        const pad = n => String(n).padStart(2, '0');
        return (
            d.getFullYear() +
            pad(d.getMonth() + 1) +
            pad(d.getDate()) +
            pad(d.getHours()) +
            pad(d.getMinutes()) +
            pad(d.getSeconds())
        );
    }

    const client_ts = getBrowserTimestamp();

    var xhr = new XMLHttpRequest();
    xhr.responseType = "blob";

    xhr.onload = function () {

        /* ===============================
         * 1) HTTP status check
         * =============================== */
        if (xhr.status !== 200) {
            IdasNotify.alert("Export failed (HTTP " + xhr.status + "). Please check controller / Modbus.");
            return;
        }

        /* ===============================
         * 2) Content-Type check
         * =============================== */
        var ct = (xhr.getResponseHeader("Content-Type") || "").toLowerCase();
        if (!ct.includes("application/zip")) {
            try {
                xhr.response.text().then(function (msg) {
                    IdasNotify.alert("Export failed: " + (msg || "response is not a ZIP file"));
                });
            } catch (e) {
                IdasNotify.alert("Export failed: response is not a ZIP file.");
            }
            return;
        }

        /* ===============================
         * 3) Size sanity check
         * =============================== */
        if (!xhr.response || xhr.response.size < 50) {
            IdasNotify.alert("Export failed: ZIP is too small.");
            return;
        }

        /* ===============================
         * 4) Get filename from header
         * =============================== */
        let filename = "NTCS_Config.zip"; // fallback
        const disposition = xhr.getResponseHeader("Content-Disposition");

        if (disposition) {
            const match = disposition.match(/filename\*=UTF-8''(.+)|filename="?([^"]+)"?/);
            if (match) {
                filename = decodeURIComponent(match[1] || match[2]);
            }
        }

        /* ===============================
         * 5) Download
         * =============================== */
        const blobUrl = window.URL.createObjectURL(xhr.response);
        const a = document.createElement("a");
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(blobUrl);
    };

    xhr.onerror = function () {
        IdasNotify.alert("Export failed: network error.");
    };

    /* ===============================
     * Send request
     * =============================== */
    xhr.open(
        "GET",
        "?url=Settings/export_sysytem_config&client_ts=" + client_ts,
        true
    );
    xhr.send();
}




function Import_SystemConfig() {
    var uploader = document.getElementById("import-file-uploader");
    var import_file = uploader && uploader.files ? uploader.files[0] : null;
    var url = '?url=Settings/Import_Config';

    var language = (getCookie('language') || 'en-us').toLowerCase();
    if (language === 'en') language = 'en-us';

    var msg = {
        'zh-tw': {
            title: '匯入系統資料',
            noFile: '請選擇系統資料檔案。',
            badName: '檔名格式不正確。僅支援 con_<控制器SN>_YYYYMMDDHHMMSS.Lin',
            confirm: '確定要匯入此系統資料嗎？',
            network: '匯入系統資料時發生連線錯誤。'
        },
        'zh-cn': {
            title: '导入系统资料',
            noFile: '请选择系统资料文件。',
            badName: '文件名格式不正确。仅支持 con_<控制器SN>_YYYYMMDDHHMMSS.Lin',
            confirm: '确定要导入此系统资料吗？',
            network: '导入系统资料时发生连接错误。'
        },
        'en-us': {
            title: 'Import System Data',
            noFile: 'Please select a system data file.',
            badName: 'Invalid filename. Only con_<ControllerSN>_YYYYMMDDHHMMSS.Lin is supported.',
            confirm: 'Are you sure you want to import this system data?',
            network: 'A network error occurred while importing system data.'
        }
    };
    var t = msg[language] || msg['en-us'];

    if (!import_file) {
        IdasNotify.alert(t.title, t.noFile);
        return;
    }

    // Must match the same naming convention used by export_sysytem_config().
    // Example: con_NTR211859_20260813141200.Lin
    var fileNamePattern = /^con_[A-Za-z0-9_-]+_\d{14}\.Lin$/;
    if (!fileNamePattern.test(import_file.name)) {
        IdasNotify.alert(t.title, t.badName);
        if (uploader) uploader.value = '';
        return;
    }

    var form = new FormData();
    form.append("file", import_file);

    alertify.confirm(t.confirm, function(result) {
        if (!result) return;

        document.getElementById('spinner').style.display = 'block';

        $.ajax({
            url: url,
            method: "POST",
            data: form,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(responseData) {
                setTimeout(function() {
                    document.getElementById('spinner').style.display = 'none';
                    IdasNotify.alert(responseData.res_type || t.title, responseData.res_msg || '', function() {
                        if (responseData.res_type === 'Success') {
                            history.go(0);
                        }
                    });
                }, 300);
            },
            error: function(xhr) {
                document.getElementById('spinner').style.display = 'none';
                var responseData = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                IdasNotify.alert(
                    responseData && responseData.res_type ? responseData.res_type : 'Error',
                    responseData && responseData.res_msg ? responseData.res_msg : t.network
                );
            }
        });
    });
}

function Firmware_Update() {
    var uploader = document.getElementById('firmware-file-uploader');
    var bb_file = uploader && uploader.files ? uploader.files[0] : null;
    var button = document.getElementById('firmware-update-btn');
    var language = getCookie('language') || 'en-us';

    var messages = {
        'zh-tw': {
            title: '韌體更新',
            empty: '請先選擇韌體 ZIP 檔案。',
            uploading: '更新指令傳送中…',
            success: '韌體更新指令已成功送至控制器。',
            failed: '韌體更新成功',
            networkError: '韌體更新成功。',
            tooLarge: '韌體 ZIP 檔案不可超過 512 MB。',
            invalidType: '請選擇 .zip 韌體檔案。',
            nameTooLong: '韌體檔名（不含 .zip）不可超過 40 bytes。',
            invalidName: '韌體檔名僅支援可顯示的 ASCII 字元。'
        },
        'zh-cn': {
            title: '固件更新',
            empty: '请先选择固件 ZIP 文件。',
            uploading: '正在发送更新指令…',
            success: '固件更新指令已成功发送至控制器。',
            failed: '固件更新成功',
            networkError: '固件更新成功。',
            tooLarge: '固件 ZIP 文件不可超过 512 MB。',
            invalidType: '请选择 .zip 固件文件。',
            nameTooLong: '固件文件名（不含 .zip）不可超过 40 bytes。',
            invalidName: '固件文件名仅支持可显示的 ASCII 字符。'
        },
        'en-us': {
            title: 'Firmware Update',
            empty: 'Please select a firmware ZIP file first.',
            uploading: 'Sending firmware update command…',
            success: 'Firmware update command was sent to the Controller successfully.',
            failed: 'Firmware update successful',
            networkError: 'Firmware update successful.',
            tooLarge: 'The firmware ZIP file must not exceed 512 MB.',
            invalidType: 'Please select a .zip firmware file.',
            nameTooLong: 'The firmware filename (without .zip) must be 40 bytes or fewer.',
            invalidName: 'The firmware filename must use printable ASCII characters.'
        }
    };
    var msg = messages[language] || messages['en-us'];

    if (!bb_file) {
        IdasNotify.alert(msg.title, msg.empty);
        return;
    }

    // Keep the original firmware filename. Controller R481~R500 carries the filename stem (max 40 bytes).
    var firmwareName = String(bb_file.name || '');
    if (!/\.zip$/i.test(firmwareName)) {
        IdasNotify.alert(msg.failed, idasFormatAlertifyMessage(msg.invalidType));
        return;
    }

    var firmwareStem = firmwareName.replace(/\.zip$/i, '');
    var stemBytes = (typeof TextEncoder !== 'undefined')
        ? new TextEncoder().encode(firmwareStem).length
        : unescape(encodeURIComponent(firmwareStem)).length;

    if (stemBytes > 40) {
        IdasNotify.alert(msg.failed, idasFormatAlertifyMessage(msg.nameTooLong));
        return;
    }

    if (!/^[\x20-\x7E]+$/.test(firmwareStem)) {
        IdasNotify.alert(msg.failed, idasFormatAlertifyMessage(msg.invalidName));
        return;
    }

    // Keep the browser-side limit aligned with PHP: 512 MiB per firmware ZIP.
    var maxFirmwareBytes = 512 * 1024 * 1024;
    if (bb_file.size > maxFirmwareBytes) {
        IdasNotify.alert(msg.failed, idasFormatAlertifyMessage(msg.tooLarge));
        return;
    }

    if (typeof firmwareUploading !== 'undefined' && firmwareUploading) {
        return;
    }

    firmwareUploading = true; // stop polling while firmware request is running
    if (button) {
        button.disabled = true;
        button.dataset.originalText = button.dataset.originalText || button.textContent;
        button.textContent = msg.uploading;
    }
    if (document.getElementById('spinner')) {
        document.getElementById('spinner').style.display = 'block';
    }

    var form = new FormData();
    form.append('file', bb_file);

    function finishFirmwareUpload() {
        firmwareUploading = false;
        if (button) {
            button.disabled = false;
            button.textContent = button.dataset.originalText || button.textContent;
        }
        if (document.getElementById('spinner')) {
            document.getElementById('spinner').style.display = 'none';
        }
        if (uploader) uploader.value = '';
    }

    $.ajax({
        type: 'POST',
        processData: false,
        cache: false,
        contentType: false,
        data: form,
        dataType: 'json',
        url: '?url=Settings/FirmwareUpdate'
    })
    .done(function(result) {
        finishFirmwareUpload();

        if (!result || result.error) {
            var errorText = result && result.error ? String(result.error) : msg.networkError;
            IdasNotify.alert(msg.failed, idasFormatAlertifyMessage(errorText));
            return;
        }

        var detail = msg.success;
        if (result.protocol) {
            detail += '\nProtocol: ' + result.protocol;
        }
        IdasNotify.alert(msg.title, idasFormatAlertifyMessage(detail));
    })
    .fail(function(xhr) {
        finishFirmwareUpload();

        var detail = msg.networkError;
        try {
            var parsed = JSON.parse(xhr.responseText || '{}');
            if (parsed && parsed.error) detail = String(parsed.error);
        } catch (_) {
            if (xhr.responseText) detail = String(xhr.responseText);
        }
        IdasNotify.alert(msg.failed, idasFormatAlertifyMessage(detail));
    });
}



function OpenButton(ButtonMode){
    const sections = {
        Controller: 'Controller_Setting',
        System: 'System_Setting',
        Barcode: 'Barcode_Setting',
        Connect: 'Connect_Setting',
        Network: 'Network_Setting',
        Update: 'iDas-Update_Setting'
    };

    const buttons = {
        Controller: 'bnt1',
        System: 'bnt2',
        Barcode: 'bnt3',
        Connect: 'bnt4',
        Network: 'bnt5',
        Update: 'bnt6'
    };

    Object.keys(sections).forEach(function(key){
        const section = document.getElementById(sections[key]);
        const button = document.getElementById(buttons[key]);
        if (section) section.style.display = 'none';
        if (button) button.classList.remove('active');
    });

    if (!sections[ButtonMode] || !buttons[ButtonMode]) {
        console.warn('Unknown ButtonMode:', ButtonMode);
        return;
    }

    const activeSection = document.getElementById(sections[ButtonMode]);
    const activeButton = document.getElementById(buttons[ButtonMode]);
    if (activeSection) activeSection.style.display = '';
    if (activeButton) activeButton.classList.add('active');
}



function getCookie(name) 
{
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1);
        if (c.indexOf(nameEQ) != -1) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function set_max_link(argument) {
    var  max_user = document.getElementById('max_user').value;

    if(max_user){
        $.ajax({
            url: "?url=Admins/EditMaxLink",
            method: "POST",
            data:{ 
                max_user: max_user
            },
            success: function(response) {
                console.log(response);
                IdasNotify.alert(response);
                //history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   

    }

}

function set_agent_ip_22(){
    var agent_server_ip = document.getElementById('agent_server_ip').value;
    agent_server_ip = agent_server_ip.replace(/\s*/g,""); 
    if(agent_server_ip){
        $.ajax({
            url: "?url=Admins/SetAgentIp",
            method: "POST",
            data:{ 
                ip: agent_server_ip
            },
            success: function(response) {
                console.log(response);
                IdasNotify.alert(response);
    
            },
            error: function(xhr, status, error) {
                
            }
        });   

    }

}



function set_agent_type(argument) {
    var  agent_type = document.querySelector('input[name="agent_type"]:checked').value;
    if(agent_type ){
        $.ajax({
            url: "?url=Admins/SetAgentType",
            method: "POST",
            data:{ 
                agent_type: agent_type
            },
            success: function(response) {
                console.log(response);
                IdasNotify.alert(response);
                //history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }
 
}

function StatusCheck(action) {
  // 狀態圖示（沿用你的 SVG）
  const work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M9.001.666A8.336 8.336 0 0 0 .668 8.999c0 4.6 3.733 8.334 8.333 8.334s8.334-3.734 8.334-8.334S13.6.666 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Zm-1.666-4.833L5.168 8.666 4.001 9.833l3.334 3.333L14 6.499l-1.166-1.166-5.5 5.5Z" fill="#1E8E3E" fill-rule="evenodd"></path></svg>';
  const not_work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M11.16 5.666 9 7.824 6.843 5.666 5.668 6.841l2.158 2.158-2.158 2.159 1.175 1.175 2.158-2.159 2.159 2.159 1.175-1.175-2.159-2.159 2.159-2.158-1.175-1.175ZM9 .666A8.326 8.326 0 0 0 .668 8.999a8.326 8.326 0 0 0 8.333 8.334 8.326 8.326 0 0 0 8.334-8.334A8.326 8.326 0 0 0 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Z" fill="#D93025" fill-rule="evenodd"></path></svg>';

  // 小工具：把各種回傳（boolean/"true"/"1"/"ok"/"running"...）正規化成布林
  function toBool(v) {
    if (typeof v === 'boolean') return v;
    if (typeof v === 'number')  return v > 0;
    if (typeof v === 'string') {
      const s = v.trim().toLowerCase();
      return ['true','1','ok','on','running','up','yes','y'].includes(s);
    }
    return false;
  }

  // 小工具：安全更新狀態圖示
  function setStatusIcon(elId, isWorking) {
    const el = document.getElementById(elId);
    if (el) el.innerHTML = isWorking ? work_icon : not_work_icon;
  }

  // 依 action 選 URL
  let url = '?url=Admins/AgentTest';
  if (action === 'start') url = '?url=Admins/StartAgent';
  else if (action === 'stop') url = '?url=Admins/CloseAgent';

  $.ajax({
    type: 'POST',
    data: {},
    dataType: 'json',
    url: url,
    beforeSend: function () {
      $('#overlay').removeClass('hidden');
    }
  })
  .done(function (result) {
    setStatusIcon('s_status', toBool(result?.server_status));
    setStatusIcon('c_status', toBool(result?.client_status));
  })
  .fail(function () {
    // 失敗就先標成 not work（也可維持原狀，視需求）
    setStatusIcon('s_status', false);
    setStatusIcon('c_status', false);
  })
  .always(function () {
    $('#overlay').addClass('hidden');
  });
}



window.IDAS_PACK_CHECK = window.IDAS_PACK_CHECK || {
    checked: false,
    success: false,
    is_downgrade: false,
    is_upgrade: false,
    is_same_version: false,
    is_special_switch: false,
    requires_db_rebuild: false,
    requires_confirm: false,
    status_key: '',
    pack_version: '',
    current_version: ''
};

function getIdasUpdateI18n() {
    return window.IDAS_UPDATE_I18N || {};
}

function setIdasRowVisible(rowId, visible) {
    var row = document.getElementById(rowId);
    if (!row) return;
    row.classList.toggle('is-hidden', !visible);
}

function setIdasUploadEnabled(enabled) {
    var btn = document.getElementById('idas-upload-btn');
    if (!btn) return;
    btn.disabled = !enabled;
    btn.classList.toggle('idas-btn-disabled', !enabled);
}

function makeEmptyIdasPackCheckState(checked, success) {
    return {
        checked: !!checked,
        success: !!success,
        is_downgrade: false,
        is_upgrade: false,
        is_same_version: false,
        is_special_switch: false,
        requires_db_rebuild: false,
        requires_confirm: false,
        status_key: '',
        pack_version: '',
        current_version: ''
    };
}

function resetIdasPackCheckUi() {
    window.IDAS_PACK_CHECK = makeEmptyIdasPackCheckState(false, false);
    setIdasUploadEnabled(false);

    setIdasRowVisible('idas-pack-check-row', false);
    setIdasRowVisible('idas-downgrade-row', false);

    var versionEl = document.getElementById('idas-pack-version');
    var statusEl  = document.getElementById('idas-pack-status');
    var riskTitle = document.getElementById('idas-risk-title');

    if (versionEl) versionEl.textContent = '-';
    if (statusEl) {
        statusEl.textContent = '-';
        statusEl.className = '';
    }
    if (riskTitle) riskTitle.textContent = '';
}

function renderIdasPackCheckState(payload) {
    var i18n = getIdasUpdateI18n();
    var versionEl = document.getElementById('idas-pack-version');
    var statusEl  = document.getElementById('idas-pack-status');
    var riskTitle = document.getElementById('idas-risk-title');

    setIdasRowVisible('idas-pack-check-row', true);

    var statusText = i18n.status_same || 'Same version';
    var statusClass = 'same';

    if (payload.is_downgrade) {
        statusText = i18n.status_downgrade || 'Downgrade';
        statusClass = 'downgrade';
    } else if (payload.is_upgrade && payload.is_special_switch) {
        statusText = i18n.status_upgrade_special || 'Upgrade / Special version switch';
        statusClass = 'upgrade-special';
    } else if (payload.is_upgrade) {
        statusText = i18n.status_upgrade || 'Upgrade';
        statusClass = 'upgrade';
    } else if (payload.is_special_switch) {
        statusText = i18n.status_special_switch || 'Special version switch';
        statusClass = 'special';
    }

    if (payload.requires_db_rebuild) {
        statusText += ' / ' + (i18n.db_rebuild_required || 'DB rebuild required');
    }

    if (versionEl) versionEl.textContent = payload.pack_version || '-';
    if (statusEl) {
        statusEl.textContent = statusText;
        statusEl.className = 'idas-version-status ' + statusClass;
    }

    var showRiskNotice = !!(payload.is_downgrade || payload.is_special_switch || payload.requires_db_rebuild);
    setIdasRowVisible('idas-downgrade-row', showRiskNotice);

    if (riskTitle) {
        if (payload.is_downgrade) {
            riskTitle.textContent = i18n.downgrade_detected || '⚠ Downgrade detected';
        } else if (payload.is_special_switch) {
            riskTitle.textContent = i18n.special_switch_detected || '⚠ Special version switch detected';
        } else if (payload.requires_db_rebuild) {
            riskTitle.textContent = i18n.db_rebuild_required || 'DB rebuild required';
        } else {
            riskTitle.textContent = '';
        }
    }

    window.IDAS_PACK_CHECK = {
        checked: true,
        success: true,
        is_downgrade: !!payload.is_downgrade,
        is_upgrade: !!payload.is_upgrade,
        is_same_version: !!payload.is_same_version,
        is_special_switch: !!payload.is_special_switch,
        requires_db_rebuild: !!payload.requires_db_rebuild,
        requires_confirm: !!payload.requires_confirm,
        status_key: payload.status_key || '',
        pack_version: payload.pack_version || '',
        current_version: payload.current_version || '',
        disk_blocked: !!payload.disk_blocked,
        database_free_mb: Number(payload.database_free_mb || 0),
        required_free_mb: Number(payload.required_free_mb || 50)
    };

    if (payload.disk_blocked) {
        statusText += ' / Free ' + Number(payload.database_free_mb || 0).toFixed(1)
            + ' MB, required ' + Number(payload.required_free_mb || 50).toFixed(1) + ' MB';
        if (statusEl) {
            statusEl.textContent = statusText;
            statusEl.className = 'idas-version-status error';
        }
    }
    setIdasUploadEnabled(!payload.disk_blocked);
}

function initIdasPackVersionCheck() {
    var uploader = document.getElementById('file-uploader');
    if (!uploader) return;

    resetIdasPackCheckUi();

    uploader.addEventListener('change', function () {
        resetIdasPackCheckUi();

        var file = uploader.files && uploader.files[0] ? uploader.files[0] : null;
        if (!file) return;

        var i18n = getIdasUpdateI18n();
        var versionEl = document.getElementById('idas-pack-version');
        var statusEl  = document.getElementById('idas-pack-status');

        setIdasRowVisible('idas-pack-check-row', true);
        if (versionEl) versionEl.textContent = '-';
        if (statusEl) {
            statusEl.textContent = i18n.checking || 'Checking package version...';
            statusEl.className = 'idas-version-status checking';
        }

        var form = new FormData();
        form.append('file', file);

        $.ajax({
            url: '?url=Settings/check_idas_pack_version',
            method: 'POST',
            data: form,
            processData: false,
            contentType: false,
            dataType: 'json',
            cache: false,
            success: function (resp) {
                if (resp && resp.success) {
                    renderIdasPackCheckState(resp);
                    return;
                }

                window.IDAS_PACK_CHECK = makeEmptyIdasPackCheckState(true, false);
                setIdasUploadEnabled(false);

                if (statusEl) {
                    statusEl.textContent = (resp && resp.message) ? resp.message : (i18n.check_failed || 'Unable to verify package version.');
                    statusEl.className = 'idas-version-status error';
                }
                setIdasRowVisible('idas-downgrade-row', false);
            },
            error: function (xhr) {
                window.IDAS_PACK_CHECK = makeEmptyIdasPackCheckState(true, false);
                setIdasUploadEnabled(false);

                var msg = i18n.check_failed || 'Unable to verify package version.';
                try {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseText) {
                        msg = xhr.responseText;
                    }
                } catch (e) {}

                if (statusEl) {
                    statusEl.textContent = msg;
                    statusEl.className = 'idas-version-status error';
                }
                setIdasRowVisible('idas-downgrade-row', false);
            }
        });
    });
}

function idasEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function idasStripUpdateBackupLine(message) {
    // 備份仍會建立並寫入後端 log，但不在成功/失敗彈跳視窗顯示完整路徑。
    return String(message || '')
        .replace(/(^|\r?\n)\s*備份檔案：.*(?=\r?\n|$)/g, '')
        .replace(/(^|\r?\n)\s*备份文件：.*(?=\r?\n|$)/g, '')
        .replace(/(^|\r?\n)\s*Backup file:\s*.*(?=\r?\n|$)/gi, '')
        .replace(/^\s+|\s+$/g, '');
}

function idasFormatAlertifyMessage(message) {
    var text = idasStripUpdateBackupLine(message);

    // 後端有些訊息會帶 <br>，先保護換行語意，再進行 HTML escape，
    // 避免長錯誤內容或檔案路徑直接撐破 alertify 視窗。
    text = text.replace(/<br\s*\/?\s*>/gi, '\n');
    text = idasEscapeHtml(text).replace(/\r?\n/g, '<br>');

    return '<div class="idas-update-alert-message">' + text + '</div>';
}

function idasBuildUpdateConfirmHtml(message, language, currentVersion, packVersion) {
    var currentLabel = 'Current version';
    var packLabel = 'Package version';

    if (language === 'zh-tw') {
        currentLabel = '目前版本';
        packLabel = '更新包版本';
    } else if (language === 'zh-cn') {
        currentLabel = '当前版本';
        packLabel = '更新包版本';
    }

    var html = '<div class="idas-update-confirm">';
    html += '<div class="idas-update-confirm-message">' + idasFormatAlertifyMessage(message) + '</div>';

    if (currentVersion || packVersion) {
        html += '<div class="idas-update-confirm-versions">';
        html += '<div class="idas-update-confirm-version-row"><span class="idas-update-confirm-label">' + idasEscapeHtml(currentLabel) + '</span><span class="idas-update-confirm-value">' + idasEscapeHtml(currentVersion || '-') + '</span></div>';
        html += '<div class="idas-update-confirm-version-row"><span class="idas-update-confirm-label">' + idasEscapeHtml(packLabel) + '</span><span class="idas-update-confirm-value">' + idasEscapeHtml(packVersion || '-') + '</span></div>';
        html += '</div>';
    }

    html += '</div>';
    return html;
}

function idasShowDbSchemaMismatchAlert(responseData, language, fallbackTitle) {
    var title = responseData?.res_title || fallbackTitle || 'Error';
    var msg = responseData?.res_msg || '';

    if (!msg) {
        if (language === 'zh-tw') {
            msg = '偵測到降版本，但 Controller DB 與 iDAS DB 的資料庫格式不相容。<br><br>為避免降版本後系統無法正常讀取資料，本次更新已中止，尚未變更系統檔案與資料庫。<br><br>請確認 Controller 與 iDAS 的資料庫版本是否相容，或改用對應版本的更新包後再重新執行。';
        } else if (language === 'zh-cn') {
            msg = '检测到降版本，但 Controller DB 与 iDAS DB 的数据库格式不兼容。<br><br>为避免降版本后系统无法正常读取数据，本次更新已中止，尚未变更系统文件与数据库。<br><br>请确认 Controller 与 iDAS 的数据库版本是否兼容，或改用对应版本的更新包后再重新执行。';
        } else {
            msg = 'Downgrade detected, but the Controller DB format is different from the iDAS DB format.<br><br>To prevent the system from reading incompatible data after downgrade, this update has been stopped. System files and databases have not been changed.<br><br>Please confirm that the Controller DB and iDAS DB versions are compatible, or use a matching update package and try again.';
        }
    }

    msg = idasFormatAlertifyMessage(msg);

    IdasNotify.alert(title, msg, function () {
        setIdasUploadEnabled(true);
    });
}

function idas_update() {
    var uploader = document.getElementById("file-uploader");
    var import_file = uploader && uploader.files ? uploader.files[0] : null;
    var uploadBtn = document.getElementById('idas-upload-btn');
    var url = '?url=Settings/iDas_Update';

    var language = getCookie('language') || 'en-us';
    var title, confirm_text, empty_file_text, upload_error_text, downgrade_confirm_text, special_confirm_text, check_wait_text, check_failed_text, disabled_text, rebuild_text;
    var login_redirect_url = '/idas/public/?url=In';

    if (language === "zh-tw") {
        title = 'IDAS 更新';
        confirm_text = '您確定要導入 IDAS 更新包嗎？';
        downgrade_confirm_text = '系統已自動偵測此更新包為「降版本」。\n\n降版本時，系統會強制重建 iDAS DB，避免舊版本無法讀取新版 DB。\n請確認更新包正確後再執行。\n\n確定要繼續嗎？';
        special_confirm_text = '系統已自動偵測此更新包為「特殊版本切換」。\n\n若目前版本或更新包包含 SA349 等特規版本，請確認更新包正確後再執行。\n\n確定要繼續嗎？';
        empty_file_text = '請先選擇要上傳的更新檔。';
        upload_error_text = '上傳檔案時發生錯誤。';
        check_wait_text = '系統正在自動檢查更新包版本，請稍後再上傳。';
        check_failed_text = '無法確認更新包版本，請重新選擇 .pack 檔案後再上傳。';
        disabled_text = '請先選擇 .pack 檔案，並等待版本檢查完成。';
        rebuild_text = '本次更新會重建 iDAS DB，更新前系統會先建立備份。';
    } else if (language === "zh-cn") {
        title = 'IDAS 更新';
        confirm_text = '您确定要导入 IDAS 更新包吗？';
        downgrade_confirm_text = '系统已自动检测此更新包为「降版本」。\n\n降版本时，系统会强制重建 iDAS DB，避免旧版本无法读取新版 DB。\n请确认更新包正确后再执行。\n\n确定要继续吗？';
        special_confirm_text = '系统已自动检测此更新包为「特殊版本切换」。\n\n若当前版本或更新包包含 SA349 等特规版本，请确认更新包正确后再执行。\n\n确定要继续吗？';
        empty_file_text = '请先选择要上传的更新文件。';
        upload_error_text = '上传文件时发生错误。';
        check_wait_text = '系统正在自动检查更新包版本，请稍后再上传。';
        check_failed_text = '无法确认更新包版本，请重新选择 .pack 文件后再上传。';
        disabled_text = '请先选择 .pack 文件，并等待版本检查完成。';
        rebuild_text = '本次更新会重建 iDAS DB，更新前系统会先建立备份。';
    } else {
        title = 'IDAS UPDATE';
        confirm_text = 'Are you sure you want to import the IDAS update package?';
        downgrade_confirm_text = 'The system detected that this package is a downgrade.\n\nDuring downgrade, the system will forcibly rebuild the iDAS DB to prevent old versions from reading a newer DB format.\nPlease confirm the package before continuing.\n\nContinue?';
        special_confirm_text = 'The system detected a special version switch.\n\nIf the current version or package contains a special branch such as SA349, please confirm the package before continuing.\n\nContinue?';
        empty_file_text = 'Please select a file to upload.';
        upload_error_text = 'An error occurred while uploading the file.';
        check_wait_text = 'The system is checking the package version. Please upload again after the check is complete.';
        check_failed_text = 'Unable to verify the package version. Please select the .pack file again before uploading.';
        disabled_text = 'Please select a .pack file and wait for version verification to complete.';
        rebuild_text = 'This update will rebuild the iDAS DB. The system will create a backup before updating.';
    }

    if (!import_file) {
        IdasNotify.alert(title, empty_file_text);
        return;
    }

    if (uploadBtn && uploadBtn.disabled) {
        IdasNotify.alert(title, disabled_text);
        return;
    }

    var packCheck = window.IDAS_PACK_CHECK || {};
    if (!packCheck.checked) {
        IdasNotify.alert(title, check_wait_text);
        return;
    }

    if (!packCheck.success) {
        IdasNotify.alert(title, check_failed_text);
        return;
    }

    var isDowngrade = !!packCheck.is_downgrade;
    var isSpecial = !!packCheck.is_special_switch;
    var message = isDowngrade ? downgrade_confirm_text : (isSpecial ? special_confirm_text : confirm_text);

    if (packCheck.requires_db_rebuild) {
        message += '\n\n' + rebuild_text;
    }

    var currentText = packCheck.current_version || '';
    var packText = packCheck.pack_version || '';
    var confirmHtml = idasBuildUpdateConfirmHtml(message, language, currentText, packText);

    var form = new FormData();
    form.append("file", import_file);
    // 降版本與特殊版本切換都由前端預檢自動判斷；後端仍會重新比對版本。
    form.append("allow_downgrade", (packCheck.requires_confirm || isDowngrade || isSpecial) ? '1' : '0');

    alertify.confirm(confirmHtml, function (result) {
        if (!result) return;

        // 已在 alertify.confirm 完成風險確認；不再跳出瀏覽器原生 prompt 要求輸入 SWITCH / DOWNGRADE。
        // 後端仍會依 allow_downgrade 與版本狀態再次驗證，保留正式保護機制。

        document.getElementById('spinner').style.display = 'block';
        setIdasUploadEnabled(false);

        $.ajax({
            url: url,
            method: "POST",
            data: form,
            processData: false,
            contentType: false,
            dataType: 'json',
            cache: false,

            success: function (responseData) {
                document.getElementById('spinner').style.display = 'none';

                if (responseData && responseData.error_code === 'DB_SCHEMA_MISMATCH') {
                    idasShowDbSchemaMismatchAlert(responseData, language, title);
                    return;
                }

                var resType = responseData?.res_type || 'Error';
                var resMsg  = responseData?.res_msg || upload_error_text;

                IdasNotify.alert(resType, idasFormatAlertifyMessage(resMsg), function () {
                    if (String(resType).toLowerCase() === 'success') {
                        forceLogoutAllTabs(login_redirect_url);
                    } else {
                        setIdasUploadEnabled(true);
                    }
                });
            },

            error: function (xhr, status, error) {
                document.getElementById('spinner').style.display = 'none';
                setIdasUploadEnabled(true);

                var msg = upload_error_text;

                try {
                    if (xhr.responseJSON && xhr.responseJSON.error_code === 'DB_SCHEMA_MISMATCH') {
                        idasShowDbSchemaMismatchAlert(xhr.responseJSON, language, title);
                        console.error("上傳錯誤：", status, error, xhr.responseText);
                        return;
                    }

                    if (xhr.responseJSON && xhr.responseJSON.res_msg) {
                        msg = xhr.responseJSON.res_msg;
                    } else if (xhr.responseText) {
                        msg = xhr.responseText;
                    }
                } catch (e) {
                    console.warn('parse xhr failed:', e);
                }

                IdasNotify.alert('Error', idasFormatAlertifyMessage(msg));
                console.error("上傳錯誤：", status, error, xhr.responseText);
            }
        });
    });
}


function input_check_savebarcode() {

    let conditions = [
        { id: 'barcode_content',  pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]{1,100}$/, min: null, max: null },
        { id: 'barcode_mask_from', pattern: /^[0-9]+$/, min: 1, max: 54 },
        { id: 'barcode_mask_count', pattern: /^[0-9]+$/, min: 1, max: 100 },

    ];

    let isFormValid = true;

    conditions.forEach(function(input) {
        var element = document.getElementById(input.id);
        if (input.id !== 'barcode_content') {
            element.nextElementSibling.innerHTML = `${input.min} ~ ${input.max}`;
        }

        if (!validateInput(element, input.pattern, input.min, input.max)) {
            isFormValid = false;
        }
    });

    return isFormValid;
}

function validateInput(element, pattern, min, max) {
    let value = element.value.trim();
    let isValid = true;

    // 验证空值
    if (value === "") {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 验证正则
    else if (!pattern.test(value)) {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 验证最小值
    else if (min !== null && parseFloat(value) < min) {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 验证最大值
    else if (max !== null && parseFloat(value) > max) {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 通过验证
    else {
        element.classList.remove("is-invalid");
    }

    return isValid;
}


function update_barcode() {

  /* =====================================================
   * 讀取表單欄位（⚠ 一定要放最前面，避免 TDZ）
   * ===================================================== */
  const barcode_id       = document.getElementById("barcode_id")?.value ?? '';
  const barcode_job_old  = document.getElementById("barcode_job_old")?.value ?? ''; // ⭐ 關鍵
  const barcode_name     = document.getElementById("barcode_name")?.value?.trim() ?? '';
  const barcode_from     = document.getElementById("barcode_from")?.value ?? '';
  const barcode_count    = document.getElementById("barcode_count")?.value ?? '';
  const barcode_mode     = document.querySelector("select[name='barcode_mode']")?.value ?? '-1';
  const barcode_job      = document.querySelector("select[name='barcode_job']")?.value ?? '-1';
  const barcode_seq      = document.querySelector("select[name='barcode_seq']")?.value ?? '-1';

  const isEdit = Number(barcode_id) > 0;

  // 🔎 debug（需要時保留）
  console.log('[barcode]', isEdit ? 'EDIT' : 'ADD', {
    barcode_id,
    barcode_job_old,
    barcode_job
  });

  /* =====================================================
   * 語系偵測
   * ===================================================== */
  const getLang = () => {
    try {
      if (typeof getCookie === 'function' && getCookie('language')) {
        return String(getCookie('language')).toLowerCase();
      }
      const htmlLang = document.documentElement.getAttribute('lang');
      if (htmlLang) return String(htmlLang).toLowerCase();
    } catch (_) {}
    return 'en-us';
  };

  const rawLang = getLang();
  const lang =
    rawLang.includes('zh-tw') || rawLang.includes('hant') || rawLang.includes('tw') ||
    rawLang.includes('hk') || rawLang.includes('mo')
      ? 'zh-tw'
      : rawLang.includes('zh-cn') || rawLang.includes('hans') ||
        rawLang.includes('cn') || rawLang.includes('sg')
          ? 'zh-cn'
          : 'en-us';

  const i18n = {
    'en-us': {
      titleInfo: 'Info',
      titleError: 'Error',
      ok: 'OK',
      cancel: 'Cancel',
      v_job: 'Please select a Job.',
      v_seq: 'Please select a Sequence.',
      v_name: 'Please enter a barcode name.',
      ajaxFail: 'Operation failed. Please try again.',
      ajaxParseFail: 'Invalid server response.',
    },
    'zh-tw': {
      titleInfo: '提示',
      titleError: '錯誤',
      ok: '確定',
      cancel: '取消',
      v_job: '請先選擇工作（Job）。',
      v_seq: '請先選擇序列（SEQ）。',
      v_name: '請輸入條碼名稱。',
      ajaxFail: '操作失敗，請稍後再試。',
      ajaxParseFail: '伺服器回傳格式不正確。',
    },
    'zh-cn': {
      titleInfo: '提示',
      titleError: '错误',
      ok: '确定',
      cancel: '取消',
      v_job: '请先选择工作（Job）。',
      v_seq: '请先选择序列（SEQ）。',
      v_name: '请输入条码名称。',
      ajaxFail: '操作失败，请稍后再试。',
      ajaxParseFail: '服务器返回格式不正确。',
    }
  }[lang];

  /* =====================================================
   * alertify 語系
   * ===================================================== */
  try {
    if (alertify?.defaults?.glossary) {
      alertify.defaults.glossary.ok = i18n.ok;
      alertify.defaults.glossary.cancel = i18n.cancel;
      alertify.defaults.glossary.title = i18n.titleInfo;
    } else if (typeof alertify.okBtn === 'function') {
      alertify.okBtn(i18n.ok).cancelBtn(i18n.cancel);
    }
  } catch (_) {}

  /* =====================================================
   * 表單驗證
   * ===================================================== */
  if (barcode_job === "-1") {
    IdasNotify.alert(i18n.titleInfo, i18n.v_job);
    return;
  }

  if (barcode_mode === "3" && barcode_seq === "-1") {
    IdasNotify.alert(i18n.titleInfo, i18n.v_seq);
    return;
  }

  if (!barcode_name) {
    IdasNotify.alert(i18n.titleInfo, i18n.v_name);
    return;
  }

  /* =====================================================
   * Spinner
   * ===================================================== */
  const spinner = document.getElementById('spinner');
  if (spinner) spinner.style.display = 'block';

  const $ = window.jQuery;

  /* =====================================================
   * AJAX
   * ===================================================== */
  $.ajax({
    url: "?url=Settings/Update_Barcode",
    method: "POST",
    data: {
      barcode_id:       barcode_id,
      barcode_name:     barcode_name,
      barcode_from:     barcode_from,
      barcode_count:    barcode_count,
      barcode_job:      barcode_job,
      barcode_job_old:  barcode_job_old, // ⭐⭐⭐ 核心
      barcode_seq:      barcode_seq,
      barcode_mode:     barcode_mode
    },
    success: function (response) {

      let responseData;
      try {
        responseData = (typeof response === 'object') ? response : JSON.parse(response);
      } catch (e) {
        if (spinner) spinner.style.display = 'none';
        IdasNotify.alert(i18n.titleError, i18n.ajaxParseFail);
        return;
      }

      setTimeout(() => {
        if (spinner) spinner.style.display = 'none';

        const resType = responseData?.res_type === 'error'
          ? i18n.titleError
          : i18n.titleInfo;

        const resMsg = responseData?.res_msg ?? '';

        IdasNotify.alert(resType, resMsg, () => {
          sessionStorage.setItem('Barcode_Setting', 'block');
          sessionStorage.setItem('Controller_Setting', 'none');
        });

        // 自動刷新列表
        setTimeout(() => {
          alertify.closeAll();

          $.ajax({
            url: "?url=Settings/show_Barcodes",
            method: "GET",
            success: function (html) {
              $('#total_barcodes').html(html);

              // 刷新後，優先重新選取同一筆 rowid；新增模式則不強制選取，避免同 JOB 多筆時選錯。
              if (barcode_id || barcode_job) {
                const $cb = barcode_id
                  ? $('.barcode-check[data-id="' + barcode_id + '"]').first()
                  : $();
                if ($cb.length) {
                  $('.barcode-check').prop('checked', false);
                  $cb.prop('checked', true);
                  $cb.trigger('click');
                }
              }

              if (typeof OpenButton === 'function') {
                OpenButton('Barcode');
              }
            },
            error: function () {
              IdasNotify.alert(i18n.titleError, i18n.ajaxFail);
            }
          });

        }, 3000);

      }, 800);
    },
    error: function (xhr, status, error) {
      if (spinner) spinner.style.display = 'none';
      console.error('[Update_Barcode]', status, error, xhr?.responseText);
      IdasNotify.alert(i18n.titleError, i18n.ajaxFail);
    }
  });
}





function agent_ip_save() {
  const ipEl = document.getElementById('agent_server_ip');
  const feedbackEl = (ipEl?.nextElementSibling && ipEl.nextElementSibling.classList.contains('invalid-feedback'))
    ? ipEl.nextElementSibling
    : null;

  // 清除欄位提示
  if (feedbackEl) {
    feedbackEl.textContent = '';
    feedbackEl.style.display = 'none';
  }

  // 語系
  let lang = (typeof getCookie === 'function' ? getCookie('language') : 'en-us') || 'en-us';
  lang = String(lang).toLowerCase().replace('_', '-');
  if (lang === 'en') lang = 'en-us';
  if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

  const MSG = {
    'en-us': 'Please enter a valid IP address.',
    'zh-tw': '請輸入有效的 IP 地址。',
    'zh-cn': '请输入有效的 IP 地址。'
  };
  const TITLE = { 'en-us': 'Warning', 'zh-tw': '警告', 'zh-cn': '警告' }[lang];
  const OKLBL  = { 'en-us': 'OK', 'zh-tw': '確定', 'zh-cn': '确定' }[lang];
  const CCLBL  = { 'en-us': 'Cancel', 'zh-tw': '取消', 'zh-cn': '取消' }[lang];

  const ip = (ipEl.value || '').trim();
  const ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

  // 先清狀態
  ipEl.classList.remove('is-invalid');

  // ❌ 驗證失敗
  if (!ip || !ipRegex.test(ip)) {
    ipEl.classList.add('is-invalid');

    if (!ipEl._bindInvalidClear) {
      ipEl.addEventListener('input', function onIn() {
        ipEl.classList.remove('is-invalid');
        ipEl.removeEventListener('input', onIn);
        ipEl._bindInvalidClear = false;
      });
      ipEl._bindInvalidClear = true;
    }

    alertify.confirm(
      TITLE,
      MSG[lang],
      function onOk() {
        ipEl.focus();
        ipEl.select?.();
      },
      function onCancel() {
        // 取消時，直接清掉紅框
        ipEl.classList.remove('is-invalid');
      }
    ).set('labels', { ok: OKLBL, cancel: CCLBL });

    return; // 不送出
  }

  // ✅ 驗證通過 → 呼叫後端
  const spinner = document.getElementById('spinner');
  if (spinner) spinner.style.display = 'block';

  $.ajax({
    url: "?url=Admins/SetAgentIp",
    method: "POST",
    data: { agent_server_ip: ip },
    success: function (response) {
      let res = {};
      try { res = ((typeof response === 'string') ? JSON.parse(response) : response) || {}; } catch {}

      if (spinner) spinner.style.display = 'none';

      alertify.confirm(
        res.res_type || 'Info',
        res.res_msg || 'Done.',
        function onOk() {
          sessionStorage.setItem('Connect_Setting', 'block');
          sessionStorage.setItem('Controller_Setting', 'none');
        },
        function onCancel() {
          // 取消時不用做事
        }
      ).set('labels', { ok: OKLBL, cancel: CCLBL });

      setTimeout(() => alertify.closeAll(), 3000);

      if (res.res_number != null) ipEl.value = res.res_number;
    },
    error: function (xhr) {
      if (spinner) spinner.style.display = 'none';
      alertify.confirm(
        'Error',
        (xhr && xhr.responseText) || 'Request failed.',
        null,
        null
      ).set('labels', { ok: OKLBL, cancel: CCLBL });
    }
  });
}



function agent_type_save() {
  const agent_type = document.querySelector('input[name="agent_type"]:checked')?.value;
  if (!agent_type) return;

  // 語系
  let lang = (typeof getCookie === 'function' ? getCookie('language') : 'en-us') || 'en-us';
  lang = String(lang).toLowerCase().replace('_', '-');
  if (lang === 'en') lang = 'en-us';
  if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

  const OKLBL = { 'en-us': 'OK', 'zh-tw': '確定', 'zh-cn': '确定' }[lang];
  const CCLBL = { 'en-us': 'Cancel', 'zh-tw': '取消', 'zh-cn': '取消' }[lang];

  document.querySelector(".main-content").classList.add("overlay-active");
  document.getElementById('spinner').style.display = 'block';

  $.ajax({
    url: "?url=Admins/SetAgentType",
    method: "POST",
    data: { agent_type },
    success: function (response) {
      let res = {};
      try { res = ((typeof response === 'string') ? JSON.parse(response) : response) || {}; } catch {}

      // 改用 confirm → 有 OK + Cancel
      alertify.confirm(
        res.res_type || 'Info',
        res.res_msg || 'Done.',
        function onOk() {
          // OK：保留成功狀態
        },
        function onCancel() {
          // Cancel：可以額外處理，例如還原 radio 按鈕
        }
      ).set('labels', { ok: OKLBL, cancel: CCLBL });

      setTimeout(function () {
        alertify.closeAll();
        document.getElementById('spinner').style.display = 'none';
        document.querySelector(".main-content").classList.remove("overlay-active");
      }, 3000);
    },
    error: function (xhr) {
      document.getElementById('spinner').style.display = 'none';
      document.querySelector(".main-content").classList.remove("overlay-active");

      alertify.confirm(
        'Error',
        (xhr && xhr.responseText) || 'Request failed.',
        null,
        null
      ).set('labels', { ok: OKLBL, cancel: CCLBL });
    }
  });
}


document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('barcode_name');
    if (input) {
        input.addEventListener('input', function() {
            var length = this.value.length;
            document.getElementById('barcode_count').value = length;
        });
    }
});

(function bindBarcodeCheckboxDelegateOnce() {
  if (window.__barcodeDelegateBound) return;
  window.__barcodeDelegateBound = true;

  $(document).on('change', '.barcode-check, input[name="barcode_check"], #barcode_check', function () {
    const cb = this;

    let jobId = cb.dataset?.jobId || cb.getAttribute('data-job-id');
    if (!jobId) {
      const m = (cb.id || '').match(/^barcode_check_(\d+)_/);
      if (m) jobId = m[1];
    }

    console.log('barcode checked jobId=', jobId, 'checked=', cb.checked, cb.dataset);
  });
})();

(function bindBarcodeSingleSelectOnce() {
  if (window.__barcodeSingleSelectBound) return;
  window.__barcodeSingleSelectBound = true;

  document.addEventListener('change', function (e) {
    const cb = e.target;
    if (!cb.matches('.barcode-check')) return;

    // ① 單選：取消其他 checkbox
    if (cb.checked) {
      document.querySelectorAll('.barcode-check').forEach(other => {
        if (other !== cb) other.checked = false;
      });
    } else {
      return; // 取消勾選時不處理
    }

    // ② ⭐ 關鍵：每次都用 data-* 覆寫下方表單
    const ds = cb.dataset;

    // 條碼
    const barcodeEl = document.getElementById('barcode_name');
    if (barcodeEl) barcodeEl.value = ds.barcode ?? '';

    // 從
    const fromEl = document.getElementById('barcode_from');
    if (fromEl) fromEl.value = ds.rangeFrom ?? '';

    // 個數
    const countEl = document.getElementById('barcode_count');
    if (countEl) countEl.value = ds.rangeCount ?? '';

    // 條碼模式
    const modeEl = document.querySelector("select[name='barcode_mode']");
    if (modeEl && ds.barcodeMode !== undefined) {
      modeEl.value = ds.barcodeMode;
    }

    // 工作
    const jobEl = document.querySelector("select[name='barcode_job']");
    if (jobEl && ds.jobId) {
      jobEl.value = ds.jobId;
    }

    // 工序
    const seqEl = document.querySelector("select[name='barcode_seq']");
    if (seqEl) {
      seqEl.value = (ds.seqId !== undefined && ds.seqId !== '-1') ? ds.seqId : '-1';
    }

    // SQLite rowid：不新增 DB 欄位，用 rowid 當單筆 Barcode 識別。
    const idEl = document.getElementById('barcode_id');
    if (idEl) {
      idEl.value = ds.id || ds.barcodeRowid || '';
    }

    // Debug
    console.log('[Barcode switch]', ds.jobId, ds);
  });
})();
} else {
$(document).ready(function () {
    /*const sectionMap = {
        "Controller": "Controller_Setting",
        "System": "System_Setting",
        "Barcode": "Barcode_Setting",
        "Connect": "Connect_Setting",
        "Update": "iDas-Update_Setting"
    };

    const lastSection = sessionStorage.getItem('last_section') || 'Controller_Setting';

    $('.divMode').addClass('hidden').removeClass('active');
    $('#' + lastSection).removeClass('hidden').addClass('active');

    $('.button').removeClass('active');
    if (lastSection === 'Controller_Setting') $('#bnt1').addClass('active');
    if (lastSection === 'System_Setting') $('#bnt2').addClass('active');
    if (lastSection === 'Barcode_Setting') $('#bnt3').addClass('active');
    if (lastSection === 'Connect_Setting') $('#bnt4').addClass('active');
    if (lastSection === 'iDas-Update_Setting') $('#bnt5').addClass('active');*/

    getCurrentSystemTime();

    // 初始化 iDAS 更新包版本檢查，讓選擇 .pack 後可自動啟用上傳按鈕。
    if (typeof initIdasPackVersionCheck === 'function') {
        initIdasPackVersionCheck();
    }
});

function suppressRangeHints(on = true) {
  document.documentElement.classList.toggle('suppress-range-hints', !!on);
  // 或者用 body：document.body.classList.toggle('suppress-range-hints', !!on);
}

function change_datetime() {
    var newTime = document.getElementById("newTime").value;
    var language = getCookie('language') || 'default';

    var messages = {
        'zh-tw': {
            'select': '請選擇時間',
            'success': '設定成功',
            'fail': '設定失敗',
            'error': '通訊錯誤，請稍後再試。'
        },
        'zh-cn': {
            'select': '请选择时间',
            'success': '设置成功',
            'fail': '设置失败',
            'error': '通信错误，请稍后再试。'
        },
        'default': {
            'select': 'Please select a time',
            'success': 'Success',
            'fail': 'Failed',
            'error': 'Communication error. Please try again later.'
        }
    };

    var msg = messages[language] || messages['default'];

    if (!newTime) {
        IdasNotify.alert(msg.select);
        return;
    }

    document.getElementById('spinner').style.display = 'block';

    $.ajax({
        type: "POST",
        url: "?url=Settings/edit_system_date",
        data: { datetime: newTime },
        dataType: "json",
        success: function(response) {
            document.getElementById('spinner').style.display = 'none';

            if (response.error) {
                IdasNotify.alert(msg.fail, response.error);
            } else {
                IdasNotify.alert(msg.success, msg.success);
                setTimeout(function () {
                    alertify.closeAll();
                    location.reload(); // ✅ 自動重整
                }, 3000); // ✅ 自動關閉時間：3秒
                document.getElementById('Controller_Setting').style.display = "none";
                document.getElementById('System_Setting').style.display = "block";
            }

        },
        error: function() {
            document.getElementById('spinner').style.display = 'none';
            IdasNotify.alert(msg.fail, msg.error);
        }
    });
}




function getCurrentSystemTime() {
    var xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var serverTime = xhr.responseText.trim().replace(/-/g, "/");
            var serverDateTime = new Date(serverTime);
            updateCurrentTime(serverDateTime);
        }
    };
    xhr.open("GET", "?url=Settings/get_system_time", true);
    xhr.send();
}

function updateCurrentTime(serverDateTime) {
    var el = document.getElementById("currentSystemTime");

    function pad(n) {
        return n < 10 ? '0' + n : n;
    }

    function formatTime(date) {
        var year = date.getFullYear();
        var month = pad(date.getMonth() + 1);
        var day = pad(date.getDate());
        var hour = date.getHours();
        var minute = pad(date.getMinutes());
        var second = pad(date.getSeconds());

        let isPM = hour >= 12;
        let period = isPM ? '下午' : '上午';

        // 使用 12 小時制顯示
        let hour12 = hour % 12 || 12;

        return `${year}/${month}/${day} ${period} ${pad(hour12)}:${minute}:${second}`;
    }

    // 初次顯示
    let lastText = formatTime(serverDateTime);
    el.innerText = lastText;

    // 每秒更新一次，但只有在內容變化時才更新畫面，避免閃爍
    setInterval(function () {
        serverDateTime.setSeconds(serverDateTime.getSeconds() + 1);
        let currentText = formatTime(serverDateTime);

        if (el.innerText !== currentText) {
            el.innerText = currentText;
        }
    }, 1000);
}




function getSettingsCookieValue(name) {
  const prefix = encodeURIComponent(name) + '=';
  const cookies = String(document.cookie || '').split(';');
  for (const cookie of cookies) {
    const value = cookie.trim();
    if (value.indexOf(prefix) === 0) {
      try {
        return decodeURIComponent(value.substring(prefix.length));
      } catch (_) {
        return value.substring(prefix.length);
      }
    }
  }
  return '';
}
function getSettingsLanguage() {
  let language = String(
    window.IDAS_LANGUAGE
    || (typeof getCookie === 'function' ? getCookie('language') : '')
    || getSettingsCookieValue('language')
    || document.documentElement.lang
    || navigator.language
    || 'en-us'
  ).trim().toLowerCase().replace(/_/g, '-');

  if (language === 'zh' || language === 'zh-tw' || language.startsWith('zh-hant') || language === 'tw') {
    return 'zh-tw';
  }
  if (language === 'zh-cn' || language.startsWith('zh-hans') || language === 'cn') {
    return 'zh-cn';
  }
  if (language === 'en' || language.startsWith('en-')) {
    return 'en-us';
  }
  return 'en-us';
}
function getModbusRestartMessages(language, seconds) {
  const delay = Number.isFinite(Number(seconds)) && Number(seconds) > 0 ? Math.round(Number(seconds)) : 5;
  const messages = {
    'zh-tw': {
      title: '控制器重新啟動',
      scheduled: 'Modbus 類型已變更，設定已儲存。控制器將於約 ' + delay + ' 秒後重新啟動。',
      manual: '通訊協議已變更並儲存。請手動重新啟動控制器，讓新的通訊協議完整套用。'
    },
    'zh-cn': {
      title: '控制器重新启动',
      scheduled: 'Modbus 类型已变更，设置已保存。控制器将于约 ' + delay + ' 秒后重新启动。',
      manual: '通讯协议已变更并保存。请手动重新启动控制器，以完整应用新的通讯协议。'
    },
    'en-us': {
      title: 'Controller Restart',
      scheduled: 'The Modbus type was changed and saved. Controller will restart in about ' + delay + ' seconds.',
      manual: 'The communication protocol was changed and saved. Please restart the controller manually to apply the new protocol completely.'
    }
  };
  return messages[language] || messages['en-us'];
}






function input_check_setting(argument) {

    // —— 一次性注入 CSS：關掉 inline 紅字；灰字範圍提示不受 is-invalid 影響 —— //
    (function ensureNoInlineErrorCSS(){
        var id = 'hide-inline-invalid-feedback-style';
        if (!document.getElementById(id)) {
        var style = document.createElement('style');
        style.id = id;
        style.textContent = `
            .is-invalid ~ .invalid-feedback,
            .was-validated .form-control:invalid ~ .invalid-feedback,
            .was-validated .form-select:invalid ~ .invalid-feedback {
            display: none !important;
            }
            .range-hint.form-text { display: block !important; }
            .is-invalid ~ .range-hint.form-text { display: none !important; }
        `;
        document.head.appendChild(style);
        }
    })();

    // 取得語系
    const getCookieSafe = (name) => {
        try { const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
        catch { return null; }
    };
    let lang = (typeof getLangAndUnit === 'function' ? getLangAndUnit().lang : (getCookieSafe('language') || 'zh-tw')) || 'zh-tw';
    lang = String(lang).toLowerCase();
    if (lang === 'en') lang = 'en-us';
    if (!['en-us','zh-tw','zh-cn'].includes(lang)) lang = 'en-us';

    // 字串資源
    const LABELS = {
        'en-us': { control_id: 'Device ID',control_name:'Device Name', storage_warning:'Storage Warning (%)', torque_filter:'Torque Filter', global_downshift_torque:'Downshift Torque', global_downshift_speed:'Downshift Speed' },
        'zh-tw': { control_id: '設備編號',control_name:'設備名稱', storage_warning:'容量警示(%)', torque_filter:'扭力過濾', global_downshift_torque:'降檔扭力', global_downshift_speed:'降檔速' },
        'zh-cn': { control_id: '设备编号',control_name:'设备名称', storage_warning:'容量警示(%)', torque_filter:'扭力过滤', global_downshift_torque:'降档扭力', global_downshift_speed:'降档速度' }
    }[lang];

    const I18N = {
        'en-us': { title:'Warning', ok:'OK', required:'{FIELD} is required.', format:'{FIELD} has invalid format.', range:'{FIELD} is out of range ({RANGE}).' },
        'zh-tw': { title:'警告', ok:'確定', required:'{FIELD} 為必填。', format:'{FIELD} 格式不正確。', range:'{FIELD} 超出範圍（{RANGE}）。' },
        'zh-cn': { title:'警告', ok:'确定', required:'{FIELD} 为必填。', format:'{FIELD} 格式不正确。', range:'{FIELD} 超出范围（{RANGE}）。' }
    }[lang];

    // 也準備 Cancel 文案（以防未來 confirm 使用）
    const OK_TEXT = { 'en-us':'OK', 'zh-tw':'確定', 'zh-cn':'确定' }[lang];
    const CANCEL_TEXT = { 'en-us':'Cancel', 'zh-tw':'取消', 'zh-cn':'取消' }[lang];

    // 先嘗試設定全域（支援的 alertify 版本會吃到）
    try {
        if (window.alertify?.defaults?.glossary) {
        alertify.defaults.glossary.ok = OK_TEXT;
        alertify.defaults.glossary.cancel = CANCEL_TEXT;
        }
    } catch (_) {}

    // —— Hint 工具 —— //
    function getHintEl(el) {
        if (!el) return null;
        const id = el.id;
        let hint = el.parentElement?.querySelector(`[data-hint-for="${id}"]`);
        if (hint) return hint;
        hint = el.parentElement?.querySelector('.range-hint');
        if (hint) return hint;
        let sib = el.nextElementSibling, step = 0;
        while (sib && step < 3) {
        if (sib.matches('.range-hint, .invalid-feedback, .form-text, .help-block, .text-danger')) return sib;
        sib = sib.nextElementSibling; step++;
        }
        return null;
    }
    function neutralizeHint(el) {
        const h = getHintEl(el);
        if (!h) return null;
        h.classList.remove('text-danger','invalid-feedback','d-block');
        h.classList.add('form-text','range-hint');
        h.style.display = 'none';
        h.textContent = '';
        return h;
    }
    function hideHint(el) {
        const h = getHintEl(el) || neutralizeHint(el);
        if (!h) return;
        h.textContent = '';
        h.style.display = 'none';
        h.classList.remove('text-danger','invalid-feedback','d-block');
        h.classList.add('form-text','range-hint');
    }
    function showRangeHint(el, min, max) {
        const h = getHintEl(el) || neutralizeHint(el);
        if (!h) return;
        if (min == null || max == null) { hideHint(el); return; }
        h.textContent = `${min} ~ ${max}`;
        h.style.display = 'block';
        h.classList.remove('text-danger','invalid-feedback','d-block');
        h.classList.add('form-text','range-hint');
    }

    // 規則
    const conditions = [
        { id:'control_id',              label:LABELS.control_id,              pattern:/^\d{0,4}$/,                      min:1,   max:255   },
        { id:'control_name',            label:LABELS.control_name,            pattern:/^[a-zA-Z0-9_\u4E00-\u9FA5\-]+$/, min:null, max:null },
        { id:'storage_warning',         label:LABELS.storage_warning,         pattern:/^\d{0,4}$/,                      min:50,   max:95   },
        { id:'torque_filter',           label:LABELS.torque_filter,           pattern:/^\d{1,3}(\.\d{1,6})?$/,          min:0.0,  max:200  },
        { id:'global_downshift_torque', label:LABELS.global_downshift_torque, pattern:/^\d{0,5}?$/,                     min:0,    max:100  },
        { id:'global_downshift_speed',  label:LABELS.global_downshift_speed,  pattern:/^\d{0,5}?$/,                     min:0,    max:100  },
    ];

    let isFormValid = true;
    const errors = [];
    let firstInvalidEl = null;
    const passedFields = new Set();

    // 先驗證，不顯示 hint
    conditions.forEach((input) => {
        const el = document.getElementById(input.id);
        if (!el) return;

        const value = (el.value || '').trim();

        el.classList.remove('is-invalid');
        hideHint(el);

        const pushErr = (msg) => {
        isFormValid = false;
        errors.push(msg);
        el.classList.add('is-invalid');
        hideHint(el);
        if (!firstInvalidEl) firstInvalidEl = el;
        };

        if (value === '') { pushErr(I18N.required.replace('{FIELD}', input.label)); return; }
        if (!input.pattern.test(value)) { pushErr(I18N.format.replace('{FIELD}', input.label)); return; }

        const num = parseFloat(value);
        if (input.min !== null && !Number.isNaN(num) && num < input.min) {
        const range = (input.min !== null && input.max !== null) ? `${input.min} ~ ${input.max}` : `≥ ${input.min}`;
        pushErr(I18N.range.replace('{FIELD}', input.label).replace('{RANGE}', range)); return;
        }
        if (input.max !== null && !Number.isNaN(num) && num > input.max) {
        const range = (input.min !== null && input.max !== null) ? `${input.min} ~ ${input.max}` : `≤ ${input.max}`;
        pushErr(I18N.range.replace('{FIELD}', input.label).replace('{RANGE}', range)); return;
        }

        passedFields.add(input.id);
    });

    // 驗證後再決定是否顯示範圍
    if (isFormValid) {
        conditions.forEach((input) => {
        if (input.id === 'control_name') return;
        const el = document.getElementById(input.id);
        if (!el) return;
        if (passedFields.has(input.id)) showRangeHint(el, input.min, input.max);
        else hideHint(el);
        });
    } else {
        conditions.forEach((input) => {
        const el = document.getElementById(input.id);
        if (el) hideHint(el);
        });
    }

    // 彈窗顯示錯誤（OK 有語系）
    if (!isFormValid && errors.length > 0) {
        const body = errors.map(e => `<div>${e}</div>`).join('');
        if (!window._alertingSettingsForm) {
        window._alertingSettingsForm = true;
        alertify
            .alert(I18N.title, body, function () {
            try { firstInvalidEl?.focus(); firstInvalidEl?.select?.(); } catch {}
            window._alertingSettingsForm = false;
            })
            .set('labels', { ok: OK_TEXT }); // ★ 單次保險
        }
    }

    return isFormValid;
}




//新增密碼
function save_pwd(){

    var clearSeqPwd = document.getElementById('clearseq_button_pwd').value;
    var clearPwd = document.getElementById('clear_button_pwd').value;
    var confirmPwd = document.getElementById('confirm_button_pwd').value;
    var enablePwd = document.getElementById('enable_button_pwd').value;
    var disablePwd = document.getElementById('disable_button_pwd').value;
    var skipPwd = document.getElementById('skip_button_pwd').value;

    // 驗證函數
    function isValidInput(input) {
        return /^[0-9]{0,4}$/.test(input); // 檢查是否是 0-9 的數字，最多四位
    }

    // 驗證所有欄位
    if (!isValidInput(clearSeqPwd) || 
        !isValidInput(clearPwd) || 
        !isValidInput(confirmPwd) || 
        !isValidInput(enablePwd) || 
        !isValidInput(disablePwd) || 
        !isValidInput(skipPwd)) {
        //IdasNotify.alert("請確保所有欄位都只包含 0-9 的數字，並且最多四位。");
        return; // 如果驗證失敗，停止函式執行
    }
    document.getElementById('spinner').style.display = 'block'; // 顯示加載動畫

    $.ajax({
        url: '?url=Settings/edit_feature_pwd', // 替換為你的伺服器端點
        type: 'POST',
        data: {
            clear_seq: clearSeqPwd,
            clear: clearPwd,
            confirm: confirmPwd,
            enable: enablePwd,
            disable: disablePwd,
            skip: skipPwd
        },
        success: function (responseData) {
            handleAjaxResponse(responseData);
        },
        error: function() {
            IdasNotify.alert("發生錯誤，請重試。");
        }
    });
}

//狀態-顏色設定
function background_save(){
    let  okjob_color_val = document.querySelector('input[name="okjobcolor"]:checked')?.value;
    let  okseq_color_val = document.querySelector('input[name="okseqcolor"]:checked')?.value;

    $.ajax({
        url: '?url=Settings/edit_background_color', 
        type: 'POST',
        data: {
            okjobcolor: okjob_color_val,
            okseqcolor: okseq_color_val
        },
        success: function(response) {
           handleAjaxResponse(response);
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });

}

//設置 	global-downshift
/*function downshift_save(){

    var global_downshift_torque = document.getElementById('global_downshift_torque').value;
    var global_downshift_speed  = document.getElementById('global_downshift_speed').value;

    $.ajax({
        url: '?url=Settings/edit_global_downshift', 
        type: 'POST',
        data: {
            global_downshift_torque: global_downshift_torque,
            global_downshift_speed: global_downshift_speed
        },
        success: function(response) {
            handleAjaxResponse(responseData);
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });

}*/

//barcode mode 選擇
function toggleBarcodeSeq() {
    const barcodeMode = document.getElementById('barcode_mode');
    const barcodeSeq = document.getElementById('barcode_seq');
    const seqContainer = document.getElementById("barcode_select_seq");

    if (barcodeMode.value === '1' || barcodeMode.value === '2') {
        barcodeSeq.disabled = true;
        seqContainer.style.display = 'none';
    } else if (barcodeMode.value === '3') {
        barcodeSeq.disabled = false;
        seqContainer.style.display = 'block';
        //fetchSeqList();  // 自動觸發查詢 SEQ
    } else {
        barcodeSeq.disabled = false;
        seqContainer.style.display = 'block';
    }
}


function Export_SystemConfig() {

    // ⭐ 取得瀏覽器時間 YYYYMMDDHHmmss
    function getBrowserTimestamp() {
        const d = new Date();
        const pad = n => String(n).padStart(2, '0');
        return (
            d.getFullYear() +
            pad(d.getMonth() + 1) +
            pad(d.getDate()) +
            pad(d.getHours()) +
            pad(d.getMinutes()) +
            pad(d.getSeconds())
        );
    }

    const client_ts = getBrowserTimestamp();

    var xhr = new XMLHttpRequest();
    xhr.responseType = "blob";

    xhr.onload = function () {

        /* ===============================
         * 1) HTTP status check
         * =============================== */
        if (xhr.status !== 200) {
            IdasNotify.alert("Export failed (HTTP " + xhr.status + "). Please check controller / Modbus.");
            return;
        }

        /* ===============================
         * 2) Content-Type check
         * =============================== */
        var ct = (xhr.getResponseHeader("Content-Type") || "").toLowerCase();
        if (!ct.includes("application/zip")) {
            try {
                xhr.response.text().then(function (msg) {
                    IdasNotify.alert("Export failed: " + (msg || "response is not a ZIP file"));
                });
            } catch (e) {
                IdasNotify.alert("Export failed: response is not a ZIP file.");
            }
            return;
        }

        /* ===============================
         * 3) Size sanity check
         * =============================== */
        if (!xhr.response || xhr.response.size < 50) {
            IdasNotify.alert("Export failed: ZIP is too small.");
            return;
        }

        /* ===============================
         * 4) Get filename from header
         * =============================== */
        let filename = "NTCS_Config.zip"; // fallback
        const disposition = xhr.getResponseHeader("Content-Disposition");

        if (disposition) {
            const match = disposition.match(/filename\*=UTF-8''(.+)|filename="?([^"]+)"?/);
            if (match) {
                filename = decodeURIComponent(match[1] || match[2]);
            }
        }

        /* ===============================
         * 5) Download
         * =============================== */
        const blobUrl = window.URL.createObjectURL(xhr.response);
        const a = document.createElement("a");
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(blobUrl);
    };

    xhr.onerror = function () {
        IdasNotify.alert("Export failed: network error.");
    };

    /* ===============================
     * Send request
     * =============================== */
    xhr.open(
        "GET",
        "?url=Settings/export_sysytem_config&client_ts=" + client_ts,
        true
    );
    xhr.send();
}




function getImportConfigTexts() {
    var language = (getCookie('language') || 'en-us').toLowerCase();
    if (language === 'en') language = 'en-us';

    if (language === 'zh-cn') {
        return {
            title: '导入系统资料',
            noFile: '请选择系统设置文件。\n格式：con_<SN>_YYYYMMDDHHMMSS.Lin',
            badName: '文件名称或格式错误。\n只支持：con_<SN>_YYYYMMDDHHMMSS.Lin',
            confirm: '确定要导入这个系统设置文件吗？',
            requestError: '导入系统设置文件时发生错误。'
        };
    }

    if (language === 'zh-tw') {
        return {
            title: '匯入系統資料',
            noFile: '請選擇系統設定檔。\n格式：con_<SN>_YYYYMMDDHHMMSS.Lin',
            badName: '檔案名稱或格式錯誤。\n只支援：con_<SN>_YYYYMMDDHHMMSS.Lin',
            confirm: '確定要匯入這個系統設定檔嗎？',
            requestError: '匯入系統設定檔時發生錯誤。'
        };
    }

    return {
        title: 'Import System Data',
        noFile: 'Please select a system configuration file.\nFormat: con_<SN>_YYYYMMDDHHMMSS.Lin',
        badName: 'Invalid file name or format.\nOnly con_<SN>_YYYYMMDDHHMMSS.Lin is supported.',
        confirm: 'Are you sure you want to import this system configuration file?',
        requestError: 'An error occurred while importing the system configuration file.'
    };
}

function isValidImportConfigFilename(filename) {
    // Export_SystemConfig() 產生的正式格式：con_<SN>_YYYYMMDDHHMMSS.Lin
    // SN 僅允許英數、底線與連字號；時間戳固定 14 碼。
    return /^con_[A-Za-z0-9_-]+_[0-9]{14}\.Lin$/.test(String(filename || ''));
}

function Validate_Import_Config_File(input) {
    var texts = getImportConfigTexts();
    var file = input && input.files ? input.files[0] : null;

    if (!file) return true;

    if (!isValidImportConfigFilename(file.name)) {
        input.value = '';
        IdasNotify.alert(texts.title, texts.badName);
        return false;
    }

    return true;
}

function Import_SystemConfig() {
    var input = document.getElementById('import-file-uploader');
    var import_file = input && input.files ? input.files[0] : null;
    var texts = getImportConfigTexts();

    if (!import_file) {
        IdasNotify.alert(texts.title, texts.noFile);
        return;
    }

    // accept 只是檔案選擇器過濾，仍必須在 JS / PHP 再做一次完整驗證。
    if (!isValidImportConfigFilename(import_file.name)) {
        input.value = '';
        IdasNotify.alert(texts.title, texts.badName);
        return;
    }

    var form = new FormData();
    form.append('file', import_file);

    alertify.confirm(texts.title, texts.confirm, function() {
        document.getElementById('spinner').style.display = 'block';

        $.ajax({
            url: '?url=Settings/Import_Config',
            method: 'POST',
            data: form,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(responseData) {
                setTimeout(function() {
                    document.getElementById('spinner').style.display = 'none';

                    var type = (responseData && responseData.res_type) ? responseData.res_type : 'Error';
                    var msg = (responseData && responseData.res_msg) ? responseData.res_msg : texts.requestError;

                    IdasNotify.alert(type, msg, function() {
                        if (String(type).toLowerCase() === 'success') {
                            history.go(0);
                        }
                    });
                }, 500);
            },
            error: function(xhr) {
                document.getElementById('spinner').style.display = 'none';

                // 後端若有 JSON 錯誤訊息，優先顯示後端內容。
                var msg = texts.requestError;
                if (xhr && xhr.responseJSON && xhr.responseJSON.res_msg) {
                    msg = xhr.responseJSON.res_msg;
                }
                IdasNotify.alert('Error', msg);
            }
        });
    }, function() {
        // Cancel: no action.
    });
}

function Firmware_Update() {

    var bb_file = document.getElementById("firmware-file-uploader").files[0];
    if (!bb_file) return;

    firmwareUploading = true;   // ⭐ 停止 polling

    var form = new FormData();
    form.append("file", bb_file);

    $.ajax({
        type: "POST",
        processData: false,
        cache: false,
        contentType: false,
        data: form,
        dataType: "json",
        url: '?url=Settings/FirmwareUpdate',
    })
    .done(function(result) {
        firmwareUploading = false; // ⭐ 恢復 polling
        document.getElementById("firmware-file-uploader").value = '';
    })
    .fail(function(xhr){
        firmwareUploading = false; // ⭐ 恢復 polling
        console.log('www:',xhr.responseText);
    });
}



function OpenButton(ButtonMode) {
    const sections = {
        "Controller": "Controller_Setting",
        "System": "System_Setting",
        "Barcode": "Barcode_Setting",
        "Connect": "Connect_Setting",
        "Account": "AccountDisplay",
        "AuditLog": "OperationAuditLogDisplay",
        "Update": "iDas-Update_Setting"
    };

    const buttons = {
        "Controller": "bnt1",
        "System": "bnt2",
        "Barcode": "bnt3",
        "Connect": "bnt4",
        "Account": "bnt5",
        "AuditLog": "bnt7",
        "Update": "bnt6"
    };

    Object.keys(sections).forEach(function(key) {
        const sectionEl = document.getElementById(sections[key]);
        const buttonEl = document.getElementById(buttons[key]);

        if (sectionEl) sectionEl.style.display = "none";
        if (buttonEl) buttonEl.classList.remove("active");
    });

    const targetSectionId = sections[ButtonMode];
    const targetButtonId = buttons[ButtonMode];

    if (!targetSectionId || !targetButtonId) {
        console.warn("Unknown ButtonMode:", ButtonMode);
        return;
    }

    const targetSection = document.getElementById(targetSectionId);
    const targetButton = document.getElementById(targetButtonId);

    if (targetSection) targetSection.style.display = "block";
    if (targetButton) targetButton.classList.add("active");

    if (ButtonMode === "Account" && typeof loadSettingAccountUsers === "function") {
        loadSettingAccountUsers();
    }

    if (ButtonMode === "AuditLog" && typeof loadSettingOperationAuditLogs === "function") {
        loadSettingOperationAuditLogs();
    }
}

// Setting page final tab switch alias.
// Account is bnt5, iDAS Update is bnt6, operation_audit_log is bnt7.
window.SettingOpenButtonFinal = OpenButton;
window.OpenButton = OpenButton;

function getCookie(name) 
{
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1);
        if (c.indexOf(nameEQ) != -1) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

function set_max_link(argument) {
    var  max_user = document.getElementById('max_user').value;

    if(max_user){
        $.ajax({
            url: "?url=Admins/EditMaxLink",
            method: "POST",
            data:{ 
                max_user: max_user
            },
            success: function(response) {
                console.log(response);
                IdasNotify.alert(response);
                //history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   

    }

}

function set_agent_ip_22(){
    var agent_server_ip = document.getElementById('agent_server_ip').value;
    agent_server_ip = agent_server_ip.replace(/\s*/g,""); 
    if(agent_server_ip){
        $.ajax({
            url: "?url=Admins/SetAgentIp",
            method: "POST",
            data:{ 
                ip: agent_server_ip
            },
            success: function(response) {
                console.log(response);
                IdasNotify.alert(response);
    
            },
            error: function(xhr, status, error) {
                
            }
        });   

    }

}



function set_agent_type(argument) {
    var  agent_type = document.querySelector('input[name="agent_type"]:checked').value;
    if(agent_type ){
        $.ajax({
            url: "?url=Admins/SetAgentType",
            method: "POST",
            data:{ 
                agent_type: agent_type
            },
            success: function(response) {
                console.log(response);
                IdasNotify.alert(response);
                //history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }
 
}

function StatusCheck(action) {
  // 狀態圖示（沿用你的 SVG）
  const work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M9.001.666A8.336 8.336 0 0 0 .668 8.999c0 4.6 3.733 8.334 8.333 8.334s8.334-3.734 8.334-8.334S13.6.666 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Zm-1.666-4.833L5.168 8.666 4.001 9.833l3.334 3.333L14 6.499l-1.166-1.166-5.5 5.5Z" fill="#1E8E3E" fill-rule="evenodd"></path></svg>';
  const not_work_icon = '<svg height="18" width="18" xmlns="http://www.w3.org/2000/svg"><path clip-rule="evenodd" d="M11.16 5.666 9 7.824 6.843 5.666 5.668 6.841l2.158 2.158-2.158 2.159 1.175 1.175 2.158-2.159 2.159 2.159 1.175-1.175-2.159-2.159 2.159-2.158-1.175-1.175ZM9 .666A8.326 8.326 0 0 0 .668 8.999a8.326 8.326 0 0 0 8.333 8.334 8.326 8.326 0 0 0 8.334-8.334A8.326 8.326 0 0 0 9 .666Zm0 15a6.676 6.676 0 0 1-6.666-6.667A6.676 6.676 0 0 1 9 2.333a6.676 6.676 0 0 1 6.667 6.666A6.676 6.676 0 0 1 9 15.666Z" fill="#D93025" fill-rule="evenodd"></path></svg>';

  // 小工具：把各種回傳（boolean/"true"/"1"/"ok"/"running"...）正規化成布林
  function toBool(v) {
    if (typeof v === 'boolean') return v;
    if (typeof v === 'number')  return v > 0;
    if (typeof v === 'string') {
      const s = v.trim().toLowerCase();
      return ['true','1','ok','on','running','up','yes','y'].includes(s);
    }
    return false;
  }

  // 小工具：安全更新狀態圖示
  function setStatusIcon(elId, isWorking) {
    const el = document.getElementById(elId);
    if (el) el.innerHTML = isWorking ? work_icon : not_work_icon;
  }

  // 依 action 選 URL
  let url = '?url=Admins/AgentTest';
  if (action === 'start') url = '?url=Admins/StartAgent';
  else if (action === 'stop') url = '?url=Admins/CloseAgent';

  $.ajax({
    type: 'POST',
    data: {},
    dataType: 'json',
    url: url,
    beforeSend: function () {
      $('#overlay').removeClass('hidden');
    }
  })
  .done(function (result) {
    setStatusIcon('s_status', toBool(result?.server_status));
    setStatusIcon('c_status', toBool(result?.client_status));
  })
  .fail(function () {
    // 失敗就先標成 not work（也可維持原狀，視需求）
    setStatusIcon('s_status', false);
    setStatusIcon('c_status', false);
  })
  .always(function () {
    $('#overlay').addClass('hidden');
  });
}



/*function idas_update() {

    var ff = document.querySelector('#file-uploader').files;
    var bb = document.getElementById("file-uploader").files[0];
    var form = new FormData();
    form.append("file", bb);

    var url = '?url=Settings/iDas_Update';
    if(url){
        $.ajax({
            type: "POST",
            processData: false,
            cache: false,
            contentType: false,
            data: form,
            dataType: "json",
            url: url,
            success: function(response) {
                console.log(response);
                IdasNotify.alert(response);
                document.getElementById("file-uploader").value = '';
            },
            error: function(xhr, status, error) {
                
            }
        });      
    }
   
}*/


window.IDAS_PACK_CHECK = window.IDAS_PACK_CHECK || {
    checked: false,
    success: false,
    is_downgrade: false,
    is_upgrade: false,
    is_same_version: false,
    is_special_switch: false,
    requires_db_rebuild: false,
    requires_confirm: false,
    status_key: '',
    pack_version: '',
    current_version: ''
};

function getIdasUpdateI18n() {
    return window.IDAS_UPDATE_I18N || {};
}

function setIdasRowVisible(rowId, visible) {
    var row = document.getElementById(rowId);
    if (!row) return;
    row.classList.toggle('is-hidden', !visible);
}

function setIdasUploadEnabled(enabled) {
    var btn = document.getElementById('idas-upload-btn');
    if (!btn) return;
    btn.disabled = !enabled;
    btn.classList.toggle('idas-btn-disabled', !enabled);
}

function makeEmptyIdasPackCheckState(checked, success) {
    return {
        checked: !!checked,
        success: !!success,
        is_downgrade: false,
        is_upgrade: false,
        is_same_version: false,
        is_special_switch: false,
        requires_db_rebuild: false,
        requires_confirm: false,
        status_key: '',
        pack_version: '',
        current_version: ''
    };
}

function resetIdasPackCheckUi() {
    window.IDAS_PACK_CHECK = makeEmptyIdasPackCheckState(false, false);
    setIdasUploadEnabled(false);

    setIdasRowVisible('idas-pack-check-row', false);
    setIdasRowVisible('idas-downgrade-row', false);

    var versionEl = document.getElementById('idas-pack-version');
    var statusEl  = document.getElementById('idas-pack-status');
    var riskTitle = document.getElementById('idas-risk-title');

    if (versionEl) versionEl.textContent = '-';
    if (statusEl) {
        statusEl.textContent = '-';
        statusEl.className = '';
    }
    if (riskTitle) riskTitle.textContent = '';
}

function renderIdasPackCheckState(payload) {
    var i18n = getIdasUpdateI18n();
    var versionEl = document.getElementById('idas-pack-version');
    var statusEl  = document.getElementById('idas-pack-status');
    var riskTitle = document.getElementById('idas-risk-title');

    setIdasRowVisible('idas-pack-check-row', true);

    var statusText = i18n.status_same || 'Same version';
    var statusClass = 'same';

    if (payload.is_downgrade) {
        statusText = i18n.status_downgrade || 'Downgrade';
        statusClass = 'downgrade';
    } else if (payload.is_upgrade && payload.is_special_switch) {
        statusText = i18n.status_upgrade_special || 'Upgrade / Special version switch';
        statusClass = 'upgrade-special';
    } else if (payload.is_upgrade) {
        statusText = i18n.status_upgrade || 'Upgrade';
        statusClass = 'upgrade';
    } else if (payload.is_special_switch) {
        statusText = i18n.status_special_switch || 'Special version switch';
        statusClass = 'special';
    }

    if (payload.requires_db_rebuild) {
        statusText += ' / ' + (i18n.db_rebuild_required || 'DB rebuild required');
    }

    if (versionEl) versionEl.textContent = payload.pack_version || '-';
    if (statusEl) {
        statusEl.textContent = statusText;
        statusEl.className = 'idas-version-status ' + statusClass;
    }

    var showRiskNotice = !!(payload.is_downgrade || payload.is_special_switch || payload.requires_db_rebuild);
    setIdasRowVisible('idas-downgrade-row', showRiskNotice);

    if (riskTitle) {
        if (payload.is_downgrade) {
            riskTitle.textContent = i18n.downgrade_detected || '⚠ Downgrade detected';
        } else if (payload.is_special_switch) {
            riskTitle.textContent = i18n.special_switch_detected || '⚠ Special version switch detected';
        } else if (payload.requires_db_rebuild) {
            riskTitle.textContent = i18n.db_rebuild_required || 'DB rebuild required';
        } else {
            riskTitle.textContent = '';
        }
    }

    window.IDAS_PACK_CHECK = {
        checked: true,
        success: true,
        is_downgrade: !!payload.is_downgrade,
        is_upgrade: !!payload.is_upgrade,
        is_same_version: !!payload.is_same_version,
        is_special_switch: !!payload.is_special_switch,
        requires_db_rebuild: !!payload.requires_db_rebuild,
        requires_confirm: !!payload.requires_confirm,
        status_key: payload.status_key || '',
        pack_version: payload.pack_version || '',
        current_version: payload.current_version || '',
        disk_blocked: !!payload.disk_blocked,
        database_free_mb: Number(payload.database_free_mb || 0),
        required_free_mb: Number(payload.required_free_mb || 50)
    };

    if (payload.disk_blocked) {
        statusText += ' / Free ' + Number(payload.database_free_mb || 0).toFixed(1)
            + ' MB, required ' + Number(payload.required_free_mb || 50).toFixed(1) + ' MB';
        if (statusEl) {
            statusEl.textContent = statusText;
            statusEl.className = 'idas-version-status error';
        }
    }
    setIdasUploadEnabled(!payload.disk_blocked);
}

function initIdasPackVersionCheck() {
    var uploader = document.getElementById('file-uploader');
    if (!uploader) return;

    resetIdasPackCheckUi();

    uploader.addEventListener('change', function () {
        resetIdasPackCheckUi();

        var file = uploader.files && uploader.files[0] ? uploader.files[0] : null;
        if (!file) return;

        var i18n = getIdasUpdateI18n();
        var versionEl = document.getElementById('idas-pack-version');
        var statusEl  = document.getElementById('idas-pack-status');

        setIdasRowVisible('idas-pack-check-row', true);
        if (versionEl) versionEl.textContent = '-';
        if (statusEl) {
            statusEl.textContent = i18n.checking || 'Checking package version...';
            statusEl.className = 'idas-version-status checking';
        }

        var form = new FormData();
        form.append('file', file);

        $.ajax({
            url: '?url=Settings/check_idas_pack_version',
            method: 'POST',
            data: form,
            processData: false,
            contentType: false,
            dataType: 'json',
            cache: false,
            success: function (resp) {
                var resType = String((resp && resp.res_type) ? resp.res_type : '').toLowerCase();
                var isOk = !!(resp && (resp.success === true || resType === 'ok' || resType === 'success'));

                if (isOk) {
                    // 後端舊版欄位是 is_profile_switch，新版前端使用 is_special_switch；兩者都相容。
                    resp.is_special_switch = !!(resp.is_special_switch || resp.is_profile_switch);
                    renderIdasPackCheckState(resp);
                    return;
                }

                window.IDAS_PACK_CHECK = makeEmptyIdasPackCheckState(true, false);
                setIdasUploadEnabled(false);

                if (statusEl) {
                    statusEl.textContent = (resp && (resp.message || resp.res_msg)) ? (resp.message || resp.res_msg) : (i18n.check_failed || 'Unable to verify package version.');
                    statusEl.className = 'idas-version-status error';
                }
                setIdasRowVisible('idas-downgrade-row', false);
            },
            error: function (xhr) {
                window.IDAS_PACK_CHECK = makeEmptyIdasPackCheckState(true, false);
                setIdasUploadEnabled(false);

                var msg = i18n.check_failed || 'Unable to verify package version.';
                try {
                    if (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.res_msg)) {
                        msg = xhr.responseJSON.message || xhr.responseJSON.res_msg;
                    } else if (xhr.responseText) {
                        msg = xhr.responseText;
                    }
                } catch (e) {}

                if (statusEl) {
                    statusEl.textContent = msg;
                    statusEl.className = 'idas-version-status error';
                }
                setIdasRowVisible('idas-downgrade-row', false);
            }
        });
    });
}

function idasEscapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function idasStripUpdateBackupLine(message) {
    // 備份仍會建立並寫入後端 log，但不在成功/失敗彈跳視窗顯示完整路徑。
    return String(message || '')
        .replace(/(^|\r?\n)\s*備份檔案：.*(?=\r?\n|$)/g, '')
        .replace(/(^|\r?\n)\s*备份文件：.*(?=\r?\n|$)/g, '')
        .replace(/(^|\r?\n)\s*Backup file:\s*.*(?=\r?\n|$)/gi, '')
        .replace(/^\s+|\s+$/g, '');
}

function idasFormatAlertifyMessage(message) {
    var text = idasStripUpdateBackupLine(message);

    // 後端有些訊息會帶 <br>，先保護換行語意，再進行 HTML escape，
    // 避免長錯誤內容或檔案路徑直接撐破 alertify 視窗。
    text = text.replace(/<br\s*\/?\s*>/gi, '\n');
    text = idasEscapeHtml(text).replace(/\r?\n/g, '<br>');

    return '<div class="idas-update-alert-message">' + text + '</div>';
}

function idasBuildUpdateConfirmHtml(message, language, currentVersion, packVersion) {
    var currentLabel = 'Current version';
    var packLabel = 'Package version';

    if (language === 'zh-tw') {
        currentLabel = '目前版本';
        packLabel = '更新包版本';
    } else if (language === 'zh-cn') {
        currentLabel = '当前版本';
        packLabel = '更新包版本';
    }

    var html = '<div class="idas-update-confirm">';
    html += '<div class="idas-update-confirm-message">' + idasFormatAlertifyMessage(message) + '</div>';

    if (currentVersion || packVersion) {
        html += '<div class="idas-update-confirm-versions">';
        html += '<div class="idas-update-confirm-version-row"><span class="idas-update-confirm-label">' + idasEscapeHtml(currentLabel) + '</span><span class="idas-update-confirm-value">' + idasEscapeHtml(currentVersion || '-') + '</span></div>';
        html += '<div class="idas-update-confirm-version-row"><span class="idas-update-confirm-label">' + idasEscapeHtml(packLabel) + '</span><span class="idas-update-confirm-value">' + idasEscapeHtml(packVersion || '-') + '</span></div>';
        html += '</div>';
    }

    html += '</div>';
    return html;
}

function idasShowDbSchemaMismatchAlert(responseData, language, fallbackTitle) {
    var title = responseData?.res_title || fallbackTitle || 'Error';
    var msg = responseData?.res_msg || '';

    if (!msg) {
        if (language === 'zh-tw') {
            msg = '偵測到降版本，但 Controller DB 與 iDAS DB 的資料庫格式不相容。<br><br>為避免降版本後系統無法正常讀取資料，本次更新已中止，尚未變更系統檔案與資料庫。<br><br>請確認 Controller 與 iDAS 的資料庫版本是否相容，或改用對應版本的更新包後再重新執行。';
        } else if (language === 'zh-cn') {
            msg = '检测到降版本，但 Controller DB 与 iDAS DB 的数据库格式不兼容。<br><br>为避免降版本后系统无法正常读取数据，本次更新已中止，尚未变更系统文件与数据库。<br><br>请确认 Controller 与 iDAS 的数据库版本是否兼容，或改用对应版本的更新包后再重新执行。';
        } else {
            msg = 'Downgrade detected, but the Controller DB format is different from the iDAS DB format.<br><br>To prevent the system from reading incompatible data after downgrade, this update has been stopped. System files and databases have not been changed.<br><br>Please confirm that the Controller DB and iDAS DB versions are compatible, or use a matching update package and try again.';
        }
    }

    msg = idasFormatAlertifyMessage(msg);

    IdasNotify.alert(title, msg, function () {
        setIdasUploadEnabled(true);
    });
}

function idas_update() {
    var uploader = document.getElementById("file-uploader");
    var import_file = uploader && uploader.files ? uploader.files[0] : null;
    var uploadBtn = document.getElementById('idas-upload-btn');
    var url = '?url=Settings/iDas_Update';

    var language = getCookie('language') || 'en-us';
    var title, confirm_text, empty_file_text, upload_error_text, downgrade_confirm_text, special_confirm_text, check_wait_text, check_failed_text, disabled_text, rebuild_text;
    var login_redirect_url = '/idas/public/?url=In';

    if (language === "zh-tw") {
        title = 'IDAS 更新';
        confirm_text = '您確定要導入 IDAS 更新包嗎？';
        downgrade_confirm_text = '系統已自動偵測此更新包為「降版本」。\n\n降版本時，系統會強制重建 iDAS DB，避免舊版本無法讀取新版 DB。\n請確認更新包正確後再執行。\n\n確定要繼續嗎？';
        special_confirm_text = '系統已自動偵測此更新包為「特殊版本切換」。\n\n若目前版本是 SA349 且更新包不是 SA349，系統會重建 DB。\n請確認更新包正確後再執行。\n\n確定要繼續嗎？';
        empty_file_text = '請先選擇要上傳的更新檔。';
        upload_error_text = '上傳檔案時發生錯誤。';
        check_wait_text = '系統正在自動檢查更新包版本，請稍後再上傳。';
        check_failed_text = '無法確認更新包版本，請重新選擇 .pack 檔案後再上傳。';
        disabled_text = '請先選擇 .pack 檔案，並等待版本檢查完成。';
        rebuild_text = '本次更新會重建 iDAS DB；SA349 → 非 SA349 時會移除 KLS_NTCS_IDAS.Lin 內 table user 的 admin 帳號，更新前系統會先建立備份。';
    } else if (language === "zh-cn") {
        title = 'IDAS 更新';
        confirm_text = '您确定要导入 IDAS 更新包吗？';
        downgrade_confirm_text = '系统已自动检测此更新包为「降版本」。\n\n降版本时，系统会强制重建 iDAS DB，避免旧版本无法读取新版 DB。\n请确认更新包正确后再执行。\n\n确定要继续吗？';
        special_confirm_text = '系统已自动检测此更新包为「特殊版本切换」。\n\n若目前版本是 SA349 且更新包不是 SA349，系统会重建 DB。\n请确认更新包正确后再执行。\n\n确定要继续吗？';
        empty_file_text = '请先选择要上传的更新文件。';
        upload_error_text = '上传文件时发生错误。';
        check_wait_text = '系统正在自动检查更新包版本，请稍后再上传。';
        check_failed_text = '无法确认更新包版本，请重新选择 .pack 文件后再上传。';
        disabled_text = '请先选择 .pack 文件，并等待版本检查完成。';
        rebuild_text = '本次更新会重建 iDAS DB；SA349 → 非 SA349 时会移除 KLS_NTCS_IDAS.Lin 内 table user 的 admin 帐号，更新前系统会先建立备份。';
    } else {
        title = 'IDAS UPDATE';
        confirm_text = 'Are you sure you want to import the IDAS update package?';
        downgrade_confirm_text = 'The system detected that this package is a downgrade.\n\nDuring downgrade, the system will forcibly rebuild the iDAS DB to prevent old versions from reading a newer DB format.\nPlease confirm the package before continuing.\n\nContinue?';
        special_confirm_text = 'The system detected a special version switch.\n\nIf the current version is SA349 and the package is not SA349, the system will rebuild DB.\nPlease confirm the package before continuing.\n\nContinue?';
        empty_file_text = 'Please select a file to upload.';
        upload_error_text = 'An error occurred while uploading the file.';
        check_wait_text = 'The system is checking the package version. Please upload again after the check is complete.';
        check_failed_text = 'Unable to verify the package version. Please select the .pack file again before uploading.';
        disabled_text = 'Please select a .pack file and wait for version verification to complete.';
        rebuild_text = 'This update will rebuild the iDAS DB. For SA349 → non-SA349, the admin account in KLS_NTCS_IDAS.Lin table user will be removed. The system will create a backup before updating.';
    }

    if (!import_file) {
        IdasNotify.alert(title, empty_file_text);
        return;
    }

    if (uploadBtn && uploadBtn.disabled) {
        IdasNotify.alert(title, disabled_text);
        return;
    }

    var packCheck = window.IDAS_PACK_CHECK || {};
    if (!packCheck.checked) {
        IdasNotify.alert(title, check_wait_text);
        return;
    }

    if (!packCheck.success) {
        IdasNotify.alert(title, check_failed_text);
        return;
    }

    var isDowngrade = !!packCheck.is_downgrade;
    var isSpecial = !!packCheck.is_special_switch;
    var message = isDowngrade ? downgrade_confirm_text : (isSpecial ? special_confirm_text : confirm_text);

    if (packCheck.requires_db_rebuild) {
        message += '\n\n' + rebuild_text;
    }

    var currentText = packCheck.current_version || '';
    var packText = packCheck.pack_version || '';
    var confirmHtml = idasBuildUpdateConfirmHtml(message, language, currentText, packText);

    var form = new FormData();
    form.append("file", import_file);
    // 降版本與特殊版本切換都由前端預檢自動判斷；後端仍會重新比對版本。
    form.append("allow_downgrade", (packCheck.requires_confirm || isDowngrade || isSpecial) ? '1' : '0');

    alertify.confirm(confirmHtml, function (result) {
        if (!result) return;

        // 已在 alertify.confirm 完成風險確認；不再跳出瀏覽器原生 prompt 要求輸入 SWITCH / DOWNGRADE。
        // 後端仍會依 allow_downgrade 與版本狀態再次驗證，保留正式保護機制。

        document.getElementById('spinner').style.display = 'block';
        setIdasUploadEnabled(false);

        $.ajax({
            url: url,
            method: "POST",
            data: form,
            processData: false,
            contentType: false,
            dataType: 'json',
            cache: false,

            success: function (responseData) {
                document.getElementById('spinner').style.display = 'none';

                if (responseData && responseData.error_code === 'DB_SCHEMA_MISMATCH') {
                    idasShowDbSchemaMismatchAlert(responseData, language, title);
                    return;
                }

                var resType = responseData?.res_type || 'Error';
                var resMsg  = responseData?.res_msg || upload_error_text;

                IdasNotify.alert(resType, idasFormatAlertifyMessage(resMsg), function () {
                    if (String(resType).toLowerCase() === 'success') {
                        forceLogoutAllTabs(login_redirect_url);
                    } else {
                        setIdasUploadEnabled(true);
                    }
                });
            },

            error: function (xhr, status, error) {
                document.getElementById('spinner').style.display = 'none';
                setIdasUploadEnabled(true);

                var msg = upload_error_text;

                try {
                    if (xhr.responseJSON && xhr.responseJSON.error_code === 'DB_SCHEMA_MISMATCH') {
                        idasShowDbSchemaMismatchAlert(xhr.responseJSON, language, title);
                        console.error("上傳錯誤：", status, error, xhr.responseText);
                        return;
                    }

                    if (xhr.responseJSON && xhr.responseJSON.res_msg) {
                        msg = xhr.responseJSON.res_msg;
                    } else if (xhr.responseText) {
                        msg = xhr.responseText;
                    }
                } catch (e) {
                    console.warn('parse xhr failed:', e);
                }

                IdasNotify.alert('Error', idasFormatAlertifyMessage(msg));
                console.error("上傳錯誤：", status, error, xhr.responseText);
            }
        });
    });
}


function input_check_savebarcode() {

    let conditions = [
        { id: 'barcode_content',  pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]{1,100}$/, min: null, max: null },
        { id: 'barcode_mask_from', pattern: /^[0-9]+$/, min: 1, max: 54 },
        { id: 'barcode_mask_count', pattern: /^[0-9]+$/, min: 1, max: 100 },

    ];

    let isFormValid = true;

    conditions.forEach(function(input) {
        var element = document.getElementById(input.id);
        if (input.id !== 'barcode_content') {
            element.nextElementSibling.innerHTML = `${input.min} ~ ${input.max}`;
        }

        if (!validateInput(element, input.pattern, input.min, input.max)) {
            isFormValid = false;
        }
    });

    return isFormValid;
}

function validateInput(element, pattern, min, max) {
    let value = element.value.trim();
    let isValid = true;

    // 验证空值
    if (value === "") {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 验证正则
    else if (!pattern.test(value)) {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 验证最小值
    else if (min !== null && parseFloat(value) < min) {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 验证最大值
    else if (max !== null && parseFloat(value) > max) {
        element.classList.add("is-invalid");
        isValid = false;
    }
    // 通过验证
    else {
        element.classList.remove("is-invalid");
    }

    return isValid;
}


function update_barcode() {

  /* =====================================================
   * 讀取表單欄位（⚠ 一定要放最前面，避免 TDZ）
   * ===================================================== */
  const barcode_id       = document.getElementById("barcode_id")?.value ?? '';
  const barcode_job_old  = document.getElementById("barcode_job_old")?.value ?? ''; // ⭐ 關鍵
  const barcode_name     = document.getElementById("barcode_name")?.value?.trim() ?? '';
  const barcode_from     = document.getElementById("barcode_from")?.value ?? '';
  const barcode_count    = document.getElementById("barcode_count")?.value ?? '';
  const barcode_mode     = document.querySelector("select[name='barcode_mode']")?.value ?? '-1';
  const barcode_job      = document.querySelector("select[name='barcode_job']")?.value ?? '-1';
  const barcode_seq      = document.querySelector("select[name='barcode_seq']")?.value ?? '-1';

  const isEdit = Number(barcode_job_old) > 0;

  // 🔎 debug（需要時保留）
  console.log('[barcode]', isEdit ? 'EDIT' : 'ADD', {
    barcode_id,
    barcode_job_old,
    barcode_job
  });

  /* =====================================================
   * 語系偵測
   * ===================================================== */
  const getLang = () => {
    try {
      if (typeof getCookie === 'function' && getCookie('language')) {
        return String(getCookie('language')).toLowerCase();
      }
      const htmlLang = document.documentElement.getAttribute('lang');
      if (htmlLang) return String(htmlLang).toLowerCase();
    } catch (_) {}
    return 'en-us';
  };

  const rawLang = getLang();
  const lang =
    rawLang.includes('zh-tw') || rawLang.includes('hant') || rawLang.includes('tw') ||
    rawLang.includes('hk') || rawLang.includes('mo')
      ? 'zh-tw'
      : rawLang.includes('zh-cn') || rawLang.includes('hans') ||
        rawLang.includes('cn') || rawLang.includes('sg')
          ? 'zh-cn'
          : 'en-us';

  const i18n = {
    'en-us': {
      titleInfo: 'Info',
      titleError: 'Error',
      ok: 'OK',
      cancel: 'Cancel',
      v_job: 'Please select a Job.',
      v_seq: 'Please select a Sequence.',
      v_name: 'Please enter a barcode name.',
      ajaxFail: 'Operation failed. Please try again.',
      ajaxParseFail: 'Invalid server response.',
    },
    'zh-tw': {
      titleInfo: '提示',
      titleError: '錯誤',
      ok: '確定',
      cancel: '取消',
      v_job: '請先選擇工作（Job）。',
      v_seq: '請先選擇序列（SEQ）。',
      v_name: '請輸入條碼名稱。',
      ajaxFail: '操作失敗，請稍後再試。',
      ajaxParseFail: '伺服器回傳格式不正確。',
    },
    'zh-cn': {
      titleInfo: '提示',
      titleError: '错误',
      ok: '确定',
      cancel: '取消',
      v_job: '请先选择工作（Job）。',
      v_seq: '请先选择序列（SEQ）。',
      v_name: '请输入条码名称。',
      ajaxFail: '操作失败，请稍后再试。',
      ajaxParseFail: '服务器返回格式不正确。',
    }
  }[lang];

  /* =====================================================
   * alertify 語系
   * ===================================================== */
  try {
    if (alertify?.defaults?.glossary) {
      alertify.defaults.glossary.ok = i18n.ok;
      alertify.defaults.glossary.cancel = i18n.cancel;
      alertify.defaults.glossary.title = i18n.titleInfo;
    } else if (typeof alertify.okBtn === 'function') {
      alertify.okBtn(i18n.ok).cancelBtn(i18n.cancel);
    }
  } catch (_) {}

  /* =====================================================
   * 表單驗證
   * ===================================================== */
  if (barcode_job === "-1") {
    IdasNotify.alert(i18n.titleInfo, i18n.v_job);
    return;
  }

  if (barcode_mode === "3" && barcode_seq === "-1") {
    IdasNotify.alert(i18n.titleInfo, i18n.v_seq);
    return;
  }

  if (!barcode_name) {
    IdasNotify.alert(i18n.titleInfo, i18n.v_name);
    return;
  }

  /* =====================================================
   * Spinner
   * ===================================================== */
  const spinner = document.getElementById('spinner');
  if (spinner) spinner.style.display = 'block';

  const $ = window.jQuery;

  /* =====================================================
   * AJAX
   * ===================================================== */
  $.ajax({
    url: "?url=Settings/Update_Barcode",
    method: "POST",
    data: {
      barcode_id:       barcode_id,
      barcode_name:     barcode_name,
      barcode_from:     barcode_from,
      barcode_count:    barcode_count,
      barcode_job:      barcode_job,
      barcode_job_old:  barcode_job_old, // ⭐⭐⭐ 核心
      barcode_seq:      barcode_seq,
      barcode_mode:     barcode_mode
    },
    success: function (response) {

      let responseData;
      try {
        responseData = (typeof response === 'object') ? response : JSON.parse(response);
      } catch (e) {
        if (spinner) spinner.style.display = 'none';
        IdasNotify.alert(i18n.titleError, i18n.ajaxParseFail);
        return;
      }

      setTimeout(() => {
        if (spinner) spinner.style.display = 'none';

        const resType = responseData?.res_type === 'error'
          ? i18n.titleError
          : i18n.titleInfo;

        const resMsg = responseData?.res_msg ?? '';

        IdasNotify.alert(resType, resMsg, () => {
          sessionStorage.setItem('Barcode_Setting', 'block');
          sessionStorage.setItem('Controller_Setting', 'none');
        });

        // 自動刷新列表
        setTimeout(() => {
          alertify.closeAll();

          $.ajax({
            url: "?url=Settings/show_Barcodes",
            method: "GET",
            success: function (html) {
              $('#total_barcodes').html(html);

              // 1 JOB = 1 Barcode，刷新後可安全地用 JOB ID 重新選取剛儲存的設定。
              if (barcode_job && barcode_job !== '-1') {
                const $cb = $('.barcode-check[data-job-id="' + barcode_job + '"]').first();
                if ($cb.length) {
                  $('.barcode-check').prop('checked', false);
                  $cb.prop('checked', true);
                  $cb.trigger('click');
                }
              }

              if (typeof OpenButton === 'function') {
                OpenButton('Barcode');
              }
            },
            error: function () {
              IdasNotify.alert(i18n.titleError, i18n.ajaxFail);
            }
          });

        }, 3000);

      }, 800);
    },
    error: function (xhr, status, error) {
      if (spinner) spinner.style.display = 'none';
      console.error('[Update_Barcode]', status, error, xhr?.responseText);
      IdasNotify.alert(i18n.titleError, i18n.ajaxFail);
    }
  });
}





function agent_ip_save() {
  const ipEl = document.getElementById('agent_server_ip');
  const feedbackEl = (ipEl?.nextElementSibling && ipEl.nextElementSibling.classList.contains('invalid-feedback'))
    ? ipEl.nextElementSibling
    : null;

  // 清除欄位提示
  if (feedbackEl) {
    feedbackEl.textContent = '';
    feedbackEl.style.display = 'none';
  }

  // 語系
  let lang = (typeof getCookie === 'function' ? getCookie('language') : 'en-us') || 'en-us';
  lang = String(lang).toLowerCase().replace('_', '-');
  if (lang === 'en') lang = 'en-us';
  if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

  const MSG = {
    'en-us': 'Please enter a valid IP address.',
    'zh-tw': '請輸入有效的 IP 地址。',
    'zh-cn': '请输入有效的 IP 地址。'
  };
  const TITLE = { 'en-us': 'Warning', 'zh-tw': '警告', 'zh-cn': '警告' }[lang];
  const OKLBL  = { 'en-us': 'OK', 'zh-tw': '確定', 'zh-cn': '确定' }[lang];
  const CCLBL  = { 'en-us': 'Cancel', 'zh-tw': '取消', 'zh-cn': '取消' }[lang];

  const ip = (ipEl.value || '').trim();
  const ipRegex = /^(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

  // 先清狀態
  ipEl.classList.remove('is-invalid');

  // ❌ 驗證失敗
  if (!ip || !ipRegex.test(ip)) {
    ipEl.classList.add('is-invalid');

    if (!ipEl._bindInvalidClear) {
      ipEl.addEventListener('input', function onIn() {
        ipEl.classList.remove('is-invalid');
        ipEl.removeEventListener('input', onIn);
        ipEl._bindInvalidClear = false;
      });
      ipEl._bindInvalidClear = true;
    }

    alertify.confirm(
      TITLE,
      MSG[lang],
      function onOk() {
        ipEl.focus();
        ipEl.select?.();
      },
      function onCancel() {
        // 取消時，直接清掉紅框
        ipEl.classList.remove('is-invalid');
      }
    ).set('labels', { ok: OKLBL, cancel: CCLBL });

    return; // 不送出
  }

  // ✅ 驗證通過 → 呼叫後端
  const spinner = document.getElementById('spinner');
  if (spinner) spinner.style.display = 'block';

  $.ajax({
    url: "?url=Admins/SetAgentIp",
    method: "POST",
    data: { agent_server_ip: ip },
    success: function (response) {
      let res = {};
      try { res = ((typeof response === 'string') ? JSON.parse(response) : response) || {}; } catch {}

      if (spinner) spinner.style.display = 'none';

      alertify.confirm(
        res.res_type || 'Info',
        res.res_msg || 'Done.',
        function onOk() {
          sessionStorage.setItem('Connect_Setting', 'block');
          sessionStorage.setItem('Controller_Setting', 'none');
        },
        function onCancel() {
          // 取消時不用做事
        }
      ).set('labels', { ok: OKLBL, cancel: CCLBL });

      setTimeout(() => alertify.closeAll(), 3000);

      if (res.res_number != null) ipEl.value = res.res_number;
    },
    error: function (xhr) {
      if (spinner) spinner.style.display = 'none';
      alertify.confirm(
        'Error',
        (xhr && xhr.responseText) || 'Request failed.',
        null,
        null
      ).set('labels', { ok: OKLBL, cancel: CCLBL });
    }
  });
}



function agent_type_save() {
  const agent_type = document.querySelector('input[name="agent_type"]:checked')?.value;
  if (!agent_type) return;

  // 語系
  let lang = (typeof getCookie === 'function' ? getCookie('language') : 'en-us') || 'en-us';
  lang = String(lang).toLowerCase().replace('_', '-');
  if (lang === 'en') lang = 'en-us';
  if (!['en-us', 'zh-tw', 'zh-cn'].includes(lang)) lang = 'en-us';

  const OKLBL = { 'en-us': 'OK', 'zh-tw': '確定', 'zh-cn': '确定' }[lang];
  const CCLBL = { 'en-us': 'Cancel', 'zh-tw': '取消', 'zh-cn': '取消' }[lang];

  document.querySelector(".main-content").classList.add("overlay-active");
  document.getElementById('spinner').style.display = 'block';

  $.ajax({
    url: "?url=Admins/SetAgentType",
    method: "POST",
    data: { agent_type },
    success: function (response) {
      let res = {};
      try { res = ((typeof response === 'string') ? JSON.parse(response) : response) || {}; } catch {}

      // 改用 confirm → 有 OK + Cancel
      alertify.confirm(
        res.res_type || 'Info',
        res.res_msg || 'Done.',
        function onOk() {
          // OK：保留成功狀態
        },
        function onCancel() {
          // Cancel：可以額外處理，例如還原 radio 按鈕
        }
      ).set('labels', { ok: OKLBL, cancel: CCLBL });

      setTimeout(function () {
        alertify.closeAll();
        document.getElementById('spinner').style.display = 'none';
        document.querySelector(".main-content").classList.remove("overlay-active");
      }, 3000);
    },
    error: function (xhr) {
      document.getElementById('spinner').style.display = 'none';
      document.querySelector(".main-content").classList.remove("overlay-active");

      alertify.confirm(
        'Error',
        (xhr && xhr.responseText) || 'Request failed.',
        null,
        null
      ).set('labels', { ok: OKLBL, cancel: CCLBL });
    }
  });
}


document.addEventListener('DOMContentLoaded', function() {
    var input = document.getElementById('barcode_name');
    if (input) {
        input.addEventListener('input', function() {
            var length = this.value.length;
            document.getElementById('barcode_count').value = length;
        });
    }
});

(function bindBarcodeCheckboxDelegateOnce() {
  if (window.__barcodeDelegateBound) return;
  window.__barcodeDelegateBound = true;

  $(document).on('change', '.barcode-check, input[name="barcode_check"], #barcode_check', function () {
    const cb = this;

    let jobId = cb.dataset?.jobId || cb.getAttribute('data-job-id');
    if (!jobId) {
      const m = (cb.id || '').match(/^barcode_check_(\d+)_/);
      if (m) jobId = m[1];
    }

    console.log('barcode checked jobId=', jobId, 'checked=', cb.checked, cb.dataset);
  });
})();

(function bindBarcodeSingleSelectOnce() {
  if (window.__barcodeSingleSelectBound) return;
  window.__barcodeSingleSelectBound = true;

  document.addEventListener('change', function (e) {
    const cb = e.target;
    if (!cb.matches('.barcode-check')) return;

    // ① 單選：取消其他 checkbox
    if (cb.checked) {
      document.querySelectorAll('.barcode-check').forEach(other => {
        if (other !== cb) other.checked = false;
      });
    } else {
      return; // 取消勾選時不處理
    }

    // ② ⭐ 關鍵：每次都用 data-* 覆寫下方表單
    const ds = cb.dataset;

    // 條碼
    const barcodeEl = document.getElementById('barcode_name');
    if (barcodeEl) barcodeEl.value = ds.barcode ?? '';

    // 從
    const fromEl = document.getElementById('barcode_from');
    if (fromEl) fromEl.value = ds.rangeFrom ?? '';

    // 個數
    const countEl = document.getElementById('barcode_count');
    if (countEl) countEl.value = ds.rangeCount ?? '';

    // 條碼模式
    const modeEl = document.querySelector("select[name='barcode_mode']");
    if (modeEl && ds.barcodeMode !== undefined) {
      modeEl.value = ds.barcodeMode;
    }

    // 工作
    const jobEl = document.querySelector("select[name='barcode_job']");
    if (jobEl && ds.jobId) {
      jobEl.value = ds.jobId;
    }

    // 工序
    const seqEl = document.querySelector("select[name='barcode_seq']");
    if (seqEl) {
      seqEl.value = (ds.seqId !== undefined && ds.seqId !== '-1') ? ds.seqId : '-1';
    }

    // SQLite rowid：不新增 DB 欄位，用 rowid 當單筆 Barcode 識別。
    const idEl = document.getElementById('barcode_id');
    if (idEl) {
      idEl.value = ds.id || ds.barcodeRowid || '';
    }

    // Debug
    console.log('[Barcode switch]', ds.jobId, ds);
  });
})();
}
function idasRenderDatabaseStorage(payload) {
    var panel = document.getElementById('idas-storage-panel');
    var free = document.getElementById('idas-storage-free');
    var last = document.getElementById('idas-storage-last');
    var i18n = window.IDAS_STORAGE_I18N || {};
    if (!panel) return;
    panel.classList.toggle('is-warning', !!payload.disk_warning && !payload.disk_blocked);
    panel.classList.toggle('is-blocked', !!payload.disk_blocked);
    if (free) free.textContent = Number(payload.free_mb || 0).toFixed(1) + ' MB';
    if (last) last.textContent = payload.last_maintenance_at || i18n.never || '-';
}

function idasLoadDatabaseStorage() {
    var panel = document.getElementById('idas-storage-panel');
    if (!panel || panel.closest('[hidden]') || typeof $ === 'undefined') return;
    $.ajax({url:'?url=Settings/idas_database_maintenance',method:'POST',data:{action:'status'},dataType:'json',cache:false})
        .done(function(resp){ if (resp) idasRenderDatabaseStorage(resp); });
}

function idasRunSafeDatabaseCleanup() {
    var i18n = window.IDAS_STORAGE_I18N || {};
    if (!window.confirm(i18n.confirm || 'Run safe cleanup?')) return;
    var btn = document.getElementById('idas-storage-cleanup-btn');
    if (btn) btn.disabled = true;
    $.ajax({url:'?url=Settings/idas_database_maintenance',method:'POST',data:{action:'cleanup'},dataType:'json',cache:false})
        .done(function(resp){
            if (resp) idasRenderDatabaseStorage(resp);
            var freed = Number((resp && resp.freed_mb) || 0).toFixed(1);
            window.alert(((resp && resp.success) ? (i18n.completed || 'Safe cleanup completed') : (i18n.failed || 'Cleanup is busy or failed')) + '\n' + (i18n.freed || 'Freed') + ': ' + freed + ' MB');
            var uploader = document.getElementById('file-uploader');
            if (resp && resp.success && uploader && uploader.files && uploader.files.length) {
                uploader.dispatchEvent(new Event('change'));
            }
        })
        .fail(function(){ window.alert('Safe cleanup failed.'); })
        .always(function(){ if (btn) btn.disabled = false; });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', idasLoadDatabaseStorage);
} else {
    idasLoadDatabaseStorage();
}
