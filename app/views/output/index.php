
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_output.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['output'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:2.5vmin;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="8" maxlength="20" value="" disabled style="height:30px; font-size:2.5vmin;text-align: center; background-color: #DDDDDD; border:0;">&nbsp;&nbsp;
                <button id="Button_Select" type="button" onclick="document.getElementById('JobSelect').style.display='block'"><?php echo $text['select'];?></button>
            </div>

            <!-- Job Select Modal -->
            <div id="JobSelect" class="modal">
                <form class="w3-modal-content w3-card-4 w3-animate-zoom" style="width: 400px; top: 12%; left: -20%" action="">
                    <div class="w3-light-grey">
                        <header class="w3-container w3-dark-grey" style="height: 48px">
                            <span onclick="document.getElementById('JobSelect').style.display='none'" class="w3-button w3-red w3-large w3-display-topright" style="margin: 2px">&times;</span>
                            <h3 style="margin: 5px"><?php echo $text['job_select'];?></h3>
                        </header>
                        <table id="Job_Select">
                            <tr>
                                <td>
                                    <select style="margin: center" id="JobNameSelect" name="JobNameSelect" size="200">
                                        <?php foreach($data['job_list'] as $key =>$val){?>
                                            <option value="<?php echo $val['job_id'];?>"><?php echo $val['job_name'];?></option>
                                        <?php }?>                                                                                                                                
                                     </select>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="modal-footer justify-content-center w3-dark-grey" style="height: 48px">
                        <button id="select_confirm" type="button" class="btn btn-primary" onclick='job_confirm()'><?php echo $text['confirm'];?></button>
                        <button id="select_close" type="button" class="btn btn-secondary" onclick="document.getElementById('JobSelect').style.display='none'"><?php echo $text['close'];?></button>
                    </div>
                </form>
            </div>

            <!-- Table Input -->
            <!---./img/signal01.png持續 -->
            <!---./img/signal02.png單一週期 -->
            <!---./img/trigger.png起子trigger觸發-->
            <div id="TableOutputSetting">
                <div class="table-output">
                    <div class="scrollbar" id="style-outputtable">
                        <div class="scrollbar-force-overflow">
                            <table id="output_table" class="table w3-table-all w3-hoverable">
                                <thead class="header-table">
                                    <tr class="w3-dark-grey">
                                        <th><?php echo $text['event'];?></th>
                                        <?php $io = 1; for($io = 1; $io <= 11;$io++){?>
                                            <th><?php echo $io;?></th>
                                        <?php } ?>
                                        
                                        <th><?php echo $text['time'];?></th>
                                    </tr>
                                </thead>

                                <tbody id="output_jobid_select" style="font-size: 1.8vmin;text-align: center;"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="footer">
                    <div class="buttonbox">
                        <input id="S1" name="New_Submit" type="button" value="<?php echo $text['New'];?>" tabindex="1"       onclick="crud_job_event('new')">
                        <input id="S2" name="Edit_Submit" type="button" value="<?php echo $text['Edit'];?>" tabindex="1"     onclick="crud_job_event('edit')">
                        <input id="S3" name="Copy_Submit" type="button" value="<?php echo $text['Copy'];?>" tabindex="1"     onclick="crud_job_event('copy')">
                        <input id="S4" name="Delete_Submit" type="button" value="<?php echo $text['Delete'];?>" tabindex="1" onclick="crud_job_event('del')">
                        <input id="S6" name="Align_Submit" type="button" value="<?php echo $text['Align'];?>" tabindex="1" onclick="crud_job_event('unified')">
                    </div>
                </div>
            </div>

            <!-- Add New Output -->
            <div id="new_output" class="modal">
                <div class="modal-dialog modal-lg" style="top: 3%;">
                    <div class="modal-content w3-animate-zoom" style="width:65%">
                        <header class="w3-container modal-header">
                            <span onclick="document.getElementById('new_output').style.display='none'"
                                class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                            <h3 id='modal_title'><?php echo $text['new_event'];?></h3>
                        </header>

                        <div class="modal-body">
                            <form id="new_output_form" style="padding-left: 5%">
                                <div class="row">
                                    <div for="event" class="col-3 t1"><?php echo $text['event'];?> :</div>
                                    <div class="col-2 t2">
                                        <select id="Event_Option" class="col custom-file">
                                        <option value="-1" disabled selected><?php echo $text['Choose_option']; ?></option>
                                           <?php foreach($data['event_output'] as $key =>$val){?>
                                                <option value ='<?php echo $key;?>'><?php echo $text[$val];?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

								<?php for ($i = 1; $i <= 11; $i++) {?>
									<div class="row output-pin">
										<div class="col-sm-2 t1"><?php echo $i; ?>:</div>
										<div class="col-sm-2 t2 form-check form-check-inline">
											<input class="form-check-input" type="radio" name="pin_option" id="pin<?php echo $i; ?>_1" value="1"  onclick="toggleOnputTime('pin<?php echo $i; ?>_1', this.checked,'1')">
											<label class="form-check-label" for="pin<?php echo $i; ?>_signal01"><img src="./img/signal01.png"></label>
										</div>
										<div class="col-sm-2 t2 form-check form-check-inline">
											<input class="form-check-input" type="radio" name="pin_option" id="pin<?php echo $i; ?>_2" value="2"  onclick="toggleOnputTime('pin<?php echo $i; ?>_2', this.checked,'2')">
											<label class="form-check-label" for="pin<?php echo $i; ?>_signal02"><img src="./img/signal02.png"></label>
										</div>
										<div class="col-sm-2 t2 form-check form-check-inline">
											<input class="form-check-input" type="radio" name="pin_option" id="pin<?php echo $i; ?>_3" value="3" onclick="toggleOnputTime('pin<?php echo $i; ?>_3', this.checked,'3')">
											<label class="form-check-label" for="pin<?php echo $i; ?>_trigger"><img src="./img/trigger.png"></label>
										</div>
										<div class="col-sm-2 t2">
											<input type="text" class="form-control" id="time<?php echo $i; ?>" placeholder="ms" style="height: 28px; text-align: center;">
										</div>
									</div>
								<?php } ?>

                            </form>
                        </div>

                        <div class="modal-footer justify-content-center">
                            <button id="" class="button-modal" onclick="create_output_id()"><?php echo $text['save'];?></button>
                            <button id="" class="button-modal" onclick="document.getElementById('new_output').style.display='none'" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Output -->
            <div id="edit_output" class="modal">
                <div class="modal-dialog modal-lg" style="top: 3%;">
                    <div class="modal-content w3-animate-zoom" style="width:65%">
                        <header class="w3-container modal-header">
                            <span onclick="document.getElementById('edit_output').style.display='none'"
                                class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                            <h3 id='modal_title'><?php echo $text['edit_event'];?></h3>
                        </header>

                        <div class="modal-body">
                            <form id="new_output_form" style="padding-left: 5%">
                                <div class="row">
                                    <div for="event" class="col-3 t1"><?php echo $text['event'];?> :</div>
                                    <div class="col-2 t2">
                                        <select id="edit_event_option" name='edit_event_option' class="col custom-file">
                                           <?php foreach($data['event_output'] as $key =>$val){?>
                                                <option value ='<?php echo $key;?>'><?php echo $text[$val];?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

								<?php for ($i = 1; $i <= 11; $i++) {?>
										<div class="row output-pin">
											<div class="col-sm-2 t1"><?php echo $i; ?>:</div>
											<div class="col-sm-2 t2 form-check form-check-inline">
												<input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin<?php echo $i; ?>_1" value="1" onclick="toggleOnputTime_edit('edit_pin<?php echo $i; ?>_1', this.checked,'1')" >
												<label class="form-check-label" for="pin<?php echo $i; ?>_signal01"><img src="./img/signal01.png"></label>
											</div>
											<div class="col-sm-2 t2 form-check form-check-inline">
												<input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin<?php echo $i; ?>_2" value="2" onclick="toggleOnputTime_edit('edit_pin<?php echo $i; ?>_2', this.checked,'2')">
												<label class="form-check-label" for="pin<?php echo $i; ?>_signal02"><img src="./img/signal02.png"></label>
											</div>
											<div class="col-sm-2 t2 form-check form-check-inline">
												<input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin<?php echo $i; ?>_3" value="3" onclick="toggleOnputTime_edit('edit_pin<?php echo $i; ?>_3', this.checked,'3')">
												<label class="form-check-label" for="pin<?php echo $i; ?>_trigger"><img src="./img/trigger.png"></label>
											</div>
											<div class="col-sm-2 t2">
												<input type="text" class="form-control" id="edit_time<?php echo $i; ?>" placeholder="ms" style="height: 28px; text-align: center;">
											</div>
										</div>
						        <?php } ?>


                            </form>
                        </div>

                        <div class="modal-footer justify-content-center">
                            <button id="" class="button-modal" onclick="edit_output_id()"><?php echo $text['save'];?></button>
                            <button id="" class="button-modal" onclick="document.getElementById('edit_output').style.display='none'" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copy Output -->
            <div id="copy_output" class="modal">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content w3-animate-zoom" style="width: 60%;">
                        <header class="w3-container modal-header">
                            <span onclick="document.getElementById('copy_output').style.display='none'"
                                class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                            <h3 id='modal_title'><?php echo $text['copy_input'];?></h3>
                        </header>

                        <div class="modal-body">
                            <form id="new_output_form">
                	            <label for="from_job_id" class="col col-form-label" style="font-weight: bold;padding-left: 5%;"><?php echo $text['copy_from'];?></label>
                	            <div style="padding-left: 10%;">
                		            <div class="row">
                				        <label for="from_job_id" class="t1 col-4 col-form-label"><?php echo $text['job_id'];?> :</label>
                				        <div class="col-5 t2 ">
                				            <input type="number" class="form-control" id="from_job_id" disabled>
                				        </div>

                				        <label for="from_job_name" class="t1 col-4 col-form-label"><?php echo $text['job_name'];?> :</label>
                				        <div class="col-5 t2 ">
                				            <input type="text" class="form-control" id="from_job_name" disabled>
                				        </div>
                				    </div>
                			    </div>

                			    <label for="from_job_id" class="col col-form-label" style="font-weight: bold;padding-left: 5%;"><?php echo $text['copy_to'];?></label>
                			    <div style="padding-left: 10%">
                				    <div class="row">
                				        <label for="to_step_id" class="t1 col-4 col-form-label"><?php echo $text['job'];?> :</label>
                				        <div class="t2 col-6">
                                            <select id="JobSelect1" class="col custom-file" style="margin: center; width: 160px">
                                            <option value="-1" disabled selected><?php echo $text['Choose_option']; ?></option>    
                                                <?php foreach($data['job_list'] as $kk => $vv){?>
                                                    <option id ='job_list_option' value="<?php echo $vv['job_id']; ?>">
                                                        <?php echo $vv['job_id'] . " - " . $vv['job_name']; ?>
                                                    </option>
                                                <?php } ?>  
                                            </select>
                				        </div>
                				    </div>
                			    </div>
                			  </form>
                        </div>

                        <div class="modal-footer justify-content-center">
                            <button id="" class="button-modal" onclick="copy_output_id()"><?php echo $text['save'];?></button>
                            <button id="" class="button-modal" onclick="document.getElementById('copy_output').style.display='none'" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
var job_id; 
var output_event;
var temp;
var tempA;
var buttonDisabled = false;
var backgroundColorYellow = false;
var output_job;
var all_job;
var del_output_val;
var output_pinval;
var dataoutput_pin_val;
$(document).ready(function () {
    highlight_row_input('output_table');

    var all_output_job = '<?php echo $data['device_data']['device_output_all_job']?>';
    job_id = all_output_job ;
    output_job = all_output_job;
    if(job_id){
        get_output_by_job_id(job_id);
        document.getElementById('Button_Select').disabled = true;
        document.getElementById('job_id').style.backgroundColor = 'yellow';
    }
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

var modal = document.getElementById('newinput');

window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

function crud_job_event(argument){
    var table = document.getElementById('output_table');
    var selectedRow = table.querySelector('tr.selected');
    if (selectedRow) {
        var dataEventValue = selectedRow.getAttribute('data-event');
        del_output_val = dataEventValue;
        output_event = del_output_val;

        var dataOutputPinElement = selectedRow.querySelector('[data-outputpin]');
        var dataOutputPinValue = dataOutputPinElement ? dataOutputPinElement.getAttribute('data-outputpin') : null;
        output_pinval = dataOutputPinValue;



    }
    
    if(argument == 'del' && job_id != '' &&  del_output_val){
        delete_output_id(job_id,del_output_val);
    }

    if(argument == 'new' && job_id != ''){

        if (Array.isArray(temp)){ 
            temp.forEach(function(element) {
                var radio = document.getElementById(element);
                if (radio && radio.type === 'radio') { 
                    radio.disabled = true; 
                }
            });


        } 

        var filtered_array = [];
        temp.forEach(function(element) {
            // 檢查是否是以 'pin' 開頭並且不包含 'edit_pin'
            if (element.includes('pin') && !element.includes('edit_pin')) {
                filtered_array.push(element);
            }
        });

       
        filtered_array.forEach(function(id) {
      
            var match = id.match(/(pin\d+)_(\d+)/);
            if (match) {
                var basePinId = match[1]; 
                var pinNumber = match[2]; 

       
                for (var i = 1; i <= 3; i++) {
                    var pinElementId = basePinId + "_" + i;
                    var pinElement = document.getElementById(pinElementId);
                    if (pinElement && pinElement.type === 'radio') {
                        pinElement.disabled = true;
                    }
                }

                // 禁用 time 相關的元素
                var timeElementId = 'time' + basePinId.slice(3); // 假設 time ID 的格式是 'time' + 數字部分
                var timeElement = document.getElementById(timeElementId);
                if (timeElement) {
                    timeElement.disabled = true;
                }
            }
        });

         //針對已設定的事件option做反灰+disable
         if (Array.isArray(tempA)){
            tempA.forEach(function(element){
                var option = document.querySelector('#Event_Option option[value="' + element + '"]');
                if(option){
                    if (option.selected){
                        selectedValue = element;
                    }

                    option.disabled = true;
                    option.classList.add('disabled_input');
                }
            });
        }

      


        document.getElementById('new_output').style.display='block';
        var eventOption = document.getElementById('Event_Option');
        eventOption.addEventListener('change', function() {
            var selectedOptionId = eventOption.options[eventOption.selectedIndex].value;
            if(selectedOptionId == 7 || selectedOptionId == 8 || selectedOptionId == 9){
                toggleElementsInRange(1, 11, 2, true);
            }else{
                toggleElementsInRange(1, 11, 2, false);
            }

            for(let i = 1; i <= 11; i++) {
            let radioId = 'pin' + i + '_3';
            let radioElement = document.getElementById(radioId);
            
            if (radioElement) {
                radioElement.addEventListener('change', updateInputsBasedOnRadioSelection);
            }

            let tempC = tempA.slice();
            tempC.forEach(pin => {
                for (let i = 1; i <= 3; i++) {
                    let id = `pin${pin}_${i}`;
                    let element = document.getElementById(id);
                    if (element) {
                        element.disabled = true; 
                    }
                }
            });
        }

        
            
        }); 


        
    }

    //&& output_event != ''
    if (argument === 'edit' && job_id != '' && output_event != '') {
 
        var selectElement = document.getElementById('edit_event_option');
        if (selectElement) {
            selectElement.disabled = true;
            Array.from(selectElement.options).forEach(option => {
                option.disabled = true;
                option.classList.add('disabled_input');
            });
        }


        if (Array.isArray(temp)) { 
            temp.forEach(id => {
                var radio = document.getElementById(id);
                console.log(radio);

                if (radio && radio.type === 'radio') { 
                    radio.disabled = true; 
                }
            });

            let tempC = temp.slice(); 
        
   
            const filtered_C = tempC.filter(item => item.includes("edit_pin"));
            filtered_C.forEach(function(id) {
      
                var match = id.match(/(edit_pin\d+)_(\d+)/);
                if (match) {
                    var basePinId = match[1]; 
                    var pinNumber = match[2]; 

            
                    for (var i = 1; i <= 3; i++) {
                        var pinElementId = basePinId + "_" + i;
                        var pinElement = document.getElementById(pinElementId);
                        if (pinElement && pinElement.type === 'radio') {
                            pinElement.disabled = true;
                        }
                    }

                    // 禁用 edit_time 相關的元素
                    var timeElementId = 'edit_time' + basePinId.slice(3);
                    const toremove = "t_pin"; 
                    timeElementId = timeElementId.replace(toremove,'');
                    //console.log(timeElementId);
                    
                    var timeElement = document.getElementById(timeElementId);
                    if (timeElement) {
                        timeElement.disabled = true;
                    }
                }
            });



        }


        //該事件的pin的 所有radio 及 input 全部都要可以填
        if(output_pinval != ''){

            const idsToDisable = [
                `edit_pin${output_pinval}_1`,
                `edit_pin${output_pinval}_2`,
                `edit_pin${output_pinval}_3`,
                `edit_time${output_pinval}`
            ];

            idsToDisable.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.disabled = false;
                }
            });
            
        }


        get_output_info(job_id, output_event);
        document.getElementById('edit_output').style.display = 'block';
    }

    if(argument == 'copy' && job_id != '' && output_event != ''){

        var jobinfo = <?php echo json_encode($data['job_list_new']); ?>;
        var from_job_name_bk = jobinfo[job_id]['job_name'];

        document.getElementById("from_job_id").value = job_id;
        document.getElementById("from_job_name").value = from_job_name_bk;

        var selectElement = document.getElementById('JobSelect1');
        var options = selectElement.getElementsByTagName('option');

        for (var i = 0; i < options.length; i++) {
            var optionId = options[i].getAttribute('id');
            var optionValue = options[i].value;
            if(optionValue == job_id){
                options[i].disabled = true; 
                options[i].classList.add('disabled_input'); 
            }
        }
        document.getElementById('copy_output').style.display='block';
    }

    if(argument == 'unified' && job_id != ''){
        enableButton();
        resetBackgroundColor();
        if(output_job != job_id){
            alignsubmit(job_id);  
        }else{
            resetalignsubmit(job_id);
        }
        
    }
}

function collectPinValues(selector) {
    var pinOptions = document.querySelectorAll(selector);
    var selectedValues = [];

    pinOptions.forEach(function(option) {
        if (option.checked){ 
            var radioInfo = {
                id: option.id,
                value: option.value
            };
            selectedValues.push(radioInfo);
        }
    });

    return selectedValues;
}

function toggleElementsInRange(start, end, suffix, disable) {
    for (var i = start; i <= end; i++) {
        for (var j = 1; j <= suffix; j++) {
            var id = 'pin' + i + '_' + j;
            var element = document.getElementById(id);
            if (element) {
                element.disabled = disable;
            }
        }
    }
}


var old_output_event; 
var output_event;
function job_confirm(){
    var jobid = document.getElementById("JobNameSelect").value;
    localStorage.setItem("jobid", jobid);
    job_id = jobid;
    all_job = jobid;

    if(jobid){
        $.ajax({
            url: "?url=Outputs/get_output_by_job_id",
            method: "POST",
            data:{ 
                job_id: job_id,
            },
            success: function(response) {
                var data = JSON.parse(response);
                var job_outputlist = data.job_outputlist;
                temp = data.temp;
                tempA = data.tempA;


                document.getElementById("output_jobid_select").innerHTML = job_outputlist;
                document.getElementById("JobSelect").style.display = 'none';
                document.getElementById("job_id").value = job_id;
            
                var rows = document.querySelectorAll('#output_jobid_select tr');
                rows.forEach(function(row) {
                    row.addEventListener('click', function() { 

                        row.getAttribute('data-event');
                        output_event = row.getAttribute('data-event');
                   
 
                    });
                });

                var language = getCookie('language');
                if(language == "zh-cn"){
                    document.getElementById('1') && (document.getElementById('1').textContent = 'OK');
                    document.getElementById('2') && (document.getElementById('2').textContent = 'NG');
                    document.getElementById('3') && (document.getElementById('3').textContent = '超出上限');
                    document.getElementById('4') && (document.getElementById('4').textContent = '低于下限');
                    document.getElementById('5') && (document.getElementById('5').textContent = '工序完成信号');
                    document.getElementById('6') && (document.getElementById('6').textContent = '工作任务完成信号');
                    document.getElementById('7') && (document.getElementById('7').textContent = '马达信号');
                    document.getElementById('8') && (document.getElementById('8').textContent = '启动信号');
                    document.getElementById('9') && (document.getElementById('9').textContent = '拆螺丝');
                    document.getElementById('10') && (document.getElementById('10').textContent = 'BS');
                    document.getElementById('11') && (document.getElementById('11').textContent = '条码');
                    document.getElementById('12') && (document.getElementById('12').textContent = '自定义1');
                    document.getElementById('13') && (document.getElementById('13').textContent = '自定义2');
                    document.getElementById('14') && (document.getElementById('14').textContent = '自定义3');
                    document.getElementById('15') && (document.getElementById('15').textContent = '自定义4');
                    document.getElementById('16') && (document.getElementById('16').textContent = '自定义5');

                } 
                else if(language == "zh-tw"){
                    document.getElementById('1') && (document.getElementById('1').textContent = 'OK');
                    document.getElementById('2') && (document.getElementById('2').textContent = 'NG');
                    document.getElementById('3') && (document.getElementById('3').textContent = '超出上限');
                    document.getElementById('4') && (document.getElementById('4').textContent = '低於下限');
                    document.getElementById('5') && (document.getElementById('5').textContent = '工序完成信號');
                    document.getElementById('6') && (document.getElementById('6').textContent = '完工信號');
                    document.getElementById('7') && (document.getElementById('7').textContent = '馬達信號');
                    document.getElementById('8') && (document.getElementById('8').textContent = '啟動信號');
                    document.getElementById('9') && (document.getElementById('9').textContent = '拆螺絲');
                    document.getElementById('10') && (document.getElementById('10').textContent = 'BS');
                    document.getElementById('11') && (document.getElementById('11').textContent = '條碼');
                    document.getElementById('12') && (document.getElementById('12').textContent = '自定義1');
                    document.getElementById('13') && (document.getElementById('13').textContent = '自定義2');
                    document.getElementById('14') && (document.getElementById('14').textContent = '自定義3');
                    document.getElementById('15') && (document.getElementById('15').textContent = '自定義4');
                    document.getElementById('16') && (document.getElementById('16').textContent = '自定義5');
                }

            },
            error: function(xhr, status, error) {
            
            }
        });
    }
}

//delete
function delete_output_id(job_id,del_output_val){
    if(job_id){
        $.ajax({
            url: "?url=Outputs/delete_output",
            method: "POST",
            data: { 
                job_id: job_id,
                output_event: del_output_val,
             
            },
            success: function(response) {
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    get_output_by_job_id(job_id);
                });
            },
            error: function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
            }
        });     
    }   
}

