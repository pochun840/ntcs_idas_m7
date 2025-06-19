<?php require APPROOT . 'views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_operation_m.css" type="text/css">
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
                <input type="text" id="Job_Name" name="Job_Name" size="16" maxlength="20" value="" disabled>

                <label style="color: #fff;" for="seq_name"><?php echo  $data['text']['sequence'];?>:</label>
                <input type="text" id="Seq_Name" name="Seq_Name" size="16" maxlength="20" value="" disabled>

                <label style="color: #fff;" for="screw"><?php echo  $data['text']['screws'];?>:</label>
                <input type="text" id="Screws" name="Screws" size="4" maxlength="20" value="" disabled>
            </div>
            
            <div class="operation-setting">
                <div class="column">
                    <div class="item-target-torque w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo  $data['text']['final_torque'] ;?>(<?php echo  $data['text']['N.m'];?>)</div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 6vmin; margin: 5px 0;"></div>
                    </div>
                    <div class="item-result w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black"><?php echo  $data['text']['final_result'];?></div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 6vmin; margin: 5px 0"></div>            
                    </div>
                </div>
                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo  $data['text']['final_angle'];?></div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 6vmin; margin: 5px 0"></div>                        
                    </div>
                    <div class="item-message w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo  $data['text']['final_message'];?></div>
                        <div id="Message" class="w3-display-middle" style="font-size: 5vmin; margin: 5px 0"></div>                                    
                    </div>
                </div>
            </div>
            <div class="chart-setting">
                <div class="button-chart">
                    <?php foreach($data['chart_menu_arr'] as $k_menu =>$v_menu){?>
                            <button type="button" <?php if($data['chart_mode'] == $k_menu){ echo $class ='class="btn-chart active"';}else { echo $class ='class="btn-chart"'; }?>   id= '<?php echo $v_menu['id'];?>' onclick="chart_type('<?php echo $v_menu['id'];?>')" ><?php echo  $data['text'][$v_menu['name']];?></button>
                    <?php }?>
                </div>
                <div id="graph" class="display-chart">
                    <div id="chart" style="max-width: 100%; height: 290px;"></div>
                </div>                         
            </div>
        </div>
    </div>
</div>


<script>
// ✅ 切換圖表按鈕及更新 URL
function chart_type(argument) {
    const chartMap = {
        "torque_time": 1,
        "angle_time": 2,
        "rpm_time": 3,
        "torque_angle": 4,
        "torque_speed": 5
    };

    const chart = chartMap[argument];
    if (!chart) return;

    // 切換按鈕樣式
    const buttons = document.getElementsByClassName("btn-chart");
    for (const btn of buttons) btn.classList.remove("active");
    const activeButton = document.getElementById(argument);
    if (activeButton) activeButton.classList.add("active");

    // 更新 URL
    const currentUrl = window.location.href;
    const chartIndex = currentUrl.indexOf('chart=');
    const nextChartValue = `chart=${chart}`;
    const nextinfo_url = chartIndex !== -1
        ? currentUrl.substring(0, chartIndex) + nextChartValue
        : currentUrl + (currentUrl.includes('?') ? '&' : '?') + nextChartValue;

    // 觸發跳轉
    fetch(nextinfo_url)
        .then(res => res.ok && window.location.assign(nextinfo_url));
}

// ✅ 初始化圖表語系標題
const language = getCookie('language');
const chartMode = "<?php echo $data['chart_mode']; ?>";

let x_title = '<?php echo addslashes($data['echart_name'][1]); ?>';
let y_title = '<?php echo addslashes($data['echart_name'][0]); ?>';

const translations = {
    "zh-tw": { "Time(MS)": "時間", "Angle": "角度", "Torque": "扭力", "RPM": "轉速" },
    "zh-cn": { "Time(MS)": "时间", "Angle": "角度", "Torque": "扭力", "RPM": "转速" }
};
const labelsMap = translations[language] || {};
x_title = labelsMap[x_title] || x_title;
y_title = labelsMap[y_title] || y_title;

