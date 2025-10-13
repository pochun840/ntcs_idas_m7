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
                            <?php echo $data['text'][$data['data_info']['fasten_status_text']];?>
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
                        <div id="Message" class="w3-display-middle" style="font-size: 28px">
                        <?php if ($data['data_info']['error_message']){?>
                            <?php echo $data['data_info']['error_message']; ?>
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

                        <div id="graph" class="display-chart">
                            <div id="chart" style="width: 100%; height: 100%"></div>
                        </div>      
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

// 啟用縮放 
function enableZoom(xAxisType = 'value') {
  // 類別軸用 filterMode: 'empty'，數值軸用 'filter'，避免扯到另一邊的資料
  const isCategory = String(xAxisType) === 'category';
  myChart.setOption({
    toolbox: {
      right: 10,
      feature: {
        dataZoom: { yAxisIndex: 'none' },   // 工具列上的縮放/還原
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
        zoomOnMouseWheel: 'shift',  // 滾輪需搭配 Shift，避免誤觸；想直接滾輪縮放就改 true
        moveOnMouseMove: true,
        preventDefaultMouseMove: false,
        throttle: 50
      },
      // 底部滑桿
      {
        type: 'slider',
        xAxisIndex: 0,
        height: 18,
        brushSelect: false,
        filterMode: isCategory ? 'empty' : 'filter'
      }
    ]
  });
}


function renderChart(chart_mode, chart_info) {
    const chartDom = document.getElementById('chart');
    echarts.dispose(chartDom);
    myChart = echarts.init(chartDom); // 全域

    const x_data_val        = chart_info?.x_val || [];
    const y_data_val        = (chart_info?.y_val || []).map(Number);
    const y_data_val_torque = chart_info?.y_val_torque?.map(Number) || [];
    const y_data_val_rpm    = chart_info?.y_val_rpm?.map(Number)    || [];
    const stepsRaw          = chart_info?.steps || [];

    // === chart 6：步驟角度（Y 軸沿用 mode=4 的 y_val，X 用角度）===
    if (String(chart_mode) === "6") {
        // 角度 X（優先 x_val，其次 angle.1/angle）
        const angleX = pickSeries(chart_info?.x_val, chart_info?.angle1, chart_info?.angle_1, chart_info?.angle);

        // ★ 關鍵：Y = mode=4 的座標值
        const yFromMode4 = (chart_info?.y_val && chart_info.y_val.length)
            ? chart_info.y_val.map(Number)
            : y_data_val; // ← 這是你在上面已算好的、給 mode=4 用的 y

        // steps
        const steps = chart_info?.steps || [];

        // 對齊長度後再丟給 mode6
        const len = Math.min(angleX.length, yFromMode4.length, steps.length || Infinity);
        renderChartMode6({
            x_val: angleX.slice(0, len),
            y_val: yFromMode4.slice(0, len),   // ★ 傳進去，確保 Y 與 mode=4 一致
            steps: steps.slice(0, len),
            // 若你有帶 mode=4 的 y 軸上下界，也一起傳；沒有就略過
            y_min: chart_info?.y_min,
            y_max: chart_info?.y_max
        });
        //enableZoom('value');
        return; // 別讓下面覆蓋
    }

    // === chart 7：Angle vs Time（單一曲線，但依 step 分段上色；Y 使用「總角度」）===
    if (String(chart_mode) === "7") {
        // 1) 轉 step 為數字
        const stepsArr = (chart_info?.steps || x_data_val.map(() => 1)).map(s => {
            const m = /(\d+)/.exec(String(s));
            return m ? +m[1] : 1;
        });

        // 2) 原始「局部角度」與時間
        const localY = y_data_val.map(v => Number(v) || 0);
        const timeX  = x_data_val.map(v => Number(v) || 0);

        // 3) 把每步角度累加成「總角度」
        let offset = 0, prevStep = stepsArr[0] ?? 1, prevLocal = localY[0] || 0;
        const totalY = localY.map((y, i) => {
            const s = stepsArr[i] ?? prevStep;
            if (i > 0 && s !== prevStep) {
            offset += prevLocal;   // 新 step 開始前，先把上一 step 的末值加進 offset
            prevLocal = 0;
            }
            prevStep = s;
            prevLocal = y;
            return offset + y;       // 這筆的「總角度」
        });

        // 4) 依「連續的同一步驟」切成多段（邊界點重複兩邊，避免縫隙）
        const segments = [];
        let currStep = stepsArr[0] ?? 1;
        let buf = [];
        for (let i = 0; i < timeX.length; i++) {
            const s = stepsArr[i] ?? currStep;
            const pt = [timeX[i], totalY[i]];

            if (i > 0 && s !== currStep) {
            buf.push(pt);                            // 用邊界點結束上一段
            segments.push({ step: currStep, data: buf });
            buf = [pt];                              // 用同一邊界點開始下一段
            currStep = s;
            } else {
            // 同一步內，若時間重複，覆蓋舊點避免直線跳動
            if (buf.length && buf[buf.length - 1][0] === pt[0]) buf[buf.length - 1] = pt;
            else buf.push(pt);
            }
        }
        if (buf.length) segments.push({ step: currStep, data: buf });

        // 5) 顏色表：1 藍、2 紅、3 綠、4 橘、5 紫
        const palette = {1:'#0066ff',2:'#cc0000',3:'#009933',4:'#ff9900',5:'#6600cc'};
        const series = segments.map(seg => ({
            name: `Step${seg.step}`,
            type: 'line',
            showSymbol: false,
            connectNulls: true,
            lineStyle: { width: 2, color: palette[seg.step] || '#888' },
            data: seg.data
        }));

        myChart.setOption({
            tooltip: { trigger: 'axis', axisPointer: { type: 'line' } },
            xAxis: { type: 'value', name: 'Time', boundaryGap: false, splitLine: { show: false } },
            yAxis: { type: 'value', name: 'Angle', splitLine: { show: true } },
            series
        });
        //enableZoom('value');
        return;
    }


    // === chart 2：Angle vs Time（Step angle）；X 軸與 chart=7 相同（用數值 Time）===
    if (String(chart_mode) === "2") {

        // 1) 轉成數值 time / angle
        const timeX  = (chart_info?.x_val || []).map(v => Number(v) || 0);
        const angleY = (chart_info?.y_val || []).map(v => Number(v) || 0);

        // 2) 解析 step 編號（S2 / Step 2 / 2 都會變 2；抓不到就用 1）
        const stepsArr = (chart_info?.steps || timeX.map(() => 1)).map(s => {
            const m = /(\d+)/.exec(String(s));
            return m ? +m[1] : 1;
        });

        // 3) 依出現順序建立 Step 清單（確保顏色/圖例穩定）
        const stepOrder = [];
        for (let i = 0; i < stepsArr.length; i++) {
            const s = stepsArr[i];
            if (!stepOrder.includes(s)) stepOrder.push(s);
        }

        // 4) 配色（和其它分支一致）
        const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];
        const colorFor = (key, idx) =>
            (typeof key === 'number' && key >= 1 && key <= 5)
            ? palette[key - 1]
            : palette[idx % palette.length];

        // 5) 每個 step 一條線，資料點是 [time, local-angle]
        const series = stepOrder.map((s, idx) => {
            const pts = [];
            for (let i = 0; i < timeX.length; i++) {
            if (stepsArr[i] === s && Number.isFinite(timeX[i]) && Number.isFinite(angleY[i])) {
                pts.push([timeX[i], angleY[i]]);
            }
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

        // 6) 用「數值型」X 軸，名稱 Time；Y 軸名稱 Angle
        myChart.setOption({
            tooltip: { trigger: 'axis', axisPointer: { type: 'line' } },
            xAxis: { type: 'value', name: 'Time', boundaryGap: false, splitLine: { show: false } },
            yAxis: { type: 'value', name: 'Angle', splitLine: { show: true } },
            series: series.length ? series : [{
            name: 'Angle', type: 'line', showSymbol: false, lineStyle: { width: 2 },
            data: timeX.map((t, i) => [t, angleY[i]])
            }]
        });
        //enableZoom('value');
        return; // ✅ 不要再往下跑到通用分支（分類軸）
    }


    // === chart 5：Torque + RPM（保留你的做法）===
    if (String(chart_mode) === "5") {
        const rpmMin = chart_info.min_rpm ?? Math.floor(Math.min(...y_data_val_rpm) / 100) * 100;
        const rpmMax = chart_info.max_rpm ?? Math.ceil(Math.max(...y_data_val_rpm) / 100) * 100;

        // 步驟正規化（僅供分組著色）
        const stepKeys = stepsRaw.map(v => {
        const m = String(v).match(/\d+/);
        return m ? Number(m[0]) : String(v || 'step');
        });
        const uniqueKeys = Array.from(new Set(stepKeys));

        const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc','#5b9bd5','#ed7d31','#70ad47'];
        const colorFor = (key, idx) => (typeof key === 'number' && key >=1 && key <=5)
        ? palette[key-1] : palette[idx % palette.length];

        // ⭐ chart=5：X 軸標籤改成整數（例：92.5 → 93）
        const xAxisData5 = (chart_info?.x_val || []).map(v => {
            const n = Number(v);
            return Number.isFinite(n) ? Math.round(n) : v;
        });


        const torqueSeries = uniqueKeys.map((key, idx) => {
        const seriesData = y_data_val_torque.map((v, i) => stepKeys[i] === key ? v : null);
        if (!seriesData.some(v => v != null)) return null;
        return {
            name: `Step${key}`,
            type: 'line',
            symbol: 'none',
            connectNulls: true,
            yAxisIndex: 0,
            lineStyle: { width: 2, color: colorFor(key, idx) },
            data: seriesData
        };
        }).filter(Boolean);

        const rpmSeries = {
        name: 'RPM',
        type: 'line',
        symbol: 'none',
        yAxisIndex: 1,
        lineStyle: { width: 1.5, color: 'orange' },
        data: y_data_val_rpm
        };

        myChart.setOption({
        tooltip: { trigger: 'axis', axisPointer: { type: 'cross' } },
        xAxis: { type: 'category', boundaryGap: false, data: xAxisData5, axisLabel: { show: true } },
        yAxis: [
            { type: 'value', name: 'Torque', splitLine: { show: true } },
            { type: 'value', name: 'RPM', min: rpmMin, max: rpmMax, splitLine: { show: false } }
        ],
        series: [...torqueSeries, rpmSeries]
        });
        //enableZoom('value');
        return;
    }

    // === chart 4：Torque–Angle，X 需連續（跨 step 累加）===
    if (String(chart_mode) === "4") {
        // 1) 解析 step 編號（S2 / Step 2 / 2 都會變成 2；抓不到就沿用上一個或預設 1）
        const stepsRaw = chart_info?.steps || [];
        const stepNum = x_data_val.map((_, i) => {
            const v = stepsRaw[i];
            const m = /(\d+)/.exec(String(v));
            if (m) return Number(m[1]);
            return i ? (stepNum[i - 1] ?? 1) : 1;
        });

        // 2) 把每個 step 的局部角度累加成「連續角度」
        let offset = 0;
        let prevStep = stepNum[0] ?? 1;
        let prevLocal = Number(x_data_val[0]) || 0;

        const contX = x_data_val.map((x, i) => {
            const localX = Number(x) || 0;
            const s = stepNum[i] ?? prevStep;

            if (i > 0 && s !== prevStep) {
            offset += prevLocal;           // 累加上一個 step 的尾端角度
            }
            prevStep = s;
            prevLocal = localX;
            return offset + localX;          // 全域連續角度
        });

        // 3) 依 step 分出多條線，但每條線使用「連續角度 contX」做 X
        const uniqueKeys = [];
        for (let i = 0; i < stepNum.length; i++) {
            if (!uniqueKeys.includes(stepNum[i])) uniqueKeys.push(stepNum[i]);
        }

        const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];
        const colorFor = (key, idx) =>
            (typeof key === 'number' && key >= 1 && key <= 5)
            ? palette[key - 1]
            : palette[idx % palette.length];

        const series = uniqueKeys.map((key, idx) => {
            const pts = [];
            for (let i = 0; i < y_data_val.length; i++) {
            if (stepNum[i] === key) pts.push([contX[i], y_data_val[i]]);
            }
            if (!pts.length) return null;
            return {
            name: `Step${key}`,
            type: 'line',
            showSymbol: false,
            connectNulls: true,
            lineStyle: { width: 2, color: colorFor(key, idx) }, // Step2 會是紅色
            data: pts
            };
        }).filter(Boolean);

        myChart.setOption({
            tooltip: { trigger: 'axis', axisPointer: { type: 'line' } },
            xAxis: { type: 'value', name: 'Angle', splitLine: { show: false } }, // ★ 連續數值軸
            yAxis: { type: 'value', name: 'Torque', splitLine: { show: true } },
            series: series.length ? series : [{
            name: 'Curve',
            type: 'line',
            showSymbol: false,
            lineStyle: { width: 2 },
            data: contX.map((x, i) => [x, y_data_val[i]])
            }]
        });
        //enableZoom('value');
        return; // 不要進到下面的一般 1~4 分支
    }


    

    // === 其餘（1~4）：沿用原本 category 畫法 ===
    const stepKeys = stepsRaw.map(v => {
        const m = String(v).match(/\d+/);
        return m ? Number(m[0]) : String(v || 'step');
    });

    const uniqueKeys = Array.from(new Set(stepKeys));
    const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];
    const colorFor = (key, idx) => (typeof key === 'number' && key >=1 && key <=5)
        ? palette[key-1] : palette[idx % palette.length];

    const series = uniqueKeys.map((key, idx) => {
        const seriesData = y_data_val.map((v, i) => stepKeys[i] === key ? v : null);
        if (!seriesData.some(v => v != null)) return null;
        return {
        name: `Step${key}`,
        type: 'line',
        symbol: 'none',
        connectNulls: true,
        lineStyle: { width: 2, color: colorFor(key, idx) },
        data: seriesData
        };
    }).filter(Boolean);

    const finalSeries = series.length ? series : [{
        name: 'Curve',
        type: 'line',
        symbol: 'none',
        lineStyle: { width: 2 },
        data: y_data_val
    }];

    // ⭐ 修正 chart=4 的 X 軸「0 重複」標籤
    const isMode4 = String(chart_mode) === "4";
    const xLabels = (function dedupeEdgeZero(arr){
        if (isMode4 && arr.length && Number(arr[arr.length - 1]) === 0) {
        const copy = arr.slice();
        copy[copy.length - 1] = '';   // 尾端若是 0，改成空字串避免重複顯示
        return copy;
        }
        return arr;
    })(x_data_val);

    // ➜ 新增：chart=2  && chart=7  時，Y 軸名稱使用 'Angle'，其餘維持 'Torque'
    const yAxisTitle = (String(chart_mode) === "2" || String(chart_mode) === "7") ? 'Angle' : 'Torque';

    // ⭐ chart=1 / chart=3：X 軸標籤改成整數（例：92.5 → 93）
    const xAxisData = (["1","3"].includes(String(chart_mode)))
    ? x_data_val.map(v => {
        const n = Number(v);
        return Number.isFinite(n) ? Math.round(n) : v;
        })
    : x_data_val;
        


    myChart.setOption({
        tooltip: { trigger: 'axis', axisPointer: { type: 'none' } },
        xAxis: { type: 'category', boundaryGap: false, data: xAxisData, axisLabel: { show: true } },
        yAxis: { type: 'value', name: yAxisTitle, splitLine: { show: true } },
        series: finalSeries
    });

    //enableZoom('value');
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

// === chart=6：Y 軸沿用 mode=4 的 y_val；每個 step 各自一條曲線（X 用自己的角度起點，不拼接）===
function renderChartMode6(chart_info) {
  const X = (chart_info?.x_val || []).map(Number);
  const Y = (chart_info?.y_val || []).map(Number);   // ★ 已確保是 mode=4 的 y
  const stepsRaw = chart_info?.steps || Array(X.length).fill('S1');

  const len = Math.min(X.length, Y.length, stepsRaw.length);
  const steps = stepsRaw.slice(0, len).map(v => {
    const m = String(v).match(/\d+/); return m ? Number(m[0]) : String(v || 'step');
  });

  // 依 step 分組
  const byStep = new Map();
  for (let i = 0; i < len; i++) {
    if (!Number.isFinite(X[i]) || !Number.isFinite(Y[i])) continue;
    const k = steps[i];
    if (!byStep.has(k)) byStep.set(k, []);
    byStep.get(k).push([X[i], Y[i]]);
  }

  // step 出現順序（for legend / 顏色）
  const stepOrder = [];
  for (let i = 0; i < len; i++) if (!stepOrder.includes(steps[i])) stepOrder.push(steps[i]);

  // 你的配色順序
  const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];
  const colorFor = (key, idx) =>
    (typeof key === 'number' && key >= 1 && key <= 5) ? palette[key - 1] : palette[idx % palette.length];

  const series = stepOrder.map((k, idx) => ({
    name: `Step${k}`,
    type: 'line',
    showSymbol: false,
    connectNulls: true,                 // 同一步內連線
    lineStyle: { width: 2, color: colorFor(k, idx) },
    data: byStep.get(k) || []
  })).filter(s => s.data.length);

  // Y 軸範圍：優先沿用 mode=4；否則由資料推
  const yMin = Number.isFinite(chart_info?.y_min) ? chart_info.y_min
             : Math.min(0, ...Y.filter(Number.isFinite));
  const yMax = Number.isFinite(chart_info?.y_max) ? chart_info.y_max
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
    yAxis: { type: 'value', name: 'Torque', min: yMin, max: yMax, splitLine: { show: true } },
    series: series.length ? series : [{
      name: 'Curve', type: 'line', showSymbol: false, lineStyle: { width: 2 },
      data: X.slice(0, len).map((xi, i) => [xi, Y[i]])
    }]
  });

  // （可選）debug：確認進來的資料不是全 0
  console.log('mode6 check', {
    x_10: X.slice(0,10), y_10: Y.slice(0,10),
    steps_10: steps.slice(0,10), series_n: series.length
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

            document.getElementById("Job_Name").value = info.job_id + "/" +info.job_name ?? '***';
            document.getElementById("Seq_Name").value = info.sequence_id + "/" + info.sequence_name ?? '***';
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