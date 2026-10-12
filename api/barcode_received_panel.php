<?php
$barcodeText=[
'zh-tw'=>['接收條碼','顯示本機 iDAS 最近成功接收的條碼，每 2 秒自動更新。','等待接收條碼','接收時間','條碼接收成功','無法讀取接收狀態，稍後自動重試','條碼會送至設定的 MES API 取得配方；配方尚未寫入控制器。'],
'zh-cn'=>['接收条码','显示本机 iDAS 最近成功接收的条码，每 2 秒自动更新。','等待接收条码','接收时间','条码接收成功','无法读取接收状态，稍后自动重试','条码会发送至设置的 MES API 取得配方；配方尚未写入控制器。'],
'en-us'=>['Received barcode','Latest barcode successfully received by this iDAS host. Refreshes every 2 seconds.','Waiting for a barcode','Received at','Barcode received successfully','Unable to read status. Retrying automatically.','Barcodes are sent to the configured MES API. Recipes are not yet written to the controller.'],
][$lang];
?>
<div style="border-top:1px solid #d9e3f1">
<div class="section-head"><div><div class="section-title"><?php echo h($barcodeText[0]); ?></div><div class="section-hint"><?php echo h($barcodeText[1]); ?></div></div></div>
<div class="section-body">
<div id="receivedBarcodeValue" style="font-size:26px;font-weight:700;overflow-wrap:anywhere;padding:16px;background:#f5f8fc;border:1px solid #d9e3f1;border-radius:8px;white-space:pre-wrap">—</div>
<p><?php echo h($barcodeText[3]); ?>: <span id="receivedBarcodeTime">—</span></p>
<p id="receivedBarcodeMes" role="status" aria-live="polite"></p>
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
const delivery=document.getElementById('receivedBarcodeMes');
let lastDelivery=null;
const escapeMessage=value=>String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const value=document.getElementById('receivedBarcodeValue'),time=document.getElementById('receivedBarcodeTime'),status=document.getElementById('receivedBarcodeStatus');
let timer=null,stopped=false,active=false,initialized=false,lastReceipt=null,errorShown=false;
async function refresh(){
if(stopped || active || document.hidden)return;
active=true;const controller=new AbortController();const timeout=setTimeout(()=>controller.abort(),6000);
try{const response=await fetch(endpoint,{cache:'no-store',signal:controller.signal});const body=await response.json();if(!response.ok || body.success!==true)throw new Error();
if(body.data){value.textContent=body.data.barcode;const date=new Date(body.data.received_at);time.textContent=isNaN(date.getTime())?body.data.received_at:date.toLocaleString(document.documentElement.lang);status.textContent='';
const receipt=body.data.receipt_id || body.data.received_at;
if(initialized && receipt!==lastReceipt && window.IdasNotify)window.IdasNotify.success(text[4]);
const state=body.data.mes_status || 'received';
const message=state==='sending'?deliveryText[0]:state==='sent'?deliveryText[1]:state==='failed'?deliveryText[2]+(body.data.mes_message?'：'+body.data.mes_message:''):deliveryText[3];
delivery.textContent=message;delivery.style.color=state==='sent'?'#18794e':state==='failed'?'#b42318':'#456080';
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
