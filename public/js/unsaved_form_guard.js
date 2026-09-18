(function (window, document) {
  'use strict';

  if (window.__IDAS_UNSAVED_GUARD_INSTALLED__) return;
  window.__IDAS_UNSAVED_GUARD_INSTALLED__ = true;

  function init() {
    const controller = getControllerName();

    // Login is not a configuration form. Never track password/account inputs,
    // show unsaved warnings, or open the save-diff preview on the login screen.
    if (isLoginPage(controller)) return;

    const EDITABLE_CONTROLLERS = new Set([
      'jobs',
      'sequences',
      'step',
      'inputs',
      'outputs',
      'settings',
      'admins',
      'admin',
      'license',
      'remotes',
      'remote',
      'customize'
    ]);

    const CONTROL_SELECTOR = 'input, select, textarea, [contenteditable="true"]';
    const MODAL_SELECTOR = '.w3-modal, .modal, [role="dialog"], [data-modal], [id*="modal"], [id*="Modal"], [id*="MODAL"]';
    const DIRTY_CLASS = 'idas-field-modified';
    const INVALID_SELECTOR = '.is-invalid, [aria-invalid="true"], input:invalid, select:invalid, textarea:invalid';

    const COPY = {
      'zh-tw': {
        title: '尚未儲存',
        message: '表單內容尚未儲存，確定要離開嗎？',
        ok: '離開',
        cancel: '繼續編輯',
        previewTitle: '確認儲存變更',
        previewMessage: '以下 {count} 項內容將被更新，請確認後再儲存。',
        previewField: '欄位',
        previewBefore: '原值',
        previewAfter: '新值',
        previewSave: '儲存',
        previewCancel: '取消',
        previewEmpty: '（空白）',
        reloadTitle: '尚未儲存變更',
        reloadMessage: '重新載入後，尚未儲存的內容將會遺失。',
        reloadOk: '重新載入',
        reloadCancel: '繼續編輯'
      },
      'zh-cn': {
        title: '尚未保存',
        message: '表单内容尚未保存，确定要离开吗？',
        ok: '离开',
        cancel: '继续编辑',
        previewTitle: '确认保存更改',
        previewMessage: '以下 {count} 项内容将被更新，请确认后再保存。',
        previewField: '字段',
        previewBefore: '原值',
        previewAfter: '新值',
        previewSave: '保存',
        previewCancel: '取消',
        previewEmpty: '（空白）',
        reloadTitle: '尚未保存变更',
        reloadMessage: '重新加载后，尚未保存的内容将会丢失。',
        reloadOk: '重新加载',
        reloadCancel: '继续编辑'
      },
      'en-us': {
        title: 'Unsaved changes',
        message: 'Your changes have not been saved. Are you sure you want to leave?',
        ok: 'Leave',
        cancel: 'Keep editing',
        previewTitle: 'Confirm Changes',
        previewMessage: 'The following {count} item(s) will be updated. Please review before saving.',
        previewField: 'Field',
        previewBefore: 'Original',
        previewAfter: 'New',
        previewSave: 'Save',
        previewCancel: 'Cancel',
        previewEmpty: '(empty)',
        reloadTitle: 'Unsaved changes',
        reloadMessage: 'Reloading will discard your unsaved changes.',
        reloadOk: 'Reload',
        reloadCancel: 'Keep editing'
      }
    };

    const baselines = new WeakMap();
    const baselineDisplays = new WeakMap();
    const dirtyControls = new Set();

    let ready = false;
    let allowLeave = false;
    let dialogOpen = false;
    let pendingSaveUntil = 0;
    let pendingSaveScope = null;
    let pendingSaveTimer = null;
    let validationWatchUntil = 0;
    let validationFocusTimer = null;
    let lastInvalidFocus = null;
    let savePreviewOpen = false;
    let previewBypassTarget = null;
    let previewBypassUntil = 0;
    const previewSubmitBypass = new WeakSet();

    function isLoginPage(controllerName) {
      if (window.IDAS_IS_LOGIN_PAGE === true) return true;

      const name = String(controllerName || '').toLowerCase();
      if (name === 'login' || name === 'logins') return true;

      // Fallback for older/cached headers where the server-side view flag is
      // not present yet. init() runs after DOMContentLoaded, so the login form
      // can be identified reliably here.
      if (document.getElementById('loginForm')) return true;
      try {
        if (document.querySelector('form[action*="url=Logins"], form[action*="url=Login"]')) return true;
      } catch (e) {}
      return false;
    }

    function getControllerName() {
      let route = '';
      try {
        route = new URLSearchParams(window.location.search).get('url') || '';
      } catch (e) {
        const match = String(window.location.search || '').match(/[?&]url=([^&]+)/i);
        route = match ? decodeURIComponent(match[1]) : '';
      }
      route = String(route || '').replace(/^\/+/, '');
      return (route.split('/')[0] || '').trim().toLowerCase();
    }

    function getLanguage() {
      let value = 'en-us';
      try {
        if (typeof window.getCookie === 'function') {
          value = window.getCookie('language') || value;
        } else {
          const match = document.cookie.match(/(?:^|;\s*)language=([^;]+)/);
          if (match) value = decodeURIComponent(match[1]);
        }
      } catch (e) {}

      value = String(value || 'en-us').toLowerCase().replace('_', '-');
      if (value === 'en' || value.indexOf('en-') === 0) return 'en-us';
      if (value === 'zh-cn' || value === 'zh-sg' || value.indexOf('hans') !== -1) return 'zh-cn';
      if (value === 'zh-tw' || value === 'zh-hk' || value === 'zh-mo' || value.indexOf('hant') !== -1) return 'zh-tw';
      return 'en-us';
    }

    function text() {
      return COPY[getLanguage()] || COPY['en-us'];
    }

    function escapeHtml(value) {
      return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function compactText(value) {
      return String(value == null ? '' : value).replace(/\s+/g, ' ').trim();
    }

    function imageChoiceText(img) {
      if (!img) return '';
      const explicit = compactText(img.getAttribute('alt') || img.getAttribute('title') || '');
      if (explicit) return explicit;

      const src = String(img.getAttribute('src') || '').split('?')[0].split('#')[0];
      const file = (src.split('/').pop() || '').toLowerCase();
      const known = {
        'high.png': 'HIGH',
        'low.png': 'LOW',
        'signal01.png': 'Signal 1',
        'signal02.png': 'Signal 2',
        'trigger.png': 'Trigger'
      };
      if (known[file]) return known[file];

      return compactText(file
        .replace(/\.[a-z0-9]+$/i, '')
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, function (m) { return m.toUpperCase(); }));
    }

    function labelChoiceText(label) {
      if (!label) return '';
      const value = compactText(label.textContent || label.innerText || '');
      if (value) return value;
      const img = label.querySelector && label.querySelector('img');
      return imageChoiceText(img);
    }

    function inferredChoiceText(el) {
      if (!el) return '';
      const token = String((el.id || '') + ' ' + (el.name || '')).toLowerCase();
      const rules = [
        [/(?:^|[_\s-])off(?:$|[_\s-])/, 'OFF'],
        [/(?:^|[_\s-])on(?:$|[_\s-])/, 'ON'],
        [/(?:^|[_\s-])high(?:$|[_\s-])/, 'HIGH'],
        [/(?:^|[_\s-])low(?:$|[_\s-])/, 'LOW'],
        [/(?:^|[_\s-])ccw(?:$|[_\s-])/, 'CCW'],
        [/(?:^|[_\s-])cw(?:$|[_\s-])/, 'CW'],
        [/(?:^|[_\s-])auto(?:$|[_\s-])/, 'AUTO'],
        [/(?:^|[_\s-])custom(?:$|[_\s-])/, 'CUSTOM'],
        [/(?:^|[_\s-])(?:unlimit|unlimited)(?:$|[_\s-])/, 'Unlimited'],
        [/(?:^|[_\s-])trigger(?:$|[_\s-])/, 'Trigger']
      ];
      for (let i = 0; i < rules.length; i += 1) {
        if (rules[i][0].test(token)) return rules[i][1];
      }

      const raw = compactText(el.value == null ? '' : el.value);
      if (raw === '0') return 'OFF';
      if (raw === '1') return 'ON';
      return raw;
    }

    function controlChoiceText(el) {
      if (!el) return '';
      const explicit = compactText(
        el.getAttribute('data-option-label')
        || el.getAttribute('data-display-label')
        || el.getAttribute('data-value-label')
        || ''
      );
      if (explicit) return explicit;

      if (el.id) {
        try {
          const label = document.querySelector('label[for="' + String(el.id).replace(/(["\\])/g, '\\$1') + '"]');
          const value = labelChoiceText(label);
          if (value) return value;
        } catch (e) {}
      }

      const wrapper = el.closest && el.closest('label');
      const wrapperValue = labelChoiceText(wrapper);
      if (wrapperValue) return wrapperValue;

      const formCheck = el.closest && el.closest('.form-check');
      if (formCheck) {
        const labels = Array.from(formCheck.querySelectorAll('label'));
        for (let i = 0; i < labels.length; i += 1) {
          const value = labelChoiceText(labels[i]);
          if (value) return value;
        }
      }

      const next = el.nextElementSibling;
      if (next && next.tagName === 'LABEL') {
        const value = labelChoiceText(next);
        if (value) return value;
      }

      const aria = compactText(el.getAttribute('aria-label') || el.getAttribute('title') || '');
      if (aria) return aria;
      return inferredChoiceText(el);
    }

    function readableValue(el) {
      if (!el) return '';

      if (el.isContentEditable) return compactText(el.textContent || el.innerText || '');

      if (el.tagName === 'SELECT') {
        const selected = Array.from(el.selectedOptions || []);
        return selected.map(function (option) { return compactText(option.textContent || option.value); }).join(', ');
      }

      if (el.tagName === 'INPUT') {
        const type = String(el.type || 'text').toLowerCase();
        if (type === 'checkbox') return el.checked ? 'ON' : 'OFF';
        if (type === 'radio') return el.checked ? controlChoiceText(el) : '';
        if (type === 'file') {
          return Array.from(el.files || []).map(function (file) { return file.name; }).join(', ');
        }
      }

      return compactText(el.value == null ? '' : el.value);
    }

    function cleanFieldLabel(value) {
      let label = compactText(value).replace(/[:：]\s*$/, '');
      // Avoid huge container text becoming a field name.
      if (label.length > 80) label = label.slice(0, 77) + '…';
      return label;
    }

    function fallbackFieldName(el) {
      const raw = String((el && (el.getAttribute('name') || el.id)) || 'Field');
      return cleanFieldLabel(raw
        .replace(/[_-]+/g, ' ')
        .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/\s+/g, ' '));
    }

    function rowFieldLabel(el) {
      if (!el || !el.closest) return '';
      const row = el.closest('.row, tr');
      if (!row) return '';

      if (row.tagName === 'TR') {
        const cell = el.closest('td, th');
        if (cell) {
          let prev = cell.previousElementSibling;
          while (prev) {
            const value = cleanFieldLabel(prev.textContent);
            if (value) return value;
            prev = prev.previousElementSibling;
          }
        }
        const th = row.querySelector('th');
        if (th && !th.contains(el)) return cleanFieldLabel(th.textContent);
      }

      let column = el;
      while (column && column.parentElement !== row) column = column.parentElement;
      const children = Array.from(row.children || []);
      const columnIndex = children.indexOf(column);
      if (columnIndex > 0) {
        for (let i = columnIndex - 1; i >= 0; i -= 1) {
          if (children[i].querySelector && children[i].querySelector(CONTROL_SELECTOR)) continue;
          const value = cleanFieldLabel(children[i].textContent);
          if (value) return value;
        }
      }
      return '';
    }

    function fieldLabel(el) {
      if (!el) return 'Field';
      const explicit = el.getAttribute('data-change-label') || el.getAttribute('data-idas-change-label') || el.getAttribute('aria-label');
      if (explicit) return cleanFieldLabel(explicit);

      if (el.id) {
        try {
          const label = document.querySelector('label[for="' + String(el.id).replace(/([\"\\])/g, '\\$1') + '"]');
          if (label) {
            const value = cleanFieldLabel(label.textContent);
            if (value) {
              if (el.tagName === 'INPUT'
                && String(el.type || '').toLowerCase() === 'checkbox'
                && /^\d+$/.test(value)) {
                const rowLabel = rowFieldLabel(el);
                if (rowLabel) return cleanFieldLabel(rowLabel + ' - ' + value);
              }
              return value;
            }
          }
        } catch (e) {}
      }

      const group = el.closest && el.closest('.form-group, .form-row, .input-group, .setting-row');
      if (group) {
        const label = group.querySelector('label');
        if (label && !label.contains(el)) {
          const value = cleanFieldLabel(label.textContent);
          if (value) return value;
        }
      }

      const rowLabel = rowFieldLabel(el);
      if (rowLabel) return rowLabel;

      const placeholder = el.getAttribute && el.getAttribute('placeholder');
      if (placeholder) return cleanFieldLabel(placeholder);
      return fallbackFieldName(el);
    }

    function displayOrEmpty(value) {
      const v = compactText(value);
      return v === '' ? text().previewEmpty : v;
    }

    function isIgnoredElement(el) {
      if (!el || el.nodeType !== 1) return true;
      if (el.matches('[data-unsaved-ignore], [data-idas-unsaved-ignore]')) return true;
      if (el.closest('[data-unsaved-ignore], [data-idas-unsaved-ignore]')) return true;

      // Search / pagination / row-selection controls change the view only; they
      // are not unsaved configuration data and must never trigger a leave alert.
      if (el.closest('.dataTables_wrapper, .dataTables_filter, .dataTables_length')) return true;
      if (el.closest('#JobSelect')) return true;
      if (el.matches('#ckAll, .row-ck, .select-all, .row-select')) return true;

      return false;
    }

    function isTrackableControl(el) {
      if (!el || !el.matches || !el.matches(CONTROL_SELECTOR)) return false;
      if (isIgnoredElement(el)) return false;
      if (el.disabled || el.readOnly || el.getAttribute('aria-disabled') === 'true') return false;

      if (el.tagName === 'INPUT') {
        const type = String(el.type || 'text').toLowerCase();
        if (['hidden', 'button', 'submit', 'reset', 'image', 'search'].includes(type)) return false;
      }

      // Standard forms are always protected. Several legacy Settings / Job / IO
      // pages use form-like controls without an actual <form>, so those known
      // edit modules are protected at page level as well.
      return !!el.closest('form') || EDITABLE_CONTROLLERS.has(controller);
    }

    function controlValue(el) {
      if (!el) return '';

      if (el.isContentEditable) return 'html:' + String(el.innerHTML || '');

      if (el.tagName === 'INPUT') {
        const type = String(el.type || 'text').toLowerCase();
        if (type === 'checkbox' || type === 'radio') {
          return (el.checked ? '1:' : '0:') + String(el.value || '');
        }
        if (type === 'file') {
          const files = Array.from(el.files || []);
          return files.map(function (file) {
            return [file.name, file.size, file.lastModified].join(':');
          }).join('|');
        }
      }

      if (el.tagName === 'SELECT' && el.multiple) {
        return Array.from(el.selectedOptions || []).map(function (option) {
          return String(option.value);
        }).join('\u001f');
      }

      return String(el.value == null ? '' : el.value);
    }

    function setDirtyVisual(el, dirty) {
      if (!el || !el.classList) return;
      el.classList.toggle(DIRTY_CLASS, !!dirty);
      if (dirty) el.setAttribute('data-idas-modified', 'true');
      else el.removeAttribute('data-idas-modified');
    }

    function snapshotControl(el, force) {
      if (!isTrackableControl(el)) return;
      if (force || !baselines.has(el)) {
        baselines.set(el, controlValue(el));
        baselineDisplays.set(el, readableValue(el));
      }
      if (force) {
        dirtyControls.delete(el);
        setDirtyVisual(el, false);
      }
    }

    function snapshotScope(scope, force) {
      const root = scope && scope.querySelectorAll ? scope : document;
      if (root.matches && isTrackableControl(root)) snapshotControl(root, force);
      root.querySelectorAll(CONTROL_SELECTOR).forEach(function (el) {
        snapshotControl(el, force);
      });
    }

    function updateDirtyState(el) {
      if (!isTrackableControl(el)) return;
      if (!baselines.has(el)) baselines.set(el, controlValue(el));

      if (controlValue(el) === baselines.get(el)) {
        dirtyControls.delete(el);
        setDirtyVisual(el, false);
      } else {
        dirtyControls.add(el);
        setDirtyVisual(el, true);
      }
    }

    function pruneDirtyControls() {
      dirtyControls.forEach(function (el) {
        if (!document.documentElement.contains(el) || !isTrackableControl(el)) {
          dirtyControls.delete(el);
          setDirtyVisual(el, false);
          return;
        }

        // If the value was changed back to the original value, it is no longer dirty.
        if (baselines.has(el) && controlValue(el) === baselines.get(el)) {
          dirtyControls.delete(el);
          setDirtyVisual(el, false);
        }
      });
    }

    function isDirty(scope) {
      pruneDirtyControls();
      if (!scope) return dirtyControls.size > 0;

      for (const el of dirtyControls) {
        if (scope === el || (scope.contains && scope.contains(el))) return true;
      }
      return false;
    }

    function controlsInScope(scope) {
      const root = scope && scope.querySelectorAll ? scope : document;
      const controls = [];
      if (root.matches && root.matches(CONTROL_SELECTOR)) controls.push(root);
      root.querySelectorAll(CONTROL_SELECTOR).forEach(function (el) { controls.push(el); });
      return controls;
    }

    function radioGroupRoot(el, fallbackRoot) {
      if (!el || !el.closest) return fallbackRoot || document;
      return el.closest('form') || el.closest(MODAL_SELECTOR) || fallbackRoot || document;
    }

    function radioGroupControls(el, fallbackRoot) {
      if (!el) return [];
      const name = String(el.getAttribute('name') || '');
      if (!name) return [el];
      const groupRoot = radioGroupRoot(el, fallbackRoot);
      return controlsInScope(groupRoot).filter(function (candidate) {
        return candidate.tagName === 'INPUT'
          && String(candidate.type || '').toLowerCase() === 'radio'
          && String(candidate.getAttribute('name') || '') === name
          && isTrackableControl(candidate);
      });
    }

    function radioGroupContexts(group) {
      const values = [];
      (group || []).forEach(function (radio) {
        const value = rowFieldLabel(radio);
        if (value && values.indexOf(value) === -1) values.push(value);
      });
      return values;
    }

    function radioChoiceDisplay(radio, group) {
      if (!radio) return '';
      const choice = controlChoiceText(radio);
      const contexts = radioGroupContexts(group);
      const context = rowFieldLabel(radio);
      if (contexts.length > 1 && context) {
        return cleanFieldLabel(context) + ' / ' + choice;
      }
      return choice;
    }

    function radioBaselineSelection(group) {
      return (group || []).find(function (radio) {
        const value = baselines.get(radio);
        return typeof value === 'string' && value.indexOf('1:') === 0;
      }) || null;
    }

    function radioCurrentSelection(group) {
      return (group || []).find(function (radio) { return !!radio.checked; }) || null;
    }

    function radioGroupFieldLabel(group) {
      const current = radioCurrentSelection(group);
      const original = radioBaselineSelection(group);
      const anchor = current || original || (group && group[0]) || null;
      if (!anchor) return 'Field';

      const explicit = anchor.getAttribute('data-change-label')
        || anchor.getAttribute('data-idas-change-label')
        || (radioGroupRoot(anchor, document).getAttribute && radioGroupRoot(anchor, document).getAttribute('data-change-label'));
      if (explicit) return cleanFieldLabel(explicit);

      const contexts = radioGroupContexts(group);
      if (contexts.length === 1) return cleanFieldLabel(contexts[0]);

      const name = String(anchor.getAttribute('name') || '').toLowerCase();
      if (name.indexOf('pin_option') !== -1) return 'PIN';

      return fallbackFieldName(anchor);
    }

    function collectDirtyChanges(scope) {
      pruneDirtyControls();
      const root = scope && scope.querySelectorAll ? scope : document;
      const changes = [];
      const processedRadios = new Set();

      controlsInScope(root).forEach(function (el) {
        if (!isTrackableControl(el)) return;

        const type = el.tagName === 'INPUT' ? String(el.type || '').toLowerCase() : '';
        if (type === 'radio') {
          if (processedRadios.has(el)) return;
          const group = radioGroupControls(el, root);
          group.forEach(function (radio) { processedRadios.add(radio); });
          if (!group.some(function (radio) { return dirtyControls.has(radio); })) return;

          const beforeRadio = radioBaselineSelection(group);
          const afterRadio = radioCurrentSelection(group);
          const before = beforeRadio ? radioChoiceDisplay(beforeRadio, group) : '';
          const after = afterRadio ? radioChoiceDisplay(afterRadio, group) : '';
          if (compactText(before) === compactText(after)) return;

          changes.push({
            label: radioGroupFieldLabel(group),
            before: displayOrEmpty(before),
            after: displayOrEmpty(after)
          });
          return;
        }

        if (!dirtyControls.has(el)) return;
        const before = baselineDisplays.has(el) ? baselineDisplays.get(el) : '';
        const after = readableValue(el);
        if (compactText(before) === compactText(after)) return;
        changes.push({
          label: fieldLabel(el),
          before: displayOrEmpty(before),
          after: displayOrEmpty(after)
        });
      });

      return changes;
    }

    function previewHtml(changes) {
      const t = text();
      const countText = t.previewMessage.replace('{count}', String(changes.length));
      const rows = changes.map(function (change) {
        return '<tr>'
          + '<td class="idas-change-field">' + escapeHtml(change.label) + '</td>'
          + '<td class="idas-change-before" title="' + escapeHtml(change.before) + '">' + escapeHtml(change.before) + '</td>'
          + '<td class="idas-change-arrow" aria-hidden="true">→</td>'
          + '<td class="idas-change-after" title="' + escapeHtml(change.after) + '">' + escapeHtml(change.after) + '</td>'
          + '</tr>';
      }).join('');

      return '<div class="idas-save-preview">'
        + '<div class="idas-confirm-icon idas-save-preview-icon" aria-hidden="true">'
        + '<svg viewBox="0 0 24 24"><path d="M7 7h11m0 0-3-3m3 3-3 3M17 17H6m0 0 3-3m-3 3 3 3"/></svg>'
        + '</div>'
        + '<div class="idas-save-preview-title">' + escapeHtml(t.previewTitle) + '</div>'
        + '<div class="idas-save-preview-message">' + escapeHtml(countText) + '</div>'
        + '<div class="idas-change-table-wrap">'
        + '<table class="idas-change-table">'
        + '<thead><tr><th>' + escapeHtml(t.previewField) + '</th><th colspan="2">' + escapeHtml(t.previewBefore) + '</th><th>' + escapeHtml(t.previewAfter) + '</th></tr></thead>'
        + '<tbody>' + rows + '</tbody>'
        + '</table></div></div>';
    }

    function plainPreviewText(changes) {
      const t = text();
      const head = t.previewMessage.replace('{count}', String(changes.length));
      return head + '\n\n' + changes.map(function (change) {
        return change.label + ': ' + change.before + ' → ' + change.after;
      }).join('\n');
    }

    function applyPreviewLabels(dialog) {
      if (!dialog) return;
      const t = text();
      try {
        const root = dialog.elements && dialog.elements.dialog ? dialog.elements.dialog : null;
        if (!root) return;
        root.classList.add('idas-confirm-dialog', 'idas-save-preview-dialog');
        root.setAttribute('data-idas-preserve-labels', 'true');
        const ok = root.querySelector('.ajs-ok');
        const cancel = root.querySelector('.ajs-cancel');
        if (ok) ok.textContent = t.previewSave;
        if (cancel) cancel.textContent = t.previewCancel;
      } catch (e) {}
    }

    function showSavePreview(changes, onSave) {
      if (!changes || !changes.length || savePreviewOpen) return false;
      const t = text();

      if (!window.alertify || typeof window.alertify.confirm !== 'function') {
        if (window.confirm(plainPreviewText(changes))) onSave();
        return true;
      }

      savePreviewOpen = true;
      const dialog = window.alertify.confirm(
        t.previewTitle,
        previewHtml(changes),
        function () {
          savePreviewOpen = false;
          onSave();
        },
        function () {
          savePreviewOpen = false;
        }
      );

      if (dialog && typeof dialog.set === 'function') {
        dialog.set({
          labels: { ok: t.previewSave, cancel: t.previewCancel },
          closable: false,
          movable: false,
          pinnable: false,
          resizable: false,
          onshow: function () {
            applyPreviewLabels(this);
            const self = this;
            // idas_notifications.js also enhances Alertify confirms. Reapply
            // preview-specific labels after that shared enhancer has run.
            window.setTimeout(function () { applyPreviewLabels(self); }, 90);
          }
        });
      }
      applyPreviewLabels(dialog);
      return true;
    }

    function markPreviewBypass(target) {
      previewBypassTarget = target;
      previewBypassUntil = Date.now() + 1600;
      window.setTimeout(function () {
        if (Date.now() >= previewBypassUntil) previewBypassTarget = null;
      }, 1700);
    }

    function consumePreviewBypass(target) {
      if (!previewBypassTarget || Date.now() > previewBypassUntil) {
        previewBypassTarget = null;
        return false;
      }
      if (target !== previewBypassTarget) return false;
      previewBypassTarget = null;
      previewBypassUntil = 0;
      return true;
    }

    function isSubmitControl(target) {
      if (!target || !target.tagName) return false;
      const tag = target.tagName.toUpperCase();
      const type = String(target.getAttribute('type') || (tag === 'BUTTON' ? 'submit' : '')).toLowerCase();
      return (tag === 'BUTTON' && type === 'submit') || (tag === 'INPUT' && (type === 'submit' || type === 'image'));
    }

    function replaySaveClick(target) {
      markPreviewBypass(target);
      const form = target.form || (target.closest ? target.closest('form') : null);
      if (form && isSubmitControl(target)) {
        previewSubmitBypass.add(form);
        window.setTimeout(function () { previewSubmitBypass.delete(form); }, 1800);
      }
      window.setTimeout(function () { target.click(); }, 0);
    }

    function dirtyScopeForElement(el) {
      if (!el || !el.closest) return null;
      const modal = el.closest(MODAL_SELECTOR);
      if (modal && isDirty(modal)) return modal;
      const form = el.closest('form');
      if (form && isDirty(form)) return form;
      return isDirty() ? document : null;
    }

    function markSaved(scope) {
      const root = scope && scope.querySelectorAll ? scope : document;
      snapshotScope(root, true);
      if (root === document) dirtyControls.clear();
      pendingSaveScope = null;
      pendingSaveUntil = 0;
      if (pendingSaveTimer) {
        window.clearTimeout(pendingSaveTimer);
        pendingSaveTimer = null;
      }
      allowLeave = false;
    }

    function discardScope(scope) {
      markSaved(scope || document);
    }

    function resetAllowLeaveSoon() {
      window.setTimeout(function () {
        allowLeave = false;
      }, 250);
    }

    function isVisible(el) {
      if (!el || !el.isConnected) return false;
      const style = window.getComputedStyle(el);
      if (style.display === 'none' || style.visibility === 'hidden') return false;
      // Also reject controls hidden by an ancestor (tabs / closed modals).
      if (typeof el.getClientRects === 'function' && el.getClientRects().length === 0) return false;
      return true;
    }

    function visibleModalFor(el) {
      const modal = el && el.closest ? el.closest(MODAL_SELECTOR) : null;
      return modal && isVisible(modal) ? modal : null;
    }

    function showConfirm(onLeave) {
      if (dialogOpen) return;
      const t = text();

      if (!window.alertify || typeof window.alertify.confirm !== 'function') {
        if (window.confirm(t.message)) onLeave();
        return;
      }

      dialogOpen = true;
      const dialog = window.alertify.confirm(
        t.title,
        t.message,
        function () {
          dialogOpen = false;
          onLeave();
        },
        function () {
          dialogOpen = false;
          allowLeave = false;
        }
      );

      if (dialog && typeof dialog.set === 'function') {
        dialog.set({
          labels: { ok: t.ok, cancel: t.cancel },
          closable: false,
          movable: false,
          pinnable: false,
          resizable: false,
          onshow: function () {
            try {
              this.elements.dialog.classList.add('idas-confirm-dialog', 'idas-unsaved-dialog');
            } catch (e) {}
          }
        });
      }
    }

    function showReloadConfirm(onReload) {
      if (dialogOpen) return;
      const t = text();

      if (!window.alertify || typeof window.alertify.confirm !== 'function') {
        if (window.confirm(t.reloadMessage)) onReload();
        return;
      }

      dialogOpen = true;
      const dialog = window.alertify.confirm(
        t.reloadTitle,
        t.reloadMessage,
        function () {
          dialogOpen = false;
          onReload();
        },
        function () {
          dialogOpen = false;
          allowLeave = false;
        }
      );

      if (dialog && typeof dialog.set === 'function') {
        dialog.set({
          labels: { ok: t.reloadOk, cancel: t.reloadCancel },
          closable: false,
          movable: false,
          pinnable: false,
          resizable: false,
          onshow: function () {
            try {
              this.elements.dialog.classList.add('idas-confirm-dialog', 'idas-unsaved-dialog', 'idas-reload-dialog');
            } catch (e) {}
          }
        });
      }
    }

    function isReloadShortcut(event) {
      if (!event) return false;
      const key = String(event.key || '').toLowerCase();
      const code = String(event.code || '');
      const isF5 = key === 'f5' || event.keyCode === 116;
      const isKeyboardReload = (event.ctrlKey || event.metaKey) && (key === 'r' || code === 'KeyR');
      return isF5 || isKeyboardReload;
    }

    function reloadAfterConfirm() {
      allowLeave = true;
      try {
        window.location.reload();
      } finally {
        // If a browser extension or another handler unexpectedly blocks reload,
        // restore protection instead of leaving the page permanently unlocked.
        window.setTimeout(function () { allowLeave = false; }, 1200);
      }
    }

    function firstVisibleInvalid(scope) {
      const root = scope && scope.querySelectorAll ? scope : document;
      const candidates = [];
      if (root.matches && root.matches(INVALID_SELECTOR)) candidates.push(root);
      try {
        root.querySelectorAll(INVALID_SELECTOR).forEach(function (el) { candidates.push(el); });
      } catch (e) {}

      for (const el of candidates) {
        if (!el || !el.matches || el.disabled || isIgnoredElement(el)) continue;
        if (!isVisible(el)) continue;
        const style = window.getComputedStyle(el);
        if (style.pointerEvents === 'none' && !el.matches('input,select,textarea')) continue;
        return el;
      }

      // Legacy pages sometimes only show .invalid-feedback while leaving the
      // control itself without is-invalid. Use the nearest preceding control.
      const feedbacks = root.querySelectorAll ? root.querySelectorAll('.invalid-feedback') : [];
      for (const fb of feedbacks) {
        if (!isVisible(fb) || !(fb.textContent || '').trim()) continue;
        let el = fb.previousElementSibling;
        while (el && !el.matches(CONTROL_SELECTOR)) el = el.previousElementSibling;
        if (el && isVisible(el) && !el.disabled) return el;
      }
      return null;
    }

    function pulseInvalidField(el) {
      if (!el || !el.classList) return;
      el.classList.remove('idas-validation-pulse');
      // Restart the CSS animation even if the same field fails twice in a row.
      void el.offsetWidth;
      el.classList.add('idas-validation-pulse');
      window.setTimeout(function () {
        if (el && el.classList) el.classList.remove('idas-validation-pulse');
      }, 1500);
    }

    function focusInvalidField(el) {
      if (!el || !document.documentElement.contains(el)) return false;
      lastInvalidFocus = el;
      try {
        el.scrollIntoView({ behavior: 'smooth', block: 'center', inline: 'nearest' });
      } catch (e) {
        try { el.scrollIntoView(); } catch (ignore) {}
      }
      pulseInvalidField(el);
      window.setTimeout(function () {
        try { el.focus({ preventScroll: true }); } catch (e) {
          try { el.focus(); } catch (ignore) {}
        }
      }, 80);
      return true;
    }

    function focusFirstInvalid(scope) {
      const invalid = firstVisibleInvalid(scope || document);
      if (!invalid) return false;
      return focusInvalidField(invalid);
    }

    function scheduleInvalidFocus(scope) {
      validationWatchUntil = Date.now() + 1400;
      if (validationFocusTimer) window.clearTimeout(validationFocusTimer);
      const root = scope && scope.querySelectorAll ? scope : document;
      let attempts = 0;
      const check = function () {
        attempts += 1;
        if (focusFirstInvalid(root)) {
          validationFocusTimer = null;
          return;
        }
        if (attempts < 6 && Date.now() <= validationWatchUntil) {
          validationFocusTimer = window.setTimeout(check, attempts < 3 ? 80 : 180);
        } else {
          validationFocusTimer = null;
        }
      };
      validationFocusTimer = window.setTimeout(check, 0);
    }

    function buttonText(el) {
      if (!el) return '';
      return String(el.value || el.textContent || '').replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function isCommitAction(el) {
      if (!el || !el.closest) return false;
      const target = el.closest('button, input[type="button"], input[type="submit"], a, [onclick]');
      if (!target) return false;

      if (target.matches('input[type="submit"], button[type="submit"]')) return true;

      const onclick = String(target.getAttribute('onclick') || '').toLowerCase();
      const idClass = [target.id || '', target.getAttribute('name') || '', target.className || ''].join(' ').toLowerCase();
      const label = buttonText(target);

      if (/\b(save|update|apply|activate|commit|submit)\b/.test(label)) return true;
      if (/(儲存|储存|保存|套用)/.test(label)) return true;

      return /(savejob|updatejob|save_sequence|edit_sequence|save_or_edit_step|copy_job_by_id|copy_seq_by_id|copy_step_by_id_ajax|create_input_id|edit_input_id|copy_input_id|create_output_id|edit_output_id|copy_output_id|controller_save|save_pwd|update_barcode|agent_ip_save|agent_type_save|change_datetime|set_max_link|set_guest_password|set_agent_ip|set_agent_type|change_job|activate)/i.test(onclick + ' ' + idClass);
    }

    function beginPendingSave(el) {
      if (!isDirty()) return;
      pendingSaveScope = dirtyScopeForElement(el) || document;
      pendingSaveUntil = Date.now() + 8000;

      if (pendingSaveTimer) window.clearTimeout(pendingSaveTimer);
      pendingSaveTimer = window.setTimeout(function () {
        pendingSaveUntil = 0;
        pendingSaveScope = null;
        pendingSaveTimer = null;
      }, 8200);
    }

    function completePendingSave() {
      if (!pendingSaveScope || Date.now() > pendingSaveUntil) return;
      markSaved(pendingSaveScope);
    }

    function responseLooksSuccessful(payload) {
      if (payload == null || payload === '') return true;

      let data = payload;
      if (typeof data === 'string') {
        const trimmed = data.trim();
        if (!trimmed) return true;
        try { data = JSON.parse(trimmed); } catch (e) {
          return !/(\berror\b|\bfail(?:ed|ure)?\b|失敗|失败)/i.test(trimmed);
        }
      }

      if (!data || typeof data !== 'object') return true;
      if (data.success === false || data.ok === false) return false;

      const type = String(data.res_type || data.status || data.result || '').toLowerCase();
      if (/^(error|fail|failed|failure|ng|warning|invalid)/.test(type)) return false;
      if (/^(success|ok|saved|updated|created)/.test(type)) return true;

      const message = String(data.res_msg || data.message || '').toLowerCase();
      if (/(\berror\b|\bfail(?:ed|ure)?\b|失敗|失败)/i.test(message)) return false;

      return true;
    }

    function installAjaxSaveDetection() {
      if (window.jQuery && typeof window.jQuery === 'function') {
        window.jQuery(document).on('ajaxSuccess.idasUnsavedGuard', function (event, xhr) {
          let payload = null;
          try {
            payload = xhr && xhr.responseJSON != null ? xhr.responseJSON : (xhr ? xhr.responseText : null);
          } catch (e) {}
          if (responseLooksSuccessful(payload)) completePendingSave();
        });
      }

      if (typeof window.fetch === 'function' && !window.fetch.__idasUnsavedWrapped) {
        const originalFetch = window.fetch;
        const wrappedFetch = function () {
          const shouldWatch = !!pendingSaveScope && Date.now() <= pendingSaveUntil;
          return originalFetch.apply(this, arguments).then(function (response) {
            if (shouldWatch && response && response.ok) {
              try {
                response.clone().text().then(function (body) {
                  if (responseLooksSuccessful(body)) completePendingSave();
                }).catch(function () {
                  completePendingSave();
                });
              } catch (e) {
                completePendingSave();
              }
            }
            return response;
          });
        };
        wrappedFetch.__idasUnsavedWrapped = true;
        wrappedFetch.__idasUnsavedOriginal = originalFetch;
        window.fetch = wrappedFetch;
      }
    }

    function getNavigationAction(target) {
      if (!target) return null;

      const anchor = target.closest && target.closest('a[href]');
      if (anchor) {
        const rawHref = String(anchor.getAttribute('href') || '').trim();
        if (rawHref && rawHref !== '#' && !/^javascript:/i.test(rawHref) && !anchor.hasAttribute('download')) {
          return function () {
            if (anchor.target && anchor.target !== '_self') {
              window.open(anchor.href, anchor.target);
              allowLeave = false;
            } else {
              window.location.href = anchor.href;
            }
          };
        }
      }

      const actionable = target.closest && target.closest('button, input[type="button"], input[type="submit"], img[onclick], span[onclick], [onclick]');
      if (!actionable) return null;

      const onclick = String(actionable.getAttribute('onclick') || '');
      let match = onclick.match(/(?:window\.)?location(?:\.href)?\s*=\s*['"]([^'"]+)['"]/i);
      if (match) {
        const destination = match[1];
        return function () { window.location.href = destination; };
      }

      match = onclick.match(/(?:window\.)?location\.replace\(\s*['"]([^'"]+)['"]\s*\)/i);
      if (match) {
        const destination = match[1];
        return function () { window.location.replace(destination); };
      }

      if (/history\.back\s*\(|history\.go\s*\(\s*-1\s*\)/i.test(onclick)) {
        return function () { window.history.back(); };
      }

      // Legacy iDAS pages commonly use onclick="back()" for the Home icon.
      // Replay the original click only after confirmation so the page's own
      // back()/logout() logic is preserved instead of falling through to the
      // browser-native beforeunload dialog.
      if (/^\s*(?:back|logout)\s*\(\s*\)\s*;?\s*$/i.test(onclick)) {
        return function () { actionable.click(); };
      }

      // Home/back controls are also treated as navigation when their handler is
      // assigned dynamically rather than written directly in the HTML.
      const homeImage = actionable.matches && actionable.matches('img[src*="btn_home"]')
        ? actionable
        : (actionable.querySelector ? actionable.querySelector('img[src*="btn_home"]') : null);
      if (homeImage || /^(?:back_btn|back_home|home)$/i.test(String(actionable.id || ''))) {
        return function () { actionable.click(); };
      }

      return null;
    }

    function isModalCloseAction(target) {
      if (!target || !target.closest) return false;
      const actionable = target.closest('button, input[type="button"], a, [onclick]');
      if (!actionable) return false;

      const modal = visibleModalFor(actionable);
      if (!modal || !isDirty(modal)) return false;

      const onclick = String(actionable.getAttribute('onclick') || '').toLowerCase();
      const idClass = [actionable.id || '', actionable.getAttribute('name') || '', actionable.className || ''].join(' ').toLowerCase();
      const label = buttonText(actionable);

      if (actionable.hasAttribute('data-dismiss') || actionable.hasAttribute('data-bs-dismiss')) return true;
      if (/(closebutton|close.*modal|cancel.*modal|hide.*modal)/i.test(onclick)) return true;
      if (/\b(closebtn|modal-close|btn-close)\b/i.test(idClass)) return true;
      if (/^(close|cancel|關閉|关闭|取消)$/.test(label)) return true;

      // Some legacy pages directly hide the modal in onclick.
      if (/style\.display\s*=\s*['"]none['"]/i.test(onclick)) return true;

      return false;
    }

    function replayClickAfterConfirm(target, scope) {
      allowLeave = true;
      discardScope(scope);
      window.setTimeout(function () {
        try { target.click(); } finally { resetAllowLeaveSoon(); }
      }, 0);
    }

    // Some legacy iDAS modals are rendered first and filled with the current
    // DB/controller values afterwards (for example INPUT/OUTPUT pin radios).
    // In that case the initial page snapshot may contain an empty/default value.
    // Right before the user's first real edit, refresh a still-clean control/group
    // from what is currently visible on screen. This keeps the preview's "Before"
    // value aligned with the value the operator actually saw before editing.
    function syncLateLoadedBaselineBeforeEdit(el) {
      if (!ready || !isTrackableControl(el)) return;

      const type = el.tagName === 'INPUT' ? String(el.type || '').toLowerCase() : '';
      if (type === 'radio') {
        const root = radioGroupRoot(el, document);
        const group = radioGroupControls(el, root);
        if (!group.length || group.some(function (radio) { return dirtyControls.has(radio); })) return;

        const stale = group.some(function (radio) {
          return !baselines.has(radio) || baselines.get(radio) !== controlValue(radio);
        });
        if (!stale) return;

        group.forEach(function (radio) { snapshotControl(radio, true); });
        return;
      }

      if (dirtyControls.has(el)) return;
      if (!baselines.has(el) || baselines.get(el) !== controlValue(el)) {
        snapshotControl(el, true);
      }
    }

    // pointerdown runs before checkbox/radio click default behavior, so it can
    // still capture HIGH before the user switches it to LOW. focusin also covers
    // keyboard navigation and non-pointer editing.
    document.addEventListener('pointerdown', function (event) {
      const target = event.target && event.target.closest
        ? event.target.closest(CONTROL_SELECTOR)
        : null;
      if (target) syncLateLoadedBaselineBeforeEdit(target);
    }, true);

    document.addEventListener('focusin', function (event) {
      syncLateLoadedBaselineBeforeEdit(event.target);
    }, true);

    document.addEventListener('input', function (event) {
      if (!ready) return;
      updateDirtyState(event.target);
    }, true);

    document.addEventListener('change', function (event) {
      if (!ready) return;
      updateDirtyState(event.target);
    }, true);

    document.addEventListener('click', function (event) {
      const target = event.target && event.target.closest
        ? event.target.closest('button, input, select, textarea, a, [onclick]')
        : null;
      if (!target) return;

      // Never treat Alertify's own Save/Cancel buttons as page form actions.
      if (target.closest && target.closest('.alertify')) return;

      if (isCommitAction(target)) {
        const validationScope = visibleModalFor(target) || target.closest('form') || document;
        const dirtyScope = dirtyScopeForElement(target) || validationScope || document;
        scheduleInvalidFocus(validationScope);

        // Real form submit buttons are handled by the submit event so native
        // HTML validation can run before the change preview opens.
        if (target.form && isSubmitControl(target)) return;

        const bypassPreview = consumePreviewBypass(target);
        if (!bypassPreview && isDirty(dirtyScope)) {
          const changes = collectDirtyChanges(dirtyScope);
          if (changes.length) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showSavePreview(changes, function () { replaySaveClick(target); });
            return;
          }
        }

        beginPendingSave(target);
        return;
      }

      if (allowLeave || dialogOpen || savePreviewOpen || !isDirty()) return;

      if (isModalCloseAction(target)) {
        const modal = visibleModalFor(target);
        event.preventDefault();
        event.stopImmediatePropagation();
        showConfirm(function () {
          replayClickAfterConfirm(target, modal || document);
        });
        return;
      }

      const navigate = getNavigationAction(target);
      if (!navigate) return;

      event.preventDefault();
      event.stopImmediatePropagation();
      showConfirm(function () {
        allowLeave = true;
        navigate();
      });
    }, true);

    // A real form submit is a save attempt. Temporarily permit the resulting
    // navigation; AJAX forms stay on the same page and are reset by ajaxSuccess/fetch.
    document.addEventListener('submit', function (event) {
      const form = event.target;
      if (!form || !form.matches || !form.matches('form')) return;
      scheduleInvalidFocus(form);
      if (!isDirty(form)) return;

      if (previewSubmitBypass.has(form)) {
        previewSubmitBypass.delete(form);
      } else {
        const changes = collectDirtyChanges(form);
        if (changes.length) {
          event.preventDefault();
          event.stopImmediatePropagation();
          const submitter = event.submitter || null;
          showSavePreview(changes, function () {
            previewSubmitBypass.add(form);
            window.setTimeout(function () { previewSubmitBypass.delete(form); }, 1800);

            if (typeof form.requestSubmit === 'function') {
              try {
                if (submitter && submitter.form === form) form.requestSubmit(submitter);
                else form.requestSubmit();
                return;
              } catch (e) {}
            }

            // Legacy fallback: form.submit() skips the submit event, so permit
            // the navigation explicitly while keeping the normal save pending.
            beginPendingSave(form);
            allowLeave = true;
            try { form.submit(); } finally { resetAllowLeaveSoon(); }
          });
          return;
        }
      }

      beginPendingSave(form);
      allowLeave = true;
      window.setTimeout(function () {
        allowLeave = false;
      }, 500);
    }, true);

    // Native browser validation uses the same scroll/focus/pulse behavior.
    document.addEventListener('invalid', function (event) {
      const el = event.target;
      if (!el || !el.matches || !el.matches(CONTROL_SELECTOR)) return;
      window.setTimeout(function () { focusInvalidField(el); }, 0);
    }, true);

    // Keyboard reloads are page-level events, so unlike the browser toolbar's
    // Reload button they can use the same iDAS/Alertify confirmation style.
    // Capture phase keeps legacy page key handlers from triggering reload first.
    document.addEventListener('keydown', function (event) {
      if (!isReloadShortcut(event)) return;
      if (allowLeave || !ready || !isDirty()) return;

      event.preventDefault();
      event.stopPropagation();
      if (typeof event.stopImmediatePropagation === 'function') event.stopImmediatePropagation();

      // A currently open iDAS dialog already owns the operator's decision.
      // Prevent the browser reload shortcut, but do not stack another dialog.
      if (dialogOpen || savePreviewOpen) return;

      showReloadConfirm(reloadAfterConfirm);
    }, true);

    // Refresh / close tab / direct script navigation must use the browser's
    // native beforeunload UI. Browsers do not allow custom Alertify text here.
    window.addEventListener('beforeunload', function (event) {
      if (allowLeave || !isDirty()) return;
      event.preventDefault();
      event.returnValue = '';
    });

    // Snapshot controls inserted dynamically (Customize rows, modal forms, etc.).
    if (window.MutationObserver) {
      const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
          if (mutation.type === 'childList') {
            mutation.addedNodes.forEach(function (node) {
              if (!node || node.nodeType !== 1) return;
              snapshotScope(node, false);
            });
            return;
          }

          if (mutation.type === 'attributes') {
            const node = mutation.target;
            if (!node || !node.matches) return;

            if (Date.now() <= validationWatchUntil && node.matches(INVALID_SELECTOR) && isVisible(node)) {
              window.setTimeout(function () {
                if (Date.now() <= validationWatchUntil) focusFirstInvalid(visibleModalFor(node) || node.closest('form') || document);
              }, 0);
            }

            if (!node.matches(MODAL_SELECTOR) || !isVisible(node)) return;

            // Opening an edit/copy/new modal establishes its current values as
            // the clean baseline after the page's own population code completes.
            window.setTimeout(function () {
              if (isVisible(node)) snapshotScope(node, true);
            }, 0);
          }
        });
      });

      observer.observe(document.documentElement, {
        subtree: true,
        childList: true,
        attributes: true,
        attributeFilter: ['style', 'class', 'hidden', 'aria-invalid']
      });
    }

    installAjaxSaveDetection();

    // Let legacy page initialization populate values first, then capture clean state.
    window.setTimeout(function () {
      snapshotScope(document, true);
      ready = true;
      allowLeave = false;
    }, 180);

    window.IdasUnsavedGuard = {
      isDirty: isDirty,
      markSaved: markSaved,
      reset: function (scope) {
        markSaved(scope || document);
      },
      allowNextNavigation: function () {
        allowLeave = true;
      },
      resetAllowLeave: function () {
        allowLeave = false;
      },
      focusFirstInvalid: function (scope) {
        return focusFirstInvalid(scope || document);
      },
      getChanges: function (scope) {
        return collectDirtyChanges(scope || document);
      }
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})(window, document);
