<?php

declare(strict_types=1);

namespace Subugoe\Nkwgok\Importer;

interface ImporterInterface
{
    public function run(): bool;
}
