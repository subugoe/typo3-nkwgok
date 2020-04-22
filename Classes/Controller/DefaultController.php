<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Controller;

use Subugoe\Nkwgok\Elements\Element;
use Subugoe\Nkwgok\Utility\Utility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

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

/**
 * See the ChangeLog or git repository for details.
 */
class DefaultController extends ActionController
{
    /**
     * Main method of the PlugIn.
     *
     * @return string The content that is displayed on the website
     */
    public function mainAction()
    {
        // CSS
        $this->addStylesheet();

        // get getvars
        $arguments = GeneralUtility::_GET('tx_nkwgok');

        // get flexform
        $arguments['notation'] = $this->settings['source'];

        // alternative source overrides first definition
        if ($this->settings['altSource'] !== null) {
            $arguments['notation'] = trim($this->settings['altSource']);
        }

        // unique expand array
        if (array_key_exists('expand', $arguments) && is_array($arguments['expand'])) {
            $arguments['expand'] = array_unique($arguments['expand']);
        }
        $contentObject = $this->configurationManager->getContentObject();
        $arguments['style'] = $this->settings['style'];
        $arguments['showGOKID'] = $this->settings['showGOKID'];
        $arguments['omitXXX'] = $this->settings['omitXXX'];
        $arguments['objectID'] = $contentObject->data['uid'];
        $arguments['language'] = $GLOBALS['TSFE']->lang;
        $arguments['pageLink'] = $this->uriBuilder->reset()
            ->setTargetPageUid((int) $GLOBALS['TSFE']->id)
            ->setCreateAbsoluteUri(true)
            ->build();

        /** @var Element $nkwgok */
        $nkwgok = Element::instantiateSubclassFor($arguments);

        return $nkwgok->getMarkup()->saveHTML();
    }

    /**
     * Helper function to add our default stylesheet or the one at the path
     * set up in Extension Manager configuration to the page’s head.
     */
    protected function addStylesheet()
    {
        $cssPath = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nkwgok', 'CSSPath');
        if (!$cssPath) {
            $cssPath = 'EXT:nkwgok/Resources/Public/Css/nkwgok.css';
        }

        $GLOBALS['TSFE']->pSetup['includeCSS.'][Utility::extKey] = $cssPath;
    }
}
