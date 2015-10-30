<?php
/*******************************************************************************
 * Copyright notice
 *
 * Copyright (C) 2012 by Sven-S. Porst, SUB Göttingen
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
 * Subclass of tx_nkwgok that creates markup for a subject hierarchy as menus.
 */
class tx_nkwgok_menu extends tx_nkwgok
{

    /**
     * Returns markup for subject menus based on the configuration in $this->arguments.
     *
     * @return DOMElement containing the markup for a menu
     */
    public function getMarkup()
    {
        $this->addGOKMenuJSToElement($this->doc);

        // Create the form and insert the first menu.
        $container = $this->doc->createElement('div');
        $this->doc->appendChild($container);
        $container->setAttribute('class', 'gokContainer menu');
        $container->setAttribute('id', 'tx_nkwgok-' . $this->objectID);
        $form = $this->doc->createElement('form');
        $container->appendChild($form);
        $form->setAttribute('class', 'gokMenuForm no-JS');
        $form->setAttribute('method', 'get');
        $form->setAttribute('action', $this->arguments['pageLink']);

        $pageID = $this->doc->createElement('input');
        $form->appendChild($pageID);
        $pageID->setAttribute('type', 'hidden');
        $pageID->setAttribute('name', 'no_cache');
        $pageID->setAttribute('value', 1); //$GLOBALS['TSFE']->id);

        $startNodes = explode(',', $this->arguments['notation']);
        if (count($startNodes) > 1) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('several start nodes given (' . $this->arguments['notation'] . ') but only the first is used in menu mode',
                tx_nkwgok_utility::extKey, 2);
        }
        $startNodeGOK = trim($startNodes[0]);
        $firstNodeCondition = "notation LIKE " . $GLOBALS['TYPO3_DB']->fullQuoteStr($startNodeGOK,
                tx_nkwgok_utility::dataTable) . ' AND statusID = 0';
        // run query and collect result
        $queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            NKWGOKQueryFields,
            tx_nkwgok_utility::dataTable,
            $firstNodeCondition,
            '',
            'notation ASC',
            '');

        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
            $menuInlineThreshold = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nkwgok_pi1.']['menuInlineThreshold'];
            $this->appendGOKMenuChildren($row['ppn'], $form, $menuInlineThreshold);
        }

        $button = $this->doc->createElement('input');
        $button->setAttribute('type', 'submit');
        $button->setAttribute('value', $this->localise('Untergebiete anzeigen'));
        $form->appendChild($button);

        return $this->doc;
    }

    /**
     * Helper function to insert JavaScript for the subject menu into the passed
     * $container element.
     *
     * @param DOMElement $container the <script> tag is inserted into
     */
    private function addGOKMenuJSToElement($container)
    {
        $scriptElement = $this->doc->createElement('script');
        $container->appendChild($scriptElement);
        $scriptElement->setAttribute('type', 'text/javascript');

        $js = "
		jQuery(document).ready(function() {
			jQuery('.gokMenuForm input[type=\'submit\']').hide();
		});

		function GOKMenuSelectionChanged" . $this->objectID . " (menu) {
			var selectedOption = menu.options[menu.selectedIndex];
			jQuery(menu).nextAll().remove();
			if (selectedOption.getAttribute('haschildren') && !selectedOption.getAttribute('isautoexpanded')) {
				newMenuForSelection" . $this->objectID . "(selectedOption);
			}
			if (selectedOption.value != 'pleaseselect') {
				jQuery('option[value=\"pleaseselect\"]', menu).remove();
			}
			startSearch" . $this->objectID . "(selectedOption);
		}

		function newMenuForSelection" . $this->objectID . " (option) {
			var URL = location.protocol + '//' + location.host + location.pathname;
			var PPN = option.value;
			var level = parseInt(option.parentNode.getAttribute('level')) + 1;
			var parameters = location.search.replace(/^\?/, '') + '&tx_" . tx_nkwgok_utility::extKey . "[expand]=' + PPN
				+ '&tx_" . tx_nkwgok_utility::extKey . "[language]=" . $this->language . "&eID=" . tx_nkwgok_utility::extKey . "'
				+ '&tx_" . tx_nkwgok_utility::extKey . "[level]=' + level
				+ '&tx_" . tx_nkwgok_utility::extKey . "[style]=menu'
				+ '&tx_" . tx_nkwgok_utility::extKey . "[objectID]=" . $this->objectID . "'
				+ '&tx_" . tx_nkwgok_utility::extKey . "[menuInlineThreshold]=" . $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nkwgok_pi1.']['menuInlineThreshold'] . "';

			jQuery(option.parentNode).nextAll().remove();
			var newSelect = document.createElement('select');
			var jNewSelect = jQuery(newSelect);
			newSelect.setAttribute('level', level);
			var isIE = (navigator.appVersion.indexOf('MSIE ') !== -1);
			if (!isIE) {
				jNewSelect.hide();
			}
			option.form.appendChild(newSelect);
			if (!isIE) {
				jNewSelect.slideDown('fast');
			}
			var loadingOption = document.createElement('option');
			newSelect.appendChild(loadingOption);
			loadingOption.appendChild(document.createTextNode('" . $this->localise('Laden ...') . "'));
			var downloadFinishedFunction = function (HTML) {
				jNewSelect.empty();
				var jHTML = jQuery(HTML);
				var newOptions = jQuery('option, optgroup', jHTML);
				if (newOptions.length > 0) {
					newOptions[0].setAttribute('query', option.getAttribute('query'));
				}
				jNewSelect.attr('onchange', jHTML.attr('onchange'));
				jNewSelect.attr('title', jHTML.attr('title'));
				jNewSelect.append(newOptions);
				newSelect.selectedIndex = 0;
			};
			jQuery.get(URL, parameters, downloadFinishedFunction);
		}
		function startSearch" . $this->objectID . " (option) {
			nkwgokItemSelected(option);
		}
