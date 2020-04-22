<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Importer;

use Subugoe\Nkwgok\Utility\Utility;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogManager;
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
        $this->configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('nkwgok');
        $opacBaseURL = $this->configuration['opacBaseURL'].'XML=1/XMLSAVE=N/';

        $baseDir = Environment::getPublicPath().'/fileadmin/gok/';

        GeneralUtility::mkdir_deep(Environment::getPublicPath(). '/fileadmin/gok/xml');
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

        if (true === $success) {
            $success = $this->downloadAuthorityDataFromOpacToFolder($opacBRKURL, $XMLDir, Utility::recordTypeBRK);
        } else {
            return false;
        }

        // Create the hitcounts folder if necessary and delete all files inside it if it exists.
        GeneralUtility::mkdir(Environment::getPublicPath().'/fileadmin/gok/hitcounts');
        $hitCountDir = $baseDir.'hitcounts/';
        $hitCountFileList = glob($hitCountDir.'*');
        foreach ($hitCountFileList as $file) {
            unlink($file);
        }

        $opacHitCountURL = $opacBaseURL.'CMD?ACT=BRWS&SCNST='.self::NKWGOKImportChunkSize;

        if (true === $success) {
            $success = $this->downloadHitCountsFromOpacToFolder(
                $opacHitCountURL,
                Utility::typeToIndexName(Utility::recordTypeGOK),
                $hitCountDir
            );
        } else {
            return false;
        }

        if (true === $success) {
            $success = $this->downloadHitCountsFromOpacToFolder(
                $opacHitCountURL,
                Utility::typeToIndexName(Utility::recordTypeBRK),
                $hitCountDir
            );
        } else {
            return false;
        }
        if (true === $success) {
            $success = $this->downloadHitCountsFromOpacToFolder(
                $opacHitCountURL,
                Utility::typeToIndexName(Utility::recordTypeMSC),
                $hitCountDir
            );
        } else {
            return false;
        }

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
     * @return bool success status of the download
     */
    private function downloadAuthorityDataFromOpacToFolder(string $opacBaseURL, string $folderPath, string $fileBaseName): bool
    {
        $success = true;
        $firstRecord = 1; // Pica result indexing is 1 based
        $hitsAttribute = simplexml_load_string(file_get_contents($opacBaseURL))->xpath('/RESULT/SET/@hits');
        $resultCount = (int) $hitsAttribute[0];
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        while (($firstRecord < $resultCount) && $success) {
            $URL = sprintf('%s/SHRTST=%d/FRST=%s', $opacBaseURL, self::NKWGOKImportChunkSize, $firstRecord);
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
                    $logger->error(sprintf('Could not write file at path %s', $targetFilePath));
                    $success = false;
                }
            } else {
                $logger->error(sprintf('Failed to load %s', $URL));
                $success = false;
            }
        }

        if ($success) {
            $logger->info(sprintf('LKL download for %s succeeded', $fileBaseName));
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
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $success = true;
        /* Begin scanning the index at 0, except for LKL (which only start at a and have a lot of
            junk entries starting with digits. */
        $scanNext = '0';
        if ('lkl' === $indexName) {
            $scanNext = 'a';
        } elseif ('brk' === $indexName) {
            $scanNext = '01';
        }
        $index = 0;

        while (null !== $scanNext && $success) {
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
                    $logger->error(sprintf('Could not write file at path %s', $targetFilePath));
                    $success = false;
                }

                $termAttribute = simplexml_load_string($opacDownload)->xpath('/RESULT/SCANNEXT/@term');
                $scanNext = $termAttribute[0];
            } else {
                $logger->error(sprintf('Failed to load %s', $URL));
                $success = false;
            }
        }

        if ($success) {
            $logger->info(sprintf('Hitcount download for index %s succeeded.', $indexName));
        }

        return $success;
    }
}
