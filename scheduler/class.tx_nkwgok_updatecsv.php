<?php

/**
 * Class tx_nkwgok_updatecsv provides task procedures
 *
 * TYPO3 Scheduler task to automatically our scheduler tasks for converting and importing CSV data.
 * 1. Convert CSV Data to XML
 * 2. Import all the XML to the TYPO3 Database
 */
class tx_nkwgok_updateCSV extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * Function executed by the Scheduler.
     * @return boolean TRUE if success, otherwise FALSE
     */
    public function execute()
    {
        /** @var \tx_nkwgok_convertCSV $convertCSVTask */
        $convertCSVTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_nkwgok_convertCSV::class);
        $success = $convertCSVTask->execute();
        if (!$success) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('updateCSV Scheduler Task: Problem during conversion of CSV files. Stopping.',
                tx_nkwgok_utility::extKey, 3);
        } else {
            /** @var \tx_nkwgok_loadxml $loadxmlTask */
            $loadxmlTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_nkwgok_loadxml::class);
            $success = $loadxmlTask->execute();
            if (!$success) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('updateCSV Scheduler Task: could not import XML to TYPO3 database.',
                    tx_nkwgok_utility::extKey, 3);
            }
        }

        return $success;
    }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_updatecsv.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_updatecsv.php']);
}
