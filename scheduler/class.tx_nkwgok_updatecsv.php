<?php
/**
 * TYPO3 Scheduler task to automatically our scheduler tasks for converting and importing CSV data.
 * 1. Convert CSV Data to XML
 * 2. Import all the XML to the TYPO3 Database
 *
 * 2011-2012 Sven-S. Porst <porst@sub.uni-goettingen.de>
 */


/**
 * Class tx_nkwgok_updatecsv provides task procedures
 *
 * @author		Sven-S. Porst <porst@sub.uni-goettingen.de>
 * @package		TYPO3
 * @subpackage	tx_nkwgok
 */
class tx_nkwgok_updateCSV extends tx_scheduler_Task {

	/**
	 * Function executed by the Scheduler.
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {
		$convertCSVTask = t3lib_div::makeInstance('tx_nkwgok_convertCSV');
		$success = $convertCSVTask->execute($this->nkwgokStartPageId);
		if (!$success) {
			t3lib_div::devLog('updateCSV Scheduler Task: Problem during conversion of CSV files. Stopping.' , tx_nkwgok_utility::extKey, 3);
		}
		else {
			$loadxmlTask = t3lib_div::makeInstance('tx_nkwgok_loadxml');
			$success = $loadxmlTask->execute();
			if (!$success) {
				t3lib_div::devLog('updateCSV Scheduler Task: could not import XML to TYPO3 database.' , tx_nkwgok_utility::extKey, 3);
			}
		}

		return $success;
	}

}



if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_updatecsv.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_updatecsv.php']);
}
?>
