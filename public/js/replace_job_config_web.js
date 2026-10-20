(function () {
  'use strict';
  const config = window.IDAS_JOB_CONFIG_UI || {};
  const T = config.translations || {};
  const switchText = config.switchText || {};
  const deploymentLabels = {
    'en-us':['DB write','JOB / SEQ switch','Pending','Not attempted','Details','Switch complete','Switch failed','Preview','Succeeded','Failed','Skipped'],
    'zh-tw':['DB 寫入','JOB／SEQ 切換','處理中','未執行','詳細內容','切換成功','切換失敗','預覽','成功','失敗','略過'],
    'zh-cn':['DB 写入','JOB／SEQ 切换','处理中','未执行','详细内容','切换成功','切换失败','预览','成功','失败','略过']
  }[config.language || 'zh-cn'];
  let lastDeploymentData = null;
  let lastNoticeKey = '', lastNoticeAt = 0;

  const switchJob = document.getElementById('autoSwitchJob');
  const switchSeq = document.getElementById('autoSwitchSeq');
  const switchResult = document.getElementById('autoSwitchResult');
  const body = document.getElementById('jsonBody');
  const defaultJsonText = body.value;
  // Separate the updated five-step sample from legacy browser drafts.
  // Keep the v1 key intact so previously edited configurations remain recoverable.
  const JSON_DRAFT_STORAGE_KEY = 'idas.replace_job_config.last_json.v2';
  let draftSourceNotice = null, loadCurrentSampleButton = null;
  const JSON_DRAFT_MAX_CHARS = 2 * 1024 * 1024;
  let jsonInputTimer = null;
  const file = document.getElementById('jsonFile');
  const fileName = document.getElementById('fileName');
  const send = document.getElementById('sendButton');
  const previewButton = document.getElementById('previewButton');
  const retryFailedButton = document.getElementById('retryFailedButton');
  const formatButton = document.getElementById('formatButton');
  const editorShell = document.getElementById('editorShell');
  const editorToggleButton = document.getElementById('editorToggleButton');
  const restoreSampleButton = document.getElementById('restoreSampleButton');
  const downloadSampleButton = document.getElementById('downloadSampleButton');
  const copyApiButton = document.getElementById('copyApiButton');
  const exportResultJsonButton = document.getElementById('exportResultJsonButton');
  const exportResultCsvButton = document.getElementById('exportResultCsvButton');
  const resultExportTools = document.getElementById('resultExportTools');
  const previewSummary = document.getElementById('previewSummary');
  const previewJobs = document.getElementById('previewJobs');
  const previewSeqs = document.getElementById('previewSeqs');
  const previewSteps = document.getElementById('previewSteps');
  const overallResult = document.getElementById('overallResult');
  const resultMetrics = document.getElementById('resultMetrics');
  const resultSuccessValue = document.getElementById('resultSuccessValue');
  const resultSkippedValue = document.getElementById('resultSkippedValue');
  const resultFailedValue = document.getElementById('resultFailedValue');
  const batchSummary = document.getElementById('batchSummary');
  let lastFailedTargets = [];
  let busy = false;
  let lastExportResult = null;
  const MAX_REQUEST_BYTES = 67174400;
  let jsonDirty = false;
  const statusElement = document.getElementById('status');
  const responseElement = document.getElementById('response');
  const responseCard = document.getElementById('responseCard');
  const targetIps = document.getElementById('targetIps');
  const singleTargetIp = document.getElementById('singleTargetIp');
  const singleTargetPanel = document.getElementById('singleTargetPanel');
  const multiTargetPanel = document.getElementById('multiTargetPanel');
  const multiStats = document.getElementById('multiStats');
  const modeSingle = document.getElementById('modeSingle');
  const modeMulti = document.getElementById('modeMulti');
  const modeHint = document.getElementById('modeHint');
  const checkTargetsButtonMulti = document.getElementById('checkTargetsButtonMulti');
  let deploymentMode = 'single';
  let renderedTargetsKey = '';
  const targetCount = document.getElementById('targetCount');
  const targetCountValue = document.getElementById('targetCountValue');
  const readyCountValue = document.getElementById('readyCountValue');
  const skippedCountValue = document.getElementById('skippedCountValue');
  const targetTableEmpty = document.getElementById('targetTableEmpty');
  const resultPlaceholder = document.getElementById('resultPlaceholder');
  const targetTableWrap = document.getElementById('targetTableWrap');
  const targetTableBody = document.getElementById('targetTableBody');
  const checkTargetsButton = document.getElementById('checkTargetsButton');
  const networkBadge = document.getElementById('networkBadge');
  const apiEndpoint = config.apiEndpoint || '/idas/api/replace_job_config.php';
  const apiVersion = String(config.apiVersion || '');
  if (!apiVersion) throw new Error('API version configuration is missing');

  function pad2(value) {
    return String(value).padStart(2, '0');
  }

  function getComputerLocalTime() {
    const now = new Date();
    return now.getFullYear() + '-' +
      pad2(now.getMonth() + 1) + '-' +
      pad2(now.getDate()) + ' ' +
      pad2(now.getHours()) + ':' +
      pad2(now.getMinutes()) + ':' +
      pad2(now.getSeconds());
  }

  function applyComputerTime(data) {
    const currentTime = getComputerLocalTime();
    ['JOB_lst', 'SEQ_lst', 'STEP_lst'].forEach(function (listName) {
      if (!Array.isArray(data[listName])) return;
      data[listName].forEach(function (item) {
        if (item && typeof item === 'object') {
          item.time = currentTime;
        }
      });
    });
    return data;
  }

  function refreshJsonTimeFromComputer() {
    let data;
    try { data = applyComputerTime(JSON.parse(body.value)); }
    catch (error) { locateInputError(body, T.json_invalid + error.message); throw error; }
    body.value = JSON.stringify(data, null, 2);
    saveJsonDraftNow();
    updateCounts();
    return data;
  }

  function saveJsonDraftNow() {
    if (jsonInputTimer !== null) {
      clearTimeout(jsonInputTimer);
      jsonInputTimer = null;
    }
    const value = body.value;
    if (!value || value.length > JSON_DRAFT_MAX_CHARS) return false;
    try {
      window.localStorage.setItem(JSON_DRAFT_STORAGE_KEY, value);
      return true;
    } catch (error) {
      return false;
    }
  }

  function scheduleJsonInputProcessing() {
    if (jsonInputTimer !== null) clearTimeout(jsonInputTimer);
    jsonInputTimer = setTimeout(function () {
      jsonInputTimer = null;
      saveJsonDraftNow();
      updateCounts();
    }, 300);
  }

  function restoreSavedJsonDraft() {
    let saved;
    try {
      saved = window.localStorage.getItem(JSON_DRAFT_STORAGE_KEY);
      if (typeof saved !== 'string' || saved.trim() === '') return 'none';
      const parsed = JSON.parse(saved);
      if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) throw new Error('Invalid configuration root');
      body.value = saved;
      return 'restored';
    } catch (error) {
      try { window.localStorage.removeItem(JSON_DRAFT_STORAGE_KEY); } catch (storageError) {}
      return typeof saved === 'string' && saved.trim() !== '' ? 'invalid' : 'none';
    }
  }

  function restoreBuiltInSample() {
    if (!window.confirm(T.restore_sample_confirm)) return;
    if (jsonInputTimer !== null) {
      clearTimeout(jsonInputTimer);
      jsonInputTimer = null;
    }
    try { window.localStorage.removeItem(JSON_DRAFT_STORAGE_KEY); } catch (error) {}
    body.value = defaultJsonText;
    try {
      let data;
    try { data = applyComputerTime(JSON.parse(body.value)); }
    catch (error) { locateInputError(body, T.json_invalid + error.message); throw error; }
      body.value = JSON.stringify(data, null, 2);
    } catch (error) {}
    saveJsonDraftNow();
    if (draftSourceNotice) draftSourceNotice.remove();
    jsonDirty = true;
    updateCounts();
    showUnifiedNotice(T.success, T.restore_sample_done, 'success');
  }

  function isValidIpv4(value) {
    const parts = String(value || '').split('.');
    return parts.length === 4 && parts.every(function (part) {
      return /^\d{1,3}$/.test(part) && Number(part) >= 0 && Number(part) <= 255;
    });
  }

  function parseTargets() {
    const seen = Object.create(null);
    const source = deploymentMode === 'single' ? singleTargetIp.value : targetIps.value;
    const values = deploymentMode === 'single' ? [String(source || '').trim()] : String(source || '').split(/[\s,;]+/).map(function (value) { return value.trim(); });
    return values.filter(function (value) {
      if (!value || seen[value] || !isValidIpv4(value)) return false;
      seen[value] = true;
      return true;
    });
  }

  function ipv4SortValue(ip) {
    const parts = String(ip).split('.');
    if (parts.length !== 4 || parts.some(function (p) { return !/^\d+$/.test(p) || Number(p) > 255; })) return Number.MAX_SAFE_INTEGER;
    return parts.reduce(function (n, p) { return n * 256 + Number(p); }, 0);
  }

  function normalizeTargetInput() {
    const targets = parseTargets().sort(function (a, b) {
      const av = ipv4SortValue(a), bv = ipv4SortValue(b);
      return av === bv ? a.localeCompare(b) : av - bv;
    });
    if (deploymentMode === 'single') {
      singleTargetIp.value = targets.length ? targets[0] : '';
    } else {
      targetIps.value = targets.join('\n');
    }
    updateTargetCount();
    return targets;
  }

  function setDeploymentMode(mode, preserveValue) {
    const next = mode === 'multi' ? 'multi' : 'single';
    if (next === deploymentMode && preserveValue !== false) return;
    const currentTargets = parseTargets();
    deploymentMode = next;
    modeSingle.checked = next === 'single';
    modeMulti.checked = next === 'multi';
    singleTargetPanel.hidden = next !== 'single';
    multiTargetPanel.hidden = next !== 'multi';
    multiStats.style.display = next === 'multi' ? 'grid' : 'none';
    modeHint.textContent = next === 'single' ? T.mode_single_hint : T.mode_multi_hint;
    retryFailedButton.style.display = next === 'multi' && lastFailedTargets.length ? '' : 'none';
    if (preserveValue !== false && currentTargets.length) {
      if (next === 'single') singleTargetIp.value = currentTargets[0];
      else targetIps.value = currentTargets.join('\n');
    }
    targetTableBody.textContent = '';
    targetTableWrap.style.display = 'none';
    if (targetTableEmpty) targetTableEmpty.style.display = 'block';
    updateTargetCount();
  }

  function setBusy(value) {
    busy = !!value;
    [loadCurrentSampleButton, send, previewButton, retryFailedButton, checkTargetsButton, checkTargetsButtonMulti, formatButton, restoreSampleButton, downloadSampleButton, copyApiButton, exportResultJsonButton, exportResultCsvButton].forEach(function (el) { if (el) el.disabled = busy; });
    switchJob.disabled = switchSeq.disabled = busy;
    file.disabled = busy;
    targetIps.disabled = busy;
    singleTargetIp.disabled = busy;
    modeSingle.disabled = busy;
    modeMulti.disabled = busy;
  }

  function payloadCounts(payload) {
    return {
      jobs: Array.isArray(payload && payload.JOB_lst) ? payload.JOB_lst.length : 0,
      sequences: Array.isArray(payload && payload.SEQ_lst) ? payload.SEQ_lst.length : 0,
      steps: Array.isArray(payload && payload.STEP_lst) ? payload.STEP_lst.length : 0
    };
  }

  function targetsKey(targets) {
    return (Array.isArray(targets) ? targets.slice() : [])
      .filter(Boolean)
      .sort(function (a, b) { return ipv4SortValue(a) - ipv4SortValue(b); })
      .join('|');
  }

  function clearRenderedTargetChecks() {
    renderedTargetsKey = '';
    targetTableBody.textContent = '';
    targetTableWrap.style.display = 'none';
    if (targetTableEmpty) targetTableEmpty.style.display = 'block';
    if (readyCountValue) readyCountValue.textContent = '--';
    if (skippedCountValue) skippedCountValue.textContent = '--';
    lastFailedTargets = [];
    retryFailedButton.style.display = 'none';
  }

  function updateTargetCount() {
    const currentTargets = parseTargets();
    const count = currentTargets.length;
    targetCount.textContent = T.target_count + ' ' + count;
    if (targetCountValue) targetCountValue.textContent = String(count);
    if (renderedTargetsKey && renderedTargetsKey !== targetsKey(currentTargets)) {
      clearRenderedTargetChecks();
    }
    if (targetTableWrap.style.display === 'none') {
      if (readyCountValue) readyCountValue.textContent = '--';
      if (skippedCountValue) skippedCountValue.textContent = '--';
    }
  }

  function errorMessageFromCode(code, fallback) {
    const map = {
      DEFAULT_TARGET_UNAVAILABLE: T.err_default_target_unavailable,
      NOT_SAME_SUBNET: T.err_not_same_subnet,
      REMOTE_NOT_SAME_SUBNET: T.err_not_same_subnet,
      TARGET_UNREACHABLE: T.err_target_unreachable,
      NO_RESPONSE: T.err_target_unreachable,
      CONTROLLER_IN_USE: T.err_controller_in_use,
      CONTROLLER_ROOT_NOT_FOUND: T.err_root_missing,
      NOT_NTCS7: T.err_not_ntcs7,
      PROBE_INVALID_RESPONSE: T.err_invalid_response,
      WRITE_INVALID_RESPONSE: T.err_invalid_response,
      VERIFY_FAILED: T.err_verify_failed,
      WRITE_VERIFY_FAILED: T.err_verify_failed,
      REMOTE_WRITE_FAILED: T.err_write_failed,
      LOCAL_WRITE_FAILED: T.err_write_failed,
      SKIPPED_NOT_READY: T.err_not_ready,
      TARGET_NOT_READY: T.err_not_ready
    };
    return (code && map[code]) ? map[code] : (fallback || '');
  }

  function stateHtml(text, state) {
    const span = document.createElement('span');
    span.className = 'state ' + (state || '');
    span.textContent = text;
    return span;
  }

  function renderTargetChecks(checks, writeResults, elapsedMs) {
    targetTableBody.textContent = '';
    const currentTargets = parseTargets();
    const checkMap = Object.create(null);
    if (Array.isArray(checks)) {
      checks.forEach(function (item) { if (item && item.ip) checkMap[item.ip] = item; });
    }
    if (currentTargets.length) {
      checks = currentTargets.map(function (ip) {
        return checkMap[ip] || {
          ip: ip,
          same_subnet: false,
          network: null,
          reachable: null,
          controller_root_exists: null,
          controller_logged_out: null,
          ready: null,
          code: 'NO_CHECK_RESULT',
          message: T.no_check_result,
          check_missing: true
        };
      });
    }
    const writeMap = Object.create(null);
    if (Array.isArray(writeResults)) {
      writeResults.forEach(function (item) { if (item && item.ip) writeMap[item.ip] = item; });
    }
    if (!Array.isArray(checks) || checks.length === 0) {
      renderedTargetsKey = '';
      targetTableWrap.style.display = 'none';
      if (targetTableEmpty) targetTableEmpty.style.display = 'block';
      if (readyCountValue) readyCountValue.textContent = '--';
      if (skippedCountValue) skippedCountValue.textContent = '--';
      return;
    }
    renderedTargetsKey = targetsKey(checks.map(function (item) { return item && item.ip; }));
    const knownItems = checks.filter(function (item) { return !!(item && !item.check_missing); }).length;
    const readyItems = checks.filter(function (item) { return !!(item && item.ready === true); }).length;
    const skippedItems = checks.filter(function (item) { return !!(item && !item.check_missing && item.ready === false); }).length;
    if (readyCountValue) readyCountValue.textContent = knownItems ? String(readyItems) : '--';
    if (skippedCountValue) skippedCountValue.textContent = knownItems ? String(skippedItems) : '--';
    checks.forEach(function (item) {
      const tr = document.createElement('tr');
      const cells = [];
      function addText(text) {
        const td = document.createElement('td');
        td.textContent = text == null || text === '' ? '--' : String(text);
        tr.appendChild(td);
        return td;
      }
      addText(item.ip || '--');
      addText(item.network || '--');
      const connection = document.createElement('td');
      const reachable = item.reachable;
      connection.appendChild(stateHtml(reachable === true ? T.reachable : (reachable === false ? T.unreachable : T.unknown), reachable === true ? 'ok' : (reachable === false ? 'bad' : 'warn')));
      tr.appendChild(connection);
      const root = document.createElement('td');
      const rootExists = item.controller_root_exists;
      root.appendChild(stateHtml(rootExists === true ? T.exists : (rootExists === false ? T.missing : T.unknown), rootExists === true ? 'ok' : (rootExists === false ? 'bad' : 'warn')));
      tr.appendChild(root);
      const controller = document.createElement('td');
      const loggedOut = item.controller_logged_out;
      controller.appendChild(stateHtml(loggedOut === true ? T.logged_out : (loggedOut === false ? T.in_use : T.unknown), loggedOut === true ? 'ok' : (loggedOut === false ? 'bad' : 'warn')));
      tr.appendChild(controller);
      const result = document.createElement('td');
      const writeResult = writeMap[item.ip];
      if (writeResult) {
        const skipped = !!writeResult.skipped;
        const label = writeResult.success ? T.write_ok_label : (skipped ? T.write_skip_label : T.write_fail_label);
        const state = writeResult.success ? 'ok' : (skipped ? 'warn' : 'bad');
        result.appendChild(stateHtml(label, state));
        const writeMessage = errorMessageFromCode(writeResult.code, writeResult.message);
        if (writeMessage && writeResult.message !== 'Write complete') result.appendChild(document.createTextNode(' · ' + writeMessage));
      } else {
        const ready = item.ready;
        const resultText = ready === true ? T.ready_label : (ready === false ? T.not_ready_label : T.no_check_result);
        result.appendChild(stateHtml(resultText, ready === true ? 'ok' : (ready === false ? 'bad' : 'warn')));
        const itemMessage = errorMessageFromCode(item.code, item.message);
        if (itemMessage && item.message !== 'Ready') result.appendChild(document.createTextNode(' · ' + itemMessage));
      }
      tr.appendChild(result);
      const elapsed = document.createElement('td');
      const operationElapsedMs = Number(elapsedMs || 0);
      elapsed.textContent = writeResult ? ((operationElapsedMs / 1000).toFixed(2) + ' s') : '--';
      tr.appendChild(elapsed);
      targetTableBody.appendChild(tr);
    });
    targetTableWrap.style.display = 'block';
    if (targetTableEmpty) targetTableEmpty.style.display = 'none';
  }

  function buildApiRequest(payload, action) {
    const request = Object.assign({api_version: apiVersion, action: action || 'write'}, payload || {});
    // A normal API request uses the controller IP configured in iDAS.
    // Only an explicit multi-controller deployment needs targets.
    if (deploymentMode === 'multi') request.targets = normalizeTargetInput();
    return request;
  }

  function updatePreviewSummary(inserted, updated) {
    function textFor(key) {
      return T.inserted + ' ' + Number(inserted[key] || 0) + ' · ' + T.updated + ' ' + Number(updated[key] || 0);
    }
    previewJobs.textContent = textFor('jobs');
    previewSeqs.textContent = textFor('sequences');
    previewSteps.textContent = textFor('steps');
    previewSummary.style.display = 'block';
  }

  function updateOverallResult(mode, successCount, skippedCount, failedCount) {
    overallResult.className = 'overall-result';
    if (mode === 'preview') {
      overallResult.textContent = T.overall_status + ': ' + T.overall_preview;
      overallResult.classList.add('preview');
      return;
    }
    if (successCount > 0 && skippedCount === 0 && failedCount === 0) {
      overallResult.textContent = T.overall_status + ': ' + T.overall_all_success;
      overallResult.classList.add('success');
    } else if (successCount > 0) {
      overallResult.textContent = T.overall_status + ': ' + T.overall_partial;
      overallResult.classList.add('warning');
    } else {
      overallResult.textContent = T.overall_status + ': ' + T.overall_all_failed;
      overallResult.classList.add('error');
    }
  }

  function renderDeploymentDetails(data, mode) {
    const container=document.getElementById('deploymentDetails');
    if(!container)return;
    container.replaceChildren();
    const results=Array.isArray(data && data.results)?data.results:[];
    container.hidden=!results.length;
    const switches=Array.isArray(data && data.auto_switch && data.auto_switch.results)?data.auto_switch.results:[];
    const byIp=new Map(switches.map(function(item){return [item.ip,item];}));
    if(mode!=='preview' && switches.length){
      const summary=document.createElement('p');summary.className='deployment-switch-summary';
      summary.textContent=deploymentLabels[1]+' · '+deploymentLabels[8]+': '+switches.filter(function(item){return item.success;}).length+' · '+deploymentLabels[9]+': '+switches.filter(function(item){return !item.success;}).length;
      container.appendChild(summary);
    }
    results.forEach(function(result){
      if(!result)return;
      const card=document.createElement('article');card.className='deployment-device-card';
      const heading=document.createElement('strong');heading.className='deployment-device-ip';heading.textContent=result.ip || '--';card.appendChild(heading);
      const states=document.createElement('div');states.className='deployment-device-states';
      function stage(label,value,state){const line=document.createElement('div'),name=document.createElement('span');name.className='deployment-stage-name';name.textContent=label;line.appendChild(name);line.appendChild(stateHtml(value,state));states.appendChild(line);}
      stage(mode==='preview'?deploymentLabels[7]:deploymentLabels[0],result.skipped?deploymentLabels[10]:result.success?deploymentLabels[8]:deploymentLabels[9],result.skipped?'warn':result.success?'ok':'bad');
      const switched=byIp.get(result.ip);
      if(mode!=='preview')stage(deploymentLabels[1],switched?(switched.success?deploymentLabels[5]:deploymentLabels[6]):result.success&&!result.skipped?deploymentLabels[2]:deploymentLabels[3],switched?(switched.success?'ok':'bad'):'warn');
      card.appendChild(states);
      const message=errorMessageFromCode(result.code,result.message);
      if(!result.success && message){const reason=document.createElement('p');reason.className='deployment-device-error';reason.textContent=message;card.appendChild(reason);}
      if(switched && !switched.success && switched.message){const reason=document.createElement('p');reason.className='deployment-device-error';reason.textContent=switched.message;card.appendChild(reason);}
      const details=document.createElement('details'),summary=document.createElement('summary'),content=document.createElement('pre');
      summary.textContent=deploymentLabels[4];content.textContent=JSON.stringify(sanitizeApiResponse({write:result,switch:switched || null}),null,2);
      details.appendChild(summary);details.appendChild(content);card.appendChild(details);container.appendChild(card);
    });
  }

  function showBatchSummary(data, mode) {
    lastExportResult = data || null;
    lastDeploymentData = data || null;
    renderDeploymentDetails(data, mode);
    if (resultExportTools) resultExportTools.style.display = lastExportResult ? 'flex' : 'none';
    if (!data || typeof data !== 'object') { batchSummary.style.display = 'none'; return; }
    const results = Array.isArray(data.results) ? data.results : [];
    const summary = data.summary && typeof data.summary === 'object' ? data.summary : {};
    function summaryNumber(key, legacyKey) {
      if (summary[key] !== undefined && summary[key] !== null) return Number(summary[key] || 0);
      return Number(data[legacyKey] || 0);
    }
    const successCount = summaryNumber('success', 'success_count');
    const skippedCount = summaryNumber('skipped', 'skipped_count');
    const failedCount = summaryNumber('failed', 'failed_count');
    let inserted = {jobs:0,sequences:0,steps:0};
    let updated = {jobs:0,sequences:0,steps:0};
    let verifiedCount = 0;
    results.forEach(function (r) {
      const d = r && r.data ? r.data : null;
      const c = d && d.counts ? d.counts : (d && d.preview && d.preview.counts ? d.preview.counts : null);
      if (c) {
        const ci = c.inserted || {};
        const cu = c.updated || {};
        ['jobs','sequences','steps'].forEach(function (k) { inserted[k] += Number(ci[k] || 0); updated[k] += Number(cu[k] || 0); });
      }
      if (d && d.verified) verifiedCount++;
    });
    updatePreviewSummary(inserted, updated);
    const lines = [];
    lines.push('<strong>' + (mode === 'preview' ? T.preview_done : T.write_done) + '</strong>');
    lines.push(T.inserted + ': JOB ' + inserted.jobs + ' / SEQ ' + inserted.sequences + ' / STEP ' + inserted.steps);
    lines.push(T.updated + ': JOB ' + updated.jobs + ' / SEQ ' + updated.sequences + ' / STEP ' + updated.steps);
    if (mode !== 'preview') lines.push(T.verified + ': ' + verifiedCount + ' / ' + successCount);
    updateOverallResult(mode, successCount, skippedCount, failedCount);
    lines.push(T.success + ': ' + successCount + ' · ' + T.write_skip_label + ': ' + skippedCount + ' · ' + T.write_fail_label + ': ' + failedCount);
    const elapsedMs = Number(data.elapsed_ms || 0);
    if (elapsedMs >= 0 && mode !== 'preview') lines.push(T.elapsed_time + ': ' + (elapsedMs / 1000).toFixed(2) + ' s');
    if (resultSuccessValue) resultSuccessValue.textContent = String(successCount);
    if (resultSkippedValue) resultSkippedValue.textContent = String(skippedCount);
    if (resultFailedValue) resultFailedValue.textContent = String(failedCount);
    if (resultMetrics) resultMetrics.style.display = 'grid';
    batchSummary.innerHTML = lines.join('<br>');
    batchSummary.style.display = 'block';
    if (resultPlaceholder) resultPlaceholder.style.display = 'none';
  }

  function sanitizeApiResponse(value) {
    if (Array.isArray(value)) return value.map(sanitizeApiResponse);
    if (!value || typeof value !== 'object') return value;
    const cleaned = {};
    Object.keys(value).forEach(function (key) {
      if (key === 'controller_database' || key === 'controller_root' || key === 'backup') return;
      cleaned[key] = sanitizeApiResponse(value[key]);
    });
    return cleaned;
  }

  function utf8ByteLength(text) {
    if (window.TextEncoder) return new TextEncoder().encode(text).length;
    return unescape(encodeURIComponent(text)).length;
  }

  async function copyTextToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      await navigator.clipboard.writeText(text);
      return;
    }
    const temp = document.createElement('textarea');
    temp.value = text;
    temp.style.position = 'fixed';
    temp.style.opacity = '0';
    document.body.appendChild(temp);
    temp.select();
    const copied = document.execCommand('copy');
    temp.remove();
    if (!copied) throw new Error('copy failed');
  }

  function downloadBlob(filename, content, type) {
    const blob = new Blob([content], {type: type});
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 0);
  }

  function csvCell(value) {
    const text = value == null ? '' : String(value);
    return '"' + text.replace(/"/g, '""') + '"';
  }

  function exportResultCsv(data) {
    const results = Array.isArray(data && data.results) ? data.results : (data && data.data && Array.isArray(data.data.results) ? data.data.results : []);
    const switchRows=Array.isArray(data && data.auto_switch && data.auto_switch.results)?data.auto_switch.results:[];
    const switchMap=new Map(switchRows.map(function(item){return [item.ip,item];}));
    const lines = [['IP','Status','Code','Message','Elapsed ms','Switch status','Switch message']];
    results.forEach(function (item) {
      let status = 'failed';
      if (item && (item.success === true || item.written === true)) status = 'success';
      else if (item && (item.skipped === true || item.ready === false)) status = 'skipped';
      lines.push([item.ip || '', status, item.code || item.error_code || '', item.message || '', Number((item && item.elapsed_ms) || (data && data.elapsed_ms) || 0),switchMap.has(item.ip)?(switchMap.get(item.ip).success?'success':'failed'):'not_attempted',switchMap.has(item.ip)?switchMap.get(item.ip).message||'':'']);
    });
    const csv = '\ufeff' + lines.map(function (row) { return row.map(csvCell).join(','); }).join('\r\n');
    downloadBlob('job_config_result_' + Date.now() + '.csv', csv, 'text/csv;charset=utf-8');
  }

  async function callRemoteApi(payload) {
    const action = payload && payload.action ? payload.action : 'check';
    const timeoutMs = action === 'write' ? 30000 : (action === 'preview' ? 15000 : 8000);
    const controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    const timer = controller ? setTimeout(function () { controller.abort(); }, timeoutMs) : null;
    let httpResponse;
    try {
      const requestBody = JSON.stringify(payload);
      if (utf8ByteLength(requestBody) > MAX_REQUEST_BYTES) throw new Error(T.request_too_large);
      httpResponse = await fetch(apiEndpoint + '?request=' + Date.now(), {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: requestBody,
        cache: 'no-store',
        signal: controller ? controller.signal : undefined
      });
    } catch (error) {
      if (error && error.name === 'AbortError') throw new Error(T.connection_failed);
      throw error;
    } finally {
      if (timer) clearTimeout(timer);
    }
    const responseText = await httpResponse.text();
    const responseType = String(httpResponse.headers.get('Content-Type') || '').toLowerCase();
    if (responseType.indexOf('application/json') === -1) {
      throw new Error(httpResponse.redirected ? T.redirected : T.response_invalid);
    }
    let responseData;
    try {
      responseData = JSON.parse(responseText);
    } catch (error) {
      throw new Error(T.response_json_invalid);
    }
    return {httpResponse: httpResponse, data: responseData};
  }

  // Accept both the current flat response and the legacy {data:{...}} shape.
  function operationData(responseData) {
    if (!responseData || typeof responseData !== 'object') return {};
    const nested = responseData.data && typeof responseData.data === 'object' ? responseData.data : null;
    const hasFlatOperation = Array.isArray(responseData.checks) || Array.isArray(responseData.results) || (responseData.summary && typeof responseData.summary === 'object');
    const normalized = Object.assign({}, hasFlatOperation || !nested ? responseData : nested);
    ['success', 'action', 'api_version', 'elapsed_ms', 'error'].forEach(function (key) {
      if (normalized[key] === undefined && responseData[key] !== undefined) normalized[key] = responseData[key];
    });
    return normalized;
  }

  async function checkTargets(showNotice, manageBusy) {
    if (manageBusy === undefined) manageBusy = true;
    if (manageBusy && busy) return null;
    const targets = parseTargets();
    if (!targets.length) {
      showUnifiedNotice(T.error, T.target_required, 'error');
      return null;
    }
    if (manageBusy) setBusy(true);
    showStatus(T.checking, '');
    try {
      const result = await callRemoteApi({api_version: apiVersion, action: 'check', targets: targets});
      const checkData = operationData(result.data);
      const checks = Array.isArray(checkData.checks) ? checkData.checks : [];
      renderTargetChecks(checks);
      // Legacy responses use an outer success=true for a valid HTTP/API call,
      // while data.all_ready carries the actual controller readiness result.
      // Current flat responses expose that readiness directly as success.
      const allReady = typeof checkData.all_ready === 'boolean'
        ? checkData.all_ready
        : checkData.success === true;
      const readyCount = checks.filter(function (item) { return !!(item && item.ready); }).length;
      const anyReady = readyCount > 0;
      const message = allReady ? T.all_ready : (anyReady ? T.some_not_ready : T.no_ready);
      showStatus(message, allReady ? 'success' : (anyReady ? 'warning' : 'error'));
      if (showNotice) showUnifiedNotice(allReady ? T.success : T.warning_notice, message, allReady ? 'success' : 'warning');
      return {allReady: allReady, anyReady: anyReady, readyCount: readyCount, checks: checks, response: result.data};
    } catch (error) {
      showUnifiedNotice(T.error, error.message || T.connection_failed, 'error');
      showStatus(T.connection_status_failed, 'error');
      return null;
    } finally {
      if (manageBusy) setBusy(false);
    }
  }

  async function loadNetworkInfo() {
    try {
      const httpResponse = await fetch(apiEndpoint + '?action=network&request=' + Date.now(), {cache: 'no-store'});
      const data = await httpResponse.json();
      if (!httpResponse.ok || !data.success || !data.data) throw new Error(T.network_unavailable);
      const networks = Array.isArray(data.data.networks) ? data.data.networks : [];
      networkBadge.textContent = T.local_network + ': ' + (networks.length ? networks.map(function (item) { return item.cidr; }).join(', ') : '--');
      if (data.data.preferred_ip) {
        if (!singleTargetIp.value.trim()) singleTargetIp.value = data.data.preferred_ip;
        if (!targetIps.value.trim()) targetIps.value = data.data.preferred_ip;
        updateTargetCount();
      }
    } catch (error) {
      networkBadge.textContent = T.local_network + ': --';
      showStatus(T.network_unavailable, 'error');
    }
  }

  function showStatus(text, type) {
    statusElement.textContent = text;
    statusElement.className = 'status' + (type ? ' ' + type : '');
  }

  function refreshSwitchChoices(payload, keepJob, keepSeq) {
    const oldJob = keepJob === false ? '' : switchJob.value;
    const oldSeq = keepSeq === false ? '' : switchSeq.value;
    const jobs = (payload.JOB_lst || []).filter(function (j) { return j && Number.isInteger(Number(j.JOBID)) && Number(j.JOBID) >= 1 && Number(j.JOBID) <= 100; });
    switchJob.replaceChildren(new Option(switchText.choose, ''));
    jobs.forEach(function (j) { switchJob.add(new Option('JOB ' + j.JOBID + ' · ' + (j.JOBname || ''), String(Number(j.JOBID)))); });
    const preferredJob = jobs.find(function (j) { return Number(j.act) === 1; }) || jobs[0];
    switchJob.value = jobs.some(function (j) { return String(Number(j.JOBID)) === oldJob; }) ? oldJob : (preferredJob ? String(Number(preferredJob.JOBID)) : '');
    const seqs = (payload.SEQ_lst || []).filter(function (q) { return q && switchJob.value !== '' && Number(q.JOBID) === Number(switchJob.value) && Number.isInteger(Number(q.SEQID)) && Number(q.SEQID) >= 0 && Number(q.SEQID) <= 50; });
    switchSeq.replaceChildren(new Option(switchText.choose, ''));
    seqs.forEach(function (q) { switchSeq.add(new Option('SEQ ' + q.SEQID + ' · ' + (q.SEQname || ''), String(Number(q.SEQID)))); });
    const preferredSeq = seqs.find(function (q) { return Number(q.act) === 1; }) || seqs[0];
    switchSeq.value = oldJob === switchJob.value && seqs.some(function (q) { return String(Number(q.SEQID)) === oldSeq; }) ? oldSeq : (preferredSeq ? String(Number(preferredSeq.SEQID)) : '');
  }

  function selectedSwitchPair(payload) {
    const job = Number(switchJob.value), seq = Number(switchSeq.value);
    if (!switchJob.value || !switchSeq.value || !Number.isInteger(job) || !Number.isInteger(seq) ||
        !(payload.JOB_lst || []).some(function (j) { return j && Number(j.JOBID) === job; }) ||
        !(payload.SEQ_lst || []).some(function (q) { return q && Number(q.JOBID) === job && Number(q.SEQID) === seq; })) {
      throw new Error(switchText.invalid);
    }
    return {job_id: job, seq_id: seq};
  }

  async function switchWrittenControllers(results, pair) {
    const targets = Array.from(new Set(results.filter(function (r) { return r && r.success === true && !r.skipped && r.ip; }).map(function (r) { return r.ip; })));
    const switched = [];
    switchResult.textContent = targets.length ? switchText.pending : '';
    // One bounded request per device; never resend a switch after a timeout.
    for (const ip of targets) {
      const abort = new AbortController();
      const timer = setTimeout(function () { abort.abort(); }, 25000);
      let item;
      try {
        const response = await fetch(config.switchEndpoint, {
          method: 'POST', headers: {'Content-Type': 'application/json'}, cache: 'no-store',
          body: JSON.stringify({targets: [ip], job_id: pair.job_id, seq_id: pair.seq_id}), signal: abort.signal
        });
        const data = await response.json();
        const found = Array.isArray(data.results) ? data.results.find(function (r) { return r && r.ip === ip; }) : null;
        if (!response.ok || !found) throw new Error((data.error && data.error.message) || T.response_invalid);
        item = {ip: ip, success: found.success === true, message: found.message || ''};
      } catch (error) {
        item = {ip: ip, success: false, message: error.name === 'AbortError' ? T.connection_failed : error.message};
      } finally { clearTimeout(timer); }
      switched.push(item);
      if(lastDeploymentData){lastDeploymentData.auto_switch={job_id:pair.job_id,seq_id:pair.seq_id,results:switched.slice()};renderDeploymentDetails(lastDeploymentData,'write');}
      switchResult.textContent = 'JOB ' + pair.job_id + ' / SEQ ' + pair.seq_id + '\n' + switched.map(function (r) {
        return r.ip + ': ' + (r.success ? switchText.ok : switchText.failed) + (r.success ? '' : ' · ' + r.message);
      }).join('\n');
    }
    return switched;
  }

  switchJob.addEventListener('change', function () {
    try { refreshSwitchChoices(JSON.parse(body.value), true, false); } catch (error) { switchSeq.replaceChildren(); }
  });

  function updateCounts() {
    try {
      const data = JSON.parse(body.value);
      refreshSwitchChoices(data);
      document.getElementById('jobCount').textContent = String(Array.isArray(data.JOB_lst) ? data.JOB_lst.length : 0);
      document.getElementById('seqCount').textContent = String(Array.isArray(data.SEQ_lst) ? data.SEQ_lst.length : 0);
      document.getElementById('stepCount').textContent = String(Array.isArray(data.STEP_lst) ? data.STEP_lst.length : 0);
    } catch (e) {
      document.getElementById('jobCount').textContent = '-';
      document.getElementById('seqCount').textContent = '-';
      document.getElementById('stepCount').textContent = '-';
    }
  }

  function locateInputError(field, message) {
    if (!field) return;
    if (field === body) {
      const toggle = document.getElementById('editorToggleButton');
      if (toggle && toggle.getAttribute('aria-expanded') === 'false') toggle.click();
    }
    field.classList.add('ux-input-error');
    field.setAttribute('aria-invalid', 'true');
    let hint = document.getElementById('ux-error-' + field.id);
    if (!hint) {
      hint = document.createElement('p'); hint.id = 'ux-error-' + field.id;
      hint.className = 'ux-field-error'; hint.setAttribute('role', 'status');
      field.insertAdjacentElement('afterend', hint);
      const previous = field.getAttribute('aria-describedby') || '';
      field.setAttribute('aria-describedby', (previous + ' ' + hint.id).trim());
      field.addEventListener('input', function () {
        field.classList.remove('ux-input-error'); field.removeAttribute('aria-invalid'); hint.hidden = true;
      });
      field.addEventListener('change', function () {
        field.classList.remove('ux-input-error'); field.removeAttribute('aria-invalid'); hint.hidden = true;
      });
    }
    hint.textContent = message; hint.hidden = false;
    field.scrollIntoView({behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center'});
    field.focus({preventScroll: true});
  }

  function showUnifiedNotice(title, message, type) {
    if (type === 'error') {
      if (String(message).indexOf(T.json_invalid) === 0) locateInputError(body, message);
      else if (message === T.target_required) locateInputError(deploymentMode === 'multi' ? targetIps : singleTargetIp, message);
      else if (message === switchText.invalid) locateInputError(!switchJob.value ? switchJob : switchSeq, message);
    }
    const noticeKey=String(type)+'|'+String(title)+'|'+String(message);
    if(noticeKey===lastNoticeKey && Date.now()-lastNoticeAt<2000)return true;
    lastNoticeKey=noticeKey;lastNoticeAt=Date.now();
    if (window.IdasNotify && typeof window.IdasNotify.show === 'function') {
      window.IdasNotify.show({title: title, message: message, type: type || 'info'});
      return true;
    }
    showStatus(title + ': ' + message, type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'error'));
    return false;
  }

  function confirmWrite(precheck, targets, payload) {
    const counts = payloadCounts(payload);
    const targetList = Array.isArray(targets) ? targets : parseTargets();
    const readyCount = precheck ? Number(precheck.readyCount || 0) : targetList.length;
    const skippedCount = Math.max(0, targetList.length - readyCount);
    let message = T.confirm_message;
    message += '\n\nIP: ' + targetList.join(', ');
    message += '\n\n' + T.target_count + ': ' + targetList.length;
    message += '\n' + T.confirm_ready + ': ' + readyCount;
    message += '\n' + T.confirm_skipped + ': ' + skippedCount;
    message += '\nJOB: ' + counts.jobs + ' / SEQ: ' + counts.sequences + ' / STEP: ' + counts.steps;
    message += '\n' + switchText.title + ': JOB ' + switchJob.value + ' / SEQ ' + switchSeq.value;
    if (precheck && !precheck.allReady) message += '\n\n' + T.partial_ready;
    return new Promise(function (resolve) {
      // A page-owned dialog avoids shared Alertify lifecycle state.
      const modal = document.createElement('dialog');
      modal.className = 'ux-write-dialog';
      modal.setAttribute('aria-labelledby', 'ux-write-dialog-title');
      const title = document.createElement('h2'); title.id = 'ux-write-dialog-title'; title.textContent = T.confirm_title;
      const summary = document.createElement('div'); summary.className = 'ux-write-confirm'; summary.textContent = message;
      const actions = document.createElement('div'); actions.className = 'ux-confirm-actions';
      const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'btn btn-secondary'; cancel.textContent = T.cancel;
      const accept = document.createElement('button'); accept.type = 'button'; accept.className = 'btn btn-primary'; accept.textContent = T.confirm_ok;
      actions.append(cancel, accept); modal.append(title, summary, actions); document.body.append(modal);
      let settled = false;
      const finish = function (confirmed) {
        if (settled) return; settled = true;
        if (modal.open) modal.close();
        modal.remove();
        resolve(confirmed === true);
      };
      cancel.addEventListener('click', function () { finish(false); });
      accept.addEventListener('click', function () { finish(true); });
      modal.addEventListener('cancel', function (event) { event.preventDefault(); finish(false); });
      modal.addEventListener('close', function () { finish(false); });
      try { modal.showModal(); cancel.focus(); }
      catch (error) { finish(false); showUnifiedNotice(T.error, T.common_notice_failed, 'error'); }
    });
  }

  function updateRetryTargets(batchData) {
    lastFailedTargets = Array.isArray(batchData && batchData.results) ? batchData.results
      .filter(function (r) { return r && !r.success; })
      .map(function (r) { return r.ip; })
      .filter(Boolean) : [];
    retryFailedButton.style.display = deploymentMode === 'multi' && lastFailedTargets.length ? '' : 'none';
  }

  function showApiDetails(responseData) {
    responseElement.textContent = JSON.stringify(sanitizeApiResponse(responseData), null, 2);
    responseCard.style.display = 'block';
    responseCard.open = false;
  }

  async function executeWrite(targets, payload, isRetry) {
    if (busy) return;
    setBusy(true);
    responseCard.style.display = 'none';
    showStatus(isRetry ? T.retrying_failed : T.write_precheck, '');
    try {
      const switchPair = selectedSwitchPair(payload);
      switchResult.textContent = '';
      let effectivePrecheck = null;
      if (isRetry) {
        const checked = await callRemoteApi({api_version:apiVersion, action:'check', targets:targets});
        const checkedData = operationData(checked.data);
        const checks = Array.isArray(checkedData.checks) ? checkedData.checks : [];
        const readyCount = checks.filter(function (item) { return !!(item && item.ready); }).length;
        effectivePrecheck = {allReady: readyCount === targets.length, anyReady: readyCount > 0, readyCount: readyCount, checks: checks};
        renderTargetChecks(checks);
      } else {
        effectivePrecheck = await checkTargets(false, false);
      }
      if (!effectivePrecheck || !effectivePrecheck.anyReady) {
        showUnifiedNotice(T.warning_notice, T.no_ready, 'warning');
        showStatus(T.no_ready, 'error');
        return;
      }
      if (!(await confirmWrite(effectivePrecheck, targets, payload))) {
        const cancelled = {'zh-tw':'已取消寫入，未變更控制器。','zh-cn':'已取消写入，未更改控制器。','en-us':'Write cancelled. No controller changes were made.'};
        showStatus(cancelled[config.language] || cancelled['zh-cn'], '');
        return;
      }

      const result = await callRemoteApi(Object.assign({api_version:apiVersion, action:'write', targets:targets}, payload));
      const responseData = result.data;
      showApiDetails(responseData);
      const batchData = operationData(responseData);
      renderTargetChecks(batchData.checks || [], batchData.results || [], batchData.elapsed_ms);
      showBatchSummary(batchData, 'write');
      updateRetryTargets(batchData);
      const switchResults = await switchWrittenControllers(batchData.results || [], switchPair);
      responseData.auto_switch = {job_id: switchPair.job_id, seq_id: switchPair.seq_id, results: switchResults};
      if (lastExportResult) lastExportResult.auto_switch = responseData.auto_switch;
      showApiDetails(responseData);
      const switchFailed = switchResults.some(function (r) { return !r.success; });

      // Refresh controller availability after deployment. This does not rewrite anything.
      try {
        const refreshed = await callRemoteApi({api_version:apiVersion, action:'check', targets:targets});
        const refreshedData = operationData(refreshed.data);
        const refreshedChecks = Array.isArray(refreshedData.checks) ? refreshedData.checks : [];
        if (refreshedChecks.length) renderTargetChecks(refreshedChecks, batchData.results || [], batchData.elapsed_ms);
      } catch (recheckError) {
        // Keep the deployment result intact if the post-write status refresh fails.
      }

      if (switchFailed) {
        overallResult.className='overall-result warning';
        overallResult.textContent=T.overall_status+': '+switchText.failed;
        showUnifiedNotice(T.warning_notice, switchText.failed, 'warning');
        showStatus(switchText.failed, 'warning');
      } else if (responseData && responseData.success) {
        jsonDirty = false;
        showUnifiedNotice(T.success, switchText.ok, 'success');
        showStatus(switchText.ok, 'success');
      } else if (Number(batchData && batchData.summary && batchData.summary.success || 0) > 0) {
        showUnifiedNotice(T.warning_notice, T.write_partial, 'warning');
        showStatus(T.write_partial, 'warning');
      } else if (responseData && responseData.error && responseData.error.code === 'NO_READY_TARGETS') {
        showUnifiedNotice(T.warning_notice, T.no_ready, 'warning');
        showStatus(T.no_ready, 'error');
      } else {
        const err = responseData && responseData.error ? responseData.error : {};
        const message = errorMessageFromCode(err.code, err.message) || T.write_none;
        showUnifiedNotice(T.error, message, 'error');
        showStatus(T.write_none, 'error');
      }
    } catch (error) {
      responseElement.textContent = error.message || T.connection_failed;
      responseCard.style.display = 'block';
      responseCard.open = false;
      showUnifiedNotice(T.error, error.message || T.connection_failed, 'error');
      showStatus(T.connection_status_failed, 'error');
    } finally {
      setBusy(false);
    }
  }

  downloadSampleButton.addEventListener('click', function () {
    if (busy) return;
    try {
      const payload = applyComputerTime(JSON.parse(defaultJsonText));
      const request = buildApiRequest(payload, 'write');
      const blob = new Blob([JSON.stringify(request, null, 2)], {type: 'application/json;charset=utf-8'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'job_config_api_sample.json';
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(function () { URL.revokeObjectURL(url); }, 0);
      showUnifiedNotice(T.success, T.download_ready, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.json_invalid + error.message, 'error');
    }
  });

  copyApiButton.addEventListener('click', async function () {
    if (busy) return;
    try {
      const payload = refreshJsonTimeFromComputer();
      const request = buildApiRequest(payload, 'write');
      const text = JSON.stringify(request, null, 2);
      await copyTextToClipboard(text);
      showUnifiedNotice(T.success, T.copy_success, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.copy_failed, 'error');
    }
  });

  exportResultJsonButton.addEventListener('click', function () {
    if (!lastExportResult) { showUnifiedNotice(T.warning_notice, T.no_result_export, 'warning'); return; }
    downloadBlob('job_config_result_' + Date.now() + '.json', JSON.stringify(lastExportResult, null, 2), 'application/json;charset=utf-8');
    showUnifiedNotice(T.success, T.export_ready, 'success');
  });

  exportResultCsvButton.addEventListener('click', function () {
    if (!lastExportResult) { showUnifiedNotice(T.warning_notice, T.no_result_export, 'warning'); return; }
    exportResultCsv(lastExportResult);
    showUnifiedNotice(T.success, T.export_ready, 'success');
  });

  editorToggleButton.addEventListener('click', function () {
    const collapsed = editorShell.classList.toggle('collapsed');
    editorToggleButton.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    editorToggleButton.textContent = (collapsed ? '▾ ' + T.expand_json : '▴ ' + T.collapse_json);
  });

  restoreSampleButton.addEventListener('click', function () {
    if (!busy) restoreBuiltInSample();
  });

  formatButton.addEventListener('click', function () {
    if (busy) return;
    try {
      body.value = JSON.stringify(JSON.parse(body.value), null, 2);
      jsonDirty = true;
      saveJsonDraftNow();
      updateCounts();
      showStatus(T.json_valid, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.json_invalid + error.message, 'error');
      showStatus(T.json_invalid + error.message, 'error');
    }
  });

  file.addEventListener('change', async function () {
    if (busy || !file.files[0]) return;
    fileName.textContent = file.files[0].name;
    try {
      body.value = await file.files[0].text();
      body.value = JSON.stringify(JSON.parse(body.value), null, 2);
      jsonDirty = true;
      saveJsonDraftNow();
      updateCounts();
      showStatus(T.file_loaded + file.files[0].name, 'success');
    } catch (error) {
      showUnifiedNotice(T.error, T.file_invalid + error.message, 'error');
      showStatus(T.file_invalid + error.message, 'error');
    }
  });

  modeSingle.addEventListener('change', function () { if (modeSingle.checked) setDeploymentMode('single', true); });
  modeMulti.addEventListener('change', function () { if (modeMulti.checked) setDeploymentMode('multi', true); });
  singleTargetIp.addEventListener('input', updateTargetCount);
  singleTargetIp.addEventListener('blur', normalizeTargetInput);
  singleTargetIp.addEventListener('paste', function (event) {
    const text = event.clipboardData ? event.clipboardData.getData('text') : '';
    const pastedTargets = String(text || '').split(/[\s,;]+/).filter(function (v) { return isValidIpv4(v.trim()); });
    if (pastedTargets.length > 1) {
      event.preventDefault();
      targetIps.value = pastedTargets.join('\n');
      setDeploymentMode('multi', false);
      normalizeTargetInput();
    }
  });
  targetIps.addEventListener('input', updateTargetCount);
  targetIps.addEventListener('blur', normalizeTargetInput);
  targetIps.addEventListener('paste', function () { setTimeout(normalizeTargetInput, 0); });
  checkTargetsButton.addEventListener('click', function () { checkTargets(true, true); });
  checkTargetsButtonMulti.addEventListener('click', function () { checkTargets(true, true); });
  body.addEventListener('input', function () { jsonDirty = true; scheduleJsonInputProcessing(); });

  previewButton.addEventListener('click', async function () {
    if (busy) return;
    let payload;
    try { payload = refreshJsonTimeFromComputer(); }
    catch (error) { showUnifiedNotice(T.error, T.json_invalid + error.message, 'error'); return; }
    const targets = normalizeTargetInput();
    if (!targets.length) { showUnifiedNotice(T.error, T.target_required, 'error'); return; }
    setBusy(true);
    showStatus(T.previewing, '');
    try {
      const result = await callRemoteApi(Object.assign({api_version:apiVersion, action:'preview', targets:targets}, payload));
      const data = operationData(result.data);
      renderTargetChecks(data.checks || [], data.results || [], data.elapsed_ms);
      showBatchSummary(data, 'preview');
      showApiDetails(result.data);
      showStatus(T.preview_done, Number(data && data.summary && data.summary.success || 0) > 0 ? 'success' : 'warning');
    } catch (error) {
      showUnifiedNotice(T.error, error.message || T.connection_failed, 'error');
      showStatus(T.connection_status_failed, 'error');
    } finally {
      setBusy(false);
    }
  });

  retryFailedButton.addEventListener('click', async function () {
    if (busy) return;
    if (!lastFailedTargets.length) {
      showUnifiedNotice(T.warning_notice, T.no_failed_targets, 'warning');
      return;
    }
    let payload;
    try { payload = refreshJsonTimeFromComputer(); }
    catch (error) { showUnifiedNotice(T.error, T.json_invalid + error.message, 'error'); return; }
    await executeWrite(lastFailedTargets.slice(), payload, true);
  });

  send.addEventListener('click', async function () {
    if (busy) return;
    let payload;
    try {
      payload = refreshJsonTimeFromComputer();
    } catch (error) {
      showUnifiedNotice(T.error, T.json_invalid + error.message, 'error');
      showStatus(T.json_invalid + error.message, 'error');
      return;
    }
    const targets = normalizeTargetInput();
    if (!targets.length) {
      showUnifiedNotice(T.error, T.target_required, 'error');
      showStatus(T.target_required, 'error');
      return;
    }
    await executeWrite(targets, payload, false);
  });

  window.addEventListener('beforeunload', function (event) {
    if (!busy && !jsonDirty) return;
    event.preventDefault();
    event.returnValue = T.leave_warning;
    return T.leave_warning;
  });

  setDeploymentMode('single', false);
  updateTargetCount();
  loadNetworkInfo();

  const draftRestoreState = restoreSavedJsonDraft();
  if (draftRestoreState === 'restored') {
    const sourceText = {
      'zh-tw':['目前顯示瀏覽器保留的草稿；不會自動套用新版範例。新版範例：JOB 10／1 SEQ／5 STEP，Thread_Calcu = 31。','載入新版 5 STEP 範例'],
      'zh-cn':['当前显示浏览器保留的草稿；不会自动应用新版示例。新版示例：JOB 10／1 SEQ／5 STEP，Thread_Calcu = 31。','载入新版 5 STEP 示例'],
      'en-us':['Showing your saved browser draft. The updated sample is not applied automatically. New sample: JOB 10 / 1 SEQ / 5 STEP, Thread_Calcu = 31.','Load updated 5 STEP sample']
    }[config.language] || ['Showing your saved browser draft. New sample: JOB 10 / 1 SEQ / 5 STEP, Thread_Calcu = 31.','Load updated 5 STEP sample'];
    draftSourceNotice = document.createElement('div');
    draftSourceNotice.id = 'jsonDraftSourceNotice';
    draftSourceNotice.style.cssText = 'padding:12px 16px;margin:0 0 16px;border:1px solid #e8c87b;border-radius:8px;background:#fff8e7;color:#704e13;display:flex;align-items:center;flex-wrap:wrap;gap:12px';
    const message = document.createElement('span'); message.textContent = sourceText[0];
    loadCurrentSampleButton = document.createElement('button');
    loadCurrentSampleButton.type = 'button';loadCurrentSampleButton.className = 'btn btn-secondary';
    loadCurrentSampleButton.textContent = sourceText[1];
    loadCurrentSampleButton.addEventListener('click', function () {if (!busy) restoreBuiltInSample();});
    draftSourceNotice.append(message, loadCurrentSampleButton);
    const summary = document.querySelector('.config-summary');
    if (summary) summary.before(draftSourceNotice);
    updateCounts();
  } else {
    try {
      refreshJsonTimeFromComputer();
    } catch (error) {
      updateCounts();
    }
    if (draftRestoreState === 'invalid') {
      showUnifiedNotice(T.warning_notice, T.draft_invalid_recovered, 'warning');
    }
  }

  const resetButton=document.getElementById('backDefaultButton');
  const resetLabels=document.documentElement.lang.toLowerCase().startsWith('en')?['Restore defaults','This removes JOB / SEQ / STEP and I/O settings. Continue?','Reset completed','Reset failed']:document.documentElement.lang.toLowerCase().includes('cn')?['恢复默认','将清除 JOB／SEQ／STEP 与 I/O 设置。确定执行？','恢复默认完成','恢复默认失败']:['恢復預設','將清除 JOB／SEQ／STEP 與 I/O 設定。確定執行？','恢復預設完成','恢復預設失敗'];
  if(resetButton) resetButton.addEventListener('click',async()=>{
    if(busy)return;
    let targets;try{targets=parseTargets();if(!targets.length)throw new Error(T.same_subnet_only);}catch(e){showUnifiedNotice(resetLabels[0],e.message,'error');return;}
    if(!window.alertify||typeof window.alertify.confirm!=='function')return;
    setBusy(true);resetButton.disabled=true;
    try{
      const confirmed=await new Promise(resolve=>{const dialog=window.alertify.confirm(resetLabels[0],resetLabels[1]+'<br>'+targets.map(ip=>String(ip).replace(/[&<>"']/g,'')).join('<br>'),()=>resolve(true),()=>resolve(false));dialog.set('labels',{ok:T.confirm_ok,cancel:T.cancel});});
      if(!confirmed)return;
      const lines=[];let failed=false;
      for(const ip of targets){
        try{const response=await fetch(apiEndpoint.replace('replace_job_config.php','replace_job_config_reset.php'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({targets:[ip],confirmed:true})});const data=await response.json();if(!response.ok||data.success!==true)throw new Error(data.error&&data.error.message||resetLabels[3]);lines.push(ip+': '+resetLabels[2]);}
        catch(e){failed=true;lines.push(ip+': '+resetLabels[3]+' · '+e.message);}
      }
      clearRenderedTargetChecks();showUnifiedNotice(resetLabels[0],lines.join('\n'),failed?'error':'success');
    }finally{setBusy(false);resetButton.disabled=false;}
  });

  const deviceButtons=[document.getElementById('disableDeviceButton'),document.getElementById('enableDeviceButton')];
  const deviceText=document.documentElement.lang.toLowerCase().startsWith('en')?['Disable','Enable','Apply this operation to the following controllers?','Completed','Failed']:document.documentElement.lang.toLowerCase().includes('cn')?['停用','启用','确定对以下控制器执行此操作？','完成','失败']:['停用','啟用','確定對以下控制器執行此操作？','完成','失敗'];
  deviceButtons.forEach((button,index)=>{if(!button)return;button.addEventListener('click',async()=>{
    if(busy)return;const targets=parseTargets();if(!targets.length){showUnifiedNotice(deviceText[index],T.same_subnet_only,'error');return;}
    if(!window.alertify||typeof window.alertify.confirm!=='function')return;
    setBusy(true);deviceButtons.forEach(b=>{if(b)b.disabled=true;});
    try{
      const confirmed=await new Promise(resolve=>{const dialog=window.alertify.confirm(deviceText[index],deviceText[2]+'<br>'+targets.map(ip=>String(ip).replace(/[&<>"']/g,'')).join('<br>'),()=>resolve(true),()=>resolve(false));dialog.set('labels',{ok:T.confirm_ok,cancel:T.cancel});});
      if(!confirmed)return;const lines=[];let failed=false;
      for(const ip of targets){try{const response=await fetch(apiEndpoint.replace('replace_job_config.php','replace_job_config_device.php'),{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({targets:[ip],action:index===0?'disable':'enable',confirmed:true})});const data=await response.json();if(!response.ok||data.success!==true)throw new Error(data.error&&data.error.message||deviceText[4]);lines.push(ip+': '+deviceText[index]+deviceText[3]);}catch(e){failed=true;lines.push(ip+': '+deviceText[4]+' · '+String(e.message).replace(/(?:禁用|停用)[電电]批/g,deviceText[0]).replace(/(?:啟用|启用)[電电]批/g,deviceText[1]));}}
      showUnifiedNotice(deviceText[index],lines.join('\n'),failed?'error':'success');
    }finally{setBusy(false);deviceButtons.forEach(b=>{if(b)b.disabled=false;});}
  });});
}());
