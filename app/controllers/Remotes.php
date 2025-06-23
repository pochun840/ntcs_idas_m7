<?php

class Remotes extends Controller
{
    private $DataModel;
    private $SettingModel;
    private $MiscellaneousModel;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->DataModel = $this->model('Datas');
        $this->SettingModel = $this->model('Setting');
        $this->MiscellaneousModel = $this->model('Miscellaneous');
    }

    // 取得所有Jobs
    public function index(){

        /*$file = $this->MiscellaneousModel->lang_load();
        if(!empty($file)){
            include $file;
        }*/


        $isMobile = $this->isMobileCheck();
        $job_list = $this->SettingModel->get_job_list();

        $data = [
            'isMobile' => $isMobile,
            'job_list' => $job_list,
        ];
        
        $this->view('remote/index', $data);

    }

    public function Change_Job($value=''){
        
       $error_message = '';
        $input_check = true;
        if( !empty($_GET['job_id']) && isset($_GET['job_id'])  ){
            $job_id = $_GET['job_id'];
        }else{ 
            $input_check = false;
            $error_message .= "job_id,";
        }
        if( !empty($_GET['seq_id']) && isset($_GET['seq_id'])  ){
            $seq_id = $_GET['seq_id'];
        }else{ 
            $input_check = false;
            $error_message .= "seq_id,";
        }

        if($input_check && PHP_OS_FAMILY == 'Linux'){ 
            require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
            $modbus = new ModbusMaster("127.0.0.1", "TCP");
            try {
                $modbus->port = 502;
                $modbus->timeout_sec = 10;
                $data = array($job_id,$seq_id);
                $dataTypes = array("INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT");

                // FC 16
                $modbus->writeMultipleRegister(0, 463, $data, $dataTypes);
                // $this->logMessage('modbus write 506 ,array = '.implode("','", $data));
                // $this->logMessage('modbus status:'.$modbus->status);
                // $this->logMessage('Import config end');
                // echo json_encode(array('error' => ''))

                echo json_encode(array('error' => '','modbus_status' => $modbus->status));
                exit();

            } catch (Exception $e) {
                $this->logMessage('modbus write 265 fail');
                echo json_encode(array('error' => 'modbus fail'));
                exit();
            }
        }else{
            echo json_encode(array('error' => $error_message));
            exit();
        }
    }

    public function get_current_job($value='')
    {
        $error_message = '';
        if(PHP_OS_FAMILY == 'Linux'){
            require_once '../modules/phpmodbus-master/Phpmodbus/ModbusMaster.php';
            $modbus = new ModbusMaster("127.0.0.1", "TCP");
            try {
                $modbus->port = 502;
                $modbus->timeout_sec = 10;
                $dataTypes = array("INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT", "INT");

                // FC 3
                $recData = $modbus->readMultipleRegisters(1, 4305, 3);
                //$recData[0] = ;$recData[1] = ;$recData[2] = ;$recData[3] = ;


                $data['jod_id']  = $recData[0]*16 + $recData[1];
                $data['seq_id']  = $recData[2]*16 + $recData[3];
                $data['step_id'] = $recData[4]*16 + $recData[5];

                echo json_encode(array('error' => '','result' => $data));
                exit();

            } catch (Exception $e) {
                $this->logMessage('modbus read 4305 fail');
                echo json_encode(array('error' => 'modbus fail'));
                exit();
            }
        }else{
            echo json_encode(array('error' => $error_message));
            exit();
        }
    }

    
}