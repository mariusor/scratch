<?php
import ('domain/models');

abstract class tsSimpleProcessor extends vscProcessorA {

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
			if ($e->getCode() > 500) {
				$oModel->setPageTitle ('Internal error');
			} elseif ($e->getCode() > 400) {
				$oModel->setPageTitle ('User error');
			}
			
			if (vsc::getEnv()->isDevelopment()) {
				$sContent = $e->getMessage() . vsc::nl() . '<pre class="backtrace">'. $e->getTraceAsString() .'</pre>';
			} else {
				$sContent = $e->getMessage();
			}
			$oModel->setPageContent($sContent);
			$this->getMap()->setResponse($oResponse);
			$this->getMap()->setTemplate('error.php');
		}

		return $oModel;
	}
}
