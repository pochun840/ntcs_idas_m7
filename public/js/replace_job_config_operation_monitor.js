(function(){
'use strict';
const config=window.IDAS_JOB_CONFIG_UI||{},box=document.getElementById('operationMonitorStatus');
if(!box||!config.operationMonitorEndpoint)return;
const all={
 'zh-cn':['锁附记录自动检查','检查正常','检查失败','最后检查','已处理 ID','待处理','笔','需确认','保持页面开启；关闭后暂停，重新开启继续处理。','上传失败','测试略过','已上传','已有上传记录','其他请求处理中','尚未检查'],
 'zh-tw':['鎖附紀錄自動檢查','檢查正常','檢查失敗','最後檢查','已處理 ID','待處理','筆','需確認','保持頁面開啟；關閉後暫停，重新開啟繼續處理。','上傳失敗','測試略過','已上傳','已有上傳紀錄','其他請求處理中','尚未檢查'],
 'en-us':['Automatic tightening checks','Check OK','Check failed','Last checked','Processed ID','Pending','records','Needs review','Keep this page open. Checks pause when closed and resume when reopened.','Upload failed','Test skipped','Uploaded','Existing upload history','Another request is processing','Not checked yet']
};
const t=all[config.language]||all['zh-cn'];
box.replaceChildren();box.style.cssText='padding:16px;border:1px solid #cbd8e3;border-radius:12px;background:#f5f9fc;overflow-wrap:anywhere';
function element(tag,text,parent){const e=document.createElement(tag);e.textContent=text;parent.append(e);return e;}
const heading=element('strong',t[0],box),status=element('span',t[14],box);status.style.cssText='margin-left:14px;font-weight:600';
const metrics=element('div','',box);metrics.style.cssText='display:flex;flex-wrap:wrap;gap:12px 28px;margin-top:12px';
const fields={};for(const [key,label] of [['checked',t[3]],['id',t[4]],['pending',t[5]],['uncertain',t[7]]]){const cell=element('span',label+'：',metrics);fields[key]=element('strong','—',cell);}
const detail=element('div','',box);detail.style.cssText='margin-top:10px;white-space:normal';
element('small',t[8],box).style.cssText='display:block;margin-top:8px;color:#657184';
let timer=null,active=false,stopped=false,repeatSoon=false;
function showResult(r){
 if(!r)return;
 const message=r.status==='failed'?t[9]+'：'+r.message:r.message==='test_skipped'||r.message==='duplicate_test'?t[10]:r.message==='existing_history'||r.message==='duplicate'?t[12]:t[11];
 detail.textContent='ID '+(r.id||r.record_id)+' · '+message;detail.style.color=r.status==='failed'?'#a12622':'#275c41';
}
function update(data){
 if(typeof data.test_mode==='boolean')window.dispatchEvent(new CustomEvent('idas-mes-mode',{detail:{test_mode:data.test_mode}}));
 status.textContent=data.busy?t[13]:t[1];status.style.color=data.busy?'#7a5c18':'#275c41';
 const m=data.monitor;
 if(m){
  const date=m.checked_at?new Date(m.checked_at):null;
  fields.checked.textContent=date&&!isNaN(date.getTime())?date.toLocaleString(document.documentElement.lang):'—';
  fields.id.textContent=m.last_processed_id===null?'—':String(m.last_processed_id);
  fields.pending.textContent=m.pending_count+' '+t[6];fields.uncertain.textContent=m.uncertain_count+' '+t[6];
  fields.uncertain.style.color=m.uncertain_count>0?'#a12622':'';
  showResult(data.result||m.last_result);
 }else showResult(data.result);
}
async function tick(){
 if(active||stopped)return;active=true;repeatSoon=false;
 const controller=new AbortController(),deadline=setTimeout(()=>controller.abort(),45000);
 try{
  const response=await fetch(config.operationMonitorEndpoint,{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json','X-IDAS-Page-Monitor':'1'},signal:controller.signal});
  const data=await response.json();if(!response.ok||data.success!==true)throw new Error(data.message||String(response.status));
  if(!stopped)update(data);
 }catch(error){if(!stopped){status.textContent=t[2];status.style.color='#a12622';detail.textContent=error.name==='AbortError'?'timeout':error.message;detail.style.color='#a12622';}}
 finally{clearTimeout(deadline);active=false;if(!stopped)timer=setTimeout(tick,repeatSoon?0:2000);}
}
window.addEventListener('idas-upload-retry-finished',()=>{clearTimeout(timer);if(active)repeatSoon=true;else tick();});
window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);});
window.addEventListener('pageshow',()=>{if(stopped){stopped=false;tick();}});
document.addEventListener('visibilitychange',()=>{if(!document.hidden&&!active&&!stopped){clearTimeout(timer);tick();}});
tick();
})();
