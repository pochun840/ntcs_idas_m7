<?php
    // chart=6 視覺上歸到 chart=4 的按鈕
    // chart=7 視覺上歸到 chart=2 的按鈕
    $cm  = (string)($data['chart_mode'] ?? '');
    $map = ['6' => '4', '7' => '2'];
    $effective_mode = $map[$cm] ?? $cm;
?>

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/operation_m.css?v=".<?php echo  date('YmdHi'); ?> type="text/css">

<body>
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%">
                    <h3><?php echo  $data['text']['operation_result'];?></h3>
                </td>
                <td>
                    <img id="back_home" src="./img/btn_home.png" style="margin-right: 10px"  onclick="window.location.href = '?url=In';">
                </td>
            </tr>
        </table>
    </div>
    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="color: #fff;" for="job_name"><?php echo  $data['text']['job'];?>:</label>
                <input type="text" id="Job_Name" name="Job_Name" size="10" maxlength="20"  disabled>

                <label style="color: #fff;" for="seq_name"><?php echo  $data['text']['sequence'];?>:</label>
                <input type="text" id="Seq_Name" name="Seq_Name" size="10" maxlength="20"    disabled>

                <label style="color: #fff;" for="screw"><?php echo  $data['text']['screws'];?>:</label>
                <input type="text" id="Screws" name="Screws" size="4" maxlength="20"    disabled>
            </div>
            
            <div class="operation-setting">
                <div class="column">
                    <div class="item-target-torque w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red">
                             <?php echo $data['text']['final_torque']; ?>
                            (<span id="Torque_Unit_Label"><?php echo $data['chart_unit_label'] ?? ($data['text'][$data['data_info']['final_torque_unit'] ?? ''] ?? ''); ?></span>)
                        </div>
                        <div class="w3-display-middle torque-value-row">
                            <span id="Target_Torque" class="torque-main-value"><?php echo $data['data_info']['final_fasten_torque'] ?? '-'; ?></span>
                            <span id="Torque_Csv_Angle" class="torque-csv-angle" style="display:none;">
                                <?php echo $data['text']['angle'] ?? 'Angle'; ?>: <span id="Torque_Csv_Angle_Value">-</span>°
                            </span>
                        </div>
                    </div>

                    <?php
                        $color = $data['data_info']['result_status_color_text'] ?? '';
                        $bgStyle = $color ? "background-color: {$color}; color: black;" : '';
                    ?>


                    <div class="item-result w3-display-container" id='fasten_status_color'  style="<?php echo $bgStyle; ?>"  >
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black"><?php echo  $data['text']['final_result'];?></div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 4vmin; margin: 5px 0">     
                            <?php if(!empty($data['data_info'])){?>
                                <?php echo $data['text'][$data['data_info']['fasten_status_text']];?>
                            <?php }?>
                        </div>            
                    </div>
                </div>
                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo  $data['text']['final_angle'];?></div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 4vmin; margin: 5px 0"><?php echo $data['data_info']['total_fasten_angle'] ?? '-'; ?></div>                        
                    </div>
                    <div class="item-message w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo  $data['text']['final_message'];?></div>
                        <div id="Message" class="w3-display-middle" style="font-size: 5vmin; margin: 5px 0">  
                            <?php if(!empty($data['data_info'])){?>
                                <?php if ($data['data_info']['error_message']){?>
                                    <?php echo $data['data_info']['error_message']; ?>
                                <?php }?>
                            <?php }?>
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
            <!-- <div class="chart-setting">
                <div class="button-chart">
                    <//?php foreach($data['chart_menu_arr'] as $k_menu => $v_menu) {
                        $isActive = ($data['chart_mode'] == $k_menu) ? 'btn-chart active' : 'btn-chart';
                    ?>
                        <button type="button"
                                class="<//?php echo $isActive; ?>"
                                id="<//?php echo $v_menu['id']; ?>"
                                onclick="chart_type('<//?php echo $v_menu['id']; ?>')">
                            <//?php echo $data['text'][$v_menu['name']]; ?>
                        </button>
                    <//?php } ?>
                </div>

                <//?php if(!empty($data['chart_info'])){?>
                    <div id="graph" class="display-chart">
                        <div id="chart" style="max-width: 100%; height: 290px;"></div>
                    </div> 
                <//?php }?>
            </div> -->
        </div>
    </div>
</div>

