<?php 
    if($_SESSION['language'] == 'en-us'){
        $calendar_lang = 'Please Select Seq';
    }else if($_SESSION['language'] == 'zh-cn'){
        $calendar_lang = '请选择工序';
    }else if($_SESSION['language'] == 'zh-tw'){
        $calendar_lang = '请選擇工序';
    }else{
        $calendar_lang = '';
    }
?>
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
        // --- i18n 翻譯文字 ---
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
        </style>

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
                            <th style="width:90px"></th>
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
    
    let ROW_UID = 1; 
    const IS_ADMIN = <?php echo json_encode(($_SESSION['privilege'] ?? '') === 'admin'); ?>;
    const L = <?php echo json_encode($L, JSON_UNESCAPED_UNICODE); ?>;
    const SAVE_URL = '?url=Customize/save_positions'; // TODO: 換成你的實際 API

    const tbody = document.getElementById('dynTbody');
    const btnAdd = document.getElementById('btnAddRow');
    const btnSave = document.getElementById('btnSave');

    const ckAll  = document.getElementById('ckAll');
    const btnDelSel = document.getElementById('btnDeleteSelected');
    const btnDelAll = document.getElementById('btnDeleteAll');

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
        return [...tbody.querySelectorAll(`tr td:nth-child(${colIndex}) input.table-input`)];
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
        // 把第一個相同值也標紅
        const first = seen.get(v);
        if (first) first.classList.add('invalid');
        } else {
        seen.set(v, el);
        }
    });
    return ok;
    }
    function validateUniqueColumns(){
        // 第3欄=讀取位置、第4欄=輸入位置
        const okRead  = validateUniqueForCol(3);
        const okInput = validateUniqueForCol(4);
        return okRead && okInput;
    }
    // 即時高亮（輸入任何數字欄時觸發）
    document.addEventListener('input', (e)=>{
        if (e.target && e.target.matches('td:nth-child(3) input.table-input, td:nth-child(4) input.table-input')) {
            validateUniqueColumns();
        }
    });




    // ——— helpers ———
    function renumber(){
    [...tbody.querySelectorAll('tr')].forEach((tr, idx)=>{
        const noCell = tr.querySelector('.cell-no');
        if(noCell) noCell.textContent = String(idx+1);
    });
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

    function addRow(readVal='', inputVal='', resultVal=''){
        const tr = document.createElement('tr');

        // <<< 新增：固定 rowId 與 dataset
        const rowId = 'r' + (ROW_UID++);
        tr.dataset.rowId = rowId;

        // 選取（admin）
        const tdSel = document.createElement('td');
        tdSel.className = 'sel-ck';
        if (IS_ADMIN) {
            const ck = document.createElement('input');
            ck.type = 'checkbox';
            ck.className = 'row-ck';
            tdSel.appendChild(ck);
        }
        tr.appendChild(tdSel);

        // NO
        const tdNo = document.createElement('td');
        tdNo.className = 'cell-no';
        tr.appendChild(tdNo);

        // 讀取位置
        const tdRead = document.createElement('td');
        const inRead = document.createElement('input');
        inRead.type = 'text'; inRead.className = 'table-input';
        inRead.placeholder = L['placeholder_read'];
        inRead.value = readVal;
        tdRead.appendChild(inRead);
        tr.appendChild(tdRead);

        // 輸入位置（寫入）
        const tdInput = document.createElement('td');
        const inInput = document.createElement('input');
        inInput.type = 'text'; inInput.className = 'table-input';
        inInput.placeholder = L['placeholder_input'];
        inInput.value = inputVal;
        tdInput.appendChild(inInput);
        tr.appendChild(tdInput);

        // 結果（唯讀）
        const tdRes = document.createElement('td');
        const inRes = document.createElement('input');
        inRes.type = 'text';
        inRes.className = 'table-input result-input';
        inRes.value = resultVal || '';
        inRes.readOnly = true;
        inRes.id = 'result-' + rowId; // <<< 新增：指定唯一 id
        tdRes.appendChild(inRes);
        tr.appendChild(tdRes);

        if(IS_ADMIN){
            const tdAct = document.createElement('td');
            tdAct.className = 'row-actions';
            const del = document.createElement('button');
            del.textContent = L['delete'];
            del.className = 'w3-btn w3-round-large';
            del.addEventListener('click', ()=>{
                if (window.alertify) {
                    alertify.confirm(L['delete'], L['confirm_delete'], ()=>{
                        tr.remove(); renumber(); syncCkAllState();
                    }, function(){});
                } else {
                    if(confirm(L['confirm_delete'])){ tr.remove(); renumber(); syncCkAllState(); }
                }
            });
            tdAct.appendChild(del);
            tr.appendChild(tdAct);
        }

        tbody.appendChild(tr);
        renumber();
        return tr;
    }
    



    // ——— initial row (optional) ———
    addRow();

    // ——— admin controls ———
    if(IS_ADMIN){
        if(btnAdd) btnAdd.addEventListener('click', ()=> addRow());
        if(btnSave) btnSave.addEventListener('click', onSave);
    }else{
        // 非 admin：沿用你既有的 disable 行為
        // 這裡保險起見再次鎖住表格
        [...tbody.querySelectorAll('input,select,button')].forEach(el=> el.disabled = true);
    }



    function collectData(){
        const rows = [];
        [...tbody.querySelectorAll('tr')].forEach(tr=>{
            const readPos  = tr.querySelector('td:nth-child(3) input')?.value?.trim() ?? '';
            const inputPos = tr.querySelector('td:nth-child(4) input')?.value?.trim() ?? '';
            const result   = tr.querySelector('.result-input')?.value ?? '';
            const rowId    = tr.dataset.rowId || '';

            if(readPos || inputPos || result){
                rows.push({ row_id: rowId, read_pos: readPos, input_pos: inputPos, result });
            }
        });
        return rows;
    }

    function syncCkAllState(){
        if (!IS_ADMIN || !ckAll) return;
        const rowCks = tbody.querySelectorAll('.row-ck');
        if (rowCks.length === 0) {
            ckAll.checked = false;
            ckAll.indeterminate = false;   // 不使用半選
            return;
        }
        const checked = [...rowCks].filter(ck => ck.checked).length;
        if (checked === rowCks.length) {
            // 全部勾選 → 全選打勾
            ckAll.checked = true;
            ckAll.indeterminate = false;
        }else {
            // 只要不是全部勾選（包含 1/3、2/3）→ 全選不勾，且不顯示半選
            ckAll.checked = false;
            ckAll.indeterminate = false;
        }
    }



    if (IS_ADMIN && ckAll) {
        ckAll.addEventListener('change', ()=>{
            const rowCks = tbody.querySelectorAll('.row-ck');
            rowCks.forEach(ck => ck.checked = ckAll.checked);
            ckAll.indeterminate = false; // 點全選後也確保不出現半選
        });
        tbody.addEventListener('change', (e)=>{
            if (e.target && e.target.classList.contains('row-ck')) {
            syncCkAllState();
            }
        });
    }

    function deleteSelectedRows(){
        const sel = [...tbody.querySelectorAll('.row-ck:checked')].map(ck => ck.closest('tr'));
        if(sel.length === 0){
            if(window.alertify){ alertify.alert('Info', L['none_selected']); }
            else{ alert(L['none_selected']); }
            return;
        }
        const doRemove = () => {
            sel.forEach(tr => tr.remove());
            renumber(); syncCkAllState();
        };
        if(window.alertify){
            alertify.confirm(L['delete_sel'], L['confirm_delete'], doRemove, function(){});
        }else{
            if(confirm(L['confirm_delete'])) doRemove();
        }
    }

    function deleteAllRows(){
        const doRemoveAll = () => {
            tbody.innerHTML = '';
            renumber(); syncCkAllState();
        };
        if(window.alertify){
            alertify.confirm(L['delete_all'], L['confirm_delete_all'], doRemoveAll, function(){});
        }else{
            if(confirm(L['confirm_delete_all'])) doRemoveAll();
        }
    }

    if(IS_ADMIN){
        if(btnDelSel) btnDelSel.addEventListener('click', deleteSelectedRows);
        if(btnDelAll) btnDelAll.addEventListener('click', deleteAllRows);
    }



    function onSave(){
        const payload = { rows: collectData() };

        // ⬇️ 新增：若數字欄位不合法就終止並提示
        if (!validateNumericRows()) {
            if (window.alertify) alertify.alert('Error', L['num_only']); else alert(L['num_only']);
            return;
        }

        // 再做不可重複檢查
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

                // <<< 新增：把每列的結果值寫回 UI
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

