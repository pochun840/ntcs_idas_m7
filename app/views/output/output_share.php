<script>

document.addEventListener('DOMContentLoaded', applyUnifiedUIFromStore);

// 🔝 放最上面，避免「初始化前被使用」的錯誤
let unifiedFlag = 0;
let _getOutputReqId = 0;

let enterPageYellowJobId = null; // 進入頁面時若 job_id 是黃底就記下
let clickedYellowJobId   = null; // 按 unified 時若 job_id 是黃底就記下
let _suppressYellowOnce = false;  // 下一次 get_output_by_job_id 執行時，禁用黃底



// 從 PHP 帶入目前 unified 的 jobid（可能為 null/空字串/數字）
const BOOT_FOCUSED_JOBID = <?php echo json_encode($data['focused_jobid'] ?? null); ?>;

var job_id; 
var output_event;
var temp;
var tempA;
var buttonDisabled = false;
var backgroundColorYellow = false;
var output_job;
var all_job;
var del_output_val;
var output_pinval;
var temp_event;


$(document).ready(function () {
  highlight_row_input('output_table');

  var all_output_job = BOOT_FOCUSED_JOBID;      // 從全域帶入
  job_id = all_output_job ? String(all_output_job) : '';
  output_job = job_id;

  // 先依 boot 狀態鎖按鈕（可選，但推薦）
  const btn = document.getElementById('Button_Select');
  if (btn) btn.disabled = !!(BOOT_FOCUSED_JOBID !== null && String(BOOT_FOCUSED_JOBID).length > 0);

  if (job_id) {
    get_output_by_job_id(job_id); // 內部會依 boot 狀態自動上黃、鎖按鈕
  }
});


