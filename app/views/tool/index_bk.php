<link rel="stylesheet" href="<?php echo URLROOT; ?>css/tcc_tool.css" type="text/css">

<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['tool_info'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>


    <div class="main-content">
        <div class="center-content">
            <div class="tool-setting">
                <div class="scrollbar" id="style-tool">
                    <div class="force-overflow">
                        <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['tool_info'];?></div>
                        <div class="row t2 border-bottom" >
                            <div class="col-4 t1"><?php echo $text['tool_type'];?>:</div>
                            <div class="col t2">
                                <div ></div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['tool_sn'];?>:</div>
                            <div class="col t2">
                                <div></div>
                            </div>
                        </div> 
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['calibration_value'];?>:</div>
                            <div class="col t2">
                                <div></div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['total_counts'];?>:</div>
                            <div class="col t2">
                                <div></div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['rpm'];?>:</div>
                            <div class="col t2">
                                <div></div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['torque'];?> (<?php echo $text['Nm'];?>):</div>
                            <div class="col t2">
                                <div></div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">    
                            <div class="col-4 t1"><?php echo $text['sw_version'];?>:</div>
                            <div class="col t2">
                                <div></div>
                            </div>
                        </div>    
                        
                        <hr>
                        
                        <div class="col t1" style="padding-left: 3%;font-weight: bold; padding-top: 1%"><?php echo $text['controller_info'];?></div>            
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['controller_sn'];?>:</div>
                            <div class="col t2">
                                <div></div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['controller_version'];?>:</div>
                            <div class="col t2">
                                <div</div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['mcb_version'];?>:</div>
                            <div class="col t2">
                                <div></div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['image_version'];?>:</div>
                            <div class="col t2">
                                <div></div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['network_ip'];?>:</div>
                            <div class="col t2">
                                <div><?php echo $data['IP'];?></div>
                            </div>
                        </div>    
                        <div class="row t2 border-bottom">
                            <div class="col-4 t1"><?php echo $text['Mac'];?>:</div>
                            <div class="col t2">
                                <div><?php echo $data['MAC'];?></div>
                            </div>
                        </div>    
                    </div>
                </div>              
            </div>
        </div>
    </div>
</div>    
</div> 