<!-- operation_audit_log list OP -->
<div id="OperationAuditLogDisplay" class="operation-audit-log-page" style="display:none;">
    <div id="operationAuditLogListPanel">
        <div class="operation-audit-monitor-bar">
            <div class="operation-audit-monitor-left">
                <span id="operationAuditMonitorDot" class="operation-audit-monitor-dot is-running"></span>
                <span class="operation-audit-monitor-title" data-i18n="audit_realtime_monitor">Realtime Monitor</span>
                <span id="operationAuditMonitorStatus" class="operation-audit-monitor-status">Running</span>
                <label class="operation-audit-source-label" for="operationAuditSourceSelect" data-i18n="audit_monitor">Monitor</label>
                <select id="operationAuditSourceSelect" class="operation-audit-source-select">
                    <option value="idas" selected>iDAS</option>
                    <option value="app">Controller</option>
                </select>
            </div>
            <div class="operation-audit-monitor-right">
                <span id="operationAuditMonitorUpdated">Updated: -</span>
                <span id="operationAuditMonitorCount">Records: 0</span>
            </div>
        </div>

        <div class="table-container operation-audit-table-container">
            <div class="scrollbar" id="style-operation-audit-table">
                <div class="scrollbar-force-overflow">
                    <table id="operation_audit_log_table" class="table w3-table operation-audit-table">
                        <thead id="operation-audit-header-table">
                            <tr class="w3-dark-grey">
                                <th data-i18n="audit_no">No</th>
                                <th data-i18n="audit_time">Time</th>
                                <th data-i18n="audit_user">User</th>
                                <th data-i18n="audit_module">Module</th>
                                <th data-i18n="audit_action">Action</th>
                                <th data-i18n="audit_target">Target</th>
                            </tr>
                        </thead>
                        <tbody id="operationAuditLogTbody" style="font-size: 1.55vmin;text-align: center;">
                            <tr><td colspan="6" data-i18n="audit_select_tab">Please select operation_audit_log tab</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- operation_audit_log list ED -->

<script>

/* =====================================================
   Operation Audit Log I18N
   Language source: cookie language / lang
   ===================================================== */
function operationAuditCookieValue(name) {
    var target = name + '=';
    var parts = (document.cookie || '').split(';');

    for (var i = 0; i < parts.length; i++) {
        var item = parts[i].trim();
        if (item.indexOf(target) === 0) {
            try {
                return decodeURIComponent(item.substring(target.length));
            } catch (e) {
                return item.substring(target.length);
            }
        }
    }

    return '';
}

function operationAuditLang() {
    var lang = String(operationAuditCookieValue('languages') || operationAuditCookieValue('language') || operationAuditCookieValue('lang') || 'en-us')
        .trim()
        .toLowerCase()
        .replace('_', '-');

    if (lang === 'zh-tw' || lang === 'zh-hant' || lang === 'tw') return 'zh-tw';
    if (lang === 'zh-cn' || lang === 'zh-hans' || lang === 'cn') return 'zh-cn';
    if (lang === 'en') return 'en-us';

    return lang === 'en-us' ? 'en-us' : 'en-us';
}

