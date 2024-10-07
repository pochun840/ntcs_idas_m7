<?php require APPROOT . 'views/inc/header.php'; ?>

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/w3.css" type="text/css">
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/agent.css" type="text/css">


<div class="container">
    <div class="header">
        <div id="day" class="w3-right-align" style="font-size: 14px; margin-top: auto; margin: 10px">Date: 2023/16/10</div>
        <div id="time" class="w3-right-align" style="font-size: 14px; margin-top: auto; margin: 0px 10px 0px 10px">Time: 15 : 00</div>
        <div style="margin-top: 1%">
            <h1><?php echo TITLE_AGENT; ?></h1>
        </div>
    </div>

    <div style="margin-top: 10px">
        <div id="menu">
            <a id="bnt1" onclick="OpenButton('agent')">Agent</a>
            <a id="bnt2" onclick="OpenButton('button1')">Button2</a>
            <a id="bnt3" onclick="OpenButton('button2')">Button3</a>
        </div>

        <!-- Agent -->
        <div id="Agent_Display"  style="margin-top: 18px">
            <div class="scrollbar table-container" id="style-Agent">
                <div class="force-overflow">
                    <table id="data-table" class="container2">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="10%">Device Type</th>
                                <th width="14%">Device Name</th>
                                <th width="10%">IP</th>
                                <th width="12%">Date Time</th>
                                <th width="6%">Job ID</th>
                                <th width="6%">Seq ID</th>
                                <th width="6%">Toque</th>
                                <th width="6%">Unit</th>
                                <th width="6%">Angle</th>
                                <th width="6%">Total</th>
                                <th width="6%">Count</th>
                                <th width="15%">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Button2 -->
        <div id="Button2_Display">

        </div>

        <!-- Button3 -->
        <div id="Button3_Display">

        </div>

        <div class="footer">
            <div class="w3-center">
                <!-- <button class="custom-btn btn-12" onclick="change_page('previous')"><span style="font-size: 24px">&#60;</span><span>Prev</span></button> -->
                <button type="button" class="custom-btn btn-13" onclick="open_das()"><span style="font-size: 24px">&#8629;</span><span>Open</span></button>
                <!-- <button class="custom-btn btn-14" onclick="change_page('next')"><span style="font-size: 24px">&#62;</span><span>Next</span></button> -->
                <button class="custom-btn btn-15" onclick="window.location.href='?url=Dashboards'"><span style="font-size: 24px">&#8678;</span><span>Back</span></button>
            </div>
        </div>
    </div>

</div>

