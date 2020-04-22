<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Command;

use Subugoe\Nkwgok\Importer\ConvertCsv;
use Subugoe\Nkwgok\Importer\LoadFromOpac;
use Subugoe\Nkwgok\Importer\LoadXml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 Scheduler task to automatically run our three scheduler tasks
 * in the correct order:
 * 1. Load LKL data from OPAC
 * 2. Convert History CSV Data to XML
 * 3. Import all the XML to the TYPO3 Database.
 */
class ImportAllCommand extends Command
{
    public function configure()
    {
        parent::configure();
        $this->setDescription('Task to automatically run three tasks: Load LKL data from OPAC, convert History CSV Data to XML, import all the XML to the TYPO3 Database.');
    }

    /**
     * Function executed by the Scheduler.
     *
     * @return int
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $loadFromOpacTask = GeneralUtility::makeInstance(LoadFromOpac::class);
        $output->writeln('<info>Starting import from Opac</info>');
        $success = $loadFromOpacTask->run();
        if (!$success) {
            $output->writeln('<error>importALL Scheduler Task: could not load OPAC data. Stopping.</error>');
        } else {
            $output->writeln('<info>Starting CSV conversion</info>');

            $convertCSVTask = GeneralUtility::makeInstance(ConvertCsv::class);
            $success = $convertCSVTask->run();
            if (!$success) {
                $output->writeln('<error>importAll Scheduler Task: Problem during conversion of CSV files. Stopping.');
            } else {
                $loadxmlTask = GeneralUtility::makeInstance(LoadXml::class);
                $output->writeln('<info>Start loading XML files.</info>');

                $success = $loadxmlTask->run();
                if (!$success) {
                    $output->writeln('<error>importAll Scheduler Task: could not import XML to TYPO3 database.');
                }
            }
        }

        return $success ? 0 : 1;
    }
}
