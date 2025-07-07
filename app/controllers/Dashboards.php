<?php

class Dashboards extends Controller
{
    private $DashboardModel;
    private $AdminModel;
    private $MiscellaneousModel;
    private $DataModel;
    private $SettingModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->DashboardModel = $this->model('Dashboard');
        $this->AdminModel = $this->model('Admin');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
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
        $controller_info = $this->SettingModel->GetControllerInfo();
        $status_arr       = $this->MiscellaneousModel->details('status');
        $unit_arr   = $this->MiscellaneousModel->details('torque_unit');


        //


        //取得控制器的扭力單位 
        $res_device = $this->SettingModel->GetControllerInfo();
        $device_torque_unit = (int)$res_device['torque_unit'];




        if (!empty($data_info['fasten_status'])) {
        
            $data_info['error_message'] = $error_message['ERR_' . $data_info['error_message']] ?? $data_info['error_message'];

            $fastenStatus = (string)$data_info['fasten_status'];

            // 預設為綠色（成功狀態）
            $color = 'green';

            if (in_array($fastenStatus, ['5', '6'])) {
                $controller_info = $this->SettingModel->GetControllerInfo();
                $color = ($controller_info['okseqcolor'] ?? 0) == 1 ? 'green' : 'yellow';
            } elseif (in_array($fastenStatus, ['7', '8'])) {
                $color = 'red';
            }


            $data_info['fasten_status_text'] = $status_arr[$fastenStatus];
            $data_info['result_status_color_text'] = $color;


            $data_info['final_fasten_torque_temp'] =  $this->MiscellaneousModel->convert_all_torque_units($data_info['final_fasten_torque'], 1);
            $data_info['final_fasten_torque'] = $data_info['final_fasten_torque_temp'][$unit_arr[$device_torque_unit]]; 
            $data_info['final_torque_unit'] = $unit_arr[$device_torque_unit];
    
        }


        //取得控制器的扭力單位 
        $res_device = $this->SettingModel->GetControllerInfo();
        $device_torque_unit = (int)$res_device['torque_unit'];


        // 最新鎖附資料取得 ID
        $id = null;
        $first_data = $this->get_current_data();
        if (!empty($first_data)) {
            $id = $first_data['id'];
        }

        $chart_mode       = isset($_GET['chart']) && $_GET['chart'] >= 1 && $_GET['chart'] <= 6 ? (int)$_GET['chart'] : 1;
        $chat_mode_arr    = $chart_mode;
        $x_val            = $this->DashboardModel->get_csv_first_column($id);
        if (!empty($x_val)) $x_val = array_slice($x_val, 1);

        $chart_menu_arr   = $this->MiscellaneousModel->details('chart_menu');
        $chart_mode_arr   = $this->MiscellaneousModel->details('chart_mode');
        $echart_name      = explode("/", $chart_mode_arr[$chart_mode]);

        $temp_chart       = null;
        $csvdata_arr      = $this->DashboardModel->get_info($chart_mode, $id);

        // Torque 統一範圍：用 mode 5 的 torque
        $unified_min_torque = 0;
        $unified_max_torque = 100;
        $torque_range_mode5 = $this->DashboardModel->get_info(5, $id);
        if (!empty($torque_range_mode5['torque'])) {
            $torque_vals = $torque_range_mode5['torque'];
            array_shift($torque_vals);
            if (!empty($torque_vals)) {
                $unified_min_torque = min($torque_vals);
                $unified_max_torque = max($torque_vals);
            }
        }

        // RPM 統一範圍：用 mode 3 的 rpm
        $unified_min_rpm = null;
        $unified_max_rpm = null;
        $rpm_range_mode3 = $this->DashboardModel->get_info(3, $id);
        if (!empty($rpm_range_mode3['rpm'])) {
            $rpm_vals = $rpm_range_mode3['rpm'];
            array_shift($rpm_vals);
            if (!empty($rpm_vals)) {
                $unified_min_rpm = min($rpm_vals);
                $unified_max_rpm = max($rpm_vals);
            }
        }

