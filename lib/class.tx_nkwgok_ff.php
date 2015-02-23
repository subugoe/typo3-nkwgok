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
 * Changes 2011-2013 by Sven-S. Porst <porst@sub.uni-goettingen.de>
 * See the Changelog or git repository for details.
 */


require_once(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nkwgok') . 'lib/class.tx_nkwgok_utility.php');

/**
 * Description
 */
class tx_nkwgok_ff {
	function addFields($config) {
		$rootNodes = $this->queryForChildrenOf(tx_nkwgok_utility::rootNode);

		$options = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($rootNodes)) {
			$optionTitle = '[' . $row['notation'] . '] ' . $row['descr'];
			$optionValue = $row['notation'];
			$options[] = array($optionTitle, $optionValue);

			$childNodes = $this->queryForChildrenOf($row['ppn']);
			while ($childRow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($childNodes)) {
				$childOptionTitle = '—[' . $childRow['notation'] . '] ' . $childRow['descr'];
				$childOptionValue = $childRow['notation'];
				$options[] = array($childOptionTitle, $childOptionValue);
			}
		}

		$config['items'] = array_merge($config['items'], $options);
		return $config;
	}


	/**
	 * Queries the database for all records having the $parentID parameter as
	 * their parent element and returns the query result.
	 *
	 * @param string $parentID
	 * @return array
	 */
	private function queryForChildrenOf($parentID) {
		$queryResults = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				tx_nkwgok_utility::dataTable,
				"parent = '" . $parentID . "'",
				'',
				'notation ASC',
				'');

		return $queryResults;
	}

}