document.addEventListener('DOMContentLoaded', function() {
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      var headerElements = document.querySelectorAll('.ajs-header');
      headerElements.forEach(function(headerElement) {
        headerElement.parentNode.removeChild(headerElement);
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
});

var modal = document.getElementById('newinput');

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}


function lockEventDropdownForNew(usedEvents = []) {
  // 清單可能是字串或數字；先正規化
  const usedSingle = usedEvents
    .map(v => parseInt(v, 10))
    .filter(n => Number.isFinite(n) && n >= 1 && n <= 11)
    .map(String);

  // 先重置整個下拉（移除既有 disabled/灰階）
  disableOptions('#Event_Option', [], false, true);

  // 灰階 + 禁用 已使用的 1~11
  disableOptions('#Event_Option', usedSingle, true, false);

  // 確保 12~16 不會被禁用（就算後端曾回傳）
  for (let i = 12; i <= 16; i++) {
    const opt = document.querySelector(`#Event_Option option[value="${i}"]`);
    if (opt) {
      opt.disabled = false;
      opt.style.color = '';
      opt.classList.remove('disabled_input');
    }
  }
}

// 提前宣告供事件處理用（避免多次綁定）
let filteredPins = [];

function handleEventChange(e) {

  const selectedId = e.target.value;
  const isGroupA = ['7', '8', '9'].includes(selectedId);
  const isGroupB = ['12', '13', '14', '15', '16'].includes(selectedId);

  if (isGroupA) {
    // 事件 7~9：所有 pin 的 _0/_1 禁用，只留 _2（且 time 全關）
    lockPin01ForGroupA({ pinCount: 11, idPrefix: 'pin' });
  } else {
    // 復原到「可選狀態」，再把已被佔用的 pin 重新鎖回去
    for (let i = 1; i <= 11; i++) {
      for (let j = 0; j <= 2; j++) {
        const el = document.getElementById(`pin${i}_${j}`);
        if (el) el.disabled = false;
      }
      const t = document.getElementById(`time${i}`);
      if (t) t.disabled = false;
    }
    // 重新套用「已被使用」的禁用規則
    disableElements(filteredPins);
  }

  if (isGroupB) {
    // 事件 12~16：禁用所有 *_1，時間全開
    for (let i = 1; i <= 11; i++) {
      const p1 = document.getElementById(`pin${i}_1`);
      if (p1) p1.disabled = true;
      const t = document.getElementById(`time${i}`);
      if (t) t.disabled = false;
    }
  }
}


// ---- unified 狀態工具 ----

function isJobIdYellow() {
  const el = document.getElementById('job_id');
  return el?.classList.contains('bg-yellow');
}


function setJobIdBg(isYellow) {
  const el = document.getElementById('job_id');
  if (!el) return;
  el.classList.toggle('bg-yellow', isYellow);
}


function setUnifiedState(job_id, enable) {
  unifiedFlag = enable ? 1 : 0;

  if (enable) {
    if (typeof enableButton === 'function') enableButton();
    if (typeof resetBackgroundColor === 'function') resetBackgroundColor();
    if (typeof alignsubmit === 'function') alignsubmit(job_id);
    setJobIdBg(true);
  } else {
    if (typeof resetalignsubmit === 'function') resetalignsubmit(job_id);
    setJobIdBg(false);
  }

  if (typeof syncButtonSelectState === 'function') syncButtonSelectState();
}


// ---- 初始：依背景色決定 unifiedFlag（不呼叫 alignsubmit/resetalignsubmit）----
document.addEventListener('DOMContentLoaded', () => {
  const el = document.getElementById('job_id');
  const isYellow = isJobIdYellow();
  unifiedFlag = isYellow ? 1 : 0;
  if (el && isYellow) {
    enterPageYellowJobId = el.value || '';
  }
});

/**
 * 自定義事件(12~16)時：禁用所有 pin 的「_1」radio；
 * 目前正在編輯的那組允許「_0 / _2」可操作。
 *
 * @param {Object} opts
 * @param {number|string} opts.wave          事件代碼 (e.g. 12~16)
 * @param {number|string} opts.output_pin    目前正在編輯的 pin 編號 (e.g. 1)
 * @param {number} [opts.pinCount=11]        總 pin 數
 * @param {string} [opts.idPrefix='edit_pin']radio 的 id 前綴；編輯畫面用 'edit_pin'，新增畫面可傳 'pin'
 */
function lockPin1ForCustomEvent({
  wave,
  output_pin,
  pinCount = 11,
  idPrefix = 'edit_pin'
} = {}) {
  const w = Number(wave);
  // 事件代碼 12~16 才套規則
  if (w < 12 || w > 16) return;

  const cur = String(output_pin);

  // 1) 禁用所有 pin 的 *_1
  for (let i = 1; i <= pinCount; i++) {
    const p1 = document.getElementById(`${idPrefix}${i}_1`);
    if (p1) p1.disabled = true;
  }

  // 2) 當前這組：_0 / _2 保持可操作；_1 取消勾選並禁用（保險）
  const p0 = document.getElementById(`${idPrefix}${cur}_0`);
  const p1 = document.getElementById(`${idPrefix}${cur}_1`);
  const p2 = document.getElementById(`${idPrefix}${cur}_2`);
  if (p0) p0.disabled = false;
  if (p2) p2.disabled = false;
  if (p1) { p1.checked = false; p1.disabled = true; }
}



function lockPin01ForGroupA({ pinCount = 11, idPrefix = 'pin' } = {}) {
  for (let i = 1; i <= pinCount; i++) {
    const p0 = document.getElementById(`${idPrefix}${i}_0`);
    const p1 = document.getElementById(`${idPrefix}${i}_1`);
    const p2 = document.getElementById(`${idPrefix}${i}_2`);

    if (p0) { p0.checked = false; p0.disabled = true; }
    if (p1) { p1.checked = false; p1.disabled = true; }
    // 注意：不要強制啟用 p2（避免覆蓋掉「已被占用」而被禁用的 pin）
    // if (p2 && !p2.disabled) p2.disabled = false;

    // 新增視窗(time*)：事件 7~9 不需時間，全部關閉
    const t = document.getElementById(`time${i}`);
    if (t) t.disabled = true;
  }
}

function lockPin01ForGroupAEdit({ currentPinNo, pinCount = 11, idPrefix = 'edit_pin' } = {}) {
  for (let i = 1; i <= pinCount; i++) {
    const p0 = document.getElementById(`${idPrefix}${i}_0`);
    const p1 = document.getElementById(`${idPrefix}${i}_1`);
    const p2 = document.getElementById(`${idPrefix}${i}_2`);
    if (p0) { p0.checked = false; p0.disabled = true; }
    if (p1) { p1.checked = false; p1.disabled = true; }
    if (p2) { p2.disabled = String(i) !== String(currentPinNo); } // 只放行當前 pin 的 _2
    const t = document.getElementById(`edit_time${i}`);
    if (t) t.disabled = true; // 7~9 不用時間
  }
}

// 讓「變淺」效果穩定
function ensureSelectBtnDimCSS() {
  if (document.getElementById('btnSelectDimCSS')) return;
  const tag = document.createElement('style');
  tag.id = 'btnSelectDimCSS';
  tag.textContent = `
  #Button_Select.btn-dimmed{
    opacity:.45 !important;
    filter:grayscale(40%) !important;
    cursor:not-allowed !important;
    pointer-events:none !important;
  }`;
  document.head.appendChild(tag);
}

// 切換 #Button_Select 的外觀與可用性
function setSelectBtnDisabled(disabled) {
  ensureSelectBtnDimCSS();
  const btn = document.getElementById('Button_Select');
  if (!btn) return;

  btn.setAttribute('aria-disabled', disabled ? 'true' : 'false');

  // 若是原生 button/input，順便切 disabled 屬性
  if (/^(BUTTON|INPUT)$/i.test(btn.nodeName)) {
    if (disabled) btn.setAttribute('disabled', 'disabled');
    else btn.removeAttribute('disabled');
  } else {
    // 非原生按鈕，補上可達性屬性
    if (disabled) {
      if (!btn.getAttribute('role')) btn.setAttribute('role', 'button');
      btn.setAttribute('tabindex', '-1');
    } else {
      btn.removeAttribute('tabindex');
    }
  }

  // 視覺「變淺」
  btn.classList.toggle('btn-dimmed', !!disabled);
  // 專案若有其它禁用 class，也一併同步
  ['disabled_input','disabled','btn-disabled','is-disabled'].forEach(cls => {
    btn.classList.toggle(cls, !!disabled);
  });

  if (!disabled) {
    btn.style.opacity = '';
    btn.style.filter = '';
    btn.style.pointerEvents = '';
    btn.style.cursor = '';
  }
}

// 鎖/解鎖「工作選擇」相關元件
function setJobPickerDisabled(disabled) {
  const picks = [
    document.getElementById('JobNameSelect'),
    document.getElementById('JobSelect1'),
  ].filter(Boolean);

  picks.forEach(sel => {
    sel.disabled = !!disabled;
    sel.classList.toggle('disabled_input', !!disabled);
    if (!disabled) {
      // 若曾把 option 設 disabled（例如複製或別處邏輯），解除時全開
      Array.from(sel.options).forEach(opt => {
        opt.disabled = false;
        opt.classList.remove('disabled_input');
      });
    }
  });
}

// === 統一以 localStorage 為主（避免 aria/樣式在換頁後丟失）===
const UNIFIED_LOCK_KEY = 'unified_locked';
const UNIFIED_JOB_KEY  = 'unified_job_id';

function isUnifiedLockedStored() {
  return localStorage.getItem(UNIFIED_LOCK_KEY) === '1';
}
function setUnifiedLockedStored(locked, jobId) {
  if (locked) {
    localStorage.setItem(UNIFIED_LOCK_KEY, '1');
    if (jobId != null) localStorage.setItem(UNIFIED_JOB_KEY, String(jobId));
  } else {
    localStorage.removeItem(UNIFIED_LOCK_KEY);
    localStorage.removeItem(UNIFIED_JOB_KEY);
  }
}
function applyUnifiedUIFromStore() {
  const locked = isUnifiedLockedStored();
  setSelectBtnDisabled(locked);
  setJobPickerDisabled(locked);
}


function crud_job_event(argument) {
    const table = document.getElementById('output_table');
    const eventOption = document.getElementById('Event_Option');
    const selectedRow = table.querySelector('tr.selected');

    const jobSelect = document.getElementById('JobNameSelect');
    const job_id =
        jobSelect?.value
        || document.getElementById('job_id')?.value
        || window.job_id
        || null;

    

    if (!job_id) return;

    if (selectedRow) {
        output_event = selectedRow.getAttribute('data-event');
        output_pinval = selectedRow.querySelector('[data-outputpin]')?.getAttribute('data-outputpin') || null;
        del_output_val = output_event;
    }

    const showModal = (id) => document.getElementById(id).style.display = 'block';

    if (argument === 'unified') {
        const el = document.getElementById('job_id');
        if (el && isJobIdYellow()) {
        clickedYellowJobId = el.value || '';
        // 若要持久化也可加：localStorage.setItem('clickedYellowJobId', clickedYellowJobId);
        }
    }
    

    switch (argument) {
        case 'del':
            if (del_output_val) {
                showOverlay();
                delete_output_id(job_id, del_output_val,output_pinval);
            }
        break;

        case 'new': {
            output_event  = null;
            output_pinval = null;
            del_output_val = null;
            temp = [];
            tempA = [];
            temp_event = [];

            // 清掉列表選取
            table.querySelectorAll('tr.selected').forEach(row => row.classList.remove('selected'));

            showOverlay();

            $.ajax({
                url: "?url=Outputs/get_output_by_job_id",
                method: "POST",
                data: { job_id },
                dataType: "json"
            }).done(function (data) {
                // 後端資料到手後再處理 UI
                temp       = Array.isArray(data?.temp)       ? data.temp       : [];
                tempA      = Array.isArray(data?.tempA)      ? data.tempA      : [];
                temp_event = Array.isArray(data?.temp_event) ? data.temp_event : [];

                // 1) 清理 radio 勾選、時間欄位
                clearAndDisableRadios(temp);
                resetTimeFields(1, 11);

                // 2) 把已被佔用的 pin（非編輯畫面）鎖回去
                filteredPins = temp.filter(id => id.includes('pin') && !id.includes('edit_pin'));
                disableElements(filteredPins);

                // 3) 開新增視窗
                showModal('new_output');

                // 4) 綁定事件（避免重複綁定）
                eventOption?.removeEventListener('change', handleEventChange);
                eventOption?.addEventListener('change', handleEventChange);

                // 5) 計算「已使用的一次性事件（1~11）」：以 payload 與目前列表雙保險
                const usedSingle = new Set(
                (Array.isArray(temp_event) ? temp_event : [])
                    .map(v => parseInt(v, 10))
                    .filter(v => v >= 1 && v <= 11)
                );
                // DOM fallback：讀當前表格中已存在的事件
                document.querySelectorAll('#output_jobid_select tr[data-event]').forEach(tr => {
                const eid = parseInt(tr.getAttribute('data-event'), 10);
                if (eid >= 1 && eid <= 11) usedSingle.add(eid);
                });

                // 6) 重置整個下拉再灰階「已使用的一次性事件」
                disableOptions('#Event_Option', [], false, true);               // reset
                disableOptions('#Event_Option', [...usedSingle], true, false);  // 只有 1~11 灰階禁選
                // 12~16 保持可選（可重複）→ 不要放進去

                // 7) 依目前選到的事件代碼，套 pin/time 的 UI 規則
                const curVal = eventOption?.value || '';
                if (['7','8','9'].includes(curVal)) {
                // 7~9：所有 pin 的 _0/_1 禁用、只留 _2；time 全關
                lockPin01ForGroupA({ pinCount: 11, idPrefix: 'pin' });
                } else if (['12','13','14','15','16'].includes(curVal)) {
                // 12~16：禁用所有 *_1（新增視窗）
                for (let i = 1; i <= 11; i++) {
                    const p1 = document.getElementById(`pin${i}_1`);
                    if (p1) p1.disabled = true;
                }
                // 時間欄位依實作需求是否開啟，這裡不強制關
                }

                // 8) 把「已使用的一次性事件」記起來，供送出前二次防呆
                window._usedOneShotEvents = usedSingle;

                // 9) 更新按鈕狀態
                syncButtonSelectState();
            }).fail(function (xhr, status, error) {
                console.error("取得 job output 設定失敗:", status, error);
                temp = tempA = temp_event = [];
            });

            break;
            }




        case 'edit': {
            if (!output_event) return;
            showOverlay();

            const selectedEditRows = document.querySelectorAll('#output_jobid_select tr.selected');
            if (!selectedEditRows.length) return;

            if (!output_pinval) return; // 需要先有被選到的 pin (從列表 data-outputpin 來)

            // 先向後端拿資料並渲染 DOM
            get_output_info(job_id, output_event, output_pinval);

            // 等待 DOM 更新完成後再處理互動邏輯
            setTimeout(() => {
                const currentPinNo  = String(output_pinval);
                const basePin       = `edit_pin${currentPinNo}`;
                const baseTime      = `edit_time${currentPinNo}`;

                const p0            = document.getElementById(`${basePin}_0`);
                const p1            = document.getElementById(`${basePin}_1`);
                const p2            = document.getElementById(`${basePin}_2`);
                const timeEl        = document.getElementById(baseTime);
                const eventOptionEl = document.getElementById('edit_event_option');

                const isCustom = () => {
                const w = Number(eventOptionEl?.value);
                return w >= 12 && w <= 16; // 自定義 12~16
                };
                const isGroupA = () => {
                const w = Number(eventOptionEl?.value);
                return w >= 7 && w <= 9;   // 事件 7~9
                };

                // 已被佔用的 pins（用於非當前 pin 的解鎖判斷）
                const usedPins = Array.from(document.querySelectorAll('#output_jobid_select [data-outputpin]'))
                .map(el => parseInt(el.getAttribute('data-outputpin'), 10))
                .filter(n => Number.isFinite(n));

                // ---- time 欄位：非當前組一律禁用（避免編到別組）
                const allTimeInputs = document.querySelectorAll('input[id^="edit_time"]');
                allTimeInputs.forEach(t => {
                const m = t.id.match(/^edit_time(\d+)$/);
                if (!m) return;
                const pinNum = m[1];
                if (pinNum !== currentPinNo) t.disabled = true;
                });

                // 暫存/還原 time 的小工具
                const saveAndDisable = (el) => {
                if (!el) return;
                if (el.value !== '') el.dataset.prev = el.value;
                el.disabled = true;
                };
                const enableAndRestore = (el) => {
                if (!el) return;
                el.disabled = false;
                if (el.value === '' && el.dataset.prev != null) el.value = el.dataset.prev;
                };
                if (timeEl) timeEl.addEventListener('input', () => { timeEl.dataset.prev = timeEl.value; });

                // 依規則鎖/開所有 edit_pin*
                const applyPinLock = () => {
                // ✅ 事件 7~9：所有 pin 的 _0/_1 全禁用；
                //    「當前 pin」的 _2 一定可用；其他 pin 的 _2 若未被佔用則解除禁用。
                if (isGroupA()) {
                    for (let i = 1; i <= 11; i++) {
                    const pin0 = document.getElementById(`edit_pin${i}_0`);
                    const pin1 = document.getElementById(`edit_pin${i}_1`);
                    const pin2 = document.getElementById(`edit_pin${i}_2`);
                    if (pin0) { pin0.checked = false; pin0.disabled = true; }
                    if (pin1) { pin1.checked = false; pin1.disabled = true; }
                    if (pin2) {
                        if (String(i) === currentPinNo) {
                        pin2.disabled = false; // 當前 pin 的 _2：一定可用
                        } else {
                        const isUsed = usedPins.includes(i);
                        pin2.disabled = isUsed; // 未被佔用 → 解除禁用；已佔用 → 保持禁用
                        }
                    }
                    const t = document.getElementById(`edit_time${i}`);
                    if (t) t.disabled = true; // 7~9 不用時間，全部關閉
                    }
                    return; // 別再往下跑，避免被重新啟用
                }

                const allPinRadios = document.querySelectorAll('input[type="radio"][id^="edit_pin"]');
                allPinRadios.forEach(r => {
                    const m = r.id.match(/^edit_pin(\d+)_([0-2])$/);
                    if (!m) return;
                    const pinNum = parseInt(m[1], 10);
                    const suffix = m[2];

                    // ✅ 自定義 12~16：所有 *_1 禁用且取消勾選；當前 pin 的 _0/_2 可用
                    if (isCustom()) {
                    if (suffix === '1') { r.checked = false; r.disabled = true; return; }
                    if (String(pinNum) === currentPinNo) {
                        r.disabled = false;
                    } else {
                        r.disabled = usedPins.includes(pinNum);
                    }
                    return;
                    }

                    // 其他事件：當前 pin 可用；其他 pin 依是否已被佔用鎖定
                    if (String(pinNum) === currentPinNo) {
                    r.disabled = false;
                    } else {
                    r.disabled = usedPins.includes(pinNum);
                    }
                });
                };

                // 同步 time 與鎖定規則
                const sync = () => {
                applyPinLock(); // 先套規則（7~9 特別處理；12~16 關所有 *_1）

                if (isGroupA()) {
                    // 7~9：所有時間欄都不使用 → 全關（已在 applyPinLock 做過一次，這裡保險再做）
                    for (let i = 1; i <= 11; i++) {
                    const t = document.getElementById(`edit_time${i}`);
                    if (t) t.disabled = true;
                    }
                    return;
                }

                // 其他事件：只有 _1 時開時間，其餘關閉
                if (p1?.checked) {
                    enableAndRestore(timeEl);
                } else {
                    saveAndDisable(timeEl);
                }
                };

                // 先確保當前組 p0/p2/time 可操作；p1 交給 applyPinLock 控制
                [p0, p2, timeEl].forEach(el => el && (el.disabled = false));

                // 綁定事件
                [p0, p1, p2].forEach(el => el && el.addEventListener('change', sync));
                if (eventOptionEl && !eventOptionEl._bindSyncCustom) {
                eventOptionEl.addEventListener('change', sync);
                eventOptionEl._bindSyncCustom = true;
                }

                // 進場先跑一次
                sync();
            }, 100);

            break;
            }



        case 'copy':

            
            const messages = {
                'en-us': "No event available to copy",
                'zh-tw': "沒有可複製的事件",
                'zh-cn': "没有可复制的事件"
            };

            // 假設語言來源 (可改成你系統裡的語言判斷方式)
            var language = getCookie('language');
            const msg = messages[language] || messages['en-us'];


            if (!output_event) {
                 if (typeof alertify !== 'undefined') {
                    alertify.alert(msg, function() {
                        // callback：使用者按下 OK 時
                    });

                    // 3 秒後自動關閉 alert 視窗
                    setTimeout(function() {
                        alertify.closeAll(); 
                    }, 3000);
                }

                document.querySelector(".main-content")?.classList.remove("overlay-active");
                if (typeof hideOverlay === 'function') hideOverlay();

                return;

            }
            showOverlay();

            const jobinfo = <?php echo json_encode($data['job_list_new']); ?>;
            document.getElementById("from_job_id").value = job_id;
            document.getElementById("from_job_name").value = jobinfo[job_id]?.JOBname ?? "";

            const jobSelect1 = document.getElementById('JobSelect1');
            Array.from(jobSelect1.options).forEach(opt => {
                if (opt.value === job_id) {
                    opt.disabled = true;
                    opt.classList.add('disabled_input');
                }
            });

            const selectedRows = document.querySelectorAll('#output_jobid_select tr.selected');
            showModal('copy_output');
          
        break;

        case 'unified': {
            if (!job_id) {
              if (typeof alertify !== 'undefined') setTimeout(() => alertify.closeAll(), 2000);
              break;
            }

            // 以 localStorage 的狀態為主（不要看 aria/樣式，避免換頁後不同步）
            const lockedNow = isUnifiedLockedStored();
            const willLock  = !lockedNow; // true=套用(鎖定/變淺)；false=解除(恢復)

            // 若有後端 API（回傳 Promise），可沿用；沒有就讓它為 null
            const maybePromise = (typeof setUnifiedState === 'function')
              ? setUnifiedState(job_id, willLock)
              : null;

            const finishUI = () => {
              // 持久化狀態 → 下次進來會自動還原
              setUnifiedLockedStored(willLock, job_id);

              // 立刻套 UI
              setSelectBtnDisabled(willLock);
              setJobPickerDisabled(willLock);

              // 關 overlay/spinner（若有）
              document.querySelector(".main-content")?.classList.remove("overlay-active");
              const sp = document.getElementById('spinner'); if (sp) sp.style.display = 'none';
            };

            const revertUI = () => {
              // 失敗 → 回復到原本狀態
              setUnifiedLockedStored(lockedNow, job_id);
              setSelectBtnDisabled(lockedNow);
              setJobPickerDisabled(lockedNow);
              document.querySelector(".main-content")?.classList.remove("overlay-active");
              const sp = document.getElementById('spinner'); if (sp) sp.style.display = 'none';
            };

            // showOverlay?.(); // 若有覆蓋層可打開

            if (maybePromise && typeof maybePromise.then === 'function') {
              maybePromise.then(finishUI).catch(revertUI);
            } else {
              finishUI();
            }
            break;
          }



        default:
            console.warn(`Unknown action: ${argument}`);
        break;
    }
}

// 專責把 output_unified 寫進後端（val=1 開；val=0 關）
let _unifiedXHR = null;
function unified_output(job_id, unifiedFlag) {
  if (!job_id) return;

  var val = (unifiedFlag === 1) ? 1 : 0;

  return $.ajax({
    url: '?url=Jobs/set_output_unified',
    method: 'POST',
    dataType: 'json',
    data: { jobid: job_id, val: val }
  }).done(function(resp){
    if (!resp || resp.ok !== true) {
      console.warn('set_output_unified failed:', resp);
    }
  }).fail(function(xhr, status, err){
    console.error('set_output_unified error:', status, err, xhr && xhr.responseText);
  });
}



// 原：function toggleElementsInRange(start, end, suffix, disable) {
function toggleElementsInRange(start, end, /*suffix(未使用)*/_, disable) {
  for (let i = start; i <= end; i++) {
    for (let j = 0; j <= 2; j++) {             // ★ 原本是 j <= 1
      const id = `pin${i}_${j}`;
      const el = document.getElementById(id);
      if (el) el.disabled = disable;
    }
    const timeEl = document.getElementById(`time${i}`);
    if (timeEl) timeEl.disabled = disable;
  }
}


var old_output_event; 
function job_confirm() {
  const select = document.getElementById("JobNameSelect");
  const jobid  = select ? String(select.value || "") : "";

  // 記住選擇
  localStorage.setItem("jobid", jobid);
  job_id  = jobid;
  all_job = jobid;

  if (!jobid) return;

  // 1) 清空列表
  const listEl = document.getElementById("output_jobid_select");
  if (listEl) listEl.innerHTML = "";

  // 2) 重置全域狀態
  window.output_event     = null;
  window.old_output_event = null;

  // 3) 清空/解鎖所有 radio（避免殘留）
  document.querySelectorAll('input[type="radio"]').forEach(r => {
    r.checked  = false;
    r.disabled = false;
  });

  // 4) 重置時間欄位（若有 util）
  if (typeof resetTimeFields === 'function') {
    resetTimeFields(1, 11);
  }

  // 5) 寫回目前 job_id 到輸入框
  const jobIdInput = document.getElementById("job_id");
  if (jobIdInput) jobIdInput.value = job_id;

  // 6) 交給統一流程：渲染列表、處理多語系、事件委派、按鈕狀態、黃底邏輯等
  get_output_by_job_id(job_id);
}


//delete
function delete_output_id(job_id, del_output_val,output_pinval) {
  if (!job_id) return;

  // 多語系
  var lang = getCookie('language') || 'en-us';
  var title, text, okText, cancelText, errMsg, cancelledMsg;

  if (lang === 'zh-tw') {
    title = '刪除事件';
    text  = '你確定要刪除此事件嗎？';
    okText = '確定';
    cancelText = '取消';
    errMsg = '刪除失敗，請稍後再試！';
    cancelledMsg = '已取消';
  } else if (lang === 'zh-cn') {
    title = '删除事件';
    text  = '你确定要删除该事件吗？';
    okText = '确定';
    cancelText = '取消';
    errMsg = '删除失败，请稍后再试！';
    cancelledMsg = '已取消';
  } else {
    title = 'Delete Event';
    text  = 'Are you sure you want to delete this event?';
    okText = 'OK';
    cancelText = 'Cancel';
    errMsg = 'Delete failed, please try again later.';
    cancelledMsg = 'Cancelled';
  }

  alertify.confirm(
    title,
    text,
    function onOk() {
      // 顯示遮罩＋spinner
      document.querySelector(".main-content")?.classList.add("overlay-active");
      document.getElementById('spinner').style.display = 'block';

      $.ajax({
        url: "?url=Outputs/delete_output",
        method: "POST",
        data: {
          job_id: job_id,
          output_event: del_output_val,
          output_pin:output_pinval
        },
        success: function(response) {

            // 依你現有邏輯：用通用的成功處理器
            input_success_res(response, job_id, () => get_output_by_job_id(job_id), 'edit_output');
            hideOverlay();
        },
        error: function(xhr, status, error) {
          alertify.error(errMsg);
          document.querySelector(".main-content")?.classList.remove("overlay-active");
          document.getElementById('spinner').style.display = 'none';
        }
      });
    },
    function onCancel() {
      document.querySelector(".main-content")?.classList.remove("overlay-active");
      hideOverlay();
      alertify.message(cancelledMsg);
    }
  ).set('labels', { ok: okText, cancel: cancelText });
}



function input_success_res(response, job_id, callbackFn, hideElementId = 'newinput') {
    // 安全解析回傳
    let data;
    try {
        data = (typeof response === 'string') ? JSON.parse(response) : response;
    } catch (e) {
        data = { res_type: 'Info', res_msg: String(response || 'Done') };
    }
    const title = data?.res_type || '';
    const msg   = data?.res_msg  || '';

    // 依語系決定 OK 文案（含常見變體）
    const rawLang = (typeof getCookie === 'function' && getCookie('language')) ||
                    document.documentElement.getAttribute('lang') || 'en-us';
    const l = String(rawLang).toLowerCase();
    let okLabel = 'OK';
    if (l === 'zh-tw' || l.includes('hant') || l.includes('tw') || l.includes('hk') || l.includes('mo')) {
        okLabel = '確定';
    } else if (l === 'zh-cn' || l.includes('hans') || l.includes('cn') || l.includes('sg')) {
        okLabel = '确定';
    }

    // 保險：有些版本要先設全域 glossary 才吃得到
    if (window.alertify?.defaults?.glossary) {
        try { alertify.defaults.glossary.ok = okLabel; } catch (e) {}
    }

    let autoTimer;

    // 顯示彈窗（單次也設 labels，雙保險）
    alertify
        .alert(title, msg, function () {
        clearTimeout(autoTimer);
        finalize();
        })
        .set('labels', { ok: okLabel });

    // 2 秒後自動關閉並收尾
    autoTimer = setTimeout(finalize, 2000);

    function finalize() {
        try { alertify.closeAll(); } catch (e) {}
        document.querySelector(".main-content")?.classList.remove("overlay-active");
        const sp = document.getElementById('spinner');
        if (sp) sp.style.display = 'none';

        // 重置 UI
        const selectEl = document.getElementById('Event_Option');
        if (selectEl) selectEl.value = '-1';
        document.querySelectorAll('input[name="pin_option"]').forEach(r => r.checked = false);
        document.querySelectorAll('input[name="gateconfirm"]').forEach(r => r.checked = false);

        // 更新資料後再回呼
        if (typeof get_input_by_job_id === 'function') {
        get_input_by_job_id(job_id, function () {
            if (typeof callbackFn === 'function') callbackFn();
        });
        } else if (typeof callbackFn === 'function') {
        callbackFn();
        }

        const hideEl = document.getElementById(hideElementId);
        if (hideEl) hideEl.style.display = 'none';
    }
}


function setJobIdHighlight(active) {
  const el = document.getElementById('job_id');
  if (!el) return;
  el.classList.toggle('bg-yellow', !!active);
  // 永遠清掉 inline，避免和 class 打架
  el.style.backgroundColor = '';
}


// 小幫手：語系正規化
function normalizeLang(raw) {
  let l = String(raw || '').toLowerCase();
  if (l === 'en') l = 'en-us';
  return ['en-us', 'zh-tw', 'zh-cn'].includes(l) ? l : 'en-us';
}

function get_output_by_job_id(job_id) {
  const reqId = ++_getOutputReqId;

  // unifiedFlag==0 且不是 boot 聚焦時才清黃
  if (unifiedFlag === 0 && !isBootFocusedCurrent()) {
    setJobIdHighlight(false);
  }

  const jobIdEl = document.getElementById('job_id');
  if (jobIdEl) jobIdEl.value = job_id || '';

  $.ajax({
    url: '?url=Outputs/get_output_by_job_id',
    method: 'POST',
    dataType: 'json',
    data: { job_id },
    success: function (data) {
      // 防止舊回應覆蓋新狀態
      if (reqId !== _getOutputReqId) return;

      const job_outputlist = (data && typeof data.job_outputlist === 'string') ? data.job_outputlist : '';
      // ★ 寫回全域，別用區域 const
      temp  = Array.isArray(data?.temp)  ? data.temp  : [];
      tempA = Array.isArray(data?.tempA) ? data.tempA : [];

      // 渲染表格
      const listEl = document.getElementById('output_jobid_select');
      if (listEl) listEl.innerHTML = job_outputlist;

      const jobSelectWrap = document.getElementById('JobSelect');
      if (jobSelectWrap) jobSelectWrap.style.display = 'none';

      // === 決定是否上黃底 ===
      const listEmpty  = (job_outputlist.trim() === '');
      const hasAnyData = !(listEmpty && temp.length === 0 && tempA.length === 0);

      const shouldYellowByUnified = (unifiedFlag !== 0);
      const isBootFocused = (
        BOOT_FOCUSED_JOBID !== null &&
        String(BOOT_FOCUSED_JOBID).length > 0 &&
        String(job_id) === String(BOOT_FOCUSED_JOBID)
      );

      // 尊重一次性抑制旗標
      const allowYellow = !_suppressYellowOnce && (shouldYellowByUnified || isBootFocused);

      if (jobIdEl) {
        jobIdEl.classList.remove('bg-yellow');
        jobIdEl.style.backgroundColor = '';
        if (allowYellow) jobIdEl.classList.add('bg-yellow');

        _suppressYellowOnce = false;

        //if (!hasAnyData && !isBootFocused && unifiedFlag === 0) {
          //jobIdEl.value = '';
        //}
      }

      // === Button_Select 狀態 ===
      const btn = document.getElementById('Button_Select');
      if (btn) {
        const disabled = isBootFocused ? true : (unifiedFlag !== 0);
        btn.disabled = disabled;
        btn.classList.toggle('disabled', disabled);
        btn.classList.toggle('disabled_input', disabled);
        btn.setAttribute('aria-disabled', String(disabled));
      }

      // ===== 語系套用（1~16）=====
      const labels = {
        'en-us': {1:'OK',2:'NG',3:'NG - High',4:'NG - Low',5:'OK - Sequence',6:'OK - Job',7:'Tool Running',8:'Tool Trigger',9:'Reverse',10:'BS',11:'Barcode',12:'UserDefine1',13:'UserDefine2',14:'UserDefine3',15:'UserDefine4',16:'UserDefine5'},
        'zh-tw': {1:'OK',2:'NG',3:'超出上限',4:'低於下限',5:'工序完成信號',6:'完工信號',7:'馬達信號',8:'啟動信號',9:'反向',10:'條碼停止',11:'條碼',12:'自定義1',13:'自定義2',14:'自定義3',15:'自定義4',16:'自定義5'},
        'zh-cn': {1:'OK',2:'NG',3:'超出上限',4:'低于下限',5:'工序完成信号',6:'工作任务完成信号',7:'马达信号',8:'启动信号',9:'反向',10:'条码停止',11:'条码',12:'自定义1',13:'自定义2',14:'自定义3',15:'自定义4',16:'自定义5'}
      };
      const lang = normalizeLang(data?.language || getCookie('language') || 'en-us');
      const L = labels[lang];

      if (listEl) {
        listEl.querySelectorAll('.evt-label[data-eid]').forEach(el => {
          const eid = parseInt(el.getAttribute('data-eid'), 10);
          if (Number.isFinite(eid) && L[eid]) el.textContent = L[eid];
        });

        // 重置選取狀態
        listEl.querySelectorAll('tr.selected').forEach(r => r.classList.remove('selected'));
        window.output_event  = null;
        window.output_pinval = null;

        // 事件委派（只綁一次）
        if (!listEl._boundClick) {
          listEl.addEventListener('click', function (e) {
            const row = e.target.closest('tr[data-event]');
            if (!row) return;
            listEl.querySelectorAll('tr.selected').forEach(r => r.classList.remove('selected'));
            row.classList.add('selected');
            window.output_event  = row.getAttribute('data-event');
            window.output_pinval = row.querySelector('[data-outputpin]')?.getAttribute('data-outputpin') || null;
          });
          listEl._boundClick = true;
        }
      }

      if (typeof syncButtonSelectState === 'function') {
        syncButtonSelectState();
      }
    },
    error: function (xhr, status, error) {
      console.error('AJAX request failed:', status, error, xhr?.responseText);
    }
  });
}







function collectPinValues(selector) {
    var pinOptions = document.querySelectorAll(selector);
    var selectedValues = [];

    pinOptions.forEach(function(option) {
        if (option.checked){ 
            var radioInfo = {
                id: option.id,
                value: option.value
            };
            selectedValues.push(radioInfo);
        }
    });

    return selectedValues;
}


function create_output_id() {
  var output_event = document.getElementById("Event_Option").value;
  var pinval = collectPinValues('input[name="pin_option"]');

  if (!pinval || !pinval[0]) {
    console.error("No pinval found or pinval[0] is undefined.");
    return false;
  }

  var pin_old = pinval[0]['id'];
  var wave = pinval[0]['value'];

  var match = pin_old.match(/\d+/);
  var output_pin = match ? parseInt(match[0], 10) : null;

  var time_ms = 'time' + output_pin;
  var wave_on_raw = document.getElementById(time_ms)?.value ?? '';
  var wave_on = Number(wave_on_raw); // 確保是數字

  // 多語言
  var lang = (typeof getCookie === 'function' ? getCookie('language') : 'en-us') || 'en-us';
  lang = String(lang).toLowerCase();
  if (lang === 'en') lang = 'en-us';
  if (!['zh-tw','zh-cn','en-us'].includes(lang)) lang = 'en-us';

  var titles = {
    'en-us': 'Warning',
    'zh-tw': '警告',
    'zh-cn': '警告'
  };

  var okTexts = {
    'en-us': 'OK',
    'zh-tw': '確定',
    'zh-cn': '确定'
  };

  var messages = {
    'en-us': "Please enter a wave value between 100 and 10000.",
    'zh-tw': "請輸入介於 100 到 10000 之間的脈波時間。",
    'zh-cn': "请输入介于 100 到 10000 之间的脉波时间。"
  };

  // 盡量設定全域 glossary（支援的 alertify 版本會吃到）
  try {
    if (window.alertify?.defaults?.glossary) {
      alertify.defaults.glossary.ok = okTexts[lang];
      // alertify.confirm 需要 Cancel 時再設定：
      alertify.defaults.glossary.cancel = (lang === 'zh-tw' || lang === 'zh-cn') ? '取消' : 'Cancel';
    }
  } catch (e) {}

  // 驗證：除跳過清單外，wave==1 時需檢查範圍
  const skipEvents = [7,8,9,10,11,12,13,14,15,16];
  if (wave == 1 && !skipEvents.includes(Number(output_event))) {
    if (!Number.isFinite(wave_on) || wave_on < 100 || wave_on > 10000) {
      // 本地化 alert + OK 按鈕
      const dlg = alertify
        .alert(titles[lang], messages[lang], function () {
          clearTimeout(autoCloseTimer);
        })
        .set('labels', { ok: okTexts[lang] })
        .set('movable', false);

      const autoCloseTimer = setTimeout(function () {
        try { alertify.closeAll(); } catch (e) {}
      }, 3000);

      return false; // 中止
    }
  }

  if (typeof job_id !== 'undefined' && job_id) {
    document.getElementById('spinner').style.display = 'block';

    $.ajax({
      url: "?url=Outputs/create_output_event",
      method: "POST",
      data: {
        job_id: job_id,
        output_pin: output_pin,
        output_event: output_event,
        wave: wave,
        wave_on: wave_on
      },
      success: function(response) {
        output_success_res(response, job_id, get_output_by_job_id, 'new_output');
        hideOverlay();
        resetBackgroundColor();
      },
      error: function(xhr, status, error) {
        console.error("AJAX request failed:", status, error);
      }
    });
  }
}


function edit_output_id() {
  var select = document.getElementById("edit_event_option");
  if (!select) {
    console.error('[edit_output_id] #edit_event_option not found');
    return;
  }

  var output_event = select.value;
  var pinval       = collectPinValues('input[name="edit_pin_option"]');
  if (!pinval || !pinval.length) {
    alertify && alertify.alert('請先選擇要編輯的輸出腳位');
    return;
  }

  var pin_old      = pinval[0]['id'];        // e.g. "edit_pin2_1"
  var wave         = pinval[0]['value'];     // 0/1/2...
  var match        = pin_old.match(/\d+/);
  var output_pin   = match ? parseInt(match[0], 10) : null;

  if (!job_id || !output_pin) {
    alertify && alertify.alert('資料不完整，請重新選取事件/腳位');
    return;
  }

  var time_ms_id = 'edit_time' + output_pin;
  var wave_on_el = document.getElementById(time_ms_id);
  var wave_on    = wave_on_el ? wave_on_el.value : '';

  // === 語系偵測（優先順序：window.CURR_LANG → <html lang> → #curr_lang → 預設 en-us）===
  var LANG = (window.CURR_LANG || document.documentElement.lang || document.getElementById('curr_lang')?.value || 'en-us').toLowerCase();

  // === 動態訊息：空值與範圍錯誤都顯示「輸入框{id}數值不在 100 到 10000 之間。」===
  var MIN = 100, MAX = 10000;
  var MSG = {
    'en-us': {
      empty: (id, min, max) => `Input ${id} must be an integer between ${min} and ${max}.`,
      range: (id, min, max) => `Input ${id} must be an integer between ${min} and ${max}.`
    },
    'zh-tw': {
      empty: (id, min, max) => `輸入框${id}數值不在 ${min} 到 ${max} 之間。`,
      range: (id, min, max) => `輸入框${id}數值不在 ${min} 到 ${max} 之間。`
    },
    'zh-cn': {
      empty: (id, min, max) => `输入框${id}数值不在 ${min} 到 ${max} 之间。`,
      range: (id, min, max) => `输入框${id}数值不在 ${min} 到 ${max} 之间。`
    }
  };
  if (!MSG[LANG]) LANG = 'en-us';

  // === wave==1：必填 + 範圍（整數）檢查 ===
  if (Number(wave) === 1) {
    var v = String(wave_on ?? '').trim();

    if (v === '') {
      alertify && alertify.alert(MSG[LANG].empty(output_pin, MIN, MAX));
      if (wave_on_el) wave_on_el.focus();
      return;
    }

    var n = Number(v);
    if (!Number.isFinite(n) || !Number.isInteger(n) || n < MIN || n > MAX) {
      alertify && alertify.alert(MSG[LANG].range(output_pin, MIN, MAX));
      if (wave_on_el) wave_on_el.focus();
      return;
    }

    // 正規化為整數字串再送出
    wave_on = String(parseInt(n, 10));
  }

  // 顯示遮罩 + spinner
  document.querySelector(".main-content")?.classList.add("overlay-active");
  var spinner = document.getElementById('spinner');
  if (spinner) spinner.style.display = 'block';

  // 小工具：真正送出「編輯」
  function doSave() {
    $.ajax({
      url: "?url=Outputs/edit_output_event",
      method: "POST",
      data: {
        job_id: job_id,
        output_pin: output_pin,
        output_event: output_event,      // 目標事件（ex: NG）
        wave: wave,
        wave_on: wave_on,
        old_output_event: old_output_event // 後端若需要比對舊事件（未定義時等於 undefined）
      },
      success: function (response) {
        output_success_res(response, job_id, get_output_by_job_id, 'edit_output');
        hideOverlay();
      },
      error: function (xhr, status, error) {
        console.error("[edit_output_id] save failed:", status, error);
        hideOverlay();
        if (spinner) spinner.style.display = 'none';
        alertify && alertify.alert('編輯失敗，請稍後再試');
      }
    });
  }

  // 先「靜默刪除」目標事件（若存在就清掉），避免唯一性衝突
  // 刪除結束（成功/失敗/不存在）都一律往下 doSave()
  $.ajax({
    url: "?url=Outputs/delete_output",
    method: "POST",
    data: {
      job_id: job_id,
      output_event: output_event,   // 要換成的事件（先清除可能已有的）
      output_pin: output_pin
    },
    complete: function () {
      // 無論刪除成功/失敗與否，皆嘗試儲存（若原本就不存在，這裡等於 no-op）
      doSave();
    }
  });
}



function resetalignsubmit(job_id) {
    unifiedFlag = 0;   // ✅ 執行 resetalignsubmit 時設回 0

    // ★ 取得畫面上 id="job_id" 的值（即使 disabled 也能讀到）
    const el = document.getElementById('job_id');
    const domJobId = (el && typeof el.value === 'string') ? el.value.trim() : '';

    // ★ 決定要用哪個 job id：DOM 優先，其次用傳入參數
    const effectiveJobId = domJobId || job_id || '';
    var job_id_new = 0;

    if(job_id_new == 0){
        $.ajax({
            url: "?url=Outputs/output_alljob",
            method: "POST",
            data: {
                job_id: domJobId
            },
            success: function (response) {
                // ★ 這次刷新不要把 job_id 上黃
                _suppressYellowOnce = true;
                get_output_by_job_id(effectiveJobId);
                //get_output_by_job_id(job_id);
            },
            error: function (xhr, status, error) {

            }
        });

    }

}
function alignsubmit(job_id) {
    if (job_id) {
        $.ajax({
            url: "?url=Outputs/output_alljob",
            method: "POST",
            data: {
                job_id: job_id
            },
            success: function (response) {
                get_output_by_job_id(job_id);
            
                buttonDisabled = !buttonDisabled;
                document.getElementById('Button_Select').disabled = buttonDisabled;
     
                backgroundColorYellow = !backgroundColorYellow;
                if (backgroundColorYellow) {
                    setJobIdHighlight(true);
                } else {
                    document.getElementById('job_id').style.backgroundColor = '';
                }
            },
            error: function (xhr, status, error) {

            }
        });
    }
}
//copy
function copy_output_id(){

    var language = getCookie('language');
    if(language == "zh-cn"){
        var text_info ='若设定已存在，将会取代原有设定';
    }else if(language == "zh-tw"){
        var text_info ='若設定已存在，將會取代原有設定';
    }else{
        var text_info ='If the job input already exists, it will replace the original setting';
    }
    alertify.confirm( text_info, function (e) {
        if (e) {
            var to_job_id = document.getElementById("JobSelect1").value;
            if(to_job_id){
                document.getElementById('spinner').style.display = 'block';
                $.ajax({
                    url: "?url=Outputs/copy_output",
                    method: "POST",
                    data: { 
                        from_job_id: job_id,
                        to_job_id: to_job_id
                    },
                    success: function(response) {
                        output_success_res(response, job_id, get_output_by_job_id, 'copy_output');
                        hideOverlay();

                    },
                    error: function(xhr, status, error) {
                        
                    }
                });
        
            } 
        } else {
            // cancel
            hideOverlay();
        }
    });
    document.getElementById('copy_output').style.display='none';
}

function updateInputsBasedOnRadioSelection() {
   
    for (let i = 1; i <= 11; i++) {
        let radioId = 'pin' + i + '_3';
        let inputId = 'time' + i;
        
        let radioElement = document.getElementById(radioId);
        let inputElement = document.getElementById(inputId);

        if (radioElement && inputElement) {
            inputElement.disabled = !radioElement.checked;
        }
    }
}


function get_output_info(job_id, output_event, output_pin_param) {
  if (!job_id || !output_event) return;

  $.ajax({
    url: "?url=Outputs/check_job_event",
    method: "POST",
    data: { job_id, output_event, output_pin: output_pin_param || '' },
    success: function (response) {
      if (!response || response === 'no_data') {
        getLanguageMessage('language');
        return;
      }

      // 顯示編輯視窗
      const editModal = document.getElementById('edit_output');
      if (editModal) editModal.style.display = 'block';

      // 解析回傳
      const data = (typeof response === 'string') ? JSON.parse(response) : response;

      // ★ 優先用呼叫端傳入的 pin，沒有才用後端回傳
      const output_pin = String(output_pin_param ?? data.Pin);
      const signal     = data.signal;        // 0 / 1 / 2
      const wave       = Number(data.EvenID);
      const wave_on    = data.durate;

      // ===== 小工具 =====
      const resetAll = () => {
        for (let i = 1; i <= 11; i++) {
          for (let j = 0; j <= 2; j++) {
            const r = document.getElementById(`edit_pin${i}_${j}`);
            if (r) { r.checked = false; r.disabled = true; }
          }
          const t = document.getElementById(`edit_time${i}`);
          if (t) { t.value = ''; t.disabled = true; }
        }
      };

      const applyGroupAEdit = (currentPin) => {
        for (let i = 1; i <= 11; i++) {
          const p0 = document.getElementById(`edit_pin${i}_0`);
          const p1 = document.getElementById(`edit_pin${i}_1`);
          const p2 = document.getElementById(`edit_pin${i}_2`);
          if (p0) { p0.checked = false; p0.disabled = true; }
          if (p1) { p1.checked = false; p1.disabled = true; }
          if (p2) { p2.disabled = String(i) !== String(currentPin); } // 只放行當前 pin 的 _2
          const t = document.getElementById(`edit_time${i}`);
          if (t) t.disabled = true; // 7~9 不用時間
        }
      };

      const applyCustomEdit = (currentPin) => {
        for (let i = 1; i <= 11; i++) {
          const p0 = document.getElementById(`edit_pin${i}_0`);
          const p1 = document.getElementById(`edit_pin${i}_1`);
          const p2 = document.getElementById(`edit_pin${i}_2`);
          if (p1) { p1.checked = false; p1.disabled = true; } // 所有 *_1 禁用
          if (String(i) === String(currentPin)) {
            if (p0) p0.disabled = false;
            if (p2) p2.disabled = false;
          }
          const t = document.getElementById(`edit_time${i}`);
          if (t) t.disabled = String(i) !== String(currentPin); // 只開當前 pin 的時間
        }
      };

      const applyWave6 = () => {
        for (let i = 1; i <= 11; i++) {
          const p0 = document.getElementById(`edit_pin${i}_0`);
          const p1 = document.getElementById(`edit_pin${i}_1`);
          if (p0) p0.disabled = true;
          if (p1) p1.disabled = true;
        }
      };

      // ===== 套 UI =====
      // 設定事件選單
      const eventOption = document.getElementById('edit_event_option');
      if (eventOption) eventOption.value = wave;

      //啟用下拉，並將「其他列已使用的一次性事件(1~11)」灰掉
      setupEditEventDropdownForEdit(eventOption, wave);


      // 全數重置
      resetAll();

      // 勾回這筆資料
      const currentRadioId = `edit_pin${output_pin}_${signal}`;
      const currentRadio   = document.getElementById(currentRadioId);
      if (currentRadio) { currentRadio.disabled = false; currentRadio.checked = true; }

      const currentTime = document.getElementById(`edit_time${output_pin}`);
      if (currentTime) currentTime.value = (wave_on === '' || wave_on === '0') ? '' : wave_on;

      // 依事件代碼套規則
      if ([7, 8, 9].includes(wave)) {
        // 7~9：只允許當前 pin 的 _2；時間全關
        applyGroupAEdit(output_pin);
      } else if (wave >= 12 && wave <= 16) {
        // 12~16：所有 *_1 禁用，當前 pin 的 _0/_2 可用；只開當前時間
        applyCustomEdit(output_pin);
      } else if (wave === 6) {
        // 6：所有 *_0/*_1 禁用；當前 pin 的 _2 可用；時間依 signal
        applyWave6();
        const p2 = document.getElementById(`edit_pin${output_pin}_2`);
        if (p2) p2.disabled = false;
        if (currentTime) currentTime.disabled = (signal != 1);
      } else {
        // 其他：當前 pin 三顆都開；時間依 signal
        const p0 = document.getElementById(`edit_pin${output_pin}_0`);
        const p1 = document.getElementById(`edit_pin${output_pin}_1`);
        const p2 = document.getElementById(`edit_pin${output_pin}_2`);
        if (p0) p0.disabled = false;
        if (p1) p1.disabled = false;
        if (p2) p2.disabled = false;
        if (currentTime) currentTime.disabled = (signal != 1);
      }

      // 事件選單切換時，維持在同一顆 pin 上操作
      if (eventOption && !eventOption._bindEditRules) {
        eventOption.addEventListener('change', () => {
          const newWave = Number(eventOption.value);

          // 先重置
          resetAll();

          // 保留這筆的 radio 勾選與可操作
          const keep = document.getElementById(`edit_pin${output_pin}_${signal}`);
          if (keep) { keep.disabled = false; keep.checked = true; }

          // 重新套規則（同上）
          if ([7, 8, 9].includes(newWave)) {
            applyGroupAEdit(output_pin);
          } else if (newWave >= 12 && newWave <= 16) {
            applyCustomEdit(output_pin);
          } else if (newWave === 6) {
            applyWave6();
            const p2 = document.getElementById(`edit_pin${output_pin}_2`);
            if (p2) p2.disabled = false;
            const t = document.getElementById(`edit_time${output_pin}`);
            if (t) t.disabled = (signal != 1);
          } else {
            const p0 = document.getElementById(`edit_pin${output_pin}_0`);
            const p1 = document.getElementById(`edit_pin${output_pin}_1`);
            const p2 = document.getElementById(`edit_pin${output_pin}_2`);
            if (p0) p0.disabled = false;
            if (p1) p1.disabled = false;
            if (p2) p2.disabled = false;
            const t = document.getElementById(`edit_time${output_pin}`);
            if (t) t.disabled = (signal != 1);
          }
        });
        eventOption._bindEditRules = true;
      }

      // 記下舊事件代碼（你原本有在用）
      old_output_event = output_event;
    },
    error: function (xhr, status, error) {
      console.error("AJAX request failed:", status, error);
    }
  });
}





function toggleOnputTime(inputId, checked, option) {
    var inputElement = document.getElementById(inputId);
    if (!inputElement) {
        return; 
    }

    if (inputElement.type === 'checkbox' || inputElement.type === 'radio') {

        if (inputElement.checked !== checked) {
           
        }
    }
    
 
    if(option == 1 || option == 3){
        var newId = inputId.replace(/^pin(\d+)_\d+$/, 'time$1');
        
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = true;
        }
    }else{
        var newId = inputId.replace(/^pin(\d+)_\d+$/, 'time$1');
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = false;
        }
    }    
}





