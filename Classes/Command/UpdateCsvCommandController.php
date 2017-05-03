<?php

namespace Subugoe\Nkwgok\Command;

use Subugoe\Nkwgok\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * Class tx_nkwgok_updatecsv provides task procedures.
 *
 * TYPO3 Scheduler task to automatically our scheduler tasks for converting and importing CSV data.
 * 1. Convert CSV Data to XML
 * 2. Import all the XML to the TYPO3 Database
 */
class UpdateCsvCommandController extends CommandController
{
    /**
     * Function executed by the Scheduler.
     *
     * @return bool TRUE if success, otherwise FALSE
     */
    public function executeCommand()
    {
        $convertCSVTask = GeneralUtility::makeInstance(ConvertCsvCommandController::class);
        $success = $convertCSVTask->execute();
        if (!$success) {
            GeneralUtility::devLog('updateCSV Scheduler Task: Problem during conversion of CSV files. Stopping.', Utility::extKey, 3);
        } else {
            $loadxmlTask = GeneralUtility::makeInstance(LoadXmlCommandController::class);
            $success = $loadxmlTask->execute();
            if (!$success) {
                GeneralUtility::devLog('updateCSV Scheduler Task: could not import XML to TYPO3 database.', Utility::extKey, 3);
            }
        }

        return $success;
    }
}
