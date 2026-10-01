<?php
$remoteSwitchLanguage = strtolower((string)($_SESSION['language'] ?? 'zh-tw'));
$remoteSwitchUiTexts = [
    'zh-tw' => [
        'close' => '關閉',
        'notice' => '工作操作',
        'reading' => '正在讀取控制器…',
        'sending' => '正在送出切換命令並等待確認…',
        'checking' => '正在重新讀取 JOB／SEQ…',
        'seconds' => '已等待 {seconds} 秒',
        'readSuccess' => '已讀取 JOB {job}／SEQ {seq}。',
        'readFailed' => '讀取失敗，請確認連線後重新讀取。',
        'stale' => '尚未更新：以下數值為上次讀取結果。',
        'readAgain' => '重新讀取',
        'retry' => '重試切換',
        'fixed' => '固定顯示',
        'readTitle' => '讀取工作失敗',
        'invalid' => '請選擇有效的 JOB 與 SEQ。',
        'same' => '目前已是 JOB {job}／SEQ {seq}，不需要重複切換。',
        'switching' => '切換中…',
        'success' => 'JOB {job}／SEQ {seq} 切換成功。',
        'failedTitle' => '工作切換失敗',
        'controllerFailed' => '無法確認切換成功，請先重新讀取目前工作，再決定是否重試。',
        'busy' => '設備正在執行另一個切換工作，請稍後再試。',
        'parameter' => 'JOB／SEQ 參數錯誤，請重新選擇。',
        'network' => '無法連線至 iDAS，請確認網路後重試。',
        'verify' => '命令已送出，但無法確認控制器目前的 JOB／SEQ。',
    ],
    'zh-cn' => [
        'close' => '关闭',
        'notice' => '工作操作',
        'reading' => '正在读取控制器…',
        'sending' => '正在发送切换命令并等待确认…',
        'checking' => '正在重新读取 JOB／SEQ…',
        'seconds' => '已等待 {seconds} 秒',
        'readSuccess' => '已读取 JOB {job}／SEQ {seq}。',
        'readFailed' => '读取失败，请确认连接后重新读取。',
        'stale' => '尚未更新：以下数值为上次读取结果。',
        'readAgain' => '重新读取',
        'retry' => '重试切换',
        'fixed' => '固定显示',
        'readTitle' => '读取工作失败',
        'invalid' => '请选择有效的 JOB 与 SEQ。',
        'same' => '目前已是 JOB {job}／SEQ {seq}，不需要重复切换。',
        'switching' => '切换中…',
        'success' => 'JOB {job}／SEQ {seq} 切换成功。',
        'failedTitle' => '工作切换失败',
        'controllerFailed' => '无法确认切换成功，请先重新读取目前工作，再决定是否重试。',
        'busy' => '设备正在执行另一个切换工作，请稍后再试。',
        'parameter' => 'JOB／SEQ 参数错误，请重新选择。',
        'network' => '无法连接至 iDAS，请确认网络后重试。',
        'verify' => '命令已发送，但无法确认控制器目前的 JOB／SEQ。',
    ],
    'en-us' => [
        'close' => 'Close',
        'notice' => 'Job operation',
        'reading' => 'Reading the controller…',
        'sending' => 'Sending the switch command and waiting for verification…',
        'checking' => 'Reading JOB / SEQ again…',
        'seconds' => 'Waiting {seconds}s',
        'readSuccess' => 'Read JOB {job} / SEQ {seq}.',
        'readFailed' => 'Unable to read the controller. Check the connection and read again.',
        'stale' => 'Not updated: values below are from the previous read.',
        'readAgain' => 'Read again',
        'retry' => 'Retry switch',
        'fixed' => 'Fixed display',
        'readTitle' => 'Job Read Failed',
        'invalid' => 'Select a valid JOB and SEQ.',
        'same' => 'JOB {job} / SEQ {seq} is already active.',
        'switching' => 'Switching…',
        'success' => 'JOB {job} / SEQ {seq} switched successfully.',
        'failedTitle' => 'Job Switch Failed',
        'controllerFailed' => 'Unable to confirm the switch. Read the current job before retrying.',
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

window.remoteJobUi = {
    busy: false, stale: true, timer: null, saved: [],
    panel: function () {
        var panel = document.getElementById('remote_job_status');
        if (!panel) {
            var popup = document.createElement('div');
            popup.id = 'remote_job_popup';
            popup.style.cssText = 'position:fixed;inset:0;z-index:10000;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.45);padding:20px;box-sizing:border-box;';
            panel = document.createElement('div');
            panel.id = 'remote_job_status';
            panel.tabIndex = -1;
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-modal', 'true');
            panel.setAttribute('aria-label', remoteSwitchUiText.notice);
            panel.className = 'idas-notification-card is-info';
            panel.style.cssText = 'max-width:430px;max-height:85vh;overflow:auto;';
            popup.appendChild(panel);
            document.body.appendChild(popup);
        }
        var popup = document.getElementById('remote_job_popup');
        if (popup.style.display === 'none') this.returnFocus = document.activeElement;
        popup.style.display = 'flex';
        return panel;
    },
    close: function () {
        if (this.busy) return;
        clearTimeout(this.autoCloseTimer); this.autoCloseTimer = null;
        var popup = document.getElementById('remote_job_popup');
        if (popup) popup.style.display = 'none';
        if (this.returnFocus && this.returnFocus.isConnected && !this.returnFocus.disabled) this.returnFocus.focus();
    },
    show: function (message, failed, retry) {
        clearTimeout(this.autoCloseTimer); this.autoCloseTimer = null;
        var panel = this.panel();
        panel.replaceChildren();
        panel.className = 'idas-notification-card ' + (failed ? 'is-error' : (this.busy ? 'is-info' : 'is-success'));
        var icon = document.createElement('div');
        icon.className = 'idas-notification-icon';
        icon.setAttribute('aria-hidden', 'true');
        icon.innerHTML = failed ? '<svg viewBox="0 0 24 24"><path d="M12 8v5m0 3h.01"/><path d="M10.3 4.4 3.2 17a2 2 0 0 0 1.8 3h14a2 2 0 0 0 1.8-3L13.7 4.4a2 2 0 0 0-3.4 0Z"/></svg>' : (this.busy ? '<svg viewBox="0 0 24 24"><path d="M12 11v6m0-10h.01"/><circle cx="12" cy="12" r="9"/></svg>' : '<svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></svg>');
        panel.appendChild(icon);
        var line = document.createElement('div');
        line.setAttribute('role', 'status');
        line.setAttribute('aria-live', 'polite');
        line.className = 'idas-notification-title';
        line.textContent = message;
        panel.appendChild(line);
        if (failed) {
            var hint = document.createElement('div');
            hint.className = 'idas-notification-message';
            hint.textContent = remoteSwitchUiText.stale;
            panel.appendChild(hint);
            var read = document.createElement('button');
            read.className = 'btn btn-default';
            read.type = 'button'; read.textContent = remoteSwitchUiText.readAgain;
            read.onclick = function () { get_job(); };
            read.style.margin = '8px 8px 0 0'; panel.appendChild(read);
            if (retry) {
                var button = document.createElement('button');
                button.className = 'btn btn-default';
                button.type = 'button'; button.textContent = remoteSwitchUiText.retry;
                button.onclick = function () { change_job(); };
                panel.appendChild(button);
            }
        }
        panel.focus();
        if (!this.busy) {
            var self = this;
            this.autoCloseTimer = setTimeout(function () { self.close(); }, failed ? 6000 : 3000);
        }
    },
    start: function (message) {
        clearTimeout(this.autoCloseTimer); this.autoCloseTimer = null;
        this.busy = true; this.started = Date.now(); this.stage = message;
        this.saved = Array.from(document.querySelectorAll('button[onclick*="get_job"],button[onclick*="switch_job"],#remote_change_job_save_btn,#switch_job_id,#switch_seq_id,#remote_job_status button')).map(function (node) {
            var previous = node.disabled; node.disabled = true; return [node, previous];
        });
        var self = this;
        this.tick();
        this.timer = setInterval(function () { self.tick(); }, 1000);
    },
    tick: function () {
        var seconds = Math.floor((Date.now() - this.started) / 1000);
        this.show(this.stage + ' · ' + remoteSwitchUiText.seconds.replace('{seconds}', seconds), false);
    },
    stop: function () {
        clearInterval(this.timer); this.timer = null; this.busy = false;
        this.saved.forEach(function (item) { item[0].disabled = item[1]; });
        this.saved = [];
    },
    markStale: function () {
        this.stale = true;
        ['current_job_id', 'current_seq_id'].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) { node.style.color = '#94652b'; node.title = remoteSwitchUiText.stale; }
        });
    },
    markFresh: function () {
        this.stale = false;
        ['current_job_id', 'current_seq_id'].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) { node.style.color = ''; node.title = ''; }
        });
    }
};
window.remoteReadJob = function (argument) {
    var opts = argument && typeof argument === 'object' ? argument : {};
    var internal = opts.silent === true && window.remoteChangeJobBusy === true;
    if (remoteJobUi.busy && !internal) return false;
    var actual = null;
    if (!internal) remoteJobUi.start(remoteSwitchUiText.reading);
    $.ajax({
        url: '?url=Remotes/get_current_job', method: 'GET', dataType: 'json',
        cache: false, timeout: 15000,
        success: function (response) {
            var data = response && !response.error && response.result;
            if (!data || !Number.isInteger(Number(data.jod_id)) || Number(data.jod_id) < 1 ||
                !Number.isInteger(Number(data.seq_id)) || Number(data.seq_id) < 1) return;
            document.getElementById('current_job_id').value = data.jod_id;
            document.getElementById('current_seq_id').value = data.seq_id;
            document.getElementById('current_step_id').value = 1;
            actual = data;
            remoteJobUi.markFresh();
        },
        error: function (xhr, status, error) { console.log('get_job failed', status, error, xhr.responseText); },
        complete: function () {
            if (!internal) {
                remoteJobUi.stop();
                if (actual) remoteJobUi.show(remoteSwitchUiText.readSuccess.replace('{job}', actual.jod_id).replace('{seq}', actual.seq_id), false);
                else { remoteJobUi.markStale(); remoteJobUi.show(remoteSwitchUiText.readFailed, true); }
            }
            if (typeof opts.done === 'function') opts.done(actual);
        }
    });
    return false;
};
document.addEventListener('keydown', function (event) {
    var popup = document.getElementById('remote_job_popup');
    if (!popup || popup.style.display === 'none') return;
    if (event.key === 'Escape') { event.preventDefault(); event.stopPropagation(); remoteJobUi.close(); }
    if (event.key === 'Tab') {
        var panel = document.getElementById('remote_job_status');
        var buttons = Array.from(panel.querySelectorAll('button:not(:disabled)'));
        if (!buttons.length) { event.preventDefault(); panel.focus(); return; }
        var first = buttons[0], last = buttons[buttons.length - 1];
        if (event.shiftKey && (document.activeElement === first || !panel.contains(document.activeElement))) {
            event.preventDefault(); last.focus();
        } else if (!event.shiftKey && (document.activeElement === last || !panel.contains(document.activeElement))) {
            event.preventDefault(); first.focus();
        }
    }
}, true);