function toggleOnputTime_edit(inputId, checked, option) {
    var inputElement = document.getElementById(inputId);
    
    if (!inputElement) {
        return; 
    }

   
    if (inputElement.type === 'checkbox' || inputElement.type === 'radio') {

        if (inputElement.checked !== checked) {
           
        }
    }

    if(option == 1 || option == 3){
        var newId = inputId.replace(/^edit_pin(\d+)_\d+$/, 'edit_time$1');
        
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = true;
        }
    }else{
        var newId = inputId.replace(/^edit_pin(\d+)_\d+$/, 'edit_time$1');
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = false;
        }
    }    
}


function disableElements(filtered_array) {
    // 生成新的 id 数组，去除末尾的数字并添加 "_0", "_1", "_2" 和 "time1" 到 "time11"
    let new_array = filtered_array
        .map(item => item.replace(/_\d$/, ''))  // 去除原始字符串末尾的数字
        .flatMap(item => {
            let result = [
                item + "_0",
                item + "_1",
                item + "_2"
            ];

            // 新增 "time" + 1 到 11
            for (let i = 1; i <= 11; i++) {
                result.push("time" + i);
            }

            return result;
        });

    // 遍历新生成的 id 数组，如果元素存在就禁用它
    new_array.forEach(id => {
        let element = document.getElementById(id); 
        if (element) {
            element.disabled = true;  // 禁用该元素
        }
    });
}


