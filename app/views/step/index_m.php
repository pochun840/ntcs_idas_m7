
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_step_m.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <header id="header">
 	        <h3> <?php echo $text['step_management'];?> </h3>
        </header>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:3vmin;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="5" maxlength="20" value="<?php echo $data['job_id'];?>" disabled
                style="height:28px; font-size:3vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:3vmin;color: #000; padding-left: 2%" for="seq_id"><?php echo $text['seq_id'];?> :</label>&nbsp;
                <input type="text" id="seq_id" name="seq_id" size="5" maxlength="20" value="1" disabled
                style="height:28px; font-size:3vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <button id="back_btn" type="button" onclick="goToPage()"><?php echo $text['return'];?></button>
            </div>

            <div class="table-container">
                <table id="step_table" class="table w3-table-all w3-hoverable">
                    <thead id="header-table" style="2.5vmin">
                        <tr class="w3-dark-grey">
                            <th><?php echo $text['step_id'];?></th>
                            <th><?php echo $text['step_target_type'];?></th>
                            <th><?php echo $text['direction'];?></th>
                            <th><?php echo $text['rpm'];?></th>
                            <th><?php echo $text['up'];?></th>
                            <th><?php echo $text['down'];?></th>
                        </tr>
                    </thead>

                    <tbody style="font-size: 2vmin;text-align: center;">
                  
                        <?php foreach($data['step'] as $key =>$val){?>
                            <tr>
                                <td><?php echo $val['StepSelect'];?></td>
                                <td><?php echo $text[$data['target_option'][$val['StepOption']]];?></td>
                                <td><?php echo $text[$data['direction'][$val['StepDirection']]];?></td>
                                <td><?php echo $val['StepRPM'];?></td>
                                <td><img src="./img/btn_up.png" onclick="MoveUp(this);"></td>
                                <td><img src="./img/btn_down.png"onclick="MoveDown(this);"></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="footer">
        <div id="TotalPage">
            <div id="TotalStepTable">
                <div style="color:black; float: right; margin: 2px"><?php echo $text['total_step'];?> :
                    <label id="RecordCnt" name="RecordCnt" type="text" style="margin-right: 20px"><?php echo count($data['step']);?></label>
                </div>
            </div>
        </div>

        <div class="buttonbox">
            <?php $status = count($data['step']) == 4 ? 'disabled' : ''; ?>
            <input id="S3" name="Step_Manager_Submit" type="button" value="<?php echo $text['New'];?>"    tabindex="1"  onclick="cound_step('new');" <?php echo $status; ?>>
            <input id="S6" name="Step_Manager_Submit" type="button" value="<?php echo $text['Edit'];?>"   tabindex="1" onclick="cound_step('edit')">
            <input id="S5" name="Step_Manager_Submit" type="button" value="<?php echo $text['Copy'];?>"   tabindex="1"  onclick="cound_step('copy');" <?php echo $status; ?>>
            <input id="S4" name="Step_Manager_Submit" type="button" value="<?php echo $text['Delete'];?>" tabindex="1" onclick="cound_step('del');" >
        </div>
    </div>

    <!-- Copy Step -->
    <div id="copystep" class="modal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content w3-animate-zoom" style="width: 60%">
                <header class="w3-container modal-header">
                    <span onclick="document.getElementById('copystep').style.display='none'"
                        class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                    <h3 id='modal_title'><?php echo $text['copy_step'];?></h3>
                </header>

                <div class="modal-body">
                    <form id="new_step_form">
        	            <label for="from_step_id" class="col col-form-label" style="font-weight: bold"><?php echo $text['copy_from'];?></label>
        	            <div style="padding-left: 10%">
        		            <div class="row">
        				        <label for="from_step_id" class="t1 col-4 col-form-label"><?php echo $text['step_id'];?> :</label>
        				        <div class="col-5 t2 ">
        				            <input type="number" class="form-control" id="from_step_id" disabled>
        				        </div>
        				    </div>
        			    </div>

        			    <label for="from_step_id" class="col col-form-label" style="font-weight: bold"><?php echo $text['copy_to'];?></label>
        			    <div style="padding-left: 10%">
        				    <div class="row">
        				        <label for="to_step_id" class="t1 col-4 col-form-label"><?php echo $text['step_id'];?> :</label>
        				        <div class="t2 col-5">
        				            <input type="number" class="form-control" id="to_step_id">
        				        </div>
        				    </div>
        			    </div>
        			  </form>
                </div>

                <div class="modal-footer justify-content-center">
                    <button id="copyButton" class="button-modal" onclick="copy_step_by_id_ajax()" ><?php echo $text['save'];?></button>
                    <button id="" class="button-modal" onclick="document.getElementById('copystep').style.display='none'" class="closebtn"><?php echo $text['close'];?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    highlight_row('step_table');
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

var modal = document.getElementById('newstep');
let job_id;
let seq_id;
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

var rows = document.getElementsByTagName("tr");
var stepid = '';
for (var i = 0; i < rows.length; i++) {
    (function(row) {
        var cells = row.getElementsByTagName("td");
        if (cells.length > 0) {
            cells[0].addEventListener("click", function() {
                stepid   = cells[0] ? (cells[0].textContent || cells[0].innerText) : null;
                localStorage.setItem("stepid", stepid);
            });
        }
    })(rows[i]);
}


function cound_step(argument){

    var table = document.getElementById('step_table');
    var selectedRow = table.querySelector('.selected');
    var selectedRowData = selectedRow ? selectedRow.cells[0].innerText : null;

    job_id ='<?php echo $data['job_id']?>';
    seq_id ='<?php echo $data['seq_id']?>';

    stepid = selectedRowData;
    if(argument == 'del'){
        del_stepid(stepid);
    }

    if(argument =="copy" && stepid != null){
        copy_step(stepid);
    }

    if(argument =="new"){
        var step_count = countrows();
        if(step_count  < 4){
            create_step();
        }
    }

    if(argument =="edit" && stepid != null){
        edit_step(stepid);
    }

}




function edit_step_save() {

    var jobid = '<?php echo $data['job_id']?>';
    var seqid = '<?php echo $data['seq_id']?>';
    var target_option = document.getElementById("edit_target_option").value;

    var target_torque = 0;
    var target_angle = 0;
    var target_delaytime = 0;
    var hi_torque = 0;
    var lo_torque = 0;
    var hi_angle = 0;
    var lo_angle = 0;
    var rpm = 0;
    var direction = 0;
    var downshift = 0;
    var threshold_torque = 0;
    var downshift_torque = 0;
    var downshift_speed = 0;

    if(target_option == 2) {
        target_delaytime = document.getElementById("edit_target_delaytime").value;
    }else if(target_option == 1) {
        target_angle = document.getElementById("edit_target_angle").value;
    }else{
        target_torque = document.getElementById("edit_target_torque").value;
    }      

    hi_torque = document.getElementById("edit_hi_torque").value;
    lo_torque = document.getElementById("edit_lo_torque").value;
    hi_angle = document.getElementById("edit_hi_angle").value;
    lo_angle = document.getElementById("edit_lo_angle").value;
    rpm = document.getElementById("edit_rpm").value;
    direction = document.querySelector('input[name="edit_direction_option"]:checked').value;
    downshift = document.querySelector('input[name="edit_downshift_option"]:checked').value;
    threshold_torque = document.getElementById("edit_downshift_threshold").value;
    downshift_torque = document.getElementById("edit_downshift_torque").value;
    downshift_speed = document.getElementById("edit_downshift_speed").value;
    

    var requestData = {
        jobid: jobid,
        seqid: seqid,
        stepid: stepid,
        target_option: target_option,
        target_torque: target_torque,
        target_angle: target_angle,
        target_delaytime: target_delaytime,
        hi_torque: hi_torque,
        lo_torque: lo_torque,
        hi_angle: hi_angle,
        lo_angle: lo_angle,
        rpm: rpm,
        direction: direction,
        downshift: downshift,
        threshold_torque: threshold_torque,
        downshift_torque: downshift_torque,
        downshift_speed: downshift_speed
    };

    if (target_option) {
        $.ajax({
            url: "?url=Step/edit_step",
            method: "POST",
            data: requestData,
            success: function(response) {
                console.log(response);
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    history.go(0);
                });
            },
            error: function(xhr, status, error) {

            }
        });
    }
}



