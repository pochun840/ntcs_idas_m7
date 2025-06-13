<?php

class Dashboards extends Controller
{
    private $DashboardModel;
    private $AdminModel;
    private $MiscellaneousModel;
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

        if($isMobile){
            $this->view('dashboards/index_m', $data);
        }else{
            $this->view('dashboards/index', $data);
        }


    }

    // operation即時面板
    public function operation(){

        $isMobile = $this->isMobileCheck();

        // 最新一筆鎖附資料
        $data_info = $this->DashboardModel->get_Data();
        if (empty($data_info)) {
            $data_info = '';
        }

        $status_arr = $this->MiscellaneousModel->details('status');

        // 取得圖表模式（限 1~6）
        $chart_mode = !empty($_GET['chart']) ? $_GET['chart'] : 1;
        if ($chart_mode < 1 || $chart_mode > 6) {
            $chart_mode = 1;
        }

        $id = "None"; // CSV 對應 ID（目前用途可能保留）
        $chat_mode_arr = $chart_mode;

        // 取得 x 軸資料（通常為時間或序列）
        $x_val = $this->DashboardModel->get_csv_first_column($id);
        if (!empty($x_val)) {
            $x_val = array_slice($x_val, 1); // 移除 header
        }

        // 取得圖表對應名稱（如 Torque/Angle）
        $chart_menu_arr  = $this->MiscellaneousModel->details('chart_menu');
        $chart_mode_arr  = $this->MiscellaneousModel->details('chart_mode');
        $echart_name     = explode("/", $chart_mode_arr[$chart_mode]);

        // 預設空圖表（防止未定義錯誤）
        $temp_chart = [];

        // 抓取對應的資料內容（依 chart_mode 不同格式可能不同）
        $csvdata_arr = $this->DashboardModel->get_info($chart_mode);

        if (!empty($csvdata_arr)) {
            if ($chart_mode != 5) {
                $csvdata_arr = array_slice($csvdata_arr, 1);
                $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val);
            } else {
                // ✅ 防呆：chart_mode == 5 要有 torque 和 rpm 且為陣列
                if (isset($csvdata_arr['torque'], $csvdata_arr['rpm']) &&
                    is_array($csvdata_arr['torque']) &&
                    is_array($csvdata_arr['rpm'])) {

                    array_shift($csvdata_arr['torque']);
                    array_shift($csvdata_arr['rpm']);

                    $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val);
                }
            }
        }

        // 組成畫面資料
        $data = [
            'isMobile'       => $isMobile,
            'chart_info'     => $temp_chart,
            'echart_name'    => $echart_name,
            'chart_mode'     => $chart_mode,
            'chart_menu_arr' => $chart_menu_arr,
            'data_info'      => $data_info,
            'status_arr'     => $status_arr
        ];

        // 依裝置選擇對應畫面
        if ($isMobile) {
            $this->view('dashboards/operation_m', $data);
        } else {
            $this->view('dashboards/operation', $data);
        }
    }



    public function change_language(){

        $error_message = '';
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



    private function ChartData($chat_mode, $csvdata_arr, $chat_mode_arr, $x_val) {
        $chart_info = [];

        $chat_mode = (int)$chat_mode; // 確保比較型別一致

        if (in_array($chat_mode, [1, 3, 4])) {
            // 單一折線圖（Torque、Angle 或 RPM）
            if (!empty($csvdata_arr) && is_array($csvdata_arr)) {
                $chart_info['y_val'] = json_encode($csvdata_arr);
                $chart_info['max'] = max($csvdata_arr);
                $chart_info['min'] = min($csvdata_arr);
            } else {
                $chart_info['y_val'] = "[]";
                $chart_info['max'] = 0;
                $chart_info['min'] = 0;
            }

        } else if ($chat_mode === 5) {
            // 雙 Y 軸：Torque + RPM
            if (
                isset($csvdata_arr['torque'], $csvdata_arr['rpm']) &&
                is_array($csvdata_arr['torque']) &&
                is_array($csvdata_arr['rpm']) &&
                !empty($csvdata_arr['torque']) &&
                !empty($csvdata_arr['rpm'])
            ) {
                $chart_info['y_val_torque'] = json_encode($csvdata_arr['torque']);
                $chart_info['y_val_rpm']    = json_encode($csvdata_arr['rpm']);

                $chart_info['max_torque'] = max($csvdata_arr['torque']);
                $chart_info['min_torque'] = min($csvdata_arr['torque']);
                $chart_info['max_rpm']    = max($csvdata_arr['rpm']);
                $chart_info['min_rpm']    = min($csvdata_arr['rpm']);
            } else {
                $chart_info['y_val_torque'] = "[]";
                $chart_info['y_val_rpm']    = "[]";
                $chart_info['max_torque']  = 0;
                $chart_info['min_torque']  = 0;
                $chart_info['max_rpm']     = 0;
                $chart_info['min_rpm']     = 0;
            }

            $chart_info['y_val'] = "[]"; // 統一補 y_val 為空（ECharts 使用可能會用到）

        } else {
            // 其他情況：一般陣列資料
            if (!empty($csvdata_arr) && is_array($csvdata_arr)) {
                $chart_info['y_val'] = json_encode($csvdata_arr);
                $chart_info['max'] = max($csvdata_arr);
                $chart_info['min'] = min($csvdata_arr);
            } else {
                $chart_info['y_val'] = "[]";
                $chart_info['max'] = 0;
                $chart_info['min'] = 0;
            }
        }

        // 處理 X 軸數值顯示：去掉 .0
        $x_val = array_map(function($value) {
            return ($value == (int)$value) ? (int)$value : $value;
        }, $x_val);

        $chart_info['x_val'] = json_encode($x_val);

        return $chart_info;
    }


    
}
?>