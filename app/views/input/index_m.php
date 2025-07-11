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
                <label style="font-size:3.2vmin;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="8" maxlength="20" value="1" disabled style="height:30px; font-size:3.2vmin;text-align: center; background-color: #DDDDDD; border:0;">&nbsp;&nbsp;
                <button id="Button_Select" class="w3-button w3-border w3-round-large" type="button" onclick="document.getElementById('JobSelect').style.display='block'"><?php echo $text['select'];?></button>
            </div>

            <!-- Job Select Modal -->
            <div id="JobSelect" class="modal" style="width: 70%; top: 13%;">
                <div class="modal-dialog modal-lg">
                    <form class="modal-content w3-animate-zoom" action="">
                        <div class="w3-light-grey">
                            <header class="w3-container w3-dark-grey" style="height: 48px">
                                <span onclick="document.getElementById('JobSelect').style.display='none'" class="w3-button w3-red w3-large w3-display-topright" style="margin: 2px">&times;</span>
                                <h3 style="margin: 5px" onclick="get_job_list()"><?php echo $text['job_select'];?></h3>
                            </header>
                            <div class="modal-body">
                               <div class="modal-body">
                                    <div class="row">
                                        <div class="col-12 t2 px-3"> <!-- col-12 cho toàn dòng, px-3 để có khoảng cách ngang -->
                                            <select id="JobNameSelect" name="JobNameSelect">
                                                <?php foreach($data['job_list'] as $key => $val) { ?>
                                                    <option value="<?php echo $val['JOBID']; ?>"><?php echo $val['JOBname']; ?></option>
                                                <?php } ?>
                                            </select>
                                        </div>
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
            </div>

            <div id="DivMode">
                <!-- Table Input -->
                <div id="TableInputSetting" class="table-container">
                    <div class="scrollbar-inputtable" id="style-inputtable">
                        <div class="force-overflow-inputtable">
                            <table id="input_table" class="table w3-table">
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

                                <tbody id="input_jobid_select"  style="font-size: 2.6vmin;text-align: center;">
                                   
                                   
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
                        <table class="table w3-table-all w3-hoverable" style="font-size: 2.5vmin">
                            <tr>
                                <td class="w3-left-align">1-50 SW Job ID</td>
                                <td class="w3-left-align">101 <?php echo $text['disable'];?></td>
                                <td class="w3-left-align">102 <?php echo $text['enable'];?></td>
                            </tr>
                            <tr>
                                <td class="w3-left-align">103 <?php echo $text['Clear'];?></td>
                                <td class="w3-left-align">104 <?php echo $text['Confirm'];?></td>                             
                                <td class="w3-left-align">105 <?php echo $text['Start-IN'];?></td>
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
                            <span onclick="closebutton('newinput')"
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
                                                <input class="form-check-input" type="radio" name="gateconfirm" id="gateconfirm_0" value="0" checked="">
                                                <label class="form-check-label"><?php echo $text['NO'];?></label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="gateconfirm" id="gateconfirm_1" value="1">
                                                <label class="form-check-label"><?php echo $text['YES'];?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer justify-content-center">
                            <button id="" class="button-modal" onclick="create_input_id()"><?php echo $text['save'];?></button>
                            <button id="" class="button-modal" onclick="closebutton('newinput')" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Edit Input -->
            <div id="edit_input" class="modal">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content w3-animate-zoom" style="width: 90%">
                        <header class="w3-container modal-header">
                            <span onclick="closebutton('edit_input')"
                                class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                            <h3 id='modal_title'><?php echo $text['edit_event'];?></h3>
                        </header>

                        <div class="modal-body">
                            <form id="new_input_form" style="padding-left: 5%">
                                <div class="row">
                                    <div for="event" class="col-3 t1"><?php echo $text['event'];?>:</div>
                                    <div class="col-2 t2">
                                        <select id="edit_Event_Option" name ="edit_Event_Option" class="col custom-file" disabled>
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
                            <button id="" class="button-modal" onclick="closebutton('edit_input')" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copy Input -->
            <div id="copyinput" class="modal">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content w3-animate-zoom" style="width: auto">
                        <header class="w3-container modal-header">
                            <span onclick="closebutton('copyinput')"
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
                            <button id="" class="button-modal" onclick="closebutton('copyinput')" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 加载動畫 OP -->
        <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->

    <div id="modal-overlay"></div>

</div>

<?php require_once '../app/views/input/input_share.php';?>



<style>
#modal-overlay {
  display: none; /* 預設隱藏 */
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.5); /* 灰色半透明 */
  z-index: 1040; /* 必須比主畫面內容高，但比 modal 低 */
}
</style>