<?php
use vsc\infrastructure\vsc;

/* @var \vsc\application\sitemaps\RwSiteMap $this */
$oModuleMap = $this->getCurrentModuleMap();
// this will break if the current map is the first loaded
$oModuleMap->setMainTemplatePath('templates');
$oModuleMap->setMainTemplate('main.php');
$sCurPath = $oModuleMap->getModulePath();

// main components
$oModuleMap->setTemplatePath ('templates');
$oModuleMap->addStyle ('static/css/style.css');

// setting the main template path to our templates folder
$oCtrlMap = $oModuleMap->mapController ('.*', \vsc\application\controllers\Html5Controller::class);
$oCtrlMap->setView (\littrme\presentation\views\View::class);
$oCtrlMap->setMainTemplatePath ('templates');
$oCtrlMap->setMainTemplate ('master.php');

if ( vsc::getEnv()->getHttpRequest()->isPost() ) {
	$oMap = $this->map ('(\w*)/?' , \littrme\littr\application\processors\Check::class);
	$oMap->setTemplate('check.php');
	$oSaveCtrlMap = $oMap->mapController('.*', \vsc\application\controllers\JsonController::class);
	$oSaveCtrlMap->setView (\vsc\presentation\views\JsonView::class);
} else {
	// we do this ugly thing as the @var UrlRwDispatcher doesn't know about GET variables
	if ( vsc::getEnv()->getHttpRequest()->hasGetVars() ) {
		if ( vsc::getEnv()->getHttpRequest()->hasGetVar ('show-index') ) {
			$oMap = $this->map ('(.*)/?\Z', \littrme\littr\application\processors\ShowIndex::class);
			$oMap->setTemplate ('showindex.php');
			$oMap->setTitle ('Littr - listing of child pages');
		} elseif ( vsc::getEnv()->getHttpRequest()->hasGetVar ('random') ) {
			$oMap = $this->map ('(.*)/?\Z', \littrme\littr\application\processors\RedirectToRand::class);
		} else {
			$oMap = $this->map ('(.*)/?\Z', \littrme\littr\application\processors\SimpleEdit::class);
		}
	} else {
		$oMap = $this->map ('(.*)/?\Z', littrme\littr\application\processors\SimpleEdit::class);
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
