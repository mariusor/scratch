<?php
/**
 * @package vsc_application
 * @subpackage controllers
 * @author marius orcsik <marius@habarnam.ro>
 * @date 2011.02.21
 */

class vscStaticFileView extends vscPlainTextView {
	public function getMTime () {
		return $this->getModel()->getMTime();
	}

	public function fetch ($includePath) {
		$oModel = $this->getModel();
		/* @var $oModel vscStaticFileModel */
		return $oModel->getFileContent();
	}
}
