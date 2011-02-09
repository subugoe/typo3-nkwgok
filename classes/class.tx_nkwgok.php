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

class tx_nkwgok extends tx_nkwlib {

	var $queryTable;
	var $queryFor;
	var $getvarsExpandArr;

	function addLanguageToWhereClause($query, $lang) {
		if (!$lang) {
			$lang = $this->getLanguage();
		}
		$query .= ' AND sys_language_uid = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($lang);
		return $query;
	}

	function setQueryTable($str) {
		$this->queryTable = $str;
	}

	function setQueryFor($str) {
		$this->queryFor = $str;
	}
	
	function getQueryTable() {
		return $this->queryTable;
	}

	function getQueryFor() {
		return $this->queryFor;
	}

	function setGetvarsExpandArr($str) {
		$this->getvarsExpandArr = $str;
	}

	function getGetvarsExpandArr($str) {
		return $this->getvarsExpandArr;
	}

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

	function getChildren($parentPPN, $level, $depth) {
		if ($level < $depth || in_array($parentPPN, $this->getvarsExpandArr)) {
			$i0 = 0;
			$whereClause = "parent = " . $GLOBALS['TYPO3_DB']->fullQuoteStr($parentPPN);
			$res0 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				$this->getQueryFor(), 
				$this->getQueryTable(), 
				$whereClause, 
				'', 
				'gok ASC', 
				'');
			while($row0 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res0)) {
				$children[$i0] = $row0;
				$return = $this->getChildren($row0['ppn'], ($level+1), $depth);
				$children[$i0]['children'] = $return;
				$i0++;
			}
		}
		return $children;
	}


	function getChildrenAjax($parentPPN, $level, $depth) {
		$i0 = 0;
		$ppn = mysql_real_escape_string($parentPPN);
		$res0 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			$this->getQueryFor(), 
			$this->getQueryTable(), 
			"parent = " . $GLOBALS['TYPO3_DB']->fullQuoteStr($parentPPN), 
			'', 
			'gok ASC', 
			'');
		while($row0 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res0)) {
			$children[$i0] = $row0;
			$i0++;
		}
		return $children;
	}


	function displayChildrenAjax($gok, $parentPpn, $lang = 0) {
		$size0 = sizeof($gok);
		$tmp .= "<a href='#' id='ajaxLinkHide" . $parentPpn . "' style='cursor: pointer;' " . 
			"onclick='hideGok(\"" . $parentPpn . "\"); return false'>";
		$tmp .= "[-]</a>";
		$tmp .= "<ul class='tx-nkwgok-pi1-level-" . $level . "' id='ul" . $parentPpn . "'>";
		$level++;
		for($i0=0; $i0<$size0; $i0++) {
			$ppnCurrent = $gok[$i0]['ppn'];
			$expand = $ppnCurrent;
			$tmp .= "<li id='c" . $ppnCurrent . "'>";
			$tmp .= "<a title='(" . $gok[$i0]['gok'] . ") " . $gok[$i0]['descr'] . 
				"' target='_blank' href='" . $this->makeOPAClink($gok[$i0], $lang) . "'>";
			$tmp .= '(' . $gok[$i0]['gok'] . ') ' . $gok[$i0]['descr'];
			$tmp .= '</a>';
			$tmp .= '&nbsp;';
			if ($gok[$i0]['haschildren']) {
				$tmp .= "<a href='#' id='ajaxLinkShow" . $ppnCurrent . 
					"' style='cursor: pointer;' onclick='expandGok(\"" . $ppnCurrent . "\", \"c" . $ppnCurrent . 
					"\"); return false;'>[+]</a>";
			}
			$tmp .= "</li>";
		}
		$tmp .= "</ul>";
		return $tmp;
	}


	function displayChildren($conf, $gok, $level = 0, $expandMarker = 0, $parentPpn = 0)
	{
		// $this->dprint($conf['gok']);
		$size0 = sizeof($gok);
		if ($size0 >= 1) {
			$tmp .= "<ul id='ul" . $gok[0]['parent'] . "' class='tx-nkwgok-pi1-level-" . $level . " '>";
			$level++;
			for($i0=0; $i0<$size0; $i0++) {
				$ppnCurrent = $gok[$i0]['ppn'];
				$expand = $ppnCurrent;
				if ($level != 1) {
					$expand = $expandMarker . "-" . $ppnCurrent;
				}
				$tmp .= "<li id='c" . $ppnCurrent . "'>";
				$tmp .= "<a title='(" . $gok[$i0]['gok'] . ") " . $gok[$i0]['descr'] . 
					"' target='_blank' href='" . $this->makeOPAClink($gok[$i0], $this->getLanguage()) . "'>";
				$tmp .= '(' . $gok[$i0]['gok'] . ') ' . $gok[$i0]['descr'];
				$tmp .= '</a>';
				$tmp .= '&nbsp;';
				if (!in_array($ppnCurrent, $conf['getVars']['expand']) && $gok[$i0]['haschildren']) {
					// next line to catch in single gok view
					if (($level == 1 && $conf['gok'] == 'all') || $level != 1) {
						// make JS more link
						$tmp .= "<script type='text/javascript'>";
						$tmp .= "document.write('<a href='#' id='ajaxLinkShow" . $ppnCurrent . 
							"' style='cursor: pointer;' onclick='expandGok(\"" . $ppnCurrent . "\", \"c" . $ppnCurrent . 
							"\");return false;'>[+]</a>');";
						$tmp .= '</script>';
						// make no JS more link
						$tmp .= "<noscript>";
						$tmp .= $this->pi_LinkToPage(
								'[+]', 
								$GLOBALS['TSFE']->id . "#c" . $ppnCurrent, '',
								array('tx_' . $this->extKey . '[expand]' => $expand, 'no_cache' => 1));
						$tmp .= '</noscript>';
					}
				}
				// show less option if an children attached
				if (sizeof($gok[$i0]["children"]) >= 1) {
					// next line to catch in single gok view
					if ($conf['gok'] == 'all' || ($conf['gok'] != 'all' && $level != 1)) {
						// make JS less link
						$tmp .= "<script type='text/javascript'>";
						$tmp .= 'document.write(<a href=\'#\' id=\'ajaxLinkHide' . $ppnCurrent . '\' style=\'cursor: pointer;\' onclick=\'hideGok(\"' . $ppnCurrent . '\", \"c' . $ppnCurrent . '\");return false;\'>[-]</a>)';
						$tmp .= '</script>';
						// make JS less link
						$tmp .= '<noscript>';
						$tmp .= '&nbsp;';
						$tmp .= $this->pi_LinkToPage(
									'[-]', 
									$GLOBALS['TSFE']->id, 
									'', 
									array('tx_' . $this->extKey . '[expand]' => $expandMarker));
						$tmp .= '</noscript>';
					}
					$tmp .= $this->displayChildren($conf, $gok[$i0]['children'], $level, $expand);
				}
				unset($expand);
				$tmp .= '</li>';
			}
			$tmp .= '</ul>';
		}
		return $tmp;
	}

}
?>