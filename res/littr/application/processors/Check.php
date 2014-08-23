<?php
namespace littrme\littr\application\processors;

use littrme\littr\domain\models\ContentTable;
use vsc\application\processors\ProcessorA;
use vsc\domain\models\ArrayModel;
use vsc\domain\models\ModelA;
use vsc\infrastructure\urls\UrlRWParser;
use vsc\infrastructure\vsc;
use vsc\presentation\requests\HttpRequestA;

class Check extends ProcessorA {

	public function __construct() {}

	public function init () {}

	/**
	 * @param HttpRequestA $oHttpRequest
	 * @return ArrayModel
	 */
	public function getModel ($oHttpRequest) {
		$oModel = new ArrayModel();
		$oUri = new UrlRWParser();

		$sRefererUri = $oHttpRequest->getHttpReferer();
		if (!empty ($sRefererUri)) {
			$oUri->setUrl($sRefererUri);
		}
		$sIncomingUri = $oHttpRequest->getVar('uri');
		if (!empty($sIncomingUri)) {
			$oUri->setUrl($sIncomingUri);
		}

		$oModel->uri		= urldecode($oUri->getPath());
		if ($oHttpRequest->getVar('key') == '' || $oModel->uri == '/reddit/' || $oModel->uri == '/reddit.com') {
			$oModel->key = null;
		} else {
			$oModel->key = $oHttpRequest->getVar('key');
		}
		$oModel->auth_token	= $oHttpRequest->getVar('auth_token');
		$oModel->action		= $oHttpRequest->getVar('action');
		return $oModel;
	}

	/**
	 * @param ModelA $oModel
	 * @param HttpRequestA $oHttpRequest
	 * @return ModelA
	 */
	public function handleDelete ($oModel, $oHttpRequest) {
		$oDataTable = new ContentTable();
		$sContent = $oHttpRequest->getVar('content');

		if (strlen($oModel->uri) >= 254) {
			$oModel->uri = substr ($oModel->uri, 0, 254) . '/';
		}

		$oDataTable->setUri($oModel->uri);
		if ( strlen($sContent) > 0 ) {
			$oDataTable->setContent($sContent);
		}

		try {
			if (!$oDataTable->hasSecret($oModel->uri) || $oDataTable->checkToken ($oModel->uri, $oHttpRequest->getVar('auth_token'))) {
				if ($oDataTable->delete()) {
					$oModel->status = 'ok';
				} else {
					$oModel->status = 'ko';
					$oModel->message = 'secret key needed';
				}
			}
		} catch (\Exception $e) {
			$oModel->status = 'ko';
			if (vsc::getEnv()->isDevelopment()) {
				$oModel->message = $e->getMessage();
			} else {
				$oModel->message = 'exception triggered';
			}
		}
		return $oModel;
	}

	/**
	 * @param ModelA $oModel
	 * @param HttpRequestA $oHttpRequest
	 * @return ModelA
	 */
	public function handleSave ($oModel, $oHttpRequest) {
		$oDataTable = new ContentTable();
		$sContent = $oHttpRequest->getVar('content');
		if (strlen($oModel->uri) >= 254) {
			$oModel->uri = substr ($oModel->uri, 0, 254) . '/';
		}

		$oDataTable->setUri($oModel->uri);
		$oDataTable->setContent($sContent);

		try {
			if (!$oDataTable->hasSecret($oModel->uri) || $oDataTable->checkToken ($oModel->uri, $oHttpRequest->getVar('auth_token'))) {
				if ($oDataTable->save ()) {
					$oModel->status = 'ok';
					$oModel->modified = strtotime($oDataTable->modified);
				} else {
					$oModel->status = 'ko';
					if (vsc::getEnv()->isDevelopment()) {
						$oModel->message = $oDataTable->getConnection()->getError();
					} else {
						$oModel->message = 'persistence layer error';
					}
				}
			} else {
				$oModel->status = 'ko';
				$oModel->message = 'secret key needed';
			}
		} catch (\Exception $e) {
			$oModel->status = 'ko';
			if (vsc::getEnv()->isDevelopment()) {
				$oModel->message = $e->getMessage();
			} else {
				$oModel->message = 'oops!';
			}
		}
		return $oModel;
	}
	/**
	 * @param ModelA $oModel
	 * @return ModelA
	 */
	public function handleCheck ($oModel) {
		$oTable = new ContentTable();
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

	/**
	 * @param ModelA $oModel
	 * @return ModelA
	 */
	public function handleUpdate ($oModel) {
		$oTable = new ContentTable();
		$oModel = $this->handleCheck ($oModel);

		if ($oModel->status = 'ok') {
			$oTable->updateSecret ($oModel->uri, $oModel->key);

			$oModel->auth_token = $oTable->getAuthenticationToken($oModel->uri, $oModel->key);
		}

		return $oModel;
	}
	/**
	 * @param HttpRequestA $oHttpRequest
	 * @return ModelA
	 */
	public function handleRequest (HttpRequestA $oHttpRequest) {
		$oModel = $this->getModel($oHttpRequest);
		if ($oHttpRequest->isPost()) {
			if ($oModel->action == 'update') {
				return $this->handleUpdate ($oModel);
			} elseif ($oModel->action == 'save') {
				return $this->handleSave ($oModel, $oHttpRequest);
			} elseif ($oModel->action == 'delete') {
				return $this->handleDelete ($oModel, $oHttpRequest);
			} else {
				return $this->handleCheck ($oModel);
			}
		} else {
			return $this->handleCheck($oModel);
		}
	}
}
