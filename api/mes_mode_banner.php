<?php
$modeText=[
'zh-cn'=>['测试模式','正式模式','条码与锁附记录均不发送至 MES；锁附测试可略过空条码检查。','条码与锁附记录会实际发送至已设置的 MES 接口；正式上传需有条码。'],
'zh-tw'=>['測試模式','正式模式','條碼與鎖附紀錄均不送至 MES；鎖附測試可略過空條碼檢查。','條碼與鎖附紀錄會實際送至已設定的 MES 接口；正式上傳需有條碼。'],
'en-us'=>['TEST MODE','PRODUCTION MODE','Barcode and tightening MES calls are skipped; empty barcodes are allowed for tightening tests.','Barcode and tightening records are sent to configured MES APIs. Production tightening uploads require a barcode.'],
][$lang];
$modeEnabled=MesTestMode::enabled();
?>
<div id="mesModeBanner" role="status" aria-live="polite" data-test-mode="<?php echo $modeEnabled?'1':'0'; ?>" style="position:sticky;top:0;z-index:1000;display:flex;flex-wrap:wrap;align-items:center;gap:8px 16px;padding:12px 24px;border-bottom:1px solid <?php echo $modeEnabled?'#e6c274':'#9ec7b6'; ?>;background:<?php echo $modeEnabled?'#fff4d9':'#eaf5ef'; ?>;color:<?php echo $modeEnabled?'#795213':'#24583f'; ?>;box-shadow:0 3px 10px #152a4010">
<strong id="mesModeBannerTitle" style="font-size:15px;letter-spacing:.02em"><?php echo h($modeText[$modeEnabled?0:1]); ?></strong>
<span id="mesModeBannerDetail" style="font-size:13px;line-height:1.6"><?php echo h($modeText[$modeEnabled?2:3]); ?></span>
</div>
<script>
(function(){
const text=<?php echo json_encode($modeText,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
function positionBanner(){
 const banner=document.getElementById('mesModeBanner'),reboot=document.getElementById('rebootBanner');
 banner.style.top=reboot?reboot.getBoundingClientRect().height+'px':'0';
}
window.addEventListener('resize',positionBanner);
new MutationObserver(positionBanner).observe(document.body,{childList:true});
positionBanner();
window.addEventListener('idas-mes-mode',event=>{
 if(!event.detail||typeof event.detail.test_mode!=='boolean')return;
 const test=event.detail.test_mode,banner=document.getElementById('mesModeBanner');
 if(banner.dataset.testMode===(test?'1':'0'))return;
 banner.dataset.testMode=test?'1':'0';banner.style.background=test?'#fff4d9':'#eaf5ef';banner.style.color=test?'#795213':'#24583f';banner.style.borderColor=test?'#e6c274':'#9ec7b6';
 document.getElementById('mesModeBannerTitle').textContent=text[test?0:1];
 document.getElementById('mesModeBannerDetail').textContent=text[test?2:3];
 // Reserve space below a simultaneous controller reboot notice.
 positionBanner();
});
})();
</script>