function getLanguageMessage(cookieName) {
    var value = "; " + document.cookie;
    var parts = value.split("; " + cookieName + "=");
    var language = (parts.length == 2) ? parts.pop().split(";").shift() : '';
    var message;
    if (language === 'en-us') {
       message =  'Please select the event to delete';
    } else if (language === 'zh-cn') {
       message =  '请选择要删除的事件';
    } else if (language === 'zh-tw') {
       message =  '請點選要刪除的事件';
    } else {
      message =  'Please select the event to delete';
    }
   alertify.alert(message);
}


function showOverlay() {
    document.getElementById("modal-overlay").style.display = "block";
}

function hideOverlay() {
    document.getElementById("modal-overlay").style.display = "none";
}

function output_success_res(response, job_id, callbackFn, hideElementId = 'newinput') {
    var responseData = JSON.parse(response);
    alertify.alert(responseData.res_type, responseData.res_msg);

    setTimeout(function () {
        alertify.closeAll();
        document.querySelector(".main-content").classList.remove("overlay-active");
        document.getElementById('spinner').style.display = 'none';

        if (typeof callbackFn === 'function') {
            callbackFn(job_id);
        }
    }, 2000);

    const hideEl = document.getElementById(hideElementId);
    if (hideEl) hideEl.style.display = 'none';
}


