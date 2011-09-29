<?php
import ('domain/models');

class check extends vscProcessorA {

	public function __construct() {}

	public function init () {}

	public function getModel ($oHttpRequest) {
		$oModel = new vscArrayModel();
		$oUri = new vscUrlRWParser();

		$sRefererUri = $oHttpRequest->getHttpReferer();
		if (!empty ($sRefererUri)) {
			$oUri->setUrl($sRefererUri);
		}
		$sIncomingUri = $oHttpRequest->getVar('uri');
		if (!empty($sIncomingUri)) {
			$oUri->setUrl($sIncomingUri);
		}

		$oModel->uri		= $oUri->getPath();
		if ($oHttpRequest->getVar('key') == '' || stristr($oModel->uri, '/reddit') !== false) {
			$oModel->key = null;
		} else {
			$oModel->key = $oHttpRequest->getVar('key');
		}
		$oModel->auth_token	= $oHttpRequest->getVar('auth_token');
		$oModel->action		= $oHttpRequest->getVar('action');
		return $oModel;
	}

	public function handleSave ($oModel, $oHttpRequest) {
		$saveObject = new contentTable();
		$sContent = $oHttpRequest->getVar('content');
		if (strlen($oModel->uri) >= 254) {
			$oModel->uri = substr ($oModel->uri, 0, 254) . '/';
		}

		$saveObject->setUri($oModel->uri);
		$saveObject->setData($sContent);

		try {
			if (!$saveObject->hasSecret($oModel->uri) || $saveObject->checkToken ($oModel->uri, $oHttpRequest->getVar('auth_token'))) {
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
		return $oModel;
	}

	public function handleCheck ($oModel) {
		$oTable = new contentTable();
		if ($oTable->hasSecret ($oModel->uri)) {
			if ($oModel->auth_token != '' && $oTable->checkToken( $oModel->uri, $oModel->auth_token )) {
				$oModel->status = 'ok';
			} elseif ($oModel->key != '' && $oTable->checkKey ( $oModel->uri, $oModel->key )) {
				$oModel->status 		= 'ok';
				$oModel->auth_token		= $oTable->getAuthenticationToken($oModel->uri);
			} else {
				$oModel->status = 'ko';
			}
		} else {
			$oModel->status = 'ok';
			$oModel->auth_token = null;
		}

		return $oModel;
	}

	public function handleUpdate ($oModel) {
		$oTable = new contentTable();
		$oModel = $this->handleCheck ($oModel);

		if ($oModel->status = 'ok') {
			$oTable->updateSecret ($oModel->uri, $oModel->key);

			$oModel->auth_token = $oTable->getAuthenticationToken($oModel->uri, $oModel->key);
		}

		return $oModel;
	}

	public function handleRequest (vscHttpRequestA $oHttpRequest) {
		$oModel = $this->getModel($oHttpRequest);
		if ($oHttpRequest->isPost()) {
			if ($oModel->action == 'update') {
				return $this->handleUpdate ($oModel);
			} elseif ($oModel->action == 'save') {
				return $this->handleSave ($oModel, $oHttpRequest);
			} else {
				return $this->handleCheck ($oModel);
			}
		} else {
			return $this->handleCheck($oModel);
		}
	}
}