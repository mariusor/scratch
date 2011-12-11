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

		$iLastModified = strtotime($this->getView()->getModel()->modified);

		$oResponse->setCacheControl ('max-age='. $iExpireTime . ', must-revalidate');
		$oResponse->setLastModified(strftime('%a, %d %b %Y %T GMT', $iLastModified));
		$oResponse->setExpires(strftime('%a, %d %b %Y %T GMT', $iLastModified + $iExpireTime));

		return $oResponse;
	}
}