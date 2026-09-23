(function () {
  'use strict';
  const body = document.getElementById('jsonBody');
  const sections = document.getElementById('formSections');
  const errorBox = document.getElementById('formError');
  const upload = document.getElementById('jsonFile');
  const T = window.IDAS_JOB_CONFIG_UI.translations;
  const language = window.IDAS_JOB_CONFIG_UI.lang || 'en-us';
  const messages = {
    'en': {add:'Add',remove:'Remove',required:'At least one JOB, SEQ and STEP is required',reference:'SEQ must belong to a JOB; STEP must belong to a SEQ',duplicate:'Duplicate IDs are not allowed',invalid:'Please check the field',active:'Only one active JOB is allowed',loaded:'Form loaded',jobLimit:'At most 100 JOBs are allowed',seqLimit:'At most 50 SEQs per JOB are allowed',stepLimit:'At most 5 STEPs per SEQ are allowed',validator:'NTCS STEP validator is unavailable',tool:'Cannot read tool capabilities for controller',rpm:'RPM is outside the tool range',torque:'Target torque is outside the tool range',torqueWindow:'Torque limits must surround target torque and stay within tool maximum × 1.10',angle:'Angle must be within 1–30600 and between its limits',angleWindow:'Angle limits must satisfy 0 ≤ Low < High ≤ 30600',threshold:'Threshold torque must be below target or high torque',downshift:'Downshift torque must be below target torque',offset:'Torque offset is outside the allowed range',target:'Select and check a controller first'},
    'zh-tw': {add:'新增',remove:'刪除',required:'JOB、SEQ、STEP 各至少需要一筆',reference:'SEQ 必須對應 JOB；STEP 必須對應 SEQ',duplicate:'編號不可重複',invalid:'請檢查欄位',active:'只能有一個啟用的 JOB',loaded:'已載入表單',jobLimit:'JOB 上限為 100 筆',seqLimit:'每個 JOB 的 SEQ 上限為 50 筆',stepLimit:'每個 SEQ 的 STEP 上限為 5 筆',validator:'無法載入 NTCS STEP 驗證',tool:'無法取得控制器的工具範圍',rpm:'轉速超過工具允許範圍',torque:'目標扭力超過工具允許範圍',torqueWindow:'扭力上下限須涵蓋目標，且上限不可超過工具最大扭力 × 1.10',angle:'目標角度須介於 1～30600，且落在上下限之間',angleWindow:'角度上下限須符合 0 ≤ Low < High ≤ 30600',threshold:'扭力門檻須低於目標或扭力上限',downshift:'降速扭力須低於目標扭力',offset:'扭力補償超出允許範圍',target:'請先選擇並檢查控制器'},
    'zh-cn': {add:'新增',remove:'删除',required:'JOB、SEQ、STEP 各至少需要一笔',reference:'SEQ 必须对应 JOB；STEP 必须对应 SEQ',duplicate:'编号不可重复',invalid:'请检查字段',active:'只能有一个启用的 JOB',loaded:'表单已加载',jobLimit:'JOB 上限为 100 笔',seqLimit:'每个 JOB 的 SEQ 上限为 50 笔',stepLimit:'每个 SEQ 的 STEP 上限为 5 笔',validator:'无法加载 NTCS STEP 验证',tool:'无法取得控制器的工具范围',rpm:'转速超出工具允许范围',torque:'目标扭力超出工具允许范围',torqueWindow:'扭力上下限须包含目标，且上限不能超过工具最大扭力 × 1.10',angle:'目标角度须介于 1～30600，且位于上下限之间',angleWindow:'角度上下限须符合 0 ≤ Low < High ≤ 30600',threshold:'扭力门槛须低于目标或扭力上限',downshift:'降速扭力须低于目标扭力',offset:'扭力补偿超出允许范围',target:'请先选择并检查控制器'}
  };
  const labels = messages[language] || messages.en;
  // Keep the stored API keys intact; translate only form captions.
  const captionMap = {
    JOBID:['Job ID','Job ID','Job ID'], JOBname:['Job Name','工作名稱','工作名称'],
    SEQID:['Seq ID','Seq ID','Seq ID'], SEQname:['Seq Name','工序名稱','工序名称'],
    StepSelect:['Step ID','Step ID','Step ID'], STEPname:['Step name','步驟名稱','步骤名称'],
    type:['Type','類型','类型'], act:['Active','啟用','启用'], ok_job:['Ok Job','工作完成','工作完成'],
    ok_job_stop:['Ok Job Stop','工作完成停止','工作完成停止'], output_unified:['Unified outputs','輸出統一','输出统一'],
    input_unified:['Unified inputs','輸入統一','输入统一'], job_unit:['Job torque unit','工作扭力單位','工作扭力单位'],
    skip:['Skip','略過','跳过'], seq_repeat:['TR','TR','TR'], timeout:['Timeout (Sec)','逾時時間（秒）','超时时间（秒）'],
    ok_seq:['OK-Sequence','工序完成','工序完成'], ok_stop:['OK Seq Stop','工序完成停止','工序完成停止'],
    countType:['Count type','計數類型','计数类型'], ok_screw:['OK screw count','合格螺絲數','合格螺丝数'],
    ng_stop:['NG Stop (0-9)','NG 停止（0-9）','NG 停止（0-9）'], ng_unscrew:['NG Reverse','NG 反轉','NG 反转'],
    interrupt_alarm:['Interrupt alarm','中斷警報','中断警报'], accu_angle:['Accumulate Angle','累計角度','累计角度'],
    Thread_Calcu:['Angle Calculation (Step)','角度計算（步驟）','角度计算（步骤）'], unscrew_mode:['Reverse Mode','反轉模式','反转模式'],
    unscrew_force:['Force (%)','力道（%）','力度（%）'], unscrew_rpm:['Reverse RPM (rpm)','反轉轉速（rpm）','反转转速（rpm）'],
    unscrew_dir:['Direction','方向','方向'], image:['Image','圖片','图片'],
    message:['Message','訊息','信息'], delay:['Delay','延遲','延迟'], input:['Input pin','輸入接點','输入引脚'],
    input_signal:['Input signal','輸入訊號','输入信号'], output:['Output pin','輸出接點','输出引脚'],
    output_signal:['Output signal','輸出訊號','输出信号'], output_durat:['Output duration','輸出持續時間','输出持续时间'],
    addtion:['Additional setting','附加設定','附加设置'], unscrew_count_switch:['Reverse Count','反轉計數','反转计数'],
    unscrew_torque_threshold:['Torque Threshold','扭力門檻','扭力门槛'], seq_unit:['Sequence torque unit','工序扭力單位','工序扭力单位'],
    unscrew_angle_threshold:['Threshold Angle','門檻角度','门槛角度'],
    dt_time:['DT Time (Sec)','DT 時間（秒）','DT 时间（秒）'], tt_time:['TT Time (Sec)','TT 時間（秒）','TT 时间（秒）'],
    total_angle_limit:['Total High Angle','總角度上限','总角度上限'], total_angle_lower:['Total Low Angle','總角度下限','总角度下限'],
    StepSwitch:['Step enabled','啟用步驟','启用步骤'], StepRPM:['Run Down Speed','鎖附轉速','锁附转速'],
    StepOption:['Target Type','目標類型','目标类型'], StepTime:['Target time','目標時間','目标时间'],
    StepAngle:['Target Angle','目標角度','目标角度'], StepTorque:['Target Torque','目標扭力','目标扭力'],
    StepDirection:['Direction','方向','方向'], StepDelay:['Delay Time (Sec)','延遲時間（秒）','延迟时间（秒）'],
    StepMoniByWin:['Monitoring by window','視窗監控','窗口监控'], StepLimiHi:['Upper(%)','上限（%）','上限（%）'],
    StepLimiLo:['Lower(%)','下限（%）','下限（%）'], StepHiAngle:['High Angle (°)','角度上限（°）','角度上限（°）'],
    StepLoAngle:['Low Angle (°)','角度下限（°）','角度下限（°）'], StepHiTorque:['High Torque','扭力上限','扭力上限'],
    StepLoTorque:['Low Torque','扭力下限','扭力下限'], StepAccelerateOffset:['Acceleration offset','加速補償','加速补偿'],
    StepAccelerateOffsetSign:['Acceleration sign','加速補償方向','加速补偿方向'],
    StepEnableTorqueOffset:['Enable torque offset','啟用扭力補償','启用扭力补偿'],
    StepTorqueOffset:['Joint Offset','接頭補償','接头补偿'], StepTorqueOffsetSign:['Joint Offset Direction','接頭補償方向','接头补偿方向'],
    StepEnableDownShift:['Downshift','降速','降速'],
    StepTorqueDownShift:['Downshift Point','降速點','降速点'], StepRPMDownShift:['Downshift RPM','降速轉速','降速转速'],
    StepEnableThreshold:['Threshold Type','門檻類型','门槛类型'], StepTorqueTS:['Threshold Point','門檻點','门槛点'],
    StepReTry:['Retry count','重試次數','重试次数'], StepUnScrew:['Unscrew','反轉','反转'],
    StepReTryTorq:['Retry torque','重試扭力','重试扭力'], StepReTryAngl:['Retry angle','重試角度','重试角度'],
    StepAngleRecord:['Record angle','記錄角度','记录角度'], StepAutoDetectAngle:['Auto detect angle','自動偵測角度','自动检测角度'],
    InterruptAlarm:['Interrupt Alarm','中斷警報','中断警报'], OverAngleStop:['Over Angle Stop','超角度停止','超角度停止'],
    KValue:['K value','K 值','K 值'], step_unit:['Step torque unit','步驟扭力單位','步骤扭力单位']
  };
  const captionIndex = language === 'zh-tw' ? 1 : language === 'zh-cn' ? 2 : 0;
  const units = {0:'kgf.cm',1:'N.m',2:'Lbf.in',3:'kgf.m',4:'cN.m'};
  function caption(key, row) {
    let name = captionMap[key] ? captionMap[key][captionIndex] : key;
    if (row && key === 'StepMoniByWin') {
      name = language === 'zh-tw' ? (Number(row.StepOption) === 1 ? '依視窗監控角度' : '依視窗監控扭力') : language === 'zh-cn' ? (Number(row.StepOption) === 1 ? '按窗口监控角度' : '按窗口监控扭力') : 'Monitoring ' + (Number(row.StepOption) === 1 ? 'angle' : 'torque') + ' by window';
    }
    const torqueFields = ['StepTorque','StepHiTorque','StepLoTorque','StepTorqueOffset'];
    if (row && torqueFields.includes(key)) name += ' (' + (units[row.step_unit] || 'N.m') + ')';
    if (row && key === 'unscrew_torque_threshold') name += ' (' + (units[row.seq_unit] || 'N.m') + ')';
    if (row && key === 'StepTorqueTS' && Number(row.StepEnableThreshold) !== 0) {
      name = Number(row.StepEnableThreshold) === 1
        ? (language === 'zh-tw' ? '門檻角度 (°)' : language === 'zh-cn' ? '门槛角度 (°)' : 'Threshold Angle (°)')
        : (language === 'zh-tw' ? '扭力門檻' : language === 'zh-cn' ? '扭力门槛' : 'Torque Threshold') + ' (' + (units[row.step_unit] || 'N.m') + ')';
    }
    if (row && key === 'StepTorqueDownShift' && Number(row.StepEnableDownShift) !== 0) {
      name = Number(row.StepEnableDownShift) === 1
        ? (language === 'zh-tw' ? '降速角度 (°)' : language === 'zh-cn' ? '降速角度 (°)' : 'Downshift Angle (°)')
        : (language === 'zh-tw' ? '降速扭力' : language === 'zh-cn' ? '降速扭力' : 'Downshift Torque') + ' (' + (units[row.step_unit] || 'N.m') + ')';
    }
    return name;
  }
  const choices = {
    off: language === 'zh-tw' ? '關閉' : language === 'zh-cn' ? '关闭' : 'Off',
    on: language === 'zh-tw' ? '開啟' : language === 'zh-cn' ? '开启' : 'On',
    time: language === 'zh-tw' ? '時間' : language === 'zh-cn' ? '时间' : 'Time',
    angle: language === 'zh-tw' ? '角度' : language === 'zh-cn' ? '角度' : 'Angle',
    torque: language === 'zh-tw' ? '扭力' : language === 'zh-cn' ? '扭力' : 'Torque',
    cw:'CW', ccw:'CCW', auto: language === 'zh-tw' ? '自動' : language === 'zh-cn' ? '自动' : 'Auto',
    custom: language === 'zh-tw' ? '自訂' : language === 'zh-cn' ? '自定义' : 'Custom',
    unlimited: language === 'zh-tw' ? '無限制' : language === 'zh-cn' ? '无限制' : 'Unlimited',
    plus: language === 'zh-tw' ? '正' : language === 'zh-cn' ? '正' : 'Plus',
    minus: language === 'zh-tw' ? '負' : language === 'zh-cn' ? '负' : 'Minus'
  };
  const tables = ['JOB_lst', 'SEQ_lst', 'STEP_lst'];
  const titles = {JOB_lst:'JOB', SEQ_lst:'SEQ', STEP_lst:'STEP'};
  const idFields = {JOB_lst:['JOBID'], SEQ_lst:['JOBID','SEQID'], STEP_lst:['JOBID','SEQID','StepSelect']};
  const maximum = {JOBID:100, SEQID:50, StepSelect:5};
  // Same fixed ranges as the NTCS SEQ editor's input_check_seq().
  const fixedRanges = {
    JOB_lst:{JOBID:[1,100]},
    SEQ_lst:{SEQID:[1,50],seq_repeat:[1,99],timeout:[0,60],dt_time:[0,99],tt_time:[0,6000],ng_stop:[0,9],unscrew_angle_threshold:[0,30600],total_angle_limit:[0,30600],total_angle_lower:[0,30600]},
    STEP_lst:{StepSelect:[1,5],StepDelay:[0,9.9],StepAngle:[1,30600],StepHiAngle:[1,30600],StepLoAngle:[0,30600],StepLimiHi:[0,99],StepLimiLo:[0,99]}
  };
  function rangeMessage(table,key) {
    const range = fixedRanges[table][key];
    if (language === 'zh-tw') return '允許範圍：' + range[0] + '～' + range[1];
    if (language === 'zh-cn') return '允许范围：' + range[0] + '～' + range[1];
    return 'Allowed range: ' + range[0] + '–' + range[1];
  }
  let data;
  let activeControllerUnit = null;
  let selectedTool = null;
  const toNm = [0.0980665,1,0.112984829333,9.80665,0.01];
  const fromNm = [10.1971621298,1,8.85074576738,0.1019716213,100];
  const unitPrecision = [2,3,2,4,1];
  function convertedTorque(value, source, target) {
    if (value == null || value === '' || !Number.isFinite(Number(value))) return value;
    const factor = Math.pow(10,unitPrecision[target]);
    return Math.round((Number(value) * toNm[source] * fromNm[target] + 1 / (factor * 1000)) * factor) / factor;
  }
  function applyControllerUnit(unit) {
    if (!Object.prototype.hasOwnProperty.call(units,String(unit))) throw new Error(labels.tool);
    let changed = false;
    data.SEQ_lst.forEach(function (row) {
      const old = Number(row.seq_unit);
      if (!Object.prototype.hasOwnProperty.call(units,String(row.seq_unit)) || old === unit) return;
      row.unscrew_torque_threshold = convertedTorque(row.unscrew_torque_threshold,old,unit);
      row.seq_unit = unit;changed = true;
    });
    data.STEP_lst.forEach(function (row) {
      const old = Number(row.step_unit);
      if (!Object.prototype.hasOwnProperty.call(units,String(row.step_unit)) || old === unit) return;
      ['StepTorque','StepHiTorque','StepLoTorque','StepTorqueOffset'].forEach(function (key) {row[key] = convertedTorque(row[key],old,unit);});
      if (Number(row.StepEnableThreshold) === 2) row.StepTorqueTS = convertedTorque(row.StepTorqueTS,old,unit);
      if (Number(row.StepEnableDownShift) === 2) row.StepTorqueDownShift = convertedTorque(row.StepTorqueDownShift,old,unit);
      row.step_unit = unit;changed = true;
    });
    data.JOB_lst.forEach(function (row) {if (Number(row.job_unit) !== unit) {row.job_unit = unit;changed = true;}});
    if (activeControllerUnit !== unit || changed) {activeControllerUnit = unit;updateBody();render();}
  }
  const initialSample = JSON.parse(body.value);
  const draftKey = 'idas.replace_job_config.form.v1';
  const seqFrame = document.createElement('iframe');
  seqFrame.id = 'ntcsSeqValidator';
  seqFrame.title = 'NTCS SEQ validation';
  seqFrame.tabIndex = -1;
  seqFrame.setAttribute('aria-hidden','true');
  seqFrame.src = window.location.pathname.replace(/\/api\/[^/]+$/, '/api/seq_validation_ntcs_frame.php');
  document.getElementById('formEditor').appendChild(seqFrame);
  document.getElementById('formLanguage').addEventListener('change', function (event) {
    updateBody();
    document.cookie = 'language=' + encodeURIComponent(event.target.value) + '; path=/; SameSite=Lax';
    window.__FORM_LANGUAGE_SWITCH__ = true;
    window.location.reload();
  });

  function formError(message) {
    errorBox.replaceChildren();
    errorBox.textContent = message || '';
    errorBox.hidden = !message;
  }
  function showFieldErrors(errors) {
    errorBox.replaceChildren();
    if (!errors.length) {errorBox.hidden = true;return;}
    errorBox.hidden = false;
    const heading = document.createElement('strong');
    heading.textContent = labels.invalid + ' (' + errors.length + ')';
    errorBox.appendChild(heading);
    const list = document.createElement('ul'); list.className = 'config-error-list';
    errors.forEach(function (error) {
      const item = document.createElement('li');
      const field = sections.querySelector('[data-table="' + error.table + '"][data-row="' + error.index + '"][data-key="' + error.key + '"]');
      const card = Array.from(sections.querySelectorAll('.config-group'))[tables.indexOf(error.table)]?.querySelectorAll('.config-row')[error.index];
      const row = data[error.table][error.index];
      const location = (error.ip ? error.ip + ' / ' : '') + 'JOB ' + row.JOBID +
        (error.table === 'JOB_lst' ? '' : ' / SEQ ' + row.SEQID) +
        (error.table === 'STEP_lst' ? ' / STEP ' + row.StepSelect : '');
      const message = location + '：' + (error.key ? caption(error.key,row) + ' — ' : '') + error.message;
      if (field || card) {
        const link = document.createElement('button');link.type = 'button';link.className = 'config-error-link';link.textContent = message;
        link.onclick = function () {card.open = true;(field || card).scrollIntoView({behavior:'smooth',block:'center'});if (field) field.focus({preventScroll:true});};
        item.appendChild(link);
      } else item.textContent = message;
      if (field) {
        field.classList.add('config-input-error');field.setAttribute('aria-invalid','true');
        const wrapper = field.closest('.config-field');
        if (!wrapper.querySelector('.config-field-error')) {
          const note = document.createElement('small');note.className = 'config-field-error';note.textContent = error.message;wrapper.appendChild(note);
        }
      } else if (card) card.classList.add('config-row-error');
      list.appendChild(item);
    });
    errorBox.appendChild(list);
    errorBox.scrollIntoView({behavior:'smooth',block:'start'});
  }
  function clearFieldErrors() {
    sections.querySelectorAll('.config-input-error').forEach(function (field) {field.classList.remove('config-input-error');field.removeAttribute('aria-invalid');});
    sections.querySelectorAll('.config-field-error').forEach(function (note) {note.remove();});
    sections.querySelectorAll('.config-row-error').forEach(function (card) {card.classList.remove('config-row-error');});
    formError('');
  }
  function updateBody() {
    body.value = JSON.stringify(data);
    body.dispatchEvent(new Event('input', {bubbles:true}));
    try { localStorage.setItem(draftKey, body.value); } catch (e) {}
  }
  function nextId(table, field, row) {
    const used = new Set(data[table].filter(function (item) {return idFields[table].filter(function (key) {return key !== field;}).every(function (key) {return Number(item[key]) === Number(row[key]);});}).map(function (item) {return Number(item[field]);}));
    for (let n = 1; n <= (maximum[field] || 100); n++) if (!used.has(n)) return n;
    return null;
  }
  function createRow(table) {
    if (table === 'JOB_lst' && data.JOB_lst.length >= 100) {formError(labels.jobLimit); return;}
    if (table === 'SEQ_lst' && data.SEQ_lst.filter(function (s) {return Number(s.JOBID) === Number(data.JOB_lst[0].JOBID);}).length >= 50) {formError(labels.seqLimit); return;}
    const source = initialSample[table][0] || data[table][0];
    const row = JSON.parse(JSON.stringify(source));
    if (table === 'STEP_lst') {
      row.StepOption = 2;row.StepAngle = 3000;row.StepRPM = 500;row.StepDelay = 0;
      row.StepHiAngle = 30600;row.StepLoAngle = 0;
      row.StepLimiHi = 30;row.StepLimiLo = 30;row.StepMoniByWin = 0;
      row.InterruptAlarm = 0;row.OverAngleStop = 0;row.StepDirection = 0;
      row.StepTorqueOffsetSign = 43;row.StepTorqueOffset = 0;
      row.StepEnableThreshold = 0;row.StepEnableDownShift = 0;
      row.StepTorqueDownShift = 0;row.StepRPMDownShift = 0;row.KValue = 100;
      if (selectedTool && activeControllerUnit !== null) {
        const range = selectedTool.ranges[String(activeControllerUnit)];
        row.StepTorque = range.min;row.StepHiTorque = Math.round(range.max * 1.10 * Math.pow(10,range.precision)) / Math.pow(10,range.precision);
        row.StepLoTorque = 0;row.step_unit = activeControllerUnit;
      }
    }
    if (table !== 'JOB_lst') row.JOBID = data.JOB_lst[0].JOBID;
    if (table === 'STEP_lst') row.SEQID = data.SEQ_lst.find(function (seq) {return Number(seq.JOBID) === Number(row.JOBID);})?.SEQID || 1;
    const key = idFields[table].slice(-1)[0];
    if (table === 'STEP_lst' && data.STEP_lst.filter(function (s) {return Number(s.JOBID) === Number(row.JOBID) && Number(s.SEQID) === Number(row.SEQID);}).length >= 5) {formError(labels.stepLimit); return;}
    const next = nextId(table, key, row);
    if (next === null) { formError(labels.duplicate + ': ' + key); return; }
    row[key] = next;
    const name = {JOB_lst:'JOBname',SEQ_lst:'SEQname',STEP_lst:'STEPname'}[table];
    row[name] = titles[table] + '-' + next;
    if (table === 'JOB_lst') row.act = 0;
    data[table].push(row);
    updateBody(); render();
  }
  function fieldControl(table, row, index, key) {
    const value = row[key];
    const label = document.createElement('label');
    label.className = 'config-field';
    const text = document.createElement('span');
    text.textContent = caption(key, row);
    label.appendChild(text);
    let input;
    const parentTable = key === 'JOBID' && table !== 'JOB_lst' ? 'JOB_lst' : key === 'SEQID' && table === 'STEP_lst' ? 'SEQ_lst' : null;
    if (parentTable || key === 'step_unit' || (typeof value === 'number' && ['act','skip','ok_job','ok_job_stop','StepSwitch','StepDirection','StepOption','StepMoniByWin','StepTorqueOffsetSign','StepEnableThreshold','StepEnableDownShift','InterruptAlarm','OverAngleStop','unscrew_mode','unscrew_dir'].includes(key))) {
      input = document.createElement('select');
      let options;
      if (parentTable === 'JOB_lst') options = data.JOB_lst.map(function (j) {return [j.JOBID,j.JOBname];});
      else if (parentTable === 'SEQ_lst') options = data.SEQ_lst.filter(function (s) {return Number(s.JOBID) === Number(row.JOBID);}).map(function (s) {return [s.SEQID,s.SEQname];});
      else if (key === 'step_unit') options = Object.keys(units).map(function (code) {return [code,units[code]];});
      else if (key === 'StepOption') options = [[1,choices.angle],[2,choices.torque]];
      else if (key === 'StepMoniByWin') options = [[0,choices.off],[Number(row.StepOption),choices.on]];
      else if (key === 'StepDirection') options = [[1,choices.cw],[0,choices.ccw]];
      else if (key === 'StepTorqueOffsetSign') options = [[43,choices.plus],[45,choices.minus]];
      else if (key === 'StepEnableThreshold' || key === 'StepEnableDownShift') options = [[0,choices.off],[2,choices.torque],[1,choices.angle]];
      else if (key === 'unscrew_mode') options = [[1,choices.auto],[0,choices.custom]];
      else if (key === 'unscrew_dir') options = [[0,choices.cw],[1,choices.ccw]];
      else options = [[0,choices.off],[1,choices.on]];
      if (!options.some(function (opt) {return Number(opt[0]) === Number(value);}) && key !== 'StepOption' && key !== 'step_unit') options.push([value,String(value)]);
      if (key === 'StepOption' && !options.some(function (opt) {return Number(opt[0]) === Number(value);})) {
        options.unshift(['',labels.invalid + ': ' + caption(key)]);
        input.required = true;
      }
      if (key === 'step_unit' && !Object.prototype.hasOwnProperty.call(units,String(value))) {
        options.unshift(['',labels.invalid + ': ' + caption(key)]);
        input.required = true;
      }
      options.forEach(function (option) {const item = new Option(String(option[1]), String(option[0])); input.add(item);});
      input.value = (key === 'StepOption' && ![1,2].includes(Number(value))) || (key === 'step_unit' && !Object.prototype.hasOwnProperty.call(units,String(value))) ? '' : String(value);
      if (key === 'StepMoniByWin' && Number(value) === -1) input.querySelector('option[value="-1"]').textContent = choices.off;
    } else if (table === 'SEQ_lst' && key === 'unscrew_force') {
      const mode = document.createElement('select');
      mode.dataset.table = table;
      mode.dataset.row = String(index);
      mode.dataset.key = 'unscrew_force_mode';
      [[0,choices.on],[1,choices.unlimited],[2,choices.off]].forEach(function (option) {mode.add(new Option(option[1],String(option[0])));});
      mode.value = Number(value) >= 101 ? '1' : Number(value) === 0 ? '2' : '0';
      mode.disabled = Number(row.unscrew_mode) !== 0;
      label.appendChild(mode);
      input = document.createElement('input');
      input.type = 'number'; input.min = '1'; input.max = '100'; input.step = '1';
      input.value = Number(value) >= 101 ? '50' : String(value);
      input.disabled = mode.disabled || mode.value !== '0';
    } else {
      input = document.createElement('input');
      input.type = typeof value === 'number' ? 'number' : 'text';
      if (input.type === 'number') {
        input.step = Number.isInteger(value) ? '1' : 'any';
        if (maximum[key]) {input.min = '1';input.max = String(maximum[key]);}
        const range = fixedRanges[table][key];
        if (range) {input.min = String(range[0]);input.max = String(range[1]);}
        if (table === 'STEP_lst' && key === 'StepRPM' && selectedTool) {
          input.min = String(selectedTool.min_rpm);
          input.max = String(selectedTool.max_rpm);
        }
      }
      if (key.endsWith('name')) {input.required = true; input.maxLength = key === 'JOBname' ? 250 : 64;}
      input.value = value === null ? '' : String(value);
    }
    input.dataset.table = table;
    input.dataset.row = String(index);
    input.dataset.key = key;
    if (table === 'SEQ_lst' && ['unscrew_rpm','unscrew_torque_threshold','unscrew_angle_threshold','unscrew_dir'].includes(key)) input.disabled = Number(row.unscrew_mode) !== 0;
    input.setAttribute('aria-label', titles[table] + ' ' + (index + 1) + ' ' + caption(key, row));
    label.appendChild(input);
    return label;
  }
  function render() {
    sections.replaceChildren();
    tables.forEach(function (table) {
      const group = document.createElement('section'); group.className = 'config-group';
      const heading = document.createElement('div'); heading.className = 'config-heading';
      const title = document.createElement('h2'); title.textContent = titles[table] + ' (' + data[table].length + ')'; heading.appendChild(title);
      const add = document.createElement('button'); add.type = 'button'; add.className = 'btn btn-secondary'; add.textContent = '+ ' + labels.add + ' ' + titles[table]; add.onclick = function () {createRow(table);}; heading.appendChild(add);
      group.appendChild(heading);
      data[table].forEach(function (row, index) {
        if (table === 'STEP_lst' && !Object.prototype.hasOwnProperty.call(row,'step_unit')) row.step_unit = null;
        const card = document.createElement('details'); card.className = 'config-row'; card.open = index === 0;
        const summary = document.createElement('summary'); summary.textContent = titles[table] + ' ' + idFields[table].map(function (key) {return row[key];}).join(' / ') + ' — ' + (row.JOBname || row.SEQname || row.STEPname || ''); card.appendChild(summary);
        const grid = document.createElement('div'); grid.className = 'config-fields';
        // Match the fields exposed by the native NTCS JOB / SEQ / STEP editors.
        // Values not shown here remain in `row` and are still sent to the API.
        const visibleFields = {
          JOB_lst: new Set(['JOBID','JOBname','ok_job','ok_job_stop']),
          SEQ_lst: new Set([
            'JOBID','SEQID','SEQname','seq_repeat','timeout','dt_time','tt_time',
            'ok_seq','ok_stop','ng_stop','accu_angle','Thread_Calcu',
            'unscrew_count_switch','ng_unscrew','unscrew_mode','unscrew_rpm',
            'unscrew_torque_threshold','unscrew_angle_threshold','unscrew_dir',
            'unscrew_force','total_angle_limit','total_angle_lower'
          ]),
          STEP_lst: new Set([
            'JOBID','SEQID','StepSelect','STEPname','StepOption','StepTorque',
            'StepAngle','StepHiTorque','StepLoTorque','StepMoniByWin',
            'StepLimiHi','StepLimiLo','StepHiAngle','StepLoAngle',
            'InterruptAlarm','OverAngleStop','StepDirection','StepDelay',
            'StepRPM','StepTorqueOffsetSign','StepTorqueOffset',
            'StepEnableThreshold','StepTorqueTS','StepEnableDownShift',
            'StepTorqueDownShift','StepRPMDownShift'
          ])
        };
        const fieldKeys = Object.keys(row);
        if (table === 'STEP_lst') {
          const valueIndex = fieldKeys.indexOf('StepTorqueOffset');
          const directionIndex = fieldKeys.indexOf('StepTorqueOffsetSign');
          if (valueIndex !== -1 && directionIndex !== -1 && valueIndex < directionIndex) {
            fieldKeys[valueIndex] = 'StepTorqueOffsetSign';
            fieldKeys[directionIndex] = 'StepTorqueOffset';
          }
        }
        fieldKeys.forEach(function (key) {
          if (!visibleFields[table].has(key) && !(table === 'STEP_lst' && key === 'step_unit' && !Object.prototype.hasOwnProperty.call(units,String(row.step_unit)))) return;
          if (table === 'STEP_lst') {
            const mode = Number(row.StepOption);
            if (key === 'StepTorque' && mode !== 2) return;
            if (key === 'StepAngle' && mode !== 1) return;
            if (key === 'OverAngleStop' && mode !== 2) return;
            if (key === 'StepTorqueTS' && Number(row.StepEnableThreshold) === 0) return;
            if (['StepTorqueDownShift','StepRPMDownShift'].includes(key) && Number(row.StepEnableDownShift) === 0) return;
          }
          grid.appendChild(fieldControl(table, row, index, key));
        });
        card.appendChild(grid);
        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-neutral remove-row'; remove.textContent = labels.remove + ' ' + titles[table];
        remove.onclick = function () {data[table].splice(index,1);updateBody();render();}; card.appendChild(remove);
        group.appendChild(card);
      });
      sections.appendChild(group);
    });
    if (data) refreshBarcodeJobs();
  }
  function validate() {
    clearFieldErrors();
    for (const table of tables) if (!data[table].length) {formError(labels.required);return false;}
    if (data.JOB_lst.length > 100) {formError(labels.jobLimit);return false;}
    const seqCounts = {};
    data.SEQ_lst.forEach(function (seq) {seqCounts[seq.JOBID] = (seqCounts[seq.JOBID] || 0) + 1;});
    if (Object.values(seqCounts).some(function (count) {return count > 50;})) {formError(labels.seqLimit);return false;}
    const stepCounts = {};
    data.STEP_lst.forEach(function (step) {const key = step.JOBID + ':' + step.SEQID;stepCounts[key] = (stepCounts[key] || 0) + 1;});
    if (Object.values(stepCounts).some(function (count) {return count > 5;})) {formError(labels.stepLimit);return false;}
    const fields = Array.from(sections.querySelectorAll('input,select'));
    const jobErrors = [];
    data.JOB_lst.forEach(function (job,index) {
      if (!Number.isInteger(Number(job.JOBID)) || Number(job.JOBID) < 1 || Number(job.JOBID) > 100) jobErrors.push({table:'JOB_lst',index:index,key:'JOBID',message:rangeMessage('JOB_lst','JOBID')});
      if (typeof job.JOBname !== 'string' || job.JOBname.length < 1 || job.JOBname.length > 250 || !/^[a-zA-Z0-9\u4e00-\u9fa5-]+$/.test(job.JOBname)) jobErrors.push({table:'JOB_lst',index:index,key:'JOBname',message:labels.invalid});
      ['ok_job','ok_job_stop'].forEach(function (key) {if (![0,1].includes(Number(job[key])) || job[key] == null) jobErrors.push({table:'JOB_lst',index:index,key:key,message:labels.invalid});});
    });
    if (jobErrors.length) {showFieldErrors(jobErrors);return false;}
    const bad = fields.filter(function (field) {return !field.disabled && (!field.checkValidity() || (field.type === 'number' && field.value === ''));});
    if (bad.length) {
      showFieldErrors(bad.map(function (field) {return {table:field.dataset.table,index:Number(field.dataset.row),key:field.dataset.key,message:field.dataset.table === 'STEP_lst' && field.dataset.key === 'StepRPM' && selectedTool
        ? (language === 'zh-tw' ? '鎖附轉速須介於 ' : language === 'zh-cn' ? '锁附转速须介于 ' : 'Run down speed must be between ') + selectedTool.min_rpm + '～' + selectedTool.max_rpm + ' rpm'
        : fixedRanges[field.dataset.table]?.[field.dataset.key] && (field.validity.rangeOverflow || field.validity.rangeUnderflow) ? rangeMessage(field.dataset.table,field.dataset.key) : field.validationMessage || labels.invalid};}));return false;
    }
    const badNames = fields.filter(function (field) {return ['JOBname','SEQname'].includes(field.dataset.key) && !/^[a-zA-Z0-9\u4e00-\u9fa5-]+$/.test(field.value);});
    if (badNames.length) {showFieldErrors(badNames.map(function (field) {return {table:field.dataset.table,index:Number(field.dataset.row),key:field.dataset.key,message:labels.invalid};}));return false;}
    if (data.JOB_lst.filter(function (job) {return Number(job.act) === 1;}).length > 1) {formError(labels.active);return false;}
    for (const table of tables) {
      const seen = new Set();
      for (const row of data[table]) {
        if (table === 'STEP_lst' && ![1,2].includes(Number(row.StepOption))) {formError(labels.invalid + ': ' + caption('StepOption'));return false;}
        const key = idFields[table].map(function (field) {return row[field];}).join(':');
        if (seen.has(key)) {formError(labels.duplicate + ': ' + titles[table] + ' ' + key);return false;}
        seen.add(key);
        if (table !== 'JOB_lst' && !data.JOB_lst.some(function (job) {return Number(job.JOBID) === Number(row.JOBID);})) {formError(labels.reference);return false;}
        if (table === 'STEP_lst' && !data.SEQ_lst.some(function (seq) {return Number(seq.JOBID) === Number(row.JOBID) && Number(seq.SEQID) === Number(row.SEQID);})) {formError(labels.reference);return false;}
      }
    }
    updateBody();
    return true;
  }
  sections.addEventListener('change', function (event) {
    const field = event.target;
    if (!field.dataset.table) return;
    const row = data[field.dataset.table][Number(field.dataset.row)];
    clearFieldErrors();
    if (field.dataset.key === 'unscrew_force_mode') {
      row.unscrew_force = field.value === '1' ? 101 : field.value === '2' ? 0 : 50;
      updateBody(); render(); return;
    }
    const old = row[field.dataset.key];
    row[field.dataset.key] = typeof old === 'number' ? (field.value === '' ? null : Number(field.value)) : (field.value === '' && old === null ? null : field.value);
    updateBody();
    if (idFields[field.dataset.table].includes(field.dataset.key) || ['JOBname','SEQname','STEPname','StepOption','StepMoniByWin','StepEnableThreshold','StepEnableDownShift','step_unit','seq_unit','unscrew_mode'].includes(field.dataset.key)) {
      if (field.dataset.key === 'StepOption' && ![0,-1,Number(row.StepOption)].includes(Number(row.StepMoniByWin))) row.StepMoniByWin = 0;
      updateBody(); render();
    }
  });
  sections.addEventListener('input', function (event) {
    const field = event.target;
    if (!field.dataset.table || field.tagName === 'SELECT') return;
    if (field.classList.contains('config-input-error')) clearFieldErrors();
    const row = data[field.dataset.table][Number(field.dataset.row)];
    const old = row[field.dataset.key];
    row[field.dataset.key] = typeof old === 'number' ? (field.value === '' ? null : Number(field.value)) : (field.value === '' && old === null ? null : field.value);
    updateBody();
  });
  // Show the same field feedback on blur for JOB, SEQ and STEP.
  const liveToolCache = new Map();
  async function readTargetTool(ip, refresh) {
    if (!refresh && liveToolCache.has(ip)) return liveToolCache.get(ip);
    const endpoint = window.location.pathname.replace(/\/api\/[^/]+$/, '/api/job_config_form_tool.php');
    const response = await fetch(endpoint + '?target=' + encodeURIComponent(ip), {credentials:'same-origin',cache:'no-store'});
    const result = await response.json();
    if (!response.ok || !result.success || !result.tool) throw new Error(ip + ': ' + labels.tool);
    liveToolCache.set(ip,result.tool);
    return result.tool;
  }
  async function syncTargetUnits(targets, refresh) {
    const targetTools = [];
    for (const ip of targets) targetTools.push(await readTargetTool(ip,refresh));
    const codes = targetTools.map(function (tool) {return tool.controller_torque_unit;});
    if (codes.some(function (unit) {return !Object.prototype.hasOwnProperty.call(units,String(unit));})) throw new Error(labels.tool);
    if (new Set(codes.map(Number)).size !== 1) {
      throw new Error(language === 'zh-tw' ? '目標控制器的扭力單位不同，請分批寫入。' :
        language === 'zh-cn' ? '目标控制器的扭力单位不同，请分批写入。' :
          'Controllers use different torque units; write them in separate batches.');
    }
    const toolChanged = selectedTool !== targetTools[0];
    const unitUnchanged = activeControllerUnit === Number(codes[0]);
    selectedTool = targetTools[0];
    applyControllerUnit(Number(codes[0]));
    if (toolChanged && unitUnchanged) render();
    return targetTools;
  }
  async function refreshSelectedController() {
    const single = document.getElementById('modeSingle').checked;
    const input = document.getElementById(single ? 'singleTargetIp' : 'targetIps').value;
    const targets = Array.from(new Set(input.split(/[\s,;]+/).filter(Boolean)));
    if (!targets.length) {selectedTool = null;return;}
    try {await syncTargetUnits(targets,true);formError('');}
    catch (error) {selectedTool = null;formError(error.message || labels.tool);}
  }
  ['singleTargetIp','targetIps','modeSingle','modeMulti'].forEach(function (id) {
    document.getElementById(id).addEventListener('change',refreshSelectedController);
  });
  sections.addEventListener('focusout', async function (event) {
    const field = event.target;
    if (!field.dataset.table || field.disabled || !data[field.dataset.table]?.[Number(field.dataset.row)]) return;
    const table = field.dataset.table;
    const key = field.dataset.key;
    const row = data[table][Number(field.dataset.row)];
    let message = '';
    if (!field.checkValidity() || (field.type === 'number' && field.value === '')) {
      message = table === 'STEP_lst' && key === 'StepRPM' && selectedTool
        ? (language === 'zh-tw' ? '鎖附轉速須介於 ' : language === 'zh-cn' ? '锁附转速须介于 ' : 'Run down speed must be between ') + selectedTool.min_rpm + '～' + selectedTool.max_rpm + ' rpm'
        : fixedRanges[table]?.[key] && (field.validity.rangeOverflow || field.validity.rangeUnderflow)
        ? rangeMessage(table,key) : field.validationMessage || labels.invalid;
    } else if (['JOBname','SEQname'].includes(key) && !/^[a-zA-Z0-9\u4e00-\u9fa5-]+$/.test(field.value)) {
      message = labels.invalid;
    } else if (idFields[table].includes(key) && data[table].some(function (item) {
      return item !== row && idFields[table].every(function (id) {return Number(item[id]) === Number(row[id]);});
    })) message = labels.duplicate;
    if (message) {showFieldErrors([{table:table,index:Number(field.dataset.row),key:key,message:message}]);return;}
    if (table === 'JOB_lst' && ['ok_job','ok_job_stop'].includes(key) && ![0,1].includes(Number(row[key]))) {
      showFieldErrors([{table:table,index:Number(field.dataset.row),key:key,message:labels.invalid}]);return;
    }
    const checkStep = table === 'STEP_lst' && ['StepTorque','StepAngle','StepHiTorque','StepLoTorque','StepHiAngle','StepLoAngle','StepRPM','StepDelay','StepTorqueTS','StepTorqueDownShift','StepRPMDownShift','StepTorqueOffset','StepLimiHi','StepLimiLo'].includes(key);
    const checkSeq = table === 'SEQ_lst' && ['SEQname','seq_repeat','timeout','dt_time','tt_time','unscrew_rpm','unscrew_torque_threshold','unscrew_angle_threshold','unscrew_force','total_angle_limit','total_angle_lower'].includes(key);
    if (!checkStep && !checkSeq) return;
    const single = document.getElementById('modeSingle').checked;
    const targetText = document.getElementById(single ? 'singleTargetIp' : 'targetIps').value;
    const targets = Array.from(new Set(targetText.split(/[\s,;]+/).filter(Boolean)));
    if (!targets.length) return;
    const errors = [];
    try {
      const tools = await syncTargetUnits(targets,false);
      const snapshot = JSON.stringify(row);
      for (let i = 0; i < targets.length; i++) {
        if (snapshot !== JSON.stringify(row)) return;
        errors.push.apply(errors,checkSeq ? validateNtcsSeq(row,tools[i],targets[i]) : validateNtcsStep(row,tools[i],targets[i]));
      }
      if (snapshot === JSON.stringify(row) && errors.length) showFieldErrors(errors);
    } catch (error) {formError(error.message || labels.tool);}
  });
  function validateNtcsSeq(seq, tool, ip) {
    const validator = seqFrame.contentWindow && seqFrame.contentWindow.validateImportedNtcsSeq;
    if (typeof validator !== 'function') throw new Error(labels.validator.replace('STEP','SEQ'));
    if (!tool || !tool.ranges || !tool.ranges[String(seq.seq_unit)]) throw new Error(ip + ': ' + labels.tool);
    const result = validator(seq,tool);
    if (result && result.valid) return [];
    const keys = result && Array.isArray(result.errors) && result.errors.length ? result.errors : [null];
    return keys.map(function (key) {
      const known = key && Object.prototype.hasOwnProperty.call(seq,key);
      return {table:'SEQ_lst',index:data.SEQ_lst.indexOf(seq),key:known ? key : null,ip:ip,
        message:result.details && result.details[key] || (known ? labels.invalid : key && key !== 'SEQ' ? labels.invalid + ': ' + key : labels.invalid)};
    });
  }
  function validateNtcsStep(step, tool, ip) {
    // The native STEP editor limits RPM to a 1–4 digit integer inside the tool range.
    // Check it here first so the off-screen editor cannot hide the reason in a dialog.
    const rpm = Number(step.StepRPM);
    if (!/^\d{1,4}$/.test(String(step.StepRPM)) || !Number.isInteger(rpm) ||
        rpm < Number(tool.min_rpm) || rpm > Number(tool.max_rpm)) {
      const limits = String(tool.min_rpm) + '～' + String(tool.max_rpm);
      const message = language === 'zh-tw' ? '鎖附轉速須為 ' + limits + ' rpm 範圍內的 1～4 位整數' :
        language === 'zh-cn' ? '锁附转速须为 ' + limits + ' rpm 范围内的 1～4 位整数' :
          'Run down speed must be a 1–4 digit integer between ' + tool.min_rpm + ' and ' + tool.max_rpm + ' rpm';
      return [{table:'STEP_lst',index:data.STEP_lst.indexOf(step),key:'StepRPM',ip:ip,message:message}];
    }
    const frame = document.getElementById('ntcsStepValidator');
    const validator = frame && frame.contentWindow && frame.contentWindow.validateImportedNtcsStep;
    if (typeof validator !== 'function') throw new Error(labels.validator);
    if (!Object.prototype.hasOwnProperty.call(units,String(step.step_unit))) {
      return [{table:'STEP_lst',index:data.STEP_lst.indexOf(step),key:'step_unit',ip:ip,message:labels.invalid}];
    }
    if (!tool || !tool.ranges || !tool.ranges[String(step.step_unit)]) throw new Error(ip + ': ' + labels.tool);
    const result = validator(step, tool);
    if (result && result.valid) return [];
    const range = tool.ranges[String(step.step_unit)];
    const derived = [];
    const describe = function (zhTw,zhCn,en) {return language === 'zh-tw' ? zhTw : language === 'zh-cn' ? zhCn : en;};
    const add = function (key,message) {derived.push({table:'STEP_lst',index:data.STEP_lst.indexOf(step),key:key,ip:ip,message:message});};
    const number = function (key) {return Number(step[key]);};
    if (Number(step.StepOption) === 2) {
      if (number('StepTorque') < range.min || number('StepTorque') > range.max) {
        add('StepTorque',describe('目標扭力須介於 '+range.min+'～'+range.max+' '+units[step.step_unit],
          '目标扭力须介于 '+range.min+'～'+range.max+' '+units[step.step_unit],
          'Target torque must be between '+range.min+' and '+range.max+' '+units[step.step_unit]));
      }
      if (number('StepHiTorque') <= number('StepTorque') || number('StepHiTorque') > range.high) {
        add('StepHiTorque',describe('扭力上限須大於目標扭力 '+step.StepTorque+'，且不超過工具上限 '+range.high+' '+units[step.step_unit],
          '扭力上限须大于目标扭力 '+step.StepTorque+'，且不超过工具上限 '+range.high+' '+units[step.step_unit],
          'High torque must exceed target '+step.StepTorque+' and not exceed tool limit '+range.high+' '+units[step.step_unit]));
      }
      if (number('StepLoTorque') >= number('StepTorque') || number('StepLoTorque') >= number('StepHiTorque')) {
        add('StepLoTorque',describe('扭力下限須小於目標扭力與扭力上限','扭力下限须小于目标扭力与扭力上限','Low torque must be below target and high torque'));
      }
    } else if (Number(step.StepOption) === 1 && !(number('StepLoAngle') < number('StepAngle') && number('StepAngle') < number('StepHiAngle'))) {
      add('StepAngle',describe('目標角度須大於角度下限且小於角度上限','目标角度须大于角度下限且小于角度上限','Target angle must be between the angle limits'));
    }
    const aliases = {step_limit_hi_tor:'StepLimiHi',step_limit_hi_ang:'StepLimiHi',step_limit_lo_tor:'StepLimiLo',step_limit_lo_ang:'StepLimiLo'};
    const keys = result && Array.isArray(result.errors) && result.errors.length ? result.errors : [null];
    const nativeErrors = keys.map(function (key) {
      const mapped = aliases[key] || key;
      const known = mapped && Object.prototype.hasOwnProperty.call(step,mapped);
      return {table:'STEP_lst',index:data.STEP_lst.indexOf(step),key:known ? mapped : null,ip:ip,
        message:result.details && result.details[key] || (known ? labels.invalid : key && key !== 'STEP' ? labels.invalid + ': ' + key : labels.invalid)};
    });
    const informative = nativeErrors.filter(function (error) {return error.key && !derived.some(function (item) {return item.key === error.key;});});
    return derived.concat(informative.length ? informative : derived.length ? [] : nativeErrors);
  }
  let passingToolCheck = false;
  ['previewButton','sendButton','retryFailedButton'].forEach(function (id) {
    const button = document.getElementById(id);
    button.addEventListener('click', async function (event) {
      if (passingToolCheck) {passingToolCheck = false;return;}
      event.stopImmediatePropagation();event.preventDefault();
      if (!validate()) return;
      const single = document.getElementById('modeSingle').checked;
      const input = document.getElementById(single ? 'singleTargetIp' : 'targetIps').value;
      const targets = Array.from(new Set(input.split(/[\s,;]+/).map(function (x) {return x.trim();}).filter(Boolean)));
      if (!targets.length) {formError(labels.target);return;}
      button.disabled = true;
      try {
        const targetTools = await syncTargetUnits(targets,true);
        if (!validate()) return;
        const errors = [];
        for (let i = 0; i < targets.length; i++) {
          for (const seq of data.SEQ_lst) errors.push.apply(errors,validateNtcsSeq(seq, targetTools[i], targets[i]));
          for (const step of data.STEP_lst) errors.push.apply(errors,validateNtcsStep(step, targetTools[i], targets[i]));
        }
        if (errors.length) {showFieldErrors(errors);return;}
        formError('');button.disabled = false;passingToolCheck = true;button.click();
      } catch (error) {formError(error && error.message && error.message !== 'Failed to fetch' ? error.message : labels.tool);}
      finally {button.disabled = false;}
    }, true);
  });
  ['previewButton','sendButton','retryFailedButton','downloadSampleButton','copyApiButton'].forEach(function (id) {
    document.getElementById(id).addEventListener('click', function (event) {
      if (!validate()) {event.stopImmediatePropagation();event.preventDefault();}
    }, true);
  });
  document.getElementById('formReset').addEventListener('click', function () {
    if (!confirm(T.restore_sample_confirm)) return;
    data = JSON.parse(JSON.stringify(initialSample));
    updateBody(); render(); formError('');refreshSelectedController();
  });
  document.getElementById('formExport').addEventListener('click', function () {
    const contents = JSON.stringify({JOB_lst:data.JOB_lst,SEQ_lst:data.SEQ_lst,STEP_lst:data.STEP_lst},null,2) + '\n';
    const url = URL.createObjectURL(new Blob(['\uFEFF',contents], {type:'application/json;charset=utf-8'}));
    const link = document.createElement('a');
    link.href = url;
    link.download = 'idas_job_seq_step_' + new Date().toISOString().slice(0,19).replace(/[-:T]/g,'') + '.json';
    document.body.appendChild(link);
    link.click();link.remove();
    setTimeout(function () {URL.revokeObjectURL(url);},1000);
  });
  const scanInput = document.getElementById('formScanInput');
  const scanResult = document.getElementById('formScanResult');
  const scanMatched = document.getElementById('formScanMatched');
  const barcodeFrom = document.getElementById('formBarcodeFrom');
  const barcodeCount = document.getElementById('formBarcodeCount');
  const barcodeMode = document.getElementById('formBarcodeMode');
  const barcodeJob = document.getElementById('formBarcodeJob');
  const barcodeSeq = document.getElementById('formBarcodeSeq');
  const mapStatus = document.getElementById('formMapStatus');
  const mapRows = document.getElementById('formMapRows');
  const mapEndpoint = window.location.pathname.replace(/\/api\/[^/]+$/, '/api/job_config_barcode_map.php');
  function replaceOptions(select,rows,placeholder) {
    const previous = select.value;
    select.replaceChildren(new Option(placeholder,'-1'));
    rows.forEach(function (item) {select.add(new Option(item.label,String(item.id)));});
    select.value = Array.from(select.options).some(function (item) {return item.value === previous;}) ? previous : '-1';
  }
  function refreshBarcodeSequences() {
    const rows = data.SEQ_lst.filter(function (seq) {
      return String(seq.JOBID) === barcodeJob.value;
    }).map(function (seq) {return {id:seq.SEQID,label:seq.SEQID+' '+seq.SEQname};});
    replaceOptions(barcodeSeq,rows.filter(function (row) {
      const seq = data.SEQ_lst.find(function (item) {return String(item.JOBID) === barcodeJob.value && String(item.SEQID) === String(row.id);});
      return seq && (barcodeMode.value !== '3' || Number(seq.skip || 0) === 0);
    }),'—');
  }
  function refreshBarcodeJobs() {
    replaceOptions(barcodeJob,data.JOB_lst.map(function (job) {return {id:job.JOBID,label:job.JOBID+' '+job.JOBname};}),
      language === 'zh-tw' ? '請選擇工作' : language === 'zh-cn' ? '请选择工作' : 'Select Job');
    refreshBarcodeSequences();
  }
  function refreshScanResult() {
    const raw = scanInput.value;
    scanResult.textContent = raw;
    scanMatched.textContent = '';
    if (!raw) return;
    const start = Number(barcodeFrom.value),count = Number(barcodeCount.value);
    if (!Number.isInteger(start) || start < 1 || start > 54 || !Number.isInteger(count) || count < 1 || count > 54 || start > raw.length) {
      scanMatched.textContent = language === 'zh-tw' ? '請設定有效起始位元與位數（1～54），起始位元不可超過條碼長度。' :
        language === 'zh-cn' ? '请设置有效起始位与位数（1～54），起始位不能超过条码长度。' :
          'Set a valid start and maximum count (1–54); start must be within the barcode.';
      return;
    }
    const matched = raw.slice(start-1,start-1+count);
    scanMatched.textContent = (language === 'zh-tw' ? '有效條碼：' : language === 'zh-cn' ? '有效条码：' : 'Matched barcode: ') + matched;
  }
  async function mapRequest(method,payload) {
    const response = await fetch(mapEndpoint + (method === 'GET' && payload ? '?scan=' + encodeURIComponent(payload) : ''), {
      method:method,credentials:'same-origin',cache:'no-store',
      headers:method === 'GET' ? {} : {'Content-Type':'application/json'},
      body:method === 'GET' ? undefined : JSON.stringify(payload)
    });
    const result = await response.json();
    if (!response.ok || !result.success) throw new Error(result.message || labels.invalid);
    return result;
  }
  async function loadMappings() {
    const result = await mapRequest('GET');
    mapRows.replaceChildren();
    result.mappings.forEach(function (item) {
      const tr = document.createElement('tr');
      [item.barcode,item.job_id,item.seq_id].forEach(function (value) {
        const td = document.createElement('td');td.textContent = String(value);tr.appendChild(td);
      });
      const cell = document.createElement('td');const remove = document.createElement('button');
      remove.type = 'button';remove.className = 'btn btn-neutral';
      remove.textContent = labels.remove;
      remove.addEventListener('click',async function () {
        try {await mapRequest('DELETE',{id:Number(item.id)});await loadMappings();mapStatus.textContent = '';}
        catch (error) {mapStatus.textContent = error.message;}
      });
      cell.appendChild(remove);tr.appendChild(cell);mapRows.appendChild(tr);
    });
  }
  let scanLookup = 0;
  async function lookupMapping() {
    const raw = scanInput.value;
    if (!raw) return;
    const current = ++scanLookup;
    try {
      const response = await mapRequest('GET',raw);
      if (current !== scanLookup || raw !== scanInput.value) return;
      const mapping = response.mapping;
      if (!mapping) {mapStatus.textContent = language === 'zh-tw' ? '尚無此條碼的對應資料' : language === 'zh-cn' ? '尚无此条码的对应数据' : 'No mapping for this barcode';return;}
      barcodeFrom.value = mapping.range_from;barcodeCount.value = mapping.range_count;
      barcodeMode.value = mapping.barcode_mode;
      barcodeJob.value = String(mapping.job_id);refreshBarcodeSequences();
      barcodeSeq.value = String(mapping.seq_id);refreshScanResult();
      mapStatus.textContent = (language === 'zh-tw' ? '已對應：' : language === 'zh-cn' ? '已对应：' : 'Mapped: ') +
        'JOB '+mapping.job_id+' / SEQ '+mapping.seq_id;
    } catch (error) {if (current === scanLookup) mapStatus.textContent = error.message;}
  }
  document.getElementById('formMapSave').addEventListener('click',async function () {
    const job = Number(barcodeJob.value),seq = Number(barcodeSeq.value);
    const raw = scanInput.value.trim();
    const from = Number(barcodeFrom.value),count = Number(barcodeCount.value);
    if (!raw || !Number.isInteger(from) || !Number.isInteger(count) || from < 1 || from > 54 || count < 1 || count > 54 || from > raw.length) {
      mapStatus.textContent = language === 'zh-tw' ? '條碼長度為 ' + raw.length + ' 碼；起始位元須落在條碼內，有效位數可設為最大 54 碼。' :
        language === 'zh-cn' ? '条码长度为 ' + raw.length + ' 码；起始位须在条码内，有效位数可设为最多 54 码。' :
          'Barcode length is ' + raw.length + '; start must be inside it. Count may be set to a maximum of 54.';
      barcodeFrom.focus();
      return;
    }
    if (!data.SEQ_lst.some(function (row) {return Number(row.JOBID) === job && Number(row.SEQID) === seq;}) ||
        ![1,2,3].includes(Number(barcodeMode.value)) || barcodeSeq.value === '-1') {
      mapStatus.textContent = language === 'zh-tw' ? '請選擇條碼模式，以及表單中存在的 JOB／SEQ。' :
        language === 'zh-cn' ? '请选择条码模式及表单中存在的 JOB／SEQ。' :
          'Select a barcode mode and an existing JOB / SEQ.';
      return;
    }
    try {
      const result = await mapRequest('POST',{raw_barcode:raw,range_from:from,
        range_count:count,barcode_mode:Number(barcodeMode.value),job_id:job,seq_id:seq});
      mapStatus.textContent = (language === 'zh-tw' ? '已儲存條碼：' : language === 'zh-cn' ? '已保存条码：' : 'Saved barcode: ') + result.barcode;
      await loadMappings();
    } catch (error) {mapStatus.textContent = error.message;}
  });
  barcodeMode.addEventListener('change',refreshBarcodeSequences);
  barcodeJob.addEventListener('change',refreshBarcodeSequences);
  [barcodeFrom,barcodeCount].forEach(function (field) {field.addEventListener('input',refreshScanResult);});
  scanInput.addEventListener('input',refreshScanResult);
  scanInput.addEventListener('keydown',function (event) {if (event.key === 'Enter') {event.preventDefault();scanInput.value = scanInput.value.trim();refreshScanResult();lookupMapping();}});
  scanInput.addEventListener('change',lookupMapping);
  upload.addEventListener('change', function () {
    const selected = upload.files[0];
    if (!selected) return;
    selected.text().then(function (text) {
      const parsed = JSON.parse(text);
      if (!parsed || tables.some(function (table) {return !Array.isArray(parsed[table]);})) throw new Error(labels.invalid);
      data = parsed;
      document.getElementById('formFileName').textContent = selected.name;
      updateBody();render();formError('');refreshSelectedController();
    }).catch(function (error) {formError(error.message);});
  });
  try {
    const saved = localStorage.getItem(draftKey);
    data = saved ? JSON.parse(saved) : JSON.parse(body.value);
    if (tables.some(function (table) {return !Array.isArray(data[table]);})) throw new Error(labels.invalid);
  } catch (error) {data = JSON.parse(JSON.stringify(initialSample));}
  updateBody();render();refreshSelectedController();
  loadMappings().catch(function (error) {mapStatus.textContent = error.message;});
})();
