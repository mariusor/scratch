<?php
import ('domain/access/tables');

class cBasicSqlAccess extends vscSqlAccess {
	public function getDatabaseType() {
		return vscConnectionType::mysql;
	}
	public function getDatabaseHost() {
		return DB_HOST;
	}
	public function getDatabaseUser() {
		return DB_USER;
	}
	public function getDatabasePassword() {
		return DB_PASS;
	}
	public function getDatabaseName() {
		return DB_NAME;
	}
}
