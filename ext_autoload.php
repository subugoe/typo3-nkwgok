<?php

$extensionPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('nkwgok');
return [
    'tx_nkwgok' => $extensionPath . 'lib/class.tx_nkwgok.php',
    'tx_nkwgok_column' => $extensionPath . 'lib/class.tx_nkwgok_column.php',
    'tx_nkwgok_ff' => $extensionPath . 'lib/class.tx_nkwgok_ff.php',
    'tx_nkwgok_menu' => $extensionPath . 'lib/class.tx_nkwgok_menu.php',
    'tx_nkwgok_tree' => $extensionPath . 'lib/class.tx_nkwgok_tree.php',
    'tx_nkwgok_eid' => $extensionPath . 'lib/get.php',
    'tx_nkwgok_pi1' => $extensionPath . 'pi1/class.tx_nkwgok_pi1.php',
    'tx_nkwgok_checknewcsv' => $extensionPath . 'scheduler/class.tx_nkwgok_checknewcsv.php',
    'tx_nkwgok_convertcsv' => $extensionPath . 'scheduler/class.tx_nkwgok_convertcsv.php',
    'tx_nkwgok_importall' => $extensionPath . 'scheduler/class.tx_nkwgok_importall.php',
    'tx_nkwgok_loadfromopac' => $extensionPath . 'scheduler/class.tx_nkwgok_loadfromopac.php',
    'tx_nkwgok_loadxml' => $extensionPath . 'scheduler/class.tx_nkwgok_loadxml.php',
    'tx_nkwgok_updatecsv' => $extensionPath . 'scheduler/class.tx_nkwgok_updatecsv.php',
    'tx_nkwgok_utility' => $extensionPath . 'lib/class.tx_nkwgok_utility.php',
];
