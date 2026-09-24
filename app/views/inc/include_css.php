<?php
if (!function_exists('idas_asset_cache_version')) {
    function idas_asset_cache_version(): string
    {
        $version = (string)ASSET_VERSION;
        $recovery = (string)($_GET['_idas_boot'] ?? $_GET['_startup_retry'] ?? '');
        if ($recovery !== '' && preg_match('/^\d{8,20}$/', $recovery)) {
            $version .= '-' . $recovery;
        }
        return $version;
    }
}
?>
<?php if (idas_is_icontroller()): ?>
<?php 
// 共用：條件式載入 CSS / JS（根據 URL 第一層）
function include_asset($part, $fileName) {
    // $_GET['url'] is already URL-decoded. Reading raw QUERY_STRING breaks
    // routes such as Settings%2Findex after cache-busting reloads.
    $parts = explode('/', trim((string)($_GET['url'] ?? ''), '/'));
    $firstPart = $parts[0] ?? '';
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

    //特別排除 Sequences 頁面載入 sequences.js（強制不要載）
    if (!($firstPart === 'Sequences' && $fileName === 'sequences.js')) {
        if ($firstPart === $part) {
            $path = ($extension === 'css') ? 'css' : 'js';
            $tag = ($extension === 'css')
                ? "<link rel=\"stylesheet\" href=\"" . idas_asset_url($path . '/' . $fileName) . '?v=' . idas_asset_cache_version() . "\">"
                : "<script src=\"" . idas_asset_url($path . '/' . $fileName) . '?v=' . idas_asset_cache_version() . "\"></script>";
            echo $tag . "\n";
        }
    }

    //額外條件：若網址是 Sequences，就強制載入 seq.js
    if ($firstPart === 'Sequences' && $fileName === 'sequences.js') {
        echo "<script src=\"" . idas_asset_url('js/seq.js') . '?v=' . idas_asset_cache_version() . "\"></script>\n";
    }
}

function include_css() {
    $routeParts = explode('/', trim((string)($_GET['url'] ?? ''), '/'));
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
        echo '<link rel="stylesheet" href="' . idas_asset_url('css/' . $cssFile) . '?v=' . idas_asset_cache_version() . '" type="text/css">' . "\n";
    }
}



