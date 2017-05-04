<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Importer;

use Subugoe\Nkwgok\Utility\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LoadFromOpac implements ImporterInterface
{
    const NKWGOKImportChunkSize = 500;

    /**
     * @var array
     */
    protected $configuration;

    public function run(): bool
    {
        $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][Utility::extKey]);
        $opacBaseURL = $this->configuration['opacBaseURL'].'XML=1/XMLSAVE=N/';

        $baseDir = PATH_site.'fileadmin/gok/';

        GeneralUtility::mkdir_deep(PATH_site, 'fileadmin/gok/xml');
        // Create lkl folder if necessary and remove all files whose names begin with a digit.
        // (This is a simple heuristic to delete all the files we downloaded and keep
        // the CSV files whose names begin with a letter.)
        $XMLDir = $baseDir.'xml/';
        $XMLFileList = glob($XMLDir.'*.xml');
        foreach ($XMLFileList as $file) {
            unlink($file);
        }

        $opacLKLURL = $opacBaseURL.'CMD?ACT=SRCHA/IKT=8600/TRM=tev+not+LKL+p%3F/REC=2/PRS=XML/NORND=1';
        $success = $this->downloadAuthorityDataFromOpacToFolder($opacLKLURL, $XMLDir, Utility::recordTypeGOK);

        $opacBRKURL = $opacBaseURL.'CMD?ACT=SRCHA/IKT=8600/TRM=tov/REC=2/PRS=XML/NORND=1';
        $success &= $this->downloadAuthorityDataFromOpacToFolder($opacBRKURL, $XMLDir, Utility::recordTypeBRK);

        // Create the hitcounts folder if necessary and delete all files inside it if it exists.
        GeneralUtility::mkdir(PATH_site.'fileadmin/gok/hitcounts');
        $hitCountDir = $baseDir.'hitcounts/';
        $hitCountFileList = glob($hitCountDir.'*');
        foreach ($hitCountFileList as $file) {
            unlink($file);
        }

        $opacHitCountURL = $opacBaseURL.'CMD?ACT=BRWS&SCNST='.self::NKWGOKImportChunkSize;
        $success &= $this->downloadHitCountsFromOpacToFolder($opacHitCountURL,
            Utility::typeToIndexName(Utility::recordTypeGOK), $hitCountDir);
        $success &= $this->downloadHitCountsFromOpacToFolder($opacHitCountURL,
            Utility::typeToIndexName(Utility::recordTypeBRK), $hitCountDir);
        $success &= $this->downloadHitCountsFromOpacToFolder($opacHitCountURL,
            Utility::typeToIndexName(Utility::recordTypeMSC), $hitCountDir);

        return $success;
    }

    /**
     * Downloads batches of local authority records from OPAC as
     * Pica XML records and writes them into our fileadmin folder.
     *
     * @param string $opacBaseURL
     * @param string $folderPath
     * @param string $fileBaseName
     *
     * @return bool sucess status of the download
     */
    private function downloadAuthorityDataFromOpacToFolder($opacBaseURL, $folderPath, $fileBaseName)
    {
        $success = true;
        $firstRecord = 1; // Pica result indexing is 1 based
        $hitsAttribute = simplexml_load_file($opacBaseURL)->xpath('/RESULT/SET/@hits');
        $resultCount = (int) $hitsAttribute[0];

        while (($firstRecord < $resultCount) && $success) {
            $URL = $opacBaseURL.'/SHRTST='.self::NKWGOKImportChunkSize.'/FRST='.$firstRecord;
            $opacDownload = file_get_contents($URL);
            if ($opacDownload) {
                $targetFilePath = $folderPath.$fileBaseName.'-'.$firstRecord.'.xml';
                $targetFile = fopen($targetFilePath, 'w');
                if ($targetFile) {
                    // As of 2012-11 the Pica OPAC XML output erroneously double escapes
                    // &, <, > and ", giving &amp;#38; instead of &#38; etc. Undo that.
                    $opacDownload = str_replace('&amp;#', '&#', $opacDownload);

                    fwrite($targetFile, $opacDownload);
                    fclose($targetFile);
                    $firstRecord += self::NKWGOKImportChunkSize;
                } else {
                    GeneralUtility::devLog('loadFromOpac Scheduler Task: could not write file at path '.$targetFilePath,
                        Utility::extKey, 3);
                    $success = false;
                }
            } else {
                GeneralUtility::devLog('loadFromOpac Scheduler Task: failed to load '.$URL, Utility::extKey, 3);
                $success = false;
            }
        }

        if ($success) {
            GeneralUtility::devLog('loadFromOpac Scheduler Task: LKL download for '.$fileBaseName - ' succeeded',
                Utility::extKey, 1);
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
     *
     * @return bool success status of the download
     */
    private function downloadHitCountsFromOpacToFolder($opacScanURL, $indexName, $folderPath)
    {
        $success = true;
        /* Begin scanning the index at 0, except for LKL (which only start at a and have a lot of
            junk entries starting with digits. */
        $scanNext = '0';
        if ($indexName === 'lkl') {
            $scanNext = 'a';
        } elseif ($indexName === 'brk') {
            $scanNext = '01';
        }
        $index = 0;

        while ($scanNext !== null && $success) {
            ++$index;
            $URL = $opacScanURL.'/TRM='.$indexName.'+%22'.$scanNext.'%22';
            $opacDownload = file_get_contents($URL);
            if ($opacDownload) {
                $targetFilePath = $folderPath.$indexName.'-'.$index.'.xml';
                $targetFile = fopen($targetFilePath, 'w');
                if ($targetFile) {
                    fwrite($targetFile, $opacDownload);
                    fclose($targetFile);
                } else {
                    GeneralUtility::devLog('loadFromOpac Scheduler Task: could not write file at path '.$targetFilePath,
                        Utility::extKey, 3);
                    $success = false;
                }

                $termAttribute = simplexml_load_string($opacDownload)->xpath('/RESULT/SCANNEXT/@term');
                $scanNext = $termAttribute[0];
            } else {
                GeneralUtility::devLog('loadFromOpac Scheduler Task: failed to load '.$URL, Utility::extKey, 3);
                $success = false;
            }
        }

        if ($success) {
            GeneralUtility::devLog('loadFromOpac Scheduler Task: hitcount download for index '.$indexName.' succeeded',
                Utility::extKey, 1);
        }

        return $success;
    }
}
