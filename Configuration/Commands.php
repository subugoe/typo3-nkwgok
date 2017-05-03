<?php

return [
    'nkwgok:updateCsv' => [
        'class' => \Subugoe\Nkwgok\Command\UpdateCsvCommand::class,
    ],
    'nkwgok:importAll' => [
        'class' => \Subugoe\Nkwgok\Command\ImportAllCommand::class,
    ],
    'nkwgok:checkNewCsv' => [
        'class' => \Subugoe\Nkwgok\Command\CheckNewCsvCommand::class,
    ],
    'nkwgok:loadFromOpac' => [
        'class' => \Subugoe\Nkwgok\Command\LoadFromOpacCommand::class,
    ],
    'nkwgok:loadXml' => [
        'class' => \Subugoe\Nkwgok\Command\LoadXmlCommand::class,
    ],
];
