<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TCA['tx_nkwgok_configuration'] = array(
	'ctrl' => array(
		'label' => 'title',
		'dividers2tabs' => TRUE,
	),
	'types' => array(
		'0' => array(
			'showitem' => 'urlList',
		),
	),
	'palettes' => array(
		'0' => array(
			'showitem' => ''
		),
	),
	'columns' => array(
		'urlList' => array(
			'label'   => 'LLL:EXT:nkwgok/locallang.xml:tx_nkwgok.urlList',
			'config'  => array(
				'type' => 'text',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'selectedListStyle' => 'width: 250px; height: 34px;',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
	),
);

?>