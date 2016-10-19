<?php
namespace littrme\application\processors;

use vsc\application\processors\ProcessorA;
use vsc\domain\models\ErrorModel;
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

			$oModel = new ErrorModel($e);
			$this->getMap()->setResponse($oResponse);
			$this->getMap()->setTemplate('error.php');
		}

		return $oModel;
	}
}
