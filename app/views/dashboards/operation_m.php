<?php
    // chart=6 視覺上歸到 chart=4 的按鈕
    // chart=7 視覺上歸到 chart=2 的按鈕
    $cm  = (string)($data['chart_mode'] ?? '');
    $map = ['6' => '4', '7' => '2'];
    $effective_mode = $map[$cm] ?? $cm;
?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/operation_m.css?v=<?php echo date('YmdHi'); ?>" type="text/css">

<body>
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%">
                    <h3><?php echo $data['text']['operation_result'] ?? 'Operation Result'; ?></h3>
                </td>
                <td>
                    <img id="back_home"
                         src="./img/btn_home.png"
                         style="margin-right: 10px"
                         onclick="window.location.href='?url=In';">
                </td>
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="color: #fff;" for="Job_Name">
                    <?php echo $data['text']['job_name'] ?? ($data['text']['job'] ?? 'Job'); ?>:
                </label>
                <input type="text" id="Job_Name" name="Job_Name" size="10" maxlength="20" disabled>

                <label style="color: #fff;" for="Seq_Name">
                    <?php echo $data['text']['seq_name'] ?? ($data['text']['sequence'] ?? 'Sequence'); ?>:
                </label>
                <input type="text" id="Seq_Name" name="Seq_Name" size="10" maxlength="20" disabled>

                <label style="color: #fff;" for="Screws">
                    <?php echo $data['text']['screws'] ?? 'Screws'; ?>:
                </label>
                <input type="text" id="Screws" name="Screws" size="4" maxlength="20" disabled>
            </div>

            <div class="operation-setting">
                <div class="column">
                    <div class="item-target-torque w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red">
                            <?php echo $data['text']['final_torque'] ?? 'Final Torque'; ?>
                            (
                            <span id="Torque_Unit_Label">
                                <?php echo $data['text'][$data['chart_unit_name'] ?? ($data['data_info']['final_torque_unit'] ?? '')] ?? ''; ?>
                            </span>
                            )
                        </div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 4vmin; margin: 5px 0;">
                            <?php echo $data['data_info']['final_fasten_torque'] ?? '-'; ?>
                        </div>
                    </div>

                    <?php
                        $color = $data['data_info']['result_status_color_text'] ?? '';
                        $bgStyle = $color ? "background-color: {$color}; color: black;" : '';
                    ?>

                    <div class="item-result w3-display-container" id="fasten_status_color" style="<?php echo $bgStyle; ?>">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black">
                            <?php echo $data['text']['final_result'] ?? 'Final Result'; ?>
                        </div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 4vmin; margin: 5px 0;">
                            <?php if (!empty($data['data_info'])): ?>
                                <?php echo $data['text'][$data['data_info']['fasten_status_text'] ?? ''] ?? ''; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red">
                            <?php echo $data['text']['final_angle'] ?? 'Final Angle'; ?>
                        </div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 4vmin; margin: 5px 0;">
                            <?php echo $data['data_info']['total_fasten_angle'] ?? '-'; ?>
                        </div>
                    </div>

                    <div class="item-message w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red">
                            <?php echo $data['text']['final_message'] ?? 'Final Message'; ?>
                        </div>
                        <div id="Message" class="w3-display-middle" style="font-size: 4vmin; margin: 5px 0;">
                            <?php echo $data['data_info']['error_message'] ?? ''; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chart-setting">
                <div class="button-chart">
                    <?php foreach (($data['chart_menu_arr'] ?? []) as $k_menu => $v_menu): ?>
                        <button type="button"
                                class="btn-chart <?php echo ((string)$effective_mode === (string)$k_menu) ? 'active' : ''; ?>"
                                id="<?php echo $v_menu['id']; ?>"
                                onclick="chart_type('<?php echo $v_menu['id']; ?>')">
                            <?php echo $data['text'][$v_menu['name']] ?? $v_menu['name']; ?>
                        </button>
                    <?php endforeach; ?>

                    <div id="angleMode46" class="angle-switch-wrap" style="display:none;">
                        <label class="angle-radio-label">
                            <input type="radio" name="chartType46" value="4" onclick="selectAngleMode46(4)">
                            <em class="angle-radio-text"><?php echo $data['text']['Total_angle'] ?? 'Total Angle'; ?></em>
                        </label>

                        <label class="angle-radio-label">
                            <input type="radio" name="chartType46" value="6" onclick="selectAngleMode46(6)">
                            <em class="angle-radio-text"><?php echo $data['text']['Step_angle'] ?? 'Step Angle'; ?></em>
                        </label>
                    </div>

                    <div id="angleSwitch" class="angle-switch-wrap" style="display:none;">
                        <label class="angle-radio-label">
                            <input type="radio" name="chartType" value="7" onclick="chart_type('total_angle')">
                            <em class="angle-radio-text"><?php echo $data['text']['Total_angle'] ?? 'Total Angle'; ?></em>
                        </label>

                        <label class="angle-radio-label">
                            <input type="radio" name="chartType" value="2" onclick="chart_type('angle_time')">
                            <em class="angle-radio-text"><?php echo $data['text']['Step_angle'] ?? 'Step Angle'; ?></em>
                        </label>
                    </div>

                </div>

                <div id="graph" class="display-chart">
                    <div id="chart"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let chartMode = Number(<?php echo json_encode((int)($data['chart_mode'] ?? 1)); ?>);
