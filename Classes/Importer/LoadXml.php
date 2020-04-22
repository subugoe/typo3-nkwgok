<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Importer;

use Subugoe\Nkwgok\Domain\Model\Description;
use Subugoe\Nkwgok\Domain\Model\Term;
use Subugoe\Nkwgok\Utility\Utility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class LoadXml implements ImporterInterface
{
    const NKWGOKMaxHierarchy = 31;

    /**
     * Stores the hitcount for each notation.
     * Key: classifiaction system string => Value: Array with
     *        Key: notation => Value: hits for this notation.
     *
     * @var array
     */
    private $hitCounts;

    public function run(): bool
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Utility::dataTable);

        // Remove records with statusID 1. These should not be around, but can
        // exist if a previous run of this task was cancelled.
        $queryBuilder->delete(Utility::dataTable)
            ->where($queryBuilder->expr()->eq('statusID', 1))
            ->execute();

        // Load hit counts.
        $this->hitCounts = $this->loadHitCounts();

        // Load XML files. Process those coming from csv files first as they can
        // be quite large and we are less likely to run into memory limits this way.
        $result = $this->loadXMLForType(Utility::recordTypeCSV);

        if (true === $result) {
            $logger->info(sprintf('Import for %s succeeded', Utility::recordTypeCSV));
            $result = $this->loadXMLForType(Utility::recordTypeGOK);
        } else {
            $logger->error(sprintf('Import for %s failed', Utility::recordTypeCSV));

            return false;
        }

        if (true === $result) {
            $logger->info(sprintf('Import for %s succeeded', Utility::recordTypeBRK));
            $result = $this->loadXMLForType(Utility::recordTypeBRK);
        } else {
            $logger->error(sprintf('Import for %s failed', Utility::recordTypeBRK));

            return false;
        }

        // Delete all old records with statusID 1, then switch all new records to statusID 0.
        $queryBuilder->delete(Utility::dataTable)
            ->where($queryBuilder->expr()->eq('statusID', 0))
            ->execute();

        $queryBuilder->update(Utility::dataTable)
            ->set('statusID', 0)
            ->where($queryBuilder->expr()->eq('statusID', 1))
            ->execute();

        $logger->info('Import of subject hierarchy XML to TYPO3 database completed');

        return $result;
    }

    /**
     * Loads the Pica XML records of the given type, tries to determine the hit
     * counts for each of them and inserts them to the database.
     *
     * @param string $type
     *
     * @return bool
     */
    protected function loadXMLForType($type)
    {
        $XMLFolder = Environment::getPublicPath().'/fileadmin/gok/xml/';
        $fileList = $this->fileListAtPathForType($XMLFolder, $type);
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        if (is_array($fileList) && count($fileList) > 0) {
            // Parse XML files to extract just the tree structure.
            $subjectTree = $this->loadSubjectTree($fileList);

            // Compute total hit count sums.
            $totalHitCounts = $this->computeTotalHitCounts(Utility::rootNode, $subjectTree, $this->hitCounts);

            // Run through the files again, read all data, add the information
            // about parent elements and store it to our table in the database.
            foreach ($fileList as $xmlPath) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(Utility::dataTable);

                $xml = simplexml_load_string(file_get_contents($xmlPath));

                foreach ($xml->xpath('/RESULT/SET/SHORTTITLE/record') as $recordElement) {
                    $term = new Term();

                    $term
                        ->setStatusId(1)
                        ->setType($this->typeOfRecord($recordElement));

                    // Build complete record and insert into database.
                    // Discard records without a PPN.
                    $PPNs = $recordElement->xpath('datafield[@tag="003@"]/subfield[@code="0"]');
                    $term->setPpn(trim((string) $PPNs[0]));

                    $notations = $recordElement->xpath('datafield[@tag="045A"]/subfield[@code="a"]');

                    if (count($notations) > 0) {
                        $term->setNotation(trim((string) $notations[0]));
                    }

                    $mscs = $recordElement->xpath('datafield[@tag="044H" and subfield[@code="2"]="msc"]/subfield[@code="a"]');
                    $csvSearches = $recordElement->xpath('datafield[@tag="str"]/subfield[@code="a"]');

                    if ('' !== $term->getPpn() && array_key_exists($term->getPpn(), $subjectTree)) {
                        if (Utility::recordTypeCSV === $term->getType()) {
                            // Subject coming from CSV file with a CCL search query in the 'str/a' field.
                            if (count($csvSearches) > 0) {
                                $csvSearch = trim((string) $csvSearches[0]);
                                $term->setSearch($csvSearch);
                            }
                        } else {
                            // Subject coming from a Pica authority record.
                            if (count($mscs) > 0) {
                                // Maths type GOK with an MSC type search term.
                                $msc = trim((string) $mscs[0]);
                                $term->setSearch('msc="'.$msc.'"');
                            } elseif (count($notations) > 0) {
                                if (Utility::recordTypeGOK === $term->getType() || Utility::recordTypeBRK === $term->getType()) {
                                    // GOK or BRK OPAC search, using the corresponding index.
                                    $indexName = Utility::typeToIndexName($term->getType());
                                    // Requires quotation marks around the search term as notations can begin
                                    // with three character strings that could be mistaken for index names.
                                    $term->setSearch($indexName.'="'.$term->getNotation().'"');
                                } else {
                                    $logger->info(sprintf('Unknown record type »%s« in record PPN %s. Skipping.', $term->getType(), $term->getPpn()), ['name' => $recordElement->getName()]);
                                    continue;
                                }
                            }
                        }

                        $treeElement = $subjectTree[$term->getPpn()];
                        $term->setParent($treeElement['parent']);

                        // Use stored subject tree to determine hierarchy level.
                        // The hierarchy should be no deeper than 12 levels
                        // (for GOK) and 25 levels (for BRK).
                        // Cut off at 32 to prevent an infinite loop.
                        $nextParent = $term->getParent();
                        while (null !== $nextParent && Utility::rootNode !== $nextParent) {
                            $term->setHierarchy($term->getHierarchy() + 1);
                            if (array_key_exists($nextParent, $subjectTree)) {
                                $nextParent = $subjectTree[$nextParent]['parent'];
                            } else {
                                $logger->error(
                                    sprintf('Could not determine hierarchy level: Unknown parent PPN %s for record PPN %s. This needs to be fixed if he subject is meant to appear in a subject hierarchy.', $nextParent, $term->getPpn()),
                                    ['element' => $recordElement->getName()]
                                );
                                $term->setHierarchy(-1);
                                break;
                            }
                            if ($term->getHierarchy() > self::NKWGOKMaxHierarchy) {
                                $logger->error(sprintf('Hierarchy level for PPN %s exceeds the maximum limit of %s levels. This needs to be fixed, the subject tree may contain an infinite loop.', $term->getPpn(), self::NKWGOKMaxHierarchy), ['element' => $recordElement->getName()]);
                                $term->setHierarchy(-1);
                                break;
                            }
                        }

                        $term->setDescription($this->getDescription($recordElement));

                        // Tags from the field tags artificially inserted by our CSV converter.
                        $tagss = $recordElement->xpath('datafield[@tag="tags"]/subfield[@code="a"]');
                        if (count($tagss) > 0) {
                            $term->setTags(trim((string) $tagss[0]));
                        }

                        // Hitcount keys are lowercase.
                        // Set result count information:
                        // * for GOK, BRK, and MSC-type records: try to use hitcount
                        // * for CSV-type records: if only one LKL query, try to use hitcount, else use -1
                        // * otherwise: use 0
                        if (count($mscs) > 0) {
                            $msc = trim((string) $mscs[0]);
                            if (array_key_exists(Utility::recordTypeMSC, $this->hitCounts)
                                && array_key_exists($msc, $this->hitCounts[Utility::recordTypeMSC])
                            ) {
                                $term->setHitCount($this->hitCounts[Utility::recordTypeMSC][$msc]);
                            }
                        } elseif ((Utility::recordTypeGOK === $term->getType() || Utility::recordTypeBRK === $term->getType())
                            && (is_array($this->hitCounts[$term->getType()]) && array_key_exists(strtolower($term->getNotation()), $this->hitCounts[$term->getType()]))
                        ) {
                            $term->setHitCount($this->hitCounts[$term->getType()][strtolower($term->getNotation())]);
                        } elseif (Utility::recordTypeCSV === $term->getType() && count($csvSearches) > 0) {
                            // Try to detect simple GOK and MSC queries from CSV files so hit counts can be displayed for them.
                            $csvSearch = trim((string) $csvSearches[0]);

                            $foundGOKs = [];
                            $GOKPattern = '/^lkl=([a-zA-Z]*\s?[.X0-9]*)$/';
                            preg_match($GOKPattern, $csvSearch, $foundGOKs);
                            $foundGOK = strtolower((string) $foundGOKs[1]);

                            $foundMSCs = [];
                            $MSCPattern = '/^msc=([0-9Xx][0-9Xx][A-Z-]*[0-9Xx]*)/';
                            preg_match($MSCPattern, $csvSearch, $foundMSCs);
                            $foundMSC = strtolower((string) $foundMSCs[1]);

                            if (count($foundGOKs) > 1
                                && $foundGOK
                                && array_key_exists(Utility::recordTypeGOK, $this->hitCounts)
                                && array_key_exists($foundGOK, $this->hitCounts[Utility::recordTypeGOK])
                            ) {
                                $term->setHitCount($this->hitCounts[Utility::recordTypeGOK][$foundGOK]);
                            } elseif (count($foundMSCs) > 1
                                && $foundMSC
                                && array_key_exists(Utility::recordTypeMSC, $this->hitCounts)
                                && array_key_exists($foundMSC, $this->hitCounts[Utility::recordTypeMSC])
                            ) {
                                $term->setHitCount($this->hitCounts[Utility::recordTypeMSC][$foundMSC]);
                                $term->setType(Utility::recordTypeMSC);
                            }
                        } else {
                            $term->setHitCount(0);
                        }

                        // Add total hit count information if it exists.
                        if (array_key_exists($term->getPpn(), $totalHitCounts)) {
                            $term->setTotalHitCounts($totalHitCounts[$term->getPpn()]);
                        }

                        $term->setChildCount(count($treeElement['children']));

                        $row = [
                            'ppn' => $term->getPpn(),
                            'hierarchy' => $term->getHierarchy(),
                            'notation' => $term->getNotation(),
                            'parent' => $term->getParent(),
                            'descr' => $term->getDescription()->getDescription(),
                            'descr_en' => $term->getDescription()->getDescriptionEnglish(),
                            'descr_alternate' => $term->getDescription()->getAlternate(),
                            'descr_alternate_en' => $term->getDescription()->getAlternateEnglish(),
                            'search' => $term->getSearch(),
                            'tags' => $term->getTags(),
                            'childcount' => $term->getChildCount(),
                            'type' => $term->getType(),
                            'hitcount' => $term->getHitCount(),
                            'totalhitcount' => $term->getTotalHitCounts(),
                            'crdate' => time(),
                            'tstamp' => time(),
                            'statusID' => $term->getStatusId(),
                        ];

                        $queryBuilder
                           ->insert(Utility::dataTable)
                           ->values($row)
                           ->execute();
                    }
                } // end of loop over subjects
            } // end of loop over files

            $result = true;
        } else {
            $logger->error(sprintf('No XML files for type %s found.', $type));
            $result = true;
        }

        return $result;
    }

    /**
     * Goes through data files and creates information of the subject tree’s
     * structure from that.
     *
     * Storing the full data from all records would run into memory problems.
     * The resulting array just keeps the information we strictly need for
     * analysis.
     *
     * Returns an array. Keys are record IDs (PPNs), values are Arrays with:
     * * children => Array of strings (record IDs of child elements)
     * * [parent => string (record ID of parent element)]
     * * notation [gok|brk|msc|bkl|…] => string
     *
     * @param array $fileList list of XML files to read
     *
     * @return array containing the subject tree structure
     */
    private function loadSubjectTree($fileList)
    {
        $tree = [];
        $tree[Utility::rootNode] = ['children' => []];

        // Run through all files once to gather information about the
        // structure of the data we process.
        foreach ($fileList as $xmlPath) {
            $xml = simplexml_load_string(file_get_contents($xmlPath));
            $records = $xml->xpath('/RESULT/SET/SHORTTITLE/record');

            foreach ($records as $record) {
                $PPNs = $record->xpath('datafield[@tag="003@"]/subfield[@code="0"]');
                $PPN = (string) ($PPNs[0]);

                // Create entry in the tree array if necessary.
                if (!array_key_exists($PPN, $tree)) {
                    $tree[$PPN] = ['children' => []];
                }

                $recordType = $this->typeOfRecord($record);
                $tree[$PPN]['type'] = $recordType;

                $myParentPPNs = $record->xpath('datafield[@tag="045C" and subfield[@code="4"] = "nueb"]/subfield[@code="9"]');
                if ($myParentPPNs && count($myParentPPNs) > 0) {
                    // Child record: store its PPN in the list of its parent’s children…
                    $parentPPN = (string) ($myParentPPNs[0]);
                    if (!array_key_exists($parentPPN, $tree)) {
                        $tree[$parentPPN] = ['children' => []];
                    }
                    $tree[$parentPPN]['children'][] = $PPN;

                    // … and store the PPN of the parent record.
                    $tree[$PPN]['parent'] = $parentPPN;
                } else {
                    // has no parent record
                    $parentPPN = Utility::rootNode;
                    $tree[$parentPPN]['children'][] = $PPN;
                    $tree[$PPN]['parent'] = $parentPPN;
                }

                if (Utility::recordTypeGOK === $recordType || Utility::recordTypeBRK === $recordType) {
                    // Store notation information.
                    $notationStrings = $record->xpath('datafield[@tag="045A"]/subfield[@code="a"]');
                    if (count($notationStrings) > 0) {
                        $notationString = (string) ($notationStrings[0]);
                        $notation = strtolower(trim($notationString));
                        $tree[$PPN][$recordType] = $notation;
                    }
                } else {
                    $queries = $record->xpath('datafield[@tag="str"]/subfield[@code="a"]');
                    if (1 === count($queries)) {
                        $query = (string) ($queries[0]);
                        $foundQueries = null;
                        if (preg_match('/^msc=([^ ]*)$/', $query, $foundQueries) && 2 === count($foundQueries)) {
                            $msc = $foundQueries[1];
                            $tree[$PPN][Utility::recordTypeMSC] = $msc;
                            $tree[$PPN]['type'] = Utility::recordTypeMSC;
                        }
                    }
                }

                // Store the last additional notation information (044H) of
                // each type (given in $2). In particular used for MSC.
                $extraNotations = $record->xpath('datafield[@tag="044H"]');
                foreach ($extraNotations as $extraNotation) {
                    $extraNotationTexts = $extraNotation->xpath('subfield[@code="a"]');
                    $extraNotationLabels = $extraNotation->xpath('subfield[@code="2"]');
                    if ($extraNotationTexts && $extraNotationLabels) {
                        $tree[$PPN][strtolower(trim($extraNotationLabels[0]->getName()))] = strtolower(trim($extraNotationTexts[0]->getName()));
                    }
                }
            } // end foreach $records
        } // end foreach $fileList

        return $tree;
    }

    /**
     * Load hitcounts from fileadmin/gok/hitcounts/*.xml.
     * These files are downloaded from the OPAC by the loadFromOpac Scheduler task.
     *
     * @return array with Key: classification system string => Value: Array with Key: notation => Value: hits for this notation
     */
    private function loadHitCounts()
    {
        $hitCountFolder = Environment::getPublicPath().'/fileadmin/gok/hitcounts/';
        $fileList = $this->fileListAtPathForType($hitCountFolder, 'all');
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $hitCounts = [];
        if (is_array($fileList)) {
            foreach ($fileList as $xmlPath) {
                $xml = simplexml_load_string(file_get_contents($xmlPath));
                if ($xml) {
                    $scanlines = $xml->xpath('/RESULT/SCANLIST/SCANLINE');
                    foreach ($scanlines as $scanline) {
                        $hits = null;
                        $description = null;
                        $hitCountType = null;
                        foreach ($scanline->attributes() as $name => $value) {
                            if ('hits' === $name) {
                                $hits = (int) $value;
                            } elseif ('description' === $name) {
                                $description = (string) $value;
                            } elseif ('mnemonic' === $name) {
                                $hitCountType = Utility::indexNameToType(strtolower((string) $value));
                            }
                        }
                        if (null !== $hits && null !== $description && null !== $hitCountType) {
                            if (!array_key_exists($hitCountType, $hitCounts)) {
                                $hitCounts[$hitCountType] = [];
                            }
                            $hitCounts[$hitCountType][$description] = $hits;
                        }
                    }
                } else {
                    $logger->error(sprintf('Could not load/parse XML from %s', $xmlPath));
                }
            }
        } // end foreach

        foreach ($hitCounts as $hitCountType => $array) {
            $logger->info(sprintf('Loaded %d %s hit count entries.', count($array), $hitCountType));
        }

        return $hitCounts;
    }

    /**
     * Recursively go through $subjectTree and add up the $hitCounts to return a
     * total hit count including the hits for all child elements.
     *
     * @param string $startPPN    - PPN to start at
     * @param array  $subjectTree
     * @param array  $hitCounts
     *
     * @return array with Key: PPN => Value: sum of hit counts
     */
    private function computeTotalHitCounts(string $startPPN, array $subjectTree, array $hitCounts): array
    {
        $totalHitCounts = [];
        $myHitCount = 0;
        if (array_key_exists($startPPN, $subjectTree)) {
            $record = $subjectTree[$startPPN];
            $type = $record['type'];

            $notation = strtolower($record[$type] ?? '');

            if (array_key_exists(Utility::recordTypeMSC, $record) && Utility::recordTypeBRK !== $type) {
                $type = Utility::recordTypeMSC;
                $notation = strtolower($record[Utility::recordTypeMSC]);
            }

            if (count($record['children']) > 0) {
                // A parent node: recursively collect and add up the hit counts.
                foreach ($record['children'] as $childPPN) {
                    $childHitCounts = $this->computeTotalHitCounts($childPPN, $subjectTree, $hitCounts);
                    if (array_key_exists($childPPN, $childHitCounts)) {
                        $myHitCount += $childHitCounts[$childPPN];
                    }
                    $totalHitCounts += $childHitCounts;
                }

                if (array_key_exists($type, $hitCounts)
                    && array_key_exists($notation, $hitCounts[$type])
                ) {
                    $myHitCount += $hitCounts[$type][$notation];
                }
            } elseif (array_key_exists($type, $hitCounts)
                && array_key_exists($notation, $hitCounts[$type])
            ) {
                $myHitCount += $hitCounts[$type][$notation];
            }
        }

        $totalHitCounts[$startPPN] = $myHitCount;

        return $totalHitCounts;
    }

    /**
     * Returns Array of file paths in $basePath of the given type.
     * The types are:
     *    * 'all': returns all *.xml files
     *  * 'gok': returns all gok-*.xml files
     *  * 'brk': returns all brk-*.xml files
     *  * otherwise the list given by 'all' - 'gok' - 'brk' is returned.
     *
     * @param string $basePath
     * @param string $type
     *
     * @return array of file paths in $basePath
     */
    private function fileListAtPathForType($basePath, $type)
    {
        if ('all' === $type) {
            $fileList = glob($basePath.'*.xml');
        } elseif (Utility::recordTypeGOK === $type || Utility::recordTypeBRK === $type) {
            $fileList = glob($basePath.$type.'-*.xml');
        } else {
            $fileList = glob($basePath.'*.xml');
            $gokFiles = glob($basePath.Utility::recordTypeGOK.'-*.xml');
            if (is_array($gokFiles)) {
                $fileList = array_diff($fileList, $gokFiles);
            }
            $brkFiles = glob($basePath.Utility::recordTypeBRK.'-*.xml');
            if (is_array($brkFiles)) {
                $fileList = array_diff($fileList, $brkFiles);
            }
        }

        return $fileList;
    }

    /**
     * Returns the type of the $record passed.
     * Logs unknown record types.
     *
     * @param \SimpleXMLElement $record
     *
     * @return string - gok|brk|csv|unknown
     */
    private function typeOfRecord($record)
    {
        $recordType = Utility::recordTypeUnknown;
        $recordTypes = $record->xpath('datafield[@tag="002@"]/subfield[@code="0"]');

        if ($recordTypes && 1 === count($recordTypes)) {
            $recordTypeCode = (string) $recordTypes[0];

            if ('Tev' === $recordTypeCode) {
                $recordType = Utility::recordTypeGOK;
            } elseif ('Tov' === $recordTypeCode) {
                $recordType = Utility::recordTypeBRK;
            } elseif ('csv' === $recordTypeCode) {
                $queryElements = $record->xpath('datafield[@tag="str"]/subfield[@code="a"]');
                if ($queryElements && 1 === count($queryElements)
                    && preg_match('/^msc=[0-9A-Zx-]*/', (string) ($queryElements[0] > 0))
                ) {
                    // Special case: an MSC record.
                    $recordType = Utility::recordTypeMSC;
                } else {
                    // Regular case: a standard CSV record.
                    $recordType = Utility::recordTypeCSV;
                }
            }
        }

        if (Utility::recordTypeUnknown === $recordType) {
            GeneralUtility::devLog(
                'Record of unknown type.',
                Utility::extKey,
                1,
                [$record->saveXML()]
            );
        }

        return $recordType;
    }

    private function getDescription(\SimpleXMLElement $recordElement): Description
    {
        $description = new Description();

        // Main subject name from field 045A $j.
        $descrs = $recordElement->xpath('datafield[@tag="045A"]/subfield[@code="j"]');
        if (count($descrs) > 0) {
            $description->setDescription(trim((string) $descrs[0]));
        }

        // English version of the subject’s name from field 044F $a if $S is »d«.
        $descr_ens = $recordElement->xpath('datafield[@tag="044F" and subfield[@code="S"]="d"]/subfield[@code="a"]');
        if (count($descr_ens) > 0) {
            $description->setDescriptionEnglish(trim((string) $descr_ens[0]));
        }

        // Alternate/additional description of the subject from field 044F $a if $S is »g« and $L is not »eng«
        $descr_alternates = $recordElement->xpath('datafield[@tag="044F" and subfield[@code="S"]="g" and not(subfield[@code="L"]="eng")]/subfield[@code="a"]');
        if (count($descr_alternates) > 0) {
            $description->setAlternate(trim(implode('; ', $descr_alternates)));
        }

        // English version of alternate/additional description of the subject from field 044F $a if $S is »g« and $L is  »eng«
        $descr_alternate_ens = $recordElement->xpath('datafield[@tag="044F" and subfield[@code="S"]="g" and subfield[@code="L"]="eng"]/subfield[@code="a"]');
        if (count($descr_alternate_ens) > 0) {
            $description->setAlternateEnglish(trim(implode('; ', $descr_alternate_ens)));
        }

        return $description;
    }
}
