<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Nils K. Windisch (windisch@sub.uni-goettingen.de)
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
 * 2011-03-14: Sven-S. Porst <porst@sub.uni-goettingen.de>
 * - unify data model by storing complete queries in the search field and not
 * omitting the leading LKL for pure GOK queries
 * 2011-02-28: Sven-S. Porst <porst@sub.uni-goettingen.de>
 * - add Opac hit counts to data
 * 2011-02-08: Sven-S. Porst <porst@sub.uni-goettingen.de>
 * - debugged
 * - improved structure
 * - > 20X speed improvement
 */

/**
 * Class "tx_nkwgok_loadxml" provides task procedures
 *
 * @author		Nils K. Windisch <windisch@sub.uni-goettingen.de>
 * @author		Sven-S. Porst <porst@sub.uni-goettingen.de>
 * @package		TYPO3
 * @subpackage	tx_nkwgok
 */

define('NKWGOKRootNode', 'Root');
define('NKWGOKGOKRootNode', 'GOK-Root');


class tx_nkwgok_loadxml extends tx_scheduler_Task {

	/**
	 * Stores the hierarchical GOK structure:
	 * Key: PPN => Value: Array containing PPNs of child elements.
	 * @var Array
	 */
	private $parentPPNs;



	/**
	 * Maps Normsatz-PPN to its corresponding GOK.
	 * Key: PPN => Value: GOK string
	 * @var Array
	 */
	private $PPNToGOK;



	/**
	 * Stores the hitcount for each GOK.
	 * Key: GOK string => Value: integer
	 * @var Array
	 */
	private $hitCounts;



