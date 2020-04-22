<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Command;

use Subugoe\Nkwgok\Importer\LoadFromOpac;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 Scheduler task to download the OPAC data we need and store them in
 * fileadmin/gok/...
 *
 * Unifies the features provided by class.tx_nkwgok_loadxml.php and
 * getHitCounts.py and makes them accessible from the TYPO3 Scheduler.
 */
class LoadFromOpacCommand extends Command
{
    public function configure()
    {
        parent::configure();
        $this->setDescription('Download the OPAC data we need');
    }

    /**
     * Function executed from the Scheduler.
     *
     * @return bool TRUE if success, otherwise FALSE
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(1200);

        $service = GeneralUtility::makeInstance(LoadFromOpac::class);

        return $service->run();
    }
}
