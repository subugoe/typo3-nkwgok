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
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY, 'pi1/class.tx_nkwgok_pi1.php', '_pi1', 'list_type', 1);

// Scheduler task for downloading LKL data from OPAC.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_nkwgok_loadFromOpac'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.loadFromOpac.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.loadFromOpac.description',
);

// Scheduler task for downloading LKL data from OPAC.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_nkwgok_convertCSV'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.convertCSV.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.convertCSV.description',
	'additionalFields' => 'tx_nkwgok_scheduler_convertcsvadditionalparameters'
);

// Scheduler task for importing Pica authority record XML files into TYPO3 database.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_nkwgok_loadxml'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.loadxml.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.loadxml.description',
);

// Scheduler task to determine whether we need to run the scheduler task for
// converting and importing CSV, and doing that if we do.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_nkwgok_checkNewCSV'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.checkNewCSV.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.checkNewCSV.description',
	'additionalFields' => 'tx_nkwgok_scheduler_convertcsvadditionalparameters'
);

// Scheduler task 2+3 for converting the CSV files and reimporting the database.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_nkwgok_updateCSV'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.updateCSV.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.updateCSV.description',
	'additionalFields' => 'tx_nkwgok_scheduler_convertcsvadditionalparameters'
);

// Scheduler task 1+2+3 for running our 3 other Scheduler tasks in the correct order.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_nkwgok_importAll'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.importAll.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:scheduler.importAll.description',
	'additionalFields' => 'tx_nkwgok_scheduler_convertcsvadditionalparameters'
);

$TYPO3_CONF_VARS['FE']['eID_include']['nkwgok'] = 'EXT:nkwgok/lib/get.php';
