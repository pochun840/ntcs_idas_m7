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
            
            <div class="operation-setting">
                <div class="column">
                    <div class="item-target-torque w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red">
                            <?php echo $data['text']['final_torque']; ?>
                            (<?php echo $data['text'][$data['data_info']['final_torque_unit'] ?? ''] ?? ''; ?>)
                        </div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 4vmin"><?php echo $data['data_info']['final_fasten_torque'] ?? '-'; ?></div>
                    </div>

                    <?php
                        $color = $data['data_info']['result_status_color_text'] ?? '';
                        $bgStyle = $color ? "background-color: {$color}; color: black;" : '';
                    ?>

                    <div class="item-result w3-display-container" id='fasten_status_color'  style="<?php echo $bgStyle; ?>" >
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black"><?php echo $data['text']['final_result'];?></div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 4vmin">
                            <?php if(!empty($data['data_info'])){?>
                            <?php  echo $data['text'][$data['data_info']['fasten_status_text']];?>
                            <?php }?>
                        </div>            
                    </div>
                </div>
                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $data['text']['final_angle'];?></div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 4vmin"><?php echo $data['data_info']['total_fasten_angle'] ?? '-'; ?></div>                        
                    </div>
                    <div class="item-message w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $data['text']['final_message'];?></div>
                        <div id="Message" class="w3-display-middle" style="font-size: 4vmin">
                            <?php if(!empty($data['data_info'])){?>
                                <?php if ($data['data_info']['error_message']){?>
                                    <?php echo $data['data_info']['error_message']; ?>
                                <?php }?>
                            <?php }?>
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
                                    <?php echo isset($data['text'][$v_menu['name']]) ? $data['text'][$v_menu['name']] : $v_menu['name']; ?>
                                </button>
                            <?php } ?>

                            <?php if ($data['chart_mode'] == "4" || $data['chart_mode'] == "6") { ?>
                                <div style="white-space: nowrap;">
                                    <label>
                                        <input type="radio" name="chartType" value="4" <?php echo ($data['chart_mode'] == "4") ? 'checked' : ''; ?>> <?php echo $data['text']['Total_angle'];?>
                                    </label>
                                    <label style="margin-left: 10px;">
                                        <input type="radio" name="chartType" value="6" <?php echo ($data['chart_mode'] == "6") ? 'checked' : ''; ?>> <?php echo $data['text']['Step_angle'];?>
                                    </label>
                                </div>
                            <?php } ?>
                            
                            <?php if ($data['chart_mode'] == "2" || $data['chart_mode'] == "7") { ?>
                                <div style="white-space: nowrap;">
                                    <label>
                                        <input type="radio" name="chartType" value="7" <?php echo ($data['chart_mode'] == "7") ? 'checked' : ''; ?>> <?php echo $data['text']['Total_angle'];?>
                                    </label>
                                    <label style="margin-left: 10px;">
                                        <input type="radio" name="chartType" value="2" <?php echo ($data['chart_mode'] == "2") ? 'checked' : ''; ?>> <?php echo $data['text']['Step_angle'];?>
                                    </label>
                                </div>
                            <?php } ?>

                        </div>
                        
                            <?php if(!empty($data['chart_info'])){?>
                                <div id="graph" class="display-chart">
                                    <div id="chart" style="width: 100%; height: 100%"></div>
                                </div>   
                            <?php }?>
            
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('input[name="chartType"]').forEach(radio => {
        radio.addEventListener('change', function () {
            if (this.checked) {
                const url = new URL(window.location);
                url.searchParams.set('chart', this.value); // ✅ 用 radio 的 value
                window.location.href = url.toString();
            }
        });
    });
});
</script>

<script>

let myChart = null;

// ✅ 切換圖表按鈕背景樣式
function changeBackgroundColor(button) {
    var buttons = document.getElementsByClassName('btn-chart');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove('active');
    }
    button.classList.add('active');
}

