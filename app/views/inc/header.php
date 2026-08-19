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

    <script src="<?php echo URLROOT; ?>js/jquery-3.7.1.min.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo URLROOT; ?>js/jquery_dataTables_min.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <?php $includeCssFile = idas_platform_app_file('views/inc/include_css.php'); include_once $includeCssFile; ?>
    <title><?php echo SITENAME; ?></title>    
    
<script>
window.IS_ICONTROLLER = <?php echo (defined('IS_ICONTROLLER') && IS_ICONTROLLER) ? 'true' : 'false'; ?>;
document.documentElement.classList.toggle('icontroller', window.IS_ICONTROLLER);
document.documentElement.classList.toggle('ntcs', !window.IS_ICONTROLLER);
</script>
</head>
<body>