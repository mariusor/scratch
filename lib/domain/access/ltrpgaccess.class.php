<?php
import (ORM_PATH . 'res');
import (ORM_PATH . 'lib');
import ('domain/connections');
class ltrPgAccess extends postgreSql {
	protected function getDatabaseType() {
		return vscConnectionType::postgresql;
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