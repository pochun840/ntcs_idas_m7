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
                    <option value="app">APP</option>
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
                            <tr><td colspan="8" data-i18n="audit_select_tab">Please select operation_audit_log tab</td></tr>
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
    var lang = String(operationAuditCookieValue('language') || operationAuditCookieValue('lang') || 'en-us')
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

            action_NEW: 'NEW',
            action_ADD: 'ADD',
            action_EDIT: 'EDIT',
            action_DELETE: 'DELETE',
            action_COPY: 'COPY',
            action_UP: 'UP',
            action_DOWN: 'DOWN',
            action_LOG: 'LOG',

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

            action_NEW: '新增',
            action_ADD: '新增',
            action_EDIT: '編輯',
            action_DELETE: '刪除',
            action_COPY: '複製',
            action_UP: '上移',
            action_DOWN: '下移',
            action_LOG: '紀錄',

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

            action_NEW: '新增',
            action_ADD: '新增',
            action_EDIT: '编辑',
            action_DELETE: '删除',
            action_COPY: '复制',
            action_UP: '上移',
            action_DOWN: '下移',
            action_LOG: '纪录',

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

function operationAuditTranslateValue(type, value) {
    var raw = String(value === null || value === undefined ? '' : value).trim();
    if (raw === '') return '';

    var upper = raw.toUpperCase();
    var key = type + '_' + upper;

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
            if (tbody) tbody.innerHTML = '<tr><td colspan="8">' + operationAuditText('audit_loading') + '</td></tr>';
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
        tbody.innerHTML = '<tr><td colspan="8">' + operationAuditText('audit_loading') + '</td></tr>';
    }

    operationAuditPost('operation_audit_log_list', {
        limit: 100,
        source: operationAuditCurrentSource()
    }).then(function(json) {
        operationAuditLogLoaded = true;
        operationAuditLogIsLoading = false;

        if (!json.success) {
            if (tbody && silent !== true) tbody.innerHTML = '<tr><td colspan="8">Load failed</td></tr>';
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

        if (tbody && silent !== true) tbody.innerHTML = '<tr><td colspan="8">Load failed</td></tr>';
        if (silent !== true) settingAccountAlert(err.message || 'Load operation audit log failed.');
    });
}


function operationAuditDisplayOperator(row) {
    return row.operator || row.user_id || row.user_name || row.username || row.user || '';
}

function operationAuditBuildTarget(row) {
    var target = [];
    var source = String(row.source || operationAuditCurrentSource() || '').toLowerCase();

    // APP 的 ntcs_log.csv 已經有完整 target 欄位，例如：Job ID: 2; Seq ID: [1,2]
    // 不要再額外補 JOB / SEQ / STEP，避免格式重複或對不起來。
    if (row.target !== null && row.target !== '' && row.target !== undefined) {
        target.push(row.target);
        return target;
    }

    if (row.job_id !== null && row.job_id !== '' && row.job_id !== undefined) target.push('JOB ' + row.job_id);
    if (row.seq_id !== null && row.seq_id !== '' && row.seq_id !== undefined) target.push('SEQ ' + row.seq_id);
    if (row.step_id !== null && row.step_id !== '' && row.step_id !== undefined) target.push('STEP ' + row.step_id);
    if (!target.length) target.push('-');

    return target;
}

function renderSettingOperationAuditLogs(records) {
    var tbody = document.getElementById('operationAuditLogTbody');
    if (!tbody) return;

    selectedOperationAuditLogId = null;

    if (!records || !records.length) {
        tbody.innerHTML = '<tr><td colspan="8">' + operationAuditText('audit_no_data') + '</td></tr>';
        operationAuditLogLastLogId = 0;
        return;
    }

    var maxLogId = 0;

    tbody.innerHTML = records.map(function(row, index) {
        var logId = parseInt(row.log_id || 0, 10);
        var target = operationAuditBuildTarget(row);
        var operator = operationAuditDisplayOperator(row);
        var isNew = operationAuditLogLastLogId > 0 && logId > operationAuditLogLastLogId;
        var rowClass = 'operation-audit-row' + (isNew ? ' operation-audit-new' : '');

        if (logId > maxLogId) maxLogId = logId;

        return '<tr class="' + rowClass + '" data-log-id="' + logId + '">' +
            '<td>' + (index + 1) + '</td>' +
            '<td title="' + operationAuditEscape(row.created_at || '') + '">' + operationAuditEscape(row.created_at || '') + '</td>' +
            '<td title="' + operationAuditEscape(operator) + '">' + operationAuditEscape(operator) + '</td>' +
            '<td title="' + operationAuditEscape(row.module || '') + '">' + operationAuditEscape(operationAuditTranslateValue('module', row.module || '')) + '</td>' +
            '<td title="' + operationAuditEscape(row.action || '') + '">' + operationAuditEscape(operationAuditTranslateValue('action', row.action || '')) + '</td>' +
            '<td title="' + operationAuditEscape(target.join(' / ')) + '">' + operationAuditEscape(target.join(' / ')) + '</td>' +
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
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(3) { width: 8%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(4),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(4) { width: 13%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(5),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(5) { width: 8%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(6),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(6) { width: 20%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(7),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(7) { width: 7%; }
#OperationAuditLogDisplay #operation_audit_log_table th:nth-child(8),
#OperationAuditLogDisplay #operation_audit_log_table td:nth-child(8) { width: 22%; }

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