function get_output_by_job_id(job_id){
    $.ajax({
        url: "?url=Outputs/get_output_by_job_id",
        method: "POST",
        data: { 
            job_id: job_id,
        },
        success: function(response) {
            var data = JSON.parse(response);
            var job_outputlist = data.job_outputlist;
            temp = data.temp;
            tempA = data.tempA;

            document.getElementById("output_jobid_select").innerHTML = job_outputlist;
            document.getElementById("JobSelect").style.display = 'none';
            document.getElementById("job_id").value = job_id;
        
            var rows = document.querySelectorAll('#output_jobid_select tr');
            rows.forEach(function(row) {
                row.addEventListener('click', function() { 
                    output_event = this.className; 
                });
            });

            
            var language = getCookie('language');
            if(language == "zh-cn"){
                document.getElementById('1') && (document.getElementById('1').textContent = 'OK');
                document.getElementById('2') && (document.getElementById('2').textContent = 'NG');
                document.getElementById('3') && (document.getElementById('3').textContent = '超出上限');
                document.getElementById('4') && (document.getElementById('4').textContent = '低于下限');
                document.getElementById('5') && (document.getElementById('5').textContent = '工序完成信号');
                document.getElementById('6') && (document.getElementById('6').textContent = '工作任务完成信号');
                document.getElementById('7') && (document.getElementById('7').textContent = '马达信号');
                document.getElementById('8') && (document.getElementById('8').textContent = '启动信号');
                document.getElementById('9') && (document.getElementById('9').textContent = '拆螺丝');
                document.getElementById('10') && (document.getElementById('10').textContent = 'BS');
                document.getElementById('11') && (document.getElementById('11').textContent = '条码');
                document.getElementById('12') && (document.getElementById('12').textContent = '自定义1');
                document.getElementById('13') && (document.getElementById('13').textContent = '自定义2');
                document.getElementById('14') && (document.getElementById('14').textContent = '自定义3');
                document.getElementById('15') && (document.getElementById('15').textContent = '自定义4');
                document.getElementById('16') && (document.getElementById('16').textContent = '自定义5');

            } 
            else if(language == "zh-tw"){
                document.getElementById('1') && (document.getElementById('1').textContent = 'OK');
                document.getElementById('2') && (document.getElementById('2').textContent = 'NG');
                document.getElementById('3') && (document.getElementById('3').textContent = '超出上限');
                document.getElementById('4') && (document.getElementById('4').textContent = '低於下限');
                document.getElementById('5') && (document.getElementById('5').textContent = '工序完成信號');
                document.getElementById('6') && (document.getElementById('6').textContent = '完工信號');
                document.getElementById('7') && (document.getElementById('7').textContent = '馬達信號');
                document.getElementById('8') && (document.getElementById('8').textContent = '啟動信號');
                document.getElementById('9') && (document.getElementById('9').textContent = '拆螺絲');
                document.getElementById('10') && (document.getElementById('10').textContent = 'BS');
                document.getElementById('11') && (document.getElementById('11').textContent = '條碼');
                document.getElementById('12') && (document.getElementById('12').textContent = '自定義1');
                document.getElementById('13') && (document.getElementById('13').textContent = '自定義2');
                document.getElementById('14') && (document.getElementById('14').textContent = '自定義3');
                document.getElementById('15') && (document.getElementById('15').textContent = '自定義4');
                document.getElementById('16') && (document.getElementById('16').textContent = '自定義5');
            }
            
        },
        error: function(xhr, status, error) {
            console.error("AJAX request failed:", status, error);
        }
    }); 

}

