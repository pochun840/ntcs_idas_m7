<?php
// 語系設定
switch ($_SESSION['language'] ?? '') {
    case 'zh-cn':
        $calendar_lang = 'zh';
        break;
    case 'zh-tw':
        $calendar_lang = 'zh_tw';
        break;
    default:
        $calendar_lang = '';
        break;
}


#顯示 表格
function renderTableRows($records, $unit_arr, $status_arr, $text) {
    foreach ($records as $row) {
        $status = $row['fasten_status'];
        $class = $row['row_color'] ?? '';  // 這一行決定樣式 class

        echo "<tr>
                <td>{$row['id']}</td>
                <td style='white-space: nowrap;'>{$row['data_time']}</td>
                <td>{$row['job_name']}</td>
                <td>{$row['sequence_name']}</td>
                <td class='td-torque'>{$row['final_fasten_torque']}</td>
                <td>{$text[$unit_arr[$row['torque_unit']]]}</td>
                <td>{$row['total_fasten_angle']}</td>
                <td>{$row['last_screw_count']}</td>
                <td>{$row['total_screw_count']}</td>
                <td class='{$class}'>{$status_arr[$status]}</td>
              </tr>";
    }
}


?>

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['data'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>
    
    <div class="main-content">
        <div class="center-content">
            <div class="w3-center" style="position: relative; padding-right: 10px">
                <button id="bnt1" name="History_Display" class="button active" onclick="OpenButton('History')"><?php echo $text['data_history'];?></button>
                <button id="bnt2" name="Export_Data_Display" class="button" onclick="OpenButton('Exportdata')"><?php echo $text['data_export'];?></button>
                <button id="bnt3" name="Export_Data_download" class="button" onclick="OpenButton('Export_Data_download')"><?php echo $text['download_chart'];?></button>
                <button id="bnt4" name="Customize" class="button hide-mobile"   onclick="OpenButton('Customize')"><?php echo $text['customize'];?></button>
                <button id="bnt5" name="Torque_line_chart" class="button hide-mobile"   onclick="OpenButton('Torque_line_chart')"><?php echo "Torque_line_chart";?></button>

                <div style="position:absolute;z-index: 9;right: 1px;top: 10px;">
                    <select id="data_select" class="form-select" onchange="DataMode(this)">
                        <option value="ALL">ALL</option>
                        <option value="OK">OK</option>
                        <option value="NOK">NG</option>
                    </select>
                </div>
            </div>
            
            <div id="DataButtonMode">
                <div id="HistoryDisplay">
                     <!-- 三個資料區塊 -->
                     <?php
                    $tableConfigs = array(
                        ['id' => 'res_data_all', 'label' => $text['data_history_success'], 'data' => $data['res_data'], 'display' => 'block'],
                        ['id' => 'res_data_ok', 'label' => $text['data_history_success'], 'data' => $data['res_data_ok'], 'display' => 'none'],
                        ['id' => 'res_data_nok', 'label' => $text['data_history_fail'], 'data' => $data['res_data_nok'], 'display' => 'none']
                    );

                    foreach ($tableConfigs as $config){?>
                        <div class="table-container" id="<?php echo $config['id']; ?>" style="display: <?php echo $config['display']; ?>;">
                            <div style="font-weight: bold; font-size: 20px; padding-left: 1%"><?php echo $config['label']; ?></div>
                            <div class="scrollbar" id="style-data">
                                <table class="table w3-table w3-hoverable">
                                    <thead>
                                        <tr style="font-size: 16px; color: white;">
                                            <th><?php echo $text['column_no']; ?></th>
                                            <th style="white-space: nowrap;"><?php echo $text['column_datetime']; ?></th>
                                            <th><?php echo $text['job_name']; ?></th>
                                            <th><?php echo $text['seq_name']; ?></th>
                                            <th><?php echo $text['torque']; ?></th>
                                            <th><?php echo $text['column_unit']; ?></th>
                                            <th><?php echo $text['angle']; ?></th>
                                            <th><?php echo $text['column_count']; ?></th>
                                            <th><?php echo $text['column_total']; ?></th>
                                            <th><?php echo $text['column_status']; ?></th>
                                            <th><?php echo $text['system_barcode']; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="<?php echo $config['id']; ?>_tbody" style="font-size: 16px; text-align: center;">
                                        <?php renderTableRows($config['data'], $data['unit_arr'], $data['status_arr'], $text,$data['color_arr']); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php } ?>                                                                                                       
                </div>
                
                <div id="ExportdataDisplay" style="display:none;">
                    <div class="data-export" style="background-color: #F2F1F1;">
                        <h2><?php echo $text['data_export'];?></h2>
                        <div class="row">
                            <div class="col-sm-6">
                                <div style="max-width: 450px;margin: auto;text-align: center;">
                                    <label for="start" style="font-size:20px;">📅 <?php echo $text['start_date'];?> :</label>
                                    <div class="mb-3">
                                        <input type="text" id="start_date" placeholder="Select datetime" class="form-control" style="background-color: #fff;display:none;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div style="max-width: 450px;margin: auto;text-align: center;">
                                    <label for="start" style="font-size:20px;">📅 <?php echo $text['end_date'];?> :</label>
                                    <div class="mb-3">
                                        <input type="text" id="end_date" placeholder="Select datetime" class="form-control" style="background-color: #fff;display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row" style="padding-left: 30%">
                            <div class="col-4 t1"><?php echo $text['Export Format'];?>:</div>
                            <div class="col t2">
                                <div class="form-check form-check-inline">
                                    <input class="t2 form-check-input" type="radio" name="export-option" id="export-csv" value="0" style="zoom:1.2; vertical-align: middle" checked>
                                    <label class="t2 form-check-label" for="export-csv" style="font-weight: normal">CSV</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="t2 form-check-input" type="radio" name="export-option" id="export-zip" value="1" style="zoom:1.2; vertical-align: middle">
                                    <label class="t2 form-check-label" for="export-zip" style="font-weight: normal">ZIP</label>
                                </div>
                            </div>    
                        </div>
                        
                        <div style="text-align: center;margin-top: 20px;">
                            <button class="btn-export w3-button w3-border w3-round" onclick="exportData()"><?php echo $text['data_export'];?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>    

</div>


<script>

    document.addEventListener("DOMContentLoaded", function () {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');


       
        // 預設值
        const startStr = `${yyyy}-${mm}-${dd} 00:00:00`;
        const endStr   = `${yyyy}-${mm}-${dd} 23:59:59`;

        document.getElementById("start_date").value = startStr;
        document.getElementById("end_date").value = endStr;

        // 初始化 flatpickr
        flatpickr("#start_date", {
            enableTime: true,
            static: true,
            inline: true,
            dateFormat: "Y-m-d H:i:S",
            defaultDate: startStr,
            locale: "<?php echo $calendar_lang; ?>",
            disableMobile: "true",
            maxDate: `${yyyy}-12-31`,
            time_24hr: true
        });

        flatpickr("#end_date", {
            enableTime: true,
            enableSeconds: true,
            static: true,
            inline: true,
            dateFormat: "Y-m-d H:i:S",
            defaultDate: endStr,
            locale: "<?php echo $calendar_lang; ?>",
            disableMobile: "true",
            maxDate: `${yyyy}-12-31`,
            time_24hr: true
    });


    });





    function DataMode() {
        const mode = document.getElementById("data_select").value;
        const map = {
            'ALL': 'res_data_all',
            'OK': 'res_data_ok',
            'NOK': 'res_data_nok'
        };

        ['res_data_all', 'res_data_ok', 'res_data_nok'].forEach(id => {
            document.getElementById(id).style.display = 'none';
        });

        const target = map[mode];
        if (target) {
            document.getElementById(target).style.display = 'block';
        }
    }


    function fetchRealTimeData(mode = 'ALL') {
        const formData = new FormData();
        formData.append('mode', mode);

        const baseURL = `${window.location.protocol}//${window.location.hostname}/idas/public/?url=Data/getreal_time_data`;

        fetch(baseURL, {
            method: 'POST',
            body: formData
        })
        .then(res => res.text()) // 改成 text() 先看原始回傳內容
        .then(text => {
            //console.log('伺服器回傳內容：', text);
            try {
                const result = JSON.parse(text);
                if (result.success) {
                    updateTable(mode, result.records, result.unit_arr, result.status_arr);
                } else {
                    console.warn(result.msg);
                }
            } catch (e) {
                console.error('❌ 無法解析為 JSON，內容如下：', text);
            }
        })
        .catch(error => console.error('❌ 資料載入失敗:', error));
    }

    function syncNtcsDataDb() {
      const url = `${window.location.protocol}//${window.location.hostname}/idas/public/?url=Data/ntcs_data_db_sysnc`;

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

    function updateTable(mode, records, unit_arr, status_arr,color_arr) {
        const tbodyId = `res_data_${mode.toLowerCase()}_tbody`;
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;

        tbody.innerHTML = ''; // 清空原有內容
        records.forEach(row => {
            let status = row.fasten_status;
            const html = `
                <tr>
                    <td>${row.id}</td>
                    <td style="white-space: nowrap;" >${row.data_time}</td>
                    <td>${row.job_name}</td>
                    <td>${row.sequence_name}</td>
                    <td >${row.final_fasten_torque}</td>
                    <td>${unit_arr[row.torque_unit]}</td>
                    <td>${row.total_fasten_angle}</td>
                    <td>${row.last_screw_count}</td>
                    <td>${row.total_screw_count}</td>
                    <td class="${row.row_color}">${status_arr[status]}</td>
                    <td >${row.barcode}</td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', html);
        });
    }

    // 初始載入一次
    let currentMode = 'ALL';
    fetchRealTimeData(currentMode);

    // 每 2 秒抓一次
    setInterval(() => {
        fetchRealTimeData(currentMode);
    }, 2000);

    // 若有切換 dropdown（OK/NOK/ALL）
    document.getElementById('data_select').addEventListener('change', function () {
        currentMode = this.value;
        fetchRealTimeData(currentMode);
    });

</script>
</body>

</html>
<style>
    th.col-dt{
  white-space: nowrap;
  max-width: 110px;   /* 自行調整 */
  overflow: hidden;
  text-overflow: ellipsis;
}

@media (max-width: 768px){
  .hide-mobile{
    display:none !important;
  }
}

</style>