function clearAndDisableRadios(ids = []) {
    ids.forEach(id => {
        const radio = document.getElementById(id);
        if (radio?.type === 'radio') {
            radio.checked = false;
            radio.disabled = true;
        }
    });
}

function resetTimeFields(start, end) {
    for (let i = start; i <= end; i++) {
        const el = document.getElementById(`time${i}`);
        if (el) {
            el.value = '';
            el.disabled = false;
        }
    }
}

function disableAllPinsAndTimes(count) {
    for (let i = 1; i <= count; i++) {
        const pin = document.getElementById(`pin${i}_1`);
        const time = document.getElementById(`time${i}`);
        if (pin) pin.disabled = true;
        if (time) time.disabled = true;
    }
}

function disableOptions(selector, values = [], gray = false, reset = false) {

    if (!Array.isArray(values)) {
        console.warn("disableOptions: 'values' 不是陣列，自動轉換");
        values = [values];
    }

    const valueSet = values.map(String);

    const options = document.querySelectorAll(`${selector} option`);
    if (!options.length) return;

    options.forEach(opt => {
        if (reset) {
            opt.disabled = false;
            opt.style.color = '';
            opt.classList.remove('disabled_input');
        }

        if (valueSet.includes(opt.value)) {
            opt.disabled = true;
            opt.classList.add('disabled_input');
            if (gray) opt.style.color = 'gray';
        }
    });
}