function collectPinValues(selector) {
    var pinOptions = document.querySelectorAll(selector);
    var selectedValues = [];

    pinOptions.forEach(function(option) {
        if (option.checked){ 
            var radioInfo = {
                id: option.id,
                value: option.value
            };
            selectedValues.push(radioInfo);
        }
    });

    return selectedValues;
}


function create_output_id() {
    var output_event = document.getElementById("Event_Option").value;
    var pinval = collectPinValues('input[name="pin_option"]');
  

    if (pinval.length > 0) {
        var pin_old = pinval[0]['id']; 
        var wave = pinval[0]['value'];
        
        var match = pin_old.match(/\d+/); 
        var output_pin = match ? parseInt(match[0]) : null;
        
        var time_ms = 'time'+ output_pin;
        var wave_on =  document.getElementById(time_ms).value;

        if (job_id) {
            $.ajax({
                url: "?url=Outputs/create_output_event",
                method: "POST",
                data: { 
                    job_id: job_id,
                    output_pin: output_pin,
                    output_event: output_event,
                    wave: wave,
                    wave_on: wave_on
                },
                success: function(response) {
                    var responseData = JSON.parse(response);
                    alertify.alert(responseData.res_type, responseData.res_msg, function() {
                        get_output_by_job_id(job_id);
                    });
                    document.getElementById('new_output').style.display = 'none';

                },
                error: function(xhr, status, error) {
                    console.error("AJAX request failed:", status, error);
                }
            });
        }
    } else {
        console.error("No pinval found or pinval[0] is undefined.");
    }
}

