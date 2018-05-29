<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Importer;

use Subugoe\Nkwgok\Utility\Utility;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConvertCsv implements ImporterInterface
{
    /**
     * List of all PPNs processed so far.
     * Used to determine whether all parent PPNs exist.
     *
     * @var array
     */
    protected $PPNList = [];

    public function run(): bool
    {
        $URLList = $this->getCSVDownloadURLs();
        $this->downloadURLs($URLList);

        $success = true;
        $fileList = glob(PATH_site.'fileadmin/gok/csv/*.csv');
        foreach ($fileList as $CSVPath) {
            $success = $this->processCSVFile($CSVPath);
            if (!$success) {
                break;
            }
        }

        return $success;
    }

    /**
     * Retrieves setup information with an array of URLs pointing to CSV files
     * that need to be downloaded from TypoScript.
     * The ID of the page storing the TypoScript needs to be set up in extension
     * manager.
     *
     * @return array of URLs to download
     */
    protected function getCSVDownloadURLs()
    {
        $downloadURLs = [];

        $conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['nkwgok']);
        $URLsString = $conf['CSVURLs'];

        if ($URLsString) {
            $URLStrings = explode(' ', trim($URLsString));
            foreach ($URLStrings as $URL) {
                $URL = trim($URL);
                if ('' !== $URL) {
                    $downloadURLs[] = $URL;
                }
            }
        }

        return $downloadURLs;
    }

    /**
     * Downloads files from URLs in the passed array to fileadmin/gok/csv.
     * Potentially overwrites previously existing files.
     *
     * @param array $URLList
     */
    private function downloadURLs($URLList)
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        foreach ($URLList as $URL) {
            $URLPathComponents = explode('/', parse_url($URL, PHP_URL_PATH));
            $fileName = $URLPathComponents[count($URLPathComponents) - 1];
            $remoteData = file_get_contents($URL);
            if (false !== $remoteData) {
                $localPath = PATH_site.'fileadmin/gok/csv/'.$fileName;
                $localData = file_get_contents($localPath);
                if ($localData != $remoteData) {
                    // Only overwrite local file if the file contents have changed.
                    if (false !== file_put_contents($localPath, $remoteData)) {
                        $logger->info(sprintf('Replaced file %s.', $localPath));
                    } else {
                        $logger->warning(sprintf('Failed to write downloaded file to %s.', $localPath), [$localData, $remoteData]);
                    }
                }
            } else {
                $logger->warning(sprintf('Failed to download %s.', $URL));
            }
        }
    }

    /**
     * Loads CSV file at the given path and processes it to OPAC XML format
     * with Pica Tev fields for the corresponding Normdatensatz.
     *
     * The file’s text encoding is expected to be UTF-8 or ISO/Windows Latin-1.
     *
     * Columns in the file are:
     * 1:    PPN -> 003@ $0
     * 2:    parent PPN -> 045C $9 with $4 nueb
     * 3:    subject name (German) -> 045A $j
     * 4:    search query -> str $a
     * 5:    subject name (English) -> 044F $a with $S d [optional]
     * 6:    Tags (comma-separated list of strings) -> tags $a [optional]
     *
     * @param string $CSVPath path to CSV file whose name should end in .csv and contain no other dots
     *
     * @return bool success status
     */
    private function processCSVFile($CSVPath)
    {
        $success = false;
        $doc = null;
        $startLine = 0;
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $CSVString = file_get_contents($CSVPath);
        // Handle UTF-8, ISO, and Windows files. We expect the latter as the CSV is written by Excel.
        $stringEncoding = mb_detect_encoding($CSVString, ['UTF-8', 'ISO-8859-1', 'windows-1252']);
        if ('UTF-8' != $stringEncoding) {
            $CSVString = mb_convert_encoding($CSVString, 'UTF-8', $stringEncoding);
        }
        $CSVString = str_replace("\r\n", "\n", $CSVString);

        $CSVLines = explode("\n", $CSVString);
        foreach ($CSVLines as $lineNumber => $line) {
            // Set up document.
            if (null === $doc) {
                $domImplementation = new \DOMImplementation();
                $doc = $domImplementation->createDocument();
                $result = $doc->createElement('RESULT');
                $doc->appendChild($result);
                $set = $doc->createElement('SET');
                $result->appendChild($set);
                $startLine = $lineNumber;
            }

            $fields = str_getcsv($line, ';', '"');

            // Use data from CSV to build Pica-style data fields in XML.
            if (count($fields) >= 3 && '' !== trim(implode('', $fields))) {
                // subject name is in field 5, so ignore lines with fewer fields
                // as well as those with only empty fields.
                $PPN = trim($fields[0]);

                if ('' !== $PPN) {
                    // The record is required to have a non-empty PPN.
                    $shorttitle = $doc->createElement('SHORTTITLE');
                    $set->appendChild($shorttitle);
                    $record = $doc->createElement('record');
                    $shorttitle->appendChild($record);
                    // 002@ is the Pica record type, put our made-up 'csv' there.
                    $this->appendFieldForDataTo('002@', '0', 'csv', $record, $doc);
                    // 003@ is the Pica record ID, PPN.
                    $this->appendFieldForDataTo('003@', '0', $PPN, $record, $doc);
                    // 045A contains the subject notation in $a and subject name in $j.
                    $d045A = $this->appendFieldForDataTo('045A', 'a', $PPN, $record, $doc);
                    if (null !== trim($fields[2])) {
                        $subfieldJ = $doc->createElement('subfield');
                        $subfieldJ->setAttribute('code', 'j');
                        $subfieldJ->appendChild($doc->createTextNode(trim($fields[2])));
                        $d045A->appendChild($subfieldJ);
                    }
                    // 045C $9 is the parent record’s PPN, $4 nueb indicates it is a parent record.
                    $parentPPN = trim($fields[1]);
                    $d045C = $this->appendFieldForDataTo('045C', '9', $parentPPN, $record, $doc);
                    $subfield4 = $doc->createElement('subfield');
                    $subfield4->setAttribute('code', '4');
                    $subfield4->appendChild($doc->createTextNode('nueb'));
                    $d045C->appendChild($subfield4);

                    if (count($fields) > 3) {
                        // Search query
                        // Write custom search query in the made-up field str $a.
                        $this->appendFieldForDataTo('str', 'a', trim($fields[3]), $record, $doc);

                        if (count($fields) > 4) {
                            // 044F $a contains the subject name’s English translation and a subfield $S d.
                            $d044F = $this->appendFieldForDataTo('044F', 'a', trim($fields[4]), $record, $doc);
                            $subfieldS = $doc->createElement('subfield');
                            $subfieldS->setAttribute('code', 'S');
                            $subfieldS->appendChild($doc->createTextNode('d'));
                            $d044F->appendChild($subfieldS);

                            if (count($fields) > 5) {
                                // Use made-up field tags $a for tags string.
                                $this->appendFieldForDataTo('tags', 'a', trim($fields[5]), $record, $doc);
                            }
                        }
                    }

                    if ($this->PPNList[$PPN]) {
                        $logger->warning(sprintf('Duplicate PPN "%s" in file %s', $PPN, $CSVPath));
                    }
                    if ($this->PPNList[$PPN]) {
                        $logger->warning(sprintf('Duplicate PPN "%s" in file %s', $PPN, $CSVPath));
                    }

                    // Add current PPN to PPN list.
                    $this->PPNList[$PPN] = true;
                } else {
                    $logger->warning(sprintf('Blank PPN  in line: "%s" of file %s', implode(';', $fields), $CSVPath));
                }
            } elseif (count($fields) > 1 && '' !== trim(implode('', $fields))) {
                $logger->warning(sprintf('Line "%s" of file %s contains less than 3 fields.', implode(';', $fields), $CSVPath));
            }

            // Write document to XML file every 500 lines or after the last line in the file.
            if (0 === ($lineNumber + 1) % 500 || $lineNumber + 1 === count($CSVLines)) {
                $csvPathParts = explode('/', $CSVPath);
                $originalFileName = $csvPathParts[count($csvPathParts) - 1];
                $originalFileNameParts = explode('.', $originalFileName);
                $XMLFileName = $originalFileNameParts[0].'-'.$startLine.'.xml';
                $resultPath = PATH_site.'fileadmin/gok/xml/'.$XMLFileName;

                if (false === $doc->save($resultPath)) {
                    $logger->error(sprintf('Failed to write XML file %s', $resultPath));
                    break;
                } else {
                    $success = true;
                }
                $doc = null;
            }
        }

        return $success;
    }

    /**
     * Wraps the field content in a datafield/subfield structure with the
     * given field names and inserts it into the passed container.
     *
     * @param string       $fieldName    tag attribute of the resulting datafield tag
     * @param string       $subfieldName code attribute of the resulting subfield tag
     * @param string       $content      text put into the subfield
     * @param \DOMElement  $container    the datafield is appended to
     * @param \DOMDocument $doc          of $container
     *
     * @return \DOMElement|null The datafield that was inserted
     */
    private function appendFieldForDataTo($fieldName, $subfieldName, $content, $container, $doc)
    {
        $datafield = null;
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        if (null !== $fieldName && null !== $subfieldName
            && null !== $content && null !== $container && null !== $doc
        ) {
            $datafield = $doc->createElement('datafield');
            $datafield->setAttribute('tag', $fieldName);
            $container->appendChild($datafield);

            $subfield = $doc->createElement('subfield');
            $subfield->setAttribute('code', $subfieldName);
            $datafield->appendChild($subfield);

            $subfield->appendChild($doc->createTextNode($content));
        } else {
            $logger->error('Some parameter was Null in appendFieldForDataTo');
        }

        return $datafield;
    }
}
