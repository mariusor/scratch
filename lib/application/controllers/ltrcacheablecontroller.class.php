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

		$oResponse->setETag(substr(sha1(serialize($this->getView()->getModel())),0,8));
		if (is_null($this->getView()->getModel()->created)) {
			$oResponse->setCacheControl ('no-cache, no-store, must-revalidate');
		} else {
			$iExpireTime = 604800; // one week
			$iNow = time();
			$iLastModified = strtotime($this->getView()->getModel()->modified);

			if (
				($oRequest->getIfNoneMatch()
				&& ($oRequest->getIfNoneMatch() == '"'.$oResponse->getETag().'"'))
				|| ($oRequest->getIfModifiedSince()
				&& ($iLastModified > strtotime($oRequest->getIfModifiedSince())))
			) {
				$oResponse->setStatus(304);
			} else {
				$oResponse->setCacheControl ('public, max-age='. $iExpireTime);
				$oResponse->setExpires(strftime('%a, %d %b %Y %T GMT', max($iLastModified, $iNow) + $iExpireTime));

				$oResponse->setLastModified(strftime('%a, %d %b %Y %T GMT', $iLastModified));
			}
		}
		return $oResponse;
	}
}