
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_jobs.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['job_management'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="table-container">
                <div class="scrollbar" id="style-jobtable">
                    <div class="scrollbar-force-overflow">
                        <table id="job_table"  class="table w3-table-all w3-hoverable">
                            <thead id="header-table">
                                <tr class="w3-dark-grey">
                                    <th><?php echo $text['job_id'];?></th>
                                    <th><?php echo $text['job_name'];?></th>
                                    <th><?php echo $text['reverse_direction'];?></th>
                                    <th><?php echo $text['reverse_rpm'];?></th>
                                    <th><?php echo $text['reverse_power'];?></th>
                                    <th><?php echo $text['total_seq'];?></th>
                                    <th><?php echo $text['add_seq'];?></th>
                                </tr>
                            </thead>

                            <tbody style="font-size: 1.8vmin;text-align: center;">
									<?php foreach($data['jobs'] as $key =>$val){?>
										<tr >
											<td id='job_id' ><?php echo $val['job_id'];?></td>
											<td><?php echo $val['job_name'];?></td>
											<td><?php echo $text[$data['direction'][$val['reverse_direction']]];?></td>
											<td><?php echo $val['reverse_rpm'];?></td>
											<td><?php echo $val['reverse_power'];?></td>
											<td><?php echo $val['total_seq'];?></td>
                                            <?php $url ='?url=Sequences/index/'.$val['job_id'];?>
                                            <td><img id="Add_Seq" src="./img/btn_plus.png" onclick="location.href='<?php echo $url;?>'">

                                		</tr>

									<?php }?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div id="TotalPage">
            <div id="TotalJobTable">
                <div style="color:black; float: right; margin: 2px"><?php echo $text['total_job'];?> :
                    <label id="RecordCnt" name="RecordCnt" type="text" style="margin-right: 20px"><?php echo count($data['jobs']);?></label>
                </div>
            </div>
        </div>

        <div class="buttonbox">
        <?php $status = count($data['jobs']) >=  50 ? 'disabled' : ''; ?>
            <input id="S3" name="Job_Manager_Submit" type="button" value="<?php echo $text['New'];?>" tabindex="1"   onclick="cound_job('new')" <?php echo $status;?> >
            <input id="S6" name="Job_Manager_Submit" type="button" value="<?php echo $text['Edit'];?>" tabindex="1"  onclick="cound_job('edit')">
            <input id="S5" name="Job_Manager_Submit" type="button" value="<?php echo $text['Copy'];?>" tabindex="1"  onclick="cound_job('copy')" <?php echo $status;?> >
            <input id="S4" name="Job_Manager_Submit" type="button" value="<?php echo $text['Delete'];?>" tabindex="1" onclick="cound_job('del')">
        </div>
    </div>

    <!-- Add New Job -->
    <div id="newjob" class="modal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content w3-animate-zoom" style="width: 70%">
                <header class="w3-container modal-header">
                    <span onclick="hideElementById('newjob');"
                        class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                    <h3 id='modal_title'><?php echo $text['new_job'];?></h3>
                </header>

                <div class="modal-body">
                    <form id="new_job_form" style="padding-left: 5%">
                        <div class="row">
                            <div for="job-id" class="col-6 t1"><?php echo $text['job_id'];?> :</div>
                            <div class="col-4 t2">
                                <input type="text" class="form-control input-ms" id="job_id" maxlength=""  value='<?php echo $data['jobint'];?>'>
                            </div>
                        </div>
                        <div class="row">
                            <div for="job-name" class="col-6 t1"><?php echo $text['job_name'];?> :</div>
                            <div class="col-4 t2">
                                <input type="text" class="form-control input-ms" id="job_name" maxlength="" >
                            </div>
                        </div>

                        <div class="row">
                            <div for="Unscrew-Direction" class="col-6 t1"><?php echo $text['job_ok'];?> :</div>
                            <div class="col t2" >

                                <div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="job_ok" id="job_off" value="0" >
            					  <label class="form-check-label" for="job_off"> <?php  echo $text['OFF_text']; ?></label>
            					</div>

            			      	<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="job_ok" id="job_ok" value="1">
            					  <label class="form-check-label" for="job_ok"><?php  echo $text['ON_text']; ?></label>
            					</div>
                            </div>
                        </div>

                        <div class="row">
                            <div for="Unscrew-Direction" class="col-6 t1"><?php echo $text['JOB_COMPLETED'];?> :</div>
                            <div class="col t2" >

                                <div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="stop_job_ok" id="stop_job_ok_off" value="0" >
            					  <label class="form-check-label" for="job_off"> <?php  echo $text['OFF_text']; ?></label>
            					</div>

            			      	<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="stop_job_ok" id="stop_job_ok_ok" value="1">
            					  <label class="form-check-label" for="job_ok"><?php  echo $text['ON_text']; ?></label>
            					</div>
                            </div>
                        </div>



                        <div class="row">
                            <div for="Unscrew-Direction" class="col-6 t1"><?php echo $text['reverse_direction'];?> :</div>
                            <div class="col t2" >
            			      	<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="direction" id="reverse_direction_CW" value="0">
            					  <label class="form-check-label" for="reverse_direction_CW"><?php  echo $text['CW']; ?></label>
            					</div>
            					<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="direction" id="reverse_direction_CCW" value="1">
            					  <label class="form-check-label" for="reverse_direction_CCW"> <?php  echo $text['CCW']; ?></label>
            					</div>

                                <div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="direction" id="reverse_direction_disable" value="2">
            					  <label class="form-check-label" for="reverse_direction_disable"> <?php  echo $text['Disable']; ?></label>
            					</div>

                            </div>
                        </div>
                        <div class="row">
                            <div for="reverse-RPM" class="col-6 t1"><?php echo $text['reverse_rpm'];?>(1=10%) :</div>
                            <div class="col-4 t2">
                                <input type="text" class="form-control input-ms" id="reverse_rpm" maxlength="" >
                            </div>
                        </div>
                        <div class="row">
                            <div for="reverse-power" class="col-6 t1"><?php echo $text['reverse_power'];?>(1=10%):</div>
                            <div class="col-4 t2">
                                <input type="text" class="form-control input-ms" id="reverse_power" maxlength="">
                            </div>
                        </div>

                    </form>
                </div>

                <div class="modal-footer justify-content-center">
                    <button id="" class="button-modal" onclick="savejob()"><?php echo $text['save'];?></button>
                    <button id="" class="button-modal" onclick="hideElementById('newjob');" class="closebtn"><?php echo $text['close'];?></button>
                </div>
            </div>
        </div>
    </div>


    <!-- edit Job -->
    <div id="editjob" class="modal" >
    <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content w3-animate-zoom" style="width: 70%">
                <header class="w3-container modal-header">
                    <span onclick="hideElementById('editjob');"
                        class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                    <h3 id='modal_title'><?php echo $text['edit_job'];?></h3>
                </header>

                <div class="modal-body">
                    <form id="new_job_form" style="padding-left: 5%">
                        <div class="row">
                            <div for="job-id" class="col-6 t1"><?php echo $text['job_id'];?> :</div>
                            <div class="col-4 t2">
                                <input type="text" class="form-control input-ms" id="edit_jobid" maxlength=""  value=''>
                            </div>
                        </div>
                        <div class="row">
                            <div for="job-name" class="col-6 t1"><?php echo $text['job_name'];?> :</div>
                            <div class="col-4 t2">
                                <input type="text" class="form-control input-ms" id="edit_jobname" maxlength="" >
                            </div>
                        </div>

                        <div class="row">
                            <div for="Unscrew-Direction" class="col-6 t1"><?php echo $text['job_ok'];?> :</div>
                            <div class="col t2" >

                                <div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_job_ok" id="job_off" value="0" >
            					  <label class="form-check-label" for="job_off"> <?php  echo $text['OFF_text']; ?></label>
            					</div>

            			      	<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_job_ok" id="job_ok" value="1">
            					  <label class="form-check-label" for="job_ok"><?php  echo $text['ON_text']; ?></label>
            					</div>
                            </div>
                        </div>

                        <div class="row">
                            <div for="Unscrew-Direction" class="col-6 t1"><?php echo $text['JOB_COMPLETED'];?> :</div>
                            <div class="col t2" >

                                <div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_stop_job_ok" id="stop_job_ok_off" value="0" >
            					  <label class="form-check-label" for="job_off"> <?php  echo $text['OFF_text']; ?></label>
            					</div>

            			      	<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_stop_job_ok" id="stop_job_ok_ok" value="1">
            					  <label class="form-check-label" for="job_ok"><?php  echo $text['ON_text']; ?></label>
            					</div>
                            </div>
                        </div>


                        <div class="row">
                            <div for="Unscrew-Direction" class="col-6 t1"><?php echo $text['reverse_direction'];?> :</div>
                            <div class="col t2" >
            			      	<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_direction" id="direction_CW" value="0">
            					  <label class="form-check-label" for="reverse_direction_CW"><?php  echo $text['CW'];?></label>
            					</div>

            					<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio"  name="edit_direction" id="direction_CCW" value="1">
            					  <label class="form-check-label" for="reverse_direction_CCW"><?php  echo $text['CCW'];?></label>
            					</div>

                                <div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_direction" id="direction_disable" value="2">
            					  <label class="form-check-label" for="reverse_direction_disable"> <?php  echo $text['Disable']; ?></label>
            					</div>
                            </div>
                        </div>
                        <div class="row">
                            <div for="reverse-RPM" class="col-6 t1"><?php echo $text['reverse_rpm'];?>(1=10%) :</div>
                            <div class="col-4 t2">
                                <input type="text" class="form-control input-ms" id="edit_reverse_rpm" maxlength="" >
                            </div>
                        </div>
                        <div class="row">
                            <div for="reverse-power" class="col-6 t1"><?php echo $text['reverse_power'];?>(1=10%):</div>
                            <div class="col-4 t2">
                                <input type="text" class="form-control input-ms" id="edit_reverse_power" maxlength="">
                            </div>
                        </div>

                    </form>
                </div>

                <div class="modal-footer justify-content-center">
                    <button id="" class="button-modal" onclick="updatejob();"><?php echo $text['save'];?></button>
                    <button id="" class="button-modal" onclick="hideElementById('editjob');" class="closebtn"><?php echo $text['close'];?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- Copy Job -->
    <div id="copyjob" class="modal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content w3-animate-zoom" style="width: 60%">
                <header class="w3-container modal-header">
                    <span onclick="hideElementById('copyjob');"
                        class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                    <h3 id='modal_title'><?php echo $text['copy_job'];?></h3>
                </header>

                <div class="modal-body">
                    <form id="new_job_form">
        	            <label for="from_job_id" class="col col-form-label" style="font-weight: bold"><?php echo $text['copy_from'];?></label>
        	            <div style="padding-left: 10%;">
        		            <div class="row">
        				        <label for="from_job_id" class="t1 col-4 col-form-label"><?php echo $text['job_id'];?> :</label>
        				        <div class="col-5 t2 ">
        				            <input type="number" class="form-control" id="from_job_id" disabled>
        				        </div>
        				    </div>
        				    <div class="row">
        				        <label for="from_job_name" class="t1 col-4 col-form-label"><?php echo $text['job_name'];?> :</label>
        				        <div class="t2 col-5">
        				            <input type="text" class="form-control" id="from_job_name" disabled>
        				        </div>
        				    </div>
        			    </div>

        			    <label for="from_job_id" class="col col-form-label" style="font-weight: bold"><?php echo $text['copy_to'];?></label>
        			    <div style="padding-left: 10%;">
        				    <div class="row">
        				        <label for="to_job_id" class="t1 col-4 col-form-label"><?php echo $text['job_id'];?> :</label>
        				        <div class="t2 col-5">
        				            <input type="number" class="form-control" id="to_job_id">
        				        </div>
        				    </div>
        				    <div class="row">
        				        <label for="to_job_name" class="t1 col-4 col-form-label"><?php echo $text['job_name'];?> :</label>
        				        <div class="t2 col-5">
        				            <input type="text" class="form-control" id="to_job_name">
        				        </div>
        				    </div>
        			    </div>
        			  </form>
                </div>

                <div class="modal-footer justify-content-center">
                    <button id="" class="button-modal"  onclick="copy_job_by_id();"><?php echo $text['save'];?></button>
                    <button id="" class="button-modal" onclick="hideElementById('copyjob');"  class="closebtn"><?php echo $text['close'];?></button>
                </div>
            </div>
        </div>
    </div>


</div>

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
                
                localStorage.setItem("jobid", jobid );
                localStorage.setItem("jobname", secondCellValue);
                localStorage.setItem("direction", thirdCellValue);
                localStorage.setItem("powervalue", powervalue);
                localStorage.setItem("rpmvalue", rpmvalue);

            });
        }
    })(rows[i]);
}

