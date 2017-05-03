<?php

namespace Subugoe\Nkwgok\Command;

use Subugoe\Nkwgok\Utility\Utility;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 Scheduler task to check whether any of our CSV files has been updated
 * and triggering the CSV to XML conversion as well as database update if it has.
 */
class CheckNewCsvCommand extends Command
{
    /**
     * Function executed by the Scheduler.
     *
     * @return bool TRUE if success, otherwise FALSE
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $success = true;
        if ($this->needsUpdate()) {
            $convertCSVTask = GeneralUtility::makeInstance(ConvertCsvCommandController::class);
            $success = $convertCSVTask->executeCommand();
            if (!$success) {
                GeneralUtility::devLog('checkNewCSV Scheduler Task: Problem during conversion of CSV files. Stopping.',
                    Utility::extKey, 3);
            } else {
                $loadxmlTask = GeneralUtility::makeInstance(LoadXmlCommandController::class);
                $success = $loadxmlTask->executeCommand();
                if (!$success) {
                    GeneralUtility::devLog('checkNewCSV Scheduler Task: could not import XML to TYPO3 database.',
                        Utility::extKey, 3);
                }
            }
        }

        return $success;
    }

    /**
     * Returns whether all CSV files in filadmin/gok/csv/ have corresponding
     * XML files in fileadmin/gok/xml with a newer modification date.
     *
     * @return bool
     */
    private function needsUpdate()
    {
        $needsUpdate = false;
        $CSVFiles = glob(PATH_site.'fileadmin/gok/csv/*.csv');
        foreach ($CSVFiles as $CSVPath) {
            $CSVPathInfo = pathinfo($CSVPath);
            $XMLPath = PATH_site.'fileadmin/gok/xml/'.$CSVPathInfo['filename'].'-0.xml';
            if (!file_exists($XMLPath)) {
                GeneralUtility::devLog('Need to convert CSV files because '.$XMLPath.' is missing.',
                    Utility::extKey, 1);
                $needsUpdate = true;
                break;
            } else {
                if (filemtime($XMLPath) < filemtime($CSVPath)) {
                    GeneralUtility::devLog('Need to convert CSV files because '.$CSVPath.' is newer than the corresponding XML file.',
                        Utility::extKey, 1);
                    $needsUpdate = true;
                    break;
                }
            }
        }

        return $needsUpdate;
    }
}
