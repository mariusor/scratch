<?php
class vscInlineResources extends vscHtml5View {

	static public function outputScript ($sPath) {
		if (preg_match('#http[s]?://#', $sPath) == 0 && is_file($sPath)) {
			echo '<script type="text/javascript">' ."\n" . file_get_contents($sPath) . "\n" . '</script>' . "\n";
		} else {
			echo '<script type="text/javascript" src="'. $sPath . '"> </script>' . "\n";
		}
	}

	static public function outputStyle ($sPath, $sMedia) {
		if (preg_match('#http[s]?://#', $sPath) == 0 && is_file($sPath)) {
			echo '<style type="text/css" media="' . $sMedia . '" >' . "\n" . file_get_contents($sPath) . "\n" . '</style>' . "\n";
		} else {
			echo  '<link rel="stylesheet" href="' . $sPath . '"' . ($sMedia ? ' media="'. $sMedia.'"' : '') . " />\n";
		}
	}
}