// ✅ 切換圖表類型後導向正確 URL（chart=1~5）
function chart_type(argument) {
    const currentUrl = window.location.href;
    const chartMapping = {
        "torque_time": 1,
        "angle_time": 2,
        "rpm_time": 3,
        "torque_angle": 4,
        "torque_speed": 5
    };
    const chart = chartMapping[argument];
    if (!chart) return console.error("Invalid argument:", argument);

    updateActiveButton(argument);
    const nextinfo_url = updateChartUrl(currentUrl, chart);
    fetch(nextinfo_url, { method: 'GET' })
        .then(res => {
            if (res.ok) window.location.assign(nextinfo_url);
            else console.error("Failed to fetch:", res.statusText);
        })
        .catch(error => console.error("Error fetching URL:", error));
}

// ✅ 更新按鈕樣式為 active
function updateActiveButton(argument) {
    const buttons = document.getElementsByClassName("btn-chart");
    Array.from(buttons).forEach(btn => btn.classList.remove("active"));
    const activeButton = document.getElementById(argument);
    if (activeButton) activeButton.classList.add("active");
}

// ✅ 根據目前 URL 替換 chart=參數
function updateChartUrl(currentUrl, chart) {
    const chartIndex = currentUrl.indexOf('chart=');
    const nextChartValue = `chart=${chart}`;
    if (chartIndex !== -1) {
        return currentUrl.substring(0, chartIndex) + nextChartValue;
    } else {
        const separator = currentUrl.includes('?') ? '&' : '?';
        return currentUrl + separator + nextChartValue;
    }
}

const chartMode = "<?php echo $data['chart_mode']; ?>";

console.log("chart_mode from php:", chartMode );

// 啟用縮放（無底部 slider）
function enableZoom(xAxisType = 'value') {
  // 類別軸用 filterMode: 'empty'，數值軸用 'filter'
  const isCategory = String(xAxisType) === 'category';

  myChart.setOption({
    toolbox: {
      right: 10,
      feature: {
        // 工具列提供區域縮放/還原/存圖；不會產生底部 slider
        dataZoom: { yAxisIndex: 'none' },
        restore: {},
        saveAsImage: {}
      }
    },
    dataZoom: [
      // 內建手勢/滑輪縮放（手機/滑鼠）
      {
        type: 'inside',
        xAxisIndex: 0,
        filterMode: isCategory ? 'empty' : 'filter',
        // 滾輪縮放行為：'shift' 需配合 Shift；若想直接用滾輪縮放改成 true
        zoomOnMouseWheel: 'shift',
        moveOnMouseMove: true,
        preventDefaultMouseMove: false,
        throttle: 50
      }
    ]
  });
}

// === 移除 angle 前面都是 0 的區段，避免曲線貼在 Y 軸 ===
function trimLeadingZeroAngle(x, y) {
    let idx = 0;
    while (idx < x.length && Number(x[idx]) === 0) {
        idx++;
    }
    // 如果全部都是 0 就不處理
    if (idx >= x.length - 1) return { x, y };

    return {
        x: x.slice(idx),
        y: y.slice(idx)
    };
}



