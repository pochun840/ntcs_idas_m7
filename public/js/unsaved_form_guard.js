(function (window, document) {
  'use strict';

  if (window.__IDAS_UNSAVED_GUARD_INSTALLED__) return;
  window.__IDAS_UNSAVED_GUARD_INSTALLED__ = true;

  function init() {
    const controller = getControllerName();

    // Login page is explicitly excluded from unsaved-form protection.
    if (controller === 'login' || controller === 'logins') return;

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

    const COPY = {
      'zh-tw': {
        title: '尚未儲存',
        message: '表單內容尚未儲存，確定要離開嗎？',
        ok: '離開',
        cancel: '繼續編輯'
      },
      'zh-cn': {
        title: '尚未保存',
        message: '表单内容尚未保存，确定要离开吗？',
        ok: '离开',
        cancel: '继续编辑'
      },
      'en-us': {
        title: 'Unsaved changes',
        message: 'Your changes have not been saved. Are you sure you want to leave?',
        ok: 'Leave',
        cancel: 'Keep editing'
      }
    };

    const baselines = new WeakMap();
    const dirtyControls = new Set();

    let ready = false;
    let allowLeave = false;
    let dialogOpen = false;
    let pendingSaveUntil = 0;
    let pendingSaveScope = null;
    let pendingSaveTimer = null;

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

    function snapshotControl(el, force) {
      if (!isTrackableControl(el)) return;
      if (force || !baselines.has(el)) baselines.set(el, controlValue(el));
      if (force) dirtyControls.delete(el);
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

      if (controlValue(el) === baselines.get(el)) dirtyControls.delete(el);
      else dirtyControls.add(el);
    }

    function pruneDirtyControls() {
      dirtyControls.forEach(function (el) {
        if (!document.documentElement.contains(el) || !isTrackableControl(el)) {
          dirtyControls.delete(el);
          return;
        }

        // If the value was changed back to the original value, it is no longer dirty.
        if (baselines.has(el) && controlValue(el) === baselines.get(el)) {
          dirtyControls.delete(el);
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
      return style.display !== 'none' && style.visibility !== 'hidden';
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

      if (isCommitAction(target)) {
        beginPendingSave(target);
        return;
      }

      if (allowLeave || dialogOpen || !isDirty()) return;

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
      if (!isDirty(form)) return;

      beginPendingSave(form);
      allowLeave = true;
      window.setTimeout(function () {
        allowLeave = false;
      }, 500);
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
            if (!node || !node.matches || !node.matches(MODAL_SELECTOR)) return;
            if (!isVisible(node)) return;

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
        attributeFilter: ['style', 'class', 'hidden']
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
      }
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})(window, document);
