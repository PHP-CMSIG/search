<?php

declare(strict_types=1);

/** @var \PhpCsFixer\Config $phpCsConfig */
$phpCsConfig = require(dirname(__DIR__, 2) . '/.php-cs-fixer.dist.php');

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ])
    ->ignoreVCSIgnored(true);

$phpCsConfig->setFinder($finder);
$phpCsConfig->setRules([
    ...$phpCsConfig->getRules(),
    'header_comment' => false,
]);

return $phpCsConfig->setFinder($finder);
