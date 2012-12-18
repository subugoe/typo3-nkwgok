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


define('NKWGOKMaxHierarchy', 31);



class tx_nkwgok_loadxml extends tx_scheduler_Task {

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
		set_time_limit(1200);

		// Remove records with statusID 1. These should not be around, but can
		// exist if a previous run of this task was cancelled.
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(tx_nkwgok_utility::dataTable, 'statusID = 1');

		// Load hit counts.
		$this->hitCounts = $this->loadHitCounts();

		// Load XML files. Process those coming from csv files first as they can
		// be quite large and we are less likely to run into memory limits this way.
		$result = $this->loadXMLForType(tx_nkwgok_utility::recordTypeCSV);
		$result &= $this->loadXMLForType(tx_nkwgok_utility::recordTypeGOK);
		$result &= $this->loadXMLForType(tx_nkwgok_utility::recordTypeBRK);

		$this->addRootNodes();

		// Delete all old records with statusID 1, then switch all new records to statusID 0.
		$GLOBALS['TYPO3_DB']->exec_DELETEquery(tx_nkwgok_utility::dataTable, 'statusID = 0');
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(tx_nkwgok_utility::dataTable, 'statusID = 1', Array('statusID' => 0));

		t3lib_div::devLog('loadXML Scheduler Task: Import of subject hierarchy XML to TYPO3 database completed', tx_nkwgok_utility::extKey, 1);

