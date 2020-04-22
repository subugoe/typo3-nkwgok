<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Command;

use Subugoe\Nkwgok\Importer\ConvertCsv;
use Subugoe\Nkwgok\Importer\LoadXml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class tx_nkwgok_updatecsv provides task procedures.
 *
 * TYPO3 Scheduler task to automatically our scheduler tasks for converting and importing CSV data.
 * 1. Convert CSV Data to XML
 * 2. Import all the XML to the TYPO3 Database
 */
class UpdateCsvCommand extends Command
{
    public function configure()
    {
        parent::configure();
        $this->setDescription('TYPO3 Scheduler task to automatically our scheduler tasks for converting and importing CSV data.');
    }

    /**
     * Function executed by the Scheduler.
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $convertCSVTask = GeneralUtility::makeInstance(ConvertCsv::class);
        $output->writeln('<info>Starting CSV conversion</info>');

        $success = $convertCSVTask->run();
        if (!$success) {
            $output->writeln('<error>updateCSV Scheduler Task: Problem during conversion of CSV files. Stopping.</error>');
        } else {
            $loadxmlTask = GeneralUtility::makeInstance(LoadXml::class);
            $success = $loadxmlTask->run();
            if (!$success) {
                $output->writeln('<error>updateCSV Scheduler Task: could not import XML to TYPO3 database.</error>');
            }
        }

        return $success ? 0 : 1;
    }
}
