<!-- operation_audit_log detail OP -->
<div id="operationAuditLogDetailPanel" class="operation-audit-detail-page" style="display:none;">
    <div class="operation-audit-detail-card">
        <header class="operation-audit-detail-header">
            <button type="button" class="operation-audit-back-btn" onclick="backSettingOperationAuditLogList()">&lt;</button>
            <h3>Operation Audit Detail</h3>
        </header>

        <div class="operation-audit-detail-body">
            <div id="operationAuditDetailSummary" class="operation-audit-detail-summary"></div>

            <div class="operation-audit-json-grid">
                <div class="operation-audit-json-card">
                    <div class="operation-audit-json-title">before_json</div>
                    <pre id="operationAuditBeforeJson"></pre>
                </div>
                <div class="operation-audit-json-card">
                    <div class="operation-audit-json-title">after_json</div>
                    <pre id="operationAuditAfterJson"></pre>
                </div>
                <div class="operation-audit-json-card">
                    <div class="operation-audit-json-title">order_before_json</div>
                    <pre id="operationAuditOrderBeforeJson"></pre>
                </div>
                <div class="operation-audit-json-card">
                    <div class="operation-audit-json-title">order_after_json</div>
                    <pre id="operationAuditOrderAfterJson"></pre>
                </div>
                <div class="operation-audit-json-card operation-audit-json-card-wide">
                    <div class="operation-audit-json-title">request_json</div>
                    <pre id="operationAuditRequestJson"></pre>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- operation_audit_log detail ED -->

<script>
function operationAuditPrettyJson(value) {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }

    var text = String(value);
    try {
        return JSON.stringify(JSON.parse(text), null, 2);
    } catch (e) {
        return text;
    }
}

function operationAuditDetailField(label, value) {
    return '<div class="operation-audit-detail-field">' +
        '<div class="operation-audit-detail-label">' + operationAuditEscape(label) + '</div>' +
        '<div class="operation-audit-detail-value">' + operationAuditEscape(value === null || value === undefined || value === '' ? '-' : value) + '</div>' +
        '</div>';
}

function renderSettingOperationAuditLogDetail(row) {
    var summary = document.getElementById('operationAuditDetailSummary');
    if (!summary) return;

    var source = [];
    if (row.source_job_id !== null && row.source_job_id !== '' && row.source_job_id !== undefined) source.push('JOB ' + row.source_job_id);
    if (row.source_seq_id !== null && row.source_seq_id !== '' && row.source_seq_id !== undefined) source.push('SEQ ' + row.source_seq_id);
    if (row.source_step_id !== null && row.source_step_id !== '' && row.source_step_id !== undefined) source.push('STEP ' + row.source_step_id);

    var target = [];
    if (row.target_job_id !== null && row.target_job_id !== '' && row.target_job_id !== undefined) target.push('JOB ' + row.target_job_id);
    if (row.target_seq_id !== null && row.target_seq_id !== '' && row.target_seq_id !== undefined) target.push('SEQ ' + row.target_seq_id);
    if (row.target_step_id !== null && row.target_step_id !== '' && row.target_step_id !== undefined) target.push('STEP ' + row.target_step_id);

    summary.innerHTML =
        operationAuditDetailField('Log ID', row.log_id) +
        operationAuditDetailField('Time', row.created_at) +
        operationAuditDetailField('User ID', row.user_id) +
        operationAuditDetailField('Operator', row.operator) +
        operationAuditDetailField('Client IP', row.client_ip) +
        operationAuditDetailField('Device ID', row.device_id) +
        operationAuditDetailField('Module', row.module) +
        operationAuditDetailField('Action', row.action) +
        operationAuditDetailField('Status', row.status) +
        operationAuditDetailField('Job ID', row.job_id) +
        operationAuditDetailField('Seq ID', row.seq_id) +
        operationAuditDetailField('Step ID', row.step_id) +
        operationAuditDetailField('Source', source.length ? source.join(' / ') : '-') +
        operationAuditDetailField('Target', target.length ? target.join(' / ') : '-') +
        operationAuditDetailField('Title', row.title) +
        operationAuditDetailField('Message', row.message);

    document.getElementById('operationAuditBeforeJson').textContent = operationAuditPrettyJson(row.before_json);
    document.getElementById('operationAuditAfterJson').textContent = operationAuditPrettyJson(row.after_json);
    document.getElementById('operationAuditOrderBeforeJson').textContent = operationAuditPrettyJson(row.order_before_json);
    document.getElementById('operationAuditOrderAfterJson').textContent = operationAuditPrettyJson(row.order_after_json);
    document.getElementById('operationAuditRequestJson').textContent = operationAuditPrettyJson(row.request_json);
}
</script>

