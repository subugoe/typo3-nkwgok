<?php

/* * *************************************************************
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
 * ************************************************************* */
require_once(t3lib_extMgm::extPath('nkwlib') . 'class.tx_nkwlib.php');

/**
 * undocumented class
 *
 * @package default
 * @author Nils K. Windisch
 * */
class tx_nkwgok extends tx_nkwlib {

	/**
	 * undocumented class variable
	 *
	 * @var string
	 * */
	var $queryTable;
	/**
	 * undocumented class variable
	 *
	 * @var string
	 * */
	var $queryFor;
	/**
	 * undocumented class variable
	 *
	 * @var string
	 * */
	var $getvarsExpandArr;

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
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
	 * */
	function setQueryTable($str) {
		$this->queryTable = $str;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
	function setQueryFor($str) {
		$this->queryFor = $str;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
	function getQueryTable() {
		return $this->queryTable;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
	function getQueryFor() {
		return $this->queryFor;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
	function setGetvarsExpandArr($str) {
		$this->getvarsExpandArr = $str;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
	function getGetvarsExpandArr($str) {
		return $this->getvarsExpandArr;
	}

	/**
	 * Return GOK name based on the current language code.
	 *
	 * Typo3 returns the language code in ISO 639-1 format, e.g. 'de' or 'en'.
	 * We default to German and try to use the English term if the current
	 * language is English and the English term exists.
	 *
	 * German GOK names are stored in field 044E $a. These always exist.
	 * Other GOK names are store in field 044F $a with subfield 044 $b
	 * containing a language code designating the language in ISO 639-2/B
	 * format, e.g. 'ger' or 'eng'.
	 *
	 * Some GOK names end in a super-subject indicator which can be helpful
	 * when viewing the subject name on its own but will be redundant when
	 * viewed inside the subject hierarchy. The parameter $simplify = True
	 * removed that indicator.
	 *
	 * @author Sven-S. Porst <porst@sub.uni-goettingen.de>
	 * @param Array $gokRecord
	 * @param Boolean $simplify should the trailing {…} be removed? defaults to False
	 * @return string
	 */
	private function GOKName($gokRecord, $language="de", $simplify = False) {
		$displayName = $gokRecord['descr'];

		if ($language == 'en') {
			$englishName = $gokRecord['descr_en'];
			t3lib_div::devLog($language . ": " . $englishName . $gokRecord['descr'], 'nkwgok', 1);

			if ($englishName) {
				$displayName = $englishName;
			}
		}

		// Remove trailing super-subject designator in { }
		if ($simplify) {
			$displayName = preg_replace("/( \{.*\})$/", "", $displayName);
		}

		return trim($displayName);
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
	function makeOPAClink($str, $lang = 0) {
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nkwgok']);
		$defaultOpacUrl = explode(',', $conf['defaultOpacUrl']);
		$alternativeOpacUrl = explode(',', $conf['alternativeOpacUrl']);
		$alternativeOpacUrlTrigger = explode(',', $conf['alternativeOpacUrlTrigger']);
		$opacUrl = $defaultOpacUrl[$lang];
		if (in_array($str['gok']{0}, $alternativeOpacUrlTrigger)) {
			$opacUrl = $alternativeOpacUrl[$lang];
		}
		$link = preg_replace('/PLACEHOLDER/', $str['search'], $opacUrl);
		return $link;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
	function linkToOpac($gok, $lang = 0, $simplifyName = False, $language = "de") {
		$str = '<a title="(' . $gok['gok'] . ') ' . $this->GOKName($gok, $language, $simplifyName) .
				'" target="_blank" href="' . $this->makeOPAClink($gok, $lang) . '">' .
				'<span class="GOKID">(' . $gok['gok'] . ')</span> ' . $this->GOKName($gok, $language, $simplifyName) . '</a>';
		return $str;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
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
			while ($row0 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res0)) {
				$row0['children'] = $this->getChildren($row0['ppn'], ($level + 1), $depth);
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
	 * */
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
		while ($row0 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res0)) {
			$children[] = $row0;
		}
		return $children;
	}

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
	function displayChildrenAjax($GOKs, $parentPpn, $lang = 0, $language = 'de') {
		$return = '';
		$level = 0;
		// $return .= "<a href='#' id='ajaxLinkHide" . $parentPpn . "' style='cursor: pointer;' " 
		// 	. "onclick='hideGok(\"" . $parentPpn . "\"); return false'>" 
		// 	. '[-ajax]</a>';
		$return .= '<ul class="tx-nkwgok-pi1-level-' . $level . '" id="ul' . $parentPpn . '">';
		$level++;

		foreach ($GOKs as $GOK) {
			$PPN = $GOK['ppn'];

			$return .= '<li id="c' . $PPN . '">';

			if ($GOK['haschildren']) {
				// Links for expanding and reducing tree levels
				$return .= "<a href='#' id='ajaxLinkHide" . $PPN . "' style='cursor: pointer; display: none;' "
						. "onclick='hideGok(\"" . $PPN . "\"); return false'>"
						. '[-]</a>';
				$return .= "<a href='#' id='ajaxLinkShow" . $PPN
					. "' style='cursor: pointer;' onclick='expandGok(\"" . $PPN . "\", \"c" . $PPN
					. "\"); return false;'>"
					. '[+]</a> ';
			}

			$return .= $this->linkToOpac($GOK, $lang, True, $language);
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
	 * */
	function displayChildren($conf, $GOKs, $level = 0, $expandMarker = 0, $parentPpn = 0) {
		$markup = '';

		if (sizeof($GOKs) > 0) {
			$markup .= '<ul id="ul' . $GOKs[0]['parent'] . '" class="tx-nkwgok-pi1-level-' . $level . '">';
			$level++;
			foreach ($GOKs as $GOK) {
				$PPN = $GOK['ppn'];
				$expand = $PPN;
				if ($level != 1) {
					$expand = $expandMarker . '-' . $PPN;
				}

				// Expand link: JavaScript and static versions.
				$expandLink = "<script type='text/javascript'>"
						. "document.write('<a href=\"#\" id=\"ajaxLinkShow" . $PPN
						. "\" style=\"cursor: pointer;\" onclick=\"expandGok(\'"
						. $PPN . "\', \'c" . $PPN . "\');return false;\">[+]</a>');\n"
						. "</script>";
				$expandLink .= "<noscript>"
						. $this->pi_LinkToPage(
								'[+]',
								$GLOBALS['TSFE']->id . "#c" . $PPN, '',
								array('tx_' . $this->extKey . '[expand]' => $expand, 'no_cache' => 1))
						. "</noscript>\n";
				
				// Collapse Link: JavaScript and static versions.
				$collapseLink = "<script type='text/javascript'>"
						. "document.write('<a href=\"#\" id=\"ajaxLinkHide"
						. $PPN . "\" style=\"cursor: pointer; display: none;\" onclick=\"hideGok(\'" . $PPN . "\', \'c"
						. $PPN . "\');return false;\">[-]</a>')"
						. "</script>\n";
				$collapsLink .= '<noscript>&nbsp;'
						. $this->pi_LinkToPage(
								'[-]',
								$GLOBALS['TSFE']->id,
								'',
								array('tx_' . $this->extKey . '[expand]' => $expandMarker))
						. "</noscript>\n";




				if (!in_array($PPN, $conf['getVars']['expand']) && $GOK['haschildren']) {
					// next line to catch in single gok view
					if (($level == 1 && $conf['gok'] == 'all') || $level != 1) {
						// construct LI
						$markup .= '<li class="open" id="c' . $PPN . '">';
						$markup .= $expandLink . $collapseLink . '&nbsp;';
					}
				}

				// show less option if children exist
				if (sizeof($GOK['children']) > 0) {
					// next line to catch in single gok view
					if ($conf['gok'] == 'all' || ($conf['gok'] != 'all' && $level != 1)) {
						// construct LI
						$markup .= '<li class="close" id="c' . $PPN . '">';
						$markup .= $collapseLink;
					}
					$markup .= $this->displayChildren($conf, $GOK['children'], $level, $expand);
				}

				// link to OPAC
				$markup .= $this->linkToOpac($GOK, $this->getLanguage(), True);
				$markup .= "</li>\n";
			}
			$markup .= "</ul>\n";
		}

		return $markup;
	}

}

?>
