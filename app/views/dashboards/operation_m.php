
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
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo  $data['text']['final_torque'] ;?>(<?php echo  $data['text']['N.m'];?>)</div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 6vmin; margin: 5px 0;"><?php echo $data['data_info']['final_fasten_torque'] ?? '-'; ?></div>
                    </div>

                    <?php
                        $color = $data['data_info']['result_status_color_text'] ?? '';
                        $bgStyle = $color ? "background-color: {$color}; color: black;" : '';
                    ?>


                    <div class="item-result w3-display-container" id='fasten_status_color'  style="<?php echo $bgStyle; ?>"  >
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black"><?php echo  $data['text']['final_result'];?></div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 6vmin; margin: 5px 0">     <?php echo $data['text'][$data['data_info']['fasten_status_text']];?></div>            
                    </div>
                </div>
                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo  $data['text']['final_angle'];?></div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 6vmin; margin: 5px 0"><?php echo $data['data_info']['total_fasten_angle'] ?? '-'; ?></div>                        
                    </div>
                    <div class="item-message w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo  $data['text']['final_message'];?></div>
                        <div id="Message" class="w3-display-middle" style="font-size: 5vmin; margin: 5px 0">  
                            <?php if ($data['data_info']['error_message']){?>
                                <?php echo $data['data_info']['error_message']; ?>
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
                <div id="graph" class="display-chart">
                    <div id="chart" style="max-width: 100%; height: 290px;"></div>
                </div>                         
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

function renderChart(chart_mode, chart_info) {
    const chartDom = document.getElementById('chart');
    echarts.dispose(chartDom);
    const myChart = echarts.init(chartDom);

    const x_data_val = chart_info.x_val;
    const y_data_val = chart_info.y_val.map(Number);
    const y_data_val_torque = chart_info.y_val_torque?.map(Number) || [];
    const y_data_val_rpm = chart_info.y_val_rpm?.map(Number) || [];
    const steps = (chart_info.steps || []).map(s => Number(s));

    const stepColors = {
        1: '#0066ff', // 藍
        2: '#cc0000', // 紅
        3: '#009933', // 綠
        4: '#ff9900', // 橘
        5: '#6600cc'  // 紫
    };

    const uniqueSteps = Array.from(new Set(steps)).filter(s => s >= 1 && s <= 5);

    // 建立多色折線圖，每個 Step 一條線
    const generateStepSeries = (dataArr, stepArr, yAxisIndex = 0) => {
        return uniqueSteps.map(stepNum => {
            const seriesData = dataArr.map((v, i) => stepArr[i] === stepNum ? v : null);
            const hasData = seriesData.some(v => v !== null && v !== undefined);

            if (!hasData) return null;

            return {
                name: `Step${stepNum}`,
                type: 'line',
                symbol: 'none',
                connectNulls: true,
                yAxisIndex,
                lineStyle: { width: 2, color: stepColors[stepNum] },
                data: seriesData
            };
        }).filter(Boolean);
    };

    const plainTextTooltip = {
        trigger: 'axis',
        axisPointer: { type: 'none' },
        formatter: function (params) {
            let text = '';
            params.forEach(p => {
                const name = (p.seriesName || '').padEnd(10, ' ');
                const val = (Array.isArray(p.value) ? p.value[1] : p.value ?? '').toString().padStart(8, ' ');
                text += `${name}: ${val}\n`;
            });
            return text.trim();
        }
    };

    if (chart_mode === "5") {
        const rpmMin = chart_info.min_rpm ?? Math.floor(Math.min(...y_data_val_rpm) / 100) * 100;
        const rpmMax = chart_info.max_rpm ?? Math.ceil(Math.max(...y_data_val_rpm) / 100) * 100;

        const torqueSeries = generateStepSeries(y_data_val_torque, steps, 0);
        const rpmSeries = {
            name: 'RPM',
            type: 'line',
            symbol: 'none',
            yAxisIndex: 1,
            lineStyle: { width: 1.5, color: 'orange' },
            data: y_data_val_rpm
        };

        const option = {
            tooltip: plainTextTooltip,
            xAxis: { type: 'category', boundaryGap: false, data: x_data_val, axisLabel: { show: true  } },
            yAxis: [
                { type: 'value', name: 'Torque', splitLine: { show: true } },
                { type: 'value', name: 'RPM', min: rpmMin, max: rpmMax, splitLine: { show: false } }
            ],
            series: [...torqueSeries, rpmSeries]
        };

        myChart.setOption(option);
        return;
    }

    // chart_mode 1~4
    const option = {
        tooltip: plainTextTooltip,
        xAxis: { type: 'category', boundaryGap: false, data: x_data_val, axisLabel: { show: true  } },
        yAxis: { type: 'value', name: 'Torque', splitLine: { show: true } },
        series: generateStepSeries(y_data_val, steps, 0)
    };

    myChart.setOption(option);
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
            

            document.getElementById("Job_Name").value = info.job_id + "/" +info.job_name ?? '***';
            document.getElementById("Seq_Name").value = info.sequence_id + "/" + info.sequence_name ?? '***';
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


