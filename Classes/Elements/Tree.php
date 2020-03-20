<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Elements;

/*******************************************************************************
 * Copyright notice
 *
 * Copyright (C) 2010 by Nils K. Windisch, SUB Göttingen
 * <windisch@sub.uni-goettingen.de
 * Copyright (C) 2011-2013 by Sven-S. Porst, SUB Göttingen
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
use Subugoe\Nkwgok\Domain\Model\Description;
use Subugoe\Nkwgok\Domain\Model\Term;
use Subugoe\Nkwgok\Utility\Utility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Subclass of tx_nkwgok that creates markup for a subject hierarchy as a tree.
 */
class Tree extends Element
{
    /**
     * Returns markup for subject tree based on the configuration in $this->arguments.
     *
     * The $this->arguments array needs to contain:
     * - a string element 'notation' which can either be 'all' (to display the
     *        complete subject tree) or the notation of the node to be used as the
     *        root of the tree
     * - a string element 'style' which may take the values 'tree' or 'column'
     * - an array element 'expand' listing the IDs of subjects to be expanded
     *
     * Further array elements can be:
     * - 'showGOKID', determining whether the subject’s notation is displayed along
     *        with its name
     *
     * @return \DOMDocument
     */
    public function getMarkup()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Utility::dataTable);
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $queryResult = $queryBuilder->select('*')->from(Utility::dataTable);

        $this->addGOKTreeJSToElement($this->doc);

        // Create container.
        $container = $this->doc->createElement('div');
        $this->doc->appendChild($container);

        $containerClasses = ['gokContainer', $this->arguments['style']];
        if (!$this->arguments['showGOKID']) {
            $containerClasses[] = 'hideGOKID';
        }

        $useShallowLinks = ($GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_nkwgok_pi1.']['shallowSearch'] == 1);
        if ($this->arguments['omitXXX']) {
            $useShallowLinks = true;
            $logger->error('Configured to use deep links together with omitXXX. This will not work as the totalhitcount is incorrect when we arbitrarily leave out child elements. Using shallow links instead.');
        }
        if ($useShallowLinks) {
            $containerClasses[] = 'shallowLinks';
        }

        $container->setAttribute('class', implode(' ', $containerClasses));
        $container->setAttribute('id', 'tx_nkwgok-'.$this->objectID);

        // Get and insert start nodes.
        $startNodes = explode(',', (string) $this->arguments['notation']);

        foreach ($startNodes as $startNodeGOK) {
            $queryResult->orWhere($queryBuilder->expr()->eq('notation', $queryBuilder->quote(trim($startNodeGOK))));
        }

        $results = $queryResult
            ->andWhere($queryBuilder->expr()->eq('statusID', 0))
            ->orderBy('notation', 'ASC')
            ->execute();

        $resultCount = $results->rowCount();

        $topElementType = 'span';
        $topItemContainer = $container;
        if ($resultCount > 1) {
            $topElementType = 'li';
            $ul = $this->doc->createElement('ul');
            $container->appendChild($ul);
            $topItemContainer = $ul;
        }

        while ($GOK = $results->fetch()) {
            $GOK = $this->rowToObject($GOK);
            $topElement = $this->appendGOKTreeItem($topItemContainer, $topElementType, $GOK, [], 1, ($resultCount > 1));
            $topElement->setAttribute('class', 'rootNode');

            if (1 === $resultCount) {
                // There is a single element: display expanded tree without controls at top level.
                $this->appendGOKTreeChildren($GOK->getPpn(), $container, [], 1);
            }
        }

        return $this->doc;
    }

    /**
     * Returns markup for subject menus based on the configuration in $this->arguments.
     *
     * @return \DOMNode
     */
    public function getAJAXMarkup()
    {
        $parentPPN = $this->arguments['expand'][0];

        $this->appendGOKTreeChildren($parentPPN, $this->doc, [$parentPPN], 1);

        return $this->doc;
    }

    /**
     * Helper function to insert JavaScript for the subject tree into the passed
     * $container element.
     *
     * @param \DOMNode $container the <script> tag is inserted into
     */
    private function addGOKTreeJSToElement(\DOMNode $container)
    {
        $scriptElement = $this->doc->createElement('script');
        $container->appendChild($scriptElement);
        $scriptElement->setAttribute('type', 'text/javascript');

        $js = '
        function swapTitles'.$this->objectID." (element) {
            var jElement = jQuery(element);
            var otherTitle = jElement.attr('alttitle');
            jElement.attr('alttitle', jElement.attr('title'));
            jElement.attr('title', otherTitle);
        }";

        $HTMLInsertionTarget = 'jContainerLI';
        if ('column' === $this->arguments['style']) {
            $HTMLInsertionTarget = 'jQuery("#tx_nkwgok-'.$this->objectID.'")';
        }

        $js .= '
        function expandGOK'.$this->objectID." (id) {
            var jContainerLI = jQuery('#c".$this->objectID."-' + id);
            selectGOK".$this->objectID."(id);
            jContainerLI.removeClass('open').addClass('close');
            var link = jQuery('#openCloseLink-".$this->objectID."-' + id);";
        if ('column' === $this->arguments['style']) {
            $js .= "
            jContainerLI.parent().nextAll('ul').remove();";
        }
        $js .= "
            var plusMinus = jQuery('.plusMinus', link);
            swapTitles".$this->objectID."(link);
            plusMinus.text('[*]');
            var functionText = 'hideGOK".$this->objectID."(\"' + id + '\");return false;';
            link[0].onclick = new Function(functionText);
            jQuery.get("
            ."'/',
                {'eID': '".Utility::extKey."', "
            ."'tx_".Utility::extKey."[language]': '".$this->language."', "
            ."'tx_".Utility::extKey."[expand][0]': id, "
            ."'tx_".Utility::extKey."[style]': '".$this->arguments['style']."', "
            .(($this->arguments['omitXXX']) ? ("'tx_".Utility::extKey."[omitXXX]': '".$this->arguments['omitXXX']."', ") : (''))
            ."'tx_".Utility::extKey."[objectID]': '".$this->objectID."'},
                function (html) {
                    plusMinus.text('[-]');
                    ".$HTMLInsertionTarget.'.append(html);
                }
            );
        }';

        $js .= '
        function hideGOK'.$this->objectID." (id) {
            jQuery('#ul-".$this->objectID."-' + id).remove();
            jQuery('#c".$this->objectID."-' + id).removeClass('close').addClass('open');
            var link = jQuery('#openCloseLink-".$this->objectID."-' + id);
            jQuery('.plusMinus', link).text('[+]');
            swapTitles".$this->objectID."(link);
            var	functionText = 'expandGOK".$this->objectID."(\"' + id + '\");return false;';
            link[0].onclick = new Function(functionText);
        }";

        if ('column' === $this->arguments['style']) {
            $js .= '
        function unselectSiblings'.$this->objectID." (jElement) {
            jElement.siblings('.close').each( function() {
                    var siblingID = this.id.substr(".(strlen($this->objectID) + 2).');
                    hideGOK'.$this->objectID."(siblingID);
                }
            );
            jElement.siblings('.selected').removeClass('selected');
        }
        function selectGOK".$this->objectID."(id) {
            var element = document.getElementById('c".$this->objectID."-' + id);
            var jElement = jQuery(element);
            jElement.addClass('selected');
            unselectSiblings".$this->objectID.'(jElement);
            if (window.nkwgokItemSelected !== undefined) {
                window.nkwgokItemSelected(element);
            }
        }';
        } else {
            $js .= '
        function unselectSiblings'.$this->objectID.' (jElement) { }
        function selectGOK'.$this->objectID.'(id) {}
            ';
        }

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][Utility::extKey]['gokTreeJavaScript'] ?? [] as $reference) {
            $js = GeneralUtility::callUserFunction($reference, $js, $this);
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
     * @param string   $parentPPN
     * @param \DOMNode $container       the created markup is appended to (needs to be a child element of $this->doc)
     * @param array    $expandMarker    list of PPNs of open parent elements
     * @param int      $autoExpandLevel automatically expand subentries if they have at most this many child elements
     */
    private function appendGOKTreeChildren(string $parentPPN, \DOMNode $container, array $expandMarker, int $autoExpandLevel)
    {
        $GOKs = $this->getChildren($parentPPN, true);
        if (count($GOKs) > 1) {
            $ul = $this->doc->createElement('ul');
            $container->appendChild($ul);
            $ul->setAttribute('id', 'ul-'.$this->objectID.'-'.$parentPPN);

            // The first item in the array is the root element.
            /** @var Term $firstGOK */
            $firstGOK = array_shift($GOKs);
            $ul->setAttribute('class', 'level-'.$firstGOK->getHierarchy());
            if ($firstGOK->getHitCount() > 0) {
                $description = (new Description())->setDescription($this->localise('Allgemeines'));
                $firstGOK->setDescription($description);
                $this->appendGOKTreeItem($ul, 'li', $firstGOK, $expandMarker, $autoExpandLevel, false, 'general-items-node');
            }

            /** @var Term $GOK */
            foreach ($GOKs as $GOK) {
                /* Do not display the GOK item if
                 * 1. it has no child elements and
                 * 2. it is known to have no matching hits
                 */
                if (0 !== $GOK->getHitCount() || 0 !== $GOK->getChildCount()) {
                    $this->appendGOKTreeItem($ul, 'li', $GOK, $expandMarker, $autoExpandLevel);
                }
            }
        }
    }

    /**
     * Appends a single subject item child element of type $elementName
     * to the element $container inside $this->doc and returns it.
     *
     * @param \DOMElement $container       the created markup is appended to (needs to be a child element of $this->doc)
     * @param string      $elementName     name of the element to insert into $container
     * @param Term        $GOK
     * @param array       $expandMarker    list of PPNs of open parent elements [defaults to Array()]
     * @param int         $autoExpandLevel automatically expand subentries if they have at most this many child elements [defaults to 0]
     * @param bool        $isInteractive   whether the element can be an expandable part of the tree and should have dynamic links [defaults to TRUE]
     * @param string      $extraClass      class added to the appended links [defaults to NULL]
     *
     * @return \DOMElement
     */
    private function appendGOKTreeItem(\DOMElement $container, string $elementName, Term $GOK, array $expandMarker = [], int $autoExpandLevel = 0, bool $isInteractive = true, string $extraClass = '')
    {
        $expand = [$GOK->getPpn()];

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
        $item->setAttribute('id', 'c'.$this->objectID.'-'.$GOK->getPpn());
        $item->setAttribute('query', $GOK->getSearch());

        $openLink = $this->doc->createElement('a');
        $openLink->setAttribute('id', 'openCloseLink-'.$this->objectID.'-'.$GOK->getPpn());
        $item->appendChild($openLink);

        $control = $this->doc->createElement('span');
        $openLink->appendChild($control);
        $openLinkClass = 'plusMinus';
        if (true !== $isInteractive) {
            $openLinkClass .= ' nkwgok-invisible';
        }
        $control->setAttribute('class', $openLinkClass);

        $GOKIDSpan = $this->doc->createElement('span');
        $GOKIDSpan->setAttribute('class', 'GOKID');
        $GOKIDSpan->appendChild($this->doc->createTextNode($GOK->getNotation()));

        $GOKNameSpan = $this->doc->createElement('span');
        $GOKNameSpan->setAttribute('class', 'GOKName');
        $GOKNameSpan->appendChild($this->doc->createTextNode($this->GOKName($GOK, true)));

        $openLink->appendChild($this->doc->createTextNode(' '));
        $openLink->appendChild($GOKIDSpan);
        $openLink->appendChild($this->doc->createTextNode(' '));
        $openLink->appendChild($GOKNameSpan);

        // Add OPAC links when not in column mode.
        if ('column' !== $this->arguments['style']) {
            $this->appendOpacLinksTo($GOK, $item);
        }
        // Careful: These are three non-breaking spaces to get better alignment.
        $buttonText = '   ';
        $JSCommand = '';
        $itemClass = 'nochildren';
        if (true === $isInteractive) {
            // Set up addition to title, if it exists.
            $titleAddition = '';
            if ('en' === $this->language && '' !== $GOK->getDescription()->getAlternateEnglish()) {
                $titleAddition = $GOK->getDescription()->getAlternateEnglish();
            } elseif ('' !== $GOK->getDescription()->getAlternate()) {
                $titleAddition = $GOK->getDescription()->getAlternate();
            }

            if ($GOK->getChildCount() > 0) {
                $mainTitle = sprintf($this->localise('%s Unterkategorien anzeigen'), $GOK->getChildCount());
                $alternativeTitle = $this->localise('Unterkategorien ausblenden');
                $urlComponents = parse_url(GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL'));
                $baseURL = $urlComponents['scheme'].'://'.$urlComponents['host'].$urlComponents['path'];
                $queryComponents = [];
                parse_str((string) $urlComponents['query'], $queryComponents);
                $queryComponents['no_cache'] = 1;

                if ((array_key_exists('expand', $this->arguments)
                        && is_array($this->arguments['expand'])
                        && in_array($GOK->getPpn(), $this->arguments['expand']))
                    || $GOK->getChildCount() <= $autoExpandLevel
                ) {
                    $itemClass = 'close';
                    $JSCommand = 'hideGOK'.$this->objectID;
                    $buttonText = '[-]';
                    $tmpTitle = $mainTitle;
                    $mainTitle = $alternativeTitle;
                    $alternativeTitle = $tmpTitle;
                    $queryComponents['tx_nkwgok']['expand'] = $expandMarker;
                    $noscriptLink = GeneralUtility::linkThisUrl($baseURL, $queryComponents);

                    // Recursively add opened subject list.
                    // Add it into the current item for the default tree style.
                    // Add it as a sibling to our parent node in column style.
                    $childListContainer = $item;
                    if ('column' === $this->arguments['style']) {
                        $childListContainer = $container->parentNode;
                    }
                    $this->appendGOKTreeChildren($GOK->getPpn(), $childListContainer, $expand, $autoExpandLevel);
                } else {
                    $itemClass = 'open';
                    $JSCommand = 'expandGOK'.$this->objectID;
                    $buttonText = '[+]';
                    $queryComponents['tx_nkwgok']['expand'] = $expand;
                    $noscriptLink = GeneralUtility::linkThisUrl($baseURL, $queryComponents)
                        .'#c'.$this->objectID.'-'.$GOK->getPpn();
                }

                $openLink->setAttribute('href', $noscriptLink);
                $openLink->setAttribute('rel', 'nofollow');

                // Add extra note to title if it exists.
                if ('' !== $titleAddition) {
                    $mainTitle .= ': '.$titleAddition;
                    $alternativeTitle .= ': '.$titleAddition;
                }
                // Set up title attribute and its alternate.
                $openLink->setAttribute('title', $mainTitle);
                $openLink->setAttribute('alttitle', $alternativeTitle);
            } elseif ('column' === $this->arguments['style']) {
                // Column style: set up the JavaScript command.
                $openLink->setAttribute('href', '#');
                $JSCommand = 'selectGOK'.$this->objectID;
            } else {
                // Tree style: add tooltip to the surrounding anchor tag if an alternative title exists.
                if ('' !== $titleAddition) {
                    $openLink->setAttribute('title', $titleAddition);
                }
            }
        }

        if ($JSCommand) {
            $openLink->setAttribute('onclick', $JSCommand.'("'.$GOK->getPpn().'");return false;');
        }

        if ('' !== $extraClass) {
            $itemClass .= ' '.$extraClass;
        }
        $item->setAttribute('class', $itemClass);

        $control->appendChild($this->doc->createTextNode($buttonText));

        return $item;
    }

    /**
     * Appends two OPAC search links to $container, one for shallow search and
     * one for deep search. One of them will be hidden by CSS.
     *
     * @param Term        $GOK       subject record
     * @param \DOMElement $container the link elements are appended to
     */
    private function appendOpacLinksTo(Term $GOK, $container)
    {
        $opacLinkElement = $this->OPACLinkElement($GOK, true);
        if ($opacLinkElement) {
            $container->appendChild($this->doc->createTextNode(' '));
            $container->appendChild($opacLinkElement);
        }

        $opacLinkElement = $this->OPACLinkElement($GOK, false);
        if ($opacLinkElement) {
            $container->appendChild($this->doc->createTextNode(' '));
            $container->appendChild($opacLinkElement);
        }
    }

    /**
     * Returns DOMElement with complete markup for linking to the OPAC entry.
     * The link text indicates the number of results if it is known.
     *
     * @param Term $GOKData    subject record
     * @param bool $deepSearch
     *
     * @return \DOMElement
     */
    private function OPACLinkElement(Term $GOKData, $deepSearch)
    {
        $opacLink = null;
        $hitCount = $GOKData->getHitCount();
        $useDeepSearch = $deepSearch && ($GOKData->getTotalHitCounts() > 0);
        if (true === $useDeepSearch) {
            $hitCount = $GOKData->getTotalHitCounts();
        }
        $URL = $this->opacGOKSearchURL($GOKData, $deepSearch);
        if (0 != $hitCount && $URL) {
            $opacLink = $this->doc->createElement('a');
            $opacLink->setAttribute('href', $URL);
            if (true === $useDeepSearch && 0 != $GOKData->getChildCount()) {
                $titleString = $this->localise('Bücher zu diesem und enthaltenen Themengebieten im Opac anzeigen');
            } else {
                $titleString = $this->localise('Bücher zu genau diesem Thema im Opac anzeigen');
            }
            $opacLink->setAttribute('title', $titleString);

            // Question: Is '_blank' a good idea?
            $opacLink->setAttribute('target', '_blank');
            if ($hitCount > 0) {
                // we know the number of results: display it
                $numberString = number_format($hitCount, 0, $this->localise('decimal separator'), $this->localise('thousands separator'));
                $opacLink->appendChild($this->doc->createTextNode(sprintf($this->localise('%s Treffer anzeigen'), $numberString)));
            } else {
                // we don't know the number of results: display a general text
                $opacLink->appendChild($this->doc->createTextNode($this->localise('Treffer anzeigen')));
            }

            $linkClass = 'opacLink '.((true === $deepSearch) ? 'deep' : 'shallow');
            $opacLink->setAttribute('class', $linkClass);
        }

        return $opacLink;
    }

    /**
     * Returns URL string for an OPAC Search.
     * If $deepSearch is false, the search query stored in $GOKData is used.
     * If $deepSearch is true, a deep hierarchical search for records related
     * to the ID (PPN) of the subject’s authority  record is used.
     * If the record did not originate from OPAC, Null is returned.
     *
     * @param Term $GOKData    subject record
     * @param bool $deepSearch
     *
     * @return string|null URL
     */
    private function opacGOKSearchURL(Term $GOKData, $deepSearch)
    {
        $GOKSearchURL = null;

        $opacBaseUrl = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nkwgok', 'opacBaseURL');
        $picaLanguageCode = ('en' === $this->language) ? 'EN' : 'DU';
        $GOKSearchURL = $opacBaseUrl.'LNG='.$picaLanguageCode;

        if (true === $deepSearch
            && (Utility::recordTypeGOK === $GOKData->getType() || Utility::recordTypeBRK === $GOKData->getType())
        ) {
            // Use special command to do the hierarchical search for records related
            // to the Normsatz PPN.
            $GOKSearchURL .= '/EPD?PPN='.$GOKData->getPpn().'&FRM=';
        } elseif ($GOKData->getSearch()) {
            // Convert CCL string to OPAC-style search string and escape.
            $searchString = urlencode(str_replace('=', ' ', $GOKData->getSearch()));
            $GOKSearchURL .= '/REC=1/CMD?ACT=SRCHA&IKT=1016&SRT=YOP&TRM='.$searchString;
        } else {
            $GOKSearchURL = null;
        }

        return $GOKSearchURL;
    }
}