window.remoteChangeJobExecute = function (options) {
    options = options || {};
    if (options.operator === true || window.remoteChangeJobBusy || remoteJobUi.busy) return false;

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
        remoteJobUi.markStale();
        remoteJobUi.show(message, true, true);
    }
    function finish() {
        remoteJobUi.stop();
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
    if (!remoteJobUi.stale && currentJob === jobId && currentSeq === seqId) {
        remoteJobUi.show(format(text.same), false);
        document.getElementById('SwitchJob').style.display = 'none';
        return false;
    }

    remoteJobUi.start(text.sending);
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
        timeout: 20000,
        success: function (response) {
            if (!response || response.code !== 0 || !response.data || response.data.switch_status !== 1) {
                var code = Number(response && response.code);
                finish();
                notifyError(code === 3002 ? text.busy : (code === 3003 ? text.parameter : text.controllerFailed));
                return;
            }

            remoteJobUi.stage = text.checking;
            remoteJobUi.tick();
            window.setTimeout(function () {
                get_job({ silent: true, done: function (actual) {
                    finish();
                    if (actual && Number(actual.jod_id) === jobId && Number(actual.seq_id) === seqId) {
                        document.getElementById('SwitchJob').style.display = 'none';
                        remoteJobUi.show(format(text.success), false);
                    } else {
                        notifyError(text.verify);
                    }
                }});
            }, 1000);
        },
        error: function (xhr, status, error) {
            console.log('change job failed', status, error, xhr.responseText);
            finish();
            var result = xhr.responseJSON;
            var code = Number(result && result.code);
            notifyError(code === 3002 ? text.busy : (code === 3003 ? text.parameter :
                (xhr.status === 0 ? text.network : text.controllerFailed)));
        }
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
                            <input type="text" id="current_step_id" value="1" name="" style="width:70%;max-width: 100px;text-align: center;" disabled>
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
        return window.remoteReadJob(argument);
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
                            <input type="text" id="current_step_id" value="1" name="" style="width:70%;max-width: 100px;text-align: center;" disabled>
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
        return window.remoteReadJob(argument);
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
