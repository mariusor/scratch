<?php
import ('domain/models');
class ltrSimpleEdit extends ltrProcessor {
	protected $aLocalVars = array ('page' => null);

	public function __construct() {}

	public function init () {}

	public function handlePost (vscHttpRequestA $oHttpRequest) { }
	public function handleGet (vscHttpRequestA $oHttpRequest) {
		if (empty($this->aLocalVars['page'])) {
			$this->aLocalVars['page'] = 'index';
		}

		$aGetVars	= $oHttpRequest->getGetVars();
		$aGetKeys	= array_keys ($aGetVars);
		$sAction	= array_shift ($aGetKeys);

		$oUri = new vscUrlRWParser(vscUrlRWParser::getCurrentUrl());

		$sCurrentUri = urldecode($oUri->getPath());
		$oModel = new ltrEditModel($sCurrentUri);

		return $oModel;
	}
}
