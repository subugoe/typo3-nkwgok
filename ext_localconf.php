<?php

defined('TYPO3_MODE') or die();

$boot = function () {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Subugoe.Nkwgok',
        'Pi1',
        ['Default' => 'main']
    );
    // Scheduler task for downloading LKL data from OPAC.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\LoadFromOpacCommand::class;

    // Scheduler task for downloading LKL data from OPAC.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\ConvertCsvCommandController::class;

    // Scheduler task for importing Pica authority record XML files into TYPO3 database.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\LoadXmlCommand::class;

    // Scheduler task to determine whether we need to run the scheduler task for
    // converting and importing CSV, and doing that if we do.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\CheckNewCsvCommand::class;

    // Scheduler task 2+3 for converting the CSV files and reimporting the database.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\UpdateCsvCommand::class;

    // Scheduler task 1+2+3 for running our 3 other Scheduler tasks in the correct order.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\ImportAllCommand::class;

    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['nkwgok'] = 'EXT:nkwgok/lib/get.php';
};

$boot();
unset($boot);
