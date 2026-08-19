<?php if (idas_is_icontroller()): ?>
<link rel="stylesheet" type="text/css" href="<?php echo URLROOT; ?>css/add_seq_step_m.css">
<style type="text/css">
.form-control
{
    width: auto!important;
    display: initial!important;
}
.form-control.is-invalid
{
    padding-right:inherit!important;
}

.small-input {
    max-width: 100%;
    height: 2rem;
    font-size: 13px;
}

.custom-style {
    font-size: 12px;
    margin-right: 5px;
}

    
.is-invalid~.invalid-feedback
{
    display: inline!important;
}

</style>
<?php
// 預設空字串
$unscrew_torque_threshold = '';

if ($data['type'] == 'edit') {
    $raw_val = $data['sequences']['unscrew_torque_threshold'];
    $seq_unit = $data['sequences']['seq_unit'];  // 假設 seq_unit 也從 DB 帶出
    
    $decimalMap = [
        0 => 2,
        1 => 3, 
        2 => 3,
        3 => 4,
        4 => 1,
    ];
    
    $decimals = $decimalMap[$seq_unit] ?? 2;
    
    if ($raw_val !== '' && is_numeric($raw_val)) {
        $unscrew_torque_threshold = number_format((float)$raw_val, $decimals, '.', '');
    }
}
?>

