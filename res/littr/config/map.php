<?php
/* @var $this vscRwSiteMap */
$oModuleMap = $this->getCurrentModuleMap();
$sCurPath = $oModuleMap->getModulePath();

// static files
$oMap = $this->map ('default.css', $sCurPath . 'static/css/default.css');
if (vsc::getEnv()->isDevelopment()) {
	$oMap = $this->map ('default.js', $sCurPath . 'static/js/default.js');
	$oMap = $this->map ('jquery.editable.js', $sCurPath . 'static/js/jquery.editable.js');
} else {
	$oMap = $this->map ('default.js', $sCurPath . 'static/js/default.min.js');
	$oMap = $this->map ('jquery.editable.js', $sCurPath . 'static/js/jquery.editable.min.js');
}
// main components
$oModuleMap->setTemplatePath ($sCurPath . 'templates');
$oModuleMap->addScript('https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js');
$oModuleMap->addStyle('/default.css');
$oModuleMap->addScript('/jquery.editable.js');
$oModuleMap->addScript('/default.js');

$oCssMap = $oModuleMap->mapController ('.*\.css\Z' , LOCAL_LIB_PATH . 'application/controllers/vsccacheablecontroller.class.php');
$oCssMap->setView(VSC_RES_PATH . 'presentation/views/vsccssview.class.php');

$oJsMap = $oModuleMap->mapController ('.*\.js\Z' , LOCAL_LIB_PATH . 'application/controllers/vsccacheablecontroller.class.php');
$oJsMap->setView(VSC_RES_PATH . 'presentation/views/vscjsview.class.php');

$oModuleMap->mapController('.*', VSC_RES_PATH . 'application/controllers/vschtml5controller.class.php');

// $oMap = $this->map ('\Z', $sCurPath . 'application/processors/redirecttorand.class.php');

// ajax save/authenticate controller
$oMap = $this->map ('s/?\Z', $sCurPath . 'application/processors/check.class.php');
$oMap->setTemplate('check.php');
$oSaveCtrlMap = $oMap->mapController(VSC_RES_PATH . 'application/controllers/vscjsoncontroller.class.php');
$oSaveCtrlMap->setView (VSC_RES_PATH . 'presentation/views/vscjsonview.class.php');

// ajax save controller
// $oMap = $this->map ('save/?\Z', $sCurPath . 'application/processors/save.class.php');
// $oMap->setTemplate('save.php');

$oSaveCtrlMap = $oMap->mapController(VSC_RES_PATH . 'application/controllers/vscjsoncontroller.class.php');
$oSaveCtrlMap->setView(VSC_RES_PATH . 'presentation/views/vscjsonview.class.php');;

// user edit
$oMap = $this->map ('~(\w+)(?:/(\w*))/?' ,$sCurPath . 'application/processors/useredit.class.php');
$oMap->setTemplate ('main.php');
$oMap->setTitle ('Littr - edit protect and share html');

// simple edit
$oMap = $this->map ('(\w*)/?' ,$sCurPath . 'application/processors/simpleedit.class.php');
$oMap->setTemplate ('main.php');
$oMap->setTitle ('Littr - edit protect and share html');