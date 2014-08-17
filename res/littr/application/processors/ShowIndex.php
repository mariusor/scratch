<?php
namespace littrme\littr\application\processors;

use littrme\application\processors\Processor;
use littrme\littr\domain\models\ContentTable;
use vsc\domain\models\ArrayModel;
use vsc\infrastructure\urls\UrlRWParser;
use vsc\presentation\requests\HttpRequestA;

class ShowIndex extends Processor {
	protected $aLocalVars = array ('uri' => null);

	public function __construct() {}

	public function init () {}

	public function handlePost (HttpRequestA $oHttpRequest) {}

	public function handleGet (HttpRequestA $oHttpRequest) {
		$oUri = new UrlRWParser();
		$oUri->setUrl($oUri->getCompleteUri(true));

		$sCurrentUri = urldecode($oUri->getPath());
		$o = new ContentTable();

		$oModel = new ArrayModel();
		$oModel->uri = $o->uri;
		$aLinks = $o->getChildrenUris($sCurrentUri);
		$iMaxModified = 1;

		foreach ($aLinks as $i => $aData) {
			$iModified = (int)$aData['modified'];
			$iMaxModified = max ($iMaxModified, $iModified);
			$aLinks[$i]['modified'] = $iModified;
			$aLinks[$i]['size'] = $aData['size'];
		}
		$oModel->links		= $aLinks;
		$oModel->modified	= $iMaxModified;

		return $oModel;
	}
}