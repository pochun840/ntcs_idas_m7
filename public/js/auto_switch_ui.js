(function () {
  'use strict';
  function init() {
    var job = document.getElementById('autoSwitchJob');
    var seq = document.getElementById('autoSwitchSeq');
    var raw = document.getElementById('autoSwitchResult');
    if (!job || !seq || !raw || document.getElementById('autoSwitchVisual')) return;
    var section = raw.closest('.section-card');
    if (!section) return;
    var lang = (document.documentElement.lang || '').toLowerCase();
    var words = lang.indexOf('zh') === 0 ? (lang.indexOf('cn') >= 0 || lang.indexOf('hans') >= 0 ?
      {target:'切换目标',job:'工作 JOB',seq:'程序 SEQ',ready:'请选择目标 JOB / SEQ',result:'切换结果',empty:'写入成功后，将在此显示各设备的切换结果。'} :
      {target:'切換目標',job:'工作 JOB',seq:'程序 SEQ',ready:'請選擇目標 JOB / SEQ',result:'切換結果',empty:'寫入成功後，將在此顯示各設備的切換結果。'}) :
      {target:'Switch target',job:'Job',seq:'Sequence',ready:'Select a target JOB / SEQ',result:'Switch results',empty:'Results for each controller will appear here after a successful DB write.'};
    var states = (window.IDAS_JOB_CONFIG_UI || {}).switchText || {};
    section.classList.add('auto-switch-card');
    var body = raw.parentElement;
    var layout = document.createElement('div'); layout.className = 'auto-switch-controls';
    [[job,words.job],[seq,words.seq]].forEach(function (item) {
      var select = item[0];
      var oldLabel = body.querySelector('label[for="' + select.id + '"]');
      if (oldLabel) oldLabel.remove();
      var field = document.createElement('div'); field.className = 'auto-switch-field';
      var label = document.createElement('label'); label.htmlFor = select.id; label.textContent = item[1];
      select.setAttribute('aria-label',item[1]);
      field.appendChild(label); field.appendChild(select); layout.appendChild(field);
    });
    var target = document.createElement('div'); target.className = 'auto-switch-target';
    var targetLabel = document.createElement('span'); targetLabel.textContent = words.target;
    var targetValue = document.createElement('strong');
    target.appendChild(targetLabel); target.appendChild(targetValue); layout.appendChild(target);
    body.insertBefore(layout,raw);
    raw.classList.add('auto-switch-raw'); raw.removeAttribute('role'); raw.removeAttribute('aria-live');
    var visual = document.createElement('div'); visual.id = 'autoSwitchVisual';
    visual.className = 'auto-switch-results'; visual.setAttribute('role','status'); visual.setAttribute('aria-live','polite');
    body.appendChild(visual);
    function updateTarget() {
      targetValue.textContent = job.value !== '' && seq.value !== '' ? 'JOB ' + job.value + ' / SEQ ' + seq.value : words.ready;
    }
    function renderResults() {
      var lines = raw.textContent.trim().split('\n').filter(function (line) { return line.trim(); });
      visual.replaceChildren();
      var title = document.createElement('div'); title.className = 'auto-switch-result-title'; title.textContent = words.result;
      visual.appendChild(title);
      if (!lines.length) {
        var empty = document.createElement('div'); empty.className = 'auto-switch-empty'; empty.textContent = words.empty;
        visual.appendChild(empty); return;
      }
      if (/^JOB\s+\d+\s*\/\s*SEQ\s+\d+/.test(lines[0])) {
        var executed = document.createElement('div'); executed.className = 'auto-switch-executed'; executed.textContent = lines.shift();
        visual.appendChild(executed);
      }
      lines.forEach(function (line) {
        var state = states.failed && line.indexOf(states.failed) >= 0 ? 'failed' :
          states.ok && line.indexOf(states.ok) >= 0 ? 'success' :
          states.pending && line.indexOf(states.pending) >= 0 ? 'pending' : 'neutral';
        var row = document.createElement('div'); row.className = 'auto-switch-result-row is-' + state;
        var icon = document.createElement('span'); icon.className = 'auto-switch-status-icon'; icon.setAttribute('aria-hidden','true');
        icon.textContent = state === 'success' ? '✓' : state === 'failed' ? '!' : state === 'pending' ? '…' : '·';
        var content = document.createElement('div'); content.className = 'auto-switch-result-content';
        var match = line.match(/^(\d{1,3}(?:\.\d{1,3}){3}):\s*(.*)$/);
        if (match) {
          var ip = document.createElement('strong'); ip.className = 'auto-switch-device'; ip.textContent = match[1]; content.appendChild(ip);
        }
        var message = document.createElement('span'); message.textContent = match ? match[2] : line; content.appendChild(message);
        row.appendChild(icon); row.appendChild(content); visual.appendChild(row);
      });
    }
    job.addEventListener('change',updateTarget); seq.addEventListener('change',updateTarget);
    var choiceObserver = new MutationObserver(updateTarget);
    [job,seq].forEach(function (select) { choiceObserver.observe(select,{childList:true,subtree:true,attributes:true,attributeFilter:['disabled']}); });
    new MutationObserver(renderResults).observe(raw,{childList:true,subtree:true,characterData:true});
    updateTarget(); renderResults();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',init); else init();
}());
