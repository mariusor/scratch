<?php
/**
 * @package application
 * @subpackage processors
 * @author marius orcsik <marius@habarnam.ro>
 * @date 2010.09.20
 */
abstract class vscFormProcessorA extends vscProcessorA {
	abstract public function handleGet(vscHttpRequestA $oHttpRequest);

	abstract public function handlePost($oModel, vscHttpRequestA $oHttpRequest);

	abstract public function getFormModel (vscHttpRequestA $oHttpRequest);

	public function handleRequest (vscHttpRequestA $oHttpRequest) {
		if ($oHttpRequest->isGet()) {
			return $this->handleGet($oHttpRequest);
		} elseif ($oHttpRequest->isPost()) {
			$oModel = $this->getFormModel ($oHttpRequest);

			try {
				return $this->handlePost($oModel, $oHttpRequest);
			} catch (vscExceptionFormValidation $e) {
				// todo:
				/**
				 * $oFormErrorModel = new vscFormErrorModel($oModel, $e);
				 */
				return $oModel;
			}
		} else {
			throw new vscExceptionController('This application does not support ' . $_SERVER['REQUEST_METHOD']);
		}
	}
}