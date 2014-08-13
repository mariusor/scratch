<?php
namespace littrme\application\processors;

use vsc\application\processors\ProcessorA;
use vsc\domain\models\EmptyModel;
use vsc\infrastructure\vsc;
use vsc\presentation\requests\HttpRequestA;
use vsc\presentation\responses\ExceptionResponseError;
use vsc\presentation\responses\HttpResponse;

abstract class Processor extends ProcessorA {

	abstract function handleGet(HttpRequestA $oHttpRequest);
	abstract function handlePost(HttpRequestA $oHttpRequest);

	public function handleRequest (HttpRequestA $oHttpRequest) {
		try {
			if ($oHttpRequest->isGet()) {
				return $this->handleGet($oHttpRequest);
			} elseif ($oHttpRequest->isPost())  {
				return $this->handlePost($oHttpRequest);
			} else {
				throw new ExceptionResponseError('Method not allowed', 405);
			}
		} catch (\ErrorException $e) {
			$oResponse = new HttpResponse();
			if (ExceptionResponseError::isValid($e)) {
				$oResponse->setStatus($e->getCode());
			}

			$oModel = new EmptyModel();
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
