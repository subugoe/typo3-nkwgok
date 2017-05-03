<?php

namespace Subugoe\Nkwgok\Ajax;

use Subugoe\Nkwgok\Elements\Element;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Menu
{
    /**
     * @return string
     */
    public function main()
    {
        $arguments = GeneralUtility::_GET('tx_nkwgok');
        $nkwgok = Element::instantiateSubclassFor($arguments);

        return $nkwgok->getAJAXMarkup()->saveHTML();
    }
}
