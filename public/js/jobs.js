
var oldjobname ='';
var old_jobid  = '';
function cound_job(argument){

    var table = document.getElementById('job_table');
    var selectedRow = table.querySelector('.selected');
    var jobid  = selectedRow ? selectedRow.cells[0].innerText : null;
    oldjobname = selectedRow ? selectedRow.cells[1].innerText : null;
    old_jobid  = selectedRow ? selectedRow.cells[0].innerText : null;
    if(argument == 'del' && jobid != null){
        document.querySelector(".main-content").classList.add("overlay-active");
        delete_jobid(jobid);
    }

    if(argument =="edit" && jobid != null){
        document.querySelector(".main-content").classList.add("overlay-active");
        edit_job(jobid);
    }

    if(argument =="new"){
        document.querySelector(".main-content").classList.add("overlay-active");
        create_job();
    }

    if(argument =="copy" && jobid != null){
        document.querySelector(".main-content").classList.add("overlay-active");
        copy_job(jobid);
    }

}

function readFromLocalStorage(key) {
    return localStorage.getItem(key);
}

function create_job() {
    
    //帶入預設值
    document.getElementById('newjob').style.display = 'block';
    document.getElementById('job_off').checked = true;
    document.getElementById('stop_job_ok_off').checked = true;
}

function copy_job(jobid){
    var new_jobid = document.getElementById("to_job_id").value;
    var new_jobname = document.getElementById("to_job_name").value;

    document.getElementById("from_job_id").value = old_jobid;
    document.getElementById("from_job_name").value = oldjobname;
    document.getElementById("to_job_id").value = new_jobid;
    
    document.getElementById('copyjob').style.display = 'block';
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

function validateJobNameInput(id) {
    const element = document.getElementById(id);
    const value = element?.value?.trim() || "";
    const pattern = /^[a-zA-Z0-9\u4E00-\u9FA5\-]+$/;

    let isValid = true;

    if (value === "") {
        isValid = false;
    } else if (!pattern.test(value)) {
        isValid = false;
    }

    if (!isValid) {
        element.classList.add("is-invalid");
    } else {
        element.classList.remove("is-invalid");
    }

    return isValid;
}



function input_check() {
    return validateJobNameInput("job_name");
}

function edit_input_check() {
    return validateJobNameInput("edit_jobname");
}

