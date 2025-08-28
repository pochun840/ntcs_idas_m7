<script>
document.addEventListener('DOMContentLoaded', function () {
    const langRaw = getCookie('language') || 'en-us';
    const language = (langRaw.toLowerCase() === 'en') ? 'en-us' : langRaw.toLowerCase();

    alertify.defaults.glossary = {
        title: (language === 'zh-tw') ? '提示' :
               (language === 'zh-cn') ? '提示' : 'Notification',
        ok: (language === 'zh-tw') ? '確定' :
            (language === 'zh-cn') ? '确定' : 'OK',
        cancel: (language === 'zh-tw') ? '取消' :
                (language === 'zh-cn') ? '取消' : 'Cancel'
    };
});
    
function cound_step(action) {
    const table = document.getElementById('step_table');
    if (!table) return;

    const selectedRow = table.querySelector('.selected');
    const stepid = selectedRow?.cells[0]?.innerText ?? null;
    const stepname = selectedRow?.cells[1]?.innerText ?? null;

    // 全域設定（如果其他函式需要）
    window.stepid = stepid;
    window.stepname = stepname;

    const requiresSelection = ['del', 'edit', 'copy'].includes(action);

    // 有選取列時才執行這些操作
    if (requiresSelection && !stepid) {
        //alert("請先選擇一筆 Step 資料！");
        return;
    }

    // 顯示 overlay（如果需要）
    if (action === 'new' || requiresSelection) {
        document.querySelector(".main-content").classList.add("overlay-active");
    }

    // 根據參數執行對應操作
    switch (action) {
        case 'del':
            del_stepid(stepid);
            break;
        case 'edit':
            edit_step(stepid);
            break;
        case 'copy':
            copy_step(stepid);
            break;
        case 'new':
            create_step();
            break;
        default:
            console.warn(`未知的操作類型: ${action}`);
    }
}




function create_step() {
    var job_id = '<?php echo $data['job_id'];?>';    
    var seq_id = '<?php echo $data['seq_id'];?>';
    window.location.href = '../public/?url=Step/variation/' + job_id + '/' + seq_id; 


}

function edit_step(){
    var job_id = '<?php echo $data['job_id'];?>';    
    var seq_id = '<?php echo $data['seq_id'];?>';
    window.location.href = '../public/?url=Step/variation/' + job_id + '/' + seq_id + '/' + stepid; 
    
}






function copy_step_by_id(){
    var jobid = '<?php echo $data['job_id']?>';
    var seqidnew = '<?php echo $data['stepid_new']?>';
    
    document.getElementById('from_step_id').value = stepid;    
    document.getElementById("to_step_id").value = seqidnew;


}

function copy_step_by_id_ajax(){
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


function del_stepid(stepid) {
    const jobid = '<?php echo $data['job_id']?>';
    const seqid = '<?php echo $data['seq_id']?>';
    const language = getCookie('language') || 'en';

    const messages = {
        'en': {
            confirm: "Are you sure you want to delete this step?",
            title: "Delete Confirmation"
        },
        'zh-tw': {
            confirm: "您確定要刪除此步驟嗎？",
            title: "刪除確認"
        },
        'zh-cn': {
            confirm: "您确定要删除此步骤吗？",
            title: "删除确认"
        }
    };

    const msg = messages[language] || messages['en'];

    if (stepid) {
        alertify.confirm(msg.title, msg.confirm, function () {

            document.querySelector(".main-content").classList.add("overlay-active");
            document.getElementById("spinner").style.display = 'block';

            $.ajax({
                url: "?url=Step/delete_step",
                method: "POST",
                data: {
                    stepid: stepid,
                    jobid: jobid,
                    seqid: seqid
                },
                success: function (response) {
                    success_response_seq(response, 'spinner', `../public/?url=Step/index/${jobid}/${seqid}`);
                },
                error: function (xhr, status, error) {
                    alertify.alert("Error", "Delete failed: " + error);
                }
            });
        }, function () {
              document.querySelector(".main-content").classList.remove("overlay-active");
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
    var JOBID = '<?php echo $data['job_id']?>';
    var dataToSend = {
        JOBID: JOBID,
        rowInfoArray: rowInfoArray
    };
 
    
    if(rowInfoArray){

        $.ajax({
            url: "?url=Step/adjustment_order", 
            method: "POST",
            data: dataToSend,
            success: function(response) {
                //console.log(response);
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
    return rowCount;
}
</script>