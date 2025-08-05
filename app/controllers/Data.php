<?php

class Data extends Controller
{
    private $DataModel;
    private $MiscellaneousModel;
    private $ToolModel;
    private $SettingModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->DataModel = $this->model('Datas');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
        $this->ToolModel = $this->model('Tool');
        $this->SettingModel = $this->model('Setting');
    }

    // 取得所有Jobs
     public function index(){

        
        
        $type = 'ALL';
        $this->ntcs_data_db_sysnc();
        $isMobile = $this->isMobileCheck();
        $decimals_arr = $this->MiscellaneousModel->details("decimals");
        // 取得當前年份
        if (PHP_OS_FAMILY === 'Linux') {
            $db_path = "/var/www/html/database/data".date('Y').".db";

            // 檢查資料庫是否存在
            $db_exists = file_exists($db_path);
    
            if ($db_exists) {
                $res_data     = $this->DataModel->getData('ALL');
                $res_data_ok  = $this->DataModel->getData('OK');
                $res_data_nok = $this->DataModel->getData('NOK');
            } else {
                $res_data     = [];
                $res_data_ok  = [];
                $res_data_nok = [];
            }

        }else{
            $res_data     = $this->DataModel->getData('ALL');
            $res_data_ok  = $this->DataModel->getData('OK');
            $res_data_nok = $this->DataModel->getData('NOK');
            $db_exists = '';
            $db_path = '';
        }


        $unit_arr    = $this->MiscellaneousModel->details('torque_unit');
        $status_arr  = $this->MiscellaneousModel->details('status');
        $device_info = $this->Device_Info();
        $color_arr   = $this->get_color_type();

        foreach ($res_data as &$row) {
            $status = $row['fasten_status'];

            // 加入 NG 顏色或 OK 顏色分類
            if ($status == 5) {
                $row['row_color'] = $color_arr['okseqcolor_text'];
            }else if($status == 6) {
                $row['row_color'] = $color_arr['okseqcolor_text'];
            }else if ($status == 4){
                $row['row_color'] = 'status-ok';
            }else{
                $row['row_color'] = 'status-ng';
            } 

            $torque_value = $row['final_fasten_torque'] ?? 0;
            $torque_unit  = $row['torque_unit'] ?? 1; // 預設為 N.m
            $precision = $decimals_arr[$torque_unit] ?? 3; // 預設顯示三位小數
            $row['final_fasten_torque'] = number_format((float)$torque_value, $precision);

        }

        $data = array(
            'isMobile'      => $isMobile,
            'res_data'      => $res_data,
            'res_data_ok'   => $res_data_ok,
            'res_data_nok'  => $res_data_nok,
            'device_info'   => $device_info,
            'unit_arr'      => $unit_arr,
            'status_arr'    => $status_arr,
            'db_exists'     => $db_exists,
            'db_path'       => $db_path,
            'color_arr'     => $color_arr
        );

        $this->view('data/index', $data);
    }


    public function exportData() {
        
        $input_check = true;

        //取得控制器的編號 
        
        $controller_info = $this->SettingModel->GetControllerInfo();

        // 檢查開始日期
        if (!empty($_POST['start_date']) && isset($_POST['start_date'])) {
            $start_date = $_POST['start_date'] . ":00";
        } else {
            $input_check = false;
        }

        // 檢查結束日期
        if (!empty($_POST['end_date']) && isset($_POST['end_date'])) {
            $end_date = $_POST['end_date'] . ":00";
        } else {
            $input_check = false;
        }

        // 匯出格式
        $expert_val = $_POST['expert_val'] ?? "0";

        if ($input_check) {
            $dataset = $this->DataModel->get_range_data($start_date, $end_date);

            if (count($dataset) === 0) {
                echo json_encode(["error" => "無法找到符合條件的資料"]);
                exit();
            }

            $dataset = array_slice($dataset, 0, 10000);
            $csv_headers = array_keys($dataset[0]);

            date_default_timezone_set('Asia/Taipei'); 
            $timestamp = date("Y-m-d_Hi");

            $csv_filename = $controller_info['device_id']."_data_{$timestamp}.csv";
            $zip_filename = $controller_info['device_id']."_data_{$timestamp}.zip";

            if ($expert_val === "0") {
                // 匯出 CSV
                header('Content-Type: text/csv; charset=utf-8');
                header("Content-Disposition: attachment; filename={$csv_filename}");

                $output = fopen('php://output', 'w');
                fputcsv($output, $csv_headers);
                foreach ($dataset as $row) {
                    fputcsv($output, $row);
                }
                fclose($output);
                exit();

            } elseif ($expert_val === "1") {
                // 匯出 ZIP + CSV
                $csv_content = implode(',', $csv_headers) . "\n";
                foreach ($dataset as $row) {
                    $csv_content .= implode(',', $row) . "\n";
                }

                $zip = new ZipArchive();
                $temp_zip_path = tempnam(sys_get_temp_dir(), 'ntcs_zip');
                $temp_zip_final = $temp_zip_path . '.zip';

                if ($zip->open($temp_zip_final, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    $zip->addFromString($csv_filename, $csv_content); // zip 中的檔案名稱
                    $zip->close();

                    header('Content-Type: application/zip');
                    header("Content-Disposition: attachment; filename={$zip_filename}");
                    header('Content-Length: ' . filesize($temp_zip_final));
                    readfile($temp_zip_final);
                    unlink($temp_zip_final);
                    exit();
                } else {
                    echo json_encode(["error" => "無法建立 ZIP 檔案"]);
                    exit();
                }
            }

        } else {
            echo json_encode(["error" => "輸入參數不正確"]);
            exit();
        }
    }



    public function getreal_time_data() {

        $mode = $_POST['mode'] ?? 'ALL';

        // 根據系統設定路徑
        if(PHP_OS_FAMILY  ==="Linux"){
            $base_path = '/home/kls/NTCS7/';
        }else{
            $base_path =  '../';
        }
      
        $db_path = $base_path . "ntcs_data.db";


        if (!file_exists($db_path)) {
            echo json_encode(['success' => false, 'msg' => "資料庫不存在"]);
            return;
        }

        $res_data     = $this->DataModel->getData($mode);
        $unit_arr     = $this->MiscellaneousModel->details('torque_unit');
        $status_arr   = $this->MiscellaneousModel->details('status');
        $decimals_arr = $this->MiscellaneousModel->details("decimals");

        $color_arr  = $this->get_color_type();

        // 加入對應的顏色到每筆資料
        foreach ($res_data as &$row) {
            $status = $row['fasten_status'];

            //

            if($status == 5) {
                $row['row_color'] = $color_arr['okseqcolor_text'];
            }else if ($status == 6) {
                $row['row_color'] = $color_arr['okjobcolor_text'];
            }else if($status == 4){
                $row['row_color'] = 'status-ok';
            }else{
                $row['row_color'] = 'status-ng';
            }

            $torque_value = $row['final_fasten_torque'] ?? 0;
            $torque_unit  = $row['torque_unit'] ?? 1; // 預設為 N.m
            $precision = $decimals_arr[$torque_unit] ?? 3; // 預設顯示三位小數
            $row['final_fasten_torque'] = number_format((float)$torque_value, $precision);

   
        }

        echo json_encode([
            'success' => true,
            'records' => $res_data,
            'unit_arr' => $unit_arr,
            'status_arr' => $status_arr,
            'color_arr' => $color_arr
        ]);
    }



    public function get_color_type(){

        $Controller_Info = $this->ToolModel->GetControllerInfo();   

        if(!empty($Controller_Info)){
            $color_arr = array();
            $color_arr['okseqcolor'] = $Controller_Info['okseqcolor'];
            $color_arr['okjobcolor'] = $Controller_Info['okjobcolor'];

            if(!empty($color_arr)){
                if( $color_arr['okseqcolor']  == 1){
                    $color_arr['okseqcolor_text'] = 'status-ok';
                }else{
                    $color_arr['okseqcolor_text'] = 'status-warn';
                }

                if( $color_arr['okjobcolor']  == 1){
                    $color_arr['okjobcolor_text'] = 'status-ok';
                }else{
                    $color_arr['okjobcolor_text'] = 'status-warn';
                }
            }
            
        }   

        return $color_arr;

    }
        
    public function assignRowColor(&$rows, $color_arr, $force_ng = false) {
        foreach ($rows as &$row) {
            if ($force_ng) {
                $row['row_color'] = 'status-ng';
                continue;
            }

            $status = $row['fasten_status'];

            if ($status == 5) {
                $row['row_color'] = $color_arr['okseqcolor_text'] ?? 'status-ok';
            } elseif ($status == 6) {
                $row['row_color'] = $color_arr['okjobcolor_text'] ?? 'status-ok';
            } elseif ($status == 4) {
                $row['row_color'] = 'status-ok';
            } else {
                $row['row_color'] = 'status-ng';
            }
        }
    }

}
?>