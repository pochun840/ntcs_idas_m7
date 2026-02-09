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
   🌐 Language helper
============================================================ */
function getCookieSafe(name){
    try{
        const m=document.cookie.match(new RegExp('(?:^|; )'+name+'=([^;]*)'));
        return m?decodeURIComponent(m[1]):null;
    }catch(e){return null;}
}
function getLangCode(){
    let lang=(getCookieSafe("language")||"zh-tw").toLowerCase();
    if(lang==="en") lang="en-us";
    if(!["en-us","zh-tw","zh-cn"].includes(lang)) lang="en-us";
    return lang;
}
const TEXT = {
    rebootBanner:{
        "zh-tw":"控制器裝置編號已變更，請重新啟動控制器以完成套用。",
        "zh-cn":"控制器设备编号已变更，请重新启动控制器以完成应用。",
        "en-us":"Controller device ID changed. Please reboot the controller."
    },
    reloadTitle:{ "zh-tw":"提示","zh-cn":"提示","en-us":"Notice" },
    reloadMsg:(id)=>({
        "zh-tw":"偵測到新的控制器 (ID:"+id+")，是否重新整理畫面？",
        "zh-cn":"检测到新的控制器 (ID:"+id+")，是否重新刷新页面？",
        "en-us":"New controller detected (ID:"+id+"). Reload now?"
    }),
    ok:{ "zh-tw":"確定","zh-cn":"确定","en-us":"OK" }
};
function t(obj){ return obj[getLangCode()] || obj["en-us"]; }

/* ============================================================
   ⭐ Boot grace（controller reboot）
============================================================ */
var bootGraceStart=null;
const BOOT_GRACE_MS=30000;
var lastOnlineState=null;

function startBootGrace(){ bootGraceStart=Date.now(); }
function inBootGrace(){
    if(!bootGraceStart) return false;
    return (Date.now()-bootGraceStart)<BOOT_GRACE_MS;
}

/* ============================================================
   🔴 Banner（改ID用）
============================================================ */
function showRebootBanner(){
    if(document.getElementById("rebootBanner")) return;

    const banner=document.createElement("div");
    banner.id="rebootBanner";
    banner.innerHTML="🔴 "+t(TEXT.rebootBanner);

    Object.assign(banner.style,{
        position:"fixed",top:"0",left:"0",width:"100%",
        background:"#c0392b",color:"#fff",padding:"12px",
        textAlign:"center",fontSize:"15px",zIndex:"99999",fontWeight:"bold"
    });
    document.body.appendChild(banner);
}
function hideRebootBanner(){ document.getElementById("rebootBanner")?.remove(); }

/* ============================================================
   🔵 Popup（換控制器用）
============================================================ */
var currentDeviceId=null;
var deviceReloadDialogShown=false;

function showReloadPopup(id){
    if(deviceReloadDialogShown) return;
    deviceReloadDialogShown=true;
    hideRebootBanner();

    alertify.alert(
        t(TEXT.reloadTitle),
        t(TEXT.reloadMsg(id))
    ).set({
        labels:{ ok:t(TEXT.ok) },
        closable:false,movable:false,pinnable:false,resizable:false,
        onok:function(){ location.reload(); }
    });
}

/* ============================================================
   ⭐ 同步 + Banner 控制（最終完整版）
============================================================ */
var lastChangedState = null; // ⭐ 新增：記錄上一輪 changed 狀態

function autoSyncDeviceAfterReload(){

    $.post("?url=Check/ajax_check_device_id",function(res){

        if(!res || res.res_type!=="OK") return;
        const changed = (res.changed===true || res.changed==="true" || res.changed==1);

        /* ⭐⭐⭐ 同步完成偵測（最關鍵）⭐⭐⭐
           changed：true → false = 後端剛同步完成
           → UI 必須 reload
        */
        if(lastChangedState === true && changed === false){
            console.log("Device sync finished → show reload popup");
            showReloadPopup(res.device_id);
            lastChangedState = changed;
            return;
        }

        /* 更新狀態紀錄 */
        lastChangedState = changed;

        /* ⭐ reboot期間：只顯示 Banner */
        if(inBootGrace()){
            if(changed) showRebootBanner();
            else hideRebootBanner();
            return;
        }

        /* ⭐ ID已一致 */
        if(!changed){
            hideRebootBanner();
            localStorage.removeItem("device_sync_lock");
            return;
        }

        /* ⭐ 同一台控制器改ID → 顯示 Banner */
        showRebootBanner();

        /* ⭐ 同步只做一次 */
        if(localStorage.getItem("device_sync_lock")==="1") return;
        localStorage.setItem("device_sync_lock","1");

        console.log("Start backend sync...");
        $.post("?url=Check/sync_device_identity");

    },"json");
}


/* ============================================================
   ⭐ 輪詢 Controller（最重要）
============================================================ */
function pollDeviceId(){
    $.ajax({
        url:"?url=Check/ajax_check_device_id",
        type:"POST",
        dataType:"json",
        success:function(res){

            if(!res || res.res_type!=="OK") return;

            const newId=parseInt(res.device_id);
            if(!Number.isFinite(newId)) return;

            const changed = (res.changed===true || res.changed==="true" || res.changed==1);

            /* reboot偵測 */
            if(lastOnlineState===false && res.online===true)
                startBootGrace();
            lastOnlineState=res.online;

            /* 初始化 */
            if(currentDeviceId===null){
                currentDeviceId=newId;
                return;
            }

            /* ⭐⭐⭐ 核心判斷 ⭐⭐⭐ */
            if(newId!==currentDeviceId){

                if(inBootGrace()){
                    currentDeviceId=newId;
                    return;
                }

                if(changed){
                    /* 改ID（同一台）→ Banner */
                    showRebootBanner();
                    currentDeviceId=newId;
                    return;
                }else{
                    /* 換控制器 → Popup */
                    showReloadPopup(newId);
                    return;
                }
            }

            currentDeviceId=newId;
            autoSyncDeviceAfterReload();
        },
        complete:function(){ setTimeout(pollDeviceId,2000); }
    });
}

/* ============================================================
   啟動
============================================================ */
$(function(){
    const cookieVal=getCookieSafe("temp_device_id");
    if(cookieVal) currentDeviceId=parseInt(cookieVal);

    autoSyncDeviceAfterReload();
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