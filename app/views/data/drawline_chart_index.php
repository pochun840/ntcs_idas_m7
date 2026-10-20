<?php
// =====================================================
// drawline_chart_index.php
// 持續顯示最新 25 筆扭力資料 + ECharts 折線圖
// =====================================================

$textMap = $text ?? [];

$labelTitle      = htmlspecialchars($textMap['data'] ?? 'Data', ENT_QUOTES, 'UTF-8');
$labelTorque     = htmlspecialchars($textMap['torque'] ?? 'Torque', ENT_QUOTES, 'UTF-8');
$labelLineChart  = htmlspecialchars($textMap['line_chart'] ?? 'Line Chart', ENT_QUOTES, 'UTF-8');
$labelLatest25   = htmlspecialchars($textMap['latest_25_records'] ?? 'Latest 25 Records', ENT_QUOTES, 'UTF-8');
$labelAll        = htmlspecialchars($textMap['all'] ?? 'ALL', ENT_QUOTES, 'UTF-8');
$labelOK         = htmlspecialchars($textMap['ok'] ?? 'OK', ENT_QUOTES, 'UTF-8');
$labelNOK        = htmlspecialchars($textMap['ng'] ?? ($textMap['nok'] ?? 'NG'), ENT_QUOTES, 'UTF-8');
$labelTime       = htmlspecialchars($textMap['data_time'] ?? 'Time', ENT_QUOTES, 'UTF-8');
$labelNo         = htmlspecialchars($textMap['no'] ?? 'No.', ENT_QUOTES, 'UTF-8');
$labelExportCsv  = htmlspecialchars($textMap['export_csv'] ?? 'Export CSV', ENT_QUOTES, 'UTF-8');
$chartUnitLabel  = htmlspecialchars($data['chart_unit_label'] ?? '', ENT_QUOTES, 'UTF-8');
$chartLimit      = (int)($data['chart_limit'] ?? 25);

function drawline_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function renderLineChartTableRows($records) {
    if (empty($records)) {
        echo "<tr><td colspan='3' class='empty-row'>No Data</td></tr>";
        return;
    }

    // 表格顯示順序：最新資料排最上面。
    // 注意：Controller / Model 仍維持舊 → 新給折線圖使用，避免折線圖時間軸反向。
    $displayRecords = array_reverse(array_values($records));

    foreach ($displayRecords as $i => $row) {
        $class = preg_replace('/[^a-zA-Z0-9_\-]/', '', $row['row_color'] ?? '');
        $no    = drawline_h($row['id'] ?? ($row['sn'] ?? ($row['chart_index'] ?? ($row['rid'] ?? ($row['rowid'] ?? ($i + 1))))));
        $torque = drawline_h($row['final_fasten_torque'] ?? '0');
        $time   = drawline_h($row['data_time'] ?? ($row['chart_label'] ?? ''));

        echo "<tr class='{$class}'>";
        echo "<td>{$no}</td>";
        echo "<td class='td-torque'>{$torque}</td>";
        echo "<td class='td-time'>{$time}</td>";
        echo "</tr>";
    }
}
?>

<script src="<?php echo URLROOT; ?>js/echarts.min.js"></script>

