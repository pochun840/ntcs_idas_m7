<div class="container-ms">
  <div class="w3-text-white w3-center">
    <table>
      <tr id="header">
        <td width="100%">
          <h3><?php echo $data['text']['customize']; ?></h3>
        </td>
        <td>
          <button id="home" class="w3-btn w3-round-large" style="height:50px;padding:0" onclick="window.location.href='./?url=Dashboards'">
            <img src="../public/img/btn_home.png">
          </button>
        </td>
      </tr>
    </table>
  </div>

  <?php
    function norm_lang($s){
      $s = strtolower(trim((string)$s));
      if ($s === 'zh' || strpos($s,'hant')!==false || strpos($s,'tw')!==false || strpos($s,'hk')!==false || strpos($s,'mo')!==false) return 'zh-tw';
      if ($s === 'cn' || strpos($s,'hans')!==false || strpos($s,'cn')!==false || strpos($s,'sg')!==false) return 'zh-cn';
      return in_array($s, ['en-us','zh-tw','zh-cn']) ? $s : 'en-us';
    }

    // 1) 先從 Session 推語系
    $uiLang = norm_lang($_SESSION['language'] ?? 'en-us');

    // 2) 若 $data['text'] 顯示的是繁/簡，則覆蓋 uiLang（確保一致）
    if (!empty($data['text']) && is_array($data['text'])) {
      $probe = implode(' ', array_map(fn($v) => is_string($v) ? $v : '', array_values($data['text'])));
      if (preg_match('/[讀輸號臺雜檔錯儲顯開關閃]/u', $probe)) { $uiLang = 'zh-tw'; }
      elseif (preg_match('/[读输号台杂档错储显开关闪]/u', $probe)) { $uiLang = 'zh-cn'; }
    }

    // --- 翻譯文字 ---
    $L_dict = [
      'en-us' => [
        'no'=>'NO','read'=>'Read Position','input'=>'Input Position','result'=>'Result',
        'add'=>'Add Row','save'=>'Save','delete'=>'Delete',
        'placeholder_read'=>'Enter read location…','placeholder_input'=>'Enter input location…',
        'sel_none'=>'—','sel_ok'=>'OK','sel_ng'=>'NG',
        'saved'=>'Saved successfully','save_fail'=>'Save failed',
        'confirm_delete'=>'Delete this row?','delete_sel'=>'Delete Selected','delete_all'=>'Delete All',
        'none_selected'=>'Please select at least one row.','confirm_delete_all'=>'Delete ALL rows?',
        'num_only'=>'Numbers only.','no_dup'=>'Duplicate values are not allowed.',
        'move_up'=>'Move Up','move_down'=>'Move Down',
        'max_rows' => 'Maximum 100 rows allowed.', 'forbidden_idx' => 'This field ID is not allowed.',
        'in_use' => 'This field conflicts with the manual value. Remove one of them.',
        'nothing_to_save' => 'Nothing to save. Please add at least one row.',
        'confirm_save_empty' => 'No data found. Do you want to save an empty configuration ?',
        'saved_empty' => 'Saved.',
        'ok_btn'=>'OK','cancel_btn'=>'Cancel',
        'title_ok'=>'OK','title_info'=>'Info','title_error'=>'Error','title_confirm'=>'Confirm',




      ],
      'zh-tw' => [
        'no'=>'NO','read'=>'讀取位置','input'=>'輸入位置','result'=>'結果',
        'add'=>'新增列','save'=>'儲存','delete'=>'刪除',
        'placeholder_read'=>'輸入讀取位置…','placeholder_input'=>'輸入輸入位置…',
        'sel_none'=>'—','sel_ok'=>'OK','sel_ng'=>'NG',
        'saved'=>'已儲存','save_fail'=>'儲存失敗',
        'confirm_delete'=>'要刪除此列嗎？','delete_sel'=>'刪除已選','delete_all'=>'全部刪除',
        'none_selected'=>'請先勾選至少一列。','confirm_delete_all'=>'要刪除全部列嗎？',
        'num_only'=>'只能輸入數字。','no_dup'=>'不可以輸入重複的數值。',
        'move_up'=>'往上移動','move_down'=>'往下移動',
        'max_rows' => '最多允許 100 列。','forbidden_idx' => '此欄位不可使用。',
        'in_use' => '此欄位與手動輸入的數值互斥，請移除其中之一。',
        'nothing_to_save' => '沒有可儲存的內容，請先新增至少一列或填入資料。',
        'confirm_save_empty' => '目前沒有任何資料。要儲存為空設定嗎？',
        'saved_empty' => '已儲存。',
        'ok_btn'=>'確定','cancel_btn'=>'取消',
        'title_ok'=>'完成','title_info'=>'訊息','title_error'=>'錯誤','title_confirm'=>'確認',


      ],
      'zh-cn' => [
        'no'=>'NO','read'=>'读取位置','input'=>'输入位置','result'=>'结果',
        'add'=>'新增行','save'=>'保存','delete'=>'删除',
        'placeholder_read'=>'输入读取位置…','placeholder_input'=>'输入输入位置…',
        'sel_none'=>'—','sel_ok'=>'OK','sel_ng'=>'NG',
        'saved'=>'已保存','save_fail'=>'保存失败',
        'confirm_delete'=>'要删除此行吗？','delete_sel'=>'删除所选','delete_all'=>'全部删除',
        'none_selected'=>'请先勾选至少一行。','confirm_delete_all'=>'要删除全部行吗？',
        'num_only'=>'只能输入数字。','no_dup'=>'不可以输入重复的数值。',
        'move_up'=>'往上移动','move_down'=>'往下移动',
        'max_rows' => '最多允许 100 行。','forbidden_idx' => '此字段不可使用。',
        'in_use' => '该字段与手动输入的数值互斥，请移除其中之一。',
        'nothing_to_save' => '没有可保存的内容，请先新增至少一行或填写数据。',
        'confirm_save_empty' => '目前没有任何数据。要保存为空配置吗？',
        'saved_empty' => '已保存。',
        'ok_btn'=>'确定','cancel_btn'=>'取消',
        'title_ok'=>'完成','title_info'=>'信息','title_error'=>'错误','title_confirm'=>'确认',


      ],
    ];
    $L = $L_dict[$uiLang] ?? $L_dict['en-us'];

    // $data_button 的格式與 $columns 相同： [index => column_name]
    $btns = $data['data_button'];
  ?>

  <style>
    :root{
      --brand:#0e2a47; --brand-2:#1b74d2;
      --ink-a:#60a5fa; --ink-b:#34d399;
      --chip-a:#ffffff; --chip-b:#f7fafc;
      --ring:#93c5fd;
    }

    /* ===== Table minimal styles ===== */
    .table-wrap{margin:12px auto;max-width:1100px}
    #dynTable{width:100%;border-collapse:collapse;background:#fff}
    #dynTable thead th{background:#0e2a47;color:#fff;padding:10px;border:1px solid #274864}
    #dynTable tbody td{padding:8px;border:1px solid #e3e6eb}
    #dynTable td:nth-child(1){text-align:center;width:64px}
    .row-actions button{margin:0 4px}
    .result-ok{font-weight:600}
    .result-ng{font-weight:600}
    .control-bar{display:flex;gap:8px;justify-content:flex-end;margin:8px 0}
    input.table-input{width:100%;padding:6px 8px;box-sizing:border-box}
    select.table-select{width:100%;padding:6px 8px;box-sizing:border-box}
    #dynTable td.sel-ck, #dynTable th.sel-ck { text-align:center; width:46px; }

    input.table-input.invalid { border-color:#e00; background:#fff2f2; }

    /* 拖曳插入指示線 */
    #dynTable tr.insert-marker td{ padding:0; border:none; }
    #dynTable tr.insert-marker .insert-line{ height:2px; background:#60a5fa; width:100%;}

    /* 隱藏「輸入位置」第 4 欄：用 class 更穩定 */
    #dynTable thead th.col-input,
    #dynTable tbody td.col-input { display:none; }

    /* 上下移動按鈕的儲存格置中與寬度 */
    #dynTable td.move-cell, #dynTable th.move-cell { text-align:center; width:90px; }

    /* 小圖示按鈕外觀一致 */
    .w3-btn.icon-btn { min-width:44px; height:32px; line-height:32px; padding:0 10px; }

    /* ===== Fancy Tabs & Buttons ===== */
    .tabs{ margin:14px auto; max-width:1100px; }
    .tabs-nav{
      position:relative; display:flex; gap:10px; flex-wrap:wrap;
      padding:10px; background:linear-gradient(180deg,#ffffff,#f8fafc);
      border:1px solid #e5e7eb; border-radius:14px;
      box-shadow:0 8px 20px rgba(14,42,71,.06), 0 2px 6px rgba(14,42,71,.04);
    }
    .tab-btn{
      position:relative; overflow:hidden;
      border:1px solid #cbd5e1; border-radius:999px;
      padding:8px 14px; cursor:pointer; font-weight:700; letter-spacing:.2px;
      background:linear-gradient(180deg,#fff,#f3f6fb);
      box-shadow:0 1px 0 rgba(255,255,255,.7) inset, 0 1px 2px rgba(0,0,0,.05);
      transition:transform .2s ease, box-shadow .2s ease, background .2s ease, color .2s ease;
      color:#0f172a;
    }
    .tab-btn:hover{ transform:translateY(-1px); box-shadow:0 6px 16px rgba(14,42,71,.10); }
    .tab-btn.active{
      color:#fff; border-color:transparent;
      background:linear-gradient(135deg, var(--brand), var(--brand-2));
      box-shadow:0 10px 24px rgba(14,42,71,.22);
    }
    /* 漣漪 */
    .tab-btn::after{
      content:""; position:absolute; left:var(--x,50%); top:var(--y,50%);
      width:0; height:0; pointer-events:none;
      background:radial-gradient(circle, rgba(255,255,255,.45) 0%, rgba(255,255,255,0) 60%);
      transform:translate(-50%,-50%);
      transition:width .5s ease, height .5s ease;
    }
    .tab-btn:active::after{ width:180px; height:180px; transition:0s; }

    /* Ink bar */
    .tabs-nav .tab-indicator{
      position:absolute; left:0; bottom:-2px;
      height:4px; width:60px; border-radius:4px;
      background:linear-gradient(90deg, var(--ink-a), var(--ink-b));
      box-shadow:0 6px 18px rgba(96,165,250,.45);
      transition:transform .35s cubic-bezier(.22,.61,.36,1), width .35s cubic-bezier(.22,.61,.36,1);
    }

    /* 分頁內容卡片化 */
    .tab-pane{
      display:none; background:rgba(248,250,252,.6);
      border:1px solid #e5e7eb; border-radius:16px; padding:16px;
      box-shadow:0 8px 20px rgba(14,42,71,.06), 0 2px 6px rgba(14,42,71,.04);
    }
    .tab-pane.active{ display:block; }

    /* 欄位銀行 */
    .field-bank{ background:#f8fafc; border:1px solid #e5e7eb; border-radius:16px; padding:14px; }
    .field-bank h4{ margin:0 0 10px; font-weight:800; color:#0f172a; letter-spacing:.2px; }
    .field-list{ display:flex; flex-wrap:wrap; gap:10px; max-height:200px; overflow:auto; }
    .field-btn{
      border:1px solid #cbd5e1; border-radius:999px;
      padding:8px 12px; font-size:12px; line-height:1;
      background:linear-gradient(180deg, var(--chip-a), var(--chip-b));
      box-shadow:0 1px 0 rgba(255,255,255,.85) inset, 0 2px 8px rgba(2,6,23,.06);
      display:inline-flex; align-items:center; gap:8px; cursor:grab; user-select:none;
      transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .field-btn:hover{ transform:translateY(-2px); box-shadow:0 10px 18px rgba(2,6,23,.10); border-color:#a5b4fc; }
    .field-btn:focus{ outline:none; box-shadow:0 0 0 4px var(--ring); }
    .field-btn code{ background:#0ea5e9; color:#fff; border-radius:10px; padding:2px 6px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; font-size:11px; }
    .field-btn.dragging{ transform:rotate(-2deg) scale(.98); opacity:.85; }

    /* 表格內 chip */
    .field-chip{
      border:1px solid #d1d5db; border-radius:999px;
      padding:6px 10px; background:linear-gradient(180deg,#fff,#f8fafc);
      box-shadow:0 1px 0 rgba(255,255,255,.9) inset, 0 1px 3px rgba(2,6,23,.05);
      display:inline-flex; align-items:center; gap:6px;
      font-size:12px; line-height:1;
    }
    .field-chip code{ background:#10b981; color:#fff; border-radius:10px; padding:2px 6px; font-size:11px; }
    .field-chip code { display: none !important; }
    .field-chip .chip-del{
      border:none; background:transparent; font-size:16px; line-height:1; cursor:pointer;
      transition:transform .15s ease, color .15s ease;
    }
    .field-chip .chip-del:hover{ transform:scale(1.1); color:#ef4444; }
    

    /* 表格 hover */
    #dynTable tbody tr:hover td{ background:#f8fafc; }

    /* 拖曳目標強調 */
    .drop-ready{ outline:2px dashed #60a5fa; outline-offset:3px; background:#eef6ff!important; }

    /* 控制列 icon 按鈕尺寸 */
    .row-actions .icon-btn, .control-bar .icon-btn { padding:4px 10px; line-height:1; font-weight:700; }
    #btnMoveUp { display:none !important; }
    #btnMoveDown{ display:none !important; }


    /* === Tabs: 每行 6 顆按鈕 === */
    .tabs-nav{
        display:grid !important;
        grid-template-columns:repeat(6, minmax(0, 1fr));
        gap:10px;
    }

    .tabs-nav .tab-btn{
        width:100%;
        text-align:center;
    }

    /* 手機/窄螢幕自動縮列數（可留可拿掉） */
    @media (max-width: 900px){
        .tabs-nav{ grid-template-columns:repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 640px){
        .tabs-nav{ grid-template-columns:repeat(2, minmax(0, 1fr)); }
    }

  
    /* === Bottom Drawer: 強化版 === */
    .bottom-drawer{
      position: fixed;
      left: 0; right: 0; bottom: 0;
      z-index: 2147483647;         /* 提高層級，壓過 footer / 模態等 */
      pointer-events: none;        /* 只讓內層可點 */
    }
    .bottom-drawer > .drawer{
      pointer-events: auto;
      background:#fff;
      border-top-left-radius:16px; border-top-right-radius:16px;
      border:1px solid #e5e7eb; border-bottom:none;
      box-shadow: 0 -12px 30px rgba(2,6,23,.18);
      transform: translateY(calc(100% - 36px));  /* 預設只露出把手 */
      transition: transform .25s ease;
    }
    .bottom-drawer.open > .drawer{ transform: translateY(0); }

    /* 把手與內容：加上父層，避免與其他 .drawer* 規則衝突 */
    .bottom-drawer > .drawer > .handle{
      height:36px; display:flex; align-items:center; justify-content:center;
      cursor:pointer; user-select:none;
      background:linear-gradient(180deg,#fff,#f8fafc);
      border-top-left-radius:16px; border-top-right-radius:16px;
    }
    .bottom-drawer > .drawer > .handle .bar{
      width:48px; height:5px; border-radius:999px; background:#cbd5e1;
    }
    .bottom-drawer > .drawer > .content{
      max-height: 60vh; overflow:auto; padding:12px 16px; background:#fff;
    }
    @media (max-width: 640px){
      .bottom-drawer > .drawer > .content{ max-height: 70vh; }
    }


    /* 表頭固定，tbody 在外層容器內捲動 */
    #dynTable thead th {
      position: sticky;
      top: 0;
      z-index: 2;
    }

    /* 可捲動容器；高度由 JS 寫入 --table-max-h 控制可見列數上限 */
    .table-scroller {
      display: block;
      overflow: auto;
      max-height: var(--table-max-h, 9999px);
      -webkit-overflow-scrolling: touch;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
    }

    /* 視覺小優化：最後一列邊界 */
    #dynTable tbody tr:last-child td {
      border-bottom-width: 1px;
    }






    
  </style>

  <?php if(($_SESSION['privilege'] ?? '') === 'admin' && !empty($btns)) : ?>
      <div class="tabs" id="customizeTabs">
        <div class="tabs-nav" role="tablist">
          <button class="tab-btn" data-tab-target="#tab-4-1">4-1</button>
          <button class="tab-btn" data-tab-target="#tab-4-2">4-2</button>
        </div>

        <!-- 4-1：不顯示 43~52 -->
        <div class="tab-pane" id="tab-4-1" role="tabpanel">
            <div class="field-bank">
              <h4><?php echo htmlspecialchars($data['text']['customize'] ?? 'Customize'); ?></h4>
              <div class="field-list" id="fieldList">
                <?php foreach ($btns as $idx => $name){
                  $intIdx = is_numeric($idx) ? (int)$idx : null;
                  if ($intIdx !== null && $intIdx >= 43 && $intIdx <= 52) { continue; } // 跳過 43~52
                  $label = $data['text'][$name] ?? $name;
                ?>
                  <button class="field-btn"
                          draggable="true"
                          id="<?php echo 'fb-'.htmlspecialchars($idx); ?>"
                          data-field-index="<?php echo htmlspecialchars($idx); ?>"
                          data-field-name="<?php echo htmlspecialchars($name); ?>"
                          data-field-label="<?php echo htmlspecialchars($label); ?>"
                          title="<?php echo htmlspecialchars($label); ?>">
                    <code><?php /* echo htmlspecialchars($idx); */ ?></code><?php echo htmlspecialchars($label); ?>
                  </button>
                <?php } ?>
              </div>
            </div>
        </div>

        <!-- 4-2：只顯示 43~52 -->
        <div class="tab-pane" id="tab-4-2" role="tabpanel">
            <div class="field-bank">
              <h4><?php echo htmlspecialchars($data['text']['customize'] ?? 'Customize'); ?></h4>
              <div class="field-list" id="fieldList-2">
                <?php for ($idx = 43; $idx <= 52; $idx++):
                  if (!isset($btns[$idx])) continue;
                  $name  = $btns[$idx];
                  $label = $data['text'][$name] ?? $name;
                ?>
                  <button class="field-btn"
                          draggable="true"
                          id="fb-<?php echo htmlspecialchars($idx); ?>"
                          data-field-index="<?php echo htmlspecialchars($idx); ?>"
                          data-field-name="<?php echo htmlspecialchars($name); ?>"
                          data-field-label="<?php echo htmlspecialchars($label); ?>"
                          title="<?php echo htmlspecialchars($label); ?>">
                    <code></code><?php echo htmlspecialchars($label); ?>
                  </button>
                <?php endfor; ?>
              </div>
            </div>
        </div>
      </div>


  <?php endif; ?>

  <script>
    // 若沒有 window.RULES，就先有個基本殼
    window.RULES = window.RULES || {};
    window.RULES.manual_groups = (window.RULES.manual_groups || []);

    (function addOnlyFirstPairs(){
      // 建議名稱改清楚一點：groups
      const pairs = [
        ['4170','4171'], ['4176','4177'],
        ['4190','4191'], ['4172','4173'], ['4174','4175'], ['4178','4179'],
        ['4180','4181'], ['4182','4183'], ['4184','4185'],
        ['4242','4243'], ['4244','4245'], ['4246','4247'],
        ['4248','4249'], ['4250','4251'], ['4252','4253'],
        ['4254','4255'], ['4256','4257'], ['4258','4259'],
        ['4260','4261'],
        ['4102','4103','4104','4105','4106','4107','4108','4109','4110','4111'],
        ['4112','4113','4114','4115','4116','4117','4118','4119','4120','4121'],
        ['4122','4123','4124','4125','4126','4127','4128','4129','4130','4131'],
        ['4132','4133','4134','4135','4136','4137'],
        ['4138','4139','4140','4141','4142','4143'],

        // ※ 這組代表「條碼」：只允許 4192，其餘 4193~4241 都會被擋
        ['4192','4193','4194','4195','4196','4197','4198','4199','4200','4201','4202','4203','4204','4205','4206','4207','4208','4209','4210','4211','4212','4213','4214','4215','4216','4217','4218','4219','4220','4221','4222','4223','4224','4225','4226','4227','4228','4229','4230','4231','4232','4233','4234','4235','4236','4237','4238','4239','4240','4241']
      ];

      // ✅ 正確寫法：用 pairs 迭代，每次取出一個 group
      for (const group of pairs){
        const vals = group.map(String);
        window.RULES.manual_groups.push({
          values: vals,
          max: 1,
          allow: [vals[0]],   // 只允許第一個，其餘同組都會被擋
        });
      }

      // 4165 / 4166 互斥但都允許（一次只能出現一個）
      window.RULES.manual_groups.push({
        values: ['4165', '4166'],
        max: 1,
        allow: ['4165']
      });
    })();


  </script>


  <script>
    const FIELD_LABEL_BY_INDEX = <?php
      $labMap = [];
      if (!empty($btns)) {
        foreach ($btns as $i=>$n){ $labMap[$i] = $data['text'][$n] ?? $n; }
      }
      echo json_encode($labMap, JSON_UNESCAPED_UNICODE);
    ?> || {};
  </script>

  <div class="table-wrap">
    <div class="control-bar">
      <?php if(($_SESSION['privilege'] ?? '') === 'admin'){ ?>
        <button id="btnAddRow" class="w3-btn w3-round-large"><?php echo $L['add']; ?></button>
        <button id="btnDeleteSelected" class="w3-btn w3-round-large"><?php echo $L['delete_sel']; ?></button>
        <button id="btnDeleteAll" class="w3-btn w3-round-large"><?php echo $L['delete_all']; ?></button>
        <button id="btnSave" class="w3-btn w3-round-large"><?php echo $L['save']; ?></button>
      <?php } ?>
    </div>

    
  <div id="tableScroller" class="table-scroller">
    <table id="dynTable">
      <thead>
        <tr>
          <th style="width:46px;text-align:center">
            <?php if(($_SESSION['privilege'] ?? '') === 'admin'){ ?>
              <input type="checkbox" id="ckAll">
            <?php } ?>
          </th>
          <th><?php echo $L['no']; ?></th>
          <th><?php echo $L['read']; ?></th>
          <th class="col-input"><?php echo $L['input']; ?></th>
          <th><?php echo $L['result']; ?></th>
          <?php if(($_SESSION['privilege'] ?? '') === 'admin'){ ?>
            <th></th>
            <th></th>
            <th style="width:90px"></th>
          <?php } ?>
        </tr>
      </thead>
      <tbody id="dynTbody"><!-- rows injected by JS --></tbody>
    </table>
    </div>
  </div>
</div>

<?php if($_SESSION['privilege'] != 'admin'){ ?>
<script>
  $(document).ready(function () {
    if (typeof disableAllButtonsAndInputs === 'function') disableAllButtonsAndInputs();
    document.getElementById("home").disabled = false;
    const ds = document.getElementById("data_select");
    if (ds) ds.disabled = false;
  });
</script>
<?php } ?>

<!-- Bottom Drawer: Table Data -->
<div class="bottom-drawer" id="tableDrawer" aria-expanded="true">
  <div class="drawer">
    <!-- 把手 -->
    <div class="handle" role="button" tabindex="0"
         onclick="toggleTableDrawer()"
         onkeydown="if(event.key==='Enter'||event.key===' '){toggleTableDrawer();event.preventDefault();}">
      <div class="bar"></div>
    </div>

    <div class="content">
      <!-- Table Data Information -->
      <div id="TableDataInput">
        <div class="w3-border-bottom w3-center" style="font-size:20px;">Modbus 對照表</div>

        <table class="w3-table-all">
            <tr>
              <th class="w3-left-align">Modbus 位置</th>
              <th class="w3-left-align">Bytes</th>
              <th class="w3-left-align">名稱</th>
            </tr>

            <?php
             // 1) 從 JS 的 SPECIAL_MUTEX 做一份 PHP 對照（與 JS 完全一致）
              $specialMutex = [
                0  => range(4165,4166),           // 系統流水號
                2  => range(4096,4101),           // 時間(Y-M-D H:I:S)

                // 3  => [4144],                  //（JS 未定義，若需要請同步加回 JS）

                5  => range(4102,4111),           // 鎖附控制器序號
                6  => range(4112,4121),           // 鎖附起子型號
                7  => range(4122,4131),           // 鎖附起子序號（修正原本 4122~4121 的錯誤）
                9  => [4148],                     // JOBID
                10 => range(4132,4137),           // JOBNAME
                11 => [4149],                     // SEQID
                12 => range(4138,4143),           // SEQNAME
                14 => [4157],                     // 扭力單位（修正原本 4148）
                15 => [4153],                     // 鎖附目標類型
                16 => [4170,4171],                // 目標扭力
                17 => [4176,4177],                // 目標角度
                19 => [4158],                     // 總鎖附時間(秒)
                20 => [4155,4156],                // 鎖附扭力
                21 => [4176,4177],                // 鎖附角度（修正原本 4170/4171）
                22 => [4190,4191],                // 鎖附總角度
                23 => [4161],                     // 計數模式
                24 => [4162],                     // 鎖附顆數
                25 => [4163],                     // 總顆數
                26 => [4164],                     // 狀態
                27 => [4168],                     // 錯誤狀態
                28 => [4152],                     // 鎖附起子轉向
                29 => [4169],                     // 鎖附轉速
                30 => [4172,4173],                // 鎖附扭力上限
                31 => [4174,4175],                // 鎖附扭力下限
                32 => [4178,4179],                // 鎖附角度上限
                33 => [4180,4181],                // 鎖附角度下限
                35 => [4182,4183],                // 鎖附門檻點扭力
                37 => [4184,4185],                // 鎖附降速點扭力
                39 => [4186],                     // 鎖附降速轉速
                42 => range(4192,4241),           // 條碼

                // 步驟 1~5（依 JS）
                43 => [4242,4243],                // 步驟1扭力（修正原本放成角度）
                44 => [4244,4245],                // 步驟1角度（修正原本放成扭力）
                45 => [4246,4247],                // 步驟2扭力
                46 => [4248,4249],                // 步驟2角度
                47 => [4250,4251],                // 步驟3扭力
                48 => [4252,4253],                // 步驟3角度
                49 => [4254,4255],                // 步驟4扭力
                50 => [4256,4257],                // 步驟4角度
                51 => [4258,4259],                // 步驟5扭力
                52 => [4260,4261],                // 步驟5角度
              ];

              // 直接算出「起始位址」與「Bytes(長度)」
              $startByIdx = [];
              $bytesByIdx = [];
              foreach ($specialMutex as $k => $vals) {
                $vals = array_map('intval', $vals);
                $startByIdx[(int)$k] = min($vals);     // 起始位置 = 最小值
                $bytesByIdx[(int)$k] = count($vals);   // Bytes   = 數量（需要別的規則可在這裡改）
              }

              // 2) 預設值（沒在 specialMutex 的就用這個）
              $defaultAddress = 4165;
              $defaultBytes   = 2;

              // 3) 個別覆寫（可選）：若你想手動改某幾筆
              $overrides = [
                // 例：把 idx=43 的位址改 5000、Bytes 改 6
                // 43 => ['addr' => 5000, 'bytes' => 6],
                // 也可用名稱當 key：'Torque' => ['addr'=>5010,'bytes'=>4],
              ];

              if (!empty($btns) && is_array($btns)) {
                foreach ($btns as $idx => $name) {
                  $idx   = is_numeric($idx) ? (int)$idx : $idx;
                  $label = $data['text'][$name] ?? $name;

                  // 先用 SPECIAL_MUTEX 推導
                  $addr  = $startByIdx[$idx] ?? $defaultAddress;
                  $bytes = $bytesByIdx[$idx] ?? $defaultBytes;

                  // 再看是否有覆寫
                  if (isset($overrides[$idx])) {
                    $addr  = $overrides[$idx]['addr']  ?? $addr;
                    $bytes = $overrides[$idx]['bytes'] ?? $bytes;
                  } elseif (isset($overrides[$name])) {
                    $addr  = $overrides[$name]['addr']  ?? $addr;
                    $bytes = $overrides[$name]['bytes'] ?? $bytes;
                  }

                  echo '<tr>';
                  echo '  <td class="w3-left-align">'.htmlspecialchars((string)$addr).'</td>';
                  echo '  <td class="w3-left-align">'.htmlspecialchars((string)$bytes).'</td>';
                  echo '  <td class="w3-left-align">'.htmlspecialchars($label).'</td>';
                  echo '</tr>';
                }
              } else {
                echo '<tr><td colspan="3" class="w3-left-align">（沒有資料）</td></tr>';
              }
            ?>
          </table>



        </div>
      </div>
  


    </div>
  </div>
</div>



<?php require APPROOT . 'views/inc/footer.php'; ?>

<script>
// 若沒有 window.RULES，就先有個基本殼
window.RULES = window.RULES || {};
window.RULES.manual_groups = (window.RULES.manual_groups || []);

(function addOnlyFirstPairs(){
  // 建議名稱改清楚一點：groups
  const pairs = [
    ['4170','4171'], ['4176','4177'],
    ['4190','4191'], ['4172','4173'], ['4174','4175'], ['4178','4179'],
    ['4180','4181'], ['4182','4183'], ['4184','4185'],
    ['4242','4243'], ['4244','4245'], ['4246','4247'],
    ['4248','4249'], ['4250','4251'], ['4252','4253'],
    ['4254','4255'], ['4256','4257'], ['4258','4259'],
    ['4260','4261'],
    ['4102','4103','4104','4105','4106','4107','4108','4109','4110','4111'],
    ['4112','4113','4114','4115','4116','4117','4118','4119','4120','4121'],
    ['4122','4123','4124','4125','4126','4127','4128','4129','4130','4131'],
    ['4132','4133','4134','4135','4136','4137'],
    ['4138','4139','4140','4141','4142','4143'],

    // ※ 這組代表「條碼」：只允許 4192，其餘 4193~4241 都會被擋
    ['4192','4193','4194','4195','4196','4197','4198','4199','4200','4201','4202','4203','4204','4205','4206','4207','4208','4209','4210','4211','4212','4213','4214','4215','4216','4217','4218','4219','4220','4221','4222','4223','4224','4225','4226','4227','4228','4229','4230','4231','4232','4233','4234','4235','4236','4237','4238','4239','4240','4241']
  ];

  // ✅ 正確寫法：用 pairs 迭代，每次取出一個 group
  for (const group of pairs){
    const vals = group.map(String);
    window.RULES.manual_groups.push({
      values: vals,
      max: 1,
      allow: [vals[0]],   // 只允許第一個，其餘同組都會被擋
    });
  }

  // 4165 / 4166 互斥但都允許（一次只能出現一個）
  window.RULES.manual_groups.push({
    values: ['4165', '4166'],
    max: 1,
    allow: ['4165']
  });
})();


// 從宣告式規則產生「不允許的手動值」集合：凡在同組且不在 allow 裡者，一律禁止
function buildForbiddenSetFromRules(){
  const set = new Set();
  const groups = (window.RULES && window.RULES.manual_groups) || [];
  for (const g of groups){
    const vals  = (g.values || []).map(String);
    const allow = new Set((g.allow || []).map(String));
    if (allow.size){
      for (const v of vals){
        if (!allow.has(v)) set.add(v);
      }
    }
  }
  return set;
}
let FORBID_MANUAL = buildForbiddenSetFromRules();

// 產生對應訊息（多語系可自行換成 $L）
function forbidMsg(digit){
  const groups = (window.RULES && window.RULES.manual_groups) || [];
  for (const g of groups){
    const vals = (g.values || []).map(String);
    if (vals.includes(String(digit)) && g.allow && g.allow.length){
      const allowStr = g.allow.map(String).join('、');
      return (window.L?.forbidden_idx || '此欄位不允許輸入') + ` ${digit}，只允許 ${allowStr}。`;
    }
  }
  return (window.L?.forbidden_idx || '此欄位不允許輸入') + ` ${digit}。`;
}


</script>


<script>
    (function(){

    // 允許空白儲存（清空）
    const ALLOW_EMPTY_SAVE = true; 

    // 想要空表格就設 false
    const START_WITH_EMPTY_ROW = false;  

    // 從 PHP 帶入
    let CSV_DATA = <?php echo json_encode($data['data_csv'] ?? null, JSON_UNESCAPED_UNICODE); ?>;
    const FIELD_NAME_BY_INDEX = <?php echo json_encode($btns ?? [], JSON_UNESCAPED_UNICODE); ?> || {};

    let ROW_UID = 1;
    const IS_ADMIN = <?php echo json_encode(($_SESSION['privilege'] ?? '') === 'admin'); ?>;
    const L = <?php echo json_encode($L, JSON_UNESCAPED_UNICODE); ?>;
    const SAVE_URL = '?url=Customize/save_positions';

    const tbody = document.getElementById('dynTbody');
    const btnAdd = document.getElementById('btnAddRow');
    const btnSave = document.getElementById('btnSave');
    updateSaveButtonState(); 
    const ckAll  = document.getElementById('ckAll');
    const btnDelSel = document.getElementById('btnDeleteSelected');
    const btnDelAll = document.getElementById('btnDeleteAll');

    let poller = null;
    let insertMarker = null;

    (function i18nAlertify(){
      if (!window.alertify) return;
      const ok = (L && (L.ok_btn || L.ok)) || 'OK';
      const cancel = (L && (L.cancel_btn || L.cancel)) || 'Cancel';

      // alertify.js 1.x：全域字彙
      if (alertify.defaults && alertify.defaults.glossary) {
        alertify.defaults.glossary.ok = ok;
        alertify.defaults.glossary.cancel = cancel;
      }

      // 舊版相容（若仍有 set('labels',...) 可用）
      if (typeof alertify.set === 'function') {
        try { alertify.set('labels', { ok, cancel }); } catch(e){}
      }
    })();

    /* =========================
    * 互斥規則（唯一保留的限制）
    * dbIndex "0"(id) 與 手動數值 4165、4166 互斥
    * ========================= */

    const RANGE = (from, to) => Array.from({ length: to - from + 1 }, (_, i) => String(from + i));

    const SPECIAL_MUTEX = [
        { dbIndex: '0', manualValues: ['4165','4166']  }, //id
        { dbIndex: '2', manualValues: ['4096','4097','4098','4099','4100','4101'] }, //時間(Y-M-D H:I:S)
        { dbIndex: '5', manualValues: ['4102','4103','4104','4105','4106','4107','4108','4109','4110','4111'] }, //鎖附控制器序號 
        { dbIndex: '6', manualValues: ['4112','4113','4114','4115','4116','4117','4118','4119','4120','4121'] }, //鎖附起子型號 
        { dbIndex: '7', manualValues: ['4122','4123','4124','4125','4126','4127','4128','4129','4130','4131'] }, //鎖附起子序號 
        { dbIndex: '8', manualValues: ['4167'] },//鎖附起子狀態
        { dbIndex: '9', manualValues: ['4148'] },//JOBID
        { dbIndex: '10', manualValues: ['4132','4133','4134','4135','4136','4137'] },//JOBNANE
        { dbIndex: '11', manualValues: ['4149'] },//SEQID
        { dbIndex: '12', manualValues: ['4138','4139','4140','4141','4142','4143'] },//SEQNANE
        { dbIndex: '14', manualValues: ['4157'] },//扭力單位
        { dbIndex: '15', manualValues: ['4153'] },//鎖附目標類型
        { dbIndex: '16', manualValues: ['4170','4171'] },//目標扭力
        { dbIndex: '17', manualValues: ['4176','4177'] },//目標角度
        { dbIndex: '19', manualValues: ['4158'] },//總鎖附時間(秒)
        { dbIndex: '20', manualValues: ['4155','4156'] },//鎖附扭力
        { dbIndex: '21', manualValues: ['4176','4177'] },//鎖附角度
        { dbIndex: '22', manualValues: ['4190','4191'] },//鎖附總角度
        { dbIndex: '23', manualValues: ['4161'] },//計數模式
        { dbIndex: '24', manualValues: ['4162'] },//鎖附顆數 
        { dbIndex: '25', manualValues: ['4163'] },//總顆數 
        { dbIndex: '26', manualValues: ['4164'] },//狀態
        { dbIndex: '27', manualValues: ['4168'] },//錯誤狀態
        { dbIndex: '28', manualValues: ['4152'] },//鎖附起子轉向 
        { dbIndex: '29', manualValues: ['4169'] },//鎖附轉速 
        { dbIndex: '30', manualValues: ['4172','4173'] },//鎖附扭力上限
        { dbIndex: '31', manualValues: ['4174','4175'] },//鎖附扭力下限
        { dbIndex: '32', manualValues: ['4178','4179'] },//鎖附角度上限
        { dbIndex: '33', manualValues: ['4180','4181'] },//鎖附角度下限
        { dbIndex: '35', manualValues: ['4182','4183'] },//鎖附門檻點扭力
        { dbIndex: '37', manualValues: ['4184','4185'] },//鎖附降速點扭力
        { dbIndex: '39', manualValues: ['4186'] },//鎖附降速轉速
        { dbIndex: '42', manualValues: RANGE(4192, 4241) }, //條碼

        { dbIndex: '43', manualValues: ['4242', '4243'] }, // 步驟1扭力
        { dbIndex: '44', manualValues: ['4244', '4245'] }, // 步驟1角度

        { dbIndex: '45', manualValues: ['4246', '4247'] }, // 步驟2扭力
        { dbIndex: '46',  manualValues:['4248', '4249'] }, // 步驟2角度

        { dbIndex: '47', manualValues: ['4250', '4251'] }, // 步驟3扭力
        { dbIndex: '48', manualValues: ['4252', '4253'] }, // 步驟3角度

        { dbIndex: '49', manualValues: ['4254', '4255'] }, // 步驟4扭力
        { dbIndex: '50', manualValues: ['4256', '4257'] }, // 步驟4角度

        { dbIndex: '51', manualValues: ['4258', '4259'] }, // 步驟5扭力
        { dbIndex: '52', manualValues: ['4260', '4261'] }, // 步驟5角度
    
    ];

    // 建表：manual 值 → 可能互斥的 dbIndex 清單（用於即時輸入攔截）
    const __MX_MANUAL_TO_DBIDXS = (() => {
      const map = new Map();
      for (const r of (SPECIAL_MUTEX || [])) {
        const dbi = String(r.dbIndex);
        for (const mv of (r.manualValues || [])) {
          const k = String(mv);
          const set = map.get(k) || new Set();
          set.add(dbi);
          map.set(k, set);
        }
      }
      return map;
    })();



    // 是否有（其他格）出現指定的手動數值；exceptTd 可排除目前編輯中的格
    function hasManualOf(values, exceptTd = null){
        const inputs = tbody.querySelectorAll('#dynTbody td.drop-target[data-drop="read"] input.table-input[type="text"]');
        for (const el of inputs){
        if (exceptTd && el.closest('td') === exceptTd) continue;
        const v = String(el.value || '').trim();
        if (!v) continue;
        const m = /^#?\s*(\d+)\b/.exec(v);
        if (m && values.includes(m[1])) return true;
        }
        return false;
    }

    // 是否已存在指定 dbIndex 的 chip
    function chipExistsForIndex(idx){
        return !!tbody.querySelector(`.field-chip[data-field-index="${String(idx)}"]`);
    }

    /* ===== DOM 版本（避免舊回應覆蓋） ===== */
    let DOM_VERSION = 0;
    const bumpDom = () => { DOM_VERSION++; };

    const MAX_ROWS = 100;
    const MSG_MAX_ROWS = (L && (L.max_rows || L['max_rows'])) || 'Maximum 100 rows allowed.';

    // 自動重編號觀察
    if (tbody && 'MutationObserver' in window) {
        const autoRenumber = new MutationObserver(() => requestAnimationFrame(renumber));
        autoRenumber.observe(tbody, { childList: true });
    }

    /* ===== 工具 ===== */
    function getDataRows(){ return [...tbody.querySelectorAll('tr:not(.insert-marker)')]; }
    function headerColspan(){ return document.querySelectorAll('#dynTable thead th').length || 5; }

    function ensureInsertMarker(){
        if (insertMarker) return insertMarker;
        insertMarker = document.createElement('tr');
        insertMarker.className = 'insert-marker';
        const td = document.createElement('td');
        td.colSpan = headerColspan();
        td.innerHTML = '<div class="insert-line"></div>';
        insertMarker.appendChild(td);
        return insertMarker;
    }
    function placeMarkerBefore(tr){ const m = ensureInsertMarker(); if (tr) tbody.insertBefore(m, tr); else tbody.appendChild(m); }
    function clearInsertMarker(){ if (insertMarker?.parentNode) insertMarker.parentNode.removeChild(insertMarker); insertMarker = null; }
    function computeBeforeTrByY(clientY){
        const rows = getDataRows();
        for (const r of rows){
        const rect = r.getBoundingClientRect(); const mid = rect.top + rect.height/2;
        if (clientY < mid) return r;
        }
        return null;
    }

    // 上/下移（統一版本，支援 silent）— 取代原有 moveRow
    function moveRow(tr, dir, { silent=false } = {}){
      if (!tr || !tbody) return;
      const rows = getDataRows();
      const idx  = rows.indexOf(tr);
      if (idx === -1) return;

      const newIdx = idx + dir;
      if (newIdx < 0 || newIdx >= rows.length) return;

      const target = rows[newIdx];
      if (dir < 0) {
        tbody.insertBefore(tr, target);
      } else {
        const after = target.nextSibling;
        after ? tbody.insertBefore(tr, after) : tbody.appendChild(tr);
      }

      if (!silent) {
        renumber?.();
        syncCkAllState?.();
        bumpDom?.();
        poller?.triggerNow?.();

        // ★ 保持可視：移動後把它捲回可見範圍
        if (typeof window.__ensureRowVisible === 'function') {
          window.__ensureRowVisible(tr);
        }
        if (typeof window.__fitTableVisibleRows === 'function') {
          window.__fitTableVisibleRows();
        }
      }
    }


    // 批次移動（維持相對順序）— 取代原有 moveSelectedRows
    function moveSelectedRows(dir){
      const rows = getDataRows();
      const selected = rows.filter(r => r.querySelector('.row-ck')?.checked);
      if (!selected.length) {
        if (window.alertify) alertify.alert('Info', L['none_selected'] || 'Please select at least one row.');
        else alert(L['none_selected'] || 'Please select at least one row.');
        return;
      }

      // 移動時維持相對順序
      if (dir < 0) { selected.forEach(tr => moveRow(tr, -1, { silent:true })); }
      else { [...selected].reverse().forEach(tr => moveRow(tr, +1, { silent:true })); }

      renumber?.();
      syncCkAllState?.();
      bumpDom?.();
      poller?.triggerNow?.();

      // ★ 群組保持可視：以「群組第一列」為基準
      const anchor = selected[0];
      if (typeof window.__ensureRowVisible === 'function') {
        window.__ensureRowVisible(anchor);
      }
      if (typeof window.__fitTableVisibleRows === 'function') {
        window.__fitTableVisibleRows();
      }
    }

    function getRowCount(){ return tbody ? tbody.querySelectorAll('tr:not(.insert-marker)').length : 0; }

    // 是否任何一列有「有效內容」：有 chip、或讀/輸入欄有數字
    function anyDataExists(){
      const rows = [...tbody.querySelectorAll('tr:not(.insert-marker)')];
      for (const tr of rows){
        const tdRead  = tr.querySelector('td:nth-child(3)');
        const tdInput = tr.querySelector('td:nth-child(4)');
        const hasChip = !!tdRead?.querySelector('.field-chip');
        const readVal = tdRead?.querySelector('input.table-input[type="text"]')?.value?.trim() || '';
        const inputVal= tdInput?.querySelector('input.table-input[type="text"]')?.value?.trim() || '';
        if (hasChip || readVal !== '' || inputVal !== '') return true;
      }
      return false;
    }

    // 依據是否有內容啟用/停用儲存鈕
    function updateSaveButtonState(){

      if (!btnSave) return;
      const hasData = anyDataExists();

      if (ALLOW_EMPTY_SAVE) {
        btnSave.disabled = false;  // 空也可按
        btnSave.title = hasData ? '' : (L['confirm_save_empty'] || '');
      } else {
        btnSave.disabled = !hasData; // 需要有資料才可按
        btnSave.title = '';
      }

      // 不要任何會讓它變淡或擋點擊的樣式
      btnSave.classList.remove('w3-opacity', 'w3-disabled');
    }



    function updateAddButtonState(){ if (btnAdd) btnAdd.disabled = getRowCount() >= MAX_ROWS; }

    /* ===== （同時支援 4-1 與 4-2） 分頁  ===== */
      function bindFieldBank(containerId){
        const bank = document.getElementById(containerId);
        if (!bank) return;

        // 確保每顆按鈕有 id=fb-<index>
        [...bank.querySelectorAll('.field-btn')].forEach(btn => {
          const idx = btn.getAttribute('data-field-index');
          if (idx && !btn.id) btn.id = 'fb-' + String(idx);
        });

        let localDragPayload = null;

        bank.addEventListener('dragstart', (e) => {
          const btn = e.target.closest('.field-btn'); if (!btn) return;
          const idx = btn.getAttribute('data-field-index') || '';
          const name= btn.getAttribute('data-field-name') || '';
          localDragPayload = {
            idx, name,
            label: btn.getAttribute('data-field-label') || btn.getAttribute('title') || '',
            originId: btn.id
          };
          try {
            e.dataTransfer.effectAllowed = 'copyMove';
            e.dataTransfer.setData('text/plain', idx);
          } catch {}
        });

        // 點一下 → 放到聚焦或第一個空白「讀取位置」
        bank.addEventListener('click', (e) => {
          const btn = e.target.closest('.field-btn'); if (!btn) return;
          const idx   = btn.getAttribute('data-field-index') || '';
          const name  = btn.getAttribute('data-field-name')  || '';
          const label = btn.getAttribute('data-field-label') || btn.getAttribute('title') || '';
          const originId = btn.id;

          let targetTd = document.activeElement?.closest?.('td.drop-target[data-drop="read"]') || null;
          if (!targetTd) {
            targetTd = [...tbody.querySelectorAll('td.drop-target[data-drop="read"]')].find(td => {
              const chip = td.querySelector('.field-chip'); if (chip) return false;
              const inp  = td.querySelector('input.table-input'); return !inp || !inp.value.trim();
            }) || null;
          }
          if (!targetTd) return;
          assignFieldToCell({ idx, name, label, originId }, targetTd);
        });

        // 美化拖曳狀態（可選）
        bank.addEventListener('dragstart', e => { const b = e.target.closest('.field-btn'); if (b) b.classList.add('dragging'); });
        bank.addEventListener('dragend',   e => { const b = e.target.closest('.field-btn'); if (b) b.classList.remove('dragging'); });
      }

      // 綁定兩個銀行
      bindFieldBank('fieldList');
      bindFieldBank('fieldList-2');

    

    /* ===== 表格拖放（合併 dragover） ===== */
    tbody.addEventListener('dragover', (e) => {
        const isFieldDrag = !!dragPayload || ( ()=>{ try{ return !!e.dataTransfer.getData('text/plain'); }catch{ return false; } } )();
        if (!isFieldDrag) return;

        e.preventDefault();
        const td = e.target.closest('td.drop-target[data-drop="read"]');
        if (td) td.classList.add('drop-ready');
        const beforeTr = computeBeforeTrByY(e.clientY);
        placeMarkerBefore(beforeTr);
    });
    tbody.addEventListener('dragleave', (e) => {
        if (!tbody.contains(e.relatedTarget)) clearInsertMarker();
        const td = e.target.closest('td.drop-target[data-drop="read"]'); if (td) td.classList.remove('drop-ready');
    });
    tbody.addEventListener('drop', (e) => {
        e.preventDefault();
        const td = e.target.closest('td.drop-target[data-drop="read"]');

        let idx = '', originId = '', name = '', label = '';
        try { idx = e.dataTransfer.getData('text/plain'); } catch {}
        if (!idx && dragPayload) idx = dragPayload.idx;

        if (dragPayload && dragPayload.idx === idx) {
        originId = dragPayload.originId || ('fb-' + String(idx));
        name     = dragPayload.name || '';
        label    = dragPayload.label || '';
        } else if (idx) {
        const srcBtn = document.getElementById('fb-' + String(idx));
        name     = srcBtn?.getAttribute('data-field-name') || '';
        label    = srcBtn?.getAttribute('data-field-label') || srcBtn?.getAttribute('title') || '';
        originId = srcBtn?.id || ('fb-' + String(idx));
        }

        if (td) {
        td.classList.remove('drop-ready');
        if (idx) assignFieldToCell({ idx, name, label, originId }, td);
        clearInsertMarker();
        } else if (insertMarker && idx) {
        const rows = getDataRows();
        const beforeTr = insertMarker.nextElementSibling;
        const pos = beforeTr ? (rows.indexOf(beforeTr) + 1) : (rows.length + 1);
        const newTr = insertRowAt(pos, '', '', '');
        if (newTr) {
            const newReadTd = newTr.querySelector('td:nth-child(3)');
            assignFieldToCell({ idx, name, label, originId }, newReadTd);
            updateAddButtonState?.(); bumpDom?.(); poller?.triggerNow?.();
        }
        clearInsertMarker();
        }
        dragPayload = null;
    });

    /* ===== 指派 chip（含互斥檢查） ===== */
    function assignFieldToCell(payload, td) {
        const { idx, name, label, originId: givenOriginId } = payload;
        if (!td) return;

        // 互斥：若要放入 dbIndex 0，但表內已有 4165/4166 的手動值 → 不允許
        for (const rule of SPECIAL_MUTEX){
        if (String(idx) === rule.dbIndex && hasManualOf(rule.manualValues, td)){
            const msg = (L && (L['in_use'] || L['forbidden_idx'])) || '此欄位已被使用或與手動值互斥。';
            if (window.alertify) alertify.alert(L['title_error'] || 'Error', msg);
            else alert(msg);
            return; // 不指派
        }
        }

        const originId = givenOriginId || ('fb-' + String(idx));

        // 保持唯一：移除舊 chip
        const prevChip = tbody.querySelector(`.field-chip[data-origin-id="${cssEscape(originId)}"]`);
        if (prevChip && prevChip.closest('td') !== td) {
        unassignFromCell(prevChip.closest('td'), { restoreButton: false });
        }

        // 目標有既有 chip → 還原它
        const existingChip = td.querySelector('.field-chip');
        if (existingChip) restoreFieldBankButton(existingChip.dataset.originId);

        // 隱藏欄位銀行按鈕
        const bankBtn = document.getElementById(originId) || findBankButtonByIndex(idx);
        if (bankBtn) { bankBtn.style.display = 'none'; bankBtn.dataset.assignedRowId = td.closest('tr')?.dataset?.rowId || ''; }

        // 渲染 chip
        td.innerHTML = '';
        const chip = document.createElement('div');
        chip.className = 'field-chip';
        chip.dataset.source = 'db';
        chip.dataset.fieldIndex = String(idx);
        chip.dataset.originId = originId;

        const displayText = (label && String(label).trim() !== '')
        ? label
        : (FIELD_LABEL_BY_INDEX[idx] || name || '');

        chip.innerHTML = `
        <code>${escapeHtml(String(idx))}</code>${escapeHtml(displayText)}
        <button type="button" class="chip-del" aria-label="Remove">×</button>
        `;
        td.appendChild(chip);

        // 隱藏欄位（供保存）
        ensureHiddenInputs(td, { read_db_index: String(idx), read_db_name: String(name || ''), read_src: 'db' });

        // 刪除 chip
        chip.querySelector('.chip-del')?.addEventListener('click', () => {
        unassignFromCell(td, { restoreButton: true });
        validateUniqueColumns();
        });

        validateUniqueColumns();
        bumpDom(); poller?.triggerNow?.();

        //有資料時 儲存
        updateSaveButtonState(); 
    }

    /* ===== 輔助 ===== */
    function ensureHiddenInputs(td, data) {
        setHidden(td, 'read_db_index', data.read_db_index);
        setHidden(td, 'read_db_name',  data.read_db_name);
        setHidden(td, 'read_src',      data.read_src);
    }
    function setHidden(td, name, value) {
        let input = td.querySelector(`input[type="hidden"][name="${cssEscape(name)}"]`);
        if (!input) { input = document.createElement('input'); input.type='hidden'; input.className='table-input'; input.name = name; td.appendChild(input); }
        input.value = value;
    }

    function hydrateFromCsv(csv){
        if (!csv) return;

        const len = Math.max(
        Array.isArray(csv.no) ? csv.no.length : 0,
        Array.isArray(csv.read_position) ? csv.read_position.length : 0,
        Array.isArray(csv.input_position) ? csv.input_position.length : 0,
        Array.isArray(csv.result) ? csv.result.length : 0
        );

        for (let i = 0; i < len && i < 100; i++) {
        const r = addRow('', '', ''); if (!r) break;

        const tdRead  = r.querySelector('td:nth-child(3)');
        const tdInput = r.querySelector('td:nth-child(4) input.table-input');
        const tdRes   = r.querySelector('.result-input');

        const rpRaw = (csv.read_position && csv.read_position[i] != null) ? String(csv.read_position[i]).trim() : '';
        const ipRaw = (csv.input_position && csv.input_position[i] != null) ? String(csv.input_position[i]).trim() : '';
        const rsRaw = (csv.result && csv.result[i] != null) ? String(csv.result[i]).trim() : '';

        const inRead = tdRead.querySelector('input.table-input[type="text"]');

        // CSV 若是資料庫欄位格式：# <idx> <name?>
        const m = /^#\s*(\d+)(?:\s+(.+))?$/i.exec(rpRaw);
        if (m) {
            const idx  = m[1];
            const name = (m[2] && m[2].trim()) || (FIELD_NAME_BY_INDEX[idx] || '');
            // 直接交給 assignFieldToCell（其內含互斥檢查）
            assignFieldToCell({ idx, name, originId: 'fb-' + String(idx) }, tdRead);
        } else {
            // 純手動值，先塞；互斥會在下面的 change/input 監聽處理
            if (inRead){ inRead.value = rpRaw; bindExclusive416xListeners(inRead); }
        }

        if (tdInput) tdInput.value = ipRaw;
        if (tdRes)   tdRes.value   = rsRaw;
        }

        validateUniqueColumns?.();
        if (btnAdd) {
          const count = tbody ? tbody.querySelectorAll('tr').length : 0;
          btnAdd.disabled = count >= 100;
        }

        //載入完成後更新儲存鈕
        updateSaveButtonState();
    }

    function findBankButtonByIndex(idx) {
      return document.querySelector(`.field-btn[data-field-index="${cssEscape(String(idx))}"]`) || null;
    }
    function findBankButtonByOrigin(originId) {
      return document.querySelector(`.field-btn#${cssEscape(originId)}`) || null;
    }
    function restoreFieldBankButton(originId) {
        const btn = document.getElementById(originId) || findBankButtonByOrigin(originId);
        if (btn) { btn.style.display = ''; delete btn.dataset.assignedRowId; }
    }

    function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
    function cssEscape(s){ return String(s).replace(/([ !"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1'); }

    function unassignFromCell(td, { restoreButton = true } = {}) {
        if (!td) return;
        const chip = td.querySelector('.field-chip'); const originId = chip?.dataset?.originId || '';
        if (chip) chip.remove();

        td.innerHTML = '';
        const input = document.createElement('input');
        input.type = 'text'; 
        input.className = 'table-input'; 
        input.placeholder = L['placeholder_read']; td.appendChild(input);
        bindExclusive416xListeners(input); 

        if (restoreButton && originId) restoreFieldBankButton(originId);
        bumpDom(); poller?.triggerNow?.();

        // 若全空則禁用儲存
        updateSaveButtonState(); 
    }

    /* 數字與唯一性驗證（沿用） */
    const NUM_ONLY_SELECTOR = '.table-input[data-num-only]';
    function sanitizeDigits(s){ return (s || '').replace(/[^\d]/g, ''); }
    function markInvalid(el, bad){ if (!el) return; if (bad) el.classList.add('invalid'); else el.classList.remove('invalid'); }

    document.addEventListener('input', (e)=>{ if (e.target && e.target.matches(NUM_ONLY_SELECTOR)) {
        const v = e.target.value; const digits = sanitizeDigits(v); if (v !== digits) e.target.value = digits; }});
    document.addEventListener('paste', (e)=>{ if (e.target && e.target.matches(NUM_ONLY_SELECTOR)) {
        e.preventDefault(); const text = (e.clipboardData || window.clipboardData).getData('text');
        const digits = sanitizeDigits(text); document.execCommand('insertText', false, digits); }});

    function validateNumericRows(){
        let bad = 0;
        [...tbody.querySelectorAll('tr')].forEach(tr=>{
        const readEl  = tr.querySelector('td:nth-child(3) input');
        const inputEl = tr.querySelector('td:nth-child(4) input');
        const readVal  = readEl?.value?.trim()  ?? '';
        const inputVal = inputEl?.value?.trim() ?? '';
        const readOk  = (readVal  === '' || /^\d+$/.test(readVal));
        const inputOk = (inputVal === '' || /^\d+$/.test(inputVal));
        markInvalid(readEl,  !readOk);
        markInvalid(inputEl, !inputOk);
        if (!readOk || !inputOk) bad++;
        });
        return bad === 0;
    }

    function getColInputs(colIndex){ return [...tbody.querySelectorAll(`tr td:nth-child(${colIndex}) input.table-input[type="text"]`)]; }
    function validateUniqueForCol(colIndex){
        const inputs = getColInputs(colIndex);
        const seen = new Map(); let ok = true;
        inputs.forEach(el => el && el.classList.remove('invalid'));
        inputs.forEach(el=>{
        const v = (el?.value ?? '').trim(); if (!v) return;
        if (seen.has(v)) { ok = false; el.classList.add('invalid'); const first = seen.get(v); if (first) first.classList.add('invalid'); }
        else seen.set(v, el);
        });
        return ok;
    }
    function validateUniqueColumns(){ const okRead = validateUniqueForCol(3); const okInput = validateUniqueForCol(4); return okRead && okInput; }
    document.addEventListener('input', (e)=>{ if (e.target && e.target.matches('td:nth-child(3) input.table-input, td:nth-child(4) input.table-input')) { validateUniqueColumns(); } });

    // === BEGIN REPLACE: 4165/4166 mutex (single source of truth) ===

    // 對單一「讀取位置」輸入框綁定 4165/4166 互斥檢查
    function bindExclusive416xListeners(inputEl){
      if (!inputEl || inputEl.dataset.mxBound === '1') return;
      inputEl.dataset.mxBound = '1';

      const run = (evt) => _enforceExclusive416x(inputEl, evt);

      // 先於其他程式碼執行
      inputEl.addEventListener('beforeinput', run, true);     // ★ 這個最關鍵：可攔截
      inputEl.addEventListener('input',       run, true);
      inputEl.addEventListener('change',      run, true);
      inputEl.addEventListener('compositionend', run, true);

      // 輔助
      inputEl.addEventListener('keyup', run, true);
      inputEl.addEventListener('paste', (evt) => { requestAnimationFrame(()=>run(evt)); }, true);

      // 初始就有 4165/4166 → 立刻檢一次
      if (/^\s*#?\s*(4165|4166)\b/.test(String(inputEl.value||''))) {
        run();
        requestAnimationFrame(()=>run());
      }
    }



    // 取得所有「讀取位置」輸入框（第 3 欄）
    function _getReadInputs416x() {
       return [...(tbody || document).querySelectorAll('#dynTbody td.drop-target[data-drop="read"] input.table-input')];
    }

    // 掃描目前是否存在 4165 / 4166（可排除某個欄位）
    function _scan416x(exceptEl = null){
      const out = { has4165:false, has4166:false, els4165:[], els4166:[] };
      for (const el of _getReadInputs416x()) {
        if (exceptEl && el === exceptEl) continue;
        const raw = String(el.value || '').trim();
        const m = /^#?\s*(\d+)\b/.exec(raw);
        if (!m) continue;
        const digits = m[1];
        if (digits === '4165') { out.has4165 = true; out.els4165.push(el); }
        if (digits === '4166') { out.has4166 = true; out.els4166.push(el); }
      }
      return out;
    }

    // 輕量提示（避免 alert 洗版）
    function _softToast416x(msg){
      try {
        if (typeof alertify !== 'undefined' && alertify) {
          if (typeof alertify.alert === 'function') { alertify.alert(L['title_info'] || 'Info', msg); return; }
          if (typeof alertify.message === 'function') { alertify.message(msg); return; }
        }
      } catch (e) {}
      alert(msg); // 最終保險
    }

    // 標記紅框（保持到輸入變回不衝突）
    function flagInvalid(el){
      if (!el) return;
      el.classList.add('invalid');
      el.setAttribute('aria-invalid','true');
    }

    // 清空 + 標紅 + 觸發 input + 跳視窗（支援 beforeinput 當下攔截）
    function clearAndNotify(el, msg, evt){
      try {
        if (evt && evt.type === 'beforeinput') evt.preventDefault();
      } catch(e){}
      el.value = '';
      try { el.setSelectionRange(0,0); } catch(e){}
      el.dispatchEvent(new Event('input', { bubbles:true }));
      flagInvalid(el);
      _softToast416x(msg);
    }



    // 小工具：此手動值是否與任何已存在 chip（依 SPECIAL_MUTEX）互斥
    function __violatesChipMutex(digits){
      if (!digits) return false;
      const set = __MX_MANUAL_TO_DBIDXS && __MX_MANUAL_TO_DBIDXS.get(String(digits));
      if (!set || !set.size) return false;
      for (const dbi of set) {
        if (typeof chipExistsForIndex === 'function' && chipExistsForIndex(dbi)) {
          return true; // 該手動值對應的某 dbIndex 的 chip 已存在 → 互斥
        }
      }
      return false;
    }

    function _enforceExclusive416x(currentEl, evt){
      // 預測這次輸入後的值（insert 類型）
      const predicted = (() => {
        try{
          if (evt && evt.type === 'beforeinput' &&
              (evt.inputType === 'insertText' || evt.inputType === 'insertFromPaste')) {
            const data = (typeof evt.data === 'string') ? evt.data : '';
            const s = currentEl.selectionStart ?? currentEl.value.length;
            const e = currentEl.selectionEnd   ?? currentEl.value.length;
            return String(currentEl.value || '').slice(0, s) + data + String(currentEl.value || '').slice(e);
          }
        }catch(e){}
        return String(currentEl.value || '');
      })();

      const check = (val) => {
        const m = /^#?\s*(\d+)\b/.exec(String(val).trim());
        return m ? m[1] : '';
      };

      const nextDigits = check(predicted);
      const nowDigits  = check(currentEl.value);  // ★ 提前算好，後面就能用了

      // A. 預測值攔截（beforeinput 當下阻止）
      if (FORBID_MANUAL.has(nextDigits)) {
        clearAndNotify(currentEl, forbidMsg(nextDigits), evt);
        return;
      }
      // A-2) 若將成為某「互斥手動值」，且其對應欄位 chip 已存在 → 擋
      if (__violatesChipMutex(nextDigits)) {
        clearAndNotify(currentEl, (window.L && (L.in_use || L.forbidden_idx)) || '此欄位與手動值互斥，請移除其中之一。', evt);
        return;
      }

      // 若輸入即將變成 4165/4166 → 做互斥檢查
      if (nextDigits === '4165' || nextDigits === '4166') {
        // 規則 1：有 ID chip(0) 就不允許
        if (typeof chipExistsForIndex === 'function' && chipExistsForIndex('0')) {
          clearAndNotify(currentEl, (window.L && (L.in_use || L.forbidden_idx)) || '此值與欄位互斥，無法同時使用。', evt);
          requestAnimationFrame(() => _enforceExclusive416x(currentEl));
          return;
        }
        // 規則 2：4165 與 4166 彼此互斥
        const scan = _scan416x(currentEl);
        const clash = (nextDigits === '4165') ? scan.has4166 : scan.has4165;
        if (clash) {
          clearAndNotify(currentEl, (window.L && (L.in_use || L.forbidden_idx)) || '此值與其他手動值互斥，無法同時使用。', evt);
          return;
        }
      }

      // B. 已輸進去後再補救（非 beforeinput 或其他程式碼回填）
      if (FORBID_MANUAL.has(nowDigits)) {
        clearAndNotify(currentEl, forbidMsg(nowDigits));
        requestAnimationFrame(() => _enforceExclusive416x(currentEl));
        return;
      }
      // B-2) 已輸入後：若現在值與既有 chip 互斥 → 清空並提示
      if (__violatesChipMutex(nowDigits)) {
        clearAndNotify(currentEl, (window.L && (L.in_use || L.forbidden_idx)) || '此欄位與手動值互斥，請移除其中之一。');
        requestAnimationFrame(() => _enforceExclusive416x(currentEl));
        return;
      }

      if (nowDigits !== '4165' && nowDigits !== '4166') {
        currentEl.classList.remove('invalid');
        return;
      }

      // 已經變成 4165/4166 → 再做一次既有規則
      if (typeof chipExistsForIndex === 'function' && chipExistsForIndex('0')) {
        currentEl.value = '';
        try { currentEl.setSelectionRange(0,0); } catch(e) {}
        currentEl.dispatchEvent(new Event('input', { bubbles:true }));
        currentEl.classList.remove('invalid');
        _softToast416x((window.L && (L.in_use || L.forbidden_idx)) || '此值與欄位互斥，無法同時使用。');
        requestAnimationFrame(() => _enforceExclusive416x(currentEl));
        return;
      }
      const scan2 = _scan416x(currentEl);
      const clash2 = (nowDigits === '4165') ? scan2.has4166 : scan2.has4165;
      if (clash2) {
        clearAndNotify(currentEl, (window.L && (L.in_use || L.forbidden_idx)) || '此值與其他手動值互斥，無法同時使用。');
        requestAnimationFrame(() => _enforceExclusive416x(currentEl));
        return;
      }

      currentEl.classList.remove('invalid');
    }




  // === END REPLACE ===


    // ▼ 把這段保險絲貼在這裡（與上面同一個 IIFE 內）
    (function attachGlobal416xDelegate(){
      // 避免重複綁定（頁面重載/局部重繪）
      if (document.__mx416xDelegateBound) return;
      document.__mx416xDelegateBound = true;

      const handler = (e) => {
        const el = e.target;
        if (!el || el.tagName !== 'INPUT') return;
        // 只處理「讀取位置」（第 3 欄）
        if (!el.closest('#dynTbody td.drop-target[data-drop="read"]')) return;

        // 確保欄位有綁規則，並在「當下」執行一次
        bindExclusive416xListeners(el);
        _enforceExclusive416x(el);
      };

      // 用捕獲階段，先於其他監聽器執行
      document.addEventListener('input', handler, true);
      document.addEventListener('compositionend', handler, true);
      document.addEventListener('change', handler, true);
    })();





    /* 編號與上下移按鈕狀態 */
    function renumber(){
        if (!tbody) return;
        const rows = [...tbody.children].filter(tr => !tr.classList.contains('insert-marker'));
        rows.forEach((tr, idx) => { const noCell = tr.querySelector('.cell-no'); if (noCell) noCell.textContent = String(idx + 1); });
        updateRowMoveButtonsState();
    }
    function updateRowMoveButtonsState(){
        const rows = getDataRows();
        rows.forEach((tr, i) => {
        const up = tr.querySelector('.btn-row-up'); const down = tr.querySelector('.btn-row-down');
        if (up) up.disabled = (i === 0); if (down) down.disabled = (i === rows.length - 1);
        });
    }

    /* Admin 控制列加入上下移群組操作 */
    if (IS_ADMIN) {
        const controlBar = document.querySelector('.control-bar');
        if (controlBar && !document.getElementById('btnMoveUp')) {
        const btnUp = document.createElement('button');
        btnUp.id = 'btnMoveUp'; btnUp.className = 'w3-btn w3-round-large icon-btn'; btnUp.title = (L['move_up'] || 'Move Up'); btnUp.textContent = '▲';
        btnUp.addEventListener('click', () => moveSelectedRows(-1));

        const btnDown = document.createElement('button');
        btnDown.id = 'btnMoveDown'; btnDown.className = 'w3-btn w3-round-large icon-btn'; btnDown.title = (L['move_down'] || 'Move Down'); btnDown.textContent = '▼';
        btnDown.addEventListener('click', () => moveSelectedRows(+1));

        controlBar.insertBefore(btnUp, controlBar.firstChild);
        controlBar.insertBefore(btnDown, controlBar.firstChild.nextSibling);
        }
    }

    /* 結果下拉（保留，如需） */
    function makeResultSelect(val=''){
        const sel = document.createElement('select'); sel.className = 'table-select result-select';
        [['',L['sel_none']], ['OK',L['sel_ok']], ['NG',L['sel_ng']]].forEach(([v,lab])=>{
        const opt = document.createElement('option'); opt.value = v; opt.textContent = lab; if(v===val) opt.selected = true; sel.appendChild(opt);
        });
        sel.addEventListener('change', function(){ const td = this.closest('td'); td.classList.remove('result-ok','result-ng'); if(this.value==='OK') td.classList.add('result-ok'); if(this.value==='NG') td.classList.add('result-ng'); });
        return sel;
    }

    /* 在第 pos 個位置插入一列（1-based） */
    function insertRowAt(pos, readVal = '', inputVal = '', resultVal = '') {
        if (getRowCount() >= MAX_ROWS) { if (window.alertify) alertify.alert('Info', MSG_MAX_ROWS); else alert(MSG_MAX_ROWS); if (btnAdd) btnAdd.disabled = true; return null; }
        const tr = addRow(readVal, inputVal, resultVal); if (!tr) return null;

        const dataRows = tbody.querySelectorAll('tr:not(.insert-marker)');
        const rowCount = dataRows.length;

        pos = Math.max(1, Math.min(pos, rowCount + 1));
        const beforeNode = dataRows[pos - 1] || null;

        if (beforeNode) tbody.insertBefore(tr, beforeNode); else tbody.appendChild(tr);

        renumber(); syncCkAllState();
        if (btnAdd) btnAdd.disabled = getRowCount() >= MAX_ROWS;
        bumpDom?.(); poller?.triggerNow?.();
        return tr;
    }

    /* Poll payload：排除 insert-marker */
    function buildPollPayloadFromDOM() {
        const no = [], read_position = [], input_position = [], row_id = [];
        [...tbody.querySelectorAll('tr:not(.insert-marker)')].forEach((tr, i) => {
        no.push(i + 1); row_id.push(tr.dataset.rowId || '');
        const tdRead = tr.querySelector('td:nth-child(3)'); const chip = tdRead?.querySelector('.field-chip');
        if (chip) {
            const idx  = tdRead.querySelector('input[name="read_db_index"]')?.value || chip.dataset.fieldIndex || '';
            const name = tdRead.querySelector('input[name="read_db_name"]')?.value || (FIELD_NAME_BY_INDEX[idx] || '');
            read_position.push('#' + idx + (name ? ' ' + name : ''));
        } else {
            const val = tdRead?.querySelector('input.table-input[type="text"]')?.value?.trim() || '';
            read_position.push(val);
        }
        const ip = tr.querySelector('td:nth-child(4) input.table-input')?.value?.trim() || '';
        input_position.push(ip);
        });
        return { no, read_position, input_position, row_id };
    }

    /* 輪詢（加上可見度暫停） */
    function startResultPolling(opts = {}) {
        const url        = opts.url ?? '?url=Customize/get_api';
        const intervalMs = opts.intervalMs ?? 1000;
        const tbodyEl    = opts.tbodyEl ?? tbody;

        const getData = (typeof opts.data === 'function')
        ? opts.data
        : () => (opts.data ?? {
            no: (CSV_DATA && CSV_DATA.no) || [],
            read_position: (CSV_DATA && CSV_DATA.read_position) || [],
            input_position: (CSV_DATA && CSV_DATA.input_position) || []
            });

        const mapRowToElement = opts.mapRowToElement ?? ((row, tb) => {
        if (row.row_id) return tb?.querySelector(`tr[data-row-id="${row.row_id}"]`) || null;
        const idx = (row.no || 0) - 1; return tb?.querySelectorAll('tr')?.[idx] || null;
        });

        const onUpdate = opts.onUpdate ?? ((row, tr) => {
        const resInput = tr?.querySelector('.result-input'); if (resInput) resInput.value = row.result ?? '';
        });

        let timer = null, inFlight = false, aborted = false, controller = null;

        const tick = () => {
        if (aborted || inFlight) return;
        inFlight = true; controller = new AbortController();
        const reqVersion = DOM_VERSION;
        fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(getData()), signal: controller.signal })
            .then(r => r.ok ? r.json() : Promise.reject(r))
            .then(resp => {
            if (reqVersion !== DOM_VERSION) return;
            if (!resp || !Array.isArray(resp.rows)) return;
            resp.rows.forEach(row => { const tr = mapRowToElement(row, tbodyEl); if (tr) onUpdate(row, tr); });
            })
            .catch(()=>{})
            .finally(()=>{ inFlight = false; });
        };

        timer = setInterval(tick, intervalMs);
        return {
        stop(){ aborted = true; if (timer) clearInterval(timer); if (controller) controller.abort(); },
        triggerNow(){ tick(); }
        };
    }


  // ----- 儲存前的終局檢查（沿用你的 validateMutexRules 也可在這裡強化） -----
  function validateMutexRules(){

    // 禁止規則：凡在 FORBID_MANUAL 的值，一律不准存在
    for (const el of document.querySelectorAll('#dynTbody td.drop-target[data-drop="read"] input.table-input')) {
      const m = /^#?\s*(\d+)\b/.exec(String(el.value || '').trim());
      if (m && FORBID_MANUAL.has(m[1])) return false;
    }

        

    // 若有 ID chip，不得同時存在任一 4165/4166
    if (typeof chipExistsForIndex === 'function' && chipExistsForIndex('0')) {
      const s = _scan416x();
      if (s.has4165 || s.has4166) return false;
    }
    // 4165 與 4166 彼此互斥
    const s2 = _scan416x();
    if (s2.has4165 && s2.has4166) return false;

    return true;
  }
  // 若你原本已經有 validateMutexRules()，就用這個覆蓋原本版本即可。

    function addRow(readVal = '', inputVal = '', resultVal = '') {
        if (getRowCount() >= MAX_ROWS) { if (window.alertify) alertify.alert('Info', MSG_MAX_ROWS); else alert(MSG_MAX_ROWS); if (btnAdd) btnAdd.disabled = true; return null; }

        const tr = document.createElement('tr');
        const rowId = 'r' + (ROW_UID++); tr.dataset.rowId = rowId;

        // (1) 選取
        const tdSel = document.createElement('td'); tdSel.className = 'sel-ck';
        if (IS_ADMIN) { const ck = document.createElement('input'); ck.type='checkbox'; ck.className='row-ck'; tdSel.appendChild(ck); }
        tr.appendChild(tdSel);

        // (2) NO
        const tdNo = document.createElement('td'); tdNo.className = 'cell-no'; tr.appendChild(tdNo);

        // (3) 讀取位置（可投放）
        const tdRead = document.createElement('td'); tdRead.classList.add('drop-target'); tdRead.dataset.drop = 'read';
        const inRead = document.createElement('input'); inRead.type = 'text'; inRead.className = 'table-input'; inRead.placeholder = L['placeholder_read']; inRead.value = readVal; tdRead.appendChild(inRead);
        bindExclusive416xListeners(inRead);

        inRead.addEventListener('input', updateSaveButtonState);  
        tr.appendChild(tdRead);

        // (4) 輸入位置（隱藏欄）
        const tdInput = document.createElement('td');
        tdInput.classList.add('col-input');

        const inInput = document.createElement('input');
        inInput.type = 'text';
        inInput.className = 'table-input';
        inInput.placeholder = L['placeholder_input'];
        inInput.value = inputVal;

        tdInput.appendChild(inInput);

        //加上監聽，讓輸入欄位變動時更新儲存鈕狀態
        inInput.addEventListener('input', updateSaveButtonState);

        tr.appendChild(tdInput);

        // (5) 結果（唯讀）
        const tdRes = document.createElement('td');
        const inRes = document.createElement('input'); inRes.type='text'; inRes.className='table-input result-input'; inRes.value = resultVal || ''; inRes.readOnly = true; inRes.id = 'result-' + rowId;
        tdRes.appendChild(inRes); tr.appendChild(tdRes);

        // (6/7) 上/下
        if (IS_ADMIN) {
          const tdUp = document.createElement('td'); tdUp.className = 'move-cell';
          const bUp = document.createElement('button'); bUp.type='button'; bUp.className='w3-btn w3-round-large icon-btn btn-row-up'; bUp.title=(L['move_up']||'Move Up'); bUp.textContent='▲';
          bUp.addEventListener('click', () => moveRow(tr, -1)); tdUp.appendChild(bUp); tr.appendChild(tdUp);

          const tdDown = document.createElement('td'); tdDown.className = 'move-cell';
          const bDown = document.createElement('button'); bDown.type='button'; bDown.className='w3-btn w3-round-large icon-btn btn-row-down'; bDown.title=(L['move_down']||'Move Down'); bDown.textContent='▼';
          bDown.addEventListener('click', () => moveRow(tr, +1)); tdDown.appendChild(bDown); tr.appendChild(tdDown);
        }

        // (8) 單筆刪除
        if (IS_ADMIN) {
          const tdAct = document.createElement('td'); tdAct.className = 'row-actions';
          const del = document.createElement('button'); del.textContent = L['delete']; del.className = 'w3-btn w3-round-large';
          del.addEventListener('click', () => {
              tr.querySelectorAll('.field-chip').forEach(chip => { const originId = chip.dataset.originId; if (originId) restoreFieldBankButton(originId); });
              tr.remove(); renumber(); syncCkAllState?.(); if (btnAdd && getRowCount() < MAX_ROWS) btnAdd.disabled = false; bumpDom?.(); poller?.triggerNow?.();

              
              //刪完後更新儲存鈕狀態
              updateSaveButtonState();
          });
          tdAct.appendChild(del); tr.appendChild(tdAct);
        }

        tbody.appendChild(tr);
        renumber(); updateRowMoveButtonsState();
        if (btnAdd) btnAdd.disabled = getRowCount() >= MAX_ROWS;
        bumpDom(); poller?.triggerNow?.();
        return tr;
    }

    // 初始化
    if (CSV_DATA && ((CSV_DATA.no && CSV_DATA.no.length) || (CSV_DATA.read_position && CSV_DATA.read_position.length) || (CSV_DATA.input_position && CSV_DATA.input_position.length) || (CSV_DATA.result && CSV_DATA.result.length))) {
        hydrateFromCsv(CSV_DATA);
    } else {
        addRow();
    }

    updateSaveButtonState();

    // 啟動輪詢（+ 可見度暫停/恢復）
    const createPoller = () => startResultPolling({
        intervalMs: 1000,
        tbodyEl: tbody,
        data: () => {
        const p = buildPollPayloadFromDOM();
        if (!p.no.length && CSV_DATA) {
            return {
            no: (CSV_DATA.no || []),
            read_position: (CSV_DATA.read_position || []),
            input_position: (CSV_DATA.input_position || []),
            row_id: (CSV_DATA.row_id || [])
            };
        }
        return p;
        }
    });
    poller = createPoller(); poller.triggerNow();
    document.addEventListener('visibilitychange', ()=> {
        if (document.hidden) { poller?.stop(); }
        else { poller = createPoller(); poller.triggerNow(); }
    });
    window.addEventListener('beforeunload', () => poller.stop());

    // Admin 綁定
    if(IS_ADMIN){
        if(btnAdd) btnAdd.addEventListener('click', ()=> addRow());
        if(btnSave) btnSave.addEventListener('click', onSave);
    } else {
        [...tbody.querySelectorAll('input,select,button')].forEach(el=> el.disabled = true);
    }

    function collectData(){
        const rows = [];
        const trs = [...tbody.querySelectorAll('tr')];
        trs.forEach((tr, i) => {
        const rowId   = tr.dataset.rowId || ('r' + (i + 1));
        const tdRead  = tr.querySelector('td:nth-child(3)');
        const chip    = tdRead?.querySelector('.field-chip');
        let readSrc='', readIdx='', readName='', readPos='';

        if (chip) {
            readSrc  = tdRead?.querySelector('input[name="read_src"]')?.value?.trim() || 'db';
            readIdx  = tdRead?.querySelector('input[name="read_db_index"]')?.value?.trim() || chip.dataset.fieldIndex || '';
            readName = tdRead?.querySelector('input[name="read_db_name"]')?.value?.trim() || '';
        } else {
            readPos = tdRead?.querySelector('input.table-input[type="text"]')?.value?.trim() ?? '';
        }

        const tdInput = tr.querySelector('td:nth-child(4)');
        const inputPos = tdInput?.querySelector('input.table-input[type="text"]')?.value?.trim() ?? '';
        const result = tr.querySelector('.result-input')?.value ?? '';

        if (readPos || inputPos || result || readSrc || readIdx || readName) {
            rows.push({ row_id: rowId, read_pos: readPos, input_pos: inputPos, result, read_src: readSrc, read_db_index: readIdx, read_db_name: readName });
        }
        });
        return rows;
    }

    function syncCkAllState(){
        if (!IS_ADMIN || !ckAll) return;
        const cks = [...tbody.querySelectorAll('.row-ck')];
        const total = cks.length;
        const chosen = cks.filter(c => c.checked).length;
        ckAll.indeterminate = (chosen > 0 && chosen < total);
        ckAll.checked = (total > 0 && chosen === total);
    }

    if (IS_ADMIN && ckAll) {
        ckAll.addEventListener('change', ()=>{ const rowCks = tbody.querySelectorAll('.row-ck'); rowCks.forEach(ck => ck.checked = ckAll.checked); ckAll.indeterminate = false; });
        tbody.addEventListener('change', (e)=>{ if (e.target && e.target.classList.contains('row-ck')) syncCkAllState(); });
    }

    function deleteSelectedRows(){
        const rows = [...tbody.querySelectorAll('.row-ck:checked')].map(ck => ck.closest('tr')).filter(Boolean);
        if (rows.length === 0) { if (window.alertify) alertify.alert('Info', L['none_selected']); else alert(L['none_selected']); return; }

        const doRemove = () => {
        rows.forEach(tr => {
            tr.querySelectorAll('.field-chip').forEach(chip => { const originId = chip.dataset.originId; if (originId) restoreFieldBankButton(originId); });
            tr.remove();
        });
        renumber(); syncCkAllState();
        if (ckAll) { ckAll.checked = false; ckAll.indeterminate = false; }
        if (btnAdd) btnAdd.disabled = getRowCount() >= MAX_ROWS;
            bumpDom(); poller?.triggerNow();

              //刪完後更新儲存鈕狀態
              updateSaveButtonState();
        };

        if (window.alertify) alertify.confirm(L['delete_sel'], L['confirm_delete'], doRemove, function(){});
        else if (confirm(L['confirm_delete'])) doRemove();
    }

    function deleteAllRows(){
        const doRemoveAll = () => {
        tbody.querySelectorAll('.field-chip').forEach(chip => { const originId = chip.dataset.originId; if (originId) restoreFieldBankButton(originId); });
        tbody.innerHTML = '';
        renumber(); syncCkAllState();
        if (ckAll) { ckAll.checked = false; ckAll.indeterminate = false; }
        if (btnAdd) btnAdd.disabled = false;
        bumpDom(); poller?.triggerNow();

        updateSaveButtonState(); // 新增

        };

        if (window.alertify) alertify.confirm(L['delete_all'], L['confirm_delete_all'], doRemoveAll, function(){});
        else if (confirm(L['confirm_delete_all'])) doRemoveAll();
    }

    if(IS_ADMIN){
        if(btnDelSel) btnDelSel.addEventListener('click', deleteSelectedRows);
        if(btnDelAll) btnDelAll.addEventListener('click', deleteAllRows);
    }

    // 插入位置輔助（保留）
    function rowHasData(tr){
        const tdRead  = tr.querySelector('td:nth-child(3)');
        const tdInput = tr.querySelector('td:nth-child(4)');
        const hasChip = !!tdRead?.querySelector('.field-chip');
        const readVal = tdRead?.querySelector('input.table-input[type="text"]')?.value?.trim() || '';
        const inputVal= tdInput?.querySelector('input.table-input[type="text"]')?.value?.trim() || '';
        return hasChip || !!readVal || !!inputVal;
    }
    function getFilledRowIndexes(){
        const trs = [...tbody.querySelectorAll('tr')]; const idxs = [];
        for (let i = 0; i < trs.length; i++){ if (rowHasData(trs[i])) idxs.push(i); }
        return idxs;
    }
    function getPosBetweenFilledPair(n){
        const idxs = getFilledRowIndexes();  const trsLen = tbody.querySelectorAll('tr').length;
        if (n >= 1 && idxs.length >= n + 1){ const secondIdx = idxs[n]; return secondIdx + 1; }
        if (idxs.length > 0){ return Math.min(idxs[idxs.length - 1] + 2, trsLen + 1); }
        return Math.min(2, trsLen + 1);
    }
    function insertBetweenFilled(n){ insertRowAt( getPosBetweenFilledPair(n) ); }
    function insertBetweenNos(no){ insertRowAt(no + 1); }

    async function onSave(){
        const hasData = anyDataExists();

        // 空白儲存處理
        if (!hasData) {
          if (!ALLOW_EMPTY_SAVE) {
            const msg = L['nothing_to_save'] || 'Nothing to save. Please add at least one row.';
            if (window.alertify) alertify.alert(L['title_info'] || 'Info', msg); else alert(msg);
            return;
          }
          // 允許空白儲存 → 詢問是否清空伺服端資料
          const confirmMsg = L['confirm_save_empty'] || 'No data found. Save empty (clear server data)?';
          const ok = (window.alertify)
            ? await new Promise(res => alertify.confirm(L['title_confirm'] || 'Confirm', confirmMsg, () => res(true), () => res(false)))
            : confirm(confirmMsg);
          if (!ok) return;
        }

        // 一律蒐集（空白時 rows 會是 []），並帶 clear 旗標（後端可用）
        const payload = { rows: collectData(), clear: hasData ? 0 : 1 };

        // ★ 相容舊版後端（有的後端會檢查這三個欄位長度）
        if (!hasData) {
          payload.no = [];
          payload.read_position = [];
          payload.input_position = [];
        }


        // 有資料時才需要做驗證（空白清空可略過）
        if (hasData) {
          if (!validateNumericRows()) {
            if (window.alertify) alertify.alert('Error', L['num_only']); else alert(L['num_only']);
            return;
          }
          if (!validateUniqueColumns()) {
            if (window.alertify) alertify.alert('Error', L['no_dup']); else alert(L['no_dup']);
            return;
          }
          if (!validateMutexRules()){
            const msg = (L && (L['in_use'] || L['forbidden_idx'])) || '欄位與手動值互斥，請移除其中之一。';
            if (window.alertify) alertify.alert(L['title_error'] || 'Error', msg); else alert(msg);
            return;
          }
        }

        const spinner = document.getElementById('spinner');
        if (spinner) spinner.style.display = 'block';

        $.ajax({
          url: SAVE_URL,
          type:'POST',
          data: JSON.stringify(payload),
          contentType:'application/json; charset=UTF-8',
          success: function(resp){
            if (spinner) spinner.style.display = 'none';

            // 回填 result（跟原本一樣）
            if (resp && Array.isArray(resp.rows)) {
              resp.rows.forEach(r => {
                const id = r.result_id || ('result-' + r.row_id);
                const el = document.getElementById(id);
                if (el) el.value = (r.result ?? '');
              });
            }

            //若是「空設定」的儲存，清掉前端 fallback，避免輪詢再吃舊 CSV_DATA
            if (!hasData) {
                CSV_DATA = null; // 這裡需要第 1 步把 const 改成 let
                try { localStorage.removeItem('customize.csv.cache'); } catch(e) {}
                if (poller) {
                  poller.stop();
                  poller = createPoller();  // 重新啟動，這次不會帶 CSV_DATA
                  poller.triggerNow();
                }
            }


            // 依是否為清空顯示不同訊息（若沒加多語系可用預設英字）
            const msg = (!hasData)
              ? (L['saved_empty'] || 'Saved (empty configuration).')
              : (L['saved'] || 'Saved successfully');
            if (window.alertify) alertify.alert(L['title_ok'] || 'OK', msg);
          },
          error: function(xhr){
            if (spinner) spinner.style.display = 'none';
            if (window.alertify) alertify.alert('Error', L['save_fail']); else alert(L['save_fail']);
            console.error('Save error:', xhr?.responseText || xhr);
          }
        });
      }



    })(); 
</script>


<script>
    /* ===== Tabs 初始化（ARIA + ink bar + 鍵盤操作） ===== */
    (function tabsInit(){
    const KEY  = 'customize.activeTab';
    const root = document.getElementById('customizeTabs');
    if(!root) return;

    const nav   = root.querySelector('.tabs-nav');
    const btns  = [...root.querySelectorAll('.tab-btn')];
    const panes = [...root.querySelectorAll('.tab-pane')];

    // ARIA
    btns.forEach((b,i)=>{
        const id = b.id || ('tab-btn-' + i);
        const tgt = (b.dataset.tabTarget || '').replace('#','');
        b.id = id;
        b.setAttribute('role','tab');
        b.setAttribute('aria-controls', tgt);
    });
    panes.forEach((p,i)=>{
        p.setAttribute('role','tabpanel');
        const owner = btns.find(b => b.dataset.tabTarget === '#'+p.id);
        if (owner) p.setAttribute('aria-labelledby', owner.id);
        p.setAttribute('tabindex','0');
    });

    // Ink bar
    let ink = nav.querySelector('.tab-indicator');
    if(!ink){ ink = document.createElement('div'); ink.className = 'tab-indicator'; nav.appendChild(ink); }

    function moveInkTo(btn){
        if(!btn) return;
        const br = btn.getBoundingClientRect();
        const nr = nav.getBoundingClientRect();
        ink.style.width = br.width + 'px';
        ink.style.transform = `translateX(${br.left - nr.left}px)`;
    }

    function activate(id){
        btns.forEach(b => {
        const on = b.dataset.tabTarget === id;
        b.classList.toggle('active', on);
        b.setAttribute('aria-selected', on ? 'true' : 'false');
        b.setAttribute('tabindex', on ? '0' : '-1');
        });
        panes.forEach(p => p.classList.toggle('active', '#'+p.id === id));
        moveInkTo(btns.find(b=>b.dataset.tabTarget===id) || btns[0]);
        try{ localStorage.setItem(KEY, id); }catch(e){}
    }

    // 漣漪 & 點擊
    btns.forEach(b => {
        b.addEventListener('pointerdown', (e)=>{
        const r = b.getBoundingClientRect();
        b.style.setProperty('--x', (e.clientX - r.left) + 'px');
        b.style.setProperty('--y', (e.clientY - r.top) + 'px');
        });
        b.addEventListener('click', ()=> activate(b.dataset.tabTarget));
        b.addEventListener('keydown', (e)=>{
        const idx = btns.indexOf(b);
        if (e.key === 'ArrowRight') { (btns[idx+1] || btns[0]).focus(); (btns[idx+1] || btns[0]).click(); }
        if (e.key === 'ArrowLeft')  { (btns[idx-1] || btns.at(-1)).focus(); (btns[idx-1] || btns.at(-1)).click(); }
        if (e.key === 'Home') { btns[0].focus(); btns[0].click(); }
        if (e.key === 'End')  { btns.at(-1).focus(); btns.at(-1).click(); }
        });
    });

    // 初始化
    let initId = null;
    try{ initId = localStorage.getItem(KEY); }catch(e){}
    if(!initId || !root.querySelector(initId)) initId = (btns[0] && btns[0].dataset.tabTarget) || '#tab-4-1';
    activate(initId);

    // ink 位置隨尺寸變化
    const ro = new ResizeObserver(()=> {
        const activeBtn = root.querySelector('.tab-btn.active') || btns[0];
        moveInkTo(activeBtn);
    });
    ro.observe(nav);

    // 欄位銀行拖曳動效
    const fieldList = document.getElementById('fieldList');
    if(fieldList){
        fieldList.addEventListener('dragstart', e=>{ const btn = e.target.closest('.field-btn'); if(btn) btn.classList.add('dragging'); });
        fieldList.addEventListener('dragend',   e=>{ const btn = e.target.closest('.field-btn'); if(btn) btn.classList.remove('dragging'); });
    }
    })();
</script>

<script>
  // 開/關抽屜；force 可傳 true/false 強制狀態
  function toggleTableDrawer(force){
    const root = document.getElementById('tableDrawer');
    if(!root) return;
    const willOpen = (typeof force === 'boolean') ? force : !root.classList.contains('open');
    root.classList.toggle('open', willOpen);
    root.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    try { localStorage.setItem('drawer.tableData.open', willOpen ? '1' : '0'); } catch(e){}
  }

  // 供「關閉」按鈕使用（保持你原本的命名）
  function showTableInputSetting(){ toggleTableDrawer(false); }

  // 邊界：Esc 關閉
  document.addEventListener('keydown', (e)=>{
    if(e.key === 'Escape'){ toggleTableDrawer(false); }
  });

  // 初始狀態（預設關閉；若要記憶上次狀態可以打開下面兩行）
  (function initDrawer(){
    try{
      const saved = localStorage.getItem('drawer.tableData.open');
      if(saved === '1') toggleTableDrawer(true);
    }catch(e){}
  })();
</script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    const btnSave = document.getElementById('btnSave');
    if (btnSave) btnSave.classList.remove('w3-opacity', 'w3-disabled');
  });
</script>


<script>
/** ===== 可見列數控制（100 列）＋ 避免被底部抽屜遮擋 ===== */
(function visibleRowsLimiter(){
  // 你要的最多顯示列數
  const MAX_VISIBLE_ROWS = 100;

  const scroller = document.getElementById('tableScroller');
  const table    = document.getElementById('dynTable');
  if (!scroller || !table) return;

  // 量測列高與表頭高 → 設定可捲動容器 max-height
  function fitTableVisibleRows(maxRows = MAX_VISIBLE_ROWS) {
    try {
      const thead = table.querySelector('thead');
      const tbody = table.querySelector('tbody');
      const firstRow = tbody?.querySelector('tr:not(.insert-marker)');
      const headH = thead ? thead.getBoundingClientRect().height : 40;
      const rowH  = firstRow ? firstRow.getBoundingClientRect().height : 40;

      // 底部抽屜把手高度 + 安全間距，避免最後一列被遮
      const drawerHandle = document.querySelector('#tableDrawer .handle');
      const reserve = (drawerHandle ? drawerHandle.getBoundingClientRect().height : 36) + 24;

      // 期望高度：表頭 + N 列
      const want = headH + (rowH * maxRows);

      // 可用高度：視窗到底部抽屜上緣之間的空間
      const scTop = scroller.getBoundingClientRect().top;
      const canUse = Math.max(180, window.innerHeight - scTop - reserve);

      const maxH = Math.min(want, canUse);
      scroller.style.setProperty('--table-max-h', `${Math.round(maxH)}px`);
    } catch(e) {}
  }

  // 讓指定列保持在可視範圍
  function ensureRowVisible(tr) {
    if (!tr) return;
    try { tr.scrollIntoView({ block: 'nearest' }); } catch(e) {}
  }

  // 視窗變化 / 容器尺寸變化 → 重算
  window.addEventListener('resize', () => fitTableVisibleRows());
  const ro = new ResizeObserver(() => fitTableVisibleRows());
  ro.observe(scroller);

  // 抽屜開關（#tableDrawer .open） → 重算
  const drawer = document.getElementById('tableDrawer');
  if (drawer) {
    const mo = new MutationObserver(() => fitTableVisibleRows());
    mo.observe(drawer, { attributes: true, attributeFilter: ['class', 'style', 'aria-expanded'] });
  }

  // tbody 有增刪列 → 重算高度，並確保新增列可見
  const tbodyEl = table.querySelector('tbody');
  if (tbodyEl) {
    const mo2 = new MutationObserver((muts) => {
      fitTableVisibleRows();
      const lastAdd = muts.find(m => m.addedNodes && m.addedNodes.length);
      if (lastAdd) {
        const tr = [...lastAdd.addedNodes].find(n => n.nodeType===1 && n.tagName==='TR' && !n.classList.contains('insert-marker'));
        if (tr) ensureRowVisible(tr);
      }
    });
    mo2.observe(tbodyEl, { childList: true, subtree: false });
  }

  // 初始執行
  document.addEventListener('DOMContentLoaded', () => fitTableVisibleRows());
  setTimeout(fitTableVisibleRows, 0);

  // —— 曝光 helper 給外面（可選）——
  window.__ensureRowVisible = ensureRowVisible;
  window.__fitTableVisibleRows = fitTableVisibleRows;
})();
</script>

