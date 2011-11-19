<?php
import (ORM_PATH . 'res');
import (ORM_PATH . 'lib');
import ('domain/connections');

class ltrMySqlAccess extends mySqlIm {
	public function getDatabaseType() {
		return vscConnectionType::mysql;
	}
	public function getDatabaseHost() {
		return 'localhost';
	}
	public function getDatabaseUser() {
		return 'littr';
	}
	public function getDatabasePassword() {
		return '***';
	}
	public function getDatabaseName() {
		return 'littr';
	}
}
