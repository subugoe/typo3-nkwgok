<?php
/**
 * Typo3 Scheduler task to process CSV files with subject tree information.
 *
 * The file format is described in the processCSVFile function.
 *
 * 2011 Sven-S. Porst <porst@sub.uni-goettingen.de>
 */


/**
 * Class tx_nkwgok_convertCSV
 *
 * @author		Sven-S. Porst <porst@sub.uni-goettingen.de>
 * @package		TYPO3
 * @subpackage	tx_nkwgok
 */
class tx_nkwgok_convertCSV extends tx_scheduler_Task {

	/**
	 * Function executed from the Scheduler.
	 * 
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {
		$this->downloadFiles();

		$success = true;
		$fileList = glob(PATH_site . 'fileadmin/gok/csv/*.csv');
		foreach ($fileList as $CSVPath) {
			$success = $this->processCSVFile($CSVPath);
			if (!$success) break;
		}
		return $success;
	}



	/**
	 * Uses file list from the extension configuration and downloads the
	 * files to fileadmin/gok/csv.
	 */
	private function downloadFiles() {
		//get Configuration for nkwgok
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nkwgok']);
		$urlListConf = $this->extConf['urlList'];
		// Split it up by linebreaks
		$urlList = explode("\n", $urlListConf);

		$filePaths = array();
		foreach ($urlList as $URL) {
			$URLPathComponents = explode('/', parse_url($URL, PHP_URL_PATH));
			$fileName = $URLPathComponents[count($URLPathComponents)-1];
			$filePath = PATH_site. 'fileadmin/gok/csv/' . $fileName;
			if (file_put_contents($filePath, file_get_contents($URL))) {
				$filePaths[] = $filePath;
			}
			else {
				t3lib_div::devLog('convertCSV Scheduler Task: failed to download ' . $URL . ' to ' . $filePath . '.', 'nkwgok', 2);
			}
		}
	}