function operationAuditText(key, vars) {
    var lang = operationAuditLang();

    var dict = {
        'en-us': {
            audit_button: 'operation_audit_log',
            audit_realtime_monitor: 'Realtime Monitor',
            audit_running: 'Running',
            audit_paused: 'Paused',
            audit_pause: 'Pause',
            audit_start: 'Start',
            audit_standby: 'Standby',
            audit_error: 'Error',
            audit_monitor: 'Monitor',
            audit_updated: 'Updated',
            audit_records: 'Records',
            audit_no: 'No',
            audit_time: 'Time',
            audit_user: 'User',
            audit_module: 'Module',
            audit_action: 'Action',
            audit_target: 'Target',
            audit_status: 'Status',
            audit_message: 'Message',
            audit_select_tab: 'Please select operation_audit_log tab',
            audit_no_data: 'No Data',
            audit_loading: 'Loading...',
            audit_response_not_json: 'operation_audit_log response is not JSON.',
            audit_http_status: 'HTTP Status',
            audit_app: 'APP',
            audit_idas: 'iDAS',

            module_JOB: 'JOB',
            module_SEQ: 'SEQ',
            module_STEP: 'STEP',
            module_APP: 'APP',
            module_IDAS: 'iDAS',
            module_DB_SYNC: 'DB Sync',
            module_AUTH: 'AUTH',

            action_NEW: 'NEW',
            action_EDIT: 'EDIT',
            action_DELETE: 'DELETE',
            action_COPY: 'COPY',
            action_UP: 'UP',
            action_DOWN: 'DOWN',
            action_LOG: 'LOG',
            action_LOGIN: 'LOGIN',
            action_LOGOUT: 'LOGOUT',
            action_SAVE: 'SAVE',
            action_SYNC_D2C: 'SAVE',
            action_SYNC_C2D: 'LOAD',
            target_SYNC_D2C: 'iDAS data sync to controller',
            target_SYNC_C2D: 'Controller data sync to iDAS',

            status_SUCCESS: 'SUCCESS',
            status_FAIL: 'FAIL',
            status_ERROR: 'ERROR',
            status_INFO: 'INFO',
            status_WARNING: 'WARNING',

            msg_create_job: 'Create job id: {id}',
            msg_edit_job: 'Edit job id: {id}',
            msg_delete_job: 'Delete job id: {id}',
            msg_create_sequence: 'Create sequence id: {id}',
            msg_edit_sequence: 'Edit sequence id: {id}',
            msg_delete_sequence: 'Delete sequence id: {id}',
            msg_sequence_order_changed: 'Sequence order changed',
            msg_create_step: 'Create step id: {id}',
            msg_edit_step: 'Edit step id: {id}',
            msg_delete_step: 'Delete step id: {id}',
            msg_step_order_changed: 'Step order changed'
        },
        'zh-tw': {
            audit_button: '操作紀錄',
            audit_realtime_monitor: '即時監控',
            audit_running: '監控中',
            audit_paused: '已暫停',
            audit_pause: '暫停',
            audit_start: '開始',
            audit_standby: '待命',
            audit_error: '錯誤',
            audit_monitor: '監控來源',
            audit_updated: '更新時間',
            audit_records: '筆數',
            audit_no: '編號',
            audit_time: '時間',
            audit_user: '使用者',
            audit_module: '模組',
            audit_action: '動作',
            audit_target: '目標',
            audit_status: '狀態',
            audit_message: '訊息',
            audit_select_tab: '請選擇操作紀錄分頁',
            audit_no_data: '無資料',
            audit_loading: '載入中...',
            audit_response_not_json: 'operation_audit_log 回應不是 JSON。',
            audit_http_status: 'HTTP 狀態',
            audit_app: 'APP',
            audit_idas: 'iDAS',

            module_JOB: '工作',
            module_SEQ: '工序',
            module_STEP: '步驟',
            module_APP: 'APP',
            module_IDAS: 'iDAS',
            module_DB_SYNC: '資料庫同步',
            module_AUTH: '登入登出',

            action_NEW: '新增',
            action_EDIT: '編輯',
            action_DELETE: '刪除',
            action_COPY: '複製',
            action_UP: '上移',
            action_DOWN: '下移',
            action_LOG: '紀錄',
            action_LOGIN: '登入',
            action_LOGOUT: '登出',
            action_SAVE: '儲存',
            action_SYNC_D2C: '儲存',
            action_SYNC_C2D: '上傳',
            target_SYNC_D2C: 'iDAS 資料同步到控制器',
            target_SYNC_C2D: '控制器資料同步到 iDAS',

            status_SUCCESS: '成功',
            status_FAIL: '失敗',
            status_ERROR: '錯誤',
            status_INFO: '資訊',
            status_WARNING: '警告',

            msg_create_job: '新增工作 ID：{id}',
            msg_edit_job: '編輯工作 ID：{id}',
            msg_delete_job: '刪除工作 ID：{id}',
            msg_create_sequence: '新增工序 ID：{id}',
            msg_edit_sequence: '編輯工序 ID：{id}',
            msg_delete_sequence: '刪除工序 ID：{id}',
            msg_sequence_order_changed: '工序排序已變更',
            msg_create_step: '新增步驟 ID：{id}',
            msg_edit_step: '編輯步驟 ID：{id}',
            msg_delete_step: '刪除步驟 ID：{id}',
            msg_step_order_changed: '步驟排序已變更'
        },
        'zh-cn': {
            audit_button: '操作纪录',
            audit_realtime_monitor: '即时监控',
            audit_running: '监控中',
            audit_paused: '已暂停',
            audit_pause: '暂停',
            audit_start: '开始',
            audit_standby: '待命',
            audit_error: '错误',
            audit_monitor: '监控来源',
            audit_updated: '更新时间',
            audit_records: '笔数',
            audit_no: '编号',
            audit_time: '时间',
            audit_user: '使用者',
            audit_module: '模块',
            audit_action: '动作',
            audit_target: '目标',
            audit_status: '状态',
            audit_message: '信息',
            audit_select_tab: '请选择操作纪录分页',
            audit_no_data: '无资料',
            audit_loading: '载入中...',
            audit_response_not_json: 'operation_audit_log 回应不是 JSON。',
            audit_http_status: 'HTTP 状态',
            audit_app: 'APP',
            audit_idas: 'iDAS',

            module_JOB: '工作',
            module_SEQ: '工序',
            module_STEP: '步骤',
            module_APP: 'APP',
            module_IDAS: 'iDAS',
            module_DB_SYNC: '数据库同步',
            module_AUTH: '登录登出',

            action_NEW: '新增',
            action_EDIT: '编辑',
            action_DELETE: '删除',
            action_COPY: '复制',
            action_UP: '上移',
            action_DOWN: '下移',
            action_LOG: '纪录',
            action_LOGIN: '登录',
            action_LOGOUT: '登出',
            action_SAVE: '保存',
            action_SYNC_D2C: '保存',
            action_SYNC_C2D: '载入',
            target_SYNC_D2C: 'iDAS 数据同步到控制器',
            target_SYNC_C2D: '控制器数据同步到 iDAS',

            status_SUCCESS: '成功',
            status_FAIL: '失败',
            status_ERROR: '错误',
            status_INFO: '信息',
            status_WARNING: '警告',

            msg_create_job: '新增工作 ID：{id}',
            msg_edit_job: '编辑工作 ID：{id}',
            msg_delete_job: '删除工作 ID：{id}',
            msg_create_sequence: '新增工序 ID：{id}',
            msg_edit_sequence: '编辑工序 ID：{id}',
            msg_delete_sequence: '删除工序 ID：{id}',
            msg_sequence_order_changed: '工序排序已变更',
            msg_create_step: '新增步骤 ID：{id}',
            msg_edit_step: '编辑步骤 ID：{id}',
            msg_delete_step: '删除步骤 ID：{id}',
            msg_step_order_changed: '步骤排序已变更'
        }
    };

    var text = (dict[lang] && dict[lang][key]) || dict['en-us'][key] || key;
    vars = vars || {};

    Object.keys(vars).forEach(function(name) {
        text = text.replace(new RegExp('\\{' + name + '\\}', 'g'), vars[name]);
    });

    return text;
}

