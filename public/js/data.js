function exportData() {
    var radioButtons = document.querySelectorAll('input[name="export-option"]');
    var isChecked = false;
    var expert_val = '';

    for (var i = 0; i < radioButtons.length; i++) {
        if (radioButtons[i].checked) {
            isChecked = true;
            expert_val = radioButtons[i].value;
            break;
        }
    }

    if (!isChecked) {
        alertify.alert("請選擇一個選項");
        return;
    }

    var start_date = document.getElementById('start_date').value;
    var end_date   = document.getElementById('end_date').value;

    if (start_date === '' || end_date === '') {
        alertify.alert("請選擇開始日期與結束日期");
        return;
    }

    if (start_date > end_date) {
        alertify.alert("開始日期必須小於結束日期");
        return;
    }

    // =====================================
    // ⭐ 取得瀏覽器當前時間（YYYYMMDDHHmmss）
    // =====================================
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

    $.ajax({
        url: "?url=Data/exportData",
        method: "POST",
        data: {
            start_date: start_date,
            end_date: end_date,
            expert_val: expert_val,
            client_ts: getBrowserTimestamp() // ⭐⭐⭐ 關鍵新增
        },
        xhrFields: {
            responseType: 'blob'
        },
        success: function(response, status, xhr) {
            const disposition = xhr.getResponseHeader('Content-Disposition');
            let filename = 'downloaded_file';

            if (disposition && disposition.indexOf('filename=') !== -1) {
                const matches = disposition.match(/filename="?([^"]+)"?/);
                if (matches && matches.length > 1) {
                    filename = matches[1];
                }
            }

            const contentType = xhr.getResponseHeader('Content-Type');
            const blob = new Blob([response], { type: contentType });

            const link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.setAttribute('download', filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        },
        error: function(xhr, status, error) {
            console.error("AJAX 請求失敗:", status, error);
            alertify.alert("發生錯誤，無法導出資料");
        }
    });
}




