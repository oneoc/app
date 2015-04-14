<?php
// 检测PHP环境
if(version_compare(PHP_VERSION, '5.3.0','<'))  die('require PHP > 5.3.0 !');

define('DS', DIRECTORY_SEPARATOR);
// 站点目录
define('SITE_DIR', dirname(__FILE__));
// 站点地址 TODO 防止在控制台中报错增加判断
define('SCRIPT_DIR', (isset($_SERVER['SCRIPT_NAME']) ? rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/\\') : ''));
define('SITE_URL', isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] . SCRIPT_DIR : '');
//来源页面
define('HTTP_REFERER', isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '');
//文件上传根目录
define('UPLOAD_PATH', './Public/upload/');

// ThinkPHP定义
define('APP_DEBUG', true);
define('THINK_PATH', SITE_DIR . DS . 'Libs' . DS . 'ThinkPHP' . DS);
define('APP_PATH', SITE_DIR . DS . 'App' . DS);
define('RUNTIME_PATH', SITE_DIR . DS . 'Public' . DS . 'Runtime' . DS);   // 系统运行时目录

require(THINK_PATH.'ThinkPHP.php');