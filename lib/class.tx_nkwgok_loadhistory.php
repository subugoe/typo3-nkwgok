<?php
/**
 * Typo3 Scheduler task to download and process the CSV file with
 * local classification information used for history subjects.
 *
 * The file format is described in the processCSVFile function.
 *
 * 2011 Sven-S. Porst <porst@sub.uni-goettingen.de>
 */

/**
 * Class tx_nkwgok_loadHistory provides task procedures
 *
 * @author		Sven-S. Porst <porst@sub.uni-goettingen.de>
 * @package		TYPO3
 * @subpackage	tx_nkwgok
 */
class tx_nkwgok_loadHistory extends tx_scheduler_Task {

	/**
	 * Function executed from the Scheduler.
	 * @return	boolean	TRUE if success, otherwise FALSE
	 */
	public function execute() {
		$success = $this->fetchHistoryCSV();

		if ($success) {
			$fileList = glob(PATH_site . 'fileadmin/gok/csv/*.csv');
			foreach ($fileList as $CSVPath) {
				$success = $this->processCSVFile($CSVPath);
				if (!$success) break;
			}
		}

		return $success;
	}



	/**
	 * TODO: implement download of history file once the server is set up.
	 * @return Boolean success status
	 */
	private function fetchHistoryCSV() {
		return True;
	}



	/**
	 * Loads CSV file at the given path and processes it to Opac XML format
	 * with Pica Tev fields for the corresponding Normdatensatz.
	 *
	 * The file’s text encoding is expected to be UTF-8.
	 *
	 * Columns in the file are:
	 * 1:	PPN -> 003@ $0
	 * 2:	hierarchy level -> 009B $a
	 * 3:	GOK -> 045A $a
	 * 4:	parent PPN -> 038D $9
	 * 5:	GOK name (German) -> 044E $a
	 * 6:	search query -> str
	 * 7:	GOK name (English) -> 044F $a
	 *
	 * @param string $csvPath path to CSV file whose name should end in .csv and contain no other dots
	 * @return Boolean success status
	 */
	private function processCSVFile ($csvPath) {
		$success = False;
		$doc = Null;
		$csvString = file_get_contents($csvPath);
		$PPNList = Array();
		
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

			while (($fields = fgetcsv($fileHandle, 4096, ';', '"')) !== false) { 
				if (count($fields) >= 5) {
					// GOK name is in field 5, so ignore lines with less fields.
					$shorttitle = $doc->createElement('SHORTTITLE');
					$set->appendChild($shorttitle);
					$record = $doc->createElement('record');
					$shorttitle->appendChild($record);
					$PPN = trim($fields[0]);
					$parentPPN = trim($fields[3]);
					$this->appendFieldForDataTo('003@', '0', $PPN, $record, $doc);
					$this->appendFieldForDataTo('009B', 'a', trim($fields[1]), $record, $doc);
					$this->appendFieldForDataTo('045A', 'a', trim($fields[2]), $record, $doc);
					$this->appendFieldForDataTo('038D', '9', $parentPPN, $record, $doc);
					$this->appendFieldForDataTo('044E', 'a', trim($fields[4]), $record, $doc);
					if (count($fields) > 5) {
						// Search query
						$this->appendFieldForDataTo('str', 'a', trim($fields[5]), $record, $doc);

						if (count($fields) >6) {
							// English GOK Name
							$this->appendFieldForDataTo('044F', 'a', trim($fields[6]), $record, $doc);
						}
					}
					
					// Warn if the parent PPN doesn’t exist yet.
					if ($parentPPN != '' && !$PPNList[$parentPPN]) {
						t3lib_div::devLog('loadHistory Scheduler Task: Parent PPN ' . $parentPPN . ' not defined when reading child record ' . $PPN, 'nkwgok', 2);
					}

					// Warn if there are duplicate PPNs.
					if ($PPNList[$PPN]) {
						t3lib_div::devLog('loadHistory Scheduler Task: Duplicate PPN ' . $PPN, 'nkwgok', 2);
					}
					
					// Add current PPN to PPN list.
					$PPNList[$PPN] = True;
										
				}	
				else if (count($fields) != 0) {
					t3lib_div::devLog('loadHistory Scheduler Task: Line "' . implode(';', $fields) . 'contains less than 5 fields.', 'nkwgok', 2);
				}
			}
			fclose($fileHandle); 

			// Write XML file
			$csvPathParts = explode('/', $csvPath);
			$originalFileName = $csvPathParts[count($csvPathParts) - 1];
			$originalFileNameParts = explode('.', $originalFileName);
			$XMLFileName = $originalFileNameParts[0] . '.xml';
			$resultPath = PATH_site. 'fileadmin/gok/lkl/' . $XMLFileName ;
			if ($doc->save($resultPath) === False) {
				t3lib_div::devLog('loadHistory Scheduler Task: Failed to write XML file' . $resultPath , 'nkwgok', 3);
			}
			else {
				t3lib_div::devLog('loadHistory Scheduler Task: Successfully wrote XML file ' . $resultPath , 'nkwgok', 1);
				$success = True;
			}
		}
		else {
			t3lib_div::devLog('loadHistory Scheduler Task: Could not open file ' . $csvPath , 'nkwgok', 3);
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
	 */
	private function appendFieldForDataTo ($fieldName, $subfieldName, $content, $container, $doc) {
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
			t3lib_div::devLog('loadHistory Scheduler Task: Some parameter was Null in appendFieldForDataTo' , 'nkwgok', 3);
		}
	}


}



if (defined('TYPO3_MODE')
		&& $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadhistory.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/nkwgok/lib/class.tx_nkwgok_loadhistory.php']);
}
?>
