<?php
error_reporting (E_ALL); ini_set('display_errors', 1);

//define ('LOCAL_CONFIG_PATH', realpath('../config') . DIRECTORY_SEPARATOR);
define ('LOCAL_LIB_PATH', realpath('../lib/') . DIRECTORY_SEPARATOR);
define ('LOCAL_RES_PATH', realpath('../res/') . DIRECTORY_SEPARATOR);


include (LOCAL_RES_PATH . 'functions.inc.php');

if (stristr($_SERVER['REMOTE_ADDR'], '127.0.0.1') == true) { // a hard-coded isDevelopment
	define ('VSC_PATH', realpath ('/home/habarnam/workspace/vsc-v2/') . DIRECTORY_SEPARATOR);
	define ('DB_HOST', 'localhost');
	define ('DB_USER', 'root');
	define ('DB_PASS', 'asd');
	define ('DB_NAME', 'htlm');
} else {
	define ('VSC_PATH', realpath ('/srv/http/vsc-v2-no-sql/') . DIRECTORY_SEPARATOR);
	define ('DB_HOST', 'localhost');
	define ('DB_USER', 'htlm');
	define ('DB_PASS', ';\'asd');
	define ('DB_NAME', 'htlm');
}

date_default_timezone_set('Europe/Bucharest');
// set function overloading for unicode
// ini_set('mbstring.func_overload', '1');

// including lib vsc
if (!@include(VSC_PATH . 'vsc.inc.php')) {
	throw new ErrorException('Could not load libVSC');
}

import (LOCAL_LIB_PATH);
import (LOCAL_RES_PATH);

set_error_handler('exceptions_error_handler');