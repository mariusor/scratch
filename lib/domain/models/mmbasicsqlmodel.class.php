<?php
import ('domain/models');
import ('domain/access');
import ('domain/connections');
class mmBasicSqlModel extends vscSQLModelA {
	private $oConnection;

	/**
	 * @var vscSimpleDataAccess
	 */
	private $oAccess;

	private $oDomainObject;

	public function buildObject() {
		$this->setAccess(new vscSimpleDataAccess ());
		$this->getAccess()->setGrammarHelper (vscAccessFactory::getGrammarHelper($this->getAccess()->getConnection()));
	}

	public function setAccess ($oAccess) {
		$this->oAccess = $oAccess;
	}

	/**
	 * @return vscSimpleDataAccess
	 */
	public function getAccess () {
		return $this->oAccess;
	}
}