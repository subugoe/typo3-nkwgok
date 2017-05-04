<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Task;

use Subugoe\Nkwgok\Command\ImportAllCommand;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Scheduler Task - just a wrapper for the real task to appear in the scheduler menu.
 */
class ImportAllTask extends Task
{
    public function execute()
    {
        $task = GeneralUtility::makeInstance(ImportAllCommand::class);

        return $task->execute($this->input, $this->output);
    }
}
