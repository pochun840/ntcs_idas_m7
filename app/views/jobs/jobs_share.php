<script>

    //新增 job
    function savejob() {
        var jobidnew = '<?php echo $data['jobint']?>';
        var jobname_val = document.getElementById("job_name").value;
        var jobElement = document.querySelector('input[name="job_ok"]:checked');
        var job_ok_val = jobElement ? jobElement.value : null;

        var stopjobokElement = document.querySelector('input[name="stop_job_ok"]:checked');
        var stop_job_ok_val = stopjobokElement ? stopjobokElement.value : null;

        let check = input_check();
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
                    success_response(response);
                },
                error: function(xhr, status, error) {
                    console.error("AJAX request failed:", status, error);
                }
            });
        }


    }


    //編輯job
    function updatejob(){

        var jobid      = document.getElementById("edit_jobid").value;
        var jobname    = document.getElementById("edit_jobname").value;
        var jobokValue = document.querySelector('input[name="edit_job_ok"]:checked').value;
        var stopjobValue = document.querySelector('input[name="edit_stop_job_ok"]:checked').value;

        let check_edit = edit_input_check();

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
                    success_response(response);
                },
                error: function(xhr, status, error) {
                    
                }
            });

        }

    }


    //複製JOB
    function copy_job_by_id(jobid) {

        document.getElementById("from_job_id").value = old_jobid;
        document.getElementById("from_job_name").value = oldjobname;
        //document.getElementById("to_job_id").value = new_jobid;


        var new_jobid = document.getElementById("to_job_id").value.trim();
        var new_jobname = document.getElementById("to_job_name").value.trim();

        if (!new_jobid || !new_jobname) {
            //alertify.error("Please fill in all required fields.");
            return;
        }

        // 多語言訊息
        var language = getCookie('language');
        var confirm_text = (language === "zh-cn") ? "你确定要复制这个工作吗？" :
                        (language === "zh-tw") ? "你確定要複製這個工作嗎？" :
                        "Are you sure you want to copy this job?";

        alertify.confirm(confirm_text, function (result) {
            /*if (!result) {
                //alertify.error("Cancelled");
                return;
            }*/

            // 顯示灰階與 loading
            document.querySelector(".main-content").classList.add("overlay-active");
            document.getElementById("spinner").style.display = 'block';

            // 確認新 job id 合法性
            $.ajax({
                url: "?url=Jobs/check_job_type",
                method: "POST",
                data: { new_jobid: new_jobid },
                success: function (response) {
                    // 開始複製資料
                    $.ajax({
                        url: "?url=Jobs/copy_job_data",
                        method: "POST",
                        data: {
                            old_jobid: old_jobid,
                            old_jobname: oldjobname,
                            new_jobid: new_jobid,
                            new_jobname: new_jobname
                        },
                        success: function (response) {

                            handleAjaxResponseWithSpinner(response); 
                            document.getElementById('copyjob').style.display = 'none';
                           
                        },
                        error: function () {
                            alertify.error("Copy failed.");
                            document.querySelector(".main-content").classList.remove("overlay-active");
                            document.getElementById("spinner").style.display = 'none';
                        }
                    });
                },
                error: function () {
                    alertify.error("Check job ID failed.");
                    document.querySelector(".main-content").classList.remove("overlay-active");
                    document.getElementById("spinner").style.display = 'none';
                }
            });
        });
    }



    //刪除job_id
    function delete_jobid(jobid) {
        if (!jobid) return;

        var language = getCookie('language');
        var text_info, title;

        if (language === "zh-cn") {
            text_info = '你确定要删除这个工作吗？';
            title = '删除作业';
        } else if (language === "zh-tw") {
            text_info = '你確定要刪除這個工作嗎？';
            title = '刪除作業';
        } else {
            text_info = 'Are you sure you want to delete this job?';
            title = 'Delete Job';
        }

        alertify.confirm(title, text_info, function () {
            // 點擊確認才會執行 AJAX
            document.querySelector(".main-content").classList.add("overlay-active");
            document.getElementById("spinner").style.display = 'block';

            $.ajax({
                url: "?url=Jobs/delete_jobid",
                method: "POST",
                data: { jobid: jobid },
                success: function (response) {
                    handleAjaxResponseWithSpinner(response); // 假設你有處理成功回傳的方法
                },
                error: function (xhr, status, error) {
                    alertify.error("Delete failed: " + error);
                    document.querySelector(".main-content").classList.remove("overlay-active");
                    document.getElementById("spinner").style.display = 'none';
                }
            });

        }, function () {
            // 使用者點取消時什麼都不做
        });
    }


</script>