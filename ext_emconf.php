<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Subject Hierarchy / GOK',
    'description' => 'Displays subject hierarchies as trees, menus or columns',
    'category' => 'plugin',
    'author' => 'Nils K. Windisch, Sven-S. Porst',
    'author_email' => 'www@sub.uni-goettingen.de',
    'shy' => '',
    'dependencies' => '',
    'conflicts' => '',
    'suggests' => '',
    'priority' => '',
    'module' => '',
    'state' => 'stable',
    'internal' => '',
    'uploadfolder' => 0,
    'createDirs' => 'fileadmin/gok,fileadmin/gok/xml,fileadmin/gok/hitcounts,fileadmin/gok/csv,',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'lockType' => '',
    'author_company' => 'Göttingen State and University Library, Germany http://www.sub.uni-goettingen.de',
    'version' => '6.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.4.0-9.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
