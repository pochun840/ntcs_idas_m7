
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_input.css" type="text/css">
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
                <label style="font-size:2.5vmin;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="8" maxlength="20" value="" disabled style="height:30px; font-size:2.5vmin;text-align: center; background-color: #DDDDDD; border:0;">&nbsp;&nbsp;
                <button id="Button_Select" type="button" onclick="document.getElementById('JobSelect').style.display='block'"><?php echo $text['select'];?></button>
            </div>

            <!-- Job Select Modal -->
            <div id="JobSelect" class="modal">
                <form class="w3-modal-content w3-card-4 w3-animate-zoom" style="width: 400px; top: 5%; left: -20%" action="">
                    <div class="w3-light-grey">
                        <header class="w3-container w3-dark-grey" style="height: 48px">
                            <span onclick="document.getElementById('JobSelect').style.display='none'" class="w3-button w3-red w3-large w3-display-topright" style="margin: 2px">&times;</span>
                            <h3 style="margin: 5px" onclick="get_job_list()"><?php echo $text['job_select'];?></h3>
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
                        <button id="select_confirm" type="button" class="btn btn-primary" onclick="job_confirm()"><?php echo $text['confirm'];?></button>
                        <button id="select_close" type="button" class="btn btn-secondary" onclick="document.getElementById('JobSelect').style.display='none'" ><?php echo $text['close'];?></button>
                    </div>
                </form>
            </div>
            <div id="DivMode">
                <!-- Table Input -->
                <!--<img src="./img/low.png" style="max-width: 50px;">-->
                <!--<img src="./img/high.png" style="max-width: 50px;">-->
                <div id="TableInputSetting">
                    <div class="table-input">
                        <div class="scrollbar" id="style-inputtable">
                            <div class="scrollbar-force-overflow">
                                <table id="input_table" class="table w3-table-all w3-hoverable">
                                    <thead class="header-table">
                                        <tr class="w3-dark-grey">
                                            <th><?php echo $text['event'];?></th>
                                            <th>2</th>
                                            <th>3</th>
                                            <th>4</th>
                                            <th>5</th>
                                            <th>6</th>
                                            <th>7</th>
                                            <th>8</th>
                                            <th>9</th>
                                            <th>10</th>
                                            <th><?php echo $text['Confirm'];?></th>
                                            <th><?php echo $text['page'];?></th>
                                            <th><?php echo $text['mode'];?></th>
                                        </tr>
                                    </thead>

                                    <tbody  id="input_jobid_select" style="font-size: 1.8vmin;text-align: center;">
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="footer" id='input_menu'>
                        <div class="buttonbox">
                            <input id="S1" name="New_Submit" type="button" value="<?php echo $text['New'];?>" tabindex="1" onclick="crud_job_event('new')">
                            <input id="S2" name="Edit_Submit" type="button" value="<?php echo $text['Edit'];?>" tabindex="1" onclick="crud_job_event('edit')">
                            <input id="S3" name="Copy_Submit" type="button" value="<?php echo $text['Copy'];?>" tabindex="1" onclick="crud_job_event('copy')" >
                            <input id="S4" name="Delete_Submit" type="button" value="<?php echo $text['Delete'];?>" tabindex="1" onclick="crud_job_event('del')">
                            <input id="S5" name="Table_Submit" type="button" value="<?php echo $text['Table'];?>" tabindex="1" onclick="tablesubmit('show')">
                            <input id="S6" name="Align_Submit" type="button" value="<?php echo $text['Align'];?>" tabindex="1" onclick="crud_job_event('unified')" >
                        </div>
                    </div>
                </div>

                <!-- Table Data Information -->
                <div id="TableDataInput" style="display: none">
                    
                    <div id="Event_List" align="center" style="margin-top: 10px;background-color: #F2F2D9">
                        <div class="w3-border-bottom" style="font-size: 20px;">Event List</div>
                        <table class="w3-table-all">
                            <tr>
                                <td class="w3-left-align">1-50 SW Job ID</td>
                                <td class="w3-left-align">101 <?php echo $text['Disable'];?></td>
                                <td class="w3-left-align">102 <?php echo $text['Enable'];?></td>
                                <td class="w3-left-align">103 <?php echo $text['Clear'];?></td>
                                <td class="w3-left-align">104 <?php echo $text['Confirm'];?></td>
                            </tr>
                            <tr>
                                <td class="w3-left-align">105 <?php echo $text['Start-IN(Remote)'];?></td>
                                <td class="w3-left-align">106 <?php echo $text['Unscrew(Remote)'];?></td>
                                <td class="w3-left-align">107 <?php echo $text['Sequence Clear'];?></td>
                                <td class="w3-left-align">108 <?php echo $text['Reboot'];?></td>
                                <td class="w3-left-align">109 <?php echo $text['Gate Once'];?></td>
                            </tr>
                            <tr>
                                <td class="w3-left-align">110 <?php echo $text['UDEFINE'];?>1</td>
                                <td class="w3-left-align">111 <?php echo $text['UDEFINE'];?>2</td>
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
                    <div class="modal-content w3-animate-zoom" style="width: 70%">
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

                                <div class="row input-pin">
                                    <div class="col-1 t1">2:</div>
                                    <div class="col t2" >
                    			      	<div class="col-4 form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin2_high" value="1">
                    					    <label class="form-check-label" for="pin2_high"><img src="./img/high.png"></label>
                    					</div>
                    					<div class="form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin2_low" value="2">
                    					    <label class="form-check-label" for="pin2_low"><img src="./img/low.png"></label>
                    					</div>
                                    </div>

                                    <div class="col-1 t1">7:</div>
                                    <div class="col t2" >
                    			      	<div class="col-4 form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin7_high" value="1">
                    					    <label class="form-check-label" for="pin7_high"><img src="./img/high.png"></label>
                    					</div>
                    					<div class="form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin7_low" value="2">
                    					    <label class="form-check-label" for="pin7_low"><img src="./img/low.png"></label>
                    					</div>
                                    </div>
                                </div>

                                <div class="row input-pin">
                                    <div class="col-1 t1">3:</div>
                                    <div class="col t2" >
                    			      	<div class="col-4 form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin3_high" value="1">
                    					    <label class="form-check-label" for="pin3_high"><img src="./img/high.png"></label>
                    					</div>
                    					<div class="form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin3_low" value="2">
                    					    <label class="form-check-label" for="pin3_low"><img src="./img/low.png"></label>
                    					</div>
                                    </div>

                                    <div class="col-1 t1">8:</div>
                                    <div class="col t2" >
                    			      	<div class="col-4 form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin8_high" value="1">
                    					    <label class="form-check-label" for="pin8_high"><img src="./img/high.png"></label>
                    					</div>
                    					<div class="form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin8_low" value="2">
                    					    <label class="form-check-label" for="pin8_low"><img src="./img/low.png"></label>
                    					</div>
                                    </div>
                                </div>

                                <div class="row input-pin">
                                    <div class="col-1 t1">4:</div>
                                    <div class="col t2" >
                    			      	<div class="col-4 form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin4_high" value="1">
                    					    <label class="form-check-label" for="pin4_high"><img src="./img/high.png"></label>
                    					</div>
                    					<div class="form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin4_low" value="2">
                    					    <label class="form-check-label" for="pin4_low"><img src="./img/low.png"></label>
                    					</div>
                                    </div>

                                    <div class="col-1 t1">9:</div>
                                    <div class="col t2">
                    			      	<div class="col-4 form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin9_high" value="1">
                    					    <label class="form-check-label" for="pin9_high"><img src="./img/high.png"></label>
                    					</div>
                    					<div class="form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin9_low" value="2">
                    					    <label class="form-check-label" for="pin9_low"><img src="./img/low.png"></label>
                    					</div>
                                    </div>
                                </div>

                                <div class="row input-pin">
                                    <div class="col-1 t1">5:</div>
                                    <div class="col t2" >
                    			      	<div class="col-4 form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin5_high" value="1">
                    					    <label class="form-check-label" for="pin5_high"><img src="./img/high.png"></label>
                    					</div>
                    					<div class="form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin5_low" value="2">
                    					    <label class="form-check-label" for="pin5_low"><img src="./img/low.png"></label>
                    					</div>
                                    </div>

                                    <div class="col-1 t1">10:</div>
                                    <div class="col t2">
                    			      	<div class="col-4 form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin10_high" value="1">
                    					    <label class="form-check-label" for="pin10_high"><img src="./img/high.png"></label>
                    					</div>
                    					<div class="form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin10_low" value="2">
                    					    <label class="form-check-label" for="pin10_low"><img src="./img/low.png"></label>
                    					</div>
                                    </div>
                                </div>

                                <div class="row input-pin">
                                    <div class="col-1 t1">6:</div>
                                    <div class="col t2" >
                    			      	<div class="col-2 form-check form-check-inline">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin6_high" value="1">
                    					    <label class="form-check-label" for="pin6_high"><img src="./img/high.png"></label>
                    					</div>
                    					<div class="col form-check form-check-inline" style="margin-left: -10px">
                    					    <input class="form-check-input" type="radio" name="pin_option" id="pin6_low" value="2">
                    					    <label class="form-check-label" for="pin6_low"><img src="./img/low.png"></label>
                    					</div>
                                    </div>

                                    
                                </div>

                                <div class="row" id='work_goc'  style="display: none;">
                                    <div for="Workpice Ready Confirm" class="col-6 t1"><?php echo $text['gate_confirm'];?> :</div>
                                    <div class="col t2" >
                                        <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gateconfirm" id="gateconfirm_0" value="0" checked>
                                        <label class="form-check-label"><?php echo $text['NO'];?></label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="gateconfirm" id="gateconfirm_1" value="1">
                                        <label class="form-check-label" ><?php echo $text['YES'];?></label>
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

            <!-- edit Input -->
            <div id="edit_input" class="modal">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content w3-animate-zoom" style="width: 70%">
                    <header class="w3-container modal-header">
                        <span onclick="document.getElementById('edit_input').style.display='none'"
                            class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                        <h3 id='modal_title'><?php echo $text['edit_event'];?></h3>
                    </header>

                    <div class="modal-body">
                        <form id="new_input_form" style="padding-left: 5%">
                            <div class="row">
                                <div for="event" class="col-3 t1">Event :</div>
                                <div class="col-2 t2">
                                    <select id="edit_Event_Option" name ="edit_Event_Option" class="col custom-file">
                                        <?php foreach($data['event'] as $key =>$val){?>
                                            <option value ='<?php echo $key;?>'><?php echo $text[$val];?></option>
                                        <?php } ?>
                                
                                    </select>
                                </div>
                            </div>

                            <div class="row input-pin">
                                <div class="col-1 t1">2:</div>
                                <div class="col t2" >
                                    <div class="col-4 form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin2_high" value="1">
                                        <label class="form-check-label" for="pin2_high"><img src="./img/high.png"></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin2_low" value="2">
                                        <label class="form-check-label" for="pin2_low"><img src="./img/low.png"></label>
                                    </div>
                                </div>

                                <div class="col-1 t1">7:</div>
                                <div class="col t2" >
                                    <div class="col-4 form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin7_high" value="1">
                                        <label class="form-check-label" for="pin7_high"><img src="./img/high.png"></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin7_low" value="2">
                                        <label class="form-check-label" for="pin7_low"><img src="./img/low.png"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row input-pin">
                                <div class="col-1 t1">3:</div>
                                <div class="col t2" >
                                    <div class="col-4 form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin3_high" value="1">
                                        <label class="form-check-label" for="pin3_high"><img src="./img/high.png"></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin3_low" value="2">
                                        <label class="form-check-label" for="pin3_low"><img src="./img/low.png"></label>
                                    </div>
                                </div>

                                <div class="col-1 t1">8:</div>
                                <div class="col t2" >
                                    <div class="col-4 form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin8_high" value="1">
                                        <label class="form-check-label" for="pin8_high"><img src="./img/high.png"></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin8_low" value="2">
                                        <label class="form-check-label" for="pin8_low"><img src="./img/low.png"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row input-pin">
                                <div class="col-1 t1">4:</div>
                                <div class="col t2" >
                                    <div class="col-4 form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin4_high" value="1">
                                        <label class="form-check-label" for="pin4_high"><img src="./img/high.png"></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin4_low" value="2">
                                        <label class="form-check-label" for="pin4_low"><img src="./img/low.png"></label>
                                    </div>
                                </div>

                                <div class="col-1 t1">9:</div>
                                <div class="col t2">
                                    <div class="col-4 form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin9_high" value="1">
                                        <label class="form-check-label" for="pin9_high"><img src="./img/high.png"></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin9_low" value="2">
                                        <label class="form-check-label" for="pin9_low"><img src="./img/low.png"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row input-pin">
                                <div class="col-1 t1">5:</div>
                                <div class="col t2" >
                                    <div class="col-4 form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin5_high" value="1">
                                        <label class="form-check-label" for="pin5_high"><img src="./img/high.png"></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin5_low" value="2">
                                        <label class="form-check-label" for="pin5_low"><img src="./img/low.png"></label>
                                    </div>
                                </div>

                                <div class="col-1 t1">10:</div>
                                <div class="col t2">
                                    <div class="col-4 form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin10_high" value="1">
                                        <label class="form-check-label" for="pin10_high"><img src="./img/high.png"></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin10_low" value="2">
                                        <label class="form-check-label" for="pin10_low"><img src="./img/low.png"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row input-pin">
                                <div class="col-1 t1">6:</div>
                                <div class="col t2" >
                                    <div class="col-2 form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin6_high" value="1">
                                        <label class="form-check-label" for="pin6_high"><img src="./img/high.png"></label>
                                    </div>
                                    <div class="col form-check form-check-inline" style="margin-left: -10px">
                                        <input class="form-check-input" type="radio" name="edit_pin_option" id="edit_pin6_low" value="2">
                                        <label class="form-check-label" for="pin6_low"><img src="./img/low.png"></label>
                                    </div>
                                </div>

                                
                            </div>

                            <div class="row" id='edit_work_goc'  style="display: none;">
                                <div for="Workpice Ready Confirm" class="col-6 t1"><?php echo $text['gate_confirm'];?> :</div>
                                <div class="col t2" >
                                    <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="edit_gateconfirm" id="edit_gateconfirm_0" value="0">
                                    <label class="form-check-label"><?php echo $text['NO'];?></label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="edit_gateconfirm" id="edit_gateconfirm_1" value="1">
                                    <label class="form-check-label" ><?php echo $text['YES'];?></label>
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
                    <div class="modal-content w3-animate-zoom" style="width: 60%;">
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
                				            <input type="text" class="form-control" id="from_job_name" disabled>
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
// Change the color of a row in a table

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

