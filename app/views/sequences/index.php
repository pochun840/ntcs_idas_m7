
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_seq.css" type="text/css">


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
                    <div class="scrollbar-force-overflow">
                        <table id="seq_table" class="table w3-table-all w3-hoverable">
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
                                <?php foreach($data['sequences'] as $key =>$val) {?>
                                <tr>
                                    <td class="seq-id"> <?php echo $val['SEQID'];?></td>
                                    <td class="seq-name"><?php echo $val['SEQname'];?></td>
                                    <td><?php echo $val['seq_repeat'];?></td>
                                    <td>
                                        <?php if($val['act']== 1){?>
                                            <input class="seq_enable" style="zoom:1.5; vertical-align: middle"  data-sequence-id="<?php echo $val['SEQID'];?>" id="sequence_enable"   value="1"  type="checkbox" onclick="updateValue(this)"  checked>
                                        <?php }else{?>
                                            <input class="seq_enable" style="zoom:1.5; vertical-align: middle"  data-sequence-id="<?php echo $val['SEQID'];?>" id="sequence_enable"   value="0"  type="checkbox" onclick="updateValue(this)">
                                        <?php }?>
                                        

                                    </td>
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
        <?php  //$status = count($data['sequences']) >  100 ? 'disabled' : ''; ?>
            <input id="S3" name="Seq_Manager_Submit" type="button" value="<?php echo $text['New'];?>" tabindex="1"  onclick="cound_seq('new');"  >
            <input id="S6" name="Seq_Manager_Submit" type="button" value="<?php echo $text['Edit'];?>" tabindex="1" onclick="cound_seq('edit');">
            <input id="S5" name="Seq_Manager_Submit" type="button" value="<?php echo $text['Copy'];?>" tabindex="1" onclick="cound_seq('copy');" <?php //echo $status;?> >
            <input id="S4" name="Seq_Manager_Submit" type="button" value="<?php echo $text['Delete'];?>" tabindex="1" onclick="cound_seq('del');">
        </div>
    </div>

    <!-- Copy Sequence -->
    <div id="copyseq" class="modal">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content w3-animate-zoom" style="width: 60%">
                <header class="w3-container modal-header">
                    <span onclick="document.getElementById('copyseq').style.display='none'"
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
        				            <input type="number" class="form-control" id="to_seq_id">
        				        </div>
        				    </div>
        				    <div class="row">
        				        <label for="to_seq_name" class="t1 col-4 col-form-label"><?php echo $text['seq_name'];?> :</label>
        				        <div class="t2 col-5">
        				            <input type="text" class="form-control" id="to_seq_name">
        				        </div>
        				    </div>
        			    </div>
        			  </form>
                </div>

                <div class="modal-footer justify-content-center">
                    <button id="" class="button-modal" onclick="copy_seq_by_id()"><?php echo $text['save'];?></button>
                    <button id="" class="button-modal" onclick="hideElementById('copyseq');" class="closebtn"><?php echo $text['close'];?></button>
                </div>
            </div>
        </div>
    </div>

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


function cound_seq(argument){

    var table = document.getElementById('seq_table');
    var selectedRow = table.querySelector('.selected');  
    var selectedRowData = selectedRow ? selectedRow.cells[0].innerText : null;
    var selectedRowData_name = selectedRow ? selectedRow.cells[1].innerText : null;
    seqid = selectedRowData;
    seqname = selectedRowData_name;
    
    if(argument == 'del' && seqid != null){
        delete_seqid(seqid);
    }

    if(argument =="edit" && seqid != null){
        
        edit_seq(seqid);
    }

    if(argument =="new"){
        //alert('eweee');
        create_seq();
    }

    if(argument =="copy" && seqid != null){
        copy_seq(seqid);
    }


}

var rowInfoArray = [];
<?php foreach($data['sequences'] as $key =>$val) {?>
        var sequenceId = "<?php echo $val['SEQID'];?>";
        var sequenceName = "<?php echo $val['SEQname'];?>";
        
        var rowInfo = {
            sequence_id: sequenceId,
            sequence_name: sequenceName
        };
        
        rowInfoArray.push(rowInfo);
<?php } ?>

var seqid = ''; 
var seqname = '';
var rows = document.getElementsByTagName("tr");
for (var i = 0; i < rows.length; i++) {
    (function(row) {
        var cells = row.getElementsByTagName("td");
        if (cells.length > 0) {
            cells[0].addEventListener("click", function() {
           
                seqid = cells[0] ? (cells[0].textContent || cells[0].innerText) : null;
                seqname  = seqname = cells[1] ? (cells[1].textContent || cells[1].innerText) : null;
              
            });
        }
    })(rows[i]);
}

function copy_seq_by_id(){

    var jobid = '<?php echo $data['job_id'];?>';
    var oldseqname = seqname;
    var newseqid = document.getElementById('to_seq_id').value;
    var newseqname = document.getElementById("to_seq_name").value;    

    var language = getCookie('language');
    if(language == "zh-cn"){
        var text_info ='你确定吗？';
    }else if(language == "zh-tw"){
        var text_info ='你確定嗎 ?';
    }else{
        var text_info ='Are you sure ?';
    }


    if(newseqname){
        $.ajax({
            url: "?url=Sequences/check_seq_type",
            method: "POST",
            data:{ 
                jobid:jobid,
                newseqid: newseqid

            },
            success: function(response) {
                alertify.confirm(text_info, function (result) {
                if(result){
                    $.ajax({
                        url: "?url=Sequences/copy_seq_data",
                        method: "POST",
                        data:{ 
                            jobid: jobid,
                            seqid: seqid,
                            oldseqname: oldseqname,
                            newseqid: newseqid,
                            newseqname: newseqname
                        },
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
                }else {
                    alertify.error('Cancelled');
                    // 用户点击取消按钮的处理逻辑
                }
                });
                        },
            error: function(xhr, status, error) {
                
            }
        });
          
    }

}


function create_seq() {
    var job_id = '<?php echo $data['job_id'];?>';    
    window.location.href = '../public/?url=Sequences/variation/' + job_id; 

}


function copy_seq(seqid){
    
    document.getElementById('copyseq').style.display = 'block';   
    document.getElementById('from_seq_id').value =seqid;
    document.getElementById('from_seq_name').value =seqname;
    copy_seq_by_id(seqid);
}


function delete_seqid(seqid){
 
    var jobid = '<?php echo $data['job_id']?>';
    if (jobid) {
        $.ajax({
            url: "?url=Sequences/delete_seq",
            method: "POST",
            data:{ 
                jobid: jobid,
                seqid: seqid
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

function edit_seq(seqid) {
    var jobid = '<?php echo $data['job_id']?>'; 
    window.location.href = '../public/?url=Sequences/variation/'+ jobid+'/'+ seqid;   

}



function getSelectedValue(name, defaultValue = 0) {
    const selectedOption = document.querySelector(`input[name="${name}"]:checked`);
    return selectedOption ? selectedOption.value : defaultValue;
}

function updateValue(element){
    var jobid = '<?php echo $data['job_id']?>';
    var type_value = element.checked ? 1 : 0;
    var seqid = element.getAttribute('data-sequence-id');

    if(seqid){
        $.ajax({
            url: "?url=Sequences/check_seq_enable", 
            method: "POST",
            data: { 
                jobid: jobid,
                seqid: seqid,
                type_value: type_value
            },
            success: function(response) {
                console.log(response);
                history.go(0);
            },
            error: function(xhr, status, error) {
                console.error('AJAX 错误:', status, error); 
            }
        });    
    }
}

</script>
<script>
    
<?php foreach($data['sequences'] as $key =>$val) {?>
    var sequenceId = "<?php echo $val['SEQID'];?>";
    var sequenceName = "<?php echo $val['SEQname'];?>";

    var exists = rowInfoArray.some(function(item) {
        return item.sequence_id === sequenceId || item.sequence_name === sequenceName;
    });

    if (!exists) {
        var rowInfo = {
            sequence_id: sequenceId,
            sequence_name: sequenceName
        };
        rowInfoArray.push(rowInfo);
    }
<?php } ?>


function sendRowInfoArray() {
    var jobid = '<?php echo $data['job_id']?>';
    var dataToSend = {
        jobid: jobid,
        rowInfoArray: rowInfoArray
    };

    $.ajax({
        url: "?url=Sequences/adjustment_order", 
        method: "POST",
        data: dataToSend,
        success: function(response) {
            console.log(response);
            history.go(0); 
        },
        error: function(xhr, status, error) {
            console.error('Error sending data:', error);
        }
    });
}


function setRadioButton_value(radioButtons, value) {
    radioButtons.forEach(function(button) {
        if (button.value === value.toString()) {
            button.checked = true;
        } else {
            button.checked = false;
        }
    });
}


</script>