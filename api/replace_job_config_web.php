<?php
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; connect-src 'self'");

require_once dirname(__DIR__) . '/service/JobConfigApiConfig.php';

$scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/idas/api/replace_job_config_web.php'));
$idasBaseUrl = preg_replace('#/api/[^/]+$#', '', $scriptName);
if (!is_string($idasBaseUrl) || $idasBaseUrl === '' || $idasBaseUrl === $scriptName) {
    $idasBaseUrl = '/idas';
}
$assetMtime = max(
    (int)@filemtime(dirname(__DIR__) . '/public/js/idas_notifications.js'),
    (int)@filemtime(dirname(__DIR__) . '/public/js/replace_job_config_web.js'),
    (int)@filemtime(dirname(__DIR__) . '/public/css/replace_job_config_web.css')
);
$assetVersion = (string)($assetMtime > 0 ? $assetMtime : time());

// Web API protocol version. This is independent of the installed iDAS version.
$idasApiVersion = JobConfigApiConfig::VERSION;

// Follow the same language convention as the rest of iDAS: cookie first, unknown -> en-us.
$langRaw = strtolower(str_replace('_', '-', trim((string)(
    $_COOKIE['language'] ?? $_COOKIE['languages'] ?? $_COOKIE['lang'] ?? 'en-us'
))));
if ($langRaw === 'zh' || $langRaw === 'zh-hant') $langRaw = 'zh-tw';
if ($langRaw === 'zh-hans') $langRaw = 'zh-cn';
if ($langRaw === 'en') $langRaw = 'en-us';
$lang = in_array($langRaw, ['en-us', 'zh-tw', 'zh-cn'], true) ? $langRaw : 'en-us';

