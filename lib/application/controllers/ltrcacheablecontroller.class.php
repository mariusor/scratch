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

		$iLastModified = strtotime($this->getView()->getModel()->modified);

		$oResponse->setCacheControl ('max-age='. $iExpireTime . ', must-revalidate');
		$oResponse->setExpires(strftime('%a, %d %b %Y %T GMT', $iLastModified + $iExpireTime));

		$oResponse->setLastModified(strftime('%a, %d %b %Y %T GMT', $iLastModified));

		return $oResponse;
	}
}