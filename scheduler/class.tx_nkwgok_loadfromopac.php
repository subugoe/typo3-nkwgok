<?php
/**
 * TYPO3 Scheduler task to download the Opac data we need and store them in
 * fileadmin/gok/...
 *
 * Unifies the features provided by class.tx_nkwgok_loadxml.php and
 * getHitCounts.py and makes them accessible from the TYPO3 Scheduler.
 *
 * 2011-2012 Sven-S. Porst <porst@sub.uni-goettingen.de>
 */


define('NKWGOKImportChunkSize', 500);


/**
 * Class tx_nkwgok_loadFromOpac provides task procedures
 *
 * @author		Sven-S. Porst <porst@sub.uni-goettingen.de>
 * @package		TYPO3
 * @subpackage	tx_nkwgok
 */
class tx_nkwgok_loadFromOpac extends tx_scheduler_Task {

	/**
	 * Function executed from the Scheduler.
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {
		set_time_limit(1200);

		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][tx_nkwgok_utility::extKey]);
		$opacBaseURL = $conf['opacBaseURL'] . 'XML=1/';
		$baseDir = PATH_site . 'fileadmin/gok/';

		t3lib_div::mkdir_deep(PATH_site, 'fileadmin/gok/xml');
		// Create lkl folder if necessary and remove all files whose names begin with a digit.
		// (This is a simple heuristic to delete all the files we downloaded and keep
		// the CSV files whose names begin with a letter.)
		$XMLDir = $baseDir . 'xml/';
		$XMLFileList = glob($XMLDir . '*.xml');
		foreach ($XMLFileList as $file) {
			unlink($file);
		}

		$opacLKLURL = $opacBaseURL . 'CMD?ACT=SRCHA/IKT=8600/TRM=tev+not+LKL+p%3F/REC=2/PRS=XML/NORND=1';
		$GOKFileBaseName = 'gok';
		$success = $this->downloadAuthorityDataFromOpacToFolder($opacLKLURL, $XMLDir, $GOKFileBaseName);

		$opacBRKURL = $opacBaseURL . 'CMD?ACT=SRCHA/IKT=8600/TRM=tov/REC=2/PRS=XML/NORND=1';
		$BRKFileBaseName = 'brk';
		$success &= $this->downloadAuthorityDataFromOpacToFolder($opacBRKURL, $XMLDir, $BRKFileBaseName);


		// Create the hitcounts folder if necessary and delete all files inside it if it exists.
		t3lib_div::mkdir(PATH_site, 'fileadmin/gok/hitcounts');
		$hitCountDir = $baseDir . 'hitcounts/';
		$hitCountFileList = glob($hitCountDir . '*');
		foreach ($hitCountFileList as $file) {
			unlink($file);
		}

		$opacHitCountURL = $opacBaseURL . 'CMD?ACT=BRWS&SCNST=' . NKWGOKImportChunkSize;
		$success &= $this->downloadHitCountsFromOpacToFolder($opacHitCountURL, 'lkl', $hitCountDir);
		$success &= $this->downloadHitCountsFromOpacToFolder($opacHitCountURL, 'msc', $hitCountDir);
		$success &= $this->downloadHitCountsFromOpacToFolder($opacHitCountURL, 'brk', $hitCountDir);

		return $success;
	}



	/**
	 * Downloads batches of local authority records from Opac as
	 * Pica XML records and writes them into our fileadmin folder.
	 *
	 * @param string $opacBaseURL
	 * @param string $folderPath
	 * @param string $fileBaseName
	 * @return boolean sucess status of the download
	 */
	private function downloadAuthorityDataFromOpacToFolder($opacBaseURL, $folderPath, $fileBaseName) {
		$success = True;
		$firstRecord = 1; // Pica result indexing is 1 based
		$hitsAttribute = simplexml_load_file($opacBaseURL)->xpath('/RESULT/SET/@hits');
		$resultCount = (int)$hitsAttribute[0];

		while (($firstRecord < $resultCount) && $success) {
			$URL = $opacBaseURL . '/SHRTST=' . NKWGOKImportChunkSize . '/FRST=' . $firstRecord;
			$opacDownload = file_get_contents($URL);
			if ($opacDownload) {
				$targetFilePath = $folderPath . $fileBaseName . '-' . $firstRecord . '.xml';
				$targetFile = fopen($targetFilePath, 'w');
				if ($targetFile) {
					fwrite($targetFile, $opacDownload);
					fclose($targetFile);
					$firstRecord += NKWGOKImportChunkSize;
				}
				else {
					t3lib_div::sysLog('loadFromOpac Scheduler Task: could not write file at path ' . $targetFilePath , tx_nkwgok_utility::extKey, t3lib_div::SYSLOG_SEVERITY_FATAL);
					$success = False;
				}
			}
			else {
				t3lib_div::sysLog('loadFromOpac Scheduler Task: failed to load ' . $URL, tx_nkwgok_utility::extKey, t3lib_div::SYSLOG_SEVERITY_FATAL);
				$success = False;
			}
		}

		if ($success) {
			t3lib_div::sysLog('loadFromOpac Scheduler Task: subject authority download succeeded', tx_nkwgok_utility::extKey, t3lib_div::SYSLOG_SEVERITY_INFO);
		}

		return $success;
	}



	/**
	 * Downloads hit counts for all entries of the $indexName index by browsing.
	 * Stores the resulting XML files into the hitcounts folder.
	 *
	 * @param string $opacScanURL
	 * @param string $indexName
	 * @param string $folderPath
	 * @return boolean success status of the download
	 */
	private function downloadHitCountsFromOpacToFolder($opacScanURL, $indexName, $folderPath) {
		$success = True;
		/* Begin scanning the index at 0, except for LKL (which only start at a and have a lot of
			junk entries starting with digits. */
		$scanNext = '0';
		if ($indexName === 'lkl') {
			$scanNext = 'a';
		}
		else if ($indexName === 'brk') {
			$scanNext = '01';
		}
		$index = 0;

		while ($scanNext !== Null && $success) {
			$index++;
			$URL = $opacScanURL . '/TRM=' . $indexName . '+%22' . $scanNext . '%22';
			$opacDownload = file_get_contents($URL);
			if ($opacDownload) {
				$targetFilePath = $folderPath . $indexName . '-' . $index . '.xml';
				$targetFile = fopen($targetFilePath, 'w');
				if ($targetFile) {
					fwrite($targetFile, $opacDownload);
					fclose($targetFile);
				}
				else {
					t3lib_div::sysLog('loadFromOpac Scheduler Task: could not write file at path ' . $targetFilePath , tx_nkwgok_utility::extKey, t3lib_div::SYSLOG_SEVERITY_FATAL);
					$success = False;
				}

				$termAttribute = simplexml_load_string($opacDownload)->xpath('/RESULT/SCANNEXT/@term');
				$scanNext = $termAttribute[0];
			}
			else {
				t3lib_div::sysLog('loadFromOpac Scheduler Task: failed to load ' . $URL, tx_nkwgok_utility::extKey, t3lib_div::SYSLOG_SEVERITY_FATAL);
				$success = False;
			}
		}

		if ($success) {
			t3lib_div::sysLog('loadFromOpac Scheduler Task: hitcount download for index ' . $indexName . ' succeeded', tx_nkwgok_utility::extKey, t3lib_div::SYSLOG_SEVERITY_INFO);
		}

		return $success;
	}


}



if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadfromopac.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadfromopac.php']);
}
?>
