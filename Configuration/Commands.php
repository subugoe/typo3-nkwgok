<?php

return [
    'nkwgok:csv:update' => [
        'class' => \Subugoe\Nkwgok\Command\UpdateCsvCommand::class,
    ],
    'nkwgok:import:all' => [
        'class' => \Subugoe\Nkwgok\Command\ImportAllCommand::class,
    ],
    'nkwgok:csv:check' => [
        'class' => \Subugoe\Nkwgok\Command\CheckNewCsvCommand::class,
    ],
    'nkwgok:opac:loac' => [
        'class' => \Subugoe\Nkwgok\Command\LoadFromOpacCommand::class,
    ],
    'nkwgok:xml:load' => [
        'class' => \Subugoe\Nkwgok\Command\LoadXmlCommand::class,
    ],
];
