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

class tx_nkwgok_eid {

	/**
	 * @return string
	 */
	public function eid_main() {

		$arguments = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('tx_nkwgok');
		$nkwgok = tx_nkwgok::instantiateSubclassFor($arguments);
		$output = $nkwgok->getAJAXMarkup();

		return $output->saveHTML();
	}

}

$nkwgok_eid = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_nkwgok_eid::class);
echo $nkwgok_eid->eid_main();
