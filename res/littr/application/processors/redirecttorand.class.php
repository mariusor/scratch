<?php
class redirectToRand extends vscProcessorA {
	public function __construct() {
	}

	public function init () {}

	public function handleRequest (vscHttpRequestA $oHttpRequest) {
		$oUrl = new vscUrlRWParser();
		$sStr = substr(sha1(rand (1,100)), 0, 7);

		$oUrl->addPath($sStr);

		throw new vscExceptionResponseRedirect($oUrl->getCompleteUri(true));
	}
}