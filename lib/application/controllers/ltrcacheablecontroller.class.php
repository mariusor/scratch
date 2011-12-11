<?php
/**
 * @package vsc_application
 * @subpackage controllers
 * @author marius orcsik <marius@habarnam.ro>
 * @date 2011.02.21
 */
class ltrCacheableController extends vscHtml5Controller {

	/**
	 * @param vscHttpRequestA $oRequest
	 * @param vscProcessorA $oProcessor
	 * @param vscViewA $oView
	 * @return vscHttpResponseA
	 */
	public function getResponse (vscHttpRequestA $oRequest, $oProcessor = null) {
		$oResponse = parent::getResponse($oRequest, $oProcessor);

		$iExpireTime = 604800; // one week
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

		// if the last modified date + max-age is lower than the current date we need to extend it with $iExpireTime
		$iLastModified = strtotime($this->getView()->getModel()->modified);
		if ($iNow > $iExpireTime + $iLastModified) {
			$iExpireDate = $iExpireTime + ($iNow - $iLastModified);
		}

		$oResponse->setCacheControl ('max-age='. $iExpireDate . ', must-revalidate');
		$oResponse->setExpires (strftime('%a, %d %B %Y %T GMT', $iNow + $iExpireTime));

		return $oResponse;
	}
}