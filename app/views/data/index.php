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

$is_admin_login = (isset($_COOKIE['username']) && strtolower(trim((string)$_COOKIE['username'])) === 'admin');

// Operator(law = 3) 只可瀏覽，不可下載/匯出檔案。
// 以目前 SESSION 登入帳號為準，避免 guest/admin 被舊 cookie user_law=3 誤判。
$operator_law_keys = ['user_law', 'law', 'userLaw', 'user_level', 'permission', 'role_law'];
$operator_role_keys = ['role', 'user_role', 'account_role', 'permission_name'];
$is_operator_login = false;
$session_source = $_SESSION ?? [];
$session_privilege = strtolower(trim((string)($session_source['privilege'] ?? '')));

if (in_array($session_privilege, ['admin', 'administrator', 'guest'], true)) {
    $is_operator_login = false;
} elseif ($session_privilege === 'operator') {
    $is_operator_login = true;
} else {
    $session_law_found = false;
    foreach ($operator_law_keys as $key) {
        if (isset($session_source[$key]) && is_numeric($session_source[$key])) {
            $session_law_found = true;
            $is_operator_login = ((int)$session_source[$key] === 3);
            break;
        }
    }

    if (!$session_law_found) {
        foreach ($operator_role_keys as $key) {
            if (isset($session_source[$key])) {
                $role = strtolower(trim((string)$session_source[$key]));
                if ($role === 'operator') {
                    $is_operator_login = true;
                    break;
                }
                if (in_array($role, ['admin', 'administrator', 'guest'], true)) {
                    $is_operator_login = false;
                    break;
                }
            }
        }
    }

    // 沒有明確 SESSION 身分時才 fallback 到 cookie，避免切換帳號後舊 cookie 影響 guest。
    $has_session_identity = isset($session_source['privilege']) || isset($session_source['username']) || isset($session_source['user']) || isset($session_source['account']);
    if (!$is_operator_login && !$has_session_identity) {
        foreach ($operator_law_keys as $key) {
            if (isset($_COOKIE[$key]) && is_numeric($_COOKIE[$key]) && (int)$_COOKIE[$key] === 3) {
                $is_operator_login = true;
                break;
            }
        }
        if (!$is_operator_login) {
            foreach ($operator_role_keys as $key) {
                if (isset($_COOKIE[$key]) && strtolower(trim((string)$_COOKIE[$key])) === 'operator') {
                    $is_operator_login = true;
                    break;
                }
            }
        }
    }
}

$lang_for_operator_msg = strtolower(trim((string)($_SESSION['language'] ?? $_COOKIE['language'] ?? 'zh-tw')));
if ($lang_for_operator_msg === 'zh-cn') {
    $operator_download_denied_title = '权限不足';
    $operator_download_denied_msg = 'Operator 权限不允许下载或汇出档案。';
} elseif ($lang_for_operator_msg === 'en-us' || $lang_for_operator_msg === 'en') {
    $operator_download_denied_title = 'Permission denied';
    $operator_download_denied_msg = 'Operator permission is not allowed to download or export files.';
} else {
    $operator_download_denied_title = '權限不足';
    $operator_download_denied_msg = 'Operator 權限不允許下載或匯出檔案。';
}

