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
    rebootBannerDevice:{
        "zh-tw":"控制器裝置編號已變更，請手動重新啟動控制器以完成套用。",
        "zh-cn":"控制器设备编号已变更，请手动重新启动控制器以完成应用。",
        "en-us":"Controller device ID changed. Please manually reboot the controller."
    },
    rebootBannerModbus:{
        "zh-tw":"控制器通訊協議已變更，請手動重新啟動控制器以完成套用。",
        "zh-cn":"控制器通讯协议已变更，请手动重新启动控制器以完成应用。",
        "en-us":"Controller communication protocol changed. Please manually reboot the controller."
    },
    rebootBannerBoth:{
        "zh-tw":"控制器裝置編號與通訊協議已變更，請手動重新啟動控制器以完成套用。",
        "zh-cn":"控制器设备编号与通讯协议已变更，请手动重新启动控制器以完成应用。",
        "en-us":"Controller device ID and communication protocol changed. Please manually reboot the controller."
    },
    reloadTitle:{ "zh-tw":"提示","zh-cn":"提示","en-us":"Notice" },
    reloadMsg:(id)=>({
        "zh-tw":"偵測到新的控制器 (ID:"+id+")，是否重新整理畫面？",
        "zh-cn":"检测到新的控制器 (ID:"+id+")，是否重新刷新页面？",
        "en-us":"New controller detected (ID:"+id+"). Reload now?"
    }),
    syncReloadMsg:(id, changeType)=>({
        "zh-tw":getSyncReloadMessage("zh-tw", id, changeType),
        "zh-cn":getSyncReloadMessage("zh-cn", id, changeType),
        "en-us":getSyncReloadMessage("en-us", id, changeType)
    }),
    syncFail:{
        "zh-tw":"同步控制器設定失敗，請稍後再試。",
        "zh-cn":"同步控制器设置失败，请稍后再试。",
        "en-us":"Failed to synchronize controller settings. Please try again."
    },
    ok:{ "zh-tw":"確定","zh-cn":"确定","en-us":"OK" }
};

function t(obj){ return obj[getLangCode()] || obj["en-us"]; }

function getSyncReloadMessage(lang, id, changeType){
    const idText = Number.isFinite(parseInt(id,10)) ? " (ID:"+id+")" : "";

    if(lang === "zh-cn"){
        if(changeType === "modbus_type") return "检测到控制器已重新上线，是否同步新的通讯协议并重新刷新页面？";
        if(changeType === "device_id") return "检测到控制器已重新上线，是否同步新的设备编号"+idText+"并重新刷新页面？";
        return "检测到控制器已重新上线，是否同步新的控制器设置"+idText+"并重新刷新页面？";
    }

    if(lang === "en-us"){
        if(changeType === "modbus_type") return "The controller is online again. Synchronize the new communication protocol and reload now?";
        if(changeType === "device_id") return "The controller is online again. Synchronize the new device ID"+idText+" and reload now?";
        return "The controller is online again. Synchronize the new controller settings"+idText+" and reload now?";
    }

    if(changeType === "modbus_type") return "偵測到控制器已重新上線，是否同步新的通訊協議並重新整理畫面？";
    if(changeType === "device_id") return "偵測到控制器已重新上線，是否同步新的裝置編號"+idText+"並重新整理畫面？";
    return "偵測到控制器已重新上線，是否同步新的控制器設定"+idText+"並重新整理畫面？";
}

/* ============================================================
   Controller identity change flow
   1) changed=true          → show red banner only
   2) online=false observed → wait for controller online again
   3) online=true observed  → show popup
   4) popup OK             → Check/sync_device_identity → reload
============================================================ */
const DEVICE_FLOW_STATE_KEY   = "idas_device_identity_flow_state";
const DEVICE_FLOW_PAYLOAD_KEY = "idas_device_identity_flow_payload";
const DEVICE_FLOW_IDLE        = "idle";
const DEVICE_FLOW_WAIT_OFFLINE= "waiting_offline";
const DEVICE_FLOW_WAIT_ONLINE = "waiting_online";
const DEVICE_FLOW_WAIT_POPUP  = "waiting_popup";
const DEVICE_FLOW_SYNCING     = "syncing";