function add_step(){

    var jobid = '<?php echo $data['job_id']?>';
    var seqid = '<?php echo $data['seq_id']?>';
    var stepid = '<?php echo $data['stepid_new']?>';

    var target_option = document.getElementById('target_option').value;
    var target_torque = document.getElementById('target_torque').value;

    var hi_torque = document.getElementById('hi_torque').value;
    var lo_torque = document.getElementById('lo_torque').value;

    var hi_angle = document.getElementById('hi_angle').value;
    var lo_angle = document.getElementById('lo_angle').value;
    var rpm = document.getElementById('rpm').value;

    var threshold_torque = document.getElementById('downshift_threshold').value;
    var downshift_torque = document.getElementById('downshift_torque').value;
    var downshift_rpm  = document.getElementById('downshift_rpm').value;

    var direction = document.querySelector('input[name="direction_option"]:checked').value;
    var downshift = document.querySelector('input[name="downshift_option"]:checked').value;


    if(target_torque){

        $.ajax({
            url: "?url=Step/create_step",
            method: "POST",
            data:{ 
                jobid: jobid,
                seqid: seqid,
                stepid: stepid,
                target_option: target_option,
                target_torque: target_torque,
                hi_torque: hi_torque,
                lo_torque: lo_torque,
                hi_angle: hi_angle,
                lo_angle: lo_angle,
                rpm: rpm,
                direction: direction,
                downshift: downshift,
                threshold_torque: threshold_torque,
                downshift_torque: downshift_torque,
                downshift_speed: downshift_speed

            },
            success: function(response) {

                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    history.go(0);
                });
                
            },
            error: function(xhr, status, error) {
                
            }
        });
    }
}

