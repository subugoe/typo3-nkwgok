<?php
/**
 * Typo3 Scheduler task to download the Opac data we need and store them in
 * fileadmin/gok/...
 *
 * Unifies the features provided by class.tx_nkwgok_loadxml.php and
 * getHitCounts.py and makes them accessible from the Typo3 Scheduler.
 *
 * 2011 Sven-S. Porst <porst@sub.uni-goettingen.de>
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
		set_time_limit(300);

		$opacBaseURL = 'http://opac.sub.uni-goettingen.de/DB=1/XML=1/';
		$baseDir = PATH_site. 'fileadmin/gok/';

		$opacLKLURL = $opacBaseURL . 'CMD?ACT=SRCHA/IKT=8600/TRM=tev+not+LKL+p%3F/REC=2/PRS=XML/NORND=1';
		$LKLDir = $baseDir . 'lkl/';
		mkdir($LKLDir);
		$success = $this->downloadLKLDataFromOpacToFolder($opacLKLURL, $LKLDir);
		
		$opacHitCountURL = $opacBaseURL . 'CMD?ACT=BRWS&SCNST=' . NKWGOKImportChunkSize . '/TRM=lkl+';
		$hitCountDir = $baseDir . 'hitcounts/';
		mkdir($hitCountDir);
		$success &= $this->downloadHitCountsFromOpacToFolder($opacHitCountURL, $hitCountDir);

		return $success;
	}



	/**
	 * Downloads batches of local classification Normdaten from Opac as
	 * Pica XML records and writes them into our fileadmin folder.
	 *
	 * @param string $opacBaseURL
	 * @param string $folderPath
	 * @return boolean sucess status of the download
	 */
	private function downloadLKLDataFromOpacToFolder($opacBaseURL, $folderPath) {
		$success = True;
		$firstRecord = 1; // Pica result indexing is 1 based
		$hitsAttribute = simplexml_load_file($opacBaseURL)->xpath('/RESULT/SET/@hits');
		$resultCount = (int)$hitsAttribute[0];

		while (($firstRecord < $resultCount) && $success) {
			$URL = $opacBaseURL . '/SHRTST=' . NKWGOKImportChunkSize . '/FRST=' . $firstRecord;
			$opacDownload = file_get_contents($URL);
			if ($opacDownload) {
				$targetFilePath = $folderPath . $firstRecord . '.xml';
				$targetFile = fopen($targetFilePath, 'w');
				if ($targetFile) {
					fwrite($targetFile, $opacDownload);
					fclose($targetFile);
					$firstRecord += NKWGOKImportChunkSize;
				}
				else {
					t3lib_div::devLog('loadFromOpac Scheduler Task: could not write file at path ' . $targetFilePath , 'nkwgok', 3);
					$success = False;
				}
			}
			else {
				t3lib_div::devLog('loadFromOpac Scheduler Task: failed to load ' . $URL, 'nkwgok', 3);
				$success = False;
			}
		}

		if ($success) {
			t3lib_div::devLog('loadFromOpac Scheduler Task: LKL download succeeded', 'nkwgok', 1);
		}

		return $success;
	}



	/**
	 * Downloads hit counts for all LKL index entries by browsing the index.
	 * Stores the resulting XML files into the hitcounts folder.
	 *
	 * @param string $opacBaseURL
	 * @param string $folderPath
	 * @return boolean success status of the download
	 */
	private function downloadHitCountsFromOpacToFolder($opacBaseURL, $folderPath) {
		$success = True;
		$scanNext = 'a'; // begin scanning the index at LKL a

		while ($scanNext && $success) {
			$URL = $opacBaseURL . $scanNext;
			$opacDownload = file_get_contents($URL);
			if ($opacDownload) {
				$targetFilePath = $folderPath . $scanNext . '.xml';
				$targetFile = fopen($targetFilePath, 'w');
				if ($targetFile) {
					fwrite($targetFile, $opacDownload);
					fclose($targetFile);
				}
				else {
					t3lib_div::devLog('loadFromOpac Scheduler Task: could not write file at path ' . $targetFilePath , 'nkwgok', 3);
					$success = False;
				}

				$termAttribute = simplexml_load_string($opacDownload)->xpath('/RESULT/SCANNEXT/@term');
				$scanNext = $termAttribute[0];
			}
			else {
				t3lib_div::devLog('loadFromOpac Scheduler Task: failed to load ' . $URL, 'nkwgok', 3);
				$success = False;
			}
		}

		if ($success) {
			t3lib_div::devLog('loadFromOpac Scheduler Task: hitcount download succeeded', 'nkwgok', 1);
		}

		return $success;
	}


}



if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadfromopac.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadfromopac.php']);
}
?>
