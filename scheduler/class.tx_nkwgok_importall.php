<?php

/**
 * TYPO3 Scheduler task to automatically run our three scheduler tasks
 * in the correct order:
 * 1. Load LKL data from OPAC
 * 2. Convert History CSV Data to XML
 * 3. Import all the XML to the TYPO3 Database
 *
 */
class tx_nkwgok_importAll extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{

    /**
     * Function executed by the Scheduler.
     * @return    bool    TRUE if success, otherwise FALSE
     */
    public function execute()
    {
        /** @var \tx_nkwgok_loadFromOpac $loadFromOpacTask */
        $loadFromOpacTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_nkwgok_loadFromOpac::class);
        $success = $loadFromOpacTask->execute();
        if (!$success) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('importALL Scheduler Task: could not load OPAC data. Stopping.', \tx_nkwgok_utility::extKey, 3);
        } else {
            /** @var \tx_nkwgok_convertCSV $convertCSVTask */
            $convertCSVTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_nkwgok_convertCSV::class);
            $success = $convertCSVTask->execute();
            if (!$success) {
                \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('importAll Scheduler Task: Problem during conversion of CSV files. Stopping.', \tx_nkwgok_utility::extKey, 3);
            } else {
                /** @var \tx_nkwgok_loadxml $loadxmlTask */
                $loadxmlTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\tx_nkwgok_loadxml::class);
                $success = $loadxmlTask->execute();
                if (!$success) {
                    \TYPO3\CMS\Core\Utility\GeneralUtility::devLog('importAll Scheduler Task: could not import XML to TYPO3 database.', \tx_nkwgok_utility::extKey, 3);
                }
            }
        }

        return $success;
    }
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_importall.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_importall.php']);
}
