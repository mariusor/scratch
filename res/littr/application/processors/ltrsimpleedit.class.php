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

		$oUri = new vscUrlRWParser();
		$oUri->setUrl($oUri->getCompleteUri(true));

		$sCurrentUri = urldecode($oUri->getPath());
		$o = new contentTable();

		$oModel = new vscArrayModel();
		$oModel->uri = $o->uri;

		$o->loadContent ($sCurrentUri);
// 		d ($o);

		$oModel->content	= $o->content;
		$oModel->created	= $o->created;
		$oModel->modified	= $o->modified;
		$oModel->secret		= $o->secret;

		$oModel->help = "Tab indent, Shift+Tab outdent, Ctrl+B bold, Ctrl+I italic, Ctrl+L insert a link, Ctrl+G insert an image";

		return $oModel;
	}
}
