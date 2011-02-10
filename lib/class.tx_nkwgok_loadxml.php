<?php
/***************************************************************
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
***************************************************************/


/**
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
class tx_nkwgok_loadxml extends tx_scheduler_Task {

	/**
	 * Function executed from the Scheduler.
	 *
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {
		$result = False;
		$wantedFieldNames = array('045A', '044E', '044F', '009B', '038D', '003@', '045G', 'str');
		$dir = PATH_site . '/fileadmin/gok/';
		$fileList = glob($dir . '*.xml');

		if (count($fileList) > 0) {
			// Empty our database table.
			$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE tx_nkwgok_data');

			// Run through all files once to get a list of parent element PPNs.
			// $parentPPNs is a dictionary whose keys are the parent element PPNs.
			$parentPPNs = Array();

			foreach ($fileList as $xmlPath) {
				$xml = simplexml_load_file($xmlPath);
				$parentGOKs = $xml->xpath('/RESULT/SET/SHORTTITLE/record/datafield[@tag="038D"]/subfield[@code="9"]');
				foreach($parentGOKs as $parentPPN) {
					$parentPPNs[trim($parentPPN)] = True;
				}
			}

			// Run through the files again, read all data, add the information
			// about parent elements and store it to our table in the database.
			foreach ($fileList as $xmlPath) {
				$xml = simplexml_load_file($xmlPath);

				foreach ($xml->SET->SHORTTITLE AS $GOKElement) {
					$previousFieldName = Null;
					$GOK = Array();

					foreach($GOKElement->record->datafield AS $field) {
						$fieldName = trim((string)$field->attributes());

						// Only read the desired fields
						if (in_array($fieldName, $wantedFieldNames)) {
							// append "_2" to field Name to avoid duplication (ahem)
							if ($fieldName == $previousFieldName) {
								$fieldName = $fieldName . '_2';
							}
							$previousFieldName = $fieldName;

							// Get subfields
							foreach ($field->subfield as $subfield) {
								$subfieldName = (string)$subfield['code'];
								$subfieldContent = trim((string)$subfield);
								if ($subfieldContent) {
									$GOK[$fieldName][$subfieldName] = $subfieldContent;
								}
							}
						}
					} // end of datafield loop


					// Build complete record and insert into database.
					if ($GOK['str']) {
						$search = $GOK['str']['a'];
					}
					elseif ($GOK['045G'] && $GOK['045G']['C'] == 'MSC') {
						$search = $GOK['045G']['C'] . '+' . $GOK['045G']['a'];
					}
					else {
						$search = $GOK['045A']['a'];
					}

					$search = preg_replace(	array('/lkl/', '/\ /', '/\?/'),
											array('LKL', '+', '%3F'),
											trim($search) );

					$GOKPPN = trim($GOK['003@']['0']);

					$values = array(
						'ppn' => $GOKPPN,
						'parent' => trim($GOK['038D'][9]),
						'hierarchy' => trim($GOK['009B']['a']),
						'descr' => trim($GOK['044E']['a']),
						'gok' => trim($GOK['045A']['a']),
						'crdate' => time(),
						'tstamp' => time(),
						'search' => $search,
						'haschildren' => ($parentPPNs[$GOKPPN]) ? 1 : 0
					);

					if ($GOK['044F']['b'] == 'eng' && $GOK['044F']['a']) {
						$values['descr_en'] = trim($GOK['044F']['a']);
					}

					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nkwgok_data', $values);

				} // end of loop over GOKs
			} // end of loop over files

			t3lib_div::devLog('Loading of GOK XML completed', 'nkwgok', 1);
			$result = True;
		}
		else {
			t3lib_div::devLog('No "*.xml" files found in ' . $dir, 'nkwgok', 3);
		}

		return $result;
	}

}


if (defined('TYPO3_MODE')
	&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadxml.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadxml.php']);
}

?>
