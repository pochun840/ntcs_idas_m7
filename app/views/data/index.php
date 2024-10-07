
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_data.css" type="text/css">

<?php 
    if($_SESSION['language'] == 'en-us'){
        $calendar_lang = '';
    }else if($_SESSION['language'] == 'zh-cn'){
        $calendar_lang = 'zh';
    }else if($_SESSION['language'] == 'zh-tw'){
        $calendar_lang = 'zh_tw';
    }else{
        $calendar_lang = '';
    }
?>
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['data'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>
    
    <div class="main-content">
        <div class="center-content">
            <div class="w3-center" style="position: relative; padding-right: 10px">
                <button id="bnt1" name="History_Display" class="button active" onclick="OpenButton('History')"><?php echo $text['data_history'];?></button>
                <button id="bnt2" name="Export_Data_Display" class="button" onclick="OpenButton('Exportdata')"><?php echo $text['data_export'];?></button>
                <div style="position:absolute;z-index: 9;right: 1px;top: 10px;">
                    <select id="data_select" class="form-select" onchange="DataMode(this)">
                        <option value="ALL">ALL</option>
                        <option value="OK">OK</option>
                        <option value="NOK">NG</option>
                    </select>
                </div>
            </div>
            
            <div id="DataButtonMode">
                <div id="HistoryDisplay">
                    <!-- Data ALL -->
                    <div  id ='res_title' style="font-weight: bold; font-size: 20px; padding-left: 1%"><?php echo $text['data_history_success'];?></div>
                    <div class="table-container" id='res_data_all'>
                        <div class="scrollbar" id="style-data">
                            <div class="scrollbar-force-overflow">
                                <table id="fasten_log_all" class="table w3-table-all w3-hoverable">
                                    <thead id="header-table">
                                        <tr>
                                            <th><?php echo $text['column_no'];?></th>
                                            <th><?php echo $text['column_datetime'];?></th>
                                            <th><?php echo $text['job_name'];?></th>
                                            <th><?php echo $text['seq_name'];?></th>
                                            <th><?php echo $text['torque'];?></th>
                                            <th><?php echo $text['column_unit'];?></th>
                                            <th><?php echo $text['angle'];?></th>
                                            <th><?php echo $text['column_total'];?></th>
                                            <th><?php echo $text['column_count'];?></th>
                                            <th><?php echo $text['column_status'];?></th>
                                        </tr>
                                    </thead>

                                    <tbody  style="font-size: 1.8vmin;text-align: center;" id='res_data'>
                                    
                                            <?php foreach($data['res_data'] as $key =>$val){?>

                                                <?php ////#FFEF62
                                                    if($val['fasten_status'] == 7 || $val['fasten_status'] == 8 ){
                                                        $style ='style="background: red"';
                                                    }else if($val['fasten_status'] == 5 || $val['fasten_status'] == 6){
                                                        $style ='style="background: #FFEF62"';
                                                    }else{
                                                        $style ='style="background: green"';
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?php echo $val['system_sn'];?></td>
                                                    <td><?php echo $val['data_time'];?></td>
                                                    <td><?php echo $val['job_name'];?></td>
                                                    <td><?php echo $val['sequence_name'];?></td>
                                                    <td><?php echo $val['target_torque'];?></td>
                                                    <td><?php echo $text[$data['unit_arr'][$val['torque_unit']]];?></td>
                                                    <td><?php echo $val['target_angle'];?></td>
                                                    <td><?php echo $val['total_screw_count'];?></td>
                                                    <td><?php echo $val['last_screw_count'];?></td>
                                                    <td <?php echo $style;?>><?php echo $data['status_arr'][$val['fasten_status']];?></td>
                                                </tr>
                                            <?php }?>
                                          
                                       
                                       
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Data OK -->
                    <div class="table-container"  id ='res_data_ok'style="display: none;">
                        <div style="font-weight: bold; font-size: 20px; padding-left: 1%"><?php echo $text['data_history_success'];?></div>
                        <div class="scrollbar" id="style-data">
                            <div class="scrollbar-force-overflow">
                                <table id="fasten_log" class="table w3-table-all w3-hoverable">
                                    <thead id="header-table">
                                        <tr>
                                            <th><?php echo $text['column_no'];?></th>
                                            <th><?php echo $text['column_datetime'];?></th>
                                            <th><?php echo $text['job_name'];?></th>
                                            <th><?php echo $text['seq_name'];?></th>
                                            <th><?php echo $text['torque'];?></th>
                                            <th><?php echo $text['column_unit'];?></th>
                                            <th><?php echo $text['angle'];?></th>
                                            <th><?php echo $text['column_total'];?></th>
                                            <th><?php echo $text['column_count'];?></th>
                                            <th><?php echo $text['column_status'];?></th>
                                        </tr>
                                    </thead>

                                    <tbody style="font-size: 1.8vmin;text-align: center;">
                                        <?php foreach($data['res_data_ok'] as $key_ok =>$val_ok){?>

                                            <?php ////#FFEF62
                                                if($val_ok['fasten_status'] == 7 || $val_ok['fasten_status'] == 8 ){
                                                    $style ='style="background: red"';
                                                }else if($val_ok['fasten_status'] == 5 || $val_ok['fasten_status'] == 6){
                                                    $style ='style="background: #FFEF62"';
                                                }else{
                                                    $style ='style="background: green"';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo $val_ok['system_sn'];?></td>
                                                <td><?php echo $val_ok['data_time'];?></td>
                                                <td><?php echo $val_ok['job_name'];?></td>
                                                <td><?php echo $val_ok['sequence_name'];?></td>
                                                <td><?php echo $val_ok['target_torque'];?></td>
                                                <td><?php echo $text[$data['unit_arr'][$val_ok['torque_unit']]];?></td>
                                                <td><?php echo $val_ok['target_angle'];?></td>
                                                <td><?php echo $val_ok['total_screw_count'];?></td>
                                                <td><?php echo $val_ok['last_screw_count'];?></td>
                                                <td <?php echo $style;?>><?php echo $data['status_arr'][$val_ok['fasten_status']];?></td>
                                            </tr>
                                        <?php }?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Data NG -->
                    <div class="table-container" id='res_data_nok' style="display: none;">
                        <div style="font-weight: bold; font-size: 20px; padding-left: 1%"><?php echo $text['data_history_fail'];?></div>
                        <div class="scrollbar" id="style-data">
                            <div class="scrollbar-force-overflow">
                                <table id="error_fasten_log" class="table w3-table-all w3-hoverable">
                                    <thead id="header-table">
                                        <tr>
                                            <th><?php echo $text['column_no'];?></th>
                                            <th><?php echo $text['column_datetime'];?></th>
                                            <th><?php echo $text['job_name'];?></th>
                                            <th><?php echo $text['seq_name'];?></th>
                                            <th><?php echo $text['torque'];?></th>
                                            <th><?php echo $text['column_unit'];?></th>
                                            <th><?php echo $text['angle'];?></th>
                                            <th><?php echo $text['column_total'];?></th>
                                            <th><?php echo $text['column_count'];?></th>
                                            <th><?php echo $text['column_status'];?></th>
                                        </tr>
                                    </thead>

                                    <tbody style="font-size: 1.8vmin;text-align: center;" >
                                        <?php foreach($data['res_data_nok'] as $key_nok =>$val_nok){?>

                                            <?php ////#FFEF62
                                                if($val_nok['fasten_status'] == 7 || $val_nok['fasten_status'] == 8 ){
                                                    $style ='style="background: red"';
                                                }else if($val_nok['fasten_status'] == 5 || $val_nok['fasten_status'] == 6){
                                                    $style ='style="background: #FFEF62"';
                                                }else{
                                                    $style ='style="background: green"';
                                                }
                                            ?>
                                            <tr>
                                                <td><?php echo $val_nok['system_sn'];?></td>
                                                <td><?php echo $val_nok['data_time'];?></td>
                                                <td><?php echo $val_nok['job_name'];?></td>
                                                <td><?php echo $val_nok['sequence_name'];?></td>
                                                <td><?php echo $val_nok['target_torque'];?></td>
                                                <td><?php echo $text[$data['unit_arr'][$val_nok['torque_unit']]];?></td>
                                                <td><?php echo $val_nok['target_angle'];?></td>
                                                <td><?php echo $val_nok['total_screw_count'];?></td>
                                                <td><?php echo $val_nok['last_screw_count'];?></td>
                                                <td <?php echo $style;?>><?php echo $data['status_arr'][$val_nok['fasten_status']];?></td>
                                            </tr>
                                        <?php }?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>                                                                                                      
                </div>
                
                <div id="ExportdataDisplay" style="display:none;">
                    <div class="data-export" style="background-color: #F2F1F1;">
                        <h2><?php echo $text['data_export'];?></h2>
                        <div class="row">
                            <div class="col-sm-6">
                                <div style="max-width: 450px;margin: auto;text-align: center;">
                                    <label for="start" style="font-size:20px;">📅 <?php echo $text['start_date'];?> :</label>
                                    <div class="mb-3">
                                        <input type="text" id="start_date" placeholder="Select datetime" class="form-control" style="background-color: #fff;display:none;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div style="max-width: 450px;margin: auto;text-align: center;">
                                    <label for="start" style="font-size:20px;">📅 <?php echo $text['end_date'];?> :</label>
                                    <div class="mb-3">
                                        <input type="text" id="end_date" placeholder="Select datetime" class="form-control" style="background-color: #fff;display: none;">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row" style="padding-left: 30%">
                            <div class="col-4 t1"><?php echo $text['Export Format'];?>:</div>
                            <div class="col t2">
                                <div class="form-check form-check-inline">
                                    <input class="t2 form-check-input" type="radio" name="export-option" id="export-csv" value="0" style="zoom:1.2; vertical-align: middle">
                                    <label class="t2 form-check-label" for="export-csv" style="font-weight: normal">CSV</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="t2 form-check-input" type="radio" name="export-option" id="export-zip" value="1" style="zoom:1.2; vertical-align: middle">
                                    <label class="t2 form-check-label" for="export-zip" style="font-weight: normal">ZIP</label>
                                </div>
                            </div>    
                        </div>
                        
                        <div style="text-align: center;margin-top: 20px;">
                            <button class="btn-export w3-button w3-border w3-round" onclick="exportData()"><?php echo $text['data_export'];?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>    

</div>


<script>

    // Button Home
    const moonLanding = new Date();
    let yy = moonLanding.getFullYear();
    flatpickr("#start_date,#end_date", {
        enableTime: true,
        static: true,
        inline:true,
        dateFormat: "Y-m-d H:i",
        locale: "<?php echo $calendar_lang; ?>",
        disableMobile: "true",
        // minDate: String(yy),
        maxDate: String(yy)+'-12-31',
        // maxDate: new Date().fp_incr(0) // 14 days from now
    });


    function DataMode() {    
        var mode = document.getElementById("data_select").value; 
        var error1 = '<?php echo $text['data_history_success']?>'; 
        var error = '<?php echo $text['data_history_fail']?>'; 

        if(mode =="OK"){
            document.getElementById('res_data_all').style.display = 'none';
            document.getElementById('res_data_ok').style.display = 'block';
            document.getElementById('res_data_nok').style.display = 'none';
            document.getElementById('res_title').style.display = 'none';
            
        }else if(mode =="NOK"){
            document.getElementById('res_data_all').style.display = 'none';
            document.getElementById('res_data_ok').style.display = 'none';
            document.getElementById('res_data_nok').style.display = 'block';
            document.getElementById('res_title').style.display = 'none';
        }else{
            document.getElementById('res_data_all').style.display = 'block';
            document.getElementById('res_data_ok').style.display = 'none';
            document.getElementById('res_data_nok').style.display = 'none';
            document.getElementById('res_title').style.display = 'block';

        }

        
    }



</script>
</body>

</html>