";
        $scriptElement->appendChild($this->doc->createTextNode($js));
    }

    /**
     * Looks up child elements for the given $parentPPN, creates DOM elements
     * for a popup menu containing the child elements and adds them to the
     * given $container element inside $this->doc, taking into account which
     * menu items are configured to be selected.
     *
     * Also tries to include short (as in at most the length of $autoExpandLevel)
     * submenus in higher level menus, adding an indent to their titles.
     *
     * @param string $parentPPN
     * @param DOMElement $container the created markup is appended to (needs to be a child element of $this->doc). Is expected to be a <select> element if the $autoExpandStep paramter is not 0 and a <form> element otherwise.
     * @param int $autoExpandLevel automatically expand subentries if they have at most this many child elements [defaults to 0]
     * @param int $level the depth in the menu hierarchy [defaults to 0]
     * @param int $autoExpandStep the depth of auto-expansion [defaults to 0]
     */
    private function appendGOKMenuChildren(
        $parentPPN,
        $container,
        $autoExpandLevel = 0,
        $level = 0,
        $autoExpandStep = 0
    )
    {
        $GOKs = $this->getChildren($parentPPN);
        if (sizeof($GOKs) > 0) {
            if ((sizeof($GOKs) <= $autoExpandLevel) && ($level != 0) && $autoExpandStep == 0) {
                // We are auto-expanded, so throw away the elements, as they are already present in the previous menu
                $GOKs = [];
            }

            // When auto-expanding, continue using the previous <select>
            // Element which should be passed to us as $container.
            $select = $container;

            if ($autoExpandStep == 0) {
                // Create the containing <select> when we’re not auto-expanding.
                $select = $this->doc->createElement('select');
                $container->appendChild($select);
                $select->setAttribute('id', 'select-' . $this->objectID . '-' . $parentPPN);
                $select->setAttribute('name', 'tx_' . tx_nkwgok_utility::extKey . '[expand][' . $level . ']');
                $select->setAttribute('onchange', 'GOKMenuSelectionChanged' . $this->objectID . '(this);');
                $select->setAttribute('title', $this->localise('Fachgebiet auswählen') . ' ('
                    . $this->localise('Ebene') . ' ' . ($level + 1) . ')');
                $select->setAttribute('level', $level);

                // add dummy item at the beginning of the menu
                if ($level == 0) {
                    $option = $this->doc->createElement('option');
                    $select->appendChild($option);
                    $option->appendChild($this->doc->createTextNode($this->localise('Bitte Fachgebiet auswählen:')));
                    $option->setAttribute('value', 'pleaseselect');
                } else {
                    /* Add general menu item(s).
                     * A menu item searching for all subjects beneath the selected one in the
                     * hierarchy and one searching for records matching exactly the subject selected.
                     * The latter case is only expected to happen for subjects coming from OPAC
                     * subject authority records.
                     */
                    $option = $this->doc->createElement('option');
                    $select->appendChild($option);
                    $label = '';
                    if ($GOKs[0]['type'] === tx_nkwgok_utility::recordTypeGOK
                        || $GOKs[0]['type'] === tx_nkwgok_utility::recordTypeBRK
                    ) {
                        $label = 'Treffer für diese Zwischenebene zeigen';
                    } else {
                        $label = 'Treffer aller enthaltenen Untergebiete zeigen';
                    }
                    $option->appendChild($this->doc->createTextNode($this->localise($label)));
                    $option->setAttribute('value', 'withchildren');
                    if (count($this->arguments['expand']) < $level) {
                        $option->setAttribute('selected', 'selected');
                    }

                    if (count($GOKs) > 0) {
                        $optgroup = $this->doc->createElement('optgroup');
                        $select->appendChild($optgroup);
                    }
                }
            }

            foreach ($GOKs as $GOK) {
                $PPN = $GOK['ppn'];

                $option = $this->doc->createElement('option');
                $select->appendChild($option);
                $option->setAttribute('value', $PPN);
                $option->setAttribute('query', $GOK['search']);
                // Careful: non-breaking spaces used here to create in-menu indentation
                $menuItemString = str_repeat('   ', $autoExpandStep) . $this->GOKName($GOK, True);
                if ($GOK['childcount'] > 0) {
                    $menuItemString .= $this->localise(' ...');
                    $option->setAttribute('hasChildren', $GOK['childcount']);
                }
                $option->appendChild($this->doc->createTextNode($menuItemString));
                if (($GOK['childcount'] > 0) && ($GOK['childcount'] <= $autoExpandLevel)) {
                    $option->setAttribute('isAutoExpanded', '');
                    $this->appendGOKMenuChildren($PPN, $select, $autoExpandLevel, $level, $autoExpandStep + 1);
                }

                if ($PPN === $this->arguments['expand'][$level]) {
                    // this item should be selected and the next menu should be added
                    $option->setAttribute('selected', 'selected');
                    $this->appendGOKMenuChildren($PPN, $container, $autoExpandLevel, $level + 1);
                    // remove the first/default item of the menu if we have a selection already
                }
            }
        }
    }

    /**
     * Returns markup for subject menus based on the configuration in $this->arguments.
     *
     * @return DOMDocument
     */
    public function getAJAXMarkup()
    {
        $menuInlineThreshold = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nkwgok_pi1.']['menuInlineThreshold'];
        $this->appendGOKMenuChildren($this->arguments['expand'],
            $this->doc,
            $menuInlineThreshold,
            (int)$this->arguments['level']);

        return $this->doc;
    }

}
