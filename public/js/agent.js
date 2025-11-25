// 對應不同鎖附狀態與顏色
const fasten_status = [
  { status: "Initialize" }, { status: "Tool Ready" }, { status: "Tool running" }, { status: "Reverse" },
  { status: "OK", color: "green" }, { status: "OK-SEQ", color: "yellow" }, { status: "OK-JOB", color: "yellow" },
  { status: "NG", color: "red" }, { status: "NG Stop", color: "red" }, { status: "Setting" },
  { status: "EOC" }, { status: "C1" }, { status: "C2" }, { status: "C4" }, { status: "C5" }, { status: "BS" }
];

// 扭力單位對應表
const torque_unit = [
  { status: "Kgf-m" }, { status: "N-m" }, { status: "Kgf-cm" }, { status: "In-lbs" }, { status: "CN.m" }
];

// 裝置類型（全部用 DEVICE_TYPE_10，根據 PHP 定義）
const device_type = Array(11).fill({ status: "<?php echo DEVICE_TYPE_11; ?>" });

// 儲存 IP → 資料列的對應關係
const ipToTableRow = new Map();

// 宣告 WebSocket 和 DataTable 實例
let socket;
let table2;

/**
 * 處理從 WebSocket 收到的訊息
 */
function handleWebSocketMessage(event) {
  const match = event.data.match(/^Client (\d+) said: (.*)/);
  if (!match) return;

  try {
    const data = JSON.parse(match[2]);
    const tableBody = document.querySelector("#data-table tbody");

    // 若 IP 已存在，則更新資料列；否則新增資料列
    let row = ipToTableRow.get(data.client_ip);
    if (row) {
      updateTableRow(row, data);
    } else if (data.client_ip) {
      row = tableBody.insertRow();
      populateTableRow(row, data);
      ipToTableRow.set(data.client_ip, row);
      table2.row.add(row).draw();
    }
  } catch (error) {
    console.error("JSON Parse Error:", error);
  }
}

/**
 * 插入新資料列並填入資料
 */
function populateTableRow(row, data) {
  const cells = [
    ipToTableRow.size + 1, // 序號
    device_type[data.device_type]?.status || '',
    data.device_name || '',
    data.client_ip || '',
    data.data_time || '',
    data.job_id || '',
    data.seq_id || '',
    data.fasten_torque || '',
    torque_unit[data.step_tor_unit]?.status || '',
    data.fasten_angle || '',
    data.max_screw_count || '',
    data.last_screw_count || '',
    fasten_status[data.fasten_status]?.status || ''
  ];
  cells.forEach(content => row.insertCell().textContent = content);

  // 閃爍效果（可用於表示即時更新）
  row.classList.add("breathing-row");
  setTimeout(() => row.classList.remove("breathing-row"), 1000);
}

/**
 * 更新既有資料列的內容
 */
function updateTableRow(row, data) {
  const cells = row.cells;
  cells[1].textContent = device_type[data.device_type]?.status || '';
  cells[2].textContent = data.device_name;
  cells[3].textContent = data.client_ip;
  cells[4].textContent = data.data_time;
  cells[5].textContent = data.job_id;
  cells[6].textContent = data.seq_id;
  cells[7].textContent = data.fasten_torque;
  cells[8].textContent = torque_unit[data.step_tor_unit]?.status || '';
  cells[9].textContent = data.fasten_angle;
  cells[10].textContent = data.max_screw_count;
  cells[11].textContent = data.last_screw_count;
  cells[12].textContent = fasten_status[data.fasten_status]?.status || '';

  row.classList.add("breathing-row");
  setTimeout(() => row.classList.remove("breathing-row"), 1000);
}

/**
 * 建立 WebSocket 連線，並處理連線錯誤與重連
 */
function connectWebSocket() {
  const serverUrl = "ws://<?php echo $data['agent_server_ip']; ?>:9501";
  socket = new WebSocket(serverUrl);

  socket.addEventListener('open', () => console.log('WebSocket connected'));
  socket.addEventListener('message', handleWebSocketMessage);
  socket.addEventListener('close', () => {
    console.log('WebSocket disconnected, retrying...');
    setTimeout(connectWebSocket, 5000);
  });
  socket.addEventListener('error', (e) => console.error('WebSocket error', e));
}

/**
 * 顯示當前時間（每秒刷新）
 */
function ShowTime() {
  const now = new Date();
  const pad = (n) => String(n).padStart(2, '0');
  document.getElementById('day').textContent = `${now.getFullYear()}/${now.getMonth()+1}/${now.getDate()}`;
  document.getElementById('time').textContent = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
  setTimeout(ShowTime, 1000);
}

/**
 * 手動切換 DataTable 頁碼
 */
function change_page(page) {
  table2.page(page).draw('page');
}

/**
 * 根據選取的列開啟 DAS 頁面
 */
function open_das() {
  const row = table2.row('.selected').data();
  const ip = row ? row[3] : null;
  if (ip) window.open(`http://${ip}/das/public/`, '_blank');
}

// 初始化
$(document).ready(function () {
  ShowTime();

  // 初始化 DataTable
  table2 = $('#data-table').DataTable({
    searching: false,
    bInfo: false,
    ordering: false,
    dom: "frti",
    pageLength: 99,
    language: { zeroRecords: " " }
  });

  // 表格列點擊時加上選取樣式
  $('#data-table tbody').on('click', 'tr', function () {
    $(this).toggleClass('selected').siblings().removeClass('selected');
  });

  // 啟動 WebSocket
  connectWebSocket();
});