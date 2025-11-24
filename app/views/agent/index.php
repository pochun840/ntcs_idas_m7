<?php require APPROOT . 'views/inc/header.php'; ?>

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/w3.css" type="text/css">
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/agent.css" type="text/css">
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/balloon.min.css" type="text/css">

<?php
$now = new DateTime();      // 目前時間（依伺服器時區）
$now->modify('+8 hours');   // 往後加 8 小時

$dateStr = $now->format("Y/m/d");
$timeStr = $now->format("H : i");

?>
<div class="container">
    <div class="header">
        <div id="day" class="w3-right-align" style="font-size: 14px; margin-top: auto; margin: 10px">Date: <?php echo $dateStr;?></div>
        <div id="time" class="w3-right-align" style="font-size: 14px; margin-top: auto; margin: 0px 10px 0px 10px">Time: <?php echo $timeStr;?></div>
        <div style="margin-top: 1%">
            <h1><?php echo TITLE_AGENT; ?></h1>
        </div>
    </div>

    <div style="margin-top: 10px">
        <div id="menu">
            <a id="bnt1" onclick="OpenButton('agent')"><?php echo $text['agent_title'];?></a>
    
            <!--<a id="bnt2" onclick="OpenButton('button1')">Button2</a>
            <a id="bnt3" onclick="OpenButton('button2')">Button3</a>-->
        </div>

        <!-- Agent -->
        <div id="Agent_Display" class="agent-display" style="margin-top:18px">
            <div class="table-scroll" id="style-Agent">
                <table id="data-table" class="container2" role="table" aria-label="<?php echo htmlspecialchars($text['agent_title'] ?? 'Agent', ENT_QUOTES); ?>">
                <!-- 固定欄寬，避免抖動 -->
                <colgroup>
                    <col style="min-width:56px">   <!-- no -->
                    <col style="min-width:120px">  <!-- device_type -->
                    <col style="min-width:160px">  <!-- device_name -->
                    <col style="min-width:140px">  <!-- ip -->
                    <col style="min-width:200px">  <!-- datetime -->
                    <col style="min-width:80px">   <!-- job -->
                    <col style="min-width:80px">   <!-- seq -->
                    <col style="min-width:110px">  <!-- torque -->
                    <col style="min-width:110px">  <!-- unit -->
                    <col style="min-width:110px">  <!-- angle -->
                    <col style="min-width:90px">   <!-- count -->
                    <col style="min-width:90px">   <!-- total -->
                    <col style="min-width:160px">  <!-- status -->
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



        <!-- Button2 -->
        <div id="Button2_Display">

        </div>

        <!-- Button3 -->
        <div id="Button3_Display">

        </div>

        <div class="footer">
            <div class="w3-center">
                <!-- <button class="custom-btn btn-12" onclick="change_page('previous')"><span style="font-size: 24px">&#60;</span><span>Prev</span></button> -->
                <button type="button" class="custom-btn btn-13" onclick="open_das()"><span style="font-size: 24px">&#8629;</span><span><?php echo $text['open'];?></span></button>
                <!-- <button class="custom-btn btn-14" onclick="change_page('next')"><span style="font-size: 24px">&#62;</span><span>Next</span></button> -->
                <button class="custom-btn btn-15" onclick="window.location.href='?url=Dashboards'"><span style="font-size: 24px">&#8678;</span><span><?php echo $text['return'];?></span></button>
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
        return Number.isInteger(n) && n>=0 && n<FASTEN_STATUS.length ? FASTEN_STATUS[n] : String(idx ?? "");
    }
    function torqueUnitName(idx){
        const n = Number(idx);
        return Number.isInteger(n) && n>=0 && n<TORQUE_UNIT.length ? TORQUE_UNIT[n] : String(idx ?? "");
    }

    /** ---------- DataTable 初始化 ---------- */
    const DEVICE_TYPE_7 = <?php echo json_encode(DEVICE_TYPE_7); ?>;

    const table2 = $('#data-table').DataTable({
        autoWidth: false,
        searching: false,
        info: false,
        ordering: false,
        dom: "frti",
        pageLength: 99,
        language: { zeroRecords: " " },
        rowId: 'client_ip', 

        // ★ WebSocket / redraw 都會重新用 render() 套 tooltip，所以不需要 createdRow

        columns: [
            {
                data: null,
                width:"5%",
                render: (d, t, r, meta) => {
                    const n = meta.row + 1;
                    return `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${String(n).padStart(2, '0')}">${String(n).padStart(2, '0')}</span>`;
                }
            },
            {
                data: 'device_type_name', width: "10%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'device_name', width: "14%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'client_ip', width: "10%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'data_time', width: "20%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'job_id', width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'sequence_id', width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'final_fasten_torque', width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'torque_unit_name', width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'final_fasten_angle', width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'last_screw_count', width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'total_screw_count', width: "6%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            },
            {
                data: 'fasten_status_name', width: "15%",
                render: d => `<span data-bs-toggle="tooltip" data-bs-placement="top" title="${d || ''}">${d || ''}</span>`
            }
        ]
    });

    // ★★★ 初始化 Bootstrap Tooltip（需要每次 redraw 時都重新啟動）
    function initTooltips(){
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    table2.on('draw', function () {
        initTooltips();
    });




    // 列單選高亮
    $('#data-table tbody').on('click','tr', function(){
        if ($(this).hasClass('selected')) $(this).removeClass('selected');
        else {
        table2.$('tr.selected').removeClass('selected');
        $(this).addClass('selected');
        }
    });

    /** ---------- WebSocket upsert ---------- */
    let socket;
    const server_ip  = <?php echo json_encode($data['agent_server_ip']); ?>;
    const serverUrl  = `ws://${server_ip}:9501`;

    function upsertRow(payload){
        
        const rowData = {
            device_type_name: DEVICE_TYPE_7,
            device_name: payload.device_name ?? '',
            client_ip: payload.client_ip ?? '',
            data_time: payload.data_time ?? '',
            job_id: payload.job_id ?? '',
            sequence_id: payload.sequence_id ?? '',
            final_fasten_torque: payload.final_fasten_torque ?? '',
            torque_unit_name: torqueUnitName(payload.torque_unit),
            final_fasten_angle: payload.final_fasten_angle ?? '',
            last_screw_count: payload.last_screw_count ?? '',
            total_screw_count: payload.total_screw_count ?? '',
            fasten_status_name: fastenStatusName(payload.fasten_status)
        };
        if (!rowData.client_ip) return;

        // 依主鍵 upsert
        const rowApi = table2.row(function(_, d){ return d.client_ip === rowData.client_ip; });
        if (rowApi.any()){
            const merged = Object.assign({}, rowApi.data(), rowData);
            rowApi.data(merged).draw(false);
        } else {
            table2.row.add(rowData).draw(false);
        }

        // 🔵 動態更新動畫
        const rowNode = rowApi.node();
        $(rowNode).addClass('row-flash');
        setTimeout(() => $(rowNode).removeClass('row-flash'), 400);




    }

    function handleWebSocketMessage(event){
        // 支援 "Client X said: {...}" 格式
        const m = /^Client \d+ said:\s*(\{.*\})$/.exec(event.data);
        const jsonStr = m ? m[1] : event.data;
        try{
        const payload = JSON.parse(jsonStr);
        upsertRow(payload);
        }catch(err){
        console.error("JSON parse error:", err, "raw:", event.data);
        }
    }

    function connectWebSocket(){
        try{
        socket = new WebSocket(serverUrl);
        }catch(e){
        console.error('WebSocket 無法建立：', e);
        setTimeout(connectWebSocket, 5000);
        return;
        }

        socket.addEventListener('open', ()=> {
        console.log('WebSocket 連線成功');
        });

        socket.addEventListener('message', handleWebSocketMessage);

        socket.addEventListener('close', ()=> {
        console.log('WebSocket 關閉，5 秒後重連...');
        setTimeout(connectWebSocket, 5000);
        });

        socket.addEventListener('error', (e)=> {
        console.error('WebSocket 錯誤', e);
        try{ socket.close(); }catch(_){}
        });
    }

    /** ---------- 對外：開啟 DAS ---------- */
    window.open_das = function(){
        const d = table2.row('.selected').data();
        const ip = d?.client_ip;
        if (ip) window.open(`http://${ip}/das/public/`, '_blank');
    };

    /** ---------- 啟動 ---------- */
    $(document).ready(function(){
        connectWebSocket();
    });

    })();
</script>



<?php require APPROOT . 'views/inc/footer.php'; ?>

<style>
    .agent-display .table-scroll{
        position: relative;
        max-height: calc(100vh - 260px); /* 視頁面排版調整 */
        overflow: auto;
    }

    #data-table{
        width: 100%;
        table-layout: fixed; /* 配合 colgroup，防抖動 */
        border-collapse: separate;
        border-spacing: 0;
    }

    #data-table th, #data-table td{
        padding: 8px 10px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* 黏住表頭 */
    #data-table thead.sticky th{
        position: sticky;
        top: 0;
        z-index: 2;
        background: #1f2937; /* 深灰 */
        color: #fff;
    }

    /* 響應式：窄螢幕隱藏次要欄位（可依需求調整） */
    @media (max-width: 1200px){
        #data-table .col-count, #data-table .col-total { display: none; }
    }
    @media (max-width: 992px){
        #data-table .col-angle, #data-table .col-unit { display: none; }
    }
    @media (max-width: 768px){
        #data-table .col-job, #data-table .col-seq, #data-table .col-type { display: none; }
    }

    /* 沿用你的空表訊息隱藏 */
    .dataTables_empty{ display:none; }

    #data-table .col-ip {
        cursor: help;
    }
</style>