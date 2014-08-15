<?php
error_reporting (E_ALL); ini_set('display_errors', 1);

//define ('LOCAL_CONFIG_PATH', realpath('../config') . DIRECTORY_SEPARATOR);
define ('LOCAL_LIB_PATH', realpath('../lib/') . DIRECTORY_SEPARATOR);
define ('LOCAL_RES_PATH', realpath('../res/') . DIRECTORY_SEPARATOR);


include (LOCAL_RES_PATH . 'functions.inc.php');

date_default_timezone_set('Europe/Bucharest');
// set function overloading for unicode
// ini_set('mbstring.func_overload', '1');

chdir(dirname(__FILE__));
// Composer autoloading.
if ( file_exists('vendor/autoload.php') ) {
	$loader = include_once 'vendor/autoload.php';
} else {
	throw new RuntimeException('Unable to load the autoloader. Run `php composer.phar install`.');
}
if ( file_exists('vendor' . DIRECTORY_SEPARATOR . 'vsc' . DIRECTORY_SEPARATOR . 'vsc' . DIRECTORY_SEPARATOR . 'vsc.inc.php') ) {
	include_once 'vendor' . DIRECTORY_SEPARATOR . 'vsc' . DIRECTORY_SEPARATOR . 'vsc' . DIRECTORY_SEPARATOR . 'vsc.inc.php';
} else {
	throw new RuntimeException('Unable to load vsc library. Run `php composer.phar install`.');
}

if ( file_exists('vendor' . DIRECTORY_SEPARATOR . 'vsc' . DIRECTORY_SEPARATOR . 'orm' . DIRECTORY_SEPARATOR . 'orm.inc.php') ) {
	include_once 'vendor' . DIRECTORY_SEPARATOR . 'vsc' . DIRECTORY_SEPARATOR . 'orm' . DIRECTORY_SEPARATOR . 'orm.inc.php';
} else {
	throw new RuntimeException('Unable to load vsc\'s library orm module. Run `php composer.phar install`.');
}

set_error_handler(
	function($iSeverity, $sMessage, $sFilename, $iLineNo) {
		\vsc\exceptions_error_handler($iSeverity, $sMessage, $sFilename, $iLineNo);
	}
);
