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

define('NKWGOKExtKey', 'nkwgok');
define('NKWGOKQueryTable', 'tx_nkwgok_data');
define('NKWGOKQueryFields', 'ppn, gok, search, descr, descr_en, parent, childcount, hitcount, fromopac');

/**
 * undocumented class
 *
 * @package default
 * @author Nils K. Windisch
 * */
class tx_nkwgok extends tslib_pibase {

	/**
	 * @var Array
	 */
	protected $localisation;

	/**
	 * Provide our own localisation function as getLL() isn't available when
	 * running in eID.
	 *
	 * @author Sven-S. Porst
	 * @param string $key key to lool up in pi1/locallang.xml
	 * @param string $language ISO 639-1 language code
	 * @return string
	 */
	private function localise ($key, $language) {
		// initialise the $localisation variable
		if (!$this->localisation) {
	        $this->localisation = t3lib_div::readLLfile('EXT:' . NKWGOKExtKey . '/pi1/locallang.xml', $language, '', 2);
		}

		$result = $this->localisation[$language][$key];
		if (!result) {
			$result = $this->localisation['default'][$key];
		}

		return $result;
	}



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
	 * Returns GOK records for the children of a given PPN.
	 *
	 * @param string $parentPPN
	 * @return Array of GOK records of the $parentPPN’s children
	 */
	private function getChildren($parentPPN) {
		$whereClause = 'parent = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($parentPPN, NKWGOKQueryTable);
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
	 * Returns DOMElement with complete markup for linking to the OPAC entry.
	 * The link text indicates the number of results if it is known.
	 *
	 * @author Sven-S. Porst
	 * @param Array $GOKData GOK record
	 * @param DOMDocument $doc document used to create the resulting element
	 * @param string $language ISO 639-1 language code
	 * @return DOMElement
	 */
	private function OPACLinkElement ($GOKData, $doc, $language) {
		$opacLink = Null;
		$hitCount = $GOKData['hitcount'];
		$URL = $this->opacGOKSearchURL($GOKData, $language);
		if ($hitCount != 0 && $URL) {
			$opacLink = $doc->createElement('a');
			$opacLink->setAttribute('href', $URL);
			$opacLink->setAttribute('title', $this->localise('Bücher zu diesem Thema im Opac anzeigen', $language) );
			// Question: Is '_blank' a good idea?
			$opacLink->setAttribute('target', '_blank');
			if ($hitCount > 0) {
				// we know the number of results: display it
				$opacLink->appendChild($doc->createTextNode(sprintf($this->localise('%d Treffer anzeigen', $language), $GOKData['hitcount'])));
			}
			else {
				// we don't know the number of results: display a general text
				$opacLink->appendChild($doc->createTextNode($this->localise('Treffer anzeigen', $language)));
			}
			$opacLink->setAttribute('class', 'opacLink');
		}

		return $opacLink;
	}



	/**
	 * Returns DOMElement with complete markup for linking to the OPAC entry.
	 * Link text is the GOK record’s name.
	 *
	 * @author Sven-S. Porst
	 * @param Array $GOKData GOK record
	 * @param DOMDocument $doc document used to create the resulting element
	 * @param string $language ISO 639-1 language code
	 * @return DOMElement
	 */
	private function OPACLinkElementUgly ($GOKData, $doc, $language) {
		$opacLink = $doc->createElement('a');
		$hitCount = $GOKData['hitcount'];
		$URL = $this->opacGOKSearchURL($GOKData, $language);
		if ($hitCount != 0 && $URL) {
			$opacLink->setAttribute('href', $URL);

			if ($hitCount > 0) {
				// we know the number of results: display it
				$opacLink->setAttribute('title', sprintf($this->localise('%d Treffer anzeigen', $language), $GOKData['hitcount']));
			}
			else {
				// we don't know the number of results: display a general text
				$opacLink->setAttribute('title', $this->localise('Treffer anzeigen', $language));
			}

			// Question: Is '_blank' a good idea?
			$opacLink->setAttribute('target', '_blank');
			$opacLink->setAttribute('class', 'opacLink');
		}

		return $opacLink;
	}

	
	
	/**
	 * Returns URL string pointing to an Opac search in the current language
	 * for the given GOK record or Null if there is no search query.
	 *
	 * @author Sven-S. Porst
	 * @param Array $GOKData GOK record
	 * @param string $language ISO 639-1 language code
	 * @return string|Null URL
	 */
	private function opacGOKSearchURL($GOKData, $language) {
		$GOKSearchURL = Null;

		if ($GOKData['search']) {
			// Convert CCL string to Opac-style search string and escape.
			$searchString = urlencode(str_replace('=', ' ', $GOKData['search']));

			$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nkwgok']);

			$picaLanguageCode = 'DU';
			if ($language == 'en') {
				$picaLanguageCode = 'EN';
			}

			$GOKSearchURL = $conf['opacBaseURL'] . 'LNG=' . $picaLanguageCode
				. '/REC=1/CMD?ACT=SRCHA&IKT=1016&SRT=YOP&TRM=' . $searchString;
		}
		
		return $GOKSearchURL;
	}

	
	
	/**
	 * Helper function to add our default stylesheet or the one at the path
	 * set up in Extension Manager configuration to the page’s head.
	 * 
	 * @author Sven-S. Porst
	 * @return void 
	 */
	private function addStylesheet () {
		$nkwgokGlobalConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nkwgok']);
		$cssPath = $nkwgokGlobalConf['CSSPath'];
		if (!$cssPath) {
			$cssPath = 'EXT:' . $this->extKey . '/res/nkwgok.css';
		}
		
		$GLOBALS['TSFE']->pSetup['includeCSS.'][$this->extKey] = $cssPath;
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
	 * - an array element 'getVars' with:
	 *   * an array element 'expand'. Each of that array’s elements are PPNs of
	 *     the GOK elements displaying their child elements
	 *   * an array element 'style' indicating the style (treeNew or treeOld)
	 *     to be used
	 *   * an array element 'showGOKID' indicating whether GOK IDs are shown or hidden
	 *
	 * @author Sven-S. Porst
	 * @param Array $conf
	 * @return DOMDocument
	 */
	public function GOKTree ($conf) {
		$language = $GLOBALS['TSFE']->lang;
		$objectID = $this->cObj->data['uid'];

		$doc = DOMImplementation::createDocument();
		$this->addGOKTreeJSToElement($doc, $doc, $conf['getVars']['style'], $language, $objectID);

		$this->addStylesheet();

		// Get start node.
		$firstNodeCondition = "gok LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr($conf['gok'], NKWGOKQueryTable);
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

			$containerClasses = Array('gokContainer', 'tree');
			if ($conf['getVars']['style'] != 'treeOld') {
				$containerClasses[] = 'newStyle';
			}

			if (!$conf['getVars']['showGOKID']) {
				$containerClasses[] = 'hideGOKID';
			}
			$container->setAttribute('class', implode(' ', $containerClasses));

			$nameSpan = $doc->createElement('span');
			$container->appendChild($nameSpan);
			$nameSpan->setAttribute('class', 'GOKName');
			$nameSpan->appendChild($doc->createTextNode($this->GOKName($GOK, $language, True)));

			$this->appendGOKTreeChildren($GOK['ppn'], $doc, $container, $language, $objectID, $conf['getVars']['expand'], '', 1, $conf['getVars']['style']);
		}

		return $doc;
	}



	/**
	 * Helper function to insert JavaScript for the GOK Tree into the passed
	 * $element.
	 *
	 * It seems we need to pass the DOMDocument here as using $element->ownerDocument
	 * doesn't seem to work if $element is the DOMDocument itself.
	 *
	 * @author Sven-S. Porst
	 * @param DOMElement $element the <script> tag is inserted into
	 * @param DOMDocument $doc the containing document
	 * @param string $style the display style to use ('treeNew' or 'treeOld')
	 * @param string $language ISO 369-1 language code
	 * @param string $objectID ID of Typo3 content object
	 */
	private function addGOKTreeJSToElement ($element, $doc, $style, $language, $objectID) {
		$scriptElement = $doc->createElement('script');
		$doc->appendChild($scriptElement);
		$scriptElement->setAttribute('type', 'text/javascript');

		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][NKWGOKExtKey]);

