<?php
/* @var $this vscRwSiteMap */
$oModuleMap = $this->getCurrentModuleMap();
$sCurPath = $oModuleMap->getModulePath();

// static files
// $oMap = $this->map ('default.css', $sCurPath . 'static/css/default.css');

// main components
$oModuleMap->setTemplatePath ($sCurPath . 'templates');
$oModuleMap->addStyle ($sCurPath . 'static/css/style.css');

// setting the main template path to our templates folder
$oCtrlMap = $oModuleMap->mapController ('.*',LOCAL_LIB_PATH . 'application/controllers/ltrcacheablecontroller.class.php');
$oCtrlMap->setView ( LOCAL_LIB_PATH . 'presentation/views/vscinlineresources.class.php');
$oCtrlMap->setMainTemplatePath ($sCurPath . 'templates');
$oCtrlMap->setMainTemplate ('master.php');

if ( vsc::getHttpRequest()->isPost() ) {
	$oMap = $this->map ('(\w*)/?' ,$sCurPath . 'application/processors/ltrcheck.class.php');
	$oMap->setTemplate('check.php');
	$oSaveCtrlMap = $oMap->mapController(VSC_RES_PATH . 'application/controllers/vscjsoncontroller.class.php');
	$oSaveCtrlMap->setView (VSC_RES_PATH . 'presentation/views/vscjsonview.class.php');
} else {
	// we do this ugly thing as the vscUrlRwDispatcher doesn't know about GET variables
	if ( vsc::getHttpRequest()->hasGetVars() ) {
		if ( vsc::getHttpRequest()->hasGetVar ('show-index') ) {
			$oMap = $this->map ('(.*)/?\Z', $sCurPath . 'application/processors/ltrshowindex.class.php');
			$oMap->setTemplate ('showindex.php');
			$oMap->setTitle ('Littr - listing of child pages');
		} elseif ( vsc::getHttpRequest()->hasGetVar ('random') ) {
			$oMap = $this->map ('(.*)/?\Z', $sCurPath . 'application/processors/ltrredirecttorand.class.php');
		} else {
			$oMap = $this->map ('(.*)/?\Z', $sCurPath . 'application/processors/ltrsimpleedit.class.php');
		}
	} else {
		$oMap = $this->map ('(.*)/?\Z', $sCurPath . 'application/processors/ltrsimpleedit.class.php');
	}

	if ( stristr($oMap->getPath(), 'simpleedit') !== false ) {
		$oMap->setTemplate ('main.php');
		$oMap->setTitle ('Littr - edit, protect and share html');

		if (vsc::getEnv()->isDevelopment()) {
			$oMap->addScript($sCurPath . 'static/js/jquery.js');
			$oMap->addScript($sCurPath . 'static/js/default.js');
			$oMap->addScript($sCurPath . 'static/js/jquery.editable.js');
		} else {
			$oMap->addScript('http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js');
			$oMap->addScript($sCurPath . 'static/js/default.min.js');
			$oMap->addScript($sCurPath . 'static/js/jquery.editable.min.js');
		}
	}
}
