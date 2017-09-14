<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Importer;

use TYPO3\CMS\Core\Log\LogManager;
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
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $needsUpdate = false;
        $CSVFiles = glob(PATH_site.'fileadmin/gok/csv/*.csv');
        foreach ($CSVFiles as $CSVPath) {
            $CSVPathInfo = pathinfo($CSVPath);
            $XMLPath = PATH_site.'fileadmin/gok/xml/'.$CSVPathInfo['filename'].'-0.xml';
            if (!file_exists($XMLPath)) {
                $logger->info(sprintf('Need to convert CSV files because %s is missing.', $XMLPath));
                $needsUpdate = true;
                break;
            } else {
                if (filemtime($XMLPath) < filemtime($CSVPath)) {
                    $logger->info(sprintf('Need to convert CSV files because %s is newer than the corresponding XML file.', $CSVPath));
                    $needsUpdate = true;
                    break;
                }
            }
        }

        return $needsUpdate;
    }
}
