

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


function copy_seq_by_id() {
    var jobid      = '<?php echo $data['job_id'];?>';
    var oldseqname = (typeof seqname !== 'undefined') ? seqname : ''; // 來源工序名
    var newseqid   = document.getElementById('to_seq_id')?.value?.trim();
    var newseqname = document.getElementById('to_seq_name')?.value?.trim();

    // ===== 多語系字串 =====
    var lang = getCookie('language');
    var title = 'Copy Sequence';
    var text  = 'Are you sure you want to copy this sequence?';
    var okText = 'OK';
    var cancelText = 'Cancel';
    var needNameMsg = 'Please enter the new sequence ID and name.';

    if (lang === 'zh-cn') {
        title = '复制工序';
        text  = '你确定要复制该工序吗？';
        okText = '确定';
        cancelText = '取消';
        needNameMsg = '请输入新工序的编号与名称。';
    } else if (lang === 'zh-tw') {
        title = '複製工序';
        text  = '你確定要複製該工序嗎？';
        okText = '確定';
        cancelText = '取消';
        needNameMsg = '請輸入新工序的編號與名稱。';
    }

    // ===== 基本檢查 =====
    if (!newseqid || !newseqname) {
        alertify.error(needNameMsg);
        return;
    }

    // 先檢查目標 SEQ 是否可用
    $.ajax({
        url: "?url=Sequences/check_seq_type",
        method: "POST",
        data: { jobid: jobid, newseqid: newseqid },
        success: function(response) {
            alertify
              .confirm(
                title,
                text,
                function onOk() {
                    // 顯示遮罩＋spinner
                    document.querySelector('.main-content')?.classList.add('overlay-active');
                    document.getElementById('spinner').style.display = 'block';


                    // 執行複製
                    $.ajax({
                        url: "?url=Sequences/copy_seq_data",
                        method: "POST",
                        data: {
                            jobid: jobid,
                            seqid: typeof seqid !== 'undefined' ? seqid : '', // 來源 SEQID
                            oldseqname: oldseqname,
                            newseqid: newseqid,
                            newseqname: newseqname
                        },
                        success: function(resp) {
                            var data;
                            try { data = (typeof resp === 'string') ? JSON.parse(resp) : resp; }
                            catch(e) { data = { res_type: 'Info', res_msg: resp || 'Done.' }; }

                            // 顯示 alert
                            var dlg = alertify.alert(data.res_type, data.res_msg, function () {
                                history.go(0);
                            });

                            // 3 秒後自動關閉並刷新
                            setTimeout(function() {
                                dlg.close();       // 關閉視窗
                                history.go(0);     // 重整頁面
                            }, 3000);
                        },
                        error: function(xhr, status, error) {
                            alertify.error((lang === 'zh-tw')
                                ? '複製失敗：' + error
                                : (lang === 'zh-cn') ? '复制失败：' + error
                                : 'Copy failed: ' + error
                            );
                        },
                        complete: function() {
                            document.querySelector('.main-content')?.classList.remove('overlay-active');
                            document.getElementById('spinner').style.display = 'none';
                        }
                    });

                },
                function onCancel() {
                    /*alertify.message(
                        (lang === 'zh-tw') ? '已取消' :
                        (lang === 'zh-cn') ? '已取消' : 'Cancelled'
                    );*/
                }
              )
              .set('labels', { ok: okText, cancel: cancelText });
        },
        error: function(xhr, status, error) {
            alertify.error(
                (lang === 'zh-tw') ? ('預檢失敗：' + error) :
                (lang === 'zh-cn') ? ('预检失败：' + error) :
                ('Pre-check failed: ' + error)
            );
        }
    });
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

    // 多語系
    var lang = getCookie('language');
    var text_info = 'Are you sure you want to delete this sequence?';
    var title = 'Delete Sequence';
    if (lang === 'zh-cn') {
        text_info = '你确定要删除该工序吗？';
        title = '删除工序';
    } else if (lang === 'zh-tw') {
        text_info = '你確定要刪除該工序嗎？';
        title = '刪除工序';
    }

    // OK / Cancel 文案
    var okText     = (lang === 'zh-tw') ? '確定' : (lang === 'zh-cn') ? '确定' : 'OK';
    var cancelText = (lang === 'zh-tw') ? '取消' : (lang === 'zh-cn') ? '取消' : 'Cancel';

    alertify
      .confirm(title, text_info,
        function () {
            // ✅ 確認後執行
            document.querySelector('.main-content').classList.add('overlay-active');
            document.getElementById('spinner').style.display = 'block';

            $.ajax({
                url: "?url=Sequences/delete_seq",
                method: "POST",
                data: { jobid: jobid, seqid: seqid },
                success: function (response) {
                    success_response(response, 'spinner', true); // 自動關閉 + 刷新
                },
                error: function (xhr, status, error) {
                    alertify.error("Delete failed: " + error);
                    document.querySelector('.main-content').classList.remove('overlay-active');
                    document.getElementById('spinner').style.display = 'none';
                }
            });
        },
        function () {
            // 取消：移除遮罩
            document.querySelector('.main-content').classList.remove('overlay-active');
        }
      )
      .set('labels', { ok: okText, cancel: cancelText }); // ← 套用多語系按鈕
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
    var type_value = element.checked ? 0 : 1 ;
    var seqid = element.getAttribute('data-sequence-id');

    if(seqid){
        $.ajax({
            url: "?url=Sequences/check_seq_enable", 
            method: "POST",
            data: { 
                jobid: jobid,
                seqid: seqid,
                skip: type_value
            },
            success: function(response) {
                history.go(0);
            },
            error: function(xhr, status, error) {
                console.error('AJAX 错误:', status, error); 
            }
        });    
    }
}

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