<div class="container-ms drawline-page">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['tor_line_chart']; ?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>


    <?php
        // DATA 頁籤：直接放在本頁，不另外新增 app/views/data/_data_tabs.php。
        $drawlineTabsText = [];
        if (isset($text) && is_array($text)) {
            $drawlineTabsText = $text;
        } elseif (isset($data['text']) && is_array($data['text'])) {
            $drawlineTabsText = $data['text'];
        } elseif (isset($textMap) && is_array($textMap)) {
            $drawlineTabsText = $textMap;
        }

        $drawlineTabText = function(string $key, string $fallback) use ($drawlineTabsText): string {
            return htmlspecialchars((string)($drawlineTabsText[$key] ?? $fallback), ENT_QUOTES, 'UTF-8');
        };

        $drawlineOperatorLawKeys  = ['user_law', 'law', 'userLaw', 'user_level', 'permission', 'role_law'];
        $drawlineOperatorRoleKeys = ['role', 'user_role', 'account_role', 'permission_name'];
        $isDrawlineDataTabOperator = false;

        foreach ([($_SESSION ?? []), ($_COOKIE ?? [])] as $source) {
            foreach ($drawlineOperatorLawKeys as $key) {
                if (isset($source[$key]) && is_numeric($source[$key]) && (int)$source[$key] === 3) {
                    $isDrawlineDataTabOperator = true;
                    break 2;
                }
            }
        }

        if (!$isDrawlineDataTabOperator) {
            foreach ([($_SESSION ?? []), ($_COOKIE ?? [])] as $source) {
                foreach ($drawlineOperatorRoleKeys as $key) {
                    if (isset($source[$key]) && strtolower(trim((string)$source[$key])) === 'operator') {
                        $isDrawlineDataTabOperator = true;
                        break 2;
                    }
                }
            }
        }

        $drawlineDataTabs = [
            [
                'key'   => 'History',
                'label' => $drawlineTabText('data_history', '歷史資料'),
                'url'   => '?url=Data/index',
                'restricted' => false,
            ],
            [
                'key'   => 'Exportdata',
                'label' => $drawlineTabText('data_export', '歷史資料匯出'),
                'url'   => '?url=Data/index&tab=Exportdata',
                'restricted' => true,
            ],
            [
                'key'   => 'Export_Data_download',
                'label' => $drawlineTabText('download_chart', '曲線圖下載'),
                'url'   => '?url=Data/index&tab=Export_Data_download',
                'restricted' => true,
            ],
            [
                'key'   => 'Customize',
                'label' => $drawlineTabText('customize', '自定義'),
                'url'   => '?url=Customize',
                'restricted' => false,
            ],
            [
                'key'   => 'Torque_line_chart',
                'label' => $drawlineTabText('tor_line_chart', '扭力折線圖'),
                'url'   => '?url=Data/drawLineChart',
                'restricted' => false,
            ],
        ];
    ?>

    <div class="ntcs-data-tabs-wrap">
        <div class="ntcs-data-tabs w3-center" role="navigation" aria-label="Data tabs">
            <?php foreach ($drawlineDataTabs as $tab): ?>
                <?php
                    $isActive = ($tab['key'] === 'Torque_line_chart');
                    $isDisabled = $isDrawlineDataTabOperator && !empty($tab['restricted']);
                    $classes = ['button', 'ntcs-data-tab-btn'];
                    if ($isActive) {
                        $classes[] = 'active';
                    }
                    if ($isDisabled) {
                        $classes[] = 'download-restricted';
                        $classes[] = 'operator-disabled';
                    }
                    $classAttr = htmlspecialchars(implode(' ', $classes), ENT_QUOTES, 'UTF-8');
                    $url = htmlspecialchars($tab['url'], ENT_QUOTES, 'UTF-8');
                    $disabledAttr = $isDisabled ? ' disabled aria-disabled="true" data-operator-restricted="1"' : '';
                    $onclick = $isDisabled ? 'return false;' : "window.location.href='{$url}'";
                ?>
                <button type="button"
                        class="<?php echo $classAttr; ?>"
                        onclick="<?php echo htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $disabledAttr; ?>><?php echo $tab['label']; ?></button>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="center-content drawline-content">

            <div class="drawline-toolbar">
                <div class="drawline-title">
                    <span><?php echo $text['latest_25_fastening_data'];?></span>
                    <small id="lineChartStatus">Loading...</small>
                </div>

                <div class="drawline-actions">
                    <label for="data_select"><?php echo $text['mode'];?></label>
                    <select id="data_select" class="drawline-select">
                        <option value="ALL" selected><?php echo $labelAll; ?></option>
                        <option value="OK"><?php echo $labelOK; ?></option>
                        <option value="NG"><?php echo $labelNOK; ?></option>
                    </select>
                </div>
            </div>


            <div class="drawline-layout">
                <section class="chart-panel">
                    <div class="panel-header">
                       
                        <div class="panel-header-right">
                            <span><?php echo $text['auto_refresh_25_records'];?></span>
                            <button type="button" id="lineChartExportCsvBtn" class="drawline-export-btn" onclick="exportLineChartCsv()">
                                <?php echo $text['account_export']; ?>
                            </button>
                        </div>
                    </div>
                    <div id="lineChart" class="line-chart-box"></div>
                </section>

                <section class="table-panel">
                    <div class="drawline-table-scroll">
                        <table class="table w3-table w3-hoverable drawline-table">
                            <thead>
                                <tr>
                                    <th><?php echo $labelNo; ?></th>
                                    <th><?php echo $labelTorque; ?></th>
                                    <th><?php echo $labelTime; ?></th>
                                </tr>
                            </thead>
                            <tbody id="lineChartTableBody">
                                <?php renderLineChartTableRows($data['res_data'] ?? []); ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<script>