<style>
/* =====================================================
   operation_audit_log detail page - independent page under setting
   ===================================================== */
#operationAuditLogDetailPanel.operation-audit-detail-page {
    width: 96%;
    margin: 18px auto 0 auto;
}

#operationAuditLogDetailPanel .operation-audit-detail-card {
    width: 100%;
    max-height: calc(100vh - 160px);
    overflow: hidden;
    border-radius: 6px;
    background: #eeeeee;
    box-shadow: 0 2px 10px rgba(0,0,0,.16);
}

#operationAuditLogDetailPanel .operation-audit-detail-header {
    height: 56px;
    background: #686767;
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 0 18px;
}

#operationAuditLogDetailPanel .operation-audit-detail-header h3 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

#operationAuditLogDetailPanel .operation-audit-back-btn {
    width: 48px;
    height: 40px;
    border: 1px outset #ffffff;
    border-radius: 8px;
    background: #333333;
    color: #ffffff;
    font-size: 26px;
    line-height: 34px;
    cursor: pointer;
}

#operationAuditLogDetailPanel .operation-audit-detail-body {
    max-height: calc(100vh - 216px);
    overflow-y: auto;
    padding: 18px;
    box-sizing: border-box;
}

#operationAuditLogDetailPanel .operation-audit-detail-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 10px;
    margin-bottom: 16px;
}

#operationAuditLogDetailPanel .operation-audit-detail-field {
    min-height: 62px;
    border: 1px solid #d1d1d1;
    border-radius: 8px;
    background: #ffffff;
    overflow: hidden;
}

#operationAuditLogDetailPanel .operation-audit-detail-label {
    padding: 6px 8px;
    background: #dddddd;
    color: #333333;
    font-size: 14px;
    font-weight: 800;
}

#operationAuditLogDetailPanel .operation-audit-detail-value {
    padding: 8px;
    color: #111111;
    font-size: 15px;
    font-weight: 600;
    word-break: break-word;
}

#operationAuditLogDetailPanel .operation-audit-json-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

#operationAuditLogDetailPanel .operation-audit-json-card {
    border: 1px solid #d1d1d1;
    border-radius: 8px;
    background: #ffffff;
    overflow: hidden;
}

#operationAuditLogDetailPanel .operation-audit-json-card-wide {
    grid-column: 1 / span 2;
}

#operationAuditLogDetailPanel .operation-audit-json-title {
    padding: 8px 10px;
    background: #616161;
    color: #ffffff;
    font-size: 16px;
    font-weight: 800;
}

#operationAuditLogDetailPanel pre {
    min-height: 130px;
    max-height: 260px;
    overflow: auto;
    margin: 0;
    padding: 12px;
    background: #fafafa;
    color: #111111;
    font-size: 14px;
    line-height: 1.35;
    white-space: pre-wrap;
    word-break: break-word;
}

@media (max-width: 900px) {
    #operationAuditLogDetailPanel .operation-audit-detail-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    #operationAuditLogDetailPanel .operation-audit-json-grid {
        grid-template-columns: 1fr;
    }

    #operationAuditLogDetailPanel .operation-audit-json-card-wide {
        grid-column: auto;
    }
}
</style>
