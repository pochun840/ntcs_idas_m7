<div id="System_Setting" class="divMode_1" style="display: none">
    <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_setting'];?></div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_sys_date'];?>(UTC):</div>
        <div class="col t2">
            <form style="margin: 3px 0px">
                <span id="currentSystemTime"></span>&nbsp;
                <input type="datetime-local" id="newTime" value="" required class="t3 w3-submit w3-border w3-round">
                <input type="button" value="<?php echo $text['save'];?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right" onclick="time_save()">
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
            <input type="file" id="import-file-uploader" data-target="import-file-uploader" accept=".cfg" class="t3 w3-submit w3-border w3-round">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Import_SystemConfig();"><?php echo $text['system_import_config'];?></button>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_firmware_update'];?>:</div>
        <div class="col t2">
            <input type="file" id="firmware-file-uploader" data-target="firmware-file-uploader" accept=".cfg" class="t3 w3-submit w3-border w3-round">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Firmware_Update();"><?php echo $text['system_firmware_update'];?></button>
        </div>        
    </div>  
    
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_diskfull_warning']; ?>:</div>
        <div class="col t2">
            <div class="progress" style="height: 20px; width: 40%; background-color: #eee; border-radius: 10px;">
                <div id="disk-usage-bar" class="progress-bar" style="width: 0%; height: 100%; border-radius: 10px; text-align: center; color: white;font-weight: bold;">0%</div>
            </div>
        </div>
    </div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_delete_database']; ?></div>
        <div class="col t2">
            <?php 
                if (!empty($data['history_year_arr'])) {
                    foreach ($data['history_year_arr']['year'] as $key => $val) { ?>
                        <input type="checkbox"
                            name="year[]"
                            value="<?php echo htmlspecialchars($val); ?>"
                            onclick="onlyOne(this)"
                            <?php echo ($key === 0) ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($val); ?>&nbsp;&nbsp;
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



    function deleteSelectedFiles() {
        var del_year_id = [];
        var checkboxes = document.querySelectorAll('input[name="year[]"]:checked');

        checkboxes.forEach(function (checkbox) {
            del_year_id.push(checkbox.value);
        });

        if (del_year_id.length > 0) {

            document.getElementById('spinner').style.display = 'block';

            $.ajax({
                url: "?url=Settings/delete_files",
                method: "POST",
                data: { 
                    del_year_id: del_year_id
                },
                success: function(response) {
                    var res = JSON.parse(response);
                    const texts = i18nAlert[language] || i18nAlert['en-us'];

                    if (res.result === true) {
                        showAlert("successTitle", res.res_msg || texts.successMsg, 3);
                    } else {
                        showAlert("errorTitle", res.res_msg || texts.errorMsg, 3);
                    }

                },
                error: function(xhr, status, error) {
                    console.error("刪除失敗", error);
                    alertify.error("AJAX 錯誤：" + error);
                }
            });
        } else {
            alert("請先選擇要刪除的年份");
        }

        document.getElementById('spinner').style.display = 'none';
    }


    function deleteSelectedFiles(){

        var del_year_id = [];
        var checkboxes = document.querySelectorAll('input[name="year[]"]:checked');
        var language = getCookie('language');

        checkboxes.forEach(function (checkbox) {
            del_year_id.push(checkbox.value);
        });

        if (del_year_id.length > 0) {
            $.ajax({
                url: "?url=Settings/delete_files",
                method: "POST",
                data: { 
                    del_year_id: del_year_id
                },
               success: function(response) {
                    var res = JSON.parse(response);
                    if (res.result === true) {
                        var res = JSON.parse(response);
                        const texts = i18nAlert[language] || i18nAlert['en-us'];

                        if (res.result === true) {
                            showAlert("successTitle", res.res_msg || texts.successMsg, 3);
                        } else {
                            showAlert("errorTitle", res.res_msg || texts.errorMsg, 3);
                        }

                        // 將已勾選的 checkbox 與其旁邊的年份一起從畫面上移除
                        const checkboxes = document.querySelectorAll('input[name="year[]"]:checked');
                        checkboxes.forEach(function (checkbox) {
                            // 移除 checkbox 和其後的文字節點與空白
                            const labelText = checkbox.nextSibling;
                            if (labelText && labelText.nodeType === Node.TEXT_NODE) {
                                labelText.remove();
                            }
                            checkbox.remove();
                        });

                    } else {
                        //alertify.error("刪除失敗：" + (res.msg || "未知錯誤"));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("刪除失敗", error);
                    alertify.error("AJAX 錯誤：" + error);
                }
            });
        } else {
            alert("請先選擇要刪除的年份");
        }
    }

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