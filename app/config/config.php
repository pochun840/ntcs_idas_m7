<?php

//sudo chmod -R 777 /var/www/html/idas
// sudo chmod -R 777  var/www/html/idas/public/ftp
//sudo chmod -R 777 /var/www/html/database
//sudo rm -rf /var/www/html/idas
//sudo rm -rf /var/www/html/database/KLS_NTCS_IDAS.Lin
//sudo chown -R www-data:www-data /var/www/html/idas/app/views/step
//12345678rd
//

date_default_timezone_set(trim(shell_exec('cat /etc/timezone')));

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




define('CONTROLLER_IP', '127.0.0.1');

// 每次刷新都取最新時間，避免快取
define('ASSET_VERSION', date('YmdHi')); 

//table - barcode 
define('TABLE_NTCS_BARCODE', 'ntcs_barcode_test');

//table - tools
define('TABLE_NTCS_TOOLS', 'ntcs_tool_test');


//table - device
define('TABLE_NTCS_DEVICE', 'ntcs_device_test');


// 抓取APP的檔案名稱，判斷是哪一個品牌
$brand = get_iconmode_from_ver();
//var_dump($brand_code);
//if()
//$brand = 0;//預設值帶kilews

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


// iDAS出貨版本 0:Kilews 2:上海 shanhai 4:MyTorque 5:晶元SUMAKE 6:DELTA 7:白牌
define('ICONMODE', (int)$brand);

switch (ICONMODE) {
	case 0: // Kilews
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       'KILEWS');
		define('SUBTITLE_INDEX',    'iDAS FOR KL-NTCS-M7');
		define('TITLE_AGENT',       'KILEWS IoT Agent');
		define('DEVICE_TYPE_11',    'KL-NTCS-M7');
	break;

	case 2: // 上海 shanhai
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       'KILEWS');
		define('SUBTITLE_INDEX',    'iDAS FOR KL-EPNC-M7');
		define('TITLE_AGENT',       'EPNC IoT Agent');
		define('DEVICE_TYPE_11',    'KL-EPNC-M7');
	break;

	case 4: // MyTorque
		define('ICON_NORMAL',       URLROOT . 'img/MY-icon/yellow-192x192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/MY-icon/yellow-60x60.png');
		define('ICON_AGENT',        URLROOT . 'img/MY-icon/blue-192x192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/MY-icon/blue-60x60.png');
		define('TITLE_INDEX',       'MYTORQ');
		define('SUBTITLE_INDEX',    'iDAS FOR MY-EVO-M7');
		define('TITLE_AGENT',       'MYTORQ IoT Agent');
		define('DEVICE_TYPE_11',    'MY-EVO-3 mini');
	break;

	case 5: // 晶元 SUMAKE
		define('ICON_NORMAL',       URLROOT . 'img/Sumake_icon/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/Sumake_icon/60.png');
		define('ICON_AGENT',        URLROOT . 'img/Sumake_icon/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/Sumake_icon/60.png');
		define('TITLE_INDEX',       'SUMAKE');
		define('SUBTITLE_INDEX',    'iDAS FOR SMT-C3');
		define('TITLE_AGENT',       'SUMAKE IoT Agent');
		define('DEVICE_TYPE_11',    'SMT-C3');
	break;

	case 6: // DELTA
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       'DELTA');
		define('SUBTITLE_INDEX',    'iDAS FOR XTCA1');
		define('TITLE_AGENT',       'DELTA IoT Agent');
		define('DEVICE_TYPE_11',    'NTCS-M7');
	break;

	case 7: // 白牌
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       '');
		define('SUBTITLE_INDEX',    'iDAS FOR OPT-GK TRS1');
		define('TITLE_AGENT',       'IoT Agent');
		define('DEVICE_TYPE_11',    'NTCS-M7');
	break;

	default:
		define('ICON_NORMAL',       URLROOT . 'img/192.png');
		define('ICON_NORMAL_APPLE', URLROOT . 'img/60.png');
		define('ICON_AGENT',        URLROOT . 'img/192.png');
		define('ICON_AGENT_APPLE',  URLROOT . 'img/60.png');
		define('TITLE_INDEX',       'KILEWS');
		define('SUBTITLE_INDEX',    'iDAS FOR KL-NTCS-M7');
		define('TITLE_AGENT',       'KILEWS IoT Agent');
		define('DEVICE_TYPE_11',    'NTCS-M7');
	break;
}



function get_iconmode_from_ver()
{
    // 非 Linux 直接回預設
    if (!defined('PHP_OS_FAMILY') || PHP_OS_FAMILY !== 'Linux') {
        return 0; // 預設 Kilews
    }

    $verFile = '/home/kls/NTCS7/version';
    if (!is_file($verFile) || !is_readable($verFile)) {
        return 0;
    }

    $content = file_get_contents($verFile);
    if ($content === false) {
        return 0;
    }

    // 取第一行
    $lines = preg_split("/\r\n|\n|\r/", trim($content));
    $firstLine = isset($lines[0]) ? trim((string)$lines[0]) : '';
    if ($firstLine === '') {
        return 0;
    }

    //只取空格前面的識別碼(第一個)
    $brandKey = explode(' ', $firstLine, 2)[0];

    // 品牌對照
    $map = [
        'NTCS7'  => 0, // Kilews
        'EPNC7'  => 2, // 上海
        'SMT-C3' => 5, // SUMAKE
		'MY-EVO' =>4, //MYTORQ
    ];

    return $map[$brandKey] ?? 0;
}