function operationAuditNormalizeModuleKey(value) {
    var raw = String(value === null || value === undefined ? '' : value).trim();
    var normalized = raw.toUpperCase().replace(/[\s\-_]+/g, ' ');

    var map = {
        'JOB': 'JOB',
        'JOBS': 'JOB',
        'JOB EDITOR': 'JOB',
        'JOB MANAGEMENT': 'JOB',
        'JOB MANAGER': 'JOB',

        'SEQ': 'SEQ',
        'SEQUENCE': 'SEQ',
        'SEQUENCE EDITOR': 'SEQ',
        'SEQUENCE MANAGEMENT': 'SEQ',
        'SEQUENCE MANAGER': 'SEQ',

        'STEP': 'STEP',
        'STEP EDITOR': 'STEP',
        'STEP MANAGEMENT': 'STEP',
        'STEP MANAGER': 'STEP',

        'APP': 'APP',
        'IDAS': 'IDAS',

        'DB SYNC': 'DB_SYNC',
        'DATABASE SYNC': 'DB_SYNC',
        'AUTH': 'AUTH',
        'LOGIN': 'AUTH',
        'LOGIN KEYBOARD': 'AUTH',
        'LOGOUT': 'AUTH'
    };

    return map[normalized] || raw.toUpperCase();
}

function operationAuditNormalizeActionKey(value) {
    var raw = String(value === null || value === undefined ? '' : value).trim();

    var normalized = raw
        .toUpperCase()
        .replace(/[\s\-_]+/g, ' ')
        .trim();

    var map = {
        'NEW': 'NEW',
        'ADD': 'NEW',
        'CREATE': 'NEW',
        'CREATED': 'NEW',
        'INSERT': 'NEW',
        'INSERTED': 'NEW',

        'EDIT': 'EDIT',
        'UPDATE': 'EDIT',
        'UPDATED': 'EDIT',
        'MODIFY': 'EDIT',
        'MODIFIED': 'EDIT',

        'DELETE': 'DELETE',
        'DEL': 'DELETE',
        'REMOVE': 'DELETE',
        'REMOVED': 'DELETE',

        'COPY': 'COPY',
        'DUPLICATE': 'COPY',
        'CLONE': 'COPY',

        'UP': 'UP',
        'MOVE UP': 'UP',
        'DOWN': 'DOWN',
        'MOVE DOWN': 'DOWN',

        'LOG': 'LOG',
        'LOGIN': 'LOGIN',
        'SIGN IN': 'LOGIN',
        'LOG IN': 'LOGIN',
        '登入': 'LOGIN',
        '登录': 'LOGIN',
        'LOGOUT': 'LOGOUT',
        'SIGN OUT': 'LOGOUT',
        'LOG OUT': 'LOGOUT',
        '登出': 'LOGOUT',
        'SAVE': 'SAVE',
        'LOAD': 'SYNC_C2D',

        // ★ DB Sync 不要轉成 SAVE / LOAD
        // ★ 要保留成語系 key：SYNC_D2C / SYNC_C2D
        'SYNC D2C': 'SYNC_D2C',
        'SYNC C2D': 'SYNC_C2D',
        'D2C': 'SYNC_D2C',
        'C2D': 'SYNC_C2D'
    };

    return map[normalized] || raw.toUpperCase();
}


function operationAuditTranslateValue(type, value) {
    var raw = String(value === null || value === undefined ? '' : value).trim();
    if (raw === '') return '';

    var normalizedKey = raw.toUpperCase();

    if (type === 'module') {
        normalizedKey = operationAuditNormalizeModuleKey(raw);
    } else if (type === 'action') {
        normalizedKey = operationAuditNormalizeActionKey(raw);
    }

    var key = type + '_' + normalizedKey;
    var translated = operationAuditText(key);

    return translated === key ? raw : translated;
}

function operationAuditTranslateMessage(message) {
    var raw = String(message === null || message === undefined ? '' : message).trim();

    var patterns = [
        { re: /^Create job id:\s*(\d+)/i, key: 'msg_create_job' },
        { re: /^Edit job id:\s*(\d+)/i, key: 'msg_edit_job' },
        { re: /^Delete job id:\s*(\d+)/i, key: 'msg_delete_job' },
        { re: /^Create sequence id:\s*(\d+)/i, key: 'msg_create_sequence' },
        { re: /^Edit sequence id:\s*(\d+)/i, key: 'msg_edit_sequence' },
        { re: /^Delete sequence id:\s*(\d+)/i, key: 'msg_delete_sequence' },
        { re: /^Create step id:\s*(\d+)/i, key: 'msg_create_step' },
        { re: /^Edit step id:\s*(\d+)/i, key: 'msg_edit_step' },
        { re: /^Delete step id:\s*(\d+)/i, key: 'msg_delete_step' }
    ];

    for (var i = 0; i < patterns.length; i++) {
        var m = raw.match(patterns[i].re);
        if (m) {
            return operationAuditText(patterns[i].key, { id: m[1] });
        }
    }

    if (/^Sequence order changed$/i.test(raw)) {
        return operationAuditText('msg_sequence_order_changed');
    }

    if (/^Step order changed$/i.test(raw)) {
        return operationAuditText('msg_step_order_changed');
    }

    return raw;
}