let myChart = null;
let lastCsvSignature = null;
let lastChartData = null;

const palette = ['#0066ff', '#cc0000', '#009933', '#ff9900', '#6600cc', '#00cccc', '#cc00cc'];

function colorForStep(step) {
    return palette[(Math.max(1, step) - 1) % palette.length];
}

function getEffectiveMode(mode) {
    mode = Number(mode);
    if (mode === 6) return 4;
    if (mode === 7) return 2;
    return mode;
}

function initChart() {
    const dom = document.getElementById("chart");
    if (!dom) return;

    if (!myChart) {
        myChart = echarts.init(dom);
    } else {
        myChart.resize();
    }
}

document.addEventListener("DOMContentLoaded", function () {
    initChart();
    updateDataInfo();
    updateChartFromCSV();
    updateChartMenuActive();
    updateAngleSwitchUI();
});

window.addEventListener("resize", function () {
    if (myChart) myChart.resize();
});

function setValue(id, value) {
    const el = document.getElementById(id);
    if (el) el.value = value || "";
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.innerText = value ?? "";
}

async function updateDataInfo() {
    try {
        const res = await fetch("?url=Dashboards/operation&ajax=1&t=" + Date.now());
        const json = await res.json();
        if (!json || !json.data_info) return;

        setValue("Job_Name", json.data_info.job_name || "");
        setValue("Seq_Name", json.data_info.sequence_name || "");
        setValue("Screws", json.data_info.total_screw_count || "");

        setText("Target_Torque", json.data_info.final_fasten_torque ?? "-");
        setText("Target_Angle", json.data_info.total_fasten_angle ?? "-");
        setText("Torque_Result", json.text?.[json.data_info.fasten_status_text] ?? "");
        setText("Message", json.data_info.error_message || "");

        if (json.chart_unit_label) {
            setText("Torque_Unit_Label", json.chart_unit_label);
        }

        const statusBox = document.getElementById("fasten_status_color");
        if (statusBox && json.data_info.result_status_color_text) {
            statusBox.style.backgroundColor = json.data_info.result_status_color_text;
            statusBox.style.color = "black";
        }
    } catch (e) {
        console.warn("updateDataInfo error", e);
    }
}

function hashString(str) {
    let hash = 0x811c9dc5;
    for (let i = 0; i < str.length; i++) {
        hash ^= str.charCodeAt(i);
        hash = (hash * 0x01000193) >>> 0;
    }
    return hash.toString(16);
}

function parseCSV(text) {
    const lines = text.trim().split(/\r?\n/);
    if (!lines.length) return [];

    const header = lines[0].split(",").map(s => s.trim());
    const idx = {
        time: header.indexOf("time"),
        torque: header.indexOf("torque"),
        angle: header.indexOf("angle"),
        rpm: header.indexOf("rpm"),
        step: header.indexOf("step"),
    };

    return lines.slice(1).filter(line => line.trim() !== '').map(line => {
        const col = line.split(",");
        return {
            time: Number(col[idx.time] ?? 0),
            torque: Number(col[idx.torque] ?? 0),
            angle: Number(col[idx.angle] ?? 0),
            rpm: Number(col[idx.rpm] ?? 0),
            step: Number(col[idx.step] ?? 1),
        };
    });
}

