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
                       
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['tool_type'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['Tool_Info']['tool_type'];?></div>
                        </div>
                         <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['tool_sn'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['Tool_Info']['tool_sn'];?></div>
                        </div>
                        
                  
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['torque'];?>(<?php echo $text[$data['unit_name']];?>):</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['Tool_Info']['min_torque']."/".$data['Tool_Info']['max_torque']; ?></div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['rpm'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['Tool_Info']['min_rpm']."/".$data['Tool_Info']['max_rpm'];?></div>
                        </div>

                         <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['tools_version'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['tools_version'];?></div>
                        </div>
                     

                        <hr>
                    
                        <h3 style="margin: 5px 3px 10px"><b><?php echo $text['controller_info'];?></b></h3>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['controller_sn'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['Controllers_Info']['device_sn'];?></div>
                        </div>
                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['controller_version'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['Controllers_Info']['device_version'];?></div>
                        </div>

                         <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['cpb_version'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['firmware_version'];?></div>
                        </div>


                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['db_version'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['Controllers_Info']['device_version'];?></div>
                        </div>



                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['image_version'] ;?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['image_version']. "/" .$data['upgrade_ver'];?></div>
                        </div>



                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['network_ip'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['IP']; ?></div>
                        </div>

                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['mask'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo isset($data['netmask']) && $data['netmask'] !== '' ? $data['netmask'] : '--'; ?></div>
                        </div>

                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['gateway'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo isset($data['gateway']) && $data['gateway'] !== '' ? $data['gateway'] : '--'; ?></div>
                        </div>


                        <div class="row border-bottom">
                            <div class="col-6" style="font-size: 18px; margin: 5px 10px 5px"><?php echo $text['Mac'];?>:</div>
                            <div class="col" style="font-size: 18px; margin: 5px 5px 5px"><?php echo $data['MAC']; ?></div>
                        </div>

                        <hr>

                        <div class="row border-bottom" style="display: flex; justify-content: center; align-items: center; height: 150px;">
                            <img 
                                    src="img/qr_code.jpeg" 
                                    alt="QR Code" 
                                    style="width: 150px; height: 150px; cursor: pointer;" 
                                    onclick="window.open('https://www.kilews.com.tw/tc/', '_blank');"
                            >
                        </div>

                </div>
            </div>
        </div>
    </div>
</div>