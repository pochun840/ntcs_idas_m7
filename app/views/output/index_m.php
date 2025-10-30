

<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_output_m.css" type="text/css">
<?php
  // 從控制器帶進來的值
  $focusedJobId = isset($data['focused_jobid']) ? $data['focused_jobid'] : null;
?>

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
                <label style="font-size:3vmin;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="8" maxlength="20" value="1" disabled style="height:30px; font-size:3.2vmin;text-align: center; background-color: #DDDDDD; border:0;">&nbsp;&nbsp;
                <button id="Button_Select" type="button" ><?php echo $text['select'];?></button>
            </div>

            <!-- Job Select Modal -->
             <div id="JobSelect" class="modal" style="width: 70%; top: 13%;">
                <div class="modal-dialog modal-lg">
                    <form class="modal-content w3-animate-zoom" action="">
                        <div class="w3-light-grey">
                            <header class="w3-container w3-dark-grey" style="height: 48px">
                                <span onclick="document.getElementById('JobSelect').style.display='none'" class="w3-button w3-red w3-large w3-display-topright" style="margin: 2px">&times;</span>
                                <h3 style="margin: 5px"><?php echo $text['job_select'];?></h3>
                            </header>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-12 t2 px-3"> <!-- col-12 cho toàn dòng, px-3 để có khoảng cách ngang -->
                                        <select id="JobNameSelect" name="JobNameSelect" disabled>
                                            <?php foreach($data['job_list'] as $key => $val) { ?>
                                                <option value="<?php echo $val['JOBID']; ?>"><?php echo $val['JOBname']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                            </div>    
                        </div>
                        <div class="modal-footer justify-content-center w3-dark-grey" style="height: 48px">
                            <button id="select_confirm" type="button" class="btn btn-primary" onclick='job_confirm()'><?php echo $text['confirm'];?></button>
                            <button id="select_close" type="button" class="btn btn-secondary" onclick="document.getElementById('JobSelect').style.display='none'" ><?php echo $text['close'];?></button>
                        </div>
                    </form>
                </div>    
            </div>

            <!-- Table Input -->
            <div id="TableOutputSetting">
                <div class="table-container">
                    <div class="scrollbar" id="style-outputtable">
                        <div class="force-overflow">
                            <table id="output_table" class="table w3-table">
                                <thead id="header-table">
                                    <tr class="w3-dark-grey" style="font-size: 2.6vmin">
                                        <th class="w3-center"><?php echo $text['event'];?></th>
                                        <th class="w3-center">Pin</th>
                                        <th class="w3-center"></th>
                                        <th class="w3-center"><?php echo $text['time'];?></th>
                                    </tr>
                               </thead>

                                <tbody style="font-size: 2.6vmin;text-align: center;" id="output_jobid_select" >
                                </tbody>
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
                <div class="modal-dialog modal-lg" style="top: 6%;">
                    <div class="modal-content w3-animate-zoom" style="width: 98%">
                        <header class="w3-container modal-header">
                            <span onclick="closebutton('new_output')"
                                class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                            <h3 id='modal_title'><?php echo $text['new_event'];?></h3>
                        </header>

                        <div class="modal-body" id="new_output">
                            <form id="new_output_from" style="padding-left: 1%; padding-right: 1%">
                                <div class="row">
                                    <div for="event" class="col-3 t1"><?php echo $text['event'];?> :</div>
                                    <div class="col-3 t2">
                                        <select id="Event_Option" class="col custom-file">
                                        <option value="-1" disabled selected><?php echo $text['Choose_option']; ?></option>
                                           	<?php foreach($data['event_output'] as $key =>$val){?>
                                                <option value ='<?php echo $key;?>'><?php echo $text[$val];?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="newEvent-scrollbar" id="style-newEvent">
                                    <div class="newEvent-force-overflow">
                                        <?php for ($i = 1; $i <= 11; $i++) {?>
                                            <div class="row output-pin">
                                                <div class="col t1"><?php echo $i;?>:</div>
                                                <div class="col t2 form-check form-check-inline">
                                                    <input class="zoom form-check-input" type="radio" name="pin_option" id="pin<?php echo $i; ?>_0" value="0" onclick="toggleOnputTime('pin<?php echo $i; ?>_0', this.checked,'1')" >
                                                    <label class="form-check-label" for="pin1_signal01"><img src="./img/signal01.png"></label>
                                                </div>
                                                <div class="col t2 form-check form-check-inline">
                                                    <input class="zoom form-check-input" type="radio" name="pin_option" id="pin<?php echo $i; ?>_1" value="1" onclick="toggleOnputTime('pin<?php echo $i; ?>_1', this.checked,'2')" >
                                                    <label class="form-check-label" for="pin1_signal02"><img src="./img/signal02.png"></label>
                                                </div>
                                                <div class="col t2 form-check form-check-inline">
                                                    <input class="zoom form-check-input" type="radio" name="pin_option" id="pin<?php echo $i; ?>_2" value="2"  onclick="toggleOnputTime('pin<?php echo $i; ?>_2', this.checked,'3')" >
                                                    <label class="form-check-label" for="pin1_trigger"><img src="./img/trigger.png"></label>
                                                </div>
                                                <div class="col-3 t2">
                                                    <input type="text" class="t4 form-control" id="time<?php echo $i; ?>"   placeholder="ms" value="" >
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="modal-footer justify-content-center">
                            <button id="" class="button-modal" onclick="create_output_id()"><?php echo $text['save'];?></button>
                            <button id="" class="button-modal" onclick="closebutton('new_output')" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Output -->
            <div id="edit_output" class="modal">
                <div class="modal-dialog modal-lg" style="top: 6%;">
                    <div class="modal-content w3-animate-zoom" style="width: auto">
                        <header class="w3-container modal-header">
                            <span onclick="closebutton('edit_output')"
                                class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                            <h3 id='modal_title'><?php echo $text['edit_event'];?></h3>
                        </header>

                        <div class="modal-body" id="new_output">
                            <form id="new_output_from" style="padding-left: 1%; padding-right: 1%">
                                <div class="row">
                                    <div for="event" class="col-3 t1"><?php echo $text['event'];?> :</div>
                                    <div class="col-2 t2">
                                        <select id="edit_event_option" name='edit_event_option' class="col custom-file grey-disabled" >
                                        <option value="-1" disabled selected><?php echo $text['Choose_option']; ?></option>
                                           <?php foreach($data['event_output'] as $key =>$val){?>
                                                <option value ='<?php echo $key;?>'><?php echo $text[$val];?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
								<?php for ($i = 1; $i <= 11; $i++) {?>

									<div class="row output-pin">
										<div class="col t1"><?php echo $i;?>:</div>
										<div class="col t2 form-check form-check-inline">
											<input class="zoom form-check-input" type="radio" name="edit_pin_option"  id="edit_pin<?php echo $i; ?>_0" value="0" onclick="toggleOnputTime_edit('edit_pin<?php echo $i; ?>_0', this.checked,'1')"  >
											<label class="form-check-label" for="pin1_signal01"><img src="./img/signal01.png"></label>
										</div>
										<div class="col t2 form-check form-check-inline">
											<input class="zoom form-check-input" type="radio" name="edit_pin_option" id="edit_pin<?php echo $i; ?>_1" value="1" onclick="toggleOnputTime_edit('edit_pin<?php echo $i; ?>_1', this.checked,'2')" >
											<label class="form-check-label" for="pin1_signal02"><img src="./img/signal02.png"></label>
										</div>
										<div class="col t2 form-check form-check-inline">
											<input class="zoom form-check-input" type="radio" name="edit_pin_option" id="edit_pin<?php echo $i; ?>_2" value="2" onclick="toggleOnputTime_edit('edit_pin<?php echo $i; ?>_2', this.checked,'3')" >
											<label class="form-check-label" for="pin1_trigger"><img src="./img/trigger.png"></label>
										</div>
										<div class="col-3 t2">
											<input type="text" class="t4 form-control" id="edit_time<?php echo $i; ?>" placeholder="ms" value="" >
										</div>
                                	</div>
									
								<?php } ?>
                            </form>
                        </div>

                        <div class="modal-footer justify-content-center">
                            <button id="" class="button-modal" onclick="edit_output_id()"><?php echo $text['save'];?></button>
                            <button id="" class="button-modal" onclick="closebutton('edit_output')" class="closebtn"><?php echo $text['close'];?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Copy Output -->
            <div id="copy_output" class="modal">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content w3-animate-zoom" style="width: auto">
                        <header class="w3-container modal-header">
                            <span onclick="closebutton('copy_output')"
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
                                                    <option id ='job_list_option' value="<?php echo $vv['JOBID']; ?>">
                                                        <?php echo $vv['JOBID'] . " - " . $vv['JOBname']; ?>
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
                            <button id="" class="button-modal" onclick="closebutton('copy_output')" class="closebtn"><?php echo $text['close'];?></button>
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

<?php require_once '../app/views/output/output_share.php';?>

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

.grey-disabled[disabled] {
    background-color: #d6d6d6; /* 整個背景灰 */
    color: #8a8a8a;            /* 文字灰 */
    border: 1px solid #b5b5b5; /* 灰色邊框 */
    cursor: not-allowed;       /* 滑鼠變禁止符號 */
    opacity: 1;                /* 取消部分瀏覽器預設透明 */
}

#job_id.bg-yellow { background-color: yellow !important; }
/* 避免任何顏色過渡造成的閃動 */
#job_id { transition: none !important; }
/* 若是 Chrome 的自動填寫黃底在作祟，這段可蓋掉 */
#job_id:-webkit-autofill,
#job_id:-webkit-autofill:focus {
  -webkit-box-shadow: 0 0 0px 1000px white inset !important;
  box-shadow: inset 0 0 0 1000px white !important;
}
</style>

<style>
    #output_table td,
    #output_table th {
        width: 100px; 
        padding: 10px;
    }
</style>



<script>
  document.addEventListener('DOMContentLoaded', function () {
    // 從 PHP 帶入目前 unified 的 jobid（可能為 null/空字串/數字）
    var focusedJobId = <?php echo json_encode($data['focused_jobid']); ?>;
    var el = document.getElementById('job_id');
    if (!el) return;

    // 有值 → 顯示數值並上黃色；沒值 → 清空並還原為灰色
    if (focusedJobId !== null && String(focusedJobId).length > 0) {
      el.value = String(focusedJobId);
      el.style.backgroundColor = 'yellow';
    } else {
      el.value = '';
      el.style.backgroundColor = '#DDDDDD';
    }
  });
</script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const jobIdInput = document.getElementById('job_id');
    const focusedJobId = <?php echo json_encode($focusedJobId); ?>;

    if (focusedJobId) {
        // 有值 → 背景改黃色，並填入 JOBID
        jobIdInput.style.backgroundColor = 'yellow';
        jobIdInput.value = focusedJobId;
    } else {
        // 沒值 → 恢復灰色
        jobIdInput.style.backgroundColor = '#DDDDDD';
        jobIdInput.value = '';
    }
});
</script>

