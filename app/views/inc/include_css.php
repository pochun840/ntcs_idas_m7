<?php 
// 共用：條件式載入 CSS / JS（根據 URL 第一層）
function include_asset($part, $fileName) {
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $queryStringWithoutUrl = str_replace('url=', '', $queryString);
    $parts = explode('/', $queryStringWithoutUrl);
    $firstPart = $parts[0] ?? '';
    $extension = pathinfo($fileName, PATHINFO_EXTENSION);

    //特別排除 Sequences 頁面載入 sequences.js（強制不要載）
    if (!($firstPart === 'Sequences' && $fileName === 'sequences.js')) {
        if ($firstPart === $part) {
            $path = ($extension === 'css') ? 'css' : 'js';
            $tag = ($extension === 'css')
                ? "<link rel=\"stylesheet\" href=\"" . URLROOT . "$path/$fileName?v=" . ASSET_VERSION . "\">"
                : "<script src=\"" . URLROOT . "$path/$fileName?v=" . ASSET_VERSION . "\"></script>";
            echo $tag . "\n";
        }
    }

    //額外條件：若網址是 Sequences，就強制載入 seq.js
    if ($firstPart === 'Sequences' && $fileName === 'sequences.js') {
        echo "<script src=\"" . URLROOT . "js/seq.js?v=" . ASSET_VERSION . "\"></script>\n";
    }
}

function include_css() {
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $routeParts = explode('/', str_replace('url=', '', $queryString));
    $controller = $routeParts[0] ?? '';
    $action = $routeParts[1] ?? '';

    $isMobile = isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $_SERVER['HTTP_USER_AGENT']);

    // 模組對應表
    $cssMap = [
        'Jobs'      => ['pc' => 'jobs.css',    'mobile' => 'jobs_m.css'],
        'Sequences' => ['pc' => 'seq.css',     'mobile' => 'seq_m.css'],
        'Step'      => ['pc' => 'step.css',    'mobile' => 'step_m.css'],
        'Inputs'    => ['pc' => 'input.css',   'mobile' => 'input_m.css'],
        'Outputs'   => ['pc' => 'output.css',  'mobile' => 'output_m.css'],
        'Settings'  => ['pc' => 'setting.css', 'mobile' => 'setting_m.css'],
        'Tools'     => ['pc' => 'tools.css'],
        'Data'      => ['pc' => 'data.css'],
        'Agents'    => ['pc' => 'agent.css'],
    ];

    $cssFile = null;

    // 特例處理 - Dashboards 模組
    if ($controller === 'Dashboards') {
        if ($action === 'index') {
            $cssFile = 'tcc_main.css';
        } elseif ($action === 'operation') {
            $cssFile = $isMobile ? 'operation_m.css' : 'operation.css';
        } else {
            $cssFile = 'operation.css'; // fallback
        }


    // 特例處理 - In 模組
    } elseif ($controller === 'In') {
        $cssFile = 'main.css';

    // 一般對應
    } elseif (isset($cssMap[$controller])) {
        $cssFile = $isMobile && isset($cssMap[$controller]['mobile']) 
            ? $cssMap[$controller]['mobile'] 
            : $cssMap[$controller]['pc'];
    }

    // 輸出 <link>
    if ($cssFile) {
        echo '<link rel="stylesheet" href="' . URLROOT . 'css/' . $cssFile . '?v=' . ASSET_VERSION . '" type="text/css">' . "\n";
    }
}



?>

    <!-- ================== 基礎 JS ================== -->
    <script src="<?php echo URLROOT; ?>js/jquery-3.7.1.min.js?v=<?php echo ASSET_VERSION; ?>"></script>

    <!-- ================== 基礎 CSS ================== -->
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/jquery_data_Tables.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/datatables.min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/w3.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/font-awesome.min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/flatpickr.min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/alertify_min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/default_min.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo URLROOT; ?>css/footer.css?v=<?php echo ASSET_VERSION; ?>">
    <?php
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $route = explode('/', str_replace('url=', '', $queryString))[0] ?? '';

        // 檢查是否為行動裝置
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod/i', $userAgent);

        // 不在 Inputs 或 Outputs 頁面時，根據裝置載入對應的 CSS
        if (!in_array($route, ['Inputs', 'Outputs'])) {
            $cssFile = $isMobile ? 'share_m.css' : 'share.css';
            echo '<link rel="stylesheet" href="' . URLROOT . 'css/' . $cssFile . '?v=' . ASSET_VERSION . '">' . "\n";
        }
    ?>


    
    <!-- ================== 模組 CSS 動態載入 ================== -->
    <?php echo include_css();?>


    <!-- ================== 共用 JS ================== -->
    <script src="<?php echo URLROOT; ?>js/all.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/echarts_min.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/jquery_data_Tables.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/alertify_min.js?v=<?php echo ASSET_VERSION; ?>"></script>



    <!-- ================== 模組 JS 動態載入 ================== -->
    <?php 
    $modules = ['Inputs', 'Outputs', 'Jobs', 'Data', 'Sequences', 'Step', 'Settings'];
    foreach ($modules as $mod) {
        include_asset($mod, strtolower($mod) . '.js');
    }
    ?>

    <!-- ================== 其他工具 JS ================== -->
    <script src="<?php echo URLROOT; ?>js/flatpickr.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/flatpickr_zh-tw.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/tcc_data.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/jszip.js?v=<?php echo ASSET_VERSION; ?>"></script>
