<?php
/**
 * Static wrapper for PasswordHash class.
 * 
 * @package mm_infrastructure
 * @author marius orcsik <marius@habarnam.ro>
 * @date 2011.03.23
 */

import ('imported');

class mmCrypter extends vscNull {
	static private $oInstance;
	/**
	 * @var PasswordHash
	 */
	private $oCrypt;
	
	public function __construct() {
		// Base-2 logarithm of the iteration count used for password stretching
		$hash_cost_log2 = 8;
		// Do we require the hashes to be portable to older systems (less secure)?
		$hash_portable = false;

		$this->oCrypt = new PasswordHash($hash_cost_log2, $hash_portable);
	}
	
	/**
	 * @return PasswordHash
	 */
	public function getCrypt () {
		return $this->oCrypt;
	}

	/**
	 * @return mmCrypter 
	 */
	static public function getInstance () {
		if (!(self::$oInstance instanceof self)) {
			self::$oInstance = new self();
		} 
		
		return self::$oInstance;
	}
	
	static public function hash ($sIncoming) {
		return self::getInstance()->getCrypt()->HashPassword($sIncoming);
	}
	
	static public function check ($sIncoming, $sStoredHash) {
		return self::getInstance()->getCrypt()->CheckPassword($sIncoming, $sStoredHash);
	}
}