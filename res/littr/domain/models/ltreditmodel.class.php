<?php
class ltrEditModel extends vscCacheableModelA {
	public $uri;
	public $help;
	public $content;
	public $created;
	public $modified;
	public $secret;

	public function __construct ($sCurrentUri) {
		$o = new contentTable();
		$o->loadContent ($sCurrentUri);

		$this->uri			= $o->uri;
		$this->content		= $o->content;
		$this->created		= $o->created;
		$this->modified		= $o->modified;
		$this->secret		= $o->secret;
		$this->help			= "Tab indent, Shift+Tab outdent, Ctrl+B bold, Ctrl+I italic, Ctrl+L insert a link, Ctrl+G insert an image";
	}

	public function getLastModified () {
		return $this->modified;
	}
}