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
                <input type="text" id="Job_Name" name="Job_Name" size="15" maxlength="20" value="<?php echo $data['data_info']['job_name'] ?? '***'; ?>" disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="seq_name"><?php echo $data['text']['seq_name'];?> :</label>&nbsp;
                <input type="text" id="Seq_Name" name="Seq_Name" size="15" maxlength="20" value="<?php echo $data['data_info']['sequence_name'] ?? '***';?>" disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="screw"><?php echo $data['text']['screws'];?> :</label>&nbsp;
                <input type="text" id="Screws" name="Screws" size="4" maxlength="20" value="<?php echo $data['data_info']['total_screw_count'] ?? '***';?>" disabled>
            </div>
            
            <div class="operation-setting">
                <div class="column">
                    <div class="item-target-torque w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $data['text']['final_torque'] ;?>(<?php echo $data['text']['N.m'];?>)</div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 6vmin"><?php echo $data['data_info']['final_fasten_torque'] ?? '-'; ?></div>
                    </div>
                    <div class="item-result w3-display-container" id='fasten_status_color'>
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black"><?php echo $data['text']['final_result'];?></div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 6vmin">
                            <?php echo $data['status_arr'][$data['data_info']['fasten_status'] ?? 0] ?? '-';?>
                        </div>            
                    </div>
                </div>
                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $data['text']['final_angle'];?></div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 6vmin"><?php echo $data['data_info']['final_fasten_angle'] ?? '-'; ?></div>                        
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
                                    class="btn-chart <?php echo ($data['chart_mode'] == $k_menu) ? 'active' : ''; ?>"
                                    id="<?php echo $v_menu['id']; ?>"
                                    onclick="chart_type('<?php echo $v_menu['id']; ?>')">
                                    <?php echo isset($data['text'][$v_menu['name']]) ? $data['text'][$v_menu['name']] : $v_menu['name']; ?>
                                </button>
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

