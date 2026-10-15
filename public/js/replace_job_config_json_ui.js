(function(){
'use strict';
const editor=document.getElementById('jsonBody'),errorBox=document.getElementById('recipeJsonError');
if(!editor||!errorBox)return;
const language=window.IDAS_JOB_CONFIG_UI?.language||'zh-cn';
const texts={
 'en-us':['Invalid JSON. Check quotes, commas, brackets and braces.','JSON cannot be empty.','Line'],
 'zh-tw':['JSON 格式錯誤，請檢查引號、逗號及括號是否完整。','JSON 內容不可為空。','行'],
 'zh-cn':['JSON 格式错误，请检查引号、逗号及括号是否完整。','JSON 内容不可为空。','行']
}[language]||['JSON 格式错误，请检查引号、逗号及括号是否完整。','JSON 内容不可为空。','行'];
let timer=null;
function clearError(){errorBox.hidden=true;errorBox.textContent='';editor.removeAttribute('aria-invalid');editor.removeAttribute('aria-describedby');}
function validate(){
 clearTimeout(timer);
 try{
  if(!editor.value.trim()){showError(texts[1]);return;}
  JSON.parse(editor.value);clearError();
 }catch(error){
  let message=texts[0];
  const position=String(error.message).match(/position\s+(\d+)/i);
  if(position){const line=editor.value.slice(0,Number(position[1])).split('\n').length;message+=' ('+texts[2]+' '+line+')';}
  showError(message);
 }
}
function showError(message){errorBox.textContent=message;errorBox.hidden=false;editor.setAttribute('aria-invalid','true');editor.setAttribute('aria-describedby','recipeJsonError');}
editor.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(validate,650);});
editor.addEventListener('blur',validate);
for(const id of ['formatButton','restoreSampleButton','copyApiButton','sendButton','previewButton']){
 document.getElementById(id)?.addEventListener('click',()=>setTimeout(validate,0));
}
document.getElementById('jsonFile')?.addEventListener('change',clearError);
window.addEventListener('pagehide',()=>clearTimeout(timer));
})();
