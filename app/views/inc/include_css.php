<?php 
// 共用：條件式載入 CSS / JS（根據 URL 第一層）
function include_asset($part, $fileName) {
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $queryStringWithoutUrl = str_replace('url=', '', $queryString);
    $parts = explode('/', $queryStringWithoutUrl);
    $firstPart = $parts[0] ?? '';
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

    //特別排除 Sequences 頁面載入 sequences.js（強制不要載）
    if (!($firstPart === 'Sequences' && $fileName === 'sequences.js')) {
        if ($firstPart === $part) {
            $path = ($extension === 'css') ? 'css' : 'js';
            $tag = ($extension === 'css')
                ? "<link rel=\"stylesheet\" href=\"" . URLROOT . "$path/$fileName?v=" . ASSET_VERSION . "\">"
                : "<script src=\"" . URLROOT . "$path/$fileName?v=" . ASSET_VERSION . "\"></script>";
            echo $tag . "\n";
        }
    }

    //額外條件：若網址是 Sequences，就強制載入 seq.js
    if ($firstPart === 'Sequences' && $fileName === 'sequences.js') {
        echo "<script src=\"" . URLROOT . "js/seq.js?v=" . ASSET_VERSION . "\"></script>\n";
    }
}

function include_css() {
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $routeParts = explode('/', str_replace('url=', '', $queryString));
    $controller = $routeParts[0] ?? '';
    $action = $routeParts[1] ?? '';

    $isMobile = isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $_SERVER['HTTP_USER_AGENT']);

    // 模組對應表
    $cssMap = [
        'Jobs'      => ['pc' => 'jobs.css',    'mobile' => 'jobs_m.css'],
        'Sequences' => ['pc' => 'seq.css',     'mobile' => 'seq_m.css'],
        'Step'      => ['pc' => 'step.css',    'mobile' => 'step_m.css'],
        'Inputs'    => ['pc' => 'input.css',   'mobile' => 'input_m.css'],
        'Outputs'   => ['pc' => 'output.css',  'mobile' => 'output_m.css'],
        'Settings'  => ['pc' => 'setting.css', 'mobile' => 'setting_m.css'],
        'Tools'     => ['pc' => 'tools.css'],
        'Data'      => ['pc' => 'data.css'],
        'Agents'    => ['pc' => 'agent.css'],
        'Remotes'   => ['pc' => 'jobs.css'],
        'Customize' => ['pc' => 'jobs.css'],
    ];

    $cssFile = null;

    // 特例處理 - Dashboards 模組
    if ($controller === 'Dashboards') {
        if ($action === 'index') {
            $cssFile = 'main.css';
        } elseif ($action === 'operation') {
            $cssFile = $isMobile ? 'operation_m.css' : 'operation.css';
        } else {
            $cssFile = 'tcc_main.css'; // fallback
        }

    // 特例處理 - In 模組
    } elseif ($controller === 'In' || $controller === 'Logins' ||  $controller === 'Login') {
        $cssFile = 'main.css';

    // 一般對應
    } elseif (isset($cssMap[$controller])) {
        $cssFile = $isMobile && isset($cssMap[$controller]['mobile']) 
            ? $cssMap[$controller]['mobile'] 
            : $cssMap[$controller]['pc'];
    }

    // 輸出 <link>
    if ($cssFile) {
        echo '<link rel="stylesheet" href="' . URLROOT . 'css/' . $cssFile . '?v=' . ASSET_VERSION . '" type="text/css">' . "\n";
    }
}



?>

    <!-- ================== 基礎 JS ================== -->
    <script src="<?php echo URLROOT; ?>js/jquery-3.7.1.min.js?v=<?php echo ASSET_VERSION; ?>"></script>

    <!-- ================== 基礎 CSS ================== -->
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/jquery_data_Tables.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/datatables.min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/w3.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/font-awesome.min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/flatpickr.min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/alertify_min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/default_min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/footer.css?v=<?php echo ASSET_VERSION; ?>">
    <?php
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $route = explode('/', str_replace('url=', '', $queryString))[0] ?? '';

        // 檢查是否為行動裝置
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent);

        // 不在 Inputs 或 Outputs 頁面時，根據裝置載入對應的 CSS
        if (!in_array($route, ['Inputs', 'Outputs'])) {
            $cssFile = $isMobile ? 'share_m.css' : 'share.css';
            echo '<link rel="stylesheet" href="' . URLROOT . 'css/' . $cssFile . '?v=' . ASSET_VERSION . '">' . "\n";
        }
    ?>


    
    <!-- ================== 模組 CSS 動態載入 ================== -->
    <?php echo include_css();?>


    <!-- ================== 共用 JS ================== -->
    <script src="<?php echo URLROOT; ?>js/all.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/echarts_min.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/jquery_data_Tables.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/alertify_min.js?v=<?php echo ASSET_VERSION; ?>"></script>



    <!-- ================== 模組 JS 動態載入 ================== -->
    <?php 
    $modules = ['Inputs', 'Outputs', 'Jobs', 'Data', 'Sequences', 'Step', 'Settings'];
    foreach ($modules as $mod) {
        include_asset($mod, strtolower($mod) . '.js');
    }
    ?>

    <!-- ================== 其他工具 JS ================== -->
    <script src="<?php echo URLROOT; ?>js/flatpickr.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/flatpickr_zh-tw.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/tcc_data.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/jszip.js?v=<?php echo ASSET_VERSION; ?>"></script>


    <!-- ================== 其他工具 JS ================== -->

   <script>
