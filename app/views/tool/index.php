<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_tool.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table>
            <tr id="header">
                <td width="100%">
                    <h3><?php echo $text['tool']; ?></h3>
                </td>
                <td>
                    <button class="w3-btn w3-round-large" style="height:50px;padding: 0" onclick="window.location.href='./?url=Dashboards'"> <img src="../public/img/btn_home.png"></button>
                </td>
            </tr>
        </table>
    </div>

    <div class="main-content">
        <div class="center-content">
            <div class="container" style="padding: 10px;border-radius: 5px ;box-shadow: 0px 3px 8px 0px rgba(0, 0, 0, 0.2);">
                <div id="Tool_Setting">
                        <h3 style="margin: 5px 3px 10px"><b><?php echo $text['tool_info'];?></b></h3>
                        <div class="row  border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px" ><?php echo $text['tool_type'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['tool_sn'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['sw_version'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['maintain_counts'];?>:</div>
                            <div class="col-2" style="font-size: 18px; margin: 5px 5px 5px">---------</div>&nbsp;
                           
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['total_counts'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['tool_max_torque2'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['tool_max_speed'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['calibration_value'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>

                        <hr>

                        <h3 style="margin: 5px 3px 10px"><b><?php echo $text['controller_info'];?></b></h3>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['controller_sn'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['controller_version'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['mcb_version'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['image_version'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px">---------</div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['network_ip'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['IP']; ?></div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['Mac'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['MAC']; ?></div>
                        </div>
                </div>
            </div>
        </div>
    </div>
</div>