function edit_output_id(){
    var output_event = document.getElementById("edit_event_option").value;
    var pinval       = collectPinValues('input[name="edit_pin_option"]');
    var pin_old      = pinval[0]['id'];
    var wave         = pinval[0]['value'];
    var match        = pin_old.match(/\d+/); 
    var output_pin   = match ? parseInt(match[0]) : null;

    var time_ms = 'edit_time'+ output_pin;
    var wave_on =  document.getElementById(time_ms).value;
    if(job_id){
        $.ajax({
            url: "?url=Outputs/edit_output_event",
            method: "POST",
            data: { 
                job_id: job_id,
                output_pin: output_pin,
                output_event: output_event,
                wave: wave,
                wave_on: wave_on,
                old_output_event: old_output_event
            },
            success: function(response) {
                
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    get_output_by_job_id(job_id);
                });

                document.getElementById('edit_output').style.display='none';
                
            },
            error: function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
            }
        });         
    }
}
function resetalignsubmit(job_id) {

    var job_id_new = 0;
    if(job_id_new == 0){
        $.ajax({
            url: "?url=Outputs/output_alljob",
            method: "POST",
            data: {
                job_id_new: job_id_new
            },
            success: function (response) {
                get_output_by_job_id(job_id);
            },
            error: function (xhr, status, error) {

            }
        });

    }

}
function alignsubmit(job_id) {
    if (job_id) {
        $.ajax({
            url: "?url=Outputs/output_alljob",
            method: "POST",
            data: {
                job_id: job_id
            },
            success: function (response) {
                get_output_by_job_id(job_id);
            
                buttonDisabled = !buttonDisabled;
                document.getElementById('Button_Select').disabled = buttonDisabled;
     
                backgroundColorYellow = !backgroundColorYellow;
                if (backgroundColorYellow) {
                    document.getElementById('job_id').style.backgroundColor = 'yellow';
                } else {
                    document.getElementById('job_id').style.backgroundColor = '';
                }
            },
            error: function (xhr, status, error) {

            }
        });
    }
}
//copy
function copy_output_id(){

    var language = getCookie('language');
    if(language == "zh-cn"){
        var text_info ='若设定已存在，将会取代原有设定';
    }else if(language == "zh-tw"){
        var text_info ='若設定已存在，將會取代原有設定';
    }else{
        var text_info ='If the job input already exists, it will replace the original setting';
    }
    alertify.confirm( text_info, function (e) {
        if (e) {
            var to_job_id = document.getElementById("JobSelect1").value;
            if(to_job_id){
                $.ajax({
                    url: "?url=Outputs/copy_output",
                    method: "POST",
                    data: { 
                        from_job_id: job_id,
                        to_job_id: to_job_id
                    },
                    success: function(response) {

                        var responseData = JSON.parse(response);
                        alertify.alert(responseData.res_type, responseData.res_msg, function() {
                            get_output_by_job_id(job_id);
                        });

                        document.getElementById('copy_output').style.display='none';

                    },
                    error: function(xhr, status, error) {
                        
                    }
                });
        
            } 
        } else {
            // cancel
        }
    });
    document.getElementById('copy_output').style.display='none';
}