	/**
	 * Function executed from the Scheduler.
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {
		// Initialisation and general setup.
		set_time_limit(1200);
		$result = False;

		$this->parentPPNs = Array(NKWGOKRootNode => Array(), NKWGOKGOKRootNode => Array());
		$this->PPNToGOK = Array();

		$wantedFieldNames = Array('045A', '044E', '044K', '009B', '038D', '003@', '045G', 'str', 'tags');

		$dir = PATH_site . 'fileadmin/gok/xml/';
		$fileList = glob($dir . '*.xml');
		if (count($fileList) > 0) {
			// Run through all files once to gather information about the
			// structure of the data we process.

			foreach ($fileList as $xmlPath) {
				$xml = simplexml_load_file($xmlPath);
				$records = $xml->xpath('/RESULT/SET/SHORTTITLE/record');

				foreach ($records as $record) {
					$PPNs = $record->xpath('datafield[@tag="003@"]/subfield[@code="0"]');
					$PPN = (string)($PPNs[0]);
					$myParentPPNs = $record->xpath('datafield[@tag="038D"]/subfield[@code="9"]');
					if ($myParentPPNs && count($myParentPPNs) > 0) {
						$parentPPN = (string)($myParentPPNs[0]);
						if (array_key_exists($parentPPN, $this->parentPPNs)) {
							$this->parentPPNs[$parentPPN][] = $PPN;
						}
						else {
							$this->parentPPNs[$parentPPN] = Array($PPN);
						}
					}
					else {
						$fromOpac = (count($record->xpath('datafield[@tag="str"]')) === 0);
						if ($fromOpac) {
							$this->parentPPNs[NKWGOKGOKRootNode][] = $PPN;
						}
						else {
							$this->parentPPNs[NKWGOKRootNode][] = $PPN;
						}
					}

					$GOKStrings = $record->xpath('datafield[@tag="045A"]/subfield[@code="a"]');
					if (count($GOKStrings) > 0) {
						$GOKString = (string)($GOKStrings[0]);
						$this->PPNToGOK[$PPN] = strtolower(trim($GOKString));
					}
				}
			}

			
			// Load hit count information and compute total hit count sums.
			$this->hitCounts = $this->loadHitCounts();
			$totalHitCounts = $this->computeTotalHitCounts(NKWGOKGOKRootNode);

			// Run through the files again, read all data, add the information
			// about parent elements and store it to our table in the database.
			foreach ($fileList as $xmlPath) {
				$rows = Array();
				$keyNames = Array('ppn', 'hierarchy', 'gok', 'parent', 'descr', 'search', 'descr_en', 'tags', 'childcount', 'fromopac', 'hitcount', 'totalhitcount', 'crdate', 'tstamp', 'statusID');

				$xml = simplexml_load_file($xmlPath);
				foreach ($xml->xpath('/RESULT/SET/SHORTTITLE') as $GOKElement) {
					$previousFieldName = Null;
					$GOK = Array();

					foreach ($GOKElement->xpath('record/datafield') as $field) {
						$fieldName = trim((string) $field->attributes());

						// Only read the desired fields
						if (in_array($fieldName, $wantedFieldNames)) {
							// append "_2" to field Name to avoid duplication (ahem)
							if ($fieldName === $previousFieldName) {
								$fieldName = $fieldName . '_2';
							}
							$previousFieldName = $fieldName;

							// Get subfields
							foreach ($field->xpath('subfield') as $subfield) {
								$subfieldName = (string) $subfield['code'];
								$subfieldContent = trim((string) $subfield);
								if ($subfieldContent !== Null) {
									$GOK[$fieldName][$subfieldName] = $subfieldContent;
								}
							}
						}
					} // end of datafield loop


					// Build complete record and insert into database.
					// Discard records without a PPN.
					$PPN = trim($GOK['003@']['0']);
					if ($PPN !== '') {
						$childCount = 0;
						if ($this->parentPPNs[$PPN]) {
							$childCount = count($this->parentPPNs[$PPN]);
						}

						$parent = Null;
						if (array_key_exists('038D', $GOK) && array_key_exists(9, $GOK['038D']) && $GOK['038D'][9]) {
							$parent = trim($GOK['038D'][9]);
						}

						$search = '';
						$fromOpac = False;
						if (array_key_exists('str', $GOK) && array_key_exists('a', $GOK['str'])) {
							// GOK coming from CSV file with a CCL search query in the 'str/a' field.
							if ($GOK['str']['a']) {
								$search = $GOK['str']['a'];
							}
							
							// Set parent element to Root node if it is blank.
							if ($parent === Null) {
								$parent = NKWGOKRootNode;
							}
						}
						else {
							// GOK coming from standard Opac record.
							$fromOpac = True;
							
							if (array_key_exists('045G', $GOK) && array_key_exists('C', $GOK['045G']) && $GOK['045G']['C'] === 'MSC') {
								// Maths type GOK with an MSC type search term.
								$search = 'msc=' . $GOK['045G']['a'];
							}
							else if (array_key_exists('045A', $GOK) && array_key_exists ('a', $GOK['045A']) && $GOK['045A']['a']) {
								// Generic GOK search, using the LKL field.
								$search = 'lkl=' . $GOK['045A']['a'];
							}
							
							// Set parent element to GOK-Root node if it is blank.
							if ($parent === Null) {
								$parent = NKWGOKGOKRootNode;
							}
						}

						$GOKString = trim($GOK['045A']['a']);
						$hierarchy = trim($GOK['009B']['a']);
						$descr = trim($GOK['044E']['a']);
						$search = trim($search);
						$descr_en = '';
						$tags = $GOK['tags']['a'];
						$hitCount = -1;
						$totalHitCount = -1;

						// English translation of the GOK’s name is in field 044K $a.
						// This field is designated for the _English_ version.
						if (array_key_exists('044K', $GOK) && array_key_exists('a', $GOK['044K'])) {
							$descr_en = trim($GOK['044K']['a']);
						}

						// Hit keys are lowercase.
						// Set result count information:
						// * for GOK and MSC-type records: try to use hitcount
						// * for CSV-type records: if only one LKL query, try to use hitcount, else use -1
						// * otherwise: use 0
						if (array_key_exists('045G', $GOK) && array_key_exists('C', $GOK['045G']) && $GOK['045G']['C'] === 'MSC') {
							$hitCount = $this->hitCounts[strtolower($GOK['045G']['a'])];
						}
						else if (array_key_exists(strtolower($GOKString), $this->hitCounts)) {
							$hitCount = $this->hitCounts[strtolower($GOKString)];
						}
						else if ($GOK['str']['a']) {
							$foundGOKs = Array();
							$pattern = '/lkl=([a-zA-Z]*\s?[.X0-9]*)$/';
							preg_match($pattern, $GOK['str']['a'], $foundGOKs);
							$foundGOK = strtolower($foundGOKs[1]);

							if (count($foundGOKs) > 1 && $foundGOK && array_key_exists($foundGOK, $this->hitCounts)) {
								$hitCount = $this->hitCounts[$foundGOK];
							}
						}
						else {
							$hitCount = 0;
						}

						// Add total hit count information if it exists.
						if (array_key_exists($PPN, $totalHitCounts)) {
							$totalHitCount = $totalHitCounts[$PPN];
						}

						$rows[] = Array($PPN, $hierarchy, $GOKString, $parent, $descr, $search, $descr_en, $tags, $childCount, $fromOpac, $hitCount, $totalHitCount, time(), time(), 1);
					}
				} // end of loop over GOKs
				$result = $GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows('tx_nkwgok_data', $keyNames, $rows);

			} // end of loop over files

			// Add the GOK root node.
			$row = array(
				'ppn' => NKWGOKGOKRootNode,
				'hierarchy' => '-1',
				'gok' => NKWGOKGOKRootNode,
				'parent' => NKWGOKRootNode,
				'descr' => 'Göttinger Online Klassifikation (GOK)',
				'search' => '',
				'descr_en' => 'Göttingen Online Classification (GOK)',
				'tags' => '',
				'childcount' => count($this->parentPPNs[NKWGOKGOKRootNode]),
				'fromopac' => True,
				'hitcount' => -1,
				'totalhitcount' => -1,
				'crdate' => time(),
				'tstamp' => time(),
				'statusID' => 1
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nkwgok_data', $row);

			// Delete all old records with statusID 1, then switch all new records to statusID 0.
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_nkwgok_data', 'statusID = 0');
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nkwgok_data', 'statusID = 1', Array('statusID' => 0));

			t3lib_div::devLog('loadXML Scheduler Task: Import of GOK XML to Typo3 database completed', 'nkwgok', 1);
			$result = True;
		} else {
			t3lib_div::devLog('loadXML Scheduler Task: No "*.xml" files found in ' . $dir, 'nkwgok', 3);
		}

		return $result;
	}



	/**
	 * Load LKL hit counts from fileadmin/gok/hitcounts/*.xml.
	 * These files are downloaded from the Opac by the loadFromOpac
	 * Scheduler task.
	 *
	 * @author Sven-S. Porst
	 *
	 * @return Array with Key: LKL entries => Value: integer with the hit count for the entry
	 */
	private function loadHitCounts () {
		$hitCounts = Array();

		$fileList = glob(PATH_site . '/fileadmin/gok/hitcounts/' . '*.xml');
		foreach ($fileList as $xmlPath) {
			$xml = simplexml_load_file($xmlPath);
			if ($xml) {
				$scanlines = $xml->xpath('/RESULT/SCANLIST/SCANLINE');
				foreach ($scanlines as $scanline) {
					$hits = Null;
					$description = Null;
					foreach($scanline->attributes() as $name => $value) {
						if ($name === 'hits') {
							$hits = (int)$value;
						}
						else if ($name === 'description') {
							$description = (string)$value;
						}
					}
					if ($hits !== Null && $description !== Null) {
						$hitCounts[$description] = $hits;
					}
				}
			}
			else {
				t3lib_div::devLog('loadXML Scheduler Task: could not load/parse XML from ' . $xmlPath, 'nkwgok', 3);
			}
		} // end foreach

		t3lib_div::devLog('loadXML Scheduler Task: Loaded ' . count($hitCounts) . ' hit count entries.', 'nkwgok', 1);

		return $hitCounts;
	}



