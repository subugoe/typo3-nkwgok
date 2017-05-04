<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Task;

use Subugoe\Nkwgok\Command\LoadFromOpacCommand;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Scheduler Task - just a wrapper for the real task to appear in the scheduler menu.
 */
class LoadFromOpacTask extends Task
{
    public function execute()
    {
        $task = GeneralUtility::makeInstance(LoadFromOpacCommand::class, $this->getTaskTitle());

        return $task->execute($this->input, $this->output);
    }
}
