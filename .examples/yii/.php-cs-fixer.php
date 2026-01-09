<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

ini_set('memory_limit', '512M');

/** @var \PhpCsFixer\Config $phpCsConfig */
$phpCsConfig = require(dirname(__DIR__, 2) . '/.php-cs-fixer.dist.php');

$root = __DIR__;
$finder = (new Finder())
    ->in([
        $root . '/config',
        $root . '/src',
        $root . '/tests',
    ])
    ->exclude([
        'Support/_generated',
    ])
    ->append([
        $root . '/public/index.php',
    ]);

return $phpCsConfig
    ->setCacheFile(__DIR__ . '/runtime/cache/.php-cs-fixer.cache')
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        ...$phpCsConfig->getRules(),
        'header_comment' => false,
    ])
    ->setFinder($finder);
