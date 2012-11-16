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
 * Changes 2011-2012 by Sven-S. Porst <porst@sub.uni-goettingen.de>
 * See the ChangeLog or git repository for details.
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
define('NKWGOKBRKRootNode', 'BRK-Root');
define('NKWGOKMaxHierarchy', 31);

class tx_nkwgok_loadxml extends tx_scheduler_Task {

	/**
	 * Stores the subject tree. Keys are PPNs, values are Arrays with:
	 * * children => Array of strings (PPNs of child elements)
	 * * [parent => string (PPN of parent element)]
	 * * notation [GOK|msc|bkl|…] => string
	 * @var Array
	 */
	private $subjectTree;


	/**
	 * Stores the hitcount for each notation.
	 * Key: classifiaction system string => Value: Array with
	 *		Key: notation => Value: hits for this notation
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

		// Remove records with statusID 1. These should not be around, but can
		// exist if a previous run of this task was cancelled.
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_nkwgok_data', 'statusID = 1');

		// Loop over all XML files to extract their data.
		// Do so in reverse order as a heuristic to process the handwritten
		// files first. Some of them are large and PHP seems less likely to run
		// out of memory when processing those first.
		$dir = PATH_site . 'fileadmin/gok/xml/';
		$fileList = array_reverse(glob($dir . '*.xml'));
		if (count($fileList) > 0) {
			// Parse XML files to extract just the tree structure.
			$this->subjectTree = $this->loadSubjectTree($fileList);

			// Load hit count information and compute total hit count sums.
			$this->hitCounts = $this->loadHitCounts();
			$totalHitCounts = $this->computeTotalHitCounts(NKWGOKGOKRootNode);

			// Run through the files again, read all data, add the information
			// about parent elements and store it to our table in the database.
			foreach ($fileList as $xmlPath) {
				$rows = Array();

				$xml = simplexml_load_file($xmlPath);
				foreach ($xml->xpath('/RESULT/SET/SHORTTITLE') as $GOKElement) {
					$GOK = $this->GOKXMLToArray($GOKElement);

					// Build complete record and insert into database.
					// Discard records without a PPN.
					$PPN = trim($GOK['003@']['0']);
					if ($PPN !== '' && array_key_exists($PPN, $this->subjectTree)) {
						$treeElement = $this->subjectTree[$PPN];

						$recordType = $GOK['002@']['0'];
						$search = '';
						if ($recordType === 'csv') {
							// GOK coming from CSV file with a CCL search query in the 'str/a' field.
							if (array_key_exists('str', $GOK) && array_key_exists('a', $GOK['str']) && $GOK['str']['a']) {
								$search = $GOK['str']['a'];
							}
						}
						else {
							// GOK coming from an Opac Pica authority record.
							if (array_key_exists('044H', $GOK)
								&& array_key_exists('2', $GOK['044H'])
								&& strtolower($GOK['044H']['2']) === 'msc') {
								// Maths type GOK with an MSC type search term.
								$search = 'msc="' . $GOK['044H']['a'] . '"';
							}
							else if (array_key_exists('045A', $GOK)
									 && array_key_exists ('a', $GOK['045A'])
									 && $GOK['045A']['a']) {

								$indexName;
								if ($recordType[1] === 'e') {
									// Te-record: GOK search, using the LKL index.
									$indexName = 'lkl';
								}
								else if ($recordType[1] === 'o') {
									// To-record: Bandrealkatalog search, using the BRK index.
									$indexName = 'brk';
								}
								else {
									t3lib_div::devLog('loadXML Scheduler Task: Unknown record type »' . $recordType . '« in record PPN ' . $PPN . '.', 'nkwgok', 3, $GOK);
								}
								// Requires quotation marks around the search term as notations can begin
								// with three character strings that could be mistaken for index names.
								$search = $indexName . '="' . $GOK['045A']['a'] . '"';
							}
						}

						$childCount = count($treeElement['children']);
						$parent = $treeElement['parent'];

						// Use stored subject tree to determine hierarchy level.
						// The hierarchy typically is no deeper than 12 levels:
						// cut off at 32 to prevent an infinite loop.
						$hierarchy = 0;
						$nextParent = $parent;
						while ($nextParent !== Null && $nextParent !== NKWGOKRootNode) {
							$hierarchy++;
							if (array_key_exists($nextParent, $this->subjectTree)) {
								$nextParent = $this->subjectTree[$nextParent]['parent'];
							}
							else {
								t3lib_div::devLog('loadXML Scheduler Task: Could not determine hierarchy level: Unknown parent PPN ' . $nextParent . ' for record PPN ' . $PPN . '. This needs to be fixed if he subject is meant to appear in a subject hierarchy.', 'nkwgok', 3, $GOK);
								$hierarchy = -1;
								break;
							}
							if ($hierarchy > NKWGOKMaxHierarchy) {
								t3lib_div::devLog('loadXML Scheduler Task: Hierarchy level for PPN ' . $PPN . ' exceeds the maximum limit of ' . NKWGOKMaxHierarchy . ' levels. This needs to be fixed, the subject tree may contain an infinite loop.', 'nkwgok', 3, $GOK);
								$hierarchy = -1;
								break;
							}
						}

						$GOKString = trim($GOK['045A']['a']);
						$GOKLower = strtolower($GOKString);
						$descr = trim($GOK['045A']['j']);
						$search = trim($search);
						$descr_en = '';
						$tags = $GOK['tags']['a'];
						$hitCount = -1;
						$totalHitCount = -1;

						// English translation of the GOK’s name is in field 044F $a if $S is »d«.
						if (array_key_exists('044F', $GOK)
							&& array_key_exists('a', $GOK['044F'])
							&& array_key_exists('S', $GOK['044F'])
							&& $GOK['044F']['S'] === 'd') {
							$descr_en = trim($GOK['044F']['a']);
						}

						// Hit keys are lowercase.
						// Set result count information:
						// * for GOK, BRK, and MSC-type records: try to use hitcount
						// * for CSV-type records: if only one LKL query, try to use hitcount, else use -1
						// * otherwise: use 0
						if (array_key_exists('044H', $GOK)
							&& array_key_exists('2', $GOK['044H'])
							&& array_key_exists('a', $GOK['044H'])
							&& in_array(strtolower($GOK['044H']['2']), Array('msc'))) {
							$type = strtolower($GOK['044H']['2']);
							$notation = strtolower($GOK['044H']['a']);
							if (array_key_exists($type, $this->hitCounts)
									&& array_key_exists($notation, $this->hitCounts[$type])) {
								$hitCount = $this->hitCounts[$type][$notation];
							}
						}
						else if ($recordType[1] === 'e' 
								 && array_key_exists($GOKLower, $this->hitCounts['lkl'])) {
							$hitCount = $this->hitCounts['lkl'][$GOKLower];
						}
						else if ($recordType[1] === 'o'
								 && array_key_exists($GOKLower, $this->hitCounts['brk'])) {
							$hitCount = $this->hitCounts['brk'][$GOKLower];
						}
						else if ($GOK['str']['a']) {
							$foundGOKs = Array();
							$pattern = '/lkl=([a-zA-Z]*\s?[.X0-9]*)$/';
							preg_match($pattern, $GOK['str']['a'], $foundGOKs);
							$foundGOK = strtolower($foundGOKs[1]);

							if (count($foundGOKs) > 1 && $foundGOK && array_key_exists($foundGOK, $this->hitCounts['lkl'])) {
								$hitCount = $this->hitCounts['lkl'][$foundGOK];
							}
						}
						else {
							$hitCount = 0;
						}

						// Add total hit count information if it exists.
						if (array_key_exists($PPN, $totalHitCounts)) {
							$totalHitCount = $totalHitCounts[$PPN];
						}

						$fromOpac = ($type !== 'csv');
						$rows[] = Array($PPN, $hierarchy, $GOKString, $parent, $descr, $search, $descr_en, $tags, $childCount, $fromOpac, $hitCount, $totalHitCount, time(), time(), 1);
					}
				} // end of loop over GOKs
				$keyNames = Array('ppn', 'hierarchy', 'gok', 'parent', 'descr', 'search', 'descr_en', 'tags', 'childcount', 'fromopac', 'hitcount', 'totalhitcount', 'crdate', 'tstamp', 'statusID');
				$result = $GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows('tx_nkwgok_data', $keyNames, $rows);

			} // end of loop over files

			// Add the GOK root node.
			$row = array(
				'ppn' => NKWGOKGOKRootNode,
				'hierarchy' => 0,
				'gok' => NKWGOKGOKRootNode,
				'parent' => NKWGOKRootNode,
				'descr' => 'Göttinger Online Klassifikation (GOK)',
				'search' => '',
				'descr_en' => 'Göttingen Online Classification (GOK)',
				'tags' => '',
				'childcount' => count($this->tree[NKWGOKGOKRootNode]),
				'fromopac' => True,
				'hitcount' => -1,
				'totalhitcount' => -1,
				'crdate' => time(),
				'tstamp' => time(),
				'statusID' => 1
			);

			// Add the Bandrealkatalog root node.
			$row = array(
				'ppn' => NKWGOKBRKRootNode,
				'hierarchy' => 0,
				'gok' => NKWGOKBRKRootNode,
				'parent' => NKWGOKRootNode,
				'descr' => 'Göttinger Bandrealkatalog',
				'search' => '',
				'descr_en' => 'Göttingen Bandrealkatalog',
				'tags' => '',
				'childcount' => count($this->tree[NKWGOKBRKRootNode]),
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

			t3lib_div::devLog('loadXML Scheduler Task: Import of GOK XML to TYPO3 database completed', 'nkwgok', 1);
			$result = True;
		}
		else {
			t3lib_div::devLog('loadXML Scheduler Task: No "*.xml" files found in ' . $dir . '.', 'nkwgok', 3);
		}

		return $result;
	}


	
	/**
	 * Go through the SHORTRECORD XML element and turn it into a PHP array.
	 * The array uses Pica field names as keys with arrays as values. The
	 * inner arrays use Pica subfield names as keys and their values as values.
	 * 
	 * If fields or subfields are repeated, their last occurrence is used.
	 * 
	 * @param type $GOKElement
	 * @return type
	 */
	private function GOKXMLToArray ($GOKElement) {
		$GOK = Array();

		foreach ($GOKElement->xpath('record/datafield') as $field) {
			$fieldName = trim((string) $field->attributes());

			// Process just the fields we need.
			$wantedFieldNames = Array('002@','003@', '044F', '044H', '045A', '045C', 'str', 'tags');
			if (in_array($fieldName, $wantedFieldNames)) {
				foreach ($field->xpath('subfield') as $subfield) {
					$subfieldName = (string) $subfield['code'];
					$subfieldContent = trim((string) $subfield);
					if ($subfieldContent !== Null) {
						$GOK[$fieldName][$subfieldName] = $subfieldContent;
					}
				}
			}
		}

		return $GOK;
	}



