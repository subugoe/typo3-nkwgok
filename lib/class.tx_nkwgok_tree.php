<?php
/*******************************************************************************
 * Copyright notice
 *
 * Copyright (C) 2010 by Nils K. Windisch, SUB Göttingen
 * <windisch@sub.uni-goettingen.de
 * Copyright (C) 2011-2012 by Sven-S. Porst, SUB Göttingen
 * <porst@sub.uni-goettingen.de>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 ******************************************************************************/



/**
 * class.tx_nkwgok_tree.php
 *
 * Subclass of tx_nkwgok that creates markup for a subject hierarchy as a tree.
 */
class tx_nkwgok_tree extends tx_nkwgok {

	/**
	 * Returns markup for GOK tree based on the configuration in $this->arguments.
	 *
	 * The $this->arguments array needs to contain:
	 * - a string element 'gok' which can either be 'all' (to display the
	 *		complete GOK tree) or a GOK string of the node to be used as the
	 *		root of the tree
	 * - an array element 'getVars' with:
	 *   * an array element 'expand'. Each of that array’s elements are PPNs of
	 *     the GOK elements displaying their child elements
	 *   * an array element 'showGOKID' indicating whether GOK IDs are shown or hidden
	 *
	 * @author Sven-S. Porst
	 * @return DOMDocument
	 */
	public function getMarkup () {
		$language = $GLOBALS['TSFE']->lang;

		$doc = DOMImplementation::createDocument();
		$this->addGOKTreeJSToElement($doc, $doc, $language, $this->arguments['objectID']);

		// Get start node.
		$firstNodeCondition = "gok LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->arguments['gok'], NKWGOKQueryTable) . ' AND statusID = 0';
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
			if (!$this->arguments['showGOKID']) {
				$containerClasses[] = 'hideGOKID';
			}
			if ($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nkwgok_pi1.']['shallowSearch'] == 1) {
				$containerClasses[] = 'shallowLinks';
			}
			$container->setAttribute('class', implode(' ', $containerClasses));

			$topElement = $this->appendGOKTreeItem($doc, $container, 'span', $GOK, $language, $this->arguments['objectID'], $this->arguments['expand'], '', 1, False);
			$topElement->setAttribute('class', 'rootNode');

			$this->appendGOKTreeChildren($GOK['ppn'], $doc, $container, $language, $this->arguments['objectID'], $this->arguments['expand'], '', 1);
		}

