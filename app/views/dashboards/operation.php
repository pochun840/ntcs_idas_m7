<?php require APPROOT . 'views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_operation.css" type="text/css">

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
                <input type="text" id="Job_Name" name="Job_Name" size="15" maxlength="20" value="<?php echo $data['data_info']['job_name'];?>" disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="seq_name"><?php echo $text['seq_name'];?> :</label>&nbsp;
                <input type="text" id="Seq_Name" name="Seq_Name" size="15" maxlength="20" value="<?php echo $data['data_info']['sequence_name'];?>" disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="screw"><?php echo $text['screws'];?> :</label>&nbsp;
                <input type="text" id="Screws" name="Screws" size="4" maxlength="20" value="<?php echo $data['data_info']['total_screw_count'];?>" disabled>
            </div>
            
            <div class="operation-setting">
                <div class="column">
                    <div class="item-target-torque w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $text['final_torque'] ;?>(<?php echo $text['N.m'];?>)</div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 6vmin"><?php echo $data['data_info']['final_fasten_torque'];?></div>
                    </div>
                    <div class="item-result w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black"><?php echo $text['final_result'];?></div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 6vmin">
                            <?php echo $data['status_arr'][$data['data_info']['fasten_status']];?>
                        </div>            
                    </div>
                </div>
                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $text['final_angle'];?></div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 6vmin"><?php echo $data['data_info']['final_fasten_angle'];?></div>                        
                    </div>
                    <div class="item-message w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $text['final_message'];?></div>
                        <div id="Message" class="w3-display-middle" style="font-size: 28px">
                        <?php if ($data['data_info']['error_message']) { ?>
                            <?php echo $data['data_info']['error_message']; ?>
                        <?php } else { ?>
                            <?php echo "N/A"; ?>
                        <?php } ?>
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

    // 定義 argument 與 chart 的對應關係
    const chartMapping = {
        "torque_time": 1,
        "angle_time": 2,
        "rpm_time": 3,
        "torque_angle": 4,
        "torque_speed": 5
    };

    // 獲取對應的 chart 值
    const chart = chartMapping[argument];
    if (!chart) {
        console.error("Invalid argument:", argument);
        return;
    }

    // 處理按鈕的 active 狀態
    updateActiveButton(argument);

    // 更新 URL
    const nextinfo_url = updateChartUrl(currentUrl, chart);

    // 發送請求並跳轉
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

// 更新按鈕的 active 狀態
function updateActiveButton(argument) {
    const buttons = document.getElementsByClassName("btn-chart");
    Array.from(buttons).forEach(button => button.classList.remove("active"));
    const activeButton = document.getElementById(argument);
    if (activeButton) {
        activeButton.classList.add("active");
    }
}

// 更新 URL 並返回新的 URL
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


// 取得語言設定
var language = getCookie('language');

// 初始化 ECharts
var myChart = echarts.init(document.getElementById('chart'));

//取得資料
var x_data_val = <?php echo $data['chart_info']['x_val']; ?>;
var y_data_val = <?php echo $data['chart_info']['y_val']; ?>;
var x_title = '<?php echo addslashes($data['echart_name'][1]); ?>';
var y_title = '<?php echo addslashes($data['echart_name'][0]); ?>';
var y_data_val_torque = <?php echo !empty($data['chart_info']['y_val_torque']) ? $data['chart_info']['y_val_torque'] : '[]'; ?>;
var y_data_val_rpm = <?php echo !empty($data['chart_info']['y_val_rpm']) ? $data['chart_info']['y_val_rpm'] : '[]'; ?>;
var chart_mode = '<?php echo $data['chart_mode']; ?>';

const translations = {
    "zh-tw": { "Time(MS)": "時間", "Angle": "角度", "Torque": "扭力", "RPM": "轉速" },
    "zh-cn": { "Time(MS)": "时间", "Angle": "角度", "Torque": "扭力", "RPM": "转速" }
};

// 翻譯函數
function translateTitles(language, xTitle, yTitle) {
    const langTranslations = translations[language];
    if (langTranslations) {
        xTitle = langTranslations[xTitle] || xTitle;
        yTitle = langTranslations[yTitle] || yTitle;
    }
    return { xTitle, yTitle };
}

// 翻譯標題
({ xTitle: x_title, yTitle: y_title } = translateTitles(language, x_title, y_title));

