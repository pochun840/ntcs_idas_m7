
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_step.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['step_management']; ?></h3></td>
                <!--<td>
                    <img src="./img/btn_home.png" style="margin-right: 10px">
                </td>-->
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:20px;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="8" maxlength="20" value="<?php echo $data['job_id'];?>" disabled
                style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:20px;color: #000; padding-left: 2%" for="seq_id"><?php echo $text['seq_id'];?> :</label>&nbsp;
                <input type="text" id="seq_id" name="seq_id" size="8" maxlength="20" value="<?php echo $data['seq_id'];?>" disabled
                style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <button id="back_btn" type="button" onclick="window.history.back()"><?php echo $text['return'];?></button>
            </div>

            <div class="table-container">
                <table id="step_table" class="table w3-table-all w3-hoverable">
                    <thead id="header-table">
                        <tr class="w3-dark-grey">
                            <th><?php echo $text['step_id'];?></th>
                            <th><?php echo $text['step_target_type'];?></th>
                            <th><?php echo $text['direction'];?></th>
                            <th><?php echo $text['rpm'];?></th>
                            <th><?php echo $text['up'];?></th>
                            <th><?php echo $text['down'];?></th>
                        </tr>
                    </thead>

                    <tbody style="font-size: 1.8vmin;text-align: center;">
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
            <input id="S3" name="Step_Manager_Submit" type="button" value="<?php echo $text['New'];?>" tabindex="1"  onclick="cound_step('new');" <?php echo $status;?>>
            <input id="S6" name="Step_Manager_Submit" type="button" value="<?php echo $text['Edit'];?>" tabindex="1" onclick="cound_step('edit')">
            <input id="S5" name="Step_Manager_Submit" type="button" value="<?php echo $text['Copy'];?>" tabindex="1"  onclick="cound_step('copy');" <?php echo $status; ?>>
            <input id="S4" name="Step_Manager_Submit" type="button" value="<?php echo $text['Delete'];?>" tabindex="1" onclick="cound_step('del');" >
        </div>
    </div>

    

    <!-- edit Step -->
    <div id="editstep" class="modal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content w3-animate-zoom" style="width: 80%">
                <header class="w3-container modal-header">
                    <span onclick="hideElementById('editstep');"
                        class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                    <h3 id='modal_title'><?php echo $text['edit_step'];?></h3>
                </header>

                <div class="modal-body">
                    <form id="new_step_form" style="padding-left: 5%">
                        <div class="row">
                            <div for="target-option" class="col-6 t1"><?php echo $text['step_target_type'];?> :</div>
                            <div class="col-3 t2">
                                <select id="edit_target_option" name="edit_target_option" class="col custom-file">
                                    <?php foreach($data['target_option'] as $key => $val){?>
                                        <option value="<?php echo $key;?>"><?php echo $text[$val];?></option>
                                    <?php }?>
                                    
                                </select>
                            </div>
                        </div>

                        
                        <div class="row">
                            <div for="edit_target-torque" id="edit_target-torque_title"  class="col-6 t1" style="display: none;" ><?php echo $text['Target_Angle'];?>(<?php echo $text[$data['unit']];?>):</div>
                            <div class="col-3 t2" id="edit_target-torque_val" style="display:none;" >
                                <input type="text" class="form-control input-ms" id="edit_target_torque" maxlength="" >
                            </div>
                        </div>
                     
                   

                        <div class="row">
                            <div for="edit_target-angle" id="edit_target-angle_title"  class="col-6 t1" style="display: none;" ><?php echo $text['Target_Angle'];?>:</div>
                            <div class="col-3 t2" id="edit_target-angle_val" style="display:none;" >
                                <input type="text" class="form-control input-ms" id="edit_target_angle" maxlength="" >
                            </div>
                        </div>


                        <div class="row">
                            <div for="edit_target-delaytime" id="edit_target-delaytime_title"  class="col-6 t1" style="display: none;" ><?php  echo  $text['Target Delay Time'] ; ?>:</div>
                            <div class="col-3 t2" id="edit_target-delaytime_val" style="display:none;" >
                                <input type="text" class="form-control input-ms" id="edit_target_delaytime" maxlength="" >
                            </div>
                        </div>

                        <div class="row">
                            <div for="hi-torque" class="col-6 t1"><?php echo $text['High_Torque'];?> (<?php echo $text[$data['unit_name']];?>):</div>
                            <div class="col-3 t2">
                                <input type="text" class="form-control input-ms" id="edit_hi_torque" maxlength="" >
                            </div>
                        </div>
                        
                        <div class="row">
                            <div for="lo-torque" class="col-6 t1"><?php echo $text['Low_Torque'];?> (<?php echo $text[$data['unit_name']];?>):</div>
                            <div class="col-3 t2">
                                <input type="text" class="form-control input-ms" id="edit_lo_torque" maxlength="" >
                            </div>
                        </div>
                        <div class="row">
                            <div for="hi-angle" class="col-6 t1"><?php echo $text['High_Angle'];?>:</div>
                            <div class="col-3 t2">
                                <input type="text" class="form-control input-ms" id="edit_hi_angle" maxlength="" >
                            </div>
                        </div>
                        <div class="row">
                            <div for="lo-angle" class="col-6 t1"><?php echo $text['Low_Angle'];?>:</div>
                            <div class="col-3 t2">
                                <input type="text" class="form-control input-ms" id="edit_lo_angle" maxlength="" >
                            </div>
                        </div>
                        <div class="row">
                            <div for="RPM" class="col-6 t1"><?php echo $text['rpm'];?>:</div>
                            <div class="col-3 t2">
                                <input type="text" class="form-control input-ms" id="edit_rpm" maxlength="" >
                            </div>
                        </div>
                        <div class="row">
                            <div for="direction" class="col-6 t1"><?php echo $text['direction'];?>:</div>
                            <div class="col t2" >
            			      	<div class="col-4 form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_direction_option" id="direction_CW" value="0">
            					  <label class="form-check-label" for="direction_CW"><?php echo $text['CW'];?></label>
            					</div>
            					<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_direction_option" id="direction_CCW" value="1">
            					  <label class="form-check-label" for="direction_CCW"><?php echo $text['CCW'];?></label>
            					</div>
                            </div>
                        </div>
                        <div class="row">
                            <div for="downshift" class="col-6 t1"><?php echo $text['Downshift'];?>:</div>
                            <div class="col t2" >
            			      	<div class="col-4 form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_downshift_option" id="downshift_ON" value="1">
            					  <label class="form-check-label" for="downshift_ON"><?php echo $text['switch_on'];?></label>
            					</div>
            					<div class="form-check form-check-inline">
            					  <input class="form-check-input" type="radio" name="edit_downshift_option" id="downshift_OFF" value="0" >
            					  <label class="form-check-label" for="downshift_OFF"><?php echo $text['switch_off'];?></label>
            					</div>
                            </div>
                        </div>
                        <div class="row">
                            <div for="edit_downshift-threshold" class="col-6 t1">Downshift Threshold(<?php echo $text[$data['unit_name']];?>):</div>
                            <div class="col-3 t2">
                                <input type="text" class="form-control input-ms" id="edit_downshift_threshold" maxlength="" >
                            </div>
                        </div>
                        <div class="row">
                            <div for="edit_downshift-torque" class="col-6 t1"><?php echo $text['Downshift_Torque'];?>(<?php echo $text[$data['unit_name']];?>):</div>
                            <div class="col-3 t2">
                                <input type="text" class="form-control input-ms" id="edit_downshift_torque" maxlength="" >
                            </div>
                        </div>
                        <div class="row">
                            <div for="edit_downshift-speed" class="col-6 t1"><?php echo $text['Downshift_Speed'];?>:</div>
                            <div class="col-3 t2">
                                <input type="text" class="form-control input-ms" id="edit_downshift_speed" maxlength="" >
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer justify-content-center">
                    <button id="" class="button-modal" onclick="edit_step_save()" ><?php echo $text['save'];?></button>
                    <button id="" class="button-modal" onclick="hideElementById('edittep');"  class="closebtn"><?php echo $text['close'];?></button>
                </div>
            </div>
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

