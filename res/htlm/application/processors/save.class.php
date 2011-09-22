<?php
import ('domain/models');

class save extends vscProcessorA {
	public function __construct() {
	}

	public function init () {
	}

	public function handleRequest (vscHttpRequestA $oHttpRequest) {
		$oModel = new vscArrayModel();
		$saveObject = new contentTable();
		$oUri = new vscUrlRWParser();

		if ($oHttpRequest->isPost()) {
			$sRefererUri = $oHttpRequest->getHttpReferer();
			if (!empty ($sRefererUri)) {
				$oUri->setUrl($sRefererUri);
			}
			$sIncomingUri = $oHttpRequest->getVar('uri');
			if (!empty($sIncomingUri)) {
				$oUri->setUrl($sIncomingUri);
			}

			$saveObject->setUri($oUri->getPath());
			$saveObject->setData($oHttpRequest->getVar('content'));

			try {
				if (!$saveObject->hasSecret($oUri->getPath()) || $saveObject->checkToken ($oUri->getPath(), $oHttpRequest->getVar('auth_token'))) {
					if ($saveObject->saveData ()) {
						$oModel->status = 'ok';
					} else {
						$oModel->status = 'nok';
						$oModel->message = $saveObject->getConnection()->getError();
					}
				} else {
					$oModel->status = 'nok';
					$oModel->message = 'You need to provide the correct key in order to save';
				}
			} catch (vscException $e) {
				$oModel->status = 'nok';
				$oModel->message = $e->getMessage();
			}
		} else {
			$oModel->status = 'nok';
		}
		return $oModel;
	}
}