/**
 * 從列表 DOM 收集已被使用的「一次性事件」(1~11)
 * @param {string} tableSelector  列表選擇器（預設 '#output_jobid_select'）
 * @returns {Set<number>}
 */
function collectUsedOneShotEventsFromList(tableSelector = '#output_jobid_select') {
  const used = new Set();
  document.querySelectorAll(`${tableSelector} tr[data-event]`).forEach(tr => {
    const eid = parseInt(tr.getAttribute('data-event'), 10);
    if (eid >= 1 && eid <= 11) used.add(eid);
  });
  return used;
}


/**
 * 編輯模式：啟用下拉，灰掉「其他列已使用」的一次性事件 (1~11)。
 * - currentWave：本列事件值，需保持可選
 */
function setupEditEventDropdownForEdit(selectEl, currentWave) {
  if (!selectEl) return;

  // 先重置整個下拉（移除既有 disabled/灰階）
  disableOptions('#edit_event_option', [], false, true);

  // 讀取目前列表已使用的一次性事件 (1~11)
  const usedOneShots = collectUsedOneShotEventsFromList();

  // 把本列的事件從 used 集合移除（避免自己也被灰掉）
  usedOneShots.delete(Number(currentWave));

  // 只灰 1~11（一次性事件）；12~16 可重複 -> 不處理
  disableOptions('#edit_event_option', Array.from(usedOneShots), true, false);

  // 確保下拉是啟用狀態（可選）
  selectEl.disabled = false;
  selectEl.classList.remove('select-locked');
  selectEl.removeAttribute('aria-disabled');
  selectEl.removeAttribute('tabindex');
  selectEl.title = ''; // 清除鎖定提示
}



