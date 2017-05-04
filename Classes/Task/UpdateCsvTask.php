<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Task;

use Subugoe\Nkwgok\Command\UpdateCsvCommand;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Scheduler Task - just a wrapper for the real task to appear in the scheduler menu.
 */
class UpdateCsvTask extends Task
{
    public function execute()
    {
        $task = GeneralUtility::makeInstance(UpdateCsvCommand::class, $this->getTaskTitle());

        return $task->execute($this->input, $this->output);
    }
}