function renderChart(chart_mode, chart_info) {
    const chartDom = document.getElementById('chart');
    echarts.dispose(chartDom);
    myChart = echarts.init(chartDom);

    const x_data_val        = chart_info?.x_val || [];
    const y_data_val        = (chart_info?.y_val || []).map(Number);
    const y_data_val_torque = chart_info?.y_val_torque?.map(Number) || [];
    const y_data_val_rpm    = chart_info?.y_val_rpm?.map(Number)    || [];
    const stepsRaw          = chart_info?.steps || [];

    /* -----------------------------
       CHART 6 - Step Angle (Y = Mode4)
       ----------------------------- */
    if (String(chart_mode) === "6") {
        // 這裡直接用後端給的 x_val（通常是累積角度 contX）
        const xContinuous = (chart_info?.x_val || []).map(Number);
        const yFromMode4  = (chart_info?.y_val?.length ? chart_info.y_val : y_data_val).map(Number);
        const steps       = chart_info?.steps || [];

        const len = Math.min(xContinuous.length, yFromMode4.length, steps.length || Infinity);

        renderChartMode6({
            x_val: xContinuous.slice(0, len),
            y_val: yFromMode4.slice(0, len),
            steps: steps.slice(0, len),
            y_min: chart_info?.y_min,
            y_max: chart_info?.y_max
        });
        return;
    }

    /* -----------------------------
       CHART 7 - Angle vs Time (per step)
       ----------------------------- */
    if (String(chart_mode) === "7") {
        const timeX = x_data_val.map(v => Number(v) || 0);
        const localY = y_data_val.map(v => Number(v) || 0);

        const stepsArr = (stepsRaw.length ? stepsRaw : timeX.map(() => 1)).map(s => {
            const m = /(\d+)/.exec(String(s));
            return m ? +m[1] : 1;
        });

        const segments = [];
        let buf = [];
        let currStep = stepsArr[0];

        for (let i = 0; i < timeX.length; i++) {
            const s = stepsArr[i];
            const pt = [timeX[i], localY[i]];

            if (i > 0 && s !== currStep) {
                buf.push(pt);
                segments.push({ step: currStep, data: buf });
                buf = [pt];
                currStep = s;
            } else buf.push(pt);
        }
        if (buf.length) segments.push({ step: currStep, data: buf });

        const palette = {1:'#0066ff',2:'#cc0000',3:'#009933',4:'#ff9900',5:'#6600cc'};
        const series = segments.map(seg => ({
            name: `Step${seg.step}`,
            type: 'line',
            showSymbol: false,
            connectNulls: true,
            smooth: true,
            lineStyle: { width: 2, color: palette[seg.step] || '#888' },
            data: seg.data
        }));

        myChart.setOption({
            tooltip: { trigger: 'axis', axisPointer: { type: 'line' } },
            xAxis: { type: 'value', name: 'Time' },
            yAxis: { type: 'value', name: 'Angle' },
            series
        });

        return;
    }

    /* -----------------------------
       CHART 2 - Step Angle vs Time
       ----------------------------- */
    if (String(chart_mode) === "2") {
        const timeX  = x_data_val.map(v => Number(v) || 0);
        const angleY = y_data_val.map(v => Number(v) || 0);

        const stepsArr = (stepsRaw.length ? stepsRaw : timeX.map(() => 1)).map(s => {
            const m = /(\d+)/.exec(String(s));
            return m ? +m[1] : 1;
        });

        const stepOrder = [...new Set(stepsArr)];

        const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];
        const colorFor = (k, i) =>
            (k >= 1 && k <= 5) ? palette[k - 1] : palette[i % palette.length];

        const series = stepOrder.map((s, idx) => {
            const pts = [];
            for (let i = 0; i < timeX.length; i++) {
                if (stepsArr[i] === s)
                    pts.push([timeX[i], angleY[i]]);
            }
            if (!pts.length) return null;
            return {
                name: `Step${s}`,
                type: 'line',
                showSymbol: false,
                connectNulls: true,
                lineStyle: { width: 2, color: colorFor(s, idx) },
                data: pts
            };
        }).filter(Boolean);

        myChart.setOption({
            tooltip: { trigger: 'axis' },
            xAxis: { type: 'value', name: 'Time' },
            yAxis: { type: 'value', name: 'Angle' },
            series
        });

        return;
    }


    /* -----------------------------
    CHART 5 - Torque + RPM（修正版，不貼 Y 軸）
    ----------------------------- */
    if (String(chart_mode) === "5") {

        const X = x_data_val.map(v => Number(v) || 0);
        const torqueY = y_data_val_torque;
        const rpmY    = y_data_val_rpm;

        const steps = stepsRaw.map(v => {
            const m = /\d+/.exec(String(v));
            return m ? Number(m[0]) : 1;
        });

        const uniqueSteps = [...new Set(steps)];

        const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];

        const seriesTorque = uniqueSteps.map((s, idx) => {
            const pts = [];
            for (let i = 0; i < X.length; i++) {
                if (steps[i] === s) pts.push([X[i], torqueY[i]]);
            }
            return {
                name: `Step${s}`,
                type: 'line',
                yAxisIndex: 0,
                showSymbol: false,
                connectNulls: true,
                lineStyle: { width: 2, color: palette[idx % palette.length] },
                data: pts
            };
        });

        const rpmSeries = {
            name: 'RPM',
            type: 'line',
            yAxisIndex: 1,
            showSymbol: false,
            connectNulls: true,
            lineStyle: { width: 2, color: 'orange' },
            data: X.map((x, i) => [x, rpmY[i]])
        };

        myChart.setOption({
            tooltip: { trigger: 'axis' },
            xAxis: { 
                type: 'value',
                name: 'Time',       
                boundaryGap: false 
            },
            yAxis: [
                { type: 'value', name: 'Torque' },
                { type: 'value', name: 'RPM', min: 0, max: 700 }
            ],
            series: [...seriesTorque, rpmSeries]
        });

        return;
    }




    /* -----------------------------
    CHART 4 - Torque vs Angle (continuous angle)
    ----------------------------- */
    if (String(chart_mode) === "4") {

        // ⭐ 先移除 angle=0 的前導資料
        const trimmed = trimLeadingZeroAngle(x_data_val, y_data_val);
        const x_raw = trimmed.x;   // 修正後的角度
        const y_raw = trimmed.y;   // 修正後的 torque

        // ⭐ 修正 stepsRaw 也需要同步裁切
        const startIndex = x_data_val.length - x_raw.length;
        const stepsArrFull = stepsRaw.map(v => {
            const m = /\d+/.exec(String(v));
            return m ? Number(m[0]) : 1;
        });
        const stepsArr = stepsArrFull.slice(startIndex);

        /* ====== 建立連續角度 contX（用裁切後 x_raw）====== */
        let offset = 0;
        let prevStep = stepsArr[0];
        let prevLocal = Number(x_raw[0]) || 0;

        const contX = x_raw.map((localAngle, i) => {
            const s = stepsArr[i];

            if (i > 0 && s !== prevStep) {
                offset += prevLocal; // 上一步的 ending angle
            }

            prevStep = s;
            prevLocal = localAngle;

            return offset + localAngle;  // ⭐ 正確累積
        });

        /* ====== 分步建立曲線 ====== */
        const uniqueSteps = [...new Set(stepsArr)];
        const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];

        const series = uniqueSteps.map((s, idx) => {
            const pts = [];
            for (let i = 0; i < y_raw.length; i++) {
                if (stepsArr[i] === s) pts.push([contX[i], y_raw[i]]);
            }
            return {
                name: `Step${s}`,
                type: 'line',
                showSymbol: false,
                connectNulls: false,
                lineStyle: { width: 2, color: palette[idx % palette.length] },
                data: pts
            };
        });

        myChart.setOption({
            tooltip: { trigger: 'axis' },
            xAxis: { type: 'value', name: 'Angle' },
            yAxis: { type: 'value', name: 'Torque' },
            series
        });

        return;
    }




    /* -----------------------------
       CHART 1 / 3 (Time / RPM)
       （沒有 fallback）
       ----------------------------- */
    const stepKeys = stepsRaw.map(v => {
        const m = /\d+/.exec(String(v));
        return m ? Number(m[0]) : v;
    });

    const uniqueKeys = [...new Set(stepKeys)];
    const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];

    const seriesBase = uniqueKeys.map((key, idx) => {
        const arr = y_data_val.map((v, i) => stepKeys[i] === key ? v : null);
        if (!arr.some(v => v != null)) return null;
        return {
            name: `Step${key}`,
            type: 'line',
            symbol: 'none',
            connectNulls: true,
            lineStyle: { width: 2, color: palette[idx % palette.length] },
            data: arr
        };
    }).filter(Boolean);

    let finalSeries = seriesBase;  // ⭐ 沒有 fallback！

    if (["1","3"].includes(String(chart_mode))) {
        finalSeries = finalSeries.map(s => {
            const d = [...s.data];
            for (let i = 0; i < d.length; i++) {
                const v = Number(d[i]);
                if (v !== 0 && !isNaN(v)) {
                    d[i] = null;     // 不貼 Y 軸
                    break;
                }
            }
            return { ...s, data: d };
        });
    }

    const yAxisTitle = ["2","7"].includes(String(chart_mode)) ? "Angle" : "Torque";
    const xAxisData = ["1","3"].includes(String(chart_mode))
        ? x_data_val.map(v => Number.isFinite(Number(v)) ? Math.round(Number(v)) : v)
        : x_data_val;

    myChart.setOption({
        tooltip: { trigger: 'axis' },
        xAxis: { type: 'category', boundaryGap: false, data: xAxisData },
        yAxis: { type: 'value', name: yAxisTitle },
        series: finalSeries
    });
}