	/**
	 * Goes through data files and creates information of the subject tree’s
	 * structure from that.
	 *
	 * Storing the full data from all records would run into memory problems.
	 * The resulting array just keeps the information we strictly need for
	 * analysis.
	 *
	 * @author Sven-S. Porst
	 *
	 * @param Array $fileList list of XML files to read
	 * @return Array containing the subject tree structure
	 */
	private function loadSubjectTree ($fileList) {
		$tree = Array();
		$tree[NKWGOKGOKRootNode] = Array('children' => Array(), 'parent' => NKWGOKRootNode);
		$tree[NKWGOKBRKRootNode] = Array('children' => Array(), 'parent' => NKWGOKRootNode);
		$tree[NKWGOKRootNode] = Array('children' => Array(NKWGOKGOKRootNode, NKWGOKBRKRootNode));

		// Run through all files once to gather information about the
		// structure of the data we process.
		foreach ($fileList as $xmlPath) {
			$xml = simplexml_load_file($xmlPath);
			$records = $xml->xpath('/RESULT/SET/SHORTTITLE/record');

			foreach ($records as $record) {
				$PPNs = $record->xpath('datafield[@tag="003@"]/subfield[@code="0"]');
				$PPN = (string)($PPNs[0]);

				// Create entry in the tree array if necessary.
				if (!array_key_exists($PPN, $tree)) {
					$tree[$PPN] = Array('children' => Array());
				}

				$myParentPPNs = $record->xpath('datafield[@tag="045C"]/subfield[@code="9"]');
				if ($myParentPPNs && count($myParentPPNs) > 0) {
					// Child record: store its PPN in the list of its parent’s children…
					$parentPPN = (string)($myParentPPNs[0]);
					if (!array_key_exists($parentPPN, $tree)) {
						$tree[$parentPPN] = Array('children' => Array());
					}
					$tree[$parentPPN]['children'][] = $PPN;

					// … and store the PPN of the parent record.
					$tree[$PPN]['parent'] = $parentPPN;
				}
				else {
					// has no parent record
					$recordTypes = $record->xpath('datafield[@tag="002@"]/subfield[@code="0"]');
					if ($recordTypes && count($recordTypes) === 1) {
						$recordType = (string)$recordTypes[0];
						if ($recordType[1] === 'e') {
							// GOK Tev record.
							$parentPPN = NKWGOKGOKROOTNODE;
						}
						else if ($recordType[1] === 'o') {
							// BRK Tov record.
							$parentPPN = NKWGOKBRKRootNode;
						}
						else {
							// Other record: goes in at top level.
							$parentPPN = NKWGOKROOTNode;
						}

						$tree[$parentPPN]['children'][] = $PPN;
						$tree[$PPN]['parent'] = $parentPPN;
					}
					else {
						t3lib_div::devLog('loadXML Scheduler Task: could not determine record type for PPN ' . $PPN, 'nkwgok', 3, Array($record->saveXML()));
					}
				}

				// Store notation information.
				// GOK
				$GOKStrings = $record->xpath('datafield[@tag="045A"]/subfield[@code="a"]');
				if (count($GOKStrings) > 0) {
					$GOKString = (string)($GOKStrings[0]);
					$tree[$PPN]['GOK'] = strtolower(trim($GOKString));
				}
				// Store the last additional notation information (044H) of
				// each type (given in $2). In particular used for MSC.
				$extraNotations = $record->xpath('datafield[@tag="044H"]');
				foreach ($extraNotations as $extraNotation) {
					$extraNotationTexts = $extraNotation->xpath('subfield[@code="a"]');
					$extraNotationLabels = $extraNotation->xpath('subfield[@code="2"]');
					if ($extraNotationTexts && $extraNotationLabels) {
						$tree[$PPN][strtolower(trim($extraNotationLabels[0]))] = strtolower(trim($extraNotationTexts[0]));
					}
				}

			} // end foreach $records
		} // end foreach $fileList

		return $tree;
	}



