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
                <button class="all-btn w3-button w3-border w3-round-large" onclick="update_barcode()" ><?php echo $text['save'];?></button>&nbsp;&nbsp;
                <button class="all-btn w3-button w3-border w3-round-large" onclick="delete_barcode_item()" ><?php echo $text['delete_text'];?></button>
            </div>               
</div>

<script>
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

    // 升級版：透過 JOBID 載入 SEQ，支援「預選 seqId」
    function fetchSeqList(jobId = null, selectedSeqId = null) {
    const jobSelect  = document.getElementById('barcode_job');
    const barcodeSeq = document.getElementById('barcode_seq');

    if (!jobId) jobId = jobSelect.value;

    // Reset list
    barcodeSeq.innerHTML = '';
    const defaultOption = document.createElement('option');
    defaultOption.value = '-1';
    defaultOption.textContent = "<?php echo $text['system_barcode_select_seq_m'];?>";
    barcodeSeq.appendChild(defaultOption);

    if (jobId === '-1') return;

    $.ajax({
        url: '?url=Settings/GetJobSeq',
        type: 'POST',
        data: { job_id: jobId },
        success: function(response) {
        let seqList = [];
        try { seqList = JSON.parse(response); } catch(e) {
            console.error('Invalid JSON:', response);
            return;
        }

        seqList.forEach(seq => {
            const option = document.createElement('option');
            option.value = seq.SEQID;
            option.textContent = `${seq.SEQID} ${seq.SEQname}`;
            barcodeSeq.appendChild(option);
        });

        // 預選（若有）
        if (selectedSeqId != null && selectedSeqId !== '-1') {
            barcodeSeq.value = String(selectedSeqId);
            if (barcodeSeq.value !== String(selectedSeqId)) {
            barcodeSeq.value = '-1';
            }
        }
        },
        error: function(xhr, status, error) {
        console.error('Error occurred:', error);
        }
    });
    }

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



    function delete_barcode_item() {
        const spinner = document.getElementById('spinner');
        const checked = document.querySelectorAll('.barcode-check:checked');

        // 取 data-job-id，正規化為正整數陣列
        const jobIds = Array.from(checked)
            .map(cb => Number(cb.dataset.jobId))
            .filter(n => Number.isInteger(n) && n > 0);

        if (jobIds.length === 0) {
            alertify.alert('提示', '請先勾選要刪除的條碼。');
            setTimeout(() => alertify.closeAll(), 3000);
            return;
        }

        alertify.confirm(
            '確認',
            `確定要刪除 ${jobIds.length} 筆條碼嗎？`,
            function onOk() {
                if (spinner) spinner.style.display = 'block';

                $.ajax({
                    url: "?url=Settings/delete_barcodes",
                    method: "POST",
                    data: { job_id: jobIds },       // 後端需支援 job_id 為陣列
                    traditional: true,              // job_id[]=1&job_id[]=2...
                    dataType: 'json',
                    success: function(response) {
                        if (spinner) spinner.style.display = 'none';

                        const res_type = response?.res_type || 'Success';
                        const res_msg  = response?.res_msg  || '已處理完成。';

                        // 顯示完成訊息
                        alertify.alert(res_type, res_msg, function () {
                            // 可選：立即做前置狀態設定
                            sessionStorage.setItem('Barcode_Setting', 'block');
                            sessionStorage.setItem('Controller_Setting', 'none');
                        });

                        // ✅ 需求：成功後延遲 3 秒關閉 alert 並刷新列表
                        setTimeout(function () {
                            alertify.closeAll();  // 關閉所有 alertify 視窗

                            // 刷新條碼列表
                            $.ajax({
                                url: "?url=Settings/show_Barcodes",
                                method: "GET",
                                success: function (html) {
                                    $('#total_barcodes').html(html);

                                    // ✅ 刷新完成後再重整頁面
                                    location.reload();

                                },
                                error: function (xhr, status, error) {
                                    console.error("刷新條碼失敗:", error);
                                    alertify.error('刷新條碼列表失敗');
                                    setTimeout(() => alertify.closeAll(), 3000);

                                    // ✅ 刷新完成後再重整頁面
                                    location.reload();

                                }
                            });
                        }, 3000);
                    },
                    error: function(xhr, status, error) {
                        if (spinner) spinner.style.display = 'none';
                        console.error("刪除時發生錯誤:", error, xhr?.responseText);
                        alertify.alert('Error', '無法刪除條碼，請稍後再試。');
                        setTimeout(() => alertify.closeAll(), 3000);
                    }
                });
            },
            function onCancel() { /* 使用者取消 */ }
        );
    }





</script>
