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
 let abort=null,revision=0,json='',filename='huarong_payload.json',mime='application/json';
 function disable(value){copy.disabled=value;download.disabled=value;}
 function close(){revision++;if(abort)abort.abort();abort=null;disable(true);json='';if(dialog.open)dialog.close();}
 get('mesPayloadPreviewClose').addEventListener('click',close);
 dialog.addEventListener('cancel',close);
 function field(label,value){const cell=document.createElement('div'),name=document.createElement('small'),content=document.createElement('strong');name.textContent=label;name.style.cssText='display:block;color:#66758a;font-weight:400;margin-bottom:5px';content.textContent=value===null||value===undefined||value===''?'—':String(value);content.style.cssText='display:block;overflow-wrap:anywhere';cell.append(name,content);summary.append(cell);}
 function mode(test){field(t[3],t[test?4:5]);}
 function reset(title){revision++;if(abort)abort.abort();abort=null;json='';summary.replaceChildren();warning.textContent='';feedback.textContent='';body.textContent=t[15];disable(true);get('mesPayloadPreviewTitle').textContent=title;if(!dialog.open)dialog.showModal();return revision;}
 function render(payload,missing,file){
  mime='application/json';json=JSON.stringify(payload,null,2);filename=file;body.textContent=json;
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
   if(data.mock===true){
    const notes={'zh-tw':'測試模式：工單資料來自條碼模擬測試；runParam_inf 使用截至完成 ID 的整個產品鎖附紀錄，不會送往 MES。','zh-cn':'测试模式：工单信息来自条码模拟测试；runParam_inf 使用截至完成 ID 的整个产品锁附记录，不会发送至 MES。','en-us':'Test mode: work order data comes from the barcode sample. runParam_inf contains all product records through the terminal ID. Nothing is sent to MES.'};
    warning.textContent=(notes[lang]||notes['zh-cn'])+(missing.length?' '+t[6]+missing.join('、'):'');
    warning.style.cssText='padding:12px 20px;margin:0;background:#fff4df;color:#865819';
   }
  }catch(error){if(current===revision){body.textContent=t[16]+'：'+(error.name==='AbortError'?'timeout':error.message);disable(true);}}
  finally{clearTimeout(deadline);if(current===revision)abort=null;}
 }
 function showProduct(row){
  reset(t[17]);field(t[14],row.id);field(t[0],row.reference);field(t[1],row.barcode);field(t[2],row.job_name);
  const test=row.source==='TEST_PRODUCT';mode(test);
  let payload=null;try{payload=JSON.parse(row.request_json||'null');}catch(_){}
  if(!payload||typeof payload!=='object'||Array.isArray(payload)){body.textContent=t[16];disable(true);return;}
  render(payload,[],'product_'+String(row.reference).replace(/[^0-9]/g,'')+'.json');
  const notes={'zh-tw':'此為該產品整包上傳時保存的報文快照；包含重鎖紀錄。測試模式不送 MES。','zh-cn':'这是该产品整包上传时保存的报文快照，包含重锁记录。测试模式不发送 MES。','en-us':'Saved product report snapshot, including retry attempts. Test mode does not send data to MES.'};
  warning.textContent=notes[lang]||notes['zh-cn'];
 }
 function showMockOperation(row){
  reset(t[17]);field(t[14],row.id);field(t[1],row.barcode);field(t[2],row.job_name);mode(true);
  let payload=null;try{payload=JSON.parse(row.request_json||'null');}catch(_){}
  if(!payload||typeof payload!=='object'||Array.isArray(payload)){body.textContent=t[16];disable(true);return;}
  render(payload,[],'mock_tightening_'+String(row.id).replace(/[^0-9]/g,'')+'.json');
  const notes={'zh-tw':'模擬鎖附上傳：工單來自同次條碼假資料；runParam_inf 為模擬值，未操作控制器、未傳送 MES。','zh-cn':'模拟拧紧上传：工单来自同次条码模拟数据；runParam_inf 为模拟值，未操作控制器、未发送 MES。','en-us':'Sample tightening report: work order data comes from the same barcode sample. All measurements are simulated. No controller action or MES request was made.'};
  warning.textContent=notes[lang]||notes['zh-cn'];warning.style.cssText='padding:12px 20px;margin:0;background:#fff4df;color:#865819';
 }
 function showBarcode(row){
  reset(t[18]);const banner=get('mesModeBanner'),test=banner&&banner.dataset.testMode==='1';
  field(t[14],row.id);field(t[1],row.barcode);mode(test);
  let payload=null;
  try { if(row.request_json) payload=JSON.parse(row.request_json); } catch (_) {}
  const historic=!payload || typeof payload!=='object' || Array.isArray(payload) || !Object.prototype.hasOwnProperty.call(payload,'station');
  if(historic)payload={barcode:row.barcode||'',station:null};
  const missing=[];
  if(!String(payload.barcode||'').trim())missing.push(t[1]+' (barcode)');
  if(payload.station===null||payload.station===undefined||String(payload.station).trim()==='')missing.push('station');
  render(payload,missing,'barcode_upload_'+String(row.id).replace(/[^0-9]/g,'')+'.json');
  if(historic){
   const notes={'zh-tw':'此歷史紀錄未保存當次站號，station 顯示 null。請重新測試產生新紀錄；不以目前站號回填歷史資料。','zh-cn':'此历史记录未保存当次站号，station 显示 null。请重新测试生成新记录；不以当前站号回填历史数据。','en-us':'The station was not saved for this historical record, so station is null. Run a new test to create a new record. Current settings are not used to backfill history.'};
   warning.textContent=notes[lang]||notes['zh-cn'];
  }
 }
 // Format JSON tokens without converting numeric values or rewriting strings.
 function formatResponseJson(raw){
  JSON.parse(raw);
  const tokens=raw.match(/"(?:\\.|[^"\\])*"|[{}\[\],:]|[^\s{}\[\],:]+/g)||[];
  let output='',depth=0;
  const newline=()=>{output+='\n'+'  '.repeat(depth);};
  tokens.forEach((token,index)=>{
   if(token==='{'||token==='['){output+=token;depth++;if(tokens[index+1]!=='}'&&tokens[index+1]!==']')newline();}
   else if(token==='}'||token===']'){depth--;if(tokens[index-1]!=='{'&&tokens[index-1]!=='[')newline();output+=token;}
   else if(token===','){output+=token;newline();}
   else if(token===':')output+=': ';
   else output+=token;
  });
  return output;
 }
 function showBarcodeResponse(row){
  const texts={
   'zh-tw':['條碼回傳格式','此歷史紀錄未保存回傳內容。','測試模式未呼叫 MES，無回傳資料。','假資料回應；未呼叫 MES。','MES 原始回應','未取得 MES 回應，請查看錯誤原因。','尚未呼叫 MES，無回傳資料。','MES 回傳空內容。','MES 回傳非 JSON，以下保留原始文字。'],
   'zh-cn':['条码回传格式','此历史记录未保存回传内容。','测试模式未调用 MES，无回传数据。','模拟回应；未调用 MES。','MES 原始回应','未取得 MES 回应，请查看错误原因。','尚未调用 MES，无回传数据。','MES 回传空内容。','MES 回传非 JSON，以下保留原始文本。'],
   'en-us':['Barcode response','No response was saved for this historical record.','MES was not called in test mode. No response is available.','Sample response. MES was not called.','Original MES response','No MES response received. See the error details.','MES was not called. No response is available.','MES returned an empty body.','MES returned non-JSON content. Original text is shown below.']
  };
  const labels=texts[lang]||texts['zh-cn'];
  reset(labels[0]);field(t[14],row.id);field(t[1],row.barcode);
  let details=null;
  try {if(row.response_json)details=JSON.parse(row.response_json);} catch (_) {}
  if(!details||typeof details!=='object'||Array.isArray(details)){
   body.textContent=labels[1];warning.textContent=labels[1];return;
  }
  mode(details.kind==='mock'||details.kind==='test_skipped');
  if(details.http_status)field('HTTP',details.http_status);
  const hasBody=typeof details.raw_body==='string'&&details.raw_body!=='';
  const note=details.kind==='test_skipped'?labels[2]:details.kind==='mock'?labels[3]:details.kind==='not_called'?labels[6]:details.kind==='transport_error'?labels[5]:hasBody?labels[4]:labels[7];
  warning.style.cssText='padding:12px 20px;margin:0;background:#fff4df;color:#865819';
  warning.textContent=note;
  if(!hasBody){body.textContent=note+(row.message?'\n'+row.message:'');return;}
  json=details.raw_body;
  let isJson=true;try{json=formatResponseJson(json);}catch(_){isJson=false;}
  body.textContent=json;
  mime=isJson?'application/json':'text/plain';
  filename='barcode_response_'+String(row.id).replace(/[^0-9]/g,'')+(isJson?'.json':'.txt');
  if(!isJson)warning.textContent=note+' '+labels[8];
  disable(false);
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
  if(!json)return;const blob=new Blob([json+'\n'],{type:mime+';charset=utf-8'}),url=URL.createObjectURL(blob),link=document.createElement('a');link.href=url;link.download=filename;dialog.append(link);link.click();link.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);feedback.textContent=t[11];
 });
 return {showProduct,showOperation,showMockOperation,showBarcode,showBarcodeResponse,close};
};
})();
