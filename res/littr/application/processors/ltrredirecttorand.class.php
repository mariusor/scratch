<?php
class ltrRedirectToRand extends vscProcessorA {
	public function __construct() {
	}

	public function init () {}

	public function handleRequest (vscHttpRequestA $oHttpRequest) {
		$oUrl = new vscUrlRWParser();
		$sStr = base_encode(intval(microtime(true) * 10000));

		$oCurUri = new vscUrlRWParser();
		$oCurUri->setUrl($oCurUri->getCompleteUri(true));

		$sCurrentUri = urldecode($oCurUri->getPath());
		$oUrl->addPath($sStr);

		throw new vscExceptionResponseRedirect($oUrl->getCompleteUri(true));
	}
}