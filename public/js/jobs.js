
var oldjobname ='';
var old_jobid  = '';

function cound_job(action) {
    const table = document.getElementById('job_table');
    const selectedRow = table.querySelector('.selected');

    const jobid = selectedRow ? selectedRow.cells[0].innerText : null;
    oldjobname = selectedRow ? selectedRow.cells[1].innerText : null;
    old_jobid  = selectedRow ? selectedRow.cells[0].innerText : null;

    const needJobid = ['del', 'edit', 'copy'];

    // 若需要 jobid 的操作卻沒選取列
    if (needJobid.includes(action) && !jobid) {
        //IdasNotify.warning("Please select a job first.");
        return;
    }

    // 統一加載遮罩
    document.querySelector(".main-content").classList.add("overlay-active");

    switch (action) {
        case 'del':
            delete_jobid(jobid);
            break;
        case 'edit':
            edit_job(jobid);
            break;
        case 'new':
            create_job();
            break;
        case 'copy':
            copy_job(jobid);
            break;
        default:
            IdasNotify.error("Unknown action: " + action);
            document.querySelector(".main-content").classList.remove("overlay-active");
    }
}


function readFromLocalStorage(key) {
    return localStorage.getItem(key);
}

function create_job() {
    
    //帶入預設值
    document.getElementById('newjob').style.display = 'block';
    document.getElementById('job_ok').checked = true;
    document.getElementById('stop_job_ok_off').checked = true;


}

function copy_job(jobid){
    document.getElementById('from_job_id').value =jobid;
    document.getElementById('from_job_name').value =oldjobname;
    document.getElementById('copyjob').style.display = 'block';
    
}

function updatejob(){

    var jobid      = document.getElementById("edit_jobid").value;
    var jobname    = document.getElementById("edit_jobname").value;
    var jobokValue = document.querySelector('input[name="edit_job_ok"]:checked').value;
    var stopjobValue = document.querySelector('input[name="edit_stop_job_ok"]:checked').value;

    let check_edit = edit_input_check();

    if(check_edit) {
        $.ajax({
            url: "?url=Jobs/update_job",
            method: "POST",
            data: { 
                jobid: jobid,
                jobname: jobname,
                jobokValue:jobokValue,
                stopjobValue:stopjobValue

            },
            success: function(response) {   

                
                var responseData = (typeof response === 'string') ? JSON.parse(response) : response;
                IdasNotify.alert(responseData.res_type, responseData.res_msg, function() {
                    localStorage.setItem('jobid', jobid);
                    localStorage.setItem('jobname', jobname);
                    history.go(0);
                });

            },
            error: function(xhr, status, error) {
                
            }
        });

    }
   
}

function edit_job(jobid) {

    if(jobid){
        $.ajax({
            url: "?url=Jobs/search_job",
            method: "POST",
            data:{ 
                jobid: jobid
            },
            success: function(response) {
                var responseJSON = JSON.stringify(response);
                var cleanString = responseJSON.replace(/Array|\\n/g, '');
                var cleanString = cleanString.substring(2, cleanString.length - 2);
                var [, jobid] = cleanString.match(/\[JOBID]\s*=>\s*([^ ]+)/) || [, null];
                var [, jobname] = cleanString.match(/\[JOBname]\s*=>\s*([^ ]+)/) || [, null];
              
                var [, ok_job] = cleanString.match(/\[ok_job]\s*=>\s*([^ ]+)/) || [, null];
                var [, ok_job_stop] = cleanString.match(/\[ok_job_stop]\s*=>\s*([^ ]+)/) || [, null];
          
                document.getElementById('editjob').style.display = 'block';


                document.getElementById("edit_jobid").value = jobid;
                document.getElementById("edit_jobname").value = jobname;
                var radioButtons_job = document.getElementsByName("edit_job_ok");
                setRadioButtonValue(radioButtons_job, ok_job);

                var radioButtons_stop_job = document.getElementsByName("edit_stop_job_ok");
                setRadioButtonValue(radioButtons_stop_job, ok_job_stop);
              
            },
            error: function(xhr, status, error) {
                
            }
        });
    }   
}