?>

    <!-- Browser-independent reboot/startup recovery. Inline by design: it must
         still render when external CSS or JavaScript is temporarily unavailable. -->
    <style id="idas-boot-recovery-style">
        html.idas-boot-pending body > *:not(#idas-boot-recovery-overlay){visibility:hidden!important}
        #idas-boot-recovery-overlay{position:fixed;inset:0;z-index:2147483647;display:flex;align-items:center;justify-content:center;background:#f5f6f7;color:#222;font-family:Arial,"Microsoft JhengHei",sans-serif;visibility:visible!important}
        #idas-boot-recovery-overlay[hidden]{display:none!important}
        #idas-boot-recovery-card{width:min(520px,calc(100vw - 40px));box-sizing:border-box;padding:28px 34px;border:1px solid #d8dde2;border-radius:10px;background:#fff;box-shadow:0 4px 18px rgba(0,0,0,.12);text-align:center}
        #idas-boot-recovery-spinner{width:44px;height:44px;margin:0 auto 18px;border:4px solid #d9dee3;border-top-color:#2878c8;border-radius:50%;animation:idas-boot-spin .9s linear infinite}
        #idas-boot-recovery-title{font-size:22px;font-weight:700;margin-bottom:10px}
        #idas-boot-recovery-message{font-size:15px;line-height:1.6;color:#555}
        #idas-boot-recovery-retry{display:none;margin:20px auto 0;padding:10px 24px;border:0;border-radius:6px;background:#2878c8;color:#fff;font-size:15px;cursor:pointer}
        @keyframes idas-boot-spin{to{transform:rotate(360deg)}}
    </style>
    <script>
    (function installIdasBootRecovery(){
        'use strict';
        // Startup interception was removed: it could replace a valid page and
        // trap the user in an endless retry/home loop.
        return;
        var RECOVERY_KEY='idas_asset_recovery_count';
        var STARTUP_KEY='idas_startup_retry_count';
        var MAX_RELOADS=1;
        var RETRY_DELAY=3000;
        var resourceFailed=false;
        var waitingForBackend=false;
        var backendTimer=null;

        window.addEventListener('error',function(ev){
            var target=ev && ev.target;
            if(target && (target.tagName==='LINK' || target.tagName==='SCRIPT')){
                resourceFailed=true;
            }
        },true);

        function text(){
            var lang='zh-tw';
            try{
                var m=document.cookie.match(/(?:^|; )language=([^;]*)/);
                lang=m?decodeURIComponent(m[1]).toLowerCase():'zh-tw';
            }catch(e){}
            if(lang==='zh-cn') return {title:'iDAS 正在启动',message:'系统服务正在恢复，请稍候。',failed:'系统尚未完全就绪，请重新加载。',retry:'重新加载'};
            if(lang==='en' || lang==='en-us') return {title:'iDAS is starting',message:'System services are recovering. Please wait.',failed:'The system is not ready yet. Please reload.',retry:'Reload'};
            return {title:'iDAS 正在啟動',message:'系統服務正在恢復，請稍候。',failed:'系統尚未完全就緒，請重新載入。',retry:'重新載入'};
        }

        function ensureOverlay(message){
            var overlay=document.getElementById('idas-boot-recovery-overlay');
            if(!overlay && document.body){
                var t=text();
                overlay=document.createElement('div');
                overlay.id='idas-boot-recovery-overlay';
                overlay.innerHTML='<div id="idas-boot-recovery-card" role="status" aria-live="polite">'
                    +'<div id="idas-boot-recovery-spinner" aria-hidden="true"></div>'
                    +'<div id="idas-boot-recovery-title"></div>'
                    +'<div id="idas-boot-recovery-message"></div>'
                    +'<button id="idas-boot-recovery-retry" type="button"></button></div>';
                document.body.appendChild(overlay);
                document.getElementById('idas-boot-recovery-title').textContent=t.title;
                document.getElementById('idas-boot-recovery-retry').textContent=t.retry;
                document.getElementById('idas-boot-recovery-retry').onclick=function(){
                    try{sessionStorage.removeItem(RECOVERY_KEY);sessionStorage.removeItem(STARTUP_KEY);}catch(e){}
                    reloadWithCacheBust();
                };
            }
            if(overlay){
                overlay.hidden=false;
                var msg=document.getElementById('idas-boot-recovery-message');
                if(msg) msg.textContent=message || text().message;
            }
            document.documentElement.classList.add('idas-boot-pending');
            return overlay;
        }

        function hideOverlay(){
            var overlay=document.getElementById('idas-boot-recovery-overlay');
            if(overlay) overlay.hidden=true;
            document.documentElement.classList.remove('idas-boot-pending');
            try{
                sessionStorage.removeItem(RECOVERY_KEY);
                sessionStorage.removeItem(STARTUP_KEY);
            }catch(e){}
        }

        function reloadWithCacheBust(){
            var hashParts=location.href.split('#');
            var u=hashParts.shift();
            u+=(u.indexOf('?')>=0?'&':'?')+'_idas_boot='+Date.now();
            location.replace(u+(hashParts.length?'#'+hashParts.join('#'):''));
        }

        function stylesHealthy(){
            var links=Array.prototype.slice.call(document.querySelectorAll('link[rel="stylesheet"]'));
            return links.every(function(link){
                if(link.disabled) return true;
                if(!link.sheet) return false;
                try{void link.sheet.cssRules;return true;}catch(e){return true;}
            });
        }

        function layoutHealthy(){
            var button=document.querySelector('#bnt1.button');
            if(!button) return true;
            var s=window.getComputedStyle(button);
            return parseFloat(s.height||'0')>=40
                && parseFloat(s.fontSize||'0')>=17
                && s.display==='inline-block'
                && s.backgroundColor!=='rgba(0, 0, 0, 0)'
                && s.backgroundColor!=='transparent';
        }

        function scriptsHealthy(){
            return typeof window.jQuery==='function'
                && typeof window.alertify!=='undefined';
        }

        function pageHealthy(){
            return !resourceFailed
                && document.readyState==='complete'
                && stylesHealthy()
                && layoutHealthy()
                && scriptsHealthy();
        }

        function recoverAssets(){
            ensureOverlay();
            var count=0;
            try{count=parseInt(sessionStorage.getItem(RECOVERY_KEY)||'0',10)||0;}catch(e){}
            if(count>=MAX_RELOADS){
                var retry=document.getElementById('idas-boot-recovery-retry');
                var msg=document.getElementById('idas-boot-recovery-message');
                if(msg) msg.textContent=text().failed;
                if(retry) retry.style.display='block';
                return;
            }
            try{sessionStorage.setItem(RECOVERY_KEY,String(count+1));}catch(e){}
            setTimeout(reloadWithCacheBust,RETRY_DELAY);
        }

        function probeBackend(){
            if(!waitingForBackend) return;
            var u=new URL(location.href);
            u.searchParams.set('_idas_web_probe',String(Date.now()));
            fetch(u.toString(),{cache:'no-store',credentials:'same-origin'})
                .then(function(res){
                    if(!res.ok) throw new Error('HTTP '+res.status);
                    waitingForBackend=false;
                    if(pageHealthy()) hideOverlay();
                    else reloadWithCacheBust();
                })
                .catch(function(){
                    backendTimer=setTimeout(probeBackend,RETRY_DELAY);
                });
        }

        window.IDASBootRecovery={
            show:function(message){ensureOverlay(message);},
            hide:hideOverlay,
            waitForBackend:function(message){
                ensureOverlay(message);
                waitingForBackend=true;
                if(backendTimer) clearTimeout(backendTimer);
                probeBackend();
            },
            check:function(){
                if(pageHealthy()) hideOverlay(); else recoverAssets();
            }
        };

        window.addEventListener('load',function(){
            setTimeout(function(){
                if(resourceFailed) window.IDASBootRecovery.check();
                else hideOverlay();
            },700);
        },{once:true});

        // Never expose a page whose critical layout CSS is still unavailable.
        // At eight seconds, switch to an actionable retry state instead of an
        // endless spinner or a forced broken layout.
        setTimeout(function(){if(resourceFailed)window.IDASBootRecovery.check();},8000);
    })();
    </script>

    <!-- ================== 基礎 JS ================== -->
    <script src="<?php echo idas_asset_url('js/jquery-3.7.1.min.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>

    <!-- ================== 基礎 CSS ================== -->
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/jquery_data_Tables.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/datatables.min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/w3.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/font-awesome.min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/flatpickr.min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/alertify_min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/default_min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/footer.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/interaction_feedback.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/idas_notifications.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>-notify4">
    <?php
        $route = explode('/', trim((string)($_GET['url'] ?? ''), '/'))[0] ?? '';

        // 檢查是否為行動裝置
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent);

        // 不在 Inputs 或 Outputs 頁面時，根據裝置載入對應的 CSS
        if (!in_array($route, ['Inputs', 'Outputs'])) {
            $cssFile = $isMobile ? 'share_m.css' : 'share.css';
            echo '<link rel="stylesheet" href="' . idas_asset_url('css/' . $cssFile) . '?v=' . idas_asset_cache_version() . '">' . "\n";
        }
    ?>


    
    <!-- ================== 模組 CSS 動態載入 ================== -->
    <?php echo include_css();?>


    <!-- ================== 共用 JS ================== -->
    <script src="<?php echo idas_asset_url('js/all.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/interaction_feedback.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/echarts_min.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/jquery_data_Tables.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/alertify_min.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/idas_notifications.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>-notify4"></script>



    <!-- ================== 模組 JS 動態載入 ================== -->
    <?php 
    $modules = ['Inputs', 'Outputs', 'Jobs', 'Data', 'Sequences', 'Step', 'Settings'];
    foreach ($modules as $mod) {
        include_asset($mod, strtolower($mod) . '.js');
    }
    ?>

    <!-- ================== 其他工具 JS ================== -->
    <script src="<?php echo idas_asset_url('js/flatpickr.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/flatpickr_zh-tw.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/tcc_data.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/jszip.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>


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
function t(obj){ return obj[getLangCode()] || obj["en-us"]; }

