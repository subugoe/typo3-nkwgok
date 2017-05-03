<?php

namespace Subugoe\Nkwgok\Importer;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Output\ConsoleOutput;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CheckNewCsv implements ImporterInterface
{
    public function run(): bool
    {
        return $this->needsUpdate();
    }

    /*
    * Returns whether all CSV files in filadmin/gok/csv/ have corresponding
    * XML files in fileadmin/gok/xml with a newer modification date.
    *
    * @return bool
    */
    protected function needsUpdate()
    {
        $output = GeneralUtility::makeInstance(ConsoleOutput::class);
        $output->setFormatter(new OutputFormatter(true));

        $needsUpdate = false;
        $CSVFiles = glob(PATH_site.'fileadmin/gok/csv/*.csv');
        foreach ($CSVFiles as $CSVPath) {
            $CSVPathInfo = pathinfo($CSVPath);
            $XMLPath = PATH_site.'fileadmin/gok/xml/'.$CSVPathInfo['filename'].'-0.xml';
            if (!file_exists($XMLPath)) {
                $output->writeln('Need to convert CSV files because '.$XMLPath.' is missing.');
                $needsUpdate = true;
                break;
            } else {
                if (filemtime($XMLPath) < filemtime($CSVPath)) {
                    $output->writeln('Need to convert CSV files because '.$CSVPath.' is newer than the corresponding XML file.');
                    $needsUpdate = true;
                    break;
                }
            }
        }

        return $needsUpdate;
    }
}
