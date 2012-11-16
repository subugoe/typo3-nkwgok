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
define('NKWGOKRecordTypeGOK', 'gok');
define('NKWGOKRecordTypeBRK', 'brk');
define('NKWGOKRecordTypeCSV', 'csv');
define('NKWGOKRecordTypeUnknown', 'unknown');


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
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_nkwgok_data', 'statusID = 1');

		// Load hit counts.
		$this->hitCounts = $this->loadHitCounts();

		// Load XML files. Process those coming from csv files first as they can
		// be quite large and we are less likely to run into memory limits this way.
		$result = $this->loadXMLForType(NKWGOKRecordTypeCSV);
		$result &= $this->loadXMLForType(NKWGOKRecordTypeGOK);
		$result &= $this->loadXMLForType(NKWGOKRecordTypeBRK);

		$this->addRootNodes();

		// Delete all old records with statusID 1, then switch all new records to statusID 0.
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_nkwgok_data', 'statusID = 0');
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nkwgok_data', 'statusID = 1', Array('statusID' => 0));

		t3lib_div::devLog('loadXML Scheduler Task: Import of subject hierarchy XML to TYPO3 database completed', 'nkwgok', 1);

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
	file_put_contents('/tmp/printr-'. $type . '.text',print_r(Array($subjectTree, $this->hitCounts, $type), TRUE));
			$totalHitCounts = $this->computeTotalHitCounts(NKWGOKRootNode, $subjectTree, $this->hitCounts);

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
						$treeElement = $subjectTree[$PPN];

						$search = '';
						if ($GOK['type'] === 'csv') {
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

								if ($GOK['type'] === NKWGOKRecordTypeGOK || $GOK['type'] === NKWGOKRecordTypeBRK) {
									// GOK or BRK Opac search, using the corresponding index.
									$indexName = $this->typeToIndexName($GOK['type']);
								}
								else {
									t3lib_div::devLog('loadXML Scheduler Task: Unknown record type »' . $GOK['type'] . '« in record PPN ' . $PPN . '. Skipping.', 'nkwgok', 3, $GOK);
									continue;
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
							if (array_key_exists($nextParent, $subjectTree)) {
								$nextParent = $subjectTree[$nextParent]['parent'];
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

						// English translation of the subject’s name is in field 044F $a if $S is »d«.
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
						else if ($GOK['type'] === NKWGOKRecordTypeGOK
								 && array_key_exists($GOKLower, $this->hitCounts[NKWGOKRecordTypeGOK])) {
							$hitCount = $this->hitCounts[NKWGOKRecordTypeGOK][$GOKLower];
						}
						else if ($GOK['type'] === NKWGOKRecordTypeBRK
								 && array_key_exists($GOKLower, $this->hitCounts[NKWGOKRecordTypeBRK])) {
							$hitCount = $this->hitCounts[NKWGOKRecordTypeBRK][$GOKLower];
						}
						else if ($GOK['str']['a']) {
							$foundGOKs = Array();
							$pattern = '/lkl=([a-zA-Z]*\s?[.X0-9]*)$/';
							preg_match($pattern, $GOK['str']['a'], $foundGOKs);
							$foundGOK = strtolower($foundGOKs[1]);

							if (count($foundGOKs) > 1 && $foundGOK && array_key_exists($foundGOK, $this->hitCounts[NKWGOKRecordTypeGOK])) {
								$hitCount = $this->hitCounts[NKWGOKRecordTypeGOK][$foundGOK];
							}
						}
						else {
							$hitCount = 0;
						}

						// Add total hit count information if it exists.
						if (array_key_exists($PPN, $totalHitCounts)) {
							$totalHitCount = $totalHitCounts[$PPN];
						}

						$fromOpac = ($GOK['type'] !== 'csv');
						$rows[] = Array($PPN, $hierarchy, $GOKString, $parent, $descr, $search, $descr_en, $tags, $childCount, $fromOpac, $hitCount, $totalHitCount, time(), time(), 1);
					}
				} // end of loop over GOKs
				$keyNames = Array('ppn', 'hierarchy', 'gok', 'parent', 'descr', 'search', 'descr_en', 'tags', 'childcount', 'fromopac', 'hitcount', 'totalhitcount', 'crdate', 'tstamp', 'statusID');
				$result = $GLOBALS['TYPO3_DB']->exec_INSERTmultipleRows('tx_nkwgok_data', $keyNames, $rows);

			} // end of loop over files

			$result = True;
		}
		else {
			t3lib_div::devLog('loadXML Scheduler Task: Found no XML files for type ' . $type . '.', 'nkwgok', 3);
			$result = True;
		}

		return $result;
	}



	/**
	 * Helper function, adding the GOK and BRK root nodes to the database.
	 */
	private function addRootNodes () {
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
		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nkwgok_data', $row);

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
		$tree[NKWGOKGOKRootNode] = Array('children' => Array(), 'parent' => NKWGOKRootNode, 'type' => NKWGOKRecordTypeGOK);
		$tree[NKWGOKBRKRootNode] = Array('children' => Array(), 'parent' => NKWGOKRootNode, 'type' => NKWGOKRecordTypeBRK);
		$tree[NKWGOKRootNode] = Array('children' => Array(NKWGOKGOKRootNode, NKWGOKBRKRootNode));

		// Run through all files once to gather information about the
		// structure of the data we process.
		foreach ($fileList as $xmlPath) {
			$xml = simplexml_load_file($xmlPath);
			$records = $xml->xpath('/RESULT/SET/SHORTTITLE/record');

			foreach ($records as $record) {
				$PPNs = $record->xpath('datafield[@tag="003@"]/subfield[@code="0"]');
				$PPN = (string)($PPNs[0]);
				$recordType = $this->typeOfRecord($record);

				// Create entry in the tree array if necessary.
				if (!array_key_exists($PPN, $tree)) {
					$tree[$PPN] = Array('children' => Array(), 'type' => $recordType);
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
					if ($recordType === NKWGOKRecordTypeGOK) {
						// GOK subject authority record.
						$parentPPN = NKWGOKGOKRootNode;
					}
					else if ($recordType === NKWGOKRecordTypeBRK) {
						// BRK subject authoriy record.
						$parentPPN = NKWGOKBRKRootNode;
					}
					else {
						// Other record: goes in at top level.
						$parentPPN = NKWGOKRootNode;
					}

					$tree[$parentPPN]['children'][] = $PPN;
					$tree[$PPN]['parent'] = $parentPPN;
				}

				// Store notation information.
				$notationStrings = $record->xpath('datafield[@tag="045A"]/subfield[@code="a"]');
				if (count($notationStrings) > 0) {
					$notationString = (string)($notationStrings[0]);
					$notation = strtolower(trim($notationString));
					$tree[$PPN][$recordType] = $notation;
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
	 * These files are downloaded from the Opac by the loadFromOpac Scheduler task.
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
				t3lib_div::devLog('loadXML Scheduler Task: could not load/parse XML from ' . $xmlPath, 'nkwgok', 3);
			}
		} // end foreach

		foreach ($hitCounts as $hitCountType => $array) {
			t3lib_div::devLog('loadXML Scheduler Task: Loaded ' . count($array) . ' ' . $hitCountType . ' hit count entries.', 'nkwgok', 1);
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
			$notation = $record[$type];
			if (array_key_exists('msc', $record)
				&& $type === NKWGOKRecordTypeGOK) {
				$type = 'msc';
				$notation = $record['msc'];
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
		else if ($type === NKWGOKRecordTypeGOK || $type === NKWGOKRecordTypeBRK) {
			$fileList = glob($basePath . $type . '-*.xml');
		}
		else {
			$allFiles = glob($basePath . '*.xml');
			$gokFiles = glob($basePath . NKWGOKRecordTypeGOK . '-*.xml');
			$brkFiles = glob($basePath . NKWGOKRecordTypeBRK . '-*.xml');
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
		$recordType = NKWGOKRecordTypeUnknown;
		$recordTypes = $record->xpath('datafield[@tag="002@"]/subfield[@code="0"]');

		if ($recordTypes && count($recordTypes) === 1) {
			$recordTypeCode = (string)$recordTypes[0];

			if ($recordTypeCode === 'Tev') {
				$recordType = NKWGOKRecordTypeGOK;
			}
			else if ($recordTypeCode === 'Tov') {
				$recordType = NKWGOKRecordTypeBRK;
			}
			else if ($recordTypeCode === 'csv') {
				$recordType = NKWGOKRecordTypeCSV;
			}
		}

		if ($recordType === NKWGOKRecordTypeUnknown) {
			t3lib_div::devLog('loadXML Scheduler Task: Record of unknown type.', 'nkwgok', 1, Array($record->saveXML()));
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
