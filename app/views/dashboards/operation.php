<body>
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['operation_result'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>
    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:18px;color: #fff; padding-left: 1%" for="job_name"><?php echo $text['job_name'];?> :</label>&nbsp;
                <input type="text" id="Job_Name" name="Job_Name" size="15" maxlength="20" value="<?php echo $data['data_info']['job_name'] ?? '***'; ?>" disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="seq_name"><?php echo $text['seq_name'];?> :</label>&nbsp;
                <input type="text" id="Seq_Name" name="Seq_Name" size="15" maxlength="20" value="<?php echo $data['data_info']['sequence_name'] ?? '***';?>" disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="screw"><?php echo $text['screws'];?> :</label>&nbsp;
                <input type="text" id="Screws" name="Screws" size="4" maxlength="20" value="<?php echo $data['data_info']['total_screw_count'] ?? '***';?>" disabled>
            </div>
            
            <div class="operation-setting">
                <div class="column">
                    <div class="item-target-torque w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $text['final_torque'] ;?>(<?php echo $text['N.m'];?>)</div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 6vmin"><?php echo $data['data_info']['final_fasten_torque'] ?? '-'; ?></div>
                    </div>
                    <div class="item-result w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black"><?php echo $text['final_result'];?></div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 6vmin">
                            <?php echo $data['status_arr'][$data['data_info']['fasten_status'] ?? 0] ?? '-';?>
                        </div>            
                    </div>
                </div>
                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $text['final_angle'];?></div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 6vmin"><?php echo $data['data_info']['final_fasten_angle'] ?? '-'; ?></div>                        
                    </div>
                    <div class="item-message w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $text['final_message'];?></div>
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

                            <?php foreach($data['chart_menu_arr'] as $k_menu =>$v_menu){?>
                                <button type="button" <?php if($data['chart_mode'] == $k_menu){ echo $class ='class="btn-chart active"';}else { echo $class ='class="btn-chart"'; }?>   id= '<?php echo $v_menu['id'];?>' onclick="chart_type('<?php echo $v_menu['id'];?>')" ><?php echo $text[$v_menu['name']];?></button>
                            <?php }?>
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
// change button background coler
function changeBackgroundColor(button) {
    var buttons = document.getElementsByClassName('btn-chart');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove('active');
    }
    button.classList.add('active');
}

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
    if (!chart) {
        console.error("Invalid argument:", argument);
        return;
    }
    updateActiveButton(argument);
    const nextinfo_url = updateChartUrl(currentUrl, chart);
    fetch(nextinfo_url, { method: 'GET' })
        .then(response => {
            if (response.ok) {
                window.location.assign(nextinfo_url);
            } else {
                console.error("Failed to fetch:", response.statusText);
            }
        })
        .catch(error => console.error("Error fetching URL:", error));
}

function updateActiveButton(argument) {
    const buttons = document.getElementsByClassName("btn-chart");
    Array.from(buttons).forEach(button => button.classList.remove("active"));
    const activeButton = document.getElementById(argument);
    if (activeButton) {
        activeButton.classList.add("active");
    }
}

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

var language = getCookie('language');
var myChart = echarts.init(document.getElementById('chart'));

var x_data_val = <?php echo $data['chart_info']['x_val']; ?>;
var y_data_val = <?php echo $data['chart_info']['y_val']; ?>;
var y_data_val_torque = <?php echo !empty($data['chart_info']['y_val_torque']) ? $data['chart_info']['y_val_torque'] : '[]'; ?>;
var y_data_val_rpm = <?php echo !empty($data['chart_info']['y_val_rpm']) ? $data['chart_info']['y_val_rpm'] : '[]'; ?>;
var chart_mode = '<?php echo $data['chart_mode']; ?>';

const translations = {
    "zh-tw": { "Time": "時間", "Torque": "扭力", "Angle": "角度", "RPM": "轉速" },
    "zh-cn": { "Time": "时间", "Torque": "扭力", "Angle": "角度", "RPM": "转速" }
};
const labels = translations[language] || { "Time": "Time", "Torque": "Torque", "Angle": "Angle", "RPM": "RPM" };

let xTitle = '';
let yTitle = '';
switch (chart_mode) {
    case "1": xTitle = labels.Time; yTitle = labels.Torque; break;
    case "2": xTitle = labels.Time; yTitle = labels.Angle; break;
    case "3": xTitle = labels.Time; yTitle = labels.RPM; break;
    case "4": xTitle = labels.Angle; yTitle = labels.Torque; break;
    case "5": xTitle = labels.Time; break;
    default: xTitle = 'X'; yTitle = 'Y';
}

const minTorque = Math.min(...y_data_val_torque);
const maxTorque = Math.max(...y_data_val_torque);
const minRPM = Math.min(...y_data_val_rpm);
const maxRPM = Math.max(...y_data_val_rpm);

const tickCount = 6;
const torqueStep = (maxTorque - minTorque) / (tickCount - 1);
const torqueTicks = Array.from({ length: tickCount }, (_, i) => parseFloat((minTorque + i * torqueStep).toFixed(2)));
const ratio = (maxRPM - minRPM) / (maxTorque - minTorque);
const rpmTicks = torqueTicks.map(t => parseFloat((minRPM + (t - minTorque) * ratio).toFixed(2)));

const option = {
    tooltip: {
        trigger: 'axis',
        formatter: function(params) {
            let html = '';
            params.forEach(p => {
                if (chart_mode === "5") {
                    if (p.seriesName === 'Torque') {
                        html += `<span style=\"color:red;\">${labels.Torque}:</span> ${p.value} Nm<br>`;
                    } else if (p.seriesName === 'RPM') {
                        html += `<span style=\"color:blue;\">${labels.RPM}:</span> ${p.value} RPM<br>`;
                    }
                } else {
                    html += `<span style=\"color:red;\">${yTitle}:</span> ${p.value}<br>`;
                }
            });
            return html;
        }
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
            //name: '',
            name: `${labels.Torque} (Nm)`, // ✅ 左側加上 Torque 單位
            position: 'left',
            min: torqueTicks[0],
            max: torqueTicks[tickCount - 1],
            interval: torqueStep, // ✅ 強制使用相同間距
            splitNumber: tickCount - 1,
            axisLabel: { formatter: '{value}' }
        },
        {
            type: 'value',
            name: `${labels.RPM}`,         // ✅ 右側加上 RPM
            position: 'right',
            min: rpmTicks[0],
            max: rpmTicks[tickCount - 1],
            interval: parseFloat((rpmTicks[1] - rpmTicks[0]).toFixed(2)), // ✅ 使用相同 tick 數量
            splitNumber: tickCount - 1,
            axisLabel: { formatter: '{value}' },
            axisLabel: { formatter: val => parseInt(val) }  // ✅ 顯示為整數
        }
    ] : [
        {
            type: 'value',
            name: yTitle,
            boundaryGap: [0, '100%']
        }
    ],
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
            data: y_data_val_torque
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
    ] : [
        {
            name: '',
            type: 'line',
            symbol: 'none',
            itemStyle: { color: 'red' },
            lineStyle: { width: 0.75 },
            data: y_data_val
        }
    ]
};

myChart.setOption(option);
</script>
