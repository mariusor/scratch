<?php
import ('processors');
class userEdit extends simpleEdit {
	protected $aLocalVars = array ('user' => null, 'page' => null);

	public function __construct() {}

	public function init () {}

	public function handleRequest (vscHttpRequestA $oHttpRequest) {
		return parent::handleRequest($oHttpRequest);
	}
}