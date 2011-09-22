<?php
class mmPermissionsTable extends vscDomainObjectA {
	public $id;
	public $name;
	public $bitmask;
	
	protected $sTableName = 'mm_permissions';
	
	public function buildObject () {
		$this->id = new vscFieldInteger('id');
		$this->name = new vscFieldText('name');
		$this->bitmask = new vscFieldInteger('bitmask');
	}
}