<?php

/* * *************************************************************
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
 * ************************************************************* */
if (!defined('PATH_typo3conf')) {
	die('Could not access this script directly!');
}
require_once(t3lib_extMgm::extPath('nkwgok') . 'lib/class.tx_nkwgok.php');

/**
 * undocumented class
 *
 * @package default
 * @author Nils K. Windisch
 * */
class tx_nkwgok_eid extends tx_nkwgok {

	/**
	 * undocumented function
	 *
	 * @return void
	 * @author Nils K. Windisch
	 * */
	function eid_main() {
		// initialize DB functions
		tslib_eidtools::connectDB();

		// even more get values
		$get = t3lib_div::_GET();
		$nkwgokArgs = $get['tx_nkwgok'];

		// and display them
		$nkwgok = t3lib_div::makeInstance('tx_nkwgok');
		$ppn = $nkwgokArgs['expand'];
		$display = Null;
		if ($nkwgokArgs['style'] == 'menu') {
			$display = $nkwgok->AJAXGOKMenuChildren($ppn, $nkwgokArgs['level'], $nkwgokArgs['language']);
		}
		else {
			$display = $nkwgok->AJAXGOKTreeChildren($ppn, $nkwgokArgs['style'], $nkwgokArgs['language']);
		}
	
		// track action
		if ($statsEnabled === true) {
			$doStatistics = t3lib_div::getUserObj('EXT:ke_stats/pi1/class.tx_kestats_pi1.php:tx_kestats_pi1');
			$doStatistics->initApi();
			$doStatistics->increaseCounter('GOK', 'element_title,year,month', $ppn, $ppn, 290, 1, 1, 'extension', FALSE);
			unset($doStatistics);
		}
		// return ajax call output
		echo $display->saveHTML();
	}

}

$nkwgok_eid = t3lib_div::makeInstance('tx_nkwgok_eid');
$nkwgok_eid->eid_main();
?>