$download_restricted_class = $is_operator_login ? ' download-restricted' : '';
$download_restricted_title = '';
$download_restricted_attr = $is_operator_login ? ' disabled aria-disabled="true"' : '';
$export_data_onclick = $is_operator_login ? 'return false;' : "OpenButton('Exportdata')";
$download_chart_onclick = $is_operator_login ? 'return false;' : "OpenButton('Export_Data_download')";
$customize_onclick = $is_operator_login ? 'return false;' : "OpenButton('Customize')";
$export_submit_onclick = $is_operator_login ? 'return false;' : 'exportData()';


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
                <button id="data_bnt1" name="History_Display" class="button active" onclick="OpenButton('History')"><?php echo $text['data_history'];?></button>
                <button id="data_bnt2" name="Export_Data_Display" class="button<?php echo $download_restricted_class; ?>" onclick="<?php echo htmlspecialchars($export_data_onclick, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($download_restricted_title, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $download_restricted_attr; ?>><?php echo $text['data_export'];?></button>
                <button id="data_bnt3" name="Export_Data_download" class="button<?php echo $download_restricted_class; ?>" onclick="<?php echo htmlspecialchars($download_chart_onclick, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($download_restricted_title, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $download_restricted_attr; ?>><?php echo $text['download_chart'];?></button>
                <button id="data_bnt4" name="Customize" class="button hide-mobile<?php echo $download_restricted_class; ?>" onclick="<?php echo htmlspecialchars($customize_onclick, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($download_restricted_title, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $download_restricted_attr; ?>><?php echo $text['customize'];?></button>
                <!--<button id="data_bnt5" name="Torque_line_chart" class="button hide-mobile"   onclick="OpenButton('Torque_line_chart')"><?php echo $text['tor_line_chart'];?></button>-->
                <?php //if ($is_admin_login) { ?>
                    <!--<button id="data_bnt7" name="Operation_Audit_Log_Display" class="button operation-audit-top-button" onclick="window.location.href='?url=Data/AuditLog'"><?php echo htmlspecialchars($text['audit_button'] ?? 'operation_audit_log', ENT_QUOTES, 'UTF-8'); ?></button>-->
                <?php //} ?>
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
                                            <th><?php echo $text['system_barcode']; ?></th>
                                            <th><?php echo $text['user_id']; ?></th>
                                            <th><?php echo $text['job_cycle_time']; ?></th>
                                            <th><?php echo $text['column_status']; ?></th>
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
                            <button class="btn-export w3-button w3-border w3-round<?php echo $download_restricted_class; ?>" onclick="<?php echo htmlspecialchars($export_submit_onclick, ENT_QUOTES, 'UTF-8'); ?>" title="<?php echo htmlspecialchars($download_restricted_title, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $download_restricted_attr; ?>><?php echo $text['data_export'];?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>    

</div>


<script>

    const IS_OPERATOR_LOGIN = <?php echo $is_operator_login ? 'true' : 'false'; ?>;
    const OPERATOR_DOWNLOAD_DENIED_TITLE = <?php echo json_encode($operator_download_denied_title, JSON_UNESCAPED_UNICODE); ?>;
    const OPERATOR_DOWNLOAD_DENIED_MESSAGE = <?php echo json_encode($operator_download_denied_msg, JSON_UNESCAPED_UNICODE); ?>;

    function denyDownloadByOperator() {
        return false;
    }

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
                    <td>${row.barcode}</td>
                    <td>${row.user_id}</td>
                    <td>${row.job_cycle_time}</td>
                    <td class="${row.row_color}">${status_arr[status]}</td>
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


    // 支援從自定義頁 DATA 頁籤導回指定頁籤，例如：?url=Data/index&tab=Exportdata
    document.addEventListener('DOMContentLoaded', function () {
        const params = new URLSearchParams(window.location.search);
        const requestedTab = params.get('tab') || params.get('data_tab') || '';
        const allowedTabs = {
            History: 'History',
            Exportdata: 'Exportdata',
            Export_Data_download: 'Export_Data_download',
            Customize: 'Customize',
            Torque_line_chart: 'Torque_line_chart'
        };

        if (requestedTab === 'Customize' && IS_OPERATOR_LOGIN) {
            return;
        }

        if (requestedTab && allowedTabs[requestedTab] && typeof OpenButton === 'function') {
            setTimeout(function () {
                OpenButton(allowedTabs[requestedTab]);
            }, 80);
        }
    });

</script>
</body>

</html>
<style>
    .download-restricted {
        background-color: #8a8f93 !important;
        border-color: #8a8f93 !important;
        color: #ffffff !important;
        cursor: not-allowed !important;
        opacity: 0.75;
    }

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
