<?php

namespace Subugoe\Nkwgok\Command;

use Subugoe\Nkwgok\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/**
 * TYPO3 Scheduler task to automatically run our three scheduler tasks
 * in the correct order:
 * 1. Load LKL data from OPAC
 * 2. Convert History CSV Data to XML
 * 3. Import all the XML to the TYPO3 Database.
 */
class ImportAllCommandController extends CommandController
{
    /**
     * Function executed by the Scheduler.
     *
     * @return bool TRUE if success, otherwise FALSE
     */
    public function executeCommand()
    {
        $loadFromOpacTask = GeneralUtility::makeInstance(LoadFromOpacCommandController::class);
        $success = $loadFromOpacTask->execute();
        if (!$success) {
            GeneralUtility::devLog('importALL Scheduler Task: could not load OPAC data. Stopping.', Utility::extKey, 3);
        } else {
            $convertCSVTask = GeneralUtility::makeInstance(ConvertCsvCommandController::class);
            $success = $convertCSVTask->execute();
            if (!$success) {
                GeneralUtility::devLog('importAll Scheduler Task: Problem during conversion of CSV files. Stopping.', Utility::extKey, 3);
            } else {
                $loadxmlTask = GeneralUtility::makeInstance(LoadXmlCommandController::class);
                $success = $loadxmlTask->execute();
                if (!$success) {
                    GeneralUtility::devLog('importAll Scheduler Task: could not import XML to TYPO3 database.', Utility::extKey, 3);
                }
            }
        }

        return $success;
    }
}
