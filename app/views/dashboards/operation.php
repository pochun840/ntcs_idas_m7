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
                <input type="text" id="Job_Name" name="Job_Name" size="15" maxlength="20" value="" disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="seq_name"><?php echo $text['seq_name'];?> :</label>&nbsp;
                <input type="text" id="Seq_Name" name="Seq_Name" size="15" maxlength="20" value="" disabled>

                <label style="font-size:18px;color: #fff; padding-left: 2%" for="screw"><?php echo $text['screws'];?> :</label>&nbsp;
                <input type="text" id="Screws" name="Screws" size="4" maxlength="20" value="" disabled>
            </div>
            
            <div class="operation-setting">
                <div class="column">
                    <div class="item-target-torque w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $text['final_torque'] ;?>(<?php echo $text['N.m'];?>)</div>
                        <div id="Target_Torque" class="w3-display-middle" style="font-size: 6vmin"></div>
                    </div>
                    <div class="item-result w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-black"><?php echo $text['final_result'];?></div>
                        <div id="Torque_Result" class="w3-display-middle" style="font-size: 6vmin"></div>            
                    </div>
                </div>
                <div class="column">
                    <div class="item-targer-angle w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $text['final_angle'];?></div>
                        <div id="Target_Angle" class="w3-display-middle" style="font-size: 6vmin"></div>                        
                    </div>
                    <div class="item-message w3-display-container">
                        <div class="w3-display-topmiddle w3-border-top w3-border-bottom w3-border-red"><?php echo $text['final_message'];?></div>
                        <div id="Message" class="w3-display-middle" style="font-size: 28px"></div>                                    
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

function chart_type(argument){

    var currentUrl = window.location.href;

    // 處理button的class 
    var buttons = document.getElementsByClassName("btn-chart");
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove("active");
    }
    var activeButton = document.getElementById(argument);
    activeButton.classList.add("active");

    var chartIndex = currentUrl.indexOf('chart=');




    var chart;

    if(argument == "torque_time"){
        chart = 1;
    }
    
    if(argument == "angle_time"){
        chart = 2;
    }

    if(argument == "rpm_time"){
        chart = 3;
    }

    if(argument == "torque_angle"){
        chart = 4;
    }

    if(argument == "torque_speed"){
        chart = 5;
    }

    var nextinfo_url;

    if (chartIndex !== -1) {
        var nextChartValue = 'chart=' + chart;
        nextinfo_url = currentUrl.substring(0, chartIndex) + nextChartValue;
    } else {
        var separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
        nextinfo_url = currentUrl + separator + 'chart=' + chart;
    }

    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
            window.location.assign(nextinfo_url);
        }
    };
    xhttp.open("GET", nextinfo_url, true);
    xhttp.send();
}

var language = getCookie('language');

var myChart = echarts.init(document.getElementById('chart'));
var x_data_val = <?php echo  $data['chart_info']['x_val']; ?>;
var y_data_val = <?php echo  $data['chart_info']['y_val']; ?>;
var x_title    = '<?php echo addslashes($data['echart_name'][1]); ?>';
var y_title    = '<?php echo addslashes($data['echart_name'][0]); ?>';
var y_data_val_torque = <?php echo !empty($data['chart_info']['y_val_torque']) ? $data['chart_info']['y_val_torque'] : '[]'; ?>;
var y_data_val_rpm    = <?php echo !empty($data['chart_info']['y_val_rpm']) ? $data['chart_info']['y_val_rpm'] : '[]'; ?>;
var chart_mode = '<?php echo $data['chart_mode'];?>';



if(language =="zh-tw"){
    if(x_title =="Time(MS)"){
        x_title ="時間";
    }

    if(x_title =="Angle"){
        x_title ="角度";
    }
    if(x_title =="Torque"){
        x_title ="扭力";
    }

    if(y_title =="Angle"){
        y_title ="角度";
    }
    if(y_title =="Torque"){
        y_title ="扭力";
    }

    if(y_title =="RPM"){
        y_title ="轉速";
    }

}

if(language =="zh-cn"){
    if(x_title =="Time(MS)"){
        x_title ="时间";
    }

    if(x_title =="Angle"){
        x_title ="角度";
    }
    if(x_title =="Torque"){
        x_title ="扭力";
    }

    if(y_title =="Angle"){
        y_title ="角度";
    }
    if(y_title =="Torque"){
        y_title ="扭力";
    }

    if(y_title =="RPM"){
        y_title ="转速";
    }

}


var language = getCookie('language');

