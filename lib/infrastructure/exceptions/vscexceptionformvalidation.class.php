<?php
/**
 *
 * Form Validation exception
 * @author marius orcsik <marius@habarnam.ro>
 * @date 2011.03.26
 *
 */
class vscExceptionFormValidation extends vscExceptionDomain {
	private $aFormErrors	= array();
	private $aFormWarnings	= array();

	private $bStopOnWarning = true;

	public function getFormErrors () {
		return $this->aFormErrors;
	}

	public function setFormErrors ($aIncomming) {
		throw new vscExceptionUnimplemented(__METHOD__.' not implemented in ' . __FILE__);
	}

	public function getFormWarnings () {
		return $this->aFormWarnings;
	}

	public function setFormWarnings ($aIncomming) {
		throw new vscExceptionUnimplemented(__METHOD__.' not implemented in ' . __FILE__);
	}

	public function getStopOnWarning () {
		return $this->bStopOnWarning;
	}

	public function setStopOnWarning ($bStop) {
		$this->bStopOnWarning = (bool)$bStop;
	}


}