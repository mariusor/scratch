<?php
namespace littrme\presentation\views;

class View extends InlineResources {
	public function dateFormat ($sFormat, $iTimestamp) {
		if ($sFormat == '%fancy') {
			$sWhen = 'ago';
			$iNow = time();
			$iDifference = $iNow - $iTimestamp;
			if ($iDifference < 0) {
				$sWhen = 'in the future';
				$iDifference = abs($iDifference);
			}
			if ( $iDifference < 60 ) {
				// this minute
				$sUnit = 'second';
				$iCount = $iDifference;
			} elseif ( $iDifference < 3600 ) {
				// this hour
				$sUnit = 'minute';
				$iCount = intval($iDifference / 60);
			} elseif ( $iDifference < 86400 ) {
				// today
				$sUnit = 'hour';
				$iCount = intval($iDifference / 3600);
			} elseif ( $iDifference < 604800 ) {
				// this week
				$sUnit = 'day';
				$iCount = intval($iDifference / 86400);
			} elseif ( $iDifference < 2419200 ) {
				// this month
				$sUnit = 'week';
				$iCount = intval($iDifference / 604800);
			} else {
				$sUnit = 'month';
				$iCount = round($iDifference / 2419200);
			}
			return $iCount . ' ' . $sUnit . ($iCount > 1 ? 's' : '') . ' ' . $sWhen;
		} else {
			return strftime($sFormat, $iTimestamp);
		}
	}
}