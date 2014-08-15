<?php
namespace littrme\littr\application\processors;

use littrme\littr\domain\models\EditModel;
use vsc\infrastructure\urls\UrlRWParser;
use vsc\infrastructure\vsc;
use vsc\presentation\requests\HttpRequestA;
use littrme\application\processors\Processor;

class SimpleEdit extends Processor {
	protected $aLocalVars = array ('page' => null);

	public function __construct() {}

	public function init () {}

	public function handlePost (HttpRequestA $oHttpRequest) { }
	public function handleGet (HttpRequestA $oHttpRequest) {
		if (empty($this->aLocalVars['page'])) {
			$this->aLocalVars['page'] = 'index';
		}

		$aGetVars	= $oHttpRequest->getGetVars();
		$aGetKeys	= array_keys ($aGetVars);
		$sAction	= array_shift ($aGetKeys);

		$oUri = UrlRWParser::getCurrentUrl();

		$sCurrentUri = urldecode($oUri->getPath());
		$oModel = new EditModel($sCurrentUri);

		return $oModel;
	}
}
