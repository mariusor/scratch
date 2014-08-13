<?php
namespace littrme\littr\application\processors;

use vsc\application\processors\ProcessorA;
use vsc\infrastructure\urls\UrlRWParser;
use vsc\presentation\requests\HttpRequestA;
use vsc\presentation\responses\ExceptionResponseRedirect;
use vsc\infrastructure\String;

class RedirectToRand extends ProcessorA {
	public function __construct() {
	}

	public function init () {}

	public function handleRequest (HttpRequestA $oHttpRequest) {
		$oUrl = new UrlRWParser();
		$oUrl->addPath(String::baseEncode(intval(microtime(true) * 10000)));
		$oUrl->setQueryParameters(null);

		throw new ExceptionResponseRedirect($oUrl->getCompleteUri(true));
	}
}