// ✅ 主圖表繪製函式（吃 AJAX 拿到的 chart_info）
function renderChart(chart_mode, chart_info) {
    const myChart = echarts.init(document.getElementById('chart'));
    const language = getCookie('language');

    const x_data_val = chart_info.x_val;
    const y_data_val = chart_info.y_val;
    const y_data_val_torque = chart_info.y_val_torque || [];
    const y_data_val_rpm = chart_info.y_val_rpm || [];

    // 多語系軸標籤
    const translations = {
        "zh-tw": { "Time": "時間", "Torque": "扭力", "Angle": "角度", "RPM": "轉速" },
        "zh-cn": { "Time": "时间", "Torque": "扭力", "Angle": "角度", "RPM": "转速" }
    };
    const labels = translations[language] || { "Time": "Time", "Torque": "Torque", "Angle": "Angle", "RPM": "RPM" };

    // 設定主軸標題
    let xTitle = '', yTitle = '';
    switch (chart_mode) {
        case "1": xTitle = labels.Time; yTitle = labels.Torque; break;
        case "2": xTitle = labels.Time; yTitle = labels.Angle; break;
        case "3": xTitle = labels.Time; yTitle = labels.RPM; break;
        case "4": xTitle = labels.Angle; yTitle = labels.Torque; break;
        case "5": xTitle = labels.Time; break;
        default: xTitle = 'X'; yTitle = 'Y';
    }

    // 計算 torque 座標刻度
    const tickCount = 6;
    const safeMinTorque = Number(chart_info.min_torque ?? 0);
    const safeMaxTorque = Number(chart_info.max_torque ?? 100);
    const torqueStep = (safeMaxTorque - safeMinTorque) / (tickCount - 1);
    const torqueTicks = Array.from({ length: tickCount }, (_, i) =>
        parseFloat((safeMinTorque + i * torqueStep).toFixed(5))
    );

    // chart_mode 5 特有 ➤ 中間值抓出做 markLine
    let torqueMid = 0, rpmMid = 0;
    if (chart_mode === "5") {
        const alignIndex = Math.floor(Math.min(y_data_val_torque.length, y_data_val_rpm.length) / 2);
        torqueMid = Number(y_data_val_torque[alignIndex] ?? 0);
        rpmMid = Number(y_data_val_rpm[alignIndex] ?? 0);
    }

    // ✅ 畫圖設定
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
            left: '10%',
            right: '10%',
            top: '10%',
            bottom: '15%',
            containLabel: true
        },
        xAxis: {
            type: 'category',
            boundaryGap: false,
            name: xTitle,
            data: x_data_val
        },
        yAxis: chart_mode === "5" ? [
            {
                type: 'value',
                name: `${labels.Torque} (Nm)`,
                position: 'left',
                min: torqueTicks[0],
                max: torqueTicks[tickCount - 1],
                interval: torqueStep,
                splitNumber: tickCount - 1,
                alignTicks: true,
                axisLabel: { color: '#000', fontSize: 12 },
                axisLine: { show: false },
                axisTick: { show: true },
                splitLine: { show: true }
            },
            {
                type: 'value',
                name: `${labels.RPM} (RPM)`,
                position: 'right',
                min: Number(chart_info.min_rpm ?? 0),
                max: Number(chart_info.max_rpm ?? 700),
                interval: 100,
                splitNumber: 7,
                alignTicks: true,
                axisLabel: { color: '#000', fontSize: 12 },
                axisLine: { show: false },
                axisTick: { show: true },
                splitLine: { show: true }
            }
        ] : (
            ["1", "4"].includes(chart_mode) ? [{
                type: 'value',
                name: yTitle,
                min: torqueTicks[0],
                max: torqueTicks[tickCount - 1],
                interval: torqueStep,
                splitNumber: tickCount - 1,
                axisLabel: { color: '#000', fontSize: 12 },
                splitLine: { show: true }
            }] : [{
                type: 'value',
                name: yTitle,
                axisLabel: { color: '#000', fontSize: 12 },
                splitLine: { show: true }
            }]
        ),
        dataZoom: [
            { type: 'inside', start: 0, end: 100 },
            { type: 'slider', show: false, start: 0, end: 100 }
        ],
        series: chart_mode === "5" ? [
            {
                name: 'Torque',
                type: 'line',
                symbol: 'none',
                yAxisIndex: 0,
                itemStyle: { color: 'red' },
                lineStyle: { width: 0.75 },
                data: y_data_val_torque,
                /*markLine: {
                    //silent: true,
                    //lineStyle: { type: 'dashed', color: '#999' },
                    /*label: {
                        formatter: `${labels.Torque} ≈ ${torqueMid.toFixed(5)} / ${labels.RPM} ≈ ${rpmMid}`,
                        color: '#333',
                        fontSize: 11
                    },*/
                    /*data: [{ yAxis: torqueMid }]
                }*/
            },
            {
                name: 'RPM',
                type: 'line',
                symbol: 'none',
                yAxisIndex: 1,
                itemStyle: { color: 'blue' },
                lineStyle: { width: 0.75 },
                data: y_data_val_rpm
            }
        ] : [{
            name: '',
            type: 'line',
            symbol: 'none',
            itemStyle: { color: 'red' },
            lineStyle: { width: 0.75 },
            data: y_data_val
        }]
    };

    myChart.setOption(option);
}

// ✅ 每 2 秒從後端抓資料並重繪圖表
function fetchChartAndRender() {
    fetch(`?url=Dashboards/operation&chart=${chartMode}&ajax=1`)
        .then(res => res.json())
        .then(data => {
            if (data.chart_info) {
                renderChart(chartMode, data.chart_info);
            }
        })
        .catch(err => console.error("AJAX ERROR", err));
}
fetchChartAndRender();
setInterval(fetchChartAndRender, 2000);

// ✅ 根據 fasten_status = 7 or 8 改背景紅
function updateFastenStatusStyle(status, targetId = 'fasten_status_color') {
    const element = document.getElementById(targetId);
    if (!element) return;

    if (status === 7 || status === 8) {
        element.style.backgroundColor = 'red';
        element.style.color = 'black'; // 字改黑
    } else {
        element.style.backgroundColor = '';
        element.style.color = '';
    }
}
document.addEventListener('DOMContentLoaded', function () {
    const status = <?php echo json_encode((int)($data['data_info']['fasten_status'] ?? 0)); ?>;
    updateFastenStatusStyle(status);
});
</script>