function applyOperationAuditI18n() {
    document.querySelectorAll('[data-i18n]').forEach(function(el) {
        var key = el.getAttribute('data-i18n');
        if (key) el.innerText = operationAuditText(key);
    });

    var btn = document.getElementById('bnt7');
    if (btn) btn.innerText = operationAuditText('audit_button');

    var updatedEl = document.getElementById('operationAuditMonitorUpdated');
    if (updatedEl) {
        var current = updatedEl.innerText || '';
        var time = current.split(': ').length > 1 ? current.substring(current.indexOf(': ') + 2) : '-';
        updatedEl.innerText = operationAuditText('audit_updated') + ': ' + time;
    }

    var countEl = document.getElementById('operationAuditMonitorCount');
    if (countEl) {
        var currentCount = countEl.innerText || '';
        var count = currentCount.match(/\d+/);
        countEl.innerText = operationAuditText('audit_records') + ': ' + (count ? count[0] : '0');
    }

    var statusEl = document.getElementById('operationAuditMonitorStatus');
    if (statusEl) {
        var rawStatus = statusEl.getAttribute('data-status-raw') || statusEl.innerText || 'Running';
        statusEl.innerText = operationAuditText('audit_' + rawStatus.toLowerCase()) || rawStatus;
    }

    var toggleBtn = document.getElementById('operation_audit_monitor_toggle_btn');
    if (toggleBtn) {
        var rawToggle = toggleBtn.getAttribute('data-toggle-raw') || toggleBtn.value || 'Pause';
        toggleBtn.value = operationAuditText('audit_' + rawToggle.toLowerCase()) || rawToggle;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    applyOperationAuditI18n();
});
window.addEventListener('load', function() {
    applyOperationAuditI18n();
});


var selectedOperationAuditLogId = null;
var operationAuditLogLoaded = false;
var operationAuditLogTimer = null;
var operationAuditLogIntervalMs = 2000;
var operationAuditLogLastLogId = 0;
var operationAuditLogRenderSignature = '';
var operationAuditLogIsLoading = false;
var operationAuditLogMonitorPaused = false;

function operationAuditApi(path) {
    return '?url=Settings/' + path;
}