/* ============================================================
   Device ID 自動偵測 + 5秒延遲跳視窗 (FINAL)
   ============================================================ */

const WAIT_MS = 5000; // ⭐ 等待5秒再跳視窗

/* ---------------- cookie ---------------- */
function getCookieSafe(name){
    try{
        const m=document.cookie.match(new RegExp('(?:^|; )'+name+'=([^;]*)'));
        return m?decodeURIComponent(m[1]):null;
    }catch(e){return null;}
}

/* ---------------- 語系 ---------------- */
function getLangCode(){
    let lang=(getCookieSafe("language")||"zh-tw").toLowerCase();
    if(lang==="en") lang="en-us";
    if(!["en-us","zh-tw","zh-cn"].includes(lang)) lang="en-us";
    return lang;
}

function getDeviceReloadMessage(newId){
    const lang=getLangCode();
    if(lang==="zh-tw") return "控制器裝置編號已變更為 "+newId+"，是否重新整理畫面";
    if(lang==="zh-cn") return "控制器设备编号已变更为 "+newId+"，是否重新刷新页面";
    return "Controller device ID has changed to "+newId+". Reload now?";
}

function getAlertifyUiText(){
    const lang=getLangCode();
    if(lang==="zh-tw") return {title:"提示",ok:"確定"};
    if(lang==="zh-cn") return {title:"提示",ok:"确定"};
    return {title:"Notice",ok:"OK"};
}

/* ---------------- 倒數狀態 (跨刷新保存) ---------------- */
function markDeviceChanged(id){
    const prev=localStorage.getItem("device_changed_value");

    // ⭐ ID不同 → 重新開始倒數
    if(prev!==String(id)){
        localStorage.setItem("device_changed_value",id);
        localStorage.setItem("device_changed_time",Date.now());
    }
}

function clearDeviceChanged(){
    localStorage.removeItem("device_changed_time");
    localStorage.removeItem("device_changed_value");
}

function getCountdownRemaining(){
    const t=localStorage.getItem("device_changed_time");
    if(!t) return null;
    return WAIT_MS-(Date.now()-parseInt(t));
}

/* ---------------- 主狀態 ---------------- */
var currentDeviceId=null;
var deviceReloadDialogShown=false;

/* ---------------- 跳視窗 ---------------- */
function showReloadPopup(id){
    if(deviceReloadDialogShown) return;
    deviceReloadDialogShown=true;
    clearDeviceChanged();

    const msg=getDeviceReloadMessage(id);
    const ui=getAlertifyUiText();

    if(!window.alertify?.alert){
        location.reload();
        return;
    }

    alertify.alert(ui.title,msg,function(){
        location.reload();
    }).set({
        labels:{ok:ui.ok},
        closable:false,
        movable:false
    });
}

/* ---------------- 檢查是否到達倒數時間 ---------------- */
function checkCountdown(id){
    const remain=getCountdownRemaining();
    if(remain===null) return;

    if(remain<=0){
        showReloadPopup(id);
    }
}

/* ---------------- 輪詢 ---------------- */
function pollDeviceId(){

    $.ajax({
        url:"?url=Check/ajax_check_device_id",
        type:"POST",
        dataType:"json",
        timeout:3000,

        success:function(res){
            if(!res || res.res_type!=="OK") return;

            let newId=parseInt(res.device_id);
            if(!Number.isFinite(newId)) return;

            /* ⭐ 第一次初始化 */
            if(currentDeviceId === null){
                currentDeviceId = newId;
                return;
            }

            /* ⭐⭐⭐ 真正偵測 ID 變化 ⭐⭐⭐ */
            if(newId !== currentDeviceId){
                console.log("Device ID changed:", currentDeviceId, "→", newId);

                // ⭐ 如果還沒在倒數 → 才開始倒數
                if(getCountdownRemaining() === null){
                    markDeviceChanged(newId);
                }
            }

            /* ⭐ 檢查倒數是否結束 */
            checkCountdown(newId);

            currentDeviceId = newId;
        },

        complete:function(){
            setTimeout(pollDeviceId,2000);
        }
    });
}


/* ---------------- 啟動 ---------------- */
$(function(){
    const cookieVal=getCookieSafe("temp_device_id");
    if(cookieVal){
        currentDeviceId=parseInt(cookieVal);
        if(!Number.isFinite(currentDeviceId)) currentDeviceId=null;
    }
    pollDeviceId();
});
</script>








<style>

/* 不要再用裸的 button，改用 big-btn */
.big-btn {
    background: #EEEEEE;
    border-radius: 10%;
    width: 130px;
    height: 130px;
    color: #FFFFFF;
    text-align: center;
    /* float: center; 這個其實是無效值，可以拿掉，改用 flex 或 text-align 排版 */
}

/* 只縮小 Device ID 警告視窗裡的「確定」(OK) 按鈕 */
.device-reload-alert .ajs-footer .ajs-buttons .ajs-button.ajs-ok {
    width: auto !important;
    height: auto !important;
    min-width: 60px !important;
    min-height: 26px !important;

    padding: 2px 8px !important;
    font-size: 12px !important;
    font-weight: 400 !important;
    text-transform: none !important;

    background: transparent !important;
    border-radius: 4px !important;
    line-height: 1.2 !important;
    margin: 0 4px !important;
}
</style>