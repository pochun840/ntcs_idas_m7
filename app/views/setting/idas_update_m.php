<div id="iDas-Update_Setting" class="divMode" style="display: none;">
    <div class="col t1" style="padding-top: 5%">Current iDAS Version:</div>
    <div class="row t2">
        <div class="col t2" style="margin-left: 10%">
            <input id="idas_software_version" name="idas_software_version" type="text" value="<?php echo $data['iDas_Vesion'];?>"  style="height: 32px; width: 250px" class="form-control" disabled>
        </div>
    </div>

    <div class="col t1">Match Controller Version:</div>
    <div class="row t2">
        <div class="col t2" style="margin-left: 10%">
            <input id="match_control_version" name="match_control_version" type="text" value="" style="height: 32px; width: 250px" class="form-control" disabled>
        </div>
    </div>

    <div class="col t1">Upload file:</div>
    <div class="row t2">
        <div class="col t2" style="margin-left: 10%">
            <input type="file" id="file-uploader" data-target="file-uploader" accept=".pack" class="form-control" style="height: 32px; width: 250px">
        </div>
    </div>

    <div style="text-align: center;margin-top:50px;">
        <input class="all-btn w3-submit w3-border w3-round-large" type="button" value="Update" onclick='idas_update();'>
    </div> 
</div>