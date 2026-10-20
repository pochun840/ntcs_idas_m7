<?php
$logText=[
'zh-tw'=>['條碼上傳紀錄','鎖附上傳紀錄','時間','紀錄 ID','條碼','來源','JOB 名稱','上傳狀態','MES 已保存','失敗','尚無紀錄','無法讀取上傳紀錄','上一頁','下一頁','頁','共','筆','每頁 10 筆','查看華榮格式','關閉','正在組合鎖附紀錄…','無法取得華榮格式','操作','華榮鎖附紀錄格式','測試略過','查看上傳格式','條碼上傳格式','控制器','失敗原因','重送','重送中…','重送完成','重送失敗','此歷史紀錄未保存失敗原因','重送逾時，請先確認 MES 與上傳紀錄','測試略過：未送往 MES','假資料','假資料完成（未送 MES）','查看回傳格式'],
'zh-cn'=>['条码上传记录','锁附上传记录','时间','记录 ID','条码','来源','JOB 名称','上传状态','MES 已保存','失败','暂无记录','无法读取上传记录','上一页','下一页','页','共','笔','每页 10 条','查看华荣格式','关闭','正在组合锁附记录…','无法取得华荣格式','操作','华荣锁附记录格式','测试略过','查看上传格式','条码上传格式','控制器','失败原因','重送','重送中…','重送完成','重送失败','此历史记录未保存失败原因','重送超时，请先确认 MES 与上传记录','测试略过：未送往 MES','模拟数据','模拟完成（未送 MES）','查看回传格式'],
'en-us'=>['Barcode upload history','Tightening upload history','Time','Record ID','Barcode','Source','JOB name','Upload status','MES saved','Failed','No records','Unable to load upload history','Previous','Next','Page','Total','records','10 per page','View HuaRong JSON','Close','Preparing tightening record…','Unable to load HuaRong JSON','Action','HuaRong tightening payload','Test skipped','View upload JSON','Barcode upload payload','Controller','Failure reason','Retry','Retrying…','Retry completed','Retry failed','No failure reason saved for this historical attempt','Retry timed out; check MES and upload history before retrying','Test skipped: nothing sent to MES','Sample data','Sample completed (not sent to MES)','View response'],
][$lang];
?>
<style>
.upload-history-table th,.upload-history-table td{overflow-wrap:anywhere}
.upload-history-table td:last-child{min-width:0!important}
.upload-history-table td:last-child .btn{max-width:100%;white-space:normal}
.upload-history-card .upload-pagination{min-height:74px;box-sizing:border-box;display:flex;align-items:center;flex-wrap:nowrap;gap:8px}
.upload-history-card .upload-pagination>span{flex:1;min-width:0;font-variant-numeric:tabular-nums}
.upload-history-card .upload-pagination>.btn{flex:0 0 auto;min-width:88px;transition:none!important;animation:none!important}
</style>
<?php foreach (['barcode','tightening'] as $kind): ?>
<section class="section-card upload-history-card">
<div class="section-head"><div class="section-title"><?php echo h($logText[$kind==='barcode'?0:1]); ?></div></div>
<div class="section-body upload-history-body"><div class="upload-table-scroll">
<table class="upload-history-table" style="table-layout:fixed;width:100%;min-width:1200px">
<colgroup><?php foreach ($kind==='barcode'?[16,20,10,15,16,23]:[15,7,17,12,13,17,19] as $width): ?><col style="width:<?php echo (int)$width; ?>%"><?php endforeach; ?></colgroup>
<thead><tr><?php foreach ($kind==='barcode'?[2,4,5,7,28,22]:[2,3,4,6,7,28,22] as $index): ?><th ><?php echo h($logText[$index]); ?></th><?php endforeach; ?></tr></thead>
<tbody id="uploadLog-<?php echo h($kind); ?>"></tbody>
</table></div>
<div class="upload-pagination">
<span id="uploadLogPage-<?php echo h($kind); ?>" role="status" aria-live="polite"></span>
<button type="button" class="btn btn-secondary" id="uploadLogPrev-<?php echo h($kind); ?>" disabled><?php echo h($logText[12]); ?></button>
<button type="button" class="btn btn-secondary" id="uploadLogNext-<?php echo h($kind); ?>" disabled><?php echo h($logText[13]); ?></button>
</div>
</div></section>
<?php endforeach; ?>
<dialog id="mesPayloadPreviewDialog" style="width:min(880px,calc(100% - 32px));max-height:85vh;overflow:auto;padding:0;border:1px solid #d5e0ed;border-radius:14px;box-shadow:0 18px 70px #182e4f40">
<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 20px;background:#f4f7fb;border-bottom:1px solid #e0e7f0"><strong id="mesPayloadPreviewTitle"><?php echo h($logText[23]); ?></strong><button type="button" class="btn btn-secondary" id="mesPayloadPreviewClose"><?php echo h($logText[19]); ?></button></div>
<div id="mesPayloadPreviewSummary" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:16px;padding:18px 20px;background:#fff"></div>
<p id="mesPayloadPreviewWarning" role="status" aria-live="polite"></p>
<div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;padding:12px 20px;border-top:1px solid #e0e7f0">
<button id="mesPayloadPreviewCopy" type="button" class="btn btn-secondary" disabled>JSON</button>
<button id="mesPayloadPreviewDownload" type="button" class="btn btn-secondary" disabled>JSON</button>
<span id="mesPayloadPreviewFeedback" role="status" aria-live="polite" style="font-size:13px;color:#275c41"></span>
</div>
<pre id="mesPayloadPreviewBody" style="margin:0;padding:20px;max-height:42vh;overflow:auto;white-space:pre-wrap;overflow-wrap:anywhere;font:16px/1.75 Consolas,monospace;background:#fbfcfe;color:#172c46"></pre>
<p id="mesPayloadPreviewNote" style="padding:12px 20px;margin:0;border-top:1px solid #e0e7f0;font-size:12px;color:#66758a"></p>
</dialog>
<p id="uploadRetryFeedback" role="status" aria-live="polite" style="overflow-wrap:anywhere"></p>
<p id="uploadLogError" role="status" aria-live="polite"></p>
<script src="<?php echo h($idasBaseUrl); ?>/public/js/mes_payload_preview.js?v=<?php echo (int)@filemtime(dirname(__DIR__).'/public/js/mes_payload_preview.js'); ?>"></script>
<script>
(function(){
const labels=<?php echo json_encode($logText,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
const url=<?php echo json_encode($idasBaseUrl.'/api/mes_upload_logs.php',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
let timer=null,active=false,stopped=false,revision=0;
const kinds=['barcode','tightening'];
const retrying=new Set();
const rowCache={barcode:new Map(),tightening:new Map()};
function setText(el,value){if(el.textContent!==String(value))el.textContent=String(value);}
const preview=window.createIdasMesPreview({language:document.documentElement.lang,url:url.replace('mes_upload_logs.php','operation_mes_preview.php')});
function showBarcodePayload(row){preview.showBarcode(row);}
function showPayload(id){preview.showOperation(id);}

const state={barcode:{page:1,pages:1,anchor:0},tightening:{page:1,pages:1,anchor:0}};
async function retryUpload(row,kind){
 const key=String(row.id);if(retrying.has(key))return;
 retrying.add(key);const feedback=document.getElementById('uploadRetryFeedback');feedback.textContent=labels[30];
 document.querySelectorAll('[data-retry-log]').forEach(button=>{if(button.dataset.retryLog===key){button.disabled=true;button.textContent=labels[30];}});
 const controller=new AbortController(),deadline=setTimeout(()=>controller.abort(),45000);
 try{
  const target=new URL(url.replace('mes_upload_logs.php','mes_upload_retry.php'),window.location.href);
  const response=await fetch(target,{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json','X-IDAS-Page-Monitor':'1'},body:JSON.stringify({log_id:Number(row.id)}),signal:controller.signal});
  const data=await response.json();
  if(!response.ok||data.success!==true)throw new Error(data.message||String(response.status));
  if(typeof data.test_mode==='boolean')window.dispatchEvent(new CustomEvent('idas-mes-mode',{detail:{test_mode:data.test_mode}}));
  feedback.textContent=(data.test_mode?labels[35]:labels[31])+' · '+(kind==='tightening'?'ID '+row.reference:row.barcode);
  feedback.style.color='#275c41';
 }catch(error){feedback.textContent=error.name==='AbortError'?labels[34]:labels[32]+'：'+error.message;feedback.style.color='#a12622';}
 finally{
  clearTimeout(deadline);retrying.delete(key);if(rowCache[kind].has(key))rowCache[kind].get(key).signature=null;state[kind].page=1;state[kind].anchor=0;revision++;clearTimeout(timer);refresh();
  window.dispatchEvent(new Event('idas-upload-retry-finished'));
 }
}

function navigation(kind,delta){
 const item=state[kind],next=Math.max(1,Math.min(item.pages,item.page+delta));
 if(next===item.page)return;
 item.page=next;if(next===1)item.anchor=0;
 revision++;clearTimeout(timer);refresh();
}
for(const kind of kinds){
 document.getElementById('uploadLogPrev-'+kind).addEventListener('click',()=>navigation(kind,-1));
 document.getElementById('uploadLogNext-'+kind).addEventListener('click',()=>navigation(kind,1));
}
function renderPagination(kind,meta){
 Object.assign(state[kind],meta);
 setText(document.getElementById('uploadLogPage-'+kind),labels[14]+' '+meta.page+' / '+meta.pages+' · '+labels[15]+' '+meta.total+' '+labels[16]+' · '+labels[17]);
 const prev=document.getElementById('uploadLogPrev-'+kind),next=document.getElementById('uploadLogNext-'+kind);
 if(prev.disabled!==(meta.page<=1))prev.disabled=meta.page<=1;
 if(next.disabled!==(meta.page>=meta.pages))next.disabled=meta.page>=meta.pages;
}
function createRow(kind,row){
  const date=new Date(row.created_at),time=isNaN(date.getTime())?row.created_at:date.toLocaleString(document.documentElement.lang);
  const values=kind==='barcode'?[time,row.barcode,row.status==='mock_received'?labels[36]:(String(row.source||'').toUpperCase()==='MODBUS'?labels[27]:row.source)]:[time,row.reference,row.barcode,row.job_name];
  values.push(row.status==='mock_received'?labels[37]:row.status==='test_skipped'?labels[24]:row.status==='sent'?labels[8]:labels[9]);
  const tr=document.createElement('tr');
  values.forEach((value,index)=>{const td=document.createElement('td');td.textContent=value||'—';if(index===values.length-1){const badge=document.createElement('span');badge.className='upload-status '+(row.status==='test_skipped'||row.status==='mock_received'?'is-test':row.status==='sent'?'is-success':'is-failed');badge.textContent=value;td.replaceChildren(badge);}tr.append(td);});{const cell=document.createElement('td'),button=document.createElement('button');button.type='button';button.className='btn btn-secondary';button.textContent=kind==='barcode'?labels[25]:labels[18];button.addEventListener('click',()=>kind==='barcode'?showBarcodePayload(row):(row.source==='MOCK'?preview.showMockOperation(row):['PRODUCT','TEST_PRODUCT'].includes(row.source)&&row.request_json?preview.showProduct(row):showPayload(row.reference)));cell.append(button);
  if(kind==='barcode'){const responseButton=document.createElement('button');responseButton.type='button';responseButton.className='btn btn-secondary';responseButton.textContent=labels[38];responseButton.style.margin='6px 0 0 6px';responseButton.addEventListener('click',()=>preview.showBarcodeResponse(row));cell.append(responseButton);}
  if(row.can_retry){const retry=document.createElement('button');retry.type='button';retry.className='btn btn-secondary';retry.dataset.retryLog=String(row.id);retry.disabled=retrying.has(String(row.id));retry.textContent=retry.disabled?labels[30]:labels[29];retry.style.margin='6px 0 0 6px';retry.addEventListener('click',()=>retryUpload(row,kind));cell.append(retry);}
  const reason=document.createElement('td');reason.textContent=row.message||(row.status==='failed'||row.status==='test_failed'?labels[33]:'—');reason.style.cssText='min-width:180px;max-width:320px;white-space:normal;overflow-wrap:anywhere;text-align:left';tr.append(reason);tr.append(cell);}return tr;
}
function render(kind,rows){
 const body=document.getElementById('uploadLog-'+kind),cache=rowCache[kind];
 if(!rows.length){
  if(body.dataset.empty==='1')return;
  cache.clear();body.replaceChildren();body.dataset.empty='1';
  const tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=kind==='barcode'?6:7;td.textContent=labels[10];td.style.padding='10px';tr.append(td);body.append(tr);return;
 }
 if(body.dataset.empty==='1'){body.replaceChildren();delete body.dataset.empty;}
 const keep=new Set(rows.map(row=>String(row.id)));
 for(const [key,item] of cache){if(!keep.has(key)){item.node.remove();cache.delete(key);}}
 rows.forEach((row,index)=>{
  const key=String(row.id),signature=JSON.stringify([row,retrying.has(key)]),old=cache.get(key);
  let node=old&&old.node;
  if(!old||old.signature!==signature){
   const active=document.activeElement;
   const focusIndex=old&&old.node.contains(active)?Array.from(old.node.querySelectorAll('button')).indexOf(active):-1;
   node=createRow(kind,row);node.dataset.logId=key;
   if(old)old.node.replaceWith(node);
   cache.set(key,{node,signature});
   if(focusIndex>=0){const button=node.querySelectorAll('button')[focusIndex];if(button&&!button.disabled)button.focus({preventScroll:true});}
  }
  if(body.children[index]!==node)body.insertBefore(node,body.children[index]||null);
 });
}
async function refresh(){
 if(active||stopped||document.hidden)return;
 active=true;const requestedRevision=revision;
 const controller=new AbortController(),deadline=setTimeout(()=>controller.abort(),6000);
 const requestUrl=new URL(url,window.location.href);
 for(const kind of kinds){
  requestUrl.searchParams.set(kind+'_page',state[kind].page);
  requestUrl.searchParams.set(kind+'_anchor',state[kind].page===1?0:state[kind].anchor);
 }
 try{
  const response=await fetch(requestUrl,{cache:'no-store',signal:controller.signal}),data=await response.json();
  if(!response.ok||data.success!==true)throw new Error();
  if(typeof data.test_mode==='boolean')window.dispatchEvent(new CustomEvent('idas-mes-mode',{detail:{test_mode:data.test_mode}}));
  if(requestedRevision!==revision||stopped)return;
  for(const kind of kinds){render(kind,data[kind]);renderPagination(kind,data.pagination[kind]);}
  setText(document.getElementById('uploadLogError'),'');
 }catch(e){if(requestedRevision===revision&&!stopped)setText(document.getElementById('uploadLogError'),labels[11]);}
 finally{clearTimeout(deadline);active=false;if(!stopped)timer=setTimeout(refresh,requestedRevision!==revision?0:2000);}
}
document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(!document.hidden)refresh();});
window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);preview.close();});
window.addEventListener('pageshow',()=>{if(stopped){stopped=false;refresh();}});
refresh();
})();
</script>
