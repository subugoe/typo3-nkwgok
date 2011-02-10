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
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
$TCA['tx_nkwgok_data'] = array(
	'ctrl' => $TCA['tx_nkwgok_data']['ctrl'], 
	'interface' => array(
		'showRecordFieldList' => 'gok,ppn,descr,parent,hierarchy'
	),
	'feInterface' => $TCA['tx_nkwgok_data']['feInterface'],
	'columns' => array(
		'gok' => array(
			'exclude' => 0, 
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.gok', 
			'config' => array(
				'type' => 'input', 
				'size' => '30', 
				'eval' => 'trim',
			)
		),
		'ppn' => array(
			'exclude' => 0, 
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.ppn', 
			'config' => array( 
				'type' => 'input', 
				'size' => '30', 
				'eval' => 'trim',
			)
		),
		'descr' => array(
			'exclude' => 0, 
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.descr', 
			'config' => array(
				'type' => 'text', 
				'cols' => '30', 
				'rows' => '5'
			)
		),
		'descr-en' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.descr_en',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5'
			)
		),
		'parent' => array(
			'exclude' => 0, 
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.parent', 
			'config' => array(
				'type' => 'input', 
				'size' => '30', 
				'eval' => 'trim',
			)
		),
		'hierarchy' => array(
			'exclude' => 0, 
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.hierarchy', 
			'config' => array(
				'type'     => 'input', 
				'size'     => '4', 
				'max'      => '4', 
				'eval'     => 'int', 
				'checkbox' => '0', 
				'range'    => array(
					'upper' => '1000', 
					'lower' => '10'
				), 
				'default' => 0
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'gok;;;;1-1-1, ppn, descr, parent, hierarchy')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
?>