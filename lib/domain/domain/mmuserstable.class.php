<?php
// import ('infrastructure');
import (VSC_LIB_PATH . 'domain/domain');
class mmUsersTable extends vscDomainObjectA {
	private $id;
	private $email;
	private $secret;

	protected $sTableName = 'mm_users';

	public function __set ($sIncName, $mValue) {
		if ($sIncName == 'secret') {
			$this->setSecret($mValue);
		} else {
			parent::__set ($sIncName, $mValue);
		}
	}

	function setSecret ($sSecret) {
		$this->secret->setValue( mmCrypter::hash($sSecret));
	}

	function checkSecret ($sIncoming) {
		return mmCrypter::check($sIncoming, $this->secret->getValue());
	}

	public function buildObject () {
		$this->id		= new vscFieldInteger('id');
		$this->id->setAutoIncrement(true);

		$this->setPrimaryKey($this->id);

		$this->email	= new vscFieldText('email');

		$this->addIndex(new vscKeyUnique ($this->email));
		$this->secret	= new vscFieldText('secret');
	}
}