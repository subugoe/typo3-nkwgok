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
	 * - a string element 'style' which may take tha values 'tree' or 'column'
	 * - an array element 'expand' listing the IDs of subjects to be expanded
	 *
	 * Further array elements can be:
	 * - 'showGOKID', determining whether the subject’s ID is displayed along
	 *		with its name
	 *
	 * @author Sven-S. Porst
	 * @return DOMDocument
	 */
	public function getMarkup () {
		$this->addGOKTreeJSToElement($this->doc);

		// Create container.
		$container = $this->doc->createElement('div');
		$this->doc->appendChild($container);

		$containerClasses = Array('gokContainer', $this->arguments['style']);
		if (!$this->arguments['showGOKID']) {
			$containerClasses[] = 'hideGOKID';
		}

		$useShallowLinks = ($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nkwgok_pi1.']['shallowSearch'] == 1);
		if ($this->arguments['omitXXX']) {
			$useShallowLinks = TRUE;
			t3lib_div::devLog('Configured to use deep links together with omitXXX. This will not work as the totalhitcount is incorrect when we arbitrarily leave out child elements. Using shallow links instead.' , tx_nkwgok_utility::extKey, 2);
		}
		if ($useShallowLinks)  {
			$containerClasses[] = 'shallowLinks';
		}
		
		$container->setAttribute('class', implode(' ', $containerClasses));
		$container->setAttribute('id', 'tx_nkwgok-' . $this->objectID);

		// Get and insert start nodes.
		$startNodes = explode(',', $this->arguments['gok']);
		$queryParts = Array();
		foreach ($startNodes as $startNodeGOK) {
			$queryParts[] = 'gok = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(trim($startNodeGOK), tx_nkwgok_utility::dataTable);
		}
		$query = implode(' OR ', $queryParts) . ' AND statusID = 0';
		$queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					NKWGOKQueryFields,
					tx_nkwgok_utility::dataTable,
					$query,
					'',
					'gok ASC',
					'');

		$resultCount = $GLOBALS['TYPO3_DB']->sql_num_rows($queryResult);
		$topElementType = 'span';
		$topItemContainer = $container;
		if ($resultCount > 1) {
			$topElementType = 'li';
			$ul = $this->doc->createElement('ul');
			$container->appendChild($ul);
			$topItemContainer = $ul;
		}

		while ($GOK = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
			$topElement = $this->appendGOKTreeItem($topItemContainer, $topElementType, $GOK, Array(), 1, ($resultCount > 1));
			$topElement->setAttribute('class', 'rootNode');

			if ($resultCount === 1) {
				// There is a single element: display expanded tree without controls at top level.
				$this->appendGOKTreeChildren($GOK['ppn'], $container, Array(), 1);
			}
		}

		return $this->doc;
	}



	/**
	 * Returns markup for GOK menus based on the configuration in $this->arguments.
	 *
	 * @author Sven-S. Porst
	 * @return DOMDocument
	 */
	public function getAJAXMarkup () {
		$parentPPN = $this->arguments['expand'][0];

		$this->appendGOKTreeChildren($parentPPN, $this->doc, Array($parentPPN), 1);

		return $this->doc;
	}



	/**
	 * Helper function to insert JavaScript for the GOK Tree into the passed
	 * $container element.
	 *
	 * @author Sven-S. Porst
	 * @param DOMElement $container the <script> tag is inserted into
	 * @return void
	 */
	private function addGOKTreeJSToElement ($container) {
		$scriptElement = $this->doc->createElement('script');
		$container->appendChild($scriptElement);
		$scriptElement->setAttribute('type', 'text/javascript');

		$js = "
		function swapTitles" . $this->objectID . " (element) {
			var jElement = jQuery(element);
			var otherTitle = jElement.attr('alttitle');
			jElement.attr('alttitle', jElement.attr('title'));
			jElement.attr('title', otherTitle);
		}";

		$HTMLInsertionTarget = 'jContainerLI';
		if ($this->arguments['style'] === 'column') {
			$HTMLInsertionTarget = 'jQuery("#tx_nkwgok-' . $this->objectID .'")';
		}

		$js .= "
		function expandGOK" . $this->objectID . " (id) {
			var jContainerLI = jQuery('#c" . $this->objectID . "-' + id);
			selectGOK" . $this->objectID . "(id);
			jContainerLI.removeClass('open').addClass('close');
			var link = jQuery('#openCloseLink-" . $this->objectID ."-' + id);";
		if ($this->arguments['style'] === 'column') {
			$js .= "
			jContainerLI.parent().nextAll('ul').remove();";
		}
		$js .= "
			var plusMinus = jQuery('.plusMinus', link);
			swapTitles" . $this->objectID . "(link);
			plusMinus.text('[*]');
			var functionText = 'hideGOK" . $this->objectID . "(\"' + id + '\");return false;';
			link[0].onclick = new Function(functionText);
			jQuery.get("
				. "'" . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . "index.php',
				{'eID': '" . tx_nkwgok_utility::extKey . "', "
				. "'tx_" . tx_nkwgok_utility::extKey . "[language]': '" . $this->language . "', "
				. "'tx_" . tx_nkwgok_utility::extKey . "[expand][0]': id, "
				. "'tx_" . tx_nkwgok_utility::extKey . "[style]': '" . $this->arguments['style'] . "', "
				. (($this->arguments['omitXXX']) ? ("'tx_" . tx_nkwgok_utility::extKey . "[omitXXX]': '" . $this->arguments['omitXXX'] . "', ") : (''))
				. "'tx_" . tx_nkwgok_utility::extKey . "[objectID]': '" . $this->objectID . "'},
				function (html) {
					plusMinus.text('[-]');
					" . $HTMLInsertionTarget . ".append(html);
				}
			);
		}";

		$js .= "
		function hideGOK" . $this->objectID . " (id) {
			jQuery('#ul-" . $this->objectID . "-' + id).remove();
			jQuery('#c" . $this->objectID . "-' + id).removeClass('close').addClass('open');
			var link = jQuery('#openCloseLink-" . $this->objectID . "-' + id);
			jQuery('.plusMinus', link).text('[+]');
			swapTitles" . $this->objectID . "(link);
			var	functionText = 'expandGOK" . $this->objectID . "(\"' + id + '\");return false;';
			link[0].onclick = new Function(functionText);
		}";

		if ($this->arguments['style'] === 'column') {
			$js .= "
		function unselectSiblings" . $this->objectID . " (jElement) {
			jElement.siblings('.close').each( function() {
					var siblingID = this.id.substr(" . (strlen($this->objectID) + 2) . ");
					hideGOK" . $this->objectID . "(siblingID);
				}
			);
			jElement.siblings('.selected').removeClass('selected');
		}
		function selectGOK" . $this->objectID . "(id) {
			var element = document.getElementById('c" . $this->objectID . "-' + id);
			var jElement = jQuery(element);
			jElement.addClass('selected');
			unselectSiblings" . $this->objectID . "(jElement);
			if (window.nkwgokItemSelected !== undefined) {
				window.nkwgokItemSelected(element);
			}
		}";
		}
		else {
			$js .= "
		function unselectSiblings" . $this->objectID . " (jElement) { }
		function selectGOK" . $this->objectID . "(id) {}
			";
		}

		$scriptElement->appendChild($this->doc->createTextNode($js));
	}



	/**
	 * Looks up child elements for the given $parentPPN,
	 * creates a list with markup for them
	 * and adds them to the given $container element inside $this->doc,
	 * taking into account which parent elements are configured to display their
	 * children.
	 *
	 * @author Nils K. Windisch
	 * @author Sven-S. Porst
	 * @param string $parentPPN
	 * @param DOMElement $container the created markup is appended to (needs to be a child element of $this->doc)
	 * @param Array $expandMarker list of PPNs of open parent elements
	 * @param int $autoExpandLevel automatically expand subentries if they have at most this many child elements
	 * @return void
	 * */
	private function appendGOKTreeChildren($parentPPN, $container, $expandMarker, $autoExpandLevel) {
		$GOKs = $this->getChildren($parentPPN, True);
		if (sizeof($GOKs) > 1) {
			$ul = $this->doc->createElement('ul');
			$container->appendChild($ul);
			$ul->setAttribute('id', 'ul-' . $this->objectID . '-' . $parentPPN);

			// The first item in the array is the root element.
			$firstGOK = array_shift($GOKs);
			$ul->setAttribute('class', 'level-' . $firstGOK['hierarchy']);
			if ($firstGOK['hitcount'] > 0) {
				$firstGOK['descr'] = $this->localise('Allgemeines');
				$this->appendGOKTreeItem($ul, 'li', $firstGOK, $expandMarker, $autoExpandLevel, False, 'general-items-node');
			}

			foreach ($GOKs as $GOK) {
				/* Do not display the GOK item if
				 * 1. it has no child elements and
				 * 2. it is known to have no matching hits
				 */
				if ($GOK['hitcount'] != 0 || $GOK['childcount'] != 0) {
					$this->appendGOKTreeItem($ul, 'li', $GOK, $expandMarker, $autoExpandLevel);
				}
			} // end foreach ($GOKs as $GOK)
		}
	}



	/**
	 * Appends a single GOK item child element of type $elementName
	 * to the element $container inside $this->doc and returns it.
	 *
	 * @author Sven-S. Porst
	 * @param DOMElement $container the created markup is appended to (needs to be a child element of $this->doc)
	 * @param string $elementName name of the element to insert into $container
	 * @param Array $GOK
	 * @param Array $expandMarker list of PPNs of open parent elements [defaults to Array()]
	 * @param int $autoExpandLevel automatically expand subentries if they have at most this many child elements [defaults to 0]
	 * @param Boolean $isInteractive whether the element can be an expandable part of the tree and should have dynamic links [defaults to TRUE]
	 * @param string|NULL $extraClass class added to the appended links [defaults to NULL]
	 * @return DOMElement
	 */
	private function appendGOKTreeItem ($container, $elementName, $GOK, $expandMarker = Array(), $autoExpandLevel = 0, $isInteractive = TRUE, $extraClass = NULL) {
		$PPN = $GOK['ppn'];
		$expand = Array($PPN);

		if (count($expandMarker) > 0) {
			$expand = array_merge($expandMarker, $expand);
		}

		/* Display in each list item:
		 * 1. Expand xor collapse link if there are child elements depending on the expanded state
		 * 2. The linked name
		 * 3. If the item has child elements and is expanded, the list of child elements
		 */
		$item = $this->doc->createElement($elementName);
		$container->appendChild($item);
		$item->setAttribute('id', 'c' . $this->objectID . '-' . $PPN);
		$item->setAttribute('query', $GOK['search']);

		$openLink = $this->doc->createElement('a');
		$openLink->setAttribute('id', 'openCloseLink-' . $this->objectID . '-' . $PPN);
		$item->appendChild($openLink);

		$control = $this->doc->createElement('span');
		$openLink->appendChild($control);
		$openLinkClass = 'plusMinus';
		if ($isInteractive !== True) {
			$openLinkClass .= ' nkwgok-invisible';
		}
		$control->setAttribute('class', $openLinkClass);

		$GOKIDSpan = $this->doc->createElement('span');
		$GOKIDSpan->setAttribute('class', 'GOKID');
		$GOKIDSpan->appendChild($this->doc->createTextNode($GOK['gok']));

		$GOKNameSpan = $this->doc->createElement('span');
		$GOKNameSpan->setAttribute('class', 'GOKName');
		$GOKNameSpan->appendChild($this->doc->createTextNode($this->GOKName($GOK, True)));

		$openLink->appendChild($this->doc->createTextNode(' '));
		$openLink->appendChild($GOKIDSpan);
		$openLink->appendChild($this->doc->createTextNode(' '));
		$openLink->appendChild($GOKNameSpan);

		// Add Opac links when not in column mode.
		if ($this->arguments['style'] !== 'column') {
			$this->appendOpacLinksTo($GOK, $item);
		}
		// Careful: These are three non-breaking spaces to get better alignment.
		$buttonText = '   ';
		$JSCommand = '';
		$itemClass = 'nochildren';
		if ($isInteractive === True) {
			if ($GOK['childcount'] > 0) {
				$noscriptLink = '#';
				$mainTitle = sprintf($this->localise('%s Unterkategorien anzeigen'), $GOK['childcount']);
				$alternativeTitle = $this->localise('Unterkategorien ausblenden');
				$urlComponents = parse_url(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
				$baseURL = $urlComponents['scheme'] .  '://' . $urlComponents['host'] . $urlComponents['path'];
				$queryComponents = Array();
				parse_str($urlComponents['query'], $queryComponents);
				$queryComponents['no_cache'] = 1;

				if ((array_key_exists('expand', $this->arguments)
							&& is_array($this->arguments['expand'])
							&& in_array($PPN, $this->arguments['expand']))
						|| $GOK['childcount'] <= $autoExpandLevel) {
					$itemClass = 'close';
					$JSCommand = 'hideGOK' . $this->objectID;
					$buttonText = '[-]';
					$tmpTitle = $mainTitle;
					$mainTitle = $alternativeTitle;
					$alternativeTitle = $tmpTitle;
					$queryComponents['tx_nkwgok']['expand'] = $expandMarker;
					$noscriptLink = t3lib_div::linkThisUrl($baseURL, $queryComponents);

					// Recursively add opened subject list.
					// Add it into the current item for the default tree style.
					// Add it as a sibling to our parent node in column style.
					$childListContainer = $item;
					if ($this->arguments['style'] === 'column') {
						$childListContainer = $container->parentNode;
					}
					$this->appendGOKTreeChildren($PPN, $childListContainer, $expand, $autoExpandLevel);
				}
				else {
					$itemClass = 'open';
					$JSCommand = 'expandGOK' . $this->objectID;
					$buttonText = '[+]';
					$queryComponents['tx_nkwgok']['expand'] = $expand;
					$noscriptLink = t3lib_div::linkThisUrl($baseURL, $queryComponents)
						. '#c' . $this->objectID . '-' .  $PPN;
				}

				$openLink->setAttribute('href', $noscriptLink);
				$openLink->setAttribute('rel', 'nofollow');
				$openLink->setAttribute('title', $mainTitle);
				$openLink->setAttribute('alttitle', $alternativeTitle);
			}
			else if ($this->arguments['style'] === 'column') {
				$openLink->setAttribute('href', '#');
				$JSCommand = 'selectGOK' . $this->objectID;
			}
		}

		if ($JSCommand) {
			$openLink->setAttribute('onclick',  $JSCommand . '("' . $PPN . '");return false;');
		}

		if ($extraClass !== NULL) {
			$itemClass .= ' ' . $extraClass;
		}
		$item->setAttribute('class', $itemClass);

		$control->appendChild($this->doc->createTextNode($buttonText));

		return $item;
	}



	/**
	 * Appends two Opac search links to $container, one for shallow search and
	 * one for deep search. One of them will be hidden by CSS.
	 *
	 * @author Sven-S. Porst
	 * @param Array $GOK GOK record
	 * @param DOMElement $container the link elements are appended to
	 */
	private function appendOpacLinksTo ($GOK, $container) {
		$opacLinkElement = $this->OPACLinkElement($GOK, True);
		if ($opacLinkElement) {
			$container->appendChild($this->doc->createTextNode(' '));
			$container->appendChild($opacLinkElement);
		}

		$opacLinkElement = $this->OPACLinkElement($GOK, False);
		if ($opacLinkElement) {
			$container->appendChild($this->doc->createTextNode(' '));
			$container->appendChild($opacLinkElement);
		}
	}



	/**
	 * Returns DOMElement with complete markup for linking to the OPAC entry.
	 * The link text indicates the number of results if it is known.
	 *
	 * @author Sven-S. Porst
	 * @param Array $GOKData GOK record
	 * @param Boolean $deepSearch
	 * @return DOMElement
	 */
	private function OPACLinkElement ($GOKData, $deepSearch) {
		$opacLink = Null;
		$hitCount = $GOKData['hitcount'];
		$useDeepSearch = $deepSearch && ($GOKData['totalhitcount'] > 0);
		if ($useDeepSearch === True) {
			$hitCount = $GOKData['totalhitcount'];
		}
		$URL = $this->opacGOKSearchURL($GOKData, $deepSearch);
		if ($hitCount != 0 && $URL) {
			$opacLink = $this->doc->createElement('a');
			$opacLink->setAttribute('href', $URL);
			$titleString = '';
			if ($useDeepSearch === True && $GOKData['childcount'] != 0) {
				$titleString = $this->localise('Bücher zu diesem und enthaltenen Themengebieten im Opac anzeigen');
			}
			else {
				$titleString = $this->localise('Bücher zu genau diesem Thema im Opac anzeigen');
			}
			$opacLink->setAttribute('title', $titleString);

			// Question: Is '_blank' a good idea?
			$opacLink->setAttribute('target', '_blank');
			if ($hitCount > 0) {
				// we know the number of results: display it
				$numberString = number_format($hitCount, 0, $this->localise('decimal separator'), $this->localise('thousands separator'));
				$opacLink->appendChild($this->doc->createTextNode(sprintf($this->localise('%s Treffer anzeigen'), $numberString)));
			}
			else {
				// we don't know the number of results: display a general text
				$opacLink->appendChild($this->doc->createTextNode($this->localise('Treffer anzeigen')));
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
	 * @param Boolean $deepSearch
	 * @return string|Null URL
	 */
	private function opacGOKSearchURL($GOKData, $deepSearch) {
		$GOKSearchURL = Null;

		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][tx_nkwgok_utility::extKey]);
		$picaLanguageCode = ($this->language === 'en') ? 'EN' : 'DU';
		$GOKSearchURL = $conf['opacBaseURL'] . 'LNG=' . $picaLanguageCode;

		if ($deepSearch === True
			&& ($GOKData['type'] === tx_nkwgok_utility::recordTypeGOK || $GOKData['type'] === tx_nkwgok_utility::recordTypeBRK)
			&& $GOKData['ppn'] !== tx_nkwgok_utility::GOKRootNode && $GOKData['ppn'] !== tx_nkwgok_utility::BRKRootNode) {
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