function pickSeries(...cands) {
  for (const a of cands) {
    if (!a) continue;
    const arr = a.map(Number).filter(v => !Number.isNaN(v));
    if (arr.length && arr.some(v => v !== 0)) return a.map(Number);
  }
  for (const a of cands) { if (a && a.length) return a.map(Number); }
  return [];
}

// === chart=6：Y 軸沿用 mode=4 的 y_val；每個 step 自己的角度 0 起點 ===
function renderChartMode6(chart_info) {
  const X_raw = pickSeries(chart_info?.angle1, chart_info?.angle, chart_info?.x_val).map(Number);
  const Y        = (chart_info?.y_val || []).map(Number);  // mode=4 的 torque
  const stepsRaw = chart_info?.steps || Array(X_raw.length).fill('S1');

  const len = Math.min(X_raw.length, Y.length, stepsRaw.length);

  // 解析 step 成數字（S1 -> 1）
  const steps = stepsRaw.slice(0, len).map(v => {
    const m = String(v).match(/\d+/);
    return m ? Number(m[0]) : 1;
  });

  // ⭐ 針對每個 step，把 X 重新以該 step 的第一個點當作 0 起點
  const localX = new Array(len);
  let currentStep = steps[0];
  let stepStartX  = X_raw[0] || 0;

  for (let i = 0; i < len; i++) {
    const s = steps[i];
    const x = X_raw[i] || 0;

    if (i === 0) {
      currentStep = s;
      stepStartX  = x;
    } else if (s !== currentStep) {
      // step 切換 → 更新新的起點
      currentStep = s;
      stepStartX  = x;
    }
    localX[i] = x - stepStartX;   // 讓每個 step 從 0 開始
  }

  // 依 step 分組資料點
  const byStep = new Map();
  for (let i = 0; i < len; i++) {
    if (!Number.isFinite(localX[i]) || !Number.isFinite(Y[i])) continue;
    const k = steps[i];
    if (!byStep.has(k)) byStep.set(k, []);
    byStep.get(k).push([localX[i], Y[i]]);
  }

  // step 出現順序
  const stepOrder = [];
  for (let i = 0; i < len; i++) {
    if (!stepOrder.includes(steps[i])) stepOrder.push(steps[i]);
  }

  const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];
  const colorFor = (key, idx) =>
    (typeof key === 'number' && key >= 1 && key <= 5)
      ? palette[key - 1]
      : palette[idx % palette.length];

  const series = stepOrder.map((k, idx) => ({
    name: `Step${k}`,
    type: 'line',
    showSymbol: false,
    connectNulls: true,
    lineStyle: { width: 2, color: colorFor(k, idx) },
    data: byStep.get(k) || []
  })).filter(s => s.data.length);

  const yMin = Number.isFinite(chart_info?.y_min)
    ? chart_info.y_min
    : Math.min(0, ...Y.filter(Number.isFinite));

  const yMax = Number.isFinite(chart_info?.y_max)
    ? chart_info.y_max
    : Math.max(...Y.filter(Number.isFinite), 1);

  myChart.setOption({
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'line' },
      formatter: (params) => {
        const p = params.find(p => p?.value && p.value[1] != null) || params[0];
        if (!p || !p.value) return '';
        return `Angle: ${p.value[0]}\n${p.seriesName}: ${p.value[1]}`;
      }
    },
    legend: { show: false },
    xAxis: { type: 'value', name: 'Angle', splitLine: { show: false } },
    yAxis: {
      type: 'value',
      name: 'Torque',
      min: yMin,
      max: yMax,
      splitLine: { show: true }
    },
    series: series.length ? series : [{
      name: 'Curve',
      type: 'line',
      showSymbol: false,
      lineStyle: { width: 2 },
      data: localX.slice(0, len).map((xi, i) => [xi, Y[i]])
    }]
  });

  console.log('mode6 check', {
    X_raw_10: X_raw.slice(0,10),
    localX_10: localX.slice(0,10),
    Y_10: Y.slice(0,10),
    steps_10: steps.slice(0,10),
    series_n: series.length
  });
}





