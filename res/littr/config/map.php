<?php
use vsc\infrastructure\vsc;

/* @var \vsc\application\sitemaps\RwSiteMap $this */
$oModuleMap = $this->getCurrentModuleMap();
$sCurPath = $oModuleMap->getModulePath();

// static files
//$oMap = $this->map ('default.css', 'static/css/style.css');
//$oMap = $this->map ('default.js', 'static/js/default.js');

// main components
$oModuleMap->setTemplatePath ('templates');
$oModuleMap->addStyle ('static/css/style.css');

// setting the main template path to our templates folder
$oCtrlMap = $oModuleMap->mapController ('.*', '\\vsc\\application\\controllers\\Html5Controller');
$oCtrlMap->setView ('\\littrme\\presentation\\views\\View');
$oCtrlMap->setMainTemplatePath ('templates');
$oCtrlMap->setMainTemplate ('master.php');

if ( vsc::getEnv()->getHttpRequest()->isPost() ) {
	$oMap = $this->map ('(\w*)/?' , '\\littrme\\littr\\application\\processors\\Check');
	$oMap->setTemplate('check.php');
	$oSaveCtrlMap = $oMap->mapController('.*', '\\vsc\\application\\controllers\\JsonController');
	$oSaveCtrlMap->setView ('\\vsc\\presentation\\views\\JsonView');
} else {
	// we do this ugly thing as the @var UrlRwDispatcher doesn't know about GET variables
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
	// this needs an extra step of minifying the sources on the server
	if ( stristr($oMap->getPath(), 'simpleedit') !== false ) {
		$oMap->setTemplate ('main.php');
		$oMap->setTitle ('Littr - edit, protect and share html');
		$oMap->addScript( 'static/js/jquery.editable.min.js' );
		$oMap->addScript( 'static/js/default.min.js' );
	}
	if ( stristr($oMap->getPath(), 'showindex') !== false) {
		$oMap->addScript('static/js/display-links.min.js' );
	}
}
