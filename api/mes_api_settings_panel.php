<?php
$mesText = [
'zh-tw' => ['MES API 設定','請輸入對方 MES 的完整 API 網址。設定儲存在本機 iDAS。','條碼上傳，取得配方','鎖附紀錄上傳','儲存 API 設定','設定已儲存','請輸入有效的 HTTP／HTTPS 完整網址','無法讀取或儲存設定','儲存中…'],
'zh-cn' => ['MES API 设置','请输入对方 MES 的完整 API 地址。设置保存在本机 iDAS。','条码上传，取得配方','锁附记录上传','保存 API 设置','设置已保存','请输入有效的 HTTP／HTTPS 完整地址','无法读取或保存设置','保存中…'],
'en-us' => ['MES API settings','Enter complete MES API URLs. Settings are saved on this iDAS host.','Upload barcode and retrieve recipe','Upload tightening records','Save API settings','Settings saved','Enter a valid complete HTTP/HTTPS URL','Unable to load or save settings','Saving…'],
][$lang];
?>
<section class="section-card mes-settings-card">
<div class="section-head"><div><div class="section-title"><?php echo h($mesText[0]); ?></div><div class="section-hint"><?php echo h($mesText[1]); ?></div></div></div>
<?php require_once dirname(__DIR__).'/service/MesTestMode.php'; if (MesTestMode::enabled()): ?>
<p style="margin:16px 22px 0;padding:12px 14px;border:1px solid #ead6aa;border-radius:8px;background:#fff8e8;color:#835b18;font-size:13px"><?php echo h($lang==='en-us'?'Device test mode: both MES APIs are skipped for all controller requests. Set test_mode=0 in the page URL to restore production mode.':($lang==='zh-tw'?'本機測試模式：控制器所有請求皆略過兩個 MES 接口。請在頁面網址加入 test_mode=0 恢復正式模式。':'本机测试模式：控制器所有请求均略过两个 MES 接口。请在页面地址加入 test_mode=0 恢复正式模式。')); ?></p>
<?php endif; ?>
<form id="mesApiSettingsForm" class="section-body">
<div class="toolbar" style="justify-content:flex-start;margin-bottom:16px" role="group" aria-label="<?php echo h($lang==='en-us'?'Edit mode':($lang==='zh-cn'?'编辑模式':'編輯模式')); ?>">
<button type="button" id="mesFormMode" class="btn btn-primary" aria-pressed="true"><?php echo h($lang==='en-us'?'Form':($lang==='zh-cn'?'表单':'表單')); ?></button>
<button type="button" id="mesJsonMode" class="btn btn-secondary" aria-pressed="false">JSON</button></div>
<div id="mesJsonModePanel" hidden><div id="mesEditorShell" class="editor-shell">
<div class="editor-toolbar"><strong>MES API JSON</strong><div class="toolbar">
<input id="mesJsonFile" type="file" accept="application/json,.json"><label for="mesJsonFile" class="btn btn-secondary" role="button">↥ <?php echo h($t['choose_file']); ?></label><span id="mesJsonFileName" class="file-name"><?php echo h($t['no_file']); ?></span>
<button id="mesJsonFormat" class="btn btn-secondary" type="button">{ } <?php echo h($t['format_json']); ?></button><button id="mesJsonToggle" class="btn btn-secondary" type="button" aria-expanded="true">▴ <?php echo h($t['collapse_json']); ?></button></div></div>
<div class="editor-body"><textarea id="mesJsonBody" aria-label="MES API JSON" spellcheck="false" disabled></textarea></div></div>
<div class="toolbar" style="justify-content:flex-end;margin:12px 0 16px">
<button id="mesJsonReset" type="button" class="btn btn-secondary">↺ <?php echo h($lang==='en-us'?'Restore sample JSON':($lang==='zh-cn'?'恢复范例 JSON':'恢復範例 JSON')); ?></button>
<button id="mesJsonDownload" type="button" class="btn btn-secondary">↓ <?php echo h($lang==='en-us'?'Download sample JSON':($lang==='zh-cn'?'下载范例 JSON':'下載範例 JSON')); ?></button>
<button id="mesJsonCopy" type="button" class="btn btn-secondary">▣ <?php echo h($lang==='en-us'?'Copy API Request':($lang==='zh-cn'?'复制 API Request':'複製 API Request')); ?></button></div>
<div class="toolbar" style="justify-content:flex-start;margin-bottom:16px">
<?php foreach (['mes_barcode_api_url','mes_tightening_api_url'] as $index => $key): ?>
<button type="button" class="btn btn-secondary mes-test" data-key="<?php echo h($key); ?>"><?php echo h($mesText[$index+2]); ?> · <?php echo h($lang==='en-us'?'Test connection':($lang==='zh-cn'?'测试连接':'測試連線')); ?></button>
<?php endforeach; ?></div></div>
<div id="mesFormModePanel">
<?php foreach (['mes_barcode_api_url','mes_tightening_api_url'] as $index => $key): ?>
<div class="mes-endpoint-row"><label for="<?php echo h($key); ?>" class="mes-endpoint-label"><?php echo h($mesText[$index+2]); ?></label>
<input type="url" id="<?php echo h($key); ?>" maxlength="2048" placeholder="<?php echo $index===0?'https://mes.example.com/api/barcode':'https://mes.example.com/api/report_tightening_result'; ?>" class="mes-url-input" autocomplete="off" spellcheck="false" disabled>
<button type="button" class="btn btn-secondary mes-test mes-test-position" data-key="<?php echo h($key); ?>"><?php echo h($lang==='en-us'?'Test connection':($lang==='zh-cn'?'测试连接':'測試連線')); ?></button>
<span id="<?php echo h($key); ?>_test" role="status" aria-live="polite" class="mes-test-result"></span></div>
<?php endforeach; ?></div>
<div class="mes-save-row"><button type="submit" class="btn btn-primary" id="mesApiSettingsSave" disabled><?php echo h($mesText[4]); ?></button>
<span id="mesApiSettingsStatus" role="status" aria-live="polite" style="display:inline-block;margin:10px;overflow-wrap:anywhere"></span>
</div>
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
if(!syncMesJson())return;
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
const editor=document.getElementById('mesJsonBody');
let mesMode='form';
function formToJson(){editor.value=JSON.stringify(Object.fromEntries(keys.map(k=>[k,document.getElementById(k).value])),null,2);}
function setMesMode(mode){if(mode===mesMode)return;if(mesMode==='json'&&!syncMesJson())return;if(mode==='json')formToJson();mesMode=mode;document.getElementById('mesFormModePanel').hidden=mode!=='form';document.getElementById('mesJsonModePanel').hidden=mode!=='json';keys.forEach(k=>document.getElementById(k).disabled=mode==='json'||editor.disabled);for(const [id,value] of [['mesFormMode','form'],['mesJsonMode','json']]){const b=document.getElementById(id),active=mode===value;b.classList.toggle('btn-primary',active);b.classList.toggle('btn-secondary',!active);b.setAttribute('aria-pressed',String(active));}}
document.getElementById('mesFormMode').onclick=()=>setMesMode('form');
document.getElementById('mesJsonMode').onclick=()=>setMesMode('json');
keys.forEach(k=>document.getElementById(k).addEventListener('input',()=>{if(mesMode==='form')formToJson();}));
const sample={mes_barcode_api_url:'',mes_tightening_api_url:''};
function syncMesJson(){
if(mesMode==='form'){formToJson();return true;}
try{const value=JSON.parse(editor.value);if(!value||Array.isArray(value)||typeof value!=='object'||Object.keys(value).some(k=>!keys.includes(k))||keys.some(k=>typeof value[k]!=='string'))throw new Error();keys.forEach(k=>document.getElementById(k).value=value[k]);return true;}
catch(e){showSettingsMessage('error',<?php echo json_encode($lang==='en-us'?'Invalid MES API JSON: provide both URL fields as strings':($lang==='zh-cn'?'MES API JSON 格式错误，请提供两个网址字符串字段':'MES API JSON 格式錯誤，請提供兩個網址字串欄位')); ?>);editor.focus();return false;}}
document.getElementById('mesJsonFormat').onclick=()=>{if(syncMesJson())editor.value=JSON.stringify(Object.fromEntries(keys.map(k=>[k,document.getElementById(k).value])),null,2);};
document.getElementById('mesJsonToggle').onclick=function(){const collapsed=document.getElementById('mesEditorShell').classList.toggle('collapsed');this.setAttribute('aria-expanded',String(!collapsed));this.textContent=(collapsed?'▾ ':'▴ ')+(collapsed?<?php echo json_encode($t['expand_json']); ?>:<?php echo json_encode($t['collapse_json']); ?>);};
document.getElementById('mesJsonReset').onclick=()=>{editor.value=JSON.stringify(sample,null,2);};
document.getElementById('mesJsonDownload').onclick=()=>{const url=URL.createObjectURL(new Blob([JSON.stringify(sample,null,2)+'\n'],{type:'application/json'}));const a=document.createElement('a');a.href=url;a.download='mes_api_settings.json';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);};
document.getElementById('mesJsonFile').onchange=async function(){const f=this.files[0];if(!f)return;try{if(f.size>65536)throw new Error();const old=editor.value;editor.value=await f.text();if(!syncMesJson()){editor.value=old;return;}editor.value=JSON.stringify(JSON.parse(editor.value),null,2);document.getElementById('mesJsonFileName').textContent=f.name;}catch(e){showSettingsMessage('error',text[7]);}finally{this.value='';}};
document.getElementById('mesJsonCopy').onclick=async()=>{if(!syncMesJson())return;const payload=JSON.stringify(JSON.parse(editor.value));const quote=v=>"'"+v.replace(/'/g,"'\\''")+"'";const command="wget -qO- --content-on-error --header='Content-Type: application/json' --post-data="+quote(payload)+' '+quote(new URL(endpoint,location.href).href);try{if(navigator.clipboard&&window.isSecureContext)await navigator.clipboard.writeText(command);else{const a=document.createElement('textarea');a.value=command;document.body.appendChild(a);a.select();const ok=document.execCommand('copy');a.remove();if(!ok)throw new Error();}showSettingsMessage('success',<?php echo json_encode($lang==='en-us'?'API Request copied':($lang==='zh-cn'?'已复制 API Request':'已複製 API Request')); ?>);}catch(e){showSettingsMessage('error',text[7]);}};
async function request(options){const response=await fetch(endpoint,options); const body=await response.json(); if(!response.ok || body.success !== true) throw new Error(text[7]); return body.data;}
request().then(data=>{ keys.forEach(key=>{document.getElementById(key).value=data[key] || '';document.getElementById(key).disabled=mesMode==='json';});editor.value=JSON.stringify(data,null,2);editor.disabled=false;button.disabled=false; }).catch(()=>{showSettingsMessage('error',text[7]);});
form.addEventListener('submit',async event=>{
event.preventDefault();if(!syncMesJson())return;const data={};
for(const key of keys){const value=document.getElementById(key).value.trim(); if(value){try{const url=new URL(value);if(!['http:','https:'].includes(url.protocol)||url.username||url.password||url.hash) throw new Error();}catch(e){showSettingsMessage('warning',text[6]);editor.focus();return;}} data[key]=value;}
button.disabled=true;button.textContent=text[8];status.textContent='';
try{await request({method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(data)});showSettingsMessage('success',text[5]);}catch(e){showSettingsMessage('error',text[7]);}finally{button.disabled=false;button.textContent=text[4];}
});
})();
</script>
