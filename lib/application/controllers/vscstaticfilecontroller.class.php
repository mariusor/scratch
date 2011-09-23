<?php
/**
 * @package vsc_application
 * @subpackage controllers
 * @author marius orcsik <marius@habarnam.ro>
 * @date 2011.02.21
 */
class vscStaticFileController extends vscPlainTextController {

	/**
	 * @param vscHttpRequestA $oRequest
	 * @param vscProcessorA $oProcessor
	 * @param vscViewA $oView
	 * @return vscHttpResponseA
	 */
	public function getResponse (vscHttpRequestA $oRequest, $oProcessor = null) {
		$oResponse = parent::getResponse($oRequest, $oProcessor);

		$iExpireTime = 86400;
		$iNow = time();

		// checking if the resource has not been modified so the user agent can serve from cache
		if (
			($oRequest->getIfNoneMatch()
			&& ($oRequest->getIfNoneMatch() == '"'.$oResponse->getETag().'"'))
			|| ($oRequest->getIfModifiedSince()
			 && (strtotime($oRequest->getIfModifiedSince()) > $iNow + $iExpireTime))
		) {
			$oResponse->setStatus(304);
			$oResponse->setContentLength(0);
		}

		// if the last modified date + max-age is lower than the current date we need to extend it with one more day
		if ($iNow > $iExpireTime + $this->getMTime()) {
			$iExpireTime += $iNow - $this->getMTime();
		}

		$oResponse->setCacheControl ('max-age='. $iExpireTime . ', must-revalidate');

		return $oResponse;
	}
}