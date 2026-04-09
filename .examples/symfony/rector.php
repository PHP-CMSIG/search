<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return static function (RectorConfig $rectorConfig): void {
    $config = require __DIR__ . '/../../rector.php';
    $config($rectorConfig, __DIR__);

    $rectorConfig->skip([
        __DIR__ . '/config/bundles.php',
        __DIR__ . '/config/reference.php',
    ]);

    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/tests',
    ]);
};
