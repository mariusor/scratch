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

		$oRandUrl = new vscUrlRWParser();
		$sStr = base_encode(intval(microtime(true) * 10000));
		$oRandUrl->addPath($sStr);

		$oUri = new vscUrlRWParser();
		$oUri->setUrl($oUri->getCompleteUri(true));

		$o = new contentTable();
		$o->loadContent ($oUri->getPath());

		$oModel = new vscArrayModel();
		$oModel->uri = $o->uri;
		$oModel->content = $o->content;
		$oModel->created = $o->created;
		$oModel->modified = $o->modified;
		if ($o->uri == '/') {
			$oModel->content = str_replace('<!--{RAND_URL}-->', $oRandUrl->getCompleteUri(true), $o->content);
		}
		$oModel->help = "Tab indent, Shift+Tab outdent, Ctrl+B bold, Ctrl+I italic, Ctrl+L insert a link, Ctrl+G insert an image";

		return $oModel;
	}
}
