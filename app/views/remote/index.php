<?php if (defined('IS_ICONTROLLER') && IS_ICONTROLLER): ?>
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
                    <button type="button" class="btn btn-primary" onclick="change_job()"><?php echo $text['save'];?></button>
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
                        alertify.alert('Get Job Failed', response?.msg || response?.error || 'protocol fail');
                    }
                    return;
                }

                document.getElementById("current_job_id").value = response.result.jod_id;
                document.getElementById("current_seq_id").value = response.result.seq_id;
                document.getElementById("current_step_id").value = response.result.step_id;
            },
            complete: function() {
                if (!silent) $('#overlay').addClass('hidden');
                if (done) done();
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

        if (remoteChangeJobBusy) return;

        let job_id = document.getElementById("switch_job_id").value;
        let seq_id = document.getElementById("switch_seq_id").value;

        remoteChangeJobBusy = true;

        $.ajax({
            url: '?url=Remotes/change_job', // 指向服務器端檢查更新的 PHP 腳本
            method: 'GET',
            dataType: 'json',
            data :{ 'job_id' : job_id, 'seq_id' : seq_id },
            beforeSend: function() {
                $('#overlay').removeClass('hidden');
            },
            success: function(response) {
                console.log(response);

                if (response && response.error) {
                    const msg = response.msg || response.error || 'protocol fail';
                    if (window.alertify) {
                        alertify.alert('Change Job Failed', msg);
                    } else {
                        alert(msg);
                    }
                    return;
                }
                
                // 隱藏 SwitchJob 區塊
                document.getElementById("SwitchJob").style.display = "none";

                // 先顯示送出的值；背景再讀一次控制器目前 JOB，避免 OP 寫入失敗時畫面誤判成功。
                // 背景讀取不顯示 overlay，避免切換工作後轉圈圈出現兩次。
                document.getElementById("current_job_id").value = job_id;
                document.getElementById("current_seq_id").value = seq_id;

                if (typeof get_job === 'function') {
                    setTimeout(function(){ get_job({ silent: true }); }, 1000);
                }
            },
            complete: function(XHR, TS) {
                $('#overlay').addClass('hidden');
                remoteChangeJobBusy = false;
                XHR = null;
                console.log("执行一次"); 
            },
            error: function(xhr, status, error) {
                console.log("fail", status, error, xhr.responseText);
                if (window.alertify) {
                    alertify.alert('Change Job Failed', error || status || 'request failed');
                }
            }
        });
        
    }

</script>

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
                        alertify.alert('Get Job Failed', response?.msg || response?.error || 'protocol fail');
                    }
                    return;
                }

                document.getElementById("current_job_id").value = response.result.jod_id;
                document.getElementById("current_seq_id").value = response.result.seq_id;
                document.getElementById("current_step_id").value = response.result.step_id;
            },
            complete: function() {
                if (!silent) $('#overlay').addClass('hidden');
                if (done) done();
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

        if (remoteIsOperatorLaw3()) return false;
        if (remoteChangeJobBusy) return;

        let job_id = document.getElementById("switch_job_id").value;
        let seq_id = document.getElementById("switch_seq_id").value;

        remoteChangeJobBusy = true;

        $.ajax({
            url: '?url=Remotes/change_job', // 指向服務器端檢查更新的 PHP 腳本
            method: 'GET',
            dataType: 'json',
            data :{ 'job_id' : job_id, 'seq_id' : seq_id },
            beforeSend: function() {
                $('#overlay').removeClass('hidden');
            },
            success: function(response) {
                console.log(response);

                if (response && response.error) {
                    const msg = response.msg || response.error || 'protocol fail';
                    if (window.alertify) {
                        alertify.alert('Change Job Failed', msg);
                    } else {
                        alert(msg);
                    }
                    return;
                }
                
                // 隱藏 SwitchJob 區塊
                document.getElementById("SwitchJob").style.display = "none";

                // 先顯示送出的值；背景再讀一次控制器目前 JOB，避免 OP 寫入失敗時畫面誤判成功。
                // 背景讀取不顯示 overlay，避免切換工作後轉圈圈出現兩次。
                document.getElementById("current_job_id").value = job_id;
                document.getElementById("current_seq_id").value = seq_id;

                if (typeof get_job === 'function') {
                    setTimeout(function(){ get_job({ silent: true }); }, 1000);
                }
            },
            complete: function(XHR, TS) {
                $('#overlay').addClass('hidden');
                remoteChangeJobBusy = false;
                XHR = null;
                console.log("执行一次"); 
            },
            error: function(xhr, status, error) {
                console.log("fail", status, error, xhr.responseText);
                if (window.alertify) {
                    alertify.alert('Change Job Failed', error || status || 'request failed');
                }
            }
        });
        
    }

</script>

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
<?php endif; ?>
