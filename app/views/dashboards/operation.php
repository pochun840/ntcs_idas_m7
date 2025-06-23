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

                    <?php
                        $color = $data['data_info']['result_status_color_text'] ?? '';
                        $bgStyle = $color ? "background-color: {$color}; color: black;" : '';
                    ?>

                    <div class="item-result w3-display-container" id='fasten_status_color'  style="<?php echo $bgStyle; ?>" >
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black"><?php echo $data['text']['final_result'];?></div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 6vmin">
                            <?php echo $data['text'][$data['data_info']['fasten_status_text']];?>
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

function renderChart(chart_mode, chart_info) {
    const chartDom = document.getElementById('chart');
    echarts.dispose(chartDom);  // ✅ 清除舊圖
    myChart = echarts.init(chartDom);
    const language = getCookie('language');

    const x_data_val = chart_info.x_val;
    const y_data_val = chart_info.y_val;
    const y_data_val_torque = chart_info.y_val_torque || [];
    const y_data_val_rpm = chart_info.y_val_rpm || [];

    const translations = {
        "zh-tw": { "Time": "時間", "Torque": "扭力", "Angle": "角度", "RPM": "轉速" },
        "zh-cn": { "Time": "时间", "Torque": "扭力", "Angle": "角度", "RPM": "转速" }
    };
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
    const safeMinTorque = Number(chart_info.min_torque ?? 0);
    const safeMaxTorque = Number(chart_info.max_torque ?? 100);
    const torqueStep = (safeMaxTorque - safeMinTorque) / (tickCount - 1);
    const torqueTicks = Array.from({ length: tickCount }, (_, i) =>
        parseFloat((safeMinTorque + i * torqueStep).toFixed(5))
    );

    if (chart_mode === "5") {
        const torqueRaw = y_data_val_torque.map(Number);
        const rpmRaw = y_data_val_rpm.map(Number);

        let rpmMin = Math.min(...rpmRaw);
        let rpmMax = Math.max(...rpmRaw);
        if (rpmMin === rpmMax) {
            rpmMin = Math.floor(rpmMin * 0.9);
            rpmMax = Math.ceil(rpmMax * 1.1);
        } else {
            rpmMin = Math.floor(rpmMin / 100) * 100;
            rpmMax = Math.ceil(rpmMax / 100) * 100;
        }

        const option = {
            tooltip: {
                trigger: 'axis',
                formatter: function (params) {
                    return params.map(p => {
                        const unit = p.seriesName === 'Torque' ? 'Nm' : 'RPM';
                        return `<span style="color:${p.color}">${p.seriesName}:</span> ${p.value} ${unit}<br>`;
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
            yAxis: [
                {
                    type: 'value',
                    name: `${labels.Torque} (Nm)`,
                    position: 'left',
                    min: safeMinTorque,
                    max: safeMaxTorque,
                    interval: torqueStep,
                    splitNumber: tickCount - 1,
                    axisLabel: {
                        color: '#000',
                        fontSize: 12,
                        formatter: val => parseFloat(val.toFixed(5)).toString()
                    },
                    splitLine: { show: true }
                },
                {
                    type: 'value',
                    name: `${labels.RPM} (RPM)`,
                    position: 'right',
                    min: rpmMin,
                    max: rpmMax,
                    interval: 100,
                    splitNumber: 7,
                    axisLabel: { color: '#000', fontSize: 12 },
                    splitLine: { show: false }
                }
            ],
            dataZoom: [
                { type: 'inside', start: 0, end: 100 },
                { type: 'slider', show: false, start: 0, end: 100 }
            ],
            series: [
                {
                    name: 'Torque',
                    type: 'line',
                    symbol: 'none',
                    yAxisIndex: 0,
                    itemStyle: { color: 'red' },
                    lineStyle: { width: 0.75 },
                    data: torqueRaw
                },
                {
                    name: 'RPM',
                    type: 'line',
                    symbol: 'none',
                    yAxisIndex: 1,
                    itemStyle: { color: 'blue' },
                    lineStyle: { width: 0.75 },
                    data: rpmRaw
                }
            ]
        };

        myChart.setOption(option);
        return;
    }

    // 非 chart_mode 5 的處理
    const option = {
        tooltip: {
            trigger: 'axis',
            formatter: function (params) {
                return params.map(p => `<span style="color:red;">${yTitle}:</span> ${p.value}<br>`).join('');
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
        yAxis: (
            ["1", "4"].includes(chart_mode) ? [{
                type: 'value',
                name: yTitle,
                min: torqueTicks[0],
                max: torqueTicks[tickCount - 1],
                interval: torqueStep,
                splitNumber: tickCount - 1,
                axisLabel: { color: '#000', fontSize: 12 },
                splitLine: { show: true }
            }] : [ {
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
        series: [{
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


let previousChartInfo = null;

function fetchChartAndRender() {
    console.log("Fetching chart at", new Date().toLocaleTimeString());

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
            /*if (!data.chart_info || data.chart_info === null) {
                chartArea.innerHTML = `
                    <div style="text-align:center; padding:2em; font-size:16px; color:gray;">
                        ${messages[language] || messages['en']}
                        <br><button onclick="fetchChartAndRender()" style="margin-top:10px;padding:6px 12px;">
                            🔄 ${language === 'zh-tw' ? '重新整理' : language === 'zh-cn' ? '重新加载' : 'Refresh'}
                        </button>
                    </div>`;
                previousChartInfo = null;
                return;
            }*/

            // ✅ 更新畫面上資料區塊（data_info）
            const info = data.data_info || {};
            const textMap = data.text || {};
            const statusKey = info.fasten_status_text ?? '-';

            document.getElementById("Job_Name").value = info.job_name ?? '***';
            document.getElementById("Seq_Name").value = info.sequence_name ?? '***';
            document.getElementById("Screws").value = info.total_screw_count ?? '***';
            document.getElementById("Target_Torque").innerText = info.final_fasten_torque ?? '-';
            document.getElementById("Target_Angle").innerText = info.final_fasten_angle ?? '-';
            document.getElementById("Torque_Result").innerText = textMap[statusKey] ?? statusKey;
            document.getElementById("Message").innerText = info.error_message ?? '';

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

// 旋轉或調整螢幕大小時 → 圖表會自動調整 - Khi xoay hoặc resize màn hình → biểu đồ tự điều chỉnh lại
window.addEventListener("resize", () => myChart?.resize?.());
 
</script>


