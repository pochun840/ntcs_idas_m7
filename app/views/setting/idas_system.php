<?php if (idas_is_icontroller()): ?>
<div id="System_Setting" class="divMode" style="display: none;">
    <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%;"><?php echo $text['system_setting'];?></div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_sys_date'];?>(UTC):</div>
        <div class="col t2">
            <form onsubmit="change_datetime();return false;">
                <span id="currentSystemTime"></span>
                <input type="datetime-local" id="newTime" value="" size="25" required class="w3-submit w3-border">
                <input type="submit" value="<?php echo $text['save']; ?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right">
            </form>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col t1"><?php echo $text['system_export_config'];?>:</div>
        <div class="col t2">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Export_SystemConfig();"><?php echo $text['system_export_config'];?></button>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_import_config'];?>:</div>
        <div class="col t2">
            <input type="file" id="import-file-uploader" data-target="import-file-uploader" accept=".Lin" class="t3 w3-submit w3-border w3-round">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Import_SystemConfig();"><?php echo $text['system_import_config'];?></button>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_firmware_update'];?>:</div>
        <div class="col t2">
            <input type="file" id="firmware-file-uploader" data-target="firmware-file-uploader" accept=".zip" class="t3 w3-submit w3-border w3-round">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" id="firmware-update-btn" onclick="Firmware_Update();"><?php echo $text['system_firmware_update'];?></button>
        </div>        
    </div>  
    
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_diskfull_warning']; ?>:</div>
        <div class="col t2">
            <div class="progress custom-bg" style="height: 25px; width: 40%; border-radius: 10px;">
                <div id="disk-usage-bar" class="progress-bar custom-bar" style="border-radius: 10px; text-align: center; color: white;font-weight: bold;">0%</div>
            </div>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_delete_database']; ?></div>
        <div class="col t2">
            <?php 
                if (!empty($data['history_year_arr'])) {
                    foreach ($data['history_year_arr']['year'] as $key => $val) { ?>
                        <label class="year-item">
                        <input type="checkbox" 
                            class="year-checkbox zoom form-check-input"
                            name="year[]"
                            value="<?php echo htmlspecialchars($val); ?>"
                            onclick="onlyOne(this)"
                            <?php echo ($key === 0) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($val); ?>&nbsp;&nbsp;
                        </label>
                <?php }
                }
            ?>
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="deleteSelectedFiles();"><?php echo $text['delete_text']; ?></button>
        </div>     
    </div>


</div>

<script>

    var language = getCookie('language');
          console.log(language);
    const i18nAlert = {
        'en-us': {
            successTitle: "Success",
            errorTitle: "Error",
            successMsg: "Delete succeeded",
            errorMsg: "The controller's ModBus rejected the deletion"
        },
        'zh-tw': {
            successTitle: "提示",
            errorTitle: "錯誤",
            successMsg: "刪除成功",
            errorMsg: "控制器ModBus 拒絕刪除"
        },
        'zh-cn': {
            successTitle: "提示",
            errorTitle: "错误",
            successMsg: "删除成功",
            errorMsg: "控制器ModBus 拒絕刪除"
        }



    };

    (function () {
        const usedPercent = <?php echo is_numeric($data['disk_usage_percent']) ? $data['disk_usage_percent'] : 0; ?>;
        const bar = document.getElementById('disk-usage-bar');

        // 動態設百分比
        bar.style.width = usedPercent + '%';
        bar.textContent = usedPercent + '%';

        // 動態變色
        if (usedPercent < 60) {
            bar.style.backgroundColor = '#4caf50'; // 綠
        } else if (usedPercent < 80) {
            bar.style.backgroundColor = '#ff9900'; // 橘
        } else {
            bar.style.backgroundColor = '#e53935'; // 紅
        }
    })();



    function onlyOne(checkbox) {
        const checkboxes = document.getElementsByName('year[]');
        checkboxes.forEach((item) => {
            if (item !== checkbox) item.checked = false;
        });
    }


    // ===== 語系工具 =====
function resolveLang() {
  let l = (typeof language === 'string' ? language : (window.getCookie?.('language') || 'zh-tw')) || 'zh-tw';
  l = String(l).toLowerCase();
  if (l === 'en') l = 'en-us';
  return ['zh-tw', 'zh-cn', 'en-us'].includes(l) ? l : 'en-us';
}