let previousChartInfo = null;

function fetchChartAndRender() {
   
    fetch(`?url=Dashboards/operation&chart=${chartMode}&ajax=1`)
        .then(res => res.json())
        .then(data => {
            const chartArea = document.getElementById('chart');

            const messages = {
                "zh-tw": "⚠️ 無可用資料",
                "zh-cn": "⚠️ 无可用数据",
                "en": "⚠️ No available data"
            };
            const language = getCookie('language') || 'en';

           
            // ✅ 更新畫面上資料區塊（data_info）
            const info = data.data_info || {};
            const textMap = data.text || {};
            const statusKey = info.fasten_status_text ?? '-';

            console.log(info);

            document.getElementById("Job_Name").value =`${info?.job_id ?? '***'}/${info?.job_name ?? '***'}`;
            document.getElementById("Seq_Name").value =`${info?.sequence_id ?? '***'}/${info?.sequence_name ?? '***'}`;
            document.getElementById("Screws").value = info.	last_screw_count + "/" + info.total_screw_count  ?? '***';

            document.getElementById("Screws").value = info.total_screw_count ?? '***';
            document.getElementById("Target_Torque").innerText = info.final_fasten_torque ?? '-';
            document.getElementById("Target_Angle").innerText = info.total_fasten_angle ?? '-';
            document.getElementById("Torque_Result").innerText = textMap[statusKey] ?? statusKey;
            document.getElementById("Message").innerText = info.error_message ?? '';
            document.getElementById("fasten_status_color").style.backgroundColor  = info.result_status_color_text;

            // ✅ 比對圖表資料是否需要更新
            const currentChartInfo = JSON.stringify(data.chart_info);
            if (currentChartInfo === previousChartInfo) {
                return;
            }
            previousChartInfo = currentChartInfo;

            // ✅ 繪製圖表
            renderChart(chartMode, data.chart_info);
        })
        .catch(err => {
            console.error("AJAX ERROR", err);
        });
}

