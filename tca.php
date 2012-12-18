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
		'showRecordFieldList' => 'gok,search,ppn,descr,descr_en,parent,hierarchy,hitcount,tags'
	),
	'feInterface' => $TCA['tx_nkwgok_data']['feInterface'],
	'columns' => array(
		'ppn' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.ppn',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'gok' => array(
			'exclude' => 0, 
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.gok', 
			'config' => array(
				'type' => 'input', 
				'size' => '30', 
				'eval' => 'trim',
			)
		),
		'search' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.search',
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
		'descr_en' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.descr_en',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5'
			)
		),
		'descr_alternate' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.descr_alternate',
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
				'size'     => '10',
				'max'      => '4', 
				'eval'     => 'int', 
				'checkbox' => '0', 
				'range'    => array(
					'upper' => '31',
					'lower' => '0'
				), 
				'default' => 0
			)
		),
		'childcount' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.childcount',
			'config' => array(
				'type'     => 'input',
				'size'     => '10',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array(
					'upper' => '10000',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'hitcount' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.hitcount',
			'config' => array(
				'type'     => 'input',
				'size'     => '10',
				'max'      => '10',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array(
					'upper' => '10000000',
					'lower' => '-1'
				),
				'default' => 0
			)
		),
		'totalhitcount' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.totalhitcount',
			'config' => array(
				'type'     => 'input',
				'size'     => '10',
				'max'      => '10',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array(
					'upper' => '10000000',
					'lower' => '-1'
				),
				'default' => 0
			)
		),
		'tags' => array(
			'exclude' => 0, 
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.tags', 
			'config' => array(
				'type' => 'text', 
				'cols' => '30', 
				'rows' => '5'
			)
		),
		'type' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.type',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'ppn, gok, search, descr, descr_en, parent, hierarchy, childcount, hitcount, totalhitcount, tags, type')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
?>