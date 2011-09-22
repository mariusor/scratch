<?php
class mmSqlAccess extends vscSqlAccess {
	public function getDatabaseType() {
		return vscConnectionType::mysql;
	}
	public function getDatabaseHost() {
		return 'localhost';
	}
	public function getDatabaseUser() {
		return 'root';
	}
	public function getDatabasePassword() {
		return null;
	}
	public function getDatabaseName() {
		return 'mmark';
	}
}