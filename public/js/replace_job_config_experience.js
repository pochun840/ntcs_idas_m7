(function () {
 'use strict';
 const ui=window.IDAS_JOB_CONFIG_UI||{};
 const text={
  'zh-tw':{nav:'快速前往',sections:['API 設定','條碼與配方','目標控制器','配方 JSON','執行操作','結果與紀錄'],mode:'MES 模式',test:'測試模式',live:'正式模式',target:'目標控制器',none:'尚未指定',recipe:'目前配方',preview:'預覽變更',write:'寫入控制器',busy:'處理中，請稍候…',hint:'先預覽變更，再確認寫入。',testHint:'測試模式只略過 MES；寫入仍會更新真實控制器。',normalHint:'正式模式會傳送 MES；寫入將更新所選控制器。',apiHelp:'站號與網址儲存後會鎖定；需要修改時請按「編輯 API 設定」。',barcodeHelp:'取得配方後，載入下方 JSON 並確認內容；這一步不會寫入控制器。',targetHelp:'請確認控制器 IP，再檢查連線與可寫入狀態。',recipeHelp:'先確認工作、工序與步驟數量，再檢視或調整 JSON。',actionsHelp:'預覽不會寫入。啟用、停用與恢復預設會操作真實設備。',resultHelp:'在這裡確認每台控制器的寫入與工作切換結果。',top:'回到頁首',selected:'台已指定'},
  'zh-cn':{nav:'快速前往',sections:['API 设置','条码与配方','目标控制器','配方 JSON','执行操作','结果与记录'],mode:'MES 模式',test:'测试模式',live:'正式模式',target:'目标控制器',none:'尚未指定',recipe:'当前配方',preview:'预览变更',write:'写入控制器',busy:'处理中，请稍候…',hint:'先预览变更，再确认写入。',testHint:'测试模式只跳过 MES；写入仍会更新真实控制器。',normalHint:'正式模式会发送 MES；写入将更新所选控制器。',apiHelp:'站号与地址保存后会锁定；需要修改时请点击「编辑 API 设置」。',barcodeHelp:'取得配方后，载入下方 JSON 并确认内容；这一步不会写入控制器。',targetHelp:'请确认控制器 IP，再检查连接与可写入状态。',recipeHelp:'先确认工作、工序与步骤数量，再查看或调整 JSON。',actionsHelp:'预览不会写入。启用、停用与恢复默认会操作真实设备。',resultHelp:'在这里确认每台控制器的写入与工作切换结果。',top:'回到页首',selected:'台已指定'},
  'en-us':{nav:'Jump to section',sections:['API settings','Barcode & recipe','Controllers','Recipe JSON','Actions','Results & history'],mode:'MES mode',test:'Test mode',live:'Production mode',target:'Target controllers',none:'Not specified',recipe:'Current recipe',preview:'Preview changes',write:'Write to controller',busy:'Working, please wait…',hint:'Preview changes before confirming the write.',testHint:'Test mode skips MES only. Writes still update real controllers.',normalHint:'Production mode sends data to MES. Writes update the selected controllers.',apiHelp:'Station and URLs lock after saving. Select Edit API settings to change them.',barcodeHelp:'Load the returned recipe into the JSON editor and review it. This does not write to a controller.',targetHelp:'Confirm the controller IP, then check connectivity and write availability.',recipeHelp:'Review the JOB, SEQ and STEP counts before checking or editing the JSON.',actionsHelp:'Preview does not write. Enable, disable and restore defaults control real devices.',resultHelp:'Review the write and job switch result for each controller here.',top:'Back to top',selected:'selected'}
 }[ui.language||'zh-cn'];
 const get=id=>document.getElementById(id),content=document.querySelector('main.content');
 if(!content||get('idasExperienceNav'))return;
 document.body.classList.add('idas-experience');
 const make=(tag,className,value)=>{const el=document.createElement(tag);if(className)el.className=className;if(value!==undefined)el.textContent=value;return el;};
 const sourceWrite=get('sendButton'),sourcePreview=get('previewButton');
 const actionSection=sourceWrite&&sourceWrite.closest('.section-card');
 const sections=[document.querySelector('.mes-settings-card'),get('barcodeRecipeTest'),document.querySelector('.controller-settings-card'),document.querySelector('.recipe-json-card'),actionSection,document.querySelector('.deployment-results-card')];
 const help=[text.apiHelp,text.barcodeHelp,text.targetHelp,text.recipeHelp,text.actionsHelp,text.resultHelp];
 const nav=make('nav','ux-section-nav');nav.id='idasExperienceNav';nav.setAttribute('aria-label',text.nav);
 const links=[];
 sections.forEach((section,index)=>{
  if(!section)return;
  if(!section.id)section.id='idas-section-'+index;
  section.classList.add('ux-section');
  const heading=section.querySelector('.section-title');
  if(heading)heading.setAttribute('data-step',String(index+1).padStart(2,'0'));
  const link=make('a','ux-section-link');link.href='#'+section.id;
  const number=make('span','ux-step-number',String(index+1).padStart(2,'0'));
  link.append(number,make('span','',text.sections[index]));nav.append(link);links.push({link,section,number,heading});
  link.addEventListener('click',event=>{event.preventDefault();section.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});section.setAttribute('tabindex','-1');section.focus({preventScroll:true});});
  const head=section.querySelector('.section-head');
  if(head){const hint=make('p','ux-context-help',help[index]);head.insertAdjacentElement('afterend',hint);}
 });
 content.prepend(nav);
 const overview=make('div','ux-overview');
 const cells=[text.mode,text.target,text.recipe].map(label=>{const cell=make('div','ux-overview-cell');const value=make('strong');cell.append(make('span','ux-overview-label',label),value);overview.append(cell);return value;});
 const hero=document.querySelector('.hero');if(hero)hero.append(overview);
 const bar=make('aside','ux-action-dock');bar.setAttribute('aria-label',text.sections[4]);
 const copy=make('div','ux-dock-copy'),dockTitle=make('strong','',text.hint),dockStatus=make('span','ux-dock-status');
 // The original status remains the live region; this copy avoids repeated announcements.
 copy.append(dockTitle,dockStatus);
 const controls=make('div','ux-dock-controls');
 const preview=make('button','btn btn-secondary',text.preview);preview.type='button';preview.id='uxPreviewChanges';
 const write=make('button','btn btn-primary',text.write);write.type='button';write.id='uxWriteController';
 preview.addEventListener('click',()=>{if(sourcePreview&&!sourcePreview.disabled)sourcePreview.click();});
 write.addEventListener('click',()=>{if(sourceWrite&&!sourceWrite.disabled)sourceWrite.click();});
 controls.append(preview,write);bar.append(copy,controls);document.body.append(bar);
 if(actionSection)actionSection.classList.add('ux-action-section');
 const top=make('a','ux-back-top',text.top);top.href='#';top.addEventListener('click',event=>{event.preventDefault();window.scrollTo({top:0,behavior:window.matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth'});if(hero){hero.setAttribute('tabindex','-1');hero.focus({preventScroll:true});}});content.append(top);
 const banner=get('mesModeBanner'),status=get('status');
 let scheduled=false,disposed=false,lastTestMode=null;
 const logsSelect=get('jobConfigLogs');
 function navigateSetting(key,value,select){
  const target=new URL(window.location.href);target.searchParams.delete('test_mode');target.searchParams.set(key,value);
  // Keep the current selection if the existing unsaved-changes prompt cancels navigation.
  select.value=new URL(window.location.href).searchParams.get('show_logs')==='1'?'1':'0';
  window.location.assign(target.toString());
 }
 logsSelect?.addEventListener('change',()=>navigateSetting('show_logs',logsSelect.value,logsSelect));
 function applyMode(test){
  if(lastTestMode===test)return;lastTestMode=test;
  const barcode=get('barcodeRecipeTest');if(barcode)barcode.hidden=!test;
  let step=0;
  for(const item of links){
   const hidden=item.section===barcode&&!test;item.link.hidden=hidden;
   if(hidden){item.link.removeAttribute('aria-current');continue;}
   const value=String(++step).padStart(2,'0');item.number.textContent=value;
   if(item.heading)item.heading.setAttribute('data-step',value);
  }
  requestAnimationFrame(()=>{sizes();updateNav();});
 }

 function sync(){
  scheduled=false;if(disposed)return;
  const test=!!banner&&banner.dataset.testMode==='1';applyMode(test);
  cells[0].textContent=test?text.test:text.live;cells[0].dataset.mode=test?'test':'live';
  const multi=get('modeMulti')&&get('modeMulti').checked;
  const value=multi?(get('targetIps')?.value||''):(get('singleTargetIp')?.value||'');
  const targets=[...new Set(value.split(/[\s,;]+/).filter(Boolean))];
  cells[1].textContent=targets.length===1?targets[0]:targets.length?targets.length+' '+text.selected:text.none;
  cells[1].title=targets.join(', ');
  cells[2].textContent=['jobCount','seqCount','stepCount'].map((id,index)=>(get(id)?.textContent||'0')+' '+['JOB','SEQ','STEP'][index]).join(' / ');
  const busy=!!(sourceWrite?.disabled&&get('modeSingle')?.disabled);
  if(logsSelect)logsSelect.disabled=busy;
  preview.disabled=!sourcePreview||sourcePreview.disabled;write.disabled=!sourceWrite||sourceWrite.disabled;
  write.textContent=busy?text.busy:text.write;bar.classList.toggle('is-working',busy);bar.setAttribute('aria-busy',String(busy));
  dockTitle.textContent=test?text.testHint:text.normalHint;
  dockStatus.textContent=status?.textContent||text.hint;
  dockStatus.dataset.state=status?.classList.contains('error')?'error':status?.classList.contains('success')?'success':'neutral';
 }
 function schedule(){if(!scheduled&&!disposed){scheduled=true;requestAnimationFrame(sync);}}
 const observer=new MutationObserver(schedule);
 for(const el of [banner,status,sourceWrite,sourcePreview,get('jobCount'),get('seqCount'),get('stepCount'),get('targetCountValue')]){
  if(el)observer.observe(el,{attributes:true,attributeFilter:['disabled','class','data-test-mode'],childList:true,subtree:true,characterData:true});
 }
 for(const id of ['singleTargetIp','targetIps','modeSingle','modeMulti']){get(id)?.addEventListener('input',schedule);get(id)?.addEventListener('change',schedule);}
 let navFrame=false;
 function updateNav(){
  navFrame=false;
  const offset=parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--ux-scroll-offset'))||140;
  const visible=links.filter(item=>!item.link.hidden);
  let active=visible[0];for(const item of visible){if(item.section.getBoundingClientRect().top<=offset+35)active=item;}
  for(const item of links){if(item===active)item.link.setAttribute('aria-current','location');else item.link.removeAttribute('aria-current');}
 }
 const onScroll=()=>{if(!navFrame){navFrame=true;requestAnimationFrame(updateNav);}};
 window.addEventListener('scroll',onScroll,{passive:true});
 function sizes(){
  const modeHeight=banner?banner.getBoundingClientRect().height:0;
  const reboot=get('rebootBanner'),rebootHeight=reboot?reboot.getBoundingClientRect().height:0;
  document.documentElement.style.setProperty('--ux-banner-height',(modeHeight+rebootHeight)+'px');
  document.documentElement.style.setProperty('--ux-scroll-offset',(modeHeight+rebootHeight+nav.getBoundingClientRect().height+20)+'px');
  document.documentElement.style.setProperty('--ux-dock-height',(bar.getBoundingClientRect().height+32)+'px');
  onScroll();
 }
 const resize=typeof ResizeObserver==='function'?new ResizeObserver(sizes):null;
 for(const el of [banner,get('rebootBanner'),nav,bar])if(el&&resize)resize.observe(el);
 window.addEventListener('resize',sizes);
 const banners=new MutationObserver(()=>{const reboot=get('rebootBanner');if(reboot&&resize)resize.observe(reboot);sizes();});
 banners.observe(document.body,{childList:true});
 window.addEventListener('pagehide',event=>{if(!event.persisted){disposed=true;observer.disconnect();resize?.disconnect();banners.disconnect();}});
 window.addEventListener('pageshow',event=>{if(event.persisted){sync();sizes();updateNav();}});
 sync();sizes();updateNav();
})();