// ===== 多語系字串 =====
const I18N_DELETE = {
  'zh-tw': {
    selectNoneTitle: '提示',
    selectNoneMsg: '請先選擇要刪除的年份',
    confirmTitle: '確認刪除',
    confirmMsg: (n) => `確定要刪除所選的 ${n} 筆年份檔案嗎？`,
    ok: '刪除',
    cancel: '取消',
    ajaxErrorPrefix: 'AJAX 錯誤：',
    successFallback: '刪除成功',
    errorFallback: '刪除失敗'
  },
  'zh-cn': {
    selectNoneTitle: '提示',
    selectNoneMsg: '请先选择要删除的年份',
    confirmTitle: '确认删除',
    confirmMsg: (n) => `确定要删除所选的 ${n} 条年份文件吗？`,
    ok: '删除',
    cancel: '取消',
    ajaxErrorPrefix: 'AJAX 错误：',
    successFallback: '删除成功',
    errorFallback: '删除失败'
  },
  'en-us': {
    selectNoneTitle: 'Notice',
    selectNoneMsg: 'Please select at least one year to delete.',
    confirmTitle: 'Confirm Deletion',
    confirmMsg: (n) => `Delete ${n} selected year file(s)?`,
    ok: 'Delete',
    cancel: 'Cancel',
    ajaxErrorPrefix: 'AJAX error: ',
    successFallback: 'Deleted successfully',
    errorFallback: 'Deletion failed'
  }
};

    // ===== 刪除功能（含確認與多語系） =====
    function deleteSelectedFiles() {
    const del_year_id = [];
    document.querySelectorAll('input[name="year[]"]:checked')
        .forEach(cb => del_year_id.push(cb.value));

    const lang = resolveLang();
    const T = I18N_DELETE[lang];

    if (del_year_id.length === 0) {
        if (window.alertify) {
        alertify.alert(T.selectNoneTitle, T.selectNoneMsg);
        } else {
        alert(T.selectNoneMsg);
        }
        return;
    }

    const doDelete = () => {
        const spinner = document.getElementById('spinner');
        if (spinner) spinner.style.display = 'block';

        $.ajax({
        url: "?url=Settings/delete_files",
        method: "POST",
        dataType: "json",     // 讓 jQuery 自動 parse JSON
        traditional: true,    // del_year_id=1&del_year_id=2...
        data: { del_year_id },

        success: function (res) {
            const lang2 = resolveLang();
            const texts = (typeof i18nAlert !== 'undefined' && i18nAlert[lang2])
            ? i18nAlert[lang2]
            : { successMsg: T.successFallback, errorMsg: T.errorFallback };

            if (res && (res.result === true || res.result === 'true')) {
            // 顯示成功訊息
            if (typeof showAlert === 'function') {
                showAlert("successTitle", res.res_msg || texts.successMsg, 2);
            } else if (window.alertify) {
                alertify.success(res.res_msg || texts.successMsg);
            }

            // 重整前打旗標：重整後自動切到 System
            sessionStorage.setItem('openTabAfterReload', 'System');

            setTimeout(() => location.reload(), 600);
            } else {
            const msg = (res && res.res_msg) || texts.errorMsg;
            if (typeof showAlert === 'function') {
                showAlert("errorTitle", msg, 3);
            } else if (window.alertify) {
                alertify.error(msg);
            } else {
                alert(msg);
            }
            }
        },

        error: function (xhr, status, error) {
            console.error("刪除失敗", error);
            if (window.alertify) {
            alertify.error(T.ajaxErrorPrefix + error);
            } else {
            alert(T.ajaxErrorPrefix + error);
            }
        },

        complete: function () {
            const spinner = document.getElementById('spinner');
            if (spinner) spinner.style.display = 'none';
        }
        });
    };

    // 確認對話框（alertify 存在就用，否則用原生 confirm）
    if (window.alertify && typeof alertify.confirm === 'function') {
        alertify
        .confirm(
            T.confirmTitle,
            T.confirmMsg(del_year_id.length),
            doDelete,
            function onCancel() {}
        )
        .set('labels', { ok: T.ok, cancel: T.cancel });
    } else {
        if (confirm(T.confirmMsg(del_year_id.length))) doDelete();
    }
    }

    // ===== 重整後自動切到 System =====
    document.addEventListener('DOMContentLoaded', () => {
    const tab = sessionStorage.getItem('openTabAfterReload');
    if (tab && typeof OpenButton === 'function') {
        OpenButton(tab); // e.g. "System"
        sessionStorage.removeItem('openTabAfterReload');
    }
    });




    function showAlert(titleKey, message, autoCloseSec = 0) {
        const texts = i18nAlert[language] || i18nAlert['en-us'];
        const title = texts[titleKey] || titleKey;

        alertify.alert(title, message).set({ closable: false });

        if (autoCloseSec > 0) {
            setTimeout(function () {
                alertify.alert().close();
            }, autoCloseSec * 1000);
        }
    }

