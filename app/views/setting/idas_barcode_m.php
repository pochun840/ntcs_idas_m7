<?php if (defined('IS_ICONTROLLER') && IS_ICONTROLLER): ?>
<div id="Barcode_Setting" class="divMode" style="display: none">
    <div class="col t1" style="padding-left: 3%; font-weight: bold; padding-top: 1%;">
        <?php echo $text['system_barcode_setting']; ?>
    </div>

    <div class="barcode-scrollbar" id="style-barcode">
        <div class="barcode-force-overflow">
            <div style="position: relative; max-width: 100%;">
                <div class="table-container" id="tableContainer">
                    <table id="job_table" class="setting-table w3-table w3-hoverable">
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
                                        <td style="text-align:center; vertical-align:middle;">
                                            <input
                                                class="form-check-input barcode-check"
                                                type="checkbox"
                                                name="barcode_check"
                                                id="barcode_check_<?php echo (int)($v_b['barcode_rowid'] ?? 0); ?>"
                                                value="1"
                                                data-id="<?php echo (int)($v_b['barcode_rowid'] ?? 0); ?>"
                                                data-barcode-rowid="<?php echo (int)($v_b['barcode_rowid'] ?? 0); ?>"
                                                data-job-id="<?php echo (int)$v_b['job_id']; ?>"
                                                data-barcode="<?php echo htmlspecialchars($v_b['barcode'], ENT_QUOTES); ?>"
                                                data-range-from="<?php echo (int)$v_b['range_from']; ?>"
                                                data-range-count="<?php echo (int)$v_b['range_count']; ?>"
                                                data-barcode-mode="<?php echo (int)$v_b['barcode_mode']; ?>"
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

                <div class="bacode-btn-container">
                    <button class="bacode-btn" type="button" onclick="changePage('job_table', -1)">&#60;</button>
                    <button class="bacode-btn" type="button" onclick="changePage('job_table', 1)">&#62;</button>
                </div>
            </div>

            <hr>
        </div>
    </div>
</div>

<script>
$(document).on('change', 'input[name="barcode_check"]', function () {
    const isChecked = $(this).is(':checked');
    const $row = $(this).closest('tr');

    if (isChecked) {
        const jobId = $(this).data('job-id');
        const seqId = $(this).data('seq-id');

        if (jobId) {
            $('#barcode_job').val(String(jobId));
            fetchSeqList(jobId, seqId);
        }
    }

    if (isChecked) {
        $row.find('td').css('background-color', '#9AC0CD');
    } else {
        $row.find('td').css('background-color', '');
    }

    $row.find('input, select, textarea')
        .not(this)
        .prop('disabled', !isChecked);

    if (isChecked) {
        $row.find('input, select, textarea')
            .not(this)
            .first()
            .focus();
    }
});

function removeDuplicateOptions(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const seen = new Set();
    const options = Array.from(select.options);

    options.forEach(option => {
        if (seen.has(option.value) && option.value !== "-1") {
            option.remove();
        } else {
            seen.add(option.value);
        }
    });
}

let _fetchSeqXhr = null;
let _fetchSeqReqId = 0;

function fetchSeqList(jobId = null, selectedSeqId = null) {
    const jobSelect = document.getElementById('barcode_job');
    const barcodeSeq = document.getElementById('barcode_seq');
    const barcodeSeqWrap = document.getElementById('barcode_select_seq');

    if (!jobSelect || !barcodeSeq) return;

    if (!jobId) jobId = jobSelect.value;

    if (_fetchSeqXhr) {
        try { _fetchSeqXhr.abort(); } catch(e){}
        _fetchSeqXhr = null;
    }

    barcodeSeq.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '-1';
    opt0.textContent = "<?php echo $text['system_barcode_select_seq_m']; ?>";
    barcodeSeq.appendChild(opt0);

    if (jobId === '-1' || !jobId) {
        if (barcodeSeqWrap) barcodeSeqWrap.style.display = 'none';
        return;
    }

    if (barcodeSeqWrap) barcodeSeqWrap.style.display = 'block';

    const myReqId = ++_fetchSeqReqId;

    _fetchSeqXhr = $.ajax({
        url: '?url=Settings/GetJobSeq',
        type: 'POST',
        data: { job_id: jobId },
        success: function(response) {
            if (myReqId !== _fetchSeqReqId) return;

            let seqList = [];
            try {
                seqList = JSON.parse(response) || [];
            } catch (e) {
                console.error('Invalid JSON:', response);
                return;
            }

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
            if (myReqId === _fetchSeqReqId) _fetchSeqXhr = null;
        }
    });
}

$(function () {
    $('#barcode_job').off('change.fetchSeq').on('change.fetchSeq', function () {
        fetchSeqList(this.value, null);
    });

    fetchSeqList();
});

