
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
                            (<?php echo $data['text'][$data['data_info']['final_torque_unit'] ?? ''] ?? ''; ?>)
                        </div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 4vmin; margin: 5px 0;"><?php echo $data['data_info']['final_fasten_torque'] ?? '-'; ?></div>
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
                        <div id="Message" class="w3-display-middle" style="font-size: 4vmin; margin: 5px 0">  
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
                    <?php foreach($data['chart_menu_arr'] as $k_menu => $v_menu) {
                        $isActive = ($data['chart_mode'] == $k_menu) ? 'btn-chart active' : 'btn-chart';
                    ?>
                        <button type="button"
                                class="<?php echo $isActive; ?>"
                                id="<?php echo $v_menu['id']; ?>"
                                onclick="chart_type('<?php echo $v_menu['id']; ?>')">
                            <?php echo $data['text'][$v_menu['name']]; ?>
                        </button>
                    <?php } ?>
                </div>

                    <?php if(!empty($data['chart_info'])){?>
                        <div id="graph" class="display-chart">
                            <div id="chart" style="max-width: 100%; height: 290px;"></div>
                        </div> 
                    <?php }?>
              
            </div>
        </div>
    </div>
</div>


<script>
let chartMode = "<?php echo $data['chart_mode']; ?>"; // ✅ 改為 let

// ✅ 切換圖表按鈕
function chart_type(argument) {
    document.querySelectorAll('.btn-chart').forEach(btn => {
        btn.classList.toggle('active', btn.id === argument);
    });

    const chartMap = {
        "torque_time": 1,
        "angle_time": 2,
        "rpm_time": 3,
        "torque_angle": 4,
        "torque_speed": 5
    };

    let newChartMode = chartMap[argument];
    if (!newChartMode) return;

    chartMode = newChartMode; // ✅ 成功更新 chartMode

    const newUrl = new URL(window.location);
    newUrl.searchParams.set("chart", newChartMode);
    window.history.replaceState({}, '', newUrl);

    fetchChartAndRender(); // 立即更新圖表
}

// ✅ 初始化語系標題
const language = getCookie('language');
let x_title = '<?php echo addslashes($data['echart_name'][1]); ?>';
let y_title = '<?php echo addslashes($data['echart_name'][0]); ?>';

const translations = {
    "zh-tw": { "Time(MS)": "時間", "Angle": "角度", "Torque": "扭力", "RPM": "轉速" },
    "zh-cn": { "Time(MS)": "时间", "Angle": "角度", "Torque": "扭力", "RPM": "转速" }
};
const labelsMap = translations[language] || {};
x_title = labelsMap[x_title] || x_title;
y_title = labelsMap[y_title] || y_title;

var myChart;

