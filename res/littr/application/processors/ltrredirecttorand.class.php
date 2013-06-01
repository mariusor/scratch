<?php
class ltrRedirectToRand extends vscProcessorA {
	public function __construct() {
	}

	public function init () {}

	public function handleRequest (vscHttpRequestA $oHttpRequest) {
		$oUrl = new vscUrlRWParser();
		$oUrl->addPath(vscString::baseEncode(intval(microtime(true) * 10000)));
		$oUrl->setQueryParameters(null);

		throw new vscExceptionResponseRedirect($oUrl->getCompleteUri(true));
	}
}