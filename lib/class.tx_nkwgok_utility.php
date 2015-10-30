<?php

/**
 * Class providing helper functions and constants.
 **/
class tx_nkwgok_utility
{

    const extKey = 'nkwgok';
    const dataTable = 'tx_nkwgok_data';

    const rootNode = 'Root';

    const recordTypeGOK = 'gok';
    const recordTypeBRK = 'brk';
    const recordTypeCSV = 'csv';
    const recordTypeMSC = 'msc';
    const recordTypeUnknown = 'unknown';


    /**
     * Returns the internal type name for the given index name.
     * * lkl -> gok
     * * pass others through unchanged
     *
     * @param String $indexName
     * @return String
     */
    public static function indexNameToType($indexName)
    {
        $type = $indexName;

        if ($indexName === 'lkl') {
            $type = tx_nkwgok_utility::recordTypeGOK;
        }

        return $type;
    }


    /**
     * Returns the internal type name for the given index name.
     * * gok -> lkl
     * * pass others through unchanged
     *
     * @param String $type
     * @return String
     */
    public static function typeToIndexName($type)
    {
        $indexName = $type;

        if ($type === tx_nkwgok_utility::recordTypeGOK) {
            $indexName = 'lkl';
        }

        return $indexName;
    }

}
