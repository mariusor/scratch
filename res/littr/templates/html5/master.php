<?php /* @var $this vscInlineResources */ ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
<?php
if (count($this->getMetaHeaders()) >= 1) {
	foreach ($this->getMetaHeaders() as $sName => $sValue) { ?>
	<meta <?php echo 'name="'.$sName .'" content="'.$sValue.'"'; ?> />
<?php
	}
}

$aAllScripts = $this->getScripts(true);
$aMoreScripts = array();
if (count ($aAllScripts) >= 1 ) {
	if (!$request->hasGetVar('show-index')) {
		foreach ($aAllScripts as $sPath) {
			static::outputScript ($sPath);
		}
	}
}

$aAllStyles = $this->getStyles ();
if (count($aAllStyles) >= 1) {
	foreach ($aAllStyles as $sMedia => $aStyles) {
		if (is_array($aStyles)) {
			foreach ($aStyles as $sPath ) {
				static::outputStyle ($sPath, $sMedia);
			}
		}
	}
}
if (is_array ($this->getLinks()) && count($this->getLinks()) >= 1) {
	foreach ($this->getLinks() as $sType => $aLinkContent) {
		foreach ($aLinkContent as $aValue) {
			echo "\t".'<link type="' . $sType .'" ';
			foreach ($aValue as $sKey => $sValue) {
				echo $sKey . '="' . $sValue . '" ';
			}
			echo '/>'."\n";
		}
	}
}
?>
	<title><?php $sTitle = $this->getTitle(); echo ($sTitle ? $sTitle : '[null]') ?></title>
</head>
<body>
	<!-- hic sunt leones -->
<?php
try {
	$sContent = $this->fetch ($this->getTemplate());
} catch (vscExceptionPath $e) {
	// the template could not be found
}
	if (!empty($sContent)) {
		echo $sContent;
	} else {
		echo $this->fetch(dirname(__FILE__) . '/content.php');
	}
?>
	<!-- /hic sunt leones -->
<?php
$aAllScripts = $this->getScripts();
if (count ($aAllScripts) >= 1 ) {
	if (!$request->hasGetVar('show-index')) {
		foreach ($aAllScripts as $sPath) {
			static::outputScript ($sPath);
		}
	}
}
?>
</body>
</html>
