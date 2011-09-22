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
		if ($oHttpRequest->getVar('key') == '') {
			$oModel->key = null;
		} else {
			$oModel->key = $oHttpRequest->getVar('key');
		}
		$oModel->auth_token	= $oHttpRequest->getVar('auth_token');
		$oModel->action		= $oHttpRequest->getVar('action');
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
				$oModel->status = 'nok';
			}
		} else {
			$oModel->status = 'ok';
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
		$oModel = new vscArrayModel();
		$oTable = new contentTable();
		$oUri = new vscUrlRWParser();

		$oModel = $this->getModel($oHttpRequest);
		if ($oHttpRequest->isPost()) {
			if ($oModel->action != 'update') {
				return $this->handleCheck($oModel);
			} else {
				return $this->handleUpdate($oModel);
			}
		} else {
			return $this->handleCheck($oModel);
		}
	}
}