</script>
<?php else: ?>
<div id="System_Setting" class="divMode" style="display: none;">
    <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%;"><?php echo $text['system_setting'];?></div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_sys_date'];?>(UTC):</div>
        <div class="col t2">
            <form onsubmit="change_datetime();return false;">
                <span id="currentSystemTime"></span>
                <input type="datetime-local" id="newTime" value="" size="25" required class="w3-submit w3-border">
                <input type="submit" value="<?php echo $text['save']; ?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right">
            </form>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col t1"><?php echo $text['system_export_config'];?>:</div>
        <div class="col t2">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Export_SystemConfig();"><?php echo $text['system_export_config'];?></button>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_import_config'];?>:</div>
        <div class="col t2">
            <input type="file" id="import-file-uploader" data-target="import-file-uploader" accept=".Lin" onchange="Validate_Import_Config_File(this);" title="con_&lt;SN&gt;_YYYYMMDDHHMMSS.Lin" class="t3 w3-submit w3-border w3-round">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Import_SystemConfig();"><?php echo $text['system_import_config'];?></button>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_firmware_update'];?>:</div>
        <div class="col t2">
            <input type="file" id="firmware-file-uploader" data-target="firmware-file-uploader" accept=".zip" class="t3 w3-submit w3-border w3-round">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Firmware_Update();"><?php echo $text['system_firmware_update'];?></button>
        </div>        
    </div>  
    
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_diskfull_warning']; ?>:</div>
        <div class="col t2">
            <div class="progress custom-bg" style="height: 25px; width: 40%; border-radius: 10px;">
                <div id="disk-usage-bar" class="progress-bar custom-bar" style="border-radius: 10px; text-align: center; color: white;font-weight: bold;">0%</div>
            </div>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_delete_database']; ?></div>
        <div class="col t2">
            <?php 
                if (!empty($data['history_year_arr'])) {
                    foreach ($data['history_year_arr']['year'] as $key => $val) { ?>
                        <label class="year-item">
                        <input type="checkbox" 
                            class="year-checkbox zoom form-check-input"
                            name="year[]"
                            value="<?php echo htmlspecialchars($val); ?>"
                            onclick="onlyOne(this)"
                            <?php echo ($key === 0) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($val); ?>&nbsp;&nbsp;
                        </label>
                <?php }
                }
            ?>
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="deleteSelectedFiles();"><?php echo $text['delete_text']; ?></button>
        </div>     
    </div>


</div>

<script>

    var language = getCookie('language');
          console.log(language);
    const i18nAlert = {
        'en-us': {
            successTitle: "Success",
            errorTitle: "Error",
            successMsg: "Delete succeeded",
            errorMsg: "The controller's ModBus rejected the deletion"
        },
        'zh-tw': {
            successTitle: "提示",
            errorTitle: "錯誤",
            successMsg: "刪除成功",
            errorMsg: "控制器ModBus 拒絕刪除"
        },
        'zh-cn': {
            successTitle: "提示",
            errorTitle: "错误",
            successMsg: "删除成功",
            errorMsg: "控制器ModBus 拒絕刪除"
        }



    };

    (function () {
        const usedPercent = <?php echo is_numeric($data['disk_usage_percent']) ? $data['disk_usage_percent'] : 0; ?>;
        const bar = document.getElementById('disk-usage-bar');

        // 動態設百分比
        bar.style.width = usedPercent + '%';
        bar.textContent = usedPercent + '%';

        // 動態變色
        if (usedPercent < 60) {
            bar.style.backgroundColor = '#4caf50'; // 綠
        } else if (usedPercent < 80) {
            bar.style.backgroundColor = '#ff9900'; // 橘
        } else {
            bar.style.backgroundColor = '#e53935'; // 紅
        }
    })();



    function onlyOne(checkbox) {
        const checkboxes = document.getElementsByName('year[]');
        checkboxes.forEach((item) => {
            if (item !== checkbox) item.checked = false;
        });
    }


    // ===== 語系工具 =====
function resolveLang() {
  let l = (typeof language === 'string' ? language : (window.getCookie?.('language') || 'zh-tw')) || 'zh-tw';
  l = String(l).toLowerCase();
  if (l === 'en') l = 'en-us';
  return ['zh-tw', 'zh-cn', 'en-us'].includes(l) ? l : 'en-us';
}