function buildChartData(rows) {
    return {
        time: rows.map(r => r.time),
        torque: rows.map(r => r.torque),
        angle: rows.map(r => r.angle),
        rpm: rows.map(r => r.rpm),
        step: rows.map(r => r.step),
    };
}

function groupByStep(xarr, yarr, stepArr) {
    const map = new Map();
    for (let i = 0; i < xarr.length; i++) {
        const s = stepArr[i] || 1;
        if (!map.has(s)) map.set(s, []);
        map.get(s).push([xarr[i], yarr[i]]);
    }
    return map;
}

async function updateChartFromCSV() {
    try {
        initChart();
        if (!myChart) return;

        const resInfo = await fetch("?url=Dashboards/get_latest_csv&t=" + Date.now());
        const info = await resInfo.json();

        if (!info || !info.status) return;
        if (info.stable === false) return;

        const csvPath = info.csv;
        if (!csvPath) return;

        const res = await fetch(csvPath + "?t=" + Date.now());
        const text = await res.text();
        if (!text.trim()) return;

        const sig = hashString(text);
        if (sig === lastCsvSignature && lastChartData) return;
        lastCsvSignature = sig;

        const rows = parseCSV(text);
        if (!rows.length) return;

        const chartData = buildChartData(rows);
        lastChartData = chartData;

        renderChart(chartMode, chartData);
    } catch (e) {
        console.error("updateChartFromCSV error", e);
    }
}

