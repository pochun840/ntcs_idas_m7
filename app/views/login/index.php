<div class="container-ms">
    <div class="buttonbox" style="text-align: right; margin: 10px">
    
    <?php foreach(LANGUAGE as $key =>$val){?>
        <input type="button" name='<?php echo $val[1];?>' id= '<?php echo $val[1];?>'  value="<?php echo $val[0];?>" onclick="language_change()" >
    <?php } ?>
    </div>
    <div>
        <h1 class="col-ms-3 pt-3" style="font-size: 50px; text-align: center; color: #fff"><?php echo $text['login_text']; ?></h1>
    </div>
    <form class="pt-4" action="?url=Logins" method="POST">
      <input type="password" name="password" placeholder="<?php echo $text['password_text']; ?>" required>
      <button type="submit"><?php echo $text['login_text']; ?></button>
    </form>
</div>

  <script>

    function language_change() {
        var language = event.target.id;
        $.ajax({
        type: "POST",
        url: "?url=Dashboards/change_language",
        data: {'language':language},
        dataType: "json",
        encode: true,
        async: false,
        }).done(function (data) {
            location.reload();
        });
    }

    $(document).ready(function () {
        <?php 
            if($data['error_message'] != ''){
                echo "alert('",$data['error_message'],"')";
            }
        ?>
    });

  </script>
<style>
.container-ms
{
    margin: 0 auto;
    padding: 20px;
    width: 100%;
    height: 100vh;
    background-color: #000000;
    background-image: url(./img/vn.jpg);
    background-size: cover;
    background-position: center;
}

.center-content
{
    display: flex;
    justify-content: center;
    align-items: center;
}

    /* Button Language */
.buttonbox input
{
    margin-bottom: 5px;
}

.buttonbox input
{
    border: none;
    outline: none;
    width:90px;
    height: 40px;
    background: #888888;
    color: #fff;
    font-size: 18px;
    border: 1px outset #666;
    border-radius: 5px;
    text-align:center;
}
.buttonbox input:hover
{
    cursor: pointer;
    background:#333300;
    color: white;
}

.buttonbox input:active
{
    background-color: #DDDDDD;
    box-shadow: 0 5px #666;
    transform: translateY(4px);
}

/* Login */
:focus { outline: none; }
::-webkit-input-placeholder { color: #DEDFDF; }
::-moz-placeholder { color: #DEDFDF; }
:-moz-placeholder { color: #DEDFDF; }
::-ms-input-placeholder { color: #DEDFDF; }

form
{
    float: center;
    max-width: 600px;
    height: 370px;
    margin: 0 auto;
    padding: 15vmin;

}

input[type=radio] { display: none; }

input[type=text],
input[type=password]
{
    background: #fff;
    border: none;
    border-radius: 8px;
    font-size: 25px;
    font-family: 'Raleway', sans-serif;
    height: 72px;
    width: 100%;
    margin-bottom: 10px;
    opacity: 1;
    text-indent: 20px;
    transition: all .2s ease-in-out;
}

button
{
    background: #079BCF;
    border: none;
    border-radius: 8px;
    color: #fff;
    cursor: pointer;
    font-family: 'Raleway', sans-serif;
    font-size: 30px;
    height: 72px;
    width: 100%;
    margin-bottom: 10px;
    overflow: hidden;
    transition: all .3s cubic-bezier(.6,0,.4,1);
}

button
{
    display: block;
    line-height: 72px;
    position: relative;
    top: 0px;
    transform: translate3d(0,0,0);
}

button:hover
{
    background: #007BA5;
}
</style>