<?php
$logText=[
'zh-tw'=>['條碼上傳紀錄','鎖附上傳紀錄','時間','紀錄 ID','條碼','來源','JOB 名稱','上傳狀態','成功','失敗','尚無紀錄','無法讀取上傳紀錄','上一頁','下一頁','頁','共','筆','每頁 10 筆','查看華榮格式','關閉','正在組合鎖附紀錄…','無法取得華榮格式','操作','華榮鎖附紀錄格式','測試略過','查看上傳格式','條碼上傳格式','控制器'],
'zh-cn'=>['条码上传记录','锁附上传记录','时间','记录 ID','条码','来源','JOB 名称','上传状态','成功','失败','暂无记录','无法读取上传记录','上一页','下一页','页','共','笔','每页 10 条','查看华荣格式','关闭','正在组合锁附记录…','无法取得华荣格式','操作','华荣锁附记录格式','测试略过','查看上传格式','条码上传格式','控制器'],
'en-us'=>['Barcode upload history','Tightening upload history','Time','Record ID','Barcode','Source','JOB name','Upload status','Success','Failed','No records','Unable to load upload history','Previous','Next','Page','Total','records','10 per page','View HuaRong JSON','Close','Preparing tightening record…','Unable to load HuaRong JSON','Action','HuaRong tightening payload','Test skipped','View upload JSON','Barcode upload payload','Controller'],
][$lang];
?>
<?php foreach (['barcode','tightening'] as $kind): ?>
<section class="section-card upload-history-card">
<div class="section-head"><div class="section-title"><?php echo h($logText[$kind==='barcode'?0:1]); ?></div></div>
<div class="section-body upload-history-body"><div class="upload-table-scroll">
<table class="upload-history-table">
<thead><tr><?php foreach ($kind==='barcode'?[2,4,5,7,22]:[2,3,4,6,7,22] as $index): ?><th ><?php echo h($logText[$index]); ?></th><?php endforeach; ?></tr></thead>
<tbody id="uploadLog-<?php echo h($kind); ?>"></tbody>
</table></div>
<div class="upload-pagination">
<span id="uploadLogPage-<?php echo h($kind); ?>" role="status" aria-live="polite"></span>
<button type="button" class="btn btn-secondary" id="uploadLogPrev-<?php echo h($kind); ?>" disabled><?php echo h($logText[12]); ?></button>
<button type="button" class="btn btn-secondary" id="uploadLogNext-<?php echo h($kind); ?>" disabled><?php echo h($logText[13]); ?></button>
</div>
</div></section>
<?php endforeach; ?>
<dialog id="mesPayloadPreviewDialog" style="width:min(760px,calc(100% - 32px));max-height:85vh;padding:0;border:1px solid #d5e0ed;border-radius:14px;box-shadow:0 18px 70px #182e4f40">
<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 20px;background:#f4f7fb;border-bottom:1px solid #e0e7f0"><strong id="mesPayloadPreviewTitle"><?php echo h($logText[23]); ?></strong><button type="button" class="btn btn-secondary" id="mesPayloadPreviewClose"><?php echo h($logText[19]); ?></button></div>
<pre id="mesPayloadPreviewBody" style="margin:0;padding:20px;max-height:65vh;overflow:auto;white-space:pre-wrap;overflow-wrap:anywhere;font:13px/1.7 Consolas,monospace;background:#fbfcfe" role="status" aria-live="polite"></pre>
</dialog>
<p id="uploadLogError" role="status" aria-live="polite"></p>
<script>
(function(){
const labels=<?php echo json_encode($logText,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
const url=<?php echo json_encode($idasBaseUrl.'/api/mes_upload_logs.php',JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
let timer=null,active=false,stopped=false,revision=0;
const kinds=['barcode','tightening'];
const previewDialog=document.getElementById('mesPayloadPreviewDialog'),previewBody=document.getElementById('mesPayloadPreviewBody');
previewDialog.setAttribute('aria-labelledby','mesPayloadPreviewTitle');
let previewAbort=null,previewRevision=0;
function closePreview(){previewRevision++;if(previewAbort)previewAbort.abort();previewDialog.close();}
document.getElementById('mesPayloadPreviewClose').addEventListener('click',closePreview);
previewDialog.addEventListener('cancel',()=>{previewRevision++;if(previewAbort)previewAbort.abort();});
function showBarcodePayload(row){
 previewRevision++;if(previewAbort)previewAbort.abort();previewAbort=null;
 document.getElementById('mesPayloadPreviewTitle').textContent=labels[26];
 previewBody.textContent=JSON.stringify({barcode:row.barcode||''},null,2);
 if(!previewDialog.open)previewDialog.showModal();
}
async function showPayload(id){
 if(previewAbort)previewAbort.abort();
 const current=++previewRevision;previewAbort=new AbortController();const abort=previewAbort;
 document.getElementById('mesPayloadPreviewTitle').textContent=labels[23]+' · ID '+id;
 previewBody.textContent=labels[20];if(!previewDialog.open)previewDialog.showModal();
 const deadline=setTimeout(()=>abort.abort(),15000);
 try{
  const target=new URL(url.replace('mes_upload_logs.php','operation_mes_preview.php'),window.location.href);target.searchParams.set('id',id);
  const response=await fetch(target,{cache:'no-store',signal:abort.signal}),data=await response.json();
  if(current!==previewRevision)return;
  if(!response.ok||data.success!==true)throw new Error(data.message||labels[21]);
  previewBody.textContent=JSON.stringify(data.payload,null,2);
 }catch(error){if(current===previewRevision)previewBody.textContent=labels[21]+(error.name==='AbortError'?'':'：'+error.message);}
 finally{clearTimeout(deadline);if(current===previewRevision)previewAbort=null;}
}

const state={barcode:{page:1,pages:1,anchor:0},tightening:{page:1,pages:1,anchor:0}};
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
 document.getElementById('uploadLogPage-'+kind).textContent=labels[14]+' '+meta.page+' / '+meta.pages+' · '+labels[15]+' '+meta.total+' '+labels[16]+' · '+labels[17];
 document.getElementById('uploadLogPrev-'+kind).disabled=meta.page<=1;
 document.getElementById('uploadLogNext-'+kind).disabled=meta.page>=meta.pages;
}
function render(kind,rows){
 const body=document.getElementById('uploadLog-'+kind);body.replaceChildren();
 if(!rows.length){const tr=document.createElement('tr'),td=document.createElement('td');td.colSpan=kind==='barcode'?5:6;td.textContent=labels[10];td.style.padding='10px';tr.append(td);body.append(tr);return;}
 for(const row of rows){
  const date=new Date(row.created_at),time=isNaN(date.getTime())?row.created_at:date.toLocaleString(document.documentElement.lang);
  const values=kind==='barcode'?[time,row.barcode,String(row.source||'').toUpperCase()==='MODBUS'?labels[27]:row.source]:[time,row.reference,row.barcode,row.job_name];
  values.push(row.status==='test_skipped'?labels[24]:row.status==='sent'?labels[8]:labels[9]);
  const tr=document.createElement('tr');
  values.forEach((value,index)=>{const td=document.createElement('td');td.textContent=value||'—';if(index===values.length-1){const badge=document.createElement('span');badge.className='upload-status '+(row.status==='test_skipped'?'is-test':row.status==='sent'?'is-success':'is-failed');badge.textContent=value;td.replaceChildren(badge);}tr.append(td);});{const cell=document.createElement('td'),button=document.createElement('button');button.type='button';button.className='btn btn-secondary';button.textContent=kind==='barcode'?labels[25]:labels[18];button.addEventListener('click',()=>kind==='barcode'?showBarcodePayload(row):showPayload(row.reference));cell.append(button);tr.append(cell);}body.append(tr);
 }
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
  if(requestedRevision!==revision||stopped)return;
  for(const kind of kinds){render(kind,data[kind]);renderPagination(kind,data.pagination[kind]);}
  document.getElementById('uploadLogError').textContent='';
 }catch(e){if(requestedRevision===revision&&!stopped)document.getElementById('uploadLogError').textContent=labels[11];}
 finally{clearTimeout(deadline);active=false;if(!stopped)timer=setTimeout(refresh,requestedRevision!==revision?0:2000);}
}
document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(!document.hidden)refresh();});
window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);previewRevision++;if(previewAbort)previewAbort.abort();});
window.addEventListener('pageshow',()=>{if(stopped){stopped=false;refresh();}});
refresh();
})();
</script>
