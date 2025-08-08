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

        $this->ntcs_data_db_sysnc();
        
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

        $isMobile         = $this->isMobileCheck();
        $data_info        = $this->DashboardModel->get_Data() ?? [];
        $controller_info  = $this->SettingModel->GetControllerInfo();
        $status_arr       = $this->MiscellaneousModel->details('status');
        $unit_arr         = $this->MiscellaneousModel->details('torque_unit');
        $decimals_arr     = $this->MiscellaneousModel->details("decimals");
        $res_device       = $this->SettingModel->GetControllerInfo();
        $device_torque_unit = (int)$res_device['torque_unit'];
        $chart_unit_name  = $unit_arr[$device_torque_unit] ?? 'N.m';

        if (!empty($data_info['fasten_status'])) {
            $data_info['error_message'] = $error_message['ERR_' . $data_info['error_message']] ?? $data_info['error_message'];
            $fastenStatus = (string)$data_info['fasten_status'];
            $color = 'green';

            if (in_array($fastenStatus, ['5'])) {
                $controller_info = $this->SettingModel->GetControllerInfo();
                $color = ($controller_info['okseqcolor'] ?? 0) == 0 ? 'green' : 'yellow';
            } else if(in_array($fastenStatus, ['6'])){
                $controller_info = $this->SettingModel->GetControllerInfo();
                $color = ($controller_info['okjobcolor'] ?? 0) == 0 ? 'green' : 'yellow';
            }elseif (in_array($fastenStatus, ['7', '8'])) {
                $color = 'red';
            }


            $data_info['fasten_status_text'] = $status_arr[$fastenStatus];
            $data_info['result_status_color_text'] = $color;
            $data_info['torque_unit'] = (int)$data_info['torque_unit'];

            if ($device_torque_unit != $data_info['torque_unit']) {
                $data_info['final_fasten_torque_temp'] = $this->MiscellaneousModel
                    ->convert_all_torque_units($data_info['final_fasten_torque'], $data_info['torque_unit'], $device_torque_unit);
                $data_info['final_fasten_torque_temp'] = $data_info['final_fasten_torque_temp'][$unit_arr[$device_torque_unit]];
            } else {
                $data_info['final_fasten_torque_temp'] = $data_info['final_fasten_torque'];
            }

            $data_info['final_fasten_torque'] = $data_info['final_fasten_torque_temp'];
            $data_info['final_torque_unit']   = $chart_unit_name;
        }

        $decimal_places = $decimals_arr[$device_torque_unit] ?? 3;
        $data_info['final_fasten_torque'] = number_format($data_info['final_fasten_torque'], $decimal_places);

        $id             = null;
        $first_data     = $this->get_current_data();
        if (!empty($first_data)) $id = $first_data['id'];

        $chart_mode     = isset($_GET['chart']) && $_GET['chart'] >= 1 && $_GET['chart'] <= 6 ? (int)$_GET['chart'] : 1;
        $chat_mode_arr  = $chart_mode;

        $x_val          = $this->DashboardModel->get_csv_first_column($id);
        if (!empty($x_val)) $x_val = array_slice($x_val, 1);

        $angle_as_x     = $this->DashboardModel->get_info(2, $id); // 取角度做 X 軸
        if (!empty($angle_as_x)) {
            $angle_as_x = array_slice($angle_as_x, 1);
        }

        $chart_menu_arr = $this->MiscellaneousModel->details('chart_menu');
        $chart_mode_arr = $this->MiscellaneousModel->details('chart_mode');
        $echart_name    = explode("/", $chart_mode_arr[$chart_mode]);
        $step_only      = $this->DashboardModel->get_step_only($id);
        $csvdata_arr    = $this->DashboardModel->get_info($chart_mode, $id);

        // Torque 統一範圍
        $torque_range_mode5 = $this->DashboardModel->get_info(5, $id);
        $unified_min_torque = 0;
        $unified_max_torque = 100;
        if (!empty($torque_range_mode5['torque'])) {
            $torque_vals = array_slice($torque_range_mode5['torque'], 1);
            if (!empty($torque_vals)) {
                $torque_vals_converted = array_map(function($val) use ($device_torque_unit) {
                    return (float)$this->MiscellaneousModel->convert_single_torque_unit($val, 1, $device_torque_unit);
                }, $torque_vals);
                $unified_min_torque = min($torque_vals_converted);
                $unified_max_torque = max($torque_vals_converted);
            }
        }

        // RPM 範圍
        $unified_min_rpm = null;
        $unified_max_rpm = null;
        $rpm_range_mode3 = $this->DashboardModel->get_info(3, $id);
        if (!empty($rpm_range_mode3['rpm'])) {
            $rpm_vals = array_slice($rpm_range_mode3['rpm'], 1);
            if (!empty($rpm_vals)) {
                $unified_min_rpm = min($rpm_vals);
                $unified_max_rpm = max($rpm_vals);
            }
        }

        $temp_chart = null;
        if (!empty($csvdata_arr)) {
            if ($chart_mode !== 5) {
                $csvdata_arr = array_slice($csvdata_arr, 1);
                $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val, $angle_as_x);
            } elseif (
                isset($csvdata_arr['torque'], $csvdata_arr['rpm']) &&
                is_array($csvdata_arr['torque']) && is_array($csvdata_arr['rpm']) &&
                !empty($csvdata_arr['torque']) && !empty($csvdata_arr['rpm'])
            ) {
                array_shift($csvdata_arr['torque']);
                array_shift($csvdata_arr['rpm']);
                $temp_chart = $this->ChartData($chart_mode, $csvdata_arr, $chat_mode_arr, $x_val);
            }

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

                $temp_chart['chart_unit_name'] = $chart_unit_name;
            }

            $temp_chart['steps'] = $step_only;
        }

        $data = [
            'isMobile'       => $isMobile,
            'chart_info'     => $temp_chart,
            'echart_name'    => $echart_name,
            'chart_mode'     => $chart_mode,
            'chart_menu_arr' => $chart_menu_arr,
            'data_info'      => $data_info,
            'status_arr'     => $status_arr,
            'text'           => $text ?? [],
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


    private function ChartData($chat_mode, $csvdata_arr, $chat_mode_arr, $x_val, $angle_as_x = []) {
        $chart_info = [];
        $chat_mode = (int)$chat_mode;

        $res_device = $this->SettingModel->GetControllerInfo();
        $device_torque_unit = (int)$res_device['torque_unit'];
        $unit_arr = $this->MiscellaneousModel->details('torque_unit');
        $unit_name = $unit_arr[$device_torque_unit] ?? 'N.m';
        $decimals_arr = $this->MiscellaneousModel->details('decimals');
        $precision = $decimals_arr[$device_torque_unit] ?? 3;

        $convertTorque = function($val) use ($device_torque_unit, $precision) {
            $converted = $this->MiscellaneousModel->convert_single_torque_unit($val, 1, $device_torque_unit);
            return round((float)$converted, $precision);
        };

        if ($chat_mode === 5) {
            $torque = $csvdata_arr['torque'] ?? [];
            $rpm = $csvdata_arr['rpm'] ?? [];

            $torque_converted = array_map($convertTorque, $torque);
            $chart_info['y_val_torque'] = $torque_converted;
            $chart_info['y_val_rpm'] = array_map('floatval', $rpm);

            $chart_info['max_torque'] = !empty($torque_converted) ? max($torque_converted) : 0;
            $chart_info['min_torque'] = !empty($torque_converted) ? min($torque_converted) : 0;
            $chart_info['max_rpm'] = !empty($rpm) ? max($rpm) : 0;
            $chart_info['min_rpm'] = !empty($rpm) ? min($rpm) : 0;

            $chart_info['y_val'] = $torque_converted;
            $chart_info['max'] = $chart_info['max_torque'];
            $chart_info['min'] = $chart_info['min_torque'];

        } elseif (in_array($chat_mode, [1, 2, 3, 4])) {
            if (in_array($chat_mode, [1, 4])) {
                $torque_converted = array_map($convertTorque, $csvdata_arr);
                $chart_info['y_val'] = $torque_converted;
                $chart_info['max'] = !empty($torque_converted) ? max($torque_converted) : 0;
                $chart_info['min'] = !empty($torque_converted) ? min($torque_converted) : 0;

                $chart_info['y_val_torque'] = $torque_converted;
                $chart_info['max_torque'] = $chart_info['max'];
                $chart_info['min_torque'] = $chart_info['min'];
            } else {
                $chart_info['y_val'] = array_map('floatval', $csvdata_arr);
                $chart_info['max'] = !empty($chart_info['y_val']) ? max($chart_info['y_val']) : 0;
                $chart_info['min'] = !empty($chart_info['y_val']) ? min($chart_info['y_val']) : 0;
            }
        } else {
            $chart_info['y_val'] = [];
            $chart_info['max'] = 0;
            $chart_info['min'] = 0;
        }

        $chart_info['unit_name'] = $unit_name;

        // X 軸使用邏輯
        $chart_info['x_val'] = array_map(function($v) {
            return ($v == (int)$v) ? (int)$v : (float)$v;
        }, ($chat_mode === 4 && !empty($angle_as_x)) ? $angle_as_x : $x_val);

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