<script>
  const fasten_status = [
          { index: 0, status: "Initialize", color: "" },
          { index: 1, status: "Tool Ready", color: "" },
          { index: 2, status: "Tool running", color: "" },
          { index: 3, status: "Reverse", color: "" },
          { index: 4, status: "OK", color: "green" },
          { index: 5, status: "OK-SEQ", color: "yellow" },
          { index: 6, status: "OK-JOB", color: "yellow" },
          { index: 7, status: "NG", color: "red" },
          { index: 8, status: "NG Stop", color: "red" },
          { index: 9, status: "Setting", color: "" },
          { index: 10, status: "EOC", color: "" },
          { index: 11, status: "C1", color: "" },
          { index: 12, status: "C2", color: "" },
          { index: 13, status: "C4", color: "" },
          { index: 14, status: "C5", color: "" },
          { index: 15, status: "BS", color: "" },
        ];
  const torque_unit = [
          { index: 0, status: "Kgf-m", color: "" },
          { index: 1, status: "N-m", color: "" },
          { index: 2, status: "Kgf-cm", color: "" },
          { index: 3, status: "In-lbs", color: "" },
        ];
  const device_type = [
          { index: 0, status: "<?php echo DEVICE_TYPE_7; ?>", color: "" },
          { index: 1, status: "<?php echo DEVICE_TYPE_7; ?>", color: "" },
          { index: 2, status: "<?php echo DEVICE_TYPE_7; ?>", color: "" },
          { index: 3, status: "<?php echo DEVICE_TYPE_7; ?>", color: "" },
          { index: 4, status: "<?php echo DEVICE_TYPE_7; ?>", color: "" },
          { index: 5, status: "<?php echo DEVICE_TYPE_7; ?>", color: "" },
          { index: 6, status: "<?php echo DEVICE_TYPE_7; ?>", color: "" },
          { index: 7, status: "<?php echo DEVICE_TYPE_7; ?>", color: "" },
        ];
  // 用于跟踪IP到表格行的映射
  const ipToTableRow = new Map();

  // 用于处理WebSocket消息的回调函数
  function handleWebSocketMessage(event) {
      const message = event.data;

      // 检查消息是否以 "client X said:" 开头
      const match = message.match(/^Client (\d+) said: (.*)/);

      if (match) {
          const clientNumber = match[1];
          const jsonMessage = match[2];

          try {
              const data = JSON.parse(jsonMessage);

              // 检查IP是否在映射中
              if (ipToTableRow.has(data.client_ip)) {
                  // 如果IP已存在，更新现有行
                  const row = ipToTableRow.get(data.client_ip);
                  // row.cells[0].textContent = 1;
                  row.cells[1].textContent = device_type[data.device_type].status;
                  row.cells[2].textContent = data.device_name;
                  row.cells[3].textContent = data.client_ip;
                  row.cells[4].textContent = data.data_time;
                  row.cells[5].textContent = data.job_id;
                  row.cells[6].textContent = data.sequence_id;
                  row.cells[7].textContent = data.fasten_torque;
                  row.cells[8].textContent = torque_unit[data.torque_unit].status;
                  row.cells[9].textContent = data.fasten_angle;
                  row.cells[10].textContent = data.max_screw_count;
                  row.cells[11].textContent = data.last_screw_count;
                  row.cells[12].textContent = fasten_status[data.fasten_status].status;
                  // row.classList.add("breathing-row");// 閃的css

                  setTimeout(() => {
                      // row.className = "";
                      row.classList.remove("breathing-row");
                  }, "1000");

                  // 更新其他单元格
              } else {
                  if(data.client_ip != null){
                  // 如果IP不存在，创建一行
                  const table = document.getElementById("data-table").getElementsByTagName('tbody')[0];
                  const row = table.insertRow();
                  row.insertCell(0).textContent = ipToTableRow.size+1;
                  row.insertCell(1).textContent = device_type[data.device_type].status;
                  row.insertCell(2).textContent = data.device_name;
                  row.insertCell(3).textContent = data.client_ip;
                  row.insertCell(4).textContent = data.data_time;
                  row.insertCell(5).textContent = data.job_id;
                  row.insertCell(6).textContent = data.sequence_id;
                  row.insertCell(7).textContent = data.fasten_torque;
                  row.insertCell(8).textContent = torque_unit[data.torque_unit].status;
                  row.insertCell(9).textContent = data.fasten_angle;
                  row.insertCell(10).textContent = data.max_screw_count;
                  row.insertCell(11).textContent = data.last_screw_count;
                  row.insertCell(12).textContent = fasten_status[data.fasten_status].status;
                  // row.className = "breathing-row";// 閃的css
                  // 添加其他单元格
                  // alert(456)
                  setTimeout(() => {
                      row.className = "";
                  }, "1000");
                  // 将IP与表格行关联
                  ipToTableRow.set(data.client_ip, row);
                  table2.row.add( row ).draw();
              }

              }
          } catch (error) {
              console.error("Error parsing JSON message: " + error);
          }
      }
  }

  // 通过WebSocket接收消息
  // let socket = new WebSocket('ws://192.168.0.42:9501');
  // socket.addEventListener('message', handleWebSocketMessage);

  //--------------------------------------------
  let socket; // WebSocket对象
  const server_ip = '<?php echo $data['agent_server_ip']; ?>';
  const serverUrl = 'ws://'+server_ip+':9501';
  var table2 = $('#data-table').DataTable({
          // paging: false,
          searching: false,
          bInfo: false,
          "ordering": false,
          // "bPaginate": false,
          "dom": "frti",
          "pageLength": 99,
          language: {
              "zeroRecords": " "
          },
      });

  function connectWebSocket() {
      socket = new WebSocket(serverUrl);

      socket.addEventListener('open', (event) => {
          console.log('WebSocket连接已建立');
          // 在连接建立时可以执行其他逻辑
      });

      socket.addEventListener('message', (event) => {
          // 处理接收到的WebSocket消息
          handleWebSocketMessage(event);
      });

      socket.addEventListener('close', (event) => {
          console.log('WebSocket连接已关闭');
          // 连接关闭时，设置定时器以尝试重新连接
          setTimeout(connectWebSocket, 5000); // 2秒后重新连接
      });

      socket.addEventListener('error', (event) => {
          console.error('WebSocket连接发生错误', event);
          // 在发生错误时也可以执行其他逻辑
      });
  }  

  // 初始连接
  connectWebSocket();
</script>
<script type="text/javascript">
    $(document).ready(function () {
        ShowTime();
        $('#data-table tbody').on('click', 'tr', function () {
            if ($(this).hasClass('selected')) {
                $(this).removeClass('selected');
            } else {
                table2.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
            }
        });
    });
    function change_page(argument) {
        let table = $('#data-table').DataTable();
        table.page( argument ).draw( 'page' );
        // let page_info = table.page.info();
        // $('#CurrentPage').val(page_info.page + 1);
    }

    function open_das(argument) {
        let table = $('#data-table').DataTable();
        let ip;
        try { 
            ip = table.row('.selected').data()[3];
        } catch (error) {
            ip = null; /* 任意默认值都可以被使用 */
        };
        
        if (ip != null) {
            window.open("http://"+ip+"/das/public/", "_blank");
        }
    }

    function ShowTime(){
    　var NowDate=new Date();
    　var y=NowDate.getFullYear();
    　var m=NowDate.getMonth()+1;
    　var d=NowDate.getDate();
    　var h=NowDate.getHours();
    　var i=NowDate.getMinutes();
    　var s=NowDate.getSeconds();
      h = String(h).padStart(2, "0");//補0到2位數
      s = String(s).padStart(2, "0");//補0到2位數
    　document.getElementById('day').innerHTML = y+'/'+m+'/'+d;
    　document.getElementById('time').innerHTML = h+':'+i+':'+s+'';
    　setTimeout('ShowTime()',1000);
    }

    
</script>

<style>
  /* CSS */
  .breathing-row {
      animation: breathing 1s alternate;
  }

  @keyframes breathing {
      0% {
          background-color: #66f26b;
      }

      100% {
          background-color: #2C344600;
      }
  }
  .dataTables_empty{
    display: none;
  }
</style>

<?php require APPROOT . 'views/inc/footer.php'; ?>