$i18nAll = require __DIR__ . '/replace_job_config_i18n.php';
$t = $i18nAll[$lang];
function h($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="<?php echo h($t['html_lang']); ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo h($t['title']); ?></title>
  <link rel="stylesheet" href="<?php echo h($idasBaseUrl); ?>/public/css/alertify_min.css?v=<?php echo h($assetVersion); ?>">
  <link rel="stylesheet" href="<?php echo h($idasBaseUrl); ?>/public/css/default_min.css?v=<?php echo h($assetVersion); ?>">
  <link rel="stylesheet" href="<?php echo h($idasBaseUrl); ?>/public/css/idas_notifications.css?v=<?php echo h($assetVersion); ?>">
  <link rel="stylesheet" href="<?php echo h($idasBaseUrl); ?>/public/css/replace_job_config_web.css?v=<?php echo h($assetVersion); ?>">
</head>
<body>
<div class="page">
  <header class="hero">
    <div class="hero-top">
      <div>
        <h1><?php echo h($t['title']); ?></h1>
        <p class="subtitle"><?php echo h($t['subtitle']); ?></p>
      </div>
      <span class="tag"><?php echo h($t['api_tool']); ?></span>
    </div>
  </header>

  <main class="content">
    <section class="section-card">
      <div class="section-head"><div><div class="section-title"><?php echo h($t['section_targets']); ?></div><div class="section-hint"><?php echo h($t['target_hint']); ?></div></div><span id="networkBadge" class="network-badge"><?php echo h($t['local_network']); ?>: --</span></div>
      <div class="section-body">
        <div class="mode-block">
          <span class="mode-label"><?php echo h($t['deployment_mode']); ?></span>
          <div class="mode-switch" role="radiogroup" aria-label="<?php echo h($t['deployment_mode']); ?>">
            <label class="mode-option"><input id="modeSingle" type="radio" name="deploymentMode" value="single" checked><span><?php echo h($t['mode_single']); ?></span></label>
            <label class="mode-option"><input id="modeMulti" type="radio" name="deploymentMode" value="multi"><span><?php echo h($t['mode_multi']); ?></span></label>
          </div>
          <div id="modeHint" class="mode-hint"><?php echo h($t['mode_single_hint']); ?></div>
        </div>
        <div id="singleTargetPanel" class="target-mode-panel">
          <div class="target-row"><div class="target-field"><label for="singleTargetIp"><?php echo h($t['single_target_ip']); ?></label><input id="singleTargetIp" class="single-target-input" type="text" inputmode="decimal" autocomplete="off" spellcheck="false" placeholder="<?php echo h($t['single_placeholder']); ?>"></div><button id="checkTargetsButton" class="btn btn-secondary" type="button">⌁ <?php echo h($t['check_targets']); ?></button></div>
        </div>
        <div id="multiTargetPanel" class="target-mode-panel" hidden>
          <div class="target-row"><div class="target-field"><label for="targetIps"><?php echo h($t['target_ips']); ?></label><textarea id="targetIps" class="target-input" rows="2" spellcheck="false" placeholder="<?php echo h($t['target_placeholder']); ?>"></textarea></div><button id="checkTargetsButtonMulti" class="btn btn-secondary" type="button">⌁ <?php echo h($t['check_targets']); ?></button></div>
        </div>
        <div class="target-meta"><?php echo h($t['same_subnet_only']); ?></div>
        <div id="multiStats" class="mini-stats" aria-live="polite" style="display:none"><div class="mini-stat"><span class="mini-stat-label"><?php echo h($t['target_count']); ?></span><strong id="targetCountValue" class="mini-stat-value">0</strong></div><div class="mini-stat"><span class="mini-stat-label"><?php echo h($t['confirm_ready']); ?></span><strong id="readyCountValue" class="mini-stat-value">--</strong></div><div class="mini-stat"><span class="mini-stat-label"><?php echo h($t['confirm_skipped']); ?></span><strong id="skippedCountValue" class="mini-stat-value">--</strong></div></div>
        <span id="targetCount" style="display:none"><?php echo h($t['target_count']); ?> 0</span>
      </div>
    </section>
    <section class="section-card"><div class="section-head"><div><div class="section-title"><?php echo h($t['section_controllers']); ?></div><div class="section-hint"><?php echo h($t['controllers_empty']); ?></div></div></div><div id="targetTableEmpty" class="controllers-empty"><?php echo h($t['controllers_empty']); ?></div><div id="targetTableWrap" class="target-table-wrap"><table class="target-table"><thead><tr><th><?php echo h($t['col_ip']); ?></th><th><?php echo h($t['col_subnet']); ?></th><th><?php echo h($t['col_connection']); ?></th><th><?php echo h($t['col_root']); ?></th><th><?php echo h($t['col_controller']); ?></th><th><?php echo h($t['col_result']); ?></th><th><?php echo h($t['col_elapsed']); ?></th></tr></thead><tbody id="targetTableBody"></tbody></table></div></section>
    <section class="section-card"><div class="section-head"><div><div class="section-title"><?php echo h($t['section_configuration']); ?></div><div class="section-hint"><?php echo h($t['json_hint']); ?></div></div></div><div class="section-body config-layout"><div class="config-summary" aria-label="<?php echo h($t['config_summary']); ?>"><div class="config-stat"><span class="label"><?php echo h($t['job_count']); ?></span><span id="jobCount" class="value">0</span></div><div class="config-stat"><span class="label"><?php echo h($t['seq_count']); ?></span><span id="seqCount" class="value">0</span></div><div class="config-stat"><span class="label"><?php echo h($t['step_count']); ?></span><span id="stepCount" class="value">0</span></div></div><div id="editorShell" class="editor-shell"><div class="editor-toolbar"><strong><?php echo h($t['json_title']); ?></strong><div class="toolbar"><input id="jsonFile" type="file" accept="application/json,.json"><label for="jsonFile" class="btn btn-secondary" role="button">↥ <?php echo h($t['choose_file']); ?></label><span id="fileName" class="file-name"><?php echo h($t['no_file']); ?></span><button id="formatButton" class="btn btn-secondary" type="button">{ } <?php echo h($t['format_json']); ?></button><button id="editorToggleButton" class="btn btn-secondary" type="button" aria-expanded="true">▴ <?php echo h($t['collapse_json']); ?></button></div></div><div id="editorBody" class="editor-body"><textarea id="jsonBody" aria-label="<?php echo h($t['json_title']); ?>" spellcheck="false">{
  "JOB_lst": [
    {
      "JOBID": 1,
      "JOBname": "JOB-1",
      "type": 1,
      "time": "2026-09-11 00:00:00",
      "act": 1,
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
    },
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 2,
      "STEPname": "STEP-2",
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
    },
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 3,
      "STEPname": "STEP-3",
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
    },
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 4,
      "STEPname": "STEP-4",
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
    },
    {
      "JOBID": 1,
      "SEQID": 1,
      "StepSelect": 5,
      "STEPname": "STEP-5",
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
}</textarea></div></div><div class="api-tools"><button id="restoreSampleButton" class="btn btn-neutral" type="button">↺ <?php echo h($t['restore_sample']); ?></button><button id="downloadSampleButton" class="btn btn-neutral" type="button">↓ <?php echo h($t['download_sample']); ?></button><button id="copyApiButton" class="btn btn-neutral" type="button">⧉ <?php echo h($t['copy_api_request']); ?></button></div></div></section>
    <section class="section-card"><div class="section-head"><div><div class="section-title"><?php echo h($t['section_deployment']); ?></div><div class="section-hint"><?php echo h($t['deployment_hint']); ?></div></div></div><div class="section-body"><div class="deployment-grid"><div class="deployment-copy"><?php echo h($t['partial_ready']); ?><br><span id="status" class="status"><?php echo h($t['ready']); ?></span></div><div class="action-buttons"><button id="previewButton" class="btn btn-secondary" type="button">≋ <?php echo h($t['preview']); ?></button><button id="retryFailedButton" class="btn btn-secondary" type="button" style="display:none">↻ <?php echo h($t['retry_failed']); ?></button><button id="sendButton" class="btn btn-primary" type="button">✓ <?php echo h($t['write_db']); ?></button></div></div><div id="previewSummary" class="preview-summary"><div class="preview-summary-title"><?php echo h($t['preview_summary']); ?></div><div class="preview-summary-grid"><div class="preview-summary-item"><strong>JOB</strong><span id="previewJobs">--</span></div><div class="preview-summary-item"><strong>SEQ</strong><span id="previewSeqs">--</span></div><div class="preview-summary-item"><strong>STEP</strong><span id="previewSteps">--</span></div></div></div></div></section>
    <section class="section-card"><div class="section-head"><div><div class="section-title"><?php echo h($t['section_result']); ?></div><div class="section-hint"><?php echo h($t['result_hint']); ?></div></div></div><div id="overallResult" class="overall-result" aria-live="polite"></div><div id="resultPlaceholder" class="result-placeholder"><?php echo h($t['result_hint']); ?></div><div id="resultMetrics" class="result-metrics" aria-live="polite"><div class="result-metric success"><span class="result-metric-label"><?php echo h($t['result_success']); ?></span><strong id="resultSuccessValue" class="result-metric-value">0</strong></div><div class="result-metric skipped"><span class="result-metric-label"><?php echo h($t['result_skipped']); ?></span><strong id="resultSkippedValue" class="result-metric-value">0</strong></div><div class="result-metric failed"><span class="result-metric-label"><?php echo h($t['result_failed']); ?></span><strong id="resultFailedValue" class="result-metric-value">0</strong></div></div><div id="batchSummary" class="batch-summary" aria-live="polite"></div><div id="resultExportTools" class="api-tools" style="display:none;padding:0 16px 14px"><button id="exportResultJsonButton" class="btn btn-neutral" type="button">↓ <?php echo h($t['export_json']); ?></button><button id="exportResultCsvButton" class="btn btn-neutral" type="button">↓ <?php echo h($t['export_csv']); ?></button></div></section>
    <details id="responseCard" class="response-card" aria-live="polite"><summary class="response-title"><?php echo h($t['api_details']); ?></summary><pre id="response"></pre></details>
  </main>
</div>

<script src="<?php echo h($idasBaseUrl); ?>/public/js/alertify_min.js?v=<?php echo h($assetVersion); ?>"></script>
<script src="<?php echo h($idasBaseUrl); ?>/public/js/idas_notifications.js?v=<?php echo h($assetVersion); ?>"></script>
<script>
window.IDAS_JOB_CONFIG_UI = <?php echo json_encode([
  'translations' => $t,
  'apiEndpoint' => $idasBaseUrl . '/api/replace_job_config.php',
  'apiVersion' => $idasApiVersion,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
</script>
<script src="<?php echo h($idasBaseUrl); ?>/public/js/replace_job_config_web.js?v=<?php echo h($assetVersion); ?>"></script>
</body>
</html>
