<div id="Barcode_Setting" class="divMode" style="display: none; overflow-x: hidden;"  >
            <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_barcode_setting'] ;?></div>
            <div class="table-container">
                <div class="scrollbar" id="style-table">
                    <div class="force-overflow">
                        <table id="job_table" class="table w3-table w3-hoverable">
                            <thead id="header-table">
                                <tr class="w3-dark-grey">
                                    <th></th>
                                    <th><?php echo $text['job_id'];?></th>
                                    <th><?php echo $text['job_name'];?></th>
                                    <th><?php echo $text['system_barcode'];?></th>
                                    <th><?php echo $text['system_barcode_from'];?></th>
                                    <th><?php echo $text['system_barcode_to'];?></th>
                                    <th><?php echo $text['system_barcode_mode'];?></th>
                                </tr>
                            </thead>

                            <tbody style="font-size: 1.8vmin;text-align: center;" id='total_barcodes'>
                                
                                <?php foreach ($data['barcodes'] as $k_b =>$v_b){?>
                                    <tr>
                                            <td style="text-align: center; vertical-align: middle;">
                                                <input
                                                class="form-check-input barcode-check"
                                                type="checkbox"
                                                name="barcode_check"
                                                id="barcode_check_<?php echo (int)$v_b['job_id'].'_'.$k_b; ?>"
                                                value="1"
                                                data-job-id="<?php echo (int)$v_b['job_id']; ?>"
                                                data-job-name="<?php echo htmlspecialchars($v_b['JOBname'], ENT_QUOTES); ?>"
                                                data-barcode="<?php echo htmlspecialchars($v_b['barcode'], ENT_QUOTES); ?>"
                                                data-range-from="<?php echo (int)$v_b['range_from']; ?>"
                                                data-range-count="<?php echo (int)$v_b['range_count']; ?>"
                                                data-barcode-mode="<?php echo (int)$v_b['barcode_mode']; ?>"
                                                <?php /* 若有 seq_id 就帶上，沒有就 -1 */ ?>
                                                data-seq-id="<?php echo isset($v_b['seq_id']) ? (int)$v_b['seq_id'] : -1; ?>"
                                                style="zoom:1.2">
                                            </td>

                                        <td><?php echo $v_b['job_id'];?></td>
                                        <td><?php echo $v_b['JOBname'];?></td>
                                        <td><?php echo $v_b['barcode'];?></td>
                                        <td><?php echo $v_b['range_from'];?></td>
                                        <td><?php echo $v_b['range_count'];?></td>
                                        <td><?php echo $data['barcode_mode'][$v_b['barcode_mode']];?></td>
                                    </tr>
                                <?php } ?>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                
            <hr>
                            
            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode'];?>:</div>
                <div class="col-6 t2">
                    <input id="barcode_name" name="barcode_name"  type="text" value="" maxlength="100" class="t3 form-control" required>
                </div>
            </div>
            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode_match_from'];?>:</div>
                <div class="col-3 t2">
                    <input id="barcode_from" name="barcode_from" type="text" value="1" class="t3 form-control">
                </div>
            </div>
            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode_match_to'];?>:</div>
                <div class="col-3 t2">
                    <input id="barcode_count" name="barcode_count" type="text" value="" class="t3 form-control">
                </div>
            </div>

            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode_mode'];?>:</div>
                <div class="col-3 t2">
                    <select id="barcode_mode" name="barcode_mode" onchange="toggleBarcodeSeq()">
                        <option value="-1" disabled selected ><?php echo $text['system_barcode_setting'];?></option>
                            <?php
                            foreach ($data['barcode_mode'] as $key_b => $value_b) {?>
                                <option value='<?php echo $key_b ;?>'><?php echo $text['system_barcode_mode_' . $key_b] ;?></option>
                            <?php }?>
                            
                    </select>
                </div>
            </div>

            
            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode_select_job'];?>:</div>
                <div class="col-3 t2">
                    <select id="barcode_job" name="barcode_job" onchange="fetchSeqList()" >
                        <option value="-1"><?php echo $text['system_barcode_select_job_m'];?></option>
                            <?php
                            foreach ($data['job_list'] as $key => $value) {?>
                                <option value='<?php echo $value['JOBID'];?>'><?php echo $value['JOBID']." ".$value['JOBname'];?></option>
                            <?php }?>
                            
                    </select>
                </div>
            </div>
            <div id="barcode_select_seq" style="display:none;">
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_barcode_select_seq'];?>:</div>
                    <div class="col-3 t2">
                        <select id="barcode_seq" name="barcode_seq">
                            <option value="-1"><?php echo $text['system_barcode_select_seq_m'];?></option>
                            
                        </select>
                    </div>
                </div>
            </div>

            <div style="text-align: center;margin-top: 30px; margin-bottom:10px">
                <input type="hidden" id="barcode_id" value="">
                <button class="all-btn w3-button w3-border w3-round-large" onclick="update_barcode()" ><?php echo $text['save'];?></button>&nbsp;&nbsp;
                <button class="all-btn w3-button w3-border w3-round-large" onclick="delete_barcode_item()" ><?php echo $text['delete_text'];?></button>
            </div>               
