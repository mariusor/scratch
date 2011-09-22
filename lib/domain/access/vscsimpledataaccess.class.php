<?php
import ('domain/clauses');
import ('access/clauses');
class vscSimpleDataAccess extends vscSqlAccessA {
	private $oGrammarHelper;

	public $iStart;
	public $iCount;

	public function getDatabaseType() {
		return vscConnectionType::mysql;
	}
	public function getDatabaseHost() {
		return 'localhost';
	}
	public function getDatabaseUser() {
		return 'calator';
	}
	public function getDatabasePassword() {
		return 'asd';
	}
	public function getDatabaseName() {
		return 'calator';
	}

	public function save (vscDomainObjectA $oInc) {}

	public function setGrammarHelper ($oGrammarHelper) {
		$this->oGrammarHelper = $oGrammarHelper;
	}

	/**
	 * @return SQLGenericDriver
	 */
	public function getGrammarHelper () {
		return $this->oGrammarHelper;
	}

	public function buildDefaultClauses (vscCompositeDomainObjectI $oDomainObject) {
		$o = $this->getGrammarHelper();
		$aWheres = array();

		$aClauses = array();
		/* @var $oField vscFieldA */
		foreach ($oDomainObject->getFields() as $oField) {
			if ($oField->hasValue()) {
				$aWheres[]	= new vscClause($oField, '=', $oField->getValue());
			}
		}

		if (vscCompositeDomainObjectA::isValid ($oDomainObject)) {
			foreach ($oDomainObject->getForeignKeys() as $aFields) {
				if (vscFieldA::isValid($aFields[0]) && vscFieldA::isValid($aFields[1]) ) {
					$aWheres[]	= new vscClause($aFields[0], '=', $aFields[1]);
				}
			}
		}

		if (count($aClauses) == 0 && count($aWheres) == 0) {
			$aClauses = array (new vscClause ($o->TRUE));
		} else {
			$aClauses = array_merge($aClauses, $aWheres);
		}

		return $aClauses;
	}

	public function outputFieldsForSelect (vscDomainObjectI $oDomainObject) {
		$aSelectFields = array ();
		/* @var $oField vscFieldA */

		$o = $this->getGrammarHelper();
		foreach ($oDomainObject->getFields() as $oField) {
			if (is_null($oField->getValue())) {
				$sFieldSelect = ($oDomainObject->hasTableAlias() ? $o->FIELD_OPEN_QUOTE . $oDomainObject->getTableAlias() . $o->FIELD_CLOSE_QUOTE . '.' : '') .
				$o->FIELD_OPEN_QUOTE . $oField->getName() . $o->FIELD_CLOSE_QUOTE;

//				if ($this->hasFieldAggregatorFunction($oField)) {
//					$sFieldSelect = sprintf($this->getFieldAggregatorFunction($oField), $sFieldSelect);
//				}
				$aSelectFields[] = $sFieldSelect . ($oField->hasAlias() ? $o->_AS($o->FIELD_OPEN_QUOTE . $oField->getAlias(). $o->FIELD_CLOSE_QUOTE) : '');
			}
		}

		return implode(', ', $aSelectFields);
	}

	public function outputLimit () {
		return $this->getGrammarHelper()->_LIMIT ($this->iStart, $this->iCount);
	}

	public function outputClauses ($aClauses) {
		$sStr = '';
		$aStrClauses = array();

		if (count ($aClauses) > 0 ) {
			$oClauseAccess = new vscSqlClauseAccess();
			$oClauseAccess->setGrammarHelper($this->getGrammarHelper());

			foreach ($aClauses as $oClause) {
				$aStrClauses[] .= $oClauseAccess->getDefinition($oClause);
			}

			$sStr = implode ($this->getGrammarHelper()->_AND(), $aStrClauses);
		}

		return $sStr;
	}

	public function outputTablesForSelect (vscDomainObjectI $oDomainObject, $bWithAlias = false) {
		$o = $this->getGrammarHelper();

		$sRet = $o->FIELD_OPEN_QUOTE . $oDomainObject->getTableName() . $o->FIELD_CLOSE_QUOTE;
		if ($bWithAlias && $oDomainObject->hasTableAlias()) {
			$sRet .=  $o->_AS($o->FIELD_OPEN_QUOTE . $oDomainObject->getTableAlias() . $o->FIELD_CLOSE_QUOTE);
		}

		return $sRet;
	}


	public function outputSelectSql (vscCompositeDomainObjectI $oDomainObject) {
		$aSelects = array();
		$aNames = array ();

		if ($oDomainObject instanceof vscCompositeDomainObjectI) {
			// fsck yeah: joins!
			$aDomainObjects = $oDomainObject->getDomainObjects();
		} elseif ($oDomainObject instanceof vscDomainObjectI) {
			// single table mode
			$aDomainObjects = array (
				$oDomainObject->getTableName() => $oDomainObject
			);
		}

		foreach ($aDomainObjects as $key => $oParameter) {
			if (!vscDomainObjectA::isValid($oParameter)) {
				unset ($aParameters[$key]);
				continue;
			}
			/* @var $oParameter vscDomainObjectA */
			if (!$oParameter->hasTableAlias()) {
				$oParameter->setTableAlias('t'.$key);
			}

			$aNames[] = $this->outputTablesForSelect($oParameter, true);

			$aSelects[] =  $this->outputFieldsForSelect($oParameter, true);
		}

		$aClauses = $this->buildDefaultClauses($oDomainObject);

		$aWheres = array();
		$o = $this->getGrammarHelper();

		$sRet = $o->_SELECT (implode (', ', $aSelects)) .
			$o->_FROM(implode (', ', $aNames)) ."\n" .
		//$this->getJoinsString() .
			$o->_WHERE($this->outputClauses ($aClauses)) .
				$this->outputGroupBys($aGroupBys) .
				$this->outputOrderBys($aOrderBys) .
				$this->outputLimit();

		return $sRet . ';';
	}

	public function outputGroupBys ($aGroupBys) {
		$sGroupBy = '';
		if (count ($aGroupBys) > 0 ) {
			foreach ($aGroupBys as $oField) {
				$oDomainObject = $oField->getParent();
				$sGroupBy .= ($oField->hasAlias() ? $oField->getAlias() : $oField->getName());
			}
			return $this->getGrammarHelper()->_GROUP($sGroupBy);
		} else {
			return '';
		}
	}

	public function outputOrderBys($aOrderBys) {
		$sOrderBy = '';
		if (count ($aOrderBys) > 0 ) {
			foreach ($aOrderBys as $aOrderBy) {
				$oField = $aOrderBy[0];
				$sDirection = $aOrderBy[1];
				$sOrderBy = ($oField->hasAlias() ? $oField->getAlias() : $oField->getName()) . ' '. $sDirection ;
			}

			return $this->getGrammarHelper()->_ORDER($sOrderBy);
		} else {
			return '';
		}
	}

}