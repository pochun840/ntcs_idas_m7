<?php

class Licenses extends Controller
{
    private $AdminModel;
    private $LicenseModel;
    private $ToolController;
    // 在建構子中將 Post 物件（Model）實例化
    public function __construct()
    {
        $this->AdminModel = $this->model('Admin');
        $this->LicenseModel = $this->model('License');
        $this->ToolController = $this->controller_new('Tools');
    }

    // 取得所有info
    public function index(){

        $isMobile = $this->isMobileCheck();
        $device_info = $this->Device_Info();
        $agent_server_ip = $this->AdminModel->Get_Das_Config('agent_server_ip');
        $activate_status = $this->AdminModel->Get_Das_Config('activate_status');
        $expired_date = $this->AdminModel->Get_Das_Config('expired_date');
        // $Controller_Info = $this->ToolModel->GetControllerInfo();

        $this->TableExistCheck();
        $mac = $this->ToolController->getMacAddress();

        $data = [
            'isMobile' => $isMobile,
            'agent_server_ip' => $agent_server_ip,
            'device_info' => $device_info,
            'agent_icon' => 'true',
            'activate_status' => $activate_status,
            'expired_date' => $expired_date,
        ];

        $this->view('license/index', $data);
    }

    //DB檢查，確保所需的table或欄位已建立
    public function TableExistCheck($value='')
    {
        //檢查license table是否存在idas db，若不存在就自動建立        
        $this->LicenseModel->LicenseDB_Initial('license');

        //檢查config table是否存在 activate_status 若不存在就自動建立
        $reslut = $this->AdminModel->Get_Das_Config('activate_status');
        if(!$reslut || $reslut < 2 ){ // 0:未啟用1:試用版2:永久版 ，改成免費使用
            $this->AdminModel->Set_Das_Config('activate_status',2);
        }

        //檢查config table是否存在 expired_date 若不存在就自動建立
        $reslut = $this->AdminModel->Get_Das_Config('expired_date');
        if(!$reslut){ // 2000-01-01
            $this->AdminModel->Set_Das_Config('expired_date','2000-01-01');
        }
    }

    public function AcitveKeyCheck()
    {
        $json_result['result'] = '';
        $encryptedText = $_POST['activate_key'];

        //判斷是否使用過這組key，若使用過則直接return false
        $key_repeat = $this->LicenseModel->ActiveKey_Repeat_Check($encryptedText);
        if($key_repeat){
            $json_result['result'] = false;
            $json_result['error_message'] = 'key repeat';
            echo json_encode($json_result);
            exit();
        }

        $mac = $this->ToolController->getMacAddress();
        $mac = strtolower($mac);
        $fixedValue = 'your_fixed_value';
        $key = $this->generateKey($mac, $fixedValue);

        $decryptText = $this->decryptText($encryptedText,$key);

        if($encryptedText == 'howdoyouturnthison'){//特殊代碼 每台機器都可用1次 試用15天
            $decryptText['issuance_type'] = 1;
            $decryptText['trial_period'] = 15;
        }

        if( isset( $decryptText) 
            && $decryptText['issuance_type'] >= 0 && $decryptText['issuance_type'] <= 2 && is_int($decryptText['issuance_type'])
            && $decryptText['trial_period'] >= 0 && $decryptText['trial_period'] <= 365 && is_int($decryptText['trial_period'])
        ){
            //if (DB中的expired_date < today)則從today開始計算，else 用expired_date 往後加天數
            $db_expired_date = $this->AdminModel->Get_Das_Config('expired_date');
            $today = date('Y-m-d');
            if($today >= $db_expired_date){
                $date = strtotime("+".$decryptText['trial_period']." day");
            }else{
                $date = strtotime($db_expired_date." +".$decryptText['trial_period']." day");
            }
            $expired_date = date("Y-m-d", $date);

            //將key的資料寫入DB
            $this->LicenseModel->Activate_iDas($decryptText,$encryptedText,$expired_date);
            $this->AdminModel->Set_Das_Config('activate_status',$decryptText['issuance_type']);
            $this->AdminModel->Set_Das_Config('expired_date',$expired_date);

            $json_result['result'] = true;
            $json_result['error_message'] = '';
            echo json_encode($json_result);
            exit();
        }else{
            $json_result['result'] = false;
            $json_result['error_message'] = 'key illegal';
            echo json_encode($json_result);
            exit();
        }
    }

    private function generateKey($mac, $fixedValue)
    {
        return hash('sha256', $mac . $fixedValue);
    }

    private function decryptText($encryptedText, $key)
    {
        // 解碼 base64 字串，取得初始化向量、認證標籤和加密後的文本
        $decoded = base64_decode($encryptedText);

        // 取得初始化向量和認證標籤的長度
        $ivLength = openssl_cipher_iv_length('aes-256-gcm');
        $tagLength = 16; // 標籤的長度為固定值

        // 分離初始化向量、認證標籤和加密後的文本
        $iv = substr($decoded, 0, $ivLength);
        $tag = substr($decoded, $ivLength, $tagLength);
        $encrypted = substr($decoded, $ivLength + $tagLength);

        // 使用金鑰、初始化向量和認證標籤進行解密
        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return json_decode($decrypted,true);
    }

    
}