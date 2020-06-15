<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('memory_limit', '64M');
setlocale(LC_ALL, 'en-US');
date_default_timezone_set('America/Los_Angeles');
// load dependencies
define('DIR_BASE',	dirname(dirname(__FILE__)) . '/');
define('DIR_APP',	DIR_BASE . 'App/');
require(DIR_APP . 'functions.php');
require(DIR_APP . 'TidesApi/TidesApi.php');
require(DIR_APP . 'TideCalendar/TideCalendar.php');

set_error_handler('errorAsExceptionHandler');

// define app vars
define('DIR_DATA',		DIR_BASE	. 'data/');

define('URL_BASE',	baseUrl());
define('URL_IMG',	baseUrl()	. 'img/');
define('URL_CSS',	baseUrl()	. 'css/');
define('URL_LESS',	baseUrl()	. 'less/');
define('URL_JS',	baseUrl()	. 'js/');

// define template vars
define('DIR_TEMPLATE',		DIR_BASE		. 'template/');
define('TEMPLATE_HEADER',	DIR_TEMPLATE	. 'header.php');
define('TEMPLATE_FOOTER',	DIR_TEMPLATE	. 'footer.php');

// files
define('TIDES_CACHE_FILE_BASE',	DIR_DATA . 'tides-');

// define('DEBUG',		true);
define('DEBUG',		false);
// define('USE_LESS',		true);
define('USE_LESS',		false);

// load config
$config = loadConfig();