function copy_step_by_id(){
    var jobid = '<?php echo $data['job_id']?>';
    var seqidnew = '<?php echo $data['stepid_new']?>';

    document.getElementById('from_step_id').value = stepid;    
    document.getElementById("to_step_id").value = seqidnew;


}

function copy_step_by_id_ajax(){

    var jobid = '<?php echo $data['job_id']?>';
    var seqid = '<?php echo $data['seq_id']?>';
    var stepid_new  = '<?php echo $data['stepid_new']?>';

    if(stepid_new){
        $.ajax({
            url: "?url=Step/copy_step",
            method: "POST",
            data:{ 
                jobid: jobid,
                seqid: seqid,
                stepid:stepid,
                stepid_new: stepid_new
            },
            success: function(response) {
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    history.go(0);
                });
            },
            error: function(xhr, status, error) {
                
            }
        });
    }
}


function del_stepid(step_id){
    var jobid = '<?php echo $data['job_id']?>';
    var seqid = '<?php echo $data['seq_id']?>';
    if(stepid) {

        $.ajax({
            url: "?url=Step/delete_step",
            method: "POST",
            data:{ 
                stepid:stepid,
                jobid:jobid,
                seqid:seqid
            },
            success: function(response){
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    history.go(0);
                }); 
            },
            error: function(xhr, status, error) {
                
            }
        });

    }

}


var rowInfoArray = [];
<?php foreach($data['step'] as $key =>$val) {?>
    var JOBID = "<?php echo $val['JOBID'];?>";
    var SEQID= "<?php echo $val['SEQID'];?>";
    var StepSelect = "<?php echo $val['StepSelect'];?>";
      
    
    var rowInfo = {
        JOBID: JOBID,
        SEQID: SEQID,
        StepSelect: StepSelect,
    };
    
    rowInfoArray.push(rowInfo);
<?php } ?>

function sendRowInfoArray() {
    var jobid = '<?php echo $data['job_id']?>';
    var dataToSend = {
        jobid: jobid,
        rowInfoArray: rowInfoArray
    };

    if(rowInfoArray){

        $.ajax({
            url: "?url=Step/adjustment_order", 
            method: "POST",
            data: dataToSend,
            success: function(response) {
                history.go(0); 
            },
            error: function(xhr, status, error) {
                console.error('Error sending data:', error);
            }
        });
    }
}


function countrows() {
    var tbody = document.querySelector('#step_table tbody');
    var rows = tbody.querySelectorAll('tr');
    var rowCount = rows.length;
    console.log("共有 " + rowCount + " 行");
    return rowCount;
}


function goToPage() {
    var seq_id = '<?php echo $data['seq_id'];?>';
    var url = '?url=Sequences/index/' + seq_id;
    window.location.href = url;
}


function create_step() {
    var job_id = '<?php echo $data['job_id'];?>';    
    var seq_id = '<?php echo $data['seq_id'];?>';
    window.location.href = '../public/?url=Step/variation/' + job_id + '/' + seq_id; 
}

function edit_step(){
    var job_id = '<?php echo $data['job_id'];?>';    
    var seq_id = '<?php echo $data['seq_id'];?>';
    window.location.href = '../public/?url=Step/variation/' + job_id + '/' + seq_id + '/' + stepid; 
}

</script>