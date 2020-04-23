<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Ajax;

use Psr\Http\Message\ServerRequestInterface;
use Subugoe\Nkwgok\Elements\Element;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Menu
{
    /**
     * @return string
     */
    public function main(ServerRequestInterface $request, Response $response = null)
    {
        if (!$response) {
            $response = GeneralUtility::makeInstance(Response::class);
        }

        $arguments = $request->getQueryParams();
        $nkwgok = Element::instantiateSubclassFor($arguments['tx_nkwgok']);

        $response->getBody()->write($nkwgok->getAJAXMarkup()->saveHTML());

        return $response;
    }
}
