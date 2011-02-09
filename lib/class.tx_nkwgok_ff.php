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
class tx_nkwgok_ff {
	function addFields($config) {
		$res0 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'*', 
			'tx_nkwgok_data', 
			"parent = ''", 
			'', 
			'gok ASC', 
			'');
		$optionList = array();
		$optionList[0] = array(0 => 'All', 1 => 'all');
		while($row0 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res0)) {
			$optionList[] = array(0 => '(' . $row0['gok'] . ') ' . $row0['descr'] , 1 => $row0['gok']);
		}
		$config['items'] = array_merge($config['items'], $optionList);
		return $config;
	}
}
?>