		$js = "
		function swapTitles" . $objectID . " (element) {
			var jQElement = jQuery(element);
			var otherTitle = jQElement.attr('alttitle');
			jQElement.attr('alttitle', jQElement.attr('title'));
			jQElement.attr('title', otherTitle);
		}
		function expandGOK" . $objectID . " (id) {
			var link = jQuery('#openCloseLink-" . $objectID ."-' + id);
			var plusMinus = jQuery('.plusMinus', link);
			swapTitles" . $objectID . "(link);
			plusMinus.text('[*]');
			var functionText = 'hideGOK" . $objectID . "(\"' + id + '\");return false;';
			link[0].onclick = new Function(functionText);
			jQuery.get("
				. "'" . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . "index.php',
				{'eID': '" . NKWGOKExtKey . "', "
				. "'tx_" . NKWGOKExtKey . "[language]': '" . $language . "', "
				. "'tx_" . NKWGOKExtKey . "[expand]': id, "
				. "'tx_" . NKWGOKExtKey . "[style]': '" . $style . "', "
				. "'tx_" . NKWGOKExtKey . "[objectID]': '" . $objectID . "'},
				function (html) {
					plusMinus.text('[-]');
					jQuery('#c" . $objectID . "-' + id).append(html);
				}
			);
		};
		function hideGOK". $objectID . " (id) {
			jQuery('#ul-" . $objectID . "-' + id).remove();
			var link = jQuery('#openCloseLink-" . $objectID . "-' + id);
			jQuery('.plusMinus', link).text('[+]');
			swapTitles" . $objectID . "(link);
			var	functionText = 'expandGOK" . $objectID . "(\"' + id + '\");return false;';
			link[0].onclick = new Function(functionText);
		};
";
		$scriptElement->appendChild($doc->createTextNode($js));
	}



