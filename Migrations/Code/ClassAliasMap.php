<?php

return [
    'tx_nkwgok_loadFromOpac' => \Subugoe\Nkwgok\Task\LoadFromOpacTask::class,
    'tx_nkwgok_convertCSV' => \Subugoe\Nkwgok\Task\LoadXmlTask::class,
    'tx_nkwgok_loadxml' => \Subugoe\Nkwgok\Task\LoadXmlTask::class,
    'tx_nkwgok_checkNewCSV' => \Subugoe\Nkwgok\Task\CheckNewCsvTask::class,
    'tx_nkwgok_updateCSV' => \Subugoe\Nkwgok\Task\UpdateCsvTask::class,
    'tx_nkwgok_importAll' => \Subugoe\Nkwgok\Task\ImportAllTask::class,
];