function protocolName(type){
    const n = parseInt(type, 10);
    if(n === 0) return 'MODBUS TCP';
    if(n === 1) return 'MODBUS RTU';
    if(n === 2) return 'OP';
    return '';
}

function protocolChangeLabel(oldType, newType){
    const oldName = protocolName(oldType);
    const newName = protocolName(newType);

    // 方向必須依後端回傳值動態顯示：
    // idas_modbus_type = iDAS 目前舊值
    // modbus_type      = Controller 目前新值
    // 因此可支援 MODBUS TCP → OP，也可支援 OP → MODBUS TCP / RTU 等反向變更。
    if(oldName && newName) return oldName + ' → ' + newName;
    if(newName) return newName;
    return '';
}

function getChangeFlags(res){
    const idChanged = (
        res?.id_changed === true || res?.id_changed === 'true' || res?.id_changed == 1
    );
    const modbusTypeChanged = (
        res?.modbus_type_changed === true || res?.modbus_type_changed === 'true' || res?.modbus_type_changed == 1
    );
    const changed = (
        res?.changed === true || res?.changed === 'true' || res?.changed == 1
        || idChanged || modbusTypeChanged
    );

    let type = res?.change_type || 'none';
    if(type === 'none' && idChanged && modbusTypeChanged) type = 'both';
    else if(type === 'none' && idChanged) type = 'device_id';
    else if(type === 'none' && modbusTypeChanged) type = 'modbus_type';

    return { changed, idChanged, modbusTypeChanged, type };
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
    reloadDevice:(id)=>({
        "zh-tw":"偵測到新的控制器 (ID:"+id+")，是否重新整理畫面？",
        "zh-cn":"检测到新的控制器 (ID:"+id+")，是否重新刷新页面？",
        "en-us":"New controller detected (ID:"+id+"). Reload now?"
    }),
    reloadModbus:(protocolChange)=>({
        "zh-tw": protocolChange
            ? "偵測到控制器通訊協議已變更（"+protocolChange+"），是否重新整理畫面？"
            : "偵測到控制器通訊協議已變更，是否重新整理畫面？",
        "zh-cn": protocolChange
            ? "检测到控制器通讯协议已变更（"+protocolChange+"），是否重新刷新页面？"
            : "检测到控制器通讯协议已变更，是否重新刷新页面？",
        "en-us": protocolChange
            ? "Controller communication protocol changed ("+protocolChange+"). Reload now?"
            : "Controller communication protocol changed. Reload now?"
    }),
    reloadBoth:(id,protocolChange)=>({
        "zh-tw": protocolChange
            ? "偵測到新的控制器 (ID:"+id+")，且通訊協議已變更（"+protocolChange+"），是否重新整理畫面？"
            : "偵測到新的控制器 (ID:"+id+")，且通訊協議已變更，是否重新整理畫面？",
        "zh-cn": protocolChange
            ? "检测到新的控制器 (ID:"+id+")，且通讯协议已变更（"+protocolChange+"），是否重新刷新页面？"
            : "检测到新的控制器 (ID:"+id+")，且通讯协议已变更，是否重新刷新页面？",
        "en-us": protocolChange
            ? "New controller detected (ID:"+id+") and protocol changed ("+protocolChange+"). Reload now?"
            : "New controller detected (ID:"+id+") and protocol changed. Reload now?"
    }),
    ok:{ "zh-tw":"確定","zh-cn":"确定","en-us":"OK" },
    cancel:{ "zh-tw":"取消","zh-cn":"取消","en-us":"Cancel" }
};

function getBannerText(res){
    const flags = getChangeFlags(res || {});
    if(flags.type === 'both' || (flags.idChanged && flags.modbusTypeChanged)) {
        return t(TEXT.rebootBannerBoth);
    }
    if(flags.type === 'modbus_type' || flags.modbusTypeChanged) {
        return t(TEXT.rebootBannerModbus);
    }
    return t(TEXT.rebootBannerDevice);
}

function getReloadMessage(res){
    const flags = getChangeFlags(res || {});
    const id = res?.device_id ?? '';
    const protocolChange = protocolChangeLabel(res?.idas_modbus_type, res?.modbus_type);

    if(flags.type === 'both' || (flags.idChanged && flags.modbusTypeChanged)) {
        return t(TEXT.reloadBoth(id, protocolChange));
    }
    if(flags.type === 'modbus_type' || flags.modbusTypeChanged) {
        return t(TEXT.reloadModbus(protocolChange));
    }
    return t(TEXT.reloadDevice(id));
}

/* ============================================================
   ⭐ Controller ID / MODBUS TYPE 變更流程
   1. 偵測 device_id / modbus_type 變更 → 只顯示紅色 Banner。
   2. 等待使用者手動重新啟動控制器。
   3. 前端偵測到 Controller 曾經斷線一次。
   4. Controller 重新上線後才跳出 Popup。
   5. 使用者按確定後才執行 Controller → iDAS 同步。
   6. 同步完成後重新整理畫面。
============================================================ */
var currentDeviceId=null;
var deviceReloadDialogShown=false;
var pendingChangeContext=null;
var waitingForControllerReboot=false;
var controllerWentOfflineAfterChange=false;
var syncAfterRebootStarted=false;

