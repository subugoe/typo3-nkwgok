<?php

namespace Subugoe\Nkwgok\Task;

use Subugoe\Nkwgok\Command\LoadXmlCommand;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Scheduler Task - just a wrapper for the real task to appear in the scheduler menu.
 */
class LoadXmlTask extends Task
{
    public function execute()
    {
        $task = GeneralUtility::makeInstance(LoadXmlCommand::class);

        return $task->execute($this->input, $this->output);
    }
}
