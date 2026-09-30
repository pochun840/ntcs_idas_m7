<?php
$remoteSwitchLanguage = strtolower((string)($_SESSION['language'] ?? 'zh-tw'));
$remoteSwitchUiTexts = [
    'zh-tw' => [
        'invalid' => '請選擇有效的 JOB 與 SEQ。',
        'same' => '目前已是 JOB {job}／SEQ {seq}，不需要重複切換。',
        'switching' => '切換中…',
        'success' => 'JOB {job}／SEQ {seq} 切換成功。',
        'failedTitle' => '工作切換失敗',
        'controllerFailed' => '控制器未接受切換命令，請確認連線後重試。',
        'busy' => '設備正在執行另一個切換工作，請稍後再試。',
        'parameter' => 'JOB／SEQ 參數錯誤，請重新選擇。',
        'network' => '無法連線至 iDAS，請確認網路後重試。',
        'verify' => '命令已送出，但無法確認控制器目前的 JOB／SEQ。',
    ],
    'zh-cn' => [
        'invalid' => '请选择有效的 JOB 与 SEQ。',
        'same' => '目前已是 JOB {job}／SEQ {seq}，不需要重复切换。',
        'switching' => '切换中…',
        'success' => 'JOB {job}／SEQ {seq} 切换成功。',
        'failedTitle' => '工作切换失败',
        'controllerFailed' => '控制器未接受切换命令，请确认连接后重试。',
        'busy' => '设备正在执行另一个切换工作，请稍后再试。',
        'parameter' => 'JOB／SEQ 参数错误，请重新选择。',
        'network' => '无法连接至 iDAS，请确认网络后重试。',
        'verify' => '命令已发送，但无法确认控制器目前的 JOB／SEQ。',
    ],
    'en-us' => [
        'invalid' => 'Select a valid JOB and SEQ.',
        'same' => 'JOB {job} / SEQ {seq} is already active.',
        'switching' => 'Switching…',
        'success' => 'JOB {job} / SEQ {seq} switched successfully.',
        'failedTitle' => 'Job Switch Failed',
        'controllerFailed' => 'The controller did not accept the switch command. Check the connection and try again.',
        'busy' => 'Another job switch is in progress. Try again shortly.',
        'parameter' => 'The JOB / SEQ selection is invalid. Select it again.',
        'network' => 'Unable to connect to iDAS. Check the network and try again.',
        'verify' => 'The command was sent, but the current controller JOB / SEQ could not be verified.',
    ],
];
$remoteSwitchUiText = $remoteSwitchUiTexts[$remoteSwitchLanguage] ?? $remoteSwitchUiTexts['zh-tw'];
?>
<script>
window.remoteSwitchUiText = <?php echo json_encode($remoteSwitchUiText, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
window.remoteChangeJobExecute = function (options) {
    options = options || {};
    if (options.operator === true || window.remoteChangeJobBusy) return false;

    var text = window.remoteSwitchUiText || {};
    var jobSelect = document.getElementById('switch_job_id');
    var seqSelect = document.getElementById('switch_seq_id');
    var saveButton = document.getElementById('remote_change_job_save_btn');
    var jobId = Number(jobSelect ? jobSelect.value : NaN);
    var seqId = Number(seqSelect ? seqSelect.value : NaN);

    function format(message) {
        return String(message || '')
            .replace('{job}', String(jobId))
            .replace('{seq}', String(seqId));
    }
    function notifyError(message) {
        if (window.IdasNotify) IdasNotify.alert(text.failedTitle, message);
        else window.alert(message);
    }
    function finish() {
        $('#overlay').addClass('hidden');
        window.remoteChangeJobBusy = false;
        if (saveButton) {
            saveButton.disabled = false;
            if (saveButton.dataset.originalText) saveButton.textContent = saveButton.dataset.originalText;
        }
    }

    if (!Number.isInteger(jobId) || jobId < 1 ||
        !Number.isInteger(seqId) || seqId < 1) {
        notifyError(text.invalid);
        return false;
    }

    var currentJob = Number(document.getElementById('current_job_id')?.value);
    var currentSeq = Number(document.getElementById('current_seq_id')?.value);
    if (currentJob === jobId && currentSeq === seqId) {
        if (window.IdasNotify) IdasNotify.message(format(text.same));
        else window.alert(format(text.same));
        document.getElementById('SwitchJob').style.display = 'none';
        return false;
    }

    window.remoteChangeJobBusy = true;
    if (saveButton) {
        saveButton.dataset.originalText = saveButton.textContent.trim();
        saveButton.disabled = true;
        saveButton.textContent = text.switching;
    }

    $.ajax({
        url: '../api/job_switch.php',
        method: 'POST',
        contentType: 'application/json; charset=utf-8',
        dataType: 'json',
        data: JSON.stringify({ job_id: jobId, seq_id: seqId }),
        beforeSend: function () { $('#overlay').removeClass('hidden'); },
        success: function (response) {
            if (!response || response.code !== 0 || !response.data || response.data.switch_status !== 1) {
                var code = Number(response && response.code);
                notifyError(code === 3002 ? text.busy : (code === 3003 ? text.parameter : text.controllerFailed));
                finish();
                return;
            }

            window.setTimeout(function () {
                get_job({ silent: true, done: function (actual) {
                    if (actual && Number(actual.jod_id) === jobId && Number(actual.seq_id) === seqId) {
                        document.getElementById('SwitchJob').style.display = 'none';
                        if (window.IdasNotify) IdasNotify.success(format(text.success));
                    } else {
                        notifyError(text.verify);
                    }
                    finish();
                }});
            }, 1000);
        },
        error: function (xhr, status, error) {
            console.log('change job failed', status, error, xhr.responseText);
            var result = xhr.responseJSON;
            var code = Number(result && result.code);
            notifyError(code === 3002 ? text.busy : (code === 3003 ? text.parameter :
                (xhr.status === 0 ? text.network : text.controllerFailed)));
            finish();
        },
        complete: function () { $('#overlay').addClass('hidden'); }
    });
    return false;
};
</script>
<?php if (idas_is_icontroller()): ?>
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
                    <h3><?php echo $text['command']; ?></h3>
                </td>
                <td>
                    <button id="home" class="w3-btn w3-round-large" style="height:50px;padding: 0" onclick="window.location.href='./?url=Dashboards'"> <img src="../public/img/btn_home.png"></button>
                </td>
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content" style="padding: 20px 40px;">
            <div class="container" style="padding: 10px;border-radius: 5px; background-color:#F2F1F1;box-shadow: 0px 3px 8px 0px rgba(0, 0, 0, 0.2);">
                <div id="Tool_Setting">
                    <h3 style="margin: 5px 3px 10px"><b> </b></h3>
                    <div class="row border-bottom">
                        <div class="col-3" style="font-size: 18px; margin: 5px 10px 5px">
                            <label for="current_job_id"><?php echo $text['job_id']; ?></label>
                            <input type="text" id="current_job_id" name="" style="width:70%;max-width: 100px;text-align: center;" disabled>
                        </div>
                        <div class="col-3" style="font-size: 18px; margin: 5px 10px 5px">
                            <label for="current_seq_id"><?php echo $text['seq_id']; ?></label>
                            <input type="text" id="current_seq_id" name="" style="width:70%;max-width: 100px;text-align: center;" disabled>
                        </div>
                        <div class="col-3" style="font-size: 18px; margin: 5px 10px 5px">
                            <label for="current_step_id"><?php echo $text['step_id']; ?></label>
                            <input type="text" id="current_step_id" name="" style="width:70%;max-width: 100px;text-align: center;" disabled>
                        </div>
                        <div class="col my-auto" style="font-size: ; margin: 5px 5px 5px">
                            <button style=" height: 35px; " onclick="get_job()"><?php echo $text['get_job']; ?></button>
                        </div>
                        <div class="col my-auto" style="font-size: ; margin: 5px 5px 5px">
                            <button style=" height: 35px; " onclick="switch_job()"><?php echo $text['switch_job']; ?></button>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>

    <!-- Switch Job Modal -->
    <div id="SwitchJob" class="modal">
        <div class="modal-dialog modal-lg " style=" margin-top: 10%; ">
            <div class="modal-content w3-animate-zoom" style="">
                <header class="w3-container modal-header" style="background-color: #616161;color: white;">
                    <span onclick="document.getElementById('SwitchJob').style.display='none'" class="w3-display-topright" style=" margin-top: 9px; margin-right: 5px">✕</span>
                    <h2 id="modal_head"><?php echo $text['switch_job'];?></h2>
                </header>
                <div class="modal-body" style="padding-left: 3%">
                    <div class="row mb-3">
                        <div class="col-3 t1" style=" display: flex; align-items: center; "><?php echo $text['job_id'];?> :</div>
                        <div class="col-8 t2">
                            <select id="switch_job_id" class="t2 form-control input-ms" onchange="seq_list_update()">
                                <option value="-1" disabled selected><?php echo $text['system_barcode_select_job_m']; ?></option>
                                <?php
                                foreach ($data['job_list'] as $key => $value) {
                                     echo "<option value='".$value['JOBID']."' >".$value['JOBID']." ".$value['JOBname']."</option>";
                                  
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row" id="seq_id_block" style="display: none;">
                        <div class="col-3 t1" style=" display: flex; align-items: center; "><?php echo $text['seq_id'];?>:</div>
                        <div class="col-8 t2">
                            <select id="switch_seq_id" class="t2 form-control input-ms">
                                <option value="-1" disabled selected><?php echo  $calendar_lang; ?></option>
                                <?php foreach ($data['controller_list'] as $key => $value) {
                                            echo '<option value='.$value['controller_id'].'>'.$value['controller_name'].'</option>';
                                        } ?>
                            </select>
                        </div>
                    </div>
                    <input type="text" id="mode" value="" style="display: none;" disabled>
                    <input type="text" id="view_id" value="" style="display: none;" disabled>
                </div>
                <div class="w3-center modal-footer justify-content-center" style="padding: 0;background-color: #616161;color: white;">
                    <button type="button" id="remote_change_job_save_btn" class="btn btn-primary" onclick="change_job()"><?php echo $text['save'];?></button>
                </div>
            </div>
        </div>
    </div>

</div>

<script type="text/javascript">
    //for switch job button
    function switch_job(argument) {
        document.getElementById("switch_job_id").value = -1;
        document.getElementById("switch_seq_id").innerHTML = '';
        document.getElementById('SwitchJob').style.display = 'block'
    }

    function seq_list_update(defaultValue = 1) {
        const jobSelect = document.getElementById("switch_job_id");
        const seqSelect = document.getElementById("switch_seq_id");
        const seqBlock = document.getElementById("seq_id_block");

        // 基本檢查
        if (!jobSelect || !seqSelect || !seqBlock) return;

        // 清空選項
        seqSelect.innerHTML = "";

        // 準備 AJAX 請求
        const xhr = new XMLHttpRequest();
        const url = `?url=Settings/GetJobSeq_for_modbus&job_id=${encodeURIComponent(jobSelect.value)}`;
        xhr.open("GET", url, true);

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const optionsData = JSON.parse(xhr.responseText);

                        if (Array.isArray(optionsData) && optionsData.length > 0) {
                            seqBlock.style.display = "flex"; // 顯示區塊

                            optionsData.forEach((option, index) => {
                                const opt = document.createElement("option");
                                opt.value = option.SEQID;
                                opt.textContent = `Seq-${index + 1} ${option.SEQname || ''}`;
                                seqSelect.appendChild(opt);
                            });

                            // 設定預設選項
                            const targetOption = seqSelect.querySelector(`option[value="${defaultValue}"]`);
                            if (targetOption) {
                                seqSelect.value = defaultValue;
                            } else {
                                seqSelect.selectedIndex = 0; // 沒有預設值則選第一個
                            }
                        } else {
                            seqBlock.style.display = "none"; // 無資料就隱藏整塊
                        }
                    } catch (err) {
                        console.error("JSON 解析錯誤：", err);
                        seqBlock.style.display = "none";
                    }
                } else {
                    console.error("伺服器錯誤（HTTP " + xhr.status + "）");
                    seqBlock.style.display = "none";
                }
            }
        };

        xhr.send();
    }



    var remoteChangeJobBusy = false;

    function get_job(argument) {
        const opts = (argument && typeof argument === 'object') ? argument : {};
        const silent = opts.silent === true;
        const done = (typeof opts.done === 'function') ? opts.done : null;
        let actual = null;

        $.ajax({
            url: '?url=Remotes/get_current_job', // 指向服務器端檢查更新的 PHP 腳本
            method: 'GET',
            dataType: "json",
            beforeSend: function() {
                if (!silent) $('#overlay').removeClass('hidden');
            },
            success: function(response) {
                if (!silent) $('#overlay').addClass('hidden');
                // 處理服務器返回的響應
                console.log(response);

                if (!response || response.error || !response.result) {
                    if (!silent && window.alertify) {
                        IdasNotify.alert('Get Job Failed', response?.msg || response?.error || 'protocol fail');
                    }
                    return;
                }

                document.getElementById("current_job_id").value = response.result.jod_id;
                document.getElementById("current_seq_id").value = response.result.seq_id;
                document.getElementById("current_step_id").value = response.result.step_id;
                actual = response.result;
            },
            complete: function() {
                if (!silent) $('#overlay').addClass('hidden');
                if (done) done(actual);
            },
            error: function(xhr, status, error) {
                if (!silent) {
                    $('#overlay').addClass('hidden');
                    history.go(0);
                } else {
                    console.log("silent get_job failed", status, error, xhr.responseText);
                }
            }
        });
    }

    function change_job(argument) {
        return window.remoteChangeJobExecute({ operator: false });
    }