function downloadCSVZip() {
  const url = "?url=Data/download_file";
  const $btn = document.getElementById('bnt2') || null;

  // ---- i18n ----
  const rawLang = (typeof getCookie === 'function' && getCookie('language')) ||
                  document.documentElement.getAttribute('lang') || 'en-us';
  const l = String(rawLang).toLowerCase();
  const dict = (function (lang) {
    if (lang === 'zh-tw' || lang.includes('hant') || lang.includes('tw') || lang.includes('hk') || lang.includes('mo')) {
      return { ok:'確定', info:'提示', error:'錯誤', done:'下載完成：', noCurve:'沒有曲線圖的資料可以下載', genericFail:'沒有曲線圖的資料可以下載', httpFail:'下載發生錯誤' };
    } else if (lang === 'zh-cn' || lang.includes('hans') || lang.includes('cn') || lang.includes('sg')) {
      return { ok:'确定', info:'提示', error:'错误', done:'下载完成：', noCurve:'没有曲线图的资料可以下载', genericFail:'没有曲线图的资料可以下载', httpFail:'下载发生错误' };
    }
    return { ok:'OK', info:'Notice', error:'Error', done:'Downloaded: ', noCurve:'No curve data available to download.', genericFail:'No curve data available to download', httpFail:'An error occurred while downloading.' };
  })(l);

  // 預設 OK 文案（雙保險）
  if (window.alertify?.defaults?.glossary) {
    try { alertify.defaults.glossary.ok = dict.ok; } catch (e) {}
  }

  if ($btn) $btn.disabled = true;

  // =========================
  // Helpers (含除錯工具)
  // =========================

  // 總開關：要看 console 設 true
  const DBG = true;
  const dbg = (...args) => { if (DBG) console.log('[downloadCSVZip]', ...args); };

  // 跨 realm 也有效的 Blob 偵測（含兜底）
  const isBlobLike = (v) => {
    if (!v) return false;
    const tag = Object.prototype.toString.call(v);
    if (tag === '[object Blob]' || tag === '[object File]') return true;
    return typeof v === 'object'
        && typeof v.size === 'number'
        && typeof v.slice === 'function'
        && (!('type' in v) || typeof v.type === 'string');
  };

  // 讀整個 Blob 為文字
  const readBlobAsText = (blob) =>
    new Promise((resolve, reject) => {
      const fr = new FileReader();
      fr.onload = () => resolve(String(fr.result || ''));
      fr.onerror = (e) => reject(e);
      fr.readAsText(blob);
    });

  // 僅讀前 firstKB 的文字預覽
  async function readBlobTextPreview(blob, firstKB = 64) {
    try {
      const slice = blob.slice(0, Math.min(blob.size, firstKB * 1024));
      const text = await new Promise((resolve, reject) => {
        const fr = new FileReader();
        fr.onload = () => resolve(String(fr.result || ''));
        fr.onerror = reject;
        fr.readAsText(slice);
      });
      return text;
    } catch {
      return null;
    }
  }

  // 讀前 maxBytes 的十六進位預覽
  async function readBlobHexPreview(blob, maxBytes = 64) {
    try {
      const slice = blob.slice(0, Math.min(blob.size, maxBytes));
      const buf = await slice.arrayBuffer();
      const view = new Uint8Array(buf);
      const hex = Array.from(view).map(v => v.toString(16).padStart(2,'0')).join(' ');
      return hex;
    } catch {
      return null;
    }
  }

  // 主 debug：把 Blob 的資訊印出來
  async function debugBlob(label, blob, extra = {}) {
    const tag = Object.prototype.toString.call(blob);
    const type = blob?.type;
    const size = blob?.size;
    const textPreview = await readBlobTextPreview(blob, 64);   // 64KB 文字預覽
    const hexPreview  = await readBlobHexPreview(blob, 64);    // 64 bytes 十六進位
    dbg(`${label} -> tag=${tag} type=${type} size=${size}`, extra);
    if (textPreview !== null) dbg(`${label} textPreview(64KB):`, textPreview.slice(0, 1000));
    if (hexPreview  !== null) dbg(`${label} hexPreview(64B):`, hexPreview);
  }

  // 小 Blob 嘗試 parse JSON（避免誤讀大檔）
  async function tryParseJsonFromBlob(blob, maxKB = 512) {
    try {
      if (!isBlobLike(blob) || blob.size > maxKB * 1024) return null;
      const text = await readBlobAsText(blob);
      return JSON.parse(text);
    } catch { return null; }
  }

  // 小 Blob 讀文字
  async function tryReadTextFromBlob(blob, maxKB = 1024) {
    try {
      if (!isBlobLike(blob) || blob.size > maxKB * 1024) return null;
      return await readBlobAsText(blob);
    } catch { return null; }
  }

  // 後端語意偵測（JSON）
  function isNoCurveByJson(json) {
    const code = json?.res_code || json?.code || '';
    const msg  = json?.res_msg  || json?.message || '';
    const typ  = (json?.res_type || '').toLowerCase();
    return code === 'NO_CURVE_DATA'
        || (typ === 'info' && /no\s*curve\s*data/i.test(msg))
        || /沒有可供下載|没有可供下载|no.+csv|no.+data/i.test(msg)
        || /沒有曲線圖|没有曲线图/i.test(msg);
  }

  // 後端語意偵測（純文字）
  function isNoCurveByText(text) {
    const t = String(text || '');
    return /沒有曲線圖|没有曲线图|沒有可供下載|没有可供下载|no\s+curve\s+data/i.test(t);
  }

  // 專治 [object Blob] 的安全 alert（統一出入口）
  const safeAlert = async (title, msg) => {
    const tag = Object.prototype.toString.call(msg);
    if (isBlobLike(msg) || tag === '[object Blob]' || tag === '[object File]') {
      await debugBlob('safeAlert-blob', msg);
      const text = await readBlobTextPreview(msg, 1024); // 1MB 上限
      alertify.alert(title, String(text || '[blob]')).set('labels', { ok: dict.ok });
      return;
    }
    if (msg && typeof msg === 'object') {
      alertify.alert(title, String(msg.res_msg || msg.message || JSON.stringify(msg, null, 2)))
              .set('labels', { ok: dict.ok });
      return;
    }
    alertify.alert(title, String(msg || '')).set('labels', { ok: dict.ok });
  };

  // =========================
  // AJAX
  // =========================
  $.ajax({
    url,
    method: "GET",
    xhrFields: { responseType: 'blob' }, // 成功/失敗都可能是 Blob
    headers: { 'Accept': 'application/zip, application/json, text/plain, text/html' },

    success: async function (data, textStatus, jqXHR) {
      try {
        const ct = (jqXHR.getResponseHeader('Content-Type') || '').toLowerCase();
        dbg('success', { ct, status: jqXHR.status }, jqXHR.getAllResponseHeaders?.());
        if (isBlobLike(data)) await debugBlob('success-data', data, { ct });

        // 1) 明確是 JSON：讀出與判斷
        if (ct.includes('application/json')) {
          const text = await readBlobAsText(data);
          let json = {};
          try { json = JSON.parse(text || '{}'); } catch {}
          const title = isNoCurveByJson(json) ? dict.info : dict.error;
          const msg   = isNoCurveByJson(json) ? dict.noCurve : (json?.res_msg || dict.genericFail);
          await safeAlert(title, msg);
          return;
        }

        // 2) 可能是 text/plain / text/html / application/octet-stream（小檔）：先讀成文字
        if (ct.includes('text/plain') || ct.includes('text/html') || ct.includes('application/octet-stream')) {
          const text = await tryReadTextFromBlob(data, 1024);
          if (text && isNoCurveByText(text)) { await safeAlert(dict.info, dict.noCurve); return; }
          if (text && text.trim() && !ct.includes('application/zip')) { // 明顯錯誤字串
            await safeAlert(dict.error, text.trim()); return;
          }
          // 否則繼續往下當作 ZIP
        }

        // 3) 嗅探小 Blob 是否其實是 JSON
        const sniff = await tryParseJsonFromBlob(data, 512);
        if (sniff) {
          const title = isNoCurveByJson(sniff) ? dict.info : dict.error;
          const msg   = isNoCurveByJson(sniff) ? dict.noCurve : (sniff?.res_msg || dict.genericFail);
          await safeAlert(title, msg);
          return;
        }

        // 4) 真的就是 ZIP：下載
        let filename = 'csv_bundle.zip';
        const cd = jqXHR.getResponseHeader('Content-Disposition') || '';
        const match = cd.match(/filename\*?=(?:UTF-8'')?("?)([^";]+)\1/i);
        if (match && match[2]) filename = decodeURIComponent(match[2]);

        const blobUrl = URL.createObjectURL(data);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = blobUrl;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        URL.revokeObjectURL(blobUrl);
        a.remove();

        // 如需提示成功可開啟
        // await safeAlert(dict.info, dict.done + filename);

      } catch (err) {
        dbg('success-catch', err);
        await safeAlert(dict.error, dict.genericFail);
      } finally {
        if ($btn) $btn.disabled = false;
      }
    },

    error: async function (jqXHR) {
      try {
        dbg('error', { status: jqXHR.status }, jqXHR.getAllResponseHeaders?.());

        // ★★★ 特例：jQuery 把錯誤訊息變成字串 "[object Blob]" 的狀況
        // 某些環境下 jqXHR.response 可能拿不到 / 不是 blob-like，但 responseText 卻是這個字串
        if (typeof jqXHR.responseText === 'string' && /^\s*\[object Blob\]\s*$/i.test(jqXHR.responseText)) {
        await safeAlert(dict.info, dict.noCurve); // 多語系：「沒有曲線圖的資料可以下載」
        return; // 結束 error handler，避免再往下跑
        }
        

        // A) 錯誤回應是 Blob：先試 JSON，再試文字
        if (isBlobLike(jqXHR.response)) {
          await debugBlob('error-response', jqXHR.response, { status: jqXHR.status });
          const json = await tryParseJsonFromBlob(jqXHR.response, 512);
          if (json) {
            const title = isNoCurveByJson(json) ? dict.info : dict.error;
            const msg   = isNoCurveByJson(json) ? dict.noCurve : (json?.res_msg || dict.httpFail);
            await safeAlert(title, msg);
            return;
          }
          const text = await tryReadTextFromBlob(jqXHR.response, 1024);
          if (text && isNoCurveByText(text)) { await safeAlert(dict.info, dict.noCurve); return; }
          if (text && text.trim())           { await safeAlert(dict.error, text.trim()); return; }
          await safeAlert(dict.error, dict.httpFail);
          return;
        }

        // B) 有 responseText（字串）
        if (typeof jqXHR.responseText === 'string' && jqXHR.responseText.length) {
          dbg('error-responseText', jqXHR.responseText.slice(0, 1000));
          try {
            const json = JSON.parse(jqXHR.responseText);
            const title = isNoCurveByJson(json) ? dict.info : dict.error;
            const msg   = isNoCurveByJson(json) ? dict.noCurve : (json?.res_msg || dict.httpFail);
            await safeAlert(title, msg);
          } catch {
            await safeAlert(dict.error, jqXHR.responseText);
          }
          return;
        }

        // C) jQuery 幫忙 parse 的 JSON
        if (jqXHR.responseJSON) {
          dbg('error-responseJSON', jqXHR.responseJSON);
          const json = jqXHR.responseJSON;
          const title = isNoCurveByJson(json) ? dict.info : dict.error;
          const msg   = isNoCurveByJson(json) ? dict.noCurve : (json?.res_msg || dict.httpFail);
          await safeAlert(title, msg);
          return;
        }

        // D) Fallback
        await safeAlert(dict.error, dict.httpFail);
      } finally {
        if ($btn) $btn.disabled = false;
      }
    }
  });
}

