
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
        //alertify.warning("Please select a job first.");
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
            alertify.error("Unknown action: " + action);
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

                
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
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

  // 語系
  const cookieMatch = document.cookie.match(/(?:^|;\s*)lang=([^;]+)/i);
  const htmlLang = (document.documentElement.getAttribute('lang') || '').toLowerCase();
  const lang = (cookieMatch ? cookieMatch[1].toLowerCase() : htmlLang) || 'en-us';
  const dict = I18N[lang] || I18N['en-us'];

  // 規則
  const conditions = [
    {
      id: 'job_name',
      label: 'Job Name',
      pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/,
      minLength: 1,
      maxLength: 250
    }
  ];

  // 驗證
  for (const input of conditions) {
    const el = document.getElementById(input.id);
    if (!el) continue;
    const label = el.getAttribute('data-label') || input.label || input.id;
    const value = (el.value ?? '').trim();

    // reset
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

      return false; // 停在第一個錯誤
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

  // 取語系：cookie lang > <html lang> > en-us
  const cookieMatch = document.cookie.match(/(?:^|;\s*)lang=([^;]+)/i);
  const htmlLang = (document.documentElement.getAttribute('lang') || '').toLowerCase();
  const lang = (cookieMatch ? cookieMatch[1].toLowerCase() : htmlLang) || 'en-us';
  const dict = I18N[lang] || I18N['en-us'];

  for (const rule of conditions) {
    const el = document.getElementById(rule.id);
    if (!el) {
      console.warn(`Element with ID '${rule.id}' not found.`);
      continue;
    }

    const label = el.getAttribute('data-label') || rule.label || rule.id;
    const value = (el.value ?? '').trim();

    // reset 樣式
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
      el.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(() => el.focus({ preventScroll: true }), 0);

      alertify
        .alert(dict.dialogTitle, msg, () => el.focus())
        .set('movable', false)
        .set('labels', { ok: dict.ok });

      return false; // 第一個錯誤即返回
    }
  }

  return true; // 全部通過
}