</div>

<script>

    function removeDuplicateOptions(selectId) {
        const select = document.getElementById(selectId);
        const seen = new Set();
        const options = Array.from(select.options);

        options.forEach(option => {
            if (seen.has(option.value) && option.value !== "-1") {
                option.remove(); // 移除重複
            } else {
                seen.add(option.value);
            }
        });
    }

    // —— 規則：哪些 barcode_mode 需要 SEQ（依實際調整）——
    function modeRequiresSeq(modeVal) {
    return String(modeVal) === '3'; // 範例：mode=2 需要 SEQ
    }

    // 清空/預設表單
    function resetBarcodeForm() {
    $('#barcode_name').val('');
    $('#barcode_from').val('1');
    $('#barcode_count').val('');
    $('#barcode_mode').val('-1');
    $('#barcode_job').val('-1');
    $('#barcode_seq').html('<option value="-1"><?php echo $text['system_barcode_select_seq_m'];?></option>');
    $('#barcode_select_seq').hide();
    }

    // 顯示/隱藏 SEQ 區塊
    function toggleBarcodeSeq() {
    const need = modeRequiresSeq($('#barcode_mode').val());
    $('#barcode_select_seq').toggle(need);
    if (need) {
        const jobId = $('#barcode_job').val();
        if (jobId && jobId !== '-1') {
        fetchSeqList(jobId, null); // 無預選
        }
    }
    }

    // 升級版：透過 JOBID 載入 SEQ，支援「預選 seqId」，並自動去除重複
    // ---- 全域保險變數 ----
    let _fetchSeqXhr = null;   
    let _fetchSeqReqId = 0;   

    function fetchSeqList(jobId = null, selectedSeqId = null) {
        const jobSelect  = document.getElementById('barcode_job');
        const barcodeSeq = document.getElementById('barcode_seq');
        if (!jobSelect || !barcodeSeq) return;

        if (!jobId) jobId = jobSelect.value;

        // 先取消舊請求（若有）
        if (_fetchSeqXhr) { try { _fetchSeqXhr.abort(); } catch(e){} _fetchSeqXhr = null; }

        // 重設下拉
        barcodeSeq.innerHTML = '';
        const opt0 = document.createElement('option');
        opt0.value = '-1';
        opt0.textContent = "<?php echo $text['system_barcode_select_seq_m'];?>";
        barcodeSeq.appendChild(opt0);

        if (jobId === '-1') return;

        // 標記這次請求的編號
        const myReqId = ++_fetchSeqReqId;

        _fetchSeqXhr = $.ajax({
            url: '?url=Settings/GetJobSeq',
            type: 'POST',
            data: { job_id: jobId },
            success: function(response) {
            // 只處理「最後一個」請求的回應
            if (myReqId !== _fetchSeqReqId) return;

            let seqList = [];
            try { seqList = JSON.parse(response) || []; }
            catch (e) { console.error('Invalid JSON:', response); return; }

            // 去重 + 批次 append（用 fragment）
            const seen = new Set();
            const frag = document.createDocumentFragment();

            seqList.forEach(seq => {
                const id = String(seq.SEQID);
                if (seen.has(id)) return;
                seen.add(id);

                const option = document.createElement('option');
                option.value = id;
                option.textContent = `${id} ${seq.SEQname ?? ''}`;
                frag.appendChild(option);
            });

            barcodeSeq.appendChild(frag);

            // 預選（若有）
            if (selectedSeqId != null && selectedSeqId !== '-1') {
                barcodeSeq.value = String(selectedSeqId);
                if (barcodeSeq.value !== String(selectedSeqId)) {
                barcodeSeq.value = '-1';
                }
            }
            },
            error: function(xhr, status, err) {
            if (status !== 'abort') console.error('GetJobSeq error:', err);
            },
            complete: function() {
            // 只有「最後一個請求」完成時，才清掉指標
            if (myReqId === _fetchSeqReqId) _fetchSeqXhr = null;
            }
        });
        }

        // ---- 建議的事件綁定（避免重複）----
        $(function () {
        // 先解綁再綁，避免多重初始化導致執行兩次
        $('#barcode_job').off('change.fetchSeq').on('change.fetchSeq', function () {
            fetchSeqList(this.value, null);
        });

        // 首次載入
        fetchSeqList();
        });


    // 勾選列 → 單選 + 高亮 + 帶入表單（最後點選的為準）
    $(document).on('change', '.barcode-check', function () {
    const isChecked = this.checked;
    const $row = $(this).closest('tr');

    // 單選：勾到自己時，取消其他
    if (isChecked) {
        $('.barcode-check').not(this).each(function () {
        this.checked = false;
        $(this).closest('tr').find('td').css('background-color', '');
        });
    }

    // 高亮/還原
    $row.find('td').css('background-color', isChecked ? '#9AC0CD' : '');

    if (isChecked) {
        const jobId      = String($(this).data('job-id'));
        const barcode    = String($(this).data('barcode'));
        const rangeFrom  = String($(this).data('range-from'));
        const rangeCount = String($(this).data('range-count'));
        const modeVal    = String($(this).data('barcode-mode'));
        const seqId      = String($(this).data('seq-id'));

        // 帶入基本欄位
        $('#barcode_name').val(barcode);
        $('#barcode_from').val(rangeFrom);
        $('#barcode_count').val(rangeCount);
        $('#barcode_mode').val(modeVal);
        $('#barcode_job').val(jobId);

        // 依模式顯示/隱藏 SEQ，下拉選單載入與預選
        toggleBarcodeSeq();
        if (modeRequiresSeq(modeVal)) {
            fetchSeqList(jobId, seqId);
        }
    } else if ($('.barcode-check:checked').length === 0) {
        resetBarcodeForm();
    }
    });

    // 手動改 Job / Mode 也連動
    $('#barcode_job').on('change', function() {
    if (modeRequiresSeq($('#barcode_mode').val())) {
        fetchSeqList(this.value, null);
    }
    });
    $('#barcode_mode').on('change', toggleBarcodeSeq);

    // 初次載入：對齊顯示狀態
    $(function(){ toggleBarcodeSeq(); });


    // 局部刷新條碼清單（避免整頁 reload）
    function refreshBarcodeList(i18n, options) {
        options = options || {};
        const keepScroll = options.keepScroll !== false; // 預設保留卷軸位置
        const container = document.getElementById('total_barcodes');
        const prevScroll = (keepScroll && container) ? container.scrollTop : 0;

        $.ajax({
            url: "?url=Settings/show_Barcodes",
            method: "GET",
            success: function (html) {
            $('#total_barcodes').html(html);
            if (keepScroll && container) container.scrollTop = prevScroll;

            // 事件委已用 $(document).on('change', '.barcode-check', ...) 不需重綁
            try { if (typeof toggleBarcodeSeq === 'function') toggleBarcodeSeq(); } catch(_) {}
            try {
                if ($('.barcode-check:checked').length === 0 && typeof resetBarcodeForm === 'function') {
                resetBarcodeForm();
                }
            } catch(_) {}
            try { $(document).trigger('barcode:list:refreshed'); } catch(_) {}
            },
            error: function () {
            console.error("刷新條碼失敗");
            try {
                if (i18n && i18n.refreshFail) alertify.error(i18n.refreshFail);
                else alertify.error('刷新條碼列表失敗');
            } catch(_) {}
            setTimeout(function(){ try { alertify.closeAll(); } catch(_) {} }, 3000);
            }
        });
    }




    function delete_barcode_item() {
        // ---- 語系處理 ----
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

        const langKey = getLang().includes('zh-tw') || getLang().includes('hant') || getLang().includes('tw') || getLang().includes('hk') || getLang().includes('mo')
            ? 'zh-tw'
            : (getLang().includes('zh-cn') || getLang().includes('hans') || getLang().includes('cn') || getLang().includes('sg'))
                ? 'zh-cn'
                : 'en-us';

        const i18n = {
            'en-us': {
                info: 'Info',
                error: 'Error',
                confirm: 'Confirm',
                ok: 'OK',
                cancel: 'Cancel',
                noSelect: 'Please select at least one barcode.',
                confirmMsg: n => `Are you sure you want to delete ${n} barcode(s)?`,
                refreshFail: 'Failed to refresh barcode list',
                deleteFail: 'Failed to delete barcode. Please try again later.',
            },
            'zh-tw': {
                info: '提示',
                error: '錯誤',
                confirm: '確認',
                ok: '確定',
                cancel: '取消',
                noSelect: '請先勾選要刪除的條碼。',
                confirmMsg: n => `確定要刪除 ${n} 筆條碼嗎？`,
                refreshFail: '刷新條碼列表失敗',
                deleteFail: '無法刪除條碼，請稍後再試。',
            },
            'zh-cn': {
                info: '提示',
                error: '错误',
                confirm: '确认',
                ok: '确定',
                cancel: '取消',
                noSelect: '请先勾选要删除的条码。',
                confirmMsg: n => `确定要删除 ${n} 条条码吗？`,
                refreshFail: '刷新条码列表失败',
                deleteFail: '无法删除条码，请稍后再试。',
            }
        }[langKey];

        // 套用到 alertify 的按鈕
        try {
            if (alertify?.defaults?.glossary) {
                alertify.defaults.glossary.ok = i18n.ok;
                alertify.defaults.glossary.cancel = i18n.cancel;
                alertify.defaults.glossary.title = i18n.info;
            } else if (typeof alertify.okBtn === 'function' && typeof alertify.cancelBtn === 'function') {
                alertify.okBtn(i18n.ok).cancelBtn(i18n.cancel);
            }
        } catch (_) {}

        const spinner = document.getElementById('spinner');
        // 更穩健的選取：支援 .barcode-check、name="barcode_check"、以及 #barcode_check
        const checked = document.querySelectorAll('.barcode-check:checked, input[name="barcode_check"]:checked, #barcode_check:checked');

        // 從 data-job-id 取得 jobId；若缺，從 id="barcode_check_<jobid>_..." 推回
        const jobIds = Array.from(checked).map(cb => {
            const raw = (cb.dataset && cb.dataset.jobId) ? cb.dataset.jobId : cb.getAttribute('data-job-id');
            if (raw && /^\d+$/.test(raw)) return Number(raw);
            const m = (cb.id || '').match(/^barcode_check_(\d+)_/);
            if (m) return Number(m[1]);
            return null;
        }).filter(n => Number.isInteger(n));

        if (checked.length === 0 || jobIds.length === 0) {
            alertify.alert(i18n.info, i18n.noSelect);
            setTimeout(() => alertify.closeAll(), 3000);
            return;
        }




        alertify.confirm(
            i18n.confirm,
            i18n.confirmMsg(jobIds.length),
            function onOk() {
                if (spinner) spinner.style.display = 'block';

                $.ajax({
                    url: "?url=Settings/delete_barcodes",
                    method: "POST",
                    data: { job_id: jobIds }, // jQuery 會自動用 job_id[]
                    dataType: 'json',
                    success: function(response) {
                        if (spinner) spinner.style.display = 'none';

                        const res_type = response?.res_type || i18n.info;
                        const res_msg  = response?.res_msg  || '';

                        alertify.alert(res_type, res_msg, function () {
                            sessionStorage.setItem('Barcode_Setting', 'block');
                            sessionStorage.setItem('Controller_Setting', 'none');
                        });

                        setTimeout(function () {
                            alertify.closeAll();
                            // 使用局部刷新，不整頁重載
                            refreshBarcodeList(i18n);
                        }, 3000);
                    },
                    error: function(xhr, status, error) {
                        if (spinner) spinner.style.display = 'none';
                        console.error("刪除時發生錯誤:", error, xhr?.responseText);
                        alertify.alert(i18n.error, i18n.deleteFail);
                        setTimeout(() => alertify.closeAll(), 3000);
                    }
                });
            },
            function onCancel() { /* 使用者取消 */ }
        );
    }





</script>
