<?php require APPROOT . 'views/inc/header.php'; ?>

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/w3.css" type="text/css">
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/agent.css" type="text/css">
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/balloon.min.css" type="text/css">

<script>
document.addEventListener('DOMContentLoaded', function () {

    function getNowPlus8() {
        const now = new Date();
        const utc = now.getTime() + (now.getTimezoneOffset() * 60000);
        return new Date(utc + 8 * 60 * 60 * 1000);
    }

    function pad2(n) {
        return String(n).padStart(2, '0');
    }

    function renderTime(now) {
        const year  = now.getFullYear();
        const month = pad2(now.getMonth() + 1);
        const day   = pad2(now.getDate());

        const hour   = pad2(now.getHours());
        const minute = pad2(now.getMinutes());
        const second = pad2(now.getSeconds());

        const dayEl  = document.getElementById('agent-day');
        const timeEl = document.getElementById('agent-time');

        if (dayEl)  dayEl.textContent  = `Date: ${year}/${month}/${day}`;
        if (timeEl) timeEl.textContent = `Time: ${hour} : ${minute}:${second}`;
    }

    let lastSec = -1;
    function tick() {
        const now = getNowPlus8();
        const sec = now.getSeconds();
        if (sec !== lastSec) {
            lastSec = sec;
            renderTime(now);
        }
        requestAnimationFrame(tick);
    }

    renderTime(getNowPlus8());
    requestAnimationFrame(tick);
});
</script>

