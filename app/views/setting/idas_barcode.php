<div id="Barcode_Setting" class="divMode" style="overflow-y: hidden; max-height: none;" >
            <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_barcode_setting'] ;?></div>
            <div class="table-container">
                <div class="scrollbar" id="style-table">
                    <div class="scrollbar-force-overflow">
                        <table id="job_table" class="table w3-table-all w3-hoverable">
                            <thead id="header-table">
                                <tr class="w3-dark-grey">
                                    <th></th>
                                    <th><?php echo $text['job_id'];?></th>
                                    <th><?php echo $text['job_name'];?></th>
                                    <th><?php echo $text['system_barcode'];?></th>
                                    <th><?php echo $text['system_barcode_from'];?></th>
                                    <th><?php echo $text['system_barcode_to'];?></th>
                                    <th><?php echo $text['system_barcode_mode'];?></th>
                                </tr>
                            </thead>

                            <tbody style="font-size: 1.8vmin;text-align: center;" id='total_barcodes'>
                                
                                <?php foreach ($data['barcodes'] as $k_b =>$v_b){?>
                                    <tr>
                                        <td style="text-align: center; vertical-align: middle;" >
                                            <input class="form-check-input" type="checkbox" name="barcode_check" id="barcode_check" value="<?php echo $v_b['job_id'];?>" style="zoom:1.2">
                                        </td> 
                                        <td><?php echo $v_b['job_id'];?></td>
                                        <td><?php echo $v_b['JOBname'];?></td>
                                        <td><?php echo $v_b['barcode'];?></td>
                                        <td><?php echo $v_b['range_from'];?></td>
                                        <td><?php echo $v_b['range_count'];?></td>
                                        <td><?php echo $v_b['range_count'];?></td>
                                    </tr>
                                <?php } ?>
                                
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
                
            <hr>
                            
            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode'];?>:</div>
                <div class="col-6 t2">
                    <input id="barcode_name" name="barcode_name" style="height: 32px" type="text" value="" maxlength="54" class="form-control" required>
                </div>
            </div>
            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode_match_from'];?>:</div>
                <div class="col-3 t2">
                    <input id="barcode_from" name="barcode_from" style="height: 32px" type="text" value="1" class="form-control">
                </div>
            </div>
            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode_match_to'];?>:</div>
                <div class="col-3 t2">
                    <input id="barcode_count" name="barcode_count" style="height: 32px" type="text" value="" class="form-control">
                </div>
            </div>

            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode_mode'];?>:</div>
                <div class="col-3 t2">
                    <select class="form-select" id="barcode_mode" name="barcode_mode" onchange="toggleBarcodeSeq()" >
                        <option value="-1"><?php echo $text['system_barcode_setting'];?></option>
                            <?php
                            foreach ($data['barcode_mode'] as $key_b => $value_b) {?>
                                <option value='<?php echo $key_b ;?>'><?php echo $text['system_barcode_mode_' . $key_b] ;?></option>
                            <?php }?>
                            
                    </select>
                </div>
            </div>

            
            <div class="row t2">
                <div class="col-3 t1"><?php echo $text['system_barcode_select_job'];?>:</div>
                <div class="col-3 t2">
                    <select class="form-select" id="barcode_job" name="barcode_job" onchange="fetchSeqList()" >
                        <option value="-1"><?php echo $text['system_barcode_select_job_m'];?></option>
                            <?php
                            foreach ($data['job_list'] as $key => $value) {?>
                                <option value='<?php echo $value['JOBID'];?>'><?php echo $value['JOBID']." ".$value['JOBname'];?></option>
                            <?php }?>
                            
                    </select>
                </div>
            </div>
            <div id="barcode_select_seq" style="display:none;">
                <div class="row t2">
                    <div class="col-3 t1"><?php echo $text['system_barcode_select_seq'];?>:</div>
                    <div class="col-3 t2">
                        <select class="form-select" id="barcode_seq" name="barcode_seq">
                            <option value="-1"><?php echo $text['system_barcode_select_seq_m'];?></option>
                            
                        </select>
                    </div>
                </div>
            </div>

            <div style="text-align: center;margin-top: 50px;">
                <button class="all-btn w3-button w3-border w3-round-large" onclick="update_barcode()" ><?php echo $text['save'];?></button>&nbsp;&nbsp;
                <button class="all-btn w3-button w3-border w3-round-large" onclick="delete_barcode()" ><?php echo $text['delete_text'];?></button>
            </div>               
</div>