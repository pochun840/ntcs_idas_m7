(function(){
'use strict';
const config=window.IDAS_JOB_CONFIG_UI||{},box=document.getElementById('operationMonitorStatus');
if(!box||!config.operationMonitorEndpoint)return;
const all={
 'zh-cn':['锁附记录自动检查','检查正常','检查失败','最后状态更新','已处理 ID','待处理','笔','需确认','保持页面开启；关闭后暂停，重新开启继续处理。','上传失败','测试略过','已上传','已有上传记录','其他请求处理中','尚未检查'],
 'zh-tw':['鎖附紀錄自動檢查','檢查正常','檢查失敗','最後狀態更新','已處理 ID','待處理','筆','需確認','保持頁面開啟；關閉後暫停，重新開啟繼續處理。','上傳失敗','測試略過','已上傳','已有上傳紀錄','其他請求處理中','尚未檢查'],
 'en-us':['Automatic tightening checks','Check OK','Check failed','Last status update','Processed ID','Pending','records','Needs review','Keep this page open. Checks pause when closed and resume when reopened.','Upload failed','Test skipped','Uploaded','Existing upload history','Another request is processing','Not checked yet']
};
const t=all[config.language]||all['zh-cn'];
box.replaceChildren();box.style.cssText='padding:16px;border:1px solid #cbd8e3;border-radius:12px;background:#f5f9fc;overflow-wrap:anywhere';
function element(tag,text,parent){const e=document.createElement(tag);e.textContent=text;parent.append(e);return e;}
const heading=element('strong',t[0],box),status=element('span',t[14],box);status.style.cssText='display:inline-block;margin-left:14px;font-weight:600;min-width:150px;min-height:24px';
const metrics=element('div','',box);metrics.style.cssText='display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:10px 20px;margin-top:12px;align-items:start';
const fields={};for(const [key,label] of [['checked',t[3]],['id',t[4]],['pending',t[5]],['uncertain',t[7]]]){const cell=element('span',label+'：',metrics);fields[key]=element('strong','—',cell);fields[key].style.cssText='display:inline-block;font-variant-numeric:tabular-nums;white-space:nowrap;min-width:'+(key==='checked'?'196px':'48px');}
const detail=element('div','',box);detail.style.cssText='margin-top:10px;white-space:normal;min-height:24px';
element('small',t[8],box).style.cssText='display:block;margin-top:8px;color:#657184';
const connectionNote=element('div','',box);connectionNote.style.cssText='margin-top:8px;min-height:40px;line-height:20px;font-size:12px;color:#865819';
const batchNotice=element('div','',box);batchNotice.style.cssText='margin-top:8px;color:#865819';
let timer=null,active=false,stopped=false,repeatSoon=false;
let hasSnapshot=false,lastHealth=null,lastMode=null;
function setColor(el,value){if(el.style.color!==value)el.style.color=value;}
function setText(el,value){if(el.textContent!==String(value))el.textContent=String(value);}
function showResult(r){
 if(!r)return;
 const collecting={'zh-tw':'產品紀錄累積中，尚未上傳 MES','zh-cn':'产品记录累积中，尚未上传 MES','en-us':'Accumulating product records; not uploaded to MES'};
 const message=r.status==='pending'?t[13]:r.message==='accumulating'?(collecting[config.language]||collecting['zh-cn']):r.status==='failed'?t[9]+'：'+r.message:r.message==='test_skipped'||r.message==='test_product_saved'||r.message==='duplicate_test'?t[10]:r.message==='existing_history'||r.message==='duplicate_product'||r.message==='duplicate'?t[12]:t[11];
 setText(detail,'ID '+(r.id||r.record_id)+' · '+message);setColor(detail,r.status==='failed'?'#a12622':'#275c41');
}
function update(data){
 const needsCount={'zh-tw':'產品計數尚待實機核對；真實鎖附紀錄暫不封包上傳，假資料測試仍可使用。','zh-cn':'产品计数尚待实机核对；真实锁附记录暂不封包上传，模拟数据测试仍可使用。','en-us':'Product counter verification is pending. Real tightening records will not be sealed or uploaded yet. Sample testing remains available.'};
 setText(batchNotice,data.product_counter_verified===false?(needsCount[config.language]||needsCount['zh-cn']):'');
 if(typeof data.test_mode==='boolean'&&lastMode!==data.test_mode){lastMode=data.test_mode;window.dispatchEvent(new CustomEvent('idas-mes-mode',{detail:{test_mode:data.test_mode}}));}
 if(!data.busy || !hasSnapshot){setText(status,data.busy?t[13]:t[1]);setColor(status,data.busy?'#7a5c18':'#275c41');}
 // A competing poll is transient; retain the previous successful snapshot.
 setText(connectionNote,data.busy&&!hasSnapshot?t[13]:'');
 const m=data.monitor;
 if(m){
  const date=m.checked_at?new Date(m.checked_at):null;
  const healthKey=JSON.stringify([m.pending_count,m.uncertain_count,m.last_processed_id,data.result||m.last_result,data.product_counter_verified,data.test_mode]);
  // checked_at advances on every poll. Paint the timestamp only for a meaningful change.
  if(!hasSnapshot || lastHealth!==healthKey){
   setText(fields.checked,date&&!isNaN(date.getTime())?date.toLocaleString(document.documentElement.lang):'—');
  }
  lastHealth=healthKey;
  setText(fields.id,m.last_processed_id===null?'—':String(m.last_processed_id));
  setText(fields.pending,m.pending_count+' '+t[6]);setText(fields.uncertain,m.uncertain_count+' '+t[6]);
  setColor(fields.uncertain,m.uncertain_count>0?'#a12622':'');
  showResult(data.result||m.last_result);
 }else showResult(data.result);
 hasSnapshot=true;
}
async function tick(){
 if(active||stopped)return;active=true;repeatSoon=false;
 const controller=new AbortController(),deadline=setTimeout(()=>controller.abort(),45000);
 try{
  const response=await fetch(config.operationMonitorEndpoint,{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json','X-IDAS-Page-Monitor':'1'},signal:controller.signal});
  const data=await response.json();if(!response.ok||data.success!==true)throw new Error(data.message||String(response.status));
  if(!stopped)update(data);
 }catch(error){if(!stopped){
  const retryNotes={'zh-tw':'暫時無法更新，保留上次結果；背景會繼續重試。','zh-cn':'暂时无法更新，保留上次结果；后台会继续重试。','en-us':'Update temporarily unavailable. Previous results are retained; checks will retry.'};
  if(hasSnapshot){setText(connectionNote,retryNotes[config.language]||retryNotes['zh-cn']);}
  else {setText(status,t[2]);setColor(status,'#a12622');setText(detail,error.name==='AbortError'?'timeout':error.message);setColor(detail,'#a12622');}
 }}
 finally{clearTimeout(deadline);active=false;if(!stopped)timer=setTimeout(tick,repeatSoon?0:2000);}
}
window.addEventListener('idas-upload-retry-finished',()=>{clearTimeout(timer);if(active)repeatSoon=true;else tick();});
window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);});
window.addEventListener('pageshow',()=>{if(stopped){stopped=false;tick();}});
document.addEventListener('visibilitychange',()=>{if(!document.hidden&&!active&&!stopped){clearTimeout(timer);tick();}});
tick();
})();
