<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once 'config/config.php';

spl_autoload_register(function($className){
    require_once 'libraries/' . $className . '.php';
});