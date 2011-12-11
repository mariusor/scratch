<?php
import ('domain/models');
class simpleEdit extends tsSimpleProcessor {
	protected $aLocalVars = array ('page' => null);

	public function __construct() {}

	public function init () {}

	public function handlePost (vscHttpRequestA $oHttpRequest) { }
	public function handleGet (vscHttpRequestA $oHttpRequest) {
		if (empty($this->aLocalVars['page'])) {
			$this->aLocalVars['page'] = 'index';
		}

		if ($oHttpRequest->hasGetVar('random')) {
			$oRandUrl = new vscUrlRWParser();
			$sStr = base_encode(intval(microtime(true) * 10000));
			$oRandUrl->addPath($sStr);

			throw new vscExceptionResponseRedirect($oRandUrl->getCompleteUri(true), 303);
		} else {
			$oUri = new vscUrlRWParser();
			$oUri->setUrl($oUri->getCompleteUri(true));

			$o = new contentTable();
			$o->loadContent (urldecode($oUri->getPath()));

			$oModel = new vscArrayModel();
			$oModel->uri = $o->uri;
			$oModel->content = $o->content;
			$oModel->created = $o->created;
			$oModel->modified = $o->modified;

			$oModel->help = "Tab indent, Shift+Tab outdent, Ctrl+B bold, Ctrl+I italic, Ctrl+L insert a link, Ctrl+G insert an image";

			return $oModel;
		}
	}
}
