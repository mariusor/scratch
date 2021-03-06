<?php
namespace littrme\littr\application\processors;

use vsc\application\processors\ProcessorA;
use vsc\infrastructure\StringUtils;
use vsc\infrastructure\urls\UrlParserA;
use vsc\presentation\requests\HttpRequestA;
use vsc\presentation\responses\ExceptionResponseRedirect;

class RedirectToRand extends ProcessorA {
	public function __construct() {
	}

	public function init () {}

	public function handleRequest (HttpRequestA $oHttpRequest) {
		$oUrl = UrlParserA::getCurrentUrl();
		$oUrl->addPath(StringUtils::baseEncode(intval(microtime(true) * 10000)));
		$oUrl->setQuery(null);

		throw new ExceptionResponseRedirect($oUrl->getUrl(3));
	}
}