<div class="container">
    <div class="header">
        <div id="agent-day" class="w3-right-align" style="font-size: 14px; margin: 10px">Date:</div>
        <div id="agent-time" class="w3-right-align" style="font-size: 14px; margin: 0px 10px;">Time:</div>
        <div style="margin-top: 1%">
            <h1><?php echo TITLE_AGENT; ?></h1>
        </div>
    </div>

    <div style="margin-top: 10px">
        <div id="menu">
            <a id="bnt1" onclick="OpenButton('agent')"><?php echo $text['agent_title'];?></a>
        </div>

        <div id="Agent_Display" class="agent-display" style="margin-top:18px">
            <div class="table-scroll" id="style-Agent">
                <table id="data-table" class="container2" role="table">
                    <colgroup>
                        <col style="min-width:56px">
                        <col style="min-width:120px">
                        <col style="min-width:160px">
                        <col style="min-width:140px">
                        <col style="min-width:200px">
                        <col style="min-width:80px">
                        <col style="min-width:80px">
                        <col style="min-width:110px">
                        <col style="min-width:110px">
                        <col style="min-width:110px">
                        <col style="min-width:90px">
                        <col style="min-width:90px">
                        <col style="min-width:160px">
                    </colgroup>

                    <thead class="sticky">
                        <tr>
                            <th class="col-no"><?php echo $text['column_no']; ?></th>
                            <th class="col-type"><?php echo $text['device_type'];?></th>
                            <th class="col-name"><?php echo $text['device_name'];?></th>
                            <th class="col-ip" title="<?php echo htmlspecialchars($text['network_ip'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo $text['network_ip']; ?></th>
                            <th class="col-dt"><?php echo $text['column_datetime']; ?></th>
                            <th class="col-job"><?php echo $text['job_id'];?></th>
                            <th class="col-seq"><?php echo $text['seq_id'];?></th>
                            <th class="col-torque"><?php echo $text['Torque'];?></th>
                            <th class="col-unit"><?php echo $text['torque_unit'];?></th>
                            <th class="col-angle"><?php echo $text['angle']; ?></th>
                            <th class="col-count"><?php echo $text['column_count'];?></th>
                            <th class="col-total"><?php echo $text['column_total']; ?></th>
                            <th class="col-status"><?php echo $text['column_status']; ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="footer">
            <div class="w3-center">
                <!--<button type="button" class="custom-btn btn-13" onclick="open_das()"><span style="font-size: 24px">&#8629;</span><span><?php //echo $text['open'];?></span></button>-->
                <button class="custom-btn btn-15" onclick="window.location.href='?url=Dashboards'">
                    <span style="font-size: 24px">&#8678;</span>
                    <span><?php echo $text['return'];?></span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    "use strict";

    /** ---------- 對照表與轉換 ---------- */
    const FASTEN_STATUS = [
        "Initialize","Tool Ready","Tool running","Reverse","OK",
        "OK-SEQ","OK-JOB","NG","NG Stop","Setting","EOC","C1","C2","C4","C5","BS"
    ];
    const TORQUE_UNIT = ["Kgf-m","N-m","Kgf-cm","In-lbs"];

    function fastenStatusName(idx){
        const n = Number(idx);
        return Number.isInteger(n) && n >= 0 && n < FASTEN_STATUS.length ? FASTEN_STATUS[n] : String(idx ?? "");
    }

    function torqueUnitName(idx){
        const n = Number(idx);
        return Number.isInteger(n) && n >= 0 && n < TORQUE_UNIT.length ? TORQUE_UNIT[n] : String(idx ?? "");
    }

    /** ---------- PHP 常數帶入 ---------- */
    const DEVICE_TYPE_11 = <?php echo json_encode(defined('DEVICE_TYPE_11') ? DEVICE_TYPE_11 : 'KL-NTCS-M7'); ?>;
    const ICONMODE = <?php echo json_encode(defined('ICONMODE') ? ICONMODE : 0); ?>;

    /** ---------- 機型名稱與登入頁集中設定 ---------- */
    const DEVICE_TYPE_CONFIG = Object.freeze({
        7:  Object.freeze({ name: 'GTCS-10', path: '/das/public/index.php?url=In' }),
        8:  Object.freeze({ name: 'NTCS-10', path: '/idas/public/?url=In' }),
        9:  Object.freeze({ name: 'TCC-HMI', path: '/idas/public/?url=In' }),
        10: Object.freeze({ name: 'MTCS', path: null }),
        11: Object.freeze({ name: DEVICE_TYPE_11 || 'KL-NTCS-M7', path: '/idas/public/?url=In' })
    });

    /** ---------- DataTable 初始化 ---------- */
    const table2 = $('#data-table').DataTable({
        autoWidth: false,
        searching: false,
        info: false,
        ordering: false,
        dom: "frti",
        pageLength: 99,
        language: { zeroRecords: " " },

        rowId: 'client_ip',

        columns: [
            {
                data: null,
                width: "5%",
                render: (d, t, r, meta) => {
                    const n = meta.row + 1;
                    const label = String(n).padStart(2, '0');
                    return `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${label}">${label}</span>`;
                }
            },
            {
                data: 'device_type_name',
                width: "10%",
                render: function (d, type, row) {
                    const label = escapeAgentHtml(d || '');
                    const href = getDevicePageUrl(row?.device_type, row?.client_ip);
                    if (!href) {
                        return `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${label}">${label}</span>`;
                    }
                    const tooltip = escapeAgentHtml(`${d || ''} - ${row?.client_ip || ''}`);
                    return `<a href="${escapeAgentHtml(href)}" target="_blank" rel="noopener noreferrer" `
                        + `class="agent-device-link" data-bs-toggle="tooltip" data-bs-placement="top" `
                        + `title="${tooltip}" aria-label="${tooltip}">${label} `
                        + `<i class="fa fa-external-link" aria-hidden="true" style="font-size:.8em;margin-left:4px"></i></a>`;
                }
            },
            {
                data: 'device_name',
                width: "14%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'client_ip',
                width: "10%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'data_time',
                width: "20%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'job_id',
                width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'sequence_id',
                width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'final_fasten_torque',
                width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'torque_unit_name',
                width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'final_fasten_angle',
                width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'last_screw_count',
                width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'total_screw_count',
                width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'fasten_status_name',
                width: "15%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            }
        ]
    });

    function initTooltips() {
        if (typeof bootstrap === 'undefined' || typeof bootstrap.Tooltip === 'undefined') {
            return;
        }

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            if (el._bsTooltipInstance) return;
            el._bsTooltipInstance = new bootstrap.Tooltip(el);
        });
    }

    function renumberSerial() {
        if (!table2) return;

        let visibleIndex = 0;
        table2.rows({ page: 'current' }).every(function() {
            visibleIndex++;
            const label = String(visibleIndex).padStart(2, '0');
            const $row = $(this.node());
            const $cell = $row.find('td').eq(0).find('span[data-bs-toggle="tooltip"]');
            if ($cell.length) {
                $cell.text(label).attr('title', label);
            }
        });
    }

    table2.on('draw', function () {
        renumberSerial();
        initTooltips();
    });

    $('#data-table tbody').on('click', 'tr', function() {
        if ($(this).hasClass('selected')) {
            $(this).removeClass('selected');
        } else {
            table2.$('tr.selected').removeClass('selected');
            $(this).addClass('selected');
        }
    });

    /** ---------- WebSocket upsert ---------- */
    let socket;
    const server_ip = <?php echo json_encode($data['agent_server_ip']); ?>;
    const serverUrl = `ws://${server_ip}:9501`;

    function getDeviceTypeName(deviceType) {
        const config = DEVICE_TYPE_CONFIG[Number(deviceType)];
        return config ? config.name : (deviceType ?? '');
    }

    function escapeAgentHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (character) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character];
        });
    }

    function normalizeDeviceHost(clientIp) {
        let host = String(clientIp ?? '').trim();
        if (!host || /[\s/?#@]/.test(host)) return '';

        if (host.startsWith('[') && host.endsWith(']')) {
            host = host.slice(1, -1);
        }

        const ipv4Parts = host.split('.');
        if (ipv4Parts.length === 4 && ipv4Parts.every(part => /^\d{1,3}$/.test(part))) {
            return ipv4Parts.every(part => Number(part) <= 255 && String(Number(part)) === part)
                ? host
                : '';
        }

        if (host.includes(':')) {
            try {
                const parsed = new URL(`http://[${host}]/`);
                return parsed.hostname === `[${host.toLowerCase()}]` ? `[${host}]` : '';
            } catch (error) {
                return '';
            }
        }

        if (host.length > 253) return '';
        const labels = host.split('.');
        const validHostname = labels.every(label =>
            label.length >= 1
            && label.length <= 63
            && /^[A-Za-z0-9](?:[A-Za-z0-9-]*[A-Za-z0-9])?$/.test(label)
        );
        return validHostname ? host.toLowerCase() : '';
    }

    function getDevicePageUrl(deviceType, clientIp) {
        const config = DEVICE_TYPE_CONFIG[Number(deviceType)];
        if (!config || !config.path) return '';

        const host = normalizeDeviceHost(clientIp);
        return host ? `http://${host}${config.path}` : '';
    }

    function formatDataTime(raw) {
        if (!raw) return '';
        const s = String(raw);

        if (/^\d{8} \d{2}:\d{2}:\d{2}$/.test(s)) {
            return `${s.slice(0, 4)}-${s.slice(4, 6)}-${s.slice(6, 8)}${s.slice(8)}`;
        }

        return s;
    }

    function upsertRow(payload) {
        console.log(payload);

        const deviceType = Number(payload.device_type);

        const rowData = {
            device_type: deviceType,
            device_type_name: getDeviceTypeName(payload.device_type),
            device_name: payload.device_name ?? '',
            client_ip: payload.client_ip ?? '',
            data_time: formatDataTime(payload.data_time),
            job_id: payload.job_id ?? '',

            sequence_id: (deviceType === 9 ? payload.seq_id : payload.sequence_id) ?? '',
            final_fasten_torque: ([7, 9].includes(deviceType) ? payload.fasten_torque : payload.final_fasten_torque) ?? '',
            torque_unit_name: torqueUnitName(deviceType === 9 ? payload.step_tor_unit : payload.torque_unit),
            final_fasten_angle: ([7, 9].includes(deviceType) ? payload.fasten_angle : payload.final_fasten_angle) ?? '',
            last_screw_count: payload.last_screw_count ?? '',
            total_screw_count: ([7, 9].includes(deviceType) ? payload.max_screw_count : payload.total_screw_count) ?? '',
            fasten_status_name: fastenStatusName(payload.fasten_status),
            device_sn: payload.device_sn ?? '',
            device_id: payload.device_id ?? ''
        };

        if (!rowData.client_ip && !rowData.device_sn && !rowData.device_id) return;

        const key = (
            rowData.device_sn ||
            rowData.device_id ||
            (rowData.client_ip + "_" + rowData.device_name)
        );

        let rowApi = table2.row(function(idx, d) {
            return d.client_ip === rowData.client_ip;
        });

        const isUpdate = rowApi.any();

        if (isUpdate) {
            rowApi.data(rowData).draw(false);
        } else {
            table2.row.add(rowData).draw(false);

            rowApi = table2.row(function(idx, d) {
                const existingKey = (
                    d.device_sn ||
                    d.device_id ||
                    (d.client_ip + "_" + d.device_name)
                );
                return existingKey === key;
            });
        }
    }

    function handleWebSocketMessage(event) {
        const raw = (event && event.data != null) ? String(event.data).trim() : "";
        if (!raw) return;

        if (!raw.startsWith("{") && !raw.startsWith("[") && !raw.startsWith("Client ")) {
            console.log("Non-JSON WS message, ignored:", raw);
            return;
        }

        let jsonStr = raw;
        const m = /^Client \d+ said:\s*(\{.*\})$/.exec(raw);
        if (m) {
            jsonStr = m[1];
        }

        const trimmed = jsonStr.trim();
        if (!trimmed.startsWith("{") && !trimmed.startsWith("[")) {
            console.log("WS message looks non-JSON after Client-strip, ignored:", raw);
            return;
        }

        let payload;
        try {
            payload = JSON.parse(trimmed);
        } catch (err) {
            console.error("JSON parse error:", err, "raw:", raw);
            return;
        }

        try {
            upsertRow(payload);
        } catch (err) {
            console.error("WS upsert error:", err, "payload:", payload);
        }
    }

    function connectWebSocket() {
        try {
            socket = new WebSocket(serverUrl);
        } catch (e) {
            console.error('WebSocket 無法建立：', e);
            setTimeout(connectWebSocket, 5000);
            return;
        }

        socket.addEventListener('open', () => {
            console.log('WebSocket 連線成功');
        });

        socket.addEventListener('message', handleWebSocketMessage);

        socket.addEventListener('close', () => {
            console.log('WebSocket 關閉，5 秒後重連...');
            setTimeout(connectWebSocket, 5000);
        });

        socket.addEventListener('error', (e) => {
            console.error('WebSocket 錯誤', e);
            try { socket.close(); } catch (_) {}
        });
    }

    /** ---------- 對外：開啟 DAS ---------- */
    window.open_das = function() {
        const d = table2.row('.selected').data();
        const ip = d?.client_ip;
        if (ip) window.open(`http://${ip}/das/public/`, '_blank');
    };

    /** ---------- 啟動 ---------- */
    $(document).ready(function() {
        connectWebSocket();
        initTooltips();
    });

})();
</script>

