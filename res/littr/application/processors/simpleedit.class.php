<?php
import ('domain/models');
class simpleEdit extends vscProcessorA {
	protected $aLocalVars = array ('page' => null);

	public function __construct() {}

	public function init () {}

	public function handleRequest (vscHttpRequestA $oHttpRequest) {
		if (empty($this->aLocalVars['page'])) {
			$this->aLocalVars['page'] = 'index';
		}

		$oUrl = $oHttpRequest->getUriObject();

		$o = new contentTable();
		$o->loadData ($oUrl->getPath());

		return $o;
	}
}