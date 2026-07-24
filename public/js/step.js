document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        if (event.target && event.target.id === 'copyButton') {
            copy_step_by_id();
        }
    });
});

function copy_step(stepid){
    document.getElementById('copystep').style.display = 'block';   
    copy_step_by_id();

}


function disableElements(elements, value) {
    elements.forEach(function(element) {
        element.disabled = value;
        element.value = value === true ? 0 : ''; 
    });
}
/* Operator law=3 Step Edit Save Lock
 * law=3 operator：Step 清單頁 Edit 可以進入編輯頁，但 Step 編輯頁 Save 不可執行。
 * 此檔只做 Step 頁面補強；主要全域規則仍由 public/js/all.js 處理。
 */
(function () {
    function getCookieSafe(name) {
        var parts = document.cookie ? document.cookie.split(';') : [];
        var prefix = name + '=';
        for (var i = 0; i < parts.length; i++) {
            var item = parts[i].trim();
            if (item.indexOf(prefix) === 0) {
                try {
                    return decodeURIComponent(item.substring(prefix.length));
                } catch (e) {
                    return item.substring(prefix.length);
                }
            }
        }
        return '';
    }

    function isOperatorLaw3() {
        return String(getCookieSafe('user_law') || '').trim() === '3';
    }

    function getOnclickText(el) {
        return String((el && el.getAttribute) ? (el.getAttribute('onclick') || '') : '');
    }

    function isStepEditSaveButton(el) {
        if (!el || el.nodeType !== 1) return false;
        var onclickText = getOnclickText(el);
        return /\bsave_or_edit_step\s*\(/i.test(onclickText)
            || String(el.getAttribute('data-operator-step-save-lock') || '') === '1';
    }

    function insertStyle() {
        if (document.getElementById('idasOperatorStepSaveStyle')) return;

        var style = document.createElement('style');
        style.id = 'idasOperatorStepSaveStyle';
        style.textContent = [
            '.idas-operator-step-save-disabled {',
            '  opacity: 0.45 !important;',
            '  cursor: not-allowed !important;',
            '  filter: grayscale(1) !important;',
            '  pointer-events: none !important;',
            '}'
        ].join('\n');
        document.head.appendChild(style);
    }

    function disableStepEditSave(el) {
        if (!el || el.dataset.operatorStepSaveLocked === '1') return;

        el.dataset.operatorOriginalOnclick = el.getAttribute('onclick') || '';
        el.removeAttribute('onclick');
        el.onclick = null;
        el.disabled = true;
        el.setAttribute('aria-disabled', 'true');
        el.classList.add('idas-operator-step-save-disabled');
        el.dataset.operatorStepSaveLocked = '1';
    }

    function applyStepEditSaveLock() {
        if (!isOperatorLaw3()) return;

        insertStyle();
        document.querySelectorAll('button[onclick*="save_or_edit_step("], input[onclick*="save_or_edit_step("], [data-operator-step-save-lock="1"]').forEach(function (el) {
            if (isStepEditSaveButton(el)) {
                disableStepEditSave(el);
            }
        });
    }

    function patchSaveOrEditStepFunction() {
        if (!isOperatorLaw3()) return false;

        if (typeof window.save_or_edit_step === 'function' && window.save_or_edit_step.__operatorStepSaveLocked !== true) {
            var blocked = function () {
                return false;
            };
            blocked.__operatorStepSaveLocked = true;
            window.save_or_edit_step = blocked;
            return true;
        }

        return false;
    }

    document.addEventListener('DOMContentLoaded', function () {
        applyStepEditSaveLock();

        var attempts = 0;
        var timer = setInterval(function () {
            attempts += 1;
            applyStepEditSaveLock();

            if (patchSaveOrEditStepFunction() || attempts >= 20) {
                clearInterval(timer);
            }
        }, 250);
    });

    window.addEventListener('load', function () {
        applyStepEditSaveLock();
        patchSaveOrEditStepFunction();
    });

    document.addEventListener('click', function (event) {
        if (!isOperatorLaw3()) return;

        var target = event.target && event.target.closest ? event.target.closest('button, input, a') : event.target;
        if (isStepEditSaveButton(target)) {
            disableStepEditSave(target);
            event.preventDefault();
            event.stopPropagation();
            return false;
        }
    }, true);

    window.idasApplyOperatorStepSaveLock = applyStepEditSaveLock;
})();