var currentDeviceId = null;
var lastOnlineState = null;
var deviceReloadDialogShown = false;

function boolVal(value){
    return value === true || value === "true" || value === 1 || value === "1";
}

function getFlowState(){
    return localStorage.getItem(DEVICE_FLOW_STATE_KEY) || DEVICE_FLOW_IDLE;
}

function setFlowState(state, payload){
    localStorage.setItem(DEVICE_FLOW_STATE_KEY, state);
    if(payload){
        localStorage.setItem(DEVICE_FLOW_PAYLOAD_KEY, JSON.stringify(payload));
    }
}

function getFlowPayload(){
    try{
        return JSON.parse(localStorage.getItem(DEVICE_FLOW_PAYLOAD_KEY) || "{}");
    }catch(e){
        return {};
    }
}

function clearFlowState(){
    localStorage.removeItem(DEVICE_FLOW_STATE_KEY);
    localStorage.removeItem(DEVICE_FLOW_PAYLOAD_KEY);
    localStorage.removeItem("device_sync_lock"); // 舊版相容：清掉曾經自動同步用的 lock
}

function normalizeChangeType(res){
    const type = String(res.change_type || "").toLowerCase();
    if(type === "modbus_type" || type === "device_id" || type === "both") return type;

    const idChanged = boolVal(res.id_changed);
    const modbusChanged = boolVal(res.modbus_type_changed);
    if(idChanged && modbusChanged) return "both";
    if(modbusChanged) return "modbus_type";
    if(idChanged) return "device_id";
    return "none";
}

function buildFlowPayload(res){
    return {
        device_id: res.device_id ?? null,
        idas_id: res.idas_id ?? null,
        modbus_type: res.modbus_type ?? null,
        idas_modbus_type: res.idas_modbus_type ?? null,
        change_type: normalizeChangeType(res),
        updated_at: Date.now()
    };
}

function getBannerText(changeType){
    if(changeType === "modbus_type") return t(TEXT.rebootBannerModbus);
    if(changeType === "both") return t(TEXT.rebootBannerBoth);
    return t(TEXT.rebootBannerDevice);
}

function showRebootBanner(changeType){
    const payload = getFlowPayload();
    const finalChangeType = changeType || payload.change_type || "device_id";
    let banner = document.getElementById("rebootBanner");

    if(!banner){
        banner=document.createElement("div");
        banner.id="rebootBanner";
        Object.assign(banner.style,{
            position:"fixed",top:"0",left:"0",width:"100%",
            background:"#c0392b",color:"#fff",padding:"12px",
            textAlign:"center",fontSize:"15px",zIndex:"99999",fontWeight:"bold"
        });
        document.body.appendChild(banner);
    }

    banner.innerHTML="🔴 "+getBannerText(finalChangeType);
}

function hideRebootBanner(){
    document.getElementById("rebootBanner")?.remove();
}

function syncDeviceIdentityAndReload(){
    const payload = getFlowPayload();
    setFlowState(DEVICE_FLOW_SYNCING, payload);

    $.ajax({
        url:"?url=Check/sync_device_identity",
        type:"POST",
        dataType:"json",
        timeout:15000,
        success:function(syncRes){
            if(syncRes && syncRes.res_type === "OK"){
                clearFlowState();
                hideRebootBanner();
                location.reload();
                return;
            }

            deviceReloadDialogShown = false;
            setFlowState(DEVICE_FLOW_WAIT_POPUP, payload);
            alertify.alert(t(TEXT.reloadTitle), t(TEXT.syncFail));
        },
        error:function(){
            deviceReloadDialogShown = false;
            setFlowState(DEVICE_FLOW_WAIT_POPUP, payload);
            alertify.alert(t(TEXT.reloadTitle), t(TEXT.syncFail));
        }
    });
}

