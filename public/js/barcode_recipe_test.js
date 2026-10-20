(function () {
  'use strict';
  const settings = window.IDAS_BARCODE_TEST_UI;
  if (!settings) return;
  const t = settings.text;
  const form = document.getElementById('barcodeTestForm');
  const input = document.getElementById('barcodeTestInput');
  const send = document.getElementById('barcodeTestSend');
  const mock = document.getElementById('barcodeTestMock');
  const load = document.getElementById('barcodeTestLoad');
  const status = document.getElementById('barcodeTestStatus');
  const results = document.getElementById('barcodeTestResults');
  let recipe = null;
  let busy = false;
  const show = (part, value) => {
    document.getElementById('barcodeTest_' + part).textContent =
      value == null ? '—' : JSON.stringify(value, null, 2);
  };
  const validRecipe = value => value && ['JOB_lst', 'SEQ_lst', 'STEP_lst'].every(
    key => Array.isArray(value[key]) && value[key].length > 0
  );
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (busy) return;
    const useMock = !!(event.submitter && event.submitter.value === 'mock');
    const barcode = input.value.trim();
    if (!barcode) { status.textContent = t.empty; input.focus(); return; }
    busy = true; send.disabled = true; mock.disabled = true; input.disabled = true; load.disabled = true;
    recipe = null; results.hidden = true; status.textContent = t.pending;
    ['mes', 'context', 'recipe', 'response'].forEach(part => show(part, null));
    const abort = new AbortController();
    const timer = setTimeout(() => abort.abort(), 30000);
    try {
      const response = await fetch(settings.endpoint, {
        method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify(useMock ? {barcode, mock_recipe: true} : {barcode}), cache: 'no-store', signal: abort.signal
      });
      const raw = await response.text();
      let body;
      try { body = JSON.parse(raw); }
      catch (_) {
        results.hidden = false;
        document.getElementById('barcodeTest_response').textContent = raw;
        status.textContent = t.error + ' (HTTP ' + response.status + ')';
        return;
      }
      results.hidden = false;
      show('response', body); show('mes', body.mock_mes_responses || body.mes_response);
      show('context', body.work_order_context); show('recipe', body.job_config);
      if (!response.ok || body.success !== true) {
        status.textContent = t.error + ' (HTTP ' + response.status + ')' + (body.message ? '\n' + body.message : '');
        return;
      }
      if (!body.mock && (body.test_mode || body.status === 'test_skipped')) { status.textContent = t.test; return; }
      if (!validRecipe(body.job_config)) { status.textContent = t.missing; return; }
      recipe = body.job_config; load.disabled = false;
      status.textContent = body.mock ? t.mockOk : (body.work_order_context ? t.ok : t.unknown);
      const preview = document.getElementById('barcodeTest_recipe');
      if (preview) {
        const detail = preview.closest('details'); if (detail) detail.open = true;
        preview.scrollIntoView({behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'center'});
      }
    } catch (_) { status.textContent = t.network; }
    finally { clearTimeout(timer); busy = false; send.disabled = false; mock.disabled = false; input.disabled = false; }
  });
  load.addEventListener('click', async () => {
    if (!recipe || busy) return;
    load.disabled = true;
    const selected = recipe;
    try {
      const confirmed = await new Promise(resolve => {
        if (window.alertify && window.alertify.confirm) {
          const ui = window.IDAS_JOB_CONFIG_UI || {};
          const labels = ui.translations || {};
          const dialog = window.alertify.confirm(t.title, t.confirm, () => resolve(true), () => resolve(false));
          dialog.set('labels', {ok: labels.confirm_ok || 'OK', cancel: labels.cancel || 'Cancel'});
        } else resolve(window.confirm(t.confirm));
      });
      if (!confirmed || selected !== recipe || busy) return;
      const editor = document.getElementById('jsonBody');
      // Keep target IPs and action under the existing deployment controls.
      editor.value = JSON.stringify({JOB_lst: selected.JOB_lst, SEQ_lst: selected.SEQ_lst, STEP_lst: selected.STEP_lst}, null, 2);
      editor.dispatchEvent(new Event('input', {bubbles: true}));
      const toggle = document.getElementById('editorToggleButton');
      if (toggle && toggle.getAttribute('aria-expanded') === 'false') toggle.click();
      const card = editor.closest('.recipe-json-card');
      const summary = card && card.querySelector('.config-summary');
      const destination = summary || card || editor;
      destination.scrollIntoView({behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start'});
      destination.setAttribute('tabindex', '-1'); destination.focus({preventScroll: true});
      destination.classList.remove('ux-recipe-loaded');
      void destination.offsetWidth;
      destination.classList.add('ux-recipe-loaded');
      setTimeout(() => destination.classList.remove('ux-recipe-loaded'), 2400);
      status.textContent = t.loaded;
    } finally { load.disabled = busy || !recipe; }
  });
})();
