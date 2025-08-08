<div id="Barcode_Setting" class="divMode" style="display: none">
        <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_barcode_setting'] ;?></div>
        <div class="barcode-scrollbar" id="style-barcode">
            <div class="barcode-force-overflow">  
                <div style="position: relative; max-width: 100%;">                
                    <div class="table-container" id="tableContainer">
                        <table id="job_table" class="setting-table w3-table w3-hoverable">
                            <thead style="font-size: 3vmin;">
                                <tr class="w3-dark-grey">
                                    <th></th>
                                    <th><?php echo $text['job_id'];?></th>
                                    <th><?php echo $text['job_name'];?></th>
                                    <th><?php echo $text['system_barcode'];?></th>
                                    <th><?php echo $text['system_barcode_from'];?></th>
                                    <th>to</th>
                                    <th>barcode mode</th>
                                    <th>Count</th>
                                </tr>
                            </thead>

                            <tbody style="font-size: 3vmin;" id='total_barcodes'>
                                <?php foreach ($data['barcodes'] as $k_b =>$v_b){?>
                                    <tr>
                                        <td style="text-align: center; vertical-align: middle;" >
                                            <input class="form-check-input" type="checkbox" name="barcode_check" id="barcode_check" value="<?php echo $v_b['barcode_selected_job'];?>" style="zoom:1.2">
                                        </td>
                                        <td><?php echo $v_b['barcode_selected_job'];?></td>
                                        <td><?php echo $v_b['job_name'];?></td>
                                        <td><?php echo $v_b['barcode'];?></td>
                                        <td><?php echo $v_b['barcode_range_from'];?></td>
                                        <td><?php echo $v_b['barcode_range_to'];?></td>
                                        <td><?php echo $data['barcode_mode'][$v_b['barcode_enable']];?></td>
                                        <td><?php echo $v_b['barcode_range_count'];?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="bacode-btn-container">
                        <button class="bacode-btn" onclick="changePage('job_table', -1)">&#60;</button>
                        <button class="bacode-btn" onclick="changePage('job_table', 1)">&#62;</button>
                    </div>
                </div>    
                    
                <hr>
                                
                <div class="row t2">
                    <div class="col-5 t1"><?php echo $text['system_barcode'];?>:</div>
                    <div class="col-7 t2">
                        <input id="barcode_name" name="barcode_name" style="height: 32px" type="text" value="" maxlength="100" class="form-control" required>
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-5 t1"><?php echo $text['system_barcode_match_from'];?>:</div>
                    <div class="col-7 t2">
                        <input id="barcode_from" name="barcode_from" style="height: 32px" type="text" value="" class="form-control">
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-5 t1"><?php echo $text['system_barcode_match_to'];?>:</div>
                    <div class="col-7 t2">
                        <input id="barcode_count" name="barcode_count" style="height: 32px" type="text" value="" class="form-control">
                    </div>
                </div>
                <div class="row t2">
                    <div class="col-5 t1"><?php echo $text['select_job'];?>:</div>
                    <div class="col t2">
                    <select id="barcode_job" name="barcode_job" onchange="fetchSeqList()" >
                        <option value="-1"><?php echo $text['system_barcode_select_job_m'];?></option>
                            <?php
                            foreach ($data['job_list'] as $key => $value) {?>
                                <option value='<?php echo $value['job_id'];?>'><?php echo $value['job_id']." ".$value['job_name'];?></option>
                            <?php }?>
                            
                    </select>
                    </div>
                </div>

                <div id="barcode_select_seq" style="display:none;">
                    <div class="row t2">
                        <div class="col-5 t1"><?php echo $text['system_barcode_select_seq'];?>:</div>
                        <div class="col t2">
                            <select id="barcode_seq" name="barcode_seq">
                                <option value="-1"><?php echo $text['system_barcode_select_seq_m'];?></option>   
                            </select>
                        </div>
                    </div>
                </div>

            </div>
        </div>    
                
        <div style="text-align: center;margin-top: 30px;">
            <button class="all-btn w3-button w3-border w3-round-large" onclick="update_barcode()" ><?php echo $text['save'];?></button>&nbsp;&nbsp;
            <button class="all-btn w3-button w3-border w3-round-large" onclick="delete_barcode()" ><?php echo $text['delete_text'];?></button>
        </div>               
    </div>

<script>

$(document).on('change', 'input[name="barcode_check"]', function () {
    const isChecked = $(this).is(':checked');
    const $row = $(this).closest('tr');

    // 切換背景顏色
    if (isChecked) {
        $row.find('td').css('background-color', '#9AC0CD');
    } else {
        $row.find('td').css('background-color', ''); // 清空回到預設
    }

    // 欄位啟用/禁用
    $row.find('input, select, textarea')
        .not(this) // 排除自己
        .prop('disabled', !isChecked);

    // 勾選時自動 focus 第一個可輸入欄位
    if (isChecked) {
        $row.find('input, select, textarea')
            .not(this)
            .first()
            .focus();
    }
});

//透過JOBID 取得對應的SEQ
function fetchSeqList() {
    const jobId = document.getElementById('barcode_job').value;
    const barcodeSeq = document.getElementById('barcode_seq');

    // Reset list
    barcodeSeq.innerHTML = '';

    // 預設項目
    const defaultOption = document.createElement('option');
    defaultOption.value = "-1";
    defaultOption.textContent = "<?php echo $text['system_barcode_select_seq_m'];?>";
    barcodeSeq.appendChild(defaultOption);

    if (jobId === '-1') return;

    $.ajax({
        url: '?url=Settings/GetJobSeq',
        type: 'POST',
        data: { job_id: jobId },
        success: function(response) {
            const seqList = JSON.parse(response);

            seqList.forEach(seq => {
                const option = document.createElement('option');
                option.value = seq.SEQID;
                option.textContent = `${seq.SEQID} ${seq.SEQname}`;
                barcodeSeq.appendChild(option);
            });
        },
        error: function(xhr, status, error) {
            console.error('Error occurred:', error);
        }
    });
}
    
</script>