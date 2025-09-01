var G_InitialCntFlg = 0;

function init()
{
    if (G_InitialCntFlg == 0)
    {
        G_ButtonMode = 1;

        document.getElementById('HistoryDisplay').setAttribute("style", "display:block");
        document.getElementById('ExportdataDisplay').setAttribute("style","display:none");
    }

    G_InitialCntFlg ++;

}

function OpenButton(ButtonMode)
{

    if (ButtonMode == "History"){
        document.getElementById('HistoryDisplay').setAttribute("style", "display:block");
        document.getElementById('ExportdataDisplay').setAttribute("style","display:none");
        document.getElementById('bnt1').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");

        document.getElementById('data_select').setAttribute("style", "display:block");


    }else if (ButtonMode == "Exportdata"){
        document.getElementById('ExportdataDisplay').setAttribute("style","display:block;");
        document.getElementById('HistoryDisplay').setAttribute("style", "display:none");
        document.getElementById('bnt2').classList.add("active");
        document.getElementById('bnt1').classList.remove("active");

        document.getElementById('data_select').setAttribute("style", "display:none");
    }else if(ButtonMode == "Export_Data_download"){
        downloadCSVZip();
    }else{
        alert("Function ["+ ButtonMode +"] is under constructing ...");
    }
}

function downloadCSVZip() {
  const url = "?url=Data/download_file";
  const $btn = document.getElementById('bnt2') || null;

  // ---- i18n ----
  const rawLang = (typeof getCookie === 'function' && getCookie('language')) ||
                  document.documentElement.getAttribute('lang') || 'en-us';
  const l = String(rawLang).toLowerCase();
  const dict = (function(lang){
    if (lang === 'zh-tw' || lang.includes('hant') || lang.includes('tw') || lang.includes('hk') || lang.includes('mo')) {
      return { ok:'確定', info:'提示', error:'錯誤', done:'下載完成：', noCurve:'沒有曲線圖的資料可以下載', genericFail:'沒有曲線圖的資料可以下載', httpFail:'下載發生錯誤' };
    } else if (lang === 'zh-cn' || lang.includes('hans') || lang.includes('cn') || lang.includes('sg')) {
      return { ok:'确定', info:'提示', error:'错误', done:'下载完成：', noCurve:'没有曲线图的资料可以下载', genericFail:'没有曲线图的资料可以下载', httpFail:'下载发生错误' };
    }
    return { ok:'OK', info:'Notice', error:'Error', done:'Downloaded: ', noCurve:'No curve data available to download.', genericFail:'No curve data available to download', httpFail:'An error occurred while downloading.' };
  })(l);

  // 有些版本需要先設預設 OK（單次也會再 set，雙保險）
  if (window.alertify?.defaults?.glossary) {
    try { alertify.defaults.glossary.ok = dict.ok; } catch (e) {}
  }

  if ($btn) $btn.disabled = true;

  // ---- helpers ----
  const showAlert = (title, msg) =>
    alertify.alert(title, String(msg || '')).set('labels', { ok: dict.ok });

  const readBlobAsText = (blob) =>
    new Promise((resolve, reject) => {
      const fr = new FileReader();
      fr.onload = () => resolve(String(fr.result || ''));
      fr.onerror = (e) => reject(e);
      fr.readAsText(blob);
    });

  async function tryParseJsonFromBlob(blob, maxKB = 512) {
    try {
      if (!(blob instanceof Blob) || blob.size > maxKB * 1024) return null;
      const text = await readBlobAsText(blob);
      return JSON.parse(text);
    } catch { return null; }
  }

  async function tryReadTextFromBlob(blob, maxKB = 1024) {
    try {
      if (!(blob instanceof Blob) || blob.size > maxKB * 1024) return null;
      return await readBlobAsText(blob);
    } catch { return null; }
  }

  function isNoCurveByJson(json) {
    const code = json?.res_code || json?.code || '';
    const msg  = json?.res_msg  || '';
    return code === 'NO_CURVE_DATA'
        || /沒有可供下載|没有可供下载|no.+csv|no.+data/i.test(msg)
        || /沒有曲線圖|没有曲线图/i.test(msg);
  }

  function isNoCurveByText(text) {
    const t = String(text || '');
    return /沒有曲線圖|没有曲线图|沒有可供下載|没有可供下载|no\s+curve\s+data/i.test(t);
  }

  $.ajax({
    url,
    method: "GET",
    xhrFields: { responseType: 'blob' }, // 成功/失敗都可能是 Blob
    success: async function (data, textStatus, jqXHR) {
      try {
        const ct = (jqXHR.getResponseHeader('Content-Type') || '').toLowerCase();

        // 1) 明確是 JSON：讀出與判斷
        if (ct.includes('application/json')) {
          const text = await readBlobAsText(data);
          let json = {};
          try { json = JSON.parse(text || '{}'); } catch {}
          console.log(json);
          console.log('eeeeert');
          const title = isNoCurveByJson(json) ? dict.info : dict.error;
          const msg   = isNoCurveByJson(json) ? dict.noCurve : (json?.res_msg || dict.genericFail);
          showAlert(title, msg);
          return;
        }

        // 2) 可能是 text/plain / text/html / application/octet-stream（小檔）：先讀成文字
        if (ct.includes('text/plain') || ct.includes('text/html') || ct.includes('application/octet-stream')) {
          const text = await tryReadTextFromBlob(data, 1024);
          if (text && isNoCurveByText(text)) {
            showAlert(dict.info, dict.noCurve);
            return;
          }
          // 若是明顯的錯誤字串，也顯示出來
          if (text && text.trim() && !ct.includes('application/zip')) {
            showAlert(dict.error, text.trim());
            return;
          }
          // 否則繼續往下當作 ZIP
        }

        // 3) 嗅探小 Blob 是否其實是 JSON
        const sniff = await tryParseJsonFromBlob(data);
        if (sniff) {
          const title = isNoCurveByJson(sniff) ? dict.info : dict.error;
          const msg   = isNoCurveByJson(sniff) ? dict.noCurve : (sniff?.res_msg || dict.genericFail);
          showAlert(title, msg);
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
        // showAlert(dict.info, dict.done + filename);

      } catch (err) {
        showAlert(dict.error, dict.genericFail);
      } finally {
        if ($btn) $btn.disabled = false;
      }
    },
    error: async function (jqXHR) {
      try {
        // A) 錯誤回應是 Blob：先試 JSON，再試文字
        if (jqXHR.response instanceof Blob) {
          const json = await tryParseJsonFromBlob(jqXHR.response);
          if (json) {
            const title = isNoCurveByJson(json) ? dict.info : dict.error;
            const msg   = isNoCurveByJson(json) ? dict.noCurve : (json?.res_msg || dict.httpFail);
            showAlert(title, msg);
            return;
          }
          const text = await tryReadTextFromBlob(jqXHR.response);
          if (text && isNoCurveByText(text)) {
            showAlert(dict.info, dict.noCurve);
            return;
          }
          if (text && text.trim()) {
            showAlert(dict.error, text.trim());
            return;
          }
          showAlert(dict.error, dict.httpFail);
          return;
        }

        // B) 有 responseText（字串）
        if (typeof jqXHR.responseText === 'string' && jqXHR.responseText.length) {
          try {
            const json = JSON.parse(jqXHR.responseText);
            const title = isNoCurveByJson(json) ? dict.info : dict.error;
            const msg   = isNoCurveByJson(json) ? dict.noCurve : (json?.res_msg || dict.httpFail);
            showAlert(title, msg);
          } catch {
            showAlert(dict.error, jqXHR.responseText);
          }
          return;
        }

        // C) jQuery 幫忙 parse 的 JSON
        if (jqXHR.responseJSON) {
          const json = jqXHR.responseJSON;
          const title = isNoCurveByJson(json) ? dict.info : dict.error;
          const msg   = isNoCurveByJson(json) ? dict.noCurve : (json?.res_msg || dict.httpFail);
          showAlert(title, msg);
          return;
        }

        // D) Fallback
        showAlert(dict.error, dict.httpFail);
      } finally {
        if ($btn) $btn.disabled = false;
      }
    }
  });
}