function crud_job_event(argument){
    if(argument == 'new' && job_id != ''){
        //針對已設定的pin角位disable
        if (Array.isArray(temp)){ 
            temp.forEach(function(element) {
                var radio = document.getElementById(element);
                if (radio && radio.type === 'radio') { 
                    radio.disabled = true; 
                }
            });
        } 
        
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

        document.getElementById('newinput').style.display='block';
    } 
    
    if(argument == 'del' && job_id  != '' &&  input_event != '')  {
        delete_input_id(job_id,input_event);
    }


    if(argument == 'edit' && job_id != '' && input_event != ''){

        var selectElement = document.getElementById('edit_Event_Option');
        if(selectElement){
            selectElement.disabled = true;
            var options = selectElement.options;
            for (var i = 0; i < options.length; i++) {
                options[i].disabled = true;
                options[i].classList.add('disabled_input');
            }
        }
    
        if (Array.isArray(temp)){
            temp.forEach(function(element){
                var radio = document.getElementById(element);
                if (radio && radio.type === 'radio'){
                    radio.disabled = true;
                }
            });
        }


        //console.log(temp);

        if(input_event ==109){
            document.getElementById('edit_work_goc').style.display='block';
            var gateconfirm = temp[19];
            var result = gateconfirm.replace("check_", "");

            if (result == "0") {
                document.getElementById('edit_gateconfirm_0').checked = true;
            } else if (result == "1") {
                document.getElementById('edit_gateconfirm_1').checked = true;
            }
         
        }
        
        get_input_info(job_id,input_event);
        handleEventChange(input_event); 
        document.getElementById('edit_input').style.display='block';

    
    }

    if(argument == 'copy' && job_id != '' && input_event != ''){
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

        document.getElementById('copyinput').style.display='block';
    }

    if(argument == 'unified' && job_id != ''){
        enableButton();
        resetBackgroundColor();

        if(input_job != job_id){
            alignsubmit(job_id);  
        }else{
            resetalignsubmit(job_id);
        }
    }
}



