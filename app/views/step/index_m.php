
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/step_m.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <header id="header">
 	        <h3> <?php echo $text['step_management'];?> </h3>
        </header>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:3.2vmin;color: #000; padding-left: 0%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="5" maxlength="20" value="<?php echo $data['job_id'];?>" disabled
                style="height:28px; font-size:3.2vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:3.2vmin;color: #000; padding-left: 0%" for="seq_id"><?php echo $text['seq_id'];?> :</label>&nbsp;
                <input type="text" id="seq_id" name="seq_id" size="5" maxlength="20" value="1" disabled
                style="height:28px; font-size:3.2vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <?php $url ='?url=Sequences/index/'.$data['job_id'];?>
                <button id="back_btn" type="button" onclick="window.location.href='<?php echo $url; ?>';"><?php echo $text['return'];?></button>
            </div>

            <div class="table-container">
                <div class="scrollbar" id="style-steptable">
                    <div class="force-overflow">
                        <table id="step_table" class="table w3-table ">
                            <thead id="header-table">
                                <tr class="w3-dark-grey"  style="font-size: 2.6vmin">
                                    <th><?php echo $text['step_id'];?></th>
                                    <th><?php echo$text['step_name'];?></th>
                                    <th><?php echo $text['step_target_type'];?></th>
                                    <th><?php echo $text['direction'];?></th>
                                    <th><?php echo $text['rpm'];?></th>
                                    <th><?php echo $text['up'];?></th>
                                    <th><?php echo $text['down'];?></th>
                                </tr>
                            </thead>

                            <tbody style="font-size: 2.6vmin;text-align: center;">
                                <?php foreach($data['step'] as $key =>$val){?>
                                    <tr>
                                        <td><?php echo $val['StepSelect'];?></td>
                                        <td><?php echo $val['STEPname'];?></td>
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
            <?php $status = count($data['step']) == 5 ? 'disabled' : ''; ?>
            <input id="S3" name="Step_Manager_Submit" type="button" value="<?php echo $text['New'];?>"    tabindex="1"  onclick="cound_step('new');" <?php echo $status; ?>>
            <input id="S6" name="Step_Manager_Submit" type="button" value="<?php echo $text['Edit'];?>"   tabindex="1" onclick="cound_step('edit')">
            <input id="S5" name="Step_Manager_Submit" type="button" value="<?php echo $text['Copy'];?>"   tabindex="1"  onclick="cound_step('copy');" <?php echo $status; ?>>
            <input id="S4" name="Step_Manager_Submit" type="button" value="<?php echo $text['Delete'];?>" tabindex="1" onclick="cound_step('del');" >
        </div>
    </div>

    <!-- Copy Step -->
    <div id="copystep" class="modal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content w3-animate-zoom" style="width: 90%">
                <header class="w3-container modal-header">
                    <span onclick="closebutton('copystep')"
                        class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                    <h3 id='modal_title'><?php echo $text['copy_step'];?></h3>
                </header>

                <div class="modal-body">
                    <form id="new_step_form">
        	            <label for="from_step_id" class="col col-form-label" style="font-weight: bold"><?php echo $text['copy_from'];?></label>
        	            <div style="padding-left: 10%">
        		            <div class="row">
        				        <label for="from_step_id" class="t1 col-5 col-form-label"><?php echo $text['step_id'];?> :</label>
        				        <div class="col-4 t2 ">
        				            <input type="number" class="form-control" id="from_step_id" disabled>
        				        </div>
        				    </div>
        			    </div>

        			    <label for="from_step_id" class="col col-form-label" style="font-weight: bold"><?php echo $text['copy_to'];?></label>
        			    <div style="padding-left: 10%">
        				    <div class="row">
        				        <label for="to_step_id" class="t1 col-5 col-form-label"><?php echo $text['step_id'];?> :</label>
        				        <div class="t2 col-4">
        				            <input type="number" class="form-control" id="to_step_id">
        				        </div>
        				    </div>
        			    </div>
        			  </form>
                </div>

                <div class="modal-footer justify-content-center">
                    <button id="copyButton" class="button-modal" onclick="copy_step_by_id_ajax()" ><?php echo $text['save'];?></button>
                    <button id="" class="button-modal" onclick="closebutton('copystep')" class="closebtn"><?php echo $text['close'];?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- 加载動畫 OP -->
       <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->

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
</script>

<?php require_once '../app/views/step/step_share.php';?>
