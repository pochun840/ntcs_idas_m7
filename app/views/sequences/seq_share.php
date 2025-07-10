<script>
function cound_seq(argument) {
    const table = document.getElementById('seq_table');
    if (!table) return;

    const selectedRow = table.querySelector('.selected');
    const seqid = selectedRow?.cells[0]?.innerText ?? null;
    const seqnameVal = selectedRow?.cells[1]?.innerText ?? null;

    // 全域使用者預期 seqid/seqname 仍會存進變數
    window.seqid = seqid;
    window.seqname = seqnameVal;

    const hasTarget = ['del', 'edit', 'copy'].includes(argument) && seqid !== null;
    const alwaysShowOverlay = argument === 'new' || hasTarget;

    if (alwaysShowOverlay) {
        document.querySelector(".main-content").classList.add("overlay-active");
    }

    switch (argument) {
        case 'del':
            if (seqid) delete_seqid(seqid);
            break;
        case 'edit':
            if (seqid) edit_seq(seqid);
            break;
        case 'new':
            create_seq();
            break;
        case 'copy':
            if (seqid) copy_seq(seqid, seqnameVal);
            break;
        default:
            console.warn(`未知的操作類型: ${argument}`);
    }
}




var rowInfoArray = [];
<?php foreach($data['sequences'] as $key =>$val) {?>
        var SEQID   = "<?php echo $val['SEQID'];?>";
        var SEQname = "<?php echo $val['SEQname'];?>";
        
        var rowInfo = {
            SEQID: SEQID,
            SEQname: SEQname
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
    const job_id = '<?php echo $data['job_id']; ?>';
    if (!job_id) {
        //alert('Job ID 無效，無法進入 Sequence 設定');
        return;
    }

    const targetUrl = `../public/?url=Sequences/variation/${job_id}`;
    window.location.href = targetUrl;
}


function copy_seq(seqid, seqname) {
    document.getElementById('copyseq').style.display = 'block';
    document.getElementById('from_seq_id').value = seqid;
    document.getElementById('from_seq_name').value = seqname;
}


//刪除seq
function delete_seqid(seqid) {

    var jobid = '<?php echo $data['job_id']?>';

    if (!jobid) return;

    var language = getCookie('language');
    var text_info, title;

    if (language === "zh-cn") {
        text_info = '你确定要删除这个工序吗？';
        title = '删除作业';
    } else if (language === "zh-tw") {
        text_info = '你確定要刪除這個工序嗎？';
        title = '刪除作業';
    } else {
        text_info = 'Are you sure you want to delete this sequences?';
        title = 'Delete sequences';
    }

    alertify.confirm(title, text_info, function () {
        // 點擊確認才會執行 AJAX
        document.querySelector(".main-content").classList.add("overlay-active");
        document.getElementById("spinner").style.display = 'block';

        $.ajax({
            url: "?url=Sequences/delete_seq",
            method: "POST",
            data:{ 
                jobid: jobid,
                seqid: seqid
            },
            success: function (response) {
                  success_response(response, 'spinner', true); // 自動關閉
            },
            error: function (xhr, status, error) {
                alertify.error("Delete failed: " + error);
                document.querySelector(".main-content").classList.remove("overlay-active");
                document.getElementById("spinner").style.display = 'none';
            }
        });

    }, function () {
        // 使用者點取消時什麼都不做
        document.querySelector(".main-content").classList.remove("overlay-active");
    });
}



function edit_seq(seqid) {
    const jobid = '<?php echo $data['job_id']; ?>';
    
    if (!jobid || !seqid) {
        return;
    }

    const targetUrl = `../public/?url=Sequences/variation/${jobid}/${seqid}`;
    window.location.href = targetUrl;
}



function getSelectedValue(name, defaultValue = 0) {
    const selected = document.querySelector(`input[name="${name}"]:checked`);
    return selected?.value ?? defaultValue;
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
                //console.log(response);
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

    console.log(dataToSend);


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