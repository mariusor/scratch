<?php
class tstPgAccess extends vscSqlAccess {
	public function getDatabaseType() {
		return vscConnectionType::postgresql;
	}
	public function getDatabaseHost() {
		return 'localhost';
	}
	public function getDatabaseUser() {
		return 'b';
	}
	public function getDatabasePassword() {
		return 'asd890l;';
	}
	public function getDatabaseName() {
		return 'b';
	}
}