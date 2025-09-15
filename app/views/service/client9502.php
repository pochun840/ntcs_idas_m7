<!doctype html>
<html lang="zh-Hant">
<meta charset="utf-8" />
<title>Customize List（結果一律取 DB 最後一筆）</title>
<style>
  :root { color-scheme: light dark; }
  body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", Arial; padding: 16px; }
  h1 { margin: 0 0 8px; font-size: 20px; }
  .toolbar { display:flex; gap:8px; align-items:center; margin: 8px 0 12px; flex-wrap: wrap; }
  .status { margin: 6px 0 12px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ddd; padding: 8px; }
  th { background: #f7f7f7; text-align: left; }
  tbody tr:nth-child(even){ background: #fafafa; }
  .small { font-size: 12px; color: #666; margin-top: 8px; }
  select, button, input[type="text"] { padding:6px 8px; }
</style>

<h1>Customize List</h1>

<div class="toolbar">
  <label>列來源埠（只拿結構，不拿數值）：
    <select id="port">
      <option value="9502">9502(廣達 自定義)</option>
      <option value="9501">9501(IDAS Agent)</option>
    </select>
  </label>

    <input id="kw" type="text" placeholder="過濾 No/Read/Input…" />

  <button id="btnReconnect" type="button">重新連線</button>
</div>

<div class="status" id="status">[INIT]</div>
<div class="status" id="dbStatus">[DB] 尚未連線</div>

<table id="tbl" aria-label="customize list table">
  <thead>
    <tr>
      <th style="width:80px;">No</th>
      <th>Read Position</th>
      <th>Input Position</th>
      <th>Result</th>
    </tr>
  </thead>
  <tbody></tbody>
</table>

<script>
(function () {
  const statusEl   = document.getElementById('status');
  const dbStatusEl = document.getElementById('dbStatus');
  const tbody      = document.querySelector('#tbl tbody');
  const portSel    = document.getElementById('port');
  const kwInput    = document.getElementById('kw');
  const btnReconnect = document.getElementById('btnReconnect');

  const host   = location.hostname || '127.0.0.1';
  const scheme = (location.protocol === 'https:') ? 'wss' : 'ws';

  // WebSocket 實體
  let wsDB = null;        // 永久連 9501，存 DB 最後一筆
  let wsList = null;      // 連選擇的埠，取得列結構
  let reconnectTimerList = null;
  let reconnectTimerDB   = null;

  // 狀態資料
  let lastDbObj = null;   // { column: value, ... }
  let lastRowsStructure = []; // [{no, read_position, input_position}], 不含 result
  let reconnectDelayDB   = 3000;
  let reconnectDelayList = 3000;

  function setStatus(s){ statusEl.textContent = s; }
  function setDbStatus(s){ dbStatusEl.textContent = s; }
  function listUrl(){ return `${scheme}://${host}:${portSel.value}/`; }
  function dbUrl(){   return `${scheme}://${host}:9501/`; }

  // 從訊息中擷取 JSON（伺服器可能前綴 "Client X said: "）
  function extractJsonString(s) {
    if (typeof s !== 'string') return null;
    const i = s.indexOf('{');
    return i === -1 ? null : s.slice(i);
  }

  // 將 DB 物件轉成「列結構」（不用 DB 值，只拿 key 放到 read_position）
  function objToStructureRows(obj) {
    const rows = [];
    let i = 1;
    for (const k of Object.keys(obj || {})) {
      rows.push({ no: i++, read_position: k, input_position: '' });
    }
    return rows;
  }

  // 正規化 Read Position：把 "#36 xxx" 或 "# 36xxx" 的前綴移除 → 取 "xxx"
  function normalizeReadKey(rp) {
    const s = String(rp || '');
    return s.replace(/^\s*#\s*\d+\s*/,'').trim();
  }

  // 依據「列結構 + lastDbObj」合成可顯示的 rows（Result 由 DB 取值）
  function buildRowsWithDb(structRows) {
    const db = lastDbObj || {};
    const rows = [];
    for (const r of (structRows || [])) {
      const key = normalizeReadKey(r.read_position);
      let val = '';
      if (key && Object.prototype.hasOwnProperty.call(db, key)) {
        const v = db[key];
        if (Array.isArray(v))       val = v.map(String).join(',');
        else if (v === null)        val = '';
        else if (typeof v === 'object') val = JSON.stringify(v);
        else                        val = String(v);
      }
      rows.push({
        no: r.no ?? '',
        read_position: r.read_position ?? '',
        input_position: r.input_position ?? '',
        result: val
      });
    }
    return rows;
  }

  // 畫表（加上前端關鍵字過濾）
  function renderRows(rows) {
    const kw = kwInput.value.trim().toLowerCase();
    tbody.innerHTML = '';
    if (!Array.isArray(rows)) return;

    const filtered = kw
      ? rows.filter(r => {
          const a = String(r.read_position ?? '').toLowerCase();
          const b = String(r.input_position ?? '').toLowerCase();
          const c = String(r.result ?? '').toLowerCase();
          const d = String(r.no ?? '').toLowerCase();
          return a.includes(kw) || b.includes(kw) || c.includes(kw) || d.includes(kw);
        })
      : rows;

    for (const r of filtered) {
      const tr = document.createElement('tr');
      const tdNo     = document.createElement('td');
      const tdRead   = document.createElement('td');
      const tdInput  = document.createElement('td');
      const tdResult = document.createElement('td');

      tdNo.textContent     = r.no ?? '';
      tdRead.textContent   = r.read_position ?? '';
      tdInput.textContent  = r.input_position ?? '';
      tdResult.textContent = r.result ?? '';

      tr.append(tdNo, tdRead, tdInput, tdResult);
      tbody.appendChild(tr);
    }
  }

  // 依目前 struct + DB 重新渲染
  function rerender() {
    const rows = buildRowsWithDb(lastRowsStructure);
    renderRows(rows);
  }

  // —— 9501：DB 連線 —— //
  function cleanupDB() {
    if (reconnectTimerDB) { clearTimeout(reconnectTimerDB); reconnectTimerDB = null; }
    if (wsDB) { try { wsDB.close(); } catch {} wsDB = null; }
  }

  function connectDB() {
    cleanupDB();
    setDbStatus(`[DB][CONNECTING] ${dbUrl()}`);

    try { wsDB = new WebSocket(dbUrl()); }
    catch (e) {
      setDbStatus(`[DB][ERROR] 建立連線失敗：${e?.message || e}`);
      reconnectTimerDB = setTimeout(connectDB, reconnectDelayDB);
      return;
    }

    wsDB.onopen = () => {
      setDbStatus(`[DB][OPEN] 已連線 ${dbUrl()}`);
      reconnectDelayDB = 3000;
    };

    wsDB.onmessage = (e) => {
      const raw = String(e.data);
      const jsonText = extractJsonString(raw) || '';
      try {
        const j = JSON.parse(jsonText);
      // 9501 預期是物件；若誤傳 rows/array 也應對一下
        if (j && typeof j === 'object' && !Array.isArray(j)) {
          lastDbObj = j;
        } else if (Array.isArray(j?.rows)) {
          // 如果 9501 回了 rows，嘗試還原為物件（用 read_position 做 key，result 做值）
          const obj = {};
          for (const r of j.rows) {
            const key = normalizeReadKey(r.read_position);
            if (key) obj[key] = r.result;
          }
          lastDbObj = obj;
        } else if (Array.isArray(j)) {
          // j 可能是 [{key:'x', value:'y'}] 之類，這裡不做推測，略過
        }
        setDbStatus(`[DB][OK] 更新 @ ${new Date().toLocaleString()}`);
        rerender(); // DB 更新後重算 Result
      } catch {
        setDbStatus('[DB][INFO] 不是 JSON，忽略');
      }
    };

    wsDB.onerror = () => setDbStatus('[DB][ERROR] 連線錯誤');

    wsDB.onclose = () => {
      setDbStatus('[DB][CLOSE] 3 秒後重連…');
      reconnectDelayDB = Math.min(reconnectDelayDB * 1.5, 30000);
      reconnectTimerDB = setTimeout(connectDB, 3000);
    };
  }

  // —— 列來源埠：List 連線 —— //
  function cleanupList() {
    if (reconnectTimerList) { clearTimeout(reconnectTimerList); reconnectTimerList = null; }
    if (wsList) { try { wsList.close(); } catch {} wsList = null; }
  }

  function connectList() {
    cleanupList();
    setStatus(`[CONNECTING] ${listUrl()}`);
    lastRowsStructure = [];
    renderRows([]); // 清空

    try { wsList = new WebSocket(listUrl()); }
    catch (e) {
      setStatus(`[ERROR] 建立連線失敗：${e?.message || e}`);
      reconnectTimerList = setTimeout(connectList, reconnectDelayList);
      return;
    }

    wsList.onopen = () => {
      setStatus(`[OPEN] 已連線 ${listUrl()}`);
      reconnectDelayList = 3000;
    };

    wsList.onmessage = (e) => {
      const raw = String(e.data);
      const jsonText = extractJsonString(raw) || '';
      try {
        const j = JSON.parse(jsonText);

        if (Array.isArray(j?.rows)) {
          // 9502 標準 rows
          lastRowsStructure = j.rows.map(x => ({
            no: x.no ?? '',
            read_position: x.read_position ?? '',
            input_position: x.input_position ?? ''
          }));
        } else if (j && typeof j === 'object' && !Array.isArray(j)) {
          // 9501 物件 → 轉成結構列（只拿 key 當 read_position）
          lastRowsStructure = objToStructureRows(j);
        } else if (Array.isArray(j)) {
          // 備援：直接是陣列就照欄位名猜
          lastRowsStructure = j.map((x,i) => ({
            no: x.no ?? (i+1),
            read_position: x.read_position ?? x.key ?? '',
            input_position: x.input_position ?? ''
          }));
        } else {
          // 非預期，忽略
        }

        setStatus(`[OK] 列結構 ${lastRowsStructure.length} 列 @ ${new Date().toLocaleString()}（來源埠 ${portSel.value}）`);
        rerender(); // 用 DB 值合成
      } catch {
        setStatus('[INFO] 非 JSON，忽略');
      }
    };

    wsList.onerror = () => setStatus('[ERROR] 連線錯誤');

    wsList.onclose = () => {
      setStatus('[CLOSE] 3 秒後重連…');
      reconnectDelayList = Math.min(reconnectDelayList * 1.5, 30000);
      reconnectTimerList = setTimeout(connectList, 3000);
    };
  }

  // 事件
  portSel.addEventListener('change', connectList);
  btnReconnect.addEventListener('click', () => { connectDB(); connectList(); });
  kwInput.addEventListener('input', rerender);

  // 啟動：先連 DB，再連列來源
  connectDB();
  connectList();
})();
</script>
</html>


<style>
#tbl thead th:nth-child(3),
#tbl tbody td:nth-child(3) {
  display: none;
}
/* 隱藏工具列中的關鍵字輸入與重新連線按鈕 */
#kw,
#btnReconnect {
  display: none;
}
</style>