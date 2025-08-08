<script>
$(document).ready(function () {
    highlight_row('job_table');
});

document.addEventListener('DOMContentLoaded', function() {
  var observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      var headerElements = document.querySelectorAll('.ajs-header');
      headerElements.forEach(function(headerElement) {
        headerElement.parentNode.removeChild(headerElement);
      });
    });
  });

  observer.observe(document.body, { childList: true, subtree: true });
});

// Get the modal
var modal = document.getElementById('newjob');
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
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
    var jobidnew = '<?php echo $data['next_job_id']?>';
    var jobname_val = document.getElementById("job_name").value;
  
    var jobElement = document.querySelector('input[name="job_ok"]:checked');
    var job_ok_val = jobElement ? jobElement.value : null;

    var stopjobokElement = document.querySelector('input[name="stop_job_ok"]:checked');
    var stop_job_ok_val = stopjobokElement ? stopjobokElement.value : null;

    let check = input_check_job();
    if(check){
        document.getElementById('spinner').style.display = 'block';
        $.ajax({
            url: "?url=Jobs/create_job",
            method: "POST",
            data: { 
                jobidnew: jobidnew,
                jobname_val: jobname_val,
                job_ok_val: job_ok_val,
                stop_job_ok_val: stop_job_ok_val
            },
            success: function(response) {
                success_response(response, 'spinner', true); // 自動關閉
            },
            error: function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
            }
        });
    }


}

//編輯JOB 
function updatejob(){

    var jobid      = document.getElementById("edit_jobid").value;
    var jobname    = document.getElementById("edit_jobname").value;
    var jobokValue = document.querySelector('input[name="edit_job_ok"]:checked').value;
    var stopjobValue = document.querySelector('input[name="edit_stop_job_ok"]:checked').value;

    let check_edit = edit_input_check_job();

    if(check_edit) {
        document.getElementById('spinner').style.display = 'block';

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
               success_response(response, 'spinner', true); // 自動關閉

            },
            error: function(xhr, status, error) {
                
            }
        });

    }
   
}

//刪除 JOB
function delete_jobid(jobid) {
    if (!jobid) return;

    // 根據語言顯示訊息（可擴充）
    var lang = getCookie('language');
    var confirmTitle = "Delete Job";
    var confirmMsg = "Are you sure to delete this job?";
    if (lang === "zh-tw") {
        confirmTitle = "刪除工作";
        confirmMsg = "你確定要刪除此工作嗎？";
    } else if (lang === "zh-cn") {
        confirmTitle = "删除工作";
        confirmMsg = "你确定要删除此工作吗？";
    }

    // 顯示確認視窗
    alertify.confirm(confirmTitle, confirmMsg, function (ok) {
        if (ok) {
            document.getElementById('spinner').style.display = 'block';


            $.ajax({
                url: "?url=Jobs/delete_jobid",
                method: "POST",
                data: { jobid: jobid },
                success: function (response) {
                   success_response(response, 'spinner', true); // 自動關閉
                },
                error: function (xhr, status, error) {
                    alertify.error("Delete request failed.");
                }
            });
        }
    }, function () {
        document.querySelector(".main-content").classList.remove("overlay-active");
    });
}


//複製複製 JOB
function copy_job_by_id(jobid) {
    const new_jobid = document.getElementById("to_job_id")?.value.trim();
    const new_jobname = document.getElementById("to_job_name")?.value.trim();
    const from_jobid = old_jobid ?? jobid;
    const from_jobname = oldjobname ?? '';

    // 設定 hidden 欄位
    document.getElementById("from_job_id").value = from_jobid;
    document.getElementById("from_job_name").value = from_jobname;
    document.getElementById("to_job_id").value = new_jobid;

    if (!new_jobid || !new_jobname) {
        alertify.error("New job ID and name are required.");
        return;
    }

    // 多語系文字
    const lang = getCookie('language');
    let confirmText = "Are you sure?";
    let confirmTitle = "Copy Job";
    if (lang === "zh-cn") {
        confirmText = "你确定吗？";
        confirmTitle = "复制作业";
    } else if (lang === "zh-tw") {
        confirmText = "你確定嗎？";
        confirmTitle = "複製作業";
    }

    // 先檢查 job 類型
    $.ajax({
        url: "?url=Jobs/check_job_type",
        method: "POST",
        data: { new_jobid: new_jobid },
        success: function(response) {
            alertify.confirm(confirmTitle, confirmText, function(result) {
                if (!result) {
                    alertify.message("Cancelled");
                    return;
                }

                // ✅ 顯示 spinner 與遮罩
                document.querySelector(".main-content").classList.add("overlay-active");
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
                    success: function(response) {
                        success_response(response, 'spinner', true); // 自動關閉 + 刷新
                    },
                    error: function(xhr, status, error) {
                        document.getElementById("spinner").style.display = "none";
                        document.querySelector(".main-content").classList.remove("overlay-active");
                        alertify.error("Failed to copy job data.");
                        console.error("Copy error:", error);
                    }
                });
            }, function() {
                // 使用者取消 → 移除遮罩
                document.querySelector(".main-content").classList.remove("overlay-active");
            });
        },
        error: function(xhr, status, error) {
            alertify.error("Job type check failed.");
            console.error("Check job type error:", error);
        }
    });
}
</script>