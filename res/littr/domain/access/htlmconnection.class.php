<?php
class htlmConnection {
	const SERVER = DB_HOST;
	const USER = DB_USER;
	const PASSWORD = DB_PASS;
	const DATABASE = DB_NAME;

	private $connection;

	public function getErrorCode () {
		if (!is_null($this->connection->connect_error)) {
			return $this->connection->connect_errno;
		} else {
			return $this->connection->errno;
		}
	}

	public function getError () {
		if (!is_null($this->connection->connect_error)) {
			return $this->connection->connect_error;
		} else {
			return $this->connection->error;
		}
	}

	public function __construct () {
		$this->connection = new mysqli();
	}

	public function __destruct () {
		if (($this->connection instanceof mysqli) && is_null($this->connection->connect_errno)) {
			$this->connection->close();
		}
	}

	public function connect () {
		$this->connection->connect(self::SERVER, self::USER, self::PASSWORD, self::DATABASE);
	}

	public function close () {
		return $this->connection->close();
	}

	public function escape ($sParam) {
		if (!($this->connection instanceof mysqli) && is_null($this->connection->errno)) {
			return $sParam;
		}
		if (is_null($sParam)) {
			return 'NULL';
		} elseif (is_numeric($sParam)) {
			return $sParam;
		} elseif (is_string($sParam)) {
			return "'" . $this->connection->real_escape_string($sParam) . "'";
		}
	}

	/**
	 * @param string $sSql
	 * @param array $aParams
	 * @return mysqli_result
	 */
	public function query ($sSql, $aParams = null) {
		if (is_array($aParams)) {
			$iAnonParamCount = substr_count($sSql, '?');
			if ($iAnonParamCount == count ($aParams)) {
				foreach ($aParams as $sKey => $sParam) {
					if (is_integer($sKey)) {
						$aReplace[] = '?';
						$aValues[] = $this->escape($sParam);
					}
				}
			}
			$i = preg_match_all('/:(\w+)/', $sSql, $aNamedParams);
			foreach ($aNamedParams[1] as $iKey => $sKey) {
				$aReplace[$iKey] = $aNamedParams[0][$iKey];
				$aValues[$iKey] = $this->escape($aParams[$sKey]);
			}
			$sSql = str_replace($aReplace, $aValues, $sSql);
		}

		$oRes = $this->connection->query($sSql);
		if ($oRes instanceof mysqli_result) {
			return $oRes;
		} else {
			return new vscNull();
		}
	}
}