<?php require APPROOT . 'views/inc/footer.php'; ?>

<style>
.agent-display .table-scroll{
    position: relative;
    max-height: calc(100vh - 260px);
    overflow: auto;
}

#data-table{
    width: 100%;
    table-layout: fixed;
    border-collapse: separate;
    border-spacing: 0;
}

#data-table th,
#data-table td{
    padding: 8px 10px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#data-table thead.sticky th{
    position: sticky;
    top: 0;
    z-index: 2;
    background: #1f2937;
    color: #fff;
}

@media (max-width: 1200px){
    #data-table .col-count,
    #data-table .col-total {
        display: none;
    }
}

@media (max-width: 992px){
    #data-table .col-angle,
    #data-table .col-unit {
        display: none;
    }
}

@media (max-width: 768px){
    #data-table .col-job,
    #data-table .col-seq,
    #data-table .col-type {
        display: none;
    }
}

.dataTables_empty{
    display: none;
}

#data-table .col-ip{
    cursor: help;
}

.row-flash {
    animation: flashblue 0.4s linear;
}

@keyframes flashblue {
    from { background-color: #dbeafe; }
    to   { background-color: inherit; }
}
</style>

<script>
(function () {
    const FLAG_KEY = 'idas_agent_initial_ran';

    if (localStorage.getItem(FLAG_KEY) === '1') {
        return;
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (localStorage.getItem(FLAG_KEY) === '1') {
            return;
        }

        fetch('?url=Check/runAgentInitial', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function () {
            localStorage.setItem(FLAG_KEY, '1');
        })
        .catch(function (err) {
            console.error('runAgentInitial failed:', err);
        });
    });
})();
</script>