function savejob() {

    var jobidnew = '<?php echo $data['jobint']?>';

    var jobname_val      = document.getElementById("job_name").value;
    var reverse_rpm_val  = document.getElementById("reverse_rpm").value;
    var reverse_power_val = document.getElementById("reverse_power").value;
    
    var directionElement = document.querySelector('input[name="direction"]:checked');
    var direction_val = directionElement ? directionElement.value : null;

    var jobElement = document.querySelector('input[name="job_ok"]:checked');
    var job_ok_val = jobElement ? jobElement.value : null;

    var stopjobokElement = document.querySelector('input[name="stop_job_ok"]:checked');
    var stop_job_ok_val = stopjobokElement ? stopjobokElement .value : null;

    if (jobname_val && reverse_rpm_val && reverse_power_val  && direction_val ) {
        $.ajax({
            url: "?url=Jobs/create_job",
            method: "POST",
            data: { 
                jobidnew: jobidnew,
                jobname_val: jobname_val,
                reverse_rpm_val: reverse_rpm_val,
                reverse_power_val: reverse_power_val,
                direction_val: direction_val, //起子方向
                job_ok_val: job_ok_val,
                stop_job_ok_val:stop_job_ok_val
            },
            success: function(response) {
       
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    history.go(0);
                });         
            },
            error: function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
            }
        });
    }
}


