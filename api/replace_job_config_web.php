<?php
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self'");
$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/replace_job_config.php'));
$idasBaseUrl = preg_replace('#/api/[^/]+$#', '', $scriptName);
if (!is_string($idasBaseUrl) || $idasBaseUrl === '' || $idasBaseUrl === $scriptName) $idasBaseUrl = '/idas';
$assetVersion = (string)(@filemtime(dirname(__DIR__) . '/public/js/idas_notifications.js') ?: time());
?>
<!doctype html>
<html lang="zh-Hant">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>IDAS JOB/SEQ/STEP API</title>
  <link rel="stylesheet" href="<?php echo htmlspecialchars($idasBaseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/css/alertify_min.css?v=<?php echo $assetVersion; ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars($idasBaseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/css/default_min.css?v=<?php echo $assetVersion; ?>">
  <link rel="stylesheet" href="<?php echo htmlspecialchars($idasBaseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/css/idas_notifications.css?v=<?php echo $assetVersion; ?>">
  <style>
    *{box-sizing:border-box}
    body{margin:0;background:#f4f6f9;color:#182230;font-family:Arial,"Microsoft JhengHei",sans-serif}
    main{width:min(1040px,calc(100% - 32px));margin:28px auto;background:#fff;border:1px solid #d8dee8;border-radius:12px;box-shadow:0 8px 28px rgba(30,48,70,.08);overflow:hidden}
    header{padding:20px 24px;background:#26375d;color:#fff}
    h1{font-size:22px;margin:0 0 6px}
    header p{margin:0;color:#dbe6ff}
    section{padding:22px 24px}
    .warning{padding:13px 15px;margin-bottom:18px;border-left:5px solid #df8b00;background:#fff6df;color:#6c4300;line-height:1.55}
    label{display:block;font-weight:700;margin-bottom:8px}
    .tools,.actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .tools{margin-bottom:10px}
    input[type=file]{max-width:100%}
    textarea{display:block;width:100%;min-height:390px;resize:vertical;padding:14px;border:1px solid #aeb8c8;border-radius:7px;background:#fbfcfe;color:#152238;font:14px/1.55 Consolas,monospace}
    textarea:focus{outline:3px solid #cfe2ff;border-color:#2878d4}
    .actions{margin-top:14px}
    button{border:0;border-radius:7px;padding:10px 18px;font-size:15px;cursor:pointer}
    .primary{background:#1769c2;color:#fff;font-weight:700}
    .primary:hover{background:#0f56a2}
    .primary:disabled{opacity:.55;cursor:not-allowed}
    .secondary{background:#e8edf4;color:#24344c}
    #status{font-weight:700}
    .success{color:#087833}.error{color:#c22626}
    pre{display:none;white-space:pre-wrap;word-break:break-word;margin:18px 0 0;padding:14px;border-radius:7px;background:#101827;color:#e9f1ff;max-height:330px;overflow:auto}
    @media(max-width:640px){main{width:100%;margin:0;border:0;border-radius:0}header,section{padding:17px}textarea{min-height:330px}}
  </style>
</head>
<body>
<main>
  <header>
    <h1>IDAS JOB / SEQ / STEP 新增／覆蓋</h1>
  </header>
  <section>
    <label for="jsonBody">JSON 資料</label>
    <div class="tools">
      <input id="jsonFile" type="file" accept="application/json,.json">
      <button id="formatButton" class="secondary" type="button">格式化 JSON</button>
    </div>
    <textarea id="jsonBody" spellcheck="false">{
  "JOB_lst": [
    {
      "JOBID": 1,
      "JOBname": "JOB-1",
      "type": 1,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "ok_job": 1,
      "ok_job_stop": 0,
      "output_unified": 0,
      "input_unified": 0,
      "job_unit": 1
    }
  ],
  "SEQ_lst": [
    {
      "JOBID": 1,
      "SEQID": 1,
      "SEQname": "SEQ-1",
      "type": 0,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "skip": 0,
      "seq_repeat": 1,
      "timeout": 20,
      "ok_seq": 1,
      "ok_stop": 0,
      "countType": 1,
      "ok_screw": 1,
      "ng_stop": 0,
      "ng_unscrew": 0,
      "interrupt_alarm": 1,
      "accu_angle": 1,
      "Thread_Calcu": 31,
      "unscrew_mode": 1,
      "unscrew_force": 50,
      "unscrew_rpm": 300,
      "unscrew_dir": 0,
      "image": null,
      "message": null,
      "delay": 0,
      "input": 0,
      "input_signal": 1,
      "output": 0,
      "output_signal": 1,
      "output_durat": 100,
      "addtion": null,
      "unscrew_count_switch": 0,
      "unscrew_torque_threshold": 0,
      "seq_unit": 1,
      "unscrew_angle_threshold": 0,
      "dt_time": 0,
      "tt_time": 0,
      "total_angle_limit": 0,
      "total_angle_lower": 0
    }
  ],
  "STEP_lst": [
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 1,
      "STEPname": "STEP-1",
      "type": 0,
      "time": "2026-09-11 00:00:00",
      "act": 0,
      "StepSwitch": 1,
      "StepRPM": 500,
      "StepOption": 2,
      "StepTime": 1000,
      "StepAngle": 3000,
      "StepTorque": 1.0,
      "StepDirection": 1,
      "StepDelay": 0,
      "StepMoniByWin": 0,
      "StepLimiHi": 30,
      "StepLimiLo": 30,
      "StepHiAngle": 30600,
      "StepLoAngle": 0,
      "StepHiTorque": 1.2,
      "StepLoTorque": 0.8,
      "StepAccelerateOffset": 0.2,
      "StepAccelerateOffsetSign": 43,
      "StepEnableTorqueOffset": 0,
      "StepTorqueOffset": 0,
      "StepTorqueOffsetSign": 43,
      "StepEnableDownShift": 0,
      "StepTorqueDownShift": 0,
      "StepRPMDownShift": 0,
      "StepEnableThreshold": 0,
      "StepTorqueTS": 0,
      "StepReTry": 0,
      "StepUnScrew": 1,
      "StepReTryTorq": 0,
      "StepReTryAngl": 0,
      "StepAngleRecord": 0,
      "StepAutoDetectAngle": 0,
      "InterruptAlarm": 1,
      "OverAngleStop": 1,
      "KValue": 100,
      "step_unit": 1
    }
  ]
}</textarea>
    <div class="actions">
      <button id="sendButton" class="primary" type="button">寫入控制器 DB</button>
      <span id="status"></span>
    </div>
    <pre id="response"></pre>
  </section>
</main>
<script src="<?php echo htmlspecialchars($idasBaseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/js/alertify_min.js?v=<?php echo $assetVersion; ?>"></script>
<script src="<?php echo htmlspecialchars($idasBaseUrl, ENT_QUOTES, 'UTF-8'); ?>/public/js/idas_notifications.js?v=<?php echo $assetVersion; ?>"></script>
<script>
(function () {
  'use strict';
  const body = document.getElementById('jsonBody');
  const file = document.getElementById('jsonFile');
  const send = document.getElementById('sendButton');
  const statusElement = document.getElementById('status');
  const responseElement = document.getElementById('response');
  const apiEndpoint = <?php echo json_encode($idasBaseUrl . '/api/add_job_config.php', JSON_UNESCAPED_SLASHES); ?>;

  function showStatus(text, type) {
    statusElement.textContent = text;
    statusElement.className = type || '';
  }

  function showUnifiedNotice(title, message, type) {
    if (window.IdasNotify && typeof window.IdasNotify.show === 'function') {
      window.IdasNotify.show({title: title, message: message, type: type || 'info'});
      return true;
    }
    showStatus(title + '：' + message, type === 'success' ? 'success' : 'error');
    return false;
  }

  function confirmAdd() {
    const message = '確定要寫入這些 JOB / SEQ / STEP 嗎？相同 ID 的資料會被更新覆蓋。';
    if (window.alertify && typeof window.alertify.confirm === 'function') {
      return new Promise(function (resolve) {
        const dialog = window.alertify.confirm('操作確認', message,
          function () { resolve(true); },
          function () { resolve(false); }
        );
        dialog.set('labels', {ok: '確定', cancel: '取消'});
      });
    }
    showUnifiedNotice('錯誤', '共用提示元件載入失敗，請重新整理頁面後再試。', 'error');
    return Promise.resolve(false);
  }

  document.getElementById('formatButton').addEventListener('click', function () {
    try {
      body.value = JSON.stringify(JSON.parse(body.value), null, 2);
      showStatus('JSON 格式正確', 'success');
    } catch (error) {
      showUnifiedNotice('錯誤', 'JSON 格式錯誤：' + error.message, 'error');
      showStatus('JSON 格式錯誤：' + error.message, 'error');
    }
  });

  file.addEventListener('change', async function () {
    if (!file.files[0]) return;
    try {
      body.value = await file.files[0].text();
      body.value = JSON.stringify(JSON.parse(body.value), null, 2);
      showStatus('已載入 ' + file.files[0].name, 'success');
    } catch (error) {
      showUnifiedNotice('錯誤', '檔案不是有效 JSON：' + error.message, 'error');
      showStatus('檔案不是有效 JSON：' + error.message, 'error');
    }
  });

  send.addEventListener('click', async function () {
    try {
      JSON.parse(body.value);
    } catch (error) {
      showUnifiedNotice('錯誤', 'JSON 格式錯誤：' + error.message, 'error');
      showStatus('JSON 格式錯誤：' + error.message, 'error');
      return;
    }

    if (!(await confirmAdd())) return;

    send.disabled = true;
    responseElement.style.display = 'none';
    showStatus('處理中…', '');
    try {
      const requestUrl = apiEndpoint + '?request=' + Date.now();
      const httpResponse = await fetch(requestUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: body.value,
        cache: 'no-store'
      });
      const responseText = await httpResponse.text();
      const responseType = String(httpResponse.headers.get('Content-Type') || '').toLowerCase();
      if (responseType.indexOf('application/json') === -1) {
        throw new Error(httpResponse.redirected
          ? 'API POST 被重新導向，請重新整理頁面後再試。'
          : 'API 回應格式錯誤，伺服器沒有回傳 JSON。');
      }
      let responseData;
      try {
        responseData = JSON.parse(responseText);
      } catch (error) {
        throw new Error('API 回傳的 JSON 格式錯誤。');
      }
      responseElement.textContent = JSON.stringify(responseData, null, 2);
      responseElement.style.display = 'block';
      const successful = httpResponse.ok && responseData.success;
      if (successful) {
        const counts = (responseData.data && responseData.data.counts) || {};
        const inserted = counts.inserted || {};
        const updated = counts.updated || {};
        showUnifiedNotice(
          '成功',
          '新增 ' + ((inserted.jobs || 0) + (inserted.sequences || 0) + (inserted.steps || 0))
            + ' 筆、覆蓋 ' + ((updated.jobs || 0) + (updated.sequences || 0) + (updated.steps || 0)) + ' 筆成功',
          'success'
        );
      } else if (responseData && responseData.error && responseData.error.code === 'CONTROLLER_IN_USE') {
        showUnifiedNotice('警告', '控制器尚未登出，請先登出後再寫入。', 'warning');
      } else if (responseData && responseData.error && responseData.error.code === 'CONTROLLER_STATUS_UNAVAILABLE') {
        showUnifiedNotice('錯誤', '無法確認控制器登入狀態，請檢查控制器連線後再試。', 'error');
      } else {
        showUnifiedNotice('錯誤', (responseData.error && responseData.error.message) || '寫入失敗，請檢查 JSON 資料。', 'error');
      }
      showStatus(successful ? '寫入完成' : '寫入失敗', successful ? 'success' : 'error');
    } catch (error) {
      responseElement.textContent = error.message;
      responseElement.style.display = 'block';
      showUnifiedNotice('錯誤', error.message || '連線或處理失敗。', 'error');
      showStatus('連線或處理失敗', 'error');
    } finally {
      send.disabled = false;
    }
  });
}());
</script>
</body>
</html>