function delete_barcode_item() {
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

    const lang = getLang();
    const langKey = lang.includes('zh-tw') || lang.includes('hant') || lang.includes('tw') || lang.includes('hk') || lang.includes('mo')
        ? 'zh-tw'
        : (lang.includes('zh-cn') || lang.includes('hans') || lang.includes('cn') || lang.includes('sg'))
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

    try {
        if (alertify?.defaults?.glossary) {
            alertify.defaults.glossary.ok = i18n.ok;
            alertify.defaults.glossary.cancel = i18n.cancel;
            alertify.defaults.glossary.title = i18n.info;
        }
    } catch (_) {}

    const spinner = document.getElementById('spinner');
    const checked = document.querySelectorAll('.barcode-check:checked');

    const barcodeIds = Array.from(checked)
        .map(cb => {
            const raw = cb.dataset.id || cb.dataset.barcodeRowid || cb.getAttribute('data-id') || cb.getAttribute('data-barcode-rowid');
            return /^\d+$/.test(String(raw)) ? Number(raw) : null;
        })
        .filter(n => Number.isInteger(n) && n > 0);

    if (barcodeIds.length === 0) {
        alertify.alert(i18n.info, i18n.noSelect);
        setTimeout(() => alertify.closeAll(), 3000);
        return;
    }

    alertify.confirm(
        i18n.confirm,
        i18n.confirmMsg(barcodeIds.length),
        function onOk() {
            if (spinner) spinner.style.display = 'block';

            $.ajax({
                url: "?url=Settings/delete_barcodes",
                method: "POST",
                data: { barcode_id: barcodeIds },
                dataType: 'json',
                success: function(response) {
                    if (spinner) spinner.style.display = 'none';

                    const res_type = response?.res_type || i18n.info;
                    const res_msg = response?.res_msg || '';

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
        function onCancel() {}
    );
}
</script>
<?php else: ?>
<div id="Barcode_Setting" class="divMode" style="display: none">
    <div class="col t1" style="padding-left: 3%; font-weight: bold; padding-top: 1%;">
        <?php echo $text['system_barcode_setting']; ?>
    </div>

    <div class="barcode-scrollbar" id="style-barcode">
        <div class="barcode-force-overflow">
            <div style="position: relative; max-width: 100%;">
                <div class="table-container" id="tableContainer">
                    <table id="job_table" class="setting-table w3-table w3-hoverable">
                        <thead id="header-table">
                                <tr class="w3-dark-grey">
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

                <div class="bacode-btn-container">
                    <button class="bacode-btn" type="button" onclick="changePage('job_table', -1)">&#60;</button>
                    <button class="bacode-btn" type="button" onclick="changePage('job_table', 1)">&#62;</button>
                </div>
            </div>

            <hr>
        </div>
    </div>
</div>

<script>
$(document).on('change', 'input[name="barcode_check"]', function () {
    const isChecked = $(this).is(':checked');
    const $row = $(this).closest('tr');

    if (isChecked) {
        const jobId = $(this).data('job-id');
        const seqId = $(this).data('seq-id');

        if (jobId) {
            $('#barcode_job').val(String(jobId));
            fetchSeqList(jobId, seqId);
        }
    }

    if (isChecked) {
        $row.find('td').css('background-color', '#9AC0CD');
    } else {
        $row.find('td').css('background-color', '');
    }

    $row.find('input, select, textarea')
        .not(this)
        .prop('disabled', !isChecked);

    if (isChecked) {
        $row.find('input, select, textarea')
            .not(this)
            .first()
            .focus();
    }
});

function removeDuplicateOptions(selectId) {
    const select = document.getElementById(selectId);
    if (!select) return;

    const seen = new Set();
    const options = Array.from(select.options);

    options.forEach(option => {
        if (seen.has(option.value) && option.value !== "-1") {
            option.remove();
        } else {
            seen.add(option.value);
        }
    });
}

let _fetchSeqXhr = null;
let _fetchSeqReqId = 0;

function fetchSeqList(jobId = null, selectedSeqId = null) {
    const jobSelect = document.getElementById('barcode_job');
    const barcodeSeq = document.getElementById('barcode_seq');
    const barcodeSeqWrap = document.getElementById('barcode_select_seq');

    if (!jobSelect || !barcodeSeq) return;

    if (!jobId) jobId = jobSelect.value;

    if (_fetchSeqXhr) {
        try { _fetchSeqXhr.abort(); } catch(e){}
        _fetchSeqXhr = null;
    }

    barcodeSeq.innerHTML = '';
    const opt0 = document.createElement('option');
    opt0.value = '-1';
    opt0.textContent = "<?php echo $text['system_barcode_select_seq_m']; ?>";
    barcodeSeq.appendChild(opt0);

    if (jobId === '-1' || !jobId) {
        if (barcodeSeqWrap) barcodeSeqWrap.style.display = 'none';
        return;
    }

    if (barcodeSeqWrap) barcodeSeqWrap.style.display = 'block';

    const myReqId = ++_fetchSeqReqId;

    _fetchSeqXhr = $.ajax({
        url: '?url=Settings/GetJobSeq',
        type: 'POST',
        data: { job_id: jobId },
        success: function(response) {
            if (myReqId !== _fetchSeqReqId) return;

            let seqList = [];
            try {
                seqList = JSON.parse(response) || [];
            } catch (e) {
                console.error('Invalid JSON:', response);
                return;
            }

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
            if (myReqId === _fetchSeqReqId) _fetchSeqXhr = null;
        }
    });
}

$(function () {
    $('#barcode_job').off('change.fetchSeq').on('change.fetchSeq', function () {
        fetchSeqList(this.value, null);
    });

    fetchSeqList();
});

function delete_barcode_item() {
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

    const lang = getLang();
    const langKey = lang.includes('zh-tw') || lang.includes('hant') || lang.includes('tw') || lang.includes('hk') || lang.includes('mo')
        ? 'zh-tw'
        : (lang.includes('zh-cn') || lang.includes('hans') || lang.includes('cn') || lang.includes('sg'))
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

    try {
        if (alertify?.defaults?.glossary) {
            alertify.defaults.glossary.ok = i18n.ok;
            alertify.defaults.glossary.cancel = i18n.cancel;
            alertify.defaults.glossary.title = i18n.info;
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
                dataType: 'json',
                success: function(response) {
                    if (spinner) spinner.style.display = 'none';

                    const res_type = response?.res_type || i18n.info;
                    const res_msg = response?.res_msg || '';

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
        function onCancel() {}
    );
}
</script>
<?php endif; ?>
