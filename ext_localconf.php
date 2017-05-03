<?php

defined('TYPO3_MODE') or die();

$boot = function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43('nkwgok',
        'Classes/Controller/DefaultController.php', '', 'list_type', 1);

// Scheduler task for downloading LKL data from OPAC.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\LoadFromOpacCommandController::class;
    /*
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Command\LoadFromOpacCommandController::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.loadFromOpac.name',
        'description' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.loadFromOpac.description',
    ];*/

// Scheduler task for downloading LKL data from OPAC.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\ConvertCsvCommandController::class;
    /*
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Tasks\ConvertCsvCommandController::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.convertCSV.name',
        'description' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.convertCSV.description',
        'additionalFields' => 'tx_nkwgok_scheduler_convertcsvadditionalparameters',
    ];*/

// Scheduler task for importing Pica authority record XML files into TYPO3 database.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\LoadXmlCommandController::class;
    /*
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Tasks\LoadXml::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.loadxml.name',
        'description' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.loadxml.description',
    ];*/

// Scheduler task to determine whether we need to run the scheduler task for
// converting and importing CSV, and doing that if we do.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\CheckNewCsvCommand::class;
    /*$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Command\CheckNewCsvCommandController::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.checkNewCSV.name',
        'description' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.checkNewCSV.description',
        'additionalFields' => 'tx_nkwgok_scheduler_convertcsvadditionalparameters',
    ];*/

// Scheduler task 2+3 for converting the CSV files and reimporting the database.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\UpdateCsvCommandController::class;

    /*$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Tasks\UpdateCSV::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.updateCSV.name',
        'description' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.updateCSV.description',
        'additionalFields' => 'tx_nkwgok_scheduler_convertcsvadditionalparameters',
    ];*/

// Scheduler task 1+2+3 for running our 3 other Scheduler tasks in the correct order.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \Subugoe\Nkwgok\Command\ImportAllCommandController::class;

    /*$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Tasks\ImportAll::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.importAll.name',
        'description' => 'LLL:EXT:'.$_EXTKEY.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.importAll.description',
        'additionalFields' => 'tx_nkwgok_scheduler_convertcsvadditionalparameters',
    ];*/

    $TYPO3_CONF_VARS['FE']['eID_include']['nkwgok'] = 'EXT:nkwgok/lib/get.php';
};

$boot();
unset($boot);
