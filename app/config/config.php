<?php
// App 根目錄，這是引入 app 資料夾裡的資源用的
define('APPROOT', dirname(dirname(__FILE__)) . '/');

// URL 根目錄，這是引入 public 資料夾裡的資源，或是頁面跳轉時用的
define('URLROOT', '../public/'); //local用

// 網站名稱
define('SITENAME', 'iDAS');

// iDAS連線模式 0:單機版 1:連線版
define('IDASMODE', '1');


// 設定語言狀態
$language = array(
	0=>array('简中','zh-cn'),
	1=>array('繁中','zh-tw'),
	2=>array('English','en-us'),
);
define('LANGUAGE',$language);


// 抓取APP的檔案名稱，判斷是哪一個品牌
//$brand_code = get_brand_code();
$brand = '0';//預設值帶kilews

/*if($brand_code == false || $brand_code == 'BF01'){ //Kilews or Windows
	$brand = '0';
}else if($brand_code == 'BF02'){ //上海
	$brand = '2';
}else if($brand_code == 'BF04'){ //MyTorq
	$brand = '4';
}else if($brand_code == 'BF05'){ //SUMAKE
	$brand = '5';
}else if($brand_code == 'BF06'){ //DELTA
	$brand = '6';
}else if($brand_code == 'BF07'){ //白牌
	$brand = '7';
}*/

// iDAS出貨版本 0:Kilews 2:上海 shanhai 4:MyTorque 5:晶元SUMAKE 6:DELTA 7:白牌 6:
define('ICONMODE', $brand);

define('TITLE_INDEX',       'KILEWS');
define('SUBTITLE_INDEX',    'iDAS for TCC-M7');
define('ICON_NORMAL_APPLE', URLROOT.'img/60.png');
define('ICON_AGENT',        URLROOT.'img/192.png');

/*switch ( ICONMODE ) {
	case '0': // Kilews
		define('ICON_NORMAL',       URLROOT.'img/192.png'); // normal icon
		define('ICON_NORMAL_APPLE', URLROOT.'img/60.png');  // apple icon
		define('ICON_AGENT',        URLROOT.'img/192.png'); // normal icon
		define('ICON_AGENT_APPLE',  URLROOT.'img/60.png');  // apple icon
		define('TITLE_INDEX',       'KILEWS');              // 首頁title
		define('SUBTITLE_INDEX',    'iDAS FOR KILEWS');     // 首頁subtitle
		define('TITLE_AGENT',       'KILEWS IoT Agent');    // Agent頁title
		define('DEVICE_TYPE_7',     'KL-GTCS');    // Agent頁title
		break;
	case '4': // MyTorque
		define('ICON_NORMAL',       URLROOT.'img/MY-icon/yellow-192x192.png');
		define('ICON_NORMAL_APPLE', URLROOT.'img/MY-icon/yellow-60x60.png');
		define('ICON_AGENT',        URLROOT.'img/MY-icon/blue-192x192.png');
		define('ICON_AGENT_APPLE',  URLROOT.'img/MY-icon/blue-60x60.png');
		define('TITLE_INDEX',       'MYTORQ');
		define('SUBTITLE_INDEX',    'iDAS FOR MY-SIRIUS');
		define('TITLE_AGENT',       'MYTORQ IoT Agent');
		define('DEVICE_TYPE_7',     'MY-SIRIUS');    // Agent頁title
		break;
	case '2': // 上海 shanhai
		define('ICON_NORMAL',       URLROOT.'img/192.png'); // normal icon
		define('ICON_NORMAL_APPLE', URLROOT.'img/60.png');  // apple icon
		define('ICON_AGENT',        URLROOT.'img/192.png'); // normal icon
		define('ICON_AGENT_APPLE',  URLROOT.'img/60.png');  // apple icon
		define('TITLE_INDEX',       'KILEWS');              // 首頁title
		define('SUBTITLE_INDEX',    'iDAS FOR KILEWS');     // 首頁subtitle
		define('TITLE_AGENT',       'KILEWS IoT Agent');    // Agent頁title
		define('DEVICE_TYPE_7',     'KL-EPIC');    // Agent頁title
		break;
	case '5': // 晶元SUMAKE
		define('ICON_NORMAL',       URLROOT.'img/192.png'); // normal icon
		define('ICON_NORMAL_APPLE', URLROOT.'img/60.png');  // apple icon
		define('ICON_AGENT',        URLROOT.'img/192.png'); // normal icon
		define('ICON_AGENT_APPLE',  URLROOT.'img/60.png');  // apple icon
		define('TITLE_INDEX',       'SUMAKE');              // 首頁title
		define('SUBTITLE_INDEX',    'iDAS FOR SMT-C2');     // 首頁subtitle
		define('TITLE_AGENT',       'SUMAKE IoT Agent');    // Agent頁title
		define('DEVICE_TYPE_7',     'SMT-C2');    // Agent頁title
		break;
	case '6': // DELTA
		define('ICON_NORMAL',       URLROOT.'img/192.png'); // normal icon
		define('ICON_NORMAL_APPLE', URLROOT.'img/60.png');  // apple icon
		define('ICON_AGENT',        URLROOT.'img/192.png'); // normal icon
		define('ICON_AGENT_APPLE',  URLROOT.'img/60.png');  // apple icon
		define('TITLE_INDEX',       'DELTA');              // 首頁title
		define('SUBTITLE_INDEX',    'iDAS FOR XTCA1');     // 首頁subtitle
		define('TITLE_AGENT',       'DELTA IoT Agent');    // Agent頁title
		define('DEVICE_TYPE_7',     'XTCA1');    // Agent頁title
		break;
	case '7': // 白牌
		define('ICON_NORMAL',       URLROOT.'img/192.png'); // normal icon
		define('ICON_NORMAL_APPLE', URLROOT.'img/60.png');  // apple icon
		define('ICON_AGENT',        URLROOT.'img/192.png'); // normal icon
		define('ICON_AGENT_APPLE',  URLROOT.'img/60.png');  // apple icon
		define('TITLE_INDEX',       '');              // 首頁title
		define('SUBTITLE_INDEX',    'iDAS FOR OPT-GK TRS1');     // 首頁subtitle
		define('TITLE_AGENT',       'IoT Agent');    // Agent頁title
		define('DEVICE_TYPE_7',     'OPT-GK TRS1');    // Agent頁title
		break;
	// case '8':
	// 	// code...
	// 	break;
	// case '7':
	// 	// code...
	// 	break;
	
	default:
		define('ICON_NORMAL',       URLROOT.'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT.'img/60.png');
		define('ICON_AGENT',        URLROOT.'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT.'img/60.png');
		define('TITLE_INDEX',       'KILEWS');
		define('SUBTITLE_INDEX',    'iDAS for TCC-M7');
		define('TITLE_AGENT',       'KILEWS IoT Agent');
		define('DEVICE_TYPE_7',     'KL-GTCS');    // Agent頁title
		break;
}*/



/*function get_brand_code()
{
	if( PHP_OS_FAMILY == 'Linux'){
		$directory = '/home/kls/project/system/ltver'; // 指定目錄路徑

		// 取得目錄中的檔案和子目錄列表
		$fileList = scandir($directory);

		// 移除 "." 和 ".." 兩個特殊條目
		$fileList = array_diff($fileList, array('.', '..'));

		// 輸出檔案和子目錄列表
		$explode_result = explode("-",$fileList[2]);

		$brand_code = $explode_result[0];

		return $brand_code;
	}else{
		return false;
	}
}*/