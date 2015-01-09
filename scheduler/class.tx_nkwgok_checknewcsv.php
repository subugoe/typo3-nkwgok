<?php
/**
 * TYPO3 Scheduler task to check whether any of our CSV files has been updated
 * and triggering the CSV to XML conversion as well as database update if it has.
 *
 * 2011-2012 Sven-S. Porst <porst@sub.uni-goettingen.de>
 */


/**
 * Class tx_nkwgok_checkNewCSV provides task procedures
 *
 * @author		Sven-S. Porst <porst@sub.uni-goettingen.de>
 * @package		TYPO3
 * @subpackage	tx_nkwgok
 */
class tx_nkwgok_checkNewCSV extends \TYPO3\CMS\Scheduler\Task\AbstractTask {

	/**
	 * Function executed by the Scheduler.
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {
		$success = True;
		if ($this->needsUpdate()) {
			$convertCSVTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_nkwgok_convertCSV');
			$success = $convertCSVTask->execute();
			if (!$success) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('checkNewCSV Scheduler Task: Problem during conversion of CSV files. Stopping.' , tx_nkwgok_utility::extKey, 3);
			}
			else {
				$loadxmlTask = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_nkwgok_loadxml');
				$success = $loadxmlTask->execute();
				if (!$success) {
					\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('checkNewCSV Scheduler Task: could not import XML to TYPO3 database.' , tx_nkwgok_utility::extKey, 3);
				}
			}
		}

		return $success;
	}



	/**
	 * Returns whether all CSV files in filadmin/gok/csv/ have corresponding
	 * XML files in fileadmin/gok/xml with a newer modification date.
	 * @return boolean
	 */
	private function needsUpdate () {
		$needsUpdate = False;
		$CSVFiles = glob(PATH_site . 'fileadmin/gok/csv/*.csv');
		foreach ($CSVFiles as $CSVPath) {
			$CSVPathInfo = pathinfo($CSVPath);
			$XMLPath = PATH_site . 'fileadmin/gok/xml/' . $CSVPathInfo['filename'] . '-0.xml';
			if (!file_exists($XMLPath)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Need to convert CSV files because ' . $XMLPath . ' is missing.' , tx_nkwgok_utility::extKey, 1);
				$needsUpdate = True;
				break;
			}
			else if (filemtime($XMLPath) < filemtime($CSVPath)) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::devLog('Need to convert CSV files because ' . $CSVPath . ' is newer than the corresponding XML file.' , tx_nkwgok_utility::extKey, 1);
				$needsUpdate = True;
				break;
			}
		}

		return $needsUpdate;
	}

}

if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_checknewcsv.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_checknewcsv.php']);
}
