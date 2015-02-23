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

/**
 * Changes 2011-2012 by Sven-S. Porst <porst@sub.uni-goettingen.de>
 * See the ChangeLog or git repository for details.
 */


define('NKWGOKQueryFields', 'ppn, notation, search, descr, descr_en, descr_alternate, descr_alternate_en, parent, hierarchy, childcount, hitcount, totalhitcount, type');

/**
 * Class tx_nkwgok: provides output for the nkwgok extension.
 *
 * Instantiate the required subclass using the instantiateSubclassFor() method
 * passing the setup as arguments.
 *
 * Then call the getMarkup() or getAJAXMarkup() methods to receive the output.
 *
 * @package TYPO3
 * @author Nils K. Windisch
 * @author Sven-S. Porst
 * */
abstract class tx_nkwgok {

	/**
	 * Arguments from the GET query as well as further settings that may have
	 * been added. This variable lets us have the same interface for accessing the
	 * date when running in pibase or eID.
	 * @var Array
	 */
	protected $arguments;


	/**
	 * Language code to use for the localisation.
	 * @var string ISO 639-1 language code
	 */
	protected $language;

	/**
	 * TYPO3 content object ID for our content element. This variable
	 * is initialised by the instantiateSubclassFor() method.
	 * @var string
	 */
	protected $objectID;

	/**
	 * DOMDocument used by subclasses to create their content. This variable
	 * is initialised by the instantiateSubclassFor() method.
	 * @var DOMDocument
	 */
	protected $doc;

	/**
	 * Implemented by subclasses.
	 * Returns a DOMDocument with markup for the subject hierarchy based on the
	 * settings passed to instantiateSubclassFor.
	 *
	 * @return DOMDocument
	 */
	abstract function getMarkup();

	/**
	 * Implemented by subclasses.
	 * Returns a DOMDocument with markup for the partial subject hierarchy based
	 * on the settings passed to instantiateSubclassFor.
	 *
	 * @return DOMDocument
	 */
	abstract function getAJAXMarkup();

	/**
	 * Uses the 'style' field of the $arguments array to determine which subclass
	 * to instantiate, instantiates it, and adds $arguments to it.
	 *
	 * @param Array $arguments
	 * @return tx_nkwgok
	 */
	public static function instantiateSubclassFor($arguments) {
		$subclass = NULL;

		if ($arguments['style'] === 'menu') {
			$subclass = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_nkwgok_menu');
		} else {
			// Default to displaying the tree. Expected for styles 'tree' and 'column'.
			$subclass = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_nkwgok_tree');
			if (!array_key_exists('style', $arguments) || !$arguments['style']) {
				// Default to tree style if style is not set.
				$arguments['style'] = 'tree';
			}
		}

		if ($subclass) {
			// Configure the newly created instance.
			$subclass->arguments = $arguments;
			$subclass->doc = \DOMImplementation::createDocument();
			$subclass->objectID = $arguments['objectID'];
			$subclass->language = $arguments['language'];
		}

		return $subclass;
	}

	/**
	 * @var Array
	 */
	protected $localisation;

	/**
	 * Provide our own localisation function as getLL() is not available when
	 * running in eID.
	 *
	 * @author Sven-S. Porst
	 * @param string $key key to look up in pi1/locallang.xml
	 * @return string
	 */
	protected function localise($key) {
		$result = '';

		$filePath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName('EXT:' . tx_nkwgok_utility::extKey . '/pi1/locallang.xml');
		if (!$this->localisation) {
			/**
			 * The returned $localisation seems to have the following structure:
			 * array('languageKey' => array('stringKey' => array(array('target' => 'localisedString'))))
			 * Only the requested languageKey seems to be present and the innermost
			 * array can also contain a 'source' key.
			 */
			$parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('t3lib_l10n_parser_Llxml');
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
	 * @author Sven-S. Porst <porst@sub.uni-goettingen.de>
	 * @param Array $gokRecord
	 * @param Boolean $simplify should the trailing {…} be removed? [defaults to False]
	 * @return string
	 */
	protected function GOKName($gokRecord, $simplify = False) {
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
			$displayName = preg_replace("/ - Allgemein- und Gesamtdarstellungen$/", "", $displayName);
			$displayName = preg_replace("/( \{.*\})$/", "", $displayName);
		}
		return trim($displayName);
	}

	/**
	 * Returns subject records for the children of a given identifier (PPN)
	 * ordered by their notation.
	 *
	 * @param string $parentPPN
	 * @param Boolean $includeParent if True, the parent item is included
	 * @return Array of subject records of the $parentPPN’s children
	 */
	protected function getChildren($parentPPN, $includeParent = False) {
		$parentEscaped = $GLOBALS['TYPO3_DB']->fullQuoteStr($parentPPN, tx_nkwgok_utility::dataTable);
		$whereClause = 'parent = ' . $parentEscaped;
		if ($this->arguments['omitXXX']) {
			$whereClause .= ' AND NOT notation LIKE "%XXX"';
		}
		$whereClause = '(' . $whereClause . ')';
		if ($includeParent) {
			$whereClause = '(' . $whereClause . ' OR ppn = ' . $parentEscaped . ')';
		}
		$whereClause .= ' AND statusID = 0';
		$queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				NKWGOKQueryFields,
				tx_nkwgok_utility::dataTable,
				$whereClause,
				'',
				'hierarchy,notation ASC',
				'');

		$children = Array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
			$children[] = $row;
		}

		return $children;
	}

}