function operationAuditEscape(value) {
    return String(value === null || value === undefined ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function operationAuditNowTime() {
    var now = new Date();
    var hh = String(now.getHours()).padStart(2, '0');
    var mm = String(now.getMinutes()).padStart(2, '0');
    var ss = String(now.getSeconds()).padStart(2, '0');
    return hh + ':' + mm + ':' + ss;
}

function operationAuditIsVisible() {
    var display = document.getElementById('OperationAuditLogDisplay');
    return !!(display && display.style.display !== 'none');
}

function operationAuditCurrentSource() {
    var select = document.getElementById('operationAuditSourceSelect');
    var value = select ? String(select.value || 'idas').toLowerCase() : 'idas';
    return value === 'app' ? 'app' : 'idas';
}

function resetSettingOperationAuditMonitorState() {
    operationAuditLogLoaded = false;
    operationAuditLogLastLogId = 0;
    operationAuditLogRenderSignature = '';
    selectedOperationAuditLogId = null;
}

function setOperationAuditMonitorStatus(status, isRunning) {
    var statusEl = document.getElementById('operationAuditMonitorStatus');
    var dotEl = document.getElementById('operationAuditMonitorDot');

    if (statusEl) {
        statusEl.setAttribute('data-status-raw', status || '');
        statusEl.innerText = operationAuditText('audit_' + String(status || '').toLowerCase()) || status || '';
    }

    if (dotEl) {
        dotEl.classList.remove('is-running', 'is-paused', 'is-error');
        if (isRunning === true) {
            dotEl.classList.add('is-running');
        } else if (status === 'Error') {
            dotEl.classList.add('is-error');
        } else {
            dotEl.classList.add('is-paused');
        }
    }
}

function updateOperationAuditMonitorInfo(count) {
    var updatedEl = document.getElementById('operationAuditMonitorUpdated');
    var countEl = document.getElementById('operationAuditMonitorCount');

    if (updatedEl) updatedEl.innerText = operationAuditText('audit_updated') + ': ' + operationAuditNowTime();
    if (countEl) countEl.innerText = operationAuditText('audit_records') + ': ' + (count || 0);
}

function operationAuditPost(path, data) {
    var formData = new FormData();

    Object.keys(data || {}).forEach(function(key) {
        formData.append(key, data[key]);
    });

    return fetch(operationAuditApi(path), {
        method: 'POST',
        body: formData,
        cache: 'no-store',
        credentials: 'same-origin',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    }).then(function(res) {
        return res.text().then(function(text) {
            return {
                status: res.status,
                ok: res.ok,
                text: text
            };
        });
    }).then(function(result) {
        var text = result.text || '';

        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('operation_audit_log API status:', result.status);
            console.error('operation_audit_log API raw response:', text);
            throw new Error(operationAuditText('audit_response_not_json') + ' ' + operationAuditText('audit_http_status') + ': ' + result.status);
        }
    });
}

function startSettingOperationAuditMonitor() {
    operationAuditLogMonitorPaused = false;
    setOperationAuditMonitorStatus('Running', true);

    var toggleBtn = document.getElementById('operation_audit_monitor_toggle_btn');
    if (toggleBtn) {
        toggleBtn.setAttribute('data-toggle-raw', 'Pause');
        toggleBtn.value = operationAuditText('audit_pause');
    }

    if (operationAuditLogTimer) return;

    operationAuditLogTimer = setInterval(function() {
        if (operationAuditLogMonitorPaused) return;

        if (!operationAuditIsVisible()) {
            setOperationAuditMonitorStatus('Standby', false);
            return;
        }

        setOperationAuditMonitorStatus('Running', true);
        fetchSettingOperationAuditLogs(true, true);
    }, operationAuditLogIntervalMs);
}

function pauseSettingOperationAuditMonitor() {
    operationAuditLogMonitorPaused = true;
    setOperationAuditMonitorStatus('Paused', false);

    var toggleBtn = document.getElementById('operation_audit_monitor_toggle_btn');
    if (toggleBtn) {
        toggleBtn.setAttribute('data-toggle-raw', 'Start');
        toggleBtn.value = operationAuditText('audit_start');
    }
}

function toggleSettingOperationAuditMonitor() {
    if (operationAuditLogMonitorPaused) {
        startSettingOperationAuditMonitor();
        fetchSettingOperationAuditLogs(true, false);
    } else {
        pauseSettingOperationAuditMonitor();
    }
}

function bindSettingOperationAuditButtons() {
    var refreshBtn = document.getElementById('operation_audit_refresh_btn');
    var toggleBtn = document.getElementById('operation_audit_monitor_toggle_btn');
    var sourceSelect = document.getElementById('operationAuditSourceSelect');

    if (sourceSelect && sourceSelect.dataset.bound !== '1') {
        sourceSelect.dataset.bound = '1';
        sourceSelect.onchange = function() {
            resetSettingOperationAuditMonitorState();
            setOperationAuditMonitorStatus('Running', true);
            var tbody = document.getElementById('operationAuditLogTbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="6">' + operationAuditText('audit_loading') + '</td></tr>';
            fetchSettingOperationAuditLogs(true, false);
        };
    }

    if (refreshBtn && refreshBtn.dataset.bound !== '1') {
        refreshBtn.dataset.bound = '1';
        refreshBtn.onclick = function() {
            fetchSettingOperationAuditLogs(true, false);
        };
    }

    if (toggleBtn && toggleBtn.dataset.bound !== '1') {
        toggleBtn.dataset.bound = '1';
        toggleBtn.onclick = toggleSettingOperationAuditMonitor;
    }
}

function loadSettingOperationAuditLogs(forceReload) {
    bindSettingOperationAuditButtons();
    startSettingOperationAuditMonitor();

    if (operationAuditLogLoaded && forceReload !== true) {
        return;
    }

    fetchSettingOperationAuditLogs(true, false);
}

function fetchSettingOperationAuditLogs(forceReload, silent) {
    var tbody = document.getElementById('operationAuditLogTbody');

    if (operationAuditLogIsLoading) return;
    operationAuditLogIsLoading = true;

    if (tbody && operationAuditLogLoaded !== true && silent !== true) {
        tbody.innerHTML = '<tr><td colspan="6">' + operationAuditText('audit_loading') + '</td></tr>';
    }

    operationAuditPost('operation_audit_log_list', {
        limit: 100,
        source: operationAuditCurrentSource()
    }).then(function(json) {
        operationAuditLogLoaded = true;
        operationAuditLogIsLoading = false;

        if (!json.success) {
            if (tbody && silent !== true) tbody.innerHTML = '<tr><td colspan="6">Load failed</td></tr>';
            setOperationAuditMonitorStatus('Error', false);
            if (silent !== true) settingAccountAlert(json.res_msg || 'Load operation audit log failed.');
            return;
        }

        var records = json.records || [];
        renderSettingOperationAuditLogs(records);
        updateOperationAuditMonitorInfo(records.length);
        setOperationAuditMonitorStatus(operationAuditLogMonitorPaused ? 'Paused' : 'Running', !operationAuditLogMonitorPaused);
    }).catch(function(err) {
        operationAuditLogIsLoading = false;
        setOperationAuditMonitorStatus('Error', false);

        if (tbody && silent !== true) tbody.innerHTML = '<tr><td colspan="6">Load failed</td></tr>';
        if (silent !== true) settingAccountAlert(err.message || 'Load operation audit log failed.');
    });
}

/* =====================================================
   Target Formatter
   目的：
   讓 iDAS / APP 操作紀錄「目標」欄位格式一致。
   - Job ID: 2
   - Job ID: 2; Seq ID: 2
   - Job ID: 2; Seq ID: [1,2]
   - Job ID: 1; Seq ID: 1; Step ID: [2,1]
   ===================================================== */
function operationAuditValueFilled(value) {
    var text = String(value === null || value === undefined ? '' : value).trim();

    return text !== '' &&
           text.toLowerCase() !== 'null' &&
           text.toLowerCase() !== 'undefined';
}

function operationAuditDisplayId(primaryValue, fallbackValue) {
    if (operationAuditValueFilled(primaryValue)) return primaryValue;
    if (operationAuditValueFilled(fallbackValue)) return fallbackValue;
    return '';
}

function operationAuditMoveId(sourceValue, targetValue, fallbackValue) {
    var sourceFilled = operationAuditValueFilled(sourceValue);
    var targetFilled = operationAuditValueFilled(targetValue);

    if (sourceFilled && targetFilled) {
        return '[' + sourceValue + ',' + targetValue + ']';
    }

    if (targetFilled) return targetValue;
    if (operationAuditValueFilled(fallbackValue)) return fallbackValue;
    if (sourceFilled) return sourceValue;

    return '';
}

function operationAuditNormalizeLegacyTarget(targetText) {
    var raw = String(targetText === null || targetText === undefined ? '' : targetText).trim();

    if (!raw) return '';

    // 已經是新格式就直接保留
    if (/Job\s*ID\s*:/i.test(raw) || /Seq\s*ID\s*:/i.test(raw) || /Step\s*ID\s*:/i.test(raw)) {
        return raw;
    }

    // 舊格式：JOB 2 / SEQ 2 / STEP 1
    var jobMatch = raw.match(/\bJOB\s*[:#-]?\s*(\[[^\]]+\]|\d+)/i);
    var seqMatch = raw.match(/\bSEQ\s*[:#-]?\s*(\[[^\]]+\]|\d+)/i);
    var stepMatch = raw.match(/\bSTEP\s*[:#-]?\s*(\[[^\]]+\]|\d+)/i);

    var parts = [];

    if (jobMatch && jobMatch[1]) parts.push('Job ID: ' + jobMatch[1]);
    if (seqMatch && seqMatch[1]) parts.push('Seq ID: ' + seqMatch[1]);
    if (stepMatch && stepMatch[1]) parts.push('Step ID: ' + stepMatch[1]);

    return parts.length ? parts.join('; ') : raw;
}

function operationAuditBuildTarget(row) {
    row = row || {};

    var moduleName = String(row.module || '').trim().toUpperCase();
    var moduleKey = operationAuditNormalizeModuleKey(row.module || '');
    var actionKey = operationAuditNormalizeActionKey(row.action || '');

    // DB Sync: Target column displays the sync direction in the current language.
    if (moduleKey === 'DB_SYNC' && actionKey === 'SYNC_D2C') {
        return operationAuditText('target_SYNC_D2C');
    }

    if (moduleKey === 'DB_SYNC' && actionKey === 'SYNC_C2D') {
        return operationAuditText('target_SYNC_C2D');
    }

    var jobId  = operationAuditDisplayId(row.target_job_id, row.job_id);
    var seqId  = operationAuditDisplayId(row.target_seq_id, row.seq_id);
    var stepId = operationAuditDisplayId(row.target_step_id, row.step_id);

    // 排序資料：只要 source/target 欄位存在，就顯示 [原ID,新ID]
    // 不強制依賴 action，避免舊資料 action 仍是 EDIT 時無法顯示 [2,1]
    if (moduleName === 'SEQ' && (operationAuditValueFilled(row.source_seq_id) || operationAuditValueFilled(row.target_seq_id))) {
        seqId = operationAuditMoveId(row.source_seq_id, row.target_seq_id, row.seq_id);
    }

    if (moduleName === 'STEP' && (operationAuditValueFilled(row.source_step_id) || operationAuditValueFilled(row.target_step_id))) {
        stepId = operationAuditMoveId(row.source_step_id, row.target_step_id, row.step_id);
    }

    var parts = [];

    if (operationAuditValueFilled(jobId)) {
        parts.push('Job ID: ' + jobId);
    }

    if (operationAuditValueFilled(seqId)) {
        parts.push('Seq ID: ' + seqId);
    }

    if (operationAuditValueFilled(stepId)) {
        parts.push('Step ID: ' + stepId);
    }

    // APP 或舊 API 可能直接回 target，沒有拆 ID 欄位時才使用
    if (!parts.length && operationAuditValueFilled(row.target)) {
        return operationAuditNormalizeLegacyTarget(row.target);
    }

    return parts.length ? parts.join('; ') : '-';
}

function operationAuditNormalizeForSignature(value) {
    return String(value === null || value === undefined ? '' : value);
}

function operationAuditIsDbSyncRow(row) {
    row = row || {};

    var moduleKey = operationAuditNormalizeModuleKey(row.module || '');
    var actionRaw = String(row.action || '').trim().toUpperCase();

    return moduleKey === 'DB_SYNC'
        || actionRaw === 'SYNC_D2C'
        || actionRaw === 'SYNC_C2D';
}

function operationAuditIsAuthLoginRow(row) {
    row = row || {};

    var actionKey = operationAuditNormalizeActionKey(row.action || '');
    var moduleKey = operationAuditNormalizeModuleKey(row.module || '');

    // iDAS: module AUTH + action LOGIN
    // Controller: module may be "Login Keyboard" or "-", so action LOGIN alone must also be styled.
    return actionKey === 'LOGIN' || moduleKey === 'AUTH' && actionKey === 'LOGIN';
}

function operationAuditIsAuthLogoutRow(row) {
    row = row || {};

    var actionKey = operationAuditNormalizeActionKey(row.action || '');
    var moduleKey = operationAuditNormalizeModuleKey(row.module || '');

    // iDAS: module AUTH + action LOGOUT
    // Controller: module may be "-", so action LOGOUT alone must also be styled.
    return actionKey === 'LOGOUT' || moduleKey === 'AUTH' && actionKey === 'LOGOUT';
}

function operationAuditBuildRenderSignature(records) {
    if (!records || !records.length) {
        return 'empty';
    }

    return records.map(function(row, index) {
        return [
            index,
            operationAuditNormalizeForSignature(row.log_id),
            operationAuditNormalizeForSignature(row.created_at),
            operationAuditNormalizeForSignature(row.operator || row.user_id),
            operationAuditNormalizeForSignature(row.module),
            operationAuditNormalizeForSignature(row.action),
            operationAuditNormalizeForSignature(row.job_id),
            operationAuditNormalizeForSignature(row.seq_id),
            operationAuditNormalizeForSignature(row.step_id),
            operationAuditNormalizeForSignature(row.source_job_id),
            operationAuditNormalizeForSignature(row.source_seq_id),
            operationAuditNormalizeForSignature(row.source_step_id),
            operationAuditNormalizeForSignature(row.target_job_id),
            operationAuditNormalizeForSignature(row.target_seq_id),
            operationAuditNormalizeForSignature(row.target_step_id),
            operationAuditNormalizeForSignature(row.target),
            operationAuditNormalizeForSignature(row.message || row.title)
        ].join('|');
    }).join('||');
}

function renderSettingOperationAuditLogs(records) {
    var tbody = document.getElementById('operationAuditLogTbody');
    if (!tbody) return;

    records = records || [];

    var newSignature = operationAuditBuildRenderSignature(records);

    // 監控每 2 秒會抓資料，但資料沒變時不要重畫 tbody。
    // 這樣可以避免表格閃爍、抖動、列高/scrollbar 重算造成的晃動。
    if (operationAuditLogRenderSignature === newSignature) {
        return;
    }

    operationAuditLogRenderSignature = newSignature;
    selectedOperationAuditLogId = null;

    if (!records.length) {
        tbody.innerHTML = '<tr><td colspan="6">' + operationAuditText('audit_no_data') + '</td></tr>';
        operationAuditLogLastLogId = 0;
        return;
    }

    var maxLogId = 0;

    tbody.innerHTML = records.map(function(row, index) {
        var logId = parseInt(row.log_id || 0, 10);
        var isNew = operationAuditLogLastLogId > 0 && logId > operationAuditLogLastLogId;
        var isDbSync = operationAuditIsDbSyncRow(row);
        var isAuthLogin = operationAuditIsAuthLoginRow(row);
        var isAuthLogout = operationAuditIsAuthLogoutRow(row);

        var rowClass = 'operation-audit-row'
            + (isNew ? ' operation-audit-new' : '')
            + (isDbSync ? ' operation-audit-db-sync-row' : '')
            + (isAuthLogin ? ' operation-audit-login-row' : '')
            + (isAuthLogout ? ' operation-audit-logout-row' : '');

        if (logId > maxLogId) maxLogId = logId;

        var targetText = operationAuditBuildTarget(row);

        return '<tr class="' + rowClass + '" data-log-id="' + logId + '">' +
            '<td>' + (index + 1) + '</td>' +
            '<td>' + operationAuditEscape(row.created_at || '') + '</td>' +
            '<td>' + operationAuditEscape(row.operator || row.user_id || '') + '</td>' +
            '<td>' + operationAuditEscape(operationAuditTranslateValue('module', row.module || '')) + '</td>' +
            '<td>' + operationAuditEscape(operationAuditTranslateValue('action', row.action || '')) + '</td>' +
            '<td>' + operationAuditEscape(targetText) + '</td>' +
            '</tr>';
    }).join('');

    operationAuditLogLastLogId = maxLogId;
}

function selectSettingOperationAuditLog(row) {
    document.querySelectorAll('.operation-audit-row').forEach(function(tr) {
        tr.classList.remove('selected');
    });

    row.classList.add('selected');
    selectedOperationAuditLogId = row.getAttribute('data-log-id') || '';
}

document.addEventListener('DOMContentLoaded', function() {
    bindSettingOperationAuditButtons();
});
</script>

<style>
/* =====================================================
   operation_audit_log list page - realtime monitor
   ===================================================== */
#OperationAuditLogDisplay.operation-audit-log-page {
    position: relative;
    width: 100%;
    min-height: calc(100vh - 125px);
    padding-bottom: 20px;
    box-sizing: border-box;
}

#OperationAuditLogDisplay .operation-audit-monitor-bar {
    width: 96%;
    margin: 14px auto 0 auto;
    min-height: 42px;
    padding: 8px 14px;
    box-sizing: border-box;
    border-radius: 8px;
    background: #f1eadc;
    border: 1px solid #e0cdb1;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    color: #4a2f18;
    font-size: 15px;
    font-weight: 700;
}

#OperationAuditLogDisplay .operation-audit-monitor-left,
#OperationAuditLogDisplay .operation-audit-monitor-right {
    display: flex;
    align-items: center;
    gap: 12px;
    white-space: nowrap;
}

#OperationAuditLogDisplay .operation-audit-monitor-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    background: #888888;
}

#OperationAuditLogDisplay .operation-audit-monitor-dot.is-running {
    background: #22aa55;
    box-shadow: 0 0 0 0 rgba(34,170,85,.55);
    animation: auditMonitorPulse 1.4s infinite;
}

#OperationAuditLogDisplay .operation-audit-monitor-dot.is-paused {
    background: #999999;
}

#OperationAuditLogDisplay .operation-audit-monitor-dot.is-error {
    background: #cc3333;
}

#OperationAuditLogDisplay .operation-audit-monitor-status {
    min-width: 68px;
    text-align: center;
    padding: 3px 10px;
    border-radius: 12px;
    background: rgba(255,255,255,.72);
}