</script>

<?php if($_SESSION['privilege'] != 'admin'){ ?>
<script>
  $(document).ready(function () {
    if (typeof window.disableAllButtonsAndInputs === 'function') {
      window.disableAllButtonsAndInputs();
    }
    const homeButton = document.getElementById("home");
    const dataButton = document.getElementById("data_select");
    if (homeButton) homeButton.disabled = false;
    if (dataButton) dataButton.disabled = false;
  });
</script>
<?php } ?>

<?php require APPROOT . 'views/inc/footer.php'; ?>
<?php else: ?>
<?php 
    $idasRemoteUserLaw = (string)($_COOKIE['user_law'] ?? ($_SESSION['user_law'] ?? '1'));
    $idasRemoteIsOperator = ($idasRemoteUserLaw === '3');

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
                    <h3><?php echo $text['command']; ?></h3>
                </td>
                <td>
                    <button id="home" class="w3-btn w3-round-large" style="height:50px;padding: 0" onclick="window.location.href='./?url=Dashboards'"> <img src="../public/img/btn_home.png"></button>
                </td>
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content" style="padding: 20px 40px;">
            <div class="container" style="padding: 10px;border-radius: 5px; background-color:#F2F1F1;box-shadow: 0px 3px 8px 0px rgba(0, 0, 0, 0.2);">
                <div id="Tool_Setting">
                    <h3 style="margin: 5px 3px 10px"><b> </b></h3>
                    <div class="row border-bottom">
                        <div class="col-3" style="font-size: 18px; margin: 5px 10px 5px">
                            <label for="current_job_id"><?php echo $text['job_id']; ?></label>
                            <input type="text" id="current_job_id" name="" style="width:70%;max-width: 100px;text-align: center;" disabled>
                        </div>
                        <div class="col-3" style="font-size: 18px; margin: 5px 10px 5px">
                            <label for="current_seq_id"><?php echo $text['seq_id']; ?></label>
                            <input type="text" id="current_seq_id" name="" style="width:70%;max-width: 100px;text-align: center;" disabled>
                        </div>
                        <div class="col-3" style="font-size: 18px; margin: 5px 10px 5px">
                            <label for="current_step_id"><?php echo $text['step_id']; ?></label>
                            <input type="text" id="current_step_id" name="" style="width:70%;max-width: 100px;text-align: center;" disabled>
                        </div>
                        <div class="col my-auto" style="font-size: ; margin: 5px 5px 5px">
                            <button style=" height: 35px; " onclick="get_job()"><?php echo $text['get_job']; ?></button>
                        </div>
                        <div class="col my-auto" style="font-size: ; margin: 5px 5px 5px">
                            <button id="remote_switch_job_btn"
                                    class="<?php echo $idasRemoteIsOperator ? 'idas-operator-crud-disabled' : ''; ?>"
                                    style=" height: 35px; "
                                    <?php echo $idasRemoteIsOperator ? 'disabled aria-disabled="true" data-operator-save-lock="1"' : ''; ?>
                                    onclick="<?php echo $idasRemoteIsOperator ? 'return false' : 'switch_job()'; ?>">
                                <?php if ($idasRemoteIsOperator) { ?><span class="idas-operator-forbidden-badge" aria-hidden="true">🚫</span><?php } ?><?php echo $text['switch_job']; ?>
                            </button>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>

    <!-- Switch Job Modal -->
    <div id="SwitchJob" class="modal">
        <div class="modal-dialog modal-lg " style=" margin-top: 10%; ">
            <div class="modal-content w3-animate-zoom" style="">
                <header class="w3-container modal-header" style="background-color: #616161;color: white;">
                    <span onclick="document.getElementById('SwitchJob').style.display='none'" class="w3-display-topright" style=" margin-top: 9px; margin-right: 5px">✕</span>
                    <h2 id="modal_head"><?php echo $text['switch_job'];?></h2>
                </header>
                <div class="modal-body" style="padding-left: 3%">
                    <div class="row mb-3">
                        <div class="col-3 t1" style=" display: flex; align-items: center; "><?php echo $text['job_id'];?> :</div>
                        <div class="col-8 t2">
                            <select id="switch_job_id" class="t2 form-control input-ms" onchange="seq_list_update()">
                                <option value="-1" disabled selected><?php echo $text['system_barcode_select_job_m']; ?></option>
                                <?php
                                foreach ($data['job_list'] as $key => $value) {
                                     echo "<option value='".$value['JOBID']."' >".$value['JOBID']." ".$value['JOBname']."</option>";
                                  
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                    <div class="row" id="seq_id_block" style="display: none;">
                        <div class="col-3 t1" style=" display: flex; align-items: center; "><?php echo $text['seq_id'];?>:</div>
                        <div class="col-8 t2">
                            <select id="switch_seq_id" class="t2 form-control input-ms">
                                <option value="-1" disabled selected><?php echo  $calendar_lang; ?></option>
                                <?php foreach ($data['controller_list'] as $key => $value) {
                                            echo '<option value='.$value['controller_id'].'>'.$value['controller_name'].'</option>';
                                        } ?>
                            </select>
                        </div>
                    </div>
                    <input type="text" id="mode" value="" style="display: none;" disabled>
                    <input type="text" id="view_id" value="" style="display: none;" disabled>
                </div>
                <div class="w3-center modal-footer justify-content-center" style="padding: 0;background-color: #616161;color: white;">
                    <button type="button"
                            id="remote_change_job_save_btn"
                            class="btn btn-primary <?php echo $idasRemoteIsOperator ? 'idas-operator-crud-disabled' : ''; ?>"
                            <?php echo $idasRemoteIsOperator ? 'disabled aria-disabled="true" data-operator-save-lock="1"' : ''; ?>
                            onclick="<?php echo $idasRemoteIsOperator ? 'return false' : 'change_job()'; ?>">
                        <?php if ($idasRemoteIsOperator) { ?><span class="idas-operator-forbidden-badge" aria-hidden="true">🚫</span><?php } ?><?php echo $text['save'];?>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script type="text/javascript">
    //for switch job button
    function remoteIsOperatorLaw3() {
        return <?php echo $idasRemoteIsOperator ? 'true' : 'false'; ?>;
    }

    function switch_job(argument) {
        if (remoteIsOperatorLaw3()) return false;
        document.getElementById("switch_job_id").value = -1;
        document.getElementById("switch_seq_id").innerHTML = '';
        document.getElementById('SwitchJob').style.display = 'block'
    }

    function seq_list_update(defaultValue = 1) {
        const jobSelect = document.getElementById("switch_job_id");
        const seqSelect = document.getElementById("switch_seq_id");
        const seqBlock = document.getElementById("seq_id_block");

        // 基本檢查
        if (!jobSelect || !seqSelect || !seqBlock) return;

        // 清空選項
        seqSelect.innerHTML = "";

        // 準備 AJAX 請求
        const xhr = new XMLHttpRequest();
        const url = `?url=Settings/GetJobSeq_for_modbus&job_id=${encodeURIComponent(jobSelect.value)}`;
        xhr.open("GET", url, true);

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const optionsData = JSON.parse(xhr.responseText);

                        if (Array.isArray(optionsData) && optionsData.length > 0) {
                            seqBlock.style.display = "flex"; // 顯示區塊

                            optionsData.forEach((option, index) => {
                                const opt = document.createElement("option");
                                opt.value = option.SEQID;
                                opt.textContent = `Seq-${index + 1} ${option.SEQname || ''}`;
                                seqSelect.appendChild(opt);
                            });

                            // 設定預設選項
                            const targetOption = seqSelect.querySelector(`option[value="${defaultValue}"]`);
                            if (targetOption) {
                                seqSelect.value = defaultValue;
                            } else {
                                seqSelect.selectedIndex = 0; // 沒有預設值則選第一個
                            }
                        } else {
                            seqBlock.style.display = "none"; // 無資料就隱藏整塊
                        }
                    } catch (err) {
                        console.error("JSON 解析錯誤：", err);
                        seqBlock.style.display = "none";
                    }
                } else {
                    console.error("伺服器錯誤（HTTP " + xhr.status + "）");
                    seqBlock.style.display = "none";
                }
            }
        };

        xhr.send();
    }



    var remoteChangeJobBusy = false;

    function get_job(argument) {
        const opts = (argument && typeof argument === 'object') ? argument : {};
        const silent = opts.silent === true;
        const done = (typeof opts.done === 'function') ? opts.done : null;
        let actual = null;

        $.ajax({
            url: '?url=Remotes/get_current_job', // 指向服務器端檢查更新的 PHP 腳本
            method: 'GET',
            dataType: "json",
            beforeSend: function() {
                if (!silent) $('#overlay').removeClass('hidden');
            },
            success: function(response) {
                if (!silent) $('#overlay').addClass('hidden');
                // 處理服務器返回的響應
                console.log(response);

                if (!response || response.error || !response.result) {
                    if (!silent && window.alertify) {
                        IdasNotify.alert('Get Job Failed', response?.msg || response?.error || 'protocol fail');
                    }
                    return;
                }

                document.getElementById("current_job_id").value = response.result.jod_id;
                document.getElementById("current_seq_id").value = response.result.seq_id;
                document.getElementById("current_step_id").value = response.result.step_id;
                actual = response.result;
            },
            complete: function() {
                if (!silent) $('#overlay').addClass('hidden');
                if (done) done(actual);
            },
            error: function(xhr, status, error) {
                if (!silent) {
                    $('#overlay').addClass('hidden');
                    history.go(0);
                } else {
                    console.log("silent get_job failed", status, error, xhr.responseText);
                }
            }
        });
    }

    function change_job(argument) {
        return window.remoteChangeJobExecute({ operator: remoteIsOperatorLaw3() });
    }

</script>

<?php if($_SESSION['privilege'] != 'admin'){ ?>
<script>
  $(document).ready(function () {
    if (typeof window.disableAllButtonsAndInputs === 'function') {
      window.disableAllButtonsAndInputs();
    }
    const homeButton = document.getElementById("home");
    const dataButton = document.getElementById("data_select");
    if (homeButton) homeButton.disabled = false;
    if (dataButton) dataButton.disabled = false;
  });
</script>
<?php } ?>

<?php require APPROOT . 'views/inc/footer.php'; ?>
<?php endif; ?>
