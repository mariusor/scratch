<?php
$iStart		= microtime(true);
$sContent 	= '';

ob_start ();
try {
	include ('../config.inc.php');
	echo getErrorHeaderOutput (); // in the case of a fatal error we have this as fallback
	ob_start ();

	// here be dragons
	import ('exceptions');
	import ('application/controllers');

	/* @var $oDispatcher vscRwDispatcher */
	$oDispatcher = vsc::getEnv()->getDispatcher();
	// here definitely should be a factory
	$oRequest = vsc::getEnv()->getHttpRequest();

	if(!vscUrlRWParser::hasGoodTermination($oRequest->getUri())) {
		// fixing(?) urls which don't have an ending slash
		// or a filename.ext termination
		$oResponse = new vscHttpResponse();
		$oResponse->setStatus(301); // 301 permanently moved
		$oResponse->setLocation($oRequest->getUriObject()->getCompleteUri(true));

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

	/* @var $oProcessor vscProcessorA */
	// get the controller
	$oProcessor			= $oDispatcher->getProcessController ($oRequest);

	/* @var $oFrontController vscFrontControllerA */
	// get the front controller
	$oFrontController 	= $oDispatcher->getFrontController ();

	// get the response
} catch (Exception $e) {
	import ('application/processors');
	import ('application/controllers');
	import ('application/sitemaps');
	$oMap = new vscErrorMap();
	$oMap->setTemplate('error.php');

	$oCtrlMap = new vscErrorControllerMap();
	$oCtrlMap->setTemplatePath(LOCAL_RES_PATH . 'littr/templates');

	$oProcessor = new vscErrorProcessor($e);
	$oProcessor->setMap($oMap);

	$oFrontController = new vscHtml5Controller();
	$oFrontController->setMap($oCtrlMap);
}

try {
	$aErrors = cleanBuffers();
	$oResponse			= $oFrontController->getResponse ($oRequest, $oProcessor);

	// output the response
	$sContent = $oResponse->getOutput();
} catch (Exception $e) {
	_e ($e);
}

echo $sContent;