	/**
	 * Recursively go through the $childRelations and add up the $hitCounts to return a
	 * total hit count including the hits for all child elements.
	 *
	 * Requires that the class’ $hitCounts and $parentPPNs arrays are initialised.
	 *
	 * @author Sven-S. Porst
	 *
	 * @param String $startPPN - PPN to start at
	 * @return Array with Key: PPN => Value: sum of hit counts
	 */
	private function computeTotalHitCounts ($startPPN) {
		$totalHitCounts = Array();
		$GOK = Null;
		$myHitCount = 0;

		if (array_key_exists($startPPN, $this->PPNToGOK)) {
			$GOK = $this->PPNToGOK[$startPPN];
		}

		if (array_key_exists($startPPN, $this->parentPPNs)) {
			// A parent node: recursively collect and add up the hit counts.
			foreach ($this->parentPPNs[$startPPN] as $childPPN) {
				$childHitCounts = $this->computeTotalHitCounts($childPPN);
				if (array_key_exists($childPPN, $childHitCounts)) {
					$myHitCount += $childHitCounts[$childPPN];
				}
				$totalHitCounts += $childHitCounts;
			}

			if ($GOK && array_key_exists($GOK, $this->hitCounts)) {
				$myHitCount += $this->hitCounts[$GOK];
			}
		}
		else {
			// A leaf node: just store its hit count.
			if ($GOK && array_key_exists($GOK, $this->hitCounts)) {
				$myHitCount += $this->hitCounts[$GOK];
			}
		}

		$totalHitCounts[$startPPN] = $myHitCount;

		return $totalHitCounts;
	}

}



if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadxml.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadxml.php']);
}
?>
