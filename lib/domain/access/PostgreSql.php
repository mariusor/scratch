<?php
namespace littrme\domain\access;

use orm\domain\access\connections\PostgreSql as vscPostgreSql;
use orm\domain\connections\ConnectionType;

class PostgreSql extends vscPostgreSql {
	protected function getDatabaseType() {
		return ConnectionType::postgresql;
	}
	protected function getDatabaseHost() {
		return 'localhost';
	}
	protected function getDatabaseUser() {
		return 'littr';
	}
	protected function getDatabasePassword() {
		return '***';
	}
	protected function getDatabaseName() {
		return 'littr';
	}
}