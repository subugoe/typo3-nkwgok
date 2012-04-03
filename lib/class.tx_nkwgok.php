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


define('NKWGOKExtKey', 'nkwgok');
define('NKWGOKQueryTable', 'tx_nkwgok_data');
define('NKWGOKQueryFields', 'ppn, gok, search, descr, descr_en, parent, childcount, hitcount, totalhitcount, fromopac');

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
	 * Implemented by subclasses.
	 * Returns a DOMDocument with markup for the subject hierarchy based on the
	 * settings passed to instantiateSubclassFor.
	 * 
	 * @return DOMDocument
	 */
	abstract function getMarkup ();

	
	
	/**
	 * Implemented by subclasses.
	 * Returns a DOMDocument with markup for the partial subject hierarchy based
	 * on the settings passed to instantiateSubclassFor.
	 * 
	 * @return DOMDocument
	 */
	abstract function getAJAXMarkup ();



	/**
	 * Uses the 'style' field of the $arguments array to determine which subclass
	 * to instantiate, instantiates it, and adds $arguments to it.
	 *
	 * @param Array $arguments
	 * @return tx_nkwgok
	 */
	public function instantiateSubclassFor ($arguments) {
		$subclass = NULL;

		if ($arguments['style'] === 'menu') {
			$subclass = t3lib_div::makeInstance('tx_nkwgok_menu');
		}
		else if ($arguments['style'] === 'horizontal') {
			$subclass = t3lib_div::makeInstance('tx_nkwgok_horizontal');
		}
		else {
			$subclass = t3lib_div::makeInstance('tx_nkwgok_tree');
		}

		$subclass->arguments = $arguments;

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
	 * @param string $language ISO 639-1 language code
	 * @return string
	 */
	protected function localise ($key, $language) {
		$result = '';
		
		$filePath = t3lib_div::getFileAbsFileName('EXT:' . NKWGOKExtKey . '/pi1/locallang.xml');
		if (!$this->localisation) {
			if (t3lib_div::int_from_ver(TYPO3_version) >= 4006000) {
				/**
				 * In TYPO3 >=4.6 t3lib_l10n_parser_Llxml is recommended for reading
				 * localisations.
				 *
				 * The returned $localisation seems to have the following structure:
				 * array('languageKey' => array('stringKey' => array(array('target' => 'localisedString'))))
				 * Only the requested languageKey seems to be present and the innermost
				 * array can also contain a 'source' key.
				 */
				$parser = t3lib_div::makeInstance('t3lib_l10n_parser_Llxml');
				$this->localisation = $parser->getParsedData($filePath, $language);
			}
			else {
				/**
				 * In TYPO3 <4.6 use t3lib_div::readLLXMLfile.
				 *
				 * The returned $localisation has the following structure:
				 * array('languageKey' => array('stringKey' => 'localisedString'))
				 * It seems to contain languageKeys for all localisations in the XML file.
				 */
				$this->localisation = t3lib_div::readLLXMLfile($filePath, $language);
			}
		}

		$myLanguage = $language;
		if (!array_key_exists($language, $this->localisation)) {
			$myLanguage = 'default';
		}
		
		if (array_key_exists($key, $this->localisation[$myLanguage])) {
			$result = $this->localisation[$myLanguage][$key];
		}
		else {
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
	protected function GOKName($gokRecord, $language='de', $simplify = False) {
		$displayName = $gokRecord['descr'];

		if ($language == 'en') {
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
	 * Returns GOK records for the children of a given PPN, ordered by GOK.
	 *
	 * @param string $parentPPN
	 * @param Boolean $includeParent if True, the parent item is included
	 * @return Array of GOK records of the $parentPPN’s children
	 */
	protected function getChildren($parentPPN, $includeParent = False) {
		$parentEscaped = $GLOBALS['TYPO3_DB']->fullQuoteStr($parentPPN, NKWGOKQueryTable);
		$includeParentSelectCondition = '';
		if ($includeParent) {
			$includeParentSelectCondition = ' OR ppn = ' . $parentEscaped;
		}
		$whereClause = '(parent = ' . $parentEscaped . $includeParentSelectCondition . ') AND statusID = 0';
		$queryResult = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					NKWGOKQueryFields,
					NKWGOKQueryTable,
					$whereClause,
					'',
					'hierarchy,gok ASC',
					'');

		$children = Array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($queryResult)) {
			$children[] = $row;
		}

		return $children;
	}

}

?>
