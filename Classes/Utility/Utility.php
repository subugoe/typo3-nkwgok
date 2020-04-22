<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Utility;

/**
 * Class providing helper functions and constants.
 */
class Utility
{
    public const extKey = 'nkwgok';
    public const dataTable = 'tx_nkwgok_data';

    public const rootNode = 'Root';

    public const recordTypeGOK = 'gok';
    public const recordTypeBRK = 'brk';
    public const recordTypeCSV = 'csv';
    public const recordTypeMSC = 'msc';
    public const recordTypeUnknown = 'unknown';

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

        if ('lkl' === $indexName) {
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

        if (self::recordTypeGOK === $type) {
            $indexName = 'lkl';
        }

        return $indexName;
    }
}