// =====================================================
// 持續顯示最新 25 筆扭力折線圖
// 參考 operation.php 的 ECharts 架構：echarts.init + setOption
// =====================================================
let lineChartInstance = null;
let lineChartTimer = null;
let lastLineChartSignature = '';
let currentMode = 'ALL';
let latestLineChartRecords = [];
const CHART_LIMIT = <?php echo $chartLimit; ?>;

function getApiUrl(path) {
    return `${window.location.protocol}//${window.location.hostname}/idas/public/?url=${path}`;
}

function safeNumber(value) {
    if (value === null || value === undefined || value === '') return null;
    const num = Number(String(value).replace(/,/g, ''));
    return Number.isFinite(num) ? num : null;
}

function initLineChart() {
    const chartDom = document.getElementById('lineChart');
    if (!chartDom) return;

    if (typeof echarts === 'undefined') {
        chartDom.innerHTML = '<div class="chart-message">ECharts not loaded. Please check operation.php chart package.</div>';
        return;
    }

    if (!lineChartInstance) {
        lineChartInstance = echarts.init(chartDom);
    }
}

function buildSignature(records) {
    return records.map(row => {
        return [
            row.id || row.sn || row.chart_index || row.rid || row.rowid || '',
            row.data_time || '',
            row.final_fasten_torque_raw ?? row.final_fasten_torque ?? '',
            row.fasten_status || ''
        ].join('|');
    }).join('@@');
}

function normalizeChartRecords(result, isFallback) {
    let records = Array.isArray(result.records) ? result.records.slice() : [];

    // 顯示規則：最新資料排最前面。
    // Data/get_drawline_chart_data 目前回傳舊 → 新，所以要 reverse。
    // getreal_time_data fallback 原本就是新 → 舊，所以只取前 CHART_LIMIT 筆。
    if (isFallback) {
        records = records.slice(0, CHART_LIMIT);
    } else {
        records = records.slice(-CHART_LIMIT).reverse();
    }

    return records.map((row, index) => {
        const rawTorque = safeNumber(row.final_fasten_torque_raw ?? row.final_fasten_torque);
        return {
            ...row,
            chart_index: row.id || row.sn || row.chart_index || row.rid || row.rowid || (index + 1),
            chart_label: row.chart_label || row.data_time || String(row.id || row.sn || row.chart_index || row.rid || row.rowid || (index + 1)),
            final_fasten_torque_raw: rawTorque,
            final_fasten_torque: row.final_fasten_torque ?? (rawTorque === null ? '' : String(rawTorque))
        };
    });
}

async function fetchLineChartData(mode = 'ALL') {
    const formData = new FormData();
    formData.append('mode', mode);
    formData.append('limit', CHART_LIMIT);

    const endpoints = [
        { url: getApiUrl('Data/get_drawline_chart_data'), fallback: false },
        { url: getApiUrl('Data/getreal_time_data'), fallback: true }
    ];

    for (const endpoint of endpoints) {
        try {
            const res = await fetch(endpoint.url + `&t=${Date.now()}`, {
                method: 'POST',
                body: formData,
                cache: 'no-store'
            });

            const text = await res.text();
            const result = JSON.parse(text);

            if (result && result.success) {
                const records = normalizeChartRecords(result, endpoint.fallback);
                renderLineChartPage(records, result);
                return;
            }
        } catch (e) {
            console.warn('fetchLineChartData failed:', endpoint.url, e);
        }
    }

    setLineChartStatus('Load failed');
}

