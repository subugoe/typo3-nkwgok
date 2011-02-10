<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Nils K. Windisch <windisch@sub.uni-goettingen.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
require_once(t3lib_extMgm::extPath('nkwlib') . 'class.tx_nkwlib.php');
/**
 * undocumented class
 *
 * @package default
 * @author Nils K. Windisch
 **/
class tx_nkwgok extends tx_nkwlib {
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	var $queryTable;
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	var $queryFor;
	/**
	 * undocumented class variable
	 *
	 * @var string
	 **/
	var $getvarsExpandArr;
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function addLanguageToWhereClause($query, $lang) {
		if (!$lang) {
			$lang = $this->getLanguage();
		}
		$query .= ' AND sys_language_uid = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($lang);
		return $query;
	}
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function setQueryTable($str) {
		$this->queryTable = $str;
	}
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function setQueryFor($str) {
		$this->queryFor = $str;
	}
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function getQueryTable() {
		return $this->queryTable;
	}
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function getQueryFor() {
		return $this->queryFor;
	}
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function setGetvarsExpandArr($str) {
		$this->getvarsExpandArr = $str;
	}
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function getGetvarsExpandArr($str) {
		return $this->getvarsExpandArr;
	}
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function makeOPAClink($str, $lang = 0) {
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nkwgok']);
		$defaultOpacUrl = explode(',', $conf['defaultOpacUrl']);
		$alternativeOpacUrl = explode(',', $conf['alternativeOpacUrl']);
		$alternativeOpacUrlTrigger = explode(',', $conf['alternativeOpacUrlTrigger']);
		$opacUrl = $defaultOpacUrl[$lang];
		if (in_array($str['gok']{0}, $alternativeOpacUrlTrigger)) {
			$opacUrl = $alternativeOpacUrl[$lang];
		}
		$link = ereg_replace('PLACEHOLDER', $str['search'], $opacUrl);
		return $link;
	}

	
	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function linkToOpac($gok, $lang = 0) {
		$str = '<a title="(' . $gok['gok'] . ') ' . $gok['descr'] . 
			'" target="_blank" href="' . $this->makeOPAClink($gok, $lang) . '">'
			. '(' . $gok['gok'] . ') ' . $gok['descr'] 
			. '</a>';
		return $str;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function getChildren($parentPPN, $level, $depth) {
		$children = Null;

		if ($level < $depth || in_array($parentPPN, $this->getvarsExpandArr)) {
			$whereClause = 'parent = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($parentPPN);
			$res0 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$this->getQueryFor(), 
				$this->getQueryTable(), 
				$whereClause, 
				'', 
				'gok ASC', 
				'');

			$children = Array();
			while($row0 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res0)) {
				$row0['children'] = $this->getChildren($row0['ppn'], ($level+1), $depth);
				$children[] = $row0;
			}
		}
		return $children;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function getChildrenAjax($parentPPN, $level, $depth) {
		$ppn = mysql_real_escape_string($parentPPN);
		$res0 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$this->getQueryFor(), 
			$this->getQueryTable(), 
			'parent = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($parentPPN), 
			'', 
			'gok ASC', 
			'');
		$children = Array();
		while($row0 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res0)) {
			$children[] = $row0;
		}
		return $children;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function displayChildrenAjax($gok, $parentPpn, $lang = 0) {
		$return = '';
		$level = 0;
		$size0 = sizeof($gok);
		// $return .= "<a href='#' id='ajaxLinkHide" . $parentPpn . "' style='cursor: pointer;' " 
		// 	. "onclick='hideGok(\"" . $parentPpn . "\"); return false'>" 
		// 	. '[-ajax]</a>';
		$return .= '<ul class="tx-nkwgok-pi1-level-' . $level . '" id="ul' . $parentPpn . '">';
		$level++;
		for ($i0 = 0; $i0 < $size0; $i0++) {
			$ppnCurrent = $gok[$i0]['ppn'];
			$expand = $ppnCurrent;
			$return .= '<li id="c' . $ppnCurrent . '">';
			if ($gok[$i0]['haschildren']) {
				$return .= "<a href='#' id='ajaxLinkHide" . $ppnCurrent . "' style='cursor: pointer; display: none;' " 
					. "onclick='hideGok(\"" . $ppnCurrent . "\"); return false'>" 
					. '[-]</a>';
			}
			// construct link to OPAC
			$tmpGokLink = $this->linkToOpac($gok[$i0], $lang);
			// construct More Link
			$tmpGokMoreLink = "<a href='#' id='ajaxLinkShow" . $ppnCurrent 
				. "' style='cursor: pointer;' onclick='expandGok(\"" . $ppnCurrent . "\", \"c" . $ppnCurrent 
				. "\"); return false;'>" 
				. '[+]</a> ';

			if ($gok[$i0]['haschildren']) {
				$return .= $tmpGokMoreLink;
			}

			$return .= $tmpGokLink;
			$return .= "</li>\n";
		}
		$return .= "</ul>\n";
		return $return;
	}


	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 **/
	function displayChildren($conf, $gok, $level = 0, $expandMarker = 0, $parentPpn = 0) {
		$tmp = '';
		$size0 = sizeof($gok);
		if ($size0 >= 1) {
			$tmp .= '<ul id="ul' . $gok[0]['parent'] . '" class="tx-nkwgok-pi1-level-' . $level . '">';
			$level++;
			for ($i0 = 0; $i0 < $size0; $i0++) {
				$ppnCurrent = $gok[$i0]['ppn'];
				$expand = $ppnCurrent;
				if ($level != 1) {
					$expand = $expandMarker . '-' . $ppnCurrent;
				}
				// check if children to display
				$gokHasChildren = (sizeof($gok[$i0]['children']) >= 1);
				// construct A HREF to OPAC
				$tmpLink = $this->linkToOpac($gok[$i0], $this->getLanguage());
				// construct More Link
				// make JS more link
				$tmpMoreLink = "<script type='text/javascript'>";
				$tmpMoreLink .= "document.write('<a href=\"#\" id=\"ajaxLinkShow" . $ppnCurrent
					. "\" style=\"cursor: pointer;\" onclick=\"expandGok(\'"
					. $ppnCurrent . "\', \'c" . $ppnCurrent . "\');return false;\">[+]</a>');";
				$tmpMoreLink .= "</script>\n";


				// make no JS more link
				$tmpMoreLink .= "<noscript>";
				$tmpMoreLink .= $this->pi_LinkToPage(
					'[+]', 
					$GLOBALS['TSFE']->id . "#c" . $ppnCurrent, '',
					array('tx_' . $this->extKey . '[expand]' => $expand, 'no_cache' => 1));
				$tmpMoreLink .= "</noscript>\n";
				// construct Less Link
				$tmpLessLink = "<script type='text/javascript'>";
				$tmpLessLink .= "document.write('<a href=\"#\" id=\"ajaxLinkHide"
					. $ppnCurrent . "\" style=\"cursor: pointer; display: none;\" onclick=\"hideGok(\'" . $ppnCurrent . "\', \'c"
					. $ppnCurrent . "\');return false;\">[-]</a>')";
				$tmpLessLink .= "</script>\n";
				// make JS less link
				$tmpLessLink .= '<noscript>';
				$tmpLessLink .= '&nbsp;';
				$tmpLessLink .= $this->pi_LinkToPage(
					'[-]', 
					$GLOBALS['TSFE']->id, 
					'', 
					array('tx_' . $this->extKey . '[expand]' => $expandMarker));
				$tmpLessLink .= "</noscript>\n";
				if (!in_array($ppnCurrent, $conf['getVars']['expand']) && $gok[$i0]['haschildren']) {
					// next line to catch in single gok view
					if (($level == 1 && $conf['gok'] == 'all') || $level != 1) {
						// construct LI
						$tmp .= '<li class="open" id="c' . $ppnCurrent . '">';
						$tmp .= $tmpMoreLink . $tmpLessLink . '&nbsp;';
					}
				}
				// show less option if a children attached
				if ($gokHasChildren == TRUE) {
					// next line to catch in single gok view
					if ($conf['gok'] == 'all' || ($conf['gok'] != 'all' && $level != 1)) {
						// construct LI
						$tmp .= '<li class="close" id="c' . $ppnCurrent . '">';
						$tmp .= $tmpLessLink;
					}
					$tmp .= $this->displayChildren($conf, $gok[$i0]['children'], $level, $expand);
				}
				// link to OPAC
				$tmp .= $tmpLink;
				$tmp .= "</li>\n";
			}
			$tmp .= "</ul>\n";
		}
		return $tmp;
	}
}
?>
