<?php
namespace littrme\presentation\views;

use vsc\presentation\views\Html5View;

class InlineResources extends Html5View {

	static public function outputScript ($sPath) {
		if (preg_match('#http[s]?://#', $sPath) == 0 && is_file($sPath)) {
			return '<script type="text/javascript">' ."\n" . file_get_contents($sPath) . "\n" . '</script>' . "\n";
		} else {
			return '<script type="text/javascript" src="'. $sPath . '"> </script>' . "\n";
		}
	}

	static public function outputStyle ($sPath, $sMedia) {
		if (preg_match('#http[s]?://#', $sPath) == 0 && is_file($sPath)) {
			return '<style type="text/css" media="' . $sMedia . '" >' . "\n" . file_get_contents($sPath) . "\n" . '</style>' . "\n";
		} else {
			return '<link rel="stylesheet" href="' . $sPath . '"' . ($sMedia ? ' media="'. $sMedia.'"' : '') . " />\n";
		}
	}
}
