
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table>
            <tr id="header">
                <td width="100%">
                    <h3><?php echo "Customize"; ?></h3>
                </td>
                <td>
                    <button id="home" class="w3-btn w3-round-large" style="height:50px;padding: 0" onclick="window.location.href='./?url=Dashboards'"> <img src="../public/img/btn_home.png"></button>
                </td> 
            </tr>
        </table>
    </div>

    
    <?php
        // --- 翻譯文字 ---
        $uiLang = $_SESSION['language'] ?? 'en-us';
        $L = [
            'en-us' => [
                'no'                  => 'NO',
                'read'                => 'Read Position',
                'input'               => 'Input Position',
                'result'              => 'Result',
                'add'                 => 'Add Row',
                'save'                => 'Save',
                'delete'              => 'Delete',
                'placeholder_read'    => 'Enter read location…',
                'placeholder_input'   => 'Enter input location…',
                'sel_none'            => '—',
                'sel_ok'              => 'OK',
                'sel_ng'              => 'NG',
                'saved'               => 'Saved successfully',
                'save_fail'           => 'Save failed',
                'confirm_delete'      => 'Delete this row?',
                'delete_sel'          => 'Delete Selected',
                'delete_all'          => 'Delete All',
                'none_selected'       => 'Please select at least one row.',
                'confirm_delete_all'  => 'Delete ALL rows?',
                'num_only'            => 'Numbers only.',
                'no_dup'              => 'Duplicate values are not allowed.',
                'move_up'             => 'Move Up',
                'move_down'           => 'Move Down',
            ],

            'zh-tw' => [
                'no'                  => 'NO',
                'read'                => '讀取位置',
                'input'               => '輸入位置',
                'result'              => '結果',
                'add'                 => '新增列',
                'save'                => '儲存',
                'delete'              => '刪除',
                'placeholder_read'    => '輸入讀取位置…',
                'placeholder_input'   => '輸入輸入位置…',
                'sel_none'            => '—',
                'sel_ok'              => 'OK',
                'sel_ng'              => 'NG',
                'saved'               => '已儲存',
                'save_fail'           => '儲存失敗',
                'confirm_delete'      => '要刪除此列嗎？',
                'delete_sel'          => '刪除已選',
                'delete_all'          => '全部刪除',
                'none_selected'       => '請先勾選至少一列。',
                'confirm_delete_all'  => '要刪除全部列嗎？',
                'num_only'            => '只能輸入數字。',
                'no_dup'              => '不可以輸入重複的數值。',
                'move_up'             => '往上移動',
                'move_down'           => '往下移動',

            ],

            'zh-cn' => [
                'no'                  => 'NO',
                'read'                => '读取位置',
                'input'               => '输入位置',
                'result'              => '结果',
                'add'                 => '新增行',
                'save'                => '保存',
                'delete'              => '删除',
                'placeholder_read'    => '输入读取位置…',
                'placeholder_input'   => '输入输入位置…',
                'sel_none'            => '—',
                'sel_ok'              => 'OK',
                'sel_ng'              => 'NG',
                'saved'               => '已保存',
                'save_fail'           => '保存失败',
                'confirm_delete'      => '要删除此行吗？',
                'delete_sel'          => '删除所选',
                'delete_all'          => '全部删除',
                'none_selected'       => '请先勾选至少一行。',
                'confirm_delete_all'  => '要删除全部行吗？',
                'num_only'            => '只能输入数字。',
                'no_dup'              => '不可以输入重复的数值。',
                'move_up'             => '往上移动',
                'move_down'           => '往下移动',
            ],
        ];

        $L = $L[strtolower($uiLang)] ?? $L['en-us'];
        ?>

        <style>
            /* ——— table minimal styles ——— */
            .table-wrap{margin:12px auto;max-width:1100px}
            #dynTable{width:100%;border-collapse:collapse;background:#fff}
            #dynTable thead th{background:#0e2a47;color:#fff;padding:10px;border:1px solid #274864}
            #dynTable tbody td{padding:8px;border:1px solid #e3e6eb}
            #dynTable td:nth-child(1){text-align:center;width:64px}
            .row-actions button{margin:0 4px}
            .result-ok{font-weight:600}
            .result-ng{font-weight:600}
            .control-bar{display:flex;gap:8px;justify-content:flex-end;margin:8px 0}
            input.table-input{width:100%;padding:6px 8px;box-sizing:border-box}
            select.table-select{width:100%;padding:6px 8px;box-sizing:border-box}
            #dynTable td.sel-ck, #dynTable th.sel-ck { text-align:center; width:46px; }

            input.table-input.invalid { border-color:#e00; background:#fff2f2; }/* 錯誤框提示*/

            /* 拖曳插入指示線 */
            #dynTable tr.insert-marker td{ padding:0; border:none; }
            #dynTable tr.insert-marker .insert-line{ height:2px; background:#60a5fa; width:100%;}


            #dynTable thead th:nth-child(4),
            #dynTable tbody td:nth-child(4) {
            display: none;
            }

            /* 上下移動按鈕的儲存格置中與寬度 */
            #dynTable td.move-cell,
            #dynTable th.move-cell { text-align:center; width:90px; }

            /* 小圖示按鈕外觀一致 */
            .w3-btn.icon-btn { min-width:44px; height:32px; line-height:32px; padding:0 10px; }

        </style>

        <?php
            // $data_button 的格式與 $columns 相同： [index => column_name]
            $btns = $data['data_button'];
        ?>

        <?php if(($_SESSION['privilege'] ?? '') === 'admin' && !empty($btns)) : ?>
            <style>
                .field-bank { 
                margin:10px auto; max-width:1100px; 
                background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; 
                padding:12px 12px 6px; 
                }
                .field-bank h4 { margin:0 0 8px; font-weight:700; color:#0f172a; }
                .field-list { display:flex; flex-wrap:wrap; gap:8px; max-height:160px; overflow:auto; }
                .field-btn {
                border:1px solid #cbd5e1; background:#fff; border-radius:999px; 
                padding:6px 10px; cursor:grab; user-select:none; 
                font-size:12px; line-height:1; display:inline-flex; align-items:center; gap:6px;
                }
                .field-btn:active { cursor:grabbing; }
                .field-btn code { 
                background:#0ea5e9; color:#fff; border-radius:10px; padding:2px 6px; 
                font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                font-size:11px;
                }
                .drop-ready { outline:2px dashed #60a5fa; outline-offset:2px; background:#eff6ff!important; }

                /* 表格內顯示 chip（取代 input） */
                .field-chip {
                    border:1px solid #cbd5e1; background:#fff; border-radius:999px;
                    padding:6px 10px; display:inline-flex; align-items:center; gap:6px;
                    font-size:12px; line-height:1; cursor:default;
                }
                .field-chip code {
                    background:#10b981; color:#fff; border-radius:10px; padding:2px 6px;
                    font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                    font-size:11px;
                }
                .field-chip .chip-del {
                    border:none; background:transparent; font-size:14px; cursor:pointer; line-height:1;
                }

            </style>

            <div class="field-bank">
                <h4><?php echo ($uiLang==='zh-tw'?'欄位快速放置':
                                ($uiLang==='zh-cn'?'字段快速放置':'Field Quick Insert')); ?></h4>
                <div class="field-list" id="fieldList">
                <?php foreach ($btns as $idx => $name): 
                        $idx = is_numeric($idx) ? (int)$idx : $idx; ?>
                    <button class="field-btn" 
                            draggable="true"
                            data-field-index="<?php echo htmlspecialchars($idx); ?>"
                            data-field-name="<?php echo htmlspecialchars($name); ?>"
                            title="<?php echo htmlspecialchars($name); ?>">
                    <code><?php echo htmlspecialchars($idx); ?></code><?php echo htmlspecialchars($name); ?>
                    </button>
                <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>



        <div class="table-wrap">
                <div class="control-bar">
                    <?php if(($_SESSION['privilege'] ?? '') === 'admin'){ ?>
                        <button id="btnAddRow" class="w3-btn w3-round-large"><?php echo $L['add']; ?></button>
                        <button id="btnDeleteSelected" class="w3-btn w3-round-large"><?php echo $L['delete_sel']; ?></button>
                        <button id="btnDeleteAll" class="w3-btn w3-round-large"><?php echo $L['delete_all']; ?></button>
                        <button id="btnSave"   class="w3-btn w3-round-large"><?php echo $L['save']; ?></button>
                    <?php } ?>
                </div>

                <table id="dynTable">
                    <thead>
                        <tr>
                            <th style="width:46px;text-align:center">
                            <?php if(($_SESSION['privilege'] ?? '') === 'admin'){ ?>
                                <input type="checkbox" id="ckAll">
                            <?php } ?>
                            </th>
                            <th><?php echo $L['no']; ?></th>
                            <th><?php echo $L['read']; ?></th>
                            <th><?php echo $L['input']; ?></th>
                            <th><?php echo $L['result']; ?></th>
                            <?php if(($_SESSION['privilege'] ?? '') === 'admin'){ ?>
                                <th></th>
                                <th></th>
                                <th style="width:90px"></th> <!-- 你原本的刪除操作欄 -->
                            <?php } ?>

                        </tr>
                    </thead>

                    <tbody id="dynTbody">
                    <!-- rows injected by JS -->
                    </tbody>
                </table>
        </div>
   

</div>



<?php if($_SESSION['privilege'] != 'admin'){ ?>
<script>
  $(document).ready(function () {
    disableAllButtonsAndInputs();
    document.getElementById("home").disabled = false; 
    document.getElementById("data_select").disabled = false; 
  });
</script>
<?php } ?>

<?php require APPROOT . 'views/inc/footer.php'; ?>

<script>
(function(){

    // 從 PHP 帶入
    const CSV_DATA = <?php echo json_encode($data['data_csv'] ?? null, JSON_UNESCAPED_UNICODE); ?>;
    const FIELD_NAME_BY_INDEX = <?php echo json_encode($btns ?? [], JSON_UNESCAPED_UNICODE); ?> || {};

    let ROW_UID = 1;
    const IS_ADMIN = <?php echo json_encode(($_SESSION['privilege'] ?? '') === 'admin'); ?>;
    const L = <?php echo json_encode($L, JSON_UNESCAPED_UNICODE); ?>;
    const SAVE_URL = '?url=Customize/save_positions';

    const tbody = document.getElementById('dynTbody');
    const btnAdd = document.getElementById('btnAddRow');
    const btnSave = document.getElementById('btnSave');

    const ckAll  = document.getElementById('ckAll');
    const btnDelSel = document.getElementById('btnDeleteSelected');
    const btnDelAll = document.getElementById('btnDeleteAll');
    let   poller = null;

    let insertMarker = null;

    // 任何列有新增 / 刪除 / 重新插入，下一個 frame 自動重編號
    if (tbody && 'MutationObserver' in window) {
        const autoRenumber = new MutationObserver(() => {
            // 用 rAF 確保 DOM 已完成插入/移動後再重編號
            requestAnimationFrame(renumber);
        });
        autoRenumber.observe(tbody, { childList: true });
    }



    // 只抓「資料列」(排除暫時插入線)
    function getDataRows(){
        return [...tbody.querySelectorAll('tr:not(.insert-marker)')];
    }

    // 單列上/下移：dir=-1 上移、dir=+1 下移
    function moveRow(tr, dir, { silent=false } = {}){
        if (!tr || !tbody) return;
        const rows = getDataRows();
        const idx  = rows.indexOf(tr);
        if (idx === -1) return;

        const newIdx = idx + dir;
        if (newIdx < 0 || newIdx >= rows.length) return; // 邊界

        const target = rows[newIdx];
        if (dir < 0) {
            // 上移：插到目標之前
            tbody.insertBefore(tr, target);
        } else {
            // 下移：插到目標之後
            const after = target.nextSibling;
            after ? tbody.insertBefore(tr, after) : tbody.appendChild(tr);
        }

        if (!silent) {
            renumber?.();
            syncCkAllState?.();
            bumpDom?.();
            poller?.triggerNow?.();
        }
    }

    // 批次移動：會維持相對順序
    function moveSelectedRows(dir){

        const rows = getDataRows();
        const selected = rows.filter(r => r.querySelector('.row-ck')?.checked);
        if (!selected.length) {
            if (window.alertify) alertify.alert('Info', L['none_selected'] || 'Please select at least one row.');
            else alert(L['none_selected'] || 'Please select at least one row.');
            return;
        }
        if (dir < 0) {
            // 上移：由上到下跑一次
            selected.forEach(tr => moveRow(tr, -1, { silent:true }));
        } else {
            // 下移：由下到上跑一次
            [...selected].reverse().forEach(tr => moveRow(tr, +1, { silent:true }));
        }
        renumber?.();
        syncCkAllState?.();
        bumpDom?.();
        poller?.triggerNow?.();
    }


    function headerColspan(){
        return document.querySelectorAll('#dynTable thead th').length || 5;
    }

    function ensureInsertMarker(){

        if (insertMarker) return insertMarker;
        insertMarker = document.createElement('tr');
        insertMarker.className = 'insert-marker';
        const td = document.createElement('td');
        td.colSpan = headerColspan();
        td.innerHTML = '<div class="insert-line"></div>';
        insertMarker.appendChild(td);
        return insertMarker;
    }

    function placeMarkerBefore(tr){

        const m = ensureInsertMarker();
        if (tr) tbody.insertBefore(m, tr); else tbody.appendChild(m);
    }

    function clearInsertMarker(){

        if (insertMarker?.parentNode) insertMarker.parentNode.removeChild(insertMarker);
        insertMarker = null;
    }

    // 用滑鼠 Y 找到應該插在誰「前面」
    function computeBeforeTrByY(clientY){

        const rows = getDataRows();
        for (const r of rows){
            const rect = r.getBoundingClientRect();
            const mid  = rect.top + rect.height / 2;
            if (clientY < mid) return r; // 在這列上半部 → 插在此列前
        }
        return null; // 否則插在最後（append）
    }


    // ---- 防舊回應覆蓋：DOM 版本號 ----
    let DOM_VERSION = 0;
    const bumpDom = () => { DOM_VERSION++; };

    const MAX_ROWS = 100;
    const MSG_MAX_ROWS = (L && (L.max_rows || L['max_rows'])) || 'Maximum 100 rows allowed.';

    // 目前列數
    function getRowCount(){

        return tbody ? tbody.querySelectorAll('tr:not(.insert-marker)').length : 0;
    }

    // 依列數啟用/停用「新增列」按鈕
    function updateAddButtonState(){

        if (btnAdd) btnAdd.disabled = getRowCount() >= MAX_ROWS;
    }

    // ===== 欄位銀行（來源） =====
    const fieldList = document.getElementById('fieldList');
    let dragPayload = null;

    // 幫每顆欄位銀行按鈕給一個 id（便於回復）
    if (fieldList) {

        [...fieldList.querySelectorAll('.field-btn')].forEach(btn => {
            const idx = btn.getAttribute('data-field-index');
            if (!btn.id) btn.id = 'fb-' + String(idx); // 以索引為主鍵，假設唯一
        });
    }

    // 拖曳開始
    if (fieldList) {
        fieldList.addEventListener('dragstart', (e) => {
        const btn = e.target.closest('.field-btn');
        if (!btn) return;
        const idx = btn.getAttribute('data-field-index') || '';
        const name = btn.getAttribute('data-field-name') || '';
        dragPayload = { idx, name, originId: btn.id };
        try {
            e.dataTransfer.setData('text/plain', String(idx));
            e.dataTransfer.effectAllowed = 'copyMove';
        } catch {}
        });

        // 點一下按鈕：放到聚焦或第一個空白讀取位置
        fieldList.addEventListener('click', (e) => {
        const btn = e.target.closest('.field-btn');
        if (!btn) return;
        const idx = btn.getAttribute('data-field-index') || '';
        const name = btn.getAttribute('data-field-name') || '';
        const originId = btn.id;

        // 找到目標 td
        let targetTd = document.activeElement?.closest?.('td.drop-target[data-drop="read"]') || null;
        if (!targetTd) {
            targetTd = [...tbody.querySelectorAll('td.drop-target[data-drop="read"]')]
            .find(td => {
                const chip = td.querySelector('.field-chip');
                if (chip) return false;
                const inp = td.querySelector('input.table-input');
                return !inp || !inp.value.trim();
            }) || null;
        }
        if (!targetTd) return;

        assignFieldToCell({ idx, name, originId }, targetTd);
        });
    }

    // 表格拖放
    tbody.addEventListener('dragover', (e) => {
        const td = e.target.closest('td.drop-target[data-drop="read"]');
        if (!td) return;
        e.preventDefault();
        td.classList.add('drop-ready');
        try { e.dataTransfer.dropEffect = 'copyMove'; } catch {}
    });

    tbody.addEventListener('dragover', (e) => {

        // 僅在拖的是「欄位按鈕」時處理
        const isFieldDrag = !!dragPayload || (()=>{ 
            try{ const t = e.dataTransfer.getData('text/plain'); return !!t; }catch{ return false; } 
        })();
        if (!isFieldDrag) return;

        e.preventDefault(); // 允許在 tbody 任意處 drop
        const beforeTr = computeBeforeTrByY(e.clientY);
        placeMarkerBefore(beforeTr);
        });
        tbody.addEventListener('dragleave', (e) => {
        // 滑出整個 tbody 才清除
        if (!tbody.contains(e.relatedTarget)) clearInsertMarker();
    });


    tbody.addEventListener('dragleave', (e) => {
        const td = e.target.closest('td.drop-target[data-drop="read"]');
        if (!td) return;
        td.classList.remove('drop-ready');
    });


    tbody.addEventListener('drop', (e) => {
        e.preventDefault();
        const td = e.target.closest('td.drop-target[data-drop="read"]');

        // 取出拖曳 payload（和你原本相同）
        let idx = '', originId = '', name = '';
        try { idx = e.dataTransfer.getData('text/plain'); } catch {}
        if (!idx && dragPayload) idx = dragPayload.idx;
        if (dragPayload) {
            originId = dragPayload.originId || '';
            name     = dragPayload.name || '';
        } else if (idx) {
            const btn = document.getElementById('fb-' + String(idx));
            name     = btn?.getAttribute('data-field-name') || '';
            originId = btn?.id || ('fb-' + String(idx));
        }

        // 1) 若是直接丟在讀取欄 → 沿用原本邏輯
        if (td) {
            td.classList.remove('drop-ready');
            if (idx) assignFieldToCell({ idx, name, originId }, td);
            clearInsertMarker();
            return;
        }
        // 2) 沒有丟在儲存格，但有「插入線」→ 插在兩列中間（若在最底則變成 NOx+1）
        if (insertMarker && idx) {
            const rows = getDataRows();                       // 不含插入線
            const beforeTr = insertMarker.nextElementSibling; // 插入線後面那列（null 代表最底）
            const pos = beforeTr ? (rows.indexOf(beforeTr) + 1) : (rows.length + 1); // 1-based

            const newTr = insertRowAt(pos, '', '', '');
            if (newTr) {
                const newReadTd = newTr.querySelector('td:nth-child(3)');
                assignFieldToCell({ idx, name, originId }, newReadTd);
                updateAddButtonState?.();   // 讓「新增列」按鈕狀態即時更新
                bumpDom?.();
                poller?.triggerNow?.();
            }
        }
        clearInsertMarker();


    });



    // ===== 把欄位指派到某個 td（變成 chip） =====
    function assignFieldToCell(payload, td) {

        const { idx, name, originId: givenOriginId } = payload;
        if (!td) return;

        const originId = givenOriginId || ('fb-' + String(idx)); // 欄位銀行按鈕的 id 慣例

        // 1) 若此欄位已被放在別處，先移除舊 chip（保持唯一）
        const prevChip = tbody.querySelector(`.field-chip[data-origin-id="${cssEscape(originId)}"]`);
        if (prevChip && prevChip.closest('td') !== td) {
            unassignFromCell(prevChip.closest('td'), { restoreButton: false });
        }

        // 2) 目標格若已有 chip，先恢復它原本的銀行按鈕
        const existingChip = td.querySelector('.field-chip');
        if (existingChip) {
            restoreFieldBankButton(existingChip.dataset.originId);
        }

        // 3) 隱藏欄位銀行按鈕
        const bankBtn = document.getElementById(originId) || findBankButtonByIndex(idx);
        if (bankBtn) {
            bankBtn.style.display = 'none';
            bankBtn.dataset.assignedRowId = td.closest('tr')?.dataset?.rowId || '';
        }

        // 4) 以 chip 取代表格中的 input
        td.innerHTML = '';
        const chip = document.createElement('div');
        chip.className = 'field-chip';
        chip.dataset.source = 'db';                 // 來源：DB
        chip.dataset.fieldIndex = String(idx);
        chip.dataset.originId = originId;
        chip.innerHTML = `
            <code>${escapeHtml(String(idx))}</code>${escapeHtml(name || '')}
            <button type="button" class="chip-del" aria-label="Remove">×</button>
        `;
        td.appendChild(chip);

        // 5) 放入隱藏 input（沿用你的保存/驗證流程）
        ensureHiddenInputs(td, {
            read_db_index: String(idx),
            read_db_name: String(name || ''),
            read_src: 'db'
        });

        // 6) 移除 chip（還原 input + 恢復按鈕）
        chip.querySelector('.chip-del')?.addEventListener('click', () => {
            unassignFromCell(td, { restoreButton: true });
            validateUniqueColumns();
        });

        // 7) 重新檢查唯一性 + 標記 DOM 變動並可立即輪詢
        validateUniqueColumns();
        bumpDom();
        poller?.triggerNow();
    }

    /* ---------- 輔助函式 ---------- */

    // 在 td 內建立/更新隱藏 inputs
    function ensureHiddenInputs(td, data) {
        setHidden(td, 'read_db_index', data.read_db_index);
        setHidden(td, 'read_db_name',  data.read_db_name);
        setHidden(td, 'read_src',      data.read_src);
    }

    function setHidden(td, name, value) {

        let input = td.querySelector(`input[type="hidden"][name="${cssEscape(name)}"]`);
        if (!input) {
        input = document.createElement('input');
        input.type = 'hidden';
        input.className = 'table-input'; // 保持和你既有 selector 相容
        input.name = name;
        td.appendChild(input);
        }
        input.value = value;
    }

    function hydrateFromCsv(csv){
        if (!csv) return;

        // 算出要建立的列數
        const len = Math.max(
        Array.isArray(csv.no) ? csv.no.length : 0,
        Array.isArray(csv.read_position) ? csv.read_position.length : 0,
        Array.isArray(csv.input_position) ? csv.input_position.length : 0,
        Array.isArray(csv.result) ? csv.result.length : 0
        );

        for (let i = 0; i < len && i < 100; i++) {
        const r = addRow('', '', '');
        if (!r) break;

        const tdRead  = r.querySelector('td:nth-child(3)');
        const tdInput = r.querySelector('td:nth-child(4) input.table-input');
        const tdRes   = r.querySelector('.result-input');

        const rpRaw = (csv.read_position && csv.read_position[i] != null) ? String(csv.read_position[i]).trim() : '';
        const ipRaw = (csv.input_position && csv.input_position[i] != null) ? String(csv.input_position[i]).trim() : '';
        const rsRaw = (csv.result && csv.result[i] != null) ? String(csv.result[i]).trim() : '';

        // 若是 "#<idx> <name>" 或 "#<idx>"
        const m = /^#\s*(\d+)(?:\s+(.+))?$/i.exec(rpRaw);
        if (m) {
            const idx  = m[1];
            const name = (m[2] && m[2].trim()) || (FIELD_NAME_BY_INDEX[idx] || '');
            assignFieldToCell({ idx, name, originId: 'fb-' + String(idx) }, tdRead);
        } else {
            // 否則就是一般輸入框文字
            const inRead = tdRead.querySelector('input.table-input[type="text"]');
            if (inRead) inRead.value = rpRaw;
        }

        if (tdInput) tdInput.value = ipRaw;
        if (tdRes)   tdRes.value   = rsRaw;
        }

        // 載入後做一次唯一性檢查與新增按鈕狀態更新
        if (typeof validateUniqueColumns === 'function') validateUniqueColumns();
        if (typeof btnAdd !== 'undefined' && btnAdd) {
        const count = tbody ? tbody.querySelectorAll('tr').length : 0;
        btnAdd.disabled = count >= 100;
        }
    }

    // 依索引找按鈕（銀行按鈕需有 data-field-index）
    function findBankButtonByIndex(idx) {
        return fieldList?.querySelector(`.field-btn[data-field-index="${cssEscape(String(idx))}"]`) || null;
    }

    function findBankButtonByOrigin(originId) {
        return fieldList?.querySelector(`.field-btn#${cssEscape(originId)}`) || null;
    }

    // 復原欄位銀行按鈕（顯示出來）
    function restoreFieldBankButton(originId) {
        const btn = document.getElementById(originId) || findBankButtonByOrigin(originId);
        if (btn) {
        btn.style.display = '';
        delete btn.dataset.assignedRowId;
        }
    }

    // 工具：簡易 escape（避免 name 有特殊字元）
    function escapeHtml(s) {

        return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    }
    function cssEscape(s) {

        return String(s).replace(/([ !"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1');
    }

    // 從某個 td 移除 chip，並（可選）復原欄位銀行按鈕；同時把格子改回 input
    function unassignFromCell(td, { restoreButton = true } = {}) {
        if (!td) return;

        const chip = td.querySelector('.field-chip');
        const originId = chip?.dataset?.originId || '';
        if (chip) chip.remove();

        // 清這格，改回可輸入的 input
        td.innerHTML = '';
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'table-input';
        input.placeholder = L['placeholder_read'];
        td.appendChild(input);

        if (restoreButton && originId) {
            restoreFieldBankButton(originId);
        }

        bumpDom();
        poller?.triggerNow();
    }

    // ——— 數字欄位過濾與驗證 ———
    const NUM_ONLY_SELECTOR = '.table-input[data-num-only]';

    function sanitizeDigits(s){ return (s || '').replace(/[^\d]/g, ''); }
    function markInvalid(el, bad){
        if (!el) return;
        if (bad) el.classList.add('invalid'); else el.classList.remove('invalid');
    }

    // 針對帶 data-num-only 的欄位：input 事件即時過濾非數字
    document.addEventListener('input', (e)=>{
        if (e.target && e.target.matches(NUM_ONLY_SELECTOR)) {
        const v = e.target.value;
        const digits = sanitizeDigits(v);
        if (v !== digits) e.target.value = digits;
        }
    });

    // 貼上時也過濾
    document.addEventListener('paste', (e)=>{
        if (e.target && e.target.matches(NUM_ONLY_SELECTOR)) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        const digits = sanitizeDigits(text);
        document.execCommand('insertText', false, digits);
        }
    });

    // 儲存前檢核（只要有填就必須是數字）
    function validateNumericRows(){

        let bad = 0;
        [...tbody.querySelectorAll('tr')].forEach(tr=>{
            const readEl  = tr.querySelector('td:nth-child(3) input');
            const inputEl = tr.querySelector('td:nth-child(4) input');
            const readVal  = readEl?.value?.trim()  ?? '';
            const inputVal = inputEl?.value?.trim() ?? '';

            const readOk  = (readVal  === '' || /^\d+$/.test(readVal));
            const inputOk = (inputVal === '' || /^\d+$/.test(inputVal));

            markInvalid(readEl,  !readOk);
            markInvalid(inputEl, !inputOk);

            if (!readOk || !inputOk) bad++;
        });
        return bad === 0;
    }

    // ====== 不能重複：同欄位內（讀取欄彼此唯一、輸入欄彼此唯一） ======
    function getColInputs(colIndex){
        // 只看可見的 text 輸入框，忽略 chip 與 hidden
        return [...tbody.querySelectorAll(`tr td:nth-child(${colIndex}) input.table-input[type="text"]`)];
    }

    function validateUniqueForCol(colIndex){

        const inputs = getColInputs(colIndex);
        const seen = new Map();
        let ok = true;

        // 先清掉舊的 invalid 樣式
        inputs.forEach(el => el && el.classList.remove('invalid'));

        inputs.forEach(el=>{
            const v = (el?.value ?? '').trim();
            if (!v) return;               // 空值跳過
            if (seen.has(v)) {
            ok = false;
            el.classList.add('invalid');
            const first = seen.get(v);
            if (first) first.classList.add('invalid');
            } else {
            seen.set(v, el);
            }
        });
        return ok;
    }


    function validateUniqueColumns(){

        const okRead  = validateUniqueForCol(3);
        const okInput = validateUniqueForCol(4);
        return okRead && okInput;
    }


    document.addEventListener('input', (e)=>{
        if (e.target && e.target.matches('td:nth-child(3) input.table-input, td:nth-child(4) input.table-input')) {
        validateUniqueColumns();
        }
    });

    // ——— helpers ———
    function renumber(){
        if (!tbody) return;
        // 只針對真正的資料列；忽略任何插入用的標記列
        const rows = [...tbody.children].filter(tr => !tr.classList.contains('insert-marker'));

        rows.forEach((tr, idx) => {
            const noCell = tr.querySelector('.cell-no');
            if (noCell) noCell.textContent = String(idx + 1);
        });
        updateRowMoveButtonsState(); 
    }


    // 只抓真正的資料列（你已有 getDataRows() 可直接沿用）
    function moveRow(tr, dir){
        const rows = getDataRows();
        const idx  = rows.indexOf(tr);
        if (idx === -1) return;

        const to = idx + dir;
        if (to < 0 || to >= rows.length) return; // 邊界保護

        const target = rows[to];
        // dir>0 往下：插到 target 後面；dir<0 往上：插到 target 前面
        tbody.insertBefore(tr, dir > 0 ? target.nextSibling : target);

        renumber();                // 你原有的重編號
        syncCkAllState?.();        // 全選方塊狀態
        updateRowMoveButtonsState();
        if (typeof bumpDom === 'function') bumpDom();
        if (poller?.triggerNow) poller.triggerNow();
        }

        // 讓首列的「上移」與末列的「下移」自動禁用
        function updateRowMoveButtonsState(){
        const rows = getDataRows();
        rows.forEach((tr, i) => {
            const up   = tr.querySelector('.btn-row-up');
            const down = tr.querySelector('.btn-row-down');
            if (up)   up.disabled   = (i === 0);
            if (down) down.disabled = (i === rows.length - 1);
        });
    }


    // ——— admin controls ———
    if (IS_ADMIN) {
        if (btnAdd)  btnAdd.addEventListener('click', ()=> addRow());
        if (btnSave) btnSave.addEventListener('click', onSave);

        const controlBar = document.querySelector('.control-bar');
        if (controlBar && !document.getElementById('btnMoveUp')) {
            const btnUp = document.createElement('button');
            btnUp.id = 'btnMoveUp';
            btnUp.className = 'w3-btn w3-round-large icon-btn';
            btnUp.title = (L['move_up'] || 'Move Up');
            btnUp.textContent = '▲';
            btnUp.addEventListener('click', () => moveSelectedRows(-1));

            const btnDown = document.createElement('button');
            btnDown.id = 'btnMoveDown';
            btnDown.className = 'w3-btn w3-round-large icon-btn';
            btnDown.title = (L['move_down'] || 'Move Down');
            btnDown.textContent = '▼';
            btnDown.addEventListener('click', () => moveSelectedRows(+1));

            controlBar.insertBefore(btnUp, controlBar.firstChild);
            controlBar.insertBefore(btnDown, controlBar.firstChild.nextSibling);
        }
    }

    // 批次：把勾選的列整體上/下移一格（順序保持）
    function moveSelectedRows(dir){
        const rows = getDataRows();
        const sel  = rows.filter(r => r.querySelector('.row-ck')?.checked);
        if (!sel.length) return;
        // 往上：由上到下移；往下：由下到上移，避免互相干擾
        const list = (dir < 0) ? sel : sel.slice().reverse();
        list.forEach(tr => moveRow(tr, dir));
    }





    function makeResultSelect(val=''){

        const sel = document.createElement('select');
        sel.className = 'table-select result-select';
        [['',L['sel_none']], ['OK',L['sel_ok']], ['NG',L['sel_ng']]].forEach(([v,lab])=>{
            const opt = document.createElement('option');
            opt.value = v; opt.textContent = lab;
            if(v===val) opt.selected = true;
            sel.appendChild(opt);
        });
        sel.addEventListener('change', function(){
            const td = this.closest('td');
            td.classList.remove('result-ok','result-ng');
            if(this.value==='OK') td.classList.add('result-ok');
            if(this.value==='NG') td.classList.add('result-ng');
        });
        return sel;
    }

    /**
     * 在第 pos 個位置插入一列（pos 從 1 開始；pos=2 表示插在 NO1 與 NO2 中間）
     */
    function insertRowAt(pos, readVal = '', inputVal = '', resultVal = '') {
        // 上限保護
        if (getRowCount() >= MAX_ROWS) {
            const msg = (L && (L.max_rows || L['max_rows'])) || 'Maximum 100 rows allowed.';
            if (window.alertify) alertify.alert('Info', msg); else alert(msg);
            if (btnAdd) btnAdd.disabled = true;
            return null;
        }

        // 先用既有的 addRow 建一列（會先 append 到最後）
        const tr = addRow(readVal, inputVal, resultVal);
        if (!tr) return null;

        // 目前的「資料列」（排除插入標記列）
        const dataRows = tbody.querySelectorAll('tr:not(.insert-marker)');
        const rowCount = dataRows.length;

        // 允許 pos = 1..rowCount（插在中間），或 pos = rowCount（剛 append 後又移）或 pos = rowCount+1（插在最後）
        // 這裡先把 pos 夾到合法範圍 1..(rowCount)
        // 注意：這時 dataRows 已包含新加的 tr（在最後），所以「插到最後」的情況 beforeNode 會是 null
        pos = Math.max(1, Math.min(pos, rowCount + 1));

        // 找到要插在誰「前面」
        // - 若 pos 在最後一列的「後面」，beforeNode 會是 null → appendChild
        const beforeNode = dataRows[pos - 1] || null;

        if (beforeNode) {
            tbody.insertBefore(tr, beforeNode);
        } else {
            // 插到最末尾（已在最後也無妨，再 append 一次沒副作用）
            tbody.appendChild(tr);
        }

        // 重新編號與 UI 同步
        renumber();
        syncCkAllState();

        // 新增鈕狀態
        if (btnAdd) btnAdd.disabled = getRowCount() >= MAX_ROWS;

        // 若你有 bumpDom()、poller
        if (typeof bumpDom === 'function') bumpDom();
        if (typeof poller !== 'undefined' && poller && typeof poller.triggerNow === 'function') {
            poller.triggerNow();
        }

        return tr;
    }


    // 依目前表格 DOM 動態組輪詢 payload（no / read_position / input_position / row_id）
    function buildPollPayloadFromDOM() {
        const no = [], read_position = [], input_position = [], row_id = [];

        [...tbody.querySelectorAll('tr')].forEach((tr, i) => {
            no.push(i + 1);
            row_id.push(tr.dataset.rowId || '');

            // 第3欄：讀取位置（可能是 chip 也可能是輸入框）
            const tdRead = tr.querySelector('td:nth-child(3)');
            const chip   = tdRead?.querySelector('.field-chip');
            if (chip) {
            const idx  = tdRead.querySelector('input[name="read_db_index"]')?.value
                        || chip.dataset.fieldIndex || '';
            const name = tdRead.querySelector('input[name="read_db_name"]')?.value
                        || (FIELD_NAME_BY_INDEX[idx] || '');
            read_position.push('#' + idx + (name ? ' ' + name : ''));
            } else {
            const val = tdRead?.querySelector('input.table-input[type="text"]')?.value?.trim() || '';
            read_position.push(val);
            }

            // 第4欄：輸入位置
            const ip = tr.querySelector('td:nth-child(4) input.table-input')?.value?.trim() || '';
            input_position.push(ip);
        });

        return { no, read_position, input_position, row_id };
    }

    /**
     * 週期性向後端取結果並更新表格（支援 opts.data 是 Function 或 Object）
     * 同時使用 DOM_VERSION 避免舊回應覆蓋新 DOM
     */
    function startResultPolling(opts = {}) {

        const url        = opts.url ?? '?url=Customize/get_api';
        const intervalMs = opts.intervalMs ?? 1000;
        const tbodyEl    = opts.tbodyEl ?? tbody;

        const getData = (typeof opts.data === 'function')
            ? opts.data
            : () => (opts.data ?? {
                no: (CSV_DATA && CSV_DATA.no) || [],
                read_position: (CSV_DATA && CSV_DATA.read_position) || [],
                input_position: (CSV_DATA && CSV_DATA.input_position) || []
            });

        // ✅ 優先以 row_id 對到列，沒有才用 no
        const mapRowToElement = opts.mapRowToElement ?? ((row, tb) => {
            if (row.row_id) {
            return tb?.querySelector(`tr[data-row-id="${row.row_id}"]`) || null;
            }
            const idx = (row.no || 0) - 1;
            return tb?.querySelectorAll('tr')?.[idx] || null;
        });

        const onUpdate = opts.onUpdate ?? ((row, tr) => {
            const resInput = tr?.querySelector('.result-input');
            if (resInput) resInput.value = row.result ?? '';
        });

        let timer = null, inFlight = false, aborted = false, controller = null;

        const tick = () => {
            if (aborted || inFlight) return;
            inFlight = true;
            controller = new AbortController();

            const reqVersion = DOM_VERSION; // ✅ 記下請求發出時的 DOM 版本

            fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(getData()),
            signal: controller.signal
            })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(resp => {
            // ✅ 若期間 DOM 變動，丟棄這份回應，避免覆蓋插入/刪除後的資料
            if (reqVersion !== DOM_VERSION) return;
            if (!resp || !Array.isArray(resp.rows)) return;
            resp.rows.forEach(row => {
                const tr = mapRowToElement(row, tbodyEl);
                if (tr) onUpdate(row, tr);
            });
            })
            .catch(() => {})
            .finally(() => { inFlight = false; });
        };

        timer = setInterval(tick, intervalMs);
        return {
            stop(){ aborted = true; if (timer) clearInterval(timer); if (controller) controller.abort(); },
            triggerNow(){ tick(); }
        };
    }

    function addRow(readVal = '', inputVal = '', resultVal = '') {

        if (getRowCount() >= MAX_ROWS) {
            const msg = (L && (L.max_rows || L['max_rows'])) || 'Maximum 100 rows allowed.';
            if (window.alertify) alertify.alert('Info', msg); else alert(msg);
            if (btnAdd) btnAdd.disabled = true;
            return null;
        }

        const tr = document.createElement('tr');

        const rowId = 'r' + (ROW_UID++);
        tr.dataset.rowId = rowId;

        // (1) 選取（admin）
        const tdSel = document.createElement('td');
        tdSel.className = 'sel-ck';
        if (IS_ADMIN) {
            const ck = document.createElement('input');
            ck.type = 'checkbox';
            ck.className = 'row-ck';
            tdSel.appendChild(ck);
        }
        tr.appendChild(tdSel);

        // (2) NO
        const tdNo = document.createElement('td');
        tdNo.className = 'cell-no';
        tr.appendChild(tdNo);

        // (3) 讀取位置（可投放）
        const tdRead = document.createElement('td');
        tdRead.classList.add('drop-target');
        tdRead.dataset.drop = 'read';

        const inRead = document.createElement('input');
        inRead.type = 'text';
        inRead.className = 'table-input';
        inRead.placeholder = L['placeholder_read'];
        inRead.value = readVal;
        tdRead.appendChild(inRead);
        tr.appendChild(tdRead);

        // (4) 輸入位置
        const tdInput = document.createElement('td');
        const inInput = document.createElement('input');
        inInput.type = 'text';
        inInput.className = 'table-input';
        inInput.placeholder = L['placeholder_input'];
        inInput.value = inputVal;
        tdInput.appendChild(inInput);
        tr.appendChild(tdInput);

        // (5) 結果（唯讀）
        const tdRes = document.createElement('td');
        const inRes = document.createElement('input');
        inRes.type = 'text';
        inRes.className = 'table-input result-input';
        inRes.value = resultVal || '';
        inRes.readOnly = true;
        inRes.id = 'result-' + rowId;
        tdRes.appendChild(inRes);
        tr.appendChild(tdRes);


        // (6) 往上 / 往下（admin）
        if (IS_ADMIN) {
            // 往上
            const tdUp = document.createElement('td');
            tdUp.className = 'move-cell';
            const bUp = document.createElement('button');
            bUp.type = 'button';
            bUp.className = 'w3-btn w3-round-large icon-btn btn-row-up';
            bUp.title = (L['move_up'] || 'Move Up');
            bUp.textContent = '▲';
            bUp.addEventListener('click', () => moveRow(tr, -1));
            tdUp.appendChild(bUp);
            tr.appendChild(tdUp);

            // 往下
            const tdDown = document.createElement('td');
            tdDown.className = 'move-cell';
            const bDown = document.createElement('button');
            bDown.type = 'button';
            bDown.className = 'w3-btn w3-round-large icon-btn btn-row-down';
            bDown.title = (L['move_down'] || 'Move Down');
            bDown.textContent = '▼';
            bDown.addEventListener('click', () => moveRow(tr, +1));
            tdDown.appendChild(bDown);
            tr.appendChild(tdDown);
        }

        // (8) 操作（admin）— 單筆刪除
        if (IS_ADMIN) {
            const tdAct = document.createElement('td');
            tdAct.className = 'row-actions';

            const del = document.createElement('button');
            del.textContent = L['delete'];                // i18n：刪除 / Delete
            del.className = 'w3-btn w3-round-large';
            del.addEventListener('click', () => {
                // 刪除前：若此列有 chip，把欄位庫按鈕恢復
                tr.querySelectorAll('.field-chip').forEach(chip => {
                const originId = chip.dataset.originId;
                if (originId) restoreFieldBankButton(originId);
                });

                tr.remove();
                renumber();                 // 重新編號
                syncCkAllState?.();         // 全選方塊狀態
                if (btnAdd && getRowCount() < MAX_ROWS) btnAdd.disabled = false;

                bumpDom?.();
                poller?.triggerNow?.();
            });

            tdAct.appendChild(del);
            tr.appendChild(tdAct);
        }




        // 插入表身
        tbody.appendChild(tr);
        renumber();
        updateRowMoveButtonsState(); 

        // 新增後若已達上限，停用新增按鈕；否則保持可用
        if (btnAdd) btnAdd.disabled = getRowCount() >= MAX_ROWS;

        bumpDom();
        poller?.triggerNow();

        return tr;
    }

    // ——— initial rows from CSV ———
    if (CSV_DATA && (
        (CSV_DATA.no && CSV_DATA.no.length) ||
        (CSV_DATA.read_position && CSV_DATA.read_position.length) ||
        (CSV_DATA.input_position && CSV_DATA.input_position.length) ||
        (CSV_DATA.result && CSV_DATA.result.length)
    )) {
        hydrateFromCsv(CSV_DATA);
    } else {
        addRow();
    }

    /* === 啟動輪詢（就放在這裡）=== */
    poller = startResultPolling({
        intervalMs: 1000,
        tbodyEl: tbody,
        data: () => {
        const p = buildPollPayloadFromDOM();
        if (!p.no.length && CSV_DATA)  {
            return {
            no: (CSV_DATA.no || []),
            read_position: (CSV_DATA.read_position || []),
            input_position: (CSV_DATA.input_position || []),
            row_id: (CSV_DATA.row_id || [])
            };
        }
        return p;
        }
    });

    poller.triggerNow();
    window.addEventListener('beforeunload', () => poller.stop());

    // ——— admin controls ———
    if(IS_ADMIN){
        if(btnAdd) btnAdd.addEventListener('click', ()=> addRow());
        if(btnSave) btnSave.addEventListener('click', onSave);
    }else{
        [...tbody.querySelectorAll('input,select,button')].forEach(el=> el.disabled = true);
    }

    function collectData(){
        const rows = [];
        const trs = [...tbody.querySelectorAll('tr')];

        trs.forEach((tr, i) => {
            const rowId   = tr.dataset.rowId || ('r' + (i + 1));

            // 第3欄：讀取位置
            const tdRead  = tr.querySelector('td:nth-child(3)');
            const chip    = tdRead?.querySelector('.field-chip');
            let readSrc   = '';
            let readIdx   = '';
            let readName  = '';
            let readPos   = '';  // 只有「非 chip」情況會填值

            if (chip) {
            readSrc  = tdRead?.querySelector('input[name="read_src"]')?.value?.trim() || 'db';
            readIdx  = tdRead?.querySelector('input[name="read_db_index"]')?.value?.trim()
                    || chip.dataset.fieldIndex || '';
            readName = tdRead?.querySelector('input[name="read_db_name"]')?.value?.trim() || '';
            } else {
            readPos = tdRead?.querySelector('input.table-input[type="text"]')?.value?.trim() ?? '';
            }

            // 第4欄：輸入位置
            const tdInput = tr.querySelector('td:nth-child(4)');
            const inputPos = tdInput?.querySelector('input.table-input[type="text"]')?.value?.trim() ?? '';

            // 結果欄
            const result = tr.querySelector('.result-input')?.value ?? '';

            if (readPos || inputPos || result || readSrc || readIdx || readName) {
            rows.push({
                row_id: rowId,
                read_pos: readPos,
                input_pos: inputPos,
                result: result,
                read_src: readSrc,
                read_db_index: readIdx,
                read_db_name: readName
            });
            }
        });

        return rows;
    }

    function syncCkAllState(){

        if (!IS_ADMIN || !ckAll) return;
        const rowCks = tbody.querySelectorAll('.row-ck');
        if (rowCks.length === 0) {
            ckAll.checked = false;
            ckAll.indeterminate = false;
            return;
        }
        const checked = [...rowCks].filter(ck => ck.checked).length;
        if (checked === rowCks.length) {
            ckAll.checked = true;
            ckAll.indeterminate = false;
        } else {
            ckAll.checked = false;
            ckAll.indeterminate = false;
        }
    }

    if (IS_ADMIN && ckAll) {

        ckAll.addEventListener('change', ()=>{
            const rowCks = tbody.querySelectorAll('.row-ck');
            rowCks.forEach(ck => ck.checked = ckAll.checked);
            ckAll.indeterminate = false;
        });
        
        tbody.addEventListener('change', (e)=>{
            if (e.target && e.target.classList.contains('row-ck')) {
            syncCkAllState();
            }
        });
    }

    function deleteSelectedRows(){

        const rows = [...tbody.querySelectorAll('.row-ck:checked')].map(ck => ck.closest('tr')).filter(Boolean);

        if (rows.length === 0) {
            if (window.alertify) { alertify.alert('Info', L['none_selected']); }
            else { alert(L['none_selected']); }
            return;
        }

        const doRemove = () => {
            rows.forEach(tr => {
            tr.querySelectorAll('.field-chip').forEach(chip => {
                const originId = chip.dataset.originId;
                if (originId) restoreFieldBankButton(originId);
            });
            tr.remove();
            });

            renumber();
            syncCkAllState();

            if (typeof ckAll !== 'undefined' && ckAll) {
            ckAll.checked = false;
            ckAll.indeterminate = false;
            }

            if (typeof btnAdd !== 'undefined' && btnAdd) {
            btnAdd.disabled = getRowCount() >= MAX_ROWS;
            }

            bumpDom();
            poller?.triggerNow();
        };

        if (window.alertify) {
            alertify.confirm(L['delete_sel'], L['confirm_delete'], doRemove, function(){});
        } else {
            if (confirm(L['confirm_delete'])) doRemove();
        }
    }

    function deleteAllRows(){

        const doRemoveAll = () => {
            tbody.querySelectorAll('.field-chip').forEach(chip => {
            const originId = chip.dataset.originId;
            if (originId) restoreFieldBankButton(originId);
            });

            tbody.innerHTML = '';

            renumber();
            syncCkAllState();

            if (typeof ckAll !== 'undefined' && ckAll) {
            ckAll.checked = false;
            ckAll.indeterminate = false;
            }

            if (typeof btnAdd !== 'undefined' && btnAdd) {
            btnAdd.disabled = false;
            }

            bumpDom();
            poller?.triggerNow();
        };

        if (window.alertify){
            alertify.confirm(L['delete_all'], L['confirm_delete_all'], doRemoveAll, function(){});
        } else {
            if (confirm(L['confirm_delete_all'])) doRemoveAll();
        }
    }

    if(IS_ADMIN){
        if(btnDelSel) btnDelSel.addEventListener('click', deleteSelectedRows);
        if(btnDelAll) btnDelAll.addEventListener('click', deleteAllRows);
    }



    // 判斷某列是否「有資料」：讀取欄有 chip 或文字、或輸入欄有文字
    function rowHasData(tr){
        const tdRead  = tr.querySelector('td:nth-child(3)');
        const tdInput = tr.querySelector('td:nth-child(4)');
        const hasChip = !!tdRead?.querySelector('.field-chip');
        const readVal = tdRead?.querySelector('input.table-input[type="text"]')?.value?.trim() || '';
        const inputVal= tdInput?.querySelector('input.table-input[type="text"]')?.value?.trim() || '';
        return hasChip || !!readVal || !!inputVal;
    }

    // 取出「有資料」的列索引（0-based）
    function getFilledRowIndexes(){
        const trs = [...tbody.querySelectorAll('tr')];
        const idxs = [];
        for (let i = 0; i < trs.length; i++){
            if (rowHasData(trs[i])) idxs.push(i);
        }
        return idxs;
    }

    // 計算：要插在「第 n 與第 n+1 筆有資料」之間（n 為 1-based）
    function getPosBetweenFilledPair(n){
        const idxs = getFilledRowIndexes();  // e.g. [0,2,3,7]
        const trsLen = tbody.querySelectorAll('tr').length;

        // 想插入「第 n 與第 n+1 筆 filled」之間 → 需要至少 n+1 筆
        if (n >= 1 && idxs.length >= n + 1){
            const secondIdx = idxs[n]; // 第 n+1 筆（0-based）
            return secondIdx + 1;      // insertRowAt 的 1-based 位置
        }

        // 不足 n+1 筆：退而求其次 → 插在最後一筆 filled 的後面；若完全沒資料就插到最後
        if (idxs.length > 0){
            return Math.min(idxs[idxs.length - 1] + 2, trsLen + 1);
        }
        return Math.min(2, trsLen + 1); // 全空表時，預設第2列（可改 1）
    }

    // 介面 1：依「第 n 與 n+1 筆有資料」插入（n 為 1-based；n=1 就是 NO1/NO2 之間，n=5 就 NO5/NO6 之間）
    function insertBetweenFilled(n){
        const pos = getPosBetweenFilledPair(n);
        insertRowAt(pos);
    }

    // 介面 2：依「NO 編號」插入（單純在 NOx / NO(x+1) 之間，無論有沒有資料）
    function insertBetweenNos(no){ // no 為 1-based，e.g. 5 → 插在 NO5/NO6 之間
         insertRowAt(no + 1);
    }

    function onSave(){
        const payload = { rows: collectData() };

        if (!validateNumericRows()) {
            if (window.alertify) alertify.alert('Error', L['num_only']); else alert(L['num_only']);
            return;
        }

        if (!validateUniqueColumns()) {
            if (window.alertify) alertify.alert('Error', L['no_dup']); else alert(L['no_dup']);
            return;
        }

        if(payload.rows.length === 0){
            if(window.alertify){ alertify.alert('Info', L['save_fail']); }
            else{ alert(L['save_fail']); }
            return;
        }
        const spinner = document.getElementById('spinner');
        if(spinner) spinner.style.display = 'block';

        $.ajax({
            url: SAVE_URL,
            type: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json; charset=UTF-8',
            success: function(resp){
            if(spinner) spinner.style.display = 'none';

            // 把每列的結果值寫回 UI（可用 row_id 或 result_id）
            if (resp && Array.isArray(resp.rows)) {
                resp.rows.forEach(r => {
                const id = r.result_id || ('result-' + r.row_id);
                const el = document.getElementById(id);
                if (el) el.value = (r.result ?? '');
                });
            }

            if(window.alertify){ alertify.alert('OK', L['saved']); }
            else{ alert(L['saved']); }
            },
            error: function(xhr){
            if(spinner) spinner.style.display = 'none';
            if(window.alertify){ alertify.alert('Error', L['save_fail']); }
            else{ alert(L['save_fail']); }
            console.error('Save error:', xhr?.responseText || xhr);
            }
        });
    }

})();
</script>

<style>
    /* 上下移動按鈕尺寸 */
    .row-actions .icon-btn,
    .control-bar .icon-btn {
    padding: 4px 10px;
    line-height: 1;
    font-weight: 700;
    }
    #btnMoveUp { display: none !important; }
    #btnMoveDown{ display: none !important; }
</style>