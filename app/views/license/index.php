<?php require APPROOT . 'views/inc/header.php'; ?>
<link rel="stylesheet" href="<?php echo URLROOT; ?>css/w3.css" type="text/css">
<!-- <link rel="stylesheet" href="<?php echo URLROOT; ?>css/datatables.css" type="text/css"> -->

<div class="container-ms">
    <h1 style="text-align: center; color: #fff">iDAS Software Licensing</h1>
</div>
<div style="color: #fff;position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%);">
    <div class="row" style="height: 250px">
        <div class="col-2 t1" style="font-size: 18px; font-weight: bold;padding: 0;">Key :</div>
        <div class="col-9 t2">
            <?php 
                if($data['activate_status'] == 2){
                    echo '<textarea id="activate_key" name="w3review" rows="4" cols="30" disabled></textarea>';
                }else{
                    echo '<textarea id="activate_key" name="w3review" rows="4" cols="30"></textarea>';
                }
            ?>
        </div>
    </div>
    <div class="w3-center">
        <?php 
            if($data['activate_status'] == 2){
                // echo '<button id="Activate" class="button-footer" onclick="location.href=\'../\'">Back</button>';
            }else{
                echo '<button id="Activate" class="button-footer" onclick="activate()">Activate</button>';
            }
        ?>
        <button id="Activate" class="button-footer" onclick="location.href='..'">Back</button>
        
    </div>
    <div class="w3-center" style="padding-top: 10%; font-size: 18px; padding-bottom: 10%;">
        <?php 
            if($data['activate_status'] == 2){
                echo '<label>Software license is permanent.</label>';
            }else if ($data['activate_status'] == 1) {
                echo '<label>Software license expiration date is '.$data['expired_date'].'.</label>';
            }else{
                echo '<label>Software license is inactive</label>';
            }
        ?>
    </div>
</div>

<style>
body
{
    margin: 0;
    padding: 0;
    background: linear-gradient(to right, rgb(156 156 156), rgb(48, 67, 82));
    -webkit-background-size: cover cover;
    background-size: cover cover;
    font-family: "helvetica neue";
}
.container-ms
{
    margin: 0 auto;
    overflow: hidden;
    width: calc(100%);
    position: static;
    padding-top: 38px;
}
.button-footer
{
    background-color: #0F3B4E;
    border: 1px solid #999999;
    box-shadow: 2px 2px 8px rgba(0, 0, 0, 0.5);
    width: auto;
    height: 40px;
    width: 100px;
    padding: 0 10px;
    margin-top: 3px;
    margin-left: 10px;
    border-radius: 5px;
    margin-bottom: 2px;
    color: #fff;
    text-align: center;
    font-size: 18px;
}

</style>

<script type="text/javascript">
    <?php if($data['activate_status'] != 2){ ?>
        function activate() {
            let activate_key = document.getElementById('activate_key').value;

            if(activate_key != ''){
                $.ajax({ // 提醒
                    type: "POST",
                    data: {
                        'activate_key': activate_key.trim(),
                    },
                    dataType: "json",
                    url: "?url=Licenses/AcitveKeyCheck",
                }).done(function(data) { //成功且有回傳值才會執行

                    document.getElementById('activate_key').value = '';
                    if (data.error_message != '') {
                        Swal.fire({ // DB sync notice
                            title: 'Error',
                            text: data.error_message,
                        })
                    } else {
                        Swal.fire('success!', '', '');
                        window.location = window.location.href;
                    }
                }).fail(function() {
                    // history.go(0);
                    document.getElementById('activate_key').value = ''
                });
            }
        }
    <?php } ?>
    
</script>

<?php require APPROOT . 'views/inc/footer.php'; ?>