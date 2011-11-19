<?php
class ltrSqlAccessFactory extends vscNull {

	static function getConnection () {
		try {
			return new ltRrPgAccess();
		} catch (Exception $e) {
			// if the connection failed, we try the mysql one
			return new ltrMySqlAccess();
		}
	}
}