function copy_job_by_id(jobid){

    var new_jobid = document.getElementById("to_job_id").value;
    var new_jobname = document.getElementById("to_job_name").value;

    document.getElementById("from_job_id").value = old_jobid;
    document.getElementById("from_job_name").value = oldjobname;
    document.getElementById("to_job_id").value = new_jobid;

    if(new_jobid){


        var language = getCookie('language');
        if(language == "zh-cn"){
            var text_info ='你确定吗？';
            var title = 'Copy Job';
        }else if(language == "zh-tw"){
            var text_info ='你確定嗎 ?';
            var title = 'Copy Job';
        }else{
            var text_info ='Are you sure ?';
            var title = 'Copy Job';
        }
        
        
        $.ajax({
            url: "?url=Jobs/check_job_type",
            method: "POST",
            data:{ 
                new_jobid: new_jobid,

            },
            success: function(response) {
                alertify.confirm(text_info, function (result) {

                
                if (result) {
                    $.ajax({
                        url: "?url=Jobs/copy_job_data",
                        method: "POST",
                        data:{ 
                            old_jobid: old_jobid,
                            old_jobname: oldjobname,
                            new_jobid: new_jobid,
                            new_jobname: new_jobname

                        },
                        success: function(response) { 
                            var responseData = JSON.parse(response);
                            alertify.alert(responseData.res_type, responseData.res_msg, function() {
                                document.getElementById('copyjob').style.display = 'none';
                                history.go(0);
                            }); 

                    
                        },
                        error: function(xhr, status, error) {
                            
                        }
                    });
                } else {
                    alertify.error('Cancelled');
                   
                }

                
                });
                        },
            error: function(xhr, status, error) {
                
            }
        });
        document.getElementById('copyjob').style.display = 'none';
    }
}
</script>