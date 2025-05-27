
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
        alertify.warning("Please select a job first.");
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



function input_check_job() {
    const conditions = [
        { id: 'job_name', pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/, minLength: 1, maxLength: 250 },
    ];
    return validate_form_inputs(conditions);
}

function edit_input_check_job() {
    const conditions = [
        { id: 'edit_jobname', pattern: /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/, minLength: 1, maxLength: 250 },
    ];
    return validate_form_inputs(conditions);
}


function validate_form_inputs(conditions) {
    let isFormValid = true;

    conditions.forEach(function(input) {
        const element = document.getElementById(input.id);
        if (!element) {
            console.warn(`Element with ID '${input.id}' not found.`);
            return;
        }

        const value = element.value.trim();

        // 字數限制錯誤提示 (可搭配顯示)
        if (input.maxLength && value.length > input.maxLength) {
            element.classList.add("is-invalid");
            isFormValid = false;
            return;
        }

        if (input.minLength && value.length < input.minLength) {
            element.classList.add("is-invalid");
            isFormValid = false;
            return;
        }

        // pattern 驗證
        if (value === "" || (input.pattern && !input.pattern.test(value))) {
            element.classList.add("is-invalid");
            isFormValid = false;
            return;
        }

        // 如果通過所有驗證，移除紅框
        element.classList.remove("is-invalid");
    });

    return isFormValid;
}
