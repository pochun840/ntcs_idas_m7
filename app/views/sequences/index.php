
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['seq_management'];?></h3></td>
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:20px;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;&nbsp;
                <input type="text" id="job_id" name="job_id" size="10" maxlength="20" value="<?php echo $data['job_id'];?>" disabled
                style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <button id="back_btn" type="button" onclick="window.location.href='?url=Jobs/index'"><?php echo $text['return'];?></button>
            </div>

            <div class="table-container">
                <div class="scrollbar" id="style-jobtable">
                    <div class="force-overflow">
                        <table id="seq_table" class="table w3-table">
                            <thead id="header-table">
                                <tr class="w3-dark-grey">
                                    <th><?php echo $text['seq_id'];?></th>
                                    <th><?php echo $text['seq_name'];?></th>
                                    <th><?php echo $text['tightening_repeat'];?></th>
                                    <th><?php echo $text['enable'];?></th>
                                    <th><?php echo $text['up'];?></th>
                                    <th><?php echo $text['down'];?></th>
                                    <th><?php echo $text['total_step'];?></th>
                                    <th><?php echo $text['add_step'];?></th>
                                </tr>
                            </thead>

                            <tbody style="font-size: 1.8vmin;text-align: center;">
                                <?php $sequenceCount = isset($data['sequences']) ? count($data['sequences']) : 0; ?>

                                <?php foreach($data['sequences'] as $key =>$val) {?>
                                <tr>
                                    <td class="seq-id"> <?php echo $val['SEQID'];?></td>
                                    <td class="seq-name"><?php echo $val['SEQname'];?></td>
                                    <td><?php echo $val['seq_repeat'];?></td>
                                    <td><input class="seq_enable" style="zoom:1.5; vertical-align: middle" data-sequence-id="<?php echo $val['SEQID']; ?>" id="sequence_enable" value="<?php echo ($val['skip'] == 0 ? '1' : '0'); ?>" type="checkbox" <?php echo ($sequenceCount > 1 ? 'onclick="updateValue(this)"' : 'disabled'); ?> <?php echo ($val['skip'] == 0 ? 'checked' : ''); ?>></td>
                                    <td><img src="./img/btn_up.png"   onclick="MoveUp(this);"></td>
                                    <td><img src="./img/btn_down.png" onclick="MoveDown(this);"></td>
                                    <td><?php echo $val['total_step'];?></td>
                                    <?php $url ='?url=Step/index/'.$data['job_id']."/".$val['SEQID'];?>
                                    <td><img id="Add_Step" src="./img/btn_plus.png" onclick="location.href='<?php echo $url;?>'"></td>
                                </tr>
                                <?php  } ?>
                               
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <div id="TotalPage">
            <div id="TotalSeqTable">
                <div style="color:black; float: right; margin: 2px"><?php  echo $text['total_seq']; ?> :
                    <label id="RecordCnt" name="RecordCnt" type="text" style="margin-right: 20px"><?php echo count($data['sequences']); ?></label>
                </div>
            </div>
        </div>

        <div class="buttonbox">
        <?php  $status = $data['total_seq']  >  100 ? 'disabled' : ''; ?>
            <input id="S3" name="Seq_Manager_Submit" type="button" value="<?php echo $text['New'];?>" tabindex="1"  onclick="cound_seq('new');"  <?php echo $status;?> >
            <input id="S6" name="Seq_Manager_Submit" type="button" value="<?php echo $text['Edit'];?>" tabindex="1" onclick="cound_seq('edit');">
            <input id="S5" name="Seq_Manager_Submit" type="button" value="<?php echo $text['Copy'];?>" tabindex="1" onclick="cound_seq('copy');" <?php echo $status;?> >
            <input id="S4" name="Seq_Manager_Submit" type="button" value="<?php echo $text['Delete'];?>" tabindex="1" onclick="cound_seq('del');">
        </div>
    </div>

    <!-- Copy Sequence -->
    <div id="copyseq" class="modal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content w3-animate-zoom" style="width: 60%">
                <header class="w3-container modal-header">
                    <span onclick="closebutton('copyseq');"
                        class="w3-button w3-red w3-display-topright" style="width: 50px; margin: 3px;">&times;</span>
                    <h3 id='modal_title'><?php echo $text['Copy_Sequence'];?></h3>
                </header>

                <div class="modal-body">
                    <form id="new_seq_form">
        	            <label for="from_seq_id" class="col col-form-label" style="font-weight: bold"><?php echo $text['copy_from'];?></label>
        	            <div style="padding-left: 10%">
        		            <div class="row">
        				        <label for="from_seq_id" class="t1 col-4 col-form-label"><?php echo $text['seq_id'];?> :</label>
        				        <div class="col-5 t2 ">
        				            <input type="text" class="form-control" id="from_seq_id" disabled>
        				        </div>
        				    </div>
        				    <div class="row">
        				        <label for="from_seq_name" class="t1 col-4 col-form-label"><?php echo $text['seq_name'];?> :</label>
        				        <div class="t2 col-5">
        				            <input type="text" class="form-control" id="from_seq_name" disabled>
        				        </div>
        				    </div>
        			    </div>

        			    <label for="from_seq_id" class="col col-form-label" style="font-weight: bold"><?php echo $text['copy_to'];?></label>
        			    <div style="padding-left: 10%">
        				    <div class="row">
        				        <label for="to_seq_id" class="t1 col-4 col-form-label"><?php echo $text['seq_id'];?> :</label>
        				        <div class="t2 col-5">
        				            <input type="number" class="form-control" id="to_seq_id" value ='<?php echo $data['next_seq_id'];?>'>
        				        </div>
        				    </div>
        				    <div class="row">
        				        <label for="to_seq_name" class="t1 col-4 col-form-label"><?php echo $text['seq_name'];?> :</label>
        				        <div class="t2 col-5">
        				            <input type="text" class="form-control" id="to_seq_name" value='<?php echo "SEQ-".$data['next_seq_id'];?>'>
        				        </div>
        				    </div>
        			    </div>
        			  </form>
                </div>

                <div class="modal-footer justify-content-center">
                    <button id="" class="button-modal" onclick="copy_seq_by_id()"><?php echo $text['save'];?></button>
                    <button id="" class="button-modal" onclick="closebutton('copyseq');" class="closebtn"><?php echo $text['close'];?></button>
                </div>
            </div>
        </div>
    </div>

    <!-- 加载動畫 OP -->
       <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->



</div>

<script>
// Change the color of a row in a table
$(document).ready(function () {
    highlight_row('seq_table');
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
var seqid = '';
var seq_name = '';
var modal = document.getElementById('newseq');
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}



</script>

<?php require_once '../app/views/sequences/seq_share.php';?>
