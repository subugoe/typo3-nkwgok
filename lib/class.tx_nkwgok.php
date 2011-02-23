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

define('NKWGOKExtKey', 'nkwgok');
define('NKWGOKQueryTable', 'tx_nkwgok_data');
define('NKWGOKQueryFields', 'ppn, gok, search, descr, descr_en, parent, childcount');

/**
 * undocumented class
 *
 * @package default
 * @author Nils K. Windisch
 * */
class tx_nkwgok extends tx_nkwlib {

	/**
	 * Return GOK name for display.
	 *
	 * Use English if the language code is 'en' and German otherwise.
	 *
	 * Some GOK names end with a super-subject indicator enclosed in { }.
	 * This is helpful when viewing the subject name on its own but is redundant
	 * when viewed inside the subject hierarchy. The parameter $simplify = True
	 * removes that indicator.
	 *
	 * @author Sven-S. Porst <porst@sub.uni-goettingen.de>
	 * @param Array $gokRecord
	 * @param string $language ISO-639-1 language code as used by Typo3 [defaults to 'de']
	 * @param Boolean $simplify should the trailing {…} be removed? [defaults to False]
	 * @return string
	 */
	private function GOKName($gokRecord, $language='de', $simplify = False) {
		$displayName = $gokRecord['descr'];

		if ($language == 'en') {
			$englishName = $gokRecord['descr_en'];

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
	private function makeOPAClink($GOKData, $language) {
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nkwgok']);

		$languageID = 0;
		if ($language == 'en') {
			$languageID= 1;
		}

		$defaultOpacUrl = explode(',', $conf['defaultOpacUrl']);
		$opacUrl = $defaultOpacUrl[$languageID];

		$alternativeOpacUrlTrigger = explode(',', $conf['alternativeOpacUrlTrigger']);
		if (in_array($GOKData['gok']{0}, $alternativeOpacUrlTrigger)) {
			$alternativeOpacUrl = explode(',', $conf['alternativeOpacUrl']);
			$opacUrl = $alternativeOpacUrl[$languageID];
		}

		$URL = preg_replace('/PLACEHOLDER/', $GOKData['search'], $opacUrl);
		return $URL;
	}



	/**
	 * Returns GOK records for the children of a given PPN.
	 *
	 * @param string $parentPPN
	 * @return Array of GOK records of the $parentPPN’s children
	 */
	private function getChildren($parentPPN) {
		$whereClause = 'parent = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($parentPPN);
		$queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					NKWGOKQueryFields,
					NKWGOKQueryTable,
					$whereClause,
					'',
					'gok ASC',
					'');

		$children = Array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
			$children[] = $row;
		}

		return $children;
	}



	/**
	 * Create DOMDocument for AJAX return value and fill it with markup for the
	 * parent PPN and language given.

	 * @author Sven-S. Porst
	 * @param string $parentPPN
	 * @param string $language ISO 639-1 language code
	 * @return DOMDocument
	 * */
	public function AJAXGOKTreeChildren ($parentPPN, $language) {
		$doc = DOMImplementation::createDocument();

		$this->appendGOKTreeChildren($parentPPN, $doc, $doc, $language, Array($parentPPN))->firstChild;

		return $doc;
	}



	/**
	 * Determine jQuery Mode
	 * @author Nils K. Windisch
	 * @param int $mode
	 * @return string
	 */
	private function getJqueryMode($mode) {
		$marker = null;

		if ($mode === 1) {
			$marker = 'jQuery';
		} else {
			$marker = '$';
		}

		return $marker;
	}



