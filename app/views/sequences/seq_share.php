<script>
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
        create_seq();
    }

    if(argument =="copy" && seqid != null){
        copy_seq(seqid);
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
    var job_id = '<?php echo $data['job_id'];?>';
    window.location.href = '../public/?url=Sequences/variation/'+ job_id;  
}


function copy_seq(seqid){
    
    document.getElementById('copyseq').style.display = 'block';   
    document.getElementById('from_seq_id').value =seqid;
    document.getElementById('from_seq_name').value =seqname;
    copy_seq_by_id(seqid);
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
                handleAjaxResponseWithSpinner(response); // 假設你有處理成功回傳的方法
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