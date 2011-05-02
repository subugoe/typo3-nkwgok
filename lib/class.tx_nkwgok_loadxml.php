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
	 * Function executed from the Scheduler.
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {
		$result = False;

		$hitCounts = $this->getHitCounts();

		$wantedFieldNames = array('045A', '044E', '044F', '009B', '038D', '003@', '045G', 'str');
		$dir = PATH_site . 'fileadmin/gok/lkl/';
		$fileList = glob($dir . '*.xml');

		if (count($fileList) > 0) {
			// Empty our database table.
			$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE tx_nkwgok_data');

			// Run through all files once to get a child count for each parent
			// element in the list.
			// $parentPPNs is a dictionary whose keys are the parent element PPNs.
			$parentPPNs = Array(NKWGOKRootNode => Array(), NKWGOKGOKRootNode => Array());
			foreach ($fileList as $xmlPath) {
				$xml = simplexml_load_file($xmlPath);
				$records = $xml->xpath('/RESULT/SET/SHORTTITLE/record');
				foreach ($records as $record) {
					$PPN = trim((string)$record->xpath('datafield[@tag="003@"]/subfield[@code="0"]'));
					$myParentPPNs = $record->xpath('datafield[@tag="038D"]/subfield[@code="9"]');
					if (count($myParentPPNs) > 0) {
						$parentPPN = (string)$myParentPPNs[0];
						if ($parentPPNs[$parentPPN]) {
							$parentPPNs[$parentPPN][] = $PPN;
						}
						else {
							$parentPPNs[$parentPPN] = Array($PPN);
						}
					}
					else {
						$fromOpac = (count($record->xpath('datafield[@tag="str"]')) == 0);
						if ($fromOpac) {
							$parentPPNs[NKWGOKGOKRootNode][] = $PPN;
						}
						else {
							$parentPPNs[NKWGOKRootNode][] = $PPN;
						}
					}
				}
			}

			// Run through the files again, read all data, add the information
			// about parent elements and store it to our table in the database.
			foreach ($fileList as $xmlPath) {
				$xml = simplexml_load_file($xmlPath);

				foreach ($xml->xpath('/RESULT/SET/SHORTTITLE') as $GOKElement) {
					$previousFieldName = Null;
					$GOK = Array();

					foreach ($GOKElement->xpath('record/datafield') as $field) {
						$fieldName = trim((string) $field->attributes());

						// Only read the desired fields
						if (in_array($fieldName, $wantedFieldNames)) {
							// append "_2" to field Name to avoid duplication (ahem)
							if ($fieldName == $previousFieldName) {
								$fieldName = $fieldName . '_2';
							}
							$previousFieldName = $fieldName;

							// Get subfields
							foreach ($field->xpath('subfield') as $subfield) {
								$subfieldName = (string) $subfield['code'];
								$subfieldContent = trim((string) $subfield);
								if ($subfieldContent) {
									$GOK[$fieldName][$subfieldName] = $subfieldContent;
								}
							}
						}
					} // end of datafield loop


					// Build complete record and insert into database.
					// Discard records without a PPN.
					$PPN = trim($GOK['003@']['0']);
					if ($PPN != '') {
						$childCount = 0;
						if ($parentPPNs[$PPN]) {
							$childCount = count($parentPPNs[$PPN]);
						}

						$parent = trim($GOK['038D'][9]);
	
						$search = '';
						if ($GOK['str']) {
							// GOK coming from CSV file with the complete search term in the 'str/a' field.
							$search = $GOK['str']['a'];
							$search = str_replace('lkl', 'LKL', $search);
							if ($parent == '') {
								$parent = NKWGOKRootNode;
							}
						}
						else {
							// GOK coming from standard Opac record.
							if ($GOK['045G'] && $GOK['045G']['C'] == 'MSC') {
								// Maths type GOK with an MSC type search term.
								$search = 'MSC ' . $GOK['045G']['a'];
							}
							else {
								// Generic GOK search, using the LKL field.
								$search = 'LKL ' . $GOK['045A']['a'];
							}
							
							// Set parent element to GOK-Root if it, i.e. '038D/9', is blank.
							if ($parent == '') {
								$parent = NKWGOKGOKRootNode;
							}
						}

						$search = trim($search);
						$search = urlencode($search);

						$GOKString = trim($GOK['045A']['a']);
						$values = array(
							'ppn' => $PPN,
							'parent' => $parent,
							'hierarchy' => trim($GOK['009B']['a']),
							'descr' => trim($GOK['044E']['a']),
							'gok' => $GOKString,
							'crdate' => time(),
							'tstamp' => time(),
							'search' => $search,
							'childcount' => $childCount
						);

						if ($GOK['044F']['b'] == 'eng' && $GOK['044F']['a']) {
							$values['descr_en'] = trim($GOK['044F']['a']);
						}

						// Hit keys are lowercase.
						// Set result count to 0 for all entries but those of 'str' type
						// for which we use -1 to indicate the count is unknown.
						if ($hitCounts[strtolower($GOKString)]) {
							$values['hitcount'] = (int)$hitCounts[strtolower($GOKString)];
						}
						else if ($GOK['str']) {
							$values['hitcount'] = -1;
						}
						else {
							$values['hitcount'] = 0;
						}

						$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nkwgok_data', $values);
					}
				} // end of loop over GOKs
			} // end of loop over files

			// Finally add the GOK root node
			$values = array(
				'ppn' => NKWGOKGOKRootNode,
				'parent' => NKWGOKRootNode,
				'hierarchy' => '-1',
				'descr' => 'Göttinger Online Klassifikation (GOK)',
				'descr_en' => 'Göttingen Online Classification (GOK)',
				'gok' => NKWGOKGOKRootNode,
				'crdate' => time(),
				'tstamp' => time(),
				'childcount' => count($parentPPNs[NKWGOKGOKRootNode])
			);

			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nkwgok_data', $values);

			t3lib_div::devLog('loadXML Scheduler Task: Import of GOK XML to Typo3 database completed', 'nkwgok', 1);
			$result = True;
		} else {
			t3lib_div::devLog('loadXML Scheduler Task: No "*.xml" files found in ' . $dir, 'nkwgok', 3);
		}

		return $result;
	}



	/**
	 * Load LKL hit counts from /fileadmin/gok/hitcounts/*.xml
	 * These files are downloaded from the Opac by the loadFromOpac
	 * Scheduler task.
	 *
	 * @author Sven-S. Porst
	 * @return array keys: LKL entries, values: hit count for the entry
	 */
	private function getHitCounts () {
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
						if ($name == 'hits') {
							// Reduce the hit count by 1 as it includes the GOK-Normsatz.
							$hits = (int)$value - 1;
						}
						else if ($name == 'description') {
							$description = (string)$value;
						}
					}
					if ($hits != Null && $description) {
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


}



if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadxml.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadxml.php']);
}
?>
