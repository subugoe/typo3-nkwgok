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
    die('Access denied.');
}

$nkwgok_data = [
    'ctrl' => [
        'title' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data',
        'label' => 'notation',
        'label_alt' => 'descr',
        'label_alt_force' => 1,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath($_EXTKEY).'Resources/Public/Images/ext_icon.gif',
        'searchFields' => 'descr, descr_en, notation',
    ],
    'interface' => [
        'showRecordFieldList' => 'notation,search,ppn,descr,descr_en,parent,hierarchy,hitcount,tags',
    ],
    'columns' => [
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'statusID' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'ppn' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.ppn',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'notation' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.notation',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'search' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.search',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'descr' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.descr',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'descr_en' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.descr_en',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'descr_alternate' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.descr_alternate',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'descr_alternate_en' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.descr_alternate_en',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'parent' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.parent',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
        'hierarchy' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.hierarchy',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '4',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '31',
                    'lower' => '0',
                ],
                'default' => 0,
            ],
        ],
        'childcount' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.childcount',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '4',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '10000',
                    'lower' => '0',
                ],
                'default' => 0,
            ],
        ],
        'hitcount' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.hitcount',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '10000000',
                    'lower' => '-1',
                ],
                'default' => 0,
            ],
        ],
        'totalhitcount' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.totalhitcount',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '10000000',
                    'lower' => '-1',
                ],
                'default' => 0,
            ],
        ],
        'tags' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.tags',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5',
            ],
        ],
        'type' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/Resources/Private/Language/locallang_db.xml:tx_nkwgok_data.type',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'ppn, notation, search, descr, descr_en, descr_alternate, descr_alternate_en, parent, hierarchy, childcount, hitcount, totalhitcount, tags, type'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

return $nkwgok_data;
