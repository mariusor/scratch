<?php
namespace littrme\domain\access;

use orm\domain\access\connections\MySqlIm;
use orm\domain\connections\ConnectionType;

class MySql extends MySqlIm {
	public function getDatabaseType() {
		return ConnectionType::mysql;
	}
	public function getDatabaseHost() {
		return 'localhost';
	}
	public function getDatabaseUser() {
		return 'littr';
	}
	public function getDatabasePassword() {
		return '';
	}
	public function getDatabaseName() {
		return 'littr';
	}
}