	/**
	 * Loads CSV file at the given path and processes it to Opac XML format
	 * with Pica Tev fields for the corresponding Normdatensatz.
	 *
	 * The file’s text encoding is expected to be UTF-8 or ISO/Windows Latin-1.
	 *
	 * Columns in the file are:
	 * 1:	PPN -> 003@ $0
	 * 2:	hierarchy level -> 009B $a
	 * 3:	GOK -> 045A $a
	 * 4:	parent PPN -> 038D $9
	 * 5:	GOK name (German) -> 044E $a
	 * 6:	search query -> str $a
	 * 7:	GOK name (English) -> 044K $a
	 * 8:	Tags (comma-separated list of strings) -> tags $a
	 *
	 * @param string $csvPath path to CSV file whose name should end in .csv and contain no other dots
	 * @return Boolean success status
	 */
	private function processCSVFile ($csvPath) {
		$success = False;
		$doc = Null;

		$csvString = file_get_contents($csvPath);
		// Handle UTF-8, ISO, and Windows files. We expect the latter as the CSV is written by Excel.
		$stringEncoding = mb_detect_encoding($csvString, Array('UTF-8', 'ISO-8859-1', 'windows-1252'));
		if ($stringEncoding != 'UTF-8') {
			$csvString = mb_convert_encoding($csvString, 'UTF-8', $stringEncoding);
		}

		// Put our text in a file handle, so we can use fgetcsv on it.
		// (SLES 11 only supports PHP 5.2 and using str_getcsv requires PHP 5.3)
		// http://php.net/manual/de/function.str-getcsv.php#100579
		$fileHandle = fopen("php://memory", "rw");
		if ($fileHandle) {
			fwrite($fileHandle, $csvString);
			fseek($fileHandle, 0);
		
			// Set up XML document.
			$doc = DOMImplementation::createDocument();
			$result = $doc->createElement('RESULT');
			$doc->appendChild($result);
			$set = $doc->createElement('SET');
			$result->appendChild($set);

			$PPNList = Array();


			/* Work around PHP bug in CSV parsing: The fgetcsv function
			 * only works correctly when the locale is _not_ en_US.UTF-8.
			 * The PHP makers are in denial about this, which means they claim
			 * the _intended_ behaviour of the function is to sometimes omit
			 * the first character of a CSV field if it is not quoted and 
			 * non-ASCII.
			 * 
			 * http://bugs.php.net/bug.php?id=31740
			 * http://bugs.php.net/bug.php?id=48507
			 * 
			 * Problem seen, for example, in PHP 5.3 on Open Suse 11.3. 
			 * 
			 * To work around this, change the encoding part of the locale to
			 * 'de_DE.UTF8' and set it back to the original value 'C' (?)
			 * afterwards.
			 * As locale changes seem to be in some sense 'global' to PHP, this 
			 * has potential to cause trouble in unforeseen ways. 
			 */
			$oldLocale = setlocale(LC_CTYPE, Null);
			setlocale(LC_CTYPE, 'de_DE.UTF8');
			
			while (($fields = fgetcsv($fileHandle, 4096, ';', '"')) !== false) {
				if (count($fields) >= 5) {
					// GOK name is in field 5, so ignore lines with less fields.
					$PPN = trim($fields[0]);

					if ($PPN != '') {
						// The record is required to have a non-empty PPN.
						$shorttitle = $doc->createElement('SHORTTITLE');
						$set->appendChild($shorttitle);
						$record = $doc->createElement('record');
						$shorttitle->appendChild($record);
						$parentPPN = trim($fields[3]);
						$this->appendFieldForDataTo('003@', '0', $PPN, $record, $doc);
						$this->appendFieldForDataTo('009B', 'a', trim($fields[1]), $record, $doc);
						$this->appendFieldForDataTo('045A', 'a', trim($fields[2]), $record, $doc);
						$this->appendFieldForDataTo('038D', '9', $parentPPN, $record, $doc);
						$this->appendFieldForDataTo('044E', 'a', trim($fields[4]), $record, $doc);
						if (count($fields) > 5) {
							// Search query
							$this->appendFieldForDataTo('str', 'a', trim($fields[5]), $record, $doc);

							if (count($fields) > 6) {
								// English GOK Name
								$englishTitleField = $this->appendFieldForDataTo('044K', 'a', trim($fields[6]), $record, $doc);

								if (count($fields > 7)) {
									$this->appendFieldForDataTo('tags', 'a', trim($fields[7]), $record, $doc);
								}
							}
						}

						// Warn about a few strange situations. These are not fatal, but they may be
						// the result of a problem with the data structures.
						if ($parentPPN != '' && !$PPNList[$parentPPN]) {
							t3lib_div::devLog('convertCSV Scheduler Task: Parent PPN ' . $parentPPN . ' not defined when reading child record ' . $PPN . ' of file ' . $csvPath, 'nkwgok', 2);
						}

						if ($PPNList[$PPN]) {
							t3lib_div::devLog('convertCSV Scheduler Task: Duplicate PPN ' . $PPN. ' in file ' . $csvPath, 'nkwgok', 2);
						}

						// Add current PPN to PPN list.
						$PPNList[$PPN] = True;
					}
					else {
						t3lib_div::devLog('convertCSV Scheduler Task: Blank PPN  in line: "' . implode(';', $fields) .'" of file ' . $csvPath, 'nkwgok', 2);

					} // if ($PPN != '')
				}	
				else if (count($fields) > 1) {
					t3lib_div::devLog('convertCSV Scheduler Task: Line "' . implode(';', $fields) . '" of file ' . $csvPath . ' contains less than 5 fields.', 'nkwgok', 2);
				} // (count($fields) >= 5)
			}
			
			// Undo locale change required by fgetcsv() bug [see above].
			setlocale(LC_CTYPE, $oldLocale);
			
			fclose($fileHandle); 

			// Write XML file
			$csvPathParts = explode('/', $csvPath);
			$originalFileName = $csvPathParts[count($csvPathParts) - 1];
			$originalFileNameParts = explode('.', $originalFileName);
			$XMLFileName = $originalFileNameParts[0] . '.xml';
			$resultPath = PATH_site. 'fileadmin/gok/xml/' . $XMLFileName ;
			if ($doc->save($resultPath) === False) {
				t3lib_div::devLog('convertCSV Scheduler Task: Failed to write XML file' . $resultPath , 'nkwgok', 3);
			}
			else {
				t3lib_div::devLog('convertCSV Scheduler Task: Successfully wrote XML file ' . $resultPath , 'nkwgok', 1);
				$success = True;
			}
		}
		else {
			t3lib_div::devLog('convertCSV Scheduler Task: Could not open file ' . $csvPath , 'nkwgok', 3);
		}

		return $success;
	}



	/**
	 * Wraps the field content in a datafield/subfield structure with the
	 * given field names and inserts it into the passed container.
	 *
	 * @param string $fieldName tag attribute of the resulting datafield tag
	 * @param string $subfieldName code attribute of the resulting subfield tag
	 * @param string $content text put into the subfield
	 * @param DOMElement $container the datafield is appended to
	 * @param DOMDocument $doc of $container
	 * @return DOMElement|Null The datafield tha was inserted
	 */
	private function appendFieldForDataTo ($fieldName, $subfieldName, $content, $container, $doc) {
		$datafield = Null;
		
		if ($fieldName !== Null && $subfieldName !== Null
				&& $content !== Null && $container !== Null && $doc !== Null) {
			$datafield = $doc->createElement('datafield');
			$datafield->setAttribute('tag', $fieldName);
			$container->appendChild($datafield);

			$subfield = $doc->createElement('subfield');
			$subfield->setAttribute('code', $subfieldName);
			$datafield->appendChild($subfield);

			$subfield->appendChild($doc->createTextNode($content));
		}
		else {
			t3lib_div::devLog('convertCSV Scheduler Task: Some parameter was Null in appendFieldForDataTo' , 'nkwgok', 3);
		}
		
		return $datafield;
	}


}



if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_convertcsv.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_convertcsv.php']);
}
?>
