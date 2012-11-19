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

/**
 * Changes 2011-2012 by Sven-S. Porst <porst@sub.uni-goettingen.de>
 * See the ChangeLog or git repository for details.
 */


if (!defined('PATH_typo3conf')) {
	die('Could not access this script directly!');
}


/**
 * @package default
 * @author Nils K. Windisch
 * */
class tx_nkwgok_eid {

	/**
	 * @return void
	 * @author Nils K. Windisch
	 * @author Sven-S. Porst <porst@sub.uni-goettingen.de>
	 * */
	function eid_main() {
		// initialize DB functions
		tslib_eidtools::connectDB();

		$arguments = t3lib_div::_GET('tx_nkwgok');
		$nkwgok = tx_nkwgok::instantiateSubclassFor($arguments);
		$output = $nkwgok->getAJAXMarkup();
	
		echo $output->saveHTML();
	}

}

$nkwgok_eid = t3lib_div::makeInstance('tx_nkwgok_eid');
$nkwgok_eid->eid_main();

?>
