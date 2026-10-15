(function(){
'use strict';
const config=window.IDAS_JOB_CONFIG_UI||{}, box=document.getElementById('operationMonitorStatus');
if(!box||!config.operationMonitorEndpoint)return;
const texts={
 'zh-cn':['锁附记录自动检查中（需保持页面开启）','自动检查失败：','记录','上传失败：','测试略过','已上传','已有上传记录','其他请求处理中','请保持页面开启；关闭后暂停检查，重新开启会继续处理。'],
 'zh-tw':['鎖附紀錄自動檢查中（需保持頁面開啟）','自動檢查失敗：','紀錄','上傳失敗：','測試略過','已上傳','已有上傳紀錄','其他請求處理中','請保持頁面開啟；關閉後暫停檢查，重新開啟會繼續處理。'],
 'en-us':['Checking tightening records (keep this page open)','Automatic check failed: ','Record','Upload failed: ','Test skipped','Uploaded','Existing upload history','Another request is processing','Keep this page open. Checks pause when closed and resume when reopened.']
}[config.language]||null;
const t=texts||['Checking tightening records','Check failed: ','Record','Upload failed: ','Test skipped','Uploaded','Existing upload history','Busy','Keep this page open.'];
let timer=null,active=false,stopped=false,lastResult='';
box.textContent=t[0];box.title=t[8];
async function tick(){
 if(active||stopped)return;
 active=true;
 const controller=new AbortController(),deadline=setTimeout(()=>controller.abort(),45000);
 try{
  const response=await fetch(config.operationMonitorEndpoint,{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json','X-IDAS-Page-Monitor':'1'},signal:controller.signal});
  const data=await response.json();
  if(!response.ok||data.success!==true)throw new Error(data.message||String(response.status));
  if(stopped)return;
  if(data.result){
   const r=data.result;
   const detail=r.status==='failed'?t[3]+r.message:r.message==='test_skipped'||r.message==='duplicate_test'?t[4]:r.message==='existing_history'||r.message==='duplicate'?t[6]:t[5];
   lastResult=t[2]+' #'+r.id+' · '+detail;
   box.style.color=r.status==='failed'?'#a12622':'#275c41';
  }
  box.textContent=lastResult||t[0];
 }catch(error){if(!stopped){box.textContent=t[1]+(error.name==='AbortError'?'timeout':error.message);box.style.color='#a12622';}}
 finally{clearTimeout(deadline);active=false;if(!stopped)timer=setTimeout(tick,2000);}
}
window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);});
window.addEventListener('pageshow',()=>{if(stopped){stopped=false;tick();}});
document.addEventListener('visibilitychange',()=>{if(!document.hidden&&!active&&!stopped){clearTimeout(timer);tick();}});
tick();
})();
