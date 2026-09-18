<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta charset="UTF-8">
    <link rel="apple-touch-icon" sizes="60x60" href="<?php echo ICON_NORMAL; ?>">
    <link rel="icon" sizes="192x192" href="<?php echo ICON_NORMAL; ?>">

    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000000">

    <!-- Platform flag must exist BEFORE include_css.php loads settings.js,
         all.js and the other platform-conditional JavaScript modules. -->
    <script>
    window.IS_ICONTROLLER = <?php echo (idas_is_icontroller()) ? 'true' : 'false'; ?>;
    window.IDAS_CURRENT_VIEW = <?php echo json_encode(isset($view) ? (string)$view : '', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
    window.IDAS_IS_LOGIN_PAGE = /^login(?:\/|$)/i.test(String(window.IDAS_CURRENT_VIEW || ''));
    document.documentElement.classList.toggle('icontroller', window.IS_ICONTROLLER);
    document.documentElement.classList.toggle('ntcs', !window.IS_ICONTROLLER);
    document.documentElement.classList.toggle('idas-login-page', window.IDAS_IS_LOGIN_PAGE);
    </script>

    <script src="<?php echo URLROOT; ?>js/jquery-3.7.1.min.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/jquery_dataTables_min.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <?php $includeCssFile = idas_platform_app_file('views/inc/include_css.php'); include_once $includeCssFile; ?>
    <title><?php echo SITENAME; ?></title>    
</head>
<body>