	/**
	 * Return DOMDocument representing the tree set up in the given configuration.
	 *
	 * This is the only function called to create the tree on the web page.
	 *
	 * The $conf array needs to contain:
	 * - an string element 'gok' which can either be 'all' (to display the
	 *		complete GOK tree) or a GOK string of the node to be used as the
	 *		root of the tree
	 * - an array element 'getVars' with a array element 'expand'. Each of that
	 *		array’s elements are PPNs of the GOK elements displaying their
	 *		child elements
	 *
	 * @author Sven-S. Porst
	 * @param Array $conf
	 * @return DOMDocument
	 */
	public function GOKTree ($conf) {
		// create Document and add JavaScript
		$doc = DOMImplementation::createDocument();
		$scriptElement = $doc->createElement('script');
		$doc->appendChild($scriptElement);
		$scriptElement->setAttribute('type', 'text/javascript');
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][NKWGOKExtKey]);
		$jQueryMarker =  $this->getJqueryMode(intval($extConf['jQueryNoConflict']));
		$js = "
		function expandGOK (id) {
			var link = " . $jQueryMarker . "('#plusMinus-' + id);
			link.text('[-]');
			var functionText = 'hideGOK(\"' + id + '\");return false;';
		//	link.attr('onclick', new Function(functionText) );
		link[0].onclick = new Function(functionText);
			" . $jQueryMarker . ".get("
				. "'" . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . "index.php',
				{'eID': '" . NKWGOKExtKey . "', "
				. "'language': '" . $GLOBALS['TSFE']->lang . "', "
				. "'tx_" . NKWGOKExtKey . "[expand]': id },
				function (html) {
					console.log(jQuery('#c'+ id));
					jQuery('#c' + id).append(html);
				}
			);
		};
		function hideGOK (id) {
			" . $jQueryMarker . "('#ul-' + id).remove();
			var link = " . $jQueryMarker . "('#plusMinus-' + id);
			link.text('[+]');
			var functionText = 'expandGOK(\"' + id + '\");return false;';
		//	link.attr('onclick', new Function(functionText) );
			link[0].onclick = new Function(functionText);
		};
";
		$scriptElement->appendChild($doc->createTextNode($js));

		// default: query for the base node of the tree
		$firstNodeCondition = "parent = ''";
		// otherwise query for the specific GOK node by GOK Name

		if ($conf['gok'] != 'all') {
			$firstNodeCondition = "gok LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr($conf['gok'], NKWGOKQueryTable);
		}

		// run query and collect result
		$queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					NKWGOKQueryFields,
					NKWGOKQueryTable,
					$firstNodeCondition,
					'',
					'gok ASC',
					'');
		$GOKs = Array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
			$GOKs[] = $row;
		}

		foreach ($GOKs as $GOK) {
			$container = $doc->createElement('div');
			$doc->appendChild($container);
			$container->setAttribute('class', 'gokTreeContainer');
			$container->appendChild($this->OPACLinkElement($GOK, $doc, $container));
			$this->appendGOKTreeChildren($GOK['ppn'], $doc, $container, $GLOBALS['TSFE']->lang, $conf['getVars']['expand']);
		}

		return $doc;
	}



	/**
	 * Returns DOMElement with complete markup for linking to the OPAC entry.
	 *
	 * @author Sven-S. Porst
	 * @param Array $GOKData GOK record
	 * @param DOMDocument $doc document used to create the resulting element
	 * @param string $language ISO 639-1 language code
	 * @return DOMElement
	 */
	private function OPACLinkElement ($GOKData, $doc, $language) {
		$opacLink = $doc->createElement('a');
		// TODO: localise
		$opacLink->setAttribute('title', 'Bücher zu diesem Thema im Opac anzeigen');
		// Question: Is '_blank' a good idea?
		$opacLink->setAttribute('target', '_blank');
		$opacLink->setAttribute('href', $this->makeOPACLink($GOKData, $language));

		$GOKIDSpan = $doc->createElement('span');
		$opacLink->appendChild($GOKIDSpan);
		$GOKIDSpan->setAttribute('class', 'GOKID');
		$GOKIDSpan->appendChild($doc->createTextNode($GOKData['gok']));

		$opacLink->appendChild($doc->createTextNode(' '));
		$GOKNameSpan = $doc->createElement('span');
		$opacLink->appendChild($GOKNameSpan);
		$GOKNameSpan->setAttribute('class', 'GOKName');
		$opacLink->appendChild($doc->createTextNode(' ' . $this->GOKName($GOKData, $language, True)));

		return $opacLink;
	}



	/**
	 * Looks up child elements for the given $parentPPN, creates a list with
	 * markup for them and adds them to the given $container element inside $doc,
	 * taking into account which parent elements are configured to display their
	 * children.
	 *
	 * @author Nils K. Windisch
	 * @author Sven-S. Porst
	 * @param string $parentPPN
	 * @param DOMDocument $doc document used to create the resulting element
	 * @param DOMElement $container the created markup is appended to (needs to be a child element of $doc)
	 * @param string $language ISO 639-1 language code
	 * @param string $expandMarker list of PPNs of open parent elements, separated by '-' [defaults to '']
	 * @return void
	 * */
	private function appendGOKTreeChildren($parentPPN, $doc, $container, $language, $expandInfo, $expandMarker = '') {
		$GOKs = $this->getChildren($parentPPN);

		if (sizeof($GOKs) > 0) {
			$ul = $doc->createElement('ul');
			$container->appendChild($ul);
			$ul->setAttribute('id', 'ul-' . $parentPPN);

			foreach ($GOKs as $GOK) {
				$PPN = $GOK['ppn'];
				$expand = $PPN;

				if ($expandMarker != '') {
					$expand = $expandMarker . '-' . $PPN;
				}

				/* display in each list item
				 * 1. Expand xor collapse link if there are child elements depending on the expanded state
				 * 2. The linked name
				 * 3. If the item has child elements and is expanded, the list of child elements
				 */
				$li = $doc->createElement('li');
				$ul->appendChild($li);
				$li->setAttribute('id', 'c' . $PPN);

				$control = $doc->createElement('a');
				$control->setAttribute('id', 'plusMinus-' . $PPN);
				$li->appendChild($control);
				
				$li->appendChild($this->OPACLinkElement($GOK, $doc, $language));

				if ($GOK['childcount'] > 0) {
					$JSCommand = '';
					$buttonText = '';
					$noscriptLink = '#';
	
					if ( ($expandInfo && in_array($PPN, $expandInfo)) ) {
						$li->setAttribute('class', 'close');
						$JSCommand = 'hideGOK';
						$buttonText = '-';
						$noscriptLink = t3lib_div::linkThisUrl(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),
								array('tx_' . NKWGOKExtKey . '[expand]' => $expandMarker) );

						// recursively call self to get child UL
						$this->appendGOKTreeChildren($PPN, $doc, $li, $language, $expandInfo, $expand)->firstChild;
					}
					else {
						$li->setAttribute('class', 'open');
						$JSCommand = 'expandGOK';
						$buttonText = '+';
						$noscriptLink = t3lib_div::linkThisUrl(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),
								array('tx_' . NKWGOKExtKey . '[expand]' => $expand, 'no_cache' => 1) )
								. '#c' . $PPN;
					}

					$buttonText = '[' . $buttonText . ']';

					$control->setAttribute('onclick',  $JSCommand . '("' . $PPN . '");return false;');
					$control->setAttribute('href', $noscriptLink);
					$control->appendChild($doc->createTextNode($buttonText));
				}
			}
		}
	}

}

?>
