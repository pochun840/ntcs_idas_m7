<?php
    // chart=6 視覺上歸到 chart=4 的按鈕
    // chart=7 視覺上歸到 chart=2 的按鈕
    $cm  = (string)($data['chart_mode'] ?? '');
    $map = ['6' => '4', '7' => '2'];
    $effective_mode = $map[$cm] ?? $cm;
?>

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/operation.css?v=".<?php echo  date('YmdHi'); ?> type="text/css">
<body>
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $data['text']['operation_result'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>
    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:18px;color: #fff; padding-left: 1%" for="job_name"><?php echo $data['text']['job_name'];?> :</label>&nbsp;
                <input type="text" id="Job_Name" name="Job_Name" size="15" maxlength="20"  disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="seq_name"><?php echo $data['text']['seq_name'];?> :</label>&nbsp;
                <input type="text" id="Seq_Name" name="Seq_Name" size="15" maxlength="20"  disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="screw"><?php echo $data['text']['screws'];?> :</label>&nbsp;
                <input type="text" id="Screws" name="Screws" size="4" maxlength="20" value="<?php echo $data['data_info']['total_screw_count'] ?? '***';?>" disabled>
            </div>
           
            <!-- FINAL TORQUE / RESULT / FINAL ANGLE / MESSAGE -->
            <div class="operation-setting">
                <div class="column">
                    <div class="item-target-torque w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red">
                           <?php echo $data['text']['final_torque']; ?>
                            (<span id="Torque_Unit_Label">
                                <?php echo $data['text'][$data['chart_unit_name'] ?? ''] ?? ''; ?>
                            </span>)
                        </div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 4vmin">
                            <?php echo $data['data_info']['final_fasten_torque'] ?? '-'; ?>
                        </div>
                    </div>

                    <?php
                        $color = $data['data_info']['result_status_color_text'] ?? '';
                        $bgStyle = $color ? "background-color: {$color}; color: black;" : '';
                    ?>

                    <div class="item-result w3-display-container" id="fasten_status_color" style="<?php echo $bgStyle; ?>">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black">
                            <?php echo $data['text']['final_result']; ?>
                        </div>

                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 4vmin">
                            <?php if(!empty($data['data_info'])){ ?>
                                <?php echo $data['text'][$data['data_info']['fasten_status_text']] ?? ''; ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>

                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red">
                            <?php echo $data['text']['final_angle']; ?>
                        </div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 4vmin">
                            <?php echo $data['data_info']['total_fasten_angle'] ?? '-'; ?>
                        </div>
                    </div>

                    <div class="item-message w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red">
                            <?php echo $data['text']['final_message']; ?>
                        </div>
                        <div id="Message" class="w3-display-middle" style="font-size: 4vmin">
                            <?php echo $data['data_info']['error_message'] ?? ''; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="operation-setting">
                <div class="column">
                    <div class="item-chart">

                        <div class="button-chart">
                            <?php foreach($data['chart_menu_arr'] as $k_menu => $v_menu) { ?>
                                <button 
                                    type="button"
                                    class="btn-chart <?php echo ((string)$effective_mode === (string)$k_menu) ? 'active' : ''; ?>"
                                    id="<?php echo $v_menu['id']; ?>"
                                    onclick="chart_type('<?php echo $v_menu['id']; ?>')">
                                    <?php echo isset($data['text'][$v_menu['name']])
                                            ? $data['text'][$v_menu['name']]
                                            : $v_menu['name']; ?>
                                </button>
                            <?php } ?>

                            <div id="angleMode46" style="display:none; white-space: nowrap; margin-top:6px;">
                                <label>
                                    <input type="radio" name="chartType46" value="4" onclick="selectAngleMode46(4)">
                                    <?php echo $data['text']['Total_angle']; ?>
                                </label>

                                <label style="margin-left: 10px;">
                                    <input type="radio" name="chartType46" value="6" onclick="selectAngleMode46(6)">
                                    <?php echo $data['text']['Step_angle']; ?>
                                </label>
                            </div>

                            <div id="angleSwitch" style="display:none; white-space: nowrap; margin-top:6px;">
                                <label>
                                    <input type="radio" name="chartType" value="7" onclick="chart_type('total_angle')">
                                    <?php echo $data['text']['Total_angle']; ?>
                                </label>

                                <label style="margin-left: 10px;">
                                    <input type="radio" name="chartType" value="2" onclick="chart_type('angle_time')">
                                    <?php echo $data['text']['Step_angle']; ?>
                                </label>
                            </div>
                        </div>

                        <!-- 圖表 -->
                        <div id="graph" class="display-chart">
                            <div id="chart" style="width:100%; height:100%;"></div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // -----------------------------
    // 全域變數初始化
    // -----------------------------
    let chartMode = Number(<?php echo json_encode((int)($data['chart_mode'] ?? 1)); ?>);
    let myChart = null;

    let lastCsvSignature = null;   // CSV 內容 Hash
    let lastChartData = null;      // 快取圖表資料（防重抓）


    // ================================
    // 扭力座標軸單位：跟控制器 / iDAS 顯示單位同步
    // ================================
    let chartTorqueUnitLabel = <?php echo json_encode($data['chart_unit_label'] ?? ($data['chart_unit_name'] ?? 'N.m')); ?> || 'N.m';
    let lastRenderedTorqueUnitKey = null;

    function getDomTorqueUnitLabel() {
        const el = document.getElementById("Torque_Unit_Label");
        return el ? String(el.innerText || '').trim() : '';
    }

    function getCurrentTorqueUnitLabel() {
        // 優先使用目前畫面顯示的單位，避免 AJAX 欄位名稱或初始 PHP key 不一致時，只更新到 label、沒有更新到換算倍率。
        return getDomTorqueUnitLabel() || String(chartTorqueUnitLabel || '').trim() || 'N.m';
    }

    function getTorqueAxisName() {
        const unit = getCurrentTorqueUnitLabel();
        return unit ? `Torque (${unit})` : 'Torque';
    }

    function setChartTorqueUnitLabel(unitLabel) {
        const next = String(unitLabel || '').trim();
        if (!next) return false;

        const oldKey = normalizeTorqueUnit(getCurrentTorqueUnitLabel());
        chartTorqueUnitLabel = next;

        const unitSpan = document.getElementById("Torque_Unit_Label");
        if (unitSpan) unitSpan.innerText = next;

        const newKey = normalizeTorqueUnit(getCurrentTorqueUnitLabel());
        return oldKey !== newKey;
    }

    function pickTorqueUnitLabel(json) {
        if (!json) return '';

        const info = json.data_info || {};
        const text = json.text || {};
        const candidates = [
            json.chart_unit_label,
            json.chart_unit_name,
            info.chart_unit_label,
            info.chart_unit_name,
            info.final_torque_unit_label,
            text[info.final_torque_unit],
            info.final_torque_unit,
            getDomTorqueUnitLabel(),
            chartTorqueUnitLabel
        ];

        for (const v of candidates) {
            const s = String(v || '').trim();
            if (s) return s;
        }
        return 'N.m';
    }


    // ================================
    // 扭力曲線數值換算
    // CSV torque 來源維持 N.m；圖表顯示時依目前單位換算，避免 Y 軸單位已改但數值仍是 N.m。
    // ================================
    function normalizeTorqueUnit(unitLabel) {
        const unit = String(unitLabel || '')
            .trim()
            .toLowerCase()
            .replace(/[（）()]/g, '')
            .replace(/torque/g, '')
            .replace(/unit/g, '')
            .replace(/扭力/g, '')
            .replace(/單位/g, '')
            .replace(/单位/g, '')
            .replace(/[·・∙。．]/g, '.')
            .replace(/[-_\s]+/g, '.')
            .replace(/\.+/g, '.');

        // kgf.cm：繁中 公斤.公分 / 簡中 公斤.厘米
        if (
            /kgf\.?cm/.test(unit) ||
            /公斤\.?(公分|厘米)/.test(unit)
        ) {
            return 'kgf.cm';
        }

        // kgf.m：繁中 公斤.公尺 / 簡中 公斤.米
        if (
            /kgf\.?m/.test(unit) ||
            /公斤\.?(公尺|米)/.test(unit)
        ) {
            return 'kgf.m';
        }

        // Lbf.in：繁中 磅.英吋 / 簡中 磅.英寸
        if (
            /(lbf|lb)\.?in/.test(unit) ||
            /磅\.?(英吋|英寸)/.test(unit)
        ) {
            return 'lbf.in';
        }

        // cN.m：繁中 牛頓.釐米 / 簡中 牛顿.厘米
        if (
            /cn\.?m/.test(unit) ||
            /牛[頓顿]\.?(釐米|厘米|厘米)/.test(unit)
        ) {
            return 'cN.m';
        }

        // N.m：繁中 牛頓.公尺 / 簡中 牛顿.米
        if (
            /n\.?m/.test(unit) ||
            /牛[頓顿]\.?(公尺|米)/.test(unit)
        ) {
            return 'N.m';
        }

        return 'N.m';
    }


    function getCurrentTorqueUnitKey() {
        return normalizeTorqueUnit(getCurrentTorqueUnitLabel());
    }

    function getTorqueFactorFromNm() {
        switch (getCurrentTorqueUnitKey()) {
            case 'kgf.cm': return 10.19716213;
            case 'kgf.m':  return 0.1019716213;
            case 'lbf.in': return 8.850745767;
            case 'cN.m':   return 100;
            case 'N.m':
            default:       return 1;
        }
    }

    function getTorqueDecimalPlaces() {
        switch (getCurrentTorqueUnitKey()) {
            case 'kgf.cm': return 2;
            case 'lbf.in': return 2;
            case 'kgf.m':  return 4;
            case 'cN.m':   return 1;
            case 'N.m':
            default:       return 3;
        }
    }

    function convertTorqueFromNm(value, factor = getTorqueFactorFromNm()) {
        const n = Number(value);
        if (!Number.isFinite(n)) return null;
        return Number((n * factor).toFixed(6));
    }

    function convertTorqueArrayFromNm(values) {
        const factor = getTorqueFactorFromNm();
        return (values || []).map(v => convertTorqueFromNm(v, factor));
    }

    function formatTorqueAxisLabel(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) return value;
        return n.toFixed(getTorqueDecimalPlaces()).replace(/\.?0+$/, '');
    }

    function getTorqueYAxisOption(extra = {}) {
        return Object.assign({
            type: "value",
            name: getTorqueAxisName(),
            axisLabel: { formatter: formatTorqueAxisLabel }
        }, extra || {});
    }

    // -----------------------------
    // 初始化 ECharts
    // -----------------------------
    window.addEventListener("load", function () 
    {
        const dom = document.getElementById("chart");
        if (!myChart) {
            myChart = echarts.init(dom);
        }
        updateAngleSwitchUI();
    });

    // ================================
    // 更新上方資訊
    // ================================
    async function updateDataInfo() {
        try {
            const res = await fetch("?url=Dashboards/operation&ajax=1&t=" + Date.now());
            const json = await res.json();
            if (!json || !json.data_info) return;

            document.getElementById("Job_Name").value = json.data_info.job_name || "";
            document.getElementById("Seq_Name").value = json.data_info.sequence_name || "";
            document.getElementById("Screws").value = json.data_info.total_screw_count || "";
            document.getElementById("Target_Torque").innerText = json.data_info.final_fasten_torque ?? "-";
            document.getElementById("Target_Angle").innerText = json.data_info.total_fasten_angle ?? "-";
            document.getElementById("Torque_Result").innerText = json.text[json.data_info.fasten_status_text] ?? "";
            document.getElementById("Message").innerText = json.data_info.error_message || "";

            // ⭐ 即時更新扭力單位，並同步刷新圖表座標軸單位
            let torqueUnitChanged = false;
            const nextTorqueUnit = pickTorqueUnitLabel(json);
            if (nextTorqueUnit) {
                torqueUnitChanged = setChartTorqueUnitLabel(nextTorqueUnit);
            }

            if (torqueUnitChanged && lastChartData) {
                renderChart(chartMode, lastChartData);
            }

            if (json.data_info.result_status_color_text) {
                document.getElementById("fasten_status_color").style.backgroundColor =
                    json.data_info.result_status_color_text;
            }
        } catch (e) {
            console.warn("updateDataInfo error", e);
        }
    }

    // ================================
    // FNV-1a Hash：避免重畫
    // ================================
    function hashString(str) {
        let hash = 0x811c9dc5;
        for (let i = 0; i < str.length; i++) {
            hash ^= str.charCodeAt(i);
            hash = (hash * 0x01000193) >>> 0;
        }
        return hash.toString(16);
    }

    // ================================
    // CSV Parser
    // ================================
    function parseCSV(text) {
        const lines = text.trim().split("\n");
        if (!lines.length) return [];

        const header = lines[0].split(",").map(s => s.trim());
        const idx = {
            time: header.indexOf("time"),
            torque: header.indexOf("torque"),
            angle: header.indexOf("angle"),
            rpm: header.indexOf("rpm"),
            step: header.indexOf("step"),
        };

        return lines.slice(1).map(line => {
            const col = line.split(",");
            return {
                time: Number(col[idx.time]   ?? 0),
                torque: Number(col[idx.torque] ?? 0),
                angle: Number(col[idx.angle]  ?? 0),
                rpm: Number(col[idx.rpm]    ?? 0),
                step: Number(col[idx.step]   ?? 1)
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

    // ================================
    // CSV 穩定器 + 圖更新
    // ================================
    async function updateChartFromCSV() {
        try {
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
            const currentUnitKey = getCurrentTorqueUnitKey();
            if (sig === lastCsvSignature && lastChartData && currentUnitKey === lastRenderedTorqueUnitKey) return;
            lastCsvSignature = sig;

            const rows = parseCSV(text);
            const chartData = buildChartData(rows);
            lastChartData = chartData;

            renderChart(chartMode, chartData);
        } catch (e) {
            console.error("updateChartFromCSV error", e);
        }
    }

    // ================================
    // Step Color + 分組工具
    // ================================
    const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc','#00cccc','#cc00cc'];
    const colorForStep = s => palette[(s - 1) % palette.length];

    function groupByStep(xarr, yarr, stepArr) {
        const map = new Map();
        for (let i = 0; i < xarr.length; i++) {
            const s = stepArr[i] || 1;
            if (!map.has(s)) map.set(s, []);
            map.get(s).push([xarr[i], yarr[i]]);
        }
        return map;
    }

    function getMaxFinite(values) {
        let maxVal = 0;
        (values || []).forEach(v => {
            const n = Number(v);
            if (Number.isFinite(n) && n > maxVal) maxVal = n;
        });
        return maxVal;
    }

    function getNiceStepSize(target, desiredTicks = 6) {
        const safeTarget = Math.max(Number(target) || 0, 1);
        const rough = safeTarget / Math.max(1, desiredTicks);
        const exp = Math.floor(Math.log10(rough));
        const base = Math.pow(10, exp);
        const fraction = rough / base;

        if (fraction <= 1) return 1 * base;
        if (fraction <= 2) return 2 * base;
        if (fraction <= 5) return 5 * base;
        return 10 * base;
    }

    function getRpmAxisConfig(values, fallback = 700) {
        const maxRpm = getMaxFinite(values);

        if (!Number.isFinite(maxRpm) || maxRpm <= 0) {
            return { max: fallback, interval: 100 };
        }

        // 保留原本 700 的最低顯示範圍；若 CSV RPM 超過 700，依資料自動放大，並把座標軸修正為漂亮刻度。
        // 例如最大 RPM 約 900~1000 時，避免右側 Y 軸出現 1000 / 1100 這種尾端不整齊刻度。
        const target = Math.max(fallback, maxRpm * 1.10);
        let interval = getNiceStepSize(target, 6);

        // 原本 700 以內維持每 100 顯示一次。
        if (target <= fallback) {
            interval = 100;
        }

        let axisMax = Math.ceil(target / interval) * interval;

        if (axisMax < fallback) axisMax = fallback;

        // 避免 1100 這種不漂亮的最高刻度，改成 1200 並使用 200 間距。
        if (axisMax === 1100 && interval === 100) {
            interval = 200;
            axisMax = 1200;
        }

        return { max: axisMax, interval };
    }

    function formatRpmAxisLabel(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) return value;
        return String(Math.round(n));
    }

    function getRpmYAxisOption(values, extra = {}) {
        const cfg = getRpmAxisConfig(values);

        return Object.assign({
            type: "value",
            name: "RPM",
            min: 0,
            max: cfg.max,
            interval: cfg.interval,
            axisLabel: { formatter: formatRpmAxisLabel }
        }, extra || {});
    }

    // ================================
    // ⭐ 核心：renderChart（所有模式）
    // ================================
    function renderChart(mode, data) {

        chartMode = Number(mode);
        const time = data.time || [];
        const tor  = convertTorqueArrayFromNm(data.torque || []);
        const ang  = data.angle || [];
        const rpm  = data.rpm || [];
        const step = data.step || [];

        if (!time.length) return;

        let timeX = [...time];
        if (timeX.length && Math.max(...timeX) <= 5) {
            timeX = timeX.map(v => v * 1000);
        }

        let option = {};

        // ========== mode 1 ==========
        if (chartMode === 1) {
            const group = groupByStep(timeX, tor, step);

            option = {
                animation: false,
                tooltip: { trigger: "axis" },
                xAxis: { type: "value", name: "Time" },
                yAxis: getTorqueYAxisOption(),
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

        // ========== mode 2 ==========
        else if (chartMode === 2) {
            const BREAK = 300;
            let series = [];
            let buf = [];
            let prevStep = step[0];
            let prevAngle = ang[0];

            for (let i = 0; i < timeX.length; i++) {
                const s = step[i], a = ang[i];

                if (s !== prevStep) {
                    if (buf.length) series.push({ step: prevStep, data: buf });
                    buf = [];
                } else if (Math.abs(a - prevAngle) > BREAK) {
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

        // ========== mode 3 ==========
        else if (chartMode === 3) {
            const BREAK = 200;
            let series = [];
            let buf = [];
            let prevStep = step[0];
            let prevRPM = rpm[0];
            const lastStep = [...new Set(step)].pop();

            for (let i = 0; i < timeX.length; i++) {
                const s = step[i], r = rpm[i];

                if (s !== prevStep) {
                    if (buf.length) series.push({ step: prevStep, data: buf });
                    buf = [];
                } else if (s !== lastStep && Math.abs(r - prevRPM) > BREAK) {
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
                xAxis: { type: "value", name: "Time" },
                yAxis: getRpmYAxisOption(rpm),
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

        // ========== mode 4 ==========
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
                xAxis: { type: "value", name: "Angle" },
                yAxis: getTorqueYAxisOption(),
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

        // ========== mode 5 ==========
        else if (chartMode === 5) {
            const group = groupByStep(timeX, tor, step);
            option = {
                animation: false,
                tooltip: { trigger: "axis" },
                xAxis: { type: "value", name: "Time" },
                yAxis: [
                    getTorqueYAxisOption(),
                    getRpmYAxisOption(rpm),
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

        // ========== mode 6 ==========
        else if (chartMode === 6) {
            const group = groupByStep(ang, tor, step);
            option = {
                animation: false,
                tooltip: { trigger: "axis" },
                xAxis: { type: "value", name: "Angle" },
                yAxis: getTorqueYAxisOption(),
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

        // ========== mode 7 ==========
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

        lastRenderedTorqueUnitKey = getCurrentTorqueUnitKey();
        myChart.clear();
        myChart.setOption(option, true);
    }

    // ================================
    // ⭐ 強化版 chart_type（v4）
    // ================================
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

        const newUrl = new URL(window.location);
        newUrl.searchParams.set("chart", chartMode);
        window.history.replaceState({}, '', newUrl);

        updateChartMenuActive();
        updateAngleSwitchUI();

        // ⭐ 切換模式必須強制畫圖，不依賴 CSV 變化
        if (lastChartData) {
            renderChart(chartMode, lastChartData);
            return;
        }

        updateChartFromCSV();
    }

    function getEffectiveMode(mode) {
        mode = Number(mode);
        if (mode === 6) return 4;
        if (mode === 7) return 2;
        return mode;
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

    // ================================
    // ⭐ Angle Switch UI
    // ================================
    function selectAngleMode46(mode) {
        if (mode === 4) chart_type("torque_angle");
        else if (mode === 6) chart_type("step_angle");
    }

    function updateAngleSwitchUI() {

        const div46 = document.getElementById("angleMode46");
        const div27 = document.getElementById("angleSwitch");

        div46.style.display = "none";
        div27.style.display = "none";

        // chart_mode 4 / 6 → 顯示 angleMode46
        if (chartMode === 4 || chartMode === 6) {
            div46.style.display = "block";

            const want = (chartMode === 4 ? "4" : "6");
            const input = document.querySelector(`input[name="chartType46"][value="${want}"]`);
            if (input) input.checked = true;
        }

        // chart_mode 2 / 7 → 顯示 angleSwitch
        if (chartMode === 2 || chartMode === 7) {
            div27.style.display = "block";

            const want = (chartMode === 7 ? "7" : "2");
            const input = document.querySelector(`input[name="chartType"][value="${want}"]`);
            if (input) input.checked = true;
        }
    }

    // ================================
    // 啟動
    // ================================
    window.onload = function () {
        updateDataInfo();
        updateChartFromCSV();
        updateChartMenuActive();
        updateAngleSwitchUI();
    };

    setInterval(() => {
        updateDataInfo();
        updateChartFromCSV();
    }, 1000);

</script>