<?php
import ('domain/models');

abstract class tsSimpleProcessor extends vscProcessorA {
// 	public function init () {
// 		$this->getMap()->setTitle ('Blog test');
// 	}

	abstract function handleGet(vscHttpRequestA $oHttpRequest);
	abstract function handlePost(vscHttpRequestA $oHttpRequest);

	public function handleRequest (vscHttpRequestA $oHttpRequest) {
		try {
			if ($oHttpRequest->isGet()) {
				return $this->handleGet($oHttpRequest);
			} elseif ($oHttpRequest->isPost())  {
				return $this->handlePost($oHttpRequest);
			} else {
				throw new vscExceptionResponseError('Method not allowed', 405);
			}
		} catch (Exception $e) {
			$oResponse = new vscHttpClientError();
			if (vscExceptionResponseError::isValid($e)) {
				$oResponse->setStatus($e->getCode());
			}

			$oModel = new vscEmptyModel();
			$oModel->setPageTitle ('Internal error');
			$oModel->setPageContent($e->getMessage() . vsc::nl() . '<pre>'.$e->getTraceAsString().'</pre>');

			$this->getMap()->setResponse($oResponse);
			$this->getMap()->setTemplate('error.php');
		}

		return $oModel;
	}
}
