<?php
$barcodeTestText = [
 'zh-tw'=>['mockSend'=>'使用假資料測試','mockOk'=>'已產生條碼及模擬鎖附上傳紀錄，可至鎖附上傳紀錄查看華榮格式；未送 MES、未寫入控制器。',
  'title'=>'條碼配方測試','hint'=>'先儲存上方 MES API 設定，再送出條碼。此操作會保存工單，不會寫入控制器。',
  'barcode'=>'測試條碼','send'=>'送出條碼／取得配方','load'=>'載入下方配方 JSON','pending'=>'正在取得 MES 配方…',
  'empty'=>'請輸入條碼','ok'=>'已取得配方並保存工單，可載入下方 JSON 預覽。',
  'test'=>'目前為測試模式：只記錄條碼，不呼叫 MES、不保存工單或取得配方。',
  'error'=>'條碼處理失敗，請查看完整 API 回應。','network'=>'無法確認處理結果，請檢查連線及上傳紀錄後再操作。',
  'mes'=>'MES 原始回應','context'=>'已保存的工單資料','recipe'=>'轉換後的配方','response'=>'完整 Barcode API 回應',
  'unknown'=>'此回應未包含保存結果，無法確認已保存的工單內容。',
  'confirm'=>'要以這次取得的配方取代下方 JSON 嗎？此操作不會寫入控制器。','loaded'=>'已載入配方，請確認目標控制器 IP，再使用既有預覽／寫入功能。',
  'mode'=>'測試模式可用「假資料測試」驗證轉換，不會呼叫 MES；真實對接請切換正式模式並使用 MES 測試環境。','missing'=>'回應未包含有效的 job_config，請確認已覆蓋後端異動檔案。'
 ],
 'zh-cn'=>['mockSend'=>'使用模拟数据测试','mockOk'=>'已生成条码及模拟拧紧上传记录，可到锁附上传记录查看华荣格式；未发送 MES、未写入控制器。',
  'title'=>'条码配方测试','hint'=>'先保存上方 MES API 设置，再提交条码。此操作会保存工单，不会写入控制器。',
  'barcode'=>'测试条码','send'=>'提交条码／获取配方','load'=>'载入下方配方 JSON','pending'=>'正在获取 MES 配方…',
  'empty'=>'请输入条码','ok'=>'已获取配方并保存工单，可载入下方 JSON 预览。',
  'test'=>'当前为测试模式：只记录条码，不调用 MES、不保存工单或获取配方。',
  'error'=>'条码处理失败，请查看完整 API 回应。','network'=>'无法确认处理结果，请检查连接及上传记录后再操作。',
  'mes'=>'MES 原始回应','context'=>'已保存的工单数据','recipe'=>'转换后的配方','response'=>'完整 Barcode API 回应',
  'unknown'=>'此回应未包含保存结果，无法确认已保存的工单内容。',
  'confirm'=>'要以本次获取的配方替换下方 JSON 吗？此操作不会写入控制器。','loaded'=>'已载入配方，请确认目标控制器 IP，再使用现有预览／写入功能。',
  'mode'=>'测试模式可用「模拟数据测试」验证转换，不会调用 MES；真实对接请切换正式模式并使用 MES 测试环境。','missing'=>'回应未包含有效的 job_config，请确认已覆盖后端变更文件。'
 ],
 'en-us'=>['mockSend'=>'Test with sample data','mockOk'=>'Barcode and sample tightening upload logs created. View the HuaRong JSON in tightening history. Nothing was sent to MES or written to a controller.',
  'title'=>'Barcode recipe test','hint'=>'Save the MES API settings above before submitting. This saves the work order without writing to the controller.',
  'barcode'=>'Test barcode','send'=>'Submit barcode / get recipe','load'=>'Load recipe into JSON editor','pending'=>'Retrieving MES recipe…',
  'empty'=>'Enter a barcode.','ok'=>'Recipe received and work order saved. Load the recipe into the editor to preview it.',
  'test'=>'Test mode: barcode recorded only. MES was not called; no work order or recipe was received.',
  'error'=>'Barcode processing failed. See the complete API response.','network'=>'The outcome is unknown. Check the connection and upload history before proceeding.',
  'mes'=>'Original MES response','context'=>'Saved work order context','recipe'=>'Converted recipe','response'=>'Complete Barcode API response',
  'unknown'=>'This response has no saved context; saved work order contents cannot be verified.',
  'confirm'=>'Replace the JSON editor contents with this recipe? This does not write to the controller.','loaded'=>'Recipe loaded. Confirm the target controller IP, then use the existing preview / write controls.',
  'mode'=>'Use sample data in test mode to verify conversion without calling MES. Use normal mode with a MES test environment for live integration tests.','missing'=>'No valid job_config in the response. Check that the backend changes are installed.'
 ]
][$lang];
?>
<section id="barcodeRecipeTest" class="section-card">
 <div class="section-head"><div><div class="section-title"><?php echo h($barcodeTestText['title']); ?></div><div class="section-hint"><?php echo h($barcodeTestText['hint']); ?></div></div></div>
 <div class="section-body">
  <form id="barcodeTestForm" class="target-row">
   <div class="target-field"><label for="barcodeTestInput"><?php echo h($barcodeTestText['barcode']); ?></label><input id="barcodeTestInput" class="single-target-input" type="text" autocomplete="off" spellcheck="false" required></div>
   <button id="barcodeTestSend" class="btn btn-primary" type="submit"><?php echo h($barcodeTestText['send']); ?></button>
   <button id="barcodeTestMock" class="btn btn-secondary" type="submit" value="mock"><?php echo h($barcodeTestText['mockSend']); ?></button>
  </form>
  <p class="section-hint"><?php echo h($barcodeTestText['mode']); ?></p>
  <p id="barcodeTestStatus" role="status" aria-live="polite"></p>
  <button id="barcodeTestLoad" class="btn btn-secondary" type="button" disabled><?php echo h($barcodeTestText['load']); ?></button>
  <div id="barcodeTestResults" hidden>
   <?php foreach (['mes','context','recipe','response'] as $part): ?>
   <details style="margin-top:16px"><summary><?php echo h($barcodeTestText[$part]); ?></summary><pre id="barcodeTest_<?php echo h($part); ?>" style="max-height:360px;overflow:auto;white-space:pre-wrap;overflow-wrap:anywhere;background:#f5f7fa;padding:16px;border-radius:8px"></pre></details>
   <?php endforeach; ?>
  </div>
 </div>
</section>
<script>
window.IDAS_BARCODE_TEST_UI = <?php echo json_encode(['endpoint'=>$idasBaseUrl.'/api/barcode.php','text'=>$barcodeTestText],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
</script>
