<?php

namespace Subugoe\Nkwgok\Utility;

/**
 * Class providing helper functions and constants.
 */
class Utility
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
     * * pass others through unchanged.
     *
     * @param string $indexName
     *
     * @return string
     */
    public static function indexNameToType($indexName)
    {
        $type = $indexName;

        if ($indexName === 'lkl') {
            $type = self::recordTypeGOK;
        }

        return $type;
    }

    /**
     * Returns the internal type name for the given index name.
     * * gok -> lkl
     * * pass others through unchanged.
     *
     * @param string $type
     *
     * @return string
     */
    public static function typeToIndexName($type)
    {
        $indexName = $type;

        if ($type === self::recordTypeGOK) {
            $indexName = 'lkl';
        }

        return $indexName;
    }
}