function updateInputsBasedOnRadioSelection() {
   
    for (let i = 1; i <= 11; i++) {
        let radioId = 'pin' + i + '_3';
        let inputId = 'time' + i;
        
        let radioElement = document.getElementById(radioId);
        let inputElement = document.getElementById(inputId);

        if (radioElement && inputElement) {
            inputElement.disabled = !radioElement.checked;
        }
    }
}

function get_output_info(job_id,output_event){

    if(job_id){
     $.ajax({
             url: "?url=Outputs/check_job_event",
             method: "POST",
             data: { 
                 job_id: job_id,
                 output_event: output_event
             },
             success: function(response) {
              
                var responseJSON = JSON.stringify(response);
                var cleanString = responseJSON.replace(/Array|\\n/g, '');
                var cleanString = cleanString.substring(2, cleanString.length - 2);
                var [, job_id] = cleanString.match(/\[output_job_id]\s*=>\s*([^ ]+)/) || [, null];
                var [, output_event] = cleanString.match(/\[output_event]\s*=>\s*([^ ]+)/) || [, null];
                var [, output_pin] = cleanString.match(/\[output_pin]\s*=>\s*([^ ]+)/) || [, null];
                var [, wave] = cleanString.match(/\[wave]\s*=>\s*([^ ]+)/) || [, null];
                var [, wave_on] = cleanString.match(/\[wave_on]\s*=>\s*([^ ]+)/) || [, null];

                var edit_output_pin = "edit_pin" + output_pin + "_"+ wave;
                var radioButton = document.getElementById(edit_output_pin);
                radioButton.removeAttribute('disabled');

                var time_ms = 'edit_time'+ output_pin;

                if(wave != 2){
                    var time_id = 'edit_time' + output_pin;
                    var element = document.getElementById(time_id);
                    
                    if(element){
                        element.disabled = true
                    }
                }


                    
                //完工信號 && 馬達信號 && 啟動信號
                if (output_event == 8  || output_event == 6 || output_event == 7 ) {
                    for(let i = 1; i <= 11; i++) {
                        let element1 = document.getElementById(`edit_pin${i}_1`);
                        if (element1) {
                            element1.disabled = true;
                        }
                
                        let element2 = document.getElementById(`edit_pin${i}_2`);
                        if (element2) {
                            element2.disabled = true;
                        }
                    }

                    if (Array.isArray(temp)) {
                        //過濾出包含 "edit_pin" 的字串
                        const filteredArray = temp.filter(item => item.includes("edit_pin"));
                        
                        const updatedArray = filteredArray.map(item => {
                            // 如果字串為空，直接返回
                            if (item.length === 0) {
                                return item;
                            }
                            //強制字串的最後一個字元更換為 '3'
                            return item.slice(0, -1) + '3';
                        });
                        
                        console.log("Updated Array:", updatedArray);
                        updatedArray.forEach(item => {
                            const radio = document.getElementById(item);
                            if (radio && radio.type === 'radio') {
                                radio.disabled = true;
                            }
                        });

                    }
                    
                    

                    
                   
              
                }

                 document.getElementById(time_ms).value = wave_on;
 
                 old_output_even = output_event;
 
                 if(radioButton){
                     radioButton.checked = true;
                 }
                 
                 document.querySelector("select[name='edit_event_option']").value = output_event;
                 document.getElementById("edit_event_option").onchange = function() {
                  var selectedValue = this.value; 
                 };
             },
             error: function(xhr, status, error) {
                 console.error("AJAX request failed:", status, error);
             }
     });      
    }
  
}

