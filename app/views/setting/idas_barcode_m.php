<div id="Barcode_Setting" class="divMode" style="display: none">
        <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_barcode_setting'] ;?></div>
        <div class="barcode-scrollbar" id="style-barcode">
            <div class="barcode-force-overflow">  
                <div style="position: relative; max-width: 100%;">                
                    <div class="table-container" id="tableContainer">
                        <table id="job_table" class="setting-table w3-table w3-hoverable">
                            <thead style="font-size: 3vmin;">
                                <tr class="w3-dark-grey">
                                    <th></th>
                                    <th><?php echo $text['job_id'];?></th>
                                    <th><?php echo $text['job_name'];?></th>
                                    <th><?php echo $text['system_barcode'];?></th>
                                    <th><?php echo $text['system_barcode_from'];?></th>
                                    <th>to</th>
                                    <th>barcode mode</th>
                                    <th>Count</th>
                                </tr>
                            </thead>

                            <tbody style="font-size: 3vmin;" id='total_barcodes'>
                                <?php foreach ($data['barcodes'] as $k_b =>$v_b){?>
                                    <tr>
                                        <td style="text-align: center; vertical-align: middle;" >
                                            <input class="form-check-input" type="checkbox" name="barcode_check" id="barcode_check" value="<?php echo $v_b['barcode_selected_job'];?>" style="zoom:1.2">
                                        </td>
                                        <td><?php echo $v_b['barcode_selected_job'];?></td>
                                        <td><?php echo $v_b['job_name'];?></td>
                                        <td><?php echo $v_b['barcode'];?></td>
                                        <td><?php echo $v_b['barcode_range_from'];?></td>
                                        <td><?php echo $v_b['barcode_range_to'];?></td>
                                        <td><?php echo $data['barcode_mode'][$v_b['barcode_enable']];?></td>
                                        <td><?php echo $v_b['barcode_range_count'];?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bacode-btn-container">
                        <button class="bacode-btn" onclick="changePage('job_table', -1)">&#60;</button>
                        <button class="bacode-btn" onclick="changePage('job_table', 1)">&#62;</button>
                    </div>
                </div>    
                    
                <hr>
                                
                <div class="row t2">
                    <div class="col-5 t1"><?php echo $text['system_barcode'];?>:</div>
                    <div class="col-7 t2">
                        <input id="barcode_name" name="barcode_name" style="height: 32px" type="text" value="" maxlength="100" class="form-control" required>
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-5 t1"><?php echo $text['system_barcode_match_from'];?>:</div>
                    <div class="col-7 t2">
                        <input id="barcode_from" name="barcode_from" style="height: 32px" type="text" value="" class="form-control">
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-5 t1"><?php echo $text['system_barcode_match_to'];?>:</div>
                    <div class="col-7 t2">
                        <input id="barcode_count" name="barcode_count" style="height: 32px" type="text" value="" class="form-control">
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-5 t1"><?php echo $text['select_job'];?>:</div>
                    <div class="col t2">
                    <select id="barcode_job" name="barcode_job" onchange="fetchSeqList()" >
                        <option value="-1" disabled selected ><?php echo $text['system_barcode_select_job_m'];?></option>
                            <?php
                            foreach ($data['job_list'] as $key => $value) {?>
                                <option value='<?php echo $value['job_id'];?>'><?php echo $value['job_id']." ".$value['job_name'];?></option>
                            <?php }?>
                            
                    </select>
                    </div>
                </div>

                <div id="barcode_select_seq" style="display:none;">
                    <div class="row t2">
                        <div class="col-5 t1"><?php echo $text['system_barcode_select_seq'];?>:</div>
                        <div class="col t2">
                            <select id="barcode_seq" name="barcode_seq">
                                <option value="-1"><?php echo $text['system_barcode_select_seq_m'];?></option>   
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </div>    
                
        <div style="text-align: center;margin-top: 30px;">
            <button class="all-btn w3-button w3-border w3-round-large" onclick="update_barcode()" ><?php echo $text['save'];?></button>&nbsp;&nbsp;
            <button class="all-btn w3-button w3-border w3-round-large" onclick="delete_barcode()" ><?php echo $text['delete_text'];?></button>
        </div>               
    </div>

<script>

$(document).on('change', 'input[name="barcode_check"]', function () {
    const isChecked = $(this).is(':checked');
    const $row = $(this).closest('tr');

    
   // === 勾選時帶入 JOB / SEQ ===
   if (isChecked) {
       const jobId = $(this).data('job-id');
       const seqId = $(this).data('seq-id');
       fetchSeqList(jobId, seqId);
   }


    // 切換背景顏色
    if (isChecked) {
        $row.find('td').css('background-color', '#9AC0CD');
    } else {
        $row.find('td').css('background-color', ''); // 清空回到預設
    }

    // 欄位啟用/禁用
    $row.find('input, select, textarea')
        .not(this) // 排除自己
        .prop('disabled', !isChecked);

    // 勾選時自動 focus 第一個可輸入欄位
    if (isChecked) {
        $row.find('input, select, textarea')
            .not(this)
            .first()
            .focus();
    }
});


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
    const checked = document.querySelectorAll('.barcode-check:checked');

    const jobIds = Array.from(checked)
        .map(cb => Number(cb.dataset.jobId))
        .filter(n => Number.isInteger(n) && n > 0);

    if (jobIds.length === 0) {
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
                data: { job_id: jobIds },
                traditional: true,
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
                        $.ajax({
                            url: "?url=Settings/show_Barcodes",
                            method: "GET",
                            success: function (html) {
                                $('#total_barcodes').html(html);
                                location.reload();
                            },
                            error: function () {
                                console.error("刷新條碼失敗");
                                alertify.error(i18n.refreshFail);
                                setTimeout(() => alertify.closeAll(), 3000);
                                location.reload();
                            }
                        });
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