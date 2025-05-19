

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_input_m.css" type="text/css">
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['input'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
     </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:3vmin;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="8" maxlength="20" value="1" disabled style="height:30px; font-size:3vmin;text-align: center; background-color: #DDDDDD; border:0;">&nbsp;&nbsp;
                <button id="Button_Select" type="button" onclick="document.getElementById('JobSelect').style.display='block'"><?php echo $text['select'];?></button>
            </div>

            <!-- Job Select Modal -->
            <div id="JobSelect" class="modal" style="width: 325px;">
                <form class="w3-modal-content w3-animate-zoom" style="top: 13%;" action="">
                    <div class="w3-light-grey">
                        <header class="w3-container w3-dark-grey" style="height: 48px">
                            <span onclick="document.getElementById('JobSelect').style.display='none'" class="w3-button w3-red w3-large w3-display-topright" style="margin: 2px">&times;</span>
                            <h3 style="margin: 5px" onclick="get_job_list()"><?php echo $text['job_select'];?></h3>
                        </header>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-2 t2" style="margin-left: 3px">
                                    <select style="margin: center" id="JobNameSelect" name="JobNameSelect" size="200">
                                        <?php foreach($data['job_list'] as $key =>$val){?>
                                            <option value="<?php echo $val['JOBID'];?>"><?php echo $val['JOBname'];?></option>
                                        <?php }?>                                                                                                                             
                                    </select>
                                </div>
                            </div>
                        </div>    
                    </div>
                    <div class="modal-footer justify-content-center w3-dark-grey" style="height: 48px">
                        <button id="select_confirm" type="button" class="btn btn-primary" onclick="job_confirm()"><?php echo $text['confirm'];?></button>
                        <button id="select_close" type="button" class="btn btn-secondary" onclick="document.getElementById('JobSelect').style.display='none'" ><?php echo $text['close'];?></button>
                    </div>
                </form>
            </div>
            <div id="DivMode">
                <!-- Table Input -->
                <div id="TableInputSetting" class="table-container">
                    <div class="scrollbar-inputtable" id="style-inputtable">
                        <div class="force-overflow-inputtable">
                            <table id="input_table" class="table w3-table-all w3-hoverable">
                                <thead id="header-table">
                                    <tr class="w3-dark-grey" style="font-size: 2.6vmin">
                                        <th width="60%"><?php echo $text['event'];?></th>
                                        <th style="display: none;">2</th>
                                        <th style="display: none;">3</th>
                                        <th style="display: none;">4</th>
                                        <th style="display: none;">5</th>
                                        <th style="display: none;">6</th>
                                        <th style="display: none;">7</th>
                                        <th style="display: none;">8</th>
                                        <th style="display: none;">9</th>
                                        <th style="display: none;">10</th>
                                        <th style="display: none;">11</th>
                                        <th style="display: none;">12</th>
                                        <th width="20%">Pin</th>
                                        <th width="20%"></th>
                                    </tr>
                                </thead>

                                <tbody id="input_jobid_select"  style="font-size: 2.5vmin;text-align: center;">
                                   
                                   
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="footer" id='input_menu'>
                        <div class="buttonbox">
                            <?php $buttonstatus = count($data['job_list_new']) ==  0 ? 'disabled' : ''; ?>
                            <input id="S1" name="New_Submit" type="button" value="<?php echo $text['New'];?>" tabindex="1" onclick="crud_job_event('new')">
                            <input id="S2" name="Edit_Submit" type="button" value="<?php echo $text['Edit'];?>" tabindex="1" onclick="crud_job_event('edit')">
                            <input id="S3" name="Copy_Submit" type="button" value="<?php echo $text['Copy'];?>" tabindex="1" onclick="crud_job_event('copy')" <?php echo $buttonstatus;?>>
                            <input id="S4" name="Delete_Submit" type="button" value="<?php echo $text['Delete'];?>" tabindex="1" onclick="crud_job_event('del')">
                            <input id="S5" name="Table_Submit" type="button" value="<?php echo $text['Table'];?>" tabindex="1" onclick="tablesubmit('show')">
                            <input id="S6" name="Align_Submit" type="button" value="<?php echo $text['Align'];?>" tabindex="1" onclick="crud_job_event('unified')" >
                        </div>
                    </div>
                </div>

                <!-- Table Data Information -->
                <div id="TableDataInput" style="display: none" class="table-container">
                    <div id="Event_List" style="margin-top: 10px;background-color: #F2F2D9;">
                        <div class="w3-border-bottom" style="font-size: 20px;">Event List</div>
                        <table class="table w3-table-all w3-hoverable" style="font-size: 2vmin">
                            <tr>
                                <td class="w3-left-align">1-50 SW Job ID</td>
                                <td class="w3-left-align">101 <?php echo $text['disable'];?></td>
                                <td class="w3-left-align">102 <?php echo $text['enable'];?></td>
                            </tr>
                            <tr>
                                <td class="w3-left-align">103 <?php echo $text['Clear'];?></td>
                                <td class="w3-left-align">104 <?php echo $text['Confirm'];?></td>                             
                                <td class="w3-left-align">105 <?php echo $text['Start-IN(Remote)'];?></td>
                            </tr>
                            <tr>
                                <td class="w3-left-align">106 <?php echo $text['Unscrew(Remote)'];?></td>
                                <td class="w3-left-align">107 <?php echo $text['Sequence Clear'];?></td>
                                <td class="w3-left-align">108 <?php echo $text['Reboot'];?></td>
                            </tr>
                            <tr>
                                <td class="w3-left-align">109 <?php echo $text['Gate Once'];?></td>
                                <td class="w3-left-align">110 <?php echo $text['UDEFINE'];?>1</td>
                                <td class="w3-left-align">111 <?php echo $text['UDEFINE'];?>2</td>
                            </tr>
                            <tr>
                                <td class="w3-left-align">112 <?php echo $text['UDEFINE'];?>3</td>
                                <td class="w3-left-align">113 <?php echo $text['UDEFINE'];?>4</td>
                                <td class="w3-left-align">114 <?php echo $text['UDEFINE'];?>5</td>
                            </tr>
                        </table>                            
                    </div>
                    <div class="center">
                        <button id="button_Close" class="button" onclick="showTableInputSetting()"><?php echo $text['close'];?></button>
                    </div>
                </div>
            </div>

            <!-- Add New Input -->
            <div id="newinput" class="modal">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content w3-animate-zoom" style="width: 90%">
                        <header class="w3-container modal-header">
                            <span onclick="document.getElementById('newinput').style.display='none'"
                                class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                            <h3 id='modal_title'><?php echo $text['new_event'];?></h3>
                        </header>

                        <div class="modal-body">
                            <form id="new_input_form" style="padding-left: 5%">
                                <div class="row">
                                    <div for="event" class="col-3 t1"><?php echo $text['event'];?> :</div>
                                    <div class="col-2 t2">
                                        <select id="Event_Option" name ="Event_Option" class="col custom-file">
                                        <option value="-1" disabled selected><?php echo $text['Choose_option']; ?></option>
                                                <?php foreach($data['event'] as $key =>$val){?>
                                                    <option value ='<?php echo $key;?>'><?php echo $text[$val];?></option>
                                                <?php } ?>
                                        
                                        </select>
                                    </div>
                                </div>

                                <?php for($i = 2; $i <= 12; $i++){?>     
                                        <div class="row input-pin">
                                            <div class="col-2 t1" style="margin-left: 5%"><?php echo $i; ?>:</div>
                                            <div class="col t2">
                                                <div class="col-4 form-check form-check-inline">
                                                    <input class="zoom form-check-input" type="radio" name="pin_option" id="pin<?php echo $i; ?>_high" value="1">
                                                    <label class="form-check-label" for="pin<?php echo $i; ?>_high"><img src="./img/high.png"></label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="zoom form-check-input" type="radio" name="pin_option" id="pin<?php echo $i; ?>_low" value="2">
                                                    <label class="form-check-label" for="pin<?php echo $i; ?>_low"><img src="./img/low.png"></label>
                                                </div>
                                            </div>
                                        </div>
                                <?php } ?>
                                <div id="work_goc" style="display: none;">
                                <div class="row" style="display: flex; align-items: center;">
                                    <div class="col t1"><?php echo $text['gate_confirm'];?>:</div>
                                    <div class="col t2">
                                                <div class="col form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="gateconfirm" id="gateconfirm_0" 
                                                        value="0" checked="">
                                                        <label class="form-check-label"><?php echo $text['NO'];?></label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                        <input class="form-check-input" type="radio" name="gateconfirm" id="gateconfirm_1" 
                                                        value="1">
                                                        <label class="form-check-label"><?php echo $text['YES'];?></label>
                                                    </div>
                                        </div>
                                </div>
                                </div>


                                

                            </form>
                        </div>

                        <div class="modal-footer justify-content-center">
                            <button id="" class="button-modal" onclick="create_input_id()"><?php echo $text['save'];?></button>
                            <button id="" class="button-modal" onclick="document.getElementById('newinput').style.display='none'" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Edit Input -->
            <div id="edit_input" class="modal">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content w3-animate-zoom" style="width: 90%">
                        <header class="w3-container modal-header">
                            <span onclick="document.getElementById('edit_input').style.display='none'"
                                class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                            <h3 id='modal_title'><?php echo $text['edit_event'];?></h3>
                        </header>

                        <div class="modal-body">
                            <form id="new_input_form" style="padding-left: 5%">
                                <div class="row">
                                    <div for="event" class="col-3 t1"><?php echo $text['event'];?>:</div>
                                    <div class="col-2 t2">
                                        <select id="edit_Event_Option" name ="edit_Event_Option" class="col custom-file">
                                                <?php foreach($data['event'] as $key =>$val){?>
                                                    <option value ='<?php echo $key;?>'><?php echo $text[$val];?></option>
                                                <?php } ?>
                                        </select>
                                    </div>
                                </div>

                                <?php for ($i = 2; $i <= 12; $i++){?>
                                    <div class="row input-pin">
                                        <div class="col-2 t1" style="margin-left: 5%"><?php echo $i; ?>:</div>
                                        <div class="col t2">
                                            <div class="col-4 form-check form-check-inline">
                                                <input class="zoom form-check-input" type="radio" name="edit_pin_option" id="edit_pin<?php echo $i; ?>_high" value="1">
                                                <label class="form-check-label" for="edit_pin<?php echo $i; ?>_high"><img src="./img/high.png"></label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="zoom form-check-input" type="radio" name="edit_pin_option" id="edit_pin<?php echo $i; ?>_low" value="2">
                                                <label class="form-check-label" for="edit_pin<?php echo $i; ?>_low"><img src="./img/low.png"></label>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <div id="edit_work_goc" style="display: none;">
                                    <div class="row" style="display: flex; align-items: center;">
                                        <div class="col t1"><?php echo $text['gate_confirm'];?>:</div>
                                        <div class="col t2">
                                            <div class="col form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="edit_gateconfirm" id="edit_gateconfirm_0" value="0">
                                                <label class="form-check-label"><?php echo $text['NO'];?></label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="edit_gateconfirm" id="edit_gateconfirm_1"  value="1">
                                                <label class="form-check-label"><?php echo $text['YES'];?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer justify-content-center">
                            <button id="" class="button-modal" onclick="edit_input_id()"><?php echo $text['save'];?></button>
                            <button id="" class="button-modal" onclick="document.getElementById('edit_input').style.display='none'" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copy Input -->
            <div id="copyinput" class="modal">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content w3-animate-zoom" style="width: auto">
                        <header class="w3-container modal-header">
                            <span onclick="document.getElementById('copyinput').style.display='none'"
                                class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                            <h3 id='modal_title'><?php echo $text['copy_input'];?></h3>
                        </header>

                        <div class="modal-body">
                            <form id="new_seq_form">
                	            <label for="from_job_id" class="col col-form-label" style="font-weight: bold;padding-left: 5%;"><?php echo $text['copy_from'];?></label>
                	            <div style="padding-left: 10%;">
                		            <div class="row">
                				        <label for="from_job_id" class="t1 col-4 col-form-label"><?php echo $text['job_id'];?> :</label>
                				        <div class="col-5 t2 ">
                				            <input type="number" class="form-control" id="from_job_id" disabled>
                				        </div>

                				        <label for="from_job_name" class="t1 col-4 col-form-label"><?php echo $text['job_name'];?> :</label>
                				        <div class="col-5 t2 ">
                				            <input type="text" class="form-control" id="from_job_name"  disabled>
                				        </div>
                				    </div>
                			    </div>

                			    <label for="from_job_id" class="col col-form-label" style="font-weight: bold;padding-left: 5%;"><?php echo $text['copy_to'];?></label>
                			    <div style="padding-left: 10%">
                				    <div class="row">
                				        <label for="to_step_id" class="t1 col-4 col-form-label"><?php echo $text['job'];?> :</label>
                				        <div class="t2 col-6">
                                            <select id="JobSelect1" class="col custom-file" style="margin: center; width: 153px">
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
                            <button id="" class="button-modal" onclick="copy_input_id()"><?php echo $text['save'];?></button>
                            <button id="" class="button-modal" onclick="document.getElementById('copyinput').style.display='none'" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>


var job_id; 
var input_event;
var temp;
var tempA;
var tempB;
var selectedValue;
var old_input_event;
var all_job;
var buttonDisabled = false;
var backgroundColorYellow = false;
var input_job;
var temp_event;

$(document).ready(function () {
    highlight_row_input('input_table');
 
    var all_input_job = '<?php echo $data['device_data']['device_input_all_job']?>';
    job_id = all_input_job;
    input_job = all_input_job;
    if(job_id){
        get_input_by_job_id(job_id);
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

document.getElementById("Event_Option").onchange = function() {
    var selectedValue = this.value; 
    handleEventChange(selectedValue); 
};

// Div Mode
function toggleDivs() {
    var tableInputSetting = document.getElementById('TableInputSetting');
    var tableDataInput = document.getElementById('TableDataInput');

    if (tableInputSetting.style.display === 'none') {
        tableInputSetting.style.display = 'block';
        tableDataInput.style.display = 'none';
    } else {
        tableInputSetting.style.display = 'none';
        tableDataInput.style.display = 'block';
    }
}

function showTableInputSetting() {
    document.getElementById('TableInputSetting').style.display = 'block';
    document.getElementById('TableDataInput').style.display = 'none';

    document.getElementById('input_menu').style.display = 'block';
}

// Get the modal
var modal = document.getElementById('newinput');


window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}





function job_confirm(){
    var jobid = document.getElementById("JobNameSelect").value;
    localStorage.setItem("jobid", jobid);
    job_id = jobid;
    all_job = jobid;
    

    if(jobid){
        $.ajax({
            url: "?url=Inputs/get_input_by_job_id",
            method: "POST",
            data:{ 
                jobid: jobid,
            },
            success: function(response) {
                var data = JSON.parse(response);
                var job_inputlist = data.job_inputlist;
                temp = data.temp;
                tempA = data.tempA;
                temp_event = data.temp_event;

                document.getElementById("input_jobid_select").innerHTML = job_inputlist;
                document.getElementById("JobSelect").style.display = 'none';
                document.getElementById("job_id").value = jobid;

                var s3Button = document.getElementById('S3');
          
            
                var rows = document.querySelectorAll('#input_jobid_select tr');
                rows.forEach(function(row) {
                    row.addEventListener('click', function() { 
                        input_event = this.className; 
                        old_input_event = this.className;
                    
                    });
                });

                var language = getCookie('language');

                if(language == "zh-cn"){

                    document.getElementById('101') && (document.getElementById('101').textContent = '禁用');
                    document.getElementById('102') && (document.getElementById('102').textContent = '启用');
                    document.getElementById('103') && (document.getElementById('103').textContent = '颗数清除');
                    document.getElementById('104') && (document.getElementById('104').textContent = '确认');
                    document.getElementById('105') && (document.getElementById('105').textContent = '启动');
                    document.getElementById('106') && (document.getElementById('106').textContent = '拆螺丝');
                    document.getElementById('107') && (document.getElementById('107').textContent = '工序清除');
                    document.getElementById('108') && (document.getElementById('108').textContent = '重启');
                    document.getElementById('109') && (document.getElementById('109').textContent = '一次感应');
                    document.getElementById('110') && (document.getElementById('110').textContent = '自定义1');
                    document.getElementById('111') && (document.getElementById('111').textContent = '自定义2');
                    document.getElementById('112') && (document.getElementById('112').textContent = '自定义3');
                    document.getElementById('113') && (document.getElementById('113').textContent = '自定义4');
                    document.getElementById('114') && (document.getElementById('114').textContent = '自定义5');
                
                }else if(language =="zh-tw"){
                    document.getElementById('101') && (document.getElementById('101').textContent = '禁用');
                    document.getElementById('102') && (document.getElementById('102').textContent = 'Enable');
                    document.getElementById('103') && (document.getElementById('103').textContent = '清除顆數');
                    document.getElementById('104') && (document.getElementById('104').textContent = '確認');
                    document.getElementById('105') && (document.getElementById('105').textContent = '啟動');
                    document.getElementById('106') && (document.getElementById('106').textContent = '拆螺絲');
                    document.getElementById('107') && (document.getElementById('107').textContent = '工序清除');
                    document.getElementById('108') && (document.getElementById('108').textContent = '重啟');
                    document.getElementById('109') && (document.getElementById('109').textContent = '一次感應');
                    document.getElementById('110') && (document.getElementById('110').textContent = '自定義1');
                    document.getElementById('111') && (document.getElementById('111').textContent = '自定義2');
                    document.getElementById('112') && (document.getElementById('112').textContent = '自定義3');
                    document.getElementById('113') && (document.getElementById('113').textContent = '自定義4');
                    document.getElementById('114') && (document.getElementById('114').textContent = '自定義5');
                }


            },
            error: function(xhr, status, error) {
            
            }
        }); 
    }
}


//create
function create_input_id(){
 
    var input_event = document.getElementById("Event_Option").value;
    var pinval      = collectPinValues('input[name="pin_option"]');
    var pin_old   = pinval[0]['id'];
    var input_wave  = pinval[0]['value'];
    var pagemode    = 1;
    var input_seqid = 0;

    if(input_event == 109){
        var selectedOption = document.querySelector('input[name="gateconfirm"]:checked');
        var gateconfirm    = selectedOption ? selectedOption.value : 0;
    }else{
        var gateconfirm	 = 0;
    }


    var input_pin = pin_old.match(/\d+/)[0];
    if(job_id){
        $.ajax({
            url: "?url=Inputs/create_input_event",
            method: "POST",
            data: { 
                job_id: job_id,
                input_event: input_event,
                input_pin: 	input_pin,
                input_wave: input_wave,
                gateconfirm: gateconfirm,
                pagemode: pagemode,
                input_seqid: input_seqid
            },
            success: function(response) {

                document.getElementById('newinput').style.display='none';
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    get_input_by_job_id(job_id);
                });
            },
            error: function(xhr, status, error) {
                
            }
        });

    }
}

//copy
function copy_input_id(){
    var language = getCookie('language');
    if(language == "zh-cn"){
        var text_info ='若设定已存在，将会取代原有设定';
    }else if(language == "zh-tw"){
        var text_info ='若設定已存在，將會取代原有設定';
    }else{
        var text_info ='If the job input already exists, it will replace the original setting';
    }
    alertify.confirm( text_info , function (e) {
        if (e) {
            var to_job_id = document.getElementById("JobSelect1").value;
            if(to_job_id){
                $.ajax({
                    url: "?url=Inputs/copy_input_event",
                    method: "POST",
                    data: { 
                        from_job_id: job_id,
                        to_job_id: to_job_id
                    },
                    success: function(response) {
                        
                        document.getElementById('copyinput').style.display='none';
                        var responseData = JSON.parse(response);
                        alertify.alert(responseData.res_type, responseData.res_msg, function() {
                            get_input_by_job_id(job_id);
                        });

                        
                    },
                    error: function(xhr, status, error) {
                        
                    }
                });
        
            }

        } else {
            // cancel
        }
    });

}

function resetalignsubmit(job_id) {

    var job_id_new = 0;

    if(job_id_new == 0){
        console.log(job_id_new);
        console.log(job_id);
        $.ajax({
            url: "?url=Inputs/input_alljob_cancel",
            method: "POST",
            data: {
                job_id_new: job_id_new
            },
            success: function (response) {
                get_input_by_job_id(job_id);
            },
            error: function (xhr, status, error) {

            }
        });
    }
}

function alignsubmit(job_id) {
    if (job_id) {
        $.ajax({
            url: "?url=Inputs/input_alljob",
            method: "POST",
            data: {
                job_id: job_id
            },
            success: function (response) {
                get_input_by_job_id(job_id);
                buttonDisabled = !buttonDisabled;
                document.getElementById('Button_Select').disabled = buttonDisabled;
     
                backgroundColorYellow = !backgroundColorYellow;
                if (backgroundColorYellow){
                    document.getElementById('job_id').style.backgroundColor = 'yellow';
                }else{
                    document.getElementById('job_id').style.backgroundColor = '';
                }
            },
            error: function (xhr, status, error) {

            }
        });
    }
}




function get_input_info(){

    if(job_id){
        $.ajax({
            url: "?url=Inputs/check_job_event_conflict",
            method: "POST",
            data: { 
                job_id: job_id,
                input_event: input_event,
            },
            success: function(response) {
                if (response === 'no_data') {
                    getLanguageMessage('language');
                    return;
                }

                document.getElementById('edit_input').style.display='block';  


                var responseJSON = JSON.stringify(response);
                var cleanString = responseJSON.replace(/Array|\\n/g, '');
                var cleanString = cleanString.substring(2, cleanString.length - 2);

                var [, jobid] = cleanString.match(/\[JOBID]\s*=>\s*([^ ]+)/) || [, null];
                var [, input_event] = cleanString.match(/\[EvenID]\s*=>\s*([^ ]+)/) || [, null];
                var [, input_pin] = cleanString.match(/\[Pin]\s*=>\s*([^ ]+)/) || [, null];
                var [, input_wave] = cleanString.match(/\[signal]\s*=>\s*([^ ]+)/) || [, null];
                var [, gateconfirm] = cleanString.match(/\[Wp_Ready_Confirm]\s*=>\s*([^ ]+)/) || [, null];

        
                if(input_wave == 1){
                    var wave = "_high";
                }else{
                    var wave = "_low";
                }
                
                var edit_input_pin = "edit_pin" + input_pin + wave;
                var radioButton = document.getElementById(edit_input_pin);
                radioButton.removeAttribute('disabled');
                old_input_event = input_event;
                
                if(radioButton){
                    radioButton.checked = true;
                    if(wave == '_high'){
                        var nstr = "edit_pin" + input_pin + '_low';
                    }else{
                        var nstr = "edit_pin" + input_pin + '_high';
                    }
                    var element = document.getElementById(nstr);
                    if(element){
                        element.disabled = false; 
                    } 
                }

                if(input_event != 109){
                    document.getElementById('edit_work_goc').style.display = 'none';
                }else{

                    document.getElementById('edit_work_goc').style.display = 'block';

                    if(gateconfirm == 1){
                        document.getElementById("edit_gateconfirm_1").checked = true;
                    }

                    if(gateconfirm == 0){
                        document.getElementById("edit_gateconfirm_0").checked = true;
                    }

                }
                
                document.querySelector("select[name='edit_Event_Option']").value = input_event;

                document.getElementById("edit_Event_Option").onchange = function() {
                    var selectedValue = this.value; 
                    edit_handleEventChange(selectedValue,gateconfirm); 
                };

             
            },
            error: function(xhr, status, error) {
                
            }
        });
   
        
    }

}

function get_input_by_job_id(jobid){
    $.ajax({
        url: "?url=Inputs/get_input_by_job_id",
        method: "POST",
        data: { 
            jobid: jobid,
        },
        success: function(response) {

            var data = JSON.parse(response);
            var job_inputlist = data.job_inputlist;
            temp = data.temp;
            tempA = data.tempA;

            document.getElementById("input_jobid_select").innerHTML = job_inputlist;
            document.getElementById("JobSelect").style.display = 'none';
            document.getElementById("job_id").value = jobid;
        
            var rows = document.querySelectorAll('#input_jobid_select tr');
            rows.forEach(function(row) {
                row.addEventListener('click', function() { 
                    input_event = this.className; 
                });
            });

            var language = getCookie('language');
                if(language == "zh-cn"){

                    document.getElementById('101') && (document.getElementById('101').textContent = '禁用');
                    document.getElementById('102') && (document.getElementById('102').textContent = '启用');
                    document.getElementById('103') && (document.getElementById('103').textContent = '颗数清除');
                    document.getElementById('104') && (document.getElementById('104').textContent = '确认');
                    document.getElementById('105') && (document.getElementById('105').textContent = '启动');
                    document.getElementById('106') && (document.getElementById('106').textContent = '拆螺丝');
                    document.getElementById('107') && (document.getElementById('107').textContent = '工序清除');
                    document.getElementById('108') && (document.getElementById('108').textContent = '重启');
                    document.getElementById('109') && (document.getElementById('109').textContent = '一次感应');
                    document.getElementById('110') && (document.getElementById('110').textContent = '自定义1');
                    document.getElementById('111') && (document.getElementById('111').textContent = '自定义2');
                    document.getElementById('112') && (document.getElementById('112').textContent = '自定义3');
                    document.getElementById('113') && (document.getElementById('113').textContent = '自定义4');
                    document.getElementById('114') && (document.getElementById('114').textContent = '自定义5');
                
                }else if(language =="zh-tw"){
                    document.getElementById('101') && (document.getElementById('101').textContent = '禁用');
                    document.getElementById('102') && (document.getElementById('102').textContent = 'Enable');
                    document.getElementById('103') && (document.getElementById('103').textContent = '清除顆數');
                    document.getElementById('104') && (document.getElementById('104').textContent = '確認');
                    document.getElementById('105') && (document.getElementById('105').textContent = '啟動');
                    document.getElementById('106') && (document.getElementById('106').textContent = '拆螺絲');
                    document.getElementById('107') && (document.getElementById('107').textContent = '工序清除');
                    document.getElementById('108') && (document.getElementById('108').textContent = '重啟');
                    document.getElementById('109') && (document.getElementById('109').textContent = '一次感應');
                    document.getElementById('110') && (document.getElementById('110').textContent = '自定義1');
                    document.getElementById('111') && (document.getElementById('111').textContent = '自定義2');
                    document.getElementById('112') && (document.getElementById('112').textContent = '自定義3');
                    document.getElementById('113') && (document.getElementById('113').textContent = '自定義4');
                    document.getElementById('114') && (document.getElementById('114').textContent = '自定義5');
                }

        },
        error: function(xhr, status, error) {
            console.error("AJAX request failed:", status, error);
        }
    }); 
}




</script>

<?php require_once '../app/views/input/input_share.php';?>


<style>
    #input_table td,
    #input_table th {
        width: 100px; 
        padding: 10px;
    }
</style>