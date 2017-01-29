<?php
use vsc\infrastructure\vsc;
use vsc\infrastructure\urls\UrlParserA;
use vsc\presentation\responses\HttpResponse;
use vsc\application\sitemaps\ErrorProcessorMap;
use vsc\application\sitemaps\ErrorControllerMap;
use vsc\application\processors\ErrorProcessor;
use vsc\application\controllers\Html5Controller;
use vsc\presentation\requests\HttpRequestA;

$iStart		= microtime(true);
$sContent 	= '';
ob_start ();

try {

	include ('../config.inc.php');

	echo \littrme\getErrorHeaderOutput(); // in the case of a fatal error we have this as fallback
	ob_start ();

	// here be dragons
	/* @var  \vsc\application\dispatchers\RwDispatcher $oDispatcher */
	$oDispatcher = vsc::getEnv()->getDispatcher();
	// here definitely should be a factory
	$oRequest = vsc::getEnv()->getHttpRequest();

	if(!UrlParserA::hasGoodTermination($oRequest->getUri())) {
		// fixing(?) urls which don't have an ending slash
		// or a filename.ext termination
		$oResponse = new HttpResponse();
		$oResponse->setStatus(301); // 301 permanently moved
		$oResponse->setLocation(UrlParserA::getCurrentUrl());

		// output the response
		$sContent = $oResponse->getOutput();
		ob_end_clean();
		echo $sContent;
		exit();
	}

	// this should be moved to vscRequest
	if ($oRequest->getVar('lang')) {
		$sLang = $oRequest->getVar('lang');
		switch ($sLang) {
			case 'ro':
				$sLocale = 'ro_RO';
				break;
			case 'en':
				$sLocale = 'en_US';
				break;
		}
	} else {
		$aLanguages = $oRequest->getHttpAcceptLanguage();
		if (count($aLanguages) > 0) {
			$sLocale = str_replace('-', '_', $aLanguages[0]);
			$sLocale = substr ($sLocale, 0, -2) . strtoupper(substr ($sLocale, -2));
		} else {
			$sLocale = 'en';
		}
	}

	if (!empty($sLocale)) {
		$aCharsets = $oRequest->getHttpAcceptCharset();
		if (in_array('UTF-8',$aCharsets))
			$sLocale .= '.utf8';

		setlocale(LC_ALL, $sLocale);
	}

	// load the sitemap
	$oDispatcher->loadSiteMap (LOCAL_RES_PATH . 'map.php');

	/* @var \vsc\application\processors\ProcessorA $oProcessor */
	// get the controller
	$oProcessor			= $oDispatcher->getProcessController ($oRequest);

	/* @var \vsc\application\controllers\FrontControllerA $oFrontController */
	// get the front controller
	$oFrontController 	= $oDispatcher->getFrontController ();

	// get the response
} catch (Exception $e) {
	$oMap = new ErrorProcessorMap();
	$oMap->setTemplate('error.php');

	$oCtrlMap = new ErrorControllerMap();
	$oCtrlMap->setTemplatePath(LOCAL_RES_PATH . 'littr/templates');

	$oProcessor = new ErrorProcessor($e);
	$oProcessor->setMap($oMap);

	$oFrontController = new Html5Controller();
	$oFrontController->setMap($oCtrlMap);
}

try {
	if (!HttpRequestA::isValid($oRequest)) {
		$oRequest = vsc::getEnv()->getHttpRequest();
	}

	$aErrors = \vsc\cleanBuffers();
	$oResponse			= $oFrontController->getResponse ($oRequest, $oProcessor);

	// output the response
	$sContent = $oResponse->getOutput();
} catch (Exception $e) {
	\vsc\_e ($e);
}

echo $sContent;