// ✅ 抽成共用復原函式
function restoreUnifiedState(options = {}) {
    const {
        // 允許自訂 selector 以符合你的頁面
        buttonsSelector = '.unified-btn, .apply-btn',   // 被 enableButton() 放開的按鈕
        rowsSelector    = '#output_jobid_select tr, #output_table tr', // 被 resetBackgroundColor() 影響的列
        highlightClasses = ['highlight', 'selected'],   // 需要移除的樣式 class
    } = options;

    // 1) 還原按鈕狀態（若專案有 disableButton()，優先用）
    if (typeof disableButton === 'function') {
        disableButton();
    } else {
        document.querySelectorAll(buttonsSelector).forEach(btn => {
            btn.disabled = true;
        });
    }

    // 2) 還原背景/選取（若專案有 restoreBackgroundColor()，優先用）
    if (typeof restoreBackgroundColor === 'function') {
        restoreBackgroundColor();
    } else {
        document.querySelectorAll(rowsSelector).forEach(tr => {
            // 清除可能的 inline 樣式
            tr.style.backgroundColor = '';
            // 清除可能加上的 class
            highlightClasses.forEach(cls => tr.classList.remove(cls));
        });
    }

    // 3) 關閉 overlay（若有）
    if (typeof hideOverlay === 'function') {
        hideOverlay();
    }
}