		return $doc;
	}



	/**
	 * Returns markup for GOK menus based on the configuration in $this->arguments.
	 *
	 * @author Sven-S. Porst
	 * @return DOMDocument
	 */
	public function getAJAXMarkup () {
		$doc = DOMImplementation::createDocument();

		$parentPPN = $this->arguments['expand'];
		$language = $this->arguments['language'];
		$objectID = $this->arguments['objectID'];

		$this->appendGOKTreeChildren($parentPPN, $doc, $doc, $language, $objectID, Array($parentPPN), '', 1)->firstChild;

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
	 * @param string $language ISO 369-1 language code
	 * @param string $objectID ID of Typo3 content object
	 * @return void
	 */
	private function addGOKTreeJSToElement ($element, $doc, $language, $objectID) {
		$scriptElement = $doc->createElement('script');
		$doc->appendChild($scriptElement);
		$scriptElement->setAttribute('type', 'text/javascript');

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
				. "'tx_" . NKWGOKExtKey . "[style]': 'tree', "
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
	 * @param Array $expandInfo information which PPNs need to be expanded
	 * @param string $expandMarker list of PPNs of open parent elements, separated by '-'
	 * @param int $autoExpandLevel automatically expand subentries if they have at most this many child elements
	 * @return void
	 * */
	private function appendGOKTreeChildren($parentPPN, $doc, $container, $language, $objectID, $expandInfo, $expandMarker, $autoExpandLevel) {
		$GOKs = $this->getChildren($parentPPN, True);
		if (sizeof($GOKs) > 1) {
			$ul = $doc->createElement('ul');
			$container->appendChild($ul);
			$ul->setAttribute('id', 'ul-' . $objectID . '-' . $parentPPN);

			/* The first item in the array is the parent element. Fetch it
			 * and
			 */
			$firstGOK = array_shift($GOKs);
			if ($firstGOK['hitcount'] > 0) {
				$firstGOK['descr'] = $this->localise('Allgemeines', $language);
				$this->appendGOKTreeItem($doc, $ul, 'li', $firstGOK, $language, $objectID, $expandInfo, $expandMarker, $autoExpandLevel, False, 'general-items-node');
			}

			foreach ($GOKs as $GOK) {
				/* Do not display the GOK item if
				 * 1. it has no child elements and
				 * 2. it is known to have no matching hits
				 */
				if ($GOK['hitcount'] != 0 || $GOK['childcount'] != 0) {
					$this->appendGOKTreeItem($doc, $ul, 'li', $GOK, $language, $objectID, $expandInfo, $expandMarker, $autoExpandLevel);
				}
			} // end foreach ($GOKs as $GOK)
		}
	}



	/**
	 * Appends a single GOK item child element of typ3 $elementName
	 * to the element $container inside $doc and returns it.
	 *
	 * @author Sven-S. Porst
	 * @param DOMDocument $doc document used to create the resulting element
	 * @param DOMElement $container the created markup is appended to (needs to be a child element of $doc)
	 * @param string $elementName name of the element to insert into $container
	 * @param Array $GOK
	 * @param string $language ISO 639-1 language code
	 * @param string $objectID ID of Typo3 content object
	 * @param Array $expandInfo information which PPNs need to be expanded
	 * @param string $expandMarker list of PPNs of open parent elements, separated by '-' [defaults to '']
	 * @param int $autoExpandLevel automatically expand subentries if they have at most this many child elements [defaults to 0]
	 * @param Boolean $isInteractive whether the element can be an expandable part of the tree and should have dynamic links [defaults to True]
	 * @param string|Null $extraClass class added to the appended links [defaults to Null]
	 * @return DOMElement
	 */
	private function appendGOKTreeItem ($doc, $container, $elementName, $GOK, $language, $objectID, $expandInfo, $expandMarker, $autoExpandLevel, $isInteractive = True, $extraClass = Null) {
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
		$item = $doc->createElement($elementName);
		$container->appendChild($item);
		$item->setAttribute('id', 'c' . $objectID . '-' . $PPN);

		$openLink = $doc->createElement('a');
		$openLink->setAttribute('id', 'openCloseLink-' . $objectID . '-' . $PPN);
		$item->appendChild($openLink);

		$control = $doc->createElement('span');
		$openLink->appendChild($control);
		$openLinkClass = 'plusMinus';
		if ($isInteractive !== True) {
			$openLinkClass .= ' nkwgok-invisible';
		}
		$control->setAttribute('class', $openLinkClass);

		$GOKIDSpan = $doc->createElement('span');
		$GOKIDSpan->setAttribute('class', 'GOKID');
		$GOKIDSpan->appendChild($doc->createTextNode($GOK['gok']));

		$GOKNameSpan = $doc->createElement('span');
		$GOKNameSpan->setAttribute('class', 'GOKName');
		$GOKNameSpan->appendChild($doc->createTextNode($this->GOKName($GOK, $language, True)));

		$openLink->appendChild($doc->createTextNode(' '));
		$openLink->appendChild($GOKIDSpan);
		$openLink->appendChild($doc->createTextNode(' '));
		$openLink->appendChild($GOKNameSpan);
		$this->appendOpacLinksTo($GOK, $doc, $language, $item);

		$itemClass = '';
		if ($extraClass !== Null) {
			$itemClass = $extraClass . ' ';
		}

		$buttonText = '   ';
		if ($isInteractive === True) {
			// Careful: These are three non-breaking spaces to get better alignment.
			if ($GOK['childcount'] > 0) {
				$JSCommand = '';
				$noscriptLink = '#';
				$mainTitle = $GOK['childcount'] . ' ' . $this->localise('Unterkategorien anzeigen', $language);
				$alternativeTitle = $this->localise('Unterkategorien ausblenden', $language);

				if ( ($expandInfo && in_array($PPN, $expandInfo)) || $GOK['childcount'] <= $autoExpandLevel) {
					$itemClass .= 'close';
					$JSCommand = 'hideGOK' . $objectID;
					$buttonText = '[-]';
					$tmpTitle = $mainTitle;
					$mainTitle = $alternativeTitle;
					$alternativeTitle = $tmpTitle;
					$noscriptLink = t3lib_div::linkThisUrl(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),
							array('tx_' . NKWGOKExtKey . '[expand]' => $expandMarker) );

					// recursively call self to get child UL
					$this->appendGOKTreeChildren($PPN, $doc, $item, $language, $objectID, $expandInfo, $expand, $autoExpandLevel);
				}
				else {
					$itemClass .= 'open';
					$JSCommand = 'expandGOK' . $objectID;
					$buttonText = '[+]';
					$noscriptLink = t3lib_div::linkThisUrl(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),
							array('tx_' . NKWGOKExtKey . '[expand]' => $expand, 'no_cache' => 1) )
							. '#c' . $PPN;
				}

				$openLink->setAttribute('onclick',  $JSCommand . '("' . $PPN . '");return false;');
				$openLink->setAttribute('href', $noscriptLink);
				$openLink->setAttribute('rel', 'nofollow');
				$openLink->setAttribute('title', $mainTitle);
				$openLink->setAttribute('alttitle', $alternativeTitle);
			}
		}

		$item->setAttribute('class', $itemClass);

		$control->appendChild($doc->createTextNode($buttonText));

		return $item;
	}



	/**
	 * Appends two Opac search links to $container, one for shallow search and
	 * one for deep search. One of them will be hidden by CSS.
	 *
	 * @author Sven-S. Porst
	 * @param Array $GOK GOK record
	 * @param DOMDocument $doc document used to create the resulting element
	 * @param string $language ISO 639-1 language code
	 * @param DOMElement $container the link elements are appended to
	 */
	private function appendOpacLinksTo ($GOK, $doc, $language, $container) {
		$opacLinkElement = $this->OPACLinkElement($GOK, $doc, $language, True);
		if ($opacLinkElement) {
			$container->appendChild($doc->createTextNode(' '));
			$container->appendChild($opacLinkElement);
		}

		$opacLinkElement = $this->OPACLinkElement($GOK, $doc, $language, False);
		if ($opacLinkElement) {
			$container->appendChild($doc->createTextNode(' '));
			$container->appendChild($opacLinkElement);
		}
	}



	/**
	 * Returns DOMElement with complete markup for linking to the OPAC entry.
	 * The link text indicates the number of results if it is known.
	 *
	 * @author Sven-S. Porst
	 * @param Array $GOKData GOK record
	 * @param DOMDocument $doc document used to create the resulting element
	 * @param string $language ISO 639-1 language code
	 * @param Boolean $deepSearch
	 * @return DOMElement
	 */
	private function OPACLinkElement ($GOKData, $doc, $language, $deepSearch) {
		$opacLink = Null;
		$hitCount = $GOKData['hitcount'];
		$useDeepSearch = $deepSearch && ($GOKData['totalhitcount'] > 0);
		if ($useDeepSearch === True) {
			$hitCount = $GOKData['totalhitcount'];
		}
		$URL = $this->opacGOKSearchURL($GOKData, $language, $deepSearch);
		if ($hitCount != 0 && $URL) {
			$opacLink = $doc->createElement('a');
			$opacLink->setAttribute('href', $URL);
			$titleString = '';
			if ($useDeepSearch === True && $GOKData['childcount'] != 0) {
				$titleString = $this->localise('Bücher zu diesem und enthaltenen Themengebieten im Opac anzeigen', $language);
			}
			else {
				$titleString = $this->localise('Bücher zu genau diesem Thema im Opac anzeigen', $language);
			}
			$opacLink->setAttribute('title', $titleString);

			// Question: Is '_blank' a good idea?
			$opacLink->setAttribute('target', '_blank');
			if ($hitCount > 0) {
				// we know the number of results: display it
				$numberString = number_format($hitCount, 0, $this->localise('decimal separator', $language), $this->localise('thousands separator', $language));
				$opacLink->appendChild($doc->createTextNode(sprintf($this->localise('%s Treffer anzeigen', $language), $numberString)));
			}
			else {
				// we don't know the number of results: display a general text
				$opacLink->appendChild($doc->createTextNode($this->localise('Treffer anzeigen', $language)));
			}

			$linkClass= 'opacLink ' . (($deepSearch === True) ? 'deep' : 'shallow');
			$opacLink->setAttribute('class', $linkClass);
		}

		return $opacLink;
	}



	/**
	 * Returns URL string for an Opac Search.
	 * If $deepSearch is false, the search query stored in $GOKData is used.
	 * If $deepSearch is true, a deep hierarchical search for records related
	 * to the GOK Normsatz PPN is used
	 * If the record did not originate from Opac, Null is returned.
	 *
	 * @author Sven-S. Porst
	 * @param Array $GOKData GOK record
	 * @param string $language ISO 639-1 language code
	 * @param Boolean $deepSearch
	 * @return string|Null URL
	 */
	private function opacGOKSearchURL($GOKData, $language, $deepSearch) {
		$GOKSearchURL = Null;

		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nkwgok']);
		$picaLanguageCode = ($language === 'en') ? 'EN' : 'DU';
		$GOKSearchURL = $conf['opacBaseURL'] . 'LNG=' . $picaLanguageCode;

		if ($deepSearch === True && $GOKData['fromopac'] == 1 && $GOKData['ppn'] !== 'GOK-Root') {
			// Use special command to do the hierarchical search for records related
			// to the Normsatz PPN.
			$GOKSearchURL .= '/EPD?PPN=' . $GOKData['ppn'] . '&FRM=';
		}
		else if ($GOKData['search']) {
			// Convert CCL string to Opac-style search string and escape.
			$searchString = urlencode(str_replace('=', ' ', $GOKData['search']));
			$GOKSearchURL .= '/REC=1/CMD?ACT=SRCHA&IKT=1016&SRT=YOP&TRM=' . $searchString;
		}
		else {
			$GOKSearchURL = Null;
		}

		return $GOKSearchURL;
	}

}

?>