const REBOOT_WAIT_STORAGE_KEY='idas_device_identity_wait_manual_reboot_v2';

function boolValue(v){
    return v===true || v==='true' || v===1 || v==='1';
}

function isOnlineResponse(res){
    return boolValue(res?.online);
}

function saveRebootWaitState(){
    try{
        if(!waitingForControllerReboot){
            localStorage.removeItem(REBOOT_WAIT_STORAGE_KEY);
            return;
        }
        localStorage.setItem(REBOOT_WAIT_STORAGE_KEY, JSON.stringify({
            waiting:true,
            offline:controllerWentOfflineAfterChange,
            context:pendingChangeContext || null,
            ts:Date.now()
        }));
    }catch(e){}
}

function restoreRebootWaitState(){
    try{
        const raw=localStorage.getItem(REBOOT_WAIT_STORAGE_KEY);
        if(!raw) return;
        const data=JSON.parse(raw);
        if(!data || data.waiting!==true) return;

        // 避免舊狀態永久殘留，超過 10 分鐘就清掉。
        if(data.ts && (Date.now()-parseInt(data.ts,10)) > 10*60*1000){
            localStorage.removeItem(REBOOT_WAIT_STORAGE_KEY);
            return;
        }

        waitingForControllerReboot=true;
        controllerWentOfflineAfterChange=!!data.offline;
        pendingChangeContext=data.context || null;
        if(pendingChangeContext) showRebootBanner(pendingChangeContext);
    }catch(e){
        localStorage.removeItem(REBOOT_WAIT_STORAGE_KEY);
    }
}

function clearRebootWaitState(){
    waitingForControllerReboot=false;
    controllerWentOfflineAfterChange=false;
    syncAfterRebootStarted=false;
    pendingChangeContext=null;
    localStorage.removeItem('device_sync_lock');
    try{ localStorage.removeItem(REBOOT_WAIT_STORAGE_KEY); }catch(e){}
}

/* ============================================================
   🔴 Banner（ID / MODBUS TYPE 變更共用）
============================================================ */
function showRebootBanner(res){
    let banner=document.getElementById("rebootBanner");
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
    banner.innerHTML="🔴 "+getBannerText(res);
}
function hideRebootBanner(){ document.getElementById("rebootBanner")?.remove(); }

/* ============================================================
   🔵 Popup（Controller 重開機 + 同步完成後 reload 共用）
============================================================ */
function showReloadPopup(res){
    if(deviceReloadDialogShown) return;
    deviceReloadDialogShown=true;
    hideRebootBanner();

    const popupContext = res || {};

    // 必須使用 confirm 才會套用全站新版警告視窗樣式。
    // Cancel 先保留給共用 enhancer 建立警告圖示/版型，onshow 後再移除，
    // 因此使用者畫面與 DOM 最終都只會留下「確定」。
    const dialog = alertify.confirm(
        t(TEXT.reloadTitle),
        getReloadMessage(popupContext),
        function(){
            syncDeviceIdentityThenReload(popupContext);
        },
        function(){}
    );

    if(dialog && typeof dialog.set === "function"){
        dialog.set({
            labels:{ ok:t(TEXT.ok), cancel:"" },
            closable:false,movable:false,pinnable:false,resizable:false,
            onshow:function(){
                try{
                    const root=this.elements.dialog;
                    root.classList.add("device-reload-alert", "idas-confirm-dialog");
                    window.setTimeout(function(){
                        const cancel=root.querySelector('.ajs-button.ajs-cancel');
                        if(cancel) cancel.remove();
                    },120);
                }catch(e){}
            },
            onok:function(){
                syncDeviceIdentityThenReload(popupContext);
            }
        });
    }
}

function syncDeviceIdentityThenReload(context){
    const popupContext = context || pendingChangeContext || {};

    try { $('#overlay').removeClass('hidden'); } catch(e) {}

    $.post("?url=Check/sync_device_identity", function(syncRes){
        try { $('#overlay').addClass('hidden'); } catch(e) {}

        if(syncRes && syncRes.res_type === "OK"){
            clearRebootWaitState();
            hideRebootBanner();
            location.reload();
            return;
        }

        // 同步失敗：保留等待狀態，避免差異被誤清掉。
        deviceReloadDialogShown=false;
        syncAfterRebootStarted=false;
        showRebootBanner(popupContext);
        saveRebootWaitState();

        if(window.alertify && IdasNotify.error){
            IdasNotify.error('Sync failed');
        }
    }, "json").fail(function(){
        try { $('#overlay').addClass('hidden'); } catch(e) {}
        deviceReloadDialogShown=false;
        syncAfterRebootStarted=false;
        showRebootBanner(popupContext);
        saveRebootWaitState();

        if(window.alertify && IdasNotify.error){
            IdasNotify.error('Sync failed');
        }
    });
}

function beginWaitControllerReboot(res){
    const online = isOnlineResponse(res);

    waitingForControllerReboot=true;
    pendingChangeContext=res || pendingChangeContext;

    // 如果第一次偵測時 Controller 已經離線，也視為已進入重開機階段。
    if(online===false) controllerWentOfflineAfterChange=true;

    showRebootBanner(pendingChangeContext || res);
    saveRebootWaitState();
}

function startSyncAfterControllerManualReboot(){
    if(syncAfterRebootStarted) return;
    syncAfterRebootStarted=true;
    saveRebootWaitState();

    // 這裡只跳 Popup，不做同步。
    // 使用者按下 Popup 確定後，才呼叫 Check/sync_device_identity。
    console.log("Controller manual reboot detected → show reload confirm popup...");
    showReloadPopup(pendingChangeContext || {});
}

