<?php
/* @var $this vscRwSiteMap */
$oModuleMap = $this->getCurrentModuleMap();
$sCurPath = $oModuleMap->getModulePath();

// static files
// $oMap = $this->map ('default.css', $sCurPath . 'static/css/default.css');
if (vsc::getEnv()->isDevelopment()) {
	$oModuleMap->addScript($sCurPath . 'static/js/jquery.js');
	$oModuleMap->addScript($sCurPath . 'static/js/default.js');
	$oModuleMap->addScript($sCurPath . 'static/js/jquery.editable.js');
	$oModuleMap->addStyle($sCurPath . 'static/css/style.css');
} else {
	$oModuleMap->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js');
	$oModuleMap->addScript($sCurPath . 'static/js/default.min.js');
	$oModuleMap->addScript($sCurPath . 'static/js/jquery.editable.min.js');
	$oModuleMap->addStyle($sCurPath . 'static/css/style.min.css');
}

// main components
$oModuleMap->setTemplatePath ($sCurPath . 'templates');

// $oJsMap = $oModuleMap->mapController ('.*\.js\Z' , LOCAL_LIB_PATH . 'application/controllers/vsccacheablecontroller.class.php');
// $oJsMap->setView(VSC_RES_PATH . 'presentation/views/vscjsview.class.php');

// setting the main template path to our templates folder
// $oCtrlMap = $oModuleMap->mapController('.*', VSC_RES_PATH . 'application/controllers/vschtml5controller.class.php');
$oCtrlMap = $oModuleMap->mapController('.*',LOCAL_LIB_PATH . 'application/controllers/ltrcacheablecontroller.class.php');
$oCtrlMap->setView(LOCAL_LIB_PATH . 'presentation/views/vscinlineresources.class.php');
$oCtrlMap->setMainTemplatePath($sCurPath . 'templates');
$oCtrlMap->setMainTemplate('master.php');

if (vsc::getHttpRequest()->isPost()) {
	$oMap = $this->map ('(\w*)/?' ,$sCurPath . 'application/processors/check.class.php');
	$oMap->setTemplate('check.php');
	$oSaveCtrlMap = $oMap->mapController(VSC_RES_PATH . 'application/controllers/vscjsoncontroller.class.php');
	$oSaveCtrlMap->setView (VSC_RES_PATH . 'presentation/views/vscjsonview.class.php');
} else {
	$oMap = $this->map ('(\w*)/?' ,$sCurPath . 'application/processors/simpleedit.class.php');
	$oMap->setTemplate ('main.php');
	$oMap->setTitle ('Littr - edit, protect and share html');
}
