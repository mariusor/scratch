<?php
namespace littrme\littr\domain\models;

use littrme\domain\access\SqlAccessFactory;
use littrme\infrastructure\mmCrypter;
use orm\domain\domain\ExceptionDomain;
use vsc\domain\models\ModelA;
use vsc\infrastructure\vsc;

class ContentTable extends ModelA {
	public $uri;
	public $content;
	public $created = null;
	public $modified = null;
	public $secret = null;

	private $connection;

	public function __construct () {
		$this->content		= 'Welcome! This page is currently empty.<br/> You can edit it and it will be saved automatically.';
		$this->created		= null;
		$this->modified		= null;

		try {
			$this->connection = SqlAccessFactory::getConnection();
			if (!$this->connection->isConnected()) {
				try {
					$this->connection->connect();

				} catch (\Exception $e) {
					//
				}
			}
		} catch (\Exception $e) {
			if (!vsc::getEnv()->isDevelopment()) {
				throw new ExceptionDomain('Could not connect', 500);
			} else {
				throw $e;
			}
		} catch (\ErrorException $e) {
			if (!vsc::getEnv()->isDevelopment()) {
				throw new ExceptionDomain('Could not connect', 500);
			} else {
				throw $e;
			}
		}
	}

	public function validResource($oResource) {
		return $this->getConnection()->validResource($oResource);
	}

	public function getConnection() {
		return $this->connection;
	}

	public function setContent ($sContent) {
		$this->content = $sContent;
	}

	public function getContent() {
		return $this->content;
	}

	public function setUri ($sUri) {
		$this->uri = $sUri;
	}

	public function getUri () {
		return $this->uri;
	}

	public function getSecret ($sUri) {
		$query = 'select secret from data where uri = :uri';
		return $this->query($query, array('uri' => $sUri));
	}

	public function getChildrenUris ($sUri) {
		$sUri = $sUri . '%';
		$query = 'select uri, length(content) as size, date_part(\'epoch\',modified) as modified, case when length(secret) is null then 0 else 1 end hassecret from data where uri like :uri order by length(uri) asc';

		$oResult = $this->query($query, array('uri' => $sUri));
		$iRows = pg_num_rows($oResult);
		$aReturn = array();
		for ($i = 0 ; $i < $iRows ; $i++ ) {
			$aReturn[$i] = pg_fetch_assoc($oResult);
		}

		return $aReturn;
	}

	public function hasSecret ($sUri) {
		try {
			$this->getOne($sUri);
			$aResult = $this->getConnection()->getAssoc();
			return !is_null($aResult['secret']);
		} catch (\Exception $e) {
			return false;
		}
		return false;
	}

	public function getAuthenticationToken ($sUri, $sKey = null) {
		try {
			$this->getOne($sUri);
			$aResult = $this->getConnection()->getAssoc();
		} catch (\Exception $e) {
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

	public function query ($sSql, $aParams) {
		if (is_array($aParams)) {
			$iAnonParamCount = substr_count($sSql, '?');
			if ($iAnonParamCount == count ($aParams)) {
				foreach ($aParams as $sKey => $sParam) {
					if (is_integer($sKey)) {
						$aReplace[] = '?';
						$aValues[] = $this->connection->escape($sParam);
					}
				}
			}
			$i = preg_match_all('/:(\w+)/', $sSql, $aNamedParams);
			foreach ($aNamedParams[1] as $iKey => $sKey) {
				$aReplace[$iKey] = $aNamedParams[0][$iKey];
				$aValues[$iKey] = $this->connection->escape($aParams[$sKey]);
			}
			$sSql = str_replace($aReplace, $aValues, $sSql);
		}
// 		d ($sSql);

		return $this->connection->query($sSql);
	}

	public function getOne ($sUri) {
		$query = 'select * from data where uri = :uri';

		return $this->query($query, array('uri' => $sUri));
	}

	public function loadContent ($sUri) {
		$iNumRows = $this->getOne($sUri);
		if ($iNumRows > 0) {
			$aTemp = $this->getConnection()->getAssoc();

			if (is_array ($aTemp) && count ($aTemp) > 0) {
				$this->uri			= $aTemp['uri'];
				$this->content		= $aTemp['content'];
				$this->created		= $aTemp['created'];
				$this->modified		= $aTemp['modified'];
				$this->secret		= $aTemp['secret'];

				return true;
			}
		}
		$this->uri 			= $sUri;
		$this->secret 		= mmCrypter::hash(' ');
		return false;
	}

	public function updateSecret ($sUri, $sKey) {
		if ($this->getOne($sUri) > 0) {
			$query = 'update data set secret = :secret where uri = :uri';
			if (!is_null($sKey)) {
				$oCrypt = new mmCrypter();
				$sKey = $oCrypt->hash($sKey);
			} else {
				$sKey = null;
			}
			return $this->query($query, array('secret' => $sKey, 'uri' => $sUri));
		} else {
			return false;
		}
	}

	public function checkKey ($sUri, $sKey) {
		try {
			$this->getOne($sUri);
			$aResult = $this->getConnection()->getAssoc();
		} catch (\ErrorException $e){
			$aResult = array();
			$aResult['secret'] = null;
		}

		return mmCrypter::check ($sKey, $aResult['secret']);
	}

	public function checkToken ($sUri, $sToken) {
		try {
			$this->getOne($sUri);
			$aResult = $this->getConnection()->getAssoc();
		} catch (\ErrorException $e){
			$aResult = array('secret' => null);
		}
		return mmCrypter::check ('#' . $aResult['secret'] . '#' . $sUri . '#', $sToken);
	}

	public function update () {
		$sUpdateSql = 'update data set content = :content where uri = :uri';

		$aParams = array(
			'uri' => $this->uri,
			'content' => $this->content,
		);

		return $this->query($sUpdateSql, $aParams);
	}

	public function insert () {
		$sInsertSql = 'insert into data (uri, content) values (:uri,:content)';

		$aParams = array(
			'uri' => $this->uri,
			'content' => $this->content
		);

		return $this->query($sInsertSql, $aParams);
	}

	public function uriExists ($sUri) {
		$sCheckUriSql = 'select count(uri) as count from data where uri = :uri';
		$this->query($sCheckUriSql, array ('uri' => $sUri));
		$aResult = $this->getConnection()->getAssoc();

		return ($aResult['count'] > 0);
	}

	public function save () {
		if ($this->uriExists($this->uri)) {
			$o = $this->update();
		} else {
			$o = $this->insert();
		}
		$this->loadContent($this->uri);
		return $o;
	}

	public function delete () {
		$sDeleteSql = 'delete from data where uri = :uri';

		$aParams = array(
			'uri' => $this->uri,
		);

		return $this->query($sDeleteSql, $aParams);
	}
}