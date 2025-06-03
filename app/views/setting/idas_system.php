<div id="System_Setting" class="divMode_1" style="display: none">
    <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['system_setting'];?></div>

    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_sys_date'];?>(UTC):</div>
        <div class="col t2">
            <form style="margin: 3px 0px">
                <span id="currentSystemTime"></span>&nbsp;
                <input type="datetime-local" id="newTime" value="" required class="t3 w3-submit w3-border w3-round">
                <input type="button" value="<?php echo $text['save'];?>" class="all-btn w3-submit w3-border w3-round-large" style="float: right" onclick="time_save()">
            </form>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col t1"><?php echo $text['system_export_config'];?>:</div>
        <div class="col t2">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Export_SystemConfig();"><?php echo $text['system_export_config'];?></button>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_import_config'];?>:</div>
        <div class="col t2">
            <input type="file" id="import-file-uploader" data-target="import-file-uploader" accept=".cfg" class="t3 w3-submit w3-border w3-round">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Import_SystemConfig();"><?php echo $text['system_import_config'];?></button>
        </div>        
    </div>          
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_firmware_update'];?>:</div>
        <div class="col t2">
            <input type="file" id="firmware-file-uploader" data-target="firmware-file-uploader" accept=".cfg" class="t3 w3-submit w3-border w3-round">
            <button class="all-btn w3-button w3-border w3-round-large" style="float: right" onclick="Firmware_Update();"><?php echo $text['system_firmware_update'];?></button>
        </div>        
    </div>  
    
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_diskfull_warning']; ?>:</div>
        <div class="col t2">
            <div class="progress" style="height: 20px; width: 60%; background-color: #eee; border-radius: 10px;">
                <div id="disk-usage-bar"
                    class="progress-bar"
                    style="width: 0%; height: 60%; border-radius: 10px; text-align: center; color: white;">
                    0%
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const usedPercent = <?php echo is_numeric($data['disk_usage_percent']) ? $data['disk_usage_percent'] : 0; ?>;
        const bar = document.getElementById('disk-usage-bar');

        // 動態設百分比
        bar.style.width = usedPercent + '%';
        bar.textContent = usedPercent + '%';

        // 動態變色
        if (usedPercent < 60) {
            bar.style.backgroundColor = '#4caf50'; // 綠
        } else if (usedPercent < 80) {
            bar.style.backgroundColor = '#ff9900'; // 橘
        } else {
            bar.style.backgroundColor = '#e53935'; // 紅
        }
    })();
</script>