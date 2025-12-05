<?php

class Core
{
    // 預設 Controller 為 Dashboards
    protected $currentController = 'Dashboards';
    // 預設方法為 index
    protected $currentMethod = 'index';
    // 預設參數為空
    protected $params = [];

    public function __construct()
    {
        
        //加入登入驗證
        require_once dirname(dirname(__FILE__)).'/controllers/Logins.php';
        $login_class = new Logins();  

        // 呼叫 getUrl() 取得 $url 陣列
        $url = $this->getUrl();        

        $valid_result = $login_class->index($url);//將url傳入

        //condition 1 url = 
        if($url[0] == 'Logins' && $valid_result){ //登入後的預設路徑
            $url[0] = 'Dashboards';
        }

        //isapi
        $isAPI = false;
        if( isset($url[1])){
            if($url[0] == 'Dashboards' && $url[1] == 'get_last_data'){
                $isAPI = true;
            }
        }

        //行動裝置判斷
        $isMobile = $this->isMobileCheck();
        if ($isMobile && !$isAPI ) {
            //如果是手機，登入後直接導向operation畫面
            // $url[0] = 'Dashboards';
            // $url[1] = 'operation';
        }

        // 將 $url[0] 視為 Controller 的名稱
        // 檢查 $url[0] 是否有對應的 Controller ，即是否存在 $url[0].php 的檔案
        if(!empty($url[0]))
            $this->currentController = $url[0];
        // 引入 Controller
        $file = dirname(dirname(__FILE__)).'/controllers/'.$this->currentController.'.php';
        //判斷url帶入的controller是否存在
        if (file_exists($file)) {
            require_once dirname(dirname(__FILE__)).'/controllers/'.$this->currentController.'.php';
        } else {
            $this->currentController = 'Dashboards';
            require_once dirname(dirname(__FILE__)).'/controllers/'.$this->currentController.'.php';
        }

        
        // 實例化 Controller
        $className = $this->currentController;

        $this->currentController = new $className();

        // $url[1] 視為 Controller 中的方法
        // 所以先要檢查是否有值，若有，檢查該值是否有對應的方法
        if(isset($url[1]))
            if(method_exists($this->currentController, $url[1]))
                $this->currentMethod = $url[1];

        // $url 陣列中的第三個值開始，視為帶入方法中的參數
        // 用 $params 陣列儲存所有剩下的值
            $temp_array = array();
            if(isset($url[2])){
                for ($i=2; $i < count($url); $i++) { 
                   array_push($temp_array,$url[$i]);
                }
            }else{
                $temp_array = array("");//給一組空值，避免因參數跳error
            }
            $this->params = $temp_array;

        // 最後透過呼叫 callback 來執行方法
        call_user_func_array([$this->currentController, $this->currentMethod], $this->params);
    }

    public function getUrl()
    {
        // 從 public?url= 後開始，將 $url 按 / 切分，轉換成陣列並回傳
        // 例如： 使用者輸入 127.0.0.1/public?url=posts/show/1
        // 則回傳 $url 的值為 ['posts', 'show', 1]
        // 它將在 __construct() 中依序被解析成 Controller, 方法, 參數
        $content ="null";
        if(isset($_GET['url'] )) {
            $content = explode('/',$_GET['url']);
            return $content;
        }

    }

    public function isMobileCheck($value='')
    {
        //Detect special conditions devices
        $iPod = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
        $iPhone = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
        $iPad = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
        if(stripos($_SERVER['HTTP_USER_AGENT'],"Android") && stripos($_SERVER['HTTP_USER_AGENT'],"mobile")){
            $Android = true;
        }else if(stripos($_SERVER['HTTP_USER_AGENT'],"Android")){
            $Android = false;
            $AndroidTablet = true;
        }else{
            $Android = false;
            $AndroidTablet = false;
        }
        $webOS = stripos($_SERVER['HTTP_USER_AGENT'],"webOS");
        $BlackBerry = stripos($_SERVER['HTTP_USER_AGENT'],"BlackBerry");
        $RimTablet= stripos($_SERVER['HTTP_USER_AGENT'],"RIM Tablet");
        //do something with this information
        if( $iPod || $iPhone || $iPad || $Android || $AndroidTablet || $webOS || $BlackBerry || $RimTablet){
            return true;
        }else{
            return false;
        }
    }

}
