<?php
import ('domain/domain');
import ('domain/access');
import (LOCAL_LIB_PATH . 'infrastructure');

class contentTable extends vscModelA {
	public $uri;
	public $data;
	public $creation = null;
	public $secret = null;
	public $rand_uri;

	private $connection;

	public function __construct () {
		try {
			$this->connection = new htlmConnection();
			$this->connection->connect();
		} catch (ErrorException $e) {
			if (!vsc::getEnv()->isDevelopment()) {
				throw new vscExceptionDomain('could not connect', 500);
			} else {
				throw $e;
			}
		}
	}

	public function getConnection() {
		return $this->connection;
	}

	public function setData ($sContent) {
		$this->data = $sContent;
	}

	public function getData() {
		return $this->data;
	}

	public function setUri ($sUri) {
		$this->uri = $sUri;
	}

	public function getUri () {
		return $this->uri;
	}

	public function getSecret ($sUri) {
		$query = 'select secret from content where uri = :uri';
		/* @var $oResult MySQLi_Result */
		return $this->connection->query($query, array('uri' => $sUri));
	}

	public function hasSecret ($sUri) {
		try {
			$aResult = $this->getOne($sUri)->fetch_assoc();
			return !is_null($aResult['secret']);
		} catch (vscException $e) {
			return false;
		}
		return false;
	}

	public function getAuthenticationToken ($sUri, $sKey = null) {
		try {
			$aResult = $this->getOne($sUri)->fetch_assoc();
		} catch (vscException $e) {
			$aResult = array('secret' => null);
		}

		if (is_null($sKey)) {
			return mmCrypter::hash('#' . $aResult['secret'] . '#' .  $sUri . '#');
		} else {
			// if we have a key we check it to be correct - maybe not needed
			$sStoredHash = $aResult['secret'];
			if (mmCrypter::check($sKey, $sStoredHash)) {
				return  mmCrypter::hash('#' . $sStoredHash . '#' .  $sUri . '#');
			}
		}
		return false;
	}

	public function getOne ($sUri) {
		$query = 'select * from content where uri = :uri';

		return $this->connection->query($query, array('uri' => $sUri));
	}

	public function loadData ($sUri) {
		$oResult = $this->getOne($sUri);
		if ($oResult instanceof mysqli_result) {
			$oTemp = $oResult->fetch_object();

			if ($oTemp instanceof stdClass) {
				$this->uri 			= $oTemp->uri;
				$this->data 		= $oTemp->data;
				$this->creation 	= $oTemp->creation;
				$this->secret 		= $oTemp->secret;

				return true;
			}
		}
		$this->uri 			= $sUri;
		$this->data 		= 'Welcome! This page is currently empty.<br/> You can edit it and it will be saved automatically.';
		$this->creation 	= null;
		$this->secret 		= 'notnull';

		return false;
	}

	public function updateSecret ($sUri, $sKey) {
		if ($this->getOne($sUri) instanceof mysqli_result) {
			$query = 'update content set secret = :secret where uri = :uri';
			if (!is_null($sKey)) {
				$oCrypt = new mmCrypter();
				$sKey = $oCrypt->hash($sKey);
			} else {
				$sKey = null;
			}
			return $this->connection->query($query, array('secret' => $sKey, 'uri' => $sUri));
		} else {
			return false;
		}
	}

	public function checkKey ($sUri, $sKey) {
		try {
			$aResult = $this->getOne($sUri)->fetch_assoc();
		} catch (ErrorException $e){
			$aResult = array();
			$aResult['secret'] = null;
		}

		return mmCrypter::check ($sKey, $aResult['secret']);
	}

	public function checkToken ($sUri, $sToken) {
		try {
			$aResult = $this->getOne($sUri)->fetch_assoc();
		} catch (ErrorException $e){
			$aResult = array('secret' => null);
		}
		return mmCrypter::check ('#' . $aResult['secret'] . '#' . $sUri . '#', $sToken);
	}

	public function updateData () {
		$sUpdateSql = 'update content set data = :data, creation = :creation where uri = :uri';

		$aParams = array(
			'uri' => $this->uri,
			'data' => $this->data,
			'creation' => $this->creation
		);

		return $this->connection->query($sUpdateSql, $aParams);
	}

	public function insertData () {
		$sInsertSql = 'insert into content set uri = :uri, data = :data';

		$aParams = array(
			'uri' => $this->uri,
			'data' => $this->data,
		);

		return $this->connection->query($sInsertSql, $aParams);
	}

	public function uriExists ($sUri) {
		$sCheckUriSql = 'select count(uri) as count from content where uri = :uri';
		$aResult = $this->connection->query($sCheckUriSql, array ('uri' => $sUri))->fetch_array(MYSQLI_ASSOC);

		return ($aResult['count'] > 0);
	}

	public function saveData () {
		if ($this->uriExists($this->uri)) {
			$o = $this->updateData();
		} else {
			$o = $this->insertData();
		}
		$this->loadData($this->uri);
		return $o;
	}
}