<?php

/**
 * TYPO3 Scheduler task to check whether any of our CSV files has been updated
 * and triggering the CSV to XML conversion as well as database update if it has.
 */
class tx_nkwgok_checkNewCSV extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * Function executed by the Scheduler.
     * @return bool TRUE if success, otherwise FALSE
     */
    public function execute()
    {
        $success = true;
        if ($this->needsUpdate()) {
            /** @var \tx_nkwgok_convertCSV $convertCSVTask */
            $convertCSVTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_nkwgok_convertCSV::class);
            $success = $convertCSVTask->execute();
            if (!$success) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('checkNewCSV Scheduler Task: Problem during conversion of CSV files. Stopping.',
                    tx_nkwgok_utility::extKey, 3);
            } else {
                /** @var \tx_nkwgok_loadxml $loadxmlTask */
                $loadxmlTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_nkwgok_loadxml::class);
                $success = $loadxmlTask->execute();
                if (!$success) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('checkNewCSV Scheduler Task: could not import XML to TYPO3 database.',
                        tx_nkwgok_utility::extKey, 3);
                }
            }
        }

        return $success;
    }

    /**
     * Returns whether all CSV files in filadmin/gok/csv/ have corresponding
     * XML files in fileadmin/gok/xml with a newer modification date.
     * @return bool
     */
    private function needsUpdate()
    {
        $needsUpdate = false;
        $CSVFiles = glob(PATH_site . 'fileadmin/gok/csv/*.csv');
        foreach ($CSVFiles as $CSVPath) {
            $CSVPathInfo = pathinfo($CSVPath);
            $XMLPath = PATH_site . 'fileadmin/gok/xml/' . $CSVPathInfo['filename'] . '-0.xml';
            if (!file_exists($XMLPath)) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Need to convert CSV files because ' . $XMLPath . ' is missing.',
                    tx_nkwgok_utility::extKey, 1);
                $needsUpdate = true;
                break;
            } else {
                if (filemtime($XMLPath) < filemtime($CSVPath)) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Need to convert CSV files because ' . $CSVPath . ' is newer than the corresponding XML file.',
                        tx_nkwgok_utility::extKey, 1);
                    $needsUpdate = true;
                    break;
                }
            }
        }

        return $needsUpdate;
    }
}

if (defined('TYPO3_MODE')
    && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_checknewcsv.php']
) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_checknewcsv.php']);
}