function showReloadPopup(id, needSync, changeType){
    if(deviceReloadDialogShown) return;
    deviceReloadDialogShown=true;

    if(needSync){
        const payload = Object.assign(getFlowPayload(), {
            device_id: id ?? getFlowPayload().device_id ?? null,
            change_type: changeType || getFlowPayload().change_type || "device_id"
        });
        setFlowState(DEVICE_FLOW_WAIT_POPUP, payload);
    }else{
        hideRebootBanner();
    }

    const msg = needSync
        ? t(TEXT.syncReloadMsg(id, changeType || getFlowPayload().change_type || "device_id"))
        : t(TEXT.reloadMsg(id));

    alertify.alert(
        t(TEXT.reloadTitle),
        msg
    ).set({
        labels:{ ok:t(TEXT.ok) },
        closable:false,
        movable:false,
        pinnable:false,
        resizable:false,
        onok:function(){
            if(needSync){
                syncDeviceIdentityAndReload();
            }else{
                location.reload();
            }
        }
    });
}

function handleDeviceIdentityStatus(res){
    if(!res || res.res_type !== "OK") return;

    const newId = parseInt(res.device_id, 10);
    const hasNewId = Number.isFinite(newId);
    const online = boolVal(res.online);
    const changed = boolVal(res.changed);
    const changeType = normalizeChangeType(res);
    const stateBefore = getFlowState();

    if(hasNewId && currentDeviceId === null){
        currentDeviceId = newId;
    }

    if(!changed){
        if(stateBefore !== DEVICE_FLOW_SYNCING){
            clearFlowState();
            hideRebootBanner();
            deviceReloadDialogShown = false;
        }

        if(hasNewId && currentDeviceId !== null && newId !== currentDeviceId && online){
            showReloadPopup(newId, false, "none");
        }

        if(hasNewId) currentDeviceId = newId;
        lastOnlineState = online;
        return;
    }

    const payload = buildFlowPayload(res);
    showRebootBanner(changeType);

    let state = stateBefore;
    if(state === DEVICE_FLOW_IDLE){
        state = DEVICE_FLOW_WAIT_OFFLINE;
        setFlowState(state, payload);
    }else if(state !== DEVICE_FLOW_SYNCING){
        setFlowState(state, Object.assign(getFlowPayload(), payload));
    }

    // 已偵測到不一致後，必須先看到 offline，不能直接同步。
    if(state === DEVICE_FLOW_WAIT_OFFLINE && online === false){
        state = DEVICE_FLOW_WAIT_ONLINE;
        setFlowState(state, payload);
    }

    // 只要已經看過 offline，下一次 online=true 才能跳同步 Popup。
    if((state === DEVICE_FLOW_WAIT_ONLINE || state === DEVICE_FLOW_WAIT_POPUP) && online === true){
        showReloadPopup(hasNewId ? newId : payload.device_id, true, payload.change_type);
    }

    if(hasNewId) currentDeviceId = newId;
    lastOnlineState = online;
}

function markControllerOfflineByAjaxError(){
    const state = getFlowState();
    if(state === DEVICE_FLOW_WAIT_OFFLINE){
        const payload = getFlowPayload();
        setFlowState(DEVICE_FLOW_WAIT_ONLINE, payload);
        showRebootBanner(payload.change_type || "device_id");
    }
    lastOnlineState = false;
}

function pollDeviceId(){
    $.ajax({
        url:"?url=Check/ajax_check_device_id",
        type:"POST",
        dataType:"json",
        timeout:5000,
        success:function(res){
            handleDeviceIdentityStatus(res);
        },
        error:function(){
            markControllerOfflineByAjaxError();
        },
        complete:function(){
            setTimeout(pollDeviceId,2000);
        }
    });
}

$(function(){
    const cookieVal=getCookieSafe("temp_device_id");
    if(cookieVal) currentDeviceId=parseInt(cookieVal,10);

    if(getFlowState() !== DEVICE_FLOW_IDLE){
        showRebootBanner(getFlowPayload().change_type || "device_id");
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