function normalizeArray(arr) {
    if (!Array.isArray(arr)) return [];

    // 如果是 {x_val:{...}} 結構 → 抽出第一層
    if (Array.isArray(arr[0]) === false && typeof arr[0] === "object") {
        arr = Object.values(arr)[0] ?? [];
    }

    // 移除 CSV 表頭
    if (arr.length && isNaN(Number(arr[0]))) arr.shift();

    return arr.map(v => {
        v = String(v).trim();
        return Number(v) || 0;
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

        // ★ 如果後端沒給 step angle，就嘗試用 chart_mode=4 的 X 當 angle
        let angleX = pickSeries(chart_info?.x_val, chart_info?.angle1, chart_info?.angle_1, chart_info?.angle);

        // ★ 最後 fallback：用 index 0,1,2,3...
        if (!angleX.length) {
            console.warn("chart 6 angleX is empty → fallback to index");
            angleX = (chart_info?.y_val || []).map((_, i) => i);
        }

        // ★ Y 必須有資料
        const yFromMode4 = (chart_info?.y_val && chart_info.y_val.length)
            ? chart_info.y_val.map(Number)
            : [];

        if (!yFromMode4.length) {
            console.warn("chart 6 y_val empty → no chart");
            myChart.setOption({
                title: { text: "No Data", left: "center", top: "middle" }
            });
            return;
        }

        // steps fallback
        const steps = (chart_info?.steps && chart_info.steps.length)
            ? chart_info.steps
            : yFromMode4.map(() => 1);

        // 對齊長度
        const len = Math.min(angleX.length, yFromMode4.length, steps.length);

        renderChartMode6({
            x_val: angleX.slice(0, len),
            y_val: yFromMode4.slice(0, len),
            steps: steps.slice(0, len)
        });

        return;
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
            tooltip: { trigger: 'axis', axisPointer: {  type: 'none', show: false } },
            xAxis: { type: 'value', name: 'Time', boundaryGap: false, splitLine: { show: false } },
            yAxis: { type: 'value', name: 'Angle', splitLine: { show: true } },
            series
        });
        //enableZoom('value');
        return;
    }


    // === chart 2：Angle vs Time（Step angle）；X 軸與 chart=7 相同（用數值 Time）===
    // === chart 2：Angle vs Time（Step angle）；X 軸為數值 ===
    if (String(chart_mode) === "2") {

        const timeX  = (chart_info?.x_val || []).map(v => Number(v) || 0);
        const angleY = (chart_info?.y_val || []).map(v => Number(v) || 0);

        // step 列（第五欄）
        const stepsArr = (chart_info?.steps || timeX.map(() => 1)).map(s => {
            const m = /(\d+)/.exec(String(s));
            return m ? +m[1] : 1;
        });

        // 建立 step 集合
        const stepOrder = [];
        for (let i = 0; i < stepsArr.length; i++) {
            const s = stepsArr[i];
            if (!stepOrder.includes(s)) stepOrder.push(s);
        }

        const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];
        const colorFor = (key, idx) =>
            (typeof key === 'number' && key >= 1 && key <= 5)
                ? palette[key - 1]
                : palette[idx % palette.length];

        // 分 step 畫線
        const series = stepOrder.map((s, idx) => {
            const pts = [];
            for (let i = 0; i < timeX.length; i++) {
                if (stepsArr[i] === s) {
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

        // Y 軸處理（全部是 0 時也要能畫）
        let yMin = Math.min(...angleY);
        let yMax = Math.max(...angleY);
        if (yMin === yMax) {
            const pad = Math.abs(yMax || 1) * 0.1;
            yMin -= pad;
            yMax += pad;
        }

        myChart.setOption({
            tooltip: { trigger: 'axis', axisPointer: {  type: 'none', show: false } },

            // ❗❗ 這裡改為 value 軸
            xAxis: { 
                type: 'value',
                name: 'Time',
                boundaryGap: false,
                splitLine: { show: false }
            },

            yAxis: {
                type: 'value',
                name: 'Angle',
                min: yMin,
                max: yMax,
                splitLine: { show: true },
            },

            series: series.length ? series : [{
                name: 'Angle',
                type: 'line',
                showSymbol: false,
                connectNulls: true,
                lineStyle: { width: 2 },
                data: timeX.map((t, i) => [t, angleY[i]])
            }]
        });




        return;
    }


    // === chart 5：Torque + RPM ===
    if (String(chart_mode) === "5") {

        // ⭐ 從後端 ChartData() 抓取 torque / rpm 陣列
        const y_data_val_torque = chart_info?.torque || [];
        const y_data_val_rpm    = chart_info?.rpm    || [];

        const stepsRaw = chart_info?.steps || [];

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

        return;
    }



    // --------- chart_mode == 4：Torque vs Angle（依 step 分段 — 與 chart 6 一樣）---------
    // --------- chart_mode == 4：Torque vs Angle（簡單 tooltip; 與 chart 6 一樣）---------
    if (String(chart_mode) === "4") {

        let angleArr  = normalizeArray(chart_info?.x_val);
        let torqueArr = normalizeArray(chart_info?.y_val);

        // Step 資料（CSV 第 5 欄）
        let steps = (chart_info?.steps || []).map(s => {
            const m = String(s).match(/\d+/);
            return m ? Number(m[0]) : 1;
        });

        // fallback：無 step → 全部當 Step1
        if (!steps.length) {
            steps = angleArr.map(() => 1);
        }

        // 分段繪圖（依 step）
        const palette = ['#0066ff','#cc0000','#009933','#ff9900','#6600cc'];
        const uniqueSteps = Array.from(new Set(steps));

        const seriesList = uniqueSteps.map((step, idx) => {
            const color = palette[idx % palette.length];

            const pts = [];
            for (let i = 0; i < angleArr.length; i++) {
                if (steps[i] === step) {
                    pts.push([angleArr[i], torqueArr[i]]);
                }
            }

            if (!pts.length) return null;

            return {
                name: `Step${step}`,
                type: "line",
                showSymbol: false,
                connectNulls: true,
                smooth: 0,
                lineStyle: { width: 2, color },
                data: pts
            };
        }).filter(Boolean);


        // ⭐⭐⭐ chart_mode 4 使用「簡單 tooltip」，格式與 chart 6 相同 ⭐⭐⭐
        myChart.setOption({
            tooltip: {
                trigger: "axis",
                axisPointer: { type: "none" }, // 不需要十字線
                formatter: function (params) {
                    const p = params[0];  // 單筆資料
                    const angle  = p.data[0];
                    const torque = p.data[1];

                    // 從 series 名稱取得 step 編號
                    const step = String(p.seriesName).replace("Step", "");

                    return (
                        "Angle: "  + angle  + "<br>" +
                        "Step: "   + step   + "<br>" +
                        "Torque: " + torque
                    );
                }
            },


            xAxis: {
                type: "value",
                name: "Angle",
                splitLine: { show: false }
            },

            yAxis: {
                type: "value",
                name: "Torque",
                splitLine: { show: true }
            },

            series: seriesList
        });

        return;
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

}

// ✅ 自動調整圖表尺寸
window.addEventListener("resize", () => myChart?.resize());
window.addEventListener("orientationchange", () => {
    setTimeout(() => myChart?.resize(), 300);
});

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

            // ✅ 顯示無資料提示
            if (!data.chart_info || data.chart_info === null) {
                chartArea.innerHTML = ``;
                previousChartInfo = null;
                return;
            }

            // ✅ 更新畫面上資料區塊（data_info）
            const info = data.data_info || {};
            const textMap = data.text || {};
            const statusKey = info.fasten_status_text ?? '-';
            

            
            document.getElementById("Job_Name").value =`${info?.job_id ?? '***'}/${info?.job_name ?? '***'}`;
            document.getElementById("Seq_Name").value =`${info?.sequence_id ?? '***'}/${info?.sequence_name ?? '***'}`;
            document.getElementById("Screws").value = info.	last_screw_count + "/" + info.total_screw_count  ?? '***';
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

// 初次載入 + 每 1 秒更新
fetchChartAndRender();
setInterval(fetchChartAndRender, 1000);

// ✅ 畫面初始化
document.addEventListener('DOMContentLoaded', function () {
    const status = <?php echo json_encode((int)($data['data_info']['fasten_status'] ?? 0)); ?>;
    //updateFastenStatusStyle(status);
});

// ✅ fasten_status 顏色控制
function updateFastenStatusStyle(status, targetId = 'fasten_status_color') {
    const element = document.getElementById(targetId);
    if (!element) return;
    if (status === 7 || status === 8) {
        element.style.backgroundColor = 'red';
        element.style.color = 'black';
    } else {
        element.style.backgroundColor = '';
        element.style.color = '';
    }
}

// ✅ 載入後滾動至圖表區
window.onload = function () {
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.scrollIntoView({ behavior: 'auto', block: 'start' });
    }
};
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