	/**
	 * Looks up child elements for the given $parentPPN,
	 * creates a list with markup for them
	 * and adds them to the given $container element inside $doc,
	 * taking into account which parent elements are configured to display their
	 * children.
	 *
	 * @author Nils K. Windisch
	 * @author Sven-S. Porst
	 * @param string $parentPPN
	 * @param DOMDocument $doc document used to create the resulting element
	 * @param DOMElement $container the created markup is appended to (needs to be a child element of $doc)
	 * @param string $language ISO 639-1 language code
	 * @param string $objectID ID of Typo3 content object
	 * @param string $expandMarker list of PPNs of open parent elements, separated by '-' [defaults to '']
	 * @param int $autoExpandLevel automatically expand subentries if they have at most this many child elements [defaults to 0]
	 * @param string $style of the tree (treeNew or treeOld) [defaults to treeNew]
 	 * @return void
	 * */
	private function appendGOKTreeChildren($parentPPN, $doc, $container, $language, $objectID, $expandInfo, $expandMarker = '', $autoExpandLevel = 0, $style = 'treeNew') {
		$GOKs = $this->getChildren($parentPPN);
		if (sizeof($GOKs) > 0) {
			$ul = $doc->createElement('ul');
			$container->appendChild($ul);
			$ul->setAttribute('id', 'ul-' . $objectID . '-' . $parentPPN);

			foreach ($GOKs as $GOK) {
				$PPN = $GOK['ppn'];
				$expand = $PPN;

				if ($expandMarker != '') {
					$expand = $expandMarker . '-' . $PPN;
				}

				/* Display in each list item:
				 * 1. Expand xor collapse link if there are child elements depending on the expanded state
				 * 2. The linked name
				 * 3. If the item has child elements and is expanded, the list of child elements
				 */
				$li = $doc->createElement('li');
				$ul->appendChild($li);
				$li->setAttribute('id', 'c' . $objectID . '-' . $PPN);

				$openLink = $doc->createElement('a');
				$openLink->setAttribute('id', 'openCloseLink-' . $objectID . '-' . $PPN);
				$li->appendChild($openLink);

				$control = $doc->createElement('span');
				$openLink->appendChild($control);
				$control->setAttribute('class', 'plusMinus');

				$GOKIDSpan = $doc->createElement('span');
				$GOKIDSpan->setAttribute('class', 'GOKID');
				$GOKIDSpan->appendChild($doc->createTextNode($GOK['gok']));

				$GOKNameSpan = $doc->createElement('span');
				$GOKNameSpan->setAttribute('class', 'GOKName');
				$GOKNameSpan->appendChild($doc->createTextNode($this->GOKName($GOK, $language, True)));

				/* Offer new and old/ugly display styles:
				 * NEW: * Link around +/- and the GOK and the subject name for opening/closing.
				 *      * Link to a separate element pointing to Opac results. If possible the number of hits is displayed.
				 * OLD: * Link around +/- only for opening/closing.
				 *      * Link around the GOK and subject name pointing to Opac results.
				 */
				if ($style != 'treeOld') {
					$openLink->appendChild($doc->createTextNode(' '));
					$openLink->appendChild($GOKIDSpan);
					$openLink->appendChild($doc->createTextNode(' '));
					$openLink->appendChild($GOKNameSpan);
					$opacLinkElement = $this->OPACLinkElement($GOK, $doc, $language);
					if ($opacLinkElement) {
						$li->appendChild($doc->createTextNode(' '));
						$li->appendChild($opacLinkElement);
					}
				}
				else {
					$li->appendChild($doc->createTextNode(' '));
					$opacLinkElement = $this->OPACLinkElementUgly($GOK, $doc, $language);
					$li->appendChild($opacLinkElement);
					$opacLinkElement->appendChild($GOKIDSpan);
					$opacLinkElement->appendChild($doc->createTextNode(' '));
					$opacLinkElement->appendChild($GOKNameSpan);
				}

				// Careful: These are three non-breaking spaces to get better alignment.
				$buttonText = '   ';
				if ($GOK['childcount'] > 0) {
					$JSCommand = '';
					$noscriptLink = '#';
					$mainTitle = $GOK['childcount'] . ' ' . $this->localise('Unterkategorien anzeigen', $language);
					$alternativeTitle = $this->localise('Unterkategorien ausblenden', $language);
					
					if ( ($expandInfo && in_array($PPN, $expandInfo))
							|| $GOK['childcount'] <= $autoExpandLevel) {
						$li->setAttribute('class', 'close');
						$JSCommand = 'hideGOK' . $objectID;
						$buttonText = '[-]';
						$tmpTitle = $mainTitle;
						$mainTitle = $alternativeTitle;
						$alternativeTitle = $tmpTitle;
						$noscriptLink = t3lib_div::linkThisUrl(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),
								array('tx_' . NKWGOKExtKey . '[expand]' => $expandMarker, 'tx_' . NKWGOKExtKey . '[style]' => $style) );
						
						// recursively call self to get child UL
						$this->appendGOKTreeChildren($PPN, $doc, $li, $language, $objectID, $expandInfo, $expand, $autoExpandLevel, $style);
					}
					else {
						$li->setAttribute('class', 'open');
						$JSCommand = 'expandGOK' . $objectID;
						$buttonText = '[+]';
						$linkTitle = $GOK['childcount'] . ' ' . $this->localise('Unterkategorien anzeigen', $language);
						$noscriptLink = t3lib_div::linkThisUrl(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),
								array('tx_' . NKWGOKExtKey . '[expand]' => $expand, 'no_cache' => 1) )
								. '#c' . $PPN;
					}

					$openLink->setAttribute('onclick',  $JSCommand . '("' . $PPN . '");return false;');
					$openLink->setAttribute('href', $noscriptLink);
					$openLink->setAttribute('title', $mainTitle);
					$openLink->setAttribute('alttitle', $alternativeTitle);
				}

				$control->appendChild($doc->createTextNode($buttonText));
			}
		}
	}



	/**
	 * Create DOMDocument for AJAX return value and fill it with markup for the
	 * parent PPN and language given.

	 * @author Sven-S. Porst
	 * @param string $parentPPN
	 * @param string $style ('treeOld' or 'treeNew')
	 * @param string $language ISO 639-1 language code
	 * @param string $objectID ID of Typo3 content object
	 * @return DOMDocument
	 * */
	public function AJAXGOKTreeChildren ($parentPPN, $style, $language, $objectID) {
		$doc = DOMImplementation::createDocument();
		
		$this->appendGOKTreeChildren($parentPPN, $doc, $doc, $language, $objectID, Array($parentPPN), '', 1, $style)->firstChild;

		return $doc;
	}



	/**
	 * Returns markup for GOK menus using the parameters passed in $conf.
	 *
	 * @author Sven-S. Porst
	 * @param Array $conf
	 * @return DOMElement containing the markup for a menu
	 */
	public function GOKMenus ($conf) {
		$language = $GLOBALS['TSFE']->lang;
		$objectID = $this->cObj->data['uid'];
		
		$doc = DOMImplementation::createDocument();
		$this->addGOKMenuJSToElement($doc, $doc, $language, $objectID);

		$this->addStylesheet();

		// Create the form and insert the first menu.
		$container = $doc->createElement('div');
		$doc->appendChild($container);
		$container->setAttribute('class', 'gokContainer menu');
		$form = $doc->createElement('form');
		$container->appendChild($form);
		$form->setAttribute('class', 'gokMenuForm no-JS');
		$form->setAttribute('method', 'get');
		$form->setAttribute('action', $this->pi_getPageLink($GLOBALS['TSFE']->id));
		
		$pageID = $doc->createElement('input');
		$form->appendChild($pageID);
		$pageID->setAttribute('type', 'hidden');
		$pageID->setAttribute('name', 'id');
		$pageID->setAttribute('value', $GLOBALS['TSFE']->id);
		
		$firstNodeCondition = "gok LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr($conf['gok'], NKWGOKQueryTable);
		// run query and collect result
		$queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					NKWGOKQueryFields,
					NKWGOKQueryTable,
					$firstNodeCondition,
					'',
					'gok ASC',
					'');

		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
			$this->appendGOKMenuChildren($row['ppn'], $doc, $form, $language, $objectID, $conf['getVars'], 2);
		}

		$button = $doc->createElement('input');
		$button->setAttribute('type', 'submit');
		$form->appendChild($button);

		return $doc;
	}


	
	/**
	 * Helper function to insert JavaScript for the GOK Menu into the passed
	 * $element.
	 *
	 * It seems we need to pass the DOMDocument here as using $element->ownerDocument
	 * doesn't seem to work if $element is the DOMDocument itself.
	 *
	 * @author Sven-S. Porst
	 * @param DOMElement $element the <script> tag is inserted into
	 * @param DOMDocument $doc the containing document
	 * @param string $language ISO 639-1 language code
	 * @param string $objectID ID of Typo3 content object
	 */
	private function addGOKMenuJSToElement ($element, $doc, $language, $objectID) {
		$scriptElement = $doc->createElement('script');
		$element->appendChild($scriptElement);
		$scriptElement->setAttribute('type', 'text/javascript');

		$js = "
		jQuery(document).ready(function() {
			jQuery('.gokMenuForm input[type=\'submit\']').hide();
		});
		
		function GOKMenuSelectionChanged" . $objectID . " (menu) {
			var selectedOption = menu.options[menu.selectedIndex];
			jQuery(menu).nextAll().remove();
			if (selectedOption.hasAttribute('haschildren') && !selectedOption.hasAttribute('isautoexpanded')) {
				newMenuForSelection" . $objectID . "(selectedOption);
			}
			if (selectedOption.value != 'pleaseselect') {
				jQuery('option[value=\"pleaseselect\"]', menu).remove();
			}
			startSearch" . $objectID . "(selectedOption);
		}
		function newMenuForSelection" . $objectID . " (option) {
			var URL = location.protocol + '//' + location.host + location.pathname;
			var PPN = option.value;
			var level = parseInt(option.parentNode.getAttribute('level')) + 1;
			var parameters = location.search.replace(/^\?/, '') + '&tx_" . NKWGOKExtKey . "[expand]=' + PPN
				+ '&tx_" . NKWGOKExtKey . "[language]=" . $language . "&eID=" . NKWGOKExtKey . "'
				+ '&tx_" . NKWGOKExtKey . "[level]=' + level
				+ '&tx_" . NKWGOKExtKey . "[style]=menu'
				+ '&tx_" . NKWGOKExtKey . "[objectID]=" . $objectID . "';

			jQuery(option.parentNode).nextAll().remove();
			var emptySelect = document.createElement('select');
			option.form.appendChild(emptySelect);
			var emptyOption = document.createElement('option');
			emptySelect.appendChild(emptyOption);
			emptyOption.appendChild(document.createTextNode('" . $this->localise('Laden ...', $language) . "'));
			var downloadFinishedFunction = function (html) {
				jQuery(option.parentNode).nextAll().remove();
				var form =  jQuery(option.form);
				form.append(html);
				jQuery('select:last-child option:first-child', form)[0].setAttribute('query', option.getAttribute('query'));
			};
			jQuery.get(URL, parameters, downloadFinishedFunction);
		}
		function startSearch" . $objectID . " (option) {
			nkwgokMenuSelected(option);
		}
";
		$scriptElement->appendChild($doc->createTextNode($js));
	}

	

	/**
	 * Looks up child elements for the given $parentPPN, creates DOM elements
	 * for a popup menu containing the child elements and adds them to the
	 * given $container element inside $doc, taking into account which
	 * menu items are configured to be selected.
	 *
	 * Also tries to include short (as in at most the length of $autoExpandLevel)
	 * submenus in higher level menus, adding an indent to their titles.
	 *
	 * @author Sven-S. Porst
	 * @param string $parentPPN
	 * @param DOMDocument $doc document used to create the resulting element
	 * @param DOMElement $container the created markup is appended to (needs to be a child element of $doc). Is expected to be a <select> element if the $autoExpandStep paramter is not 0 and a <form> element otherwise.
	 * @param string $language ISO 639-1 language code
	 * @param string $objectID ID of Typo3 content object
	 * @param Array $getVars entries for keys tx_nkwgok[expand-#] for an integer # are the selected items on level #
	 * @param int $autoExpandLevel automatically expand subentries if they have at most this many child elements [defaults to 0]
	 * @param int $level the depth in the menu hierarchy [defaults to 0]
	 * @param int $autoExpandStep the depth of auto-expansion [defaults to 0]
	 */
	private function appendGOKMenuChildren($parentPPN, $doc, $container, $language, $objectID, $getVars, $autoExpandLevel = 0, $level = 0, $autoExpandStep = 0) {
		$GOKs = $this->getChildren($parentPPN);
		if (sizeof($GOKs) > 0) {
			if ( (sizeof($GOKs) <= $autoExpandLevel) && ($level != 0) && $autoExpandStep == 0 ) {
				// We are auto-expanded, so throw away the elements, as they are already present in the previous menu
				$GOKs = Array();
			}

			// When auto-expanding, continue using the previous <select>
			// Element which should be passed to us as $container.
			$select = $container;
			
			if ($autoExpandStep == 0) {
				// Create the containing <select> when we’re not auto-expanding.
				$select = $doc->createElement('select');
				$container->appendChild($select);
				$select->setAttribute('id', 'select-' . $objectID . '-' . $parentPPN);
				$select->setAttribute('name', 'tx_' . NKWGOKExtKey . '[expand-' . $level . ']');
				$select->setAttribute('onchange', 'GOKMenuSelectionChanged' . $objectID . '(this);return false;');
				$select->setAttribute('level', $level);

				// add dummy item at the beginning of the menu
				if ($level == 0) {
					$option = $doc->createElement('option');
					$select->appendChild($option);
					$option->appendChild($doc->createTextNode($this->localise('Bitte Fachgebiet auswählen:', $language) ));
					$option->setAttribute('value', 'pleaseselect');
				}
				else {
					/* Add general menu item(s).
					 * A menu item searching for all subjects beneath the selected one in the 
					 * hierarchy and one searching for records matching exactly the subject selected.
					 * The latter case is only expected to happen for subjects coming from Opac GOK
					 * records.
					 */
					$option = $doc->createElement('option');
					$select->appendChild($option);
					$label = '';
					if ($GOKs[0]['fromopac']) {
						$label = 'Treffer für diese Zwischenebene zeigen';
					}
					else {
						$label = 'Treffer aller enthaltenen Untergebiete zeigen';
					}
					$option->appendChild($doc->createTextNode($this->localise($label, $language)));
					$option->setAttribute('value', 'withchildren');
					if (!$getVars['expand-' . $level]) {
						$option->setAttribute('selected', 'selected');
					}

					if (count($GOKs) > 0) {
						$optgroup = $doc->createElement('optgroup');
						$select->appendChild($optgroup);
					}
				}
			}

			foreach ($GOKs as $GOK) {
				$PPN = $GOK['ppn'];

				$option = $doc->createElement('option');
				$select->appendChild($option);
				$option->setAttribute('value', $PPN);
				$option->setAttribute('query', $GOK['search']);
				// Careful: non-breaking spaces used here to create in-menu indentation
				$menuItemString = str_repeat('   ', $autoExpandStep) . $this->GOKName($GOK, $language, True);
				if ($GOK['childcount'] > 0) {
					$menuItemString .= $this->localise(' ...', $language);
					$option->setAttribute('hasChildren', $GOK['childcount']);
				}
				$option->appendChild($doc->createTextNode($menuItemString));
				if ($GOK['childcount'] <= $autoExpandLevel) {
					$option->setAttribute('isAutoExpanded', '');
					$this->appendGOKMenuChildren($PPN, $doc, $select, $language, $objectID, $getVars, $autoExpandLevel, $level, $autoExpandStep + 1);
				}

				if ( $PPN == $getVars['expand-' . $level] ) {
					// this item should be selected and the next menu should be added
					$option->setAttribute('selected', 'selected');
					$this->appendGOKMenuChildren($PPN, $doc, $container, $language, $objectID, $getVars, $autoExpandLevel, $level + 1);
					// remove the first/default item of the menu if we have a selection already
				}
			}
		}
	}


	
	/**
	 * Create DOMDocument for AJAX return value and fill it with markup for the
	 * $parentPPN, $level and $language given.
	 *
	 * @author Sven-S. Porst
 	 * @param string $parentPPN
	 * @param int $level the depth in the menu hierarchy [defaults to 0]
	 * @param string $language ISO 639-1 language code
	 * @param string $objectID ID of Typo3 content object
	 * @return <type>
	 */
	public function AJAXGOKMenuChildren ($parentPPN, $level, $language, $objectID) {
		$doc = DOMImplementation::createDocument();
		$this->appendGOKMenuChildren($parentPPN, $doc, $doc, $language, $objectID, Array(), 2, $level);

		return $doc;
	}

}

?>