function validateJobForm() {
  // === 多語詞庫 ===
  const I18N = {
    'en-us': {
      dialogTitle: 'Form Error',
      empty: '“{label}” cannot be empty.',
      minLen: '“{label}” must be at least {min} characters.',
      maxLen: '“{label}” must be at most {max} characters.',
      pattern: '“{label}” contains invalid characters.',
      ok: 'OK'
    },
    'zh-tw': {
      dialogTitle: '表單錯誤',
      empty: '「{label}」不可為空白。',
      minLen: '「{label}」至少需 {min} 個字元。',
      maxLen: '「{label}」最多 {max} 個字元。',
      pattern: '「{label}」含有不允許的字元。',
      ok: '確定'
    },
    'zh-cn': {
      dialogTitle: '表单错误',
      empty: '「{label}」不能为空。',
      minLen: '「{label}」至少需要 {min} 个字符。',
      maxLen: '「{label}」最多 {max} 个字符。',
      pattern: '「{label}」包含不允许的字符。',
      ok: '确定'
    }
  };

  // === 語系 ===
  const cookieLang = (typeof getCookie === 'function') ? (getCookie('language') || '') : '';
  const htmlLang   = (document.documentElement.getAttribute('lang') || '');
  const normalizeLang = (raw) => {
    const s = String(raw || '').trim().toLowerCase().replace('_', '-');
    if (!s) return 'en-us';
    if (s.startsWith('en')) return 'en-us';
    if (s.startsWith('zh')) {
      if (/tw|hk|mo|hant|cht/.test(s)) return 'zh-tw';
      return 'zh-cn';
    }
    return 'en-us';
  };
  const lang = normalizeLang(cookieLang || htmlLang);
  const dict = I18N[lang] || I18N['en-us'];

  // === job_name 的語系化標籤覆蓋 ===
  const FIELD_LABEL_I18N = {
    job_name: {
      'en-us': 'job_name',
      'zh-tw': '工作名稱',
      'zh-cn': '工作名称'
    }
  };

  // === 規則 ===
  const conditions = [
    {
      id: 'job_name',
      label: 'Job Name',
      pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/,
      minLength: 1,
      maxLength: 250
    }
  ];

  // === 驗證 ===
  for (const input of conditions) {
    const el = document.getElementById(input.id);
    if (!el) continue;

    // 先取預設/自訂標籤
    let label = el.getAttribute('data-label') || input.label || input.id;
    // 若此欄位有語系化覆蓋，使用覆蓋文字
    if (FIELD_LABEL_I18N[input.id]?.[lang]) {
      label = FIELD_LABEL_I18N[input.id][lang];
    }

    const value = (el.value ?? '').trim();
    el.classList.remove('is-invalid');

    let message = null;
    if (value === '') {
      message = dict.empty.replace('{label}', label);
    } else if (input.maxLength && value.length > input.maxLength) {
      message = dict.maxLen.replace('{label}', label).replace('{max}', input.maxLength);
    } else if (input.minLength && value.length < input.minLength) {
      message = dict.minLen.replace('{label}', label).replace('{min}', input.minLength);
    } else if (input.pattern && !input.pattern.test(value)) {
      message = dict.pattern.replace('{label}', label);
    }

    if (message) {
      el.classList.add('is-invalid');
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => el.focus({ preventScroll: true }), 0);

      alertify
        .alert(dict.dialogTitle, message, () => el.focus())
        .set('movable', false)
        .set('labels', { ok: dict.ok });

      return false;
    }
  }

  return true;
}





function edit_input_check_job() {
    const conditions = [
        { id: 'edit_jobname', pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/, minLength: 1, maxLength: 250 },
    ];
    return validateFormWithDialog(conditions);
}



// 單一共用：第一個錯誤即跳窗 + 紅框 + focus（含多語）
function validateFormWithDialog(conditions) {
  const I18N = {
    'en-us': { dialogTitle:'Form Error', empty:'“{label}” cannot be empty.', minLen:'“{label}” must be at least {min} characters.', maxLen:'“{label}” must be at most {max} characters.', pattern:'“{label}” contains invalid characters.', ok:'OK' },
    'zh-tw': { dialogTitle:'表單錯誤', empty:'「{label}」不可為空白。', minLen:'「{label}」至少需 {min} 個字元。', maxLen:'「{label}」最多 {max} 個字元。', pattern:'「{label}」含有不允許的字元。', ok:'確定' },
    'zh-cn': { dialogTitle:'表单错误', empty:'「{label}」不能为空。', minLen:'「{label}」至少需要 {min} 个字符。', maxLen:'「{label}」最多 {max} 个字符。', pattern:'「{label}」包含不允许的字符。', ok:'确定' }
  };

  // === 語系 ===
  const getCookieSafely = (name) => {
    const m = document.cookie.match(new RegExp('(?:^|;\\s*)' + name + '=([^;]+)'));
    return m ? decodeURIComponent(m[1]) : '';
  };
  const normalizeLang = (raw) => {
    const s = String(raw || '').trim().toLowerCase().replace('_','-');
    if (!s || s.startsWith('en')) return 'en-us';
    if (s.startsWith('zh')) return /tw|hk|mo|hant|cht/.test(s) ? 'zh-tw' : 'zh-cn';
    return 'en-us';
  };
  const cookieLang = getCookieSafely('language');
  const htmlLang   = document.documentElement.getAttribute('lang') || '';
  const lang       = normalizeLang(cookieLang || htmlLang);
  const dict       = I18N[lang] || I18N['en-us'];

  // === 欄位標籤多語覆蓋（只在驗證訊息中使用）===
  const FIELD_LABEL_I18N = {
    job_name:       { 'en-us':'job_name', 'zh-tw':'工作名稱', 'zh-cn':'工作名称' },
    edit_jobname:  { 'en-us':'job_name', 'zh-tw':'工作名稱', 'zh-cn':'工作名称' }
  };

  for (const rule of conditions) {
    const el = document.getElementById(rule.id);
    if (!el) { console.warn(`Element with ID '${rule.id}' not found.`); continue; }

    // 預設 label（可由 data-label 覆蓋）
    let label = el.getAttribute('data-label') || rule.label || rule.id;
    // 若在覆蓋表中，改用多語標籤
    if (FIELD_LABEL_I18N[rule.id]?.[lang]) {
      label = FIELD_LABEL_I18N[rule.id][lang];
    }

    const value = (el.value ?? '').trim();
    el.classList.remove('is-invalid');

    let msg = null;
    if (value === '') {
      msg = dict.empty.replace('{label}', label);
    } else if (rule.maxLength && value.length > rule.maxLength) {
      msg = dict.maxLen.replace('{label}', label).replace('{max}', rule.maxLength);
    } else if (rule.minLength && value.length < rule.minLength) {
      msg = dict.minLen.replace('{label}', label).replace('{min}', rule.minLength);
    } else if (rule.pattern && !rule.pattern.test(value)) {
      msg = dict.pattern.replace('{label}', label);
    }

    if (msg) {
      el.classList.add('is-invalid');
      el.scrollIntoView({ behavior:'smooth', block:'center' });
      setTimeout(() => el.focus({ preventScroll:true }), 0);

      alertify
        .alert(dict.dialogTitle, msg, () => el.focus())
        .set('movable', false)
        .set('labels', { ok: dict.ok });

      return false;
    }
  }
  return true;
}