#OperationAuditLogDisplay .operation-audit-source-label {
    margin-left: 8px;
    color: #4a2f18;
    font-size: 15px;
    font-weight: 800;
}

#OperationAuditLogDisplay .operation-audit-source-select {
    height: 34px;
    min-width: 92px;
    padding: 2px 10px;
    border: 1px solid #b69a73;
    border-radius: 8px;
    background: #ffffff;
    color: #2f1d0f;
    font-size: 16px;
    font-weight: 800;
}

#OperationAuditLogDisplay .operation-audit-table-container {
    width: 96%;
    margin: 12px auto 0 auto;
    overflow: hidden;
}

#OperationAuditLogDisplay #style-operation-audit-table {
    float: none;
    width: 100%;
    height: calc(100vh - 310px);
    min-height: 260px;
    max-height: 420px;
    overflow-y: auto;
    overflow-x: hidden;
}

#OperationAuditLogDisplay #operation_audit_log_table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
}

#OperationAuditLogDisplay #operation_audit_log_table,
#OperationAuditLogDisplay #operation_audit_log_table * {
    transition: none !important;
}

#OperationAuditLogDisplay #operationAuditLogTbody {
    min-height: 260px;
}

#OperationAuditLogDisplay #operation_audit_log_table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background-color: #616161;
    color: #fff;
    font-size: 16px;
    height: 40px;
    text-align: center;
    vertical-align: middle;
}

