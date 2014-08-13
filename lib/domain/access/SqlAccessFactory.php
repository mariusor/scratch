<?php
namespace littrme\domain\access;

use vsc\infrastructure\Null;

class SqlAccessFactory extends Null {

	static function getConnection () {
		try {
			return new PostgreSql();
		} catch (\Exception $e) {
			// if the connection failed, we try the mysql one
			return new MySql();
		}
	}
}