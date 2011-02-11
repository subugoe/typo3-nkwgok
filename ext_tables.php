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
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$TCA['tx_nkwgok_data'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'default_sortby' => 'ORDER BY crdate',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'icon_tx_nkwgok_data.gif',
	),
);
t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key,pages';
$TCA['tt_content']['types']['list']['subtypes_addlist'][$_EXTKEY . '_pi1'] = 'pi_flexform';
t3lib_extMgm::addPiFlexFormValue($_EXTKEY . '_pi1', 'FILE:EXT:nkwgok/pi1/flexform.xml');
t3lib_extMgm::addPlugin(
				array(
					'LLL:EXT:nkwgok/locallang_db.xml:tt_content.list_type_pi1',
					$_EXTKEY . '_pi1',
					t3lib_extMgm::extRelPath($_EXTKEY) . 'ext_icon.gif'),
				'list_type');
if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addLLrefForTCAdescr('xMOD_tx_nkwgok', 'EXT:' . $_EXTKEY . '/locallang_csh.xml');
}
// class for dynamic FF
include_once(t3lib_extMgm::extPath($_EXTKEY) . '/lib/class.tx_nkwgok_ff.php');
?>