function processDeviceIdentityState(res){
    if(!res || res.res_type!=="OK") return false;

    const flags = getChangeFlags(res);
    const changed = flags.changed;
    const online = isOnlineResponse(res);

    if(changed){
        // 只要還沒完成重開機同步，就持續保留第一次偵測到的變更內容，
        // 避免後續回傳內容造成 Popup 方向或 ID 資訊被覆蓋。
        if(!waitingForControllerReboot){
            beginWaitControllerReboot(res);
        }else{
            if(!pendingChangeContext) pendingChangeContext=res;
            showRebootBanner(pendingChangeContext || res);
        }
    }

    if(waitingForControllerReboot){
        if(online===false){
            controllerWentOfflineAfterChange=true;
            showRebootBanner(pendingChangeContext || res);
            saveRebootWaitState();
            return true;
        }

        if(online===true && controllerWentOfflineAfterChange){
            startSyncAfterControllerManualReboot();
            return true;
        }

        // 已偵測到變更，但尚未看到 Controller 因人工重開而離線 → 只顯示 Banner，不同步、不跳 Popup。
        showRebootBanner(pendingChangeContext || res);
        saveRebootWaitState();
        return true;
    }

    if(!changed){
        hideRebootBanner();
        localStorage.removeItem("device_sync_lock");
    }

    return false;
}

/* ============================================================
   ⭐ 與原本流程相同的定期檢查，但改成「等待人工重開機後再 Popup」
============================================================ */
function autoSyncDeviceAfterReload(){
    $.post("?url=Check/ajax_check_device_id",function(res){
        processDeviceIdentityState(res);
    },"json");
}

