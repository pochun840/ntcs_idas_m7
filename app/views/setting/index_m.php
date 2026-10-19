<?php if (idas_is_icontroller()): ?>

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/setting_m.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['setting'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>
    <div class="main-content">
        <div class="center-content">
            <div class="menu-button w3-center" id="button_setting">
                <button id="bnt1" name="Controller_Display" class="t4 button active" onclick="OpenButton('Controller')"><?php echo $text['controller_setting'];?></button>
                <button id="bnt2" name="System_Display" class="t4 button" onclick="OpenButton('System')"><?php echo $text['system_setting'];?></button>
                <button id="bnt3" name="Barcode_Display" class="t4 button" onclick="OpenButton('Barcode')"><?php echo $text['system_barcode_setting'] ;?></button>
                <button id="bnt4" name="Connect_Display" class="t4 button" onclick="OpenButton('Connect')"><?php echo $text['system_connect_setting'];?></button>
                <?php if (idas_network_settings_enabled()): ?>
                <button id="bnt5" name="Network_Display" class="t4 button" onclick="OpenButton('Network')"><?php echo $text['network_setting'];?></button>
                <?php endif; ?>
                <button id="bnt6" name="iDas_Display" class="t4 button" onclick="OpenButton('Update')">iDAS</button>
            </div>

        
            <!-- idas_controller OP -->
                <?php require_once '../app/views/setting/idas_controller_m.php';?>
            <!-- idas_controller ED -->


            <!-- idas_system OP -->
                <?php require_once '../app/views/setting/idas_system_m.php';?>
            <!-- idas_system ED -->
               
                                        
            <!-- idas_barcode OP -->
                <?php require_once '../app/views/setting/idas_barcode_m.php';?>
            <!-- idas_barcode ED -->

            <!-- idas_agent OP -->
                <?php require_once '../app/views/setting/idas_agent.php';?>
            <!-- idas_agent ED -->

            <?php if (idas_network_settings_enabled()): ?>
            <!-- idas_network mobile OP -->
                <?php require_once '../app/views/setting/idas_network_m.php';?>
            <!-- idas_network mobile ED -->
            <?php endif; ?>

            
            <!-- idas_update OP -->
                <?php require_once '../app/views/setting/idas_update_m.php';?>
            <!-- idas_update ED -->

                                    
        </div>
    </div>    
    
    <!-- 加载動畫 OP -->
        <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->

</div>

<script>



function edit_password(){
    var new_password = document.getElementById('new_password').value;
    var comfirm_password = document.getElementById('comfirm_password').value;

    var device_id = <?php echo $data['controller_info']['device_id'];?>;

    if(new_password == comfirm_password){
        $.ajax({
            url: "?url=Settings/edit_password",
            method: "POST",
            data:{ 
                device_id: device_id,
                new_password: new_password

            },
            success: function(response) {
                IdasNotify.alert(response);
                history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }else{
        IdasNotify.alert("請確認密碼");
        return false;
    }
}

function button_save_password_gust() {
  var device_id = <?php echo (int)$data['controller_info']['device_id'];?>;

  var pass_guest1 = document.getElementById('new_password_guest').value.trim();
  var pass_guest2 = document.getElementById('comfirm_password_guest').value.trim();

  // 必須為 4 個數字（可含前導 0）
  var pattern = /^\d{4}$/;

  if (pass_guest1 !== pass_guest2) {
    IdasNotify.alert('兩次輸入的密碼不一致');
    return;
  }
  if (!pattern.test(pass_guest1)) {
    IdasNotify.alert('密碼必須為 4 位數字（0-9）');
    return;
  }

  $.ajax({
    url: "?url=Admins/EditGuestPwd",
    method: "POST",
    data: {
      device_id: device_id,
      new_password: pass_guest1
    },
    success: function (response) {
      IdasNotify.alert(response);
      history.go(0);
    },
    error: function (xhr, status, error) {
      IdasNotify.alert('更新失敗：' + (error || status));
    }
  });
}


function OpenButton(ButtonMode){

    if (ButtonMode == "Controller")
    {
        document.getElementById('Controller_Setting').style.display = "";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt1').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");   
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "System")
    {
        document.getElementById('System_Setting').style.display = "";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt2').classList.add("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");

    }
    else if (ButtonMode == "Barcode")
    {
        document.getElementById('Barcode_Setting').style.display = "";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt3').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "Connect")
    {
        document.getElementById('Connect_Setting').style.display = "";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt4').classList.add("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "Update")
    {
        document.getElementById('iDas-Update_Setting').style.display = "";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('bnt5').classList.add("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
    }
    else
    {
        //IdasNotify.alert("Function ["+ ButtonMode +"] is under constructing ...");
    }
}

// 切換表格頁面
function changePage(tableId, direction) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tableRows = table.getElementsByTagName("tr");
    const totalRows = tableRows.length - 1; // 不算標題行

    // 更新表格的頁面狀態
    tableState[tableId] += direction;

    // 確保頁面不會超過總頁數
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    if (tableState[tableId] < 0) tableState[tableId] = 0;
    if (tableState[tableId] >= totalPages) tableState[tableId] = totalPages - 1;

    // 顯示當前頁面
    showPage(tableId, tableState[tableId]);
}

// **當頁面載入完成時，確保表格顯示第一頁的兩行資料**
document.addEventListener("DOMContentLoaded", () => {
    showPage("job_table", 0);
    showPage("connection-table", 0);
});


</script>
<?php else: ?>
<?php
$idasSettingUserLaw = (string)($_COOKIE['user_law'] ?? ($_SESSION['user_law'] ?? '1'));
$idasSettingIsOperator = ($idasSettingUserLaw === '3');
?>

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/setting_m.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['setting'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>
    <div class="main-content">
        <div class="center-content">
            <div class="menu-button w3-center" id="button_setting">
                <button id="bnt1" name="Controller_Display" class="t4 button active" onclick="OpenButton('Controller')"><?php echo $text['controller_setting'];?></button>
                <button id="bnt2" name="System_Display" class="t4 button" onclick="OpenButton('System')"><?php echo $text['system_setting'];?></button>
                <button id="bnt3" name="Barcode_Display" class="t4 button" onclick="OpenButton('Barcode')"><?php echo $text['system_barcode_setting'] ;?></button>
                <button id="bnt4" name="Connect_Display" class="t4 button" onclick="OpenButton('Connect')"><?php echo $text['system_connect_setting'];?></button>
                <?php if (idas_network_settings_enabled()): ?>
                <button id="bnt5" name="Network_Display" class="t4 button" onclick="OpenButton('Network')"><?php echo $text['network_setting'];?></button>
                <?php endif; ?>
                <button id="bnt6" name="iDas_Display" class="t4 button" onclick="OpenButton('Update')">iDAS</button>
            </div>

        
            <!-- idas_controller OP -->
                <?php require_once '../app/views/setting/idas_controller_m.php';?>
            <!-- idas_controller ED -->


            <!-- idas_system OP -->
                <?php require_once '../app/views/setting/idas_system_m.php';?>
            <!-- idas_system ED -->
               
                                        
            <!-- idas_barcode OP -->
                <?php require_once '../app/views/setting/idas_barcode_m.php';?>
            <!-- idas_barcode ED -->

            <!-- idas_agent OP -->
                <?php require_once '../app/views/setting/idas_agent.php';?>
            <!-- idas_agent ED -->

            <?php if (idas_network_settings_enabled()): ?>
            <!-- idas_network mobile OP -->
                <?php require_once '../app/views/setting/idas_network_m.php';?>
            <!-- idas_network mobile ED -->
            <?php endif; ?>

            
            <!-- idas_update OP -->
                <?php require_once '../app/views/setting/idas_update_m.php';?>
            <!-- idas_update ED -->

                                    
        </div>
    </div>    
    
    <!-- 加载動畫 OP -->
        <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->

</div>



<style>
.operator-setting-locked,
.operator-setting-locked:hover,
.operator-setting-locked:active {
    opacity: 0.55 !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
    transform: none !important;
}
</style>

<script>

/* =====================================================
   Operator setting lock
   law=3 operator：設定頁內所有操作按鈕不可點擊；上方分頁與 Home 保留可切換/返回。
   ===================================================== */
window.IDAS_SETTING_OPERATOR_LOCK = <?php echo $idasSettingIsOperator ? 'true' : 'false'; ?>;

function idasSettingLockCookie(name) {
    var target = name + '=';
    var parts = (document.cookie || '').split(';');
    for (var i = 0; i < parts.length; i++) {
        var item = parts[i].trim();
        if (item.indexOf(target) === 0) {
            try {
                return decodeURIComponent(item.substring(target.length));
            } catch (e) {
                return item.substring(target.length);
            }
        }
    }
    return '';
}

function idasSettingOperatorLockEnabled() {
    return window.IDAS_SETTING_OPERATOR_LOCK === true || String(idasSettingLockCookie('user_law') || '').trim() === '3';
}

function idasSettingLockRoots() {
    return [
        'Controller_Setting',
        'System_Setting',
        'Barcode_Setting',
        'Connect_Setting',
        'iDas-Update_Setting',
        'Upload_Setting',
        'AccountDisplay',
        'OperationAuditLogDisplay',
        'operationAuditLogDetailPanel'
    ];
}

function idasSettingElementInLockRoot(el) {
    if (!el || !el.closest) return false;
    var roots = idasSettingLockRoots();
    for (var i = 0; i < roots.length; i++) {
        var root = document.getElementById(roots[i]);
        if (root && root.contains(el)) return true;
    }
    return false;
}

function idasSettingLockTargetSelector() {
    return 'button, input[type="button"], input[type="submit"], input[type="file"], .w3-button[onclick], [role="button"]';
}

function applyIdasOperatorSettingLock() {
    if (!idasSettingOperatorLockEnabled()) return;

    var roots = idasSettingLockRoots();
    var selector = idasSettingLockTargetSelector();

    roots.forEach(function(rootId) {
        var root = document.getElementById(rootId);
        if (!root) return;

        root.querySelectorAll(selector).forEach(function(el) {
            // 只鎖設定內容，不鎖上方分頁與 Home。
            el.classList.add('operator-setting-locked');
            el.setAttribute('aria-disabled', 'true');
            el.setAttribute('data-operator-setting-locked', '1');
            el.style.pointerEvents = 'none';

            if (!el.getAttribute('title')) {
                el.setAttribute('title', 'Operator permission locked');
            }

            if ('disabled' in el) {
                el.disabled = true;
            }

            // 避免 span.w3-button 或動態按鈕仍保留 inline onclick。
            if (el.getAttribute('onclick')) {
                el.setAttribute('data-operator-original-onclick', el.getAttribute('onclick'));
                el.removeAttribute('onclick');
            }
            el.onclick = null;
        });
    });
}

// Capture 階段攔截，避免外部 JS 或 inline onclick 被繞過。
document.addEventListener('click', function(e) {
    if (!idasSettingOperatorLockEnabled()) return;
    var target = e.target && e.target.closest ? e.target.closest(idasSettingLockTargetSelector()) : null;
    if (target && idasSettingElementInLockRoot(target)) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
        return false;
    }
}, true);

// 避免按 Enter 送出設定表單。
document.addEventListener('submit', function(e) {
    if (!idasSettingOperatorLockEnabled()) return;
    if (idasSettingElementInLockRoot(e.target)) {
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
        return false;
    }
}, true);

document.addEventListener('DOMContentLoaded', function() {
    applyIdasOperatorSettingLock();
    var observerRoot = document.querySelector('.center-content') || document.body;
    if (observerRoot && window.MutationObserver) {
        var observer = new MutationObserver(function() {
            applyIdasOperatorSettingLock();
        });
        observer.observe(observerRoot, { childList: true, subtree: true });
    }
});

window.addEventListener('load', applyIdasOperatorSettingLock);




function edit_password(){
    var new_password = document.getElementById('new_password').value;
    var comfirm_password = document.getElementById('comfirm_password').value;

    var device_id = <?php echo $data['controller_info']['device_id'];?>;

    if(new_password == comfirm_password){
        $.ajax({
            url: "?url=Settings/edit_password",
            method: "POST",
            data:{ 
                device_id: device_id,
                new_password: new_password

            },
            success: function(response) {
                IdasNotify.alert(response);
                history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }else{
        IdasNotify.alert("請確認密碼");
        return false;
    }
}

function button_save_password_gust() {
  var device_id = <?php echo (int)$data['controller_info']['device_id'];?>;

  var pass_guest1 = document.getElementById('new_password_guest').value.trim();
  var pass_guest2 = document.getElementById('comfirm_password_guest').value.trim();

  // 必須為 4 個數字（可含前導 0）
  var pattern = /^\d{4}$/;

  if (pass_guest1 !== pass_guest2) {
    IdasNotify.alert('兩次輸入的密碼不一致');
    return;
  }
  if (!pattern.test(pass_guest1)) {
    IdasNotify.alert('密碼必須為 4 位數字（0-9）');
    return;
  }

  $.ajax({
    url: "?url=Admins/EditGuestPwd",
    method: "POST",
    data: {
      device_id: device_id,
      new_password: pass_guest1
    },
    success: function (response) {
      IdasNotify.alert(response);
      history.go(0);
    },
    error: function (xhr, status, error) {
      IdasNotify.alert('更新失敗：' + (error || status));
    }
  });
}


function OpenButton(ButtonMode){

    if (ButtonMode == "Controller")
    {
        document.getElementById('Controller_Setting').style.display = "";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt1').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");   
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "System")
    {
        document.getElementById('System_Setting').style.display = "";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt2').classList.add("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");

    }
    else if (ButtonMode == "Barcode")
    {
        document.getElementById('Barcode_Setting').style.display = "";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt3').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "Connect")
    {
        document.getElementById('Connect_Setting').style.display = "";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt4').classList.add("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "Update")
    {
        document.getElementById('iDas-Update_Setting').style.display = "";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('bnt5').classList.add("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
    }
    else
    {
        //IdasNotify.alert("Function ["+ ButtonMode +"] is under constructing ...");
    }
}

// 切換表格頁面
function changePage(tableId, direction) {
    const table = document.getElementById(tableId);
    if (!table) return;

    const tableRows = table.getElementsByTagName("tr");
    const totalRows = tableRows.length - 1; // 不算標題行

    // 更新表格的頁面狀態
    tableState[tableId] += direction;

    // 確保頁面不會超過總頁數
    const totalPages = Math.ceil(totalRows / rowsPerPage);
    if (tableState[tableId] < 0) tableState[tableId] = 0;
    if (tableState[tableId] >= totalPages) tableState[tableId] = totalPages - 1;

    // 顯示當前頁面
    showPage(tableId, tableState[tableId]);
}

// **當頁面載入完成時，確保表格顯示第一頁的兩行資料**
document.addEventListener("DOMContentLoaded", () => {
    showPage("job_table", 0);
    showPage("connection-table", 0);
});


</script>
<?php endif; ?>
