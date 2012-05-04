<?php
/**
 * TYPO3 Scheduler task to automatically run our three scheduler tasks
 * in the correct order:
 * 1. Load LKL data from Opac
 * 2. Convert History CSV Data to XML
 * 3. Import all the XML to the TYPO3 Database
 *
 * 2011-2012 Sven-S. Porst <porst@sub.uni-goettingen.de>
 */


/**
 * Class tx_nkwgok_importAll provides task procedures
 *
 * @author		Sven-S. Porst <porst@sub.uni-goettingen.de>
 * @package		TYPO3
 * @subpackage	tx_nkwgok
 */
class tx_nkwgok_importAll extends tx_scheduler_Task {

	/**
	 * Function executed by the Scheduler.
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {
		$loadFromOpacTask = t3lib_div::makeInstance('tx_nkwgok_loadFromOpac');
		$success = $loadFromOpacTask->execute();
		if (!$success) {
			t3lib_div::devLog('importALL Scheduler Task: could not load Opac data. Stopping.' , 'nkwgok', 3);
		}
		else {
			$convertCSVTask = t3lib_div::makeInstance('tx_nkwgok_convertCSV');
			$success = $convertCSVTask->execute($this->nkwgokStartPageId);
			if (!$success) {
				t3lib_div::devLog('importAll Scheduler Task: Problem during conversion of CSV files. Stopping.' , 'nkwgok', 3);
			}
			else {
				$loadxmlTask = t3lib_div::makeInstance('tx_nkwgok_loadxml');
				$success = $loadxmlTask->execute();
				if (!$success) {
					t3lib_div::devLog('importAll Scheduler Task: could not import XML to TYPO3 database.' , 'nkwgok', 3);
				}
			}
		}

		return $success;
	}

}



if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_importall.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_importall.php']);
}
?>
