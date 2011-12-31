<?php
import ('application/processors');
class ltrShowIndex extends ltrProcessor {
	protected $aLocalVars = array ('uri' => null);

	public function __construct() {}

	public function init () {}

	public function handlePost (vscHttpRequestA $oHttpRequest) {}

	public function handleGet (vscHttpRequestA $oHttpRequest) {
		$oUri = new vscUrlRWParser();
		$oUri->setUrl($oUri->getCompleteUri(true));

		$sCurrentUri = urldecode($oUri->getPath());
		$o = new contentTable();

		$oModel = new vscArrayModel();
		$oModel->uri = $o->uri;
		$aLinks = $o->getChildrenUris($sCurrentUri);
		$iMaxModified = 1;

		foreach ($aLinks as $i => $aData) {
			$iModified = (int)$aData['modified'];
			$iMaxModified = max ($iMaxModified, $iModified);
			$aLinks[$i]['modified'] = $iModified;
		}
		$oModel->links		= $aLinks;
		$oModel->modified	= $iMaxModified;

		return $oModel;
	}
}