<?php /* @var $this vscHtml5View */ ?>
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
?>
<script type="text/javascript">
<?php
	foreach ($aAllScripts as $sPath) {
		if (preg_match('#http[s]?://#', $sPath) == 0) {
			echo file_get_contents($sPath) . "\n";
		} else {
			// assume it's an url
			$aMoreScripts[] = $sPath;
		}
	} ?>
</script>
<?php
}
if (count ($aMoreScripts) >= 1 ) {
	foreach ($aMoreScripts as $sPath) {
?>
	<script type="text/javascript" src="<?php echo $sPath;?>"> </script>
<?php
	}
}

$aAllStyles = $this->getStyles ();
$aMoreStyles = array();
if (count($aAllStyles) >= 1) {
	foreach ($aAllStyles as $sMedia => $aStyles) {
		if (is_array($aStyles)) {
?>
<style type="text/css" media="<?php echo $sMedia; ?>" >
<?php
	foreach ($aStyles as $sPath ) {
		if (preg_match('#http[s]?://#', $sPath) == 0) {
			echo file_get_contents($sPath) . "\n";
		} else {
			// asume it's available as an url
			$aMoreStyles[$sMedia][] = $sPath;
		}
	}
?>
</style>
<?php
		}
	}
}
if (count($aMoreStyles) >= 1) {
	foreach ($aMoreStyles as $sMedia => $aStyles) {
		if (is_array($aStyles)) {
			foreach ($aStyles as $sPath ) {
				echo "\t".'<link rel="stylesheet" href="' . $sPath . '"' . ($sMedia ? ' media="'. $sMedia.'"' : '') . " />\n";
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
?>
<script type="text/javascript">
<?php
	$aMoreScripts = array();
	foreach ($aAllScripts as $sPath) {
		if (preg_match('#http[s]?://#', $sPath) == 0) {
			echo file_get_contents($sPath) . "\n";
		} else {
			// asume it's available as an url
			$aMoreScripts[] = $sPath;
		}
	}
?>
</script>
<?php
}

if (count ($aMoreScripts) >= 1 ) {
	foreach ($aMoreScripts as $sPath) {
?>
<script type="text/javascript" src="<?php echo $sPath;?>"> </script>
<?php
	}
}
?>
</body>
</html>
