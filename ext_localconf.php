<?php

defined('TYPO3_MODE') or die();

$boot = function ($extKey) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'Subugoe.Nkwgok',
        'Pi1',
        ['Default' => 'main']
    );

    // Scheduler task for downloading LKL data from OPAC.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Task\LoadFromOpacTask::class] = [
        'extension' => $extKey,
        'title' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.loadFromOpac.name',
        'description' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.loadFromOpac.description',
    ];

    // Scheduler task for downloading LKL data from OPAC.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Task\ConvertCsvTask::class] = [
        'extension' => $extKey,
        'title' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.convertCSV.name',
        'description' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.convertCSV.description',
    ];

    // Scheduler task for importing Pica authority record XML files into TYPO3 database.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Task\LoadXmlTask::class] = [
        'extension' => $extKey,
        'title' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.loadxml.name',
        'description' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.loadxml.description',
    ];

    // Scheduler task to determine whether we need to run the scheduler task for
    // converting and importing CSV, and doing that if we do.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Task\CheckNewCsvTask::class] = [
        'extension' => $extKey,
        'title' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.checkNewCSV.name',
        'description' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.checkNewCSV.description',
    ];

    // Scheduler task 2+3 for converting the CSV files and reimporting the database.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Task\UpdateCsvTask::class] = [
        'extension' => $extKey,
        'title' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.updateCSV.name',
        'description' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.updateCSV.description',
    ];

    // Scheduler task 1+2+3 for running our 3 other Scheduler tasks in the correct order.
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Subugoe\Nkwgok\Task\ImportAllTask::class] = [
        'extension' => $extKey,
        'title' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.importAll.name',
        'description' => 'LLL:EXT:'.$extKey.'/Resources/Private/Language/locallang_scheduler.xml:scheduler.importAll.description',
    ];

    $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include'][$extKey] = 'EXT:'.$extKey.'/Resources/Private/Scripts/get.php';
};

$boot('nkwgok');
unset($boot);
