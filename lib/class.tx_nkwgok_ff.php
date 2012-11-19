<?php
/***************************************************************
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
***************************************************************/

/**
 * Description 
 */
class tx_nkwgok_ff {
	function addFields($config) {
		$rootNodes = $this->queryForChildrenOf(tx_nkwgok_utility::rootNode);

		$options = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rootNodes)) {
			$optionTitle = '[' . $row['gok'] . '] ' . $row['descr'];
			$optionValue = $row['gok'];
			$options[] = array($optionTitle , $optionValue);

			$childNodes = $this->queryForChildrenOf($optionValue);
			while($childRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($childNodes)) {
				$childOptionTitle = '—[' . $childRow['gok'] . '] ' . $childRow['descr'];
				$childOptionValue = $childRow['gok'];
				$options[] = array($childOptionTitle , $childOptionValue);
			}
		}

		$config['items'] = array_merge($config['items'], $options);
		return $config;
	}



	/**
	 * Queries the database for all records having the $parentGOK parameter as their parent element
	 *  and returns the query result.
	 *
	 * @param string $parentGOK
	 * @return array
	 */
	private function queryForChildrenOf ($parentGOK) {
		$queryResults = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*',
			tx_nkwgok_utility::dataTable,
			"parent = '" . $parentGOK . "'",
			'',
			'gok ASC',
			'');

		return $queryResults;
	}

}
?>