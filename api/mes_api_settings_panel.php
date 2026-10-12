<?php
$mesText = [
'zh-tw' => ['MES API 設定','請輸入對方 MES 的完整 API 網址。設定儲存在本機 iDAS。','條碼上傳，取得配方','鎖附紀錄上傳','儲存 API 設定','設定已儲存','請輸入有效的 HTTP／HTTPS 完整網址','無法讀取或儲存設定','儲存中…'],
'zh-cn' => ['MES API 设置','请输入对方 MES 的完整 API 地址。设置保存在本机 iDAS。','条码上传，取得配方','锁附记录上传','保存 API 设置','设置已保存','请输入有效的 HTTP／HTTPS 完整地址','无法读取或保存设置','保存中…'],
'en-us' => ['MES API settings','Enter complete MES API URLs. Settings are saved on this iDAS host.','Upload barcode and retrieve recipe','Upload tightening records','Save API settings','Settings saved','Enter a valid complete HTTP/HTTPS URL','Unable to load or save settings','Saving…'],
][$lang];
?>
<section class="section-card">
<div class="section-head"><div><div class="section-title"><?php echo h($mesText[0]); ?></div><div class="section-hint"><?php echo h($mesText[1]); ?></div></div></div>
<form id="mesApiSettingsForm" class="section-body">
<?php foreach (['mes_barcode_api_url','mes_tightening_api_url'] as $index => $key): ?>
<div style="margin-bottom:16px"><label for="<?php echo h($key); ?>" style="display:block;margin-bottom:8px;font-weight:600"><?php echo h($mesText[$index+2]); ?></label>
<input type="url" id="<?php echo h($key); ?>" maxlength="2048" placeholder="<?php echo $index === 0 ? 'https://mes.example.com/api/barcode' : 'https://mes.example.com/api/report_tightening_result'; ?>" style="box-sizing:border-box;width:100%;padding:12px;border:1px solid #c8d5ea;border-radius:8px;font-size:16px" autocomplete="off" spellcheck="false" disabled>
<button type="button" class="btn btn-secondary mes-test" data-key="<?php echo h($key); ?>" style="margin-top:8px"><?php echo h($lang === 'en-us' ? 'Test connection' : ($lang === 'zh-cn' ? '测试连接' : '測試連線')); ?></button>
<span id="<?php echo h($key); ?>_test" role="status" aria-live="polite" style="display:block;margin-top:8px;overflow-wrap:anywhere"></span></div>
<?php endforeach; ?>
<button type="submit" class="btn btn-primary" id="mesApiSettingsSave" disabled><?php echo h($mesText[4]); ?></button>
<span id="mesApiSettingsStatus" role="status" aria-live="polite" style="display:inline-block;margin:10px;overflow-wrap:anywhere"></span>
</form>
<?php require __DIR__ . "/barcode_received_panel.php"; ?>
</section>
<script>
(function(){
const text = <?php echo json_encode($mesText, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const endpoint = <?php echo json_encode($idasBaseUrl . '/api/mes_api_settings.php'); ?>;
const testEndpoint=endpoint.replace('mes_api_settings.php','mes_api_connection_test.php');
const testText={
'zh-tw':['測試中…','連線成功','MES 有回應，但不支援 HEAD；POST 功能需另行對測','MES 有回應，但需要授權或拒絕存取','MES 有回應，API 路徑可能錯誤','MES 有回應，請確認 HTTP 狀態','連線失敗','逾時','無法解析主機名稱','無法建立連線','PHP 未安裝 cURL','請輸入完整 HTTP／HTTPS 網址'],
'zh-cn':['测试中…','连接成功','MES 有响应，但不支持 HEAD；POST 功能需另行对测','MES 有响应，但需要授权或拒绝访问','MES 有响应，API 路径可能错误','MES 有响应，请确认 HTTP 状态','连接失败','超时','无法解析主机名称','无法建立连接','PHP 未安装 cURL','请输入完整 HTTP／HTTPS 地址'],
'en-us':['Testing…','Connection successful','MES responded; HEAD unsupported. Verify POST separately','MES responded; authorization required or access denied','MES responded; API path may be incorrect','MES responded; check HTTP status','Connection failed','Timed out','DNS resolution failed','Cannot connect','PHP cURL unavailable','Enter a complete HTTP/HTTPS URL']
}[<?php echo json_encode($lang); ?>];
document.querySelectorAll('.mes-test').forEach(b=>b.addEventListener('click',async()=>{
const field=document.getElementById(b.dataset.key), output=document.getElementById(b.dataset.key+'_test');
if(!field.value.trim()){output.textContent=testText[11];return;}
b.disabled=true;output.style.color='#456080';output.textContent=testText[0];
try{
const response=await fetch(testEndpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({url:field.value.trim()})});const data=await response.json();
if(!response.ok || !data.success){const message=data.reason==='curl_unavailable'?testText[10]:testText[11];output.textContent='';if(window.IdasNotify)window.IdasNotify.error(message);else output.textContent=message;return;}
const code=data.http_status;let message;
if(data.reachable){message=code>=200&&code<300?testText[1]:code===405?testText[2]:[401,403].includes(code)?testText[3]:code===404?testText[4]:testText[5];output.style.color=code>=200&&code<300?'#18794e':'#946200';}
else{message=data.reason==='timeout'?testText[7]:data.reason==='dns'?testText[8]:data.reason==='connection'?testText[9]:testText[6];output.style.color='#b42318';}
const resultMessage=message+' · HTTP '+code+' · '+data.elapsed_ms+' ms'+(data.curl_errno?' · cURL '+data.curl_errno:'');
output.textContent='';
if(window.IdasNotify){const type=data.reachable && code>=200 && code<300?'success':data.reachable?'warning':'error';window.IdasNotify[type](resultMessage);}else{output.textContent=resultMessage;}
}catch(e){output.textContent='';if(window.IdasNotify)window.IdasNotify.error(testText[6]);else output.textContent=testText[6];}finally{b.disabled=false;}
}));
const keys=['mes_barcode_api_url','mes_tightening_api_url'];
const form=document.getElementById('mesApiSettingsForm'), button=document.getElementById('mesApiSettingsSave'), status=document.getElementById('mesApiSettingsStatus');
function showSettingsMessage(type,message){status.textContent='';if(window.IdasNotify)window.IdasNotify[type](message);else if(window.alertify)window.alertify.alert(message);else status.textContent=message;}
async function request(options){const response=await fetch(endpoint,options); const body=await response.json(); if(!response.ok || body.success !== true) throw new Error(text[7]); return body.data;}
request().then(data=>{ keys.forEach(key=>{document.getElementById(key).value=data[key] || '';document.getElementById(key).disabled=false;});button.disabled=false; }).catch(()=>{showSettingsMessage('error',text[7]);});
form.addEventListener('submit',async event=>{
event.preventDefault();const data={};
for(const key of keys){const value=document.getElementById(key).value.trim(); if(value){try{const url=new URL(value);if(!['http:','https:'].includes(url.protocol)||url.username||url.password||url.hash) throw new Error();}catch(e){showSettingsMessage('warning',text[6]);document.getElementById(key).focus();return;}} data[key]=value;}
button.disabled=true;button.textContent=text[8];status.textContent='';
try{await request({method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});showSettingsMessage('success',text[5]);}catch(e){showSettingsMessage('error',text[7]);}finally{button.disabled=false;button.textContent=text[4];}
});
})();
</script>