function renderChart(mode, data) {
    initChart();
    if (!myChart || !data) return;

    chartMode = Number(mode);

    const time = data.time || [];
    const tor  = data.torque || [];
    const ang  = data.angle || [];
    const rpm  = data.rpm || [];
    const step = data.step || [];

    if (!time.length) return;

    let timeX = [...time];
    if (timeX.length && Math.max(...timeX) <= 5) {
        timeX = timeX.map(v => v * 1000);
    }

    let option = {};

    if (chartMode === 1) {
        const group = groupByStep(timeX, tor, step);
        option = {
            animation: false,
            tooltip: { trigger: "axis" },
            grid: { left: 45, right: 20, top: 30, bottom: 45, containLabel: true },
            xAxis: { type: "value", name: "Time" },
            yAxis: { type: "value", name: "Torque" },
            series: [...group.entries()].map(([s, arr]) => ({
                name: `Step${s}`,
                type: "line",
                data: arr,
                smooth: true,
                showSymbol: false,
                color: colorForStep(s)
            }))
        };
    }

    else if (chartMode === 2) {
        const BREAK = 300;
        let series = [];
        let buf = [];
        let prevStep = step[0];
        let prevAngle = ang[0];

        for (let i = 0; i < timeX.length; i++) {
            const s = step[i];
            const a = ang[i];

            if (i > 0 && s !== prevStep) {
                if (buf.length) series.push({ step: prevStep, data: buf });
                buf = [];
            } else if (i > 0 && Math.abs(a - prevAngle) > BREAK) {
                buf.push([timeX[i], null]);
            }

            buf.push([timeX[i], a]);
            prevStep = s;
            prevAngle = a;
        }

        if (buf.length) series.push({ step: prevStep, data: buf });

        option = {
            animation: false,
            tooltip: { trigger: "axis" },
            grid: { left: 45, right: 20, top: 30, bottom: 45, containLabel: true },
            xAxis: { type: "value", name: "Time" },
            yAxis: { type: "value", name: "Angle" },
            series: series.map(seg => ({
                name: `Step${seg.step}`,
                type: "line",
                data: seg.data,
                smooth: true,
                showSymbol: false,
                color: colorForStep(seg.step)
            }))
        };
    }

    else if (chartMode === 3) {
        const BREAK = 200;
        let series = [];
        let buf = [];
        let prevStep = step[0];
        let prevRPM = rpm[0];
        const lastStep = [...new Set(step)].pop();

        for (let i = 0; i < timeX.length; i++) {
            const s = step[i];
            const r = rpm[i];

            if (i > 0 && s !== prevStep) {
                if (buf.length) series.push({ step: prevStep, data: buf });
                buf = [];
            } else if (i > 0 && s !== lastStep && Math.abs(r - prevRPM) > BREAK) {
                buf.push([timeX[i], null]);
            }

            buf.push([timeX[i], r]);
            prevRPM = r;
            prevStep = s;
        }

        if (buf.length) series.push({ step: prevStep, data: buf });

        option = {
            animation: false,
            tooltip: { trigger: "axis" },
            grid: { left: 45, right: 20, top: 30, bottom: 45, containLabel: true },
            xAxis: { type: "value", name: "Time" },
            yAxis: { type: "value", name: "RPM" },
            series: series.map(seg => ({
                name: `Step${seg.step}`,
                type: "line",
                data: seg.data,
                smooth: true,
                showSymbol: false,
                color: colorForStep(seg.step)
            }))
        };
    }

    else if (chartMode === 4) {
        let offset = 0;
        let prev = step[0];
        const total = [];
        for (let i = 0; i < ang.length; i++) {
            if (i > 0 && step[i] !== prev) offset += ang[i - 1];
            total[i] = offset + ang[i];
            prev = step[i];
        }

        const group = groupByStep(total, tor, step);
        option = {
            animation: false,
            tooltip: { trigger: "axis" },
            grid: { left: 45, right: 20, top: 30, bottom: 45, containLabel: true },
            xAxis: { type: "value", name: "Angle" },
            yAxis: { type: "value", name: "Torque" },
            series: [...group.entries()].map(([s, arr]) => ({
                name: `Step${s}`,
                type: "line",
                data: arr,
                smooth: true,
                showSymbol: false,
                color: colorForStep(s)
            }))
        };
    }

    else if (chartMode === 5) {
        const group = groupByStep(timeX, tor, step);
        option = {
            animation: false,
            tooltip: { trigger: "axis" },
            grid: { left: 45, right: 45, top: 30, bottom: 45, containLabel: true },
            xAxis: { type: "value", name: "Time" },
            yAxis: [
                { type: "value", name: "Torque" },
                { type: "value", name: "RPM", min: 0, max: 700 }
            ],
            series: [
                ...[...group.entries()].map(([s, arr]) => ({
                    name: `Step${s}`,
                    type: "line",
                    data: arr,
                    smooth: true,
                    showSymbol: false,
                    color: colorForStep(s),
                    yAxisIndex: 0
                })),
                {
                    name: "RPM",
                    type: "line",
                    data: timeX.map((t, i) => [t, rpm[i]]),
                    smooth: true,
                    showSymbol: false,
                    color: "orange",
                    yAxisIndex: 1
                }
            ]
        };
    }

    else if (chartMode === 6) {
        const group = groupByStep(ang, tor, step);
        option = {
            animation: false,
            tooltip: { trigger: "axis" },
            grid: { left: 45, right: 20, top: 30, bottom: 45, containLabel: true },
            xAxis: { type: "value", name: "Angle" },
            yAxis: { type: "value", name: "Torque" },
            series: [...group.entries()].map(([s, arr]) => ({
                name: `Step${s}`,
                type: "line",
                data: arr,
                smooth: true,
                showSymbol: false,
                color: colorForStep(s)
            }))
        };
    }

    else if (chartMode === 7) {
        let offset = 0;
        let prev = step[0];
        const total = [];
        for (let i = 0; i < ang.length; i++) {
            if (i > 0 && step[i] !== prev) offset += ang[i - 1];
            total[i] = offset + ang[i];
            prev = step[i];
        }

        const group = groupByStep(timeX, total, step);
        option = {
            animation: false,
            tooltip: { trigger: "axis" },
            grid: { left: 45, right: 20, top: 30, bottom: 45, containLabel: true },
            xAxis: { type: "value", name: "Time" },
            yAxis: { type: "value", name: "Angle" },
            series: [...group.entries()].map(([s, arr]) => ({
                name: `Step${s}`,
                type: "line",
                data: arr,
                smooth: true,
                showSymbol: false,
                color: colorForStep(s)
            }))
        };
    }

    myChart.setOption(option, true);
    myChart.resize();
}