// 計算範圍
const min_torque = y_data_val_torque.length > 0 ? Math.min(...y_data_val_torque) : 0;
const max_torque = y_data_val_torque.length > 0 ? Math.max(...y_data_val_torque) : 0;
const min_rpm = y_data_val_rpm.length > 0 ? Math.min(...y_data_val_rpm) : 0;
const max_rpm = y_data_val_rpm.length > 0 ? Math.max(...y_data_val_rpm) : 0;

let rpmMinAdjusted = min_rpm;
let rpmMaxAdjusted = max_rpm;

if (chart_mode === "5" && y_data_val_torque.length > 0 && y_data_val_rpm.length > 0) {
    if (max_torque !== min_torque && max_rpm !== min_rpm) {
        const ratio = (max_rpm - min_rpm) / (max_torque - min_torque);
        rpmMinAdjusted = min_torque * ratio + min_rpm;
        rpmMaxAdjusted = max_torque * ratio + min_rpm;

        rpmMinAdjusted = Math.max(rpmMinAdjusted, min_rpm);
        rpmMaxAdjusted = Math.max(rpmMaxAdjusted, max_rpm);
    } else {
        console.warn("One of the Y-axis data has the same min and max value, cannot calculate ratio.");
    }
}

function formatAxisLabel(value) {
    return value % 1 === 0 ? value : value.toFixed(2);
}

const option = {
    title: { text: '' },
    tooltip: {
        trigger: 'axis',
        position: pt => [pt[0], '10%'],
        formatter: params => {
            if (chart_mode === "5") {
                return params.map(param => {
                    const color = param.seriesName === 'Torque' ? 'rgb(255, 0, 0)' : 'rgb(0, 0, 255)';
                    const unit = param.seriesName === 'Torque' ? 'Nm' : 'RPM';
                    return `<span style="color: ${color};">${param.seriesName}: </span>${param.value} ${unit}<br>`;
                }).join('');
            } else {
                const yAxisLabels = { "1": "Torque", "2": "Angle", "3": "Rpm", "4": "Torque" };
                const yAxisLabel = yAxisLabels[chart_mode] || '';
                return params.map(param => `<span style="color: rgb(255, 0, 0);">${yAxisLabel}: </span>${param.value}<br>`).join('');
            }
        }
    },
    xAxis: {
        type: 'category',
        boundaryGap: false,
        data: x_data_val
    },
    yAxis: chart_mode === "5" ? [
        {
            type: 'value',
            name: 'Torque',
            min: min_torque,
            max: max_torque,
            alignTicks: true,
            position: 'left',
            axisLabel: { formatter: formatAxisLabel }
        },
        {
            type: 'value',
            name: 'RPM',
            position: 'right',
            alignTicks: true,
            axisLabel: { formatter: formatAxisLabel },
            min: () => {
                if (!Number.isFinite(rpmMinAdjusted) || rpmMinAdjusted > rpmMaxAdjusted) return min_rpm;
                return rpmMinAdjusted;
            },
            max: () => {
                if (!Number.isFinite(rpmMaxAdjusted) || rpmMaxAdjusted < rpmMinAdjusted) return max_rpm;
                return rpmMaxAdjusted;
            },
        }
    ] : [
        {
            type: 'value',
            name: y_title,
            boundaryGap: [0, '100%']
        }
    ],
    dataZoom: [{ type: 'inside', start: 0, end: 100 }],
    series: chart_mode === "5" ? [
        {
            name: 'Torque',
            type: 'line',
            symbol: 'none',
            sampling: 'average',
            yAxisIndex: 0,
            itemStyle: { color: 'rgb(255,0,0)' },
            lineStyle: { width: 0.75 },
            data: y_data_val_torque
        },
        {
            name: 'RPM',
            type: 'line',
            symbol: 'none',
            sampling: 'average',
            yAxisIndex: 1,
            itemStyle: { color: 'rgb(0,0,255)' },
            lineStyle: { width: 0.75 },
            data: y_data_val_rpm
        }
    ] : [
        {
            name: '',
            type: 'line',
            symbol: 'none',
            itemStyle: { color: 'rgb(255,0,0)' },
            lineStyle: { width: 0.75 },
            data: y_data_val
        }
    ]
};

// 設置圖表
myChart.setOption(option);


</script>

</body>

</html>