// Get the modal
var modal = document.getElementById('newstep');

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
var stepid = '';
var rows = document.getElementsByTagName("tr");
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
    stepid = selectedRowData;
    if(argument == 'del'){
        del_stepid(stepid);
    }

    if(argument =="copy" && stepid != null){
        copy_step(stepid);
    }


    if(argument =="new"){
        create_step();
    }

    if(argument =="edit" && stepid != null){
        edit_step(stepid);
    }

}


function edit_step(){

    var jobid = '<?php echo $data['job_id']?>';
    var seqid = '<?php echo $data['seq_id']?>';

    var unit = '<?php echo $data['unit_name']?>';
    var language = getCookie('language');


    if(language == "zh-cn"){
        var torque_title = '目标扭力';
    }else if(language == "zh-tw"){
        var torque_title = '目標扭力';
    }else{
        var torque_title ='Target Torque';
    }


    if(jobid){
        $.ajax({
            url: "?url=Step/search_stepinfo",
            method: "POST",
            data:{ 
                jobid: jobid,
                seqid: seqid,
                stepid:stepid,
            },
            success: function(response) {

                var responseJSON = JSON.stringify(response);
                var cleanString = responseJSON.replace(/Array|\\n/g, '');
                var cleanString = cleanString.substring(2, cleanString.length - 2);



                var [, jobid] = cleanString.match(/\[job_id]\s*=>\s*([^ ]+)/) || [, null];
                var [, seqid] = cleanString.match(/\[sequence_id]\s*=>\s*([^ ]+)/) || [, null];
                var [, step_id] = cleanString.match(/\[step_id]\s*=>\s*([^ ]+)/) || [, null];
                var [, hi_torque] = cleanString.match(/\[hi_torque]\s*=>\s*([^ ]+)/) || [, null];
                var [, lo_torque] = cleanString.match(/\[lo_torque]\s*=>\s*([^ ]+)/) || [, null];
                var [, hi_angle] = cleanString.match(/\[hi_angle]\s*=>\s*([^ ]+)/) || [, null];
                var [, lo_angle] = cleanString.match(/\[lo_angle]\s*=>\s*([^ ]+)/) || [, null];
                var [, rpm] = cleanString.match(/\[rpm]\s*=>\s*([^ ]+)/) || [, null];
                var [, downshift_speed] = cleanString.match(/\[downshift_speed]\s*=>\s*([^ ]+)/) || [, null];
                var [, downshift_torque] = cleanString.match(/\[downshift_torque]\s*=>\s*([^ ]+)/) || [, null];
                var [, threshold_torque] = cleanString.match(/\[threshold_torque]\s*=>\s*([^ ]+)/) || [, null];
                var [, target_option] = cleanString.match(/\[target_option]\s*=>\s*([^ ]+)/) || [, null];
                var [, target_torque] = cleanString.match(/\[target_torque]\s*=>\s*([^ ]+)/) || [, null];
                var [, target_angle] = cleanString.match(/\[target_angle]\s*=>\s*([^ ]+)/) || [, null];
                var [, target_delaytime] = cleanString.match(/\[target_delaytime]\s*=>\s*([^ ]+)/) || [, null];
                var [, direction] = cleanString.match(/\[direction]\s*=>\s*([^ ]+)/) || [, null];
                var [, downshift] = cleanString.match(/\[downshift]\s*=>\s*([^ ]+)/) || [, null];
                var [, check_count] = cleanString.match(/\[check_count]\s*=>\s*([^ ]+)/) || [, null];
                var [, downshift_speed] = cleanString.match(/\[downshift_speed]\s*=>\s*([^ ]+)/) || [, null];
               

                document.getElementById('editstep').style.display = 'block';
                document.getElementById("edit_hi_torque").value = hi_torque;
                document.getElementById("edit_lo_torque").value = lo_torque;
                document.getElementById("edit_hi_angle").value = hi_angle;
                document.getElementById("edit_lo_angle").value = lo_angle;
                document.getElementById("edit_rpm").value = rpm;
                document.getElementById("edit_downshift_speed").value = downshift_speed;
                document.getElementById("edit_downshift_torque").value = downshift_torque;
                document.getElementById("edit_downshift_threshold").value = threshold_torque;
                document.querySelector("select[name='edit_target_option']").value = target_option;

                document.getElementById("edit_target_delaytime").value = target_delaytime;
                document.getElementById("edit_target_torque").value = target_torque;
                document.getElementById("edit_target_angle").value = target_angle;


                var radioButtons1 = document.getElementsByName("edit_direction_option");
                var radioButtons2 = document.getElementsByName("edit_downshift_option");

                setRadioButtonValue(radioButtons1, direction);
                setRadioButtonValue(radioButtons2, downshift);

                //判斷有其他的step 選了 扭力 
                //target_option的下拉式選單 就會把 扭力 移除
                if(target_option != 0 && check_count != 0){
                    
                    var selectElement = document.getElementById('mySelect');
                    var selectElement = document.querySelector('select[name="edit_target_option"]');

                    for(var i = selectElement.options.length - 1; i >= 0; i--) {
                        var option = selectElement.options[i];
                        if (option.value === '0') { 
                            selectElement.remove(i);
                        }
                    }
                }else{
                    var selectElement = document.getElementById('mySelect');
                    var selectElement = document.querySelector('select[name="edit_target_option"]');
                }

                if(target_option == 2){
                    document.querySelector('div[for="edit_target-torque"]').textContent = '<?php echo $text['Target Delay Time']?>';
                    document.getElementById('edit_hi_torque').disabled = true; 
                    document.getElementById('edit_lo_torque').disabled = true; 
                    document.getElementById('edit_hi_angle').disabled = true; 
                    document.getElementById('edit_lo_angle').disabled = true; 
                    document.getElementById('edit_rpm').disabled = true; 
                    document.getElementById('edit_downshift_threshold').disabled = true; 
                    document.getElementById('edit_downshift_torque').disabled = true; 
                    document.getElementById('edit_downshift_speed').disabled = true; 

                    document.querySelectorAll('input[name="edit_direction_option"]').forEach(function(radioButton) {
                                radioButton.disabled = true;
                    });

                    document.querySelectorAll('input[name="edit_downshift_option"]').forEach(function(radioButton) {
                                radioButton.disabled = true;
                    });
                }

                //扭力
                if(target_option == 0){
                    document.getElementById('edit_target-torque_title').style.display = 'block';
                    document.getElementById('edit_target-torque_val').style.display = 'block';
                    document.getElementById('edit_target_torque').value = target_torque;  
                }

                //角度
                if(target_option == 1){
                    document.getElementById('edit_target-angle_title').style.display = 'block';
                    document.getElementById('edit_target-angle_val').style.display = 'block';
                    document.getElementById('edit_target_angle').value = target_angle; 
                }

                //delay time 
                if(target_option == 2){
                    document.getElementById('edit_target-delaytime_title').style.display = 'block';
                    document.getElementById('edit_target-delaytime_val').style.display = 'block';
                    document.getElementById("edit_target_delaytime").value = target_delaytime;
                }


                if(target_option == 0){
                    var name = '<?php echo $text['Target_Torque']?>';
                    const unitTranslations = {
                        "zh-cn": {
                            "kgf.cm": "公斤公分",
                            "kgf.m": "公斤米",
                            "N.m": "牛頓米",
                            "default": "英磅英吋"
                        },
                        "zh-tw": {
                            "kgf.cm": "公斤公分",
                            "kgf.m": "公斤米",
                            "N.m": "牛頓米",
                            "default": "英磅英吋"
                        }
                        
                    };

                    if (language === "zh-cn" || language === "zh-tw") {
                        unit = unitTranslations[language][unit] || unitTranslations[language]["default"];
                    } 
                    document.getElementById('edit_target-torque_title').style.display = 'block';
                    document.querySelector('div[for="edit_target-torque"]').textContent = torque_title + "(" + unit + ")" ;
                }

                if(downshift == 0){
                    document.querySelector('div[for="edit_downshift-torque"]').style.display = "none";
                    document.getElementById('edit_downshift_torque').style.display = "none";

                    document.querySelector('div[for="edit_downshift-threshold"]').style.display = "none";
                    document.getElementById('edit_downshift_threshold').style.display = "none";

                    document.querySelector('div[for="edit_downshift-speed"]').style.display = "none";
                    document.getElementById('edit_downshift_speed').style.display = "none";

                }

                var target_option = document.getElementById("edit_target_option");
                target_option.addEventListener('change', function() {
                var selectedValue = this.value;
                    //alert(selectedValue);

                    if (selectedValue == 2) {
                        var elementsToDisable = [
                            document.getElementById('edit_hi_torque'),
                            document.getElementById('edit_lo_torque'),
                            document.getElementById('edit_hi_angle'),
                            document.getElementById('edit_lo_angle'),
                            document.getElementById('edit_rpm'),
                            document.getElementById('edit_downshift_speed'),
                            document.getElementById('edit_downshift_threshold'),
                            document.getElementById('edit_downshift_speed'),
                            document.getElementById('edit_downshift_torque')
                        ];

                        disableElements(elementsToDisable, true);

                        document.querySelectorAll('input[name="edit_direction_option"]').forEach(function(radioButton) {
                            radioButton.disabled = true;
                        });

                        document.querySelectorAll('input[name="edit_downshift_option"]').forEach(function(radioButton) {
                            radioButton.disabled = true;
                        });
                

                        document.querySelector('div[for="edit_target-torque"]').textContent = '<?php echo $text['Target Delay Time']?>';
                    } 
                    
                    if (selectedValue == 1 || selectedValue == 0) {

                        document.getElementById('edit_hi_torque').disabled = false;
                        document.getElementById('edit_hi_torque').value = hi_torque;

                        document.getElementById('edit_lo_torque').disabled = false;
                        document.getElementById('edit_lo_torque').value = lo_torque;

                        document.getElementById('edit_hi_angle').disabled = false;
                        document.getElementById('edit_hi_angle').value = hi_angle;

                        document.getElementById('edit_lo_angle').disabled = false;
                        document.getElementById('edit_lo_angle').value = lo_angle;

                        document.getElementById('edit_rpm').disabled = false;  
                        document.getElementById('edit_rpm').value = rpm;

                        document.getElementById('edit_downshift_threshold').disabled = false; 
                        document.getElementById('edit_downshift_threshold').value = threshold_torque;

                        document.getElementById('edit_downshift_speed').disabled = false; 
                        document.getElementById('edit_downshift_speed').value = downshift_speed;


                        document.getElementById('edit_downshift_torque').disabled = false; 
                        document.getElementById('edit_downshift_torque').value = downshift_torque;


                        document.querySelectorAll('input[name="edit_direction_option"]').forEach(function(radioButton) {
                            radioButton.disabled = false;
                        });

                        document.querySelectorAll('input[name="edit_downshift_option"]').forEach(function(radioButton) {
                            radioButton.disabled = false;
                        });

                    }

                    if(selectedValue == 0){

                        document.getElementById('edit_target-torque_title').style.display = 'block';
                        document.getElementById('edit_target-torque_val').style.display = 'block';

                        document.getElementById('edit_target-angle_title').style.display = 'none';
                        document.getElementById('edit_target-angle_val').style.display = 'none';

                        document.getElementById('edit_target-delaytime_title').style.display = 'none';
                        document.getElementById('edit_target-delaytime_val').style.display = 'none';
                        document.querySelector('div[for="edit_target-torque"]').textContent = '<?php echo $text['Target_Torque'] ?>' + "(" + unit + ")" ;

                    }


                    if(selectedValue == 1){

                        document.getElementById('edit_target-torque_title').style.display = 'none';
                        document.getElementById('edit_target-torque_val').style.display = 'none';

                        document.getElementById('edit_target-angle_title').style.display = 'block';
                        document.getElementById('edit_target-angle_val').style.display = 'block';


                        document.getElementById('edit_target-delaytime_title').style.display = 'none';
                        document.getElementById('edit_target-delaytime_val').style.display = 'none';

                    }


                    if(selectedValue == 2){

                        document.getElementById('edit_target-torque_title').style.display = 'none';
                        document.getElementById('edit_target-torque_val').style.display = 'none';

                        document.getElementById('edit_target-angle_title').style.display = 'none';
                        document.getElementById('edit_target-angle_val').style.display = 'none';

                        document.getElementById('edit_target-delaytime_title').style.display = 'block';
                        document.getElementById('edit_target-delaytime_val').style.display = 'block';

                    }


                
                });


                var downshiftOptionRadios = document.getElementsByName("edit_downshift_option");
                for (var i = 0; i < downshiftOptionRadios.length; i++) {
                    downshiftOptionRadios[i].addEventListener("change", function() {
                        var selectval = this.value;
                        localStorage.setItem('downshift_option',selectval);
                        if(selectval == 1){
                            document.querySelector('div[for="edit_downshift-torque"]').style.display = "block";
                            document.getElementById('edit_downshift_torque').style.display = "block";

                            document.querySelector('div[for="edit_downshift-threshold"]').style.display = "block";
                            document.getElementById('edit_downshift_threshold').style.display = "block";

                            document.querySelector('div[for="edit_downshift-speed"]').style.display = "block";
                            document.getElementById('edit_downshift_speed').style.display = "block";
                        }else{
                            document.querySelector('div[for="edit_downshift-torque"]').style.display = "none";
                            document.getElementById('edit_downshift_torque').style.display = "none";

                            document.querySelector('div[for="edit_downshift-threshold"]').style.display = "none";
                            document.getElementById('edit_downshift_threshold').style.display = "none";

                            document.querySelector('div[for="edit_downshift-speed"]').style.display = "none";
                            document.getElementById('edit_downshift_speed').style.display = "none";
                        }
                    
                    });
                }

            },
            error: function(xhr, status, error) {
                
            }
        });

    }

}




function create_step() {
    var job_id = '<?php echo $data['job_id'];?>';    
    var seq_id = '<?php echo $data['seq_id'];?>';
    window.location.href = '../public/?url=Step/variation/' + job_id + '/' + seq_id; 


}






function copy_step_by_id(){
    var jobid = '<?php echo $data['job_id']?>';
    var seqidnew = '<?php echo $data['stepid_new']?>';
    
    document.getElementById('from_step_id').value = stepid;    
    document.getElementById("to_step_id").value = seqidnew;


}

function copy_step_by_id_ajax(){
    //var stepid = readFromLocalStorage("stepid");
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

function disableElements(elements, value) {
    elements.forEach(function(element) {
        element.disabled = value;
        element.value = value === true ? 0 : ''; 
    });
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
</script>