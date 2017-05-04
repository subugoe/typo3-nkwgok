<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Task;

use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

abstract class Task extends AbstractTask
{
    /**
     * @var StringInput
     */
    protected $input;

    /**
     * @var NullOutput
     */
    protected $output;

    public function __construct()
    {
        parent::__construct();
        $this->input = new StringInput($this->getTaskTitle());
        $this->output = new NullOutput();
    }

    public function execute()
    {
    }
}