        // ✅ 當資料不為空時才處理圖表
        if (!empty($csvdata_arr)) {
            if ($chart_mode !== 5) {
                $csvdata_arr = array_slice($csvdata_arr, 1);
                $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val);
            } elseif (
                isset($csvdata_arr['torque'], $csvdata_arr['rpm']) &&
                is_array($csvdata_arr['torque']) && is_array($csvdata_arr['rpm']) &&
                !empty($csvdata_arr['torque']) && !empty($csvdata_arr['rpm'])
            ) {
                array_shift($csvdata_arr['torque']);
                array_shift($csvdata_arr['rpm']);
                $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val);
            }

            // 如果成功取得圖表資料才套用 min/max
            if (!empty($temp_chart)) {
                if (in_array($chart_mode, [1, 4, 5])) {
                    $temp_chart['min_torque'] = $unified_min_torque;
                    $temp_chart['max_torque'] = $unified_max_torque;
                }

                if ($chart_mode == 5 && $unified_max_rpm > $unified_min_rpm) {
                    $temp_chart['min_rpm'] = $unified_min_rpm;
                    $temp_chart['max_rpm'] = $unified_max_rpm;
                }

                if (!isset($temp_chart['min_torque'])) $temp_chart['min_torque'] = 0;
                if (!isset($temp_chart['max_torque'])) $temp_chart['max_torque'] = 100;
            }
        }

        // 打包回前端
        $data = [
            'isMobile'       => $isMobile,
            'chart_info'     => $temp_chart, // 若為 null，前端會顯示「無可用資料」
            'echart_name'    => $echart_name,
            'chart_mode'     => $chart_mode,
            'chart_menu_arr' => $chart_menu_arr,
            'data_info'      => $data_info,
            'status_arr'     => $status_arr,
            'text'           => $text ?? []
        ];


        // 如果是 AJAX 請求，回傳 JSON
        if (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
            isset($_GET['ajax'])
        ) {
            header('Content-Type: application/json');
            echo json_encode($data);
            return;
        }

        // 否則載入網頁
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

        // 取得控制器的扭力單位
        $res_device = $this->SettingModel->GetControllerInfo();
        $device_torque_unit = (int)$res_device['torque_unit'];
        $unit_name = $this->MiscellaneousModel->get_unit_name_by_index($device_torque_unit);

        $torque = [];
        $rpm = [];

        if ($chat_mode === 5) {
            // ➤ 雙軸：Torque + RPM
            $torque = $csvdata_arr['torque'] ?? [];
            $rpm    = $csvdata_arr['rpm'] ?? [];

            // ➤ 轉換 torque 單位
            $torque_converted = array_map(function($val) use ($device_torque_unit, $unit_name) {
                $converted = $this->MiscellaneousModel->convert_single_torque_unit($val, 1, $device_torque_unit);
                return is_array($converted) ? ($converted[$unit_name] ?? 0) : (is_numeric($converted) ? $converted : 0);
            }, $torque);

            $chart_info['y_val_torque'] = $torque_converted;
            $chart_info['max_torque']   = !empty($torque_converted) ? max($torque_converted) : 0;
            $chart_info['min_torque']   = !empty($torque_converted) ? min($torque_converted) : 0;

            $chart_info['y_val_rpm']    = $rpm;
            $chart_info['max_rpm']      = !empty($rpm) ? max($rpm) : 0;
            $chart_info['min_rpm']      = !empty($rpm) ? min($rpm) : 0;

            // ➤ 主軸用 torque
            $chart_info['y_val'] = $torque_converted;
            $chart_info['max']   = !empty($torque_converted) ? max($torque_converted) : 0;
            $chart_info['min']   = !empty($torque_converted) ? min($torque_converted) : 0;

        } else if (in_array($chat_mode, [1, 2, 3, 4])) {
            // ➤ 單軸圖

            if (in_array($chat_mode, [1, 4])) {
                // ➤ Torque 需要轉換
                $torque_converted = array_map(function($val) use ($device_torque_unit, $unit_name) {
                    $converted = $this->MiscellaneousModel->convert_single_torque_unit($val, 1, $device_torque_unit);
                    return is_array($converted) ? ($converted[$unit_name] ?? 0) : (is_numeric($converted) ? $converted : 0);
                }, $csvdata_arr);

                $chart_info['y_val'] = $torque_converted;
                $chart_info['max']   = !empty($torque_converted) ? max($torque_converted) : 0;
                $chart_info['min']   = !empty($torque_converted) ? min($torque_converted) : 0;

                $chart_info['y_val_torque'] = $torque_converted;
                $chart_info['max_torque']   = !empty($torque_converted) ? max($torque_converted) : 0;
                $chart_info['min_torque']   = !empty($torque_converted) ? min($torque_converted) : 0;
            } else {
                // ➤ 非 torque 類，不需轉換
                $chart_info['y_val'] = $csvdata_arr;
                $chart_info['max']   = !empty($csvdata_arr) ? max($csvdata_arr) : 0;
                $chart_info['min']   = !empty($csvdata_arr) ? min($csvdata_arr) : 0;
            }
        } else {
            $chart_info['y_val'] = [];
            $chart_info['max']   = 0;
            $chart_info['min']   = 0;
        }

        $chart_info['x_val'] = array_map(function($value) {
            return ($value == (int)$value) ? (int)$value : $value;
        }, $x_val);

        return $chart_info;
    }



    
    public function get_current_data(){

        $status_arr = $this->MiscellaneousModel->details('status');
        $unit_arr   = $this->MiscellaneousModel->details('torque_unit');

        $current_data = $this->DataModel->get_operation_info(); 

        return $current_data;
    
    } 
}
?>