function isBootFocusedCurrent(){
    const el = document.getElementById('job_id');
    return (BOOT_FOCUSED_JOBID !== null &&
    String(BOOT_FOCUSED_JOBID).length > 0 &&
    el && String(el.value) === String(BOOT_FOCUSED_JOBID));
}


function syncButtonSelectState() {
  const btnSelect = document.getElementById('Button_Select');
  if (btnSelect) {
    // unifiedFlag=0 可點，反之鎖住
    btnSelect.disabled = (unifiedFlag !== 0);
  }

  // unifiedFlag 為 0，但不是 boot 聚焦時才清黃
  if (unifiedFlag === 0 && !isBootFocusedCurrent()) {
    const jobIdEl = document.getElementById('job_id');
    if (jobIdEl) {
      const hasYellowClass   = jobIdEl.classList?.contains('bg-yellow');
      const inlineIsYellow   = (jobIdEl.style.backgroundColor || '').toLowerCase() === 'yellow';
      const computedIsYellow = window.getComputedStyle(jobIdEl).backgroundColor === 'rgb(255, 255, 0)';
      if (hasYellowClass || inlineIsYellow || computedIsYellow) {
        jobIdEl.classList.remove('bg-yellow');
        jobIdEl.style.backgroundColor = '';
      }
    }
  }
}

</script>

<style>
/* 表格外觀（保留原設定） */
#output_table td,
#output_table th {
  width: 100px;
  padding: 10px;
}

/* 統一：只靠 .bg-yellow 控制黃色高亮 */
.bg-yellow {
  background-color: #ffea00 !important;
  color: #000 !important;
  -webkit-text-fill-color: #000; /* 修 Safari/Chromium 對 disabled 文字顏色 */
}

/* 讓 disabled 的 input 也能正常顯示黃色，不被灰度影響 */
input#job_id.bg-yellow[disabled] {
  opacity: 1;
}


</style>