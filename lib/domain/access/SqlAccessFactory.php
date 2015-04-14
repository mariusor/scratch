<?php
namespace littrme\domain\access;

use orm\domain\connections\NullSql;
use vsc\infrastructure\Base;

class SqlAccessFactory extends Base {
	static function getConnection () {
		try {
			return new PostgreSql();
		} catch (\Exception $e) {
			// if the connection failed, we try the mysql one
			return new NullSql();
		}
	}
}
