
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_seq.css" type="text/css">


<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo "NEW";?></h3></td>
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:20px;color: #000; padding-left: 2%" for="job_id"><?php echo $text['job_id'];?> :</label>
                <input type="text" id="job_id" name="job_id" size="10" maxlength="20" value="<?php echo $data['job_id'];?>" disabled
                    style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px; margin-right: 250px;">

                <label style="font-size:20px;color: #000; padding-left: 2%" for="seq_id"><?php echo $text['seq_id'];?> :</label>
                <input type="text" id="seq_id" name="seq_id" size="10" maxlength="20" value="<?php echo $data['seq_id'];?>" disabled
                    style="height:28px; font-size:20px;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <button id="back_btn" type="button" onclick="window.location.href='?url=Jobs/index'"><?php echo $text['return'];?></button>
            </div>

            <div class="table-container">   
            <div class="scrollbar" id="style-jobtable" style="background-color: #f0f0f0;">
                <div class="scrollbar-force-overflow" style="background-color: #f0f0f0;">

                    <div class="row" style="margin-bottom: 20px; margin-left: 20px;">
                        <div class="col-6 t1" style="font-size: 16px; text-align: left;">
                            <?php echo $text['seq_name']; ?> :
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control input-ms" id="SEQname" maxlength="" style="font-size: 14px; width: 150px; display: inline-block; margin-left: -700px;">
                        </div>
                    </div>

                    <hr style="border: 1px solid #ccc; width: 50%; margin: 20px 0;">

                    <div class="row" style="margin-bottom: 20px; margin-left: 20px;">
                        <div class="col-6 t1" style="font-size: 16px; text-align: left;">
                            <?php echo $text['tighten_repeat']; ?> :
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control input-ms" id="SEQname" maxlength="" style="font-size: 14px; width: 150px; display: inline-block; margin-left: -700px;">
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 20px; margin-left: 20px;">
                        <div class="col-6 t1" style="font-size: 16px; text-align: left;">
                            <?php echo "Timeout(Sec)"; ?> :
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control input-ms" id="SEQname" maxlength="" style="font-size: 14px; width: 150px; display: inline-block; margin-left: -700px;"> (0-60)
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 20px; margin-left: 20px;">
                        <div class="col-6 t1" style="font-size: 16px; text-align: left;">
                            <?php echo $text['OK_Sequence']; ?> :
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center" style="margin-top: 5px;">
                                <div class="form-check me-3"style="margin-left: -700px;" > <!-- 使用 Bootstrap 的間距類 -->
                                    <input class="form-check-input" type="radio" name="job_ok" id="job_off" value="0">
                                    <label class="form-check-label" for="job_off"><?php echo $text['OFF_text']; ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="job_ok" id="job_ok" value="1">
                                    <label class="form-check-label" for="job_ok"><?php echo $text['ON_text']; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 20px; margin-left: 20px;">
                        <div class="col-6 t1" style="font-size: 16px; text-align: left;">
                            <?php echo $text['OK_Sequence_Stop']; ?> :
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center" style="margin-top: 5px;">
                                <div class="form-check me-3"style="margin-left: -700px;" > <!-- 使用 Bootstrap 的間距類 -->
                                    <input class="form-check-input" type="radio" name="job_ok" id="job_off" value="0">
                                    <label class="form-check-label" for="job_off"><?php echo $text['OFF_text']; ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="job_ok" id="job_ok" value="1">
                                    <label class="form-check-label" for="job_ok"><?php echo $text['ON_text']; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row" style="margin-bottom: 20px; margin-left: 20px;">
                        <div class="col-6 t1" style="font-size: 16px; text-align: left;">
                            <?php echo "Reverse Count"; ?> :
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center" style="margin-top: 5px;">
                                <div class="form-check me-3"style="margin-left: -700px;" > <!-- 使用 Bootstrap 的間距類 -->
                                    <input class="form-check-input" type="radio" name="job_ok" id="job_off" value="0">
                                    <label class="form-check-label" for="job_off"><?php echo $text['OFF_text']; ?></label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="job_ok" id="job_ok" value="1">
                                    <label class="form-check-label" for="job_ok"><?php echo $text['ON_text']; ?></label>
                                </div>
                            </div>
                        </div>
                    </div>


                   

                   
                </div>
            </div>

            </div>
        </div>
    </div>

    
    


 

</div>

