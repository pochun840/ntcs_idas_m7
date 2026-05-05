<!-- operation_audit_log list OP -->
<div id="OperationAuditLogDisplay" class="operation-audit-log-page" style="display:none;">
    <div id="operationAuditLogListPanel">
        <div class="operation-audit-monitor-bar">
            <div class="operation-audit-monitor-left">
                <span id="operationAuditMonitorDot" class="operation-audit-monitor-dot is-running"></span>
                <span class="operation-audit-monitor-title">Realtime Monitor</span>
                <span id="operationAuditMonitorStatus" class="operation-audit-monitor-status">Running</span>
                <label class="operation-audit-source-label" for="operationAuditSourceSelect">Monitor</label>
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
                                <th>No</th>
                                <th>Time</th>
                                <th>User</th>
                                <th>Module</th>
                                <th>Action</th>
                                <th>Target</th>
                                <th>Status</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody id="operationAuditLogTbody" style="font-size: 1.55vmin;text-align: center;">
                            <tr><td colspan="8">Please select operation_audit_log tab</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
<!-- operation_audit_log list ED -->

<script>
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

    if (statusEl) statusEl.innerText = status || '';

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

    if (updatedEl) updatedEl.innerText = 'Updated: ' + operationAuditNowTime();
    if (countEl) countEl.innerText = 'Records: ' + (count || 0);
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
            throw new Error('operation_audit_log response is not JSON. HTTP Status: ' + result.status);
        }
    });
}

function startSettingOperationAuditMonitor() {
    operationAuditLogMonitorPaused = false;
    setOperationAuditMonitorStatus('Running', true);

    var toggleBtn = document.getElementById('operation_audit_monitor_toggle_btn');
    if (toggleBtn) toggleBtn.value = 'Pause';

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
    if (toggleBtn) toggleBtn.value = 'Start';
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
            if (tbody) tbody.innerHTML = '<tr><td colspan="8">Loading...</td></tr>';
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
        tbody.innerHTML = '<tr><td colspan="8">Loading...</td></tr>';
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

function renderSettingOperationAuditLogs(records) {
    var tbody = document.getElementById('operationAuditLogTbody');
    if (!tbody) return;

    selectedOperationAuditLogId = null;

    if (!records || !records.length) {
        tbody.innerHTML = '<tr><td colspan="8">No Data</td></tr>';
        operationAuditLogLastLogId = 0;
        return;
    }

    var maxLogId = 0;

    tbody.innerHTML = records.map(function(row, index) {
        var logId = parseInt(row.log_id || 0, 10);
        var target = [];
        var isNew = operationAuditLogLastLogId > 0 && logId > operationAuditLogLastLogId;
        var rowClass = 'operation-audit-row' + (isNew ? ' operation-audit-new' : '');

        if (logId > maxLogId) maxLogId = logId;

        if (row.target !== null && row.target !== '' && row.target !== undefined) target.push(row.target);
        if (row.job_id !== null && row.job_id !== '' && row.job_id !== undefined) target.push('JOB ' + row.job_id);
        if (row.seq_id !== null && row.seq_id !== '' && row.seq_id !== undefined) target.push('SEQ ' + row.seq_id);
        if (row.step_id !== null && row.step_id !== '' && row.step_id !== undefined) target.push('STEP ' + row.step_id);
        if (!target.length) target.push('-');

        return '<tr class="' + rowClass + '" data-log-id="' + logId + '">' +
            '<td>' + (index + 1) + '</td>' +
            '<td>' + operationAuditEscape(row.created_at || '') + '</td>' +
            '<td>' + operationAuditEscape(row.operator || row.user_id || '') + '</td>' +
            '<td>' + operationAuditEscape(row.module || '') + '</td>' +
            '<td>' + operationAuditEscape(row.action || '') + '</td>' +
            '<td>' + operationAuditEscape(target.join(' / ')) + '</td>' +
            '<td>' + operationAuditEscape(row.status || '') + '</td>' +
            '<td class="operation-audit-message-cell">' + operationAuditEscape(row.message || row.title || '') + '</td>' +
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
