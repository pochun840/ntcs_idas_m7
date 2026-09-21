<?php
//http://192.168.0.97/idas/api/get_operation_api.php?type=json
#==================================
#   匯出鎖附記錄API
#   get_operation_api.php
#==================================

require_once('../app/libraries/Database.php');
require_once('../service/HuarongTighteningReportService.php'); 

$db = new Database();
$db_data = $db->getDb_data();  

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: OPTIONS, GET, POST");
header("Access-Control-Allow-Headers: Content-Type, Depth, User-Agent, X-File-Size, X-Requested-With, If-Modified-Since, X-File-Name, Cache-Control");

// 輸出API說明
$ps_text = '
<!--
NTCS7 匯出鎖附記錄API

介接位址：/api/get_operation_api.php&type=xml
　　　　　/api/get_operation_api.php&limit=100&type=xml


補充說明: 
1.輸出格式為:xml && json && array
-->
';


# 筆數
$limit =isset($_GET['limit']) ? $_GET['limit'] : null;
if(!preg_match('/^\d+$/', $limit)) $limit = 100;

# 輸出類型
$type = $_GET['type'];
if(empty($type)) $type = 'xml';


$sql = "SELECT * FROM `ntcs_data` ";
$sql.= "WHERE 1 ";
$sql .= "ORDER BY id DESC LIMIT ".$limit." ";


$statement = $db_data->prepare($sql); 
$statement->execute(); 

$results = $statement->fetchAll(PDO::FETCH_ASSOC); 
if(!empty($results)){
    $newsItem = $results;
}


# 輸出結果
switch($type){
    
    # 陣列
    case 'array':
        header('Content-type: text/html; charset=utf-8');
        if(!empty($newsItem)){
            echo "<pre>";
            print_r($newsItem);
            echo "</pre>";
        }
    break;
    # 华荣 MES 接口2报文
    case 'huarong_mes':
        header('Content-type: application/json; charset=utf-8');

        if (empty($newsItem)) {
            http_response_code(404);
            echo json_encode([
                'code' => 4001,
                'msg' => '上報失敗',
                'data' => [
                    'SN' => '',
                    'receive_time' => date('Y-m-d H:i:s'),
                    'save_status' => 0,
                    'message' => '沒有可上報的鎖附結果'
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            break;
        }

        // 接口 1 成功後會保存 work_order Context；接口 2 預設自動使用。
        // 若 POST Body 有提供欄位，則以 POST 值覆蓋 Context，保留既有測試/除錯能力。
        $context = [];
        $contextFile = dirname(__DIR__) . '/public/huarong_mes_context.json';
        if (is_file($contextFile) && is_readable($contextFile)) {
            $saved = json_decode((string)file_get_contents($contextFile), true);
            if (is_array($saved)) $context = $saved;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            $raw = file_get_contents('php://input');
            if ($raw !== false && trim($raw) !== '') {
                $decoded = json_decode($raw, true);
                if (!is_array($decoded)) {
                    http_response_code(400);
                    echo json_encode([
                        'code' => 4003,
                        'msg' => '報文格式錯誤',
                        'data' => [
                            'SN' => '',
                            'receive_time' => date('Y-m-d H:i:s'),
                            'save_status' => 0,
                            'message' => 'JSON 解析失敗'
                        ]
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    break;
                }
                $context = array_replace($context, $decoded);
            }
        }

        try {
            $mapper = new HuarongTighteningReportService();
            echo json_encode(
                $mapper->buildPayload($newsItem, $context),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (InvalidArgumentException $e) {
            $code = strpos($e->getMessage(), 'SN') !== false ? 4002 : 4003;
            http_response_code(400);
            echo json_encode([
                'code' => $code,
                'msg' => $code === 4002 ? 'SN 不能為空' : '報文格式錯誤',
                'data' => [
                    'SN' => '',
                    'receive_time' => date('Y-m-d H:i:s'),
                    'save_status' => 0,
                    'message' => $e->getMessage()
                ]
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    break;

    # JSON
    case 'json':
        header('Content-type: application/json; charset=utf-8');
        if(!empty($newsItem)){
            echo json_encode($newsItem);
        }
    break;
    default:
    # XML
    case 'xml':

        header('Content-Type: text/xml; charset=utf-8');
        $fields = [
            'id','system_sn','data_time','device_type','device_id','device_sn',
            'tool_type','tool_sn','tool_status','job_id','job_name',
            'sequence_id','sequence_name','step_id','torque_unit',
            'target_type','target_torque','target_angle','target_time',
            'fasten_time','final_fasten_torque','final_fasten_angle',
            'total_fasten_angle','count_type','last_screw_count',
            'total_screw_count','fasten_status','error_message',
            'fasten_direction','rpm','hi_torque','lo_torque',
            'hi_angle','lo_angle','delay_ttime','threshold_torque',
            'threshold_angle','downshift_torque','downshift_angle',
            'downshift_speed','final_tool_voltage','final_tool_current',
            'barcode',
            'step0_last_times','step0_last_angle','step0_last_torque','step0_last_threadshold',
            'step1_last_times','step1_last_angle','step1_last_torque','step1_last_threadshold',
            'step2_last_times','step2_last_angle','step2_last_torque','step2_last_threadshold',
            'step3_last_times','step3_last_angle','step3_last_torque','step3_last_threadshold',
            'step4_last_times','step4_last_angle','step4_last_torque','step4_last_threadshold',
            'step5_last_times','step5_last_angle','step5_last_torque','step5_last_threadshold',
            'step6_last_times','step6_last_angle','step6_last_torque','step6_last_threadshold',
            'step7_last_times','step7_last_angle','step7_last_torque','step7_last_threadshold',
            'step8_last_times','step8_last_angle','step8_last_torque','step8_last_threadshold',
            'step9_last_times','step9_last_angle','step9_last_torque','step9_last_threadshold',
            'step10_last_times','step10_last_angle','step10_last_torque','step10_last_threadshold',
            'step11_last_times','step11_last_angle','step11_last_torque','step11_last_threadshold',
            'step12_last_times','step12_last_angle','step12_last_torque','step12_last_threadshold',
            'step13_last_times','step13_last_angle','step13_last_torque','step13_last_threadshold',
            'step14_last_times','step14_last_angle','step14_last_torque','step14_last_threadshold',
            'step15_last_times','step15_last_angle','step15_last_torque','step15_last_threadshold'
        ];

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= $ps_text . "\n";
        $xml .= '<ntcs_data>' . "\n";

        if (!empty($newsItem)) {
            foreach ($newsItem as $row) {
                $xml .= "  <item>\n";
                foreach ($fields as $f) {
                    $val = array_key_exists($f, $row) ? $row[$f] : '';
                    // 確保是字串；陣列/物件轉為 JSON 以避免壞掉
                    if (!is_scalar($val)) {
                        $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                    }
                    $xml .= "    <{$f}><![CDATA[" . trim((string)$val) . "]]></{$f}>\n";
                }
                $xml .= "  </item>\n";
            }
        }

        $xml .= "</ntcs_data>\n";
        echo $xml;

    break;


}

?>
