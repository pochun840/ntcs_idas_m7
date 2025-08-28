<script>
(function () {
  // 1) 套用 Alertify 多語系（全域）
  var lang = (typeof getCookie === 'function' && getCookie('language')) || 'en-us';
  if (typeof applyAlertifyI18n === 'function') {
    applyAlertifyI18n(lang);
  } else if (window.alertify && alertify.defaults && alertify.defaults.glossary) {
    // fallback（沒載到 helper 時）
    var gl = { 'zh-tw': {ok:'確定',cancel:'取消'}, 'zh-cn': {ok:'确定',cancel:'取消'}, 'en-us': {ok:'OK',cancel:'Cancel'} };
    var key = (lang || 'en-us').toLowerCase();
    if (key === 'en') key = 'en-us';
    var labels = gl[key] || gl['en-us'];
    alertify.defaults.glossary.ok = labels.ok;
    alertify.defaults.glossary.cancel = labels.cancel;
  }

  // 2) 用 CSS 隱藏 alertify 標題，取代 MutationObserver
  (function injectHideAlertifyHeader() {
    var id = 'hide-alertify-header-style';
    if (!document.getElementById(id)) {
      var s = document.createElement('style');
      s.id = id;
      s.textContent = '.ajs-header{display:none!important;}';
      document.head.appendChild(s);
    }
  })();

  // 3) 高亮列
  if (typeof highlight_row === 'function') {
    highlight_row('job_table');
  }

  // 4) Modal 關閉（點背景或按 ESC）
  var modal = document.getElementById('newjob');
  if (modal) {
    document.addEventListener('click', function (e) {
      if (e.target === modal) modal.style.display = 'none';
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') modal.style.display = 'none';
    });
  }

  // 5) 若日後語系有切換，可呼叫這個重新套用
  window.refreshAlertifyI18n = function () {
    var l = (typeof getCookie === 'function' && getCookie('language')) || 'en-us';
    if (typeof applyAlertifyI18n === 'function') applyAlertifyI18n(l);
  };
})();
</script>

<script>    

var jobid ='';
var old_jobname = '';
var rows = document.getElementsByTagName("tr");
for (var i = 0; i < rows.length; i++) {
    (function(row) {
        var cells = row.getElementsByTagName("td");
        if (cells.length > 0) {
            cells[0].addEventListener("click", function() {
           
                var jobid = cells[0] ? (cells[0].textContent || cells[0].innerText) : null;
                var secondCellValue = cells[1] ? (cells[1].textContent || cells[1].innerText) : null;
                var thirdCellValue = cells[2] ? (cells[2].textContent || cells[2].innerText) : null;
                var rpmvalue = cells[3] ? (cells[3].textContent || cells[3].innerText) : null;
                var powervalue = cells[4] ? (cells[4].textContent || cells[4].innerText) : null;
                jobid = jobid;
                old_jobname = secondCellValue;

            });
        }
    })(rows[i]);
}

//新增JOB
function savejob() {
  // 可選：若語系可能剛被切換，保險再刷新一次
  if (typeof refreshAlertifyI18n === 'function') refreshAlertifyI18n();

  const lang = (getCookie('language') || 'en-us').toLowerCase();
  const jobidnew     = '<?php echo $data['next_job_id']?>';
  const jobname_val  = document.getElementById("job_name")?.value?.trim() || '';
  const job_ok_val   = document.querySelector('input[name="job_ok"]:checked')?.value ?? null;
  const stop_job_ok_val = document.querySelector('input[name="stop_job_ok"]:checked')?.value ?? null;

  if (!validateJobForm()) return;

  // 顯示遮罩＋spinner
  document.querySelector('.main-content')?.classList.add('overlay-active');
  document.getElementById('spinner').style.display = 'block';

  $.ajax({
    url: "?url=Jobs/create_job",
    method: "POST",
    data: {
      jobidnew,
      jobname_val,
      job_ok_val,
      stop_job_ok_val
    },
    success: function(response) {
      // 交給你原本封裝，會自動關閉 spinner/overlay 並彈窗（OK 文字已全域套好）
      success_response(response, 'spinner', true);
    },
    error: function(xhr, status, error) {
      const errMsg =
        lang === 'zh-tw' ? ('新增失敗：' + error) :
        lang === 'zh-cn' ? ('新增失败：' + error) :
                           ('Create failed: ' + error);
      alertify.error(errMsg);
      document.querySelector('.main-content')?.classList.remove('overlay-active');
      document.getElementById('spinner').style.display = 'none';
    }
  });
}



//編輯JOB 
function updatejob() {
  if (typeof refreshAlertifyI18n === 'function') refreshAlertifyI18n();

  const lang          = (getCookie('language') || 'en-us').toLowerCase();
  const jobid         = document.getElementById("edit_jobid")?.value;
  const jobname       = document.getElementById("edit_jobname")?.value?.trim() || '';
  const jobokValue    = document.querySelector('input[name="edit_job_ok"]:checked')?.value ?? null;
  const stopjobValue  = document.querySelector('input[name="edit_stop_job_ok"]:checked')?.value ?? null;

  if (!edit_input_check_job()) return;

  document.querySelector('.main-content')?.classList.add('overlay-active');
  document.getElementById('spinner').style.display = 'block';

  $.ajax({
    url: "?url=Jobs/update_job",
    method: "POST",
    data: { jobid, jobname, jobokValue, stopjobValue },
    success: function(response) {
      success_response(response, 'spinner', true); // 自動關閉＋刷新
    },
    error: function(xhr, status, error) {
      const errMsg =
        lang === 'zh-tw' ? ('更新失敗：' + error) :
        lang === 'zh-cn' ? ('更新失败：' + error) :
                           ('Update failed: ' + error);
      alertify.error(errMsg);
      document.querySelector('.main-content')?.classList.remove('overlay-active');
      document.getElementById('spinner').style.display = 'none';
    }
  });
}



