(function(){
'use strict';
function controllerEndpoint(action){const url=new URL('../public/',window.location.href);url.searchParams.set('url','Check/'+action);return url.href;}
function getCookieSafe(name){
    try{
        const m=document.cookie.match(new RegExp('(?:^|; )'+name+'=([^;]*)'));
        return m?decodeURIComponent(m[1]):null;
    }catch(e){return null;}
}
function getLangCode(){
    const lang=window.IDAS_JOB_CONFIG_UI?.language || 'zh-cn';
    return ['en-us','zh-tw','zh-cn'].includes(lang)?lang:'zh-cn';
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
            position:"sticky",top:"0",width:"100%",boxSizing:"border-box",
            background:"#c0392b",color:"#fff",padding:"12px",
            textAlign:"center",fontSize:"15px",zIndex:"99999",fontWeight:"bold"
        });
        document.body.prepend(banner);
    }

    banner.textContent="● "+getBannerText(finalChangeType);
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
            position:"sticky",top:"0",width:"100%",boxSizing:"border-box",
            background:"#c0392b",color:"#fff",padding:"12px",
            textAlign:"center",fontSize:"15px",zIndex:"99999",fontWeight:"bold"
        });
        document.body.prepend(banner);
    }

    banner.textContent="● "+String(message || "");
}

function syncDeviceIdentityAndReload(){
    const payload = getFlowPayload();
    if(getFlowState() === DEVICE_FLOW_SYNCING) return;
    setFlowState(DEVICE_FLOW_SYNCING, payload);
    setRebootBannerMessage(t(TEXT.syncing));

    $.ajax({
        url:controllerEndpoint("sync_device_identity"),
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
        url:controllerEndpoint("ajax_check_device_change_fast"),
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
        url:controllerEndpoint("ajax_check_device_id"),
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


})();