function toggleOnputTime(inputId, checked, option) {
    var inputElement = document.getElementById(inputId);
    
    if (!inputElement) {
        console.error(`Element with ID '${inputId}' not found.`);
        return; // Exit if element is not found
    }

   
    if (inputElement.type === 'checkbox' || inputElement.type === 'radio') {

        if (inputElement.checked !== checked) {
            console.warn(`The checked state of the element with ID '${inputId}' does not match the provided 'checked' value.`);
        }
    }

    
    if (option != '2') {
        var newId = inputId.replace(/^pin(\d+)_\d+$/, 'time$1');
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = true;
        }
        
    } else { 
        var newId = inputId.replace(/^pin(\d+)_\d+$/, 'time$1');
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = false;
        }
    }
}

function toggleOnputTime_edit(inputId, checked, option) {
    var inputElement = document.getElementById(inputId);
    
    if (!inputElement) {
        console.error(`Element with ID '${inputId}' not found.`);
        return; // Exit if element is not found
    }

   
    if (inputElement.type === 'checkbox' || inputElement.type === 'radio') {

        if (inputElement.checked !== checked) {
            console.warn(`The checked state of the element with ID '${inputId}' does not match the provided 'checked' value.`);
        }
    }

    
    if (option != '2') {
        var newId = inputId.replace(/^edit_pin(\d+)_\d+$/, 'edit_time$1');
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = true;
        }
        
    } else { 
        var newId = inputId.replace(/^edit_pin(\d+)_\d+$/, 'edit_time$1');
        var element = document.getElementById(newId);
        if (element) {
            element.disabled = false;
        }
    }
}


</script>
<style>
    #output_table td,
    #output_table th {
        width: 100px; 
        padding: 10px;
    }
</style>