function syncNtcsDataDb() {
    const url = `${window.location.protocol}//${window.location.hostname}/ntcs_idas/public/?url=Data/ntcs_data_db_sysnc`;

    fetch(url, {
    method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
    console.log(data);
    document.getElementById("result").textContent =
        `[${data.status}] ${data.message}`;
    })
    .catch(err => {
    console.error("❌ 呼叫失敗", err);
    document.getElementById("result").textContent =
        "❌ 呼叫失敗";
    });
}


// 初次載入 + 每 1 秒更新
fetchChartAndRender();
setInterval(fetchChartAndRender, 1000);



// 旋轉或調整螢幕大小時 → 圖表會自動調整 - Khi xoay hoặc resize màn hình → biểu đồ tự điều chỉnh lại
window.addEventListener("resize", () => myChart?.resize?.());
 
</script>


<style>
#graph { position: relative; }
#chart { position: relative; z-index: 1; } /* 圖 */
#chart-note {
  display: block !important;   /* 防外部把 <p> 隱藏 */
  margin: 4px 0 8px;
  position: relative;
  z-index: 2;                  /* 比圖還高 */
  color: #000 !important;      /* 防父層強制白字或透明 */
  font-size: 14px !important;  /* 防 font-size:0 */
  line-height: 1.2;
}
</style>