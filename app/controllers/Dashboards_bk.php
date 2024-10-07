<?php

class Dashboards extends Controller
{

    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->DashboardModel = $this->model('Dashboard');
        $this->AdminModel = $this->model('Admin');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
    }

    // 取得所有Jobs
    public function index(){

        
       
    }

    // operation即時面板
    /*public function operation(){

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
       

    }*/

   

   






   

    
}
?>