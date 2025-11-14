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
        'Remotes'   => ['pc' => 'jobs.css'],
        'Customize' => ['pc' => 'jobs.css'],
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


    <!-- ================== 其他工具 JS ================== -->

<script>
/* ============================================================
   Device ID 自動偵測 + alertify 語系提示
   ============================================================ */

// -----------------------------
// 1) 安全取得 cookie
// -----------------------------
function getCookieSafe(name) {
    try {
        const m = document.cookie.match(new RegExp('(?:^|; )' + name + '=([^;]*)'));
        return m ? decodeURIComponent(m[1]) : null;
    } catch (e) {
        return null;
    }
}

// -----------------------------
// 2) 取得語系 (en-us / zh-tw / zh-cn)
// -----------------------------
function getLangCode() {
    let lang =
        (typeof getCookie === "function" && getCookie("language")) ||
        getCookieSafe("language") ||
        "zh-tw";

    lang = String(lang).toLowerCase();
    if (lang === "en") lang = "en-us";
    if (!["en-us", "zh-tw", "zh-cn"].includes(lang)) lang = "en-us";
    return lang;
}

// -----------------------------
// 3) 語系提示訊息
// -----------------------------
function getDeviceReloadMessage(newId) {
    const lang = getLangCode();

    if (lang === "zh-tw") {
        return "控制器裝置編號已變更為 " + newId + "，是否重新整理畫面";
    }
    if (lang === "zh-cn") {
        return "控制器设备编号已变更为 " + newId + "，是否重新刷新页面";
    }
    return "Controller device ID has changed to " + newId + ". Reload the page now";
}

// -----------------------------
// 4) alertify 語系 UI
// -----------------------------
function getAlertifyUiText() {
    const lang = getLangCode();

    if (lang === "zh-tw") {
        return { title: "提示", ok: "確定", cancel: "取消" };
    }
    if (lang === "zh-cn") {
        return { title: "提示", ok: "确定", cancel: "取消" };
    }
    return { title: "Notice", ok: "OK", cancel: "Cancel" };
}

// -----------------------------
// 5) 全域變數
// -----------------------------
var currentDeviceId = null;
var deviceReloadDialogShown = false; // 避免狂跳 alert 視窗

// -----------------------------
// 6) 主輪詢函式
// -----------------------------
function pollDeviceId() {
    $.ajax({
        url: "?url=Customize/ajax_check_device_id",
        type: "POST",
        dataType: "json",
        data: {
            current_device_id: currentDeviceId
        },
        success: function (res) {
            if (!res || res.res_type !== "OK") return;

            var newId =
                res.device_id !== null && res.device_id !== undefined
                    ? parseInt(res.device_id, 10)
                    : null;

            if (!Number.isFinite(newId)) newId = null;

            var changed = !!res.changed; // 完全依後端判斷

            // 初始化 currentDeviceId
            if (newId !== null && currentDeviceId === null) {
                currentDeviceId = newId;
                return; // 第一次不彈窗
            }

            // 若後端說 changed = true → 就跳 alertify
            if (newId !== null && changed) {

                if (typeof alertify === "undefined" || !alertify.confirm) {
                    console.warn("Alertify not loaded -- cannot show popup.");
                    return;
                }

                if (deviceReloadDialogShown) return;
                deviceReloadDialogShown = true;

                var msg = getDeviceReloadMessage(newId);
                var ui = getAlertifyUiText();

                alertify
                    .confirm(
                        ui.title,
                        msg,
                        function () {
                            location.reload(); // OK → reload
                        },
                        function () {
                            deviceReloadDialogShown = false; // Cancel → 等下一次偵測
                        }
                    )
                    .set({
                        labels: {
                            ok: ui.ok,
                            cancel: ui.cancel
                        }
                    });
            }

            // 更新 currentDeviceId
            if (newId !== null) currentDeviceId = newId;
        },
        complete: function () {
            setTimeout(pollDeviceId, 2000); // 每2秒檢查一次
        }
    });
}

// -----------------------------
// 7) 初始化
// -----------------------------
$(function () {
    var cookieVal = getCookieSafe("temp_device_id");
    if (cookieVal !== null && cookieVal !== "") {
        currentDeviceId = parseInt(cookieVal, 10);
        if (!Number.isFinite(currentDeviceId)) currentDeviceId = null;
    }

    pollDeviceId(); // 啟動輪詢
});

</script>





