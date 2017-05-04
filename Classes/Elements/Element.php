<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Elements;

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
use Subugoe\Nkwgok\Utility\Utility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\Parser\LocallangXmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provide output for the nkwgok extension.
 *
 * Instantiate the required subclass using the instantiateSubclassFor() method
 * passing the setup as arguments.
 *
 * Then call the getMarkup() or getAJAXMarkup() methods to receive the output.
 * */
abstract class Element
{
    const NKWGOKQueryFields = 'ppn, notation, search, descr, descr_en, descr_alternate, descr_alternate_en, parent, hierarchy, childcount, hitcount, totalhitcount, type';

    /**
     * Arguments from the GET query as well as further settings that may have
     * been added. This variable lets us have the same interface for accessing the
     * date when running in pibase or eID.
     *
     * @var array
     */
    protected $arguments;

    /**
     * Language code to use for the localisation.
     *
     * @var string ISO 639-1 language code
     */
    protected $language;

    /**
     * TYPO3 content object ID for our content element. This variable
     * is initialised by the instantiateSubclassFor() method.
     *
     * @var string
     */
    protected $objectID;

    /**
     * DOMDocument used by subclasses to create their content. This variable
     * is initialised by the instantiateSubclassFor() method.
     *
     * @var \DOMDocument
     */
    protected $doc;

    /**
     * Implemented by subclasses.
     * Returns a DOMDocument with markup for the subject hierarchy based on the
     * settings passed to instantiateSubclassFor.
     *
     * @return \DOMDocument
     */
    abstract public function getMarkup();

    /**
     * Implemented by subclasses.
     * Returns a DOMDocument with markup for the partial subject hierarchy based
     * on the settings passed to instantiateSubclassFor.
     *
     * @return \DOMDocument
     */
    abstract public function getAJAXMarkup();

    /**
     * Uses the 'style' field of the $arguments array to determine which subclass
     * to instantiate, instantiates it, and adds $arguments to it.
     *
     * @param array $arguments
     *
     * @return Element
     */
    public static function instantiateSubclassFor($arguments)
    {
        $subclass = null;

        if ($arguments['style'] === 'menu') {
            $subclass = GeneralUtility::makeInstance(Menu::class);
        } else {
            // Default to displaying the tree. Expected for styles 'tree' and 'column'.
            $subclass = GeneralUtility::makeInstance(Tree::class);
            if (!array_key_exists('style', $arguments) || !$arguments['style']) {
                // Default to tree style if style is not set.
                $arguments['style'] = 'tree';
            }
        }

        if ($subclass) {
            // Configure the newly created instance.
            $subclass->arguments = $arguments;
            $domImplementation = new \DOMImplementation();
            $subclass->doc = $domImplementation->createDocument();
            $subclass->objectID = $arguments['objectID'];
            $subclass->language = $arguments['language'];
        }

        return $subclass;
    }

    /**
     * @var array
     */
    protected $localisation;

    /**
     * Provide our own localisation function as getLL() is not available when
     * running in eID.
     *
     * @param string $key key to look up in pi1/locallang.xml
     *
     * @return string
     */
    protected function localise($key)
    {
        $filePath = GeneralUtility::getFileAbsFileName('EXT:'.Utility::extKey.'/Resources/Private/Language/locallang.xml');
        if (!$this->localisation) {
            /**
             * The returned $localisation seems to have the following structure:
             * array('languageKey' => array('stringKey' => array(array('target' => 'localisedString'))))
             * Only the requested languageKey seems to be present and the innermost
             * array can also contain a 'source' key.
             */
            $parser = GeneralUtility::makeInstance(LocallangXmlParser::class);
            $this->localisation = $parser->getParsedData($filePath, $this->language);
        }

        $myLanguage = $this->language;
        if (!array_key_exists($this->language, $this->localisation)) {
            $myLanguage = 'default';
        }

        if (array_key_exists($key, $this->localisation[$myLanguage])) {
            $result = $this->localisation[$myLanguage][$key];
        } else {
            // Return the original key in upper case if we don’t find a localisation.
            $result = strtoupper($key);
        }

        // In TYPO3 >=4.6 $result is an array. Extract the relevant string from that.
        if (is_array($result)) {
            $result = $result[0]['target'];
        }

        return $result;
    }

    /**
     * Return subject name for display.
     *
     * Use English if the language code is 'en' and German otherwise.
     *
     * Some subject names end with a super-subject indicator enclosed in { }.
     * This is helpful when viewing the subject name on its own but is redundant
     * when viewed inside the subject hierarchy. The parameter $simplify = True
     * removes that indicator.
     *
     * @param array $gokRecord
     * @param bool  $simplify  should the trailing {…} be removed? [defaults to False]
     *
     * @return string
     */
    protected function GOKName($gokRecord, $simplify = false)
    {
        $displayName = $gokRecord['descr'];

        if ($this->language == 'en') {
            $englishName = $gokRecord['descr_en'];

            if ($englishName) {
                $displayName = $englishName;
            }
        }

        // Remove trailing ' - Allgemein- und Gesamtdarstellungen'
        // Remove trailing super-subject designator in { }
        if ($simplify) {
            $displayName = preg_replace('/ - Allgemein- und Gesamtdarstellungen$/', '', $displayName);
            $displayName = preg_replace("/( \{.*\})$/", '', $displayName);
        }

        return trim($displayName);
    }

    /**
     * Returns subject records for the children of a given identifier (PPN)
     * ordered by their notation.
     *
     * @param string $parentPPN
     * @param bool   $includeParent if True, the parent item is included
     *
     * @return array of subject records of the $parentPPN’s children
     */
    protected function getChildren($parentPPN, $includeParent = false)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Utility::dataTable);

        $parentEscaped = $queryBuilder->quote($parentPPN);
        $whereClause = 'parent = '.$parentEscaped;
        if ($this->arguments['omitXXX']) {
            $whereClause .= ' AND NOT notation LIKE "%XXX"';
        }
        $whereClause = '('.$whereClause.')';
        if ($includeParent) {
            $whereClause = '('.$whereClause.' OR ppn = '.$parentEscaped.')';
        }

        $queryResult = $queryBuilder
            ->select('*')
            ->from(Utility::dataTable)
            ->where($whereClause)
            ->andWhere($queryBuilder->expr()->eq('statusID', 0))
            ->orderBy('hierarchy', 'ASC')
            ->addOrderBy('notation', 'ASC')
            ->execute();

        return $queryResult->fetchAll();
    }
}