		return $result;
	}



	/**
	 * Loads the Pica XML records of the given type, tries to determine the hit
	 * counts for each of them and inserts them to the database.
	 *
	 * @param String $type
	 * @return Boolean
	 */
	protected function loadXMLForType ($type) {
		$XMLFolder = PATH_site . 'fileadmin/gok/xml/';
		$fileList = $this->fileListAtPathForType ($XMLFolder, $type);

		if (count($fileList) > 0) {
			// Parse XML files to extract just the tree structure.
			$subjectTree = $this->loadSubjectTree($fileList);

			// Compute total hit count sums.
			$totalHitCounts = $this->computeTotalHitCounts(tx_nkwgok_utility::rootNode, $subjectTree, $this->hitCounts);

			// Run through the files again, read all data, add the information
			// about parent elements and store it to our table in the database.
			foreach ($fileList as $xmlPath) {
				$rows = Array();

				$xml = simplexml_load_file($xmlPath);
				foreach ($xml->xpath('/RESULT/SET/SHORTTITLE/record') as $recordElement) {
					$GOK = $this->picaXMLToArray($recordElement);

					// Build complete record and insert into database.
					// Discard records without a PPN.
					$PPN = trim($GOK['003@']['0']);
					if ($PPN !== '' && array_key_exists($PPN, $subjectTree)) {
						$search = '';
						if ($GOK['type'] === tx_nkwgok_utility::recordTypeCSV) {
							// Subject coming from CSV file with a CCL search query in the 'str/a' field.
							if (array_key_exists('str', $GOK) && array_key_exists('a', $GOK['str']) && $GOK['str']['a']) {
								$search = $GOK['str']['a'];
							}
						}
						else {
							// Subject coming from a Pica authority record.
							if (array_key_exists('044H', $GOK)
								&& array_key_exists('2', $GOK['044H'])
								&& strtolower($GOK['044H']['2']) === 'msc') {
								// Maths type GOK with an MSC type search term.
								$search = 'msc="' . $GOK['044H']['a'] . '"';
							}
							else if (array_key_exists('045A', $GOK)
									 && array_key_exists ('a', $GOK['045A'])
									 && $GOK['045A']['a']) {

								if ($GOK['type'] === tx_nkwgok_utility::recordTypeGOK || $GOK['type'] === tx_nkwgok_utility::recordTypeBRK) {
									// GOK or BRK OPAC search, using the corresponding index.
									$indexName = $this->typeToIndexName($GOK['type']);
								}
								else {
									t3lib_div::devLog('loadXML Scheduler Task: Unknown record type »' . $GOK['type'] . '« in record PPN ' . $PPN . '. Skipping.', tx_nkwgok_utility::extKey, 3, $GOK);
									continue;
								}
								// Requires quotation marks around the search term as notations can begin
								// with three character strings that could be mistaken for index names.
								$search = $indexName . '="' . $GOK['045A']['a'] . '"';
							}
						}

						$treeElement = $subjectTree[$PPN];
						$parentID = $treeElement['parent'];

						// Use stored subject tree to determine hierarchy level.
						// The hierarchy typically is no deeper than 12 levels:
						// cut off at 32 to prevent an infinite loop.
						$hierarchy = 0;
						$nextParent = $parentID;
						while ($nextParent !== Null && $nextParent !== tx_nkwgok_utility::rootNode) {
							$hierarchy++;
							if (array_key_exists($nextParent, $subjectTree)) {
								$nextParent = $subjectTree[$nextParent]['parent'];
							}
							else {
								t3lib_div::devLog('loadXML Scheduler Task: Could not determine hierarchy level: Unknown parent PPN ' . $nextParent . ' for record PPN ' . $PPN . '. This needs to be fixed if he subject is meant to appear in a subject hierarchy.', tx_nkwgok_utility::extKey, 3, $GOK);
								$hierarchy = -1;
								break;
							}
							if ($hierarchy > NKWGOKMaxHierarchy) {
								t3lib_div::devLog('loadXML Scheduler Task: Hierarchy level for PPN ' . $PPN . ' exceeds the maximum limit of ' . NKWGOKMaxHierarchy . ' levels. This needs to be fixed, the subject tree may contain an infinite loop.', tx_nkwgok_utility::extKey, 3, $GOK);
								$hierarchy = -1;
								break;
							}
						}

						$GOKString = trim($GOK['045A']['a']);
						$GOKLower = strtolower($GOKString);
						$descr = trim($GOK['045A']['j']);
						$search = trim($search);
						$tags = $GOK['tags']['a'];
						$hitCount = -1;
						$totalHitCount = -1;

						// English translation of the subject’s name is in field 044F $a if $S is »d«.
						$descr_en = '';
						$d044FSdsa = $recordElement->xpath('datafield[@tag="044F" and subfield[@code="S"]="d"]/subfield[@code="a"]');
						if (count($d044FSdsa) > 0) {
							$descr_en = trim($d044FSdsa[0]);
						}

						// Alternate/additional description of the subject.
						$descr_alternate = '';
						$d044FSgsa = $recordElement->xpath('datafield[@tag="044F" and subfield[@code="S"]="g"]/subfield[@code="a"]');
						if (count($d044FSgsa) > 0) {
							$descr_alternate = trim($d044FSgsa[0]);
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
						else if (($GOK['type'] === tx_nkwgok_utility::recordTypeGOK
									|| $GOK['type'] === tx_nkwgok_utility::recordTypeBRK)
								 && array_key_exists($GOKLower, $this->hitCounts[$GOK['type']])) {
							$hitCount = $this->hitCounts[$GOK['type']][$GOKLower];
						}
						else if ($GOK['str']['a']) {
							// Try to detect simple GOK and MSC queries from CSV files so hit counts can be displayed for them.
							$foundGOKs = Array();
							$GOKPattern = '/^lkl=([a-zA-Z]*\s?[.X0-9]*)$/';
							preg_match($GOKPattern, $GOK['str']['a'], $foundGOKs);
							$foundGOK = strtolower($foundGOKs[1]);

							$foundMSCs = Array();
							$MSCPattern = '/^msc=([0-9Xx][0-9Xx][A-Z-]*[0-9Xx]*)/';
							preg_match($MSCPattern, $GOK['str']['a'], $foundMSCs);
							$foundMSC = strtolower($foundMSCs[1]);

							if (count($foundGOKs) > 1 && $foundGOK
								&& array_key_exists($foundGOK, $this->hitCounts[tx_nkwgok_utility::recordTypeGOK])) {
								$hitCount = $this->hitCounts[tx_nkwgok_utility::recordTypeGOK][$foundGOK];
							}
							else if (count($foundMSCs) > 1 && $foundMSC
								&& array_key_exists($foundMSC, $this->hitCounts[tx_nkwgok_utility::recordTypeMSC])) {
								$hitCount = $this->hitCounts[tx_nkwgok_utility::recordTypeMSC][$foundMSC];
								$GOK['type'] = tx_nkwgok_utility::recordTypeMSC;
							}
						}
						else {
							$hitCount = 0;
						}

						// Add total hit count information if it exists.
						if (array_key_exists($PPN, $totalHitCounts)) {
							$totalHitCount = $totalHitCounts[$PPN];
						}

						$childCount = count($treeElement['children']);

						$rows[] = Array($PPN, $hierarchy, $GOKString, $parentID, $descr, $descr_en, $descr_alternate, $search, $tags, $childCount, $GOK['type'], $hitCount, $totalHitCount, time(), time(), 1);
					}
				} // end of loop over subjects
				$keyNames = Array('ppn', 'hierarchy', 'gok', 'parent', 'descr', 'descr_en', 'descr_alternate', 'search', 'tags', 'childcount', 'type', 'hitcount', 'totalhitcount', 'crdate', 'tstamp', 'statusID');
				$result = $GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows(tx_nkwgok_utility::dataTable, $keyNames, $rows);

			} // end of loop over files

			$result = True;
		}
		else {
			t3lib_div::devLog('loadXML Scheduler Task: Found no XML files for type ' . $type . '.', tx_nkwgok_utility::extKey, 3);
			$result = True;
		}

		return $result;
	}



	/**
	 * Helper function, adding the GOK and BRK root nodes to the database.
	 */
	private function addRootNodes () {
		// Add the GOK root node.
		$GOKRootRow = array(
			'ppn' => tx_nkwgok_utility::GOKRootNode,
			'hierarchy' => 0,
			'gok' => tx_nkwgok_utility::GOKRootNode,
			'parent' => tx_nkwgok_utility::rootNode,
			'descr' => 'Göttinger Online Klassifikation (GOK)',
			'descr_en' => 'Göttingen Online Classification (GOK)',
			'descr_alternate' => '',
			'search' => '',
			'tags' => '',
			'childcount' => count($this->tree[tx_nkwgok_utility::GOKRootNode]),
			'type' => tx_nkwgok_utility::recordTypeGOK,
			'hitcount' => -1,
			'totalhitcount' => -1,
			'crdate' => time(),
			'tstamp' => time(),
			'statusID' => 1
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(tx_nkwgok_utility::dataTable, $GOKRootRow);

		// Add the Band-Realkatalog root node.
		$BRKRootRow = array(
			'ppn' => tx_nkwgok_utility::BRKRootNode,
			'hierarchy' => 0,
			'gok' => tx_nkwgok_utility::BRKRootNode,
			'parent' => tx_nkwgok_utility::rootNode,
			'descr' => 'Göttinger Band-Realkatalog',
			'descr_en' => 'Göttingen Band-Realkatalog',
			'descr_alternate' => '',
			'search' => '',
			'tags' => '',
			'childcount' => count($this->tree[tx_nkwgok_utility::BRKRootNode]),
			'type' => tx_nkwgok_utility::recordTypeBRK,
			'hitcount' => -1,
			'totalhitcount' => -1,
			'crdate' => time(),
			'tstamp' => time(),
			'statusID' => 1
		);
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(tx_nkwgok_utility::dataTable, $BRKRootRow);
	}



	/**
	 * Go through the record XML element and turn it into a PHP array.
	 * The array uses Pica field names as keys with arrays as values. The
	 * inner arrays use Pica subfield names as keys and their values as values.
	 * 
	 * If fields or subfields are repeated, their last occurrence is used.
	 * 
	 * @param DOMElement $recordElement
	 * @return Array
	 */
	private function picaXMLToArray ($recordElement) {
		$record = Array();
		$record['type'] = $this->typeOfRecord($recordElement);

		foreach ($recordElement->xpath('datafield') as $field) {
			$fieldName = trim((string) $field->attributes());

			// Process just the fields we need.
			$wantedFieldNames = Array('003@', '044F', '044H', '045A', '045C', 'str', 'tags');
			if (in_array($fieldName, $wantedFieldNames)) {
				foreach ($field->xpath('subfield') as $subfield) {
					$subfieldName = (string)$subfield['code'];
					$subfieldContent = trim((string)$subfield);
					if ($subfieldContent !== Null) {
						$record[$fieldName][$subfieldName] = $subfieldContent;
					}
				}
			}
		}

		return $record;
	}



	/**
	 * Goes through data files and creates information of the subject tree’s
	 * structure from that.
	 *
	 * Storing the full data from all records would run into memory problems.
	 * The resulting array just keeps the information we strictly need for
	 * analysis.
	 *
	 * Returns an array. Keys are record IDs (PPNs), values are Arrays with:
	 * * children => Array of strings (record IDs of child elements)
	 * * [parent => string (record ID of parent element)]
	 * * notation [gok|brk|msc|bkl|…] => string
	 *
	 * @author Sven-S. Porst
	 *
	 * @param Array $fileList list of XML files to read
	 * @return Array containing the subject tree structure
	 */
	private function loadSubjectTree ($fileList) {
		$tree = Array();
		$tree[tx_nkwgok_utility::GOKRootNode] = Array('children' => Array(), 'parent' => tx_nkwgok_utility::rootNode, 'type' => tx_nkwgok_utility::recordTypeGOK);
		$tree[tx_nkwgok_utility::BRKRootNode] = Array('children' => Array(), 'parent' => tx_nkwgok_utility::rootNode, 'type' => tx_nkwgok_utility::recordTypeBRK);
		$tree[tx_nkwgok_utility::rootNode] = Array('children' => Array(tx_nkwgok_utility::GOKRootNode, tx_nkwgok_utility::BRKRootNode));

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

				$recordType = $this->typeOfRecord($record);
				$tree[$PPN]['type'] = $recordType;

				$myParentPPNs = $record->xpath('datafield[@tag="045C" and subfield[@code="4"] = "nueb"]/subfield[@code="9"]');
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
					if ($recordType === tx_nkwgok_utility::recordTypeGOK) {
						// GOK subject authority record.
						$parentPPN = tx_nkwgok_utility::GOKRootNode;
					}
					else if ($recordType === tx_nkwgok_utility::recordTypeBRK) {
						// BRK subject authoriy record.
						$parentPPN = tx_nkwgok_utility::BRKRootNode;
					}
					else {
						// Other record: goes in at top level.
						$parentPPN = tx_nkwgok_utility::rootNode;
					}

					$tree[$parentPPN]['children'][] = $PPN;
					$tree[$PPN]['parent'] = $parentPPN;
				}

				if ($recordType === tx_nkwgok_utility::recordTypeGOK || $recordType === tx_nkwgok_utility::recordTypeBRK) {
					// Store notation information.
					$notationStrings = $record->xpath('datafield[@tag="045A"]/subfield[@code="a"]');
					if (count($notationStrings) > 0) {
						$notationString = (string)($notationStrings[0]);
						$notation = strtolower(trim($notationString));
						$tree[$PPN][$recordType] = $notation;
					}
				}
				else {
					$queries = $record->xpath('datafield[@tag="str"]/subfield[@code="a"]');
					if (count($queries) === 1) {
						$query = (string)($queries[0]);
						$foundQueries = NULL;
						if (preg_match('/^msc=([^ ]*)$/', $query, $foundQueries) && count($foundQueries) === 2) {
							$msc = $foundQueries[1];
							$tree[$PPN][tx_nkwgok_utility::recordTypeMSC] = $msc;
							$tree[$PPN]['type'] = tx_nkwgok_utility::recordTypeMSC;
						}
					}
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
	 * These files are downloaded from the OPAC by the loadFromOpac Scheduler task.
	 *
	 * @author Sven-S. Porst
	 *
	 * @return Array with Key: classification system string => Value: Array with Key: notation => Value: hits for this notation
	 */
	private function loadHitCounts () {
		$hitCountFolder = PATH_site . '/fileadmin/gok/hitcounts/';
		$fileList = $this->fileListAtPathForType($hitCountFolder, 'all');

		$hitCounts = Array();
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
							$hitCountType = $this->indexNameToType(strtolower((string)$value));
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
				t3lib_div::devLog('loadXML Scheduler Task: could not load/parse XML from ' . $xmlPath, tx_nkwgok_utility::extKey, 3);
			}
		} // end foreach

		foreach ($hitCounts as $hitCountType => $array) {
			t3lib_div::devLog('loadXML Scheduler Task: Loaded ' . count($array) . ' ' . $hitCountType . ' hit count entries.', tx_nkwgok_utility::extKey, 1);
		}

		return $hitCounts;
	}



	/**
	 * Recursively go through $subjectTree and add up the $hitCounts to return a
	 * total hit count including the hits for all child elements.
	 *
	 * @author Sven-S. Porst
	 *
	 * @param String $startPPN - PPN to start at
	 * @param Array $subjectTree
	 * @param Array $hitCounts
 	 * @return Array with Key: PPN => Value: sum of hit counts
	 */
	private function computeTotalHitCounts ($startPPN, $subjectTree, $hitCounts) {
		$totalHitCounts = Array();
		$myHitCount = 0;
		if (array_key_exists($startPPN, $subjectTree)) {
			$record = $subjectTree[$startPPN];
			$type = $record['type'];
			$notation = strtolower($record[$type]);
			if (array_key_exists(tx_nkwgok_utility::recordTypeMSC, $record)
				&& $type !== tx_nkwgok_utility::recordTypeBRK) {
				$type = tx_nkwgok_utility::recordTypeMSC;
				$notation = strtolower($record[tx_nkwgok_utility::recordTypeMSC]);
			}

			if (count($record['children']) > 0) {
				// A parent node: recursively collect and add up the hit counts.
				foreach ($record['children'] as $childPPN) {
					$childHitCounts = $this->computeTotalHitCounts($childPPN, $subjectTree, $hitCounts);
					if (array_key_exists($childPPN, $childHitCounts)) {
						$myHitCount += $childHitCounts[$childPPN];
					}
					$totalHitCounts += $childHitCounts;
				}

				if (array_key_exists($type, $hitCounts)
					&& array_key_exists($notation, $hitCounts[$type])) {
					$myHitCount += $hitCounts[$type][$notation];
				}
			}
			else {
				// A leaf node: just store its hit count.
				if (array_key_exists($type, $hitCounts)
					&& array_key_exists($notation, $hitCounts[$type])) {
					$myHitCount += $hitCounts[$type][$notation];
				}
			}
		}
		
		$totalHitCounts[$startPPN] = $myHitCount;

		return $totalHitCounts;
	}



	/**
	 * Returns Array of file paths in $basePath of the given type.
	 * The types are:
	 *	* 'all': returns all *.xml files
	 *  * 'gok': returns all gok-*.xml files
	 *  * 'brk': returns all brk-*.xml files
	 *  * otherwise the list given by 'all' - 'gok' - 'brk' is returned
	 *
	 * @param String $basePath
	 * @param String $type
	 * @return Array of file paths in $basePath
	 */
	private function fileListAtPathForType ($basePath, $type) {
		if ($type === 'all') {
			$fileList = glob($basePath . '*.xml');
		}
		else if ($type === tx_nkwgok_utility::recordTypeGOK || $type === tx_nkwgok_utility::recordTypeBRK) {
			$fileList = glob($basePath . $type . '-*.xml');
		}
		else {
			$allFiles = glob($basePath . '*.xml');
			$gokFiles = glob($basePath . tx_nkwgok_utility::recordTypeGOK . '-*.xml');
			$brkFiles = glob($basePath . tx_nkwgok_utility::recordTypeBRK . '-*.xml');
			$fileList = array_diff($allFiles, $gokFiles, $brkFiles);
		}

		return $fileList;
	}



	/**
	 * Returns the type of the $record passed.
	 * Logs unknown record types.
	 *
	 * @param DOMElement $record
	 * @return string - gok|brk|csv|unknown
	 */
	private function typeOfRecord ($record) {
		$recordType = tx_nkwgok_utility::recordTypeUnknown;
		$recordTypes = $record->xpath('datafield[@tag="002@"]/subfield[@code="0"]');

		if ($recordTypes && count($recordTypes) === 1) {
			$recordTypeCode = (string)$recordTypes[0];

			if ($recordTypeCode === 'Tev') {
				$recordType = tx_nkwgok_utility::recordTypeGOK;
			}
			else if ($recordTypeCode === 'Tov') {
				$recordType = tx_nkwgok_utility::recordTypeBRK;
			}
			else if ($recordTypeCode === 'csv') {
				$queryElements = $record->xpath('datafield[@tag="str"]/subfield[@code="a"]');
				if ($queryElements && count($queryElements) === 1
					&& preg_match('/^msc=[0-9A-Zx-]*/', (string)$queryElements[0] > 0)) {
					// Special case: an MSC record.
					$recordType = tx_nkwgok_utility::recordTypeMSC;
				}
				else {
					// Regular case: a standard CSV record.
					$recordType = tx_nkwgok_utility::recordTypeCSV;
				}
			}
		}

		if ($recordType === tx_nkwgok_utility::recordTypeUnknown) {
			t3lib_div::devLog('loadXML Scheduler Task: Record of unknown type.', tx_nkwgok_utility::extKey, 1, Array($record->saveXML()));
		}

		return $recordType;
	}


	
	/**
	 * Returns the internal type name for the given index name.
	 * * lkl -> gok
	 * * pass others through unchanged
	 * 
	 * @param String $indexName
	 * @return String
	 */
	private function indexNameToType ($indexName) {
		$type = $indexName;

		if ($indexName === 'lkl') {
			$type = 'gok';
		}

		return $type;
	}



	/**
	 * Returns the internal type name for the given index name.
	 * * gok -> lkl
	 * * pass others through unchanged
	 *
	 * @param String $type
	 * @return String
	 */
	private function typeToIndexName ($type) {
		$indexName = $type;

		if ($type === 'gok') {
			$indexName = 'lkl';
		}

		return $indexName;
	}


}



if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadxml.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadxml.php']);
}
?>
