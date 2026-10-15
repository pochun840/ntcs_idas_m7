(function(){
'use strict';
window.createIdasMesPreview=function(config){
 const raw=String(config.language||'zh-cn').toLowerCase().replace('_','-');
 const lang=raw==='en'?'en-us':raw;
 const i18n={
  'zh-cn':['记录 ID','条码','JOB 名称','当前模式','测试模式','正式模式','缺少必要字段：','本机必要字段检查未发现缺漏。','此窗口仅供预览，不会发送至 MES。','已复制 JSON','无法复制，请手动选取 JSON','已下载 JSON','复制 JSON','下载 JSON','上传记录 ID','正在组合锁附记录…','无法取得华荣格式','华荣锁附记录格式','条码上传格式','关闭'],
  'zh-tw':['紀錄 ID','條碼','JOB 名稱','目前模式','測試模式','正式模式','缺少必要欄位：','本機必要欄位檢查未發現缺漏。','此視窗僅供預覽，不會送往 MES。','已複製 JSON','無法複製，請手動選取 JSON','已下載 JSON','複製 JSON','下載 JSON','上傳紀錄 ID','正在組合鎖附紀錄…','無法取得華榮格式','華榮鎖附紀錄格式','條碼上傳格式','關閉'],
  'en-us':['Record ID','Barcode','JOB name','Current mode','Test mode','Production mode','Missing required fields: ','No missing fields found by local checks.','Preview only. This window does not send data to MES.','JSON copied','Unable to copy. Select the JSON manually.','JSON downloaded','Copy JSON','Download JSON','Upload log ID','Preparing tightening record…','Unable to load HuaRong JSON','HuaRong tightening payload','Barcode upload payload','Close']
 };
 const t=i18n[lang]||i18n['zh-cn'],get=id=>document.getElementById(id);
 const dialog=get('mesPayloadPreviewDialog'),body=get('mesPayloadPreviewBody'),summary=get('mesPayloadPreviewSummary'),warning=get('mesPayloadPreviewWarning'),feedback=get('mesPayloadPreviewFeedback');
 const copy=get('mesPayloadPreviewCopy'),download=get('mesPayloadPreviewDownload');
 copy.textContent=t[12];download.textContent=t[13];get('mesPayloadPreviewNote').textContent=t[8];get('mesPayloadPreviewClose').textContent=t[19];
 dialog.setAttribute('aria-labelledby','mesPayloadPreviewTitle');
 let abort=null,revision=0,json='',filename='huarong_payload.json';
 function disable(value){copy.disabled=value;download.disabled=value;}
 function close(){revision++;if(abort)abort.abort();abort=null;disable(true);json='';if(dialog.open)dialog.close();}
 get('mesPayloadPreviewClose').addEventListener('click',close);
 dialog.addEventListener('cancel',close);
 function field(label,value){const cell=document.createElement('div'),name=document.createElement('small'),content=document.createElement('strong');name.textContent=label;name.style.cssText='display:block;color:#66758a;font-weight:400;margin-bottom:5px';content.textContent=value===null||value===undefined||value===''?'—':String(value);content.style.cssText='display:block;overflow-wrap:anywhere';cell.append(name,content);summary.append(cell);}
 function mode(test){field(t[3],t[test?4:5]);}
 function reset(title){revision++;if(abort)abort.abort();abort=null;json='';summary.replaceChildren();warning.textContent='';feedback.textContent='';body.textContent=t[15];disable(true);get('mesPayloadPreviewTitle').textContent=title;if(!dialog.open)dialog.showModal();return revision;}
 function render(payload,missing,file){
  json=JSON.stringify(payload,null,2);filename=file;body.textContent=json;
  warning.textContent=missing.length?t[6]+missing.join('、'):t[7];warning.style.cssText='padding:12px 20px;margin:0;border-top:1px solid #e0e7f0;background:'+(missing.length?'#fff4df;color:#865819':'#eff8f2;color:#275c41');disable(false);
 }
 function modeEvent(test){if(typeof test==='boolean')window.dispatchEvent(new CustomEvent('idas-mes-mode',{detail:{test_mode:test}}));}
 async function showOperation(id){
  const current=reset(t[17]+' · ID '+id);abort=new AbortController();const controller=abort,deadline=setTimeout(()=>controller.abort(),15000);
  try{
   const target=new URL(config.url,window.location.href);target.searchParams.set('id',id);
   const response=await fetch(target,{cache:'no-store',signal:controller.signal}),data=await response.json();
   if(current!==revision)return;
   if(!response.ok||data.success!==true||!data.payload||typeof data.payload!=='object')throw new Error(data.message||t[16]);
   field(t[0],data.id);field(t[1],data.payload.SN);field(t[2],data.payload.process_name);mode(data.test_mode===true);modeEvent(data.test_mode);
   const names={SN:t[1]+' (SN)',process_name:t[2]+' (process_name)'};
   const missing=(Array.isArray(data.missing_fields)?data.missing_fields:[]).map(name=>names[name]||String(name));
   render(data.payload,missing,'huarong_operation_'+String(id).replace(/[^0-9]/g,'')+'.json');
  }catch(error){if(current===revision){body.textContent=t[16]+'：'+(error.name==='AbortError'?'timeout':error.message);disable(true);}}
  finally{clearTimeout(deadline);if(current===revision)abort=null;}
 }
 function showBarcode(row){
  reset(t[18]);const banner=get('mesModeBanner'),test=banner&&banner.dataset.testMode==='1';
  field(t[14],row.id);field(t[1],row.barcode);mode(test);
  render({barcode:row.barcode||''},String(row.barcode||'').trim()?[]:[t[1]+' (barcode)'],'barcode_upload_'+String(row.id).replace(/[^0-9]/g,'')+'.json');
 }
 async function copyJson(){
  if(!json)return;const value=json,current=revision;let success=false;
  try{if(navigator.clipboard&&window.isSecureContext){await navigator.clipboard.writeText(value);success=true;}}catch(error){}
  if(!success&&current===revision){
   const active=document.activeElement,area=document.createElement('textarea');area.value=value;area.setAttribute('readonly','');area.style.cssText='position:fixed;top:0;left:0;width:1px;height:1px;opacity:0';dialog.append(area);
   try{area.focus();area.select();success=document.execCommand('copy');}catch(error){}finally{area.remove();if(active&&active.focus)active.focus();}
  }
  if(current===revision)feedback.textContent=t[success?9:10];
 }
 copy.addEventListener('click',copyJson);
 download.addEventListener('click',()=>{
  if(!json)return;const blob=new Blob([json+'\n'],{type:'application/json;charset=utf-8'}),url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download=filename;dialog.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);feedback.textContent=t[11];
 });
 return {showOperation,showBarcode,close};
};
})();