	/**
	 * Load hitcounts from fileadmin/gok/hitcounts/*.xml.
	 * These files are downloaded from the Opac by the loadFromOpac
	 * Scheduler task.
	 *
	 * @author Sven-S. Porst
	 *
	 * @return Array with Key: classifiaction system string => Value: Array with Key: notation => Value: hits for this notation
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
					$hitCountType = Null;
					foreach($scanline->attributes() as $name => $value) {
						if ($name === 'hits') {
							$hits = (int)$value;
						}
						else if ($name === 'description') {
							$description = (string)$value;
						}
						else if ($name === 'mnemonic') {
							$hitCountType = strtolower((string)$value);
						}
					}
					if ($hits !== Null && $description !== Null && $hitCountType !== Null) {
						if (!array_key_exists($hitCountType, $hitCounts)) {
							$hitCounts[$hitCountType] = Array();
						}
						$hitCounts[$hitCountType][$description] = $hits;
					}
				}
			}
			else {
				t3lib_div::devLog('loadXML Scheduler Task: could not load/parse XML from ' . $xmlPath, 'nkwgok', 3);
			}
		} // end foreach

		foreach ($hitCounts as $type => $array) {
			t3lib_div::devLog('loadXML Scheduler Task: Loaded ' . count($array) . ' ' . $type . ' hit count entries.', 'nkwgok', 1);
		}

		return $hitCounts;
	}



	/**
	 * Recursively go through the subject tree and add up the $hitCounts to return a
	 * total hit count including the hits for all child elements.
	 *
	 * Requires that the class’ $hitCounts and $subjectTree arrays are initialised.
	 *
	 * @author Sven-S. Porst
	 *
	 * @param String $startPPN - PPN to start at
	 * @return Array with Key: PPN => Value: sum of hit counts
	 */
	private function computeTotalHitCounts ($startPPN) {
		$totalHitCounts = Array();
		$notation = Null;
		$myHitCount = 0;

		if (array_key_exists($startPPN, $this->subjectTree)) {
			$record = $this->subjectTree[$startPPN];
			$hitCountType = 'lkl';
			$notation = $record['GOK'];
			if (array_key_exists('msc', $record)) {
				$hitCountType = 'msc';
				$notation = $record['msc'];
			}

			if (count($record['children']) > 0) {
				// A parent node: recursively collect and add up the hit counts.
				foreach ($record['children'] as $childPPN) {
					$childHitCounts = $this->computeTotalHitCounts($childPPN);
					if (array_key_exists($childPPN, $childHitCounts)) {
						$myHitCount += $childHitCounts[$childPPN];
					}
					$totalHitCounts += $childHitCounts;
				}

				if (array_key_exists($notation, $this->hitCounts[$hitCountType])) {
					$myHitCount += $this->hitCounts[$hitCountType][$notation];
				}
			}
			else {
				// A leaf node: just store its hit count.
				if (array_key_exists($notation, $this->hitCounts[$hitCountType])) {
					$myHitCount += $this->hitCounts[$hitCountType][$notation];
				}
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
