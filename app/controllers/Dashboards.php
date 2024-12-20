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
        if ($chart_mode < 1 || $chart_mode > 6) {
            $chart_mode = 1;
        } 
        $id = "None";
        $chat_mode_arr = $chart_mode;


        $x_val = $this->DashboardModel->get_csv_first_column($id);
        if(!empty($x_val)){
            $x_val = array_slice($x_val, 1);
        }
     

        #取得目前的曲線圖模式 制定曲線圖的座標名稱
        $chart_menu_arr = $this->MiscellaneousModel->details('chart_menu');
        $chart_mode_arr = $this->MiscellaneousModel->details('chart_mode');
        $echart_name = explode("/",$chart_mode_arr[$chart_mode]);

        $csvdata_arr = $this->DashboardModel->get_info($id,$chart_mode);
    
        if(!empty($csvdata_arr)){
            if($chart_mode != 5){
                $csvdata_arr = array_slice($csvdata_arr, 1);
            }else{
                array_shift($csvdata_arr['torque']);
                array_shift($csvdata_arr['rpm']);
            }


           
            $temp_chart = $this->ChartData($chart_mode, $csvdata_arr,$chat_mode_arr,$x_val);       
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



    private function ChartData($chat_mode, $csvdata_arr, $chat_mode_arr,$x_val){
        $chart_info = array();
   
           
        if(($chat_mode == "1" || $chat_mode == "3" || $chat_mode == "4")){
            


            $chart_info['y_val'] = json_encode($csvdata_arr);

            $temp_val = json_decode($chart_info['y_val']); 

            $chart_info['max'] = max($temp_val);
            $chart_info['min'] = min($temp_val);

        }else if($chat_mode == "5"){
            
            #$chat_mode==5
            $chart_info['y_val_torque'] = json_encode($csvdata_arr['torque']);
            $chart_info['y_val_rpm'] = json_encode($csvdata_arr['rpm']);
            $chart_info['max_torque'] = max($csvdata_arr['torque']);
            $chart_info['min_torque'] = min($csvdata_arr['torque']);

            $chart_info['max_rpm'] = max($csvdata_arr['rpm']);
            $chart_info['min_rpm'] = min($csvdata_arr['rpm']);
            $chart_info['y_val'] = "[]";
            
        }else{

            $chart_info['y_val'] = json_encode($csvdata_arr);
            $chart_info['max'] = max($csvdata_arr);
            $chart_info['min'] = min($csvdata_arr);
        }


        // 去除 .0 的部分
        $x_val = array_map(function($value) {
            return ($value == (int)$value) ? (int)$value : $value;
        }, $x_val);

        $chart_info['x_val'] = json_encode($x_val);



        
        return $chart_info;
    }

    
}
?>