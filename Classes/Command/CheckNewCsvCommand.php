<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Command;

use Subugoe\Nkwgok\Importer\CheckNewCsv;
use Subugoe\Nkwgok\Importer\ConvertCsv;
use Subugoe\Nkwgok\Importer\LoadXml;
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
        $service = GeneralUtility::makeInstance(CheckNewCsv::class);

        $success = true;
        if ($service->run()) {
            $convertCSVTask = GeneralUtility::makeInstance(ConvertCsv::class);
            $success = $convertCSVTask->run();
            if (!$success) {
                $output->writeln('<error>checkNewCSV Scheduler Task: Problem during conversion of CSV files. Stopping.</error>');
            } else {
                $loadxmlTask = GeneralUtility::makeInstance(LoadXml::class);
                $success = $loadxmlTask->run();
                if (!$success) {
                    $output->writeln('<error>checkNewCSV Scheduler Task: could not import XML to TYPO3 database.</error>');
                }
            }
        }

        return $success;
    }
}