function handleEventChange(selectedValue) {
    if(selectedValue ==109){
        document.getElementById('work_goc').style.display = 'block';
    }else{
        document.getElementById('work_goc').style.display = 'none';
    }
}

function edit_handleEventChange(selectedValue) {
    if(selectedValue ==109){
        document.getElementById('edit_work_goc').style.display = 'block';
    }else{
        document.getElementById('edit_work_goc').style.display = 'none';
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



function tablesubmit(keyno){
    if(keyno =='show'){
        document.getElementById('TableDataInput').style.display = 'block';
        document.getElementById('input_menu').style.display = 'none';
        get_input_by_job_id(job_id);
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

//delete
function delete_input_id(jobid,input_event){
    if(job_id){
        $.ajax({
            url: "?url=Inputs/delete_input",
            method: "POST",
            data: { 
                job_id: job_id,
                input_event: input_event,
             
            },
            success: function(response) {
            
                var responseData = JSON.parse(response);
                alertify.alert(responseData.res_type, responseData.res_msg, function() {
                    get_input_by_job_id(job_id);
                });

            },
            error: function(xhr, status, error) {
                console.error("AJAX request failed:", status, error);
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
            url: "?url=Inputs/input_alljob",
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

function enableButton() {
    var button = document.getElementById('Button_Select');
    if (button.disabled) {
        button.disabled = false;
    }
}

function resetBackgroundColor() {
    var jobInput = document.getElementById('job_id');
    if (jobInput.style.backgroundColor === 'yellow') {
        jobInput.style.backgroundColor = '';
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
                
                var responseJSON = JSON.stringify(response);
                var cleanString = responseJSON.replace(/Array|\\n/g, '');
                var cleanString = cleanString.substring(2, cleanString.length - 2);

                var [, jobid] = cleanString.match(/\[input_job_id]\s*=>\s*([^ ]+)/) || [, null];
                var [, input_event] = cleanString.match(/\[input_event]\s*=>\s*([^ ]+)/) || [, null];
                var [, input_pin] = cleanString.match(/\[input_pin]\s*=>\s*([^ ]+)/) || [, null];
                var [, input_wave] = cleanString.match(/\[input_wave]\s*=>\s*([^ ]+)/) || [, null];
                var [, gateconfirm] = cleanString.match(/\[gateconfirm]\s*=>\s*([^ ]+)/) || [, null];

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
                
                document.querySelector("select[name='edit_Event_Option']").value = input_event;

                document.getElementById("edit_Event_Option").onchange = function() {
                    var selectedValue = this.value; 
                    edit_handleEventChange(selectedValue); 
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


function edit_input_id(){

    var input_event = document.getElementById("edit_Event_Option").value;
    var pinval      = collectPinValues('input[name="edit_pin_option"]');
    var pin_old   = pinval[0]['id'];
    var input_wave  = pinval[0]['value'];
    var pagemode    = 1;
    var input_seqid = 0;
    var input_pin = pin_old.match(/\d+/)[0];

    if(input_event == 109){
        var selectedOption = document.querySelector('input[name="edit_gateconfirm"]:checked');
        var gateconfirm    = selectedOption ? selectedOption.value : 0;
    }else{
        var gateconfirm	 = 0;
    }

    if(job_id){
        $.ajax({
            url: "?url=Inputs/edit_input_event",
            method: "POST",
            data: { 
                job_id: job_id,
                input_event: input_event,
                input_pin: 	input_pin,
                input_wave: input_wave,
                gateconfirm: gateconfirm,
                pagemode: pagemode,
                input_seqid: input_seqid,
                old_input_event: old_input_event
            },
            success: function(response) {

                document.getElementById('edit_input').style.display='none';
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


</script>
<style>
    #input_table td,
    #input_table th {
        width: 100px; 
        padding: 10px;
    }
</style>