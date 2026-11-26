<?php

declare(strict_types=1);

$phpCsConfig = require(dirname(__DIR__, 2) . '/.php-cs-fixer.dist.php');

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->ignoreVCSIgnored(true);

$phpCsConfig->setFinder($finder);

return $phpCsConfig->setFinder($finder);