function renderLineChartPage(records, result) {
    latestLineChartRecords = Array.isArray(records) ? records.slice() : [];
    const signature = buildSignature(records);

    updateLineChartSummary(records, result);
    updateLineChartTable(records);

    // 資料沒變時，不重畫圖，減少控制器負擔。
    if (signature === lastLineChartSignature) {
        return;
    }
    lastLineChartSignature = signature;

    drawLineChart(records, result.chart_unit_label || '');
}

function drawLineChart(records, unitLabel = '') {
    initLineChart();
    if (!lineChartInstance) return;

    // X 軸 NO 改用資料庫 rowid/rid。
    const labels = records.map((row, index) => String(row.id || row.sn || row.chart_index || row.rid || row.rowid || (index + 1)));

    // 時間保留在 tooltip，不放在 X 軸。
    const times = records.map(row => row.data_time || row.chart_label || '');

    const values = records.map(row => safeNumber(row.final_fasten_torque_raw ?? row.final_fasten_torque));
    const latestUnit = unitLabel || <?php echo json_encode($chartUnitLabel, JSON_UNESCAPED_UNICODE); ?> || '';

    const option = {
        animation: false,
        tooltip: {
            trigger: 'axis',
            formatter: function(params) {
                const p = params && params[0] ? params[0] : null;
                if (!p) return '';

                const idx = p.dataIndex;
                const no = labels[idx] || '-';
                const time = times[idx] || '-';
                const torque = p.data ?? '-';

                return `NO: ${no}<br/>Time: ${time}<br/>Torque: ${torque} ${latestUnit}`;
            }
        },
        grid: {
            left: 60,
            right: 42,
            top: 42,
            // Keep X-axis labels horizontal; use extra bottom space for 6-digit NO.
            bottom: 70,
            containLabel: true
        },
        xAxis: {
            type: 'category',
            boundaryGap: true,
            name: 'NO',
            nameLocation: 'middle',
            nameGap: 32,
            data: labels,
            axisLabel: {
                // Fixed horizontal labels. Do not auto-tilt for 6-digit NO.
                rotate: 0,
                interval: 0,
                hideOverlap: true,
                fontSize: 11,
                margin: 12
            },
            axisTick: {
                alignWithLabel: true
            }
        },
        yAxis: {
            type: 'value',
            name: latestUnit ? `Torque (${latestUnit})` : 'Torque',
            scale: true
        },
        series: [{
            name: 'Torque',
            type: 'line',
            data: values,
            smooth: true,
            showSymbol: true,
            symbolSize: 5,
            connectNulls: false,
            lineStyle: {
                width: 3
            },
            areaStyle: {
                opacity: 0.08
            }
        }]
    };

    lineChartInstance.setOption(option, true);
}

function formatLineChartUpdateTime24(dateObj) {
    const pad2 = value => String(value).padStart(2, '0');
    return `${pad2(dateObj.getHours())}:${pad2(dateObj.getMinutes())}:${pad2(dateObj.getSeconds())}`;
}

function updateLineChartSummary(records, result) {
    // 上方 Count / Torque / Unit 摘要卡片已移除，只保留更新狀態。
    // 固定使用 24 小時制，避免瀏覽器語系顯示 上午 / 下午 或 AM / PM。
    setLineChartStatus(`Updated: ${formatLineChartUpdateTime24(new Date())}`);
}

