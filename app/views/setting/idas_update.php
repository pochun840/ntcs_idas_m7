

<div id="iDas-Update_Setting" class="divMode" style="display: none;">
    <div class="row t2" style="padding-top: 30px">
        <div class="col-3 t1"><?php echo $text['system_idas_current_version'];?>:</div>
        <div class="col-3 t2">
            
            <input id="idas_software_version" name="idas_software_version" type="text" value="<?php echo $data['idas_version'];?>" style="height: 32px" class="form-control" value="" disabled>
        </div>
    </div>
 
    
    <div class="row t2">
        <div class="col-3 t1"><?php echo $text['system_idas_upload_file'];?>:</div>
        <div class="col-3 t2">
            <input type="file" id="file-uploader" data-target="file-uploader" accept=".pack" class="form-control" style="height: 32px">
        </div>
    </div>

    <div style="text-align: center;margin-top:50px;">
        <input class="all-btn w3-submit w3-border w3-round-large" type="button" value="<?php echo $text['system_idas_upload_file'];?>" onclick='idas_update();'>
    </div> 
</div>