/* ============================================================
   ⭐ 輪詢 Controller
============================================================ */
function pollDeviceId(){
    $.ajax({
        url:"?url=Check/ajax_check_device_id",
        type:"POST",
        dataType:"json",
        success:function(res){
            if(!res || res.res_type!=="OK") return;

            const newId=parseInt(res.device_id);

            if(Number.isFinite(newId) && currentDeviceId===null){
                currentDeviceId=newId;
            }else if(Number.isFinite(newId)){
                currentDeviceId=newId;
            }

            processDeviceIdentityState(res);
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

    restoreRebootWaitState();
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

/* Controller 重新上線同步提示：此流程必須確認同步，不提供取消按鈕 */
.device-reload-alert .ajs-footer .ajs-buttons .ajs-button.ajs-cancel {
    display: none !important;
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
<?php else: ?>
<?php 
// 共用：條件式載入 CSS / JS（根據 URL 第一層）
function include_asset($part, $fileName) {
    // $_GET['url'] is already URL-decoded. Reading raw QUERY_STRING breaks
    // routes such as Settings%2Findex after cache-busting reloads.
    $parts = explode('/', trim((string)($_GET['url'] ?? ''), '/'));
    $firstPart = $parts[0] ?? '';
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

    //特別排除 Sequences 頁面載入 sequences.js（強制不要載）
    if (!($firstPart === 'Sequences' && $fileName === 'sequences.js')) {
        if ($firstPart === $part) {
            $path = ($extension === 'css') ? 'css' : 'js';
            $tag = ($extension === 'css')
                ? "<link rel=\"stylesheet\" href=\"" . idas_asset_url($path . '/' . $fileName) . '?v=' . idas_asset_cache_version() . "\">"
                : "<script src=\"" . idas_asset_url($path . '/' . $fileName) . '?v=' . idas_asset_cache_version() . "\"></script>";
            echo $tag . "\n";
        }
    }

    //額外條件：若網址是 Sequences，就強制載入 seq.js
    if ($firstPart === 'Sequences' && $fileName === 'sequences.js') {
        echo "<script src=\"" . idas_asset_url('js/seq.js') . '?v=' . idas_asset_cache_version() . "\"></script>\n";
    }
}

function include_css() {
    $routeParts = explode('/', trim((string)($_GET['url'] ?? ''), '/'));
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
        echo '<link rel="stylesheet" href="' . idas_asset_url('css/' . $cssFile) . '?v=' . idas_asset_cache_version() . '" type="text/css">' . "\n";
    }
}



?>

    <?php // Startup recovery overlay intentionally disabled. ?>

    <!-- ================== 基礎 JS ================== -->
    <script src="<?php echo idas_asset_url('js/jquery-3.7.1.min.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>

    <!-- ================== 基礎 CSS ================== -->
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/jquery_data_Tables.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/datatables.min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/w3.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/font-awesome.min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/flatpickr.min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/alertify_min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/default_min.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/footer.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/interaction_feedback.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>">
    <link rel="stylesheet" href="<?php echo idas_asset_url('css/idas_notifications.css'); ?>?v=<?php echo idas_asset_cache_version(); ?>-notify4">
    <?php
        $route = explode('/', trim((string)($_GET['url'] ?? ''), '/'))[0] ?? '';

        // 檢查是否為行動裝置
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent);

        // 不在 Inputs 或 Outputs 頁面時，根據裝置載入對應的 CSS
        if (!in_array($route, ['Inputs', 'Outputs'])) {
            $cssFile = $isMobile ? 'share_m.css' : 'share.css';
            echo '<link rel="stylesheet" href="' . idas_asset_url('css/' . $cssFile) . '?v=' . idas_asset_cache_version() . '">' . "\n";
        }
    ?>


    
    <!-- ================== 模組 CSS 動態載入 ================== -->
    <?php echo include_css();?>


    <!-- ================== 共用 JS ================== -->
    <script src="<?php echo idas_asset_url('js/all.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/interaction_feedback.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/echarts_min.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/jquery_data_Tables.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/alertify_min.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/idas_notifications.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>-notify4"></script>



    <!-- ================== 模組 JS 動態載入 ================== -->
    <?php 
    $modules = ['Inputs', 'Outputs', 'Jobs', 'Data', 'Sequences', 'Step', 'Settings'];
    foreach ($modules as $mod) {
        include_asset($mod, strtolower($mod) . '.js');
    }
    ?>

    <!-- ================== 其他工具 JS ================== -->
    <script src="<?php echo idas_asset_url('js/flatpickr.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/flatpickr_zh-tw.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/tcc_data.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>
    <script src="<?php echo idas_asset_url('js/jszip.js'); ?>?v=<?php echo idas_asset_cache_version(); ?>"></script>


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
        "zh-tw":"控制器設定已變更，請手動重新啟動控制器以完成套用。",
        "zh-cn":"控制器设置已变更，请手动重新启动控制器以完成应用。",
        "en-us":"Controller settings changed. Please manually reboot the controller."
    },
    rebootBannerPort:{
        "zh-tw":"控制器 Server Port 已變更，請手動重新啟動控制器以完成套用。",
        "zh-cn":"控制器 Server Port 已变更，请手动重新启动控制器以完成应用。",
        "en-us":"Controller Server Port changed. Please manually reboot the controller."
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
    syncing:{
        "zh-tw":"控制器已重新上線，正在同步控制器設定，請稍候。",
        "zh-cn":"控制器已重新上线，正在同步控制器设置，请稍候。",
        "en-us":"Controller is online again. Synchronizing controller settings..."
    },
    syncFail:{
        "zh-tw":"同步控制器設定失敗，將自動重試。",
        "zh-cn":"同步控制器设置失败，将自动重试。",
        "en-us":"Failed to synchronize controller settings. Retrying automatically."
    },
    ok:{ "zh-tw":"確定","zh-cn":"确定","en-us":"OK" },
    cancel:{ "zh-tw":"取消","zh-cn":"取消","en-us":"Cancel" }
};

function t(obj){ return obj[getLangCode()] || obj["en-us"]; }

function getSyncReloadMessage(lang, id, changeType){
    const idText = Number.isFinite(parseInt(id,10)) ? " (ID:"+id+")" : "";

    if(lang === "zh-cn"){
        if(changeType === "server_port") return "检测到控制器已重新上线，是否同步新的 Server Port 并重新刷新页面？";
        if(changeType === "modbus_type") return "检测到控制器已重新上线，是否同步新的通讯协议并重新刷新页面？";
        if(changeType === "device_id") return "检测到控制器已重新上线，是否同步新的设备编号"+idText+"并重新刷新页面？";
        return "检测到控制器已重新上线，是否同步新的控制器设置"+idText+"并重新刷新页面？";
    }

    if(lang === "en-us"){
        if(changeType === "server_port") return "The controller is online again. Synchronize the new Server Port and reload now?";
        if(changeType === "modbus_type") return "The controller is online again. Synchronize the new communication protocol and reload now?";
        if(changeType === "device_id") return "The controller is online again. Synchronize the new device ID"+idText+" and reload now?";
        return "The controller is online again. Synchronize the new controller settings"+idText+" and reload now?";
    }

    if(changeType === "server_port") return "偵測到控制器已重新上線，是否同步新的 Server Port 並重新整理畫面？";
    if(changeType === "modbus_type") return "偵測到控制器已重新上線，是否同步新的通訊協議並重新整理畫面？";
    if(changeType === "device_id") return "偵測到控制器已重新上線，是否同步新的裝置編號"+idText+"並重新整理畫面？";
    return "偵測到控制器已重新上線，是否同步新的控制器設定"+idText+"並重新整理畫面？";
}

/* ============================================================
   Controller identity change flow
   1) changed=true          → show red banner
   2) online=false         → wait for controller online again
   3) online=true          → show synchronization confirmation
   4) user confirms        → synchronize and reload automatically
============================================================ */
const DEVICE_FLOW_STATE_KEY   = "idas_device_identity_flow_state";
const IDAS_REBOOT_OBSERVED_KEY = "idas_reboot_observed";
const DEVICE_FLOW_PAYLOAD_KEY = "idas_device_identity_flow_payload";
const DEVICE_IDENTITY_BASELINE_KEY = "idas_device_identity_baseline";
const DEVICE_FLOW_IDLE        = "idle";
const DEVICE_FLOW_WAIT_OFFLINE= "waiting_offline";
const DEVICE_FLOW_WAIT_ONLINE = "waiting_online";
const DEVICE_FLOW_WAIT_POPUP  = "waiting_popup";
const DEVICE_FLOW_SYNCING     = "syncing";

var currentDeviceId = null;
var lastOnlineState = null;
var deviceReloadDialogShown = false;
var controllerAjaxErrorCount = 0;
var fastChangeAjaxErrorCount = 0;

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


function wasControllerRebootObserved(){
    try{
        return localStorage.getItem(IDAS_REBOOT_OBSERVED_KEY) === "1";
    }catch(e){
        return false;
    }
}

function clearControllerRebootObserved(){
    try{
        localStorage.removeItem(IDAS_REBOOT_OBSERVED_KEY);
    }catch(e){}
}

function clearFlowState(){
    localStorage.removeItem(DEVICE_FLOW_STATE_KEY);
    localStorage.removeItem(DEVICE_FLOW_PAYLOAD_KEY);
    localStorage.removeItem("device_sync_lock"); // 舊版相容：清掉曾經自動同步用的 lock
}

function normalizeObservedIdentity(source){
    const deviceId=parseInt(source?.device_id,10);
    const modbusType=parseInt(source?.modbus_type,10);
    const tcpPort=parseInt(source?.server_port,10);
    return {
        device_id:Number.isFinite(deviceId)?deviceId:null,
        modbus_type:Number.isFinite(modbusType)?modbusType:null,
        // Only TCP owns a configurable Server Port. OP is always 4545.
        server_port:modbusType===0 && Number.isFinite(tcpPort)?tcpPort:null
    };
}

function getIdentityBaseline(){
    try{
        const value=JSON.parse(localStorage.getItem(DEVICE_IDENTITY_BASELINE_KEY)||"null");
        return value?normalizeObservedIdentity(value):null;
    }catch(e){return null;}
}

function setIdentityBaseline(source){
    try{
        localStorage.setItem(
            DEVICE_IDENTITY_BASELINE_KEY,
            JSON.stringify(normalizeObservedIdentity(source||{}))
        );
    }catch(e){}
}

function compareIdentityBaseline(previous,current){
    const idChanged=previous.device_id!==null && current.device_id!==null
        && previous.device_id!==current.device_id;
    const modbusChanged=previous.modbus_type!==null && current.modbus_type!==null
        && previous.modbus_type!==current.modbus_type;
    const portChanged=previous.modbus_type===0 && current.modbus_type===0
        && previous.server_port!==null && current.server_port!==null
        && previous.server_port!==current.server_port;
    const count=(idChanged?1:0)+(modbusChanged?1:0)+(portChanged?1:0);
    return {
        changed:count>0,
        id_changed:idChanged,
        modbus_type_changed:modbusChanged,
        server_port_changed:portChanged,
        change_type:count>1?'both':(idChanged?'device_id':(modbusChanged?'modbus_type':(portChanged?'server_port':'none')))
    };
}

function normalizeChangeType(res){
    const type = String(res.change_type || "").toLowerCase();
    if(type === "modbus_type" || type === "device_id" || type === "server_port" || type === "both") return type;

    const idChanged = boolVal(res.id_changed);
    const modbusChanged = boolVal(res.modbus_type_changed);
    const serverPortChanged = boolVal(res.server_port_changed);
    const count = (idChanged ? 1 : 0) + (modbusChanged ? 1 : 0) + (serverPortChanged ? 1 : 0);
    if(count > 1) return "both";
    if(modbusChanged) return "modbus_type";
    if(serverPortChanged) return "server_port";
    if(idChanged) return "device_id";
    return "none";
}

function buildFlowPayload(res){
    return {
        device_id: res.device_id ?? null,
        idas_id: res.idas_id ?? null,
        modbus_type: res.modbus_type ?? null,
        idas_modbus_type: res.idas_modbus_type ?? null,
        server_port: res.server_port ?? null,
        idas_server_port: res.idas_server_port ?? null,
        current_boot_id: res.system_boot_id ?? null,
        boot_id_at_change: res.system_boot_id ?? null,
        reboot_observed: false,
        change_type: normalizeChangeType(res),
        updated_at: Date.now()
    };
}

function getBannerText(changeType){
    if(changeType === "modbus_type") return t(TEXT.rebootBannerModbus);
    if(changeType === "server_port") return t(TEXT.rebootBannerPort);
    if(changeType === "both") return t(TEXT.rebootBannerBoth);
    return t(TEXT.rebootBannerDevice);
}

function getControllerReconnectText(){
    const lang=getLangCode();
    if(lang==='zh-cn') return '控制器正在重新连线，请稍候。';
    if(lang==='en-us') return 'The controller is reconnecting. Please wait.';
    return '控制器正在重新連線，請稍候。';
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

function setRebootBannerMessage(message){
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

    banner.innerHTML="🔴 "+String(message || "");
}

function syncDeviceIdentityAndReload(){
    const payload = getFlowPayload();
    if(getFlowState() === DEVICE_FLOW_SYNCING) return;
    setFlowState(DEVICE_FLOW_SYNCING, payload);
    setRebootBannerMessage(t(TEXT.syncing));

    $.ajax({
        url:"?url=Check/sync_device_identity",
        type:"POST",
        dataType:"json",
        timeout:15000,
        success:function(syncRes){
            if(
                syncRes
                && syncRes.res_type === "OK"
                && syncRes.db_consistent === true
            ){
                setIdentityBaseline(syncRes);
                clearFlowState();
                hideRebootBanner();

                // Reflect verified synchronized Server Port immediately.
                if(syncRes.server_port != null){
                    const portEl = document.getElementById('controller_server_port');
                    if(portEl){
                        portEl.value = String(syncRes.server_port);
                        portEl.dataset.rtuPort = String(syncRes.server_port);
                    }
                }

                const hashParts=window.location.href.split('#');
                let freshUrl=hashParts.shift();
                freshUrl+=(freshUrl.includes('?')?'&':'?')+'_idas_refresh='+Date.now();
                if(hashParts.length) freshUrl+='#'+hashParts.join('#');

                setTimeout(function(){
                    window.location.replace(freshUrl);
                }, 800);
                return;
            }

            deviceReloadDialogShown = false;
            setFlowState(DEVICE_FLOW_WAIT_ONLINE, payload);
            setRebootBannerMessage(t(TEXT.syncFail));
        },
        error:function(){
            deviceReloadDialogShown = false;
            setFlowState(DEVICE_FLOW_WAIT_ONLINE, payload);
            setRebootBannerMessage(t(TEXT.syncFail));
        }
    });
}

function showReloadPopup(id, needSync, changeType){
    if(deviceReloadDialogShown) return;
    deviceReloadDialogShown=true;

    const payload = needSync
        ? Object.assign(getFlowPayload(), {
            device_id: id ?? getFlowPayload().device_id ?? null,
            change_type: changeType || getFlowPayload().change_type || "device_id"
        })
        : null;

    if(needSync){
        setFlowState(DEVICE_FLOW_WAIT_POPUP, payload);
    }else{
        hideRebootBanner();
    }

    const msg = needSync
        ? t(TEXT.syncReloadMsg(id, changeType || getFlowPayload().change_type || "device_id"))
        : t(TEXT.reloadMsg(id));

    // 保留 confirm，讓全站共用 enhancer 套用新版警告視窗；
    // 顯示後移除 Cancel，最終只保留「確定」。
    const dialog = alertify.confirm(
        t(TEXT.reloadTitle),
        msg,
        function(){
            if(needSync){
                syncDeviceIdentityAndReload();
            }else{
                location.reload();
            }
        },
        function(){}
    );

    if(dialog && typeof dialog.set === "function"){
        dialog.set({
            labels:{ ok:t(TEXT.ok), cancel:"" },
            onshow:function(){
                try{
                    const root=this.elements.dialog;
                    root.classList.add("device-reload-alert", "idas-confirm-dialog");
                    window.setTimeout(function(){
                        const cancel=root.querySelector('.ajs-button.ajs-cancel');
                        if(cancel) cancel.remove();
                    },120);
                }catch(e){}
            },
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
}

function handleDeviceIdentityStatus(res){
    if(!res || res.res_type !== "OK") return;
    controllerAjaxErrorCount = 0;

    const observedIdentity=normalizeObservedIdentity(res);
    let baseline=getIdentityBaseline();
    if(!baseline){
        const savedPayload=getFlowPayload();
        if(getFlowState()!==DEVICE_FLOW_IDLE && savedPayload.idas_id!=null){
            baseline=normalizeObservedIdentity({
                device_id:savedPayload.idas_id,
                modbus_type:savedPayload.idas_modbus_type,
                server_port:savedPayload.idas_server_port
            });
            setIdentityBaseline(baseline);
        }else{
            baseline=observedIdentity;
            setIdentityBaseline(baseline);
        }
    }

    const snapshotChange=compareIdentityBaseline(baseline,observedIdentity);
    if(snapshotChange.changed){
        res=Object.assign({},res,snapshotChange,{changed:true});
    }

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

    const previousPayload = getFlowPayload();
    const payload = buildFlowPayload(res);
    if(stateBefore !== DEVICE_FLOW_IDLE && previousPayload.boot_id_at_change){
        payload.boot_id_at_change = previousPayload.boot_id_at_change;
    }
    payload.reboot_observed = boolVal(previousPayload.reboot_observed);
    showRebootBanner(changeType);

    let state = stateBefore;
    const startupRebootObserved = wasControllerRebootObserved();
    const bootChanged = !!(
        payload.boot_id_at_change
        && payload.current_boot_id
        && payload.boot_id_at_change !== payload.current_boot_id
    );

    if(startupRebootObserved || bootChanged){
        payload.reboot_observed = true;
        clearControllerRebootObserved();
    }

    // A DB write is not reboot completion. Only an observed offline transition,
    // startup recovery marker, or changed Linux Boot ID can unlock the popup.
    if(state !== DEVICE_FLOW_SYNCING){
        if(payload.reboot_observed && online){
            state = DEVICE_FLOW_WAIT_POPUP;
        }else if(payload.reboot_observed){
            state = DEVICE_FLOW_WAIT_ONLINE;
        }else{
            state = DEVICE_FLOW_WAIT_OFFLINE;
        }
        setFlowState(state, payload);
        if(!online && payload.reboot_observed){
            setRebootBannerMessage(getControllerReconnectText());
        }else if(!online){
            showRebootBanner(payload.change_type);
        }
    }

    if(payload.reboot_observed && online === true && state !== DEVICE_FLOW_SYNCING){
        showReloadPopup(hasNewId ? newId : payload.device_id, true, payload.change_type);
    }

    if(hasNewId) currentDeviceId = newId;
    lastOnlineState = online;
}

function markControllerOfflineByAjaxError(){
    controllerAjaxErrorCount += 1;
    // A changed ID/protocol can make the full communication check time out
    // before any reboot. Only the independent fast Web probe may confirm that
    // the system actually disappeared.
    lastOnlineState = false;
}

function handleFastControllerChange(res){
    fastChangeAjaxErrorCount=0;
    if(!res || res.res_type!=="OK" || !boolVal(res.changed)) return;

    const state=getFlowState();
    if(state===DEVICE_FLOW_IDLE || state===DEVICE_FLOW_WAIT_OFFLINE){
        const previous=getFlowPayload();
        const payload=Object.assign(buildFlowPayload(res),{
            reboot_observed:boolVal(previous.reboot_observed),
            boot_id_at_change:previous.boot_id_at_change||null
        });
        setFlowState(DEVICE_FLOW_WAIT_OFFLINE,payload);
        showRebootBanner(payload.change_type);
    }
}

function markFastChangeProbeOffline(){
    fastChangeAjaxErrorCount+=1;
    if(fastChangeAjaxErrorCount<2) return;

    const state=getFlowState();
    if(state===DEVICE_FLOW_WAIT_OFFLINE || state===DEVICE_FLOW_WAIT_ONLINE){
        const payload=getFlowPayload();
        payload.reboot_observed=true;
        setFlowState(DEVICE_FLOW_WAIT_ONLINE,payload);
        setRebootBannerMessage(getControllerReconnectText());
    }
}

function pollControllerChangeFast(){
    $.ajax({
        url:"?url=Check/ajax_check_device_change_fast",
        type:"POST",
        dataType:"json",
        cache:false,
        timeout:5000,
        success:handleFastControllerChange,
        error:markFastChangeProbeOffline,
        complete:function(){setTimeout(pollControllerChangeFast,2000);}
    });
}

function pollDeviceId(){
    $.ajax({
        url:"?url=Check/ajax_check_device_id",
        type:"POST",
        dataType:"json",
        timeout:15000,
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

    let restoredState=getFlowState();
    if(restoredState === DEVICE_FLOW_SYNCING){
        const restoredPayload=getFlowPayload();
        restoredPayload.reboot_observed=true;
        setFlowState(DEVICE_FLOW_WAIT_ONLINE, restoredPayload);
        restoredState=DEVICE_FLOW_WAIT_ONLINE;
    }
    if(restoredState !== DEVICE_FLOW_IDLE){
        if(restoredState === DEVICE_FLOW_WAIT_ONLINE){
            setRebootBannerMessage(getControllerReconnectText());
        }else{
            showRebootBanner(getFlowPayload().change_type || "device_id");
        }
    }

    pollControllerChangeFast();
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

/* Controller 重新上線同步提示：此流程必須確認同步，不提供取消按鈕 */
.device-reload-alert .ajs-footer .ajs-buttons .ajs-button.ajs-cancel {
    display: none !important;
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
<?php endif; ?>