function updateLineChartTable(records) {
    const tbody = document.getElementById('lineChartTableBody');
    if (!tbody) return;

    if (!records.length) {
        tbody.innerHTML = `<tr><td colspan="3" class="empty-row">No Data</td></tr>`;
        return;
    }

    // records 已經是最新 → 舊，表格直接依照同一順序顯示。
    tbody.innerHTML = records.map((row, index) => {
        const rowClass = String(row.row_color || '').replace(/[^a-zA-Z0-9_-]/g, '');
        const torque = row.final_fasten_torque ?? '';
        const time = row.data_time || row.chart_label || '';
        return `
            <tr class="${rowClass}">
                <td>${escapeHtml(row.id || row.sn || row.chart_index || row.rid || row.rowid || (index + 1))}</td>
                <td class="td-torque">${escapeHtml(torque)}</td>
                <td class="td-time">${escapeHtml(time)}</td>
            </tr>`;
    }).join('');
}

function setLineChartStatus(text) {
    const el = document.getElementById('lineChartStatus');
    if (el) el.innerText = text;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


function formatLineChartDateTime(dateObj, endOfDay = false) {
    const pad2 = value => String(value).padStart(2, '0');
    const yyyy = dateObj.getFullYear();
    const mm = pad2(dateObj.getMonth() + 1);
    const dd = pad2(dateObj.getDate());

    if (endOfDay) {
        return `${yyyy}-${mm}-${dd} 23:59:59`;
    }
    return `${yyyy}-${mm}-${dd} 00:00:00`;
}

function getCurrentLineChartExportRange() {
    const times = latestLineChartRecords
        .map(row => String(row.data_time || row.chart_label || '').trim())
        .filter(value => /^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/.test(value))
        .sort();

    // records 目前顯示為最新 → 舊；排序後第 1 筆為最舊，最後 1 筆為最新。
    if (times.length) {
        return {
            start: times[0],
            end: times[times.length - 1]
        };
    }

    // 沒有目前資料時，退回今天 00:00:00 ~ 23:59:59。
    const now = new Date();
    return {
        start: formatLineChartDateTime(now, false),
        end: formatLineChartDateTime(now, true)
    };
}

function exportLineChartCsv() {
    const range = getCurrentLineChartExportRange();
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = getApiUrl('Data/export_drawline_chart_csv');
    form.style.display = 'none';

    const appendHidden = (name, value) => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = value;
        form.appendChild(input);
    };

    appendHidden('start_date', range.start);
    appendHidden('end_date', range.end);

    document.body.appendChild(form);
    form.submit();

    setTimeout(() => {
        if (form && form.parentNode) {
            form.parentNode.removeChild(form);
        }
    }, 1000);
}

document.addEventListener('DOMContentLoaded', function() {
    initLineChart();

    const select = document.getElementById('data_select');
    if (select) {
        select.addEventListener('change', function() {
            currentMode = this.value || 'ALL';
            lastLineChartSignature = '';
            fetchLineChartData(currentMode);
        });
    }

    fetchLineChartData(currentMode);

    if (lineChartTimer) {
        clearInterval(lineChartTimer);
    }
    lineChartTimer = setInterval(function() {
        fetchLineChartData(currentMode);
    }, 2000);
});

window.addEventListener('resize', function() {
    if (lineChartInstance) {
        lineChartInstance.resize();
    }
});
</script>

<style>

.ntcs-data-tabs-wrap {
    width: 96%;
    max-width: 1320px;
    margin: 10px auto 8px auto;
}
.ntcs-data-tabs {
    position: relative;
    padding: 0 10px;
}
.ntcs-data-tab-btn {
    margin: 0 8px 8px 0;
    min-width: 88px;
}
.ntcs-data-tab-btn.active {
    background-color: #0d6fb8 !important;
    border-color: #0d6fb8 !important;
    color: #fff !important;
}
.ntcs-data-tab-btn.download-restricted,
.ntcs-data-tab-btn.operator-disabled {
    background-color: #8a8f93 !important;
    border-color: #8a8f93 !important;
    color: #ffffff !important;
    opacity: .72 !important;
    cursor: not-allowed !important;
}
.ntcs-data-tab-btn[disabled] {
    pointer-events: none !important;
}

.drawline-page .drawline-content {
    width: 96%;
    max-width: 1320px;
    margin: 0 auto;
}