<script>
    let chartMode = Number(<?php echo json_encode((int)($data['chart_mode'] ?? 1)); ?>);
    let myChart = null;
    let lastCsvSignature = null;
    let lastChartData = null;
    let latestOperationInfo = null; // 最新鎖附結果：判斷是否為目標角度
    let latestCsvRows = [];         // 最新曲線 CSV：用 Torque 找同一資料點的 Angle


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

    const palette = ['#0066ff', '#cc0000', '#009933', '#ff9900', '#6600cc', '#00cccc', '#cc00cc'];

    function colorForStep(step) {
        return palette[(Math.max(1, step) - 1) % palette.length];
    }


    // ================================
    // 目標角度鎖附：Torque 欄位旁顯示「該 Torque 在 CSV 同一資料點的 Angle」
    // 注意：右側原本的 Final Angle / Total Angle 欄位完全不修改。
    // CSV torque 以 N.m 儲存；比對前依目前畫面扭力單位換算。
    // ================================
    function isAngleTargetOperation(info) {
        if (!info) return false;

        if (Number(info.target_type) === 1) return true;

        const typeText = String(info.target_type ?? '').trim().toLowerCase();
        return typeText === 'angle' || typeText === 'target_angle';
    }

    function getRowsForCurrentStep(rows, info) {
        const source = (rows || []).filter(row =>
            Number.isFinite(Number(row.torque)) && Number.isFinite(Number(row.angle))
        );
        if (!source.length) return [];

        const stepId = Number(info?.step_id);
        if (!Number.isFinite(stepId)) return source;

        const sameStep = source.filter(row => Number(row.step) === stepId);
        return sameStep.length ? sameStep : source;
    }

    function findCsvAngleByDisplayedTorque(rows, info) {
        if (!isAngleTargetOperation(info)) return null;

        const finalTorque = Number(info?.final_fasten_torque);
        if (!Number.isFinite(finalTorque)) return null;

        const candidates = getRowsForCurrentStep(rows, info);
        if (!candidates.length) return null;

        const factor = getTorqueFactorFromNm();

        // Final Torque 通常是顯示後的數值，而 CSV torque 可能保留更多小數，
        // 因此找「數值上最接近 Final Torque」的那一筆，並取同一列 angle。
        // 若差距完全相同，取較後面的資料點（更接近鎖附結束）。
        let bestAngle = null;
        let bestDiff = Infinity;
        for (const row of candidates) {
            const displayTorque = convertTorqueFromNm(row.torque, factor);
            const angle = Number(row.angle);
            if (!Number.isFinite(displayTorque) || !Number.isFinite(angle)) continue;

            const diff = Math.abs(displayTorque - finalTorque);
            if (diff <= bestDiff) {
                bestDiff = diff;
                bestAngle = angle;
            }
        }

        return Number.isFinite(bestAngle) ? bestAngle : null;
    }

    function formatCsvAngleValue(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) return '-';

        if (Math.abs(n - Math.round(n)) < 0.000001) {
            return String(Math.round(n));
        }
        return n.toFixed(3).replace(/\.?0+$/, '');
    }

    function refreshTorqueCsvAngle() {
        const badge = document.getElementById('Torque_Csv_Angle');
        const valueEl = document.getElementById('Torque_Csv_Angle_Value');
        if (!badge || !valueEl) return;

        if (!isAngleTargetOperation(latestOperationInfo) || !latestCsvRows.length) {
            badge.style.display = 'none';
            valueEl.innerText = '-';
            return;
        }

        const csvAngle = findCsvAngleByDisplayedTorque(latestCsvRows, latestOperationInfo);
        if (csvAngle === null) {
            badge.style.display = 'none';
            valueEl.innerText = '-';
            return;
        }

        valueEl.innerText = formatCsvAngleValue(csvAngle);
        badge.style.display = 'inline-flex';
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

            latestOperationInfo = json.data_info;

            setValue("Job_Name", json.data_info.job_name || "");
            setValue("Seq_Name", json.data_info.sequence_name || "");
            setValue("Screws", json.data_info.total_screw_count || "");

            setText("Target_Torque", json.data_info.final_fasten_torque ?? "-");
            setText("Target_Angle", json.data_info.total_fasten_angle ?? "-");
            setText("Torque_Result", json.text?.[json.data_info.fasten_status_text] ?? "");
            setText("Message", json.data_info.error_message || "");

            let torqueUnitChanged = false;
            const nextTorqueUnit = pickTorqueUnitLabel(json);
            if (nextTorqueUnit) {
                torqueUnitChanged = setChartTorqueUnitLabel(nextTorqueUnit);
            }

            if (torqueUnitChanged && lastChartData) {
                renderChart(chartMode, lastChartData);
            }

            // 只更新 Torque 旁的 CSV Angle；右側 Target_Angle 維持原值。
            refreshTorqueCsvAngle();

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
            const currentUnitKey = getCurrentTorqueUnitKey();
            if (sig === lastCsvSignature && lastChartData && currentUnitKey === lastRenderedTorqueUnitKey) return;
            lastCsvSignature = sig;

            const rows = parseCSV(text);
            if (!rows.length) return;

            latestCsvRows = rows;
            refreshTorqueCsvAngle();

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

        if (chartMode === 1) {
            const group = groupByStep(timeX, tor, step);
            option = {
                animation: false,
                tooltip: { trigger: "axis" },
                grid: { left: 45, right: 20, top: 30, bottom: 45, containLabel: true },
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

        else if (chartMode === 5) {
            const group = groupByStep(timeX, tor, step);
            option = {
                animation: false,
                tooltip: { trigger: "axis" },
                grid: { left: 45, right: 45, top: 30, bottom: 45, containLabel: true },
                xAxis: { type: "value", name: "Time" },
                yAxis: [
                    getTorqueYAxisOption(),
                    getRpmYAxisOption(rpm)
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

        lastRenderedTorqueUnitKey = getCurrentTorqueUnitKey();
        myChart.clear();
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
        color: #000 !important;
        font-size: 14px !important;
        line-height: 1.4 !important;
        opacity: 1 !important;
        visibility: visible !important;
        white-space: nowrap;
        vertical-align: middle;
    }

    .angle-radio-label span {
        display: inline-block !important;
        color: #000 !important;
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