var min_torque = Math.min.apply(null, y_data_val_torque);
var max_torque = Math.max.apply(null, y_data_val_torque);
var min_rpm = Math.min.apply(null, y_data_val_rpm);
var max_rpm = Math.max.apply(null, y_data_val_rpm);

// 計算兩者的最小值和最大值，這樣可以設置對齊的範圍
var min_val = Math.min(min_torque, min_rpm);
var max_val = Math.max(max_torque, max_rpm);


// 根據 chart_mode 設置
var option = {
    title: {
        text: ''
    },
    tooltip: {
        trigger: 'axis',
        position: function (pt) {
            return [pt[0], '10%'];
        },
        formatter: function (params) {
            // 初始化空的字符串來顯示 tooltip
            var tooltipContent = '';
            
            // 確保 tooltip 顯示扭力和轉速的數值
            params.forEach(function (param) {
                if (param.seriesName === '扭力') {
                    tooltipContent += '<span style="color: rgb(255, 0, 0);">torque: </span>' + param.value + ' Nm<br>';
                } else if (param.seriesName === '轉速') {
                    tooltipContent += '<span style="color: rgb(255, 0, 0);">rpm: </span>' + param.value + ' RPM<br>';
                }
            });

            return tooltipContent;
        }
    },
    xAxis: {
        type: 'category',
        boundaryGap: false,
        name: x_title,
        data: x_data_val
    },
    yAxis: chart_mode == "5" ? [  // 判斷 chart_mode 是否為 5
        // 左側 Y 軸，對應 y_data_val_torque
        {
            type: 'value',
            name: '',
            min: Math.min.apply(null, y_data_val_torque),
            max: Math.max.apply(null, y_data_val_torque),
            position: 'left',
            axisLabel: {
                formatter: '{value}'
            }
        },
        // 右側 Y 軸，對應 y_data_val_rpm
        {
            type: 'value',
            name: '',
            min: Math.min.apply(null, y_data_val_rpm),
            max: Math.max.apply(null, y_data_val_rpm),
            position: 'right',
            axisLabel: {
                formatter: '{value}'
            }
        }
    ] : [  // 如果不是 chart_mode == 5，只顯示單一 Y 軸
        {
            type: 'value',
            name: y_title,
            boundaryGap: [0, '100%']
        }
    ],
    dataZoom: generateDataZoom(),
    series: chart_mode == "5" ? [  // 如果是 chart_mode == 5，顯示兩條曲線
        {
            name: '扭力',
            type: 'line',
            symbol: 'none',
            sampling: 'average',
            yAxisIndex: 0,  // 左側 Y 軸
            itemStyle: {
                normal: {
                    color: 'rgb(255,0,0)'
                }
            },
            lineStyle: { width: 1 },
            data: y_data_val_torque
        },
        {
            name: '轉速',
            type: 'line',
            symbol: 'none',
            sampling: 'average',
            yAxisIndex: 1,  // 右側 Y 軸
            itemStyle: {
                normal: {
                    color: 'rgb(0,0,255)'
                }
            },
            lineStyle: { width: 1 },
            data: y_data_val_rpm
        }
    ] : [  // 否則顯示單條曲線
        {
            name: '',
            type: 'line',
            symbol: 'none',
            sampling: 'average',
            itemStyle: {
                normal: {
                    color: 'rgb(255,0,0)'
                }
            },
            areaStyle: {
                normal: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 0, [{
                        offset: 0,
                        color: 'rgb(255,255,255)'
                    }, {
                        offset: 0,
                        color: 'rgb(255,255,255)'
                    }])
                }
            },
            lineStyle: { width: 0.75 },
            data: y_data_val
        }
    ]
};

myChart.setOption(option);

function generateDataZoom() {
    return [
        {
            type: 'inside',
            start: 0,
            end: 100
        },
        {
            show: false,
            type: 'slider',
            start: 0,
            end: 100,
            handleIcon: 'M10.7,11.9v-1.3H9.3v1.3c-4.9,0.3-8.8,4.4-8.8,9.4c0,5,3.9,9.1,8.8,9.4v1.3h1.3v-1.3c4.9-0.3,8.8-4.4,8.8-9.4C19.5,16.3,15.6,12.2,10.7,11.9z M13.3,24.4H6.7V23h6.6V24.4z M13.3,19.6H6.7v-1.4h6.6V19.6z',
            handleSize: '80%',
            handleStyle: {
                color: '#fff',
                shadowBlur: 3,
                shadowColor: 'rgba(0, 0, 0, 0)',
                shadowOffsetX: 0,
                shadowOffsetY: 0
            }
        }
    ];
}
</script>

</body>

</html>