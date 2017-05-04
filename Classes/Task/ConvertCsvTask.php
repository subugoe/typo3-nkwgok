<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Task;

use Subugoe\Nkwgok\Command\ConvertCsvCommand;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Scheduler Task - just a wrapper for the real task to appear in the scheduler menu.
 */
class ConvertCsvTask extends Task
{
    public function execute()
    {
        $task = GeneralUtility::makeInstance(ConvertCsvCommand::class, $this->getTaskTitle());

        return $task->execute($this->input, $this->output);
    }
}
