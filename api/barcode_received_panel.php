<?php
$barcodeText=[
'zh-tw'=>['接收條碼','每 2 秒更新 POST 與 Modbus 條碼，顯示以 Modbus 為主。','等待接收條碼','接收時間','條碼接收成功','無法讀取接收狀態，稍後自動重試','條碼會送至設定的 MES API 取得配方；配方尚未寫入控制器。'],
'zh-cn'=>['接收条码','每 2 秒更新 POST 与 Modbus 条码，显示以 Modbus 为准。','等待接收条码','接收时间','条码接收成功','无法读取接收状态，稍后自动重试','条码会发送至设置的 MES API 取得配方；配方尚未写入控制器。'],
'en-us'=>['Received barcode','Refreshes POST and Modbus barcodes every 2 seconds. Modbus takes priority.','Waiting for a barcode','Received at','Barcode received successfully','Unable to read status. Retrying automatically.','Barcodes are sent to the configured MES API. Recipes are not yet written to the controller.'],
][$lang];
?>
<div class="barcode-card">
<div class="section-head"><div><div class="section-title"><?php echo h($barcodeText[0]); ?></div></div></div>
<div class="section-body">
<div id="receivedBarcodeValue" class="barcode-value">—</div>
<p class="barcode-time"><span id="receivedBarcodeTimeLabel"><?php echo h($barcodeText[3]); ?></span>: <span id="receivedBarcodeTime">—</span></p>
<p class="barcode-delivery" id="receivedBarcodeMes" role="status" aria-live="polite"></p>
<p id="receivedBarcodeStatus" role="status" aria-live="polite"><?php echo h($barcodeText[2]); ?></p>
<p class="section-hint"><?php echo h($barcodeText[6]); ?></p>
</div></div>
<script>
(function(){
const endpoint=<?php echo json_encode($idasBaseUrl . '/api/barcode_received.php'); ?>;
const text=<?php echo json_encode($barcodeText,JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const deliveryText=<?php echo json_encode([
'zh-tw'=>['傳送中…','傳送成功，已取得 MES 配方','傳送失敗','已接收，尚無傳送結果'],
'zh-cn'=>['发送中…','发送成功，已取得 MES 配方','发送失败','已接收，暂无发送结果'],
'en-us'=>['Sending…','Sent successfully; MES recipe received','Sending failed','Received; no delivery result available'],
][$lang],JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const sourceText=<?php echo json_encode($lang==='en-us'?['Source','Modbus unavailable; showing POST','Read at']:($lang==='zh-cn'?['来源','Modbus 读取失败，显示 POST','读取时间']:['來源','Modbus 讀取失敗，顯示 POST','讀取時間'])); ?>;
const delivery=document.getElementById('receivedBarcodeMes');
let lastDelivery=null;
const escapeMessage=value=>String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const value=document.getElementById('receivedBarcodeValue'),time=document.getElementById('receivedBarcodeTime'),status=document.getElementById('receivedBarcodeStatus');
let timer=null,stopped=false,active=false,initialized=false,lastReceipt=null,errorShown=false;
let fallbackActive=false,lastFallbackBarcode=null;
async function requestModbusFallback(barcode){
 if(fallbackActive||lastFallbackBarcode===barcode)return;
 fallbackActive=true;const abort=new AbortController();const deadline=setTimeout(()=>abort.abort(),20000);
 try{
  const response=await fetch(endpoint.replace('barcode_received.php','barcode_modbus_fallback.php'),{method:'POST',headers:{'Content-Type':'application/json'},body:'{}',signal:abort.signal});
  const result=await response.json();
  // Busy receipt can be picked up on the next poll; other results wait for a new barcode.
  if(response.status!==409)lastFallbackBarcode=result.barcode||barcode;
  if(response.status!==409&&result.success!==true&&window.IdasNotify)window.IdasNotify.error(escapeMessage(result.message||text[5]));
 }catch(e){lastFallbackBarcode=barcode;if(window.IdasNotify)window.IdasNotify.error(text[5]);}
 finally{clearTimeout(deadline);fallbackActive=false;}
}
async function refresh(){
if(stopped || active || document.hidden)return;
active=true;const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),6000);
try{const response=await fetch(endpoint,{cache:'no-store',signal:controller.signal});const body=await response.json();if(!response.ok || body.success!==true)throw new Error();
const sources=body.sources||{},mb=sources.modbus||{},post=sources.post;
if(mb.success&&mb.barcode!==lastFallbackBarcode&&!fallbackActive)lastFallbackBarcode=null;
if(body.fallback_needed&&mb.success&&mb.barcode)requestModbusFallback(mb.barcode);
if(body.data){document.getElementById('receivedBarcodeTimeLabel').textContent=body.data.source==='MODBUS'?sourceText[2]:text[3];value.textContent=body.data.barcode||'—';const date=new Date(body.data.received_at);time.textContent=isNaN(date.getTime())?body.data.received_at:date.toLocaleString(document.documentElement.lang);status.textContent=body.data.source==='POST'&&mb.success===false?sourceText[1]:'';
const receipt=body.data.receipt_id || body.data.received_at;
if(initialized && receipt!==lastReceipt && window.IdasNotify)window.IdasNotify.success(text[4]);
const state=body.data.mes_status || 'received';
const message=state==='sending'?deliveryText[0]:state==='sent'?deliveryText[1]:state==='failed'?deliveryText[2]+(body.data.mes_message?'：'+body.data.mes_message:''):deliveryText[3];
delivery.textContent=body.data.source==='MODBUS'&&!body.data.mes_status?'':message;delivery.dataset.state=state;
const deliveryId=receipt+':'+state;
if(initialized && deliveryId!==lastDelivery && ['sent','failed'].includes(state) && window.IdasNotify)window.IdasNotify[state==='sent'?'success':'error'](escapeMessage(message));
lastDelivery=deliveryId;lastReceipt=receipt;}
else{delivery.textContent='';lastDelivery=null;value.textContent='—';time.textContent='—';status.textContent=text[2];status.style.color='#456080';}
initialized=true;errorShown=false;
}catch(e){if(!errorShown && window.IdasNotify){window.IdasNotify.error(text[5]);errorShown=true;}status.textContent='';}
finally{clearTimeout(timeout);active=false;if(!stopped)timer=setTimeout(refresh,2000);}
}
document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(!document.hidden)refresh();});
window.addEventListener('pagehide',()=>{stopped=true;clearTimeout(timer);});
window.addEventListener('pageshow',()=>{if(stopped){stopped=false;refresh();}});
refresh();
})();
</script>
