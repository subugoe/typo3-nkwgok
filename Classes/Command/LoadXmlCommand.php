<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Command;

use Subugoe\Nkwgok\Importer\LoadXml;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Nils K. Windisch (windisch@sub.uni-goettingen.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * ************************************************************* */

/**
 * Class "tx_nkwgok_loadxml" provides task procedures.
 */
class LoadXmlCommand extends Command
{
    /**
     * Function executed from the Scheduler.
     *
     * @return bool TRUE if success, otherwise FALSE
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(1200);
        $service = GeneralUtility::makeInstance(LoadXml::class);

        return $service->run();
    }
}