// ✅ 主圖表繪製函式
function renderChart(chart_mode, chart_info) {
    const myChart = echarts.init(document.getElementById('chart'));

    const x_data_val = chart_info.x_val;
    const y_data_val = chart_info.y_val;
    const y_data_val_torque = chart_info.y_val_torque || [];
    const y_data_val_rpm = chart_info.y_val_rpm || [];

    const labels = translations[language] || { "Time": "Time", "Torque": "Torque", "Angle": "Angle", "RPM": "RPM" };

    let xTitle = '', yTitle = '';
    switch (chart_mode) {
        case "1": xTitle = labels.Time; yTitle = labels.Torque; break;
        case "2": xTitle = labels.Time; yTitle = labels.Angle; break;
        case "3": xTitle = labels.Time; yTitle = labels.RPM; break;
        case "4": xTitle = labels.Angle; yTitle = labels.Torque; break;
        case "5": xTitle = labels.Time; break;
        default: xTitle = 'X'; yTitle = 'Y';
    }

    const tickCount = 6;
    const minTorque = Number(chart_info.min_torque ?? 0);
    const maxTorque = Number(chart_info.max_torque ?? 100);
    const torqueStep = (maxTorque - minTorque) / (tickCount - 1);
    const torqueTicks = Array.from({ length: tickCount }, (_, i) =>
        parseFloat((minTorque + i * torqueStep).toFixed(5))
    );

    let torqueMid = 0, rpmMid = 0;
    if (chart_mode === "5") {
        const alignIndex = Math.floor(Math.min(y_data_val_torque.length, y_data_val_rpm.length) / 2);
        torqueMid = Number(y_data_val_torque[alignIndex] ?? 0);
        rpmMid = Number(y_data_val_rpm[alignIndex] ?? 0);
    }

    const option = {
        tooltip: {
            trigger: 'axis',
            formatter: function (params) {
                return params.map(p => {
                    if (chart_mode === "5") {
                        if (p.seriesName === 'Torque') return `<span style="color:red;">${labels.Torque}:</span> ${p.value} Nm<br>`;
                        if (p.seriesName === 'RPM') return `<span style="color:blue;">${labels.RPM}:</span> ${p.value} RPM<br>`;
                    }
                    return `<span style="color:red;">${yTitle}:</span> ${p.value}<br>`;
                }).join('');
            }
        },
        grid: {
            left: '10%', right: '10%', top: '10%', bottom: '15%', containLabel: true
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            name: xTitle,
            data: x_data_val
        },
        yAxis: chart_mode === "5" ? [
            {
                type: 'value', name: `${labels.Torque} (Nm)`, position: 'left',
                min: torqueTicks[0], max: torqueTicks[tickCount - 1], interval: torqueStep,
                axisLabel: { color: '#000', fontSize: 12 },
                splitLine: { show: true }, alignTicks: true
            },
            {
                type: 'value', name: `${labels.RPM} (RPM)`, position: 'right',
                min: Number(chart_info.min_rpm ?? 0), max: Number(chart_info.max_rpm ?? 700),
                interval: 100, splitNumber: 7,
                axisLabel: { color: '#000', fontSize: 12 },
                splitLine: { show: true }, alignTicks: true
            }
        ] : (["1", "4"].includes(chart_mode) ? [{
            type: 'value', name: yTitle,
            min: torqueTicks[0], max: torqueTicks[tickCount - 1], interval: torqueStep,
            axisLabel: { color: '#000', fontSize: 12 }, splitLine: { show: true }
        }] : [{
            type: 'value', name: yTitle,
            axisLabel: { color: '#000', fontSize: 12 }, splitLine: { show: true }
        }]),
        dataZoom: [
            { type: 'inside', start: 0, end: 100 },
            { type: 'slider', show: false, start: 0, end: 100 }
        ],
        series: chart_mode === "5" ? [
            {
                name: 'Torque', type: 'line', symbol: 'none', yAxisIndex: 0,
                itemStyle: { color: 'red' }, lineStyle: { width: 0.75 }, data: y_data_val_torque
            },
            {
                name: 'RPM', type: 'line', symbol: 'none', yAxisIndex: 1,
                itemStyle: { color: 'blue' }, lineStyle: { width: 0.75 }, data: y_data_val_rpm
            }
        ] : [{
            name: '', type: 'line', symbol: 'none',
            itemStyle: { color: 'red' }, lineStyle: { width: 0.75 }, data: y_data_val
        }]
    };

    myChart.setOption(option);
}

// ✅ 每 2 秒更新圖表
function fetchChartAndRender() {
    fetch(`?url=Dashboards/operation&chart=${chartMode}&ajax=1`)
        .then(res => res.json())
        .then(data => data.chart_info && renderChart(chartMode, data.chart_info))
        .catch(err => console.error("AJAX ERROR", err));
}
fetchChartAndRender();
setInterval(fetchChartAndRender, 2000);

// ✅ fasten_status 狀態顏色變更
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

// ✅ 初始化 fasten_status 樣式

document.addEventListener('DOMContentLoaded', function () {
    const status = <?php echo json_encode((int)($data['data_info']['fasten_status'] ?? 0)); ?>;
    updateFastenStatusStyle(status);
});

</script>

</body>

</html>