function chart_type(argument) {
    const chartMap = {
        "torque_time": 1,
        "angle_time": 2,
        "rpm_time": 3,
        "torque_angle": 4,
        "torque_speed": 5,
        "step_angle": 6,
        "total_angle": 7
    };

    if (!chartMap[argument]) return;

    chartMode = chartMap[argument];

    const newUrl = new URL(window.location.href);
    newUrl.searchParams.set("chart", chartMode);
    window.history.replaceState({}, '', newUrl);

    updateChartMenuActive();
    updateAngleSwitchUI();

    if (lastChartData) {
        renderChart(chartMode, lastChartData);
    } else {
        updateChartFromCSV();
    }
}

function updateChartMenuActive() {
    const effectiveMode = getEffectiveMode(chartMode);

    document.querySelectorAll('.btn-chart').forEach(btn => {
        btn.classList.remove('active');
    });

    const activeIdMap = {
        1: "torque_time",
        2: "angle_time",
        3: "rpm_time",
        4: "torque_angle",
        5: "torque_speed"
    };

    const activeBtnId = activeIdMap[effectiveMode];
    if (activeBtnId) {
        const activeBtn = document.getElementById(activeBtnId);
        if (activeBtn) activeBtn.classList.add('active');
    }
}

function selectAngleMode46(mode) {
    if (mode === 4) chart_type("torque_angle");
    else if (mode === 6) chart_type("step_angle");
}

function updateAngleSwitchUI() {
    const div46 = document.getElementById("angleMode46");
    const div27 = document.getElementById("angleSwitch");

    if (div46) div46.style.display = "none";
    if (div27) div27.style.display = "none";

    const effectiveMode = getEffectiveMode(chartMode);

    if (effectiveMode === 4) {
        if (div46) {
            div46.style.display = "block";
            const want = String(chartMode === 6 ? 6 : 4);
            const input = document.querySelector(`input[name="chartType46"][value="${want}"]`);
            if (input) input.checked = true;
        }
    }

    if (effectiveMode === 2) {
        if (div27) {
            div27.style.display = "block";
            const want = String(chartMode === 7 ? 7 : 2);
            const input = document.querySelector(`input[name="chartType"][value="${want}"]`);
            if (input) input.checked = true;
        }
    }
}

setInterval(() => {
    updateDataInfo();
    updateChartFromCSV();
}, 1000);
</script>

<style>
#graph {
    position: relative;
    width: 100%;
    min-height: 290px;
}

#chart {
    position: relative;
    z-index: 1;
    width: 100% !important;
    height: 290px !important;
}

.display-chart {
    width: 100%;
    overflow: hidden;
}

.button-chart {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    width: 100%;
    position: relative;
    z-index: 5;
}

.angle-switch-wrap {
    display: none;
    width: 100%;
    margin-top: 8px;
    margin-bottom: 8px;
    text-align: center;
    color: #fff !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
    position: relative;
    z-index: 10;
}

.angle-radio-label {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center;
    gap: 6px;
    margin: 0 10px;
    color: #fff !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
    opacity: 1 !important;
    visibility: visible !important;
    white-space: nowrap;
    vertical-align: middle;
}

.angle-radio-label span {
    display: inline-block !important;
    color: #fff !important;
    font-size: 14px !important;
    line-height: 1.4 !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.angle-switch-wrap input[type="radio"] {
    transform: scale(1.05);
    flex: 0 0 auto;
    margin: 0;
}

@media screen and (max-width: 768px) {
    #graph {
        min-height: 260px;
    }

    #chart {
        height: 260px !important;
    }

    .angle-switch-wrap {
        font-size: 12px !important;
    }

    .angle-radio-label {
        font-size: 12px !important;
        margin: 0 8px;
    }

    .angle-radio-label span {
        font-size: 12px !important;
    }
}
</style>