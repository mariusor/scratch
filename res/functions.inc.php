<?php
namespace littrme;

function isDebug () { return true; }

/**
 * @param \Exception $e
 * @return string
 */
function getErrorHeaderOutput ($e = null) {
	header ('HTTP/1.1 500 Internal Server Error');
	$sRet = '<?xml version="1.0" encoding="utf-8"?>';
	$sRet .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"';
	$sRet .= '"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	$sRet .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">';
	$sRet .= '<head>';
	$sRet .= '<style>ul {padding:0; font-size:0.8em} li {padding:0.2em;display:inline} address {position:fixed;bottom:0;}</style>';
	$sRet .= '<title>Internal Error' . (!$e ? '' : ': '. substr($e->getMessage(), 0, 20) . '...') . '</title>';
	$sRet .= '</head>';
	$sRet .= '<body>';
	$sRet .= '<strong>Internal Error' . (!$e ? '' : ': '. $e->getMessage()) . '</strong>';
	$sRet .= '<address>&copy; habarnam</address>';
	$sRet .= '<ul><li><a href="#" onclick="p = document.getElementById(\'trace\'); if (p.style.display==\'block\') p.style.display=\'none\';else p.style.display=\'block\'; return false">toggle trace</a></li><li><a href="javascript: p = document.getElementById(\'trace\'); document.location.href =\'mailto:marius@habarnam.ro?subject=Problems&body=\' + p.innerHTML; return false">mail me</a></li></ul>';

	if ($e instanceof \Exception)
		$sRet .= '<p style="font-size:.8em">Triggered in <strong>' . $e->getFile() . '</strong> at line ' . $e->getLine() .'</p>';

	$sRet .= '<pre style="position:fixed;bottom:2em;display:none;font-size:.8em" id="trace">';

	return $sRet;
}

function _e ($e) {
	$sErrors = '';
	$iLevel = ob_get_level();
	for ($i = 0; $i < $iLevel - 2; $i++) {
		$sErrors .= ob_get_clean();
	}
	$iCleanLevel = ob_get_level();
	for ($i = 0; $i < $iLevel; $i++) {
		ob_end_clean();
	}

	header ('HTTP/1.1 500 Internal Server Error');
	echo getErrorHeaderOutput ($e);
	if (isDebug()) {
		echo $e ? $e->getTraceAsString() : '';
	}
	if ($sErrors)
	echo '<p>' . $sErrors . '</p>';
	echo '</pre>';
	echo '</body>';
	echo '</html>';
	exit (0);
}
/**/
function base_encode($val, $base=62, $chars='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
	// can't handle numbers larger than 2^31-1 = 2147483647
	$str = '';
	do {
		$i = $val % $base;
		$str = $chars[$i] . $str;
		$val = ($val - $i) / $base;
	} while($val > 0);
	return $str;
}

function base_decode($str, $base=62, $chars='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
	$len = strlen($str);
	$val = 0;
	$arr = array_flip(str_split($chars));
	for($i = 0; $i < $len; ++$i) {
		$val += $arr[$str[$i]] * pow($base, $len-$i-1);
	}
	return $val;
}

// function _ ($s) {
// 	return $s;
// }