//刪除 JOB
function delete_jobid(jobid) {
  if (!jobid) return;

  var lang = (getCookie('language') || 'en-us').toLowerCase();
  var title = 'Delete Job';
  var text  = 'Are you sure you want to delete this job?';
  var errMsg = 'Delete request failed.';

  if (lang === 'zh-tw') { title='刪除工作'; text='你確定要刪除此工作嗎？'; errMsg='刪除失敗，請稍後再試！'; }
  else if (lang === 'zh-cn') { title='删除工作'; text='你确定要删除此工作吗？'; errMsg='删除失败，请稍后再试！'; }

  alertify.confirm(title, text,
    function onOk() {
      document.querySelector('.main-content')?.classList.add('overlay-active');
      document.getElementById('spinner').style.display = 'block';

      $.ajax({
        url: "?url=Jobs/delete_jobid",
        method: "POST",
        data: { jobid },
        success: function (response) {
          success_response(response, 'spinner', true);
        },
        error: function () {
          alertify.error(errMsg);
          document.querySelector('.main-content')?.classList.remove('overlay-active');
          document.getElementById('spinner').style.display = 'none';
        }
      });
    },
    function onCancel() {
      document.querySelector('.main-content')?.classList.remove('overlay-active');
    }
  ); // 不再需要 .set('labels', …)
}




function copy_job_by_id(jobid) {
  const new_jobid   = document.getElementById("to_job_id")?.value.trim();
  const new_jobname = document.getElementById("to_job_name")?.value.trim();
  const from_jobid  = (typeof old_jobid !== 'undefined' && old_jobid != null) ? old_jobid : jobid;
  const from_jobname= (typeof oldjobname !== 'undefined') ? oldjobname : '';

  // 設定 hidden 欄位
  document.getElementById("from_job_id").value = from_jobid;
  document.getElementById("from_job_name").value = from_jobname;
  document.getElementById("to_job_id").value   = new_jobid;

  // 語系
  const lang = (getCookie('language') || 'en-us').toLowerCase();
  const i18n = (function () {
    if (lang === 'zh-tw') {
      return {
        title: '複製作業',
        text: '你確定要複製該工作嗎？',
        needBoth: '請輸入新工作的編號與名稱。',
        sameJob: '目標工作不可與來源相同。',
        precheckFail: '工作類型預檢失敗。',
        copyFail: '複製工作失敗。',
        cancelled: '已取消'
      };
    } else if (lang === 'zh-cn') {
      return {
        title: '复制工作',
        text: '你确定要复制该工作吗？',
        needBoth: '请输入新工作的编号与名称。',
        sameJob: '目标工作不可与来源相同。',
        precheckFail: '工作类型预检失败。',
        copyFail: '复制工作失败。',
        cancelled: '已取消'
      };
    }
    return {
      title: 'Copy Job',
      text: 'Are you sure you want to copy this job?',
      needBoth: 'New job ID and name are required.',
      sameJob: 'Target job cannot be the same as the source.',
      precheckFail: 'Job type check failed.',
      copyFail: 'Failed to copy job data.',
      cancelled: 'Cancelled'
    };
  })();

  // 已有全域 alertify i18n，這裡不用再 set('labels')
  if (typeof refreshAlertifyI18n === 'function') refreshAlertifyI18n();

  // 基本檢查
  if (!new_jobid || !new_jobname) {
    alertify.error(i18n.needBoth);
    return;
  }
  if (String(new_jobid) === String(from_jobid)) {
    alertify.error(i18n.sameJob);
    return;
  }

  // 先檢查 job 類型
  $.ajax({
    url: "?url=Jobs/check_job_type",
    method: "POST",
    data: { new_jobid },
    success: function () {
      alertify.confirm(
        i18n.title,
        i18n.text,
        function onOk() {
          // 顯示 spinner 與遮罩
          document.querySelector(".main-content")?.classList.add("overlay-active");
          document.getElementById("spinner").style.display = "block";

          // 執行複製
          $.ajax({
            url: "?url=Jobs/copy_job_data",
            method: "POST",
            data: {
              old_jobid: from_jobid,
              old_jobname: from_jobname,
              new_jobid: new_jobid,
              new_jobname: new_jobname
            },
            success: function (response) {
              success_response(response, 'spinner', true); // 自動關閉 + 刷新
            },
            error: function (xhr, status, error) {
              document.getElementById("spinner").style.display = "none";
              document.querySelector(".main-content")?.classList.remove("overlay-active");
              alertify.error(i18n.copyFail);
              console.error("Copy error:", error);
            }
          });
        },
        function onCancel() {
          // 取消 → 移除遮罩
          document.querySelector(".main-content")?.classList.remove("overlay-active");
          alertify.message(i18n.cancelled);
        }
      );
    },
    error: function () {
      alertify.error(i18n.precheckFail);
    }
  });
}


</script>