/**
 * sync_overlay_i18n.js
 * =====================================================
 * FINAL VERSION
 *
 * Features:
 * - Web-trigger auto sync (Tool Spec)
 * - Frontend polling (no page reload needed)
 * - Fullscreen overlay (disable operations)
 * - Auto refresh UI after synced
 * - Timeout protection
 * - I18N (zh-tw / zh-cn / en-us)
 * - CSS auto-injected
 *
 * Requirement:
 * - Backend API: ?url=Tool/get_sync_state
 *   return: { state: applying|synced|timeout, elapsed? }
 */

/* =====================================================
 * 0) CONFIG
 * ===================================================== */
const TOOL_SYNC_API = '?url=Tool/get_sync_state'; // 若 controller 是 Tools 請改
const POLL_APPLYING_MS = 1000;
const POLL_IDLE_MS = 3000;

/* =====================================================
 * 1) Inject CSS (once)
 * ===================================================== */
(function injectCSS() {
    if (document.getElementById('sync-overlay-style')) return;

    const style = document.createElement('style');
    style.id = 'sync-overlay-style';
    style.textContent = `
#sync-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: none;
}
.sync-overlay-backdrop {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.45);
}
.sync-overlay-box {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #1e1e1e;
    color: #fff;
    padding: 28px 36px;
    border-radius: 12px;
    min-width: 320px;
    text-align: center;
    box-shadow: 0 20px 40px rgba(0,0,0,.6);
}
.sync-text {
    margin-top: 12px;
    font-size: 16px;
}
.sync-sub {
    margin-top: 6px;
    font-size: 13px;
    opacity: .7;
}
.sync-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid rgba(255,255,255,.2);
    border-top-color: #4cafef;
    border-radius: 50%;
    margin: 0 auto;
    animation: syncSpin 1s linear infinite;
}
@keyframes syncSpin {
    to { transform: rotate(360deg); }
}
`;
    document.head.appendChild(style);
})();

/* =====================================================
 * 2) I18N
 * ===================================================== */
const I18N = {
    'en-us': {
        applying: 'Applying tool specification…',
        synced:   'Tool specification applied',
        timeout:  'Sync timeout',
        syncing:  'System synchronization in progress…'
    },
    'zh-tw': {
        applying: '工具規格套用中…',
        synced:   '工具規格已套用',
        timeout:  '同步逾時',
        syncing:  '系統同步中…'
    },
    'zh-cn': {
        applying: '工具规格套用中…',
        synced:   '工具规格已套用',
        timeout:  '同步超时',
        syncing:  '系统同步中…'
    }
};

function t(key) {
    const lang = (window.APP_LANG || 'en-us').toLowerCase();
    return (I18N[lang] && I18N[lang][key]) || I18N['en-us'][key] || key;
}

/* =====================================================
 * 3) Overlay Manager
 * ===================================================== */
const SyncOverlay = (function () {

    let overlay = null;
    let showing = false;

    function create() {
        overlay = document.createElement('div');
        overlay.id = 'sync-overlay';
        overlay.innerHTML = `
            <div class="sync-overlay-backdrop"></div>
            <div class="sync-overlay-box">
                <div class="sync-spinner"></div>
                <div class="sync-text">${t('syncing')}</div>
                <div class="sync-sub"></div>
            </div>
        `;
        document.body.appendChild(overlay);
    }

    function show(subText) {
        if (!overlay) create();
        showing = true;
        overlay.style.display = 'block';
        document.body.style.pointerEvents = 'none';
        overlay.style.pointerEvents = 'auto';
        overlay.querySelector('.sync-sub').textContent = subText || '';
    }

    function hide() {
        if (!overlay) return;
        showing = false;
        overlay.style.display = 'none';
        document.body.style.pointerEvents = '';
    }

    return { show, hide };
})();

/* =====================================================
 * 4) Auto Polling + Web-trigger Sync
 * ===================================================== */
(function autoToolSpecSync() {

    let lastState = null;
    let timer = null;

    function schedule(ms) {
        clearTimeout(timer);
        timer = setTimeout(poll, ms);
    }

    async function poll() {
        try {
            const res = await fetch(TOOL_SYNC_API, { cache: 'no-store' });
            const data = await res.json();

            const state = data?.state || 'synced';

            // ---------------- applying ----------------
            if (state === 'applying') {
                SyncOverlay.show(t('applying'));
                lastState = 'applying';
                schedule(POLL_APPLYING_MS);
                return;
            }

            // ---------------- synced ----------------
            if (state === 'synced') {
                SyncOverlay.hide();

                if (lastState === 'applying') {
                    // ⭐ 同步完成（只觸發一次）
                    onSyncedOnce();
                }

                lastState = 'synced';
                schedule(POLL_IDLE_MS);
                return;
            }

            // ---------------- timeout ----------------
            if (state === 'timeout') {
                SyncOverlay.hide();
                console.warn('[ToolSpecSync] timeout', data?.elapsed);
                lastState = 'timeout';
                schedule(POLL_IDLE_MS);
                return;
            }

            schedule(POLL_IDLE_MS);

        } catch (e) {
            console.error('[ToolSpecSync] poll error', e);
            schedule(POLL_IDLE_MS);
        }
    }

    poll();

    document.addEventListener('visibilitychange', () => {
        clearTimeout(timer);
        schedule(document.hidden ? 10000 : 0);
    });

    /* =================================================
     * 5) Auto Refresh UI after synced
     * ================================================= */
    function onSyncedOnce() {
        console.log('[ToolSpecSync] synced → refresh UI');

        // ① 專案自訂（最推薦）
        if (typeof window.refreshToolSpec === 'function') {
            window.refreshToolSpec();
            return;
        }

        // ② 次佳：重新載入部分資料（你可自行改）
        if (typeof window.reloadToolTable === 'function') {
            window.reloadToolTable();
            return;
        }

        // ③ 最保守（預設關閉）
        // location.reload();
    }

})();