.drawline-toolbar,
.drawline-layout {
    box-sizing: border-box;
}

.drawline-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin: 10px 0;
    padding: 12px 16px;
    background: #f5efe4;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,.12);
}

.drawline-title span {
    display: block;
    font-size: 22px;
    font-weight: 800;
    color: #4b3422;
}

.drawline-title small {
    display: block;
    margin-top: 4px;
    color: #7a6657;
    font-size: 13px;
}

.drawline-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 16px;
    font-weight: 700;
    color: #4b3422;
}

.drawline-select {
    min-width: 120px;
    height: 38px;
    padding: 0 12px;
    border: 1px solid #b79b80;
    border-radius: 8px;
    background: #fff;
    font-size: 16px;
    font-weight: 700;
}





.drawline-layout {
    display: grid;
    /* 右側表格加寬，讓 Fastening Time 可以完整顯示 */
    grid-template-columns: minmax(0, 1.65fr) minmax(470px, 1fr);
    gap: 12px;
}

.chart-panel,
.table-panel {
    min-height: 0;
    background: #fffaf2;
    border: 1px solid #e1cdb8;
    border-radius: 14px;
    box-shadow: 0 2px 10px rgba(0,0,0,.12);
    overflow: hidden;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 10px 14px;
    background: linear-gradient(180deg, #8c6748, #60442f);
    color: #fff;
}


.panel-header-right {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    flex-wrap: nowrap;
}

.drawline-export-btn {
    height: 34px;
    min-width: 108px;
    padding: 0 14px;
    border: 1px solid rgba(255,255,255,.55);
    border-radius: 8px;
    background: #2f80ed;
    color: #fff;
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,.18);
}

.drawline-export-btn:active {
    transform: translateY(1px);
}


.panel-header strong {
    font-size: 18px;
}

.panel-header span {
    font-size: 13px;
    opacity: .9;
}

.line-chart-box {
    width: 100%;
    height: 360px;
    background: #fff;
}

.chart-message {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #9b2c2c;
    font-weight: 800;
    text-align: center;
    padding: 20px;
}

.drawline-table-scroll {
    height: 360px;
    overflow-y: auto;
    background: #fff;
}

/* 右側表格 header 已移除，所以高度補齊，讓表格高度接近左側圖表卡片。 */
.table-panel .drawline-table-scroll {
    height: 402px;
}

.drawline-table {
    width: 100%;
    margin: 0;
    table-layout: auto;
}

.drawline-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #4d3a2c;
    color: #fff;
    text-align: center;
    white-space: nowrap;
}

.drawline-table td {
    text-align: center;
    vertical-align: middle;
    white-space: nowrap;
    padding-left: 8px;
    padding-right: 8px;
}

.drawline-table .td-torque {
    font-weight: 900;
}

.drawline-table .td-time {
    min-width: 190px;
    max-width: none;
    overflow: visible;
    text-overflow: clip;
    font-size: 14px;
}

.drawline-table th:nth-child(1),
.drawline-table td:nth-child(1) {
    /* Support 6-digit NO without squeezing the torque/time columns. */
    width: 92px;
    min-width: 92px;
    max-width: 92px;
    font-size: 14px;
}

.drawline-table th:nth-child(2),
.drawline-table td:nth-child(2) {
    width: 92px;
    min-width: 92px;
}

.drawline-table th:nth-child(3),
.drawline-table td:nth-child(3) {
    min-width: 190px;
}

.empty-row {
    padding: 24px !important;
    color: #777;
    font-weight: 700;
}

.status-ok td {
    background: #e9f8e9;
}

.status-ng td {
    background: #fdeaea;
}

.status-warn td {
    background: #fff4d8;
}

@media (max-width: 900px) {
    .drawline-layout {
        grid-template-columns: 1fr;
    }


    .drawline-toolbar {
        align-items: stretch;
        flex-direction: column;
    }

    .line-chart-box,
    .drawline-table-scroll,
    .table-panel .drawline-table-scroll {
        height: 320px;
    }
}
</style>