<?php $url = '?url=Sequences/index/' . $data['job_id']; ?>
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <header id="header">
 	        <h3><?php echo ($data['type'] == 'edit') ? $text['edit_seq'] : $text['new_seq']; ?></h3>
        </header>
    </div>
    <div style="display:none;">
        <input id="tool_max_torque" value="<?php echo $data['tools_info']['max_torque']; ?>">
        <input id="tool_min_torque" value="<?php echo $data['tools_info']['min_torque']; ?>">
        <input id="tool_max_rpm" value="<?php echo $data['tools_info']['max_rpm']; ?>">
        <input id="tool_min_rpm" value="<?php echo $data['tools_info']['min_rpm']; ?>">
        <input id="seq_unit_code" value="<?php echo $data['torque_unit_code']; ?>">
        
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:3.2vmin;color: #000; padding-left: 0%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="8" maxlength="20" value="<?php echo $data['job_id'];?>" disabled
                style="height:28px; font-size:3.2vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:3.2vmin;color: #000; padding-left: 0%" for="seq_id"><?php echo $text['seq_id'];?> :</label>&nbsp;
                <input type="text" id="seq_id" name="seq_id" size="8" maxlength="20" value="<?php echo $data['seq_id'];?>" disabled
                style="height:28px; font-size:3.2vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">
                <?php $url ='?url=Sequences/index/'.$data['job_id'];?>
                <button id="back_btn" type="button" onclick="window.location.href='<?php echo $url; ?>';"><?php echo $text['return']; ?></button>

            </div>

            <div class="new-container">
                <div class="newSeq-scrollbar" id="style-newSeq">
                    <div class="newSeq-force-overflow">
                        <div style="background-color: #F2F1F1; padding-left: 2%">
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['seq_name'];?>:</div>
                                <div class="col-4 t2">
                                    <input id="SEQname" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['SEQname'] : 'SEQ-'.$data['next_seq_id']; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 0px 3px;">
                    
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['tightening_repeat'];?>:</div>
                                <div class="col-4 t2">
                                    <input id="seq_repeat"  class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['seq_repeat'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['Timeout'];?> (sec):</div>
                                <div class="col-4 t2">
                                    <input id="timeout" class="t2 form-control small-input"value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['timeout'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col t2" style="padding-left: 0;">
                                    <label style="margin-left: -10px;">(0-60)</label>
                                </div>
                            </div>

                            <div class="row" >
                                <div class="col-5 t1"><?php echo $text['DT_Time'];?> (<?php echo $text['Second'];?>):</div>
                                <div class="col-4 t2">
                                    <input id="dt_time" class="t2 form-control small-input" value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['dt_time'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col t2" style="padding-left: 0;">
                                    <label style="margin-left: -10px;">(0-99)</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['TT_Time'];?> (<?php echo $text['Second'];?>):</div>
                                <div class="col-4 t2">
                                    <input id="tt_time" class="t2 form-control small-input" value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['tt_time'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col t2" style="padding-left: 0;">
                                    <label style="margin-left: -10px;">(0-6000)</label>
                                </div>
                            </div>

                            <div class="row">
                                <div  class="col-5 t1"><?php echo $text['OK-Sequence'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ok_seq" id="ok_seq_off" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_seq'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="ok_seq_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ok_seq" id="ok_seq_on" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_seq'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="ok_seq_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['OK_Sequence_Stop'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ok_stop" id="seq_stop_off" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_stop'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="seq_stop_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ok_stop" id="seq_stop_on" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_stop'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="seq_stop_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['NG_Stop'];?> (0-9):</div>
                                <div class="col t2">
                                    <select id="ng_stop" class="form-select" style="width: 110px;">
                                        <?php 
                                            for ($i = 0; $i <= 9; $i++) {
                                                echo '<option value="' . $i . '" ' . (($data['type'] == 'edit' && $data['sequences']['ng_stop'] == $i) ? 'selected' : '') . '>' . $i . '</option>';
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row" >
                                <div class="col-5 t1"><?php echo $text['Accumulate_Angle'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="accu_angle" id="accu_angle_off" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['accu_angle'] == 0) ? 'checked' : ''; ?>  >
                                        <label class="form-check-label" for="accu_angle_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="accu_angle" id="accu_angle_on" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['accu_angle'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="accu_angle_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row" >
                                <div class="col-5 t1"><?php echo $text['Angle_Calculation'];?> (<?php echo $text['step'];?>):</div>
                                <div class="col t2">
                                    <?php if($data['type'] =="edit"){
                                        $digits = str_split($data['sequences']['Thread_Calcu']);                              
                                    }else{

                                    }?>
                                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                                        <div class="form-check form-check-inline zoom">
                                            <input class="form-check-input" type="checkbox" id="Thread_Calcu_<?php echo $i; ?>" value="<?php echo $i; ?>" onchange="getCheckboxValue_seq()" >
                                            <label class="form-check-label" for="Thread_Calcu_<?php echo $i; ?>"><?php echo $i; ?></label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 0px 3px;">

                            <div class="row">
                                <div class="col fw-bolder t1"><?php echo $text['Reverse'];?></div>
                            </div>

                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['rev_count'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_count_switch" id="unscrew_count_switch_off" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_count_switch'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="unscrew_count_switch_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_count_switch" id="unscrew_count_switch_on" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_count_switch'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="unscrew_count_switch_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['NG_Reverse'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ng_unscrew" id="ng_unscrew_off" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ng_unscrew'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="ng_unscrew_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ng_unscrew" id="ng_unscrew_on" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ng_unscrew'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="ng_unscrew_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>  

                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['Reverse_mode'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_auto" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="unscrew_mode_auto"><?php echo $text['Auto_text'] ; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom" style="margin-left: -7px;">
                                        <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_custom" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="unscrew_mode_custom"><?php echo $text['Custom_text']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row" id="div_speed">
                                <div class="col-5 t1"><?php echo $text['reverse_rpm'];?> (rpm):</div>
                                <div class="col-4 t2">
                                    <input id="unscrew_rpm" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_rpm'] : ''; ?>">
                                     <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row" id="div_torque_threshold">
                                <div class="col-5 t1"><?php echo $text['Threshold_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <div class="col-4 t2">
                                    <input id="unscrew_torque_threshold"class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_torque_threshold'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row" id="div_angle_threshold">
                                <div class="col-5 t1"><?php echo $text['Threshold_Angle'];?>:</div>
                                <div class="col-4 t2">
                                    <input id="unscrew_angle_threshold"class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_angle_threshold'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row" id="div_direction">
                                <div class="col-5 t1"><?php echo $text['direction'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_dir" id="unscrew_dir_cw" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_dir'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="unscrew_dir_cw"><?php echo $text['CW']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_dir" id="unscrew_dir_ccw" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_dir'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="unscrew_dir_ccw"><?php echo $text['CCW']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row" id="div_force">
                                <div class="col-5 t1"><?php echo $text['Force'];?> (%):</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_forcemode" id="unscrew_forcemode_on" value="0"
                                        <?php  echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] >= 1 && $data['sequences']['unscrew_force'] <= 100) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="force_on"><?php echo $text['switch_on']; ?></label>
                                        <input  id="unscrew_force"  class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] >= 1 && $data['sequences']['unscrew_force'] <= 100) ? $data['sequences']['unscrew_force'] : ''; ?>" 
                                        style="width: 40%!important;display: inline-block!important;">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_forcemode" id="unscrew_forcemode_unlimit" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force']  > 101) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="force_unlimit"><?php echo $text['Unlimited_text']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio"  name="unscrew_forcemode" id="unscrew_forcemode_off" value="2" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="force_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div  class="col-5 t1"><?php echo $text['total_high_angle'];?>:</div>
                                <div class="col-4 t2">
                                        <input id="total_angle_limit" class="t2 form-control small-input"  value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['total_angle_limit'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div  class="col-5 t1"><?php echo $text['total_low_angle'];?>:</div>
                                <div class="col-4 t2">
                                        <input id="total_angle_lower" class="t2 form-control small-input"  value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['total_angle_lower'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="text-align: center;margin-top: 30px; margin-bottom:10px">
                <button id="button1" class="button w3-button w3-border w3-round-large"  onclick="<?php echo $data['type'] == 'new' ? 'save_sequence()' : 'edit_sequence()'; ?>">
                    <?php echo $text['save']; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- 加载動畫 OP -->
       <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->    
</div>

<?php require_once '../app/views/sequences/add_seq_share.php';?>
<script>
function goBackOrRedirect() {
    if (document.referrer) {
        // 嘗試回上一頁
        history.back();

        // 如果 500ms 內沒成功切換頁面，自動導向備援 URL
        setTimeout(function () {
            window.location.href = "<?php echo $url; ?>";
        }, 500);
    } else {
        // 若無上一頁紀錄，直接導向指定 URL
        window.location.href = "<?php echo $url; ?>";
    }
}
</script>
<?php else: ?>
<?php $idasSeqEditUserLaw = (string)($_COOKIE['user_law'] ?? '1'); ?>
<link rel="stylesheet" type="text/css" href="<?php echo URLROOT; ?>css/add_seq_step_m.css">
<style type="text/css">
.form-control
{
    width: auto!important;
    display: initial!important;
}
.form-control.is-invalid
{
    padding-right:inherit!important;
}

.small-input {
    max-width: 100%;
    height: 2rem;
    font-size: 13px;
}

.custom-style {
    font-size: 12px;
    margin-right: 5px;
}

    
.is-invalid~.invalid-feedback
{
    display: inline!important;
}

</style>
<?php
// 預設空字串
$unscrew_torque_threshold = '';

if ($data['type'] == 'edit') {
    $raw_val = $data['sequences']['unscrew_torque_threshold'];
    $seq_unit = $data['sequences']['seq_unit'];  // 假設 seq_unit 也從 DB 帶出
    
    $decimalMap = [
        0 => 2,
        1 => 3, 
        2 => 3,
        3 => 4,
        4 => 1,
    ];
    
    $decimals = $decimalMap[$seq_unit] ?? 2;
    
    if ($raw_val !== '' && is_numeric($raw_val)) {
        $unscrew_torque_threshold = number_format((float)$raw_val, $decimals, '.', '');
    }
}
?>

<?php $url = '?url=Sequences/index/' . $data['job_id']; ?>
<div class="container-ms">
    <div class="w3-text-white w3-center">
        <header id="header">
 	        <h3><?php echo ($data['type'] == 'edit') ? $text['edit_seq'] : $text['new_seq']; ?></h3>
        </header>
    </div>
    <div style="display:none;">
        <input id="tool_max_torque" value="<?php echo $data['tools_info']['max_torque']; ?>">
        <input id="tool_min_torque" value="<?php echo $data['tools_info']['min_torque']; ?>">
        <input id="tool_max_rpm" value="<?php echo $data['tools_info']['max_rpm']; ?>">
        <input id="tool_min_rpm" value="<?php echo $data['tools_info']['min_rpm']; ?>">
        <input id="seq_unit_code" value="<?php echo $data['torque_unit_code']; ?>">
        
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="topnav">
                <label style="font-size:3.2vmin;color: #000; padding-left: 0%" for="job_id"><?php echo $text['job_id'];?> :</label>&nbsp;
                <input type="text" id="job_id" name="job_id" size="8" maxlength="20" value="<?php echo $data['job_id'];?>" disabled
                style="height:28px; font-size:3.2vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">

                <label style="font-size:3.2vmin;color: #000; padding-left: 0%" for="seq_id"><?php echo $text['seq_id'];?> :</label>&nbsp;
                <input type="text" id="seq_id" name="seq_id" size="8" maxlength="20" value="<?php echo $data['seq_id'];?>" disabled
                style="height:28px; font-size:3.2vmin;text-align: center; background-color: #DDDDDD; border:0; margin: 3px;">
                <?php $url ='?url=Sequences/index/'.$data['job_id'];?>
                <button id="back_btn" type="button" onclick="window.location.href='<?php echo $url; ?>';"><?php echo $text['return']; ?></button>

            </div>

            <div class="new-container">
                <div class="newSeq-scrollbar" id="style-newSeq">
                    <div class="newSeq-force-overflow">
                        <div style="background-color: #F2F1F1; padding-left: 2%">
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['seq_name'];?>:</div>
                                <div class="col-4 t2">
                                    <input id="SEQname" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['SEQname'] : 'SEQ-'.$data['next_seq_id']; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 0px 3px;">
                    
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['tightening_repeat'];?>:</div>
                                <div class="col-4 t2">
                                    <input id="seq_repeat"  class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['seq_repeat'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['Timeout'];?> (sec):</div>
                                <div class="col-4 t2">
                                    <input id="timeout" class="t2 form-control small-input"value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['timeout'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col t2" style="padding-left: 0;">
                                    <label style="margin-left: -10px;">(0-60)</label>
                                </div>
                            </div>

                            <div class="row" >
                                <div class="col-5 t1"><?php echo $text['DT_Time'];?> (<?php echo $text['Second'];?>):</div>
                                <div class="col-4 t2">
                                    <input id="dt_time" class="t2 form-control small-input" value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['dt_time'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col t2" style="padding-left: 0;">
                                    <label style="margin-left: -10px;">(0-99)</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['TT_Time'];?> (<?php echo $text['Second'];?>):</div>
                                <div class="col-4 t2">
                                    <input id="tt_time" class="t2 form-control small-input" value ="<?php echo ($data['type'] == 'edit') ? $data['sequences']['tt_time'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                                <div class="col t2" style="padding-left: 0;">
                                    <label style="margin-left: -10px;">(0-6000)</label>
                                </div>
                            </div>

                            <div class="row">
                                <div  class="col-5 t1"><?php echo $text['OK-Sequence'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ok_seq" id="ok_seq_off" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_seq'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="ok_seq_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ok_seq" id="ok_seq_on" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_seq'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="ok_seq_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['OK_Sequence_Stop'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ok_stop" id="seq_stop_off" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_stop'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="seq_stop_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ok_stop" id="seq_stop_on" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ok_stop'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="seq_stop_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['NG_Stop'];?> (0-9):</div>
                                <div class="col t2">
                                    <select id="ng_stop" class="form-select" style="width: 110px;">
                                        <?php 
                                            for ($i = 0; $i <= 9; $i++) {
                                                echo '<option value="' . $i . '" ' . (($data['type'] == 'edit' && $data['sequences']['ng_stop'] == $i) ? 'selected' : '') . '>' . $i . '</option>';
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row" >
                                <div class="col-5 t1"><?php echo $text['Accumulate_Angle'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="accu_angle" id="accu_angle_off" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['accu_angle'] == 0) ? 'checked' : ''; ?>  >
                                        <label class="form-check-label" for="accu_angle_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="accu_angle" id="accu_angle_on" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['accu_angle'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="accu_angle_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="row" >
                                <div class="col-5 t1"><?php echo $text['Angle_Calculation'];?> (<?php echo $text['step'];?>):</div>
                                <div class="col t2">
                                    <?php if($data['type'] =="edit"){
                                        $digits = str_split($data['sequences']['Thread_Calcu']);                              
                                    }else{

                                    }?>
                                    <?php for ($i = 1; $i <= 5; $i++) { ?>
                                        <div class="form-check form-check-inline zoom">
                                            <input class="form-check-input" type="checkbox" id="Thread_Calcu_<?php echo $i; ?>" value="<?php echo $i; ?>" onchange="getCheckboxValue_seq()" >
                                            <label class="form-check-label" for="Thread_Calcu_<?php echo $i; ?>"><?php echo $i; ?></label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>

                            <hr style="border: 1px solid #ccc; width: 96%; margin: 0px 3px;">

                            <div class="row">
                                <div class="col fw-bolder t1"><?php echo $text['Reverse'];?></div>
                            </div>

                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['rev_count'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_count_switch" id="unscrew_count_switch_off" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_count_switch'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="unscrew_count_switch_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_count_switch" id="unscrew_count_switch_on" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_count_switch'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="unscrew_count_switch_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['NG_Reverse'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ng_unscrew" id="ng_unscrew_off" value="0"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ng_unscrew'] == 0) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="ng_unscrew_off"><?php echo $text['switch_off']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="ng_unscrew" id="ng_unscrew_on" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['ng_unscrew'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="ng_unscrew_on"><?php echo $text['switch_on']; ?></label>
                                    </div>
                                </div>
                            </div>  

                            <div class="row">
                                <div class="col-5 t1"><?php echo $text['Reverse_mode'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_auto" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 1) ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="unscrew_mode_auto"><?php echo $text['Auto_text'] ; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom" style="margin-left: -7px;">
                                        <input class="form-check-input" type="radio" name="unscrew_mode" id="unscrew_mode_custom" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_mode'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="unscrew_mode_custom"><?php echo $text['Custom_text']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row" id="div_speed">
                                <div class="col-5 t1"><?php echo $text['reverse_rpm'];?> (rpm):</div>
                                <div class="col-4 t2">
                                    <input id="unscrew_rpm" class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_rpm'] : ''; ?>">
                                     <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row" id="div_torque_threshold">
                                <div class="col-5 t1"><?php echo $text['Threshold_Torque'];?> (<?php echo $text[$data['torque_unit']]; ?>):</div>
                                <div class="col-4 t2">
                                    <input id="unscrew_torque_threshold"class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_torque_threshold'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row" id="div_angle_threshold">
                                <div class="col-5 t1"><?php echo $text['Threshold_Angle'];?>:</div>
                                <div class="col-4 t2">
                                    <input id="unscrew_angle_threshold"class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['unscrew_angle_threshold'] : ''; ?>">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row" id="div_direction">
                                <div class="col-5 t1"><?php echo $text['direction'];?>:</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_dir" id="unscrew_dir_cw" value="1"
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_dir'] == 1) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="unscrew_dir_cw"><?php echo $text['CW']; ?></label>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_dir" id="unscrew_dir_ccw" value="0" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_dir'] == 0) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="unscrew_dir_ccw"><?php echo $text['CCW']; ?></label>
                                    </div>
                                </div>
                            </div>

                            <div class="row" id="div_force">
                                <div class="col-5 t1"><?php echo $text['Force'];?> (%):</div>
                                <div class="col t2">
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_forcemode" id="unscrew_forcemode_on" value="0"
                                        <?php  echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] >= 1 && $data['sequences']['unscrew_force'] <= 100) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="force_on"><?php echo $text['switch_on']; ?></label>
                                        <input  id="unscrew_force"  class="t2 form-control small-input" value="<?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force'] >= 1 && $data['sequences']['unscrew_force'] <= 100) ? $data['sequences']['unscrew_force'] : ''; ?>" 
                                        style="width: 40%!important;display: inline-block!important;">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                    <div class="form-check form-check-inline zoom">
                                        <input class="form-check-input" type="radio" name="unscrew_forcemode" id="unscrew_forcemode_unlimit" value="1" 
                                        <?php echo ($data['type'] == 'edit' && $data['sequences']['unscrew_force']  > 101) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="force_unlimit"><?php echo $text['Unlimited_text']; ?></label>
                                    </div>
                                    
                                </div>
                            </div>

                            <div class="row">
                                <div  class="col-5 t1"><?php echo $text['total_high_angle'];?>:</div>
                                <div class="col-4 t2">
                                        <input id="total_angle_limit" class="t2 form-control small-input"  value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['total_angle_limit'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="row">
                                <div  class="col-5 t1"><?php echo $text['total_low_angle'];?>:</div>
                                <div class="col-4 t2">
                                        <input id="total_angle_lower" class="t2 form-control small-input"  value="<?php echo ($data['type'] == 'edit') ? $data['sequences']['total_angle_lower'] : ''; ?>">
                                        <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div style="text-align: center;margin-top: 30px; margin-bottom:10px">
                <button id="button1" class="button w3-button w3-border w3-round-large"  onclick="<?php echo $data['type'] == 'new' ? 'save_sequence()' : 'edit_sequence()'; ?>">
                    <?php echo $text['save']; ?>
                </button>
            </div>
        </div>
    </div>

    <!-- 加载動畫 OP -->
       <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->    
</div>


<script>
(function () {
    var currentLaw = <?php echo json_encode($idasSeqEditUserLaw, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    if (String(currentLaw) !== '3') return;

    function lockSeqSaveButton() {
        var btn = document.getElementById('button1');
        if (!btn) return;
        btn.removeAttribute('onclick');
        btn.onclick = null;
        btn.disabled = true;
        btn.setAttribute('aria-disabled', 'true');
        btn.classList.add('operator-seq-save-disabled');
        btn.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            return false;
        }, true);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', lockSeqSaveButton);
    } else {
        lockSeqSaveButton();
    }
})();
</script>
<style>
.operator-seq-save-disabled {
    opacity: 0.45 !important;
    filter: grayscale(1) !important;
    cursor: not-allowed !important;
    pointer-events: none !important;
}
</style>

<?php require_once '../app/views/sequences/add_seq_share.php';?>
<script>
function goBackOrRedirect() {
    if (document.referrer) {
        // 嘗試回上一頁
        history.back();

        // 如果 500ms 內沒成功切換頁面，自動導向備援 URL
        setTimeout(function () {
            window.location.href = "<?php echo $url; ?>";
        }, 500);
    } else {
        // 若無上一頁紀錄，直接導向指定 URL
        window.location.href = "<?php echo $url; ?>";
    }
}
</script>
<?php endif; ?>
