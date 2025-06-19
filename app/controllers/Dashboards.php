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


    public function operation() {

        $file = $this->MiscellaneousModel->lang_load();
        if (!empty($file)) include $file;

        $isMobile   = $this->isMobileCheck();
        $data_info  = $this->DashboardModel->get_Data() ?? [];

        if (!empty($data_info['error_message'])) {
            $data_info['error_message'] = $error_message['ERR_' . $data_info['error_message']] ?? $data_info['error_message'];
        }

        $status_arr       = $this->MiscellaneousModel->details('status');
        $chart_mode       = isset($_GET['chart']) && $_GET['chart'] >= 1 && $_GET['chart'] <= 6 ? (int)$_GET['chart'] : 1;
        $chat_mode_arr    = $chart_mode;
        $x_val            = $this->DashboardModel->get_csv_first_column();
        if (!empty($x_val)) $x_val = array_slice($x_val, 1);

        $chart_menu_arr   = $this->MiscellaneousModel->details('chart_menu');
        $chart_mode_arr   = $this->MiscellaneousModel->details('chart_mode');
        $echart_name      = explode("/", $chart_mode_arr[$chart_mode]);

        $temp_chart       = [];
        $csvdata_arr      = $this->DashboardModel->get_info($chart_mode);

        // ➤ Torque 統一範圍：用 mode 5 的 torque
        $unified_min_torque = 0;
        $unified_max_torque = 100;

        $torque_range_mode5 = $this->DashboardModel->get_info(5);
        if (!empty($torque_range_mode5['torque'])) {
            $torque_vals = $torque_range_mode5['torque'];
            array_shift($torque_vals);
            if (!empty($torque_vals)) {
                $unified_min_torque = min($torque_vals);
                $unified_max_torque = max($torque_vals);
            }
        }

        // ➤ RPM 統一範圍：用 mode 3 的 rpm
        $unified_min_rpm = null;
        $unified_max_rpm = null;

        $rpm_range_mode3 = $this->DashboardModel->get_info(3);
        if (!empty($rpm_range_mode3['rpm'])) {
            $rpm_vals = $rpm_range_mode3['rpm'];
            array_shift($rpm_vals);
            if (!empty($rpm_vals)) {
                $unified_min_rpm = min($rpm_vals);
                $unified_max_rpm = max($rpm_vals);
            }
        }

        if (!empty($csvdata_arr)) {
            if ($chart_mode !== 5) {
                $csvdata_arr = array_slice($csvdata_arr, 1); // 去標頭
                $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val);
            } elseif (isset($csvdata_arr['torque'], $csvdata_arr['rpm']) &&
                    is_array($csvdata_arr['torque']) && is_array($csvdata_arr['rpm'])) {
                array_shift($csvdata_arr['torque']);
                array_shift($csvdata_arr['rpm']);
                $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val);
            }

            // ➤ 套用 torque 範圍
            if (in_array($chart_mode, [1, 4, 5])) {
                $temp_chart['min_torque'] = $unified_min_torque;
                $temp_chart['max_torque'] = $unified_max_torque;
            }

            // ➤ chart_mode == 5 要額外套用 rpm 統一範圍
            /*if ($chart_mode == 5 && $unified_max_rpm > $unified_min_rpm) {
                $temp_chart['min_rpm'] = $unified_min_rpm;
                $temp_chart['max_rpm'] = $unified_max_rpm;
            }*/

            if (!isset($temp_chart['min_torque'])) $temp_chart['min_torque'] = 0;
            if (!isset($temp_chart['max_torque'])) $temp_chart['max_torque'] = 100;
        }

        $data = [
            'isMobile'       => $isMobile,
            'chart_info'     => $temp_chart,
            'echart_name'    => $echart_name,
            'chart_mode'     => $chart_mode,
            'chart_menu_arr' => $chart_menu_arr,
            'data_info'      => $data_info,
            'status_arr'     => $status_arr,
            'text'           => $text ?? []
        ];

        if (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            isset($_GET['ajax'])
        ) {
            header('Content-Type: application/json');
            echo json_encode($data);
            return;
        }

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
        $chat_mode = (int)$chat_mode;

        // 預設值
        $torque = [];
        $rpm = [];

        if ($chat_mode === 5) {
            // ➤ 雙軸：Torque + RPM
            $torque = isset($csvdata_arr['torque']) ? $csvdata_arr['torque'] : [];
            $rpm    = isset($csvdata_arr['rpm']) ? $csvdata_arr['rpm'] : [];

            $chart_info['y_val_torque'] = $torque;
            $chart_info['max_torque']   = !empty($torque) ? max($torque) : 0;
            $chart_info['min_torque']   = !empty($torque) ? min($torque) : 0;

            $chart_info['y_val_rpm']    = $rpm;
            $chart_info['max_rpm']      = !empty($rpm) ? max($rpm) : 0;
            $chart_info['min_rpm']      = !empty($rpm) ? min($rpm) : 0;

            // 主軸 y_val 統一為 torque
            $chart_info['y_val'] = $torque;
            $chart_info['max']   = !empty($torque) ? max($torque) : 0;
            $chart_info['min']   = !empty($torque) ? min($torque) : 0;

        } else if (in_array($chat_mode, [1, 2, 3, 4])) {
            // ➤ 單軸：Torque / Angle / RPM
            $chart_info['y_val'] = $csvdata_arr;
            $chart_info['max']   = !empty($csvdata_arr) ? max($csvdata_arr) : 0;
            $chart_info['min']   = !empty($csvdata_arr) ? min($csvdata_arr) : 0;

            if (in_array($chat_mode, [1, 4])) {
                // ➤ mode 1 和 4 額外補 torque 用於座標統一
                $torque = $csvdata_arr;
                $chart_info['y_val_torque'] = $torque;
                $chart_info['max_torque']   = !empty($torque) ? max($torque) : 0;
                $chart_info['min_torque']   = !empty($torque) ? min($torque) : 0;
            }
        } else {
            // ➤ fallback，空圖
            $chart_info['y_val'] = [];
            $chart_info['max']   = 0;
            $chart_info['min']   = 0;
        }

        // ➤ x 軸整數格式化
        $chart_info['x_val'] = array_map(function($value) {
            return ($value == (int)$value) ? (int)$value : $value;
        }, $x_val);

        return $chart_info;
    }


 
}
?>