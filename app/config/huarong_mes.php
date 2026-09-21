<?php

declare(strict_types=1);

return [
    // 華榮提供正式 MES Server 後，只需填入此值，例如：
    // 'base_url' => 'http://192.168.100.200:8080',
    'base_url' => '',
    'barcode_path' => '/api/barcode',
    'connect_timeout' => 3,
    'timeout' => 5,

    // 測試模式：true 時不連華榮 MES，直接使用本機 Mock Response。
    // 正式上線前務必改為 false，並設定 base_url。
    'mock_enabled' => true,
    'mock_response_file' => __DIR__ . '/huarong_mes_barcode_mock.json',

    // 華榮配方中的扭力值以 x100 傳送，例如 350 = 3.50。
    // 若正式 MES 定義不同，只需調整此倍率，不需修改 Mapping 程式。
    'torque_scale' => 100,

    // 接口 1 成功後保存工單 Context，接口 2 自動讀取。
    'context_file' => dirname(__DIR__, 2) . '/public/huarong_mes_context.json',
];
