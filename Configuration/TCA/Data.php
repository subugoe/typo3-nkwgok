<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$TCA['tx_nkwgok_data'] = [
    'ctrl' => $TCA['tx_nkwgok_data']['ctrl'],
    'interface' => [
        'showRecordFieldList' => 'notation,search,ppn,descr,descr_en,parent,hierarchy,hitcount,tags'
    ],
    'feInterface' => $TCA['tx_nkwgok_data']['feInterface'],
    'columns' => [
        'ppn' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.ppn',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'notation' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.notation',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'search' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.search',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'descr' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.descr',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            ]
        ],
        'descr_en' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.descr_en',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            ]
        ],
        'descr_alternate' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.descr_alternate',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            ]
        ],
        'descr_alternate_en' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.descr_alternate_en',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            ]
        ],
        'parent' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.parent',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'hierarchy' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.hierarchy',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '4',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '31',
                    'lower' => '0'
                ],
                'default' => 0
            ]
        ],
        'childcount' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.childcount',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '4',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '10000',
                    'lower' => '0'
                ],
                'default' => 0
            ]
        ],
        'hitcount' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.hitcount',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '10000000',
                    'lower' => '-1'
                ],
                'default' => 0
            ]
        ],
        'totalhitcount' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.totalhitcount',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '10',
                'eval' => 'int',
                'checkbox' => '0',
                'range' => [
                    'upper' => '10000000',
                    'lower' => '-1'
                ],
                'default' => 0
            ]
        ],
        'tags' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.tags',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            ]
        ],
        'type' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:nkwgok/locallang_db.xml:tx_nkwgok_data.type',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'ppn, notation, search, descr, descr_en, descr_alternate, descr_alternate_en, parent, hierarchy, childcount, hitcount, totalhitcount, tags, type']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];
?>