// ===== 多語系字串 =====
const I18N_DELETE = {
  'zh-tw': {
    selectNoneTitle: '提示',
    selectNoneMsg: '請先選擇要刪除的年份',
    confirmTitle: '確認刪除',
    confirmMsg: (n) => `確定要刪除所選的 ${n} 筆年份檔案嗎？`,
    ok: '刪除',
    cancel: '取消',
    ajaxErrorPrefix: 'AJAX 錯誤：',
    successFallback: '刪除成功',
    errorFallback: '刪除失敗'
  },
  'zh-cn': {
    selectNoneTitle: '提示',
    selectNoneMsg: '请先选择要删除的年份',
    confirmTitle: '确认删除',
    confirmMsg: (n) => `确定要删除所选的 ${n} 条年份文件吗？`,
    ok: '删除',
    cancel: '取消',
    ajaxErrorPrefix: 'AJAX 错误：',
    successFallback: '删除成功',
    errorFallback: '删除失败'
  },
  'en-us': {
    selectNoneTitle: 'Notice',
    selectNoneMsg: 'Please select at least one year to delete.',
    confirmTitle: 'Confirm Deletion',
    confirmMsg: (n) => `Delete ${n} selected year file(s)?`,
    ok: 'Delete',
    cancel: 'Cancel',
    ajaxErrorPrefix: 'AJAX error: ',
    successFallback: 'Deleted successfully',
    errorFallback: 'Deletion failed'
  }
};

    // ===== 刪除功能（含確認與多語系） =====
    function deleteSelectedFiles() {
    const del_year_id = [];
    document.querySelectorAll('input[name="year[]"]:checked')
        .forEach(cb => del_year_id.push(cb.value));

    const lang = resolveLang();
    const T = I18N_DELETE[lang];

    if (del_year_id.length === 0) {
        if (window.alertify) {
        alertify.alert(T.selectNoneTitle, T.selectNoneMsg);
        } else {
        alert(T.selectNoneMsg);
        }
        return;
    }

    const doDelete = () => {
        const spinner = document.getElementById('spinner');
        if (spinner) spinner.style.display = 'block';

        $.ajax({
        url: "?url=Settings/delete_files",
        method: "POST",
        dataType: "json",     // 讓 jQuery 自動 parse JSON
        traditional: true,    // del_year_id=1&del_year_id=2...
        data: { del_year_id },

        success: function (res) {
            const lang2 = resolveLang();
            const texts = (typeof i18nAlert !== 'undefined' && i18nAlert[lang2])
            ? i18nAlert[lang2]
            : { successMsg: T.successFallback, errorMsg: T.errorFallback };

            if (res && (res.result === true || res.result === 'true')) {
            // 顯示成功訊息
            if (typeof showAlert === 'function') {
                showAlert("successTitle", res.res_msg || texts.successMsg, 2);
            } else if (window.alertify) {
                alertify.success(res.res_msg || texts.successMsg);
            }

            // 重整前打旗標：重整後自動切到 System
            sessionStorage.setItem('openTabAfterReload', 'System');

            setTimeout(() => location.reload(), 600);
            } else {
            const msg = (res && res.res_msg) || texts.errorMsg;
            if (typeof showAlert === 'function') {
                showAlert("errorTitle", msg, 3);
            } else if (window.alertify) {
                alertify.error(msg);
            } else {
                alert(msg);
            }
            }
        },

        error: function (xhr, status, error) {
            console.error("刪除失敗", error);
            if (window.alertify) {
            alertify.error(T.ajaxErrorPrefix + error);
            } else {
            alert(T.ajaxErrorPrefix + error);
            }
        },

        complete: function () {
            const spinner = document.getElementById('spinner');
            if (spinner) spinner.style.display = 'none';
        }
        });
    };

    // 確認對話框（alertify 存在就用，否則用原生 confirm）
    if (window.alertify && typeof alertify.confirm === 'function') {
        alertify
        .confirm(
            T.confirmTitle,
            T.confirmMsg(del_year_id.length),
            doDelete,
            function onCancel() {}
        )
        .set('labels', { ok: T.ok, cancel: T.cancel });
    } else {
        if (confirm(T.confirmMsg(del_year_id.length))) doDelete();
    }
    }

    // ===== 重整後自動切到 System =====
    document.addEventListener('DOMContentLoaded', () => {
    const tab = sessionStorage.getItem('openTabAfterReload');
    if (tab && typeof OpenButton === 'function') {
        OpenButton(tab); // e.g. "System"
        sessionStorage.removeItem('openTabAfterReload');
    }
    });




    function showAlert(titleKey, message, autoCloseSec = 0) {
        const texts = i18nAlert[language] || i18nAlert['en-us'];
        const title = texts[titleKey] || titleKey;

        alertify.alert(title, message).set({ closable: false });

        if (autoCloseSec > 0) {
            setTimeout(function () {
                alertify.alert().close();
            }, autoCloseSec * 1000);
        }
    }

</script>
<?php endif; ?>