#OperationAuditLogDisplay #operation_audit_log_table th,
#OperationAuditLogDisplay #operation_audit_log_table td {
    border: 1px solid #ddd;
    padding: 7px 6px;
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(1),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(1) { width: 5%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(2),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(2) { width: 17%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(3),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(3) { width: 10%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(4),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(4) { width: 9%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(5),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(5) { width: 9%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(6),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(6) { width: 16%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(7),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(7) { width: 9%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(8),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(8) { width: 25%; }

#OperationAuditLogDisplay #operation_audit_log_table tbody td {
    height: 36px;
    font-size: 15px;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr {
    cursor: default;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr:nth-child(odd) td {
    background-color: #eeeeee;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr:nth-child(even) td {
    background-color: #ffffff;
}

/* DB_SYNC / SAVE 整列藍色 */
#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-db-sync-row td {
    background-color: #d8ecff !important;
    color: #063b63 !important;
    font-weight: 800 !important;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-db-sync-row:hover td {
    background-color: #c4e2ff !important;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-db-sync-row.selected td {
    background-color: #9acfff !important;
    color: #002b4a !important;
}


/* AUTH LOGIN / LOGOUT 不同顏色 */
#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-login-row td {
    background-color: #dff3df !important;
    color: #145a24 !important;
    font-weight: 800 !important;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-login-row:hover td {
    background-color: #cceccc !important;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-login-row.selected td {
    background-color: #aee0ae !important;
    color: #0b3d18 !important;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-logout-row td {
    background-color: #ffe4c7 !important;
    color: #7a3b00 !important;
    font-weight: 800 !important;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-logout-row:hover td {
    background-color: #ffd3a3 !important;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-logout-row.selected td {
    background-color: #ffbd73 !important;
    color: #5a2a00 !important;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr.selected,
#OperationAuditLogDisplay #operation_audit_log_table tbody tr.selected td {
    background-color: #9AC0CD !important;
    color: #000000;
    font-weight: normal;
}

#OperationAuditLogDisplay #operation_audit_log_table tbody tr.operation-audit-new td {
    animation: auditNewRowFlash 2.2s ease-out 1;
    font-weight: 700;
}

#OperationAuditLogDisplay .operation-audit-message-cell {
    text-align: left;
}

#OperationAuditLogDisplay .operation-audit-footer {
    display: none !important;
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    width: 100%;
    padding: 12px 20px 22px 20px;
    background-color: #ffffff;
    text-align: center;
    z-index: 2;
    box-sizing: border-box;
}

#OperationAuditLogDisplay .operation-audit-footer .buttonbox {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 28px;
    padding: 0;
    margin: 0 auto;
}

#OperationAuditLogDisplay .operation-audit-footer .buttonbox input {
    width: 120px;
    height: 50px;
    background: #333333;
    color: #ffffff;
    font-size: 20px;
    border: 2px outset #ffffff;
    border-radius: 10px;
    transition: color 0.3s, background-color 0.3s, transform 0.1s;
}

#OperationAuditLogDisplay .operation-audit-footer .buttonbox input:hover {
    cursor: pointer;
    background: #DDDDDD;
    color: #000000;
}

@keyframes auditMonitorPulse {
    0% { box-shadow: 0 0 0 0 rgba(34,170,85,.55); }
    70% { box-shadow: 0 0 0 10px rgba(34,170,85,0); }
    100% { box-shadow: 0 0 0 0 rgba(34,170,85,0); }
}

@keyframes auditNewRowFlash {
    0% { background-color: #fff1a6; }
    100% { background-color: inherit; }
}

@media (max-width: 900px) {
    #OperationAuditLogDisplay .operation-audit-monitor-bar {
        display: block;
        font-size: 14px;
    }

    #OperationAuditLogDisplay .operation-audit-monitor-left,
    #OperationAuditLogDisplay .operation-audit-monitor-right {
        justify-content: center;
        margin: 3px 0;
    }
}
</style>
