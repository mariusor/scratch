<?php
use vsc\infrastructure\vsc;

/* @var \vsc\application\sitemaps\RwSiteMap $this */
$oModuleMap = $this->getCurrentModuleMap();
$sCurPath = $oModuleMap->getModulePath();

// static files
// $oMap = $this->map ('default.css', $sCurPath . 'static/css/default.css');

// main components
$oModuleMap->setTemplatePath ($sCurPath . 'templates');
$oModuleMap->addStyle ($sCurPath . 'static/css/style.css');

// setting the main template path to our templates folder
$oCtrlMap = $oModuleMap->mapController ('.*', '\\vsc\\application\\controllers\\Html5Controller');
$oCtrlMap->setView ( LOCAL_LIB_PATH . 'presentation/views/View.php');
$oCtrlMap->setMainTemplatePath ($sCurPath . 'templates');
$oCtrlMap->setMainTemplate ('master.php');

if ( vsc::getEnv()->getHttpRequest()->isPost() ) {
	$oMap = $this->map ('(\w*)/?' , '\\littrme\\littr\\application\\processors\\Check.php');
	$oMap->setTemplate('check.php');
	$oSaveCtrlMap = $oMap->mapController('.*', '\\vsc\\application\\controllers\\JsonController');
	$oSaveCtrlMap->setView (VSC_RES_PATH . 'presentation/views/vscjsonview.class.php');
} else {
	// we do this ugly thing as the vscUrlRwDispatcher doesn't know about GET variables
	if ( vsc::getEnv()->getHttpRequest()->hasGetVars() ) {
		if ( vsc::getEnv()->getHttpRequest()->hasGetVar ('show-index') ) {
			$oMap = $this->map ('(.*)/?\Z', '\\littrme\\littr\\application\\processors\\ShowIndex');
			$oMap->setTemplate ('showindex.php');
			$oMap->setTitle ('Littr - listing of child pages');
		} elseif ( vsc::getEnv()->getHttpRequest()->hasGetVar ('random') ) {
			$oMap = $this->map ('(.*)/?\Z', '\\littrme\\littr\\application\\processors\\RedirectToRand');
		} else {
			$oMap = $this->map ('(.*)/?\Z', '\\littrme\\littr\\application\\processors\\SimpleEdit');
		}
	} else {
		$oMap = $this->map ('(.*)/?\Z', '\\littrme\\littr\\application\\processors\\SimpleEdit');
	}

	$oMap->addScript('//ajax.googleapis.com/ajax/libs/jquery/2.0.0/jquery.min.js');
	if (vsc::getEnv()->isDevelopment()) {
		if ( stristr($oMap->getPath(), 'simpleedit') !== false ) {
			$oMap->setTemplate ('main.php');
			$oMap->setTitle ('Littr - edit, protect and share html');
			$oMap->addScript($sCurPath . 'static/js/jquery.editable.js');
			$oMap->addScript($sCurPath . 'static/js/default.js');
		}
// 		if ( stristr($oMap->getPath(), 'showindex') !== false) {
// 			$oMap->addScript($sCurPath . 'static/js/display-links.js');
// 		}
	} else {
		// this needs an extra step of minifying the sources on the server
		if ( stristr($oMap->getPath(), 'simpleedit') !== false ) {
			$oMap->setTemplate ('main.php');
			$oMap->setTitle ('Littr - edit, protect and share html');
			$oMap->addScript($sCurPath . 'static/js/jquery.editable.min.js');
			$oMap->addScript($sCurPath . 'static/js/default.min.js');
		}
// 		if ( stristr($oMap->getPath(), 'showindex') !== false) {
// 			$oMap->addScript($sCurPath . 'static/js/display-links.min.js');
// 		}
	}
}
