<?php

class Dashboards extends Controller
{
    private $DashboardModel;
    private $AdminModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->DashboardModel = $this->model('Dashboard');
        $this->AdminModel = $this->model('Admin');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
    }

    // 取得所有Jobs
    public function index(){

        
        $isMobile = $this->isMobileCheck();
        $agent_type = $this->AdminModel->Get_Das_Config('agent_type');
        $device_info = $this->Device_Info();

        $data = [
            'isMobile' => $isMobile,
            'agent_type' => $agent_type,
            'device_info' => $device_info,
        ];
        
        $this->view('dashboards/index', $data);

    }

    // operation即時面板
    public function operation(){

        $isMobile = $this->isMobileCheck();
    
        $chart_mode = !empty($_GET['chart']) ? $_GET['chart'] : 1;
        if ($chart_mode < 1 || $chart_mode > 4) {
            $chart_mode = 1;
        } 
        $id = 4170;
        $unitvalue = 3;
        $chat_mode_arr = $chart_mode;


        #取得目前的曲線圖模式 制定曲線圖的座標名稱
        $chart_menu_arr = $this->MiscellaneousModel->details('chart_menu');
        $chart_mode_arr = $this->MiscellaneousModel->details('chart_mode');
        $echart_name = explode("/",$chart_mode_arr[$chart_mode]);

        
        $csvdata_arr = $this->DashboardModel->get_info($id,$chart_mode);
        if(!empty($csvdata_arr)){
            $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $unitvalue, $chat_mode_arr);       
        }
   
        $data = [
            'isMobile'    => $isMobile,
            'chart_info'  => $temp_chart,
            'echart_name' => $echart_name,
            'chart_mode'  => $chart_mode,
            'chart_menu_arr' => $chart_menu_arr
        ];

        if($isMobile){
            $this->view('dashboards/operation_m', $data);
        }else{
            $this->view('dashboards/operation', $data);
        }
       

    }

   

    /*public function get_last_data(){
        date_default_timezone_set("Asia/Taipei");

        try {        
            // session_start();
            $latestData = $this->DashboardModel->get_last_data();
            $updated = false;
            // 比較數據庫最新數據與上一次請求的數據，如果不一致則認為有更新
            if ($latestData !== $_SESSION['lastData'] || !isset($_SESSION['lastData'])) {
                $updated = true;
                $_SESSION['lastData'] = $latestData; // 更新上一次請求的數據

                // 產生csv檔
                // $data = ["John Doe", 30, "john@example.com"]; // 你要写入的数据
                // $csvFilePath = $this->AdminModel->Get_Das_Config('csv_file_path');
                // $csvFileName = date("YmdH").'.csv';  

                // $this->writeToCSV($latestData['data'], $csvFilePath.'/'.$csvFileName); // 调用函数将数据写入 CSV 文件

            }
            // 返迴響應
            $response = array(
                'updated' => $updated,
                'data' => $latestData
            );
            header('Content-Type: application/json');
            echo json_encode($response);
        } catch (PDOException $e) {
          // 處理數據庫連接錯誤
          die('數據庫連接錯誤: ' . $e->getMessage());
        }

    }*/

    //return datalog csv for graph
    /*public function get_datalog($sn = null)
    {
        if( isset($_GET['sn'])  ){
            $sn = (int)$_GET['sn'];
        }
        $sn_id = $sn;
        //將數字由左邊補零至三位數
        $sn_id = str_pad($sn_id,10,'0',STR_PAD_LEFT);

        //get device_datalog_frequency
        $freq = $this->DashboardModel->get_device_datalog_frequency();
        if($freq == 0){
            $freq = '0p5';
        }else if($freq == 1){
            $freq = '1p0';
        }else if($freq == 2){
            $freq = '2p0';
        }else{
            $freq = '0p5';
        }

        if( PHP_OS_FAMILY == 'Linux'){
            $logfile = "/mnt/ramdisk/DATALOG_".$sn_id."_".$freq.".csv";
        }else{
            $logfile = "../data.csv";
        }

        if( file_exists($logfile)){
            $f = fopen($logfile, 'r');
            $csv_line = stream_get_contents($f);
            echo rtrim($csv_line);
        }else{
            return '';
        }
       
    }*/


    public function change_language()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        if( !empty($_POST['language']) && isset($_POST['language'])  ){
            $language = $_POST['language'];
        }else{ 
            $input_check = false; 
            $error_message .= "language,";
        }
        $_SESSION['language'] = $language;

        $response = array(
            'language' => $language,
            'result' => true,
        );
        echo json_encode($response);
    

    }



    private function ChartData($chat_mode, $csvdata_arr, $unitvalue, $chat_mode_arr){
        $chart_info = array();
   
           
        if(($chat_mode == "1" || $chat_mode == "3" || $chat_mode == "4") && $unitvalue != "1"){
            
            $TransType = $unitvalue;
            $torValues = $csvdata_arr;
            $temp_val = $this->MiscellaneousModel->unitarr_change($torValues, 1, $TransType);
            $chart_info['y_val'] = json_encode($temp_val);
            $chart_info['max'] = max($temp_val);
            $chart_info['min'] = min($temp_val);

        }else{
            
            $chart_info['y_val'] = json_encode($csvdata_arr);
            $chart_info['max'] = max($csvdata_arr);
            $chart_info['min'] = min($csvdata_arr);
            
        }
        $chart_info['x_val'] = json_encode(array_keys($csvdata_arr));
        
        return $chart_info;
    }

    
}
?>