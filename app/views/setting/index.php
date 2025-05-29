<div class="container-ms">
    <div class="w3-text-white w3-center">
        <table class="no-border">
            <tr id="header">
                <td width="100%"><h3><?php echo $text['setting'];?></h3></td>
                <td><img src="./img/btn_home.png" style="margin-right: 10px" onclick="back()"></td>
            </tr>
        </table>
    </div>
    <div class="main-content">
        <div class="center-content">
            <div class="w3-center">
                <button id="bnt1" name="Controller_Display" class="button active" onclick="OpenButton('Controller')"><?php echo $text['controller_setting'];?></button>
                <button id="bnt2" name="System_Display" class="button" onclick="OpenButton('System')"><?php echo $text['system_setting'];?></button>
                <button id="bnt3" name="Barcode_Display" class="button" onclick="OpenButton('Barcode')"><?php echo $text['system_barcode_setting'] ;?></button>
                <button id="bnt4" name="Connect_Display" class="button" onclick="OpenButton('Connect')"><?php echo $text['system_connect_setting'];?></button>
                <button id="bnt5" name="iDas_Display" class="button" onclick="OpenButton('Update')">iDAS</button>
            </div>
        
            <!-- idas_controller OP -->
                <?php require_once '../app/views/setting/idas_controller.php';?>
            <!-- idas_controller ED -->

            <!-- idas_system OP -->
                <?php require_once '../app/views/setting/idas_system.php';?>
            <!-- idas_system ED -->
               

            <!-- idas_barcode OP -->
                <?php require_once '../app/views/setting/idas_barcode.php';?>
            <!-- idas_barcode ED -->


            <!-- idas_agent OP -->
                <?php require_once '../app/views/setting/idas_agent.php';?>
            <!-- idas_agent ED -->


            <!-- idas_update OP -->
                <?php require_once '../app/views/setting/idas_update.php';?>
            <!-- idas_update ED -->

        </div>
    </div>  
    
    <!-- 加载動畫 OP -->
        <?php require_once '../app/views/inc/include_spinner.php';?>
    <!-- 加载動畫 ED -->

</div>

<script>




function time_save(){
    var newTime = document.getElementById('newTime').value;
    var device_id = <?php echo $data['controller_info']['device_id'];?>;

    //console.log(newTime);
    if(newTime){
        $.ajax({
            url: "?url=Settings/edit_system_date",
            method: "POST",
            data:{ 
                device_id: device_id,
                newTime: newTime

            },
            success: function(response){
                alert(response);
                //history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });       
    }

}


function button_save_password_gust(){

    var device_id = <?php echo $data['controller_info']['device_id'];?>;

    var pass_guest1 = document.getElementById('new_password_guest').value;
    var pass_guest2 = document.getElementById('comfirm_password_guest').value;

    //正規化 密碼格式(1個英文+1個數字,長度:4)
    var pattern = /^(?=.*[A-Za-z])(?=.*\d).{4,}$/;
    if(pass_guest1 == pass_guest2 && pattern.test(pass_guest1)){
        $.ajax({
            url: "?url=Admins/EditGuestPwd",
            method: "POST",
            data:{ 
                device_id: device_id,
                new_password: pass_guest1

            },
            success: function(response) {
                alert(response);
                history.go(0);
            },
            error: function(xhr, status, error) {
                
            }
        });   
    }else{
        alert('密碼格式不符合要求');
    }
    
}


/*const fileUploader = document.querySelector('#file-uploader');

function idas_update() {
    let ff = document.querySelector('#file-uploader').files;
    let bb = document.getElementById("file-uploader").files[0];
    let form = new FormData();
    form.append("file", bb)

    let url = '?url=Settings/iDas_Update';
    $.ajax({ // 提醒
        type: "POST",
        processData: false,
        cache: false,
        contentType: false,
        data: form,
        dataType: "json",
        url: url,
        beforeSend: function() {
            $('#overlay').removeClass('hidden');
        },
    }).done(function(result) { //成功且有回傳值才會執行
        $('#overlay').addClass('hidden');

        if (result.message != '') {
            Swal.fire({ // DB sync notice
                title: 'Error',
                text: result.message,
            })
        } else {
            Swal.fire('', '', 'success');
            setTimeout(function() {history.go(0)}, 2000);
        }
        document.getElementById("file-uploader").value = '';
        
    });
}*/

function OpenButton(ButtonMode){

    if (ButtonMode == "Controller")
    {
        document.getElementById('Controller_Setting').style.display = "";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt1').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");   
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "System")
    {
        document.getElementById('System_Setting').style.display = "";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt2').classList.add("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");

    }
    else if (ButtonMode == "Barcode")
    {
        document.getElementById('Barcode_Setting').style.display = "";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt3').classList.add("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "Connect")
    {
        document.getElementById('Connect_Setting').style.display = "";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('iDas-Update_Setting').style.display = "none";
        document.getElementById('bnt4').classList.add("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
        document.getElementById('bnt5').classList.remove("active");
    }
    else if (ButtonMode == "Update")
    {
        document.getElementById('iDas-Update_Setting').style.display = "";
        document.getElementById('Connect_Setting').style.display = "none";
        document.getElementById('Barcode_Setting').style.display = "none";
        document.getElementById('System_Setting').style.display = "none";
        document.getElementById('Controller_Setting').style.display = "none";
        document.getElementById('bnt5').classList.add("active");
        document.getElementById('bnt4').classList.remove("active");
        document.getElementById('bnt3').classList.remove("active");
        document.getElementById('bnt2').classList.remove("active");
        document.getElementById('bnt1').classList.remove("active");
    }
    else
    {
        //alert("Function ["+ ButtonMode +"] is under constructing ...");
    }
}




</script>    