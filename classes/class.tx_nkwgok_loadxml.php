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

ini_set ('max_execution_time', '180');

/**
 * Class "tx_nkwgok_loadxml" provides task procedures
 *
 * @author		Nils K. Windisch <windisch@sub.uni-goettingen.de>
 * @package		TYPO3
 * @subpackage	tx_nkwgok
 *
 */
class tx_nkwgok_loadxml extends tx_scheduler_Task {

	/**
	 * A page uid to be cleaned up
	 *
	 * @var	int		$pageid
	 */
	 var $pageid;

	/**
	 * Function executed from the Scheduler.
	 * Hides all content elements of a page
	 *
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {

		$success = FALSE;
		$trigger = array('045A', '044E', '009B', '038D', '003@', '045G', 'str');
		$dir = $GLOBALS['_SERVER']['DOCUMENT_ROOT'] . '/fileadmin/gok/';

		// get files for import
		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != '.' && $file != '..' && $file != '.DS_Store') {
					$files[] = $file;
				}
			}
			closedir($handle);
		}

		$size = sizeof($files);

		$arrParents = array();

		if ($size >= 1) {
			// clean (truncate) DB table
			$GLOBALS['TYPO3_DB']->sql_query('TRUNCATE tx_nkwgok_data');

			$time = time();

			for($i=0; $i<$size; $i++) {
				$actionFile = $dir . $files[$i];
				$xml = simplexml_load_file($actionFile);

				$x = 0;
				foreach($xml->SET->SHORTTITLE AS $set) {
					unset($arr[$x]);
					foreach($set->record->datafield AS $field) {
						# we are here (current datafield)
						$thisField = trim((string)$field->attributes());

						# trigger only if datafields we really want
						if (in_array($thisField,$trigger)) {

							# check duplicate tag fields
							# append "_2" if duplicate
							if ($thisField == $prevField) {
								$thisField = $thisField."_2";
							}
							$prevField = $thisField;

							# get first subfield
							if (trim((string)$field->subfield[0])) {
								$arr[$x][$thisField][(string)$field->subfield[0]["code"]] = trim((string)$field->subfield[0]);
							}
							# get second subfield
							if (trim((string)$field->subfield[1])) {
								$arr[$x][$thisField][(string)$field->subfield[1]["code"]] = trim((string)$field->subfield[1]);
							}

							if ($arr[$x]["038D"][9]) {
								array_push($arrParents, $arr[$x]['038D'][9]);
							}

						}

					}
					$x++;
				}

				$arrParents = array_unique($arrParents);

				for($x=0; $x<sizeof($arr); $x++) {

					unset($values);
					unset($search);
					$haschildren = 0;

					if (in_array($arr[$x]['003@'][0], $arrParents)) {
						$haschildren = 1;
					}

					if ($arr[$x]["str"]) {
						$search = trim($arr[$x]['str']['a']);
					} elseif ($arr[$x]['045G'] && $arr[$x]['045G']['C'] == 'MSC') {
						$search = $arr[$x]['045G']['C'] . '+' . $arr[$x]['045G']['a'];
					} else {
						$search = $arr[$x]['045A']['a'];
					}

					$search = preg_replace(
								array('/lkl/', '/\ /', '/\?/'),
								array('LKL', '+', '%3F'),
								$search);
		
					$values = array(
							'ppn' => trim($arr[$x]['003@'][0]),
							'parent' => trim($arr[$x]['038D'][9]),
							'hierarchy' => trim($arr[$x]['009B']['a']),
							'descr' => trim($arr[$x]['044E']['a']),
							'gok' => trim($arr[$x]['045A']['a']),
							'crdate' => $time,
							'tstamp' => $time,
							'search' => $search,
							'haschildren' => $haschildren
						);
				
					$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nkwgok_data', $values); // write to DB

				}

			}
		}

		t3lib_div::devLog('[scheduler: GOK Load XML]: Yeah!'.$asdf, 'nkwgok', 3);

		$success = TRUE;

		return $success;
	}

}

if (defined('TYPO3_MODE') 
	&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/classes/class.tx_nkwgok_loadxml.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/classes/class.tx_nkwgok_loadxml.php']);
}

?>
