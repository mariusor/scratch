<?php
import ('domain/models');

class diffSave extends vscProcessorA {
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

			$sContent = $oHttpRequest->getVar('content');
			$sUri = $oUri->getPath();

			if (strlen($sUri) >= 254) {
				$sUri = substr ($sUri, 0, 254) . '/';
			}

			$saveObject->setUri($sUri);
			$saveObject->setData($sContent);

			try {
				if (!$saveObject->hasSecret($oUri->getPath()) || $saveObject->checkToken ($oUri->getPath(), $oHttpRequest->getVar('auth_token'))) {
					if ($saveObject->saveData ()) {
						$oModel->status = 'ok';
					} else {
						$oModel->status = 'ko';
						if (vsc::getEnv()->isDevelopment()) {
							$oModel->message = $saveObject->getConnection()->getError();
						} else {
							$oModel->message = 'persistence layer error';
						}
					}
				} else {
					$oModel->status = 'ko';
					$oModel->message = 'secret key needed';
				}
			} catch (vscException $e) {
				$oModel->status = 'ko';
				if (vsc::getEnv()->isDevelopment()) {
					$oModel->message = $e->getMessage();
				} else {
					$oModel->message = 'exception triggered';
				}
			}
		} else {
			$oModel->status = 'ko';
			$oModel->message = 'invalid request type';
		}
		$oModel->status = 'ko';
		$oModel->message = 'Test 123!';
		return $oModel;
	}
}